<?php
/**
 * Admin functions to manage Headless Features.
 */
namespace Greyd\Headless;

use Greyd\Helper as Helper;
use Greyd\Settings as Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Admin( $config );
class Admin {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Standalone mode.
	 *
	 * @var bool
	 */
	public static $is_standalone = false;

	public static $is_logged_in = false;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// set config
		$this->config = (object) $config;

		// standalone mode
		if ( isset( $this->config->is_standalone ) && $this->config->is_standalone == true ) {
			self::$is_standalone = true;
		}

		// handle settings
		if ( self::$is_standalone ) {
			// standalone mode
			// todo: settings handling without hub
		} else {
			// in hub
			add_filter( 'greyd_settings_default_site', array( $this, 'add_greyd_settings' ) );
			add_action( 'greyd_ajax_mode_fetch_api', array( $this, 'fetch_api' ) );
		}

		add_action( 'init', function() {
			// logged in (public or not)
			self::$is_logged_in = is_user_logged_in();
			// debug(self::$is_logged_in ? 'true' : 'false');
		}, 0 );

		// setup rest api
		add_filter( 'rest_authentication_errors', array( $this, 'disable_api' ), 0 );
		add_filter( 'rest_index', array( $this, 'hide_api_index' ), 9999, 2 );
		add_action( 'init', array( $this, 'disable_api_posttype_endpoints' ), 9999 );
		add_filter( 'rest_endpoints', array( $this, 'disable_api_endpoints' ), 9999 );
		add_action( 'rest_api_init', array( $this, 'add_api_endpoints' ), 99 );

	}

	/*
	=======================================================================
		settings
	=======================================================================
	*/

	/**
	 * Add default settings if Hub is installed.
	 *
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_greyd_settings( $settings ) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults()
		);
		return $settings;
	}

	/**
	 * default settings.
	 *
	 * @return array    The default (empty) settings array.
	 */
	public static function get_defaults() {

		/**
		 * Filter the headless application settings.
		 * 
		 * @param array $settings The default settings.
		 *   @property array basic        Basic settings.
		 *     @property string disable_api  Disable the API for all, public or logged in users.
		 *     @property string hide_index   Hide the index for all, public or logged in users.
		 *     @property array  modify_index Modify the index by removing or adding items.
		 *   @property array posttypes    Posttype settings.
		 *     @property array  hide         Hide posttypes for all, public or logged in users.
		 *     @property array  show         Show posttypes for all, public or logged in users.
		 *   @property array taxonomies   Taxonomy settings.
		 *     @property array  hide         Hide taxonomies for all, public or logged in users.
		 *     @property array  show         Show taxonomies for all, public or logged in users.
		 *   @property array endpoints    Endpoint settings.
		 *     @property array  remove       Remove endpoints for all, public or logged in users.
		 *     @property array  add          Add endpoints for all, public or logged in users.
		 *   @property array apis         Different APIs keyed by name with the following settings:
		 *     @property string title        The title of the API.
		 *     @property string slug         The slug of the API.
		 *     @property string base_url     The base URL of the API.
		 *     @property string url_path     The URL path of the API.
		 *     @property array  url_atts     URL attributes.
		 *     @property array  headers      Headers to be sent with the request.
		 *     @property string method       The request method.
		 *     @property array  routes       Routes to be added.
		 *     @property array  blocks       Blocks to be added.
		 *     @property array  posttypes    Posttypes to be added.
		 * 
		 * @return array          The filtered settings.
		 */
		$settings = apply_filters( 'greyd_api_settings', array(
			'basic'      => '',
			'posttypes'  => '',
			'taxonomies' => '',
			'endpoints'  => '',
			'apis'       => '',
		) );

		return array(
			'api' => $settings,
		);

	}

	/**
	 * Hold cached settings of get_settings function.
	 *
	 * @var array
	 */
	private static $_settings_cache = array();

	/**
	 * get settings.
	 *
	 * @param string $type      Subsettings "api", "basic", "posttypes", "taxonomies", "endpoints" or "apis".
	 * @return array            The settings array.
	 */
	public static function get_settings( $type ) {

		// check type
		if ( !in_array( $type, array( 'api', 'basic', 'posttypes', 'taxonomies', 'endpoints', 'apis' ) ) ) {
			return false;
		}

		// // mode
		// if ( !$mode ) {
		// $mode = ( is_multisite() && is_network_admin() ) ? 'global' : 'site';
		// debug("get ".$mode);
		// }

		$mode = 'site';

		// get cache
		if ( isset( self::$_settings_cache[ $mode . '_' . $type ] ) ) {
			return self::$_settings_cache[ $mode . '_' . $type ];
		}

		// get settings
		if ( $type == 'api' ) {
			$settings = Settings::get_setting( array( $mode, $type ) );
			foreach ( array_keys( $settings ) as $key ) {
				if ( is_string( $settings[ $key ] ) ) {
					$settings[ $key ] = unserialize( $settings[ $key ] );
				}
			}
		} else {
			$settings = Settings::get_setting( array( $mode, 'api', $type ) );
			if ( is_string( $settings ) ) {
				$settings = unserialize( $settings );
			}
		}

		// examples and filtered settings
		$dev = $type == 'api' ? self::get_defaults()[ $type ] : self::get_defaults()['api'][ $type ];
		if ( isset( $dev ) && json_encode( $settings ) !== json_encode( $dev ) ) {
			self::save_settings( $type, $dev );
			$settings = $dev;
		}

		// set cache
		self::$_settings_cache[ $mode . '_' . $type ] = $settings;
		return $settings;

	}

	/**
	 * save settings.
	 *
	 * @param string $type      Subsettings "api", "basic", "posttypes", "taxonomies", "endpoints" or "apis".
	 * @param array  $settings   The settings array to be saved.
	 * @return bool             true on successful update, false on failure.
	 */
	public static function save_settings( $type, $settings ) {

		// check type
		if ( !in_array( $type, array( 'api', 'basic', 'posttypes', 'taxonomies', 'endpoints', 'apis' ) ) ) {
			return false;
		}

		// // mode
		// $mode          = ( is_multisite() && is_network_admin() ) ? 'global' : 'site';
		// $settings_mode = $mode == 'global' ? 'global_network' : $mode;
		// // debug("save global".$mode);

		$mode = 'site';

		// update settings
		if ( $type == 'api' ) {
			foreach ( array_keys( $settings ) as $key ) {
				$settings[ $key ] = serialize( $settings[ $key ] );
			}
			$result = Settings::update_setting( $mode, array( $mode, $type ), $settings );
		} else {
			$settings = serialize( $settings );
			$result   = Settings::update_setting( $mode, array( $mode, 'api', $type ), $settings );
		}
		// reload theme
		Settings::reload_settings();
		// reset cache
		self::$_settings_cache = array();
		return $result;

	}

	/*
	=======================================================================
		call api
	=======================================================================
	*/

	/**
	 * Ajax function to proxy api requests.
	 *
	 * @action 'greyd_ajax_mode_'
	 *
	 * @param array $post_data   $_POST data.
	 */
	public function fetch_api( $post_data ) {

		// echo "\r\n\r\n";
		// debug($post_data);

		$data = json_decode( rawurldecode( $post_data ), true );

		echo "\r\n\r\n";
		echo 'CALLING API';
		echo "\r\n\r\n";
		debug( $data );
		echo "\r\n\r\n";

		echo "\r\n\r\n" . '------------- debug end -------------' . "\r\n\r\n";

		// call
		$response = Api_Helper::remote_get( $data );

		// convert
		if ( isset( $data['block'] ) ) {
			// echo "\r\n\r\n";
			// debug($data['block']);
			// echo "\r\n\r\n";
			$response = Api_Helper::convert_response( $response, $data );
		}
		else if ( isset($data['posttype']) ) {
			// echo "\r\n\r\n";
			// debug($data['posttype']);
			// echo "\r\n\r\n";
			$response = Api_Helper::convert_response( $response, $data, 'posttype' );
		}

		// respond
		if ( $response['status'] === 'success' ) {
			echo 'success::' . rawurlencode( $response['body'] );
		} elseif ( $response['status'] === 'error' ) {
			echo 'error::' . $response['body'];
		} else {
			echo 'error::unidentified error';
		}

		// end ajax request
		die();

	}

	/*
	=======================================================================
		configure own rest api
	=======================================================================
	*/

	public static function is_allowed( $permission ) {
		if (
			$permission == 'all' ||
			( $permission == 'public' && ! self::$is_logged_in ) ||
			( $permission == 'loggedin' && self::$is_logged_in )
		) {
			return true;
		}
		return false;
	}

	public function disable_api( $access ) {

		$settings = self::get_settings( 'api' );
		if ( ! isset( $settings['basic'] ) ||
			! isset( $settings['basic']['disable_api'] ) ||
			$settings['basic']['disable_api'] === false
		) {
			return $access;
		}

		// debug($settings);
		// debug($access);

		if ( self::is_allowed( $settings['basic']['disable_api'] ) ) {
			// fully deny access
			$access = new \WP_Error(
				'rest_cannot_access',
				'no access',
				array(
					'status' => 403,
				)
			);
		}

		return $access;

	}

	public function hide_api_index( $response, $request ) {

		$settings = self::get_settings( 'api' );
		if ( ! isset( $settings['basic'] ) ||
			empty( $settings['basic'] )
		) {
			return $response;
		}

		// debug($settings);
		// debug($response);

		if ( isset( $settings['basic']['hide_index'] ) && self::is_allowed( $settings['basic']['hide_index'] ) ) {
			// fully hide index
			$response       = new \WP_REST_Response();
			$response->data = array();
		}

		if ( ! empty( $response->data ) && isset( $settings['basic']['modify_index'] ) ) {
			if ( isset( $settings['basic']['modify_index']['remove'] ) &&
				is_array( $settings['basic']['modify_index']['remove'] )
			) {
				// remove items from index
				foreach ( $settings['basic']['modify_index']['remove'] as $remove => $value ) {
					if ( isset( $response->data[ $remove ] ) && self::is_allowed( $value ) ) {
						unset( $response->data[ $remove ] );
					}
				}
			}
			if ( isset( $settings['basic']['modify_index']['add'] ) &&
				is_array( $settings['basic']['modify_index']['add'] )
			) {
				// add items to index
				foreach ( $settings['basic']['modify_index']['add'] as $add => $value ) {
					if ( self::is_allowed( $value['show'] ) ) {
						$response->data[ $add ] = $value['value'];
					}
				}
			}
		}

		return $response;

	}

	public function disable_api_posttype_endpoints() {

		if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === Api_Page::$page['slug'] ) {
			return;
		}

		$settings = self::get_settings( 'api' );
		// debug($settings);

		if ( isset( $settings['posttypes'] ) && is_array( $settings['posttypes'] ) ) {

			global $wp_post_types;
			foreach ( $wp_post_types as $slug => $posttype ) {
				// echo $slug."\n";
				if ( isset( $settings['posttypes']['hide'][ $slug ] ) && self::is_allowed( $settings['posttypes']['hide'][ $slug ] ) ) {
					// hide posttype in api
					$wp_post_types[ $slug ]->show_in_rest = false;
				}

				if ( isset( $settings['posttypes']['show'][ $slug ] ) && self::is_allowed( $settings['posttypes']['show'][ $slug ] ) ) {
					// show posttype in api
					$wp_post_types[ $slug ]->show_in_rest = true;
				}
			}
		}

		if ( isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] ) ) {

			global $wp_taxonomies;
			foreach ( $wp_taxonomies as $slug => $taxonomy ) {
				// echo $slug."\n";
				if ( isset( $settings['taxonomies']['hide'][ $slug ] ) && self::is_allowed( $settings['taxonomies']['hide'][ $slug ] ) ) {
					// hide taxonomy in api
					$wp_taxonomies[ $slug ]->show_in_rest = false;
				}
				if ( isset( $settings['taxonomies']['show'][ $slug ] ) && self::is_allowed( $settings['taxonomies']['show'][ $slug ] ) ) {
					// show taxonomy in api
					$wp_taxonomies[ $slug ]->show_in_rest = true;
				}
			}
		}

	}

	public function disable_api_endpoints( $endpoints ) {

		$settings = self::get_settings( 'api' );
		if ( ! isset( $settings['endpoints'] ) ||
			! isset( $settings['endpoints']['remove'] ) ||
			empty( $settings['endpoints']['remove'] )
		) {
			return $endpoints;
		}

		// debug($settings);
		// debug($endpoints);
		$namespaces = array();
		foreach ( $settings['endpoints']['remove'] as $route => $value ) {
			if ( isset( $endpoints[ $route ] ) && self::is_allowed( $value ) ) {
				// remove single route
				unset( $endpoints[ $route ] );
			} elseif ( strpos( $route, '*' ) === strlen( $route ) - 1 ) {
				// remove route and subroutes (namespace)
				$namespaces[ substr( $route, 0, -1 ) ] = $value;
			}
		}
		if ( ! empty( $namespaces ) ) {
			// debug($namespaces);
			foreach ( $endpoints as $route => $handlers ) {
				// echo $route."\n";
				foreach ( $namespaces as $ns => $value ) {
					if ( strpos( $route, $ns ) === 0 && self::is_allowed( $value ) ) {
						// remove route of namespace
						unset( $endpoints[ $route ] );
					}
				}
			}
		}

		return $endpoints;

	}

	public function add_api_endpoints( $server ) {

		$settings = self::get_settings( 'api' );
		if ( ! isset( $settings['endpoints'] ) ||
			! isset( $settings['endpoints']['add'] ) ||
			empty( $settings['endpoints']['add'] )
		) {
			return;
		}

		// debug($settings);
		// debug($server);
		foreach ( $settings['endpoints']['add'] as $namespace => $value ) {
			if ( self::is_allowed( $value['show'] ) ) {
				// debug($route);
				foreach ( $value['routes'] as $route ) {
					// add route
					register_rest_route(
						$namespace,
						$route,
						array(
							'methods'             => 'GET',
							'callback'            => function( $request ) use ( $namespace, $route ) {
								// debug($request);
								// debug('greyd_rest_'.str_replace('/', '_', $namespace.$route));
								// make filter to populate data in callback
								$data = apply_filters( 'greyd_rest_api_' . str_replace( '/', '_', $namespace . $route ), array(), $request );
								return rest_ensure_response( $data );
							},
							'permission_callback' => '__return_true',
						)
					);
				}
			}
		}

	}

}
