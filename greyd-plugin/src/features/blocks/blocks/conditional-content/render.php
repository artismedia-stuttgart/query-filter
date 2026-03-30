<?php
namespace greyd\blocks;

/**
 * Render Conditional Content block.
 *
 * @param array $attributes Block attributes.
 *
 * @return string $block_content
 */
function render_conditional_content_block( $attributes, $block_content ) {

	if ( class_exists( '\Greyd\Extensions\Cookie_Handler' ) ) {
		$url_values = \Greyd\Extensions\Cookie_Handler::get_url_values();
		$cookie_values = \Greyd\Extensions\Cookie_Handler::get_cookie_values();
		$all_url_values = \Greyd\Extensions\Cookie_Handler::get_all_url_values();
		$all_cookie_values = \Greyd\Extensions\Cookie_Handler::get_all_cookie_values();
	} else if ( class_exists('\url_handler') ) {
		$url_values = \url_handler::get_url_values();
		$cookie_values = \url_handler::get_cookie_values();
		$all_url_values = \url_handler::get_all_url_values();
		$all_cookie_values = \url_handler::get_all_cookie_values();
	}

	// get current values
	$values = array(
		'urlparam' => $url_values,
		'cookie'   => $cookie_values,
		'time'     => intval( current_time( 'His' ) ), // get time as intval (13:15h is 131500, 07:30h is 73000)
		'userrole' => is_user_logged_in() ? wp_get_current_user()->roles : array( 'none' ),
	);

	// atts
	// debug( $attributes );
	$operator          = isset( $attributes['operator'] ) ? esc_attr( $attributes['operator'] ) : 'OR';
	$is_displayed      = $operator === 'AND';
	$conditions        = isset( $attributes['conditions'] ) ? (array) $attributes['conditions'] : array();
	$debug             = isset( $attributes['debug'] ) ? (bool) $attributes['debug'] : false;
	$debug_content     = '';
	$default_condition = array(
		'type'     => 'urlparam',
		'operator' => 'is',
		'value'    => '',
		'detail'   => '',
		'custom'   => '',
	);

	/**
	 * @since 2.6.0 Make conditions live.
	 * * For now this only supports time conditions.
	 */
	$live_javascript = '';

	/**
	 * @since 2.14.0 avoid caching.
	 */
	$harden_conditions = [];

	$id = uniqid( 'condition-'); // used for live conditions and to avoid caching

	// init debug mode
	if ( $debug ) {
		$debug_content = "<====== Conditional Content DEBUG Mode ======<br>	ID: ".$id."<br>	Operator <strong>$operator:</strong> " . (
			$operator === 'AND' ?
			'as soon as 1 condition fails, nothing is rendered.' :
			'as soon as 1 condition matches, the content is rendered.'
		) . '<br>	Conditions:';
	}

	// get matches
	foreach ( $conditions as $condition ) {

		$is            = null;
		$condition     = array_merge( $default_condition, $condition );
		$type          = $condition['type'];
		$cond_operator = $condition['operator'];
		$should        = $condition['value'];
		$detail        = isset( $condition['detail'] ) ? esc_attr( $condition['detail'] ) : '';

		if ( $type === 'urlparam' || $type === 'cookie' ) {

			if ( $detail !== 'custom' ) {
				$is = isset( $values[ $type ][ $detail ] ) ? esc_attr( $values[ $type ][ $detail ] ) : null;
			} else {
				$detail     = $condition['custom'];
				$_values = $type === 'urlparam' ? $all_url_values : $all_cookie_values;
				$is      = isset( $_values[ $detail ] ) ? esc_attr( $_values[ $detail ] ) : null;

				/**
				 * Support for session cookies.
				 * @since 1.7.6
				 */
				if ( $is === null && $type === 'cookie' ) {
					if ( isset( $_SESSION ) && isset( $_SESSION[ $detail ] ) ) {
						$is = $_SESSION[ $detail ];
					} else if ( isset( $_COOKIE ) && isset( $_COOKIE[ $detail ] ) ) {
						$is = $_COOKIE[ $detail ];
					}
				}
			}
			// harden
			if ( isset($attributes['harden']) && $attributes['harden'] ) {
				$harden_conditions[] = array_merge( $condition, array( "id" => $id, "is" => $is, "mainoperator" => $operator ) );
			}
		}
		elseif ( $type === 'localStorage' ) {
			$harden_conditions[] = array_merge( $condition, array( "id" => $id, "is" => $is, "mainoperator" => $operator ) );
		}
		elseif ( $type === 'userrole' ) {
			$is = $values[ $type ];
		}
		elseif ( $type === 'time' ) {
			$should = $should === 'custom' ? $condition['custom'] : $should;
			$is     = $values[ $type ];

			/**
			 * @since 2.6.0 Live conditions.
			 */
			if ( isset($attributes['live']) && $attributes['live'] ) {
				foreach ( (array) explode( ',', $should ) as $time ) {
					list( $start_time, $end_time ) = conditional_content_get_start_and_end_time( $time );

					$seconds_until_start = ( intval( $start_time ) - $is ) * 60 / 100;
					$seconds_until_end   = ( intval( $end_time ) - $is ) * 60 / 100;

					// is a value is negative, the time is in the past, we use the next day
					if ( $seconds_until_start < 0 ) {
						$seconds_until_start += 24 * 60 * 60;
					}
					if ( $seconds_until_end < 0 ) {
						$seconds_until_end += 24 * 60 * 60;
					}

					// Show the block after the start time, but only if the logic is OR.
					// Otherwise we would show it even though the other conditions are met
					if ( count( $conditions ) === 1 || $operator === 'OR' ) {

						if ( ! $id ) $id = wp_unique_id( 'condition-');

						$live_javascript .= "setTimeout(function() { document.getElementById('$id').style.display = 'block'; }, $seconds_until_start * 1000);";

					}

					// Hide the block after the end time, but only if the logic is AND.
					// Otherwise we would hide it even though the other conditions are not met
					if ( count( $conditions ) === 1 || $operator === 'AND' ) {
						
						if ( ! $id ) $id = wp_unique_id( 'condition-');

						$live_javascript .= "setTimeout(function() { document.getElementById('$id').style.display = 'none'; }, $seconds_until_end * 1000);";
					}
				}
			}
		}
		elseif ( $type === 'search' ) {
			$should = empty( $cond_operator ) ? 'not_empty' : $cond_operator;
			$is = 0;
			if ( isset($attributes['queryTags']['post-count']) ) {
				// in query loop
				$is = intval(strip_tags($attributes['queryTags']['post-count']));
			}
			else if ( is_search() || is_archive() ) {
				// in search or archive
				global $wp_query;
				$is = $wp_query->found_posts;
			}
		}
		elseif ( $type === 'taxonomy' ) {

			global $post;
			$taxonomy = $condition['custom'];

			if ( is_object($post) && isset($post->global_status) && isset($post->post_taxonomy_terms) ) {

				$post_taxonomy_terms = $post->post_taxonomy_terms;

				if ( is_object( $post_taxonomy_terms ) ) {
					$post_taxonomy_terms = (array) $post_taxonomy_terms;
				}

				if ( is_array($post_taxonomy_terms) && isset($post_taxonomy_terms[$taxonomy]) ) {
					if ( $cond_operator == 'empty' || $cond_operator == 'not_empty' ) {
						$is = !empty($post_taxonomy_terms[$taxonomy]) ? 'not_empty' : '';
					}
					else {
						foreach ( $post_taxonomy_terms[$taxonomy] as $term ) {
							if ( in_array($term->slug, $should) ) {
								$is = $should;
								break;
							} else {
								$is =  '';
							}
						}
					}
				}
			} else {
				if ( $cond_operator == 'empty' || $cond_operator == 'not_empty' ) {
					$is = has_term( array(), $taxonomy, $post ) ? 'not_empty' : '';
				}
				else $is = has_term( $should, $taxonomy, $post ) ? $should : '';
			}
		}
		elseif ( $type === 'archivetax' ) {

			$taxonomy = $condition['custom'];
			$is = is_tax( $taxonomy, $should ) ? $should : '';

			if ( empty($is) ) {
				$queried_object = get_queried_object();
				if ( $queried_object && is_a( $queried_object, 'WP_Term' ) && isset( $queried_object->slug ) ) {
					$is = $queried_object->slug;
				}
			}
		}
		elseif ( $type === 'posttype' ) {

			global $post;
			$is = get_post_type( $post );
		}
		elseif ( $type === 'postIndex' ) {

			global $current_post_index;
			if ( $current_post_index ) {
				$is = strval( $current_post_index );
			} else {
				$is = '0';
			}
		}
		elseif ( $type === 'field' ) {
			global $post;

			$fields = false;
			/**
			 * If the posttype is the same as the detail, get the dynamic values.
			 */
			if ( get_post_type( $post ) == $detail ) {
				$fields = \Greyd\Posttypes\Posttype_Helper::get_dynamic_values( $post, array(
					'raw_date' => true
				) );
			}
			/**
			 * If the posttype is not the same as the user-selected posttype, check if it is a
			 * "global dynamic tag".
			 * If yes, we get the latest post of this posttype and get the dynamic values.
			 */
			else {
				$posttype = \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttype_by_slug( $detail );
				if (
					isset($posttype['arguments'])
					&& isset($posttype['arguments']['is_global_dynamic_tag'])
					&& $posttype['arguments']['is_global_dynamic_tag']
				) {
					$recent_posts = get_posts( array(
						'post_type'   => $detail,
						'numberposts' => 1,
						'fields'      => 'ids'
					) );
					if ( $recent_posts && isset($recent_posts[0]) ) {
						$fields = \Greyd\Posttypes\Posttype_Helper::get_dynamic_values( $recent_posts[0], array(
							'raw_date' => true
						) );
					}
				}
			}
			/**
			 * ...otherwise there are no values and the condition is not met.
			 */
			if ( $fields && is_array($fields) && isset($fields[$condition['custom']]) ) {
				$is = $fields[$condition['custom']];
			}
		}
		elseif ( $type === 'post' ) {

			global $post;
			$is = $post->ID;
		}
		elseif ( $type === 'location' ) {

			$is = array();
			if ( is_front_page() ) $is[] = 'frontPage';
			if ( is_home() ) $is[] = 'postsPage';
			if ( is_singular() ) $is[] = 'singular';
			if ( is_archive() ) $is[] = 'archive';
			if ( is_search() ) $is[] = 'search';
			if ( is_404() ) $is[] = '404';
		}
		elseif ( $type === 'custom' ) {

			$detail = isset( $condition['detail'] ) ? esc_attr( $condition['detail'] ) : '';

			if ( ! empty( $detail ) && $detail !== 'default' ) {
				/**
				 * @since 2.6.0 Custom conditions.
				 */
				$is = apply_filters( $detail, $is, $condition );
			} else {
				/**
				 * @since 2.6.0 Custom conditions.
				 */
				$is = apply_filters( 'greyd_conditional_content_filter', $is, $condition );
			}
		}

		/**
		 * Filter condition "is" state.
		 * @filter 'greyd_conditional_content_condition_is'
		 * 
		 * @param any $is	Evaluated "is" state of the condition
		 * @param array $condition
		 */
		$is = apply_filters( 'greyd_conditional_content_condition_is', $is, $condition );
		
		$match = conditional_content_match_condition( $is, $should, $type, $cond_operator );

		if ( $debug ) {
			$debug_content .= sprintf(
				// cookie "utm_medium" (cookie) HAS_NOT "testvalue" ==> TRUE (because it is "empty")
				'<br>	- %s %s%s? ==> <strong>%s</strong> (because it is %s)',
				( !empty($detail) ) ? "$type \"{$detail}\"" : ( $type == 'taxonomy' ? "$type \"{$taxonomy}\"" : $type ), // cookie "utm_medium"
				strtoupper( $cond_operator ), // HAS_NOT
				$cond_operator === 'empty' || $cond_operator === 'not_empty' ? '' : ' "' . ( is_array( $should ) ? implode( ', ', $should ) : $should ) . '"', // eg. "testvalue"
				$match ? 'TRUE' : 'FALSE', // eg. "NO"
				empty( $is ) ? 'empty/not set' : '"' . ( is_array( $is ) ? implode( ', ', $is ) : esc_attr( $is ) ) . '"' // "empty"
			);
		}

		if ( $operator === 'OR' && $match ) {
			$is_displayed = true;
			break;
		} elseif ( $operator === 'AND' && ! $match ) {
			$is_displayed = false;
			break;
		}
	}

	/**
	 * @since 2.6.0 Render a wrapper with style="display:none;" and an id for live conditions.
	 */
	if ( !empty( $live_javascript ) ) {
		$block_content = sprintf(
			'<div id="%s" class="wp-block-greyd-conditional-content" %s>%s</div><script>%s</script>',
			$id,
			$is_displayed ? '' : 'style="display:none"',
			$block_content,
			$live_javascript
		);
	}
	/**
	 * @since 2.14.0 avoid caching: Render a wrapper with style="display:none;", id and data attribute.
	 */
	else if ( !empty( $harden_conditions ) ) {
		$block_content = sprintf(
			'<div id="%s" class="wp-block-greyd-conditional-content" %s %s>%s</div>',
			$id,
			'style="display:none"',
			"data-harden-conditions='".json_encode($harden_conditions)."'",
			$block_content
		);
	}
	else {
		// modify block content
		if ( ! $is_displayed ) {
			$block_content = '';
		}
	}

	// debug mode
	if ( ! empty( $debug_content ) ) {
		$block_content = '<pre>' . $debug_content . '<br>======></pre>' . $block_content;
	}

	return $block_content;
}


