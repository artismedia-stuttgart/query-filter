<?php
/**
 * Global Dynamic Tags feature.
 */

namespace Greyd\Posttypes\GDT;

use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Render();
class Render {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'greyd_render_dynamic_tag', array($this, 'render_global_dynamic_tag'), 10, 5 );
		add_filter( 'greyd_get_dynamic_url', array($this, 'get_global_dynamic_url'), 10, 4 );
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
	public function render_global_dynamic_tag( $html, $name, $params, $block, $wp_post ) {

		if ( strpos($name, 'gdt-') !== 0 ) return $html;

		$tag_parts = explode( "-", $name );

		/**
		 * @since 1.7.4 Fixed global dynamic tags for post types with
		 * dashes in the name.
		 * If a post type includes a dash, the post type name can be
		 * more than one part. In this case, we need to add the parts
		 * together to get the full post type name.
		 */
		$posttype = $tag_parts[1];
		if ( ! post_type_exists( $posttype ) ) {
			$next_part = 2;
			while ( ! post_type_exists( $posttype ) ) {
				if ( ! isset( $tag_parts[ $next_part ] ) ) {
					break;
				}
				$posttype = $posttype . '-' . $tag_parts[ $next_part ];
				$next_part++;
			}
			if ( ! post_type_exists( $posttype ) ) {
				return $html;
			}
		}
		
		$tagName = explode( $posttype . '-', $name, 2 );
		if ( ! isset( $tagName[1] ) ) {
			return $html;
		}

		$tagName = $tagName[1];

		$recent_posts = get_posts( array(
			'post_type'   => $posttype,
			'numberposts' => 1,
			'fields'      => 'ids'
		) );

		if ( $recent_posts && isset($recent_posts[0]) ) {
			$values = Posttype_Helper::get_dynamic_values( $recent_posts[0], array(
				'rawurlencode' => false,
				'resolve_file_ids' => false
			) );

			if ( $values && isset( $values[$tagName] ) ) {
				$new         = $values[$tagName];
				$new_decoded = html_entity_decode( $new );
				$new         = $new === strip_tags( $new_decoded ) ? $new : $new_decoded;
				$new = \Greyd\Dynamic\Render_Blocks::render_dynamic_posttype_tag_params( $new, $tagName, $params, $posttype, $recent_posts[0] );
				return do_shortcode( $new );
			}
		}

		return $html;
	}

	/**
	 * @filter greyd_get_dynamic_url
	 * 
	 * @param string $url       URL.
	 * @param string $name      Name of the dynamic tag
	 * @param string $params    Dynamic Tag Paras as json string.
	 * @param object $block     Parsed Block.
	 * @param WP_Post $wp_post  Post object.
	 * 
	 * @return string $url
	 */
	public function get_global_dynamic_url( $url, $name, $block, $wp_post ) {

		if ( strpos($name, 'gdt-') !== 0 ) return $url;

		$tag_parts = explode( "-", $name );

		/**
		 * @since 1.7.4 Fixed global dynamic tags for post types with
		 * dashes in the name.
		 * If a post type includes a dash, the post type name can be
		 * more than one part. In this case, we need to add the parts
		 * together to get the full post type name.
		 */
		$posttype = $tag_parts[1];
		if ( ! post_type_exists( $posttype ) ) {
			$next_part = 2;
			while ( ! post_type_exists( $posttype ) ) {
				if ( ! isset( $tag_parts[ $next_part ] ) ) {
					break;
				}
				$posttype = $posttype . '-' . $tag_parts[ $next_part ];
				$next_part++;
			}
			if ( ! post_type_exists( $posttype ) ) {
				return $url;
			}
		}
		
		$tagName = explode( $posttype . '-', $name, 2 );
		if ( ! isset( $tagName[1] ) ) {
			return $url;
		}

		$tagName = $tagName[1];

		$recent_posts = get_posts( array(
			'post_type'   => $posttype,
			'numberposts' => 1,
			'fields'      => 'ids'
		) );

		if ( $recent_posts && isset($recent_posts[0]) ) {
			$values = Posttype_Helper::get_dynamic_values( $recent_posts[0], array(
				'rawurlencode' => false,
				'resolve_file_ids' => false
			) );

			if ( $values && isset( $values[$tagName] ) ) {
				return $values[$tagName];
			}
		}

		return $url;
	}
}