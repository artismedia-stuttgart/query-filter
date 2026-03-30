<?php
/**
 * Enqueue the animation assets.
 *
 * @since 1.6.0
 */
namespace greyd\animations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Enqueue();

class Enqueue {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_blocks_assets' ) );
	}

	/**
	 * Add frontend styles & scripts.
	 */
	public function add_frontend_assets() {

		// styles
		wp_register_style(
			'greyd-animation-public-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
			null,
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0'
		);
		wp_enqueue_style( 'greyd-animation-public-style' );

		/**
		 * Greyd scroll handler.
		 * 
		 * @note This script is not loaded by default. Currently it is only used
		 * inside the animation feature and therefore stil located here.
		 * @todo Move this script to a more appropriate location as soon
		 * as it is used in other features as well.
		 */
		if ( ! wp_script_is( 'greyd-scroll-observer', 'registered' ) ) {
			wp_register_script(
				'greyd-scroll-observer',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'scroll-observer.js',
				null,
				defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0',
				array(
					'strategy' => 'defer',
					'in_footer' => true
				)
			);
		}
		if ( ! wp_script_is( 'greyd-scroll-observer', 'enqueued' ) ) {
			wp_enqueue_script( 'greyd-scroll-observer' );
		}

		// animation framework
		wp_register_script(
			'greyd-animation-public-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'animations.js',
			array( 'greyd-scroll-observer' ),
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0',
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script( 'greyd-animation-public-script' );
	}

	/**
	 * Add block editor assets.
	 */
	public function register_blocks_assets() {

		// editor style
		wp_register_style(
			'greyd-animation-editor-style',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
			null,
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0'
		);
		wp_enqueue_style( 'greyd-animation-editor-style' );

		// editor script
		wp_register_script(
			'greyd-animation-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			defined( 'GREYD_BLOCKS_VERSION' ) ? constant( 'GREYD_BLOCKS_VERSION' ) : '1.0'
		);
		wp_enqueue_script( 'greyd-animation-editor-script' );

		// add script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'greyd-animation-editor-script',
				'greyd_blocks',
				trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages'
			);
		}

	}
}
