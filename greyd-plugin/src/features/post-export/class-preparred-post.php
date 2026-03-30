<?php
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to implement the Preparred_Post object.
 * 
 * @since 2.17.0
 *
 * This class attaches all post meta options, taxonomy terms and
 * other post properties to a customized WP_Post object. It also gets
 * all nested templates, forms, images etc. inside the post content,
 * dynamic meta or set as thumbnail and prepares them for later
 * replacement.
 *
 * @see     WP_Post
 * @source  /wp-includes/class-wp-post.php
 */
#[AllowDynamicProperties]
class Preparred_Post {

	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $ID;

	/**
	 * The post's slug.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_name = '';

	/**
	 * The post's title.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_title = '';

	/**
	 * The post's type, like post or page.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * The post's local publication time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * Post meta info of the post.
	 * 
	 * @var array
	 */
	public $meta = array();

	/**
	 * Assigned terms of the post.
	 * 
	 * @var array
	 */
	public $terms = array();

	/**
	 * Nested posts, keyed by ID.
	 * 
	 * @var array[]
	 * Nested post arrays keyed by post_id:
	 *   {{post_id}} => array(
	 *     @property int    ID         ID of the nested post.
	 *     @property string post_name  Post name (slug) of the nested post.
	 *     @property string post_type  Post type of the nested post.
	 *     @property string front_url  Frontend URL of the nested post.
	 *     @property string file_path  (only for attachments)
	 *   )
	 */
	public $nested = array();

	/**
	 * Nested terms of the post.
	 * 
	 * @var array
	 */
	public $nested_terms = array();

	/**
	 * Attached media file, only when the post is an attachment.
	 * 
	 * @var array
	 *     @property int    name  Post name (slug) of the media file.
	 *     @property string path  DIR path of the media file.
	 *     @property string url   URL to the media file.
	 */
	public $media = array();

	/**
	 * Language information of the post.
	 * 
	 * @var array
	 *     @property string code       The post's language code (eg. 'en')
	 *     @property string tool       The plugin used to setup the translation.
	 *     @property array  post_ids   All translated post IDs keyed by language code.
	 *     @property array  args       Additional arguments (depends on the tool)
	 */
	public $language = array();

	/**
	 * The arguments used to export the post.
	 * @since new
	 * 
	 * This takes precedence over the default arguments, passed to
	 * the Class __construct() function: @param $export_arguments.
	 * 
	 * @var array
	 *    @property bool  append_nested   Append nested posts to the export.
	 *    @property bool  whole_posttype  Export the whole post type.
	 *    @property bool  all_terms       Export all terms of the post.
	 *    @property bool  resolve_menus   Resolve navigation links to custom links.
	 *    @property bool  translations    Include translations of the post.
	 *    @property array query_args      Additional query arguments.
	 */
	public $export_arguments = array();

	/**
	 * Conflict action: What to do if a conflicting post already exists.
	 * @since new
	 * 
	 * A conflicting post is a post with the same post_name and post_type.
	 * 
	 * @var string 'keep|replace|skip'
	 *    @default 'keep'    Keep the existing post and insert the new one with a new ID.
	 *    @value   'replace' Replace the existing post with the new one.
	 *    @value   'skip'    Skip the post if a conflicting post already exists.
	 * 
	 */
	public $conflict_action = 'keep';

	/**
	 * Import action: What to do with the post on/after import.
	 * @since new
	 * 
	 * @var string 'insert|draft|trash|delete'
	 *    @default 'update'  Insert or update the post if it already exists.
	 *    @value   'draft'   Set the post to draft.
	 *    @value   'trash'   Move the post to trash.
	 *    @value   'delete'  Delete the post permanently.
	 */
	public $import_action = 'insert';


	/**
	 * =================================================================
	 *                          Functions
	 * =================================================================
	 */

	/**
	 * Constructor.
	 *
	 * @param WP_Post|object|array|int $post  Post object or Post ID.
	 * @param array $export_arguments  Additional arguments.
	 */
	public function __construct( $post, $export_arguments = array() ) {

		// support array as input
		if ( is_array( $post ) ) {
			$post = (object) $post;
		}

		// support post_id as input
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		// not a post object
		if ( ! $post || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return;
		}

		do_action( 'greyd_post_export_log', "\r\n\r\n|\r\n|  PREPARE POST {$post->ID} '{$post->post_title}'\r\n|" );

		// set base object vars from WP_Post
		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}

