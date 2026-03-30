<?php
/**
 * Admin Dashboard
 */
namespace Greyd;

use Greyd\Helper;
use Greyd\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Dashboard( $config );
class Dashboard {

	/**
	 * Holds the plugin config.
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// set config
		$this->config = (object) $config;
		
		add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts'), 9 );

		// add greyd header
		add_action( 'in_admin_header', array( $this, 'add_greyd_header' ), 1 );

		add_action( 'render_greyd_dashboard', array( $this, 'render_dashboard' ) );

		// add dashboard panels
		add_action( 'greyd_dashboard_active_panels', array( $this, 'add_dashboard_panels' ) );

		// activate features
		add_action( 'wp_ajax_activate_greyd_features', array( $this, 'activate_greyd_features' ) );

		// install Greyd Plugins
		add_action( 'wp_ajax_install_greyd_plugins', array( $this, 'install_greyd_plugins' ) );

		// install Greyd Theme
		add_action( 'wp_ajax_install_greyd_theme', array( $this, 'install_greyd_theme' ) );

		// add overlay contents
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );
	}

	/**
	 * Load scripts in the admin area.
	 */
	public function load_backend_scripts() {

		$uri = plugin_dir_url( __FILE__ ) . 'assets';

		wp_register_style(
			"greyd-admin-dashboard",
			$uri . '/css/admin-dashboard.css',
			array(),
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style(
			"greyd-admin-dashboard"
		);

		// Scripts
		wp_register_script(
			"greyd-admin-dashboard",
			$uri . '/js/admin-dashboard.js',
			array('greyd-admin-script'),
			GREYD_VERSION,
			true
		);
		wp_enqueue_script(
			"greyd-admin-dashboard"
		);
	}

	/*
	====================================================================================
		WP Dashboard Widget
	====================================================================================
	*/

	/**
	 * function to add dashboard panels if theme is a block theme
	 */
	public function add_dashboard_panels( $panels ) {

		if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
			$panels['global-styles']   = true;
			$panels['template-editor'] = true;
		}

		return $panels;
	}

