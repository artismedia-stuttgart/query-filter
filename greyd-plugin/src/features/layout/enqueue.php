<?php
/**
 * Enqueue advanced Layout features.
 * - core/columns extensions
 * - core/column extensions
 * - greyd/box block
 * - Background feature
 * - Grid
 */
namespace greyd\blocks\layout;
// namespace Greyd\Layout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Enqueue( $config );
class Enqueue {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Hold the global styles from WP_Theme_JSON_Resolver.
	 *
	 * @var array
	 */
	private static $global_styles_data;

	/**
	 * Hold the color palette.
	 *
	 * @var array
	 */
	private static $color_palette;

	/**
	 * Hold the color palette.
	 *
	 * @var array
	 */
	private static $gradient_presets;

	/**
	 * Hold the color palette.
	 *
	 * @var array
	 */
	private static $font_sizes;

	/**
	 * Hold the theme breakpoint values.
	 *
	 * @var array
	 */
	private static $breakpoints;

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
		$this->config->css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

		// add to JS @var greyd.data
		add_filter( 'greyd_blocks_editor_data', array( $this, 'add_editor_data' ) );

		// enqueue
		if ( is_admin() ) {
			add_filter( 'block_editor_settings_all', array( $this, 'add_editor_styles' ), 100, 2 );
			/**
			 * 'enqueue_block_assets' action should be used here. 
			 * @see features/blocks/enqueue.php
			 */
			$action = \Greyd\Helper::is_active_plugin('gutenberg/gutenberg.php') ? 'enqueue_block_assets' : 'current_screen';
			add_action( $action, array( $this, 'register_block_editor_styles' ) );
			add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_styles' ), 92 );

		// register blocks
		add_action( 'init', array( $this, 'register_blocks' ), 99 );

		// init classic theme (depends on processor)
		add_action( 'init', array( $this, 'init_greyd_classic' ) );

		// todo lazyload settings by filter to pagespeed module
	}

	/**
	 * =================================================================
	 *                          GET THEME DATA
	 * =================================================================
	 */

