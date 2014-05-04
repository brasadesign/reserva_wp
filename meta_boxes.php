<?php
ini_set('error_reporting', E_ALL);

require_once dirname( __FILE__ ) . '/cron.php';

reserva_wp_cron_check_pagamentos();

// add_action( 'updated_post_meta', 'reserva_wp_transaction_status_update_changes' );

function reserva_wp_transaction_status_update_changes($meta_id, $object_id, $meta_key, $meta_value) {

	//if('rwp_transaction_status' == $meta_key) {
		wp_die( dump($meta_key.' '.$meta_value) );	
	// }

	

}