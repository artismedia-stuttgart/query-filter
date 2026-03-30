<?php
/**
 * Custom block that overwrites the core post-template block.
 * 
 * It enables features like custom pagination, arrows, animations, live search...
 */
namespace Greyd\Query;

// depends on greyd\blocks\helper
// ::implode_html_attributes
// ::compose_css
use \greyd\blocks\helper as Blocks_Helper;

use Greyd\Helper as Helper;
use Greyd\Settings as Settings;

if ( !defined( 'ABSPATH' ) ) exit;

class Post_Template {

	/**
	 * The Block ID. Defaults to post-results-[i].
	 * 
	 * @var string
	 */
	public $ID;

	/**
	 * The full block, including name and attributes.
	 * 
	 * @var array
	 */
	public $block;

	/**
	 * The block content about to be appended.
	 * 
	 * @var array
	 */
	public $block_content;

	/**
	 * The block without the attributes.
	 * 
	 * @var array
	 */
	public $inner_block;

	/**
	 * Block attributes.
	 * 
	 * @var array
	 */
	public $atts;

	/**
	 * Whether to inherit WP main query.
	 * 
	 * @var bool
	 */
	public $inherit = false;

	/**
	 * Holds the query.
	 * 
	 * @var WP_Query
	 */
	public $query;

	/**
	 * Whether we found any posts.
	 * 
	 * @var bool
	 */
	public $found_posts = 0;

	/**
	 * Holds the advanced search settings.
	 * 
	 * @var array
	 */
	public $search_settings;

	/**
	 * Whether this displays the live search query results.
	 * 
	 * @var bool
	 */
	public $is_live_search = false;

	/**
	 * Whether a live filter query is enabled.
	 * 
	 * @var bool
	 */
	public $is_live_filter = false;

	/**
	 * Whether to build responsive grid CSS.
	 * @since 2.13.0
	 * 
	 * @var bool
	 */
	public $change_responsive_item_count_with_css = false;

	/**
	 * The maximum posts per page.
	 * @since 2.13.0
	 * 
	 * @var int
	 */
	public $max_posts_per_page = 0;

	/**
	 * Wrapper attributes (class, data...)
	 * 
	 * @var array
	 */
	public $wrapper_atts = array(
		'class' => array(
			'greyd-posts-slider',
			'wp-block-post-template',
			// 'posts_wrapper' /** @deprecated since 2.0 */
		),
		'data' => array(
			'currentpage' => '1'
		),
		'style' => array()
	);

	/**
	 * Holds the query arguments.
	 * @since 2.7.0
	 * 
	 * @var array
	 */
	public $query_args = array();

	/**
	 * Whether the block is rendered initially or is updating from live-query.
	 * @since 2.15.0
	 * 
	 * @var bool
	 */
	public $update = false;

	/**
	 * Constructor
	 * 
	 * @param array  $block           The full block, including name and attributes.
	 * @param string $block_content   Already rendered content.
	 * 
	 * @return string HTML wrapper.
	 */
	public function __construct( $block, $block_content="" ) {

		/**
		 * Parse the block attributes.
		 */
		$atts = isset($block['attrs']) ? $block['attrs'] : array();
		$default_query_atts = array(
			'query' => array(
				'inherit' => false,
				'perPage' => 10,
				'postType' => 'post',
				'pages' => 1,
			)
		);
		if ( !isset($atts['query']) ) {
			$atts['query'] = $default_query_atts;
		} else if ( !isset($atts['query']['query']) ){
			$atts['query']['query'] = $default_query_atts['query'];
		} else {
			$atts['query']['query'] = wp_parse_args( $atts['query']['query'], $default_query_atts['query'] );
		}

		// basic class vars
		$this->atts             = $atts;
		$this->block_content    = strval( $block_content );
		$this->block            = $block;
		$this->inherit          = isset($this->atts['query']['query']['inherit']) && $this->atts['query']['query']['inherit'];
		$this->search_settings  = Settings::get_setting(array('site', 'advanced_search'));
		$this->is_live_search   = $this->inherit && is_search() && !is_archive() && $this->search_settings && $this->search_settings['live_search'] === 'true';
		$this->is_live_filter   = false;
		
		// live search & live filter cannot co-exist in the same query
		if ( !$this->is_live_search ) {

			/**
			 * @since 2.7.0 Check if live filter is enabled.
			 * @see Greyd\Query\Render for details on how this is defined
			 */
			$this->is_live_filter   = isset( $this->atts['liveFilter'] ) && $this->atts['liveFilter'];
			if ( ! $this->is_live_filter ) {

				/**
				 * live filter dropdown is enabled
				 * @deprecated since 2.7.0
				 */
				if ( isset($this->atts['filter']['enable']) && $this->atts['filter']['enable'] ) {
					$this->is_live_filter = true;
				}
				/**
				 * Responsive grid layout is enabled
				 * @since 2.7.0
				 */
				else {

					/**
					 * if pages count is 1, we don't need to check for responsive layout
					 * @since 1.23.0
					 */
					if ( isset($this->atts['query']['query']['pages']) && intval($this->atts['query']['query']['pages']) === 1 ) {
						$this->change_responsive_item_count_with_css = true;
					}
					else {
						// get layout attribute
						$layout = array();
	
						/** @since 2.7.0 */
						if ( isset($this->atts['layout']) ) {
							$layout = $this->atts['layout'];
						}
						/** @deprecated since 2.7.0 */
						else if ( isset($this->atts['query']['displayLayout']) ) {
							$layout = $this->atts['query']['displayLayout'];
						}
	
						// if items count is changed on different devices
						if (
							isset($layout['responsive'])
							&& (
								( isset($layout['responsive']['lg']) && isset($layout['responsive']['lg']['items']) )
								|| ( isset($layout['responsive']['md']) && isset($layout['responsive']['md']['items']) )
								|| ( isset($layout['responsive']['md']) && isset($layout['responsive']['md']['items']) )
							)
						) {
							// compare counts
							$items = array( $layout['items'] );
							if ( isset($layout['responsive']['lg']['items']) ) $items[] = $layout['responsive']['lg']['items'];
							if ( isset($layout['responsive']['md']['items']) ) $items[] = $layout['responsive']['md']['items'];
							if ( isset($layout['responsive']['sm']['items']) ) $items[] = $layout['responsive']['sm']['items'];
							if ( count(array_unique($items)) > 1 ) {
								// only if some are different
								$this->is_live_filter = true;
							}
						}
					}
				}
			}
		}

		// create an inner_block that has no attributes
		$this->inner_block = $block;
		if ( isset($this->inner_block['attrs']) ) {
			$this->inner_block['attrs'] = array();
		}

		// if attrs align is set, add it to the wrapper
		if ( isset($block['attrs']['align']) && !empty($block['attrs']['align']) ) {
			$this->wrapper_atts['class'][] = 'align'.$this->atts['align'];
		}
		if ( isset($block['attrs']['className']) && !empty($block['attrs']['className']) ) {
			$this->wrapper_atts['class'][] = $block['attrs']['className'];
		}

		// ID
		if ( isset($this->atts['query']['anchor']) && !empty($this->atts['query']['anchor']) ) {
			$this->ID = $this->atts['query']['anchor'];
		} else {
			$this->ID = uniqid( 'post-results-' );
		}

		// save unique IDs for elements that are re-rendered with live-filter
		if (isset($this->atts['pagination']['greydStyles']) && !isset($this->atts['pagination']['greydClass'])) {
			$this->atts['pagination']['greydClass'] = uniqid('query_');
		}
		if (isset($this->atts['arrows']['greydStyles']) && !isset($this->atts['arrows']['greydClass'])) {
			$this->atts['arrows']['greydClass'] = uniqid('query_');
		}
		if (isset($this->atts['sorting']['greydStyles']) && !isset($this->atts['sorting']['greydClass'])) {
			$this->atts['sorting']['greydClass'] = uniqid('query_');
		}
		if (!isset($this->atts['query']['anchor'])) {
			$this->atts['query']['anchor'] = $this->ID;
		}

		// debug($this);
	}

	/**
	 * =================================================================
	 *                          Render
	 * =================================================================
	 */

