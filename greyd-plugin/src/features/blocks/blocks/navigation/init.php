<?php
/**
 * Extend core navigation
 * 
 * @since 1.13.0
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

new Navigation($config);
class Navigation {

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

		// set config
		$this->config = (object) $config;

		// setup
		$this->config->assets_url = trailingslashit( plugin_dir_url( __FILE__ ) );

		// enqueue styles & scripts
		add_action( 'wp_enqueue_scripts', array($this, 'add_frontend_assets'), 91 );
		if ( is_admin() ) {
			/**
			 * 'enqueue_block_assets' action should be used here. 
			 * @see features/blocks/enqueue.php
			 */
			$action = \Greyd\Helper::is_active_plugin('gutenberg/gutenberg.php') ? 'enqueue_block_assets' : 'current_screen';
			add_action( $action, array($this, 'register_block_editor_styles') );
			add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );
		}
		
		// hook block rendering
		add_filter( 'greyd_blocks_render_block', array($this, 'render_core_navigation'), 10, 2 );
		
		// allow navigation inside navigation
		add_filter( 'block_core_navigation_render_inner_blocks', array($this, 'allow_inner_core_navigation') );
	}

	/**
	 * Add frontend block styles and script
	 * @action wp_enqueue_scripts
	 */
	public function add_frontend_assets() {

		// frontend blocks styles
		wp_register_style(
			'greyd-navigation-frontend-style',
			$this->config->assets_url.'blocks.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style('greyd-navigation-frontend-style');

		// frontend script
		wp_register_script(
			'greyd-navigation-frontend-script',
			$this->config->assets_url.'frontend.js',
			array( 'greyd-scroll-observer' ),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
		wp_enqueue_script( 'greyd-navigation-frontend-script' );

	}

	/**
	 * Register and enqueue all the styles for the editor.
	 * @action enqueue_block_assets
	 */
	public function register_block_editor_styles() {

		if ( ! is_admin() ) return;

		// frontend styles in editor
		add_editor_style( $this->config->assets_url.'blocks.css' );

	}
	
	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {

		// editor script
		wp_register_script(
			'greyd-navigation-editor',
			$this->config->assets_url.'editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-navigation-editor');

		// translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-navigation-editor', 'greyd_hub', $this->config->plugin_path."/languages" );
		}

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
	 *      @property array  html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public function render_core_navigation( $content, $block ) {

		$block_content = $content['block_content'];
		$html_atts = $content['html_atts'];
		$style = $content['style'];

		/**
		 * custom styles
		 */
		if (
			$block['blockName'] == 'core/navigation' ||
			$block['blockName'] == 'core/navigation-submenu' ||
			$block['blockName'] == 'core/navigation-link'
		) {

			if (
				isset($block["attrs"]["custom"]) && $block["attrs"]["custom"] &&
				isset($block["attrs"]['customStyles']) && !empty($block["attrs"]['customStyles']) && 
				isset($block["attrs"]['greydClass'])
			) {
				// debug($block['blockName']);
				// debug($block);
				// debug(htmlentities($block_content));
				$selectors = array();
				if ( $block['blockName'] == 'core/navigation' ) {
					// get string between '<nav' and first 'class=' (either empty, or additional styles)
					preg_match( '/'.preg_quote('<nav ', '/').'(.*)'.preg_quote('class="', '/').'/U', $block_content, $match );
					$match = $match && isset($match[1]) ? ' '.$match[1] : ' ';
					// add greydClass
					$block_content = preg_replace('/'.preg_quote('<nav'.$match.'class="', '/').'/', '<nav'.$match.'class="'.$block["attrs"]['greydClass'].' ', $block_content, 1);
					$selectors['.'.$block["attrs"]['greydClass'].' .wp-block-navigation-item__content'] = $block["attrs"]['customStyles'];
					// icon
					if ( isset($block["attrs"]['customStyles']['color']) ) {
						$icon_selector = '.'.$block["attrs"]['greydClass'].' .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg';
						$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['color'] );
					}
					if ( isset($block["attrs"]['customStyles']['hover']['color']) ) {
						$icon_selector = '.'.$block["attrs"]['greydClass'].' .wp-block-navigation-item__content:hover + .wp-block-navigation__submenu-icon svg';
						$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['hover']['color'] );
					}
					// active
					if ( isset($block["attrs"]['customStyles']['active']) ) {
						$selectors['.'.$block["attrs"]['greydClass'].' .current-menu-item > .wp-block-navigation-item__content'] = $block["attrs"]['customStyles']['active'];
						// active icon
						if ( isset($block["attrs"]['customStyles']['active']['color']) ) {
							$icon_selector = '.'.$block["attrs"]['greydClass'].' .current-menu-item > .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg';
							$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['active']['color'] );
						}
					}
				}
				else if ( $block['blockName'] == 'core/navigation-submenu' ) {
					// get string between first '<li' and first 'class=' (either empty, or additional styles)
					preg_match( '/'.preg_quote('<li ', '/').'(.*)'.preg_quote('class="', '/').'/U', $block_content, $match );
					$match = $match && isset($match[1]) ? ' '.$match[1] : ' ';
					// add greydClass
					$block_content = preg_replace('/'.preg_quote('<li'.$match.'class="', '/').'/', '<li'.$match.'class="'.$block["attrs"]['greydClass'].' ', $block_content, 1);
					$selectors = array(
						'.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' > .wp-block-navigation-item__content' => $block["attrs"]['customStyles'],
						'.'.$block["attrs"]['greydClass'].' li .wp-block-navigation-item__content' => $block["attrs"]['customStyles']
					);
					// icon
					if ( isset($block["attrs"]['customStyles']['color']) ) {
						$icon_selector = '.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg';
						$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['color'] );
					}
					if ( isset($block["attrs"]['customStyles']['hover']['color']) ) {
						$icon_selector = '.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' .wp-block-navigation-item__content:hover + .wp-block-navigation__submenu-icon svg';
						$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['hover']['color'] );
					}
					// active
					if ( isset($block["attrs"]['customStyles']['active']) ) {
						$selectors['.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].'.current-menu-item > .wp-block-navigation-item__content'] = $block["attrs"]['customStyles']['active'];
						$selectors['.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' .current-menu-item > .wp-block-navigation-item__content'] = $block["attrs"]['customStyles']['active'];
						// active icon
						if ( isset($block["attrs"]['customStyles']['active']['color']) ) {
							$icon_selector = '.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].'.current-menu-item > .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg';
							$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['active']['color'] );
							$icon_selector = '.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' .current-menu-item > .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg';
							$selectors[$icon_selector] = array( 'stroke' => $block["attrs"]['customStyles']['active']['color'] );
						}
					}
				}
				else if ( $block['blockName'] == 'core/navigation-link' ) {
					// get string between first '<li' and first 'class=' (either empty, or additional styles)
					preg_match( '/'.preg_quote('<li ', '/').'(.*)'.preg_quote('class="', '/').'/U', $block_content, $match );
					$match = $match && isset($match[1]) ? ' '.$match[1] : ' ';
					// add greydClass
					$block_content = preg_replace('/'.preg_quote('<li'.$match.'class="', '/').'/', '<li'.$match.'class="'.$block["attrs"]['greydClass'].' ', $block_content, 1);
					$selectors['.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].' > .wp-block-navigation-item__content'] = $block["attrs"]['customStyles'];
					// active
					if ( isset($block["attrs"]['customStyles']['active']) ) {
						$selectors['.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].'.'.$block["attrs"]['greydClass'].'.current-menu-item > .wp-block-navigation-item__content'] = $block["attrs"]['customStyles']['active'];

					}
				}
				// enqueue customStyles
				foreach ( $selectors as $selector => $styles ) {
					render::enqueue_custom_style($selector, $styles, true);
				}
				// debug(htmlspecialchars($block_content));
			}

		}

		/**
		 * navigation behaviour
		 */
		if ( $block['blockName'] == 'core/navigation' ) {

			// add submenu class
			if ( isset($block["attrs"]['submenu']) && $block["attrs"]['submenu'] != '' ) {
				// get string between '<nav' and first 'class=' (either empty, or additional styles)
				preg_match( '/'.preg_quote('<nav ', '/').'(.*)'.preg_quote('class="', '/').'/U', $block_content, $match );
				$match = $match && isset($match[1]) ? ' '.$match[1] : ' ';
				// add submenu class
				$block_content = preg_replace('/'.preg_quote('<nav'.$match.'class="', '/').'/', '<nav'.$match.'class="submenus-'.$block["attrs"]['submenu'].' ', $block_content, 1);
			}

			// add anchor-active class and data
			if ( isset($block["attrs"]['anchoractive']) && $block["attrs"]['anchoractive'] ) {

				if ( isset($block["attrs"]['anchoractive']['enable']) && $block["attrs"]['anchoractive']['enable'] ) {
					// get string between '<nav' and first 'class=' (either empty, or additional styles)
					preg_match( '/'.preg_quote('<nav ', '/').'(.*)'.preg_quote('class="', '/').'/U', $block_content, $match );
					$match = $match && isset($match[1]) ? ' '.$match[1] : ' ';
					// add anchor-active class
					$block_content = preg_replace('/'.preg_quote('<nav'.$match.'class="', '/').'/', '<nav'.$match.'class="is-anchor-active-style ', $block_content, 1);
					$data = wp_parse_args( $block["attrs"]['anchoractive'], array(
						'start' => '0%',
						'end' => '100%',
						'multiple' => '',
						'none' => ''
					) );
					// add anchor-active data
					$block_content = preg_replace('/'.preg_quote('<nav'.$match.'class="', '/').'/', '<nav'.$match.'data-anchoractive="'.str_replace('"', '&quot;', json_encode($data)).'" class="', $block_content, 1);
				}

			}

		}

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);

	}


	/**
	 * Allow navigation inside navigation.
	 * Core scans the inner_blocks of a navigation block for nested navigation blocks and aborts
	 * rendering if any is found. However, nested navigation blocks work well (e.g. inside popover).
	 * To allow this, we hook the list of inner_blocks which is used for the scan and rename the
	 * 'core/navigation' block to anything else. This does not affect the rendering of the nested
	 * navigation block since the renaming is only done in this list - block parsing and rendering
	 * are untouched.
	 * 
	 * @filter 'block_core_navigation_render_inner_blocks'
	 * 
	 * @param \WP_Block_List $inner_blocks
	 * @return \WP_Block_List $inner_blocks
	 */
	public function allow_inner_core_navigation( $inner_blocks ) {
		foreach ( $inner_blocks as $block ) {
			if ( $block->name === 'core/navigation' ) {
				$block->name = "core/navigation-allow";
			}
			if ( $block->inner_blocks ) {
				$block->inner_blocks = $this->allow_inner_core_navigation( $block->inner_blocks );
			}
		}
		return $inner_blocks;
	}

}