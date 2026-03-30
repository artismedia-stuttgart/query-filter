<?php
/**
 * Script to render Dynamic Features.
 * Enqueueing and Shortcode rendering is kind of deprecated,
 * but still needed for rendering of (old) nested Templates.
 */
namespace Greyd\Dynamic;

use Greyd\Helper as Helper;

if( ! defined( 'ABSPATH' ) ) exit;

new Render();
class Render {

	/*
	====================================================================================
		Frontend
	====================================================================================
	*/

	/**
	 * Render a dynamic template
	 * 
	 * @todo refactor
	 */
	public static function add_dynamic( $atts = array(), $content = '' ) {
		$template_name = isset( $atts['template'] ) ? esc_attr( $atts['template'] ) : '';
		if ( ! empty( $template_name ) ) {
			// get template
			$template =  \Greyd\Dynamic\Dynamic_Helper::get_template_post( $template_name );
			// debug($template_name);
			// debug($template);
			if ( $template && !empty( $template ) && isset( $template->post_content ) ) {
				if ( ! function_exists( 'register_block_type' ) || ! has_blocks( $template->post_content ) ) {
					// render shortcode content
					$content = \Greyd\Dynamic\Render_Deprecated::process_shortcode_content( $atts, $template );
				} else {
					// render blocks content
					if ( method_exists( '\greyd\blocks\render', 'process_blocks_content' ) ) {
						// blocks v1
						$content = \greyd\blocks\render::process_blocks_content( $atts, $template );
					} elseif ( method_exists( '\greyd\blocks\dynamic\Render', 'process_blocks_content' ) ) {
						// blocks refactored
						$content = \greyd\blocks\dynamic\Render::process_blocks_content( $atts, $template );
					} elseif ( method_exists( '\Greyd\Dynamic\Render_Blocks', 'process_blocks_content' ) ) {
						// blocks modularized
						$content = \Greyd\Dynamic\Render_Blocks::process_blocks_content( $atts, $template );
					} else {
						// no Greyd.Blocks
						$content = do_blocks( $template->post_content );
					}
				}
			} else {

				/**
				 * Filter the content of a dynamic template that could not be found.
				 * 
				 * @since 2.11.0
				 * 
				 * @param string $message        The content of the message.
				 * @param string $template_name  The name of the dynamic template.
				 * @param array  $atts           The attributes of the dynamic template.
				 */
				$message = apply_filters( 'greyd_dynamic_template_not_found_message', sprintf( __( 'No template with the name "%s" found.', 'greyd_hub' ), '<strong>' . $template_name . '</strong>' ), $template_name, $atts );

				// template not found
				$content = is_user_logged_in() ? "<div class='container'><div class='message info'>" . $message . '</div></div>' : "";
			}
		}
		return $content;
	}

