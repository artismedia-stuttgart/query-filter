<?php
/*
Plugin Name:    Greyd Installer
Description:    Installation for the entire Greyd SUITE
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        1.0
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Forced:         true
*/

namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Deprecated_Installer( $config );

class Deprecated_Installer {

	/**
	 * Name of the transient that holds the activation status.
	 */
	const TRANSIENT_NAME = 'enable_greyd_hub';

	/**
	 * Holds the plugin config.
	 *
	 * @var object.
	 */
	public $config;

	/**
	 * Constructor
	 *
	 * @param object $config
	 */
	public function __construct( $config ) {

		// set config
		$this->config = (object) $config;

		// handle activation and deactivation
		register_activation_hook( $this->config->plugin_file, array( $this, 'greyd_activate' ) );
		register_deactivation_hook( $this->config->plugin_file, array( $this, 'greyd_deactivate' ) );
		
		add_action( 'greyd_suite_activated', array( $this, 'greyd_suite_activated' ) );
	}

	/**
	 * Handle plugin activation
	 */
	public function greyd_activate() {

		if ( ! Helper::is_greyd_classic() ) {
			return;
		}

		$my_theme = wp_get_theme();
		$name     = $my_theme->get( 'Name' );

		if ( strpos( $name, 'Greyd.' ) === false && strpos( $name, 'GREYD' ) === false ) {
			// only activate plugin for Greyd themes
			die( sprintf( __( "You are not allowed to activate the %s plugin.", 'greyd_hub' ), $this->config->plugin_name_full ) );
		} else {
			// show activation message
			set_transient( self::TRANSIENT_NAME, true, 5 );
		}
	}

	/**
	 * Display admin notices for activation and check if this plugin needs an update.
	 */
	public function display_admin_notices() {

		// show activation notice
		if ( get_transient( self::TRANSIENT_NAME ) ) {
			delete_transient( self::TRANSIENT_NAME );

			// do initial setup (deprecated)
			do_action( 'greyd_suite_activated' );
			// do initial setup (new action)
			do_action( 'greyd_plugin_activated' );
		}
	}

	/**
	 * Action after activation or after the Greyd.Suite is activated.
	 *
	 * called via @action 'greyd_suite_activated'
	 */
	public static function greyd_suite_activated() {

		if ( ! Helper::is_greyd_classic() ) {
			return;
		}

		// don't call via network page
		if ( is_network_admin() ) {
			return;
		}

		// don't call inside the wizard to prevent an endless loop
		if ( isset( $_GET['wizzard'] ) || isset( $_GET['wizard'] ) ) {
			return false;
		}

		// don't call via hub-ajax
		if ( isset( $_POST['mode'] ) ) {
			return false;
		}

		// disable wpb backend redirect and editor notice
		delete_transient( '_vc_page_welcome_redirect' );
		$dismissed = array( 'vc_pointers_backend_editor', 'plugin_editor_notice' );
		$users     = get_users();
		foreach ( $users as $user ) {
			$meta          = get_user_meta( $user->ID );
			$old_dismissed = explode( ',', $meta['dismissed_wp_pointers'][0] );
			$new_dismissed = implode( ',', array_filter( array_unique( array_merge( $old_dismissed, $dismissed ) ) ) );
			update_user_meta( $user->ID, 'dismissed_wp_pointers', $new_dismissed );
		}
		// delete_transient( 'enable_greyd_tp_management' );
		delete_transient( self::TRANSIENT_NAME );

		// Start the wizard
		update_option( 'greyd_installed', '1' );
		wp_redirect( 'admin.php?page=greyd_dashboard&wizard=install' );
		exit();
	}

	/**
	 * Handle plugin deactivation
	 */
	public function greyd_deactivate() {
		// cleanup after (deprecated)
		do_action( 'greyd_hub_deactivated' );
		// cleanup after (new action)
		do_action( 'greyd_plugin_deactivated' );
	}
}
