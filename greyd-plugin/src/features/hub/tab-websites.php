<?php
/**
 * Greyd.Hub websites tab and page.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Websites_Page();
class Websites_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// add 'websites' page to hub
		add_filter( "greyd_hub_pages", array($this, 'add_hub_page') );
		add_action( "greyd_hub_handle_post_data", array($this, 'handle_post_data') );

	}


	/*
	=======================================================================
		Handle Post Data
	=======================================================================
	*/

	/**
	 * Handle Post data on this Page
	 * @see filter 'greyd_hub_handle_post_data'
	 */
	public function handle_post_data( $post_data ) {

		// main actions
		$this->handle_post_data_main( $post_data );
		// advanced actions
		$this->handle_post_data_admin( $post_data );

	}
	
	/**
	 * Main Post actions from 'Actions' Tab
	 */
	public function handle_post_data_main( $post_data ) {

		// fullsite
		if ($post_data['submit'] == "Edit Site") {
			if (isset($post_data['edit_site_id']) &&
				isset($post_data['edit_site_domain']) ) {
				// 
				$blogid = $post_data['edit_site_id'];
				$domain = $post_data['edit_site_domain'];
					
				// export
				if (isset($post_data['edit_site_export']) && $post_data['edit_site_export'] != "") {
					Tools::download_blog( array(
						'mode' => 'site',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_site_import']) && $post_data['edit_site_import'] != "") {
					if ($post_data['edit_site_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'site', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_site_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'site', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_site_import']
						) );
					}
				}
			}
		}
		// content
		else if ($post_data['submit'] == "Edit Content") {
			if (isset($post_data['edit_content_id']) &&
				isset($post_data['edit_content_domain']) ) {
				// 
				$blogid = $post_data['edit_content_id'];
				$domain = $post_data['edit_content_domain'];
					
				// export
				if (isset($post_data['edit_content_export']) && $post_data['edit_content_export'] != "") {
					Tools::download_blog( array(
						'mode' => 'content',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_content_import']) && $post_data['edit_content_import'] != "") {
					if ($post_data['edit_content_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'content', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_content_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'content', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_content_import']
						) );
					}

				}
			}
		}
		// thememods
		else if ($post_data['submit'] == "Edit Mods") {
			if (isset($post_data['edit_mods_id']) &&
				isset($post_data['edit_mods_domain']) &&
				isset($post_data['edit_mods_option'])) {
				// 
				$blogid = $post_data['edit_mods_id'];
				$domain = $post_data['edit_mods_domain'];
				$option = $post_data['edit_mods_option'];

				// import
				if (isset($post_data['edit_mods_data']) && $post_data['edit_mods_data'] != "") {
					$mods = urldecode($post_data['edit_mods_data']);
					Tools::import_thememods($blogid, $domain, $mods, $option);
				}
				// reset (deprecated)
				// else if (isset($post_data['edit_mods_delete']) && $post_data['edit_mods_delete'] != "") {
				//     $this->deleteMods($option, $blogid, $domain);
				// }
			}
		}
		// global styles
		else if ($post_data['submit'] == "Edit Styles") {
			if (isset($post_data['edit_styles_id']) &&
				isset($post_data['edit_styles_domain']) ) {
				// 
				$blogid = $post_data['edit_styles_id'];
				$domain = $post_data['edit_styles_domain'];
					
				// export
				if (isset($post_data['edit_styles_export']) && $post_data['edit_styles_export'] != "") {
					Tools::download_styles($blogid, $domain);
				}
				// import
				else if (isset($post_data['edit_styles_import']) && $post_data['edit_styles_import'] != "") {
					Tools::import_styles($blogid, $domain, 'edit_styles_file');
				}
			}
		}


	}
	
	/**
	 * Advanced Post actions from 'Admin' Tab
	 */
	public function handle_post_data_admin( $post_data ) {

		// db
		if ($post_data['submit'] == "Edit DB") {
			if (isset($post_data['edit_db_id']) &&
				isset($post_data['edit_db_domain'])) {
				// 
				$blogid = $post_data['edit_db_id'];
				$domain = $post_data['edit_db_domain'];

				// export
				if (isset($post_data['edit_db_export']) && $post_data['edit_db_export'] != "") {
					Tools::download_blog( array(
						'mode' => 'db',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_db_import']) && $post_data['edit_db_import'] != "") {
					if ($post_data['edit_db_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'db', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_db_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'db', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_db_import']
						) );
					}
				}
			}
		}
		// files
		else if ($post_data['submit'] == "Edit Files") {
			if (isset($post_data['edit_files_id']) &&
				isset($post_data['edit_files_domain'])) {
				// 
				$blogid = $post_data['edit_files_id'];
				$domain = $post_data['edit_files_domain'];

				// export
				if (isset($post_data['edit_files_export']) && $post_data['edit_files_export'] != "") {
					Tools::download_blog( array(
						'mode' => 'files',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_files_import']) && $post_data['edit_files_import'] != "") {
					if ($post_data['edit_files_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'files', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_files_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'files', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_files_import']
						) );
					}
				}
			}
		}
		// themes
		else if ($post_data['submit'] == "Edit Themes") {
			if (isset($post_data['edit_themes_id']) && 
				isset($post_data['edit_themes_domain'])) {
				// 
				$blogid = intval( $post_data['edit_themes_id'] );
				$domain  = strval( $post_data['edit_themes_domain'] );

				// export
				if (isset($post_data['edit_themes_export']) && !empty($post_data['edit_themes_export'])) {
					Tools::download_blog( array(
						'mode' => 'themes',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_themes_import']) && !empty($post_data['edit_themes_import'])) {
					if ($post_data['edit_themes_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'themes', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_themes_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'themes', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_themes_import']
						) );
					}
				}
			}
		}
		// plugins
		else if ($post_data['submit'] == "Edit Plugins") {
			if (isset($post_data['edit_plugins_id']) &&
				isset($post_data['edit_plugins_domain'])) {
				// 
				$blogid = $post_data['edit_plugins_id'];
				$domain = $post_data['edit_plugins_domain'];

				// export
				if (isset($post_data['edit_plugins_export']) && $post_data['edit_plugins_export'] != "") {
					Tools::download_blog( array(
						'mode' => 'plugins',
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				else if (isset($post_data['edit_plugins_export_internal']) && $post_data['edit_plugins_export_internal'] != "") {
					Tools::download_blog( array(
						'mode' => 'plugins',
						'internal' => true,
						'blogid' => $blogid,
						'domain' => $domain
					) );
				}
				// import
				else if (isset($post_data['edit_plugins_import']) && $post_data['edit_plugins_import'] != "") {
					if ($post_data['edit_plugins_import'] == "yes") {
						Tools::import_blog( array(
							'mode' => 'plugins', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'input' => 'edit_plugins_file'
						) );
					}
					else {
						Tools::import_blog( array(
							'mode' => 'plugins', 
							'blogid' => $blogid, 
							'domain' => $domain, 
							'source' => $post_data['edit_plugins_import']
						) );
					}
				}
			}
		}

		// clean exports
		else if ($post_data['submit'] == "Edit Clean") {
			if (isset($post_data['edit_clean_id']) &&
				isset($post_data['edit_clean_domain']) ) {
				// 
				$blogid = $post_data['edit_clean_id'];
				$domain = $post_data['edit_clean_domain'];
				
				if (isset($post_data['edit_site_clean']) && $post_data['edit_site_clean'] != "") {
					Tools::download_blog( array(
						'mode' => 'site',
						'blogid' => $blogid,
						'domain' => $domain,
						'clean' => true
					) );
				}
				else if (isset($post_data['edit_content_clean']) && $post_data['edit_content_clean'] != "") {
					Tools::download_blog( array(
						'mode' => 'content',
						'blogid' => $blogid,
						'domain' => $domain,
						'clean' => true
					) );
				}
				else if (isset($post_data['edit_db_clean']) && $post_data['edit_db_clean'] != "") {
					Tools::download_blog( array(
						'mode' => 'db',
						'blogid' => $blogid,
						'domain' => $domain,
						'clean' => true
					) );
				}
			}
		}
		
		// Admin
		if ($post_data['submit'] == "Edit Greyd") {
			if (isset($post_data['edit_greyd_id']) &&
				isset($post_data['edit_greyd_domain']) &&
				isset($post_data['edit_greyd_mode']) ) {
				// 
				$blogid = $post_data['edit_greyd_id'];
				$domain = $post_data['edit_greyd_domain'];
				$mode = $post_data['edit_greyd_mode'];
				
				if ($post_data['edit_greyd_mode'] == "check") {
					Tools::check_blog($blogid); 
				}
			}
		}
		else if ($post_data['submit'] == "Edit Website") {
			if (isset($post_data['edit_website_id']) &&
				isset($post_data['edit_website_domain']) &&
				isset($post_data['edit_website_mode']) ) {
				// 
				$blogid = $post_data['edit_website_id'];
				$domain = $post_data['edit_website_domain'];
				$mode = $post_data['edit_website_mode'];

				if ($mode == "reset") {
					Tools::reset_blog($blogid);
				}
				else if ($mode == "delete") {
					Tools::delete_blog($blogid);
				}
			}
		}

	}


	/*
	=======================================================================
		Hub Tab: Websites
	=======================================================================
	*/

	/**
	 * Add Hub Page and Tab
	 * @see filter 'greyd_hub_pages'
	 */
	public function add_hub_page( $hub_pages ) {
		$hub_pages[] = array(
			"slug"      => "websites",
			"icon"      => "screenoptions",
			"class"     => "wp",
			"title"     => __('Websites', 'greyd_hub'),
			"title_cb"  => array($this, "render_title_actions"),
			"callback"  => array($this, "render_websites"),
			"priority"	=> 0
		);
		return $hub_pages;
	}


	/**
	 * Render the websites page title actions.
	 */
	public function render_title_actions() {

		// refresh notice
		self::render_refresh_notice();

		// backup files (for js select file - deactivated)
		// $files = Hub_Helper::get_backup_files();
		// if ( $files ) echo "<script> var backup_files = '".json_encode($files)."'; </script>";

		// raw theme-mods (for js)
		$raw_mods = Hub_Helper::get_raw_mods();
		if ($raw_mods) echo "<div id='raw_mods' data-mods='".$raw_mods."'></div>";

	}

	/**
	 * Render refresh notice (fade-in after 2 sec).
	 */
	public static function render_refresh_notice() {
		echo '<div id="greyd_hub_refresh" class="notice hidden is-dismissible notice-warning">
			<p>
				<b>'.__("Please refresh the page.", 'greyd_hub').'</b>
				<button type="button" class="button" onClick="window.location.reload();">'.__("Refresh page", 'greyd_hub').'</button>
			</p>
			<p>
				'.__("The content of the websites may have changed. To make sure everything is up to date, refresh this page before making changes or backups.", 'greyd_hub').'
			</p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__("Hide this message.", 'greyd_hub').'</span></button>
		</div>';
	}


	/**
	 * Render the websites page.
	 * @param string $nonce nonce field
	 */
	public function render_websites($nonce) {

		ob_start();

		// websites navbar (list, grid, preview, etc)
		$this->render_hub_navbar();

		$loader = '<div id="hub-loader" class="hub_panel loading">';
		$loader .= $this->get_skeleton_section( is_multisite() ? 3 : 1 );
		if (Admin::$connections) {
			foreach (Admin::$connections as $network_url => $connection) {
				$loader .= '<hr>';
				$loader .= '<h3 class="hub_remote_title">'.Helper::render_dashicon( "admin-site-alt" ).'&nbsp;<a>'.$network_url.'</a></h3>';
				$loader .= $this->get_skeleton_section( 1 );
			}
		}
		$loader .= '</div>';

		echo str_pad( $loader, 4096 );
		ob_flush();
		flush();
		// return;

		// get data for all blogs
		$blogs = Hub_Helper::get_all_networks();

		/**
		 * Greyd.Hub website tiles (all Blogs)
		 * 
		 * @filter greyd_hub_tiles
		 * 
		 * @param array  $blogs  All Blogs.
		 *      @property object local      All local Blogs.
		 *      @property object remote     All remote Blogs by network. (optional)
		 */
		$blogs = apply_filters( "greyd_hub_tiles", $blogs );

		// remove loader
		echo '<script> document.getElementById("hub-loader").remove(); </script>';

		// wrapper
		echo "<div class='hub_panel'>";

		// setup pagination
		$blogs_local = Hub_Helper::paginateBlogs( $blogs['local'] );
		Hub_Helper::render_pagination_links();

		// render local websites
		echo "<section><div class='greyd_tile_wrapper'>";

		foreach ($blogs_local as $url => $blog) {
			$this->render_website_tile( $blog, $nonce );
		}
		echo "</div></section>";

		Hub_Helper::render_pagination_links();

		// render remote websites
		if ( isset($blogs['remote']) ) {
			
			foreach ( $blogs['remote'] as $network_url => $remote_blogs ) {
				// debug( $remote_blogs, true );

				echo "<hr>";

				// error message
				if ( is_wp_error( $remote_blogs ) ) {
					echo "<h3 class='hub_remote_title'>
						".Helper::render_dashicon( "admin-site-alt" )."&nbsp;
						<a id='".sanitize_title($network_url)."' href='{$network_url}' target='_blank' title='".__("Open in new tab", 'greyd_hub')."' style='text-decoration: none'>{$network_url}</a>
					</h3>";
					echo Helper::render_info_box(array(
						"style" => "red",
						"above" => __("Error fetching the websites", "greyd_hub"),
						"text"  => $remote_blogs->get_error_message()
					));
					continue;
				}

				if (isset($remote_blogs['basic']['action_url'])) {
					$action = $remote_blogs['basic']['action_url'];
				}
				else {
					$blog_table_name = $remote_blogs['basic']['prefix'].'blogs';
					$is_multisite    = isset($remote_blogs['basic']['tables']) && array_search( $blog_table_name, $remote_blogs['basic']['tables'] );
					$action          = $is_multisite ? "https://{$network_url}/wp-admin/network/admin.php?page=greyd_hub" : "https://{$network_url}/wp-admin/admin.php?page=greyd_hub";
				}

				// network headline
				echo "<h3 class='hub_remote_title'>
					".Helper::render_dashicon( "admin-site-alt" )."&nbsp;
					<a id='".sanitize_title($network_url)."' href='{$action}' target='_blank' title='".__("Open in new tab", 'greyd_hub')."' style='text-decoration: none'>{$network_url}</a>
				</h3>";

				// setup pagination
				$remote_blogs = Hub_Helper::paginateBlogs( $remote_blogs, 'paged_' . $network_url );
				Hub_Helper::render_pagination_links( 'paged_' . $network_url );

				// render the tiles
				echo "<section><div class='greyd_tile_wrapper'>";
				foreach( $remote_blogs as $i => $blog ) {
					$this->render_website_tile( $blog, $nonce );
				}
				echo "</div></section>";

				Hub_Helper::render_pagination_links( 'paged_' . $network_url );
			}
		}

		echo "</div>";

		// flush the output buffer
		ob_flush();
		flush();

		// end output buffering
		ob_end_flush();
	}

	/**
	 * Render the hub navbar.
	 */
	public function render_hub_navbar() {
		$hub_url = Admin::$page['url'];

		$block_tabs = "";
		if ( Admin::$connections ) {
			$block_tabs = "<div class='block_tabs'>
				<a class='block_tab active' href='#'>
				".__("This installation", 'greyd_hub')."
				</a>
				".implode( "", array_map( function($url){ return "<a class='block_tab' href='#".sanitize_title($url)."'>{$url}</a>"; }, array_keys( Admin::$connections ) ) )."
				<a class='block_tab add-connection' href='{$hub_url}&tab=connections' title='".__("Add connection", "greyd_hub")."'>＋</a>
			</div>";
		}

		$nav_actions = "<div class='nav-actions'>
			<input type='radio' id='layout-tiles' name='layout' value='tiles'/>
			<label for='layout-tiles'>".Helper::render_dashicon("grid-view")."</label>
			<input type='radio' id='layout-list' name='layout' value='list'/>
			<label for='layout-list'>".Helper::render_dashicon("list-view")."</label>
			<span class='divider'></span>
			<input type='checkbox' id='thumbs-toggle'>
			<label for='thumbs-toggle'>".Helper::render_dashicon("welcome-view-site")."</label>
			<span class='divider'></span>
			<input type='radio' id='tabs-action' name='tabs' value='action'/>
			<label class='block_tab' for='tabs-action'>".__("Actions", 'greyd_hub')."</label>
			<input type='radio' id='tabs-info' name='tabs' value='info'/>
			<label class='block_tab' for='tabs-info'>".__("Information", 'greyd_hub')."</label>
			<input type='radio' id='tabs-admin' name='tabs' value='admin'/>
			<label class='block_tab' for='tabs-admin'>".__("More", 'greyd_hub')."</label>
			".(
				!Admin::$connections && !is_multisite() ? "" :
				"<span class='divider'></span>
				<label for='filter-sites'>".Helper::render_dashicon("search")."<input type='search' id='filter-sites' name='filter-sites' placeholder='".__("Domain or title", 'greyd_hub')."' value=''/></label>"
			)."
		</div>";

		$navbar = "<form class='hub-nav-wrap is-loading'>
					<div class='hub-nav'>
						$block_tabs
						$nav_actions
					</div>
				</form>";

		echo str_pad( $navbar, 4096 );
		ob_flush();
		flush();
	}


	/*
	=======================================================================
		Website Tile
	=======================================================================
	*/

	/**
	 * Render a website tile.
	 * Can be extended or overwritten with filter 'greyd_hub_tile'
	 * 
	 * @param array $blog   @see Hub_Helper::get_all_blogs() for details.
	 * @param string $nonce nonce field
	 */
	public function render_website_tile( $blog, $nonce ) {

		$tile = "";
		// render single tile if blog_id is correct
		if (isset($blog["blog_id"]) && $blog['blog_id'] > 0) {
			$tile = self::render_tile( $blog, $nonce );
		}
		
		/**
		 * Greyd.Hub website tile rendering.
		 * 
		 * @filter greyd_hub_tile
		 * 
		 * @param string $tile  The rendered website tile.
		 * @param array $blog   Blog details.
		 * @param string $nonce nonce field.
		 */
		$tile = apply_filters( "greyd_hub_tile", $tile, $blog, $nonce );

		// echo tile if it is not empty
		if (!empty($tile)) {
			echo $tile;
			ob_flush();
			flush();
		}

	}

	/**
	 * Render a single website tile.
	 * 
	 * @param array $blog   @see Hub_Helper::get_all_blogs() for details.
	 * @param string $nonce nonce field
	 */
	public static function render_tile( $blog, $nonce ) {

		// @see Hub_Helper::get_all_blogs() for details
		$blog = wp_parse_args( (array)$blog, array(
			"blog_id"       => 0,
			"name"          => "",
			"description"   => "",
			"admin"         => "",
			"domain"        => "",
			"http"          => "",
			"prefix"        => "",
			"current"       => false,
			"tables"        => array(),
			"attributes"    => array(),
			"plugins"       => array(),
			"plugins_list"  => array(),
			"theme"         => "",
			"theme_version" => "",
			"theme_slug"    => "",
			"theme_main"    => "",
			"theme_main_slug" => "",
			"theme_main_version" => "",
			"theme_mods"    => array(),
			// remote args
			"is_remote"     => false,
			"action_url"    => "",
			"network"       => Hub_Helper::get_nice_url(Admin::$urls->network_url)
		) );

		if ($blog['blog_id'] < 1) return;

		/**
		 * Get theme_mods from raw options key.
		 * Ensures backwards compatiblity below @version 1.2.2
		 */
		if ( empty($blog['theme_mods']) ) {
			$theme_mods_slug = 'theme_mods_' . $blog['theme_slug'];
			if ( isset( $blog[ $theme_mods_slug ] ) ) {
				$blog['theme_mods'] = $blog[ $theme_mods_slug ];
			}
		}

		// make sure remote flag is set
		if ( !$blog['is_remote'] && ($blog['network'] != Hub_Helper::get_nice_url(Admin::$urls->network_url)) ) {
			$blog['is_remote'] = true;
		}

		// vars
		$vars = array(
			'nonce'			=> $blog['is_remote'] ? Hub_Helper::make_remote_nonce() : $nonce,
			'action'		=> $blog['is_remote'] ? "action='".$blog['action_url']."'" : "",
			'site_url'		=> $blog['is_remote'] ? 'https://'.$blog['domain'] : get_site_url($blog['blog_id']),
			'home_url'		=> $blog['is_remote'] ? 'https://'.$blog['domain'] : get_home_url($blog['blog_id']),
			'is_greyd'		=> Hub_Helper::greyd_suite_active($blog['theme_main_slug']),
			'is_greyd_theme'=> Hub_Helper::greyd_theme_active($blog['theme_main_slug']),
			'is_parent'		=> $blog['blog_id'] <= 1 && is_multisite() && !$blog['is_remote'],
			// dashicons
			'icons'			=> (object)array(
				"blank"     => Helper::render_dashicon( "external" ),
				"download"  => Helper::render_dashicon( "arrow-down-alt" ),
				"upload"    => Helper::render_dashicon( "arrow-up-alt" ),
				"reset"     => Helper::render_dashicon( "undo" ),
				"delete"    => Helper::render_dashicon( "trash" ),
				"db"        => Helper::render_dashicon( "admin-tools" ),
				"new"       => Helper::render_dashicon( "yes-alt" ),
				"save"      => Helper::render_dashicon( "saved" ),
				"cancel"    => Helper::render_dashicon( "no-alt" ),
				"activate"  => Helper::render_dashicon( "controls-play" ),
				"lock"      => Helper::render_dashicon( "lock" ),
				"locked"    => "<small style='float:right; margin: 4px -8px;'>".Helper::render_dashicon( "lock" )."</small>",
				"global"    => "<small style='float:right; margin: 4px -8px;'>".Helper::render_dashicon( "admin-site-alt" )."</small>",
				"move"      => Helper::render_dashicon( "arrow-right-alt" ),
				"create"    => Helper::render_dashicon("plus-alt2"),
				// list view
				"list_down"     => Helper::render_dashicon("arrow-down-alt list_view__hide"),
				"list_full"     => Helper::render_dashicon("desktop list_view__only"),
				"list_content"  => Helper::render_dashicon("format-aside list_view__only"),
				"list_db"       => Helper::render_dashicon("database-import list_view__only")
			)
		);

		/**
		 * Greyd.Hub website tile pages
		 * 
		 * @filter greyd_hub_tile_pages
		 * 
		 * @param array  $tile_pages  All tabs as array.
		 *      @property string slug       Slug of the tab.
		 *      @property string class      CSS class name.
		 *      @property string title      Title of the tab.
		 *      @property callback callback Callback function to render the tabs content.
		 *      @property array args        Arguments to apply to the callback.
		 *      @property int priority      position of the tab (optional, 0: first, 99: last, defaults to 10)
		 */
		$tile_pages = apply_filters( "greyd_hub_tile_pages", array(
			// action
			array(
				"slug"      => "action",
				"title"     => __("Actions", 'greyd_hub'),
				"callback"  => array(__CLASS__, "render_main"),
				"priority"	=> 0
			),
			// info
			array(
				"slug"      => "info",
				"title"     => __("Information", 'greyd_hub'),
				"callback"  => array(__CLASS__, "render_infos"),
				"priority"	=> 1
			),
			// admin
			array(
				"slug"      => "admin",
				"title"     => __("More", 'greyd_hub'),
				"callback"  => array(__CLASS__, "render_admin"),
				"priority"	=> 2
			),
			// dev
			// array(
			// 	"slug"      => "staging",
			// 	"title"     => __("Stage", 'greyd_hub'),
			// 	"callback"  => function() {
			// 		echo self::make_row( array('content' => "Hello Staging!") );
			// 	},
			// 	"priority"	=> 3
			// )
		) );

		// sort pages by priority
		usort($tile_pages, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});


		/**
		 * Render Tile
		 */

		/**
		 * Greyd.Hub website tile html attributes.
		 * 
		 * @filter greyd_hub_tile_attributes
		 * 
		 * @param array $tile_attributes  Array of html attributes (class, data-*, etc).
		 * @param array $blog             Blog details.
		 */
		$tile_attributes = apply_filters( "greyd_hub_tile_attributes", array(
			"class" => "greyd_tile",
			"data-id" => $blog['blog_id'],
			"data-domain" => $blog['domain'],
			"data-name" => $blog['name'],
			"data-network" => $blog['network']
		), $blog );

		// tile wrapper
		$atts = array();
		foreach ($tile_attributes as $key => $value) {
			$atts[] = $key."='".$value."'";
		}
		$tile = "<div ".implode(" ", $atts).">";

		/**
		 * Head
		 */
		$tile .= self::render_head( $blog, $vars );

		/**
		 * Tablist
		 */
		$first = true;
		$tile .= '<ul class="greyd_tabs tab_list">';
		foreach( $tile_pages as $tile_page ) {
			$tile_page = wp_parse_args( $tile_page, array(
				"slug"      => "",
				"title"     => "Tab",
				"class"     => "",
			) );
			$tab_classes = array( "tab" );
			if (!empty($tile_page['class'])) $tab_classes[] = $tile_page['class'];
			if ($first) $tab_classes[] = "active";

			$tile .= "<li class='".implode(' ', $tab_classes)."' data-show='".$tile_page['slug']."'>".$tile_page['title']."</li>";
			
			$first = false;
		}
		$tile .= '</ul>';

		/**
		 * Tab contents
		 */
		$first = true;
		foreach( $tile_pages as $tile_page ) {
			$tile_page = wp_parse_args( $tile_page, array(
				"slug"      => "",
				"callback"  => null,
				"args"      => array()
			) );
			if (empty($tile_page['callback'])) continue;
			$tile_classes = array( "inner", "tab_view" );
			if ($first) $tile_classes[] = "active";

			$tile .= "<div class='".implode(' ', $tile_classes)."' data-tab='".$tile_page['slug']."'>";
				$args = array_merge( $tile_page['args'], array( $blog, $vars ) );
				$tile .= call_user_func_array( $tile_page["callback"], $args );
			$tile .= "</div>";

			$first = false;
		}

		// end tile wrapper
		$tile .= "</div>";

		return $tile;

	}

	/**
	 * Render head of Tile Page content.
	 * 
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 */
	public static function render_head( $blog, $vars ) {

		// init rows
		$rows = array();

		/**
		 * website iFrame
		 */
		$rows[] = array(
			'slug' => 'frame',
			'content' =>
				"<div class='hub_frame_wrapper' style='display:none;'>".
					"<div class='hub_frame' data-siteurl='".$vars['home_url']."'>".
						"<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>".
					"</div>".
					"<a class='overlay' href='".$vars['home_url']."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".$vars['icons']->blank."</span></a>".
				"</div>",
			'priority' => 1
		);

		/**
		 * domain & title
		 */
		$rows[] = array(
			'slug' => 'title',
			'content' =>
				"<div class='hub_domain_and_title'>".
					"<div class='hub_domain'>
						<a class='' href='".$vars['home_url']."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>".$blog['domain']."</a>".
						( $vars['is_parent'] ? " (".__("Home", 'greyd_hub').")".$vars['icons']->locked : "" ).
						( $blog['is_remote'] ? " (".__("Remote", 'greyd_hub').")".$vars['icons']->global : "" ).
					"</div>".
					"<hr>".
					"<div class='hub_title'>".($blog['is_remote'] ?
						"<h4>".$blog['name']."</h4>
						<h5>".$blog['description']."</h5>"
						:
						"<form class='change_bloginfo'>
							<textarea name='blogname' class='h4 input' data-reset='".$blog['name']."' title='"._x("Edit site title", "small", "greyd_hub")."'>".$blog['name']."</textarea>
							<h4 class='preview' data-empty='".__("+ Add title", "small", "greyd_hub")."'>".$blog['name']."</h4>
							<div class='buttons'>
								<button type='submit' class='button button-primary' name='submit' title='".__("Confirm", "greyd_hub")."' >".$vars['icons']->save."</button>
								<button type='reset' class='button button-ghost' name='reset' title='".__("Cancel", "greyd_hub")."' >".$vars['icons']->cancel."</button>
							</div>
							<div class='icons'>
								<div class='loader tiny'></div><div class='color_green'>".$vars['icons']->save."</div><div class='color_red'>".$vars['icons']->cancel."</div>
							</div>
						</form>".
						"<form class='change_bloginfo'>
							<textarea name='blogdescription' class='h5 input' data-reset='".$blog['description']."' title='"._x("Edit site description", "small", "greyd_hub")."'>".$blog['description']."</textarea>
							<h5 class='preview' data-empty='".__("+ Add description", "small", "greyd_hub")."'>".$blog['description']."</h5>
							<div class='buttons'>
								<button type='submit' class='button button-primary' name='submit' title='".__("Confirm", "greyd_hub")."' >".$vars['icons']->save."</button>
								<button type='reset' class='button button-ghost' name='reset' title='".__("Cancel", "greyd_hub")."' >".$vars['icons']->cancel."</button>
							</div>
							<div class='icons'>
								<div class='loader tiny'></div><div class='color_green'>".$vars['icons']->save."</div><div class='color_red'>".$vars['icons']->cancel."</div>
							</div>
						</form>"
					)."</div>".
				"</div>".
				"<hr>",
			'priority' => 2
		);

		/**
		 * Links
		 */

		// dashboard
		$dashboard_url = $vars['site_url']."/wp-admin";
		if ( $vars['is_greyd'] || in_array("greyd_tp_management/init.php", $blog['plugins_list']) || in_array("greyd-plugin/init.php", $blog['plugins_list']) ) 
			$dashboard_url .= "/admin.php?page=greyd_dashboard";
		$dashboard_link = "<a href='".$dashboard_url."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>".$vars['icons']->blank."&nbsp;<span>".__("Dashboard", 'greyd_hub')."</span></a>";
		// customizer/site-editor
		$editor_link = "";
		if ( $vars['is_greyd'] ) {
			$editor_url = $vars['site_url']."/wp-admin/customize.php";
			$editor_link = "<a href='".$editor_url."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>".$vars['icons']->blank."&nbsp;<span>".__("Customizer", 'greyd_hub')."</span></a>";
		}
		else if ( $vars['is_greyd_theme'] ) {
			$editor_url = $vars['site_url']."/wp-admin/site-editor.php";
			$editor_link = "<a href='".$editor_url."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>".$vars['icons']->blank."&nbsp;<span>".__("Site Editor", 'greyd_hub')."</span></a>";
		}
		// network setup
		$setup_url = network_admin_url("site-info.php?id=".$blog['blog_id']);
		$setup_link = is_multisite() && !$blog['is_remote'] ? "<a href='".$setup_url."' target='_blank'>".$vars['icons']->blank."&nbsp;<span>".__("Setup", 'greyd_hub')."</span></a>" : "";
		
		// render
		$rows[] = array(
			'slug' => 'links',
			'content' =>
				"<div class='hub_links'>".
					$dashboard_link.
					$editor_link.
					$setup_link.
				"</div>",
			'priority' => 4
		);

		/**
		 * Greyd.Hub website tile head
		 * 
		 * @filter greyd_hub_tile_head
		 * 
		 * @param array  $rows          All rows as array.
		 *      @property string slug       Slug of the row.
		 *      @property string content    Row content.
		 *      @property int priority      position of the row (optional, 0: first, 99: last, defaults to 10)
		 * @param array $blog			Blog details.
		 * @param array $vars			Blog vars used in tile.
		 */
		$rows = apply_filters( "greyd_hub_tile_head", $rows, $blog, $vars );

		// sort rows by priority
		usort($rows, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});

		// render rows
		$content = "";
		foreach( $rows as $row ) {
			$row = wp_parse_args( $row, array(
				"slug"      => "",
				"content"   => ""
			) );
			// echo $row['content'];
			$content .= $row['content'];
		}
		return $content;
	}

	/**
	 * Render main 'Actions' Tile Page content.
	 * 
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 */
	public static function render_main( $blog, $vars ) {

		// init rows
		$rows = array();

		// full site
		$rows[] = array(
			'slug' => 'site',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Entire website", 'greyd_hub')."</p>".
					"<div>".
						"<small>".__("Media, database, plugins and themes as .zip", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Migration of the entire website.", 'greyd_hub')."</h3>
							<p>".__("Use this download to migrate a site along with all plugins, themes and content to another WordPress installation.", 'greyd_hub')."</p>
							<p>".__("No user or multisite information will be exported.", 'greyd_hub')."</p>
							<p><b>".__("Problems? This download can be large and slow in parts.", 'greyd_hub')."</b></p>
							<p>".__("A common cause are a lot or very large media and images.", 'greyd_hub')."</p>
							<p>".__("Tip: Download media, database, themes and plugins individually in the \"Advanced\" tab. Then upload the media e.g. via FTP and the rest with Greyd.Hub. This way even the migration of very large websites should work without problems.", 'greyd_hub')."</p>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_site_file' id='edit_site_file_".$blog['blog_id']."' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_site_export' id='edit_site_export_".$blog['blog_id']."' value=''>
						<input type='hidden' name='edit_site_import' id='edit_site_import_".$blog['blog_id']."' value=''>
						<input type='hidden' name='edit_site_id' id='edit_site_id_".$blog['blog_id']."' value='".$blog['blog_id']."'>
						<input type='hidden' name='edit_site_domain' id='edit_site_domain_".$blog['blog_id']."' value='".$blog['domain']."'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_site_".$blog['blog_id']."' value='Edit Site'>
						
					</form>
					<span id='btn_export_site' class='button small' data-event='button' data-args='advanced' title='"._x("Download website", "small", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_site' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Upload website", "small", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
					<!-- <span id='btn_select_site' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
				</div>",
			'priority' => 1
		);

		// contents
		$rows[] = array(
			'slug' => 'content',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Content", 'greyd_hub')."</p>".
					"<div>".
						"<small>".__("Media and database as .zip", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Download of all content.", 'greyd_hub')."</h3>
							<p>".__("This download contains all posts, pages, media, etc.", 'greyd_hub')."</p>
							<p>".__("Plugins, users and multisite information are not exported.", 'greyd_hub')."</p>
							<p>".__("This allows you to copy websites within a multisite as the plugins and themes are already there.", 'greyd_hub')."</p>
							<p>".__("Key advantage: This download is smaller and faster than downloading an entire website. You can also use it for migration from/to staging sites.", 'greyd_hub')."</p>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_content_file' id='edit_content_file_".$blog['blog_id']."' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_content_export' id='edit_content_export_".$blog['blog_id']."' value=''>
						<input type='hidden' name='edit_content_import' id='edit_content_import_".$blog['blog_id']."' value=''>
						<input type='hidden' name='edit_content_id' id='edit_content_id_".$blog['blog_id']."' value='".$blog['blog_id']."'>
						<input type='hidden' name='edit_content_domain' id='edit_content_domain_".$blog['blog_id']."' value='".$blog['domain']."'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_content_".$blog['blog_id']."' value='Edit Content'>
						
					</form>
					<span id='btn_export_content' class='button button-primary small' data-event='button' data-args='advanced' title='".__("Download content", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_content' class='button small button-ghost' data-event='button' data-args='advanced' title='".__("Upload content", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
					<!-- <span id='btn_select_content' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
				</div>",
			'priority' => 2
		);

		// only integrated
		if ( !Admin::$is_standalone ) {

			// thememods
			if ( $vars['is_greyd'] ) {

				$domain = str_replace("/", "-", untrailingslashit( $blog['domain'] ));
				$file_name = $blog['domain']."_design_".date("ymd");
				// normalize uploads url
				$replace = array(
					'old' => array( 'upload_url' => wp_upload_dir()["baseurl"] ),
					'new' => array( 'upload_url' => $blog['http'].'://'.$blog['domain']."/wp-content/uploads" )
				);
				$mods = Hub_Helper::url_replace(json_encode($blog['theme_mods']), $replace);
				$data = urlencode("{\n\"version\":\"".$blog['theme_version']."\",\n\"siteurl\":\"".$vars['site_url']."\",\n\"blog_id\":\"".$blog['blog_id']."\",\n\"mods\":".$mods."\n}");

				$rows[] = array(
					'slug' => 'mods',
					'content' =>
						"<div class='flex inner_head'>".
							"<p>".__("Active design", 'greyd_hub')."</p>".
							"<small>".__("Customizer settings as .data", 'greyd_hub')."</small>".
						"</div>".
						"<div class='data-holder' data-mods='".$data."' data-name='".$file_name."' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."' data-siteurl='".$vars['site_url']."'>
							<form method='post' ".$vars['action']." >".$vars['nonce']."
								<input type='hidden' name='edit_mods_delete' id='edit_mods_delete_".$blog['blog_id']."' value=''>
								<input type='hidden' name='edit_mods_data' id='edit_mods_data_".$blog['blog_id']."' value=''>
								<input type='hidden' name='edit_mods_option' id='edit_mods_option_".$blog['blog_id']."' value='theme_mods_".$blog['theme_slug']."'>
								<input type='hidden' name='edit_mods_id' id='edit_mods_id_".$blog['blog_id']."' value='".$blog['blog_id']."'>
								<input type='hidden' name='edit_mods_domain' id='edit_mods_domain_".$blog['blog_id']."' value='".$blog['domain']."'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_mods_".$blog['blog_id']."' value='Edit Mods'>
								
							</form>
							<span id='btn_export_mods' class='button small' data-event='button' title='".__("Download file", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
							<span id='btn_import_mods' class='button small button-ghost' data-event='button' title='".__("Upload file", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
							<!-- <span id='btn_select_mods' class='button small button-ghost' data-event='button' title='".__("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
							<!-- <span id='btn_reset_mods' class='button small button-danger' data-event='button' title='".__("Reset", 'greyd_hub')."'>".$vars['icons']->reset."</span> -->
						</div>",
					'priority' => 3
				);

			}
			// global styles
			else if ( $vars['is_greyd_theme'] ) {
				
				$rows[] = array(
					'slug' => 'mods',
					'content' =>
						"<div class='flex inner_head'>".
							"<p>".__("Active design", 'greyd_hub')."</p>".
							"<small>".__("Global Styles as JSON", 'greyd_hub')."</small>".
						"</div>".
						"<div class='data-holder' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."' data-siteurl='".$vars['site_url']."'>
							<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
								<input style='display:none' type='file' type='hidden' name='edit_styles_file' id='edit_styles_file_".$blog['blog_id']."' value='' maxlength='".wp_max_upload_size()."'>
								<input type='hidden' name='edit_styles_export' id='edit_styles_export_".$blog['blog_id']."' value=''>
								<input type='hidden' name='edit_styles_import' id='edit_styles_import_".$blog['blog_id']."' value=''>
								<input type='hidden' name='edit_styles_id' id='edit_styles_id_".$blog['blog_id']."' value='".$blog['blog_id']."'>
								<input type='hidden' name='edit_styles_domain' id='edit_styles_domain_".$blog['blog_id']."' value='".$blog['domain']."'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_styles_".$blog['blog_id']."' value='Edit Styles'>
								
							</form>
							<span id='btn_export_styles' class='button small' data-event='button' data-args='advanced' title='".__("Download file", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
							<span id='btn_import_styles' class='button small button-ghost' data-event='button' data-args='advanced' title='".__("Upload file", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
						</div>",
					'priority' => 3
				);

			}

			// activate greyd
			if ( !$vars['is_greyd'] && !$vars['is_greyd_theme'] ) {

				$rows[] = array(
					'slug' => 'template',
					'content' =>
						"<div class='flex inner_head'>
							<p>".__("Greyd.Suite", 'greyd_hub')."</p>
							<div>".(
								$blog['is_remote'] ? Helper::render_info_dialog(
									"<h3>".__("This is a different installation", 'greyd_hub')."</h3>
									<smpall>".__("You can not activate the Greyd.Suite from here - go to the Greyd.Hub of the installation.", 'greyd_hub')."</p>"
								) : ""
							)."</div>
						</div>
						<div class='data-holder' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."'>
							<form method='post'>".$vars['nonce']."
								<input type='hidden' name='edit_greyd_mode' id='edit_greyd_mode_".$blog['blog_id']."' value=''>
								<input type='hidden' name='edit_greyd_id' id='edit_greyd_id_".$blog['blog_id']."' value='".$blog['blog_id']."'>
								<input type='hidden' name='edit_greyd_domain' id='edit_greyd_domain_".$blog['blog_id']."' value='".$blog['domain']."'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_greyd_".$blog['blog_id']."' value='Edit Greyd'>
								
							</form>".(
								$blog['is_remote'] ? 
								"<span id='btn_setup_greyd' class='button button-greyd small' data-event='button' data-args='admin' disabled style='pointer-events:none;' title='"._x("Activate Greyd.Suite", "small", 'greyd_hub')."'><span>"._x("Activate", "small", 'greyd_hub')."</span>".$vars['icons']->lock."</span>" :
								"<span id='btn_setup_greyd' class='button button-greyd small' data-event='button' data-args='admin' title='"._x("Activate Greyd.Suite", "small", 'greyd_hub')."'>".$vars['icons']->activate."<span>"._x("Activate", "small", 'greyd_hub')."</span></span>"
							)."
						</div>",
					'priority' => 5
				);

			}

		}

		/**
		 * Greyd.Hub website tile page 'Actions'
		 * 
		 * @filter greyd_hub_tile_page_action
		 * 
		 * @param array  $rows          All rows as array.
		 *      @property string slug       Slug of the row.
		 *      @property string class      CSS class name.
		 *      @property string content    Row content.
		 *      @property int priority      position of the row (optional, 0: first, 99: last, defaults to 10)
		 * @param array $blog			Blog details.
		 * @param array $vars			Blog vars used in tile.
		 */
		$rows = apply_filters( "greyd_hub_tile_page_action", $rows, $blog, $vars );

		// sort rows by priority
		usort($rows, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});

		// render rows
		$content = "";
		foreach( $rows as $row ) {
			// echo self::make_row( $row );
			$content .= self::make_row( $row );
		}
		return $content;

	}

	/**
	 * Render 'Info' Tile Page content.
	 * 
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 */
	public static function render_infos( $blog, $vars ) {

		// init rows
		$rows = array();

		// Blog ID and admin
		$rows[] = array(
			'slug' => 'details',
			'class' => 'column',
			'content' =>
				"<div class='inner_head' style='display:inline-block;margin-right:20px;'>".
					"<span>".__("Blog ID:", 'greyd_hub')."&nbsp;</span>".
					"<strong>".$blog['blog_id']."</strong>".
				"</div>".
				"<div class='inner_head' style='display:inline-block;'>".
					"<span>".__("Admin:", 'greyd_hub')."&nbsp;</span>".
					"<strong>".$blog['admin']."</strong>".
				"</div>",
			'priority' => 1
		);

		if (!empty($blog['attributes'])) {

			// debug($blog['attributes']);
			// debug(mysql2date(get_option( 'date_format' ), $blog['attributes']['registered']));
			// debug(mysql2date(get_option( 'date_format' ), $blog['attributes']['last_updated']));
			
			if (isset($blog['attributes']['registered'])) $rows[count($rows)-1]['content'] .=
				"<br><br>".
				"<div class='inner_head flex flex-start'>".
					"<span>".__("Registered:", 'greyd_hub')."&nbsp;</span>".
					"<strong>".mysql2date(get_option( 'date_format' ), $blog['attributes']['registered'])."</strong>".
				"</div>";
			if (isset($blog['attributes']['registered'])) $rows[count($rows)-1]['content'] .=
				"<div class='inner_head flex flex-start'>".
					"<span>".__("Updated:", 'greyd_hub')."&nbsp;</span>".
					"<strong>".mysql2date(get_option( 'date_format' ), $blog['attributes']['last_updated'])."</strong>".
				"</div>";

			$rows[] = array(
				'slug' => 'atts',
				'class' => 'column',
				'content' =>
					(isset($blog['attributes']['public']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Public:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'public', $vars).
						"</div>" : "").
					(isset($blog['attributes']['archived']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Archived:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'archived', $vars).
						"</div>" : "").
					(isset($blog['attributes']['spam']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Spam:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'spam', $vars).
						"</div>" : "").
					(isset($blog['attributes']['deleted']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Deleted:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'deleted', $vars).
						"</div>" : "").
					(isset($blog['attributes']['mature']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Adult content:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'mature', $vars).
						"</div>" : "").
					(isset($blog['attributes']['protected']) ? 
						"<div class='inner_head flex flex-start'>".
							"<span>".__("Protected:", 'greyd_hub')."&nbsp;</span>".self::make_form($blog, 'protected', $vars).
						"</div>" : ""),
				'priority' => 1
			);
		}

		// Theme
		$rows[] = array(
			'slug' => 'theme',
			'content' =>
				"<div class='inner_head'>".
					"<p>".__("Active theme:", 'greyd_hub')."</p>".
					"<strong>".$blog['theme']."</strong>&nbsp;(".$blog['theme_version'].")".
					($blog['theme_main_slug'] != $blog['theme_slug'] ? sprintf(
						"<p style='margin-top:6px'>%s<br>%s</p>",
						__("Child theme of:", 'greyd_hub'),
						"<strong>".$blog['theme_main']."</strong>&nbsp;(".$blog['theme_main_version'].")"
					) : "").
				"</div>",
			'priority' => 2
		);

		// Plugins
		$content = "<div class='inner_head'>".
					"<p>".__("Active plugins:", 'greyd_hub')."</p>";
		if ( count($blog['plugins']) > 0 ) {
			$content .= "<ul class='small'>";
			foreach ($blog['plugins'] as $plugin) {
				if (!is_array($plugin))
					$content .= "<li>".$plugin."</li>";
				else if ($plugin["installed"]) 
					$content .= "<li>".$plugin["name"]."</li>";
				else 
					$content .= "<li><span style='opacity:.5'>".sprintf(__("Plugin '%s' not found", 'greyd_hub'), $plugin["name"])."</span></li>";
			};
			$content .= "</ul>";
		} 
		else {
			$content .= "<div style='opacity:.5'>".__("No plugins active", 'greyd_hub')."</div>";
		}
		$content .= "</div>";
		$rows[] = array(
			'slug' => 'plugins',
			'content' => $content,
			'priority' => 3
		);

		/**
		 * Greyd.Hub website tile page 'Infos'
		 * 
		 * @filter greyd_hub_tile_page_info
		 * 
		 * @param array  $rows          All rows as array.
		 *      @property string slug       Slug of the row.
		 *      @property string class      CSS class name.
		 *      @property string content    Row content.
		 *      @property int priority      position of the row (optional, 0: first, 99: last, defaults to 10)
		 * @param array $blog			Blog details.
		 * @param array $vars			Blog vars used in tile.
		 */
		$rows = apply_filters( "greyd_hub_tile_page_info", $rows, $blog, $vars );

		// sort rows by priority
		usort($rows, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});

		// render rows
		$content = "";
		foreach( $rows as $row ) {
			// echo self::make_row( $row );
			$content .= self::make_row( $row );
		}
		return $content;

	}

	public static function make_form($blog, $attribute, $vars) {

		$val = $blog['attributes'][$attribute];
		
		if ($attribute == 'protected') {
			$val_pw = "";
			if ( strpos( $val, '3::' ) === 0 ) {
				$tmp = explode( '::', $val, 2 );
				$val = $tmp[0];
				$val_pw = $tmp[1];
			}
			switch ($val) {
				case '0': $value = "<span>".__("No", 'greyd_hub')."</span>"; break;
				case '1': $value = "<strong>".__("Users only", 'greyd_hub')."</strong>"; break;
				case '2': $value = "<strong>".__("Admins only", 'greyd_hub')."</strong>"; break;
				case '3': $value = "<strong>".__("Password", 'greyd_hub')."</strong>"; break;
				default:  $value = "";
			}
			$input = "<select style='font-weight:".($val == '0' ? "normal" : "600")."' class='input' name='protected' data-reset='".$val."' autocomplete='off'>
						<option style='font-weight:normal' value='0' ".($val == '0' ? "selected" : "").">".__("No", 'greyd_hub')."</option>
						<option style='font-weight:normal' value='1' ".($val == '1' ? "selected" : "").">".__("Users only", 'greyd_hub')."</option>
						<option style='font-weight:normal' value='2' ".($val == '2' ? "selected" : "").">".__("Admins only", 'greyd_hub')."</option>
						<option style='font-weight:normal' value='3' ".($val == '3' ? "selected" : "").">".__("Password", 'greyd_hub')."</option>
					</select>
					<input style='display:".($val == '3' ? "block" : "none")."' class='input' name='pw' type='text' value='' data-reset='' placeholder='".($val_pw == "" ? __("Enter password ...", 'greyd_hub') : "●●●●")."' autocomplete='new-password'>";
		}
		else {
			$value = $val == '0' ? "<span>".__("No", 'greyd_hub')."</span>" : "<strong>".__("Yes", 'greyd_hub')."</strong>";
			$input = "<select style='font-weight:".($val == '0' ? "normal" : "600")."' class='input' name='".$attribute."' data-reset='".$val."' autocomplete='off'>
						<option style='font-weight:normal' value='0' ".($val == '0' ? "selected" : "").">".__("No", 'greyd_hub')."</option>
						<option style='font-weight:normal' value='1' ".($val == '1' ? "selected" : "").">".__("Yes", 'greyd_hub')."</option>
					</select>";
		}

		if ($blog['is_remote']) 
			return $value;
		else
			return "<form class='change_bloginfo2'>
						".$input."
						<div class='buttons'>
							<button type='submit' class='button button-primary' name='submit' title='".__("Confirm", "greyd_hub")."' >".$vars['icons']->save."</button>
							<button type='reset' class='button button-ghost' name='reset' title='".__("Cancel", "greyd_hub")."' >".$vars['icons']->cancel."</button>
						</div>
						<div class='icons'>
							<div class='loader tiny'></div><div class='color_green'>".$vars['icons']->save."</div><div class='color_red'>".$vars['icons']->cancel."</div>
						</div>
					</form>";
	}

	/**
	 * Render 'Admin' Tile Page content.
	 * 
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 */
	public static function render_admin( $blog, $vars ) {

		// init rows
		$rows = array();

		// database
		$rows[] = array(
			'slug' => 'database',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Database", 'greyd_hub')."</p>".
					"<div>".
						"<small>".__("Database tables as .sql", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Export of the database.", 'greyd_hub')."</h3>
							<p>".__("This download contains all database tables of the site. It contains your posts, the name of the page, settings, permalinks, meta information and much more.", 'greyd_hub')."</p>
							<p>".__("However, no user or multisite information is exported.", 'greyd_hub')."</p>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_db_file' id='edit_db_file_{$blog['blog_id']}' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_db_export' id='edit_db_export_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_db_import' id='edit_db_import_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_db_import_clear' id='edit_db_import_clear_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_db_id' id='edit_db_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
						<input type='hidden' name='edit_db_domain' id='edit_db_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_db_{$blog['blog_id']}' value='Edit DB'>
						
					</form>
					<span id='btn_export_db' class='button small' data-event='button' data-args='advanced' title='"._x("Download SQL file", "small", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_db' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Upload SQL file", "small", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
					<!-- <span id='btn_select_db' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
				</div>",
			'priority' => 1
		);

		// media
		$rows[] = array(
			'slug' => 'media',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Media & files", 'greyd_hub')."</p>".
					"<div>".
						"<small>".__("Media as .zip", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Included in the download:", 'greyd_hub')."</h3>
							<ul>
								<li>".__("WordPress media uploads (images, PDFs, animations that are loaded into the library)", 'greyd_hub')."</li>
								<li>".__("Custom fonts from the Customizer (located in the template files)", 'greyd_hub')."</li>
								<li>".__("Custom CSS (located in the VC folder)", 'greyd_hub')."</li>
							</ul>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_files_file' id='edit_files_file_{$blog['blog_id']}' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_files_export' id='edit_files_export_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_files_import' id='edit_files_import_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_files_import_clear' id='edit_files_import_clear_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_files_id' id='edit_files_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
						<input type='hidden' name='edit_files_domain' id='edit_files_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_files_{$blog['blog_id']}' value='Edit Files'>
						
					</form>
					<span id='btn_export_files' class='button small' data-event='button' data-args='advanced' title='"._x("Download ZIP archive", "small", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_files' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Upload ZIP archive", "small", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
					<!-- <span id='btn_select_files' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
				</div>",
			'priority' => 2
		);

		// themes
		$rows[] = array(
			'slug' => 'themes',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Themes", 'greyd_hub')."</p>".
					"<div>".
						"<small>"._x("Themes as .zip", "small", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Included in the download:", 'greyd_hub')."</h3>
							<ul>
								<li>"._x("The active theme.", "small", 'greyd_hub')."</li>
								<li>"._x("The parent theme, if the active theme is a child theme.", "small", 'greyd_hub')."</li>
							</ul>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_themes_file' id='edit_themes_file_{$blog['blog_id']}' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_themes_export' id='edit_themes_export_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_themes_import' id='edit_themes_import_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_themes_id' id='edit_themes_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
						<input type='hidden' name='edit_themes_domain' id='edit_themes_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_themes_{$blog['blog_id']}' value='Edit Themes'>
						
					</form>
					<span id='btn_export_themes' class='button small' data-event='button' data-args='advanced' title='"._x("Download ZIP archive", "small", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_themes' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Upload ZIP archive", "small", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
				</div>",
			'priority' => 3
		);

		// plugins
		$rows[] = array(
			'slug' => 'plugins',
			'content' =>
				"<div class='flex inner_head'>".
					"<p>".__("Plugins", 'greyd_hub')."</p>".
					"<div>".
						"<small>"._x("Plugins as .zip", "small", 'greyd_hub')."</small>".
						"&nbsp;".
						Helper::render_info_dialog("
							<h3>".__("Included in the download:", 'greyd_hub')."</h3>
							<ul>
								<li>"._x("All active plugins", "small", 'greyd_hub')."</li>
								<li>"._x("All network-wide activated plugins (applies only to multisites)", "small", 'greyd_hub')."</li>
								<li>"._x("The normal download does not include internal plugins like the Greyd Plugin, Greyd Forms, Gutenberg and VC. If you want to download these plugins use the 'Greyd Plugins' button.", "small", 'greyd_hub')."</li>
							</ul>
						").
					"</div>".
				"</div>".
				"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
					<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
						<input style='display:none' type='file' type='hidden' name='edit_plugins_file' id='edit_plugins_file_{$blog['blog_id']}' value='' maxlength='".wp_max_upload_size()."'>
						<input type='hidden' name='edit_plugins_export_internal' id='edit_plugins_export_internal_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_plugins_export' id='edit_plugins_export_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_plugins_import' id='edit_plugins_import_{$blog['blog_id']}' value=''>
						<input type='hidden' name='edit_plugins_id' id='edit_plugins_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
						<input type='hidden' name='edit_plugins_domain' id='edit_plugins_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
						<input style='display:none' type='submit' name='submit' id='submit_edit_plugins_{$blog['blog_id']}' value='Edit Plugins'>
						
					</form>
					<span id='btn_export_plugins' class='button small' data-event='button' data-args='advanced' title='"._x("Download ZIP archive", "small", 'greyd_hub')."'><span>".__("Download", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_export_plugins_internal' class='button small' data-event='button' data-args='advanced' title='"._x("Download ZIP archive", "small", 'greyd_hub')."'><span>".__("Greyd Plugins", 'greyd_hub')."</span>".$vars['icons']->download."</span>
					<span id='btn_import_plugins' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Upload ZIP archive", "small", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span>
					<!-- <span id='btn_select_plugins' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Select source", 'greyd_hub')."'><span>".__("Import", 'greyd_hub')."</span>".$vars['icons']->upload."</span> -->
				</div>",
			'priority' => 4
		);

		// only integrated
		if ( !Admin::$is_standalone ) {

			if ( $vars['is_greyd'] || $vars['is_greyd_theme'] ) {
				
				// clean downloads
				$rows[] = array(
					'slug' => 'plugins',
					'content' =>
						"<div class='flex inner_head'>".
							"<p>".__("Cleaned downloads", 'greyd_hub')."</p>".
							"<div>".
								Helper::render_info_dialog("
									<h3>".__("Cleaned downloads.", 'greyd_hub')."</h3>
									<p>".__("These downloads contain cleaned data and content.", 'greyd_hub')."</p>
									<p>".__("For example, historical data such as form entries, user roles or revisions are excluded. In addition, individual database tables are removed, some plugin settings such as WPML settings are removed and all media that do not also appear in the media library are ignored.", 'greyd_hub')."</p>
									<p>".__("These files are suitable for optical copies of existing websites or to create a \"blueprint\" for future installations. By the way: You can also create website templates for this very comfortably.", 'greyd_hub')."</p>
								").
							"</div>".
						"</div>".
						"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
							<form method='post' enctype='multipart/form-data' ".$vars['action'].">".$vars['nonce']."
								<input type='hidden' name='edit_site_clean' id='edit_site_clean_{$blog['blog_id']}' value=''>
								<input type='hidden' name='edit_content_clean' id='edit_content_clean_{$blog['blog_id']}' value=''>
								<input type='hidden' name='edit_db_clean' id='edit_db_clean_{$blog['blog_id']}' value=''>
								<input type='hidden' name='edit_clean_id' id='edit_clean_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
								<input type='hidden' name='edit_clean_domain' id='edit_clean_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_clean_{$blog['blog_id']}' value='Edit Clean'>
								
							</form>
							<span id='btn_clean_site' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Download cleaned site", "small", 'greyd_hub')."'>
								<span>".__("Entire website", 'greyd_hub')."</span>".$vars['icons']->list_down.$vars['icons']->list_full."
							</span>
							<span id='btn_clean_content' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Download cleaned content", "small", 'greyd_hub')."'>
								<span>".__("Content", 'greyd_hub')."</span>".$vars['icons']->list_down.$vars['icons']->list_content."
							</span>
							<span id='btn_clean_db' class='button small button-ghost' data-event='button' data-args='advanced' title='"._x("Download cleaned SQL file", "small", 'greyd_hub')."'>
								<span>".__("Database", 'greyd_hub')."</span>".$vars['icons']->list_down.$vars['icons']->list_db."
							</span>
						</div>",
					'priority' => 5
				);
				
			}

			if ( !$blog['is_remote'] && ( $vars['is_greyd'] || !$vars['is_greyd_theme'] )) {

				// Greyd
				$rows[] = array(
					'slug' => 'greyd',
					'content' =>
						"<div class='flex inner_head'>".
							"<p>".__("Greyd.Suite", 'greyd_hub')."</p>".( $vars['is_greyd'] ?
							"<div>".
								Helper::render_info_dialog("
									<h3>".__("Reset Greyd.Suite", 'greyd_hub')."</h3>
									<p>".__("Greyd.Suite will be reset to the default state. All content and designs will be deleted. Make a backup beforehand.", 'greyd_hub')."</p>
									<h3>".__("Repair:", 'greyd_hub')."</h3>
									<p>".__("The database tables will be searched for incorrectly serialized post meta information and errors will be corrected.", 'greyd_hub')."</p>
								", "bottom").
							"</div>" : "" ).
						"</div>".
						"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
							<form method='post'>".$vars['nonce']."
								<input type='hidden' name='edit_greyd_mode' id='edit_greyd_mode_{$blog['blog_id']}' value=''>
								<input type='hidden' name='edit_greyd_id' id='edit_greyd_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
								<input type='hidden' name='edit_greyd_domain' id='edit_greyd_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_greyd_{$blog['blog_id']}' value='Edit Greyd'>
								
							</form>".( $vars['is_greyd'] ?
								"<span id='btn_init_greyd' class='button small' data-event='button' data-args='admin' title='"._x("Reset Greyd.Suite", "small", 'greyd_hub')."'><span>"._x("Reset", "small", 'greyd_hub')."</span>".$vars['icons']->reset."</span>
								<span id='btn_check_greyd' class='button small button-ghost' data-event='button' data-args='admin' title='"._x("Repair database", "small", 'greyd_hub')."'><span>"._x("Repair", "small", 'greyd_hub')."</span>".$vars['icons']->db."</span>"
								:
								"<span id='btn_setup_greyd' class='button button-greyd small' data-event='button' data-args='admin' title='"._x("Activate Greyd.Suite", "small", 'greyd_hub')."'>".$vars['icons']->activate."<span>"._x("Activate", "small", 'greyd_hub')."</span></span>"
							)."
						</div>",
					'priority' => 6
				);
				
			}

		}

		if ( is_multisite() && !$blog['is_remote'] ) {

			// Website
			$rows[] = array(
				'slug' => 'website',
				'content' =>
					"<div class='flex inner_head'>".
						"<p>".__("Website", 'greyd_hub')."</p>".( !$vars['is_parent'] ?
						"<div>".
							Helper::render_info_dialog("
								<h3>".__("Reset WordPress", 'greyd_hub')."</h3>
								<p>".__("This action resets the installation to its original state. All content is deleted, plugins are deactivated and the standard theme is activated.", 'greyd_hub')."</p>
								<p>".__("Always make a backup beforehand.", 'greyd_hub')."</p>
							", "bottom").
						"</div>" : "" ).
					"</div>".( !$vars['is_parent'] ?
						"<div class='data-holder' data-id='{$blog['blog_id']}' data-domain='{$blog['domain']}'>
							<form method='post'>".$vars['nonce']."
								<input type='hidden' name='edit_website_mode' id='edit_website_mode_{$blog['blog_id']}' value=''>
								<input type='hidden' name='edit_website_id' id='edit_website_id_{$blog['blog_id']}' value='{$blog['blog_id']}'>
								<input type='hidden' name='edit_website_domain' id='edit_website_domain_{$blog['blog_id']}' value='{$blog['domain']}'>
								<input style='display:none' type='submit' name='submit' id='submit_edit_website_{$blog['blog_id']}' value='Edit Website'>
								
							</form>
							<span id='btn_reset_website' class='button small' data-event='button' data-args='admin' title='"._x("Reset WordPress", "small", 'greyd_hub')."'><span>"._x("Reset WordPress", "small", 'greyd_hub')."</span>".$vars['icons']->reset."</span>
							<span id='btn_delete_website' class='button small button-danger' data-event='button' data-args='admin' title='"._x("Delete site", "small", 'greyd_hub')."'><span>"._x("Delete site", "small", 'greyd_hub')."</span>".$vars['icons']->delete."</span>
						</div>"
						:
						"<p class='list_view__hide'><i>".__("This is the parent page of your multisite. Deleting or resetting it can lead to irreparable errors.", 'greyd_hub')."</i></p>"
					),
				'priority' => 7
			);

		}

		/**
		 * Greyd.Hub website tile page 'Admin'
		 * 
		 * @filter greyd_hub_tile_page_admin
		 * 
		 * @param array  $rows          All rows as array.
		 *      @property string slug       Slug of the row.
		 *      @property string class      CSS class name.
		 *      @property string content    Row content.
		 *      @property int priority      position of the row (optional, 0: first, 99: last, defaults to 10)
		 * @param array $blog			Blog details.
		 * @param array $vars			Blog vars used in tile.
		 */
		$rows = apply_filters( "greyd_hub_tile_page_admin", $rows, $blog, $vars );

		// sort rows by priority
		usort($rows, function($a, $b) {
			$prio_a = isset($a['priority']) ? $a['priority'] : 10;
			$prio_b = isset($b['priority']) ? $b['priority'] : 10;
			return $prio_a - $prio_b;
		});

		// render rows
		$content = "";
		foreach( $rows as $row ) {
			// echo self::make_row( $row );
			$content .= self::make_row( $row );
		}
		return $content;

	}

	/**
	 * Render a single row of actions inside Tile Page.
	 * 
	 * @param array  $row           Row object.
	 *      @property string slug       Slug of the row.
	 *      @property string class      CSS class name.
	 *      @property string content    Row content.
	 * @return string 				Rendered row markup.
	 */
	public static function make_row( $row ) {

		$row = wp_parse_args( $row, array(
			"slug"      => "",
			"class"     => "",
			"content"   => ""
		) );
		if (empty($row['content'])) return "";
		$classes = array( 'tab_row' );
		if (!empty($row['class'])) $classes[] = $row['class'];

		return "<div class='".implode(' ', $classes)."' data-row='".$row['slug']."'>".$row['content']."</div>";

	}

	public function get_skeleton_section( $count = 1 ) {
		$html = '<section style="margin-top:85px"><div class="greyd_tile_wrapper">';
		for ($i=0; $i < $count; $i++) { 
			$html .= $this->get_skeleton_tile();
		}
		$html .= '</div></section>';
		return $html;
	}

	public function get_skeleton_tile() {
		return '<div class="greyd_tile">
			<div class="hub_domain_and_title">
				<div class="hub_domain">
					<a><span class="skeleton--placeholder" style="width:120px"></span></a>
				</div>
				<hr>
				<div class="hub_title">
					<div class="change_bloginfo">
						<h4 class="preview"><span class="skeleton--placeholder" style="width:150px"></span></h4>
					</div>
					<div class="change_bloginfo">
						<h5 class="preview"><span class="skeleton--placeholder" style="width:100%"></span></h4>
					</div>
				</div>
			</div>
			<hr>
			<div></div>
			<div class="hub_links">
				<a><span class="skeleton--placeholder" style="width:80px"><span class="dashicons dashicons-external"></span></span></a>
				<a><span class="skeleton--placeholder" style="width:80px"><span class="dashicons dashicons-external"></span></span></a>
				<a><span class="skeleton--placeholder" style="width:80px"><span class="dashicons dashicons-external"></span></span></a>
			</div>
			<div class="inner" style="margin-top:64px"></div>

		</div>';
	}
}