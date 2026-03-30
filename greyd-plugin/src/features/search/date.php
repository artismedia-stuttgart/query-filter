<?php

namespace Greyd\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Date();
class Date {

	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'prepare_date_query' ), 99 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'post_date';
		$vars[] = 'meta_date';
		$vars[] = 'dynamic_meta_date';
		return $vars;
	}

	/**
	 * Prepare date query
	 *
	 * @param WP_Query $query
	 * @return WP_Query
	 */
	public function prepare_date_query( $query ) {

		if (
			$query->is_admin
			|| ! $query->is_search
			// return if it's neither the main- nor a live-query
			|| ( ! $query->is_main_query() && ! $query->get( 'is_livesearch' ) )
		) {
			return $query;
		}

		$date_query = $query->get('post_date');
		$meta_date = $query->get('meta_date');
		$dynamic_meta_date = $query->get('dynamic_meta_date');

		switch (true) {
			case !empty($date_query):
				$date_query = self::prepare_date_query_args($query, $date_query);

				break;
			case !empty($meta_date):
				$date_query = self::prepare_meta_date_query_args($query, $meta_date);
				break;
			case !empty($dynamic_meta_date):
				$date_query = self::prepare_dynamic_meta_date_query_args($query, $dynamic_meta_date);
				break;
			default:
				return;
		}

		return $query;
	}

	/**
	 * Prepare date query args
	 *
	 * @param WP_Query $query
	 * @param array $date_query
	 * @return WP_Query
	 */
	public static function prepare_date_query_args( $query, $date_query) {
		$date_query['inclusive'] = true;
		if ( is_array($query) ) {
			$query['date_query'] = array(
				$date_query
			);
		} else {
			$query->set( 'date_query', array(
				$date_query
			));
		}
		
		return $query;
	}

	/**
	 * Prepare meta date query args
	 *
	 * @param WP_Query $query
	 * @param array $date_query
	 * @return WP_Query
	 */
	public static function prepare_meta_date_query_args( $query, $date_query ) {	
	
		if ( !isset($date_query['from']) || !isset($date_query['to']) ) {
			return $query;
		}

		if ( isset($date_query['field']) && isset($date_query['from']) && isset($date_query['to']) ) {
			if ( is_array($query) ) {
				$query['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key' => $date_query['field'], 
						'value' => $date_query['from'], 
						'compare' => '>=', 
						'type' => 'DATE'
					),
					array(
						'key' => $date_query['field'], 
						'value' => $date_query['to'], 
						'compare' => '<=',
						'type' => 'DATE'
					)
				);
			} else {
				$query->set( 'meta_query', array(
					'relation' => 'AND',
					array(
						'key' => $date_query['field'], 
						'value' => $date_query['from'], 
						'compare' => '>=', 
						'type' => 'DATE'
					),
					array(
						'key' => $date_query['field'], 
						'value' => $date_query['to'], 
						'compare' => '<=',
						'type' => 'DATE'
					)
				));
			}
		} else {
			if ( is_array($query) ) {
				$query['meta_query'] = array(
					'key' => $date_query['field'],
					'value' => $date_query['from'],
					'compare' => '=',
					'type' => 'DATE'
				);
			} else {
				$query->set( 'meta_query', array(
					'key' => $date_query['field'],
					'value' => $date_query['from'],
					'compare' => '=',
					'type' => 'DATE'
				));
			}
		}
		return $query;
	}

	/**
	 * Prepare dynamic meta date query args
	 *
	 * @param WP_Query $query
	 * @param array $date_query
	 * @return WP_Query
	 */
	public static function prepare_dynamic_meta_date_query_args( $query, $date_query ) {
		
		if ( is_array($query)) {
			$posttype = $query['post_type'];
		} else {
			$posttype = $query->get('post_type');
		}

		if ( !isset($date_query['from']) && !isset($date_query['to']) ) {
			return $query;
		}

		if ( isset($date_query['field']) && isset($posttype) ) {
			$posts = \Greyd\Helper::get_all_posts($posttype);
			$field = $date_query['field'];

			$post__in = array();
			$post__not_in = array();

			foreach ($posts as $post) {
				$dynamic_meta = \Greyd\Posttypes\Posttype_Helper::get_dynamic_meta($post->id, $field);
				
				if ( $date_query['from'] == $date_query['to'] ) {
					if ( 
						array_key_exists($field, $dynamic_meta)
						&& isset($dynamic_meta[$field]) 
						&& self::compare_date($dynamic_meta[$field], $date_query['from']) == 0 
					) {
						$post__in[] = $post->id;
					} else {
						$post__not_in[] = $post->id;
					}
				} else {
					if ( 
						array_key_exists($field, $dynamic_meta)
						&& isset($dynamic_meta[$field]) 
						&& self::compare_date($dynamic_meta[$field], $date_query['from']) >= 0 
						&& self::compare_date($dynamic_meta[$field], $date_query['to']) <= 0 
					) {
						// debug("FROM". $date_query['from']);
						// debug("TO". $date_query['to']);
						// debug("META". $dynamic_meta[$field]);
	
						$post__in[] = $post->id;
					} else {
						$post__not_in[] = $post->id;
					}
				}
			}

			if ( is_array($query) ) {
				$query['post__in'] = $post__in;
				$query['post__not_in'] = $post__not_in;

			} else {
				$query->set( 'post__in', $post__in );
				$query->set( 'post__not_in', $post__not_in );
			}
		}
				
		return $query;
	}
	/**
	 * Compare date
	 *
	 * @param string $date1
	 * @param string $date2
	 * @return int
	 */
	public static function compare_date( $date1, $date2 ) {
		$date1 = strtotime($date1);
		$date2 = strtotime($date2);
		if ( $date1 > $date2 ) {
			return 1;
		} else if ( $date1 < $date2 ) {
			return -1;
		} else {
			return 0;
		}
	}
}