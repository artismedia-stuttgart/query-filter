<?php
/*
Feature Name:       Headless API Integrations
Plugin URI:         https://greyd.io
Author:             Greyd
Author URI:         https://greyd.io
Description:        Raw Development for Headless API Integrations
Version:            0.2.1
Text Domain:        greyd_hub
Domain Path:        /languages/
Priority:           90
Requires Features:  hub, posttypes
Flag:               alpha
*/
namespace Greyd\Headless;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// escape if plugin already runs in standalone mode
if ( class_exists( 'Greyd\Headless\Admin' ) ) {
	return;
}

// disable if plugin wants to run standalone
if ( !class_exists("Greyd\Admin") ) {
	// reject activation
	if ( !function_exists( 'get_plugins' ) ) {
		require_once ABSPATH.'wp-admin/includes/plugin.php';
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
// if (!isset($config) || $config['plugin_name'] != 'greyd-plugin') {

// standalone config
// $config = array(
// 'plugin_name_full' => "Greyd.Plugin.Headless",
// 'plugin_name'      => "greyd-headless",
// 'plugin_file'      => __FILE__,
// 'plugin_path'      => __DIR__,
// 'is_standalone'    => true
// );
// include extra
// include_once(__DIR__.'/helper.php');
// include_once(__DIR__.'/settings.php');

// }

/* Debug Function */
if ( ! function_exists( 'debug' ) ) {
	function debug( $a, $b = false ) {
		echo '<pre>';
		! $b ? print_r( $a ) : var_dump( $a );
		echo '</pre>';
	}
}

require_once __DIR__.'/admin.php';
require_once __DIR__.'/loader.php';
require_once __DIR__.'/api-helper.php';
require_once __DIR__.'/api-blocks.php';
require_once __DIR__.'/api-posttypes.php';
require_once __DIR__.'/api-page.php';
