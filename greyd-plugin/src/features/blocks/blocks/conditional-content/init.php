<?php
namespace greyd\blocks;

/**
 * Register Conditional Content block.
 */
function register_conditional_content_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0';

	// register the scripts
	if ( function_exists( 'wp_register_script' ) ) {

		wp_register_script(
			'greyd-conditional-content-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-conditional-content-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages' );
		}
	}

	// register the block
	if ( function_exists( 'register_block_type' ) ) {

		require_once __DIR__ . '/render.php';

		register_block_type(
			'greyd/conditional-content',
			array(
				'editor_script'   => 'greyd-conditional-content-editor-script',
				'render_callback' => 'greyd\blocks\render_conditional_content_block',
			)
		);
	}
}
add_action( 'init', 'greyd\blocks\register_conditional_content_block', 99 );
