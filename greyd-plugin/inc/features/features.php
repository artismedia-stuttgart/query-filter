<?php
/**
 * The features settings page.
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Features( $config );
class Features {

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
	 * Option name for the features array on site level.
	 *
	 * @var string
	 */
	const OPTION = 'greyd_features';

	/**
	 * Option name for the features array on network level.
	 *
	 * @var string
	 */
	const OPTION_GLOBAL = 'greyd_features_global';

	/**
	 * path for the features folder.
	 *
	 * @var string
	 */
	const FEATURES_PATH = GREYD_PLUGIN_PATH . '/src/features/';

	/**
	 * Constructor
	 */
	public function __construct( $config ) {
		// set config
		$this->config = (object) $config;

		// load Features
		add_action( 'plugins_loaded', array( $this, 'load_active_features' ), 0 );


		// define page details
		add_action( 'init', function() {
			// Get admin instance for callback
			$admin = new \Greyd\Features\Admin( $this->config );
			self::$page = array(
				'slug'     => 'greyd_features',
				'title'    => __( 'Features', 'greyd_hub' ),
				'url'      => admin_url( 'admin.php?page=greyd_features' ),
				'cap'      => 'manage_options',
				'callback' => array( $admin, 'render_features_page' ),
			);
		}, 0 );

		// filter
		add_filter( 'greyd_features_files', array( $this, 'add_core_plugins' ) );
		// add_filter( 'greyd_features_files', array($this, 'add_test_features') );

		add_filter( 'greyd_features', array( $this, 'force_template_library_in_non_classic_sites' ) );
	}


	/**
	 * Load active features.
	 */
	public function load_active_features() {

		// global config for incs
		global $config;
		$config = (array) $this->config;

		/**
		 * Use saved feature setup to include.
		 * uncomment to enable and override the dev-mode.
		 */
		$features = self::get_active_features();
		$plugins  = Helper::active_plugins();
		// debug($features);
		// debug($plugins);

		/**
		 * Classic SUITE: fixed list of features to include.
		 */
		if ( Helper::is_greyd_classic() ) {
			$features = array_merge( $features, array(
				'hub/init.php',
				'posttypes/init.php',
				'post-export/init.php',
				'multiselects/init.php',
				'license/init.php',
				'connections/init.php',
				'search/init.php',
				'popups/init.php',
				'dynamic/init.php',
				'query/init.php',
				'library/init.php',
				'user/init.php',
				'icons/icons.php',
				'search-and-replace/search-and-replace.php',
				'comments.php',
				'cookie-handler/init.php',
				'pagespeed.php',
				'seo.php',
				'uberall.php',
				'smtp.php',
				'accessibility.php',
				'global-dynamic-tags/init.php',
				'blocks/init.php',
				// from blocks plugin
				'blocks/init.php',
				'layout/init.php',
				'trigger/init.php',
				'animations/init.php',
				'lottie/init.php'
			) );
		}


		foreach ( $features as $feature ) {

			// paths
			$plugin_path  = wp_normalize_path( GREYD_PLUGIN_PATH . '/' );
			$feature_path = wp_normalize_path( self::FEATURES_PATH );

			$file = '';
			if ( strpos( $feature, '..' ) !== false ) {
				// is internal plugin
				// convert relative path to absolute path
				$file = wp_normalize_path( realpath( $plugin_path . $feature ) );
			} elseif ( substr_count( $feature, '/' ) > 3 ) {
				// is external
				// is absolute path
				$file = wp_normalize_path( $feature );
			} else {
				// is feature
				// absolute feature path
				$file = wp_normalize_path( $feature_path . $feature );
			}

			// check if feature is active as plugin
			$maybe_plugin = str_replace( wp_normalize_path( WP_PLUGIN_DIR . '/' ), '', $file );

			if ( ! empty( $file ) && ! in_array( $maybe_plugin, $plugins ) && file_exists( $file ) ) {
				// include feature
				// debug($file);
				require_once $file;
			}
		}
	}


	/**
	 * =================================================================
	 *                          Utils
	 * =================================================================
	 */

	/**
	 * Get all active features.
	 *
	 * @return array
	 */
	public static function get_active_features() {

		$active_features = array();
		$all_features    = self::get_all_features();
		$_saved_features = self::get_saved_features();
		$saved_features  = array_merge(
			$_saved_features['global'] ?? array(),
			$_saved_features['site'] ?? array()
		);

		foreach ( $all_features as $feature ) {

			// skip if not valid
			if ( $feature['state'] !== 'valid' ) {
				continue;
			}

			// skip if disabled
			if ( $feature['Disabled'] ) {
				continue;
			}

			if (
				// or feature is forced
				$feature['Forced']
				// feature is set to active
				|| isset( $saved_features[ $feature['slug'] ] )
			) {
				$active_features[ $feature['slug'] ] = $feature['include'];
			}
		}
		return $active_features;
	}

	/**
	 * Get all available features, active and inactive.
	 * 
	 * @return array
	 */
	public static function get_all_features() {

		$path = wp_normalize_path( self::FEATURES_PATH );
		if ( ! file_exists( $path ) ) {
			return array();
		}

		// get files
		$files = self::get_feature_files();
		// debug($files);

		// get feature data
		foreach ( $files as &$file ) {

			// set features include path
			if ( strpos( $file['include'], '..' ) !== false ) {
				// convert relative path
				$abs = GREYD_PLUGIN_PATH . '/' . $file['include'];
				if ( realpath( $abs ) ) {
					$file['include'] = wp_normalize_path( realpath( $abs ) );
				}
			}

			// feature or plugin not found
			if ( ! realpath( $file['include'] ) ) {
				$file['state']    = 'not_found';
				$file['Priority'] = 999;
				continue;
			}

			$feature_data = self::get_feature_data( $file['include'], false, false );

			// register only if Plugin definitions are met
			if (
				$feature_data
				&& ! empty( $feature_data['Name'] )
				&& ! empty( $feature_data['Version'] )
				&& ! empty( $feature_data['Author'] )
			) {
				$file          = array_merge( $file, $feature_data );
				$file['state'] = 'valid';
			} else {
				$file['state'] = 'not_valid';
			}

			// default Priority
			if ( ! isset( $file['Priority'] ) || empty( $file['Priority'] ) ) {
				$file['Priority'] = 10;
			}

			// Hidden?
			$file['Hidden'] = isset( $file['Hidden'] ) ? $file['Hidden'] === 'true' : false;

			// Disabled?
			$file['Disabled'] = isset( $file['Disabled'] ) ? $file['Disabled'] === 'true' : false;

			// Forced?
			$file['Forced'] = isset( $file['Forced'] ) ? $file['Forced'] === 'true' : false;

			// Flag check - track if feature is alpha or beta
			$status = isset( $file['Flag'] ) ? strtolower( trim( $file['Flag'] ) ) : 'stable';
			if ( ! self::should_load_feature( $status ) ) {
				$file['state'] = 'not_valid';
			}
		}
		// debug($files);

		/**
		 * @filter 'greyd_features'
		 *
		 * @param array $files   All integrated Features.
		 *      @property string slug      Feature Slug (Filename or Foldername)
		 *      @property string include   Absolute (or relative to 'plugin_path') Path of the main Feature file to include.
		 *      @property int Priority     1-99 (default: 10).
		 */
		return (array) apply_filters( 'greyd_features', $files );
	}

	/**
	 * Get all active features.
	 *
	 * @return array   The full Settings-Object (global and site).
	 */
	public static function get_saved_features() {

		// delete_option( self::OPTION ); // debug

		$features = array(
			'global' => (array) get_site_option(
				self::OPTION_GLOBAL,
				self::get_default_features( 'global' )
			),
			'site'   => (array) get_option(
				self::OPTION,
				self::get_default_features( 'site' )
			),
		);
		return apply_filters( 'greyd_active_features', $features );
	}

	/**
	 * Get default features
	 */
	public static function get_default_features( $mode = 'site' ) {
		if ( $mode === 'global' ) {
			$features = array(
				'hub'         => 'hub/init.php',
				'connections' => 'connections/init.php',
			);
		} elseif ( $mode === 'site' ) {
			$features = array(
				'dynamic'        => 'dynamic/init.php',
				'posttypes'      => 'posttypes/init.php',
				'popups'         => 'popups/init.php',
				'query'          => 'query/init.php',
				'search'         => 'search/init.php',
				// forced features
				// 'license'        => 'license/init.php',
				// 'wizard'         => 'wizard/wizard.php',
				'icons'          => 'icons.php',
				'post-export'    => 'post-export/init.php',
				'pagespeed'      => 'pagespeed.php',
				'cookie-handler' => 'cookie-handler/init.php',
				'multiselects'   => 'multiselects/init.php',
				'icons'          => 'icons/icons.php',
				'blocks'         => 'blocks/init.php',
				// from blocks plugin
				'layout'         => 'layout/init.php',
				'trigger'        => 'trigger/init.php',
				'animations'     => 'animations/init.php',
				'aria-attributes' => 'aria-attributes/init.php',
				'lottie'         => 'lottie/init.php',
			);

			if ( Helper::is_greyd_classic() ) {
				$features['accessibility'] = 'accessibility.php';
				$features['comments']      = 'comments.php';
				$features['seo']           = 'seo.php';
				$features['search']        = 'search/init.php';
			}
		}

		return apply_filters( 'greyd_features_default_' . $mode, $features );
	}

	/**
	 * Remove template library for all sites that do not use the Classic Theme.
	 * Instead, force it in 'blueprint' mode (only user fullsite templates, no library).
	 * @filter 'greyd_features'
	 * 
	 * @since 2.17.6
	 * 
	 * @param array $files   All integrated Features.
	 * @return array $files  The filtered Features.
	 */
	public function force_template_library_in_non_classic_sites( $files ) {

		if ( Helper::is_greyd_classic() ) {
			return $files;
		}
	
		foreach ( $files as $key => $feature ) {
			$slug = is_array( $feature ) && isset( $feature['slug'] ) ? $feature['slug'] : $key;
			if ( $slug === 'library' ) {
				$files[ $key ]['Forced'] = true;
				break;
			}
		}
	
		return $files;
	}

	/**
	 * Get all available features files.
	 *
	 * @return array
	 */
	public static function get_feature_files() {

		$files = array();

		$path = wp_normalize_path( self::FEATURES_PATH );
		if ( ! file_exists( $path ) ) {
			return array();
		}

		// get files
		$results = scandir( $path );
		foreach ( $results as $result ) {
			if ( $result[0] == '.' ) {
				continue;
			}
			if ( is_dir( $path . $result ) ) {
				$init = '';
				if ( file_exists( $path . $result . '/init.php' ) ) {
					$init = '/init.php';
				} elseif ( file_exists( $path . $result . '/index.php' ) ) {
					$init = '/index.php';
				} elseif ( file_exists( $path . $result . '/' . $result . '.php' ) ) {
					$init = '/' . $result . '.php';
				}

				if ( ! empty( $init ) ) {
					array_push(
						$files,
						array(
							'slug'    => $result,
							'include' => $path . $result . $init,
						)
					);
				}
			} elseif ( preg_match( '~\.(php)$~', $result ) ) {
				array_push(
					$files,
					array(
						'slug'    => str_replace( '.php', '', $result ),
						'include' => $path . $result,
					)
				);
			}
		}

		/**
		 * @filter 'greyd_features_files'
		 *
		 * @param array $files   All integrated Features.
		 *      @property string slug      Feature Slug (Filename or Foldername)
		 *      @property string include   Absolute (or relative to 'plugin_path') Path of the main Feature file to include.
		 */
		return (array) apply_filters( 'greyd_features_files', $files );
	}

	/**
	 * Get Data from Feature File.
	 * Same as get_plugin_data, but with extended headers
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
	 * @see https://developer.wordpress.org/reference/functions/get_file_data/
	 *
	 * @param  string $feature_file    The full path the the main feature php.
	 * @param  bool   $markup          Render html-markup (default: true)
	 * @param  bool   $translate       Translate strings (default: true)
	 * @return array  $feature_data    Feature Data/Header Array
	 */
	public static function get_feature_data( $feature_file, $markup = true, $translate = true ) {

		if ( empty( $feature_file ) ) {
			return false;
		}
		if ( realpath( $feature_file ) == false ) {
			return false;
		}

		$default_headers = array(
			// default wp plugin headers
			'Name'        => 'Feature Name',
			'PluginURI'   => 'Plugin URI',
			'Version'     => 'Version',
			'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
			'Network'     => 'Network',
			'RequiresWP'  => 'Requires at least',
			'RequiresPHP' => 'Requires PHP',
			'UpdateURI'   => 'Update URI',
			// additional greyd sub plugin headers
			'RequiresHub' => 'Requires Features',
			'Priority'    => 'Priority',
			'Disabled'    => 'Disabled',
			'Hidden'      => 'Hidden',
			'Forced'      => 'Forced',
			'Flag'      => 'Flag',
		);

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$feature_data = get_file_data( $feature_file, $default_headers );
		$feature_data = _get_plugin_data_markup_translate( $feature_file, $feature_data, $markup, $translate );

		return $feature_data;
	}

	/**
	 * Check if a feature should be loaded based on its status
	 * 
	 * @param string $status The status of the feature (alpha, beta, stable)
	 * @return bool True if the feature should be loaded
	 */
	public static function should_load_feature( $status ) {
		switch ( strtolower( $status ) ) {
			case 'alpha':
				return \Greyd\Helper::is_greyd_alpha();
			case 'beta':
				// Beta features load if beta is enabled OR if alpha is enabled
				return \Greyd\Helper::is_greyd_beta() || \Greyd\Helper::is_greyd_alpha();
			case 'stable':
			default:
				return true;
		}
	}

	/**
	 * Check if Greyd Blocks Features should be loaded based on Version:
	 * <= 1.14.0: Features are loaded in greyd_blocks
	 * >= 1.15.0: Fearures are loaded here
	 * 
	 * Called before includes of the Features
	 * blocks, layout, trigger, animations and lottie
	 * to decide from where to include
	 * 
	 * @return bool
	 */
	public static function load_blocks_features() {

		// check version constant
		$blocks_version = GREYD_VERSION;
		if ( defined( 'GREYD_BLOCKS_VERSION' ) ) {
			// echo '<pre>'; print_r( GREYD_BLOCKS_VERSION ); echo '</pre>';
			$blocks_version = GREYD_BLOCKS_VERSION;
		}

		// sometimes the constant is defined too late
		if ( $blocks_version != GREYD_VERSION ) {
			// check theme
			// in theme version <= 1.8.5, the greyd_blocks plugin is forced
			// but maybe not yet in the active plugins
			$blocks_forced = false;
			// echo '<pre>'; print_r( wp_get_theme() ); echo '</pre>';
			if ( wp_get_theme()->get('Name') == 'GREYD.SUITE' ) {
				$theme_version = wp_get_theme()->get('Version');
				if ( version_compare( $theme_version, '1.8.5', '<=' ) ) {
					$blocks_forced = true;
				}
			}

			// check plugin
			// if greyd_blocks is forced or active we check the version manually
			// echo '<pre>'; print_r( Helper::active_plugins() ); echo '</pre>';
			if ( $blocks_forced || Helper::is_active_plugin('greyd_blocks/init.php') ) {
				if ( !function_exists( 'get_plugin_data' ) ) {
					require_once ABSPATH.'wp-admin/includes/plugin.php';
				}
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR."/greyd_blocks/init.php", false, false );
				// echo '<pre>'; print_r( $plugin_data ); echo '</pre>';
				$blocks_version = $plugin_data["Version"];
			}
		}
	
		if ( version_compare( $blocks_version, '1.14.0', '<=' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if feature is active.
	 * 
	 * @param string $feature	Feature slug
	 * @return bool
	 */
	public static function is_feature_active( $feature ) {
		$features = self::get_active_features();
		return isset($features[$feature]) && !empty($features[$feature]);
	}

	/**
	 * Save Feature Settings
	 *
	 * @param string $mode  Type of Setting to save (site or global).
	 * @param array  $value  Settings-Object with all values.
	 * @return bool         True if Option is updated, False otherwise.
	 */
	public static function update_features( $mode, $value ) {
		if ( $mode == 'site' ) {
			return update_option( self::OPTION, $value );
		}
		if ( $mode == 'global' ) {
			return update_site_option( self::OPTION_GLOBAL, $value );
		}
		return false;
	}

	/**
	 * Prepare features data for rendering.
	 *
	 * @return array
	 */
	public static function prepare_features() {

		$path = wp_normalize_path( self::FEATURES_PATH );
		if ( ! file_exists( $path ) ) {
			return array();
		}

		$files = self::get_all_features();

		// sort by name and Priority
		usort(
			$files,
			function( $a, $b ) {
				// Sort by prio
				$prio_a = intval( $a['Priority'] );
				$prio_b = intval( $b['Priority'] );
				if ( $prio_a > $prio_b ) {
					return 1; // move up
				} elseif ( $prio_a < $prio_b ) {
					return -1; // move down
				}

				// Sort by name
				$name_a = isset( $a['Name'] ) ? $a['Name'] : $a['slug'];
				$name_b = isset( $b['Name'] ) ? $b['Name'] : $b['slug'];
				return strcasecmp( $name_a, $name_b );
			}
		);
		// debug($files);

		// sort features in core|bundled|internal|external
		$features = array(
			'core'     => array(),
			'bundled'  => array(),
			'internal' => array(),
			'external' => array(),
		);

		foreach ( $files as &$file ) {
			if ( strpos( $file['include'], wp_normalize_path( WP_PLUGIN_DIR ) ) === 0 ) {
				// inside wp plugin directory
				if ( strpos( $file['include'], $path ) === 0 ) {
					// is feature inside greyd plugin directory
					$file['include'] = str_replace( $path, '', $file['include'] );
					if ( $file['state'] != 'valid' || $file['Author'] == 'Greyd' ) {
						// Author is Greyd, or feature not valid
						array_push( $features['core'], $file );
					} else {
						// any non-Greyd Author
						array_push( $features['bundled'], $file );
					}
				} else {
					// is wp Plugin - make relative url
					$file['include'] = str_replace( wp_normalize_path( WP_PLUGIN_DIR ), '..', $file['include'] );
					array_push( $features['internal'], $file );
				}
			} else {
				// not inside wp plugin directory
				if ( substr_count( $file['include'], '/' ) > 3 ) {
					// is absolute path
					array_push( $features['external'], $file );
				} else {
					// probably non valid
					if ( strpos( $file['include'], '..' ) !== false ) {
						// if has rel path, it is a non valid Plugin
						array_push( $features['internal'], $file );
					} else {
						// otherwise, it is a non valid Feature
						array_push( $features['core'], $file );
					}
				}
			}
		}
		// debug($features);

		return $features;
	}

	/*
	=======================================================================
		UTILITY FUNCTIONS
	=======================================================================
	*/

	/**
	 * Add test features for development fixes
	 *
	 * @param array $files
	 *
	 * @return array
	 */
	public function add_test_features( $files ) {

		// test: add normal (internal) plugin
		array_push(
			$files,
			array(
				// with absolute path
				'slug'    => 'advanced-wp-reset',
				'include' => wp_normalize_path( WP_PLUGIN_DIR . '/advanced-wp-reset/advanced-wp-reset.php' ),
			)
		);
		array_push(
			$files,
			array(
				// with relative path to plugin_dir
				'slug'    => 'enable-media-replace',
				'include' => '../enable-media-replace/enable-media-replace.php',
			)
		);
		array_push(
			$files,
			array(
				// with strange relative path to plugin_dir
				'slug'     => 'debug-bar',
				'include'  => '../../plugins/debug-bar/debug-bar.php',
				'Priority' => 1,
			)
		);

		// test: add remote (external) plugin
		array_push(
			$files,
			array(
				// dev: with correct path on dev server -> works, but very unstable
				'slug'    => 'editorplus',
				'include' => 'D:/web/greyd/web_rc2/wp-content/plugins/editorplus/index.php',
			)
		);

		// test: non valid
		array_push(
			$files,
			array(
				// feature not found
				'slug'    => 'mystery-feature',
				'include' => 'mystery-feature.php',
			)
		);
		array_push(
			$files,
			array(
				// plugin not found
				'slug'    => 'mystery-plugin',
				'include' => '../mystery-plugin/init.php',
			)
		);
		array_push(
			$files,
			array(
				// external plugin not found -> external urls are not allowed
				'slug'    => 'external-plugin-sample',
				'include' => 'http://mf23.net/exchange/external-plugin-sample/external-plugin-sample.php',
			)
		);

		// debug($files);
		return $files;
	}

	/**
	 * Add test features for development fixes
	 *
	 * @param array $files
	 *
	 * @return array
	 */
	public function add_core_plugins( $files ) {

		array_push(
			$files,
			array(
				'slug'    => 'greyd_tp_forms',
				'include' => '../greyd_tp_forms/init.php',
			)
		);

		// array_push(
		// 	$files,
		// 	array(
		// 		'slug'    => 'greyd_blocks',
		// 		'include' => '../greyd_blocks/init.php',
		// 	)
		// );

		array_push(
			$files,
			array(
				'slug'    => 'greyd_globalcontent',
				'include' => '../greyd_globalcontent/init.php',
			)
		);

		// debug($files);
		return $files;
	}


}
