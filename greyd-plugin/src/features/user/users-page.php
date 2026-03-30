<?php
/**
 * Main Admin page to manage users
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Users_Page( $config );
class Users_Page {

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
	 * Class constructor.
	 */
	public function __construct( $config ) {

		if ( !Manage::wp_up_to_date() ) {
			return;
		}
		
		// set config
		$this->config = (object) $config;
		if ( isset( $this->config->is_standalone ) && $this->config->is_standalone == true ) {
			// standalone mode
			self::$is_standalone = true;
		}

		// define page details
		add_action(
			'init',
			function() {
				self::$page = array(
					'slug'     => 'greyd_user',
					'title'    => __( 'User Management', 'greyd_hub' ),
					'url'      => is_network_admin() ? network_admin_url( 'admin.php?page=greyd_user' ) : admin_url( 'admin.php?page=greyd_user' ),
					'cap'      => 'list_users',
					'callback' => array( $this, 'render_page' ),
				);
				// debug(self::$page);
			},
			0
		);

		// greyd backend
		if ( is_admin() ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ) );

			if ( self::$is_standalone ) {
				// standalone mode
				add_action( 'admin_menu', array( $this, 'standalone_submenu' ), 40 );
			} else {
				// in hub
				add_filter( 'greyd_submenu_pages_network', array( $this, 'add_greyd_submenu_page_network' ) );
				add_filter( 'greyd_submenu_pages', array( $this, 'add_greyd_submenu_page' ) );
				add_filter( 'greyd_dashboard_tabs', array( $this, 'add_greyd_dashboard_tab' ) );
			}
		}

	}

	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * add scripts
	 */
	public function load_backend_scripts() {

		// load helper scripts in standalone mode
		if ( isset( $_GET['page'] ) && $_GET['page'] === self::$page['slug'] ) {
			
			$css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
			$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

			// Styles
			if ( self::$is_standalone ) {
				wp_register_style( $this->config->plugin_name . '_backend_css', $css_uri . '/backend.css', null, GREYD_VERSION, 'all' );
				wp_enqueue_style( $this->config->plugin_name . '_backend_css' );
			}
			wp_register_style( $this->config->plugin_name . '_user_css', $css_uri . '/user.css', null, GREYD_VERSION, 'all' );
			wp_enqueue_style( $this->config->plugin_name . '_user_css' );

			// Scripts
			// if (isset($_GET["action"]) && ($_GET["action"] == "edit" || $_GET["action"] == "view" || $_GET["action"] == "create")) {
			if ( isset( $_GET['tab'] ) && ( $_GET['tab'] == 'roles' || $_GET['tab'] == 'settings' || $_GET['tab'] == 'mail' ) ) {
				wp_register_script( $this->config->plugin_name . '_user_js', $js_uri . '/user.js', null, GREYD_VERSION, true );
				wp_enqueue_script( $this->config->plugin_name . '_user_js' );
				// inline script before
				// define global greyd var
				wp_add_inline_script( $this->config->plugin_name . '_user_js', 'var greyd = greyd || {};', 'before' );

				// only on role edit page
				if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'edit' || $_GET['action'] == 'view' || $_GET['action'] == 'create' ) ) {
					// metafields-table script
					wp_register_script( $this->config->plugin_name . '_meta_table_js', $js_uri . '/meta-table.js', array( 'jquery' ), GREYD_VERSION, true );
					wp_enqueue_script( $this->config->plugin_name . '_meta_table_js' );
					// metabox script
					wp_enqueue_script( 'postbox' );
				}
				// only on mails page
				if ( $_GET['tab'] == 'mail' ) {
					// metabox script
					wp_enqueue_script( 'postbox' );
				}
			}
		}

		$screen = get_current_screen();

		/**
		 * Add filePicker & linkPicker to user edit page
		 * @example https://www.website.de/wp-admin/user-edit.php?user_id=1
		 * 
		 * @since 1.7.0
		 */
		if ( isset( $_GET['user_id'] ) || ( $screen && $screen->base == 'profile' ) ) {
			
			$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

			wp_enqueue_media();
			wp_register_script( $this->config->plugin_name . '_eidtUser_js', $js_uri . '/edit-user.js', null, GREYD_VERSION, true );
			wp_enqueue_script( $this->config->plugin_name . '_eidtUser_js' );
		}

	}

	/**
	 * Add a standalone submenu item to general settings if Hub is not installed
	 */
	public function standalone_submenu() {
		add_submenu_page(
			'options-general.php', // parent slug
			self::$page['title'],  // page title
			self::$page['title'], // menu title
			self::$page['cap'], // capability
			self::$page['slug'], // slug
			self::$page['callback'], // function
			80 // position
		);
	}

	/**
	 * Add the network submenu item to Greyd.Suite
	 *
	 * @see filter 'greyd_submenu_pages_network'
	 */
	public function add_greyd_submenu_page_network( $submenu_pages ) {
		// debug($submenu_pages);

		array_push(
			$submenu_pages,
			array(
				'title'    => self::$page['title'],
				'cap'      => self::$page['cap'],
				'slug'     => self::$page['slug'],
				'callback' => self::$page['callback'],
				'position' => 80,
			)
		);

		return $submenu_pages;
	}

	/**
	 * Add the submenu item to Greyd.Suite if Hub is installed
	 *
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page( $submenu_pages ) {
		// debug($submenu_pages);

		array_push(
			$submenu_pages,
			array(
				'page_title' => __( 'Greyd.Suite', 'greyd_hub' ) . ' ' . self::$page['title'],
				'menu_title' => self::$page['title'],
				'cap'        => self::$page['cap'],
				'slug'       => self::$page['slug'],
				'callback'   => self::$page['callback'],
				'position'   => 50,
			)
		);

		return $submenu_pages;
	}

	/**
	 * Add dashboard tab if Hub is installed
	 *
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab( $tabs ) {
		// debug($tabs);

		$tabs[ self::$page['slug'] ] = array(
			'title'    => self::$page['title'],
			'slug'     => self::$page['slug'],
			'url'      => self::$page['url'],
			'cap'      => self::$page['cap'],
			'priority' => 50,
		);

		return $tabs;
	}


	/*
	=======================================================================
		render
	=======================================================================
	*/

	/**
	 * Render the page
	 */
	public function render_page() {

		if ( ! is_admin() ) {
			return;
		}

		// init sub-pages
		$pages = array();
		$pages[] = array(
			'slug'     => 'users',
			'icon'     => 'admin-users',
			// "class"     => "wp",
			'title'    => __( 'Users', 'greyd_hub' ),
			'callback' => array( $this, 'render_users_page' ),
			'cap'      => self::$page['cap'],
		);

		// roles page
		if ( Manage::wp_up_to_date( true ) ) {
			$roles_page    = new Roles_Page();
			$pages[] = array(
				'slug'     => 'roles',
				'icon'     => 'groups',
				// "class"     => "wp",
				'title'    => __( 'Roles', 'greyd_hub' ),
				'callback' => array( $roles_page, 'render_roles_page' ),
				'cap'      => self::$page['cap'],
				'args'     => array( 'edit_users' ),
			);
		}

		// settings page
		$settings_page = new Settings_Page();
		$pages[] = array(
			'slug'     => 'settings',
			'icon'     => 'admin-links',
			'title'    => __( "Links", 'greyd_hub' ),
			'callback' => array( $settings_page, 'render_settings_page' ),
			'cap'      => 'manage_options',
		);

		// mails page
		$mails_page    = new Mails_Page();
		$pages[] = array(
			'slug'     => 'mail',
			'icon'     => 'email',
			'title'    => __( 'Emails', 'greyd_hub' ),
			'callback' => array( $mails_page, 'render_mail_page' ),
			'cap'      => 'manage_options',
		);
		
		$current_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'users';
		// debug($current_tab);

		echo "<div class='wrap settings_wrap'>"; // wrap

			// render the tabs
			echo "<div class='greyd_tabs'>";
		foreach ( $pages as $page ) {
			if ( ! current_user_can( $page['cap'] ) ) {
				continue;
			}
			$page  = wp_parse_args(
				$page,
				array(
					'slug'  => '',
					'icon'  => 'screenoptions',
					'title' => 'Tab',
					'class' => '',
				)
			);
			$url   = self::$page['url'] . '&tab=' . $page['slug'];
			$class = 'tab ' . $page['class'] . ( $current_tab == $page['slug'] ? ' active' : '' );
			$icon  = "<span class='dashicons dashicons-" . $page['icon'] . "'></span>";
			echo "<a href='" . $url . "' class='" . $class . "'>" . $icon . $page['title'] . '</a>';
		}
			echo '</div>';

			// render the content
		foreach ( $pages as $page ) {
			if ( ! current_user_can( $page['cap'] ) ) {
				continue;
			}
			if ( $current_tab != $page['slug'] ) {
				continue;
			}
			$page = wp_parse_args(
				$page,
				array(
					'slug'     => '',
					'callback' => null,
					'args'     => array(),
				)
			);
			call_user_func_array( $page['callback'], $page['args'] );
		}

		echo '</div>'; // end of wrap
	}

	/**
	 * Render users page
	 */
	public function render_users_page() {

		// title
		echo "<h1 class='wp-heading-inline'>" . __( 'Users', 'greyd_hub' ) . '</h1>';

		// network admin
		if ( is_multisite() && is_network_admin() ) {
			// new user link
			echo "<a href='" . network_admin_url( 'user-new.php' ) . "' class='page-title-action'>" . __( 'New user', 'greyd_hub' ) . '</a>';
			// users table
			$users_table = new \WP_MS_Users_List_Table( array( 'screen' => 'users' ) );
		}
		// site admin
		else {
			// new user link
			echo "<a href='" . admin_url( 'user-new.php' ) . "' class='page-title-action'>" . __( 'New user', 'greyd_hub' ) . '</a>';
			// users table
			$users_table = new \WP_Users_List_Table( array( 'screen' => 'users' ) );
		}
		// debug($users_table);

		// prepare
		$users_table->prepare_items();
		// render
		echo '<form id="users-form" method="post">';
			$users_table->search_box( __( 'Find user', 'greyd_hub' ), 'users' );
			$users_table->display();
		echo '</form>';

	}


}
