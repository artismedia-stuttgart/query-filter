<?php
/**
 * Register Accordion block.
 * 
 * @since 1.2.6
 */
function greyd_register_accordion_block() {

	// in case we need to update the scripts & styles
	$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0';

	// register the scripts
	if ( function_exists('wp_register_script') ) {

		wp_register_script(
			'greyd-accordion-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);

		wp_register_script(
			'greyd-accordion-frontend-script',
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
			wp_set_script_translations( 'greyd-accordion-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
		}
	}
	
	// register the styles
	if ( function_exists('wp_register_style') ) {
		wp_register_style(
			'greyd-accordion-frontend-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
			null,
			$version
		);
		wp_register_style(
			'greyd-accordion-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			$version
		);
	}

	// register the blocks
	if ( function_exists('register_block_type') ) {
		register_block_type( 'greyd/accordion', array(
			'editor_script'  => 'greyd-accordion-editor-script',
			'editor_style'   => 'greyd-accordion-editor-style',
			'view_script'    => 'greyd-accordion-frontend-script',
			'style'          => 'greyd-accordion-frontend-style'
		) );
		register_block_type( 'greyd/accordion-item', array(
			'editor_script' => 'greyd-accordion-editor-script',
		) );
	}

	// render block
	add_filter( 'greyd_blocks_render_block', function( $content, $block ) {
		
		// don't render accordion-item if title or content is empty
		if ( $block['blockName'] === 'greyd/accordion-item' ) {

			// test if title is empty
			if ( strpos( $content['block_content'], '><span></span><span class="icon' ) !== false ) {
				$content['block_content'] = "";
			}
			if ( !empty($content['block_content']) ) {
				// additionally test if content is empty
				$test = preg_replace( '/\s/', '', str_replace( '</div></div>', '', explode( '>', explode( '<div class="wp-block-greyd-accordion__content"', $content['block_content'], 2 )[1], 2 )[1] ) );
				// debug( htmlspecialchars($test) );
				// debug( strlen($test) );
				if ( strlen($test) == 0 ) {
					$content['block_content'] = "";
				}
			}

		}

		return $content;

	}, 10, 2 );

}
add_action( 'init', 'greyd_register_accordion_block', 99 );

require_once( 'render.php' );