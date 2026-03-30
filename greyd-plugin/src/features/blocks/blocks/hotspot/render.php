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
function greyd_render_hotspot_block( $block_content, $block ) {

	if ( $block['blockName'] !== 'greyd/hotspot-wrapper' ) return $block_content;

	if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) return $block_content;

	// get image attachment id
	$attachment_id = isset($block['attrs']['image']) && isset($block['attrs']['image']['id']) ? $block['attrs']['image']['id'] : null;
	if ( ! $attachment_id ) return $block_content;

	// get image html attributes
	$html_atts = greyd_get_image_html_atts( $attachment_id, $block );

	$tags = new \WP_HTML_Tag_Processor( $block_content );
	if (
		method_exists( $tags, 'next_tag' )
		&& method_exists( $tags, 'has_class' )
		&& method_exists( $tags, 'get_updated_html' )
	) {
		while ( $tags->next_tag( 'img' ) ) {
			if ( empty($tags->get_attribute( 'class' )) || $tags->has_class( 'hotspot-desktop-image' ) ) {
				foreach ( $html_atts as $key => $value ) {
					$tags->set_attribute( $key, $value );
				}
			}
		}
		$block_content = $tags->get_updated_html();
	}

	if (
		isset( $block['attrs']['customMobile'] )
		&& $block['attrs']['customMobile']
		&& isset( $block['attrs']['mobileImage'] )
		&& isset( $block['attrs']['mobileImage']['id'] )
	) {
		$mobile_image_id = $block['attrs']['mobileImage']['id'];
		$mobile_html_atts = greyd_get_image_html_atts( $mobile_image_id, $block );

		$tags = new \WP_HTML_Tag_Processor( $block_content );
		if (
			method_exists( $tags, 'next_tag' )
			&& method_exists( $tags, 'has_class' )
			&& method_exists( $tags, 'get_updated_html' )
		) {
			while ( $tags->next_tag( array( 'tag_name' => 'img', 'class_name' => 'hotspot-mobile-image' ) ) ) {
				foreach ( $mobile_html_atts as $key => $value ) {
					$tags->set_attribute( $key, $value );
				}
			}
			$block_content = $tags->get_updated_html();
		}
	}

	return $block_content;
}

function greyd_get_image_html_atts( $attachment_id, $block ) {

	/**
	 * Filter hotspot image size.
	 *
	 * @since 1.15.0
	 * @see https://developer.wordpress.org/reference/functions/wp_get_attachment_image/
	 *
	 * @param string $size  A registered attachement image size (eg. 'small', 'thumbnail'). Defaults to 'large'.
	 * @param int $attachment_id The attachement ID.
	 * @param array $block  The rendered block.
	 *
	 * @return string
	 */
	$image_size = apply_filters( 'greyd_hotspot_image_size', 'large', $attachment_id, $block );

	$image = wp_get_attachment_image_src( $attachment_id, $image_size, false );
	if ( ! $image ) return array();

	list( $src, $width, $height ) = $image;

	$html_atts = array();

	/**
	 * Filter hotspot image alt text.
	 *
	 * @since 1.15.0
	 *
	 * @param string $image_alt_text  The current attachment alt text.
	 * @param int $attachment_id           The attachement ID.
	 * @param array $block            The rendered block.
	 *
	 * @return string
	 */
	$html_atts['alt'] = apply_filters( 'greyd_hotspot_image_alt_text', \Greyd\Helper::get_attachment_text( $attachment_id ), $attachment_id, $block );

	/**
	 * Get image srcset and sizes.
	 */
	$image_meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $image_meta ) ) {
		$size_array = array( absint( $width ), absint( $height ) );
		$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $attachment_id );
		$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $attachment_id );

		if ( $srcset ) {
			$html_atts['srcset'] = $srcset;

			if ( $sizes ) {
				$html_atts['sizes'] = $sizes;
			}
		}
	}

	return $html_atts;
}

add_filter( 'render_block', 'greyd_render_hotspot_block', 10, 2 );