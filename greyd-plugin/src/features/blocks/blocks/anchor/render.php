<?php

/**
 * Filter rendering of the anchor block.
 * 
 * @since 2.8.0 Added to support dynamic tags and proper sanitization.
 *
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 *
 * @return string $block_content    altered Block Content
 */
function greyd_render_anchor_target_block( $block_content, $block ) {

	if ( $block['blockName'] !== 'greyd/anchor' ) {
		return $block_content;
	}

	$name = isset( $block['attrs']['anchor'] ) ? $block['attrs']['anchor'] : null;

	if ( method_exists( '\Greyd\Dynamic\Render_Blocks', 'match_dynamic_tags' ) ) {
		$new_name = \Greyd\Dynamic\Render_Blocks::match_dynamic_tags( $block, $name );
		if ( ! empty( $new_name ) && $new_name !== $name ) {
			$name = $new_name;
		}
	}
	
	$name = rawurlencode( $name );

	if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
		$tags = new \WP_HTML_Tag_Processor( $block_content );
		if (
			method_exists( $tags, 'next_tag' )
			&& method_exists( $tags, 'has_class' )
			&& method_exists( $tags, 'get_updated_html' )
		) {
			if ( $tags->next_tag( array( 'class_name' => 'greyd-anchor-target' ) ) ) {
				$tags->set_attribute( 'id', $name );
				$block_content = $tags->get_updated_html();
			}
		}
	}

	return $block_content;
}
add_filter( 'render_block', 'greyd_render_anchor_target_block', 20, 2 );
