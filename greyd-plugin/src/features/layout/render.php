<?php
/**
 * Advanced Layout features rendering.
 * - render core/columns extensions
 * - render core/column extensions
 * - render greyd/box block
 * - Background feature
 */
namespace greyd\blocks\layout;
// namespace Greyd\Layout;

// old content-box depends on Theme vc\helper
// ::make_background
// ::get_shadow
use \vc\helper as Theme_Helper;

// can be used in future versions > 1.5.0
// use Greyd\Settings as Settings;
// use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Render($config);
class Render {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Holds the current settings for frontend
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;
		if (is_admin()) return;

		// set config
		$this->config = (object) $config;
		$this->config->css_uri = plugin_dir_url(__FILE__).'assets/css';
		$this->config->js_uri = plugin_dir_url(__FILE__).'assets/js';

		add_action( 'init', array($this, 'init') );
	}
	public function init() {

		if ( !class_exists('greyd\blocks\render') ) return;

		// get settings
		$this->settings = self::get_setting( array('site') );

		// frontend
		add_action( 'wp_footer', array($this, 'add_frontend_styles_footer'), 2 );
		
		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'render_block'), 10, 2 );

	}


	/**
	 * Add frontend block styles
	 * and script for Backgrounds
	 */
	public function add_frontend_styles_footer() {
		
		// frontend styles
		wp_register_style(
			'greyd-layout-frontend-style',
			function_exists( '\wp_process_style' ) ? $this->config->css_uri.'/classic/blocks.css' : $this->config->css_uri.'/blocks.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style('greyd-layout-frontend-style');

		// css animation styles
		wp_register_style(
			'greyd-layout-anims-style',
			$this->config->css_uri.'/css-anims.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style('greyd-layout-anims-style');

		// frontend script
		wp_register_script(
			'greyd-layout-backgrounds-script',
			$this->config->js_uri.'/frontend.js',
			array('jquery'),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script('greyd-layout-backgrounds-script');

		// define global greyd var
		wp_add_inline_script('greyd-layout-backgrounds-script', '
			var greyd = greyd || {};
			if (typeof greyd.setup === "undefined") greyd.setup = {};
			greyd.setup = {
				lazyload: "'.esc_js( isset($this->settings['lazyload']) ? $this->settings['lazyload'] : 'false' ).'",
				...greyd.setup
			};
		', 'before');

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
		// debug("render layout");

		if (isset($block["attrs"]["disable_element"]) && $block["attrs"]["disable_element"] == true) {
			
			return array(
				'block_content' => "",
				'html_atts' => [],
				'style' => ""
			);
		}

		$block_content = $content['block_content'];
		$html_atts     = $content['html_atts'];
		$style         = $content['style'];

		// explode css classes to make it easier to add classes.
		$html_atts['class'] = isset($html_atts['class']) && !empty($html_atts['class']) ? explode( ' ', $html_atts['class'] ) : array();

		// inline_css
		if (isset($block["attrs"]["inline_css"]) && $block["attrs"]["inline_css"] != "") {
			// debug($block);
			$style .= $block["attrs"]["inline_css"];
		}

		// Content box
		if ($block['blockName'] === 'greyd/box') {

			// render old content-box
			if (isset($block["attrs"]['textColor'])) {

				$cb_id = "cb_".uniqid();
				$cb_style = "";
				$background = "";
				$wrapper_atts = "";

				// link
				if (isset($block["attrs"]['link']) && !empty($block["attrs"]['link']['url'])) {
					$link = $block["attrs"]['link'];
					$target = $link['opensInNewTab'] ? "_blank" : "_self";
					$wrapper_atts = "style='cursor: pointer' onclick='window.open(\"".$link['url']."\", \"".$target."\");' title='".$link['title']."'";
				}
				// transition
				$transition = "";
				$transition_duration = 0.3;
				if (isset($block["attrs"]["hover"]["duration"]) && $block["attrs"]["hover"]["duration"] != "") {
					$transition_duration = $block["attrs"]["hover"]["duration"];
				}
				$transition_effect = 'ease';
				if (isset($block["attrs"]["hover"]["effect"]) && $block["attrs"]["hover"]["effect"] != "") {
					$transition_effect = $block["attrs"]["hover"]["effect"];
				}
				$transition = "transition-property: box-shadow, border, border-radius, background, color; will-change: box-shadow, border, border-radius, background, color; ";
				$transition .= "transition-duration: ".$transition_duration."s; transition-timing-function: ".$transition_effect."; ";

				// layout
				if (isset($block["attrs"]['height']) && $block["attrs"]['height'] != "") {
					$cb_style .= "#".$cb_id." .vc-content-box-content { min-height: ".$block["attrs"]['height']." } ";
				}
				if (isset($block["attrs"]['padding'])) {
					$padding  = "padding-top: ".$block["attrs"]["padding"]['top']."; ";
					$padding .= "padding-right: ".$block["attrs"]["padding"]['right']."; ";
					$padding .= "padding-bottom: ".$block["attrs"]["padding"]['bottom']."; ";
					$padding .= "padding-left: ".$block["attrs"]["padding"]['left']."; ";
					$cb_style .= "#".$cb_id." .vc-content-box-content { ".$padding." } ";
				}
				if (isset($block["attrs"]['margin'])) {
					$margin  = "margin-top: ".$block["attrs"]["margin"]['top']."; ";
					$margin .= "margin-right: ".$block["attrs"]["margin"]['right']."; ";
					$margin .= "margin-bottom: ".$block["attrs"]["margin"]['bottom']."; ";
					$margin .= "margin-left: ".$block["attrs"]["margin"]['left']."; ";
					$cb_style .= "#".$cb_id." { ".$margin." } ";
				}
				// text
				if (isset($block["attrs"]['textColor']) && $block["attrs"]["textColor"] != "") {
					$text_color = $block["attrs"]['textColor'];
					if (strpos($text_color, "color-") === 0) $text_color = "var(--".str_replace('-', '', $text_color).")";
					$cb_style .= "#".$cb_id." .vc-content-box-content ,
								#".$cb_id." > .vc-content-box-content h1,
								#".$cb_id." > .vc-content-box-content h2,
								#".$cb_id." > .vc-content-box-content h3,
								#".$cb_id." > .vc-content-box-content h4,
								#".$cb_id." > .vc-content-box-content h5,
								#".$cb_id." > .vc-content-box-content h6,
								#".$cb_id." > .vc-content-box-content p { color: ".$text_color."; ".$transition." } ";
				}

				if ( self::is_greyd_classic() ) {
					// background
					if (isset($block["attrs"]["background"])) {
						$bg = array(
							'id'        => $cb_id,
							'inline_style' => "",
							'basic'     => "",
						);
						// basic background
						$type = isset($block["attrs"]["background"]['type']) ? esc_attr($block["attrs"]["background"]['type']) : '';
						$bg_atts = $block["attrs"]["background"];
						$atts = array(
							"type"      			=> $bg_atts["type"] == "" ? "" : str_replace('gradient', 'gradient2', $bg_atts["type"]),
							"opacity"   			=> $bg_atts["opacity"],
							"colorselect"			=> str_replace('-', '_', $bg_atts["color"]),
							"colorselect_hover"		=> str_replace('-', '_', $block["attrs"]["hover"]["background"]["color"]),
							"gradient2" 			=> self::get_gradient_deprecated($bg_atts["gradient"]),
							"image"     			=> $bg_atts["image"]["id"],
							"image_size"              => $bg_atts["image"]["size"],
							"image_repeat"            => $bg_atts["image"]["repeat"],
							"image_position"          => $bg_atts["image"]["position"],
							"animation_file"        => $bg_atts["anim"]["id"],
							// "animation_placeholder",
							"animation"               => $bg_atts["anim"]["anim"],
							"anim_size"               => $bg_atts["anim"]["width"] == "100%" ? "full" : $bg_atts["anim"]["width"],
							"anim_position"           => $bg_atts["anim"]["position"],
							"anim_full_position"      => explode('_', $bg_atts["anim"]["position"])[0],
							"video"					=> $bg_atts["video"]["url"],
							"video_aspect"		  	  => $bg_atts["video"]["aspect"],
							"image_scroll"          => $bg_atts["scroll"]["type"],
							"image_parallax"          => $bg_atts["scroll"]["parallax"],
							"image_parallax_mobile"   => $bg_atts["scroll"]["parallax_mobile"],
						);
						$bg = Theme_Helper::make_background($atts, "", $bg);
						if ($bg['inline_style'] != "") $cb_style .= $bg['inline_style'];
						if ($bg['basic'] == "") $bg['basic'] = "<div class='vc_bg_div'></div>";
						$background = "<div class='vc_bg vc-content-box-bg'>".$bg['basic']."</div>";
						// border
						$border = "";
						if (isset($block["attrs"]["background"]["border"]) && $block["attrs"]["background"]["border"]["enable"]) {
							$border  = "border-style: ".$block["attrs"]["background"]["border"]['style']."; ";
							$border .= "border-top-width: ".$block["attrs"]["background"]["border"]['width']['top']."; ";
							$border .= "border-right-width: ".$block["attrs"]["background"]["border"]['width']['right']."; ";
							$border .= "border-bottom-width: ".$block["attrs"]["background"]["border"]['width']['bottom']."; ";
							$border .= "border-left-width: ".$block["attrs"]["background"]["border"]['width']['left']."; ";
							$color = $block["attrs"]["background"]["border"]['color'];
							if (strpos($color, "color-") === 0) $color = "var(--".str_replace('-', '', $color).")";
							$border .= "border-color: ".$color."; ";
						}
						if (isset($block["attrs"]['border_radius']) && $block["attrs"]['border_radius'] != "") {
							$border .= "border-radius: ".$block["attrs"]['border_radius']."px; ";
						}
						// shadow
						$shadow = "";
						if (isset($block["attrs"]["background"]["shadow"]) && $block["attrs"]["background"]["shadow"]["enable"]) {
							$sdw = Theme_Helper::get_shadow(array( "shadow" => $bg_atts["shadow"]["shadow"] ), 'shadow', 'none');
							$shadow = 'box-shadow: '.$sdw.';';
						}

						$cb_style .= "#".$cb_id." > .vc-content-box-bg { position: absolute; top:0;left:0;right:0;bottom:0; } 
									#".$cb_id." > .vc-content-box-bg .vc_bg_div { position: absolute; top:0;left:0;right:0;bottom:0; overflow: hidden; 
																					".$transition." ".$border." ".$shadow." } ";
					}
					// hover
					if (isset($block["attrs"]["hover"])) {
						// text
						if (isset($block["attrs"]["hover"]['textColor']) && $block["attrs"]["hover"]["textColor"] != "") {
							$text_color_hover = $block["attrs"]["hover"]['textColor'];
							if (strpos($text_color_hover, "color-") === 0) $text_color_hover = "var(--".str_replace('-', '', $text_color_hover).")";
							$cb_style .= "#".$cb_id.":hover .vc-content-box-content ,
										#".$cb_id.":hover > .vc-content-box-content h1,
										#".$cb_id.":hover > .vc-content-box-content h2,
										#".$cb_id.":hover > .vc-content-box-content h3,
										#".$cb_id.":hover > .vc-content-box-content h4,
										#".$cb_id.":hover > .vc-content-box-content h5,
										#".$cb_id.":hover > .vc-content-box-content h6,
										#".$cb_id.":hover > .vc-content-box-content p { color: ".$text_color_hover."; } ";
						}
						// border
						$border_hover = "";
						if (isset($block["attrs"]["background"]["border"]) && $block["attrs"]["background"]["border"]["enable"]) {
							$border_hover  = "border-style: ".$block["attrs"]["hover"]["background"]["border"]['style']."; ";
							$border_hover .= "border-top-width: ".$block["attrs"]["hover"]["background"]["border"]['width']['top']."; ";
							$border_hover .= "border-right-width: ".$block["attrs"]["hover"]["background"]["border"]['width']['right']."; ";
							$border_hover .= "border-bottom-width: ".$block["attrs"]["hover"]["background"]["border"]['width']['bottom']."; ";
							$border_hover .= "border-left-width: ".$block["attrs"]["hover"]["background"]["border"]['width']['left']."; ";
							$color = $block["attrs"]["hover"]["background"]["border"]['color'];
							if (strpos($color, "color-") === 0) $color = "var(--".str_replace('-', '', $color).")";
							$border_hover .= "border-color: ".$color."; ";
						}
						if (isset($block["attrs"]["hover"]['border_radius']) && $block["attrs"]["hover"]['border_radius'] != "") {
							$border_hover .= "border-radius: ".$block["attrs"]["hover"]['border_radius']."px; ";
						}
						// shadow
						$shadow_hover = $shadow;
						if (isset($block["attrs"]["background"]["shadow"]) && $block["attrs"]["background"]["shadow"]["enable"]) {
							$sdw = Theme_Helper::get_shadow(array( "shadow" => $block["attrs"]["hover"]["background"]["shadow"] ), 'shadow', 'none');
							$shadow_hover = 'box-shadow: '.$sdw.';';
						}

						$cb_style .= "#".$cb_id.":hover > .vc-content-box-bg .vc_bg_div { ".$border_hover." ".$shadow_hover." } ";
					}
				}
				else {
					$background = self::show_frontend_message("The Content-Box Attributes are outdated. Please edit this Page/Post and convert this Element to the newest version.");
				}

				if ($cb_style != "") \Greyd\Enqueue::add_custom_style($cb_style);
				$wrapper_class = "vc-content-box";
				if (isset($block["attrs"]["css_animation"]) && $block["attrs"]["css_animation"] != "") {
					$wrapper_class .= ' animate-when-almost-visible '.$block["attrs"]["css_animation"];
				}
				$block_content = "<div id='".$cb_id."' class='".$wrapper_class."' ".$wrapper_atts.">".$background.$block_content."</div>";
			}

			// render new content-box
			else {
				if ( !isset($block["attrs"]["greydAnim"]) || $block["attrs"]["greydAnim"] === false ) {
					$transition = "";
					$transition_duration = 0.3;
					if (isset($block["attrs"]["transition"]["duration"]) && $block["attrs"]["transition"]["duration"] != "") {
						$transition_duration = $block["attrs"]["transition"]["duration"];
					}
					$transition_effect = 'ease';
					if (isset($block["attrs"]["transition"]["effect"]) && $block["attrs"]["transition"]["effect"] != "") {
						$transition_effect = $block["attrs"]["transition"]["effect"];
					}

					/**
					 * setting greydStyles at this point does not have any effect.
					 * transition styles must be rendered directly.
					 */
					// if (!isset($block["attrs"]['greydStyles'])) $block["attrs"]['greydStyles'] = array();
					// $block["attrs"]['greydStyles']['transitionProperty'] = "box-shadow, border, border-radius, background, color; will-change: box-shadow, border, border-radius, background, color";
					// $block["attrs"]['greydStyles']['transitionDuration'] = $transition_duration."s";
					// $block["attrs"]['greydStyles']['transitionTimingFunction'] = $transition_effect;
					$transition .= "transition-property: box-shadow, border, border-radius, background, color; ";
					$transition .= "will-change: box-shadow, border, border-radius, background, color; ";
					$transition .= "transition-duration: ".$transition_duration."s; ";
					$transition .= "transition-timing-function: ".$transition_effect."; ";
					$style .= $transition;
				}

				// align
				if (isset($block["attrs"]['greydStyles']["align"])) {
					$align = "";
					if ($block["attrs"]['greydStyles']["align"] == "left" || $block["attrs"]['greydStyles']["align"] == "center")
						$align .= "margin-right: auto !important; ";
					if ($block["attrs"]['greydStyles']["align"] == "right" || $block["attrs"]['greydStyles']["align"] == "center")
						$align .= "margin-left: auto !important; ";
					if ($align != "") $html_atts['style'] = $align;
					unset($block["attrs"]['greydStyles']["align"]);
				}

				$wrapper_class = 'greyd-content-box';
				// debug($block["attrs"]);
				if (isset($block["attrs"]["css_animation"]) && $block["attrs"]["css_animation"] != "") {
					$wrapper_class .= ' animate-when-almost-visible '.$block["attrs"]["css_animation"];
				}
				if (isset($block["attrs"]['greydClass'])) {
					$wrapper_class .= ' '.$block["attrs"]['greydClass'];
				}
				if (isset($block["attrs"]['greydStyles']['color'])) {
					$wrapper_class .= ' has-text-color';
				}
				if (isset($block["attrs"]['greydStyles']['justifyContent'])) {
					$justify_content_class = esc_attr( trim( $block["attrs"]['greydStyles']['justifyContent'] ) );
					if ( !empty( $justify_content_class ) ) {
						$justify_content_class = "flex-justify-".$justify_content_class;
						$wrapper_class .= " ".$justify_content_class;
						unset( $block["attrs"]['greydStyles']['justifyContent'] );
					}
				}
				if ( isset($block["attrs"]['background']) && !empty($block["attrs"]['background']) ) {
					$wrapper_class .= ' has-background';
				}
				$html_atts['class'][] = $wrapper_class;

				// get background
				$background = self::render_background('greyd-background', $block["attrs"]);

				if ( !empty($background) ) {
					/**
					 * @since 1.2.10: add the background as child.
					 */
					if ( ! isset($html_atts['children']) ) {
						$html_atts['children'] = array();
					}
					$html_atts['children'][] = $background;
				}

				/**
				 * Variation: ".is-position-sticky"
				 * @since 1.7.2
				 */
				if ( isset( $block['attrs']['variation'] ) && !empty( $block['attrs']['variation'] ) ) {
					
					$html_atts['class'][] .= ' is-position-'.$block['attrs']['variation'];
				}

			}
		}

		// columns
		if ($block['blockName'] === 'core/columns') {
			// debug($block);
			$wrapper_classes = self::is_greyd_classic() ? array( 'row_wrap' ) : array();

			/**
			 * removed old deprecation.
			 * Fixes error because regex sometimes matches something deep in the innerContent and renders falsy backgrounds.
			 */
			// // get saved gradient with 'var(--colorXX)' colors from block_content
			// preg_match( '/style=\"background:(.*)\"/', $block_content, $matches );
			// if (isset($matches[1])) {
			// 	// debug(htmlspecialchars($matches[1]));
			// 	$block["attrs"]["style"]["color"]["gradient"] = $matches[1];
			// }

			$background = self::render_background('greyd-background bg_full', $block["attrs"]);
			
			if ( ! empty( $background ) ) {
				$wrapper_classes[] = 'row_wrap';
				$wrapper_classes[] = 'has-background';
			}

			// debug($block["attrs"]);
			if ( isset($block["attrs"]["css_animation"]) && $block["attrs"]["css_animation"] != "" ) {
				$wrapper_classes[] = 'row_wrap';
				$wrapper_classes[] = 'animate-when-almost-visible';
				$wrapper_classes[] = $block["attrs"]["css_animation"];
			}

			if ( isset($block["attrs"]["className"]) && strpos($block["attrs"]["className"], 'is-style-bootstrap') !== false ) {
				$wrapper_classes[] = 'row_wrap';
				$wrapper_classes[] = 'is-style-bootstrap';

				// check if has attribute: style.spacing.blockGap.left
				if ( isset($block["attrs"]["style"]["spacing"]["blockGap"]["left"]) ) {
					$html_atts['style'] = \greyd\blocks\helper::get_css_line( '--gap', 'calc( ' . $block["attrs"]["style"]["spacing"]["blockGap"]["left"] . ' / 2 )' );
				}
			}

			// add wrapper classes
			if ( !empty( $wrapper_classes ) ) {

				// adopt align class
				if ( isset( $block['attrs']['align'] ) ) {
					$wrapper_classes[] = 'align'.$block['attrs']['align'];
				}

				$html_atts['class'][] = implode( ' ', array_unique( $wrapper_classes ) );
			}
			$block_content = $background.$block_content;

		}

		// column
		if ($block['blockName'] === 'core/column') {

			if (isset($block["attrs"]['width']) && $block["attrs"]['width'] != "") {
				// debug($block_content);
				// debug($block);
				$style_old = 'style="flex-basis:'.$block["attrs"]['width'].'"';
				$style_new = 'style="flex-basis:'.$block["attrs"]['width'].';width:'.$block["attrs"]['width'].'"';
				$block_content = str_replace($style_old, $style_new, $block_content);
			}
		}

		// implode css classes again
		$html_atts['class'] = trim(implode( ' ', $html_atts['class'] ));
		if ( empty($html_atts['class']) ) unset($html_atts['class']);

		/**
		 * Update css animation trigger class to use plugin instead of theme js.
		 * This way, even old nested VC content is triggered correctly.
		 */
		$block_content = str_replace('wpb_animate_when_almost_visible', 'animate-when-almost-visible', $block_content);
		if (isset($html_atts['class'])) $html_atts['class'] = str_replace('wpb_animate_when_almost_visible', 'animate-when-almost-visible', $html_atts['class']);

		// debug($block_content);

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);
	}



	/**
	 * =================================================================
	 *                          Render backgrounds
	 * =================================================================
	 */

	/**
	 * Render Background 
	 * called from block rendering hook
	 * 
	 * @param string $class      Class of the background wrapper element
	 * @param object $atts       Block attributes
	 * 
	 * @return string Background html string
	 */
	public static function render_background($class, $atts) {
		// debug($atts);

		// prepare
		$background = array(
			'id'            => "bg_".uniqid(),
			'class'         => $class,
			'inline_style'  => "",
			'basic'     	=> "",
			'overlay'   	=> "",
			'pattern'   	=> "",
			'seperator' 	=> "",
		);

		// basic
		$background = self::make_background($atts, $background);
		// overlay
		if (isset($atts["background"]["overlay"]) && !empty($atts["background"]["overlay"])) {
			$background = self::make_overlay($atts["background"]["overlay"], $background);
		}
		// pattern
		if (isset($atts["background"]["pattern"]) && !empty($atts["background"]["pattern"])) {
			$background = self::make_pattern($atts["background"]["pattern"], $background);
		}
		// seperator
		if (isset($atts["background"]["seperator"]) && !empty($atts["background"]["seperator"])) {
			$background = self::make_seperator($atts["background"]["seperator"], $background);
		}

		// make background
		if ($background['basic'] != "" || $background['overlay'] != "" || $background['pattern'] != "" || $background['seperator'] != "") {
			// custom css
			\Greyd\Enqueue::add_custom_style($background['inline_style']);
			// make element
			$element = "<div id='".$background['id']."' class='".$background['class']."'>
								".$background['basic']."
								".$background['overlay']."
								".$background['pattern']."
								".$background['seperator']."
							</div>";
			// debug(htmlspecialchars($element));
			return $element;
		}
		return '';

	}

	/**
	 * Render Background Part: basics 
	 * (color/gradient/type)
	 * 
	 * @param object $atts       Block attributes
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_background($atts, $bg) {

		$bg_id           = $bg['id'];
		$bg_inline_style = "";
		$bg_basic        = "";

		// color and gradient
		$color = "";
		$gradient = "";
		// support old atts
		if (isset($atts["background"]["color"])) $color = self::get_color($atts["background"]["color"]);
		if (isset($atts["background"]["gradient"])) $gradient = self::get_gradient($atts["background"]["gradient"]);
		// get core background color or gradient
		if (isset($atts["style"]) && isset($atts["style"]["color"])) {
			if (isset($atts["style"]["color"]["background"])) {
				$color = $atts["style"]["color"]["background"];
			}
			if (isset($atts["style"]["color"]["gradient"])) {
				$gradient = $atts["style"]["color"]["gradient"];
			}
		}
		if (isset($atts["backgroundColor"]) && !empty($atts["backgroundColor"])) {
			$color = self::get_color($atts["backgroundColor"]);
		}
		if (isset($atts["gradient"]) && !empty($atts["gradient"])) {
			$gradient = self::get_gradient($atts["gradient"]);
		}

		// color
		if ($color != "transparent" && !empty($color)) {
			// make basic color layer
			$bg_inline_style .= "#".$bg_id." .bg_color { background-color: ".$color."; } ";
			$bg_basic .= "<div class='bg_color'></div>";
		}
		// gradient
		else if (!empty($gradient)) {
			// make basic gradient layer
			$bg_inline_style .= "#".$bg_id." .bg_gradient { background-image: ".$gradient."; background-attachment: scroll; } ";
			$bg_basic .= "<div class='bg_gradient'></div>";
		}

		if (isset($atts["background"]['type'])) {
			// image
			if ($atts["background"]['type'] == "image" && isset($atts["background"]["image"]) && !empty($atts["background"]["image"])) {
				// make basic image layer
				$background = self::make_image($atts["background"], $bg);
				$bg_inline_style .= $background['inline_style'];
				$bg_basic .= $background['basic'];
			}

			// animation
			if ($atts["background"]['type'] == "animation" && isset($atts["background"]["anim"]) && !empty($atts["background"]["anim"])) {
				// make basic animation layer
				$background = self::make_anim($atts["background"], $bg);
				$bg_inline_style .= $background['inline_style'];
				$bg_basic .= $background['basic'];
			}

			// video
			if ($atts["background"]['type'] == "video" && isset($atts["background"]["video"]) && !empty($atts["background"]["video"])) {
				// make basic video layer
				$background = self::make_video($atts["background"], $bg);
				$bg_inline_style .= $background['inline_style'];
				$bg_basic .= $background['basic'];
			}
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['basic'] .= $bg_basic;
		return $bg;
	}

	/**
	 * =================================================================
	 *                          Render background elements
	 * =================================================================
	 */

	/**
	 * Render Background Part: image
	 * 
	 * @param object $atts       Block attributes.background
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_image($atts, $bg) {

		$bg_id           = $bg['id'];
		$bg_inline_style = "";
		$bg_image_html   = "";
		$className       = "bg_image";
		$html_data_attr  = "";
		$css_properties  = "";
		$img_atts        = $atts["image"];
		$attachment_src  = "";
		$attachment_id   = isset($img_atts['id']) ? esc_attr($img_atts['id']) : -1;

		// get attachment url by id
		if ( is_numeric($attachment_id) && $attachment_id > 0 ) {
			$attachment_src = wp_get_attachment_url( $attachment_id );
		}
		// get attachment by dynamic tag, such as "_image_"
		else if ( class_exists('\Greyd\Dynamic\Render_Blocks') ) {
			$attachment_src = \Greyd\Dynamic\Render_Blocks::get_dynamic_url(preg_replace('/(^_|_$)/', '', $attachment_id));
			$attachment_id  = attachment_url_to_postid( $attachment_src );
		}

		// use the set url if no attachment found
		if ( empty($attachment_src) && isset( $img_atts['url'] ) && !empty( $img_atts['url'] ) ) {
			$attachment_src = esc_attr( $img_atts['url'] );
		}

		if ( empty($attachment_src) ) {
			return $bg;
		}


		// only load thumbnail if lazyload is enabled
		if (self::get_setting(array('site', 'lazyload')) == 'true') {
			$reduced_thumbnail = self::get_image($attachment_id, 'medium');
			if ( $reduced_thumbnail ) {
				$attachment_src = $reduced_thumbnail;
			}
		}

		/**
		 * Build the css
		 */
		$css_properties .= "background-image: url("  .$attachment_src . "); ";

		// image size
		$size = isset($img_atts['size']) && $img_atts['size'] != "" ? esc_attr($img_atts['size']) : "cover";
		$css_properties .= "background-size: ".$size."; ";

		// image repeat
		$repeat = isset($img_atts['repeat']) && $img_atts['repeat'] != "" ? esc_attr($img_atts['repeat']) : "no-repeat";
		$css_properties .= "background-repeat: ".$repeat."; ";

		// image position
		$position = isset($img_atts['position']) && $img_atts['position'] != "" ? esc_attr($img_atts['position']) : "center_center";
		$position = str_replace("_", " ", $position);
		$css_properties .= "background-position: ".$position."; ";

		// scroll
		$scroll = (isset($atts["scroll"])) ? self::get_parallax($atts["scroll"]): array();
		if (!empty($scroll) && is_array($scroll)) {
			if ($scroll['class'] == "scroll" || $scroll['class'] == "fixed") $css_properties .= "background-attachment: {$scroll['class']};";
			else {
				$html_data_attr       = $scroll['data'];
				$className .= " " . $scroll['class'];
			}
		}

		// opacity
		$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 1;
		$css_properties .= "opacity: ".$opacity."; ";

		// finish the css
		$bg_inline_style .= "#{$bg_id} .bg_image { {$css_properties} } ";

		// get srcset
		$srcset = self::get_imageset($attachment_id);
		if ( !empty($srcset) ) {
			$html_data_attr .= " data-srcset='".json_encode($srcset)."'";
		}
		
		/**
		 * Build the html
		 */
		$bg_image_html .= "<div class='{$className}' {$html_data_attr}></div>";


		/**
		 * Fix fixed background position on mobile devices
		 * 
		 * @since 1.2.9
		 */
		if ( is_array($scroll) && isset($scroll['class']) && $scroll['class'] == "fixed" ) {
			$enable_mobile = isset($scroll['data']) && isset($scroll['data']['parallax_mobile']) && $scroll['data']['parallax_mobile'];
			if ( !$enable_mobile ) {
				$bg_inline_style .= "@media (pointer: coarse) { html:not(.ios) #{$bg_id} .bg_image { background-attachment: initial; } } ";
			}

		}

		// finish
		$bg['inline_style'] .= $bg_inline_style;
		$bg['basic'] .= $bg_image_html;

		return $bg;
	}

	/**
	 * Render Background Part: animation
	 * 
	 * @param object $atts       Block attributes.background
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_anim($atts, $bg) {

		$bg_id = $bg['id'];
		$bg_inline_style = "";
		$bg_anim = "";

		$atts_anim = $atts["anim"];
		$animation_file = isset($atts_anim['id']) ? esc_attr($atts_anim['id']) : -1;
		$path = wp_get_attachment_url($animation_file);
		if (isset($path)) {
			$anim_id = "anim_".uniqid();
			$class = "";
			$data = "";
			// animation behaviour
			$behaviour = isset($atts_anim['anim']) && $atts_anim['anim'] != "" ? esc_attr($atts_anim['anim']) : 'auto';
			$data .= " data-anim='".$behaviour."'";
			// anim size
			$size = isset($atts_anim['width']) ? esc_attr($atts_anim['width']) : "";
			if ($size == "100%") $size = "full";
			// anim position
			$position = isset($atts_anim['position']) && $atts_anim['position'] != "" ? esc_attr($atts_anim['position']) : "top_center";
			$style = "";
			if ($size != "full") {
				if ($size != "") {
					$style = "width: ".$size."; ";
				}
				$class = $position;
			}
			else if ($size == "full") {
				$class = explode('_', $position)[0];
			}
			// scroll
			$scroll = (isset($atts["scroll"])) ? self::get_parallax($atts["scroll"]): array();
			if (!empty($scroll) && is_array($scroll)) {
				$data .= $scroll['data'];
				$class .= " div_parallax ".$scroll['class'];
			}
			// make placeholder - todo
			$placeholder = isset($atts_anim['placeholder']) ? esc_attr($atts_anim['placeholder']) : "";
			// if ($placeholder != "") {
			// 	// $placeholder_path = wp_get_attachment_url($placeholder);
			// 	$placeholder_path = get_image($placeholder, 'medium');
			// 	$placeholder = "<img class='lottie-animation-placeholder ".$class."' src='".$placeholder_path."' style='display:inline-block;'>";
			// 	$style .= "display:none;";
			// }
			// else $style .= "display:inline-block;";
			// opacity
			$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 1;
			$opacity = "opacity: ".$opacity."; ";
			// make basic animation layer
			$anim = $placeholder."<div class='lottie-animation ".$behaviour." ".$class."' style='$style' id='".$anim_id."' data-src='".$path."' ".$data."></div>";
			$bg_anim .= "<div class='bg_animation' style='".$opacity."'>".$anim."</div>";
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['basic'] .= $bg_anim;
		return $bg;
	}

	/**
	 * Render Background Part: video
	 * 
	 * @param object $atts       Block attributes.background
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_video($atts, $bg) {

		$bg_id = $bg['id'];
		$bg_inline_style = "";
		$bg_video = "";

		$atts_video = $atts["video"];
		$src = isset($atts_video['url']) ? rawurldecode(esc_attr($atts_video['url'])) : '';
		if ($src != "") {
			$video_id = "video_".uniqid();
			$class = "";
			$data = "";
			// scroll
			$scroll = (isset($atts["scroll"])) ? self::get_parallax($atts["scroll"]): array();
			if (!empty($scroll) && is_array($scroll)) {
				$data .= $scroll['data'];
				$class .= " div_parallax ".$scroll['class'];
			}
			// aspect ratio
			$aspect = isset($atts_video['aspect']) ? esc_attr($atts_video['aspect']) : "16/9";
			$data .= " data-bg_video_ar='".$aspect."'";
			// opacity
			$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 1;
			$opacity = "opacity: ".$opacity."; ";
			// make basic video layer
			if (strpos($src, "youtu") !== false) {

				if ( strpos($src, "youtu.be") ) {
					$tmp = explode(".be/", $src);
					$src = trim($tmp[1]);
				}
				else if ( strpos($src, "?v=") ) {
					$tmp = explode("?v=", $src);
					$src = trim($tmp[1]);
				}
				else if ( strpos($src, "embed/") ) {
					$tmp = explode("embed/", $src);
					$tmp = explode("?", $tmp[1]);
					$src = trim($tmp[0]);
				}
				else {
					// get string between last '/' and '?'
					// eg. https://www.youtube.com/shorts/abc1234_456
					$tmp = explode("/", $src);
					$src = trim($tmp[count($tmp)-1]);
					$tmp = explode("?", $src);
					$src = trim($tmp[0]);
				}

				$data .= " data-bg_video_src='youtube' data-bg_video_id='{$src}'";
				$video = "<div class='{$class}' style='width:100%;height:100%' {$data} ><div id='".wp_unique_id('yt-video-')."'></div></div>";
			}
			else if (strpos($src, "vimeo") !== false) {

				$video_id = trim( str_replace( 'video/', '', explode("vimeo.com/", $src)[1] ) );
				$full_url = "https://player.vimeo.com/video/{$video_id}";
				
				$data .= " data-bg_video_src='vimeo' data-bg_video_id='{$video_id}'";
				$video = "<div class='{$class}' style='width:100%;height:100%' {$data} >
							<iframe id='".wp_unique_id('vimeo-video-')."' src='{$full_url}?autoplay=1&loop=1&muted=1&background=1&controls=0&title=0&byline=0&portrait=0' frameborder='0' allow='autoplay; fullscreen' allowfullscreen></iframe>
						</div>";
			}
			else {
				$video = self::show_frontend_message("Video not recognized");
			}
			$bg_video .= "<div class='bg_video' style='{$opacity}'>{$video}</div>";
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['basic'] .= $bg_video;
		return $bg;
	}

	/**
	 * Render Background Part: overlay
	 * 
	 * @param object $atts       Block attributes.background.overlay
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_overlay($atts, $bg) {

		$bg_id = $bg['id'];
		$bg_inline_style = "";
		$bg_overlay = "";

		// opacity
		$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 1;

		// color overlay
		if ($atts['type'] == "color" && isset($atts['color'])) {
			$color = self::get_color($atts['color']);
			if ($color != "transparent" && !empty($color)) {
				// opacity
				$color_style = "background-color: ".$color."; opacity: ".$opacity.";";
				// make color overlay layer
				$bg_inline_style .= "#".$bg_id." .bg_overlay_color { ".$color_style." } ";
				$bg_overlay .= "<div class='bg_overlay_color'></div>";
			}
		}

		// gradient overlay
		if ($atts['type'] == "gradient" && isset($atts['gradient'])) {
			$gradient = self::get_gradient($atts['gradient']);
			$gradient_style = "background-image: ".$gradient."; background-attachment: scroll; ";
			// opacity
			$gradient_style .= "opacity: ".$opacity.";";
			// make basic gradient layer
			$bg_inline_style .= "#".$bg_id." .bg_overlay_gradient { ".$gradient_style." } ";
			$bg_overlay .= "<div class='bg_overlay_gradient'></div>";
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['overlay'] .= $bg_overlay;
		return $bg;
	}

	/**
	 * Render Background Part: pattern
	 * 
	 * @param object $atts       Block attributes.background.pattern
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_pattern($atts, $bg) {

		$bg_id = $bg['id'];
		$bg_inline_style = "";
		$bg_pattern = "";

		// pattern overlay
		$pattern = isset($atts['type']) ? esc_attr($atts['type']) : '';
		if ($pattern != "") {
			// pattern img
			if ($pattern == "pattern_custom") {
				$custom_pattern = isset($atts['id']) ? esc_attr($atts['id']) : -1;
				$path = wp_get_attachment_url($custom_pattern);
				if (isset($path)) $style = "background-image: url(".$path.");";
			}
			else {
				$style = "background-image: url(".self::get_pattern_png($pattern).");";
			}
			// pattern size
			$size = isset($atts['size']) ? esc_attr($atts['size']) : "";
			if ($size != "") {
				if (strpos($size, "px") === false) $size = $size."px";
				$style .= "background-size: ".$size.";";
			}
			// pattern scroll
			$scroll = isset($atts['scroll']) ? esc_attr($atts['scroll']) : "";
			if ($scroll == "fixed") $style .= "background-attachment: fixed;";
			// pattern opacity
			$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 0.5;
			$style .= "opacity: ".$opacity.";";
			// make pattern layer
			$bg_inline_style .= "#".$bg_id." .bg_pattern { ".$style." } ";
			$bg_pattern .= "<div class='bg_pattern'></div>";

			/**
			 * Fixed background position on mobile devices
			 * 
			 * @since 1.2.9
			 */
			if ( $scroll == "fixed" ) {
				$bg_inline_style .= "@media (pointer: coarse) { #{$bg_id} .bg_image { background-attachment: initial; } } ";
			}
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['pattern'] .= $bg_pattern;
		return $bg;
	}

	/**
	 * Render Background Part: seperator
	 * 
	 * @param object $atts       Block attributes.background.seperator
	 * @param object $bg         Background Object
	 * 
	 * @return object $bg        altered Background Object
	 */
	public static function make_seperator($atts, $bg) {

		$bg_id = $bg['id'];
		$bg_inline_style = "";
		$bg_seperator = "";

		$seperator = isset($atts['type']) ? esc_attr($atts['type']) : "";
		if ($seperator != "") {
			$sep_class = $seperator;
			$svg = "";
			$content = "";
			// color
			$color = (isset($atts["color"])) ? self::get_color_value($atts["color"]) : "";
			// size
			$height = isset($atts['height']) && $atts['height'] != "" ? esc_attr($atts['height']) : "60px";
			// make svg
			$svg = self::get_seperator_svg($seperator, $color, '100%');
			if ($seperator == "zigzag_seperator") {
				$zid = "zigzag_".uniqid();
				$svg = '<svg width="100%" height="100%" fill="var(--color)" overflow="hidden">
							<svg width="100%" height="100%" viewBox="-0.675 0 0.1 1" preserveAspectRatio="xMidYMid meet" overflow="visible">
								<pattern id="'.$zid.'" viewBox="0 0 100 80" height="1" width="1.25" patternUnits="userSpaceOnUse">
									<svg width="100" height="80" viewBox="0 0 18 14.4">
										<polygon points="8.98762301 0 0 9.12771969 0 14.519983 9 5.40479869 18 14.519983 18 9.12771969"/>
									</svg>
								</pattern>
								<rect fill="url(#'.$zid.')" x="-500" y="0" width="1000" height="1"/>
							</svg>
						</svg>';
			}
			// position
			$position = isset($atts['position']) && $atts['position'] != "" ? esc_attr($atts['position']) : "top";
			$zposition = isset($atts['zposition']) && $atts['zposition'] != "" ? esc_attr($atts['zposition']) : "0";
			if (strpos($position, "top") !== false) 
				$content .= "<div class='sep_top' style='height: ".$height."; z-index: ".$zposition."'>".$svg."</div>";
			if (strpos($position, "bottom") !== false) {
				$sep_bclass = "sep_bottom";
				if (strpos($position, "asym") !== false) $sep_bclass .= "_mirror";
				$content .= "<div class='".$sep_bclass."' style='height: ".$height."; z-index: ".$zposition."'>".$svg."</div>";
			}
			// opacity
			$opacity = (isset($atts["opacity"])) ? self::get_opacity($atts["opacity"]) : 1;
			$style = "style='opacity: ".$opacity.";'";
			// make seperator layer
			$bg_seperator .= "<div class='bg_seperator' ".$style.">".$content."</div>";
		}

		$bg['inline_style'] .= $bg_inline_style;
		$bg['seperator'] .= $bg_seperator;
		return $bg;
	}

	/**
	 * =================================================================
	 *                          Render helper
	 * =================================================================
	 */

	/**
	 * Get Image/Attachment Thumbnail.
	 * 
	 * @param int $id		Attachment ID
	 * @param string $size	Thumbnail size
	 * @return string		Thumbnail url
	 */
	public static function get_image($id, $size) {
		$size = !$size ? 'full' : $size;
		$thumb = wp_get_attachment_image_src($id, $size);
		return $thumb === false ? $thumb : $thumb[0];
	}

	/**
	 * Get Image/Attachment srcset.
	 * 
	 * @param int $id		Attachment ID
	 * @return array		Array of all Attachment sizes with urls
	 */
	public static function get_imageset($id) {

		$fullsize = wp_get_attachment_image_src($id, 'full');

		if ( !$fullsize || !is_array($fullsize) ) {
			return false;
		}

		$srcset = array(
			'full' => $fullsize[0]
		);

		foreach ( get_intermediate_image_sizes() as $sze ) {
			$size = wp_get_attachment_image_src($id, $sze);
			if (
				$size
				&& !isset($srcset[$size[1]])
				&& $size[0] != $srcset['full']
			) {
				$srcset[$size[1]] = $size[0];
			}
		}
		ksort($srcset);
		return $srcset;
	}

	/**
	 * Get background opactiy value
	 */
	public static function get_opacity($opacity) {
		$op = 1;
		if (isset($opacity)) {
			// $op = esc_attr($atts[$pre.'opacity'.$post]);
			$op = str_replace("%", "", $opacity);
			$op = $op/100.0;
		}

		return $op;
	}

	/**
	 * Get parallax attributes
	 */
	public static function get_parallax($scroll) {
		// get data
		$type = isset($scroll['type']) ? esc_attr($scroll['type']) : "";
		if ($type == "fixed" || $type == "scroll") {
			$class = $type;
			// $data = array( 'parallax_mobile' => isset($scroll['parallax_mobile']) && $scroll['parallax_mobile'] == "1" );
			$parallax_mobile = isset($scroll['parallax_mobile']) && $scroll['parallax_mobile'] == "true" ? "true" : "false";
			$data = " data-bg_parallax_enable_mobile='".$parallax_mobile."'";
		}
		else {
			$parallax = isset($scroll['parallax']) && $scroll['parallax'] != "" ? esc_attr($scroll['parallax']) : "30";
			$parallax_mobile = isset($scroll['parallax_mobile']) && $scroll['parallax_mobile'] == "true" ? "true" : "false";
			$data = " data-bg_parallax_speed='".$parallax."' data-bg_parallax_enable_mobile='".$parallax_mobile."'";
			$class = "bg_".$type;
		}
		
		return array( 
			'data' => $data, 
			'class' => $class
		);
	}
	
	/**
	 * Get shadow CSS string.
	 */
	public static function get_shadow($shadow) {
		
		if (empty($shadow) || $shadow == 'none') return 'none';

		// get values
		$tmp = explode('+', $shadow);
		$color = self::get_color_value($tmp[count($tmp)-2]);
		$opacity = $tmp[count($tmp)-1];
		// convert to rgba array
		if ($color == 'transparent' || $color == '')
			$color = array( 'r' => 0, 'g' => 0, 'b' => 0, 'a' => 0);
		else {
			$col = self::hex2RGB($color);
			if (!$col) {
				$col = self::rgb2RGB($color);
				if ( !$col ) {
					$col = array( 'r' => 0, 'g' => 0, 'b' => 0, 'a' => 1);
				}
			}
			if (!isset($col['a'])) $col['a'] = 1;
			$col['a'] = $col['a'] * ($opacity / 100.0);
			$color = $col;
		}
		// make shadow color
		$shadowcolor = 'rgba('.$color['r'].','.$color['g'].','.$color['b'].','.$color['a'].')';
		array_pop($tmp); // remove opacity
		array_pop($tmp); // remove color
		array_push($tmp, $shadowcolor);
		return implode(' ',$tmp);
			
	}

	/**
	 * Get the color as a CSS-variable
	 * @param string color 	Variable name (eg. "color-31")
	 * @return string 		CSS-Variable of the color (eg. "var(--wp--preset--color--foreground)")
	 */
	public static function get_color($color) {
		// debug($color);
		if (strpos($color, "color-") === 0) {
			$color = 'var(--'.str_replace("-", "", $color).')';
		}
		else if ( strpos($color, "var(u002du002d") === 0 ) {
			$color = str_replace( "var(u002du002d", "var(--", $color );
		}
		// else if ( method_exists( '\greyd\blocks\layout\Enqueue', 'get_color_palette' ) ) {
		// 	foreach ( Enqueue::get_color_palette() as $col ) {
		// 		if ($col['slug'] == $color) {
		// 			$color = 'var(--wp--preset--color--' . $color . ')';
		// 			break;
		// 		}
		// 	}
		// }
		// if color is still neither a var(), hex, rgb, rgba, hsl or hsla...
		if ( strpos($color, "var") === false && strpos($color, "#") === false && strpos($color, "rgb") === false && strpos($color, "hsl") === false ) {
			$color = 'var(--wp--preset--color--' . $color . ')';
		}
		return $color;
	}

	/**
	 * Get the color value of a CSS-variable
	 * @param string color  Variable name, eg. "var(--wp--preset--color--foreground)"
	 * @return string       Hex value of the color, eg. "#5D5D5D"
	 */
	public static function get_color_value($color) {
		// debug($color);

		// convert css variable into slug
		if (strpos($color, "var") !== false) {

			// remove var()
			$color = str_replace(array( 'var(--', ')' ), '', $color);

			// FSE: convert 'wp--preset--color--foreground' into slug 'foreground'
			if ( strpos($color, '--') !== false ) {
				// explode the string with '--' and get the last part
				$color = explode('--', $color);
				$color = $color[count($color)-1];
			}
			// Classic SUITE: convert 'color12' into 'color-12'
			else if ( strpos($color, '-') === false ) {
				$color = str_replace('color', 'color-', $color);
			}
		}

		// FSE
		if ( method_exists( '\greyd\blocks\layout\Enqueue', 'get_color_palette' ) ) {
			// debug(Enqueue::get_color_palette());
			$_color_new = $color;
			if ( strpos($color, "color-") === 0 ) {
				if ( $color == "color-11" ) $_color_new = "primary";
				if ( $color == "color-12" ) $_color_new = "secondary";
				if ( $color == "color-13" ) $_color_new = "tertiary";
				if ( $color == "color-31" ) $_color_new = "foreground";
				if ( $color == "color-21" ) $_color_new = "dark";
				if ( $color == "color-32" ) $_color_new = "mediumdark";
				if ( $color == "color-22" ) $_color_new = "mediumlight";
				if ( $color == "color-33" ) $_color_new = "base";
				if ( $color == "color-23" ) $_color_new = "background";
				if ( $color == "color-41" ) $_color_new = "#222"; // dummy
				if ( $color == "color-51" ) $_color_new = "#222"; // dummy
				if ( $color == "color-42" ) $_color_new = "#222"; // dummy
				if ( $color == "color-52" ) $_color_new = "#222"; // dummy
				if ( $color == "color-43" ) $_color_new = "#222"; // dummy
				if ( $color == "color-53" ) $_color_new = "#222"; // dummy
				if ( $color == "color-61" ) $_color_new = "darkest";
				if ( $color == "color-62" ) $_color_new = "lightest";
				if ( $color == "color-63" ) $_color_new = "transparent";
			}
			// get color hex value
			foreach ( Enqueue::get_color_palette() as $col ) {
				if (
					$col['slug'] == $color
					|| $col['slug'] == $_color_new
				) {
					$color = $col['color'];
					break;
				}
			}
		}
		else if ( isset( Manage::$colors ) ) {
			if (strpos($color, "color-") === 0) {
				// get color value
				foreach (Manage::$colors as $col) {
					if ($col['slug'] == $color) {
						$color = $col['color'];
						break;
					}
				}
			}
		}


		return $color;
	}

	/**
	 * Get gradient value
	 */
	public static function get_gradient($gradient) {
		// if ($gradient == "") return "";
		// if (strpos($gradient, '-to-color') > -1) {
		if (strpos($gradient, 'linear-gradient(') == false && strpos($gradient, 'radial-gradient(') == false) {
			// color-31-to-color-63-s
			foreach ( Enqueue::get_gradient_presets() as $grad ) {
				if ($grad['slug'] == $gradient) {
					$gradient = $grad['gradient'];
					break;
				}
			}
		}
		return $gradient;
	}

	/**
	 * Get gradient
	 * 
	 * @deprecated gradient helper for old contentbox
	 */
	public static function get_gradient_deprecated($gradient) {
		if ($gradient == "") return "";
		if (strpos($gradient, '-to-color') > -1) {
			// color-31-to-color-63-s
			foreach ( Enqueue::get_gradient_presets() as $grad ) {
				if ($grad['slug'] == $gradient) {
					$gradient = $grad['gradient'];
					break;
				}
			}
		}
		// linear-gradient(180deg,#1f0026 0%,rgb(38,96,121) 52%,rgba(255,255,255,0) 100%)
		// linear-gradient(45deg,#f700c1 0%,#990000 47.8%,rgba(255,255,255,0) 100%)
		$str = substr(str_replace('linear-gradient', '', $gradient), 1, -1);
		$str = explode('deg,', $str);
		$angle = str_replace('deg', '', $str[0]);
		// colors
		$pattern = "/(\S+ \S+)(?:,|$)/U";
		if (preg_match_all($pattern, $str[1], $matches)) {
			// debug($matches);
			$colors = array();
			for ($i=0; $i<count($matches[1]); $i++) {
				$color = explode(' ', $matches[1][$i]);
				$colors[] = $color[0].':'.str_replace('%', '', $color[1]);
			}
			// "scroll|angle|45|color_21:0|color_33:100"
			// "scroll|angle	|45 |#f700c1:0		|#990000:47.8	|color_11:100"
			$gradient = "scroll|angle|".$angle."|".implode('|', $colors);
		}
		return $gradient;
	}

	/**
	 * =================================================================
	 *                          Color and Content helper
	 * =================================================================
	 */

	/**
	 * Convert Color hex string to rgb array.
	 * 
	 * @param string $hex	Color string ( '#rgb', '#rrggbb' or '#rrggbbaa' )
	 * @return array $rgb	Color as array with numeric properties r,g,b,a
	*/
	public static function hex2RGB($hex) {

		// #rrggbb
		if (preg_match("/^#?([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})\$/", $hex, $rgb)) {
			return array(
				'r' => hexdec($rgb[1]),
				'g' => hexdec($rgb[2]),
				'b' => hexdec($rgb[3]),
				'a' => 1
			);
		}

		// #rrggbbaa
		if (preg_match("/^#?([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})\$/", $hex, $rgb)) {
			return array(
				'r' => hexdec($rgb[1]),
				'g' => hexdec($rgb[2]),
				'b' => hexdec($rgb[3]),
				'a' => hexdec($rgb[4])
			);
		}

		// #rgb
		if (preg_match("/^#?([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])$/", $hex, $rgb)) {
			return array(
				'r' => hexdec($rgb[1].$rgb[1]),
				'g' => hexdec($rgb[2].$rgb[2]),
				'b' => hexdec($rgb[3].$rgb[3]),
				'a' => 1
			);
		}

		return false;
	}

	/**
	 * Convert Color rgb string to rgba array.
	 * 
	 * @param string $rgb	Color string ( 'rgb(xx,xx,xx)' or 'rgba(xx,xx,xx,x.x)' )
	 * @return array $rgb	Color as array/object with properties r,g,b,a
	 */
	public static function rgb2RGB($rgb) {

		if (strpos($rgb, "rgb") === false) return false;

		$tmp = explode("(", $rgb);
		$tmp = explode(")", $tmp[1]);
		$tmp = explode(",", $tmp[0]);
		if (count($tmp) == 3) return array( 'r' => trim($tmp[0]), 'g' => trim($tmp[1]), 'b' => trim($tmp[2]), 'a' => 1 );
		if (count($tmp) == 4) return array( 'r' => trim($tmp[0]), 'g' => trim($tmp[1]), 'b' => trim($tmp[2]), 'a' => trim($tmp[3]) );
		return false;
	}

	/**
	 * Get the Background Pattern PNG.
	 * 
	 * @param string $pattern_type	Name of the Pattern
	 * @return string $png			PNG Pattern as data string (e.g. for img src)
	 */
	public static function get_pattern_png($pattern_type) {
		$png = '';
		// patterns base64 strings
		if ($pattern_type == 'pattern_01') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAADklEQVQIW2NggID/cAIADwMB/zSts64AAAAASUVORK5CYII=";
		if ($pattern_type == 'pattern_02') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAE0lEQVR4AWMAgv9gjAMgFAxrAADDbwP9sI02swAAAABJRU5ErkJggg==";
		if ($pattern_type == 'pattern_03') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAEklEQVR4AWMAgs0YGEygAQoFAc1BBkwbUqhOAAAAAElFTkSuQmCC";
		if ($pattern_type == 'pattern_04') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAEklEQVR4AWMYKuA/jMariGwTACH1BPyE5LjvAAAAAElFTkSuQmCC";
		if ($pattern_type == 'pattern_05') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAQAAAAziH6sAAAADklEQVR4AWNg+M/AwAAABAIBAMMz9ccAAAAASUVORK5CYII=";
		if ($pattern_type == 'pattern_06') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABAQMAAADO7O3JAAAABlBMVEUAAAAAAAClZ7nPAAAAAXRSTlMAQObYZgAAAApJREFUCNdjcAAAAEIAQYO57K0AAAAASUVORK5CYII=";
		if ($pattern_type == 'pattern_07') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAECAYAAABGM/VAAAAADklEQVR4AWMgBP6TrgIARBAB/8Id9pYAAAAASUVORK5CYII=";
		if ($pattern_type == 'pattern_08') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAADklEQVR4AWMAgv8MowAABBMBANYOezsAAAAASUVORK5CYII=";
		if ($pattern_type == 'pattern_09') $png = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAD0lEQVQIW2NgYGD4z4AMAA4EAQAMvbGxAAAAAElFTkSuQmCC";
		return $png;
	}

	/**
	 * Get the Background Seperators.
	 * 
	 * @since 1.5.7
	 * 
	 * @return array $seperators Array of Seperators
	 */
	public static function get_background_seperators() {
		$separators = array(
			'triangle_seperator' => '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 0.156661 0.1"><polygon points="0.156661,3.93701e-006 0.156661,0.000429134 0.117665,0.05 0.0783307,0.0999961 0.0389961,0.05 -0,0.000429134 -0,3.93701e-006 0.0783307,3.93701e-006 "/></svg>',
			'triangle_big_seperator' => '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4.66666 0.333331" preserveAspectRatio="none"><path class="fil0" d="M-0 0.333331l4.66666 0 0 -3.93701e-006 -2.33333 0 -2.33333 0 0 3.93701e-006zm0 -0.333331l4.66666 0 0 0.166661 -4.66666 0 0 -0.166661zm4.66666 0.332618l0 -0.165953 -4.66666 0 0 0.165953 1.16162 -0.0826181 1.17171 -0.0833228 1.17171 0.0833228 1.16162 0.0826181z"/></svg>',
			'triangle_big_left_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 2000 90" preserveAspectRatio="none"><polygon xmlns="http://www.w3.org/2000/svg" points="535.084,64.886 0,0 0,90 2000,90 2000,0 "></polygon></svg>',
			'triangle_big_right_seperator' => '<svg class="svg_mirror_xy" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 2000 90" preserveAspectRatio="none"><polygon xmlns="http://www.w3.org/2000/svg" points="535.084,64.886 0,0 0,90 2000,90 2000,0 "></polygon></svg>',
			'circle_seperator' => '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 0.2 0.1"><path d="M0.200004 0c-3.93701e-006,0.0552205 -0.0447795,0.1 -0.100004,0.1 -0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1l0.200004 0z"/></svg>',
			'curve_seperator' => '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4.66666 0.333331" preserveAspectRatio="none"><path class="fil1" d="M4.66666 0l0 7.87402e-006 -3.93701e-006 0c0,0.0920315 -1.04489,0.166665 -2.33333,0.166665 -1.28844,0 -2.33333,-0.0746339 -2.33333,-0.166665l-3.93701e-006 0 0 -7.87402e-006 4.66666 0z"/></svg>',
			'curve_left_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4.66666 0.333331" preserveAspectRatio="none"><path class="fil0" d="M-7.87402e-006 0.0148858l0.00234646 0c0.052689,0.0154094 0.554437,0.154539 1.51807,0.166524l0.267925 0c0.0227165,-0.00026378 0.0456102,-0.000582677 0.0687992,-0.001 1.1559,-0.0208465 2.34191,-0.147224 2.79148,-0.165524l0.0180591 0 0 0.166661 -7.87402e-006 0 0 0.151783 -4.66666 0 0 -0.151783 -7.87402e-006 0 0 -0.166661z"/></svg>',
			'curve_right_seperator' => '<svg class="svg_mirror_xy" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4.66666 0.333331" preserveAspectRatio="none"><path class="fil0" d="M-7.87402e-006 0.0148858l0.00234646 0c0.052689,0.0154094 0.554437,0.154539 1.51807,0.166524l0.267925 0c0.0227165,-0.00026378 0.0456102,-0.000582677 0.0687992,-0.001 1.1559,-0.0208465 2.34191,-0.147224 2.79148,-0.165524l0.0180591 0 0 0.166661 -7.87402e-006 0 0 0.151783 -4.66666 0 0 -0.151783 -7.87402e-006 0 0 -0.166661z"/></svg>',
			'tilt_left_seperator' => '<svg class="svg_mirror_xy" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4 0.266661" preserveAspectRatio="none"><polygon class="fil0" points="4,0 4,0.266661 -0,0.266661 "/></svg>',
			'tilt_right_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 4 0.266661" preserveAspectRatio="none"><polygon class="fil0" points="4,0 4,0.266661 -0,0.266661 "/></svg>',
			'waves_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 6 0.1" preserveAspectRatio="none"><path d="M0.199945 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c-0.0541102,0 -0.0981929,-0.0430079 -0.0999409,-0.0967008l0 0.0967008 0.0999409 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm2.00004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm-0.1 0.1l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm1.90004 -0.1c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.199945 0.00329921l0 0.0967008 -0.0999409 0c0.0541102,0 0.0981929,-0.0430079 0.0999409,-0.0967008z"/></svg>',
			'clouds_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="var(--color)" width="100%" height="var(--height)" viewBox="0 0 2.23333 0.1" preserveAspectRatio="none"><path class="fil0" d="M2.23281 0.0372047c0,0 -0.0261929,-0.000389764 -0.0423307,-0.00584252 0,0 -0.0356181,0.0278268 -0.0865354,0.0212205 0,0 -0.0347835,-0.00524803 -0.0579094,-0.0283701 0,0 -0.0334252,0.0112677 -0.0773425,-0.00116929 0,0 -0.0590787,0.0524724 -0.141472,0.000779528 0,0 -0.0288189,0.0189291 -0.0762362,0.0111535 -0.00458268,0.0141024 -0.0150945,0.040122 -0.0656811,0.0432598 -0.0505866,0.0031378 -0.076126,-0.0226614 -0.0808425,-0.0308228 -0.00806299,0.000854331 -0.0819961,0.0186969 -0.111488,-0.022815 -0.0076378,0.0114843 -0.059185,0.0252598 -0.083563,-0.000385827 -0.0295945,0.0508661 -0.111996,0.0664843 -0.153752,0.019 -0.0179843,0.00227559 -0.0571181,0.00573622 -0.0732795,-0.0152953 -0.027748,0.0419646 -0.110602,0.0366654 -0.138701,0.00688189 0,0 -0.0771732,0.0395709 -0.116598,-0.0147677 0,0 -0.0497598,0.02 -0.0773346,-0.00166929 0,0 -0.0479646,0.0302756 -0.0998937,0.00944094 0,0 -0.0252638,0.0107874 -0.0839488,0.00884646 0,0 -0.046252,0.000775591 -0.0734567,-0.0237087 0,0 -0.046252,0.0101024 -0.0769567,-0.00116929 0,0 -0.0450827,0.0314843 -0.118543,0.0108858 0,0 -0.0715118,0.0609803 -0.144579,0.00423228 0,0 -0.0385787,0.00770079 -0.0646299,0.000102362 0,0 -0.0387559,0.0432205 -0.125039,0.0206811 0,0 -0.0324409,0.0181024 -0.0621457,0.0111063l-3.93701e-005 0.0412205 2.2323 0 0 -0.0627953z"/></svg>',
			'multi_triangle_seperator' => '<svg class="svg_mirror_y" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 100 100" preserveAspectRatio="none" width="100%" height="var(--height)">\
				<path class="large left" d="M0 0 L50 50 L0 100" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .1)"></path>\
				<path class="large right" d="M100 0 L50 50 L100 100" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .1)"></path>\
				<path class="medium left" d="M0 100 L50 50 L0 33.3" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .3)"></path>\
				<path class="medium right" d="M100 100 L50 50 L100 33.3" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .3)"></path>\
				<path class="small left" d="M0 100 L50 50 L0 66.6" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .5)"></path>\
				<path class="small right" d="M100 100 L50 50 L100 66.6" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), .5)"></path>\
				<path d="M0 99.9 L50 49.9 L100 99.9 L0 99.9" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), 1)"></path>\
				<path d="M48 52 L50 49 L52 52 L48 52" fill="rgba(var(--color-rgb-r),var(--color-rgb-g),var(--color-rgb-b), 1)"></path>\
			</svg>'
		);
		
		/**
		 * Filter the Background Seperators.
		 * Use CSS variables 'var(--color)' and 'var(--height)' to customize the seperators.
		 * Those variables will be replaced with actual color and height values.
		 * 
		 * @filter greyd_get_background_seperators
		 * @since 1.5.7
		 * 
		 * @return array $seperators
		 */
		return apply_filters( 'greyd_background_seperators', $separators );
	}
	
	/**
	 * Get the Background Seperator SVG.
	 * 
	 * @param string $svg               SVG Markup of the Seperator
	 * @param string $seperator_type    Name of the Seperator
	 * @param string $color             Color - hex or rgb
	 * @param string $height            Height of the Seperator
	 * 
	 * @return string $svg              SVG Markup of the Seperator
	 */
	public static function get_seperator_svg($seperator_type, $color='#fff', $height='100%') {

		/** get svg */
		$seperators = self::get_background_seperators();
		$svg = isset($seperators[$seperator_type]) ? $seperators[$seperator_type] : '';

		/** fallback colors */
		if ( empty($color) || $color === 'transparent') {
			$color = '#fff';
		}
		
		$color = self::get_color_value( $color );

		/** build replacements */
		$replacements = array(
			'var(--color)' => $color,
			'var(--height)' => $height,
		);
		if ( strpos($svg, 'var(--color-rgb-') !== false ) {
			$color_rgb = self::hex2RGB($color);
			if ( !$color_rgb ) {
				$color_rgb = self::rgb2RGB($color);
			}
			$replacements['var(--color-rgb-r)'] = $color_rgb['r'];
			$replacements['var(--color-rgb-g)'] = $color_rgb['g'];
			$replacements['var(--color-rgb-b)'] = $color_rgb['b'];
		}

		/** replace */
		$svg = str_replace( array_keys($replacements), array_values($replacements), $svg );
		
		/**
		 * Filter the Background Seperator SVG.
		 * 
		 * @filter greyd_background_seperator_svg
		 * @since 1.5.7
		 * 
		 * @param string $svg               SVG Markup of the Seperator
		 * @param string $seperator_type    Name of the Seperator
		 * @param string $color             Color - hex or rgb
		 * @param string $height            Height of the Seperator
		 * 
		 * @return string $svg              SVG Markup of the Seperator
		 */
		return apply_filters( 'greyd_background_seperator_svg', $svg, $seperator_type, $color, $height );
	}

	/**
	 * Backward compatiblity functions.
	 * Can be removed in later versions > 1.5.0
	 */
	public static function is_greyd_classic() {
		if ( method_exists( '\Greyd\Helper', 'is_greyd_classic' ) ) {
			return \Greyd\Helper::is_greyd_classic();
		}

		// check if Greyd.Suite is active
		if ( defined( 'GREYD_CLASSIC_VERSION' ) || class_exists("\basics") ) {
			return true;
		}

		// check if Greyd Theme is active
		if ( defined( 'GREYD_THEME_CONFIG' ) ) {
			return false;
		}

		// check if Greyd.Suite is installed
		$_current_main_theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
		return strpos( $_current_main_theme->get('Name'), "GREYD.SUITE" ) !== false;
	}
	public static function show_frontend_message( $msg, $mode="info" ) {
		if ( method_exists( '\Greyd\Helper', 'show_frontend_message' ) ) {
			return \Greyd\Helper::show_frontend_message( $msg, $mode );
		}
		if ( $mode != "info" && $mode != "success" && $mode != "danger" ) $mode = "info";
		return "<div class='message ".$mode."'>".$msg."</div>";
	}
	public static function get_setting( $key, $default=true ) {
		if ( method_exists( '\Greyd\Settings', 'get_setting' ) ) {
			return \Greyd\Settings::get_setting( $key, $default );
		}
		else if ( method_exists( '\basics', 'get_setting' ) ) {
			return \basics::get_setting( $key, $default );
		}
		return null;
	}
}