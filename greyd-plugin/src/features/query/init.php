<?php
/*
Feature Name:   Query Features
Description:    Post slider, pagination, filters and much more - natively in the block editor.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.0.1
Text Domain:    greyd_hub
Domain Path:    /languages/
Requires Features: dynamic
Priority:       13
*/
namespace Greyd\Query;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in standalone mode
if (class_exists("Greyd\Query\Post_Template")) return;

/**
 * Modular setup as  standalone plugin.
 * 
 * greyd-query
 * Standalone setup requires copy of helper.php and backend.css.
 * - assets/css/backend.css (copy)
 * - assets/js/editor.js
 * - assets/js/frontend.js
 * - init.php
 * - helper.php (copy)
 * - query.php (copy)
 * - post-table.php (copy)
 * - live-filter.php (copy)
 */
if ( !isset($config) || 
	($config['plugin_name'] != 'greyd-plugin' &&
	 $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "Greyd Plugin.Query",
		'plugin_name'      => "greyd-query",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'is_standalone'    => true
	);
	// include extra
	// require_once __DIR__.'/helper.php';

}

require_once __DIR__.'/enqueue.php';
require_once __DIR__.'/render.php';
require_once __DIR__.'/post-template.php';
require_once __DIR__.'/post-table.php';
require_once __DIR__.'/live-filter.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}