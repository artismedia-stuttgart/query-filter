<?php
/**
 * Greyd Animation render callback.
 * 
 * @since 1.6.0
 */
namespace greyd\animations;

use \greyd\blocks\helper as Helper;
use \greyd\blocks\render as Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'render_block', 'greyd\animations\render_block_with_greydAnim', 80, 2 );
	add_filter( 'render_block', 'greyd\animations\render_block_with_greydBackgroundAnim', 80, 2 );
}

/**
 * Render blocks with attribute 'greydAnim'
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 * 
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 * 
 * @return string $block_content    altered Block Content
 */
function render_block_with_greydAnim( $block_content, $block ) {

	// early escape
	if (
		! $block
		|| ! isset( $block["attrs"] )
		|| ! is_array( $block["attrs"] )
		|| ! isset( $block["attrs"]["greydAnim"] )
		|| empty( $block["attrs"]["greydAnim"] )
	) {
		return $block_content;
	}
	
	$anim = get_animation( $block["attrs"] );
	// debug( $anim );

	if ( !empty($anim['data']) ) {
		$html_attributes = Helper::implode_html_attributes( array( 'data' => $anim['data'] ) );
		$block_content = preg_replace(
			'/(<[^<]+?)( [\w-]+="[^"]+")?( ?\/?>)/',
			'$1$2 '.$html_attributes.'$3',
			$block_content,
			1 // only the first occurence
		);
	}

	if ( !empty($anim['styles']) ) {
		foreach( $anim['styles'] as $selector => $styles ) {
			// we enqueue it using the render class, to not have
			// styles duplicated on the page.
			Render::enqueue_custom_style( $selector, $styles );
		}
	}

	return $block_content;
}

/**
 * Render blocks with attribute 'greydBackgroundAnim'
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 * 
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 * 
 * @return string $block_content    altered Block Content
 */
function render_block_with_greydBackgroundAnim( $block_content, $block ) {

	// early escape
	if (
		! $block
		|| ! isset( $block["attrs"] )
		|| ! is_array( $block["attrs"] )
		|| ! isset( $block["attrs"]["greydBackgroundAnim"] )
		|| empty( $block["attrs"]["greydBackgroundAnim"] )
	) {
		return $block_content;
	}
	
	$anim = get_animation( $block["attrs"], 'greydBackgroundAnim' );
	// debug( $anim );

	if ( !empty($anim['data']) ) {
		$html_attributes = Helper::implode_html_attributes( array( 'data' => $anim['data'] ) );

		if ( strpos( $block_content, 'greyd-background' ) !== false ) {
			$block_content = preg_replace(
				'/(<div [^>]+?)(class=[\"\']greyd-background)/',
				'$1 '.$html_attributes.' $2',
				$block_content,
				1 // only the first occurence
			);
		}
		else if ( $block['blockName'] === 'core/cover' ) {


			/**
			 * Make sure the background image exists as a separate element in the block.
			 * @since 2.9.0
			 * 
			 * This is necessary because sometimes the background image is not part of the
			 * block itself, but rather set as a style attribute on the block element. This
			 * is used for parallax images. In this case, we need to make sure the image is
			 * actually part of the block, so we can animate it by setting the html attributes.
			 * 
			 * @see /wp-includes/blocks/cover.php
			 */
			if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {

				$processor = new \WP_HTML_Tag_Processor( $block_content );
				$processor->next_tag();
		
				$styles         = $processor->get_attribute( 'style' );

				// if style has 'background-image:url('...
				if ( $styles && strpos( $styles, 'background-image:url(' ) !== false ) {

					// get everything from 'background-image:url(' and after
					$background_image_style = substr( $styles, strpos( $styles, 'background-image:url(' ) );

					// remove this part from the original styles attribute
					$styles = str_replace( $background_image_style, '', $styles );
					$processor->set_attribute( 'style', $styles );
					$block_content = $processor->get_updated_html();

					$image_atts = array(
						'class'           => 'wp-block-cover__image-background',
						'data-object-fit' => 'cover',
						'style'           => $background_image_style,
					);

					if ( isset( $block["attrs"]['hasParallax'] ) && $block["attrs"]['hasParallax'] ) {
						$image_atts['class']                .= ' has-parallax';
					}

					if ( isset( $block["attrs"]['isRepeated'] ) && $block["attrs"]['isRepeated'] ) {
						$image_atts['class']                .= ' is-repeated';
					}
			
					if ( isset( $block["attrs"]['focalPoint'] ) ) {
						$object_position                     = round( $block["attrs"]['focalPoint']['x'] * 100 ) . '% ' . round( $block["attrs"]['focalPoint']['y'] * 100 ) . '%';
						$image_atts['data-object-position']  = $object_position;
						$image_atts['style']                .= 'object-position: ' . $object_position;
					}
		
					$image = '<div ' . Helper::implode_html_attributes( $image_atts ) . '></div>';
			
					/*
					 * Inserts the featured image between the (1st) cover 'background' `span` and 'inner_container' `div`,
					 * and removes eventual whitespace characters between the two (typically introduced at template level)
					 */
					$inner_container_start = '/<div\b[^>]+wp-block-cover__inner-container[\s|"][^>]*>/U';
					if ( 1 === preg_match( $inner_container_start, $block_content, $matches, PREG_OFFSET_CAPTURE ) ) {
						$offset  = $matches[0][1];
						$block_content = substr( $block_content, 0, $offset ) . $image . substr( $block_content, $offset );
					}
				}
			}
			
			$block_content = preg_replace(
				'/(<(?:img|video|div)[^>]+?)(class="[^"]*?wp-block-cover__(?:image|video)-background)/',
				'$1 '.$html_attributes.' $2',
				$block_content,
				1 // only the first occurence
			);
			
		}
	}

	if ( !empty($anim['styles']) ) {
		foreach( $anim['styles'] as $selector => $styles ) {
			// we enqueue it using the render class, to not have
			// styles duplicated on the page.
			Render::enqueue_custom_style( $selector, $styles );
		}
	}

	return $block_content;
}

