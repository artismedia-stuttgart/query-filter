<?php
/**
 * Export Post Controller
 *
 * This file enables advanced post exports inside the GREYD.SUITE.
 * Posts of all supported post types can be exported via the WordPress
 * backend (edit.php) and later be imported to any GREYD.SUITE site.
 *
 * The export contains a JSON file that holds all post data as
 * Preparred_Post objects. The export also contains all media files
 * that are attached to the posts. Structure of the export:
 * - posts.json
 * - media/
 *   - media-file.jpg
 *
 * @since 0.8.4
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Post_Export();
class Post_Export {

	/**
	 * Holds all Preparred_Post objects for export.
	 *
	 * @var Preparred_Post[]
	 */
	public static $posts = array();

	/**
	 * Holds all WP_Media objects for export.
	 *
	 * @var array
	 */
	public static $media = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		// Export
		add_action( 'greyd_ajax_mode_post_export', array( $this, 'handle_export' ) );
		add_filter( 'greyd_export_post_meta-dynamic_meta', array( $this, 'prepare_dynamic_meta' ), 10, 3 );

		// add bulk action callbacks
		add_action( 'admin_init', array( $this, 'add_bulk_action_callbacks' ) );
	}

	/**
	 * Handle the ajax export action
	 *
	 * @action 'greyd_ajax_mode_post_export'
	 *
	 * @param array $data   holds the $_POST['data']
	 */
	public function handle_export( $data ) {

		Post_Export_Helper::enable_logs();

		do_action( 'greyd_post_export_log', "\r\n\r\n" . 'HANDLE EXPORT' . "\r\n", $data );

		$post_id = isset( $data['post_id'] ) ? $data['post_id'] : '';
		$args    = array(
			'append_nested'  => isset( $data['nested'] ) ? true : false,
			'whole_posttype' => isset( $data['whole_posttype'] ) ? true : false,
			'all_terms'      => isset( $data['all_terms'] ) ? true : false,
			'resolve_menus'  => isset( $data['resolve_menus'] ) ? true : false,
			'translations'   => isset( $data['translations'] ) ? true : false,
		);
		if ( ! empty( $post_id ) ) {

			// export post
			$post = self::export_post( $post_id, $args );

			/**
			 * Create the export ZIP-archive.
			 * If posts & media are null, write_export_file() uses the class vars.
			 */
			$posts    = $media = $args['append_nested'] ? null : array();
			$filename = self::write_filename( $post, $args );
			$filepath = self::write_export_file( $filename, $posts, $media );

			if ( ! $filepath ) {
				Post_Export_Helper::error( __( "The export file could not be written.", 'greyd_hub' ) );
			}

			Post_Export_Helper::success( Post_Export_Helper::convert_content_dir_to_path( $filepath ) );
		}
		Post_Export_Helper::error( __( "No valid post ID could found.", 'greyd_hub' ) );
	}

	/**
	 * Get post with all its meta, taxonomies, media etc.
	 *
	 * @param int   $post_id
	 * @param array $args       Arguments.
	 *
	 * @return Preparred_Post
	 */
	public static function export_post( $post_id, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'append_nested'  => true,
			'whole_posttype' => false,
			'all_terms'      => false,
			'resolve_menus'  => true,
			'translations'   => false,
			'query_args'   => array(),
		) );

		// reset the class vars
		self::$posts = array();
		self::$media = array();

		// prepare the export based on this post
		$post = self::prepare_post( $post_id, $args );

		// if this is a posttype, enable bulk export
		if (
			class_exists( '\Greyd\Posttypes\Posttype_Helper' )
			&& ( $args['whole_posttype'] || $args['all_terms'] ) 
			&& $post->post_type === 'tp_posttypes'
		) {

			$post_type = \Greyd\Posttypes\Posttype_Helper::get_posttype_slug_from_title( $post->post_title );

			do_action( 'greyd_post_export_log', "\r\n" . "EXPORT MULTIPLE POSTS from posttype '" . $post_type . "'\r\n" );

			$query_args = array(
				'numberposts' => -1,
				'post_type'   => $post_type,
				'fields'      => 'ids',
			);
			if ( ! empty( $args['query_args'] ) ) {
				$query_args = array_merge( $query_args, $args['query_args'] );
			}

			/**
			 * Filter to modify the query arguments before exporting posts from a post type.
			 * 
			 * This filter allows developers to customize the WordPress query arguments used
			 * when exporting multiple posts from a custom post type. It's useful for adding
			 * custom filters, ordering, or limiting the posts that get exported.
			 * 
			 * @filter greyd_export_post_query_args
			 * 
			 * @param array $query_args  The query arguments for get_posts() function.
			 * @param int   $post_id     The post ID of the post type definition being exported.
			 * @param array $args        The export arguments including options like 'whole_posttype'.
			 * 
			 * @return array $query_args Modified query arguments for post export.
			 */
			$query_args = apply_filters( 'greyd_export_post_query_args', $query_args, $post_id, $args );

			$post_ids = get_posts( $query_args );

			if ( $post_ids && is_array( $post_ids ) ) {
				foreach ( $post_ids as $_post_id ) {
					self::prepare_post( $_post_id, $args );
				}
			}
		}

		return $post;
	}

	/**
	 * Export posts with all its meta, taxonomies, media etc.
	 *
	 * @param int[] $post_ids   Array of post IDs.
	 * @param array $args       Arguments.
	 *
	 * @return Preparred_Post[]
	 */
	public static function export_posts( $post_ids, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'append_nested'  => true,
			'whole_posttype' => false,
			'all_terms'      => false,
			'resolve_menus'  => true,
			'translations'   => false,
			'query_args'   => array(),
		) );

		// reset the class vars
		self::$posts = array();
		self::$media = array();

		if ( $post_ids && is_array( $post_ids ) ) {
			foreach ( $post_ids as $_post_id ) {
				self::prepare_post( $_post_id, $args );
			}
		}

		return self::$posts;
	}

	/**
	 * Prepare post for export.
	 *
	 * @param int   $post_id        Post ID.
	 * @param array $args           Arguments.
	 *
	 * @return Preparred_Post|bool  Preparred_Post on success. False on failure.
	 *
	 * This function automatically sets the following class vars.
	 * Use them to export all nested posts at once.
	 *
	 * @var array class::$posts     Array of all preparred post objects.
	 * @var array class::$media     Array of all media files.
	 */
	public static function prepare_post( $post_id, $args = array() ) {

		// return if we're already processed this post
		if ( isset( self::$posts[ $post_id ] ) ) {
			do_action( 'greyd_post_export_log', "\r\n" . "Post '$post_id' already processed" );
			return self::$posts[ $post_id ];
		}

		/**
		 * First we append the post object to the class var. We do this to
		 * kind of 'reserve' the position of the post inside the array.
		 */
		self::$posts[ $post_id ] = $post_id;

		/**
		 * Create a new Preparred_Post object.
		 */
		$post = new Preparred_Post( $post_id, $args );

		/**
		 * Check if prepared post is valid
		 */
		if ( empty($post->ID) ) {
			unset( self::$posts[ $post_id ] );
			do_action( 'greyd_post_export_log', "\r\n" . "Post '$post_id' not found or invalid" );
			return false;
		}

		/**
		 * Now we update the post in the class var.
		 */
		self::$posts[ $post_id ] = $post;

		/**
		 * Let's save the media to the class var.
		 *
		 * We need this, so write_export_file() can access all the files at once.
		 */
		if ( ! empty( $post->media ) ) {
			self::$media[ $post_id ] = $post->media;
		}

		/**
		 * The post thumbnail always has to be included in the export,
		 * because WP references it with an ID, therefore it needs to
		 * be accessable as a post.
		 */
		if ( $thumbnail_id = get_post_thumbnail_id( $post ) ) {
			self::prepare_post( $thumbnail_id, $args );
		}

		/**
		 * Now we loop through all the nested posts (if the option is set).
		 */
		if ( $args['append_nested'] ) {
			foreach ( $post->nested as $nested_id => $nested_name ) {
				self::prepare_post( $nested_id, $args );
			}
		}

		/**
		 * Now we loop through all translations of this post (if the option is set).
		 */
		if (
			$args['translations']
			&& isset($post->language)
			&& isset($post->language['post_ids'])
			&& is_countable($post->language['post_ids'])
			&& count( $post->language['post_ids'] )
		) {
			foreach ( $post->language['post_ids'] as $lang => $translated_post_id ) {
				self::prepare_post( $translated_post_id, $args );
			}
		}

		return $post;
	}

	/**
	 * Filter to modify dynamic meta values before export processing.
	 * 
	 * This filter allows developers to customize how dynamic meta values are processed
	 * during post export, particularly for custom post type fields like files, URLs,
	 * and HTML content. It's useful for modifying meta data structure or adding
	 * custom export logic for specific field types.
	 * 
	 * @filter greyd_export_post_meta-dynamic_meta
	 * 
	 * @param mixed $meta_value  The meta value to be processed and exported.
	 * @param int   $post_id     The post ID being exported.
	 * @param array $args        Export arguments including options like 'append_nested'.
	 * 
	 * @return mixed $meta_value Modified meta value ready for export.
	 */
	public function prepare_dynamic_meta( $meta_value, $post_id, $args = array() ) {
		do_action( 'greyd_post_export_log', '  - prepare dynamic meta for export' );

		if ( ! class_exists( '\Greyd\Posttypes\Posttype_Helper' ) ) {
			do_action( 'greyd_post_export_log', '  - Posttype class does not exist.' );
			return $meta_value;
		}

		if ( is_object( $meta_value ) ) {
			$meta_value = (array) $meta_value;
		}
		if ( ! is_array( $meta_value ) ) {
			do_action( 'greyd_post_export_log', '  - meta value is not an array.' );
			return $meta_value;
		}

		$post     = get_post( $post_id );
		$posttype = \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttype_by_slug( $post->post_type );
		if ( $posttype && isset( $posttype['fields'] ) ) {
			foreach ( (array) $posttype['fields'] as $field ) {
				$current_value = null;
				if ( is_object( $field ) ) {
					$field = (array) $field;
				}

				// media files
				if ( $field['type'] === 'file' ) {
					$current_value = isset( $meta_value[ $field['name'] ] ) ? $meta_value[ $field['name'] ] : null;
					if ( is_numeric( $current_value ) ) {
						do_action( 'greyd_post_export_log', sprintf( "  - file value for '%s' is an ID (%s) and was prepared for export.", $field['name'], $current_value ) );
						if ( isset( $args['append_nested'] ) && $args['append_nested'] ) {
							do_action( 'greyd_post_export_log', "\r\n---------------------" );
							self::prepare_post( $current_value, $args );
							do_action( 'greyd_post_export_log', "\r\n---------------------\r\n" );
						}
						$current_value = '{{' . $current_value . '}}';
					}
				}
				// url or tinymce editor
				elseif ( $field['type'] === 'url' || $field['type'] === 'text_html' ) {
					$current_value = isset( $meta_value[ $field['name'] ] ) ? $meta_value[ $field['name'] ] : '';
					$current_value = self::prepare_strings( $current_value, $post_id, false );
					do_action( 'greyd_post_export_log', sprintf( "  - content of field '%s' was preparred for export", $field['name'] ) );
				}

				if ( $current_value ) {
					$meta_value[ $field['name'] ] = $current_value;
				}
			}
		}
		return $meta_value;
	}

	/**
	 * Add export bulk action callbacks.
	 */
	public function add_bulk_action_callbacks() {
		// usual posttypes
		foreach ( Post_Export_Helper::get_supported_post_types() as $posttype ) {
			add_filter( 'bulk_actions-edit-' . $posttype, array( $this, 'add_export_bulk_action' ) );
			add_filter( 'handle_bulk_actions-edit-' . $posttype, array( $this, 'handle_export_bulk_action' ), 10, 3 );
		}
		// media library
		add_filter( 'bulk_actions-upload', array( $this, 'add_export_bulk_action' ) );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_export_bulk_action' ), 10, 3 );
	}

	/**
	 * Add export to the bulk action dropdown
	 */
	public function add_export_bulk_action( $bulk_actions ) {
		$bulk_actions['greyd_export'] = __( 'Export', 'greyd_hub' );

		if ( !empty( Post_Export_Helper::get_translation_tool() ) ) {
			$bulk_actions['greyd_export_multilanguage'] = __( 'Export including translations', 'greyd_hub' );
		}
		return $bulk_actions;
	}

	/**
	 * Handle export bulk action
	 *
	 * set via 'add_bulk_action_callbacks'
	 *
	 * @param string $sendback The redirect URL.
	 * @param string $doaction The action being taken.
	 * @param array  $items    Array of IDs of posts.
	 */
	public static function handle_export_bulk_action( $sendback, $doaction, $items ) {
		if ( count( $items ) === 0 ) {
			return $sendback;
		}
		if ( $doaction !== 'greyd_export' && $doaction !== 'greyd_export_multilanguage' ) {
			return $sendback;
		}

		$args = array(
			'append_nested'  => true,
			'translations'   => $doaction === 'greyd_export_multilanguage'
		);

		self::export_posts( $items, $args );

		$filename = self::write_filename( $items );
		$filepath = self::write_export_file( $filename );

		if ( $filepath ) {
			$href = Post_Export_Helper::convert_content_dir_to_path( $filepath );
			// $sendback = add_query_arg( 'download', $href, $sendback );
			$sendback = $href;
		} else {
			// set transient to display admin notice
			set_transient( 'greyd_transient_notice', 'error::' .  __( "The export file could not be written.", 'greyd_hub' ) );
		}

		return $sendback;
	}

	/**
	 * Write export as a .zip archive
	 *
	 * @param string $filename  Name of the final archive.
	 * @param array  $posts     Content of posts.json inside archive.
	 *                          Defaults to class var $posts.
	 * @param array  $media     Media files.
	 *                          Defaults to class var $media.
	 *
	 * @return mixed $path      Path to the archive. False on failure.
	 */
	public static function write_export_file( $filename, $posts = null, $media = null ) {

		do_action( 'greyd_post_export_log', "\r\n" . "Write export .zip archive '$filename'" );

		$posts_data = $posts ? $posts : self::$posts;
		$media_data = $media ? $media : self::$media;

		// set monthly folder
		$folder = date( 'y-m' );
		$path   = Post_Export_Helper::get_file_path( $folder );

		// write the temporary posts.json file
		$json_name = 'posts.json';
		$json_path = $path . $json_name;
		$json_file = fopen( $json_path, 'w' );

		if ( ! $json_file ) {
			return false;
		}

		fwrite( $json_file, json_encode( $posts_data, JSON_PRETTY_PRINT ) );
		fclose( $json_file );

		// create a zip archive
		$zip      = new \ZipArchive();
		$zip_name = str_replace( '.zip', '', $filename ) . '.zip';
		$zip_path = $path . $zip_name;

		// delete previous zip archive
		if ( file_exists( $zip_path ) ) {
			unlink( $zip_path );
		}

		// add files to the zip archive
		if ( $zip->open( $zip_path, \ZipArchive::CREATE ) ) {

			// copy the json to the archive
			$zip->addFile( $json_path, $json_name );

			// add media
			$zip->addEmptyDir( 'media' );
			if ( is_array( $media_data ) && count( $media_data ) > 0 ) {
				foreach ( $media_data as $post_id => $_media ) {
					if ( isset( $_media['path'] ) && isset( $_media['name'] ) ) {
						$zip->addFile( $_media['path'], 'media/' . $_media['name'] );
					}
				}
			}

			$zip->close();
		} else {
			return false;
		}

		// delete temporary json file
		unlink( $json_path );

		// return path to file
		return $zip_path;
	}


	/**
	 * =================================================================
	 *                          Helper functions
	 * =================================================================
	 */

	/**
	 * @deprecated but might be used by other plugins.
	 * Use Post_Export_Helper::prepare_strings() instead.
	 */
	public static function prepare_strings( $subject, $post_id, $log = true ) {
		return Post_Export_Helper::replace_dynamic_strings( $subject, $post_id, $log );
	}

	/**
	 * Get all posts from class var
	 * 
	 * @return Preparred_Post[]
	 */
	public static function get_all_posts() {
		return self::$posts;
	}

	/**
	 * Get all posts from class var
	 * 
	 * @return array[]
	 */
	public static function get_all_media() {
		return self::$media;
	}

	/**
	 * Create filename from export attributes
	 *
	 * @param Preparred_Post|Preparred_Post[] $posts
	 * @param array         $args
	 *
	 * @return string $filename
	 */
	public static function write_filename( $posts, $args = array() ) {

		// vars
		$filename     = array();
		$default_args = array(
			'whole_posttype' => false,
			// we don't need other arguments to create the filename
		);
		$args = array_merge( $default_args, (array) $args );

		// bulk export
		if ( is_array( $posts ) && isset( $posts[0] ) ) {
			$bulk = true;
			$post = $posts[0];
			$post = ! is_object( $post ) ? get_post( $post ) : $post;
		}
		// single export
		elseif ( is_object( $posts ) ) {
			$bulk = false;
			$post = $posts;
			// add post name to filename
			$filename[] = $post->post_name;
		}
		// unknown export
		if ( ! isset( $post ) || ! $post || ! isset( $post->post_type ) ) {
			return 'post-export';
		}

		$post_type     = $post->post_type;
		$default_types = array(
			'post'             => 'post',
			'page'             => 'page',
			'attachment'       => 'media_file',
			'tp_forms'         => 'form',
			'dynamic_template' => 'template',
			'tp_posttypes'     => 'posttype',
			'greyd_popup'      => 'popup',
		);

		// handle default posttypes
		if ( isset( $default_types[ $post_type ] ) ) {

			if ( $args['whole_posttype'] && $post_type == 'tp_posttypes' ) {
				$post_type = 'posts_and_posttype';
			} else {
				$post_type = $default_types[ $post_type ] . ( $bulk ? 's' : '' );
			}
		}
		// handle other posttypes
		elseif ( $bulk ) {
			$post_type_obj = get_post_type_object( $post_type );
			if ( $post_type_obj && isset( $post_type_obj->labels ) ) {
				$post_type = $post_type_obj->labels->name;
			}
		}

		// add post type to filename
		$filename[] = $post_type;

		// add site title to filename
		$filename[] = get_bloginfo( 'name' );

		// cleanup strings
		foreach ( $filename as $k => $string ) {
			$filename[ $k ] = preg_replace( '/[^a-z_]/', '', strtolower( preg_replace( '/-+/', '_', $string ) ) );
		}

		return implode( '-', $filename );
	}


	/**
	 * =================================================================
	 *                          Compatiblity functions
	 * =================================================================
	 * 
	 * @since 2.0
	 * 
	 * These functions are used by external plugins:
	 * * get_supported_post_types
	 * * get_translation_tool
	 * * get_languages_codes
	 * * enable_logs
	 * * import_posts
	 * * import_get_conflict_posts_for_backend_form
	 * * import_get_conflict_actions_from_backend_form
	 * 
	 * They are used by the old export class and are therefore
	 * still needed for backwards compatibility.
	 */

	public static $logs = false;

	public static function get_supported_post_types() {
		return Post_Export_Helper::get_supported_post_types();
	}

	public static function get_translation_tool() {
		return Post_Export_Helper::get_translation_tool();
	}

	public static function get_languages_codes() {
		return Post_Export_Helper::get_language_codes();
	}

	public static function enable_logs() {
		return Post_Export_Helper::enable_logs();
	}

	public static function import_posts( $posts, $conflict_actions = array(), $zip_file = '' ) {
		return Post_Import::import_posts( $posts, $conflict_actions, $zip_file );
	}

	public static function import_get_conflict_posts_for_backend_form( $posts ) {
		return Post_Import::import_get_conflict_posts_for_backend_form( $posts );
	}

	public static function import_get_conflict_actions_from_backend_form( $conflicts ) {
		return Post_Import::import_get_conflict_actions_from_backend_form( $conflicts );
	}
}