	/**
	 * Render the query block.
	 * 
	 * @param array $options    Optional options object.
	 * 
	 * @return string Rendered block output.
	 */
	public function render( $options=array() ) {

		/**
		 * We only wan't to re-render this block if the block content
		 * holds the original query results or is empty
		 */
		if ( '<ul' === substr($this->block_content, 0, 3) || empty($this->block_content) ) {
			$options = wp_parse_args(
				$options,
				array(
					'dynamic' => true
				)
			);
		} else {
			// otherwise we just return the already rendered content.
			return $this->block_content;
		}

		// don't render this block dynamically.
		if ( !$options['dynamic'] ) {
			return $this->block_content;
		}

		// determin if block is rendered initially or is updating from live-query.
		$this->update = isset($options['update']) && $options['update'];

		// decide whether we need to rebuild the query
		$rebuild_query = $this->inherit;
		if ( !$this->inherit && !empty($this->block_content) ) {
			// re-build the query only if we had some results in the original query.
			$rebuild_query = true;
		}

		if ( $rebuild_query  ) {

			$query_args  = $this->get_query_args();

			if ( $this->inherit && is_search() && !is_archive() ) {
				$query_args[ 'is_livesearch' ] = true;
			}

			$this->query = new \WP_Query( $query_args );

			$this->found_posts = $this->query->found_posts;
		}

		/**
		 * we need to get the wrapper attributes now otherwise the
		 * @var $this->change_responsive_item_count_with_css might not set to true
		 */
		$wrapper_atts = $this->get_wrapper_atts();

		/**
		 * If we haven't found any posts, the only case in which we're
		 * still rendering the wrapper and everything, is when this is
		 * a live search results wrapper.
		 * Because in this case we need the whole div structure to 
		 * render results when the search term is changed.
		 * 
		 * @since 1.3.0 also render when live_filter is enabled
		 */
		if ( $this->found_posts === 0 && !$this->is_live_search && !$this->is_live_filter ) {
			return $this->no_results_message();
		}

		ob_start();

		$this->render_filter_select( $options, 'top' );

		// wrapper
		echo "<div id='$this->ID' ".Blocks_Helper::implode_html_attributes( $wrapper_atts ).">";

		$this->render_sorting_dropdown( 'top' );

		$this->render_pagination( 'top' );

		$this->render_arrows( 'left' );

		if ( $this->found_posts ) {

			$postsPerPage    = $this->get_posts_per_page();
			$maxPostsPerPage = $this->get_max_posts_per_page();

			// pages wrapper
			echo "<div class='query-pages-wrapper" . ( Helper::is_greyd_classic() ? ' results_wrapper' : '' ) . "' data-ppp='{$postsPerPage}'>";
	
			// get the html tag for the wrapper (<div>, <ul>, <nav><ul>)
			$parent_html_tag = isset($this->atts['HTMLTags']['parent']) ? $this->atts['HTMLTags']['parent'] : 'div';
			$child_html_tag  = isset($this->atts['HTMLTags']['child']) ? $this->atts['HTMLTags']['child'] : 'article';
			$sub_tag         = '';

			if ( $parent_html_tag === 'nav,ul') {
				$parent_html_tag = 'nav';
				$sub_tag = 'ul';
			}

			// page
			$page_num = 1;
			echo "<{$parent_html_tag} class='query-page" . ( Helper::is_greyd_classic() ? ' result_wrapper' : '' ) . " is-current' data-page='{$page_num}'>";

			// enable highlighting of the search term in titles & excerpts
			if ( isset($query_args) && isset($query_args['s']) && !empty($query_args['s']) && strlen($query_args['s']) > 3 ) {
				global $query_search_term;
				$query_search_term = $query_args['s'];
			}

			if ( !empty($sub_tag) ) {
				echo "<{$sub_tag}>";
			}

			// cache the previous global post
			global $post;
			$previous_global_post = $post;

			// save post-index
			global $current_post_index;
			$previous_index = $current_post_index;

			$i = 0;
			while ( $this->query->have_posts() ) {

				// set the post
				$this->query->the_post();
				$post_id = get_the_ID();
				
				/**
				 * really exclude excluded posts.
				 * wpml does not properly exclude translations of sticky posts.
				 */
				if (
					isset($this->query->query['post__not_in']) && 
					in_array($post_id, $this->query->query['post__not_in'])
				) continue;
	
				/**
				 * There should be a pagebreak...
				 */
				if ( !$this->inherit && $i == ($postsPerPage * $page_num)) {

					/**
					 * @since 2.13.0 only when change_responsive_item_count_with_css is true,
					 * we do not break the page (we only render 1) but instead keep rendering
					 * items that are visible on other breakpoints.
					 */
					if ( $this->change_responsive_item_count_with_css ) {
						if ( $maxPostsPerPage > $postsPerPage && $i <= $maxPostsPerPage ) {
							// continue rendering the posts without pagebreak
						} else {
							// stop rendering the posts when we reached the max posts per page
							break;
						}
					}
					else {
						$page_num++;

						// end page
						if ( !empty($sub_tag) ) {
							echo "</{$sub_tag}>";
						}
						echo "</{$parent_html_tag}>";

						// new page
						echo "<{$parent_html_tag} class='query-page" . ( Helper::is_greyd_classic() ? ' result_wrapper' : '' ) . "' data-page='{$page_num}'>";
						if ( !empty($sub_tag) ) {
							echo "<{$sub_tag}>";
						}
					}
				}

				// count post-index
				$current_post_index = $i + 1;
	
				// render the tile
				echo sprintf(
					"<{$child_html_tag} class='query-post %s' %s>",
					esc_attr( implode( ' ', get_post_class('wp-block-post') ) ) . (Helper::is_greyd_classic() ? ' search_result' : ''),
					implode( array(
						"data-post='{$post_id}'",
						"data-title='".preg_replace('/[^a-z0-9\s]/', '', strtolower(get_the_title()))."'",
						"data-date='".get_post()->post_date."'",
						"data-postviews='".intval(get_post_meta($post_id, 'postviews_count', true))."'",
						"data-index='".$current_post_index."'"
					) )
				);

				// render the block
				$data = array(
					'postType' => get_post_type(),
					'postId'   => $post_id,
				);

				/**
				 * @since 2.6.0    Only render the inner blocks.
				 * 
				 * Rendering the block directly leads to issues when adding core support classes through
				 * functions like 'wp_render_layout_support_flag'. The classes of the post template,
				 * like 'wp-post-template-is-layout-grid', woukd be added to the first inner block.
				 */
				if ( isset($this->block['innerBlocks']) && !empty($this->block['innerBlocks']) ) {
					foreach ($this->block['innerBlocks'] as $inner_block) {
						// debug($inner_block);
						echo (new \WP_Block($inner_block, $data))->render();
					}
				} else {
					/**
					 * Fallback if the inner blocks are not set. Make sure to set 'dynamic' to false,
					 * otherwise this function will be called recursively, leading to an infinite loop.
					 */
					 echo (new \WP_Block($this->block, $data))->render(array(
						'dynamic' => false
					));
				}
	
				echo "</{$child_html_tag}>";
	
				$i++;
			}

			// reset the global post to the previous global post
			wp_reset_postdata();

			// reset post-index (may have changed by child loops)
			$current_post_index = $previous_index;

			// reset the post to the previous global post
			global $post;
			if (
				$post
				&& is_object( $post )
				&& isset( $post->ID )
				&& $previous_global_post
				&& is_object( $previous_global_post )
				&& isset( $previous_global_post->ID )
				&& $post->ID !== $previous_global_post->ID
			) {
				$post = $previous_global_post;
			}

			if ( isset($query_search_term) ) {
				$query_search_term = null;
			}

			// close sub tag
			if ( !empty($sub_tag) ) {
				echo "</{$sub_tag}>";
			}

			// close page
			echo "</{$parent_html_tag}>";

			// close pages wrapper
			echo "</div>";
		}
		else if ( $this->is_live_filter ) {
			echo $this->no_results_message();
		}

		$this->render_arrows( 'right' );

		$this->render_sorting_dropdown( 'bottom' );

		$this->render_pagination( 'bottom' );

		// close wrapper
		echo "</div>";

		echo $this->loading_spinner();

		$this->render_live_search_elements();
		
		$this->enqueue_responsive_stylesheet();

		$this->render_filter_select( $options, 'bottom' );

		$content = ob_get_contents();
		ob_end_clean();

		/**
		 * Filter the content similar to render_block filter.
		 * 
		 * @since 2.17.5
		 * 
		 * @param string $content The content of the post template.
		 * @param array  $block   The block.
		 * 
		 * @return string The filtered content.
		 */
		$content = apply_filters( 'greyd_render_block_post_template', $content, $this->block );

		return ltrim( $content );
	}

