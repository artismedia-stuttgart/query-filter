<?php
/**
 * Dynamic Posttypes Greyd Admin
 */

namespace Greyd\Posttypes;

if ( !defined( 'ABSPATH' ) ) exit;

new Admin($config);
class Admin {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * Standalone mode.
	 * 
	 * @var bool
	 */
	public static $is_standalone = false;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	/**
	 * Constants for custom posttypes
	 */
	const POST_TYPE  = "tp_posttypes";
	const OPTION     = "posttype_settings";
	const META       = "dynamic_meta";
	const DEFAULTS   = [
		"icon" => "admin-post",
		"position" => 54,
		"supports" => array("title" => "title", "custom-fields" => "custom-fields", "author" => "author"),
		"arguments" => array("search" => "search", "archive" => "archive")
	];


	/**
	 * Class constructor.
	 */
	public function __construct($config) {

		// set config
		$this->config = (object) $config;
		if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
			// standalone mode
			self::$is_standalone = true;
		}

		// static vars
		add_action( 'init', function() {
			// define page details
			self::$page = array(
				'slug'      => self::POST_TYPE,
				'name'      => __("Post Types", 'greyd_hub'),
				'title'     => __('Dynamic Post Types', 'greyd_hub'),
				'descr'     => __("Create custom post types with their own layouts and clear data maintenance.", 'greyd_hub'),
				'url'       => admin_url( 'edit.php?post_type='.self::POST_TYPE ),
				'new'       => admin_url( 'post-new.php?post_type='.self::POST_TYPE ),
				'cap'       => get_post_type_object(self::POST_TYPE) ? get_post_type_object(self::POST_TYPE)->cap->edit_posts : 'edit_pages'
			);
			// debug(self::$page);
		}, 0 );

