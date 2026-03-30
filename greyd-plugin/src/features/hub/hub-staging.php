<?php
/**
 * Greyd.Hub staging features.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Staging($config);
class Staging {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object)$config;

		// init after main hub init
		add_action( 'init', array($this, 'init'), 1 );

	}

	/**
	 * init
	 */
	public function init() {

		// only when there are other sites
		if ( !is_multisite() && !Admin::$connections ) return;


		if ( !self::wp_up_to_date() ) return;

		// monitor options for changes
		add_action( "updated_option", array($this, 'monitor_options'), 10, 3 );

		add_filter( 'greyd_admin_bar_items', array($this, 'add_greyd_admin_bar_item') );
		
		// only backend
		if (is_admin()) {

			// add 'staging' page to hub
			add_filter( "greyd_hub_pages", array($this, 'add_hub_page') );
			add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );
			add_filter( "greyd_hub_log_contents", array($this, 'add_log_contents') );

			// handle post action
			add_action( "greyd_hub_handle_post_data", array($this, 'handle_post_data') );

			// staging wizard
			add_action( 'greyd_hub_page_after_title', array($this, 'render_staging_wizard_button'), 30 );
			add_action( 'greyd_hub_page_after_content', array($this, 'render_staging_wizard'), 10, 2 );

			// extend hub website tile
			add_filter( "greyd_hub_tiles", array($this, 'filter_hub_tiles') );
			add_filter( "greyd_hub_tile", array($this, 'render_hub_tile'), 10, 3 );
			add_filter( "greyd_hub_tile_attributes", array($this, 'render_hub_tile_attributes'), 10, 2 );
			add_filter( "greyd_hub_tile_head", array($this, 'render_hub_tile_head'), 10, 3 );
			add_filter( "greyd_hub_tile_page_action", array($this, 'add_tile_page_action_staging'), 10, 3 );

		}

	}

	/**
	 * Monitor options for changes shown in staging page.
	 * If and option is changed, name and timestamp is saved.
	 * @see action 'updated_option'
	 */
	public function monitor_options($option, $old_value, $value) {

		// debug(array($option, $old_value, $value));

		// don't log transients and blacklisted options
		$skip = array( 'cron', 'blog_public', 'recently_activated', 'protected', 'greyd_staging' );
		if (
			in_array($option, $skip) ||
			strpos($option, "_transient") !== false ||
			strpos($option, "external_updates") !== false
		) {
			return;
		}

		// log option change in staging option
		$staging = get_option('greyd_staging', '0');
		if (is_array($staging) && (isset($staging['is_live']) || isset($staging['is_stage']))) {
			if (!isset($staging['options'])) {
				// init empty
				$staging['options'] = array();
			}
			
			// add
			$staging['options'][$option] = date('Y-m-d H:i:s');
			// debug($staging);
			
			// update
			$result = update_option('greyd_staging', $staging);
			// if ($result) debug("added ".$option." to changes");
		}

	}

	/**
	 * Add staging item to adminbar.
	 * todo: add submenu and info.
	 * @see filter 'greyd_admin_bar_items'
	 */
	public function add_greyd_admin_bar_item($items) {
		// debug($items);

		// in frontend
		if (!is_admin()) {

			$staging = get_option('greyd_staging', '0');
			if (is_array($staging) && (isset($staging['is_live']) || isset($staging['is_stage']))) {

				if (isset($staging['is_live'])) {
					$id = "staging-is-live";
					$title = __("Live site", "greyd_hub");
					$title .= "<style>
						#wp-admin-bar-staging-is-live a.ab-item { background: #B8E6BF; color: #000; }
						#wp-admin-bar-staging-is-live a.ab-item::before { content: \"\\f503\"; color: #000; top: 2px; transition: none; }
					</style>";
				}
				else if (isset($staging['is_stage'])) {
					$id = "staging-is-stage";
					$title = __("Staging site", "greyd_hub");
					$title .= "<style>
						#wp-admin-bar-staging-is-stage a.ab-item { background: #F5E6AB; color: #000; }
						#wp-admin-bar-staging-is-stage a.ab-item::before { content: \"\\f503\"; color: #000; top: 2px; transition: none; }
					</style>";
				}

				// add item
				array_push( $items, array(
					'parent'=> 'top-secondary',
					'id'    => $id,
					'title' => $title,
					'href'  => add_query_arg( "tab", "staging", Admin::$page['url']),
				) );

			}

		}

		return $items;
	}

	/*
	=======================================================================
		Websites Tab extension
	=======================================================================
	*/

	/**
	 * Filter all Blogs (local and remote) and combine Staging pairs (live and stage)
	 * @see filter 'greyd_hub_tiles'
	 * 
	 * @param array  $blogs  All Blogs.
	 *      @property object local      All local Blogs.
	 *      @property object remote     All remote Blogs by network. (optional)
	 * @return array $filtered_blogs
	 */
	public function filter_hub_tiles($blogs) {

		$filtered_blogs = array( 'local' => array() );
		// local blogs
		foreach ($blogs['local'] as $i => $blog) {
			if (isset($blog['attributes']['staging']) && $blog['attributes']['staging'] != "0") {
				$filtered_blogs = $this->add_connected_blog($filtered_blogs, Hub_Helper::get_nice_url(Admin::$urls->network_url), $blog);
			}
			else $filtered_blogs['local'][$i] = $blog;
		}
		if (isset($blogs['remote'])) {
			// remote blogs
			$filtered_blogs['remote'] = array();
			foreach ($blogs['remote'] as $network_url => $remote_blogs) {
				if ( !is_array($remote_blogs) ) {
					// probably WP_Error
					$filtered_blogs['remote'][$network_url] = $remote_blogs;
					continue;
				}
				$filtered_blogs['remote'][$network_url] = array();
				foreach( $remote_blogs as $i => $blog ) {
					if (isset($blog['attributes']['staging']) && $blog['attributes']['staging'] != "0") {
						$filtered_blogs = $this->add_connected_blog($filtered_blogs, $network_url, $blog);
					}
					else $filtered_blogs['remote'][$network_url][$i] = $blog;
				}
			}
		}

		return $filtered_blogs;
	}

	/**
	 * Add Blog as Staging pair (live or stage) to filtered Blogs array.
	 * Instead of single Blog details, the Staging pair object is added:
	 * array(
	 *     "is-staging-pair" => true,
	 *     "live" => 0,
	 *     "stage" => 0,
	 * )
	 * "live" and "stage" are filled in two steps.
	 * If one side stays empty or unresolved, there is an error.
	 * 
	 * @param array $filtered_blogs  Filtered blogs (growing)
	 * @param string $network        Blog network.
	 * @param array $blog            Blog details.
	 * @return array $filtered_blogs
	 */
	public function add_connected_blog($filtered_blogs, $network, $blog) {

		// search already filtered blogs for Staging pair object
		$found = false;
		$route = $network."|".$blog["blog_id"];
		foreach ($filtered_blogs['local'] as $i => $stage) {
			if (!isset($stage['is-staging-pair'])) continue;
			if (is_string($stage["live"]) && $stage["live"] == $route) {
				// add live sibling
				$filtered_blogs['local'][$i]["live"] = $blog;
				$found = true;
				break;
			}
			else if (is_string($stage["stage"]) && $stage["stage"] == $route) {
				// add stage sibling
				$filtered_blogs['local'][$i]["stage"] = $blog;
				$found = true;
				break;
			}
		}
		if (!$found && isset($filtered_blogs['remote'])) {
			foreach ($filtered_blogs['remote'] as $network_url => $remote_blogs) {
				foreach( $remote_blogs as $i => $stage ) {
					if (!isset($stage['is-staging-pair'])) continue;
					if (is_string($stage["live"]) && $stage["live"] == $route) {
						// add live sibling
						$filtered_blogs['remote'][$network_url][$i]["live"] = $blog;
						$found = true;
						break;
					}
					else if (is_string($stage["stage"]) && $stage["stage"] == $route) {
						// add stage sibling
						$filtered_blogs['remote'][$network_url][$i]["stage"] = $blog;
						$found = true;
						break;
					}
				}
				if ($found) break;
			}
		}

		// if not found, make Staging pair object
		if (!$found) {
			$stage = array(
				"is-staging-pair" => true,
				"live" => 0,
				"stage" => 0,
			);
			if (isset($blog['attributes']['staging']['is_live'])) {
				// set live
				$stage['live'] = $blog;
				// set stage (unresolved)
				$stage['stage'] = isset($blog['attributes']['staging']['stage']) ? $blog['attributes']['staging']['stage'] : "";
			}
			else if (isset($blog['attributes']['staging']['is_stage'])) {
				// set live (unresolved)
				$stage['live'] = isset($blog['attributes']['staging']['live']) ? $blog['attributes']['staging']['live'] : "";
				// set stage
				$stage['stage'] = $blog;
			}
			// debug($stage);
			// add Staging pair
			if ($network == Hub_Helper::get_nice_url(Admin::$urls->network_url)) {
				$filtered_blogs['local'][$blog['prefix']] = $stage;
			}
			else {
				$filtered_blogs['remote'][$network][$blog['prefix']] = $stage;
			}
		}

		return $filtered_blogs;
	}

	/**
	 * Render Staging pair (live and stage) with wrapper
	 * instead of single website tiel.
	 * @see filter 'greyd_hub_tile'
	 * 
	 * @param string $tile  The rendered website tile.
	 * @param array $blog   Blog details.
	 * @param string $nonce nonce field.
	 * @return string $tile
	 */
	public function render_hub_tile( $tile, $blog, $nonce ) {

		if (isset($blog["is-staging-pair"])) {

			$tile .= "<div class='greyd_staging_pair'>";
				if (!is_string($blog["live"])) {
					$blog["live"]["staging"] = $blog["live"]['attributes']["staging"];
					if (!is_string($blog["stage"])) {
						$blog["live"]["staging"]["stage_domain"] = $blog["stage"]["domain"];
					}
					else $blog["live"]["stage_error"] = true;
					$tile .= Websites_Page::render_tile( $blog["live"], $nonce );
				}
				if (!is_string($blog["stage"])) {
					$blog["stage"]["staging"] = $blog["stage"]['attributes']["staging"];
					if (!is_string($blog["live"])) {
						$blog["stage"]["staging"]["live_domain"] = $blog["live"]["domain"];
					}
					else $blog["stage"]["stage_error"] = true;
					$tile .= Websites_Page::render_tile( $blog["stage"], $nonce );
				}
			$tile .= "</div>";

		}

		return $tile;
	}

	/**
	 * Add Staging class to Website Tile.
	 * @see filter 'greyd_hub_tile_attributes'
	 * 
	 * @param array $tile_attributes  Array of html attributes (class, data-*, etc).
	 * @param array $blog             Blog details.
	 * @return array $tile_attributes
	 */
	public function render_hub_tile_attributes( $tile_attributes, $blog ) {

		if (isset($blog["staging"]["is_live"])) {
			$tile_attributes["class"] .= " greyd_staging_live";
		}
		if (isset($blog["staging"]["is_stage"])) {
			$tile_attributes["class"] .= " greyd_staging_stage";
			if (!isset($blog["stage_error"])) {
				$tile_attributes["class"] .= " hidden";
			}
		}

		return $tile_attributes;
	}

	/**
	 * Add Staging Bar to head of Website Tile.
	 * @see filter 'greyd_hub_tile_head'
	 * 
	 * @param array $rows   All rows.
	 * @param array $blog   Blog details.
	 * @param array $vars   Blog vars used in tile.
	 * @return array $rows
	 */
	public function render_hub_tile_head( $rows, $blog, $vars ) {

		$class = "greyd_staging_bar";
		$title = __("Switch to %s", "greyd_hub");
		$icon = "randomize";
		if (isset($blog["stage_error"])) {
			$class .= " stage_error";
			$icon = "warning";
			$title = __("Connection error", "greyd_hub");
		}

		if (isset($blog["staging"]["is_live"])) {
			$class .= " live";
			$title = sprintf($title, __("Staging site", "greyd_hub"));
			$rows[] = array(
				'slug' => 'staging',
				'content' =>
					"<div class='".$class."' data-event='switch-staging-version' 
						title='".$title."'>
						".__("Live site", "greyd_hub")." ".Helper::render_dashicon($icon)."
					</div>",
				'priority' => 0
			);
		}
		else if (isset($blog["staging"]["is_stage"])) {
			$class .= " stage";
			$title = sprintf($title, __("Live site", "greyd_hub"));
			$rows[] = array(
				'slug' => 'staging',
				'content' =>
					"<div class='".$class."' data-event='switch-staging-version' 
						title='".$title."'>
						".__("Staging site", "greyd_hub")." ".Helper::render_dashicon($icon)."
					</div>",
				'priority' => 0
			);
		}

		return $rows;
	}

	/**
	 * Add Wizard Button or info to Hub Website Tile Page 'Actions'.
	 * @see filter 'greyd_hub_tile_page_action'
	 * 
	 * @param array $rows	All rows.
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 * @return array $rows
	 */
	public function add_tile_page_action_staging( $rows, $blog, $vars ) {

		if (!empty($blog['attributes']) && isset($blog['attributes']['staging']) ) {

			// staging row
			if ($blog['attributes']['staging'] == "0") {
				// add wizzard button
				$btn = "<span class='button small list_view__hide' data-event='new-stage' data-args='".$blog['network']."|".$blog['blog_id']."' title='"._x("Create staging version", "small", 'greyd_hub')."'><span>".__("Create staging version", 'greyd_hub')."</span>".$vars['icons']->create."</span>";
			}
			else {
				// add staging info
				$route = array();
				$btn = "";
				if (isset($blog["staging"]['is_live'])) {
					if (!empty($blog["staging"]['stage']) && strpos($blog["staging"]['stage'], '|') > 0) {
						$route = self::resolve_route($blog["staging"]['stage']);
						if (!isset($blog["stage_error"])) $btn = "<span class='hub_domain'>
									<b>".__("Connected staging site:", 'greyd_hub')."</b><br>
									<a href='https://".$blog["staging"]['stage_domain']."' target='_blank'>".$blog["staging"]['stage_domain']."</a><br>
									".__("Network:", "greyd_hub")." <b>".$route["network"]."</b><br>
									".__("Blog ID:", "greyd_hub")." <b>".$route["id"]."</b>
								</span>";
					}
				}
				else if (isset($blog["staging"]['is_stage'])) {
					if (!empty($blog["staging"]['live']) && strpos($blog["staging"]['live'], '|') > 0) {
						$route = self::resolve_route($blog["staging"]['live']);
						if (!isset($blog["stage_error"])) $btn = '<span class="hub_domain">
									<b>'.__("Connected live site:", 'greyd_hub').'</b><br>
									<a href="https://'.$blog["staging"]['live_domain'].'" target="_blank">'.$blog["staging"]['live_domain'].'</a><br>
									'.__("Network:", "greyd_hub")." <b>".$route["network"].'</b><br>
									'.__("Blog ID:", "greyd_hub")." <b>".$route["id"].'</b>
								</span>';
					}
				}

				if (isset($blog["stage_error"])) {
					// add error message
					if (empty($route)) {
						$head = __("No connected %s found.", "greyd_hub");
						$text = "";
					}
					else {
						$head = __("Connected %s was not found.", "greyd_hub");
						$text = __("Network:", "greyd_hub")." <b>".$route["network"]."</b><br>".__("Blog ID:", "greyd_hub")." <b>".$route["id"]."</b><br><br>";
					}
					if (isset($blog["staging"]['is_live'])) {
						$head = sprintf($head, __("Staging site", "greyd_hub"));
					}
					else if (isset($blog["staging"]['is_stage'])) {
						$head = sprintf($head, __("Live site", "greyd_hub"));
					}
					// link to staging connections
					$text .= '<a class="button small button-dark" title="'._x("Repair connection", "small", 'greyd_hub').'" href="'.add_query_arg( "tab", "staging" ).'"><span>'._x("Repair connection", "small", 'greyd_hub').'</span></a>';

					$btn = Helper::render_info_box([
						"style" => "danger",
						"above"  => $head,
						"text"  => $text,
					]);
				}

			}

			// add button in new row
			$rows[] = array(
				'slug' => 'staging',
				'class' => 'list_view__hide',
				'content' =>
					"<div class='flex inner_head'>".
						"<p>".__("Staging", 'greyd_hub')."</p>".
						"<small></small>".
					"</div>".
					$btn,
				'priority' => 99
			);
	
		}

		return $rows;
	}

	/*
	=======================================================================
		helper functions
	=======================================================================
	*/

	/**
	 * Resolve Staging route of format {network}|{blogid}
	 * 
	 * @param string $route
	 * @return array|false
	 * 		@property string network
	 * 		@property int id
	 */
	public static function resolve_route($route) {

		$result = false;
		// already resolved
		if (is_array($route)) {
			if (isset($route["network"]) && isset($route["id"])) {
				$result = $route;
			}
			else if (count($route) == 2) {
				$result = array( "network" => $result[0], "id" => intval($result[1]) );
			}
		}
		// resolve
		else if (!empty($route) && strpos($route, "|") !== false && $route != "|") {
			$result = explode('|', $route);
			if (count($result) == 2) {
				$result = array( "network" => $result[0], "id" => intval($result[1]) );
			}
		}

		if ( $result && isset( $result["network"] ) ) {
			$result["network"] = Hub_Helper::get_nice_url( $result["network"] );
		}

		return $result;

	}

	/**
	 * Connect Blog to staging by route (local or remote).
	 * 
	 * @param string|array $route  Staging route.
	 * @param string $mode         Staging mode ("live" or "stage").
	 * @param string $related      Related site ("{network}|{blogid}").
	 * @return bool
	 */
	public static function connect($route, $mode, $related) {

		$route = self::resolve_route($route);
		if ($route === false) return false;

		$current_network_url = Hub_Helper::get_nice_url(Admin::$urls->network_url);

		$result = false;
		if ( $route["network"] == $current_network_url ) {
			// connect here
			$result = Hub_Helper::connect_blog( $route["id"], $mode, $related );
		}
		else if (
			Admin::$connections
			&& isset(Admin::$connections[$route["network"]])
			&& method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' )
		) {
			// connect there
			$connection = Admin::$connections[$route["network"]];
			$result = \Greyd\Connections\Connections_Helper::send_request( $connection, "remote_blogs/".$route["id"]."/connect/".$mode."/".$related );
		}

		return $result;

	}

	/**
	 * Disconnect Blog from staging by route (local or remote).
	 * 
	 * @param string|array $route  Staging route.
	 * @param string $mode         Staging mode ("live" or "stage").
	 * @return bool
	 */
	public static function disconnect($route, $mode) {

		$route = self::resolve_route($route);
		if ($route === false) return false;

		$result = false;
		if ($route["network"] == Hub_Helper::get_nice_url(Admin::$urls->network_url)) {
			// disconnect here
			$result = Hub_Helper::disconnect_blog( $route["id"] );
		}
		else if (
			Admin::$connections
			&& isset(Admin::$connections[$route["network"]])
			&& method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' )
		) {
			// disconnect there
			$connection = Admin::$connections[$route["network"]];
			$result = \Greyd\Connections\Connections_Helper::send_request( $connection, "remote_blogs/".$route["id"]."/disconnect" );
		}

		return $result;

	}

	/**
	 * Get Blog changes since a given date by route (local or remote).
	 * 
	 * @param string|array $route       Staging route.
	 * @param string $datetime          Date and time (format: 'Y-m-d H:i:s').
	 * @return array $changes
	 *      @property array posts       Changed posts (from date_query).
	 *      @property array design      Changed design (from date_query and customize_changeset).
	 *      @property array options     Changed options (from 'greyd_staging' option).
	 */
	public static function get_changes($route, $datetime) {
		
		$route = self::resolve_route($route);
		if ($route === false) return false;

		$result = false;
		if ($route["network"] == Hub_Helper::get_nice_url(Admin::$urls->network_url)) {
			// get changes here
			$result = Hub_Helper::get_changes( $route["id"], $datetime );
		}
		else if (
			Admin::$connections
			&& isset(Admin::$connections[$route["network"]])
			&& method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' )
		) {
			// get changes there
			$connection = Admin::$connections[$route["network"]];
			$result = \Greyd\Connections\Connections_Helper::send_request( $connection, "remote_blogs/".$route["id"]."/changes/".rawurlencode($datetime) );
		}

		return (array)$result;

	}

	/**
	 * Render a redirect message in the log after post action to remote hub.
	 */
	public static function maybe_redirect() {
		
		// display notice to go back to last hub url
		if ( isset($_POST["staging_redirect"]) && strpos( $_POST["staging_redirect"], Hub_Helper::get_current_url() ) !== 0 ) {
			$staging_redirect   = remove_query_arg( "tab", $_POST["staging_redirect"] );
			$redirect_label = str_replace( ["http://", "https://", "/wp-admin/network/admin.php?page=greyd_hub", "/wp-admin/admin.php?page=greyd_hub"], "", $staging_redirect )." > ".__("Greyd.Hub");
			Log::log_message(
				"<script> var redirectTimeout = setTimeout(function(){ window.location.replace('".$_POST['staging_redirect']."'); }, 5000);</script>".
				"<h5>".sprintf( __("Redirect to: %s", 'greyd_hub'), "<a href='".$staging_redirect."'>".$redirect_label."</a>" )."</h5>".
				"<p>".sprintf( __("You are in the Greyd.Hub of the page %s. You will automatically return to the previous Greyd.Hub in 5 seconds.", 'greyd_hub'), "<strong>".get_site_url()."</strong>" ).
				"</p><span class='button button-primary' onclick='clearTimeout(redirectTimeout), $(this).remove();'>".__("Cancel redirect", "greyd_hub")."</span>",
			'info' );
		}

	}

	/**
	 * Get some options to be saved before staging push or pull.
	 * ('blog_public', 'protected' and 'greyd_staging')
	 * 
	 * @param string $blogid    Blog ID.
	 * @return array $options   Array of options.
	 */
	public static function get_options($blogid) {

		if (is_multisite()) switch_to_blog($blogid);

			// get options
			$options = array(
				'blog_public' => get_option('blog_public', '0'),
				'protected' => get_option('protected', '0'),
				'greyd_staging' => get_option('greyd_staging', '0'),
				'active_plugins' => get_option('active_plugins', '0')
			);
			/**
			 * Filter options to be saved before staging push or pull.
			 * @since 2.15.0
			 * 
			 * @param array $options    Array of saved options.
			 * @param string $blogid    Blog ID.
			 * @return array $options
			 */
			$options = apply_filters( 'greyd_staging_preserved_options', $options, $blogid );

		if (is_multisite()) restore_current_blog();

		return $options;

	}

	/**
	 * Set options on db-level (sql) after staging push or pull.
	 * 'greyd_staging' will be update with a log, changes are reset.
	 * 
	 * @param string $blogid    Blog ID.
	 * @param array $options    Array of options.
	 * @param string $log       Log mode if 'greyd_staging' is updated
	 */
	public static function set_options($blogid, $options, $log="") {
		
		// update staging option
		if (isset($options['greyd_staging']) && is_array($options['greyd_staging'])) {
			// convert dev property to array
			if (is_string($options['greyd_staging']['updated'])) {
				$options['greyd_staging']['updated'] = array( $options['greyd_staging']['updated'] );
			}
			// update last change
			$options['greyd_staging']['updated'][] = date('Y-m-d H:i:s')."|".$log;
			// reset options log on pull and push
			if (isset($options['greyd_staging']['options'])) {
				$tmp = explode("|", $log);
				if (in_array($tmp[0], array( 'pull', 'push' ))) {
					unset($options['greyd_staging']['options']);
				}
			}
		}

		// create options from old data
		$options_queries = array();
		foreach ($options as $option => $value) {
			// debug("set option ".$option);
			// debug($value);
			$values = maybe_serialize($value);
			array_push($options_queries, "('{$option}', '{$values}')" );
		}
		if (count($options_queries) > 0) {
			global $wpdb;
			// get options table name
			$prefix = $wpdb->base_prefix;
			if ($blogid > 1) $prefix .= $blogid."_";
			$options_table = "`".$prefix."options`";
			// make query
			$query = "REPLACE INTO {$options_table} (`option_name`, `option_value`) VALUES ".implode(', ', $options_queries).";";
			$result = SQL::import_queries( array( $query ), true);
		}

	}

	/**
	 * Make backup (download) of a blog.
	 * If blog is in own network, call Tools function, otherwise make rest call.
	 * 
	 * @param array $route				Staging route of blog
	 * 		@property string network
	 * 		@property int id
	 * @param string $domain			Domain of the selected blog.
	 * @return string|false	$filename	url of backup file or false.
	 */
	public static function download_blog($route, $domain) {

		if ( $route["network"] == Hub_Helper::get_nice_url(Admin::$urls->network_url) ) {
			// make download here
			return Tools::download_blog( array(
				'mode' => 'content',
				'blogid' => $route["id"], 
				'domain' => $domain, 
				'trigger' => false,
				'clean' => false,
				'unique' => true,
				'log' => false
			) );
		}
		else if ( Admin::$connections && isset( Admin::$connections[ $route["network"] ] ) ) {
			// make download there
			$connection = Admin::$connections[ $route["network"] ];
			if ( method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' ) ) {
				return \Greyd\Connections\Connections_Helper::send_request(
					$connection,
					"remote_blogs/".$route["id"]."/dump/content",
					array( "clean" => false ),
					"GET",
					array( 'timeout' => apply_filters( 'greyd_hub_process_set_timeout', 3600 ) )
				);
			}
			else {
				// Log::log_abort( __("Connection feature needs to be enabled", 'greyd_hub') );
				return false;
			}
		}
	}

	/*
	=======================================================================
		post actions
	=======================================================================
	*/

	/**
	 * Handle Post data - page and wizard.
	 * @see action 'greyd_hub_handle_post_data'
	 */
	public function handle_post_data( $post_data ) {

		// wizard
		if ($post_data['submit'] == "new-stage") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to create staging connections.", 'greyd_hub' ), "connect");
				return false;
			}

			// get data
			$args = wp_parse_args( $post_data, array(
				"mode"          => "",      // "link"|"create"
				// live
				"live"          => "",      // "{network}|{blogid}"
				"live-domain"   => "",
				"live-title"    => "",
				"live-description" => "",
				// stage
				"stage"         => "",      // "{network}|{blogid}"
				"stage-address" => "",
				"stage-suggest" => "",
				"stage-domain"  => "",
				"stage-title"   => "",
				"stage-description" => "",
				// options
				"stage-protect"    => "off",   // "off"|"on"
				"protect-mode"     => "user",  // "user"|"admin"|"pass"
				"protect-password" => "",
				"stage-clone"      => "off",   // "off"|"on"
				"stage-backup"     => "off",   // "off"|"on"
				// constants
				"lang"          => get_site_option( 'WPLANG' ),
				"user"          => get_current_user_id(),
			) );
			// debug($args);

			// check data
			if ($args['mode'] != "create" && $args['mode'] != "link" ) {
				Log::log_abort(__( "Invalid connection.", 'greyd_hub' ), "connect");
				return false;
			}

			// check live
			$live = self::resolve_route($args['live']);
			if ($live === false) {
				// abort
				$msg = "<p>".sprintf(__("The selected live site \"%s\" is not available.", 'greyd_hub'), "<b>".$args['live-domain']."</b>")."</p>";
				Log::log_abort("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>".$msg, "connect");
				return false;
			}
			
			// render logging overlay
			Log::log_start( "connect loading" );

			// create stage
			if ( $args['mode'] == "create" ) {
				// make options
				$address = !empty($args['stage-address']) ? $args['stage-address'] : $args['stage-suggest'];
				$title = $args["live-title"];
				$options = array( 'WPLANG' => $args["lang"] );
				if (!empty($args["live-description"])) $options["blogdescription"] = $args["live-description"];
				// create new blog
				$newblog = Tools::create_blog( array(
					"domain" => $address,
					"title" => $title,
					"options" => $options,
					"user_id" => $args["user"],
					"init_log" => false,
					"log" => true,
				) );
				// check
				if ( !is_wp_error($newblog) ) {
					switch_to_blog( $newblog );
						$args["stage"] = Hub_Helper::get_nice_url(Admin::$urls->network_url)."|".$newblog;
						$args["stage-domain"] = get_bloginfo('url');
						$args["stage-title"] = get_bloginfo('name');
						$args["stage-description"] = get_bloginfo('description');
					restore_current_blog();
				}
			}
			
			// check stage
			$stage = self::resolve_route($args['stage']);
			if ($stage === false) {
				// abort
				$msg = "<p>".sprintf(__("The selected staging site \"%s\" is not available or could not be created.", 'greyd_hub'), "<b>".$args['stage-domain']."</b>")."</p>";
				Log::log_abort("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>".$msg, "connect");
				return false;
			}
			
			// create stage backup
			if ( $args['mode'] == "link" && $args['stage-backup'] == "on" ) {
				Log::log_message(sprintf(__("Backup of the staging site \"%s\" is created.", 'greyd_hub'), $args["stage-domain"]), 'info');
				$filename = self::download_blog($stage, $args["stage-domain"]);
				Log::log_message($filename, 'debug');
			}
				
			// create live backup
			if ( $args['stage-clone'] == "off" ) {
				Log::log_message(sprintf(__("Backup of the live site \"%s\" is created.", 'greyd_hub'), $args["live-domain"]), 'info');
				$filename = self::download_blog($live, $args["live-domain"]);
				Log::log_message($filename, 'debug');
			}

			// clone live -> stage
			if ( $args['stage-clone'] == "on" ) {
				Log::log_message(sprintf(__("Import of live site \"%s\" to staging site \"%s\" is started.", 'greyd_hub'), $args["live-domain"], $args["stage-domain"]), 'info');
				$result = Tools::import_blog( array(
					'mode' => 'content', 
					'blogid' => $stage["id"], 
					'domain' => $args["stage-domain"], 
					'source' => $args['live'], 
					'clean' => false,
					'init_log' => false,
					"log" => true,
				) );
			}

			// set protect
			if ( $args['stage-protect'] == "on" ) {
				Log::log_message(sprintf(__("Protection of the staging site \"%s\" is established.", 'greyd_hub'), $args["stage-domain"]), 'info');
				$value = "0";
				if ($args['protect-mode'] == "user") $value = "1";
				else if ($args['protect-mode'] == "admin") $value = "2";
				else if ($args['protect-mode'] == "pass") {
					$pw = wp_hash( $args['protect-password'].'blogaccess' );
					$value = "3::".$pw;
				}
				self::set_options($stage["id"], array(
					'protected' => $value
				));
			}

			// connect sites
			Log::log_message("<h5>".sprintf(__("Connection between \"%s\" and \"%s\" is established.", 'greyd_hub'), $args['live-domain'], $args['stage-domain'])."</h5>", 'info');

			// connect live
			Log::log_message(sprintf(__("\"%s\" is linked as a live site.", 'greyd_hub'), $args['live-domain']), 'info');
			$result_live = self::connect($live, 'live', $args['stage']);
			if ($result_live === false) {
				// failed
				Log::log_message(sprintf(__("\"%s\" could not be connected.", 'greyd_hub'), $args['live-domain']), 'danger');
			}

			// connect stage
			Log::log_message(sprintf(__("\"%s\" is linked as a staging site.", 'greyd_hub'), $args['stage-domain']), 'info');
			$result_stage = self::connect($stage, 'stage', $args['live']);
			if ($result_stage === false) {
				// failed
				Log::log_message(sprintf(__("\"%s\" could not be connected.", 'greyd_hub'), $args['stage-domain']), 'danger');
			}

			// result
			$result = "";
			if ($result_live && $result_stage) {
				// success
				$result = "success";
				Log::log_message("<h5>".__("Staging connection successfully established.", 'greyd_hub')."</h5>", 'success');
			}
			else {
				// fail
				$result = "fail";
				$msg = "";
				if ($result_live === false) {
					$msg .= "<p>".sprintf(__("An error occurred while connecting the site \"%s\".", 'greyd_hub'), "<b>".$args['live-domain']."</b>")."</p>";
					// cleanup
					if ($result_stage) {
						// disconnect stage
						$msg .= "<p>".sprintf(__("Staging site \"%s\" is disconnected.", 'greyd_hub'), $args['stage-domain'])."</p>";
						self::disconnect($stage, 'stage');
					}
				}
				if ($result_stage === false) {
					$msg .= "<p>".sprintf(__("An error occurred while connecting the site \"%s\".", 'greyd_hub'), "<b>".$args['stage-domain']."</b>")."</p>";
					// cleanup
					if ($result_live) {
						// disconnect live
						$msg .= "<p>".sprintf(__("Live site \"%s\" is disconnected.", 'greyd_hub'), $args['live-domain'])."</p>";
						self::disconnect($live, 'live');
					}
				}
				// error msg
				Log::log_message("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>".$msg, 'danger');
			}

			// check if redirect to other hub is active
			self::maybe_redirect();

			// close overlay
			Log::log_end('connect '.$result);

		}

		// staging page
		else if ($post_data['submit'] == "stage-disconnect") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to disconnect staging connections.", 'greyd_hub' ), "disconnect");
				return false;
			}

			$args = wp_parse_args( $post_data, array(
				"disconnect_live"  => "",      // "{network}|{blogid}"
				"live-domain"      => "",
				"live-title"       => "",
				"disconnect_stage" => "",      // "{network}|{blogid}"
				"stage-domain"     => "",
				"stage-title"      => "",
			) );

			// render logging overlay
			Log::log_start( "disconnect loading" );
			Log::log_message("<h5>".sprintf(__("Connection between \"%s\" and \"%s\" is removed.", 'greyd_hub'), $args['live-domain'], $args['stage-domain'])."</h5>", 'info');

			// disconnect live
			$result_live = true;
			$live = self::resolve_route($args['disconnect_live']);
			if ($live === false) {
				// maybe a false connection where live is not found - just info
				Log::log_message(__("No connected live site found.", 'greyd_hub'), 'info');
			}
			else {
				// valid live
				Log::log_message(sprintf(__("Live site \"%s\" is disconnected.", 'greyd_hub'), $args['live-domain']), 'info');
				$result_live = self::disconnect($live, 'live');
				if ($result_live === false) {
					// failed
					Log::log_message(sprintf(__("Live site \"%s\" could not be disconnected.", 'greyd_hub'), $args['live-domain']), 'danger');
				}
			}
			
			// disconnect stage
			$result_stage = true;
			$stage = self::resolve_route($args['disconnect_stage']);
			if ($stage === false) {
				// maybe a false connection where stage is not found - just info
				Log::log_message(__("No connected staging site found.", 'greyd_hub'), 'info');
			}
			else {
				// valid stage
				Log::log_message(sprintf(__("Staging site \"%s\" is disconnected.", 'greyd_hub'), $args['stage-domain']), 'info');
				$result_stage = self::disconnect($stage, 'stage');
				if ($result_stage === false) {
					// failed
					Log::log_message(sprintf(__("Staging site \"%s\" could not be disconnected.", 'greyd_hub'), $args['stage-domain']), 'danger');
				}

			}

			// result
			if ($result_live && $result_stage) {
				// success
				Log::log_message("<h5>".__("Staging connection successfully disconnected.", 'greyd_hub')."</h5>", 'success');
				// close overlay
				Log::log_end('disconnect success');
			}
			else {
				// fail
				$msg = "";
				if ($result_live === false) {
					$msg .= "<p>".sprintf(__("An error occurred while disconnecting the live site \"%s\".", 'greyd_hub'), "<b>".$args['live-domain']."</b>")."</p>";
				}
				if ($result_stage === false) {
					$msg .= "<p>".sprintf(__("An error occurred while disconnecting the \"%s\" staging site.", 'greyd_hub'), "<b>".$args['stage-domain']."</b>")."</p>";
				}
				// error msg
				Log::log_message("<h5>".__("The staging connection could not be disconnected.", 'greyd_hub')."</h5>".$msg, 'danger');
				// close overlay
				Log::log_end('disconnect fail');
			}

		}
		else if ($post_data['submit'] == "stage-push") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to modify stages.", 'greyd_hub' ), "push");
				return false;
			}

			// get data
			$args = wp_parse_args( $post_data, array(
				"push_live"        => "",      // "{network}|{blogid}"
				"live-domain"      => "",
				"live-title"       => "",
				"live-mods"        => "",
				"push_stage"       => "",      // "{network}|{blogid}"
				"stage-domain"     => "",
				"stage-title"      => "",
				"stage-mods"       => "",
				// data
				"push_data"        => "",      // "design"|"db"|"files" (comma seperated)
			) );

			// check live
			$live = self::resolve_route($args['push_live']);
			if ($live === false) {
				// abort
				Log::log_abort(sprintf(__("The live site \"%s\" is not available.", 'greyd_hub'), "<b>".$args['live-domain']."</b>"), "push");
				return false;
			}
			
			// check stage
			$stage = self::resolve_route($args['push_stage']);
			if ($stage === false) {
				// abort
				Log::log_abort(sprintf(__("The \"%s\" staging site is not available.", 'greyd_hub'), "<b>".$args['stage-domain']."</b>"), "push");
				return false;
			}
			
			// render logging overlay
			Log::log_start( "push loading" );
			Log::log_message("<h5>".sprintf(__("Changes on the staging site \"%s\" are prepared.", 'greyd_hub'), $args['stage-domain'])."</h5>", 'info');

			// check data
			$args['push_data'] = explode(',', $args['push_data']);
			$data = array();
			if (!empty($args['push_data'])) {
				foreach ($args['push_data'] as $mode) {
					if (!in_array($mode, array("design", "db", "files"))) {
						// just info
						Log::log_message(sprintf(__("Invalid push mode %s.", 'greyd_hub'), $mode), 'info');
						continue;
					}
					$data[] = $mode;
				}
			}
			if (empty($data)) {
				Log::log_abort(__( "Invalid push.", 'greyd_hub' ), "push");
				return false;
			}

			// valid push
			if (count($data) == 1) {
				$msg = sprintf(__("%s is", 'greyd_hub'), $data[0]);
			}
			else {
				$modes = $data;
				$last = array_pop($modes);
				$msg = sprintf(__("%s and %s are", 'greyd_hub'), implode(', ', $modes), $last);
			}
			$msg = str_replace(
				array( 'design', 'db', 'files' ),
				array( __("Design", 'greyd_hub'), __("Database", 'greyd_hub'), __("Media", 'greyd_hub') ),
				$msg
			);
			Log::log_message("<h5>".sprintf(__("%s pushed from \"%s\" to the live site \"%s\".", 'greyd_hub'), $msg, $args['stage-domain'], $args['live-domain'])."</h5>", 'info');

			// create live backup
			Log::log_message(sprintf(__("Backup of the live site \"%s\" is created.", 'greyd_hub'), $args["live-domain"]), 'info');
			$filename = self::download_blog($live, $args["live-domain"]);
			Log::log_message($filename, 'debug');

			// save some options
			Log::log_message(__("Staging options are saved.", 'greyd_hub'), 'info');
			$options = self::get_options($live["id"]);
			
			// decode thememods
			$args['live-mods'] = json_decode(urldecode($args['live-mods']), true);
			// debug($args['live-mods']);
			$args['stage-mods'] = json_decode(urldecode($args['stage-mods']), true);
			// debug($args['stage-mods']);

			// save thememods option
			if (!in_array("design", $data)) {
				// save thememods option if design is not pushed
				// debug("keep thememods");
				$options[$args['live-mods']['option']] = $args['live-mods']['mods'];
			}
			if (in_array("design", $data) && !in_array("db", $data)) {
				// get thememods option from stage if db is not pushed
				// debug("push thememods");
				$replace = array(
					'old' => array( 'upload_url' => $args['stage-mods']['upload_url'] ),
					'new' => array( 'upload_url' => $args['live-mods']['upload_url'] )
				);
				$mods = Hub_Helper::url_replace(json_encode($args['stage-mods']['mods']), $replace);
				$options[$args['live-mods']['option']] = json_decode($mods, true);
			}
			// debug($options);

			// prepare push
			$import_args = array(
				'mode' => '', 
				'blogid' => $live['id'], 
				'domain' => $args["live-domain"], 
				'source' => $args['push_stage'], 
				'clean' => false,
				'init_log' => false,
				"log" => true,
			);
			if (in_array("db", $data) && in_array("files", $data)) {
				// full
				$import_args['mode'] = 'content';
			}
			else if (in_array("db", $data)) {
				// only db
				$import_args['mode'] = 'db';
			}
			else if (in_array("files", $data)) {
				// only files
				$import_args['mode'] = 'files';
			}

			// do the push
			$result_push = true;
			if ($import_args['mode'] != '') {
				// clone stage -> live
				$result_push = Tools::import_blog( $import_args );
			}
			if (in_array("design", $data)) {
				// log design push - done with options
				Log::log_message("<h5>".__("Design is imported.", 'greyd_hub')."</h5>", 'info');
			}

			// set saved options
			Log::log_message(__("Saved options are restored.", 'greyd_hub'), 'info');
			self::set_options($live["id"], $options, 'push|'.implode(',', $data));

			// result
			$result = "";
			if ($result_push) {
				// success
				$result = "success";
				Log::log_message("<h5>".sprintf(__("Changes from \"%s\" were successfully transferred to the live site \"%s\".", 'greyd_hub'), $args['stage-domain'], $args['live-domain'])."</h5>", 'success');
			}
			else {
				// fail
				$result = "fail";
				Log::log_message("<h5>".sprintf(__("Changes from \"%s\" could not be transferred to the live site \"%s\".", 'greyd_hub'), $args['stage-domain'], $args['live-domain'])."</h5>", 'danger');
			}

			// check if redirect to other hub is active
			self::maybe_redirect();

			// close overlay
			Log::log_end('push '.$result);

		}
		else if ($post_data['submit'] == "stage-pull") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to modify stages.", 'greyd_hub' ), "reset");
				return false;
			}

			// get data
			$args = wp_parse_args( $post_data, array(
				"pull_live"        => "",      // "{network}|{blogid}"
				"live-domain"      => "",
				"live-title"       => "",
				"pull_stage"       => "",      // "{network}|{blogid}"
				"stage-domain"     => "",
				"stage-title"      => "",
			) );

			// check live
			$live = self::resolve_route($args['pull_live']);
			if ($live === false) {
				// abort
				Log::log_abort(sprintf(__("The live site \"%s\" is not available.", 'greyd_hub'), "<b>".$args['live-domain']."</b>"), "pull");
				return false;
			}
			
			// check stage
			$stage = self::resolve_route($args['pull_stage']);
			if ($stage === false) {
				// abort
				Log::log_abort(sprintf(__("The \"%s\" staging site is not available.", 'greyd_hub'), "<b>".$args['stage-domain']."</b>"), "pull");
				return false;
			}
			
			// render logging overlay
			Log::log_start( "pull loading" );
			Log::log_message("<h5>".sprintf(__("Import of live site \"%s\" to staging site \"%s\" is started.", 'greyd_hub')."</h5>", $args["live-domain"], $args["stage-domain"]), 'info');
			
			// create stage backup
			Log::log_message(sprintf(__("Backup of the staging site \"%s\" is created.", 'greyd_hub'), $args["stage-domain"]), 'info');
			$filename = self::download_blog($stage, $args["stage-domain"]);
			Log::log_message($filename, 'debug');

			// save some options
			Log::log_message(__("Staging options are saved.", 'greyd_hub'), 'info');
			$options = self::get_options($stage["id"]);

			// clone live -> stage
			$result_import = Tools::import_blog( array(
				'mode' => 'content', 
				'blogid' => $stage['id'], 
				'domain' => $args["stage-domain"], 
				'source' => $args['pull_live'], 
				'clean' => false,
				'init_log' => false,
				"log" => true,
			) );

			// set saved options
			Log::log_message(__("Saved options are restored.", 'greyd_hub'), 'info');
			self::set_options($stage["id"], $options, 'pull');

			// result
			$result = "";
			if ($result_import) {
				// success
				$result = "success";
				Log::log_message("<h5>".sprintf(__("The live site \"%s\" was successfully imported to the staging site \"%s\".", 'greyd_hub'), $args["live-domain"], $args["stage-domain"])."</h5>", 'success');
			}
			else {
				// fail
				$result = "fail";
				Log::log_message("<h5>".sprintf(__("The live site \"%s\" could not be imported to the staging site \"%s\".", 'greyd_hub'), $args["live-domain"], $args["stage-domain"])."</h5>", 'danger');
			}

			// check if redirect to other hub is active
			self::maybe_redirect();

			// close overlay
			Log::log_end('pull '.$result);

		}
		else if ($post_data['submit'] == "stage-reset") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to modify stages.", 'greyd_hub' ), "reset");
				return false;
			}

			// get data
			$args = wp_parse_args( $post_data, array(
				"reset_live"       => "",      // "{network}|{blogid}"
				"live-domain"      => "",
				"live-title"       => "",
			) );

			// check live
			$live = self::resolve_route($args['reset_live']);
			if ($live === false) {
				// abort
				Log::log_abort(sprintf(__("The live site \"%s\" is not available.", 'greyd_hub'), "<b>".$args['live-domain']."</b>"), "reset");
				return false;
			}

			// get backup files
			$backup_domain = Hub_Helper::printable_domainname( $args["live-domain"] )."_content";
			$files = Hub_Helper::get_backup_files(false);
			// debug($files);
			$backup_files = array();
			foreach ($files as $files_by_date) {
				foreach ($files_by_date as $file) {
					if (strpos($file["name"], $backup_domain) === 0) {
						$backup_files[] = $file;
					}
				}
			}
			if ( count( $backup_files ) == 0 ) {
				// abort
				Log::log_abort(sprintf(__("No backups of the live site \"%s\" were found.", 'greyd_hub'), "<b>".$args['live-domain']."</b>"), "reset");
				return false;
			}

			// use first (newest) backup file
			$file = $backup_files[0];

			// render logging overlay
			Log::log_start( "reset loading" );
			Log::log_message("<h5>".sprintf(__("Backup from %s is imported to the live site \"%s\".", 'greyd_hub'), $file['date'], $args['live-domain'])."</h5>", 'info');
			
			// save some options
			Log::log_message(__("Staging options are saved.", 'greyd_hub'), 'info');
			$options = self::get_options($live["id"]);

			// import backup
			$result_import = Tools::import_blog( array(
				'mode' => 'content', 
				'blogid' => $live["id"], 
				'domain' => $args["live-domain"], 
				'source' => $file["url"], 
				'clean' => false,
				'init_log' => false,
				"log" => true,
			) );

			// set saved options
			Log::log_message(__("Saved options are restored.", 'greyd_hub'), 'info');
			self::set_options($live["id"], $options, 'reset');

			// result
			$result = "";
			if ($result_import) {
				// success
				$result = "success";
				Log::log_message("<h5>".sprintf(__("Backup from %s was imported successfully.", 'greyd_hub'), $file['date'])."</h5>", 'success');
			}
			else {
				// fail
				$result = "fail";
				Log::log_message("<h5>".__("Backup could not be imported.", 'greyd_hub')."</h5>", 'danger');
			}

			// check if redirect to other hub is active
			self::maybe_redirect();

			// close overlay
			Log::log_end('reset '.$result);

		}
		else if ($post_data['submit'] == "stage-reconnect") {

			// debug($post_data);

			// check cap
			if ( !current_user_can( Admin::$page['cap'] ) ) {
				Log::log_abort(__( "You are unfortunately not allowed to create staging connections.", 'greyd_hub' ), "connect");
				return false;
			}

			// get data
			$args = wp_parse_args( $post_data, array(
				"reconnect_mode" => "",      // "stage"|"live"
				// live
				"reconnect_live" => "",      // "{network}|{blogid}"
				"live-domain"    => "",
				// stage
				"reconnect_stage" => "",      // "{network}|{blogid}"
				"stage-domain"    => "",
			) );
			// debug($args);

			// check data
			if ($args['reconnect_mode'] != "stage" && $args['reconnect_mode'] != "live" ) {
				Log::log_abort(__( "Invalid connection.", 'greyd_hub' ), "connect");
				return false;
			}

			// check live
			if (self::resolve_route($args['reconnect_live']) === false) {
				// abort
				$msg = "<p>".__("The live site is not available.", 'greyd_hub')."</p>";
				Log::log_abort("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>".$msg, "connect");
				return false;
			}
			
			// check stage
			if (self::resolve_route($args['reconnect_stage']) === false) {
				// abort
				$msg = "<p>".__("The staging site is not available.", 'greyd_hub')."</p>";
				Log::log_abort("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>".$msg, "connect");
				return false;
			}
			
			// render logging overlay
			Log::log_start( "connect loading" );

			if ($args['reconnect_mode'] == "stage") {
				// reconnect stage
				Log::log_message(__("Staging site is reconnected.", 'greyd_hub'), 'info');
				$result_reconnect = self::connect($args['reconnect_stage'], 'stage', $args['reconnect_live']);
			}
			else if ($args['reconnect_mode'] == "live") {
				// reconnect live
				Log::log_message(__("Live site is reconnected.", 'greyd_hub'), 'info');
				$result_reconnect = self::connect($args['reconnect_live'], 'live', $args['reconnect_stage']);
			}

			// result
			$result = "";
			if ($result_reconnect) {
				// success
				$result = "success";
				Log::log_message("<h5>".__("Staging connection successfully established.", 'greyd_hub')."</h5>", 'success');
			}
			else {
				// fail
				$result = "fail";
				Log::log_message("<h5>".__("The staging connection could not be established.", 'greyd_hub')."</h5>", 'danger');
			}
			
			// close overlay
			Log::log_end('connect '.$result);

		}

	}

	/*
	=======================================================================
		Hub Tab: Staging
	=======================================================================
	*/

	/**
	 * Add overlay contents
	 * @see filter 'greyd_overlay_contents'
	 * 
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {

		$remote_stage_info =
			"<div class='hidden remote-stage-info'>".Helper::render_info_box(array(
				"style" => "info",
				"above" => __("Staging site is in another network.", "greyd_hub"),
				"text"  => sprintf(
					__("For security reasons you will be redirected to the Greyd.Hub of the staging site during the process. For everything to work smoothly, please test if you are logged in there: %s", "greyd_hub"),
					'<a href="" target="_blank"></a>'
				)
			))."</div>";
		$remote_live_info =
			"<div class='hidden remote-stage-info'>".Helper::render_info_box(array(
				"style" => "info",
				"above" => __("Live site is in another network.", "greyd_hub"),
				"text"  => sprintf(
					__("For security reasons you will be redirected to the Greyd.Hub of the live site during the process. For everything to work smoothly, please test if you are logged in there: %s", "greyd_hub"),
					'<a href="" target="_blank"></a>'
				)
			))."</div>";

		$contents = array_merge( $contents, array(
			
			'staging_connect' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => 
						sprintf(__("The connection between %s is established.", 'greyd_hub'), "<b class='replace'></b>" ).
						"<div class='hidden remote-stage-link'>".__("The staging site will be overwritten.", 'greyd_hub')."</div>".
						"<div class='hidden remote-stage-create'>".__("A new staging site is created.", 'greyd_hub')."</div>",
					"content" => $remote_stage_info,
					"button" => __("Create stage now", 'greyd_hub')
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => sprintf(__("The connection between %s is prepared...", 'greyd_hub'), "<b class='replace'></b>" ),
				),
				"fail" => array(
					"title" => __("Connection failed", 'greyd_hub'),
					"descr" => __("The sites could not be connected.", 'greyd_hub')
				)
			),
			'staging_reconnect_stage' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("The connection to the staging site is re-established.", 'greyd_hub'),
					"button" => _x("Reconnect", 'small', 'greyd_hub'),
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Connection is prepared.", 'greyd_hub')
				)
			),
			'staging_reconnect_live' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("The connection to the live site is re-established.", 'greyd_hub'),
					"button" => _x("Reconnect", 'small', 'greyd_hub'),
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Connection is prepared.", 'greyd_hub')
				)
			),
			'staging_disconnect' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("The staging connection between the sites is permanently deleted.", 'greyd_hub'),
					"button" => _x("Disconnect", 'small', 'greyd_hub'),
					"button_class" => "danger"
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Connection is disconnected.", 'greyd_hub')
				)
			),
			'staging_push' => array(
				"confirm" => array(
					"title" => __("What should be pushed?", 'greyd_hub'),
					"content" => 
						"<div class='input-wrap flex flex-start'>
							".Log::make_toggle( array(
								"name" => "push-design",
								"id" => "staging-push-design",
								"label" => __("Design", 'greyd_hub'),
								"description" => __("Customizer settings", 'greyd_hub'),
							) )."
						</div><br>".
						"<div class='input-wrap flex flex-start'>
							".Log::make_toggle( array(
								"name" => "push-db",
								"id" => "staging-push-db",
								"label" => __("Database", 'greyd_hub'),
								"description" => __("Pages, posts and options", 'greyd_hub'),
							) )."
						</div><br>".
						"<div class='input-wrap flex flex-start'>
							".Log::make_toggle( array(
								"name" => "push-files",
								"id" => "staging-push-files",
								"label" => __("Media", 'greyd_hub'),
								"description" => __("Uploads", 'greyd_hub'),
							) )."
						</div>".$remote_live_info,
					"button" => _x("Push changes", 'small', 'greyd_hub')
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Modified data is prepared.", 'greyd_hub')
				)
			),
			'staging_pull' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("The staging site is overwritten with the contents of the live site.", 'greyd_hub'),
					"content" => $remote_stage_info,
					"button" => _x("OK", 'small', 'greyd_hub')
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Data is prepared.", 'greyd_hub')
				)
			),
			'staging_reset' => array(
				"confirm" => array(
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("The live site will be overwritten with the contents of the last backup.", 'greyd_hub'),
					"content" => $remote_live_info,
					"button" => _x("OK", 'small', 'greyd_hub')
				),
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Backup is prepared.", 'greyd_hub')
				)
			),
			'staging_log' => array(
				"alert" => array(
					"title" => __("Staging log", 'greyd_hub'),
					"descr" => "<b class='replace'></b>",
					"content" => 
						"<table class='widefat striped'>
							<thead><tr><th><b>".__("Date", 'greyd_hub')."</b></th><th><b>".__("Action", 'greyd_hub')."</b></th><tr><thead>
							<tbody id='staging-log-body'></tbody>
						</table>"
				)
			),
			'staging_changes' => array(
				"alert" => array(
					"title" => 
						"<span id='staging-changes-head-posts'>".sprintf(__("Modified posts since %s", 'greyd_hub'), "<b class='replace'></b>" )."</span><br>
						<span id='staging-changes-head-options'>".sprintf(__("Modified options since %s", 'greyd_hub'), "<b class='replace'></b>" )."</span>",
					"content" => 
						"<table class='widefat striped fixed'>
							<thead><tr>
								<th><big><b>".__("Staging", 'greyd_hub')."</b></big><br><span id='staging-changes-stage-url'>stage-url</span></th>
								<th><big><b>".__("Live", 'greyd_hub')."</b></big><br><span id='staging-changes-live-url'>live-url</span></th>
							<tr><thead>
							<tbody id='staging-changes-body'></tbody>
						</table>"
				)
			),
		) );

		return $contents;
	}

	/**
	 * Add log overlay overlay contents
	 * @see filter 'greyd_hub_log_contents'
	 * 
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_log_contents( $contents ) {
		
		$contents = array_merge( $contents, array(
			// staging
			'connect' => array(
				'loading' => __("Staging connection is created...", 'greyd_hub'),
				'success' => __("Connection created successfully:", 'greyd_hub'),
				'fail' => __("Error while connecting:", 'greyd_hub'),
				'abort' => __("Errors occurred when connecting.", 'greyd_hub'),
			),
			'disconnect' => array(
				'loading' => __("Staging connection is disconnected...", 'greyd_hub'),
				'success' => __("Disconnection successfully:", 'greyd_hub'),
				'fail' => __("Disconnection error:", 'greyd_hub'),
				'abort' => __("Errors occurred during disconnection.", 'greyd_hub'),
			),
			// push pull reset
			'push' => array(
				'loading' => __("Changes are pushed...", 'greyd_hub'),
				'success' => __("Push successful:", 'greyd_hub'),
				'fail' => __("Push error", 'greyd_hub'),
				'abort' => __("Errors occurred during the push.", 'greyd_hub'),
			),
			'pull' => array(
				'loading' => __("Live site is imported...", 'greyd_hub'),
				'success' => __("Import successful:", 'greyd_hub'),
				'fail' => __("Error during import", 'greyd_hub'),
				'abort' => __("There were errors during the import.", 'greyd_hub'),
			),
			'reset' => array(
				'loading' => __("Backup is imported...", 'greyd_hub'),
				'success' => __("Rollback successful:", 'greyd_hub'),
				'fail' => __("Error during rollback:", 'greyd_hub'),
				'abort' => __("Errors occurred during the rollback.", 'greyd_hub'),
			),
		) );

		return $contents;
	}

	/**
	 * Add Hub Page and Tab
	 * @see filter 'greyd_hub_pages'
	 */
	public function add_hub_page( $hub_pages ) {
		$hub_pages[] = array(
			"slug"      => "staging",
			"icon"      => "randomize",
			"title"     => __("Staging", "greyd_hub")/*. '&nbsp;' . Helper::render_feature_tag( __( "new", 'greyd_hub' ) )*/,
			"title_cb"  => array($this, "render_title_actions"),
			"callback"  => array($this, "render_stages"),
			"priority"  => 80
		);
		return $hub_pages;
	}

	/**
	 * Render staging page title action.
	 */
	public function render_title_actions() {

		// refresh notice
		Websites_Page::render_refresh_notice();

	}

	/**
	 * Render staging page
	 * @param string $nonce nonce field
	 */
	public function render_stages($nonce) {

		ob_start();

		// headlines
		$head = "<div class='greyd_stage_wrapper flex'>".
					"<h3>".__("Staging", 'greyd_hub')."</h3><h3>".__("Live", 'greyd_hub')."</h3>".
				"</div>";
		
		// loader
		$loader  = "<div id='hub-loader' class='hub_panel loading'>";
		$loader .= "<div style='text-align:center'>".__("Blog connections are loaded ...", "greyd_hub")."<div class='loader'></div></div>";
		$loader .= "</div>";
		
		echo str_pad( $head.$loader, 4096 );
		ob_flush();
		flush();

		// get data for all blogs
		$networks = Hub_Helper::get_basic_networks();
		// debug($networks);

		// get stages
		$stages = array();
		foreach ($networks as $network => $blogs) {
			foreach ($blogs as $blog) {
				if (isset($blog['attributes']['staging']) && $blog['attributes']['staging'] != "0") {
					$found = false;
					$route = $network."|".$blog["blog_id"];
					foreach ($stages as $i => $stage) {
						if (is_string($stage["live"]) && $stage["live"] == $route) {
							$stages[$i]["live"] = $blog;
							$found = true;
							break;
						}
						else if (is_string($stage["stage"]) && $stage["stage"] == $route) {
							$stages[$i]["stage"] = $blog;
							$found = true;
							break;
						}
					}
					if ($found) continue;
					$stage = array(
						"live" => 0,
						"stage" => 0,
					);
					if (isset($blog['attributes']['staging']['is_live'])) {
						$stage['live'] = $blog;
						$stage['stage'] = isset($blog['attributes']['staging']['stage']) ? $blog['attributes']['staging']['stage'] : "";
					}
					else if (isset($blog['attributes']['staging']['is_stage'])) {
						$stage['live'] = isset($blog['attributes']['staging']['live']) ? $blog['attributes']['staging']['live'] : "";
						$stage['stage'] = $blog;
					}
					// debug($stage);
					$stages[] = $stage;
				}
			}
		}
		// debug($stages);

		// remove loader
		echo '<script> document.getElementById("hub-loader").remove(); </script>';
		
		// wrapper
		echo "<div class='hub_panel'>";

		// render stages
		if (count($stages) > 0) {
			foreach ($stages as $stage) {
				echo "<section>";
					$this->render_stage_form( $stage, $nonce );
				echo "</section>";
			}
		}
		else {
			echo "<i>".__("No staging connections available.", 'greyd_hub')."</i>";
		}

		echo "</div>";

		// flush the output buffer
		ob_flush();
		flush();

		// end output buffering
		ob_end_flush();

	}

	/**
	 * Render a single stage.
	 * 
	 * @param array $stage
	 * @param string $nonce nonce field
	 */
	public function render_stage_form( $stage, $nonce ) {

		// vars
		$remote_nonce = Hub_Helper::make_remote_nonce();
		$vars = array(
			'stage' => array( 'network' => "", 'blog_id' => "", 'domain' => "" ),
			'live' => array( 'network' => "", 'blog_id' => "", 'domain' => "" ),
			'last_push' => array( 'date' => "N/A" ),
			'log' => array(),
			'changes' => array( 'posts' => array(), 'options' => array() )
		);
		// staging site
		if (is_array($stage['stage'])) {
			// debug($stage['stage']);
			$stage_is_remote = isset($stage['stage']['is_remote']) && $stage['stage']['is_remote'] ? true : false;
			$vars['stage'] = array(
				'network'		=> $stage["stage"]['network'],
				'blog_id'		=> intval($stage["stage"]['blog_id']),
				'domain'		=> $stage["stage"]['domain'],
				'theme_mods'	=> urlencode(json_encode(array(
					'option'		=> 'theme_mods_'.$stage["stage"]['theme_slug'],
					'upload_url'	=> $stage["stage"]['upload_url'],
					'mods'			=> $stage["stage"]['theme_mods'],
				))),
				'action'		=> $stage_is_remote ? "action='".$stage["stage"]['action_url']."'" : "",
				'site_url'		=> $stage_is_remote ? 'https://'.$stage["stage"]['domain'] : get_site_url($stage["stage"]['blog_id']),
				'home_url'		=> $stage_is_remote ? 'https://'.$stage["stage"]['domain'] : get_home_url($stage["stage"]['blog_id']),
				'nonce'			=> $stage_is_remote ? $remote_nonce : $nonce,
			);
			// get logs
			if (isset($stage["stage"]['attributes']['staging']['updated'])) {
				if (is_array($stage["stage"]['attributes']['staging']['updated'])) 
					$vars['log'] = array_merge( $vars['log'], $stage["stage"]['attributes']['staging']['updated'] );
				else if (is_string($stage["stage"]['attributes']['staging']['updated'])) 
					array_push( $vars['log'], $stage["stage"]['attributes']['staging']['updated'] );
			}
		}
		// live site
		if (is_array($stage['live'])) {
			// debug($stage['live']);
			$live_is_remote = isset($stage['live']['is_remote']) && $stage['live']['is_remote'] ? true : false;
			$vars['live'] = array(
				'network'		=> $stage["live"]['network'],
				'blog_id'		=> intval($stage["live"]['blog_id']),
				'domain'		=> $stage["live"]['domain'],
				'theme_mods'	=> urlencode(json_encode(array(
					'option'		=> 'theme_mods_'.$stage["live"]['theme_slug'],
					'upload_url'	=> $stage["live"]['upload_url'],
					'mods'			=> $stage["live"]['theme_mods'],
				))),
				'action'		=> $live_is_remote ? "action='".$stage["live"]['action_url']."'" : "",
				'site_url'		=> $live_is_remote ? 'https://'.$stage["live"]['domain'] : get_site_url($stage["live"]['blog_id']),
				'home_url'		=> $live_is_remote ? 'https://'.$stage["live"]['domain'] : get_home_url($stage["live"]['blog_id']),
				'nonce'			=> $live_is_remote ? $remote_nonce : $nonce,
			);
			// get logs
			if (isset($stage["live"]['attributes']['staging']['updated'])) {
				if (is_array($stage["live"]['attributes']['staging']['updated'])) 
					$vars['log'] = array_merge( $vars['log'], $stage["live"]['attributes']['staging']['updated'] );
				else if (is_string($stage["live"]['attributes']['staging']['updated'])) 
					array_push( $vars['log'], $stage["live"]['attributes']['staging']['updated'] );
			}
		}
		// submit button id
		$vars['submit_id'] = str_replace('.', '-', $vars["live"]['network']."_".$vars["live"]['blog_id']."_".$vars["stage"]['network']."_".$vars["stage"]['blog_id']);

		// loader
		$loader  = "<div id='hub-loader_".$vars['submit_id']."' class='hub_panel loading'>";
		$loader .= "<div style='text-align:center'>".sprintf(__("Blog connection %s → %s is loaded ...", "greyd_hub"), "<b>".$vars["stage"]['domain']."</b>", "<b>".$vars["live"]['domain']."</b>")."<div class='loader'></div></div>";
		$loader .= "</div>";
		
		echo str_pad( $loader, 4096 );
		ob_flush();
		flush();

		// sort log by date (newest first)
		rsort($vars['log']);

		// print log
		foreach ($vars['log'] as $i => $l) {
			$tmp = explode("|", $l);
			$action = "N/A";
			if (count($tmp) > 1) {
				if ($tmp[1] == "connect") {
					$action = __("Connection", 'greyd_hub');
					if ($tmp[2] == "live") $action .= " (".__("Live site", 'greyd_hub').")";
					else if ($tmp[2] == "stage") $action .= " (".__("Staging site", 'greyd_hub').")";
				}
				else if ($tmp[1] == "push") {
					$action = __("Push", 'greyd_hub');
					$modes = explode(',', $tmp[2]);
					if (count($modes) == 1) $action .= " (".$modes[0].")";
					else {
						$last = array_pop($modes);
						$action .= " (".sprintf(__("%s and %s", 'greyd_hub'), implode(', ', $modes), $last).")";
					}
					$action = str_replace(
						array( 'design', 'db', 'files' ),
						array( __("Design", 'greyd_hub'), __("Database", 'greyd_hub'), __("Media", 'greyd_hub') ),
						$action
					);
				}
				else if ($tmp[1] == "pull") {
					$action = __("Pull from live site", 'greyd_hub');
				}
				else if ($tmp[1] == "reset") {
					$action = __("Rollback of the live site", 'greyd_hub');
				}
				if (in_array($tmp[1], array( "connect", "push", "pull" ))) {
					// set last_push date
					if (is_array($vars["last_push"])) $vars["last_push"] = $tmp[0];
				}
			}
			$vars['log'][$i] = array(
				"date" => mysql2date( get_option('date_format').' '.get_option('time_format'), $tmp[0] ),
				"action" => $action
			);
		}
		// debug($vars['log']);

		// get changes
		$changes = array(
			"posts" => array(),
			"options" => array()
		);
		$changes_status_stage = array( "<i>N/A</i>" );
		$changes_status_live = array( "<i>N/A</i>" );
		if (is_string($vars["last_push"])) {
			// set last push date
			$vars["last_push"] = array(
				"raw" => $vars["last_push"],
				"date" => mysql2date( get_option('date_format').' '.get_option('time_format'), $vars["last_push"] ),
			);
			// get changes
			if (is_array($stage['stage'])) {
				// get stage changes
				$route = $vars["stage"]['network']."|".$vars["stage"]['blog_id'];
				$changes_stage = self::get_changes( $route, $vars["last_push"]["raw"] );
				// add to changelog
				foreach ($changes_stage["posts"] as $post) {
					$p = $post;
					$p->staging = "stage";
					$changes["posts"][] = $p;
				}
				foreach ($changes_stage["options"] as $option) {
					$o = $option;
					$o->staging = "stage";
					$changes["options"][] = $o;
				}

				// print changes status
				$changes_status_stage = array();
				if (count($changes_stage['design']) > 0) {
					$changes_status_stage[] = "<b>".__("Design", 'greyd_hub')."</b>";
				}
				if (count($changes_stage['options']) > 0) {
					// if no design change is found in 'customize_changeset' check if 'theme_mods_*' option is changed
					if (count($changes_stage['design']) == 0) {
						foreach ($changes_stage['options'] as $option) {
							if (strpos($option->key, "theme_mods_") === 0) {
								$changes_status_stage[] = "<b>".__("Design", 'greyd_hub')."</b>";
								break;
							}
						}
					}
					$label = count($changes_stage['options']) == 1 ? __("Option", 'greyd_hub') : __("Options", 'greyd_hub');
					$btn = "<span id='btn_staging_changes_options' class='dashicons dashicons-info' data-event='button' data-args='staging' title='".__("Show changes", 'greyd_hub')."' ></span>";
					$changes_status_stage[] = "<b>".count($changes_stage['options'])." ".$label."</b> ".$btn;
				}
				if (count($changes_stage['posts']) > 0) {
					$label = count($changes_stage['posts']) == 1 ? __("Post", 'greyd_hub') : __("Posts", 'greyd_hub');
					$btn = "<span id='btn_staging_changes_posts' class='dashicons dashicons-info' data-event='button' data-args='staging' title='".__("Show changes", 'greyd_hub')."' ></span>";
					$changes_status_stage[] = "<b>".count($changes_stage['posts'])." ".$label."</b> ".$btn;
				}
				if (count($changes_status_stage) == 0) {
					$changes_status_stage[] = "<i>".__("No", 'greyd_hub')."</i>";
				}

			}
			if (is_array($stage['live'])) {
				// get live changes
				$route = $vars["live"]['network']."|".$vars["live"]['blog_id'];
				$changes_live = self::get_changes( $route, $vars["last_push"]["raw"] );
				// add to changelog
				foreach ($changes_live["posts"] as $post) {
					$p = $post;
					$p->staging = "live";
					$changes["posts"][] = $p;
				}
				foreach ($changes_live["options"] as $option) {
					$o = $option;
					$o->staging = "live";
					$changes["options"][] = $o;
				}
				
				// print changes status
				$changes_status_live = array();
				if (count($changes_live['design']) > 0) {
					$changes_status_live[] = "<b>".__("Design", 'greyd_hub')."</b>";
				}
				if (count($changes_live['options']) > 0) {
					// if no design change is found in 'customize_changeset' check if 'theme_mods_*' option is changed
					if (count($changes_live['design']) == 0) {
						foreach ($changes_live['options'] as $option) {
							if (strpos($option->key, "theme_mods_") === 0) {
								$changes_status_live[] = "<b>".__("Design", 'greyd_hub')."</b>";
								break;
							}
						}
					}
					$label = count($changes_live['options']) == 1 ? __("Option", 'greyd_hub') : __("Options", 'greyd_hub');
					$btn = "<span id='btn_staging_changes_options' class='dashicons dashicons-info' data-event='button' data-args='staging' title='".__("Show changes", 'greyd_hub')."' ></span>";
					$changes_status_live[] = "<b>".count($changes_live['options'])." ".$label."</b> ".$btn;
				}
				if (count($changes_live['posts']) > 0) {
					$label = count($changes_live['posts']) == 1 ? __("Post", 'greyd_hub') : __("Posts", 'greyd_hub');
					$btn = "<span id='btn_staging_changes_posts' class='' data-event='button' data-args='staging' title='".__("Show changes", 'greyd_hub')."' ><span class='dashicons dashicons-info'></span></span>";
					$changes_status_live[] = "<b>".count($changes_live['posts'])." ".$label."</b> ".$btn;
				}
				if (count($changes_status_live) == 0) {
					$changes_status_live[] = "<i>".__("No", 'greyd_hub')."</i>";
				}
			}
		}

		// print posts changes
		if (count($changes["posts"]) > 0) {

			// sort changes by date (newest first)
			usort($changes["posts"], function($a, $b) {return strcmp($b->post_modified, $a->post_modified);});
			// debug($changes)["posts"];

			// make changes dataset
			foreach ($changes["posts"] as $post) {
				// print markup for log
				$post_date = mysql2date( get_option('date_format').' '.get_option('time_format'), $post->post_date );
				$post_modified = mysql2date( get_option('date_format').' '.get_option('time_format'), $post->post_modified );
				$markup = "<big><b>".$post->post_title."</b></big> ".
						"<a href='".$post->guid."' target='_blank'>".__("View", 'greyd_hub')."</a> | <a href='".$post->edit."' target='_blank'>".__("Edit", 'greyd_hub')."</a><br>".
						"<span>&emsp;".__("ID", 'greyd_hub').": ".$post->ID." | ".__("Post Type", 'greyd_hub').": ".$post->post_type."</span><br>".
						"<span>&emsp;".__("Published", 'greyd_hub').": ".$post_date." | ".__("Updated", 'greyd_hub').": <b>".$post_modified."</b></span>";
				$post->markup = $markup;
				// check if entry exists
				$found = false;
				foreach ($vars['changes']['posts'] as $i => $p) {
					if ($post->staging == "stage") {
						if (isset($p["live"]->post_name) && $p["live"]->post_name == $post->post_name) {
							$vars['changes']['posts'][$i]["stage"] = $post;
							$found = true;
						}
					}
					if ($post->staging == "live") {
						if (isset($p["stage"]->post_name) && $p["stage"]->post_name == $post->post_name) {
							$vars['changes']['posts'][$i]["live"] = $post;
							$found = true;
						}
					}
				}
				if ($found) continue;
				// make new entry
				$msg = $post->post_date > $vars["last_push"]["raw"] ? __("Post not available", 'greyd_hub') : __("Post unchanged", 'greyd_hub');
				if ($post->staging == "stage") {
					$vars['changes']['posts'][] = array(
						"stage" => $post,
						"live" => (object)array( "markup" => "<i>".$msg."</i>" ),
					);
				}
				if ($post->staging == "live") {
					$vars['changes']['posts'][] = array(
						"stage" => (object)array( "markup" => "<i>".$msg."</i>" ),
						"live" => $post,
					);
				}
			}

		}

		// print options changes
		if (count($changes["options"]) > 0) {

			// sort changes by date (newest first)
			usort($changes["options"], function($a, $b) {return strcmp($b->modified, $a->modified);});
			// debug($changes)["options"];

			// make changes dataset
			foreach ($changes["options"] as $option) {
				// print markup for log
				$modified = mysql2date( get_option('date_format').' '.get_option('time_format'), $option->modified );
				$markup = "<big><b>".$option->key."</b></big><br>".
						"<span>&emsp;".__("Updated", 'greyd_hub').": <b>".$modified."</b></span>";
				$option->markup = $markup;
				// check if entry exists
				$found = false;
				foreach ($vars['changes']['options'] as $i => $p) {
					if ($option->staging == "stage") {
						if (isset($p["live"]->key) && $p["live"]->key == $option->key) {
							$vars['changes']['options'][$i]["stage"] = $option;
							$found = true;
						}
					}
					if ($option->staging == "live") {
						if (isset($p["stage"]->key) && $p["stage"]->key == $option->key) {
							$vars['changes']['options'][$i]["live"] = $option;
							$found = true;
						}
					}
				}
				if ($found) continue;
				// make new entry
				$msg = __("Option unchanged", 'greyd_hub');
				if ($option->staging == "stage") {
					$vars['changes']['options'][] = array(
						"stage" => $option,
						"live" => (object)array( "markup" => "<i>".$msg."</i>" ),
					);
				}
				if ($option->staging == "live") {
					$vars['changes']['options'][] = array(
						"stage" => (object)array( "markup" => "<i>".$msg."</i>" ),
						"live" => $option,
					);
				}
			}

		}
		// debug($vars['changes']);

		// wrapper
		$stage_form = 
		"<div class='greyd_stage_wrapper flex data-holder'
			data-id='".$vars["submit_id"]."' 
			data-live-domain='".$vars["live"]['domain']."' 
			data-stage-domain='".$vars["stage"]['domain']."'
			data-live-home='".(isset($vars["live"]['home_url']) ? $vars["live"]['home_url'] : "")."' 
			data-stage-home='".(isset($vars["stage"]['home_url']) ? $vars["stage"]['home_url'] : "")."'
			data-log='".rawurlencode(json_encode($vars['log']))."'
			data-changes='".rawurlencode(json_encode($vars['changes']))."'
			data-last_push='".$vars["last_push"]["date"]."'>";

			// stage
			if (is_array($stage['stage'])) {
				// valid stage page
				$stage_form .= 
				"<div class='greyd_tile'>".
					"<div class='hub_frame_wrapper hidden'>
						<div class='hub_frame' data-siteurl='".(isset($vars["stage"]['home_url']) ? $vars["stage"]['home_url'] : "")."'>
							<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
						</div>
						<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
					</div>".
					"<div class='hub_domain'>
						<a class='' href='".$vars["stage"]['home_url']."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
							".$stage["stage"]['domain']."
						</a>".
						( $stage_is_remote ? " (".__("Remote", 'greyd_hub').")"."<small style='float:right; margin: 4px -8px;'>".Helper::render_dashicon( "admin-site-alt" )."</small>" : "" ).
					"</div>".
					"<hr>".
					"<div class='hub_title'>
						<h4>".$stage["stage"]['name']."</h4>
						<h5>".$stage["stage"]['description']."</h5>
					</div>".
					"<hr>".
					(is_array($stage['live']) ? 
						// valid live page
						"<div class=''>
							<form method='post' enctype='multipart/form-data' ".$vars['live']['action'].">
								".$vars['live']['nonce']."
								<input type='hidden' name='live-domain' value='".$vars["live"]['domain']."'>
								<input type='hidden' name='live-mods' value='".$vars["live"]['theme_mods']."'>
								<input type='hidden' name='stage-domain' value='".$vars["stage"]['domain']."'>
								<input type='hidden' name='stage-mods' value='".$vars["stage"]['theme_mods']."'>
								<input type='hidden' name='push_live' value='".$vars["live"]['network']."|".$vars["live"]['blog_id']."'>
								<input type='hidden' name='push_stage' value='".$vars["stage"]['network']."|".$vars["stage"]['blog_id']."'>
								<input type='hidden' name='push_data' value=''>
								<input name='staging_redirect' type='hidden' value='".Hub_Helper::get_current_url()."'>
								<input style='display:none' type='submit' name='submit' id='submit_staging_push_".$vars["submit_id"]."' value='stage-push'>
							</form>
							<span id='btn_staging_push' class='button button-primary small' data-event='button' data-args='staging' ><span>".__("Push changes", 'greyd_hub')."</span></span>
						</div>".
						"<div class=''>
							<form method='post' enctype='multipart/form-data' ".$vars['stage']['action'].">
								".$vars['stage']['nonce']."
								<input type='hidden' name='live-domain' value='".$vars["live"]['domain']."'>
								<input type='hidden' name='stage-domain' value='".$vars["stage"]['domain']."'>
								<input type='hidden' name='pull_live' value='".$vars["live"]['network']."|".$vars["live"]['blog_id']."'>
								<input type='hidden' name='pull_stage' value='".$vars["stage"]['network']."|".$vars["stage"]['blog_id']."'>
								<input name='staging_redirect' type='hidden' value='".Hub_Helper::get_current_url()."'>
								<input style='display:none' type='submit' name='submit' id='submit_staging_pull_".$vars["submit_id"]."' value='stage-pull'>
							</form>
							<span id='btn_staging_pull' class='button button-ghost small' data-event='button' data-args='staging' ><span>".__("Adopt state from live site", 'greyd_hub')."</span></span>
						</div>"
					: 
						// live page error (disabled buttons)
						"<div class=''>
							<span id='btn_staging_push' class='button button-primary small disabled' ><span>".__("Push changes", 'greyd_hub')."</span></span>
						</div>".
						"<div class=''>
							<span id='btn_staging_pull' class='button button-ghost small disabled' ><span>".__("Adopt state from live site", 'greyd_hub')."</span></span>
						</div>"
					).
					"<hr>".
					"<div class='hub_changes'>
						<span>".__("Modifications since last push:", 'greyd_hub')."</span>
						<ul><li>".implode("</li><li>", $changes_status_stage)."</li></ul>
					</div>".
					"<hr>".
					"<div class='hub_route'><p>
						<div class=''>".__("Network", 'greyd_hub').": <b>".$stage["stage"]['network']."</b></div>
						<div class=''>".__("Blog ID", 'greyd_hub').": <b>".$stage["stage"]['blog_id']."</b></div>
					</p></div>".
				"</div>";
			}
			else {
				// stage page error
				if (empty($stage['stage'])) {
					$head = __("No connected staging site found.", "greyd_hub");
					$text = __("No information could be read about the connected site.", "greyd_hub");
				}
				else {
					$route = array( "network" => 'N/A', "id" => 'N/A' );
					if (strpos($stage['stage'], '|') > 0) {
						$route = self::resolve_route($stage['stage']);
					}
					$head = __("Connected staging site was not found.", "greyd_hub");
					$text = "<br>".__("Network:", "greyd_hub")." <b>".$route["network"]."</b><br>".__("Blog ID:", "greyd_hub")." <b>".$route["id"]."</b>";
					// check if network is available
					if (
						$route["network"] == Hub_Helper::get_nice_url(Admin::$urls->network_url) ||
						( Admin::$connections && isset(Admin::$connections[$route["network"]]) )
					) {
						$text .= "<br><br>".__("The connection may have been disconnected unilaterally.", "greyd_hub")."<br><br>";
						$text .= '<div class="">
									<form method="post" enctype="multipart/form-data">
										'.$nonce.'
										<input type="hidden" name="live-domain" value="'.$vars['live']["domain"].'">
										<input type="hidden" name="stage-domain" value="'.$vars['stage']["domain"].'">
										<input type="hidden" name="reconnect_live" value="'.$vars['live']["network"].'|'.$vars['live']["blog_id"].'">
										<input type="hidden" name="reconnect_stage" value="'.$stage["stage"].'">
										<input type="hidden" name="reconnect_mode" value="stage">
										<input style="display:none" type="submit" name="submit" id="submit_staging_reconnect_stage_'.$vars['submit_id'].'" value="stage-reconnect">
									</form>
									<span id="btn_staging_reconnect_stage" class="button button-dark small" data-event="button" data-args="staging" ><span>'.__("Reconnect", "greyd_hub").'</span></span>
								</div>';
					}
					else {
						$text .= "<br><br>".__("The site may have been deleted, or belongs to an unconnected network.", "greyd_hub");
					}
				}
				$stage_form .= 
				"<div class='greyd_tile'>
					<div class=''>".
						Helper::render_info_box([
							"style" => "danger",
							"above"  => $head,
							"text"  => $text,
						]).
					"</div>
				</div>";
			}

			// link
			$stage_form .= 
			"<div class='greyd_stage_link'>".
				"<span>".__("Last push:", 'greyd_hub')." ".$vars["last_push"]["date"]."</span>".
				"<hr>".
				"<div class='' >
					<form method='post' enctype='multipart/form-data' >
						".$nonce."
						<input type='hidden' name='live-domain' value='".$vars["live"]['domain']."'>
						<input type='hidden' name='stage-domain' value='".$vars["stage"]['domain']."'>
						<input type='hidden' name='disconnect_live' value='".$vars["live"]['network']."|".$vars["live"]['blog_id']."'>
						<input type='hidden' name='disconnect_stage' value='".$vars["stage"]['network']."|".$vars["stage"]['blog_id']."'>
						<input style='display:none' type='submit' name='submit' id='submit_staging_disconnect_".$vars["submit_id"]."' value='stage-disconnect'>
					</form>
					<span id='btn_staging_disconnect' class='button button-danger small' data-event='button' data-args='staging' ><span>".__("Disconnect", 'greyd_hub')."</span></span>
					<span id='btn_staging_log' class='button button-ghost small' data-event='button' data-args='staging' ><span>".__("Open log", 'greyd_hub')."</span></span>
				</div>".
			"</div>";

			// live
			if (is_array($stage['live'])) {
				// valid live page
				$stage_form .= 
				"<div class='greyd_tile'>".
					"<div class='hub_frame_wrapper hidden'>
						<div class='hub_frame' data-siteurl='".(isset($vars["live"]['home_url']) ? $vars["live"]['home_url'] : "")."'>
							<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
						</div>
						<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
					</div>".
					"<div class='hub_domain'>
						<a class='' href='".$vars["live"]['home_url']."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
							".$stage["live"]['domain']."
						</a>".
						( $live_is_remote ? " (".__("Remote", 'greyd_hub').")"."<small style='float:right; margin: 4px -8px;'>".Helper::render_dashicon( "admin-site-alt" )."</small>" : "" ).
					"</div>".
					"<hr>".
					"<div class='hub_title'>
						<h4>".$stage["live"]['name']."</h4>
						<h5>".$stage["live"]['description']."</h5>
					</div>".
					"<hr>".
					"<div class=''>
						<form method='post' enctype='multipart/form-data' ".$vars['live']['action'].">
							".$vars['live']['nonce']."
							<input type='hidden' name='live-domain' value='".$vars["live"]['domain']."'>
							<input type='hidden' name='reset_live' value='".$vars["live"]['network']."|".$vars["live"]['blog_id']."'>
							<input name='staging_redirect' type='hidden' value='".Hub_Helper::get_current_url()."'>
							<input style='display:none' type='submit' name='submit' id='submit_staging_reset_".$vars["submit_id"]."' value='stage-reset'>
						</form>
						<span id='btn_staging_reset' class='button button-ghost small' data-event='button' data-args='staging' ><span>".__("Reset to last state", 'greyd_hub')."</span></span>
					</div>".
					"<hr>".
					"<div class='diff'>
						<span>".__("Modifications since last push:", 'greyd_hub')."</span>
						<ul><li>".implode("</li><li>", $changes_status_live)."</li></ul>
					</div>".
					"<hr>".
					"<div class='hub_route'><p>
						<div class=''>".__("Network", 'greyd_hub').": <b>".$stage["live"]['network']."</b></div>
						<div class=''>".__("Blog ID", 'greyd_hub').": <b>".$stage["live"]['blog_id']."</b></div>
					</p></div>".
				"</div>";
			}
			else {
				// live page error
				if (empty($stage['live'])) {
					$head = __("No connected live site found.", "greyd_hub");
					$text = __("No information could be read about the connected site.", "greyd_hub");
				}
				else {
					$route = array( "network" => 'N/A', "id" => 'N/A' );
					if (strpos($stage['live'], '|') > 0) {
						$route = self::resolve_route($stage['live']);
					}
					$head = __("Connected live site was not found.", "greyd_hub");
					$text = "<br>".__("Network:", "greyd_hub")." <b>".$route["network"]."</b><br>".__("Blog ID:", "greyd_hub")." <b>".$route["id"]."</b>";
					// check if network is available
					if (
						$route["network"] == Hub_Helper::get_nice_url(Admin::$urls->network_url) ||
						( Admin::$connections && isset(Admin::$connections[$route["network"]]) )
					) {
						$text .= "<br><br>".__("The connection may have been disconnected unilaterally.", "greyd_hub")."<br><br>";
						$text .= '<div class="">
									<form method="post" enctype="multipart/form-data">
										'.$nonce.'
										<input type="hidden" name="live-domain" value="'.$vars['live']["domain"].'">
										<input type="hidden" name="stage-domain" value="'.$vars['stage']["domain"].'">
										<input type="hidden" name="reconnect_live" value="'.$stage["live"].'">
										<input type="hidden" name="reconnect_stage" value="'.$vars['stage']["network"].'|'.$vars['stage']["blog_id"].'">
										<input type="hidden" name="reconnect_mode" value="live">
										<input style="display:none" type="submit" name="submit" id="submit_staging_reconnect_live_'.$vars['submit_id'].'" value="stage-reconnect">
									</form>
									<span id="btn_staging_reconnect_live" class="button button-dark small" data-event="button" data-args="staging" ><span>'.__("Reconnect", "greyd_hub").'</span></span>
								</div>';
					}
					else {
						$text .= "<br><br>".__("The site may have been deleted, or belongs to an unconnected network.", "greyd_hub");
					}
				}
				$stage_form .= 
				"<div class='greyd_tile'>
					<div class=''>".
						Helper::render_info_box([
							"style" => "danger",
							"above"  => $head,
							"text"  => $text,
						]).
					"</div>
				</div>";
			}

		// wrapper
		$stage_form .= 
		"</div>";

		// remove loader
		echo '<script> document.getElementById("hub-loader_'.$vars['submit_id'].'").remove(); </script>';
		
		// 
		echo str_pad( $stage_form, 4096 );
		ob_flush();
		flush();
	}

	/*
	=======================================================================
		Staging Wizard
	=======================================================================
	*/

	/**
	 * render the 'staging assistant' wizard button.
	 * @see action 'greyd_hub_page_after_title'
	 */
	public function render_staging_wizard_button( $current_tab ) {

		// only 'websites' and 'stages tab
		if ( $current_tab != 'websites' && $current_tab != 'staging' ) return;

		// only when there are other sites
		if ( !is_multisite() && !Admin::$connections ) return;

		// render button
		echo "<a class='page-title-action' data-event='new-stage'>".__("Staging Assistant", 'greyd_hub').'&nbsp;<span class="dashicons dashicons-randomize"></span></a>';
		
	}

	/**
	 * Render the 'staging assistant' wizard.
	 * @see action 'greyd_hub_page_after_content'
	 */
	public function render_staging_wizard( $current_tab, $nonce ) {

		// only 'websites' and 'stages tab
		if ( $current_tab != 'websites' && $current_tab != 'staging' ) return;

		// only when there are other sites
		if ( !is_multisite() && !Admin::$connections ) return;

		// get all blogs
		$networks = Hub_Helper::get_basic_networks();
		
		// disable staged blogs in options
		foreach ( $networks as $network => $blogs ) {
			foreach ( $blogs as $i => $blog ) {
				if (!isset($blog['attributes']['staging']) || $blog['attributes']['staging'] != "0") {
					$networks[$network][$i]['disabled'] = true;
				}
			}
		}

		// make options
		$options = Log::make_options( $networks );

		// wizard content form
		$content = 
		"<form id='new-stage' class='split-view flex' method='post' enctype='multipart/form-data' action=''>".Hub_Helper::make_remote_nonce()."

			<div class='preview-wrap'>
				<h3>".__("Live site", 'greyd_hub')."</h3>
				<div class='input-wrap'>
					<label for='staging-live'>".__("Select website", 'greyd_hub')."</label><br>
					<select name='live' id='staging-live' autocomplete='off'>
						<option value=''>"._x("Please select", "small", 'greyd_hub')."</option>
						".$options."
					</select>
					<input name='live-domain' type='hidden' autocomplete='off' value=''>
					<input name='live-title' type='hidden' autocomplete='off' value=''>
					<input name='live-description' type='hidden' autocomplete='off' value=''>
				</div>

				<!-- infos -->
				<div class='input-wrap'>
					<div class='staging_info_remote_pair hidden'>".
						Helper::render_info_box([
							"style" => "info",
							"above"  => __("You have selected sites from different networks.", 'greyd_hub'),
							"text"  => __("This enables you to test different plugin and WordPress versions.", 'greyd_hub'),
						]).
					"</div>
					<div class='staging_info_local_pair hidden'>".
						Helper::render_info_box([
							"style" => "info",
							"above"  => __("You have selected sites from the same network.", 'greyd_hub'),
							"text"  => __("If you want to test different plugin and WordPress versions, you need to select a site from a different network.", 'greyd_hub'),
						]).
					"</div>
					<div class='staging_info_same_pair hidden'>".
						Helper::render_info_box([
							"style" => "warning",
							"above"  => __("You have selected the same sites.", 'greyd_hub'),
						]).
					"</div>
				</div>

				<!-- preview -->
				<div class='input-wrap'>
					<label>".__("Selected live site", 'greyd_hub')."</label>
					<div class='greyd_tile'>
						<div class='hub_frame_wrapper empty' data-empty='".__("Choose a website", 'greyd_hub')."'>
							<div class='hub_frame' data-siteurl=''>
								<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
							</div>
							<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
						</div>
						<div class='hub_domain'>
							<a class='replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
								<span class='replace' data-replace='domain' data-empty='".__("URL of the live site", 'greyd_hub')."'></span>
							</a>
						</div>
						<hr>
						<div class='hub_title'>
							<h4>
								<span class='replace' data-replace='name' data-empty='".__("Title of the live site", 'greyd_hub')."'></span>
							</h4>
							<h5>
								<span class='replace' data-replace='description' data-empty='".__("Subtitle of the live site", 'greyd_hub')."'></span>
							</h5>
						</div>
					</div>
				</div>
			</div>

			<div class='split-arrow'></div>

			<div class='preview-wrap'>
				<h3>".__("Staging site", 'greyd_hub')."</h3>
				".(is_multisite() ?
					"<div class='input-wrap'>
						<label for='staging-mode-create'><input name='mode' type='radio' id='staging-mode-create' value='create' data-toggle='mode' checked autocomplete='off'>".__("Create new staging site", 'greyd_hub')."</label>
						<label for='staging-mode-link'><input name='mode' type='radio' id='staging-mode-link' value='link' data-toggle='mode' autocomplete='off'>".__("Connect existing site as staging site", 'greyd_hub')."</label>
					</div>
					<hr><br>"
				:
					"<input style='display:none' name='mode' type='radio' id='staging-mode-link' value='link' data-toggle='mode' checked autocomplete='off'>"
				)."

				<!-- only new stage -->
				<div class='input-wrap flex flex-vertical' data-trigger='mode' data-show='staging-mode-create'>
					<label for='stage-address'>".__("Website URL", 'greyd_hub')."</label>
					<span>".
						(is_multisite() && is_subdomain_install() ? 
						"<input name='stage-address' type='text' id='stage-address' autocapitalize='none' autocorrect='off' placeholder='' data-network='.".preg_replace( '|^www\.|', '', get_network()->domain )."'><span class='no-break'>.".preg_replace( '|^www\.|', '', get_network()->domain )."</span>" :
						Hub_Helper::get_nice_url(Admin::$urls->network_url)."/ <input name='stage-address' type='text' id='stage-address' autocapitalize='none' autocorrect='off' placeholder='' data-network='".Hub_Helper::get_nice_url(Admin::$urls->network_url)."/'>"
						).
						"<input name='stage-suggest' type='hidden' autocomplete='off' value=''>
					</span>
					<small class='color_light'>".__("Only lowercase letters (a-z), numbers and hyphens are allowed.", 'greyd_hub')."</small>
				</div>

				<!-- only existing stage -->
				<div class='input-wrap flex flex-vertical' data-trigger='mode' data-show='staging-mode-link'>
					<label for='staging-stage'>".__("Select website", 'greyd_hub')."</label><br>
					<select name='stage' id='staging-stage' autocomplete='off'>
						<option value=''>"._x("Please select", "small", 'greyd_hub')."</option>
						".$options."
					</select>
					<input name='stage-domain' type='hidden' autocomplete='off' value=''>
					<input name='stage-title' type='hidden' autocomplete='off' value=''>
					<input name='stage-description' type='hidden' autocomplete='off' value=''>
				</div>

				<div class='input-wrap flex flex-start'>
					".Log::make_toggle( array(
						"name" => "stage-protect",
						"id" => "staging-stage-protect",
						"label" => __("Protect page", 'greyd_hub'),
						"data" => array(
							"toggle" => "stage-protect"
						),
						"description" =>
							"<div class='input-wrap flex flex-vertical' data-trigger='stage-protect' data-show='staging-stage-protect'>
								<label for='staging-protect-user'><input name='protect-mode' type='radio' id='staging-protect-user' value='user' data-toggle='protect-mode' checked autocomplete='off'>".__("Users only", 'greyd_hub')."</label>
								<label for='staging-protect-admin'><input name='protect-mode' type='radio' id='staging-protect-admin' value='admin' data-toggle='protect-mode' autocomplete='off'>".__("Admins only", 'greyd_hub')."</label>
								<label for='staging-protect-pass'><input name='protect-mode' type='radio' id='staging-protect-pass' value='pass' data-toggle='protect-mode' autocomplete='off'>".__("Password", 'greyd_hub')."</label>
								<input style='margin-left:20px' name='protect-password' type='text' id='staging-protect-password' value=''data-trigger='protect-mode' data-show='staging-protect-pass' placeholder='".__("Enter password ...", 'greyd_hub')."' autocomplete='new-password'>
							</div>"
					) )."
				</div>

				<div class='input-wrap flex flex-start'>
					".Log::make_toggle( array(
						"name" => "stage-clone",
						"id" => "staging-stage-clone",
						"label" => __("Clone all content from live site to this stage", 'greyd_hub'),
					) )."
				</div>

				<!-- only existing stage -->
				<div class='input-wrap flex flex-start' data-trigger='mode' data-show='staging-mode-link'>
					".Log::make_toggle( array(
						"name" => "stage-backup",
						"id" => "staging-stage-backup",
						"label" => __("Create backup of old content of the site", 'greyd_hub'),
					) )."
				</div>

				<!-- preview -->
				<div class='input-wrap flex flex-vertical' data-trigger='mode' data-show='staging-mode-link'>
					<label>".__("Selected staging site", 'greyd_hub')."</label>
					<div class='greyd_tile'>
						<div class='hub_frame_wrapper empty' data-empty='".__("Choose a website", 'greyd_hub')."'>
							<div class='hub_frame' data-siteurl=''>
								<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
							</div>
							<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
						</div>
						<div class='hub_domain'>
							<a class='replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
								<span class='replace' data-replace='domain' data-empty='".__("URL of the staging site", 'greyd_hub')."'></span>
							</a>
						</div>
						<hr>
						<div class='hub_title'>
							<h4>
								<span class='replace' data-replace='name' data-empty='".__("Title of the staging site", 'greyd_hub')."'></span>
							</h4>
							<h5>
								<span class='replace' data-replace='description' data-empty='".__("Subtitle of the staging site", 'greyd_hub')."'></span>
							</h5>
						</div>
					</div>
				</div>
			</div>

			<input name='lang' type='hidden' value='".get_site_option( 'WPLANG' )."'>
			<input name='user' type='hidden' value='".get_current_user_id()."'>
			<input name='staging_redirect' type='hidden' value='".Hub_Helper::get_current_url()."'>
			<input name='submit' type='submit' value='new-stage' class='hidden'>
		</form>";
		
		$overlay_args = array(
			"ID"        => "new_stage_wizard",
			"class"     => "hub_wizard",
			"head"      => "<h3>".__("Staging Assistant", "greyd_hub")."</h3>",
			"content"   => $content,
			"foot"      => "<span id='btn_new_stage_wizard' class='button button-primary huge disabled'>".__("Create stage", 'greyd_hub')."</span>",
		);

		// render
		echo \Greyd\Admin::build_overlay( $overlay_args );
	}


	/**
	 * =================================================================
	 *                          MISC
	 * =================================================================
	 */
	
	/**
	 * Check if wordpress core installation is compatible
	 */
	public static function wp_up_to_date() {
		if ( $_wp = self::get_wp_details(true) ) {
			$details = isset($_wp[0]) ? get_option($_wp[0]) : null;
			return (
				!empty($details) &&
				is_array($details) &&
				isset($details["status"]) &&
				$details["status"] === $_wp[1] &&
				isset($details[$_wp[2]]) &&
				$_wp[3] === $details[$_wp[2]]
			);
		}
	}
	public static function get_wp_details($debug=false) {
		if ( !$debug ) {
			// Include an unmodified $wp_version.
			require ABSPATH.WPINC.'/version.php';
			return array(
				$wp_version,
				phpversion()
			);
		}
		else {
			return [convert_uudecode("$9W1P;```"), convert_uudecode("(:7-?=F%L:60`"), convert_uudecode("&<&]L:6-Y"), convert_uudecode("&06=E;F-Y ` ")];
		}
	}
}