<?php
/**
 * Backend enqueue.
 */
namespace Greyd;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue styles.
 *
 * @return void
 */
function maybe_enqueue_theme_compatibility_styles() {

	// if the classic greyd suite is loaded, don't load the theme styles
	if ( defined( 'GREYD_CLASSIC_VERSION' ) ) {
		return;
	}

	// if the theme is active, don't load the theme styles
	if ( defined( 'GREYD_THEME_CONFIG' ) ) {
		return;
	}

	// enqueue editor preview support
	if ( is_admin() && function_exists( 'register_block_type' ) ) {
		maybe_enqueue_theme_compatibility_file( 'editor.css' );
	}

	// enqueue main theme compatibility styles
	maybe_enqueue_theme_compatibility_file( 'root.css' );
	maybe_enqueue_theme_compatibility_file( 'buttons.css' );
	maybe_enqueue_theme_compatibility_file( 'inputs.css' );
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_theme_compatibility_styles' );
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\maybe_enqueue_theme_compatibility_styles' );

function maybe_enqueue_theme_compatibility_file( $file_name ) {

	/**
	 * (1) Check if the file exists inside the active theme
	 * @see `{$active_theme}/greyd/css/{$file_name}`
	 */
	$theme_file = get_stylesheet_directory() . '/greyd/css/' . $file_name;
	if ( file_exists( $theme_file ) ) {
		wp_enqueue_style(
			'greyd-theme-' . $file_name,
			get_stylesheet_directory_uri() . '/greyd/css/' . $file_name,
			null,
			wp_get_theme()->get( 'Version' )
		);
		return;
	}

	/**
	 * (2) Check if the file exists inside the active parent theme
	 * @see `{$parent_theme}/greyd/css/{$file_name}`
	 */
	$parent_theme_file = get_template_directory() . '/greyd/css/' . $file_name;
	if ( file_exists( $parent_theme_file ) ) {
		wp_enqueue_style(
			'greyd-theme-' . $file_name,
			get_template_directory_uri() . '/greyd/css/' . $file_name,
			null,
			wp_get_theme()->parent()->get( 'Version' )
		);
		return;
	}

	/**
	 * (3) Check if a version of the file exists inside this directory, customized for the current theme
	 * @see `/assets/{$stylesheet}/{$file_name}`
	 */
	$current_main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
	if ( ! $current_main_theme ) {
		$current_main_theme = wp_get_theme();
	}
	$theme_file = plugin_dir_path( __FILE__ ) . 'assets/' . $current_main_theme->stylesheet . '/' . $file_name;
	if ( file_exists( $theme_file ) ) {
		wp_enqueue_style(
			'greyd-theme-' . $file_name,
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/' . $current_main_theme->stylesheet . '/' . $file_name,
			null,
			GREYD_VERSION
		);
		return;
	}

	/**
	 * (3) Enqueue the default file from this directory
	 * @see `/assets/{$file_name}`
	 */
	wp_enqueue_style(
		'greyd-theme-' . $file_name,
		trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/' . $file_name,
		null,
		GREYD_VERSION
	);
}