<?php
/**
 * Posttypes Feature compatibility dummy.
 * The old version of of the Posttypes Posttypes exposed a few static functions that are used by Theme and Plugins.
 * Those are re-created here and re-routed to their versions within the new namespaces.
 */
namespace {

	if ( !defined( 'ABSPATH' ) ) exit;

	if ( !class_exists( 'posttypes' ) ) {
		class posttypes {

			/**
			 * Add all dynamic post types based on the posts of the main posttype.
			 */
			public static function add_dynamic_posttypes() {

				\Greyd\Posttypes\Dynamic_Posttypes::add_dynamic_posttypes();

			}

			/**
			 * Get dynamic posttype by slug.
			 * Result is cached ( 'dynamic_posttype_'.$slug, 'greyd' )
			 * 
			 * @param string $slug	Posttype slug
			 * @return object|bool	posttype or false
			 */
			public static function get_dynamic_posttype_by_slug( $slug ) {

				return \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttype_by_slug( $slug );

			}

			/**
			 * Get posttype settings.
			 * 
			 * @param int $post_id	WP_Post ID.
			 * @return array
			 */
			public static function get_posttype_settings( $post_id ) {

				return \Greyd\Posttypes\Posttype_Helper::get_posttype_settings( $post_id );

			}

			/**
			 * Update posttype settings.
			 * 
			 * @param int $post_id	WP_Post ID.
			 * @param array $settings
			 */
			public static function update_posttype_settings( $post_id, $settings ) {

				\Greyd\Posttypes\Posttype_Helper::update_posttype_settings( $post_id, $settings );

			}

			/**
			 * Retrieve dynamic field values.
			 * 
			 * @param WP_Post|int $post WP_Post object or ID.
			 * @param array $args       Additional arguments
			 *      @property bool rawurlencode         Default: true
			 *      @property bool resolve_file_ids     Default: false
			 * 
			 * @return array
			 */
			public static function get_dynamic_values( $post, $args=array() ) {

				return \Greyd\Posttypes\Posttype_Helper::get_dynamic_values( $post, $args );

			}

			/**
			 * Get (and repair) metadata for posttype.
			 * 
			 * @param int $post_id	WP_Post ID.
			 * @return array
			 */
			public static function get_dynamic_meta( $post_id ) {

				return \Greyd\Posttypes\Posttype_Helper::get_dynamic_meta( $post_id );

			}

		}
	}

}

/**
 * The old version of Automator and Synched_Posttype Features are used by the internal plugins
 * 'cancom_investornews' and 'cancom_jobs'. 
 * The classes with the old namespace are recreated here by just extending the new ones.
 */
namespace greyd {

	if ( !defined( 'ABSPATH' ) ) exit;

	if ( !class_exists( 'greyd\Automator' ) ) {

		class Automator extends \Greyd\Automator {}

	}

	if ( !class_exists( 'greyd\Synced_Posttype' ) ) {

		class Synced_Posttype extends \Greyd\Synced_Posttype {}

	}

}
