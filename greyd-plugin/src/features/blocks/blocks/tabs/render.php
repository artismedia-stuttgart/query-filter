<?php
/**
 * Render tabs block.
 * 
 * @see https://developer.wordpress.org/reference/hooks/render_block/
 * 
 * @param string $block_content     pre-rendered Block Content
 * @param object $block             parsed Block
 * 
 * @return string $block_content    altered Block Content
 */

 use greyd\blocks\helper as helper;

function greyd_render_tabs_block( $block_content, $block ) {

	if ($block['blockName'] !== 'greyd/tabs') return $block_content;

	$tabs          = isset($block['innerBlocks']) ? $block['innerBlocks'] : [];
	$icon_position = isset($block['attrs']['iconPosition']) ? $block['attrs']['iconPosition'] : '';
	$html = '';


	foreach ($tabs as $index => $tab) {

		if (!isset($tab['attrs']['title'])) {
			continue;
		} else {
			$title = $tab['attrs']['title'];
		}

		$icon_normal = isset($tab['attrs']['iconNormal']) ? $tab['attrs']['iconNormal'] : '';
		$icon_active= isset($tab['attrs']['iconActive']) ? $tab['attrs']['iconActive'] : '';

		$unique_id = isset($tab['attrs']['uniqueId']) ? $tab['attrs']['uniqueId'] : '';

		// make button class
		$extraClass = "";
		if ( isset($block['attrs']['className']) ) {
			if ( strpos($block['attrs']['className'], 'is-style-prim') !== false ) {
				$extraClass = "button is-style-prim";
			}
			else if ( strpos($block['attrs']['className'], 'is-style-sec') !== false ) {
				$extraClass = "button is-style-sec";
			}
			else if ( strpos($block['attrs']['className'], 'is-style-trd') !== false ) {
				$extraClass = "button is-style-trd";
			}
		}

		$tab_atts = array(
			"class" => array(
				"greyd_tab",
				$index == 0 ? "is-active" : "",
				$extraClass
			),
			"aria-selected" => "false",
			"type" => "button",
			"role" => "tab",
			'aria-controls' => "tabpanel_".$unique_id,
			'id' => "tab_".$unique_id
		);

		if ($index === 0) $tab_atts["aria-selected"] = "true";
		if ($index !== 0) $tab_atts["tabindex"] = "-1";

		$icon_active_atts = array(
			'class' => array(
				'icon',
				'icon-active',
				$icon_active
			),
			"aria-hidden" => 'true',
		);
		$icon_normal_atts = array(
			'class' => array(
				'icon',
				'icon-normal',
				$icon_normal
			),
			"aria-hidden" => 'true',
		);


		$icon = "<span ".helper::implode_html_attributes($icon_normal_atts)."></span>
		<span ".helper::implode_html_attributes($icon_active_atts)."></span>";

		$html_atts = helper::implode_html_attributes($tab_atts);

		$html .= "<button {$html_atts}>
			". ($icon_position === 'hasiconleft' ? $icon: '') ."
			<span>{$title}</span>
			". ($icon_position === '' ? $icon : '') ."
		</button>";
	}

	$block_content = str_replace(
		array(
			'<div class="tabs" role="tablist"></div>',
			'<div class="tabs" role="tablist">{{greyd_tabs}}</div>'
		),
		'<div class="tabs" role="tablist">' . $html . '</div>',
		$block_content
	);

	return $block_content;
}
add_filter( 'render_block', 'greyd_render_tabs_block', 10, 2 );