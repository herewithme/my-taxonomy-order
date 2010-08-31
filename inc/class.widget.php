<?php
class Taxonomy_Order_Walker extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'category';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * @see Walker::start_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of category. Used for tab indentation.
	 * @param array $args Will only append content if style argument value is 'list'.
	 */
	function start_lvl(&$output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * @see Walker::end_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of category. Used for tab indentation.
	 * @param array $args Will only append content if style argument value is 'list'.
	 */
	function end_lvl(&$output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int $depth Depth of category in reference to parents.
	 * @param array $args
	 */
	function start_el(&$output, $category, $depth, $args) {
		extract($args);

		$cat_name = esc_attr( $category->name);
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );

		if ( 'list' == $args['style'] ) {
			$output .= "\t".'<li id="item_'.$category->term_id.'"><span>'.$cat_name.'</span>';
		} else {
			$output .= "\t$cat_name<br />\n";
		}
	}

	/**
	 * @see Walker::end_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Not used.
	 * @param int $depth Depth of category. Not used.
	 * @param array $args Only uses 'list' for whether should append to output.
	 */
	function end_el(&$output, $page, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$output .= "</li>\n";
	}

}

class simpletermorder_Widget extends WP_Widget {

	function simpletermorder_Widget() {		
		//Initialisation of the widget
		$widget_ops = array( 
			'classname' 	=> 'widget_simpletermorder', 
			'description' 	=> __( 'Enhanced Taxonomy widget provided by My Terms Order','simpletermorder' ) 
		);
		$this->WP_Widget( 'simpletermorder', __( 'My Terms Order', 'simpletermorder' ), $widget_ops );			
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
		$title_li 			= apply_filters('widget_title', empty( $instance['title_li'] ) ? __( 'Taxonomies','simpletermorder' ) : $instance['title_li']);
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
		if ( $show_dropdown ) :
			$cat_args['show_option_none'] = __( 'Select Term','simpletermorder' );
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

		<?php else : ?>
		<ul>
		<?php 
			//Remove the title li
			$cat_args['title_li'] = '';
			
			//Display the widget
			wp_list_categories( apply_filters( 'widget_categories_args', $cat_args ) );
		?>
		</ul>
		<?php
		endif;

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
			<label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e( 'Taxonomy:', 'simpletermorder' ); ?></label>
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
			<label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e( 'Order By:', 'simpletermorder' ); ?></label>
			<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat">
				<option value="order"<?php selected( $instance['orderby'], 'order' ); ?>><?php _e('My Order', 'simpletermorder'); ?></option>
				<option value="name"<?php selected( $instance['orderby'], 'name' ); ?>><?php _e('Name', 'simpletermorder'); ?></option>
				<option value="count"<?php selected( $instance['orderby'], 'count' ); ?>><?php _e( 'Count', 'simpletermorder' ); ?></option>
				<option value="ID"<?php selected( $instance['orderby'], 'ID' ); ?>><?php _e( 'ID', 'simpletermorder' ); ?></option>
				<option value="slug"<?php selected( $instance['orderby'], 'slug' ); ?>><?php _e( 'Slug', 'simpletermorder' ); ?></option>
				<option value="term_group"<?php selected( $instance['orderby'], 'term_group' ); ?>><?php _e( 'Term Group', 'simpletermorder' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e( 'Order:', 'simpletermorder' ); ?></label>
			<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('category_order'); ?>" class="widefat">
				<option value="asc"<?php selected( $instance['order'], 'asc' ); ?>><?php _e('Ascending', 'simpletermorder'); ?></option>
				<option value="desc"<?php selected( $instance['order'], 'desc' ); ?>><?php _e('Descending', 'simpletermorder'); ?></option>
			</select>
		</p>
				<p>
			<label for="<?php echo $this->get_field_id('title_li'); ?>"><?php _e( 'Title:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $title_li; ?>" name="<?php echo $this->get_field_name('title_li'); ?>" id="<?php echo $this->get_field_id('title_li'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Default to Taxonomies.', 'simpletermorder' ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $exclude; ?>" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude_tree'); ?>"><?php _e( 'Exclude Tree:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $exclude_tree; ?>" name="<?php echo $this->get_field_name('exclude_tree'); ?>" id="<?php echo $this->get_field_id('exclude_tree'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('include'); ?>"><?php _e( 'Include:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $include; ?>" name="<?php echo $this->get_field_name('include'); ?>" id="<?php echo $this->get_field_id('include'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Taxonomy IDs, separated by commas.', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('child_of'); ?>"><?php _e( 'Child Of:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $child_of; ?>" name="<?php echo $this->get_field_name('child_of'); ?>" id="<?php echo $this->get_field_id('child_of'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Only display children of this Taxonomy ID.', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('feed'); ?>"><?php _e( 'Feed Text:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $feed; ?>" name="<?php echo $this->get_field_name('feed'); ?>" id="<?php echo $this->get_field_id('feed'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Text for RSS Feed', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('feed_image'); ?>"><?php _e( 'Feed Image:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $feed_image; ?>" name="<?php echo $this->get_field_name('feed_image'); ?>" id="<?php echo $this->get_field_id('feed_image'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'URL to RSS Image, copy url of this image', 'simpletermorder'  ); ?></small><img src="<?php bloginfo('url'); ?>/wp-includes/images/rss.png" alt="RSS" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e( 'Number to Display:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $number; ?>" name="<?php echo $this->get_field_name('number'); ?>" id="<?php echo $this->get_field_id('number'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Max number of taxonomies to display', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('depth'); ?>"><?php _e( 'Depth:', 'simpletermorder' ); ?></label> <input type="text" value="<?php echo $depth; ?>" name="<?php echo $this->get_field_name('depth'); ?>" id="<?php echo $this->get_field_id('depth'); ?>" class="widefat" />
			<br />
			<small><?php _e( '0 = All, -1 = Flat, 1 = Top Level Only, n = display n levels', 'simpletermorder'  ); ?></small>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_dropdown'], true) ?> id="<?php echo $this->get_field_id('show_dropdown'); ?>" name="<?php echo $this->get_field_name('show_dropdown'); ?>" />
			<label for="<?php echo $this->get_field_id('show_dropdown'); ?>"><?php _e('Show As Dropdown', 'simpletermorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_last_updated'], true) ?> id="<?php echo $this->get_field_id('show_last_updated'); ?>" name="<?php echo $this->get_field_name('show_last_updated'); ?>" />
			<label for="<?php echo $this->get_field_id('show_last_updated'); ?>"><?php _e('Show Last Updated', 'simpletermorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_count'], true) ?> id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>" />
			<label for="<?php echo $this->get_field_id('show_count'); ?>"><?php _e('Show Count', 'simpletermorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hide_empty'], true) ?> id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>" />
			<label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e('Hide Empty', 'simpletermorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_desc_for_title'], true) ?> id="<?php echo $this->get_field_id('use_desc_for_title'); ?>" name="<?php echo $this->get_field_name('use_desc_for_title'); ?>" />
			<label for="<?php echo $this->get_field_id('use_desc_for_title'); ?>"><?php _e('Use Desc as Title', 'simpletermorder'); ?></label><br />
			<input class="checkbox" type="checkbox" <?php checked( (bool) $instance['hierarchical'], true) ?> id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>" />
			<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e('Show Hierarchical', 'simpletermorder'); ?></label><br />
		</p>
<?php
	}
}
?>