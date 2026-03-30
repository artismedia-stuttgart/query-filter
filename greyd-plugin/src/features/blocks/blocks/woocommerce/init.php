<?php
/**
 * WooCommerce Blocks Integration
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

// check if woocommerce is installed and active
if ( !function_exists('is_plugin_active') ) return;
if ( !is_plugin_active('woocommerce/woocommerce.php') ) return;

new Woocommerce($config);
class Woocommerce {

	/**
	 * Hold the plugin config
	 * 
	 * @var object
	 */
	private $config;


	/**
	 * Constructor
	 */
	public function __construct($config) {
		
		// class vars
		$this->config   = (object) $config;
		$this->config->assets_url = plugin_dir_url( __FILE__ );

		// register the blocks
		add_action( 'init', array($this, 'register_blocks'), 95 );
		add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );

		add_action( 'admin_init', array($this, 'replace_shortcodes') );
		add_action( 'admin_init', array($this, 'get_original_post') );

		// enable block editor for products
		add_action( 'admin_init', array($this, 'maybe_enable_block_editor_for_products') );
		
		// fix archive template for global taxonomies
		add_filter( 'woocommerce_has_block_template', function( $has_template, $template_name ) {
			if ( !$has_template ) {
				// check if template post exists
				$post_ids = get_posts( array(
					'name'   => $template_name,
					'post_type'   => array( 'wp_template', 'wp_template_part' ),
					'fields' => 'ids'
				) );
				if ( $post_ids && count($post_ids) > 0 ) return true;
			}
			return $has_template;
		}, 10, 2 );
	}

	/**
	 * Register the blocks
	 */
	public function register_blocks() {
		
		// register editor blocks script
		wp_register_script(
			'greyd-woo-blocks',
			$this->config->assets_url.'blocks.js',
			array( 'greyd-woocommerce', 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// register the Woo Dynamic Content Block
		register_block_type( 'greyd/woocontent', array(
			'editor_script' => 'greyd-woo-blocks',
		) );

		register_block_type( 'greyd/woo-account', array(
			'editor_script' => 'greyd-woo-blocks',
		) );

		// woocommerce-germanized addons
		if ( \Greyd\Helper::is_active_plugin('woocommerce-germanized/woocommerce-germanized.php') ) {
			register_block_type( 'greyd/gzd-payment-methods-info', array(
				'editor_script' => 'greyd-woo-blocks',
			) );
			register_block_type( 'greyd/gzd-complaints', array(
				'editor_script' => 'greyd-woo-blocks',
			) );
			register_block_type( 'greyd/gzd-revocation-form', array(
				'editor_script' => 'greyd-woo-blocks',
			) );
			register_block_type( 'greyd/gzd-vat-info', array(
				'editor_script' => 'greyd-woo-blocks',
			) );
			register_block_type( 'greyd/gzd-sale-info', array(
				'editor_script' => 'greyd-woo-blocks',
			) );
		}
	}

	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {
		
		if ( !is_admin() ) return;

		wp_register_script(
			'greyd-woocommerce',
			$this->config->assets_url.'helper.js',
			array('greyd-tools', 'wp-i18n'),
			GREYD_VERSION
		);
		wp_add_inline_script('greyd-woocommerce', 'greyd.woo = '.json_encode( array(
			'data' => array(
				'wizard_url' => admin_url('edit.php?post_type=dynamic_template&wizard=add'),
				'shop_page_id' => intval(get_option('woocommerce_shop_page_id')),
				'my_account_page_id' => intval(get_option('woocommerce_myaccount_page_id')),
				'cart_page_id' => intval(get_option('woocommerce_cart_page_id')),
				'checkout_page_id' => intval(get_option('woocommerce_checkout_page_id')),
			)
		) ).';', 'before' );
		wp_enqueue_script('greyd-woocommerce');

		// translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'greyd-woocommerce', 'greyd_hub', $this->config->plugin_path."/languages" );
			wp_set_script_translations( 'greyd-woo-blocks', 'greyd_hub', $this->config->plugin_path."/languages" );
		}

	}

	public function replace_shortcodes() {

		if( !is_admin() ) return;
		
		// only modify edit screens
		if ( !isset($_GET['post']) ) return;
		
		// get post
		$post_id    = $_GET['post'];
		$post       = get_post($post_id);
		if (!is_object($post)) return;

		// get post content
		$content    = $post->post_content;
		$before     = '<!-- wp:shortcode -->';
		$after      = '<!-- /wp:shortcode -->';
		// on shop page
		if ( $post_id === get_option('woocommerce_shop_page_id') ) {

			// force post content because it is also forced by woocommerce
			if ( empty($content) ) {
				$post->post_content = '<!-- wp:greyd/woocontent -->WOO_CONTENT<!-- /wp:greyd/woocontent -->';
				wp_update_post($post);
			}
		}

		// on cart page
		if ( $post_id === get_option('woocommerce_cart_page_id') ) {
			if ( $content == $before.'[woocommerce_cart]'.$after ) {
				$post->post_content = '<!-- wp:greyd/woo-cart -->[woocommerce_cart]<!-- /wp:greyd/woo-cart -->';
				wp_update_post($post);
			}
		} 

		// on checkout page
		if ( $post_id === get_option('woocommerce_checkout_page_id') ) {
			if ( $content == $before.'[woocommerce_checkout]'.$after ) {
				$post->post_content = '<!-- wp:greyd/woo-checkout -->[woocommerce_checkout]<!-- /wp:greyd/woo-checkout -->';
				wp_update_post($post);
			}
		} 

		// on myaccount page
		if ( $post_id === get_option('woocommerce_myaccount_page_id') ) {
			if ( $content == $before.'[woocommerce_my_account]'.$after ) {
				$post->post_content = '<!-- wp:greyd/woo-account -->[woocommerce_my_account]<!-- /wp:greyd/woo-account -->';
				wp_update_post($post);
			}
		} 
	}

	/**
	 * Get original post_id if this is a wpml translated site
	 */
	public function get_original_post() {
		if ( function_exists('icl_object_id') ) {
			// only modify edit screens
			if ( !isset($_GET['post']) ) return;
			
			// get post
			$post_id    = $_GET['post'];
			$post       = get_post($post_id);
			if (!is_object($post)) return;


			global $sitepress;
			if ( $sitepress && method_exists($sitepress, 'get_default_language') ) {
				$lang_orig  = $sitepress->get_default_language();
				$lang_cur   = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : explode('_', get_locale(),2)[0];
				if ( $lang_orig !== $lang_cur ) {
					$post_id = strval( icl_object_id($post_id, $post->post_type, false, $lang_orig) );
				}
			}
		}
	}
	
	/**
	 * Maybe enable Gutenberg editor for products
	 */
	public function maybe_enable_block_editor_for_products() {
		$webshop_settings = class_exists('\Greyd\Settings') ? \Greyd\Settings::get_setting(array('site', 'webshop')) : array();
		if ( isset($webshop_settings['product_blocks']) && $webshop_settings['product_blocks'] === 'true' ) {
			add_filter( 'use_block_editor_for_post_type', array($this, 'enable_block_editor_for_products'), 99, 2 );
			add_filter( 'woocommerce_taxonomy_args_product_cat', array($this, 'enable_taxonomy_rest') );
			add_filter( 'woocommerce_taxonomy_args_product_tag', array($this, 'enable_taxonomy_rest') );
		}
	}
	
	/**
	 * Enable Gutenberg editor for products
	 * 
	 * @filter use_block_editor_for_post_type
	 */
	public function enable_block_editor_for_products( $can_edit, $post_type ) {

		if ( $post_type == 'product' ) {
			$can_edit = true;
		}
		return $can_edit;
	}
   
	/**
	 * Enable taxonomy fields for products
	 * 
	 * @filter woocommerce_taxonomy_args_product_cat
	 * @filter woocommerce_taxonomy_args_product_tag
	 */
	public function enable_taxonomy_rest( $args ) {
		$args['show_in_rest'] = true;
		return $args;
	}
}