	/**
	 * Get merged Global Styles data.
	 *
	 * @return array
	 */
	public static function get_global_styles_data() {

		if ( self::$global_styles_data ) {
			return self::$global_styles_data;
		}

		$global_styles_data = array();

		if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			$global_styles_data = \WP_Theme_JSON_Resolver_Gutenberg::get_merged_data()->get_settings();
		} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$global_styles_data = \WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
		}

		/**
		 * Filter the global styles data.
		 * 
		 * @param array $global_styles_data
		 * @return array
		 */
		$global_styles_data = apply_filters( 'greyd_blocks_global_styles_data', $global_styles_data );

		self::$global_styles_data = $global_styles_data;

		return $global_styles_data;
	}

	/**
	 * Get the current color palette from theme.json
	 *
	 * @return array
	 */
	public static function get_color_palette() {

		if ( self::$color_palette ) {
			return self::$color_palette;
		}

		$color_palette = array();

		$settings = self::get_global_styles_data();
		if ( count( $settings ) ) {

			// theme settings
			if ( isset( $settings['color']['palette']['theme'] ) ) {
				$color_palette = $settings['color']['palette']['theme'];
			} elseif ( isset( $settings['color']['palette']['default'] ) ) {
				$color_palette = $settings['color']['palette']['default'];
			}
		}

		// classic greyd suite
		if ( method_exists( '\blocks', 'get_color_palette' ) ) {
			$color_palette = \blocks::get_color_palette();
		}

		/**
		 * Filter the color palette.
		 * 
		 * @param array $color_palette
		 * @return array
		 */
		$color_palette = apply_filters( 'greyd_blocks_color_palette', $color_palette );

		self::$color_palette = $color_palette;

		return $color_palette;
	}

	/**
	 * Get the current gradient presets from theme.json
	 *
	 * @return array
	 */
	public static function get_gradient_presets() {

		if ( self::$gradient_presets ) {
			return self::$gradient_presets;
		}

		$gradient_presets = array();

		$settings = self::get_global_styles_data();
		if ( count( $settings ) ) {

			// theme settings
			if ( isset( $settings['color']['gradients']['theme'] ) ) {
				$gradient_presets = $settings['color']['gradients']['theme'];
			} elseif ( isset( $settings['color']['gradients']['default'] ) ) {
				$gradient_presets = $settings['color']['gradients']['default'];
			}
		}

		// classic greyd suite
		if ( method_exists( '\blocks', 'get_gradient_presets' ) ) {
			$gradient_presets = \blocks::get_gradient_presets();
		}

		/**
		 * Filter the gradient presets.
		 * 
		 * @param array $gradient_presets
		 * @return array
		 */
		$gradient_presets = apply_filters( 'greyd_blocks_gradient_presets', $gradient_presets );

		self::$gradient_presets = $gradient_presets;

		return $gradient_presets;
	}

	/**
	 * Get the current font sizes from theme.json
	 *
	 * @return array
	 */
	public static function get_font_sizes() {

		if ( self::$font_sizes ) {
			return self::$font_sizes;
		}

		$font_sizes = array();

		$settings = self::get_global_styles_data();
		if ( count( $settings ) ) {

			// theme settings
			if ( isset( $settings['typography']['fontSizes']['theme'] ) ) {
				$font_sizes = $settings['typography']['fontSizes']['theme'];
			} elseif ( isset( $settings['typography']['fontSizes']['default'] ) ) {
				$font_sizes = $settings['typography']['fontSizes']['default'];
			}
		}

		// classic greyd suite
		if ( method_exists( '\blocks', 'get_font_sizes' ) ) {
			$font_sizes = \blocks::get_font_sizes();
		}

		/**
		 * Filter the font sizes.
		 * 
		 * @param array $font_sizes
		 * @return array
		 */
		$font_sizes = apply_filters( 'greyd_blocks_font_sizes', $font_sizes );

		self::$font_sizes = $font_sizes;

		return $font_sizes;
	}

	/**
	 * Get the theme breakpoint values.
	 *
	 * @return array
	 */
	public static function get_breakpoints() {

		if ( self::$breakpoints ) {
			return self::$breakpoints;
		}

		$breakpoints = array(
			// dynamic grid
			'sm' => intval( get_theme_mod( 'grid_sm', 576 ) ),
			'md' => intval( get_theme_mod( 'grid_md', 992 ) ),
			'lg' => intval( get_theme_mod( 'grid_lg', 1200 ) ),
			'xl' => intval( get_theme_mod( 'grid_xl', 1621 ) ),
			// vc grid
			/** @todo move to deprecated */
			'vc_sm' => intval( get_theme_mod( 'grid_sm', 576 ) ),
			'vc_md' => intval( get_theme_mod( 'grid_md', 992 ) ),
			'vc_lg' => intval( get_theme_mod( 'grid_lg', 1200 ) ),
		);

		/**
		 * Filter the theme's breakpoints.
		 * 
		 * @param array $breakpoints
		 * @return array
		 */
		$breakpoints = apply_filters( 'greyd_blocks_breakpoints', $breakpoints );

		self::$breakpoints = $breakpoints;

		return $breakpoints;
	}


	/**
	 * =================================================================
	 *                          ENQUEUE
	 * =================================================================
	 */

	/**
	 * Filter Blockeditor Data values.
	 * Add 'colors', 'gradients', 'fontSizes' and 'grid' values.
	 *
	 * @filter 'greyd_blocks_editor_data'
	 *
	 * @param object $data     Original Data Array
	 * @return object $data    Data Array with filtered Values
	 */
	public function add_editor_data( $data ) {

		$data['colors']    = self::get_color_palette();
		$data['gradients'] = self::get_gradient_presets();
		$data['fontSizes'] = self::get_font_sizes();
		$data['grid']      = self::get_breakpoints();

		return $data;

	}

	/**
	 * Get the theme's breakpoints.
	 * 
	 * 'sm' => 400, 'md' => 600, 'lg' => 782				 // wp (admin-bar, blocks, etc)
	 * 'sm' => 481, 'md' => 768, 'lg' => 1025, 'xl' => 1621  // greyd (old)
	 * 'sm' => 768, 'md' => 992, 'lg' => 1200 				 // vc (old)
	 * 'sm' => 576, 'md' => 768, 'lg' => 992,  'xl' => 1200  // bs (default)
	 * 'sm' => 576, 'md' => 992, 'lg' => 1200, 'xl' => 1621  // new
	 * changes:
	 * "(min-width: 481px)"  -> "(min-width: var(grid_sm))"
	 * "(min-width: 768px)"  -> "(min-width: var(grid_md))"
	 * "(min-width: 1025px)" -> "(min-width: var(grid_lg))"
	 * "(min-width: 1621px)" -> "(min-width: var(grid_xl))"
	 * "(max-width: 480px)"  -> "(max-width: var(grid_sm))"
	 * "(max-width: 767px)"  -> "(max-width: var(grid_md))"
	 * "(max-width: 1024px)" -> "(max-width: var(grid_lg))"
	 * "(max-width: 1620px)" -> "(max-width: var(grid_xl))"
	 */
	public static function get_greyd_breakpoints() {

		$breakpoints = array(
			// dynamic grid
			'sm' => intval( get_theme_mod( 'grid_sm', 576 ) ), 
			'md' => intval( get_theme_mod( 'grid_md', 992 ) ), 
			'lg' => intval( get_theme_mod( 'grid_lg', 1200 ) ), 
			'xl' => intval( get_theme_mod( 'grid_xl', 1621 ) ),
			// vc grid
			'vc_sm' => intval( get_theme_mod( 'grid_sm', 576 ) ), 
			'vc_md' => intval( get_theme_mod( 'grid_md', 992 ) ), 
			'vc_lg' => intval( get_theme_mod( 'grid_lg', 1200 ) ), 
		);
		return $breakpoints;

	}

	// /**
	//  * Get the Grid Breakpoint Values.
	//  * @return array $grid	The static var $grid
	//  */
	// public static function get_grid() {

	// 	return self::$grid;

	// }

	/**
	 * Add frontend grid styles
	 */
	public function add_frontend_styles() {

		// global $wp_styles;
		// debug($wp_styles);

		// add grid css to frontend
		if ( function_exists( '\wp_process_style' ) ) {
			wp_process_style( 'greyd-blocks-frontend-style', $this->config->css_uri . '/classic/grid.css' );
		}
		else {
			wp_enqueue_style( 'greyd-blocks-frontend-grid-style', $this->config->css_uri . '/grid.css' );
		}

	}

	/**
	 * Add styles to the editor preview
	 */
	public function add_editor_styles( $editor_settings, $editor_context ) {

		if ( ! class_exists( '\processor' ) ) {
			return $editor_settings;
		}
		// debug($editor_settings);
		// debug($editor_context);

		// preview styles
		$editor_settings['defaultEditorStyles'][] = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/grid.css' ) );
		$editor_settings['styles'][]              = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/grid.css' ) );
		$editor_settings['defaultEditorStyles'][] = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/editor-blocks.css' ) );
		$editor_settings['styles'][] = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/editor-blocks.css' ) );
		$editor_settings['defaultEditorStyles'][] = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/blocks.css' ) );
		$editor_settings['styles'][] = array( 'css' => \processor::process_styles( $this->config->css_uri . '/classic/blocks.css' ) );
		
		return $editor_settings;

	}

	/**
	 * Register and enqueue layout styles for the editor
	 * @action enqueue_block_assets
	 */
	public function register_block_editor_styles() {

		if ( ! is_admin() ) return;
		
		/**
		 * load styles only on editor pages.
		 * can be removed when proper action 'enqueue_block_assets' can be used.
		 */
		$screen = get_current_screen();
		if (
			$screen->base != 'site-editor' &&
			method_exists($screen, 'is_block_editor') &&
			!$screen->is_block_editor()
		) {
			// debug("not editor");
			return;
		}

		// preview styles
		if ( !class_exists('\processor') ) {

			add_editor_style( $this->config->css_uri.'/grid.css' );
			add_editor_style( $this->config->css_uri.'/blocks.css' );
			add_editor_style( $this->config->css_uri . '/editor-blocks.css' );
		}

		// editor styles
		wp_register_style(
			'greyd-layout-blocks-style',
			$this->config->css_uri . '/editor.css',
			array(),
			GREYD_VERSION
		);
		wp_enqueue_style( 'greyd-layout-blocks-style' );

		// css animation styles
		add_editor_style( $this->config->css_uri . '/css-anims.css' );

	}
	
	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {
		
		// extra tools
		wp_register_script(
			'greyd-layout-tools',
			$this->config->js_uri.'/layout-tools.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-dom', 'wp-i18n', 'lodash' ),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script( 'greyd-layout-tools' );

		// extra components
		wp_register_script(
			'greyd-layout-components',
			$this->config->js_uri.'/layout-components.js',
			array( 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script( 'greyd-layout-components' );

		// script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-layout-tools', 'greyd_hub', $this->config->plugin_path . '/languages' );
			wp_set_script_translations( 'greyd-layout-components', 'greyd_hub', $this->config->plugin_path . '/languages' );
		}

	}

	/**
	 * Register Block
	 */
	public function register_blocks() {

		// register script
		wp_register_script(
			'greyd-box-editor-script',
			$this->config->js_uri.'/editor.js',
			array( 'greyd-layout-components', 'greyd-layout-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-box-editor-script', 'greyd_hub', $this->config->plugin_path . '/languages' );
		}

		// register content-box block
		register_block_type(
			'greyd/box',
			array(
				'editor_script' => 'greyd-box-editor-script',
			)
		);

	}


	/**
	 * =================================================================
	 *                          Deprecated
	 * =================================================================
	 */

	/**
	 * Init the deprecated hooks
	 *
	 * @deprecated
	 */
	public function init_greyd_classic() {

		// grid breakpoints customizer and processor
		add_action( 'get_header', array( $this, 'add_process_defaults' ) );
		add_action( 'customize_register', array( $this, 'add_customizer_settings' ) );

		// grid breakpoints filter
		add_filter( 'greyd_breakpoints', array( $this, 'filter_greyd_breakpoints' ) );
	}

	/**
	 * Add dynamic breakpoints to processor root
	 *
	 * @deprecated
	 */
	public function add_process_defaults() {

		if ( ! class_exists( '\processor' ) ) {
			return;
		}

		if ( ! array_key_exists( 'GRDsm', \processor::$root ) ) {
			// common prio to bundle sets
			$prio             = 1;
			$section          = 'section_grid';
			\processor::$root = array_merge(
				\processor::$root,
				array(
					'GRDBseparator' => array(
						'section'  => $section,
						'type'     => 'separator',
						'label'    => 'Breakpoints',
						'mod'      => 'sep_basic_grid_breakpoints',
						'priority' => $prio,
					),
					'GRDsm'         => array(
						'section'     => $section,
						'type'        => 'range',
						'lbl'         => 'Mobile (SM):',
						'mod'         => 'grid_sm',
						'priority'    => $prio,
						'default'     => 576,
						'unit'        => 'px',
						'input_attrs' => array(
							'min'  => 0,
							'max'  => 1920,
							'step' => 1,
						),
						'transport'   => 'refresh',
					),
					'GRDmd'         => array(
						'section'     => $section,
						'type'        => 'range',
						'lbl'         => 'Tablet (MD):',
						'mod'         => 'grid_md',
						'priority'    => $prio,
						'default'     => 992,
						'unit'        => 'px',
						'input_attrs' => array(
							'min'  => 0,
							'max'  => 1920,
							'step' => 1,
						),
						'transport'   => 'refresh',
					),
					'GRDlg'         => array(
						'section'     => $section,
						'type'        => 'range',
						'lbl'         => 'Notebook (LG):',
						'mod'         => 'grid_lg',
						'priority'    => $prio,
						'default'     => 1200,
						'unit'        => 'px',
						'input_attrs' => array(
							'min'  => 0,
							'max'  => 1920,
							'step' => 1,
						),
						'transport'   => 'refresh',
					),
					'GRDxl'         => array(
						'section'     => $section,
						'type'        => 'range',
						'lbl'         => 'Desktop (XL):',
						'mod'         => 'grid_xl',
						'priority'    => $prio,
						'default'     => 1621,
						'unit'        => 'px',
						'input_attrs' => array(
							'min'  => 0,
							'max'  => 1920,
							'step' => 1,
						),
						'transport'   => 'refresh',
					),
				)
			);
		}

	}

	/**
	 * Add the new settings to the customizer
	 *
	 * @deprecated
	 */
	public function add_customizer_settings( $wp_customize ) {

		$this->add_process_defaults();

	}

	/**
	 * Filter the theme's breakpoints.
	 *
	 * @filter 'greyd_breakpoints'
	 * @deprecated
	 */
	public function filter_greyd_breakpoints( $breakpoints ) {

		$breakpoints = self::get_breakpoints();
		return $breakpoints;

	}
}
