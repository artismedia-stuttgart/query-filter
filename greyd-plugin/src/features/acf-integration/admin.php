<?php
/**
 * ACF Dynamic Tags feature.
 */

namespace Greyd\Posttypes\ACF;

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

		add_filter( 'greyd_blocks_editor_data', array($this, 'add_acf_fields_to_greyd_data') );

		add_action( 'greyd_after_import_post', array($this, 'after_import_post'), 10, 2 );
	}

	public function init() {
		
		if ( !class_exists( '\Greyd\Dynamic\Admin' ) ) return;
		if ( !class_exists( '\Greyd\Posttypes\Admin' ) ) return;
		if ( !class_exists('ACF') ) return;

		// enqueue dynamic scripts
		add_action( 'enqueue_block_editor_assets', array($this, 'register_blocks_assets') );
	}

	/**
	 * Register and enqueue script for the editor
	 */
	public function register_blocks_assets() {

		if ( ! class_exists( 'ACF' ) ) return;

		$js_uri = plugin_dir_url( __FILE__ ) . 'assets/js/';

		// dtag format script
		wp_register_script(
			'greyd-acf-dynamic-tags',
			trailingslashit( $js_uri ).'editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'underscore' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-acf-dynamic-tags');
		
		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-acf-dynamic-tags', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
	}

	/**
	 * Add ACF fields to the greyd data (per post type)
	 * 
	 * @param array $data The greyd data.
	 * 
	 * @return array The modified greyd data.
	 */
	public function add_acf_fields_to_greyd_data($data) {

		if ( ! class_exists( 'ACF' ) ) return $data;
		if ( ! isset( $data['post_types'] ) ) return $data;

		foreach ( $data['post_types'] as $key => $post_type_data ) {

			if ( isset( $post_type_data['acf'] ) ) continue;

			$posttype_name = isset( $post_type_data['slug'] ) ? $post_type_data['slug'] : null;

			$acf_fields = array();

			// get all acf fields for this post type
			if ( $posttype_name ) {
				$acf_fields = $this->get_acf_fields_for_post_type( $posttype_name );
			}

			$data['post_types'][$key]['acf'] = $acf_fields;
		}

		return $data;
	}

	/**
	 * Check ACF Fields after a post was imported to make sure the values are correct.
	 * Metas with empty values will be deleted.
	 * @action 'greyd_after_import_post'
	 * 
	 * @param int $new_post_id
	 * @param object $post
	 */
	public function after_import_post( $new_post_id, $post ) {

		if ( ! class_exists( 'ACF' ) ) return;
		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) return;
		if ( ! isset( $post->meta ) || empty( $post->meta ) ) return;

		// get acf fields
		$acf_fields = $this->get_acf_fields_for_post_type( $post->post_type );
		if ( empty( $acf_fields ) ) return;

		do_action( 'greyd_post_export_log', "\r\n" . 'Set ACF post meta.' );		

		$existing_meta = (array) get_post_meta( $new_post_id );
		$new_meta = (array) $post->meta;

		// debug($acf_fields);
		// debug($existing_meta);
		// debug($new_meta);

		foreach ( $acf_fields as $acf_field ) {
			$meta_key = $acf_field["name"];
			$old_value = isset( $existing_meta[$meta_key] ) ? maybe_unserialize( $existing_meta[$meta_key][0] ) : false;
			$new_value = isset( $new_meta[$meta_key] ) ? $new_meta[$meta_key][0] : false;
			if ( $new_value && ! is_array( $new_value ) ) {
				$new_value = maybe_unserialize( $new_value );
			}
			if ( json_encode( $old_value ) != json_encode( $new_value ) ) {
				if ( $new_value === false ) {
					do_action( 'greyd_post_export_log', '  - Delete ACF field "'.$meta_key.'".' );
					delete_post_meta( $new_post_id, $meta_key );
				}
				else if ( $old_value === false ) {
					do_action( 'greyd_post_export_log', '  - Add ACF field "'.$meta_key.'".' );
					add_post_meta( $new_post_id, $meta_key, $new_value );
				}
				else {
					do_action( 'greyd_post_export_log', '  - Update ACF field "'.$meta_key.'".' );
					update_post_meta( $new_post_id, $meta_key, $new_value, $old_value );
				}
			}
		}
		
		do_action( 'greyd_post_export_log', '=> ACF post meta set' );
	}


	/**
	 * Get all registered ACF fields for a specific post type.
	 *
	 * @param string $post_type The post type to get fields for.
	 * @return array An array of ACF field data.
	 */
	public function get_acf_fields_for_post_type( $post_type ) {
		// Check if ACF is active
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return [];
		}

		$fields = [];

		// Get all field groups
		$field_groups = acf_get_field_groups( array( 'post_type' => $post_type ) );

		// Loop through field groups
		foreach ( $field_groups as $group ) {
			// Check if the field group applies to the specified post type
			foreach ( $group['location'] as $rules ) {
				foreach ( $rules as $rule ) {
					if ( $rule['param'] === 'post_type' && $rule['operator'] === '==' && $rule['value'] === $post_type ) {
						// Get all fields for the group
						$group_fields = acf_get_fields( $group['ID'] );
						if ( $group_fields ) {
							$fields = array_merge( $fields, $group_fields );
						}
					}
				}
			}
		}

		return $fields;
	}
}