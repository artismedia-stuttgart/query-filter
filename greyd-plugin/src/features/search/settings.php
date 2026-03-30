<?php
/**
 * Settings for search features
 * 
 * @since 0.8.8
 */
namespace Greyd\Search;

use Greyd\Settings as Core_Settings;
use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Settings($config);
class Settings {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Standalone mode.
	 * 
	 * @var bool
	 */
	public static $is_standalone = false;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	/**
	 * Class constructor.
	 */
	public function __construct($config) {
		// set config
		$this->config = (object)$config;
		if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
			// standalone mode
			self::$is_standalone = true;
		}
		
		// static vars
		add_action( 'init', function() {
			// define page details
			self::$page = array(
				'slug'      => 'advanced_search',
				'title'     => __('Advanced Search', 'greyd_hub'),
				// 'descr'     => __("Create dynamic templates that you can fill with different content at different places on your website.", 'greyd_hub'),
				'url'       => admin_url('admin.php?page=advanced_search'),
				'cap'       => 'manage_options',
				'callback'  => array( $this, 'settings_page' ),
			);
			// debug(self::$page);
		}, 0 );


		// backend
		if ( is_admin() ) {
			
			if (self::$is_standalone) {
				// standalone mode
				add_action( 'admin_menu', array($this, 'standalone_submenu'), 40 );
			}
			else {
				// in hub
				add_filter( 'greyd_submenu_pages', array($this, 'add_greyd_submenu_page') );
				add_filter( 'greyd_dashboard_tabs', array($this, 'add_greyd_dashboard_tab') );
				add_filter( 'greyd_settings_default_site', array($this, 'add_greyd_setting') );
			}

		}

