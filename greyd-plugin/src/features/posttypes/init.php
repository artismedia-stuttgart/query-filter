<?php
/*
Feature Name:   Post Types
Description:    Create custom post types with their own layouts and clear data maintenance.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       6
*/

namespace Greyd\Posttypes;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in standalone mode
if (class_exists("Greyd\Posttypes\Admin")) return;

/**
 * Modular setup as sub-plugin.
 * Standalone setup requires copy of helper, backend css/js and iconpicker lib.
 * 
 * greyd-posttypes
 * - assets/css/backend.css (copy)
 * - assets/css/posttypes.css
 * - assets/js/backend.js (copy)
 * - assets/js/posttypes.js
 * - libs/iconpicker/* (copy)
 * - automator/automator.php
 * - automator/synced_posttype.php
 * - init.php
 * - helper.php (copy)
 * - posttype-dummy.php
 * - posttype-helper.php
 * - admin.php
 * - posttypes.php
 * - dynamic-posttypes.php
 */
if ( !isset($config) || 
	($config['plugin_name'] != 'greyd-plugin' &&
	 $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "Greyd Plugin.Posttypes",
		'plugin_name'      => "greyd-posttypes",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'is_standalone'    => true
	);
	// include extra
	// require_once __DIR__.'/helper.php';

}

require_once __DIR__.'/automator/automator.php';
require_once __DIR__.'/automator/synced_posttype.php';

require_once __DIR__.'/posttype-dummy.php';
require_once __DIR__.'/posttype-helper.php';

require_once __DIR__.'/admin.php';
require_once __DIR__.'/posttypes.php';
require_once __DIR__.'/posttypes-wizard.php';
require_once __DIR__.'/dynamic-posttypes.php';


/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}