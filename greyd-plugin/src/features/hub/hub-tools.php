<?php
/**
 * Greyd.Hub main utility functions.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

class Tools {

	/*
	=======================================================================
		Websites page - add new site
	=======================================================================
	*/

	/**
	 * Create new Blog.
	 * 
	 * @param object $args
	 * 		@property string domain		Domain of the new blog.
	 * 		@property string title		Title of the new blog.
	 * 		@property array options		Options that should be applied.
	 * 			@property string public|archived|spam|deleted|mature	Site Options (part of WP_Site object) "0"|"1"
	 * 			@property string description|WPLANG|{any-option-name}	Blog Options (from options table)
	 * 		@property int user_id		Admin User of the new blog (default: current user).
	 * 		@property bool send_emails	Send email notifications to admin (default: true).
	 * 		@property bool log			Trigger logs (default: true).
	 * 		@property bool init_log		Open and close logging overlay (default: true).
	 * 
	 * @return int|bool|WP_Error $id	Blog ID of the new Blog.
	 * 									False on user cap error or invalid input.
	 * 									WP Error object on any other error.
	 */
	public static function create_blog( $args ) {

		global $wpdb;

		// check cap
		if ( !current_user_can( 'create_sites' ) ) {
			if ($args['log']) Log::log_abort(__( "Sorry, you are not allowed to add websites to this network.", 'greyd_hub' ), "create");
			return false;
		}

		// get all arguments
		$args = wp_parse_args( $args, array(
			'domain' => '',
			'title' => '',
			'options' => array(),
			'user_id' => get_current_user_id(),
			'send_emails' => true,
			'log' => true,
			'init_log' => true,
		) );
		if ($args['log'] == false) $args['init_log'] = false;

		// get vars
		$user_id = $args["user_id"];
		$title = $args["title"];
		$domain = '';
		$args['domain'] = trim( $args['domain'] );
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $args['domain'] ) ) {
			$domain = strtolower( $args['domain'] );
		}

		// check input
		if (empty($domain) || empty($title)) {
			if ($args['log']) Log::log_abort(__( "Missing or invalid website URL.", 'greyd_hub'), "create");
			return false;
		}

		// check domain - make sure it isn't a reserved word.
		if ( !is_subdomain_install() ) {
			$subdirectory_reserved_names = get_subdirectory_reserved_names();
			if ( in_array( $domain, $subdirectory_reserved_names, true ) ) {
				if ($args['log']) Log::log_abort(__( "The following terms are reserved for use by WordPress functions and cannot be used as website names:", 'greyd_hub').'<br><code>'.implode( '</code>, <code>', $subdirectory_reserved_names ).'</code>', "create");
				return false;
			}
		}

		// make domain and path
		if ( is_subdomain_install() ) {
			$newdomain = $domain.'.'.preg_replace( '|^www\.|', '', get_network()->domain );
			$path      = get_network()->path;
		} 
		else {
			$newdomain = get_network()->domain;
			$path      = get_network()->path.$domain.'/';
		}
	
		// render logging overlay
		if ($args['log']) {
			if ($args['init_log']) Log::log_start("create loading");
			Log::log_message("<h5>".sprintf(__("New website \"%s\" is created on domain \"%s\".", 'greyd_hub'), $args["title"], $newdomain.$path)."</h5>", 'info');
		}
		
		// create blog
		$wpdb->hide_errors();
		$id = wpmu_create_blog( $newdomain, $path, $title, $user_id, $args["options"], get_current_network_id() );
		$wpdb->show_errors();

		// check result
		$success = false;
		if ( !is_wp_error( $id ) ) {
			// set user option
			if ( !is_super_admin( $user_id ) && !get_user_option( 'primary_blog', $user_id ) ) {
				update_user_option( $user_id, 'primary_blog', $id, true );
			}
			// send email notifications
			if ($args['send_emails']) {
				wpmu_new_site_admin_notification( $id, $user_id );
				wpmu_welcome_notification( $id, $user_id, 'N/A', $title, array(  ) );
			}
			// success log
			if ($args['log']) {
				// links
				$blog_url = get_home_url( absint( $id ) );
				$blog_link = sprintf( '<a href="%s" target="_blank" title="'.__("Open in new tab", 'greyd_hub').'">%s</a>', $blog_url, $blog_url );
				$dashboard_url = get_admin_url( absint( $id ) );
				$dashboard_link = sprintf( '<a href="%s" target="_blank">%s</a>', $dashboard_url, __("Dashboard", 'greyd_hub') );
				$setup_url = network_admin_url("site-info.php?id=".$id);
				$setup_link = sprintf( '<a href="%s" target="_blank">%s</a>', $setup_url, __("Setup", 'greyd_hub') );
				Log::log_message(
					"<h5>".__("The website was created successfully.", 'greyd_hub')."</h5>".
					"<h3>".$args["title"]."</h3>".
					(isset($args["options"]["blogdescription"]) ? "<p>".$args["options"]["blogdescription"]."</p>" : "").
					"<p><b>".$blog_link."</b></p>".
					"<span>".$dashboard_link."&nbsp;&nbsp;".$setup_link."</span>", 
					'success'
				);
			}
			// blog created
			$success = true;
		} 
		else {
			// fail log
			if ($args['log']) {
				Log::log_message($id->get_error_message(), '');
				Log::log_message("<h5>".__("The website could not be created.", 'greyd_hub')."</h5>", 'danger');
			}
		}

		// close overlay
		if ($args['log'] && $args['init_log']) Log::log_end("create ".(!$success ? "fail" : "success"));

		return $id;
	}


	/*
	=======================================================================
		Websites page - export
			downloadSite -> download_blog -> download_blog_content/download_blog_full
			sqlExport -> download_blog -> download_blog_db (split fom old function)
			downloadFiles -> download_blog -> download_blog_media
			downloadPlugins -> download_blog -> download_blog_plugins
			export_themes -> download_blog -> download_blog_themes
	=======================================================================
	*/

	/**
	 * Download Blog.
	 * Main Export function.
	 * possible modes: 'db', 'files', 'plugins', 'themes', 'content', 'site'
	 * 
	 * @param object $args
	 * 		@property string mode	Export Mode.
	 * 		@property int blogid	Blog ID of the selected blog.
	 * 		@property string domain	Domain of the selected blog.
	 * 		@property bool trigger	Trigger the download (default: true).
	 * 		@property bool clean	Use cleaned-up content (default: false).
	 * 		@property bool unique	Make the filename unique (uniqid after date) (default: false).
	 * 		@property bool internal	Download internal plugins - only used in 'plugins' mode (default: false).
	 * 		@property bool log		Trigger logs (default: true).
	 * 		@property bool init_log	Open and close logging overlay (default: true).
	 * 
	 * @return string|bool $file_name	Name of the new file placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog( $args ) {

		// get all arguments
		$args = wp_parse_args( $args, array(
			'mode' => '',
			'blogid' => -1,
			'domain' => '',
			'trigger' => true,
			'clean' => false,
			'unique' => false,
			'internal' => false,
			'log' => true,
			'init_log' => true,
		) );
		if ($args['log'] == false) $args['init_log'] = false;

		// check input
		$modes = array('files', 'db', 'plugins', 'themes', 'content', 'site');
		if ( 
			// wrong mode
			empty($args['mode']) || !in_array($args['mode'], $modes) ||
			// wrong blogid
			empty($args['blogid']) || $args['blogid'] < 1
		) {
			return false;
		}

		/**
		 * Adjust script memory limit (default: 2048M)
		 * @filter 'greyd_hub_process_set_memory_limit'
		 */
		ini_set('memory_limit', apply_filters( 'greyd_hub_process_set_memory_limit', '2048M' ));
		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		set_time_limit(apply_filters( 'greyd_hub_process_set_timeout', 3600 ));

		// render logging overlay
		if ($args['log']) {
			if ($args['init_log']) Log::log_start("export loading");
			Log::log_message("<h5>".sprintf(__("Starting export of '%s'.", 'greyd_hub'), $args['domain'])."</h5>", 'info');
		}
		
		// export
		switch ($args['mode']) {
			case 'db':
				$file_name = self::download_blog_db($args['blogid'], $args['domain'], $args['trigger'], $args['clean'], $args['log']);
				break;
			case 'files':
				$file_name = self::download_blog_media($args['blogid'], $args['domain'], $args['trigger'], $args['clean'], $args['log']);
				break;
			case 'plugins':
				$file_name = self::download_blog_plugins($args['blogid'], $args['domain'], $args['trigger'], $args['internal'], $args['log']);
				break;
			case 'themes':
				$file_name = self::download_blog_themes($args['blogid'], $args['domain'], $args['trigger'], $args['log']);
				break;
			case 'content':
				$file_name = self::download_blog_content($args['blogid'], $args['domain'], $args['trigger'], $args['clean'], $args['unique'], $args['log']);
				break;
			case 'site':
				$file_name = self::download_blog_full($args['blogid'], $args['domain'], $args['trigger'], $args['clean'], $args['unique'], $args['log']);
				break;
		}
		
		// close overlay
		if ($args['log'] && $args['init_log']) Log::log_end("export ".(!$file_name ? "fail" : "success"));

		// return
		if ( $file_name && !empty($file_name) ) {
			if ( !$args['trigger'] ) return $file_name;
			return true;
		}
		return false;
	}

	/**
	 * Download Blog database.
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $clean         Use cleaned-up content.
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $sql_name	Name of the new SQL file placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_db( $blogid, $domain, $trigger, $clean, $log ) {

		global $wpdb;

		if ($log) Log::log_message("<h5>".sprintf(__("Export of the database on the domain \"%s\" starts.", 'greyd_hub'), $domain)."</h5>", 'info');
		
		// make name and make replace-array
		$original_domain = $domain;
		$domain = Hub_Helper::printable_domainname( $domain );
		$replace = array( 'old' => array(), 'new' => array() );
		if ($clean) {
			$domain = Admin::$clean->domain;
			$replace = array(
				'old' => array( 
					'id' => $blogid,
					'domain' => $original_domain
				),
				'new' => array( 
					'prefix' => Admin::$clean->prefix, 
					'id' => 1,
					'http' => 'https', 
					'domain' => Admin::$clean->domain
				),
			);
		}
		$sql_name = $domain."_database_".date("ymd").".sql";

		// get urls
		if (is_multisite()) switch_to_blog($blogid);
			$wpurl = get_bloginfo('wpurl');
			$siteurl = get_bloginfo('url');
			if (strpos($siteurl, '?') > 0) 
				$siteurl = trim(explode('?', $siteurl)[0], '/');
		if (is_multisite()) restore_current_blog();
		
		// normalize uploads url
		// debug(wp_upload_dir()["baseurl"]);
		// debug($siteurl."/wp-content/uploads");
		$replace['old']['upload_url'] = wp_upload_dir()["baseurl"];
		$replace['new']['upload_url'] = $siteurl."/wp-content/uploads";

		// normalize wp url
		if ($wpurl != $siteurl) {
			$replace['old']['wp_url'] = $wpurl.'/';
			$replace['new']['wp_url'] = $siteurl.'/';
		}

		// get all blogid tables
		$tables = array();
		$prefix = ($blogid == 1) ? $wpdb->base_prefix : $wpdb->base_prefix.$blogid.'_';
		$alltables = $wpdb->get_results("SHOW TABLES FROM `".$wpdb->dbname."`");
		foreach ($alltables as $table) {
			$tbl = $table->{'Tables_in_'.$wpdb->dbname};
			if (preg_match('/^('.$prefix.')\D+/', $tbl)) {
				if ($tbl != $wpdb->base_prefix."blogs" &&
					$tbl != $wpdb->base_prefix."blog_versions" &&
					$tbl != $wpdb->base_prefix."blogmeta" &&
					$tbl != $wpdb->base_prefix."registration_log" &&
					$tbl != $wpdb->base_prefix."signups" &&
					$tbl != $wpdb->base_prefix."site" &&
					$tbl != $wpdb->base_prefix."sitemeta" &&
					$tbl != $wpdb->base_prefix."sitecategories" &&
					$tbl != $wpdb->base_prefix."users" &&
					$tbl != $wpdb->base_prefix."usermeta") {
						$tables[] = $tbl;
				}
			}
		}
		// debug($tables);

		if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $sql_name), 'info', true);

		// make download
		if (Hub_Helper::make_sql($sql_name, $tables, $replace, $clean, $log)) {
			
			if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $sql_name)."</h5>", 'success', true);

			// download file
			if ( !$trigger ) return $sql_name;
			Hub_Helper::start_download($sql_name, $domain, 'export_db');
			return true;
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_db',
			"replace"   => $domain
		);
		return false;
	}

	/**
	 * Download all media files inside the uploads folder.
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $clean         Use cleaned-up files.
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $zip_name	Name of the new ZIP archive placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_media( $blogid, $domain, $trigger, $clean, $log ) {
		
		if ($log) Log::log_message("<h5>".sprintf(__("Export of media of the domain \"%s\" is started.", 'greyd_hub'), $domain)."</h5>", 'info');
		
		// make name and prep folders
		$domain = Hub_Helper::printable_domainname( $domain );
		$zip_name = $domain."_uploads_".date("ymd").".zip";

		// get uploads folder
		$folder = Admin::$urls->uploads_folder; // primary uploads
		$exclude = Admin::$urls->uploads_folder."/sites";
		$include = null;
		if ($blogid > 1) {
			$folder .= "/sites/".$blogid; // site uploads
			$exclude = "";
		}

		// clean files
		if ( $clean ) {
			// check attachments and custom font files
			// only include those in the download
			if (is_multisite()) switch_to_blog($blogid);
				$baseurl    = trailingslashit(wp_upload_dir()['baseurl']);
				$basedir    = trailingslashit(wp_upload_dir()['basedir']);
				// get list of attachment files
				$attachments = get_posts( array(
					'post_type' => 'attachment',
					'numberposts' => -1,
					'post_status' => null,
					'post_parent' => null,
					'fields' => 'ids'
				) );
				if ($attachments) {
					// get all sizes
					$sizes = array_merge( array( 'full' ), get_intermediate_image_sizes() );
					foreach ($attachments as $post_id) {
						$files = array( wp_get_attachment_url( $post_id ) );
						foreach( $sizes as $size ) {
							$data = wp_get_attachment_image_src( $post_id, $size );
							if ( $data && isset($data[0]) ) {
								$files[] = $data[0];
							}
						}
						$files = array_unique($files);
						foreach ($files as $file) {
							$include[] = explode($baseurl, $file )[1];
						}
					}
				}
				// get list of custom font files
				$active_font_families = array_unique( array(
					get_theme_mod( 'fontFamily1', null ),
					get_theme_mod( 'fontFamily2', null ),
					get_theme_mod( 'fontFamily3', null )
				) );
				$index_file = $basedir."greyd_tp/custom_fonts/custom_fonts_index.json";
				if ( !empty($active_font_families) && file_exists($index_file) ) {
					$contents = json_decode( Helper::get_file_contents( $index_file ), true );
					if ( $contents && is_array($contents) && isset($contents['fonts']) ) {
						$custom_fonts_in_use = true;
						foreach( $contents['fonts'] as $font ) {
							$font_name = isset($font['name']) ? esc_attr($font['name']) : null;
							$font_folder = isset($font['file']) ? explode("/", $font['file'])[0] : null;
							if ( $font_folder && $font_name && in_array($font_name, $active_font_families) ) {
								$font_dir = $basedir."greyd_tp/custom_fonts/".$font_folder;
								$font_files = Hub_Helper::list_files_in_dir(array(), $font_dir);
								if ( !empty($font_files) ) {
									foreach( $font_files as $font_file ) {
										$include[] = explode( $basedir, $font_file )[1];
									}
									$custom_fonts_in_use = true;
								}
							}
						}
						if ( $custom_fonts_in_use ) {
							$include[] = explode( $basedir, $index_file )[1];
						}
					}
				}
			if (is_multisite()) restore_current_blog();
		}


		// get wp_fonts
		if (is_multisite()) switch_to_blog($blogid);

			if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver_Gutenberg::get_user_global_styles_post_id();
			} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
			}
			if ( $styles_id ) {
				// get global-styles post content
				$content = get_post_field( 'post_content', $styles_id );
				$data    = json_decode( $content );
				if ( $data && json_last_error() == 0 ) {
					// search wp fonts
					$fonts = array();
					if ( isset($data->settings->typography->fontFamilies->custom) ) {
						$network_url = trailingslashit(network_site_url());
						$site_url = trailingslashit(get_site_url());
						foreach ( $data->settings->typography->fontFamilies->custom as $i => $font ) {
							// debug($font);
							foreach ( $font->fontFace as $j => $fontFace ) {
								// debug($fontFace->src);
								$src = $fontFace->src;
								if ( strpos( $src, $site_url ) === false && strpos( $src, $network_url ) === 0 ) {
									// sometimes the url is just the network url
									// in that case it is adjusted to find the correct absolut path for zipping
									$src = str_replace( $network_url, $site_url, $src );
								}
								$src = Helper::url_to_abspath( $src );
								$fileinfo = pathinfo($src);
								// zip font file
								array_push( $fonts, array(
									"real" => $src,
									"relative" => "wp_fonts/".$fileinfo["basename"]
								) );
							}
						}
					}
					// if ($log) Log::log_message(__("WP Fonts gespeichert.", 'greyd_hub'), 'info', true);
				}
			}

		if (is_multisite()) restore_current_blog();

		if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info', true);

		// make download
		if ( Hub_Helper::make_zip($zip_name, $folder, $exclude, $include) ) {
			
			// add wp_fonts to zip
			if ( isset($fonts) && count($fonts) > 0 ) {
				$zip_path_fonts = Admin::$urls->backup_path.$zip_name;
				Hub_Helper::zip_files( $zip_path_fonts, $fonts, false );
			}

			if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success', true);

			// download file
			if ( !$trigger ) return $zip_name;
			Hub_Helper::start_download($zip_name, $domain, 'export_files');
			return true;
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_files',
			"replace"   => $domain
		);
		return false;
	}

	/**
	 * Download all active and network active plugins.
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $internal      Download only internal plugins.
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $zip_name	Name of the new ZIP archive placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_plugins( $blogid, $domain, $trigger, $internal, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Export of the plugins of the domain \"%s\" starts.", 'greyd_hub'), $domain)."</h5>", 'info');
		
		// make name
		$domain = Hub_Helper::printable_domainname( $domain );
		$zip_name = $domain."_plugins_".date("ymd").".zip";
		if ( $internal ) $zip_name = $domain."_plugins_internal_".date("ymd").".zip";

		// get plugins folder
		$folder = Admin::$urls->plugins_path;
		$exclude = array();

		// get active plugins
		if (is_multisite()) switch_to_blog( $blogid );
			$active_plugins = Helper::active_plugins();
		if (is_multisite()) restore_current_blog();
		foreach ( $active_plugins as $i => $plugin ) {
			if ( strpos( $plugin, '/' ) !== false ) {
				list( $slug, $init ) = explode( '/', $plugin, 2 );
				$active_plugins[$i] = $slug;
			}
		}

		// dont't download internal plugins
		$internal_plugins = array( 
			'greyd_hub', 'greyd_tp_management', 'greyd_tp_forms', 'greyd-plugin',
			'js_composer', 'gutenberg', 'greyd_blocks'
		);
		// get all plugins
		$all_plugins = scandir(realpath($folder));
		unset( $all_plugins[array_search('.', $all_plugins, true)] );
		unset( $all_plugins[array_search('..', $all_plugins, true)] );
		// and set excluded folders
		foreach ( $all_plugins as $slug ) {
			$path = $folder.$slug;
			if (
				// exclude non directories
				!is_dir( $path ) ||
				// exclude/include all internal plugins
				( !$internal && in_array( $slug, $internal_plugins ) ) ||
				( $internal && !in_array( $slug, $internal_plugins ) ) ||
				// exclude all non-active plugins
				!in_array( $slug, $active_plugins )
			) {
				$exclude[] = $path;
			}
		}

		if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info', true);

		// make download
		if (Hub_Helper::make_zip($zip_name, $folder, $exclude)) {
			
			if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success', true);

			// download file
			if ( !$trigger ) return $zip_name;
			Hub_Helper::start_download($zip_name, $domain, 'export_plugins');
			return true;
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_plugins',
			"replace"   => $domain
		);
		return false;
	}

	/**
	 * Download active parent- & child-theme.
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $zip_name	Name of the new ZIP archive placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_themes( $blogid, $domain, $trigger, $log ) {
		
		if ($log) Log::log_message("<h5>".sprintf(__("Export of themes of the domain \"%s\" starts.", 'greyd_hub'), $domain)."</h5>", 'info');
		
		// make name
		$domain = Hub_Helper::printable_domainname( $domain );
		$zip_name = $domain."_themes_".date("ymd").".zip";

		// get themes folder
		$folder = Admin::$urls->themes_path;
		$exclude = array();

		// get active themes
		if (is_multisite()) switch_to_blog( $blogid );
			$theme = wp_get_theme();
			$active_themes = array(
				$theme->stylesheet
			);
			if ($theme->parent()) {
				$active_themes[] = $theme->parent()->stylesheet;
			}
		if (is_multisite()) restore_current_blog();

		// get all themes
		$all_themes = scandir(realpath($folder));
		unset( $all_themes[array_search('.', $all_themes, true)] );
		unset( $all_themes[array_search('..', $all_themes, true)] );
		// and set excluded folders
		foreach ( $all_themes as $slug ) {
			$path = $folder.$slug;
			if (
				// exclude non directories
				!is_dir( $path ) ||
				// exclude all non-active themes
				!in_array( $slug, $active_themes )
			) {
				$exclude[] = $path;
			}
		}

		if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info', true);

		// make download
		if (Hub_Helper::make_zip($zip_name, $folder, $exclude)) {
			
			if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success', true);

			// download file
			if ( !$trigger ) return $zip_name;
			Hub_Helper::start_download($zip_name, $domain, 'export_themes');
			return true;
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_themes',
			"replace"   => $domain
		);
		return false;
	}

	/**
	 * Download Blog content.
	 * Includes of database and media files.
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $clean         Use cleaned-up files.
	 * @param bool   $unique        Make the filename unique (uniqid after date).
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $zip_name	Name of the new ZIP archive placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_content( $blogid, $domain, $trigger, $clean, $unique, $log ) {

		// make name
		$original_domain = $domain;
		$domain = Hub_Helper::printable_domainname( $domain );
		$zip_id = $unique ? uniqid(date("ymd")."--") : date("ymd");
		$zip_name = $domain."_content_".$zip_id.".zip";

		// get database
		$database = self::download_blog_db($blogid, $original_domain, false, $clean, $log);
		// make clean domain (after db dump)
		if ($clean) $domain = Admin::$clean->domain;
		// get media files
		$uploads = self::download_blog_media($blogid, $domain, false, $clean, $log);

		// check if files are created
		if ($database && $uploads) {

			if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info');

			// zip
			$zip_path = Admin::$urls->backup_path.$zip_name;
			if (file_exists($zip_path)) {
				unlink($zip_path);
			}
			$created = Hub_Helper::zip_files( $zip_path, array( 
				array(
					"real" => Admin::$urls->backup_path.$uploads,
					"relative" => $uploads
				),
				array(
					"real" => Admin::$urls->backup_path.$database,
					"relative" => $database
				)
			) );
			unlink(Admin::$urls->backup_path.$uploads);
			unlink(Admin::$urls->backup_path.$database);

			if ($log) Log::log_message(__("Temporary files deleted.", 'greyd_hub'), '');

			if ($created) {
				
				if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success');

				// make download
				if ( !$trigger ) return $zip_name;
				Hub_Helper::start_download($zip_name, $domain, 'export_content');
				return true;
			}
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_content',
			"replace"   => $domain
		);
		return false;
	}

	/**
	 * Download full Blog.
	 * Includes of database, media files, plugins and theme(s).
	 * 
	 * @param int    $blogid        Blog ID of the selected blog.
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $clean         Use cleaned-up files.
	 * @param bool   $unique        Make the filename unique (uniqid after date).
	 * @param bool   $log           Trigger logs.
	 * 
	 * @return string|bool $zip_name	Name of the new ZIP archive placed inside the backup folder.
	 * 									True if trigger is false.
	 * 									False if file can't be created.
	 */
	public static function download_blog_full( $blogid, $domain, $trigger, $clean, $unique, $log ) {

		// make name
		$original_domain = $domain;
		$domain = Hub_Helper::printable_domainname( $domain );
		$zip_id = $unique ? uniqid(date("ymd")."--") : date("ymd");
		$zip_name = $domain."_fullsite_".$zip_id.".zip";

		// get database
		$database = self::download_blog_db($blogid, $original_domain, false, $clean, $log);
		// make clean domain (after db dump)
		if ($clean) $domain = Admin::$clean->domain;
		// get media files
		$uploads = self::download_blog_media($blogid, $domain, false, $clean, $log);

		// get plugins
		$plugins = self::download_blog_plugins($blogid, $domain, false, false, $log);
		if ( ! $plugins && $log ) {
			Log::log_message("<h5>".__("Plugins could not be exported. If the page does not contain any active third-party plugins, this message can be ignored.", 'greyd_hub')."</h5>", 'danger');
		}

		// get themes
		$themes = self::download_blog_themes($blogid, $domain, false, $log);

		// check if files are created
		if ($database && $uploads && $themes) {

			if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info');

			// zip
			$zip_path = Admin::$urls->backup_path.$zip_name;
			if (file_exists($zip_path)) {
				unlink($zip_path);
			}

			$all_files = array(
				array(
					"real" => Admin::$urls->backup_path.$uploads,
					"relative" => $uploads
				),
				array(
					"real" => Admin::$urls->backup_path.$themes,
					"relative" => $themes
				),
				array(
					"real" => Admin::$urls->backup_path.$database,
					"relative" => $database
				)
			);
			if ( $plugins ) {
				$all_files[] = array(
					"real" => Admin::$urls->backup_path.$plugins,
					"relative" => $plugins
				);
			}

			$created = Hub_Helper::zip_files( $zip_path, $all_files );
			unlink(Admin::$urls->backup_path.$uploads);
			unlink(Admin::$urls->backup_path.$database);
			if ( $plugins ) unlink(Admin::$urls->backup_path.$plugins);
			unlink(Admin::$urls->backup_path.$themes);

			if ($log) Log::log_message(__("Temporary files deleted.", 'greyd_hub'), '', true);

			if ($created) {

				if ($log) Log::log_message("<h5>".sprintf(__("The download was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success', true);

				// make download
				if ( !$trigger ) return $zip_name;
				Hub_Helper::start_download($zip_name, $domain, 'export_site');
				return true;
			}
		}

		// fail
		if ( $trigger ) Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_site',
			"replace"   => $domain
		);
		return false;
	}


	/*
	=======================================================================
		Websites page - import
			importFile -> import_blog
			importFileSQL -> import_blog (split fom old function)
			getFile -> import_blog
			readFile -> import_blog -> import_blog_** (indivdual callbacks)
			readFileSQL -> import_blog -> import_blog_db -> SQL::mod_queries
	=======================================================================
	*/

	/**
	 * Import Blog.
	 * Main Import function.
	 * possible modes: 'db', 'files', 'plugins', 'themes', 'content', 'site', 'template
	 * 
	 * @param object $args
	 * 		@property string mode	Export Mode.
	 * 		@property int blogid	Blog ID of the selected blog.
	 * 		@property string domain	Domain of the selected blog.
	 * 		@property string input	Name of the file input holding the selected file. (for file uploads)
	 * 		@property string source	Source of the file. e.g. "greydsuite.de|1" or abs url. (for file from other location)
	 * 		@property bool clean	Use cleaned-up dump. (only for dumping file on other blog)
	 * 		@property bool log		Trigger logs (default: true).
	 * 		@property bool init_log	Open and close logging overlay (default: true).
	 * 
	 * @return bool
	 */
	public static function import_blog( $args ) {

		// get all arguments
		$args = wp_parse_args( $args, array(
			'mode' => '',
			'blogid' => -1,
			'domain' => '',
			'input' => '',
			'source' => '',
			'clean' => true,
			'log' => true,
			'init_log' => true,
		) );
		if ($args['log'] == false) $args['init_log'] = false;

		// return if no file or wrong mode
		$modes = array('files', 'db', 'plugins', 'themes', 'content', 'site', 'template');
		if ( 
			// no input
			(empty($args['input']) && empty($args['source'])) ||
			// wrong mode
			empty($args['mode']) || !in_array($args['mode'], $modes) ||
			// wrong blogid
			empty($args['blogid']) || $args['blogid'] < 1
		) {
			return false;
		}

		/**
		 * Adjust script memory limit (default: 2048M)
		 * @filter 'greyd_hub_process_set_memory_limit'
		 */
		ini_set('memory_limit', apply_filters( 'greyd_hub_process_set_memory_limit', '2048M' ));
		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		set_time_limit(apply_filters( 'greyd_hub_process_set_timeout', 3600 ));

		// render logging overlay
		if ($args['log']) {
			if ($args['init_log']) Log::log_start("import loading");
			Log::log_message("<h5>".sprintf(__("Starting import to '%s'.", 'greyd_hub'), $args['domain'])."</h5>", 'info');
		}
		
		// get input file
		if (!empty($args['input'])) {
			// Get file from $_FILES object
			if ($args['mode'] == 'db') {
				$input_file = Hub_Helper::get_uploaded_file($args['input'], 'sql');
			}
			else {
				$input_file = Hub_Helper::get_uploaded_file($args['input'], 'zip');
			}
		}
		if (!empty($args['source'])) {
			// get file from other location
			$input_file = Hub_Helper::get_temp_file($args['source'], $args['mode'], $args['clean'], $args['log']);
		}

		// check input file
		if ( !$input_file || empty($input_file) ) return false;
		if ( $args['mode'] != 'db' ) {
			// check if input is valid zip
			$zip = new \ZipArchive;
			if ($zip->open($input_file['tmp_name']) === false) {
				if ($args['log']) Log::log_abort(__("Please select a valid ZIP archive.", 'greyd_hub'));
				return false;
			}
			$zip->close();
		}

		// import
		switch ($args['mode']) {
			case 'db':
				$result = self::import_blog_db($input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'files':
				$result = self::import_blog_media($input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'plugins':
				$result = self::import_blog_plugins($input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'themes':
				$result = self::import_blog_themes($input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'content':
				$result = self::import_blog_content('content', $input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'site':
				$result = self::import_blog_content('site', $input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
			case 'template':
				$result = self::import_blog_content('template', $input_file, $args['blogid'], $args['domain'], $args['log']);
				break;
		}
		
		// cleanup
		if ( isset($input_file['delete_tmp']) ) {
			// delete temp path
			Hub_Helper::delete_directory($input_file['delete_tmp']);

			if ($args['log']) Log::log_message(__("Temporary files deleted.", 'greyd_hub'), '');

		}

		// close overlay
		if ($args['log'] && $args['init_log']) Log::log_end("import ".(!$result ? "fail" : "success"));

		return $result;
	}

	/**
	 * Import Blog database.
	 * 
	 * @param array  $file		The input file.
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 * @param int    $blogid	Blog ID of the selected blog.
	 * @param string $domain	Domain of the selected blog.
	 * @param bool   $log		Trigger logs.
	 * 
	 * @return bool
	 */
	public static function import_blog_db( $file, $blogid, $domain, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Import of the database on the domain \"%s\" starts. (File: %s)", 'greyd_hub'), $domain, $file['name'])."</h5>", 'info');
		
		// open file
		$queries = array(
			'error' => false,
			'queries' => SQL::parse_file($file['tmp_name'], $log),
			'new_data' => array(),
			'plugins_missing' => array()
		);
		// debug($queries);
		if (!$queries['queries']) {
			Log::log_abort( __("Importing the database was aborted, the file could not be read.", 'greyd_hub') );
			return false;
		}

		// make final queries
		SQL::mod_queries($queries, $blogid, $log);
		if ($queries['error']) {
			Log::log_abort( $queries['error'] );
			return false;
		}

		// import final queries
		$result = SQL::import_queries($queries['queries'], $log);

		// check for errors
		$db_errors = Hub_Helper::check_db_errors($queries['new_data']);
		if ($db_errors != "" && $log) Log::log_message($db_errors, 'success');
		if ($log) Log::log_message(__("DB checked.", 'greyd_hub'), 'debug');

		// Fix global content errors
		if ( class_exists("\Greyd\Global_Contents\GC_Helper") ) {
			// check and fix global posts with errors
			// errors can occur after import of db files.
			$errors = \Greyd\Global_Contents\GC_Helper::get_blog_global_posts_with_errors( $blogid, true );
			if ( $errors ) {
				Log::log_message("<h5>".count($errors)." ".__("Global Content errors have been fixed.", 'greyd_hub')."</h5>", 'info');
			}
			if ($log) Log::log_message(__("GC checked.", 'greyd_hub'), 'debug');
		}

		// inform about missing plugins
		if (count($queries['plugins_missing']) > 0 && $log) {
			$plugins_list = implode('<br>', $queries['plugins_missing']);
			Log::log_message("<h5>".__("The following plugins were not found on the installation and should be installed manually:", 'greyd_hub')."</h5><span>".$plugins_list."</span>", 'info');
		}

		// final log
		if ($result == "danger") {
			if ($log) Log::log_message("<h5>".sprintf(__("There were errors when importing the database on the domain \"%s\". Details can be found in the individual logs.", 'greyd_hub'), $domain)."</h5>", 'danger');
			return false;
		} 
		else {
			if ($log) Log::log_message(__("The new database entries probably do not recognize the current media. To make a complete website migration, upload the \"Media & files\" and check the active design.", 'greyd_hub'), 'info');
			if ($result == "info") {
				if ($log) Log::log_message("<h5>".sprintf(__("Some unsupported database commands were found during the import on the domain \"%s\". These were skipped.", 'greyd_hub'), $domain)."</h5>", 'info');
			} 
			else if ($result == "success") {
				if ($log) Log::log_message("<h5>".sprintf(__("On the domain \"%s\" all tables have been exchanged successfully.", 'greyd_hub'), $domain)."</h5>", 'success');
			}
		}
		flush_rewrite_rules(false);
		
		return true;
	}

	/**
	 * Import Blog Media files.
	 * 
	 * @param array  $file		The input file.
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 * @param int    $blogid	Blog ID of the selected blog.
	 * @param string $domain	Domain of the selected blog.
	 * @param bool   $log		Trigger logs.
	 * 
	 * @return bool
	 */
	public static function import_blog_media( $file, $blogid, $domain, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Import of media on the domain \"%s\" starts. (File: %s)", 'greyd_hub'), $domain, $file['name'])."</h5>", 'info');
		
		// open zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// look for 'uploads' in zipped root folder
		$found = false;
		for ($i=0; $i<$zip->numFiles; $i++) {
			$tmp = explode('/', $zip->statIndex($i)['name']);
			
			if (strpos($tmp[0], 'uploads') !== false) {
				$found = true;
				$oldname = $zip->statIndex($i)['name'];
				$newname = str_replace($tmp[0].'/', '', $oldname);
				if (isset($newname) && !empty($newname)) $zip->renameName($oldname, $newname);
			}
		}
		// close to save names
		$zip->close();
		if (!$found) {
			Log::log_abort(__("The ZIP archive does not appear to contain any media.", 'greyd_hub'));
			return false;
		}

		// reopen zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// set path
		$zip_path = Admin::$urls->uploads_folder; // default uploads
		$suffix = "/sites/".$blogid;
		// append suffix if not already
		if ( $blogid > 1 && substr_compare( $zip_path, $suffix, -strlen($suffix) ) !== 0 ) {
			$zip_path .= $suffix;
		}
		
		// delete old uploads
		Hub_Helper::delete_directory($zip_path);
		
		if ($log) Log::log_message(__("Deleted all media.", 'greyd_hub'), 'info', true);

		// extract zip
		if ($log) Log::log_message(__("Extracting zip ...", 'greyd_hub'), 'debug');
		$zip->extractTo( $zip_path );
		$zip->close();

		// move wp_fonts
		if (file_exists($zip_path."/wp_fonts")) {
			
			// get font files
			$fonts = Hub_Helper::list_files_in_dir([], $zip_path."/wp_fonts");
			if (count($fonts) > 0) {
				// init filesystem
				global $wp_filesystem;
				if ( !function_exists( 'download_url' ) ) {
					include ABSPATH.'wp-admin/includes/file.php';
				}
				\WP_Filesystem();
				// set font path
				$font_dir = wp_normalize_path( function_exists( 'wp_get_font_dir' ) ? wp_get_font_dir()['path'] : WP_CONTENT_DIR.'/fonts' );
				if (!file_exists($font_dir)) mkdir($font_dir, 0755, true);
				// move font files
				foreach ( $fonts as $font ) {
					$fileinfo = pathinfo($font);
					$new_file = $font_dir."/".$fileinfo["basename"];
					$wp_filesystem->move( $font, $new_file, true );
				}
			}
			// delete tmp folder
			Hub_Helper::delete_directory($zip_path."/wp_fonts");

			// if ($log) Log::log_message(__("WP Fonts erstellt.", 'greyd_hub'), 'info', true);
		}

		if ($log) Log::log_message("<h5>".__("Media and files have been uploaded successfully.", 'greyd_hub')."</h5>", 'success', true);

		return true;
	}

	/**
	 * Import Blog Plugins.
	 * 
	 * @param array  $file		The input file.
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 * @param int    $blogid	Blog ID of the selected blog.
	 * @param string $domain	Domain of the selected blog.
	 * @param bool   $log		Trigger logs.
	 * 
	 * @return bool
	 */
	public static function import_blog_plugins( $file, $blogid, $domain, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Import of plugins on the domain \"%s\" starts. (File: %s)", 'greyd_hub'), $domain, $file['name'])."</h5>", 'info');
		
		// open zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// look for 'plugins' in zipped root folder
		$found = false;
		$plugins = array();
		for ($i=0; $i<$zip->numFiles; $i++) {
			$tmp = explode('/', $zip->statIndex($i)['name']);
			if (strpos($tmp[0], 'plugins') !== false) {
				$found = true;
				$plugins[] = $tmp[1];
				$oldname = $zip->statIndex($i)['name'];
				$newname = str_replace($tmp[0].'/', '', $oldname);
				if (isset($newname) && !empty($newname)) $zip->renameName($oldname, $newname);
			}
		}
		$plugins = array_unique($plugins);
		// close to save names
		$zip->close();
		if (!$found) {
			Log::log_abort(__("The ZIP archive does not appear to contain any plugins.", 'greyd_hub'));
			return false;
		}
		
		// reopen zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// set path
		$zip_path = Admin::$urls->plugins_path; // default plugins
		// get available plugins
		$installed_plugins = scandir(realpath($zip_path));
		unset($installed_plugins[array_search('.', $installed_plugins, true)]);
		unset($installed_plugins[array_search('..', $installed_plugins, true)]);

		// remove already installed plugins found in zip
		foreach ($plugins as $plugin) {
			if (in_array($plugin, $installed_plugins)) {
				Hub_Helper::delete_directory($zip_path.$plugin);
			}
		}

		if ($log) Log::log_message(__("Already installed plugins deleted.", 'greyd_hub'), 'info', true);

		// extract zip
		if ($log) Log::log_message(__("Extracting zip ...", 'greyd_hub'), 'debug');
		$zip->extractTo( $zip_path );
		$zip->close();

		if ($log) Log::log_message("<h5>".__("Plugins were successfully installed.", 'greyd_hub')."</h5>", 'success', true);

		return true;
	}

	/**
	 * Import Blog Themes.
	 * 
	 * @param array  $file		The input file.
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 * @param int    $blogid	Blog ID of the selected blog.
	 * @param string $domain	Domain of the selected blog.
	 * @param bool   $log		Trigger logs.
	 * 
	 * @return bool
	 */
	public static function import_blog_themes( $file, $blogid, $domain, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Import of themes on the domain \"%s\" starts. (File: %s)", 'greyd_hub'), $domain, $file['name'])."</h5>", 'info');
		
		// open zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// look for 'themes' in zipped root folder
		$found = false;
		$themes = array();
		for ( $i=0; $i<$zip->numFiles; $i++ ) {
			$tmp = explode('/', $zip->statIndex($i)['name']);
			if ( strpos($tmp[0], 'themes') !== false ) {
				$found = true;
				$themes[] = $tmp[1];
				$oldname = $zip->statIndex($i)['name'];
				$newname = str_replace( $tmp[0].'/', '', $oldname );
				if (isset($newname) && !empty($newname)) $zip->renameName($oldname, $newname);
			}
		}
		$themes = array_unique( $themes );
		// close to save names
		$zip->close();
		if (!$found) {
			Log::log_abort(__("The ZIP archive does not seem to contain any themes.", 'greyd_hub'));
			return false;
		}
		
		// reopen zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// set path
		$zip_path = Admin::$urls->themes_path;
		// get available themes
		$installed_themes = scandir( realpath($zip_path) );
		unset($installed_themes[array_search('.', $installed_themes, true)]);
		unset($installed_themes[array_search('..', $installed_themes, true)]);
		
		// delete already installed themes found in zip
		foreach ($themes as $theme) {
			if ( in_array($theme, $installed_themes) ) {
				Hub_Helper::delete_directory($zip_path.$theme);
			}
		}

		if ($log) Log::log_message(__("Already installed themes deleted.", 'greyd_hub'), 'info', true);

		// extract zip
		if ($log) Log::log_message(__("Extracting zip ...", 'greyd_hub'), 'debug');
		$zip->extractTo( $zip_path );
		$zip->close();

		if ($log) Log::log_message("<h5>".__("Themes were successfully installed.", 'greyd_hub')."</h5>", 'success', true);

		return true;
	}

	/**
	 * Import Blog Content.
	 * possible modes: 'content', 'site', 'template'
	 * 
	 * @param string $mode		Import Mode.
	 * @param array  $file		The input file.
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 * @param int    $blogid	Blog ID of the selected blog.
	 * @param string $domain	Domain of the selected blog.
	 * @param bool   $log		Trigger logs.
	 * 
	 * @return bool
	 */
	public static function import_blog_content( $mode, $file, $blogid, $domain, $log ) {

		if ($log) Log::log_message("<h5>".sprintf(__("Import of the website on the domain \"%s\" starts. (File: %s)", 'greyd_hub'), $domain, $file['name'])."</h5>", 'info');
		
		// open zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// look for database, uploads, plugins and themes
		$found = array(
			'database' => false,
			'uploads' => false,
			'plugins' => false,
			'themes'=> false
		);
		for ($i=0; $i<$zip->numFiles; $i++) {
			if (substr($zip->getNameIndex($i), 0, 9) === "__MACOSX/" || strpos($zip->getNameIndex($i), ".DS_Store") !== false) continue;
			$tmp = explode('/', $zip->statIndex($i)['name'], 2);
			if (count($tmp) > 1 && (strpos($tmp[0], 'content') !== false || strpos($tmp[0], 'fullsite') !== false)) {
				if (strpos($tmp[1], 'database') !== false) $found['database'] = $tmp[1];
				if (strpos($tmp[1], 'uploads') !== false) $found['uploads'] = $tmp[1];
				if (strpos($tmp[1], 'themes') !== false) $found['themes'] = $tmp[1];
				if (strpos($tmp[1], 'plugins') !== false) $found['plugins'] = $tmp[1];
				if (isset($tmp) && !empty($tmp[1])) $zip->renameName($zip->statIndex($i)['name'], $tmp[1]);
			}
		}
		// close to save names
		$zip->close();
		if ( !$found['database'] ) {
			if ($log) {
				Log::log_message("<h5>".__("The ZIP archive does not seem to contain a database.", 'greyd_hub')."</h5>", 'danger');
				Log::log_end('import fail');
			}
			return false;
		}

		if ( !$found['uploads'] ) {
			if ($log) {
				Log::log_message("<h5>".__("The ZIP archive does not appear to contain any media or files.", 'greyd_hub')."</h5>", 'warning');
				// Log::log_end('import fail');
			}
			// we do not need to abort here, because we can still import the database
			// return false;
		}

		// reopen zip
		$zip = new \ZipArchive;
		$zip->open( $file['tmp_name'] );

		// set path
		$zip_path = Admin::$urls->backup_path."temp"; // default backup
		if (!file_exists($zip_path)) mkdir($zip_path, 0755, true);
		$zip_path .= "/";

		// extract zip
		if ($log) Log::log_message(__("Extracting zip ...", 'greyd_hub'), 'debug');
		$zip->extractTo( $zip_path );
		$zip->close();

		if ($log) Log::log_message(__("Temporary files created.", 'greyd_hub'), '');

		if ( $found['uploads'] ) {
			// clean uploads folder before import
			self::delete_blog_uploads( $blogid );
		}

		// installing files
		$domain = esc_url( $domain );
		$domain_link = sprintf( '<a href="%s" target="_blank">%s</a>', $domain, $domain );
		$result = array();
		// import plugins
		if ($mode == 'site' && $found['plugins']) {
			$file_plugins = array( 'tmp_name' => $zip_path.$found['plugins'], 'name' => $found['plugins'] );
			$result['plugins'] = self::import_blog_plugins($file_plugins, $blogid, $domain, $log);
		}

		// import themes
		if ($mode == 'site' && $found['themes']) {
			$file_themes = array( 'tmp_name' => $zip_path.$found['themes'], 'name' => $found['themes'] );
			$result['themes'] = self::import_blog_themes($file_themes, $blogid, $domain, $log);
		}

		if ( $found['uploads'] ) {
			// import media files
			$file_uploads = array( 'tmp_name' => $zip_path.$found['uploads'], 'name' => $found['uploads'] );
			$result['uploads'] = self::import_blog_media($file_uploads, $blogid, $domain, $log);
		}

		// import database
		$file_database = array( 'tmp_name' => $zip_path.$found['database'], 'name' => $found['database'] );
		$result['database'] = self::import_blog_db($file_database, $blogid, $domain, $log);

		// delete temp files
		Hub_Helper::delete_directory($zip_path);
		if ($log) Log::log_message(__("Temporary files deleted.", 'greyd_hub'), '');

		// todo: check results for errors

		// final log
		if ($log) {
			Log::log_message("<h5>".__("Import successful", 'greyd_hub')."</h5><span>".sprintf(__("The website was successfully installed on the domain \"%s\".", 'greyd_hub'), $domain_link)."</span>", 'success');
			// display notice to go back to last hub url
			if ( isset($_POST["redirect_url"]) && $_POST["redirect_url"] !== Hub_Helper::get_current_url() ) {
				$redirect_url   = remove_query_arg( "tab", $_POST["redirect_url"] );
				$redirect_label = str_replace( ["http://", "https://", "/wp-admin/network/admin.php?page=greyd_hub", "/wp-admin/admin.php?page=greyd_hub"], "", $redirect_url )." > ".__("Greyd.Hub");
				Log::log_message(
					"<h3>".__("Migration successful.", 'greyd_hub')."</h3>".
					"<h5>".sprintf( __("Redirect to: %s", 'greyd_hub'), "<a href='$redirect_url'>$redirect_label</a>" )."</h5>".
					"<p>".sprintf( __("You are in Greyd. %s the hub of the page. You will automatically return to the previous Greyd in 5 seconds. Hub window forwarded.", 'greyd_hub'), "<strong>".get_site_url()."</strong>" ).
					"</p><span class='button button-primary' onclick='clearTimeout(redirectTimeout), $(this).remove();'>".__("Cancel redirect", "greyd_hub")."</span>",
				'success' );
				echo "<script> var redirectTimeout = setTimeout(function(){
					window.location.replace('{$_POST['redirect_url']}');
				}, 5000);</script>";
			}
		}

		if ($mode == 'template') {
			return "imported";
		}
		return true;
	}


	/*
	=======================================================================
		Websites page - global styles design
			download_styles
			import_styles
	=======================================================================
	*/

	/**
	 * Download active global styles.
	 * 
	 * @param int $blogid		ID of Blog.
	 * @param string $domain	Domain of Blog.
	 */
	public static function download_styles( $blogid, $domain ) {

		$result = "fail";

		if ( !empty($blogid) && $blogid > 0 ) {
			if (is_multisite()) switch_to_blog($blogid);

			// debug("export styles ".$blogid." ".$domain);
			if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver_Gutenberg::get_user_global_styles_post_id();
			} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
			}
			if ( $styles_id ) {
				// get global-styles post content
				$content = get_post_field( 'post_content', $styles_id );
				$data    = json_decode( $content );

				if ( $data && json_last_error() == 0 ) {

					// search custom fonts
					$fonts = array();
					// search greyd fonts
					$greyd_fonts = get_option( 'greyd_fonts', false );
					if (
						( isset($greyd_fonts['fonts_local']) && !empty($greyd_fonts['fonts_local']) ) ||
						( isset($greyd_fonts['google_fonts_local']) && !empty($greyd_fonts['google_fonts_local']) )
					) {
						// debug($greyd_fonts['google_fonts_local']);
						// debug($greyd_fonts['fonts_local']);
						foreach ( $data->settings->typography->fontFamilies->theme as $i => $font ) {
							// debug($font);
							if ( $font->fontFamily == "" ) continue;
							$fontname = $font->fontFamily;
							$fontname = explode(':', $fontname)[0];
							if ( in_array($fontname, $greyd_fonts['google_fonts_list']) ) {
								// google font
								foreach ( $greyd_fonts['google_fonts_local'] as $slug => $fontFaces ) {
									if ( is_array($fontFaces) && $fontFaces[0]['fontFamily'] == $fontname ) {
										// debug($fontFaces);
										$save = $fontFaces;
										foreach ( $fontFaces as $j => $fontFace ) {
											// debug($fontFace->src);
											$src = Helper::url_to_abspath( $fontFace['local'] );
											$fileinfo = pathinfo($src);
											// debug($fileinfo);
											// zip font file
											array_push( $fonts, array(
												"real" => $src,
												"relative" => "greyd_fonts/google/".$fileinfo["basename"]
											) );
											// modify url
											$save[$j]['local'] = $fileinfo["basename"];
										}
										// modify data
										$data->settings->typography->fontFamilies->theme[$i]->fontFace = $save;
										break;
									}
								}
							}
							else {
								// custom font
								foreach ( $greyd_fonts['fonts_local'] as $slug => $fontFaces ) {
									if ( is_array($fontFaces) && $fontFaces[0]['fontFamily'] == $fontname ) {
										$save = $fontFaces;
										foreach ( $fontFaces as $j => $fontFace ) {
											// debug($fontFace->src);
											$src = Helper::url_to_abspath( $fontFace['src'] );
											$fileinfo = pathinfo($src);
											// debug($fileinfo);
											// zip font file
											array_push( $fonts, array(
												"real" => $src,
												"relative" => "greyd_fonts/custom/".$fileinfo["basename"]
											) );
											// modify url
											$save[$j]['src'] = $fileinfo["basename"];
										}
										// modify data
										$data->settings->typography->fontFamilies->theme[$i]->fontFace = $save;
										break;
									}
								}
							}

						}
					}
					// search wp fonts
					if ( isset($data->settings->typography->fontFamilies->custom) ) {
						foreach ( $data->settings->typography->fontFamilies->custom as $i => $font ) {
							// debug($font);
							foreach ( $font->fontFace as $j => $fontFace ) {
								// debug($fontFace->src);
								$src = Helper::url_to_abspath( $fontFace->src );
								$fileinfo = pathinfo($src);
								// debug($fileinfo);
								// zip font file
								array_push( $fonts, array(
									"real" => $src,
									"relative" => "fonts/".$fileinfo["basename"]
								) );
								// modify url
								$data->settings->typography->fontFamilies->custom[$i]->fontFace[$j]->src = $fileinfo["basename"];
							}
						}
					}

					// generate new content
					$new_content = json_encode( $data );
					if ( is_string( $new_content ) && $new_content != "" ) {

						// filename
						$json_name = sanitize_title( $domain ) . "_global-styles_".date("ymd").".json";
						// check if backup folder exists
						Hub_Helper::check_backup_path();
						// save file
						if (file_put_contents(Admin::$urls->backup_path.$json_name, $new_content)) {
							// if custom fonts are found, make zip
							if ( isset($fonts) && !empty($fonts) ) {
								// add json to zip
								array_push( $fonts, array(
									"real" => Admin::$urls->backup_path.$json_name,
									"relative" => $json_name
								) );
								// zip files
								$zip_name = sanitize_title( $domain )."_global-styles_".date("ymd").".zip";
								$zip_path = Admin::$urls->backup_path.$zip_name;
								if (file_exists($zip_path)) {
									unlink($zip_path);
								}
								$created = Hub_Helper::zip_files( $zip_path, $fonts );
								unlink(Admin::$urls->backup_path.$json_name);

								if ($created) {
									// download zip file
									Hub_Helper::start_download($zip_name, $domain, 'export_mods');
									$result = "success";
								}
							}
							// if not, just json
							else {
								// download json file
								Hub_Helper::start_download($json_name, $domain, 'export_mods');
								$result = "success";
							}
						}

					}

				}

			}

			if (is_multisite()) restore_current_blog();
		}
		
		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'export_mods',
			"replace"   => $domain
		);
	}

	/**
	 * Import global styles design to Blog.
	 * 
	 * @param int $blogid		ID of Blog.
	 * @param string $domain	Domain of Blog.
	 */
	public static function import_styles( $blogid, $domain, $input ) {

		$result = "fail";
		
		if ( empty($blogid) || $blogid < 1 ) {
			Log::log_abort( __("Invalid blog ID.", 'greyd_hub') );
			return false;
		}

		// get input
		// debug($_FILES[$input]);
		if ( !isset($_FILES[$input]) || intval($_FILES[$input]['error']) > 0 ) {
			Log::log_abort( __("Please select a valid file.", 'greyd_hub') );
			return false;
		}

		// zip (json and fonts)
		if ( in_array($_FILES[$input]['type'], array( 'application/zip', 'application/x-zip-compressed' )) ) {
			$input_file = $_FILES[$input];
				
			// open zip
			$zip = new \ZipArchive;
			$zip->open( $input_file['tmp_name'] );

			// look for 'global-styles' in zipped root folder
			$found = false;
			// $themes = array();
			for ( $i=0; $i<$zip->numFiles; $i++ ) {
				$tmp = explode('/', $zip->statIndex($i)['name']);
				if ( strpos($tmp[0], 'global-styles') !== false ) {
					$found = true;
					// $themes[] = $tmp[1];
					$oldname = $zip->statIndex($i)['name'];
					$newname = str_replace( $tmp[0].'/', '', $oldname );
					if (isset($newname) && !empty($newname)) $zip->renameName($oldname, $newname);
				}
			}
			// $themes = array_unique( $themes );
			// close to save names
			$zip->close();
			if (!$found) {
				Log::log_abort(__("The ZIP archive does not appear to contain any global styles.", 'greyd_hub'));
				return false;
			}
			
			// reopen zip
			$zip = new \ZipArchive;
			$zip->open( $input_file['tmp_name'] );

			// set path
			$zip_path = Admin::$urls->backup_path."temp"; // default backup
			if (!file_exists($zip_path)) mkdir($zip_path, 0755, true);
			$zip_path .= "/";

			// extract zip
			$zip->extractTo( $zip_path );
			$zip->close();

			$json_name = str_replace(".zip", ".json", $input_file['name']);
			$data = json_decode(file_get_contents(Admin::$urls->backup_path."temp/".$json_name)); 

			if ( $data && json_last_error() == 0 ) {

				global $wp_filesystem;
				if ( !function_exists( 'download_url' ) ) {
					include ABSPATH.'wp-admin/includes/file.php';
				}
				\WP_Filesystem();

				$upload_dir = wp_get_upload_dir();
				$folder     = $upload_dir['error'] ? WP_CONTENT_DIR.'/uploads/greyd_fonts' : $upload_dir['basedir'].'/greyd_fonts';

				$font_dir    = wp_normalize_path($folder);
				$font_folder = Helper::abspath_to_url( $font_dir );
				$font_temp   = $zip_path."greyd_fonts";

				// greyd fonts
				$greyd_fonts = get_option( 'greyd_fonts', array() );
				foreach ( $data->settings->typography->fontFamilies->theme as $i => $family ) {
					if ( isset($family->fontFace) ) {
						// debug($family);
						foreach ( $family->fontFace as $j => $fontFace ) {
							if ( isset($fontFace->local) ) {
								// google font
								$new_src = $font_folder."/google/".$fontFace->local;
								$tmp_file = $font_temp."/google/".$fontFace->local;
								$new_file = $font_dir."/google/".$fontFace->local;
								// modify url
								$family->fontFace[$j]->local = $new_src;
							}
							else if ( isset($fontFace->src) ) {
								// custom font
								// debug($fontFace->src);
								$new_src = $font_folder."/custom/".$fontFace->src;
								$tmp_file = $font_temp."/custom/".$fontFace->src;
								$new_file = $font_dir."/custom/".$fontFace->src;
								// modify url
								$family->fontFace[$j]->src = $new_src;
							}
							// copy font file
							if ( !is_dir( dirname( $new_file ) ) ) {
								wp_mkdir_p( dirname( $new_file ) );
							}
							$success = $wp_filesystem->copy( $tmp_file, $new_file, true );
						}

						// save in option
						// debug($family);
						if ( isset($family->fontFace[0]->local) ) {
							// google font
							$fontslug = preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '-', strtolower($family->fontFace[0]->fontFamily) ) );
							if ( !isset($greyd_fonts['google_fonts_local']) ) {
								$greyd_fonts['google_fonts_local'] = array();
							}
							$greyd_fonts['google_fonts_local'][$fontslug] = array();
							foreach ( $family->fontFace as $fontFace ) {
								array_push( $greyd_fonts['google_fonts_local'][$fontslug], (array)$fontFace );
							}
						}
						else if ( isset($family->fontFace[0]->src) ) {
							// custom font
							$fontslug = sanitize_key($family->fontFace[0]->fontFamily);
							if ( !isset($greyd_fonts['fonts_local']) ) {
								$greyd_fonts['fonts_local'] = array();
							}
							$greyd_fonts['fonts_local'][$fontslug] = array();
							foreach ( $family->fontFace as $fontFace ) {
								array_push( $greyd_fonts['fonts_local'][$fontslug], (array)$fontFace );
							}
						}

						// remove fontFace
						unset($data->settings->typography->fontFamilies->theme[$i]->fontFace);
					}
				}
				// update option
				// debug($greyd_fonts['google_fonts_local']);
				// debug($greyd_fonts['fonts_local']);
				update_option( 'greyd_fonts', $greyd_fonts );

				// wp fonts
				if ( isset($data->settings->typography->fontFamilies->custom) ) {

					$font_dir    = wp_normalize_path( function_exists( 'wp_get_font_dir' ) ? wp_get_font_dir()['path'] : WP_CONTENT_DIR.'/fonts' );
					$font_folder = Helper::abspath_to_url( $font_dir );
					$font_temp   = $zip_path."fonts";
					foreach ( $data->settings->typography->fontFamilies->custom as $i => $family ) {
						// debug($family);
						foreach ( $family->fontFace as $j => $fontFace ) {
							// debug($fontFace->src);
							$new_src = $font_folder."/".$fontFace->src;
							$tmp_file = $font_temp."/".$fontFace->src;
							$new_file = $font_dir."/".$fontFace->src;
							// modify url
							$data->settings->typography->fontFamilies->custom[$i]->fontFace[$j]->src = $new_src;
							// copy font file
							$success = $wp_filesystem->copy( $tmp_file, $new_file, true );
						}
						// create fontfamily post
						$post = array(
							'post_title'   => $family->name,
							'post_name'    => $family->slug,
							'post_type'    => 'wp_font_family',
							'post_content' => wp_json_encode( $family ),
							'post_status'  => 'publish',
						);
						$post_id = post_exists( $post["post_name"] );
						if ( $post_id ) $post["ID"] = $post_id;
						$post_id = wp_insert_post( $post );
					}

				}

			}
			// debug($data);
			
			// delete temp files
			Hub_Helper::delete_directory($zip_path);
		}
		// only json
		else {
			$input_file = Hub_Helper::get_uploaded_file($input, 'json');
			if ( !$input_file ) {
				return;
			}
			// check input
			$data = json_decode(file_get_contents($input_file["tmp_name"])); 
			// debug($data);
		}

		if (
			$data &&
			json_last_error() == 0 &&
			isset($data->isGlobalStylesUserThemeJSON) &&
			isset($data->version)
		) {
			if (is_multisite()) switch_to_blog($blogid);

			// debug("import styles ".$blogid." ".$domain);
			if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver_Gutenberg::get_user_global_styles_post_id();
			} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
				$styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
			}
			if ( $styles_id ) {
				// generate new content
				$new_content = json_encode( $data );
				if ( is_string( $new_content ) ) {
					// Update styles post.
					$post = array(
						'ID'           => $styles_id,
						'post_content' => wp_slash($new_content),
					);
					if ( !is_wp_error( wp_update_post( $post ) ) ) {
						$result = "success";
					}
				}
			}

			if (is_multisite()) restore_current_blog();
		}
		
		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'import_styles',
			"replace"   => $domain
		);
	}

	/*
	=======================================================================
		Websites page - more
			deleteMods (deprecated -> import empty object)
			editMods -> import_thememods

			checkSite -> check_blog
			resetSite -> reset_blog
			deleteSite -> delete_blog
	=======================================================================
	*/

	/**
	 * Import Thememods/Design to Blog.
	 * 
	 * @param int $blogid		ID of Blog.
	 * @param string $domain	Domain of Blog.
	 * @param string $mods		json encoded and url encoded Themmods.
	 * @param string $option	Name of the wp option saving the data.
	 */
	public static function import_thememods( $blogid, $domain, $mods, $option ) {

		// return if no mods or option
		if ( empty($mods) || empty($option)) return;

		$result = "fail";
		$class = $mods === "{}" ? 'reset_mods' : 'import_mods';
		$mods_data = json_decode(urldecode($mods), true);
		if (json_last_error() == 0) {
			if (is_multisite()) switch_to_blog($blogid);
				$mods_data_backup = get_option($option);
				$option_backup = "_backup_".$option;
				if (get_option($option_backup) === false && update_option($option_backup, false) === false) 
					add_option($option_backup, $mods_data_backup);
				else update_option($option_backup, $mods_data_backup);
				update_option($option, $mods_data);
			if (is_multisite()) restore_current_blog();
			$result = "success";
		}

		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => $class,
			"replace"   => $domain
		);
	}


	/**
	 * Check Blog for database serialization errors.
	 * 
	 * @param int $blogid	ID of Blog to check
	 */
	public static function check_blog( $blogid ) {
		$result = "fail";
		$info = "";
		try {
			if (is_multisite()) switch_to_blog($blogid);
				global $wpdb;
				$db_errors = Hub_Helper::check_db_errors(array(
					'prefix' => $wpdb->base_prefix,
					'id' => $wpdb->blogid,
					'tables' => array( "`".$wpdb->options."`", "`".$wpdb->postmeta."`" ),
				));
				if ($db_errors == "") 
					$result = "success";
				else { //if ($db_errors != "") 
					$result = "reload"; 
					$info = $db_errors;
				}
			if (is_multisite()) restore_current_blog();
		}
		catch(Exception $e) { }

		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'check_greyd',
			"replace"   => $info
		);
	}

	/**
	 * Reset Blog.
	 * 
	 * @param int $blogid	ID of Blog to reset
	 */
	public static function reset_blog( $blogid ) {
		global $current_user;
		$result = "fail";
		$info = "";
		
		// get data
		$user = $current_user->ID;
		$theme = wp_get_theme();
		try {
			// delete stuff
			self::delete_blog_tables($blogid);
			self::delete_blog_uploads($blogid);
			// init new site
			wp_initialize_site($blogid, array( 
				'user_id' => $user, 
				'title' => __("WordPress blog", 'greyd_hub').(is_multisite() ? " ".$blogid : ""),
			) );
			
			$result = "reload";
		}
		catch(exception $e) { }
		
		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'reset_website',
			"replace"   => $info
		);
	}

	/**
	 * Delete Blog.
	 * 
	 * @param int $blogid	ID of Blog to delete
	 */
	public static function delete_blog( $blogid ) {
		global $wpdb;
		$result = "fail";
		$info = "";

		try {
			// delete stuff
			self::delete_blog_tables($blogid);
			self::delete_blog_uploads($blogid);
			// delete blog infos
			$wpdb->query(
				"DELETE FROM {$wpdb->blogs}  
				  WHERE blog_id = '{$blogid}'" );
			$wpdb->query(
				"DELETE FROM {$wpdb->blogmeta}  
				  WHERE blog_id = '{$blogid}'" );
			$meta = $wpdb->get_results(
				"SELECT * 
				   FROM {$wpdb->sitemeta} 
				  WHERE meta_key = 'blog_count'", OBJECT );
			$count = $meta[0]->meta_value-1;
			$wpdb->query(
				"UPDATE {$wpdb->sitemeta}  
					SET meta_value = '{$count}'
				  WHERE meta_key = 'blog_count'" );

			$result = "success";
		}
		catch(exception $e) { }

		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'delete_website',
			"replace"   => $info
		);
	}

	/**
	 * Delete Blog tables.
	 * 
	 * @param int $blogid	ID of Blog to delete
	 */
	public static function delete_blog_tables( $blogid ) {
		global $wpdb;
		// get all blogid tables
		$prefix = ($blogid == 1) ? $wpdb->base_prefix : $wpdb->base_prefix.$blogid.'_';
		$alltables = $wpdb->get_results("SHOW TABLES FROM `".$wpdb->dbname."`");
		if ($alltables && count($alltables) > 0) {
			$tables = array();
			foreach ($alltables as $table) {
				$tbl = $table->{'Tables_in_'.$wpdb->dbname};
				if (preg_match('/^('.$prefix.')\D+/', $tbl)) {
					if ($tbl != $wpdb->base_prefix."blogs" &&
						$tbl != $wpdb->base_prefix."blog_versions" &&
						$tbl != $wpdb->base_prefix."blogmeta" &&
						$tbl != $wpdb->base_prefix."registration_log" &&
						$tbl != $wpdb->base_prefix."signups" &&
						$tbl != $wpdb->base_prefix."site" &&
						$tbl != $wpdb->base_prefix."sitemeta" &&
						$tbl != $wpdb->base_prefix."sitecategories" &&
						$tbl != $wpdb->base_prefix."users" &&
						$tbl != $wpdb->base_prefix."usermeta") {
							$tables[] = $tbl;
					}
				}
			}
			foreach ($tables as $table) {
				$wpdb->query("DROP TABLE IF EXISTS ".$table);
			}
		}
	}

	/**
	 * Delete Blog uploads.
	 * 
	 * @param int $blogid	ID of Blog to delete
	 */
	public static function delete_blog_uploads( $blogid ) {
		$zip_path    = Admin::$urls->uploads_folder; // default uploads
		$ex_path     = $zip_path."/sites/"; // exclude other site uploads
		$suffix      = "/sites/".$blogid;
		// use sites subfolder if on multisite
		if ( $blogid > 1 ) {
			// append suffix if not already
			if ( substr_compare( $zip_path, $suffix, -strlen($suffix) ) !== 0 ) {
				$zip_path .= $suffix;
			}
			$ex_path = "";
		}
		Hub_Helper::delete_directory($zip_path, $ex_path);
	}


	/*
	=======================================================================
		Backups page - Downloads
			downloadFull -> download_full_wp
			sqlExport -> download_full_db (split fom old function)
			deleteFile -> delete_file
	=======================================================================
	*/

	/**
	 * Download the full wordpress installation.
	 * 
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Trigger the download.
	 * @param bool   $log           Trigger logs. (default: true)
	 * 
	 * @return string $zip_name     Name of the new ZIP archive placed inside the backup folder.
	 */
	public static function download_full_wp( $domain, $trigger, $log=true ) {

		// render logging overlay
		if ($log) {
			Log::log_start("export loading");
			Log::log_message("<h5>".sprintf(__("Backup of all files of '%s' was started.", 'greyd_hub'), $domain)."</h5>", 'info');
		}
		
		// make name and prep folders
		$domain = str_replace("/", "-", untrailingslashit( $domain ));
		$zip_name = $domain."_full_".date("ymd").".zip";
		$zip_path = Admin::$urls->backup_path.$zip_name;
		$folder = Admin::$urls->wp_folder;
		// check
		Hub_Helper::check_backup_path();

		// check if wp-content is inside wp
		if (strpos(wp_normalize_path(WP_CONTENT_DIR).'/', $folder.'/') === false) {
			// no: make zip
			$content_zip_name = $domain."_full_content_".date("ymd").".zip";
			$content_zip_path = Admin::$urls->backup_path.$content_zip_name;
			$content_folder = wp_normalize_path(WP_CONTENT_DIR);
			
			if ($log) Log::log_message(sprintf(__("Temporary file '%s' is being saved.", 'greyd_hub'), $content_zip_name), '', true);

			$content_zip = Hub_Helper::zip_folder($content_zip_path, $content_folder);
		}

		// check if plugins is inside wp or wp-content
		if (strpos(Admin::$urls->plugins_path, $folder.'/') === false &&
			strpos(Admin::$urls->plugins_path, wp_normalize_path(WP_CONTENT_DIR).'/') === false) {
			// no: make zip
			$plugins_zip_name = $domain."_full_plugins_".date("ymd").".zip";
			$plugins_zip_path = Admin::$urls->backup_path.$plugins_zip_name;
			$plugins_folder = Hub_Helper::get_nice_url(Admin::$urls->plugins_path);
			
			if ($log) Log::log_message(sprintf(__("Temporary file '%s' is being saved.", 'greyd_hub'), $plugins_zip_name), '', true);

			$plugins_zip = Hub_Helper::zip_folder($plugins_zip_path, $plugins_folder);
		}

		// check if uploads is inside wp or wp-content
		if (strpos(Admin::$urls->uploads_folder.'/', $folder.'/') === false &&
			strpos(Admin::$urls->uploads_folder.'/', wp_normalize_path(WP_CONTENT_DIR).'/') === false) {
			// no: make zip
			$uploads_zip_name = $domain."_full_uploads_".date("ymd").".zip";
			$uploads_zip_path = Admin::$urls->backup_path.$uploads_zip_name;
			$uploads_folder = Admin::$urls->uploads_folder;
			
			if ($log) Log::log_message(sprintf(__("Temporary file '%s' is being saved.", 'greyd_hub'), $uploads_zip_name), '', true);

			$uploads_zip = Hub_Helper::zip_folder($uploads_zip_path, $uploads_folder);
		}
		
		// make download
		if (isset($content_zip) || isset($plugins_zip) || isset($uploads_zip)) {
			// if one or more extra folder(s) are found, make extra zip of this folder
			$wp_zip_name = $domain."_full_wp_".date("ymd").".zip";
			$wp_zip_path = Admin::$urls->backup_path.$wp_zip_name;
			
			if ($log) Log::log_message(sprintf(__("Temporary file '%s' is being saved.", 'greyd_hub'), $wp_zip_name), '', true);

			$wp_zip = Hub_Helper::zip_folder($wp_zip_path, $folder);

			// gather all extra zips
			$files = array( $wp_zip_name );
			if (isset($content_zip_name)) array_push($files, array( 
				"real" => $content_zip_path, 
				"relative" => $content_zip_name 
			));
			if (isset($plugins_zip_name)) array_push($files, array( 
				"real" => $plugins_zip_path, 
				"relative" => $plugins_zip_name 
			));
			if (isset($uploads_zip_name)) array_push($files, array( 
				"real" => $uploads_zip_path, 
				"relative" => $uploads_zip_name 
			));

			if ($log) Log::log_message(sprintf(__("Saving final file '%s'.", 'greyd_hub'), $zip_name), 'info', true);

			// make zip containing all extra zips
			$zip = Hub_Helper::zip_files($zip_path, $files);

			// delete temporary
			unlink($wp_zip_path);
			if (isset($content_zip_path)) unlink($content_zip_path);
			if (isset($plugins_zip_path)) unlink($plugins_zip_path);
			if (isset($uploads_zip_path)) unlink($uploads_zip_path);
			
			if ($log) Log::log_message(__("Temporary files deleted.", 'greyd_hub'), '', true);

		}
		else {

			if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $zip_name), 'info', true);

			// just zip this folder
			$zip = Hub_Helper::zip_folder($zip_path, $folder);
		}

		// download file
		if ($zip) {
			
			if ($log) {
				Log::log_message("<h5>".sprintf(__("The backup was created successfully. (file: %s)", 'greyd_hub'), $zip_name)."</h5>", 'success');
				Log::log_end("export success");
			}

			// make download
			if ( !$trigger ) return $zip_name;
			Hub_Helper::start_download($zip_name, $domain, 'export_full');
			return true;
		}

		if ($log) Log::log_end("export fail");
		Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_full',
			"replace"   => $domain
		);
		return false;

	}

	/**
	 * Download the full wordpress database.
	 * 
	 * @param string $domain        Domain of the selected blog.
	 * @param bool   $trigger       Whether to trigger the download.
	 * @param bool   $log           Trigger logs. (default: true)
	 * 
	 * @return string $sql_name     Name of the new sql file placed inside the backup folder.
	 */
	public static function download_full_db( $domain, $trigger, $log=true ) {

		global $wpdb;

		// render logging overlay
		if ($log) {
			Log::log_start("export loading");
			Log::log_message("<h5>".sprintf(__("Backup of entire database of '%s' started.", 'greyd_hub'), $domain)."</h5>", 'info');
		}
		
		// make name
		$domain = str_replace("/", "-", untrailingslashit( $domain ));
		$sql_name = $domain."_full_database_".date("ymd").".sql";
		
		// get all tables
		$tables = array();
		$alltables = $wpdb->get_results("SHOW TABLES FROM `".$wpdb->dbname."`");
		foreach ($alltables as $table) {
		
			if ($log) Log::log_message(sprintf(__("Exporting table '%s'.", 'greyd_hub'), $table->{'Tables_in_'.$wpdb->dbname}), '', true);
	
			$tables[] = $table->{'Tables_in_'.$wpdb->dbname};
		}
		
		if ($log) Log::log_message(sprintf(__("The file \"%s\" is being saved.", 'greyd_hub'), $sql_name), 'info', true);

		// make download
		if (Hub_Helper::make_sql($sql_name, $tables, array(), false)) {
			if ($log) {
				Log::log_message("<h5>".sprintf(__("The backup was created successfully. (file: %s)", 'greyd_hub'), $sql_name)."</h5>", 'success');
				Log::log_end("export success");
			}

			// make download
			if ( !$trigger ) return $sql_name;
			Hub_Helper::start_download($sql_name, $domain, 'export_full');
			return true;
		}
		
		if ($log) Log::log_end("export fail");
		Log::$overlay = array(
			"show"      => true,
			"action"    => 'fail',
			"class"     => 'export_full',
			"replace"   => $domain
		);
		return false;

	}

	/**
	 * Delete single file and render overlay.
	 * 
	 * @param string $name	File name relative to backup folder
	 */
	public static function delete_file($name) {
		
		$result = 'fail';
		if ( file_exists(Admin::$urls->backup_path.$name) ) {
			unlink(Admin::$urls->backup_path.$name);
			$result = 'success';
		}
		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => 'delete',
			"replace"   => $name
		);

	}


	/*
	=======================================================================
		Database page
			sqlExport -> download_db_tables (split fom old function)
			sqlDelete -> delete_db_tables
			importFileSQL -> import_db_tables (split fom old function)
	=======================================================================
	*/

	/**
	 * Download Tables from database.
	 * 
	 * @param array $tables			Array of Table names to delete
	 * @param array $replace		Array of old and new strings to replace in tables data
	 * @param bool $trigger			Whether to trigger the download.
	 * 
	 * @return string $sql_name		Name of the new sql file placed inside the backup folder.
	 */
	public static function download_db_tables( $tables, $replace, $trigger ) {

		if (count($tables) == 0) {
			Log::log_abort(__("No tables selected.", 'greyd_hub'), 'export');
			return;
		}

		// make name
		$domain = "";
		if ($replace['old']['domain'] && $replace['old']['domain'] != "" ) $domain = $replace['old']['domain'];
		if ($replace['new']['domain'] && $replace['new']['domain'] != "" ) $domain = $replace['new']['domain'];
		$domain = str_replace("/", "-", $domain);
		$sql_name = $domain."_database_".date("ymd").".sql";
		
		// make download
		if (Hub_Helper::make_sql($sql_name, $tables, $replace, false)) {
			// download file
			if ($trigger) Hub_Helper::start_download($sql_name, $domain, 'export_tables');
			else return $sql_name;
		}
		else {
			Log::$overlay = array(
				"show"      => true,
				"action"    => 'fail',
				"class"     => 'export_tables',
				"replace"   => $domain
			);
		}
	}

	public static function delete_all_unknown_tables() {
		
		$blogs = Hub_Helper::get_all_blogs();
		
		if ( !isset($blogs['unknown']) || empty($blogs['unknown']['unknown']['tables']) ) return false;

		$tables = $blogs['unknown']['unknown']['tables'];

		self::delete_db_tables( $tables );

		return true;
	}

	/**
	 * Delete Tables from database.
	 * 
	 * @param array $tables		Array of Table names to delete
	 */
	public static function delete_db_tables( $tables ) {

		if (count($tables) == 0) {
			Log::log_abort(__("No tables selected.", 'greyd_hub'), 'delete');
			return;
		}
		
		// open logging overlay
		Log::log_start( "delete loading" );
		
		$queries = array();
		foreach ($tables as $table) {
			$queries[] = "DROP TABLE IF EXISTS ".$table;
		}
		// debug($queries);

		// import queries
		$result = SQL::import_queries($queries);
		
		// result message
		if ($result == "success") {
			Log::log_message("<h5>".__("All selected tables have been successfully deleted.", 'greyd_hub')."</h5>", 'success', true);
			echo "<script>var trigger_log_overlay = [true, {'type': 'default', 'css': 'delete success', 'id': 'log'}];</script>";
		} 
		else if ($result == "danger") {
			Log::log_message("<h5>".__("There were errors deleting the tables.", 'greyd_hub')."</h5>", 'danger');
			echo "<script>var trigger_log_overlay = [true, {'type': 'default', 'css': 'delete fail', 'id': 'log'}];</script>";
		}
		
		// close overlay
		Log::log_end();
	}

	/**
	 * Import Tables from File.
	 * 
	 * @param object $input		The form file input
	 */
	public static function import_db_tables( $input ) {

		// get file input details
		$file = $input['tmp_name'];
		$filename = $input['name'];
		
		// render logging overlay
		Log::log_start();
		Log::log_message("<h5>".sprintf(__("Import of the database on the domain is starting. (File: %s)", 'greyd_hub'), $filename)."</h5>", 'info');
		
		// read queries
		$queries = SQL::parse_file($file);
		if (!$queries) {
			Log::log_abort( __("Importing the database was aborted, the file could not be read.", 'greyd_hub') );
			return;
		}

		// prepare queries for import
		$tables = array();
		foreach ($queries as $query) {
			if (strpos($query, "(ERROR) ") === false && strpos($query, "SET ") !== 0) {
				$tmp = explode("(", $query);
				$command = $tmp[0];
				// debug($command);
				$tmp = explode("`", $command);
				if (count($tmp) < 2) {
					// debug("no table name");
					continue;
				}
				$tbl = $tmp[1];
				$tables[] = "`".$tbl."`";
			}
		}
		$tables = array_unique($tables);

		$final = array();
		foreach ($tables as $table) {
			$final[] = "DROP TABLE IF EXISTS ".$table;
		}
		foreach ($queries as $query) {
			if (strpos($query, "(ERROR) ") === false && strpos($query, "SET ") !== 0)
				$final[] = $query;
		}
		
		// import final queries
		$result = SQL::import_queries($final);
		
		// result message
		if ($result == "danger") {
			Log::log_message("<h5>".__("There were errors when importing the database. Details can be found in the individual logs.", 'greyd_hub')."</h5>", 'danger');
			echo "<script>var trigger_log_overlay = [true, {'type': 'default', 'css': 'import fail', 'id': 'log'}];</script>";
		} 
		else {
			if ($result == "info") {
				Log::log_message("<h5>".__("Some unsupported database commands were found during the import on the domain. These were skipped.", 'greyd_hub')."</h5>", 'info');
			} 
			else if ($result == "success") {
				Log::log_message("<h5>".__("All selected tables have been successfully imported.", 'greyd_hub')."</h5>", 'success');
			}
			echo "<script>var trigger_log_overlay = [true, {'type': 'default', 'css': 'import success', 'id': 'log'}];</script>";
		}

		// close overlay
		Log::log_end();
	}

}