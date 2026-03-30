<?php
/**
 * Greyd Button Blocks.
 */
namespace greyd\blocks;
// namespace Greyd\Block;

use greyd\blocks\helper as helper;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if plugin already runs in tp_management
if (class_exists("Greyd\Block\Button")) return;

new Button($config);
class Button {

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
		add_action( 'init', array($this, 'register_blocks') );

		// scripts & styles
		// add_filter( 'block_editor_settings_all', array($this, 'add_blocks_editor_styles'), 999, 2 );

		// frontend
		if ( is_admin() || !class_exists('greyd\blocks\render') ) return;

		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'render_core_button'), 10, 2 );
		add_filter( 'greyd_blocks_render_block_finally', array($this, 'render_greyd_buttons'), 10, 2 );
		add_filter( 'greyd_blocks_render_block_finally', array($this, 'render_greyd_button'), 10, 2 );
	}


	/**
	 * =================================================================
	 *                          Blockeditor
	 * =================================================================
	 */

	/**
	 * Register the blocks
	 */
	public function register_blocks() {

			// styles
		if ( class_exists( '\processor' ) ) {

			wp_register_style(
				'greyd-button-frontend-style',
				$this->config->css_uri.'classic/style.css',
				array( ),
				GREYD_VERSION
			);

			wp_register_style(
				'greyd-button-editor-style',
				$this->config->css_uri.'classic/editor-blocks.css',
				array( ),
				GREYD_VERSION
			);
		} else {
			wp_register_style(
				'greyd-button-frontend-style',
				$this->config->css_uri.'style.css',
				array( ),
				GREYD_VERSION
			);

			wp_register_style(
				'greyd-button-editor-style',
				$this->config->css_uri.'editor-blocks.css',
				array( ),
				GREYD_VERSION
			);
		}

		// register script
		wp_register_script(
			'greyd-button-editor-script',
			$this->config->js_uri.'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// script translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-button-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ).'greyd-plugin/languages' );
		}

		// register button blocks
		register_block_type( 'greyd/buttons', array(
			'editor_script' => 'greyd-button-editor-script',
			'editor_style' => array( 'greyd-button-editor-style', 'elegant-icons' ), 
			'style' => 'greyd-button-frontend-style', 
		) );
		register_block_type( 'greyd/button', array(
			'editor_script' => 'greyd-button-editor-script',
		) );

	}

	/**
	 * Add styles to the editor preview
	 * 
	 * @deprecated
	 */
	public function add_blocks_editor_styles($editor_settings, $editor_context) {

		if ( ! class_exists( '\processor' ) ) {
			return $editor_settings;
		}
		// debug($editor_settings);
		// debug($editor_context);

		// add shared css to backend
		$editor_settings['styles'][] = array('css' => \processor::process_styles($this->config->css_uri.'/editor-blocks.css'));
		
		return $editor_settings;

	}


	/**
	 * =================================================================
	 *                          Frontend
	 * =================================================================
	 */

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
	 *      @property array  html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public function render_core_button( $content, $block ) {

		if ( $block['blockName'] !== 'core/button' ) {
			return $content;
		}

		$block_content = $content['block_content'];
		$html_atts     = $content['html_atts'];
		$style         = $content['style'];

		if (
			empty($block_content) &&
			isset($block["attrs"]["icon"]) &&
			$block["attrs"]["icon"] != "" &&
			isset($block["attrs"]["icon_hideEmpty"]) &&
			!$block["attrs"]["icon_hideEmpty"]
		) {
			// empty button - only icon
			$block_content = $block['innerHTML'];
		}

		// id for enqueue is set either in anchor or old inline_css_id
		if (isset($block["attrs"]["anchor"])) {
			$id = $block["attrs"]["anchor"];
		}
		else if (isset($block["attrs"]["inline_css_id"])) {
			$id = $block["attrs"]["inline_css_id"]; //props.clientId;
		}

		if ( !isset($id) ) {
			// parse id from content (core blocks don't save anchor in attrs)
			$res = preg_match_all( '/^<[^>]+\sid=\"([^\"]+)\"/', trim( $block_content ), $matches );
			if ( $res && count( $matches[0] ) > 0 ) {
				$id = $matches[1][0];
			}
			else {
				// inject unique id
				$id = 'greyd-'.uniqid();
				$block_content = preg_replace(
					'/(<div)/',
					'$1 id="' . $id . '"',
					$block_content,
					1
				);
			}
		}

		if (isset($block["attrs"]['min_width']) && $block["attrs"]['min_width'] != "") {
			// add block style
			$style .= "width: ".$block["attrs"]['min_width']."; ";

			if ( ! isset( $id ) ) {
				$id = "button-".uniqid();
				$html_atts['id'] = $id;
			}

			// add additional link style
			\Greyd\Enqueue::add_custom_style("#".$id." > a { width: 100%; } ");
		}

		if (isset($block["attrs"]["icon"]) && $block["attrs"]["icon"] != "") {
			$pos = "after";
			$margin = "left";
			if (isset($block["attrs"]["icon_align"]) && $block["attrs"]["icon_align"] == 'left') {
				$pos = "before";
				$margin = "right";
			}
			$icon_margin = isset($block["attrs"]["icon_margin"]) ? $block["attrs"]["icon_margin"] : "10px";
			$icon_size = isset($block["attrs"]["icon_size"]) ? $block["attrs"]["icon_size"] : "100%";
			$icon = class_exists('\Greyd\Icons') ? \Greyd\Icons::get_icon($block["attrs"]["icon"]) : icons::get_icon($block["attrs"]["icon"]);


			if ( ! isset( $id ) ) {
				$id = "button-".uniqid();
				$html_atts['id'] = $id;
			}
			
			// add additional icon style
			\Greyd\Enqueue::add_custom_style(
				"#".$id." > a.wp-block-button__link:".$pos." { 
					content: '".$icon."' / '';
					font-family: ElegantIcons;
					vertical-align: bottom;
					margin-".$margin.": ".$icon_margin.";
					font-size: ".$icon_size.";
				} ");
		}

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
	public function render_greyd_buttons( $block_content, $block ) {
		
		if ( $block['blockName'] !== 'greyd/buttons' ) {
			return $block_content;
		}

		// don't render empty button wrappers
		if ( empty(trim(strip_tags($block_content))) ) {
			// check if there is no a tags (e.g. icon button)
			preg_match( '/<a.+?">(.*)<\/a>/', $block_content, $matches);
			if ( empty($matches) ) return "";
		}

		// if attrs->style->spacing->blockGap is set
		if (
			isset( $block['attrs'] )
			&& isset( $block['attrs']['style'] )
			&& isset( $block['attrs']['style']['spacing'] )
			&& isset( $block['attrs']['style']['spacing']['blockGap'] )
		) {
			
			if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
				$tags = new \WP_HTML_Tag_Processor( $block_content );
				if (
					method_exists( $tags, 'next_tag' )
					&& method_exists( $tags, 'has_class' )
					&& method_exists( $tags, 'get_updated_html' )
				) {
					if ( $tags->next_tag( array( 'class_name' => 'wp-block-greyd-buttons' ) ) ) {

						$style = $tags->get_attribute( 'style' ) ?? "";
						// debug( $style, true );

						$value = \greyd\blocks\helper::get_css_line( 'gap', $block['attrs']['style']['spacing']['blockGap'] );

						$style = $value.$style;

						$tags->set_attribute( 'style', $style );
						
						$block_content = $tags->get_updated_html();
					}
				}
			}
		}

		return $block_content;
	
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
	public function render_greyd_button( $block_content, $block ) {

		if ( $block['blockName'] !== 'greyd/button' ) {
			return $block_content;
		}

		/**
		 * don't render buttons with neither text nor icon
		 * @deprecated since 1.5.1
		 */
		// if ( !preg_match('/>(<span class="|<span style="flex:1">([^<]|<span))/', $block_content) ) {
		// 	$block_content = "";
		// }

		/**
		 * Don't render empty buttons
		 * @since 1.5.1
		 */
		$block_content = preg_replace_callback( '/^\s*(<a.+?">)(.*)(<\/a>)\s*/', function( $match ) use ( $block ) {
			
			list( $content, $before, $inner, $after ) = $match;

			// debug( esc_attr($inner) );

			if (
				empty( $inner ) ||
				$inner === '<span style="flex:1"></span>' ||
				$inner === '<span style="flex:0 1 auto"></span>'
			) {
				return "";
			}
			
			// check if button has an empty text
			if (
				strpos( $inner, '<span style="flex:1"></span>' ) !== false ||
				strpos( $inner, '<span style="flex:0 1 auto"></span>' ) !== false
			) {
				// if the option 'hideEmpty' is set, don't render the button
				if (
					!empty( $block['attrs']['icon']['content'] ) &&
					isset( $block['attrs']['icon']['hideEmpty'] ) &&
					$block['attrs']['icon']['hideEmpty']
				) {
					// empty button - only icon
					return "";
				}
			}
			
			return $content;
			
		}, $block_content );

		// return empty string if there is no a tags but for example greyd styles
		preg_match( '/<a.+?">(.*)<\/a>/', $block_content, $matches);
		if (empty($matches)) return "";

		return $block_content;
	}
}
