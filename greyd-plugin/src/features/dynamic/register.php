<?php
/**
 * Main Script to manage Dynamic Features.
 */
namespace Greyd\Dynamic;

use Greyd\Helper as Helper;

use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Register( $config );
class Register {

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
	private static $post_type = 'dynamic_template';


	/**
	 * Class constructor.
	 */
	public function __construct( $config ) {
		// set config
		$this->config = (object) $config;

		// define and add custom post type
		add_action( 'init', array( $this, 'register_posttype' ) );

	}

	/*
	====================================================================================
		Dynamic Template Posttype
	====================================================================================
	*/

	/**
	 * Register the posttype
	 */
	public function register_posttype() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		$name             = __( 'Template', 'greyd_hub' );
		$name_plural      = __( 'Templates', 'greyd_hub' );
		$full_name        = __( 'Dynamic Template', 'greyd_hub' );
		$full_name_plural = __( 'Dynamic Templates', 'greyd_hub' );

		$post_type_labels    = array(
			'name'               => __( 'Dynamic Templates', 'greyd_hub' ),
			'singular_name'      => $name,
			'menu_name'          => $name_plural,
			'name_admin_bar'     => $name,
			'add_new'            => sprintf( __( "New %s", 'greyd_hub' ), $name ),
			'add_new_item'       => sprintf( __( "Create %s", 'greyd_hub' ), $name ),
			'new_item'           => sprintf( __( "New %s", 'greyd_hub' ), $name ),
			'edit_item'          => sprintf( __( "Edit %s", 'greyd_hub' ), $name ),
			'view_item'          => sprintf( __( "Show %s", 'greyd_hub' ), $name ),
			'all_items'          => sprintf( __( "All %s", 'greyd_hub' ), $name_plural ),
			'search_items'       => sprintf( __( "Search %s", 'greyd_hub' ), $name ),
			'parent_item_colon'  => sprintf( __( "Parent %s", 'greyd_hub' ), $name ),
			'not_found'          => sprintf( __( "No %s found", 'greyd_hub' ), $name_plural ),
			'not_found_in_trash' => sprintf( __( "No %s found in the trash", 'greyd_hub' ), $name_plural ),
		);
		$post_type_arguments = array(
			'labels'                => $post_type_labels,
			'public'                => false, // doesn't have a permalink etc.
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'show_in_admin_bar'     => false,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => Dynamic_Helper::get_slug() ),
			'capability_type'       => 'page',
			'taxonomies'            => array( Dynamic_Helper::get_slug() . '_categories', 'template_type' ),
			'has_archive'           => true,
			'hierarchical'          => true,
			'menu_position'         => 20,
			// 'menu_icon'             => plugin_dir_url(__FILE__) . '/assets/img/greyd-menuicon-templates.svg',
			'menu_icon'             => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik05Ljc0MjU2IDFMMTcuNDg1MSA1LjQzNDQ0VjE0LjMyOTVMOS43NDI1NiAxOC43MjY4TDIgMTQuMzI5NVY1LjQzNDQ0TDkuNzQyNTYgMVpNMTAuNDkyNCAxNi41NzU5TDE1Ljk4NTEgMTMuNDU2NFY3LjEzNDVMMTAuNDkyNCAxMC4zMTE4VjE2LjU3NTlaTTkuNzQyNTYgMi43Mjg2TDE1LjIwMTMgNS44NTUwMkw5Ljc0MjU0IDkuMDEyNzNMNC4yODM3OSA1Ljg1NTA0TDkuNzQyNTYgMi43Mjg2WiIgZmlsbD0iI0Y2RjdGNyIvPgo8L3N2Zz4K',
			// 'menu_position'      => 3,
			// 'menu_icon'          => 'dashicons-layout',
			'supports'              => array( 'title', 'editor', 'custom-fields' ),
			'show_in_rest'          => true,
			'rest_base'             => Dynamic_Helper::get_slug(),
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);
		register_post_type( Dynamic_Helper::get_slug(), $post_type_arguments );

		$name               = __( "Category", 'greyd_hub' );
		$name_plural        = __( "Categories", 'greyd_hub' );
		$category_arguments = array(
			'labels'             => array(
				'name'                       => $name_plural,
				'singular_name'              => $name,
				'menu_name'                  => $name_plural,
				'search_items'               => sprintf( __( "Search %s", 'greyd_hub' ), $name_plural ),
				'popular_items'              => sprintf( __( "Popular %s", 'greyd_hub' ), $name_plural ),
				'all_items'                  => sprintf( __( "All %s", 'greyd_hub' ), $name_plural ),
				'edit_item'                  => sprintf( __( "Edit %s", 'greyd_hub' ), $name ),
				'update_item'                => sprintf( __( 'Update %s', 'greyd_hub' ), $name ),
				'add_new_item'               => sprintf( __( "New %s", 'greyd_hub' ), $name ),
				'new_item_name'              => sprintf( __( "New %s name", 'greyd_hub' ), $name ),
				'separate_items_with_commas' => sprintf( __( "Separate %s by commas", 'greyd_hub' ), $name_plural ),
				'add_or_remove_items'        => sprintf( __( "Add or remove %s", 'greyd_hub' ), $name ),
				'choose_from_most_used'      => sprintf( __( "Select %s from most used", 'greyd_hub' ), $name ),
				'not_found'                  => sprintf( __( "No %s found.", 'greyd_hub' ), $name_plural ),
			),
			'hierarchical'       => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'template_categories' ),
			'show_in_rest'       => true,
		);
		register_taxonomy( 'template_categories', Dynamic_Helper::get_slug(), $category_arguments );

		$this->register_template_types();
	}

	public function register_template_types() {

		if ( ! is_admin() ) {
			return;
		}

		$name               = __( "Type", 'greyd_hub' );
		$name_plural        = __( "Types", 'greyd_hub' );
		$taxonomy_arguments = array(
			'labels'             => array(
				'name'                       => $name_plural,
				'singular_name'              => $name,
				'menu_name'                  => $name_plural,
				'search_items'               => sprintf( __( "Search %s", 'greyd_hub' ), $name_plural ),
				'popular_items'              => sprintf( __( "Popular %s", 'greyd_hub' ), $name_plural ),
				'all_items'                  => sprintf( __( "All %s", 'greyd_hub' ), $name_plural ),
				'edit_item'                  => sprintf( __( "Edit %s", 'greyd_hub' ), $name ),
				'update_item'                => sprintf( __( 'Update %s', 'greyd_hub' ), $name ),
				'add_new_item'               => sprintf( __( "New %s", 'greyd_hub' ), $name ),
				'new_item_name'              => sprintf( __( "New %s name", 'greyd_hub' ), $name ),
				'separate_items_with_commas' => sprintf( __( "Separate %s by commas", 'greyd_hub' ), $name_plural ),
				'add_or_remove_items'        => sprintf( __( "Add or remove %s", 'greyd_hub' ), $name ),
				'choose_from_most_used'      => sprintf( __( "Select %s from most used", 'greyd_hub' ), $name ),
				'not_found'                  => sprintf( __( "No %s found.", 'greyd_hub' ), $name_plural ),
			),
			'hierarchical'       => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'template_type' ),
		);
		register_taxonomy( 'template_type', Dynamic_Helper::get_slug(), $taxonomy_arguments );

		// delete wrong template types
		$terms = get_terms(
			array(
				'taxonomy'   => 'template_type',
				'hide_empty' => false,
			)
		);
		$slugs = array( 'dynamic', 'navigation', 'single', 'archives', 'search', 'more', 'woo' );
		foreach ( $terms as $term ) {
			if ( ! in_array( $term->slug, $slugs ) ) {
				wp_delete_term( $term->term_id, 'template_type' );
			}
		}
		// insert template types if not exist
		foreach ( Dynamic_Helper::get_template_types() as $type ) {
			if ( is_null( term_exists( $type['slug'], 'template_type' ) ) ) {
				wp_insert_term(
					$type['title'],
					'template_type',
					array(
						'slug'        => $type['slug'],
						'description' => $type['description'],
					)
				);
			}
		}
		// add type to template if not exist
		$posts = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => Dynamic_Helper::get_slug(),
				'post_status'    => 'publish',
				'tax_query'      => array(
					array(
						'taxonomy' => 'template_type',
						'field'    => 'slug',
						// 'terms' => 'dynamic',
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$slug = $post->post_name;
				$slgs = explode( '-', $slug );
				if ( $slgs[0] == 'single' || $slgs[0] == 'archives' || $slgs[0] == 'search' || $slgs[0] == 'woo' ) {
					$ttype = $slgs[0];
				} else {
					if ( $slug == 'footer' || strpos( $slug, '-menu-offcanvas' ) > 0 ) {
						$ttype = 'navigation';
					} elseif ( $slug == '404' || $slgs[0] == '404' || $slug == 'cookiebar' || $slug == 'compatibility' ) {
						$ttype = 'more';
					} else {
						$ttype = 'dynamic';
					}
				}

				// if it its our new version we dont't have system templates anymore
				if ( !Helper::is_greyd_classic() ) {
					$ttype = 'dynamic';
				}

				wp_set_object_terms( $post->ID, $ttype, 'template_type' );
			}
		}
	}

}
