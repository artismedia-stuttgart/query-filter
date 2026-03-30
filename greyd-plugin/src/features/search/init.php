<?php
/*
Feature Name:   Advanced Search
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Description:    Advanced Search Features.
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       19
*/

/**
 * Init all the advanced search features
 * @since 0.8.8
 */
namespace Greyd\Search;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in standalone mode
if ( class_exists("Greyd\Search\Settings") ) return;

/**
 * Modular setup as sub-plugin.
 * Standalone setup without helper possible.
 * settings and helper functions have fallbacks.
 * 
 * greyd-search
 * - assets/js/autosearch.js
 * - assets/js/livesearch.js
 * - init.php
 * - settings.php
 * - query.php
 * - live-search.php
 * - postviews-counter.php
 * - relevance.php
 * - exclude.php
 */
if ( !isset($config) || 
	($config['plugin_name'] != 'greyd-plugin' &&
	 $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "Greyd Plugin.Search",
		'plugin_name'      => "greyd-search",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'is_standalone'    => true
	);

}

if ( !defined('GREYD_REST_NAMESPACE') ) {
	define('GREYD_REST_NAMESPACE', 'greyd/v1');
}

require_once __DIR__.'/blocks.php';
require_once __DIR__.'/settings.php';
require_once __DIR__.'/query.php';
require_once __DIR__.'/live-search.php';
require_once __DIR__.'/postviews-counter.php';
require_once __DIR__.'/relevance.php';
require_once __DIR__.'/exclude.php';
require_once __DIR__.'/date.php';
require_once __DIR__.'/jsx-blocks/init.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}