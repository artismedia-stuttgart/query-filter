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

if ( !defined( 'ABSPATH' ) ) exit;

new Installer();

class Installer {

	/**
	 * Installer constructor.
	 */
	public function __construct() {

		// redirect to installer after plugin activation
		add_action( 'activated_plugin', array($this, 'after_plugin_activation') );
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_installer' ) );

		// add submenu page
		// add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'greyd_submenu_pages', array( $this, 'add_greyd_submenu_page' ) );

		// add scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_styles' ) );
		add_action( "admin_print_scripts", array( $this, 'add_admin_scripts' ) );

		// add notice
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notice' ) );

		// rest api
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * =================================================================
	 *                          ADMIN PAGES
	 * =================================================================
	 */

	/**
	 * Redirect to installer after plugin activation.
	 */
	public function after_plugin_activation( $plugin ) {

		// return if in admin ajax
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			return;
		}

		if ( defined('REST_REQUEST') && REST_REQUEST ) {
			return;
		}

		if ( $plugin == plugin_basename( GREYD_PLUGIN_FILE ) ) {

			if ( Helper::is_greyd_classic() ) {
				return;
			}

			/**
			 * Set transient to redirect to installer page on
			 * next page load, instead of redirecting directly
			 * after plugin activation.
			 */
			set_transient( 'greyd_installer_redirect', true, 30 );
			// exit( wp_redirect( admin_url( 'admin.php?page=install-greyd' ) ) );
		}
	}

	public function maybe_redirect_to_installer() {

		// return if in admin ajax
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			return;
		}

		if ( defined('REST_REQUEST') && REST_REQUEST ) {
			return;
		}

