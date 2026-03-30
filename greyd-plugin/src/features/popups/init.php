<?php
/*
Feature Name:   Pop-ups
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Description:    Create custom pop-ups with individual designs and easy maintenance of conditions.
Version:        0.0.1
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       7
*/
namespace Greyd\Popups;
 
if ( !defined( 'ABSPATH' ) ) exit;

if ( !isset( $_current_main_theme ) ) {
	$_current_main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
}

/**
 * Check if old version of this feature (inside theme) is still loaded.
 */
if ( strpos( $_current_main_theme->get('Name'), "GREYD.SUITE" ) !== false ) {

	// check theme version (do not activate on versions lower than 1.3.6)
	if ( version_compare( $_current_main_theme->get('Version'),  "1.3.6" ) < 0 ) {
		return;
	}

	// check if gutenberg option is enabled
	if ( ! \Greyd\Helper::is_greyd_blocks() ) {
		return;
	}
}

// escape if plugin already runs in standalone mode
if (class_exists("Greyd\Popups\Admin")) return;

/**
 * Modular setup as  standalone plugin.
 * Standalone setup requires copy of helper.php and backend.css.
 * Copies of editor.css, editor-components.js and editor-tools.js 
 * are required for Blockeditor features.
 * 
 * greyd-popups
 * - assets/css/backend.css (copy)
 * - assets/css/editor.css (copy)
 * - assets/css/frontend.css
 * - assets/css/popups.css
 * - assets/css/popup-close.css
 * - assets/js/editor-components.js (copy)
 * - assets/js/editor-tools.js (copy)
 * - assets/js/editor.js
 * - assets/js/frontend.js
 * - assets/js/popups.js
 * - assets/js/popup-close.js
 * - init.php
 * - helper.php (copy)
 * - frontend.php
 * - popups.php
 */
if ( !isset($config) || 
	($config['plugin_name'] != 'greyd-plugin' &&
	 $config['plugin_name'] != 'greyd_tp_management') ) {

	// standalone config
	$config = array(
		'plugin_name_full' => "Greyd Plugin.Pop-ups",
		'plugin_name'      => "greyd-popups",
		'plugin_file'      => __FILE__,
		'plugin_path'      => __DIR__,
		'is_standalone'    => true
	);
	// include extra
	// require_once __DIR__.'/helper.php';

}

// include 
require_once __DIR__.'/popups-dummy.php';
require_once __DIR__.'/popups.php';
require_once __DIR__.'/popups-blocks.php';
require_once __DIR__.'/frontend.php';

/* Debug Function */
if ( !function_exists('\debug') ) {
	function debug($a, $b=false) { 
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>'; 
	}
}