<?php
/**
 * Render dynamic image
 *
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 *
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 *
 * @return string $block_content    altered Block Content
 */
function greyd_render_dynamic_image( $block_content, $block ) {

	if ( $block['blockName'] !== 'greyd/image' ) {
		return $block_content;
	}

	/**
	 * Support transformed vc image dvalue
	 * When vc_icons shortcodes with a dynamic value are being transformed into
	 * blocks, the previous dynamic value only contains an image-id, and not a
	 * full image-object. This usually sets the value like this:
	 *
	 * @example array( 0 => 42 )
	 * @example (int) 42
	 */
	if ( isset( $block['attrs']['image'] ) && is_array( $block['attrs']['image'] ) && isset( $block['attrs']['image'][0] ) ) {
		if ( is_numeric( $block['attrs']['image'][0] ) ) {
			$block['attrs']['image'] = array(
				'id' => intval( $block['attrs']['image'][0] ),
			);
		}
		else if ( is_string( $block['attrs']['image'][0] ) ) {
			$block['attrs']['image'] = array(
				'url' => $block['attrs']['image'][0],
			);
		}
	}
	else if ( isset( $block['attrs']['image'] ) && is_numeric( $block['attrs']['image'] ) ) {
		$block['attrs']['image'] = array(
			'id' => intval( $block['attrs']['image'] ),
		);
	}

	$image_element = '';
	$figcaption    = '';
	$image_id      = isset( $block['attrs']['image']['id'] ) ? intval( $block['attrs']['image']['id'] ) : -1;
	$image_url     = isset( $block['attrs']['image']['url'] ) ? $block['attrs']['image']['url'] : null;
	$image_alt     = "";

	// dynamic image url
	if (
		isset( $block['attrs']['image']['type'] ) &&
		$block['attrs']['image']['type'] == 'dynamic' &&
		isset( $block['attrs']['image']['tag'] ) &&
		! empty( $block['attrs']['image']['tag'] )
	) {
		// $_image_url = \greyd\blocks\deprecated\Functions::get_dynamic_url( $block['attrs']['image']['tag'], $block );
		if ( class_exists('\Greyd\Dynamic\Render_Blocks') ) {
			$_image_url = \Greyd\Dynamic\Render_Blocks::get_dynamic_url( $block['attrs']['image']['tag'], $block );

			/**
			 * Filter dynamic image alt text when url is from dynamic tag.
			 *
			 * @since 2.14.0
			 *
			 * @param string $image_alt       The current alt text (default empty).
			 * @param string $tag             The image dynamic tag.
			 * @param string $image_url       The filtered image url (get_dynamic_url).
			 * @param array $block            The rendered block.
			 *
			 * @return string
			 */
			$image_alt = apply_filters( 'greyd_get_dynamic_url_image_alt_text', $image_alt, $block['attrs']['image']['tag'], $_image_url, $block );
		}

		/**
		 * @since 1.8.4 we only replace the URL & ID when we have a valid
		 * URL this enables us to use the default image as a fallback
		 */
		if ( ! empty( $_image_url ) ) {
			$image_id  = -1;
			$image_url = $_image_url;
		}
	}

	// dynamic image id
	if ( $image_id < 1 && ! empty( $image_url ) ) {
		$image_id = attachment_url_to_postid( $image_url ); // returns 0 on failure
	}

	// if the image_url is still an ID, resolve it
	if ( $image_id < 1 && is_numeric( $image_url ) ) {
		$image_id = (int) $image_url;
		$image_url = wp_get_attachment_image_url( $image_url, 'full' );
	}

	// render image by ID
	if ( is_numeric( $image_id ) && $image_id > 0 ) {

		// if ( method_exists( 'Greyd\Helper', 'filter_post_id' ) ) {
		// 	$image_id = \Greyd\Helper::filter_post_id( $image_id, 'attachment' );
		// } else if ( method_exists( '\greyd\blocks\deprecated\Functions', 'filter_post_id' ) ) {
		// 	$image_id = \greyd\blocks\deprecated\Functions::filter_post_id( $image_id, 'attachment' );
		// }
		$image_id = \Greyd\Helper::filter_post_id( $image_id, 'attachment' );

		/**
		 * Set dynami image size from attributes.
		 * @since 2.1.0
		 */
		$image_size = isset($block['attrs']['sizeSlug']) ? $block['attrs']['sizeSlug'] : 'large';

		/**
		 * Check if image size is available, default to 'full'
		 */
		$image_meta = wp_get_attachment_metadata( $image_id );
		if ( !isset($image_meta["sizes"]) || !isset($image_meta["sizes"][$image_size]) ) $image_size = 'full';

		/**
		 * Filter dynamic image size.
		 *
		 * @since 1.3.9
		 * @see https://developer.wordpress.org/reference/functions/wp_get_attachment_image/
		 *
		 * @param string $size  A registered attachement image size (eg. 'small', 'thumbnail'). Defaults to 'large'.
		 * @param int $image_id The attachement ID.
		 * @param array $block  The rendered block.
		 *
		 * @return string
		 */
		$image_size = apply_filters( 'greyd_dynamic_image_size', $image_size, $image_id, $block );

		// $image_alt_text = \Greyd\Helper::get_attachment_text($image_id);
		// $image_alt_text = \greyd\blocks\deprecated\Functions::get_attachment_text( $image_id );
		$image_alt_text = \Greyd\Helper::get_attachment_text($image_id);

		/**
		 * Filter dynamic image alt text.
		 *
		 * @since 1.4.5
		 *
		 * @param string $image_alt_text  The current attachment alt text.
		 * @param int $image_id           The attachement ID.
		 * @param array $block            The rendered block.
		 *
		 * @return string
		 */
		$image_alt_text = apply_filters( 'greyd_dynamic_image_alt_text', $image_alt_text, $image_id, $block );

		$image_element = wp_get_attachment_image(
			$image_id,
			$image_size,
			false,
			array(
				'alt'   => $image_alt_text,
				// 'title' => $image_alt_text, // prevent accessiblity alerts ('redundant title attributes')
				'class' => 'wp-image-' . $image_id, // . ( isset( $block['attrs']['align'] ) ? ' align' . $block['attrs']['align'] : '' ),
			)
		);
	}
	// render image by url
	if ( empty( $image_element ) && ! empty( $image_url ) ) {
		/**
		 * Set alt text if filtered by dynamic url.
		 * @since 2.14.0
		 */
		$alt = !empty($image_alt) ? "'alt='".esc_html($image_alt)."'" : "";
		// $image_url     = \greyd\blocks\deprecated\Functions::filter_url( $image_url );
		$image_element = "<img src='{$image_url}' {$alt}>";
	}

	if ( empty( $image_element ) ) {
		return '';
	}

	/**
	 * @since 1.4.5 Add figcaption
	 */
	$figcaption = '';
	if ( isset( $block['attrs']['caption'] ) && ! empty( $block['attrs']['caption'] ) ) {
		$figcaption = '<figcaption>' . $block['attrs']['caption'] . '</figcaption>';
	}

	/**
	 * @since 1.8.6 Add download link
	 */
	$download_link = '';
	if ( ! empty( $image_url ) && isset( $block['attrs']['downloadLink'] ) && $block['attrs']['downloadLink']['enable'] ) {

		$download_text = isset($block['attrs']['downloadLink']['text']) && !empty($block['attrs']['downloadLink']['text']) ? esc_html( $block['attrs']['downloadLink']['text'] ) : 'Download';

		if ( isset( $block['attrs']['downloadLink']['showFileType'] ) && $block['attrs']['downloadLink']['showFileType'] ) {
			$attachment_filesize = size_format( filesize( get_attached_file( $image_id ) ) );
			$attachment_filetype = strtoupper( wp_check_filetype( $image_url )['ext'] );
			$download_text .= ' (' . $attachment_filetype. ', ' . $attachment_filesize . ')';
		}

		$download_link = sprintf(
			'<a href="%s" class="wp-block-greyd-image__download" download>%s</a>',
			esc_url( $image_url ),
			$download_text
		);
	}

	$core_spacing = isset( $block['attrs']['style'] ) ?  wp_style_engine_get_styles( $block['attrs']['style'] ) : '';

	$block_content = sprintf(
		'<figure style="%s" class="%s">%s</figure>',
		isset( $core_spacing['css'] ) ? $core_spacing['css'] : '',
		isset( $block['attrs']['align'] ) ? 'align' . $block['attrs']['align'] : '',
		$image_element . $figcaption . $download_link
	);

	$className = 'wp-block-image greyd-image';
	if ( isset( $block['attrs']['className'] ) ) {
		$className .= ' ' . $block['attrs']['className'];
	}

	// default objectFit
	if (
		isset( $block['attrs']['greydStyles'] )
		&& isset( $block['attrs']['greydStyles']['height'] )
		&& (
			! isset( $block['attrs']['greydStyles']['objectFit'] )
			|| empty( $block['attrs']['greydStyles']['objectFit'] )
		)
	) {
		$block['attrs']['greydStyles']['objectFit'] = 'cover';
	}

	$inline_style = '';
	/**
	 * @since 1.2.9 Add focalPoint
	 */
	if ( isset( $block['attrs']['focalPoint'] ) && is_array( $block['attrs']['focalPoint'] ) ) {

		// only for objectFit: cover
		if ( isset( $block['attrs']['greydStyles'] ) && isset( $block['attrs']['greydStyles']['objectFit'] ) && $block['attrs']['greydStyles']['objectFit'] === 'cover' ) {
			// inject style into block content
			$pos           = ( $block['attrs']['focalPoint']['x'] * 100 ) . '% ' . ( $block['attrs']['focalPoint']['y'] * 100 ) . '%';
			$inline_style .= 'object-position: '.$pos.'; ';
			// $block_content = str_replace( '<img ', '<img style="object-position: ' . $pos . '" ', $block_content );
		}
	}
	/**
	 * @since 1.7.0 aspect-ratio
	 */
	if ( isset( $block['attrs']['greydStyles'] ) && isset( $block['attrs']['greydStyles']['aspectRatio'] ) ) {
		if ( $block['attrs']['greydStyles']['aspectRatio'] === 'auto' ) {
			unset( $block['attrs']['greydStyles']['aspectRatio'] );
		}
		if ( $block['attrs']['greydStyles']['aspectRatio'] != 'custom' && isset( $block['attrs']['greydStyles']['height'] ) && !empty( $block['attrs']['greydStyles']['height'] ) ) {
			$inline_style .= 'width: auto; ';
			// $block_content = str_replace( '<img ', '<img style="width:auto" ', $block_content );
		}

		// debug( $block['attrs']['greydStyles'], true );

		// if ( $block['attrs']['greydStyles']['aspectRatio'] === 'manual' && isset( $block['attrs']['greydStyles']['customAspectRatio'] ) ) {


		// 	$block['attrs']['greydStyles']['aspectRatio'] = $block['attrs']['greydStyles']['customAspectRatio'];
		// 	unset( $block['attrs']['greydStyles']['customAspectRatio'] );
		// 	// $inline_style .= 'aspect-ratio: ' . $block['attrs']['greydStyles']['customAspectRatio'] . '; ';
		// }

		// debug( $block['attrs']['greydStyles'], true );
	}
	if ( $inline_style != '' ) {
		$block_content = str_replace( '<img ', '<img style="'.$inline_style.'" ', $block_content );
	}

	if ( ! empty( $block_content ) ) {

		/**
		 * @since 1.8.5 Replace class in figure.
		 */
		if ( preg_match( '/<figure(\s[^>]*?)?class="/', $block_content ) ) {
			$block_content = preg_replace( '/<figure(\s[^>]*?)?class="/', '<figure$1class="' . $className . ' ', $block_content );
		} else {
			$block_content = preg_replace( '/<figure/', '<figure class="' . $className . '"', $block_content );
		}
	}

	return $block_content;
}

