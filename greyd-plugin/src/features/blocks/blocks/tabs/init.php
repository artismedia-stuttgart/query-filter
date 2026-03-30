<?php
/**
 * Register Accordion block.
 * 
 * @since 1.2.6
 */
function greyd_register_tabs_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0';

	// register the scripts
	if ( function_exists('wp_register_script') ) {

		wp_register_script(
			'greyd-tabs-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		wp_register_script(
			'greyd-tabs-frontend-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'frontend.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-tabs-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
		}
	}
	
	// register the styles
	if ( function_exists('wp_register_style') ) {
		wp_register_style(
			'greyd-tabs-frontend-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
			null,
			$version
		);
		wp_register_style(
			'greyd-tabs-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			$version
		);
	}

	// register the blocks
	if ( function_exists('register_block_type') ) {
		register_block_type( 'greyd/tabs', array(
			'editor_script'  => 'greyd-tabs-editor-script',
			'editor_style'   => 'greyd-tabs-editor-style',
			'view_script'    => 'greyd-tabs-frontend-script',
			'style'          => 'greyd-tabs-frontend-style'
		) );
		register_block_type( 'greyd/tab', array(
			'editor_script' => 'greyd-tabs-editor-script',
		) );
	}
}
add_action( 'init', 'greyd_register_tabs_block', 99 );


require_once __DIR__ . '/render.php';