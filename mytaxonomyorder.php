<?php
/*
Plugin Name: My Taxonomy Order
Plugin URI: http://www.beapi.fr
Description: My Taxonomy Order allows you to set the order in which taxonomy will appear. Uses a drag and drop interface for ordering. Adds a widget with additional options for easy installation on widgetized themes.
Version: 1.0
Author: Beapi
Author URI: http://www.beapi.fr

Original work from My category Order! by Andrew Charlton | http://www.geekyweekly.com

*/

//Disable of my category order plugin and load translations
add_action( 'init', 'disable_mycategoryorder' );
add_action( 'init', 'mytaxonomyorder_loadtranslation' );

//Initialize the plugin and instanciate the widget
add_action( 'plugins_loaded', 'mytaxonomyorder_init' );
add_action( 'widgets_init', 'mytaxonomyorder_widgets_init' );

//Add the javascript for option page and the option page
add_action('admin_menu', 'mytaxonomyorder_menu');
add_action('admin_menu', 'mytaxonomyorder_js_libs');

//Add filter during the order by and add link on plugin page
add_filter( 'plugin_row_meta', 'mytaxonomyorder_set_plugin_meta', 10, 2 );
add_filter( 'get_terms_orderby', 'mytaxonomyorder_applyorderfilter', 10, 2 );

/**
 * Disable all hooks of mycategoryorder for compatibility reasons
 *
 * @return void
 * @author Nicolas Juen
 */	
function disable_mycategoryorder(){
	remove_filter( 'plugin_row_meta','mycategoryorder_set_plugin_meta' );
	remove_action( 'admin_menu','mycategoryorder_menu' );
	remove_action( 'admin_menu','mycategoryorder_js_libs' );
	remove_filter( 'get_terms_orderby', 'mycategoryorder_applyorderfilter' );
	remove_action( 'plugins_loaded', 'mycategoryorder_init' );
	remove_action( 'init', 'mycategoryorder_loadtranslation' );
	remove_action( 'widgets_init', 'mycategoryorder_widgets_init' );
}
/**
 * Register the widget for admin
 *
 * @return void
 * @author Nicolas Juen
 */	
function mytaxonomyorder_widgets_init() {
	register_widget( 'mytaxonomyorder_Widget' );
}

/**
 * Load translations of the plugin
 *
 * @return void
 * @author Nicolas Juen
 */
function mytaxonomyorder_loadtranslation() {
	load_plugin_textdomain( 'mytaxonomyorder', false, 'my-taxonomy-order' . '/languages' );
}

/**
 * Add custom order to the filter
 *
 * @return void
 * @author Nicolas Juen
 */
function mytaxonomyorder_applyorderfilter( $orderby, $args ){
	if( $args['orderby'] == 'order' )
		return 'tt.term_order';
	else
		return $orderby;
}

/**
 * Init of the plugin
 *
 * @return void
 * @author Nicolas Juen
 */
