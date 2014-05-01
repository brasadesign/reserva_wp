<?php

add_action( 'init', 'reserva_wp_objects' );
add_action( 'save_post', 'reserva_wp_save_transaction' );
add_action( 'updated_post_meta', 'reserva_wp_altered_transaction_meta' );
add_action( 'add_meta_boxes', 'reserva_wp_listing_metabox');
// TODO: limpar hook abaixo pra funcionar de forma generica
add_action( 'save_post_listing', 'reserva_wp_create_transaction' );
add_action( 'rwp_status_changed', 'reserva_wp_email_status_changes' );
add_action( 'rwp_status_changed_to_liberado', 'reserva_wp_objeto_liberado' );

add_filter( 'manage_listing_posts_columns' , 'reserva_wp_modify_post_table_columns' );
add_action( 'manage_listing_posts_custom_column', 'reserva_wp_modify_post_table_row', 10, 2 );

function reserva_wp_modify_post_table_columns( $columns ) {
	return array_merge($columns, 
        array('transacoes' => __('Transações')));
}

function reserva_wp_modify_post_table_row($column, $post_id) {

	switch ($column) {
		case 'transacoes':
					$transactions = get_posts( array( 
										'post_type' => 'rwp_transaction',
										'posts_per_page' => 1,
										'meta_query' => array( 
											array( 
												'key' => 'rwp_transaction_object',
												'value' => $post_id
											),
											array( 
												'key' => 'rwp_transaction_user',
												'value' => get_current_user_id()
											) 
											) ) );
			if($transactions) {
				foreach ($transactions as $t) {
					echo '<a href="'.admin_url( 'post.php?post='.$t->ID.'&action=edit' ).'" >'.$t->post_title.'</a><br>';
				} 
			} else {
				echo 'Nenhuma transação encontrada';
			}
			break;

		default:
			// echo 'oi';
			break;
	}

	// return $column;

}

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
				'register_meta_box_cb' => 'reserva_wp_transaction_metaboxes',
				'capabilities' => array(
				    'edit_post'          => 'edit_pages',
				    'read_post'          => 'edit_pages',
				    'delete_post'        => 'edit_pages',
				    'edit_posts'         => 'edit_pages',
				    'edit_others_posts'  => 'edit_pages',
				    'publish_posts'      => 'edit_pages',
				    'read_private_posts' => 'edit_pages'
				),
		) 
	);

	/// Get custom objects
	$types = get_option( 'reserva_wp_objects' );

	if($types) :
		// Registra os tipos objetos criados pelo usuário
		foreach ($types as $object) {
			// Pula os tipos marcados
			if(isset($object['rwp_create_post_type']) && false == $object['rwp_create_post_type'] )
				continue;

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
		// $types = $defaults;
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

function reserva_wp_listing_metabox($post) {
	// Listing meta boxes
	add_meta_box( 'rwp_listing_booking', __('Agenda', 'reservawp'), 'reserva_wp_listing_calendar_render', 'listing', 'side' );

}

function reserva_wp_listing_calendar_render($post) {
	echo '<div id="bookingdatepicker"></div><div id="datepicker-inputs"></div>';
	?>

	<style type="text/css">
		#bookingdatepicker .ui-state-highlight {
			border: none;
		}
		#bookingdatepicker .ui-state-highlight a {
			background: #f00;
		}
	</style>
	<?php
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

	if( $post->post_status == 'auto-draft') {
		echo 'Os detalhes estarão disponíveis após a publicação';
	} else {
?>
	<table class="rwp_table rwp_metabox">
		<tr>
			<th><label for="rwp_transaction_id"><?php _e('ID da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_status"><?php _e('Status da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_user"><?php _e('Usuário da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_object"><?php _e('Objeto da transação', 'reservawp'); ?></label></th>
			<th><label for="rwp_transaction_object_published_until"><?php _e('Publicado até', 'reservawp'); ?></label></th>
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
		if($s['rwp_name'] == $transaction_status)
			$check = 'selected="selected"';

		echo '<option value="'.$s['rwp_name'].'" '.$check.'>'.$s['rwp_statuslabel'].'</option>';
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
	$transaction_objects = get_posts( array( 'post_type' => $post_types, 'numberposts' => -1, 'post_status' => 'any' ) );
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

	$restrict_date = array('solicitado','revisao','aguardando');
	if(in_array($transaction_status, $restrict_date))
		$disable = 'disabled="disabled"';

	$rwp_transaction_object_published_until = get_post_meta( $post->ID, 'rwp_transaction_object_published_until', true );
	$timestamp = '';
	if($rwp_transaction_object_published_until)
		$timestamp = date('d/m/Y', (int) $rwp_transaction_object_published_until);

	echo '<td><input '.$disable.' id="datepicker" name="rwp_transaction_object_published_until" value="'.$timestamp.'" /></td>';
	
?>

		</tr>
	</table>
	<script>
	  jQuery(function() {
	    jQuery( "#datepicker" ).datepicker({ dateFormat: "dd/mm/yy" });
	  });
	  </script>
<?php
	
	wp_nonce_field( 'rwp_update_transaction', 'rwp_nonce_' );
	}
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

function reserva_wp_save_transaction($transaction_id) {

	if( 'rwp_transaction' != $_POST['post_type'] )
		return;

	if ( wp_is_post_revision( $transaction_id ) )
		return;

	if( !empty($_POST) && check_admin_referer( 'rwp_update_transaction', 'rwp_nonce_' ) ) {

		$status = update_post_meta($transaction_id, 'rwp_transaction_status', $_POST['rwp_transaction_status']);

		update_post_meta($transaction_id, 'rwp_transaction_user', $_POST['rwp_transaction_user']);
		update_post_meta($transaction_id, 'rwp_transaction_object', $_POST['rwp_transaction_object']);

		$valid = explode('/', esc_attr( $_POST['rwp_transaction_object_published_until'] ) );
		$valid = strtotime($valid[2].'/'.$valid[1].'/'.$valid[0]);
		update_post_meta($transaction_id, 'rwp_transaction_object_published_until', $valid);
			
	}
		
}

/*
* Distribui as funções e hooks especificos de cada alteração de meta dados da transação
*/ 
function reserva_wp_altered_transaction_meta($meta_id) {

	$meta = get_metadata_by_mid( 'post', $meta_id );
	$transaction = get_post( $meta->post_id );

	// mudança de status
	if( "rwp_transaction_status" == $meta->meta_key && 'rwp_transaction' == $transaction->post_type )
		reserva_wp_status_change($meta->post_id, $meta->meta_value);
	
	// TODO: mudança de usuário
	// if( "rwp_transaction_user" == $meta->meta_key)
		// reserva_wp_status_change($meta->post_id, $meta->meta_key, $meta->meta_value);

	// TODO: mudança de objeto
	// if( "rwp_transaction_object" == $meta->meta_key)
		// reserva_wp_status_change($meta->post_id, $meta->meta_key, $meta->meta_value);	


}

function reserva_wp_status_change($transaction_id, $newstatus) {

	$statuses = get_option( 'reserva_wp_transaction_statuses' );
	$object_id = get_post_meta( $transaction_id, 'rwp_transaction_object', true );
	$keys = array_keys($statuses);

	if(in_array($newstatus, $keys)) {

		// Update the object status to reflect changes in transaction
		$p = wp_update_post( array( 'ID' => $object_id, 'post_status' => $statuses[$newstatus]['rwp_statusref'] ), true );

		if( !is_wp_error( $p ) ) {
			// Action hook for all status changes
			// TODO: test!
			// wp_die(dump(array($newstatus, $transaction_id, $object_id)));
			do_action( 'rwp_status_changed', array($newstatus, $transaction_id, $object_id) );
			// Action hook for specific status changes
			// takes the form of rwp_status_changed_to_{status_name}
			do_action( 'rwp_status_changed_to_'.$newstatus, array($transaction_id, $object_id) );
		}
		

	}

}

/**
* Cria automaticamente a transação quando o usuário cria a listing
* TODO: anotados
*/
function reserva_wp_create_transaction($post_id) {

	global $current_user;
	$user = get_current_user_id();

    // Não é autosave
     if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
          return;	

	// TODO: ampliar p/ fora do ecotemporadas (inclusive o hook em add_action)
	// Somente este post-type
	if ( 'listing' != $_POST['post_type'] )
		return;

	// Nunca este post-type
	if ( 'rwp_transaction' == $post->post_type )
		return;

	// Não é revision
	if ( wp_is_post_revision( $post_id ) )
		return;

	// Não é tela vazia
	if ( empty($_POST) )
		return;

	$transaction = array(
		'post_title' => $post_id.'-'.$user.'-'.time(),
		'post_status' => 'draft',
		'post_type'	=> 'rwp_transaction'
	);

	$tid = wp_insert_post( $transaction, true );


	if(!is_wp_error( $tid )) {

		// TODO: dinamizar o status inicial
		update_post_meta( $tid, 'rwp_transaction_status', 'solicitado' );
		update_post_meta( $tid, 'rwp_transaction_user', $user );
		update_post_meta( $tid, 'rwp_transaction_object', $post_id );
	}
	
}

/*
* Envia emails genéricos avisando das mudanças de status
* Roda sempre que uma transação muda de status
*/
function reserva_wp_email_status_changes($status) {

	$statuses = get_option( 'reserva_wp_transaction_statuses' );

	$transaction = get_post( $status[1] );
	$object = get_post( $status[2] );
	$user = get_post_meta( $transaction->ID, 'rwp_transaction_user', true );
	$u = get_userdata( $user );

	// Email message
	$subject = get_option( 'blogname' ) . ' :: ' . __('Mudança de status do anúncio ') . '"' . $transaction->post_title . '"';

	$message = __("Olá {$u->display_name}\n\n");
	$message .= __("Seu anúncio {$object->post_title} mudou de status para: \n\n") . $statuses[$status[0]]['rwp_statuslabel'];

	wp_mail( $u->user_email, $subject, $message, $headers, $attachments );

}

/*
* Estipula o prazo de publicação do objeto a partir da liberação
* Roda sempre que o objeto passa para o status "liberado"
*/
function reserva_wp_objeto_liberado($transaction) {

	$due = get_post_meta( $transaction[0], 'rwp_transaction_object_published_until', true );

	if(!$due) {
		$due = time()+60*24*60*60; // 60 dias	
		$u = update_post_meta( $transaction[0], 'rwp_transaction_object_published_until', $due );
	}

	

	
}

?>
