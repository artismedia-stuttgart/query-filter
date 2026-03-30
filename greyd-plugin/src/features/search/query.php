<?php
/**
 * Extend WordPress search query
 */
namespace Greyd\Search;

use Greyd\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Query( $config );
class Query {

	private $config;
	public function __construct( $config ) {
		// set config
		$this->config = (object) $config;

		// frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ), 99 );

		if ( ! is_admin() ) {

			// add our own query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

			// restore some query vars
			add_filter( 'the_posts', array( $this, 'restore_prev_query_vars' ), 99, 2 );

			// order by taxonomy
			add_filter( 'posts_clauses', array( $this, 'query_orderby_taxonomy' ), 10, 2 );

			// add meta fields to search query
			add_filter( 'posts_join', array( $this, 'search_join' ), 10, 2 );
			add_filter( 'posts_where', array( $this, 'search_where' ), 10, 2 );
			add_filter( 'posts_distinct', array( $this, 'search_distinct' ), 10, 2 );
		}
	}

	/**
	 * Add Frontend skripts for auto search and live search
	 * todo: only works for Greyd.Blocks or greyd vc moduls
	 */
	public function load_frontend_scripts() {

		$css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
		$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

		// livesearch script
		if ( is_search() ) {
			$settings = Settings::get_setting( array( 'site', 'advanced_search' ) );

			if ( $settings && $settings['live_search'] == 'true' ) {
				wp_register_script(
					$this->config->plugin_name . '_livesearch_js',
					$js_uri . '/livesearch.js',
					array( 'jquery' ),
					GREYD_VERSION,
					array(
						'strategy' => 'defer',
						'in_footer' => true
					)
				);
				wp_enqueue_script( $this->config->plugin_name . '_livesearch_js' );
			}
		}
	}

	/**
	 * Add meta_query to query_vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'prev_posts_per_page';
		$vars[] = 'prev_paged';
		$vars[] = 'meta_query';
		return $vars;
	}

	/**
	 * Restore previous query vars.
	 *
	 * In some custom searches (eg. sort by relevance), the query vars
	 * 'posts_per_page' and 'paged' are adjusted, to include all posts.
	 * We now reset them using the folowing (custom) query vars:
	 *
	 * @var int $query->prev_posts_per_page
	 * @var int $query->prev_paged
	 *
	 * @param WP_Post[] $posts
	 * @param WP_Query  $query
	 *
	 * @return WP_Post[]
	 */
	public function restore_prev_query_vars( $posts, $query ) {

		if ( $query->is_admin || ! $query->is_search || empty( $posts ) ) {
			return $posts;
		}

		// restore 'posts_per_page'
		$prev_posts_per_page = $query->get( 'prev_posts_per_page' );
		if ( $prev_posts_per_page !== '' ) {

			// adjust found posts count
			$count = count( $posts );
			if ( $query->found_posts != $count ) {
				$query->found_posts = $count;
			}

			// adjust 'posts_per_page' & 'max_num_pages'
			$query->set( 'posts_per_page', $prev_posts_per_page );
			$query->set( 'prev_posts_per_page', '' );
			$query->max_num_pages = ceil( $count / $prev_posts_per_page );
		}

		// restore 'paged'
		$prev_paged = $query->get( 'prev_paged' );
		if ( $prev_paged !== '' ) {
			$query->set( 'paged', $prev_paged );
			$query->set( 'prev_paged', '' );
		}

		// slice the array
		if ( ! empty( $prev_posts_per_page ) || ! empty( $prev_paged ) ) {
			$offset = ( $query->get( 'paged' ) - 1 ) * $query->get( 'posts_per_page' );
			$posts  = array_slice( $posts, $offset, $query->get( 'posts_per_page' ) );
		}

		return $posts;
	}


	/**
	 * Order posts query by taxonomy.
	 *
	 * this is used to enable sortable post-tables on archive- & search-pages
	 * just prepend 'tax_' to the taxonomy name
	 *
	 * @source: http://scribu.net/wordpress/sortable-taxonomy-columns.html
	 */
	public function query_orderby_taxonomy( $clauses, $query ) {

		// backward compatibility with theme function
		if ( \method_exists( "\basics", 'query_orderby_taxonomy' ) ) {
			return $clauses;
		}

		if (
			$query->is_admin || // used in frontend only
			$query->is_tax // in taxonomy archives the term-tables already get searched -> leads to wpdb error
		) {
			return $clauses;
		}

		global $wpdb;
		$orderby = isset( $query->query['orderby'] ) ? $query->query['orderby'] : null;
		if ( ! empty( $orderby ) && is_string( $orderby ) && substr( $orderby, 0, 4 ) === 'tax_' ) {

			$orderby = str_replace( 'tax_', '', $orderby );

			$clauses['join'] .= "
                LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
                LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
                LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
            ";

			$clauses['where']   .= " AND (taxonomy = '{$orderby}' OR taxonomy IS NULL)";
			$clauses['groupby']  = 'object_id';
			$clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
			$clauses['orderby'] .= ( 'ASC' == strtoupper( $query->get( 'order' ) ) ) ? 'ASC' : 'DESC';
		}
		return $clauses;
	}


	/**
	 * =================================================================
	 *                          SEARCH META QUERY
	 * =================================================================
	 */

	/**
	 * Join posts and postmeta tables
	 *
	 * @source http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
	 */
	public function search_join( $join, $query ) {

		if ( $query->is_admin || ! $query->is_search || ! self::search_metafields_option_enabled() ) {
			return $join;
		}

		global $wpdb;
		$join .= ' LEFT JOIN ' . $wpdb->postmeta . ' meta_alias ON ' . $wpdb->posts . '.ID = meta_alias.post_id ';

		return $join;
	}

	/**
	 * Modify the search query with posts_where
	 *
	 * @source http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
	 */
	public function search_where( $where, $query ) {

		if ( $query->is_admin || ! $query->is_search || ! self::search_metafields_option_enabled() ) {
			return $where;
		}

		global $pagenow, $wpdb;
		$where = preg_replace(
			'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			'(' . $wpdb->posts . '.post_title LIKE $1) OR (meta_alias.meta_value LIKE $1)',
			$where
		);

		return $where;
	}

	/**
	 * Prevent duplicates
	 *
	 * @source http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
	 */
	public function search_distinct( $where, $query ) {

		if ( $query->is_admin || ! $query->is_search || ! self::search_metafields_option_enabled() ) {
			return $where;
		}

		global $wpdb;
		return 'DISTINCT';
	}


	/**
	 * =================================================================
	 *                          UTILS
	 * =================================================================
	 */

	/**
	 * Whether we are in a REST REQUEST. Similar to is_admin().
	 */
	public static function is_wp_rest_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Check whether the query is a search query.
	 */
	public static function is_search_query( $query ) {

		// this is not a search by default
		if ( ! $query->is_search ) {
			return false;
		}

		// rest requests could also be searches (eg. live-search, autofill)
		if ( $query->is_admin() ) {
			if ( ! self::is_wp_rest_request() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if option 'Search post metadata' is enabled
	 */
	public static function search_metafields_option_enabled() {
		return Settings::get_setting( array( 'site', 'search_metafields' ) ) === 'true';
	}
}