function mytaxonomyorder_init() {
	
	/**
	 * Add to the tools menu the page for ordering items
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function mytaxonomyorder_menu() {   
		if ( function_exists( 'add_management_page' ) )
			add_management_page( __( 'My Taxonomy Order', 'mytaxonomyorder' ), __( 'My Taxonomy Order', 'mytaxonomyorder' ), 'manage_options', 'mytaxonomyorder', 'mytaxonomyorder' );
	}
	
	/**
	 * Add javascript to the admin for re-ordering
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function mytaxonomyorder_js_libs() {
		$page = isset( $_GET['page'] )? $_GET['page'] : '' ;
		if ( $page == "mytaxonomyorder" ){	
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}
	
	/**
	 * Return the name file for editing the order of items
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	function mytaxonomyorder_getTarget() {
		return "edit.php";
	}
	
	/**
	 * Add links to the plugin row
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	function mytaxonomyorder_set_plugin_meta( $links, $file ) {
		$plugin = plugin_basename( __FILE__ );
		// create link
		if ( $file == $plugin ) {
			return array_merge( $links, array( 
				'<a href="'.mytaxonomyorder_getTarget().'?page=mytaxonomyorder">' . __( 'Order Taxonomy', 'mytaxonomyorder' ) . '</a>',
			));
		}
		return $links;
	}
	
	/**
	 * Generate the content of the option plugin page
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	function mytaxonomyorder() {
		global $wpdb;
		
		$mode = "";
		$mode = isset( $_GET['mode'] ) ? $_GET['mode'] : '' ;
		$parentID = 0;
		$success = "";
		//Set parent if needed
		if ( isset( $_GET['parentID'] ) )
		    $parentID = $_GET['parentID'];
		
		//Display Query errors
		$wpdb->show_errors();
		
		//Get all taxonomies
		$taxonomies = get_taxonomies( '', 'objects' );
		
		//Get the taxonmy filtered, if not get first key of taxonmies getted
		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : key( $taxonomies );
		
		//Construct the options for taxonomies
		$taxStr = '';
		foreach( $taxonomies as $tax ){
			$taxStr .= '<option '.selected( $tax->name, $taxonomy, false ).' value="'.$tax->name.'" >';
			$taxStr .= $tax->labels->singular_name.' ('.$tax->name.')'; 
			$taxStr .= '</option>'.'\n'; 
		}
		
		//Test if the term already exists or not
		$query1 = $wpdb->query( "SHOW COLUMNS FROM $wpdb->term_taxonomy LIKE 'term_order'" );
		
		// Add the collumn if not existing
		if ( $query1 == 0 )
			$wpdb->query( "ALTER TABLE $wpdb->term_taxonomy ADD `term_order` INT( 4 ) NULL DEFAULT '0'" );
		
		//Case of ordering children and parent
		if( $mode == "act_OrderTaxonomies" ){  
			$idString = $_GET['idString'];
			$taxIDs = explode( ",", $idString );
			$result = count( $taxIDs );
			
			for( $i = 0; $i < $result; $i++ )
				$wpdb->query("UPDATE $wpdb->term_taxonomy SET term_order = '$i' WHERE term_id ='$catIDs[$i]'");
				
			$success = '<div id="message" class="updated fade"><p>'. __( 'Taxonomy updated successfully.', 'mytaxonomyorder' ).'</p></div>';
		}
		
		//Get terms for current taxonomy
		$results=$wpdb->get_results( "SELECT t.term_id, t.name FROM $wpdb->term_taxonomy tt, $wpdb->terms t, $wpdb->term_taxonomy tt2 WHERE tt.parent = $parentID AND tt.taxonomy = '".$taxonomy."' AND t.term_id = tt.term_id AND tt2.parent = tt.term_id GROUP BY t.term_id, t.name HAVING COUNT(*) > 0 ORDER BY tt.term_order ASC" );
		
		//Create the option string of subtaxonomies
		$subTaxStr = "";
		foreach( $results as $row )
			$subTaxStr = $subTaxStr.'<option value="'.$row->term_id.'">'.$row->name.'</option>'.'\n';
	?>
		<div class='wrap'>
			<h2><?php _e( 'My Taxonomy Order','mytaxonomyorder' ); ?></h2>
		<?php echo $success ; ?>	
		<p><?php _e( 'Choose a taxonomy from the drop down to order subtaxonomies in that taxonomy or order the taxonomies on this level by dragging and dropping them into the desired order.', 'mytaxonomyorder' ); ?></p>
	
		<?php 
		//If parent
		if( $parentID != 0 ){
			//Get all children terms of parentId
			$parentsParent = $wpdb->get_row( "SELECT parent FROM $wpdb->term_taxonomy WHERE term_id = $parentID ", ARRAY_N );
			
			//Display the link to parent page
			echo '<a href="'. mytaxonomyorder_getTarget() . '?page=mytaxonomyorder&parentID='.$parentsParent[0].'&taxonomy='.$taxonomy.'">'.__( 'Return to parent taxonomy', 'mytaxonomyorder' ).'</a>';
		}
		?>
		
		<h3><?php _e( 'Select taxonomy', 'mytaxonomyorder' ); ?></h3>
		<select id="taxonomy" name="taxonomy">
			<?php echo $taxStr; ?>
		</select>
		&nbsp;<input type="button" name="edit" Value="<?php _e( 'Order taxonomies', 'mytaxonomyorder' ); ?>" onClick="javascript:goEdit();">
		<?php if( $subTaxStr != "" ){	?>
			<h3><?php _e( 'Order subtaxonomies', 'mytaxonomyorder' ); ?></h3>
			<select id="taxs" name="taxs">
				<?php echo $subTaxStr; ?>
			</select>
			&nbsp;<input type="button" name="edit" Value="<?php _e( 'Order Subtaxonomies', 'mytaxonomyorder' ); ?>" onClick="javascript:goEdit();">
		<?php }
		//Get all terms of parent
		$results=$wpdb->get_results( "SELECT * FROM ".$wpdb->terms." t inner join ".$wpdb->term_taxonomy." tt on t.term_id = tt.term_id WHERE taxonomy = '".$taxonomy."' and parent = ".$parentID." ORDER BY tt.term_order ASC" ); ?>
		<h3><?php _e( 'Order taxonomies', 'mytaxonomyorder' ); ?></h3>
	    <ul id="order" style="width: 90%; margin:10px 10px 10px 0px; padding:10px; border:1px solid #B2B2B2; list-style:none;">
			<?php 
			//Display all terms
			foreach( $results as $row )
				echo "<li id='".$row->term_id."' class='lineitem'>".$row->name."</li>";
			?>
		</ul>
	
		<input type="button" id="orderButton" Value="<?php _e( 'Click to Order Taxonomies', 'mytaxonomyorder' ); ?>" onclick="javascript:orderCats();">&nbsp;&nbsp;<strong id="updateText"></strong>
	
	</div>
	
	<style>
		li.lineitem {
			margin: 3px 0px;
			padding: 2px 5px 2px 5px;
			background-color: #F1F1F1;
			border:1px solid #B2B2B2;
			cursor: move;
		}
	</style>
	
	<script language="JavaScript">
		
		function mytaxonomyorderaddloadevent(){
			jQuery("#order").sortable({ 
				placeholder: "ui-selected", 
				revert: false,
				tolerance: "pointer" 
			});
		};
	
		addLoadEvent(mytaxonomyorderaddloadevent);
	
		function orderCats() {
			jQuery( "#orderButton" ).css( "display", "none" );
			jQuery( "#updateText" ).html("<?php _e( 'Updating taxonomy Order...', 'mytaxonomyorder' ); ?>");
			
			idList = jQuery( "#order" ).sortable( "toArray" );
			var taxo = '';
			if( jQuery( '#taxonomy' ).val() != "" ){
				taxo = "&taxonomy=<?php echo $taxonomy; ?>";
			}
			location.href = '<?php echo mytaxonomyorder_getTarget(); ?>?page=mytaxonomyorder&mode=act_OrderTaxonomies&parentID=<?php echo $parentID; ?>&idString='+idList+taxo;
		}
		
		function goEdit (){
			var taxs = '';
			if( jQuery( '#taxonomy' ).val() != "" ){
				if( jQuery( "#taxs" ).val() != "" && jQuery( "#taxs" ).val() != undefined )
					taxs = "&parentID="+jQuery( "#taxs" ).val();
				location.href="<?php echo mytaxonomyorder_getTarget(); ?>?page=mytaxonomyorder&taxonomy="+jQuery( "#taxonomy" ).val()+taxs;
			}elseif( jQuery( "#taxs" ).val() != "" )
				location.href="<?php echo mytaxonomyorder_getTarget(); ?>?page=mytaxonomyorder&parentID="+jQuery( "#taxs" ).val();
		}
	</script>
	
	<?php
	}
}

class mytaxonomyorder_Widget extends WP_Widget {

	function mytaxonomyorder_Widget() {		
		//Initialisation of the widget
		$widget_ops = array( 
			'classname' 	=> 'widget_mytaxonomyorder', 
			'description' 	=> __( 'Enhanced Taxonomy widget provided by My Taxonomy Order','mytaxonomyorder' ) 
		);
		$this->WP_Widget( 'mytaxonomyorder', __( 'My Taxonomy Order', 'mytaxonomyorder' ), $widget_ops );			
	}
	
	/**
	 * Displaying the widget
	 *
	 * @param array, arguments of the widget
	 * @param array, instance of the widget
	 * @return void
	 * @author Nicolas Juen
	 */
	function widget( $args, $instance ) {
		//Extraction all variables
		extract( $args );		
		
		//Testing all variables
		$title_li 			= apply_filters('widget_title', empty( $instance['title_li'] ) ? __( 'Taxonomies','mytaxonomyorder' ) : $instance['title_li']);
		$taxonomy 			= empty( $instance['taxonomy'] ) ? 'category' : $instance['taxonomy'];
		$orderby 			= empty( $instance['orderby'] ) ? 'order' : $instance['orderby'];
		$order 				= empty( $instance['order'] ) ? 'asc' : $instance['order'];
		$show_dropdown 		= (bool) $instance['show_dropdown'];
		$show_last_updated 	= (bool) $instance['show_last_updated'];
		$show_count 		= (bool) $instance['show_count'];
		$hide_empty 		= (bool) $instance['hide_empty'];
		$use_desc_for_title = (bool) $instance['use_desc_for_title'];
		$child_of 			= empty( $instance['child_of'] ) ? '' : $instance['child_of'];
		$feed 				= empty( $instance['feed'] ) ? '' : $instance['feed'];
		$feed_image 		= empty( $instance['feed_image'] ) ? '' : $instance['feed_image'];
		$exclude 			= empty( $instance['exclude'] ) ? '' : $instance['exclude'];
		$exclude_tree 		= empty( $instance['exclude_tree'] ) ? '' : $instance['exclude_tree'];
		$include 			= empty( $instance['include'] ) ? '' : $instance['include'];
		$hierarchical 		= empty( $instance['hierarchical'] ) ? '1' : $instance['hierarchical'];
		$number 			= empty( $instance['number'] ) ? '' : $instance['number'];
		$depth 				= empty( $instance['depth'] ) ? '0' : $instance['depth'];
		
		//Display the before_widget parameter if filled
		echo $before_widget;
		
		//Display the before and after title html
		if ( $title_li )
			echo $before_title . $title_li . $after_title;
		
		//Create the arguments for selecting right taxonomies and right order
		$cat_args = array( 
			'orderby' 				=> $orderby, 
			'order' 				=> $order, 
			'show_last_updated' 	=> $show_last_updated, 
			'show_count' 			=> $show_count, 
			'hide_empty' 			=> $hide_empty, 
			'use_desc_for_title' 	=> $use_desc_for_title, 
			'child_of' 				=> $child_of, 
			'feed' 					=> $feed, 
			'feed_image' 			=> $feed_image, 
			'exclude' 				=> $exclude, 
			'exclude_tree' 			=> $exclude_tree, 
			'include' 				=> $include,
			'hierarchical' 			=> $hierarchical, 
			'number' 				=> $number, 
			'depth' 				=> $depth, 
			'taxonomy' 				=> $taxonomy, 
			'name' 					=> $taxonomy, 
			'id' 					=> 'taxonomy' 
		);
		
		//If dropdown
		if ( $show_dropdown ) {
			$cat_args['show_option_none'] = __( 'Select Term','mytaxonomyorder' );
			wp_dropdown_categories( apply_filters( 'widget_categories_dropdown_args', $cat_args ) );
		?>

		<script type='text/javascript'>
		/* <![CDATA[ */
			//Get the dropdown element
			var dropdown = document.getElementById( "taxonomy" );
			
			//Function to trim all spaces
			String.prototype.trim = function(){
			  return this.replace(/^\s+/,'').replace(/\s+$/,'');
			}
			
			//Action to call on Taxonomy change on dropdown menu
			function onTaxChange() {
				//If we call a category we have to change the way of get on the page
				if ( dropdown.options[dropdown.selectedIndex].value > 0 && dropdown.name == 'category' )
					location.href = "<?php echo get_option( 'home' ); ?>/?cat="+dropdown.options[dropdown.selectedIndex].value;
				else{
					//Get the term text selected
					term = dropdown.options[dropdown.selectedIndex].text;
					//Redirect on right custom type
					location.href = "<?php echo get_option( 'home' ); ?>/?"+dropdown.name+"="+term.trim();
				}				
			}
			dropdown.onchange = onTaxChange;
		/* ]]> */
		</script>

		<?php } else { ?>
		<ul>
		<?php 
			//Remove the title li
			$cat_args['title_li'] = '';
			
			//Display the widget
			wp_list_categories( apply_filters( 'widget_categories_args', $cat_args ) );
		?>
		</ul>
		<?php
		}

		echo $after_widget;
	}
	
	/**
	 * Update the widget options
	 *
	 * @param $new_instance array, new values of widget
	 * @param $old_instance array, old values of widget
	 * @return $instance array
	 * @author Nicolas Juen
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		//Update the ordering option with custom order
		if ( in_array( $new_instance['orderby'], array( 'order', 'name', 'count', 'ID', 'slug', 'term_group' ) ) )
			$instance['orderby'] = $new_instance['orderby'];
		else
			$instance['orderby'] = 'order';

		//Update the asc or desc option 
		if ( in_array( $new_instance['order'], array( 'asc', 'desc' ) ) )
			$instance['order'] = $new_instance['order'];
		else
			$instance['order'] = 'asc';
		
		//Update all the others options
		$instance['taxonomy']			= $new_instance['taxonomy'];
		$instance['title_li'] 			= strip_tags( $new_instance['title_li'] );	
		$instance['show_dropdown'] 		= strip_tags( $new_instance['show_dropdown'] );
		$instance['show_last_updated'] 	= strip_tags( $new_instance['show_last_updated'] );
		$instance['show_count'] 		= strip_tags( $new_instance['show_count'] );
		$instance['hide_empty'] 		= strip_tags( $new_instance['hide_empty'] );
		$instance['use_desc_for_title'] = strip_tags( $new_instance['use_desc_for_title'] );
		$instance['child_of'] 			= strip_tags( $new_instance['child_of'] );
		$instance['feed'] 				= strip_tags( $new_instance['feed'] );
		$instance['feed_image']			= $new_instance['feed_image'];
		$instance['exclude'] 			= strip_tags( $new_instance['exclude'] );
		$instance['exclude_tree'] 		= strip_tags( $new_instance['exclude_tree'] );
		$instance['include'] 			= strip_tags( $new_instance['include'] );
		$instance['hierarchical'] 		= strip_tags( $new_instance['hierarchical'] );
		$instance['number'] 			= $new_instance['number'];
		$instance['depth'] 				= $new_instance['depth'];
		
		//Return new values for updating
		return $instance;
	}
	
	/**
	 * Display the options of the widget in widget page
	 *
	 * @param $instance array, values of the actual widget
	 * @return void
	 * @author Nicolas Juen
	 */
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 
			'orderby' 				=> 'order', 
			'order' 				=> 'asc', 
			'title_li' 				=> '', 
			'show_dropdown' 		=> '', 
			'show_last_updated' 	=> '', 
			'show_count' 			=> '', 
			'hide_empty' 			=> '1', 
			'use_desc_for_title' 	=> '1', 
			'child_of' 				=> '', 
			'feed' 					=> '', 
			'feed_image'			=> '', 
			'exclude' 				=> '', 
			'exclude_tree' 			=> '', 
			'include' 				=> '', 
			'hierarchical' 			=> '1', 
			'number' 				=> '', 
			'depth' 				=> '' ) 
		);
		
		//Escape all attributes before displaying
		$taxonomy 			= esc_attr( $instance['taxonomy'] );
		$orderby 			= esc_attr( $instance['orderby'] );
		$order 				= esc_attr( $instance['order'] );
		$title_li 			= esc_attr( $instance['title_li'] );
		
		$show_dropdown 		= esc_attr( $instance['show_dropdown'] );
		$show_last_updated 	= esc_attr( $instance['show_last_updated'] );
		$show_count 		= esc_attr( $instance['show_count'] );
		$hide_empty 		= esc_attr( $instance['hide_empty'] );
		$use_desc_for_title = esc_attr( $instance['use_desc_for_title'] );
		$hierarchical 		= esc_attr( $instance['hierarchical'] );
		
		$child_of 			= esc_attr( $instance['child_of'] );
		$feed 				= esc_attr( $instance['feed'] );
		$feed_image 		= esc_attr( $instance['feed_image'] );
		$exclude 			= esc_attr( $instance['exclude'] );
		$exclude_tree 		= esc_attr( $instance['exclude_tree'] );
		$include 			= esc_attr( $instance['include'] );
		
		$number 			= esc_attr( $instance['number'] );
		$depth  			= esc_attr( $instance['depth'] );
		
		//Get all taxonomies
		$taxonomies = get_taxonomies('','objects');

	?>	
		<p>
			<label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e( 'Taxonomy:', 'mytaxonomyorder' ); ?></label>
			<select name="<?php echo $this->get_field_name('taxonomy'); ?>" id="<?php echo $this->get_field_id('taxonomy'); ?>" class="widefat">
				<?php
					foreach( $taxonomies as $tax ){
						echo '<option '.selected( $tax->name, $instance['taxonomy'], false ).' value="'.$tax->name.'" >';
						echo $tax->labels->singular_name.' ('.$tax->name.')'; 
						echo '</option>'; 
					}				
				 ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e( 'Order By:', 'mytaxonomyorder' ); ?></label>
			<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat">
				<option value="order"<?php selected( $instance['orderby'], 'order' ); ?>><?php _e('My Order', 'mytaxonomyorder'); ?></option>
				<option value="name"<?php selected( $instance['orderby'], 'name' ); ?>><?php _e('Name', 'mytaxonomyorder'); ?></option>
				<option value="count"<?php selected( $instance['orderby'], 'count' ); ?>><?php _e( 'Count', 'mytaxonomyorder' ); ?></option>
				<option value="ID"<?php selected( $instance['orderby'], 'ID' ); ?>><?php _e( 'ID', 'mytaxonomyorder' ); ?></option>
				<option value="slug"<?php selected( $instance['orderby'], 'slug' ); ?>><?php _e( 'Slug', 'mytaxonomyorder' ); ?></option>
				<option value="term_group"<?php selected( $instance['orderby'], 'term_group' ); ?>><?php _e( 'Term Group', 'mytaxonomyorder' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e( 'Order:', 'mytaxonomyorder' ); ?></label>
			<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('category_order'); ?>" class="widefat">
				<option value="asc"<?php selected( $instance['order'], 'asc' ); ?>><?php _e('Ascending', 'mytaxonomyorder'); ?></option>
				<option value="desc"<?php selected( $instance['order'], 'desc' ); ?>><?php _e('Descending', 'mytaxonomyorder'); ?></option>
			</select>
		</p>
				<p>
			<label for="<?php echo $this->get_field_id('title_li'); ?>"><?php _e( 'Title:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $title_li; ?>" name="<?php echo $this->get_field_name('title_li'); ?>" id="<?php echo $this->get_field_id('title_li'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Default to Taxonomies.', 'mytaxonomyorder' ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $exclude; ?>" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude_tree'); ?>"><?php _e( 'Exclude Tree:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $exclude_tree; ?>" name="<?php echo $this->get_field_name('exclude_tree'); ?>" id="<?php echo $this->get_field_id('exclude_tree'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('include'); ?>"><?php _e( 'Include:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $include; ?>" name="<?php echo $this->get_field_name('include'); ?>" id="<?php echo $this->get_field_id('include'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('child_of'); ?>"><?php _e( 'Child Of:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $child_of; ?>" name="<?php echo $this->get_field_name('child_of'); ?>" id="<?php echo $this->get_field_id('child_of'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Only display children of this Taxonomy ID.', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('feed'); ?>"><?php _e( 'Feed Text:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $feed; ?>" name="<?php echo $this->get_field_name('feed'); ?>" id="<?php echo $this->get_field_id('feed'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Text for RSS Feed', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('feed_image'); ?>"><?php _e( 'Feed Image:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $feed_image; ?>" name="<?php echo $this->get_field_name('feed_image'); ?>" id="<?php echo $this->get_field_id('feed_image'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'URL to RSS Image, copy url of this image', 'mytaxonomyorder'  ); ?></small><img src="<?php bloginfo('url'); ?>/wp-includes/images/rss.png" alt="RSS" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e( 'Number to Display:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $number; ?>" name="<?php echo $this->get_field_name('number'); ?>" id="<?php echo $this->get_field_id('number'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Max number of taxonomies to display', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('depth'); ?>"><?php _e( 'Depth:', 'mytaxonomyorder' ); ?></label> <input type="text" value="<?php echo $depth; ?>" name="<?php echo $this->get_field_name('depth'); ?>" id="<?php echo $this->get_field_id('depth'); ?>" class="widefat" />
			<br />
			<small><?php _e( '0 = All, -1 = Flat, 1 = Top Level Only, n = display n levels', 'mytaxonomyorder'  ); ?></small>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_dropdown'], true) ?> id="<?php echo $this->get_field_id('show_dropdown'); ?>" name="<?php echo $this->get_field_name('show_dropdown'); ?>" />
			<label for="<?php echo $this->get_field_id('show_dropdown'); ?>"><?php _e('Show As Dropdown', 'mytaxonomyorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_last_updated'], true) ?> id="<?php echo $this->get_field_id('show_last_updated'); ?>" name="<?php echo $this->get_field_name('show_last_updated'); ?>" />
			<label for="<?php echo $this->get_field_id('show_last_updated'); ?>"><?php _e('Show Last Updated', 'mytaxonomyorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_count'], true) ?> id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>" />
			<label for="<?php echo $this->get_field_id('show_count'); ?>"><?php _e('Show Count', 'mytaxonomyorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hide_empty'], true) ?> id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>" />
			<label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e('Hide Empty', 'mytaxonomyorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_desc_for_title'], true) ?> id="<?php echo $this->get_field_id('use_desc_for_title'); ?>" name="<?php echo $this->get_field_name('use_desc_for_title'); ?>" />
			<label for="<?php echo $this->get_field_id('use_desc_for_title'); ?>"><?php _e('Use Desc as Title', 'mytaxonomyorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hierarchical'], true) ?> id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>" />
			<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e('Show Hierarchical', 'mytaxonomyorder'); ?></label><br />
		</p>
<?php
	}
}
?>