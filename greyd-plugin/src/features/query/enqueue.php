<?php
/**
 * Enqueue Scripts and Styles for Query Blocks.
 * - core/query (extension)
 * - core/post-template (extension)
 */
namespace Greyd\Query;

if ( !defined( 'ABSPATH' ) ) exit;

new Enqueue($config);
class Enqueue {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;
		$this->config->assets_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		// enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_scripts' ) );

		// editor
		if ( is_admin() ) {
			/**
			 * 'enqueue_block_assets' action should be used here. 
			 * @see features/blocks/enqueue.php
			 */
			$action = \Greyd\Helper::is_active_plugin('gutenberg/gutenberg.php') ? 'enqueue_block_assets' : 'current_screen';
			add_action( $action, array($this, 'register_block_editor_styles') );
			add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );
		}
		
	}

	/**
	 * Enqueue frontend scripts.
	 * @action wp_enqueue_scripts
	 */
	public function add_frontend_scripts() {

		$version = GREYD_VERSION;
	
		// post slider script
		wp_register_script(
			'greyd-query-postslider-script',
			$this->config->assets_url.'assets/js/post-slider.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script( 'greyd-query-postslider-script' );

		// define rest_api setup
		// used by query and search features
		$rest_setup = array(
			'root' => esc_url_raw( str_replace( 'wp-json', '?rest_route=', rest_url() ) ),
			'routes' => array(
				'livequeries' => "greyd/v1/livequeries/",
				'livequery'   => "greyd/v1/livequery/",
				'livesearch'  => "greyd/v1/livesearch/",
				'autosearch'  => "wp/v2/search/"
			),
			'lang' =>			 \Greyd\Helper::get_language_code(),
			'posts_per_page' =>	 esc_js( get_option( 'posts_per_page', 10 ) ),
			'is_greyd_blocks' => \Greyd\Helper::is_greyd_blocks(),
			'is_vc' =>			 \Greyd\Helper::is_greyd_classic() && class_exists( '\WPBMap' )
		);
		// define in global greyd var
		wp_add_inline_script(
			'greyd-query-postslider-script',
			'var greyd = greyd || {}; greyd.rest_api = '.json_encode($rest_setup).';',
			'before'
		);


		// register the live filter script
		wp_register_script(
			'greyd-query-livefilter-script',
			$this->config->assets_url.'assets/js/live-filter.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		// enqueue in \Post_Template->prepare_live_filter_wrapper_atts()
		// wp_enqueue_script( 'greyd-query-livefilter-script' );

		// register the main style
		wp_register_style(
			'greyd-post-slider-style',
			$this->config->assets_url.'assets/css/style.css',
			array( ),
			$version
		);
		wp_enqueue_style('greyd-post-slider-style');

	}

	/**
	 * Enqueue block styles.
	 * @action enqueue_block_assets
	 */
	public function register_block_editor_styles() {

		if ( !is_admin() ) return;

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

		// add preview style
		add_editor_style( $this->config->assets_url.'assets/css/editor-blocks.css' );

		// add frontend styles
		if ( defined( 'GREYD_VERSION' ) && version_compare( GREYD_VERSION, '1.6.9', '>' ) ) {
			wp_register_style(
				'greyd-query-frontend-style',
				$this->config->assets_url.'assets/css/style.css',
				array( ),
				GREYD_VERSION
			);
			wp_enqueue_style( 'greyd-query-frontend-style' );
		}

	}
	
	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {

		// add editor scripts

		// core/query (extension)
		wp_register_script(
			'greyd-query-editor-script',
			$this->config->assets_url.'assets/js/block-query.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);
		wp_enqueue_script( 'greyd-query-editor-script' );

		// advanced filter script
		wp_register_script(
			'greyd-query-advanced-filter',
			$this->config->assets_url.'assets/js/advanced-filter.js',
			array( 'greyd-query-editor-script', 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script( 'greyd-query-advanced-filter' );

		// core/post-template (extension)
		wp_register_script(
			'greyd-post-template-editor-script',
			$this->config->assets_url.'assets/js/block-post-template.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);
		wp_enqueue_script( 'greyd-post-template-editor-script' );

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-query-editor-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-query-advanced-filter', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-post-template-editor-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
	}

	
}