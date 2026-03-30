<?php
/**
 * Template Library Ajax handling
 */

namespace Greyd\Library;

use Greyd\Post_Export as Post_Export;
use Greyd\Post_Import as Post_Import;
use Greyd\Settings as Settings;
use Greyd\Helper as Helper;
use Greyd\Hub\Hub_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Ajax();
class Ajax {

	/**
	 * Holds the main post ID after partial template imports.
	 * 
	 * @var int
	 */
	private $main_inserted_new_post_id = 0;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// add hooks for ajax handling
		add_action( 'greyd_ajax_mode_install_fullsite_template',  array( $this, 'install_fullsite_template' ) );
		add_action( 'greyd_ajax_mode_install_partial_template',  array( $this, 'install_partial_template' ) );
		add_action( 'greyd_ajax_mode_create_user_fullsite_template',  array( $this, 'create_user_fullsite_template' ) );
		add_action( 'greyd_ajax_mode_delete_user_fullsite_template',  array( $this, 'delete_user_fullsite_template' ) );
		add_action( 'greyd_ajax_mode_get_user_templates',  array( $this, 'get_user_templates' ) );
		add_action( 'greyd_ajax_mode_upload_fullsite_template_thumbnail',  array( $this, 'upload_fullsite_template_thumbnail' ) );
		add_action( 'greyd_ajax_mode_set_hide_welcome_screen_setting',  array( $this, 'set_hide_welcome_screen_setting' ) );

	}

	/**
	 * =================================================================
	 *                          AJAX
	 * =================================================================
	 */

	/**
	 * Ajax function to install a fullsite template.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function install_fullsite_template( $data ) {

		$download_url   = isset($data['url']) ? $data['url'] : "";
		$blog_id        = isset($data['blog_id']) ? $data['blog_id'] : get_current_blog_id();
		$domain         = isset($data['domain']) ? $data['domain'] : home_url();
		$response       = array( "status" => null );
		$is_local_file  = strpos($download_url, WP_CONTENT_URL) === 0;

		self::log_install_step(__("Download is being prepared", 'greyd_hub'), "start");

		// set the tmp folder path
		$tmp_folder_path = WP_CONTENT_DIR."/backup/tmp/"; 

		// create folder if it does not exist
		if (!file_exists($tmp_folder_path)) mkdir($tmp_folder_path, 0755, true);

		// Use the basename function to return the name of the file and build the full file path
		$file_path = $tmp_folder_path.basename($download_url);

		/**
		 * The template file comes from this stage
		 * (user fullsite templates)
		 */
		if ( $is_local_file ) {

			// we have to make a copy of the zip because it gets modified and therefore throws an error if it is opened again
			$download_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $download_url );
			$result = copy( $download_path, $file_path );
			// if ( $result ) {
			//     self::log_install_step(__("Download completed", 'greyd_hub'));
			// } else {
			//     $response['status'] = "error::Copy of the template failed...";
			//     wp_send_json( $response );
			// }
			// $file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $download_url );
		}

		/**
		 * Download the file to the backup/tmp/ folder if the template
		 * comes from a remote url (greyd template library)
		 */
		if ( ! $is_local_file ) {

			// set context header for faster download
			$context = stream_context_create(
				array(
					'http' => array(
						'header'=>'Connection: close\r\n'
					),
					"ssl"=> array(
						"verify_peer" => false,
						"verify_peer_name" => false,
					),
				)
			);

			$content = Helper::get_file_contents( $download_url, false, $context );
			if ( empty($content) ) {
				$response['status'] = "error::content of the file is empty...";
				wp_send_json( $response );
			}
			// echo "\n\r";
			// var_dump( $content );

			// download the file
			if ( file_put_contents( $file_path, $content ) ) {
				self::log_install_step(__("Download completed", 'greyd_hub'));
			}
			else {
				$response['status'] = "error::Download of the template failed...";
				wp_send_json( $response );
			}
		}

		usleep(250000);

		self::log_install_step(__("Template is being installed. This may take a moment.", 'greyd_hub'));

		// Import the template via hub.
		$result = Hub_Helper::import_template( $file_path, $blog_id, $domain );

		self::log_install_step(__("Template was successfully installed.", 'greyd_hub'));

		// delete the temporary files afterwards

		usleep(250000);
		self::log_install_step(__("Temporary files are deleted", 'greyd_hub'));

		// delete download file
		if (file_exists($file_path)) {
			unlink($file_path);
		}

		// delete tmp folder
		if (file_exists($tmp_folder_path)) {
			rmdir($tmp_folder_path);
		}


		self::log_install_step(__("Installation completed", 'greyd_hub'), "end");

		$response['status'] = "success::fullsite";

		wp_send_json( $response );
	}

	/**
	 * Ajax function to upload a thumbnail.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function upload_fullsite_template_thumbnail( $data ) {

		set_time_limit(5000);

		$thumbnail_tmp_path = WP_CONTENT_DIR."/backup/user_fullsite_templates/tmp/";

		// delete tmp folder so that there is alway only 1 image
		if (file_exists($thumbnail_tmp_path)) {
			array_map('unlink', glob("$thumbnail_tmp_path/*.*"));
			rmdir( $thumbnail_tmp_path);
		}

		mkdir($thumbnail_tmp_path, 0755, true);


		$file = $data;

		// check for errors
		if ( isset($file['error']) && $file['error'] > 0 ) {
			$file_errors = array(
				1 => sprintf(
					__("The uploaded file exceeds the server's maximum file limit (max %s MB). The limit is defined in the <u>php.ini</u> file.", 'greyd_hub'),
					intval( ini_get('upload_max_filesize') )
				),
				2 => __("The uploaded file exceeds the allowed file size of the HTML form.", 'greyd_hub'),
				3 => __("The uploaded file was only partially uploaded.", 'greyd_hub'),
				4 => __("No file was uploaded.", 'greyd_hub'),
				6 => __("Missing a temporary folder.", 'greyd_hub'),
				7 => __("Failed to save the file.", 'greyd_hub'),
				8 => __("The file was stopped while uploading.", 'greyd_hub'),
			);
			wp_die( "error::".$file_errors[ $file['error'] ] );
		}

		//size check here

		$temporary_name = $file['tmp_name'];
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);

		$name = bin2hex(random_bytes(32)); //true random id

		$result = move_uploaded_file($temporary_name, $thumbnail_tmp_path.'/'.$name.'.'.$extension);

		$response['status'] = "success::".$name.'.'.$extension;

		wp_die(  $response['status'] );
	}

	/**
	 * Ajax function to create a user fullsite template.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function create_user_fullsite_template( $data ) {

		$title = $data['meta']['title'];
		$description = $data['meta']['description'];
		$thumbnail = isset($data['filename']) ? $data['filename'] : '';
		$blog_id = intval($data['blog_id']);
		$domain = $data['domain'];

		// remove https:// from domain
		$domain = preg_replace("(^https?://)", "", $domain );

		// set file paths
		$base_path = WP_CONTENT_DIR."/backup/user_fullsite_templates/"; 

		// escape and sanatize folder name
		$template_folder_name = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', $title));
		$template_folder_path = $base_path.$template_folder_name."/";

		// create folder if it does not exist
		if (!file_exists($template_folder_path)) {
			mkdir($template_folder_path , 0755, true);
		}
		else {
			wp_die( "error::existing_template");
		}

		// Export template via hub.
		$zip = Hub_Helper::export_fullsite_template( $blog_id, $domain );

		// move file to /backup/user_fullsite_templates/{{template-name}}/
		rename($zip[0], $template_folder_path.$zip[1]);

		$template_data = array(
			"slug" => $template_folder_name,
			"title" => $title,
			"description" => $description
		);

		// create json 
		$fp = fopen($template_folder_path.$template_folder_name.'.json', 'w');
		fwrite($fp, json_encode($template_data));
		fclose($fp);

		//mv thumbnail to template folder
		if ( !empty($thumbnail) ) {
			rename($base_path."tmp/".$thumbnail, $template_folder_path.$thumbnail );
		}
		else {
			$greyd_placeholder_thumbnail = 'greyd-user-template-placeholder.jpg';
			$img_path = plugin_dir_url(__FILE__).'assets/img/';
			copy($img_path.$greyd_placeholder_thumbnail, $template_folder_path.$greyd_placeholder_thumbnail);
		}

		if (file_exists($base_path."tmp/")) rmdir($base_path."tmp/");

		$response = "success::";

		wp_die(  $response );
	}

	/**
	 * Ajax function to delete a user fullsite template.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function delete_user_fullsite_template( $data ) {

		$template_slug = isset($data['template']) ? $data['template'] : '';

		$base_path = WP_CONTENT_DIR."/backup/user_fullsite_templates/"; 

		if (!isset($template_slug)) {
			$response = "error::could not find template slug";
			wp_die(  $response );
		}

		$template_folder = $base_path.$template_slug."/";

		if (!file_exists($template_folder)) {
			$response = "error::template not found";
			wp_die(  $response );
		} 

		array_map('unlink', glob("$template_folder/*.*"));
		rmdir($template_folder);

		$response = "success::template deleted successfully";

		wp_die(  $response );
	}

	/**
	 * Ajax function to get all user fullsite template.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function get_user_templates( $data ) {

		$user_template_path = WP_CONTENT_DIR."/backup/user_fullsite_templates/"; 

		if  ( !file_exists( $user_template_path )) {
			wp_die("error::no templates found.");
		} else if ( scandir( $user_template_path ) === false ) {
			wp_die("error::no templates found.");
		} 

		$dir = new \DirectoryIterator( $user_template_path);

		$data = array();

		$template_folders = self::get_template_files($dir);


		if ( function_exists('content_url') ) {
			$base_content_url = content_url();
		}
		else {
			$base_content_url = CONTENT_URL;
		}

		$base_content_url .= "/backup/user_fullsite_templates/";


		foreach ($template_folders as $template_folder => $template_contents) {

			if (strpos($template_folder, "__MACOSX/") !== false 
				|| strpos($template_folder, ".DS_Store") !== false 
				|| strpos($template_folder, "tmp") !== false) 
			continue;


			if (is_array($template_contents)) {
				$template = array();
				foreach ($template_contents as $template_file) {
					if ( self::ends_with($template_file, ".zip" ) ) {
						$template["download_url"] = $base_content_url.$template_folder."/".$template_file;
					}
					else if ( self::ends_with($template_file, ".json" )) {
						$tmp = (array) json_decode(Helper::get_file_contents($user_template_path.$template_folder."/".$template_file));
						$template = array_merge($template, $tmp);
					}
					else {
						$template["thumbnail_url"] = $base_content_url.$template_folder."/".$template_file;
					}
				}
				$data[] = $template;
			}
		}

		if (empty( $data )) {
			wp_die("error::no templates found.");
		} else {
			wp_die("success::".json_encode( $data ));
		}
	}

	/**
	 * Ajax function to install a partial template.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function install_partial_template( $data ) {

		$download_url = isset($data['url']) ? $data['url'] : null;
		$response = array(
			"status" => null,
			"postID" => null
		);
		if ( empty($download_url) ) { 
			$response["status"] = "error::No url set";
			wp_send_json( $response );
		}

		self::log_install_step(__("Download is being prepared", 'greyd_hub'), "start");

		// set the tmp folder path
		$tmp_folder_path = WP_CONTENT_DIR."/backup/tmp/"; 

		// create folder if it does not exist
		if (!file_exists($tmp_folder_path)) mkdir($tmp_folder_path, 0755, true);

		// Use the basename function to return the name of the file and build the full file path
		$file_path = $tmp_folder_path.basename($download_url);

		// set context header for faster download
		$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));

		// download the file
		if ( file_put_contents( $file_path, Helper::get_file_contents($download_url, false, $context) ) ) { 
			self::log_install_step(__("Download completed", 'greyd_hub'));
		}
		else {
			$response["status"] = "error::Download of the template failed...";
			wp_send_json( $response );
		}

		usleep(250000);

		self::log_install_step(__("Template is being installed. This may take a moment.", 'greyd_hub'));

		// cache the main post id of the import
		add_action( 'greyd_after_import_post', array( $this, 'after_import_post' ), 10, 2 );

		// use the import of Post_Export to install partial templates
		$conflict_actions   = array();
		$post_data          = Post_Import::get_zip_posts_file_contents( $file_path );
		$conflicting_posts  = json_decode( Post_Import::import_get_conflict_posts_for_backend_form( $post_data ) );

		if (isset($conflicting_posts)) {
			foreach($conflicting_posts as $post) {
				$conflict_actions[$post->ID] ="keep";
			}
		}

		$conflict_actions   = Post_Import::import_get_conflict_actions_from_backend_form($conflict_actions);
		$result             = Post_Import::import_posts($post_data, $conflict_actions, $file_path);
		$new_post_id        = $this->main_inserted_new_post_id;

		self::log_install_step(__("Installation completed", 'greyd_hub'));

		usleep(250000);

		self::log_install_step(__("Temporary files are deleted", 'greyd_hub'), "end");

		// delete the download file
		if (file_exists($file_path)) {
			unlink($file_path);
		}

		// delete tmp folder
		if (file_exists($tmp_folder_path)) {
			rmdir($tmp_folder_path);
		}
		$response["status"] = "success::partial";
		$response["postID"] = intval($new_post_id);
		wp_send_json( $response );
	}

	/**
	 * After a partial template is imported we want to redirect the user 
	 * to the edit screen of the imported post. to do that we need the
	 * new post-id. with this action we can save the new post-id to 
	 * a class variable
	 * 
	 * @action: 'greyd_after_import_post'
	 * 
	 * @param int $post_id  
	 * @param object $post
	 */
	public function after_import_post( $post_id, $post ) {
		if ( ! $this->main_inserted_new_post_id ) {
			$this->main_inserted_new_post_id = $post_id;
		}
	}

	/**
	 * Ajax function to set the option for hiding the welcome message.
	 * 
	 * @action 'greyd_ajax_mode_'
	 * 
	 * @param array $data   $_POST data.
	 */
	public function set_hide_welcome_screen_setting( $data ) {
		$value = $data['value'] === 'true' ? 'true' : 'false';
		$result = Settings::update_setting('site', array( 'site', 'template_library', 'hide_welcome' ), $value);
		wp_die("success::".$result);
	}

	/**
	 * =================================================================
	 *                          HELPER
	 * =================================================================
	 */


	/**
	 * Outputs a message with echo and flushes the buffer
	 * it gets captured in the ajax request with jqXHR.readyState == 3
	 * in javascript backend.js
	 * 
	 * @param string $msg   the message which will be sent to the frontend
	 * @param string $position  "start" for the starting message, "end" for the last message, leave blank for the messages between start and end
	 * 
	 * Output format:  >>status::$msg::
	 */
	public static function log_install_step($msg='', $position='') {
		if ($msg == '' && $postition == '') return;
		if ( $position == "start") {
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
		} 
		if ( $position == 'end') {
			echo ">>status::{$msg}::";
			if (ob_get_level() > 0) ob_flush();
			flush();
			usleep(250000);
			echo "::END::";
			if (ob_get_level() > 0) ob_flush();
			flush();
		} else {
			echo ">>status::{$msg}::";
			if (ob_get_level() > 0) ob_flush();
			flush();
		}
	}

	/**
	 * Helper function to get all template files.
	 * 
	 * @param DirectoryIterator $dir
	 * 
	 * @return array $data
	 */
	public static function get_template_files( \DirectoryIterator $dir ) {
		$data = array();

		foreach ( $dir as $node ) {
			if ( $node->isDir() && !$node->isDot() ) {
				$data[$node->getFilename()] = self::get_template_files( (new \DirectoryIterator($node->getPathname() ) ) );
			} else if ( $node->isFile() ) {
				$data[] = $node->getFilename();
			}
		}
		return $data;
	}

	/**
	 * Helper function to check if a string ends with another string. Good to check for file-extensions.
	 * 
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public static function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}

}