		/**
		 * Parse arguments.
		 * The export_arguments defined within the post take precedence
		 * over the default arguments passed to the constructor.
		 * @property array $export_arguments
		 */
		$this->export_arguments = wp_parse_args( $this->export_arguments, $export_arguments );

		/**
		 * Prepare the custom post object properties.
		 */
		$this->prepare_nested_posts();
		$this->prepare_nested_terms();
		$this->prepare_strings();
		$this->prepare_meta();
		$this->prepare_terms();
		$this->prepare_media();
		$this->prepare_language();
		$this->prepare_menus();
	}

	/**
	 * Prepare nested posts in content.
	 */
	public function prepare_nested_posts() {

		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare nested posts.' );

		$nested_posts  = array();

		if ( empty( $this->post_content ) ) {
			do_action( 'greyd_post_export_log', '=> post content is empty' );
			return;
		}

		// get regex patterns
		$replace_id_patterns = (array) get_nested_post_patterns( $this->ID, $this );

		foreach ( $replace_id_patterns as $key => $pattern ) {

			// doing it wrong
			if (
				! isset( $pattern['search'] ) ||
				! is_array( $pattern['search'] ) ||
				count( $pattern['search'] ) < 2 ||
				! isset( $pattern['replace'] ) ||
				! is_array( $pattern['replace'] )
			) {
				continue;
			}

			$match_regex = '/' . implode( '([\da-z\-\_]+?)', $pattern['search'] ) . '/';
			$regex_group = isset( $pattern['group'] ) ? (int) $pattern['group'] : 2;
			// do_action( "greyd_post_export_log", "  - test regex: ".esc_attr($match_regex) );

			// search for all occurrences
			preg_match_all( $match_regex, $this->post_content, $matches );
			$found_posts = isset( $matches[ $regex_group ] ) ? $matches[ $regex_group ] : null;
			if ( ! empty( $found_posts ) ) {

				do_action( 'greyd_post_export_log', "\r\n" . "  Replace '" . $key . "':" );
				foreach ( $found_posts as $name_or_id ) {

					$nested_post = null;
					$nested_id = $name_or_id;

					// WP_Post->ID
					if ( is_numeric( $name_or_id ) ) {
						$nested_post = get_post( $name_or_id );
					}
					// WP_Post->post_name
					else {
						if ( isset( $pattern['post_type'] ) ) {
							$args = (object) array(
								'post_name' => $name_or_id,
								'post_type' => $pattern['post_type'],
							);
						} else {
							$args = $name_or_id;
						}
						// get post
						$nested_post = Post_Export_Helper::get_post_by_name_and_type( $args );
						if ( $nested_post ) {
							$nested_id = $nested_post->ID;
						}
					}

					if ( ! $nested_post ) {
						do_action( 'greyd_post_export_log', "  - post with id or name '$name_or_id' could not be found." );
						continue;
					}

					$search_regex   = '/' . implode( $nested_id, $pattern['search'] ) . '/';
					$replace_string = implode( '{{' . $nested_id . '}}', $pattern['replace'] );

					// replace in post_content
					$this->post_content = preg_replace( $search_regex, $replace_string, $this->post_content );

					// collect in $nested_posts
					$nested_posts[$nested_id] = $nested_post;
				}
			}
		}

		/**
		 * advancedFilter
		 * @since 2.8.0
		 */
		preg_match_all( '/\"advancedFilter\":(\[.*\])/', $this->post_content, $matches );
		if ( $matches ) {
			// debug("advancedFilter");
			foreach ( $matches[1] as $match ) {
				$res = json_decode($match);
				if ( $res ) {
					$changed = false;
					foreach ( $res as $i => $filter ) {
						if ( $filter->name == "include" && !empty($filter->include) ) {
							foreach ( $filter->include as $j => $post_id ) {
								if ( is_numeric($post_id) ) {
									$res[$i]->include[$j] = '{{'.$post_id.'}}';
									$nested_posts[$post_id] = get_post( $post_id );
									$changed = true;
								}
							}
						}
					}
					if ( $changed) {
						$encoded = str_replace( array( '"{{', '}}"' ), array( '{{', '}}' ), json_encode($res) );
						$this->post_content = str_replace( $match, $encoded, $this->post_content );
					}
				}
			}
		}

		// now collect the posts in $this->nested
		foreach ( $nested_posts as $nested_id => $nested_post ) {

			if ( isset( $this->nested[ $nested_id ] ) ) {
				continue;
			}
			
			if ( ! $nested_post ) {
				$this->nested[ $nested_id ] = null;
			}

			$this->nested[ $nested_id ] = array(
				'ID'        => $nested_id,
				'post_name' => $nested_post->post_name,
				'post_type' => $nested_post->post_type,
				'front_url' => $nested_post->post_type === 'attachment' ? wp_get_attachment_url( $nested_post->ID ) : get_permalink( $nested_id ),
			);
			if ( $nested_post->post_type === 'attachment' ) {
				// $this->nested[ $nested_id ]['file_path'] = get_attached_file( $nested_post->ID );
				// remove '-scaled' suffix (https://wp-kama.com/2284/the-scaled-suffix-for-images)
				$file_url = str_replace( '-scaled.', '.', wp_get_attachment_url( $nested_id ) );
				$file_path = str_replace( '-scaled.', '.', get_attached_file( $nested_id ) );
				$this->nested[ $nested_id ] = array(
					'ID'        => $nested_id,
					'post_name' => $nested_post->post_name,
					'post_type' => $nested_post->post_type,
					'front_url' => $file_url,
					'file_path' => $file_path,
				);

			} else {
				$this->nested[ $nested_id ] = array(
					'ID'        => $nested_id,
					'post_name' => $nested_post->post_name,
					'post_type' => $nested_post->post_type,
					'front_url' => get_permalink( $nested_id ),
				);
			
				// also replace the front url inside the post content
				$this->post_content = str_replace( $this->nested[ $nested_id ]['front_url'], '{{' . $nested_id . '-front-url}}', $this->post_content );
			}

			do_action(
				'greyd_post_export_log',
				sprintf(
					"  - nested post '%s' attached for export.\r\n     * ID: %s\r\n     * TYPE: %s\r\n     * URL: %s",
					$nested_post->post_name,
					$nested_id,
					$nested_post->post_type,
					$this->nested[ $nested_id ]['front_url']
				)
			);
		}

		do_action( 'greyd_post_export_log', '=> nested elements were preparred' );
	}

	/**
	 * Prepare nested terms inside the post content.
	 */
	public function prepare_nested_terms() {
		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare nested terms.' );

		$nested_term_ids = array();

		if ( empty( $this->post_content ) ) {
			do_action( 'greyd_post_export_log', '=> post content is empty' );
			return;
		}

		// register temporary taxonomies to prevent errors
		Post_Export_Helper::get_dynamic_taxonomies();

		// get patterns
		$replace_id_patterns = (array) get_nested_term_patterns( $this->ID, $this );

		foreach ( $replace_id_patterns as $key => $pattern ) {

			// doing it wrong
			if (
				! isset( $pattern['search'] ) ||
				! is_array( $pattern['search'] ) ||
				count( $pattern['search'] ) < 2 ||
				! isset( $pattern['replace'] ) ||
				! is_array( $pattern['replace'] )
			) {
				continue;
			}

			$match_regex = '/' . implode( '([\da-z\-\_]+?)', $pattern['search'] ) . '/';
			$regex_group = isset( $pattern['group'] ) ? (int) $pattern['group'] : 2;
			// do_action( "greyd_post_export_log", "  - test regex: ".esc_attr($match_regex) );

			// search for all occurrences
			preg_match_all( $match_regex, $this->post_content, $matches );
			$found_terms = isset( $matches[ $regex_group ] ) ? $matches[ $regex_group ] : null;
			if ( ! empty( $found_terms ) ) {

				do_action( 'greyd_post_export_log', '  - replace ' . $key . ':' );
				foreach ( $found_terms as $term_id ) {

					// default value for term_ids
					if ( $term_id == 0 || $term_id == -1 ) {
						continue;
					}

					$search_regex   = '/' . implode( $term_id, $pattern['search'] ) . '/';
					$replace_string = implode( '{{t_' . $term_id . '}}', $pattern['replace'] );

					// replace in $this->post_content
					$this->post_content = preg_replace( $search_regex, $replace_string, $this->post_content );

					// collect in $nested_term_ids
					$nested_term_ids[] = $term_id;
				}
			}
		}

		/**
		 * taxQuery and advancedFilter
		 * @since 2.8.0
		 */
		preg_match_all( '/\"taxQuery\":(\{.*?\})/', $this->post_content, $matches );
		if ( $matches ) {
			// debug("taxQuery");
			foreach ( $matches[1] as $match ) {
				$res = json_decode($match);
				if ( $res ) {
					$changed = false;
					foreach ( $res as $tax => $terms ) {
						foreach ( $terms as $i => $term_id ) {
							$res->{$tax}[$i] = '{{t_'.$term_id.'}}';
							$nested_term_ids[] = $term_id;
							$changed = true;
						}
					}
					if ( $changed) {
						$encoded = str_replace( array( '"{{', '}}"' ), array( '{{', '}}' ), json_encode($res) );
						$this->post_content = str_replace( $match, $encoded, $this->post_content );
					}
				}
			}
		}
		preg_match_all( '/\"advancedFilter\":(\[.*\])/', $this->post_content, $matches );
		if ( $matches ) {
			// debug("advancedFilter");
			foreach ( $matches[1] as $match ) {
				$res = json_decode($match);
				if ( $res ) {
					$changed = false;
					foreach ( $res as $i => $filter ) {
						if ( $filter->name == "taxonomy" && !empty($filter->terms) ) {
							foreach ( $filter->terms as $j => $term_id ) {
								if ( intval($term_id) == $term_id ) {
									$res[$i]->terms[$j] = '{{t_'.$term_id.'}}';
									$nested_term_ids[] = $term_id;
									$changed = true;
								}
							}
						}
					}
					if ( $changed) {
						$encoded = str_replace( array( '"{{', '}}"' ), array( '{{', '}}' ), json_encode($res) );
						$this->post_content = str_replace( $match, $encoded, $this->post_content );
					}
				}
			}
		}

		// collect in $this->nested_terms
		foreach ( array_unique( $nested_term_ids ) as $term_id ) {
			if ( isset( $this->nested_terms[ $term_id ] ) ) {
				continue;
			}

			$term_object = get_term( $term_id );
			if ( ! $term_object || is_wp_error( $term_object ) ) {
				do_action( 'greyd_post_export_log', "  - term with id '$term_id' could not be found." );
				$this->nested_terms[ $term_id ] = null;
			} else {
				do_action( 'greyd_post_export_log', "  - term with id '$term_id' found.", $term_object );
				$this->nested_terms[ $term_id ] = $term_object;
			}
		}

		do_action( 'greyd_post_export_log', '=> nested terms were preparred' );
	}

	/**
	 * Replace strings for export
	 */
	public function prepare_strings() {
		$this->post_content = Post_Export_Helper::replace_dynamic_strings( $this->post_content, $this->ID );
	}

	/**
	 * Prepare meta for consumption
	 */
	public function prepare_meta() {
		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare post meta.' );

		$meta = get_post_meta( $this->ID );

		// Transfer all meta
		foreach ( $meta as $meta_key => $meta_array ) {
			foreach ( $meta_array as $meta_value ) {

				// don't prepare blacklisted meta
				if ( in_array( $meta_key, Post_Export_Helper::blacklisted_meta(), true ) ) {
					continue;
				}
				// skip certain meta keys
				elseif ( Post_Export_Helper::maybe_skip_meta_option( $meta_key, $meta_value ) ) {
					continue;
				}

				$meta_value = maybe_unserialize( $meta_value );

				/**
				 * Filter to modify specific post meta values before export.
				 * 
				 * This filter allows developers to customize individual post meta values
				 * during export. The filter name is dynamic based on the meta key,
				 * allowing for targeted modifications of specific meta fields.
				 * 
				 * @filter greyd_export_post_meta-{{meta_key}}
				 * 
				 * @param mixed $meta_value      The meta value to be exported.
				 * @param int   $post_id        The ID of the post being exported.
				 * @param array $export_arguments The export arguments passed to the constructor.
				 * 
				 * @return mixed                The modified meta value for export.
				 */
				$meta_value = apply_filters( 'greyd_export_post_meta-' . $meta_key, $meta_value, $this->ID, $this->export_arguments );

				$this->meta[ $meta_key ][] = $meta_value;
			}
		}

		do_action( 'greyd_post_export_log', '=> post meta prepared' );
	}

	/**
	 * Prepare terms associated with the post.
	 */
	public function prepare_terms() {
		
		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare taxonomy terms.' );

		$posttype_meta = (array) get_post_meta( $this->ID, 'posttype_settings', true );
		$is_taxonomy   = $posttype_meta && isset($posttype_meta['is_taxonomy']) && $posttype_meta['is_taxonomy'];
		$tax_slug      = $posttype_meta && isset($posttype_meta['slug']) ? $posttype_meta['slug'] : '';

		/**
		 * If the post is a dynamic taxonomy, only prepare the associated terms.
		 */
		if ( $is_taxonomy ) {
			
			// get all terms of the taxonomy
			$terms = get_terms( array(
				'taxonomy' => $tax_slug,
				'hide_empty' => false,
			) );

			foreach ( $terms as $term ) {
				if (isset($term->term_id)) {
					$this->terms[$term->term_id] = $term;
				}
			}

			return;
		}

		/**
		 * All other post types might have terms assigned to them.
		 */
		$taxonomies = get_object_taxonomies( $this );

		// if we haven't found any taxonomies we create them temporarily.
		if ( empty( $taxonomies ) ) {
			do_action( 'greyd_post_export_log', '  - object taxonomies are empty' );
			$taxonomies = Post_Export_Helper::get_dynamic_taxonomies( $this->post_type );
		}

		if ( empty( $taxonomies ) ) {
			do_action( 'greyd_post_export_log', '=> no taxonomy terms found' );
			return array();
		}

		foreach ( $taxonomies as $taxonomy ) {

			/**
			 * Retrieve post terms directly from the database.
			 *
			 * @since 1.2.7
			 *
			 * WPML attaches a lot of filters to the function wp_get_object_terms(). This results
			 * in terms of the wrong language beeing attached to a post export. This function performs
			 * way more consistent in all tests. Therefore it completely replaced it in this class.
			 *
			 * @deprecated since 1.2.7: $terms = wp_get_object_terms( $this->ID, $taxonomy );
			 */
			$terms = \Greyd\Helper::get_post_taxonomy_terms( $this->ID, $taxonomy );

			if ( empty( $terms ) ) {
				$this->terms[ $taxonomy ] = array();
				do_action( 'greyd_post_export_log', "  - no terms found for taxonomy '$taxonomy'." );
			}
			else {
				$count = count( $terms );
				do_action(
					'greyd_post_export_log',
					"  - {$count} " . ( $count > 1 ? 'terms' : 'term' ) . " of taxonomy '$taxonomy' prepared:\r\n    - " . implode(
						"\r\n    - ",
						array_map(
							function( $term ) {
								return "{$term->name} (#{$term->term_id})";
							},
							$terms
						)
					)
				);
				/**
				 * Nest parent terms.
				 * @since 1.2.8
				 */
				$ids = array_map( function( $term ) { return $term->term_id; }, $terms );
				foreach ( $terms as $i => $term ) {
					if ( $term->name == get_stylesheet() ) $term->name = '{{theme}}';
					if ( $term->slug == get_stylesheet() ) $term->slug = '{{theme}}';
					$this->terms[ $taxonomy ][$i] = $this->get_term_parents( $term, $ids );
				}
			}
		}

		do_action( 'greyd_post_export_log', '=> all taxonomy terms prepared' );
	}

	/**
	 * Get all parent terms by replacing the ID with the actual term object recursively.
	 * 
	 * @param WP_Term $term     The term object.
	 * @param array $prepared   All already prepared term IDs.
	 * 
	 * @return WP_Term $term    The term object with nested parent term object(s)
	 */
	public function get_term_parents( $term, $prepared ) {

		if ( $term->parent != 0 && !in_array( $term->parent, $prepared ) ) {
			do_action( 'greyd_post_export_log', "    - parent of '".$term->term_id."' found: '".$term->parent."'" );
			$parent = get_term( $term->parent );
			// do_action( 'greyd_post_export_log', json_encode( $parent ) );
			$term->parent = $this->get_term_parents( $parent, $prepared );
		}

		return $term;
	}

	/**
	 * Format media items for export
	 */
	public function prepare_media() {
		
		if ( $file_path = get_attached_file( $this->ID ) ) {
			$file_path = str_replace( '-scaled.', '.', $file_path );
			$file_url    = wp_get_attachment_url( $this->ID );
			$file_url = str_replace( '-scaled.', '.', $file_url );
			$file_name   = wp_basename( $file_path );
			$this->media = array(
				'name' => $file_name,
				'path' => $file_path,
				'url'  => $file_url,
			);
			do_action( 'greyd_post_export_log', "\r\n" . sprintf( "The file '%s' was added to the post.", $file_name ) );
		}
	}

	/**
	 * Get all necessary language information.
	 */
	public function prepare_language() {

		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare post language info.' );

		$this->language = array(
			'code'     => null,
			'tool'     => Post_Export_Helper::get_translation_tool(), // null,
			'post_ids' => array(),
			'args'     => array(),
		);

		if ( $this->language['tool'] ) {

			// get language infos of this post
			$language_details = Post_Export_Helper::get_post_language_info( $this );

			if ( $language_details && isset( $language_details['language_code'] ) ) {
				$this->language['code'] = $language_details['language_code'];
				$this->language['args'] = $language_details;
				do_action( 'greyd_post_export_log', "  - post has language '{$this->language['code']}'" );
			}

			// prepare post id's if translations are included
			if ( $this->export_arguments['translations'] ) {
				$this->language['post_ids'] = Post_Export_Helper::get_translated_post_ids( $this );
				if ( ! empty( $this->language['post_ids'] ) ) {
					do_action( 'greyd_post_export_log', '  - translations of this post prepared: ' . implode( ', ', $this->language['post_ids'] ) );
				}
			}
		}

		do_action( 'greyd_post_export_log', '=> post language info prepared' );
	}


	/**
	 * Prepare menus by converting navigation links into custom links:
	 * 
	 * From:
	 * <!-- wp:navigation-link {"label":"Sticky Navbar","type":"page","id":548,"url":"{{site_url}}/sticky-navbar/","kind":"post-type"} /-->
	 * 
	 * To:
	 * <!-- wp:navigation-link {"label":"Sticky Navbar","url":"{{site_url}}/sticky-navbar/","kind":"custom"} /-->
	 */
	public function prepare_menus() {

		if (
			! isset( $this->export_arguments['resolve_menus'] ) ||
			! $this->export_arguments['resolve_menus']
		) {
			return;
		}

		do_action( 'greyd_post_export_log', "\r\n" . 'Prepare nested menus.' );

		$subject = $this->post_content;

		// return if subject doesn't contain any menus
		if ( strpos( $subject, 'wp:navigation-link' ) === false ) {
			do_action( 'greyd_post_export_log', '=> no menus found' );
			return $subject;
		}
		
		// loop through all navigation links
		$subject = preg_replace_callback( '/<!-- wp:navigation-link (.*?) \/-->/', function( $matches ) {

			// get the navigation link attributes
			$attributes = json_decode( $matches[1], true );

			// if already a custom link, return the original string
			if ( ! isset( $attributes['kind'] ) || $attributes['kind'] === 'custom' ) {
				return $matches[0];
			}

			// change kind into custom
			$attributes['kind'] = 'custom';

			// remove type & id
			unset( $attributes['type'] );
			unset( $attributes['id'] );

			// return the new navigation link
			return '<!-- wp:navigation-link ' . json_encode( $attributes ) . ' /-->';

		}, $subject );

		do_action( 'greyd_post_export_log', '=> nested menus were resolved' );

		/**
		 * Filter to modify the post content after resolving navigation menus.
		 * 
		 * This filter allows developers to customize post content after navigation
		 * links have been converted to static links during export. It's useful for
		 * applying additional content modifications or custom formatting.
		 * 
		 * @filter greyd_post_export_resolve_menus
		 * 
		 * @param string $subject  The post content after menu resolution.
		 * @param int    $post_id  The ID of the post being exported.
		 * @param object $post     The Preparred_Post object.
		 * 
		 * @return string          The modified post content for export.
		 */
		$this->post_content = apply_filters( 'greyd_post_export_resolve_menus', $subject, $this->ID, $this );
	}


	/**
	 * =================================================================
	 *                          Additional WP_Post default args
	 * =================================================================
	 */

	/**
	 * The post's content.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_content = '';

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $guid = '';

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_author = 0;

	/**
	 * The post's excerpt.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * The post's status.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Whether comments are allowed.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_password = '';

	/**
	 * URLs queued to be pinged.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * URLs that have been pinged.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $pinged = '';

	/**
	 * The post's local modified time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * A field used for ordering posts.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * An attachment's mime type.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $comment_count = 0;

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $filter;
}