	/**
	 * Render the live filter multiselect.
	 * 
	 * @param array	 $opts	render options.
	 * @param string $pos (top|bottom)
	 * 
	 * @return string HTML
	 */
	public function render_filter_select( $opts, $pos ) {

		if ( !$this->is_live_filter || isset($opts['update']) ) return;

		$atts = isset($this->atts['filter']) ? $this->atts['filter'] : array();
		$enabled = isset($atts['enable']) ? $atts['enable'] == true : false;
		$position = isset($atts['position']) ? $atts['position'] : 'top';

		// render if enabled position matches
		if ( !$enabled || $position !== $pos ) return;

		// styles
		// debug($atts);
		if ( isset($atts['inputStyle']) && $atts['inputStyle'] === 'sec') $atts['inputStyle'] = 'is-style-sec';
		$greydClass   = isset($atts['greydClass']) ? $atts['greydClass'] : uniqid('filter_');
		$greydStyles  = isset($atts['greydStyles']) ? (array) $atts['greydStyles'] : array();
		$customStyles = isset($atts['custom']) && $atts['custom'] && isset($atts['customStyles']) ? (array) $atts['customStyles'] : array();

		// get filter options
		$options = array();
		$selected = array();
		$taxes = Helper::get_all_taxonomies($this->atts['query']['query']['postType']);
		$showTitles = isset($atts['showTaxTitle']) && $atts['showTaxTitle'] == true ? true : false;

		/**
		 * Filter the taxonomies that are used for the filter.
		 * 
		 * @since 1.3.0
		 * 
		 * @param array $taxes              The taxonomies.
		 * @param Post_Template $query_class  Reference of this class
		 * 
		 * @return array
		 */
		$taxes = apply_filters('greyd_query_live_filter_taxonomies', $taxes, $this);
		// debug($taxes);

		foreach ($taxes as $i => $tax) {

			$terms = Helper::get_all_terms( $tax->slug, array(
				'hide_empty' => true
			) );
			
			foreach ($terms as $j => $term) {

				if ( $showTitles ) {
					$options[ $tax->slug.'|'.$term->id ] = $tax->title.': '.$term->title;
				} else {
					$options[ $tax->slug.'|'.$term->id ] = $term->title;
				}

				/**
				 * Check taxQuery in arguments for selected item(s)
				 */
				if (
					isset($this->atts['query']['query']['taxQuery'][$tax->slug])
					&& in_array($term->id, $this->atts['query']['query']['taxQuery'][$tax->slug])
				) {
					array_push($selected, $tax->slug.'|'.$term->id);
				}
				/**
				 * Also check tax_query query vars for selected items
				 * @since 2.10.0
				 */
				else if ( isset($this->query->query_vars['tax_query']) ) {
					$tax_query = $this->query->query_vars['tax_query'];
					$tax_query = array_filter(
						$tax_query, 
						function ($item) use ($tax) {
							return $item['taxonomy'] === $tax->slug;
						}
					);
					if ( !empty($tax_query) ) {
						$tax_query = array_shift($tax_query);
						if ( isset($tax_query['terms']) && in_array($term->id, $tax_query['terms']) ) {
							array_push($selected, $tax->slug.'|'.$term->id);
						}
					}
				}
			}
		}

		/**
		 * Filter the selected terms for the live filter.
		 * 
		 * @since 2.3.0
		 * 
		 * @param array $selected           The selected terms.
		 * @param Query_Block $query_class  Reference of this class
		 * 
		 * @return array
		 */
		$selected = apply_filters('greyd_query_live_filter_selected_terms', $selected, $this);

		// render multiselect
		// if ($position == 'bottom') echo $this->loading_spinner();
		echo "<div class='pgn filter ".$greydClass."'>";
			echo Helper::render_multiselect(
				$this->ID."-taxselect",
				$options,
				array( 
					'value' => implode(',', $selected),
					'class' => isset($atts['inputStyle']) ? $atts['inputStyle'] : '',
					'placeholder' => isset($atts['empty']) && !empty($atts['empty']) ? $atts['empty'] : __("Select filter", 'greyd_hub')
				)
			);
		echo "</div>";
		// if ($position == 'top') echo $this->loading_spinner();
		
		// enqueue styles
		self::enqueue_css(
			".{$greydClass}.filter.pgn",
			array( 'display' => 'flex', 'justify-content' => isset($atts['align']) ? $atts['align'] : 'end' )
		);
		self::enqueue_css(
			".{$greydClass}.filter.pgn .greyd_multiselect",
			$greydStyles
		);
		if ( !empty($customStyles) ) {
			self::enqueue_css(
				".{$greydClass}.filter.pgn .input, .{$greydClass}.filter.pgn .dropdown",
				$customStyles
			);
		}
	}

	/**
	 * Render the sorting dropdown.
	 * 
	 * @param string $pos (top|bottom)
	 * 
	 * @return string HTML
	 */
	public function render_sorting_dropdown( $pos ) {

		$atts    = isset($this->atts['sorting']) ? $this->atts['sorting'] : array();
		$enabled = isset($atts['enable']) ? $atts['enable'] == true : false;

		if ( !$enabled ) return;

		// render if position matches
		$position = isset($atts['position']) ? $atts['position'] : 'top';
		if ( $position !== $pos ) return;

		// styles
		// debug($atts);
		if ( isset($atts['inputStyle']) && $atts['inputStyle'] === 'sec') $atts['inputStyle'] = 'is-style-sec';
		$greydClass   = isset($atts['greydClass']) ? $atts['greydClass'] : uniqid('sorting_');
		$greydStyles  = isset($atts['greydStyles']) ? (array) $atts['greydStyles'] : array();
		if (isset($greydStyles['width']) && $greydStyles['width'] == "") unset($greydStyles['width']);
		$customStyles = isset($atts['custom']) && $atts['custom'] && isset($atts['customStyles']) ? (array) $atts['customStyles'] : array();
		if (isset($customStyles['background'])) $customStyles['box-shadow'] = 'none';

		// build options
		$selected_option = "";
		$options = array(
			'date_DESC'		=> __("Chronological (newest first)", 'greyd_hub'),
			'date_ASC'		=> __("Chronological (oldest first)", 'greyd_hub'),
			'title_ASC' 	=> __("Alphabetical (ascending)", 'greyd_hub'),
			'title_DESC' 	=> __("Alphabetical (descending)", 'greyd_hub')
		);
		if ( $this->search_settings && $this->search_settings['postviews_counter'] === 'true' ) {
			$options['views_DESC'] = __("Most read", 'greyd_hub');
		}
		if ( $this->search_settings && $this->search_settings['relevance'] === 'true' && is_search() ) {
			$options = array_merge(
				array( 'relevance_DESC' => __("Relevance", 'greyd_hub') ),
				$options
			);
		}

		// get current query atts
		$query_vars = $this->atts['query']['query'];
		$order   = isset($query_vars['order']) ? strtoupper($query_vars['order']) : 'DESC';
		$orderby = isset($query_vars['orderby']) ? strtolower($query_vars['orderby']) : ($this->search_settings && $this->search_settings['relevance'] === 'true' ? "relevance" : 'date');
		
		// selected option
		$selected_option = $orderby."_".$order;

		// adjust options when query is inherited
		if ( $this->inherit ) {

			$query_vars = \Greyd\Query\Render::get_main_query_vars();
		
			if ( isset($query_vars['paged']) ) unset( $query_vars['paged'] );

			// get current query atts
			$order   = isset($query_vars['order']) ? strtoupper($query_vars['order']) : 'DESC';
			$orderby = isset($query_vars['orderby']) ? strtolower($query_vars['orderby']) : ($this->search_settings && $this->search_settings['relevance'] === 'true' ? "relevance" : 'date');
			
			// selected option inherits the query
			$selected_option = $orderby."_".$order;

			// in archive & search templates we redirect when selecting a sorting option
			if ( ( is_archive() || is_search() ) && !$this->is_live_search ) {
				global $wp;
				
				$url = add_query_arg( $query_vars, preg_replace( "/page\/\d+/", "", home_url($wp->request) ) );
				$url = remove_query_arg(array('meta_query', 'orderby', 'order'), $url);

				foreach ($options as $value => $label) {
					if ( empty($value) ) continue;

					list($orderby, $order) = !empty($value) ? explode('_', $value) : array("","");

					$option_url = add_query_arg(array('orderby' => $orderby, 'order'   => $order), $url);

					if ( $value === "views_DESC" ) {
						$option_url = esc_url(add_query_arg(array(
							"orderby"  => 'meta_value_num',
							"order"    => 'DESC',
							'meta_query' => array(
								'relation' => 'OR',
								array(
									'key' => 'postviews_count', 
									'compare' => 'EXISTS',
								),
								array(
									'key' => 'postviews_count', 
									'compare' => 'NOT EXISTS',
								)
							),
						), $url));
					}

					// add the url to the option
					$options[ $value ] = array(
						'label' => $label,
						'value' => $option_url
					);
				}
			}
		}

		// wrapper
		echo sprintf(
			'<div %s >',
			Blocks_Helper::implode_html_attributes( array(
				'class' => array( 'pgn sorting', $greydClass, $position ),
				'style' => 'justify-content:' . ( isset($atts['align']) ? $atts['align'] : 'end' )
			) )
		);

		// select
		echo '<div class="custom-select '.(isset($atts['inputStyle']) ? $atts['inputStyle'] : '').'"><select autocomplete="off">';

		// options
		foreach( $options as $key => $option ) {

			if ( is_array($option) ) {
				$label = $option[ 'label' ];
				$value = $option[ 'value' ];
			} else {
				$label = $option;
				$value = str_replace('_', ' ', $key);
			}

			// use user input value
			if ( isset($atts['options'][$key]) && !empty($atts['options'][$key]) ) {
				$label = esc_attr( $atts['options'][$key] );
			}

			echo sprintf(
				'<option value="%s" %s>%s</option>',
				$value,
				$selected_option == $key ? 'selected="selected"' : '',
				$label
			);
		}

		echo '</select></div>';

		echo '</div>';
		
		// enqueue styles
		self::enqueue_css(
			".{$greydClass}.sorting.pgn > .custom-select",
			array_merge( array( 'width' => 'auto', 'margin' => array( 'bottom' => 'var(--FRMmargin)' ) ), $greydStyles )
		);
		self::enqueue_css(
			".{$greydClass}.sorting.pgn > .custom-select > div",
			$customStyles
		);
	}

