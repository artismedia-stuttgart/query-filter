<?php
/**
 * Lottie Animation Features.
 */
namespace greyd\blocks\lottie;
// namespace Greyd\Lottie;

// use Greyd\Settings as Settings;

if ( !defined( 'ABSPATH' ) ) exit;

new Manage($config);
class Manage {

	/**
	 * Holds the plugin config
	 */
	private $config;
	
	/**
	 * Holds the current settings for frontend
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;
		$this->config->css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri  = plugin_dir_url( __FILE__ ) . 'assets/js';
		$this->config->lib_src = plugin_dir_url( __FILE__ ) . 'assets/lib/lottie.min.js';

		// todo lottie settings by filter to pagespeed module

		// init
		add_action( 'init', array($this, 'init') );

		add_filter( 'upload_mimes', array($this, 'add_json_to_allowed_uploads') );
		add_filter( 'wp_check_filetype_and_ext', array($this, 'disable_mime_check'), 10, 4 );
	}

	public function init() {

		// get settings
		$this->settings = array();
		if ( method_exists('\Greyd\Settings', 'get_setting') ) {
			$this->settings = \Greyd\Settings::get_setting( array('site') );
		}
		else if ( method_exists('\basics', 'get_setting') ) {
			$this->settings = \basics::get_setting( array('site') );
		}

		// stop if Lottie is disabled
		if (
			isset($this->settings['lottie'])
			&& isset($this->settings['lottie']['mode'])
			&& $this->settings['lottie']['mode'] === 'disable'
		) {
			return;
		}
		
		// backend
		// set priority higher than 90 (loaded after theme enqueue)
		add_action( 'admin_enqueue_scripts', array($this, 'load_admin_scripts'), 91 );
		// register block
		add_action( 'init', array($this, 'register_blocks'), 99 );

		if (is_admin()) return;

		// frontend
		// set priority higher than 1 (loaded after theme enqueue)
		add_action( 'wp_footer', array($this, 'add_frontend'), 2 );
		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'render_block'), 10, 2 );

	}


	/**
	 * =================================================================
	 *                          Frontend
	 * =================================================================
	 */

	/**
	 * Add frontend scripts
	 */
	public function add_frontend() {

		// frontend script
		wp_register_script(
			'greyd-lottie-script',
			$this->config->js_uri.'/frontend.js',
			array('jquery'),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script('greyd-lottie-script');

		// maybe load lib
		if ( $this->settings['lottie']['mode'] === 'default' ) {
			/**
			 * Only load lottie lib when not registered by Theme
			 */
			global $wp_scripts;
			if (!isset($wp_scripts->registered['bodymovin_js'])) {
				wp_register_script(
					'greyd-lottie-lib', 
					$this->config->lib_src, 
					array('jquery'), 
					'5.10.1', 
					false
				);
				wp_enqueue_script('greyd-lottie-lib');
			}
		}

		// make setup for frontend
		$data = array(
			'src'     => esc_js( $this->config->lib_src ),
			'mode'    => esc_js( $this->settings['lottie']['mode'] ),
			'time'    => esc_js( $this->settings['lottie']['time'] ),
			'mobile'  => esc_js( $this->settings['lottie']['mobile'] )
		);

		// define global greyd var with setup
		wp_add_inline_script('greyd-lottie-script', '
			var greyd = greyd || {};
			if (typeof greyd.setup === "undefined") greyd.setup = {};
			greyd.setup = {
				lottie: '.json_encode($data).',
				...greyd.setup
			};
		', 'before');

	}

	/**
	 * Hook Greyd block rendering.
	 * 
	 * @filter 'greyd_blocks_render_block'
	 * 
	 * @param array $content
	 *      @property string block_content     block content about to be appended.
	 *      @property array  html_atts         html wrapper attributes
	 *      @property string style             css styles
	 * @param array  $block             full block, including name and attributes.
	 * 
	 * @return array $rendered
	 *      @property string block_content    altered Block Content
	 *      @property string html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public function render_block($content, $block) {
		// debug("render lottie");

		/**
		 * Update animation class to use plugin instead of theme js.
		 * Only for old content.
		 */
		$content['block_content'] = str_replace('bm_icon', 'lottie-animation', $content['block_content']);

		return $content;

	}


	/**
	 * =================================================================
	 *                          Backend
	 * =================================================================
	 */

	public function load_admin_scripts() {

		/**
		 * Load only on edit, upload and block_editor pages
		 */
		$screen = get_current_screen();
		if ($screen->base !== 'post' && 
			$screen->base !== 'upload' &&
			!$screen->is_block_editor) return;

		/**
		 * Only load lottie lib when not registered by Theme
		 */
		global $wp_scripts;
		if (!isset($wp_scripts->registered['bodymovin_js'])) {

			// debug("load lottie lib in backend");

			wp_register_script(
				'greyd-lottie-lib', 
				$this->config->lib_src, 
				array('jquery'), 
				'5.10.1', 
				false
			);
			wp_enqueue_script('greyd-lottie-lib');
		}

		// debug("load lottie tools in backend");

		// backend tools
		wp_register_script(
			'greyd-lottie-tools',
			$this->config->js_uri.'/backend.js',
			array( 'jquery', 'lodash' ),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script( 'greyd-lottie-tools' );

		// define global greyd var
		wp_add_inline_script( 'greyd-lottie-tools', 'var greyd = greyd || {};', 'before' );

	}


	/**
	 * =================================================================
	 *                          Blockeditor
	 * =================================================================
	 */

	/**
	 * Register Block.
	 */
	public function register_blocks() {

		// register script
		wp_register_script(
			'greyd-lottie-editor-script',
			$this->config->js_uri.'/editor.js',
			array( 'greyd-lottie-tools', 'greyd-components', 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-lottie-editor-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}

		// register lottie animation block
		register_block_type( 'greyd/anim', array(
			'editor_script' => 'greyd-lottie-editor-script',
		) );

	}

	/**
	 * Add json to allowed uploads
	 * 
	 * @filter 'upload_mimes'
	 * 
	 * @param array $mime_types
	 * 
	 * @return array $mime_types
	 */
	public function add_json_to_allowed_uploads( $mime_types ) {
		$mime_types['json'] = 'application/json';
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