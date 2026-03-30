<?php
/**
 * Register Query Post Table block.
 * 
 * @since 1.2.6
 */
namespace Greyd\Query;

// depends on greyd\blocks\helper
// ::compose_css
use \greyd\blocks\helper as Blocks_Helper;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Post_Table($config);
class Post_Table {

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

		// register blocks
		add_action( 'init', array($this, 'register_blocks'), 99 );

	}

	/**
	 * Blockeditor
	 */
	public function register_blocks() {

		// register script
		wp_register_script(
			'greyd-post-table-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/block-post-table.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-post-table-editor-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
		// register block
		register_block_type( 'greyd/post-table', array(
			'editor_script'  => 'greyd-post-table-editor-script'
		) );
		
	}

	/**
	 * Frontend
	 */
	public static function render( $block, $block_content ) {

		// debug("render post table");
		// debug($block["attrs"]);

		if (
			!is_array($block) ||
			!isset($block["attrs"])
		) {
			return "";
		}

		// default: 1 column
		if ( !isset($block["attrs"]["columns"]) ) $block["attrs"]["columns"] = 1;

		$table_data = array(
			"tbody" => array()
		);
		
		// body
		$query_args  = self::get_query_args($block['attrs']['query']);
		$query = new \WP_Query( $query_args );
		while ($query->have_posts()) {
			// set the post
			$query->the_post();

			$row = array();
			for ($i=0; $i<$block["attrs"]["columns"]; $i++) {
				array_push($row, self::get_cell_data($block, "tbody", $i, true));
			}
			array_push($table_data["tbody"], $row);
		}

		// head
		if (!isset($block["attrs"]["showHead"]) || $block["attrs"]["showHead"] == true) {
			// init data array
			$table_data["thead"] = array();

			$row = array();
			for ($i=0; $i<$block["attrs"]["columns"]; $i++) {
				array_push($row, self::get_cell_data($block, "thead", $i, false));
			}
			array_push($table_data["thead"], $row);
		}

		// foot
		if (!isset($block["attrs"]["showFoot"]) || $block["attrs"]["showFoot"] == true) {
			// init data array
			$table_data["tfoot"] = array();

			$row = array();
			for ($i=0; $i<$block["attrs"]["columns"]; $i++) {
				array_push($row, self::get_cell_data($block, "tfoot", $i, false));
			}
			array_push($table_data["tfoot"], $row);
		}

		// 
		// maybe process data
		// debug($table_data);

		// 
		// render table
		$atts = "";
		if (isset($block["attrs"]["anchor"]) && !empty($block["attrs"]["anchor"])) $atts .= "id='".$block["attrs"]["anchor"]."'";
		if (isset($block["attrs"]["className"]) && !empty($block["attrs"]["className"])) $atts .= " class='".$block["attrs"]["className"]."'";
		$table = "<table ".$atts.">";

			// head
			if (isset($table_data["thead"][0])) {
				$row = self::render_row($table_data["thead"][0], "th", true, $block, $table_data["tbody"]);
				$table .= "<thead>".$row."</thead>";
			}

			// body
			$table .= "<tbody>";
			foreach ($table_data["tbody"] as $row_data) {
				$table .= self::render_row($row_data, "td", false);
			}
			$table .= "</tbody>";

			// foot
			if (isset($table_data["tfoot"][0])) {
				$row = self::render_row($table_data["tfoot"][0], "td", true, $block, $table_data["tbody"]);
				$table .= "<tfoot>".$row."</tfoot>";
			}

		$table .= "</table>";
		// debug($table);

		// 
		// style
		$finalCSS = "";
		for ($i=0; $i<$block["attrs"]["columns"]; $i++) {
			// split column styles
			$column = array();
			if (isset($block["attrs"]["columnStyles"]["width_".$i]) && $block["attrs"]["columnStyles"]["width_".$i] != "") {
				$column["width"] = $block["attrs"]["columnStyles"]["width_".$i];
			}
			if (isset($block["attrs"]["columnStyles"]["hide_".$i]) && $block["attrs"]["columnStyles"]["hide_".$i] === true) {
				$column["display"] = "none";
			}
			// responsive
			if (isset($block["attrs"]["columnStyles"]["responsive"])) {
				$responsive = array();
				foreach (array( 'sm', 'md', 'lg' ) as $breakpoint) {
					if (isset($block["attrs"]["columnStyles"]["responsive"][$breakpoint])) {
						if (isset($block["attrs"]["columnStyles"]["responsive"][$breakpoint]["width_".$i]) && $block["attrs"]["columnStyles"]["responsive"][$breakpoint]["width_".$i] != "") {
							if (!isset($responsive[$breakpoint])) $responsive[$breakpoint] = array();
							$responsive[$breakpoint]["width"] = $block["attrs"]["columnStyles"]["responsive"][$breakpoint]["width_".$i];
						}
						if (isset($block["attrs"]["columnStyles"]["responsive"][$breakpoint]["hide_".$i]) && $block["attrs"]["columnStyles"]["responsive"][$breakpoint]["hide_".$i] === true) {
							if (!isset($responsive[$breakpoint])) $responsive[$breakpoint] = array();
							$responsive[$breakpoint]["display"] = "none";
						}
					}
				}
				if (!empty($responsive)) $column["responsive"] = $responsive;
			}
			// get column style
			if (!empty($column)) {
				$selector = " table th:nth-child(".($i+1)."), table td:nth-child(".($i+1).")";
				$style = Blocks_Helper::compose_css( array( $selector => $column ), '.'.$block["attrs"]["greydClass"], false );
				if ($style != "") $finalCSS .= $style;
			}
		}
		
		// table styles - alignment and colors
		if (isset($block["attrs"]["tableStyles"]["tbody"])) {
			$style = Blocks_Helper::compose_css( array( " table" => $block["attrs"]["tableStyles"]["tbody"] ), '.'.$block["attrs"]["greydClass"], false );
			if ($style != "") $finalCSS .= $style;
		}
		if (isset($block["attrs"]["tableStyles"]["thead"])) {
			$style = Blocks_Helper::compose_css( array( " table thead" => $block["attrs"]["tableStyles"]["thead"] ), '.'.$block["attrs"]["greydClass"], false );
			if ($style != "") $finalCSS .= $style;
			$override = array();
			if (!empty($block["attrs"]["tableStyles"]["thead"]["color"])) $override["color"] = "inherit";
			if (!empty($block["attrs"]["tableStyles"]["thead"]["background"])) $override["background"] = "transparent";
			if (!empty($override)) {
				$style = Blocks_Helper::compose_css( array( " table thead tr th" => $override ), '.'.$block["attrs"]["greydClass"], false );
				if ($style != "") $finalCSS .= $style;
			}
		}
		if (isset($block["attrs"]["tableStyles"]["tfoot"])) {
			$style = Blocks_Helper::compose_css( array( " table tfoot" => $block["attrs"]["tableStyles"]["tfoot"] ), '.'.$block["attrs"]["greydClass"], false );
			if ($style != "") $finalCSS .= $style;
		}

		if ($finalCSS != "") {
			// debug(htmlentities($finalCSS));
			Helper::add_custom_style($finalCSS);
		}

		// reset
		wp_reset_postdata();

		$className = "posts_table";
		if ( !Helper::is_greyd_classic() ) {
			$className .= " wp-block-table";
		}
		$className .= " ".$block["attrs"]["greydClass"];

		return "<div class='{$className}'>".$table."</div>";
	}

	public static function get_cell_data($block, $sectionName, $column, $matchTags) {
		$classes = array();
		if (isset($block["attrs"]["cells"]["align"][$column])) array_push($classes, "has-text-align-".$block["attrs"]["cells"]["align"][$column]);
		if ($sectionName == "thead" && isset($block["attrs"]["cells"]["sortable"][$column]) && $block["attrs"]["cells"]["sortable"][$column] == true) array_push($classes, "sortable");
		$content = isset($block["attrs"]["cells"][$sectionName][$column]) ? rawurldecode($block["attrs"]["cells"][$sectionName][$column]) : "";
		if ($matchTags && $content != "") {
			$content = \Greyd\Dynamic\Render_Blocks::match_dynamic_tags($block, $content);
			if (method_exists('\greyd\blocks\trigger\Render', 'match_trigger')) {
				$content = \greyd\blocks\trigger\Render::match_trigger($content);
			}
			// $content = do_blocks( $content );
		}
		return array(
			"classes" => $classes,
			"content" => $content
		);
	}

	public static function render_row($row_data, $tagName, $matchTags, $block = null, $body_data = null) {
		$row = "";
		for ($i=0; $i<count($row_data); $i++) {
			$cell = $row_data[$i];
			$class = count($cell["classes"]) > 0 ? "class='".implode(" ", $cell["classes"])."'" : "";
			$content = $cell["content"];
			if ($matchTags && $content != "") {
				$content = self::match_dynamic_tags($body_data, $i, $content);
				// $content = \Greyd\Dynamic\Render_Blocks::match_dynamic_tags($block, $content);
			}
			$icon = in_array("sortable", $cell["classes"]) && $tagName == 'th' ? '<span class="icon"><span></span><span></span></span>' : '';
			$row .= "<".$tagName." ".$class.">".$content.$icon."</".$tagName.">";
		}
		return "<tr>".$row."</tr>";
	}

	/**
	 * Match additional Dynamic Tags in Table cells
	 * - col-sum
	 * - col-average
	 * 
	 * @param object $body_data         Array of Rows and Columns of the Table data body
	 * @param int $columnIndex			Index of current column in $body_data
	 * @param string $block_content     pre-rendered Block Content (Table cell)
	 * 
	 * @return string $block_content    altered Block Content (Table cell)
	 */
	public static function match_dynamic_tags( $body_data, $columnIndex, $block_content ) {

		// don't parse dynamic tags in blocks previews
		if ( strpos($_SERVER['REQUEST_URI'], "/wp-json/wp/v2") !== false ) {
			return $block_content;
		}

		// match all dynamic fields
		preg_match_all('/(?<=<span data-tag=")([^"]+)(?=")/', $block_content, $dtags);

		// return if none found
		if ( !isset($dtags[0]) || !count($dtags[0]) ) {
			return $block_content;
		}

		for ( $i=0; $i < count($dtags[0]); $i++ ) {

			$dtag = $dtags[0][$i];

			// only modify table tags
			if ( $dtag != "col-sum" && $dtag != "col-average" ) continue;

			preg_match_all( '/(?<=<span data-tag="'.$dtag.'" data-params=")([^"]*)(?>" class="is-tag">)([^<]*)(?=<\/span>)/', $block_content, $dcontent );

			// no dynamic tags found
			if ( !count($dcontent[0]) ) continue;

			for ( $j=0; $j < count($dcontent[0]); $j++ ) {

				$old          = '<span data-tag="'.$dtag.'" data-params="'.$dcontent[1][$j].'" class="is-tag">'.$dcontent[2][$j].'</span>';
				$sum          = 0;
				$found_number = false;
				$new          = "";


				// get params
				$decimals = 0;
				switch ( Helper::get_language_code() ) {
					case "de":
					case "at":
					case "fr":
						$decimal_separator   = 'comma';
						$thousands_separator = 'dot';
						break;

					default:
						$decimal_separator   = 'dot';
						$thousands_separator = 'space';
				}
				$params = json_decode( stripslashes( html_entity_decode( $dcontent[1][$j] ) ), true );
				if ( !empty( $params ) ) {

					if ( isset($params["count"]) && $params["count"] > -1 ) {
						$decimals = intval( $params["count"] );
					}
					if ( isset($params["format"]) && !empty($params["format"]) ) {
						list( $thousands_separator, $decimal_separator ) = explode( "-", $params["format"] );
					}
				}

				// extract numeric value(s) from each string
				foreach ( $body_data as $row_data ) {

					if ( isset( $row_data[$columnIndex]["content"] ) ) {

						preg_match_all( '(\d+[\.|,|\d]*)', strip_tags( $row_data[$columnIndex]["content"] ), $matches );
						// debug($matches);

						if ( count($matches) > 0 && count($matches[0]) > 0 ) {
							
							// take only first match
							$num = $matches[0][0];

							// replace decimals for correct float conversion
							if ( $thousands_separator === 'dot' ) {
								$num = str_replace('.', '', $num);
							}
							if ( $thousands_separator === 'comma' ) {
								$num = str_replace(',', '', $num);
							}
							if ( $decimal_separator === 'comma' ) {
								$num = str_replace(',', '.', $num);
							}

							$num = floatval( $num );

							if ( is_numeric($num) ) {
								$found_number = true;
								$sum += $num;
							}
						}
					}
				}
				
				// render number (inkl. 0) only if at least some numeric value is found
				if ($found_number) {

					if ($dtag == "col-sum") {
						$new = $sum;
					}
					else if ($dtag == "col-average") {
						$new = $sum / count($body_data);
					}

					// format number
					$chars = array(
						'none' => '',
						'comma' => ',',
						'dot' => '.',
						'space' => '&#8239;'
					);
					$new = number_format(
						$new,
						$decimals,
						$chars[ $decimal_separator ],
						$chars[ $thousands_separator ]
					);
				}
				// replace dtag
				$block_content = str_replace( $old, $new, $block_content );
				$block_content = str_replace( array('<p><p>', '</p></p>' ), array( '<p>', '</p>' ), $block_content );
			}
		}

		return $block_content;
	}

	/**
	 * Get query arguments
	 * 
	 * @return array
	 */
	public static function get_query_args($query) {
		// debug($query);

		$inherit = isset($query['query']['inherit']) && $query['query']['inherit'];
		$paged = 1;
		// if ( $inherit ) {
			global $wp_query;

			$page_key = isset($query['queryId']) ? 'query-'.$query['queryId'].'-page' : 'query-page';
			$paged    = isset($_GET[$page_key]) && !empty($_GET[$page_key]) ? intval($_GET[$page_key]) : 1;
			if ( !$paged ) {
				$paged = $wp_query->paged;
			}
		// }

		// build new query
		if ( class_exists( '\Greyd\Query\Render' ) ) {
			$query_args = \Greyd\Query\Render::build_query( $query, $paged );
		}
		else {
			$query_args = array(
				'post_type'    => 'post',
				'order'        => 'DESC',
				'orderby'      => 'date',
				'post__not_in' => array(),
				'post_status'       => 'publish',
				'suppress_filters'  => false,
			);
		}
		// debug($query_args);

		// fix offset (-1 ignores the offset param)
		// if ( !$inherit ) {
		// 	$query_args['posts_per_page'] = wp_count_posts($query_args['post_type'])->publish;
		// 	if ($query['query']['pages'] != 0 && isset($query['query']['perPage'])) {
		// 		$query_args['posts_per_page'] = intval($query['query']['perPage']) * intval($query['query']['pages']);
		// 	}
		// }
		
		// inherit query args
		if ( $inherit ) {
			
			if ( $wp_query && isset($wp_query->query_vars) && is_array($wp_query->query_vars) ) {
				
				// Unset `offset` because if is set, $wp_query overrides/ignores the paged parameter and breaks pagination.
				unset($query_args['offset']);
				// $query_args = wp_parse_args($wp_query->query_vars, $query_args);
				$query_args = wp_parse_args( $query_args, $wp_query->query_vars );
				// $query_args['paged'] = $paged;
	
				if (empty($query_args['post_type']) && is_singular()) {
					$query_args['post_type'] = get_post_type( get_the_ID() );
				}
				// debug($query_args);
			}
		}

		return $query_args;
	}

}
