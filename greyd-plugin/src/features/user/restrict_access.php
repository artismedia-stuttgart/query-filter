<?php
/**
 * Restrict access to certain pages or posts.
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Restrict_Access();
class Restrict_Access {

	/**
	 * Holds the name of the meta option.
	 */
	const META_OPTION_NAME = 'greyd_user_post_exclude';

	/**
	 * Contrsuctor.
	 */
	public function __construct() {

		if ( !Manage::wp_up_to_date() ) {
			return;
		}

		// admin post metabox
		add_action( 'init', array( $this, 'register_post_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'post_updated', array( $this, 'save_post_metabox' ) );
		add_action( 'edit_attachment', array( $this, 'save_post_metabox' ) );

		// restrict access to frontend posts
		add_action( 'template_redirect', array( $this, 'restrict_access_to_post' ) );
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_objects' ), 10, 2 );
	}

	/**
	 * Register the post meta key.
	 */
	public function register_post_meta() {
		register_post_meta(
			'',
			self::META_OPTION_NAME,
			array(
				'type'    => 'array',
				'single'  => true,
				'default' => array(),
			)
		);
	}

	/**
	 * Add the metabox.
	 */
	public function add_metabox() {

		if (
			empty( get_current_screen()->post_type )
			|| !current_user_can( 'manage_options' )
		) {
			return;
		}
		
		add_meta_box(
			self::META_OPTION_NAME,
			__( "User management", 'greyd_hub' ),
			array( $this, 'render_metabox' ),
			null,
			'side'
		);
	}

	/**
	 * Render the post metabox.
	 *
	 * @param WP_Post $post
	 */
	public function render_metabox( $post ) {

		$meta_value = get_post_meta( $post->ID, self::META_OPTION_NAME, true );
		if ( ! is_array( $meta_value ) ) {
			$meta_value = array();
		}

		$roles = array_merge(
			Manage::get_roles(),
			array(
				array(
					'title' => __( "Not logged in", 'greyd_hub' ),
					'slug'  => 'none',
				),
			)
		);

		echo '<p><strong>' . __( "Exclude this post for users:", 'greyd_hub' ) . '</strong></p>';
		echo '<ul>';
		wp_nonce_field( self::META_OPTION_NAME . '_action', self::META_OPTION_NAME . '_nonce' );
		foreach ( $roles as $role ) {
			echo sprintf(
				'<li><label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s[]" value="%3$s" %4$s>%5$s</label></li>',
				// ID
				self::META_OPTION_NAME . '-' . $role['slug'],
				// option
				self::META_OPTION_NAME,
				// slug
				$role['slug'],
				// checked
				in_array( $role['slug'], $meta_value ) ? 'checked' : '',
				// title
				$role['title']
			);
		}
		echo '</ul>';
	}

	/**
	 * Save the post metabox.
	 *
	 * @param int $post_id
	 */
	public function save_post_metabox( $post_id ) {

		if (
			! isset( $_POST[ self::META_OPTION_NAME . '_nonce' ] )
			|| ! wp_verify_nonce( $_POST[ self::META_OPTION_NAME . '_nonce' ], self::META_OPTION_NAME . '_action' )
		) {
			return $post_id;
		}
	
		$meta_value = isset( $_POST[ self::META_OPTION_NAME ] ) ? $_POST[ self::META_OPTION_NAME ] : '';
		// var_error_log( $meta_value );
		update_post_meta( $post_id, self::META_OPTION_NAME, $meta_value );

		return $post_id;
	}

	/**
	 * Maybe restrict acces in the frontend and redirect to the 404 page.
	 *
	 * @param WP_Query $query
	 * @return WP_Query
	 */
	public function restrict_access_to_post() {

		if ( is_admin() ) {
			return;
		}

		// on single pages
		if ( is_singular() && ! is_archive() ) {

			if ( self::is_post_excluded_for_current_user( get_the_ID() ) ) {
				global $wp_query;
				$wp_query->is_404 = true;
			}
		}
	}

	/**
	 * Filters the sorted list of menu item objects before generating the menu's HTML.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_nav_menu/
	 *
	 * @param array    $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 * @param stdClass $args              An object containing wp_nav_menu() arguments.
	 */
	public function filter_nav_menu_objects( $sorted_menu_items, $args ) {

		foreach ( $sorted_menu_items as $i => $menu_item ) {

			// wp post
			if ( isset( $menu_item->object_id ) && ! empty( $menu_item->object_id ) ) {

				if ( self::is_post_excluded_for_current_user( $menu_item->object_id ) ) {
					unset( $sorted_menu_items[ $i ] );
				}
			}
		}

		return $sorted_menu_items;
	}

	/**
	 * Whether or not a post is excluded for the current user.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function is_post_excluded_for_current_user( $post_id ) {

		$meta_value = get_post_meta( $post_id, self::META_OPTION_NAME, true );

		if ( is_array( $meta_value ) && ! empty( $meta_value ) ) {
			return boolval( array_intersect( self::get_current_user_roles(), $meta_value ) );
		}

		return false;
	}

	/**
	 * Get the user roles of the current user.
	 *
	 * @return array
	 */
	public static function get_current_user_roles() {
		return is_user_logged_in() ? wp_get_current_user()->roles : array( 'none' );
	}
}
