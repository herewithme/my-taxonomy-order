<?php
class SimpleTermsOrder_Base {
	function SimpleTermsOrder_Base() {
	}
	
	/**
 	* Create the column for the plugin if needed
 	* 
 	* @return void
 	* @author Nicolas Juen
 	*/
	function activate() {
		global $wpdb;
		
		// Test if the "term_order" field already exists or not
		$query = $wpdb->query( "SHOW COLUMNS FROM $wpdb->term_taxonomy LIKE 'term_order'" );
	
		// Add the column if needed
		if ( $query == 0 )
			$wpdb->query( "ALTER TABLE $wpdb->term_taxonomy ADD `term_order` INT(4) NULL DEFAULT '0'" );
	}
}
?>