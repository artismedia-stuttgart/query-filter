<?php
/*
Feature Name:   ACF Integration
Description:    Display ACF fields in your content using dynamic tags.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.2
Text Domain:    greyd_hub
Domain Path:    /languages/
priority:       91
Requires Features: posttypes, dynamic
Forced:         true
*/

namespace Greyd\Posttypes\ACF;

if ( !defined( 'ABSPATH' ) ) exit;

if ( !isset($config) || ($config['plugin_name'] != 'greyd-plugin' && $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "ACF Integration",
		'plugin_name'      => "greyd-acf",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'is_standalone'    => true,
		'plugin_version'   => '0.1'
	);

}

if ( is_admin() ) {
	require_once __DIR__.'/admin.php';
} else {
	require_once __DIR__.'/render.php';
}


/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}