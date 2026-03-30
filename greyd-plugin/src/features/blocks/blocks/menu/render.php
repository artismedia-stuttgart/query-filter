<?php
/**
 * Render Menu block.
 * 
 * @since 1.6.0
 */
if ( !function_exists('greyd_render_menu_block') ) {
	/**
	 * Render menu callback.
	 * 
	 * @param array $atts      Menu block attributes.
	 * @param string $content  Menu block content saved in edit.js
	 *                         Contains just the stylesheet if any.
	 */
	function greyd_render_menu_block($atts, $content) {

		$menu = '';
		$menu_slug = isset($atts['menu']) ? $atts['menu'] : '';
		$id = isset($atts['anchor']) && $atts['anchor'] != "" ? 'id="'.$atts['anchor'].'"' : '';

		if ( empty($menu_slug) ) return '';

		// check if menu exists
		$menu_obj = wp_get_nav_menus(array('slug' => $menu_slug));
		if ( $menu_obj && isset($menu_obj[0]->term_id) ) {

			$menu = '<div '.$id.' class="block_menu_wrapper">';
			$menu .= wp_nav_menu( array(
				'menu'          => $menu_obj[0]->term_id,
				'depth'         => isset($atts['depth']) ? intval($atts['depth']) : 0,
				'menu_class'    => 'menu '.(isset($atts['greydClass']) ? $atts['greydClass'] : ''),
				'fallback_cb'   => false,
				'echo'          => false,
				/**
				 * Add possibility to filter the menu walker.
				 * 
				 * @filter greyd_menu_render_walker
				 * @since 1.5.5
				 * 
				 * @param object $walker    Instance of a custom walker class.
				 * @param array  $atts      Menu block attributes.
				 */
				'walker' => apply_filters( 'greyd_menu_render_walker', null, $atts )
			) );
			$menu .= '</div>';
		}
		return $content.$menu;
	}
}