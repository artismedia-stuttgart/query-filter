<?php
/**
 * Greyd.Hub backups tab and page.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Backups_Page();
class Backups_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// add 'backups' page to hub
		add_filter( "greyd_hub_pages", array($this, 'add_hub_page') );
		add_action( "greyd_hub_handle_post_data", array($this, 'handle_post_data') );

	}

	/**
	 * Add Hub Page and Tab
	 * @see filter 'greyd_hub_pages'
	 */
	public function add_hub_page( $hub_pages ) {
		$hub_pages[] = array(
			"slug"      => "backups",
			"icon"      => "backup",
			"class"     => "wp",
			"title"     => __("Backups", 'greyd_hub'),
			"callback"  => array($this, "render"),
			"priority"	=> 1
		);
		return $hub_pages;
	}

	/**
	 * Handle Post data on this Page
	 * @see filter 'greyd_hub_handle_post_data'
	 */
	public function handle_post_data( $post_data ) {

		// downloads
		if ($post_data['submit'] == "submit_download_full" && isset($post_data['download_full'])) {
			Tools::download_full_wp($post_data['download_full'], true);
		}

		else if ($post_data['submit'] == "submit_download_db_full" && isset($post_data['download_db'])) {
			Tools::download_full_db($post_data['download_db'], true);
		}

		else if (strpos($post_data['submit'], "delete::") !== false && strpos($post_data['submit'], "delete::") == 0) {
			$name = str_replace("delete::", "", $post_data['submit']);
			Tools::delete_file($name);
		}

	}


	/*
	=======================================================================
		Hub Tab: Backups
	=======================================================================
	*/

	/**
	 * Render backups page
	 */
	public function render($nonce) {

		echo "<div class='hub_panel'>";

			// dashicons
			$icons = (object)array(
				"download"  => Helper::render_dashicon( "arrow-down-alt" ),
				"delete"    => Helper::render_dashicon( "trash" ),
			);

			// Backups and Downloads Buttons
			echo "<section>";
				echo "<h2>".__("Download backups:", 'greyd_hub')."</h2>";
				echo "<div class='flex flex-start' style='gap: 2em'>
						<form method='post' style='padding-right: 2em; border-right: 1px solid lightgray'>".$nonce."
							<input type='hidden' name='download_full' id='download_full' value='".Admin::$urls->network_url."'>
							<span class='button large' data-event='button' data-args='full'>".__("Backup of all files", 'greyd_hub').$icons->download."</span>
							<button type='submit' name='submit' value='submit_download_full' class='hidden'>download</button>
							
							<br><br><small>".__("All files (wp-config, wp-content, wp-includes...)", 'greyd_hub')."</small>
						</form>
						<form method='post'>".$nonce."
							<input type='hidden' name='download_db' id='download_db' value='".Admin::$urls->network_url."'>
							<span class='button large' data-event='button' data-args='full'>".__("Backup of the entire database", 'greyd_hub').$icons->download."</span>
							<button type='submit' name='submit' value='submit_download_db_full' class='hidden'>download</button>
							
							<br><br><small>".__("All database entries (wp-users, wp-options, wp-posts...)", 'greyd_hub')."</small>
						</form>
					</div>"; //hub_wrapper
			echo "</section>";

			echo "<section>";
				echo "<h2>".__("Individual files:", 'greyd_hub')."</h2>";

				// get files
				Hub_Helper::check_backup_path();
				$files = Hub_Helper::get_backup_files(true);

				//
				// Backups and Downloads Files
				echo "<div class='hub_table_wrapper'><form method='post'>".$nonce."
						<table class='greyd_table'>
							<thead><tr>
								<th>".__("Date / Name", 'greyd_hub')."</th>
								<th>".__("URL", 'greyd_hub')."</th>
								<th width='70px'>".__("Size", 'greyd_hub')."</th>
								<th>".__("Actions", 'greyd_hub')."</th>
							</tr></thead>
							<tbody>";
						$old_date = $new_date = "";
						if ( $files == null || count((array)$files) === 0 ) {
							echo "<tr><td colspan='5'><strong>".__("No files available.", 'greyd_hub')."</strong></td></tr>";
						} 
						else {
							foreach ($files as $files_by_day) {
								// make date row
								$date = $files_by_day[0]['date'];
								echo "<tr class='date_row'><td colspan='5'><strong>".$date."</strong></td></tr>";

								// make file rows
								foreach ($files_by_day as $file) {
									// maybe skip file
									if (
										substr($file['file'], -5) === ".json" ||
										substr($file['file'], -4) === ".png" ||
										substr($file['file'], -4) === ".jpg" ||
										strpos($file['file'], ".htaccess") !== false
									) {
										continue;
									}

									$size = Hub_Helper::format_bytes( filesize($file['file']) );
									if (strpos($file['file'], "/backup/user_fullsite_templates/") !== false ) {
										// fullsite template
										$tooltip = Helper::is_greyd_classic() ? Helper::render_info_dialog(
											"<p>".__("This file is part of a website template. You can download the content here.", 'greyd_hub')."</p>
											<p>".__("To download the entire template you have to use an FTP client to download the respective folder in /wp&#8209;content/backup/user_fullsite_templates/.", 'greyd_hub')."</p>
											<p>".__("You can manage your templates in the Template Library.", 'greyd_hub')."</p>"
										) : Helper::render_info_dialog(
											"<p>".__("This file is part of a website blueprint. You can download the content here.", 'greyd_hub')."</p>
											<p>".__("To download the entire blueprint you have to use an FTP client to download the respective folder in /wp&#8209;content/backup/user_fullsite_templates/.", 'greyd_hub')."</p>
											<p>".__("You can manage your blueprints in the \"Website blueprints\" modal, available from the Greyd.Hub or Dashboard.", 'greyd_hub')."</p>"
										);
										echo "<tr class='file_row template_row'>
												<td>".$tooltip."<a href='".$file['url']."' download>".$file['name']."</a></td>
												<td>".$file['url']."</td>
												<td>".$size."</td>
												<td>
												<a href='".$file['url']."' download class='button small'>".__("Download", 'greyd_hub').$icons->download."</a>
												</td>
											</tr>";
									} 
									else {
										// other download
										echo "<tr class='file_row'>
												<td><a href='".$file['url']."' download>".$file['name']."</a></td>
												<td>".$file['url']."</td>
												<td>".$size."</td>
												<td>
													<a href='".$file['url']."' download class='button small'>".__("Download", 'greyd_hub').$icons->download."</a>
													<span class='button small button-danger' data-event='button' data-args='trash'>".
														__("Delete", 'greyd_hub').$icons->delete.
													"</span>
													<button class='hidden' type='submit' name='submit' value='delete::".$file['name']."'>" . __( 'Delete', 'greyd_hub' ) . "</button>
												</td>
											</tr>";
									}
								}
							}
						}
					  echo "</tbody>
						</table>
					</form></div>";
			echo "</section>";
		echo "</div>";

	}

}