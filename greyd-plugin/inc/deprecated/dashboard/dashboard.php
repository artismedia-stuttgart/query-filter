<?php
/**
 * basic plugin setup
 */
namespace Greyd;

use Greyd\Admin;
use Greyd\Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Deprecated_Dashboard($config);
class Deprecated_Dashboard {

	/**
	 * Holds the plugin config.
	 * 
	 * @var object
	 */
	private static $config;

	/**
	 * Holds the assets url.
	 */
	private static $asset_url;

	/**
	 * Holds the icon url.
	 */
	private static $icon_url;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// set config
		self::$config = (object)$config;
		
		self::$asset_url = GREYD_PLUGIN_URL . '/inc/deprecated/dashboard/assets/';
		self::$icon_url  = self::$asset_url.'icon/';
		
		add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts'), 9 );

		// add greyd ci
		add_action( 'in_admin_header', array($this, 'add_greyd_ci'), 1 );

		// add the dashboard tab-navi
		add_action( 'admin_notices', array($this, 'add_dashboard_tabs'), 1 );
	}

	/**
	 * Load scripts in the admin area.
	 */
	public function load_backend_scripts() {

		if ( ! Helper::is_greyd_classic() ) return;

		/**
		 * 'greyd-admin-style' and 'greyd-admin-script'
		 * are the basic admin scripts.
		 * All other greyd features/plugins/themes depend on them. If a feature/plugin/theme 
		 * runs standalone, these should be copied and registered there as fallback.
		 */

		// Styles
		wp_register_style(
			"greyd-admin-dashboard-style",
			self::$asset_url.'/css/admin-dashboard.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style(
			"greyd-admin-dashboard-style"
		);
	}


	/**
	 * add Greyd CI
	 */
	public function add_greyd_ci() {

		if ( ! Helper::is_greyd_classic() ) return;

		$screen = get_current_screen();
		// debug($screen);

		// don't render on post screens
		if ($screen->base === 'post') return;

		// don't render on non-greyd pages
		if (strpos($screen->id, 'greyd') === false && $screen->post_type !== 'tp_posttypes') return;

		// render
		echo "<div class='greyd_ci_bar'></div>";
		echo "<a class='greyd_admin_logo' href='".__(self::$config->homepage, 'greyd_hub')."' target='_blank' title='".__("Go to the Greyd website", 'greyd_hub')."'><img src='".self::$icon_url."greyd-logo-dark.svg' width='46' alt='".__("Greyd.Suite", 'greyd_hub')."' /></a>";

	}

	/**
	 * add Dashboard Tabs
	 */
	public function add_dashboard_tabs() {

		if ( ! Helper::is_greyd_classic() ) return;

		$screen         = (array) get_current_screen();
		$screen['page'] = isset($_GET['page']) ? $_GET['page'] : '';
		// debug($screen, true);

		// don't render tabs in Greyd.Hub
		if ( $screen['page'] === "greyd_hub" ) return false;

		// don't render on site admin screens, if greyd_dashboard is not the parent or we're on a post page
		if ( !is_network_admin() && ( $screen['parent_base'] !== Admin::$page['slug'] || $screen['base'] === 'post' ) ) return false;

		// don't render on network screens, if greyd_hub is not the parent
		if ( is_network_admin() && $screen['parent_base'] !== "greyd_hub" ) return false;

		// render
		$this->render_dashboard_tabs($screen);

	}

	/**
	 * render Dashboard Tabs
	 */
	public function render_dashboard_tabs($screen) {

		if ( ! Helper::is_greyd_classic() ) return;

		// setup tabs
		$tabs = [
			/*
			'slug' => [
				title:     Title of the page (required)
				slug:      slug of the page (required)
				url:       link to the page (required)
				cap:       capability of the page (required)
				condition: insert 'page' or 'posttype' (optional, defaults to page)
				color:     colorname (optional)
				icon:      dashicon name (optional)
				priority:  position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/

			// first
			'dashboard' => array(
				'title'     => Admin::$page['title'],
				'slug'      => Admin::$page['slug'],
				'url'       => Admin::$page['url'],
				'cap'       => Admin::$page['cap'],
				// 'icon' => 'screenoptions',
				'priority' => 1
			),
			// add tabs by filter
		];

		/**
		 * Modify or add dashboard tabs.
		 * 
		 * @filter 'greyd_dashboard_tabs'
		 * 
		 * @param array $tabs   All tabs.
		 */
		$tabs = apply_filters( 'greyd_dashboard_tabs', $tabs, $screen );

		// complete args
		foreach( $tabs as $tab => $args ) {
			$args['condition']  = isset($args['condition']) ? $args['condition'] : 'page';
			$args['condition'] .= '='.$args['slug'];
			$args['priority']   = isset($args['priority']) ? $args['priority'] : 10;
			$tabs[$tab] = $args;
		}

		// sort tabs by priority
		usort($tabs, function($a, $b) {
			return $a['priority'] - $b['priority'];
		});

		// render tabs
		ob_start();
		echo "<h1 class='greyd_suite_headline'><img src='".self::$icon_url."logotype.svg' height='22' alt='".__("Greyd.Suite", 'greyd_hub')."' /></h1>";
		echo "<div class=' greyd_tabs'>";
		foreach ($tabs as $tab) {
			if ( !current_user_can($tab['cap']) ) continue;
			$icon   = !empty($tab['icon']) ? "<span class='dashicons dashicons-".$tab['icon']."'></span>" : "";
			$color  = !empty($tab['color']) ? $tab['color'] : "";
			$cond   = explode('=', $tab['condition']);
			$active = $screen[$cond[0]] === $cond[1] ? 'active' : '';
			echo "<a href='".$tab['url']."' class='tab ".$color." ".$active."'>".$icon.$tab['title']."</a>";
		}
		echo "</div>";
		return ob_get_contents();

	}

	/**
	 * render Dashboard Page
	 */
	public static function render_dashboard() {

		if ( ! Helper::is_greyd_classic() ) return;

		// check for errors
		// $this->check_db_errors();

		// setup panels
		$panels = array(
			/*
			'slug' => array(
				icon:      icon for the panel (optional, if name is given '.svg' is attached)
				title:     title of the panel (required)
				descr:     description for the panel (optional)
				btn:       setup array for buttons to be rendered (optional)
				cap:       capability for the panel (required)
				state:     insert 'beta' for small badge (optional)
				class:     optional css class names (optional)
				priority:  position of the panel (optional, 0: first, 99: last, defaults to 10)
			)
			*/

			// first
			'helpcenter' => array(
				'icon'  => 'helpcenter',
				'title' => __('Greyd.Helpcenter', 'greyd_hub'),
				'descr' => __("Our tutorial videos and FAQ will help you quickly how to work with Greyd.Suite.", 'greyd_hub'),
				'btn'   => array(
					array( 'text' => __("View website", 'greyd_hub'), 'url' => __(self::$config->helpcenter, 'greyd_hub'), 'target' => '_blank', 'icon' => 'external' ),
					array( 'text' => __('Support', 'greyd_hub'), 'url' => __(self::$config->homepage, 'greyd_hub').'service/', 'target' => '_blank', 'icon' => 'external', 'class' => 'button button-ghost' )
				),
				'cap'   => 'edit_posts',
				'priority' => 9,
			),
			// add panels by filter
			// last
			'download' => array(
				'icon'   => '',
				'title'  => __('Download Greyd.Suite', 'greyd_hub'),
				'descr'  => __("Download the latest version of Greyd.Suite here if you want to use it on another domain, for example.", 'greyd_hub'),
				'btn'   => array(
					array(
						'text'   => __('Download ZIP', 'greyd_hub'),
						'url'    => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/themes/greyd_suite/greyd_suite.zip',
						'target' => '_blank',
						'icon'   => 'arrow-down-alt',
						// 'class'  => 'button button-ghost'
					)
				),
				'class' => 'clear center',
				'cap'   => 'edit_posts',
				'priority' => 99,
			),
			'changelog' => array(
				'icon'   => '',
				'title'  => __("What’s new?", 'greyd_hub'),
				'descr'  => __("All information regarding updates and bugfixes can be found in our changelog.", 'greyd_hub'),
				'btn'   => array(
					array( 'text' => __("View changelog", 'greyd_hub'), 'url' => __(self::$config->homepage.'changelog/', 'greyd_hub'), 'target' => '_blank', 'icon' => 'external', 'class' => 'button button-ghost' )
				),
				'class' => 'clear center',
				'cap'   => 'edit_posts',
				'priority' => 99,
			)
		);

		/**
		 * Modify or add dashboard panels.
		 * 
		 * @filter 'greyd_dashboard_panels'
		 * 
		 * @param array $panels   All panels.
		 * @param array $config   Plugin Config.
		 */
		$panels = apply_filters( "greyd_dashboard_panels", $panels, self::$config );

		// complete args
		foreach ( $panels as $page => $args ) {
			$args['icon']       = !empty($args['icon']) && ( strpos( $args['icon'], '.svg' ) === false ) ? $args['icon'].'.svg' : '';
			$args['priority']   = isset($args['priority']) ? $args['priority'] : 10;
			$panels[$page] = $args;
		}

		// sort panels by priority
		usort($panels, function($a, $b) {
			return $a['priority'] - $b['priority'];
		});

		// render page
		echo "<div class='wrap greyd_deprecated_dashboard'>";
		echo "<div class=''>";

		// tiles
		echo "<section><div class='greyd_tile_wrapper'>";
		foreach ($panels as $panel) {
			if ( isset($panel['cap']) && !current_user_can($panel['cap']) ) continue;

			$class = isset($panel['class']) ? $panel['class'] : '';
			echo "<div class='greyd_tile ".$class."'><div>";
			// state
			$state = isset($panel['state']) ? $panel['state'] : '';
			if (!empty($state)) {
				if ($state === 'beta') {
					echo Helper::render_info_box([
						"style" => "warning",
						"text" => __("Beta feature", 'greyd_hub')
					]);
				} else if ($state === 'new') {
					echo Helper::render_info_box([
						"style" => "new",
						"text" => __("New feature", 'greyd_hub')
					]);
				} else {
					echo Helper::render_info_box(
						is_array($state) ? $state : array(
							"style" => "info",
							"text" => $state
						)
					);
				}
			}
			// content
			// todo: get icon url from feature
			if ( !empty($panel['icon']) ) echo "<img src='".self::$icon_url.$panel['icon']."' width='40'>";
			if ( !empty($panel['title']) ) echo "<h4>".$panel['title']."</h4>";
			if ( !empty($panel['descr']) ) echo "<p>".$panel['descr']."</p>";
			echo "</div><div>";
			if (isset($panel['btn'])) {
				foreach ( (array)$panel['btn'] as $button ) {
					$b_text = !empty($button['text']) ? $button['text'] : $panel['title'];
					$b_url  = !empty($button['url']) ? "href='".$button['url']."'" : '';
					$b_css  = !empty($button['class']) ? 'button '.$button['class'] : 'button';
					$b_tab  = !empty($button['target']) ? "target='".$button['target']."'" : '';
					$b_icon = !empty($button['icon']) ? "&nbsp;<span class='dashicons dashicons-".$button['icon']."'></span>" : '';
					echo "<a class='".$b_css."' ".$b_url." title='".$b_text."' $b_tab>".$b_text.$b_icon."</a>";
				}
			}
			echo "</div></div>";
		}
		echo "</div></section>"; //greyd_tile_wrapper

		echo "</div>"; //hub_panel
		echo "</div>"; // wrap
	}
}