	/**
	 * Render pagination arrows on the side.
	 * 
	 * @param string $pos (left|right)
	 * 
	 * @return string HTML
	 */
	public function render_arrows( $pos ) {

		if ( $this->is_live_search ) return;

		$atts    = isset($this->atts['arrows']) ? $this->atts['arrows'] : array();
		$enabled = isset($atts['enable']) ? $atts['enable'] == true : false;

		if ( !$enabled ) return;

		// only render when multiple pages
		if ( $this->get_initial_page_count() < 2 ) return;

		// styles
		$greydClass   = isset($atts['greydClass']) ? $atts['greydClass'] : uniqid('sorting_');
		$greydStyles  = isset($atts['greydStyles']) ? (array) $atts['greydStyles'] : array();

		// html atts
		$wrapper_atts = array(
			'class' => array($greydClass, 'pgn_arrows pgn arrows animate_fast', $pos)
		);

		// setup overlap
		if ( isset($atts['overlap']) && $atts['overlap'] ) {
			$wrapper_atts['class'][] = "overlap";
		}

		echo "<div ".Blocks_Helper::implode_html_attributes( $wrapper_atts ).">";
		
		$this->render_pagination_arrow( $pos, $atts );
		
		echo "</div>";

		/**
		 * Fix color inhertiation of custom pagination arrow colors.
		 * 
		 * @since 1.3.3
		 */
		if ( isset($greydStyles['color']) || isset($greydStyles['backgroundColor']) || isset($greydStyles['hover']) ) {

			$link_styles = array();

			if ( isset($greydStyles['color']) ) {
				$link_styles['color'] = $greydStyles['color'];
				unset( $greydStyles['color'] );
			}
			if ( isset($greydStyles['backgroundColor']) ) {
				$link_styles['backgroundColor'] = $greydStyles['backgroundColor'];
				unset( $greydStyles['backgroundColor'] );
			}
			if ( isset($greydStyles['hover']) ) {
				$link_styles['hover'] = $greydStyles['hover'];
				unset( $greydStyles['hover'] );
			}

			self::enqueue_css(
				'.pgn.arrows.'.$greydClass.' > a',
				$link_styles
			);
		}

		self::enqueue_css(
			'.pgn.arrows.'.$greydClass,
			$greydStyles
		);
	}

	/**
	 * Render the pagination bar.
	 * 
	 * @param string $pos (top|bottom)
	 * 
	 * @return string HTML
	 */
	public function render_pagination( $pos ) {

		if ( $this->is_live_search ) return;

		$atts    = isset($this->atts['pagination']) ? $this->atts['pagination'] : array();
		$enabled = isset($atts['enable']) ? $atts['enable'] == true : true;

		if ( !$enabled ) return;

		// render if position matches
		$position = isset($atts['position']) ? $atts['position'] : 'bottom';
		if ( $position !== $pos ) return;

		// only render when multiple pages
		if ( $this->get_initial_page_count() < 2 ) return;

		// styles
		$greydClass   = isset($atts['greydClass']) ? $atts['greydClass'] : uniqid('sorting_');
		$greydStyles  = isset($atts['greydStyles']) ? (array) $atts['greydStyles'] : array();

		$wrapper_atts = array(
			'class' => array($greydClass, 'pagination pgn numbers', $position)
		);

		// overlap
		if ( isset($atts['overlap']) && $atts['overlap'] ) {
			$wrapper_atts['class'][] = "overlap";
		}


		echo "<div ".Blocks_Helper::implode_html_attributes( $wrapper_atts ).">";

		$this->render_pagination_arrow( 'left', $atts );

		$this->render_pagination_numbers( $atts );

		$this->render_pagination_arrow( 'right', $atts );
		
		echo "</div>";

		// enqueue styles
		if ( !empty($greydStyles) ) {
			$filter_keys = array(
				'color' => '',
				'opacity' => '',
				'hover' => '',
				'active' => '',
			);
			// wrapper styles
			self::enqueue_css(
				'.pgn.numbers.'.$greydClass,
				array_merge(
					array_diff_key(
						$greydStyles,
						$filter_keys // except these keys
					),
					array(
						'--pgn-numbers-gutter' => isset($greydStyles['gutter']) ? $greydStyles['gutter'] : '',
					)
				)
			);
			// link styles
			self::enqueue_css(
				'.pgn.numbers.'.$greydClass.' .pgn_number',
				array_intersect_key(
					$greydStyles,
					$filter_keys // only those keys
				),
				array( 'pseudo_active' => '.pgn_current' )
			);
		}
	}

	/**
	 * Render pagination numbers inside the bar.
	 * 
	 * @param array $atts        $block['attrs']['pagination']
	 */
	public function render_pagination_numbers( $atts ) {

		$pgn_type = isset($atts['type']) ? esc_attr( $atts['type'] ) : 'icon';

		if ( empty($pgn_type) ) return;

		$numbers_atts = array();

		// get icons
		if ($pgn_type == 'icon') {
			$icon_normal = isset($atts['icon_normal']) ? $atts['icon_normal'] : 'icon_circle-empty';
			$icon_active = isset($atts['icon_active']) ? $atts['icon_active'] : 'icon_circle-slelected';
			$numbers_atts = array(
				'data' => array(
					'iconnormal' => $icon_normal,
					'iconactive' => $icon_active,
				)
			);
		}
		// get images
		else if ($pgn_type == 'image') {
			$src_normal = isset($atts['img_normal']) ? wp_get_attachment_url($atts['img_normal']) : false;
			$src_active = isset($atts['img_active']) ? wp_get_attachment_url($atts['img_active']) : false;
			$numbers_atts = array(
				'data' => array(
					'imgnormal' => $src_normal,
					'imgactive' => $src_active,
				)
			);
		}

		$numbers = array();
		$queryTags = $this->get_query_tags();
		$page_keys = $this->inherit ? $queryTags['page-keys'] : array();
		$maxnum = !$this->inherit && isset($atts['maxnum']) ? $atts['maxnum'] : -1;

		if ( $this->inherit ) {
			foreach( $queryTags['page-keys'] as $i => $page ) {
	
				$text       = strval($i);
				$num_atts   = array();
				$icon_class = 'pgn_number'.( $queryTags['page-num'] == $i ? ' pgn_current' : '' );
	
				if ( $pgn_type === 'text' ) {
					if ( isset($atts['text_type']) ) {
						$text = self::make_pagination_number_style($i, $atts['text_type']);
					}
				}
				else if ( $pgn_type == 'icon' ) {
					if (!isset($atts['icon_type']) || $atts['icon_type'] == 'icon') {
						if ( $queryTags['page-num'] == $i ) $icon_class .= ' '.$icon_active;
						else $icon_class .= ' '.$icon_normal;
						$text = "";
					}
					else if ($atts['icon_type'] == 'dots') {
						$text = "●";
						$numbers_atts = array();
					}
					else if ($atts['icon_type'] == 'blocks') {
						$text = "■";
						$numbers_atts = array();
					}
				}
				else if ( $pgn_type == 'image' ) {
					if ( $src_normal ) $text = "<img aria-hidden='true' src='{$src_normal}'>";
					if ( $queryTags['page-num'] == $i && $src_active ) $text = "<img aria-hidden='true' src='{$src_active}'>";
				}
	
				// add href if set
				if ( !empty($page) ) {
					$num_atts['href'] = $this->modifiy_query_url_params( $page );
					$num_atts['role'] = 'link';
				}
				// empty element means that's the current page or the '…' filler.
				else {
					$num_atts['aria-hidden'] = 'true';
					$num_atts['style']       = 'pointer-events:none;';
					
					if ( !empty($i) && !is_numeric($i) ) {
						$icon_class = '';
						$text = $i;
					}
				}
				
				$numbers[] = "<a ".Blocks_Helper::implode_html_attributes( $num_atts )."><span class='{$icon_class}'>{$text}</span></a>";
			}
		}
		else {
			for ( $i = 1; $i <= $queryTags['page-count']; $i++ ) {
	
				$text     = strval($i);
				$pagelink = $text;
				$num_atts = array(
					'class'         => '',
					'tabindex'      => '0',
					'role'          => 'button',
					'href'          => 'javascript:void(0)',
					'onclick'       => '(greyd && greyd?.query?.slider?.onPaginateClick(event,this,"'.$pagelink.'"))',
					'data-pagelink' => $pagelink,
					'aria-label'    => sprintf(__("Go to page %s", "greyd_hub"), $pagelink ),
				);
				$icon_class = 'pgn_number'.( $queryTags['page-num'] == $i ? ' pgn_current' : '' );
	
				if ( $pgn_type === 'text' ) {
					if ( isset($atts['text_type']) ) {
						$text = self::make_pagination_number_style($i, $atts['text_type']);
					}
				}
				else if ( $pgn_type == 'icon' ) {
					if (!isset($atts['icon_type']) || $atts['icon_type'] == 'icon') {
						if ( $queryTags['page-num'] == $i ) $icon_class .= ' '.$icon_active;
						else $icon_class .= ' '.$icon_normal;
						$text = "";
					}
					else if ($atts['icon_type'] == 'dots') {
						$text = "●";
						$numbers_atts = array();
						/**
						 * @since 2.4.0 make the fonts monospace because the dots are not supported by all fonts.
						 */
						$num_atts['style'] = 'font-family:monospace;';
					}
					else if ($atts['icon_type'] == 'blocks') {
						$text = "■";
						$numbers_atts = array();
						/**
						 * @since 2.4.0 make the fonts monospace because the dots are not supported by all fonts.
						 */
						$num_atts['style'] = 'font-family:monospace;';
					}
				}
				else if ( $pgn_type == 'image' ) {
					if ( $src_normal ) $text = "<img aria-hidden='true' src='{$src_normal}'>";
					if ( $queryTags['page-num'] == $i && $src_active ) $text = "<img aria-hidden='true' src='{$src_active}'>";
				}

				// shorten pagination with '...'
				if ( $maxnum > -1 ) {
					$page = $i;
					$classes = array();
					if ( $maxnum === 0 && $page == $queryTags['page-num'] ) {
						if ( $page == 1 ) {
							$classes[] = 'dots-after';
						}
						if ( $page == $queryTags['page-count'] ) {
							$classes[] = 'dots-before';
						}
					}
					if ( $page > 1 && $page < $queryTags['page-count'] ) {
						if ( $page < $queryTags['page-num']-$maxnum || $page > $queryTags['page-num']+$maxnum ) {
							$classes[] = 'hidden';
							$num_atts['aria-hidden'] = 'true';
						}
						if ( $page > 2 && $page == $queryTags['page-num']-$maxnum ) {
							$classes[] = 'dots-before';
						}
						if ( $page < $queryTags['page-count']-1 && $page == $queryTags['page-num']+$maxnum ) {
							$classes[] = 'dots-after';
						}
					}
					$num_atts['class'] = implode(' ', $classes);
				}
				
				$numbers[] = "<a ".Blocks_Helper::implode_html_attributes( $num_atts )."><span class='{$icon_class}'>{$text}</span></a>";
			}

			if ($maxnum > -1) {
				$numbers_atts["data"]["maxnum"] = $maxnum;
			}
		}

		$numbers = apply_filters( 'greyd_pagination_numbers', $numbers, $this->query, $this->block );

		if ( count( $numbers ) ) {
			echo "<span class='pgn_numbers' ".Blocks_Helper::implode_html_attributes( $numbers_atts ).">".implode('', $numbers)."</span>";
		}
	}
	
