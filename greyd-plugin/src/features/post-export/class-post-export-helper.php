<?php
/**
 * Export & Import Post Helper
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Post_Export_Helper();

class Post_Export_Helper {

	/**
	 * Holds the relative path to the export folder.
	 *
	 * @var string
	 */
	public static $basic_path = null;

	/**
	 * Whether logs are echoed.
	 * Usually set via function @see enable_logs();
	 * Logs are especiallly usefull when debugging ajax actions.
	 *
	 * @var bool
	 */
	public static $logs = false;

	/**
	 * Holds the current language code
	 *
	 * @var string
	 */
	public static $language_code = null;

	/**
	 * Get all supported posttypes for global contents
	 */
	public static function get_supported_post_types() {

		// check cache
		if ( $cache = wp_cache_get( 'get_supported_post_types', 'greyd_post_export' ) ) {
			return $cache;
		}

		if ( class_exists( '\Greyd\Posttypes\Dynamic_Posttypes' ) ) {
			// register all dynamic post types & taxonomies
			\Greyd\Posttypes\Dynamic_Posttypes::add_dynamic_posttypes();
		}


		$include   = array( 'page', 'post', 'attachment', 'wp_template', 'wp_template_part', 'wp_block', 'wp_navigation' );
		$exclude   = array( 'tp_forms_entry', 'vc_grid_item', 'vc4_templates' );

		$posttypes = array_keys( get_post_types( array( '_builtin' => false ) ) );
		// foreach ( \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttypes() as $pt ) {
		// 	if ( !in_array( $pt['slug'], $posttypes ) ) $posttypes[] = $pt['slug'];
		// }

		$supported = array_diff( array_merge( $include, $posttypes ), $exclude );

		// Set cache
		wp_cache_set( 'get_supported_post_types', $supported, 'greyd_post_export' );

		return $supported;
	}

	/**
	 * Get path to the export folder. Use this path to write files.
	 *
	 * @param string $folder Folder inside wp-content/backup/posts/
	 *
	 * @return string $path
	 */
	public static function get_file_path( $folder = '' ) {

		// get basic path from class var
		if ( self::$basic_path ) {
			$path = self::$basic_path;
		}
		// init basic path
		else {
			$path = WP_CONTENT_DIR . '/backup';

			if ( ! file_exists( $path ) ) {
				do_action( 'greyd_post_export_log', sprintf( '  - create folder "%s".', $path ) );
				mkdir( $path, 0755, true );
			}
			$path .= '/posts';
			if ( ! file_exists( $path ) ) {
				do_action( 'greyd_post_export_log', sprintf( '  - create folder "%s".', $path ) );
				mkdir( $path, 0755, true );
			}
			$path .= '/';

			// save in class var
			self::$basic_path = $path;
		}

		// get directory
		if ( ! empty( $folder ) ) {
			$path .= $folder;
			if ( ! file_exists( $path ) ) {
				do_action( 'greyd_post_export_log', sprintf( '  - create folder "%s".', $path ) );
				mkdir( $path, 0755, true );
			}
		}

		$path .= '/';
		return $path;
	}

	/**
	 * Convert file path to absolute path. Use this path to download a file.
	 *
	 * @param string $path File path.
	 *
	 * @return string $path
	 */
	public static function convert_content_dir_to_path( $path ) {
		$url = str_replace(
			WP_CONTENT_DIR,
			WP_CONTENT_URL,
			$path
		);

		if ( is_ssl() ) {
			$url = str_replace( 'http://', 'https://', $url );
		}

		return $url;
	}

	/**
	 * Returns list of blacklisted meta keys that should not be exported.
	 *
	 * This function provides a list of meta keys that are automatically
	 * excluded from post exports. The list can be customized using the
	 * 'greyd_export_blacklisted_meta' filter.
	 *
	 * @return array Array of meta keys to exclude from export.
	 */
	public static function blacklisted_meta() {
		/**
		 * Filter to customize the list of blacklisted meta keys for export.
		 * 
		 * This filter allows developers to add or remove meta keys that should
		 * be excluded from post exports. It's useful for preventing sensitive
		 * or site-specific meta data from being exported.
		 * 
		 * @filter greyd_export_blacklisted_meta
		 * 
		 * @param array $blacklisted_meta Array of meta keys to exclude from export.
		 * 
		 * @return array                   Modified array of blacklisted meta keys.
		 */
		return apply_filters(
			'greyd_export_blacklisted_meta',
			array(
				'_wp_attached_file',
				'_wp_attachment_metadata',
				'_edit_lock',
				'_edit_last',
				'_wp_old_slug',
				'_wp_old_date',
				'_wpb_vc_js_status',
			)
		);
	}

	/**
	 * Check whether to skip a certain meta key and not im- or export it
	 */
	public static function maybe_skip_meta_option( $meta_key, $meta_value ) {

		// skip empty options
		if ( $meta_value === '' ) {
			// do_action( "greyd_post_export_log","  - skipped empty meta option for '$meta_key'");
			return true;
		}
		// skip oembed meta options
		elseif ( strpos( $meta_key, '_oembed_' ) === 0 ) {
			do_action( 'greyd_post_export_log', "  - skipped oembed option '$meta_key'" );
			return true;
		}
		// skip wpml meta if plugin not active
		elseif ( strpos( $meta_key, '_wpml_' ) === 0 ) {
			if ( Post_Export_Helper::get_translation_tool() !== 'wpml' ) {
				do_action( 'greyd_post_export_log', "  - skipped wpml option '$meta_key'" );
				return true;
			}
		}
		// skip yoast meta if plugin not active
		elseif ( strpos( $meta_key, '_yoast_' ) === 0 ) {
			if ( ! Helper::is_active_plugin( 'wordpress-seo/wp-seo.php' ) ) {
				do_action( 'greyd_post_export_log', "  - skipped yoast option '$meta_key'" );
				return true;
			}
		}
		/**
		 * Filter to determine whether a specific meta option should be skipped during export.
		 * 
		 * This filter allows developers to implement custom logic for determining
		 * whether specific meta keys or values should be excluded from export.
		 * It's useful for implementing site-specific export rules or business logic.
		 * 
		 * @filter greyd_export_maybe_skip_meta_option
		 * 
		 * @param bool   $skip_meta    Whether to skip the meta option (default: false).
		 * @param string $meta_key     The meta key being evaluated.
		 * @param mixed  $meta_value   The meta value being evaluated.
		 * 
		 * @return bool                Whether to skip the meta option.
		 */
		return apply_filters( 'greyd_export_maybe_skip_meta_option', false, $meta_key, $meta_value );
	}

	/**
	 * Get post by name and post_type
	 *
	 * eg. checks if post already exists.
	 *
	 * @param object|string $post   WP_Post object or post_name
	 *
	 * @return bool|object False on failure, WP_Post on success.
	 */
	public static function get_post_by_name_and_type( $post ) {

		$post_name = is_object( $post ) ? (string) $post->post_name : (string) $post;
		$post_type = is_object( $post ) ? (string) $post->post_type : self::get_supported_post_types();
		$args      = array(
			'name'        => $post_name,
			'post_type'   => $post_type,
			'numberposts' => 1,
			'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
		);

		// only get post of same language
		if ( Post_Export_Helper::switch_to_post_lang( $post ) ) {
			$args['suppress_filters'] = false;
		} else {
			$args['suppress_filters'] = true;
			$args['lang'] = '';
		}

		// query
		$result = get_posts( $args );

		if ( is_array( $result ) && isset( $result[0] ) ) {
			do_action( 'greyd_post_export_log', sprintf( "  - %s found with ID '%s'.", $post->post_type, $result[0]->ID ) );
			return $result[0];
		} else {
			do_action( 'greyd_post_export_log', sprintf( "  - Post '%s' not found by name and post type.", $post_name ) );
			return false;
		}
	}

	/**
	 * Get existing post ID by name and post_type
	 * 
	 * @param object|string $post   WP_Post object or post_name
	 * 
	 * @return int 0 on failure, post ID on success.
	 */
	public static function get_existing_post_id( $post ) {
		$existing_post = self::get_post_by_name_and_type( $post );
		if ( $existing_post && isset( $existing_post->ID ) ) {
			do_action( 'greyd_post_export_log', sprintf( '  - existing post with ID: %s.', $existing_post->ID ) );
			return $existing_post->ID;
		}
		return 0;
	}

	/**
	 * Return error to frontend
	 */
	public static function error( $message = '' ) {
		if ( self::$logs ) {
			echo "\r\n\r\n";
		}
		wp_die( 'error::' . $message );
	}

	/**
	 * Return success to frontend
	 */
	public static function success( $message = '' ) {
		if ( self::$logs ) {
			echo "\r\n\r\n";
		}
		wp_die( 'success::' . $message );
	}

	/**
	 * Toggle debug logs
	 *
	 * @param bool $enable
	 */
	public static function enable_logs( $enable = true ) {
		self::$logs = (bool) $enable;

		add_action( 'greyd_post_export_log', array( '\Greyd\Post_Export_Helper', 'log' ), 10, 2 );
	}

	/**
	 * Echo a log
	 */
	public static function log( $message, $var = 'do_not_log' ) {
		if ( self::$logs ) {
			if ( ! empty( $message ) ) {
				echo "\r\n" . $message;
			}
			if ( $var !== 'do_not_log' ) {
				echo "\r\n";
				print_r( $var );
				echo "\r\n";
			}
		}
	}

	/**
	 * Get the translation tool of this stage.
	 *
	 * @since 1.6 Function supports WPML.
	 * @since 2.7 Function supports polylang.
	 */
	public static function get_translation_tool() {
		$tool = null;

		$plugins = \Greyd\Helper::active_plugins();
		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', $plugins ) ) {
			$tool = 'wpml';
		}
		else if ( in_array( 'polylang/polylang.php', $plugins ) ) {
			$tool = 'polylang';
		}
		else if ( in_array( 'polylang-pro/polylang.php', $plugins ) ) {
			$tool = 'polylang';
		}

		return $tool;
	}

	/**
	 * Load translation tool.
	 * After a call to 'switch_to_blog($blog_id)', the plugins and globals of that blog are not activated or changed.
	 * If called e.g. from the network_admin without globally enabled translation tool, the tools functions can not be called for each blog.
	 * 
	 * @since 2.7 supports wpml (unused) and polylang
	 * - wpml is unused (not implemented) because the infos get queried directly from db.
	 * - polylang can be loaded temporarily by including the plugin php and calling the init() function.
	 */
	public static function load_translation_tool( $tool ) {

		switch ( $tool ) {
			case 'wpml':
				// unused - infos get queried directly from db
				break;
			case 'polylang':
				if ( !function_exists( 'PLL' ) ) {
					$plugin_path = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ).'polylang/polylang.php' );
					if ( file_exists($plugin_path) ) {
						include_once $plugin_path;
						// debug(POLYLANG_DIR);
						if ( class_exists( '\Polylang' ) ) {
							(new \Polylang())->init();
						}
					} else {
						$plugin_path = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ).'polylang-pro/polylang.php' );
						if ( file_exists($plugin_path) ) {
							include_once $plugin_path;
							// debug(POLYLANG_DIR);
							if ( class_exists( '\Polylang' ) ) {
								(new \Polylang())->init();
							}
						}
					}
				}
				if ( function_exists( 'PLL' ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Get language info of a post
	 *
	 * @param WP_Post $post
	 *
	 * @return array|null
	 */
	public static function get_post_language_info( $post ) {

		$tool = Post_Export_Helper::get_translation_tool();

		switch ( $tool ) {
			case 'wpml':
				global $wpdb;
				$table_name = "{$wpdb->prefix}icl_translations";
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
					do_action( 'greyd_post_export_log', "  - database table '{$table_name}' does not exist." );
					break;
				}

				$query  = $wpdb->prepare(
					"SELECT language_code, element_id, trid, source_language_code
					FROM {$table_name}
					WHERE element_id=%d
					AND element_type=%s
					LIMIT 1",
					array( $post->ID, 'post_' . $post->post_type )
				);
				$result = $wpdb->get_results( $query );
				if ( is_array( $result ) && count( $result ) ) {
					return (array) $result[0];
				}
				break;
			case 'polylang':
				if ( Post_Export_Helper::load_translation_tool( $tool ) ) {
					return array(
						'language_code' => pll_get_post_language($post->ID)
					);
				}
				break;
		}
		return null;
	}

	/**
	 * Get all language codes
	 *
	 * @return array
	 */
	public static function get_language_codes() {

		$tool = Post_Export_Helper::get_translation_tool();

		switch ( $tool ) {
			case 'wpml':
				global $wpdb;
				$table_name = "{$wpdb->prefix}icl_languages";
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
					do_action( 'greyd_post_export_log', "  - database table '{$table_name}' does not exist." );
					break;
				}

				$query  = $wpdb->prepare(
					"SELECT code
					FROM {$table_name}
					WHERE active=%d",
					array( 1 )
				);
				$result = $wpdb->get_results( $query );
				if ( is_array( $result ) && count( $result ) ) {
					return array_map(
						function( $object ) {
							return esc_attr( $object->code );
						},
						$result
					);
				}
				break;
			case 'polylang':
				if ( Post_Export_Helper::load_translation_tool( $tool ) ) {
					return pll_languages_list();
				}
				break;
		}

		// default
		return array( Post_Export_Helper::get_wp_lang() );
	}

	/**
	 * Get language code of the current site (2 chars).
	 */
	public static function get_wp_lang() {
		return explode( '_', get_locale(), 2 )[0];
	}

	/**
	 * Get all translated post IDs
	 *
	 * @param WP_Post $post             The post object (uses ID & post_type)
	 * @param bool    $exclude_current     Whether to include the current $post_id
	 *
	 * @return array
	 */
	public static function get_translated_post_ids( $post, $exclude_current = true ) {
		$return = array();

		$tool = Post_Export_Helper::get_translation_tool();

		switch ( $tool ) {
			case 'wpml':
				global $wpdb;
				$table_name = "{$wpdb->prefix}icl_translations";
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
					do_action( 'greyd_post_export_log', "  - database table '{$table_name}' does not exist." );
					break;
				}

				$query  = $wpdb->prepare(
					"SELECT trid
					FROM {$table_name}
					WHERE element_id=%d
					AND element_type=%s
					LIMIT 1",
					array( $post->ID, 'post_' . $post->post_type )
				);
				$result = $wpdb->get_var( $query );

				if ( is_string( $result ) ) {

					$query  = $wpdb->prepare(
						"SELECT language_code, element_id, trid, source_language_code
						FROM {$table_name}
						WHERE trid=%d
						AND element_type=%s",
						array( $result, 'post_' . $post->post_type )
					);
					$result = $wpdb->get_results( $query );

					if ( is_array( $result ) && count( $result ) ) {
						foreach ( $result as $object ) {
							$lang      = isset( $object->language_code ) ? $object->language_code : null;
							$elem_id   = isset( $object->element_id ) ? (int) $object->element_id : null;
							$is_source = empty( $object->source_language_code );

							if ( $lang && $elem_id && ! ( $exclude_current && $post->ID == $elem_id ) ) {
								// log item as first if it's the source
								if ( $is_source ) {
									$return = array_reverse( $return, true );
								}
								$return[ $lang ] = $elem_id;
								if ( $is_source ) {
									$return = array_reverse( $return, true );
								}
							}
						}
					}
				}
				break;
			case 'polylang':
				if ( Post_Export_Helper::load_translation_tool( $tool ) ) {
					$return = pll_get_post_translations( $post->ID );
				}
				break;
		}

		return $return;
	}

	/**
	 * Switch language if set & supported on current site
	 *
	 * @param WP_Post $post The post object (uses @param array language)
	 *
	 * @return mixed        null:   Post doesn't hold language information.
	 *                      false:  Language is not supported on this site.
	 *                      true:   Language is supported and switched.
	 */
	public static function switch_to_post_lang( $post ) {

		$language  = isset( $post->language ) && ! empty( $post->language ) ? (array) $post->language : array();
		$post_lang = isset( $language['code'] ) ? $language['code'] : null;
		if ( empty( $post_lang ) ) {
			return null;
		}

		// check whether the language is supported on the current site.
		$is_lang_supported = in_array( $post_lang, Post_Export_Helper::get_language_codes() );
		if ( ! $is_lang_supported ) {
			do_action( 'greyd_post_export_log', "  - the language '$post_lang' is not part of the supported languages (" . implode( ', ', Post_Export_Helper::get_language_codes() ) . ')' );
			return false;
		}

		// we've already switched to this language
		if ( self::$language_code && self::$language_code === $post_lang ) {
			return true;
		}

		$tool = Post_Export_Helper::get_translation_tool();

		switch ( $tool ) {
			case 'wpml':
				global $sitepress;
				/**
				 * The @var object sitepress is not initialized, when we're trying to call
				 * this function from a different blog without WPML, eg. when the root post
				 * is updated on a single-language site.
				 */
				if ( $sitepress && method_exists( $sitepress, 'switch_lang' ) ) {
					$sitepress->switch_lang( $post_lang );
					self::$language_code = $post_lang;
					do_action( 'greyd_post_export_log', sprintf( "  - switched the language to '%s'.", $post_lang ) );
					return true;
				} else {
					do_action( 'greyd_post_export_log', sprintf( "  - the global \$sitepress object is not initialized, so we could not switch to the language '%s'.", $post_lang ) );
				}
				break;
			case 'polylang':
				if ( Post_Export_Helper::load_translation_tool( $tool ) ) {
					\PLL()->curlang = \PLL()->model->get_language($post_lang);
					self::$language_code = $post_lang;
					do_action( 'greyd_post_export_log', sprintf( "  - switched the language to '%s'.", $post_lang ) );
					return true;
				}
				break;

			default:
				if ( $post_lang === Post_Export_Helper::get_wp_lang() ) {
					self::$language_code = $post_lang;
					return true;
				}
		}
		return false;
	}

	/**
	 * Set post language.
	 * 
	 * @param WP_Post|int $post_or_post_id The post object or post ID.
	 * @param string      $lang_code       The language code.
	 * 
	 * @return bool
	 */
	public static function set_post_lang( $post_or_post_id, $lang_code ) {

		$post_id = is_object( $post_or_post_id ) ? $post_or_post_id->ID : $post_or_post_id;

		$tool = Post_Export_Helper::get_translation_tool();

		switch ( $tool ) {
			case 'wpml':
				global $sitepress;
				// @todo
				break;
			case 'polylang':
				if ( function_exists( 'pll_set_post_language' ) ) {
					pll_set_post_language( $post_id, $lang_code );
					do_action( 'greyd_post_export_log', sprintf( "  - set language of post '%s' to '%s'.", $post_id, $lang_code ) );
					return true;
				}
				break;
		}
		return false;
		
	}

	/**
	 * Register temporary taxonomies to retrieve terms from the database.
	 * We trick WordPress into thinking our taxonomies are valid so we
	 * don't get the 'taxonomy doesn't exist' error.
	 *
	 * @see https://stackoverflow.com/questions/64320279/woocommerce-multisite-network-get-attribute-terms-of-products-from-other-blog
	 *
	 * @param string $post_type
	 *
	 * @return array Array of taxonomy arguments keyed by slug.
	 */
	public static function get_dynamic_taxonomies( $post_type = null ) {

		if (
			! class_exists( '\Greyd\Posttypes\Posttype_Helper' )
			|| ! class_exists( '\Greyd\Posttypes\Dynamic_Posttypes' )
		) {
			return array();
		}

		// register all dynamic post types & taxonomies
		\Greyd\Posttypes\Dynamic_Posttypes::add_dynamic_posttypes( false );

		// get dynamic taxonomies
		$dynamic_taxonomies = \Greyd\Posttypes\Posttype_Helper::get_dynamic_taxonomies( $post_type );

		// log
		if ( ! empty( $dynamic_taxonomies ) ) {
			do_action( 'greyd_post_export_log', sprintf( "  - found dynamic taxonomies: %s", implode( ', ', array_keys( $dynamic_taxonomies ) ) ) );
		} else {
			do_action( 'greyd_post_export_log', "  - no dynamic taxonomies found." );
		}

		return empty( $dynamic_taxonomies ) ? array() : array_keys( $dynamic_taxonomies );
	}

	/**
	 * Replace strings for export
	 *
	 * @param string $subject
	 * @param int    $post_id
	 *
	 * @return string $subject
	 */
	public static function replace_dynamic_strings( $subject, $post_id, $log = true ) {

		if ( empty( $subject ) ) {
			return $subject;
		}

		if ( $log ) {
			do_action( 'greyd_post_export_log', "\r\n" . 'Prepare strings.' );
		}

		// get patterns
		$replace_strings = (array) get_nested_string_patterns( $subject, $post_id );
		foreach ( $replace_strings as $name => $string ) {
			$subject = str_replace( $string, '{{' . $name . '}}', $subject );
			// if ($log) do_action( "greyd_post_export_log", sprintf("  - '%s' was preparred for export.", $name ) );
		}
		if ( $log ) {
			do_action( 'greyd_post_export_log', '=> strings were preparred' );
		}
		return $subject;
	}
}
