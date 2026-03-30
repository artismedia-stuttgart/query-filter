<?php
/**
 * Template Library Rendering
 */

namespace Greyd\Library;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Library($config);
class Library {

	/**
	 * Holds the plugin configuration.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;

		// render template library
		add_action( 'admin_footer', array($this, 'render_library'), 10, 2 );
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );

	}


	/**
	 * =================================================================
	 *                          Template Library
	 * =================================================================
	 */

	/**
	 * Render the html skeleton of the template library
	 */
	public function render_library() {

		list( $mode, $posttype, $page ) = Admin::get_library_mode();
		if ( empty($mode) ) return false;

		$lang = Helper::get_language_code();
		$lang = $lang === 'de' ? '' : 'en';

		$blog_id    = get_current_blog_id();
		$domain     = home_url();

		$is_classic = \Greyd\Helper::is_greyd_classic() ? 'true' : 'false';

		// capabilities
		$can_use_user_templates     = false;
		$can_edit_user_templates    = false;
		if ($mode == 'fullsite' || $mode == 'blueprint') {
			$can_use_user_templates     = current_user_can( "manage_options" );
			$can_edit_user_templates    = is_multisite() ? is_super_admin() : current_user_can( "manage_options" );
		}
		$hide_welcome_screen = Settings::get_setting( array('site', 'template_library', 'hide_welcome') ) == 'true';

		// sidebar links
		$sidebar_links = "<div class='template_library_links_wrapper'>
			<a href='".__($this->config->resources, "greyd_hub")."' target='_blank'>".__("Template Library website", "greyd_hub")."</a>
			<a href='".__($this->config->helpcenter, "greyd_hub")."' target='_blank'>".__("Helpcenter", "greyd_hub")."</a>
			<a href='".__("https://greyd.io/service/", "greyd_hub")."' target='_blank'>".__("Support", "greyd_hub")."</a>
		</div>";


		// loader
		$content_loader = str_repeat("<div class='template_library_item {$posttype}'>
			<div class='template_library_item_image loading'>
				<img src=''>
				<span class='template_library_item_version_chip hidden'>Classic</span>
				<div class='template_library_item_buttons loading'>
					<span class='install_fullsite_template button button-primary'>"._x("Install", "small", "greyd_hub")."<span class='dashicons dashicons-arrow-down-alt'></span></span>
					<span class='import_template button button-primary'>"._x("Import", "small", "greyd_hub")."<span class='dashicons dashicons-arrow-down-alt'></span></span>
					<span class='import_pattern button button-primary large'>"._x("Insert pattern", "small", "greyd_hub")."<span class='dashicons dashicons-arrow-down-alt'></span></span> 
					<span class='template_details button button-secondary'>"._x("Details", "small", "greyd_hub")."</span>
					".( $can_edit_user_templates ? "<span class='delete_user_template button button-danger hidden'>"._x("Delete", "small", "greyd_hub")."</span>" : "")." 
				</div>
			</div>
			<div class='template_library_item_title loading'></div>
			<div class='template_library_item_description loading'></div>
		</div>", 5);
		$category_loader = "<span class='template_category loading' data-category='0' data-title='".__("All", 'greyd_hub')."'></span>
		<span class='template_category loading'></span>
		<span class='template_category loading'></span>";

		// tabs
		$tabs = array();
		if ( $mode == 'blueprint' ) {
			if ( $can_use_user_templates ) {
				$tabs['user_fullsite'] = array(
					'title' => __("Website blueprints", "greyd_hub"),
					'sidebar' => "",
					'content' => "<div class='template_library_items'>{$content_loader}".(
						!$can_edit_user_templates ? "" : 
						"<div class='template_library_add_new_user_template'><span class='button large'><span class='dashicons dashicons-plus'></span>&nbsp;".__("Create new blueprint", "greyd_hub")."</span></div>"
					)."</div>",
					"error"   => Helper::render_info_box([
						'style' => 'blue',
						'above' => __("No blueprints found.", 'greyd_hub'),
						'text' => (
							$can_edit_user_templates ?
							__("You have not created any website blueprints yet. Do you want to create your first blueprint now?", "greyd_hub") :
							__("No website blueprints have been created yet. You need to be super admin to create blueprints.", "greyd_hub")
						)
					])
				);
			}
			$sidebar_links = "<div class='template_library_links_wrapper'>
				<a href='".__($this->config->helpcenter, "greyd_hub")."' target='_blank'>".__("Helpcenter", "greyd_hub")."</a>
				<a href='".__("https://greyd.io/service/", "greyd_hub")."' target='_blank'>".__("Support", "greyd_hub")."</a>
			</div>";
		}
		else if ( $mode === 'fullsite' ) {
			$tabs['fullsite'] = __("Greyd Templates", "greyd_hub");
			if ( $can_use_user_templates ) {
				$tabs['user_fullsite'] = array(
					'title' => __("Custom website templates", "greyd_hub"),
					'sidebar' => "",
					'content' => "<div class='template_library_items'>{$content_loader}".(
						!$can_edit_user_templates ? "" : 
						"<div class='template_library_add_new_user_template'><span class='button large'><span class='dashicons dashicons-plus'></span>&nbsp;".__("Create new template", "greyd_hub")."</span></div>"
					)."</div>",
					"error"   => Helper::render_info_box([
						'style' => 'blue',
						'above' => __("No templates found.", 'greyd_hub'),
						'text' => (
							$can_edit_user_templates ?
							__("You have not created any website templates yet. Do you want to create your first template now?", "greyd_hub") :
							__("No website templates have been created yet. You need to be super admin to create templates.", "greyd_hub")
						)
					])
				);
			}
		}
		else if ( $mode === 'partial' ) {
			$titles = array(
				"page"      => __("Pages", "greyd_hub"),
				"template"  => __("Templates", "greyd_hub"),
				"form"      => __("Forms", "greyd_hub"),
				"popup"     => __("Pop-ups", "greyd_hub"),
			);
			$tabs[$posttype] = $titles[$posttype];
			foreach( $titles as $type => $title ) {
				if ( $posttype != $type ) $tabs[$type] = $title;
			}
		}
		else {
			$tabs['patterns'] = __("Patterns", "greyd_hub");
		}

		if ( $mode !== 'blueprint' ) {
			$welcome_tab = array(
				'title' => __("Getting started", "greyd_hub"),
				'sidebar' => "",
				'content' => "<div class='template_library_welcome'>
					<h2>".__("Welcome to the Greyd Template Library", "greyd_hub")."</h2>
					<p>".__("Here you will find a wide range of 100% flexible templates for your website. Designs, content, layout - you can completely customize our templates. Depending on where you are in the backend, you can download templates for entire websites, individual pages, templates, forms, pop-ups or patterns.", "greyd_hub")."</p>
					<p>".__("You have suggestions for new templates or problems with the application? Use our support form!", "greyd_hub")."</p>
					<p>".__("In our Helpcenter you can find a tutorial video for the Template Library.", "greyd_hub")."</p>
					<span class='start_library button button-primary grow large'>"._x("Select template", "small", "greyd_hub")."</span>
					".( $hide_welcome_screen ? "" : "<form id='template_library_hide_welcome_form' class='template_library_hide_welcome'>
						<label for='template_library_hide_welcome'>
							<input type='checkbox' id='template_library_hide_welcome' name='template_library_hide_welcome' value='true' />
							<small>".__("Hide welcome message in the future", "greyd_hub")."</small>
						</label>
					</form>" )."

				</div>"
			);
			if ( $hide_welcome_screen ) {
				$tabs['welcome'] = $welcome_tab;
			}
			else {
				$tabs = array_merge( array( 'welcome' => $welcome_tab ), $tabs );
			}
		}

		// head
		$head = "<div class='block_tabs'>";
		foreach( array_values($tabs) as $key => $tab) {
			$i = $key+1;
			$active = $i == 1 ? " active" : "";
			$title = is_array($tab) ? $tab['title'] : $tab;

			$head .= "<div class='block_tab template_library_tab {$active}' data-tab='{$i}'>{$title}</div>";
		}
		$head .= "</div>";

		// contents
		ob_start();
		echo "<div class='template_library_content {$mode}'>";

		// main slide
		echo "<div class='template_library_main_wrapper active' data-slide='main'>";

		// sidebar
		$sidebar_middle = "";
		if ( $mode === 'partial' ) {
			$sidebar_middle = "<small>".__("The preview of those templates may differ. The templates automatically adapt to your design settings after the import.", 'greyd_hub')."</small>";
		}
		else if ( $mode === 'partial' ) {
			$sidebar_middle = Helper::render_info_box([
				'style' => 'blue',
				'text' => __("Patterns are loaded once initially. This can take a little longer.", "greyd_hub")
			]);
		}
		echo "<div class='overlay_sidebar'>
			<div class='sidebar_top'>";
			foreach( array_values($tabs) as $key => $tab) {
				$i = $key+1;
				$active     = $i == 1 ? " active" : "";
				$sidebar    = is_array($tab) && isset( $tab['sidebar'] ) ? $tab['sidebar'] : $category_loader;
				echo "<div class='sidebar_top_content{$active}' data-tab='{$i}'>{$sidebar}</div>";
			}
			echo "</div>
			<div class='sidebar_middle'>{$sidebar_middle}</div>
			<div class='sidebar_bottom'>{$sidebar_links}</div>
		</div>";

		// contents
		$i = 1;
		foreach( $tabs as $type => $tab ) {
			$active     = $i == 1 ? " active" : "";
			$content    = is_array($tab) && isset( $tab['content'] ) ? $tab['content'] : "<div class='template_library_items'>{$content_loader}</div>";
			$error      = is_array($tab) && isset( $tab['error'] ) ? $tab['error'] : Helper::render_info_box([
				'style' => 'red',
				'above' => __("Something went wrong", "greyd_hub"),
				'text' => __("Sorry, no templates could be retrieved. Please try again later.", "greyd_hub")
			]);
			echo "<div class='template_library_body{$active}' data-tab='{$i}' data-type='{$type}'>
				<div class='template_library_error hidden'>{$error}</div>
				{$content}
			</div>";
			$i++;
		}

		// end of main slide
		echo "</div>";

		// detail slide
		if ( $mode !== "pattern" ) {
			echo "<div class='template_library_detail_wrapper' data-slide='details'>
				<div class='overlay_sidebar'>
					<span class='back_to_main_library'>
						<span class='dashicons dashicons-arrow-left-alt2'></span>".__("Back", 'greyd_hub')."
					</span>
					{$sidebar_links}
				</div>
				<div class='template_library_body'>
					<div class='template_library_details'>
						<div class='template_library_details_title'></div>
						<div class='template_library_details_description'></div>
						<div class='template_library_details_bulletpoints'></div>
						<div class='template_library_details_buttons'>
							".(
								$mode == 'fullsite' ?
								"<span class='install_fullsite_template button button-primary large'>"._x("Install template", "small", "greyd_hub")."<span class='dashicons dashicons-arrow-down-alt'></span></span>" : 
								"<span class='import_template button button-primary large'>"._x("Import template", "small", "greyd_hub")."<span class='dashicons dashicons-arrow-down-alt'></span></span>"
							)."
							<a href='#' class='button button-tertiary live-preview large'>"._x("Live preview", "small", "greyd_hub")."</a>
						</div>
					</div>
					<div class='template_library_details_screenshots'>
						<div class='template_library_details_screenshot'><img src=''></div>
					</div>
				</div>
			</div>";
		}
		echo "<span class='greyd_ci_bar template_library_progress' style='width:".($mode !== "pattern" ? 100 : 0)."%;'></span>";

		// end of content
		echo "</div>";
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * Render the library
		 */
		echo \Greyd\Admin::build_overlay( array(
			"ID"        => "template_library",
			"class"     => "has-sidebar",
			"atts"      => array(
				"data-type='{$posttype}'",
				"data-mode='{$mode}'",
				"data-lang='{$lang}'",
				"data-blog-id='{$blog_id}'",
				"data-blog-domain='{$domain}'",
				"data-can_use_user_templates='{$can_use_user_templates}'",
				"data-can_edit_user_templates='{$can_edit_user_templates}'",
				"data-is-classic='{$is_classic}'",
				"data-is-classic-msg='".__("This template is not compatible with your current version of Greyd.", 'greyd_hub')."'",
			),
			"head"      => $head,
			"content"   => $contents
		) );
		return;
	}

