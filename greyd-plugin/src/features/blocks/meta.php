<?php
/**
 * Register & add meta options for the block editor.
 */
namespace greyd\blocks;

if( ! defined( 'ABSPATH' ) ) exit;

new meta($config);
class meta {
	
	public function __construct() {

		// register post meta for editor preview helper
		add_action( 'init', array($this, 'register_meta') );
		
		// extend user_meta for hover helper settings
		add_action( 'rest_api_init', array($this, 'adding_user_meta_rest') );
	}

	/**
	 * Register custom post meta 'greyd_block_editor_preview'
	 */
	public function register_meta() {

		/**
		 * Custom meta option to customize the editor
		 */
		register_meta(
			'post',
			'greyd_block_editor_preview',
			array(
				'type'              => 'object',
				'description'       => __( "Adjust width and background for this post", 'greyd_forms' ),
				'auth_callback'     => '__return_true',
				'single'            => true,
				// 'default'           => (object) array(
				// 	'enabled' => false,
				// 	'maxWidth' => '',
				// 	'backgroundColor' => ''
				// ),
				'show_in_rest'      => array(
					'schema' => array(
						'type' => 'object',
						'properties' => array(
							'enabled' => array(
								'type' => 'boolean',
							),
							'maxWidth'  => array(
								'type' => 'string',
							),
							'backgroundColor'  => array(
								'type' => 'string',
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Add 'greyd_user_settings' to rest api 'user' endpoint
	 */
	public function adding_user_meta_rest() {
		register_rest_field( 'user',
			'greyd_user_settings',
			array(
				'get_callback' => function( $user, $fieldname, $request) {
					// debug($user);
					// debug($fieldname);
					// debug($request);
					$meta = get_user_meta($user['id'], $fieldname, true);
					if (!$meta || empty($meta) || !is_array($meta)) $meta = array(
						'hover_helper' => false,
						'hover_helper_colors' => false
					);
					// debug($meta);
					return $meta;
				},
				'update_callback' => function ( $value, $user, $fieldname ) {
					// debug($value);
					// debug($user);
					// debug($fieldname);
					// Update the field/meta value.
					update_user_meta( $user->ID, $fieldname, $value );
				},
				'schema' => null,
			)
		);
	}
}