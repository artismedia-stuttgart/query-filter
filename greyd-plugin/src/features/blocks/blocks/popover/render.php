<?php

/**
 * Render Popover block via render callback
 * 
 * @since 2.4.2
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 * 
 * @param array $atts      Popover block attributes.
 * @param string $content  Popover block content saved in edit.js
 * 
 * @return string $block_content    altered Block Content
 */
function greyd_render_popover_block( $atts, $content ) {

	if ( empty($content) ) {
		return;
	}

	// do not render: popover without button (result from empty button)
	if ( strpos( $content, 'wp-block-greyd-popover-button' ) === false ) {
		return "";
	}

	// debug($atts);
	$uniqid = uniqid( 'popover-' ); // wp_unique_id( 'popover-' );
	$content = str_replace( 'popover-ID', $uniqid, $content );

	// old deprecation still saved in post content
	if ( strpos( $content, '<div class="wp-block-greyd-popover' ) === 1 ) {
		return $content;
	}

	if ( isset($atts['hideButton']) && $atts['hideButton'] ) {
		// add hidden classes
		$content = str_replace( 'wp-block-greyd-popover-button', 'wp-block-greyd-popover-button hidden-lg hidden-md hidden-sm hidden-xs', $content );
	}

	$hiddenDefault = array(
		'xs' => false,
		'sm' => false,
		'md' => false,
		'lg' => false
	);
	$hiddenAttribute = isset( $atts['hidden'] ) && $atts['hidden'] ? $atts['hidden'] : $hiddenDefault;
	$hidden = wp_parse_args( $hiddenAttribute, $hiddenDefault );

	$classNames = [];
	foreach ($hidden as $key => $value) {
		if ($value) {
			$classNames[] = 'hidden-' . $key;
		}
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'id' => isset($atts['anchor']) ? $atts['anchor'] : '',
			'class' => implode(' ', $classNames),
			'data-popover-name' => isset($atts['popoverName']) ? greyd\blocks\helper::snakecase($atts['popoverName']) : $uniqid,
			'data-popover-toggle' => isset($atts['openOnHover']) && $atts['openOnHover'] ? 'hover' : 'normal'
		)
	);

	$html_tag = "div";

	if ( isset($atts['isNavpoint']) && $atts['isNavpoint'] ) {
		$html_tag = "li";
	}

	return '<' . $html_tag . ' ' . $wrapper_attributes . '>' . $content . '</' . $html_tag . '>';
}

/**
 * Render Popover Popup block via render callback
 * 
 * @since 2.4.2
 * 
 * @param array $atts      Popover Popup block attributes.
 * @param string $content  Popover Popup block content saved in edit.js
 * 
 * @return string $block_content    altered Block Content
 */
function greyd_render_popover_popup_block( $atts, $content ) {

	// if ( empty($content) ) {
	// 	return;
	// }

	// old deprecation still saved in post content
	if ( strpos( $content, '<div class="wp-block-greyd-popover-popup' ) === 1 ) {
		return $content;
	}

	// get innerBlocks classnames
	$classNames = [
		isset( $atts['variation'] ) && !empty( $atts['variation'] ) ? 'is-variation-' . $atts['variation'] : 'is-variation-default',
		isset( $atts['position'] ) && !empty( $atts['position'] ) ? 'is-position-' . str_replace( ' ', '-', $atts['position'] ) : 'is-position-default',
	];
	if ( isset( $atts['greydStyles'] ) && isset( $atts['greydStyles']['--dialog-color'] ) ) {
		$classNames[] = 'has-text-color';
	}

	// get innerBlocks
	$innerBlocks = sprintf(
		'<div id="popover-ID" role="dialog" class="%1$s">'.
			'<button class="popover-close-button %2$s" tabindex="0" type="button" role="button" aria-expanded="false" aria-label="%3$s" aria-controls="popover-ID"></button>'.
			'%4$s'.
		'</div>'.
		'<div class="dialog-backdrop"></div>',
		implode(' ', $classNames),
		isset( $atts['closeButton'] ) ? $atts['closeButton'] : '',
		isset( $atts['closeButtonAriaLabel'] ) ? $atts['closeButtonAriaLabel'] : '',
		$content
	);

	// wrapper attributes
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'id' => isset($atts['anchor']) ? $atts['anchor'] : '',
			'class' => isset( $atts['greydClass'] ) ? $atts['greydClass'] : ''
		)
	);

	// wrapper markup
	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$innerBlocks
	);
}

