<?php
/**
 * Headless posttype features.
 */
namespace Greyd\Headless;

use Greyd\Helper as Helper;
use Greyd\Posttype_Helper as Posttype_Helper;

use Greyd\Synced_Posttype as Synced_Posttype;
use Greyd\Automator as Automator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Api_Posttypes( $config );
class Api_Posttypes {

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

		add_action( 'init', array( $this, 'add_posttypes' ) );
	}


	/**
	 * =================================================================
	 *                          Setup
	 * =================================================================
	 */

	/**
	 * Add posttypes
	 */
	public function add_posttypes() {

		$api_posttypes = self::get_all_api_posttypes();
		// debug( $api_posttypes );

		foreach ( $api_posttypes as $api_slug => $posttypes ) {
			foreach ( $posttypes as $name => $posttype ) {

				if ( ! empty( $posttype['posttype_settings'] ) ) {
					$synced_posttype = new Synced_Posttype( $posttype['posttype_settings'] );
				}

				$posttype_slug = isset($posttype['posttype_settings']['post_name'] ) ? $posttype['posttype_settings']['post_name'] : $name;

				if ( ! empty( $posttype['api_settings'] ) ) {

					$api_settings = $posttype['api_settings'];

					$menu = $posttype_slug === 'post' ? 'edit.php' : 'edit.php?post_type=' . $posttype_slug;
					// make importer for global taxonomies accessible (no menu item)
					if ( isset($posttype['posttype_settings']['is_taxonomy']) ) $menu = 'admin.php';
					
					$automator = new Automator(
						array(
							'slug'       => $posttype_slug . '-importer',
							'hook'       => 'api_import_' . $name,
							'reset'      => 'api_reset_' . $name,
							'menu'       => $menu,
							'title'      => __( 'Import posts', 'greyd_hub' ),
							'menu_title' => __( 'Import', 'greyd_hub' ),
						)
					);

					$api_config = array(
						'api_slug'      => $api_slug,
						'posttype_slug' => $posttype_slug,
						'api_settings'  => $api_settings,
					);

					add_action(
						'api_import_' . $name,
						function( $last_timestamp ) use ( $api_config, $automator ) {
							$this->import_posts( $api_config, $last_timestamp, $automator );
						}
					);
					add_action(
						'api_reset_' . $name,
						function( $last_timestamp ) use ( $api_config, $automator ) {
							$this->reset_posts( $api_config, $last_timestamp, $automator );
						}
					);
				}
			}
		}
	}


	/**
	 * =================================================================
	 *                          Import
	 * =================================================================
	 */

	/**
	 * Import posts into posttype.
	 * 
	 * @param array $api_config
	 *     @property string api_slug
	 *     @property string posttype_slug
	 *     @property array  api_settings
	 * @param int $last_timestamp
	 * @param object $automator
	 */
	public function import_posts( $api_config, $last_timestamp, $automator ) {
		$automator->log( "Import '{$api_config['posttype_slug']}' posts of API {$api_config['api_slug']} (timestamp: $last_timestamp)" );

		$api_route = $api_config['api_slug'] . '/' . $api_config['api_settings']['route'];
		$api       = Api_Helper::get_api( $api_route );

		// we do not need the block config
		if ( isset( $api['block'] ) ) {
			unset( $api['block'] );
		}

		$api['posttype'] = $api_config['api_settings'];

		// get raw response
		$response_raw = Api_Helper::remote_get( $api );
		// error_log( $response_raw );

		// convert response
		$response = Api_Helper::convert_response( $response_raw, $api, 'posttype' );
		// debug( $response );

		$posts = $this->get_posts_from_response( $response );

		/**
		 * Filter posts before import.
		 * 
		 * @filter  greyd_api_import_posts_{api-slug/enpoint-name}
		 * @example greyd_api_import_posts_myapi/fetch-items
		 * 
		 * @param WP_POST[] $posts     All post objects
		 * @param array     $api       API config
		 * @param object    $automator Automator object
		 * 
		 * @return WP_POST[] $posts
		 */
		$posts = apply_filters( 'greyd_api_import_posts_' . $api['slug'], $posts, $api_config, $automator );

		// get already existing posts of posttype
		$old_post_ids = get_posts(
			array_merge(
				array(
					'post_type'      => $api_config['posttype_slug'],
					'post_status'    => array( 'publish' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'suppress_filters' => false,
					
					// only with is_imported_post meta
					'meta_query' => array(
						array(
							'key'     => 'is_imported_post',
							'value'   => true,
							'compare' => '=',
						),
					),
				),
				// if first post has lang, get only posts with lang
				isset( $posts[0]->lang ) ? array( 'lang' => $posts[0]->lang ) : array()
			)
		);

		foreach ( $posts as $wp_post ) {

			$wp_post = (array) $wp_post;

			$automator->log( $wp_post );

			$post_id = $this->import_post( $wp_post, $api_config );
		
			if ( $post_id )  {
				$automator->log( "Updated post '{$wp_post['post_name']}' with ID ".$post_id );

				// remove post from old posts
				$old_post_ids = array_diff( $old_post_ids, array( $post_id ) );

			} else {
				$automator->log( "Failed to import post '{$wp_post['post_name']}'" );
			}

			// debug( (array) $wp_post );
			// debug( $post_id, true );
			// wp_die();
		}

		// move old posts to 'draft'
		foreach ( $old_post_ids as $old_post_id ) {
			
			$old_post = get_post( $old_post_id );

			$old_post->post_status = 'draft';
			$result = wp_update_post( $old_post );

			if ( $result ) {
				$automator->log( "Moved post '{$old_post->post_name}' with ID {$old_post->ID} to 'draft'" );
			} else {
				$automator->log( "Failed to move post '{$old_post->post_name}' with ID {$old_post->ID} to 'draft'" );
			}
		}
	}

	/**
	 * Get posts from response.
	 * 
	 * @param array $response
	 * 
	 * @return array $posts
	 */
	public function get_posts_from_response( $response ) {

		$posts = array();

		if (
			is_array( $response )
			&& isset( $response['status'] )
			&& $response['status'] === 'success'
		) {
			if ( strpos( $response['type'], 'application/json' ) === 0 ) {
				$posts = json_decode( $response['body'] );
			} elseif ( strpos( $response['type'], 'application/xml' ) === 0 || strpos( $response['type'], 'text/xml' ) === 0 ) {
				$xml   = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
				$posts = json_decode( json_encode( $xml ), true );
			} else {
				$posts = $response['body'];
			}
		}

		if ( ! is_array( $posts ) ) {
			$posts = array();
		}

		return $posts;
	}

	/**
	 * Import post into posttype.
	 * 
	 * @param array $wp_post    WP post
	 * @param array $api_config API config
	 * 
	 * @return int|bool $result Post ID or false
	 */
	public function import_post( $wp_post, $api_config ) {

		if ( is_object( $wp_post ) ) {
			$wp_post = (array) $wp_post;
		}

		if ( ! isset( $wp_post['post_name'] ) ) {
			return false;
		}

		$wp_post['post_type'] = $api_config['posttype_slug'];
		if ( ! isset( $wp_post['post_status'] ) ) {
			$wp_post['post_status'] = 'publish';
		}

		$args = array(
			'name'        => sanitize_title( $wp_post['post_name'] ),
			'post_type'   => $wp_post['post_type'],
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => 1,
			'suppress_filters' => false,
		);

		if ( isset( $wp_post['lang'] ) ) {
			
			$args['lang'] = $wp_post['lang'];

			// polylang support
			if ( function_exists('pll_default_language') ) {

				// if not default language, add language to post name
				if ( $wp_post['lang'] != pll_default_language() ) {
					$wp_post['post_name'] = sanitize_title( $wp_post['post_name'] . '-' . $wp_post['lang'] );
					$args['name']         = sanitize_title( $wp_post['post_name'] );
				}
			}
			else {
				global $sitepress;
				if ( $sitepress && method_exists( $sitepress, 'switch_lang' ) ) {
					$sitepress->switch_lang( $args['lang'] );
					unset( $wp_post['lang'] );
				}
			}
		}

		// set is_imported_post meta to true
		if ( ! isset( $wp_post['meta_input'] ) ) {
			$wp_post['meta_input'] = array();
		}
		else if ( ! is_array( $wp_post['meta_input'] ) ) {
			$wp_post['meta_input'] = (array) $wp_post['meta_input'];
		}
		$wp_post['meta_input']['is_imported_post'] = true;

		// check if post exists
		$existing_post = get_posts( $args );
		if ( $existing_post && isset( $existing_post[0] ) ) {
			$wp_post['ID'] = $existing_post[0]->ID;

			if ( isset( $wp_post['meta_input'] ) ) {
				// get existing post meta and merge with new post meta
				$this->update_post_meta_recursive( $wp_post['ID'], $wp_post['meta_input'] );
				unset( $wp_post['meta_input'] );
			}
		}

		$result = self::insert_wp_post( (array) $wp_post, true );
		
		if ( $result )  {
			// error_log( $existing_post ? "Updated post '{$wp_post['post_name']}' with ID ".$result : "Created post '{$wp_post['post_name']}' with ID ".$result );
		} else {
			// error_log( "Failed to import post '{$wp_post['post_name']}'" );
		}

		return $result;
	}

	/**
	 * Reset posts of posttype.
	 * 
	 * @param array $api_config
	 *    @property string api_slug
	 *    @property string posttype_slug
	 *    @property array  api_settings
	 * @param int $last_timestamp
	 * @param object $automator
	 */
	public function reset_posts( $api_config, $last_timestamp, $automator ) {
		// error_log( "Reset '{$api_config['posttype_slug']}' posts of API {$api_config['api_slug']} (timestamp: $last_timestamp)" );

		// get all posts
		$args = array(
			'post_type'      => $api_config['posttype_slug'],
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );

		/**
		 * Filter posts before reset.
		 * 
		 * @filter  greyd_api_reset_posts_{api-slug}
		 * @example greyd_api_reset_posts_myapi
		 * @since 2.14.0
		 * 
		 * @param WP_POST[] $posts     All post objects
		 * @param array     $api       API config
		 * @param object    $automator Automator object
		 * 
		 * @return WP_POST[] $posts
		 */
		$posts = apply_filters( 'greyd_api_reset_posts_' . $api_config['api_slug'], $posts, $api_config, $automator );

		// delete all posts
		foreach ( $posts as $post ) {
			$result = wp_delete_post( $post->ID, true );
			if ( is_wp_error( $result ) ) {
				$automator->log( $result->get_error_message() );
			}
			if ( $result ) {
				$automator->log( "Deleted post '{$post->post_name}' with ID {$post->ID}" );
			}
		}

	}


	/**
	 * =================================================================
	 *                          Helper
	 * =================================================================
	 */

	 public function update_post_meta_recursive( $post_id, $meta_input ) {

		foreach ( $meta_input as $meta_key => $meta_value ) {

			$existing_value = get_post_meta( $post_id, $meta_key, true );
			$new_value      = is_object( $meta_value ) ? (array) $meta_value : $meta_value;

			if ( is_array( $new_value ) && is_array( $existing_value ) ) {
				
				foreach ( $existing_value as $existing_value_key => $existing_value_value ) {
					if ( ! isset( $new_value[ $existing_value_key ] ) ) {
						$new_value[ $existing_value_key ] = $existing_value_value;
					}
				}
			}
			
			update_post_meta( $post_id, $meta_key, $new_value );
		}
	}

	/**
	 * Insert wp post with taxonomies.
	 * 
	 * @param array $wp_post
	 * 
	 * @return int|bool $result
	 */
	public function insert_wp_post( $wp_post ) {

		if ( isset( $wp_post['tax_input'] ) ) {
			$tax_input = $wp_post['tax_input'];
			unset( $wp_post['tax_input'] );
		}

		$post_id = wp_insert_post( (array) $wp_post, true );

		if ( is_wp_error( $post_id ) ) {
			error_log( $post_id->get_error_message() );
			return false;
		}

		if ( isset( $wp_post['lang'] ) ) {
			
			// polylang support
			if ( function_exists('pll_set_post_language') ) {
				pll_set_post_language( $post_id, $wp_post['lang'] );
			}
		}

		// insert taxonomies
		if ( isset( $tax_input ) ) {

			foreach ( (array) $tax_input as $taxonomy => $terms ) {

				$taxonomy = sanitize_key( $taxonomy );

				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				if ( ! is_array( $terms ) ) {
					if ( empty( $terms ) ) {
						continue;
					}
					else if ( is_string( $terms ) ) {
						$terms = explode( ',', $terms );
					}
					else if ( is_object( $terms ) ) {
						$terms = (array) $terms;
					}
					else {
						continue;
					}
				}

				$term_slugs = array();

				// create terms if not exists
				foreach ( $terms as $term ) {

					if ( is_object( $term ) && isset($term->slug) && isset($term->name) ) {
						$term_slug = $term->slug;
						$term = $term->name;
					}
					else if ( !is_string( $term ) || empty( $term ) ) {
						continue;
					}
					else {
						$term_slug = sanitize_title( $term );
					}

					$term_slugs[] = $term_slug;

					if ( isset( $wp_post['lang'] ) ) {

						// polylang support
						if ( function_exists('pll_default_language') ) {
			
							// if not default language, add language to term name
							if ( $wp_post['lang'] != pll_default_language() ) {
								$term_slug .= '-' . $wp_post['lang'];
							}
						}
					}

					$new_term      = null;
					$existing_term = term_exists( $term_slug, $taxonomy );

					if ( ! $existing_term ) {
						$res = wp_insert_term(
							$term,
							$taxonomy,
							array(
								'slug' => $term_slug,
							)
						);
						if ( is_wp_error( $res ) ) {
							error_log( $res->get_error_message() );
						}
					}
					else {
						$res = $existing_term;
					}
					
					if ( isset( $wp_post['lang'] ) ) {

						// polylang support
						if ( function_exists('pll_set_term_language') && is_array( $res ) ) {
							pll_set_term_language( $res['term_id'], $wp_post['lang'] );
						}
					}
				}

				// set term realtionships to post
				$res = wp_set_object_terms( $post_id, $term_slugs, $taxonomy, false );
				if ( is_wp_error( $res ) ) {
					error_log( $res->get_error_message() );
				}
			}
		}

		return $post_id;
	}

	/**
	 * Get all apis
	 *
	 * @return array $apis
	 */
	public static function get_all_apis() {

		$settings = Admin::get_settings( 'api' );

		if (
			! isset( $settings['apis'] )
			|| ! is_array( $settings['apis'] )
			|| count( $settings['apis'] ) === 0
		) {
			return array();
		}

		return $settings['apis'];
	}

	/**
	 * Get all api posttypes
	 *
	 * @return array $all_api_posttypes
	 */
	public static function get_all_api_posttypes() {

		$all_api_posttypes = array();

		$apis = self::get_all_apis();

		foreach ( $apis as $api_setup ) {

			$api_slug  = isset( $api_setup['slug'] ) ? $api_setup['slug'] : null;
			$posttypes = isset( $api_setup['posttypes'] ) ? $api_setup['posttypes'] : array();

			if ( empty( $api_slug ) || empty( $posttypes ) ) {
				continue;
			}

			foreach ( $posttypes as $name => $posttype ) {

				$posttype_settings = isset( $posttype['posttype_settings'] ) ? (array) $posttype['posttype_settings'] : array();
				$posttype_settings = self::get_posttype_settings( $posttype_settings );

				$api_settings = isset( $posttype['api_settings'] ) ? (array) $posttype['api_settings'] : array();
				$api_settings = self::get_api_settings( $api_settings );

				$posttypes[ $name ] = array(
					'posttype_settings' => $posttype_settings,
					'api_settings'      => $api_settings,
				);
			}

			$all_api_posttypes[ $api_slug ] = $posttypes;
		}

		return $all_api_posttypes;
	}

	/**
	 * Get posttype settings
	 *
	 * @param array $posttype_settings
	 *
	 * @return array $posttype_settings
	 */
	public static function get_posttype_settings( array $posttype_settings ) {

		if ( ! isset( $posttype_settings['slug'] ) || ! isset( $posttype_settings['title'] ) ) {
			return array();
		}

		$posttype_settings['slug'] = sanitize_title( $posttype_settings['slug'] );

		$settings = wp_parse_args(
			$posttype_settings,
			array(
				'slug'              => $posttype_settings['slug'],
				'title'             => $posttype_settings['title'],
				'singular'          => $posttype_settings['title'],
				'plural'            => $posttype_settings['title'],
				'icon'              => 'post',
				'position'          => 54,
				'categories'        => false,
				'tags'              => false,
				'is_taxonomy'       => false,
				'supports'          => array(),
				'arguments'         => array(),
				'fields'            => array(),
				'custom_taxonomies' => array(),
				'capabilities'      => array(),
			)
		);

		return array(
			'post_type'   => 'tp_posttypes',
			'post_name'   => $settings['slug'],
			'post_title'  => $settings['title'],
			'post_status' => 'publish',
			'meta_input'  => array(
				'posttype_settings' => array_merge(
					$settings,
					array(
						'slug'              => (string) $settings['slug'],
						'title'             => (string) $settings['title'],
						'icon'              => (string) $settings['icon'],
						'position'          => (int) $settings['position'],
						'is_taxonomy'       => (bool) $settings['is_taxonomy'],
						'categories'        => ! empty( $settings['categories'] ) ? 'categories' : false,
						'tags'              => ! empty( $settings['tags'] ) ? 'tags' : false,
						'supports'          => (array) $settings['supports'],
						'custom_taxonomies' => (array) $settings['custom_taxonomies'],
						'arguments'         => (array) $settings['arguments'],
						'capabilities'      => (array) $settings['capabilities'],
						'fields'            => (array) $settings['fields'],
					)
				),
			),
		);
	}

	/**
	 * Get api settings
	 *
	 * @param array $api_settings
	 *
	 * @return array $api_settings
	 */
	public static function get_api_settings( array $api_settings ) {

		// $settings = wp_parse_args( $api_settings, array(
		// 'route' => '',
		// 'title' => $api_settings[ 'title' ],
		// 'singular' => $api_settings[ 'title' ],
		// 'plural' => $api_settings[ 'title' ],
		// 'icon' => 'post',
		// 'position' => 54,
		// 'categories' => false,
		// 'tags' => false,
		// 'is_taxonomy' => false,
		// 'supports' => array(),
		// 'arguments' => array(),
		// 'fields' => array(),
		// 'custom_taxonomies' => array(),
		// 'capabilities' => array(),
		// ) );

		return $api_settings;
	}
}
