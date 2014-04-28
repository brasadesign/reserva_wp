<?php 

register_activation_hook( __FILE__, 'reserva_wp_cron_job_schedule' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_expires' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_removes' );

// Roda uma vez na ativação, agendando o cron
function reserva_wp_cron_job_schedule() {
	wp_schedule_event( time(), 'daily', 'reserva_wp_cron_daily_hook' );
}

// Busca posts liberados com prazo de vencimento inferior a 30 dias
// Altera o status para Expirando
function reserva_wp_cron_check_expires() {	

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 
										'rwp_transaction_status' => 'liberado',
										'meta_query' => array( array( 
											'key' => 'rwp_transaction_object_published_until',
											'value' => time()+30*24*60*60,
											'compare' => '<=',
											'type'	=> 'numeric'
											) ) ) );

	if($transactions) {
		foreach ($transactions as $t) {

			$newstatus = 'expirando';
			$object_id = get_post_meta( $transaction_id, 'rwp_transaction_object', true );

			update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );
			
			do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			do_action( 'rwp_status_changed_to_'.$newstatus, $t->ID, $object_id );

		}
	}
}

// Busca posts vencidos
// Altera o status para Retirado
function reserva_wp_cron_check_removes() {	

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 
										'rwp_transaction_status' => 'expirando',
										'meta_query' => array( array( 
											'key' => 'rwp_transaction_object_published_until',
											'value' => strtotime('today'),
											'compare' => '<=',
											'type'	=> 'numeric'
											) ) ) );

	if($transactions) {
		foreach ($transactions as $t) {

			$newstatus = 'retirado';
			$object_id = get_post_meta( $transaction_id, 'rwp_transaction_object', true );

			update_post_meta( $t->ID, 'rwp_transaction_status', $newstatus );
			
			do_action( 'rwp_status_changed', $newstatus, $t->ID, $object_id );
			do_action( 'rwp_status_changed_to_'.$newstatus, $t->ID, $object_id );

		}
	}
}

?>