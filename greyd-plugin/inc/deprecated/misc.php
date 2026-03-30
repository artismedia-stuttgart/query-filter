<?php
/**
 * Misc Features and Settings from Theme and Plugins that don't use greyd_filters.
 */
namespace Greyd;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Misc_Features($config);
class Misc_Features {

	/**
	 * Holds plugin config array
	 */
	private $config;

	/**
	 * Class constructor
	 */
	public function __construct($config) {
		// set config
		$this->config = (object)$config;

		// Theme Greyd.Suite
		if ( $this->config->is_greyd_classic ) {

			// submenu misc (plugins)
			add_filter( 'greyd_misc_pages', array($this, 'add_greyd_suite_misc_page') );
			// dashboard tab (plugins)
			add_filter( 'greyd_dashboard_tabs', array($this, 'add_greyd_suite_dashboard_tab') );


			// dashboard panel (customizer)
			add_filter( 'greyd_dashboard_panels', array($this, 'add_greyd_suite_dashboard_panel') );
			// adminbar backend/frontend customizer
			add_filter( 'greyd_admin_bar_group', array($this, 'add_customizer_admin_bar_item'), 10, 2 );

			// settings (builder, analytics, webshop)
			add_filter( 'greyd_settings_default_site', array($this, 'add_greyd_suite_setting') );
			add_filter( 'greyd_settings_basic', array($this, 'render_greyd_suite_settings'), 5, 3 );
			add_filter( 'greyd_settings_more_save', array($this, 'save_greyd_suite_settings'), 10, 3 );

		}

	}


	/*
	=======================================================================
		Greyd.Suite
	=======================================================================
	*/

	/**
	 * Add Plugins misc page
	 * @see filter 'greyd_misc_pages'
	 */
	public function add_greyd_suite_misc_page($pages) {
		// debug($pages);

		// add Greyd Plugins to 'Plugins' menu
		// $pages['plugins'] = array(
		// 	'parent'    => 'plugins.php',
		// 	'title'     => __('Empfehlungen', 'greyd_hub'),
		// 	'cap'       => 'install_plugins',
		// 	'slug'      => admin_url( 'plugins.php?page=install-required-plugins'),
		// 	'callback'  => "",
		// 	'position'  => 10
		// );

		return $pages;
	}

	/**
	 * Add Plugins dashboard tab
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_suite_dashboard_tab($tabs) {
		// debug($tabs);

		// $tabs['plugins'] = array(
		// 	'title'     => __('Plugins', 'greyd_hub'),
		// 	'slug'      => 'install-required-plugins',
		// 	'url'       => admin_url( 'plugins.php?page=install-required-plugins'),
		// 	'cap'       => 'install_plugins',
		// 	'priority'  => 50
		// );

		return $tabs;
	}

	/**
	 * Add Customizer dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_suite_dashboard_panel($panels) {
		// debug($panels);

		$url = esc_url( add_query_arg( 'return', urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), admin_url('customize.php') ) );

		array_push($panels, array(
			'icon'  => 'customizer',
			'title' => __('Customizer', 'greyd_hub'),
			'descr' => __("Define global styles for your website.", 'greyd_hub'),
			'btn'   => array(
				array( 'text' => _x("Open Customizer", 'small', 'greyd_hub'), 'url' => $url, 'icon' => 'external' )
			),
			'cap'       => 'edit_theme_options',
			'priority'  => 10,
		));

		return $panels;
	}

	/**
	 * Add Customizer and Posts/Pages items to Greyd.Suite adminbar group.
	 * @see filter 'greyd_admin_bar_group'
	 */
	public function add_customizer_admin_bar_item($items) {
		// debug($items);

		if ( current_user_can('edit_theme_options') ) {

			$url = esc_url( add_query_arg( 'return', urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), admin_url('customize.php') ) );

			// in backend
			if (is_admin() && !is_network_admin()) {

				$args = array(
					'id'    => 'customize',
					'title' => __('Customizer', 'greyd_hub'),
					'href'  => $url,
				);
				array_push( $items, $args );

			}

			// in frontend
			if ( !is_admin() ) {

				// change Customizer wording in adminbar
				global $wp_admin_bar;
				$customizer = $wp_admin_bar->get_node('customize');
				if ( is_object($customizer) ) {
					$wp_admin_bar->remove_node('customize');
					$customizer->title = __('Customizer', 'greyd_hub');
					array_unshift( $items, $customizer );
				}

				// add Customizer to Greyd.Suite parent group
				$args = array(
					'parent' => "greyd_toolbar",
					'id'     => 'customizer',
					'title'  => __('Customizer', 'greyd_hub'),
					'href'   => $url,
					'priority' => 1
				);
				array_push( $items, $args );

			}

		}

