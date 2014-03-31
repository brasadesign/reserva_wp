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
		// Registra os tipos objetos criados pelo usuário
		foreach ($types as $object) {
			register_post_type( $object['rwp_name'], 
				array( 'public' => true, 
						'description' => esc_html($object['rwp_description']),
						'label' => $object['rwp_objlabel'], 
						'singular_label' => $object['rwp_singlabel'],
						'register_meta_box_cb' => 'reserva_wp_transaction_metaboxes'
				) 
			);
		}

	else :
		// Se nenhum objeto foi criado ainda, exiba o objeto de teste
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
	// Transaction meta boxes
	add_meta_box( 'rwp_transaction', __('Detalhes da Transação', 'reservawp'), 'reserva_wp_transaction_metaboxes_render', 'rwp_transaction' );

	// General meta boxes
	$global_transaction_objects = get_option( 'reserva_wp_objects' );

	foreach($global_transaction_objects as $key => $value) {
		add_meta_box( 'rwp_transactions', __('Transações', 'reservawp'), 'reserva_wp_transaction_metaboxes_render_readonly', $key );
	}

}

function reserva_wp_transaction_metaboxes_render($post) {
?>
	<table class="rwp_table rwp_metabox">
		<tr>
			<th><label for="rwp_transaction_id"><?php _e('ID da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_status"><?php _e('Status da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_user"><?php _e('Usuário da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_object"><?php _e('Objeto da transação', 'reservawp'); ?></label></th>
		</tr>
		<tr>
			<td><?php echo $post->ID; ?></td>

<?php
	// Selecionar status da transação
	$global_transaction_statuses = get_option( 'reserva_wp_transaction_statuses' );
	$transaction_status = get_post_meta( $post->ID, 'rwp_transaction_status', true );

	echo '<td>
			<select id="rwp_transaction_status" name="rwp_transaction_status">';

	foreach ($global_transaction_statuses as $s) {
		$check = '';
		if($s == $transaction_status)
			$check = 'selected="selected"';

		echo '<option value="'.$s.'" '.$check.'>'.$s.'</option>';
	}

	echo '</select></td>';

	// Selecionar usuário da transação
	$global_transaction_users = get_users();
	$transaction_user = get_post_meta( $post->ID, 'rwp_transaction_user', true );
	
	echo '<td>
			<select id="rwp_transaction_user" name="rwp_transaction_user">';

	foreach ($global_transaction_users as $u) {
		$check = '';
		if($u->ID == $transaction_user)
			$check = 'selected="selected"';

		echo '<option value="'.$u->ID.'" '.$check.'>'.$u->user_email.'</option>';
	}

	echo '</select></td>';

	// Selecionar objeto da transação
	// Busca todos os tipos de objetos de todos os tipos
	// TODO: melhorar o filtro
	$global_transaction_objects = get_option( 'reserva_wp_objects' );
	$post_types = array_keys($global_transaction_objects);
	$transaction_objects = get_posts( array( 'post_type' => $post_types, 'numberposts' => -1) );
	$transaction_object = get_post_meta( $post->ID, 'rwp_transaction_object', true );
	
	echo '<td>
			<select id="rwp_transaction_object" name="rwp_transaction_object">';

	foreach ($transaction_objects as $o) {

		$check = '';
		if($o->ID == $transaction_object)
			$check = 'selected="selected"';

		echo '<option value="'.$o->ID.'" '.$check.'>'.$o->post_title.'</option>';
	}

	echo '</select></td>';	

?>
		</tr>
	</table>
<?php

	wp_nonce_field( 'rwp_update_transaction', 'rwp_nonce_' );
}

function reserva_wp_transaction_metaboxes_render_readonly($post) {

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 'meta_key' => 'rwp_transaction_object', 'meta_value' => $post->ID ) );

?>
	<table class="rwp_table rwp_metabox">
		<tr>
			<th><?php _e('Transação', 'reservawp'); ?></th>
			<th><?php _e('Usuário', 'reservawp'); ?></th>
			<th><?php _e('Data', 'reservawp'); ?></th>
			<th><?php _e('Status', 'reservawp'); ?></th>
		</tr>		
<?php

	foreach($transactions as $t) {
		$user = get_post_meta($t->ID, 'rwp_transaction_user', true);

		echo 	'<tr>
					<td><a href="'.admin_url( 'post.php?action=edit&post='.$t->ID ).'">'.$t->ID.'</a></td>
					<td><a href="'.admin_url( 'user-edit.php?user_id='.$user ).'">'.$user.'</td>
					<td>'.get_the_time( 'd/m/Y', $t->ID ).'</td>
					<td>'.get_post_meta($t->ID, 'rwp_transaction_status', true).'</td>
				</tr>';

	}

	echo '</table>';
}

function reserva_wp_save_transaction($post_id) {

	if( 'rwp_transaction' != $_POST['post_type'] )
		return;

	if ( wp_is_post_revision( $post_id ) )
		return;

	if( !empty($_POST) && check_admin_referer( 'rwp_update_transaction', 'rwp_nonce_' ) ) {
		update_post_meta($post_id, 'rwp_transaction_status', $_POST['rwp_transaction_status']);
		update_post_meta($post_id, 'rwp_transaction_user', $_POST['rwp_transaction_user']);
		update_post_meta($post_id, 'rwp_transaction_object', $_POST['rwp_transaction_object']);
	}
		
}
?>