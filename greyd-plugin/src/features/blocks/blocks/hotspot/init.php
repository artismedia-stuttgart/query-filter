<?php
/**
 * Hotspot block.
 * 
 * @since 1.2.6
 */
function greyd_register_hotspot_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.1';

	// register the scripts
	if ( function_exists('wp_register_script') ) {

		wp_register_script(
			'greyd-hotspot-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		wp_register_script(
			'greyd-hotspot-frontend-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'frontend.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);

		wp_register_script(
			'greyd-hotspot-polyfill-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'dialog-polyfill/dialog-polyfill.js',
			null,
			$version,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
	}

	// add script translations
	if ( function_exists('wp_set_script_translations') ) {
		wp_set_script_translations( 'greyd-hotspot-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
	}
	
	// register the styles
	if ( function_exists('wp_register_style') ) {
		wp_register_style(
			'greyd-hotspot-frontend-style',
			(
				\greyd\blocks\enqueue::is_greyd_classic()
				? trailingslashit( plugin_dir_url( __FILE__ ) ) . 'classic/style.css'
				: trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css'
			),
			null,
			$version
		);
		wp_register_style(
			'greyd-hotspot-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			$version
		);
	}

	// register the blocks
	if ( function_exists('register_block_type') ) {
		register_block_type( 'greyd/hotspot-wrapper', array(
			'editor_script' => array(
				'greyd-hotspot-editor-script'
			),
			'editor_style'  => array(
				'greyd-hotspot-editor-style'
			),
			'view_script' => array(
				'greyd-hotspot-frontend-script',
				'greyd-hotspot-polyfill-script',
			),
			'style' => array(
				'greyd-hotspot-frontend-style'
			)
		) );
		register_block_type( 'greyd/hotspot', array(
			'editor_script' => array(
				'greyd-hotspot-editor-script'
			),
		) );
	}
}
add_action( 'init', 'greyd_register_hotspot_block', 99 );


require_once __DIR__ . '/render.php';