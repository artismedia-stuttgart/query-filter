<?php
/**
 * Register iframe block.
 *
 * @since 1.7.0
 */
function greyd_register_iframe_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0';

	// register the scripts
	if ( function_exists( 'wp_register_script' ) ) {

		wp_register_script(
			'greyd-iframe-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-iframe-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages' );
		}
	}

	// register the styles
	if ( function_exists( 'wp_register_style' ) ) {
		wp_register_style(
			'greyd-iframe-frontend-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
			null,
			$version
		);
	}

	// register the blocks
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'greyd/iframe',
			array(
				'editor_script' => 'greyd-iframe-editor-script',
				'style'         => 'greyd-iframe-frontend-style'
			)
		);
	}
}
add_action( 'init', 'greyd_register_iframe_block', 99 );
