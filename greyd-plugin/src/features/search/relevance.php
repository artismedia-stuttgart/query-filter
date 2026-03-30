<?php
/**
 * Relevance sorting for wp search
 *
 * @since 0.8.8
 */
namespace Greyd\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Relevance();
class Relevance {

	/**
	 * Whether to enable debug logging for logged in users.
	 * Set the @constant RELEVANCE_SCORE_LOG to true to enable.
	 */
	private $debug = false;

	/**
	 * The minimum length of a word to be counted as relevant.
	 *
	 * @since 1.7.3
	 *
	 * If a word is as long or shorter than this value, irrelevant
	 * posts will be removed and the score will be calculated
	 * differently. This is to prevent short words from being too
	 * relevant.
	 * @example if you search for "IT", you don't want every post
	 * with words like "split" in the title to be more relevant
	 * than posts with "IT" in the title.
	 */
	private $small_word_length = 2;

	/**
	 * Construct the class
	 */
	public function __construct() {

		$this->debug = $this->debug || defined( 'RELEVANCE_SCORE_LOG' ) && constant( 'RELEVANCE_SCORE_LOG' );

		// modify query
		add_action( 'pre_get_posts', array( $this, 'prepare_relevance_query' ), 99 );
		add_filter( 'the_posts', array( $this, 'sort_query_results_by_score' ), 50, 2 );

		// deprecated
		// add_filter( 'posts_search', array( $this,'manipulate_where_clause' ), 10, 2 );
		// add_filter( 'posts_orderby', array( $this, 'fix_default_search_order'), 50, 2 );
	}

	/**
	 * Prepare the query when sorted by relevance
	 *
	 * @param WP_Query $query
	 */
	public function prepare_relevance_query( $query ) {

		if ( ! Query::is_search_query( $query ) ) {
			return;
		}

		// get search term
		$search_term = $query->get( 's' );
		if ( empty( $search_term ) ) {
			return;
		}

		// get current orderby
		$orderby = $query->get( 'orderby' );

		$relevance_enabled = Settings::get_setting( array( 'site', 'advanced_search', 'relevance' ) ) === 'true';

		// set relevance as default if enabled
		if ( empty( $orderby ) && $relevance_enabled ) {
			$query->set( 'orderby', 'relevance' );
			$query->set( 'order', 'DESC' );
			$orderby = 'relevance';
		}

		// abort if not relevance query
		if ( $orderby !== 'relevance' ) {
			return;
		}

		// adjust 'posts_per_page'
		$prev_posts_per_page = $query->get( 'prev_posts_per_page' );
		if ( $prev_posts_per_page == '' ) {

			$posts_per_page = $query->get( 'posts_per_page' );
			$posts_per_page = intval( ! empty( $posts_per_page ) ? $posts_per_page : get_option( 'posts_per_page', 10 ) );

			$query->set( 'prev_posts_per_page', $posts_per_page );
			/**
			 * posts_per_page = -1 doesn't work well with rest requests.
			 * If other query_vars are set (e.g. page) this can lead to internal errors.
			 * To fix it we set posts_per_page to a very high number.
			 * @since 2.1.0
			 */
			$query->set( 'posts_per_page', 99999 );
		}

		// adjust 'paged'
		$prev_paged = $query->get( 'prev_paged' );
		if ( $prev_paged == '' ) {

			$paged = $query->get( 'paged' );
			$paged = intval( ! empty( $paged ) ? $paged : 1 );

			$query->set( 'prev_paged', $paged );
			$query->set( 'paged', 0 );
		}

		return $query;
	}

