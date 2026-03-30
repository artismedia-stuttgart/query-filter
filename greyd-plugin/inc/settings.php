<?php
/**
 * The settings page.
 */
namespace Greyd;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Settings($config);
class Settings {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	/**
	 * Constructor
	 */
	public function __construct($config) {
		// set config
		$this->config = (object)$config;

		// define page details
		add_action( 'init', function() {
			self::$page = array(
				'slug'      => 'greyd_settings',
				'title'     => __("Settings", 'greyd_hub'),
				'url'       => is_network_admin() ? network_admin_url('admin.php?page=greyd_settings') : admin_url('admin.php?page=greyd_settings'),
				'cap'       => 'manage_options',
				'callback'  => array( $this, 'settings_page' ),
			);
		}, 0 );

		// add menu and pages
		add_filter( 'greyd_submenu_pages_network', array($this, 'add_greyd_submenu_page_network') );
		add_filter( 'greyd_submenu_pages', array($this, 'add_greyd_submenu_page') );
		add_filter( 'greyd_misc_pages', array($this, 'add_greyd_misc_page') );
		add_filter( 'greyd_dashboard_tabs', array($this, 'add_greyd_dashboard_tab') );

	}


	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add the network submenu item to Greyd.Suite
	 * @see filter 'greyd_submenu_pages_network'
	 */
	public function add_greyd_submenu_page_network($submenu_pages) {
		// debug($submenu_pages);

		array_push($submenu_pages, array(
			'title'         => self::$page['title'],
			'cap'           => self::$page['cap'],
			'slug'          => self::$page['slug'],
			'callback'      => self::$page['callback'],
			'position'      => 80,
		));

		return $submenu_pages;
	}

	/**
	 * Add the submenu item to Greyd.Suite
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page($submenu_pages) {
		// debug($submenu_pages);

		array_push($submenu_pages, array(
			'page_title'    => __("Greyd.Suite", 'greyd_hub').' '.self::$page['title'],
			'menu_title'    => self::$page['title'],
			'cap'           => self::$page['cap'],
			'slug'          => self::$page['slug'],
			'callback'      => self::$page['callback'],
			'position'      => 80,
		));

		return $submenu_pages;
	}

	/**
	 * Add the submenu item to WP settings
	 * @see filter 'greyd_misc_pages'
	 */
	public function add_greyd_misc_page($misc_pages) {
		// debug($submenu_pages);

		array_push($misc_pages, array(
			'parent'    => 'options-general.php',
			'title'     => __("Greyd.Suite", 'greyd_hub'),
			'cap'       => self::$page['cap'],
			'slug'      => self::$page['url'],
			'callback'  => "",
			'position'  => 10
		));

		return $misc_pages;
	}

	/**
	 * Add dashboard tab
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab($tabs) {
		// debug($tabs);

		array_push($tabs, array(
			'title'     => self::$page['title'],
			'slug'      => self::$page['slug'],
			'url'       => self::$page['url'],
			'cap'       => self::$page['cap'],
			'priority'  => 80
		));

		return $tabs;
	}


	/*
	=======================================================================
		HANDLE SETTINGS
	=======================================================================
	*/

	/**
	 * Get all default Settings
	 * If Greyd.Suite is active, the Theme $defaults are used. see greyd_suite/inc/basics.
	 * Use filter greyd_settings_default_global and greyd_settings_default_site to add Settings
	 * 
	 * @return array   The Settings-Object (global and site) with all default Settings
	 */
	public static function get_default_settings() {
		// use Greyd.Suite Theme
		if ( class_exists("\basics") ) {
			// see greyd_suite/inc/basics::$defaults for details
			$settings = \basics::$defaults;
			$global_settings = $settings['global'];
			$site_settings = $settings['site'];
		}
		// use hub
		else {
			$global_settings = array();
			$site_settings = array();
		}

		/**
		 * Add default global settings.
		 * 
		 * @filter 'greyd_settings_default_global'
		 * 
		 * @param array $global_settings  All current default global settings.
		 */
		$global_settings = apply_filters('greyd_settings_default_global', $global_settings);

		/**
		 * Add default site settings.
		 * 
		 * @filter 'greyd_settings_default_site'
		 * 
		 * @param array $site_settings  All current default site settings.
		 */
		$site_settings = apply_filters('greyd_settings_default_site', $site_settings) ;

		return array( 
			'global' => $global_settings,
			'site' => $site_settings
		);

	}

	/**
	 * Get all Greyd Settings
	 * If Greyd.Suite is active, the Theme functions are used. see greyd_suite/inc/basics.
	 * If not, the Settings Options are read directly.
	 * 
	 * @return array   The full Settings-Object (global and site).
	 */
	public static function get_all_settings() {
		$defaults = self::get_default_settings();
		// use Greyd.Suite Theme
		if ( class_exists("\basics") ) {
			$global = \basics::get_setting('global', true);
			$site = \basics::get_setting('site', true);
			return array_replace_recursive(
				$defaults,
				array( 
					'global' => $global, 
					'site' => $site 
				)
			);
		}
		else {
			if ( is_multisite() ) {
				$global = get_network_option(null, "greyd_settings_global", $defaults['global']);
			} else {
				$global = get_option("greyd_settings_global", $defaults['global']);
			}
			$site = get_option('greyd_settings', $defaults['site']);
			return array( 
				'global' => self::check_default_settings($global, $defaults['global']),
				'site' => self::check_default_settings($site, $defaults['site'])
			);
		}
	}

