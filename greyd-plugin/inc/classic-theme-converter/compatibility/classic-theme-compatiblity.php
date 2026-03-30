<?php
/**
 * Main Theme Functions.
 *
 * @package Greyd
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Compatibility();
class Compatibility {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// scripts & styles
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_styles' ), 20 );
		add_action( 'enqueue_block_assets', array($this, 'add_editor_styles') );

		add_filter( 'greyd_blocks_gradient_presets', array( $this, 'add_gradient_presets' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function add_frontend_styles() {

		// if theme is inactive, we don't need to do anything
		if ( ! defined( 'GREYD_THEME_CONFIG' ) ) {
			return;
		}

		// compatibility with the extension in the theme
		if ( class_exists( '\Greyd\Theme\Compatibility') ) {
			return;
		}

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		$main_theme_stylesheet = $this->get_main_theme_stylesheet();

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		if ( $main_theme_stylesheet !== 'greyd-theme' ) {
			return;
		}

		// Register compatiblity stylesheet
		wp_register_style(
			'greyd-classic-compatibility-styles',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'classic-theme-styles.css',
			array( $main_theme_stylesheet ),
			GREYD_VERSION
		);
		wp_enqueue_style( 'greyd-classic-compatibility-styles' );
	}

	/**
	 * Enqueue styles for the block editor.
	 */
	public function add_editor_styles() {

		// if theme is inactive, we don't need to do anything
		if ( ! defined( 'GREYD_THEME_CONFIG' ) ) {
			return;
		}

		// compatibility with the extension in the theme
		if ( class_exists( '\Greyd\Theme\Compatibility') ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		$main_theme_stylesheet = $this->get_main_theme_stylesheet();

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		if ( $main_theme_stylesheet !== 'greyd-theme' ) {
			return;
		}
		
		add_editor_style( trailingslashit( plugin_dir_url( __FILE__ ) ) . 'classic-theme-styles.css' );
	}

	/**
	 * Add gradient presets.
	 * 
	 * @param array $gradient_presets
	 * 
	 * @return array
	 */
	public function add_gradient_presets( $gradient_presets ) {

		// if theme is inactive, we don't need to do anything
		if ( ! defined( 'GREYD_THEME_CONFIG' ) ) {
			return $gradient_presets;
		}

		// compatibility with the extension in the theme
		if ( class_exists( '\Greyd\Theme\Compatibility') ) {
			return $gradient_presets;
		}

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		$main_theme_stylesheet = $this->get_main_theme_stylesheet();

		// If the theme is neither 'greyd-theme' or 'greyd-wp', don't load this stylesheet
		if ( $main_theme_stylesheet !== 'greyd-theme' ) {
			return $gradient_presets;
		}

		return array_merge(
			$gradient_presets,
			array(
				array(
					"name"     => __( "Primary to secondary", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--primary, #4f309e) 0%,var(--wp--preset--color--secondary, #f2b25a) 100%)",
					"slug"     => "color11-to-color12"
				),
				array(
					"name"     => __( "Primary to secondary to tertiary", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--primary, #4f309e) 0%,var(--wp--preset--color--secondary, #f2b25a) 50%,var(--wp--preset--color--tertiary, #f9dd61) 100%)",
					"slug"     => "color11-to-color13"
				),
				array(
					"name"     => __( "Secondary to tertiary", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--secondary, #f2b25a) 0%,var(--wp--preset--color--tertiary, #f9dd61) 100%)",
					"slug"     => "color12-to-color13"
				),
				array(
					"name"     => __( "Primary to secondary to tertiary to very dark", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--primary, #4f309e) 0%,var(--wp--preset--color--secondary, #f2b25a) 33%,var(--wp--preset--color--tertiary, #f9dd61) 66%,var(--wp--preset--color--foreground, #0e1111) 100%)",
					"slug"     => "color11-to-color31"
				),
				array(
					"name"     => __( "Branding gradient in steps", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--primary, #4f309e) 0%,var(--wp--preset--color--secondary, #f2b25a) 50%,var(--wp--preset--color--primary, #4f309e) 50%,var(--wp--preset--color--secondary, #f2b25a) 75%,var(--wp--preset--color--tertiary, #f9dd61) 75%,var(--wp--preset--color--secondary, #f2b25a) 100%)",
					"slug"     => "branding-steps"
				),
				array(
					"name"     => __( "Experimental branding gradient", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--tertiary, #f9dd61) 50%,var(--wp--preset--color--secondary, #f2b25a) 50%,var(--wp--preset--color--secondary, #f2b25a) 60%,var(--wp--preset--color--primary, #4f309e) 60%,var(--wp--preset--color--primary, #4f309e) 70%,var(--wp--preset--color--secondary, #f2b25a) 80%,var(--wp--preset--color--tertiary, #f9dd61) 80%,var(--wp--preset--color--tertiary, #f9dd61) 90%,var(--wp--preset--color--primary, #4f309e) 90%)",
					"slug"     => "branding-experiment"
				),
	
				array(
					"name"     => __( "White to light", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--lightest, #fff) 0%,var(--wp--preset--color--mediumlight, #cbc6d1) 100%)",
					"slug"     => "color62-to-color22"
				),
				array(
					"name"     => __( "Light to medium dark", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--background, #f9f7ff) 0%,var(--wp--preset--color--mediumlight, #cbc6d1) 50%,var(--wp--preset--color--mediumdark, #9992a3) 100%)",
					"slug"     => "color23-to-color32"
				),
				array(
					"name"     => __( "White to dark", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--lightest, #fff) 0%,var(--wp--preset--color--base, #e9e6ed) 33%,var(--wp--preset--color--mediumdark, #9992a3) 66%,var(--wp--preset--color--dark, #3d3549) 100%)",
					"slug"     => "color62-to-color21"
				),
				array(
					"name"     => __( "Light to very dark", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--base, #e9e6ed) 0%,var(--wp--preset--color--dark, #3d3549) 66%,var(--wp--preset--color--foreground, #0e1111) 100%)",
					"slug"     => "color33-to-color31"
				),
				array(
					"name"     => __( "Getting darker in steps", "greyd_hub" ),
					"gradient" => "linear-gradient(130deg,var(--wp--preset--color--background, #f9f7ff) 0%,var(--wp--preset--color--mediumlight, #cbc6d1) 50%,var(--wp--preset--color--base, #e9e6ed) 50%,var(--wp--preset--color--mediumlight, #cbc6d1) 75%,var(--wp--preset--color--mediumdark, #9992a3) 75%,var(--wp--preset--color--dark, #3d3549) 100%)",
					"slug"     => "darken-steps"
				),
				array(
					"name"     => __( "Transparent to white", "greyd_hub" ),
					"gradient" => "linear-gradient(180deg,var(--wp--preset--color--transparent, transparent) 0%,var(--wp--preset--color--lightest, #fff) 100%)",
					"slug"     => "color63-to-color62"
				),
	
				array(
					'name'     => __( 'Primary to secondary', 'greyd_hub' ),
					'gradient' => 'linear-gradient(145deg,var(--wp--preset--color--primary, #4f309e) 0%,--color12: var(--wp--preset--color--secondary, #f2b25a) 100%)',
					'slug'     => 'color-11-to-color-12-se'
				),
				array(
					'name'     => __( 'Primary to secondary', 'greyd_hub' ),
					'gradient' => 'linear-gradient(202deg,var(--wp--preset--color--primary, #4f309e) 0%,--color12: var(--wp--preset--color--secondary, #f2b25a) 100%)',
					'slug'     => 'color-11-to-color-12-sw'
				),
				array(
					'name'     => __( 'Primary to secondary', 'greyd_hub' ),
					'gradient' => 'linear-gradient(29deg,var(--wp--preset--color--primary, #4f309e) 0%,--color12: var(--wp--preset--color--secondary, #f2b25a) 100%)',
					'slug'     => 'color-11-to-color-12-ne'
				),
	
				array(
					'name'     => __( 'Very dark to transparent', 'greyd_hub' ),
					'gradient' => 'linear-gradient(180deg,var(--wp--preset--color--foreground, #0e1111) 0%,var(--wp--preset--color--transparent, transparent) 100%)',
					'slug'     => 'color-31-to-color-63-s'
				),
				array(
					'name'     => __( 'Dark to very dark', 'greyd_hub' ),
					'gradient' => 'linear-gradient(29deg,var(--wp--preset--color--dark, #3d3549) 0%,var(--wp--preset--color--foreground, #0e1111) 100%)',
					'slug'     => 'color-21-to-color-31-ne'
				),
				array(
					'name'     => __( 'Medium dark to very light', 'greyd_hub' ),
					'gradient' => 'linear-gradient(45deg,var(--wp--preset--color--mediumdark, #9992a3) 0%,var(--wp--preset--color--background, #f9f7ff) 100%)',
					'slug'     => 'color-32-to-color-23-ne'
				),
	
				array(
					'name'     => __( 'Light to white', 'greyd_hub' ),
					'gradient' => 'linear-gradient(0deg,var(--wp--preset--color--base, #e9e6ed) 0%,var(--wp--preset--color--lightest, #fff) 100%)',
					'slug'     => 'color-33-to-color-62-n'
				),
				array(
					'name'     => __( 'Light to very light', 'greyd_hub' ),
					'gradient' => 'linear-gradient(180deg,var(--wp--preset--color--base, #e9e6ed) 49.99%,var(--wp--preset--color--background, #f9f7ff) 50%)',
					'slug'     => 'color-33-to-color-23-s'
				),
			)
		);
	}

	private function get_main_theme_stylesheet() {
		$main_theme = is_child_theme() ? wp_get_theme( get_template() ) : wp_get_theme();
		if ( $main_theme ) {
			return $main_theme->get_stylesheet();
		}
		return null;
	}
}
