<?php
/**
 * Dynamic block features rendering.
 * - Template Block
 * - dynamic content
 * - dynamic tags
 */
namespace Greyd\Dynamic;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

new Render_Blocks($config);
class Render_Blocks {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;
		if (is_admin()) return;

		// set config
		$this->config = (object) $config;

		add_action( 'init', array($this, 'init') );
	}
	public function init() {

		// if (!class_exists('blocks')) return;

		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'render_block'), 999, 2 );
		
	}


	/**
	 * =================================================================
	 *                          Block render callback
	 * =================================================================
	 */

	/**
	 * Render a dynamic template.
	 * 
	 * @see \Greyd\Dynamic\Admin::add_dynamic()
	 * 
	 * @param array $atts      Dynamic attributes (eg. template, dynamic_fields, dynamic_content)
	 * @param string $content  Current block content.
	 * @param WP_Block $block  https://developer.wordpress.org/reference/classes/wp_block/
	 * 
	 * @return string
	 */
	public static function render_dynamic($atts, $content, $block) {

		$debug = '';
		// debug("render");
		// debug($atts);
		// debug($block);
		if (isset($atts['dynamic_fields']) && count($atts['dynamic_fields']) > 0 &&
			$atts['dynamic_fields'][0]['enable'] && !empty($atts['dynamic_fields'][0]['value'])) {
			// make dynamic_content from dynamic_fields if set and has value
			// debug($atts['dynamic_fields'][0]['value']);
			$dc = explode('::', $atts['dynamic_fields'][0]['value']);
			$dc[0] = $atts['template'];
			// debug($dc);
		}
		else {
			// prepare dynamic_content
			$dc = [ $atts['template'] ];
			// debug($atts['dynamic_content']);
			for ($i=0; $i<count($atts['dynamic_content']); $i++) {

				/**
				 * Cut %22' from start & end of dvalue
				 * this can be caused through encoded issues in the editor. This mainly occurred when interacting with revisions
				 * 
				 * @since 2.2.0
				 */
				$atts['dynamic_content'][$i]['dvalue'] = preg_replace('/^%22(.*?)%22$/', '$1', $atts['dynamic_content'][$i]['dvalue']);

				$val = $atts['dynamic_content'][$i]['dkey'].'|'.
					$atts['dynamic_content'][$i]['dtype'].'|'.
					Helper::rawurlencode($atts['dynamic_content'][$i]['dtitle']).'|'.
					$atts['dynamic_content'][$i]['dvalue'];
				array_push($dc, $val);
			}
		}
		
		// prematch dynamic_content with post tags (also dpt in loop)
		if (count($dc) > 1) {

			/**
			 * Display a custom post with a template.
			 * Either set as context or as the attribute 'postId'.
			 */
			$custom_post_id = null;
			if (isset($atts['postId']) && !empty($atts['postId'])) {
				$custom_post_id = $atts['postId'];
			}
			else if (isset($block->context['postId']) && !empty($block->context['postId'])) {
				$custom_post_id = $block->context['postId'];
			}

			$custom_post_id = apply_filters('greyd_dynamic_post_id', $custom_post_id, $atts, $block);

			/**
			 * @since 1.1.2:
			 * 
			 * Use the custom post ID only if it is different than the global post ID.
			 * 
			 * This is necessary because the return of get_post() differs slightly from
			 * the return of get_post(get_the_ID()). Even though the global $post->ID and
			 * the $custom_post_id are the same, the return is filtered when we provide
			 * the post ID as an argument.
			 * 
			 * Therefore it would no longer support all the custom post properties we set
			 * inside the global search, like $post->site_url or $post->blog_id.
			 * @see Greyd\Global_Contents\Search\Global_Search::extend_post_data()
			 * 
			 * @since 1.3.5:
			 * 
			 * Modify the global $post variable. Otherwise post related information cannot
			 * be matched later.
			 * @see self::get_dynamic_url()
			 */
			global $post;
			if ( $custom_post_id && !empty($custom_post_id) && ( empty($post) || $custom_post_id != $post->ID ) ) {
				$post = get_post( $custom_post_id );
			}

			/**
			 * No more prematching of dynamic tags at this point.
			 * With deeper nested Templates, the prematching of the dynamic content gets mixed up because 
			 * the global $post reference changes and the wrong values get inserted prematurely.
			 * @since 2.14.0
			 */
			// if ( $post ) {
			// 	// $debug .= $post->ID;
			// 	$dtags = Dynamic_Helper::get_dynamic_posttype_tags($post);
			// 	$tags = Dynamic_Helper::get_dynamic_tags('single');
			// 	for ($i=1; $i<count($dc); $i++) {
			// 		$dynamic_atts = explode('|', $dc[$i]);
			// 		$oldval = rawurldecode($dynamic_atts[3]);
			// 		$newval = \Greyd\Dynamic\Render::match_tags($post, $dtags, $oldval);
			// 		$newval = \Greyd\Dynamic\Render::match_tags($post, $tags, $newval);
			// 		// debug(htmlspecialchars($newval));
			// 		// debug($block);
			// 		$newval = self::match_dynamic_tags($block->parsed_block, $newval, $post );
			// 		if ($oldval != $newval) {
			// 			if ( preg_match( '/https?:\/\/https?/', $newval ) ) {
			// 				$newval = rawurldecode( preg_replace( '/^https?:\/\//', '', $newval ) );
			// 			}
			// 			$dynamic_atts[3] = Helper::rawurlencode($newval);
			// 		}
			// 		$dc[$i] = implode('|', $dynamic_atts);
			// 	}
			// }
		}
		$atts['dynamic_content'] = implode('::', $dc);
		// debug($atts);

		/**
		 * @todo: Refactor
		 * at the end only process_blocks_content() is called
		 */
		return $debug.
			\Greyd\Dynamic\Render::add_dynamic($atts, "").
			\Greyd\Dynamic\Render::get_style();

	}
	

	/**
	 * =================================================================
	 *                          Dynamic Content
	 * =================================================================
	 */

	/**
	 * Process Blocks Template Content
	 * called from add_dynamic() function
	 * 
	 * @param object $atts          attributes
	 * @param object $template      dynamic template post object
	 * 
	 * @return string $content      Rendered Content
	 */
	public static function process_blocks_content($atts, $template) {
		// dynamic content for block templates
		$dynamic_content = isset($atts['dynamic_content']) ? esc_attr($atts['dynamic_content']) : null;
		if ( !empty($dynamic_content) ) {
			$dynamic_values = explode('::', $dynamic_content);
			// debug($dynamic_values);
			$values = array();
			if ($dynamic_values[0] == $atts['template'] && count($dynamic_values) > 1) {
				for ($i=1; $i<count($dynamic_values); $i++) {
					$args = explode('|', $dynamic_values[$i]);
					// debug($args);
					$key = $args[0];
					$type = $args[1];
					$title = rawurldecode($args[2]);
					$value = $args[3];
					if ($key == 'dynamic_content') {
						$value = implode('::', $dynamic_values); // $dynamic_content;
					}
					// debug($value);
					array_push($values, array(
						'key' => $key, 
						'type' => $type, 
						'title' => $title, 
						'value' => $value
					));
				}
			}
		}
		// debug("template");
		// debug($atts);
		// debug($values);

		$content = '';
		$blocks = parse_blocks($template->post_content);
		foreach ($blocks as $block) {
			$b = isset($values) ? self::modify_block($block, $values) : $block;
			$content .= render_block( $b );
		}

		/**
		 * @since 2.6.0 We do not filter the content anymore.
		 * 
		 * Filtering the entire dynamic template content can cause issues with
		 * other plugins. We rather filter only the post content that is being
		 * included using a dynamic tag or block.
		 * @see \Greyd\Dynamic\Dynamic_Helper::make_content_string()
		 */
		$new_content = $content;
		// remove_filter( 'the_content', 'wpautop' );
		// $new_content = apply_filters( 'the_content', $content );
		// add_filter( 'the_content', 'wpautop' );

		/**
		 * @since 1.6.5
		 * When normal content rendering fails, try to render the content
		 * with the default WordPress functions.
		 * 
		 * This fixed an error specifically with the plugin "GeoDirectory"
		 * on archive & search pages.
		 */
		$new_content = wptexturize($new_content);
		$new_content = wp_filter_content_tags($new_content);
		// $new_content = do_shortcode($new_content);
		$new_content = do_shortcode( shortcode_unautop( $new_content ) );
		global $wp_embed;
		$new_content = $wp_embed->autoembed( $new_content );

		return $new_content;
	}

	/**
	 * Modify Block with dynamic values
	 * 
	 * @param object $block       parsed Block before rendering
	 * @param object $values      dynamic values
	 * 
	 * @return object $block      altered Block
	 */
	public static function modify_block($block, $values) {
		
		if (isset($block['attrs']['dynamic_fields']) && count($block['attrs']['dynamic_fields']) > 0) {
			// debug('dynamic field values found');
			// debug($block['blockName']);
			// debug($values);
			$fields = array();
			foreach ($block['attrs']['dynamic_fields'] as $field) {
				if (!$field['enable']) continue;
				if ($field['key'] == 'dynamic_content') {
					$dc = array();
					foreach ( $values as $value ) {
						array_push($dc, array(
							'dkey' => $value['key'], 
							'dtype' => $value['type'], 
							'dtitle' => $value['title'], 
							'dvalue' => $value['value']
						));
					}
					$block['attrs']['dynamic_content'] = $dc;
				}
				else foreach ( $values as $value ) {
					if ($value['title'] == $field['title']) {
						$field['value'] = $value['value'];

						break;
					}
				}
				array_push($fields, $field);
			}
			$block['attrs']['dynamic_fields'] = $fields;
			// debug($block['attrs']);

			$mod = self::match_block_fields($block, $fields);
			$block['attrs'] = $mod['attrs'];
			$block['innerHTML'] = $mod['innerHTML'];
			$innerContent = $mod['innerHTML'];
			$index = -1;
			if ( count($block['innerContent']) > 0 ) {
				foreach ($block['innerContent'] as $i => $ic) {
					if ( $ic && !empty(trim($ic)) ) {
						if ( strpos($innerContent, $ic) === false ) {
							$index = $i;
						}
						else {
							$innerContent = str_replace($ic, "", $innerContent);
						}
					}
				}
				if ( $index > -1) $block['innerContent'][$index] = $innerContent;
			}
			/**
			 * @since 2.9.0 When the innerContent of the original block is empty,
			 * but the innerContent of the modified block is not, we have to add
			 * the innerContent to the block.
			 * This is necessary for blocks like core/embed that have no video
			 * embedded as a fallback and therefore have no innerContent before.
			 */
			else if ( count($block['innerContent']) === 0 && !empty($innerContent) ) {
				$block['innerContent'] = array($innerContent);
			}
		}
		if (isset($block['innerBlocks']) && count($block['innerBlocks']) > 0) {
			$inner = array();
			foreach ( $block['innerBlocks'] as $ib ) {
				array_push($inner, self::modify_block($ib, $values));
			}
			$block['innerBlocks'] = $inner;
		}

		return $block;
	}

	/**
	 * Match Block attributes and innerHTML with dynamic values
	 * 
	 * @param object $block       parsed Block before rendering
	 * @param object $fields      dynamic fields
	 * 
	 * @return object altered attributes and innerHTML
	 */
	public static function match_block_fields($block, $fields) {
		// debug($block['blockName']);
		// debug($fields);

		$attrs = $block['attrs'];
		$block_content = $block['innerHTML'];
		foreach ($fields as $field) {

			if (!isset($field["value"])) continue;
			
			/**
			 * After removing prematching of dynamic tags, we have to match the dynamic tags
			 * to the field values here.
			 * @since 2.14.0
			 */
			if ( is_string($field["value"]) ) {
				$field["value"] = self::match_dynamic_tags( $block, $field["value"] );
			}
			
			if ($block['blockName'] === 'core/columns' || $block['blockName'] === 'greyd/box' || $block['blockName'] === 'core/group') {
				
				// image source
				if (strpos($field['key'], '/id') > 0) {

					$attachment = self::get_attachment($field["value"]);

					// only change attributes, as the background is rendered later on
					if ( !empty($attachment["src"]) && $attachment["type"] == 'image' ) {
						if ($field['key'] == 'background/image/id') {
							$attrs['background']['image']['id'] = $attachment["id"];
							$attrs['background']['image']['url'] = $attachment["src"];
						}
						if ($field['key'] == 'background/anim/id') {
							$attrs['background']['anim']['id'] = $attachment["id"];
							$attrs['background']['anim']['url'] = $attachment["src"];
						}
						if ($field['key'] == 'style/background/backgroundImage/id') {
							// core/group
							$attrs['style']['background']['backgroundImage']['id'] = $attachment["id"];
							$attrs['style']['background']['backgroundImage']['url'] = $attachment["src"];
						}
					}
				}

				// video url
				if ($field['key'] == 'background/video/url') {
					$attrs['background']['video']['url'] = rawurldecode( $field["value"] );
				}
			}
			if ($block['blockName'] === 'core/spacer') {
				if ($field['key'] == 'height') {
					
					// get field value
					if ( strpos($field["value"], 'var%3Apreset%7C') > -1 ) {
						$val = rawurldecode($field["value"]);
						$new_val = \greyd\blocks\helper::get_spacing_preset_css_var( $val );
					}
					else {
						$val = $new_val = intval($field["value"]).'px';
					}

					// set height value
					if ( isset($attrs['height']) ) {
						$old_val = \greyd\blocks\helper::get_spacing_preset_css_var( $attrs['height'] );
						$old = 'style="height:'.$old_val.'"';
						$new = 'style="height:'.$new_val.'"';
						$block_content = str_replace($old, $new, $block_content);
					} else if ( !empty($block_content) ) {
						$block_content = preg_replace(
							'/style="height:(.*?)("|;)/',
							'style="height:'.$new_val.'$2',
							$block_content
						);
					}
					$attrs['height'] = $val;
				}
			}
			if ($block['blockName'] === 'core/embed') {
				// debug($block);
				if ($field['key'] == 'url') {
					$new = rawurldecode($field["value"]);

					/**
					 * Fix for not properly included embed wrappers.
					 * (1) Original embed is empty.
					 * (2) Original embed has no url.
					 * (3) Everything is fine, replace the url attr.
					 * 
					 * Note: do not reformat the strings! $wp_embed->autoembed is detecting
					 * linebreaks to find the embed url. If we don't have linebreaks, the
					 * videos will not be rendered.
					 * 
					 * @since 1.2.2
					 */
					if ( empty($block_content) ) {
						// (1)
						$block_content = '<figure class="wp-block-embed is-type-video wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
						'.$new.'
						</div></figure>';
					}
					else if ( !isset($attrs['url']) ) {
						// (2)
						$block_content = preg_replace(
							'/(class=\"wp-block-embed__wrapper\">)[^<]?(<\/div>)/',
							'$1
							'.$new.'
							$2',
							$block_content
						);
					}
					else {
						// (3)
						$old = $attrs['url'];
						$block_content = str_replace($old, $new, $block_content);
					}
					
					$attrs['url'] = $new;
					global $wp_embed;
					$block_content = $wp_embed->autoembed( $block_content );
				}
			}
			if ($block['blockName'] === 'core/video') {
				if ($field['key'] == 'src') {

					$attachment = self::get_attachment($field["value"]);

					if ( empty($attachment["src"]) || $attachment["type"] != 'video' ) {
						$block_content = '';
					}
					else if ( !empty($block_content) && preg_match('/src="[^"]+?/', $block_content) ) {
						$block_content = preg_replace(
							'/src="[^"]+?"/',
							'src="'.$attachment["src"].'"',
							$block_content
						);
					}
					else {
						$block_content = '<figure class="wp-block-video dyn"><video autoplay loop muted preload="none" playsinline src="'.$attachment["src"].'"></video></figure>';
					}
				}
			}
			if ($block['blockName'] === 'core/paragraph') {
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				if ($field['key'] == 'content') {
					$result = self::strip_tags($block_content, array('p'));
					$old = '>'.$result.'<';
					$new = '>'.rawurldecode($field["value"]).'<';
					if ($new == '><') $block_content = "";
					else $block_content = str_replace($old, $new, $block_content);
				}
			}
			if ($block['blockName'] === 'core/heading') {
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				if ($field['key'] == 'content') {
					$result = self::strip_tags($block_content, array('h[1-6]'));
					$old = '>'.$result.'<';
					$new = '>'.rawurldecode($field["value"]).'<';
					if ($new == '><') $block_content = "";
					else $block_content = str_replace($old, $new, $block_content);
				}
				if ($field['key'] == 'level') {
					$level = isset($attrs['level']) ? $attrs['level'] : '2';
					$old = array('<h'.$level.' ', '</h'.$level.'>');
					$new = array('<h'.$field["value"].' ', '</h'.$field["value"].'>');
					$block_content = str_replace($old, $new, $block_content);
				}
			}
			if ($block['blockName'] === 'core/button') {
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				if ($field['key'] == 'text') {
					$new = '>'.rawurldecode($field["value"]).'<';
					if ($new == '><' && ( !isset($block["attrs"]["icon_hideEmpty"]) || $block["attrs"]["icon_hideEmpty"] ) ) {
						$block_content = "";
					}
					else if (empty($block_content)) {
						$block_content = '<div class="wp-block-button '.$attrs['className'].'">
											<a class="wp-block-button__link"'.$new.'/a>
										</div>';
					}
					else {
						// debug($block);
						$result = self::strip_tags($block_content, array('div', 'a'));
						$old = '>'.$result.'</a>';
						$new = '>'.rawurldecode($field["value"]).'</a>';
						$block_content = str_replace($old, $new, $block_content);
					}
				}
				if ($field['key'] == 'url') {
					$result = self::get_attribute($block_content, 'href');
					$old = 'href="'.$result.'"';
					$new = 'href="'.rawurldecode($field["value"]).'"';
					$block_content = str_replace($old, $new, $block_content);
				}
			}
			if ($block['blockName'] === 'core/image') {
				
				if ( $field['key'] == 'id' ) {

					$attachment = self::get_attachment($field["value"]);

					// replace strings in block_content
					if ( !empty($attachment["src"]) && $attachment["type"] == 'image' ) {
						$old = array();
						$new = array();
						if (isset($attrs['id'])) {
							array_push($old, 'wp-image-'.$attrs['id']);
							array_push($new, 'wp-image-'.$attachment["id"]);
							$result = self::get_attribute($block_content, 'src');
							array_push($old, 'src="'.$result.'"');
							array_push($new, 'src="'.$attachment["src"].'"');
							$block_content = preg_replace('/alt="([^"]*)"/', 'alt="'.$attachment["alt"].'"', $block_content);
						}
						else {
							array_push($old, '<img alt=""/>');
							array_push($new, '<img src="'.$attachment["src"].'" alt="'.$attachment["alt"].'" class="wp-image-'.$attachment["id"].'"/>');
						}
						$block_content = str_replace($old, $new, $block_content);
						// set attributes
						$attrs['id'] = $attachment["id"];
					}

				}
			}
			// core @since 1.2.8
			if ($block['blockName'] === 'core/media-text') {
				
				if ($field['key'] == 'mediaId') {

					$attachment = self::get_attachment($field["value"]);

					// replace strings in block_content
					if ( !empty($attachment["src"]) ) {
						$old = array();
						$new = array();
						if ( isset($attrs['mediaId']) ) {

							$old_src = self::get_attribute($block_content, 'src');

							// default mediaType is image
							if ( !isset($attrs['mediaType']) || $attrs['mediaType'] == 'image' ) {
								
								if ( $attachment["type"] == 'image' ) {
									// replace image with image
									array_push($old, 'src="'.$old_src.'"');
									array_push($new, 'src="'.$attachment["src"].'"');
									array_push($old, 'url('.$old_src.')');
									array_push($new, 'url('.$attachment["src"].')');
									array_push($old, 'wp-image-'.$attrs['mediaId']);
									array_push($new, 'wp-image-'.$attachment["id"]);
									// insert alt tag
									$block_content = preg_replace('/alt="([^"]*)"/', 'alt="'.$attachment["alt"].'"', $block_content);
								}
								else if ( $attachment["type"] == 'video' ) {
									// replace image with video
									array_push($old, '<img src="'.$old_src.'" alt="" class="wp-image-'.$attrs['mediaId'].' size-full"/>');
									array_push($new, '<video controls src="'.$attachment["src"].'"></video>');
								}
								else {
									$new = false;
								}
							}
							else if ($attrs['mediaType'] == 'video') {
								
								if ( $attachment["type"] == 'image' ) {
									// replace video with image
									array_push($old, '<video controls src="'.$old_src.'"></video>');
									array_push($new, '<img src="'.$attachment["src"].'" alt="'.$attachment["alt"].'" class="wp-image-'.$attachment["id"].' size-full"/>');
								}
								else if ( $attachment["type"] == 'video' ) {
									// repace video with video (just src)
									array_push($old, 'src="'.$old_src.'"');
									array_push($new, 'src="'.$attachment["src"].'"');
								}
								else {
									$new = false;
								}
							}
							if ($new !== false) {
								// replace links
								array_push($old, 'href="'.$old_src.'"');
								array_push($new, 'href="'.$attachment["src"].'"');
								$oldlink = get_attachment_link($attrs['mediaId']);
								array_push($old, 'href="'.$oldlink.'"');
								array_push($new, 'href="'.$attachment["link"].'"');
							}
						}
						else {
							array_push($old, '<figure class="wp-block-media-text__media">');
							if ( $attachment["type"] == 'image' ) {
								array_push($new, '<figure class="wp-block-media-text__media"><img src="'.$attachment["src"].'" alt="'.$attachment["alt"].'" class="wp-image-'.$attachment["id"].' size-full"/>');
							}
							else if ( $attachment["type"] == 'video' ) {
								array_push($new, '<figure class="wp-block-media-text__media"><video controls src="'.$attachment["src"].'"></video>');
							}
							else {
								$new = false;
							}
						}
						if ($new !== false) {
							// replace strings in block_content
							$block_content = str_replace($old, $new, $block_content);
							// set attributes
							$attrs['mediaType'] = $attachment["type"];
							$attrs['mediaLink'] = $attachment["link"];
							$attrs['mediaId']   = $attachment["id"];
						}
					}

				}
				
				// focal point
				if ($field['key'] == 'focalPoint') {
					$focal_point = (array)json_decode(rawurldecode($field["value"]));
					$new_focal_point = intval($focal_point['x'] * 100).'% '.intval($focal_point['y'] * 100).'%';
					$old = '50% 50%';
					if ( isset($attrs['focalPoint']) && isset($attrs['focalPoint']['x']) && isset($attrs['focalPoint']['y']) ) {
						$old = intval($attrs['focalPoint']['x'] * 100).'% '.intval($attrs['focalPoint']['y'] * 100).'%';
					}
					$block_content = str_replace($old, $new_focal_point, $block_content);
					$attrs['focalPoint'] = $focal_point;
					// debug(htmlspecialchars($block_content));
				}
				
			}
			if ($block['blockName'] === 'core/cover') {

				// image source
				if ($field['key'] == 'id') {

					$attachment = self::get_attachment($field["value"]);

					// replace strings in block_content
					if ( !empty($attachment["src"]) ) {
						
						// prepare replacement arrays
						$old = array();
						$new = array();
						if ( isset($attrs['id']) ) {

							// replace old image/video
							$old_id  = $attrs['id'];
							$old_src = $attrs['url'];
							// always replace 'url()'
							array_push($old, 'url('.$old_src.')');
							array_push($new, 'url('.$attachment["src"].')');

							// always replace 'src'
							array_push($old, 'src="'.$old_src.'"');
							array_push($new, 'src="'.$attachment["src"].'"');
							
							// default backgroundType is image
							if ( !isset($attrs['backgroundType']) || $attrs['backgroundType'] == 'image' ) {
								
								if ( $attachment["type"] == 'image' ) {
									// replace image with image
									array_push($old, 'wp-image-'.$old_id);
									array_push($new, 'wp-image-'.$attachment["id"]);
									// insert alt tag
									$block_content = preg_replace('/alt="([^"]*)"/', 'alt="'.$attachment["alt"].'"', $block_content);
								}
								else if ( $attachment["type"] == 'video' ) {
									// replave image with video
									array_push($old, '<img class="wp-block-cover__image-background wp-image-'.$old_id.'" alt=""');
									array_push($new, '<video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline');
									array_push($old, '/>');
									array_push($new, '></video>');
									$attrs['backgroundType'] = 'video';
								}
								else {
									$new = false;
								}
							}
							else if ($attrs['backgroundType'] == 'video') {
								
								if ( $attachment["type"] == 'image' ) {
									// replace video with image
									array_push($old, '<video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline');
									array_push($new, '<img class="wp-block-cover__image-background wp-image-'.$attachment["id"].'" alt="'.$attachment["alt"].'"');
									array_push($old, '></video>');
									array_push($new, '/>');
									$attrs['backgroundType'] = 'image';
								}
								else if ( $attachment["type"] == 'video' ) {
									// repace video with video (just src)
								}
								else {
									$new = false;
								}
							}
						}
						else {
							// inject new image/video
							array_push($old, '</span><div');
							if ( $attachment["type"] == 'image' ) {
								// make image
								$data = 'data-object-fit="cover"';
								if ( isset($attrs['focalPoint']) ) {
									$focal_point = intval($attrs['focalPoint']['x'] * 100).'% '.intval($attrs['focalPoint']['y'] * 100).'%';
									$data = 'style="object-position:'.$focal_point.'" data-object-fit="cover" data-object-position="'.$focal_point.'"';
								}
								array_push($new,'</span><img class="wp-block-cover__image-background wp-image-'.$attachment["id"].'" alt="'.$attachment["alt"].'" src="'.$attachment["src"].'" '.$data.'/><div');
								$attrs['backgroundType'] = 'image';
							}
							else if ( $attachment["type"] == 'video' ) {
								// make video
								array_push($new, '</span><video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline src="'.$attachment["src"].'" data-object-fit="cover"></video><div');
								$attrs['backgroundType'] = 'video';
							}
							else {
								$new = false;
							}
						}
						if ($new !== false) {
							// replace strings in block_content
							$block_content = str_replace($old, $new, $block_content);
							// set attributes
							$attrs['backgroundType'] = $attachment["type"];
							$attrs['url'] = $attachment["src"];
							$attrs['id'] = $attachment["id"];
						}
					}

				}

				// focal point
				if ($field['key'] == 'focalPoint') {
					$focal_point = (array)json_decode(rawurldecode($field["value"]));
					$new_focal_point = intval($focal_point['x'] * 100).'% '.intval($focal_point['y'] * 100).'%';
					if (isset($attrs['focalPoint'])) {
						$old = intval($attrs['focalPoint']['x'] * 100).'% '.intval($attrs['focalPoint']['y'] * 100).'%';
						$new = $new_focal_point;
					}
					else {
						$old = 'data-object-fit="cover"';
						$new = 'style="object-position:'.$new_focal_point.'" data-object-fit="cover" data-object-position="'.$new_focal_point.'"';
					}
					$block_content = str_replace($old, $new, $block_content);
					$attrs['focalPoint'] = $focal_point;
				}
				// debug(htmlspecialchars($block_content));

			}
			if ($block['blockName'] === 'core/quote' || $block['blockName'] === 'core/pullquote' || $block['blockName'] === 'core/verse') {
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				// debug($field);
				$old = "";
				if ($field['key'] == 'value') {
					$result = self::strip_content($block_content, array('cite'));
					$result = self::strip_tags($result, array('blockquote', 'figure', 'p'));
					$old = '<p>'.$result.'</p>';
					$new = '<p>'.rawurldecode($field["value"]).'</p>';
				}
				if ($field['key'] == 'citation') {
					$result = self::strip_content($block_content, array('p'));
					$result = self::strip_tags($result, array('blockquote', 'figure', 'cite'));
					if (empty($result)) {
						$old = '</p></blockquote>';
						$new = '</p><cite>'.rawurldecode($field["value"]).'</cite></blockquote>';
					}
					else {
						$old = '<cite>'.$result.'</cite>';
						$new = '<cite>'.rawurldecode($field["value"]).'</cite>';
					}
				}
				if ($field['key'] == 'content') {
					$result = self::strip_tags($block_content, array('pre'));
					$old = '>'.$result.'<';
					$new = '>'.rawurldecode($field["value"]).'<';
				}
				if (!empty($old)) {
					$block_content = str_replace(array($old, '<p><p>', '</p></p>'), array($new, '<p>', '</p>'), $block_content);
				}
				// debug(htmlspecialchars($block_content));
			}
			// core @since 1.7.0
			if ($block['blockName'] === 'core/table-of-contents') {
				// debug($field);
				if ($field['key'] == 'rendered') {
					// debug($block);
					// debug(htmlspecialchars(rawurldecode($field["value"])));
					$block_content = rawurldecode($field["value"]);
				}
			}
			// core @since 1.11.0
			if ($block['blockName'] === 'core/navigation') {
				// debug($field);
				if ($field['key'] == 'ref') {
					// debug($block);
					// debug(htmlspecialchars($block_content));
					$attrs['ref'] = $field["value"];
				}
			}
			// core @since 2.8.0
			if ($block['blockName'] === 'core/group') {
				// debug($field);
				// debug(htmlspecialchars($block_content));
				// debug($attrs['style']['background']);

				// image source
				if ($field['key'] == 'style/background/backgroundImage/id') {
					// see columns and box
				}
				// focal point
				if ($field['key'] == 'style/background/backgroundPosition') {
					// debug(rawurldecode($field["value"]));
					$attrs['style']['background']['backgroundPosition'] = rawurldecode($field["value"]);
				}

			}
			
			// greyd
			if ($block['blockName'] === 'greyd/anim') {
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				if ($field['key'] == 'id') {
					$attrs['id'] = $field["value"];
					$attachment = get_post($field["value"]);
					$src = $attachment->guid;
					$result = self::get_attribute($block_content, 'data-src');
					$old = 'data-src="'.$result.'"';
					$new = 'data-src="'.$src.'"';
					if ( strpos($block_content, 'data-src') !== false ) {
						$block_content = str_replace($old, $new, $block_content);
					} else {
						/**
						 * @since 2.5.0 Parse animation when empty
						 */
						$attrs = wp_parse_args( $attrs, array(
							'greydClass' => 'greyd-anim-'.uniqid(),
							'anim' => 'auto',
							'w' => 'auto'
						) );
						$attrs['url'] = $src;
						$block_content = str_replace( '</div>', '<div id="'.$attrs['greydClass'].'" class="lottie-animation '.$attrs['anim'].'" style="width:'.$attrs['w'].'" data-anim="'.$attrs['anim'].'" data-src="'.$attrs['url'].'"></div>', $block_content );
					}
				}
			}
			if ($block['blockName'] === 'greyd/list-item') {
				// debug('greyd/list-item');
				// debug($attrs);
				// debug(esc_attr($block_content));
				if ($field['key'] == 'content') {
					$new = rawurldecode($field["value"]);
					if (is_array(json_decode($new))) {
						$new = self::make_rt_value(json_decode($new));
					}
					// debug(esc_attr($new));
					if ( empty($new) ) {
						$block_content = "";
					}
					else if ( strpos($block_content, '<span class="list_content"><p>') !== false ) {
						$block_content = preg_replace(
							"/(<span class=\"list_content\"><p>).*?(<\/p><\/span>)/",
							/**
							 * @since 1.7.5
							 * Wrap backreference in curly braces to avoid confusion
							 * with numbers inside the content of list items.
							 */
							'${1}' . $new . '${2}',
							$block_content
						);


					}
					// debug($fields);
					// debug($field);
					// debug($attrs);
					// debug(esc_attr($block_content));
				}
			}
			if ($block['blockName'] === 'greyd/dynamic') {
				// debug('render dynamic block');
				// debug($fields);
				// debug($field);
				// debug($attrs);
			}
			
			// new greyd blocks
			if ($block['blockName'] === 'greyd/button' || $block['blockName'] === "greyd/popover-button") {
				if ($field['key'] == 'content') {

					$new = '>'.rawurldecode($field["value"]).'<';

					/**
					 * When neither a content isset not an icon is present, we remove the block.
					 */
					if ( $new == '><' && ( !isset($attrs['icon']) || empty($attrs['icon']['content']) ) ) {
						$block_content = "";
						$attrs['content'] = "";
					}
					else {
						$old = '>'.(isset($attrs['content']) ? $attrs['content'] : '').'<';

						if ( $new !== $old ) {

							if ( $old === '><' ) {
								/**
								 * When an icon is present in the button, the text content is contained
								 * in a span with flex:1. We specifically target this span to replace
								 * the content.
								 * @since 1.5.6
								 */
								if ( strpos($block_content, '<span style="flex:1"></span>') !== false ) {
									$block_content = str_replace(
										'<span style="flex:1"></span>',
										'<span style="flex:1"'.$new.'/span>',
										$block_content
									);
								}
								else if ( strpos($block_content, '<span style="flex:0 1 auto"></span>') !== false ) {
									$block_content = str_replace(
										'<span style="flex:0 1 auto"></span>',
										'<span style="flex:0 1 auto"'.$new.'/span>',
										$block_content
									);
								}
								else if ( $block['blockName'] === "greyd/popover-button" && strpos($block_content, '<span></span>') !== false ) {
									$block_content = str_replace(
										'<span></span>',
										'<span'.$new.'/span>',
										$block_content
									);
								}
								/**
								 * Otherwise no icon is present, so the text content takes up the entire
								 * content of the button. We target the entire content to replace it.
								 * @since 1.5.6
								 */
								else {
									$block_content = preg_replace_callback( "/^(?<start><.+?)><(?<end>\/[a-z1-9]>(?:<style.+<\/style>$|$))/", function($matches) use ($new, $old) {
										return $matches['start'].$new.$matches['end'];
									}, trim($block_content) );
								}
							}
							else {
								/**
								 * We wrap the replace function into a Regex pattern to only replace the
								 * content of the button, and not the entire block.
								 * @since 1.5.6
								 */
								$block_content = preg_replace_callback( "/^(?<start><.+?)(?<content>>.+<)(?<end>\/[a-z1-9]+?>(?:<style.+<\/style>$|$))/", function($matches) use ($new, $old) {
									return $matches['start'] . str_replace($old, $new, $matches['content']) . $matches['end'];

								}, trim($block_content) );
							}
							
							// set the attribute
							$attrs['content'] = rawurldecode($field["value"]);
						}
					}
					// debug($fields);
					// debug($field);
					// debug($attrs);
					// debug(htmlspecialchars($block_content));
				}
			}
			if ($block['blockName'] === 'greyd/image') {
				if ( $field['key'] == 'image' || $field['key'] == 'focalPoint') {
					$value = rawurldecode( $field["value"] );
					$json = json_decode( $value );
					if ( json_last_error() == 0 ) $value = $json;
					$attrs[ $field['key'] ] = (array)$value;
					// $attrs[ $field['key'] ] = (array) json_decode( rawurldecode( $field["value"] ) );
				}
				if ( $field['key'] == 'sizeSlug' ) {
					$attrs[ $field['key'] ] = $field["value"];
				}
				// debug($field);
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
			}
			if ($block['blockName'] === 'greyd/menu') {
				if ($field['key'] == 'menu') {
					$attrs['menu'] = $field["value"];
				}
				// debug($field);
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
				//
			}
			if ($block['blockName'] === 'greyd/search') {
				//
			}
			if ($block['blockName'] === 'greyd/box') {
				// same as core/columns
			}
			if ($block['blockName'] === 'greyd/form') {
				// debug($attrs);
				// debug($block_content);
				if ($field['key'] == 'id') {
					$attrs['id'] = $field["value"];
				}
			}
			if ($block['blockName'] === 'greyd/accordion-item') {
				if ($field['key'] == 'title') {

					if ( ! isset( $attrs['title'] ) ) {
						$block_content = preg_replace(
							'/wp-block-greyd-accordion__title([^"]*?)"([^>]*?)><span>(.*?)<\/span><span class="icon/',
							'wp-block-greyd-accordion__title$1"$2><span>'.rawurldecode($field["value"]).'</span><span class="icon',
							$block_content
						);
					}
					else {
						$block_content = str_replace(
							'>'.$attrs['title'].'<',
							'>'.rawurldecode($field["value"]).'<',
							$block_content
						);
					}
				}
			}
			if ($block['blockName'] === 'greyd/anchor') {
				// debug($field);
				if ($field['key'] == 'anchor') {
					$attrs['anchor'] = urldecode( $field["value"] );
				}
				// debug($attrs);
			}

			// if ($block['blockName'] === "greyd/popover-button") {
			// 	if ($field['key'] == 'content') {							
			// 		// set the attribute
			// 		$block_content = rawurldecode($field["value"]);
			// 	}
			// }
			
			if ($field['key'] == 'trigger') {
				$attrs['trigger'] = (array)json_decode(rawurldecode($field["value"]));
				if (isset($attrs['trigger']['params']) && is_object($attrs['trigger']['params'])) $attrs['trigger']['params'] = (array)$attrs['trigger']['params'];
				// debug($field);
				// debug($attrs);
				// debug(htmlspecialchars($block_content));
			}
			if ($field['key'] == 'greydStyles/maxWidth') {
				// debug(rawurldecode($field["value"]));
				// debug($attrs);
				$attrs['greydStyles']['maxWidth'] = rawurldecode($field["value"]);
			}

		}

		// debug($attrs);
		// debug($block_content);

		return array( 'attrs' => $attrs, 'innerHTML' => $block_content );
	}


	/**
	 * =================================================================
	 *                          Dynamic Content Helper
	 * =================================================================
	 */

	/**
	 * Strip HTML Tags with their entire content from string.
	 * 
	 * @param string $content   Content where the tags should be replaced.
	 * @param array $tags       HTML tags to replace.
	 * 
	 * @return string
	 */
	static function strip_content($content, $tags) {
		$output = $content;
		foreach ($tags as $tag) {
			$patterns = array(
				'/<'.$tag.'[^>]*>.*<\/'.$tag.'>/'
			);
			$output = preg_replace($patterns, "", $output);
		}
		return trim($output);
	}

	/**
	 * Strip HTML Tags from string.
	 * 
	 * @param string $content   Content where the tags should be replaced.
	 * @param array $tags       HTML tags to replace.
	 * 
	 * @return string
	 */
	static function strip_tags($content, $tags) {
		$output = trim( strval( $content ) );
		foreach ( (array) $tags as $tag ) {
			$patterns = array(
				'/<'.$tag.'[^>]*>/',
				'/<\/'.$tag.'>/'
			);
			$output = preg_replace( $patterns, "", $output );
		}
		/**
		 * Bugfix @since 1.3.0
		 * 
		 * trim() leads to errors matching dynamic fields, because it also
		 * stripped leading and ending whitespaces away. That way the field
		 * content cannot be replaced properly.
		 * @example
		 * * @param $content                '<p>\nLorem ipsum. \n</p>'
		 * * @var $output with trim():      'Lorem ipsum.'
		 * * @var $output without trim():   'Lorem ipsum. '
		 * The trailing whitespace is important because otherwise the field
		 * value would not be matched inside self::match_block_fields()
		 */
		return preg_replace( '/(^\n|\n$)/', '', $output );
		// return trim($output);
	}

	/**
	 * Get value of Attribute
	 * 
	 * @param string $content   Content.
	 * @param array $att		The Attribute to search for.
	 * 
	 * @return string
	 */
	static function get_attribute($content, $att) {
		$value = "";
		preg_match('/(?<='.$att.'=")([^"]+)(?=")/', $content, $result);
		if (count($result) > 0) {
			$value = $result[0];
		}
		return $value;
	}

	/**
	 * Render RichText string from array
	 * 
	 * @param array $value	RichText value saved in array
	 * 
	 * @return string
	 */
	static function make_rt_value($value) {
		$string = "";
		$self_enclosing_tags = array( 'area' => 'area', 'base' => 'base', 'br' => 'br', 'col' => 'col', 'embed' => 'embed', 'hr' => 'hr', 'img' => 'img', 'input' => 'input', 'link' => 'link', 'meta' => 'meta', 'param' => 'param', 'source' => 'source', 'track' => 'track', 'wbr' => 'wbr' );
		foreach ($value as $item) {
			if (is_object($item)) {
				$tag = $item->type;
				$atts = "";
				foreach ($item->props as $key => $val) {
					if ($key == 'children') continue;
					$atts .= ' '.$key.'="'.$val.'"';
				}
				$children = empty($item->props->children) ? '' : self::make_rt_value($item->props->children);
				if ( empty($children) && isset($self_enclosing_tags[$tag]) ) {
					$string .= '<'.$tag.$atts.'/>';
				} else {
					$string .= '<'.$tag.$atts.'>'.$children.'</'.$tag.'>';
				}
			}
			if (is_string($item)) $string .= $item;
		}
		return $string;
	}

	public static function get_attachment($value) {
		
		$attachment_id   = $value;
		$attachment_src  = '';
		$attachment_link = '';
		$attachment_alt  = '';
		$attachment_type = 'image';

		// value is a post ID
		if ( is_numeric($attachment_id) ) {
			$attachment = get_post( $attachment_id );
			if ( $attachment ) {
				$attachment_link = get_attachment_link($attachment_id);
				$attachment_alt  = Helper::get_attachment_text( $attachment_id );
				$attachment_type = strpos( $attachment->post_mime_type, 'video/' ) === 0 ? 'video' : 'image';
				if ( $attachment_type == 'image' ) {
					$attachment_src  = wp_get_attachment_image_src( $attachment->ID, 'full' );
					$attachment_src  = isset($attachment_src[0]) ? $attachment_src[0] : $attachment->guid;
				}
				else {
					$attachment_src  = $attachment->guid;
				}
			}
		}
		// value is a URL
		else {
			$attachment_id   = 0;
			$attachment_src  = rawurldecode( rawurldecode( $value ) );
			$attachment_link = $attachment_src;

			// decide which mime type based on file extension
			$ext = pathinfo( $attachment_src, PATHINFO_EXTENSION );
			$attachment_type = in_array( $ext, array('mp4', 'webm', 'ogg') ) ? 'video' : 'image';
		}

		return array(
			"id" => $attachment_id,
			"src" => $attachment_src,
			"link" => $attachment_link,
			"alt" => $attachment_alt,
			"type" => $attachment_type
		);
	}


	/**
	 * =================================================================
	 *                          Render
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
	 *      @property string html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public static function render_block($content, $block) {
		// debug("render list");

		$block_content = $content['block_content'];
		$html_atts     = $content['html_atts'];
		$style         = $content['style'];

		// dynamic block
		if ( $block['blockName'] === 'greyd/dynamic' ) {

			/**
			 * Set the class 'dynamic' to use the default wrapper
			 */
			if ( isset( $block['attrs']['className'] ) && !empty( $block['attrs']['className'] ) ) {
				$html_atts['class'] = 'dynamic ' . $block['attrs']['className'];
			}
			else {
				/**
				 * @since 1.6.6 Add dynamic class if ID isset
				 */
				if ( ! empty( $html_atts ) ) {
					$html_atts['class'] = 'dynamic';
				}
				/**
				 * @since 1.2.10: add class if it has a local trigger inside.
				 */
				else if (
					preg_match( '/greyd_trigger\.trigger\([^\)]+false\)/', $block_content ) ||
					preg_match( '/greyd\.trigger\.trigger\([^\)]+false\)/', $block_content )
				) {
					$html_atts['class'] = 'dynamic';
				}
				/**
				 * @since 2.17.5: add unique id and dynamic class if inline_css is set
				 */
				else if ( isset($block['attrs']['inline_css']) && !empty($block['attrs']['inline_css']) ) {
					$html_atts['id'] = 'greyd-dyn-' . uniqid();
					$html_atts['class'] = 'dynamic';
				}
				/**
				 * @since 1.8.4 Do not add the dynamic class by default.
				 */
				// else {
				// $html_atts['class'] = 'dynamic';
				// }
			}
		}

		// match dynamic tags
		$block_content = self::match_dynamic_tags($block, $block_content);
		// debug($block_content);

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);

	}

	/**
	 * Match all Dynamic Tags in Block Content
	 * 
	 * @param object $block             Parsed block.
	 * @param string $block_content     Pre-rendered block content.
	 * @param WP_Post $wp_post          Post object.
	 * 
	 * @return string $block_content    altered Block Content
	 */
	public static function match_dynamic_tags( $block, $block_content, $wp_post=null ) {

		// do not process during ajax
		if ( strpos($_SERVER['REQUEST_URI'], "/wp-json/wp/v2") !== false ) return $block_content;

		// block content is an encoded JSON object.
		if ( substr( $block_content, 0, 2 ) === '{"' ) {
			return self::match_dynamic_json_tag( $block_content, $wp_post );
		}

		/**
		 * After removing prematching of dynamic tags, 
		 * this is needed to support simple dynamic tags (eg. _categories_).
		 * @since 2.14.0
		 */
		if ( empty($wp_post) ) $wp_post = get_post();
		if ( $wp_post ) {
			$dtags = Dynamic_Helper::get_dynamic_posttype_tags($wp_post);
			$block_content = \Greyd\Dynamic\Render::match_tags($wp_post, $dtags, $block_content);
			$tags = Dynamic_Helper::get_dynamic_tags('single');
			$block_content = \Greyd\Dynamic\Render::match_tags($wp_post, $tags, $block_content);
		}

		// sometimes the quotes in the dynamic tag markup are escaped.
		// if that is the case, we manually "unescape" before matching the dynamic tag to make them render again.
		if ( strpos( $block_content, '\\"is-tag\\"' ) > 0 ) {
			// debug(htmlentities($block_content));
			$block_content = str_replace( '\\"', '"', $block_content );
		}

		// replace dynamic tags saved as spans
		return preg_replace_callback(
			'/<span data-tag="(?<tagName>[^"]+?)" data-params=(?>"(?<params>[^"]*?)"|\'(?<params2>[^\']*?)\') class="is-tag">(?<innerHTML>.*?)<\/span>/',
			function ( $matches ) use ( $block, $wp_post ) {

				$tagName = $matches[ 'tagName' ];
				$params  = !empty($matches['params2']) ? $matches[ 'params2' ] : $matches[ 'params' ];

				$html = self::render_dynamic_tag( $block, $tagName, $params, $wp_post );
				
				/**
				 * @since 1.5.0: Preserve formats set within the RichText editor.
				 * 
				 * @example '<span data-tag="title" class="is-tag"><em>Post Title</em></span>'
				 * @return  '<em>Hello world!</em>'
				 */
				if ( preg_match_all( '/<(?<tagName>[a-zA-Z][a-zA-Z0-9]*)(?<args>\b[^>]*?)>.*?/', $matches['innerHTML'], $innerHTML ) ) {
					foreach ( $innerHTML['tagName'] as $i => $tag ) {
						if ( !empty($tag) ) {
							$args = $innerHTML['args'][$i];
							$html = '<'.$tag.$args.'>'.$html.'</'.$tag.'>';
						}
					}
				}

				/**
				 * @filter greyd_match_dynamic_tag_{{tagName}}
				 * 
				 * @param string $html      HTML content of the parsed tag.
	 			 * @param string $params    Dynamic Tag Paras as json string.
	 			 * @param object $block     Parsed Block.
	 			 * @param WP_Post $wp_post  Post object.
				 * 
				 * @return string $html
				 */
				return apply_filters( 'greyd_match_dynamic_tag_'.$tagName, $html, $params, $block, $wp_post );
			},
			$block_content
		);
	}

	/**
	 * Match all JSON encoded Dynamic Tags.
	 * 
	 * @param string $block_content     Pre-rendered block content.
	 * @param WP_Post $wp_post          Post object.
	 * 
	 * @return string $block_content    altered Block Content
	 */
	public static function match_dynamic_json_tag( $block_content, $wp_post ) {

		// try decoding
		$data = json_decode($block_content);

		if (
			$data
			&& is_object($data)
			&& isset($data->type)
			&& $data->type == "dynamic"
		) {
			if ( isset($data->tag) && !empty($data->tag) ) {
				// debug( "dynamic image found: ".$data->tag );
				$url = self::get_dynamic_url($data->tag, null, $wp_post);
				$data->type = "image";
				$data->url = $url;
				$data->tag = "";
				$data->id = -1;
				return json_encode($data);
			}
			else if ( isset($data->params) ) {
				// debug("dynamic trigger found");
				if (!$data->params->tag == "woo_ajax") {
					$url = self::get_dynamic_url($data->params->tag, null, $wp_post);
					$data->type = "link";
					$data->params->url = $url;
					$data->params->tag = "";
				}
				return json_encode($data);
			}
		}

		return $block_content;
	}

	/**
	 * Render Dynamic Tag
	 * 
	 * @param object $block     parsed Block
	 * @param string $dtag      Dynamic Tag
	 * @param string $params    Dynamic Tag Paras as json string
	 * 
	 * @return string $new      Rendered Dynamic Tag
	 */
	public static function render_dynamic_tag( $block, $dtag, $params, $wp_post=null ) {
		$new = "";
		$params = json_decode(stripslashes(html_entity_decode($params)), true);
		if ( isset($params['linkStyle']) && $params['linkStyle'] != '' ) {
			// fix button style class
			$params['linkStyle'] = preg_replace( '/button (prim|sec|trd|clear)/', 'button is-style-$1', $params['linkStyle'] );
		}
		// debug($params);
		$is_woo = is_plugin_active( 'woocommerce/woocommerce.php' );

		// website dtags or global search
		if (strpos($dtag, 'site-') === 0) {

			// if ( isset($wp_post) ) debug( $wp_post );

			if ($dtag == 'site-title') {
				$new = Dynamic_Helper::get_site_title( empty($wp_post) ? get_post() : $wp_post );
			}
			else if ($dtag == 'site-url') {
				$new = Dynamic_Helper::get_site_url( empty($wp_post) ? get_post() : $wp_post );
			}
			else if ($dtag == 'site-sub') {
				$new = get_bloginfo('description');
			}

			// make them a link
			if ($new != "" && isset($params['isLink']) && $params['isLink']) {
				$class  = isset($params['linkStyle']) && $params['linkStyle'] != '' ? 'class="'.$params['linkStyle'].'"' : '';
				$href   = Helper::get_home_url();
				$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
				$new    = '<a '.$class.' href="'.$href.'" '.$target.' rel="home">'.$new.'</a>';
			}

			if ($dtag == 'site-logo') {
				$pid = get_theme_mod( 'custom_logo', -1 );
				if ($pid != -1) {
					$psrc = wp_get_attachment_image_src( $pid , 'full' )[0];
					$ptitle = get_the_title($pid);
					$pwidth = isset($params['width']) && $params['width'] != '' ? 'style="width:'.$params['width'].'"' : '';
					$palt = Helper::get_attachment_text($pid);
					$new = '<img loading="lazy" class="wp-image-'.$pid.'" '.$pwidth.' src="'.$psrc.'" title="'.$ptitle.'" alt="'.$palt.'" >';
					if (isset($params['isLink']) && $params['isLink']) {
						$href = Helper::get_home_url();
						$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
						$new = '<a href="'.$href.'" '.$target.' rel="home">'.$new.'</a>';
					}
				}
			}
			// ...
		}
		// user info
		else if (strpos($dtag, 'user-') === 0) {

			$wp_user = wp_get_current_user();

			if ( !is_a( $wp_user, '\WP_User' ) || !isset($wp_user->data) ) {
				$new = $dtag == 'user-roles' ? __("Not logged in", 'greyd_hub') : '';
			}
			else if ($dtag == 'user-name') {
				if ( isset( $wp_user->data->display_name ) ) {
					$new = $wp_user->data->display_name;
				}
				else if ( isset( $wp_user->data->user_nicename ) ) {
					$new = $wp_user->data->user_nicename;
				}
				else if ( isset( $wp_user->data->user_login ) ) {
					$new = $wp_user->data->user_login;
				}
				else {
					$new = '';
				}
				$new = ucwords( $new );
			}
			else if ($dtag == 'user-email') {
				$new = isset( $wp_user->data->user_email ) ? $wp_user->data->user_email : '';
			}
			else if ($dtag == 'user-roles') {
				$new = implode( ', ', array_map( 'ucfirst', $wp_user->roles ) );
			}
		}
		// basic dtags
		else {
			if ($dtag == 'now') {
				$prms = $dtag;
				if (isset($params['format'])) $prms .= '|'.$params['format'];
				$new = Dynamic_Helper::make_date_string( (object)array( 'post_date' => date('Y-m-d H:i:s') ), $prms);
			}
			else if ($dtag == 'symbol') {
				if (isset($params['symbol'])) {
					if ($params['symbol'] == 'copyright')   $new = "©";
					if ($params['symbol'] == 'arrow-up')    $new = "↑";
					if ($params['symbol'] == 'arrow-down')  $new = "↓";
					if ($params['symbol'] == 'arrow-left')  $new = "←";
					if ($params['symbol'] == 'arrow-right') $new = "→";
					// ...
				}
			}
			else if ($dtag == 'count') {
				if (isset($params['format'])) {
					if ($params['format'] == 'posttype' && isset($params['posttype'])) {
						$posttype = isset($params['posttype']) ? esc_attr($params['posttype']) : "";
						if (!empty($posttype) && post_type_exists($posttype)) {
							$new = wp_count_posts($posttype)->publish;
							if (isset($params['taxonomy'])) {
								$taxonomy = esc_attr($params['taxonomy']);
								if ( !empty($taxonomy) && taxonomy_exists($taxonomy) ) {
									$new  = "0";
									$term   = isset($params['term']) ? esc_attr($params['term']) : "";
									if ( !empty($term) ) {
										$wp_term = get_term($term);
										if (!is_wp_error($wp_term) && !empty($wp_term)) $new = $wp_term->count;
									}
								}
							}
						}
					}
					// ...
				}
			}
			else {
				// classic dtags
				$post = empty($wp_post) ? get_post() : $wp_post;
				// debug($post->ID);
				// $params = $dtag;
				if ($dtag == 'post-type') {
					$posttype = get_post_type_object($post->post_type);
					if ( $posttype && is_object($posttype) && isset($posttype->labels) ) {
						$new = $posttype->labels->singular_name;
					} else {
						$new = $post->post_type;
					}
				}
				else if ($dtag == 'title') {
					$new = Dynamic_Helper::get_the_title($post);
					if (isset($params['isLink']) && $params['isLink']) {
						$class = isset($params['linkStyle']) && $params['linkStyle'] != '' ? 'class="'.$params['linkStyle'].'"' : '';
						$href = get_the_permalink($post);
						$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
						$new = '<a '.$class.' href="'.$href.'" '.$target.' rel="home">'.$new.'</a>';
					}
				}
				else if ($dtag == 'author') {
					$post_author = !empty($post) ? $post->post_author : false;
					if ( empty($post) && is_author() ) {
						global $wp_query;
						// debug($wp_query);
						$post_author = $wp_query->query_vars['author'];
					}
					$new = get_the_author_meta('display_name', $post_author);
					if (isset($params['format']) && $params['format'] != '') {
						if ($params['format'] == 'avatar') {
							$width = isset($params['width']) ? intval($params['width']) : 24;
							$new = get_avatar(get_the_author_meta('ID', $post_author), $width);
						}
						else $new = get_the_author_meta($params['format'], $post_author);
					}
					if (isset($params['isLink']) && $params['isLink']) {
						$email = get_the_author_meta('user_email', $post_author);
						$url = get_author_posts_url($post_author);
						$class = isset($params['linkStyle']) && $params['linkStyle'] != '' ? 'class="'.$params['linkStyle'].'"' : '';
						$href = isset($params['format']) && $params['format'] == 'user_email' ? 'mailto:'.$email : $url;
						$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
						$new = '<a '.$class.' href="'.$href.'" '.$target.'>'.$new.'</a>';
					}
					// debug(htmlspecialchars($old));
					// debug(htmlspecialchars($new));
				}
				else if ($dtag == 'image') {
					$pid = get_post_thumbnail_id($post);
					$psrc = get_the_post_thumbnail_url($post);
					$ptitle = get_the_title($pid);
					$pwidth = isset($params['width']) && $params['width'] != '' ? 'style="width:'.$params['width'].'"' : '';
					$palt = Helper::get_attachment_text($pid);
					$new = '<img loading="lazy" class="wp-image-'.$pid.'" '.$pwidth.' src="'.$psrc.'" title="'.$ptitle.'" alt="'.$palt.'" >';
					if (isset($params['isLink']) && $params['isLink']) {
						$href = get_the_permalink($post);
						$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
						$new = '<a href="'.$href.'" '.$target.'>'.$new.'</a>';
					}
				}
				else if ($dtag == 'date') {
					$prms = $dtag;
					if (isset($params['format'])) $prms .= '|'.$params['format'];
					if (isset($params['customFormat'])) $prms .= '|'.$params['customFormat'];
					$new = Dynamic_Helper::make_date_string($post, $prms);
				}
				else if ($dtag == 'content') {
					/**
					 * Don't parse the content when in a REST request.
					 * 
					 * This could lead to an error on where single-templates cannot be
					 * safed because the wp-block-parser is running into a max execution
					 * timeout.
					 * 
					 * @since 1.1.2
					 */

					// we need the wp-block-parser for live query requests
					$allowed_routes = array( "/greyd/v1/livequery/", "/greyd/v1/livequery", "/greyd/v1/livequeries/", "/greyd/v1/livequeries" );
					$is_livequery_request = isset($_REQUEST['rest_route']) && in_array( $_REQUEST['rest_route'], $allowed_routes );

					if ( ! Helper::is_rest_request() || $is_livequery_request ) {
						$prms = $dtag;
						if (isset($params['count'])) $prms .= '|'.$params['count'];
						$new = Dynamic_Helper::make_content_string($post, $prms);
					}
				}
				else if ($dtag == 'categories' || $dtag == 'tags') {
					$prms = $dtag;
					if (isset($params['format'])) $prms .= '|'.$params['format'];
					else $prms .= '|';
					if (isset($params['isLink']) && $params['isLink']) $prms .= '|1';

					if ($dtag == 'categories') $new = Dynamic_Helper::make_categories_string($post, $params);
					if ($dtag == 'tags') $new = Dynamic_Helper::make_tags_string($post, $params);
				}
				else if ($dtag == 'archive-title') {
					$new = get_the_archive_title();
					$prms = $dtag;
					if ( isset($params['hidePrefix']) && $params['hidePrefix'] && strpos($new, ":") !== false ) {
						$new = trim( explode(":", $new, 2)[1] );
					}
				}
				else if ($dtag == 'excerpt') {
					$strip_tags = isset($params['dontStripTags']) && $params['dontStripTags'] ? false : true;
					$new = Dynamic_Helper::get_the_excerpt($post, $strip_tags);
				}
				else if ($dtag == 'post-url') {
					$new = Dynamic_Helper::get_post_permalink($post);

					// make a link
					if ($new != "" && isset($params['isLink']) && $params['isLink']) {
						$class  = isset($params['linkStyle']) && $params['linkStyle'] != '' ? 'class="'.$params['linkStyle'].'"' : '';
						$href   = Helper::get_home_url();
						$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
						// $new    = '<a '.$class.' href="'.$href.'" '.$target.' rel="home">'.$new.'</a>';
						$new    = '<a '.$class.' href="'.$new.'" '.$target.'>'.$new.'</a>';
					}
				}
				// advanced search
				else if ($dtag == 'post-views') {
					$new = Dynamic_Helper::get_post_views($post);
				}
				// woo
				else if ( $is_woo && $dtag == 'price' ) {
					$new = Dynamic_Helper::get_woo_price($post, $params);
				}
				else if ( $is_woo && $dtag == 'sale-badge' ) {
					$new = Dynamic_Helper::get_woo_sale_flash( $post, $params );
				}
				else if ( $is_woo && $dtag == 'vat' ) {
					$new = Dynamic_Helper::make_woo_vat($post, "");
				}
				else if ( $is_woo && $dtag == 'cart_excerpt' ) {
					if ( is_plugin_active('woocommerce-germanized/woocommerce-germanized.php') ) {
						$new = Dynamic_Helper::make_woo_cart_excerpt($post, "");
					}
				}
				/**
				 * Custom WooCommerce Germanized dynamic tags.
				 * 
				 * @since 1.1.2
				 */
				else if ( $is_woo && ( strpos($dtag, "gzd_") === 0 || strpos($dtag, "gzd-") === 0 ) ) {
					if ( is_plugin_active('woocommerce-germanized/woocommerce-germanized.php') ) {

						$shortcode_tag = str_replace('-', '_', $dtag);

						/**
						 * Render germanized shortcodes.
						 * @see https://vendidero.de/dokument/preisauszeichnungen-anpassen
						 */
						$new = trim( do_shortcode( "[$shortcode_tag  product=\"{$post->ID}\"]" ) );

						/**
						 * Convert <p> tags into <span> tags to enable inline embedding
						 * into headlines, paragraphs...
						 */
						if ( !empty( $new ) && substr( $new, 0, 2 ) == "<p" && substr( $new, -2 ) == "p>" ) {
							$new = "<span" . substr( substr( $new, 0, -2 ), 2 ) . "span>";
						}
					}
				}
				else if (strpos($dtag, 'taxonomy-') === 0) {
					$dtags = Dynamic_Helper::get_dynamic_posttype_tags( $post, array( 'rawurlencode' => false ) );
					if ( isset($dtags[$dtag]) ) {
						$prms = $dtag;
						if (isset($params['format'])) $prms .= '|'.$params['format'];
						if (isset($params['isLink']) && $params['isLink']) $prms .= '|1';

						$new = call_user_func( $dtags[$dtag], $post, $params, $dtag );
					}
				}
 				else {
					// debug($block['attrs']);
					// loop/archives
					if (isset($block['attrs']['queryTags']) && isset($block['attrs']['queryTags'][$dtag])) {
						// debug($block);
						// 'category'
						// 'tag'
						// 'post-count'
						// 'posts-per-page'
						// 'page-num'
						// 'page-count'
						// search
						// 'query'
						// 'filter'
						$new = $block['attrs']['queryTags'][$dtag];
					}
					else {
						// dpt
						// debug($dtag);
						$dtags = Dynamic_Helper::get_dynamic_posttype_tags( $post, array( 'rawurlencode' => false ) );
						if (isset($dtags[$dtag])) {
							if (strpos($dtag, 'customtax') === 0) {
								// custom tax
								$prms = $dtag;
								if (isset($params['format'])) $prms .= '|'.$params['format'];
								if (isset($params['isLink']) && $params['isLink']) $prms .= '|1';
								$new = call_user_func( $dtags[$dtag], $post, $prms, $dtag );
							}
							else {
								// custom field
								$pid = $dtags[$dtag];
								if (intval($pid) == $pid && isset($dtags[$dtag.'-link'])) {
									// image
									$psrc = urldecode($dtags[$dtag.'-link']);
									$ptitle = get_the_title($pid);
									$palt = Helper::get_attachment_text($pid);
									$new = '<img loading="lazy" class="wp-image-'.$pid.'" src="'.$psrc.'" title="'.$ptitle.'" alt="'.$palt.'" >';
								}
								else {
									$new         = $dtags[$dtag];
									$new_decoded = html_entity_decode( $new );

									// The strip_tags function removes all HTML tags from a string.
									// If the stripped version is different from the original string, it contains HTML.
									if ( $new !== strip_tags( $new_decoded ) ) {
										// if the custom field is an html string, we need to make sure,
										// that before we apply nl2br(), we remove all line breaks that are
										// directly in between two html tags.
										// otherwise we would have a line break after each tag,
										// eg. <ul> <li>...</li> <br> <li>...</li> </ul>
										$new = preg_replace( '/>([ \t]+)?[\n\r]+([ \t]+)?</', '><', $new_decoded );
									}

									$new = self::render_dynamic_posttype_tag_params( $new, $dtag, $params, $post->post_type, $post->ID );
									$new = nl2br( $new );
									$new = do_shortcode( $new );
								}
							}
						}
						else {
							// $qtags = self::get_query_tags( [ 'query' => [ 'inherit' => true ] ] );
							$qtags = \Greyd\Query\Render::get_query_tags( [ 'query' => [ 'inherit' => true ] ] );
							if ( isset( $qtags[$dtag] ) ) {
								$new = $qtags[$dtag];
							}
						}
					}
				}
				// else {
				// 	global $wp_query;
				// 	debug($wp_query);
				// }

			}
		}

		/**
		 * @filter greyd_render_dynamic_tag
		 * 
		 * @param string $html      HTML content of the parsed tag (default: "").
		 * @param string $name      Name of the dynamic tag (eg. "title")
		 * @param string $params    Dynamic Tag Params as json string.
		 * @param object $block     Parsed Block.
		 * @param WP_Post $wp_post  Post object.
		 * 
		 * @return string $html
		 */
		return apply_filters( 'greyd_render_dynamic_tag', $new, $dtag, $params, $block, $wp_post );
	}

	/**
	 * Render dynamic Tag from dynamic posttype field with Params.
	 * 
	 * @param string $new       HTML content of the parsed tag (default: "").
	 * @param string $dtag      Name of the dynamic tag (eg. "title")
	 * @param string $params    Dynamic Tag Params as json string.
	 * @param string $post_type Dynamic Post Type slug.
	 * @param string $post_id   Post object ID.
	 */
	public static function render_dynamic_posttype_tag_params( $new, $dtag, $params, $post_type, $post_id ) {

		if ( $new != "" && !empty($params) ) {
			// get field type
			$type = false;
			$posttype = \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttype_by_slug( $post_type );
			if ( $posttype && isset($posttype['fields']) && !empty($posttype['fields']) ) {
				foreach ( $posttype['fields'] as $field ) {
					if ( $field['name'] == $dtag ) {
						$type = $field['type'];
						break;
					}
				}
			}
			// render date format
			if ( $type == 'date' || $type == 'datetime-local' ) {
				$prms = $dtag;
				if (isset($params['format'])) $prms .= '|'.$params['format'];
				if (isset($params['customFormat'])) $prms .= '|'.$params['customFormat'];

				// get raw date value before rendering
				$raw_values = \Greyd\Posttypes\Posttype_Helper::get_dynamic_values( $post_id, array(
					'raw_date' => true,
				) );
				$raw_date_value = isset($raw_values[$dtag]) ? $raw_values[$dtag] : $new;

				$new = Dynamic_Helper::make_date_string( (object)array( "post_date" => $raw_date_value ), $prms );
			}
			// make a link (email or url)
			else if (
				( $type == 'email' || $type == 'url' ) &&
				isset($params['isLink']) && $params['isLink']
			) {
				$class  = isset($params['linkStyle']) && $params['linkStyle'] != '' ? 'class="'.$params['linkStyle'].'"' : '';
				$href   = $type == 'email' ? 'mailto:'.$new : $new;
				$target = isset($params['blank']) && $params['blank'] ? 'target="_blank"' : '';
				$new    = '<a '.$class.' href="'.$href.'" '.$target.'>'.$new.'</a>';
			}
		}

		return $new;
	}

	/**
	 * Get Dynamic URL
	 * 
	 * @param string $dtag      Dynamic Tag
	 * @param object $block     parsed Block
	 * 
	 * @return string $url      Rendered Dynamic URL
	 */
	public static function get_dynamic_url( $dtag, $block=null, $wp_post=null ) {

		$post = $wp_post ? (object) $wp_post : get_post();
		$url = '';
	
		if ($dtag == 'home') {
			$url = Helper::get_home_url();
		}
		else if ($dtag == 'site-logo') {
			$pid = get_theme_mod( 'custom_logo', -1 );
			if ($pid != -1) $url = wp_get_attachment_image_src( $pid , 'full' )[0];
		}
		else if ($dtag == 'post') {
			// $url = method_exists('Dynamic_Helper', 'get_post_permalink_encoded') ? Dynamic_Helper::get_post_permalink($post) : ( isset($post->permalink) ? $post->permalink : get_the_permalink($post) );
			$url = Dynamic_Helper::get_post_permalink($post);
			$url = Helper::filter_url( $url );
		}
		else if ($dtag == 'logout') {
			if (is_user_logged_in()) {
				$url = wp_logout_url();
			} 
		}
		else if ($dtag == 'image') {
			// $url = method_exists('Dynamic_Helper', 'get_the_post_thumbnail_url') ? Dynamic_Helper::get_the_post_thumbnail_url($post) : ( isset($post->image) ? $post->image : get_the_post_thumbnail_url($post) );
			$url = Dynamic_Helper::get_post_thumbnail_url($post);
		}
		else if ($dtag == 'author') {
			$url = Dynamic_Helper::make_authorlink_string($post);
		}
		// else if ($dtag == 'email') $url = 'mailto:'.get_the_author_meta('user_email', $post->post_author);
		else if ($dtag == 'post-next') {
			$url = get_permalink(get_adjacent_post(false, '', false)->ID);
		}
		else if ($dtag == 'post-previous') {
			$url = get_permalink(get_adjacent_post(false, '', true)->ID);
		}
		else if ($dtag == 'woo_ajax') {
			if ( isset($block["attrs"]["trigger"]["params"])) {
				$params = $block["attrs"]["trigger"]["params"];
				$redirects = isset($params['redirects']) ? $params['redirects'] : "default";
				$not_twice = isset($params['notTwice']) && $params['notTwice'] == true ? "1" : "0";
				$clear	   = isset($params['clear']) && $params['clear'] == true ? "1" : "0";
				$ajax_params =  "|".$redirects."|".$not_twice."|".$clear;
			}
			
			$url = Dynamic_Helper::make_ajax_link( $post, $ajax_params );
		}
		else {
			// loop/archives
			if ( $block && is_array($block) && isset($block['attrs']) && 
				isset($block['attrs']['queryTags']) && isset($block['attrs']['queryTags'][$dtag]) ) {
				// debug($block);
				// 'page-next'
				// 'page-previous'
				if (isset($block['attrs']['queryTags'][$dtag]) && $block['attrs']['queryTags'][$dtag] != '') {
					// $url = 'window.open(("'.$block['attrs']['queryTags'][$dtag].'").split("&#038;").join("&"), "'.$target.'");';
					$url = implode("&", explode("&#038;", $block['attrs']['queryTags'][$dtag]));
				}
			}
			else {
				// dpt
				// debug($dtag);
				$dtags = Dynamic_Helper::get_dynamic_posttype_tags( $post, array( 'rawurlencode' => true ) );
				if (isset($dtags[$dtag])) {
					if (isset($dtags[$dtag.'-link'])) $dtag .= '-link';

					/**
					 * Check if the dynamic tag is a phone number. If it is, we need to
					 * preserve the formatting and not encode it for a valid URL.
					 * 
					 * @since 1.1.2
					 */
					$phone_regex = '/^(\+?\(?[\d\s\+\(\-\–\/\)]{4,10}\)?[\d\s\-\–\/,#*]{1,64})$/';
					if ( preg_match($phone_regex, $dtags[$dtag]) ) {
						$url = $dtags[$dtag];
					}
					else {
						$url = urldecode($dtags[$dtag]);
					}
					$url = implode("&", explode("&#038;", $url));

					// debug( $dtags[$dtag], true );
					// debug( esc_url($url), true );
				}

				// $ddtag = explode('-', $dtag, 2);
				// if (count($ddtag) == 2) {
				// 	$ddtag = $ddtag[1];
				// 	$dtags = Dynamic_Helper::get_dynamic_posttype_tags($post);
				// 	// debug($dtags);
				// 	if (isset($dtags[$ddtag])) {
				// 		if (isset($dtags[$ddtag.'-link'])) $ddtag .= '-link';
				// 		// $url = 'window.open(("'.urldecode($dtags[$ddtag]).'").split("&#038;").join("&"), "'.$target.'");';
				// 		$url = implode("&", explode("&#038;", urldecode($dtags[$ddtag])));
				// 	}
				// }
			}
		}

		/**
		 * @filter greyd_get_dynamic_url
		 * 
		 * @param string $url       URL.
		 * @param string $name      Name of the dynamic tag (eg. "title")
		 * @param object $block     Parsed Block.
		 * @param WP_Post $wp_post  Post object.
		 * 
		 * @return string $html
		 */
		return apply_filters( 'greyd_get_dynamic_url', $url, $dtag, $block, $wp_post );
	}

}