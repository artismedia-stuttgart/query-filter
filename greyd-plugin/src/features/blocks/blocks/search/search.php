<?php
/**
 * Render the search bar's child blocks.
 * 
 * @deprecated since search refactoring
 * @todo write deprecated notice
 */
namespace greyd\blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Search();

class Search {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_filter( 'render_block', array( $this, 'render_search_blocks' ), 10, 2 );

		// handle autosearch via ajax
		add_action( 'pre_get_posts', array( $this, 'handle_autosearch_rest_request' ) );

		/** @deprecated */
		// add_filter( 'greyd_query_filter_tax_query_relationship', array($this, 'add_tax_relationship_or') , 10, 2 );
	}

	/**
	 * hook block rendering
	 * https://developer.wordpress.org/reference/hooks/render_block/
	 *
	 * @param string $block_content     The block content about to be appended.
	 * @param array  $block             The full block, including name and attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_search_blocks( $block_content, $block ) {

		if ( $block['blockName'] === 'greyd/search-sorting' ) {
			$block_content = $this->render_sorting_dropdown( $block['attrs'] );
		} elseif ( $block['blockName'] === 'greyd/search-filter' ) {
			$block_content = $this->render_filter_dropdown( $block['attrs'] );
		} elseif ( $block['blockName'] === 'greyd/search-input' ) {
			$block_content = $this->render_input( $block['attrs'] );
		}

		$search_url = apply_filters( 'greyd_search_url', \Greyd\Helper::get_home_url() );

		// dynamically add home url
		if ( $block['blockName'] === 'greyd/search' ) {
			$block_content = str_replace( 'method="get"', 'method="get" action="'.$search_url.'"', $block_content );


			// we need to set the posttype on archive and search templates otherwise it stays empty and redirects the user to the wrong search or archive template
			if (is_archive() || is_search()) {
				global $wp;
				$query_vars = $wp->query_vars;	
				$posttype = get_post_type();
	
				$block_content = str_replace( 'name="post_type" value=', 'name="post_type" value="'.$posttype.'"', $block_content );
			}
		
		}

		return $block_content;
	}

	/**
	 * Render search input
	 *
	 * @param string $atts              Saved block attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_input( $atts ) {

		$html = '';

		$id           = wp_unique_id( 'search-' );
		$label        = isset( $atts['label'] ) ? $atts['label'] : '';
		$placeholder  = isset( $atts['placeholder'] ) ? esc_attr( $atts['placeholder'] ) : __( "Search...", 'greyd_hub' );
		$greydClass   = isset( $atts['greydClass'] ) ? $atts['greydClass'] : '';
		$greydStyles  = isset( $atts['greydStyles'] ) ? (array) $atts['greydStyles'] : array();
		$customStyles = isset( $atts['custom'] ) && isset( $atts['customStyles'] ) ? (array) $atts['customStyles'] : array();

		global $wp;
		$query_vars = $wp->query_vars;
		$value      = isset( $query_vars['s'] ) ? $query_vars['s'] : '';

		$html .= '<div class="input-outer-wrapper ' . $greydClass . '">';
		// $html .= '<div class="input-wrapper">';

		if ( ! empty( $label ) ) {
			$html       .= sprintf( '<div class="label_wrap"><label for="%s">%s</label></div>', $id, $label );
			$labelStyles = isset( $atts['labelStyles'] ) && isset( $atts['labelStyles'] ) ? (array) $atts['labelStyles'] : array();
			if ( ! empty( $labelStyles ) ) {
				render::enqueue_custom_style(
					".{$greydClass} label",
					$labelStyles
				);
			}
		}

		// autosearch
		if ( isset( $atts['autosearch'] ) && isset( $atts['autosearch']['enable'] ) && $atts['autosearch']['enable'] ) {
			$html .= $this->render_autosearch_dropdown( $atts['autosearch'] );
		}

		// $html .= '<div class="input-inner-wrapper">';

		$html .= sprintf(
			'<input type="search" id="%s" name="s" value="%s" class="%s" placeholder="%s">',
			$id,
			$value,
			'input ' . ( isset( $atts['className'] ) ? $atts['className'] : '' ),
			$placeholder
		);
		// $html .= '</div></div>';
		$html .= '</div>';

		render::enqueue_custom_style(
			".{$greydClass} .input",
			$customStyles
		);
		render::enqueue_custom_style(
			".{$greydClass} .input::placeholder",
			array(
				'color'   => 'inherit',
				'opacity' => 0.5,
			)
		);

		return $html;
	}

	/**
	 * Render filter dropdown
	 *
	 * @param string $atts              Saved block attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_filter_dropdown( $atts ) {

		// atts
		$inherit     = isset( $atts['inherit'] ) && $atts['inherit'];
		$posttype    = isset( $atts['parentPosttype'] ) ? esc_attr( $atts['parentPosttype'] ) : '';
		$filterBy    = isset( $atts['filterBy'] ) ? esc_attr( $atts['filterBy'] ) : 'post_type';
		$multiselect = isset( $atts['multiselect'] ) && $atts['multiselect'];
		$label       = isset( $atts['label'] ) ? $atts['label'] : '';
		$placeholder = isset( $atts['placeholder'] ) ? esc_attr( $atts['placeholder'] ) : '';

		// styles
		$greydClass   = isset( $atts['greydClass'] ) ? $atts['greydClass'] : wp_unique_id( 'filter_' );
		$greydStyles  = isset( $atts['greydStyles'] ) ? (array) $atts['greydStyles'] : array();
		$customStyles = isset( $atts['custom'] ) && isset( $atts['customStyles'] ) ? (array) $atts['customStyles'] : array();

		global $wp;
		$query_vars = $wp->query_vars;

		// inherit from query
		if ( $inherit && ( is_search() || is_archive() ) ) {
			if ( isset( $_GET['post_type'] ) ) {
				$posttype = $_GET['post_type'];
			} else {
				$posttype = get_post_type();
			}
		}
		
		/**
		 * By default, wordpress search supports the URL query parameter 'category_name' for category search.
		 * In order to properly support the filter dropdown, we need to change the filterBy value to 'category_name'.
		 */
		if ( $filterBy === 'categories' || $filterBy === 'category') {
			$filterBy = 'category_name';
		}

		$error_message = '';

		// get filter options
		$options  = array();
		$selected = array();

		/**
		 * Get options
		 */
		if ( empty( $posttype ) ) {

			if ( empty( $placeholder ) ) {
				$placeholder = __( "All post types", 'greyd_hub' );
			}
			if ( isset( $query_vars['post_type'] ) ) {
				$selected = explode( ',', $query_vars['post_type'] );
			}

			$searchable_posttypes = get_post_types( array(
				'exclude_from_search' => false
			), 'objects' );
			foreach( $searchable_posttypes as $pt ) {
				$options[ $pt->name ] = $pt->label;
			}
		}
		else {
			$terms = array();

			// post_tag
			if ( $filterBy === 'tag' ) {
				$terms = get_terms( array(
					'taxonomy' => 'post_tag',
					'hide_empty' => true
				) );
			}
			// category
			else if ( $filterBy === 'category' || $filterBy === 'categories' || $filterBy === 'category_name' ) {
				$terms = get_terms( array(
					'taxonomy' => 'category',
					'hide_empty' => true
				) );
			}
			// custom taxonomy
			else {
				$terms = get_terms( array(
					'taxonomy' => $filterBy,
					'hide_empty' => true
				) );
			}
			
			/**
			 * Filter Terms.
			 * e.g. to change sorting (default alphabetical) or hierarchy.
			 * @since 2.2.0
			 * 
			 * @param array $terms      The terms.
			 * @param string $filterBy  The taxonomy.
			 * @param array $atts       block attributes.
			 * 
			 * @return array
			 */
			$terms = apply_filters( 'greyd_search_filter_terms', $terms, $filterBy, $atts );

			if ( is_wp_error( $terms ) ) {
				$error_message = sprintf(
					__( 'Error loading the filter options for %s', 'greyd_hub' ),
					$filterBy
				);
				$error_message .= ': ' . $terms->get_error_message();
				$options = array(
					'' => __( "No options available", 'greyd_hub' )
				);
			}
			
			// map options
			if ( empty( $terms ) ) {
				$options = array(
					'' => __( "No options available", 'greyd_hub' )
				);
			} else {
				/**
				 * Map the Options recursively, to enable hierarchical view in multiselects
				 * @since 2.11.0
				 */
				$options = self::build_options( $terms, $multiselect );
			}

			if ( empty( $placeholder ) ) {
				$taxonomy = get_taxonomy( $filterBy );
				if ( $taxonomy ) {
					$placeholder = sprintf(
						__( "%s select", 'greyd_hub' ),
						( $multiselect ? $taxonomy->label : $taxonomy->labels->singular_name )
					);
				} else {
					$placeholder = __( "Please select", 'greyd_hub' );
				}
			}
 
			$selected = isset( $query_vars[ $filterBy ] ) ? explode( ',', $query_vars[ $filterBy ] ) : array();

			// look for query var in URL param
			if ( empty( $selected ) && isset( $_GET[ $filterBy ] ) ) {
				$selected = explode( ',', $_GET[ $filterBy ] );
			}
		}

		// render
		$html = '<div class="filter ' . $greydClass . '">';

		if ( ! empty( $label ) ) {
			$html       .= sprintf( '<div class="label_wrap"><label>%s</label></div>', $label );
			$labelStyles = isset( $atts['labelStyles'] ) && isset( $atts['labelStyles'] ) ? (array) $atts['labelStyles'] : array();
			if ( ! empty( $labelStyles ) ) {
				render::enqueue_custom_style(
					".{$greydClass} label",
					$labelStyles
				);
			}
		}

		// render select
		if ( $multiselect ) {
			if ( method_exists( '\Greyd\Helper', 'render_multiselect' ) ) {
				$html .= \Greyd\Helper::render_multiselect(
					$filterBy,
					$options,
					array(
						'value'       => implode( ',', $selected ),
						'class'       => isset($atts['className']) ? $atts['className'] : '',
						'placeholder' => $placeholder
					)
				);
			}
			else if ( method_exists( '\vc\helper', 'render_multiselect' ) ) {
				$html .= \vc\helper::render_multiselect(
					$filterBy,
					$options,
					array(
						'value'       => implode( ',', $selected ),
						'class'       => isset($atts['className']) ? $atts['className'] : '',
						'placeholder' => $placeholder
					)
				);
			}
		} else {
			$selected = count($selected) ? trim( $selected[0] ) : ''; // make single value

			// add placeholder as first empty option
			$options = isset($options['']) ? $options : array( '' => $placeholder ) + $options;

			$html .= sprintf(
				'<div class="custom-select %s"><select name="%s">%s</select></div>',
				isset($atts['className']) ? $atts['className'] : '',
				$filterBy,
				implode( '', array_map( function( $value, $label ) use ( $selected ) {
					return sprintf(
						'<option value="%s" %s>%s</option>',
						$value,
						$value == $selected ? 'selected="selected"' : '',
						$label
					);
				}, array_keys( $options ), $options ) )
			);
		}
		$html .= '</div>';

		if ( ! empty( $error_message ) && method_exists( '\Greyd\Helper', 'show_frontend_message' ) ) {
			$html .= \Greyd\Helper::show_frontend_message( $error_message, 'error' );
		}

		// enqueue styles
		render::enqueue_custom_style(
			".{$greydClass}.filter .input, .{$greydClass}.filter .dropdown, .{$greydClass}.filter .select-selected, .{$greydClass}.filter .select-items",
			array_merge( $greydStyles, $customStyles )
		);

		return $html;
	}

	/**
	 * Map Options recursively.
	 * If a term has children, the Options get nested.
	 * This can be achieved with the 'greyd_search_filter_terms' filter.
	 * @since 2.10.0
	 * 
	 * @param array $terms      The terms to map to options.
	 * @param bool $multiselect Weather to map for a multiselect (simple selects, the children get flattened)
	 * 
	 * @return array $options
	 */
	public static function build_options( $terms, $multiselect ) {

		$options = array();

		foreach ( $terms as $term ) {

			if (
				!is_a( $term, 'WP_Term' )
				|| !isset( $term->name )
				|| !isset( $term->slug )
				|| !isset( $term->count )
				|| $term->count < 1
			) {
				continue;
			}

			$options[$term->slug] = $term->name;

			// handle children
			if ( isset( $term->children ) && !empty( $term->children ) ) {
				if ( $multiselect ) {
					$option = array(
						'name' => $term->name,
						'children' => self::build_options( $term->children, $multiselect )
					);
					$options[$term->slug] = $option;
				}
				else {
					// flatten
					$options = $options + self::build_options( $term->children, $multiselect );
				}
			}
		}

		return $options;
	}

	/**
	 * Render sorting dropdown
	 *
	 * @param string $atts              Saved block attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_sorting_dropdown( $atts ) {

		$html = '';

		$label       = isset( $atts['label'] ) ? $atts['label'] : '';
		$placeholder = isset( $atts['placeholder'] ) ? esc_attr( $atts['placeholder'] ) : '';
		$search_settings = class_exists('\Greyd\Settings') ? \Greyd\Settings::get_setting( array( 'site', 'advanced_search' ) ) : array( 'live_search' => 'false' );

		// styles
		$greydClass   = isset( $atts['greydClass'] ) ? $atts['greydClass'] : wp_unique_id( 'sorting_' );
		$greydStyles  = isset( $atts['greydStyles'] ) ? (array) $atts['greydStyles'] : array();
		$customStyles = isset( $atts['custom'] ) && isset( $atts['customStyles'] ) ? (array) $atts['customStyles'] : array();

		// build options
		$selected_option = '';
		$options         = array(
			'date_DESC'  => __( "Chronological (newest first)", 'greyd_hub' ),
			'date_ASC'   => __( "Chronological (oldest first)", 'greyd_hub' ),
			'title_ASC'  => __( "Alphabetical (ascending)", 'greyd_hub' ),
			'title_DESC' => __( "Alphabetical (descending)", 'greyd_hub' ),
		);

		if ( !empty( $placeholder ) ) {
			$options = array_merge(
				array( '' => $placeholder ),
				$options
			);
		}

		if ( $search_settings && isset($search_settings['postviews_counter']) && $search_settings['postviews_counter'] === 'true' ) {
			$options['views_DESC'] = __( "Most read", 'greyd_hub' );
		}
		if ( $search_settings && isset($search_settings['relevance']) && $search_settings['relevance'] === 'true') {
			$options = array_merge(
				array( 'relevance_DESC' => __( "Relevance", 'greyd_hub' ) ),
				$options
			);
		}

		global $wp;
		$query_vars = $wp->query_vars;

		$order   = isset( $query_vars['order'] ) ? strtoupper( $query_vars['order'] ) : 'DESC';
		$orderby = isset( $query_vars['orderby'] ) ? strtolower( $query_vars['orderby'] ) : '';

		// selected option
		$selected_option = $orderby . '_' . $order;

		// wrapper
		$html .= '<div class="sorting ' . $greydClass . '" >';

		if ( ! empty( $label ) ) {
			$html       .= sprintf( '<div class="label_wrap"><label>%s</label></div>', $label );
			$labelStyles = isset( $atts['labelStyles'] ) && isset( $atts['labelStyles'] ) ? (array) $atts['labelStyles'] : array();
			if ( ! empty( $labelStyles ) ) {
				render::enqueue_custom_style(
					".{$greydClass} label",
					$labelStyles
				);
			}
		}

		$html .= '<div class="custom-select ' . ( isset( $atts['className'] ) ? $atts['className'] : '' ) . '">';
		$html .= "<input type='hidden' name='orderby' value='' autocomplete='off' >";
		$html .= "<input type='hidden' name='order' value='' autocomplete='off' >";

		$html .= '<select>';

		// options
		foreach ( $options as $key => $option ) {

			if ( is_array( $option ) ) {
				$label = $option['label'];
				$value = $option['value'];
			} else {
				$label = $option;
				$value = str_replace( '_', ' ', $key );
			}

			// use user input value
			if ( isset( $atts['options'][ $key ] ) && ! empty( $atts['options'][ $key ] ) ) {
				$label = esc_attr( $atts['options'][ $key ] );
			}

			$html .= sprintf(
				'<option value="%s" %s>%s</option>',
				$value,
				$selected_option == $key ? 'selected="selected"' : '',
				$label
			);
		}

		$html .= '</select></div>';

		$html .= '</div>';

		render::enqueue_custom_style(
			".{$greydClass}.sorting > .custom-select",
			array( 'width' => 'auto' )
		);
		render::enqueue_custom_style(
			".{$greydClass}.sorting > .custom-select > div",
			array_merge( $greydStyles, $customStyles )
		);

		return $html;
	}

	/**
	 * Render autosearch drodown
	 *
	 * @param string $atts              Saved block attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_autosearch_dropdown( $atts ) {

		// add script
		$this->enqueue_autosearch_script();

		$max_results      = isset( $atts['maxResults'] ) ? intval( $atts['maxResults'] ) : 5;
		$show_on_click    = isset( $atts['showOnClick'] ) ? esc_attr( $atts['showOnClick'] ) : 'true';
		$sorting          = isset( $atts['sorting'] ) ? explode( '_', esc_attr( $atts['sorting'] ) ) : array( 'relevance', 'DESC' );
		$loading_text     = isset( $atts['loading'] ) ? esc_attr( $atts['loading'] ) : __( 'Loading...', 'greyd_hub' );
		$noresult_text    = isset( $atts['noResult'] ) ? esc_attr( $atts['noResult'] ) : __( 'No results found', 'greyd_hub' );
		$forward_on_click = isset( $atts['forwardOnClick'] ) ? esc_attr( $atts['forwardOnClick'] ) : 'false';

		// $same_style_as_searchfield = !isset($atts['autosearch_style']);

		$orderby = $sorting[0];
		$order   = strtolower( $sorting[1] );

		// if the filter is "all" - we don't need it
		$filter = isset( $atts['autosearch_filter'] ) ? esc_attr( $atts['autosearch_filter'] ) : '';
		$filter = $filter === 'all' ? '' : $filter;

		// // Styling
		// if ($same_style_as_searchfield) {
		// $class = isset($atts['input_style']) ? str_replace( array("_autosearch", "_input", "_"), "", esc_attr($atts['input_style']) ) : "";
		// } else {
		// $class = isset($atts['autosearch_style']) ? str_replace( array("_autosearch", "_input", "_"), "", esc_attr($atts['autosearch_style']) ) : "";
		// }

		return sprintf(
			'<div class="custom-select autosearch" %s ><select><option value=""></option></select></div>',
			helper::implode_html_attributes(
				array(
					'data' => array(
						'filter'           => $filter,
						'max-results'      => $max_results,
						'show-on-click'    => $show_on_click,
						'orderby'          => $orderby,
						'order'            => $order,
						'loading-text'     => $loading_text,
						'noresult-text'    => $noresult_text,
						'forward-on-click' => $forward_on_click,
					),
				)
			)
		);
	}

	/**
	 * Enqueue the autosearch script
	 */
	public function enqueue_autosearch_script() {

		$handle  = 'greyd-search-autosearch-script';
		$version = '1.0';

		if ( wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_register_script(
			$handle,
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'autosearch.js',
			array( 'jquery' ),
			$version,
			true
		);
		wp_localize_script(
			$handle,
			'wp_settings',
			array(
				'posts_per_page'  => esc_js( get_option( 'posts_per_page', 10 ) ),
				'is_greyd_blocks' => \Greyd\Helper::is_greyd_blocks(),
				'is_vc'           => \Greyd\Helper::is_greyd_classic() && class_exists( '\WPBMap' ),
				'lang'            => \Greyd\Helper::get_language_code(),
			)
		);
		wp_enqueue_script(
			$handle
		);
	}

	/**
	 * Handle an autosearch request.
	 *
	 * The autosearch feature uses the default REST API search endpoint,
	 * which includes every post type with the argument 'show_in_rest'
	 * set to true.
	 *
	 * For the block editor this parameter is required so that the post type
	 * works with the block editor itself. Therefore custom post types which
	 * use the editor are included in this search.
	 *
	 * This function now excludes all post types with the argument
	 * 'exclude_from_search' set to true.
	 *
	 * @param WP_Query $query
	 */
	public function handle_autosearch_rest_request( $query ) {

		if ( $query->is_admin() || ! $this->is_wp_rest_request() ) {
			return $query;
		}

		// example rest url: ?rest_route=/wp/v2/search/&autosearch=true
		$is_autosearch_query = isset( $_GET['autosearch'] ) && $_GET['autosearch'] === 'true';
		if ( ! $is_autosearch_query ) {
			return $query;
		}

		// get the post types
		$query_post_types = $query->get( 'post_type' );
		if ( is_string( $query_post_types ) ) {
			$query_post_types = explode( ',', $query_post_types );
		}

		// exclude post types
		$post_types_to_exclude = get_post_types( array( 'exclude_from_search' => true ) );
		if ( sizeof( array_intersect( $query_post_types, $post_types_to_exclude ) ) ) {
			$query->set( 'post_type', array_diff( $query_post_types, $post_types_to_exclude ) );
		}

		return $query;
	}

	/**
	 * Whether we are in a REST REQUEST. Similar to is_admin().
	 *
	 * @see \tp\management\search\query::is_wp_rest_request()
	 *
	 * @return bool
	 */
	public function is_wp_rest_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}