	/**
	 * Render a pagination number.
	 * @param int $number
	 * @param string $numbes_style	('A' 'a' '1.' '01' '01.')
	 * @return string
	 */
	public static function make_pagination_number_style($number, $numbers_style) {
		if ($numbers_style === 'A' || $numbers_style === 'a') {
			$result = '';
			for ($j = 1; $number >= 0 && $j < 10; $j++) {
				$result = chr(0x40 + ($number % pow(26, $j) / pow(26, $j - 1))) . $result;
				$number -= pow(26, $j);
			}
			if ($numbers_style == 'a') $result = strtolower($result);
		}
		else {
			$result = $number;
			if (($numbers_style === '01' || $numbers_style === '01.') && $result < 10) $result = '0'.$result;
			if ($numbers_style === '1.' || $numbers_style === '01.') $result .= '.';
		}
		return $result;
	}

	/**
	 * Render a single pagination arrow.
	 * 
	 * @param string $direction  (previous|next)
	 * @param array $atts        $block['attrs']['pagination']
	 */
	public function render_pagination_arrow( $pos, $atts ) {

		$arrow_type = 'icon';

		// classic pagination arrows use the attribute 'arrows_type'
		if ( isset($atts['arrows_type']) ) {
			$arrow_type = esc_attr( $atts['arrows_type'] );
		}
		// side arrows always have 'enable' set & use the attribute 'type'
		else if ( isset($atts['enable']) && isset($atts['type']) ) {
			$arrow_type = esc_attr( $atts['type'] );
		}

		if ( empty($arrow_type) ) return;

		$direction  = $pos === 'right' ? 'next' : 'previous';
		$queryTags  = $this->get_query_tags();
		$inner_html = "";
		$pagelink   = substr($direction, 0, 4);
		$arrow_atts = array(
			'tabindex'      => '0',
			'role'          => 'button',
			'href'          => 'javascript:void(0)',
			'onclick'       => '(greyd && greyd?.query?.slider?.onPaginateClick(event,this,"'.$pagelink.'"))',
			'class'         => array( 'pgn_number pgn_'.$direction ),
			'data-pagelink' => $pagelink,
			'aria-label'    => $direction === 'next' ? __("Go to next page", "greyd_hub") : __("Go to previous page", "greyd_hub")
		);

		// add href tag if in query
		if ( $this->inherit ) {
			$href = isset($queryTags[ 'page-'.$direction ]) ? $queryTags[ 'page-'.$direction ] : null;
			if ( !empty($href) ) {
				$arrow_atts['href'] = $this->modifiy_query_url_params( $href );
				$arrow_atts['role'] = 'link';
			}
		}

		// render as images
		if ( $arrow_type == 'image' ) {
			$src = isset($atts[ 'img_'.$direction ]) ? wp_get_attachment_url($atts[ 'img_'.$direction ]) : false;
			$inner_html = $src ? "<img aria-hidden='true' src='{$src}'>" : "";
		}
		// render as icons
		else {
			$arrow_icon_class = isset($atts[ 'icon_'.$direction ]) ? $atts[ 'icon_'.$direction ] : 'arrow_'.$pos;
			$arrow_atts['class'][] = $arrow_icon_class;
		}

		// hide if on current page
		if (
			( $pos === 'left' && $queryTags['page-num'] < 2 ) ||
			( $pos === 'right' && $queryTags['page-num'] === $queryTags['page-count'] ) ||
			( isset($href) && empty($href) ) // hide the arrow when the 'next|prev' link is empty
		) {
			$arrow_atts['class'][] = 'pgn_current';

			// hide completely if on archive page
			if ( $this->inherit ) {
				$arrow_atts = array();
			}
		}
		
		echo "<a ".Blocks_Helper::implode_html_attributes($arrow_atts).">{$inner_html}</a>";
	}

	/**
	 * Render the loader, spinner & load more button.
	 * 
	 * @return string HTML
	 */
	public function render_live_search_elements() {

		if ( !$this->is_live_search ) return;

		// render the no result message
		echo $this->no_results_message();

		$loader_atts    = isset($this->atts['loader']) ? $this->atts['loader'] : null;
		$wrapper_class  = "load_more_wrapper";
		$button_class   = "button";

		if ( $loader_atts ) {
			if ( isset($loader_atts['style']) && !empty($loader_atts['style']) ) {
				$button_class = $loader_atts['style'];
			}
			if ( isset($loader_atts['size']) && !empty($loader_atts['size']) ) {
				$button_class .= " " . $loader_atts['size'];
			}
			if ( isset($loader_atts['greydClass']) && !empty($loader_atts['greydClass']) ) {
				$wrapper_class .= " " . $loader_atts['greydClass'];

				if ( isset($loader_atts['greydStyles']) && !empty($loader_atts['greydStyles']) ) {
					self::enqueue_css( ".load_more_wrapper.".$loader_atts['greydClass'], $loader_atts['greydStyles'] );
				}
			}
		}

		echo "<div class='$wrapper_class' ".( $this->found_posts > $this->get_query_tags()['posts-per-page'] ? "" : "style='display:none'" ).">
			<div class='load_more $button_class'>".__("Load more", "greyd_hub")."</div>
			{$this->loading_spinner()}
		</div>";
	}

	/**
	 * Add responsive stylesheet for colmns and gap.
	 */
	public function enqueue_responsive_stylesheet() {

		// debug($this->atts);
		
		// get layout
		$layout = false;
		// new layout attribute
		if ( isset($this->atts['layout']) ) {
			$layout = $this->atts['layout'];
		}
		// support deprecated displayLayout
		if (
			!$layout &&
			isset($this->atts['query']['displayLayout']) &&
			isset($this->atts['query']['displayLayout']['type']) &&
			$this->atts['query']['displayLayout']['type'] == 'flex'
		) {
			$layout = $this->atts['query']['displayLayout'];
			$layout['type'] = 'grid';
			// convert 'columns' to 'columnCount'
			if ( isset( $layout['columns'] ) ) {
				$layout['columnCount'] = $layout['columns'];
				unset( $layout['columns'] );
			}
			if ( isset($layout['responsive']) ) {
				foreach ( $layout['responsive'] as $bp => $responsive ) {
					if (isset($responsive['columns'])) {
						$layout['responsive'][$bp]['columnCount'] = $responsive['columns'];
						unset( $layout['responsive'][$bp]['columns'] );
					}
				}
			}
		}

		// debug($layout);
		if ( !$layout ) return;

		// vars
		$style = "";
		$wrapper = "#".$this->ID.".greyd-posts-slider";

		if ( isset($this->atts['HTMLTags']['parent']) && $this->atts['HTMLTags']['parent'] === "nav,ul" ) {
			$selector = $wrapper." > .query-pages-wrapper > .query-page > ul";
		} else {
			$selector = $wrapper." >  .query-pages-wrapper > .query-page";
		}


		// columns
		if ( isset($layout['type']) && $layout['type'] == 'grid' ) {

			/**
			 * @since wp 6.6 support minimumColumnWidth
			 */
			if ( isset( $layout['minimumColumnWidth'] ) && $layout['minimumColumnWidth'] && ! $layout['columnCount'] ) {
				$style .= $selector." { grid-template-columns: repeat(auto-fill, minmax(min(".$layout['minimumColumnWidth'].", 100%), 1fr)); container-type: inline-size; } ";
			}
			else {
				$style .= $selector." { grid-template-columns: repeat(".($layout['columnCount'] ?? 3).", minmax(0, 1fr)); } ";
			}
		}
		// gap
		if ( isset($layout['gap']) ) {
			$style .= $wrapper." { --query-block-gap: ".Blocks_Helper::get_spacing_preset_css_var($layout['gap'])." } ";
		}

		// responsive
		if ( isset($layout['responsive']) ) {
			$sizes     = \greyd\blocks\layout\Enqueue::get_breakpoints();
			$size_keys = array( 'lg', 'md', 'sm' );
			foreach ( $size_keys as $key => $size ) {
				$css = "";
				// columns
				if ( isset($layout['type']) && $layout['type'] == 'grid' && isset($layout['responsive'][$size]['columnCount']) ) {
					$css .= $selector." { grid-template-columns: repeat(".$layout['responsive'][$size]['columnCount'].", minmax(0, 1fr)); } ";
				}
				// gap
				if ( isset($layout['responsive'][$size]['gap']) ) {
					$css .= $wrapper." { --query-block-gap: ".Blocks_Helper::get_spacing_preset_css_var($layout['responsive'][$size]['gap'])." } ";
				}

				// build breakpoint css
				if ( $css != "" ) {
					$style .= "@media (max-width: ".$sizes[$size]."px) { ".$css." } ";
				}

				// hide items on breakpoints using css
				if ( $this->change_responsive_item_count_with_css ) {

					// hide each direct nth-child larger than the column count
					$items_on_this_breakpoint = isset( $layout['responsive'][$size]['items'] ) ? intval($layout['responsive'][$size]['items']) : 0;

					if ( $items_on_this_breakpoint ) {
						
						$next_size_key = isset( $size_keys[ $key + 1 ] ) ? $size_keys[ $key + 1 ] : null;
						$min_width     = $next_size_key && isset( $sizes[ $next_size_key ] ) ? $sizes[ $next_size_key ] : '0';
						$style .= "@media (max-width: ".$sizes[$size]."px) and (min-width: ".$min_width."px) { ".$selector." > *:nth-child(n+".($items_on_this_breakpoint + 1).") { display: none; } } ";
					}
				}
			}

			if ( $this->change_responsive_item_count_with_css ) {

				// if the posts per page is set to a lower value than the max posts per page
				// hide the rest of the posts on the desktop breakpoint
				$postsPerPage    = $this->get_posts_per_page();
				$maxPostsPerPage = $this->get_max_posts_per_page();
				if ( $postsPerPage < $maxPostsPerPage ) {
					$style .= "@media (min-width: ".$sizes['lg']."px) { ".$selector." > *:nth-child(n+".($postsPerPage + 1).") { display: none; } } ";
				}
			}

		}

		// enqueue
		if ( $style != "" ) {
			Helper::add_custom_style( $style );
		}
	}

