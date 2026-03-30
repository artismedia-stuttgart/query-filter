<?php
/**
 * Search block.
 * 
 * @deprecated and moved to feature/search
 * load here if search feature is deactivated.
 */


if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 2.3.0 moved to feature/search
 * load here if search feature is deactivated.
 */
if ( \Greyd\Features::is_feature_active( 'search' ) ) {
	return;
}


if ( !function_exists('greyd_register_search_block') ) {
	function greyd_register_search_block() {

		// in case we need to update the scripts & styles
		$version = defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0';

		// register the scripts
		if ( function_exists( 'wp_register_script' ) ) {

			wp_register_script(
				'greyd-search-editor-script',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
				array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
				$version
			);

			wp_register_script(
				'greyd-search-frontend-script',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'frontend.js',
				null,
				$version,
				array(
					'strategy' => 'defer',
					'in_footer' => true
				)
			);
		}

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-search-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages' );
		}

		// register the styles
		if ( function_exists( 'wp_register_style' ) ) {
			wp_register_style(
				'greyd-search-frontend-style',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
				null,
				$version
			);
			wp_register_style(
				'greyd-search-editor-style',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
				null,
				$version
			);
		}

		// register the blocks
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				'greyd/search',
				array(
					'editor_script' => 'greyd-search-editor-script',
					'editor_style'  => 'greyd-search-editor-style',
					'view_script'   => array(
						'greyd-search-frontend-script',
					),
					'style'         => 'greyd-search-frontend-style',
				)
			);
			register_block_type(
				'greyd/search-input',
				array(
					'editor_script' => 'greyd-search-editor-script',
				)
			);
			register_block_type(
				'greyd/search-submit',
				array(
					'editor_script' => 'greyd-search-editor-script',
				)
			);
			register_block_type(
				'greyd/search-sorting',
				array(
					'editor_script' => 'greyd-search-editor-script',
				)
			);
			register_block_type(
				'greyd/search-filter',
				array(
					'editor_script' => 'greyd-search-editor-script',
				)
			);
		}
	}
}
add_action( 'init', 'greyd_register_search_block', 99 );


if ( !function_exists('greyd_search_filter_allowed_html_tags') ) {
	/**
	 * Filters the HTML tags that are allowed for a given context.
	 * 
	 * HTML tags and attribute names are case-insensitive in HTML but must be
	 * added to the KSES allow list in lowercase. An item added to the allow list
	 * in upper or mixed case will not recognized as permitted by KSES.
	 * 
	 * @param array[] $html    Allowed HTML tags.
	 * @param string  $context Context name.
	 */
	function greyd_search_filter_allowed_html_tags( $html, $context ) {

		if ( $context !== 'post' ) return $html;

		$tags = array(
			"id" => true,
			"class" => true,
			'method' => true,
			'role' => true
		);

		/**
		 * Allow form attributes.
		 */
		if ( !isset( $html['form'] ) ) {
			$html['form'] = $tags;
		} else {
			$html['form'] = array_merge( $html['form'], $tags );
		}

		return $html;
	}
}
add_filter( 'wp_kses_allowed_html', 'greyd_search_filter_allowed_html_tags', 97, 2 );

require_once __DIR__ . '/search.php';