		return $items;
	}


	/*
	=======================================================================
		Greyd.Suite Settings
		(Contentbuilder, Analytics and Webshop)
	=======================================================================
	*/

	// default settings
	public static function get_defaults() {

		$defaults = array( 
			'builder' => 'vc-vc',
			'analytics' => array(
				'enable' => null,
				'code_head' => null,
				'code_body' => null,
				'track_admins' => null
			),
			'webshop' => array(
				'pagespeed' => 'true',
				'product_blocks' => 'false',
			)
		);

		return $defaults;
	}

	/**
	 * Add default settings
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_greyd_suite_setting($settings) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults()
		);

		return $settings;
	}

	/**
	 * Render settings
	 * @see filter 'greyd_settings_basic'
	 * 
	 * @param string $content   Content of all additional settings.
	 * @param string $mode      'site' | 'network_site' | 'network_admin'
	 * @param array $data       Current settings.
	 */
	public function render_greyd_suite_settings( $content, $mode, $data ) {

		if ( $mode == 'network_admin' ) return $content;

		ob_start();

		/**
		 * Contentbuilder setting.
		 * 
		 * @since 1.6.0
		 * Only render if this is a neither a forced nor pure blocks installation.
		 */
		$is_forced_greyd_blocks = defined('IS_GREYD_BLOCKS') && constant('IS_GREYD_BLOCKS');
		$is_pure_greyd_blocks = get_option('greyd_gutenberg') === true || get_option('greyd_gutenberg') == '1';
		if ( ! $is_forced_greyd_blocks && ! $is_pure_greyd_blocks ) {
			$builder = isset($data['site']['builder']) ? strval($data['site']['builder']) : 'vc-vc';
			echo "<table class='form-table'>";
	
				echo "<tr><th>".__("Select editor", 'greyd_hub')."<br>
					</th>
					<td>";
	
					$_val = 'vc-vc';
					echo "<label for='".$_val."'>
							<input type='radio' name='builder' value='".$_val."' id='".$_val."' ".($_val === $builder ? "checked='checked'" : "" )." onchange='greyd.backend.toggleRadioByClass(\"editor\", \"vc\")'  />
							<span>".__('WPBakery', 'greyd_hub')."</span><br>
							<small class='color_light'>".__("WPBakery was used as a content editor in older versions of the Greyd.Suite. Support for this editor will no longer be available in future versions.", 'greyd_hub')."</small>
						</label><br>";
					$_val = 'gg-gg';
					echo "<label for='".$_val."'>
							<input type='radio' name='builder' value='".$_val."' id='".$_val."' ".($_val === $builder ? "checked='checked'" : "" )." onchange='greyd.backend.toggleRadioByClass(\"editor\", \"gg\")' />
							<span>".__("Gutenberg Block Editor (recommended)", 'greyd_hub')."</span><br>
							<small class='color_light'>".__("Switch to the Block Editor now and benefit from a modern and intuitive user experience, outstanding performance and brand new developments.", 'greyd_hub')."</small>
						</label><br>";
	
					echo "<div class='editor_gg hidden'>";
					echo Helper::render_info_box(array(
						"style" => "warning",
						"text" =>   __("Switching to Gutenberg can change the display of your website in the frontend. Your content will be preserved. You can then gradually switch to the Block Editor.", 'greyd_hub')."<br><br>".
									__("However, if you want to start with a new Gutenberg installation, go to the Greyd.Suite dashboard, click on \"Reset Options\" in the \"Greyd.Wizard\" and reset your site - this way you start directly with Gutenberg content!", 'greyd_hub')
					));
					echo "</div>";
	
				echo "</td>
					</tr>";
	
			echo "</table>";
		}

		/**
		 * Analytics
		 */
		echo "<table class='form-table'>";
			echo "<tr>
					<th>".__('Analytics', 'greyd_hub')."</th>
					<td>";
						// enable checkbox
						$sel = isset($data['site']['analytics']['enable']) && $data['site']['analytics']['enable'] == 'true' ? "checked='checked'" : "";
						$toggle = 'document.querySelector(".toggle_analytics").classList.toggle("hidden")';
						echo "<label for='analytics_enable'>
							<input type='checkbox' id='analytics_enable' name='analytics_enable' ".$sel." onchange='".$toggle."'/>
							<span>".__("Integrate analytics and tracking codes", 'greyd_hub')."</span>
							<small class='color_light'>".__("Allows the integration of Google Analytics (or similar) tracking codes including the script tags to analyze visits to the site. The codes can be inserted into the page at the end of the head tag or at the end of the body tag.", 'greyd_hub')."</small>
						</label>";

						echo "<div class='toggle_analytics ".($c = $sel == "" ? "hidden" : "")."'>";
							// head textarea
							$val = isset($data['site']['analytics']['code_head']) ? urldecode($data['site']['analytics']['code_head']) : '';
							echo "<label>".sprintf(__("Script at the end of the %s tag", 'greyd_hub'), "&lt;head&gt;")."</label>
									<textarea name='analytics_code_head' id='analytics_code_head' rows='3' placeholder='".__("enter here", 'greyd_hub')."'>".$val."</textarea><br>";
							// body textarea
							$val = isset($data['site']['analytics']['code_body']) ? urldecode($data['site']['analytics']['code_body']) : '';
							echo "<label>".sprintf(__("Script at the end of the %s tag", 'greyd_hub'), "&lt;body&gt;")."</label>
									<textarea name='analytics_code_body' id='analytics_code_body' rows='3' placeholder='".__("enter here", 'greyd_hub')."'>".$val."</textarea><br>";
							// admin checkbox
							$sel = isset($data['site']['analytics']['track_admins']) && $data['site']['analytics']['track_admins'] == 'true' ? "checked='checked'" : "";
							echo "<label for='analytics_track_admins'>
									<input type='checkbox' id='analytics_track_admins' name='analytics_track_admins' ".$sel."/>
									<span>".__("Track administrators", 'greyd_hub')."</span><br>
									<small class='color_light'>".__("Visits by logged-in administrators are also analyzed and trigger a tracking event.", 'greyd_hub')."</small>
									</label>";
							// infobox
							echo Helper::render_info_box([
								"style" => "info",
								"text" => sprintf(
									__("Caution: If tracking codes that use cookies are included, remember to update your privacy policy and to integrate a cookie bar if necessary - we recommend the plugin %sBorlabs Cookie - Cookie Opt-In.%s", 'greyd_hub'),
									'<a href="https://de.borlabs.io/" target="_blank">', '</a>'
								)
							]);
					echo "</div>";
				echo "</td>
				</tr>";

		echo "</table>";

		/**
		 * Webshop
		 */
		if (class_exists('woocommerce')) {

			echo "<h2>".__('WooCommerce', 'greyd_hub')."</h2>";

			echo "<table class='form-table'>";

				echo "<tr>
						<th>".__('Webshop', 'greyd_hub')."</th>
						<td>";
							$sel = (isset($data['site']['webshop']['pagespeed']) && $data['site']['webshop']['pagespeed'] == "true") ? "checked='checked'" : "";
							echo "<label for='webshop_pagespeed'>
								<input type='checkbox' id='webshop_pagespeed' name='webshop_pagespeed' ".$sel."  onchange='greyd.backend.toggleElemByClass(\"woospeed\")'/>
								<span>".__("Enable WooCommerce page speed optimization", 'greyd_hub')."</span>
								<small class='color_light'>".__("WooCommerce scripts & stylings will only be loaded on the pages where they are needed.", 'greyd_hub')."</small>
							</label>";
							echo Helper::render_info_box([
								"style" => "info",
								"above" => _x("set up shop", 'small', 'greyd_hub'),
								"text" => sprintf(
									__("To set up your WooCommerce Shop, it is best to start %sthe Wizard%s and enable the \"Webshop\" option there. This automatically activates the required plugins and creates sample content. Alternatively, you can %sinstall the required plugins here%s.", 'greyd_hub'),
									'<a href='.admin_url('admin.php?page=greyd_dashboard&wizzard=settings&option=shop').' target="_blank">',
									'</a>',
									'<a href='.admin_url( 'plugins.php?page=install-required-plugins').' target="_blank">',
									'</a>'
								)
							]);
							if ( get_option('woocommerce_demo_store') === 'yes' ) { 
								echo "<div class='toggle_woospeed ".(isset($data['site']['webshop']['pagespeed']) && $data['site']['webshop']['pagespeed'] == "true" ? "" : "hidden")."'>";
								echo Helper::render_info_box([
									"style" => "warning",
									"text" => __("If page speed optimization is active, the shop message does not work correctly on all pages. Turn off the message before your page goes live.", 'greyd_hub')
								]);
								echo "</div>";
							}
							/**
							 * Block editor in products
							 * @since 1.1.0
							 */
							if ( is_greyd_blocks() ) {
								$sel = (isset($data['site']['webshop']['product_blocks']) && $data['site']['webshop']['product_blocks'] == "true") ? "checked='checked'" : "";
								echo "<label for='webshop_product_blocks'>
									<input type='checkbox' id='webshop_product_blocks' name='webshop_product_blocks' ".$sel."  onchange='greyd.backend.toggleElemByClass(\"wooproduct\")'/>
									<span>".__("Enable Block Editor for products", 'greyd_hub')."</span>
									<small class='color_light'>".__("Use the Block Editor to edit the content of your products and create individual product descriptions.", 'greyd_hub')."</small>
								</label>";
								echo "<div class='toggle_wooproduct ".(isset($data['site']['webshop']['product_blocks']) && $data['site']['webshop']['product_blocks'] == "true" ? "" : "hidden")."'>";
								echo Helper::render_info_box([
									"style" => "warning",
									"text" => __("Note: With this option, some other functions will not always work correctly when editing products. For example, you can only edit the short description in 'Text' mode.", 'greyd_hub')
								]);
								echo "</div>";
							}
					echo "</td>
					</tr>";

			echo "</table>";

		}

		$content .= ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Save settings
	 * @see filter 'greyd_settings_more_save'
	 * 
	 * @param array $site       Current site settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_greyd_suite_settings( $site, $defaults, $data ) {

		// builder
		$site['builder'] = isset($_POST['builder']) ? esc_attr($_POST['builder']) : $defaults['builder'];
		if (isset($site['builder'])) {
			if ($site['builder'] == "gg-gg") deactivate_plugins( array( "js_composer/js_composer.php" ) );
			else if ($site['builder'] == "vc-vc") deactivate_plugins( array( "gutenberg/gutenberg.php" ) );
		}

		// analytics
		$site['analytics'] = [];
		foreach ((array) $defaults['analytics'] as $key => $value) {
			// convert checkboxes to true | false
			if ($key === 'enable' || $key === 'track_admins' ) $post_value = isset($_POST['analytics_'.$key]) ? 'true' : 'false';
			else $post_value = isset($_POST['analytics_'.$key]) ? urlencode(stripslashes($_POST['analytics_'.$key])) : $value;
			$site['analytics'][$key] = $post_value;
		}

		// webshop
		if (!isset($site['webshop'])) $site['webshop'] = [];
		$site['webshop']['pagespeed'] = isset($_POST['webshop_pagespeed']) ? 'true' : 'false';
		$site['webshop']['product_blocks'] = isset($_POST['webshop_product_blocks']) ? 'true' : 'false';

		return $site;
	}

}