/**
 * Match condition.
 *
 * @param mixed  $is         Current value.
 * @param mixed  $should     What the value should be.
 * @param string $type      Type of the condition
 * @param mixed  $condition  Actual condition.
 *
 * @return bool
 */
function conditional_content_match_condition( $is, $should, $type = 'urlparam', $condition = 'is' ) {
	// USERROLES
	if ( $type === 'userrole' ) {
		$flipped = array_flip( $is ); // get userroles as keys of array
		if ( $condition === 'is' ) {
			return isset( $flipped[ $should ] );
		} elseif ( $condition === 'is_not' ) {
			return ! isset( $flipped[ $should ] );
		}
	}
	// TIME
	elseif ( $type === 'time' ) {
		foreach ( (array) explode( ',', $should ) as $time ) {

			list( $start_time, $end_time ) = conditional_content_get_start_and_end_time( $time );

			// debug( "$start_time <= $is" );
			// debug( intval($start_time) <= $is, true );
			// debug( "$is < $end_time" );
			// debug( $is < intval($end_time), true );

			if ( $condition === 'is' ) {
				if ( intval( $start_time ) <= $is && $is < intval( $end_time ) ) {
					return true;
				}
			} elseif ( $condition === 'is_not' ) {
				if ( $is < intval( $start_time ) || intval( $end_time ) <= $is ) {
					return true;
				}
			}
		}
	}
	// // SEARCH
	// elseif ( $type === 'search' ) {
	// 	return $should === 'not_empty' ? ( ! empty( $is ) && $is !== 'empty' ) : $is === $should;
	// }
	// TAXONOMY
	elseif ( $type === 'taxonomy' ) {
		return $condition === 'has_not' || $condition === 'empty' ? empty( $is ) : ! empty( $is );
	}
	// post
	elseif ( $type === 'post' ) {
		if ( $condition === 'is' ) {
			return in_array( $is, $should );
		}
		else if ( $condition === 'is_not' ) {
			return !in_array( $is, $should );
		}
	}
	// location
	elseif ( $type === 'location' ) {
		$result = $condition === 'is_not';
		foreach ( $is as $test ) {
			if ( $condition === 'is' && in_array( $test, $should ) ) {
				$result = true;
			}
			if ( $condition === 'is_not' && in_array( $test, $should ) ) {
				$result = false;
			}
		}
		return $result;
	}
	// default: COOKIE, URL_PARAMS & ARCHIVETAX and other...
	else {
		if ( $condition === 'is' ) {
			return $should === $is;
		} 
		else if ( $condition === 'is_not' ) {
			return $should !== $is;
		} 
		else if ( $condition === 'has' ) {
			return strpos( $is ?: '', strval( $should ) ) !== false;
		} 
		else if ( $condition === 'has_not' ) {
			return strpos( $is ?: '', strval( $should ) ) === false;
		} 
		else if ( $condition === 'empty' ) {
			return empty( $is );
		} 
		else if ( $condition === 'not_empty' ) {
			return ! empty( $is );
		}
		else if ( $condition === 'less' ) {
			return intval( $is ) < intval( $should );
		}
		else if ( $condition === 'greater' ) {
			return intval( $is ) > intval( $should );
		}
		else if ( $condition === 'odd' ) {
			return intval( $is ) % 2 === 1;
		}
		else if ( $condition === 'even' ) {
			return intval( $is ) % 2 === 0;
		}
		else if ( $condition === 'past' ) {
			$date_selected = strtotime( $is );
			$date_today    = strtotime( date( 'Y-m-d' ) );
			return $date_selected < $date_today;
		}
		else if ( $condition === 'future' ) {
			$date_selected = strtotime( $is );
			$date_today    = strtotime( date( 'Y-m-d' ) );
			return $date_selected > $date_today;
		}
		else if ( $condition === 'single' ) {
			return $is == 1;
		}
		else if ( $condition === 'multiple' ) {
			return $is > 1;
		}
	}
	return false;
}

