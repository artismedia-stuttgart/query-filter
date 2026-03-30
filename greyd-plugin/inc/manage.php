<?php
/**
 * basic plugin management
 */
namespace Greyd;

if ( !defined( 'ABSPATH' ) ) exit;

new Manage($config);
class Manage {

	/**
	 * Holds the plugin config.
	 *
	 * @var object.
	 */
	private $config;

	/**
	 * Holds Language vars for translations.
	 */
	public $language = array(
		// All supported languages.
		"supported"     => array( 'de_DE', 'de_AT', 'de_CH', 'en_US' ),
		// All default languages. For those, translations are skipped.
		"default"       => array( 'de_DE', 'de_AT', 'de_CH' ),
		// Fallback for unsupported languages.
		"fallback"      => "en_US",
		// Cache the current locale for translation overrides.
		"cached_locale" => false
	);

	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object)$config;
		// make language object
		$this->language = (object)$this->language;

		// include puc lib
		require_once $this->config->plugin_path.'/libs/plugin-update-checker/plugin-update-checker.php';

		// init plugin
		add_action( 'after_setup_theme', array($this, 'init'), 5 );

		// set up translations
		add_action( 'after_setup_theme', array($this, 'greyd_textdomain') );
		add_action( 'override_load_textdomain', array( $this, 'fallback_textdomain_language' ), 10, 3 );
		add_filter( 'load_script_translation_file', array($this, 'fallback_translation_file'), 10, 3 );

		// display notices
		add_action( 'admin_notices', array($this, 'display_admin_notices') );
		add_action( 'network_admin_notices', array($this, 'display_admin_notices') );

		// display deactivation notices for greyd_tp_management and greyd_blocks
		add_action( 'admin_notices', array($this, 'display_admin_deactivation_notices'), 11 );
		add_action( 'network_admin_notices', array($this, 'display_admin_deactivation_notices'), 11 );

		/** @since greyd_blocks_new */
		// modify user capabilities
		if (is_multisite()) add_filter( 'map_meta_cap', array($this, 'mod_user_capabilities'), 1, 3 );

		// modify permissions during rest calls
		add_filter( 'rest_endpoints', array($this, 'mod_rest_endpoints_permission'), 9999 );
		
	}

	/**
	 * Init class vars and plugin updates
	 */
	public function init() {

		// make URLs from init.php translatable
		$urls = array(
			__("https://greyd.io/", 'greyd_hub'),
			__("https://greyd.io/pricing/", 'greyd_hub'),
			__("https://docs.greyd.io/", 'greyd_hub'),
			__("https://greyd-resources.de/en/", 'greyd_hub')
		);

		// fix loginpage on multisites
		if (is_multisite()) $this->fix_loginpage();

		// init plugin update checker
		if (class_exists('Puc_v4_Factory') && !$this->is_frozen()) {
			$myUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
				$this->config->update_file,     // Full path to the update server manifest file.
				$this->config->plugin_file,     // Full path to the main plugin file or functions.php.
				$this->config->plugin_name      // Insert Plugin slug, usually same as directory name.
			);
		}

		// set required versions
		// deprecated - remove in next version
		add_filter( 'greyd_theme_required_versions', array($this, 'set_required_theme_version') );
		add_filter( 'greyd_forms_required_versions', array($this, 'set_required_form_version') );
		// add_filter( 'greyd_blocks_required_versions', array($this, 'set_required_blocks_version') );

	}


	/*
	====================================================================================
		Translations
	====================================================================================
	*/

	/**
	 * Register textdomain for translations
	 */
	public function greyd_textdomain() {
		load_plugin_textdomain( 'greyd_hub', false, dirname( plugin_basename($this->config->plugin_file) ) . '/languages/' );
		/** @since greyd_blocks_new */
		load_plugin_textdomain( 'greyd_blocks', false, dirname( plugin_basename($this->config->plugin_file) ) . '/languages/' );
	}

	/**
	 * Override the loaded PHP textdomain.
	 *
	 * This overrides the translations for greyd plugins & themes, if the current
	 * language is not supported. It also prevents translations entirely if the
	 * user already uses one of the default languages.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/override_load_textdomain/
	 *
	 * @param bool $override    Whether to override the .mo file loading. Default false.
	 * @param string $domain    Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile    Path to the MO file.
	 */
	public function fallback_textdomain_language( $override, $domain, $mofile ) {

		$theme_root = str_replace('/', '\\/', wp_normalize_path(get_theme_root()."/"));
		$plugin_root = str_replace('/', '\\/', wp_normalize_path(WP_PLUGIN_DIR."/"));
		$mo_test = wp_normalize_path($mofile);

		// we only want to modify the fallback language of greyd plugins & themes
		if ( !preg_match( "/(".$theme_root."|".$plugin_root.")greyd/", $mo_test) ) return false;

		// get language
		$locale = $this->language->cached_locale ? $this->language->cached_locale : $this->get_locale();

		// /**
		//  * Skip the loading of the requested file when this is a german language,
		//  * because all default strings are german.
		//  */
		// if ( in_array( $locale, $this->language->default ) ) {
		// 	return true;
		// }

		/**
		 * If the current language is supported, continue loading the file.
		 */
		if ( in_array( $locale, $this->language->supported ) ) {
			return false;
		}

		/**
		 * If no file for this translation is available, use the fallback
		 * language file, which is usually 'en_US'
		 */
		if ( !is_readable($mofile) ) {
			// try to get the fallback file
			$fallback_mofile = preg_replace('/([a-z]{2}_[A-Z]{2}).mo/', $this->language->fallback.'.mo', $mofile);
			if (is_readable($fallback_mofile)) {
				// load fallback mofile
				load_textdomain( $domain, $fallback_mofile );
				// return true to skip the loading of the originally requested file
				return true;
			}
		}

		// by default, don't override
		return false;
	}

	/**
	 * Override the loaded JSON textdomains.
	 *
	 * Same as $this->fallback_textdomain_language() for block translations.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/load_script_translation_file/
	 *
	 * @param string|false $file    Path to the translation file to load. False if there isn't one.
	 * @param string $handle        Name of the script to register a translation domain to.
	 * @param string $domain        The text domain.
	 */
	public function fallback_translation_file( $file, $handle, $domain ) {

		// we only want to modify the fallback language of greyd plugins & themes
		if (strpos($domain, "greyd_") === false) return $file;

		// get language
		$locale = $this->language->cached_locale ? $this->language->cached_locale : $this->get_locale();

		// /**
		//  * Skip the loading of the requested file when this is a german language,
		//  * because all default strings are german.
		//  */
		// if ( in_array( $locale, $this->language->default ) ) {
		// 	return "";
		// }

		/**
		 * If the current language is supported, continue loading the file.
		 */
		if ( in_array( $locale, $this->language->supported ) ) {
			return $file;
		}

		/**
		 * If no file for this translation is available, use the fallback
		 * language file, which is usually 'en_US'
		 */
		if ( !empty($file) && !is_readable($file) ) {
			// try to get the fallback file
			$file = str_replace( '-'.$locale.'-', '-'.$this->language->fallback.'-', $file );
		}

		return $file;
	}

	/**
	 * Get the current locale depending on the user. Cuts suffix like 'formal'.
	 */
	public function get_locale() {
		$locale = function_exists('wp_get_current_user') && is_user_logged_in() ? get_user_locale() : get_locale();
		$locale = explode('_', $locale );
		return $locale[0] . ( isset($locale[1]) ? '_'.$locale[1] : '' );
	}


	/*
	====================================================================================
		Plugin Updates and version check
	====================================================================================
	*/

	/**
	 * Display admin notices for activation and check if this plugin needs an update.
	 */
	public function display_admin_notices() {

		// check plugin versions and show update notice
		if ( $this->plugin_needs_update() && !( isset($_GET['action']) && $_GET['action'] === 'do-plugin-upgrade' ) ) {
			echo "<div class='error notice'>
				<p>".sprintf(
					__("The plugin %s needs a newer version. Please %supdate the plugin%s to prevent possible errors.", 'greyd_hub'),
					"<strong>".$this->config->plugin_name_full."</strong>",
					"<a href='".( is_multisite() ? network_admin_url('update-core.php') : admin_url('update-core.php') )."'>",
					"</a>"
				)."</p>
			</div>";
		}
	}

	/**
	 * Check if this plugin needs an update
	 *
	 * @return bool Returns true if this plugin needs an update.
	 */
	public function plugin_needs_update() {

		/**
		 * Get required versions
		 * @deprecated
		 *
		 * @filter greyd_hub_required_versions
		 */
		$requirements = apply_filters( 'greyd_hub_required_versions', array() );
		
		/**
		 * Get required versions of this plugin
		 * 
		 * @filter greyd_versions
		 */
		$versions = apply_filters( 'greyd_versions', array() );
		foreach( $versions as $n => $v ) {
			if ( isset($v['required']['greyd_hub']) ) {
				$requirements[$n] = $v['required']['greyd_hub'];
			}
		}

		if ( empty($requirements) || !is_array($requirements) ) return false;
		// debug( 'hub requirements' );
		// debug( $requirements );

		if ( !function_exists('get_plugin_data') ) require_once(ABSPATH.'wp-admin/includes/plugin.php');

		$all_true           = true;
		$plugin_data        = get_plugin_data( $this->config->plugin_file, false, false );
		$current_version    = isset($plugin_data['Version']) && !empty($plugin_data['Version']) ? $plugin_data['Version'] : '0';
		foreach ( $requirements as $required_version ) {
			if ( $all_true ) {
				$all_true = version_compare( $current_version, $required_version, '>=' );
			}
		}
		return !$all_true;
	}

	public function is_frozen() {
		$l = get_option('gtpl');
		return $l && !empty($l) && is_array($l) && isset($l['frozen']) && $l['frozen'];
	}

	/**
	 * Add required Greyd Theme Version
	 *
	 * @filter greyd_suite_required_versions
	 */
	public function set_required_theme_version($return) {
		$versions = isset($this->config->required_versions) ? $this->config->required_versions : array();
		$version = isset($versions['greyd_suite']) ? $versions['greyd_suite'] : null;
		if ( $version ) {
			$return[$this->config->plugin_name] = $version;
		}
		return $return;
	}

	/**
	 * Add required Greyd.Forms Version
	 *
	 * @filter greyd_forms_required_versions
	 */
	public function set_required_form_version($return) {
		$versions = isset($this->config->required_versions) ? $this->config->required_versions : array();
		$version = isset($versions['greyd_forms']) ? $versions['greyd_forms'] : null;
		if ( $version ) {
			$return[$this->config->plugin_name] = $version;
		}
		return $return;
	}

	/**
	 * Add required Greyd.Blocks Version
	 * @deprecated since greyd_hub_new
	 *
	 * @filter greyd_blocks_required_versions
	 */
	public function set_required_blocks_version($return) {
		$versions = isset($this->config->required_versions) ? $this->config->required_versions : array();
		$version = isset($versions['greyd_blocks']) ? $versions['greyd_blocks'] : null;
		if ( $version ) {
			$return[$this->config->plugin_name] = $version;
		}
		return $return;
	}

	/**
	 * Display admin notices for deactivation of greyd_tp_management and greyd_blocks plugins.
	 * @since 2.1.0
	 */
	public function display_admin_deactivation_notices() {

		// deactivation notices for greyd_tp_management and greyd_blocks
		$theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
		$screen = get_current_screen();
		// debug($screen);

		if (
			!$screen ||
			!isset( $screen->id ) ||
			// in classic show notices only for version 1.20.0 and higher
			( $theme->stylesheet == 'greyd_suite' && version_compare( $theme->get('Version'), '1.20.0', '<' ) ) ||
			// check capability
			!current_user_can( 'install_plugins' ) ||
			( is_multisite() && is_network_admin() && !current_user_can( 'manage_network_plugins' ) )
		) {
			return;
		}

		// show notice only on certain admin screens
		if (
			$screen->id === 'plugins' || 
			$screen->id === 'plugin-install' || 
			$screen->id === 'plugins-network' || 
			$screen->id === 'plugin-install-network' || 
			$screen->id === 'update-core' || 
			$screen->id === 'update-core-network' || 
			$screen->id === 'themes' || 
			$screen->id === 'themes-network' || 
			strpos( $screen->id, 'greyd' ) !== false
		) {

			// get plugins
			$plugins = get_option('active_plugins');
			if ( is_multisite() ) {
				$plugins_multi = get_site_option('active_sitewide_plugins');
				$plugins = array_merge($plugins, array_keys($plugins_multi));
				$plugins = array_unique($plugins);
				sort($plugins);
			}

			// deprecated plugins
			$deprecated = array(
				'greyd_tp_management/init.php' => "Greyd.Hub",
				'greyd_blocks/init.php' => "Greyd.Blocks",
			);

			// page atts
			$context = $screen->parent_base == 'plugins' && isset($_GET["plugin_status"]) ? $_GET["plugin_status"] : 'all';
			$page = $screen->parent_base == 'plugins' && isset($_GET["paged"]) ? $_GET["paged"] : '1';
			$s = $screen->parent_base == 'plugins' && isset($_GET["s"]) ? $_GET["s"] : '';

			foreach ( $deprecated as $url => $title ) {
				// check if installed
				if ( in_array($url, $plugins) ) {

					// deactivation link
					$link = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin='.urlencode( $url ).'&amp;plugin_status='.$context.'&amp;paged='.$page.'&amp;s='.$s, 'deactivate-plugin_'.$url );
					
					if (
						is_multisite() &&
						is_network_admin() &&
						current_user_can( 'manage_network_plugins' ) &&
						in_array($url, array_keys($plugins_multi))
					) {
						// show message in network admin
						?>
						<div class="notice notice-warning">
							<div style="padding:6px">
								<p><?php echo sprintf(
									__("We have noticed that the %s plugin is still active in your network. It is no longer needed since Greyd Version 2.0. All features have been moved into the new Greyd Plugin. However some subsites may still be using it. Please make sure that it is no longer needed and then deactivate it.", "greyd_hub" ),
									"<strong>".$title."</strong>"
								);
								?></p>
								<p>
									<a href="<?php echo $link ?>"><?php echo sprintf(
										__("Deactivate '%s' plugin now", "greyd_hub"),
										$title
									); ?></a>
								</p>
							</div>
						</div>
						<?php
					}
					else if (
						!is_network_admin() &&
						current_user_can( 'deactivate_plugin', $url )
					) {
						// show message in admin
						?>
						<div class="notice notice-info">
							<div style="padding:6px">
								<p><?php echo sprintf(
									__("We have discovered that the %s plugin is still active. It is no longer needed since Greyd Version 2.0. All features have been moved into the new Greyd Plugin. You can safely deactivate it and remove it afterwards.", "greyd_hub" ),
									"<strong>".$title."</strong>"
								);
								?></p>
								<p>
									<a href="<?php echo $link ?>"><?php echo sprintf(
										__("Deactivate '%s' plugin now", "greyd_hub"),
										$title
									); ?></a>
								</p>
							</div>
						</div>
						<?php
					}

				}
			}

		}

	}


	/*
	====================================================================================
		Login Fixes
	====================================================================================
	*/

	/**
	 * Fix headers and footers on login pages
	 */
	public function fix_loginpage() {
		// fixes "Lost Password?" URLs on login page
		add_filter("lostpassword_url", function ($url, $redirect) {
			$args = array( 'action' => 'lostpassword' );
			if (!empty($redirect)) $args['redirect_to'] = $redirect;
			return add_query_arg($args, site_url('wp-login.php'));
		}, 10, 2);

		// fixes other password reset related urls
		add_filter('network_site_url', function($url, $path, $scheme) {
			if (stripos($url, "action=lostpassword") !== false)
				return site_url('wp-login.php?action=lostpassword', $scheme);
			if (stripos($url, "action=resetpass") !== false)
				return site_url('wp-login.php?action=resetpass', $scheme);
			return $url;
		}, 10, 3);

		/**
		 * Fixes URLs in email that goes out to the current blog url.
		 *
		 * @since 1.2.7 it uses the trailingslashit() to not interfere with
		 * non-standard wordpress url setups.
		 */
		add_filter("retrieve_password_message", function ($message, $key) {
			return str_replace(
				trailingslashit( get_site_url(1) ),
				trailingslashit( get_site_url() ),
				$message
			);
		}, 10, 2);

		// fixes email title
		add_filter("retrieve_password_title", function($title) {
			return "[".wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)."] Password Reset";
		});

		// fixes loginpage header url
		add_filter( 'login_headerurl', function ($url) {
			return home_url();
		});
	}

	/*
	====================================================================================
		greyd_blocks_new
	====================================================================================
	*/

	/**
	 * Enable unfiltered_html capability for Admins and Editors on Multisites.
	 * Needed to save styles in post content
	 *
	 * @param  array  $caps    The user's capabilities.
	 * @param  string $cap     Capability name.
	 * @param  int    $user_id The user ID.
	 * @return array  $caps    The modified user's capabilities.
	 */
	public function mod_user_capabilities($caps, $cap, $user_id) {
		if ($cap === 'unfiltered_html') {
			if (user_can( $user_id, 'administrator' ) || user_can( $user_id, 'editor' )) {
				$caps = array( 'unfiltered_html' );
			}
		}
		return $caps;
	}


	/**
	 * Allow certain rest endpoints for logged in users with "edit_posts" capability.
	 * Endpoints are called from the Editor to extend functionality, but some roles don't have the permission.
	 * By overriding the permission_callback in these circumstances we make sure the Editor works properly.
	 * 
	 * Default enabled endpoints are:
	 * - /wp/v2/users (enable editor-helper)
	 * - /wp/v2/plugins and /wp/v2/global-styles/* (enable theme global-styles)
	 * extendable by 'greyd_allow_rest_endpoints' filter (used by dynamic_templates).
	 * 
	 * @filter 'rest_endpoints'
	 * @since 2.1.0
	 * 
	 * @param  array  $endpoints    Registered endpoints.
	 * @return array  $endpoints    Modified endpoints.
	 */
	public function mod_rest_endpoints_permission( $endpoints ) {

		if (
			is_user_logged_in()
			&& current_user_can("edit_posts")
			// && isset($_REQUEST["rest_route"])
			// && isset($_REQUEST["_locale"]) && $_REQUEST["_locale"] == "user"
		) {

			$allowed = array(

				// read usermeta (editor.js -> editor-helper)
				'/wp/v2/users',

				// read active plugins (theme: editor-tools.js)
				'/wp/v2/plugins',

				// read global-styles (theme: editor-tools.js)
				'/wp/v2/global-styles/(?P<id>[\/\w-]+)',
				'/wp/v2/global-styles/themes/(?P<stylesheet>[^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?)',

			);

			/**
			 * allow more endpoints.
			 * 
			 * @filter 'greyd_allow_rest_endpoints'
			 * 
			 * @param array $allowed   allowed endpoints.
			 */
			$allowed = apply_filters( 'greyd_allow_rest_endpoints', $allowed );
	
			foreach ( $allowed as $endpoint ) {

				if ( !isset($endpoints[$endpoint]) ) {
					continue;
				}
				
				foreach ( $endpoints[$endpoint] as $key => &$handler ) {
					
					if ( !is_numeric( $key ) ) {
						continue;
					}
	
					// debug($key);
					// debug($handler["methods"]);

					if ($handler["methods"] === "GET") {
						// allow route for this call
						$handler["permission_callback"] = '__return_true';
					}
	
				}

			}
			
		}

		return $endpoints;

	}
	
}