	/**
	 * Recursive Helper function to make sure that all Settings are in the Settings-Object.
	 * Un-saved Settings are filled with their default values.
	 * 
	 * @param array $settings  The Settings-Object.
	 * @param array $default   The default Settings.
	 * @return array $settings The full Settings-Object with filled defaults.
	 */
	public static function check_default_settings($settings, $default) {
		foreach ($default as $key => $value) {
			if (!is_array($value)) {
				if (!isset($settings[$key])) $settings[$key] = $value;
			}
			else {
				if (!$settings) $settings = array();
				if (!isset($settings[$key])) $settings[$key] = array();
				if (!is_array($settings[$key])) $settings[$key] = (array) $settings[$key];
				$settings[$key] = self::check_default_settings($settings[$key], $value);
			}
		}
		return $settings;
	}

	/**
	 * Get single Setting or subset of Settings
	 * 
	 * @param string|array $key     Usually array of strings which defines the path to the Setting.
	 * @param bool $default         If the Setting is not found, give back the default value.
	 * @return string|array $value  The Setting value or subset.
	 */
	public static function get_setting($key, $default=true) {
		// use Greyd.Suite Theme or hub
		$settings = self::get_all_settings();
		$defaults = self::get_default_settings();
		$value = false;
		if (!is_array($key)) {
			// global or site
			$set = $settings[$key];
			$def = $defaults[$key];
		}
		else {
			// get deeper setting
			$set = $settings;
			$def = $defaults;
			foreach ($key as $k) {
				$set = isset($set[$k]) ? $set[$k] : null;
				$def = isset($def[$k]) ? $def[$k] : null;
			}
		}
		if (isset($set)) $value = $set;
		else if ($default && isset($def)) $value = $def;

		return $value;
	}

	/**
	 * Save Settings
	 * 
	 * @param string $mode  Type of Setting to save (site, global or global_network).
	 * @param array $value  Settings-Object with all values.
	 * @return bool         True if Option is updated, False otherwise.
	 */
	public static function update_settings($mode, $value) {

		$defaults = self::get_default_settings();

		// sanitize
		if (strpos($mode, 'global') === 0) {
			$value = self::sanitize_settings($value, $defaults['global']);
		}
		else {
			$value = self::sanitize_settings($value, $defaults['site']);
		}

		// use Greyd.Suite Theme
		if (Helper::is_greyd_classic()) {
			if ($mode == 'site') return update_option('settings_site_greyd_tp', $value);
			if ($mode == 'global') return update_option("settings_greyd_tp", $value);
			if ($mode == 'global_network') return update_network_option(null, "settings_greyd_tp", $value);
			return false;
		}
		// use hub
		else {
			if ($mode == 'site') return update_option('greyd_settings', $value);
			if ($mode == 'global') return update_option("greyd_settings_global", $value);
			if ($mode == 'global_network') return update_network_option(null, "greyd_settings_global", $value);
			return false;
		}
	}

	/**
	 * Recursive Helper function to make sure that all Settings are in the Settings-Object.
	 * Values not in default are skipped.
	 * 
	 * @param array $settings  The Settings-Object.
	 * @param array $default   The default Settings.
	 * @return array $settings The sanitzed Settings-Object.
	 */
	public static function sanitize_settings($settings, $default) {
		foreach ($settings as $key => $value) {
			if ( !is_array($default) || !array_key_exists($key, $default) ) {
				unset($settings[$key]);
			}
			else if ( is_array($value) ) {
				$settings[$key] = self::sanitize_settings($value, $default[$key]);
			}
		}
		return $settings;
	}

	/**
	 * Save single Setting or subset of Settings
	 * 
	 * @param string $mode          Type of Setting to save (site, global or global_network).
	 * @param string|array $key     Usually array of strings which defines the path to the Setting.
	 * @param string|array $value   The new Setting value or subset.
	 * @return bool                 True if Setting is updated, False otherwise.
	 */
	public static function update_setting($mode, $key, $value) {
		// use Greyd.Suite Theme or hub
		$settings = self::get_all_settings();
		if (!is_array($key)) {
			// global or site
			$key = array( $key );
		}
		// traverse the settings by reference to save the value into the array
		$set = &$settings;
		foreach ($key as $i => $k) {
			if ($i < count($key)-1) {
				// reference next node
				if (isset($set[$k])) $set = &$set[$k];
				else return false;
			}
			else {
				// assign value
				$set[$k] = $value;
			}
		}
		// choose site or global
		if (strpos($mode, 'global') === 0) $settings = $settings['global'];
		else $settings = $settings['site'];
		// save
		return self::update_settings($mode, $settings);
	}

	/**
	 * Reload Settings after saving.
	 * Used by Greyd.Suite to update $config.
	 */
	public static function reload_settings() {
		// use only in Greyd.Suite Theme
		if ( class_exists("\basics") ) \basics::theme_load_settings();
	}