	/**
	 * Add overlay contents
	 * 
	 * @filter 'greyd_overlay_contents'
	 * 
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {
		list( $mode, $posttype, $page ) = Admin::get_library_mode();

		if ( empty($mode) ) return $contents;

		$posttype_label = isset($_GET['post_type']) ? get_post_type_object($_GET['post_type'])->labels->singular_name : __("Template", "greyd_hub");

		if ( $mode == "partial") {
			$contents['partial_template_import'] = array(
				'loading' => array(
					'title'     => __("Please wait.", 'greyd_hub'),
					'content'     => '<div class="install_log"><span class="replace">'.sprintf(__("%s will be downloaded and installed. This may take a moment.", 'greyd_hub'), $posttype_label).'</span></div>'
				),
				'decision' => array(
					"title"     => __("Import successful", 'greyd_hub'),
					"descr"     => __("The template was imported successfully. What do you want to do next?", 'greyd_hub'),
					'button1'   => __("Back to the overview", 'greyd_hub'),
					'button2'   => sprintf( __("Edit %s", 'greyd_hub'), $posttype_label ),
				),
				'reload' => array(
					"title"     => __("Import successful", 'greyd_hub'),
					"descr"     => sprintf(__("%s has been imported.", 'greyd_hub'), $posttype_label )
				),
				'fail' => array(
					'title'     => __("Import failed", 'greyd_hub'),
					'descr'     => '<span class="replace">'.__("The template could not be imported.", 'greyd_hub'). ' ' . __("See browser console for details and contact an admin.", 'greyd_hub') . '</span>'
				)
			);
		}
		else if ($mode == 'blueprint') {
			$contents['fullsite_template_import'] = array(
				'confirm' => array(
					'title'     => __("Install blueprint", 'greyd_hub'),
					'content'   => Helper::render_info_box([
						'style' => 'orange',
						'text'  => sprintf(
							__('You want to install a website blueprint. All previous content will be lost and replaced by the new blueprint. If you are unsure, you can <a target="_blank" href="%s">create a backup</a> beforehand, which you can restore later if you are unhappy with the chosen blueprint.', 'greyd_hub'),
							is_multisite() ? network_admin_url('admin.php?page=greyd_hub') : admin_url('admin.php?page=greyd_hub')
						)
					]),
					'button'    => __("Import now", 'greyd_hub')
				),
				'loading' => array(
					'title'     => __("Please wait.", 'greyd_hub'),
					'content'     => '<div class="install_log"><span class="replace">'.__("Blueprint will be downloaded and installed. This may take a moment.", 'greyd_hub').'</span></div>'
				),
				'reload' => array(
					"title"     => __("Installation successful", 'greyd_hub'),
					"descr"     => __("Blueprint was successfully installed.", 'greyd_hub')
				),
				'fail' => array(
					'title'     => __("Installation failed", 'greyd_hub'),
					'descr'     => '<span class="replace">'.__("The blueprint could not be installed.", 'greyd_hub'). ' ' . __("See browser console for details and contact an admin.", 'greyd_hub') . '</span>'
				),
				'decision' => array(
					'title'     => __("This blueprint is not compatible with your current version of Greyd.", 'greyd_hub'),
					'descr'		=> Helper::render_info_box([
						'style' => 'orange',
						'text'  => __("You can continue and use the Classic to FSE Converter afterwards to convert the blueprint to your version.", 'greyd_hub')
					]),
					'button1'   => __("Continue", 'greyd_hub'),
					'button2'   => __("Abort", 'greyd_hub'),
					'button1_class' => 'danger'
				)
			);
			$contents['delete_user_fullsite_template'] = array(
				'confirm' => array(
					'title'     => __("Delete blueprint", 'greyd_hub'),
					'content'   => __("This will finally delete the blueprint from your server. Do you really want to delete this blueprint?", 'greyd_hub'),
					'button'    => __("Delete now", 'greyd_hub'),
					'button_class' => 'danger'
				),
				'loading' => array(
					'title'     => __("Please wait.", 'greyd_hub'),
					'content'     => __("Deleting blueprint.", 'greyd_hub')
				),
				'success' => array(
					"title"     => __("Deletion successful", 'greyd_hub'),
					"descr"     => __("The blueprint was successfully deleted.", 'greyd_hub')
				),
				'fail' => array(
					'title'     => __("Deletion failed", 'greyd_hub'),
					'descr'     => __("The blueprint could not be deleted.", 'greyd_hub') .  ' ' . __("See browser console for details and contact an admin.", 'greyd_hub')
				)


			);
			$can_edit_user_templates = is_multisite() ? is_super_admin() : current_user_can( "manage_options" );
			if ( $page == "hub" || ( $page == "dashboard" && $can_edit_user_templates ) ) {

				if ( is_multisite() && is_network_admin() ) {
					$blogs = get_sites( array( 'number' => 999 ) );
					$options = '';
					foreach ($blogs as $blog) {
						$options .= "<option value='{$blog->blog_id}'>".$blog->domain.$blog->path."</option>";
					}
					$blog_input = "
						<label for='blogs'>".__("Website", 'greyd_hub')."</label>
						<small>".__("Select the website you want to save as a blueprint.", 'greyd_hub')."</small>
						<select name='blogs' id='blogs'>{$options}</select>
					";
				}
				else {
					$blog_input = "<input type='hidden' name='blogs' id='blogs' value='".get_current_blog_id()."'>";
				}

				$create_template_form = "<form id='create_user_template_form' class=''>";
				$create_template_form .=  "

					<label for='title'>".__("Title", 'greyd_hub')."</label>
					<small>".__("Name your blueprint", 'greyd_hub')."</small>
					<input type='text' id='title' name='title'>
					{$blog_input}
					<label for='description'>".__("Description", 'greyd_hub')."</label>
					<textarea type='text' id='description' name='description'></textarea>
					<label for='fullsite_template_thumbnail' class='button small button-ghost'><span class='dashicons dashicons-arrow-up-alt'></span><span class='fullsite_template_thumbnail_button_text'>".__("Upload thumbnail", 'greyd_hub')."</span>
						<input type='file' accept='.jpg,.png' name='fullsite_template_thumbnail' id='fullsite_template_thumbnail'>
					</label>
					".Helper::render_info_box([
						"style" => "blue",
						"text" => __( "For example, create a screenshot of your <a target=\"_blank\" class=\"create_user_template_homepage_link\">start page</a> as a thumbnail.", 'greyd_hub' )
					]);


				$create_template_form .= '</form>';

				$contents['create_user_fullsite_template'] = array(
					'confirm' => array(
						'title'     => __("Create blueprint", 'greyd_hub'),
						'content'   => $create_template_form,
						'button'    => __("Create", 'greyd_hub')
					),
					'loading' => array(
						'title'     => __("Please wait.", 'greyd_hub'),
						'content'     => '<div class="install_log"><span class="replace">'.__("Your blueprint is being created.", 'greyd_hub').'</span></div>'
					),
					'success' => array(
						'title'     => __("Blueprint created", 'greyd_hub'),
						'descr'     => __("Your blueprint was created successfully.", 'greyd_hub')
					),
					'fail' => array(
						'title'     => __("Failed", 'greyd_hub'),
						'descr'     => __("The blueprint creation has failed.", 'greyd_hub') . ' ' . __("See browser console for details and contact an admin.", 'greyd_hub')
					)
				);
				$contents['create_user_fullsite_template_exists'] = array(
					'decision' => array(
						"title"     => __("Failed", 'greyd_hub'),
						"descr"     => __("A blueprint with this name already exists. Do you want to try again?", 'greyd_hub'),
						'button1'   => __("Cancel", 'greyd_hub'),
						'button2'   => __("Try again", 'greyd_hub'),
					),
				);
			}
		}
		else if ($mode == "fullsite") {
			$contents['fullsite_template_import'] = array(
				'confirm' => array(
					'title'     => __("Install Full Site Template", 'greyd_hub'),
					'content'   => Helper::render_info_box([
						'style' => 'orange',
						'text'  => sprintf(
							__('You want to install a full site template. All previous content will be lost and replaced by the new template. If you are unsure, you can <a target="_blank" href="%s">create a backup</a> beforehand, which you can restore later if you are unhappy with the chosen template.', 'greyd_hub'),
							is_multisite() ? network_admin_url('admin.php?page=greyd_hub') : admin_url('admin.php?page=greyd_hub')
						)
					]),
					'button'    => __("Import now", 'greyd_hub')
				),
				'loading' => array(
					'title'     => __("Please wait.", 'greyd_hub'),
					'content'     => '<div class="install_log"><span class="replace">'.__("Template will be downloaded and installed. This may take a moment.", 'greyd_hub').'</span></div>'
				),
				'reload' => array(
					"title"     => __("Installation successful", 'greyd_hub'),
					"descr"     => __("Template was successfully installed.", 'greyd_hub')
				),
				'fail' => array(
					'title'     => __("Installation failed", 'greyd_hub'),
					'descr'     => '<span class="replace">'.__("The template could not be installed.", 'greyd_hub'). ' ' . __("See browser console for details and contact an admin.", 'greyd_hub') . '</span>'
				),
				'decision' => array(
					'title'     => __("This template is not compatible with your current version of Greyd.", 'greyd_hub'),
					'descr'		=> Helper::render_info_box([
						'style' => 'orange',
						'text'  => __("You can continue and use the Classic to FSE Converter afterwards to convert the template to your version.", 'greyd_hub')
					]),
					'button1'   => __("Continue", 'greyd_hub'),
					'button2'   => __("Abort", 'greyd_hub'),
					'button1_class' => 'danger'
				)
			);
			$contents['delete_user_fullsite_template'] = array(
				'confirm' => array(
					'title'     => __("Delete template", 'greyd_hub'),
					'content'   => __("This will finally delete the template from the server and from the library. Do you really want to delete this template?", 'greyd_hub'),
					'button'    => __("Delete now", 'greyd_hub'),
					'button_class' => 'danger'
				),
				'loading' => array(
					'title'     => __("Please wait.", 'greyd_hub'),
					'content'     => __("Deleting template.", 'greyd_hub')
				),
				'success' => array(
					"title"     => __("Deletion successful", 'greyd_hub'),
					"descr"     => __("The template was successfully deleted.", 'greyd_hub')
				),
				'fail' => array(
					'title'     => __("Deletion failed", 'greyd_hub'),
					'descr'     => __("The template could not be deleted.", 'greyd_hub') .  ' ' . __("See browser console for details and contact an admin.", 'greyd_hub')
				)


			);
			$can_edit_user_templates = is_multisite() ? is_super_admin() : current_user_can( "manage_options" );
			if ( $page == "hub" || ( $page == "dashboard" && $can_edit_user_templates ) ) {

				if ( is_multisite() && is_network_admin() ) {
					$blogs = get_sites( array( 'number' => 999 ) );
					$options = '';
					foreach ($blogs as $blog) {
						$options .= "<option value='{$blog->blog_id}'>".$blog->domain.$blog->path."</option>";
					}
					$blog_input = "
						<label for='blogs'>".__("Website", 'greyd_hub')."</label>
						<small>".__("Select the website you want to save as a template.", 'greyd_hub')."</small>
						<select name='blogs' id='blogs'>{$options}</select>
					";
				}
				else {
					$blog_input = "<input type='hidden' name='blogs' id='blogs' value='".get_current_blog_id()."'>";
				}

				$create_template_form = "<form id='create_user_template_form' class=''>";
				$create_template_form .=  "

					<label for='title'>".__("Title", 'greyd_hub')."</label>
					<small>".__("Name your template", 'greyd_hub')."</small>
					<input type='text' id='title' name='title'>
					{$blog_input}
					<label for='description'>".__("Description", 'greyd_hub')."</label>
					<textarea type='text' id='description' name='description'></textarea>
					<label for='fullsite_template_thumbnail' class='button small button-ghost'><span class='dashicons dashicons-arrow-up-alt'></span><span class='fullsite_template_thumbnail_button_text'>".__("Upload thumbnail", 'greyd_hub')."</span>
						<input type='file' accept='.jpg,.png' name='fullsite_template_thumbnail' id='fullsite_template_thumbnail'>
					</label>
					".Helper::render_info_box([
						"style" => "blue",
						"text" => __( "For example, create a screenshot of your <a target=\"_blank\" class=\"create_user_template_homepage_link\">start page</a> as a thumbnail.", 'greyd_hub' )
					]);


				$create_template_form .= '</form>';

				$contents['create_user_fullsite_template'] = array(
					'confirm' => array(
						'title'     => __("Create template", 'greyd_hub'),
						'content'   => $create_template_form,
						'button'    => __("Create", 'greyd_hub')
					),
					'loading' => array(
						'title'     => __("Please wait.", 'greyd_hub'),
						'content'     => '<div class="install_log"><span class="replace">'.__("Your template is being created.", 'greyd_hub').'</span></div>'
					),
					'success' => array(
						'title'     => __("Template created", 'greyd_hub'),
						'descr'     => __("Your template was created successfully.", 'greyd_hub')
					),
					'fail' => array(
						'title'     => __("Failed", 'greyd_hub'),
						'descr'     => __("The template creation has failed.", 'greyd_hub') . ' ' . __("See browser console for details and contact an admin.", 'greyd_hub')
					)
				);
				$contents['create_user_fullsite_template_exists'] = array(
					'decision' => array(
						"title"     => __("Failed", 'greyd_hub'),
						"descr"     => __("A template with this name already exists. Do you want to try again?", 'greyd_hub'),
						'button1'   => __("Cancel", 'greyd_hub'),
						'button2'   => __("Try again", 'greyd_hub'),
					),
				);
			}
		}
		else if ($mode == "pattern") {
			$contents['pattern_import'] = array(
				'loading' => array(
					'descr'      => __("Pattern is being imported.", 'greyd_hub'),
				),
				'success' => array(
					'title'     => __("Pattern inserted", 'greyd_hub'),
					'descr'     => __("The pattern was successfully inserted.", 'greyd_hub')
				),
				'fail' => array(
					'title'     => __("Failed", 'greyd_hub'),
					'descr'     => __("The insertion of the pattern failed.", 'greyd_hub') . ' ' . __("See browser console for details and contact an admin.", 'greyd_hub')
				)
			);
		}

		return $contents;
	}

}
