<?php
// Register Custom Status
function custom_post_status() {

	$args = array(
		'label'                     => _x( 'removido', 'Status General Name', 'reservawp' ),
		'label_count'               => _n_noop( 'Removido (%s)',  'Removido (%s)', 'reservawp' ),
		'public'                    => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => false,
		'exclude_from_search'       => true,
	);
	register_post_status( 'removido', $args );

}

// Hook into the 'init' action
//add_action( 'init', 'custom_post_status', 0 );

function reserva_wp_front_scripts(){
	wp_register_script( 'rwp_admin', plugins_url( '/js/admin.js?'.mt_rand(), __FILE__ ), array('jquery') );
	wp_register_script( 'rwp_date_front', plugins_url( '/js/date-front.js?'.mt_rand(), __FILE__ ), array('jquery') );

	wp_register_script( 'rwp_validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ), array('jquery') );
	wp_register_script( 'rwp_datepicker-ptBR', plugins_url( '/js/jquery.ui.datepicker-pt-BR.js', __FILE__ ), array('jquery') );
	wp_register_script( 'jquery.multidatespicker', plugins_url( '/js/jquery-ui.multidatespicker.js?', __FILE__ ), array('jquery') );

	wp_register_style( 'jquery-ui-theme', '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css' );
	wp_localize_script( 'jquery', 'reserva_wp' ,array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'rwp_datepicker-ptBR' );
	wp_enqueue_script( 'rwp_validation' );
	wp_enqueue_script( 'jquery.multidatespicker' );
	wp_enqueue_script( 'rwp_admin' );
	//wp_enqueue_script( 'rwp_date_front' );

	wp_enqueue_style( 'jquery-ui-theme' );
}
if(is_admin()) {
	add_action( 'wp_enqueue_scripts', 'reserva_wp_admin_scripts' );	
}
else{
	add_action( 'wp_enqueue_scripts', 'reserva_wp_front_scripts' );
}


add_action( 'init', 'reserva_wp_objects' );
add_action( 'save_post', 'reserva_wp_save_transaction' );
add_action( 'save_post', 'reserva_wp_save_expire' );
add_action( 'updated_post_meta', 'reserva_wp_altered_transaction_meta' );
add_action( 'add_meta_boxes', 'reserva_wp_listing_metabox');
// TODO: limpar hook abaixo pra funcionar de forma generica
add_action( 'save_post_listing', 'reserva_wp_update_object_dates' );
add_action( 'save_post_listing', 'reserva_wp_create_transaction' );
//add_action( 'rwp_status_changed', 'reserva_wp_email_status_changes' );
//add_action( 'rwp_status_changed_to_liberado', 'reserva_wp_objeto_liberado' );

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
										'post_status' => 'any',
										'posts_per_page' => 1,
										'meta_query' => array( 
											'relation' => 'AND',
											array( 
												'key' => 'rwp_transaction_object',
												'value' => $post_id
											),
											array( 
												'key' => 'rwp_transaction_user',
												'value' => get_current_user_id()
											),
											array( 
												'key' => 'rwp_transaction_status',
												'value' => array('aguardando','solicitado','revisao')
											), 
											) ) );
			if($transactions) {
				foreach ($transactions as $t) {
					// echo '<a href="'.admin_url( 'post.php?post='.$t->ID.'&action=edit' ).'" >'.$t->post_title.'</a><br>';
					echo 'ID '.$post_id.'-'.$t->ID;
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
		// Registra os tipos objetos criados pelo usuárioe
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
	if(current_user_can('edit_others_pages')){
		add_meta_box( 'rwp_listing_expirebox', __('Data de expiração', 'reservawp'), 'reserva_wp_listing_expirebox_render', 'listing', 'side', 'high', array(false) );
	}
	add_meta_box( 'rwp_listing_booking', __('Agenda', 'reservawp'), 'reserva_wp_listing_calendar_render', 'listing', 'side', 'core', array(false) );
	// add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );

}

function reserva_wp_listing_expirebox_render($post){
	$object_id = get_post_meta( $post->ID, 'rwp_transaction_id', true );
	echo '<label>Data de expiração:</label><br><br>';
	echo '<input type="text" name="rwp_transaction_exp" value="'.get_post_meta($object_id,'rwp_transaction_expire_date',true).'">';
	echo '<br><br><label>Data de pre-expiração:</label><br><br>';
	echo '<input type="text" name="rwp_transaction_pre_exp" value="'.get_post_meta($object_id,'rwp_transaction_pre_expire_date',true).'">';
	//echo '--  '.$object_id;
}
function reserva_wp_save_expire($post_id){
	//global $wpdb;
	$object_id = get_post_meta( $post_id, 'rwp_transaction_id', true );
	//var_dump($_POST['rwp_transaction_exp']);
	//die();
	if(isset($_POST['rwp_transaction_exp'])){
		//$wpdb->update( $wpdb->postmeta, array('meta_value' => $_POST['rwp_transaction_exp']), array('post_id' => $object_id));
		//die($_POST['rwp_transaction_exp']);
		//die();
		update_post_meta($object_id, 'rwp_transaction_expire_date', $_POST['rwp_transaction_exp']);
	}
	if(isset($_POST['rwp_transaction_pre_exp'])){
		update_post_meta($object_id, 'rwp_transaction_pre_expire_date', $_POST['rwp_transaction_pre_exp']);
	}
}
if(is_singular('listing')) {
	global $post;
	add_action('dynamic_sidebar_before', reserva_wp_listing_calendar_render($post));
}
function return_json($var){
	//$var = json_decode($var,true);
	//echo var_dump($var);
	if(empty($var)){
		return array('Wed Sep 17 3099');
	}
	else{
		return $var;
	}
}
function reserva_wp_listing_calendar_render($post) {
	
	
	//if(!is_admin()) {
	//	$cls = 'front';
	//}
		
	$rwp_dates_types = get_post_meta($post->ID, 'rwp_dates_types', true);
	$ind = array_keys($rwp_dates_types, 'ind');
	$oft = array_keys($rwp_dates_types, 'oft');
	$labels = array();

	for($i=0;$i<count($ind);$i++) {
		$labels[strtotime($ind[$i])] = '<label for="date-type-'.$ind[$i].'" id="date-'.$ind[$i].'">'.$ind[$i].': <input type="radio" checked="" value="ind" name="rwp_date_type['.$ind[$i].']">Indisponível <input type="radio" value="oft" name="rwp_date_type['.$ind[$i].']">Oferta <input type="button" value="x" rel="date-'.$ind[$i].'" /><br></label>';
		$indisponiveis[$i] = date('D M d Y', strtotime($ind[$i]));
		$ind[$i] = $ind[$i];		
	}
	for($i=0;$i<count($oft);$i++) {
		$labels[strtotime($oft[$i])] = '<label for="date-type-'.$oft[$i].'" id="date-'.$oft[$i].'">'.$oft[$i].': <input type="radio" value="ind" name="rwp_date_type['.$oft[$i].']">Indisponível <input type="radio" checked="" value="oft" name="rwp_date_type['.$oft[$i].']">Oferta <input type="button" value="x" rel="date-'.$oft[$i].'" /><br></label>';
		$ofertas[$i] = date('D M d Y', strtotime($oft[$i]));
		$oft[$i] = $oft[$i];
	}

	ksort($labels, SORT_NUMERIC);

	$addDates = array_merge($ind,$oft);

	if(!$rwp_dates_types) {
		$indisponiveis = array();
		$ofertas = array();
		$ind = array();
		$oft = array();
		$addDates = array();

	}

	echo '<script type="text/javascript">
			/* <![CDATA[ */
				var indisponiveis = '.json_encode($indisponiveis).';
				var ofertas  = '.json_encode($ofertas).';
				var indDates = '.json_encode($ind).';
				var oftDates = '.json_encode($oft).';
				var addDates = '.json_encode($addDates).';
				 /* > */
			</script>';
	echo '<div id="bookingdatepicker" class="'.$cls.'"></div>';
	
	//if(is_admin()) {
		echo '<div id="datepicker-inputs">'.join("\n",$labels).'</div>';
	//}
		
	?>

	<style type="text/css">
		#bookingdatepicker .ui-state-highlight {
			border: none;
		}
		#bookingdatepicker .ui-state-default a, #bookingdatepicker .ui-state-default span {
			background: #080;
			color: #fff;
		}
		#bookingdatepicker .ui-state-highlight a, #bookingdatepicker .ui-state-highlight span {
			background: #f00;
			color: #fff;
		}
		#bookingdatepicker .ui-state-highlight.oferta a, #bookingdatepicker .ui-state-highlight.oferta span {
			background: #ff0;
			color: #444;
		}
		.ui-state-disabled, .ui-widget-content .ui-state-disabled, .ui-widget-header .ui-state-disabled {
			opacity: 1;
		}
	</style>
	<?php
}
function reserva_wp_listing_calendar_render_front($post) {


	//$cls = 'front';

	$rwp_dates_types = get_post_meta($post->ID, 'rwp_dates_types', true);
	$ind = array_keys($rwp_dates_types, 'ind');
	$oft = array_keys($rwp_dates_types, 'oft');
	$labels = array();

	for($i=0;$i<count($ind);$i++) {
		$labels[strtotime($ind[$i])] = '<label for="date-type-'.$ind[$i].'" id="date-'.$ind[$i].'">'.$ind[$i].': <input type="radio" checked="" value="ind" name="rwp_date_type['.$ind[$i].']">Indisponível <input type="radio" value="oft" name="rwp_date_type['.$ind[$i].']">Oferta <input type="button" value="x" rel="date-'.$ind[$i].'" /><br></label>';
		$indisponiveis[$i] = date('D M d Y', strtotime($ind[$i]));
		$ind[$i] = $ind[$i];
	}
	for($i=0;$i<count($oft);$i++) {
		$labels[strtotime($oft[$i])] = '<label for="date-type-'.$oft[$i].'" id="date-'.$oft[$i].'">'.$oft[$i].': <input type="radio" value="ind" name="rwp_date_type['.$oft[$i].']">Indisponível <input type="radio" checked="" value="oft" name="rwp_date_type['.$oft[$i].']">Oferta <input type="button" value="x" rel="date-'.$oft[$i].'" /><br></label>';
		$ofertas[$i] = date('D M d Y', strtotime($oft[$i]));
		$oft[$i] = $oft[$i];
	}

	ksort($labels, SORT_NUMERIC);

	$addDates = array_merge($ind,$oft);

	if(!$rwp_dates_types) {
		$indisponiveis = array();
		$ofertas = array();
		$ind = array();
		$oft = array();
		$addDates = array();

	}

	echo '<script type="text/javascript">
			/* <![CDATA[ */
				indisponiveis = '.json_encode(return_json($indisponiveis)).';
				ofertas  = '.json_encode(return_json($ofertas)).';
				indDates = '.json_encode(return_json($ind)).';
				oftDates = '.json_encode(return_json($oft)).';
				addDates = '.json_encode(return_json($addDates)).';
				 /* > */
			</script>';
	echo '<div id="bookingdatepicker" data-front="true" class="front"></div>';

	//if(is_admin()) {
	//echo '<div id="datepicker-inputs">'.join("\n",$labels).'</div>';
	//}

	?>

	<style type="text/css">
		#bookingdatepicker .ui-state-highlight {
			border: none;
		}
		#bookingdatepicker td{
			pointer-events: none;
		}
		#bookingdatepicker .ui-state-default a, #bookingdatepicker .ui-state-default span {
			background: #080;
			color: #fff;
			pointer-events: none;
		}
		#bookingdatepicker .ui-state-highlight a, #bookingdatepicker .ui-state-highlight span {
			background: #f00;
			color: #fff;
		}
		#bookingdatepicker .ui-state-highlight.oferta a, #bookingdatepicker .ui-state-highlight.oferta span {
			background: #ff0;
			color: #444;
		}
		.ui-state-disabled, .ui-widget-content .ui-state-disabled, .ui-widget-header .ui-state-disabled {
			opacity: 1;
		}
		#bookingdatepicker .ui-state-disabled{
			opacity:0.40;
		}
		#bookingdatepicker .ui-datepicker-unselectable.ui-state-disabled.ui-state-highlight, #bookingdatepicker .ui-datepicker-unselectable .ui-state-disabled{
			opacity:0.99;
		}
		#bookingdatepicker .ui-datepicker-unselectable.ui-state-disabled.ui-state-highlight span, #bookingdatepicker .ui-datepicker-unselectable .ui-state-disabled span{
			opacity:0.99;
			background: #ff0;
			color: #444;
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
			wp_die('status_changed');
		} else {
			wp_die(var_dump($p));
		}
		

	} else {
		wp_die(var_dump($newstatus));
	}

}


