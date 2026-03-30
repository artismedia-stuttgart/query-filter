<?php
/**
 * Greyd.Hub helper functions.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

class Hub_Helper {

	/*
	=======================================================================
		Helper
	=======================================================================
	*/

	/**
	 * check if Greyd.Suite is active.
	 * 
	 * @param string $name		slug/stylesheet of Theme.
	 * @return bool
	 */
	public static function greyd_suite_active($name) {
		// return (strpos($name, "Greyd.Suite") !== false) ? true : false;
		return (strpos($name, "greyd_suite") !== false) ? true : false;
	}

	/**
	 * check if Greyd Theme is active.
	 * 
	 * @param string $name		slug/stylesheet of Theme.
	 * @return bool
	 */
	public static function greyd_theme_active($name) {
		// return (strpos($name, "Greyd Theme") !== false) ? true : false;
		return (strpos($name, "greyd-theme") !== false || strpos($name, "greyd-wp") !== false) ? true : false;
	}

	/**
	 * Returns the current backend url (greyd hub link).
	 * 
	 * @return string $url
	 */
	public static function get_current_url() {
		return admin_url( str_replace( "wp-admin/", "", remove_query_arg("redirect_url") ) );
	}

	/**
	 * Get raw thememods from json.
	 * 
	 * @return string|bool	thememods object as json string or false.
	 */
	public static function get_raw_mods() {

		$mods_path = untrailingslashit( Admin::$urls->plugin_path ).'/assets/wizard/mods/raw.json';
		if ( !file_exists($mods_path) ) return false;

		return Helper::get_file_contents($mods_path);
	}


	/**
	 * search & replace url.
	 * 
	 * @param string $content   The content string.
	 * @param object $replace
	 *      @property array old     Array of old data to replace
	 *      @property array new     Array of new data to replace
	 * @return string $content  The changed content string.
	 */
	public static function url_replace($content, $replace) {
		if (isset($replace['old']) && isset($replace['new'])) {
			foreach( array( 'wp_url', 'upload_url', 'url' ) as $url ) {
				if (isset($replace['old'][$url]) && isset($replace['new'][$url])) {
					// debug($replace['old'][$url]);
					// debug($replace['new'][$url]);
					if ($replace['old'][$url] != $replace['new'][$url]) {
						// $content = str_replace($replace['old'][$url], $replace['new'][$url], $content);
						$content = self::str_replace_encode($replace['old'][$url], $replace['new'][$url], $content);
					}
				}
			}
		}
		return $content;
	}

	/**
	 * string replace encoded versions.
	 * 
	 * @param string $content       The content string.
	 * @param object $old_value     old string to replace
	 * @param object $new_value     new string to replace
	 * @return string $content  The changed content string.
	 */
	public static function str_replace_encode($old_value, $new_value, $content) {
		// encoded old values
		$old = array(
			// original value
			$old_value,
			// check if escaped value is different
			str_replace('/', '\\/', $old_value),
			// check if double escaped value is different
			str_replace('/', '\\\\/', $old_value),
			// check if encoded value is different
			urlencode($old_value),
			// check further encoding
			urlencode(urlencode($old_value))
		);

		// check if at least one encoded value is different
		if (
			$old_value !== $new_value ||
			$old_value !== $old[1] ||
			$old_value !== $old[2] ||
			$old_value !== $old[3] ||
			$old[2] !== $old[4]
		) {
			// encoded new values
			$new = array(
				$new_value,
				str_replace('/', '\\/', $new_value),
				str_replace('/', '\\\\/', $new_value),
				urlencode($new_value),
				urlencode(urlencode($new_value))
			);
			// only make str_replace when min one value is different
			return str_replace($old, $new, $content);
		}
		return $content;
	}


	/**
	 * Format bytes to readable string.
	 * 
	 * @param int $size         bytes to format.
	 * @param int $precision    precision rounding the value.
	 * 
	 * @return string           eg. 128MB
	 */
	public static function format_bytes( $size, $precision=2 ) {
		$base = log($size, 1024);
		$suffixes = array('Bytes', 'KB', 'MB', 'GB', 'TB');

		return round(pow(1024, $base - floor($base)), $precision) . '&thinsp;' . $suffixes[floor($base)];
	}

	/**
	 * Make domain name printable for filenames etc.
	 * 
	 * @param string $domain
	 * @return string
	 */
	public static function printable_domainname( $domain ) {
		return str_replace(
			array( "https://", "http://", "/" ),
			array( "", "", "-" ),
			untrailingslashit( $domain )
		);
	}


	/**
	 * check for database errors after import or in dashboard.
	 * 
	 * @param array $data
	 * 		@property string prefix		Database prefix.
	 * 		@property int id			Blog ID of the selected blog.
	 * 		@property array tables		List of tables to check.
	 * @return string $message			Log about repair or empty string.
	 */
	public static function check_db_errors($data) {

		// check input
		if ( !isset($data) || !is_array($data) || !isset($data['tables']) ) {
			return '';
		}

		global $wpdb;
		// debug($wpdb);

		$prefix = $data['prefix'];
		if (is_multisite()) $prefix .= $data['id']."_";
		$options_table = "`".$prefix."options`";
		$postmeta_table = "`".$prefix."postmeta`";

		// check options table
		$options = array();
		if ( in_array($options_table, $data['tables']) ) {
			$m = $wpdb->get_results(
				"SELECT option_name, option_value 
				   FROM {$options_table} 
				  WHERE option_value LIKE '%;s:%'", OBJECT );
			foreach ($m as $option) {
				$values = @unserialize($option->option_value);
				if (!$values) {
					// debug("corrupt option ".$option->option_name);
					$values = self::fix_value($option->option_value, $prefix);
					if ($values) {
						// debug($values);
						$wpdb->query(
							"UPDATE {$options_table}  
								SET option_value = '{$values}'
							  WHERE option_name = '{$option->option_name}'" );
						$options[] = $option->option_name;
					}
				}
			}
		}
		
		// check postmeta table
		$metas = array();
		if ( in_array($postmeta_table, $data['tables']) ) {
			$m = $wpdb->get_results(
				"SELECT meta_id, post_id, meta_key, meta_value 
				   FROM {$postmeta_table} 
				  WHERE meta_value LIKE '%;s:%'", OBJECT );
			foreach ($m as $meta) {
				$values = @unserialize($meta->meta_value);
				if(!$values) {
					// debug("corrupt meta ".$meta->meta_key." for post ".$meta->post_id);
					$values = self::fix_value($meta->meta_value, $prefix);
					if ($values) {
						// debug($values);
						$wpdb->query(
							"UPDATE {$postmeta_table}  
								SET meta_value = '{$values}'
							  WHERE meta_id = '{$meta->meta_id}'" );
						$metas[] = $meta->post_id;
					}
				}
			}
		}

		// return info if something has been fixed
		$message = "";
		if (count($options) > 0 || count($metas) > 0) {
			$message = '<h5>'.__("Database tables successfully repaired:", 'greyd_hub').'</h5>';
			if (count($options) > 0) {
				$message .= '<p>'.sprintf( _n( "Incorrect option with the name '%s' found and corrected.", "Incorrect options (%s) found and corrected.", count($options), 'greyd_hub' ), "<strong>".implode(", ", $options)."</strong>").'</p>';
			}
			if (count($metas) > 0) {
				$message .= '<p>'.sprintf( _n( "Incorrect post meta data with the name '%s' found and corrected.", "Incorrect post meta data (%s) found and corrected.", count($metas),  'greyd_hub' ), "<strong>".implode(", ", $metas)."</strong>").'</p>';
			}
		}
		return $message;
	}
	public static function fix_value($value, $prefix) {
		preg_match_all('/s:([0-9]+):"(\X*?)(";|"})/', $value, $r);
		// debug($r);
		if (is_array($r) && count($r[2]) > 0) {
			foreach ($r[2] as $i => $string) {
				if (strlen($string) != $r[1][$i]) {
					// debug("mismatch found at index ".$i);
					// debug("old: ".$r[0][$i]);
					if (strpos($string, $prefix) === 0) {
						$s = str_replace($prefix, "wp_", $string);
						$newstring = 's:'.strlen($s).':"'.$s.$r[3][$i];
					}
					else {
						$newstring = 's:'.strlen($string).':"'.$string.$r[3][$i];
					}
					// debug("new: ".$newstring);
					$value = str_replace($r[0][$i], $newstring, $value);
				}
			}
			try {
				$value = @unserialize($value);
				if ($value) {
					// escape "'"
					return str_replace("'", "\'", serialize($value));
				}
			} catch (Exception $e) {
				// catch some errors...
			}
		}
		return false;
	}


	/*
	=======================================================================
		Files Helper
	=======================================================================
	*/

	/**
	 * get verbose output of php upload errorcode.
	 * 
	 * @param int $error	The errorcode.
	 * @return string		Readable error description.
	 */
	public static function get_php_upload_error($error) {
		$php_upload_errors = array(
			1 => sprintf(
				__("The uploaded file exceeds the maximum file limit of the server (maximum %s). The limit is defined in the <u>php.ini</u> file.", 'greyd_hub'),
				self::format_bytes( wp_max_upload_size() )
			),
			2 => __("The uploaded file exceeds the allowed file size of the HTML form.", 'greyd_hub'),
			3 => __("The uploaded file was only partially uploaded.", 'greyd_hub'),
			4 => __("No file was uploaded.", 'greyd_hub'),
			6 => __("Missing a temporary folder.", 'greyd_hub'),
			7 => __("Failed to save the file.", 'greyd_hub'),
			8 => __("The file was stopped while uploading.", 'greyd_hub'),
		);
		if ( isset($php_upload_errors[$error]) ) return $php_upload_errors[$error];
		return $error;
	}

	/**
	 * Get uploaded file from raw $_FILES object.
	 * 
	 * @param string $inputname		Name of the file input holding the selected file.
	 * @param string $filetype		Type (extension) of the file input. (zip or sql or json)
	 * 
	 * @return array|bool
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 */
	public static function get_uploaded_file($inputname, $filetype) {

		// valid application types
		$application_types = array(
			'sql' => array(
				'application/octet-stream'
			),
			'zip' => array(
				'application/zip',
				'application/x-zip-compressed'
			),
			'json' => array(
				'application/json'
			)
		);

		// check file input
		if (empty($_FILES)) {
			Log::log_abort( __("No data found.", 'greyd_hub') );
			return false;
		}
		else if (count($_FILES) > 1) {
			Log::log_abort( __("Please select only one file.", 'greyd_hub') );
			return false;
		}
		else if (isset($_FILES[$inputname]) && intval($_FILES[$inputname]['error']) > 0) {
			Log::log_abort( self::get_php_upload_error( $_FILES[$inputname]['error'] ) );
			return false;
		}
		else if (!isset($_FILES[$inputname]) || !in_array($_FILES[$inputname]['type'], $application_types[$filetype]) ) {
			Log::log_abort( sprintf( __("Please select a valid %s file.", 'greyd_hub'), strtoupper($filetype) ) );
			return false;
		}

		return $_FILES[$inputname];
	}

	/**
	 * Get file from other Blog or url.
	 * 
	 * The source can be a backup file or a different blog.
	 * Different blog is either just other blogid oder also other (remote) network. 
	 * The files get dumped with current setup and copied in temp folder.
	 * Backup files are also copied in temp folder, since they will be modified on import.
	 * 
	 * @param string $source	Source of the file. e.g. "greydsuite.de|1" or abs url
	 * @param string $mode		Import Mode. (only for dumping file on other blog)
	 * @param bool $clean		Use cleaned-up dump. (only for dumping file on other blog)
	 * @param bool $log			Trigger logs (default: true).
	 * 
	 * @return array|bool
	 * 		@property string name		Filename.
	 * 		@property string tmp_name	Full url of File.
	 */
	public static function get_temp_file($source, $mode='', $clean=true, $log=true) {

		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		$timeout = apply_filters( 'greyd_hub_process_set_timeout', 3600 );
		
		// source is a blog at a different location
		// dump file there and get url
		if ( strpos($source, '|') > 0 ) {
			// from different location
			$route = explode('|', $source);
			$source = "";
			if ( !empty($mode) ) {
				if ($log) Log::log_message(__("Creating new contents", 'greyd_hub'), 'info');
				$old_blog_id = intval($route[1]);

				$other_nice_url   = self::get_nice_url($route[0]);
				$current_nice_url = self::get_nice_url(Admin::$urls->network_url);

				if ( $other_nice_url == $current_nice_url ) {
					// make dump here
					$source = self::dump_blog($old_blog_id, $mode, $clean);
				}
				else if (
					Admin::$connections
					&& isset( Admin::$connections[ $other_nice_url ] )
				) {
					// make dump there
					$connection = Admin::$connections[ $other_nice_url ];
					if ( method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' ) ) {
						$source = \Greyd\Connections\Connections_Helper::send_request(
							$connection,
							"remote_blogs/".$old_blog_id."/dump/".$mode,
							array( "clean" => $clean ),
							"GET",
							array( 'timeout' => $timeout )
						);
					}
					else {
						Log::log_abort( __("Connection feature needs to be enabled", 'greyd_hub') );
						return false;
					}
				}
			}
		}
		
		// return if source url is empty
		if ( !$source || $source == "" ) {
			Log::log_abort( __("No data found.", 'greyd_hub') );
			return false;
		}

		// make temp path
		$temp_path = Admin::$urls->backup_path."import";
		if (!file_exists($temp_path)) mkdir($temp_path, 0755, true);
		$temp_path .= "/";
		// make temp name
		$name = explode('/', $source);
		$name = $name[count($name)-1];
		// make temp copy of file
		$temp_name = $temp_path.$name;

		if ( $source == $temp_name ) {
			// file is already im tmp folder
			if ($log) Log::log_message(sprintf(__("Temporary file \"%s\" has been created.", 'greyd_hub'), $name), '');
			$success = true;
		}
		else {
			// copy file to temp folder
			if ($log) $id = Log::log_progress_start(sprintf(__("Creating temporary file \"%s\" ...", 'greyd_hub'), $name));
			$success = false;

			// try curl first
			$source_url = \Greyd\Helper::abspath_to_url( $source );
			if (
				function_exists("curl_init") &&
				function_exists("curl_exec") &&
				strpos($source_url, 'http') === 0
			) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				if ($log) {
					curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function( $resource, $download_size, $downloaded, $upload_size, $uploaded ) use ( $id ) {
						Log::log_progress( $id, $download_size, $downloaded );
					});
					curl_setopt($curl, CURLOPT_NOPROGRESS, false); // needed to make progress function work
					curl_setopt($curl, CURLOPT_BUFFERSIZE, 10*1024*1024);
				}
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
				curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // timeout in seconds
				curl_setopt($curl, CURLOPT_COOKIE, 'wordpress_logged_in');

				curl_setopt($curl, CURLOPT_FILE, fopen($temp_name, 'w'));
				curl_setopt($curl, CURLOPT_URL, \Greyd\Helper::abspath_to_url( $source ));
				
				if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) ) {
					$auth = base64_encode( $_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW'] );
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$auth));  
				}

				curl_exec($curl);
				if ( empty(curl_error($curl)) ) {
					$success = true;
				}
				else {
					// debug( curl_error($curl) );
					if ($log) Log::log_message(__("curl failed:", 'greyd_hub')."<br>".curl_error($curl), 'debug');
				}
				curl_close($curl);
			}

			if ( !$success ) {
				// get file from url and copy it to a temp folder
				$content = Helper::get_file_contents($source);
				if ( $content ) {
					file_put_contents($temp_name, $content);
					$success = true;
				}
			}
		}
		if ( $success ) {
			if ($log) Log::log_message(__("Installing new contents.", 'greyd_hub'), 'info');
			// return temp file
			return array(
				'name' => $name,
				'tmp_name' => $temp_name,
				'delete_tmp' => $temp_path
			);
		}
		else {
			Log::log_abort( __("File cannot be read.", 'greyd_hub') );
			return false;
		}
	}

	/**
	 * Get all backup files sorted by date.
	 * 
	 * @param bool $include_subfolder	
	 * @return object|null				List of files or null.
	 */
	public static function get_backup_files( $include_subfolder=false ) {
		
		$backup_path = untrailingslashit( Admin::$urls->backup_path );
		if ( !file_exists($backup_path) ) return null;

		$files = self::list_files_in_dir(array(), $backup_path);
		if ( count($files) === 0 ) return null;
		$files = array_reverse($files);

		// get file details
		$backup_files = [];
		foreach ($files as $file) {
			$name = str_replace(trailingslashit( $backup_path ), "", $file);
			if (strpos($name, '/') > 0 && !$include_subfolder) continue;

			// if is .htaccess
			if (strpos($name, ".htaccess") === 0) continue;

			// debug($name);
			$date = strval(date("ymd", filectime($file)));
			if (!isset($backup_files[$date])) $backup_files[$date] = array();
			$backup_files[$date][] = array(
				'file' => $file,
				'name' => $name,
				'url' => str_replace(trailingslashit( $backup_path ), Admin::$urls->backup_path_abs, $file),
				'date' => date_i18n(get_option( 'date_format' ), filectime($file))
			);
		}
		// sort files by date
		krsort($backup_files);

		return (object)$backup_files;
	}

	/**
	 * Check if backup path exists and add a .htaccess file to it.
	 */
	public static function check_backup_path() {

		$backup_path = untrailingslashit( Admin::$urls->backup_path );

		if ( !file_exists($backup_path) ) mkdir($backup_path, 0755, true);
		if ( !file_exists($backup_path."/.htaccess") ) {
			// debug("make htaccess file");
			$f = fopen($backup_path."/.htaccess", "a+");
			fwrite($f, "RewriteEngine On
						RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]
						RewriteRule ^(.*)$ - [R=403,L]");
			fclose($f);
		}
	}


	/*
	=======================================================================
		general files handling
			createFile -> make_zip
			downloadStart -> start_download
			deleteDir -> delete_directory
			deleteDirEx -> delete_directory
			listFolderFiles -> list_files_in_dir
	=======================================================================
	*/

	/**
	 * Store a folder with all files and subfolders in a zip inside the backup folder.
	 * Calls zip_folder but makes sure the zip is stored in the backup folder.
	 * 
	 * @param string $zip_name              Name of the new ZIP archive.
	 * @param string $folder                Path to the current folder.
	 * @param array|string $exclude_files   Path of files to exclude from the ZIP.
	 * @param array $include_files          Alternate to $exclude_files.
	 * 
	 * @return bool
	 */
	public static function make_zip( $zip_name, $folder, $exclude_files=[], $include_files=null ) {

		// check if backup folder exists
		self::check_backup_path();

		// create the zip file
		return self::zip_folder( Admin::$urls->backup_path.$zip_name, $folder, $exclude_files, $include_files );

	}

	/**
	 * Create a sql database file inside the backup folder.
	 * Makes sure the file is stored in the backup folder.
	 * 
	 * @param string $sql_name		Name of the new SQL file.
	 * @param array $tables			Array of Tablenames to write.
	 * @param object $replace
	 *      @property array old			Array of old data to replace
	 *      @property array new			Array of new data to replace
	 * @param bool $clean			Clean Tables
	 * @param bool $log				Trigger logs (default: false).
	 * 
	 * @return bool
	 */
	public static function make_sql( $sql_name, $tables, $replace, $clean, $log=false ) {
		
		// check if backup folder exists
		self::check_backup_path();

		// create the sql file
		$content = SQL::create_file($tables, $replace, $clean, $log);
		if ($content != "" && file_put_contents(Admin::$urls->backup_path.$sql_name, $content)) {
			return true;
		}
		return false;

	}

	/**
	 * Start download of a single file from backup folder.
	 * 
	 * @param string $file_name		Name of the file to download.
	 * @param string $domain
	 * @param string $class
	 */
	public static function start_download( $file_name, $domain, $class ) {

		$result = 'fail';
		if ( file_exists(Admin::$urls->backup_path.$file_name) ) {
			$url = Admin::$urls->backup_path_abs.$file_name;
			echo "<a class='hidden' id='dl_link' href='".$url."' download>".$file_name."</a>";
			echo "<script> setTimeout( function() { document.getElementById('dl_link').click(); }, 100 ); </script>";
			$result = 'success';
		}
		Log::$overlay = array(
			"show"      => true,
			"action"    => $result,
			"class"     => $class,
			"replace"   => $domain
		);

	}

	/**
	 * Delete directory.
	 * 
	 * @param string $dir_path			Path of directory to delete.
	 * @param array|string $exclude		Array of Paths to not delete.
	 */
	public static function delete_directory( $dir_path, $exclude=[] ) {
		
		if (is_string($exclude)) $exclude = array($exclude);

		$dir_path = trailingslashit( $dir_path );

		// $files = glob($dir_path.'*', GLOB_MARK);
		$files = glob($dir_path.'{,.}*', GLOB_BRACE);
		$del = true;
		foreach ($files as $file) {
			$tmp = explode('/', $file);
			if ($tmp[count($tmp)-1] == ".." || $tmp[count($tmp)-1] == ".") continue;
			if (is_array($exclude) && !empty($exclude)) {
				$skip = false;
				foreach ($exclude as $ex) {
					if ($ex != "" && strpos($file, $ex) === 0) {
						$del = false;
						$skip = true;
						break;
					}
				}
				if ($skip) continue;
			}
			if (is_dir($file)) self::delete_directory( $file, $exclude );
			else unlink($file);
		}
		
		$dir_path = untrailingslashit( $dir_path );

		if ( is_dir($dir_path) && $del ) {
			rmdir( $dir_path );
		}
	}

	/**
	 * List all files in a given folder, including subfolders.
	 * 
	 * @param string[] $files           The array of files, filled recursively.
	 * @param string $dir               The folder to list.
	 * @param string[] $exclude_files   Array of excluded files (folders).
	 * 
	 * @return string[] $files          All files in the folder.
	 */
	public static function list_files_in_dir($files, $dir, $exclude_files=[]) {
		// make exclude array
		if (is_string($exclude_files)) $exclude_files = [ $exclude_files ];
		$exclude_paths = array_map(function($exclude) {
			// trailingslash folders
			return strpos($exclude, '.') === false ? realpath(trailingslashit($exclude)) : realpath($exclude);
		}, $exclude_files);
		// get files
		$ffs = scandir($dir);
		unset($ffs[array_search('.', $ffs, true)]);
		unset($ffs[array_search('..', $ffs, true)]);
		// prevent empty ordered elements
		if (count($ffs) < 1) return $files;
		foreach ($ffs as $ff) {
			// exclude sys files
			if ( in_array( $ff, array( "thumbs.db", ".DS_Store", "__MACOSX" ) ) ) continue;
			// excluded folder/file
			if ( in_array(realpath($dir.'/'.$ff), $exclude_paths) ) continue;
			// debug($dir.'/'.$ff);
			if ( is_dir($dir.'/'.$ff) ) {
				// next iteration
				$files = self::list_files_in_dir($files, $dir.'/'.$ff, $exclude_files);
			}
			else {
				// add to files
				$files[] = $dir.'/'.$ff;
			}
		}
		return $files;
	}


	/*
	=======================================================================
		zip files handling
			zipSingleFilesStore -> zip_files
			zipFilesStore -> zip_folder
	=======================================================================
	*/

	/**
	 * Store an array of files in a zip.
	 * 
	 * @param string $zip_path		Path where to store the new ZIP archive.
	 * @param array $files			Array of file paths to store.
	 * 		@property string real		Real path to file
	 * 		@property string relative	Relative path of file inside zip (root is zip_name)
	 * @param bool $overwrite		Overwrite if ZIP archive exists. Set to false to open archive and add files.
	 * 
	 * @return bool
	 */
	public static function zip_files( $zip_path, $files, $overwrite=true ) {
		
		// return if no zip or no files found
		if ( empty($zip_path) ) return false;
		
		/**
		 * Adjust execution timeout (default: 3600)
		 * @filter 'greyd_hub_process_set_timeout'
		 */
		set_time_limit(apply_filters( 'greyd_hub_process_set_timeout', 3600 ));

		// set 1st lvl root path inside zip (zip_name)
		$root = explode( "/", $zip_path );
		$root = str_replace(".zip", "", $root[count($root)-1]);

		// create the archive
		$zip = new \ZipArchive();

		// overwrite or open
		$flags = $overwrite ? \ZipArchive::CREATE | \ZipArchive::OVERWRITE : \ZipArchive::CREATE;
		// try to open the archive
		if ( $zip->open( $zip_path, $flags ) !== TRUE ) {
			Log::log_abort( sprintf( __("ZIP file \"%s\" cannot be created.", 'greyd_hub'), $zip_path ) );
			return false;
		}

		// add files
		foreach ($files as $file) {
			// Add to archive (real path, relative path)
			$zip->addFile($file["real"], $root."/".$file["relative"]);
		}

		$zip->close();
		return true;

	}

	/**
	 * Store a folder with all files and subfolders in a zip.
	 * 
	 * @param string $zip_path				Path where to store the new ZIP archive.
	 * @param string $folder				Path to the current folder.
	 * @param array|string $exclude_files	Path of files to exclude from the ZIP.
	 * @param array $include_files			Alternate to $exclude_files.
	 * 
	 * @return bool
	 */
	public static function zip_folder( $zip_path, $folder, $exclude_files=[], $include_files=null ) {

		$files = self::list_files_in_dir(array(), realpath($folder), $exclude_files);
		// debug($files);

		// return if no files found
		if ( count($files) <= 0 ) {
			Log::log_message( sprintf( __("No files found in the \"%s\" folder.", 'greyd_hub'), $folder ), 'warning' );
			// return false;
		}
		
		// filter files
		$store = array();
		foreach ($files as $file) {

			// get relative path to folder
			$relative_path = substr($file, strlen(trailingslashit(realpath($folder))));

			// exclude files from the backup dir
			if (strpos(realpath($file), realpath(Admin::$urls->backup_path)) !== false) {
				continue;
			}

			// only include if listed in include_files
			if ( $include_files && is_array($include_files) ) {
				if ( !in_array( $relative_path, $include_files ) ) continue;
			}

			// store file
			array_push( $store, array(
				"real" => $file,
				"relative" => $relative_path
			) );
		}
		// debug($store);

		return self::zip_files( $zip_path, $store );
	}



	/*
	=======================================================================
		Blogs
	=======================================================================
	*/

	/**
	 * Blogs Cache.
	 * @var false|array
	 * 		@property array local
	 * 		@property array remote
	 */
	public static $_blogs_cache = false;

	/**
	 * Get basic information for all blogs.
	 * (only blogs > 0 and less attributes than get_all_blogs)
	 * 
	 * @return array
	 *      @property int blog_id
	 *      @property string domain
	 *      @property string http
	 *      @property string name
	 *      @property string description
	 *      @property array attributes
	 *          @property string registered
	 *          @property string last_updated
	 *          @property bool public
	 *          @property bool archived (only multisite)
	 *          @property bool spam (only multisite)
	 *          @property bool mature (only multisite)
	 *          @property bool deleted (only multisite)
	 *          @property bool|string protected
	 *          @property bool|array staging
	 *      @property string theme_slug
	 *      @property array theme_mods
	 *      @property string upload_url
	 *      @property string action_url
	 *      @property string network
	 */
	public static function get_basic_blogs() {
		
		// get all blogs and set cache
		if (self::$_blogs_cache == false || !isset(self::$_blogs_cache['local'])) {
			self::get_the_blogs();
		}

		// use cache
		$all_blogs = array();
		foreach( self::$_blogs_cache['local'] as $i => $blog ) {
			if ( $i == 'basic' || $i == 'unknown') continue;
			$all_blogs[] = array(
				'blog_id' => $blog['blog_id'],
				'domain' => $blog['domain'],
				'http' => $blog['http'],
				'name' => $blog['name'],
				'description' => $blog['description'],
				'attributes' => $blog['attributes'],
				'theme_slug' => $blog['theme_slug'],
				'theme_mods' => $blog['theme_mods'],
				'upload_url' => $blog['upload_url'],
				'action_url' => $blog['action_url'],
				'network' => $blog['network'],
			);
		}
		return $all_blogs;

	}

	/**
	 * Get all blogs with tables and infos via get_all_blogs() and sort them.
	 * 
	 * @return array
	 */
	public static function get_the_blogs() {

		// check cache
		if (self::$_blogs_cache !== false && isset(self::$_blogs_cache['local'])) {
			// use cache
			return self::$_blogs_cache['local'];
		}

		$all_blogs = self::get_all_blogs();
		$blogs = array_merge(
			$all_blogs['basic'],
			array_reverse( $all_blogs['blogs'] ),
			$all_blogs['unknown']
		);
		
		// set cache
		if (self::$_blogs_cache == false) self::$_blogs_cache = array();
		self::$_blogs_cache['local'] = $blogs;

		// debug($blogs);
		return $blogs;
	}

	/**
	 * Get all the blogs and their attributes
	 * 
	 * @return array
	 *      @property array basic
	 *          @property array basic
	 *              @property int blog_id
	 *              @property string version
	 *              @property string language
	 *              @property string domain
	 *              @property string name
	 *              @property string description
	 *              @property string admin
	 *              @property string prefix
	 *              @property array tables Array of tablenames
	 *              @property string action_url
	 * 
	 *      @property array blogs Array of tables keyed by prefix
	 *          @property int blog_id
	 *          @property string name
	 *          @property string domain
	 *          @property string prefix
	 *          @property bool current Whether this is the current blog.
	 *          @property array tables Array of tablenames
	 *          @property array attributes
	 *              @property string registered
	 *              @property string last_updated
	 *              @property bool public
	 *              @property bool archived (only multisite)
	 *              @property bool spam (only multisite)
	 *              @property bool mature (only multisite)
	 *              @property bool deleted (only multisite)
	 *              @property bool|string protected
	 *              @property bool|array staging
	 *          @property string description
	 *          @property string admin
	 *          @property array plugins_list
	 *          @property array plugins
	 *          @property string theme
	 *          @property string theme_version
	 *          @property string theme_slug
	 *          @property string theme_main
	 *          @property string theme_main_version
	 *          @property string theme_main_slug
	 *          @property array theme_mods
	 *          @property string upload_url
	 *          @property string action_url
	 *          @property string network
	 * 
	 *      @property array unknown
	 *          @property array unknown
	 *              @property int blog_id
	 *              @property string domain
	 *              @property string name
	 *              @property string description
	 *              @property array tables Array of tablenames
	 */
	public static function get_all_blogs() {
		
		global $wpdb;
		// prepare arrays
		$tables_basic = array(
			'basic' => array(
				'version' => get_bloginfo('version'),
				'language' => get_bloginfo('language'),
				'blog_id' => 0,
				'domain' => 'Basic WordPress',
				'name' => '', 'description' => '', 'admin' => '',
				'prefix' => $wpdb->base_prefix,
				'tables' => array(),
				'action_url' => Admin::$page['url'],
			)
		);
		$tables_blogs = array();
		$tables_unknown = array(
			'unknown' => array(
				'blog_id' => -1,
				'domain' => __("Unknown tables", 'greyd_hub'),
				'description' => __("Unknown tables of old websites or other installations", 'greyd_hub'), 
				'name' => '', 'admin' => '',
				'tables' => array(),
			)
		);

		if (is_multisite()) {

			// get all blogs
			$blogs = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."blogs order by blog_id desc");

			foreach ( $blogs as $blog ) {
				// get blog info
				$blog_details = get_blog_details($blog->blog_id);
				// debug($blog_details);
				// debug($blog_details->__get('protected'));
				$domainfull = substr($blog->domain.$blog->path, 0, -1);
				$http = str_replace('://'.$domainfull, '', $blog_details->home);
				$prefix = ($blog->blog_id == 1) ? $wpdb->base_prefix : $wpdb->base_prefix.$blog->blog_id.'_';
				$current = ($prefix == $wpdb->prefix) ? true : false;
				$tables_blogs[$prefix] = array(
					'name' => $blog_details->blogname,
					'blog_id' => $blog->blog_id,
					'domain' => $domainfull,
					'http' => $http,
					'prefix' => $prefix,
					'current' => $current,
					'tables' => array(),
					'attributes' => array(
						'registered' => $blog_details->registered,
						'last_updated' => $blog_details->last_updated,
						'public' => $blog_details->public,
						'archived' => $blog_details->archived,
						'spam' => $blog_details->spam,
						'mature' => $blog_details->mature,
						'deleted' => $blog_details->deleted,
						'protected' => $blog_details->__get('protected'),
						'staging' => $blog_details->__get('staging'),
					),
					'action_url' => Admin::$page['url'],
					'network' => self::get_nice_url(Admin::$urls->network_url),
				);

				switch_to_blog( $blog->blog_id );
				// https://developer.wordpress.org/reference/functions/get_bloginfo/
				$theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
				$tables_blogs[$prefix]['description'] = get_bloginfo('description');
				$tables_blogs[$prefix]['admin'] = get_bloginfo('admin_email');
				$tables_blogs[$prefix]['plugins_list'] = Helper::active_plugins();
				$tables_blogs[$prefix]['plugins'] = array();
				$tables_blogs[$prefix]['theme'] = wp_get_theme()->get('Name');
				$tables_blogs[$prefix]['theme_version'] = wp_get_theme()->get('Version');
				$tables_blogs[$prefix]['theme_slug'] = wp_get_theme()->stylesheet;
				$tables_blogs[$prefix]['theme_main'] = $theme->get('Name');
				$tables_blogs[$prefix]['theme_main_version'] = $theme->get('Version');
				$tables_blogs[$prefix]['theme_main_slug'] = $theme->stylesheet;
				$tables_blogs[$prefix]['theme_mods'] = get_theme_mods();
				$tables_blogs[$prefix]['upload_url'] = wp_upload_dir()["baseurl"];
				restore_current_blog();
				// debug($tables_blogs[$prefix]);
			}
		}
		else {
			// get this blog
			$theme = !empty( wp_get_theme()->parent() ) ? wp_get_theme()->parent() : wp_get_theme();
			// debug($wpdb);
			$domain = str_replace(array("https://", "http://"), "", get_bloginfo('url'));
			$http = str_replace('://'.$domain, '', get_bloginfo('url'));
			// $prefix = $wpdb->base_prefix;
			$prefix = $wpdb->prefix;
			$tables_blogs[$prefix] = array(
				'name' => get_bloginfo('name'),
				'description' => get_bloginfo('description'),
				'blog_id' => 1,
				'domain' => $domain,
				'http' => $http,
				'admin' => get_bloginfo('admin_email'),
				'prefix' => $prefix,
				'current' => true,
				'tables' => array(),
				'attributes' => array(
					'registered' => get_user_option('user_registered', 1),
					'last_updated' => get_lastpostdate(),
					'public' => get_option('blog_public', '0'),
					// not available on singlesite
					// 'archived' => '0',
					// 'spam' => '0',
					// 'mature' => '0',
					// 'deleted' => '0',
					'protected' => get_option('protected', '0'),
					'staging' => get_option('greyd_staging', '0'),
				),
				'plugins_list' => Helper::active_plugins(),
				'plugins' => array(),
				'theme' => wp_get_theme()->get('Name'),
				'theme_version' => wp_get_theme()->get('Version'),
				'theme_slug' => wp_get_theme()->stylesheet,
				'theme_main' => $theme->get('Name'),
				'theme_main_version' => $theme->get('Version'),
				'theme_main_slug' => $theme->stylesheet,
				'theme_mods' => get_theme_mods(),
				'upload_url' => wp_upload_dir()["baseurl"],
				'action_url' => Admin::$page['url'],
				'network' => self::get_nice_url(Admin::$urls->network_url),
			);
		}

		// plugins
		foreach ($tables_blogs as $blogkey => $blog) {
			foreach ($tables_blogs[$blogkey]['plugins_list'] as $key => $plugin) {
				$plugin_path = Admin::$urls->plugins_path.$plugin;
				if ( is_dir($plugin_path) || file_exists($plugin_path) ) {
					$plugin_data = get_plugin_data($plugin_path, false, false);
					$tables_blogs[$blogkey]['plugins'][$key] = array(
						"name" => $plugin_data['Name']." (".$plugin_data['Version'].")",
						"installed" => true
					);
				} 
				else {
					$plugin_name = $tables_blogs[$blogkey]['plugins_list'][$key];
					$plugin_name = strpos($plugin_name, '/') !== false ? explode("/", $plugin_name, 2)[0] : $plugin_name;
					$tables_blogs[$blogkey]['plugins'][$key] = array(
						"name" => $plugin_name,
						"installed" => false
					);
				}
			}
		}
		
		// get tables
		$tables = $wpdb->get_results("SHOW TABLES FROM `".$wpdb->dbname."`");
		$tables_all = array();
		foreach ($tables as $table) {
			$tablename = $table->{'Tables_in_'.$wpdb->dbname};
			// https://codex.wordpress.org/Database_Description#Multisite_Table_Overview
			if ($tablename == $wpdb->base_prefix."blogs" ||
				$tablename == $wpdb->base_prefix."blog_versions" ||
				$tablename == $wpdb->base_prefix."blogmeta" ||
				$tablename == $wpdb->base_prefix."registration_log" ||
				$tablename == $wpdb->base_prefix."signups" ||
				$tablename == $wpdb->base_prefix."site" ||
				$tablename == $wpdb->base_prefix."sitemeta" ||
				$tablename == $wpdb->base_prefix."sitecategories" ||
				$tablename == $wpdb->base_prefix."users" ||
				$tablename == $wpdb->base_prefix."usermeta") {
				$tables_basic['basic']['tables'][] = $tablename;
				continue;
			}
			$tables_all[] = $tablename;
		}
		foreach ($tables_blogs as $table_prefix => $table_value) {
			for ($i=0; $i<count($tables_all); $i++) {
				if (preg_match('/^'.$table_prefix.'\D/', $tables_all[$i]) === 1) {
					$tables_blogs[$table_prefix]['tables'][] = $tables_all[$i];
					$tables_all[$i] = "";
				}
			}
		}
		$tables_unknown['unknown']['tables'] = array_filter($tables_all);

		return array(
			'basic' => $tables_basic,
			'blogs' => $tables_blogs,
			'unknown' => $tables_unknown,
		);
	}

	public static $current_pagination = array();

	/**
	 * Paginates and displays a set of blogs based on the given array,
	 * number of blogs per page, and URL parameter for pagination.
	 *
	 * @param array  $blogs      The array of blogs to be paginated.
	 * @param string $attribute  The URL parameter for pagination.
	 *                           Default is 'paged'.
	 * 
	 * @return array             The paginated array of blogs.
	 */
	public static function paginateBlogs( $blogs, $attribute='paged' ) {

		// filter all blogs with no blog id out
		$blogs = array_filter($blogs, function($blog) {
			return ( isset($blog['blog_id']) && $blog['blog_id'] > 0 ) || ( isset($blog['is-staging-pair']) && $blog['is-staging-pair'] );
		});

		// get total number of blogs
		$total = count($blogs);
		if ( $total < 2 ) return $blogs;

		// make sure the attribute is encoded
		$attribute = sanitize_title( $attribute );

		$perPage = apply_filters( 'greyd_hub_blogs_per_page', 12 );

		// Get the current page number from the query string or set a default value
		$page = isset($_GET[ $attribute ]) ? intval($_GET[ $attribute ]) : 1;
	
		// Calculate the total number of pages
		$totalPages = ceil(count($blogs) / $perPage);
	
		// Validate the current page number
		if ($page < 1 || $page > $totalPages) {
			// Handle invalid page number, e.g., redirect to the first page
			wp_safe_redirect( add_query_arg( $attribute, 1 ) );
			exit;
		}
	
		// Calculate the starting and ending indexes of the blogs to display on the current page
		$startIndex = ($page - 1) * $perPage;
		$endIndex = $startIndex + $perPage - 1;
	
		// Get the blogs for the current page
		$blogsForPage = array_slice($blogs, $startIndex, $perPage, true);

		// Set the current pagination info
		self::$current_pagination[ $attribute ] = array(
			'perPage'     => $perPage,
			'totalPages'  => $totalPages,
			'currentPage' => $page,
			'totalBlogs'  => $total
		);

		return $blogsForPage;
	}

	/**
	 * Builds the HTML for the pagination links.
	 * 
	 * @param string $attribute  The URL parameter for pagination.
	 *                           Default is 'paged'.
	 * 
	 * @return string       The HTML for the pagination links.
	 */
	public static function render_pagination_links( $attribute='paged' ) {

		// make sure the attribute is encoded
		$attribute = sanitize_title( $attribute );

		// do not render if pagination is not set
		if ( ! isset( self::$current_pagination[ $attribute ] ) ) {
			return;
		}

		// Get the pagination attributes
		$atts = self::$current_pagination[ $attribute ];

		// Get the pagination attributes
		$page       = $atts['currentPage'];
		$totalPages = $atts['totalPages'];
		$totalBlogs = $atts['totalBlogs'];

		// Generate the pagination markup
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="greyd_hub" />';
		echo '<div class="tablenav">';
		echo '<div class="tablenav-pages">';
		echo '<span class="displaying-num">' . sprintf( __("%s websites", 'greyd_hub'), $totalBlogs ) . '</span>';
		echo '<span class="pagination-links">';

		// Previous page link
		if ($page > 1) {
			$prevPage = $page - 1;
			echo '<a class="prev-page button" href="' . esc_url(add_query_arg($attribute, 1)) . '"><span class="screen-reader-text">«</span><span aria-hidden="true">«</span></a>';
			echo '<a class="prev-page button" href="' . esc_url(add_query_arg($attribute, $prevPage)) . '"><span class="screen-reader-text">‹</span><span aria-hidden="true">‹</span></a>';
		} else {
			echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
			echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
		}

		// Current page and total pages
		echo '<span class="paging-input">';
		echo '<label for="current-page-selector" class="screen-reader-text">'.__("Current site", 'greyd_hub').'</label>';
		echo '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $page . '" size="1" aria-describedby="table-paging">';
		echo '<span class="tablenav-paging-text"> von <span class="total-pages">' . $totalPages . '</span></span>';
		echo '</span>';

		// Next page link
		if ($page < $totalPages) {
			$nextPage = $page + 1;
			echo '<a class="next-page button" href="' . esc_url(add_query_arg($attribute, $nextPage)) . '"><span class="screen-reader-text">›</span><span aria-hidden="true">›</span></a>';
			echo '<a class="last-page button" href="' . esc_url(add_query_arg($attribute, $totalPages)) . '"><span class="screen-reader-text">Letzte Seite</span><span aria-hidden="true">»</span></a>';
		} else {
			echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
			echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
		}

		echo '</span></div></div></form>';
	}
	

	/*
	=======================================================================
		Remote Blogs
	=======================================================================
	*/

	/**
	 * Get basic blogs info from remote connection.
	 * 
	 * @param array $connection
	 * @return array|WP_Error   @see get_basic_blogs() or error.
	 */
	public static function get_basic_blogs_remote( $network_url, $connection ) {

		// get all remote blogs and set cache
		if (self::$_blogs_cache == false || !isset(self::$_blogs_cache['remote'][$network_url])) {
			$response = self::get_all_blogs_remote( $network_url, $connection );
			if ( is_wp_error( $response ) ) return $response;
		}

		// use cache
		$all_blogs = array();
		foreach( self::$_blogs_cache['remote'][$network_url] as $i => $blog ) {
			if ( $i == 'basic' || $i == 'unknown') continue;
			$all_blogs[] = array(
				'blog_id' => $blog['blog_id'],
				'domain' => $blog['domain'],
				'http' => $blog['http'],
				'name' => $blog['name'],
				'description' => $blog['description'],
				'attributes' => isset($blog['attributes']) ? $blog['attributes'] : array(),
				'theme_slug' => $blog['theme_slug'],
				'theme_mods' => $blog['theme_mods'],
				'upload_url' => isset($blog['upload_url']) ? $blog['upload_url'] : "",
				'action_url' => $blog['action_url'],
				'network' => $blog['network'],
				'is_remote' => true
			);
		}
		return $all_blogs;

	}

	/**
	 * Get all blogs info from remote connection.
	 * 
	 * @param array $connection
	 * 
	 * @return WP_Error On REST Error.
	 * @return array|WP_Error   @see get_all_blogs() or error.
	 */
	public static function get_all_blogs_remote( $network_url, $connection ) {

		// check cache
		if (self::$_blogs_cache !== false && isset(self::$_blogs_cache['remote'][$network_url])) {
			// use cache
			return self::$_blogs_cache['remote'][$network_url];
		}

		ob_start();
		if ( method_exists( '\Greyd\Connections\Connections_Helper', 'send_request' ) ) {
			$response = \Greyd\Connections\Connections_Helper::send_request($connection, "remote_blogs");
		}
		ob_end_clean();
		// debug($response);

		if ( !$response ) {
			// false means that restroute "remote_blogs" responded an error or is not there
			// maybe old hub version without endpoint
			// @see global content "REST API Error" for details
			return new \WP_Error( 'no_rest_route', __( "An error has occurred. Please check the connections on both sides and make sure that all plugins are up to date.", 'greyd_hub' ) );
		}
		if (!is_object($response) || empty($response)) {
			// string 'empty' or empty array means that restroute is ok, but actually nothing was found there
			return new \WP_Error( 'empty', __( "No content was found on the connected installation.", 'greyd_hub' ) );
		}

		$blogs = json_decode( json_encode( $response ), true );

		// compatibility with old endpoint
		$blog_table_name = $blogs['basic']['basic']['prefix'].'blogs';
		$is_multisite    = isset($blogs['basic']['basic']['tables']) && array_search( $blog_table_name, $blogs['basic']['basic']['tables'] );
		$action          = $is_multisite ? "https://{$network_url}/wp-admin/network/admin.php?page=greyd_hub" : "https://{$network_url}/wp-admin/admin.php?page=greyd_hub";
		foreach( $blogs['blogs'] as $i => $blog ) {
			// set action url if not there
			if (!isset($blog['action_url'])) {
				// add action url
				$blogs['blogs'][$i]['action_url'] = $action;
			}
			// set action url if not there
			if (!isset($blog['network'])) {
				$blogs['blogs'][$i]['network'] = $network_url;
			}
		}

		$blogs = array_merge(
			$blogs['basic'],
			array_reverse( $blogs['blogs'] )
		);

		// set cache
		if (self::$_blogs_cache == false) self::$_blogs_cache = array();
		if (!isset(self::$_blogs_cache['remote'])) self::$_blogs_cache['remote'] = array();
		self::$_blogs_cache['remote'][$network_url] = $blogs;

		return $blogs;

	}

	/*
	=======================================================================
		Networks
	=======================================================================
	*/

	/**
	 * Get basic blogs from all networks.
	 * @see get_basic_blogs()
	 * @see get_basic_blogs_remote()
	 * 
	 * @return array $networks	All basic Blogs by network.
	 */
	public static function get_basic_networks() {
		
		// get all blogs
		$all_blogs = self::get_basic_blogs();
		$networks = array( self::get_nice_url(Admin::$urls->network_url) => $all_blogs );

		if (Admin::$connections) {
			// get global blogs
			foreach (Admin::$connections as $network_url => $connection) {
				$remote_blogs = self::get_basic_blogs_remote( $network_url, $connection );
				// debug( $remote_blogs, true );
				if ( is_wp_error( $remote_blogs ) ) continue;

				foreach( $remote_blogs as $i => $blog ) {
					if ( !isset($networks[$network_url]) ) {
						$networks[$network_url] = array();
					}
					$networks[$network_url][] = $blog;
				}
			}
			// debug($network_url);
		}
		// debug($networks);

		return $networks;

	}

	/**
	 * Get all blogs from all networks.
	 * @see get_all_blogs()
	 * @see get_all_blogs_remote()
	 * 
	 * @return array  $blogs  All Blogs.
	 *      @property object local      All local Blogs.
	 *      @property object remote     All remote Blogs by network. (optional)
	 */
	public static function get_all_networks() {
		
		// get data for all blogs
		$blogs = array(
			'local' => self::get_the_blogs()
		);
		if (Admin::$connections) {
			$blogs['remote'] = array();
			foreach (Admin::$connections as $network_url => $connection) {
				$blogs['remote'][$network_url] = self::get_all_blogs_remote( $network_url, $connection );
			}
		}

		return $blogs;

	}

	/**
	 * Make a nonce field to use for forms that post cross networks.
	 * @return string
	 */
	public static function make_remote_nonce() {
		return '<input type="hidden" name="_wpnonce" value="remote">';
	}

	/*
	=======================================================================
		API/ajax functions
	=======================================================================
	*/

	/**
	 * Get all Blog changes since a given date.
	 * 
	 * @param string $blogid            Blog ID.
	 * @param string $datetime          Date and time (format: 'Y-m-d H:i:s').
	 * 
	 * @return array $changes
	 *      @property array posts       Changed posts (from date_query).
	 *      @property array design      Changed design (from date_query and customize_changeset).
	 *      @property array options     Changed options (from 'greyd_staging' option).
	 */
	public static function get_changes( $blogid, $datetime ) {
		
		// check blog id
		if (
			!is_int($blogid) ||
			( !is_multisite() && $blogid != 1) ||
			( is_multisite() && \WP_Site::get_instance( $blogid ) == false )
		) {
			return false;
		}

		if (is_multisite()) switch_to_blog($blogid);

		$changes = array(
			'posts' => array(),
			'design' => array(),
			'options' => array()
		);

		// get changed options
		$staging = get_option('greyd_staging', '0');
		if (is_array($staging) && isset($staging['options'])) {
			foreach ($staging['options'] as $option => $modified) {
				if ($modified > $datetime &&
					strpos($option, "_transient") === false &&
					strpos($option, "external_updates") === false
				) {
					$changes['options'][] = (object)array(
						"key" => $option,
						"modified" => $modified
					);
				}
			}
		}

		// posttypes and poststati to check for changes
		$posttypes = array_keys(get_post_types( array( 'exclude_from_search' => false ) ));
		$posttypes = array_merge($posttypes, array(
			"customize_changeset",
			"wp_block",
			"wp_template",
			"wp_template_part",
			"wp_global_styles",
			"wp_navigation",
			"tp_posttypes",
			"greyd_popup",
			"tp_forms",
			"dynamic_template"
		));
		$poststati = array(
			"publish",
			"future",
			"draft",
			"pending",
			"private",
			"trash",
			"inherit"
		);
		// debug($posttypes);
		// debug($poststati);

		// make query
		$query = new \WP_Query( array(
			'post_type' => $posttypes,
			'post_status' => $poststati,
			'posts_per_page' => -1,
			'date_query' => array(
				'column' => 'post_modified',
				'after'  => $datetime,
			)
		) );

		// get posts
		while ( $query->have_posts() ) {
			$query->the_post();
			// debug($query->post);
			$post = (object)array(
				'ID' => $query->post->ID,
				'post_name' => $query->post->post_name,
				'post_title' => $query->post->post_title,
				'post_author' => $query->post->post_author,
				'post_date' => $query->post->post_date,
				'post_date_gmt' => $query->post->post_date_gmt,
				'post_modified' => $query->post->post_modified,
				'post_modified_gmt' => $query->post->post_modified_gmt,
				'post_type' => $query->post->post_type,
				'post_status' => $query->post->post_status,
				'guid' => $query->post->guid,
				'edit' => get_edit_post_link($query->post)
			);
			if ($post->post_type == "customize_changeset") {
				// a new trashed 'customize_changeset' means that there was something
				// changed in the customizer which means the design (thememods) is changed
				// debug($query->post);
				$changes['design'][] = $post;
			}
			else {
				// change in any other post_type
				$changes['posts'][] = $post;
			}
		}
		
		// restore original post data
		wp_reset_postdata();

		if (is_multisite()) restore_current_blog();

		// debug($changes);
		return $changes;

	}

	/**
	 * Disconnect Blog from staging.
	 * Reset 'greyd_staging' option.
	 * 
	 * @param string $blogid            Blog ID.
	 * 
	 * @return bool
	 */
	public static function disconnect_blog( $blogid ) {
		
		// check blog id
		if (
			!is_int($blogid) ||
			( !is_multisite() && $blogid != 1) ||
			( is_multisite() && \WP_Site::get_instance( $blogid ) == false )
		) {
			return false;
		}

		if (is_multisite()) switch_to_blog($blogid);

		// reset option
		$result = update_option('greyd_staging', "0");

		if (is_multisite()) restore_current_blog();

		return $result;

	}

	/**
	 * Connect Blog to staging.
	 * Set 'greyd_staging' option.
	 * 
	 * @param string $blogid    Blog ID.
	 * @param string $mode      Staging mode ("live" or "stage").
	 * @param string $related   Related site ("{network}|{blogid}").
	 * 
	 * @return bool
	 */
	public static function connect_blog( $blogid, $mode, $related ) {

		// check input
		if (
			// check blog id
			!is_int($blogid) ||
			( !is_multisite() && $blogid != 1) ||
			( is_multisite() && \WP_Site::get_instance( $blogid ) == false ) ||
			// check mode
			!in_array($mode, array( 'live', 'stage' )) ||
			// check related
			strpos($related, "|") === false ||
			count(explode("|", $related)) != 2
		) {
			return false;
		}

		// check related network
		$network = explode("|", $related)[0];
		if ( $network != self::get_nice_url(Admin::$urls->network_url) ) {
			if ( Admin::$connections == false || !isset(Admin::$connections[$network]) ) {
				return false;
			}
		}

		if (is_multisite()) switch_to_blog($blogid);

			$staging = array( 'updated' => array(
				date('Y-m-d H:i:s')."|connect|".$mode
			) );
			if ($mode == 'live') {
				$staging['is_live'] = true;
				$staging['stage'] = $related;
			}
			if ($mode == 'stage') {
				$staging['is_stage'] = true;
				$staging['live'] = $related;
			}
			// debug($staging);
			$result = update_option('greyd_staging', $staging);

		if (is_multisite()) restore_current_blog();

		return $result;

	}

	/**
	 * Make silent export of Blog data without download trigger
	 * 
	 * @param string $blogid            Blog ID.
	 * @param string $mode              Dump Mode ('files', 'db', 'plugins', 'themes', 'content', 'site').
	 * @param boolean $clean            Make clean exports (default: true).
	 * @param boolean $return_url       Whether to return an absolute URL to the file.
	 * 
	 * @return string|false             URI or URL to dump on success.
	 *                                  Empty string if dump mode not found.
	 *                                  False if blog id not found.
	 */
	public static function dump_blog( $blogid, $mode, $clean=true, $return_url=false ) {
		$url = "";
		
		// get domain
		if (is_multisite()) {
			$blog_details = get_blog_details($blogid);
			if ($blog_details === false) {
				return false;
			}
			$domain = $blog_details->domain.$blog_details->path;
		}
		else {
			if ($blogid != 1) {
				return false;
			}
			$domain = str_replace(array("https://", "http://"), "", get_bloginfo('url'));
		}

		// make blog download
		Admin::$clean->domain = "remote.greydsuite.de";
		$file_name = Tools::download_blog( array(
			'mode' => $mode,
			'blogid' => $blogid,
			'domain' => $domain,
			'trigger' => false,
			'clean' => $clean,
			'log' => false
		) );

		if ( $file_name && !empty($file_name) ) {
			$file_path = $return_url ? Admin::$urls->backup_path_abs : Admin::$urls->backup_path;
			$file_name = $file_path.$file_name;
		}
		
		return $file_name;
	}

	/**
	 * Export fullsite template.
	 * 
	 * @param string $blogid            Blog ID.
	 * @param string $domain            Blog domain.
	 * 
	 * @return array
	 * 		@property string 0          Full url of File.
	 * 		@property string 1          Filename.
	 */
	public static function export_fullsite_template( $blogid, $domain ) {

		$zip_name = Tools::download_blog( array(
			'mode' => 'content',
			'blogid' => $blogid,
			'domain' => $domain,
			'trigger' => false,
			'clean' => true,
			'log' => false
		) );
		$zip = array( Admin::$urls->backup_path.$zip_name, $zip_name );

		return $zip;
	}

	/**
	 * Import fullsite template. 
	 * (Import can potentially log some divs - those are filtered with output buffering.)
	 * 
	 * @param string $file_path         Full url of template File.
	 * @param string $blogid            Blog ID.
	 * @param string $domain            Blog domain.
	 * 
	 * @return string|bool              'imported' (on success) or false
	 */
	public static function import_template( $file_path, $blogid, $domain ) {
		ob_start();

		$result = Tools::import_blog( array(
			'mode' => 'template',
			'blogid' => $blogid,
			'domain' => $domain,
			'source' => $file_path,
			'log' => false
		) );

		$logs = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * Get url without protocol, www. and trailing slash.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_nice_url( $url ) {
		return untrailingslashit( preg_replace( "/^((http|https):\/\/)?(www.)?/", "", strval( $url ) ) );
	}

}