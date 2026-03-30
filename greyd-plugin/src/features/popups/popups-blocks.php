<?php
/**
 * Register and enqueue Blocks Features for Popups.
 */
namespace Greyd\Popups;

if ( !defined( 'ABSPATH' ) ) exit;

new Blocks($config);
class Blocks {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Holds the post_type Slug.
	 * 
	 * @var string
	 */
	private $post_type = "greyd_popup";


	/**
	 * Class constructor.
	 */
	public function __construct($config) {
		// set config
		$this->config = (object)$config;
		$this->config->css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri = plugin_dir_url( __FILE__ ) . 'assets/js';


		// blockeditor
		add_action( 'enqueue_block_editor_assets', array($this, 'block_editor_scripts'), 99 );
		// add to JS @var greyd.data
		add_filter( 'greyd_blocks_editor_data', array( $this, 'add_editor_data' ) );
		add_action( 'init', array($this, 'register_blocks'), 99 );

	}

	/*
	====================================================================================
		Block Editor
	====================================================================================
	*/

	/**
	 * Enqueue Block Editor Styles & Scripts.
	 */
	public function block_editor_scripts() {

		$screen = get_current_screen();
		$posttype = get_post_type() !== false ? get_post_type() : ( isset($screen->post_type) ? $screen->post_type : "" );
		// debug($screen);
		// debug($posttype);

		// load scripts only on posttype screens
		if ( $posttype === $this->post_type ) {

			// popup editor styles
			wp_register_style(
				'greyd-popups-style',
				$this->config->css_uri.'/editor.css',
				array( ),
				GREYD_VERSION
			);
			wp_enqueue_style( 'greyd-popups-style' );

			// popup tools
			wp_register_script(
				'greyd-popups-tools',
				$this->config->js_uri.'/popups-tools.js',
				array( 'greyd-tools', 'wp-blocks', 'wp-dom', 'wp-i18n', 'lodash' ),
				GREYD_VERSION,
				true
			);
			wp_enqueue_script( 'greyd-popups-tools' );

			// popup components
			wp_register_script(
				'greyd-popups-components',
				$this->config->js_uri.'/popups-components.js',
				array( 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
				GREYD_VERSION,
				true
			);
			wp_enqueue_script('greyd-popups-components');

			// popup script
			wp_register_script(
				'greyd-popups-editor',
				$this->config->js_uri.'/editor.js',
				array( 'greyd-popups-components', 'greyd-popups-tools', 'wp-data', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-block-editor', 'wp-plugins', 'wp-i18n', 'lodash' ),
				GREYD_VERSION,
				true
			);
			wp_enqueue_script( 'greyd-popups-editor' );
			wp_add_inline_script('greyd-popups-editor', 'greyd.home = "'.home_url().'"; greyd.popupStyles = "'.$this->config->css_uri.'/popups.css"', 'before');

			// script translations
			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'greyd-popups-tools', 'greyd_hub', $this->config->plugin_path.'/languages' );
				wp_set_script_translations( 'greyd-popups-components', 'greyd_hub', $this->config->plugin_path.'/languages' );
				wp_set_script_translations( 'greyd-popups-editor', 'greyd_hub', $this->config->plugin_path.'/languages' );
			}
		}
	}

	/**
	 * Filter Blockeditor Data values.
	 *
	 * @filter 'greyd_blocks_editor_data'
	 *
	 * @param object $data     Original Data Array
	 * @return object $data    Data Array with filtered Values
	 */
	public function add_editor_data( $data ) {

		// get all popups
		// todo: use filter in Popups Feature
		$all_popups = array();
		$popups = \Greyd\Helper::get_all_posts('greyd_popup');
		if ($popups) {
			// debug($popups);
			foreach ($popups as $popup) {
				$all_popups[] = (object)array( 
					'id' => $popup->id, 
					'slug' => $popup->slug, 
					'title' => $popup->title, 
					'lang' => $popup->lang
				);
			}
		}

		$data['popups'] = $all_popups;

		return $data;

	}

	/**
	 * Register popup close block.
	 */
	public function register_blocks() {

		/**
		 * only if block is not already registered.
		 * remove register_block_type( 'greyd/popup-close', ... ) from block plugin blocks.php to use this.
		 */
		if (!\WP_Block_Type_Registry::get_instance()->is_registered( 'greyd/popup-close' )) {

			// debug("register block");
			// categories
			add_filter('block_categories_all', function ($categories, $post) {
				foreach ( $categories as $cat ) {
					if ( $cat['slug'] == 'greyd-blocks' ) {
						// don't add if already added
						return $categories;
					}
				}
				// add Greyd category
				return array_merge(
					array(
						array(
							'slug'  => 'greyd-blocks',
							'title' => 'Greyd',
						),
					),
					$categories
				);
			}, 10, 2);


			// register assets
			wp_register_style(
				'greyd-popup-close-style',
				$this->config->css_uri.'/popup-close.css',
				null,
				GREYD_VERSION
			);
			wp_register_script(
				'greyd-popup-close-script',
				$this->config->js_uri.'/popup-close.js',
				array( 'greyd-popups-editor', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
				GREYD_VERSION
			);

			register_block_type( 'greyd/popup-close', array(
				'editor_script'  => 'greyd-popup-close-script',
				'style'          => 'greyd-popup-close-style'
			) );

			// script translations
			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'greyd-popup-close-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
			}

		}
		// else debug("do not register");

	}

}