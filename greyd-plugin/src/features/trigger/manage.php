<?php
/**
 * Trigger features.
 */
namespace greyd\blocks\trigger;
// namespace Greyd\Trigger;

if ( !defined( 'ABSPATH' ) ) exit;

new Manage($config);
class Manage {

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
		$this->config->css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri  = plugin_dir_url( __FILE__ ) . 'assets/js';

		// enqueue trigger scripts
		add_action( 'enqueue_block_editor_assets', array($this, 'register_blocks_assets') );

	}

	/**
	 * Register and enqueue dynamic scripts for the editor
	 */
	public function register_blocks_assets() {

		// editor styles
		wp_register_style(
			'greyd-trigger-blocks-style',
			$this->config->css_uri.'/editor.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style('greyd-trigger-blocks-style');

		// editor script
		wp_register_script(
			'greyd-trigger-editor',
			$this->config->js_uri.'/editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-trigger-editor');

		// trigger script
		wp_register_script(
			'greyd-trigger',
			$this->config->js_uri.'/trigger.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-trigger');
		
		// dtag format script
		wp_register_script(
			'greyd-format-trigger',
			$this->config->js_uri.'/format-trigger.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-format-trigger');

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-trigger-editor', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-trigger', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-format-trigger', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
	}

}