<?php
/*
Feature Name:   Cookie Handler
Description:    Supported standard cookies
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Forced:         true
*/

namespace Greyd\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * disable if plugin wants to run standalone
 */
if ( ! class_exists( 'Greyd\Admin' ) ) {
	// reject activation
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die( sprintf( '%s can not be activated as standalone Plugin.', $plugin_name ) );
}

/**
 * Class Cookie_Handler
 * This class sets all supported url params as cookies and exposes them via static methods.
 * 
 * @package Greyd\Extensions
 */
new Cookie_Handler();
class Cookie_Handler {

	/**
	 * Holds supported cookie values.
	 * 
	 * @var array
	 */
	public static $cookies = array();

	/**
	 * Holds all cookie values.
	 * 
	 * @var array
	 */
	public static $all_cookies = array();

	/**
	 * Holds supported url param values.
	 * 
	 * @var array
	 */
	public static $url_params = array();

	/**
	 * Holds all url param values.
	 * 
	 * @var array
	 */
	public static $all_url_params = array();

	/**
	 * Class constructor
	 */
	function __construct() {
		// save url params as cookies
		add_action( 'parse_request', array( $this, 'init_values' ), 10 );
		// Frontend script
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend' ) );
		// endpoint for urlparams and cookie values
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}
	
	/**
	 * Init all the class vars.
	 * 
	 * @example ?utm_campaign=testcampaign&utm_source=testsource&utm_medium=testmedium&utm_content=testcontent&utm_term=testterm
	 */
	public function init_values() {

		if ( is_admin() ) return;

		/**
		 * Disable Session Cookie via Filter or Constant.
		 * @since 1.7.0
		 * 
		 * @filter greyd_handle_session_cookies
		 * 
		 * @return bool
		 */
		$disable_session_cookies = apply_filters( 'greyd_disable_session_cookies', defined( 'GREYD_DISABLE_SESSION_COOKIES' ) && GREYD_DISABLE_SESSION_COOKIES );

		if ( ! $disable_session_cookies ) {

			$days   = 1; // after how many days the cookie expires
			$time   = time() + 60 * 60 * 24 * $days; // currenttime * sec * min * hours * days
			$params = self::get_supported_params();
			// debug($params);
	
			// start the session
			if ( ! isset( $_SESSION ) ) {
				session_set_cookie_params( $time, '/' );
				session_start();
			} else {
				setcookie( session_name(), session_id(), time() + $time );
			}
		}

		foreach ( $params as $i => $param ) {

			$name   = $param['name'];
			$url    = $param['url'];
			$cookie = $param['cookie'];

			// URL parameter has priority
			if ( isset( $_GET[ $url ] ) ) {

				$value = $_GET[ $url ];

				/**
				 *  @filter 'greyd_set_url_param-{{param_name}}'
				 *
				 *  manipulate value of url-param
				 */
				$value = apply_filters( 'greyd_set_url_param-' . $name, $value );

				if ( ! $disable_session_cookies ) {
					// check if session_param already exists
					if ( isset( $_SESSION[ $cookie ] ) ) {
						// is session_param still the same?
						if ( $_SESSION[ $cookie ] !== $value ) {
							$_SESSION[ $cookie ] = $value;
						}
					} else {
						// else set session_param
						$_SESSION[ $cookie ] = $value;
					}
				}

				// save in cookies & url_params var
				self::$url_params[ $name ] = $value;
				if ( ! $disable_session_cookies ) {
					self::$cookies[ $name ]    = $value;
				}
			}
			// look for cookie
			else if ( isset( $_SESSION ) && isset( $_SESSION[ $cookie ] ) ) {
				if ( ! $disable_session_cookies ) {
					self::$cookies[ $name ] = $_SESSION[ $cookie ];
				}
			}
			if ( empty(self::$cookies[ $name ]) ) {
				// check $_COOKIE variable
				if ( isset( $_COOKIE ) && isset( $_COOKIE[ $cookie ] ) ) {
					self::$cookies[ $name ] = $_COOKIE[ $cookie ];
				}
			}
		}

		if ( ! $disable_session_cookies ) {
			// self::$all_cookies = (array) $_SESSION;
			self::$all_cookies = array_merge( (array)$_SESSION, (array)$_COOKIE );
		}

		self::$all_url_params = (array) $_GET;
	}

	/**
	 * Get all supported cookie & url params.
	 * 
	 * @return array
	 */
	public static function get_supported_params() {
		/**
		 * @filter greyd_cookies_supported_values
		 * 
		 * @return array
		 */
		$params = apply_filters( 'greyd_cookies_supported_values', array(
			array(
				'nicename' => __( 'Google Campaign Name (utm_campaign)', 'greyd_hub' ),
				'name'     => 'utm_campaign',
				'url'      => 'utm_campaign',
				'cookie'   => 'utm_campaign',
			),
			array(
				'nicename' => __( 'Google Campaign Source (utm_source)', 'greyd_hub' ),
				'name'     => 'utm_source',
				'url'      => 'utm_source',
				'cookie'   => 'utm_source',
			),
			array(
				'nicename' => __( 'Google Campaign Medium (utm_medium)', 'greyd_hub' ),
				'name'     => 'utm_medium',
				'url'      => 'utm_medium',
				'cookie'   => 'utm_medium',
			),
			array(
				'nicename' => __( 'Google Campaign Term (utm_term)', 'greyd_hub' ),
				'name'     => 'utm_term',
				'url'      => 'utm_term',
				'cookie'   => 'utm_term',
			),
			array(
				'nicename' => __( 'Google Campaign Content (utm_content)', 'greyd_hub' ),
				'name'     => 'utm_content',
				'url'      => 'utm_content',
				'cookie'   => 'utm_content',
			),
		) );
		/** @deprecated greyd_add_url_params */
		$params = apply_filters( 'greyd_add_url_params', $params );
		return (array) $params;
	}

	/**
	 * GET default values stored as cookies
	 */
	public static function get_cookie_values() {
		return self::$cookies;
	}

	/**
	 * GET default url-param values
	 */
	public static function get_url_values() {
		return self::$url_params;
	}

	/**
	 * GET all cookie values
	 */
	public static function get_all_cookie_values() {
		return self::$all_cookies;
	}

	/**
	 * GET all url-param values
	 */
	public static function get_all_url_values() {
		return self::$all_url_params;
	}

	
	/**
	 * Register frontend script.
	 *
	 * @action 'wp_enqueue_scripts'
	 */
	public function register_frontend() {

		wp_register_script(
			'greyd-cookie-handler-frontend-script',
			plugin_dir_url( __FILE__ ).'frontend.js',
			null,
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script( 'greyd-cookie-handler-frontend-script' );

	}

	/**
	 * Register rest endpoint to get all url params and cookie values.
	 *
	 * @action 'rest_api_init'
	 */
	public function register_route() {

		register_rest_route(
			GREYD_REST_NAMESPACE,
			'/get_url_and_cookie_values',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $request ) {
					// get request params
					$request_params = $request->get_json_params();
					
					if ( isset( $request_params['urlparams'] ) && !empty( $request_params['urlparams'] ) ) {
						// override url_params and init values
						$_GET = $request_params['urlparams'];
					}

					$this->init_values();

					// return all url params and cookie values
					return array(
						"url_values" => self::get_url_values(),
						"cookie_values" => self::get_cookie_values(),
						"all_url_values" => self::get_all_url_values(),
						"all_cookie_values" => self::get_all_cookie_values()
					);
				},
				'permission_callback' => '__return_true',
			)
		);

	}

}
