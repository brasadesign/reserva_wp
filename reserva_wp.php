<?php
/*
Plugin Name: Reserva WP
Plugin URI: https://github.com/moraleida/wp-reserva
Description: Plugin para gerenciamento de reservas em WordPress
Author: Ricardo Moraleida, BRASA Design
Version: 0.1
Author URI: https://github.com/moraleida/wp-reserva
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Access restriction
*/
if ( !function_exists( 'add_action' ) ) {
	_e('Acesso restrito', 'reserva_wp');
	exit;
}

/**
* Definitions
*/
// define('RESERVA_WP_VERSION', '0.1');
// define('RESERVA_WP_PLUGIN_URL', plugins_url( false, __FILE__ ) );

/**
* Includes
*/
// Creates the admin screens and the basic transaction logic
// if ( is_admin() )
require_once dirname( __FILE__ ) . '/admin.php';

// Creates the cron schedule
require_once dirname( __FILE__ ) . '/cron.php';

// Creates the post types and post statuses
require_once dirname( __FILE__ ) . '/post_types.php';

register_activation_hook( __FILE__, "reserva_wp_activate" );
register_activation_hook( __FILE__, 'reserva_wp_cron_job_schedule' );

// Creates the post types and post statuses

// require_once dirname( __FILE__ ) .'/PagSeguroLibrary/searchTransactionByCode.php';

// Creates the taxonomies associated to the post_types
// require_once dirname( __FILE__ ) . '/taxonomies.php';

// Creates the meta boxes for managing object logic
// require_once dirname( __FILE__ ) . '/meta_boxes.php';


?>