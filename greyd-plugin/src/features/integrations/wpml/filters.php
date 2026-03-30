<?php
/**
 * WPML extension for Greyd.Blocks.
 */
namespace Greyd\Blocks\Integrations\WPML;

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Decode strings inside 'dynamic_content' attribute.
 *
 * When post with Gutenberg blocks is being send for translation
 * WPML parses blocks to find translatable strings. The attribute
 * 'dynamic_content' is being saved urlencoded, which makes it
 * necessary to decode it first before beeing readable.
 *
 * @param array  $strings                 already found strings.
 * @param \WP_Block_Parser_Block $block   block being parsed.
 * 
 * @filter 'wpml_found_strings_in_block'
 * @see sitepress-multilingual-cms\addons\wpml-page-builders\classes\Integrations\Gutenberg\strings-in-block\class-collection.php
 */
function wpml_decode_dynamic_content_attribute( $strings, $block ) {
	if ( $block->blockName == "greyd/dynamic" ) {
		foreach ( $strings as $key => $value ) {
			$strings[$key]->value = urldecode( $value->value );
		}
		// debug($strings);
	}
	return $strings;
}

add_filter( 'wpml_found_strings_in_block', __NAMESPACE__ . '\wpml_decode_dynamic_content_attribute', 10, 2 );

/**
 * Encode strings inside 'dynamic_content' attribute.
 * 
 * @param \WP_Block_Parser_Block $block    block being saved.
 * @param array $string_translations       array with string translations for current String Package.
 * @param string $lang                     language of translated post/block.
 * 
 * @filter 'wpml_update_strings_in_block'
 * @see sitepress-multilingual-cms\addons\wpml-page-builders\classes\Integrations\Gutenberg\strings-in-block\class-collection.php
 */
function wpml_encode_dynamic_content_attribute( $block, $string_translations, $lang ) {
	if ( $block->blockName == "greyd/dynamic" ) {
		if ( isset($block->attrs["dynamic_content"]) ) {
			foreach ( $block->attrs["dynamic_content"] as $key => $value ) {
				$block->attrs["dynamic_content"][$key]["dvalue"] = rawurlencode( $value["dvalue"] );
			}
		}
		// debug($block);
	}
	return $block;
}

add_filter( 'wpml_update_strings_in_block', __NAMESPACE__ . '\wpml_encode_dynamic_content_attribute', 10, 3 );

