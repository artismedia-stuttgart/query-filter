<?php
/*
Feature Name:   Greyd.Hub
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Description:    Manage all websites and content of your installation.
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       1
*/
namespace Greyd\Hub;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in standalone mode
if ( class_exists("Greyd\Hub\Admin") ) return;

/**
 * Modular setup as sub-plugin.
 * Standalone setup requires copy of helper and backend css/js.
 * 
 * greyd-hub
 * - assets/css/backend.css (copy)
 * - assets/css/hub.css
 * - assets/js/backend.js (copy)
 * - assets/js/hub.js
 * - init.php
 * - helper.php (copy)
 * - hub-helper.php
 * - hub-tools.php
 * - hub-sql.php
 * - hub.php
 * - tab-websites.php
 * - tab-backups.php
 * - tab-database.php
 * - hub-log.php
 */
if ( !isset($config) || 
	($config['plugin_name'] != 'greyd-plugin' &&
	 $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "Greyd Plugin.Hub",
		'plugin_name'      => "greyd-network",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'homepage'         => 'https://greyd.io/',
		'is_standalone'    => true
	);
	// include extra
	// require_once __DIR__.'/helper.php';

}

// include 
require_once __DIR__.'/hub-helper.php';
require_once __DIR__.'/hub-tools.php';
require_once __DIR__.'/hub-sql.php';
require_once __DIR__.'/hub.php';
require_once __DIR__.'/tab-websites.php';
require_once __DIR__.'/tab-backups.php';
require_once __DIR__.'/tab-database.php';
require_once __DIR__.'/hub-log.php';
require_once __DIR__.'/hub-staging.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}