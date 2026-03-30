<?php
/**
 * Export & Import Post Admin
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Post_Export_Admin( $config );

class Post_Export_Admin {

	/**
	 * Plugin config
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		$this->config = (object) $config;

		// UI
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );
		add_filter( 'page_row_actions', array( $this, 'add_export_row_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'add_export_row_action' ), 10, 2 );
		add_filter( 'media_row_actions', array( $this, 'add_export_row_action' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_import_page_title_action' ) );
		add_action( 'admin_notices', array( $this, 'display_transient_notice' ) );

		// debug
		add_action( 'admin_init', array( $this, 'maybe_enable_debug_mode' ) );
	}

	/**
	 * Check if current screen supports import/export
	 *
	 * @return bool
	 */
	public static function is_current_screen_supported() {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$supported = false;

		$screen = get_current_screen();
		if ( is_object( $screen ) && isset( $screen->base ) ) {
			if ( $screen->base === 'edit' ) {
				$post_types = array_flip( Post_Export_Helper::get_supported_post_types() );
				if ( isset( $post_types[ $screen->post_type ] ) ) {
					$supported = true;
				}
			} elseif ( $screen->base === 'upload' ) {
				$supported = true;
			}
		}

		/**
		 * Filter to customize whether the current screen supports post export/import functionality.
		 * 
		 * This filter allows developers to extend or modify the logic that determines
		 * which admin screens should display post export/import options. It's useful
		 * for adding support to custom post type screens or implementing custom
		 * screen detection logic.
		 * 
		 * @filter greyd_post_export_is_current_screen_supported
		 * 
		 * @param bool  $supported Whether the current screen supports export/import.
		 * @param object $screen   The current WP_Screen object.
		 * 
		 * @return bool           Whether the current screen supports export/import.
		 */
		return apply_filters( 'greyd_post_export_is_current_screen_supported', $supported, $screen );
	}

	/**
	 * Add overlay contents
	 *
	 * @filter 'greyd_overlay_contents'
	 *
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {

		if ( ! self::is_current_screen_supported() ) {
			return $contents;
		}

		$screen = get_current_screen();
		// debug( $screen );

		/**
		 * Export
		 */

		// default options
		$export_options = array(
			'nested' => array(
				'name'    => 'nested',
				'title'   => __( "Export nested content", 'greyd_hub' ),
				'descr'   => __( "Templates, media, etc. are added to the download so that used images, backgrounds, etc. will be displayed correctly on the target website.", 'greyd_hub' ),
				'checked' => true,
			),
			'menus'  => array(
				'name'  => 'resolve_menus',
				'title' => __( "Resolve menus", 'greyd_hub' ),
				'descr' => __( "All menus will be converted to static links.", 'greyd_hub' ),
				'checked' => true,
			),
		);

		// add options to include posts for posttypes or terms for taxonomies
		if ( $screen->id === 'edit-tp_posttypes' ) {
			$export_options['posttypes'] = array(
				'name'  => 'whole_posttype',
				'title' => __( "Include individual posts", 'greyd_hub' ),
				'descr' => __( "All posts of the post type will be added to the download.", 'greyd_hub' ),
			);
			$export_options['all_terms'] = array(
				'name'  => 'all_terms',
				'title' => __( "Include all terms", 'greyd_hub' ),
				'descr' => __( "All terms of the taxonomy will be added to the download.", 'greyd_hub' ),
			);
		}

		// remove options for media
		if ( $screen->base === 'upload' ) {
			unset( $export_options['nested'], $export_options['menus'] );
		}

		// add option to include translations when translation tool is active
		if ( ! empty( Post_Export_Helper::get_translation_tool() ) ) {
			$export_options['translations'] = array(
				'name'  => 'translations',
				'title' => __( "Include translations", 'greyd_hub' ),
				'descr' => __( "All translations of the post will be added to the download.", 'greyd_hub' ),
			);
		}

		// build the form
		$export_form = "<a id='post_export_download'></a><form id='post_export_form' class='" . ( count( $export_options ) ? 'inner_content' : '' ) . "'>";
		foreach ( $export_options as $option ) {
			$name         = $option['name'];
			$checked      = isset( $option['checked'] ) && $option['checked'] ? "checked='checked'" : '';
			$export_form .= "<label for='$name'>
				<input type='checkbox' id='$name' name='$name' $checked />
				<span>" . $option['title'] . '</span>
				<small>' . $option['descr'] . '</small>
			</label>';
		}
		$export_form .= '</form>';

		// add notices
		if ( $screen->id === 'edit-page' ) {
			$export_form .= Helper::render_info_box(
				array(
					'text'  => __( "Posts in query loops are not included in the import. Posts and Post Types must be exported separately.", 'greyd_hub' ),
					'style' => 'info',
				)
			);
		}

		// add the contents
		$contents['post_export'] = array(
			'confirm' => array(
				'title'   => __( "Export", 'greyd_hub' ),
				'descr'   => sprintf( __( "Do you want to export \"%s\"?", 'greyd_hub' ), "<b class='replace'></b>" ),
				'content' => $export_form,
				'button'  => __( "Export now", 'greyd_hub' ),
			),
			'loading' => array(
				'descr' => __( "Exporting post.", 'greyd_hub' ),
			),
			'success' => array(
				'title' => __( "Export successful", 'greyd_hub' ),
				'descr' => __( "Post has been exported.", 'greyd_hub' ),
			),
			'fail'    => array(
				'title' => __( "Export failed", 'greyd_hub' ),
				'descr' => '<span class="replace">' . __( "The post could not be exported.", 'greyd_hub' ) . '</span>',
			),
		);

		/**
		 * Import
		 */

		// get form options
		$options = '';
		foreach ( array(
			'skip'    => __( "Skip", 'greyd_hub' ),
			'replace' => __( "Replace", 'greyd_hub' ),
			'keep'    => __( "Keep both", 'greyd_hub' ),
		) as $name => $value ) {
			$options .= "<option value='$name'>$value</option>";
		}
		$options = urlencode( $options );

		// add the contents
		$contents['post_import'] = array(
			'check_file' => array(
				'title'   => __( "Please wait", 'greyd_hub' ),
				'descr'   => __( "The file is being validated.", 'greyd_hub' ),
				'content' => '<div class="loading"><div class="loader"></div></div><a href="javascript:window.location.href=window.location.href" class="color_light escape">' . __( "Cancel", 'greyd_hub' ) . '</a>',
			),
			'confirm'    => array(
				'title'   => __( "Import", 'greyd_hub' ),
				'content' => "<form id='post_import_form'>
									<input type='file' name='import_file' id='import_file' title='" . __( "Select file", 'greyd_hub' ) . "' accept='zip,application/octet-stream,application/zip,application/x-zip,application/x-zip-compressed' >
									<div class='conflicts'>
										<p>" . __( "<b>Attention:</b> Some content in the file already appears to exist on this site. Choose what to do with it.", 'greyd_hub' ) . "</p>
										<div class='inner_content' data-options='$options'></div>
									</div>
									<div class='new'>
										<p>" . sprintf( __( "No conflicts found. Do you want to import the file \"%s\" now?", 'greyd_hub' ), "<strong class='post_title'></strong>" ) . '</p>
									</div>
								</form>',
				'button'  => __( "Import now", 'greyd_hub' ),
			),
			'loading'    => array(
				'descr' => __( "Importing post.", 'greyd_hub' ),
			),
			'reload'     => array(
				'title' => __( "Import successful", 'greyd_hub' ),
				'descr' => __( "Post has been imported.", 'greyd_hub' ),
			),
			'fail'       => array(
				'title' => __( "Import failed", 'greyd_hub' ),
				'descr' => '<span class="replace">' . __( "The file could not be imported.", 'greyd_hub' ) . '</span>',
			),
		);
		
		return $contents;
	}

	/**
	 * Add export button to row actions
	 *
	 * @param  array   $actions
	 * @param  WP_Post $post
	 * @return array
	 */
	public function add_export_row_action( $actions, $post ) {

		if ( self::is_current_screen_supported() ) {
			$actions['greyd_export'] = "<a style='cursor:pointer;' onclick='greyd.postExport.openExport(this);' data-post_id='" . $post->ID . "'>" . __( 'Export', 'greyd_hub' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Add import button via javascript
	 */
	public function add_import_page_title_action() {

		if ( !self::is_current_screen_supported() || !current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		wp_register_script(
			'greyd-post-export-script',
			plugin_dir_url( __FILE__ ) . 'assets/js/post-export.js',
			array( 'jquery', 'greyd-admin-script' ),
			GREYD_VERSION
		);
		wp_enqueue_script( 'greyd-post-export-script' );

		wp_add_inline_script(
			'greyd-post-export-script',
			'jQuery(function() {
				greyd.backend.addPageTitleAction( "⬇&nbsp;' . __( 'Import', 'greyd_hub' ) . '", { onclick: "greyd.postExport.openImport();" } );
			});',
			'after'
		);
	}

	/**
	 * Display an admin notice if the transient 'greyd_transient_notice' is set
	 */
	public function display_transient_notice() {

		// get transient
		$transient = get_transient( 'greyd_transient_notice' );

		if ( $transient ) {
			// cut transient into pieces
			$transient = explode( '::', $transient );
			$mode      = $transient[0];
			$msg       = $transient[1];
			// this is my last resort
			Helper::show_message( $msg, $mode );

			// delete transient
			delete_transient( 'greyd_transient_notice' );
		}
	}


	/**
	 * =================================================================
	 *                          DEBUG
	 * =================================================================
	 */

	/**
	 * Enable the debug mode via the URl-parameter 'greyd_export_debug'
	 */
	public function maybe_enable_debug_mode() {

		if ( ! isset( $_GET['greyd_export_debug'] ) ) {
			return;
		}

		Post_Export_Helper::enable_logs();
		$echo = '';
		ob_start();

		do_action( 'before_greyd_export_debug' );

		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
			$post    = get_post( $post_id );

			// $meta = get_post_meta( $post_id );
			// var_dump( $meta );
			// foreach($meta as $k => $v) {
			// if ( strpos($k, '_oembed_') === 0 ) {
			// delete_post_meta( $post_id, $k );
			// }
			// }

			$response = Post_Export::export_post(
				$post_id,
				array(
					'append_nested'  => true,
					'whole_posttype' => true,
					'all_terms'      => true,
					'resolve_menus'  => true,
					'translations'   => true,
				)
			);
			echo "<hr>\r\n\r\n";

			if ( is_object( $response ) && isset( $response->post_content ) ) {
				$response->post_content = esc_attr( $response->post_content );
			}

			foreach ( Post_Export::get_all_posts() as $post ) {
				$post->post_content = esc_attr( $post->post_content );
				debug( $post );
			}
			echo "<hr>\r\n\r\n=== R E S P O N S E ===\r\n\r\n";
			var_dump( $response, true );
		} else {
			$posts = \Greyd\Global_Contents\GC_Helper::prepare_global_post_for_import( strval( $_GET['greyd_export_debug'] ) );
			debug( $posts );
		}

		do_action( 'after_greyd_export_debug' );

		$echo = ob_get_contents();
		ob_end_clean();
		if ( ! empty( $echo ) ) {
			echo '<pre>' . $echo . '</pre>';
		}

		wp_die();
	}
}
