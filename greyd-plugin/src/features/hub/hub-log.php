<?php
/**
 * Greyd.Hub overlay and wizard.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Log($config);
class Log {

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

		// overlays for feedback
		// add_action( 'admin_head', array($this, 'maybe_render_loading_overlay') ); // tbd
		add_action( 'greyd_hub_page_before_wrapper', array($this, 'render_overlay') );
		add_filter( "greyd_hub_log_contents", array($this, 'add_log_contents') );

		if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
			// standalone mode does not support wizard
			return;
		}

		// 
		// wizard post action
		add_action( "greyd_hub_handle_post_data", array($this, 'handle_wizard_post_data') );

		//
		// new site wizard
		add_action( 'greyd_hub_page_after_title', array($this, 'render_new_site_wizard_button'), 9 );
		add_action( 'greyd_hub_page_after_content', array($this, 'render_new_site_wizard'), 10, 2 );

		//
		// migration wizard
		add_action( 'greyd_hub_page_after_title', array($this, 'render_hub_wizard_button'), 50 );
		add_action( 'greyd_hub_page_after_content', array($this, 'render_hub_wizard'), 10, 2 );
		// hub website tile
		add_filter( "greyd_hub_tile_head", array($this, 'add_tile_page_head_row'), 10, 3 );
		add_filter( "greyd_hub_tile_page_action", array($this, 'add_tile_page_action_row'), 10, 3 );

	}


	/*
	=======================================================================
		Hub: Overlay
	=======================================================================
	*/

	public static $rendered = false;
	public static $overlay = [
		"show"      => false,           // bool:    whether to render the overlay on load
		"action"    => 'confirm',       // string:  the secondary action, (loading | confirm | success | fail)
		"class"     => 'reset_mods',    // string:  the class, which filters the content to be displayed (eg. 'export_site', 'check_greyd' ...)
		"replace"   => ''               // string:  a custom string, which replaces a word in the displayed text via sprintf();
	];

	/**
	 * Render a short overlay for a single message and an icon
	 *
	 * the overlay params are defined in the class var $overlay
	 */
	public function render_overlay( $current_tab ) {

		// return if overlay already rendered
		if (self::$rendered) return false;
		self::$rendered = true;

		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );

		$overlay = wp_parse_args( self::$overlay, array(
			"show"      => false,
			"action"    => 'confirm',
			"class"     => 'reset_mods',
			"replace"   => ''
		) );

		if ( $overlay['show'] === true ) {
			echo "<script> var trigger_overlay = [ true, { type: '{$overlay['action']}', css: '{$overlay['class']}', replace: '{$overlay['replace']}' } ]; </script>";
		}
	}

	/**
	 * Add overlay contents
	 * 
	 * @see filter 'greyd_overlay_contents'
	 * 
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {

		$contents = array_merge( $contents, array(
			'export_content' => [
				"loading" => [
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Exporting content.", 'greyd_hub')
				],
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed.", 'greyd_hub')
				]
			],
			'import_content' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" all content will be deleted and replaced. In any case, create a backup beforehand so that no files are lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import content", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading files...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("The content was successfully uploaded and installed.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("The content could not be installed. The selected file has errors.", 'greyd_hub')
				]
			],

			'export_site' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Exporting website.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed.", 'greyd_hub')
				]
			],
			'import_site' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" all content will be deleted and replaced. In any case, create a backup beforehand so that no files are lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import website", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading files...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("The website has been uploaded and installed successfully.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("The website could not be installed. The selected file has errors.", 'greyd_hub')
				]
			],

			'setup_greyd' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" Greyd.Suite will be activated.<br>Create a backup beforehand so that no content is lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Activate Greyd.Suite", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Activating Greyd.Suite.", 'greyd_hub')
				),

				"reload" => [
					"title" => __("Activation successful", 'greyd_hub'),
					"descr" => __("Greyd.Suite has been activated.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Activation failed", 'greyd_hub'),
					"descr" => __("Greyd.Suite could not be activated. You may be missing the necessary permissions or the page may have technical difficulties.", 'greyd_hub')
				]

			],
			'check_greyd' => [
				"confirm" => [
					"descr" => sprintf(__("The database of the domain \"%s\" will be checked for errors.<br>If an error is found, it will automatically be corrected.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Repair database", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Reviewing database for errors.", 'greyd_hub')
				),

				"success" => [
					"title" => __("Review successful", 'greyd_hub'),
					"descr" => __("No errors have been found in the database.", 'greyd_hub')
				],
				"reload" => [
					"title" => __("Review successful", 'greyd_hub'),
					"descr" => __("Errors have been found in the database and automatically fixed:", 'greyd_hub')."<br><b class='replace'></b>",
				],
				"fail" => [
					"title" => __("Review failed", 'greyd_hub'),
					"descr" => __("The database could not be verified. You may be missing the necessary permissions or the page may have technical difficulties.", 'greyd_hub')
				]
			],
			'init_greyd' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" Greyd.Suite  will be reset to the base state.<br>Create a backup beforehand so that the content is not lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Reset Greyd.Suite", 'small', 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Resetting Greyd.Suite.", 'greyd_hub')
				),

				"reload" => [
					"title" => __("Reset successful", 'greyd_hub'),
					"descr" => __("Greyd.Suite has been reset to its base state.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Reset failed", 'greyd_hub'),
					"descr" => __("Greyd.Suite could not be reset. You may be missing the necessary permissions or the page may have technical difficulties.", 'greyd_hub')
				]
			],

			'reset_website' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" all content and settings will be reset.<br>Make a backup beforehand so that the content is not lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Reset website", 'small', 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Resetting page.", 'greyd_hub')
				),

				"reload" => [
					"title" => __("Reset successful", 'greyd_hub'),
					"descr" => __("The page has been reset to its base state.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Reset failed", 'greyd_hub'),
					"descr" => __("The page could not be reset. You may be missing the necessary permissions or the page may have technical difficulties.", 'greyd_hub')
				]
			],
			'delete_website' => [
				"confirm" => [
					"descr" => sprintf(__("The page on the domain \"%s\" will be completely deleted.<br>Make a backup beforehand so that no content is lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Delete site", 'small', 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Deleting page.", 'greyd_hub')
				),

				"success" => [
					"title" => __("Deletion successful", 'greyd_hub'),
					"descr" => __("The website has been permanently deleted.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Deletion failed", 'greyd_hub'),
					"descr" => __("The page could not be deleted. You may be missing the necessary permissions or the page may have technical difficulties.", 'greyd_hub')
				]
			],


			'export_mods' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Design settings are exported.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. The file may be corrupted or empty.", 'greyd_hub')
				]
			],
			'reset_mods' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("The design settings will be reset on the domain \"%s\".<br>It is best to create a backup beforehand so that the settings are not lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Reset settings", 'small', 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Design settings are reset.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Reset successful", 'greyd_hub'),
					"descr" => __("The website's design settings have been reset to its base state.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Reset failed", 'greyd_hub'),
					"descr" => __("The website's design settings could not be reset. You may be missing the necessary permissions or the website may have technical difficulties.", 'greyd_hub')
				]
			],
			'import_mods' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("By importing the selected design settings, your current design on the domain \"%s\" will be overwritten.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import settings", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading design settings...", 'greyd_hub')
				),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("The website's design settings have been replaced with the imported settings.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("The selected design settings could not be imported. The selected file is invalid or corrupted.", 'greyd_hub')
				]
			],
			'import_styles' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("By importing the selected design settings, your current design on the domain \"%s\" will be overwritten.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import settings", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading design settings...", 'greyd_hub')
				),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("The website's design settings have been replaced with the imported settings.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("The selected design settings could not be imported. The selected file is invalid or corrupted.", 'greyd_hub')
				]
			],
			'export_files' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Media & files are exported.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. The uploads folder is empty or has been moved.", 'greyd_hub')
				]
			],
			'import_files' => [
				"confirm" => [
					"descr" => sprintf(__("On the domain \"%s\" all current media & files (folder \"uploads\") will be deleted and replaced. In any case, create a backup of the media beforehand so that no files are lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import media", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading files...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("Media & files have been uploaded successfully.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("Media & files could not be uploaded. The selected file has errors.", 'greyd_hub')
				]
			],
			'export_plugins' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Exporting plugins.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. The \"plugins\" folder is empty or has been moved.", 'greyd_hub')
				]
			],
			'import_plugins' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("All existing plugins on the domain \"%s\" (folder: \"plugins\") are going to be replaced. It is best to make a backup of the plugins beforehand.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import plugins", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading files...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("Plugins successfully uploaded.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("Plugins could not be imported. The selected file has errors.", 'greyd_hub')
				]
			],
			'export_themes' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Themes are exported.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. The \"themes\" folder is empty or has been moved.", 'greyd_hub')
				]
			],
			'import_themes' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("On the domain \"%s\" all existing themes (folder \"themes\") will be replaced. In any case, make a backup of the themes beforehand so that the files do not get lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import themes", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading files...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("Themes have been uploaded successfully.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("Themes could not be uploaded. The selected file has errors.", 'greyd_hub')
				]
			],
			'export_db' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Downloading database tables.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. You may be missing the necessary permissions or the website may have technical difficulties.", 'greyd_hub')
				]
			],
			'import_db' => [
				"confirm" => [
					"descr" => sprintf(__("On the domain \"%s\" all database tables will be deleted and replaced. In any case, create a backup of the database beforehand so that the data is not lost.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Import database", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Uploading file...", 'greyd_hub')
				),
				"fetching" => __("Files are being prepared...", 'greyd_hub'),
				"success" => [
					"title" => __("Import successful", 'greyd_hub'),
					"descr" => __("All database tables have been successfully replaced.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Import failed", 'greyd_hub'),
					"descr" => __("The database tables could not be imported. You may be missing the necessary permissions or the website may have technical difficulties.", 'greyd_hub')
				]
			],
			'export_full' => [
				"loading" => [
					"descr" => __("The backup is being prepared. This may take a few minutes. Please do not update the page.", 'greyd_hub'),
					"link" => __("Cancel backup", 'greyd_hub')
				],
				"success" => [
					"title" => __("Backup successful.", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Backup failed", 'greyd_hub'),
					"descr" => __("The download could not be performed. The server's response time has been exceeded or you are missing the necessary permissions.", 'greyd_hub')
				]
			],
			'export_tables' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("Do you want to export the selected tables \"%s\"?", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Export tables", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Export is being prepared.", 'greyd_hub')
				),
				"success" => [
					"title" => __("Export successful.", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Export failed", 'greyd_hub'),
					"descr" => __("The download could not be prepared. The server's response time has been exceeded or you are missing the necessary permissions.", 'greyd_hub')
				]
			],
			'import_tables' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => __("All tables that are included in the SQL file will be added. This can overwrite existing tables. In any case, create a backup of the database beforehand so that the data is not lost.", 'greyd_hub'),
					"button" => _x("Import tables", 'small', 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Import is being prepared.", 'greyd_hub')
				),
			],
			'delete_tables' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("Do you want to permanently delete the selected tables \"%s\"?", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Delete tables", 'small', 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Deleting tables...", 'greyd_hub')
				)
			],
			'download' => [
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Download is being prepared...", 'greyd_hub')
				),
				"success" => [
					"title" => __("File found.", 'greyd_hub'),
					"descr" => __("The download starts automatically. If not, check your internet connection and try again.", 'greyd_hub')
				],
				"fail" => [
					"title" => __("Download failed", 'greyd_hub'),
					"descr" => __("The file could not be found. It may have been moved or deleted.", 'greyd_hub')
				]
			],
			'delete' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("Do you want to delete the file \"%s\"?", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => __("Delete file", 'greyd_hub'),
					"button_class" => "danger"
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => __("Deleting file...", 'greyd_hub')
				),
				"success" => [
					"title" => __("Deletion successful", 'greyd_hub'),
					"descr" => sprintf(__("The file \"%s\" has been deleted.", 'greyd_hub'), "<b class='replace'></b>" ),
				],
				"fail" => [
					"title" => __("Deletion failed", 'greyd_hub'),
					"descr" => sprintf(__("The file \"%s\" could not be deleted. It may have been moved or deleted.", 'greyd_hub'), "<b class='replace'></b>" ),
				]
			],
			'relocation' => [
				"confirm" => [
					"title" => __("Are you sure?", 'greyd_hub'),
					"descr" => sprintf(__("The migration of %s is completed. The landing page is overwritten.", 'greyd_hub'), "<b class='replace'></b>" ),
					"content" => "<div class='hidden remote-destination-info'>".Helper::render_info_box([
						"style" => "info",
						"above" => __("Destination page is on a different installation.", "greyd_hub"),
						"text"  => sprintf(
							__("For security reasons, you will be redirected to the Greyd.Hub page of the destination during the migration. Please make sure that you are logged in on the destination: %s", "greyd_hub"),
							'<a href="" target="_blank"></a>'
						)
					])."</div>",
					"button" => __("Start migration", 'greyd_hub')
				],
				"loading" => array(
					"title" => __("Please wait...", 'greyd_hub'),
					"descr" => sprintf(__("Preparing to move %s...", 'greyd_hub'), "<b class='replace'></b>" ),
				),
				"fail" => [
					"title" => __("Migration failed", 'greyd_hub'),
					"descr" => __("The page could not be migrated.", 'greyd_hub')
				]
			],
			'upload' => array(
				"fail" => [
					"title" => __("Upload failed", 'greyd_hub'),
					"descr" => sprintf(__("The file could not be uploaded for the following reason: %s Please contact your hoster or administrator to fix the error.", 'greyd_hub'), "<b class='replace'></b>" ),
				]
			),

			'upload_file' => array(
				"confirm" => array(
					"title" => __("Upload file?", 'greyd_hub'),
					"descr" => sprintf(__("The file %s is uploaded, then the import process will be started.", 'greyd_hub'), "<b class='replace'></b>" ),
					"button" => _x("Upload file", 'small', 'greyd_hub')
				),
				"loading" => array(
					"title" => __("Uploading file...", 'greyd_hub'),
					"descr" => '
					<div class="uploader">
						<p class="uploader-status"
							data-empty="'.__("Download is being prepared...", 'greyd_hub').'" 
							data-wait="'.__("Remaining time is calculated...", 'greyd_hub').'" 
							data-pausing="'.__("Pausing...", 'greyd_hub').'" 
							data-paused="'.__("Upload is paused.", 'greyd_hub').'" 
							data-abort="'.__("Aborting...", 'greyd_hub').'" 
							data-seconds="'.__("Approx. %s seconds remaining", 'greyd_hub').'"
							data-minutes="'.__("Approx. %s minutes remaining", 'greyd_hub').'"
						>'.__("Download is being prepared...", 'greyd_hub').'</p>
						<div class="inner_content">
							<div class="uploader-wrapper">
								'.Helper::render_dashicon("media-archive uploader-file-icon").'
								<!--<span class="uploader-file-icon dashicons dashicons-media-archive"></span>-->
								<div>
									<div class="flex">
										<div class="uploader-percent">42%</div>
										<div class="uploader-controls">
											'.Helper::render_dashicon("controls-pause uploader-pause").'
											<!--<span class="uploader-pause dashicons dashicons-controls-pause"></span>-->
											<!--<span class="uploader-abort dashicons dashicons-no"></span>-->
										</div>
									</div>
									<div class="uploader-progress-container">
										<div class="uploader-progress-bar"></div>
									</div>
									<div class="uploader-file-info flex">
										<span class="uploader-file-title"></span>
										<span class="uploader-file-size"></span>
									</div>
								</div>
							</div>
						</div>
					</div>',
				),
				"success" => array(
					"title" => __("Upload successful", 'greyd_hub'),
					"descr" => __("The file has been uploaded successfully, the import process is being started.", 'greyd_hub')
				),
				"fail" => array(
					"title" => __("Upload failed", 'greyd_hub'),
					"descr" => sprintf(__("The file could not be uploaded for the following reason: %s", 'greyd_hub'), "<b class='replace'></b>" ).'<br>'.
								__("The import process is not started.", 'greyd_hub'),
				),
				"abort" => array(
					"title" => __("Upload canceled", 'greyd_hub'),
					"descr" => __("The file was not uploaded, the import process is aborted.", 'greyd_hub')
				)
			),
		) );

		return $contents;
	}

	/**
	 * Render a small loading overlay on admin_init, when any content is uploading right now...
	 * tbd: useful when post&loggin in admin_footer - deprecated if in admin_head
	 */
	public function maybe_render_loading_overlay() {
		if ( isset($_GET['page']) && $_GET['page'] === 'greyd_hub' && isset($_POST['submit']) ) {

			if ( !empty($_FILES) ) {
				$descr = __("Uploading files...", 'greyd_hub');
			}
			else if ( strpos($_POST['submit'], 'xport') !== false || strpos($_POST['submit'], 'ownload') !== false ) {
				$descr = __("Download is being prepared...", 'greyd_hub');
			}
			else {
				$descr = __("Operation is executed...", 'greyd_hub');
			}

			echo '<div id="init-loader" class="greyd_overlay">
				<div class="loading">
					<h3>'.__("Please wait...", 'greyd_hub').'</h3>
					<p>'.$descr.'</p>
					<div class="loader"></div>
					<a href="javascript:window.location.href=window.location.href" class="color_light escape">'.__("Cancel", 'greyd_hub').'</a>
				</div>
			</div>';
		}
	}


	/*
	=======================================================================
		Log Overlay
	=======================================================================
	*/

	public static $log_started = false;
	public static $log_ended = false;

	/**
	 * Add defautl log overlay overlay contents
	 * 
	 * @see filter 'greyd_hub_log_contents'
	 * 
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_log_contents( $contents ) {
		
		$contents = array_merge( $contents, array(
			'import' => array(
				'loading' => __("Importing data...", 'greyd_hub'),
				'success' => __("Import successful:", 'greyd_hub'),
				'fail' => __("Failed to import:", 'greyd_hub'),
				'abort' => __("There were errors during the import.", 'greyd_hub'),
			),
			'export' => array(
				'loading' => __("Exporting data...", 'greyd_hub'),
				'success' => __("Export successful:", 'greyd_hub'),
				'fail' => __("Failed to export:", 'greyd_hub'),
				'abort' => __("Errors occurred when exporting.", 'greyd_hub'),
			),
			'delete' => array(
				'loading' => __("Deleting tables...", 'greyd_hub'),
				'success' => __("Deletion successful:", 'greyd_hub'),
				'fail' => __("Failed to delete:", 'greyd_hub'),
				'abort' => __("Errors occurred during deletion.", 'greyd_hub'),
			),
			// new site
			'create' => array(
				'loading' => __("Website is registered...", 'greyd_hub'),
				'success' => __("Registration successful:", 'greyd_hub'),
				'fail' => __("Error during registration:", 'greyd_hub'),
				'abort' => __("There were errors during registration.", 'greyd_hub'),
			),
		) );

		return $contents;
	}

	public static function log_start( $action="import loading" ) {

		// return if log already started
		if ( self::$log_started ) return;
		self::$log_started = true;

		/**
		 * Get all log overlay contents
		 * 
		 * @filter greyd_hub_log_contents
		 * Example:
		 * array(
		 *		'export' => array(
		 *			'loading' => __("Exporting data…", 'greyd_hub'),
		 *			'success' => __("Export successful:", 'greyd_hub'),
		 *			'fail' => __("Failed to export:", 'greyd_hub'),
		 *			'abort' => __("Errors occurred when exporting.", 'greyd_hub'),
		 *		)
		 *  )
		 */
		$log_contents = apply_filters( 'greyd_hub_log_contents', array() );

		// make log overlay (first part of markup)
		$overlay = 
		// '<script> document.getElementById("init-loader").remove(); </script>'.
		'<div id="log" class="greyd_overlay large">'.
			'<div class="default">';

		foreach ($log_contents as $mode => $strings) {
			if (isset($strings['loading'])) $overlay .= '<h3 class="depending '.$mode.'-loading '.(strpos($action, $mode) === 0 && strpos($action, 'loading') !== false ? '' : 'hidden').'">'.$strings['loading'].'</h3>';
			if (isset($strings['success'])) $overlay .= '<h3 class="depending '.$mode.'-success '.(strpos($action, $mode) === 0 && strpos($action, 'success') !== false ? '' : 'hidden').'">'.$strings['success'].'</h3>';
			if (isset($strings['fail']))    $overlay .= '<h3 class="depending '.$mode.'-fail '   .(strpos($action, $mode) === 0 && strpos($action, 'fail')    !== false ? '' : 'hidden').'">'.$strings['fail'].'</h3>';
		}

		$overlay .= '<div class="depending loading '.($action === 'loading' ? '' : 'hidden').'">
						<div class="loader absolute"></div>
					</div>
					<div class="log loading"><div class="loader" style="align-items: center"></div><div>';

		// start outbut buffering for logging overlay
		ob_start();
		
		// Force the browser to flush
		echo str_pad( $overlay, 4096 );

		// flush the output buffer
		ob_flush();
		flush();

	}

	public static function log_message( $message, $mode='info', $list=false ) {
		
		// don't render if no log is open
		if (!self::$log_started || self::$log_ended) return;

		// return if no message
		if (empty($message) || !is_string($message)) return;

		/**
		 * Log additional debug messages with $mode == 'debug' (default: false)
		 * @filter 'greyd_hub_process_log_debug'
		 */
		if ( apply_filters( 'greyd_hub_process_log_debug', false ) == false && $mode == 'debug' ) return;

		// render message
		$classes = array( 'nag' );
		if (!empty($mode)) $classes[] = $mode;
		if (!$list && $mode != 'debug' ) $classes[] = 'large';
		$msg = '<span class="'.implode(' ', $classes).'">'.$message.self::log_memory().'</span>';

		// Force the browser to flush
  		// by padding the string to 4096 bytes
		echo str_pad( $msg, 4096 );

		// flush the output buffer
		ob_flush();
		flush();

	}

	public static function log_memory() {

		/**
		 * Log script memory usage with every message (default: false)
		 * @filter 'greyd_hub_process_log_memory'
		 */
		if ( apply_filters( 'greyd_hub_process_log_memory', false ) == false ) return '';

		// error_log(self::get_memory_usage());
		return '<code>'.self::get_memory_usage().'</code>';
	}
	public static function get_memory_usage() {
		$usage = Hub_Helper::format_bytes( memory_get_usage(true) );
		$peak = Hub_Helper::format_bytes( memory_get_peak_usage(true) );
		$limit = ini_get( 'memory_limit' );
		return sprintf(__("memory: %s of %s (%s peak)", 'greyd_hub'), $usage, $limit, $peak);
	}

	public static $progress = array();
	public static function log_progress_start( $message ) {

		/**
		 * Log progress of large process in progress-bar (default: false)
		 * @filter 'greyd_hub_process_log_progress'
		 */
		if ( apply_filters( 'greyd_hub_process_log_progress', false ) == false ) {
			self::log_message($message, '');
			return;
		}

		$id = uniqid('progress_');
		self::$progress[$id] = 0;
		self::log_message($message.'<span id="'.$id.'" class="progress"></span>', '');
		return $id;
	}
	public static function log_progress( $id, $download_size, $downloaded ) {

		// don't render if no log is open
		if (!self::$log_started || self::$log_ended) return;
		
		/**
		 * Log progress of large process in progress-bar (default: false)
		 * @filter 'greyd_hub_process_log_progress'
		 */
		if ( apply_filters( 'greyd_hub_process_log_progress', false ) == false ) return;
		if ( !isset($id) || empty($id) ) return;

		if ( !isset(self::$progress[$id]) ) self::$progress[$id] = 0;
		$percent = $download_size > 0 ? $downloaded / $download_size  * 100 : 0;

		if ( (int)$percent > self::$progress[$id] ) {
			self::$progress[$id] = $percent;
			$msg = '<style>#'.$id.' { width: '.$percent.'% }</style>';

			// Force the browser to flush
			// by padding the string to 4096 bytes
			echo str_pad( $msg, 4096 );

			// flush the output buffer
			ob_flush();
			flush();
		}

	}

	public static function log_end( $action="" ) {
		
		// return if log already ended
		if ( self::$log_ended ) return;
		self::$log_ended = true;
		
		// html part
		echo	'</div></div>
				<div class="depending success '.($h = strpos($action, 'success') !== false ? '' : 'hidden').'" style="text-align:right;">
					<button role="escape" class="button large">'.__("Close", 'greyd_hub').'</button>
				</div>
				<div class="depending fail '.($h = strpos($action, 'fail') !== false ? '' : 'hidden').'" style="text-align:right;">
					<button role="escape" class="button large">'.__("Close", 'greyd_hub').'</button>
				</div>
			</div>
		</div>';
		// script
		echo '<script>
				document.querySelector(".log.loading").classList.remove("loading");
				console.log("'.self::get_memory_usage().'");
			</script>';
		if ($action != "") echo "<script>var trigger_log_overlay = [true, {type: 'default', css: '".$action."', id: 'log'}];</script>";

		// flush the output buffer
		ob_flush();
		flush();

		// end output buffering
		ob_end_flush();

	}

	public static function log_abort( $message, $action='import' ) {

		self::log_start( $action.' fail' );

		self::log_message( $message, 'danger' );
		
		/**
		 * Get all log overlay contents
		 * 
		 * @filter greyd_hub_log_contents
		 * @see log_start
		 */
		$log_contents = apply_filters( 'greyd_hub_log_contents', array() );

		if ( isset($log_contents[$action]['abort']) && !empty($log_contents[$action]['abort']) ) {
			self::log_message( "<h5>".$log_contents[$action]['abort']."</h5><p>".__("Please contact a network administrator to fix the problem.", 'greyd_hub')."</p>", 'danger');
		}

		self::log_end( $action.' fail' );

	}


	/*
	=======================================================================
		wizard post actions
	=======================================================================
	*/

	/**
	 * Handle Post data for wizards.
	 * @see action 'greyd_hub_handle_post_data'
	 */
	public function handle_wizard_post_data( $post_data ) {

		// relocation
		if ($post_data['submit'] == "relocation") {
			
			$args = wp_parse_args( $post_data, array(
				"source"        => "",      // "{network}|{blogid}"
				"clean"         => "off",   // "off"|"on"
				"destination"   => "",      // "{network}|{blogid}"
				"backup"        => "off",   // "off"|"on"
				"redirect_url"  => ""
			) );
			
			if ( empty($args["source"]) || empty($args["destination"]) ) return false;
			if ( strpos($args["source"], "|") === false || strpos($args["destination"], "|") === false ) return false;

			$source         = $args["source"];
			$destination    = $args["destination"];

			$tmp = explode("|", $source, 2);
			$network_from = $tmp[0];
			$blogid_from  = intval( $tmp[1] );
			$domain_from  = esc_url( get_site_url( $blogid_from ) );

			$tmp = explode("|", $destination, 2);
			$network    = $tmp[0];
			$blogid     = intval( $tmp[1] );
			$domain     = esc_url( get_site_url( $blogid ) );
			$clean      = $args["clean"] === "on";
			$backup     = $args["backup"] === "on";

			// automatically use the fullsite when moving from/to different installations.
			$mode = 'content';
			if ( $network_from != $network ) $mode = 'site';

			// if (
			// 	strpos( $source, Hub_Helper::get_nice_url(Admin::$urls->network_url).'|' ) === 0 || 
			// 	strpos( $destination, Hub_Helper::get_nice_url(Admin::$urls->network_url).'|' ) === 0
			// ) {
			// 	$mode = 'site';
			// }

			// render logging overlay
			Log::log_start();
			Log::log_message("<h5>".sprintf(__("Preparing move from '%s' to '%s'", 'greyd_hub'), $domain_from, $domain)."</h5>", 'info');
			
			// make backup
			if ( $backup ) {
				Log::log_message(sprintf(__("Creating backup of %s.", 'greyd_hub'), $domain_from), 'info');
				Tools::download_blog( array(
					'mode' => $mode,
					'blogid' => $blogid,
					'domain' => $domain,
					'trigger' => false,
					'clean' => false,
					'unique' => true,
					'log' => false
				) );
			}

			// import the site
			// Log::log_message(__("Import wird gestartet.", 'greyd_hub'), 'info');
			$result = Tools::import_blog( array(
				'mode' => $mode, 
				'blogid' => $blogid, 
				'domain' => $domain, 
				'source' => $source, 
				'clean' => $clean,
				'init_log' => false
			) );

			// final log
			$domain_link = sprintf( '<a href="%s" target="_blank">%s</a>', $domain, $domain );
			Log::log_message("<h5>".__("Migration successful.", 'greyd_hub')."</h5><span>".sprintf(__("The website %s was successfully installed on the domain \"%s\".", 'greyd_hub'), "<b>".$domain_from."</b>", $domain_link)."</span>", 'success');
			
			// close overlay
			Log::log_end('import success');

			// reset other overlays that are maybe set
			Log::$overlay = array(
				"show"      => false,
				"action"    => 'loading',
				"class"     => 'relocation'
			);
		}

		// new site
		else if ($post_data['submit'] == "new-site") {

			// check data
			if ( 
				!isset($post_data["blog"]) || 
				empty($post_data["blog"]) ||
				empty($post_data["blog"]['domain']) || 
				empty($post_data["blog"]["title"])
			) {
				Log::log_abort(__( "It is not possible to create a blank website.", 'greyd_hub' ), "create");
				return false;
			}

			// get data
			// debug($post_data);
			$args = wp_parse_args( $post_data["blog"], array(
				"domain"      => "",
				"title"       => "",
				"description" => "",
				"lang"        => get_site_option( 'WPLANG' ),
				"user"        => get_current_user_id()
			) );
			// debug($args);

			// make options
			$options = array( 'WPLANG' => $args["lang"] );
			if (!empty($args["description"])) $options["blogdescription"] = $args["description"];
			if (isset($args["public"]) && $args["public"] == "1") $options["public"] = "1";
			if (isset($args["archived"]) && $args["archived"] == "1") $options["archived"] = "1";
			if (isset($args["spam"]) && $args["spam"] == "1") $options["spam"] = "1";
			if (isset($args["deleted"]) && $args["deleted"] == "1") $options["deleted"] = "1";
			if (isset($args["mature"]) && $args["mature"] == "1") $options["mature"] = "1";
			// debug($options);

			// create new blog
			$newblog = Tools::create_blog( array(
				"domain" => $args['domain'],
				"title" => $args["title"],
				"options" => $options,
				"user_id" => $args["user"],
				"log" => true,
			) );

		}

	}

	/*
	=======================================================================
		Relocation Wizard
	=======================================================================
	*/

	/**
	 * Add Button to Hub Website Tile head. (list_view__only)
	 * @see filter 'greyd_hub_tile_head'
	 * 
	 * @param array $rows	All rows.
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 * @return array $rows
	 */
	public function add_tile_page_head_row( $rows, $blog, $vars ) {

		if ( is_multisite() || Admin::$connections ) {
			$rows[] = array(
				'slug' => 'migration',
				'content' =>
					"<div class='flex flex-center'><span class='button button-dark list_view__only' data-event='wizard' data-args='".$blog['network']."|".$blog['blog_id']."' title='"._x("Migration", "small", 'greyd_hub')."'><span>".__("Migration", 'greyd_hub')."</span>".$vars['icons']->move."</span></div>",
				'priority' => 3
			);
		}

		return $rows;
	}

	/**
	 * Add Button to Hub Website Tile Page 'Actions'.
	 * @see filter 'greyd_hub_tile_page_action'
	 * 
	 * @param array $rows	All rows.
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 * @return array $rows
	 */
	public function add_tile_page_action_row( $rows, $blog, $vars ) {

		if ( is_multisite() || Admin::$connections ) {
			// migration button
			$btn = "<span class='button button-dark small list_view__hide' data-event='wizard' data-args='".$blog['network']."|".$blog['blog_id']."' title='"._x("Migration", "small", 'greyd_hub')."'><span>".__("Migration", 'greyd_hub')."</span>".$vars['icons']->move."</span>";
			// inject button into 'site' row
			foreach ( $rows as $i => $row ) {
				if ($row['slug'] == 'site') {
					$rows[$i]['content'] = str_replace( '</form>', '</form>'.$btn, $row['content'] );
					break;
				}
			}
			// // add button in new row
			// $rows[] = array(
			// 	'slug' => 'migration',
			// 	'content' =>
			// 		"<div class='flex inner_head'>".
			// 			"<p></p>".
			// 			"<small>".__("Umzug der ganzen Seite", 'greyd_hub')."</small>".
			// 		"</div>".
			// 		$btn,
			// 	'priority' => 0
			// );
		}

		return $rows;
	}

	/**
	 * render the hub 'relocation' wizard button.
	 * @see action 'greyd_hub_page_after_title'
	 */
	public function render_hub_wizard_button( $current_tab ) {

		// only 'websites' tab
		if ( $current_tab != 'websites' ) return;

		// only when there are other sites
		if ( !is_multisite() && !Admin::$connections ) return;

		if ( !Admin::$is_standalone && (is_multisite() || Admin::$connections) ) {
			// todo add by filter (greyd_hub_page_title)
			echo "<a class='page-title-action button-dark small' data-event='wizard'>".__("Migration Assistant", 'greyd_hub').'&nbsp;<span class="dashicons dashicons-arrow-right-alt"></span></a>';
		}
	}

	/**
	 * Render the hub 'relocation' wizard.
	 * @see action 'greyd_hub_page_after_content'
	 */
	public function render_hub_wizard( $current_tab, $nonce ) {

		// only 'websites' tab
		if ( $current_tab != 'websites' ) return;

		// only when there are other sites
		if ( !is_multisite() && !Admin::$connections ) return;

		// get all blogs
		$networks = Hub_Helper::get_basic_networks();
		// debug($networks);

		// make options
		$options = self::make_options( $networks );

		$content = "<form id='hub-relocation' class='split-view flex' method='post' enctype='multipart/form-data' action=''>".$nonce."
			<div class='preview-wrap'>
				<h3>".__("Source", 'greyd_hub')."</h3>
				<div class='input-wrap'>
					<label for='relocation--source'>".__("Select website", 'greyd_hub')."</label><br>
					<select name='source' id='relocation--source'>
						<option value=''>"._x("Please select", "small", 'greyd_hub')."</option>
						".$options."
					</select>
				</div>
				<div class='input-wrap flex'>
					<span class='components-form-toggle'>
						<input class='components-form-toggle__input' type='checkbox' name='clean' id='relocation--clean' checked>
						<span class='components-form-toggle__track'></span>
						<span class='components-form-toggle__thumb'></span>
					</span>
					<label for='relocation--clean'>
						<strong>".__("Use cleaned data and content", 'greyd_hub')."</strong><br>
						<small class='color_light'>".__("Historical data such as form entries, user roles or post revisions are excluded, some plugin settings are removed and all media that do not also appear in the media library are ignored. Suitable for moving an optical copy of this site.", 'greyd_hub')."</small>
					</label>
				</div>
				<div class='input-wrap'>
					<label>".__("Selected website", 'greyd_hub')."</label>
					<div class='greyd_tile'>
						<div class='hub_frame_wrapper empty' data-empty='".__("Choose a website", 'greyd_hub')."'>
							<div class='hub_frame' data-siteurl=''>
								<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
							</div>
							<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
						</div>
						<div class='hub_domain'>
							<a class='replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
								<span class='replace' data-replace='domain' data-empty='".__("URL of the source", 'greyd_hub')."'></span>
							</a>
						</div>
						<hr>
						<div class='hub_title'>
							<h4>
								<span class='replace' data-replace='name' data-empty='".__("Title of the source", 'greyd_hub')."'></span>
							</h4>
							<h5>
								<span class='replace' data-replace='description' data-empty='".__("Subtitle of the source", 'greyd_hub')."'></span>
							</h5>
						</div>
					</div>
					<p><small class='color_light'>".__("This website remains unaffected.", 'greyd_hub')."</small></p>
				</div>
			</div>
			<div class='split-arrow'></div>
			<div class='preview-wrap'>
				<h3>".__("Destination", 'greyd_hub')."</h3>
				<div class='input-wrap'>
					<label for='relocation--destination'>".__("Select website", 'greyd_hub')."</label><br>
					<select name='destination' id='relocation--destination'>
						<option value=''>"._x("Please select", "small", 'greyd_hub')."</option>
						".$options."
					</select>
				</div>
				<div class='input-wrap flex'>
					<span class='components-form-toggle'>
						<input class='components-form-toggle__input' type='checkbox' name='backup' id='relocation--backup' checked>
						<span class='components-form-toggle__track'></span>
						<span class='components-form-toggle__thumb'></span>
					</span>
					<label for='relocation--backup'>
						<strong>".__("Make a backup of the content beforehand", 'greyd_hub')."</strong><br>
						<small class='color_light'>".__("Creates a backup of the current state under the \"Backups\" tab. This way you can undo the changes if necessary.", 'greyd_hub')."</small>
					</label>
				</div>
				<div class='input-wrap'>
					<label>".__("Current state", 'greyd_hub')."</label>
					<div class='greyd_tile'>
						<div class='hub_frame_wrapper empty' data-empty='".__("Choose a website", 'greyd_hub')."'>
							<div class='hub_frame' data-siteurl=''>
								<iframe src='' width='1200px' height='768px' scrolling='yes' frameborder='0' loading='lazy'></iframe>
							</div>
							<a class='overlay replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'><span>".__("View website", 'greyd_hub')."&nbsp;".Helper::render_dashicon('external')."</a>
						</div>
						<div class='hub_domain'>
							<a class='replace_domain' href='' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>
								<span class='replace' data-replace='domain' data-empty='".__("URL of the destination", 'greyd_hub')."'></span>
							</a>
						</div>
						<hr>
						<div class='hub_title'>
							<h4>
								<span class='replace' data-replace='name' data-empty='".__("Title of the destination", 'greyd_hub')."'></span>
							</h4>
							<h5>
								<span class='replace' data-replace='description' data-empty='".__("Subtitle of the destination", 'greyd_hub')."'></span>
							</h5>
						</div>
					</div>
					<p><small><strong><span class='color_red'>".__("Attention:", 'greyd_hub')."</span> ".__("This website will be completely overwritten. This cannot be made undone. Make a backup before that.", 'greyd_hub')."</strong></small></p>
				</div>
			</div>
			<input type='hidden' name='redirect_url' value='".Hub_Helper::get_current_url()."'>
			<input type='submit' name='submit' value='relocation' class='hidden'>
		</form>";

		$overlay_args = array(
			"ID"        => "hub_wizard",
			"class"     => "hub_wizard",
			// "class"     => "is-active",
			"head"      => "<h3>".__("Migration Assistant", "greyd_hub")."</h3>",
			"content"   => $content,
			"foot"      => "<span id='btn_hub_wizard' class='button button-primary huge disabled'>".__("Start migration", 'greyd_hub')."</span>",
			// "sidebar"   => "<h4>Sidebar</h4>",
		);

		echo \Greyd\Admin::build_overlay( $overlay_args );
	}

	/*
	=======================================================================
		New Site Wizard
	=======================================================================
	*/

	/**
	 * render the 'add new site' wizard button.
	 * @see action 'greyd_hub_page_after_title'
	 */
	public function render_new_site_wizard_button( $current_tab ) {

		// only 'websites' tab
		if ( $current_tab != 'websites' ) return;

		// only on multisites
		if ( !is_multisite() ) return;

		// render 'add new site' button
		// link to site-new admin page the wp way
		// echo "<a href='".network_admin_url("site-new.php")."' class='page-title-action'>".__("Create new website", 'greyd_hub')."</a>";

		// use wizard
		echo "<a class='page-title-action' data-event='new-site'>".__("Create new website", 'greyd_hub').'&nbsp;<span class="dashicons dashicons-plus-alt2"></span></a>';
		
	}

	/**
	 * Render the 'add new site' wizard.
	 * @see action 'greyd_hub_page_after_content'
	 */
	public function render_new_site_wizard( $current_tab, $nonce ) {

		// only 'websites' tab
		if ( $current_tab != 'websites' ) return;

		// only on multisites
		if ( !is_multisite() ) return;

		// 'add new site' button
		// link to site-new admin page the wp way
		// echo "<section style='text-align:right;'>
		// 		<a href='".network_admin_url("site-new.php")."' class='button large'>".Helper::render_dashicon( "plus" )."&nbsp;".__("Create new website", 'greyd_hub')."</a>
		// 	</section>";

		// use wizard
		echo "<section>
				<a class='button large' data-event='new-site'>".Helper::render_dashicon( "plus" )."&nbsp;".__("Create new website", 'greyd_hub')."</a>
			</section>";

		// wizard content form
		$content = "<form id='new-site' class='flex' method='post' enctype='multipart/form-data' action=''>".$nonce."
			<table class='form-table' role='presentation'>
				<tbody>
					<tr class='form-field form-required'>
						<th scope='row'>
							<label for='site-address'>".__("Website URL", 'greyd_hub')." <span class='required'>*</span></label>
						</th>
						<td>".(is_subdomain_install() ? 
							"<input name='blog[domain]' type='text' id='site-address' autocapitalize='none' autocorrect='off' required=''><span class='no-break'>.".preg_replace( '|^www\.|', '', get_network()->domain )."</span>" :
							Hub_Helper::get_nice_url(Admin::$urls->network_url)."/ <input name='blog[domain]' type='text' id='site-address' autocapitalize='none' autocorrect='off' required=''>"
							)."<p class='description' id='site-address-desc'>".__("Only lowercase letters (a-z), numbers and hyphens are allowed.", 'greyd_hub')."</p>
						</td>
					</tr>
					<tr class='form-field form-required'>
						<th scope='row'>
							<label for='site-title'>".__("Website title", 'greyd_hub')." <span class='required'>*</span></label>
						</th>
						<td>
							<input name='blog[title]' type='text' id='site-title' required=''>
						</td>
					</tr>
					<tr class='form-field'>
						<th scope='row'>
							<label for='site-description'>".__("Subtitle", 'greyd_hub')."</label>
						</th>
						<td>
							<input name='blog[description]' type='text' id='site-description'>
							<p class='description' id='site-description-desc'>".__("Explain in a few words what your website is about.", 'greyd_hub')."</p>
						</td>
					</tr>
					<tr class='form-field'>
						<th scope='row'>".__("Attributes", 'greyd_hub')."</th>
						<td>
							<fieldset>
								<label><input type='checkbox' name='blog[public]' value='1'>".__("Public", 'greyd_hub')."</label><br>
								<label><input type='checkbox' name='blog[archived]' value='1'>".__("Archived", 'greyd_hub')."</label><br>
								<label><input type='checkbox' name='blog[spam]' value='1'>".__("Spam", 'greyd_hub')."</label><br>
								<label><input type='checkbox' name='blog[deleted]' value='1'>".__("Deleted", 'greyd_hub')."</label><br>
								<label><input type='checkbox' name='blog[mature]' value='1'>".__("Adult content", 'greyd_hub')."</label><br>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<input name='blog[lang]' type='hidden' value='".get_site_option( 'WPLANG' )."'>
			<input name='blog[user]' type='hidden' value='".get_current_user_id()."'>
			<input name='redirect_url' type='hidden' value='".Hub_Helper::get_current_url()."'>
			<input name='submit' type='submit' value='new-site' class='hidden'>
		</form>";

		$overlay_args = array(
			"ID"        => "new_site_wizard",
			"class"     => "hub_wizard small",
			"head"      => "<h3>".__("Create new website", "greyd_hub")."</h3>",
			"content"   => $content,
			"foot"      => "<span id='btn_new_site_wizard' class='button button-primary huge disabled'>".__("Create site", 'greyd_hub')."</span>",
		);

		// render
		echo \Greyd\Admin::build_overlay( $overlay_args );

	}



	public static function make_options( $networks ) {
		
		$options = "";
		foreach ( $networks as $network => $blogs ) {
			if ( count($networks) > 1 ) $options .= "<optgroup label='".$network."'>";
			foreach ( $blogs as $blog ) {
				if ($blog['blog_id'] < 1) continue;

				$action = isset($blog['action_url']) ? $blog['action_url'] : "";
				if ($action == Hub_Helper::get_current_url()) $action = "";
				$site_url = $blog['domain'];
				if ( isset($blog['http']) ) {
					$site_url = $blog['http']."://".$site_url;
				}
				else if ( $ret = parse_url($site_url) ) {
					if ( !isset($ret["scheme"]) ) $site_url = "https://".$site_url;
				}

				$options .= "<option
					value='".$network."|".$blog['blog_id']."'
					data-domain='".$site_url."'
					data-name='".$blog['name']."'
					data-description='".$blog['description']."'
					data-action='".$action."'
					".(isset($blog['disabled']) ? "disabled" : "")."
				>".$blog['domain']." — ".$blog['name']."</option>";
			}
			if ( count($networks) > 1 ) $options .= "</optgroup>";
		}

		return $options;

	}

	public static function make_toggle( $args ) {

		$checked = isset($args['value']) && $args['value'] === true ? "checked" : "";
		$description = isset($args['description']) ? "<small class='color_light'>".$args['description']."</small>" : "";

		$data = "";
		if (isset($args['data']) && is_array($args['data'])) {
			$data = array();
			foreach ($args['data'] as $key => $value) {
				$data[] = "data-".$key."='".$value."'";
			}
			$data = implode(" ", $data);
		}

		return 
		"<span class='components-form-toggle'>
			<input class='components-form-toggle__input' type='checkbox' name='".$args['name']."' id='".$args['id']."' ".$checked." ".$data." autocomplete='off'>
			<span class='components-form-toggle__track'></span>
			<span class='components-form-toggle__thumb'></span>
		</span>
		<label for='".$args['id']."' class='flex flex-vertical' style='display:flex'>
			<strong>".$args['label']."</strong>
			".$description."
		</label>";

	}
}