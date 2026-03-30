<?php
/**
 * Register Popover block.
 *
 * @since 1.4.3
 */
function greyd_register_popover_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.2';

	// register the scripts
	if ( function_exists( 'wp_register_script' ) ) {

		wp_register_script(
			'greyd-popover-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		wp_register_script(
			'greyd-popover-frontend-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'frontend.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-popover-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages' );
		}
	}

	// register the styles
	if ( function_exists( 'wp_register_style' ) ) {
		wp_register_style(
			'greyd-popover-frontend-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
			null,
			$version
		);
		wp_register_style(
			'greyd-popover-hamburger-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'hamburger.css',
			null,
			$version
		);
		wp_register_style(
			'greyd-popover-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			$version
		);
	}

	// register the blocks
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'greyd/popover',
			array(
				'editor_script'   => 'greyd-popover-editor-script',
				'editor_style'    => 'greyd-popover-editor-style',
				'view_script'     => 'greyd-popover-frontend-script',
				'style'           => 'greyd-popover-frontend-style',
				'render_callback' => 'greyd_render_popover_block'
			)
		);
		register_block_type(
			'greyd/popover-popup',
			array(
				'editor_script'   => 'greyd-popover-editor-script',
				'render_callback' => 'greyd_render_popover_popup_block'
			)
		);
		register_block_type(
			'greyd/popover-button',
			array(
				'editor_script'   => 'greyd-popover-editor-script',
				'style'           => 'greyd-popover-hamburger-style',
				'render_callback' => 'greyd_render_popover_button_block'
			)
		);
	}
}
add_action( 'init', 'greyd_register_popover_block', 99 );

/**
 * Add Popover block to allowed blocks inside Navigation block.
 * 
 * @since 2.5.0
 * 
 * @param array $settings The block settings.
 * @param array $metadata The block metadata.
 * 
 * @return array The block settings.
 */
function greyd_allow_popover_block_inside_navigation_block( $settings, $metadata ) {

	if ( 'core/navigation' === $metadata['name'] ) {
		
		// add popover to allowed blocks if set
		if ( isset( $settings['allowed_blocks'] ) ) {
			$settings['allowed_blocks'][] = 'greyd/popover';
		}
	}
	return $settings;
}
add_filter( 'block_type_metadata_settings', 'greyd_allow_popover_block_inside_navigation_block', 10, 2 );

require_once __DIR__ . '/render.php';