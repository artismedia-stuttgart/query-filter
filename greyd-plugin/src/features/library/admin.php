<?php
/**
 * Template Library Greyd Admin and Settings
 */

namespace Greyd\Library;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Admin($config);
class Admin {

	/**
	 * Holds the plugin configuration.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * All of those posttypes are supported.
	 * 
	 * @var array
	 */
	public static $supported_post_types = array(
		'page'              => 'page',
		'tp_forms'          => 'form',
		'greyd_popup'       => 'popup',
		'dynamic_template'  => 'template',
	);

	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;

		// settings
		add_filter( 'greyd_settings_default_site', array($this, 'add_setting') );
		add_filter( 'greyd_settings_basic', array($this, 'render_settings'), 10, 3 );
		add_filter( 'greyd_settings_more_save', array($this, 'save_settings'), 10, 3 );

		// backend
		// add_filter( 'greyd_features', array( $this, 'remove_template_library_in_non_classic_sites' ) );
		add_filter( 'greyd_dashboard_all_panels', array( $this, 'set_greyd_dashboard_panel' ) );

		add_filter( 'greyd_dashboard_active_panels', array($this, 'add_greyd_dashboard_panel') );
		add_filter( 'greyd_dashboard_panels', array($this, 'add_greyd_classic_dashboard_panel') ); // for classic
		add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts'), 40 );

