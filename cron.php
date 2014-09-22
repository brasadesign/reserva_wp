<?php
ini_set( 'error_reporting', '1' );

/* API Pagseguro */
if ( $_GET['checkpagseguro'] ) {
	reserva_wp_cron_check_pagamentos();
}
if ( $_GET['pagseguronotification'] ) {
	add_action( 'init', 'reserva_wp_pagseguro_notificacoes', 9999999999999999999999999 );
	//reserva_wp_pagseguro_notificacoes();
}
if ( $_GET['update_post_cron'] ) {
	add_action( 'init', 'reserva_wp_update_post_cron', 9999999999999999999999999 );
}
//add_action( 'init', 'teste_emails', 9999999999999999999999999 );

function teste_emails() {
	$opt = get_option( 'rwp_options' );
	var_dump( $opt );
	//die();
}

add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_expires' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_removes' );
add_action( 'reserva_wp_cron_hourly_hook', 'reserva_wp_cron_check_pagamentos' );
function reserva_wp_update_post_cron() {
	global $wpdb;
	$date = new DateTime();
	$now  = (string) $date->format( 'd-m-Y' );
	echo 'now:' . $now;
	// WP_Query arguments
	$transactions = get_posts( array(
		'post_type'   => 'rwp_transaction',
		'post_status' => 'any',
		'meta_key'    => 'rwp_transaction_expire_date',
		'meta_value'  => $now,
	) );
	$contador     = 0;

	if ( $transactions ) {
		foreach ( $transactions as $t ) {
			$newstatus = 'retirado';
			$object_id = get_post_meta( $t->ID, 'rwp_transaction_object', true );
			$post_id   = get_post_meta( $t->ID, 'rwp_transaction_listing_id', true );

			$meta_day = get_post_meta( $t->ID, 'rwp_transaction_expire_date', true );
			echo '<br>Meta:' . $meta_day . '<br>';
			update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'private' ), array( 'id' => $post_id ) );
			$post_autor      = get_post( $post_id );
			$post_autor_mail = get_the_author_meta( 'user_email', $post_autor->post_author );

			//$email = $post_autor_mail;
			// Return a boolean!
			//editar email
			$from    = 'no-reply@ecotemporadas.com';
			$opt = get_option( 'rwp_options' );
			$to      = $post_autor_mail;
			$subject = shortcode_emails($opt['rwp_email_vencido_title'],$post_id);
			$message = shortcode_emails($opt['rwp_email_vencido'],$post_id);
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: ' . get_bloginfo( 'name' ) . '' . ' <' . $from . '>' . "\r\n";

			if ( $to && $subject && $message && $headers ) {
				wp_mail( $to, $subject, $message, $headers );
				echo $to . ' - ' . $subject . ' - ' . $message . ' - ' . $headers;
			}
			//do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			//do_action( 'rwp_status_changed_to_' . $newstatus, $t->ID, $object_id );
			$contador ++;
		}
	}

	echo '<br> Removidos:' . $contador;
	$contador = 0;

	//$date->modify( '-7 days' );
	//$now = (string) $date->format( 'd-m-Y' );
	echo 'now:' . $now;
	$transactions_pre = get_posts( array(
		'post_type'   => 'rwp_transaction',
		'post_status' => 'any',
		'meta_key'    => 'rwp_transaction_pre_expire_date',
		'meta_value'  => $now,
	) );
	$contador         = 0;

	if ( $transactions_pre ) {
		foreach ( $transactions_pre as $t ) {
			$newstatus = 'expirando';
			$object_id = get_post_meta( $t->ID, 'rwp_transaction_object', true );
			$post_id   = get_post_meta( $t->ID, 'rwp_transaction_listing_id', true );

			$meta_day = get_post_meta( $t->ID, 'rwp_transaction_pre_expire_date', true );
			echo '<br>Meta:' . $meta_day . '<br>';
			//update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'expirando' ), array( 'id' => $post_id ) );
			$post_autor      = get_post( $post_id );
			$post_autor_mail = get_the_author_meta( 'user_email', $post_autor->post_author );

			//editar email
			$from    = 'no-reply@ecotemporadas.com';
			$opt = get_option( 'rwp_options' );
			$to      = $post_autor_mail;
			$subject = shortcode_emails($opt['rwp_email_pre_vencido_title'],$post_id);
			$message = shortcode_emails($opt['rwp_email_pre_vencido'],$post_id);
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: ' . get_bloginfo( 'name' ) . '' . ' <' . $from . '>' . "\r\n";

			if ( $to && $subject && $message && $headers ) {
				wp_mail( $to, $subject, $message, $headers );
				echo $to . ' - ' . $subject . ' - ' . $message . ' - ' . $headers;
			}
			//do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			//do_action( 'rwp_status_changed_to_' . $newstatus, $t->ID, $object_id );
			$contador ++;
		}
	}
	echo 'Expirados:' . $contador;
// Restore original Post Data
	wp_reset_postdata();
	die();
}

// Roda uma vez na ativação, agendando o cron
function reserva_wp_cron_job_schedule() {
	wp_schedule_event( time(), 'daily', 'reserva_wp_cron_daily_hook' );
	wp_schedule_event( time(), 'hourly', 'reserva_wp_cron_hourly_hook' );
}

