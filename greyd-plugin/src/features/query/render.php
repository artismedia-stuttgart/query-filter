<?php
/**
 * Render Block Data and Block for Query Blocks.
 * - core/query (extension)
 * - core/post-template (extension)
 * - greyd/post-table (custom)
 */
namespace Greyd\Query;

use Greyd\Helper as Helper;
use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;
use Greyd\Dynamic\Dynamic_Helper as Dynamic_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Render($config);
class Render {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		if (!function_exists('register_block_type')) return;
		if (is_admin()) return;

		// set config
		$this->config = (object) $config;

		// hook block rendering
		add_filter( 'pre_render_block', array($this, 'pre_render_block'), 10, 3 );
		add_filter( 'greyd_blocks_render_block_data', array($this, 'render_block_data') );
		add_filter( 'greyd_blocks_render_block', array($this, 'render_block'), 10, 2 );
		// core post-template
		add_filter( 'query_loop_block_query_vars', array($this, 'query_loop_block_query_vars'), 10, 3 );

	}

	/**
	 * Directly render Post-Slider
	 * 
	 * @filter 'pre_render_block' Allows render_block() to be short-circuited, by returning a non-null value.
	 * 
	 * @param null          $pre_render
	 * @param array         $parsed_block
	 * @param WP_Block|null $parent_block
	 * @return string|null  $pre_render   The pre-rendered content. Default null.
	 */
	public static function pre_render_block( $pre_render, $block, $parent_block ) {

		if (
			!is_null($parent_block) &&
			$block["blockName"] == 'core/post-template' &&
			$block['attrs']['variation'] == 'slider'
		) {
			// directly render slider
			// return ( new \Greyd\Query\Post_Template( $block, '<ul' ) )->render();
			return \greyd\blocks\render::render_block( '<ul', $block );
		}
		return $pre_render;
	}

	/**
	 * =================================================================
	 *                          Block Data
	 * =================================================================
	 */

	/**
	 * Greyd block rendering filter.
	 * Filters the block data before the block is rendered.
	 * Extends the core block rendering filter.
	 * https://developer.wordpress.org/reference/hooks/render_block_data/
	 * 
	 * - scan for 'core/query' Block to prepare query for Dynamic Tags
	 * 
	 * @filter 'greyd_blocks_render_block_data'
	 * 
	 * @param object $block     parsed Block
	 * 
	 * @return object $block    parsed Block with altered Block Data
	 */
	public static function render_block_data($block) {

		if ($block['blockName'] == 'core/query') {
			// debug($block['attrs']);
			if (!isset($block['attrs']['query'])) {
				$block['attrs']['query'] = array(
					"perPage" => null,
					"pages" => 0,
					"offset" => 0,
					"postType" => "post",
					"order" => "desc",
					"orderBy" => "date",
					"author" => "",
					"search" => "",
					"exclude" => [],
					"sticky" => "",
					"inherit" => true,
					"taxQuery" => null,
					"parents" => [],
				);
			}
			if (isset($block['attrs']['query'])) {
				// debug($block['attrs']);
				$query = $block['attrs'];
				$query = self::get_display_layout($block, $query);
				$query['queryTags'] = self::get_query_tags($query);
				// debug($query);
				self::$query_loop_has_greyd_search_block_inside = false;
				$block['innerBlocks'] = self::modify_block_data($block, $query);
			}
			// debug($block);
		}
		else if (is_archive() || is_search()) {
			// debug("add page query");
			if (!isset($block['attrs']['queryTags'])) {
				$query = [
					'query' => [ 'inherit' => true ],
					'queryTags' => self::get_query_tags([ 'query' => [ 'inherit' => true ] ])
				];
				$block['innerBlocks'] = self::modify_block_data($block, $query);
			}
		}

		return $block;

	}

	/**
	 * Modify 'core/query' Block layout Data for 'greyd/post-table' Child.
	 * 
	 * @param object $block     parsed 'core/query' Block
	 * @param object $query     query attributes of 'core/query' Block
	 * 
	 * @return object $query    altered query Block
	 */
	public static function get_display_layout($block, $query) {
		// debug($block);
		
		foreach ($block['innerBlocks'] as $inner_block) {
			if ($inner_block["blockName"] == "core/post-template") {
				continue;
			}
			else if ($inner_block["blockName"] == "greyd/post-table") {
				// just for detecting that this is a post-table block later.
				// keep displayLayout attribute name
				$query['displayLayout'] = array( "type" => "table" );
			}
			else if (isset($block['innerBlocks'])) {
				$query = self::get_display_layout($inner_block, $query);
			}
		}
		
		// debug($query['displayLayout']);
		return $query;
	}

	/**
	 * Whether the query loop has an active greyd search block inside.
	 * 
	 * @since 2.7.0 Introduced to support live filtering in post-template block.
	 * 
	 * @note We need to hold this in the class to be able to loop through all
	 *       inner blocks before we adjust the post-template block.
	 * 
	 * @var boolean
	 */
	public static $query_loop_has_greyd_search_block_inside = false;

	/**
	 * Modify children of 'core/query' Block with query Data
	 * 
	 * @param object $block     parsed 'core/query' Block
	 * @param object $query     query attributes of 'core/query' Block
	 * 
	 * @return object $inner    altered inner Blocks
	 */
	public static function modify_block_data($block, $query) {

		$inner = array();

		// vars to adjust post-template block (see after loop)
		// $query_loop_has_greyd_search_block_inside          = false;
		$no_results_block_index    = -1;
		$no_results_inner_blocks   = false;
		$post_template_block_index = -1;

		// loop through inner blocks
		foreach ($block['innerBlocks'] as $i => $inner_block) {
			$b = $inner_block;
			// debug($b);
			if ( $b['blockName'] != 'core/query' ) {

				$b['attrs']['queryTags'] = $query['queryTags'];

				if (
					strpos($b['blockName'], 'core/query-pagination') === 0
					|| $b['blockName'] == 'core/query-no-results'
					|| $b['blockName'] == 'core/post-template'
					|| $b['blockName'] == 'greyd/post-table'
				) {
					$b['attrs']['query'] = $query;
					unset($b['attrs']['query']['queryTags']);
				}

				if ( isset($b['innerBlocks']) ) {
					$b['innerBlocks'] = self::modify_block_data($b, $query);
				}

				// add support for greyd search block
				if ( $b["blockName"] == "greyd/search" ) {
					self::$query_loop_has_greyd_search_block_inside = true;
				}

				// add support for no results message
				if ( $b['blockName'] == 'core/query-no-results' ) {
					$no_results_block_index = $i;
					if ( isset($b['innerBlocks']) ) {
						$no_results_inner_blocks = $b['innerBlocks'];
					} else {
						$no_results_inner_blocks = [];
					}
				}

				// post template block found
				if ( $b['blockName'] == 'core/post-template' ) {
					$post_template_block_index = $i;
					// make sure variation attribute is set
					$b = self::is_slider($b);
				}
			}
			// array_push($inner, $b);
			$inner[ $i ] = $b;
		}

		// we adjust the post-template block after we looped through all inner blocks
		// this way all other modifications are already applied and we are not depen-
		// dent on the order of the blocks in the innerBlocks array.
		if ( $post_template_block_index > -1 && $inner[ $post_template_block_index ]['attrs']['variation'] == 'slider' ) {

			/**
			 * @since 2.7.0 Add no results message block content to post-template block
			 * @see Greyd\Query\Post_Template for details on how this is used
			 */
			if (
				$no_results_inner_blocks !== false &&
				empty($inner[ $post_template_block_index ]['attrs']['noResultsMessage'])
			) {
				$no_results_message = '';
				foreach ($no_results_inner_blocks as $no_results_inner_block) {
					// debug($inner_inner_block);
					$no_results_message .= (new \WP_Block( $no_results_inner_block ))->render();
				}
				$inner[ $post_template_block_index ]['attrs']['noResultsMessage'] = $no_results_message;
				if ($no_results_block_index > -1) {
					// remove no-results innerBlocks to avoid double rendering
					$inner[ $no_results_block_index ]['innerBlocks'] = array();
					$inner[ $no_results_block_index ]['innerContent'] = array();
				}
			}

			/**
			 * @since 2.7.0 Add live filter attribute to post-template block
			 * @see Greyd\Query\Post_Template for details on how this is used
			 */
			if ( self::$query_loop_has_greyd_search_block_inside ) {
				$inner[ $post_template_block_index ]['attrs']['liveFilter'] = true;
				self::$query_loop_has_greyd_search_block_inside = false;
			}
		}

		return $inner;
	}

	/**
	 * Holds the tags of all queries in use, keyed by queryId.
	 * The main query is cached with the ID -1.
	 * 
	 * @var array
	 */
	public static $query_tags = array();

	/**
	 * Get Dynamic Tags of 'core/query' Block query
	 * 
	 * @param object $atts      query attributes of 'core/query' Block
	 * 
	 * @return object $tags     Collection of pre-rendered Dynamic Tags
	 */
	public static function get_query_tags($atts) {
		// debug($atts);

		// get hash
		$hash_key = md5(json_encode($atts));

		// return cached query tags
		if ( isset(self::$query_tags[ $hash_key ]) ) {
			return self::$query_tags[ $hash_key ];
		}

		$page_key = isset($atts['queryId']) ? 'query-'.$atts['queryId'].'-page' : 'query-page';
		$page     = empty($_GET[$page_key]) ? 1 : (int) $_GET[$page_key];
	
		$query_args = self::build_query($atts, $page);
		// Override the custom query with the global query if needed.
		$use_global_query = (isset($atts['query']['inherit']) && $atts['query']['inherit'] != false);
		if ($use_global_query) {
			global $wp_query;
			if ($wp_query && isset($wp_query->query_vars) && is_array($wp_query->query_vars)) {
				// Unset `offset` because if is set, $wp_query overrides/ignores the paged parameter and breaks pagination.
				unset($query_args['offset']);
				// $query_args = wp_parse_args($wp_query->query_vars, $query_args);
				$query_args = wp_parse_args( $query_args, $wp_query->query_vars );
	
				if (empty($query_args['post_type']) && is_singular()) {
					$query_args['post_type'] = get_post_type(get_the_ID());
				}
				$page = isset($query_args['paged']) && $query_args['paged'] != "0" ? $query_args['paged'] : "1";
				// debug($query_args);
				$props = array(
					'current' => 1,
					'previous_link' => '',
					'next_link' => '',
					'pages' => array()
				);
				$pagination_array = paginate_links( array(
					'prev_text' => 'x',
					'next_text' => 'x',
					'type' => 'array',
					'current' => $page
				) );
				// debug($pagination_array);
				
				if ($pagination_array) {
	
					foreach ($pagination_array as $link) {
						// debug( htmlspecialchars($link) );
						// <span aria-current="page" class="page-numbers current">2</span>
		
						// get href
						$href = "";
						preg_match( '/href="([^"])+?"/', $link, $matches );
						if ( $matches && count($matches) ) {
							$href = str_replace( array('href="', '"'), "", $matches[0] );
						}
		
						// prev
						if (strpos($link, 'prev')) {
							$props['previous_link'] = $href;
						}
		
						// next
						else if (strpos($link, 'next')) {
							$props['next_link'] = $href;
						}
		
						// page numbers
						else {
							// get page number (either a number or the filler "…")
							$num = null;
							preg_match( '/>([^<])+?</', $link, $matches );
							if ( $matches && count($matches) ) {
								$num = str_replace( array('>', '<'), "", $matches[0] );
							}
		
							// current page
							if (strpos($link, 'current')) {
								$props['current'] = $num;
								$href = "";
							}
							
							if ( isset($props['pages'][$num]) ) $num = "&#8230;"; // filler "…" could be set twice on long paginations
		
							$props['pages'][$num] = $href;
						}
					}
				}
				// debug($props);


			}
		}
	
		$query = new \WP_Query($query_args);
		// debug($query);
		$max_page = ($query->max_num_pages > 0) ? $query->max_num_pages : 1;
		// if (!$use_global_query && isset($atts['query']['pages'])) {
		// 	if ($atts['query']['pages'] > 0 && $atts['query']['pages'] < $max_page) {
		// 		$max_page = $atts['query']['pages'];
		// 	}
		// }

		$max_post = $query->query_vars['posts_per_page'] * $max_page;
		$tags = array(
			'query'         => "<span class='query--search-query'>".get_search_query()."</span>",
			'filter'        => Dynamic_Helper::make_filter_string($query, null),
			'post-type'     => Dynamic_Helper::make_posttype_string($query, null),
			'category'      => Dynamic_Helper::make_category_string($query, null),
			'tag'           => Dynamic_Helper::make_tag_string($query, null),
			// 'post-count'    => $max_post, // $query->found_posts*$max_page,
			'post-count'    => "<span class='query--found-posts'>".$query->found_posts."</span>",
			'posts-per-page'=> $query->query_vars['posts_per_page'],
			'page-count'    => $max_page,
		);
		if ($use_global_query) $tags = array_merge($tags, array(
			'page-num'      => $props['current'],
			'page-next'		=> esc_url($props['next_link']),
			'page-previous' => esc_url($props['previous_link']),
			'page-key' 		=> '',
			'page-keys' 	=> $props['pages'],
		));
		else $tags = array_merge($tags, array(
			'page-num'      => $page,
			'page-next'		=> '',
			'page-previous' => '',
			'page-key' 		=> '',
		));
		// debug($tags);

		/**
		 * TBD: there was a reason this was called, but it causes errors with dynamic elements inside core/post-template blocks.
		 * needs more testing.
		 */
		// wp_reset_postdata();

		// set cached query tags
		// self::$query_tags[ $queryId ] = $tags;
		self::$query_tags[ $hash_key ] = $tags;
	
		return $tags;
	}

	
	/**
	 * Build the custom query args from query block attributes.
	 * 
	 * @param array $atts   Query block attributes.
	 * @param int $page     Current page number.
	 * @return array $query_args
	 */
	public static function build_query($atts, $page) {
		$query = array(
			'post_type'    => 'post',
			'order'        => 'DESC',
			'orderby'      => 'date',
			'post__not_in' => array(),
			'post_status'       => 'publish',
			'suppress_filters'  => false,
		);

		if ( isset($atts['query']) ) {

			// inherit main query vars
			if ( isset($atts['query']['inherit']) && $atts['query']['inherit'] ) {
				
				$query_vars = self::get_main_query_vars();
				foreach ( $query_vars as $key => $value ) {
					// we only need to adjust properties that actually interfere with the query
					// and are set inside the block attributes.
					$query_vars_to_atts_map = array(
						's'              => 'search',
						'orderby'        => 'orderBy',
						'order'          => 'order',
						'post_type'      => 'postType',
						'posts_per_page' => 'perPage'
					);
					if ( isset($query_vars_to_atts_map[$key]) ) {
						$atts['query'][ $query_vars_to_atts_map[$key] ] = $value;
					}
					else if ( taxonomy_exists($key) ) {
						// also inherit taxonomies from tax archives
						$atts['query']['taxQuery'][$key] = explode( ',', $value );
					}
				}
			}

			if (!empty($atts['query']['postType'])) {
				$post_type_param = $atts['query']['postType'];
				if ( $post_type_param === 'any' || is_post_type_viewable($post_type_param) ) {
					$query['post_type'] = $post_type_param;
				}
			}
			if (isset($atts['query']['sticky']) && !empty($atts['query']['sticky'])) {
				$sticky = get_option('sticky_posts');
				if ('only' === $atts['query']['sticky']) {
					$query['post__in'] = $sticky;
				} 
				else {
					$query['post__not_in'] = array_merge($query['post__not_in'], $sticky);
				}
			}
			if (!empty($atts['query']['exclude'])) {
				$excluded_post_ids = array_map('intval', $atts['query']['exclude']);
				$excluded_post_ids = array_filter($excluded_post_ids);
				$query['post__not_in'] = array_merge($query['post__not_in'], $excluded_post_ids);
			}
			if (isset($atts['query']['perPage']) &&
				is_numeric($atts['query']['perPage'])) {
				$per_page = absint($atts['query']['perPage']);
				$offset = 0;
				if (isset($atts['query']['offset']) &&
					is_numeric($atts['query']['offset'])) {
					$offset = absint($atts['query']['offset']);
				}
				$query['offset'] = ($per_page * ($page - 1)) + $offset;
				$query['posts_per_page'] = $per_page;
			}
			if (isset($atts['query']['order']) &&
				in_array(strtoupper($atts['query']['order']), array('ASC', 'DESC'), true)) {
				$query['order'] = strtoupper($atts['query']['order']);
			}
			if (isset($atts['query']['orderBy'])) {
				$query['orderby'] = $atts['query']['orderBy'];
			}
			if (isset($atts['query']['author'])) {
				$query['author'] = $atts['query']['author'];
			}
			if (isset($atts['query']['search'])) {
				
				if ( !empty($atts['query']['search']) ) {
					$query['s'] = $atts['query']['search'];
				}
				// we only inherit an empty search query if we are on a search page
				else if (
					is_search()
					&& isset($atts['query']['inherit'])
					&& $atts['query']['inherit']
				) {
					$query['s'] = $atts['query']['search'];
				}
			}
			if (isset($atts['query']['date']) && !empty($atts['query']['date'])) {


				$filter_by = isset($atts['query']['date']['filterBy']) ? $atts['query']['date']['filterBy'] : 'post_date';

				// var_error_log($atts['query']['date']);
				switch ($filter_by) {
					case 'post_date':
						$query = \Greyd\Search\Date::prepare_date_query_args($query, $atts['query']['date']['post_date']);
						break;
					case 'meta_date':
						$query = \Greyd\Search\Date::prepare_meta_date_query_args($query, $atts['query']['date']['meta_date']);
						break;
					case 'dynamic_meta_date':
						$query = \Greyd\Search\Date::prepare_dynamic_meta_date_query_args($query, $atts['query']['date']['dynamic_meta_date']);
						break;
				}
			}
			
			/**
			 * @since WordPress 5.9
			 */
			if ( isset($atts['query']['taxQuery']) && !empty($atts['query']['taxQuery']) ) {
				$query['tax_query'] = array();
				foreach( $atts['query']['taxQuery'] as $taxonomy => $term_ids ) {

					/**
					 * By default, wordpress search supports the URL query parameter 'category_name' for category search.
					 * This can also be passed to the query attribute 'taxQuery' as 'category_name'. We need to convert it to 'category'.
					 */
					if ( $taxonomy === 'category_name' ) {
						$taxonomy = 'category';
					}

					if ( !empty($term_ids) ) {
						// convert term slug to id
						foreach ( $term_ids as $i => $term ) {
							if ( is_string($term) ) {
								$term = get_term_by( 'slug', $term, $taxonomy );
								$term_ids[$i] = $term->term_id;
							}
						}
						array_push( $query['tax_query'], array_merge(
								array(
									'taxonomy' => $taxonomy,
									'terms'    => $term_ids
								),
								count($term_ids) > 1 ? array(
									/**
									 * @filter greyd_query_filter_tax_query_term_relationship
									 * 
									 * @since 1.3.2
									 * 
									 * @param string $operator  Operator for term filters. Default: 'IN'.
									 * @param string $taxonomy  Taxonomy name of the terms.
									 * @param array $term_ids   Term IDs to be filtered by.
									 * 
									 * @return string $operator
									 */
									'operator' => apply_filters( 'greyd_query_filter_tax_query_term_operator', 'IN', $taxonomy, $term_ids )
								) : array()
							)
						);
					}
				}

				if ( count($query['tax_query']) > 1 ) {
					/**
					 * @filter greyd_query_filter_tax_query_relationship
					 * 
					 * @since 1.3.2
					 * 
					 * @param string $relationship  Relationship between taxonomy filters. Default: 'AND'.
					 * @param array $query_args     Query arguments.
					 * 
					 * @return string $relationship
					 */
					$query['tax_query']['relation'] = apply_filters( 'greyd_query_filter_tax_query_relationship', 'AND', $query );
				}
			}
			/**
			 * @deprecated WordPress 5.9
			 */
			else if ($query['post_type'] != "post" && $query['post_type'] != "page") {
				// filter query
				// https://codex.wordpress.org/Class_Reference/WP_Query#Taxonomy_Parameters
				$query['tax_query'] = array();
				// category and tag
				if (isset($query[$query['post_type'].'_categoryIds']) && count($query[$query['post_type'].'_categoryIds']) > 0) {
					foreach ($query[$query['post_type'].'_categoryIds'] as $cat_id) {
						array_push($query['tax_query'], array(
							'taxonomy'      => $query['post_type'].'_category',
							'terms'         => $cat_id,
							'operator'      => 'IN'
						));
					}
				}
				if (isset($query[$query['post_type'].'_tagIds']) && count($query[$query['post_type'].'_tagIds']) > 0) {
					foreach ($query[$query['post_type'].'_tagIds'] as $tag_id) {
						array_push($query['tax_query'], array(
							'taxonomy'      => $query['post_type'].'_tag',
							'terms'         => $tag_id,
							'operator'      => 'IN'
						));
					}
				}
				// custom taxonomies
				if ($custom_taxonomies = self::preg_match_keys("/^".$query['post_type']."_.+/", $atts['query'])) {
					foreach ((array)$custom_taxonomies as $tax_name => $tax_ids ) {
						$tax_slug = str_replace( "Ids", "", $tax_name );
						foreach ($tax_ids as $tax_id) {
							array_push($query['tax_query'], array(
								'taxonomy'      => $tax_slug,
								'terms'         => $tax_id,
								'operator'      => 'IN'
							));
						}
					}
				}
				// set relation
				if ( count($query['tax_query']) > 1 ) $query['tax_query']['relation'] = 'AND';
			}
			/**
			 * @deprecated WordPress 5.9
			 */
			if (!empty($atts['query']['categoryIds'])) {
				$term_ids = array_map('intval', $atts['query']['categoryIds']);
				$term_ids = array_filter($term_ids);
				$query['category__in'] = $term_ids;
			}
			if (!empty($atts['query']['tagIds'])) {
				$term_ids = array_map('intval', $atts['query']['tagIds']);
				$term_ids = array_filter($term_ids);
				$query['tag__in'] = $term_ids;
			}
		}

		/**
		 * @since 1.6.5
		 */
		if (isset($atts['advancedFilter'])) {
			// debug($atts);

			// cache fields for foreach loop
			$meta_fields = false;
			// save order filter
			$order = false;

			// process all filter
			foreach ($atts['advancedFilter'] as $filter) {

				if (
					!isset($filter['name']) || 
					empty($filter['name']) 
					// !isset($filter[$filter['name']]) || 
					// empty($filter[$filter['name']])
				) continue;

				$not = isset($filter['not']) && $filter['not'];

				if ($filter['name'] == 'date') {

					// make start and end date
					$start_date = "";
					if ( isset($filter['starttype']) && $filter['starttype'] == 'today' ) {
						$start_date = date('Y-m-d H:i:s');
						$span = isset($filter['startspan']) && !empty($filter['startspan']) ? intval($filter['startspan']) : 0;
						$unit = isset($filter['startunit']) && !empty($filter['startunit']) ? $filter['startunit'] : 'days';
						if ( $span > 0 ) $start_date = date('Y-m-d H:i:s', strtotime($start_date. ' + '.$span.' '.$unit));
						if ( $span < 0 ) $start_date = date('Y-m-d H:i:s', strtotime($start_date. ' - '.abs($span).' '.$unit));
					}
					else if ( isset($filter['start']) && !empty($filter['start']) ) {
						$start_date = $filter['start'];
					}
					$end_date = "";
					if ( isset($filter['endtype']) && $filter['endtype'] == 'today' ) {
						$end_date = date('Y-m-d H:i:s');
						$span = isset($filter['endspan']) && !empty($filter['endspan']) ? intval($filter['endspan']) : 0;
						$unit = isset($filter['endunit']) && !empty($filter['endunit']) ? $filter['endunit'] : 'days';
						if ( $span > 0 ) $end_date = date('Y-m-d H:i:s', strtotime($end_date. ' + '.$span.' '.$unit));
						if ( $span < 0 ) $end_date = date('Y-m-d H:i:s', strtotime($end_date. ' - '.abs($span).' '.$unit));
					}
					else if ( isset($filter['end']) && !empty($filter['end']) ) {
						$end_date = $filter['end'];
					}
					if ( !empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date) ) {
						// end should be after start - switch
						$tmp = $start_date;
						$start_date = $end_date;
						$end_date = $tmp;
					}

					// debug($filter);
					// debug($start_date);
					// debug($end_date);

					if ( !isset($filter['field']) || empty($filter['field']) ) {
						// make date query on post_date
						$date_query = array();

						if ( !empty($start_date) && !empty($end_date) ) {
							// start and end date set
							$date_query['relation'] = $not ? 'OR' : 'AND';
							if ( $not ) {
								$date_query[] = array( "before" => $start_date );
								$date_query[] = array( "after" => $end_date );
							}
							else {
								$date_query[] = array( "before" => $end_date );
								$date_query[] = array( "after" => $start_date );
							}
						}
						else if ( !empty($start_date) ) {
							// only start date set
							if ( $not ) $date_query['before'] = $start_date;
							else $date_query['after'] = $start_date;
						}
						else if ( !empty($end_date) ) {
							// only end date set
							if ( $not ) $date_query['after'] = $end_date;
							else $date_query['before'] = $end_date;
						}

						if ( !empty($date_query) ) {
							// set date_query
							if ( !isset($query['date_query']) ) $query['date_query'] = array();
							$date_query['inclusive'] = true;
							$query['date_query'][] = $date_query;
						}
					}
					else {
						// filter by value of metafield of type date or datetime-local
						if ( !empty($start_date) || !empty($end_date) ) {
							if ($meta_fields == false) {
								$meta_fields = self::get_dynamic_fields($query['post_type'], array(
									'rawurlencode' => false,
									'resolve_file_ids' => false,
									'raw_date' => true,
								));
							}
							$post_ids = array();
							foreach ($meta_fields as $id => $fields) {
								if ( !isset($fields[$filter['field']]) ) continue;
								$meta_date = $fields[$filter['field']];
								$add = true;
								if ( !empty($start_date) && strtotime($meta_date) < strtotime($start_date) ) {
									// earlier than start
									$add = false;
								}
								if ( $add && !empty($end_date) && strtotime($meta_date) > strtotime($end_date) ) {
									// later than end
									$add = false;
								}
								if ( $add ) $post_ids[] = $fields['post_id'];
							}

							$post_ids = array_map('intval', $post_ids);
							$post_ids = array_filter($post_ids);
							if ($not) {
								// exclude
								$post__not_in = isset($query['post__not_in']) ? $query['post__not_in'] : array();
								$query['post__not_in'] = array_merge($post__not_in, $post_ids);
							}
							else {
								// include
								$post__in = isset($query['post__in']) ? $query['post__in'] : array();
								$query['post__in'] = array_merge($post__in, $post_ids);
							}		
						}
					}

				}

				else if ( $filter['name'] == 'taxonomy' && isset($filter['taxonomy']) && !empty($filter['taxonomy']) ) {

					if ( !isset($filter['terms']) ) continue;

					if (in_array('current_terms', $filter['terms'])) {
						// get current post terms
						global $post;
						// debug($post);
						$index = array_search('current_terms', $filter['terms']);
						unset($filter['terms'][$index]);
						$terms = ( $post && isset($post->ID) ) ? wp_get_post_terms($post->ID, $filter['taxonomy']) : false;
						// debug($terms);
						if ( is_array($terms) && count($terms) > 0 ) {
							foreach ($terms as $term) {
								$filter['terms'][] = $term->term_id;
							}
						}
					}

					if (in_array('current_archive_terms', $filter['terms'])) {

						// get current archive terms
						$index = array_search('current_archive_terms', $filter['terms']);
						unset($filter['terms'][$index]);

						$current_archive_terms = false;

						// if the global $queried_object_id is set, this is a Live-Query request.
						// otherwise the function get_queried_object() is used.
						global $queried_object_id;
						if ( ! empty($queried_object_id) ) {
							// try to get terms from queried object
							$term = get_term_by( 'id', $queried_object_id, $filter['taxonomy'] );
							if ( is_a( $term, 'WP_Term' ) ) {
								$current_archive_terms = array( $term );
							}
							else {
								// if this fails, the queried object could also be a post
								$terms = wp_get_post_terms( $queried_object_id, $filter['taxonomy'] );
								// debug($terms);
								if ( is_array($terms) && !empty($terms) ) {
									$current_archive_terms = $terms;
								}
							}
						}
						else {
							$queried_object = get_queried_object();
							if ( is_a( $queried_object, 'WP_Post' ) ) {
								$current_archive_terms = wp_get_post_terms( $queried_object->ID, $filter['taxonomy'] );
							}
							else if ( is_a( $queried_object, 'WP_Term' ) ) {
								$current_archive_terms = array( $queried_object );
							}
						}
						if ( is_array($current_archive_terms) && count($current_archive_terms) > 0 ) {
							foreach ($current_archive_terms as $term) {
								$filter['terms'][] = $term->term_id;
							}
						}
					}

					if (in_array('any_terms', $filter['terms'])) {
						// get all valid terms - posts with no terms will be excluded
						$index = array_search('any_terms', $filter['terms']);
						unset($filter['terms'][$index]);
						$all_terms = Helper::get_all_terms($filter['taxonomy']);
						// debug($all_terms);
						if (count($all_terms) > 0) {
							foreach ($all_terms as $term) {
								$filter['terms'][] = $term->id;
							}
						}
					}
					
					$term_ids = array_map('intval', $filter['terms']);
					$term_ids = array_filter($term_ids);
					$include_children = !isset($filter['children']) || $filter['children'] == false;
					if ( !isset($query['tax_query']) ) $query['tax_query'] = array();
					$query['tax_query'][] = array(
						'taxonomy'         => $filter['taxonomy'],
						'terms'            => $term_ids,
						'include_children' => $include_children,
						'operator'         => $not ? 'NOT IN' : 'IN'
					);

				}

				else if ( $filter['name'] == 'meta' && isset($filter['meta']) && !empty($filter['meta']) ) {

					if ($meta_fields == false) {
						$meta_fields = self::get_dynamic_fields($query['post_type'], array(
							'rawurlencode' => false,
							'resolve_file_ids' => false,
							'raw_date' => true,
						));
					}
					
					if ( isset($filter['search']) && in_array('current_meta', $filter['search']) ) {
						// get current post meta field value
						global $post;
						// debug($post);
						$index = array_search('current_meta', $filter['search']);
						$filter['search'][$index] = "";
						foreach ($meta_fields as $id => $fields) {
							if ( $fields['post_id'] == $post->ID ) {
								if (isset($fields[$filter['meta']])) {
									$filter['search'][$index] = $fields[$filter['meta']];
								}
								break;
							}
						}
						// $post_meta = Posttype_Helper::get_dynamic_values($post->ID);
						// if (isset($post_meta[$filter['meta']])) {
						// 	$filter['search'][$index] = $post_meta[$filter['meta']];
						// }
					}
					
					$post_ids = array();
					foreach ($meta_fields as $id => $fields) {
						if ( !isset($fields[$filter['meta']]) ) continue;
						$meta_string = strtolower($fields[$filter['meta']]);
						if ( !isset($filter['operator']) || $filter['operator'] == '' ) {
							if ($meta_string != '') {
								// debug($fields[$filter['meta']]);
								$post_ids[] = $fields['post_id'];
							}
							continue;
						}
						if ( !isset($filter['search']) || empty($filter['search']) ) continue;
						foreach ($filter['search'] as $search) {
							$search_string = strtolower($search);
							if ( empty($search_string) ) continue;
							// debug($search_string);
							if ( $filter['operator'] == 'is' && $meta_string == $search_string ) {
								// debug($fields[$filter['meta']]);
								$post_ids[] = $fields['post_id'];
								break;
							}
							else if ( $filter['operator'] == 'has' && strpos( $meta_string, $search_string ) !== false ) {
								// debug($fields[$filter['meta']]);
								$post_ids[] = $fields['post_id'];
								break;
							}
						}
					}

					$post_ids = array_map('intval', $post_ids);
					$post_ids = array_filter($post_ids);
					if ($not) {
						// exclude
						$post__not_in = isset($query['post__not_in']) ? $query['post__not_in'] : array();
						$query['post__not_in'] = array_merge($post__not_in, $post_ids);
					}
					else {
						// include
						$post__in = isset($query['post__in']) ? $query['post__in'] : array();
						$query['post__in'] = array_merge($post__in, $post_ids);
					}

				}

				else if ( $filter['name'] == 'author' && isset($filter['author']) && !empty($filter['author']) ) {
					
					if (in_array('current_author', $filter['author'])) {
						// get current post author
						global $post;
						// debug($post);
						$post_author = !empty($post) ? $post->post_author : false;
						if ( empty($post) && is_author() ) {
							// get archive author
							global $wp_query;
							// debug($wp_query);
							$post_author = $wp_query->query_vars['author'];
						}
						$index = array_search('current_author', $filter['author']);
						$filter['author'][$index] = $post_author;
					}
					
					if (in_array('any_author', $filter['author'])) {
						// get all valid authors/user - unvalid user (no authors) will be excluded
						$index = array_search('any_author', $filter['author']);
						unset($filter['author'][$index]);
						$users = get_users( array( 'fields' => array( 'ID' ) ) );
						foreach ($users as $user) {
							$filter['author'][] = $user->ID;
						}
					}

					$author_ids = array_map('intval', $filter['author']);
					$author_ids = array_filter($author_ids);
					if ($not) {
						// exclude
						$author__not_in = isset($query['author__not_in']) ? $query['author__not_in'] : array();
						$query['author__not_in'] = array_merge($author__not_in, $author_ids);
					}
					else {
						// include
						$author__in = isset($query['author__in']) ? $query['author__in'] : array();
						$query['author__in'] = array_merge($author__in, $author_ids);
					}

				}

				else if ( $filter['name'] == 'include' && isset($filter['include']) && !empty($filter['include']) ) {

					if (in_array('current_post', $filter['include'])) {
						// get current post id
						global $post;
						if ( !empty($post) ) {
							$index = array_search('current_post', $filter['include']);
							$filter['include'][$index] = $post->ID;
						}
					}
					
					$post_ids = array_map('intval', $filter['include']);
					$post_ids = array_filter($post_ids);
					if ( !isset($filter['children']) || $filter['children'] == false ) {
						// only selected
						if ($not) {
							// exclude
							$post__not_in = isset($query['post__not_in']) ? $query['post__not_in'] : array();
							$query['post__not_in'] = array_merge($post__not_in, $post_ids);
						}
						else {
							// include
							$post__in = isset($query['post__in']) ? $query['post__in'] : array();
							$query['post__in'] = array_merge($post__in, $post_ids);
						}
					}
					else {
						// only children of selected
						if ($not) {
							// exclude
							$post_parent__not_in = isset($query['post_parent__not_in']) ? $query['post_parent__not_in'] : array();
							$query['post_parent__not_in'] = array_merge($post_parent__not_in, $post_ids);
						}
						else {
							// include
							$post_parent__in = isset($query['post_parent__in']) ? $query['post_parent__in'] : array();
							$query['post_parent__in'] = array_merge($post_parent__in, $post_ids);
						}
					}

				}

				else if ($filter['name'] == 'order') {
					// save order filter to make the post order after all other filters
					$order = $filter;
				}

			}

			// process order after all others
			if ($order) {

				$not = isset($order['not']) && $order['not'];

				if ($order['order'] == 'date') {
					$query['orderby'] = 'date';
					$query['order'] = $not ? 'ASC' : 'DESC';
				}
				else if ($order['order'] == 'modified') {
					$query['orderby'] = 'modified';
					$query['order'] = $not ? 'ASC' : 'DESC';
				}
				else if ($order['order'] == 'title') {
					$query['orderby'] = 'title';
					$query['order'] = $not ? 'DESC' : 'ASC';
				}
				else if ($order['order'] == 'meta') {
					if ($meta_fields == false) {
						$meta_fields = self::get_dynamic_fields($query['post_type'], array(
							'rawurlencode' => false,
							'resolve_file_ids' => false,
							'raw_date' => true,
						));
					}
					// pre sort
					usort($meta_fields, function($a, $b) use ($order) {

						if ( !isset($order['meta']) || !isset($a[$order['meta']]) || !isset($b[$order['meta']]) ) return 0;
						$operator = isset($order['operator']) ? $order['operator'] : '';

						if ( $operator == 'chronological' ) {
							return strtotime($a[$order['meta']]) > strtotime($b[$order['meta']]) ? 1 : -1;
						}
						else if ( $operator == 'alphabetical' ) {
							return strtolower($a[$order['meta']]) > strtolower($b[$order['meta']]) ? 1 : -1;
						}
						else if ( $operator == 'numeric' ) {
							return self::floatval($a[$order['meta']]) > self::floatval($b[$order['meta']]) ? 1 : -1;
						}
						// automatic
						else {
							// chronological
							if ( strtotime($a[$order['meta']]) && strtotime($b[$order['meta']]) ) {
								return strtotime($a[$order['meta']]) > strtotime($b[$order['meta']]) ? 1 : -1;
							}
							// numeric
							else if ( is_numeric($a[$order['meta']]) && is_numeric($b[$order['meta']]) ) {
								return self::floatval($a[$order['meta']]) > self::floatval($b[$order['meta']]) ? 1 : -1;
							}
							// alphabetical
							return strtolower($a[$order['meta']]) > strtolower($b[$order['meta']]) ? 1 : -1;
						}
					});
					$post__in = isset($query['post__in']) ? $query['post__in'] : false;
					$post_ids = array();
					foreach ($meta_fields as $id => $field) {
						if ( $post__in == false || in_array($field['post_id'], $post__in) ) {
							$post_ids[] = $field['post_id'];
						}
					}
					if ($not) $post_ids = array_reverse($post_ids);
					$query['post__in'] = $post_ids;
					$query['orderby'] = 'post__in';
				}
				else if ($order['order'] == 'menu_order') {
					$query['orderby'] = 'menu_order';
					$query['order'] = $not ? 'DESC' : 'ASC';
				}
				else if ($order['order'] == 'views') {
					$query['meta_key'] = 'postviews_count';
					$query['orderby'] = 'meta_value_num';
					$query['order'] = $not ? 'ASC' : 'DESC';
				}
				else if ($order['order'] == 'random') {
					$query['orderby'] = 'rand';
				}

			}
		}

		/**
		 * if filtered by meta and no posts match, 'post__in' is set but empty, which returns all posts.
		 * in that case we 'force no results'.
		 */
		if ( isset($query['post__in']) && empty($query['post__in']) ) {
			$query['post__in'] = array(0);
		}

		/**
		 * @filter greyd_query_filter_query_args
		 * 
		 * @since 1.7.0
		 * 
		 * @param array $query  Query arguments.
		 * @param array $atts   Query block attributes.
		 * @param int $page     Current page number.
		 * 
		 * @return array $query
		 */
		return apply_filters( 'greyd_query_filter_query_args', $query, $atts, $page );
	}

	/**
	 * Get the main query params of an inherited query.
	 * 
	 * @return array
	 */
	public static function get_main_query_vars() {

		$query_vars = array();

		global $wp;
		if ( isset($wp->query_vars) && is_array($wp->query_vars) ) {
			foreach ( $wp->query_vars as $key => $value ) {
				$query_vars[$key] = $value;
			}
		}

		// set post type
		if ( !isset($query_vars['post_type']) || empty($query_vars['post_type']) ) {
			if (  is_search() || is_archive() || is_author() ) {
				$query_vars['post_type'] = 'any';
			}
			else if ( is_singular() ) {
				$query_vars['post_type'] = get_post_type( get_the_ID() );
			}
			else if ( is_home() || is_front_page() ) {
				$query_vars['post_type'] = 'post';
			}
			else {
				$query_vars['post_type'] = 'post';
			}
		}
		
		// set posts per page
		if ( !isset($query_vars['posts_per_page']) ) {
			$query_vars['posts_per_page'] = get_option( 'posts_per_page' );
		}

		// Unset `offset` because if is set, $wp_query overrides/ignores
		// the paged parameter and breaks pagination.
		// if ( isset( $query_vars['offset'] ) ) unset( $query_vars['offset'] );
		
		return $query_vars;
	}
	
	/**
	 * Get all dynamic fields of a posttype
	 * 
	 * @param string $posttype
	 * @param array $args (see Posttype_Helper::get_dynamic_values)
	 * @return array $meta_fields
	 */
	public static function get_dynamic_fields( $posttype, $args=array() ) {
		$meta_fields = array();
		$posts = Helper::get_all_posts($posttype);
		if ($posts) {
			// debug($posts);
			foreach ($posts as $post) {
				$fields = Posttype_Helper::get_dynamic_values( $post->id, $args );
				$fields['post_id'] = $post->id;
				$meta_fields[] = $fields;
			}
		}
		
		/**
		 * @filter greyd_query_get_dynamic_fields
		 * 
		 * @param array $meta_fields
		 * @param WP_Post[] $posts
		 * @param string $posttype
		 * @param array $args
		 * 
		 * @return array $meta_fields
		 */
		return apply_filters( 'greyd_query_get_dynamic_fields', $meta_fields, $posts, $posttype, $args );
	}

	/**
	 * Match keys of an array with a regex pattern.
	 * 
	 * @param string $pattern   Regex pattern.
	 * @param array $input      The array to validate.
	 * @param int $flags        Regex flags.
	 * @return array            Matches.
	 */
	public static function preg_match_keys(string $pattern, array $input, $flags = 0) {
		return array_intersect_key(
			$input,
			array_flip( preg_grep(
				$pattern,
				array_keys($input),
				$flags
			) )
		);
	}

	/**
	 * Get float value of a formatted number string.
	 * e.g. 1.500.000,00 or 3,950,000.00
	 * 
	 * @param string $string
	 * @return float
	 */
	public static function floatval($string) {

		// only numbers, dot, comma and minus
		$string = preg_replace("/[^0-9\.,-]/", "", $string);
		// debug($string);

		// remove multiple seperators
		$dot_count = substr_count($string, '.');
		$comma_count = substr_count($string, ',');
		if ( $dot_count > 1 ) {
			$string = preg_replace("/\./", "", $string);
		}
		else if ( $comma_count > 1 ) {
			$string = preg_replace("/,/", "", $string);
		}

		// remove sperator at fourth last place
		$dot = strrpos($string, '.');
		if ( $dot && $dot == strlen($string)-4 ) {
			$string = preg_replace("/\./", "", $string);
		}
		$comma = strrpos($string, ',');
		if ( $comma && $comma == strlen($string)-4 ) {
			$string = preg_replace("/,/", "", $string);
		}
		
		// last comma
		$string = preg_replace("/,/", ".", $string);

		// debug($string);
		return floatval($string);
	}

	/**
	 * =================================================================
	 *                          Block Render
	 * =================================================================
	 */

	/**
	 * Hook Greyd block rendering.
	 * 
	 * @filter 'greyd_blocks_render_block'
	 * 
	 * @param array $content
	 *      @property string block_content     block content about to be appended.
	 *      @property array  html_atts         html wrapper attributes
	 *      @property string style             css styles
	 * @param array  $block             full block, including name and attributes.
	 * 
	 * @return array $rendered
	 *      @property string block_content    altered Block Content
	 *      @property string html_atts        altered html wrapper attributes
	 *      @property string style            altered css styles
	 */
	public static function render_block($content, $block) {
		// debug("render list");

		$block_content = $content['block_content'];
		$html_atts     = $content['html_atts'];
		$style         = $content['style'];

		// core/query block (post overview)
		if ( $block['blockName'] === 'core/query' ) {
			// no (re-)rendering
			// the attributes get passed to 'core/post-template' child elements by render_block_data filter
		}

		// core/post-template
		if ( $block['blockName'] === 'core/post-template' ) {

			if ( isset($block['attrs']['variation']) && $block['attrs']['variation'] == 'slider' ) {
				// re-render
				if ( class_exists( '\Greyd\Query\Post_Template' ) ) {
					$block_content = ( new \Greyd\Query\Post_Template( $block, $block_content ) )->render();
				}
			}
		}

		// greyd/post-table
		if ( $block['blockName'] === 'greyd/post-table' ) {
			// render
			if ( class_exists( '\Greyd\Query\Post_Table' ) ) {
				$block_content = \Greyd\Query\Post_Table::render( $block, $block_content );
			}
		}

		// core/query-pagination-**
		if ( strpos( $block['blockName'], 'pagination' ) > -1 ) {
			// no adjustments on rendering
			// if the query is a slider variation, the pagination events are added in post-slider.js

		}
		// debug($block_content);

		return array(
			'block_content' => $block_content,
			'html_atts' => $html_atts,
			'style' => $style
		);

	}

	/**
	 * Decide whether to render as slider or not.
	 * 
	 * @since 2.6.0 Post template 'Slider' as variation.
	 * We refactored the post template block to support variations. From this point on
	 * the variation attribute is set. But to be properly backwards compatible we need to
	 * consider the following 2 cases:
	 * 
	 * (1) The block was build with the greyd-plugin in place before 2.6.0. The block
	 *     did not have a variation attribute. The block was rendered as slider by default,
	 *     displaying paginations and other slider features. The greyd plugin did always
	 *     set the layout->items attribute. So we can check for this attribute to decide
	 *     whether to render as slider or not.
	 * 
	 * (2) The block was build without the greyd-plugin in place, using the core
	 *     post-template block. The core post-template block does not support
	 *     the layout->items attribute. So we can check for this attribute to decide
	 *     whether to render as a default post list or as slider.
	 * 
	 * @param WP_Block	$block Block instance.
	 * @return WP_Block	$block Block instance with correct variation attribute.
	 */
	public static function is_slider( $block ) {

		if ( $block['blockName'] === 'core/post-template' && !isset($block['attrs']['variation']) ) {

			// the latest deprecated version of this block without the variation attribute, did
			// at least set the layout attribute, so if this attribute is not set, the query was
			// likely build without the plugin in place.
			if ( !isset($block['attrs']['layout']) ) {

				// we still check for older attributes to be sure
				if (
					( isset($block['attrs']['pagination']) && ( !isset($block['attrs']['pagination']['enable']) || $block['attrs']['pagination']['enable'] !== false ) ) ||
					( isset($block['attrs']['filter']) && isset($block['attrs']['filter']['enable']) && $block['attrs']['filter']['enable'] ) ||
					( isset($block['attrs']['arrows']) && isset($block['attrs']['arrows']['enable']) && $block['attrs']['arrows']['enable'] ) ||
					( isset($block['attrs']['sorting']) && isset($block['attrs']['sorting']['enable']) && $block['attrs']['sorting']['enable'] ) ||
					( isset($block['attrs']['animation']) && !empty($block['attrs']['animation']) ) ||
					( isset($block['attrs']['query']) && isset($block['attrs']['query']['displayLayout']) )
				) {
					$block['attrs']['variation'] = 'slider';
				}
				else {
					$block['attrs']['variation'] = '';
				}
			}
			else {
				// The core post-template block does also set the layout attribute but the
				// deprecated non-variation did always set the attribute 'items', which the core
				// post-template block does not support
				if ( isset($block['attrs']['layout']['items']) ) {
					$block['attrs']['variation'] = 'slider';
				} else {
					$block['attrs']['variation'] = '';
				}
			}

		}

		return $block;
	}

	/**
	 * Filters the arguments which will be passed to `WP_Query` for the Query Loop Block.
	 * This enables the advancedFilter Feature for core Post-Template and other Query Loop Block's children.
	 * @since 2.8.0
	 * 
	 * @filter 'query_loop_block_query_vars'
	 * 
	 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
	 * @param WP_Block $block Block instance.
	 * @param int      $page  Current query's page.
	 */
	public  function query_loop_block_query_vars( $query, $block, $page ) {
		if ( !empty( $block->attributes["query"]["advancedFilter"] ) ) {
			return self::build_query($block->attributes["query"], $page);
		}
		return $query;
	}

}