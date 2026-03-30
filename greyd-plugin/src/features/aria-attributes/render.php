<?php
/**
 * Greyd Aria Attributes render callback.
 * 
 * @since 2.17.0
 */
namespace greyd\aria_attributes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'add_filter' ) ) {
	// Add aria attributes to allowed HTML tags
	add_filter( 'wp_kses_allowed_html', 'greyd\aria_attributes\filter_allowed_html_tags', 98, 2 );
}

function filter_allowed_html_tags( $html, $context ) {

	if ( $context !== 'post' ) return $html;

	// Ensure $html is an array before processing
	if ( ! is_array( $html ) ) {
		return $html;
	}

	$aria_attributes = array(
		'aria-label' => true,
		'aria-labelledby' => true,
		'aria-describedby' => true,
		'aria-hidden' => true,
	);

	foreach ( $html as $tag => $attributes ) {
		// Guard: attributes must be an array for array_merge
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}
		$html[$tag] = array_merge( $attributes, $aria_attributes );
	}

	return $html;
}

/**
 * Helper function to apply ARIA attributes to a WP_HTML_Tag_Processor
 * 
 * @param \WP_HTML_Tag_Processor $processor The HTML processor
 * @param array                  $aria_attrs The ARIA attributes to apply
 * 
 * @return string Modified HTML content
 */
function apply_aria_attributes_to_processor( $processor, $aria_attrs ) {
	// Apply ARIA attributes to the found element
	foreach ($aria_attrs as $key => $val) {
		if (in_array($key, ['aria-label', 'aria-labelledby', 'aria-describedby', 'aria-hidden'])) {
			if (is_bool($val)) {
				$val = $val ? 'true' : 'false';
			}
			$processor->set_attribute($key, $val);
		}
	}

	// if aria-labelledby is set, aria-label should not be rendered
	if ( isset($aria_attrs['aria-labelledby']) && !empty($aria_attrs['aria-labelledby']) ) {
		$processor->remove_attribute( 'aria-label' );
	}

	return $processor->get_updated_html();
}

/**
 * Render blocks
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 * 
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 * 
 * @return string $block_content    altered Block Content
 */
function render_block( $block_content, $block ) {

	// early escape
	if (
		! $block
		|| $block['blockName'] === 'core/null'
		|| ! isset( $block["attrs"] )
		|| ! is_array( $block["attrs"] )
		|| ! isset( $block["attrs"]["greydAriaAttributes"] )
		|| empty( $block["attrs"]["greydAriaAttributes"] )
	) {
		return $block_content;
	}

	$aria_attrs = $block["attrs"]["greydAriaAttributes"];
	$block_name = $block['blockName'] ?? '';

	// Use block-specific handlers for complex blocks
	if ( $block_name === 'core/post-template' ) { 
		return render_block_aria_attributes( $block_content, $block, $aria_attrs, [
			'.greyd-posts-slider',
			'.query-pages-wrapper', 
			'.query-page', 
			'.wp-block-post-template', 
			'ul'
		] );
	}
	else if ( $block['blockName'] === 'core/button' ) {
		return render_block_aria_attributes( $block_content, $block, $aria_attrs, [ 'a' ] );
	}
	else if ( $block['blockName'] === 'greyd/search-input' ) {
		return render_block_aria_attributes( $block_content, $block, $aria_attrs, [ 'input' ] );
	}
	else if ( $block['blockName'] === 'greyd/search-submit' ) {
		return render_block_aria_attributes( $block_content, $block, $aria_attrs, [ 'button' ] );
	}

	// Default handler for other blocks
	return render_default_aria_attributes( $block_content, $block, $aria_attrs );
}

/**
 * Generic helper function to render ARIA attributes for blocks with priority-based element selection
 * TODO: Add a way to include nested attributes for example in the post slider for each slider element
 * 
 * @param string $block_content The block content
 * @param array  $block         The full block
 * @param array  $aria_attrs    The ARIA attributes
 * @param array  $priority_list Array of class names or tag names to search for in priority order
 * 
 * @return string Modified block content
 */
function render_block_aria_attributes( $block_content, $block, $aria_attrs, $priority_list ) {
	
	if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
		// Fallback for older WordPress versions
		return render_default_aria_attributes( $block_content, $block, $aria_attrs );
	}

	$processor = new \WP_HTML_Tag_Processor( $block_content );
	$target_found = false;
	
	// Try each element in the priority list until we find one
	foreach ( $priority_list as $target ) {
		// Reset processor to start from beginning
		$processor = new \WP_HTML_Tag_Processor( $block_content );
		
		// Check if target is a class name (e.g. '.my-class')
		if ( strpos( $target, '.' ) === 0 ) {
			$target = substr( $target, 1 );
			if ( $processor->next_tag( array( 'class_name' => $target ) ) ) {
				$target_found = true;
				break;
			}
		}
		// Check if target is an id (e.g. '#my-id')
		elseif ( strpos( $target, '#' ) === 0 ) {
			$target = substr( $target, 1 );
			if ( $processor->next_tag( array( 'id' => $target ) ) ) {
				$target_found = true;
				break;
			}
		}
		// It's a tag name (e.g. 'ul')
		else {
			
			if ( $processor->next_tag( $target ) ) {
				$target_found = true;
				break;
			}
		}
	}

	if ( $target_found ) {
		$block_content = apply_aria_attributes_to_processor( $processor, $aria_attrs );
	}

	return $block_content;
}

/**
 * Default ARIA attributes renderer for other blocks
 * 
 * @param string $block_content The block content
 * @param array  $block         The full block
 * @param array  $aria_attrs    The ARIA attributes
 * 
 * @return string Modified block content
 */
function render_default_aria_attributes( $block_content, $block, $aria_attrs ) {
	
	// Use WP_HTML_Tag_Processor if available
	if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content );
		
		// Find the first tag to apply attributes to
		if ( $processor->next_tag() ) {
			$block_content = apply_aria_attributes_to_processor( $processor, $aria_attrs );
		}
	}
	// Fallback: Add all aria attributes using regex
	else {
		$aria_str = '';
		foreach ($aria_attrs as $key => $val) {
			if (is_bool($val)) {
				$val = $val ? 'true' : 'false';
			}
			$aria_str .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
		}
		$block_content = preg_replace(
			'/<([a-zA-Z][a-zA-Z0-9]*)([^>]*?)>/',
			'<$1$2' . $aria_str . '>',
			$block_content,
			1
		);
	}

	return $block_content;
}

// Hook into block rendering
add_filter( 'render_block', 'greyd\aria_attributes\render_block', 11, 2 );
add_filter( 'greyd_render_block_post_template', 'greyd\aria_attributes\render_block', 10, 2 );