		// redirect to installer page if transient is set
		if ( get_transient( 'greyd_installer_redirect' ) ) {
			delete_transient( 'greyd_installer_redirect' );
			exit( wp_redirect( admin_url( 'admin.php?page=install-greyd' ) ) );
		}
	}

	/**
	 * Add the submenu item to Greyd.Suite
	 *
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page( $submenu_pages ) {
		// debug($submenu_pages);

		$submenu_pages[] = array(
			'page_title' => esc_html__( 'Greyd Installer', 'greyd_hub' ),
			'menu_title' => esc_html__( 'Installer', 'greyd_hub' ),
			'cap'        => 'manage_options',
			'slug'       => 'install-greyd',
			'callback'   => array( $this, 'render_admin_page' ),
			'position'   => 90
		);

		return $submenu_pages;
	}

	/**
	 * Add admin menu item.
	 * @deprecated use add_greyd_submenu_page instead
	 */
	public function add_menu() {

		if ( Helper::is_greyd_classic() ) {
			return;
		}

		// $admin_suffix = add_theme_page(
		// 	esc_html__( 'Greyd Installer', 'greyd_hub' ),
		// 	esc_html__( 'Greyd Installer', 'greyd_hub' ),
		// 	'manage_options',
		// 	'install-greyd',
		// 	array( $this, 'render_admin_page' )
		// );
		// add_action( "admin_print_scripts-{$admin_suffix}", array( $this, 'add_admin_scripts' ) );

		// $admin_suffix = add_plugins_page(
		// 	esc_html__( 'Greyd Installer', 'greyd_hub' ),
		// 	esc_html__( 'Greyd Installer', 'greyd_hub' ),
		// 	'manage_options',
		// 	'install-greyd',
		// 	array( $this, 'render_admin_page' )
		// );
		// add_action( "admin_print_scripts-{$admin_suffix}", array( $this, 'add_admin_scripts' ) );

		$admin_suffix = add_submenu_page(
			'greyd_dashboard',  // parent slug
			esc_html__( 'Greyd Installer', 'greyd_hub' ),  // page title
			esc_html__( 'Greyd Installer', 'greyd_hub' ), // menu title
			null, //'manage_options',   // capability
			'install-greyd',  // slug
			array( $this, 'render_admin_page' ), // function
			90 // position
		);
		add_action( "admin_print_scripts-{$admin_suffix}", array( $this, 'add_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function add_admin_scripts() {

		if ( ! $this->is_installer_page() ) {
			return;
		}

		wp_enqueue_script( 'greyd-installer-app', GREYD_PLUGIN_URL . '/inc/installer/app.js', array(
			'wp-api',
			'wp-components',
			'wp-plugins',
			'wp-edit-post',
			'wp-edit-site',
			'wp-element',
			'wp-api-fetch',
			'wp-data',
			'wp-i18n',
			'wp-block-editor',
			'lodash',
		), GREYD_VERSION, true );


		$child_theme_active = false;
		if ( Helper::is_greyd_fse() ) {
			// if theme slug is differnet than template slug
			$theme = wp_get_theme();
			$child_theme_active = $theme->template !== $theme->stylesheet;
		}

		$args = array(
			'screen'              => 'settings',
			'version'             => GREYD_VERSION,
			'logo'                => GREYD_PLUGIN_URL . '/assets/img/greyd-logo-dark.svg',
			'links'               => array(
				'greyd_website'       => 'https://greyd.io',
				'greyd_helpcenter'    => 'https://docs.greyd.io/',
				'wp_dashboard'        => esc_url( admin_url( 'admin.php?page=greyd_dashboard' ) ),
				'themes'              => esc_url( admin_url( 'themes.php' ) ),
				'plugins'             => esc_url( admin_url( 'plugins.php' ) ),
				'home'                => esc_url( home_url() ),
				'editor'              => esc_url( admin_url( 'site-editor.php' ) ),
				'license'             => esc_url( admin_url( 'admin.php?page=greyd_settings_license' ) ),
				'features'            => esc_url( admin_url( 'admin.php?page=greyd_features' ) ),
				'site_health'         => esc_url( admin_url( 'site-health.php' ) ),
				'classic_converter'   => empty( get_option( 'theme_mods_greyd_suite', false ) ) ? '' : esc_url( admin_url( 'themes.php?page=greyd-classic-converter' ) ),
			),
			'active_tools' => array(
				'greyd-theme'  => Helper::is_greyd_fse(),
				// 'greyd-blocks' => is_plugin_active( 'greyd_blocks/init.php' ),
				'greyd-forms'  => is_plugin_active( 'greyd_tp_forms/init.php' ),
				// 'gutenberg'	   => is_plugin_active( 'gutenberg/gutenberg.php' ),
				'greyd_globalcontent'  => is_plugin_active( 'greyd_globalcontent/init.php' ),
				'child-theme'  => $child_theme_active,
			),
		);
		
		wp_localize_script( 'greyd-installer-app', 'greyd_installer_args', $args );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-installer-app', 'greyd_hub', GREYD_PLUGIN_PATH . '/languages' );
		}

		// confetti
		wp_enqueue_script( 'confetti-canvas', GREYD_PLUGIN_URL . '/inc/installer/lib/canvas-confetti/confetti.js', array(
			'greyd-installer-app'
		), GREYD_VERSION, true );
		wp_add_inline_script(
			'confetti-canvas',
			'window.module = {};',
			'before'
		);
		wp_add_inline_script(
			'confetti-canvas',
			'window.confetti = module.exports;',
			'after'
		);
	}

	/**
	 * Enqueue admin styles.
	 */
	public function add_admin_styles() {
		if ( $this->is_installer_page() ) {
			wp_enqueue_style( 'greyd-installer-style', GREYD_PLUGIN_URL . '/inc/installer/style.css', array( 'wp-components' ) );
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div id="greyd-installer"></div>
		<?php
	}

	/**
	 * Show admin notice if blocks plugin is not active.
	 */
	public function maybe_show_admin_notice() {

		// blocks plugin is active
		if ( defined( 'GREYD_BLOCKS_VERSION' ) ) {
			return;
		}

		// we are on the installer page
		if ( $this->is_installer_page() ) {
			return;
		}
		
		?>
		<div class="notice notice-warning">
			<div style="padding:12px">
				<p><strong><?php
					_e("Setup incomplete.", "greyd_hub" );
				?></strong></p>
				<p><?php echo sprintf(
					__("Welcome aboard! To unleash the full power of the Greyd.Suite, ensure you've got the %sGreyd Blocks Plugin%s onboard. Simply head over to the Installer and activate the plugin to get started!", "greyd_hub" ),
					'<strong>',
					'</strong>'
				);
				?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=install-greyd' ) ) ?>"><?php echo __("Open installer", "greyd_hub"); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<a style="color: inherit;" href="https://update.greyd.io/<?php echo ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ); ?>/plugins/greyd_blocks/greyd_blocks.zip"><?php echo __("Download plugin", "greyd_hub"); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if current page is installer page.
	 */
	public static function is_installer_page() {
		$screen = get_current_screen();
		return $screen && isset($screen->base) && (
			'appearance_page_install-greyd' === $screen->base
			|| 'plugins_page_install-greyd' === $screen->base
			|| 'greyd-suite_page_install-greyd' === $screen->base
		);
	
	}

	/**
	 * =================================================================
	 *                          REST API
	 * =================================================================
	 */

	/**
	 * Set up Rest API routes.
	 *
	 * @return void
	 */
	public function rest_api_init() {

		register_rest_route( 'greyd/v1/installer', '/tools', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_tools' ],
			'permission_callback' => function () {
				return current_user_can( 'install_plugins' );
			},
		) );

		register_rest_route( 'greyd/v1/installer', '/features', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'save_features' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		register_rest_route( 'greyd/v1/installer', '/create-child-theme', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_child_theme' ],
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	/**
	 * Save tools via Rest API.
	 *
	 * @param object $request given request.
	 *
	 * @return string
	 */
	public function save_tools( $request ) {
		if ( $params = $request->get_params() ) {

			$selectedTools = isset( $params['selectedTools'] ) ? (array) $params['selectedTools'] : array();

			$install_tools = array();

			foreach ( $selectedTools as $key => $value ) {
				if (
					$value === 'greyd-theme'
					|| $value === 'greyd-forms'
					// || $value === 'gutenberg'
					|| $value === 'greyd_globalcontent'
				) {
					$install_tools[] = $value;
				}
			}

			if ( count( $install_tools ) === 0 ) {
				return json_encode( [ "status" => 400, "message" => "No tools selected" ] );
			}
			
			$errors = array();

			if ( ! class_exists( '\Greyd\Plugin_Helper' ) ) {
				require_once __DIR__ . '/plugin-helper.php';
			}

			$plugin_helper = \Greyd\Plugin_Helper::get_instance();

			foreach ( $install_tools as $tool ) {

				if ( strpos( $tool, 'theme' ) !== false ) {

					$result = $plugin_helper::activate_theme( $tool );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result->get_error_message();
					}
				}
				else {
					$result = $plugin_helper::activate_plugin( $tool );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result->get_error_message();
					}
				}
			}

			if ( count( $errors ) > 0 ) {
				return json_encode( [ "status" => 400, "message" => "Could not install tools", "errors" => $errors ] );
			}

			return json_encode( [ "status" => 200, "message" => "Tools installed" ] );
		}

		return json_encode( [ "status" => 400, "message" => "Could not save settings" ] );
	}

	/**
	 * Save features via Rest API.
	 *
	 * @param object $request given request.
	 *
	 * @return string
	 */
	public function save_features( $request ) {
		if ( $params = $request->get_params() ) {

			$selectedFeature = isset( $params['selectedFeature'] ) ? (string) $params['selectedFeature'] : 'default';

			if ( $selectedFeature === 'default' ) {
				$result = delete_option( \Greyd\Features::OPTION );

				if ( ! $result ) {
					// check if option is empty.
					// delete_option() returns null if it was the same value
					if ( !empty( get_option( \Greyd\Features::OPTION ) ) ) {
						return json_encode( [ "status" => 400, "message" => "Could not reset feature settings" ] );
					}
				}
			}
			else if ( $selectedFeature === 'all' ) {

				$features = array();
				$feature_sections = \Greyd\Features::prepare_features();

				foreach ( $feature_sections as $section ) {
					foreach ( $section as $feature ) {
						$features[ $feature['slug'] ] = $feature['include'];
					}
				}
				
				$result = \Greyd\Features::update_features( 'site', $features );

				if ( ! $result ) {
					// check if options are actually saved.
					// update_option() returns null if it was the same value
					$diff = array_diff( $features, \Greyd\Features::get_saved_features()['site'] );
					if ( ! empty( $diff ) ) {
						return json_encode( [ "status" => 400, "message" => "Could not update feature settings" ] );
					}
				}
			}
			else if ( $selectedFeature === 'minimal' ) {

				$features = array(
					'hub' => 'hub/init.php',
					'blocks' => 'blocks/init.php',
				);
				$result = \Greyd\Features::update_features( 'site', $features );

				if ( ! $result ) {
					// check if options are actually saved.
					// update_option() returns null if it was the same value
					$diff = array_diff( $features, \Greyd\Features::get_saved_features()['site'] );
					if ( ! empty( $diff ) ) {
						return json_encode( [ "status" => 400, "message" => "Could not update feature settings" ] );
					}
				}
			}

			return json_encode( [ "status" => 200, "message" => "Ok" ] );
		}

		return json_encode( [ "status" => 400, "message" => "Could not save settings" ] );
	}

	/**
	 * Create child theme via helper method.
	 *
	 * @param object $request given request.
	 *
	 * @return string
	 */
	public function create_child_theme( $request ) {

		if ( ! class_exists( '\Greyd\Plugin_Helper' ) ) {
			require_once __DIR__ . '/plugin-helper.php';
		}
		
		$plugin_helper = \Greyd\Plugin_Helper::get_instance();

		$result = $plugin_helper::create_child_theme();

		if ( is_wp_error( $result ) ) {
			return json_encode( [ "status" => 400, "message" => "Could not create child theme.", "errors" => array( $result->get_error_message() ) ] );
		}

		return json_encode( [ "status" => 200, "message" => "Ok" ] );
	}
}