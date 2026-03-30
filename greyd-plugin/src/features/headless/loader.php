<?php
/**
 * Example configurations for Headless Features.
 */
namespace Greyd\Headless;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Loader( $config );
class Loader {

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// read apis from json files
		add_action( 'init', array( $this, 'get_api_settings_json' ), 0 );

		/**
		 * Examples
		 */

		// // configure own rest api
		// add_action( 'init', array( $this, 'configure_my_rest_api' ), 10 );
		
	}

	/**
	 * Read APIs from json files in this dir.
	 * Use filter 'greyd_api_settings_json' to add external files.
	 */
	public function get_api_settings_json() {

		// get files
		$files = glob( __DIR__.'/examples/*.json', GLOB_BRACE);
		$files = apply_filters( 'greyd_api_settings_json', $files);
		// debug($files);

		foreach ( $files as $json ) {
			$json = Helper::url_to_abspath($json);
			if ( !file_exists($json) ) continue;
			$url = Helper::abspath_to_url(wp_normalize_path($json));

			$response = wp_remote_get(
				$url,
				array()
			);
			// debug($response['body']);
			$apis = json_decode($response['body'], true);
			// debug($apis);

			if ( json_last_error() == 0 ) {
				add_filter( 'greyd_api_settings', function( $settings ) use ( $apis ) {

					if ( !isset($settings['apis']) || !is_array($settings['apis']) ) {
						$settings['apis'] = array();
					}

					foreach ( $apis as $api_slug => $api ) {
						$settings['apis'][$api_slug] = $api;
					}

					return $settings;
				}, 0 );
			}

		}

	}

	/*
	=======================================================================
		Example: Configure own rest API
	=======================================================================
	*/

	public function configure_my_rest_api() {
		
		//
		// add rest endpoint settings

		add_filter( 'greyd_api_settings', function( $settings ) {
			
			$settings['basic'] = array(
				'disable_api'  => false,     // empty/false, 'public' or 'all'
				'hide_index'   => 'public',   // empty/false, 'public' or 'all'
				'modify_index' => array(    // empty/false or array
					'remove' => array(      // empty/false or array
						'url'  => 'all',             // 'public' or 'all'
						'home' => 'public',
						// 'icon' => 'false' // invalid
					),
					'add'    => array(
						'name'       => array(
							'show'  => 'all',        // 'loggedin' or 'all'
							'value' => 'Robo API',   // string/object/array
						),
						'extra_info' => array(
							'show'  => 'all',        // 'loggedin' or 'all'
							'value' => 'blahahbl',   // string/object/array
						),
						'more'       => array(
							'show'  => 'all',
							'value' => array(
								'hello' => 'world',
								'foo'   => 'bar',
							),
						),
						'ids'        => array(
							'show'  => 'loggedin',
							'value' => array( 1, 23, 54, 10456 ),
						),
					),
				),
			);

			$settings['posttypes'] = array(           // empty/false or array
				'hide' => array(
					// 'post' => 'all',             // 'public' or 'all'
					'page' => 'public',
				),
				'show' => array(
					'wp_global_styles' => 'all',    // 'loggedin' or 'all'
					'tp_posttypes'     => 'loggedin',
				),
			);

			$settings['taxonomies'] = array(          // empty/false or array
				'hide' => array(
					'category'            => 'all',            // 'public' or 'all'
					'post_tag'            => 'all',
					'template_categories' => 'public',
				),
				'show' => array(
					'wp_theme' => 'all',            // 'loggedin' or 'all'
				),
			);

			$settings['endpoints'] = array(           // empty/false or array
				'remove' => array(
					'/yoast/v1*' => 'all',          // 'public' or 'all'
					'/wp/v2*'    => 'public',
				),
				'add'    => array(
					'custom/v0' => array(
						'show'   => 'loggedin',       // 'loggedin' or 'all'
						'routes' => array(
							'/data',
							'/data/(?P<id>[\d]+)',
							'/infos',
						),
					),
					'greyd/v1'  => array(
						'show'   => 'all',            // 'loggedin' or 'all'
						'routes' => array(
							'/more',
							'/and-then-some',
						),
					),
				),
			);

			return $settings;
		} );

		//
		// rest endpoint filter

		add_filter( 'greyd_rest_api_custom_v0_infos', function( $result ) {
			$result['hello']       = 'world';
			$result['title']       = 'my custom api';
			$result['description'] = 'this is how you get data ...';
			return $result;
		} );

		add_filter( 'greyd_rest_api_custom_v0_data', function( $result ) {
			$result = Helper::get_all_posts( 'post' );
			return $result;
		} );

		add_filter( 'greyd_rest_api_custom_v0_data_(?P<id>[\d]+)', function( $result, $request ) {
			if ( isset( $request['id'] ) ) {
				$post = get_post( intval( $request['id'] ) );
				if ( $post ) {
					return $post;
				}
			}
			return $result;
		}, 10, 2 );

	}

}