		// dashboard panel
		add_filter( 'greyd_dashboard_active_panels', array($this, 'add_dashboard_active_panel') );
	}

	public function add_dashboard_active_panel($panels) {

		$panels[ 'advanced-search' ] = true;

		return $panels;
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
	 * Add the submenu item to Greyd.Suite if Hub is installed
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
			'position'      => 50,
		));

		return $submenu_pages;
	}

	/**
	 * Add dashboard tab if Hub is installed
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab($tabs) {
		// debug($tabs);

		$tabs['advanced_search'] = array(
			'title'     => self::$page['title'],
			'slug'      => self::$page['slug'],
			'url'       => self::$page['url'],
			'cap'       => self::$page['cap'],
			'priority'  => 50
		);

		return $tabs;
	}

	/**
	 * Add default settings if Hub is installed
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_greyd_setting($site_settings) {

		// add default settings
		$site_settings = array_replace_recursive(
			$site_settings,
			self::get_defaults()
		);

		return $site_settings;
	}


	/*
	=======================================================================
		handle settings and helper
	=======================================================================
	*/

	// default settings
	public static function get_defaults() {

		$defaults = array( 
			'search_metafields' => 'false',
			'advanced_search' => array(
				'postviews_counter' => 'false',
				'postviews_counter_editable' => 'false',
				'relevance' => 'false',
				'live_search' => 'false',
				'global_search' => 'false',         // todo: inject by global content plugin
				// 'exclude_posts_from_search' => "", // injected by exclude.php
				// 'exclude_terms_from_search' => "", // injected by exclude.php
			)
		);

		/**
		 * Add more default settings.
		 * 
		 * @filter 'greyd_advanced_search_settings_default'
		 * 
		 * @param array $defaults       All current default settings.
		 */
		$defaults = apply_filters( 'greyd_advanced_search_settings_default', $defaults );

		return $defaults;
	}

	/**
	 * Get all default Settings
	 * If Greyd.Hub is active, the hub functions are used.
	 * 
	 * @return array   The Settings-Object (global and site) with all default Settings
	 */
	public static function get_default_settings() {
		// use hub
		if (!self::$is_standalone) return Core_Settings::get_default_settings();
		// standalone
		else {
			return array( 
				'site' => self::get_defaults()
			);
		}
	}
	
	/**
	 * Get all Greyd Settings
	 * If Greyd.Hub is active, the hub functions are used.
	 * If not, the Settings Options are read directly.
	 * 
	 * @return array   The full Settings-Object (global and site).
	 */
	public static function get_all_settings() {
		// use hub
		if (!self::$is_standalone) return Core_Settings::get_all_settings();
		// standalone
		else {
			$defaults = self::get_default_settings();
			$site = get_option('greyd_settings', $defaults['site']);
			$site = self::check_default_settings($site, $defaults['site']);
			return array( 'site' => $site );
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
				if (!isset($settings[$key])) $settings[$key] = array();
				$settings[$key] = self::check_default_settings($settings[$key], $value);
			}
		}
		return $settings;
	}

	/**
	 * Get single Setting or subset of Settings
	 * If Greyd.Hub is active, the hub functions are used.
	 * 
	 * @param string|array $key     Usually array of strings which defines the path to the Setting.
	 * @param bool $default         If the Setting is not found, give back the default value.
	 * @return string|array $value  The Setting value or subset.
	 */
	public static function get_setting($key, $default=true) {
		// use hub
		if (!self::$is_standalone) return Core_Settings::get_setting($key, $default);
		// standalone
		else {
			$settings = self::get_all_settings();
			$defaults = self::get_default_settings();
			$value = false;
			if (!is_array($key)) {
				$set = $settings[$key];
				$def = $defaults[$key];
			}
			else {
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
	}
	
	/**
	 * Save Settings
	 * If Greyd.Hub is active, the hub functions are used.
	 * 
	 * @param string $mode  Type of Setting to save (site, global or global_network).
	 * @param array $value  Settings-Object with all values.
	 * @return bool         True if Option is updated, False otherwise.
	 */
	public static function update_settings($mode, $value) {
		// use hub
		if (!self::$is_standalone) return Core_Settings::update_settings($mode, $value);
		// standalone
		else {
			$defaults = self::get_default_settings();
			// sanitize
			$value = self::sanitize_settings($value, $defaults['site']);
			// save
			return update_option('greyd_settings', $value);
		}
	}
	
	/**
	 * Recursive Helper function to make sure that all Settings are in the Settings-Object.
	 * Values not in default are skiped.
	 * 
	 * @param array $settings  The Settings-Object.
	 * @param array $default   The default Settings.
	 * @return array $settings The sanitzed Settings-Object.
	 */
	public static function sanitize_settings($settings, $default) {
		foreach ($settings as $key => $value) {
			if (!array_key_exists($key, $default)) {
				unset($settings[$key]);
			}
			else if (is_array($value)) {
				$settings[$key] = self::sanitize_settings($value, $default[$key]);
			}
		}
		return $settings;
	}
	
	/**
	 * Save single Setting or subset of Settings
	 * If Greyd.Hub is active, the hub functions are used.
	 * 
	 * @param string $mode          Type of Setting to save (site, global or global_network).
	 * @param string|array $key     Usually array of strings which defines the path to the Setting.
	 * @param string|array $value   The new Setting value or subset.
	 * @return bool                 True if Setting is updated, False otherwise.
	 */
	public static function update_setting($mode, $key, $value) {
		// use hub
		if (!self::$is_standalone) return Core_Settings::update_setting($mode, $key, $value);
		// standalone
		else {
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
			// save
			return self::update_settings($mode, $settings['site']);
		}
	}

	/**
	 * Reload Settings after saving
	 * Used by Greyd.Suite to update $config
	 */
	public static function reload_settings() {
		// use only in hub
		if (!self::$is_standalone) Core_Settings::reload_settings();
	}

	/**
	 * Basic helper to replace hub helper in standalone mode
	 */
	public static function show_message($msg, $mode='info') {
		// use hub
		if (!self::$is_standalone) return Helper::show_message($msg, $mode, false);
		// standalone
		// wp notice (is moved to top of page)
		if ($msg != "") echo "<div class='notice notice-".$mode." is-dismissible'><p>".$msg."</p></div>";
	}
	// check for Greyd.Suite setup
	public static function is_greyd_classic() {
		return \Greyd\Helper::is_greyd_classic();
	}
	// check for Greyd.Blocks setup
	public static function is_greyd_blocks() {
		return function_exists('is_greyd_blocks') && is_greyd_blocks() ? true : false;
	}

	/*
	=======================================================================
		settings Page
	=======================================================================
	*/
	public function settings_page() {

		if ( !is_admin() ) return;

		// handle POST Data
		if ( !empty($_POST) ) $this->handle_data($_POST);

		// load data
		$data = self::get_all_settings();
		$this->render_settings_page($data);

	}

	/**
	 * handle POST Data
	 * 
	 * @param array $post_data  Raw $_POST data.
	 */
	public function handle_data($post_data) {
		
		// check nonce
		$nonce_action = "greyd_advanced_search";
		$nonce = isset($post_data["_wpnonce"]) ? $post_data["_wpnonce"] : null;

		$success = false;
		if ( $nonce && wp_verify_nonce($nonce, $nonce_action) ) {
			
			// get default settings
			$defaults = self::get_default_settings()['site'];
			// get old settings
			$old_settings = self::get_setting(array('site'));
			// make new settings
			$new_settings = array(
				'search_metafields' => isset($post_data['search_metafields']) ? esc_attr($post_data['search_metafields']) : $defaults['search_metafields'],
				'advanced_search'   => array(
					'relevance'         => isset($post_data['relevance']) ? esc_attr($post_data['relevance']) : $defaults['advanced_search']['relevance'],
					'live_search'       => isset($post_data['live_search']) ? esc_attr($post_data['live_search']) : $defaults['advanced_search']['live_search'],
					'postviews_counter' => isset($post_data['postviews_counter']) ? esc_attr($post_data['postviews_counter']) : $defaults['advanced_search']['postviews_counter'],
					'postviews_counter_editable' => isset($post_data['postviews_counter_editable']) ? esc_attr($post_data['postviews_counter_editable']) : $defaults['advanced_search']['postviews_counter_editable'],
				),
			);

			// deprecated -> params changed
			// todo: old filter version used by global content plugin
			// $new_settings = apply_filters("greyd_advanced_search_settings_save", $new_settings, $old_settings);

			/**
			 * Save more search settings.
			 * 
			 * @filter 'greyd_advanced_search_settings_save'
			 * 
			 * @param array $new_settings   New settings.
			 * @param array $defaults       Default settings.
			 * @param array $post_data      Raw $_POST data.
			 */
			$new_settings = apply_filters('greyd_advanced_search_settings_save', $new_settings, $defaults, $post_data);
			
			// save settings
			$success = self::update_settings('site', array_replace_recursive(
				$old_settings,
				$new_settings
			));

			if ($success) {
				// reload settings
				self::reload_settings();
				self::show_message(__("Settings saved.", 'greyd_hub'), 'success');
			}
		} 
		if (!$success) {
			self::show_message(__("Settings could not be saved.", 'greyd_hub'), 'error');
		}
	
	}

	/**
	 * render settings page
	 * 
	 * @param array $data   All current settings.
	 */
	public function render_settings_page($data) {

		/**
		 * Render the page
		 */
		echo "<div class='wrap settings_wrap'>
			<h2>".__('Advanced Search', 'greyd_hub')."</h2>
			<form method='post'>
			<table class='form-table'>";

		wp_nonce_field("greyd_advanced_search");

		
		// Live Search
		echo "<tr>
			<th>".__("Live Search", 'greyd_hub')."</th>
			<td>";
				// enable checkbox
				$val = isset($data['site']['advanced_search']['live_search'])? $data['site']['advanced_search']['live_search'] : false;
				$sel = $val == 'true' ? "checked='checked'" : "";
				$toggle = 'document.querySelector(".toggle_live_search").classList.toggle("hidden")';
				echo "<label for='live_search'>
					<input type='checkbox' id='live_search' name='live_search' value='true' ".$sel." onchange='".$toggle."'/>
					<span>".__("Update search results live", 'greyd_hub')."</span>
					<small class='color_light'>".
						__("If you enable this option, the results on the results page will be updated live without reloading the page.", 'greyd_hub').
						'<br><br>'.
						(
							is_greyd_blocks() ?
							__("You can customize the styling of the \"Load More\" button in the \"Query Loop\" block in the respective search template.", 'greyd_hub') :
							__("You can customize the styling of the \"Load More\" button in the \"found posts\" module in the respective search template.", 'greyd_hub')
						).
					"</small>
				</label>
			</td>
		</tr>";
		
		// Relevance
		$val = isset($data['site']['advanced_search']['relevance']) ? $data['site']['advanced_search']['relevance'] : false;
		$sel = ( $val == "true") ? "checked='checked'" : "";
		echo "<tr>
			<th>".__("Relevance", 'greyd_hub')."</th>
			<td>
				<label for='relevance'>
					<input type='checkbox' id='relevance' name='relevance' value='true' ".$sel." />
					<span>".__("Activate relevance sorting", 'greyd_hub')."</span>
					<small class='color_light'>".__("Enable this option to use the improved relevance sorting for normal search, Live Search (if enabled) and Auto Search.", 'greyd_hub')."</small>
				</label>
			</td>
		</tr>";
		
		// Post Counter
		echo "<tr>
			<th>".__("Post views counter", 'greyd_hub')."</th>
			<td>";
				// enable checkbox
				$val = isset($data['site']['advanced_search']['postviews_counter']) ? $data['site']['advanced_search']['postviews_counter'] : false;
				$sel = $val == 'true' ? "checked='checked'" : "";
				echo "<label for='postviews_counter'>
					<input type='checkbox' id='postviews_counter' name='postviews_counter' value='true' ".$sel." />
					<span>".__("Track post views", 'greyd_hub')."</span>
					<small class='color_light'>".__("If you enable this option, the views of a post are counted. In addition, a new sorting option (most read) is available.", 'greyd_hub')."</small>
					<small class='color_light'>".__("Please note that only views from users who are not logged in are counted.", 'greyd_hub')."</small>
				</label>";
		// 	echo "</td>
		// </tr>";

		// // Post Counter Editable
		// echo "<tr>
		// 	<th>".__("Change post views", 'greyd_hub')."</th>
		// 	<td>";
				// enable checkbox
				$val = isset($data['site']['advanced_search']['postviews_counter_editable']) ? $data['site']['advanced_search']['postviews_counter_editable'] : false;
				$sel = $val == 'true' ? "checked='checked'" : "";
				echo "<label for='postviews_counter_editable'>
					<input type='checkbox' id='postviews_counter_editable' name='postviews_counter_editable' value='true' ".$sel." />
					<span>".__("Allow administrators to edit the post views counter", 'greyd_hub')."</span>
					<small class='color_light'>".__("If you enable this option, administrators will be able to manually edit the post views counter on the post editing screen.", 'greyd_hub')."</small>
				</label>";
			echo "</td>
		</tr>";

		// Autofill
		echo "<tr>
			<th>".__('Auto Search', 'greyd_hub')."</th>
			<td>".
				"<p>" . __("With Auto Search you can show the user an individual preview of the search results directly when entering a search term.", 'greyd_hub') . "</p>" .
				"<small class='color_light'>" . __("To do this, activate the check mark for \"Activate Auto Search\" in the search module in the \"Auto Search\" tab.", 'greyd_hub') . "</small>" .
			"</td>
		</tr>";

		// Custom Fields
		echo "<tr>
			<th>".__("Extend WordPress search with custom fields (e.g. Dynamic Post Type fields)", 'greyd_hub')."</th>
			<td>";
				// enable checkbox
				$val = isset($data['site']['search_metafields']) ? $data['site']['search_metafields'] : false;
				$sel = $val == 'true' ? "checked='checked'" : "";
				echo "<label for='search_metafields'>
					<input type='checkbox' id='search_metafields' name='search_metafields' value='true' ".$sel." />
					<span>".__("Search post meta data", 'greyd_hub')."</span>
					<small class='color_light'>".__("The fields of your Dynamic Post Types are included in the search.<br>By default, WordPress only searches the title and content of a post for possible hits when a search is made. The contents of the individual fields are not searched and potentially relevant contributions are not shown in the results.", 'greyd_hub')."</small>
				</label>
			</td>
		</tr>";

		// deprecated -> use filter 'greyd_advanced_search_settings_more' instead
		// todo: called by global content plugin
		do_action( "greyd_advanced_search_settings_table", $data );

		/**
		 * Add more settings to search settings page.
		 * Return tablerow like this:
		 * <tr><th>...</th><tb>...</tb></tr>
		 * 
		 * @filter 'greyd_advanced_search_settings_more'
		 * 
		 * @param string $content   More settings string.
		 * @param array $data       All current settings.
		 */
		echo apply_filters( 'greyd_advanced_search_settings_more', "", $data );

		// end of table
		echo "</table>";

		// infobox
		// echo Helper::render_info_box([
		//     "style" => "warning",
		//     "above" => _x('Achtung', 'small', 'greyd_hub'),
		//     "text" => __('Alle Optionen der Advanced Search können die Geschwindigkeit der Suche beeinträchtigen. Behalte die Performance deiner Suchanfragen im Auge und deaktiviere nicht benötigte Features.', 'greyd_hub'),
		// ]);

		// Submit
		echo submit_button()."</form>";

		echo"</div>"; // wrap
	}
}