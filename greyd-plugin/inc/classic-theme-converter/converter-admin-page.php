<?php
/**
 * Transform (detect, manage and convert) classic contents.
 *
 * @package Greyd
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Converter_Admin_Page();
class Converter_Admin_Page {

	/**
	 * Hold the dashboard page config
	 * slug, title, url, cap, callback
	 *
	 * @var array
	 */
	public static $page = array();

	/**
	 * Whether the Blocks Plugin is active
	 * 
	 * @var boolean
	 */
	public static $is_theme_active = false;

	/**
	 * Hold the classic content.
	 * 
	 * @var array
	 */
	public static $classic_content = false;

	/**
	 * Theme mod option name, eg. "theme_mods_greyd_suite"
	 */
	public static $theme_mods = false;
	public static $theme_mods_default = "theme_mods_greyd_suite";
	public static $theme_mods_options = array();

	/**
	 * Stylesheet name
	 */
	public static $stylesheet;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			return;
		}

		// init page converter
		add_action( 'init', array( $this, 'init' ), 0 );

	}

	public function init() {

		// if theme is inactive, we don't need to do anything
		if ( ! defined( 'GREYD_THEME_CONFIG' ) ) {
			return;
		}

		// if the old extension is still active inside the theme, do nothing
		if ( class_exists( '\Greyd\Theme\Converter_Admin_Page') ) {
			return;
		}

		// init vars
		self::$page             = array(
			'slug'     => 'greyd-classic-converter',
			'title'    => __( 'FSE Converter', 'greyd_hub' ),
			'url'      => admin_url( 'themes.php?page=greyd-classic-converter' ),
			'cap'      => 'edit_theme_options',
			'callback' => array( $this, 'render_converter_page' ),
		);
		self::$classic_content  = self::get_classic_content();
		// debug(self::$classic_content);

		// finish if no classic content is found
		if ( ! self::$classic_content || empty( self::$classic_content ) ) {
			return;
		}

		// admin menu
		add_action( 'admin_menu', array( $this, 'add_greyd_converter_page' ) );

		// dashboard
		add_filter( 'greyd_dashboard_panels', array( $this, 'add_greyd_dashboard_panel' ) );

		// scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ), 9 );

	}

	/**
	 * Add dashboard panel
	 *
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_dashboard_panel( $panels ) {

		array_push(
			$panels,
			array(
				'icon'     => 'null',
				'title'    => __( 'Converter', 'greyd_hub' ),
				'descr'    => __( 'Transform your old classic Greyd.Suite setup to new FSE features.', 'greyd_hub' ),
				'btn'      => array(
					array(
						'text' => __( 'Start converter', 'greyd_hub' ),
						'url'  => self::$page['url'],
						'icon' => 'external',
					),
				),
				'state'    => 'beta', // 'new',
				'cap'      => self::$page['cap'],
				'priority' => 0,
			)
		);

		return $panels;
	}

	/**
	 * Add the Converter page to the Appearance menu.
	 */
	public function add_greyd_converter_page() {

		add_submenu_page(
			'themes.php', // parent slug
			self::$page['title'], // page title
			self::$page['title'], // menu title
			self::$page['cap'], // capability
			self::$page['slug'], // slug
			self::$page['callback'], // callback
			10 // position
		);

	}

	/**
	 * add basic scripts
	 */
	public function load_backend_scripts() {

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'greyd-classic-converter' ) {

			wp_register_style(
				'greyd-classic-theme-converter-style',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'classic-theme-converter.css',
				null,
				GREYD_VERSION
			);
			wp_enqueue_style( 'greyd-classic-theme-converter-style' );

			wp_register_script(
				'greyd-classic-theme-converter-script',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'classic-theme-converter.js',
				array( 'jquery' ),
				GREYD_VERSION,
				true
			);
			wp_enqueue_script( 'greyd-classic-theme-converter-script' );

			// inline script before
			// define global greyd var
			wp_add_inline_script(
				'greyd-classic-theme-converter-script',
				'var greyd = greyd || {}; 
				greyd.transformAjax = { 
					action: "greyd_transform_ajax", 
					url: "' . admin_url( 'admin-ajax.php' ) . '", 
					nonce: "' . wp_create_nonce( 'greyd-transform' ) . '"
				};',
				'before'
			);

		}

	}

	/**
	 * =================================================================
	 *                          Render Page
	 * =================================================================
	 */

	public function render_converter_page() {

		// $classic_content = self::get_classic_content();

		if ( ! self::$classic_content || empty( self::$classic_content ) ) {
			$data    = '<div>' . __( 'We could not detect any classic Greyd.Suite contents on your website.', 'greyd_hub' ) . ' <span class="dashicons dashicons-smiley"></span></div>';
			$content = '';
		}
		else {
			// $data    = self::render_classic_content_data();
			$data    = self::render_classic_content_options();
			$content = self::render_classic_content();
		}

		// render
		echo '<div id="greyd-classic-converter" class="wrap" style="display:block; min-height: calc(100vh - 32px - 65px)">' .
				'<h1 class="wp-heading-inline">' . __( 'Classic to FSE Converter ', 'greyd_hub' ) . '</h1>' .
				'<p class="greyd-notice">' . __( "Transform your old classic Greyd.Suite setup to the new FSE features. Don't be afraid. You can simply convert different parts of your website step by step. As long as you haven't deleted the old contents, you can always go back to the old setup by simply switching back to the classic theme. Keep in mind that you should always make a backup of your website before making any changes.", 'greyd_hub' ) . '</p>' .
				$data .
				$content .
				'<div class="components-snackbar-list">
					<div id="converter-snackbar" class="components-snackbar">
						<div class="components-snackbar__content">
							<span class="components-snackbar__icon">💡</span>
							<span class="components-snackbar__text">Classic Theme Converter</span>
						</div>
				</div>' .
			'</div>';

	}

	// /**
	//  * render debug data
	//  */
	// public static function render_classic_content_data() {

	// 	$details = isset( $_GET['details'] ) ? $_GET['details'] : false;
	// 	// debug($details);

	// 	/**
	// 	 * Debug: show all theme_mods and templates
	// 	 */
	// 	if ( $details !== false ) {
	// 		$content  = '<a href="' . self::$page['url'] . '" class="page-title-action">' . __( 'hide data', 'greyd_hub' ) . '</a>';
	// 		echo '<h3>' . __( 'Detected Data', 'greyd_hub' ) . '</h3>';
	// 		if ( self::$classic_content['mods'] ) {
	// 			echo '<p>' . __( 'Theme Mods', 'greyd_hub' ) . '</p>';
	// 			echo '<div class="greyd-debug-panel-wrap">';
	// 			foreach ( self::$classic_content['mods'] as $feature => $mods ) {
	// 				echo self::debug_panel( $mods, $feature );
	// 			}
	// 			echo '</div>';
	// 		}
	// 		if ( self::$classic_content['fonts'] ) {
	// 			$content     .= '<p>' . __( 'Fonts', 'greyd_hub' ) . '</p>';
	// 			$content     .= '<div class="greyd-debug-panel-wrap">';
	// 				echo self::debug_panel( self::$classic_content['fonts'], 'fonts' );
	// 			$content     .= '</div>';
	// 		}
	// 		if ( self::$classic_content['menus'] ) {
	// 			$content     .= '<p>' . __( 'Menus', 'greyd_hub' ) . '</p>';
	// 			$content     .= '<div class="greyd-debug-panel-wrap">';
	// 				echo self::debug_panel( self::$classic_content['menus'], 'menus' );
	// 			$content     .= '</div>';
	// 		}
	// 		if ( self::$classic_content['templates'] ) {
	// 			echo '<p>' . __( 'Templates', 'greyd_hub' ) . '</p>';
	// 			echo '<div class="greyd-debug-panel-wrap">';
	// 			foreach ( self::$classic_content['templates'] as $feature => $mods ) {
	// 				echo self::debug_panel( $mods, $feature );
	// 			}
	// 			echo '</div>';
	// 		}
	// 	} else {
	// 		$content = '<a href="' . self::$page['url'] . '&details" class="page-title-action">' . __( 'show detected raw data', 'greyd_hub' ) . '</a>';
	// 	}

	// 	return $content;

	// }

	/**
	 * render Thememods options
	 */
	public static function render_classic_content_options() {
	
		// debug(self::$theme_mods_options);
		// debug(self::$theme_mods);

		$data = "";
		if (
			count(self::$theme_mods_options) > 1 ||
			!in_array( self::$theme_mods, self::$theme_mods_options )
		) {
			// check default/selected thememods
			if ( !in_array( self::$theme_mods, self::$theme_mods_options ) ) {
				if ( self::$theme_mods == self::$theme_mods_default ) {
					$data .= sprintf( __( "Classic Theme Mods %s not found - maybe you used a child theme?", 'greyd_hub' ), '<b>'.self::$theme_mods.'</b>' );
				}
				else {
					$data .= sprintf( __( "Selected Theme Mods %s not found!", 'greyd_hub' ), '<b>'.self::$theme_mods.'</b>' );
				}
				$data .= '<br>'.__( "Please choose other available Theme Mods from the list below.", 'greyd_hub' );
			}
			else {
				$data .= '<b>'.__( "Additional Theme Mods detected!", 'greyd_hub' ).'</b>';
				$data .= '<br>'.__( "You can change the Theme Mods to be converted by choosing from the list below.", 'greyd_hub' );
			}
			// render options
			if ( count(self::$theme_mods_options) > 0 ) {
				$data .= '<br><br>'.'<form action="'.self::$page['url'].'" id="select_delete_form" method="get">
							<input type="hidden" name="page" value="greyd-classic-converter">
							<select id="sort_filter" name="theme-mods" method="get">';
				foreach ( self::$theme_mods_options as $mod )
					$data .= '<option value="'.$mod.'" '.(self::$theme_mods == $mod ? 'selected' : '').'>'.str_replace( 'theme_mods_', '', $mod ).'</option>';
				$data .= '</select>
							'.(isset($_GET["debug"]) ? '<input type="hidden" name="debug" value="">' : '').'
							'.(isset($_GET["details"]) ? '<input type="hidden" name="details" value="">' : '').'
							<input type="submit" class="button button-primary" value="'.__( "Select Theme Mods", 'greyd_hub' ).'">
						</form>';
			}
			else {
				$data .= '<br><br><b>'.__( "Sorry, no additional Theme Mods found!", 'greyd_hub' ).'</b>';
			}
		}
		// render notice
		if ( $data != "" ) $data = '<div style="padding-block:1em"><span>'.$data.'</span></div>';

		return $data;
	}

	/**
	 * render grid
	 */
	public static function render_classic_content() {

		// debug(self::$classic_content['templates']);

		/**
		 * sort data
		 */
		$data = array(
			'styles'       => array(),
			'fonts'        => array(),
			'custom_css'   => '',
			'navigation'   => array(
				'header' => array(
					'theme_mods' => array(),
					'templates'  => array(),
				),
				'footer' => array(
					'theme_mods' => array(),
					'templates'  => array(),
				),
				'menus'  => array(),
			),
			'wp_templates' => array(),
			'dynamic'      => array(),
			'features'     => array(
				// 'announcement' => array(),
				// 'cookiebar' => array(
				// 'theme_mods' => array(),
				// 'templates' => array(),
				// ),
				// 'compatibility' => array(
				// 'theme_mods' => array(),
				// 'templates' => array(),
				// ),
				// 'woocommerce' => array(
				// 'theme_mods' => array(),
				// 'templates' => array(),
				// ),
			),
			'delete'       => array(
				'mods'      => false,
				'templates' => false,
				'menus'     => false,
				'fonts'     => false,
			),
		);

		// get styles data
		if ( self::$classic_content['mods'] ) {
			$data['delete']['mods'] = true;
			if ( isset( self::$classic_content['mods']['styles'] ) ) {
				$data['styles'] = self::$classic_content['mods']['styles'];
				unset( self::$classic_content['mods']['styles'] );
			}
			if ( isset( self::$classic_content['mods']['header'] ) ) {
				if ( isset( self::$classic_content['mods']['header']['navi_footer'] ) ) {
					$data['navigation']['footer']['theme_mods']['navi_footer'] = self::$classic_content['mods']['header']['navi_footer'];
					unset( self::$classic_content['mods']['header']['navi_footer'] );
				}
				$data['navigation']['header']['theme_mods'] = self::$classic_content['mods']['header'];
				unset( self::$classic_content['mods']['header'] );
			}

			if ( isset( self::$classic_content['mods']['extra']['announcement'] ) ) {
				if ( ! isset( $data['features']['announcement'] ) ) {
					$data['features']['announcement'] = array( 'theme_mods' => array() );
				}
				$data['features']['announcement']['theme_mods'] = self::$classic_content['mods']['extra']['announcement'];
				unset( self::$classic_content['mods']['extra']['announcement'] );
			}
			if ( isset( self::$classic_content['mods']['extra']['cookiebar'] ) ) {
				if ( ! isset( $data['features']['cookiebar'] ) ) {
					$data['features']['cookiebar'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['cookiebar']['theme_mods'] = self::$classic_content['mods']['extra']['cookiebar'];
				unset( self::$classic_content['mods']['extra']['cookiebar'] );
			}
			if ( isset( self::$classic_content['mods']['extra']['compatibility'] ) ) {
				if ( ! isset( $data['features']['compatibility'] ) ) {
					$data['features']['compatibility'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['compatibility']['theme_mods'] = self::$classic_content['mods']['extra']['compatibility'];
				unset( self::$classic_content['mods']['extra']['compatibility'] );
			}
			if ( isset( self::$classic_content['mods']['extra']['woocommerce'] ) ) {
				if ( ! isset( $data['features']['woocommerce'] ) ) {
					$data['features']['woocommerce'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['woocommerce']['theme_mods'] = self::$classic_content['mods']['extra']['woocommerce'];
				unset( self::$classic_content['mods']['extra']['woocommerce'] );
			}
		}

		// get templates data
		if ( self::$classic_content['templates'] ) {
			$data['delete']['templates'] = true;
			if ( isset( self::$classic_content['templates']['navigation'] ) ) {
				if ( isset( self::$classic_content['templates']['navigation']['footer'] ) ) {
					$data['navigation']['footer']['templates']['footer'] = self::$classic_content['templates']['navigation']['footer'];
					unset( self::$classic_content['templates']['navigation']['footer'] );
				}
				$data['navigation']['header']['templates'] = self::$classic_content['templates']['navigation'];
				unset( self::$classic_content['templates']['navigation'] );
			}
			if ( isset( self::$classic_content['templates']['dynamic'] ) ) {
				$data['dynamic'] = self::$classic_content['templates']['dynamic'];
				unset( self::$classic_content['templates']['dynamic'] );
			}

			if ( isset( self::$classic_content['templates']['more']['cookiebar'] ) ) {
				if ( ! isset( $data['features']['cookiebar'] ) ) {
					$data['features']['cookiebar'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['cookiebar']['templates'] = self::$classic_content['templates']['more']['cookiebar'];
				unset( self::$classic_content['templates']['more']['cookiebar'] );
			}
			if ( isset( self::$classic_content['templates']['more']['compatibility'] ) ) {
				if ( ! isset( $data['features']['compatibility'] ) ) {
					$data['features']['compatibility'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['compatibility']['templates'] = self::$classic_content['templates']['more']['compatibility'];
				unset( self::$classic_content['templates']['more']['compatibility'] );
			}
			if ( isset( self::$classic_content['templates']['woo'] ) ) {
				if ( ! isset( $data['features']['woocommerce'] ) ) {
					$data['features']['woocommerce'] = array(
						'theme_mods' => array(),
						'templates'  => array(),
					);
				}
				$data['features']['woocommerce']['templates'] = self::$classic_content['templates']['woo'];
				unset( self::$classic_content['templates']['woo'] );
			}
			$data['wp_templates'] = self::$classic_content['templates'];
		}

		// get menus data
		if ( self::$classic_content['menus'] ) {
			$data['delete']['menus'] = true;
			// todo
			$data['navigation']['menus'] = self::$classic_content['menus'];
		}

		// get fonts data
		if ( self::$classic_content['fonts'] ) {
			$data['delete']['fonts'] = true;
			// todo
			$data['fonts'] = self::$classic_content['fonts'];
		}

		// get custom css
		if ( self::$classic_content['custom_css'] ) {
			$data['custom_css'] = self::$classic_content['custom_css'];
		}
		

		/**
		 * render
		 */
		ob_start();
		$content = '';

		if ( ! empty( $data['styles'] ) || ! empty( $data['fonts'] ) ) {

			echo '<section class="converter-section">
				<div class="converter-section--title">
					<h2>' . __( 'Global Styles', 'greyd_hub' ) . '</h2>
				</div>
				<div class="converter-section--content">';

			// styles
			if ( ! empty( $data['styles'] ) ) {

				echo '<div class="converter-card">
				<h3 class="converter-card--title">' . __( 'Styles', 'greyd_hub' ) . '</h3>
				<p class="converter-card--desc">' . __( 'Convert old Customizer Styles (theme_mods) to Global Styles (wp_global_styles).', 'greyd_hub' ) . '</p>';

				echo self::debug_panel_toggle( 'styles', $data['styles'] );

				echo self::render_notice(
					array(
						'type' => 'info',
						'content' => __( 'Breakpoints, messages and tables are not being converted yet.', 'greyd_hub' ),
					)
				);

				echo '<hr>';

				echo self::render_button(
					array(
						'data'  => $data['styles'],
						'mode'  => 'convert_styles_data',
						'title' => __( 'Convert styles', 'greyd_hub' )
					)
				);
				echo '</div>';
			}

			// fonts
			if ( ! empty( $data['fonts'] ) ) {

				echo '<div class="converter-card">
				<h3 class="converter-card--title">' . __( 'Fonts', 'greyd_hub' ) . '</h3>
				<p class="converter-card--desc">' . __( 'Convert your custom and Google Fonts to new format (wp_font_family).', 'greyd_hub' ) . '</p>';

				echo self::debug_panel_toggle( 'fonts', $data['fonts'], __( 'Fonts', 'greyd_hub' ) );

				echo self::render_notice(
					array(
						'type' => 'info',
						'content' => __( 'After converting the fonts, you might need to reassign them to some of the elements in the editor.', 'greyd_hub' ),
					)
				);
			

				echo '<hr>';

				echo self::render_button(
					array(
						'data'     => $data['fonts'],
						'mode'     => 'convert_fonts',
						'title'    => __( 'Convert fonts', 'greyd_hub' )
					)
				);
				
				echo '</div>';
			}

			// custom css
			if ( ! empty( $data['custom_css'] ) ) {

				echo '<div class="converter-card">
				<h3 class="converter-card--title">' . __( 'Custom CSS', 'greyd_hub' ) . '</h3>
				<p class="converter-card--desc">' . __( 'Convert your custom CSS to new format (wp_global_styles).', 'greyd_hub' ) . '</p>';

				echo self::debug_panel_toggle( 'custom_css', $data['custom_css'], __( 'Custom CSS', 'greyd_hub' ) );

				echo '<hr>';

				echo self::render_button(
					array(
						'data'     => $data['custom_css'],
						'mode'     => 'convert_custom_css',
						'title'    => __( 'Convert custom CSS', 'greyd_hub' )
					)
				);
				
				echo '</div>';
			}

			echo '</div></section>';

		}

		if ( ! empty( $data['navigation']['menus'] ) ||
			! empty( $data['navigation']['header']['theme_mods'] ) ||
			! empty( $data['navigation']['footer']['theme_mods'] )
		) {

			echo '<section class="converter-section">
				<div class="converter-section--title">
					<h2>' . __( 'Navigation', 'greyd_hub' ) . '</h2>
				</div>
				<div class="converter-section--content">';
			

			// header/footer
			echo '<div class="converter-card">
			<h3 class="converter-card--title">' . __( 'Header & Footer', 'greyd_hub' ) . '</h3>
			<p class="converter-card--desc">' . __( 'Convert old Customizer Styles (theme_mods) and Dynamic Templates to Header and Footer Template Parts (wp_template_part).', 'greyd_hub' ) . '</p>';

			echo self::debug_panel_toggle( 'header', $data['navigation']['header'], __( 'Header', 'greyd_hub' ) );
			echo self::debug_panel_toggle( 'footer', $data['navigation']['footer'], __( 'Footer', 'greyd_hub' ) );

			echo self::render_notice(
				array(
					'type' => 'info',
					'content' => __( 'Layouts are not properly converted yet.', 'greyd_hub' ),
				)
			);

			echo '<hr>';

			echo self::render_button(
				array(
					'data'  => $data['navigation']['header'],
					'mode'  => 'convert_header_data',
					'title' => __( 'Convert header', 'greyd_hub' ),
					'disabled' => empty( $data['navigation']['header']['theme_mods'] )
				)
			);
			echo self::render_button(
				array(
					'data'  => $data['navigation']['footer'],
					'mode'  => 'convert_footer_data',
					'title' => __( 'Convert footer', 'greyd_hub' ),
					'disabled' => empty( $data['navigation']['footer']['theme_mods'] )
				)
			);

			echo '</div>';

			// menus (todo)
			if ( ! empty( $data['navigation']['menus'] ) ) {

				echo '<div class="converter-card">
				<h3 class="converter-card--title">' . __( 'Menus', 'greyd_hub' ) . '</h3>
				<p class="converter-card--desc">' . __( 'Convert old WP menus to new navigation menus (wp_navigation).', 'greyd_hub' ) . '</p>';

				echo self::debug_panel_toggle( 'menus', $data['navigation']['menus'], __( 'Menus', 'greyd_hub' ) );

				echo self::render_notice(
					array(
						'type' => 'warning',
						'content' => __( 'Converting menus is not yet implemented.', 'greyd_hub' ),
					)
				);

				echo '<hr>';

				echo self::render_button(
					array(
						'data'     => $data['navigation']['menus'],
						'mode'     => 'convert_menus',
						'title'    => __( 'Convert menus', 'greyd_hub' ),
						'disabled' => true,
					)
				);
				echo '</div>';
			}

			echo '</div></section>';

		}

		echo '<section class="converter-section">
			<div class="converter-section--title">
				<h3>' . __( 'Templates', 'greyd_hub' ) . '</h3>
			</div>
			<div class="converter-section--content">';

		if ( ! empty( $data['wp_templates'] ) ) {

			echo '<div class="converter-card">
			<h3 class="converter-card--title">' . __( 'System templates', 'greyd_hub' ) . '</h3>
			<p class="converter-card--desc">' . __( 'Convert old dynamic system templates (e.g. single, archive, search, 404) to new WP templates (wp_template).', 'greyd_hub' ) . '</p>';

			echo self::render_notice(
				array(
					'type' => 'info',
					'content' => __( 'Support for Post Type and Taxonomy based templates is not yet implemented.', 'greyd_hub' ),
				)
			);

			echo '<hr>';

			foreach ( $data['wp_templates'] as $category => $templates ) {
				foreach ( $templates as $slug => $template ) {
					echo self::debug_panel_toggle( 'wp_templates', $templates, $category );
					$template['name'] = $slug;
					echo self::render_button(
						array(
							'data'  => $template,
							'mode'  => 'convert_template',
							'title' => sprintf( __( 'Convert "%s"', 'greyd_hub' ), $template['title'] ),
						)
					);
				}
			}
			
			echo '</div>';
		}

		echo '<div class="converter-card">
		<h3 class="converter-card--title">' . __( 'Dynamic Templates', 'greyd_hub' ) . '</h3>';

		echo self::render_notice(
			array(
				'type' => 'success',
				'content' => __( 'Dynamic Templates do not need to be converted, you can use them as before.', 'greyd_hub' ),
			)
		);
		
		echo self::debug_panel_toggle( 'dynamic', $data['dynamic'] );
		
		echo '</div>';

		echo '</div></section>';


		// if ( ! empty( $data['features'] ) ) {

		// 	echo '<section class="converter-section">
		// 		<div class="converter-section--title">
		// 			<h3>' . __( 'Features', 'greyd_hub' ) . '</h3>
		// 		</div>
		// 		<div class="converter-section--content">';

		// 	echo '<div class="converter-card">
		// 	<h3 class="converter-card--title">' . __( 'Customizer Features', 'greyd_hub' ) . '</h3>
		// 	<p class="converter-card--desc">' . __( 'Additional deprecated Features detected.', 'greyd_hub' ) . '</p>';

		// 	echo self::debug_panel_toggle( 'features', $data['features'] );

		// 	echo self::render_notice(
		// 		array(
		// 			'type' => 'warning',
		// 			'content' => __( 'Converting these features is not yet implemented.', 'greyd_hub' ),
		// 		)
		// 	);

		// 	echo '<hr>';
			
		// 	if ( ! empty( $data['features']['announcement'] ) ) {
		// 		echo self::render_button(
		// 			array(
		// 				'mode'     => 'convert_announcement',
		// 				'title'    => __( 'Convert Announcement Bar', 'greyd_hub' ),
		// 				'disabled' => true,
		// 			)
		// 		);
		// 	}
		// 	if ( ! empty( $data['features']['cookiebar'] ) ) {
		// 		echo self::render_button(
		// 			array(
		// 				'mode'     => 'convert_cookiebar',
		// 				'title'    => __( 'Convert Cookiebar', 'greyd_hub' ),
		// 				'disabled' => true,
		// 			)
		// 		);
		// 	}
		// 	if ( ! empty( $data['features']['compatibility'] ) ) {
		// 		echo self::render_button(
		// 			array(
		// 				'mode'     => 'convert_compatibility',
		// 				'title'    => __( 'Convert Compatibility Popup', 'greyd_hub' ),
		// 				'disabled' => true,
		// 			)
		// 		);
		// 	}
		// 	if ( ! empty( $data['features']['woocommerce'] ) ) {
		// 		echo self::render_button(
		// 			array(
		// 				'mode'     => 'convert_woocommerce',
		// 				'title'    => __( 'Convert Woocommerce', 'greyd_hub' ),
		// 				'disabled' => true,
		// 			)
		// 		);
		// 	}
		// 	echo '</div>';

		// 	echo '</div></section>';

		// }

		echo '<section class="converter-section">
		<div class="converter-section--title">
			<h3>' . __( 'Cleanup', 'greyd_hub' ) . '</h3>
		</div>
		<div class="converter-section--content">';

		echo '<div class="converter-card">
		<h3 class="converter-card--title">' . __( 'Menus', 'greyd_hub' ) . '</h3>
		<p class="converter-card--desc">' . __( 'Delete old contents and data after transformation is done.', 'greyd_hub' ) . '</p>';

		echo self::debug_panel_toggle( 'menus', $data['navigation']['menus'], __( 'Menus', 'greyd_hub' ) );

		echo self::render_notice(
			array(
				'type' => 'info',
				'content' => __( 'These actions cannot be made undone', 'greyd_hub' ),
			)
		);

		echo '<hr>';

		if ( $data['delete']['mods'] ) {
			echo self::render_button(
				array(
					'data'  => self::$theme_mods,
					'mode'  => 'delete_theme_mods',
					'title' => __( 'Delete old Customizer styles', 'greyd_hub' ),
					'class' => 'is-destructive',
					// 'disabled' => true
				)
			);
		}
		if ( $data['delete']['menus'] ) {
			echo self::render_button(
				array(
					'mode'  => 'delete_menus',
					'title' => __( 'Delete old WP menus', 'greyd_hub' ),
					'class' => 'is-destructive',
					// 'disabled' => true
				)
			);
		}
		if ( $data['delete']['templates'] ) {
			echo self::render_button(
				array(
					'mode'  => 'delete_templates',
					'title' => __( 'Delete old system templates', 'greyd_hub' ),
					'class' => 'is-destructive',
					// 'disabled' => true
				)
			);
		}
		if ( $data['delete']['fonts'] ) {
			echo self::render_button(
				array(
					'mode'  => 'delete_fonts',
					'title' => __( 'Delete old custom fonts', 'greyd_hub' ),
					'class' => 'is-destructive',
					// 'disabled' => true
				)
			);
		}

		echo '</div></section>';

		$content = ob_get_contents();
		ob_end_clean();

		return $content;

	}

	/**
	 * =================================================================
	 *                          Render Helper
	 * =================================================================
	 */

	public static function render_button( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'    => 'button-primary',
				'data'     => array(),
				'mode'     => '',
				'title'    => 'call',
				'disabled' => false,
			)
		);

		return sprintf(
			'<p class="greyd-transform-button-wrap">
				<button class="button %s" %s 
					onclick="greyd.transform.call(this)" 
					data-data="%s" 
					data-mode="%s"
				>%s
					<span class="loading hidden"><span class="spinner is-active"></span></span>
				</button>
			</p>',
			$args['class'],
			( $args['disabled'] ? 'disabled' : '' ),
			rawurlencode( json_encode( $args['data'] ) ),
			$args['mode'],
			$args['title']
		);
	}

	public static function render_activation_hint( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'headline' => __( 'Activate the Greyd Plugin to use extended features.', 'greyd_hub' ),
				'title'    => __( 'Activate plugin', 'greyd_hub' ),
			)
		);
		return self::render_notice(
			array(
				'type'     => 'warning',
				'content'  => $args['headline'],
			)
		) . self::render_button(
			array(
				'class' => 'button-greyd',
				'mode'  => 'activate_plugin',
				'title' => $args['title'],
			)
		);
	}

	public static function debug_panel_toggle( $name, $data, $title = '' ) {

		if ( !isset( $_GET['debug'] ) ) return '';

		$details = isset( $_GET['details'] ) ? $_GET['details'] : false;
		$hidden  = ( $details == 'full' || $details == $name ) ? '' : 'hidden';
		$label   = sprintf( __( 'Show %s data', 'greyd_hub' ), $name );
		$click   = 'this.nextSibling.classList.toggle(\'hidden\')';
		$toggle  = '<span class="greyd-debug-panel-toggle" onclick="' . $click . '">' . $label . ' +</span>';
		$panel   = '<div class="' . $hidden . '">' . self::debug_panel( $data, $title ) . '</div>';
		return $toggle . $panel;
	}

	public static function debug_panel( $data, $title = '' ) {
		// $id = uniqid('debug_');
		if ( ! empty( $title ) ) {
			$title = '<b>' . $title . '</b><br>';
		}
		return '<pre class="greyd-debug-panel">' . $title . self::debug_panel_content( $data ) . '</pre>';
	}
	public static function debug_panel_content( $data ) {
		// debug($data);
		$content = '';
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$class     = 'hidden';
				$arr_start = '(';
				$arr_end   = ')';
				$count     = count( $value );
				if ( count( array_filter( array_keys( $value ), 'is_string' ) ) > 0 ) {
					// assoc array
					$class     = '';
					$arr_start = '{';
					$arr_end   = '}';
					$count     = '';
				}
				$click = 'this.nextSibling.classList.toggle(\'hidden\')';
				$key   = '<span onclick="' . $click . '">[' . trim( $key ) . '] => ' . $arr_start . $count . '</span>';
				$val   = '<div class="' . $class . '">' . self::debug_panel_content( $value ) . '</div>' . $arr_end;
			} else {
				$key = '[' . trim( $key ) . '] => ';
				$val = trim( htmlentities( $value ) );
			}
			$content .= $key . $val . '<br>';
		}
		return $content;
	}

	/**
	 * =================================================================
	 *                          Classic Content
	 * =================================================================
	 */

	public static function get_classic_content() {

		/**
		 * get all available theme_mods from database.
		 * including those from child and other themes.
		 */
		global $wpdb;
		// query option names
		$all_theme_mods = $wpdb->get_col( "SELECT DISTINCT option_name FROM $wpdb->options WHERE option_name LIKE 'theme_mods_%'" );
		self::$theme_mods_options = array();
		foreach ( $all_theme_mods as $mod ) {
			// filter out 'greyd_hub' and core 'twentyxxx' thememods
			if ( strpos( $mod, 'theme_mods_greyd_hub') === false && strpos( $mod, 'theme_mods_twenty') === false ) {
				array_push( self::$theme_mods_options, $mod );
			}
		}
		// get selected thememods either from default or get param
		self::$theme_mods = isset($_GET["theme-mods"]) && strpos( $_GET["theme-mods"], 'theme_mods_') === 0 ? $_GET["theme-mods"] : self::$theme_mods_default;
		self::$stylesheet = str_replace( 'theme_mods_', '', self::$theme_mods );
		
		/**
		 * theme_mods -> global styles
		 */
		$mods       = false;
		$theme_mods = get_option( self::$theme_mods, false );
		if ( $theme_mods ) {

			$mods = array();
			// get transform infos
			$raw_url = plugin_dir_url( __FILE__ ) . 'transform.json';
			$context = stream_context_create( array(
				"ssl" => array(
					"verify_peer" => false,
					"verify_peer_name" => false,
				)
			) );
			$raw = json_decode( file_get_contents( $raw_url, false, $context ), true );
			
			// get values
			foreach ( $raw as $feature => $sections ) {
				foreach ( $sections as $section => $values ) {
					foreach ( $values as $mod ) {
						$value = $mod['default'] . $mod['unit'];
						if ( isset( $theme_mods[ $mod['mod'] ] ) ) {
							// if ( $theme_mods[$mod['mod']] != $mod['default'] )
							$value = $theme_mods[ $mod['mod'] ];
							if ( ! empty( $mod['unit'] ) ) {
								$value .= $mod['unit'];
							}
							unset( $theme_mods[ $mod['mod'] ] );
						}

						if ( ! isset( $mods[ $feature ] ) ) {
							$mods[ $feature ] = array();
						}
						if ( ! isset( $mods[ $feature ][ $section ] ) ) {
							$mods[ $feature ][ $section ] = array();
						}
						$mods[ $feature ][ $section ][] = array(
							'mod'          => $mod['mod'],
							'default'      => $mod['default'] . $mod['unit'],
							'value'        => $value,
							'global-style' => $mod['global-style'],
						);
					}
				}
			}
			// var_error_log( $mods );

			// remove deprecated keys - keep unknown
			foreach ( $theme_mods as $key => $val ) {
				if ( substr( $key, -strlen( '_preset' ) ) === '_preset' ||
					substr( $key, -strlen( '_icon' ) ) === '_icon' ||
					substr( $key, -strlen( '_color' ) ) === '_color' ||
					substr( $key, -strlen( '_color_hover' ) ) === '_color_hover' ||
					substr( $key, -strlen( '_background' ) ) === '_background' ||
					substr( $key, -strlen( '_background_hover' ) ) === '_background_hover' ||
					substr( $key, -strlen( '_border' ) ) === '_border' ||
					substr( $key, -strlen( '_border_hover' ) ) === '_border_hover' ||
					$key == 'navi_header_sec_mobile_toggle'
				) {
					unset( $theme_mods[ $key ] );
				}
			}
			$mods['unknown'] = $theme_mods;

		}

		/**
		 * templates
		 */
		$templates      = false;
		$template_posts = get_posts(
			array(
				'post_type'   => 'dynamic_template',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);
		if ( $template_posts ) {

			register_taxonomy( 'template_type', 'dynamic_template' );
			foreach ( $template_posts as $template ) {

				$terms = get_the_terms( $template->ID, 'template_type' );

				if ( ! $terms || ! is_array( $terms ) ) {
					continue;
				}
				
				$term  = array_shift( $terms );

				$type  = $term->slug;

				if ( $type == 'dynamic' ) {
					continue;
				}

				if ( ! is_array( $templates ) ) {
					$templates = array();
				}
				if ( ! isset( $templates[ $type ] ) ) {
					$templates[ $type ] = array();
				}

				$templates[ $type ][ $template->post_name ] = array(
					'id'    => $template->ID,
					'title' => $template->post_title,
					// 'content' => array( htmlentities($template->post_content) ),
				);
			}
		}

		/**
		 * menus
		 */
		$menus     = false;
		$nav_menus = wp_get_nav_menus();
		if ( $nav_menus ) {

			$menus = array();
			foreach ( $nav_menus as $nav_menu ) {
				$menu_items = wp_get_nav_menu_items( $nav_menu->term_id, array( 'update_post_term_cache' => false ) );
				$items      = array();
				foreach ( $menu_items as $item ) {
					// $meta = get_post_meta( $item->ID, '', true );
					$items[] = array(
						'id'     => $item->ID,
						'title'  => $item->title,
						'parent' => $item->menu_item_parent,
						'url'    => $item->url,
					);
				}
				$menus[ $nav_menu->slug ] = array(
					'id'    => $nav_menu->term_id,
					'title' => $nav_menu->name,
					'items' => $items,
				);
			}
		}

		/**
		 * fonts
		 */
		$fonts = array();
		$fonts['websafe'] = array();
		$fonts['custom'] = array();
		$fonts['google'] = array();
		// need to grab all fonts
		$old_theme_mods = get_option( self::$theme_mods, false );
		$old_fonts = array();
		// get the fonts stored in fontFamily1, fontFamily2, fontFamily3
		if ( $old_theme_mods ) {
			foreach ( $old_theme_mods as $key => $value ) {
				if ( str_starts_with( $key, 'fontFamily' ) && !empty($value) ) {
					if ( !in_array( $value, array_values($old_fonts) ) ) {
						$old_fonts[ $key ] = $value;
					}
				}
			}
		}

		// Check if any of those old fonts is a websafe font
		$old_webfonts = array(
			// Sans-Serif Fonts
			'Arial, Helvetica, sans-serif' => 'Arial, Helvetica, sans-serif',
			'"Arial Black", Gadget, sans-serif' => '"Arial Black", Gadget, sans-serif',
			'"Comic Sans MS", cursive, sans-serif' => '"Comic Sans MS", cursive, sans-serif',
			'Impact, Charcoal, sans-serif' => 'Impact, Charcoal, sans-serif',
			'"Lucida Sans Unicode", "Lucida Grande", sans-serif' => '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
			'Tahoma, Geneva, sans-serif' => 'Tahoma, Geneva, sans-serif',
			'"Trebuchet MS", Helvetica, sans-serif' => '"Trebuchet MS", Helvetica, sans-serif',
			'Verdana, Geneva, sans-serif' => 'Verdana, Geneva, sans-serif',
			// Serif Fonts
			'Georgia, serif' => 'Georgia, serif',
			'"Palatino Linotype", "Book Antiqua", Palatino, serif' => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
			'"Times New Roman", Times, serif' => '"Times New Roman", Times, serif',
			// Monospace Fonts
			'"Courier New", Courier, monospace' => '"Courier New", Courier, monospace',
			'"Lucida Console", Monaco, monospace' => '"Lucida Console", Monaco, monospace',
		);
		$webfonts = array_intersect( $old_webfonts, $old_fonts );

		if ( ! empty( $webfonts ) && count( $webfonts ) > 0 ) {
			// add websafe fonts to fonts array
			$fonts['websafe'] = $webfonts;
			// remove the websafe fonts from the original array
			foreach( $webfonts as $font ) {
				$old_key = array_search($font, $old_fonts);
				unset($old_fonts[$old_key]);
			}
		}

		// Check if any of the old fonts is a custom font and add them if available
		$uploads   = wp_upload_dir();
		$fonts_url = $uploads['basedir'] . '/greyd_tp/custom_fonts/custom_fonts_index.json';
		if ( file_exists( $fonts_url ) ) {
			$raw = json_decode( file_get_contents( $fonts_url ), true );
			$fonts['custom'] = $raw['fonts'];

			if ( ! empty( $fonts['custom'] ) && count( $fonts['custom'] ) > 0 ) {
				// remove custom fonts from the original array
				foreach( $fonts['custom'] as $font ) {
					$old_key = array_search($font['name_full'], $old_fonts);
					unset($old_fonts[$old_key]);
				}
			}
		}

		// Anything that is left in $old_fonts from here on is a Google Font
		if ( ! empty( $old_fonts ) && count( $old_fonts ) > 0 ) {
			foreach ( $old_fonts as $key => $value ) {
				$fonts['google'][] = $value;
			}
			if ( ! empty( $fonts['google'] ) && count( $fonts['google'] ) > 0 ) {
				// remove google fonts from the original array
				foreach( $fonts['google'] as $font ) {
					$old_key = array_search($font, $old_fonts);
					unset($old_fonts[$old_key]);
				}
			}
		}

		// check if any fonts are found
		if ( empty( $fonts['websafe'] ) && empty( $fonts['custom'] ) && empty( $fonts['google'] ) ) {
			$fonts = array();
		}

		// get custom css
		$custom_css = wp_get_custom_css( self::$stylesheet );

		if ( empty( $mods ) && empty( $templates ) && empty( $menus ) ) {
			return false;
		}

		return array(
			'mods'      => $mods,
			'templates' => $templates,
			'menus'     => $menus,
			'fonts'     => $fonts,
			'custom_css' => $custom_css,
		);
	}

	/**
	 * Render Infobox in Backend.
	 *
	 * @param array $atts
	 *      @property string content       Infocontent.
	 *      @property string type      Style of the notice (success, warning, alert, new).
	 */
	public static function render_notice( $atts = array() ) {

		$content = isset( $atts['content'] ) ? '<span>' . html_entity_decode( esc_attr( $atts['content'] ) ) . '</span>' : '';
		$type    = isset( $atts['type'] ) ? esc_attr( $atts['type'] ) : 'info';
		$color   = 'blue';
		$icon    = 'dashicons-info';

		if ( $type == 'success' ) {
			$color = 'green';
			$icon  = 'dashicons-yes';
		} elseif ( $type == 'warning' ) {
			$color = 'orange';
			$icon  = 'dashicons-warning';
		} elseif ( $type == 'alert' ) {
			$color = 'red';
			$icon  = 'dashicons-warning';
		} elseif ( $type == 'new' ) {
			$color = 'purple';
			$icon  = 'dashicons-megaphone';
		}

		return sprintf(
			'<div class="greyd-chip-notice %s"><span class="dashicons %s"></span><div>%s</div></div>',
			$color,
			$icon,
			$content
		);
	}
}