/**
 * Get the animation attributes.
 * @param  array $block_atts   The block attributes.
 * @param  string $attribute   The attribute name to look for.
 * 
 * @return array @example:
 *      array(
 *          'data' => array( 'anim-action' => 'hide' ),
 *          'css' => '.gs_1234 { opacity: 1; }'
 *      )
 */
function get_animation( $block_atts, $attribute='greydAnim' ) {

	$anim   = $block_atts[ $attribute ];
	$data   = array(
		'anim-action'    => $anim['action'],
		'anim-event'     => $anim['event'],
		'anim-triggered' => 'false'
	);

	// action
	switch ( $anim['action'] ) {

		case 'hide':
		case 'show':
			if ( ! empty( $anim['preset'] ) ) {
				$data['anim-action'] = $anim['preset'];
			}
			$data = array_merge(
				$data,
				get_animation_preset_data( $data['anim-action'] )
			);
			break;

		case 'changeColor':

			$data['anim-from'] = '';
			$data['anim-to']   = '';
			$color      = isset( $anim['color'] ) ? $anim['color'] : '';
			$background = isset( $anim['background'] ) ? $anim['background'] : '';
			if ( !empty( $color ) ) {
				$data['anim-to']   .= 'color: ' . $color . ' !important;';
			}
			if ( !empty( $background ) ) {
				/**
				 * @since 2.4.0 if set as gradient, we need to set the property 'background-image'
				 */
				$property = strpos( $background, 'gradient' ) !== false ? 'background-image' : 'background-color';
				$data['anim-to']   .= $property . ': ' . $background . ' !important;';
			}

			break;

		case 'translateX':
		case 'translateY':
		case 'scale':
		case 'rotate':
			$unit = $anim['action'] == 'rotate' ? 'deg' : '';
			$data['anim-from'] = 'transform: ' . $anim['action'] . '(' . $anim['from'] . $unit . ');';
			$data['anim-to']   = 'transform: ' . $anim['action'] . '(' . $anim['to'] . $unit . ');';
			if ( isset( $anim['origin'] ) ) {
				$data['anim-from'] .= 'transform-origin: ' . $anim['origin'] . ';';
			}
			break;

		case 'filter':
			$unit = $anim['preset'] == 'blur' ? 'px' : '%';
			$data['anim-from'] = 'filter: ' . $anim['preset'] . '(' . $anim['from'] . $unit . ');';
			$data['anim-to']   = 'filter: ' . $anim['preset'] . '(' . $anim['to'] . $unit . ');';
			break;

		case 'custom':
			$data['anim-from'] = $anim['from'];
			$data['anim-to']   = $anim['to'];
			break;
	}
	
	// selector
	$selector = '[data-anim-action]';


	// for background animations, we need to add a unique selector,
	// because the background element is not the block itself.
	if ( $attribute == 'greydBackgroundAnim' ) {
		$data[ 'anim-id' ] = uniqid( 'anim-' );
		$selector .= '[data-anim-id="' . $data[ 'anim-id' ] . '"]';
	}
	else {
		// if the block has a custom class, we use that as selector
		if ( isset( $block_atts["greydClass"] ) &&
			!empty( $block_atts["greydClass"] ) &&
			isset( $block_atts['greydStyles'] ) &&
			!\Greyd\Helper::is_array_empty( $block_atts['greydStyles'] )
		) {
			$selector .= '.' . $block_atts["greydClass"];
		}
		// if the block has no custom class, we use a unique selector
		else {
			$data[ 'anim-id' ] = uniqid( 'anim-' );
			$selector .= '[data-anim-id="' . $data[ 'anim-id' ] . '"]';
		}
	}
	$selector_active = $selector . '[data-anim-triggered="true"]';

	// event
	switch ( $anim['event'] ) {

		case 'hover':
			// for background animations, we actually trigger the animation when
			// the direct parent is hovered.
			if ( $attribute == 'greydBackgroundAnim' ) {
				$selector_active = ':is(:hover, :focus-visible) > ' . $selector;
			}
			else {
				$selector_active = $selector . ':is(:hover, :focus-visible)';
			}
			break;

		case 'click':
			if ( $attribute == 'greydBackgroundAnim' ) {
				// for background animations, we actually trigger the animation when
				// the direct parent is clicked. So we need to make sure the parent
				// has a selector, that the JS can use.
				if ( ! isset( $block_atts["greydClass"] ) || empty( $block_atts["greydClass"] ) ) {
					$block_atts["greydClass"] = Helper::generate_greydClass();
				}
				$data['anim-event']  = 'parentClick';
				$data['anim-parent'] = '.' . $block_atts["greydClass"];
			}
			// everything else about click animations is handled by JS
			break;

		case 'parentHover':
			$data['anim-parent'] = $anim['parent'];
			$selector_active = $anim['parent'] . ':is(:hover, :focus-visible) ' . $selector;
			break;
			
		case 'parentClick':
			$data['anim-parent'] = $anim['parent'];
			// everything else about click animations is handled by JS
			break;

		case 'onScroll':
			$data['anim-start'] = isset($anim['start']) ? esc_attr( $anim['start'] ) : 50;
			$data['anim-reverse'] = isset($anim['reverse']) && $anim['reverse'] ? "true" : "false";
			break;

		case 'whileScroll':
			$data['anim-start'] = isset($anim['start']) ? intval($anim['start']) : 100;
			$data['anim-end']   = isset($anim['end']) ? intval($anim['end']) : 0;
			break;

		case 'isSticky':
			// silence is golden
			break;

		case 'parentSticky':
			$selector_active = '[data-anim-event="isSticky"][data-anim-triggered="true"] ' . $selector;
			break;

		/**
		 * @since 2.10.0
		 */
		case 'load':
			$data['anim-reverse'] = isset($anim['reverse']) && $anim['reverse'] ? "true" : "false";
			$anim_time = 200;
			if ( isset($anim['duration']) ) {
				$anim_time = $anim['duration'];
			}
			if ( isset($anim['delay']) ) {
				$anim_time += $anim['delay'];
			}
			$data['anim-time'] = $anim_time;
			break;
	}

	// styles
	$css        = $data['anim-from'];
	$css_active = $data['anim-to'];

	// presets do not need extra css
	if ( $anim['action'] == 'hide' && !empty($anim['preset']) && $anim['event'] != 'parentHover' && $attribute !== 'greydBackgroundAnim' ) {
		$css = $css_active = '';
	} else if ( $anim['action'] == 'show' && !empty($anim['preset']) && $anim['event'] != 'parentHover' && $attribute !== 'greydBackgroundAnim' ) {
		$css = $css_active = '';
	}

	// transition properties
	if ( $anim['event'] == 'onScroll' && $data['anim-reverse'] === "false" ) {
		// $css .= 'transition-duration: 0s; transition-delay: 0s;';
		if ( $anim['duration'] != 200 ) {
			$css_active .= 'transition-duration: ' . $anim['duration'] . 'ms;';
		}
		if ( $anim['delay'] != 0 ) {
			$css_active .= 'transition-delay: ' . $anim['delay'] . 'ms;';
		}
		if ( $anim['timing'] != 'ease' ) {
			$css_active .= 'transition-timing-function: ' . $anim['timing'] . ';';
		}
	}
	else {
		if ( $anim['duration'] != 200 ) {
			$css .= 'transition-duration: ' . $anim['duration'] . 'ms;';
		}
		if ( $anim['delay'] != 0 ) {
			$css .= 'transition-delay: ' . $anim['delay'] . 'ms;';
		}
		if ( $anim['timing'] != 'ease' ) {
			$css .= 'transition-timing-function: ' . $anim['timing'] . ';';
		}
	}

	// whilescroll does not need active css, because it is handled by JS
	if ( $anim['event'] == 'whileScroll' ) {
		$css_active = '';
	}
	
	// build css
	$styles = array();
	if ( !empty( $css ) ) {
		$styles[ $selector ] = $css;
	}
	if ( !empty( $css_active ) ) {
		$styles[ $selector_active ] = $css_active;
	}

	return array(
		'data' => $data,
		'styles' => $styles
	);
}


