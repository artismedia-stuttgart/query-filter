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

new Api_Helper( $config );
class Api_Helper {

	/**
	 * Get the config of an API or API endpoint.
	 * 
	 * @param string $slug  The slug of the API, e.g. 'myapi' or 'myapi/fetch-items'.
	 * 
	 * @return array        The config of the API.
	 *   @property string slug     The slug of the API.
	 *   @property string title    The title of the API.
	 *   @property string base_url The base url of the API.
	 *   @property string url_path The path of the API.
	 *   @property array  url_atts The attributes of the API.
	 *   @property array  headers  The headers of the API.
	 *   @property bool   wp_cache  The cache settings of the API.
	 *   @property string method   The method of the API.
	 *   @property array  actions  The actions to perform on the response.
	 *   @property array  block    The block config.
	 *   @property array  posttype The posttype config.
	 * 
	 * @return array      The config of the API endpoint.
	 * @return null       If the API is not found.
	 */
	public static function get_api( $slug ) {
		$api      = null;
		$settings = Admin::get_settings( 'api' );
		if ( isset( $settings['apis'] ) && is_array( $settings['apis'] ) && count( $settings['apis'] ) > 0 ) {
			// search settings
			$slugs = explode( '/', $slug );
			foreach ( $settings['apis'] as $details ) {
				// debug($details);
				if ( $details['slug'] == $slugs[0] ) {
					$api = array(
						'slug'     => $slug,
						'title'    => $details['title'],
						'base_url' => $details['base_url'],
						'url_path' => $details['url_path'],
						'url_atts' => is_array( $details['url_atts'] ) ? $details['url_atts'] : array(),
						'headers'  => is_array( $details['headers'] ) ? $details['headers'] : array(),
						'wp_cache' => isset( $details['wp_cache'] ) ? $details['wp_cache'] : false,
						'method'   => isset( $details['method'] ) ? $details['method'] : 'GET',
					);
					if ( count( $slugs ) > 1 ) {
						$route_set = false;
						if ( isset( $details['routes'] ) && $details['slug'] !== $slugs[1] ) {
							foreach ( $details['routes'] as $route ) {
								if ( $route['slug'] == $slugs[1] ) {
									$api['url_path'] = $route['url_path'];
									$api['url_atts'] = array_merge( ( isset($api['url_atts']) ? $api['url_atts'] : array() ), ( isset($route['url_atts']) && is_array( $route['url_atts'] ) ? $route['url_atts'] : array() ) );
									$api['headers']  = array_merge( ( isset($api['headers']) ? $api['headers'] : array() ), ( isset($route['headers']) && is_array( $route['headers'] ) ? $route['headers'] : array() ) );
									$api['wp_cache'] = isset( $route['wp_cache'] ) ? $route['wp_cache'] : $api['wp_cache'];
									$api['method']   = isset( $route['method'] ) ? $route['method'] : $api['method'];
									$api['actions']  = isset( $route['actions'] ) ? $route['actions'] : array();
									$route_set = true;
									break;
								}
							}
						}

						if ( isset( $details['blocks'] ) && ! empty( $details['blocks'] ) ) {
							foreach ( $details['blocks'] as $api_block ) {
								if ( $api_block['route'] == $slugs[1] ) {
									$api['block'] = $api_block;
									break;
								}
							}
						}

						if ( isset( $details['posttypes'] ) && ! empty( $details['posttypes'] ) ) {
							foreach ( $details['posttypes'] as $api_posttype ) {
								if (
									isset( $api_posttype['api_settings'] ) &&
									isset( $api_posttype['api_settings']['route'] ) &&
									$api_posttype['api_settings']['route'] == $slugs[1]
								) {

									/**
									 * @property array posttype_settings
									 *   @property string slug        The slug of the posttype.
									 *   @property string title       The title of the posttype.
									 *   @property bool   is_taxonomy If the posttype is a taxonomy.
									 *   @property string singular    The singular name of the posttype.
									 *   @property string plural      The plural name of the posttype.
									 *   @property string icon        The icon of the posttype.
									 *   @property int    position    The position of the posttype.
									 *   @property bool   categories  If the posttype has categories.
									 *   @property array  arguments   The arguments of the posttype.
									 *     @property string search  Whether the posttype is searchable.
									 *     @property string archive Whether the posttype has an archive.
									 *   @property array  supports    The supports of the posttype.
									 *     @property string thumbnail   Whether the posts support a thumbnail.
									 *     @property string excerpt     Whether the posts support the excerpt.
									 *     @property string custom_taxonomies Whether the posts support custom taxonomies.
									 *   @property array  custom_taxonomies The custom taxonomies of the posttype.
									 *     @property string slug     The slug of the taxonomy.
									 *     @property string singular The singular name of the taxonomy.
									 *     @property string plural   The plural name of the taxonomy.
									 *   @property array  fields      The custom fields of the posttype.
									 *     @property string name      The name of the field.
									 *     @property string type      The type of the field.
									 *     @property string label     The label of the field.
									 * @property array api_settings
									 *   @property string route     The route to call.
									 *   @property array  vars      The variables to replace in the route.
									 *   @property string data_prop The property in the response to use to generate the posts.
									 *   @property array  data_item Map of the post properties to the response properties.
									 *     @property string post_title The title of the post.
									 *     @property string post_name  The name of the post.
									 *     @property string post_date  The date of the post.
									 *     ...
									 *     @property array  meta_input The meta input of the post.
									 *     @property array tax_input  The tax input of the post.
									 */
									$api['posttype'] = $api_posttype;
									break;
								}
							}
						}

						if ( !$route_set ) $api = false;
					}
					break;
				}
			}
		}
		return $api;
	}