	/*
	=======================================================================
		SETTINGS PAGE
	=======================================================================
	*/
	public function settings_page() {

		// handle POST Data
		if ( !empty($_POST) ) $this->handle_data($_POST);

		// load data
		$data = self::get_all_settings();
		// debug($data);
		$this->render_settings_page($data);

	}

	/**
	 * handle POST Data
	 * 
	 * @param array $post_data  Raw $_POST data.
	 */
	public function handle_data($post_data) {

		// check nonce
		$nonce_action = "greyd_settings";
		$nonce = isset($post_data["_wpnonce"]) ? $post_data["_wpnonce"] : null;
		$mode = is_multisite() ? (is_network_admin() ? "network_admin" : "network_site") : "site";

		$success = false;
		if ( $nonce && wp_verify_nonce($nonce, $nonce_action) ) {

			/**
			 * Do some action before (or instead of) saving the settings.
			 * e.g. when clicking the 'clear cache' Button in Autoptimize setup.
			 * 
			 * @filter 'greyd_settings_before_save'
			 * 
			 * @param bool $did_action  True if some action was fired that doesn't need saving after.
			 * @param array $post_data  Raw $_POST data.
			 */
			$did_action = apply_filters('greyd_settings_before_save', false, $post_data);
			if ($did_action) return;

			if (isset($post_data['mode']) && $post_data['mode'] == $mode) {
				$defaults = self::get_default_settings();

				// global settings
				if ($mode == "network_admin" || $mode == "site") {
					$global = self::get_setting( array('global') );

					/**
					 * Save more global settings.
					 * 
					 * @filter 'greyd_settings_more_save'
					 * 
					 * @param array $global         New global settings.
					 * @param array $defaults       Default global settings.
					 * @param array $post_data      Raw $_POST data.
					 */
					$global = apply_filters('greyd_settings_more_global_save', $global, $defaults['global'], $post_data);

					// save data
					// debug($global);
					if ($mode == "network_admin")
						$success = self::update_settings('global_network', $global);
					else 
						$success = self::update_settings('global', $global);
				}

				// site settings
				if ($mode == "network_site" || $mode == "site") {
					$site = self::get_setting( array('site') );

					/**
					 * Save more site settings.
					 * 
					 * @filter 'greyd_settings_more_save'
					 * 
					 * @param array $site           New site settings.
					 * @param array $defaults       Default site settings.
					 * @param array $post_data      Raw $_POST data.
					 */
					$site = apply_filters('greyd_settings_more_save', $site, $defaults['site'], $post_data);

					// save data
					// debug($site);
					$success = self::update_settings('site', $site);
				}
			}

			if ($success) {
				// reload settings
				self::reload_settings();
				Helper::show_message(__("Settings saved.", 'greyd_hub'), 'success');
			}
		} 
		if (!$success) {
			Helper::show_message(__("Settings could not be saved.", 'greyd_hub'), 'error');
		}

	}

	/**
	 * render settings page
	 * 
	 * @param array $data   All current settings.
	 */
	public function render_settings_page($data) {

		$nonce_action = "greyd_settings";
		$mode = is_multisite() ? (is_network_admin() ? "network_admin" : "network_site") : "site";

		/**
		 * Show additional admin notice
		 * @action 'greyd_settings_notice'
		 */
		do_action('greyd_settings_notice');

		// render page
		echo "<div class='wrap settings_wrap'>";

		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		echo "<form method='post'>
				<input type='hidden' name='mode' value='".$mode."'>".wp_nonce_field($nonce_action);

			/**
			 * Add basic settings to settings page.
			 * Return headline and full table like this:
			 * <h2>...</h2>
			 * <table class='form-table'><tr><th>...</th><tb>...</tb></tr></table>
			 * 
			 * @filter 'greyd_settings_basic'
			 * 
			 * @param string $content   settings string.
			 * @param string $mode      Settings mode (site, network_site or network_admin).
			 * @param array $data       All current settings.
			 */
			$settings = apply_filters( 'greyd_settings_basic', "", $mode, $data );
			if ($settings != "") {
				// echo "<h2>".__("Settings", 'greyd_hub')."</h2>";
				echo $settings;
			}

			/**
			 * Add more settings to settings page.
			 * Return full table like this:
			 * <table class='form-table'><tr><th>...</th><tb>...</tb></tr></table>
			 * 
			 * @filter 'greyd_settings_more'
			 * 
			 * @param string $content   More settings string.
			 * @param string $mode      Settings mode (site, network_site or network_admin).
			 * @param array $data       All current settings.
			 */
			$more = apply_filters( 'greyd_settings_more', "", $mode, $data );
			if ($more != "") {
				echo "<hr><h2>".__("More settings", 'greyd_hub')."</h2>";
				echo $more;
			}

			// Submit
			if ($settings != "" || $more != "") {
				echo submit_button();
			}
			else {
				echo "<i>".__("No settings available.", 'greyd_hub')."</i>";
			}

		echo "</form>";

		echo"</div>"; // wrap

	}


}