add_filter( 'render_block', 'greyd_render_dynamic_image', 9, 2 );

/**
 * Adjust the block selector for the image block.
 * 
 * @since 2.8.0
 * 
 * @param string $selector
 * @param array $block
 * 
 * @return array
 */
function filter_greyd_image_styles_selector( $selector, $block ) {
	$selector .= ' img';
	return $selector;
}
add_filter( 'greyd_styles_block_selector-greyd/image', 'filter_greyd_image_styles_selector', 10, 2 );

/**
 * @filter Filter the block styles for greyd custom styles.
 * @since 2.8.0
 * 
 * @param array $greydStyles
 * @param array $block
 * 
 * @return array $greydStyles
 */
function filter_greyd_image_styles( $greydStyles, $block ) {
	
	$aspectRatioType = isset( $greydStyles['aspectRatio'] ) ? $greydStyles['aspectRatio'] : 'auto';
	
	// if ( $aspectRatioType === 'manual' && isset( $greydStyles['customAspectRatio'] ) ) {
		
	// 	$greydStyles['aspectRatio'] = $greydStyles['customAspectRatio'];
	// 	unset( $greydStyles['customAspectRatio'] );
	// 	// $inline_style .= 'aspect-ratio: ' . $greydStyles['customAspectRatio'] . '; ';
	// }

	if (
		isset( $greydStyles['responsive'] )
		&& is_array( $greydStyles['responsive'] )
	) {
		foreach ( $greydStyles['responsive'] as $bp => $bpStyles ) {

			$bpAspectRatioType = isset( $bpStyles['aspectRatio'] ) ? $bpStyles['aspectRatio'] : $aspectRatioType;

			// if ( $bpAspectRatioType === 'manual' && isset( $bpStyles['customAspectRatio'] ) ) {
			// 	$greydStyles['responsive'][$bp]['aspectRatio'] = $bpStyles['customAspectRatio'];
			// 	unset( $greydStyles['responsive'][$bp]['customAspectRatio'] );
			// }
		}
	}

	// debug( $greydStyles, true );

	return $greydStyles;
}
add_filter( 'greyd_styles_block_styles-greyd/image', 'filter_greyd_image_styles', 10, 2 );