		// hub website tile
		add_filter( "greyd_hub_tile_page_action", array($this, 'add_tile_page_action_row'), 10, 3 );

	}


	/*
	=================================================================
		SETTINGS
	=================================================================
	*/

	// default settings
	public static function get_defaults() {

		$defaults = array( 
			'template_library' => array(
				'hide_welcome' => 'false',
				'hide_pattern_library' => 'false',
			)
		);

		return $defaults;
	}

	/**
	 * Add default settings
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_setting($site_settings) {

		// add default settings
		$site_settings = array_replace_recursive(
			$site_settings,
			self::get_defaults()
		);

		return $site_settings;
	}

	/**
	 * Render the settings
	 * @see filter 'greyd_settings_basic'
	 * 
	 * @param string $content   Content of all additional settings.
	 * @param string $mode      'site' | 'network_site' | 'network_admin'
	 * @param array $data       Current settings.
	 * 
	 */
	public function render_settings( $content, $mode, $data ) {

		// no settings in Greyd Theme
		if ( !Helper::is_greyd_classic() ) {
			return $content;
		}

		if (($mode == "network_site" || $mode == "site") && isset($data['site'])) {

			// headline
			$content .= "<h2>".__('Template Library', 'greyd_hub')."</h2>";

			// hide_welcome
			$val = isset($data['site']['template_library']['hide_welcome']) ? $data['site']['template_library']['hide_welcome'] : '';
			$sel = (isset($val) && $val == 'true') ? "checked='checked'" : "";
			$content .= "
			<table class='form-table'>
				<tr>
					<th>".__("Welcome message", 'greyd_hub')."</th>
					<td>
						<label for='hide_welcome'>
							<input type='checkbox' id='hide_welcome' value='true' name='hide_welcome' ".$sel."'/>
							<span>".__("Hide welcome message", 'greyd_hub')."</span><br>
							<small class='color_light'>".__("Hides the welcome message of the Template Library.", 'greyd_hub')."</small>
						</label>
					</td>
				</tr>
			</table>";

			if (Helper::is_greyd_blocks()) {
				// hide_pattern_library
				$val = isset($data['site']['template_library']['hide_pattern_library']) ? $data['site']['template_library']['hide_pattern_library'] : '';
				$sel = (isset($val) && $val == 'true') ? "checked='checked'" : "";
				$content .= "
				<table class='form-table'>
					<tr>
						<th>".__('Greyd Pattern Library', 'greyd_hub')."</th>
						<td>
							<label for='hide_pattern_library'>
								<input type='checkbox' id='hide_pattern_library' value='true' name='hide_pattern_library' ".$sel."'/>
								<span>".__("Use only the native Gutenberg Library", 'greyd_hub')."</span>
								<small class='color_light'>".__("Hides the Greyd Pattern Library. All patterns will be loaded in the native Gutenberg pattern library.", 'greyd_hub')."</small>
							</label>
						</td>
					</tr>
				</table>";
			}
		}
		return $content;
	}

	/**
	 * Save site settings
	 * @see filter 'greyd_settings_more_save'
	 * 
	 * @param array $site       Current site settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_settings( $site, $defaults, $data ) {

		// make new settings
		$site['template_library'] = array(
			'hide_welcome' => isset($data['hide_welcome']) ? 'true' : 'false',
			'hide_pattern_library' => isset($data['hide_pattern_library']) ? 'true' : 'false',
		);
		return $site;
	}


	/**
	 * =================================================================
	 *                          BACKEND UI
	 * =================================================================
	 */

	/**
	 * Add Buttons to Hub Website Tile.
	 * @see filter 'greyd_hub_tile_page_action'
	 * 
	 * @param array $rows	All rows.
	 * @param array $blog	Blog details.
	 * @param array $vars	Blog vars used in tile.
	 * @return array $rows
	 */
	public function add_tile_page_action_row( $rows, $blog, $vars ) {

		if ( $vars['is_greyd'] || $vars['is_greyd_theme'] ) {
			$title = __("Templates", 'greyd_hub');
			$help = __("Create and use templates", 'greyd_hub');
			$create = __("Create template", 'greyd_hub');
			$install = __("Install template", 'greyd_hub');
			if ( !Helper::is_greyd_classic() ) {
				// call it "blueprints" in Greyd Theme
				$title = __("Blueprints", 'greyd_hub');
				$help = __("Create and use website blueprints", 'greyd_hub');
				$create = __("Create blueprint", 'greyd_hub');
				$install = __("Install blueprint", 'greyd_hub');
			}
			$rows[] = array(
				'slug' => 'template',
				'content' =>
					"<div class='flex inner_head'>".
						"<p>".$title."</p>".
						"<small>".$help."</small>".
					"</div>".
					"<form class='data-holder' method='post' data-mods='' data-name='".$blog['name']."' data-id='".$blog['blog_id']."' data-domain='".$blog['domain']."' data-siteurl='".$vars['site_url']."' data-description='".$blog['description']."'>".$vars['nonce']."
						<span id='btn_create_user_template' class='button small' onclick='greyd.templateLibrary.showUserTemplateForm(jQuery(this).parent().data())' title='".$create."'><span>".$create."</span>".$vars['icons']->create."</span>
						<span id='btn_install_template' class='button small button-ghost' onclick='greyd.templateLibrary.open(jQuery(this).parent().data())' title='".$install."'><span>".$install."</span>".$vars['icons']->upload."</span>".
					"</form>",
				'priority' => 4
			);
		}

		return $rows;
	}

	/**
	 * Set dashboard panel
	 * @see filter 'greyd_dashboard_all_panels'
	 */
	public function set_greyd_dashboard_panel($panels) {

		if ( !Helper::is_greyd_classic() ) {
			// call it "blueprints" in Greyd Theme
			$panels[ 'template-library' ] = array(
				'title'     => __( 'Blueprints', 'greyd_hub' ),
				'descr'     => __( 'Create and use website blueprints that are 100% customizable.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/template-library.jpg',
				'image_alt' => '',
				'help_url'  => __($this->config->helpcenter, "greyd_hub"),
				'feat_url'  => __($this->config->helpcenter, "greyd_hub"),
				'btn_text'  => __( 'Open blueprints', 'greyd_hub' ),
				'btn_url'   => 'javascript:greyd.templateLibrary.open()',
				'slug'      => 'library',
				'cap'       => get_post_type_object( 'greyd_popup' ) ? get_post_type_object( 'greyd_popup' )->cap->edit_posts : 'edit_posts',
				'priority'  => 11,
			);
		}

		return $panels;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_dashboard_panel($panels) {

		$panels[ 'template-library' ] = true;

		return $panels;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_classic_dashboard_panel($panels) {
		// debug($panels);

		array_push($panels, array(
			'icon'  => 'library',
			'title'  => __('Template Library', 'greyd_hub'),
			'descr' => __("Download free website templates that are 100% customizable.", 'greyd_hub'),
			'btn'   => array(
				array( 'text' => _x('Greyd Template Library', 'small', 'greyd_hub'), 'class' => 'button-dark open_library' ),
				// array( 'text' => __("View Website", 'greyd_hub'), 'url' => __($this->config->resources, 'greyd_hub'), 'target' => '_blank', 'icon' => 'external', 'class' => 'button button-ghost' )
			),
			'cap'  => 'switch_themes',
			'priority' => 5
		));

		return $panels;
	}

	/**
	 * add template library scripts
	 */
	public function load_backend_scripts() {
		list( $mode, $posttype ) = self::get_library_mode(); 

		if ( !empty($mode) ) {
			
			$css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
			$js_uri = plugin_dir_url( __FILE__ ) . 'assets/js';

			// css
			wp_register_style($this->config->plugin_name."_templatelibrary_css", $css_uri.'/template-library.css', null, GREYD_VERSION, 'all');
			wp_enqueue_style($this->config->plugin_name."_templatelibrary_css");

			// js
			wp_register_script($this->config->plugin_name.'_templatelibrary_js', $js_uri.'/template-library.js', array('jquery', "greyd-admin-script"), GREYD_VERSION, true);
			wp_enqueue_script($this->config->plugin_name.'_templatelibrary_js');
		} 

		if ( current_user_can('edit_others_posts') && 
			$mode == "partial" && 
			$posttype !== false && 
			is_greyd_blocks() ) {
			// Add template library button for partial templates via javascript
			wp_add_inline_script(
				$this->config->plugin_name.'_templatelibrary_js',
				'jQuery(function() {
					greyd.backend.addPageTitleAction( "'.__("Greyd Template Library", 'greyd_hub').'", { onclick: "greyd.templateLibrary.open();", css: "button-dark template_library_main_button" } );
				});',
				'after'
			);
		}
	}

	/**
	 * =================================================================
	 *                          HELPER
	 * =================================================================
	 */

	/**
	 * Checks if the current screen supports the template library and if so returns the mode
	 * 
	 * @return array [ 0 => {{mode}}, 1 => {{posttype}}, 2 => {{page}} ]
	 */
	public static function get_library_mode() {

		$return = array( false, false, false );

		if (!is_admin()) return $return;

		// get the necassary page and posttype variables
		$page       = isset($_GET['page']) ? $_GET['page'] : '';
		$posttype   = isset($_GET['post_type']) ? $_GET['post_type'] : '';
		$screen     = get_current_screen();

		// blueprints (user fullsite templates) in Greyd Theme
		if ( !Helper::is_greyd_classic() ) {
			if ( $page == "greyd_dashboard" ) {
				$return = array(
					"blueprint", "fullsite", "dashboard"
				);
			} 
			else if ( $page == "greyd_hub") {
				$return = array(
					"blueprint", "fullsite", "hub"
				);
			}
		}
		// fullsite templates on dashboard
		else if ( $page == "greyd_dashboard" ) {
			$return = array(
				"fullsite", "fullsite", "dashboard"
			);
		} 
		else if ( $page == "greyd_hub") {
			$return = array(
				"fullsite", "fullsite", "hub"
			);
		}
		// partial templates on edit.php
		else if ( !empty($posttype) && $screen->action !== "add" && $screen->id !== "toplevel_page_global_contents" ) {
			if ( is_greyd_blocks() && isset(self::$supported_post_types[$posttype]) ) {
				$return = array(
					"partial", self::$supported_post_types[$posttype], "editphp"
				);
			}
		}
		// patterns in gutenberg editor
		else if (
			$screen->base == "post" &&
			($screen->id == "page" || $screen->id == "dynamic_template") &&
			is_greyd_blocks() &&
			Settings::get_setting(array('site', 'template_library', 'hide_pattern_library')) !== "true"
		) {
			$return = array(
				"pattern", "pattern", "editor"
			);
		}

		return $return;
	}

}
