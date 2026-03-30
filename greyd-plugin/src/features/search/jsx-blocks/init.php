<?php
/**
 * Load JSX blocks
 */
namespace Greyd\Search;

if ( !defined( 'ABSPATH' ) ) exit;


function load_jsx_blocks() {
	// Generates an array of directory paths based on the build folder
	$block_directories = glob( GREYD_PLUGIN_PATH . "/build/features/search/jsx-blocks/*", GLOB_ONLYDIR );

	foreach ($block_directories as $block) {
	
		//remove /build/ from the path
		$enqueue_path = str_replace( '/build/', '/src/', $block );
		// debug( $enqueue_path);

		if ( file_exists( $enqueue_path . '/enqueue.php' ) ) {
			require_once $enqueue_path . '/enqueue.php';
		} 

		register_block_type( $block );

		//get blockname
		$blockname = basename( $block );
		$script_handle = generate_block_asset_handle( 'greyd/'.$blockname, 'editorScript');

		wp_set_script_translations( $script_handle, 'greyd_hub', GREYD_PLUGIN_PATH  . '/languages' );
	}
}
add_action( 'init', __NAMESPACE__.'\load_jsx_blocks' );