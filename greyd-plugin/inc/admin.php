<?php
/**
 * Greyd Admin Menus and Admin Bar.
 */
namespace Greyd;

use Greyd\Helper;

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
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array(
		'main_title' => "Greyd.Suite",
		'slug'       => 'greyd_dashboard',
		'title'      => 'Dashboard',
		'url'        => null,
		'cap'        => 'edit_posts',
		'callback'   => null
	);

	/**
	 * Hub config.
	 * 
	 * @var bool
	 */
	public static $is_standalone = false;
	public static $is_global = false;

	/**
	 * Constructor
	 */
	public function __construct($config) {
		
		// set config
		$this->config = (object)$config;

		if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
			// standalone mode
			self::$is_standalone = true;
		}
		else if (is_multisite()) {
			if (class_exists('Greyd\Plugin\Features')) {
				// in plugin mode
				$features = \Greyd\Plugin\Features::get_saved_features();
				// debug($features);
				if (isset($features['global']['network-hub'])) {
					// globally enabled
					/**
					 * dev mode: hub is always local
					 */
					// self::$is_global = true;
				}
			}
			else {
			// in hub mode
				self::$is_global = true;
			}
		}

		// define page details
		add_action( 'init', function() {
			self::$page = array(
				'main_title' => __("Greyd.Suite", 'greyd_hub'),
				'slug'       => 'greyd_dashboard',
				'title'      => __('Dashboard', 'greyd_hub'),
				'url'        => admin_url('admin.php?page=greyd_dashboard'),
				'cap'        => 'edit_posts',
				'callback'   => function() {
					do_action( 'render_greyd_dashboard' );
				},
				'icon'        => plugin_dir_url( $this->config->plugin_file ) . '/assets/img/greyd-logo-dark.svg',
				'homepage'   => 'https://greyd.io/',
				'plugin_url' => GREYD_UPDATE_ZIP,
				'ok_icon'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/check-circle.svg',
				'alert_icon' => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/alert-triangle.svg',
				'info_icon'  => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/info-circle.svg',
			);
		}, 0 );

		/**
		 * Admin Menus and Admin Bar
		 */
		// add main menu
		add_action( 'admin_menu', array($this, 'add_menu'), 1 );
		// add main menu items to top
		add_action( 'admin_menu', array($this, 'add_submenu_top'), 2 );
		// add main menu items to bottom
		add_action( 'admin_menu', array($this, 'add_submenu_bottom'), 99 );
		// add other submenus
		add_action( 'admin_menu', array($this, 'add_submenu_misc'), 2 );
		// add adminbar group greyd_toolbar
		add_action( 'admin_bar_menu', array($this, 'add_adminbar_group'), 50 );
		// add adminbar items
		add_action( 'admin_bar_menu', array($this, 'add_adminbar_items'), 90 );

		/**
		 * Remove Admin Menu Link to Theme Customizer
		 */
		add_action( 'admin_menu', array($this, 'remove_theme_customizer'), 99 );
		add_action( 'admin_bar_menu', array($this, 'remove_adminbar_customizer'), 99 );


		if ( is_multisite() ) {
			// add menu
			add_action( 'network_admin_menu', array($this, 'add_menu_network'), 1 );
			// add submenu
			add_action( 'network_admin_menu', array($this, 'add_submenu_network'), 10 );
			// add adminbar item
			add_action( 'admin_bar_menu', array($this, 'add_adminbar_items_network'), 10 );
		}
		
		if ( ! is_admin() )  {
			// adminbar frontend (posts, pages)
			add_filter( 'greyd_admin_bar_group', array($this, 'add_admin_bar_items_misc'), 10, 2 );
		}
		else {
			// get adminbar colors
			add_action( 'admin_head', array( $this, 'get_wp_admin_css_colors' ) );

			// Greyd overlay
			add_action( 'admin_footer', array( $this, 'render_overlay' ), 98 );
		}
	}


	/**
	 * Holds Admin color schemes.
	 */
	public static $wp_admin_css_colors;

	/**
	 * Get admin color schmemes.
	 */
	public function get_wp_admin_css_colors() {
		global $_wp_admin_css_colors;
		self::$wp_admin_css_colors = $_wp_admin_css_colors;
	}

	/**
	 * Get admin bar color hex value.
	 *
	 * @return string
	 */
	public static function get_admin_bar_color() {
		if ( $admin_color_scheme = get_user_option( 'admin_color' ) ) {
			if ( isset( self::$wp_admin_css_colors[ $admin_color_scheme ] ) ) {
				return '--wp-admin-color-dark: ' . self::$wp_admin_css_colors[ $admin_color_scheme ]->colors[0];
			}
		}
		return '';
	}


	/*
	====================================================================================
		Admin Menus
	====================================================================================
	*/

	/**
	 * Add the main Greyd.Suite Menu.
	 * Link to the Dashboard with submenus.
	 */
	public function add_menu() {

		$menu = [
			'slug'      => self::$page['slug'],
			'title'     => self::$page['title'],
			'cap'       => self::$page['cap'],
			'callback'  => self::$page['callback']
		];

		/**
		 * change main Greyd.Suite Menu.
		 * 
		 * @filter 'greyd_menu_main'
		 * 
		 * @param array $menu   The main menu.
		 *      @property string slug       Main slug - should be 'greyd_dashboard'
		 *      @property string title      Page and Menu title
		 *      @property string cap        Capability
		 *      @property callback callback The render callback function
		 */
		$menu = apply_filters( 'greyd_menu_main', $menu );

		// add menu 'Greyd.Suite'
		add_menu_page(
			self::$page['main_title'], // page title
			self::$page['main_title'], // menu title
			$menu['cap'],       // capability
			$menu['slug'],      // slug
			$menu['callback'],  // function
			plugin_dir_url( $this->config->plugin_file ) . 'assets/img/greyd-menuicon-yellow.svg', // icon url
			71 // position
		);
		add_submenu_page(
			$menu['slug'],  // parent slug
			self::$page['main_title']." ".$menu['title'],  // page title
			$menu['title'], // menu title
			$menu['cap'],   // capability
			$menu['slug'],  // slug
			"", // function
			1 // position
		);

	}

	/**
	 * Add Greyd.Suite Submenu Items after Dashboard.
	 */
	public function add_submenu_top() {

		$submenu_pages_top = array(
			/*
			'slug' => [
				title:      Title of the page and menu (page_title can be used)
				page_title: page title (optional, defaults to title)
				menu_title: Menu title (optional, defaults to title)
				cap:        capability of the page (required, defaults to 'manage_options')
				slug:       menu_slug of the page (required, defaults to 'xxx')
				callback:   function to output the page (optional, defaults to '')
				position:   position in the menu (deprecated -> use priority)
				priority:   position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
			// dashboard
		);

		/**
		 * add some submenus right below 'Dashboard'.
		 * 
		 * @filter 'greyd_submenu_pages_top'
		 * 
		 * @param array $submenu_pages_top   All submenu pages.
		 */
		$submenu_pages_top = apply_filters( 'greyd_submenu_pages_top', $submenu_pages_top );

		// sort
		$submenu_pages_top = $this->sort_items($submenu_pages_top);

		// add items
		foreach( (array)$submenu_pages_top as $id => $args ) {
			$this->add_submenu_page($args, self::$page['slug']);
		}
	}

	/**
	 * Add Greyd.Suite Submenu Items after Posttypes and Plugins.
	 */
	public function add_submenu_bottom() {

		$submenu_pages = array(
			/*
			'slug' => [
				title:      Title of the page and menu (page_title can be used)
				page_title: page title (optional, defaults to title)
				menu_title: Menu title (optional, defaults to title)
				cap:        capability of the page (required, defaults to 'manage_options')
				slug:       menu_slug of the page (required, defaults to 'xxx')
				callback:   function to output the page (optional, defaults to '')
				position:   position in the menu (deprecated -> use priority)
				priority:   position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
			// dashboard
			// add_submenu_top()
			// Post Types
		);

		/**
		 * Modify or add submenu pages.
		 * 
		 * @filter 'greyd_submenu_pages'
		 * 
		 * @param array $submenu_pages   All submenu pages.
		 */
		$submenu_pages = apply_filters( 'greyd_submenu_pages', $submenu_pages );

		// sort
		$submenu_pages = $this->sort_items($submenu_pages);

		// add items
		foreach( (array)$submenu_pages as $id => $args ) {
			$this->add_submenu_page($args, self::$page['slug']);
		}
	}

	/**
	 * Add other Submenu Items outside the Greyd.Suite Menu.
	 */
	public function add_submenu_misc() {

		$misc_pages = array(
			/*
			'slug' => [
				parent:     Slug of the parent menu (required)
				title:      Title of the page and menu (page_title can be used)
				page_title: page title (optional, defaults to title)
				menu_title: Menu title (optional, defaults to title)
				cap:        capability of the page (required, defaults to 'manage_options')
				slug:       menu_slug of the page (required, defaults to 'xxx')
				callback:   function to output the page (optional, defaults to '')
				position:   position in the menu (optional, defaults to null)
			]
			*/
		);

		/**
		 * Modify or add misc pages.
		 * 
		 * @filter 'greyd_misc_pages'
		 * 
		 * @param array $misc_pages   All misc pages.
		 */
		$misc_pages = apply_filters( 'greyd_misc_pages', $misc_pages );

		// no need to fix position because items have individual parents

		// add items
		foreach( (array)$misc_pages as $id => $args ) {
			$this->add_submenu_page($args, $args['parent']);
		}
	}

	/**
	 * Add the network_admin Greyd.Suite Menu.
	 * Link to Hub with submenus.
	 */
	public function add_menu_network() {

		$menu = [
			'slug'      => "greyd_hub",
			'title'     => self::$page['title'],
			'cap'       => self::$page['cap'],
			'callback'  => self::$page['callback']
		];

		/**
		 * change main Greyd.Suite Menu in network-admin.
		 * 
		 * @filter 'greyd_menu_main_network'
		 * 
		 * @param array $menu   The main menu.
		 *      @property string slug       Main slug - should be "greyd_hub"
		 *      @property string title      Page and Menu title
		 *      @property string cap        Capability
		 *      @property callback callback The render callback function
		 */
		$menu = apply_filters( 'greyd_menu_main_network', $menu );

		// add menu 'Greyd.Suite'
		add_menu_page(
			self::$page['main_title'], // page title
			self::$page['main_title'], // menu title
			$menu['cap'],       // capability
			$menu['slug'],      // slug
			$menu['callback'],  // function
			plugin_dir_url( $this->config->plugin_file ) . 'assets/img/greyd-menuicon-yellow.svg', // icon url
			71 // position
		);
		add_submenu_page(
			$menu['slug'],  // parent slug
			$menu['title'], // page title
			$menu['title'], // menu title
			$menu['cap'],   // capability
			$menu['slug'],  // slug
			"", // function
			1 // position
		);

	}
	
	public function remove_theme_customizer(): void {

		if ( Helper::is_greyd_classic() ) {
			return;
		}

		global $submenu;
		if ( isset( $submenu['themes.php'] ) ) {
			foreach ( $submenu['themes.php'] as $index => $menu_item ) {
				if ( $menu_item[0] === 'Customize' ) {
					unset( $submenu['themes.php'][$index] );
					break;
				}
			}
		}
	}

	public function remove_adminbar_customizer(): void {

		if ( Helper::is_greyd_classic() ) {
			return;
		}

		global $wp_admin_bar;
		if ( $wp_admin_bar->get_node( 'customize' ) ) {
			$wp_admin_bar->remove_node( 'customize' );
		}
	}

	/**
	 * Add network_admin Greyd.Suite Submenu Items after Hub.
	 */
	public function add_submenu_network() {

		$submenu_pages = array(
			/*
			'slug' => [
				title:      Title of the page and menu (page_title can be used)
				page_title: page title (optional, defaults to title)
				menu_title: Menu title (optional, defaults to title)
				cap:        capability of the page (required, defaults to 'manage_options')
				slug:       menu_slug of the page (required, defaults to 'xxx')
				callback:   function to output the page (optional, defaults to '')
				position:   position in the menu (deprecated -> use priority)
				priority:   position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
			// Greyd.Hub
		);

		/**
		 * Modify or add network submenu pages below Greyd.Hub.
		 * 
		 * @filter 'greyd_submenu_pages_network'
		 * 
		 * @param array $submenu_pages   All submenu pages.
		 */
		$submenu_pages = apply_filters( 'greyd_submenu_pages_network', $submenu_pages );

		// sort
		$submenu_pages = $this->sort_items($submenu_pages);

		// add items
		foreach( (array)$submenu_pages as $id => $args ) {
			$this->add_submenu_page($args, "greyd_hub");
		}
	}

	/*
	====================================================================================
		Admin Bar
	====================================================================================
	*/

	/**
	 * Add Adminbar Items or Group.
	 * in frontend, items can be grouped in parent Item with id 'greyd_toolbar'.
	 * @param object $wp_admin_bar  WP Adminbar Object
	 */
	public function add_adminbar_group($wp_admin_bar) {

		$items  = array(
			/*
			[
				id:       ID/Slug of the Item (required)
				title:    Title of the Item (required)
				href:     Full URL of the Item (required)
				parent:   Slug of the parent item (optional, use 'greyd_toolbar' to add item to Greyd item)
				priority: position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
		);

		// in frontend
		if (!is_admin()) {

			/* 
			 * Greyd menu item
			 */
			if ( current_user_can('edit_posts') ) {
				$icon = '<span class="ab-icon"><img style="vertical-align:unset;margin-top:3px;" src="'.plugin_dir_url( $this->config->plugin_file ) . 'assets/img/greyd-menuicon-yellow.svg"></span>';
				$args = array(
					'id'    => "greyd_toolbar",
					'title' => $icon.self::$page['main_title'],
					'href'  => self::$page['url'],
				);
				array_push( $items, $args );
			}

		}

		/**
		 * Modify or add admin_bar items.
		 * In fronted, greyd_toolbar group is created.
		 * use is_admin() and is_network_admin() to check if in frontend or backend.
		 * 
		 * @filter 'greyd_admin_bar_group'
		 * 
		 * @param array $items   All items.
		 */
		$items = apply_filters( "greyd_admin_bar_group", $items );

		// sort
		$items = $this->sort_items($items);

		// add items
		foreach ( $items as $args ) {
			// add admin_bar item
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Add Adminbar Items at last position.
	 * @param object $wp_admin_bar  WP Adminbar Object
	 */
	public function add_adminbar_items($wp_admin_bar) {

		$items  = array(
			/*
			[
				id:       ID/Slug of the Item (required)
				title:    Title of the Item (required)
				href:     Full URL of the Item (required)
				parent:   Slug of the parent item (optional, use 'greyd_toolbar' to add item to Greyd item)
				priority: position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
		);

		/**
		 * Modify or add admin_bar items at last position.
		 * use is_admin() and is_network_admin() to check if in frontend or backend.
		 * 
		 * @filter 'greyd_admin_bar_items'
		 * 
		 * @param array $items   All items.
		 */
		$items = apply_filters( "greyd_admin_bar_items", $items );

		// sort
		$items = $this->sort_items($items);

		// add items
		foreach ( $items as $args ) {
			// add admin_bar item
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Add Adminbar Items to network-admin Menu.
	 * @param object $wp_admin_bar  WP Adminbar Object
	 */
	public function add_adminbar_items_network($wp_admin_bar) {

		$items  = array(
			/*
			[
				id:       ID/Slug of the Item (required)
				title:    Title of the Item (required)
				href:     Full URL of the Item (required)
				parent:   Slug of the parent item (optional, use 'greyd_toolbar' to add item to Greyd item)
				priority: position of the tab (optional, 0: first, 99: last, defaults to 10)
			]
			*/
		);

		/**
		 * Modify or add items to network-admin in admin_bar.
		 * use is_admin() and is_network_admin() to check if in frontend or backend.
		 * 
		 * @filter 'greyd_admin_bar_items_network'
		 * 
		 * @param array $items   All items.
		 */
		$items = apply_filters( "greyd_admin_bar_items_network", $items );

		// sort
		$items = $this->sort_items($items);

		// add items
		foreach ( $items as $args ) {
			// add admin_bar item
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Add Customizer and Posts/Pages items to Greyd.Suite adminbar group.
	 * @see filter 'greyd_admin_bar_group'
	 */
	public function add_admin_bar_items_misc( $items ) {

		// add Posts to Greyd.Suite parent group
		if ( current_user_can('edit_posts') ) {
			$args = array(
				'parent' => "greyd_toolbar",
				'id'     => 'posts',
				'title'  => __("Posts", 'greyd_hub'),
				'href'   => admin_url('edit.php'),
				'priority' => 9
			);
			array_push( $items, $args );
		}

		// add Pages to Greyd.Suite parent group
		if ( current_user_can('edit_pages') ) {
			$args = array(
				'parent' => "greyd_toolbar",
				'id'     => 'pages',
				'title'  => __("Pages", 'greyd_hub'),
				'href'   => admin_url('edit.php?post_type=page'),
				'priority' => 10
			);
			array_push( $items, $args );
		}

		return $items;
	}

	/*
	====================================================================================
		Admin Navi and Bar Helper
	====================================================================================
	*/

	/**
	 * Sort Items by priority
	 * @param array $items
	 * @return array $items the sorted Items
	 */
	public function sort_items($items) {

		/**
		 * fix position
		 * https://developer.wordpress.org/reference/functions/add_submenu_page/
		 * Position Parameter is handled explicitly, meaning the exact index-position in the menu-array.
		 * We want the Position to act like weight or priority from 0 to 99 (or more)
		 */
		foreach( (array) $items as $id => $args ) {
			$args = (array) $args;
			if (!isset($args['priority'])) $args['priority'] = 10;
			if (isset($args['position'])) {
				$args['priority'] = $args['position'];
				unset($args['position']);
			}
			$items[$id] = $args;
		}
		// sort by priority
		usort($items, function($a, $b) {
			return $a['priority'] - $b['priority'];
		});

		return $items;
	}

	/**
	 * Wrapper function for WP add_submenu_page.
	 * @param array $args {
	 *      Parameters for the original function.
	 *      @property string title      Title of the page and menu (page_title can be used)
	 *      @property string page_title page title (optional, defaults to title)
	 *      @property string menu_title Menu title (optional, defaults to title)
	 *      @property string cap        capability of the page (required, defaults to 'manage_options')
	 *      @property string slug       menu_slug of the page (required, defaults to 'xxx')
	 *      @property string callback   function to output the page (optional, defaults to '')
	 *      @property string position   position in the menu (optional, defaults to null)
	 * }
	 * @param string $parent    Slug of the parent menu
	 */
	public function add_submenu_page($args, $parent) {

		// make titles
		$title      = isset($args['title']) ? $args['title'] : ( isset($args['page_title']) ? $args['page_title'] : 'xxx' );
		$menu_title = isset($args['menu_title']) ? $args['menu_title'] : $title;

		// add item
		$hook = add_submenu_page(
			$parent, // parent slug
			$title,
			$menu_title,
			isset($args['cap']) ? $args['cap'] : 'manage_options',
			isset($args['slug']) ? $args['slug'] : 'xxx',
			isset($args['callback']) ? $args['callback'] : '',
			isset($args['position']) ? intval($args['position']) : null
		);

		// add hook
		if ( isset($args['hook']) ) {
			add_action( "load-$hook", $args['hook'] );
		}

	}


	/*
	====================================================================================
		Overlay
	====================================================================================
	*/

	/**
	 * Render the an action overlay.
	 * Used for confimation and/or loading actions via ajax.
	 * 
	 * Apply contents using the @filter greyd_overlay_contents. As soon as
	 * some contents are registered, the overlay is automatically rendered
	 * at the bottom of the page.
	 */
	public function render_overlay() {

		/**
		 * Get all contents
		 * 
		 * @filter greyd_overlay_contents
		 * 
		 * Example:
		 * 
		 * array(
		 *      'export' => array(
		 *          'confirm' => array(
		 *              'title'     => __("Export", 'greyd_hub'),
		 *              'descr'     => __('Post als JSON-Datei herunterladen.', 'greyd_hub'),
		 *              'button'    => __("export now", 'greyd_hub')
		 *          ),
		 *          'decision' => array(
		 *              'title'     => __("Export", 'greyd_hub'),
		 *              'descr'     => __('Post als JSON-Datei herunterladen.', 'greyd_hub'),
		 *              'button1'   => __("back to the overview", 'greyd_hub'),
		 *              'button2'   => __("edit template", 'greyd_hub'),
		 *          ),
		 *          'loading' => array(
		 *              'title'     => __("Post wird exportiert", 'greyd_hub'),
		 *              'escape'    => false,
		 *          ),
		 *          'reload' => array(
		 *              'title'     => __("Export successful", 'greyd_hub'),
		 *              'descr'     => __("Post has been exported.", 'greyd_hub')
		 *          ),
		 *          'fail' => array(
		 *              'title'     => __("Export failed", 'greyd_hub'),
		 *              'descr'     => __("The post could not be exported.", 'greyd_hub')
		 *          ),
		 *          {{other}} => array(
		 *              'title'     => some title,
		 *              'content'   => some content
		 *          ),
		 *      )
		 *  )
		 */
		$overlay_contents = apply_filters( 'greyd_overlay_contents', array() );

		if ( !is_array($overlay_contents) || count($overlay_contents) === 0 ) return;

		// format array for loop
		$loop_arr = array(
			'confirm' => array(),
			'decision' => array(),
			'loading' => array(),
			'success' => array(),
			'fail'    => array(),
		);
		foreach( $overlay_contents as $action => $states ) {
			foreach ( $states as $type => $contents ) {
				if ( !isset( $loop_arr[$type] ) ) {
					$loop_arr[$type] = array(); // eg. $loop_arr['confirm'] = array();
				}
				if ( $type === 'loading')  {
					if ( !is_array($contents) ) {
						$contents = array( 'descr' => $contents );
					}
					$contents['title'] = isset($contents['title']) ? $contents['title'] : __("Please wait...", 'greyd_hub');
				}
				$loop_arr[$type][$action] = $contents; // eg. $loop_arr['confirm']['export'] = $contents;
			}
		}

		// render
		echo '<div id="overlay" class="greyd_overlay hidden">';

		foreach ( $loop_arr as $type => $actions ) {

			/**
			 * types: confirm, loading, success ...
			 */
			echo '<div class="'.$type.' hidden">';

			/**
			 * actions: export, import ...
			 */
			foreach ( (array)$actions as $action => $contents ) {

				// title
				if ( isset($contents['title']) ) {
					echo '<h3 class="depending '.$action.' hidden">'.$contents["title"].'</h3>';
				}
				// text
				if ( isset($contents['descr']) ) {
					echo '<p class="depending '.$action.' hidden">'.$contents["descr"].'</p>';
				}
				// content
				if ( isset($contents['content']) ) {
					echo '<div class="depending '.$action.' hidden">'.$contents["content"].'</div>';
				}
			}

			// bottom default states elements
			if ( $type === 'loading' ) {

				echo '<div class="loader"></div>';
				echo '<a href="javascript:window.location.href=window.location.href" class="color_light escape">'.__("Cancel", 'greyd_hub').'</a>';
			}
			else if ( $type === 'alert' ) {

				echo '<div class="flex flex-end" style="margin-top:2em;">
						<button role="escape" class="button huge">'.__("Close", 'greyd_hub').'</button>
					</div>';
			}
			else if ( $type === 'confirm' ) {

				echo '<div class="flex" style="margin-top:1em;">
					<button role="escape" class="button huge button-cancel">'.__("Cancel", 'greyd_hub').'</button>';
				foreach ( $actions as $action => $contents ) {

					$button         = isset($contents["button"]) ? $contents["button"] : __("Confirm", 'greyd_hub');
					$button_class   = isset($contents["button_class"]) ? 'button-'.$contents["button_class"] : 'button-primary';
					echo '<button role="confirm" class="button '.$button_class.' huge depending '.$action.' hidden">'.$button.'</button>';
				}
				echo '</div>';
			}
			else if ( $type === 'success' || $type === 'reload' ) {

				echo '<div class="success_mark"><div class="checkmark"></div></div>';
			}
			else if ( $type === 'fail' ) {

				echo '<div class="color_red" style="text-align:center;"><span class="dashicons dashicons-warning" style="font-size: 50px;height: 1em;width: 1em;"></span></div>';
				echo '<a href="javascript:greyd.backend.triggerOverlay(false)" class="color_light escape">'.__("Close", 'greyd_hub').'</a>';
			} 
			else if ($type === 'decision') {

				// echo '<div class="success_mark"><div class="checkmark"></div></div>';
				echo '<div class="flex">';

					foreach ($actions as $action => $contents) {
						$button1 = isset($contents["button1"]) ? $contents["button1"] : __('Choice 1', 'greyd_hub');
						$button2 = isset($contents["button2"]) ? $contents["button2"] : __('Choice 2', 'greyd_hub');
						$button1_class   = isset($contents["button1_class"]) ? 'button-'.$contents["button1_class"] : 'button-primary';
						$button2_class   = isset($contents["button2_class"]) ? 'button-'.$contents["button2_class"] : 'button-secondary';

						echo "<button role='decision' decision='0' class='button {$button1_class} huge depending ".$action." hidden' style='flex:3;margin-right: 16px;'>".$button1."</button>";
						echo "<button role='decision' decision='1' class='button {$button2_class} huge depending ".$action." hidden' style='flex:3;'>".$button2."</button>";
					}
				echo '</div>';
			}

			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Build an overlay directly.
	 * Used for the Template Library, Greyd.Hub Wizard
	 * 
	 * @param array $args
	 *      @property string content    HTML content of the overlay. [required]
	 *      @property string ID         ID of the overlay.
	 *      @property string class      Additional CSS class names.
	 *      @property string head       HTML content of the header bar (eg. tabs).
	 *      @property string foot       HTML content of the footer bar.
	 *      @property string sidebar    HTML content of the sidebar.
	 *      @property array atts        HTML attributes for the wrapper.
	 * 
	 * @return string HTML tags
	 */
	public static function build_overlay( array $args=array() ) {
		$args = wp_parse_args( $args, array(
			"content"   => "", // required
			"ID"        => "",
			"class"     => "",
			"head"      => "",
			"foot"      => "",
			"sidebar"   => "",
			"atts"      => array()
		) );

		if ( empty( $args['content'] ) ) return "";

		if ( ! empty($args['sidebar']) ) $args['class'] .= " has-sidebar";
		
		return "<div class='greyd_overlay_v2 {$args['class']}' id='{$args['ID']}' ".implode(" ", $args['atts']).">
			<div class='overlay_bg' onclick='greyd.backend.closeOverlay();'></div>
			<div class='overlay_popup'>
				<div class='overlay_head'>
					{$args['head']}
					<span class='overlay_close dashicons dashicons-no-alt' onclick='greyd.backend.closeOverlay();'></span>
				</div>
				<div class='overlay_content'>
					".( empty($args['sidebar']) ? "" : "<div class='overlay_sidebar'>{$args['sidebar']}</div>" )."
					<div class='overlay_inner_content'>{$args['content']}</div>
				</div>
				".( empty($args['foot']) ? "" : "<div class='overlay_foot'>{$args['foot']}</div>" )."
			</div>
		</div>";
	}
}