<?php
/**
 * Headless block features.
 * - api Block
 * -
 */
namespace Greyd\Headless;

// depends on Theme greyd\blocks\helper
// ::implode_html_attributes
use \greyd\blocks\helper as Blocks_Helper;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Api_Blocks( $config );
class Api_Blocks {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// check if Gutenberg is active.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// set config
		$this->config = (object) $config;

		// setup
		$this->config->css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

		// enqueue scripts
		// add_action( 'enqueue_block_editor_assets', array($this, 'register_blocks_assets') );
		add_filter( 'greyd_blocks_editor_data', array( $this, 'greyd_blocks_editor_data' ) );

		// register block
		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 9, 2 );
		add_action( 'init', array( $this, 'register_blocks' ), 99 );

	}

	public function greyd_blocks_editor_data( $data ) {

		if ( isset( $data['settings']['api'] ) ) {
			foreach ( $data['settings']['api'] as $key => $val ) {
				$value = is_string( $val ) ? @unserialize( $val ) : $val;
				if ( $value && is_array( $value ) && $data['settings']['api'][ $key ] != $value ) {
					$data['settings']['api'][ $key ] = $value;
					if ( isset($data['settings']['api'][ $key ][0]) ) {
						unset($data['settings']['api'][ $key ][0]);
					}
				}
			}
			// debug($data['settings']['api']);
		}
		// debug($data);
		return $data;
	}

	/**
	 * Register and enqueue editor scripts
	 */
	public function register_blocks_assets() {

		// if ( method_exists( '\greyd\blocks\render', 'render_dynamic' ) ) return;

		// $js_uri = plugin_dir_url(__FILE__).'assets/js/blocks';

		// // editor script
		// wp_register_script(
		// 'greyd-dynamic-editor',
		// trailingslashit( $js_uri ).'editor.js',
		// array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'underscore' ),
		// GREYD_VERSION
		// );
		// wp_enqueue_script('greyd-dynamic-editor');

		// // dynamic script
		// wp_register_script(
		// 'greyd-dynamic',
		// trailingslashit( $js_uri ).'dynamic.js',
		// array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore' ),
		// GREYD_VERSION
		// );
		// wp_enqueue_script('greyd-dynamic');

		// // dtag format script
		// wp_register_script(
		// 'greyd-format-dtag',
		// trailingslashit( $js_uri ).'format-dtag.js',
		// array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore' ),
		// GREYD_VERSION
		// );
		// wp_enqueue_script('greyd-format-dtag');

		// // autocompleters script
		// wp_register_script(
		// 'greyd-dynamic-autocompleter',
		// trailingslashit( $js_uri ).'autocompleter.js',
		// array( 'greyd-format-dtag', 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore' ),
		// GREYD_VERSION
		// );
		// wp_enqueue_script('greyd-dynamic-autocompleter');

		// // add script translations
		// if ( function_exists('wp_set_script_translations') ) {
		// wp_set_script_translations( 'greyd-dynamic-editor', 'greyd_hub', $this->config->plugin_path.'/languages' );
		// wp_set_script_translations( 'greyd-dynamic', 'greyd_hub', $this->config->plugin_path.'/languages' );
		// wp_set_script_translations( 'greyd-format-dtag', 'greyd_hub', $this->config->plugin_path.'/languages' );
		// wp_set_script_translations( 'greyd-dynamic-autocompleter', 'greyd_hub', $this->config->plugin_path.'/languages' );
		// }
	}

	/**
	 * Register API Blocks Category
	 */
	public function register_block_categories( $categories, $post ) {

		return array_merge(
			array(
				array(
					'slug'  => 'greyd-blocks-apis',
					'title' => 'Greyd APIs',
				),
			),
			$categories
		);

		return $categories;
	}

	/**
	 * Blockeditor
	 */
	public function register_blocks() {

		// register script
		wp_register_script(
			'greyd-api-editor-script',
			$this->config->js_uri . '/block-api.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-api-editor-script', 'greyd_hub', $this->config->plugin_path . '/languages' );
		}

		// register style
		wp_register_style(
			'greyd-api-editor-style',
			$this->config->css_uri . '/block-api.css',
			null,
			GREYD_VERSION
		);

		// register api block
		register_block_type(
			'greyd/api',
			array(
				'supports'        => array(
					'anchor' => true,
					'layout' => false,
				),
				'attributes'      => array(
					'anchor'      => array( 'type' => 'string' ),
					'className'   => array( 'type' => 'string' ),
					'greydClass'  => array( 'type' => 'string' ),
					'greydStyles' => array( 'type' => 'object' ),
					'api'         => array( 'type' => 'string' ),
					'vars'        => array( 'type' => 'object' ),
					'inputs'      => array( 'type' => 'object' ),
					'render'      => array( 'type' => 'string' ),
				),
				'editor_script'   => 'greyd-api-editor-script',
				'editor_style'    => 'greyd-api-editor-style',
				'style'           => 'greyd-api-editor-style',
				'render_callback' => array( $this, 'render_api_block' ),
			)
		);

	}

	/**
	 * =================================================================
	 *                          Block render callback
	 * =================================================================
	 */

	/**
	 * Render api Block.
	 *
	 * @param array    $atts      Block attributes
	 * @param string   $content  Current block content.
	 * @param WP_Block $block  https://developer.wordpress.org/reference/classes/wp_block/
	 *
	 * @return string
	 */
	public function render_api_block( $atts, $content, $block ) {

		if (
			is_admin() ||
			! isset( $atts['api'] ) ||
			empty( $atts['api'] )
		) {
			return $content;
		}

		// debug($atts);

		// get api
		$api = Api_Helper::get_api( $atts['api'] );

		if (
			$api === null ||
			! isset( $api['block'] ) ||
			! is_array( $api['block'] )
		) {
			return $content;
		}

		$input = '';
		if ( isset( $api['block']['vars'] ) ) {
			// debug($api['block']['vars']);
			// debug($atts);
			$api['vars'] = $api['block']['vars'];
			if ( isset( $atts['vars'] ) ) {
				foreach ( $atts['vars'] as $var => $val ) {
					if ( isset( $api['vars'][ $var ] ) ) {
						$api['vars'][ $var ] = $val;
					}
				}
			}
			// render (search) input
			if ( isset( $atts['inputs'] ) ) {
				foreach ( $atts['inputs'] as $var => $val ) {
					if ( $val ) {
						$variable   = trim( $var, '{}' );
						$url_params = '';
						$query      = $atts['vars'][ $var ] ?? '';
						foreach ( $_GET as $key => $value ) {
							if ( $key == $variable ) {
								$query = $value;
							} else {
								$url_params .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
							}
						}

						$input .= '<form class="wp-block-greyd-api-input greyd-search-form" method="get">
							' . $url_params . '
							<div class="input-outer-wrapper">
								<input type="search" name="' . $variable . '" value="' . $query . '" class="input" placeholder="Search...">
							</div>
							<div class="input-outer-wrapper ">
								<button class="submitbutton button" type="submit" title="Search" data-text="">Search</button>
							</div>
						</form>';
					}
				}
			}
		}

		unset( $api['posttype'] );

		// get raw response
		$response_raw = Api_Helper::remote_get( $api );

		// convert response
		$response = Api_Helper::convert_response( $response_raw, $api );

		$wrapper_atts = array(
			'id'    => uniqid( 'api_' ),
			'class' => array(
				'wp-block-greyd-api',
			),
			'data'  => array(),
			'style' => array(),
		);
		if ( isset( $atts['anchor'] ) && ! empty( $atts['anchor'] ) ) {
			$wrapper_atts['id'] = $atts['anchor'];
		}
		if ( isset( $atts['className'] ) && ! empty( $atts['className'] ) ) {
			$wrapper_atts['class'][] = $atts['className'];
		}
		if ( isset( $atts['greydClass'] ) && ! empty( $atts['greydClass'] ) ) {
			$wrapper_atts['class'][] = $atts['greydClass'];
		}

		// debug($response['type']);
		if ( $response['status'] === 'success' ) {

			if ( isset( $atts['render'] ) && $atts['render'] == 'filter' ) {

				/** first render filter */
				$filtered_content = apply_filters( 'greyd_render_block_api_data_raw', '', $atts, $api, $response_raw );
				if ( is_string( $filtered_content ) && $filtered_content != '' ) {
					return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>' .
								$filtered_content .
							'</div>';
				}

				/** second render filter */
				$filtered_content = apply_filters( 'greyd_render_block_api_data', '', $atts, $api, $response );
				if ( is_string( $filtered_content ) && $filtered_content != '' ) {
					return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>' .
								$filtered_content .
							'</div>';
				}

				/** render html and text in iframe */
				if ( strpos( $response['type'], 'text/html' ) === 0 ) {
					$src = 'data:text/html;charset=utf-8,' . rawurlencode( $response['body'] );
					return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>
								<iframe src="' . $src . '" style="width:100%; min-height:350px; background:#fff;"></iframe>
							</div>';
				}

				// get data
				$debug = '';
				if ( strpos( $response['type'], 'application/json' ) === 0 ) {
					$result = json_decode( $response['body'] );
					$debug  = '<pre>' . print_r( $result, true ) . '</pre>';
				} elseif ( strpos( $response['type'], 'application/xml' ) === 0 || strpos( $response['type'], 'text/xml' ) === 0 ) {
					$xml   = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
					$json  = json_decode( json_encode( $xml ), true );
					$debug = '<pre>' . print_r( $json, true ) . '</pre>';
				} else {
					$debug = '<pre>' . htmlentities( $response['body'] ) . '</pre>';
				}

				/** render info about filter */
				return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>' .
							'<pre>' .
								'<b>API call to "' . $api['title'] . '" successful.</b><br>' .
								'<b>Response Type "' . $response['type'] . '" detected.</b><br><br>' .
								'Please add the following filter to process and render the response data:<br><br>' .
								'add_filter( \'greyd_render_block_api_data\', function( $content, $atts, $api, $response ) {<br>' .
								'	if ( $api[\'slug\'] == \'' . $api['slug'] . '\' ) {<br>' .
								'		// process $response[\'body\'] data<br>' .
								'		// and return new content<br>' .
								'	}<br>' .
								'	return $content;<br>' .
								'}, 10, 4 );' .
							'</pre>' .
							'<pre><b>response data:</b></pre>' .
							$debug .
						'</div>';

			} else {

				if ( strpos( $response['type'], 'application/json' ) === 0 ) {
					$result = json_decode( $response['body'], true );
					// $debug = '<pre>'.print_r($result, true).'</pre>';
				} elseif ( strpos( $response['type'], 'application/xml' ) === 0 || strpos( $response['type'], 'text/xml' ) === 0 ) {
					$xml    = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
					$result = json_decode( json_encode( $xml ), true );
					// $debug = '<pre>'.print_r($result, true).'</pre>';
				} else {
					$result = $response['body'];
					// $debug = '<pre>'.htmlentities($response['body']).'</pre>';
				}

				if ( $result && is_array( $result ) ) {

					$wrapper_atts['class'][] = 'grid-container';
					$content                 = '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>';
					foreach ( $result as $i => $post ) {

						// add dynamic tag filter
						foreach ( $api['block']['data_item'] as $dtag => $v ) {
							add_filter(
								'greyd_render_dynamic_tag',
								function( $new, $tag, $params, $block, $wp_post ) use ( $dtag, $post ) {
									return $tag === $dtag ? $post[ $dtag ] : $new;
								},
								99,
								5
							);
							if ( isset( $v['type'] ) && in_array( $v['type'], array( 'url', 'file', 'email' ) ) ) {
								add_filter(
									'greyd_get_dynamic_url',
									function( $new, $tag, $block, $wp_post ) use ( $dtag, $post ) {
										return $tag === $dtag ? $post[ $dtag ] : $new;
									},
									99,
									4
								);
							}
						}

						// render
						$inner          = $block->parsed_block;
						$inner['attrs'] = array();
						$b              = ( new \WP_Block( $inner ) )->render( array( 'dynamic' => false ) );
						$content       .= '<article class="query-post">' . $b . '</article>';

						// remove dynamic tag filter
						remove_all_filters( 'greyd_render_dynamic_tag', 99 );
						remove_all_filters( 'greyd_get_dynamic_url', 99 );
					}
					$content .= '</div>';

					$this->enqueue_responsive_items_stylesheet( $wrapper_atts['id'], $atts );

					return $input . $content;

				}

				return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>' .
							'<pre>' .
								'<b style="color:green">API call to "' . $api['title'] . '" successful!</b><br><br>' .
								print_r( $result, true ) .
							'</pre>' .
						'</div>';

			}
		}

		return $input . '<div ' . Blocks_Helper::implode_html_attributes( $wrapper_atts ) . '>' .
					'<pre>' .
						'<b style="color:red">API call to "' . $api['title'] . '" failed!</b><br><br>' .
						'"' . $response['body'] . '"' .
					'</pre>' .
				'</div>';

	}

	/**
	 * Make the number of items responsive.
	 */
	public function enqueue_responsive_items_stylesheet( $id, $atts ) {

		if ( ! isset( $atts['greydStyles'] ) ) {
			return;
		}

		// desktop
		if ( isset( $atts['greydStyles']['items'] ) && ! empty( $atts['greydStyles']['items'] ) ) {
			$selector = '#' . $id . ' .query-post:nth-child(n+' . ( intval( $atts['greydStyles']['items'] ) + 1 ) . ')';
		}
		// responsive
		if ( isset( $atts['greydStyles']['responsive'] ) ) {
			// lg
			if ( isset( $atts['greydStyles']['responsive']['lg']['items'] ) && ! empty( $atts['greydStyles']['responsive']['lg']['items'] ) ) {
				$selector_lg = '#' . $id . ' .query-post:nth-child(n+' . ( intval( $atts['greydStyles']['responsive']['lg']['items'] ) + 1 ) . ')';
			}
			// md
			if ( isset( $atts['greydStyles']['responsive']['md']['items'] ) && ! empty( $atts['greydStyles']['responsive']['md']['items'] ) ) {
				$selector_md = '#' . $id . ' .query-post:nth-child(n+' . ( intval( $atts['greydStyles']['responsive']['md']['items'] ) + 1 ) . ')';
			}
			// sm
			if ( isset( $atts['greydStyles']['responsive']['sm']['items'] ) && ! empty( $atts['greydStyles']['responsive']['sm']['items'] ) ) {
				$selector_sm = '#' . $id . ' .query-post:nth-child(n+' . ( intval( $atts['greydStyles']['responsive']['sm']['items'] ) + 1 ) . ')';
			}
		}

		// render styles
		$css  = '';
		$hide = '{ display: none }';
		if ( isset( $selector ) ) {
			$css .= $selector . ' ' . $hide . ' ';
		}
		if ( isset( $selector_lg ) ) {
			$css .= '@media (max-width: var(grid_lg)) { ' . $selector_lg . ' ' . $hide . ' } ';
		}
		if ( isset( $selector_md ) ) {
			$css .= '@media (max-width: var(grid_md)) { ' . $selector_md . ' ' . $hide . ' } ';
		}
		if ( isset( $selector_sm ) ) {
			$css .= '@media (max-width: var(grid_sm)) { ' . $selector_sm . ' ' . $hide . ' } ';
		}

		// enqueue
		if ( $css !== '' ) {
			Helper::add_custom_style( $css );
		}

	}

}
