<?php
class SimpleTermsOrder_Client {
	function SimpleTermsOrder_Client() {
		add_filter( 'get_terms_orderby', array(&$this, 'orderQuery'), 10, 2 );
	}
	
	/**
	 * Add custom order to the filter
	 *
	 * @param string $orderby 
	 * @param array $args 
	 * @return string
	 * @author Amaury Balmer
	 */
	function orderQuery( $orderby, $args ) {
		if( isset($args['orderby']) && $args['orderby'] == 'order' )
			return 'tt.term_order';
		else
			return $orderby;
	}
}
?>