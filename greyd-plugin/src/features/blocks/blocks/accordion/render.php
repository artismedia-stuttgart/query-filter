<?php

/**
 * Global variable to collect FAQPage structured data from all accordion blocks.
 * 
 * @since 2.18.1
 * 
 * @var array
 */
$GLOBALS['greyd_accordion_faq_data'] = isset( $GLOBALS['greyd_accordion_faq_data'] ) ? $GLOBALS['greyd_accordion_faq_data'] : array();

/**
 * Clear the greydStyles array for the accordion block to prevent double
 * rendering of styles, as they are already saved in the block content.
 * 
 * @since 2.8.0
 * 
 * @param array $greydStyles
 * @param array $block
 * 
 * @return array
 */
function filter_greyd_accordion_styles_object( $greydStyles, $block ) {
	return array();
}
add_filter( 'greyd_styles_block_styles-greyd/accordion', 'filter_greyd_accordion_styles_object', 10, 2 );

/**
 * Collect FAQPage structured data from accordion blocks.
 * 
 * Instead of outputting the JSON-LD script immediately, this function collects
 * all FAQPage data from multiple accordion blocks into a global variable.
 * The data is then output once at the end via wp_footer hook.
 *
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 *
 * @param string $block_content     The block content about to be appended.
 * @param array  $block             The full block, including name and attributes.
 *
 * @return string $block_content    altered Block Content
 */
function greyd_render_accordion_json_ld_script( $block_content, $block ) {
	// Initialize global variable if not already set
	if ( ! isset( $GLOBALS['greyd_accordion_faq_data'] ) ) {
		$GLOBALS['greyd_accordion_faq_data'] = array();
	}

	if ( $block['blockName'] !== 'greyd/accordion' ) {
		return $block_content;
	}

	$renderStructuredData = isset( $block['attrs']['renderStructuredData'] ) ? $block['attrs']['renderStructuredData'] : false;

	if ( ! $renderStructuredData ) {
		return $block_content;
	}

	// Collect FAQ items from this accordion block
	foreach ( $block['innerBlocks'] as $item ) {
		$question = trim( strip_tags( $item['innerHTML'] ) );

		$answer = '';
		if ( isset( $item['innerBlocks'] ) && is_array( $item['innerBlocks'] ) ) {
			foreach ( $item['innerBlocks'] as $innerBlock ) {
				if ( $innerBlock['blockName'] === 'core/paragraph' ) {
					$answer .= (new \WP_Block( $innerBlock ))->render();
				}
			}
		}

		if (
			empty( $answer )
			|| empty( trim( $answer ) )
			|| trim( $answer ) === '<p></p>'
			|| empty( $question )
			|| empty( trim( $question ) )
			|| trim( $question ) === '<p></p>'
		) {
			continue;
		}
		
		// Add to global collection instead of outputting immediately
		$GLOBALS['greyd_accordion_faq_data'][] = array(
			'@type' => 'Question',
			'name'  => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => trim( strip_tags( $answer ) ),
			),
		);
	}

	// Don't output anything here - return block content unchanged
	return $block_content;
}

add_filter( 'render_block', 'greyd_render_accordion_json_ld_script', 9, 2 );

/**
 * Output collected FAQPage structured data as a single JSON-LD script.
 * 
 * This function outputs all collected FAQPage data from all accordion blocks
 * on the page as a single JSON-LD script in the footer. This ensures Google
 * receives only one FAQPage schema per page, as required.
 * 
 * @since 2.18.1
 * 
 * @return void
 */
function greyd_output_accordion_faq_structured_data() {
	// Only output if we have collected FAQ data
	if ( ! isset( $GLOBALS['greyd_accordion_faq_data'] ) || empty( $GLOBALS['greyd_accordion_faq_data'] ) ) {
		return;
	}

	$faq = array(
		'@context' => 'https://schema.org',
		'@type'    => 'FAQPage',
		'mainEntity' => $GLOBALS['greyd_accordion_faq_data'],
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'greyd_output_accordion_faq_structured_data', 20 );

/**
 * Replace the uniqueId attribute with a true uniqueId.
 * @since 2.17.0
 * 
 * @param string $block_content
 * @param array $block
 * 
 * @return string
 */
function greyd_render_accordion_unique_id( $block_content, $block ) {

	if ( $block['blockName'] !== 'greyd/accordion-item' ) {
		return $block_content;
	}
	
	// replace the uniqueId attribute with a true uniqueId
	$uniqueId = isset( $block['attrs']['uniqueId'] ) ? $block['attrs']['uniqueId'] : '';
	if ( ! empty( $uniqueId ) ) {
		$block_content = str_replace(
			'-'. $uniqueId,
			'-'. wp_unique_id( $uniqueId . '-' ),
			$block_content
		);
	}

	return $block_content;
}

add_filter( 'render_block', 'greyd_render_accordion_unique_id', 10, 2 );