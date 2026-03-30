<?php

/**
 * Register front-end JavaScript for image lightbox functionality.
 * 
 * Note: Only enqueued when an image block with lightbox enabled is rendered.
 */
function greyd_image_lightbox_register_scripts() {
	wp_register_script(
		'greyd-core-image-lightbox-frontend', 
		plugins_url( '/image-lightbox.js', __FILE__ )
	);
}
add_action( 'wp_enqueue_scripts', 'greyd_image_lightbox_register_scripts' );

/**
 * Conditionally enqueue lightbox script for image blocks.
 */
function greyd_image_lightbox_render_block( $block_content, $block ) {
	// Check if lightbox is enabled for this image block
	$has_lightbox = isset( $block['attrs']['lightbox'] ) && isset( $block['attrs']['lightbox']['enabled'] ) ? $block['attrs']['lightbox']['enabled'] : false;

	if ( ! $has_lightbox ) {
		return $block_content;
	}

	// Since we need the JavaScript for this block, enqueue it
	wp_enqueue_script( 'greyd-core-image-lightbox-frontend' );

	return $block_content;
}
add_filter( 'render_block_core/image', 'greyd_image_lightbox_render_block', 10, 2 );