// Busca posts liberados com prazo de vencimento inferior a 30 dias
// Altera o status para Expirando
function reserva_wp_cron_check_expires() {

	$transactions = get_posts( array(
		'post_type'  => 'rwp_transaction',
		'meta_key'   => 'rwp_transaction_status',
		'meta_value' => 'liberado',
		'meta_query' => array(
			array(
				'key'     => 'rwp_transaction_object_published_until',
				'value'   => time() + 30 * 24 * 60 * 60,
				'compare' => '<=',
				'type'    => 'numeric'
			)
		)
	) );

	if ( $transactions ) {
		foreach ( $transactions as $t ) {

			$newstatus = 'expirando';
			$object_id = get_post_meta( $transaction_id, 'rwp_transaction_object', true );

			update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );

			do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			do_action( 'rwp_status_changed_to_' . $newstatus, $t->ID, $object_id );

		}
	}
}

// Busca posts vencidos
// Altera o status para Retirado
function reserva_wp_cron_check_removes() {

	$transactions = get_posts( array(
		'post_type'  => 'rwp_transaction',
		'meta_key'   => 'rwp_transaction_status',
		'meta_value' => 'expirando',
		'meta_query' => array(
			array(
				'key'     => 'rwp_transaction_object_published_until',
				'value'   => strtotime( 'today' ),
				'compare' => '<=',
				'type'    => 'numeric'
			)
		)
	) );

	if ( $transactions ) {
		foreach ( $transactions as $t ) {

			$newstatus = 'retirado';
			$object_id = get_post_meta( $transaction_id, 'rwp_transaction_object', true );

			update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );

			do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			do_action( 'rwp_status_changed_to_' . $newstatus, $t->ID, $object_id );

		}
	}
}


function reserva_wp_pagseguro_notificacoes() {
	header( "access-control-allow-origin: https://pagseguro.uol.com.br" );
	$opt = get_option( 'rwp_options' );
	require_once dirname( __FILE__ ) . '/PagSeguroLibrary/PagSeguroLibrary.php';

	try {
		$credentials = new PagSeguroAccountCredentials(
			$opt['rwp_pagseguro_email'],
			$opt['rwp_pagseguro_token']
		);

		/* Tipo de notificação recebida */
		$type = $_POST['notificationType'];

		/* Código da notificação recebida */
		$code = $_POST['notificationCode'];


		/* Verificando tipo de notificação recebida */
		if ( $type === 'transaction' ) {

			/* Obtendo o objeto PagSeguroTransaction a partir do código de notificação */
			$transaction = PagSeguroNotificationService::checkTransaction(
				$credentials,
				$code // código de notificação
			);
			$status      = $transaction->getStatus()->getValue();

			if ( $status < 3 ) { // 1 == aguardando / 2 == análise

				$msg = 'aguardando';
			}
			if ( 3 == $status || 4 == $status ) { // 3 == pago / 4 == disponivel

				$msg = 'liberado';
			}
			if ( $status > 4 ) { // 5 == disputa / 6 == devolvida / 7 == cancelada

				$msg = 'retirado';
			}
			$reference = $transaction->getReference();
			echo 'status:' . $status;
			echo 'referencia antes:' . $reference;
			$reference = str_replace( 'ID ', '', $reference );
			$reference = explode( '-', $reference );
			$post_id   = $reference[0];
			//$tr_id =  $reference[1];
			$tr_id = get_post_meta( $post_id, 'rwp_transaction_id', true );
			$date  = new DateTime();
			$date->modify( '+'.$opt['rwp_plano'].' days' );

			require_once dirname( __FILE__ ) . '/post_types.php';
			if ( $msg == 'liberado' ) {
				update_post_meta( $tr_id, 'rwp_transaction_listing_id', $post_id );
				update_post_meta( $tr_id, 'rwp_transaction_expire_date', $date->format( 'd-m-Y' ) );
				$date->modify( '-10 days' );
				update_post_meta( $tr_id, 'rwp_transaction_pre_expire_date', $date->format( 'd-m-Y' ) );
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'id' => $post_id ) );
				$post_autor      = get_post( $post_id );
				$post_autor_mail = get_the_author_meta( 'user_email', $post_autor->post_author );

				//$email = $post_autor_mail;
				// Return a boolean!
				//editar email
				$to      = $post_autor_mail;
				$subject = shortcode_emails($opt['rwp_email_liberado_title'],$post_id);
				$message = shortcode_emails($opt['rwp_email_liberado'],$post_id);
				$from    = 'no-reply@ecotemporadas.com';
				//$headers = 'From: '.get_bloginfo('name').''.' <' . $from.'>';
				$headers = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-"UTF-8"' . "\r\n";
				$headers .= 'From: ' . get_bloginfo( 'name' ) . '' . ' <' . $from . '>' . "\r\n";
				if ( $to && $subject && $message && $headers ) {
					wp_mail( $to, $subject, $message, $headers );
					echo $to . ' - ' . $subject . ' - ' . $message . ' - ' . $headers;
				}

			}
			update_post_meta( $tr_id, 'rwp_transaction_status', $msg );
			echo '[infos]';
			echo 'objeto:' . $tr_id;
			echo 'post:' . $post_id;
			echo 'msg:' . $msg;
		}
	} catch ( PagSeguroServiceException $e ) {
		echo '<pre>';
		var_dump( $e );
		echo '</pre>';
		$msg = $status = $e->getMessage();
	}
	//die();

	// var_dump($statuses);
}

?>