	/**
	 * =================================================================
	 *                          Utils
	 * =================================================================
	 */

	/**
	 * Compose & enqueue styles.
	 * 
	 * @param string $selector          CSS class or ID, usually a greydClass (including prefix).
	 * @param array $styles             All default, hover & responsive styles.
	 * @param array $atts               Additional attributes (deprecated: @param bool $important)
	 */
	public static function enqueue_css($selector, $styles, $atts=array()) {
		$finalCSS = Blocks_Helper::compose_css( array( '' => $styles ), $selector, $atts );
		Helper::add_custom_style($finalCSS);
	}

	/**
	 * Get query arguments
	 * 
	 * @return array
	 */
	public function get_query_args() {

		if ( ! empty( $this->query_args ) ) {
			return $this->query_args;
		}

		// before we build the query, we need to adjust some attributes because
		// when set to inherit, the block attributes do not match the actual
		// query attributes, so we overwrite them, before they are then used
		// inside the build_query() method.
		$paged = 1;
		if (
			$this->inherit ||
			(
				// if no pagination or arrows are set, only one page is rendered and can be paginated with the core pagination
				isset($this->atts['pagination']['enable']) && $this->atts['pagination']['enable'] === false &&
				( !isset($this->atts['arrows']['enable']) || $this->atts['arrows']['enable'] === false )
			)
		) {
			global $wp_query;
			$page_key = isset($this->atts['query']['queryId']) ? 'query-'.$this->atts['query']['queryId'].'-page' : 'query-page';
			$paged    = isset($_GET[$page_key]) && !empty($_GET[$page_key]) ? intval($_GET[$page_key]) : 1;
			if ( isset($wp_query->paged) && $paged != $wp_query->paged ) {
				$paged = $wp_query->paged;
			}
		}

		// build new query
		if ( class_exists( '\Greyd\Query\Render' ) ) {
			$query_args = \Greyd\Query\Render::build_query( $this->atts['query'], $paged );
		}
		else {
			$query_args = array(
				'post_type'         => is_search() || $this->is_live_search || is_archive() ? 'any' : 'post',
				'order'             => 'DESC',
				'orderby'           => 'date',
				'post__not_in'      => array(),
				'post_status'       => 'publish',
				'suppress_filters'  => false,
			);
		}

		// Adjust the pages, posts_per_page and offset.
		// this is necessary to display queries as sliders with multiple pages,
		if ( !$this->inherit ) {

			$pageCount    = isset($this->atts['query']['query']) && isset($this->atts['query']['query']['pages']) ? intval($this->atts['query']['query']['pages']) : 0;
			$postsPerPage = $this->get_posts_per_page();

			/**
			 * if at least one of the features is enabled, that need a second page
			 * or more then set the posts_per_page to the total number of posts to
			 * get all posts. We later on use the posts_per_page to break the posts
			 * into multiple pages.
			 */
			if (
				!isset($this->atts['pagination']) // if no pagination attribute is set, the pagination is enabled by default
				|| ( isset($this->atts['pagination']) && ( !isset($this->atts['pagination']['enable']) || $this->atts['pagination']['enable'] === true ) ) // pagination enabled
				|| ( isset( $this->atts['arrows'] ) && isset($this->atts['arrows']['enable']) && $this->atts['arrows']['enable'] ) // arrows enabled
				|| ( isset( $this->atts['animation'] ) && isset($this->atts['animation']['autoplay']) && $this->atts['animation']['autoplay'] ) // autoplay enabled
				// || ( isset($this->atts['layout']) && isset($this->atts['layout']['responsive']) ) // responsive layout enabled
				// || ( isset($this->atts['query']) && isset($this->atts['query']['displayLayout']) && isset($this->atts['query']['displayLayout']['responsive']) ) // deprecated responsive layout enabled
			) {

				// pageCount is set to 0 or -1 (default) -> render all posts
				if ( $pageCount < 1 ) {
					$query_args['posts_per_page'] = wp_count_posts($query_args['post_type'])->publish;
				}
				else if ( $pageCount == 1 ) {
					$query_args['posts_per_page'] = $postsPerPage;
					$this->change_responsive_item_count_with_css   = true;
				}
				else {
					$query_args['posts_per_page'] = $postsPerPage * max($pageCount, 1);
				}
			}
			else {
				$query_args['posts_per_page'] = $this->get_max_posts_per_page();
				$this->change_responsive_item_count_with_css = true;
			}
		}
		
		// inherit query vars
		if ( $this->inherit ) {
			
			global $wp_query;
			// inherit query vars from global $wp_query
			if ( $wp_query && isset($wp_query->query_vars) && is_array($wp_query->query_vars) ) {
				// $query_args = wp_parse_args($wp_query->query_vars, $query_args);
				$query_args = wp_parse_args( $query_args, $wp_query->query_vars );
			}

			// Unset `offset` because if is set, $wp_query overrides/ignores the paged parameter and breaks pagination.
			unset($query_args['offset']);
		}

		// set fallback post type
		if ( empty($query_args['post_type']) ) {
			if ( !  $this->inherit ) {
				$query_args['post_type'] = 'post';
			}
			else if (  is_search() || is_archive() || $this->is_live_search ) {
				$query_args['post_type'] = 'any';
			}
			else if ( is_singular() ) {
				$query_args['post_type'] = get_post_type( get_the_ID() );
			}
			else if ( is_home() || is_front_page() ) {
				$query_args['post_type'] = 'post';
			}
			else {
				$query_args['post_type'] = 'post';
			}
		}
		
		/**
		 * Fix an issue, when the taxonomy and term are set in the query args.
		 */
		if ( isset( $query_args['taxonomy'] ) && isset( $query_args[ $query_args['taxonomy'] ] ) ) {
			unset( $query_args['taxonomy'], $query_args['term'] );
		}

		/**
		 * Filter query arguments.
		 * 
		 * @filter greyd_query_args
		 * 
		 * @param array       $query_args   Current query arguments
		 * @param Post_Template $query_class  Instance of this class.
		 * 
		 * @return array $query_args
		 */
		$this->query_args = apply_filters( 'greyd_query_args', $query_args, $this );

		return $this->query_args;
	}

