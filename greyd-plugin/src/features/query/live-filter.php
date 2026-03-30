<?php
/**
 * Live Filter features.
 * 
 * @since 1.3.0
 */
namespace Greyd\Query;

if ( !defined( 'ABSPATH' ) ) exit;

new Live_Filter($config);
class Live_Filter {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;

		// add live Filter feature
		add_action( 'rest_api_init', array( $this, 'register_route'), 1 );
	}
	
	/**
	 * Register the endpoint.
	 */
	public function register_route() {
		
		register_rest_route( GREYD_REST_NAMESPACE, '/livequery', array(
			'methods' => 'POST',
			'callback' => array($this, 'do_live_query'),
			'permission_callback' => '__return_true'
		));

		register_rest_route( GREYD_REST_NAMESPACE, '/livequeries', array(
			'methods' => 'POST',
			'callback' => array($this, 'do_live_queries'),
			'permission_callback' => '__return_true'
		));

	}

	/**
	 * Build new query
	 * 
	 * @param \WP_REST_Request $request
	 * 
	 * @return array
	 *      @property string block_content    rendered query as HTML string.
	 */
	public function do_live_query( $request ) {
		
		// get data from request
		$data = wp_parse_args($request->get_json_params());
		// var_error_log($data);

		return array(
			'block_content' => $this->get_new_content($data)
		);

	}
	
	/**
	 * Build multiple new queries
	 * 
	 * @param \WP_REST_Request $request
	 * 
	 * @return [array]
	 *      @property string block_content    rendered query as HTML string.
	 */
	public function do_live_queries( $request ) {

		// get items data from request
		$items = wp_parse_args($request->get_json_params());
		// var_error_log($items);

		$response = array();
		foreach ( $items as $data ) {
			// reset style_engine
			$rules = \WP_Style_Engine::get_store( 'block-supports' )->get_all_rules();
			foreach ( $rules as $selector => $rule ) {
				\WP_Style_Engine::get_store( 'block-supports' )->remove_rule($selector);
			}
			// get new content
			$response[$data["key"]] = array(
				'block_content' => $this->get_new_content($data["postdata"])
			);
		}

		return $response;

	}
	
	/**
	 * Get new query content from postdata
	 * 
	 * @param object $data		json encoded postdata
	 * @return string $content	rendered query
	 */
	public function get_new_content( $data ) {

		$block = json_decode( $data['block'], true );
		// var_error_log($block);

		if (
			isset($block['attrs']['query']['query']['taxQuery'])
			&& count($block['attrs']['query']['query']['taxQuery']) === 1
			&& isset($block['attrs']['query']['query']['taxQueryRelationship'])
			&& $block['attrs']['query']['query']['taxQueryRelationship'] == 'OR'
		) {
			/**
			 * Add filter to set tax_query relationship to 'OR'
			 * this way the multiselect works as expected, selecting posts that have
			 * either term set.
			 * 
			 * @since 1.3.2
			 */
			add_filter( 'greyd_query_filter_tax_query_relationship', function( $relationship, $query_args ) {
				return 'OR';
			}, 10, 2 );
		}

		/**
		 * During rest requests, there is no global post object set.
		 * Advanced filters need this info, so we emulate it before rendering the new query.
		 * @since 1.14.0
		 */
		if (
			isset($block['attrs']['query']['advancedFilter']) &&
			isset($data['postId']) &&
			intval($data['postId']) > 0
		) {
			global $post;
			$post = get_post( intval($data['postId']) );
		}

		/**
		 * Add the global $queried_object_id  for 'current_archive_terms' Advanced filter
		 */
		if (
			isset($block['attrs']['query']['advancedFilter']) &&
			isset($data['queried_object_id']) &&
			intval($data['queried_object_id']) > 0
		) {
			global $queried_object_id;
			$queried_object_id = intval($data['queried_object_id']);
		}

		/**
		 * During rest requests, the global wp_query object is empty (not set).
		 * Conditional Content need this info, e.g. in archive templates,
		 * so we emulate it before rendering by combining the wp_query with the block query
		 * @since 1.14.0
		 */
		if ( isset($data['wp_query']) ) {

			// overwrite $wp->query_vars
			// build_query() inherits the global $wp->query_vars if inherit
			// is set to true.
			global $wp;
			$wp_query_data  = json_decode( $data['wp_query'], true );
			$wp->query_vars = $wp_query_data;
			
			$query_args = \Greyd\Query\Render::build_query( $block['attrs']['query'], 1 );

			// set new global wp_query
			global $wp_query;
			$wp_query = new \WP_Query( $query_args );
		}

		/**
		 * Get query tags after globals are set up.
		 * fixes disapearing pagination and other undesired effects after live-query reload.
		 * @since 2.1.0
		 */
		// $tags = \Greyd\Dynamic\Render_Blocks::get_query_tags($block['attrs']['query']);
		$tags = \Greyd\Query\Render::get_query_tags($block['attrs']['query']);
		$block['attrs']['queryTags'] = $tags;
		// debug($tags);

		/**
		 * @since 2.7.0 Add live filter attribute to post-template block
		 * @see Greyd\Query\Post_Template for details on how this is used
		 */
		$block['attrs']['liveFilter'] = true;

		if ( isset( $data['lang'] ) ) {

			// Polylang: if post type is not translatable, set lang to null.
			// Otherwise the query will return no results.
			if ( function_exists( 'pll_is_translated_post_type' ) ) {

				$post_type = null;
				if (
					isset( $block['attrs'] )
					&& isset( $block['attrs']['query'] )
					&& isset( $block['attrs']['query']['query'] )
					&& isset( $block['attrs']['query']['query']['postType'] )
				) {
					$post_type = $block['attrs']['query']['query']['postType'];
				}
				else if ( isset( $wp_query_data ) && isset( $wp_query_data['post_type'] ) ) {
					$post_type = $wp_query_data['post_type'];
				}

				if ( !empty( $post_type ) && ! pll_is_translated_post_type( $post_type ) ) {
					$data['lang'] = null;
				}
			}

			// set language for query
			add_filter( 'greyd_query_args' , function( $args ) use ( $data ) {
				$args['lang'] = $data['lang'];
				return $args;
			});
		}

		// render
		$block_content = (new \Greyd\Query\Post_Template( $block, '<ul' ))->render( array('update' => true) );

		/**
		 * Get core layout styles.
		 * Styles from the core/gutenberg style engine are not rendered in the block_content
		 * and may duplicate classes from the original page content.
		 * We replace these classes with unique ones and render the styles manually.
		 * @since 1.14.1
		 */
		$block_content = \Greyd\Enqueue::replace_core_css_classes_in_block_content( $block_content );
		$stylesheets   = \Greyd\Enqueue::get_all_custom_stylesheets();

		return $block_content . $stylesheets;

	}
}