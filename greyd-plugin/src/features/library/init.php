<?php
/*
Feature Name:       Template Library
Description:        Create custom website templates and benefit from pre-made (Classic) templates.
Plugin URI:         https://greyd.io
Author:             Greyd
Author URI:         https://greyd.io
Version:            0.9
Text Domain:        greyd_hub
Domain Path:        /languages/
Requires at least:  6.0
Requires Features:  hub, post-export
Priority:           11
*/
namespace Greyd\Library;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * disable if plugin wants to run standalone
 * Standalone setup not possible - needs hub and post-export.
 */
if ( !class_exists( "Greyd\Admin" ) ) {
	// reject activation
	if ( !function_exists( 'get_plugins' ) ) require_once ABSPATH.'wp-admin/includes/plugin.php';
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die( sprintf( "%s can not be activated as standalone Plugin.", $plugin_name ) );
}

require_once __DIR__.'/admin.php';
require_once __DIR__.'/ajax.php';
require_once __DIR__.'/library.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}