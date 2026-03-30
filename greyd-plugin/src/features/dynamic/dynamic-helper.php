<?php
/**
 * Utility functions for Dynamic features.
 */
namespace Greyd\Dynamic;

use Greyd\Helper as Helper;

use Greyd\Posttypes\Posttype_Helper as Posttypes;

if ( ! defined( 'ABSPATH' ) ) exit;

new Dynamic_Helper();
class Dynamic_Helper {

	/**
	 * Get dynamic posttype slug
	 */
	public static function get_slug() {
		return 'dynamic_template';
	}

	/**
	 * Get template types
	 */
	public static function get_template_types() {
		return apply_filters( 'greyd_template_types', array(
			array(
				'title'       => __( 'Dynamic Templates', 'greyd_hub' ),
				'slug'        => 'dynamic',
				'description' => '',
				'color'       => 'greyd',
			)
		) );
	}

	/**
	 * Get the template post object.
	 * 
	 * @param string|int $template_name  ID or slug of the dynamic template.
	 * 
	 * @return \WP_Post
	 */
	public static function get_template_post( $template_name, $from_all_languages = true ) {

		$cache = wp_cache_get( 'dynamic_template_' . $template_name, 'greyd' );
		if ( $cache ) {
			return $cache;
		}

		if ( $template_name != '404' && is_numeric( $template_name ) ) {

			$template_name = \Greyd\Helper::filter_post_id( $template_name );

			// debug("template by id");
			$template = array( get_post( $template_name ) );
		} else {
			$args = array(
				'name'        => $template_name,
				'post_type'   => Dynamic_Helper::get_slug(),
				'post_status' => 'publish',
				'numberposts' => 1,
			);

			/**
			 * Get posts from all languages.
			 * Supports:
			 * - WPML
			 * - Polylang
			 */
			if ( $from_all_languages ) {
				$args['suppress_filters'] = true;
				$args['lang']             = '';
			} else {
				$args['suppress_filters'] = false;
			}


			// debug("template by slug");
			$template = get_posts( $args );

			// if template name is copy (eg. footer-2) search for parent (footer)
			if ( ! $template && strpos( $template_name, '-' ) > 0 ) {

				/**
				 * Get alternate template name if the current archive term
				 * has a parent term.
				 *
				 * @since 1.4.1
				 */
				if ( is_archive() && strpos( $template_name, 'archives-' ) === 0 ) {

					$post_type = get_post_type();
					$term_slug = explode( '-', $template_name, 2 )[1];
					$tax_name  = $post_type === 'post' ? 'category' : $post_type . '_category';

					$term = get_term_by( 'slug', $term_slug, $tax_name );
					if ( $term && isset( $term->parent ) && ! empty( $term->parent ) ) {
						$term = get_term_by( 'term_id', $term->parent, $tax_name );
						if ( $term && isset( $term->slug ) ) {
							$template_name = $term_slug = explode( '-', $template_name, 2 )[0] . '-' . $term->slug . '-1';
						}
					}
				}

				// debug($template_name);
				$alternate_name = explode( '-', $template_name );
				array_pop( $alternate_name );
				$alternate_name = implode( '-', $alternate_name );
				$template       = self::get_template_post( $alternate_name );
			}
			// if still no template found, search for copies
			if ( ! $template ) {
				$i = 1;
				while ( $i <= 10 ) {
					$args['name'] = $template_name . '-' . $i;
					$template = get_posts( $args );
					if ( $template ) {
						break;
					}
					$i++;
				}
			}

			// make sure the template ID is still filtered, even if it is defined by slug
			if (
				$template
				&& is_array($template)
				&& isset($template[0])
				&& is_object($template[0])
			) {
				$template_id = isset($template[0]->ID) ? $template[0]->ID : 0;
				if ( $template_id ) {
					$filtered_id = \Greyd\Helper::filter_post_id( $template_id );
					if ( $filtered_id && $filtered_id != $template_id ) {
						$template[0] = get_post( $filtered_id );
					}
				}
			}
		}

		// get WP_Post object
		if (
			$template
			&& is_array($template)
			&& isset($template[0])
			&& is_object($template[0])
		) {
			$template = $template[0];
		}

		wp_cache_set( 'dynamic_template_' . $template_name, $template, 'greyd' );

		// debug($template);
		return $template;
	}


	/*
	====================================================================================
		from \greyd\blocks\helper and \vc\helper
		get_the_excerpt combined (\vc\helper with highlighting)
		get_the_title (from \greyd\blocks\helper with highlighting)
		strip_all_tags_and_shortcodes (used by get_the_excerpt, trim_post_content and make_clean_vc_string)
	====================================================================================
	*/

	/**
	 * Get the post excerpt.
	 * If no excerpt exists, trim the content to look like it.
	 * @supports highlighting of global $query_search_term.
	 * 
	 * @param WP_Post   $post
	 * @param bool      $strip_tags (default true)
	 * @return string   $excerpt    
	 */
	public static function get_the_excerpt($post, $strip_tags=true) {

		// custom excerpt already attached
		if ( isset($post->excerpt) ) return $post->excerpt;



		// get the default excerpt
		$excerpt = isset($post->post_excerpt) ? $post->post_excerpt : null;
		if ( !empty($excerpt) ) {
			$excerpt = trim( $excerpt );
			if ($strip_tags) {
				$excerpt =  self::strip_all_tags_and_shortcodes($excerpt);
			}

		}

		// default excerpt is empty
		if ( empty($excerpt) ) {

			if ( has_blocks( $post->post_content ) ) {
				if ( $strip_tags ) {
					$excerpt =  self::trim_post_content( $post->post_content );
				} else {
					$excerpt = trim( $post->post_content );
				}
			} else {
				$excerpt = self::make_clean_vc_string($post->post_content, 600);
			}
		}
		// trim it
		if ($strip_tags) {
			$excerpt_length = apply_filters('excerpt_length', 55);
			$excerpt_more   = apply_filters('excerpt_more', ' &#91;&hellip;&#93;');
			$excerpt        = wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );
		}
		// set highlight
		global $query_search_term;
		if ( !empty($query_search_term) ) {
			$excerpt = preg_replace( '/('.$query_search_term.')/i', '<mark>$1</mark>', $excerpt );
		}

