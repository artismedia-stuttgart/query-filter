<?php
/**
 * Greyd List Blocks.
 */
namespace greyd\blocks;
// namespace Greyd\Block;

use greyd\blocks\helper as helper;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in tp_management
if (class_exists("Greyd\Block\List_Block")) return;

new List_Block($config);
class List_Block {

	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if ( !function_exists('register_block_type') ) return;

		// set config
		$this->config = (object)$config;

		// setup
		$this->config->css_uri = plugin_dir_url(__FILE__);
		$this->config->js_uri = plugin_dir_url(__FILE__);

		// register blocks
		add_action( 'init', array($this, 'register_blocks'), 99 );

		// render
		add_action( 'init', array($this, 'init') );

	}
	public function init() {

		if (is_admin()) return;
		if ( !class_exists('greyd\blocks\render') ) return;

		// hook block rendering
		add_filter( 'greyd_blocks_render_block_data', array($this, 'render_block_data') );
		add_filter( 'greyd_blocks_render_block', array($this, 'render_block'), 10, 2 );
		add_filter( 'greyd_blocks_render_block_finally', array($this, 'render_block_finally'), 10, 2 );
	}
	
	/**
	 * Register the blocks
	 */
	public function register_blocks() {

		// frontend styles
		wp_register_style(
			'greyd-list-frontend-style',
			$this->config->css_uri.'style.css',
			array( ),
			GREYD_VERSION
		);

		// edtor script
		wp_register_script(
			'greyd-list-editor-script',
			$this->config->js_uri.'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// register list blocks
		register_block_type( 'greyd/list', array(
			'editor_script'  => 'greyd-list-editor-script',
			'editor_style'   => 'elegant-icons',
			'style'          => 'greyd-list-frontend-style'
		) );
		register_block_type( 'greyd/list-item', array(
			'editor_script'  => 'greyd-list-editor-script',
		) );

		// script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-list-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
		}

	}


	/**
	 * Greyd block rendering filter.
	 * Filters the block data before the block is rendered.
	 * Extends the core block rendering filter.
	 * https://developer.wordpress.org/reference/hooks/render_block_data/
	 * 
	 * @filter 'greyd_blocks_render_block_data'
	 * 
	 * @param object $block     parsed Block
	 * 
	 * @return object $block    parsed Block with altered Block Data
	 */
	public function render_block_data($block) {

		if ($block['blockName'] == 'greyd/list') {
			$inner = array();
			foreach ($block['innerBlocks'] as $inner_block) {
				$b = $inner_block;
				// debug($b);
				if ($b['blockName'] != 'greyd/list-item' && $b['blockName'] != 'greyd/list') {
					// set icon atts for group and box
					$b['attrs']['isListItem'] = array(
						'type' => isset($block["attrs"]["type"]) ? $block['attrs']['type'] : "web",
						'icon' => isset($block['attrs']['icon']['icon']) ? $block['attrs']['icon']['icon'] : ''
					);
				}
				array_push($inner, $b);

			}
			$block['innerBlocks'] = $inner;
			// debug($block);
		}

		return $block;

	}


	/**
	 * Hook Greyd block rendering.
	 * 
	 * @filter 'greyd_blocks_render_block'
	 * 
	 * @param array $content
	 *      @property string block_content     block content about to be appended.
	 *      @property array  html_atts         html wrapper attributes
	 *      @property string style             css styles
	 * @param array  $block             full block, including name and attributes.
	 * 
	 * @return array $rendered
	 *      @property string block_content    altered Block Content
	 *      @property string html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public function render_block($content, $block) {
		// debug("render list");

		$block_content = $content['block_content'];
		$html_atts     = $content['html_atts'];
		$style         = $content['style'];


		// list
		if ($block['blockName'] === 'greyd/list') {
			// debug($block["attrs"]);

			// selectors
			$list_id = "list_".uniqid();
			$selectors = (object)array(
				'ul' => 	 "#".$list_id."",
				'li' => 	 "#".$list_id." > *",
				'icon' => 	 "#".$list_id." > li > .list_icon",
				'content' => "#".$list_id." > li > .list_content"
			);

			// styles
			$wrapper_style    = "";
			$li_style         = "";
			$icon_style       = "";
			$responsive_style = "";

			/**
			 * Get Grid values with Layout function
			 * @since 1.4.4
			 */
			// if ( method_exists( '\Greyd\Layout\Enqueue', 'get_breakpoints' ) ) {
			// 	$grid = \Greyd\Layout\Enqueue::get_breakpoints();
			// } else {
			// 	$grid = \greyd\blocks\deprecated\Functions::get_breakpoints();
			// }
			$grid = \greyd\blocks\layout\Enqueue::get_breakpoints();

			// fullwidth - deprecated
			// $li_style .= "width: max-content; "; // fullwidth: 100%
			// gap - moved
			if (isset($block["attrs"]["gap"])) {
				$block["attrs"]["layout"] = array( "gap" => $block["attrs"]["gap"] );
			}

			// new text color
			if (isset($block["attrs"]["color"]) && $block["attrs"]["color"] != '') {
				$color = $block["attrs"]['color'];
				if (strpos($color, "color-") === 0) $color = "var(--".str_replace('-', '', $color).")";
				/**
				 * Set var(--text-color) to use for outline color
				 * @since 2.2.0
				 */
				$li_style .= "color: ".$color."; --text-color: ".$color."; ";
			}

			if ( !isset($block["attrs"]["type"]) ) $block["attrs"]["type"] = "web";

			if ( $block["attrs"]["type"] != "" ) {
				if ($block["attrs"]["type"] == 'web' || $block["attrs"]["type"] == 'num') {
					$attrs_web = array(
						"style" => "disc",
						"position" => "left",
						"align_y" => "",
					);
					if (isset($block["attrs"]["web"])) $attrs_web = array_merge($attrs_web, $block["attrs"]["web"]);
					if (isset($attrs_web["style"])) $wrapper_style .= "list-style: ".$attrs_web["style"]."; ";

					// position
					$getPositionCSS = function($selectors, $pos) {
						if ($pos == 'right') {
							return  $selectors->li." { direction: rtl; } ".
									$selectors->content." { direction: ltr; } ";
						}
						return "";
					};
					$position = self::split_style_obj($attrs_web, array( 'name' => 'position' ));
					// debug($position);
					if (isset($position['position'])) $responsive_style .= $getPositionCSS($selectors, $position['position']);
					if (isset($position['responsive'])) {
						foreach (array('lg', 'md', 'sm') as $i) {
							if (isset($position['responsive'][$i]['position'])) {
								$responsive_style .= "@media (max-width: ".($grid[$i] - 0.02 )."px) { ".$getPositionCSS($selectors, $position['responsive'][$i]['position'])."} ";
							}
						};
					}
					// align
					$getAlignCSS = function($selectors, $align) {
						$getAlign = function($align) {
							if ($align == "start") return "text-top";
							else if ($align == "center") return "middle";
							if ($align == "end") return "text-bottom";
							return "top";
						};
						return $selectors->content." { vertical-align: ".$getAlign($align)."; } ";
					};
					$align_y = self::split_style_obj($attrs_web, array( 'name' => 'align_y' ));
					// debug($align_y);
					if (isset($align_y['align_y'])) $responsive_style .= $getAlignCSS($selectors, $align_y['align_y']);
					if (isset($align_y['responsive'])) {
						foreach (array('lg', 'md', 'sm') as $i) {
							if (isset($align_y['responsive'][$i]['align_y'])) {
								$responsive_style .= "@media (max-width: ".($grid[$i] - 0.02 )."px) { ".$getAlignCSS($selectors, $align_y['responsive'][$i]['align_y'])."} ";
							}
						};
					}
				}
				if ($block["attrs"]["type"] == 'icon' || $block["attrs"]["type"] == 'img') {
					$attrs_icon = array(
						"icon" => "",
						"url" => "",
						"id" => -1,
						"color" => "",
						"size" => "20px",
						"margin" => "10px",
						"position" => "left",
						"align_y" => "start",
						"align_x" => "start",
					);
					if (isset($block["attrs"]["icon"])) $attrs_icon = array_merge($attrs_icon, $block["attrs"]["icon"]);

					$wrapper_style .= "list-style: none; ";
					$li_style .= "display: flex; ";

					// position
					$getPositionCSS = function($selectors, $pos, $margin) {
						$margin = helper::get_spacing_preset_css_var( $margin );
						if ($pos == 'left') {
							return  $selectors->li." { flex-direction: row; } ".
									$selectors->icon." { margin-right: ".$margin."; } ";
						}
						else if ($pos == 'right') {
							return  $selectors->li." { flex-direction: row-reverse; } ".
									$selectors->icon." { margin-left: ".$margin."; } ";
						}
						else if ($pos == 'top') {
							return  $selectors->li." { flex-direction: column; } ".
									$selectors->icon." { margin-bottom: ".$margin."; } ";
						}
						else if ($pos == 'bottom') {
							return  $selectors->li." { flex-direction: column-reverse; } ".
									$selectors->icon." { margin-top: ".$margin."; } ";
						}
					};
					$position = self::split_style_obj($attrs_icon, array( 'name' => 'position' ));
					// debug($position);
					if (isset($position['position'])) $responsive_style .= $getPositionCSS($selectors, $position['position'], $attrs_icon['margin']);
					if (isset($position['responsive'])) {
						foreach (array('lg', 'md', 'sm') as $i) {
							if (isset($position['responsive'][$i]['position'])) {
								$responsive_style .= "@media (max-width: ".($grid[$i] - 0.02 )."px) { ".$getPositionCSS($selectors, $position['responsive'][$i]['position'], $attrs_icon['margin'])."} ";
							}
						};
					}
					// align
					$getAlignCSS = function($selectors, $align, $icon_size) {
						$getAlign = function($align) {
							if ($align == "first") return "start";
							if ($align == "start") return "start";
							if ($align == "center") return "center";
							if ($align == "end") return "end";
							return $align;
						};
						$custom_line_height = "var(--wp--custom--line-height--normal)";
						if ( ! \Greyd\Helper::is_greyd_classic() ) {
							$theme_custom_line_height = wp_get_global_styles( array( 'typography', 'lineHeight' ), array( 'block_name'=>'core/paragraph') ) ?? $custom_line_height;
							if ( $theme_custom_line_height && is_string($theme_custom_line_height) ) $custom_line_height = $theme_custom_line_height;
						}
						$content_style = ($align == "first") ? $selectors->content." { margin-top: calc( ( {$icon_size} - ( 1em * var(--lineHeight, {$custom_line_height}) ) ) / 2 ); } " : '';
						return $selectors->li." { align-items: ".$getAlign($align)."; } ".$content_style;
					};
					$align_direction = ($position['position'] == 'left' || $position['position'] == 'right') ? 'align_y' : 'align_x';
					// debug($align);
					if (isset($attrs_icon[$align_direction])) $responsive_style .= $getAlignCSS($selectors, $attrs_icon[$align_direction], $attrs_icon['size']);
					if (isset($attrs_icon['responsive'])) {
						foreach (array('lg', 'md', 'sm') as $i) {
							$dir = (!isset($position['responsive'][$i]['position']) || $position['responsive'][$i]['position'] == 'left' || $position['responsive'][$i]['position'] == 'right') ? 'align_y' : 'align_x';
							if (isset($attrs_icon['responsive'][$i][$dir])) {
								$responsive_style .= "@media (max-width: ".($grid[$i] - 0.02 )."px) { ".$getAlignCSS($selectors, $attrs_icon['responsive'][$i][$dir], $attrs_icon['size'])."} ";
							}
						};
					}
					// color
					if ($block["attrs"]["type"] == 'icon') {
						if ( !empty($attrs_icon['color']) ) {
							$color = $attrs_icon['color'];
							if (strpos($color, "color-") === 0) $color = "var(--".str_replace('-', '', $color).")";
							$icon_style .= "color: ".$color."; ";
						}
						$icon_style .= "font-size: ".$attrs_icon['size']."; ";
					}
					// size
					if ($block["attrs"]["type"] == 'img') {
						$icon_size = $attrs_icon['size'];
						$icon_src = $attrs_icon['url'];
						$icon_style .= "width: ".$icon_size."; min-width: ".$icon_size."; height: ".$icon_size."; ";
						$icon_style .= "background: url(".$icon_src."); background-repeat: no-repeat; background-position: center; background-size: contain; ";
					}
				}
			}
			else {
				$wrapper_style .= "list-style: none; ";
			}

			// style
			$list_style = ""; //"#".$list_id." { margin-left: var(--indent, auto); } ";
			$list_style .= $selectors->ul." { ".$wrapper_style." } ";
			if ($li_style != "") 	  $list_style .= $selectors->li." { ".$li_style." } ";
			if ($icon_style != "") 	  $list_style .= $selectors->icon." { ".$icon_style." } ";
			if ($responsive_style != "") $list_style .= $responsive_style;
			// more responsive styles
			if (!isset($block["attrs"]["layout"])) $block["attrs"]["layout"] = array();
			// gap
			$layout_gap = self::split_style_obj($block["attrs"]["layout"], array( 'name' => 'gap', 'rename' => 'margin-top', 'default' => '10px' ));
			$list_style .= helper::compose_css( array( '' => $layout_gap ), $selectors->li."", false );
			// indent
			$layout_indent = self::split_style_obj($block["attrs"]["layout"], array( 'name' => '--indent', 'rename' => '--indent', 'default' => '20px' ));
			$list_style .= helper::compose_css( array( '' => $layout_indent ), $selectors->ul."", false );

			// render
			\Greyd\Enqueue::add_custom_style($list_style);

			$html_atts['id'] = $list_id;
			// $html_atts['class'] = 'greyd_list';

		}
		if ($block['blockName'] === 'greyd/list-item') {
			// debug($block['blockName']);
			// debug($block);
			// debug(htmlspecialchars($block_content));

			if ($block_content != "") {
				$item_style = '';
				// indent
				$indent = isset($block["attrs"]["indent"]) ? $block["attrs"]["indent"] : 0;

				$base_margin = !empty($block["attrs"]["type"]) ? "20px" : "0px";

				if ( !isset($block["attrs"]["type"]) ) $block["attrs"]["type"] = "web";

				if ($block["attrs"]["type"] == 'web' || $block["attrs"]["type"] == 'num') {
					if ($indent == 0) $item_style .= 'margin-left: '.$base_margin.'; ';
					else $item_style .= 'margin-left: calc('.$base_margin.' + '.$indent.' * var(--indent)); ';
				}
				else if ($indent > 0) $item_style .= 'margin-left: calc('.$indent.' * var(--indent)); ';
				// overrides
				if (isset($block["attrs"]["custom"]) && $block["attrs"]["custom"] == true) {
					// list style
					if (isset($block["attrs"]["override"]["style"])) 
						$item_style .= 'list-style: '.$block["attrs"]["override"]["style"].'; ';
					// image
					if (isset($block["attrs"]["override"]["url"]) && $block["attrs"]["override"]["url"] != "") {
						$url = $block["attrs"]["override"]["url"];
						$block_content = str_replace('class="list_icon"', 'class="list_icon" style="background-image: url('.$url.');"', $block_content);
					}
				}

			
				/**
				 * Check if an icon is set and modify the block_content to add aria-hidden="true"
				 * to exclude it from screenreaders
				 * 
				 * 
				 * @since 2.12.0
				 */
	
				if ( isset($block['attrs']['icon']) && $block['attrs']['icon'] != "" ) {
					$block_content = preg_replace(
						'/<span([^>]*\sclass="[^"]*\blist_icon\b[^"]*")((?!aria-hidden)[^>]*)>/',
						'<span$1 aria-hidden="true"$2>',
						$block_content
					);
				}

				if ($item_style != '') $block_content = str_replace('<li>', '<li style="'.$item_style.'">', $block_content);
			}
		}

		// debug($block_content);

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);

	}


	/**
	 * Greyd block rendering filter.
	 * Filters the block_content again after Wrapper and custom Styles are rendered.
	 * 
	 * @filter 'greyd_blocks_render_block_finally'
	 * 
	 * @param string $block_content     block content about to be appended.
	 * @param array  $block             full block, including name and attributes.
	 * 
	 * @return string $block_content    altered Block Content
	 */
	public function render_block_finally($block_content, $block) {

		// group and box as listitems
		if ($block['blockName'] === 'core/group' || $block['blockName'] === 'greyd/box') {

			if (isset($block['attrs']['isListItem'])) {
				$item_style = '';
				if ($block["attrs"]['isListItem']["type"] == 'web' || $block["attrs"]['isListItem']["type"] == 'num')
					$item_style .= 'style ="margin-left: 20px;"';
				$iconclass = $block['attrs']['isListItem']['type'] == 'icon' ? $block['attrs']['isListItem']['icon'] : '';
				$block_content = '<li '.$item_style.'>
					<span class="list_icon '.$iconclass.'">
					</span><span class="list_content">'.$block_content.'</span>
				</li>';
			}

		}
		else if ($block['blockName'] === 'greyd/list-item') {

			// don't render if there is no content
			if (strpos($block_content, '<span class="list_content"><p></p></span>') !== false ||
				strpos($block_content, '<span class="list_content"></span>') !== false) {
				$block_content = "";
			}

		}

		return $block_content;

	}


	/**
	 * Split style object in parts.
	 * 
	 * @see greyd/list block. So far only used there.
	 * 
	 * @param array $styleObj   All default, hover & responsive styles.
	 * @param array $attribute  Attribute settings:
	 *      @property string name     Name of the attribute to split.
	 *      @property string rename   Optional new name for the attribute.
	 *      @property string default  Optional default value for the attribute.
	 * 
	 * @return array All values of the attribute, including 'responsive', 'hover' & 'active'
	 */
	public static function split_style_obj($styleObj, $attribute) {
		$name = $attribute['name'];
		$rename = isset($attribute['rename']) ? $attribute['rename'] : $name;
		$default = isset($attribute['default']) ? $attribute['default'] : '';
		// debug($styleObj);

		$obj = array();
		if (isset($styleObj[$name])) $obj[$rename] = $styleObj[$name];
		if (isset($styleObj['responsive'])) {
			$responsive = array();
			foreach (array('lg', 'md', 'sm') as $i) {
				if (isset($styleObj['responsive'][$i][$name])) {
					$responsive[$i] = array();
					$responsive[$i][$rename] = $styleObj['responsive'][$i][$name];
				}
			}
			if (!empty($responsive)) $obj['responsive'] = $responsive;
		}
		if (isset($styleObj['hover'][$name])) {
			$obj['hover'] = array();
			$obj['hover'][$rename] = $styleObj['hover'][$name];
		}
		if (isset($styleObj['active'][$name])) {
			$obj['active'] = array();
			$obj['active'][$rename] = $styleObj['active'][$name];
		}
		if (!isset($obj[$rename]) || $obj[$rename] == '') $obj[$rename] = $default;

		// debug($obj);
		return $obj;
	}

}
