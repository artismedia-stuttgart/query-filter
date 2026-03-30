<?php
/**
 * Greyd.Hub main admin class.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Admin($config);
class Admin {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Hub config.
	 * 
	 * @var bool
	 */
	public static $is_standalone = false;
	public static $is_global = false;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	public static $urls = array();
	public static $clean = array();
	public static $connections = false;

	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;
		if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
			// standalone mode
			self::$is_standalone = true;
		}
		else if (is_multisite()) {
			if (class_exists('Greyd\Plugin\Features')) {
				// in plugin mode
				$features = \Greyd\Plugin\Features::get_saved_features();
				// debug($features);
				if (isset($features['global']['network-hub'])) {
					// globally enabled
					/**
					 * dev mode: hub is always local
					 */
					// self::$is_global = true;
				}
			}
			else {
			// in hub mode
				self::$is_global = true;
			}
		}

		// init class vars and connections
		add_action( 'init', array($this, 'init'), 0 );

		// protect site in frontend
		add_filter( 'site_details', array($this, 'add_site_details') );
		add_action( 'wp', array($this, 'protect_site'), 0 );

		// handle post data (very early before the admin area is rendered to render clean logs)
		add_action( 'admin_head', array($this, 'handle_data') );
		// tbd: also possible in footer - lets the hub render in background
		// add_action( 'admin_footer', array($this, 'handle_data') );

		// backend scripts
		add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts') );
		// website previews in hub
		add_action( 'parse_request', array($this, 'maybe_hide_admin_bar'), 1 );
		// notice after new site is registered
		add_action( 'network_site_new_form', array($this, 'site_new_notice') );

		if (self::$is_standalone) {
			// standalone
			add_action( 'admin_menu', array($this, 'standalone_submenu'), 40 );
		}
		else {
			// in hub
			// add hooks for ajax handling
			add_action( 'greyd_ajax_mode_change_bloginfo',  array( $this, 'change_bloginfo' ) );
			add_action( 'greyd_ajax_mode_upload_part',  array( $this, 'upload_part' ) );

			// add menu and pages
			add_filter( 'greyd_menu_main_network', array($this, 'add_greyd_menu_main_network') );
			add_filter( 'greyd_submenu_pages', array($this, 'add_greyd_submenu_page') );
			add_filter( 'greyd_admin_bar_group', array($this, 'add_greyd_admin_bar_group_item') );
			add_filter( 'greyd_admin_bar_items_network', array($this, 'add_greyd_admin_bar_items_network_item') );
			add_filter( 'greyd_dashboard_active_panels', array($this, 'add_greyd_dashboard_panel') );
			add_filter( 'greyd_dashboard_panels', array($this, 'add_greyd_classic_dashboard_panel') ); // for classic
		}

	}

	/**
	 * Set the static class vars and get the connections
	 */
	public function init() {

		// define page details
		self::$page = apply_filters( 'greyd_hub_admin_page', array(
			'slug'      => "greyd_hub",
			'title'     => __('Greyd.Hub', 'greyd_hub'),
			'url'       => !self::$is_standalone && self::$is_global ? network_admin_url('admin.php?page=greyd_hub') : admin_url('admin.php?page=greyd_hub'),
			'cap'       => !self::$is_standalone && self::$is_global ? 'manage_sites' : 'install_plugins',
			'callback'  => array( $this, 'hub_page' ),
		) );
		// debug(self::$page);

		// set urls
		self::$urls = (object)array(
			// make urls
			"network_url"      => str_replace( array("https://", "http://"), "", network_site_url() ),
			"backup_path_abs"  => trailingslashit( content_url() )."backup/",
			"plugins_path_abs" => trailingslashit( plugins_url() ),
			// make urls based on dirs
			"wp_folder"        => wp_normalize_path( untrailingslashit(ABSPATH) ), // full wp
			"backup_folder"    => wp_normalize_path( untrailingslashit(ABSPATH) ), // full wp
			"uploads_folder"   => wp_normalize_path( wp_upload_dir()['basedir'] ),
			"backup_path"      => wp_normalize_path( trailingslashit( WP_CONTENT_DIR )."backup/" ),
			"plugins_path"     => wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) ),
			"themes_path"      => wp_normalize_path( trailingslashit( get_theme_root() ) ),
			// this plugin
			"plugin_path"      => wp_normalize_path( $this->config->plugin_path ),
		);

		// set clean data
		self::$clean = (object)array(
			"domain" => "greydsuite.de",
			"admin" => "info@greyd.io",
			"prefix" => "wp_",
		);

		// Get the connetions
		if ( ! method_exists( '\Greyd\Connections\Connections_Helper', 'get_connections' ) ) {
			// no connections helper
			return;
		}

		$connections = \Greyd\Connections\Connections_Helper::get_connections();

		if ($connections && count($connections) > 0) {
			// debug($connections);
			self::$connections = $connections;
			// test if using_application_passwords is enabled.
			// this can be turned off after import of db files.
			$test = get_option('using_application_passwords', false);
			if (!$test) update_option('using_application_passwords', true);
		}
	}


	/**
	 * Add site settings to site details.
	 * to get property use: get_blog_details($blogid)->__get($key)
	 * @see filter 'site_details'
	 */
	public function add_site_details($details) {

		if (is_multisite()) switch_to_blog($details->blog_id);

		$details->protected = get_option('protected', '0');
		$details->staging = get_option('greyd_staging', '0');

		if (is_multisite()) restore_current_blog();

		// debug($details);
		return $details;
	}

	/**
	 * Protect site in frontend.
	 * Uses option 'protected'
	 *  '0': not protected
	 *  '1': only logged in user
	 *  '2': only admins
	 *  '3::{pw_hash}': with password (uses cookie 'greyd_access')
	 * If access is denied, custom access page is rendered.
	 * @see action 'wp'
	 */
	public function protect_site() {

		if (
			is_admin() || 
			is_customize_preview() ||
			isset($_GET['popup_preview']) ||
			isset($_GET['hide_admin_bar'])
		) return;

		$protected = get_option('protected', '0');
		$show = true;
		$error = false;
		// debug($protected);

		if ( $protected == '0' ) {
			// not protected
			return;
		}
		else if ( $protected == '1' ) {
			// only logged in user
			if ( is_user_logged_in() ) {
				$show = false;
			}
		}
		else if ( $protected == '2' ) {
			// only admin
			if ( is_user_logged_in() && current_user_can('administrator') ) {
				$show = false;
			}
		}
		else if ( strpos( $protected, '3::' ) === 0 ) {
			// with pass
			$tmp = explode( '::', $protected, 2 );
			$protected = $tmp[0];
			$value = $tmp[1];
			// saved value is already hashed
			// $value = wp_hash( $value.'blogaccess' );

			// check cookie
			if ( isset( $_COOKIE['greyd_access'] ) && $_COOKIE['greyd_access'] == $value ) {
				// debug($_COOKIE);
				$show = false;
			}
			// check input
			else if ( isset($_POST["pwd"]) ) {
				// debug($_POST);
				global $current_blog;
				// check nonce
				$nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : null;
				if ( $nonce && wp_verify_nonce($nonce, "greyd_site_access") ) {
					// verify pass
					$pass = wp_hash( trim($_POST["pwd"]).'blogaccess' );
					// debug($value);
					// debug($pass);
					if ( $value == $pass ) {
						setcookie( 'greyd_access', $value, time() + 1800, $current_blog->path );
						$show = false;
					}
					else $error = true;
				}
			}
		}

		// render access page instead of wp
		if ($show) {

			header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' ); // Date in the past
			header( 'Pragma: no-cache' );
			?>
			<!DOCTYPE html>
			<html>
				<head>
					<title><?php echo __("Access denied", 'greyd_hub'); ?></title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<link rel="stylesheet" id="login-css" href="/wp-admin/css/login.min.css?ver=<?php bloginfo('version'); ?>" type="text/css" media="all">
					<link rel="stylesheet" id="login-css" href="/wp-admin/css/forms.min.css?ver=<?php bloginfo('version'); ?>" type="text/css" media="all">
					<link rel="stylesheet" id="login-css" href="/wp-includes/css/buttons.min.css?ver=<?php bloginfo('version'); ?>" type="text/css" media="all">
				</head>
				<body class="login wp-core-ui">
					<div id="login">

						<!-- error message -->
						<?php if ($error) { ?>
							<div id="login_error"><?php echo __("Password not correct.", 'greyd_hub'); ?></div>
						<?php } ?>

						<!-- access message -->
						<form name="loginform" id="loginform" method="post">

							<h2><?php echo __("Access denied", 'greyd_hub'); ?></h2>
							<?php if ($protected == '1') { ?>
								<p><?php echo __("This website can only be viewed by logged-in users.", 'greyd_hub'); ?></p>
							<?php } else if ($protected == '2') { ?>
								<p><?php echo __("This page can be viewed only by admins.", 'greyd_hub'); ?></p>
							<?php } else if ($protected == '3') { ?>
								<p><?php echo __("This site can be viewed only with a password.", 'greyd_hub'); ?></p>
								<!-- pw form -->
								<div class="user-pass-wrap">
									<div class="wp-pwd">
										<input type="password" name="pwd" id="user_pass" class="input password-input" value="" size="20" autocomplete="off" spellcheck="false">
									</div>
								</div>
								<p class="submit">
									<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In">
									<?php wp_nonce_field("greyd_site_access") ?>
								</p>
							<?php } ?>

						</form>
					</div>
				</body>
			</html>
			<?php

			exit();
		}

	}


	/*
	=======================================================================
		admin
	=======================================================================
	*/

	/**
	 * add hub scripts
	 */
	public function load_backend_scripts() {

		if ( isset( $_GET['page'] ) && $_GET['page'] === self::$page['slug'] ) {
			// get info
			$css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
			$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

			/**
			 * standalone mode (without greyd_hub)
			 * 'greyd-admin-style' and 'greyd-admin-script'
			 * are the basic admin scripts, usually provided by the greyd_hub.
			 * If this feature runs without greyd_hub, these scripts are not registered,
			 * so they are added here as fallback.
			 */
			global $wp_styles;
			global $wp_scripts;

			if ( ! isset( $wp_styles->registered['greyd-admin-style'] ) ) {
				// style
				wp_register_style(
					'greyd-admin-style',
					$css_uri . '/admin-style.css',
					null,
					GREYD_VERSION,
					'all'
				);
				wp_enqueue_style( 'greyd-admin-style' );
			}

			if ( ! isset( $wp_scripts->registered[ 'greyd-admin-script' ] ) ) {

				// script
				wp_register_script(
					'greyd-admin-script',
					$js_uri . '/admin-script.js',
					array( 'wp-data', 'jquery' ),
					GREYD_VERSION,
					true
				);
				wp_enqueue_script( 'greyd-admin-script' );

				// inline script before
				// define global greyd var
				wp_add_inline_script(
					'greyd-admin-script',
					'if (typeof greyd === "undefined") var greyd = {}; greyd = {'.
						'upload_url:   "' . wp_upload_dir()['baseurl'] . '",'.
						'ajax_url:     "' . admin_url( 'admin-ajax.php' ) . '",'.
						'nonce:        "' . wp_create_nonce( 'install' ) . '",'.
						'is_multisite: "' . ( is_multisite() ? 'true' : 'false' ) . '",'.
						'...greyd'.
					'};',
					'before'
				);
			}

			// css
			wp_register_style(
				'greyd-hub-style',
				$css_uri . '/hub.css',
				null,
				GREYD_VERSION,
				'all'
			);
			wp_enqueue_style( 'greyd-hub-style' );

			// add js
			wp_enqueue_media();
			wp_register_script(
				'greyd-hub-script',
				$js_uri . '/hub.js',
				array( 'jquery', 'greyd-admin-script' ),
				GREYD_VERSION
			);
			wp_enqueue_script( 'greyd-hub-script' );
			wp_add_inline_script(
				'greyd-hub-script',
				'if (typeof greyd === "undefined") var greyd = {};', 'before'
			);
		}

	}

	/**
	 * Hide the admin bar when website thumbnail is shown in hub preview
	 */
	public function maybe_hide_admin_bar( $query ) {
		if ( isset($_GET['hide_admin_bar']) && $_GET['hide_admin_bar'] === 'true' ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/**
	 * New Site Registered notice
	 */
	public function site_new_notice() {
		if ( !isset($_GET) || !isset($_GET['id']) ) return false;

		Helper::show_message(
			sprintf(
				__("Would you like to work with Greyd.Suite on your new site? Then open %sGreyd.Hub%s and activate Greyd.Suite!", 'greyd_hub'),
				'<a href="'.network_admin_url('admin.php?page=greyd_hub').'">',
				'</a>'
		   )
		);
	}

	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add a standalone submenu item to general settings if Hub is not installed
	 */
	public function standalone_submenu() {
		add_submenu_page(
			'options-general.php', // parent slug
			self::$page['title'],  // page title
			self::$page['title'], // menu title
			self::$page['cap'], // capability
			self::$page['slug'], // slug
			self::$page['callback'], // function
			80 // position
		);
	}

	/**
	 * Change main menu item Greyd.Suite network-admin
	 * @see filter 'greyd_menu_main_network'
	 */
	public function add_greyd_menu_main_network($menu) {
		// debug($menu);

		$menu = [
			'slug'      => self::$page['slug'],
			'title'     => self::$page['title'],
			'cap'       => self::$page['cap'],
			'callback'  => self::$page['callback']
		];

		return $menu;
	}

	/**
	 * Add the submenu item to Greyd.Suite
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page($submenu_pages) {
		// debug($submenu_pages);

		if ( !is_multisite() || ( is_multisite() && is_super_admin() ) ) {
			array_push($submenu_pages, array(
				'title'     => self::$page['title'],
				'cap'       => self::$page['cap'],
				'slug'      => !self::$is_standalone && self::$is_global ? self::$page['url']  : self::$page['slug'],
				'callback'  => !self::$is_standalone && self::$is_global ? ''                  : self::$page['callback'],
				'position'  => 99
			));
		}

		return $submenu_pages;
	}

	/**
	 * Add item to Greyd.Suite adminbar group.
	 * @see filter 'greyd_admin_bar_group'
	 */
	public function add_greyd_admin_bar_group_item($items) {
		// debug($items);

		// in frontend
		if (!is_admin()) {

			if ( current_user_can(self::$page['cap']) ) {
				array_push($items, array(
					'parent' => "greyd_toolbar",
					'id'     => self::$page['slug'],
					'title'  => self::$page['title'],
					'href'   => self::$page['url'],
					'priority' => 99
				));
			}

		}

		return $items;
	}

	/**
	 * Add item to Greyd.Suite network-admin adminbar.
	 * @see filter 'greyd_admin_bar_items_network'
	 */
	public function add_greyd_admin_bar_items_network_item($items) {
		// debug($items);

		if ( current_user_can(self::$page['cap']) ) {
			array_push($items, array(
				'parent' => "network-admin",
				'id'     => "network_hub",
				'title'  => self::$page['title'],
				'href'   => self::$page['url'],
				'priority' => 1
			));
		}

		return $items;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_dashboard_panel($panels) {
		// debug($panels);

		// check for database errors after import
		global $wpdb;
		// debug($wpdb);
		$db_errors = Hub_Helper::check_db_errors(array(
			'prefix' => $wpdb->base_prefix,
			'id' => $wpdb->blogid,
			'tables' => array( "`".$wpdb->options."`", "`".$wpdb->postmeta."`" ),
		));
		if ($db_errors != "") echo '<div class="notice notice-info">'.$db_errors.'</div>';

		// add panel
		$panels[ 'greyd-hub' ] = true;

		return $panels;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_classic_dashboard_panel($panels) {
		// debug($panels);

		// check for database errors after import
		global $wpdb;
		// debug($wpdb);
		$db_errors = Hub_Helper::check_db_errors(array(
			'prefix' => $wpdb->base_prefix,
			'id' => $wpdb->blogid,
			'tables' => array( "`".$wpdb->options."`", "`".$wpdb->postmeta."`" ),
		));
		if ($db_errors != "") echo '<div class="notice notice-info">'.$db_errors.'</div>';

		// add panel
		$panels['hub'] = array(
			'icon'  => 'hub',
			'title' => self::$page['title'],
			'descr' => __("Manage all websites and content of your installation.", 'greyd_hub'),
			'btn'   => array(
				array( 
					'text' => __("Open Greyd.Hub", 'greyd_hub'), 
					'url' => self::$page['url']
				)
			),
			'cap'   => self::$page['cap'],
			'priority' => 98,
		);

		return $panels;
	}


	/*
	=======================================================================
		ajax
	=======================================================================
	*/

	/**
	 * Ajax function to Change Website Info.
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $post_data   $_POST data.
	 */
	public function change_bloginfo( $post_data ) {

		$name   = isset($post_data['name']) ? $post_data['name'] : '';
		$value  = isset($post_data['value']) ? $post_data['value'] : '';
		$blogid = isset($post_data['blogid']) ? $post_data['blogid'] : '';

		$response = $this->set_bloginfo( $name, $value, $blogid );

		echo "\r\n\r\n"."------------- debug end -------------"."\r\n\r\n";

		// return
		if ($response === false) 
			echo "error::";
		else
			echo "success::";

		// end ajax request
		die();

	}

	/**
	 * Set bloginfo
	 *  
	 * @return bool true | false
	 */
	public function set_bloginfo($option='', $value='', $blogid='', $debug = true) {

		if ($debug) echo "\r\n\r\n"."CHANGE BLOGINFO";

		if ( empty($option) || ( is_multisite() && empty($blogid) ) ) {
			if ($debug) echo "\r\n\r\n"."not all necessary options set --> aborted";
			return false;
		}

		$name   = strval( esc_attr( trim( $option ) ) );
		$value  = strval( esc_attr( trim( $value ) ) );
		$blogid = esc_attr( trim( $blogid ) );

		// invalid name
		$valid = array( 'blogname', 'blogdescription', 'protected', 'public', 'archived', 'mature', 'spam', 'deleted' );
		if ( !in_array( $name, $valid ) ) {
			if ($debug) echo "\r\n\r\n"."invalid option '$name'";
			return false;
		}

		// adjust option name
		if ( $name == 'public' ) $name = 'blog_public';

		// maxlengths
		if ( $name === 'blogname' && strlen($value) > 41 ) {
			$value = substr( $value, 0, 41 );
		}
		else if ( $name === 'blogdescription' && strlen($value) > 255 ) {
			$value = substr( $value, 0, 255 );
		}

		// save hashed password
		if ($name == 'protected' && strpos( $value, '3::' ) === 0) {
			$tmp = explode( '::', $value, 2 );
			$value = $tmp[0].'::'.wp_hash( $tmp[1].'blogaccess' );
		}

		if (is_multisite()) switch_to_blog($blogid);

		// update the option
		if ($debug) echo "\r\n\r\n"."changing '$name' to '$value'...";
		$option = array( 'blogname', 'blogdescription', 'protected', 'blog_public' );
		if ( in_array( $name, $option ) )
			$response = update_option($name, $value);
		else
			$response = update_blog_status($blogid, $name, $value);

		if (is_multisite()) restore_current_blog();

		if ($debug)  {
			if ($response) echo "\r\n\r\n"."option '$name' updated to '$value'";
			else echo "\r\n\r\n"."unable to update option '$name'";
		}
		return $response;
	}


	/**
	 * Ajax function to upload a file chunk.
	 * @see action 'greyd_ajax_mode_'
	 * 
	 * @param array $post_data   $_POST data.
	 */
	public function upload_part( $post_data ) {

		// decode data
		$post_data = json_decode(rawurldecode($post_data), true);
		if (json_last_error() != 0) {
			// debug(json_last_error_msg());
			// debug(json_last_error());
			echo "error::JSON ".json_last_error_msg();
			die();
		}

		// check chunk
		if (!isset($_FILES) || !isset($_FILES['chunk'])) {
			echo "error::Chunk not set";
			die();
		}

		// debug
		echo "\r\n\r\n"." uploading chunk ... "."\r\n\r\n";
		$return = "";

		// path
		$import_path = wp_normalize_path( trailingslashit( WP_CONTENT_DIR )."backup/import/" );
		if (!file_exists($import_path)) mkdir($import_path, 0755, true);
		$tmp_path = wp_normalize_path( trailingslashit( WP_CONTENT_DIR )."backup/temp/" );
		if (!file_exists($tmp_path)) mkdir($tmp_path, 0755, true);

		// file
		$tmp_name = "chunks_".md5($import_path.$post_data["final_name"]);
		if ($post_data["chunk_number"] == 1 && file_exists($tmp_path.$tmp_name)) {
			// delete old fragemnts of the same file
			unlink($tmp_path.$tmp_name);
		}

		// check abort flag
		if (isset($post_data["abort"]) && $post_data["abort"] == "true") {
			// delete tmp file and abort
			unlink($tmp_path.$tmp_name);
			echo "abort::";
			die();
		}
		else {
			// get chunk data
			// debug($_FILES);
			$fileData = Helper::get_file_contents($_FILES['chunk']['tmp_name']);

			// write chunk
			$handle = fopen($tmp_path.$tmp_name, 'a');
			fwrite($handle, $fileData);
			fclose($handle);
			$return = $tmp_name;

			// check chunks
			if ($post_data["chunk_number"] == $post_data["number_of_chunks"]) {
				// final chunk: rename and return full path
				rename($tmp_path.$tmp_name, $import_path.$post_data["final_name"]);
				$return = $import_path.$post_data["final_name"];
				// cleanup
				Hub_Helper::delete_directory($tmp_path);
			}
		}

		echo $return;
		echo "\r\n\r\n"."------------- debug end -------------"."\r\n\r\n";

		// return
		if ($return == "") 
			echo "error::";
		else {
			if (strpos($return, '<pre>') !== false) {
				// remove debugs
				$return = preg_replace('/<pre>(.|\n)*<\/pre>/', '', $return);
			}
			echo "success::".$return;
		}

		// end ajax request
		die();

	}


	/*
	=======================================================================
		Render Hub
	=======================================================================
	*/

	/**
	 * handle POST Data.
	 */
	public function handle_data() {


		// delete unused tables
		if ( isset( $_GET['delete_all_unknown_tables'] ) && $_GET['delete_all_unknown_tables'] == 'true' ) {
			Tools::delete_all_unknown_tables();
		}

		// only on hub page
		if ( isset($_GET['page']) && $_GET['page'] === self::$page['slug'] ) {

			// handle POST Data
			if ( !empty($_POST) && isset($_POST['submit']) ) {

				// check nonce
				$nonce_action = "greyd_hub";
				$nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : null;

				if ( $nonce && ( wp_verify_nonce($nonce, $nonce_action) || $nonce == "remote") ) {

					/**
					 * Handle the $_POST data and trigger actions on Hub Pages
					 * @action greyd_hub_handle_post_data
					 * 
					 * @param string $post_data   Raw $_POST data.
					 */
					do_action( 'greyd_hub_handle_post_data', $_POST );

				}
				else {

					// nonce error
					Helper::show_message(__("Operation could not be executed.", 'greyd_hub'), 'warning');

				}

			}
			else {
	
				/**
				 * Check whether this is a POST request.
				 */
				$is_post_request = strtolower( filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?? '' ) === 'post';
				if ( $is_post_request ) {
	
					/**
					 * Check if the POST Content-Length exceeds the limit.
					 * 
					 * @since 1.2.7
					 */
					$post_size = isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 0;
					if ( $post_size > wp_max_upload_size() && 
							empty($_POST) && 
						( !isset($_FILES) || empty($_FILES) ) ) {
						// add error overlay
						Log::$overlay  = array(
							"show"      => true,
							"action"    => 'fail',
							"class"     => 'upload',
							"replace"   => sprintf(
								__("The uploaded file (%s) exceeds the maximum upload limit of the server (%s). The limit is defined in the <u>php.ini</u> file.", 'greyd_hub'),
								Hub_Helper::format_bytes( $post_size ),
								Hub_Helper::format_bytes( wp_max_upload_size() )
							)
						);
					}
				}
	
			}

		}

	}

	/**
	 * Render Hub Page depending on the tab.
	 */
	public function hub_page() {

		// get the current tab
		$current_tab = !empty($_GET["tab"]) ? $_GET["tab"] : "websites";

		// make nonce without id to avoid dom errors/warnings
		$nonce = str_replace( 'id="_wpnonce"', '', wp_nonce_field("greyd_hub") );

		/**
		 * Hub pages
		 * 
		 * @filter greyd_hub_pages
		 * 
		 * @param array  $hub_pages  All hub pages as array.
		 *      @property string slug       Slug of the tab (set as url param).
		 *      @property string icon       Dashicon slug (without 'dashicons-').
		 *      @property string class      CSS class name.
		 *      @property string title      Title of the tab.
		 *      @property callback title_cb Title Actions Callback function to render before the tabs. (optional)
		 *      @property array title_args  Arguments to apply to the titlecallback. (optional)
		 *      @property callback callback Callback function to render the tabs content.
		 *      @property array args        Arguments to apply to the callback.
		 *      @property int priority      position of the tab (optional, 0: first, 99: last, defaults to 10)
		 * @param string $current_tab   Current tab
		 */
		$hub_pages = apply_filters( "greyd_hub_pages", array(
			// websites
			// backups
			// database
		), $current_tab );

		// sort hub pages by priority
		usort($hub_pages, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});

		/**
		 * Render the page
		 */

		/**
		 * Additional content before the page wrapper. (e.g. wizard overlay)
		 * @action greyd_hub_page_before_wrapper
		 * 
		 * @param string $current_tab   Current tab
		 * @param string $nonce         nonce field
		 */
		do_action( 'greyd_hub_page_before_wrapper', $current_tab, $nonce );

		// render page wrapper
		echo "<div class='wrap'>";

		// render title headline
		echo "<h1 class='wp-heading-inline'>".self::$page['title']."</h1>";

		/**
		 * Additional content after the title headline. (e.g. migration wizard button)
		 * @action greyd_hub_page_after_title
		 * 
		 * @param string $current_tab   Current tab
		 * @param string $nonce         nonce field
		 */
		do_action( 'greyd_hub_page_after_title', $current_tab, $nonce );

		// render title actions
		foreach( $hub_pages as $hub_page ) {
			$hub_page = wp_parse_args( $hub_page, array(
				"slug"       => "",
				"title_cb"   => null,
				"title_args" => array()
			) );
			if ( $current_tab == $hub_page['slug'] && $hub_page['title_cb'] ) {
				call_user_func_array( $hub_page["title_cb"], $hub_page["title_args"] );
			}
		}

		/**
		 * Additional content after the title actions.
		 * @action greyd_hub_page_after_title_actions
		 * 
		 * @param string $current_tab   Current tab
		 * @param string $nonce         nonce field
		 */
		do_action( 'greyd_hub_page_after_title_actions', $current_tab, $nonce );

		// render the tabs
		echo "<div class='greyd_tabs'>";
		foreach( $hub_pages as $hub_page ) {
			$hub_page = wp_parse_args( $hub_page, array(
				"slug"      => "",
				"icon"      => "screenoptions",
				"title"     => "Tab",
				"class"     => "",
			) );
			$tab_link = self::$page['url']."&tab=".$hub_page['slug'];
			$tab_classes = array( "tab" );
			if (!empty($hub_page['class'])) $tab_classes[] = $hub_page['class'];
			if ($current_tab == $hub_page['slug']) $tab_classes[] = "active";
			echo "<a href='".$tab_link."' class='".implode(' ', $tab_classes)."'>".Helper::render_dashicon( $hub_page['icon'] ).$hub_page['title']."</a>";
		}
		echo "</div>";

		/**
		 * Additional content after the tabs.
		 * @action greyd_hub_page_after_tabs
		 * 
		 * @param string $current_tab   Current tab
		 * @param string $nonce         nonce field
		 */
		do_action( 'greyd_hub_page_after_tabs', $current_tab, $nonce );

		// render the content
		foreach( $hub_pages as $hub_page ) {
			$hub_page = wp_parse_args( $hub_page, array(
				"slug"      => "",
				"callback"  => null,
				"args"      => array()
			) );
			if ( $current_tab == $hub_page['slug'] && $hub_page['callback'] ) {
				if (!in_array($nonce, $hub_page["args"])) array_push($hub_page["args"], $nonce);
				call_user_func_array( $hub_page["callback"], $hub_page["args"] );
			}
		}

		/**
		 * Additional content after the content. (e.g. migration wizard overlay)
		 * @action greyd_hub_page_after_content
		 * 
		 * @param string $current_tab   Current tab
		 * @param string $nonce         nonce field
		 */
		do_action( 'greyd_hub_page_after_content', $current_tab, $nonce );

		// end of page wrapper
		echo "</div>"; 
	}

}