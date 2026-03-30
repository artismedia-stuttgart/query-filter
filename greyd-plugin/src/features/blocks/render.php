<?php
/**
 * Block render callbacks & modifications.
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

new render($config);
class render {

	private $config;
	public static $custom_scripts = [];

	public function __construct($config) {

		// check if Gutenberg is active.
		if ( !function_exists('register_block_type') ) return;
		if ( is_admin() ) return;

		// set config
		$this->config = (object)$config;

		// hook block rendering
		add_filter( 'render_block_data', array($this, 'render_block_data'), 10, 2 );
		add_filter( 'render_block', array($this, 'render_block'), 10, 2 );

		// remove extra .alignleft and .alignright styles
		// if ( \Greyd\Helper::is_greyd_classic() ) {
		// 	remove_filter( 'render_block', 'gutenberg_render_layout_support_flag' );
		// }
		// render scripts
		// add_action( 'wp_footer', array($this, 'render_custom_scripts'), 98 );
	}


	/**
	 * =================================================================
	 *                          Block Data
	 * =================================================================
	 */

	/**
	 * Prepare Block Data
	 * 
	 * @param object $parsed_block      parsed Block
	 * @param object $source_block      source version of parsed Block
	 * 
	 * @return object $parsed_block    parsed Block with altered Block Data
	 */
	public static function render_block_data($parsed_block, $source_block) {
		// debug($parsed_block);
		// debug($source_block);
		$parsed_block = self::scan_block_data($parsed_block);
		return $parsed_block;
	}

	/**
	 * Scan and modify parsed Block recursively
	 * - scan for 'greyd/list' Block to prepare type and icon
	 * 
	 * @param object $block     parsed Block
	 * 
	 * @return object $block    parsed Block with altered Block Data
	 */
	public static function scan_block_data($block) {
		// debug($block);
		if (isset($block['innerBlocks'])) {

			/**
			 * Greyd block rendering filter.
			 * Filters the block data before the block is rendered.
			 * Extends the core block rendering filter.
			 * https://developer.wordpress.org/reference/hooks/render_block_data/
			 * 
			 * @filter 'greyd_blocks_render_block_data'
			 * 
			 * @param object $block     parsed Block
			 * 
			 * @return object $block    parsed Block with altered Block Data
			 */
			$block = apply_filters( 'greyd_blocks_render_block_data', $block);

			// repeat for inner blocks
			if (!empty($block['innerBlocks'])) {
				$inner = array();
				foreach ($block['innerBlocks'] as $inner_block) {
					array_push($inner, self::scan_block_data($inner_block));
				}
				$block['innerBlocks'] = $inner;
			}
		}
		return $block;
	}


	/**
	 * =================================================================
	 *                          Render block main filter
	 * =================================================================
	 */

	/**
	 * hook block rendering
	 * https://developer.wordpress.org/reference/hooks/render_block/
	 *
	 * @param string $block_content     The block content about to be appended.
	 * @param array  $block             The full block, including name and attributes.
	 *
	 * @return string $block_content    altered Block Content
	 */
	public static function render_block( $block_content, $block ) {

		if ( ! isset( $block['blockName'] ) || empty( $block['blockName'] ) ) {
			return $block_content;
		}

		$id        = '';
		$style     = '';
		$html_atts = array(); // array is imploded in the end

		if ( isset( $block['attrs']['anchor'] ) && !empty( $block['attrs']['anchor'] ) ) {
			$id = $block['attrs']['anchor'];
		}
		else if ( $block['blockName'] != 'greyd/dynamic' ) {
			// parse id from content (core blocks don't save anchor in attrs)
			$res = preg_match_all( '/^<[^>]+\sid=\"([^\"]+)\"/', trim( $block_content ), $matches );
			if ( $res && count( $matches[0] ) > 0 ) {
				$id = $matches[1][0];
			}
		}


		/**
		 * fix greydStyles color value in cases when the transparent color preset ist wrongly saved.
		 * @since. 2.2.0
		 */
		if (
			isset( $block['attrs']['greydStyles'] ) &&
			!\Greyd\Helper::is_array_empty( $block['attrs']['greydStyles'] ) &&
			strpos( json_encode( $block['attrs']['greydStyles'] ), "var(--wp--preset--color--rgb(255,255,255,0))" ) !== false
		) {
			// debug($block['attrs']['greydStyles']);
			$old = "var(--wp--preset--color--rgb(255,255,255,0))";
			$new = "var(--wp--preset--color--transparent)";
			// fix in content
			$block_content = str_replace( $old, $new, $block_content );
			// fix in attrs
			$greyd_styles = str_replace( $old, $new, json_encode( $block['attrs']['greydStyles'] ) );
			$block['attrs']['greydStyles'] = json_decode($greyd_styles, true);
		}


		/**
		 * Blocks
		 */

		// iframe
		if ( $block['blockName'] === 'core/embed' ) {

			if ( isset( $block['attrs']['width'] ) && $block['attrs']['width'] != '' ) {
				$width  = $block['attrs']['width'];
				$style .= 'max-width: ' . $width . '; width: ' . $width . '; ';
			}
		}

		// Empty space
		if ( $block['blockName'] === 'core/spacer' ) {
			
			$responsive_heights = isset( $block['attrs']['responsive']['height'] ) ? $block['attrs']['responsive']['height'] : array();

			// remove empty values and 100% values
			$responsive_heights = array_filter(
				$responsive_heights,
				function( $item ) {
					return ! empty( $item ) && $item !== '100%';
				}
			);


			// classic version
			if ( \greyd\blocks\enqueue::is_greyd_classic() ) {
				$height_default = '100px';
				$sizes          = array( 'sm' => '40%', 'md' => '60%', 'lg' => '80%', 'xl' => '100%' );
			}

			// fse version
			else {

				// return if there are no responsive heights
				if ( empty( $responsive_heights ) ) {
					return $block_content;
				}

				// defaults
				$height_default = 'var(--wp--preset--spacing--large, 1rem)';
				$sizes          = array( 'sm' => '100%', 'md' => '100%', 'lg' => '100%', 'xl' => '100%' );
			}

			// base height (if set)
			$height_base = isset( $block['attrs']['height'] ) ? esc_attr( $block['attrs']['height'] ) : $height_default;

			// if this is a numeric value, we want to add 'px' to the value
			if ( is_numeric( $height_base ) ) {
				$height_base .= 'px';
			}

			// early exit if height is 0
			if ( empty($height_base) || $height_base === '0px' || $height_base == '0' ) {
				return $block_content;
			}

			// convert values
			// from: var:preset|spacing|{size}
			// to:   var(--wp--preset--spacing--{size})
			$height_base = helper::get_spacing_preset_css_var( $height_base, '1rem' );

			/**
			 * @since 1.7.3 Support for flex options.
			 * Flex options are only used when the spacer block is
			 * used as a flex item, eg. in a navigation block.
			 */
			if (
				isset( $block['attrs']['style'] )
				&& isset( $block['attrs']['style']['layout'] )
				&& isset( $block['attrs']['style']['layout']['selfStretch'] )
			) {
				if (
					$block['attrs']['style']['layout']['selfStretch'] === 'fixed'
					&& isset( $block['attrs']['style']['layout']['flexSize'] )
				) {
					$height_base = $block['attrs']['style']['layout']['flexSize'];
					if ( intval( $height_base ) === $height_base ) {
						$height_base .= 'px';
					}
				} else {
					return $block_content;
				}
			}

			$scale_default = '100%';
			foreach ( $sizes as $size => $default ) {

				$height = isset( $responsive_heights[ $size ] ) ? esc_attr( $responsive_heights[ $size ] ) : $default;

				if ( empty( $height ) ) {
					$height = $scale_default; // inherit the default
				} else {
					$scale_default = $height; // set new default to inherit this on the next breakpoint
				}

				// early exit if height is 0
				if ( empty($height) || $height === '0%' || $height === '0' ) {
					$sizes[ $size ] = '0';
					continue;
				}

				// early exit if height is 100%
				if ( $height === '100%' || $height === '100' ) {
					$sizes[ $size ] = $height_base;
					continue;
				}

				// if this is a percent value, we want to scale the base height (in pixel)
				if ( strpos( $height, '%' ) > -1 ) {
					$height_int = intval( preg_replace( '/[^0-9]/', '', $height ) );
					$height     = 'calc(' . $height_base . ' * ' . ( $height_int / 100 ) . ')';
				} else {
					/**
					 * deprecated em val
					 *
					 * @since WordPress 5.9
					 */
					$height_float = floatval( $height );
					$height       = 'calc(' . $height_base . ' * ' . $height_float . ')';
				}

				$sizes[ $size ] = $height;
			}

			$el_id    = ( $id != '' ) ? "id='" . $id . "'" : '';
			$el_class = array( 'spacer_wrap' );
			if ( isset( $block['attrs']['className'] ) ) {
				array_push( $el_class, $block['attrs']['className'] );
			}

			$block_content = sprintf( '<div %1$s class="%2$s" aria-hidden="true">', $el_id, implode( ' ', $el_class ) );

			$size_rendered = array();
			foreach ( $sizes as $size => $height ) {

				// skip if already rendered as combined size
				if ( isset( $size_rendered[ $size ] ) ) {
					continue;
				}

				$className = 'inner-' . $size;

				// see if another size has the same height -> combine them
				foreach ( $sizes as $size2 => $height2 ) {
					if ( $height === $height2 && $size !== $size2 ) {
						$className .= ' inner-' . $size2;
						$size_rendered[ $size2 ] = true; // mark as rendered
					}
				}

				$block_content .= sprintf(
					'<div class="wp-block-spacer %1$s" style="height:%2$s" aria-hidden="true"></div>',
					$className,
					$height
				);
				$size_rendered[ $size ] = true;
			}
			$block_content .= '</div>';

		}

		if ( $block['blockName'] === 'core/archives' ) {
			ob_start();
			// debug( $block );

			// props.attributes
			$atts        = $block['attrs'];
			$filter      = array(
				'post_type'    => 'post',
				'type'         => 'monthly',
				'order'        => '',
				'hierarchical' => 0,
				'date_format'  => '',
			);
			$filter      = isset( $atts['filter'] ) ? array_merge( $filter, $atts['filter'] ) : $filter;
			$show_count  = isset( $atts['showPostCounts'] ) && $atts['showPostCounts'];
			$is_dropdown = isset( $atts['displayAsDropdown'] ) && $atts['displayAsDropdown'];
			$align       = isset( $atts['align'] ) ? $atts['align'] : null;
			$styles      = array(
				'style'  => '',
				'size'   => '',
				'custom' => 0,
				'icon'   => array(
					'content'  => '',
					'position' => 'after',
					'size'     => '100%',
					'margin'   => '10px',
				),
			);
			$styles      = isset($atts['styles']) ? array_merge($styles, $atts['styles']) : $styles;

			$item_class  = '';
			if ( !empty($styles['style']) ) {
				if ( strpos($styles['style'], 'button') === false ) {
					$item_class .= 'is-style-'.$styles['style'];
				} else {
					$item_class .= 'button is-style-' . trim( str_replace('button', '', $styles['style']) );
				}
			}
			if ( !empty($styles['size']) ) {
				$item_class .= ' is-size-'.$styles['size'];
			}

			// html elements
			$archives           = '';
			$wrapper_attributes = array(
				'class' => $is_dropdown ? 'wp-block-archives-dropdown' : 'wp-block-archives-list',
			);
			if ( ! empty( $align ) ) {
				$wrapper_attributes['class'] .= ' ' . $align;
			}
			if ( $is_dropdown ) {
				$item_class = trim( str_replace( 'button', '', $item_class ) );
			}

			// icon
			$icon = array(
				'before' => '',
				'after'  => '',
			);
			if ( ! $is_dropdown && isset( $styles['icon'] ) && isset( $styles['icon']['content'] ) ) {
				$pos          = isset( $styles['icon']['position'] ) ? $styles['icon']['position'] : 'after';
				$fsz          = isset( $styles['icon']['size'] ) ? $styles['icon']['size'] : '100%';
				$mrg          = isset( $styles['icon']['margin'] ) ? $styles['icon']['margin'] : '10px';
				$dir          = $pos === 'after' ? 'left' : 'right';
				$icon[ $pos ] = '<span class="' . $styles['icon']['content'] . '" aria-hidden="true" style="vertical-align: middle; font-size: ' . $fsz . '; margin-' . $dir . ': ' . $mrg . ';"></span>';
			}

			// custom styles
			if ( $styles['custom'] && isset( $atts['customStyles'] ) && ! empty( $atts['customStyles'] ) && isset( $atts['greydClass'] ) ) {

				// add priority to greyd class
				$selector = '.' . $atts['greydClass'];
				if ( ! $is_dropdown ) {
					$selector = '.wp-block-archives-list li a' . $selector;
				}
				if ( ! empty( $item_class ) ) {
					$selector .= '.' . implode( '.', explode( ' ', $item_class ) );
				}
				$item_class .= ' ' . $atts['greydClass'];

				self::enqueue_custom_style( $selector, $atts['customStyles'] );
			}

			// date based archive
			if ( $filter['type'] == 'yearly' || $filter['type'] == 'monthly' || $filter['type'] == 'daily' ) {

				$archives = wp_get_archives(
					array(
						'post_type'       => $filter['post_type'],
						'type'            => $filter['type'],
						'order'           => $filter['order'],
						'echo'            => false,
						'show_post_count' => $show_count,
						'format'          => $is_dropdown ? 'option' : 'html',
					)
				);

				if ( ! empty( $archives ) ) {
					$date_format = isset( $filter['date_format'] ) ? $filter['date_format'] : null;
					if ( $is_dropdown ) {
						$archives = preg_replace_callback(
							'/(<option)([^>]+>)([^<>\(]+)(&nbsp;\(\d+\))?(<\/option>)/',
							function( $matches ) use ( $date_format ) {
								list( $whole_match, $before, $attributes, $date_string, $count, $after ) = $matches;
								if ( ! empty( $date_format ) ) {
									$date_string = mysql2date( $date_format, helper::get_date_from_localed_string( $date_string ) );
								}
								return $before . $attributes . $date_string . $count . $after;
							},
							$archives
						);
					} else {
						$archives = preg_replace_callback(
							'/(<a)\s([^>]+>)([^<>]+)(<\/a>)(&nbsp;\(\d+\))?/',
							function( $matches ) use ( $date_format, $item_class, $icon ) {
								list( $whole_match, $before, $attributes, $date_string, $after ) = $matches;
								$count = isset( $matches[5] ) ? $matches[5] : '';
								if ( ! empty( $date_format ) ) {
									$date_string = mysql2date( $date_format, helper::get_date_from_localed_string( $date_string ) );
								}
								return $before . ' class="' . $item_class . '" ' . $attributes . $icon['before'] . $date_string . $count . $icon['after'] . $after;
							},
							$archives
						);
					}
				}
			}
			// category based archive
			else {
				list( $orderby, $order ) = explode( ' ', ! empty( $filter['order'] ) ? $filter['order'] : 'name ASC' );
				$hierachical             = $filter['hierarchical'] === true || $filter['hierarchical'] === 'true';

				/**
				 * Get the terms
				 *
				 * (1) we include all terms, because the WordPress core function is very
				 *     inconsistent whether term-counts are empty in different languages.
				 *
				 * (2) get only first hierarchy level, we handle the child terms later on
				 */
				$term_args = array(
					'taxonomy'   => $filter['type'],
					'orderby'    => $orderby,
					'order'      => $order,
					'hide_empty' => false, // (1)
					'parent'     => 0, // (2)
				);
				$terms     = get_terms( $term_args );

				if ( ! empty( $terms ) ) {

					$current_term = get_queried_object() ?? null;
					$current_term = isset( $current_term->term_id ) ? $current_term->term_id : null;

					foreach ( $terms as $term ) {

						// check if this term has posts
						$term_posts      = get_posts(
							array(
								'post_type'        => $filter['post_type'],
								'post_status'      => 'publish',
								'numberposts'      => -1,
								'suppress_filters' => false,
								'tax_query'        => array(
									array(
										'taxonomy' => $filter['type'],
										'field'    => 'term_id',
										'terms'    => $term->term_id,
									),
								),
							)
						);
						$term_post_count = empty( $term_posts ) ? 0 : count( $term_posts );
						if ( ! $term_post_count || $term_post_count < 1 ) {
							continue;
						}

						$archives .= sprintf(
							( $is_dropdown ?
								'<option value="%1$s"%4$s>%2$s%3$s</option>' :
								'<li class="%6$s"><a href="%1$s" class="%5$s%4$s">%2$s%3$s</a></li>'
							),
							// (1) href
							get_tag_link( $term->term_id ),
							// (2) title
							$icon['before'] . $term->name,
							// (3) count
							( $show_count ? '&nbsp;(' . $term_post_count . ')' : '' ) . $icon['after'],
							// (4) active
							$current_term === $term->term_id ? ( $is_dropdown ? ' selected="selected"' : ' active' ) : '',
							// (5) item class
							$item_class,
							// (6) wrapper class
							''
						);
						// child terms
						if ( $hierachical ) {
							$term_args['parent'] = $term->term_id;
							$child_terms         = get_terms( $term_args );
							if ( ! empty( $child_terms ) ) {
								foreach ( $child_terms as $child_term ) {
									$archives .= sprintf(
										( $is_dropdown ?
											'<option value="%1$s"%4$s>%2$s%3$s</option>' :
											'<li class="%6$s"><a href="%1$s" class="%5$s%4$s">%2$s%3$s</a></li>'
										),
										// (1) href
										get_tag_link( $child_term->term_id ),
										// (2) title
										$icon['before'] . $child_term->name,
										// (3) count
										( $show_count ? '&nbsp;(' . $child_term->count . ')' : '' ) . $icon['after'],
										// (4) active
										$current_term === $child_term->term_id ? ( $is_dropdown ? ' selected="selected"' : ' active' ) : '',
										// (5) item class
										$item_class,
										// (6) wrapper class
										'child-term'
									);
								}
							}
						}
					}
				}
			}

			echo sprintf(
				$is_dropdown ? '<div %1$s><select class="%3$s" onchange="document.location.href=this.options[this.selectedIndex].value;">%2$s</select></div>' : '<ul %1$s>%2$s</ul>',
				// (1) wrapper attributes
				helper::implode_html_attributes( $wrapper_attributes ),
				// (2) content
				$archives,
				// (3) item class
				$item_class
			);

			$block_content = ob_get_contents();
			ob_end_clean();
		}

		if ($block['blockName'] === 'core/video') {

			/**
			 * Render mobile video.
			 * Uses lazyloading to make sure only one video is loaded on init.
			 * Swich is done by media css which triggers the lazyload of the other video.
			 *
			 * @since 1.5.0
			 */
			if ( isset( $block['attrs']['mobile']['url'] ) && ! empty( $block['attrs']['mobile']['url'] ) ) {

				// get original video
				preg_match( '/(<video.*video>)/', $block_content, $video );
				if ( ! empty( $video ) ) {
					// get wrapper (figure) elements
					$content = explode( $video[0], $block_content );
					// check id
					if ( $id == '' ) {
						// inject new id to wrapper
						$id         = 'greyd-' . uniqid();
						$content[0] = preg_replace(
							'/(<[a-zA-Z1-6]+)/',
							'$1 id="' . $id . '"',
							$content[0],
							1
						);
					}

					// make mobile video - new src and add 'mobile_video' class
					$mobile = preg_replace(
						'/(src="[^"]+?")/',
						'src="' . $block['attrs']['mobile']['url'] . '"',
						$video[0]
					);
					$mobile = preg_replace(
						'/<video /',
						'<video class="mobile_video" ',
						$mobile,
						1
					);

					// make desktop video - add 'desktop_video' class
					$desktop = preg_replace(
						'/<video /',
						'<video class="desktop_video" ',
						$video[0],
						1
					);

					// make style with breakpoint toggle
					// $grid = \greyd\blocks\deprecated\Functions::get_breakpoints();
					// $grid = \Greyd\Layout\Manage::get_breakpoints();
					$grid = \greyd\blocks\layout\Enqueue::get_breakpoints();
					$breakpoint  = isset( $block['attrs']['mobile']['breakpoint'] ) ? $block['attrs']['mobile']['breakpoint'] : 'sm';
					$video_style = '<style>' .
						'#' . $id . ' .desktop_video { display: block; } ' .
						'#' . $id . ' .mobile_video { display: none; } ' .
						'@media (max-width: ' . ( $grid[ $breakpoint ] - 0.02 ) . 'px) { ' .
							'#' . $id . ' .desktop_video { display: none; } ' .
							'#' . $id . ' .mobile_video { display: block; } ' .
						'} ' .
					'</style>';

					// make new block content
					$block_content = $video_style . $content[0] . $desktop . $mobile . $content[1];

					// force lazy loading
					$make_lazy = true;
				}
			}

			/**
			 * Enable video lazyloading.
			 *
			 * If the property 'preload' is set to 'auto' or 'none' the video 'src' is
			 * lazyloaded by 'data-src' via an IntersectionObserver.
			 *
			 * @see greyd_blocks/assets/js/front-video-lazyload.js (deprecated)
			 * @see greyd_blocks/inc/layout/assets/js/frontend.js
			 *
			 * @since 1.2.5
			 */
			if ( ( isset( $make_lazy ) && $make_lazy ) || preg_match( '/ preload=\"(auto|none)\" /', $block_content ) ) {
				$block_content = preg_replace(
					'/(src="[^"]+?")/',
					'data-$1',
					$block_content
				);
			}
		}

		// layout atts
		if (
			$block['blockName'] === 'core/social-links' ||
			$block['blockName'] === 'core/query-pagination' ||
			$block['blockName'] === 'core/group'
			// $block['blockName'] === 'core/gallery'
		) {
			// debug($block);
			// debug(htmlspecialchars($block_content));
			// debug($block_content);

			$layout_id = empty($id) ? 'wp-container-' . uniqid() : $id;
			// if ( $block['blockName'] === 'core/gallery' ) {
			// 	$layout_style = '#' . $id . ' { display: flex; flex-wrap: wrap; gap: 0.5em; flex-direction: row; justify-content: flex-start; } ';
			// }
			if ( isset( $block['attrs']['layout'] ) ) {
				$layout_style = function_exists( 'gutenberg_get_layout_style' ) ? gutenberg_get_layout_style( '#' . $layout_id, $block['attrs']['layout'], false ) : '';
			}
			elseif ( isset( $block['attrs']['align'] ) ) {
				$layout_style = '#' . $layout_id . ' { gap: 0.5em; justify-content: ' . $block['attrs']['align'] . '; } ';
			}

			if ( isset( $layout_style ) && $layout_style != '' ) {
				// debug(htmlspecialchars($style));
				// inject id and render layout style
				$id = $layout_id;
				$block_content = preg_replace(
					'/' . preg_quote( 'class="', '/' ) . '/',
					'id="' . $id . '" class="',
					$block_content,
					1
				);
				\Greyd\Enqueue::add_custom_style( $layout_style );
			}
		}

		/**
		 * Attributes
		 */

		// greydStyles
		if ( isset( $block['attrs']['greydStyles'] ) && ! \Greyd\Helper::is_array_empty( $block['attrs']['greydStyles'] ) ) {

			$greydStyles = $block['attrs']['greydStyles'];

			// debug($block["attrs"]);

			// (1) use ID as selector
			$selector = ! empty( $id ) ? '#' . $id : '';

			// (2) use greydClass as selector
			if ( isset( $block['attrs']['greydClass'] ) ) {
				$greydClass = $block['attrs']['greydClass'];
			}
			// (3) generate new greydClass as selector
			elseif ( $selector == '' ) {
				$greydClass = helper::generate_greydClass();
			}

			if ( isset( $greydClass ) ) {
				$selector = '.' . $greydClass;

				// make sure the greydClass is added as an html-attribute
				if ( ! preg_match( '/' . $greydClass . '/', $block_content ) ) {
					if ( ! isset( $html_atts['class'] ) ) {
						$html_atts['class'] = $greydClass;
					} elseif ( strpos( $html_atts['class'], $greydClass ) === false ) {
						$html_atts['class'] .= ' ' . $greydClass;
					}
				}
			}

			// fix alignments for headings & paragraphs
			if ( $block['blockName'] == 'core/heading' || $block['blockName'] == 'core/paragraph' ) {

				$margin = isset( $greydStyles['margin'] ) ? $greydStyles['margin'] : array();
				$align  = isset( $block['attrs']['textAlign'] ) && $block['attrs']['textAlign'] != '' ? $block['attrs']['textAlign'] : ( isset( $block['attrs']['align'] ) && $block['attrs']['align'] != '' ? $block['attrs']['align'] : null );

				if ( $align == 'right' ) {
					$margin['left'] = 'auto';
				} elseif ( $align == 'center' ) {
					$margin['left']  = 'auto';
					$margin['right'] = 'auto';
				}

				if ( ! empty( $margin ) ) {
					$greydStyles['margin'] = $margin;
				}
			}
			// line-clamp
			if (
				$block['blockName'] == 'core/paragraph' ||
				$block['blockName'] == 'core/heading'
			) {
				$level = $block['blockName'] == 'core/heading' ? ( 'H' . ( isset( $block['attrs']['level'] ) ? $block['attrs']['level'] : 2 ) ) : '';
				foreach ( array( '', 'lg', 'md', 'sm' ) as $bp ) {
					// get styles
					$greyd_styles = $greydStyles;
					if ( $bp != '' ) {
						if ( ! isset( $greydStyles['responsive'][ $bp ] ) ) {
							continue;
						}
						$greyd_styles = $greydStyles['responsive'][ $bp ];
					}
					// compute line-clamp
					if ( isset( $greyd_styles['-webkit-line-clamp'] ) && $greyd_styles['-webkit-line-clamp'] > 0 ) {
						// add needed additional styles
						// https://css-tricks.com/almanac/properties/l/line-clamp/
						$greyd_styles['display']            = '-webkit-box';
						$greyd_styles['overflow']           = 'hidden';
						$greyd_styles['-webkit-box-orient'] = 'vertical';
						// force number of lines from line-clamp
						// debug($greyd_styles);
						$greyd_styles['min-height'] = 'auto';
						if ( isset( $greyd_styles['forceheight'] ) && $greyd_styles['forceheight'] ) {
							$lines = $greyd_styles['-webkit-line-clamp'];
							$lineHeight = 'var(--'.$level.'lineHeight)';
							if ( !\greyd\blocks\enqueue::is_greyd_classic() ) {
								if ( $level == '' ) $lineHeight = 'var(--wp--custom--line-height--normal)';
								else $lineHeight = 'var(--wp--custom--line-height--tight)';
								if ( isset($block['attrs']['style']['typography']['lineHeight']) ) {
									// fixed line-height in attrs
									$lineHeight = $block['attrs']['style']['typography']['lineHeight'];
								}
							}
							$greyd_styles['--line-height'] = $lineHeight;
							$greyd_styles['min-height'] = 'calc(var(--line-height) * '.$lines.' * 1em)';
						}
						// set styles
						if ( $bp == '' ) {
							$greydStyles = $greyd_styles;
						} else {
							$greydStyles['responsive'][ $bp ] = $greyd_styles;
						}
					}
				}
			}

			if ( $block['blockName'] == 'core/separator' ) {
				// we have to set the styles as inline_css because gutenberg overrides it with its block-css
				$greyd_styles = $greydStyles;

				$core_styles = '';
				if ( isset( $block['attrs']['style'] ) && function_exists( 'wp_style_engine_get_styles' ) ) {
					$core_styles =  wp_style_engine_get_styles( $block['attrs']['style'] );
					$core_styles = isset( $core_styles['css'] ) ? $core_styles['css'] : '';
				}
				
				// $greyd_styles['opacity'] = !empty($greyd_styles['opacity']) ? $greyd_styles['opacity'] : ".4";
				$inline_css = helper::compose_css( array( '' => $greyd_styles ), '', true );
				$inline_css = str_replace( '{', "", $inline_css );
				$inline_css = str_replace( '}', "", $inline_css );
				$inline_css = "style='{$inline_css} {$core_styles}' ";
	
				// get length of tagName to calculate correct start-index to inject inline css
				$tagName = isset($block['attrs']['tagName']) ? $block['attrs']['tagName'] : 'hr';
				$block_content = substr_replace( $block_content, $inline_css, strlen($tagName)+3, 0 );
				$html_atts     = array();
			}

			/**
			 * @filter Filter the block selector for greyd custom styles.
			 * @since 2.8.0
			 * 
			 * @param string $selector
			 * @param array $block
			 * 
			 * @return string $selector
			 */
			$selector = apply_filters( 'greyd_styles_block_selector-' . $block['blockName'], $selector, $block );

			/**
			 * @filter Filter the block styles for greyd custom styles.
			 * @since 2.8.0
			 * 
			 * @param array $greydStyles
			 * @param array $block
			 * 
			 * @return array $greydStyles
			 */
			$greydStyles = apply_filters( 'greyd_styles_block_styles-' . $block['blockName'], $greydStyles, $block );

			/**
			 * @filter Filter the addiitonal attributes for greyd custom styles.
			 * @since 2.8.0
			 * 
			 * @param array $atts        Additional attributes (deprecated: @param bool $important)
			 *                           Default: array( 'important' => true )
			 *   @property string $path_hover      Path to the hover selector.
			 *   @property string $pseudo_hover    Pseudo class for the hover state.
			 *   @property string $path_active     Path to the active selector.
			 *   @property string $pseudo_active   Pseudo class for the active state.
			 * 
			 * @param array $block       Full block, including name and attributes.
			 * 
			 * @return array $atts
			 */
			$greyd_styles_atts = apply_filters(
				'greyd_styles_block_atts-' . $block['blockName'],
				array( 'important' => true ),
				$block
			);

			// debug($greydStyles);
			// debug($atts);

			// enqueue the custom styles
			if ( !empty($greydStyles) ) {
				self::enqueue_custom_style( $selector, $greydStyles, $greyd_styles_atts );
			}
		}

		// form
		if ( $block['blockName'] === 'greyd/form' ) {
			if ( $id != '' ) {
				$html_atts['id'] = $id;
			}
			if ( isset( $block['attrs']['className'] ) ) {
				$html_atts['class'] = $block['attrs']['className'];
			}
		}

		// dynamic block
		if ( $block['blockName'] === 'greyd/dynamic' ) {

			if ( $id != '' ) {
				$html_atts['id'] = $id;
			}

		}

		/**
		 * Greyd block rendering filter.
		 * Extends the core block rendering filter.
		 * https://developer.wordpress.org/reference/hooks/render_block/
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
		 *      @property array  html_atts        altered html wrapper attributes
		 *      @property string style            altered css styles
		 */
		$rendered = apply_filters(
			'greyd_blocks_render_block',
			array(
				'block_content' => $block_content,
				'html_atts'     => $html_atts,
				'style'         => $style,
			),
			$block
		);
		if ( isset( $rendered['block_content'] ) ) {
			$block_content = $rendered['block_content'];
		}
		if ( isset( $rendered['html_atts'] ) ) {
			$html_atts = $rendered['html_atts'];
		}
		if ( isset( $rendered['style'] ) ) {
			$style = $rendered['style'];
		}

		if ( $block['blockName'] === 'core/group' ) {
			// add a class to the wrapper of the group block to not break the layout
			if ( ! empty( $html_atts ) && !isset($block['attrs']['layout']['contentSize']) ) {
				$html_atts['class'] = isset( $html_atts['class'] ) ? $html_atts['class'] . ' group_wrap' : 'group_wrap';
			}
		}

		/**
		 * We have some HTML wrapper attributes.
		 */
		if ( ! empty( $html_atts ) && ! empty( $block_content ) ) {

			/**
			 * These Blocks do get a wrapper around:
			 * - core/columns
			 * - greyd/dynamic
			 */
			if (
				$block['blockName'] === 'core/columns'
				|| $block['blockName'] === 'greyd/dynamic'
			) {

				/**
				 * Column support for hidden classes
				 */
				if ( $block['blockName'] === 'core/columns' ) {

					if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
						$tags = new \WP_HTML_Tag_Processor( $block_content );
						if (
							method_exists( $tags, 'next_tag' )
							&& method_exists( $tags, 'has_class' )
							&& method_exists( $tags, 'get_updated_html' )
						) {
							if ( $tags->next_tag( array( 'class_name' => 'wp-block-columns' ) ) ) {
								if ( $tags->has_class( 'hidden-xs' ) ) {
									$html_atts['class'] .= ' hidden-xs';
								}
								if ( $tags->has_class( 'hidden-sm' ) ) {
									$html_atts['class'] .= ' hidden-sm';
								}
								if ( $tags->has_class( 'hidden-md' ) ) {
									$html_atts['class'] .= ' hidden-md';
								}
								if ( $tags->has_class( 'hidden-lg' ) ) {
									$html_atts['class'] .= ' hidden-lg';
								}
								if ( $tags->has_class( 'alignwide' ) ) {
									$html_atts['class'] .= ' alignwide';
								}
								if ( $tags->has_class( 'alignfull' ) ) {
									$html_atts['class'] .= ' alignfull';
								}
								$block_content = $tags->get_updated_html();
							}
						}
					}
				}

				// append children
				if ( isset( $html_atts['children'] ) ) {
					$block_content = implode( '', $html_atts['children'] ) . $block_content;
					unset( $html_atts['children'] );
				}

				$block_content = '<div ' . helper::implode_html_attributes( $html_atts ) . '>' . $block_content . '</div>';
			}
			/**
			 * @since 1.8.4: All other blocks get the wrapper attributes merged
			 * into the first html tag.
			 */
			else {

				// explode the content
				list( $first_wrap, $other_content ) = explode( '>', $block_content, 2 );

				// append children
				if ( isset( $html_atts['children'] ) ) {
					$other_content = implode( '', $html_atts['children'] ) . $other_content;
					unset( $html_atts['children'] );
				}

				// replace html atts to not add a wrapper
				$first_wrap = helper::implode_html_attributes( $html_atts, $first_wrap );

				// merge the content
				$block_content = $first_wrap . '>' . $other_content;
			}
		}

		/**
		 * enqueue custom styles
		 */
		if ( $style != '' && ! empty( $block_content ) ) {

			if ( empty( $id ) ) {

				if ( isset( $html_atts['id'] ) && !empty( $html_atts['id'] ) ) {
					$id = $html_atts['id'];
				}
				else {
					/**
					 * Inject id to render custom styles.
					 * e.g. when inline_css is set, but no id is saved.
					 *
					 * @since 1.2.5
					 */
					$id            = 'greyd-' . uniqid();
					$block_content = preg_replace(
						'/(<[a-zA-Z1-6]+)/',
						'$1 id="' . $id . '"',
						$block_content,
						1
					);
				}
			}

			self::enqueue_custom_style( '#' . $id, $style );
			// \Greyd\Enqueue::add_custom_style("#".$id." { ".$style." } ");
		}

		/**
		 * Greyd block rendering filter.
		 * Filters the block_content again after Wrapper and custom Styles are rendered.
		 *
		 * @filter 'greyd_blocks_render_block_finally'
		 *
		 * @param string $block_content     block content about to be appended.
		 * @param array  $block             full block, including name and attributes.
		 *
		 * @return string $block_content    altered Block Content
		 */
		$block_content = apply_filters( 'greyd_blocks_render_block_finally', $block_content, $block );

		// return
		return $block_content;
	}


	/**
	 * =================================================================
	 *                          Block render callbacks
	 * =================================================================
	 */

	// forms
	public static function render_form($atts) {
		return (
			method_exists('\vc\shortcode', 'form_message_sc')
			? \vc\shortcode::form_message_sc($atts)
			: ""
		);
	}

	// menu
	public static function render_menu($atts, $stylesheet_saved_in_content) {

		// debug($atts);

		$menu = '';
		$menu_slug = isset($atts['menu']) ? $atts['menu'] : '';
		$id = isset($atts['anchor']) && $atts['anchor'] != "" ? 'id="'.$atts['anchor'].'"' : '';

		if ( !empty($menu_slug) ) {

			// check if menu exists
			$menu_obj = wp_get_nav_menus(array('slug' => $menu_slug));
			if ( $menu_obj && isset($menu_obj[0]->term_id) ) {

				$menu = '<div '.$id.' class="block_menu_wrapper">'.
				wp_nav_menu(array(
					'menu' 			=> $menu_obj[0]->term_id,
					'depth' 		=> isset($atts['depth']) ? intval($atts['depth']) : 0,
					'menu_class' 	=> 'menu '.(isset($atts['greydClass']) ? $atts['greydClass'] : ''),
					'fallback_cb' 	=> false,
					'echo' 			=> false,
				)).
				'</div>';
			}
		}
		return $stylesheet_saved_in_content.$menu;
	}



	/**
	 * =================================================================
	 *                          greydStyle
	 * =================================================================
	 */

	public static $css_selectors = array();

	/**
	 * Compose custom css and enqueue.
	 * 
	 * @see helper::compose_css()
	 * @see \Greyd\Enqueue::add_custom_style()
	 *  
	 * @param string $selector   css selector (eg. '.gs_123456', '#my-template')
	 * @param object $styles     greyd styles object
	 * @param array $atts        Additional attributes (deprecated: @param bool $important)
	 *   @property string $path_hover      Path to the hover selector.
	 *   @property string $pseudo_hover    Pseudo class for the hover state.
	 *   @property string $path_active     Path to the active selector.
	 *   @property string $pseudo_active   Pseudo class for the active state.
	 * 
	 * @return bool success
	 */
	public static function enqueue_custom_style( $selector, $styles, $atts=array() ) {
		// debug($atts);

		if ( isset( self::$css_selectors[ $selector ] ) ) {
			return false;
		}
		else {
			self::$css_selectors[ $selector ] = $selector;
		}

		if ( is_string( $styles ) ) {
			$finalCSS = $selector." { ".$styles." } ";
		} else {
			$finalCSS = helper::compose_css( array( '' => $styles ), $selector, $atts );
		}

		return \Greyd\Enqueue::add_custom_style( $finalCSS );
	}
}
