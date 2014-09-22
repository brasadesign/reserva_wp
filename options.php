<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15/07/14
 * Time: 20:57
 */
require_once plugin_dir_path( __FILE__ ) . '/odin-options.php' ;
$_options = new Reserva_WP_Options(
    'rwp_options', // Slug
    'Reserva WP', // Page title
    'manage_options' // Permission
);
$_options->set_tabs(
    array(
        array(
            'id' => 'rwp_options',
            'title' => __( 'Opções', 'reservawp' ),
        ),
    )
);
//$_types = get_post_types('','names');
//$_types = implode(',',$_types);
$_options->set_fields(
    array(
        'rwp_options_section' => array(
            'tab'   => 'rwp_options',
            'title' => '',
            'fields' => array(
                array(
                    'id' => 'rwp_pagseguro_token',
                    'label' => __( 'Token do PagSeguro', 'reservawp' ),
                    'type' => 'text',
                   // 'default' => $_types,
                    //'description' => __( 'Option visible in post types', 'reveal-modal' ),
                ),
	            array(
		            'id' => 'rwp_pagseguro_email',
		            'label' => __( 'Email do PagSeguro', 'reservawp' ),
		            'type' => 'text',
		            //'description' => __( 'Option visible in post types', 'reveal-modal' ),
	            ),
	            array(
		            'id' => 'rwp_email_vencido_title',
		            'label' => __( 'Email - Status Vencido - Titulo', 'reservawp' ),
		            'type' => 'text',
		            'default' => 'Seu anuncio mudou de status para: vencido/retirado',
		            'description' => 'Padrão: Seu anuncio mudou de status para: vencido/retirado',
	            ),
	            array(
		            'id' => 'rwp_plano',
		            'label' => __( 'Numero de dias em que o anuncio fica no ar depois de pago' ),
		            'type' => 'text',
		            'default' => '365',
		            'description' => 'Padrão: 365',
	            ),
	            array(
		            'id' => 'rwp_email_vencido',
		            'label' => __( 'Email - Status Vencido - Mensagem', 'reservawp' ),
		            'type'        => 'editor',
		            'default'     => 'O status do seu anuncio mudou para: vencido/retirado - Faça o pagamento para restabelecer o anuncio', // Opcional
		            'description' => __( 'Padrão: O status do seu anuncio mudou para: vencido/retirado - Faça o pagamento para restabelecer o anuncio', 'reservawp' ), // Opcional
		            'options'     => array( // Opcional (aceita argumentos do wp_editor)
			            'textarea_rows' => 20,
			            'wpautop' => true,
		            ),
	            ),
	            array(
		            'id' => 'rwp_email_pre_vencido_title',
		            'label' => __( 'Email - Status Vencendo (10 dias antes de vencer) - Titulo', 'reservawp' ),
		            'type' => 'text',
		            'default' => 'Seu anuncio vence nos proximos 10 dias',
		            'description' => 'Padrão: Seu anuncio vence nos proximos 10 dias',
	            ),
	            array(
		            'id' => 'rwp_email_pre_vencido',
		            'label' => __( 'Email - Status Vencendo (10 dias antes de vencer) - Mensagem', 'reservawp' ),
		            'type'        => 'editor',
		            'default'     => 'Seu anuncio vence nos proximos 10 dias - Faça o pagamento para restabelecer o anuncio', // Opcional
		            'description' => __( 'Padrão: Seu anuncio vence nos proximos 10 dias - Faça o pagamento para restabelecer o anuncio', 'reservawp' ), // Opcional
		            'options'     => array( // Opcional (aceita argumentos do wp_editor)
			            'textarea_rows' => 20,
			            'wpautop' => true,
		            ),
	            ),
	            array(
		            'id' => 'rwp_email_liberado_title',
		            'label' => __( 'Email - Status Liberado (quando foi pago) - Titulo', 'reservawp' ),
		            'type' => 'text',
		            'default' => 'O status do seu anuncio mudou para: liberado',
		            'description' => 'Padrão: O status do seu anuncio mudou para: liberado',
	            ),
	            array(
		            'id' => 'rwp_email_liberado',
		            'label' => __( 'Email - Status Liberado (quando foi pago) - Mensagem', 'reservawp' ),
		            'type'        => 'editor',
		            'default'     => 'Recebemos seu pagamento e liberamos seu anuncio - Obrigado por utilizar nossos serviços', // Opcional
		            'description' => __( 'Padrão: Recebemos seu pagamento e liberamos seu anuncio - Obrigado por utilizar nossos serviços', 'reservawp' ), // Opcional
		            'options'     => array( // Opcional (aceita argumentos do wp_editor)
			            'textarea_rows' => 20,
			            'wpautop' => true,
		            ),
	            ),
            )
        ),
    )
);
