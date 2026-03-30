<?php
/**
 * Render placeholder form block.
 *
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 *
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 *
 * @return string $block_content    altered Block Content
 */
function __greyd_render_form_block( $block_content, $block ) {

	if ( !function_exists('is_plugin_active') ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_plugin_active("greyd_tp_forms/init.php") ) {
		return $block_content;
	}

	if ( $block['blockName'] === 'greyd/form' ) {
		$link = __("https://greyd.io/greyd-forms/", "greyd_hub");
		$message = sprintf(
			__("Install and activate %s to enable and display this form.", 'greyd_hub'),
			'<a href="'.$link.'" target="_blank">'.__('Greyd.Forms', 'greyd_hub').'</a>'
		);
		return '<div class="message info">'.$message.'</div>';
	}

	return $block_content;
}

add_filter( 'render_block', '__greyd_render_form_block', 10, 2 );