	/**
	 * Replace all dynamic tags of a string.
	 * Supports simple tags ( eg. _categories_ ) AND advanced tags with parameters ( eg. _categories|vert|1_ )
	 * 
	 * @param WP_Post $post     Post object.
	 * @param array   $tags     Array of dynamic tags to match and replace. @see helper::get_dynamic_tags()
	 * @param string  $value    Content that holds the unprocessed dynamic tags (eg. post_content).
	 * 
	 * @return string $newval   Content with all dynamic tags replaced.
	 */
	public static function match_tags($post, $tags, $value) {

		/** 
		 * After removing prematching of dynamic tags, the new matching function should be used
		 * @since 2.14.0
		 */
		if ( defined( 'WPB_VC_VERSION' ) && \Greyd\Helper::is_greyd_classic() ) {
			return self::__deprecated_match_tags( $post, $tags, $value );
		}

		$oldval = $newval = $value;

		$tag_slugs = array_filter(
			array_keys($tags),
			function($k) {
				return !empty($k);
			}
		);

		$tag_slugs = array_map(
			function($s) {
				return str_replace('/', '\/', preg_quote($s));
			},
			$tag_slugs
		);

		$regex = '/(?<=^|[\"\'\;\s\>])_(' . join('|', $tag_slugs) . ')(\|[^"]*?)?_(?=[\"\'\&\s\<]|$)/';
		
		preg_match_all( $regex, $oldval, $matches );
		
		if ( count($matches[0]) > 0 ) {
			for ( $i = 0; $i < count($matches[0]); $i++ ) {
				
				// get vars
				$whole_tag  = $matches[0][$i];
				$tag        = $matches[1][$i];
				$params     = $matches[2][$i];
				if ( empty($tag) ) continue;
				
				// get value
				if ( !isset($tags[$tag]) ) {
					$value = "";
				}
				else if ( is_callable($tags[$tag]) ) {
					$value = call_user_func( $tags[$tag], $post, $params, $tag );
				}
				else {
					try {
						$value = strval( $tags[$tag] );
					} catch (Exception $e) {
						$value = "";
					}
				}

				$value = wp_specialchars_decode($value, ENT_QUOTES);
				$value = str_replace( [ '[', ']', '(', ')' ], '', $value );
				
				// assign value
				$newval = str_replace( $whole_tag, $value, $newval );
			}
		}
		
		return $newval;
	}

	/**
	 * Replace all dynamic tags of a string.
	 * Supports simple tags ( eg. _categories_ ) AND advanced tags with parameters ( eg. _categories|vert|1_ )
	 * 
	 * @param WP_Post $post     Post object.
	 * @param array   $tags     Array of dynamic tags to match and replace. @see helper::get_dynamic_tags()
	 * @param string  $value    Content that holds the unprocessed dynamic tags (eg. post_content).
	 * 
	 * @return string $newval   Content with all dynamic tags replaced.
	 */
	public static function __deprecated_match_tags($post, $tags, $value) {
		$oldval = $newval = $value;

		$tag_slugs = array_filter(
			array_keys($tags),
			function($k) {
				return !empty($k);
			}
		);

		$tag_slugs = array_map(
			function($s) {
				return str_replace('/', '\/', preg_quote($s));
			},
			$tag_slugs
		);
		
		$regex = '/(?<!vc)(\|title:[^:|"\[\]]*?)?_('.join('|', $tag_slugs) .')(\|[^"]*?)?_(?!box)/';
		
		preg_match_all( $regex, $oldval, $matches );
		
		if ( count($matches[0]) > 0 ) {
			for ( $i = 0; $i < count($matches[0]); $i++ ) {
				
				// get vars
				$whole_tag  = $matches[0][$i];
				$tag        = $matches[2][$i];
				$params     = $matches[3][$i];
				if ( empty($tag) ) continue;
				
				// get value
				if ( !isset($tags[$tag]) ) {
					$value = "";
				}
				else if ( is_callable($tags[$tag]) ) {
					$value = call_user_func( $tags[$tag], $post, $params, $tag );
				}
				else {
					try {
						$value = strval( $tags[$tag] );
					} catch (Exception $e) {
						$value = "";
					}
				}

				/**
				 * If the first regex group '(\|title:|)' holds a value, the tag is
				 * inside of a 'vc_link'-picker. If this is true, the value needs
				 * to be urlencoded in order to work properly.
				 * 
				 * @since 1.0.5
				 */
				if ( !empty($matches[1][$i]) ) {
					$enc = str_replace("+", "%20", urlencode($value));
					$value = $matches[1][$i].$enc;
				}
				else {
					$value = wp_specialchars_decode($value, ENT_QUOTES);
					$value = str_replace( [ '[', ']', '(', ')' ], '', $value );
				}
				
				// assign value
				$newval = str_replace( $whole_tag, $value, $newval );
			}
		}
		
		return $newval;
	}

