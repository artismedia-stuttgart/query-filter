<?php
/**
 * Register Menu block.
 * 
 * @since 1.6.0
 */
if ( !function_exists('greyd_register_menu_block') ) {
	function greyd_register_menu_block() {

		/**
		 * This is a classic-theme block.
		 * It is not needed as soon as nav menus are not supported
		 * by your theme.
		 */
		if ( ! get_theme_support( 'menus' ) ) return;
	
		// in case we need to update the scripts & styles
		$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.6.0';
	
		// register the scripts
		if ( function_exists('wp_register_script') ) {
	
			wp_register_script(
				'greyd-menu-editor-script',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'edit.js',
				array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
				$version
			);
	
			// add script translations
			if ( function_exists('wp_set_script_translations') ) {
				wp_set_script_translations( 'greyd-menu-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
			}
		}
		
		// register the styles
		if ( function_exists('wp_register_style') ) {
			wp_register_style(
				'greyd-menu-frontend-style',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
				null,
				$version
			);
		}
	
		// register the blocks
		if ( function_exists('register_block_type') ) {
			register_block_type( 'greyd/menu', array(
				'editor_script'   => 'greyd-menu-editor-script',
				'style'           => 'greyd-menu-frontend-style',
				'render_callback' =>  'greyd_render_menu_block',
				'supports' => array( 
					'anchor' => true,
					'html' => false,
					'customClassName' => false
				),
				'attributes' => array(
					'anchor' => array( 'type' => 'string' ),
					'dynamic_parent' => array( 'type' => 'string' ), // dynamic template backend helper
					'dynamic_value' => array( 'type' => 'object' ), // dynamic template frontend helper
					'dynamic_fields' => array( 'type' => 'array' ),
					'menu' => array( 'type' => 'string', 'default' => '' ),
					'depth' => array( 'type' => 'boolean', 'default' => 0 ),
					'greydClass' => array( 'type' => 'string' ),
					'listStyles' => array( 'type' => 'object' ),
					'subStyles' => array( 'type' => 'object' ),
					'lock' => array( 'type' => 'object' ),
					/** @deprecated since 1.1.2 */
					// 'itemStyles' => array( 'type' => 'object' ),
				),
			) );
		}
	}
}
add_action( 'init', 'greyd_register_menu_block', 99 );