<?php
/**
 * ==== ALPHA FEATURE ====
 * 
 * This is a snippet currently not in use.
 * It is being tested by various clients in real world scenarios
 * to make sure it doesn't break anything or affects performance.
 *
 * It is likely to be included in a future release a new option
 * inside the search settings to enable this feature.
 */

// Add filters to modify the search query
add_filter( 'posts_join', 'modify_search_join', 10, 2 );
add_filter( 'posts_where', 'modify_search_where', 10, 2 );

function modify_search_join( $join, $wp_query ) {
	global $wpdb;

	// Check if it's a search query
	if ( $wp_query->is_search ) {
		// Join the referenced post to the main query based on the template values
		$join .= " LEFT JOIN {$wpdb->prefix}posts AS referenced_post ON referenced_post.ID = SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->prefix}posts.post_content, '\"template\":\"', -1), '\"', 1), '\"', -1)";
	}

	return $join;
}

function modify_search_where( $where, $wp_query ) {
	global $wpdb;

	// Check if it's a search query
	if ( $wp_query->is_search ) {
		// Include the content of the referenced post dynamically in the search
		$where = preg_replace(
			'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			'(' . $wpdb->posts . '.post_title LIKE $1) OR (referenced_post.post_content LIKE $1)',
			$where
		);
	}

	return $where;
}
