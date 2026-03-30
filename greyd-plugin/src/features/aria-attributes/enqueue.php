<?php
/**
 * Enqueue the aria assets.
 *
 * @since 2.17.0
 */
namespace greyd\aria_attributes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Enqueue();

class Enqueue {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_blocks_assets' ), 20 );
	}

	/**
	 * Add block editor assets.
	 */
	public function register_blocks_assets() {

		// editor style
		wp_register_style(
			'greyd-aria-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0'
		);
		wp_enqueue_style( 'greyd-aria-editor-style' );

		// editor script - make dependencies more robust
		$dependencies = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' );
		
		// Add greyd dependencies if they exist
		if ( wp_script_is( 'greyd-tools', 'registered' ) ) {
			$dependencies[] = 'greyd-tools';
		}
		if ( wp_script_is( 'greyd-components', 'registered' ) ) {
			$dependencies[] = 'greyd-components';
		}

		wp_register_script(
			'greyd-aria-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			$dependencies,
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0'
		);
		wp_enqueue_script( 'greyd-aria-editor-script' );

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'greyd-aria-editor-script',
				'greyd_blocks',
				trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages'
			);
		}
		
	}
}
