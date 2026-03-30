<?php
/**
 * Greyd.Hub database tab and page.
 */
namespace Greyd\Hub;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Database_Page();
class Database_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// add 'database' page to hub
		add_filter( "greyd_hub_pages", array($this, 'add_hub_page') );
		add_action( "greyd_hub_handle_post_data", array($this, 'handle_post_data') );

	}

	/**
	 * Add Hub Page and Tab
	 * @see filter 'greyd_hub_pages'
	 */
	public function add_hub_page( $hub_pages ) {
		$hub_pages[] = array(
			"slug"      => "database",
			"icon"      => "database",
			"title"     => __("Database", 'greyd_hub'),
			"callback"  => array($this, "render"),
			"priority"	=> 2
		);
		return $hub_pages;
	}

	/**
	 * Handle Post data on this Page
	 * @see filter 'greyd_hub_handle_post_data'
	 */
	public function handle_post_data( $post_data ) {

		// sql
		if ($post_data['submit'] == "Export Tables") {
			$tables = array();
			foreach ($post_data as $key => $value) {
				if ($value == "on") $tables[] = $key;
			}
			$replace = array(
				'old' => array(
					'title' => $post_data['replace_title_old'],
					'description' => $post_data['replace_description_old'],
					'admin' => $post_data['replace_admin_old'],
					'domain' => $post_data['replace_url_old'],
					'prefix' => $post_data['replace_pre_old'],
					'id' => $post_data['replace_id_old'],
				),
				'new' => array(
					'title' => $post_data['replace_title_new'],
					'description' => $post_data['replace_description_new'],
					'admin' => $post_data['replace_admin_new'],
					'domain' => $post_data['replace_url_new'],
					'prefix' => $post_data['replace_pre_new'],
					'id' => $post_data['replace_id_new'],
				)
			);
			if (isset($post_data['replace_http'])) {
				$replace['new']['http'] = $post_data['replace_http'];
			}
			if (count($tables) > 0) {
				Tools::download_db_tables($tables, $replace, true);
			}
		}

		else if ($post_data['submit'] == "Delete Tables") {
			$tables = array();
			foreach ($post_data as $key => $value) {
				if ($value == "on") $tables[] = "`".$key."`";
			}
			if (count($tables) > 0) {
				Tools::delete_db_tables($tables);
			}
		}

		else if ($post_data['submit'] == "Import Tables") {
			$input = Hub_Helper::get_uploaded_file('data_file', 'sql');
			if ($input) {
				Tools::import_db_tables($input);
			}
		}
	}


	/*
	=======================================================================
		Hub Tab: Database
	=======================================================================
	*/

	/**
	 * Render database page
	 */
	public function render($nonce) {

		global $wpdb;
		$tables = Hub_Helper::get_the_blogs();

		echo "<div class='hub_panel hub_db'>";

		// dashicons
		$download   = Helper::render_dashicon( "arrow-down-alt" );
		$delete     = Helper::render_dashicon( "trash" );
		$icons = (object)array(
			"download"  => Helper::render_dashicon( "arrow-down-alt" ),
			"upload"    => Helper::render_dashicon( "arrow-up-alt' style='margin-top:-4px;" ),
			"delete"    => Helper::render_dashicon( "trash" ),
		);

		// tables export/delete
		echo "<section>";

			// echo "<div class='flex flex-start' style='gap: 2em;margin-bottom:2em;'>";
				echo "<form class='hub_import_form' method='post' enctype='multipart/form-data'>".$nonce;
					echo "<input type='file' name='data_file' accept='.sql' value='' class='hidden' required maxlength='".wp_max_upload_size()."'>";
					echo "<input type='submit' name='submit' id='submit_import' class='hidden' value='Import Tables'>";
					echo "<span class='button large' style='margin-right:12px;' data-event='button' data-args='tables-import'>".__("Upload and install database tables", 'greyd_hub').$icons->upload."</span><br>";
					// echo Helper::render_info_box(["style"=>"info","text"=>__("Du kannst hier einzelne Tabellen hochladen oder eine gesamte Installation.", 'greyd_hub')]);
				echo "</form>";
				// echo "<form method='post'>".$nonce;
				// 	echo "<input type='submit' name='submit' id='delete_all_unknown_tables' class='hidden' value='delete_all_unknown_tables'>";
				// 	echo "<span class='button large' data-event='button' data-args='delete_all_unknown_tables'>".__("Alle unbekannten Tabellen löschen", 'greyd_hub').$icons->upload."</span><br>";
				// 	// echo Helper::render_info_box(["style"=>"info","text"=>__("Du kannst hier einzelne Tabellen hochladen oder eine gesamte Installation.", 'greyd_hub')]);
				// echo "</form>";
			// echo "</div>";

			echo "<hr>";

			echo "<h2>".__("1. Select database tables", 'greyd_hub')."</h2>";
			
			// form
			echo "<form method='post'>".$nonce;
				echo "<div class='greyd_tile_wrapper'>";
				// tables
				foreach ($tables as $blog) {
					if ($blog['blog_id'] == -1 && count($blog['tables']) == 0) continue;
					echo "<div class='greyd_tile'>";
						if ($blog['blog_id'] < 1) {
							// title
							$description = isset($blog['description']) ? "<p>{$blog['description']}</p>" : "";
							if (isset($blog['version'])) $description = "<p>Version: {$blog['version']} {$blog['language']}</p>";
							echo "<div class='hub_title'>
									<h4>{$blog['domain']}</h4>
									<h5>{$description}</h5>
								</div>";
							// inner
							echo "<div class='inner border-top'>";
						}
						else {
							// domain
							$parent = $blog['blog_id'] === "1" && is_multisite() ? " (".__("Home", 'greyd_hub').")" : "";
							echo "<div class='hub_domain'>
									<a class='' href='".get_site_url($blog['blog_id'])."' target='_blank' title='".__("Open in new tab", 'greyd_hub')."'>{$blog['domain']}</a>
								</div>";
							echo "<hr>";
							// title
							echo "<div class='hub_title'>
									<h4>{$blog['name']}</h4>
									<h5>{$blog['description']}</h5>
								</div>";
							// inner
							echo "<div class='inner border-top'>";
								echo "<div>";
									echo "<div class='inner_head' style='display:inline-block;margin-right:20px;'>".
											"<span>".__("Blog ID:", 'greyd_hub')."&nbsp;</span>".
											"<strong>{$blog['blog_id']}</strong>".
										"</div>";
									echo "<div class='inner_head' style='display:inline-block;margin-right:20px;'>".
											"<span>".__("Prefix:", 'greyd_hub')."&nbsp;</span>".
											"<strong>{$blog['prefix']}</strong>".
										"</div>";
									echo "<div class='inner_head' style='display:inline-block;'>".
											"<span>".__("Admin:", 'greyd_hub')."&nbsp;</span>".
											"<strong>{$blog['admin']}</strong>".
										"</div>";
								echo "</div>";
						}
							// checkboxen
							echo "<div class='hub_tables'
									data-name='{$blog['name']}' data-description='{$blog['description']}' data-domain='{$blog['domain']}' 
									data-admin='{$blog['admin']}' data-prefix='".$wpdb->base_prefix."' data-id='{$blog['blog_id']}'>";
								echo "<strong class='toggle_all' data-event='button' data-args='tables-toggle' data-checked='false'><span class='dashicons dashicons-arrow-down-alt'></span>&nbsp;".__("Mark all", 'greyd_hub')."</strong><br>";
								foreach ($blog['tables'] as $table) {
									echo "<label for='{$table}'><input type='checkbox' id='{$table}' name='{$table}'>{$table}</label><br>";
								}
							echo "</div>";

						echo "</div>"; // inner

					echo "</div>"; // tile
				}
			echo "</div>"; // wrapper

			echo "<hr>";

			// controls
			echo "<div class='hub_controls'>";
				echo "<div class='hub_controls_inner'>";
					echo "<div style='max-width:700px;'>
						<h3 style='display:inline-block;'>".__("2. Replace values", 'greyd_hub')."</h3>&nbsp;<span>".__("(optional)", 'greyd_hub')."</span>
						<table class='greyd_table' border='0' cellspacing='0' cellpadding='0'>
							<thead>
								<tr>
									<th>".__("Name", 'greyd_hub')."</th>
									<th>".__("Old value", 'greyd_hub')."</th>
									<th style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</th>
									<th>".__("New value", 'greyd_hub')."</th>
								</tr>
							</thead>
							<tbody>";
								// edit title
								echo "<tr>
										<td>
											<label>".__("Title", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_title_old' id='replace_title_old' placeholder='".__("My website", 'greyd_hub')."'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_title_new' id='replace_title_new'>
										</td>
									</tr>";
								// edit description
								echo "<tr>
										<td>
											<label>".__("Subtitle", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_description_old' id='replace_description_old' placeholder='".__("Blog about...", 'greyd_hub')."'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_description_new' id='replace_description_new'>
										</td>
									</tr>";
								// edit domain
								echo "<tr>
										<td>
											<label>".__("Domain", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_url_old' id='replace_url_old' placeholder='".__("www.website.com", 'greyd_hub')."'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_url_new' id='replace_url_new'>
										</td>
									</tr>";
								// edit ssl
								echo "<tr>
										<td>".__("SSL", 'greyd_hub')."</td>
										<td colspan='3'>
											<input type='radio' id='replace_to_https' name='replace_http' value='https'>
											<label for='replace_to_https'>".sprintf(__("Convert to %s", 'greyd_hub'), "<strong>https</strong>")."</label>
											<input style='margin-left:15px;' type='radio' id='replace_to_http' name='replace_http' value='http'>
											<label for='replace_to_http'>".sprintf(__("Convert to %s", 'greyd_hub'), "<strong>http</strong>")."</label>
										</td>
									</tr>";
								// edit admin
								echo "<tr>
										<td>
											<label>".__("Admin", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_admin_old' id='replace_admin_old' placeholder='".__("admin@blog.com", 'greyd_hub')."'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_admin_new' id='replace_admin_new'>
										</td>
									</tr>";
								// edit prefix
								echo "<tr>
										<td>
											<label>".__("Prefix", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_pre_old' id='replace_pre_old' placeholder='wp'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_pre_new' id='replace_pre_new'>
										</td>
									</tr>";
								// edit blogid
								echo "<tr>
										<td>
											<label>".__("Blog ID", 'greyd_hub')."</label>
										</td>
										<td>
											<input type='text' name='replace_id_old' id='replace_id_old' placeholder='1'>
										</td>
										<td style='padding:0 .5em;text-align:center;vertical-align:middle;'>→</td>
										<td>
											<input type='text' name='replace_id_new' id='replace_id_new'>
										</td>
									</tr>";
							echo   "</tbody>
								</table>
							<div>".
								Helper::render_info_box(["style" => "info", "text" => __(
									"During an import, certain information (domain, SSL, blog ID and prefix) is automatically adapted to the respective installation.<br>Manual replacement means that values are already adapted during export according to your input. This has advantages, but it can also lead to critical errors if the settings are incorrect.",
									'greyd_hub'
								)]).
							"</div>
							</div>";

							echo "<hr>";

							echo "<h3>".__("3. Export / Delete", 'greyd_hub')."</h3>";

							echo "<div style='max-width:700px;'>
								<div class='flex' style='margin-bottom:1em;'>";
									// delete
									echo "<span style='margin-right:12px;' class='hub_table_button button button-danger large disabled' data-event='button' data-args='tables'>".__("Delete all selected tables", 'greyd_hub').$icons->delete."</span>";
									echo "<button type='submit' name='submit' id='submit_del' class='hidden' value='Delete Tables'>" . __( 'Delete', 'greyd_hub' ) . "</button>";

									// download
									echo "<span class='hub_table_button button button-primary large disabled' data-event='button' data-args='tables'>".__("Download all selected tables", 'greyd_hub').$icons->download."</span>";
									echo "<button type='submit' name='submit' id='submit_export' class='hidden' value='Export Tables'>" . __( 'Download', 'greyd_hub' ) . "</button>";
								echo "</div>";

								$hub_url = is_multisite() ? network_admin_url('admin.php?page=greyd_hub') : admin_url('admin.php?page=greyd_hub');
								echo "<div>".Helper::render_info_box([
										"style" => "warning",
										"text" => sprintf(__(
											"Caution: Deleting or modifying database tables has far-reaching effects and may damage your entire installation if performed incorrectly. It is best to <a href=%s>make a backup of the database</a> beforehand.",
										'greyd_hub'), $hub_url."&tab=backups" )
									])."</div>
							</div>";

						echo "</div>"; // controls inner

					echo "</div>"; // controls
				echo "</form>";
			echo "</section>";

		echo "</div>"; // hub_panel

	}

}