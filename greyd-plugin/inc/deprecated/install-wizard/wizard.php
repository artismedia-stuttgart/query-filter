<?php
/**
 * Plugin File for install wizard
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Install_Wizard( $config );
class Install_Wizard {

	/**
	 * Holds global config args.
	 */
	private $config;

	/**
	 * Holds the Hub url
	 */
	private $hub_url;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	/**
	 * Constructor
	 */
	public function __construct( $config ) {

		// set config
		$this->config  = (object) $config;

		if ( ! $this->config->is_greyd_classic ) return;

		$this->hub_url = is_multisite() ? network_admin_url( 'admin.php?page=greyd_hub' ) : admin_url( 'admin.php?page=greyd_hub' );

		// static vars
		add_action(
			'init',
			function() {
				// define page details
				self::$page = array(
					'slug'  => 'greyd_wizard',
					'title' => __( 'Greyd.Wizard', 'greyd_hub' ),
					'descr' => __( "With just a few clicks, make important basic settings for your website and set the basic look of the page.", 'greyd_hub' ),
					'url'   => admin_url( 'admin.php?page=greyd_dashboard&wizzard=add' ),
					'cap'   => 'switch_themes',
				);
				// debug(self::$page);
			},
			0
		);

		if ( is_admin() ) {
			// add menu and pages
			add_filter( 'greyd_dashboard_panels', array( $this, 'add_greyd_dashboard_panel' ) );
			add_filter( 'greyd_misc_pages', array( $this, 'add_greyd_misc_page' ) );
			add_action( 'greyd_settings_notice', array( $this, 'add_settings_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ), 40 );

			// render wizard
			add_action( 'admin_footer', array( $this, 'render_wizard' ), 10, 2 );
		}
	}


	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add dashboard panel
	 *
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_dashboard_panel( $panels ) {
		// debug($panels);

		array_push(
			$panels,
			array(
				'icon'     => 'wizard',
				'title'    => self::$page['title'],
				'descr'    => self::$page['descr'],
				'btn'      => array(
					array(
						'text'  => _x( "start wizard", 'small', 'greyd_hub' ),
						'class' => 'button open_wizzard',
					),
					array(
						'text'  => __( "Reset options", 'greyd_hub' ),
						'class' => 'button button-ghost open_wizzard_reset',
					),
				),
				'cap'      => self::$page['cap'],
				'priority' => 9,
			)
		);

		return $panels;
	}

	/**
	 * Add misc page
	 *
	 * @see filter 'greyd_misc_pages'
	 */
	public function add_greyd_misc_page( $pages ) {
		// debug($pages);

		// add Wizard to 'Design' menu
		$pages['wizard'] = array(
			'parent'   => 'themes.php',
			'title'    => self::$page['title'],
			'cap'      => self::$page['cap'],
			'slug'     => self::$page['url'],
			'callback' => '',
			'position' => 10,
		);

		return $pages;
	}

	/**
	 * add scripts
	 */
	public function load_backend_scripts() {

		// css
		wp_register_style(
			$this->config->plugin_name . '_wizard_css',
			plugin_dir_url( __FILE__ ) . '/assets/css/admin-style.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style( $this->config->plugin_name . '_wizard_css' );

		// js
		wp_register_script(
			$this->config->plugin_name . '_wizard_js',
			plugin_dir_url( __FILE__ ) . '/assets/js/admin-script.js',
			array( 'jquery', "greyd-admin-script" ),
			GREYD_VERSION
		);
		wp_enqueue_script( $this->config->plugin_name . '_wizard_js' );

		if ( isset( $_GET['wizard'] ) || isset( $_GET['wizzard'] ) ) {
			$script = '';
			$wizzard = $_GET['wizard'] ?? $_GET['wizzard'];
			if ( $wizzard == 'install' ) {
				$script .= 'jQuery(function() { wizzard.install(); });';
			} elseif ( $wizzard == 'add' ) {
				$script .= 'jQuery(function() { wizzard.open(); });';
			} elseif ( $wizzard == 'settings' ) {
				$option  = isset( $_GET['option'] ) ? $_GET['option'] : '';
				$script .= 'jQuery(function() { wizzard.settings("' . $option . '"); });';
			}
			if ( ! empty( $script ) ) {
				wp_add_inline_script( $this->config->plugin_name . '_wizard_js', $script, 'after' );
			}
		}
	}

	/**
	 * Show admin notice on settings page.
	 *
	 * @see action 'greyd_settings_notice'
	 */
	public function add_settings_notice() {

		// show notice if just activated
		if ( isset( $_GET['wizzard'] ) && $_GET['wizzard'] == 'install' ) {
			echo "<div class='updated notice is-dismissible'>
                    <p>" . __( "Greyd.Suite is now activated.", 'greyd_hub' ) . '</p>
                </div>';
			echo "<div class='updated notice is-dismissible'>
                    <p>" . sprintf( __( "%s Plugin activated.", 'greyd_hub' ), $this->config->plugin_name_full ) . '</p>
                </div>';
		}

	}

	/*
	=======================================================================
		Wizard
	=======================================================================
	*/

	public function render_wizard() {
		// only render on themes page or settings pages
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( strpos( $page, 'greyd_settings' ) !== 0 &&
			strpos( $page, 'greyd_dashboard' ) !== 0 &&
			get_current_screen()->base !== 'themes'
		) {
			return false;
		}
		// uncomment the following line to debug on the themes page
		// if (get_current_screen()->base === 'themes') echo '<script>var theme_activated = true;</script>';

		$asset_url = plugins_url( $this->config->plugin_name . '/inc/deprecated/install-wizard/assets' );
		$img_url   = $asset_url . '/img';

		echo '
        <div class="wizzard" id="install_wizzard">
            <div class="wizzard_box">
                <div class="wizzard_head">
                    <img class="wizzard_icon" src="' . $img_url . '/logo_light.svg">
                    <span class="close_wizzard dashicons dashicons-no-alt"></span>
                </div>
                <div class="wizzard_content">
                    <div data-slide="0-install">
                        <h2>' . __( "Installing Greyd.Suite", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Setting up the set-up...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Creating content...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Completing installation...", 'greyd_hub' ) . '</div>
                        <div class="loading"><div class="loader"></div></div>
                    </div>
                    <div data-slide="0-confirm">
                        <h2>' . __( "Greyd.Suite is already installed.", 'greyd_hub' ) . '</h2>
                        <div class="greyd_info_box blue">
                            <span class="dashicons dashicons-info"></span>
                            <div><p>' . __( "You have already enabled Greyd.Suite on this installation. If you reinstall, some of your content and settings may be overwritten.", 'greyd_hub' ) . '</p></div>
                        </div>
                    </div>
                    <div data-slide="0-success">
                        <h2>' . __( "Greyd.Suite successfully installed.", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "The wizard is now starting.", 'greyd_hub' ) . '</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                    <div data-slide="0-error">
                        <h2>' . __( 'Ooooops!', 'greyd_hub' ) . '</h2>
                        <div class="greyd_info_box red" style="width: 100%; box-sizing: border-box;">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <p>' . __( "There was a problem:", 'greyd_hub' ) . '</p><br>
                                <p class="error_msg"></p>
                            </div>
                        </div>
                    </div>
                    <div data-slide="0-reset">
                        <h2>' . __( "Reset installation", 'greyd_hub' ) . '</h2>
                        <div class="greyd_info_box orange">
                            <span class="dashicons dashicons-warning"></span>
                            <div><p>' . __( "Attention! By resetting the installation, the selected content and settings will be deleted or overwritten permanently.", 'greyd_hub' ) . '<br>' .
								sprintf( __( 'You should definitely <a target="_blank" href="%s">make a backup</a> beforehand.', 'greyd_hub' ), $this->hub_url ) . '</p></div>
                        </div>
                    </div>
                    <div data-slide="0-init">
                        <h2>' . __( "Welcome to Greyd.Suite", 'greyd_hub' ) . '</h2>
                        <div>
                            ' . __( "You have multiple possibilities:", 'greyd_hub' ) . '
                            <ol>
                                <li>' . __( "Open the Template Library and install one of many professionally designed website templates.", 'greyd_hub' ) . '</li>
                                <li>' . __( "Start the Greyd Wizard to determine the basic look of your website.", 'greyd_hub' ) . '</li>
                                <li>' . __( "Go straight to the basic settings to install recommended plugins, for example.", 'greyd_hub' ) . '</li>
                            </ol>
                        </div>
                    </div>

                    <!-- <div data-slide="0">
                        <h2>' . __( 'Wizard', 'greyd_hub' ) . '</h2>
                        <div class="greyd_info_box orange">
                            <span class="dashicons dashicons-warning"></span>
                            <div><p>' . __( "Attention! The wizard overwrites the current design of your page.", 'greyd_hub' ) . '<br>' .
								sprintf( __( 'You should definitely <a target="_blank" href="%s">make a backup</a> beforehand.', 'greyd_hub' ), $this->hub_url ) . '</p></div>
                        </div>
                    </div>
                    -->


                    <div data-slide="0">
                        <h2>' . __( "Welcome", 'greyd_hub' ) . '</h2>
                        <div class="option_txt">
                            <p>' . __( "Start the Greyd Wizard now to define the overall look of your website. In the general settings you can, for example, install recommended plugins. Templates for entire websites or individual areas can be found in our Template Library.", 'greyd_hub' ) . '</p>
                            <p>' . __( "You can find presets for entire websites or individual areas in our Template Library.", 'greyd_hub' ) . '</p>
                        </div>
          
                    </div>
                    <div data-slide="1">
                        <h2>' . __( "1. Choose a font style", 'greyd_hub' ) . '</h2>
                        <div class="wizzard_options">
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/font/slab.svg"><p>' . __( 'slab', 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/font/sans.svg"><p>' . __( 'sans', 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/font/serif.svg"><p>' . __( 'serif', 'greyd_hub' ) . '</p></span>
                        </div>
                        <div class="option_txt">' . __( "You can of course define and change all fonts individually later. Here you only set a rough look for your website.", 'greyd_hub' ) . '</div>
                    </div>
                    <div data-slide="2">
                        <h2>' . __( "2. Choose a color style", 'greyd_hub' ) . '</h2>
                        <div class="wizzard_options">
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/color/red.svg"><p>' . __( "red", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/color/blue.svg"><p>' . __( "blue", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/color/reduced.svg"><p>' . __( "reduced", 'greyd_hub' ) . '</p></span>
                        </div>
                        <div class="option_txt">' . __( "Later, you can individually define and change the colors for each item on your website. Set an approximate color scheme here.", 'greyd_hub' ) . '</div>
                    </div>
                    <div data-slide="3">
                        <h2>' . __( "3. Choose a box style", 'greyd_hub' ) . '</h2>
                        <div class="wizzard_options">
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/box/hard.svg"><p>' . __( "square", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/box/rounded.svg"><p>' . __( "rounded", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/box/round.svg"><p>' . __( "round", 'greyd_hub' ) . '</p></span>
                        </div>
                        <div class="option_txt">' . __( "This setting controls the appearance of items such as buttons, form fields or notifications. Again, it's all about a rough direction at first – later you can design all the elements in detail.", 'greyd_hub' ) . '</div>
                    </div>
                    <div data-slide="4">
                        <h2>' . __( "4. Choose a header layout", 'greyd_hub' ) . '</h2>
                        <div class="wizzard_options">
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/menu/left.svg"><p>' . __( "left", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/menu/center.svg"><p>' . __( "centered", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option"><img class="option_icon" src="' . $img_url . '/menu/right.svg"><p>' . __( "right", 'greyd_hub' ) . '</p></span>
                        </div>
                        <div class="option_txt">' . __( "Where should the logo be in relation to the menu items? More detailed and responsive settings will be available later in the Customizer.", 'greyd_hub' ) . '</div>
                    </div>
                    <div data-slide="5">
                        <h2>' . __( "Check your settings", 'greyd_hub' ) . '</h2>
                        <div class="wizzard_options">
                            <span class="wizzard_option result"><img class="option_icon" src="' . $img_url . '/no-select.svg"><p>' . __( "Font", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option result"><img class="option_icon" src="' . $img_url . '/no-select.svg"><p>' . __( "Colors", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option result"><img class="option_icon" src="' . $img_url . '/no-select.svg"><p>' . __( "Boxes", 'greyd_hub' ) . '</p></span>
                            <span class="wizzard_option result"><img class="option_icon" src="' . $img_url . '/no-select.svg"><p>' . __( 'Header', 'greyd_hub' ) . '</p></span>
                        </div>
                        <div class="option_txt">' . __( "Check all your settings again. By clicking on the fields you will get back to the respective area.", 'greyd_hub' ) . '</div>
                    </div>
                    <div data-slide="6">
                        <h2>' . __( "A little patience", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Creating design...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Design successfully created.", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Installing design...", 'greyd_hub' ) . '</div>
                        <div class="loading"><div class="loader"></div></div>
                    </div>
                    <div data-slide="7">
                        <h2>' . __( "Congratulations!", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "The created design was successfully installed.", 'greyd_hub' ) . '</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                    <div data-slide="8">
                        <h2>' . __( "Basic settings", 'greyd_hub' ) . '</h2>
                        
                        <div class="option_txt">' . __( "Install recommended plugins", 'greyd_hub' ) . '</div>
                        <label for="plugin_forms">
                            <input type="checkbox" id="plugin_forms" name="plugin_forms" checked="checked"/><span>' . __( 'Greyd.Forms', 'greyd_hub' ) . '</span>
                            <small>' . __( "Our natively integrated form generator for creating and managing individual forms - with CRM connection and double opt-in.", 'greyd_hub' ) . '</small>
                        </label>
                        ' . (
							! class_exists( 'woocommerce' ) ? '' :
							'<label for="plugin_woocommerce-germanized">
                                <input type="checkbox" id="plugin_woocommerce-germanized" name="plugin_woocommerce-germanized" checked /><span>WooCommerce Germanized</span>
                                <small>' . __( "Legally relevant additional features for German web shops.", 'greyd_hub' ) . '</small>
                            </label>'
						) . '
                        <label for="plugin_yoast">
                            <input type="checkbox" id="plugin_yoast" name="plugin_yoast" /><span>Yoast SEO</span>
                            <small>' . __( "Improve your WordPress SEO: Create better content with the Yoast SEO plugin and get a completely optimized WordPress site.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="plugin_mediareplace">
                            <input type="checkbox" id="plugin_mediareplace" name="plugin_mediareplace" /><span>Enable Media Replace</span>
                            <small>' . __( "With this plugin, you can easily upload a new file to replace it via the edit function in the library.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="plugin_hidelogin">
                            <input type="checkbox" id="plugin_hidelogin" name="plugin_hidelogin" /><span>WPS Hide Login</span>
                            <small>' . __( "Hide the WordPress admin login area.", 'greyd_hub' ) . '</small>
                        </label>
                        ' . (
							class_exists( 'woocommerce' ) ? '' :
							'<label for="plugin_woocommerce">
                                <input type="checkbox" id="plugin_woocommerce" name="plugin_woocommerce" onchange="greyd.backend.toggleElemByClass(\'woo\')"/><span>WooCommerce</span>
                                <small>' . __( "Turn your WordPress page into a modern web shop.", 'greyd_hub' ) . '</small>
                            </label>
                            <div class="toggle_woo hidden">
                                <label for="plugin_woocommerce-germanized">
                                    <input type="checkbox" id="plugin_woocommerce-germanized" name="plugin_woocommerce-germanized" /><span>WooCommerce Germanized</span>
                                    <small>' . __( "Legally relevant additional features for German web shops.", 'greyd_hub' ) . '</small>
                                </label>
                            </div>'
						) . '
                    </div>
                    <div data-slide="9">
                        <h2>' . __( "A little patience", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Configuring settings...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Installing plugins...", 'greyd_hub' ) . '</div>
                        <div class="loading"><div class="loader"></div></div>
                    </div>
                    <div data-slide="10">
                        <h2>' . __( "Congratulations!", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Your new website has been successfully configured.", 'greyd_hub' ) . '</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                    
                    <div data-slide="20">
                        <h2>' . __( "Reset options", 'greyd_hub' ) . '</h2>
                        <label for="install_basics">
                            <input type="checkbox" id="install_basics" name="install_basics" checked /><span>' . __( "Install default content", 'greyd_hub' ) . ' ' . __( "(recommended)", 'greyd_hub' ) . '</span>
                            <small>' . __( "All default content (pages, posts, menus, etc.) will be created. Greyd.Forms plugin will be activated.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_thememods">
                            <input type="checkbox" id="reset_thememods" name="reset_thememods" checked /><span>' . _x( "Reset design", 'small', 'greyd_hub' ) . '</span>
                            <small>' . __( "The design settings will be reset to the default.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_plugins">
                            <input type="checkbox" id="reset_plugins" name="reset_plugins" checked /><span>' . __( "Disable plugins", 'greyd_hub' ) . '</span>
                            <small>' . __( "All plugins unnecessary for Greyd.Suite will be deactivated.", 'greyd_hub' ) . '</small>
                        </label>
                        <hr>
                        <div class="option_txt">' . __( "Delete items:", 'greyd_hub' ) . '</div>
                        <label for="reset_pages">
                            <input type="checkbox" id="reset_pages" name="reset_pages" /><span>' . __( "Pages", 'greyd_hub' ) . '</span>
                            <small>' . __( "All pages will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_templates">
                            <input type="checkbox" id="reset_templates" name="reset_templates" /><span>' . __( 'Templates', 'greyd_hub' ) . '</span>
                            <small>' . __( "All templates will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_posts">
                            <input type="checkbox" id="reset_posts" name="reset_posts" /><span>' . __( "Posts", 'greyd_hub' ) . '</span>
                            <small>' . __( "All posts will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>';
		if ( class_exists( 'woocommerce' ) ) {
			echo '<label for="reset_products">
                                <input type="checkbox" id="reset_products" name="reset_products" /><span>' . __( "Products", 'greyd_hub' ) . '</span>
                                <small>' . __( "All products will be permanently deleted.", 'greyd_hub' ) . '</small>
                            </label>
                            <label for="reset_shop_order">
                                <input type="checkbox" id="reset_shop_order" name="reset_shop_order" /><span>' . __( "Orders", 'greyd_hub' ) . '</span>
                                <small>' . __( "All pages will be deleted permanently.", 'greyd_hub' ) . '</small>
                            </label>';
		}
						echo '<label for="reset_forms">
                            <input type="checkbox" id="reset_forms" name="reset_forms" /><span>' . __( "Forms", 'greyd_hub' ) . '</span>
                            <small>' . __( "All forms will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_posttypes">
                            <input type="checkbox" id="reset_posttypes" name="reset_posttypes" /><span>' . __( "Post types", 'greyd_hub' ) . '</span>
                            <small>' . __( "All post types and related posts will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_popups">
                            <input type="checkbox" id="reset_popups" name="reset_popups" /><span>' . __( "Popups", 'greyd_hub' ) . '</span>
                            <small>' . __( "All popups will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_media">
                            <input type="checkbox" id="reset_media" name="reset_media" /><span>' . __( "Media", 'greyd_hub' ) . '</span>
                            <small>' . __( "All media will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                        <label for="reset_menus">
                            <input type="checkbox" id="reset_menus" name="reset_menus" /><span>' . __( "Menus", 'greyd_hub' ) . '</span>
                            <small>' . __( "All menus will be deleted permanently.", 'greyd_hub' ) . '</small>
                        </label>
                    </div>
                    <div data-slide="21">
                        <h2>' . __( "A little patience", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Resetting settings...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Deleting selected content...", 'greyd_hub' ) . '</div>
                        <div class="install_txt">' . __( "Creating default content...", 'greyd_hub' ) . '</div>
                        <div class="loading"><div class="loader"></div></div>
                    </div>
                    <div data-slide="22">
                        <h2>' . __( "Congratulations!", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( "Your website has been successfully reset.", 'greyd_hub' ) . '</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                </div>
                <div class="wizzard_foot">
                    <div data-slide="0-install">
                    </div>
                    <div data-slide="0-confirm">
                        <div class="flex">
                            <span class="close_wizzard button button-secondary">' . __( "cancel", 'greyd_hub' ) . '</span>
                            <span class="install_basics button button-primary grow">' . __( "set up as new", 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="0-success">
                    </div>
                    <div data-slide="0-error">
                        <div class="flex">
                            <span class="finish_wizzard reload button button-secondary grow">' . __( "close", 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="0-init">
                        <div class="flex flex-center">
                            <span class="settings_wizzard link">' . _x( "go to basic settings", 'small', 'greyd_hub' ) . '</span>
                        </div>
                        <div class="flex">
                            <span class="start_wizzard button button-secondary">' . _x( "start wizard", 'small', 'greyd_hub' ) . '</span>
                            <span class="start_library button button-primary grow">' . _x( 'Template Library', 'small', 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="0-reset">
                        <div class="flex">
                            <span class="close_wizzard button button-secondary">' . __( "cancel", 'greyd_hub' ) . '</span>
                            <span class="start_wizzard reset button button-primary grow"><span>' . __( "view options", 'greyd_hub' ) . '</span>&nbsp;&nbsp;<span class="dashicons dashicons-arrow-right-alt"></span></span>
                        </div>
                    </div>
                    <div data-slide="0">
                        <div class="flex flex-center">
                            <span class="settings_wizzard link">' . _x( "go to basic settings", 'small', 'greyd_hub' ) . '</span>
                        </div>
                        <div class="flex">
                            <span class="start_library button button-secondary">' . _x( 'Template Library', 'small', 'greyd_hub' ) . '</span>
                            <span class="start_wizzard button button-primary grow">' . _x( "start wizard", 'small', 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="1">
                        <div class="flex">
                            <span class="prev button button-secondary"><span class="dashicons dashicons-arrow-left-alt"></span></span>
                            <span class="pagination">
                                <span class="page" data-slide="1"></span>
                                <span class="page" data-slide="2"></span>
                                <span class="page" data-slide="3"></span>
                                <span class="page" data-slide="4"></span>
                            </span>
                            <span class="next button button-secondary"><span class="dashicons dashicons-arrow-right-alt"></span></span>
                        </div>
                    </div>
                    <div data-slide="5">
                        <div class="flex">
                            <span class="prev button button-secondary"><span class="dashicons dashicons-arrow-left-alt"></span></span>
                            <span class="install_mods button button-primary grow">' . _x( "set up design", 'small', 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="6">
                    </div>
                    <div data-slide="7">
                        <div class="flex">
                            <!--<span class="close_wizzard reload button button-secondary">' . __( "close", 'greyd_hub' ) . '</span>-->
                            <a class="button button-secondary" target="_blank" href="' . site_url( '/' ) . '">' . _x( "view website", 'small', 'greyd_hub' ) . '</a>
                            <span class="next button button-primary grow">' . __( "next", 'greyd_hub' ) . '&nbsp;&nbsp;<span class="dashicons dashicons-arrow-right-alt"></span></span>
                        </div>
                    </div>
                    <div data-slide="8">
                        <div class="flex">
                            <span class="close_wizzard button button-secondary">' . __( "cancel", 'greyd_hub' ) . '</span>
                            <span class="install_setup button button-primary grow">' . __( "install now", 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="9">
                    </div>
                    <div data-slide="10">
                        <div class="flex">
                            <span class="close_wizzard reload button button-secondary">' . __( "close", 'greyd_hub' ) . '</span>
                            <a class="finish_wizzard button button-primary grow" target="_blank" href="' . site_url( '/' ) . '">' . _x( "view website", 'small', 'greyd_hub' ) . '</a>
                        </div>
                    </div>
                    <div data-slide="20">
                        <div class="flex">
                            <span class="close_wizzard button button-secondary">' . __( "cancel", 'greyd_hub' ) . '</span>
                            <span class="install_reset button button-primary grow">' . __( "reset now", 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="21">
                    </div>
                    <div data-slide="22">
                        <div class="flex">
                            <span class="close_wizzard reload button button-secondary">' . __( "close", 'greyd_hub' ) . '</span>
                            <a class="finish_wizzard button button-primary grow" target="_blank" href="' . site_url( '/' ) . '">' . _x( "view website", 'small', 'greyd_hub' ) . '</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
	}
}
