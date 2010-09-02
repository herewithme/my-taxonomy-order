<?php
/*
Plugin Name: Simple Terms Order
Plugin URI: http://www.beapi.fr
Description: A admin for order terms in taxonomies
Author: Be API
Author URI: http://beapi.fr
Version: 1.3.2
*/
//Register the plugin path
define( 'STO_URL', plugins_url('/', __FILE__) );
define( 'STO_DIR', dirname(__FILE__) );

//Include the widget part
include_once(STO_DIR.'/inc/class.widget.php');

//At the activation of the plugin add the column
register_activation_hook  ( __FILE__, array( 'SimpleTermsOrder', 'activate' ) );

//Add the actions when we are on admin
if ( is_admin() )
	add_action( 'plugins_loaded', 'initSimpleTermsOrder' );

//Add the actions for widget and ordering
add_action( 'widgets_init', 'simpletermorder_widgets_init' );
add_filter( 'get_terms_orderby', 'applyOrderFilter', 10, 2 );	


/**
 * Instaciate class and load language
 * 
 * @access public
 * @return void
 */
function initSimpleTermsOrder() {
	global $simple_terms_order;
	$simple_terms_order = new SimpleTermsOrder();
	
	// Load translations
	load_plugin_textdomain ( 'simpletermorder', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
}

/**
 * Register the widget
 *
 * @return void
 * @author Nicolas Juen
 */	
function simpletermorder_widgets_init() {
	register_widget( 'simpletermorder_Widget' );
}

/**
 * Add custom order to the filter
 *
 * @return void
 * @author Nicolas Juen
 */
function applyOrderFilter( $orderby, $args ){
	if( $args['orderby'] == 'order' )
		return 'tt.term_order';
	else
		return $orderby;
}

class SimpleTermsOrder {
	var $simple_terms = null;
	
	function SimpleTermsOrder() {
		add_action( 'admin_init', array( &$this, 'registerJavaScript'), 11 );
		add_action( 'admin_menu', array( &$this, 'addMenu' ) );	
		add_action( 'wp_ajax_update-simple-terms-order', array( &$this, 'saveAjaxOrder' ) );
	}
	
	/**
 	* Create the column for the plugin if needed
 	* 
 	* @return void
 	* @author Nicolas Juen
 	*/
	function activate(){
		global $wpdb;
		//Test if the term already exists or not
		$query = $wpdb->query( "SHOW COLUMNS FROM $wpdb->term_taxonomy LIKE 'term_order'" );
	
		// Add the collumn if not existing
		if ( $query == 0 )
			$wpdb->query( "ALTER TABLE $wpdb->term_taxonomy ADD `term_order` INT( 4 ) NULL DEFAULT '0'" );
	}
	
	/**
	 * Register the ui sortable javascript
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function registerJavaScript() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == "simpletermorder" ){	
			// jQuery UI Sortable
			wp_enqueue_script( 'cust-jquery-ui-sortable', plugins_url('/js/ui.nestedSortable.js', __FILE__), array('jquery', 'jquery-ui-core'), '1.7.2', false );
			
			wp_enqueue_style( 'nav-menu');
		}
	}
	
	/**
	 * Wait a AJAX call for save order of items of the custom type
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function saveAjaxOrder() {
		global $wpdb;
		
		parse_str($_POST['order'], $output);
		var_dump($output);
		foreach( (array) $output as $key => $values ) {
			if ( $key == 'item' ) {
				foreach( $values as $position => $id ) {					
					$wpdb->update( $wpdb->term_taxonomy, array( 'term_order' => $position, 'parent' => 0 ), array( 'term_id' => $id ), array( '%d' , '%d' ), array( '%d' ) );
				} 
			} else {
				foreach( $values as $position => $id ) {
					$wpdb->update( $wpdb->term_taxonomy, array( 'term_order' => $position, 'parent' => str_replace( 'item_', '', $key ) ), array( 'term_id' => $id ), array( '%d' , '%d' ), array( '%d' ) );
				}
			}
		}
	}
	
	/**
	 * Add pages on menu
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function addMenu() {
		add_management_page( __( 'Simple Term Order', 'simpletermorder' ), __( 'Terms Order', 'simpletermorder' ), 'manage_options', 'simpletermorder', array(&$this, 'pageManage') );
	}
	
	/**
	 * Allow to build the HTML page for order...
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function pageManage() {
		?>
		<div class="wrap" id="postcustomstuff">
			<?php
				//Get all taxonomies
				$taxonomies = get_taxonomies( array('hierarchical' => true), 'objects' );
				
				//Get the taxonmy filtered, if not get first key of taxonmies getted
				$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : key( $taxonomies );
				
				//Construct the options for taxonomies
				$taxStr = '';
				foreach( $taxonomies as $tax ){
					$taxStr .= '<option '.selected( $tax->name, $taxonomy, false ).' value="'.$tax->name.'" >';
					$taxStr .= $tax->labels->singular_name.' ('.$tax->name.')'; 
					$taxStr .= '</option>'.'\n'; 
				}
			?>
			<h2><?php printf(__('Order this taxonomy : %s', 'simpletermorder'), $taxonomy); ?></h2>
			<select id="taxonomy" name="taxonomy">
				<?php echo $taxStr; ?>
			</select>
			<input type="button" name="edit" class="button-secondary" value="<?php _e( 'Order this taxonomy', 'simpletermorder' ); ?>" onClick="javascript:goEdit( false );">
			<div id="ajax-response"></div>
			
			<noscript>
				<div class="error message">
					<p><?php _e('This plugin can\'t work without javascript, because it\'s use drag and drop and AJAX.', 'simpletermorder'); ?></p>
				</div>
			</noscript>
			
			<div id="order-taxonomy-terms">
				<ul id="sortable" class="menu">
					<?php $this->listTerms('hide_empty=0&title_li=&orderby=order&taxonomy='.$taxonomy); ?>
				</ul>
				
				<div class="clear"></div>
			</div>
			
			<p class="submit">
				<a href="#" id="save-order" class="button-primary"><?php _e('Save order', 'simpletermorder'); ?></a>
			</p>
			
			<style type="text/css">
				#sortable ul { margin-bottom: 10px }
				#sortable li.menu-item-bar { border: 1px solid #aaa; width:90%}
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#sortable").sortable({
						'tolerance':'intersect',
						'cursor':'pointer',
						'items':' li',
						'placeholder':'sortable-placeholder',
						'nested': 'ul',
						'revert' : true
					});
					
					jQuery("#sortable").disableSelection();
					jQuery("#save-order").bind( "click", function() {
						jQuery.post( ajaxurl, { action:'update-simple-terms-order', order:jQuery("#sortable").sortable("serialize") }, function() {
							jQuery("#ajax-response").html('<div class="message updated fade"><p><?php echo esc_js(__("Items sorted with success !", "simpletermorder")); ?></p></div>');
							jQuery("#ajax-response div").delay(2000).hide("slow");
						});
						return false;
					});
				});
				function goEdit (){
					var taxs = '';
					if( jQuery( '#taxonomy' ).val() != "" ){
						location.href="edit.php?page=simpletermorder&taxonomy="+jQuery( "#taxonomy" ).val();
					}
				}
			</script>
		</div>
		<?php
	}

	/**
	 * Retrieve or display list of pages in list (li) format.
	 *
	 * @since 1.5.0
	 *
	 * @param array|string $args Optional. Override default arguments.
	 * @return string HTML content, if not displaying.
	 */
	function listTerms($args = '') {
		$defaults = array(
		'show_option_all' => '', 'show_option_none' => __('No categories'),
		'orderby' => 'name', 'order' => 'ASC',
		'show_last_update' => 0, 'style' => 'list',
		'show_count' => 0, 'hide_empty' => 1,
		'use_desc_for_title' => 1, 'child_of' => 0,
		'feed' => '', 'feed_type' => '',
		'feed_image' => '', 'exclude' => '',
		'exclude_tree' => '', 'current_category' => 0,
		'hierarchical' => true, 'title_li' => __( 'Categories' ),
		'echo' => 1, 'depth' => 0,
		'taxonomy' => 'category'
	);

	$r = wp_parse_args( $args, $defaults );

	if ( !isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] )
		$r['pad_counts'] = true;

	if ( isset( $r['show_date'] ) )
		$r['include_last_update_time'] = $r['show_date'];

	if ( true == $r['hierarchical'] ) {
		$r['exclude_tree'] = $r['exclude'];
		$r['exclude'] = '';
	}

	if ( !isset( $r['class'] ) )
		$r['class'] = ( 'category' == $r['taxonomy'] ) ? 'categories' : $r['taxonomy'];

	extract( $r );

	if ( !taxonomy_exists($taxonomy) )
		return false;

	$categories = get_categories( $r );

	$output = '';
	if ( $title_li && 'list' == $style )
			$output = '<li class="' . $class . '">' . $title_li . '<ul>';

	if ( empty( $categories ) ) {
		if ( ! empty( $show_option_none ) ) {
			if ( 'list' == $style )
				$output .= '<li>' . $show_option_none . '</li>';
			else
				$output .= $show_option_none;
		}
	} else {
		global $wp_query;

		if( !empty( $show_option_all ) )
			if ( 'list' == $style )
				$output .= '<li class="menu-item-handle"><a href="' .  get_bloginfo( 'url' )  . '">' . $show_option_all . '</a></li>';
			else
				$output .= '<a href="' .  get_bloginfo( 'url' )  . '">' . $show_option_all . '</a>';

		if ( empty( $r['current_category'] ) && ( is_category() || is_tax() ) )
			$r['current_category'] = $wp_query->get_queried_object_id();

		if ( $hierarchical )
			$depth = $r['depth'];
		else
			$depth = -1; // Flat.

		$output .= $this->walkTree( $categories, $depth, $r );
	}

	if ( $title_li && 'list' == $style )
		$output .= '</ul></li>';

	$output = apply_filters( 'wp_list_categories', $output, $args );

	if ( $echo )
		echo $output;
	else
		return $output;
	}
		/**
	 * Retrieve HTML list content for page list.
	 *
	 * @uses Walker_Page to create HTML list content.
	 * @since 2.1.0
	 * @see Walker_Page::walk() for parameters and return description.
	 */
	function walkTree($pages, $depth, $r) {
		$args = func_get_args();
		// the user's options are the third parameter
		if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
			$walker = new Taxonomy_Order_Walker;
		else
			$walker = $args[2]['walker'];
	
		return call_user_func_array(array( &$walker, 'walk' ), $args );
	}
}