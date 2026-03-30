<?php
/**
 * Render the page navigation block.
 */
namespace Greyd\Blocks;

if ( !defined( 'ABSPATH' ) ) exit;

// Get block attributes
$post_type         = $attributes['postType'] ?? 'page';
$inherit_post_type = $attributes['inheritPostType'] ?? false;
$max_depth         = $attributes['maxDepth'] ?? 3;
$icons             = array(
	'closed'   => $attributes['icon'] ?? 'arrow_carrot-right',
	'expanded' => $attributes['iconExpanded'] ?? 'arrow_carrot-down',
);
$greydClass        = isset( $attributes['greydClass'] ) ? $attributes['greydClass'] : helper::generate_greydClass();

// Get wrapper attributes including typography and spacing
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $greydClass ) );

// Determine the post type to use
if ( $inherit_post_type ) {
	if ( is_singular() ) {
		$post_type = get_post_type();
	} else if ( is_post_type_archive() ) {
		$post_type = get_post_type();
	} else {
		$post_type = \Greyd\Helper::is_rest_request() ? $post_type : 'page';
	}
}

$args = array(
	'post_type'    => $post_type,
	'hierarchical' => 1,
	'sort_column'  => 'menu_order, post_title',
	'sort_order'   => 'ASC',
);

$pages      = get_pages( $args );
$current_id = get_queried_object_id();

if ( !function_exists( __NAMESPACE__ . '\\page_navigation_has_children' ) ) {
	function page_navigation_has_children( $pages, $parent_id ) {
		foreach ( $pages as $page ) {
			if ( $page->post_parent == $parent_id ) {
				return true;
			}
		}
		return false;
	}
}

if ( !function_exists( __NAMESPACE__ . '\\page_navigation_is_ancestor' ) ) {
	function page_navigation_is_ancestor( $pages, $ancestor_id, $current_id ) {
		// Walk up the tree from current_id to see if ancestor_id is in the chain
		$parent_map = array();
		foreach ( $pages as $page ) {
			$parent_map[ $page->ID ] = $page->post_parent;
		}
		$pid = $current_id;
		while ( isset( $parent_map[ $pid ] ) && $parent_map[ $pid ] ) {
			if ( $parent_map[ $pid ] == $ancestor_id ) {
				return true;
			}
			$pid = $parent_map[ $pid ];
		}
		return false;
	}
}

if ( !function_exists( __NAMESPACE__ . '\\page_navigation_build_navigation' ) ) {
	function page_navigation_build_navigation( $pages, $parent = 0, $depth = 0, $max_depth = 3, $current_id = 0, $icons = array() ) {
		if ( $depth >= $max_depth ) {
			return '';
		}

		$output = '<ul class="page-navigation-list">';

		// Track how many items we've expanded for REST preview (block editor)
		static $expanded_count = 0;
		$is_rest               = \Greyd\Helper::is_rest_request();

		foreach ( $pages as $page ) {
			if ( $page->post_parent == $parent ) {
				$has_children = page_navigation_has_children( $pages, $page->ID );
				$is_current   = $page->ID == $current_id;
				$is_ancestor  = page_navigation_is_ancestor( $pages, $page->ID, $current_id );
				// Force expand first 2 items with children in REST preview
				$expand = $is_current || $is_ancestor || ( $is_rest && $has_children && $expanded_count < 2 );
				if ( $expand && $has_children && $is_rest ) {
					++$expanded_count;
				}
				$depth_class   = 'is-depth-'.( $depth + 1 );
				$current_class = $is_current ? ' is-current' : '';
				$li_class      = trim( $depth_class.$current_class );
				$output       .= '<li class="'.esc_attr( $li_class ).'">';

				// Create a wrapper for the link and toggle
				$output .= '<div class="page-navigation-item">';

				// Add the link
				$output .= sprintf(
					'<a href="%s" class="page-navigation-link">%s</a>',
					esc_url( get_permalink( $page->ID ) ),
					esc_html( $page->post_title )
				);

				// Add toggle button if has children AND we haven't reached max depth
				if ( $has_children && $depth < $max_depth - 1 ) {
					$toggle_id = 'toggle-'.$page->ID;
					$panel_id  = 'panel-'.$page->ID;
					// Expand if current or ancestor
					$expanded = $expand ? 'true' : 'false';
					$output  .= sprintf(
						'<button class="page-navigation-toggle"'.
							'aria-expanded="%s" '.
							'aria-controls="%s" '.
							'aria-label="%s">'.
							'<span class="page-navigation-icon icon-closed '.( isset( $icons['closed'] ) ? $icons['closed'] : '' ).'" aria-hidden="true"></span>'.
							'<span class="page-navigation-icon icon-expanded '.( isset( $icons['expanded'] ) ? $icons['expanded'] : '' ).'" aria-hidden="true"></span>'.
						'</button>',
						esc_attr( $expanded ),
						esc_attr( $panel_id ),
						esc_attr( sprintf( __( 'Toggle %s submenu', 'greyd_hub' ), $page->post_title ) )
					);
				}

				$output .= '</div>'; // Close page-navigation-item

				// Recursively get children
				$children = page_navigation_build_navigation( $pages, $page->ID, $depth + 1, $max_depth, $current_id, $icons );
				if ( $children ) {
					// Expand if current or ancestor
					$panel_hidden = $expand ? 'false' : 'true';
					$output      .= sprintf(
						'<div id="%s" class="page-navigation-panel" aria-hidden="%s">%s</div>',
						esc_attr( $panel_id ?? '' ),
						esc_attr( $panel_hidden ),
						$children
					);
				}

				$output .= '</li>';
			}
		}

		$output .= '</ul>';
		return $output;
	}
}

// Start the navigation
$navigation = page_navigation_build_navigation( $pages, 0, 0, $max_depth, $current_id, $icons );

// Output the navigation with wrapper attributes
printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$navigation
);

// Enqueue custom styles
if ( isset( $attributes['linkStyles'] ) && ! empty( $attributes['linkStyles'] ) ) {
	render::enqueue_custom_style(
		".$greydClass .page-navigation-item > a",
		$attributes['linkStyles']
	);
}
if ( isset( $attributes['chevronStyles'] ) && ! empty( $attributes['chevronStyles'] ) ) {
	render::enqueue_custom_style(
		".$greydClass .page-navigation-item > .page-navigation-toggle",
		$attributes['chevronStyles']
	);
}