	/*
	====================================================================================
		Dashboard Page
	====================================================================================
	*/
	/**
	 * add Greyd CI
	 */
	public function add_greyd_header() {

		$screen = get_current_screen();

		// only render on our page
		if ( $screen->base !== 'toplevel_page_greyd_dashboard' ) {
			return;
		}

		if ( Helper::is_greyd_classic() ) {
			return;
		}

		// render
		echo '<div class="greyd_dashboard--header">';
		echo '<div class="greyd_dashboard--header--content">';
		echo '<div class="greyd_dashboard--header--content-title">';
		echo '<a class="greyd_admin_logo" href="' . Admin::$page['homepage'] . '" target="_blank" title="' . __( 'Greyd website', 'greyd_hub' ) . '"><img src="' . Admin::$page['icon'] . '" width="46" alt="' . __( 'Greyd logo', 'greyd_hub' ) . '" /></a>';
		echo '<h1>' . __( 'Welcome to the Greyd Plugin', 'greyd_hub' ) . '</h1>';
		echo '</div>';
		echo '<div class="greyd_dashboard--header--content-button">';
		echo '<a class="greyd_admin_link--outline download_greyd_plugin" href="' . Admin::$page['plugin_url'] . '" download><span class="text">' . __( 'Download latest version', 'greyd_hub' ) . '</span></a>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Master Panel Array
	 */
	public function dashboard_panels() {

		// setup panels
		$panels = array(
			/*
			'slug' => array(
				title:      title of the panel (required)
				descr:      description for the panel (optional)
				image:      image for the top section (required)
				image_alt:  alt text for the image (optional)
				help_url:   link to the helpcenter article (optional)
				feat_url:   link to the feature page (optional)
				btn_text:   text for the button (required)
				btn_url:    url for the button (required)
				btn_target: _self by default, change to _blank for external links (optional)
				slug:       feature slug (optional)
				requires:   feature that is required for this feature (optional)
				cap:        capability for the panel (required)
				priority:   position of the panel (optional, 0: first, 99: last, defaults to 10)
			)
			*/
			'global-styles'       => array(
				'title'     => __( 'Global Styles', 'greyd_hub' ),
				'descr'     => __( 'Define colors, spacings and more globally for your entire website.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/global-styles.jpg',
				'image_alt' => __( 'Screenshot of the Global Styles in the Site Editor', 'greyd_hub' ),
				'help_url'  => __( 'https://docs.greyd.io/video/how-to-set-up-the-design-of-your-website/', 'greyd_hub' ),
				'btn_text'  => __( 'Open Global Styles', 'greyd_hub' ),
				'btn_url'   => admin_url( 'site-editor.php?canvas=edit' ),
				'cap'       => 'edit_theme_options',
				'priority'  => 10,
			),
			'template-editor'     => array(
				'title'     => __( 'Template Editor', 'greyd_hub' ),
				'descr'     => __( 'Create and edit templates, patterns and template parts.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/template-editor.jpg',
				'image_alt' => __( 'Screenshot of the Browse Mode in the Site Editor', 'greyd_hub' ),
				'help_url'  => __( 'https://docs.greyd.io/video/how-do-i-work-with-templates-and-template-parts/', 'greyd_hub' ),
				'btn_text'  => __( 'Open Template Editor', 'greyd_hub' ),
				'btn_url'   => admin_url( 'site-editor.php' ),
				'cap'       => 'edit_theme_options',
				'priority'  => 10,
			),
			'greyd-global-styles' => array(
				'title'     => __( 'Greyd Global Styles', 'greyd_hub' ),
				'descr'     => __( 'Additional global styles and responsive options.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/greyd-styles.jpg',
				'image_alt' => __( 'Screenshot of the Greyd Global Styles in the Site Editor sidebar', 'greyd_hub' ),
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-theme-greyd-wp/greyd-global-styles/', 'greyd_hub' ),
				'btn_text'  => __( 'Open Greyd Global Settings', 'greyd_hub' ),
				'btn_url'   => admin_url( 'site-editor.php?canvas=edit&greyd-styles=open' ),
				'cap'       => 'edit_theme_options',
				'priority'  => 10,
			),
			'dynamic-post-types'  => array(
				'title'     => __( 'Dynamic Post Types', 'greyd_hub' ),
				'descr'     => __( 'Custom post types and taxonomies for easier content management and maximum flexibility in design.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/posttypes.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/post-types/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/dynamic-post-types/', 'greyd_hub' ),
				'btn_text'  => __( 'Create a post type', 'greyd_hub' ),
				'btn_url'   => admin_url( 'edit.php?post_type=tp_posttypes' ),
				'slug'      => 'posttypes',
				'cap'       => get_post_type_object( 'tp_posttypes' ) ? get_post_type_object( 'tp_posttypes' )->cap->edit_posts : 'edit_pages',
				'priority'  => 6,
			),
			'dynamic-templates'   => array(
				'title'     => __( 'Dynamic Templates', 'greyd_hub' ),
				'descr'     => __( 'Flexible layout templates that can be used with different content at different places.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/dynamic-templates.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/dynamic-templates/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/dynamic-templates/', 'greyd_hub' ),
				'btn_text'  => __( 'Create a template', 'greyd_hub' ),
				'btn_url'   => admin_url( 'edit.php?post_type=dynamic_template' ),
				'slug'      => 'dynamic',
				'requires'  => 'dynamic-post-types',
				'cap'       => get_post_type_object( 'dynamic_template' ) ? get_post_type_object( 'dynamic_template' )->cap->edit_posts : 'edit_pages',
				'priority'  => 2,
			),
			'greyd-hub'           => array(
				'title'     => __( 'Greyd.Hub', 'greyd_hub' ),
				'descr'     => __( 'Website management platform with numerous admin features like staging sites, backups & migration assistant.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/greyd-hub.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/greyd-hub/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/greyd-hub/', 'greyd_hub' ),
				'btn_text'  => __( 'Open the Greyd.Hub', 'greyd_hub' ),
				'btn_url'   => is_multisite() ? network_admin_url('admin.php?page=greyd_hub') : admin_url( 'admin.php?page=greyd_hub' ),
				'cap'       => ! Admin::$is_standalone && Admin::$is_global ? 'manage_sites' : 'install_plugins',
				'priority'  => 1,
			),
			'greyd-forms'         => array(
				'title'     => __( 'Greyd.Forms', 'greyd_hub' ),
				'descr'     => __( 'Powerful form generator with many interfaces, native double opt-in, and much more.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/greyd-forms.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-forms/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/greyd-forms/', 'greyd_hub' ),
				'btn_text'  => __( 'Create a form', 'greyd_hub' ),
				'btn_url'   => admin_url( 'edit.php?post_type=tp_forms' ),
				'slug'      => 'greyd_forms',
				'cap'       => get_post_type_object( 'tp_forms' ) ? get_post_type_object( 'tp_forms' )->cap->edit_posts : 'edit_posts',
				'priority'  => 10,
			),
			'greyd-popups'        => array(
				'title'     => __( 'Greyd.Popups', 'greyd_hub' ),
				'descr'     => __( 'Create custom pop-ups with a wide range of triggers and conditions.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/popups.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/pop-ups/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/greyd-popups/', 'greyd_hub' ),
				'btn_text'  => __( 'Create a pop-up', 'greyd_hub' ),
				'btn_url'   => admin_url( 'edit.php?post_type=greyd_popup' ),
				'slug'      => 'popups',
				'cap'       => get_post_type_object( 'greyd_popup' ) ? get_post_type_object( 'greyd_popup' )->cap->edit_posts : 'edit_posts',
				'priority'  => 7,
			),
			// 'template-library'    => array(
			// 	'title'     => __( 'Template Library', 'greyd_hub' ),
			// 	'descr'     => __( 'Download free website templates that are 100% customizable.', 'greyd_hub' ),
			// 	'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/template-library.jpg',
			// 	'image_alt' => '',
			// 	'help_url'  => __( 'https://greyd-resources.de/en/', 'greyd_hub' ),
			// 	'feat_url'  => __( 'https://greyd-resources.de/en/', 'greyd_hub' ),
			// 	'btn_text'  => __( 'Open Template Library', 'greyd_hub' ),
			// 	'btn_url'   => 'javascript:greyd.templateLibrary.open()',
			// 	'slug'      => 'library',
			// 	'cap'       => get_post_type_object( 'greyd_popup' ) ? get_post_type_object( 'greyd_popup' )->cap->edit_posts : 'edit_posts',
			// 	'priority'  => 11,
			// ),
			'advanced-search'     => array(
				'title'     => __( 'Advanced Search', 'greyd_hub' ),
				'descr'     => __( 'Benefit from advanced search functions to create a unique user experience.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/advanced-search.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/advanced-search/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/advanced-search/', 'greyd_hub' ),
				'btn_text'  => __( 'Configure Advanced Search', 'greyd_hub' ),
				'btn_url'   => admin_url( 'admin.php?page=advanced_search' ),
				'slug'      => 'search',
				'cap'       => 'manage_options',
				'priority'  => 19,
			),
			'global-content'      => array(
				'title'     => __( 'Global Content', 'greyd_hub' ),
				'descr'     => __( 'Synchronize content on any number of websites - even across installations.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/global-content.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-global-content/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/global-content/', 'greyd_hub' ),
				'btn_text'  => __( 'Manage Global Content', 'greyd_hub' ),
				'btn_url'   => admin_url( 'admin.php?page=global_contents' ),
				'slug'      => 'global_content',
				'cap'       => 'manage_options',
				'priority'  => 10,
			),
			'site-connector'      => array(
				'title'     => __( 'Site Connector', 'greyd_hub' ),
				'descr'     => __( 'Connect any number of websites, whether its individual websites or entire multisite installations.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/site-connector.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/site-connector/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/site-connector/', 'greyd_hub' ),
				'btn_text'  => __( 'Go to Site Connector', 'greyd_hub' ),
				'btn_url'   => is_multisite() ? network_admin_url( 'admin.php?page=greyd_hub&tab=connections' ) : admin_url( 'admin.php?page=greyd_hub&tab=connections' ),
				'cap'       => 'manage_options',
				'priority'  => 8,
			),
			'user-management'     => array(
				'title'     => __( 'User Management', 'greyd_hub' ),
				'descr'     => __( 'Custom user roles and capabilities as well as time-saving admin functions.', 'greyd_hub' ),
				'image'     => plugin_dir_url( $this->config->plugin_file ) . 'inc/dashboard/assets/img/features/user-management.jpg',
				'image_alt' => '',
				'help_url'  => __( 'https://docs.greyd.io/docs/greyd-plugin/user-management/', 'greyd_hub' ),
				'feat_url'  => __( 'https://greyd.io/user-management/', 'greyd_hub' ),
				'btn_text'  => __( 'Manage users', 'greyd_hub' ),
				'btn_url'   => admin_url( 'admin.php?page=greyd_user' ),
				'slug'      => 'user',
				'cap'       => 'manage_options',
				'priority'  => 80,
			),
		);

		/**
		 * Modify or add dashboard panels.
		 *
		 * @filter 'greyd_dashboard_all_panels'
		 *
		 * @param array $panels   All panels.
		 */
		return apply_filters( 'greyd_dashboard_all_panels', $panels );
	}

	/**
	 * Render Dashboard Page
	 */
	public function render_dashboard() {

		if ( Helper::is_greyd_classic() ) {
			return \Greyd\Deprecated_Dashboard::render_dashboard();
		}

		// get panels master array
		$panels = $this->dashboard_panels();

		// active panels array
		$active_panels = array();

		// inactive panels array
		$inactive_panels = array();

		/**
		 * Show dashboard panels in 'active' section.
		 *
		 * @filter 'greyd_dashboard_active_panels'
		 *
		 * @param array $panels   All panels.
		 * @param array $config   Plugin Config.
		 */
		$check_panels = apply_filters( 'greyd_dashboard_active_panels', array(), $this->config );

		// Iterate over panel master array
		foreach ( $panels as $key => $value ) {
			// check if panel is active
			if ( array_key_exists( $key, $check_panels ) ) {
				// add to active panels
				$active_panels[ $key ] = $panels[ $key ];
			} else {
				// otherwise, add to inactive panels
				$inactive_panels[ $key ] = $panels[ $key ];
			}
		}

		// duplicate for both active and inactive panels
		// sort panels by priority
		uasort(
			$active_panels,
			function( $a, $b ) {
				$prio_a = isset( $a['priority'] ) ? intval( $a['priority'] ) : 10;
				$prio_b = isset( $b['priority'] ) ? intval( $b['priority'] ) : 10;
				return $prio_a - $prio_b;
			}
		);

		uasort(
			$inactive_panels,
			function( $a, $b ) {
				$prio_a = isset( $a['priority'] ) ? intval( $a['priority'] ) : 10;
				$prio_b = isset( $b['priority'] ) ? intval( $b['priority'] ) : 10;
				return $prio_a - $prio_b;
			}
		);

		?>
		<div class="wrap" style="<?php echo Admin::get_admin_bar_color(); ?>">
			<div class="greyd_dashboard">
				<section class="greyd_dashboard--main">
					<div class="greyd_dashboard--main--active-features">

						<?php // There needs to be a hidden h2 for the Admin Notices to be shown in the correct position, as WP directly inserts it after the first h1 or h2 in the .wrap class. ?>
						<h2 class="visually-hidden" aria-hidden="true"></h2>

						<div class="greyd_dashboard--main--active-features--top">
							<div class="greyd_dashboard--main--active-features--top-left">
								<h2><?php _e( "Active features", 'greyd_hub' ); ?></h2>
								<p><?php _e( 'These are the features of the Greyd Plugin. Use them now or learn more about them in our documentation.', 'greyd_hub' ); ?></p>
							</div>
							<div class="greyd_dashboard--main--active-features--top-right">
								<a class="greyd_admin_link--outline" href="<?php _e( "https://docs.greyd.io/", 'greyd_hub' ); ?>" target="_blank"><span class="text"><?php _e( 'Documentation', 'greyd_hub' ); ?></span></a>
							</div>
						</div>

						<div class="greyd_dashboard--feature-grid greyd_dashboard--active-panels">
							<?php
							foreach ( $active_panels as $panel ) {

								if ( isset( $panel['cap'] ) && ! current_user_can( $panel['cap'] ) ) {
									continue;
								}

								echo sprintf(
									'<div class="greyd_dashboard--feature">
										<img class="greyd_dashboard--feature--image" src="%s" alt="%s">
										<div class="greyd_dashboard--feature--content">
											<h3 class="greyd_dashboard--feature--title">%s</h3>
											<div class="greyd_dashboard--feature--desc">
												<p class="greyd_dashboard--feature--desc-text">%s</p>
												<p class="greyd_dashboard--feature--helplink"><a href="%s" target="_blank">%s</a></p>
											</div>
											<div class="greyd_dashboard--feature--button">
												<a href="%s" target="%s" class="greyd_admin_link">%s</a>
											</div>
										</div>
									</div>',
									$panel['image'],
									isset( $panel['image_alt'] ) ? $panel['image_alt'] : '',
									$panel['title'],
									isset( $panel['descr'] ) ? $panel['descr'] : '',
									isset( $panel['help_url'] ) ? $panel['help_url'] : '',
									isset( $panel['help_url'] ) ? __( 'View tutorial video', 'greyd_hub' ) . ' →' : '',
									isset( $panel['btn_url'] ) ? $panel['btn_url'] : '',
									isset( $panel['btn_target'] ) ? $panel['btn_target'] : '_self',
									isset( $panel['btn_text'] ) ? $panel['btn_text'] : ''
								);
							}
							?>
						</div>
					</div>

					<div class="greyd_dashboard--main--more-features">
						<div class="greyd_dashboard--main--active-features--top">
							<div class="greyd_dashboard--main--active-features--top-left">
								<h2><?php _e( 'Activate more features', 'greyd_hub' ); ?></h2>
								<p><?php _e( 'Benefit from many more features of Greyd.Suite. You can also find an overview of all features in the settings.', 'greyd_hub' ); ?></p>
							</div>
							<div class="greyd_dashboard--main--active-features--top-right">
								<a class="greyd_admin_link--outline" href="<?php echo admin_url( 'admin.php?page=greyd_features' ); ?>"><span class="text"><?php _e( 'All features', 'greyd_hub' ); ?></span></a>
							</div>
						</div>

						<div class="greyd_dashboard--feature-grid greyd_dashboard--discover-panels">
							<?php

							$activate_features = array( 'dynamic-templates', 'dynamic-post-types', 'greyd-popups', 'template-library', 'advanced-search', 'user-management' );
							$install_plugin    = array( 'greyd-forms', 'global-content' );
							$install_theme     = array( 'template-editor', 'global-styles', 'greyd-global-styles' );
							$action            = '';

							foreach ( $inactive_panels as $key => $panel ) {

								if ( isset( $panel['cap'] ) && ! current_user_can( $panel['cap'] ) ) {
									continue;
								}

								if ( in_array( $key, $activate_features ) ) {
									$panel['btn_text'] = __( 'Activate feature', 'greyd_hub' );

									if ( ! empty( $panel['requires'] ) && array_key_exists( $panel['requires'], $inactive_panels ) ) {
										$panel['btn_text']     = __( 'Activate required feature first', 'greyd_hub' );
										$panel['btn_disabled'] = 'greyd_admin_button--disabled';
									} else {
										$action = ' onclick="greyd.dashboard.activateFeature(this)"';
									}
								}

								if ( in_array( $key, $install_plugin ) ) {
									$panel['btn_text'] = __( 'Install plugin', 'greyd_hub' );
									$action            = ' onclick="greyd.dashboard.activatePlugin(this)"';
								}

								if ( in_array( $key, $install_theme ) ) {
									$panel['btn_text'] = __( 'Install theme', 'greyd_hub' );
									$action            = ' onclick="greyd.dashboard.activateTheme(this)"';
								}

								echo sprintf(
									'<div class="greyd_dashboard--feature">
										<img class="greyd_dashboard--feature--image" src="%s" alt="%s">
										<div class="greyd_dashboard--feature--content">
											<h3 class="greyd_dashboard--feature--title">%s</h3>
											<div class="greyd_dashboard--feature--desc">
												<p class="greyd_dashboard--feature--desc-text">%s</p>
												<p class="greyd_dashboard--feature--helplink"><a href="%s" target="_blank">%s</a><br>%s</p>
											</div>
											<div class="greyd_dashboard--feature--button">
												<button class="greyd_admin_button greyd_admin_button--dark%s%s" data-title="%s" data-slug="%s"%s>%s</button>
											</div>
										</div>
									</div>',
									$panel['image'],
									isset( $panel['image_alt'] ) ? $panel['image_alt'] : '',
									$panel['title'],
									isset( $panel['descr'] ) ? $panel['descr'] : '',
									isset( $panel['feat_url'] ) ? $panel['feat_url'] : '',
									isset( $panel['feat_url'] ) ? __( 'Learn more about this feature', 'greyd_hub' ) . ' →' : '',
									! empty( $panel['requires'] ) && array_key_exists( $panel['requires'], $inactive_panels ) ? '<span class="greyd_dashboard--feature--required"><span class="dashicons dashicons-info"></span>' . sprintf( __( 'Requires: %s', 'greyd_hub' ), $panel['requires'] ) . '</span>' : '',
									isset( $panel['btn_class'] ) ? ' ' . $panel['btn_class'] : '',
									isset( $panel['btn_disabled'] ) ? ' ' . $panel['btn_disabled'] : '',
									$panel['title'],
									isset( $panel['slug'] ) ? $panel['slug'] : '',
									! empty( $action ) ? $action : '',
									isset( $panel['btn_text'] ) ? $panel['btn_text'] : ''
								);
							}
							?>
						</div>
					</div>

					<div class="greyd_dashboard--main--settings">
						<h2><?php _e( 'More helpful quick links', 'greyd_hub' ); ?></h2>

						<div class="greyd_dashboard--feature-grid greyd_dashboard--settings-panels">
							<div class="greyd_dashboard--feature">
								<div class="greyd_dashboard--feature--content">
									<h3 class="greyd_dashboard--feature--title"><?php _e( 'General settings', 'greyd_hub' ); ?></h3>
									<div class="greyd_dashboard--feature--desc">
										<p class="greyd_dashboard--feature--desc-text"><?php _e( 'Define website title, homepage and more.', 'greyd_hub' ); ?></p>
										<p class="greyd_dashboard--feature--helplink">
											<a href="<?php echo admin_url( 'admin.php?page=greyd_settings' ); ?>"><?php _e( 'Open settings', 'greyd_hub' ); ?> →</a>
										</p>
									</div>
								</div>
							</div>

							<div class="greyd_dashboard--feature">
								<div class="greyd_dashboard--feature--content">
									<h3 class="greyd_dashboard--feature--title"><?php _e( 'Features', 'greyd_hub' ); ?></h3>
									<div class="greyd_dashboard--feature--desc">
										<p class="greyd_dashboard--feature--desc-text"><?php _e( 'Overview of all available features. Activate the features you need.', 'greyd_hub' ); ?></p>
										<p class="greyd_dashboard--feature--helplink">
											<a href="<?php echo admin_url( 'admin.php?page=greyd_features' ); ?>"><?php _e( 'Setup features', 'greyd_hub' ); ?> →</a>
										</p>
									</div>
								</div>
							</div>

							<div class="greyd_dashboard--feature">
								<div class="greyd_dashboard--feature--content">
									<h3 class="greyd_dashboard--feature--title"><?php _e( 'License', 'greyd_hub' ); ?></h3>
									<div class="greyd_dashboard--feature--desc">
										<p class="greyd_dashboard--feature--desc-text"><?php _e( 'Activate and manage your Greyd.Suite license.', 'greyd_hub' ); ?></p>
										<p class="greyd_dashboard--feature--helplink">
											<a href="<?php echo admin_url( 'admin.php?page=greyd_settings_license' ); ?>"><?php _e( 'Configure license', 'greyd_hub' ); ?> →</a>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<aside class="greyd_dashboard--sidebar">
					<div class="greyd_dashboard--sidebar--status">
						<h2><?php _e( "State", 'greyd_hub' ); ?></h2>
						<ul class="greyd_dashboard--sidebar--status-list">
							<li><?php echo $this->sidebar_license_check(); ?></li>
							<li><?php echo $this->sidebar_theme_check(); ?></li>
							<li><?php echo $this->sidebar_plugin_check(); ?></li>
							<li><?php echo $this->sidebar_gutenberg_check(); ?></li>
						</ul>
					</div>
					<div class="greyd_dashboard--sidebar--need-help">
						<h2><?php _e( 'Need help?', 'greyd_hub' ); ?></h2>
						<p><?php _e( 'In case you need help with our plugin, check out the following resources:', 'greyd_hub' ); ?></p>
						<ul>
							<li><a href="<?php _e( "https://docs.greyd.io/", 'greyd_hub' ); ?>" target="_blank"><?php _e( 'Documentation', 'greyd_hub' ); ?> →</a></li>
							<li><a href="<?php _e( "https://greyd.io/faq/", 'greyd_hub' ); ?>" target="_blank"><?php _e( 'FAQs', 'greyd_hub' ); ?> →</a></li>
						</ul>
						<?php if ( current_user_can( 'manage_options' ) ) { ?>
							<div class="greyd_system_status_wrapper">
								<button class="greyd_admin_button open_modal_button"><?php _e( 'System status', 'greyd_hub' ); ?></button>
								<dialog class="" id="greyd_system_status">
									<span class="dashicons dashicons-no-alt close_modal_button"></span>
									<div class="greyd_system_status_content">
										<?php echo $this->get_system_status(); ?>
								</dialog>
							</div>
						<?php } ?>
					</div>
					<div class="greyd_dashboard--sidebar--changelog">
						<h2><?php _e( "Changelog - what's new?", 'greyd_hub' ); ?></h2>
						<div class="greyd-changelog">
							<?php echo self::get_changelog_content( GREYD_UPDATE_URL ); ?>
						</div>
						<p><a href="https://greyd.io/changelog/" target="_blank"><?php _e( 'See full changelog', 'greyd_hub' ); ?> →</a></p>
					</div>
				</aside>
			</div>
		</div>

		<?php
	}

	/*
	====================================================================================
		Sidebar: Status Area
	====================================================================================
	*/

	public function sidebar_license_check() {
		// get license key and set default data
		$key     = get_option( 'gtpl' );
		$message = __( 'License not active', 'greyd_hub' );
		$icon    = 'alert_icon';

		// check if license key is available and has the status of is_valid
		if ( is_array( $key ) && $key['status'] === 'is_valid' ) {
			$message = __( 'License active', 'greyd_hub' );
			$icon    = 'ok_icon';
		}

		return '<img src="' . Admin::$page[ $icon ] . '"> <span class="text">' . $message . '<br><a href="' . admin_url( 'admin.php?page=greyd_settings_license' ) . '">' . __( 'Manage license', 'greyd_hub' ) . '</a></span>';
	}

	public function sidebar_gutenberg_check() {

		$message 	= __( 'Gutenberg not active', 'greyd_hub' );
		$icon    	= 'info_icon';
		$is_active 	= is_plugin_active( 'gutenberg/gutenberg.php' );
		$url        = is_multisite() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );

		$info_text = __( 'The plugin is optional. It offers new experimental features that are not yet available in the WordPress core.', 'greyd_hub' );

		if ( $is_active ) {
			// $gutenberg_version = get_plugin_data( WP_PLUGIN_DIR . '/gutenberg/gutenberg.php', false, false )['Version'];
			// $supported_version = "17.6.6";

			// if ( version_compare( $gutenberg_version, $supported_version, '<=' ) ) {
			// 	// Gutenberg version is supported
			// 	$message = __( 'Gutenberg active and its current version is tested and supported', 'greyd_hub' );
			// 	$icon    = 'ok_icon';
			// } else {
			// 	$message = __( 'Gutenberg activ but the version is not tested yet', 'greyd_hub' );
			// 	$icon    = 'alert_icon';
			// }
			$message = __( 'Gutenberg active', 'greyd_hub' );
			$icon    = 'ok_icon';
		}

		return '<img src="' . Admin::$page[ $icon ] . '"> <span class="text">' . $message . '<span class="text small">' . $info_text . '</span><a href="' . $url . '">' . __( 'Manage plugins', 'greyd_hub' ) . '</a></span>';
	}

	public function sidebar_theme_check() {
		// get data about the current theme and set default data
		$message        = __( 'Greyd Theme not active', 'greyd_hub' );
		$message_action = __( 'Install and activate theme', 'greyd_hub' );
		$icon           = 'info_icon';
		$url            = is_multisite() ? network_admin_url( 'themes.php' ) : admin_url( 'themes.php' );

		if ( defined( 'GREYD_THEME_CONFIG' ) ) {
			// If the Greyd Theme is active, check if it's up to date
			// site transient 'update_themes' has an array 'response' that holds all theme updates, check if greyd-theme is in there (both for multi and single sites)
			$theme_updates    = get_site_transient( 'update_themes', array() );
			$update_available = array_key_exists( 'greyd-theme', $theme_updates->response );
			$message          = $update_available ? __( 'Greyd Theme update available', 'greyd_hub' ) : __( 'Greyd Theme up to date', 'greyd_hub' );
			$message_action   = $update_available ? __( 'Update theme', 'greyd_hub' ) : __( 'Manage theme', 'greyd_hub' );
			$icon             = $update_available ? 'alert_icon' : 'ok_icon';
		}

		return '<img src="' . Admin::$page[ $icon ] . '"> <span class="text">' . $message . '<br><a href="' . $url . '">' . $message_action . '</a></span>';
	}

	public function sidebar_plugin_check() {
		// get data about the Greyd plugins and set default data
		$greyd_plugins  = array( 'greyd-plugin/init.php', 'greyd_tp_forms/init.php', 'greyd_globalcontent/init.php' );
		$message        = __( 'Greyd plugins not up to date', 'greyd_hub' );
		$message_action = __( 'Update plugins', 'greyd_hub' );
		$icon           = 'alert_icon';
		$url            = is_multisite() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );

		// site transient 'update_plugins' has an array 'response' that holds all plugin updates, check if any of our plugins are in there (both for multi and single sites)
		$plugin_updates   = get_site_transient( 'update_plugins', array() );
		$update_available = array();
		if ( isset( $plugin_updates->response ) ) {
			foreach ( $greyd_plugins as $plugin ) {
				if ( array_key_exists( $plugin, $plugin_updates->response ) ) {
					$update_available[] = $plugin;
				}
			}
		}

		// if there are no updates available, update the display data
		if ( ! $update_available ) {
			$message        = __( 'Greyd plugins up to date', 'greyd_hub' );
			$message_action = __( 'Manage plugins', 'greyd_hub' );
			$icon           = 'ok_icon';
		}

		return '<img src="' . Admin::$page[ $icon ] . '"> <span class="text">' . $message . '<br><a href="' . $url . '">' . $message_action . '</a></span>';
	}

	/*
	====================================================================================
		System Status Content
	====================================================================================
	*/
	public function get_system_status() {
		$output = '';

		// get theme data
		$theme_data   = wp_get_theme();
		$parent_theme = '';
		if ( is_child_theme() ) {
			$parent_theme = wp_get_theme( $theme_data->Template );
		}

		// prepare system info array
		$systeminfo = array(
			'Home URL'                => home_url(),
			'Site URL'                => site_url(),
			'WP Version'              => get_bloginfo( 'version' ),
			'WP Debug Mode'           => defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Yes' : 'No',
			'WP Debug Log'            => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'Yes' : 'No',
			'WP Debug Display'        => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'Yes' : 'No',
			'WP Script Debug Mode'    => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'Yes' : 'No',
			'Site Language'           => get_locale(),
			'Character Set'           => get_bloginfo( 'charset' ),
			'WP Multisite'            => is_multisite() ? 'Yes' : 'No',
			'WP Timezone'             => get_option( 'timezone_string' ) . ', GMT Offset: ' . get_option( 'gmt_offset' ),
			'Default PHP Timezone'    => date_default_timezone_get(),
			'WP Date Format'          => get_option( 'date_format' ),
			'WP Time Format'          => get_option( 'time_format' ),
			'WP Permalink Structure'  => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default',
			'WP Memory Limit'         => WP_MEMORY_LIMIT,
			'WP Max Memory Limit'     => WP_MAX_MEMORY_LIMIT,
			'WP Cron'                 => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'Disabled' : 'Enabled',
			'break'                   => '-----------------------------------------&#013;',
			'Server Info'             => $_SERVER['SERVER_SOFTWARE'],
			'PHP Version'             => phpversion(),
			'PHP Post Max Size'       => ini_get( 'post_max_size' ),
			'PHP Max Execution Time'  => ini_get( 'max_execution_time' ),
			'PHP Max Input Vars'      => ini_get( 'max_input_vars' ),
			'PHP Memory Limit'        => ini_get( 'memory_limit' ),
			'PHP Upload Max Filesize' => ini_get( 'upload_max_filesize' ),
			'PHP Display Errors'      => ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A',
			'PHP Log Errors'          => ini_get( 'log_errors' ) ? 'On (' . ini_get( 'error_log' ) . ')' : 'N/A',
			'cURL Version'            => function_exists( 'curl_version' ) ? curl_version()['version'] : 'No',
			'break-2'                 => '-----------------------------------------&#013;',
			'Greyd License'           => get_option( 'greyd_suite_license_key' ),
			'break-3'                 => '-----------------------------------------&#013;',
			'Theme Name'              => $theme_data->Name,
			'Theme Version'           => $theme_data->Version,
			'Theme Author'            => $theme_data->Author,
			'Theme Author URL'        => $theme_data->AuthorURI,
			'Is Child Theme'          => is_child_theme() ? 'Yes' : 'No',
			'Parent Theme Name'       => $parent_theme ? $parent_theme->Name : 'N/A',
			'Parent Theme Version'    => $parent_theme ? $parent_theme->Version : 'N/A',
			'Parent Theme URL'        => $parent_theme ? $parent_theme->ThemeURI : 'N/A',
			'Parent Theme Author'     => $parent_theme ? $parent_theme->Author : 'N/A',
			'Parent Theme Author URL' => $parent_theme ? $parent_theme->AuthorURI : 'N/A',
		);

		// iterate over system info array and build output
		foreach ( $systeminfo as $key => $value ) {
			if ( substr( $key, 0, 5 ) == 'break' ) {
				$output .= $value;
			} else {
				$output .= $key . ': ' . $value . '&#013;';
			}
		}

		// get must use plugins and output them
		$output    .= '-----------------------------------------&#013;';
		$mu_plugins = get_mu_plugins();
		$output    .= 'Must Use Plugins: ' . ( count( $mu_plugins ) ? 'Yes' : 'No' ) . '&#013;';
		$output    .= '----------&#013;';
		foreach ( $mu_plugins as $key => $value ) {
			$output .=
				'Name: ' . $value['Name'] . '&#013;' .
				'Version: ' . $value['Version'] . '&#013;' .
				'Author: ' . $value['Author'] . '&#013;' .
				'URL: ' . $value['PluginURI'] . '&#013;' .
				'----------&#013;';
		}

		// get plugins, active plugins
		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		// network-wide active plugins
		$output .= '-----------------------------------------&#013;';
		$output .= 'Network Plugins: ' . ( count( $plugins ) ? 'Yes' : 'No' ) . '&#013;';
		$output .= '----------&#013;';
		foreach ( $plugins as $key => $value ) {
			if ( in_array( $key, $active_plugins ) ) {
				continue;
			}
			if ( ! is_plugin_active_for_network( $key ) ) {
				continue;
			}
			$output .=
				'Name: ' . $value['Name'] . '&#013;' .
				'Version: ' . $value['Version'] . '&#013;' .
				'Author: ' . $value['Author'] . '&#013;' .
				'URL: ' . $value['PluginURI'] . '&#013;' .
				'----------&#013;';
		}

		// active plugins on site
		$output .= '-----------------------------------------&#013;';
		$output .= 'Active Plugins: ' . ( count( $plugins ) ? 'Yes' : 'No' ) . '&#013;';
		$output .= '----------&#013;';
		foreach ( $plugins as $key => $value ) {
			if ( ! in_array( $key, $active_plugins ) ) {
				continue;
			}
			$output .=
				'Name: ' . $value['Name'] . '&#013;' .
				'Version: ' . $value['Version'] . '&#013;' .
				'Author: ' . $value['Author'] . '&#013;' .
				'URL: ' . $value['PluginURI'] . '&#013;' .
				'----------&#013;';
		}

		// inactive plugins
		$output .= '-----------------------------------------&#013;';
		$output .= 'Inactive Plugins: ' . ( count( $plugins ) ? 'Yes' : 'No' ) . '&#013;';
		$output .= '----------&#013;';
		foreach ( $plugins as $key => $value ) {
			if ( in_array( $key, $active_plugins ) ) {
				continue;
			}
			if ( is_plugin_active_for_network( $key ) ) {
				continue;
			}
			$output .=
				'Name: ' . $value['Name'] . '&#013;' .
				'Version: ' . $value['Version'] . '&#013;' .
				'Author: ' . $value['Author'] . '&#013;' .
				'URL: ' . $value['PluginURI'] . '&#013;' .
				'----------&#013;';
		}

		// build modal content
		$headline = '<h3>' . __( 'System status', 'greyd_hub' ) . '</h3>';
		$desc     = '<p>' . __( 'If you want to help us with a support ticket, please provide this information together with your issue.', 'greyd_hub' ) . '</p>';
		$button   = '<button class="greyd_admin_button system_status_button"><span class="dashicons dashicons-clipboard"></span><span class="text default">' . __( 'Copy to clipboard', 'greyd_hub' ) . '</span><span class="text copied hidden">' . __( 'Copied!', 'greyd_hub' ) . '</span></button>';
		$textarea = '<textarea readonly="readonly" class="greyd_system_status_text" onclick="this.focus();this.select()">' . $output . '</textarea>';

		return $headline . $desc . $button . $textarea;
	}




	/*
	====================================================================================
		Helper
	====================================================================================
	*/

	/**
	 * Get current changelog content.
	 *
	 * @return string
	 */
	public static function get_changelog_content( $file, $name = 'greyd_plugin_changelog' ) {

		$changelog_content = get_transient( $name );
		if ( ! $changelog_content ) {
			$update_metadata = @file_get_contents( $file );
			if ( $update_metadata ) {
				$decoded_metadata = @json_decode( $update_metadata, true );
				if (
					$decoded_metadata
					&& is_array( $decoded_metadata )
					&& isset( $decoded_metadata['sections'] )
					&& isset( $decoded_metadata['sections']['changelog'] )
				) {
					$changelog_data = $decoded_metadata['sections']['changelog'];

					$changelog_array   = explode( '<h2>', $changelog_data );
					$changelog_content = implode( '<h2>', array_slice( $changelog_array, 0, 4 ) );

					// replace all multiple whitespace chars and multiple '\' chars
					$changelog_content = preg_replace(
						array( '/\s+/', '/[\\\]+/' ),
						array( ' ', '' ),
						$changelog_content
					);

					set_transient(
						$name,
						$changelog_content,
						60 * 60 * 12 // 12 hours
					);
				}
			}
		}

		return empty( $changelog_content ) ? __( 'Changelog could not be loaded.', 'greyd_hub' ) : $changelog_content;
	}

	/*
	====================================================================================
		Plugin Installation
	====================================================================================
	*/

	public function install_greyd_plugins() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
			die( 'Invalid nonce.' );
		}
		// keeping this comment in case we need to make the users update to another plugin
		// $old_plugin_slug = 'reset-wp/reset-wp.php';

		// add Greyd plugin data here
		$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );
		if ( $plugin === 'greyd_forms' ) {
			$plugin_slug = 'greyd_tp_forms/init.php';
			$plugin_zip  = 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_forms/greyd_tp_forms.zip';
		} elseif ( $plugin === 'global_content' ) {
			$plugin_slug = 'greyd_globalcontent/init.php';
			$plugin_zip  = 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_globalcontent/greyd_globalcontent.zip';
		} else {
			echo 'No matching plugin found';
			return false;
		}

