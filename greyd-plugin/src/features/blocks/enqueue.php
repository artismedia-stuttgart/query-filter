<?php
/**
 * Enqueue block editor assets.
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

new enqueue($config);
class enqueue {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;

		// setup
		$this->config->assets_url = plugin_dir_url( __FILE__ ) . 'assets';
		$this->config->assets_path = trailingslashit( __DIR__ ) . 'assets';

		add_action( 'after_setup_theme', array($this, 'add_basic_theme_supports'), 1 );
		add_action( 'init', array($this, 'init') );
	}

	/**
	 * Init the hooks
	 */
	public function init() {
		
		add_action( 'wp_enqueue_scripts', array($this, 'add_frontend_styles'), 90 );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array($this, 'add_backend_styles'), 999 );
			add_filter( 'block_editor_settings_all', array($this, 'add_editor_styles'), 99, 2 );
			/**
			 * 'enqueue_block_assets' action should be used here.
			 * wp 6.4.x without gutenberg inits earlier so we need 'current_screen' to load editor styles (add_editor_style)
			 * when this is fixed in core, the correct action can be used.
			 */
			$action = \Greyd\Helper::is_active_plugin('gutenberg/gutenberg.php') ? 'enqueue_block_assets' : 'current_screen';
			add_action( $action, array($this, 'register_block_editor_styles') );
			add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );

			// disable block-directory
			remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
			remove_action( 'enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory' );
		}
	}

	/**
	 * Adds basic theme supports for non-block themes.
	 * @see \wp-includes\theme.php.
	 */
	function add_basic_theme_supports() {
		if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
			return;
		}

		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		/*
		* Makes block themes support HTML5 by default for the comment block and search form
		* (which use default template functions) and `[caption]` and `[gallery]` shortcodes.
		* Other blocks contain their own HTML5 markup.
		*/
		add_theme_support( 'html5', array( 'comment-form', 'comment-list', 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'automatic-feed-links' );

	}

	/**
	 * =================================================================
	 *                          ENQUEUE STYLES
	 * =================================================================
	 */

	/**
	 * Add frontend block styles
	 * @action wp_enqueue_scripts
	 */
	public function add_frontend_styles() {

		global $wp_styles;
		// debug($wp_styles);

		// unregister wp-editor-classic-layout-styles
		$wp_styles->registered['wp-editor-classic-layout-styles']->src = '';

		// basic frontend blocks styles
		if ( class_exists('\processor') ) {
			wp_register_style(
				'greyd-blocks-frontend-style',
				$this->config->assets_url.'/css/classic/blocks.css',
				array( ),
				GREYD_VERSION
			);
		}
		else {
			wp_register_style(
				'greyd-blocks-frontend-style',
				$this->config->assets_url.'/css/blocks.css',
				array( ),
				GREYD_VERSION
			);
		}
		wp_enqueue_style('greyd-blocks-frontend-style');

	}

	public function add_backend_styles() {

		$screen = get_current_screen();
		$disable_preview_helper = false;

		// on edit screens
		if ($screen->base === 'post' && $screen->action !== 'add') {

			// load custom and/or google fonts
			// only in Theme when processor is available
			if (class_exists('processor')) {
				\processor::load_scripts();
			}

			// remove editor helper
			if ( self::is_greyd_classic() && $screen->post_type === "dynamic_template" ) {
				// disable preview-helper for all system-templates
				if ( !has_term('dynamic', 'template_type', $_GET['post']) ) {
					$disable_preview_helper = true;
				}
			}
			if ( $screen->post_type === "greyd_popup" ) {
				$disable_preview_helper = true;
			}
		}

		if ( $screen->base !== 'customize' ) {
			echo '<script>var disablePreviewHelper = '.($disable_preview_helper ? 'true' : 'false').';</script>';
		}
	}

	/**
	 * Add styles to the editor preview
	 */
	public function add_editor_styles($editor_settings, $editor_context) {
		// debug($editor_settings);
		// debug($editor_context);

		if ( ! self::is_greyd_classic() ) {
			return $editor_settings;
		}

		// Theme Styles in editor
		// todo: refactor/deprecate
		$css = "body:not(.block-editor-block-preview__container) { 
					height: auto !important; 
					overflow-x: unset; 
					padding-bottom: 0px !important; 
				} 
				body.block-editor-block-preview__container { 
					position: relative !important; 
				} 
				body > div { 
					max-width: 100%; 
				} 
				.block-editor-writing-flow { 
					width: 100% 
				}";
		$editor_settings['defaultEditorStyles'][] = array('css' => $css);
		
		// only in Theme when processor is available
		if ( class_exists('processor') ) {
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/root.css'));
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/style.css'));
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/helper.css'));
			// $editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory_uri().'/assets/css/deprecated/vc_styles.css'));
			// $editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory_uri().'/assets/css/deprecated/vc_basics.css'));
			// $editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles(get_template_directory_uri().'/assets/css/deprecated/vc_anims.css'));

			$editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/root.css'));
			$editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/style.css'));
			$editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/helper.css'));
			$editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/deprecated/vc_styles.css'));
			$editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/deprecated/vc_basics.css'));
			// $editor_settings['styles'][] = array('css' => \processor::process_styles(get_template_directory().'/assets/css/deprecated/vc_anims.css'));

			$editor_settings['styles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/classic/editor-blocks.css'));
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/classic/editor-blocks.css'));
			$editor_settings['styles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/classic/blocks.css'));
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/classic/blocks.css'));
		}

		return $editor_settings;
	}

	/**
	 * Register and enqueue all the styles for the editor.
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

		// styles
		wp_register_style(
			'greyd-blocks-style',
			$this->config->assets_url.'/css/editor.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style( 'greyd-blocks-style' );

		add_editor_style( $this->config->assets_url.'/css/blocks.css' );

		add_editor_style( $this->config->assets_url.'/css/hover-helper.css' );

		if ( ! self::is_greyd_classic() ) {

			add_editor_style( $this->config->assets_url.'/css/editor-blocks.css' );

		}

	}

	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {

		if ( ! is_admin() ) return;

		// tools script
		wp_register_script(
			'greyd-tools',
			$this->config->assets_url.'/js/tools.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-tools');

		// Custom components
		wp_register_script(
			'greyd-components',
			$this->config->assets_url.'/js/components.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-components');

		// editor script
		wp_register_script(
			'greyd-editor',
			$this->config->assets_url.'/js/editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-editor');

		// formats script
		wp_register_script(
			'greyd-formats',
			$this->config->assets_url.'/js/formats.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-formats');

		// transform script
		wp_register_script(
			'greyd-transform',
			$this->config->assets_url.'/js/transform.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-transform');

		// translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			/**
			 * May be extended to wp_set_script_translations( 'my-handle', 'my-domain',
			 * plugin_dir_path( MY_PLUGIN ) . 'languages' ) ). For details see
			 * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
			 */
			wp_set_script_translations( 'greyd-tools', 'greyd_hub', $this->config->plugin_path."/languages" );
			wp_set_script_translations( 'greyd-components', 'greyd_hub', $this->config->plugin_path."/languages" );
			wp_set_script_translations( 'greyd-editor', 'greyd_hub', $this->config->plugin_path."/languages" );
			wp_set_script_translations( 'greyd-formats', 'greyd_hub', $this->config->plugin_path."/languages" );
			wp_set_script_translations( 'greyd-transform', 'greyd_hub', $this->config->plugin_path."/languages" );

		}

	}

	/**
	 * Whether the classic 'Greyd.Suite' Theme is active.
	 * @see \Greyd\Helper::is_greyd_classic()
	 */
	public static function is_greyd_classic() {
		if ( method_exists( '\Greyd\Helper', 'is_greyd_classic' ) ) {
			return \Greyd\Helper::is_greyd_classic();
		}

		// check if Greyd.Suite is active
		if ( defined( 'GREYD_CLASSIC_VERSION' ) || class_exists("\basics") ) {
			return true;
		}

		// check if Greyd Theme is active
		if ( defined( 'GREYD_THEME_CONFIG' ) ) {
			return false;
		}

		// check if Greyd.Suite is installed
		$_current_main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
		return strpos( $_current_main_theme->get('Name'), "GREYD.SUITE" ) !== false;
	}

}