	/**
	 * Modify the post results of a relevance search
	 *
	 * @param WP_Post[] $posts
	 * @param WP_Query  $query
	 *
	 * @return WP_Post[]
	 */
	public function sort_query_results_by_score( $posts, $query ) {

		if ( $query->is_admin || ! $query->is_search || empty( $posts ) ) {
			return $posts;
		}

		// enable debug log when enabled in class & user is logged in
		if ( $this->debug ) {
			$this->debug = is_user_logged_in() || ( isset( $query->query_vars['logged_in'] ) && $query->query_vars['logged_in'] );
		}

		$search_term = $query->get( 's' );
		$orderby     = $query->get( 'orderby' );

		if ( ! empty( $search_term ) && $orderby === 'relevance' ) {

			foreach ( $posts as $i => $post ) {

				if ( ! isset( $posts[ $i ]->score ) || $posts[ $i ]->score < 0 ) {
					if ( $this->debug ) {
						ob_start();
					}

					$posts[ $i ]->score = self::get_post_score( $post, $search_term );

					if ( $this->debug ) {
						$score_structure = ob_get_contents();
						ob_end_clean();
						$posts[ $i ]->post_title .= "<pre>$score_structure</pre>";
					}
				}
			}

			/**
			 * Support to remove posts with score 0.
			 *
			 * @since 1.7.0
			 *
			 * @param bool $remove_unrelevant_results   Whether to remove posts with score 0. Default can be changed
			 *                                          by defining the constant 'RELEVANCE_REMOVE_UNRELEVANT_RESULTS'.
			 * @param WP_Post[] $posts                  The posts to filter.
			 * @param WP_Query $query                   The current WP_Query object.
			 *
			 * @return bool
			 */
			$remove_unrelevant_results = apply_filters(
				'greyd_relevance_remove_unrelevant_results',
				defined( 'RELEVANCE_REMOVE_UNRELEVANT_RESULTS' ) && constant( 'RELEVANCE_REMOVE_UNRELEVANT_RESULTS' ),
				$posts,
				$query
			);
			if ( strlen( $search_term ) <= $this->small_word_length && $remove_unrelevant_results ) {
				$posts = array_filter(
					$posts,
					function( $post ) {
						return $post->score > 0;
					}
				);
			}

			// sort by score
			usort(
				$posts,
				function( $a, $b ) {

					$dif = intval( $b->score ) - intval( $a->score );

					// not the same score: higher score first
					if ( $dif !== 0 ) {
						return $dif;
					}

					// same score: newest first
					if ( strnatcmp( phpversion(), '7.0.0' ) >= 0 ) {
						return $b->post_date <=> $a->post_date; // php 7
					} else {
						return strtotime( $b->post_date ) - strtotime( $a->post_date ); // php 5
					}
				}
			);

			// if ( defined('WP_DEBUG') && WP_DEBUG ) {
			// foreach ( $posts as $post ) {
			// $debug_log = $post->post_title.": ".$post->score." || ID : ".$post->ID." || SiteID : ".$post->blog_id;
			// var_error_log( $debug_log );
			// }
			// }
		}

		/**
		 * We don't need to restore the query vars ('posts_per_page' etc.)
		 * This gets automatically adjusted:
		 *
		 * @see \Greyd\Search\Query->restore_prev_query_vars()
		 */

		return $posts;
	}

