<?php

add_action( 'admin_menu', 'reserva_wp_settings' );
add_action( 'admin_enqueue_scripts', 'reserva_wp_admin_scripts' );
add_action( 'wp_ajax_reserva_wp_edit_object', 'reserva_wp_edit_object' );
add_action( 'wp_ajax_reserva_wp_edit_status', 'reserva_wp_edit_status' );

/**
* Scripts de ativação
* TODO: 
*/
function reserva_wp_activate() {

	
	$reserva_wp_transaction_statuses = get_option( 'reserva_wp_transaction_statuses' );
	$reserva_wp_objects = get_option( 'reserva_wp_objects' );

	if(!$reserva_wp_transaction_statuses) {
		$default_transaction_statuses = array(
			'solicitado'	=> array( 'rwp_name' => 'solicitado',	'rwp_statuslabel' => 'Solicitado', 				'rwp_statusref'	=> 'draft',		'rwp_description' => 'Solicitado' ),
			'revisao' 		=> array( 'rwp_name' => 'revisao',		'rwp_statuslabel' => 'Em revisão', 				'rwp_statusref'	=> 'pending',		'rwp_description' => 'Em revisão' ),
			'aguardando'	=> array( 'rwp_name' => 'aguardando',	'rwp_statuslabel' => 'Aguardando Pagamento',	'rwp_statusref'	=> 'private',	'rwp_description' => 'Aguardando Pagamento' ),
			'liberado' 		=> array( 'rwp_name' => 'liberado',		'rwp_statuslabel' => 'Liberado',				'rwp_statusref'	=> 'publish',	'rwp_description' => 'Liberado' ),
			'expirando' 	=> array( 'rwp_name' => 'expirando',	'rwp_statuslabel' => 'Expirando',				'rwp_statusref'	=> 'publish',	'rwp_description' => 'Expirando' ),
			'retirado' 		=> array( 'rwp_name' => 'retirado',		'rwp_statuslabel' => 'Retirado',				'rwp_statusref'	=> 'private',	'rwp_description' => 'Retirado' )
		);

		update_option( 'reserva_wp_transaction_statuses', $default_transaction_statuses );
	}

	if(!$reserva_wp_objects) {
		$default_objects = array(
			'listing'	=> array( 'rwp_name' => 'listing', 'rwp_objlabel' => 'Listings', 'rwp_singlabel' => 'Listing', 'rwp_description' => 'Listing ecotemporadas', 'rwp_create_post_type' => false )
		);

		update_option( 'reserva_wp_objects', $default_objects );
	}	

	
}


