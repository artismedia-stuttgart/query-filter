<?php
/*
Feature Name:       User management
Description:        Manage User Roles, Accounts and User Meta Fields.
Plugin URI:         https://greyd.io
Author:             Greyd
Author URI:         https://greyd.io
Version:            0.2.0
Text Domain:        greyd_hub
Domain Path:        /languages/
priority:           80
Requires Features:
*/
namespace Greyd\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// escape if plugin already runs in standalone mode
if ( class_exists( 'Greyd\User\Manage' ) ) {
	return;
}

// disable if plugin wants to run standalone
if ( !class_exists("Greyd\Admin") ) {
	// reject activation
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die( sprintf( '%s can not be activated as standalone Plugin.', $plugin_name ) );
}

/**
 * Modular setup as sub-plugin.
 * todo
 */
// if (!isset($config) || $config['plugin_name'] != 'greyd_hub') {

// standalone config
// $config = array(
// 'plugin_name_full' => "Greyd Plugin.User",
// 'plugin_name'      => "greyd-user",
// 'plugin_file'      => __FILE__,
// 'plugin_path'      => __DIR__,
// 'is_standalone'    => true
// );
// include extra
// include_once(__DIR__.'/helper.php');
// include_once(__DIR__.'/settings.php');

// }

// include the table classes

// include
require_once __DIR__ . '/user-management.php';
require_once __DIR__ . '/registration.php';
require_once __DIR__ . '/loginform/loginform.php';
require_once __DIR__ . '/restrict_access.php';
require_once __DIR__ . '/user-meta.php';

if ( is_admin() ) {

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}
	if ( ! class_exists( 'WP_Users_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php';
	}
	if ( ! class_exists( 'WP_MS_Users_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-ms-users-list-table.php';
	}
	
	require_once __DIR__ . '/users-page.php';
	require_once __DIR__ . '/roles-page.php';
	require_once __DIR__ . '/roles-list-table.php';
	require_once __DIR__ . '/metafields-table.php';
	require_once __DIR__ . '/settings-page.php';
	require_once __DIR__ . '/mails-page.php';
}

/* Debug Function */
if ( ! function_exists( 'debug' ) ) {
	function debug( $a, $b = false ) {
		echo '<pre>';
		! $b ? print_r( $a ) : var_dump( $a );
		echo '</pre>';
	}
}