		echo 'Starting plugin install... ';

		echo 'Check if new plugin is already installed - ';

		if ( $this->is_plugin_installed( $plugin_slug ) ) {
			echo 'it\'s installed! Making sure it\'s the latest version...';
			$this->upgrade_plugin( $plugin_slug );
			$installed = true;
		} else {
			echo 'it\'s not installed. Installing... ';
			$installed = $this->install_plugin( $plugin_zip );
		}

		if ( ! is_wp_error( $installed ) && $installed ) {
			echo 'Activating new plugin... ';
			$activate = activate_plugin( $plugin_slug );

			if ( is_null( $activate ) ) {
				// echo 'Deactivating old plugin... ';
				// deactivate_plugins( array( $old_plugin_slug ) );

				echo 'Done! Everything went smooth.';
			}
		} else {
			echo 'Could not install the new plugin.';
		}
	}

	function is_plugin_installed( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( ! empty( $all_plugins[ $slug ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	function install_plugin( $plugin_zip ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader  = new \Plugin_Upgrader();
		$installed = $upgrader->install( $plugin_zip );

		return $installed;
	}

	function upgrade_plugin( $plugin_slug ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader = new \Plugin_Upgrader();
		$upgraded = $upgrader->upgrade( $plugin_slug );

		return $upgraded;
	}

	/*
	====================================================================================
		Theme Installation
	====================================================================================
	*/

	public function install_greyd_theme() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
			die( 'Invalid nonce.' );
		}

		// add Greyd theme data
		// TODO: Update this once the theme is in the repository
		$theme_slug = 'greyd-theme';
		$theme_zip  = 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/themes/greyd-theme/greyd-theme.zip';

		echo 'Starting theme install... ';

		echo 'Check if new theme is already installed - ';

		if ( $this->is_theme_installed( $theme_slug ) ) {
			echo 'it\'s installed! Making sure it\'s the latest version...';
			$this->upgrade_theme( $theme_slug );
			$installed = true;
		} else {
			echo 'it\'s not installed. Installing... ';
			$installed = $this->install_theme( $theme_zip );
		}

		if ( ! is_wp_error( $installed ) && $installed ) {
			echo 'Activating new theme... ';
			$activate = switch_theme( $theme_slug );

			if ( is_null( $activate ) ) {
				echo 'Done! Everything went smooth.';
			}
		} else {
			echo 'Could not install the new theme.';
		}
	}

	function is_theme_installed( $slug ) {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		$all_themes = wp_get_themes();

		if ( ! empty( $all_themes[ $slug ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	function install_theme( $theme_zip ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader  = new \Theme_Upgrader();
		$installed = $upgrader->install( $theme_zip );

		return $installed;
	}

	function upgrade_theme( $theme_slug ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();

		$upgrader = new \Theme_Upgrader();
		$upgraded = $upgrader->upgrade( $theme_slug );

		return $upgraded;
	}

	/*
	====================================================================================
		Feature Activation
	====================================================================================
	*/

	public function activate_greyd_features() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
			die( 'Invalid nonce.' );
		}

		// get feature slug
		$feature = sanitize_text_field( wp_unslash( $_POST['feature'] ) );

		// master list of features that can be activated on the dashboard
		$available_features = array(
			'dynamic'   => 'dynamic/init.php',
			'posttypes' => 'posttypes/init.php',
			'popups'    => 'popups/init.php',
			'library'   => 'library/init.php',
			'search'    => 'search/init.php',
			'user'      => 'user/init.php',
		);

		if ( array_key_exists( $feature, $available_features ) ) {
			// if the provided feature is in the master list, activate it
			// get the current option first
			$features = get_option( 'greyd_features', array() );
			// add the new feature to the array
			$features[ $feature ] = $available_features[ $feature ];
			// update the option
			update_option( 'greyd_features', $features );
			// return success message
			echo 'Everything went smooth.';
		} else {
			echo 'Could not activate the feature';
		}
	}

	/**
	 * Add overlay contents
	 *
	 * @filter 'greyd_overlay_contents'
	 *
	 * @param array $contents
	 *
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {

		$overlay_contents = array(
			'activate-plugin'  => array(
				'confirm' => array(
					'title'  => __( 'Please confirm plugin installation', 'greyd_hub' ),
					'descr'  => sprintf(
						__( 'By clicking on "Install Plugin" below, the "%s" plugin will be installed and activated on your site.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
					'button' => __( 'Install plugin', 'greyd_hub' ),
				),
				'loading' => array(
					'descr' => sprintf(
						__( 'The %s plugin is being installed and activated...', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'success' => array(
					'title' => __( 'Plugin installation successful', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s plugin was successfully installed and activated! Reloading the page now.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'fail'    => array(
					'title' => __( 'Plugin installation failed', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s plugin could not be installed or activated this time. Please try again later.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
			),
			'activate-theme'   => array(
				'confirm' => array(
					'title'  => __( 'Please confirm theme installation', 'greyd_hub' ),
					'descr'  => sprintf(
						__( 'By clicking on "Install Theme" below, the "%s" theme will be installed and activated on your site.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
					'button' => __( 'Install theme', 'greyd_hub' ),
				),
				'loading' => array(
					'descr' => sprintf(
						__( 'The %s theme is being installed and activated...', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'success' => array(
					'title' => __( 'Theme installation successful', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s theme was successfully installed and activated! Reloading the page now.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'fail'    => array(
					'title' => __( 'Theme installation failed', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s theme could not be installed or activated this time. Please try again later.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
			),
			'activate-feature' => array(
				'loading' => array(
					'descr' => sprintf(
						__( 'The %s feature is being activated...', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'success' => array(
					'title' => __( 'Feature activation successful', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s feature was successfully activated! Reloading the page now.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
				'fail'    => array(
					'title' => __( 'Feature activation failed', 'greyd_hub' ),
					'descr' => sprintf(
						__( 'The %s feature could not be activated this time. Please try again later.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
				),
			),
		);

		return array_merge(
			$contents,
			$overlay_contents
		);
	}
}