/**
 * Render Popover Button block via render callback
 * 
 * @since 2.4.2
 * 
 * @param array $atts      Popover Button block attributes.
 * @param string $content  Popover Button block content saved in edit.js
 * 
 * @return string $block_content    altered Block Content
 */
function greyd_render_popover_button_block( $atts, $content ) {

	// old deprecation still saved in post content
	if ( strpos( $content, '<button' ) === 1 ) {
		return $content;
	}

	$innerBlocks  = '';
	$variation    = isset( $atts['variation'] ) && !empty( $atts['variation'] ) ? $atts['variation'] : '';
	$ariaLabel    = isset( $atts['ariaLabel'] ) ? $atts['ariaLabel'] : '';

	// classNames
	$classNames   = [];
	$greydClass   = isset( $atts['greydClass'] ) ? $atts['greydClass'] : wp_unique_id( 'burger_' );
	$classNames[] = $greydClass;

	// align support
	if ( isset( $atts['align'] ) && !empty( $atts['align'] ) ) {
		$classNames[] = 'align' . $atts['align'];
	}

	/**
	 * Burger Button
	 */
	if ( $variation === "burger" ) {

		$classNames[] = 'greyd-burger-btn';

		// enqueue extra styles
		if ( isset( $atts['burgerStyles'] ) && !empty( $atts['burgerStyles'] ) ) {
			\greyd\blocks\render::enqueue_custom_style(
				".{$greydClass}",
				$atts['burgerStyles']
			);
		}

		$innerBlocks = sprintf(
			'<span class="greyd-burger greyd-burger--%1$s %2$s">'.
				'<span class="greyd-burger-inner"></span>'.
			'</span>',
			isset( $atts['animation'] ) ? $atts['animation'] : 'squeeze',
			isset( $atts['shape'] ) ? $atts['shape'] : ''
		);
	}
	/**
	 * Default Button
	 */
	else {

		// do not render: empty button
		if ( empty( trim( $content ) ) ) {
			return "";
		}

		// check if button has an empty text
		if ( strpos( $content, '<span></span>' ) !== false ) {
			// if icon is empty or the option 'hideEmpty' is set, don't render the button
			if ( empty( $atts['icon']['content'] ) || (
				!empty( $atts['icon']['content'] ) &&
				isset( $atts['icon']['hideEmpty'] ) &&
				$atts['icon']['hideEmpty']
			) ) {
				// do not render: empty button or only icon
				return "";
			}
		}

		// classNames
		$buttonStyle = isset( $atts['buttonStyle'] ) ? $atts['buttonStyle'] : 'button-prim';
		if ( strpos( $buttonStyle, 'link-' ) !== false ) {
			$classNames[] = 'link';
		} else if ( strpos( $buttonStyle, 'button-' ) !== false ) {
			$classNames[] = 'button';
		}
		$classNames[] = 'is-style-' . str_replace( 'button-', '', $buttonStyle );
		if ( isset( $atts['size'] ) && !empty( $atts['size'] ) ) {
			$classNames[] = 'is-size-' . $atts['size'];
		}

		// enqueue extra styles
		if (
			isset( $atts['custom'] )
			&& $atts['custom']
			&& isset( $atts['customStyles'] )
			&& !empty( $atts['customStyles'] )
		) {
			\greyd\blocks\render::enqueue_custom_style(
				".{$greydClass}",
				$atts['customStyles'],
				array(
					'important' => true
				)
			);
		}

		$innerBlocks = $content;
	}

	// get block props
	$block_attributes = get_block_wrapper_attributes(
		array(
			'id' => isset($atts['anchor']) ? $atts['anchor'] : '',
			'class' => implode(' ', $classNames)
		)
	);

	/**
	 * Return markup
	 * 
	 * @since 2.4.2 Add type="button" to prevent form submission.
	 * This can happen when the block is placed inside a search form.
	 */
	return '<button ' . $block_attributes . ' tabindex="0" type="button" role="button" aria-expanded="false" aria-label="' . $ariaLabel . '" aria-controls="popover-ID">' . $innerBlocks . '</button>';
}