<?php
/**
 * Load JSX blocks
 */
namespace greyd\blocks;

use Greyd\Helper;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Check if a block should be loaded based on its status
 */
function should_load_block_dir( $status ) {
	switch ( $status ) {
		case 'alpha':
			return Helper::is_greyd_alpha();
		case 'beta':
			// Beta blocks load if beta is enabled OR if alpha is enabled
			return Helper::is_greyd_beta() || Helper::is_greyd_alpha();
		default:
			return true;
	}
}

/**
 * Load blocks from a specific directory with conditional registration
 * 
 * @param string $directory_name The directory name within jsx-blocks folder
 */
function load_block_dir( $block_name ) {
	
	$block_path = GREYD_PLUGIN_PATH . '/build/features/blocks/jsx-blocks/' . $block_name;
	
	// Check if block directory exists
	if ( ! is_dir( $block_path ) ) {
		return;
	}
	
	// Load enqueue file if it exists
	$enqueue_path = str_replace( '/build/', '/src/', $block_path ) . '/enqueue.php';
	if ( file_exists( $enqueue_path ) ) {
		require_once $enqueue_path;
	}
	
	// Register the block type
	register_block_type( $block_path );
	
	// Set up translations
	$script_handle = generate_block_asset_handle( 'greyd/' . $block_name, 'editorScript' );
	wp_set_script_translations( $script_handle, 'greyd_hub', GREYD_PLUGIN_PATH . '/languages' );
}

/**
 * Load all JSX blocks based on current environment settings
 */
function load_jsx_blocks() {

	/**
	 * Define all available blocks with their status (stable, beta, alpha)
	 */
	$blocks = array(
		array(
			'name' => 'page-navigation',
			'status' => 'beta',
		)
	);
	
	// Load blocks from each directory
	foreach ( $blocks as $block_config ) {
		
		// Check if block should be loaded based on its status
		if ( ! should_load_block_dir( $block_config['status'] ) ) {
			continue;
		}

		load_block_dir( $block_config['name'] );
	}
}

add_action( 'init', __NAMESPACE__.'\load_jsx_blocks' );