	/**
	 * Fetch data from an API.
	 * 
	 * @param array $api         The API config.
	 * @param bool  $public      If the call is public or not.
	 * @param bool  $convert_xml If the response should be converted from XML to JSON.
	 * 
	 * @return array
	 *   @property string status  The status of the call, 'success' or 'error'.
	 *   @property string body    The encoded body of the response.
	 *   @property string type    The content type of the response, e.g. 'application/json'.
	 *   @property object full    The raw response object.
	 */
	public static function remote_get( $api, $public = true, $convert_xml = true ) {

		if ( !$api || empty( $api ) ) {
			return array(
				'status' => 'error',
				'body'   => 'invalid API',
				'full'   => array(),
			);
		}

		$api = wp_parse_args(
			$api,
			array(
				'slug'     => '',
				'url'      => '',
				'base_url' => '', // optional
				'url_path' => '', // optional
				'url_atts' => '', // optional
				'headers'  => array(),
				'wp_cache' => false,
				'method'   => 'GET',
				'actions'  => array()
			)
		);

		// get call url
		if ( empty( $api['url'] ) || ! is_string( $api['url'] ) ) {
			if ( ! empty( $api['base_url'] ) ) {
				$api['url'] = self::make_url( $api );
			}
		}
		if ( empty( $api['url'] ) || ! is_string( $api['url'] ) ) {
			return array(
				'status' => 'error',
				'body'   => 'invalid URL',
				'full'   => array(),
			);
		}

		// debug($api);

		// insert vars
		$api_vars = isset( $api['vars'] )? $api['vars'] : array();
		if ( empty( $api_vars ) && isset( $api['block'] ) && isset( $api['block']['vars'] ) ) {
			$api_vars = $api['block']['vars'];
		}
		else if ( empty( $api_vars ) && isset( $api['posttype'] ) && isset( $api['posttype']['vars'] ) ) {
			$api_vars = $api['posttype']['vars'];
		}
		if ( !empty( $api_vars ) ) {
			foreach ( $api_vars as $var => $value ) {
				// dynamic var from request param
				$clean_var = trim( $var, '{}' );
				if ( isset( $_REQUEST[ $clean_var ] ) ) {
					$value = $_REQUEST[ $clean_var ];
					$api_vars[ $var ] = $value;
				}
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				$api['url'] = str_replace( $var, $value, $api['url'] );
			}
		}

		// headers
		if ( is_array( $api['headers'] ) ) {
			foreach ( $api['headers'] as $key => $value ) {
				if ( $key == 'Authorization' && strpos( $value, 'Basic ' ) === 0 ) {
					$api['headers'][ $key ] = 'Basic ' . base64_encode( explode( 'Basic ', $value, 2 )[1] );
				}
			}
		}

		// cookies (for non-public calls)
		$cookies = array();
		if ( $public === false || ( isset( $api['public'] ) && $api['public'] === false ) ) {
			$tmp = explode( '; ', $_SERVER['HTTP_COOKIE'] );
			foreach ( $tmp as $cookie ) {
				$val                = explode( '=', $cookie );
				$cookies[ $val[0] ] = $val[1];
			}
			// debug($cookies);
		}

		// body - for method: POST
		$postdata = array();
		if ( $api['method'] == 'POST' ) {
			$api['url'] = $api['base_url'];
			// url path
			if ( !empty( $api['url_path'] ) ) {
				$api['url'] .= '/'.trim( $api['url_path'], '/' );
			}
			// url attributes
			if ( isset( $api['url_atts'] ) && !empty( $api['url_atts'] ) && is_array( $api['url_atts'] ) ) {
				$api['url'] = add_query_arg( $api['url_atts'], $api['url'] );
				foreach ( $api['url_atts'] as $key => $value ) {
					$postdata[$key] = $value;
				}
			}
			// debug($postdata);
		}

		// cache values
		$wp_cache = false;
		if ( $api['wp_cache'] && $api['wp_cache'] && ! empty( $api['wp_cache'] ) ) {

			$wp_cache = array(
				'enabled' => true,
				'expire'  => 60 * 60 * 24, // 1 day
			);

			if ( is_numeric( $api['wp_cache'] ) ) {
				$wp_cache['expire'] = $api['wp_cache'];
			} elseif ( is_array( $api['wp_cache'] ) ) {
				$wp_cache = wp_parse_args( $api['wp_cache'], $wp_cache );
			}

			if ( $wp_cache['enabled'] ) {
				$_cached_value = get_transient( $api['url'] );
				if ( $_cached_value !== false ) {
					// debug( 'cached value found for ' . $api['url'] );
					return $_cached_value;
				}
				// debug( 'no cached value found for ' . $api['url'] );
			}
		}

		// echo "\r\n\r\n";
		// debug( $api );
		// echo "\r\n\r\n";

		/**
		 * Filter the endpoint before the call.
		 * 
		 * @filter  greyd_api_before_{api-slug/enpoint-name}
		 * @example greyd_api_before_myapi/fetch-items
		 * 
		 * @param array $call  The call to make.
		 *   @property string url     The full url of the call.
		 *   @property array  params  The parameters of the call.
		 *     @property string method  The method of the call.
		 *     @property int    timeout The timeout of the call.
		 *     @property array  cookies The cookies of the call.
		 *     @property array  headers The headers of the call.
		 *     @property array  body    The body of the call.
		 * @param array  $api        The api config.
		 *   @property string slug     The slug of the api.
		 *   @property string url      The full url of the call.
		 *   @property string base_url The base url of the call.
		 *   @property string url_path The path of the call.
		 *   @property array  url_atts The attributes of the call.
		 *   @property array  headers  The headers of the call.
		 *   @property bool   wp_cache  The cache settings of the call.
		 *   @property string method   The method of the call.
		 *   @property array  actions  The actions to perform on the response.
		 * 
		 * @return array
		 */
		$call = apply_filters( 'greyd_api_before_' . $api['slug'], array(
			'url' => $api['url'],
			'params' => array(
				'method'  => $api['method'],
				'timeout' => 30,
				'cookies' => $cookies,
				'headers' => $api['headers'],
				'body'    => $postdata
			)
		), $api );

		if ( is_wp_error( $call ) ) {
			return array(
				'status' => 'error',
				'body'   => implode( "\r\n", $call->get_error_messages() ),
				'full'   => $call,
			);
		}

		// make call
		$response = wp_remote_get(
			$call['url'],
			$call['params']
		);

		// echo "\r\n\r\n";
		// debug($response);
		// echo "\r\n\r\n";


		/**
		 * Handle errors.
		 */
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'body'   => implode( "\r\n", $response->get_error_messages() ),
				'full'   => $response,
			);
		}
		if ( !isset( $response['response'] ) || !isset( $response['response']['code'] ) ) {
			return array(
				'status' => 'error',
				'body'   => 'unidentified error',
				'full'   => $response,
			);
		}
		if ( $response['response']['code'] != 200 ) {
			$messages = array( $response['response']['message'] );
			if ( $body = json_decode($response['body'], true) ) {
				debug($body);
				if ( isset($body['error']) && isset($body['error_description']) ) {
					array_push( $messages, $body['error'].': '.$body['error_description'] );
				}
				else if ( is_array($body) ) foreach ( $body as $err ) {
					if ( isset($err['errorCode']) && isset($err['message']) ) {
						array_push( $messages, $err['errorCode'].': '.$err['message'] );
						if ( isset($err['extendedErrorDetails']) && is_array($err['extendedErrorDetails']) ) {
							foreach ( $err['extendedErrorDetails'] as $exerr ) {
								if ( isset($exerr['extendedErrorCode']) && isset($exerr['message']) ) {
									array_push( $messages, '> '.$exerr['extendedErrorCode'].': '.$exerr['message'] );
								}
							}
						}
					}
				}
			}
			return array(
				'status' => 'error',
				'body'   => implode( "\r\n\r\n", $messages ),
				'full'   => $response,
			);
		}

		// convert xml response
		$body = $response['body'];
		$type = $response['headers']['content-type'];
		if ( $convert_xml && strpos( $response['headers']['content-type'], '/xml' ) > 0 ) {
			try {
				$xml  = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
				$body = json_encode( $xml );
				$type = 'application/json';
			}
			catch ( Exception $e ) {
			}
		}

		// var_error_log( $api );

		// here we wanna do additional actions on the response, such as paginate or repeat
		if ( ! empty( $api['actions'] ) ) {

			$decoded_body = json_decode( $body, true );

			foreach ( $api['actions'] as $action_type => $action_atts ) {
				
				switch ( $action_type ) {
					case 'paginate':

						// var_error_log( $action_atts );
						/*
						defined like:
						'actions' => array(
							'paginate' => array(
								'condition' => array(
									'key' => 'totalSize',
									'operator' => '==',
									'value' => '{max}',
								),
								'vars' => array(
									array(
										'key' => '{offset}',
										'operator' => '+=',
										'value' => '{max}',
									),
								),
								'merge' => array(
									'key' => 'records',
								)
							),
						),
						*/
						$condition         = $action_atts['condition'];
						$pagination_vars   = $action_atts['vars'];
						$condition_value   = self::get_prop_value( $condition['key'], $decoded_body );
						$condition_compare = $condition['value'];

						if ( !empty( $api_vars ) ) {
							foreach ( $api_vars as $var => $value ) {
								$condition_compare = str_replace( $var, $value, $condition_compare );
							}
						}

						// var_error_log( $condition_value );
						// var_error_log( $condition_compare );

						$condition_true = false;
						if ( $condition['operator'] == '==' && $condition_value == $condition_compare ) {
							$condition_true = true;
						} else if ( $condition['operator'] == '!=' && $condition_value != $condition_compare ) {
							$condition_true = true;
						} else if ( $condition['operator'] == '>' && $condition_value > $condition_compare ) {
							$condition_true = true;
						} else if ( $condition['operator'] == '<' && $condition_value < $condition_compare ) {
							$condition_true = true;
						} else if ( $condition['operator'] == '>=' && $condition_value >= $condition_compare ) {
							$condition_true = true;
						} else if ( $condition['operator'] == '<=' && $condition_value <= $condition_compare ) {
							$condition_true = true;
						}

						if ( $condition_true ) {
							foreach ( $pagination_vars as $var ) {

								// var_error_log( $var );

								$var_key   = $var['key'];

								// get current value from vars
								$var_value  = isset( $api_vars[ $var_key ] ) ? $api_vars[ $var_key ] : 0;

								// get value to modify by
								$var_modify = $var['value'];
								if ( !empty( $api_vars ) ) {
									foreach ( $api_vars as $api_var => $api_var_value ) {
										$var_modify = str_replace( $api_var, $api_var_value, $var_modify );
									}
								}

								// var_error_log( $var_value );
								// var_error_log( $var_modify );

								// modify the value
								if ( $var['operator'] == '+=' ) {
									$var_value += $var_modify;
								} else if ( $var['operator'] == '-=' ) {
									$var_value -= $var_modify;
								} else if ( $var['operator'] == '*=' ) {
									$var_value *= $var_modify;
								} else if ( $var['operator'] == '/=' ) {
									$var_value /= $var_modify;
								}

								// update in $api
								if ( isset( $api['vars'] ) ) {
									var_error_log( 'updating api vars: ' . $var_key . ' = ' . $var_value );
									$api['vars'][ $var_key ] = $var_value;
								} else if ( isset( $api['block'] ) && isset( $api['block']['vars'] ) ) {
									var_error_log( 'updating block api vars: ' . $var_key . ' = ' . $var_value );
									$api['block']['vars'][ $var_key ] = $var_value;
								} else if ( isset( $api['posttype'] ) && isset( $api['posttype']['vars'] ) ) {
									var_error_log( 'updating posttype api vars: ' . $var_key . ' = ' . $var_value );
									$api['posttype']['vars'][ $var_key ] = $var_value;
								}
							}

							$second_api_call_atts = $api;
							unset( $second_api_call_atts['url'] );

							$_result = self::remote_get( $second_api_call_atts, $public, $convert_xml );
							// var_error_log( $_result );
							
							// is status is success, we merge the $_result['body'] in the $decoded_body
							if ( $_result['status'] == 'success' ) {

								$_result_body               = json_decode( $_result['body'], true );
								$merge_key                  = $action_atts['merge']['key'];
								$decoded_body[ $merge_key ] = array_merge( $decoded_body[ $merge_key ], $_result_body[ $merge_key ] );

								// var_error_log( 'pagination success, new result length: ' . count( $decoded_body[ $merge_key ] ) );

								$body = json_encode( $decoded_body );
							}
						}
				}
			}
		}


		// set cache on success
		if ( $wp_cache && $wp_cache['enabled'] ) {
			set_transient(
				$api['url'],
				array(
					'status' => 'success',
					'body'   => $body,
					'type'   => $type,
					'full'   => $response,
				),
				$wp_cache['expire']
			);
			// debug( 'set cached value set for ' . $api['url'] );
		}

		$result = array(
			'status' => 'success',
			'body'   => $body,
			'type'   => $type,
			'full'   => $response,
		);
		
		/**
		 * Filter the endpoint result.
		 * 
		 * @filter  greyd_api_after_{api-slug/enpoint-name}
		 * @example greyd_api_after_myapi/fetch-items
		 * 
		 * @param array $result     The result of the API call.
		 *   @property string status  The status of the call, 'success' or 'error'.
		 *   @property string body    The encoded body of the response.
		 *   @property string type    The content type of the response, e.g. 'application/json'.
		 *   @property object full    The raw response object.
		 * @param array  $api        The api config.
		 *   @property string slug     The slug of the api.
		 *   @property string url      The full url of the call.
		 *   @property string base_url The base url of the call.
		 *   @property string url_path The path of the call.
		 *   @property array  url_atts The attributes of the call.
		 *   @property array  headers  The headers of the call.
		 *   @property bool   wp_cache  The cache settings of the call.
		 *   @property string method   The method of the call.
		 *   @property array  actions  The actions to perform on the response.
		 * 
		 * @return array
		 */
		$result = apply_filters( 'greyd_api_after_' . $api['slug'], $result, $api );

		return $result;
	}

	/**
	 * Make a url from an api config.
	 * 
	 * @param array $api
	 * 
	 * @return string|false
	 */
	public static function make_url( $api ) {

		// base url
		if ( empty( $api['base_url'] ) ) {
			return false;
		}
		$url = $api['base_url'];
		// url path
		if ( ! empty( $api['url_path'] ) ) {
			$url .= '/' . trim( $api['url_path'], '/' );
		}
		// url attributes
		if ( isset( $api['url_atts'] ) && ! empty( $api['url_atts'] ) && is_array( $api['url_atts'] ) ) {
			$url = add_query_arg( $api['url_atts'], $url );
		}

		return $url;
	}

	/**
	 * Convert the response of an API call.
	 * 
	 * @param array  $response The response to convert.
	 * @param array  $api      The api config.
	 * @param string $for      'block' or 'posttype'
	 * 
	 * @return array
	 */
	public static function convert_response( $response, $api, $for = 'block' ) {

		if (
			$response['status'] !== 'success' ||
			strpos( $response['type'], 'application/json' ) !== 0 ||
			! isset( $api )
		) {
			return $response;
		}

		// get api config
		$api_config = array();
		if ( ! empty( $for ) && isset( $api[ $for ] ) && is_array( $api[ $for ] ) ) {
			$api_config = wp_parse_args(
				$api[ $for ],
				array(
					'data_prop' => '',
					'data_item' => array(),
				)
			);
		}

		$result   = array();
		$json_src = json_decode( $response['body'], true );
		if ( $api_config['data_prop'] != '' ) {
			$props = explode( '.', $api_config['data_prop'] );
			foreach ( $props as $prop ) {
				$json_src = $json_src[ $prop ];
			}
		}
		if ( count( array_filter( array_keys( $json_src ), 'is_string' ) ) > 0 ) {
			$json_src = array( $json_src );
		}
		// debug($json_src);
		foreach ( $json_src as $index => $json_item ) {
			$data = array();
			if ( ! empty( $api_config['data_item'] ) ) {
				foreach ( $api_config['data_item'] as $item_key => $item_setup ) {
					$data[ $item_key ] = self::get_data_item_value( $item_key, $item_setup, $json_item, $api );
				}
			} else {
				$data = $json_item;
			}

			$result[] = $data;
		}
		// debug($result);
		$response['body'] = json_encode( $result );

		return $response;
	}

	/**
	 * Get the value of a data item.
	 * 
	 * @param string $item_key          Key of the value to set. (e.g. 'post_title' or 'meta_input')
	 * @param string|array $item_setup  Setup of the value to set.
	 *     @property string value       static value
	 *     @property string prop        dynamic value
	 *     @property string default     default value
	 *     @property array actions      actions to perform on the value
	 * @param mixed $json_item          The enitre json item.
	 * 
	 * @return string
	 */
	public static function get_data_item_value( $item_key, $item_setup, $json_item, $api ) {

		$value = '';

		// shorthand item setup (prop only)
		if ( is_string( $item_setup ) ) {
			$item_setup = array(
				'prop' => $item_setup,
			);
		}
		// convert object to array
		elseif ( is_object( $item_setup ) ) {
			$item_setup = (array) $item_setup;
		} elseif ( ! is_array( $item_setup ) ) {
			return $value;
		}

		// if setup has 'value' the value is set statically
		if ( isset( $item_setup['value'] ) ) {
			if ( is_string( $item_setup['value'] ) ) {
				return $item_setup['value'];
			}
			elseif ( is_array( $item_setup['value'] ) ) {

				$value = array();
				foreach ( $item_setup['value'] as $key => $val ) {
					$value[ $key ] = self::get_data_item_value( $key, $val, $json_item, $api );
				}
				return $value;
			}
		}

		// if prop is set we try to set the value dynamically
		if ( isset( $item_setup['prop'] ) ) {

			// if it is 'value' we return the whole json item
			if ( $item_setup['prop'] == 'value' && is_string( $json_item ) ) {
				return $json_item;
			}

			$value = self::get_prop_value( $item_setup['prop'], $json_item, isset( $item_setup['default'] ) ?? '' );
		}

		// if it has actions we perform them
		if ( isset( $item_setup['actions'] ) ) {
			foreach ( $item_setup['actions'] as $action ) {
				$value = self::do_data_item_action( $value, $action, $json_item, $api );
			}
		}

		/**
		 * Filter the data item value.
		 * 
		 * @filter  greyd_api_data_item_{item_key}
		 * @example greyd_api_data_item_post_title
		 * 
		 * @param string $value      The value to set.
		 * @param array  $item_setup The setup of the value.
		 * @param mixed  $json_item  The enitre json item.
		 * @param array  $api        The api config.
		 *   @property string slug     The slug of the api.
		 *   @property string url      The full url of the call.
		 *   @property string base_url The base url of the call.
		 *   @property string url_path The path of the call.
		 *   @property array  url_atts The attributes of the call.
		 *   @property array  headers  The headers of the call.
		 *   @property bool   wp_cache  The cache settings of the call.
		 *   @property string method   The method of the call.
		 *   @property array  actions  The actions to perform on the response.
		 * 
		 * @return string
		 */
		return apply_filters( "greyd_api_data_item_{$item_key}", $value, $item_setup, $json_item, $api );
	}

	/**
	 * Perform an action on a data item value.
	 * 
	 * @param string $value   The value to perform the action on.
	 * @param array  $action  The action to perform.
	 *     @property string action  The action to perform.
	 *     @property string value   The value to use for the action.
	 * @param mixed  $json_item  The enitre json item.
	 * @param array  $api     The api config.
	 * 
	 * @return string
	 */
	public static function do_data_item_action( $value, $action, $json_item, $api ) {

		switch ( $action['action'] ) {
			case 'prepend':
				if ( is_string( $action['value'] ) ) {
					$value = $action['value'] . $value;
				} elseif ( is_array( $action['value'] ) && isset( $action['value']['prop'] ) ) {
					$value = self::get_prop_value( $action['value']['prop'], $json_item, $action['value']['default'] ?? '' ) . $value;
				}
				break;

			case 'append':
				if ( is_string( $action['value'] ) ) {
					$value = $value . $action['value'];
				} elseif ( is_array( $action['value'] ) && isset( $action['value']['prop'] ) ) {
					$value = $value . self::get_prop_value( $action['value']['prop'], $json_item, $action['value']['default'] ?? '' );
				}
				break;

			case 'url_encode':
				$value = rawurlencode( $value );
				break;

			case 'json_encode':
				$value = json_encode( $value );
				break;

			case 'implode':
				$value = implode( $action['value'] ?? '', $value );
				break;

			case 'explode':
				$value = explode( $action['value'] ?? '', $value );
				break;

			case 'index':
				if ( $action['value'] == 'prepend' ) {
					$value = ( $index + 1 ) . $value;
				} elseif ( $action['value'] == 'append' ) {
					$value = $value . ( $index + 1 );
				}
				break;

			case 'count':
				if ( $action['value'] == 'prepend' ) {
					$value = count( $json_src ) . $value;
				} elseif ( $action['value'] == 'append' ) {
					$value = $value . count( $json_src );
				}
				break;

			case 'filter':
				/**
				 * Filter the value of an item.
				 * 
				 * @filter  greyd_block_api_item_value
				 * 
				 * @param mixed $value      The value to set.
				 * @param array  $action    The action to perform.
				 * @param mixed  $json_item The enitre json item.
				 * @param array  $api       The api config.
				 *   @property string slug     The slug of the api.
				 *   @property string url      The full url of the call.
				 *   @property string base_url The base url of the call.
				 *   @property string url_path The path of the call.
				 *   @property array  url_atts The attributes of the call.
				 *   @property array  headers  The headers of the call.
				 *   @property bool   wp_cache  The cache settings of the call.
				 *   @property string method   The method of the call.
				 *   @property array  actions  The actions to perform on the response.
				 * 
				 * @return mixed $value        The filtered value.
				 */
				$value = apply_filters( 'greyd_block_api_item_value', $value, $action, $json_item, $api );
				break;

			case 'call':
				$api_route = explode( '/', $api['slug'] )[0] . '/' . $action['value']['route'];
				$route     = self::get_api( $api_route );
				if ( isset( $action['value']['vars'] ) ) {
					$route['vars'] = array();
					foreach ( $action['value']['vars'] as $var => $val ) {
						$route['vars'][ $var ] = self::get_prop_value( $val, $json_item, $value );
					}
				}

				// debug( "try to get value from:" );
				// debug( $route );
				// debug( $json_item );

				// debug($route, true);
				$route_response = self::remote_get( $route );
				// debug($route_response, true);
				if (
					$route_response['status'] === 'success' &&
					strpos( $route_response['type'], 'application/json' ) === 0
				) {
					$route_result = json_decode( $route_response['body'], true );
					if ( $action['value']['data_prop'] != '' ) {
						$props = explode( '.', $action['value']['data_prop'] );
						foreach ( $props as $prop ) {
							$route_result = isset( $route_result[ $prop ] ) ? $route_result[ $prop ] : null;
						}
					}
					if ( $route_result ) {
						$value = $route_result;

						if ( isset( $action['value']['actions'] ) ) {

							$type = $action['value']['type'] ?? 'string';

							if ( $type === 'array' ) {
								foreach ( $value as $i => $val ) {
									foreach ( $action['value']['actions'] as $_action ) {
										$value[ $i ] = self::do_data_item_action( $val, $_action, $json_item, $api );
									}
								}
							} else {
								foreach ( $action['value']['actions'] as $_action ) {
									$value = self::do_data_item_action( $value, $_action, $json_item, $api );
								}
							}
						}
					}
				}
				break;
		}

		/**
		 * Filter the value of a api field after an action was performed.
		 * 
		 * @filter  greyd_api_data_item_action_{action}
		 * @example greyd_api_data_item_action_prepend
		 * 
		 * @param mixed $value      The value to set.
		 * @param array  $action    The action to perform.
		 * @param mixed  $json_item The enitre json item.
		 * @param array  $api       The api config.
		 *   @property string slug     The slug of the api.
		 *   @property string url      The full url of the call.
		 *   @property string base_url The base url of the call.
		 *   @property string url_path The path of the call.
		 *   @property array  url_atts The attributes of the call.
		 *   @property array  headers  The headers of the call.
		 *   @property bool   wp_cache  The cache settings of the call.
		 *   @property string method   The method of the call.
		 *   @property array  actions  The actions to perform on the response.
		 * 
		 * @return mixed
		 */
		return apply_filters( "greyd_api_data_item_action_{$action['action']}", $value, $action, $json_item, $api );
	}

	/**
	 * Get a value from a json item.
	 * 
	 * @param string $property   The property to get the value from.
	 *                           (e.g. 'post_title' or 'meta._yoast_wpseo_title')
	 * @param array  $json_item  The enitre json item.
	 * @param string $default    The default value to return if the property do not exist.
	 * 
	 * @return string
	 */
	public static function get_prop_value( $property, $json_item, $default = '' ) {
		$props    = explode( '.', $property );
		$json_tmp = $json_item;
		foreach ( $props as $i => $prop ) {
			if ( ! isset( $json_tmp[ $prop ] ) ) {
				$json_tmp = $default;
				break;
			}
			$json_tmp = $json_tmp[ $prop ];
		}
		
		/**
		 * Filter the value of an item property.
		 * 
		 * @filter  greyd_api_prop_value_{property}
		 * @example greyd_api_prop_value_post_title
		 * 
		 * @param string $json_tmp   The value to set.
		 * @param array  $json_item  The enitre json item.
		 * @param string $default    The default value to return if the property do not exist.
		 * 
		 * @return string
		 */
		return apply_filters( "greyd_api_prop_value_{$property}", $json_tmp, $json_item, $default );
	}
}
