<?php
/**
 * WPBakery post-export extension
 */
namespace Greyd;

if( ! defined( 'ABSPATH' ) ) exit;

new VC_Post_Export($config);
class VC_Post_Export {
	
	public function __construct() {
		
		add_filter( 'greyd_regex_nested_posts', array( $this, 'regex_nested_posts' ), 10, 3 );
		add_filter( 'greyd_regex_nested_terms', array( $this, 'regex_nested_terms' ), 10, 3 );
		
		// resolve menus is not supported
		add_filter( 'greyd_post_export_resolve_menus', array( $this, 'resolve_menus' ), 10, 3 );
	}

	/**
	 * Search & replace post-IDs or post_names inside post_content.
	 * 
	 * @filter 'greyd_regex_nested_posts'
	 * 
	 * @param array $patterns   @see get_nested_post_patterns() for details
	 * @param int $post_id      The post ID.
	 * @param WP_Post $post     The WP_Post Object
	 * 
	 * @return array $patterns  Filtered RegEx patterns
	 */
	public function regex_nested_posts( $patterns, $post_id, $post ) {

		/**
		 * Only run this filter for classic editor.
		 */
		if ( ! Helper::is_greyd_classic() ) return $patterns;

		if ( has_blocks( $post->post_content ) ) return $patterns;

		do_action( "greyd_post_export_log", "\r\n---@filter: 'regex_nested_posts' for WPBakery" );

		// debug( esc_attr($post->post_content) );


		$patterns = array_merge(
			$patterns,
			array(
				/**
				 * Find nested templates
				 *  (1) template module:            ' template="{{$name_or_id}}"'
				 *  (2) post overview:              ' post_template="{{$name_or_id}}"'
				 *  (3) dynamic content:            ' dynamic_content="{{id}}::someContent...'
				 *  (4) dynamic post content:       ' dynamic_post_content="{{id}}::someContent...'
				 *  (5) dynamic dynamic content:    ' dynamic_dynamic_content="dynamic_content|dynamic_content|{{id}}"'
				 */
				// (1) + (2)
				'template'                => array(
					'search'    => array( ' (post_)?template=\"', '\"' ),
					'replace'   => array( ' $1template="', '"' ),
					'post_type' => 'dynamic_template',
				),
				// (3) + (4)
				'dynamic_content'         => array(
					'search'    => array( ' dynamic_(post_)?content=\"', '(::|\")' ),
					'replace'   => array( ' dynamic_$1content="', '$3' ),
					'post_type' => 'dynamic_template',
				),
				// (5)
				'dynamic_dynamic_content' => array(
					'search'    => array( ' dynamic_dynamic_content=\"dynamic_content\|dynamic_content\|', '\"' ),
					'replace'   => array( ' dynamic_dynamic_content="dynamic_content|dynamic_content|', '"' ),
					'post_type' => 'dynamic_template',
					'group'     => 1,
				),
				/**
				 * Find nested forms
				 *  (1) form module:    '[vc_form ... id="{{$id}}"]'
				 *  (2) dynamic:        '::id|dropdown_forms|someName|{{$id}}::'
				 */
				// (1)
				'vc_form'                 => array(
					'search'    => array( '\[vc_form([^\[\]]*?) id=\"', '\"' ),
					'replace'   => array( '[vc_form$1 id="', '"' ),
					'post_type' => 'tp_forms',
				),
				// (2)
				'dynamic_form'            => array(
					'search'    => array( '::id\|dropdown_forms\|([^\|]*?)\|', '(::|\")' ),
					'replace'   => array( '::id|dropdown_forms|$1|', '$3' ),
					'post_type' => 'tp_forms',
				),
				/**
				 * Find all nested media files
				 *  (1) normal:         '[vc_icons ... icon="$id"]'
				 *  (2) list icon:      ' vc_list_image="$id"'
				 *  (3) row bg:         ' vc_bg_image="$id"'
				 *  (4) content box bg: ' vc_content_box_image="$id"'
				 *  (5) dynamic:
				 *      - normal:           ::icon|file_picker(_image)|Bild|513::
				 *      - row bg:           ::vc_bg_image|file_picker(_image)|bg_img|516::
				 *      - content box bg:   ::vc_content_box_image|file_picker(_image)|content_box_img|512::
				 *  forms: [vc_form_icon_panel...
				 *      (6) ... icon="508"
				 *      (7) ... icon_hover="509"
				 *      (8) ... icon_select="510"
				 */
				// (1)
				'vc_icons'                => array(
					'search'    => array( '\[vc_icons([^\[\]]*?) icon=\"', '\"' ),
					'replace'   => array( '[vc_icons$1 icon="', '"' ),
					'post_type' => 'attachment',
				),
				// (2) + (3) + (4)
				'vc_image'                => array(
					'search'    => array( ' vc_(list|bg|content_box)_image=\"', '\"' ),
					'replace'   => array( ' vc_$1_image="', '"' ),
					'post_type' => 'attachment',
				),
				// (5)
				'dynamic_image'           => array(
					'search'    => array( '\|file_picker(_image)?\|([^\|]*?)\|', '(::|\")' ),
					'replace'   => array( '|file_picker$1|$2|', '$4' ),
					'post_type' => 'attachment',
					'group'     => 3,
				),
				// (6)
				'icon_panel'              => array(
					'search'    => array( '\[vc_form_icon_panel([^\[\]]*?) icon=\"', '\"' ),
					'replace'   => array( '[vc_form_icon_panel$1 icon="', '"' ),
					'post_type' => 'attachment',
				),
				// (7)
				'icon_panel_hover'        => array(
					'search'    => array( '\[vc_form_icon_panel([^\[\]]*?) icon_hover=\"', '\"' ),
					'replace'   => array( '[vc_form_icon_panel$1 icon_hover="', '"' ),
					'post_type' => 'attachment',
				),
				// (8)
				'icon_panel_select'       => array(
					'search'    => array( '\[vc_form_icon_panel([^\[\]]*?) icon_select=\"', '\"' ),
					'replace'   => array( '[vc_form_icon_panel$1 icon_select="', '"' ),
					'post_type' => 'attachment',
				),
				/**
				 * Find nested popups
				 *  (1) popup button:       [vc_cbutton ...style="popup_open" ... popup="193"...]
				 *  (2) popup contentbox:   [vc_content_box ...vc_content_box_event_type="popup_open" ... vc_content_box_popup="193"...]
				 */
				// (1)
				'popup_button'            => array(
					'search'    => array( '\[vc_cbutton([^\[\]]*?) style=\"popup_open"([^\[\]]*?) popup="', '\"' ),
					'replace'   => array( '[vc_cbutton$1 style="popup_open"$2 popup="', '"' ),
					'post_type' => 'greyd_popup',
					'group'     => 3,
				),
				// (2)
				'popup_contentbox'        => array(
					'search'    => array( '\[vc_content_box([^\[\]]*?) vc_content_box_event_type=\"popup_open"([^\[\]]*?) vc_content_box_popup="', '\"' ),
					'replace'   => array( '[vc_content_box$1 vc_content_box_event_type="popup_open"$2 vc_content_box_popup="', '"' ),
					'post_type' => 'greyd_popup',
					'group'     => 3,
				),
			)
		);

		return $patterns;
	}