	/**
	 * Get queryTags from $this->atts and fix if necessary.
	 * 
	 * @var array $queryTags
	 *      @var string query          The search term.
	 *      ...
	 *      @var int post-count        Number of total posts found.
	 *      @var int posts-per-page    Number of posts per page.
	 *      @var int page-count        Total number of pages.
	 *      @var int page-num          Current page number.
	 *      @var string page-next      URL to the next page. Empty if last page.
	 *      @var string page-previous  URL to the previous page. Empty if first page.
	 *      @var array page-keys       URLs to the different pages, keyed by page-num.
	 */
	public function get_query_tags() {

		// if not set, set default queryTags
		if ( !isset($this->atts['queryTags']) ) {
			$this->atts['queryTags'] = array(
				'query'          => '',
				'post-count'     => 0,
				'posts-per-page' => get_option('posts_per_page'),
				'page-count'     => 0,
				'page-num'       => 1,
				'page-next'      => '',
				'page-previous'  => '',
				'page-keys'      => array(),
			);
		}

		// use cache
		if ( isset($this->atts['queryTags']['cached']) ) {
			return $this->atts['queryTags'];
		}

		// queryTags from block attributes
		if ( !$this->inherit ) {
			
			// /**
			//  * Fix page & post count.
			//  * 
			//  * @since 1.7.6
			//  */
			// if ( $this->found_posts ) {
			// 	$this->atts['queryTags']['post-count'] = $this->found_posts;
			// 	$this->atts['query']['query']['pages'] = ceil( $this->atts['queryTags']['post-count'] / $this->atts['queryTags']['posts-per-page'] );
			// }

			/**
			 * Fix offset.
			 * 
			 * @since 1.3.3
			 */
			if ( isset($this->atts['query']['query']['offset']) && $this->atts['query']['query']['offset'] > 0 ) {

				$post_count = intval(strip_tags($this->atts['queryTags']['post-count']));
				$offset     = intval(strip_tags($this->atts['query']['query']['offset']));
				$per_page   = intval(strip_tags($this->atts['queryTags']['posts-per-page']));

				$this->atts['queryTags']['post-count'] = max( 0, $post_count - $offset );
				$this->atts['queryTags']['page-count'] = ceil( $this->atts['queryTags']['post-count'] / $per_page );
			}

			/**
			 * Fix page-count inside queryTags.
			 * 
			 * @since 1.3.1
			 */
			if ( isset($this->atts['query']['query']['pages']) && $this->atts['query']['query']['pages'] ) {
				$this->atts['queryTags']['page-count'] = min( $this->atts['queryTags']['page-count'], $this->atts['query']['query']['pages'] );
			}
		}

		// queryTags from global wp_query
		if ( $this->inherit ) {

			$overwrites = array();
	
			// inherit overwrites from global query
			global $wp_query;
			if ( $wp_query->paged ) {
				$overwrites['page-num'] = $wp_query->paged;
			}
			// if ( $wp_query->posts_per_page ) {
			// 	$overwrites['posts-per-page'] = $wp_query->posts_per_page;
			// }
			$overwrites['posts-per-page'] = get_option('posts_per_page');
			if ( $wp_query->max_num_pages ) {
				$overwrites['page-count'] = $wp_query->max_num_pages;
			}
	
			// set query tags with overwrites
			$queryTags  = wp_parse_args( $overwrites, $this->atts['queryTags'] );
	
			$page_num   = $queryTags['page-num'];
			$page_count = $queryTags['page-count'];
	
			// get page keys (array of urls to the pagination pages)
			$page_keys  = isset($queryTags['page-keys']) ? $queryTags['page-keys'] : array();
			$page_href  = isset($page_keys[$page_num]) ? $page_keys[$page_num] : '';

			// modify query url params
			if ( count($page_keys) > 0 ) {
				$page_keys = array_map( array($this, 'modifiy_query_url_params'), $page_keys );
			}
	
			// If the link to the current page is not empty,
			// we need to fix the page links...
			if ( !empty( $page_href ) ) {
	
				// override corrupt page links
				$queryTags['page-next']     = '';
				$queryTags['page-previous'] = '';
				$queryTags['page-keys']     = array_fill( 1, $page_count, '' );
	
				// re-generate page links
				$paginate_links = paginate_links( array(
					'type'      => 'array',
					'current'   => $page_num,
					'total'     => $page_count,
					'prev_text' => '{{previous}}',
					'next_text' => '{{next}}',
					'before_page_number' => '{{',
					'after_page_number'  => '}}'
				) );
				// debug( array_map('esc_attr', $paginate_links) );
	
				if ( is_array($paginate_links) ) {
					foreach( $paginate_links as $i => $link ) {
		
						// get href
						$href = Helper::get_string_between( $link, 'href="', '"' );
						if ( empty($href) ) continue;

						$href = $this->modifiy_query_url_params( $href );
						
						// get the page key
						$page_key = Helper::get_string_between( $link, '{{', '}}' );
		
						if ( is_numeric( $page_key ) ) {
							// this is a page number
							$page_key = intval( $page_key );
							$queryTags['page-keys'][ $page_key ] = $href;
						} else {
							// this is a prev/next page link
							$queryTags[ 'page-'.$page_key ] = $href;
						}
					}
				}
			}
			
			// set cache
			$this->atts['queryTags'] = $queryTags;
		}

		// enable cache
		$this->atts['queryTags']['cached'] = true;

		/**
		 * Filter query tags.
		 * 
		 * @filter greyd_query_tags
		 * 
		 * @param array       $query_tags   Current query tags.
		 *      @var string query           The search term.
		 *      ...
		 *      @var int post-count         Number of total posts found.
		 *      @var int posts-per-page     Number of posts per page.
		 *      @var int page-count         Total number of pages.
		 *      @var int page-num           Current page number.
		 *      @var string page-next       URL to the next page. Empty if last page.
		 *      @var string page-previous   URL to the previous page. Empty if first page.
		 *      @var array page-keys        URLs to the different pages, keyed by page-num.
		 * @param Post_Template $query_class  Instance of this class.
		 * 
		 * @return array $query_args
		 */
		return apply_filters( 'greyd_query_tags', $this->atts['queryTags'], $this );
	}

	/**
	 * Get the no results message.
	 * 
	 * @return string
	 */
	public function no_results_message() {

		$message = "";

		/**
		 * @since 2.7.0 Added support for inner no results message core block.
		 * @see Greyd\Query\Render for details on how this is defined
		 */
		if ( isset($this->atts['noResultsMessage']) ) {
			$message = $this->atts['noResultsMessage'];

			// on an archive or search page with no results, the no results block will
			// already be rendered, so we can return an empty string here.
			if ( $this->inherit && $this->found_posts === 0 && !Helper::is_rest_request() ) {
				return "";
			}
		}
		else {
			if ( is_archive() ) {
				$message = __("Unfortunately, no posts could be found.", "greyd_hub");
			}
			else if ( is_search() ) {
				$message = sprintf(
					__("Sorry, no search results could be found for \"%s\". Please try a different term.", "greyd_hub"),
					"<strong class='query--search-query'>" . get_search_query() . "</strong>"
				);
			}
			else if ( $this->is_live_filter ) {
				$message = __("No results found.", "greyd_hub");
			}
			else if ( $this->is_live_search ) {
				$message = __("No results found. Please try a different search term.", "greyd_hub");
			}

			/**
			 * Filter no results message.
			 * 
			 * @filter greyd_query_no_results_message
			 * 
			 * @param string      $message      Current message.
			 * @param Post_Template $query_class  Instance of this class.
			 * 
			 * @return array $query_args
			 */
			$message = apply_filters( 'greyd_query_no_results_message', $message, $this );

			// wrap it in a message class
			$message = "<p class='message info'>".$message."</p>";
		}

		if ( empty($message) ) return "";
		
		/**
		 * Filter empty message html.
		 * 
		 * @filter greyd_query_no_results_message_html
		 * 
		 * @param string      $message      Current message.
		 * @param Post_Template $query_class  Instance of this class.
		 * 
		 * @return array $query_args
		 */
		return apply_filters( 'greyd_query_no_results_message_html', sprintf(
			"<div class='no_result' %s>%s</div>",
			$this->found_posts === 0 ? "" : "style='display:none'",
			$message
		), $this );
	}

	/**
	 * Get the loading spinner HTML.
	 */
	public function loading_spinner() {
		return "<div class='loading_spinner_wrapper' style='display:none;'>
			<div class='loading_spinner'>
				<div></div> <div></div> <div></div> <div></div>
			</div>
		</div>";
	}

	/**
	 * Get all the wrapper arguments.
	 * 
	 * @return array
	 */
	public function get_wrapper_atts() {
		
		$this->prepare_slider_wrapper_atts();
		$this->prepare_live_search_wrapper_atts();
		$this->prepare_live_filter_wrapper_atts();

		/**
		 * Filter query wrapper HTML attributes.
		 * 
		 * @filter greyd_query_wrapper_atts
		 * 
		 * @param array       $wrapper_atts Current wrapper HTML attributes.
		 * @param Post_Template $query_class  Instance of this class.
		 * 
		 * @return array $query_args
		 */
		return apply_filters( 'greyd_query_wrapper_atts', $this->wrapper_atts, $this );
	}

