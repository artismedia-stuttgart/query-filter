<?php
/**
 * Global Dynamic Tags feature.
 */

namespace Greyd\Posttypes\GDT;

use Greyd\Helper as Helper;
use Greyd\Posttypes\Admin as Posttype_Admin;
use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Admin($config);
class Admin {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;

		add_action( 'init', array($this, 'init') );
	}

	public function init() {
		
		if ( !class_exists( '\Greyd\Dynamic\Admin' ) ) return;
		if ( !class_exists( '\Greyd\Posttypes\Admin' ) ) return;

		// add admin features
		add_filter( 'greyd_posttypes_settings_architecture_checkboxes', array($this, 'filter_posttype_settings'), 10, 2 );
		add_filter( 'display_post_states', array($this, 'edit_posttype_admin_state'), 10, 2 );

		// enqueue dynamic scripts
		add_action( 'enqueue_block_editor_assets', array($this, 'register_blocks_assets') );
	}

	/**
	 * Add the checkbox 'is_global_dynamic_tag' to posttype options.
	 */
	public function filter_posttype_settings( $checkboxes, $post ) {

		$checkboxes[ 'is_global_dynamic_tag' ] = array(
			'label' => __("Make data from the latest post available globally.", 'greyd_hub')/* . '&nbsp;' . Helper::render_feature_tag()*/,
			'description' => __("All users can always use the data of the latest post in the editor via dynamic tag.", 'greyd_hub')
		);

		return $checkboxes;
	}

	/**
	 * Add state "Global Dynamic Tags" after title
	 */
	public function edit_posttype_admin_state( $states, $post ) {
		if (
			$post
			&& isset( $post->ID )
			&& Posttype_Admin::POST_TYPE == get_post_type( $post->ID) 
		) {

			$posttype = Posttype_Helper::get_dynamic_posttype_by_id( $post->ID );
			
			if (
				$posttype
				&& isset( $posttype['arguments'] )
				&& isset( $posttype['arguments']['is_global_dynamic_tag'] )
			) {
				$states['edited_posttype'] = __("Global Dynamic Tags", "greyd_hub");
			}

		}

		return $states;
	}

	/**
	 * Register and enqueue script for the editor
	 */
	public function register_blocks_assets() {

		$js_uri = plugin_dir_url( __FILE__ ) . 'assets/js/';

		// dtag format script
		wp_register_script(
			'greyd-global-dynamic-tags',
			trailingslashit( $js_uri ).'admin.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-global-dynamic-tags');
		
		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-global-dynamic-tags', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
	}
}