/**
 * Get start and end time.
 *
 * @param string $time Input value (eg. "12-13", "13:15-14:30", "08:00-09:00")
 *
 * @return array       Start and end time as array (eg. ["120000","130000"], ["131500","143000"], ["080000","090000"])
 */
function conditional_content_get_start_and_end_time( $time ) {
	$time = explode( '-', $time ); // explode "start-end" to ["start","end"]
	if ( ! isset( $time[1] ) ) {
		$time[1] = intval( $time[0] ) + 1; // if no end isset -> use start+1 (12 -> 12-13)
	}

	// enable minutes
	foreach ( $time as $i => $t ) {
		$t = strval( $t );
		// convert hour to hour + minutes (13 -> 1300, 08 -> 800, 5 -> 500)
		if ( strpos( $t, ':' ) === false ) {
			$t = str_replace( '0', '', $t ) . '00';
		}
		// convert hour and minutes to string (13:15 -> 1315, 07:1 -> 0710, 9:4 -> 0940)
		else {
			$t = explode( ':', $t );
			if ( isset( $t[1] ) && ! empty( $t[1] ) ) {
				$t[1] = strlen( strval( $t[1] ) ) > 2 ? substr( $t[1], 0, 2 ) : strval( $t[1] ); // max length of 2
				$t[1] = strlen( $t[1] ) < 2 ? '0' + $t[1] : $t[1]; // min length of 2
			} else {
				$t[1] = '00';
			}
			$t = implode( '', $t );
		}
		$time[ $i ] = $t;
	}

	$start_time = $time[0] . '00';
	$end_time   = $time[1] . '00';

	return array( $start_time, $end_time );
}
