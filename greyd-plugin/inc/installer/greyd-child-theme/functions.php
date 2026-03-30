<?php

add_action( 'wp_enqueue_scripts', 'greyd_child_enqueue_styles' );

/**
 * Enqueue Greyd Theme styles.
 *
 * @return void
 */
function greyd_child_enqueue_styles(): void {
	wp_enqueue_style( 'greyd-child-style', get_stylesheet_uri(), array( 'greyd-theme' ), wp_get_theme()->get( 'Version' ) );
}