/**
* Settings scripts and styles
* TODO: enqueue sem symlinks, mover tag script pro arquivo proprio
*/
function reserva_wp_admin_scripts() {

	wp_register_script( 'rwp_admin', plugins_url( '/js/admin.js?'.mt_rand(), __FILE__ ), array('jquery') );
	wp_register_script( 'rwp_validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ), array('jquery') );
	wp_register_script( 'rwp_datepicker-ptBR', plugins_url( '/js/jquery.ui.datepicker-pt-BR.js', __FILE__ ), array('jquery') );
	wp_register_script( 'jquery.multidatespicker', plugins_url( '/js/jquery-ui.multidatespicker.js?', __FILE__ ), array('jquery') );

	wp_register_style( 'jquery-ui-theme', '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css' );
	wp_localize_script( 'jquery', 'reserva_wp' ,array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'admin_print_scripts-post-new.php', 'listing_scripts', 11 );
add_action( 'admin_print_scripts-post.php', 'listing_scripts', 11 );

function listing_scripts(){
    global $post_type;
    if( 'listing' == $post_type ){
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        //wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'rwp_datepicker-ptBR' );
        wp_enqueue_script( 'rwp_validation' );
        wp_enqueue_script( 'jquery.multidatespicker' );
        wp_enqueue_script( 'rwp_admin' );

        wp_enqueue_style( 'jquery-ui-theme' );
    }
}
/**
* Settings option screens
*/
function reserva_wp_settings() { 
	// add_menu_page( 'Reserva WP', 'Reserva WP', 'edit_pages', 'reserva_wp', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Objects', 'Objects', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Object Taxonomies', 'Taxonomies', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Object Data', 'Meta Data', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Object Status', 'Status', 'edit_pages', 'reserva_wp_status', 'reserva_wp_status_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Transactions', 'Transactions', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Results', 'Results', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	// add_submenu_page( 'reserva_wp', 'Reserva WP Settings', 'Settings', 'edit_pages', 'reserva_wp_settings', 'reserva_wp_settings_page' );
}

/**
* Create / edit objects functions
* TODO: front-end validation
*/
function reserva_wp_edit_object($post) {

	// delete_option( 'reserva_wp_objects' );
	$types = get_option( 'reserva_wp_objects' );

	// If deleting
	if($_POST['ajax']) {
		
		unset( $types[$_POST['name']] );

		$bool = update_option( 'reserva_wp_objects', $types );

        header( "Content-Type: application/json" );
		echo json_encode($bool);
        exit;

	} elseif ( 

		// server side validation
		!empty( $post['rwp_name'] ) && isset( $post['rwp_name'] ) &&
		!empty( $post['rwp_objlabel'] ) && isset( $post['rwp_objlabel'] ) &&
		!empty( $post['rwp_singlabel'] ) && isset( $post['rwp_singlabel'] ) &&
		!empty( $post['rwp_description'] ) && isset( $post['rwp_description'] )
		) {

		unset($post['rwp_action']);
		unset($post['rwp_nonce_']);
		unset($post['_wp_http_referer']);

		

		if($post['rwp_action'] == 'create') {
			$types[$post['rwp_name']] = $post;
			update_option( 'reserva_wp_objects', $types );
		} else {
			// in case the name has changed we just wipe it out and replace with the new info
			if(!empty( $post['rwp_orig_name'] ) && isset( $post['rwp_orig_name'] ))
				unset( $types[$post['rwp_orig_name']] );

			$types[$post['rwp_name']] = $post;
			update_option( 'reserva_wp_objects', $types );
		}

		

		return true;

		} else {
			return new WP_Error('incomplete', __("Existem campos incompletos no formulário"));
		}
	
}

/**
* Create / edit objects function
* TODO: front-end validation
*/
function reserva_wp_edit_status($post) {

	$option = 'reserva_wp_transaction_statuses';
	
	// delete_option( $option );
	$types = get_option( $option );

	// If deleting
	if($_POST['ajax']) {
		
		unset( $types[$_POST['name']] );

		$bool = update_option( $option, $types );

        header( "Content-Type: application/json" );
		echo json_encode($bool);
        exit;

	} elseif ( 

		// server side validation
		!empty( $post['rwp_name'] ) && isset( $post['rwp_name'] ) &&
		!empty( $post['rwp_statuslabel'] ) && isset( $post['rwp_statuslabel'] ) &&
		!empty( $post['rwp_statusref'] ) && isset( $post['rwp_statusref'] ) &&
		!empty( $post['rwp_description'] ) && isset( $post['rwp_description'] )
		) {

		unset($post['rwp_action']);
		unset($post['rwp_nonce_']);
		unset($post['_wp_http_referer']);

		

		if($post['rwp_action'] == 'create') {
			$types[$post['rwp_name']] = $post;
			update_option( $option, $types );
		} else {
			// in case the name has changed we just wipe it out and replace with the new info
			if(!empty( $post['rwp_orig_name'] ) && isset( $post['rwp_orig_name'] ))
				unset( $types[$post['rwp_orig_name']] );

			$types[$post['rwp_name']] = $post;
			update_option( $option, $types );
		}

		

		return true;

		} else {
			return new WP_Error('incomplete', __("Existem campos incompletos no formulário"));
		}
	
}

/**
* Create / edit objects page
* TODO: client side validation
*/
function reserva_wp_settings_page() {

	if( !empty($_POST) && check_admin_referer( 'rwp_create_object', 'rwp_nonce_' ) )
		reserva_wp_edit_object($_POST);
	?>
	<h1><?php _e('Reserva WP', 'reservawp'); ?></h1>
	<h3><?php _e('Criar um novo tipo de objeto', 'reservawp'); ?></h3>
	<style type="text/css">
		.rwp_form label { display: block; }
		.rwp_form input { margin-left: 10px; }
	</style>

	<form action="" method="post" class="rwp_form">
		<fieldset class="main">
			<?php _e('Defina abaixo as características principais do objeto', 'reservawp'); ?>
			<label for="rwp_name"><?php _e('Nome do Objeto', 'reservawp'); ?><input type="text" name="rwp_name" id="rwp_name" /></label>
			<label for="rwp_objlabel"><?php _e('Título do Objeto (plural)', 'reservawp'); ?><input type="text" name="rwp_objlabel" id="rwp_objlabel" /></label>
			<label for="rwp_singlabel"><?php _e('Título do Objeto (singular)', 'reservawp'); ?><input type="text" name="rwp_singlabel" id="rwp_singlabel" /></label>
			<label for="rwp_description"><?php _e('Descrição do objeto', 'reservawp'); ?><textarea name="rwp_description" id="rwp_description"></textarea></label>
			<input type="hidden" id="rwp_orig_name" name="rwp_orig_name" value="" />
			<input type="hidden" id="rwp_thing" name="rwp_thing" value="object" />
		</fieldset>
			
		<input type="hidden" id="rwp_action" name="rwp_action" value="create" />
		<?php wp_nonce_field( 'rwp_create_object', 'rwp_nonce_' ); ?>
		<input type="submit" id="rwp_submit" class="button-primary" value="<?php _e('Criar objeto', 'reservawp'); ?>">
		<input style="display: none;" id="rwp_edit_cancel" type="button" class="button-primary" value="<?php _e('Cancelar edição', 'reservawp'); ?>" />
	</form>
<?php

	reserva_wp_list_objects();
}

/**
* Create / edit status page
* TODO: client side validation
*/
function reserva_wp_status_page() {

	if( !empty($_POST) && check_admin_referer( 'rwp_create_status', 'rwp_nonce_' ) )
		reserva_wp_edit_status($_POST);
	?>
	<h1><?php _e('Reserva WP', 'reservawp'); ?></h1>
	<h3><?php _e('Criar um novo tipo de status', 'reservawp'); ?></h3>
	<style type="text/css">
		.rwp_form label { display: block; }
		.rwp_form input { margin-left: 10px; }
	</style>

	<form action="" method="post" class="rwp_form">
		<fieldset class="main">
			<?php _e('Defina abaixo as características principais do objeto', 'reservawp'); ?>
			<label for="rwp_name"><?php _e('Nome do Status', 'reservawp'); ?><input type="text" name="rwp_name" id="rwp_name" /></label>
			<label for="rwp_statuslabel"><?php _e('Título do Status', 'reservawp'); ?><input type="text" name="rwp_statuslabel" id="rwp_statuslabel" /></label>
			<label for="rwp_statusref"><?php _e('Referência do Status', 'reservawp'); ?>
				<select name="rwp_statusref">
					<?php 
						$statuses = get_post_stati();
						foreach ($statuses as $key => $value) {
							echo '<option name="'.$key.'">'.$value.'</option>';
						}
					?>
				</select>
			</label>
			<label for="rwp_description"><?php _e('Descrição do Status', 'reservawp'); ?><textarea name="rwp_description" id="rwp_description"></textarea></label>
			<input type="hidden" id="rwp_orig_name" name="rwp_orig_name" value="" />
			<input type="hidden" id="rwp_thing" name="rwp_thing" value="status" />
		</fieldset>
			
		<input type="hidden" id="rwp_action" name="rwp_action" value="create" />
		<?php wp_nonce_field( 'rwp_create_status', 'rwp_nonce_' ); ?>
		<input type="submit" id="rwp_submit" class="button-primary" value="<?php _e('Criar status', 'reservawp'); ?>">
		<input style="display: none;" id="rwp_edit_cancel" type="button" class="button-primary" value="<?php _e('Cancelar edição', 'reservawp'); ?>" />
	</form>
<?php

	reserva_wp_list_statuses();
}

function reserva_wp_list_objects() {
	
	$types = get_option( 'reserva_wp_objects' ); 
	?>

	<hr>
	<h3><?php _e('Editar objetos', 'reservawp'); ?></h3>
	<table>
		<tr>
			<th><?php _e('Nome', 'reservawp'); ?></th>
			<th><?php _e('Título (plural)', 'reservawp'); ?></th>
			<th><?php _e('Título (singular)', 'reservawp'); ?></th>
			<th><?php _e('Descrição', 'reservawp'); ?></th>
			<th></th>
			<th></th>
		</tr>
	
<?php foreach ($types as $t) : $tp = get_post_type_object( $t['rwp_name'] ); ?>

	<tr class="rwp_object <?php echo $tp->name; ?>">
		<td class="rwp_name"><?php echo $tp->name; ?></td>
		<td class="rwp_objlabel"><?php echo $tp->label; ?></td>
		<td class="rwp_singlabel"><?php echo $tp->singular_label; ?></td>
		<td class="rwp_description"><?php echo $tp->description; ?></td>
		<td><input rel="<?php echo $tp->name; ?>" type="button" class="button-primary rwp_edit_object" value="<?php _e('Editar', 'reservawp'); ?>"></td>
		<td><input rel="<?php echo $tp->name; ?>" type="button" class="button-primary rwp_delete_thing" value="<?php _e('Deletar', 'reservawp'); ?>"></td>
	</tr>	

<?php endforeach; ?>
	</table>
<?php
}

function reserva_wp_list_statuses() {
	
	$types = get_option( 'reserva_wp_transaction_statuses' ); 
	
	?>

	<hr>
	<h3><?php _e('Editar status', 'reservawp'); ?></h3>
	<table>
		<tr>
			<th><?php _e('Nome', 'reservawp'); ?></th>
			<th><?php _e('Título', 'reservawp'); ?></th>
			<th><?php _e('Referência', 'reservawp'); ?></th>
			<th><?php _e('Descrição', 'reservawp'); ?></th>
			<th></th>
			<th></th>
		</tr>
	
<?php foreach ($types as $t) : ?>

	<tr class="rwp_object <?php echo $t['rwp_name']; ?>">
		<td class="rwp_name"><?php echo $t['rwp_name']; ?></td>
		<td class="rwp_statuslabel"><?php echo $t['rwp_statuslabel']; ?></td>
		<td class="rwp_statusref"><?php echo $t['rwp_statusref']; ?></td>
		<td class="rwp_description"><?php echo $t['rwp_description']; ?></td>
		<td><input rel="<?php echo $t['rwp_name']; ?>" type="button" class="button-primary rwp_edit_object" value="<?php _e('Editar', 'reservawp'); ?>"></td>
		<td><input rel="<?php echo $t['rwp_name']; ?>" type="button" class="button-primary rwp_delete_thing" value="<?php _e('Deletar', 'reservawp'); ?>"></td>
	</tr>	

<?php endforeach; ?>
	</table>
<?php
}


/**
* Busca a última transação relacionada entre um usuário e um objeto
* TODO: estender para buscar somente por usuario/objeto e outros retornos
*/
function reserva_wp_busca_ultima_transacao( $user_id, $object_id ) {
	$transactions = array_shift( get_posts( array( 
										'post_type' => 'rwp_transaction',
										'post_status' => 'any',
										'posts_per_page' => 1,
										'meta_query' => array( 
											array( 
												'key' => 'rwp_transaction_object',
												'value' => $object_id
											),
											array( 
												'key' => 'rwp_transaction_user',
												'value' => $user_id
											) 
											) ) ) );
	return $transactions->ID;

}

function reserva_wp_set_plano() {

	if(!$_POST['ajax'])
		return;

	$plano = $_POST['plano'];
	$transacao_id = $_POST['transacao'];

	if($plano && $transacao_id) {

		update_post_meta( $transacao_id, 'rwp_transaction_plan', $plano );

		$response = array('status' => 'ok');
	} else {
		$response = array('status' => 'erro');
	}

	header( "Content-Type: application/json" );
    echo json_encode($response);
    exit;

}
add_action( 'wp_ajax_reserva_wp_set_plano', 'reserva_wp_set_plano' );

function shortcode_emails($str, $post_id){
	$post = get_post($post_id);
	$user = get_user_by( 'id', $post->post_author );
	$object_id = get_post_meta( $post->ID, 'rwp_transaction_id', true );

	$str = str_replace('[titulo]',$post->post_title,$str);
	$str = str_replace('[link]',get_permalink($post_id),$str);
	$str = str_replace('[usuario]',$user->display_name,$str);
	$str = str_replace('[id]',$post_id,$str);
	$str = str_replace('[expiracao]',get_post_meta($object_id,'rwp_transaction_expire_date',true),$str);

	return $str;
}
?>