/**
* Cria / Atualiza as datas de disponibilidade dos objetos
* TODO: anotados
*/
function reserva_wp_update_object_dates($post_id) {

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


	if(isset($_POST['rwp_date_type'])){
		//echo var_dump($_POST['rwp_date_type']);
		update_post_meta( $post_id, 'rwp_dates_types', $_POST['rwp_date_type'] );
		//DIE();
	}
	if(empty($_POST['rwp_date_type'])){
		//echo var_dump($_POST['rwp_date_type']);
		delete_post_meta( $post_id, 'rwp_dates_types');
		//DIE();
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
	$tr_id_meta = get_post_meta($post_id,'rwp_transaction_id',true);
	if (!empty($tr_id_meta))
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
		update_post_meta( $post_id, 'rwp_transaction_id', $tid );


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

	$plano = get_post_meta( $transaction[0], 'rwp_transaction_plan', true );
	// $due = get_post_meta( $transaction[0], 'rwp_transaction_object_published_until', true );
	
	switch ($plano) {
		case 'pgtotrimestral':
			$due = time()+90*24*60*60; // 90 dias	
			break;
		case 'pgtosemestral':
			$due = time()+180*24*60*60; // 180 dias	
			break;
		case 'pgtoanual':
			$due = time()+365*24*60*60; // 365 dias	
			break;			
		
		default:
			$due = time()+90*24*60*60; // 90 dias	
			break;
	}

	$u = update_post_meta( $transaction[0], 'rwp_transaction_object_published_until', $due );
	/*
	if(!$due) {
		$due = time()+60*24*60*60; // 60 dias	
		$u = update_post_meta( $transaction[0], 'rwp_transaction_object_published_until', $due );
		*/
	}
?>