		return $excerpt;
	}

	/**
	 * Get the post title.
	 * @supports highlighting of global $query_search_term.
	 * 
	 * @param WP_Post   $post
	 * @return string   $title
	 */
	public static function get_the_title($post) {
		$title = get_the_title($post);

		global $query_search_term;
		if ( !empty($query_search_term) ) {
			$title = preg_replace( '/('.$query_search_term.')/i', '<mark>$1</mark>', $title );
		}

		return $title;
	}

	/**
	 * Get a clean string from the (post) content.
	 * Similar to the post excerpt but with variable length.
	 * 
	 * @param string    $content
	 * @param int       $length
	 * 
	 * @return string   $excerpt
	 */
	public static function trim_post_content( $raw_content, $length=-1 ) {

		$excerpt = self::strip_all_tags_and_shortcodes( $raw_content );

		if ( $length > 0 && $length < strlen( $excerpt ) ) {
			$excerpt_more = apply_filters('excerpt_more', ' &#91;&hellip;&#93;');
			$excerpt      = mb_substr( $excerpt, 0, $length ) . $excerpt_more;
		}

		/**
		 * @filter greyd_excerpt_from_post_content
		 * @since 1.5.6
		 * 
		 * @param string $excerpt
		 * @param int $length
		 * @param string $raw_content
		 * 
		 * @return string
		 */
		return apply_filters( 'greyd_excerpt_from_post_content', $excerpt, $length, $raw_content );
	}
	
	/**
	 * Strip all tags and shortcodes from a string.
	 * @param string    $string 
	 * @return string   Cleaned $string
	 */
	public static function strip_all_tags_and_shortcodes($string) {
		// debug( esc_attr($string) );

		// strip raw_(html|js) shortcodes & stylesheets
		$string = preg_replace( array(
			'/\[vc_raw_.*?\[\/vc_raw_[a-z]+?\]/',
			'/<script[^>]*?>.*?<\/script>/s',
			'/<style[^>]*?>.*?<\/style>/s'
		), '', $string );

		// strip embed videos, otherwise the url will be shown as text
		$string = preg_replace( '/\<\!\-\- wp\:embed.*?\<\!\-\- \/wp\:embed \-\-\>/s', '', $string );

		// strip normal shortcodes
		$string = preg_replace( '#\[[^\]]+\]#', '', $string );

		// replace breaks with whitespaces
		$string = preg_replace( array( '/<\/h\d>/', '/\n/', '/<br\s?\/?>/' ), ' ', $string );

		// strip all html tags
		$string = strip_tags($string);

		// replace double whitespaces
		$string = preg_replace( '/\s+/', ' ', $string );
		
		return $string;
	}


	/*
	====================================================================================
		from \vc\helper
		functions are copied from vc\helper and used by the modularized dynamic features.
		deprecated versions stay in Theme to be used by the old dynamic features.
	====================================================================================
	*/

	/**
	 * Get dynamic tags for dynamic posttypes.
	 * @requires Posttype feature.
	 * @param WP_Post   $post
	 * @param array $args       Additional arguments
	 *      @property bool rawurlencode         Default: true
	 *      @property bool resolve_file_ids     Default: false
	 * 
	 * @return array    $dtags  Array of dynamic tags callback functions
	 */
	public static function get_dynamic_posttype_tags( $post, $args=array() ) {
		// get posttype data
		$dtags = array();
		if (empty($post)) return $dtags;
		if ($post->post_type == 'product') {
			$dtags = array(
				'price'        => function($post, $params) { return self::make_woo_price($post, $params); },
				'vat'          => function($post, $params) { return self::make_woo_vat($post, $params); },
				'cart-excerpt' => function($post, $params) { return self::make_woo_cart_excerpt($post, $params); },
				'cart'         => function($post, $params) { return self::make_woo_cart($post, $params); },
				'woo_ajax'     => function($post, $params) { return self::make_ajax_link($post, $params); }
			);
		}
		$posttype = Posttypes::get_dynamic_posttype_by_slug($post->post_type);
		if ( !$posttype && ( $post->post_type == 'post' || $post->post_type == 'page' ) ) {
			$posttype = array( "slug" => $post->post_type );
		}
		if ($posttype) {
			if ($post->post_type == 'product') {
				// add custom fields
				$values = Posttypes::get_dynamic_values( $post, $args );
				$fields = (array)$posttype["fields"];
				foreach($fields as $field) {
					$dtags[$field['name']] = isset($values[$field['name']]) ? $values[$field['name']] : '';
					// add media links
					if (isset($values[$field['name'].'-link'])) {
						$dtags[$field['name'].'-link'] = $values[$field['name'].'-link'];
					}
				}
			}
			else {
				$dynamic_values = Posttypes::get_dynamic_values( $post, $args );
				foreach( $dynamic_values as $key => $val ) {
					$dtags[$key] = $val;
				}
				// Custom Taxonomies
				$slug = $posttype['slug'];
				$taxonomies = Helper::get_all_taxonomies($slug);
				if ( !empty($taxonomies) && is_array($taxonomies) && count($taxonomies) > 0 ) {
					foreach( $taxonomies as $tax ) {

						/**
						 * New taxonomy dynamic tag with prefix 'taxonomy-'.
						 * @since 1.7.0
						 */
						$name = self::make_taxonomy_dtag($tax->slug);
						$dtags[$name] = function($post, $params, $tag) { return self::make_taxonomy_string($post, $params, $tag); };

						if ( $tax->slug == $slug."_category" || $tax->slug == $slug."_tag" ) continue;
						$name = self::make_customtaxs_dtag($tax->slug, $slug);
						$dtags[$name] = function($post, $params, $tag) { return self::make_custom_taxonomy_string($post, $params, $tag); };
					}
				}
			}
		}
		
		// debug($dtags);
		return $dtags;
	}

	/**
	 * Get standard dynamic tags.
	 * @param string    $type   'single' or 'archive'
	 * @return array    $dtags  Array of dynamic tags callback functions
	 */
	public static function get_dynamic_tags($type) {
		if ($type == 'single') {
			return array(
				'post-type'     => function($post, $params) { return self::get_posttype_name($post); },
				'post-url'      => function($post, $params) { return self::get_post_permalink($post); },
				'post-link'     => function($post, $params) { return self::get_post_permalink_encoded($post); },
				'title'         => function($post, $params) { return self::get_post_title($post); },
				'content'       => function($post, $params) { return self::make_content_string($post, $params); },
				'excerpt'       => function($post, $params) { return self::get_post_excerpt($post); },
				'author'        => function($post, $params) { return self::make_author_string($post, $params); },
				'author-email'  => function($post, $params) { return self::make_authoremail_string($post, $params); },
				'author-link'   => function($post, $params) { return self::make_authorlink_string($post); },
				'date'          => function($post, $params) { return self::make_date_string($post, $params); },
				'categories'    => function($post, $params) { return self::make_categories_string($post, $params); },
				'tags'          => function($post, $params) { return self::make_tags_string($post, $params); },
				/**
				 * After removing prematching of dynamic tags, 'image' needs to be a url
				 * @since 2.14.0
				 */
				// 'image'         => function($post, $params) { return self::get_post_thumbnail($post); },
				'image'         => function($post, $params) { return self::get_post_thumbnail_url($post); },
				'image-link'    => function($post, $params) { return self::make_imagelink_string($post); },
				'post-views'    => function($post, $params) { return self::get_post_views($post); },
				'site-title'    => function($post, $params) { return self::get_site_title($post); },
				'site-url'      => function($post, $params) { return self::get_site_url($post); },
				'site-logo'     => function($post, $params) { return self::get_site_logo($post); },

				// 'post-ID'       => function($post, $params) { return $post->ID; },
				// 'route'       => function($post, $params) { 
				//     debug($post);
				//     debug(self::get_dynamic_posttype_tags($post));
				//     return 'http://maps.google.com/maps?daddr=_lat_,_lon_&maptype=roadmap'; 
				// },
			);
		}
		if ($type == 'archive') {
			return array(
				'post-type'     => function($query, $params) { return self::make_posttype_string($query, $params); },
				'query'         => function($query, $params) { return "<span class='query--search-query'>".get_search_query()."</span>"; },
				'filter'        => function($query, $params) { return self::make_filter_string($query, $params); },
				'category'      => function($query, $params) { return self::make_category_string($query, $params); },
				'tag'           => function($query, $params) { return self::make_tag_string($query, $params); },
				'post-count'    => function($query, $params) { return "<span class='query--found-posts'>".$query->found_posts."</span>"; },
				'posts-per-page'=> function($query, $params) { return $query->query_vars['posts_per_page']; },
				'page-num'      => function($query, $params) { return $query->query_vars['paged'] ? $query->query_vars['paged'] : 1; },
				'page-count'    => function($query, $params) { return ($query->max_num_pages > 0) ? $query->max_num_pages : 1; },
				'site-logo'     => function($post, $params) { return self::get_site_logo($post); },
			);
		}
	}

	/**
	 * render archive dynamic tags
	 */

	/**
	 * Posttype string.
	 * @param WP_Query  $query
	 * @param String    $atts (unused)
	 * @return String   
	 */
	public static function make_posttype_string($query, $atts) {
		// debug($query);
		$posttype = "alle";
		if ($query->query_vars['post_type'] != "any") {
			if ($query->query_vars['post_type'] != "") {
				$posttype = get_post_type_object($query->query_vars['post_type'])->label;
			}
			else if (get_post_type() != "") {
				$posttype = get_post_type_object(get_post_type())->label;
			}
		}
		return $posttype;
	}

	/**
	 * Query Filter string.
	 * @param WP_Query  $query
	 * @param String    $atts (unused)
	 * @return String   
	 */
	public static function make_filter_string($query, $atts) {
		if (isset($query->query)) $filter = $query->query;
		else {
			global $wp_query;
			// debug( $wp_query );
			$filter = isset($wp_query->query) ? $wp_query->query : [];
			if ( isset( $wp_query->query_vars['post_type'] ) ) $filter['post_type'] = $wp_query->query_vars['post_type'];
			unset($filter['s']);
		}
		
		$return = [];
		foreach ($filter as $key => $val) {
			if ( $key === 'post_type' ) {

				if ( ! is_string($val) && is_array($val) ) {
					$val = implode(",", $val);
				}
				if ( ! is_string($val) ) {
					continue;
				}

				$label = strpos($val, ",") !== false || $val === 'any' || empty($val) ? __('Post Types', 'greyd_hub') : __("Post Type", 'greyd_hub');
				
				
				if ( $val === 'any' || empty($val) ) {
					$val = __("All", 'greyd_hub');
				} else {
					$val = explode(",", $val);
					foreach ( $val as $i => $slug ) {
						$posttype = get_post_type_object($slug);
						if ( $posttype ) $val[$i] = $posttype->labels->name;
					}
					$val = implode(", ", $val);
				}
				$return[] = $label.": ".$val;
			}
			else if ( $tax = get_taxonomy($key) ) {
				$label = strpos($val, ",") !== false || empty($val) ? $tax->label : $tax->labels->singular_name;
				
				if ( empty($val) ) {
					$val = __("All", 'greyd_hub');
				} else {
					$val = explode(",", $val);
					foreach ( $val as $i => $slug ) {
						$term = get_term_by('slug', $slug, $tax->name);
						if ( $term ) $val[$i] = $term->name;
					}
					$val = implode(", ", $val);
				}
				$return[] = $label.": ".$val;
			}
		}
		return implode(" – ", $return);
	}

	/**
	 * Query Category string.
	 * @param WP_Query  $query
	 * @param String    $atts (unused)
	 * @return String   
	 */
	public static function make_category_string($query, $atts) {
		$category = "alle";
		if ($query->query_vars['cat'] > 0) {
			$category = get_cat_name($query->query_vars['cat']);
		}
		else {
			if (isset($query->queried_object) && isset($query->queried_object->taxonomy)) {
				if (strpos($query->queried_object->taxonomy, '_category') > 0 ||
					strpos($query->queried_object->taxonomy, '_cat') > 0) {
					$category = $query->queried_object->name;
				}
			}
		}
		return $category;
	}
	
	/**
	 * Query Tag string.
	 * @param WP_Query  $query
	 * @param String    $atts (unused)
	 * @return String   
	 */
	public static function make_tag_string($query, $atts) {
		$tag = "alle";
		if ($query->query_vars['tag_id'] > 0) {
			$tag = get_tag($query->query_vars['tag_id'])->name;
		}
		else {
			if (isset($query->queried_object) && isset($query->queried_object->taxonomy)) {
				if (strpos($query->queried_object->taxonomy, '_tag') > 0) {
					$tag = $query->queried_object->name;
				}
			}
		}
		return $tag;
	}
	
	/**
	 * render single dynamic tags
	 */

	/**
	 * Posttype string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_posttype_name($post) {
		if ( isset($post->singular_name) ) {
			return $post->singular_name;
		}
		else if ( $posttype_obj = get_post_type_object($post->post_type) ) {
			return $posttype_obj->labels->singular_name;
		}
		return $post->post_type;
	}
	
	/**
	 * Post Permalink string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_permalink($post) {
		return isset($post->permalink) ? $post->permalink : get_permalink($post);
	}
	
	/**
	 * Encoded Post Permalink string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_permalink_encoded($post) {
		return rawurlencode( isset($post->permalink) ? $post->permalink : get_the_permalink($post) );
	}
	
	/**
	 * Post Title string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_title($post) {
		return isset($post->get_all_posts) ? $post->get_all_posts : get_the_title($post);
	}

	/**
	 * Excerpt string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_excerpt($post) {
		return isset($post->excerpt) ? $post->excerpt : self::get_the_excerpt($post);
	}
	
	/**
	 * Post Thumbnail ID.
	 * @param WP_Post   $post
	 * @return String   
	 * 
	 * @deprecated 2.13.0
	 */
	public static function get_post_thumbnail($post) {
		return isset($post->thumb) ? $post->thumb : get_post_thumbnail_id($post);
	}
	
	/**
	 * Post Thumbnail URL string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_thumbnail_url( $post ) {
		return isset($post->image) ? $post->image : get_the_post_thumbnail_url($post);
	}

	/**
	 * Encoded Post Thumbnail URL string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function make_imagelink_string($post) {
		return rawurlencode( isset($post->image) ? $post->image : get_the_post_thumbnail_url($post) );
	}


	/**
	 * Post Content string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_content_string($post, $atts) {

		if ( !is_object($post) || empty($post) ) return null;

		$atts   = explode('|', $atts);
		$count  = isset($atts[1]) ? $atts[1] : "-1";
		$link   = isset($atts[2]) ? $atts[2] : "";

		// render the whole content
		if ( $count == "-1" ) {

			/**
			 * @since 2.9.0 Prevent infinite loops by checking if the post has already been rendered.
			 * 
			 * @since 2.9.1 Fixed an issue preventing the single post content from being rendered.
			 * We now use the same approach as the block 'core/post-content'.
			 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/blocks/post-content.php
			 */
			static $seen_ids = array();
			if ( ! isset( $seen_ids[ $post->ID ] ) ) {
				// If we haven't seen this post yet, set the counter to 1.
				$seen_ids[ $post->ID ] = 1;
			}
			else if ( $seen_ids[ $post->ID ] === 1 ) {
				/**
				 * @since 2.9.2 If we have seen this post exactly once, increment the counter to 2,
				 * but still render the post. This prevents an issue caused by re-building the
				 * WP_Query inside the slider-variation of the post-template block.
				 */
				$seen_ids[ $post->ID ]++;
			}
			else {
				// translators: Visible only in the front end, this warning takes the place of a faulty block.
				return ( WP_DEBUG && WP_DEBUG_DISPLAY ) ? __( '[block rendering halted]' ) : '';
			}
			
			if ( $post->ID == get_the_ID() && is_main_query() && is_singular() ) {

				/**
				 * @since 2.6.0 We manually set the global $wp_query->in_the_loop to true.
				 * This is needed to improve plugin compatibility in classic setups as the 
				 * template to render the post content is called outside the main loop.
				 */
				if ( Helper::is_greyd_classic() && is_single() && !in_the_loop() ) {
					global $wp_query;
					$wp_query->in_the_loop = true;
				}

				/**
				 * @since 2.6.0 When inside the main loop, we want to use queried object
				 * so that `the_preview` for the current post can apply. We force this
				 * behavior by omitting the third argument (post ID) from the `get_the_content`.
				 * 
				 * @deprecated 2.8.0
				 */
				// $content = get_the_content();

				/**
				 * @since 2.8.0 We use the post object to get the content or alternatively
				 * the function `get_the_content`, but with passing the post as an argument,
				 * to prevent potential infinite loops.
				 */
				$content = empty( $post->post_content ) ? get_the_content( null, null, $post ) : $post->post_content;
			}
			else if ( has_blocks($post->post_content) ) {
				if (method_exists('\Greyd\Dynamic\Render_Blocks', 'process_blocks_content')){
					$content = \Greyd\Dynamic\Render_Blocks::process_blocks_content(array( "template" => "" ), $post);
				}
				else if (method_exists('\greyd\blocks\dynamic\Render', 'process_blocks_content')) {
					$content = \greyd\blocks\dynamic\Render::process_blocks_content(array( "template" => "" ), $post);
				}
				else if (method_exists('\greyd\blocks\render', 'process_blocks_content')) {
					$content = \greyd\blocks\render::process_blocks_content(array( "template" => "" ), $post);
				}
				else {
					$content = do_blocks($post->post_content);
				}
			}
			else {
				$content = do_shortcode( shortcode_unautop( $post->post_content ) );
				/**
				 * Replace new lines in html code.
				 * They would already be converted into a p-tag if inside a text-module.
				 * Otherwise they would just create unwanted p-tags.
				 */
				$content = preg_replace( '/(\r\n)+|\r+|\n+|\t+/', '', $content );
			}

			/**
			 * @since 2.8.0 We manually set the global $post to the current post object
			 * before applying the content filter.
			 */
			// setup_postdata( $post );

			/**
			 * @since 2.6.0 We apply the `the_content` filter to the post content to allow
			 * plugins to modify the content before rendering.
			 * This filter is documented in wp-includes/post-template.php
			 */
			$content = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $content ) );

			/** @since 2.8.0 */
			// wp_reset_postdata();
		}
		// cut content length similar to the_excerpt()
		else {
			$content = self::trim_post_content( $post->post_content, $count );
		}
		
		if ( $link == "1" ) {
			return "<a href='".self::get_post_permalink($post)."' title='".self::get_post_title($post)."'>".$content."</a>";
		}

		return $content;
	}
	
	/**
	 * Post Author string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_author_string($post, $atts) {
		if ( !is_object($post) || empty($post) ) return null;
		$atts   = explode('|', $atts);
		$link   = isset($atts[1]) ? $atts[1] : "";
		$author = isset($post->author) ? $post->author : get_the_author_meta('display_name', $post->post_author);
		$url    = isset($post->author_url) ? $post->author_url : get_author_posts_url(get_the_author_meta('ID', $post->post_author));
		if ($link == "1") 
			return "<a href='".$url."' title='All Posts by &quot;".$author."&quot;'>".$author."</a>";
		else 
			return $author;
	}
	
	/**
	 * Post Author Email string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_authoremail_string($post, $atts) {
		if ( !is_object($post) || empty($post) ) return null;
		$atts = explode('|', $atts);
		$link = isset($atts[1]) ? $atts[1] : "";
		$email = get_the_author_meta('user_email', $post->post_author);
		if ($link == "1") 
			return "<a href='mailto:".$email."'>".$email."</a>";
		else 
			return $email;
	}
	
	/**
	 * Post Author Archive string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_authorlink_string($post) {
		if ( !is_object($post) || empty($post) ) return null;
		return rawurlencode( isset($post->author_url) ? $post->author_url : get_author_posts_url(get_the_author_meta('ID', $post->post_author)) );
	}
	
	/**
	 * Post Date string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_date_string($post, $atts) {
		if ( !is_object($post) || empty($post) ) return null;
		$formats = array(
			''      => get_option('date_format'),
			'dmY'   => 'd.m.Y',     // 'TT.MM.JJJJ'
			'dmy'   => 'd.m.y',     // 'TT.MM.JJ'
			'dm'    => 'd.m',       // 'TT.MM.'
			'ldmY'  => 'l d.m.Y',   // 'Tag TT.MM.JJJJ'
			'ldmy'  => 'l d.m.y',   // 'Tag TT.MM.JJ'
			'ldm'   => 'l d.m',     // 'Tag TT.MM.'
			'jFY'   => 'j. F Y',    // 'T. Monat JJJJ'
			'jFy'   => 'j. F y',    // 'T. Monat JJ'
			'jF'    => 'j. F',      // 'T. Monat'
			'ljFY'  => 'l j. F Y',  // 'Tag T. Monat JJJJ'
			'ljFy'  => 'l j. F y',  // 'Tag T. Monat JJ'
			'ljF'   => 'l j. F',    // 'Tag T. Monat'
			'mY'    => 'm.Y',       // 'MM.JJJJ'
			'my'    => 'm.y',       // 'MM.JJ'
			'm'     => 'm',         // 'MM'
			'FY'    => 'F Y',       // 'Monat JJJJ'
			'Fy'    => 'F y',       // 'Monat JJ'
			'F'     => 'F',         // 'Monat'
			'Y'     => 'Y',         // 'JJJJ'
			'y'     => 'y',         // 'JJ'
			'Gi'    => 'G:i',
			'His'   => 'H:i:s',
			'G'     => 'G',
			'm'     => 'i',
			's'     => 's',
		);
		$atts = explode('|', $atts);
		$format = isset($atts[1]) ? $atts[1] : '';

		if ($format == "custom") {
			$custom_format = isset($atts[2]) && !empty($atts[2]) ? $atts[2] : get_option('date_format');
			return mysql2date($custom_format, $post->post_date);
		} else {
			return mysql2date($formats[$format], $post->post_date);
		}
	}

	/**
	 * Post Views string.
	 * @param WP_Post   $post
	 * @return String   
	 */
	public static function get_post_views($post) {
		if ( !is_object($post) || empty($post) ) return null;
		return isset($post->postviews) ? $post->postviews : get_post_meta($post->ID, 'postviews_count', true);
	}
	
	/**
	 * Site Title string.
	 * @param WP_Post   $post (unused)
	 * @return String   
	 */
	public static function get_site_title($post) {
		return get_bloginfo('name');
	}
	
	/**
	 * Site URL string.
	 * @param WP_Post   $post (unused)
	 * @return String   
	 */
	public static function get_site_url($post) {
		return \Greyd\Helper::get_home_url();
	}
	
	/**
	 * Site Logo ID.
	 * @param WP_Post   $post (unused)
	 * @return String   
	 */
	public static function get_site_logo($post) {
		return intval( get_theme_mod( 'custom_logo', -1 ) );
	}
	
	/**
	 * taxonomies
	 */
	
	/**
	 * Post Categories string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_categories_string($post, $atts) {
		$categories = isset($post->categories) ? $post->categories : Helper::get_categories($post);
		return self::make_terms_string($categories, $atts);
	}
	
	/**
	 * Post Tags string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_tags_string($post, $atts) {
		$tags = isset($post->tags) ? $post->tags : Helper::get_tags($post);
		return self::make_terms_string($tags, $atts);
	}
	
	/**
	 * Post Custom Taxonomies string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_custom_taxonomy_string($post, $atts, $tag) {
		$tax_name   = self::solve_customtaxs_dtag($tag, $post->post_type);

		if ( isset( $post->post_taxonomy_terms ) ) {
			$terms = isset( $post->post_taxonomy_terms[ $tax_name ] ) ? $post->post_taxonomy_terms[ $tax_name ] : array();
		} else {
			$terms = wp_get_post_terms($post->ID, $tax_name);
		}

		return self::make_terms_string($terms, $atts);
	}
	
	/**
	 * Post Taxonomies string.
	 * @since 1.7.0
	 * @param WP_Post   $post
	 * @param String    $atts
	 * @return String
	 */
	public static function make_taxonomy_string($post, $atts, $tag) {
		$tax_name   = self::solve_taxonomy_dtag($tag);

		if ( isset( $post->post_taxonomy_terms ) ) {
			$terms = isset( $post->post_taxonomy_terms[ $tax_name ] ) ? $post->post_taxonomy_terms[ $tax_name ] : array();
		} else {
			$terms = wp_get_post_terms($post->ID, $tax_name);
		}
		
		return self::make_terms_string($terms, $atts);
	}

	/**
	 * Render Taxonomies string.
	 * @param WP_Post $post
	 * @param mixed   $atts   When string: 'dtag|format|separator|isLink'
	 *                        When array or object:
	 *     @property string format       The separator format.
	 *     @property bool   isLink       Link the terms.
	 *     @property string description  Show term description.
	 * @return String   
	 */
	public static function make_terms_string($terms, $atts) {
		// debug($terms);
		$separators = array(
			'' => ', ',
			'semi'  => '; ',
			'vert'  => ' | ',
			'hor'   => ' - ',
			'dot'   => ' • ',
			'space' => ' ',
			'br'    => '<br/>',
		);
		if ( is_string($atts) ) {
			$atts     = explode('|', $atts);
			$format   = isset($atts[1]) ? $atts[1] : '';
			$isLink   = isset($atts[2]) ? $atts[2] == '1' : false;
			$desc     = '';
		}
		else if ( is_object($atts) ) {
			$format  = isset($atts->format) ? strval($atts->format) : '';
			$isLink  = isset($atts->isLink) ? (bool) $atts->isLink : false;
			$desc    = isset($atts->description) ? strval($atts->description) : '';
		}
		else {
			$format  = isset($atts['format']) ? strval($atts['format']) : '';
			$isLink  = isset($atts['isLink']) ? (bool) $atts['isLink'] : false;
			$desc    = isset($atts['description']) ? strval($atts['description']) : '';
		}
		$result = "";
		if ( is_array($terms) ) {
			foreach ( $terms as $term ) {
				// debug($term);

				$title   = $term->name;
				$content = $term->name;

				// add description
				if ( $desc == 'show' && !empty($term->description) ) {
					$content .= " (".$term->description.")";
				} else if ( $desc == 'only' ) {
					$content = $term->description;
				}

				if ( empty($content) ) continue;

				// add separator
				if ($result != "") {
					$result .= isset($separators[$format]) ? $separators[$format] : $separators[''];
				}

				$url = get_term_link($term->slug, $term->taxonomy);
				if ( $isLink && !is_wp_error( $url ) ) {
					$result .= "<a href='".$url."' title='Alle &quot;".$title."&quot;'>".$content."</a>";
				} else {
					$result .= $content;
				}
			}
		}
		return $result;
	}

	/**
	 * Helper to convert customtax to dtag slug.
	 * @return String
	 */
	public static function make_customtaxs_dtag($tax_name, $posttype) {
		return "customtax-".str_replace( $posttype."_", "", $tax_name );
	}

	/**
	 * Helper to convert dtag to customtax slug.
	 * @return String
	 */
	public static function solve_customtaxs_dtag($dtag, $posttype) {
		return $posttype."_".str_replace( "customtax-", "", $dtag );
	}

	/**
	 * Helper to convert taxonomy to dtag slug.
	 * @since 1.7.0
	 * @return String
	 */
	public static function make_taxonomy_dtag($tax_name) {
		return "taxonomy-".$tax_name;
	}

	/**
	 * Helper to convert dtag to taxonomy slug.
	 * @since 1.7.0
	 * @return String
	 */
	public static function solve_taxonomy_dtag($dtag) {
		return str_replace( "taxonomy-", "", $dtag );
	}

	/**
	 * woocommerce
	 */

	/**
	 * Product Price string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_woo_price($post, $atts) {

		if ( !function_exists('wc_get_product') ) return null;
		if ( !is_object($post) || empty($post) ) return null;

		$product = wc_get_product($post->ID);
		if ( is_a($product, 'WC_Product')  ) {
			$price = number_format(wc_get_price_to_display($product), 2, ',', '');
			$symbol = get_woocommerce_currency_symbol();
			if ($atts != "" && $atts != "||") {
				$atts = explode('|', $atts);
				$sympos = isset($atts[1]) ? $atts[1] : '';
				$format = isset($atts[2]) ? $atts[2] : '';
				if ($format == 'none') {
					$price = explode(',', $price);
					$price = $price[0];
				}
				if ($sympos == 'after') return $price." ".$symbol;
				if ($sympos == 'before') return $symbol." ".$price;
				if ($sympos == 'afterh') return $price." <sup>".$symbol."</sup>";
				if ($sympos == 'beforeh') return "<sup>".$symbol."</sup> ".$price;
				if ($sympos == 'hide') return $price;
			}
			return sprintf(get_woocommerce_price_format(), $symbol, $price);
		} else return NULL;
	}

	/**
	 * Product Vat string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_woo_vat($post, $atts) {

		if ( !function_exists('wc_get_product') ) return null;

		$product = wc_get_product($post->ID);
		
		if ( is_a($product, 'WC_Product') ) {

			// get Tax info:
			$tax_info = "0";
			$tax_rate = \WC_Tax::get_rates($product->get_tax_class());
			
			if (!empty($tax_rate)) {
				$tax_rate = reset($tax_rate);
				$tax_info = (int)$tax_rate['rate']." %";
			}
			
			// wc germanized
			// if (wc_gzd_get_gzd_product($product)->get_tax_info())
			//     $tax_info = wc_gzd_get_gzd_product($product)->get_tax_info();
			// else if (get_option('woocommerce_gzd_small_enterprise') == 'yes')
			//     $tax_info = wc_gzd_get_small_business_product_notice();
			return $tax_info;
		} else return NULL;
	}
	
	/**
	 * Product Cart URL string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_woo_cart($post, $atts) {

		if ( !function_exists('wc_get_product') ) return null;
		if ( !is_object($post) || empty($post) ) return null;

		if ( is_a($post, 'WC_Product')  ) {
			$product = wc_get_product($post->ID);
			return esc_url($product->add_to_cart_url()); 
		} else return NULL;
	}

	/**
	 * Product Cat/Mini Excerpt string.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_woo_cart_excerpt($post, $atts) {

		if ( !function_exists('wc_get_product') ) return null;
		if ( !is_object($post) || empty($post) ) return null;

		$product = wc_get_product($post->ID);
		if ( is_a($product, 'WC_Product')  ) {
			return wc_gzd_get_gzd_product($product)->get_mini_desc();
		} else return NULL;
	}
	
	/**
	 * Product Add to Cart Ajax Tag.
	 * @param WP_Post   $post
	 * @param String    $atts   
	 * @return String   
	 */
	public static function make_ajax_link($post, $atts) {
		if ( !is_object($post) || empty($post) ) return null;
		// $atts = explode('|', $atts);
		// $atts[0] = $atts[0].'='.$post->ID;
		// $atts = implode('|', $atts);
		// return $atts;
		return '_woo_ajax='.$post->ID.$atts.'_';
	}


	/*
	====================================================================================
		from \greyd\blocks\helper
		additional tag rendering functions only used in blocks.
		function to be deleted there.
	====================================================================================
	*/

	/**
	 * Get the woocommerce sale flash banner html.
	 * @see woccomerce/templates/sale-flash.php
	 * 
	 * @param WP_Post|int $post     Post object or ID.
	 * @param array $atts           Custom attributes.
	 * @return string
	 */
	public static function get_woo_sale_flash( $post, $atts=array() ) {

		// get post
		$post = is_object($post) ? $post : get_post( $post );
		if ( !$post || !isset($post->ID) ) return "";

		// get product
		$product = wc_get_product( $post->ID );
		if ( !$product || !$product->is_on_sale() ) return "";

		if ( isset($atts['showPercent']) && $atts['showPercent'] ) {
			$text = self::get_woo_sale_percentage( $product );
			if ( !empty( $text ) ) {
				$text = '-'.$text;
			}
		}
		else {
			$text = esc_html__( 'Sale!', 'woocommerce' );
		}
		
		return apply_filters( 'woocommerce_sale_flash', '<span class="onsale">'.$text.'</span>', $post, $product );
	}
	
	/**
	 * Format the woocommerce price.
	 * @since 1.1.2
	 * 
	 * @param WP_Post   $post
	 * @param Object    $params
	 * @return String   Formatted price.
	 */
	public static function get_woo_price($post, $params) {

		if ( !function_exists('wc_get_product') ) return null;

		$product = wc_get_product($post->ID);
		if ( !is_a($product, 'WC_Product') ) return '';

		$return = "";
		$params = (object) wp_parse_args( $params, array(
			'symbol' => '',
			'format' => '',
			'format_thousands' => '',
			'showSale' => '',
		) );

		/**
		 * format thousands seperator
		 * @since 1.7.0
		 */
		$format_thousands = $params->format_thousands;
		if ( $format_thousands == 'space' ) $format_thousands = ' ';
		if ( $format_thousands == 'dot' ) $format_thousands = '.';

		$price  = number_format( wc_get_price_to_display($product), 2, ',', $format_thousands );
		$symbol = get_woocommerce_currency_symbol();

		/**
		 * Show previous price
		 * 
		 * @since 1.1.2
		 */
		if ( !empty($params->showSale) && $product->is_on_sale() ) {
			$regular_price = number_format( $product->get_regular_price(), 2, ',', $format_thousands );
			$return .= '<span class="price"><del aria-hidden="true"><bdi>'.self::format_woo_price( $regular_price, $params->symbol, $params->format ).'</bdi></del></span> ';
		}

		$return .= self::format_woo_price( $price, $params->symbol, $params->format, $symbol );

		return $return;
	}

	/**
	 * Format a price into a custom format.
	 * @since 1.1.2
	 * 
	 * @param String    $price
	 * @param String    $sympos Position of currency symbol (optional)
	 * @param String    $format (optional)
	 * @param String    $symbol Currency symbol (optional)
	 * @return String   Custom formatted price.
	 */
	public static function format_woo_price( $price, $sympos='', $format='', $symbol=null ) {
		if ( !$symbol ) {
			$symbol = get_woocommerce_currency_symbol();
		}
		if ($format == 'none') {
			$price = explode(',', $price);
			$price = $price[0];
		}
		if ($sympos == 'after') return $price.'&nbsp;'.$symbol;
		if ($sympos == 'before') return $symbol.'&nbsp;'.$price;
		if ($sympos == 'afterh') return $price.'&nbsp;<sup>'.$symbol.'</sup>';
		if ($sympos == 'beforeh') return '<sup>'.$symbol.'</sup>&nbsp;'.$price;
		if ($sympos == 'hide') return $price;

		// default
		return sprintf(get_woocommerce_price_format(), $symbol, $price);
	}

	/**
	 * Get the sale percentage of a product.
	 * @see https://gist.github.com/ashokmhrj/9c1c26a450adb3c3b22f0ebd73a5f598
	 * 
	 * @param WC_Product $product
	 * @return string
	 */
	public static function get_woo_sale_percentage( $product ) {

		if ( !is_a($product, 'WC_Product') ) return '';

		if ( $product->get_type() == 'variable' ) {
			$available_variations = $product->get_available_variations();
			$maximumper = 0;
			for ($i = 0; $i < count($available_variations); ++$i) {
				$variation_id=$available_variations[$i]['variation_id'];
				$variable_product1= new WC_Product_Variation( $variation_id );
				$regular_price = $variable_product1->get_regular_price();
				$sales_price = $variable_product1->get_sale_price();
				if ( $sales_price ) {
					$percentage= round( ( ( $regular_price - $sales_price ) / $regular_price ) * 100 ) ;
					if ($percentage > $maximumper) {
						$maximumper = $percentage;
					}
				}
			}
			return $maximumper.'%';
		} 
		elseif ( $product->get_type() == 'simple' ) {
			$percentage = round( ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100 );
			return $percentage.'%';
		}
	}


	/*
	====================================================================================
		Deprecated
		make_clean_vc_string (from \vc\helper)
	====================================================================================
	*/

	/**
	 * @deprecated since 1.5.6
	 * 
	 * Get a clean string from the (post) content.
	 * Similar to the post excerpt but with variable length.
	 * @param string    $content
	 * @param int       $count  Lenth of the cleaned content (-1 for full length)
	 * @return string   Cleaned $content
	 */
	public static function make_clean_vc_string($content, $count) {
		// hard remove if exists
		$shortcodes = array("vc_row", "vc_row_inner", "vc_column", "vc_column_inner", "vc_column_text" );
		foreach($shortcodes as $sc) {
			$content = preg_replace("/\[[\/]?".$sc."(.*?)\]/", "", $content);
		}
		// crawl templates
		$shortcodes = false;
		if (method_exists('\vc\helper', 'get_shortcodes'))
			$shortcodes = \vc\helper::get_shortcodes($content, array( 'dynamic' ));
		if ($shortcodes) {
			// debug($shortcodes);
			foreach ($shortcodes as $shortcode) {
				if ($shortcode['atts'] != "") {
					$slug = "";
					$dynamic_content = "";
					foreach ($shortcode['atts'] as $att) {
						if ($att['key'] == 'template') {
							$slug = $att['value'];
						}
						if ($att['key'] == 'dynamic_content') {
							$dynamic_content = $att['value'];
						}
					}
					if ($slug != "") {
						$template = self::get_template_post($slug);
						if (
							$template &&
							is_object($template) &&
							isset($template->post_content)
						) {
							$c = $template->post_content;
							if ($dynamic_content != "") {
								$dynamic_values = explode('::', $dynamic_content);
								if ($dynamic_values[0] == $slug && count($dynamic_values) > 1) {
									$c = \Greyd\Dynamic\Render::content_match_dynamic($dynamic_values, $c);
								}
							}
							$template_content = self::make_clean_vc_string($c, -1);
							$content = str_replace($shortcode['raw'], $template_content, $content);
						}
					}
				}
			}
		}
		// clean string
		$content = self::strip_all_tags_and_shortcodes($content);
		if ($count > -1) {
			// debug($content);
			$points = ($count < strlen($content)) ? apply_filters('excerpt_more', ' &#91;&hellip;&#93;') : "";
			$content = mb_substr($content, 0, $count).$points;
		}
		return $content;
	}
}