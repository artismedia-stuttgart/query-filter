<?php
/*
Feature Name:   Dynamic Templates
Description:    Create dynamic templates that you can fill with different content in different places on your website.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.0.1
Text Domain:    greyd_hub
Domain Path:    /languages/
Requires Features: posttypes
priority:       2
*/
namespace Greyd\Dynamic;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Check if old version of this feature (inside theme) is still loaded.
 */
$main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
if ( strpos($main_theme->get('Name'), "GREYD.SUITE") !== false ) {

	// // check if gutenberg option is enabled
	// if ( get_option('greyd_gutenberg') === false || get_option('greyd_gutenberg') == '' ) {
	//     return;
	// }
}

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


// include basic functionality
require_once __DIR__.'/dynamic-helper.php';
require_once __DIR__.'/deprecated/dynamic-dummy.php';
require_once __DIR__.'/register.php';
require_once __DIR__.'/admin.php';
require_once __DIR__.'/render.php';
require_once __DIR__.'/deprecated/render.php';
require_once __DIR__.'/deprecated/admin.php';

// include block features
require_once __DIR__.'/manage.php';
require_once __DIR__.'/render-blocks.php';
require_once __DIR__.'/render-patterns.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}