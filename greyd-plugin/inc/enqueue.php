<?php
/**
 * Backend enqueue.
 */
namespace Greyd;

if ( !defined( 'ABSPATH' ) ) exit;

new Enqueue($config);
class Enqueue {

	/**
	 * Holds the plugin config
	 */
	private $config;
	
	/**
	 * Holds all custom styles to be rendered inside the footer.
	 * 
	 * @var array
	 */
	private static $custom_styles = [];

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;

		add_action( 'wp_print_scripts', array($this, 'render_custom_styles_header'), 99 );
		add_action( 'wp_footer', array($this, 'render_custom_styles'), 98 );
		
		add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts'), 9 );

		add_filter( 'upload_mimes', array($this, 'add_svg_to_allowed_uploads') );
		add_filter( 'wp_check_filetype_and_ext', array($this, 'disable_mime_check'), 10, 4 );
	}

	/**
	 * Load scripts in the admin area.
	 */
	public function load_backend_scripts() {

		/**
		 * 'greyd-admin-style' and 'greyd-admin-script'
		 * are the basic admin scripts.
		 * All other greyd features/plugins/themes depend on them. If a feature/plugin/theme 
		 * runs standalone, these should be copied and registered there as fallback.
		 */

		// Styles
		wp_register_style(
			"greyd-admin-style",
			GREYD_PLUGIN_URL . '/assets/css/admin-style.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style(
			"greyd-admin-style"
		);

		wp_register_style(
			"greyd-admin-features",
			GREYD_PLUGIN_URL . '/assets/css/admin-features.css',
			array(),
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style(
			"greyd-admin-features"
		);

		// Scripts
		wp_register_script(
			"greyd-admin-script",
			GREYD_PLUGIN_URL . '/assets/js/admin-script.js',
			array('wp-data', 'jquery'),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script(
			"greyd-admin-script"
		);
		wp_localize_script("greyd-admin-script", 'ajax_var', array(
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
		));

		// inline script deprecated
		wp_localize_script( "greyd-admin-script", 'wizzard_details', array(
			'upload_url'    => wp_upload_dir()["baseurl"],
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce('install'),
			'is_multisite'  => is_multisite() ? 'true' : 'false'
		) );

		// inline script before
		// define global greyd var
		wp_add_inline_script( "greyd-admin-script", 'var greyd = greyd || {}; greyd = {
			upload_url:   "'.wp_upload_dir()["baseurl"].'",
			ajax_url:     "'.admin_url( 'admin-ajax.php' ).'",
			nonce:        "'.wp_create_nonce('install').'",
			is_multisite: "'.(is_multisite() ? 'true' : 'false').'",
			...greyd
		};', 'before');

	}

	/**
	 * Enqueue basic Block Editor Styles & Scripts.
	 * 
	 * @note not used right now
	 */
	public function block_editor_scripts() {

		/**
		 * 'greyd-editor-style', 'greyd-editor-components' and 'greyd-editor-tools'
		 * are the basic blockeditor scripts.
		 * All other greyd features/plugins/themes depend on them. If a feature/plugin/theme 
		 * runs standalone, these should be copied and registered there as fallback.
		 */

		// editor styles
		wp_register_style(
			'greyd-editor-style',
			GREYD_PLUGIN_URL . '/assets/css/editor.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style('greyd-editor-style');

		// custom components
		wp_register_script(
			'greyd-editor-components',
			GREYD_PLUGIN_URL . '/assets/js/editor-components.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION,
			true
		);
		wp_add_inline_script('greyd-editor-components', 'var greyd = greyd || {};', 'before');
		wp_enqueue_script('greyd-editor-components');

		// tools
		wp_register_script(
			'greyd-editor-tools',
			GREYD_PLUGIN_URL . '/assets/js/editor-tools.js',
			array( 'wp-blocks', 'wp-dom', 'wp-i18n', 'lodash' ),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script( 'greyd-editor-tools' );

		// script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-editor-components', 'greyd_hub', plugin_dir_url($this->config->plugin_file).'languages' );
			wp_set_script_translations( 'greyd-editor-tools', 'greyd_hub', plugin_dir_url($this->config->plugin_file).'languages' );
		}
	}
	
	/**
	 * Add a custom style to the current page.
	 * 
	 * @param string $css       The custom CSS styles to render.
	 * @param bool $in_footer   Whether to render it in the footer.
	 * 
	 * @return bool Whether the style was successfully rendered.
	 */
	public static function add_custom_style( $css, $in_footer=true ) {
		
		// doing it wrong
		if ( !is_string($css) || empty($css) ) {
			return false;
		}

		/**
		 * @filter greyd_enqueue_render_custom_styles_in_footer
		 * 
		 * @param bool $render_in_footer    Whether the style should be rendered in the footer.
		 * @param string $style             The custom CSS styles to render.
		 * 
		 * @return bool
		 */
		$in_footer = apply_filters( 'greyd_enqueue_render_custom_styles_in_footer', $in_footer, $css );

		if ( $in_footer || Helper::is_rest_request() ) {
			self::$custom_styles[] = $css;
		}
		else {

			// classic greyd suite.
			if ( class_exists( '\processor' ) ) {
				$css = \processor::process_styles_string($css);
			}

			echo '<style type="text/css">'.$css.'</style>';
		}

		return true;
	}

	/**
	 * Get all custom styles saved in this class.
	 */
	public static function get_custom_styles() {
		return self::$custom_styles;
	}

	/**
	 * Check if there are custom styles enqueued.
	 * 
	 * @return bool
	 */
	public static function has_custom_styles() {
		return count( self::get_custom_styles() ) > 0;
	}

	/**
	 * Render all custom styles inside the header
	 */
	public static function render_custom_styles_header() {

		// if we're in the classic greyd suite, render all styles in the footer by default
		if ( Helper::is_greyd_classic() ) {
			return;
		}

		/**
		 * @filter greyd_enqueue_max_styles_in_header
		 * 
		 * @param int $max_styles    The maximum number of styles to render in the header.
		 * 
		 * @return int
		 */
		$max_styles = apply_filters( 'greyd_enqueue_max_styles_in_header', 10 );

		// doing it wrong
		if ( !is_int($max_styles) || $max_styles < 1 ) {
			return;
		}

		// get all styles
		$styles = self::get_custom_styles();

		// doing it wrong
		if ( !is_array($styles) || empty($styles) ) {
			return;
		}

		// render the first couple of styles in the header
		$css = '';
		$styles = array_slice( $styles, 0, $max_styles );
		foreach ( $styles as $style ) {
			if ( !empty($style) && is_string($style) ) {
				$css .= trim( preg_replace( "/<\/?style[^>]*>/", '', $style ) ).' ';
			}
		}
		$css = apply_filters( 'greyd_custom_css', $css );
		
		if ( empty($css) ) return;

		// classic greyd suite.
		if ( class_exists( '\processor' ) ) {
			$css = \processor::process_styles_string($css);
		}

		if ( !empty($css) ) {

			echo '<style id="greyd-custom-styles-header">'.$css.'</style>';

			// remove rendered styles
			self::$custom_styles = array_slice( self::$custom_styles, $max_styles );
		}
	}

	/**
	 * Render all custom styles inside the footer
	 */
	public static function render_custom_styles() {
		
		$css = '';
		foreach ( (array) self::get_custom_styles() as $style ) {
			if ( !empty($style) && is_string($style) ) {
				$css .= trim( preg_replace( "/<\/?style[^>]*>/", '', $style ) ).' ';
			}
		}
		$css = apply_filters( 'greyd_custom_css', $css );
		
		if ( empty($css) ) return;

		// classic greyd suite.
		if ( class_exists( '\processor' ) ) {
			$css = \processor::process_styles_string($css);
		}

		if ( !empty($css) ) {

			echo '<style id="greyd-custom-styles-footer">'.$css.'</style>';

			// clear styles
			self::$custom_styles = [];
		}
	}

	/**
	 * Get all custom stylesheets as a string.
	 * This function is usually called within a Rest request that returns a rendered block.
	 * This way we can add all custom stylesheets to the response.
	 * 
	 * @return string $stylesheets All stylesheets wrapped in a <style> tag.
	 */
	public static function get_all_custom_stylesheets() {

		ob_start();
		if ( \Greyd\Helper::is_greyd_suite() && class_exists( '\enqueue' ) ) {
			\enqueue::render_custom_styles();
		}
		self::render_custom_styles();
		$stylesheets = ob_get_contents();
		ob_end_clean();

		return $stylesheets;
	}

	/**
	 * Get core layout styles.
	 * Styles from the core/gutenberg style engine are not rendered in the block_content
	 * and may duplicate classes from the original page content.
	 * We replace these classes with unique ones and add the styles via add_custom_style().
	 * @since 1.14.1
	 * 
	 * @note To actually render the styles you might need to call render_custom_styles() manually.
	 * 
	 * @param string $block_content  The block content to render.
	 * 
	 * @return string $block_content The block content with replaced classes.
	 */
	public static function replace_core_css_classes_in_block_content( $block_content ) {
		if ( function_exists('gutenberg_style_engine_get_stylesheet_from_context') ) {
			// gutenberg
			$style = gutenberg_style_engine_get_stylesheet_from_context( 'block-supports' );
		}
		else if ( function_exists('wp_style_engine_get_stylesheet_from_context') ) {
			// core > 6.1.0
			$style = wp_style_engine_get_stylesheet_from_context( 'block-supports' );
		}
		if ( $style && !empty($style) ) {
			// get layout classes
			preg_match_all( '/wp-container-[a-z-]+-layout-\d+/', $style, $classes );
			if ($classes[0]) {
				$classes = array_unique($classes[0]);
				// debug($classes);
				foreach ( $classes as $class ) {
					// replace with unique classname
					$newclass = uniqid($class);
					$style = preg_replace( '/'.$class.'([^\d])/', $newclass.'$1', $style );
					$block_content = preg_replace( '/'.$class.'([^\d])/', $newclass.'$1', $block_content );
				}
			}
			self::add_custom_style( $style );
		}

		return $block_content;
	}

	/**
	 * Add svg to allowed uploads
	 * 
	 * @filter 'upload_mimes'
	 * 
	 * @param array $mime_types
	 * 
	 * @return array $mime_types
	 */
	public function add_svg_to_allowed_uploads( $mime_types ) {
		$mime_types['svg'] = 'image/svg+xml';
		return $mime_types;
	}

	/**
	 * Disable mime check for uploaded files.
	 * @see https://almostinevitable.com/wp-svg-problems-with-wp-5/
	 * @see https://secure.wphackedhelp.com/blog/fix-file-type-not-permitted-for-security-reasons-wordpress-error/
	 * 
	 * @filter 'wp_check_filetype_and_ext'
	 * 
	 * @param array  $data
	 * @param string $file
	 * @param string $filename
	 * @param array  $mimes
	 * 
	 * @return array $data
	 */
	public function disable_mime_check( $data, $file, $filename, $mimes ) {
		$wp_filetype     = wp_check_filetype( $filename, $mimes );
		$ext             = $wp_filetype['ext'];
		$type            = $wp_filetype['type'];
		$proper_filename = $data['proper_filename'];
		return compact( 'ext', 'type', 'proper_filename' );
	}
}