	/**
	 * Get the patterns to replace term ids in post_content.
	 * 
	 * @param array   $patterns     @see get_nested_post_patterns() for details
	 * @param int     $post_id      The WP_Post ID.
	 * @param WP_Post $post         The WP_Post Object
	 * 
	 * @return array[] @see get_nested_post_patterns() for details.
	 */
	public function regex_nested_terms( $patterns, $post_id, $post ) {

		/**
		 * Only run this filter for classic editor.
		 */
		if ( ! Helper::is_greyd_classic() ) return $patterns;

		if ( has_blocks( $post->post_content ) ) return $patterns;

		$patterns = array_merge(
			$patterns,
			array(
				'post_overview_category_filter'  => array(
					'search'  => array( ' ([-_a-z]+)?categoryid=\"', '\"' ),
					'replace' => array( ' $1categoryid="', '"' ),
				),
				'post_overview_tag_filter'       => array(
					'search'  => array( ' ([-_a-z]+)?tagid=\"', '\"' ),
					'replace' => array( ' $1tagid="', '"' ),
				),
				'post_overview_customtax_filter' => array(
					'search'  => array( ' customtax_([-_a-z]+)=\"', '\"' ),
					'replace' => array( ' customtax_$1="', '"' ),
				),
				'post_overview_frontend_filter'  => array(
					'search'  => array( ' _filter=\"([-_a-z]+)" ([-_a-z]+)_initial="', '\"' ),
					'replace' => array( ' _filter="$1" $2_initial="', '"' ),
					'group'   => 3,
				),
			)
		);

		return $patterns;
	}
	