/**
 * Get animation preset data.
 * @param string $preset_name
 * @return array @example array( 'anim-from' => 'opacity: 0;', 'anim-to' => 'opacity: 1;' )
 */
function get_animation_preset_data( $preset_name ) {
	$presets = array(
		'show' => array(
			'anim-from' => 'opacity: 0;',
			'anim-to' => 'opacity: 1;',
		),
		'fadeIn' => array(
			'anim-from' => 'opacity: 0;',
			'anim-to' => 'opacity: 1;',
		),
		'fadeInUp' => array(
			'anim-from' => 'opacity: 0; transform: translateY(100px);',
			'anim-to' => 'opacity: 1; transform: translateY(0px);',
		),
		'fadeInDown' => array(
			'anim-from' => 'opacity: 0; transform: translateY(-100px);',
			'anim-to' => 'opacity: 1; transform: translateY(0px);',
		),
		'fadeInRight' => array(
			'anim-from' => 'opacity: 0; transform: translateX(100px);',
			'anim-to' => 'opacity: 1; transform: translateX(0px);',
		),
		'fadeInLeft' => array(
			'anim-from' => 'opacity: 0; transform: translateX(-100px);',
			'anim-to' => 'opacity: 1; transform: translateX(0px);',
		),
		'hide' => array(
			'anim-from' => 'opacity: 1;',
			'anim-to' => 'opacity: 0;',
		),
		'fadeOut' => array(
			'anim-from' => 'opacity: 1;',
			'anim-to' => 'opacity: 0;',
		),
		'fadeOutUp' => array(
			'anim-from' => 'opacity: 1; transform: translateY(0px);',
			'anim-to' => 'opacity: 0; transform: translateY(-100px);',
		),
		'fadeOutDown' => array(
			'anim-from' => 'opacity: 1; transform: translateY(0px);',
			'anim-to' => 'opacity: 0; transform: translateY(100px);',
		),
		'fadeOutRight' => array(
			'anim-from' => 'opacity: 1; transform: translateX(0px);',
			'anim-to' => 'opacity: 0; transform: translateX(100px);',
		),
		'fadeOutLeft' => array(
			'anim-from' => 'opacity: 1; transform: translateX(0px);',
			'anim-to' => 'opacity: 0; transform: translateX(-100px);',
		),
	);
	return isset( $presets[ $preset_name ] ) ? $presets[ $preset_name ] : null;
}