		// greyd admin
		add_filter( 'greyd_admin_bar_group', array($this, 'add_greyd_admin_bar_group_item') );
		add_filter( 'greyd_dashboard_tabs', array($this, 'add_greyd_dashboard_tab') );
		add_filter( 'greyd_dashboard_active_panels', array($this, 'add_greyd_dashboard_panel') );
		add_filter( 'greyd_dashboard_panels', array($this, 'add_greyd_classic_dashboard_panel') ); // for classic mode
		add_action( 'admin_enqueue_scripts', array($this, 'load_posttype_scripts') );

	}


	/**
	 * =================================================================
	 *                          ADMIN
	 * =================================================================
	 */

	/**
	 * Add item to Greyd.Suite adminbar group.
	 * @see filter 'greyd_admin_bar_group'
	 */
	public function add_greyd_admin_bar_group_item($items) {
		// debug($items);

		// in frontend
		if (!is_admin()) {

			if ( current_user_can(self::$page['cap']) ) {
				array_push($items, array(
					'parent' => "greyd_toolbar",
					'id'     => self::$page['slug'],
					'title'  => self::$page['name'],
					'href'   => self::$page['url'],
					'priority' => 40
				));
			}

		}

		return $items;
	}

	/**
	 * Add dashboard tab if Hub is installed
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab($tabs) {
		// debug($tabs);

		$tabs[self::$page['slug']] = array(
			'title'     => self::$page['name'],
			'slug'      => self::$page['slug'],
			'url'       => self::$page['url'],
			'cap'       => self::$page['cap'],
			'priority'  => 40,
			'condition' => 'post_type',
			'color'     => 'blue'
		);

		return $tabs;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_active_panels'
	 */
	public function add_greyd_dashboard_panel($panels) {
		
		$panels[ 'dynamic-post-types' ] = true;

		return $panels;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_classic_dashboard_panel($panels) {

		array_push($panels, array(
			'icon'  => 'posttypes',
			'title' => self::$page['title'],
			'descr' => self::$page['descr'],
			'btn'   => array(
				array( 'text' => __("View post types", 'greyd_hub'), 'url' => self::$page['url'] ),
				array( 'text' => __("Create", 'greyd_hub'), 'url' => self::$page['new'], 'class' => 'button button-ghost' )
			),
			'cap'       => self::$page['cap'],
			'priority'  => 90,
		));

		return $panels;
	}

	/**
	 * add posttype scripts
	 */
	public function load_posttype_scripts() {

		$libs_uri = plugin_dir_url( $this->config->plugin_file ) . 'libs';
		$css_uri  = plugin_dir_url( __FILE__ ) . 'assets/css';
		$js_uri   = plugin_dir_url( __FILE__ ) . 'assets/js';

		if (self::$is_standalone) {
			// load helper scripts in standalone mode

			// style and script
			wp_register_style("greyd-admin-style", $css_uri.'/backend.css', null, GREYD_VERSION, 'all');
			wp_register_script("greyd-admin-script", $js_uri.'/backend.js', array('wp-data', 'jquery'), GREYD_VERSION, true);

			// inline script before
			// define global greyd var
			wp_add_inline_script("greyd-admin-script", 'var greyd = greyd || {};', 'before');

		}

		// register style and script
		wp_register_style($this->config->plugin_name."_posttypes_css", $css_uri.'/posttypes.css', null, GREYD_VERSION, 'all');
		wp_register_script($this->config->plugin_name.'_posttypes_js', $js_uri.'/posttypes.js', array('jquery', "greyd-admin-script"), GREYD_VERSION, true);

		$script = "";
		$screen = get_current_screen();
		$posttype = get_post_type() !== false ? get_post_type() : ( isset($screen->post_type) ? $screen->post_type : "" );

		// Posttypes
		if ( $posttype === self::POST_TYPE ) {

			// enqueue style and script
			if (self::$is_standalone) {
				wp_enqueue_style("greyd-admin-style");
				wp_enqueue_script("greyd-admin-script");
			}
			wp_enqueue_style($this->config->plugin_name."_posttypes_css");
			wp_enqueue_script($this->config->plugin_name.'_posttypes_js');
			// init script
			$script .= 'jQuery(function() {';
			if ( $screen->base == 'post' ) {
				wp_enqueue_media();
				// Iconpicker (from libs)
				$iconpicker_uri = $libs_uri.'/iconpicker';
				// enqueue css
				wp_register_style('iconpicker-main', $iconpicker_uri.'/css/base/jquery.fonticonpicker.min.css', null, '1.0', 'all');
				wp_enqueue_style('iconpicker-main');
				wp_register_style('iconpicker-main-theme', $iconpicker_uri.'/css/base/jquery.fonticonpicker.grey.min.css', null, '1.0', 'all');
				wp_enqueue_style('iconpicker-main-theme');
				// enqueue scripts
				wp_register_script('iconpicker', $iconpicker_uri.'/js/jquery.fonticonpicker.min.js', array('jquery'), '1.0', false);
				wp_enqueue_script('iconpicker');
				$script .= 'greyd.posttypes.init();';
			}
			$script .= '});';
		}

		// Posts
		$cpt = Posttype_Helper::get_dynamic_posttype_by_slug($posttype);
		if ($cpt !== false) {

			// enqueue style and script
			if (self::$is_standalone) {
				wp_enqueue_style("greyd-admin-style");
				wp_enqueue_script("greyd-admin-script");
			}
			wp_enqueue_style($this->config->plugin_name."_posttypes_css");
			wp_enqueue_script($this->config->plugin_name.'_posttypes_js');
			// init script
			$script .= 'jQuery(function() {';
			if ( $screen->base === "post" ) {
				wp_enqueue_media();
				wp_enqueue_editor();
				$script .= 'greyd.dynamic_posts.init();';
			}
			else if ( $screen->base === "edit" ) {
				$script .= 'greyd.backend.addPageTitleAction( "'.sprintf( __("Edit %s", 'greyd_hub'), __("Post Type", 'greyd_hub') ).'", { url: "'.get_edit_post_link($cpt["id"]).'" } );';
			}
			$script .= '});';
		}

		// Render Inline Scripts
		if ( !empty($script) ) wp_add_inline_script( $this->config->plugin_name.'_posttypes_js', $script, 'after');

	}

}
