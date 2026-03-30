<?php
/**
 * Trigger features rendering.
 * - render greyd Blocks and extension for core blocks
 * - render richtext format
 */
namespace greyd\blocks\trigger;
// namespace Greyd\Trigger;

use greyd\blocks\helper as helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Render($config);
class Render {

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
		$this->config->css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri  = plugin_dir_url( __FILE__ ) . 'assets/js';

		add_action( 'init', array($this, 'init') );
	}
	public function init() {

		if ( !class_exists('greyd\blocks\render') ) return;

		// add allowed HTML tags
		add_filter( 'greyd_blocks_filter_allowed_html_tags', array($this, 'filter_allowed_html_tags'), 10, 2 );

		// frontend
		// set priority higher than 1 (loaded after theme enqueue)
		add_action( 'wp_footer', array($this, 'add_frontend'), 2 );
		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'greyd_render_block'), 999, 2 );

	}


	/**
	 * Filter the HTML tags that are allowed inside post content.
	 * 
	 * @filter 'greyd_blocks_filter_allowed_html_tags'
	 * 
	 * @param array[] $html    Allowed HTML tags.
	 * @param string  $context Context name.
	 * 
	 * @return array[] $html   Allowed HTML tags.
	 */
	public function filter_allowed_html_tags($html, $context) {

		$html['a'] = array_merge(
			$html['a'],
			array(
				'role' => true,
				/**
				 * Allow trigger="placeholder" inside post content to enable trigger.
				 * @deprecated since 1.3.3
				 */
				'trigger' => true
			)
		);

		return $html;
	}


	/**
	 * Add frontend script for trigger
	 */
	public function add_frontend() {
		
		// frontend script
		wp_register_script(
			'greyd-trigger-script',
			$this->config->js_uri.'/frontend.js',
			array('jquery'),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script('greyd-trigger-script');

		// define global greyd var
		wp_add_inline_script('greyd-trigger-script', '
			var greyd = greyd || {};
		', 'before');

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
	public function greyd_render_block($content, $block) {
		// debug("render trigger");

		$block_content = $content['block_content'];
		$html_atts = $content['html_atts'];
		$style = $content['style'];

		// match and replace richtext trigger
		$block_content = self::match_trigger($block_content);
		
		// get trigger object from Block
		$trigger_attributes = self::get_trigger_attributes($block);
		
		// handle trigger for greyd/buttons
		if ( $block['blockName'] === 'greyd/button' ) {
			// debug( esc_attr($block_content) );

			// don't render logout button if no user is logged in
			if ( isset($block['attrs']['trigger']['params']['tag']) && $block['attrs']['trigger']['params']['tag'] == "logout" && !is_user_logged_in()) return "";
				
			/**
			 * We handle the trigger events differently for buttons.
			 * This way proper a-tags are set for link-, email- & download-buttons.
			 */
			$replace = '';
			if ( $trigger_attributes ) {
				$replace = self::get_trigger_html_attributes_string($trigger_attributes);

				// prevent double link creation
				$trigger_attributes = null;
			}

			if ( !empty($block_content) ) {

				// the attribute 'role="trigger"' is returned inside the save-function
				$block_content = preg_replace(
					array(
						"/role=\"trigger\"/",
						"/trigger=\"placeholder\"/" /** @deprecated since 1.3.3 */
					),
					$replace,
					$block_content
				);
			}
		}

		/**
		 * @since 1.5.0
		 * We handle the trigger events differently for images.
		 * This way proper a-tags are set.
		 */
		if ( $block['blockName'] === 'greyd/image' ) {
			
			if ( $trigger_attributes && !empty($block_content) ) {

				$link_atts = self::merge_trigger_html_atts( array(), $trigger_attributes);

				if ( isset($link_atts['children']) && is_array($link_atts['children']) ) {

					// get the hidden box link
					$link = reset($link_atts['children']);
					unset( $link_atts['children'] );
					if ( isset($link_atts['class']) && $link_atts['class'] == 'has-trigger' ) {
						unset( $link_atts['class'] );
					}
					
					// replace it with other potential attributes
					$link = str_replace(
						array( ' class="hidden-trigger-link"', '</a>' ),
						array( helper::implode_html_attributes($link_atts), '' ),
						$link
					);

					$block_content = preg_replace(
						'/(<img.+?"\s?\/?>)/',
						$link . '$1' . '</a>',
						$block_content
					);

					// prevent double link creation
					$trigger_attributes = null;
				}
			}
		}

		// debug($block_content);

		// add trigger javscript event attributes
		if (isset($block["attrs"]['trigger_event']) && !empty($block["attrs"]['trigger_event'])) {
			// debug($block["attrs"]);
			$html_atts = self::merge_greyd_javascript_trigger_html_atts($html_atts, $block["attrs"]);
		}

		// add normal trigger attributes for links, buttons, etc.
		if ( $trigger_attributes ) {
			// debug($block["attrs"]);
			$html_atts = self::merge_trigger_html_atts($html_atts, $trigger_attributes);
		}

		// debug($html_atts);

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);
	}
	

	/**
	 * Match and replace all RichText Triggers in content string.
	 * 
	 * @param string $block_content			Original Block Content.
	 * @return string $block_content		Block Content with rendered RichText Triggers.
	 */
	public static function match_trigger($block_content) {
		if (strpos($_SERVER['REQUEST_URI'], "/wp-json/wp/v2") === false) {
			preg_match_all('/(?<=<span class="is-button )([^"]+)(?=")/', $block_content, $has_trigger);
			if (isset($has_trigger[0]) && count($has_trigger[0]) > 0) {
				// debug($has_trigger);
				for ($i=0; $i<count($has_trigger[0]); $i++) {
					$trigger = $has_trigger[0][$i];
					preg_match_all('/(?<=<span class="is-button '.$trigger.'" data-type=")([^"]*)(?>" data-params=")([^"]*)(?>">)(.*?)(?=<\/span>)/', $block_content, $matches);
					// debug($matches);
					if (count($matches[0]) > 0) {
						for ($j=0; $j<count($matches[0]); $j++) {
							// get trigger
							$atts = array(
								"trigger" => array(
									"type" => $matches[1][$j],
									"params" => json_decode(html_entity_decode($matches[2][$j]), true)
								)
							);
							// debug($atts);
							$trigger_attributes = self::get_trigger_attributes( array( "attrs" => $atts ) );
							// debug($trigger_attributes);
							$replace = self::get_trigger_html_attributes_string($trigger_attributes);
							// debug($replace);

							// replace
							$old = '<span class="is-button '.$trigger.'" data-type="'.$matches[1][$j].'" data-params="'.$matches[2][$j].'">'.$matches[3][$j].'</span>';
							$new = '<a class="'.$trigger.'" '.$replace.'>'.$matches[3][$j].'</a>';
							// debug(htmlspecialchars($block_content));
							$block_content = str_replace($old, $new, $block_content);
							$block_content = str_replace(array('<p><p>', '</p></p>'), array('<p>', '</p>'), $block_content);
						}
					}
				}
			}
		}
		return $block_content;
	}

	/**
	 * Get the trigger attributes.
	 * 
	 * @param array $block The block with all its attributes.
	 * 
	 * @return null|array Null when no trigger is defined. Array on success:
	 *         @property array url      e.g. [ href: 'https://your-website.com', target: '_blank' ]
	 *         @property string click   e.g. 'history.back()'
	 *         @property string hover   e.g. 'greyd.trigger.trigger("toggle-01")'
	 */
	public static function get_trigger_attributes($block) {

		// trigger
		if ( !isset($block["attrs"]['trigger']) || empty($block["attrs"]['trigger'])) return null;
		// debug("get_trigger");
		
		$trigger = array(
			'url' => array(),
			'click' => '',
			'hover' => '',
			'title' => ''
		);

		switch ( $block["attrs"]['trigger']['type'] ) {

			case 'link':
				$url = isset($block["attrs"]['trigger']['params']['url']) ? $block["attrs"]['trigger']['params']['url'] : "";
				if ( !empty($url) ) {

					$trigger['title'] = isset($block["attrs"]['trigger']['params']['title']) ? $block["attrs"]['trigger']['params']['title'] : '';

					/**
					 * @since 1.7.4 Option to not filter the url
					 */
					$is_raw_url = isset($block["attrs"]['trigger']['params']['isRawURL']) ? $block["attrs"]['trigger']['params']['isRawURL'] : false;

					if ( ! $is_raw_url ) {
						// if ( method_exists( '\Greyd\Helper', 'filter_url' ) ) {
						// 	$url = \Greyd\Helper::filter_url( $url );
						// } else if ( method_exists( '\greyd\blocks\deprecated\Functions', 'filter_url' ) ) {
						// 	$url = \greyd\blocks\deprecated\Functions::filter_url( $url );
						// }
						$url = \Greyd\Helper::filter_url( $url );
					}

					$trigger['url']['href'] = $url;
					if (
						isset($block["attrs"]['trigger']['params']['opensInNewTab']) 
						&& $block["attrs"]['trigger']['params']['opensInNewTab'] 
					) {
						$trigger['url']['target'] = '_blank';
					}
					// else {
					// 	$trigger['url']['target'] = '_self';
					// }

					if (
						isset($block["attrs"]['trigger']['params']['download']) &&
						$block["attrs"]['trigger']['params']['download'] &&
						!empty(pathinfo(urldecode($trigger['url']['href']), PATHINFO_EXTENSION))
					) {
						$trigger['url']['download'] = true;
					}
					
					/**
					 * @since 1.7.4 Option to add rel="noreferrer" and rel="external"
					 */
					if ( isset($block["attrs"]['trigger']['params']['noReferrer']) ) {
						$trigger['url']['rel'] = isset($trigger['url']['rel']) ? $trigger['url']['rel'] . ' noreferrer' : 'noreferrer';
					}
					if ( isset($block["attrs"]['trigger']['params']['external']) ) {
						$trigger['url']['rel'] = isset($trigger['url']['rel']) ? $trigger['url']['rel'] . ' external' : 'external';
					}
				}
				break;

			case 'back':
				$trigger['click'] = 'history.back();';
				break;

			case 'popup_close':
				$trigger['click'] = 'popups.btnClose(this);';
				break;

			case 'scroll':
				if ($block["attrs"]['trigger']['params'] == '_top') {
					$trigger['url']['href'] = '#';
					$trigger['url']['aria-label'] = __( 'Scroll to top of page', 'greyd_hub' );	
				} else if ($block["attrs"]['trigger']['params'] == '_bottom') {
					$trigger['url']['href'] = 'javascript: document.body.scrollIntoView(false);';
					$trigger['url']['aria-label'] = __( 'Scroll to bottom of page', 'greyd_hub' );
				} else {
					$trigger['url']['href'] = '#'.rawurlencode( $block["attrs"]['trigger']['params'] );
					$trigger['url']['aria-label'] = sprintf( __( 'Scroll to %s', 'greyd_hub' ), $block["attrs"]['trigger']['params'] );
				}
				break;

			case 'popup':
				$popup_id = $block["attrs"]['trigger']['params'];
				if ( !empty($popup_id) ) {

					if ( method_exists( 'Greyd\Helper', 'filter_post_id' ) ) {
						$popup_id = \Greyd\Helper::filter_post_id( $popup_id, 'greyd_popup' );
					}

					$trigger['click'] = 'popups.btnToggle("#popup_'.$popup_id.'");';

					if ( class_exists('\Greyd\Popups\Render') ) {
						\Greyd\Popups\Render::add_popup_id( $popup_id );
					}
					else if ( class_exists('\popup') ) {
						\popup::add_popup_id( $popup_id );
					}
				}
				break;

			case 'email':
				$url = $block["attrs"]['trigger']['params']['address'];
				if (!empty($url)) {
					$trigger['url']['href'] = 'mailto:'.$url;
					if (isset($block["attrs"]['trigger']['params']['subject'])) {
						$trigger['url']['href'] .= '?subject='.$block["attrs"]['trigger']['params']['subject'];
					}
				}
				break;

			case 'file':
				$url = wp_get_attachment_url($block["attrs"]['trigger']['params']);
				if (!empty($url)) {
					$trigger['url'] = array(
						'href' => $url,
						'download' => true
					);
				}
				break;

			case 'event':
				// debug($block["attrs"]['trigger']);
				$action = $block["attrs"]['trigger']['params']['name'];
				if ( !empty($action) ) {

					$global = isset($block["attrs"]['trigger']['params']['global']) && $block["attrs"]['trigger']['params']['global'] ? 'true' : 'false';
					$action = helper::snakecase( $action == '__custom' ? $block["attrs"]['trigger']['params']['custom'] : $action );
					$callback = 'greyd.trigger.trigger(event, "'.$action.'", '.$global.');';

					// hover
					if ( isset($block["attrs"]['trigger']['params']['hover']) && $block["attrs"]['trigger']['params']['hover'] ) {
						$trigger['hover'] = $callback;
					}
					// click
					else {
						$trigger['click'] = $callback;
					}
				}
				break;

			case 'dynamic':
				if ( isset($block["attrs"]['trigger']['params']) ) {
					$params = $block["attrs"]['trigger']['params'];
					if ( $params['tag'] == 'page-next' ) {
						$trigger['click'] = 'posts.triggerNext(this)';
					}
					else if ( $params['tag'] == 'page-previous' ) {
						$trigger['click'] = 'posts.triggerPrevious(this)';
					}
					else {
						// $trigger['url']['href'] = \greyd\blocks\deprecated\Functions::get_dynamic_url($params['tag'], $block);
						if ( class_exists( '\Greyd\Dynamic\Render_Blocks' ) ) {
							$trigger['url']['href'] = \Greyd\Dynamic\Render_Blocks::get_dynamic_url( $params['tag'], $block );
							if ( isset($params['opensInNewTab']) && $params['opensInNewTab'] ) {
								$trigger['url']['target'] = '_blank';
							}
							if (
								isset($params['download']) && $params['download'] &&
								!empty(pathinfo(urldecode($trigger['url']['href']), PATHINFO_EXTENSION))
							) {
								$trigger['url']['download'] = true;
							}
						}
					}

					// check if dynamic link is an email
					if (isset($trigger['url']['href']) && filter_var($trigger['url']['href'], FILTER_VALIDATE_EMAIL)) {
						$trigger['url']['href'] = 'mailto:'.$trigger['url']['href'];
					}

					// check if dynamic link is a telephone
					$phone_regex = '/^(\+?\(?[\d\s\+\(\-\–\/\)]{4,10}\)?[\d\s\-\–\/,#*]{1,64})$/';
					if (isset($trigger['url']['href']) && preg_match($phone_regex, $trigger['url']['href'])) {
						$trigger['url']['href'] = 'tel:'.$trigger['url']['href'];
					}

					// Post Author URL is returned raw, convert it to a valid URL
					if ( str_contains( $trigger['url']['href'], '%3A' ) ) {
						$trigger['url']['href'] = urldecode( $trigger['url']['href'] );
					}

					// If there is a linkText, set it as title to have it rendered correctly
					if ( isset($params['linkText']) ) {
						$trigger['title'] = $params['linkText'];
					}

				}
				break;
		}
		
		/**
		 * This filter can be used to modifiy the trigger attributes before they are
		 * applied to the html wrapper.
		 * 
		 * @filter 'greyd_get_trigger_attributes'
		 * 
		 * @param array $trigger_attributes  Trigger Attributes:
		 *         @property array url      e.g. [ href: 'https://your-website.com', target: '_blank' ]
		 *         @property string click   e.g. 'history.back()'
		 *         @property string hover   e.g. 'greyd.trigger.trigger("toggle-01")'
		 * @param array $block              The block with all its attributes.
		 * 
		 * @return array $trigger_attributes Altered Trigger Attributes.
		 */
		return apply_filters('greyd_get_trigger_attributes', $trigger, $block);
	}

	/**
	 * Make the javascript trigger event Actions and add them to $html_atts.
	 * 
	 * @param array $html_atts  Attributes that get rendered to the html wrapper.
	 * @param object $attrs     The Block Attributes.
	 * 
	 * @return array $html_atts Altered html wrapper attributes.
	 */
	public static function merge_greyd_javascript_trigger_html_atts($html_atts, $attrs) {
		// debug("merge_greyd_javascript_trigger_html_atts");

		$triggers = array();
		$actions  = isset($attrs['trigger_event']['actions']) ? $attrs['trigger_event']['actions'] : array();
		if ( count($actions) > 0 ) {
			foreach ($actions as $action) {
				$name  = isset($action['name']) ? helper::snakecase($action['name']) : null;
				$event = isset($action['action']) && !empty($action['action']) ? esc_attr($action['action']) : 'show';
				if ( !empty($name) && !empty($event) ) {
					$triggers[] = $name."|".$event;
				}
			}
			if (count($triggers)) {
				$html_atts['data-greyd-trigger'] = implode("::", $triggers);
			}

			$onload = isset($attrs['trigger_event']['onload']) ? esc_attr($attrs['trigger_event']['onload']) : null;
			if (!empty($onload) && $onload !== "show") $html_atts['data-onload'] = $onload;

			$siblings = isset($attrs['trigger_event']['siblings']) ? $attrs['trigger_event']['siblings'] : null;
			if (!empty($siblings) && $siblings == 1) $html_atts['data-siblings'] = 'hide';
		}

		/**
		 * This filter can be used to modifiy the html attributes of custom greyd trigger
		 * events.
		 * 
		 * @filter 'greyd_merge_greyd_javascript_trigger_html_atts'
		 * 
		 * @param array $html_atts  Altered html wrapper attributes.
		 * @param object $attrs     The Block Attributes.
		 * 
		 * @return array $html_atts  Altered html wrapper attributes.
		 */
		return apply_filters('greyd_merge_greyd_javascript_trigger_html_atts', $html_atts, $attrs);
	}

	/**
	 * Make the Trigger.
	 * 
	 * @param array $html_atts             Attributes that get rendered to the html wrapper.
	 * @param object $trigger_attributes   Trigger Attributes @see get_trigger function.
	 * @return array $html_atts            Altered html wrapper attributes.
	 */
	public static function merge_trigger_html_atts($html_atts, $trigger_attributes) {
		// debug("merge_trigger_html_atts");

		$has_trigger = false;
		if ( !empty($trigger_attributes['url']) ) {
			$has_trigger = true;

			// add title attribute to the link
			if ( isset($trigger_attributes['url']['href']) && $link_id = url_to_postid($trigger_attributes['url']['href']) ) {
				$trigger_attributes['url']['title'] = get_the_title( $link_id );
			} else if ( isset($trigger_attributes['title']) ) {
				$trigger_attributes['url']['title'] = $trigger_attributes['title'];
			}

			/**
			 * @since 1.2.10: add the hidden-trigger-link as child.
			 */
			if ( ! isset($html_atts['children']) ) {
				$html_atts['children'] = array();
			}
			$html_atts['children'][] = '<a class="hidden-trigger-link" '.helper::implode_html_attributes( $trigger_attributes['url'] ).'></a>';
		}
		else if ( !empty($trigger_attributes['click']) ) {
			$has_trigger = true;
			$html_atts['onclick'] = esc_attr( $trigger_attributes['click'] );
			$html_atts['tabindex'] = '0';
		}
		else if ( !empty($trigger_attributes['hover']) ) {
			$html_atts['onmouseenter'] = esc_attr( $trigger_attributes['hover'] );
			$html_atts['onmouseleave'] = esc_attr( $trigger_attributes['hover'] );
			$html_atts['tabindex'] = '0';
		}

		if ( $has_trigger ) {
			// $html_atts['role'] = 'button';
			$html_atts['class'] = isset($html_atts['class']) ? $html_atts['class'].' has-trigger' : 'has-trigger';
		}
		
		/**
		 * This filter can be used to modifiy the html attributes of the trigger after
		 * they are merged with other html wrapper attributes.
		 * 
		 * @filter 'greyd_merge_trigger_html_atts'
		 * 
		 * @param array $html_atts           Altered html wrapper attributes.
		 * @param array $trigger_attributes  Trigger Attributes:
		 *         @property array url      e.g. [ href: 'https://your-website.com', target: '_blank' ]
		 *         @property string click   e.g. 'history.back()'
		 *         @property string hover   e.g. 'greyd.trigger.trigger("toggle-01")'
		 * 
		 * @return array $html_atts          Altered html wrapper attributes.
		 */
		return apply_filters('greyd_merge_trigger_html_atts', $html_atts, $trigger_attributes);
	}

	/**
	 * Handle trigger events for greyd buttons.
	 * Proper a-tags are set for link-, email- & download-buttons.
	 * 
	 * @param array $trigger_attributes     Trigger Attributes @see get_trigger function.
	 * @return string $replace              Partial string to replace 'role' attribute in button markup.
	 */
	public static function get_trigger_html_attributes_string($trigger_attributes) {
		// debug("get_trigger_html_attributes_string");
		$replace = '';

		if ( !empty($trigger_attributes['url']) ) {

			// add title attribute to the link
			if ( isset($trigger_attributes['url']['href']) && $link_id = url_to_postid($trigger_attributes['url']['href']) ) {
				$trigger_attributes['url']['title'] = get_the_title( $link_id );
			} else if ( isset($trigger_attributes['title']) ) {
				$trigger_attributes['url']['title'] = $trigger_attributes['title'];
			}
			
			$replace = helper::implode_html_attributes( $trigger_attributes['url'] );
		}
		else if ( !empty($trigger_attributes['click']) ) {
			$replace = 'role="button" tabindex="0" onclick="'.esc_attr( $trigger_attributes['click'] ).'"';
		}
		else if ( !empty($trigger_attributes['hover']) ) {
			$replace = 'role="button" tabindex="0" onmouseenter="'.esc_attr( $trigger_attributes['hover'] ).'" onmouseleave="'.esc_attr( $trigger_attributes['hover'] ).'"';
		}

		/**
		 * This filter can be used to modifiy the html attributes of the trigger after
		 * they are merged with other html wrapper attributes and converted to a string
		 * to then be replaced in the button markup.
		 * 
		 * @filter 'greyd_get_trigger_html_attributes_string'
		 * 
		 * @param string $replace            Partial string to replace 'role' attribute in button markup.
		 * @param array $trigger_attributes  Trigger Attributes:
		 *         @property array url      e.g. [ href: 'https://your-website.com', target: '_blank' ]
		 *         @property string click   e.g. 'history.back()'
		 *         @property string hover   e.g. 'greyd.trigger.trigger("toggle-01")'
		 * 
		 * @return string $replace           Partial string to replace 'role' attribute in button markup.
		 */
		return apply_filters('greyd_get_trigger_html_attributes_string', $replace, $trigger_attributes);
	}

	/**
	 * Compatibility:
	 * Static functions names have changed in 2.15.0. These dummy functions re-route 
	 * old functions calls in case (e.g. a child-theme) still uses them.
	 */

	public static function get_trigger($block) {
		return self::get_trigger_attributes($block);
	}
	public static function make_trigger_event($html_atts, $attrs) {
		return self::merge_greyd_javascript_trigger_html_atts($html_atts, $attrs);
	}
	public static function make_trigger($html_atts, $trigger_object) {
		return self::merge_trigger_html_atts($html_atts, $trigger_object);
	}
	public static function make_trigger_button($trigger_object) {
		return self::get_trigger_html_attributes_string($trigger_object);
	}

}