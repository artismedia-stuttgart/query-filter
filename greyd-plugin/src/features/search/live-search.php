<?php
/**
 * Extend WordPress rest api
 *
 * @since 0.8.8
 */
namespace Greyd\Search;

use \Greyd\Helper;
use \Greyd\Enqueue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Live_Search();
class Live_Search {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'rest_post_search_query', array( $this, 'add_rest_query_args' ), 1, 2 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 1 );
	}

	/**
	 * Add rest query args for WordPress default search endpoints.
	 */
	public function add_rest_query_args( $args, $request ) {

		$args['orderby'] = isset( $request['orderby'] ) ? esc_attr( $request['orderby'] ) : '';

		if ( isset( $request['order'] ) ) {
			$args['order'] = esc_attr( $request['order'] );
		}
		if ( isset( $request['per_page'] ) ) {
			$args['posts_per_page'] = esc_attr( $request['per_page'] );
		}

		if ( $args['orderby'] == 'views' ) {
			$args['orderby']    = 'meta_value_num';
			$args['order']      = 'DESC';
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'postviews_count',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'postviews_count',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $args['orderby'] == 'relevance' ) {
			$args['order']  = 'DESC';
			$args['fields'] = 'all';
		}

		return $args;
	}

	/**
	 * Register the endpoint.
	 */
	public function register_routes() {
		register_rest_route(
			GREYD_REST_NAMESPACE,
			'/livesearch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'do_live_search' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Build the query
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 *      @property int found_posts   Number of max found posts.
	 *      @property array results     All the post results as HTML strings.
	 */
	public function do_live_search( $request ) {

		$results    = array();
		$data       = $this->parse_request_data( $request );
		$query_args = $this->build_query_args( $data );

		/**
		 * switch wpml language
		 *
		 * @since 1.5.2
		 */
		if ( isset( $data['lang'] ) && ! empty( $data['lang'] ) ) {
			global $sitepress;
			if ( $sitepress && method_exists( $sitepress, 'switch_lang' ) ) {
				$sitepress->switch_lang( $data['lang'] );
			}
		}

		/**
		 * Greyd.Blocks Live search
		 */
		if ( Helper::is_greyd_blocks() ) {
			return $this->do_blocks_live_search( $data, $query_args );
		}

		/**
		 * WPBakery Live search
		 *
		 * @deprecated since 1.1.2
		 */
		if ( Helper::is_greyd_classic() && ( '\WPBMap' ) ) {
			return $this->do_vc_live_search( $data, $query_args );
		}

		// wp_reset_postdata();

		/**
		 * Get number of found posts.
		 *
		 * When the search term is empty, the variable 'found_posts' sometimes
		 * holds the value 0 even though there are posts found...
		 *
		 * @since 1.2.8
		 */
		$found_posts = $query->found_posts;
		$post_count  = $query->post_count;
		if ( $post_count > $found_posts ) {
			$found_posts = count(
				$query->query(
					array_merge(
						$query_args,
						array(
							'orderby'        => 'date',
							'paged'          => 1,
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					)
				)
			);
		}

		return array(
			'found_posts' => $found_posts,
			'results'     => $results,
			'styles'      => $styles,
		);
	}

	/**
	 * Parse data from the request
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array    Parsed request data.
	 */
	public function parse_request_data( $request ) {

		$settings = Settings::get_setting( array( 'site', 'advanced_search' ) );

		// parse data
		$data = wp_parse_args(
			$request->get_json_params(),
			array(
				'orderby'        => $settings['relevance'] === 'true' ? 'relevance' : 'date',
				'order'          => 'DESC',
				'posts_per_page' => intval( get_option( 'posts_per_page', 10 ) ),
				'offset'         => 0,
				'block'          => null,
				'meta_query'     => array(),
			)
		);

		// build meta query
		$data['meta_query'] = array();
		if ( $data['orderby'] == 'views' ) {
			$data['orderby']    = 'meta_value_num';
			$data['order']      = 'DESC';
			$data['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'postviews_count',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'postviews_count',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		return $data;
	}

	/**
	 * Build the WP_Query arguments.
	 *
	 * @param array $data   Parsed request data.
	 *
	 * @return array        Filtered WP_Query arguments.
	 */
	public function build_query_args( $data ) {

		// build query args
		$query_args = array(
			's'                      => esc_attr( $data['s'] ),
			'orderby'                => esc_attr( $data['orderby'] ),
			'order'                  => esc_attr( $data['order'] ),
			'paged'                  => intval( $data['paged'] ),
			'posts_per_page'         => intval( $data['posts_per_page'] ),
			'meta_query'             => $data['meta_query'],
			'post_status'            => 'publish',
			'post_type'              => 'any',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true, // just query what we need
			'update_post_term_cache' => true, // disable taxonomy caching
			'update_post_meta_cache' => true, // disable meta caching
			'is_livesearch'          => true, // identify the query
			'is_search'              => true,
			'logged_in'              => $data['logged_in'], // used for relevance debugging
			'lang'                   => $data['lang'], // used for polylang
		);

		// add filter to query args
		$filter = isset( $data['filter'] ) ? (array) $data['filter'] : array();
		foreach ( $filter as $key => $value ) {
			if ( $key !== 's' && ! empty( $value ) && $value !== 'undefined' ) {
				
				/**
				 * By default, wordpress search supports the URL query parameter 'category_name' for category search.
				 * In order to properly support the native search, we need to convert the 'category' parameter to 'category_name'.
				 */
				if ( $key === 'category' ) {
					$key = 'category_name';
				}

				if ( is_array( $value ) ) {
					$query_args[ $key ] = array_map( 'esc_attr', $value );
				} else {
					$query_args[ $key ] = esc_attr( $value );
				}
			}
		}

		return $query_args;
	}

	/**
	 * Trigger the Blocks live search.
	 *
	 * @param array $data       Parsed request data.
	 * @param array $query_args Filtered WP_Query arguments.
	 *
	 * @return bool|array
	 *      @property int found_posts   Number of max found posts.
	 *      @property array results     All the post results as HTML strings.
	 */
	public function do_blocks_live_search( $data, $query_args ) {

		$query = new \WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return '';
		}

		// enable highlighting of the search term in titles & excerpts
		if ( ! empty( $query_args['s'] ) && strlen( $query_args['s'] ) > 3 ) {
			global $query_search_term;
			$query_search_term = $query_args['s'];
		}

		$article_classname = 'query-post' . esc_attr( implode( ' ', get_post_class('wp-block-post') ) ) . (Helper::is_greyd_classic() ? ' search_result' : '');

		while ( $query->have_posts() ) {

			$query->the_post();

			$post_id     = get_the_ID();
			$html_atts   = array(
				'class'      => array( $article_classname, esc_attr( implode( ' ', get_post_class( 'wp-block-post' ) ) ) ),
				'data-post'  => $post_id,
				'data-title' => sanitize_title( get_the_title() ),
				'data-date'  => get_the_date( 'Ymd' ),
			);
			$html_string = implode(
				' ',
				array_map(
					function ( $val, $att ) {
						return sprintf( '%s="%s"', $att, ( is_array( $val ) ? implode( ' ', $val ) : strval( $val ) ) );
					},
					$html_atts,
					array_keys( $html_atts )
				)
			);

			$block_content = (
				new \WP_Block(
					$data['block'],
					array(
						'postType' => get_post_type(),
						'postId'   => get_the_ID(),
					)
				)
			)->render( array( 'dynamic' => false ) );

			$block_content = \Greyd\Enqueue::replace_core_css_classes_in_block_content( $block_content );

			$results[] = '<article ' . $html_string . '>' . $block_content . '</article>';
		}

		// get styles
		$stylesheets = \Greyd\Enqueue::get_all_custom_stylesheets();

		// reset highlighting
		if ( isset( $query_search_term ) ) {
			$query_search_term = null;
		}

		/**
		 * Get number of found posts.
		 *
		 * When the search term is empty, the variable 'found_posts' sometimes
		 * holds the value 0 even though there are posts found...
		 *
		 * @since 1.2.8
		 */
		$found_posts = $query->found_posts;
		$post_count  = $query->post_count;
		if ( $post_count > $found_posts ) {
			$found_posts = count(
				$query->query(
					array_merge(
						$query_args,
						array(
							'orderby'        => 'date',
							'paged'          => 1,
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					)
				)
			);
		}

		$this->found_posts = $found_posts;

		// wp_reset_postdata();

		return array(
			'found_posts' => $found_posts,
			'results'     => $results,
			'styles'      => $stylesheets,
		);
	}

	/**
	 * Trigger the WPBakery live search.
	 *
	 * @deprecated since 1.1.2
	 *
	 * @param array $data       Parsed request data.
	 * @param array $query_args Filtered WP_Query arguments.
	 *
	 * @return bool|array
	 *      @property int found_posts   Number of max found posts.
	 *      @property array results     All the post results as HTML strings.
	 *      @property string styles     Custom CSS styles as HTML <style>.
	 */
	public function do_vc_live_search( $data, $query_args ) {

		// trick vc shortcode lazy loading
		if ( class_exists( '\WPBMap' ) ) {
			\WPBMap::addAllMappedShortcodes();
		}

		$results = array();

		// start the query
		$query        = new \WP_Query( $query_args );
		$post_results = $query->posts;

		// get root styles
		if ( $data['layout']['posts_layout'] !== 'table' ) {
			\processor::process_styles( get_template_directory() . '/assets/css/root.css' );
		}

		ob_start();

		if ( $data['table'] ) {
			for ( $i = 0; $i < count( $post_results ); $i++ ) {
				$results[ $i ] = \vc\shortcode_posts::make_post_table_row( $post_results[ $i ], $data['table'], $data['params'] );
			}
		} else {
			if ( $data['offset'] == 0 ) {
				$data['params']['first_post'] = true;
			}

			for ( $i = 0; $i < count( $post_results ); $i++ ) {
				$data['params']['post_num']   = ( ( $data['params']['post_num'] - 1 ) * $data['params']['posts_per_page'] ) + $i + 1;
				$results[ $i ]                = \vc\shortcode_posts::make_post_preview( $post_results[ $i ], $data['setup'], $data['layout'], $data['params'], true );
				$data['params']['first_post'] = false;
			}
		}
		Enqueue::render_custom_styles();

		$styles = ob_get_contents();
		ob_end_clean();

		/**
		 * Get number of found posts.
		 *
		 * When the search term is empty, the variable 'found_posts' sometimes
		 * holds the value 0 even though there are posts found...
		 *
		 * @since 1.2.8
		 */
		$found_posts = $query->found_posts;
		$post_count  = $query->post_count;
		if ( $post_count > $found_posts ) {
			$found_posts = count(
				$query->query(
					array_merge(
						$query_args,
						array(
							'orderby'        => 'date',
							'paged'          => 1,
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					)
				)
			);
		}

		return array(
			'results'     => $results,
			'found_posts' => $found_posts,
			'styles'      => $styles,
		);
	}
}
