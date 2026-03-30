<?php
/**
 * Utility functions for public usage.
 * 
 * Functions of this file need to have:
 * * A unique & easily understandable name.
 * * Inline documentation of usage & properties.
 * * Always stay backward compatible.
 */

if ( !function_exists('get_dynamic_fields') ) {
	/**
	 * Get all field values of a dynamic post.
	 * 
	 * @param int|WP_Post $post_id  Post or Post-ID. Defaults to the current post.
	 * 
	 * @return array Array of posttype fields (empty array if nothing found).
	 */
	function get_dynamic_fields( $post_id=null ) {

		if ( is_object($post_id) ) {
			$post_id = isset($post_id->ID) ? $post_id->ID : null;
		}
		else if ( !$post_id ) {
			$post_id = get_the_ID();
		}

		if ( !$post_id ) return array();

		$fields = class_exists( 'Greyd\Posttypes\Posttype_Helper' ) ? Greyd\Posttypes\Posttype_Helper::get_dynamic_values( $post_id ) : array();

		return $fields ? (array) $fields : array();
	}
}

if ( !function_exists('get_dynamic_field') ) {
	/**
	 * Get the meta value of a dynamic post field.
	 * 
	 * @param string $field_name    Name of the meta field.
	 * @param int|WP_Post $post_id  Post or Post-ID. Defaults to the current post.
	 * 
	 * @return mixed|null   Raw field value.
	 *                      Null if field value not set or post not found
	 */
	function get_dynamic_field( $field_name, $post_id=null ) {

		$fields = get_dynamic_fields( $post_id );

		if ( ! $fields ) return $fields;

		return isset( $fields[ $field_name ] ) ? $fields[ $field_name ] : null;
	}
}

if ( !function_exists( 'get_is_greyd_blocks' ) ) {

	/**
	 * Get the necessary information form the database,
	 * whether this is a greyd_blocks installation.
	 * @since 1.4.1
	 * 
	 * @param bool $default     The default value if no value is found.
	 * @return bool
	 */
	function get_is_greyd_blocks( $default=true ) {

		/**
		 * Support for constant override.
		 * @since 1.6.0
		 */
		if ( defined('IS_GREYD_BLOCKS') && constant('IS_GREYD_BLOCKS') ) {
			return true;
		}

		// check if Greyd Theme is active
		if ( defined( 'GREYD_THEME_CONFIG' ) ) {
			return true;
		}

		if ( get_option('greyd_gutenberg') === true || get_option('greyd_gutenberg') == '1' ) return true;

		$settings = (array) get_option('settings_site_greyd_tp', array());
		if ( isset($settings['builder']) && $settings['builder'] === 'gg-gg' ) {
			return true;
		}

		$dynamic_template_post = get_posts( array(
			'posts_per_page'   => 1,
			'post_type'        => 'dynamic_template',
			'suppress_filters' => true,
			'fields'           => 'ids'
		) );

		if ( empty( $dynamic_template_post ) ) {
			return $default;
		}

		return false;
	}
}

if ( ! function_exists('is_greyd_blocks') ) {

	// don't plug function in classic theme lower 1.4.4 - it is not yet wrapped
	if ( ! ( $config['is_greyd_classic'] && version_compare( $_current_main_theme->get('Version'), '1.4.4', '<' ) ) ) {

		/**
		 * Check if this is a blocks site using cache.
		 * @since 1.4.1
		 * 
		 * @param bool $use_cache   Whether to use the cached value.
		 * @param bool $default     The default value if no value is found.
		 * 
		 * @return bool
		 */
		function is_greyd_blocks( $use_cache=true, $default=true ) {

			/**
			 * Support for constant override.
			 * @since 1.6.0
			 */
			if ( defined('IS_GREYD_BLOCKS') && constant('IS_GREYD_BLOCKS') ) {
				return true;
			}
		
			if ( $use_cache && $_cache = wp_cache_get( 'is_greyd_blocks', 'greyd' ) ) {
				return $_cache === '1';
			}
		
			$is_blocks_site = get_is_greyd_blocks( $default );
		
			wp_cache_set( 'is_greyd_blocks', strval($is_blocks_site), 'greyd' );
		
			return $is_blocks_site;
		}

	}

}

add_action( 'after_setup_theme', function() {
	if ( !function_exists('debug') ) {

		/**
		 * Debug function.
		 * 
		 * @param mixed $a  Variable to debug.
		 * @param bool $b   Whether to use var_dump instead of print_r.
		 */
		function debug( $a, $b=false ) {
			echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>';
		}
	}
	
	if ( ! function_exists( 'var_error_log' ) ) {
		function var_error_log( $object = null ) {
			ob_start();                    // start buffer capture
			var_dump( $object );           // dump the values
			$contents = ob_get_contents(); // put the buffer into a variable
			ob_end_clean();                // end capture
			error_log( $contents );        // log contents of the result of var_dump( $object )
		}
	}
} );

if ( !function_exists('__debug') ) {

	/**
	 * Optional debug function, only logs if $_GET['debug'] is set.
	 * This helps to avoid accidental debug output on live sites.
	 * 
	 * @param mixed $a  Variable to debug.
	 * @param bool $b   Whether to use var_dump instead of print_r.
	 */
	function __debug( $a, $b=false ) {
		if ( ! isset( $_GET['debug'] ) ) return;
		echo '<pre>'; !$b ? print_r($a) : var_dump($a); echo '</pre>';
	}
}
