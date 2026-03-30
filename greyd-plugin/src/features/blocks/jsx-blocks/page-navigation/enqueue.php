<?php
/**
 * Enqueue block editor assets.
 */
namespace Greyd\Blocks;

if ( !defined( 'ABSPATH' ) ) exit;

function enqueue_page_navigation_assets() {
	$version = defined( 'GREYD_PLUGIN_VERSION' ) ? constant( 'GREYD_PLUGIN_VERSION' ) : '1.0';

	// register the styles
	if ( function_exists( 'wp_register_style' ) ) {
		wp_register_style(
			'greyd-page-navigation-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ).'style.css',
			null,
			$version
		);
	}
}
add_action( 'enqueue_block_assets', __NAMESPACE__.'\enqueue_page_navigation_assets' );