	/**
	 * Get the slug of the current dynamic system template.
	 * 
	 * @return string
	 */
	public static function get_dynamic_name() {

		$cache = wp_cache_get( 'get_dynamic_name', 'greyd' );
		if ( $cache ) {
			return $cache;
		}

		global $wp_query;
		// debug($wp_query);
		// default template names
		// {404|single|archives|search}-{custom_post_type|''}-{category|''}
		$template_name = $tax = '';

		// name base
		if ( is_404() ) {
			return '404';
		} elseif ( is_search() ) {
			$template_name = 'search';
		} elseif ( is_archive() ) {
			$template_name = 'archives';
		} elseif ( is_single() ) {
			$template_name = 'single';
		}

		// get post type
		$post_type = get_post_type();
		if ( is_post_type_archive() ) {
			$post_type = $wp_query->query['post_type'];
		}

		// add first category to template name
		if ( is_single() ) {
			if ( $post_type != '' && $post_type != 'page' && isset( $wp_query->queried_object ) ) {
				$categories = Helper::get_categories( $wp_query->queried_object );
				$tax        = isset( $categories ) && is_array( $categories ) && count( $categories ) > 0 ? $categories[0]->slug : '';
			}
		} elseif ( is_search() ) {
			if ( isset( $wp_query->query_vars['post_type'] ) ) {
				$post_type = $wp_query->query_vars['post_type'];
			}
		} elseif ( is_archive() ) {
			if ( isset( $wp_query->query[ $post_type . '_category' ] ) ) {
				$tax = $wp_query->query[ $post_type . '_category' ];
			} elseif ( is_tax() ) {
				$tax        = $wp_query->query_vars['taxonomy'];
				$tax_object = get_taxonomy( $tax );
				$tax_object = $tax_object ? $tax_object->object_type : array();
				$post_type  = isset($tax_object[0]) ? $tax_object[0] : '';
				$tax        = isset( $wp_query->query[ $tax ] ) ? $wp_query->query[ $tax ] : $wp_query->get( $tax );
			} else {
				$category = get_category( get_query_var( 'cat' ), false );
				$tax      = ( $category && ! is_wp_error( $category ) ) ? $category->slug : '';
			}
		}

		// if custom post type -> add cpt to default name
		if ( ! empty( $template_name ) && ! empty( $post_type ) && $post_type != 'post' && $post_type != 'page' ) {
			$template_name = $template_name . '-' . $post_type; // -{custom_post_type}
		}
		// if tax is queried -> add taxonomy to default name
		if ( ! empty( $template_name ) && ! empty( $tax ) ) {
			$template_name = $template_name . '-' . $tax; // -{taxonomy}
		}

		$template_name = apply_filters( 'tp_dynamic_edit_template_name', $template_name );

		// $archive = is_archive() ? "yes" : "no";
		// $category = is_category() ? "yes" : "no";
		// $single = is_single() ? "yes" : "no";
		// $search = is_search() ? "yes" : "no";
		// $post_type_archive = is_post_type_archive() ? "yes" : "no";
		// debug("is_archive: ".$archive);
		// debug("is_category: ".$category);
		// debug("is_single: ".$single);
		// debug("is_search: ".$search);
		// debug("is_post_type_archive: ".$post_type_archive);
		// debug("post_type: ".$post_type);
		// debug("template_name: ".$template_name);

		wp_cache_set( 'get_dynamic_name', $template_name, 'greyd' );

		return $template_name;
	}

	/**
	 * Get currently enqueued styles.
	 * 
	 * @see Helper::render_custom_styles()
	 * 
	 * @return string eg. '<style> .gs_12345 { margin-bottom: 12px } </style>'
	 */
	public static function get_style() {
		$style = "";
		if (strpos($_SERVER['REQUEST_URI'], "/wp-json/wp/v2/block-renderer") === 0) {
			if ( Helper::has_custom_styles() ) {
				ob_start();
				Helper::render_custom_styles();
				$style = ob_get_contents();
				ob_end_clean();
			}
		}
		return $style;
	}

}