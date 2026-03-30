<?php
/*
Feature Name:   Site Connector
Description:    Connect Any Number of Websites, whether its individual websites or entire multisite installations.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Requires Features: hub
Priority:       8
*/

namespace Greyd\Connections;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * disable if plugin wants to run standalone
 * Standalone setup not possible - needs hub.
 */
if ( !class_exists( "Greyd\Admin" ) ) {
	// reject activation
	if (!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
	$plugin_name = get_plugin_data(__FILE__, false, false)['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die(sprintf("%s can not be activated as standalone Plugin.", $plugin_name));
}

if ( !defined('GREYD_REST_NAMESPACE') ) {
	define('GREYD_REST_NAMESPACE', 'greyd/v1');
}

require_once __DIR__.'/connections-helper.php';
require_once __DIR__.'/init-endpoints.php';
// backend
if ( is_admin() ) {
	require_once __DIR__.'/connections-page.php';
	require_once __DIR__.'/connections-list-table.php';
}