	/**
	 * Get the relevance score for a single post
	 *
	 * @param object|int $post      WP_Post object or post ID
	 * @param string     $search_term   Query search term
	 *
	 * @return int $score
	 */
	public function get_post_score( $post, $search_term = '' ) {

		if ( ! is_object( $post ) ) {
			$post = is_numeric( $post ) ? get_post( $post ) : null;
			if ( ! is_object( $post ) ) {
				return 0;
			}
		}

		$score       = 0;
		$search_term = empty( $search_term ) ? ( isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '' ) : $search_term;
		$search_term = strtolower( preg_replace( '/\s+/', ' ', trim( $search_term ) ) );

		if ( empty( $search_term ) ) {
			return $score;
		}

		// weights
		$title_weight          = apply_filters( 'greyd_relevance_title_weight', 5, $post, $search_term );
		$content_weight        = apply_filters( 'greyd_relevance_content_weight', 1, $post, $search_term );
		$exact_word_multiplier = apply_filters( 'greyd_relevance_exact_word_multiplier', 3, $post, $search_term );

		// strip html tags & remove shortcodes
		// $title      = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', strip_tags(strtolower(apply_filters('the_title', $post->post_title, $post->ID))));
		// $content    = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', strip_tags(strtolower(apply_filters('the_content', $post->post_content))));
		// $title      = strtolower($post->post_title);
		// $content    = strip_tags( do_blocks($post->post_content) );
		$title   = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', strip_tags( strtolower( $post->post_title ) ) );
		$content = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', strip_tags( strtolower( $post->post_content ) ) );

		// look for the exact search phrase
		if ( strpos( $search_term, ' ' ) !== false ) {
			$hits   = substr_count( $title, $search_term );
			$score += $hits * $title_weight;
			if ( $this->debug && $hits ) {
				echo "$hits hits in the title for '$search_term' ($hits x $title_weight)<br>";
			}
			$hits   = substr_count( $content, $search_term );
			$score += $hits * $content_weight;
			if ( $this->debug && $hits ) {
				"$hits hits in the content for '$search_term' ($hits x $content_weight)<br>";
			}
		}

		// look for words
		$words = explode( ' ', $search_term );
		foreach ( $words as $word ) {

			/**
			 * Only count hits for words longer than 3 characters.
			 */
			if ( strlen( $word ) > $this->small_word_length ) {
				$hits   = substr_count( $title, $word );
				$score += $hits * $title_weight;
				if ( $this->debug && $hits ) {
					echo "$hits hits in the title for '$word' ($hits x $title_weight)<br>";
				}
				$hits   = substr_count( $content, $word );
				$score += $hits * $content_weight;
				if ( $this->debug && $hits ) {
					echo "$hits hits in the content for '$word' ($hits x $content_weight)<br>";
				}
			}

			// if the exact word hits, increase the score
			if ( preg_match( "/\b$word\b/", $title ) ) {
				$score += $title_weight * $exact_word_multiplier;
				if ( $this->debug ) {
					echo "Exact word hit of '$word' in the title ($title_weight x $exact_word_multiplier)<br>";
				}
			}
			if ( preg_match( "/\b$word\b/", $content ) ) {
				$score += $content_weight * $exact_word_multiplier;
				if ( $this->debug ) {
					echo "Exact word hit of '$word' in the content ($content_weight x $exact_word_multiplier)<br>";
				}
			}

			// var_error_log("POST: ".$title." SCORE: ".$score." WORD: ".$word);
		}

		if ( $this->debug ) {
			echo "Score: <strong>$score</strong>";
		}

		return $score;
	}

	/**
	 * Manipulate the Search SQL WHERE clause.
	 *
	 * @deprecated since 1.0.3
	 *
	 * @param string    $search    Search SQL for WHERE clause.
	 * @param \WP_Query $query  The current WP_Query object.
	 *
	 * @return string
	 */
	public function manipulate_where_clause( $search, $query ) {

		if ( $query->is_admin || ! $query->is_search || empty( $search ) ) {
			return $search;
		}

		$chunks     = explode( ' AND ', $search );
		$first      = array_shift( $chunks );
		$new_search = ' AND ';

		foreach ( $chunks as $key => $chunk ) {
			if ( $key === 0 ) {
				$new_search .= $chunk;
				continue;
			}
			if ( false === strpos( $chunk, 'post_title' ) || false === strpos( $chunk, 'post_content' ) ) {
				$new_search .= ' AND ' . $chunk;
			} else {
				$new_search .= ' OR ' . $chunk;
			}
		}
		return $new_search;
	}

	/**
	 * Fix: Because the orderby default of the WordPress search is relevance (searches only in title for relevance
	 * and therefore messes up our search features) this is a fix to set the default orderby to "date DESC" if our relevance
	 * feature is not activated
	 *
	 * @deprecated since 1.0.3
	 */
	function fix_default_search_order( $orderby, $query ) {

		if ( $query->is_admin || ! $query->is_search ) {
			return $orderby;
		}

		$relevance_search = Settings::get_setting( array( 'site', 'advanced_search', 'relevance' ) ) === 'true';
		if ( ! $relevance_search ) {
			global $wpdb;
			// $orderby =  $wpdb->prefix."posts.post_date DESC";
		}
		return $orderby;
	}
}
