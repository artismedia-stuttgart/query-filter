<?php
/**
 * Polylang extension for Greyd.Blocks.
 */
namespace Greyd\Blocks\Integrations\Polylang;

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Filters the list of blocks attributes to translate.
 *
 * @since 3.3
 * @since 3.6 Format changed from `array<string>` to `array<non-falsy-string, array<non-empty-string, array|true>>`.
 *
 * @param array $parsing_rules_attributes Rules for block attributes to translate.
 *               Array keys are block names for the 1st level, then attribute names for the next levels.
 *               Arrays values are `true` or an array containing sub attributes.
 *               Wildcards are allowed. Ex:
 *               array(
 *                   'block/name' => array(
 *                       'sub_key_1' => true,
 *                       'sub_key_2' => array(
 *                           'sub_sub_key_*' => true,
 *                       ),
 *                   ),
 *               )
 */
function pll_blocks_rules_for_attributes( $rules ) {

	$greyd_rules = array(
		'greyd/accordion-item' => array(
			'title' => true,
		),
		'greyd/button' => array(
			'content' => true,
		),
		'greyd/image' => array(
			'caption' => true,
			'downloadLink' => array(
				'text' => true,
			),
		),
		'greyd/list-item' => array(
			'content' => true,
		),
		'greyd/popover-button' => array(
			'content' => true,
		),
		'greyd/search-input' => array(
			'label' => true,
			'placeholder' => true,
		),
		'greyd/search-submit' => array(
			'content' => true,
		),
		'greyd/search-filter' => array(
			'label' => true,
			'placeholder' => true,
		),
		'greyd/search-sorting' => array(
			'label' => true,
			'placeholder' => true,
		),
		'greyd/tab' => array(
			'title' => true,
		),
		'greyd/dynamic' => array(
			'dynamic_content' => array(
				'*' => true,
			),
		),
		'greyd/search-datepicker' => array(
			'label' => true,
			'placeholder' => true,
			'ranges' => array(
				'label' => true,
			)
		),
		'greyd/search-filter-buttons' => array(
			'label' => true,
			'resetButton' => array(
				'label' => true,
			)
		)
	);
	
	$rules = array_merge( $rules, $greyd_rules );

	return $rules;
}

add_filter( 'pll_blocks_rules_for_attributes', __NAMESPACE__ . '\pll_blocks_rules_for_attributes', 10, 2 );

function pll_blocks_xpath_rules( $rules ) {

	$greyd_rules = array(
		'greyd/accordion-item' => array(
			'xpath' => '//*[@class="wp-block-greyd-accordion__title"]/span',
		),
		'greyd/button' => array(
			'xpath' => '//a|//a/span',
		),
		'greyd/list-item' => array(
			'xpath' => '//li/span',
		),
		'greyd/popover-button' => array(
			'xpath' => '//span',
		),
		'greyd/search-submit' => array(
			'xpath' => '//div[@class="input-outer-wrapper"]/button[@class="submitbutton"]/span[@style="flex: 1"]',
		),
	);

	$rules = array_merge( $rules, $greyd_rules );

	return $rules;
}

add_filter( 'pll_blocks_xpath_rules', __NAMESPACE__ . '\pll_blocks_xpath_rules', 10, 2 );
