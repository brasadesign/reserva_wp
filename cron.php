<?php 

require_once dirname( __FILE__ ) .'/PagSeguroLibrary/PagSeguroLibrary.php';

register_activation_hook( __FILE__, 'reserva_wp_cron_job_schedule' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_expires' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_removes' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_pagamentos' );

// Roda uma vez na ativação, agendando o cron
function reserva_wp_cron_job_schedule() {
	wp_schedule_event( time(), 'daily', 'reserva_wp_cron_daily_hook' );
}

// Busca posts liberados com prazo de vencimento inferior a 30 dias
// Altera o status para Expirando
function reserva_wp_cron_check_expires() {	

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 
										'meta_key' =>	'rwp_transaction_status',
										'meta_value' => 'liberado',
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
										'meta_key' =>	'rwp_transaction_status',
										'meta_value' => 'expirando',
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

/* API Pagseguro */

//   reserva_wp_cron_check_pagamentos();

// Chama a API do Pagseguro pra confirmar os pagamentos
function reserva_wp_cron_check_pagamentos() {	

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 
									  'meta_key' =>	'rwp_transaction_status',
									  'meta_value' => 'aguardando',
									  'posts_per_page' => -1,
									  'date_query' => array( array( 
									  	'column' => 'post_modified_gmt',
										'after' => '30 days ago',
										'inclusive' => true
										) )
	) );



	/* Definindo as credenciais  */    
	$credentials = new PagSeguroAccountCredentials(      
	    'andre@eaxdesign.com.br',       
	    '60EE5486C8B444A7BA219F7F9D82414A'      
	);  
	  
	/* Código identificador da transação  */    
	$transaction_id = 'BA83420F-F723-4270-AEB4-1D08B188A798';  
	  
	/*  
	    Realizando uma consulta de transação a partir do código identificador  
	    para obter o objeto PagSeguroTransaction 
	*/   
	$transaction = PagSeguroTransactionSearchService::searchByCode(  
	    $credentials,  
	    $transaction_id  
	);  

	/* Imprime o código do status da transação */  
	wp_die(dump($transaction->getStatus()));  

}

?>