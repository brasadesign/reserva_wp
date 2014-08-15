<?php 
ini_set('error_reporting', '1');

/* API Pagseguro */
if($_GET['pag']) {
	reserva_wp_cron_check_pagamentos();
}

add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_expires' );
add_action( 'reserva_wp_cron_daily_hook', 'reserva_wp_cron_check_removes' );
add_action( 'reserva_wp_cron_hourly_hook', 'reserva_wp_cron_check_pagamentos' );

// Roda uma vez na ativação, agendando o cron
function reserva_wp_cron_job_schedule() {
	wp_schedule_event( time(), 'daily', 'reserva_wp_cron_daily_hook' );
	wp_schedule_event( time(), 'hourly', 'reserva_wp_cron_hourly_hook' );
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



// Chama a API do Pagseguro pra confirmar os pagamentos
function reserva_wp_cron_check_pagamentos() {	

	require_once dirname( __FILE__ ) .'/PagSeguroLibrary/PagSeguroLibrary.php';

	/* Banco Pagseguro */    
	$DB_TYPE="mysql";
	//$retorno_host = 'localhost'; // Local da base de dados MySql
	//$retorno_database = 'ecotempo_main'; // Nome da base de dados MySql
	//$retorno_usuario = 'ecotempo_admin'; // Usuario com acesso a base de dados MySql 
	//$retorno_senha = 'ZpaK}GN5ni({';  // Senha de acesso a base de dados MySql

	$retorno_host = '186.202.152.99'; // Local da base de dados MySql
	$retorno_database = 'temporadas1'; // Nome da base de dados MySql
	$retorno_usuario = 'temporadas1'; // Usuario com acesso a base de dados MySql
	$retorno_senha = 'hu4wuOg5vv';  // Senha de acesso a base de dados MySql

	$mysqli = new mysqli($retorno_host, $retorno_usuario, $retorno_senha, $retorno_database);
	if($mysqli->connect_errno)
		die ('Nao foi possível conectar ao MySql: ' . $mysqli->connect_errno);
	
	/* Definindo as credenciais  */    
	$credentials = new PagSeguroAccountCredentials(      
	    'andre@eaxdesign.com.br',       
	    '8B15EE8E21634441882BADC042B182C9'
	);  

	$transactions = get_posts( array( 'post_type' => 'rwp_transaction', 
									  //'meta_key' =>	'rwp_transaction_status',
									  // 'meta_value' => 'aguardando',
									  'posts_per_page' => -1,
									  'date_query' => array( array( 
									  	'column' => 'post_modified_gmt',
										'after' => '30 days ago',
										'inclusive' => true
										) )
	) );


	foreach ($transactions as $t) {
		$obj = get_post_meta($t->ID, 'rwp_transaction_object', true);
		$in[] = $obj.'-'.$t->ID;
	}

	$in = join(',', $in);

	$sql = "SELECT TransacaoID,Referencia FROM `PagSeguroTransacoes` WHERE Referencia IN (".$in.")";
	$res = $mysqli->query($sql);

	/* Códigos identificadores da transação  */
	if($res) {
		while($row = $res->fetch_assoc()) {
			$tids[$row['Referencia']] = $row['TransacaoID'];
		}		
	}
	$tids['ID 3207-3210'] = '699C3682-44B8-4110-93A7-4E55A61D58BC'; // teste Andre
	
	$statuses = array();

	if($tids) :
	foreach($tids as $key => $value) {
		/*  
		    Realizando uma consulta de transação a partir do código identificador  
		    para obter o objeto PagSeguroTransaction 
		*/   
		try {
			$transaction = PagSeguroTransactionSearchService::searchByCode( $credentials, $value );  
			$status = $transaction->getStatus()->getValue();
			$intTrans = explode('-', $key);

			if($status < 3) { // 1 == aguardando / 2 == análise
				
				$msg = 'aguardando';
			}
			if(3 == $status || 4 == $status) { // 3 == pago / 4 == disponivel
				
				$msg = 'liberado';
			}
			if($status > 4) { // 5 == disputa / 6 == devolvida / 7 == cancelada
				
				$msg = 'retirado';
			}

			require_once dirname( __FILE__ ) . '/post_types.php';
			$meta_id = update_post_meta( $intTrans[1], 'rwp_transaction_status', $msg );
			

		} catch (PagSeguroServiceException $e) {
			echo '<pre>'; var_dump($e); echo '</pre>';
            $msg = $status = $e->getMessage();
        }
		
		$statuses[$value] = array('status' => $status, 'rwp_transaction' => $intTrans[1], 'msg' => $msg );
	}
	endif;

	// var_dump($statuses);
}

?>