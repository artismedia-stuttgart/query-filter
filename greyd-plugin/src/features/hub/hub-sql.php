<?php
/**
 * Greyd.Hub SQL utility functions.
 */
namespace Greyd\Hub;

if ( !defined( 'ABSPATH' ) ) exit;

class SQL {


	/*
	=======================================================================
		Import SQL
			sqlImport -> SQL::import_queries
	=======================================================================
	*/

	/**
	 * Import SQL queries to Database.
	 * 
	 * @param array $queries    Array of sql-queries.
	 * @param bool $log			Trigger logs (default: true).
	 * @return string $result   success, info or danger
	 */
	public static function import_queries( $queries, $log=true ) {
		
		global $wpdb;

		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		set_time_limit(apply_filters( 'greyd_hub_process_set_timeout', 3600 ));

		/**
		 * Log progress in progress-bar (default: false)
		 * @filter 'greyd_hub_process_log_progress'
		 */
		$log_progress = apply_filters( 'greyd_hub_process_log_progress', false );

		if ($log) $id = Log::log_progress_start("Importing Database Tables ...");

		$result = "success";

		foreach ($queries as $i => $query) {
			if ($log && $log_progress) Log::log_progress( $id, count($queries), $i+1 );
			//debug($query);
			if (preg_match('/^\s*(drop|create|insert|alter|replace)\s/i', $query)) {
				$tmp = explode("(", $query);
				$q = $wpdb->query($query);
				if ($q !== false) {
					if ($log && !$log_progress) Log::log_message($tmp[0]." (".$q." SQL-operations done)", 'success', true);
				}
				else {
					if ($log) Log::log_message($tmp[0]." <strong>(SQL-command failed)</strong><pre><small>".htmlentities($query)."</small></pre>".$wpdb->last_error, 'danger', true);
					$result = "danger";
				}
			}
			else if (strpos($query, "(ERROR) ") === 0) {
				$tmp = explode("(", str_replace("(ERROR) ", "", $query));
				if ($log) Log::log_message($tmp[0]." <strong>(unsupported SQL-command)</strong>", 'info', true);
				if ($result != "danger") $result = "info";
			}
			else if (strpos($query, "SET ") !== 0) {
				if ($log) Log::log_message(substr($query, 0, 20)." ... <strong>(unrecognized SQL-command)</strong>", 'danger', true);
				$result = "danger";
			}
		}

		if ($log) Log::log_message(__("Queries inserted.", 'greyd_hub'), 'debug');

		return $result;
	}


	/*
	=======================================================================
		Read SQL
			sqlReadFile -> SQL::parse_file
	=======================================================================
	*/

	/**
	 * Parse SQL queries from file.
	 * Allowed commands: SET, DROP TABLE, ALTER TABLE, CREATE TABLE, INSERT INTO
	 * Other commands are escaped with (ERROR) but stil added to the final queries
	 * 
	 * @param string $sql_file  Full path to the sql file.
	 * @param bool $log         Trigger logs (default: false).
	 * @return array $queries   Array of single sql-queries (or false).
	 */
	public static function parse_file( $sql_file, $log=false ) {

		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		set_time_limit(apply_filters( 'greyd_hub_process_set_timeout', 3600 ));

		// open file
		$file = fopen($sql_file, 'r');
		if (!$file) return false;

		if ( $log ) {
			// get length of file (number of rows)
			$test = new \SplFileObject($sql_file, 'r');
			$test->seek(PHP_INT_MAX);
			$max = $test->key();
			$i = 0;
			$id = Log::log_progress_start("Reading Database Tables ...");
		}

		$delimiter = ";";
		$isFirstRow = true;
		$isMultiLineComment = false;
		$sql = '';
		$queries = [];
		$closeQuery = false;

		while (!feof($file)) {
			if ( $log ) {
				// log progress
				Log::log_progress( $id, $max, $i+1 );
				$i++;
			}
			// get row data
			$row = fgets($file);
			// remove BOM for utf-8 encoded file
			if ($isFirstRow) {
				$row = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $row);
				$isFirstRow = false;
			}
			// 1. ignore empty string and comment row
			if (trim($row) == '' || preg_match('/^\s*(--\s)/sUi', $row)) {
				continue;
			}
			// 2. clear comments
			self::clear_comments($row, $isMultiLineComment);
			// 3. parse delimiter row
			if (preg_match('/^DELIMITER\s+[^ ]+/sUi', $row)) {
				$delimiter = preg_replace('/^DELIMITER\s+([^ ]+)$/sUi', '$1', $row);
				continue;
			}
			// 4. separate sql queries by delimiter
			if (substr($row, strlen($row)-strlen($delimiter), strlen($delimiter)) == $delimiter) {
				$row = substr($row, 0, strlen($row)-strlen($delimiter));
				$closeQuery = true;
			}
			$sql = trim($sql.' '.$row);
			// 5. create query
			if ($closeQuery) {
				if (strpos($sql, "SET ") === 0 ||
					strpos($sql, "DROP TABLE ") === 0 ||
					strpos($sql, "ALTER TABLE ") === 0 ||
					strpos($sql, "CREATE TABLE ") === 0 ||
					strpos($sql, "INSERT INTO ") === 0) {
					$queries[] = $sql;
				}
				else {
					$words = explode(" ", $sql);
					if (mb_strtoupper($words[0]) == $words[0] &&
						mb_strtoupper($words[1]) == $words[1]) {
						// probably sql command but not supported!
						$queries[] = "(ERROR) ".$sql;
					}
					else if (strpos($sql, "/*") === 0) {
						// skip commented command
					}
					else $queries[count($queries)-1] .= $delimiter.$sql;
				}
				$sql = '';
				$closeQuery = false;
			}
		}
		if (strlen($sql) > 0) {
			$queries[] = $sql;
		}
		fclose($file);