	/**
	 * Add the wrapper atts to make this a JS slider.
	 */
	public function prepare_slider_wrapper_atts() {

		if ( $this->inherit || $this->is_live_search ) return;

		$this->wrapper_atts['class'][] = "js";

		// enable change of url param on page change
		if ( isset($this->atts['query']['queryId']) ) {
			$this->wrapper_atts['data']['query-id'] = $this->atts['query']['queryId'];
			if ( isset($this->atts['animation']['url_param']) && $this->atts['animation']['url_param'] ) {
				$this->wrapper_atts['data']['set-url'] = 'true';
			}
		}

		// get the attributes
		$anim_atts = null;
		if ( isset($this->atts['animation']) ) {
			$anim_atts = $this->atts['animation'];
		}
		else if ( isset($this->atts['query']['animation']) ) {
			$anim_atts = $this->atts['query']['animation'];
		}
		
		// static wrapper
		if ( empty( $anim_atts ) || !is_array( $anim_atts ) ) {
			$this->wrapper_atts['data']['height'] = 'max';
			return;
		}

		// animation type
		if ( isset($anim_atts['anim']) ) {
			$this->wrapper_atts['data']['animation'] = esc_attr( $anim_atts['anim'] );
		}

		// transition duration
		if ( isset($anim_atts['duration']) ) {
			$this->wrapper_atts['data']['duration'] = esc_attr( $anim_atts['duration'] );
		}

		// autoplay
		if ( isset($anim_atts['autoplay']) && $anim_atts['autoplay'] == true ) {
			$this->wrapper_atts['class'][] = "autoplay";
			$interval = isset($anim_atts['interval']) ? esc_attr($anim_atts['interval']) : "5";
			$this->wrapper_atts['data']['interval'] = $interval;
		}

		// loop
		if ( isset($anim_atts['loop']) && $anim_atts['loop'] == true ) {
			$this->wrapper_atts['class'][] = "loop";
		}

		// scroll to top
		if ( isset($anim_atts['scroll_top']) && $anim_atts['scroll_top'] == true ) {
			$this->wrapper_atts['class'][] = "slider_scroll_top";
		}

		// height
		$height = isset($anim_atts['height']) ? esc_attr($anim_atts['height']) : "max";
		if ( $height == 'custom' ) {
			$height = isset($anim_atts['height_custom']) ? esc_attr($anim_atts['height_custom']) : "500px";
			$this->wrapper_atts['style'][] = "height: ".$height.";";
		}
		$this->wrapper_atts['data']['height'] = $height;
	}

	/**
	 * Add the wrapper atts to make this a live search wrapper.
	 */
	public function prepare_live_search_wrapper_atts() {

		if ( !$this->is_live_search ) return;

		$this->wrapper_atts['live-search'] = 'true';

		// a11y attributes
		$this->wrapper_atts['aria-live'] = 'polite';
		$this->wrapper_atts['role'] = 'region';

		$this->wrapper_atts['data']['block-data'] = htmlentities(
			json_encode($this->inner_block),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
		);
	}

	/**
	 * Add the wrapper atts to make this a live filter wrapper.
	 */
	public function prepare_live_filter_wrapper_atts() {

		if ( !$this->is_live_filter ) return;

		// enqueue the script
		wp_enqueue_script( 'greyd-query-livefilter-script' );

		$this->wrapper_atts['live-query'] = 'true';

		// a11y attributes
		$this->wrapper_atts['aria-live'] = 'polite';
		$this->wrapper_atts['role'] = 'region';

		// save global post id for advanced filter
		if ( isset($this->atts['query']['advancedFilter'])) {
			global $post;
			if ( $post ) {
				$this->wrapper_atts['data']['post-id'] = $post->ID;
			}
			// add queried object id for 'current_archive_terms' filter
			global $queried_object_id;
			$id = $queried_object_id;
			if ( empty($queried_object_id) ) {
				$queried_object = get_queried_object();
				if ( is_a( $queried_object, 'WP_Post' ) ) {
					$id = $queried_object->ID;
				}
				else if ( is_a( $queried_object, 'WP_Term' ) ) {
					$id = $queried_object->term_id;
				}
			}
			if ( $id ) {
				$this->wrapper_atts['data']['queried-object-id'] = $id;
			}
		}

		// save wp_query for live filter
		if ( $this->inherit ) {
			$this->wrapper_atts['data']['wp-query'] = htmlentities(json_encode(\Greyd\Query\Render::get_main_query_vars()));
		}

		// apply atts to block
		$this->block['attrs'] = $this->atts;
		// save block data
		// $this->wrapper_atts['data']['block-data'] = htmlentities(
		// 	json_encode($this->block),
		// 	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
		// );
		$this->wrapper_atts['data']['block-data'] = base64_encode( json_encode($this->block) );
		
		// get responsive layout
		$layout = $this->get_layout();

		// set responsive wrapper atts, only if the layout is set and
		// the responsive item count is not changed with pure css
		if ( $layout && ! $this->change_responsive_item_count_with_css ) {
			
			$perPage = array();
			foreach ( array( 'sm', 'md', 'lg' ) as $bp ) {
				if ( isset($layout['responsive'][$bp]['items']) && !empty($layout['responsive'][$bp]['items']) ) {
					$perPage[$bp] = intval( $layout['responsive'][$bp]['items'] );
				}
			}

			if ( !empty($perPage) ) {
				// set xl (default)
				if ( isset($layout['items']) ) {
					$perPage['xl'] = intval( $layout['items'] );
				}
			}

			$initial = isset($layout['initial']) && !empty($layout['initial']) ? $layout['initial'] : 'xl';

			$current = $this->get_posts_per_page();

			// save perPage data
			$this->wrapper_atts['data']['perPage'] = htmlentities( json_encode( array(
				'initial' => $initial,
				'current' => $current,
				'items' => $perPage,
				'breakpoints' => \greyd\blocks\layout\Enqueue::get_breakpoints()
			) ) );
		}
	}

	/**
	 * Modify the query url params.
	 * This is necessary to build a proper pagination with links even
	 * inside a live filtered query.
	 * 
	 * @param string $url
	 * 
	 * @return string
	 */
	public function modifiy_query_url_params( $url ) {

		$url = str_replace( '#038;', '&', remove_query_arg( 'rest_route', $url ) );

		if ( $this->is_live_filter && $this->inherit && Helper::is_rest_request() ) {

			$url_param_keys = array(
				's',
				'orderby',
				'order',
				'post_type'
			);
			
			foreach ( \Greyd\Query\Render::get_main_query_vars() as $key => $value ) {
				if ( in_array($key, $url_param_keys) ) {
					$url = add_query_arg( $key, $value, $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Get the max posts per page per breakpoint.
	 * 
	 * @return int
	 */
	public function get_max_posts_per_page() {

		if ( $this->max_posts_per_page ) {
			return $this->max_posts_per_page;
		}

		$maxPostsPerPage = $this->get_posts_per_page();

		// get responsive layout
		$layout = $this->get_layout();
		
		if ( $layout ) {

			$perPage = array();
			foreach ( array( 'sm', 'md', 'lg' ) as $bp ) {
				if ( isset($layout['responsive'][$bp]['items']) ) {
					$perPage[$bp] = $layout['responsive'][$bp]['items'];
					if ( $perPage[$bp] > $maxPostsPerPage ) {
						$maxPostsPerPage = $perPage[$bp];
					}
				}
			}
		}

		$this->max_posts_per_page = $maxPostsPerPage;

		return $maxPostsPerPage;
	}
	
	/**
	 * Get posts per page from query or standard wp options.
	 * If the block is not updating (live-query), is adjusted based on the $layout['initial'] attribute.
	 * @since 2.15.0
	 * 
	 * @return int
	 */
	public function get_posts_per_page() {

		// debug($this->change_responsive_item_count_with_css);
		if ( $this->update === false && $this->change_responsive_item_count_with_css === false ) {
			$layout = $this->get_layout();
			$initial = $layout && isset($layout['initial']) && !empty($layout['initial']) ? $layout['initial'] : 'xl';
			// debug($initial);
			if ( $initial !== 'xl' ) {
				// adjust the initial posts per page
				if ( isset($layout['responsive'][$initial]['items']) && !empty($layout['responsive'][$initial]['items']) ) {
					return $layout['responsive'][$initial]['items'];
				}
				else {
					// fallbacks
					if ( $initial == 'sm' && isset($layout['responsive']['md']['items']) && !empty($layout['responsive']['md']['items']) ) {
						return $layout['responsive']['md']['items'];
					}
					if ( ( $initial == 'sm' || $initial == 'md' ) && isset($layout['responsive']['lg']['items']) && !empty($layout['responsive']['lg']['items']) ) {
						return $layout['responsive']['lg']['items'];
					}
				}
			}
		}

		// return default desktop 'xl' value
		// if in live-query, this attribute is changed by the js call so it doesn't need to be adjusted
		return isset($this->atts['query']['query']) && isset($this->atts['query']['query']['perPage']) ? intval($this->atts['query']['query']['perPage']) : get_option('posts_per_page');
	}

	/**
	 * Get the page count for the initial breakpoint, which usually is the desktop breakpoint.
	 * @since 2.15.0
	 * 
	 * @return int
	 */
	public function get_initial_page_count() {

		$query_tags = $this->get_query_tags();

		if ( $this->update === false && $this->change_responsive_item_count_with_css === false ) {

			$query_post_count       = intval( strip_tags( $query_tags['post-count'] ) );
			$query_posts_per_page   = intval( strip_tags( $query_tags['posts-per-page'] ) );
			$initial_items_per_page = intval( $this->get_posts_per_page() );

			if ( $initial_items_per_page !== $query_posts_per_page ) {
				return ceil( $query_post_count / $initial_items_per_page );
			}
		}

		return intval( strip_tags( $query_tags['page-count'] ) );
	}

	/**
	 * Get layout attributes, but only when responsive elements are present.
	 * Otherwise return false.
	 */
	public function get_layout() {
		// get responsive layout
		$layout = false;

		// new layout attribute
		if (
			isset($this->atts['layout']) &&
			isset($this->atts['layout']['responsive'])
		) {
			$layout = $this->atts['layout'];
		}

		// support deprecated displayLayout
		if (
			!$layout &&
			isset($this->atts['query']['displayLayout']) &&
			isset($this->atts['query']['displayLayout']['responsive'])
		) {
			$layout = $this->atts['query']['displayLayout'];
		}
		return $layout;
	}

}