<?php
/*
Plugin Name:    Greyd Plugin
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Description:    The Greyd Plugin offers you a comprehensive set of features for creating and managing professional websites. From powerful extensions for the Block & Site Editor to a feature-rich website management platform with numerous admin functions.
Version:        2.18.3
Text Domain:    greyd_hub
Domain Path:    /languages/
*/
namespace Greyd;

if ( !defined( 'ABSPATH' ) ) exit;

if ( !isset( $_current_main_theme ) ) {
	$_current_main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
	if ( !$_current_main_theme ) {
		$_current_main_theme = wp_get_theme();
	}
}

/* check theme version to decide if greyd-plugin should be loaded */
// echo "greyd-plugin init<br>";
$init = true;
if ( $_current_main_theme->stylesheet == 'greyd-theme' || $_current_main_theme->stylesheet == 'greyd_suite' ) {
    // if ( version_compare( $_current_main_theme->get('Version'), '2.0', '<' ) ) {
    //     $init = false;
    // }
    if ( $_current_main_theme->stylesheet == 'greyd-theme' && version_compare( $_current_main_theme->get('Version'), '2.0', '<' ) ) {
        $init = false;
    }
    if ( $_current_main_theme->stylesheet == 'greyd_suite' && version_compare( $_current_main_theme->get('Version'), '1.20.0', '<' ) ) {
        $init = false;
    }
}
if ( !$init ) {
    // echo "greyd-plugin abort (theme < 2.0)<br>";
    return;
}
// echo "greyd-plugin loading<br>";

/* check active plugins for old greyd_tp_management < 1.19 */
$plugins = get_option('active_plugins');
if ( is_multisite() ) {
    $plugins_multi = get_site_option('active_sitewide_plugins');
    $plugins = array_merge($plugins, array_keys($plugins_multi));
    $plugins = array_unique($plugins);
    sort($plugins);
}
if ( in_array('greyd_tp_management/init.php', $plugins) ) {
    // check the version
    if ( !function_exists( 'get_plugin_data' ) ) require_once ABSPATH.'wp-admin/includes/plugin.php';
    $plugin_data = get_plugin_data( WP_PLUGIN_DIR."/greyd_tp_management/init.php", false, false );
    $hub_version = $plugin_data["Version"];
    if ( version_compare( $hub_version, '1.19', '<' ) ) {
        // echo "greyd-plugin abort (tp_management < 1.19)<br>";
        return;
    }
	if ( !in_array('greyd-plugin/init.php', $plugins) ) {
		// echo "greyd-plugin abort (not yet active)<br>";
		return;
	}
}
// echo "greyd-plugin loading finally<br>";


if ( !defined( 'GREYD_VERSION' ) ) {
	define( 'GREYD_VERSION', '2.18.3' );
}

if ( !defined( 'GREYD_PLUGIN_PATH' ) ) {
	define( 'GREYD_PLUGIN_PATH', __DIR__ );
}

if ( !defined( 'GREYD_PLUGIN_FILE' ) ) {
	define( 'GREYD_PLUGIN_FILE', __FILE__ );
}

if ( !defined( 'GREYD_PLUGIN_URL' ) ) {
	define( 'GREYD_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( !defined( 'GREYD_UPDATE_URL' ) ) {
	define( 'GREYD_UPDATE_URL', 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd-plugin/metadata.json' );
}

if ( !defined( 'GREYD_UPDATE_ZIP' ) ) {
	define( 'GREYD_UPDATE_ZIP', 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd-plugin/greyd-plugin.zip' );
}

if ( !defined('GREYD_REST_NAMESPACE') ) {
	define('GREYD_REST_NAMESPACE', 'greyd/v1');
}

// config vars
$config = array(
    // // current/legacy name
    // 'plugin_name_full' => "Greyd.Hub",
    // 'plugin_name'      => "greyd_tp_management",
    // future name
    'plugin_name_full' => "Greyd Plugin",
    'plugin_name'      => "greyd-plugin",
    'plugin_file'      => GREYD_PLUGIN_FILE,
    'update_file'      => GREYD_UPDATE_URL,
    'plugin_path'      => GREYD_PLUGIN_PATH,
    'homepage'         => 'https://greyd.io/',
    'pricing'          => 'https://greyd.io/pricing/',
    'helpcenter'       => 'https://docs.greyd.io/',
    'resources'        => 'https://greyd-resources.de/en/',
    'required_versions' => array(
        'greyd_forms' => '2.0',
    ),
    'is_greyd'          => strpos( $_current_main_theme->get('stylesheet'), "greyd" ) !== false,
    'is_greyd_classic'  => strpos( $_current_main_theme->get('Name'), "GREYD.SUITE" ) !== false
);

// add version infos to 'greyd_versions' filter
add_filter( 'greyd_versions', function($versions) use($config) {
    if ( !function_exists('get_plugin_data') ) require_once ABSPATH.'wp-admin/includes/plugin.php';

    $plugin_data = get_plugin_data( $config['plugin_file'], false, false );
    $versions['greyd_hub'] = array(
        'version' => $plugin_data['Version'],
        'required' => $config['required_versions']
    );

    // also add gutenberg version
    if (Helper::is_active_plugin('gutenberg/gutenberg.php')) {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR."/gutenberg/gutenberg.php", false, false );
        $versions["gutenberg"] = array(
            'version' => $plugin_data['Version'],
        );
    }
    // and wp version
    global $wp_version;
    $versions['wp'] = array(
        'version' => $wp_version
    );

    return $versions;
} );

// core includes
require_once __DIR__.'/inc/helper.php';
require_once __DIR__.'/inc/manage.php';
require_once __DIR__.'/inc/admin.php';
require_once __DIR__.'/inc/enqueue.php';
require_once __DIR__.'/inc/ajax.php';
require_once __DIR__.'/inc/settings.php';
require_once __DIR__.'/inc/features/init.php';
require_once __DIR__.'/inc/dashboard/dashboard.php';
require_once __DIR__.'/inc/installer/installer.php';
require_once __DIR__.'/inc/license/license-admin-page.php';
require_once __DIR__.'/inc/wizard/wizard.php';
require_once __DIR__.'/inc/classic-theme-converter/init.php';

// public functions
require_once __DIR__.'/functions.php';

// deprecated
require_once __DIR__.'/inc/deprecated/ajax_handler.php';
require_once __DIR__.'/inc/deprecated/default_content.php';
require_once __DIR__.'/inc/deprecated/install-wizard/wizard.php';
require_once __DIR__.'/inc/deprecated/dashboard/dashboard.php';
// interim
require_once __DIR__.'/inc/deprecated/misc.php';
require_once __DIR__.'/inc/deprecated/settings-dummy.php';
require_once __DIR__.'/inc/deprecated/installer.php';