		if (count($queries) == 0) {
			return false;
		}
		else {
			if ($log) Log::log_message(__("Queries parsed.", 'greyd_hub'), 'debug');
			return $queries;
		}
	}

	/**
	 * Remove comments from sql.
	 */
	public static function clear_comments( &$sql, &$isMultiComment ) {
		if ($isMultiComment) {
			if (preg_match('#\*/#sUi', $sql)) {
				$sql = preg_replace('#^.*\*/\s*#sUi', '', $sql);
				$isMultiComment = false;
			} 
			else {
				$sql = '';
			}
			if (trim($sql) == '') {
				return $sql;
			}
		}
		$offset = 0;
		while (preg_match('{[^!]--\s|/\*[^!]}sUi', $sql, $matched, PREG_OFFSET_CAPTURE, $offset)) {
			list($comment, $foundOn) = $matched[0];
			if (self::is_content($foundOn, $sql)) {
				$offset = $foundOn + strlen($comment);
			} 
			else {
				if (substr($comment, 0, 2) == '/*') {
					$closedOn = strpos($sql, '*/', $foundOn);
					if ($closedOn !== false) {
						$sql = substr($sql, 0, $foundOn) . substr($sql, $closedOn + 2);
					} 
					else {
						$sql = substr($sql, 0, $foundOn);
						$isMultiComment = true;
					}
				} 
				else {
					$sql = substr($sql, 0, $foundOn);
					break;
				}
			}
		}
		$sql = trim($sql);
		
		/**
		 * don't return string, $sql is passed by reference
		 */
		// return $sql;
	}

	/**
	 * Check if "offset" position is inside row content.
	 */
	public static function is_content( $offset, $text ) {
		$isBraced = self::is_braced($offset, $text);
		if ($isBraced) return true;
		$isQuoted = self::is_quoted($offset, $text);
		if ($isQuoted) return true;
		$isQuoted2 = self::is_quoted($offset, $text, '"');
		if ($isQuoted2) return true;
		return false;
	}

	/**
	 * Check if "offset" position is quoted.
	 */
	public static function is_quoted( $offset, $text, $quote="'" ) {
		if ($offset > strlen($text))
			$offset = strlen($text);
		$isQuoted = false;
		for ($i = 0; $i < $offset; $i++) {
			if ($text[$i] == $quote) $isQuoted = !$isQuoted;
			if ($text[$i] == "\\" && $isQuoted) $i++;
		}
		return $isQuoted;
	}

	/**
	 * Check if "offset" position is braced.
	 */
	public static function is_braced( $offset, $text ) {
		if ($offset > strlen($text))
			$offset = strlen($text);
		$isBraced = 0;
		for ($i = 0; $i < $offset; $i++) {
			if ($text[$i] == "{") $isBraced++;
			if ($text[$i] == "}") $isBraced--;
		}
		return ($isBraced > 0) ? true : false;
	}


	/*
	=======================================================================
		Create SQL
			sqlCreate -> SQL::create_file/clean_tables/render_content
	=======================================================================
	*/

	/**
	 * Create SQL Content to save into file.
	 * 
	 * @param array $tables     Array of Tablenames to write.
	 * @param object $replace
	 *      @property array old     Array of old data to replace
	 *      @property array new     Array of new data to replace
	 * @param bool $clean       Clean Tables
	 * @param bool $log         Trigger logs (default: false).
	 */
	public static function create_file( $tables, $replace, $clean, $log=false ) {
		global $wpdb;

		if ($log) $id = Log::log_progress_start("Preparing Database Tables ...");

		// get tables data
		$content = self::render_head();
		$to_clean = array();
		foreach ($tables as $i => $table) {
			// debug($table);
			if ($log) Log::log_progress( $id, count($tables), $i+1 );

			$target_table = array( (object)array(
				'name' => (current((Array)$table)),
				'rows' => $wpdb->get_results('SELECT * FROM `'.$table.'`')
			) );
			if ( $clean && (strpos($table, '_posts') > 0 || strpos($table, '_postmeta') > 0) ) {
				// save posts and postmeta tables to clean after foreach loop
				if ( empty($to_clean) ) $content .= '__insert_posts_and_meta_here__';
				$to_clean[] = $target_table[0];
			}
			else {
				// clean, render and replace data strings in one pass
				if ( $clean) $target_table = self::clean_tables($target_table);
				if ( count($target_table) > 0 ) {
					$target_table = self::render_table($target_table[0]);
					self::replace_data($target_table, $replace);
					$content .= $target_table;
				}
			}
			unset($target_table);
		}
		// debug($to_clean);
		if ( $clean && !empty($to_clean) ) {
			$to_clean = self::clean_tables($to_clean);
			$to_clean = self::render_table($to_clean[0]).self::render_table($to_clean[1]);
			self::replace_data($to_clean, $replace);
			$content = str_replace( '__insert_posts_and_meta_here__', $to_clean, $content );
			unset($to_clean);
		}

		if ($log) Log::log_message(__("Database Tables prepared.", 'greyd_hub'), 'debug');

		return $content;
	}

	/**
	 * Clean the Tables Data before rendering.
	 * 
	 * @param array $tables
	 *      @property string name   Table name
	 *      @property array rows    Table rows
	 * @return array $clean_tables
	 */
	public static function clean_tables( $tables ) {

		// cleaned tables array
		$clean_tables = array();
		$saved_posts = array();

		// scan table data
		foreach ($tables as $table) {

			// skip full tables
			if (
				strpos($table->name, 'yoast_') > 0 ||
				strpos($table->name, 'icl_') > 0 ||
				strpos($table->name, 'actionscheduler_') > 0 ||
				strpos($table->name, 'borlabs_cookie_') > 0
			) {
				continue;
			}
			
			// clean table rows
			$clean_rows = array();
			foreach ($table->rows as $row) {
				// clean options table
				if (strpos($table->name, '_options') > 0 && isset($row->option_name)) {
					// set generic admin email
					if ($row->option_name == 'admin_email') $row->option_value = Admin::$clean->admin;
					if ($row->option_name == 'new_admin_email') continue;
					// skip options
					$orphans = array( 
						'wpseo', 'wpcf7', 
						'rs-library', 'rs_tables_created', 'widget_rev-slider-widget', 'rs_cache_overlay',
						'ultimate_row', 'ultimate_animation', 'ultimate_google_fonts', 'ultimate_selected_google_fonts', 'ultimate_updater', 'ultimate_constants', 'ultimate_modules',
						'bsf-updater-version', 'bsf_local_transient', 'bsf_local_transient_bundled', 'brainstrom_products', 'brainstrom_bundled_products',
						'wpb_js_composer_license_activation_notified', 'wpb_js_templates', 'wpb_js_gutenberg_disable', 'vc_version',
						'wpmm_settings', 'wpmm_version',
						'tadv_settings', 'tadv_admin_settings', 'tadv_version',
						'recently_edited', 'recently_activated', 'uninstall_plugins', 
						'gtpl', 'greyd_forms_interface_settings', 'gc_connections'
					);
					if (in_array($row->option_name, $orphans)) continue;
					if ($row->option_name == 'rewrite_rules') continue; // flush_rewrite_rules(false) on import
					if ($row->option_name == 'cron') continue; // test if crons repopulate
					if (strpos($row->option_name, 'external_updates') !== false) continue; // test
					if (strpos($row->option_name, 'autoptimize_') !== false) continue; // maybe
					if (strpos($row->option_name, 'icl_') !== false) continue; // maybe
					if (strpos($row->option_name, 'yoast_') !== false) continue; // maybe
					if (strpos($row->option_name, 'widget_') !== false) continue;
					if (strpos($row->option_name, 'transient_') !== false) continue;
					if (strpos($row->option_name, '_user_roles') !== false) {
						$roles = unserialize($row->option_value);
						if ( is_array($roles) ) foreach ($roles as $role_key => $role) {
							if ( isset($role['capabilities']) ) foreach ($role['capabilities'] as $cap => $cap_value) {
								if (strpos($cap, 'nf_') !== false || 
									strpos($cap, 'wf2fa_') !== false || 
									strpos($cap, 'wpml_') !== false || 
									strpos($cap, 'mailpoet_') !== false || 
									strpos($cap, 'vc_') !== false) {
									unset($roles[$role_key]['capabilities'][$cap]);
								}
							}
						}
						// debug($roles);
						$row->option_value = serialize($roles);
					}
					if (strpos($row->option_name, 'theme_mods_') !== false) {
						if ($row->option_name != 'theme_mods_'.get_option('stylesheet')) continue;
					}
					// debug($row);
				}
				// clean postmeta table
				if (strpos($table->name, '_postmeta') > 0 && isset($row->meta_key) ) {
					// skip postmeta
					$orphans = array( 
						'_edit_lock', '_edit_last', '_wpb_vc_js_status', '_wpb_shortcodes_custom_css',
						'greyd_forms_entry_state', 'tp_token', 'tp_host', 'tp_form_id',
						'_customize_changeset_uuid'
					);
					if (in_array($row->meta_key, $orphans)) continue;
					if (strpos($row->meta_key, '_wpml_') !== false) continue; // maybe
					if (strpos($row->meta_key, 'wpseo_') !== false) continue; // test
					if (strpos($row->meta_key, '_wp_trash') !== false) continue;
					if (strpos($row->meta_key, '_oembed') !== false) continue;
				}
				// clean posts table
				if (strpos($table->name, '_posts') > 0 && isset($row->post_type)) {
					// skip posts
					$orphans = array( 
						'revision', 'tp_forms_entry', 'customize_changeset', 'oembed_cache'
					);
					if (in_array($row->post_type, $orphans)) continue;
					if (isset($row->post_status)) {
						if ($row->post_status == 'trash' || $row->post_status == 'auto-draft') continue;
					}
					array_push($saved_posts, $row->ID);
				}
				// empty comments table
				if (strpos($table->name, '_comments') > 0) {
					// skip all comments
					continue;
				}
				// add to cleaned
				array_push($clean_rows, $row);
			}

			// add to cleaned
			$clean_tables[] = (object)array(
				'name' => $table->name,
				'rows' => $clean_rows
			);
		}

		// clean postmeta table
		foreach ($clean_tables as $table) {
			if (strpos($table->name, '_postmeta') > 0) {
				// save postmeta only for valid posts
				$postmeta_rows = array();
				foreach ($table->rows as $row) {
					if (in_array($row->post_id, $saved_posts))
						array_push($postmeta_rows, $row);
				}
				$table->rows = $postmeta_rows;
			}
		}
		
		return $clean_tables;
	}

	/**
	 * Render the SQL content from the tables.
	 * 
	 * @param array $tables
	 *      @property string name   Table name
	 *      @property array rows    Table rows
	 * @return string $content
	 */
	public static function render_content( $tables ) {
		// render head
		$content = self::render_head();
		// render tables
		foreach ($tables as $table) {
			$content .= self::render_table($table);
		}
		return $content;
	}
	public static function render_head() {
		global $wpdb;

		// render sql file head
		// https://stackoverflow.com/questions/22195493/export-mysql-database-using-php-only
		$content  = "-- TP SQL Dump\n";
		$content .= "-- version: 1.0\n";
		$content .= "-- date: ".date("Y-m-d H:i:s")."\n";
		$content .= "-- database: ".$wpdb->dbname."\n\n";
		$content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET time_zone = \"+00:00\";\n\n";

		return $content;
	}
	public static function render_table( $table ) {
		global $wpdb;

		// render table dev
		$table_name = $table->name;
		$resQ       = $wpdb->get_results('SHOW CREATE TABLE `'.$table_name.'`');
		$TableMLine = $resQ[0]->{'Create Table'};
		$content    = "-- --------------------------------------------------------\n\n";
		$content   .= "--\n-- Tablestructure for `".$table_name."`\n--\n";
		$content   .= "\nDROP TABLE IF EXISTS `".$table_name."`;";
		$content   .= "\n".$TableMLine.";\n\n";
		$content   .= "--\n-- Data for `".$table_name."`\n--\n";

		// $rows       = $table->rows;
		$rows_num	= count($table->rows);
		if ($rows_num > 0) {
			// render table data
			$fields	= array();
			foreach ($table->rows[0] as $key => $value) {
				$fields[] = $key;
			}
			$fields_amount = count($fields);

			$st_counter = 0;
			foreach ($table->rows as $row) {
				// when started and after every 100 command cycles:
				if ( $st_counter%100 == 0 || $st_counter == 0 ) {
					$content .= "\nINSERT INTO `".$table_name."` (";
					$count = 0;
					foreach ($fields as $field) {
						$content .= "`".$field."`";
						if ($count < ($fields_amount-1)) $content .= ", ";
						$count++;
					}
					$content .= ") VALUES";
				}
				$content .= "\n(";
				$count = 0;
				foreach ((array)$row as $field) {
					// debug($field);
					$field = addslashes( $field ?? "" );
					$field = str_replace("\r","\\r", $field); 
					$field = str_replace("\n","\\n", $field); 
					if (isset($field)) $content .= '"'.$field.'"';
					else $content .= '""';
					if ($count < ($fields_amount-1)) $content .= ", ";
					$count++;
				}
				$content .= ")";
				// after every 100 command cycles and at last line
				if ( ( ($st_counter+1)%100 == 0 && $st_counter != 0 ) || $st_counter+1 == $rows_num ) $content .= ";";
				else $content .= ",";

				$st_counter++;
				// debug($content);
			} 
		}
		$content .= "\n\n";

		return $content;
	}

	
	/*
	=======================================================================
		Modify SQL
			readFileSQL -> SQL::mod_queries
	=======================================================================
	*/

	/**
	 * Modify SQL queries.
	 * 
	 * @param array $queries    Array of sql-queries.
	 * @param object $queries   Queries object (passed by reference)
	 * 		@property bool|string error		False or Error string.
	 * 		@property array queries			array of sql queries.
	 * 		@property array new_data		New data used for modification.
	 * 		@property array plugins_missing	List of missing plugins in new blog.
	 * @param int $blogid		New Blog ID.
	 * @param bool $log			Trigger logs (default: true).
	 * @return void
	 */
	public static function mod_queries( &$queries, $blogid, $log=true ) {
		global $wpdb;

		// old data
		$data_error = false;
		$plugins_missing = array();
		$old_data = array(
			'domain' => "",
			'http' => "",
			'admin' => "",
			'prefix' => "",
			'id' => "",
			'tables' => array()
		);

		// get default values
		$old_blogid = "";
		$old_prefix = "";

		if ($log) $id = Log::log_progress_start("Preparing Database Tables ...");

		// 1. scan queries to get old data and errors - no modifications
		foreach ($queries['queries'] as $i => $query) {

			if ($log) Log::log_progress( $id, count($queries['queries'])*2, $i+1 );

			// only valid query
			if ( strpos($query, "(ERROR) ") === false && strpos($query, "SET ") !== 0 ) {

				// check for valid wp tablename
				$tmp = explode("(", $query);
				$command = $tmp[0];
				$tmp = explode("`", $command);
				if (count($tmp) < 2) {
					// debug("no table name");
					if ($log) Log::log_message($command." (".__("No table name found.", 'greyd_hub').")", 'danger', true);
					$data_error = true;
					continue;
				}
				$tbl = $tmp[1];
				$old_data['tables'][] = "`".$tbl."`";
				$tmp = explode("_", $tbl);
				if (count($tmp) < 2) {
					// debug("no wp table");
					if ($log) Log::log_message($command." (".__("Table is not a WordPress table.", 'greyd_hub').")", 'danger', true);
					$data_error = true;
					continue;
				}

				/**
				 * First, let's get the prefix and blogid by reading the commentmeta table.
				 * 
				 * @since 2.15.0
				 * This was changed from looking for the prefix by using the wp_options table.
				 * Resons for this are:
				 * - The wp_options table is never the first table in the SQL file.
				 *   (usually sorted alphabetically by table name -> letter 'o')
				 * - There could be different third party plugins that use a custom options
				 *   table, which could lead to wrong prefix and blogid, eg. 'wp_gdpr_options'
				 * - The commentmeta table is always created very early in the SQL file and
				 *   it is a lot less likely that a similar table is created by a third party plugin
				 *   that is included before the commentmeta table.
				 */
				if (
					empty( $old_prefix )
					&& strpos($command, "CREATE TABLE ") === 0
					&& strpos($command, "commentmeta") !== false
				) {
					// debug("found commentmeta ...");

					/**
					 * Get the wp tables prefix & blogid from SQL file.
					 * 
					 * @example:
					 * (1) "INSERT INTO `wp_commentmeta` "                  Default single site.
					 * (2) "INSERT INTO `wp_10_commentmeta` "               Default multisite.
					 * (3) "INSERT INTO `wpcustom_10_commentmeta` "         Custom prefix.
					 * (4) "INSERT INTO `wp_a123456_10_commentmeta` "       Custom prefix with underscore.
					 * (5) "INSERT INTO `wp_11_wpfront_ure_commentmeta` "   Custom commentmeta table.
					 */
					$tmp = explode('commentmeta`', $query);
					if (count($tmp) > 1) {

						/**
						 * (1) --> wp
						 * (2) --> wp_10
						 * (3) --> wpcustom_10
						 * (4) --> wp_a123456_10
						 * (5) --> wp_11_wpfront_ure
						 */
						$string_before_commentmeta = str_replace("CREATE TABLE `", "", $tmp[0]);

						// get default values
						$old_blogid = "1";
						// $old_prefix = $string_before_commentmeta."_";
						$old_prefix = $string_before_commentmeta;

						// the string has an underscore
						// --> see if it includes prefix & blogid
						if (strpos($string_before_commentmeta, '_') !== false) {
							// match for blog id & prefix
							if ( preg_match('/^(?<prefix>[a-z-_\d]+_)(?<blogid>\d+)(?:_|$)/i', $string_before_commentmeta, $matches) ) {
								if ( $matches && isset($matches['blogid']) && isset($matches['prefix']) ) {
									$old_blogid = strval($matches['blogid']);
									$old_prefix = strval($matches['prefix']);
								}
							}
						}

						// set prefix and blogid
						if ($old_data['prefix'] == "" && $old_data['id'] == "") {
							// debug("setting: base: ".$old_prefix." blogid: ".$old_blogid);
							$old_data['prefix'] = $old_prefix;
							$old_data['id'] = $old_blogid;
						}
						else if ( $old_prefix != $old_data['prefix'] && strpos( $old_prefix, $old_data['prefix'] ) !== 0 ) {
							// debug("wrong wp table prefix or blog id");
							if ($log) Log::log_message($command." (".__("The table has an incorrect prefix or blog ID.", 'greyd_hub').")", 'danger', true);
							$data_error = true;
							continue;
						}
					}
				}

				/**
				 * Once we have the prefix, we can search for the options table
				 * and get the domain, http and admin email.
				 * 
				 * @since 2.15.0
				 */
				if ( !empty($old_prefix) ) {

					$wp_options_table_name = ( empty( $old_blogid ) || $old_blogid < 2 ) ? $old_prefix."options" : $old_prefix.$old_blogid."_options";

					// find the wp_options table
					if ( strpos($command, "INSERT INTO ") === 0 && strpos($command, $wp_options_table_name) !== false ) {
						// debug("found options with table name: ".$wp_options_table_name);
						// if ($log) Log::log_message(sprintf(__("Found the options table %s.", 'greyd_hub'), "<strong>".$wp_options_table_name."</strong>"), 'info');
						
						// search home url
						$tmp = explode('"home"', $query);
						if (count($tmp) > 1) {
							$tmp = explode(",", $tmp[1]);
							$siteurl = trim(trim(trim($tmp[1]), "'"), '"');
							// debug("siteurl: ".$siteurl);
							$tmp = explode("://", $siteurl);
							$old_data['domain'] = $tmp[1];
							$old_data['http'] = $tmp[0];
							$old_data['upload_url'] = $siteurl."/wp-content/uploads";
						}
	
						// search admin_email
						$tmp = explode('"admin_email"', $query);
						if (count($tmp) > 1) {
							$tmp = explode(",", $tmp[1]);
							$admin_email = trim(trim(trim($tmp[1]), "'"), '"');
							if (strpos($admin_email, "@") !== false) {
								// debug("admin_email: ".$admin_email);
								$old_data['admin'] = $admin_email;
							}
						}
	
						// check for active plugins
						$tmp = explode('"active_plugins"', $query);
						if (count($tmp) > 1) {
							$tmp = explode(",", $tmp[1]);
							$active_plugins = trim(trim(trim($tmp[1]), "'"), '"');
							// debug($active_plugins);
							preg_match_all('/(\\\")(.*?)(\\\")/', $active_plugins, $matches);
							if (count($matches[2]) > 0) {
								foreach ($matches[2] as $plugin_file) {
									// debug(WP_PLUGIN_DIR."/".$plugin_file);
									if (!file_exists(WP_PLUGIN_DIR."/".$plugin_file)) {
										$plugin_missing = explode("/", $plugin_file);
										$queries['plugins_missing'][] = $plugin_missing[0];
										// uncomment to make fatal error
										// if ($log) Log::log_message(sprintf(__("Das Plugin %s wurde auf der Zielinstallation nicht gefunden und muss manuell installiert werden.", 'greyd_hub'), "<strong>".$plugin_missing[0]."</strong>"), 'danger');
										// $data_error = true;
									}
								}
							}
						}
					}
				}
			}
		}

		// 2. check for errors in old data
		// debug($old_data);
		if ( 
			$data_error
			|| empty($old_data['prefix'])
			|| $old_data['id'] == ""
			|| empty($old_data['domain'])
			|| empty($old_data['http'])
			// || empty($old_data['admin'])
		) {
			$queries['error'] = __("Importing the database was aborted, the file contains errors.", 'greyd_hub');
			return;
		}
		
		// 3. get new data from $blogid
		if (is_multisite()) switch_to_blog($blogid);
			$new_siteurl = explode("://", get_bloginfo('url'));
			if (strpos($new_siteurl[1], '?') > 0) 
				$new_siteurl[1] = trim(explode('?', $new_siteurl[1])[0], '/');
			$queries['new_data'] = array(
				'domain' => $new_siteurl[1],
				'http' => $new_siteurl[0],
				'admin' => get_bloginfo('admin_email'),
				'prefix' => $wpdb->base_prefix,
				'id' => $blogid,
				'tables' => array(),
			);
			$save_options = array(
				'siteurl' => get_option('siteurl', array()),
				'gtpl' => get_option('gtpl', array()),
				'gc_connections' => get_option('gc_connections', array()),
			);
			$current_plugins = get_option('active_plugins', array());
		if (is_multisite()) restore_current_blog();
		
		// get all blogid tables
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
						$queries['new_data']['tables'][] = "`".$tbl."`";
				}
			}
		}
		$queries['new_data']['upload_url'] = wp_upload_dir()["baseurl"];

		$replace = array(
			'old' => $old_data,
			'new' => $queries['new_data']
		);

		$tables = $queries['new_data']['tables'];
		$old_data['tables'] = array_unique($old_data['tables']);
		foreach ($old_data['tables'] as $table) {
			self::replace_data($table, $replace);
			$tables[] = $table;
		}
		$tables = array_unique($tables);
		
		// debug($tables);
		// debug("<strong>affected tables:</strong>");
		// debug($queries['new_data']);
		// debug("<strong>blog data:</strong>");
		// debug($old_data);
		// debug("<strong>sql file data:</strong>");
		// debug($save_options);
		// debug("<strong>save_options:</strong>");
		// debug($replace);
		// debug("<strong>replace data:</strong>");
		// return array( 'error' => 'debug' );

		// 4./5. scan queries, modify data and add to final
		foreach ($queries['queries'] as $i => $query) {

			if ($log) Log::log_progress( $id, count($queries['queries'])*2, count($queries['queries'])+$i+1 );

			// modify strings per table
			if (strpos($query, "INSERT INTO ") === 0 && strpos($query, "_icl_translations") !== false) {
				/**
				 * Remove empty translations
				 * 
				 * @since 1.2.7
				 */
				$query = preg_replace(
					array(
						'/(, \(\"\d*\"\, \")(\w*?)(\"\, \"\"\, .*?\))/',
						'/((\, )?\(\"\d*\"\, \")(\w*?)(\"\, \"\"\, .*?\),?)/'
					),
					"",
					$query
				);
			}
			if (strpos($query, "INSERT INTO ") === 0 && strpos($query, "_options") !== false) {
				// debug("found options ...");
				if (strpos($query, "gc_connections") !== false || strpos($query, "gtpl") !== false) {
					// debug("found gc_connections or gtpl ...");
					preg_match_all('/(\(\"\d+?\",\s\"(?:gc_connections|gtpl).+?\"\),)/', $query, $matches);
					if (count($matches[0]) > 0) {
						foreach ($matches[0] as $remove) {
							// remove option
							$query = str_replace($remove, "", $query);
						}
					}
				}
				if (strpos($query, "active_plugins") !== false && !is_multisite()) {
					if (in_array('greyd_globalcontent/init.php', $current_plugins)) {
						preg_match_all('/(\(\"\d+?\",\s\"active_plugins",\s\"(.+?)\",\s\"yes\"\),)/', $query, $matches);
						if (count($matches[0]) > 0) {
							// debug($matches);
							foreach ($matches[0] as $i => $remove) {
								$plugins = str_replace('\\"', '"', $matches[2][$i]);
								$plugins = unserialize($plugins);
								if (!in_array('greyd_globalcontent/init.php', $plugins)) {
									// add globalcontent to active plugins
									array_push($plugins, 'greyd_globalcontent/init.php');
									$plugins = str_replace('"', '\\"', serialize($plugins));
									// debug($plugins);
									$query = str_replace($matches[2][$i], $plugins, $query);
								}
							}
						}
					}
				}
			}
			// add to final
			if (strpos($query, "(ERROR) ") === false && strpos($query, "SET ") !== 0) {
				// modify data on full query
				self::replace_data($query, $replace);
				$queries['queries'][$i] = $query;
			}
			else {
				unset($queries['queries'][$i]);
			}
		}

		// 6. re-create saved options
		if (isset($save_options)) {
			// table name
			$prefix = $queries['new_data']['prefix'];
			if ($queries['new_data']['id'] > 1) $prefix .= $queries['new_data']['id']."_";
			$options_table = "`".$prefix."options`";
			// create options from old data
			$options_queries = array();
			foreach ($save_options as $option => $value) {
				if (!empty($value)) {
					// debug("set option ".$option);
					// debug($value);
					$values = serialize($value);
					array_push($options_queries, "('{$option}', '{$values}')" );
				}
			}
			if (count($options_queries) > 0) {
				$queries['queries'][] = "REPLACE INTO {$options_table} (`option_name`, `option_value`) VALUES ".implode(', ', $options_queries).";";
			}
		}

		// add initial commands to front of queries
		foreach ($tables as $table) {
			array_unshift( $queries['queries'], "DROP TABLE IF EXISTS ".$table );
		}

		if ($log) Log::log_message(__("Queries modified.", 'greyd_hub'), 'debug');

		// debug($queries['queries']);
		// debug("<strong>final queries:</strong>");
		// return array( 'error' => 'debug' );

		/**
		 * don't return object, $queries is passed by reference
		 */
		// return array(
		// 	'error' => false,
		// 	'queries' => $final,
		// 	'new_data' => $new_data,
		// 	'plugins_missing' => $plugins_missing
		// );
	}


	/*
	=======================================================================
		Search and Replace SQL
			sqlReplace -> SQL::replace_data
	=======================================================================
	*/

	/**
	 * Replace data strings in Content.
	 * 
	 * @param string $content   The content string (passed by reference).
	 * @param object $replace
	 *      @property array old     Array of old data to replace
	 *      @property array new     Array of new data to replace
	 * @return void
	 */
	public static function replace_data( &$content, $replace ) {
		global $wpdb;
		
		$change = false;
		$old = $new = array(
			'prefix' => '`'.$wpdb->base_prefix,
			'prefix2' => '"'.$wpdb->base_prefix,
			'uploads' => 'uploads',
		);
		if (isset($replace['old']) && isset($replace['new'])) {
			// edit wp url
			if (isset($replace['old']['wp_url']) && isset($replace['new']['wp_url'])) {
				$old['wp'] = $replace['old']['wp_url'];
				$new['wp'] = $replace['new']['wp_url'];
				$change = true;
			}
			// edit uploads url
			if (isset($replace['old']['upload_url']) && isset($replace['new']['upload_url'])) {
				$old['uploads'] = $replace['old']['upload_url'];
				$new['uploads'] = $replace['new']['upload_url'];
				$change = true;
			}
			// edit title
			if (isset($replace['old']['title']) && $replace['old']['title'] != "" && 
				isset($replace['new']['title']) && $replace['new']['title'] != "") {
				$old['title'] = '"'.$replace['old']['title'].'"';
				$new['title'] = '"'.$replace['new']['title'].'"';
				$change = true;
			}
			// edit description
			if (isset($replace['old']['description']) && $replace['old']['description'] != "" && 
				isset($replace['new']['description']) && $replace['new']['description'] != "") {
				$old['description'] = '"'.$replace['old']['description'].'"';
				$new['description'] = '"'.$replace['new']['description'].'"';
				$change = true;
			}
			// edit domain
			if (isset($replace['old']['domain']) && $replace['old']['domain'] != "" && 
				isset($replace['new']['domain']) && $replace['new']['domain'] != "") {
				$old['domain'] = '://'.untrailingslashit($replace['old']['domain']);
				$new['domain'] = '://'.untrailingslashit($replace['new']['domain']);
				$change = true;
			}
			// edit admin
			if (isset($replace['old']['admin']) && $replace['old']['admin'] != "" && 
				isset($replace['new']['admin']) && $replace['new']['admin'] != "") {
				$old['admin'] = $replace['old']['admin'];
				$new['admin'] = $replace['new']['admin'];
				$change = true;
			}
			// edit prefix
			if (isset($replace['old']['prefix']) && $replace['old']['prefix'] != "") {
				$old['prefix'] = '`'.$replace['old']['prefix'];
				$old['prefix2'] = '"'.$replace['old']['prefix'];
				$new['prefix'] = '`'.$replace['old']['prefix'];
				$new['prefix2'] = '"'.$replace['old']['prefix'];
			}
			if (isset($replace['new']['prefix']) && $replace['new']['prefix'] != "") {
				$new['prefix'] = '`'.$replace['new']['prefix'];
				$new['prefix2'] = '"'.$replace['new']['prefix'];
				$change = true;
			}
			// edit blogid
			if (isset($replace['old']['id']) && $replace['old']['id'] != "" && 
				isset($replace['new']['id']) && $replace['new']['id'] != "") {
				if ($replace['old']['id'] > 1) {
					$old['prefix'] .= $replace['old']['id'].'_';
					$old['prefix2'] .= $replace['old']['id'].'_';
					$old['uploads'] .= '/sites/'.$replace['old']['id'];
				}
				if ($replace['new']['id'] > 1) {
					$new['prefix'] .= $replace['new']['id'].'_';
					$new['prefix2'] .= $replace['new']['id'].'_';
					$new['uploads'] .= '/sites/'.$replace['new']['id'];
				}
				$old['uploads'] .= '/';
				$new['uploads'] .= '/';
				$change = true;
			}
		}
		if ($change) {
			// debug($old);
			// debug($new);
			foreach ($old as $key => $old_value) {
				$new_value = $new[$key];
				if ($key == 'uploads') {
					// ignore https and http for uploads to catch mixed urls
					$old_value = str_replace( array( 'https:', 'http:' ), ':', $old_value );
					$new_value = str_replace( array( 'https:', 'http:' ), ':', $new_value );
					if ( isset($replace['old']['id']) && $replace['old']['id'] == 1 ) {
						// fix orphaned uploads urls, e.g. wrong blogid folder
						preg_match_all('/('.preg_quote($old_value, '/').'sites\/\d+\/)[^"]+/', $content, $matches);
						if ( $matches && !empty($matches[1]) ) {
							foreach ( array_unique($matches[1]) as $orphan ) {
								$content = Hub_Helper::str_replace_encode($orphan, $new_value, $content);
							}
						}
					}
					$content = Hub_Helper::str_replace_encode($old_value, $new_value, $content);
				}
				else if ($key == 'wp' || $key == 'domain') {
					$content = Hub_Helper::str_replace_encode($old_value, $new_value, $content);
				}
				else {
					$content = str_replace($old_value, $new_value, $content);
				}
			}
			// reset savewords
			$savewords_old = array(
				'page_for_privacy_policy',
				'inactive_widgets',
				'privacy_delete_old_export_files',
				'version_check',
				'update_plugins',
				'update_themes',
				'scheduled_delete',
				'scheduled_auto_draft_delete',
				'force_deactivated_plugins',
				// wp posttypes
				'block',
				'global_styles',
				'template',
				'template_part',
				'navigation',
				'font_family',
				'font_face',
				'theme'
			);
			$savewords_new = array();
			foreach ($savewords_old as $i => $saveword) {
				$savewords_old[$i] = $new['prefix2'].$saveword;
				$savewords_new[$i] = '"wp_'.$saveword;
			}
			$content = str_replace($savewords_old, $savewords_new, $content);
			// fix some errors and switch http/https
			if (isset($new['domain'])) {
				$domain_old = array( 'www.www', '@www.' );
				$domain_new = array( 'www', '@' );
				if (isset($replace['new']['http'])) {
					$http_old = $http_new = "http";
					if ($replace['new']['http'] == 'http') $http_old = "https";
					else if ($replace['new']['http'] == 'https') $http_new = "https";
					$domain_old[] = $http_old.$new['domain'];
					$domain_new[] = $http_new.$new['domain'];
				}
				$content = str_replace($domain_old, $domain_new, $content);
			}
			// debug($content);
		}

		/**
		 * don't return string, $content is passed by reference
		 */
		// return $content;
	}

}