	/**
	 * Resolve menus.
	 *
	 * @param string $subject
	 * @param  int    $post_id Post ID.
	 *
	 * @return string $subject
	 */
	public static function resolve_menus( $subject, $post_id, $post ) {

		/**
		 * Only run this filter for classic editor.
		 */
		if ( ! Helper::is_greyd_classic() ) return $subject;

		if ( has_blocks( $post->post_content ) ) return $subject;

		do_action( 'greyd_post_export_log', "\r\n" . 'Resolve nested WPBakery menus.' );

		// get patterns
		$replace_menu_patterns = (array) self::regex_nested_menus( $post_id );

		foreach ( $replace_menu_patterns as $name => $pattern ) {

			// doing it wrong
			if (
				! isset( $pattern['search'] ) ||
				! is_array( $pattern['search'] ) ||
				count( $pattern['search'] ) < 2 ||
				! isset( $pattern['replace'] ) ||
				! is_array( $pattern['replace'] )
			) {
				continue;
			}

			$match_regex = '/' . implode( '([\da-z\-\_]+?)', $pattern['search'] ) . '/';
			$regex_group = isset( $pattern['group'] ) ? (int) $pattern['group'] : 2;

			// search for all occurrences
			preg_match_all( $match_regex, $subject, $matches );
			$found_menus = isset( $matches[2] ) ? $matches[2] : null;
			if ( ! empty( $found_menus ) ) {

				do_action( 'greyd_post_export_log', sprintf( "  - %s menu(s) found: '%s'", count( $found_menus ), implode( "', '", $found_menus ) ) );

				foreach ( $found_menus as $menu_name ) {

					if ( $menu_name === 'custom' ) {
						continue;
					}

					$vc_menu  = array();
					$response = wp_get_nav_menus( array( 'slug' => $menu_name ) );
					if ( count( $response ) ) {

						$menu_items = wp_get_nav_menu_items( $response[0] );
						foreach ( $menu_items as $item ) {

							$level   = $item->menu_item_parent == 0 ? '1' : '2';
							$vc_link = array(
								'url:' . self::vc_encode( $item->url ),
								'title:' . self::vc_encode( $item->title ),
								! empty( $item->target ) ? 'target:%20_blank' : '',
								'',
							);
							$vc_link = implode( '|', $vc_link );

							$vc_menu[] = (object) array(
								'menu_link_level' => $level,
								'menu_link'       => $vc_link,
							);
						}

						$search_regex   = '/' . implode( $menu_name, $pattern['search'] ) . '/';
						$replace_string = count( $vc_menu ) ? 'custom" custom_menu="' . urlencode( json_encode( $vc_menu ) ) : $menu_name;
						$replace_string = implode( $replace_string, $pattern['replace'] );

						// replace
						$subject = preg_replace( $search_regex, $replace_string, $subject );

						do_action( 'greyd_post_export_log', sprintf( "  - menu '%s' was resolved.", $menu_name ) );
					}
				}
			}
		}

		do_action( 'greyd_post_export_log', '=> nested menus were resolved' );

		return $subject;
	}

	/**
	 * Search & replace menus inside post_content.
	 * 
	 * @filter 'greyd_regex_nested_menus'
	 * 
	 * @param array $patterns   Have a look at the class function regex_nested_posts()
	 *                          to understand how the patterns work and how to add one.
	 * @param int   $post_id    The post ID.
	 */
	public function regex_nested_menus( $patterns, $post_id ) {

		/**
		 * Only run this filter for classic editor.
		 */
		if ( ! Helper::is_greyd_classic() ) return $patterns;

		do_action( "greyd_post_export_log", "\r\n---@filter: 'regex_nested_menus' for WPBakery" );

		$patterns = array_merge(
			$patterns,
			array(
				'vc_footernav'   => array(
					'search'  => array( '\[vc_footernav(.+?) menu=\"', '\"' ),
					'replace' => array( '[vc_footernav$1 menu="', '"' ),
					'group'   => 2,
				),
				'vc_dropdownnav' => array(
					'search'  => array( '\[vc_dropdownnav(.+?) menu=\"', '\"' ),
					'replace' => array( '[vc_dropdownnav$1 menu="', '' ),
					'group'   => 2,
				),
			)
		);

		return $patterns;
	}
}
