<?php
/*
Plugin Name: Simple Terms Order
Plugin URI: http://www.beapi.fr
Description: A admin for order terms for each taxonomies
Author: Be API
Author URI: http://beapi.fr
Version: 1.4
*/

// Register the plugin path
define( 'STO_URL', plugins_url('/', __FILE__) );
define( 'STO_DIR', dirname(__FILE__) );

// Include the widget part
require_once(STO_DIR.'/inc/class.base.php');
require_once(STO_DIR.'/inc/class.walker.php');
require_once(STO_DIR.'/inc/class.widget.php');
require_once(STO_DIR.'/inc/class.client.php');

if ( is_admin() )
	require_once(STO_DIR.'/inc/class.admin.php');

// At the activation of the plugin add the column
register_activation_hook( __FILE__, array( 'SimpleTermsOrder_Base', 'activate' ) );

/**
 * Instanciate class and load language
 * 
 * @access public
 * @return void
 */
add_action( 'plugins_loaded', 'initSimpleTermsOrder' );
function initSimpleTermsOrder() {
	global $simple_terms_order;
	
	// Load translations
	load_plugin_textdomain ( 'simpletermorder', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	$simple_terms_order['client'] = new SimpleTermsOrder_Client();
	if ( is_admin() ) {
		$simple_terms_order['admin'] = new SimpleTermsOrder_Admin();
	}
	
	// Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("simpletermorder_Widget");') );
}
?>