<?php

add_action( 'init', 'reserva_wp_objects' );
add_action( 'save_post', 'reserva_wp_save_transaction' );

function reserva_wp_objects() {

	// Register default post_types
	// Transactions are "transparent" registers of relations between users and objects. 
	// Each of them registers one relation (one reservation) 
	// they hold the special statuses to be used 
	// and also the special conditions for each transaction
	register_post_type( 'rwp_transaction', 
		array( 'public' => true, 
				'label' => __('Transações', 'reservawp'), 
				'singular_label' => __('Transação', 'reservawp'), 
				'supports' => array('title'),
				'register_meta_box_cb' => 'reserva_wp_transaction_metaboxes'
		) 
	);

	/// Get custom objects
	$types = get_option( 'reserva_wp_objects' );

	if($types) :
		
		foreach ($types as $object) {
			register_post_type( $object['rwp_name'], 
				array( 'public' => true, 
						'description' => esc_html($object['rwp_description']),
						'label' => $object['rwp_objlabel'], 
						'singular_label' => $object['rwp_singlabel']
				) 
			);
		}

	else :
		
		$types = $defaults;
		register_post_type( 'reservawp', 
			array( 
				'public' => true,
				'description' => __( 'Objetos de exemplo do plugin Reserva WP' ),
				'hierarchical' => false,
				'menu_position' => 5,
				// 'menu_icon' =>
				'labels' => array( 
					'name' => 'Reserva WP Objects' 
					) 
				) 
			);

	endif;

	
}

function reserva_wp_transaction_metaboxes($post) {
	add_meta_box( 'rwp_transaction', __('Detalhes da Transação', 'reservawp'), 'reserva_wp_transaction_metaboxes_render', 'rwp_transaction' );
}

function reserva_wp_transaction_metaboxes_render($post) {

	$global_transaction_statuses = get_option( 'reserva_wp_transaction_statuses' );
	$transaction_status = get_post_meta( $post->ID, 'rwp_transaction_status', true );

	echo '<label for="rwp_transaction_status">'.__('Status da transação', 'reservawp').'</label>
			<select id="rwp_transaction_status" name="rwp_transaction_status">';

	foreach ($global_transaction_statuses as $s) {
		$check = '';
		if($s == $transaction_status)
			$check = 'selected="selected"';

		echo '<option value="'.$s.'" '.$check.'>'.$s.'</option>';
	}

	echo '</select>';

	wp_nonce_field( 'rwp_update_transaction', 'rwp_nonce_' );
}

function reserva_wp_save_transaction($post_id) {

	if( 'rwp_transaction' != $_POST['post_type'] )
		return;

	if ( wp_is_post_revision( $post_id ) )
		return;

	if( !empty($_POST) && check_admin_referer( 'rwp_update_transaction', 'rwp_nonce_' ) )
		update_post_meta($post_id, 'rwp_transaction_status', $_POST['rwp_transaction_status']);
}
?>