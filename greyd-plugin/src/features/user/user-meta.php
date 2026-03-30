<?php
namespace Greyd\User;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Meta();
class Meta {

	public function __construct() {

		// add user data to greyd data array
		add_filter( 'greyd_blocks_editor_data', array( $this, 'add_user_data_to_greyd_data') );

		// render dynamic tags
		add_filter( 'greyd_render_dynamic_tag', array($this, 'render_user_meta_dynamic_tag'), 10, 5 );
		add_filter( 'greyd_get_dynamic_url', array($this, 'get_user_meta_dynamic_url'), 10, 4 );

		// add hook for ajax handling
		add_action( 'greyd_ajax_mode_user_meta', array( $this, 'update_role_user_meta' ) );
	}

	/**
	 * Add User Data to Greyd Data Array.
	 * 
	 * @filter 'greyd_blocks_editor_data'
	 * 
	 * @param object $data     Original Data Array
	 * 
	 * @return object $data    Data Array with filtered Values
	 */
	public function add_user_data_to_greyd_data( $greyd_data ) {

		if ( !isset( $greyd_data['user_roles'] ) ) {
			$greyd_data['user_roles'] = get_editable_roles();
		}

		foreach ( $greyd_data['user_roles'] as $role => $role_data ) {

			if ( !isset( $greyd_data['user_roles'][ $role ]['meta_fields'] ) ) {
				$greyd_data['user_roles'][ $role ]['meta_fields'] = array();
			}
			
			$role_object = Manage::get_role( $role );

			// add meta fields to user
			if ( $role_object && isset( $role_object['fields'] ) ) {
				$greyd_data['user_roles'][ $role ]['meta_fields'] = $role_object['fields'];
			}
		}

		return $greyd_data;
	}

	/**
	 * @filter greyd_render_dynamic_tag
	 * 
	 * @param string $html      HTML content of the parsed tag.
	 * @param string $name      Name of the dynamic tag
	 * @param string $params    Dynamic Tag Paras as json string.
	 * @param object $block     Parsed Block.
	 * @param WP_Post $wp_post  Post object.
	 * 
	 * @return string $html
	 */
	public function render_user_meta_dynamic_tag( $html, $name, $params, $block, $wp_post ) {

		if ( strpos($name, 'user-meta-') !== 0 ) return $html;

		list( $_userstring, $_metastring, $tagName ) = explode( "-", $name, 3 );;

		if ( empty( $tagName ) ) {
			return $html;
		}

		// get user
		$user = wp_get_current_user();
		if ( ! $user || ! isset( $user->ID ) ) {
			return $html;
		}

		$core_meta_value = get_user_meta( $user->ID, $tagName, true );
		if ( !empty( $core_meta_value ) ) {
			return $core_meta_value;
		}

		// get meta value
		return get_user_meta( $user->ID, 'greyd_' . $tagName, true );
	}

	/**
	 * @filter greyd_get_dynamic_url
	 * 
	 * @param string $html      HTML content of the parsed tag.
	 * @param string $name      Name of the dynamic tag
	 * @param string $params    Dynamic Tag Paras as json string.
	 * @param object $block     Parsed Block.
	 * @param WP_Post $wp_post  Post object.
	 * 
	 * @return string $html
	 */
	public function get_user_meta_dynamic_url( $html, $name, $block, $wp_post ) {

		if ( strpos($name, 'user-meta-') !== 0 ) return $html;

		list( $_userstring, $_metastring, $tagName ) = explode( "-", $name, 3 );;

		if ( empty( $tagName ) ) {
			return $html;
		}

		// get user
		$user = wp_get_current_user();
		if ( ! $user || ! isset( $user->ID ) ) {
			return $html;
		}

		$core_meta_value = get_user_meta( $user->ID, $tagName, true );
		if ( !empty( $core_meta_value ) ) {
			return $core_meta_value;
		}

		// get meta value
		return get_user_meta( $user->ID, 'greyd_' . $tagName, true );
	}
	
	/**
	 * Handle the ajax 'update_meta' action
	 *
	 * @action 'greyd_ajax_mode_user_meta'
	 *
	 * @param array $data   holds the $_POST['data']
	 */
	public function update_role_user_meta( $data ) {

		$args = wp_parse_args( $data, array(
			'copyRole' => '',
			'copyAction' => '',
			'fields' => array()
		) );

		$role = Manage::get_role( $args['copyRole'] );

		if (
			empty( $args['copyRole'] )
			|| empty( $args['copyAction'] )
			|| empty( $args['fields'] )
			|| empty( $role )
			|| empty( $role['slug'] )
		) {
			wp_die( "error::missing data" );
		}

		// remove all fields if overwrite is set
		if ( strpos( $args['copyAction'], '-overwrite' > 0 ) ) {
			$role['fields'] = array();
		}

		// add fields to role
		foreach ( $args['fields'] as $field ) {
			$role['fields'][] = $field;
		}

		// debug( $role );

		$result = Manage::update_role( $args['copyRole'], $role );

		if ( !$result ) {
			wp_die( "error::" . sprintf( __( "Unable to create role '%s'.", "greyd_hub" ), $args['copyRole'] ) );
		}

		wp_die( "success::all good!" );
	}
}