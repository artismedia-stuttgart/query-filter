<?php
/**
 * Add the license admin page.
 */
namespace Greyd;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new License_Admin_Page ( $config );
class License_Admin_Page {

	/**
	 * Holds plugin config array
	 */
	private $config;

	/**
	 * Holds general arguments (slug, title, labels...)
	 */
	private $args;

	/**
	 * Holds the saved license.
	 */
	private $license;

	/**
	 * Default license args.
	 */
	private $default_license = array(
		'licenseKey' => '',
		'status'     => 'empty',
		'policy'     => '',
		'frozen'     => false,
	);

	/**
	 * Holds the decrypted license key, saved as a transient.
	 *
	 * @since 1.2.5
	 */
	private $licenseKey;

	/**
	 * After how many seconds the license transient expires
	 */
	private $expiration = 4 * 60;

	/**
	 * Class constructor
	 */
	public function __construct( $config ) {
		// set config
		$this->config = (object) $config;

		add_action( 'after_setup_theme', array( $this, 'init' ) );
	}

	/**
	 * Init the class arguments
	 */
	public function init() {

		if ( !is_admin() ) return;

		// set tax
		$this->args = (object) array(
			'option'        => 'gtpl',
			'parent_slug'   => 'greyd_settings',
			'slug'          => 'greyd_settings_license',
			'title'         => 'Greyd.Suite '.__( "License", 'greyd_hub' ),
			'menu_title'    => __( "License", 'greyd_hub' ),
			'url'           => admin_url( 'admin.php?page=greyd_settings_license' ),
			'capability'    => 'manage_options',
			'position'      => 80,
			'shop'          => array(
				'url'  => __( $this->config->pricing, 'greyd_hub' ),
				'text' => __( "Buy a new license", 'greyd_hub' ),
				'link' => '',
			),
			'version_paths' => array(
				'plugin'  => array(
					'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd-plugin/versions/greyd-plugin',
					'text' => __( "Greyd Plugin", 'greyd_hub' ),
				),
				'forms'   => array(
					'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_forms/versions/greyd_tp_forms',
					'text' => __( "Greyd.Forms", 'greyd_hub' ),
				),
				'theme'   => array(
					'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/themes/greyd-theme/versions/greyd-theme',
					'text' => __( "Greyd Theme", 'greyd_hub' ),
				),
				'classic' => array(
					'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/themes/greyd_suite/versions/greyd_suite',
					'text' => __( "Greyd Classic Theme", 'greyd_hub' ),
				),
				// deprecated
				// 'blocks'  => array(
				// 	'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_blocks/versions/greyd_blocks',
				// 	'text' => __( "Greyd Blocks", 'greyd_hub' ),
				// ),
				// 'hub'     => array(
				// 	'path' => 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_hub/versions/greyd-plugin',
				// 	'text' => __( "Greyd.Hub", 'greyd_hub' ),
				// ),
			),
		);
		$this->args->shop['link'] = '<a href="'.$this->args->shop['url'].'" target="_blank">'.$this->args->shop['text'].'</a>';

		// add menu and pages
		add_filter( 'greyd_submenu_pages', array( $this, 'add_greyd_submenu_page' ) );
		add_filter( 'greyd_dashboard_tabs', array( $this, 'add_greyd_dashboard_tab' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ), 40 );

		// render
		add_action( 'admin_init', array( $this, 'init_option' ) );
		add_action( 'admin_init', array( $this, 'add_license_settings' ) );
	}


	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add the submenu item to Greyd.Suite
	 *
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page( $submenu_pages ) {
		// debug($submenu_pages);

		array_push(
			$submenu_pages,
			array(
				'page_title' => $this->args->title,
				'menu_title' => $this->args->menu_title,
				'cap'        => $this->args->capability,
				'slug'       => $this->args->slug,
				'callback'   => array( $this, 'render_license_page' ),
				'position'   => 50, // 90
			)
		);

		return $submenu_pages;
	}

	/**
	 * Add dashboard tab
	 *
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab( $tabs ) {
		// debug($tabs);

		array_push(
			$tabs,
			array(
				'title'    => $this->args->menu_title,
				'slug'     => $this->args->slug,
				'url'      => $this->args->url,
				'cap'      => $this->args->capability,
				'priority' => 50, // 99
			)
		);

		return $tabs;
	}

	/**
	 * add scripts
	 */
	public function load_backend_scripts() {

		if ( !isset( $_GET['page'] ) || $_GET['page'] !== $this->args->slug ) return;

		$uri = plugin_dir_url( __FILE__ ) . 'assets';

		// css
		wp_register_style(
			$this->config->plugin_name.'_license_css',
			$uri.'/css/admin-style.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style( $this->config->plugin_name.'_license_css' );

		// js
		wp_register_script(
			$this->config->plugin_name.'_license_js',
			$uri.'/js/admin-script.js',
			array( 'jquery', "greyd-admin-script" ),
			GREYD_VERSION
		);
		wp_localize_script(
			$this->config->plugin_name.'_license_js',
			'page_details',
			array(
				'domain'  => explode( '://', get_site_url(), 2 )[1],
				'version' => GREYD_VERSION,
			)
		);
		wp_enqueue_script( $this->config->plugin_name.'_license_js' );

	}


	/**
	 * =================================================================
	 *                          REGISTER
	 * =================================================================
	 */

	/**
	 * Init the license option
	 */
	public function init_option() {

		// debug
		// delete_transient( 'greyd_suite_license_key' );
		// delete_option( $this->args->option );

		$option = get_option( $this->args->option );
		// debug( $option, true );

		if ( $option && isset( $option['licenseKey'] ) && base64_encode( base64_decode( $option['licenseKey'] ) ) === $option['licenseKey'] && strpos( $option['licenseKey'], 'GSl' ) !== 0 ) {
			$option['licenseKey'] = base64_decode( $option['licenseKey'] );
		}

		if ( $option && is_array( $option ) ) {

			$this->license = wp_parse_args(
				array(
					'licenseKey' => isset( $option['licenseKey'] ) ? $option['licenseKey'] : '',
					'status'     => isset( $option['status'] ) ? $option['status'] : 'empty',
				),
				$this->default_license
			);

			// get decrypted licenseKey
			if ( $licenseKey = get_transient( 'greyd_suite_license_key' ) ) {
				$this->licenseKey = $licenseKey;
			}
		}
		else {
			$this->license = $this->default_license;
		}

		if ( \defined( 'GREYD_SUITE_LICENSE_KEY' ) ) {
			$this->license['licenseKey'] = GREYD_SUITE_LICENSE_KEY;
		}
	}

	/**
	 * Properly initialize the license settings.
	 */
	public function add_license_settings() {

		// Create Setting
		register_setting(
			$this->args->slug, // option group
			$this->args->option, // option name
			array( $this, 'save_license' ) // sanitize callback
		);

		// Add Section
		add_settings_section(
			$this->args->option, // id
			null, // title
			null, // callback
			$this->args->slug // page slug
		);

		// Add Field
		add_settings_field(
			$this->args->option.'_key', // id
			__( "License key", 'greyd_hub' ), // title
			array( $this, 'render_license_key' ), // callback
			$this->args->slug, // page slug
			$this->args->option // section
		);
	}

	/**
	 * Render the license settings page
	 */
	public function render_license_page() {


		echo '<div class="wrap settings_wrap">';

		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

			do_action( 'greyd_validation_error' );

			echo '<form method="post" action="options.php" class="'.$this->args->slug.'">';
				settings_fields( $this->args->slug );
				do_settings_sections( $this->args->slug );

				// submit button
				$text    = __( "Save key", 'greyd_hub' );
				$classes = 'primary large';
				if ( empty( $this->license['licenseKey'] ) ) {
					$classes .= ' right';
				}
				if ( empty( $this->licenseKey ) ) {
					$classes .= ' hidden';
				}
				submit_button( $text, $classes );
			echo '</form>';

		echo '</div>';

		// add overlay contents
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );
	}


	/**
	 * =================================================================
	 *                          RENDER
	 * =================================================================
	 */

	/**
	 * Render the option (actually renders a lot more...)
	 */
	public function render_license_key() {

		// Policies
		$policies = array(
			'Basic'      => array(
				'label'   => __( "Single", 'greyd_hub' ),
				'title'   => __( "Single license", 'greyd_hub' ),
				'desc'    => __( "With this license you can activate one domain.", 'greyd_hub' ),
				'updates' => __( "Updates and domain activations are available for one year.", 'greyd_hub' ),
			),
			'Growth'  => array(
				'label'   => __( "Growth", 'greyd_hub' ),
				'title'   => __( "Growth license", 'greyd_hub' ),
				'desc'    => __( "With this license you can activate three domains per month.", 'greyd_hub' ),
				'updates' => __( "Updates and new domain activations are available for the entire runtime.", 'greyd_hub' ),
			),
			'Agency'     => array(
				'label'   => __( "Scale", 'greyd_hub' ),
				'title'   => __( "Scale license", 'greyd_hub' ),
				'desc'    => __( "With this license you can activate any number of domains.", 'greyd_hub' ),
				'updates' => __( "Updates and new domain activations are available for the entire runtime.", 'greyd_hub' ),
			),
			'Freelancer' => array(
				'label'   => __( "Starter", 'greyd_hub' ),
				'title'   => __( "Starter license", 'greyd_hub' ),
				'desc'    => __( "With this license, you can activate one domain per month.", 'greyd_hub' ),
				'updates' => __( "Updates and new domain activations are available for the entire runtime.", 'greyd_hub' ),
			),
		);

		// default args
		$domain_status = preg_replace( '/^_/', '', esc_attr( $this->license['status'] ) );
		$domain        = explode( '://', get_site_url(), 2 )[1];

		// Namings
		$option   = $this->args->option;
		$max_text = "<span class='{$option}_max'></span><span class='{$option}_maxUsesTotal'></span><sup class='{$option}_bonus'></sup> <span class='{$option}_max_name'></span>";

		// -------------- SCRIPT VARS ----------------
		echo "<script>
			var machine_singular = '".__( 'Domain', 'greyd_hub' )."',
				machine_plural = '".__( 'Domains', 'greyd_hub' )."',
				option_name = '$option',
				form_name = '".$this->args->slug."';" .
		'</script>';

		// -------------- KEY ----------------
		$value       = '';
		$placeholder = __( "Enter license key", 'greyd_hub' );
		if ( !empty( $this->licenseKey ) ) {
			$value = $this->licenseKey;
		}
		elseif ( !empty( $this->license['licenseKey'] ) ) {
			$placeholder = '•••• •••• •••• •••• •••• •••• •••• •••• ••••';
		}
		echo "<div class='input_wrapper'>
			<input type='text' id='{$option}_key' name='{$option}[key]' value='$value' placeholder='{$placeholder}' class='' /><span class='loader small'></span>
		</div>";

		// -------------- HIDDEN INPUTS ----------------

		// basic license values
		echo "<input type='hidden' id='{$option}_key_encrypted' name='{$option}_key_encrypted' value='{$this->license['licenseKey']}'/>";
		echo "<input type='hidden' id='{$option}_domain_status' name='{$option}_domain_status' value='{$domain_status}'/>";

		// -------------- STATES ----------------
		echo "<div id='states' class=''>";

		// -------------- empty ----------------
		echo "<div class='state empty'>
			<p class='info_text'>" .
				sprintf( __( 'Have you tried Greyd.Suite in the free trial version and now want to publish your site without banners? Then get <a href="%s" target="_blank">the right license package in our shop</a> now', 'greyd_hub' ), $this->args->shop['url'] ) .
			'</p>
		</div>';

		// -------------- not_found ----------------
		echo "<div class='state not_found'>" .
			Helper::render_info_box(
				array(
					'style' => 'alert',
					'text'  => __( "The license key you entered could not be found in our system.", 'greyd_hub' ),
				)
			) .
		'</div>';

		// -------------- invalid (Keygen default) ----------------
		echo "<div class='state invalid'>" .
			Helper::render_info_box(
				array(
					'style' => 'alert',
					'text'  => sprintf(
						__( "The license key entered is invalid or expired. (Error message: %s)", 'greyd_hub' ),
						'<code class="error_text">Unknown</code>'
					),
				)
			) .
		'</div>';

		// -------------- version_invalid ----------------
		echo "<div class='state version_invalid'>";

			$paths = $this->args->version_paths;
			echo Helper::render_info_box(
				array(
					'style' => 'red',
					'text'  => sprintf(
						__( 'This license has expired and is no longer valid for your current Greyd.Suite version. You can download %1$s or the older theme & plugin versions: %2$s', 'greyd_hub' ),
						$this->args->shop['link'],
						'<p class="download_links"><strong>'.sprintf( __( 'Version %s:', 'greyd_hub' ), '<span class="'.$option.'_version"></span>' ).'</strong> '.implode( ' | ', array_map( function( $path ) {
							return '<a href="'.$path['path'].'" target="_blank">'.$path['text'].'</a>';
						}, $paths )).'</p>'
					),
				)
			).'
		</div>';

		// -------------- limit_open (+monthly) ----------------
		echo "<div class='state limit_open limit_open_monthly''>
			<div class='isnotfrozen hidden'>" .
				Helper::render_info_box(
					array(
						'style' => 'green',
						'class' => 'default',
						'text'  => sprintf( __( "The license key you entered is valid.", 'greyd_hub' ), $domain ),
					)
				) .
				Helper::render_info_box(
					array(
						'style' => 'orange',
						'class' => 'default',
						'text'  => sprintf( __( "The license is not yet activated on this domain (%s).", 'greyd_hub' ), $domain ),
					)
				) .
				// <div class='greyd_info_box red not_activated'>
				// <span class='dashicons dashicons-dismiss'></span>
				// <div><p>".
				// __("Die Domain konnte nicht aktiviert werden. Bitte versuche es erneut.",'greyd_hub').
				// "</p></div>
				// </div>
				"<span class='button button-primary large' onclick='greyd.licenseAdmin.activateDomain()'>" .
						__( "Activate license on this domain", 'greyd_hub' ) .
				"</span>
				<p class='info_text'>" .
					__( "By activating your license on this domain, you can publish your website without a banner. You can undo or overwrite the activation at any time.", 'greyd_hub' ) .
				'</p>
			</div>';
			// -------------- frozen
			echo "<div class='isfrozen hidden'>" .
				Helper::render_info_box(
					array(
						'style' => 'orange',
						'text'  => sprintf( __( "New domains can no longer be activated for this license. To license Greyd.Suite on this page, you can use %s.", 'greyd_hub' ), $this->args->shop['link'] ),
					)
				) .
			'</div>
		</div>';

		// -------------- limit_reached ----------------
		echo "<div class='state limit_reached limit_reached_monthly'>
			<div class='isnotfrozen hidden'>" .
				Helper::render_info_box(
					array(
						'style' => 'orange',
						'text'  => sprintf(
							__( 'The limit of this license is reached (maximum %1$s). To license Greyd.Suite on this domain, you can overwrite another domain or %2$s.', 'greyd_hub' ),
							$max_text,
							$this->args->shop['link']
						),
					)
				);
		if ( !is_multisite() || is_super_admin() ) {
			echo "<div class='flex' style='margin:1em 0 2em'>
						<div class='flex flex-vertical' style='flex:2;padding-right:2em;border-right: 1px solid #868A8F;'>
							<h3>".__( "Overwrite another domain", 'greyd_hub' )."</h3>
							<label for='domains'>".__( "Select domain", 'greyd_hub' )."</label>
							<select name='greyd_active_domains' onchange='greyd.licenseAdmin.selectChanged(this)'>
								<option selected value=''>".__( "Please select", 'greyd_hub' ).'</option>';
					echo "</select>
							<span class='button' role='overwrite' disabled onclick='greyd.licenseAdmin.overwriteDomain(this)'>".sprintf( __( "Overwrite with \"%s\"", 'greyd_hub' ), $domain )."</span>
							<p class='info_text'>" .
						__( "If you disable a domain, the license will be available to you again for a different domain. Don't worry, the deactivated domain remains 100% intact - only the banner of the free test version will appear again.", 'greyd_hub' ) .
					"</p>
						</div>
						<div class='flex flex-vertical' style='flex:2;padding-left:2em;'>
							<h3>".__( "Buy a license", 'greyd_hub' )."</h3>
							<a class='button large' href='".$this->args->shop['url']."' target='_blank' style='margin:0;'>".__( "Buy a new license", 'greyd_hub' )." <span class='dashicons dashicons-external'></span></a>
						</div>
					</div>";
		}
			echo '</div>';
			// -------------- frozen
			echo "<div class='isfrozen hidden'>" .
				Helper::render_info_box(
					array(
						'style' => 'orange',
						'text'  => sprintf(
							__( 'The limit of this license has been reached (maximum %1$s) and no new domains can be activated. To license Greyd.Suite on this domain, you can %2$s.', 'greyd_hub' ),
							$max_text,
							$this->args->shop['link']
						),
					)
				);
			echo '</div>
		</div>';

		// -------------- is_valid ----------------
		echo "<div class='state is_valid'>
			<div class='greyd_info_box green'>
				<span class='dashicons dashicons-yes' style='margin-right:-12px;'></span>
				<span class='dashicons dashicons-yes'></span>
				<div><p>" .
					__( "The license key you entered is valid and activated on this domain.", 'greyd_hub' ) .
				"</p></div>
			</div>
			<div class='verified hidden'>
				<div class='greyd_info_box'>
					<span class='dashicons dashicons-info'></span>
					<div><p>
						".__( "Your license key is stored encrypted for security reasons. It is only available for editing for a short time - afterwards you have to enter it again.", 'greyd_hub' ).'
					</p></div>
				</div>
			</div>
		</div>';

		// -------------- error ----------------
		echo "<div class='state _error'>" .
			Helper::render_info_box(
				array(
					'style' => 'alert',
					'text'  => __( "Something must have gone wrong. Please check your internet connection and try again. If it doesn't work, please contact an administrator.", 'greyd_hub' ),
				)
			) .
		'</div>';

		echo '</div>';

		// -------------- Encryption ----------------
		echo "<div class='unverified hidden'>
			<div class='greyd_info_box'>
				<span class='dashicons dashicons-info'></span>
				<div><p>
				".__( "Your license key is stored encrypted for security reasons. To manage your license, you need to enter the key again.", 'greyd_hub' ).'
				</p></div>
			</div>
		</div>';

		// -------------- Constant support ----------------
		if ( \defined( 'GREYD_SUITE_LICENSE_KEY' ) ) {
			echo "<div class='greyd_info_box'>
				<span class='dashicons dashicons-info'></span>
				<div><p>
					".sprintf(
						__( 'Your license key was set with the constant %s.', 'greyd_hub' ),
						'<code>GREYD_SUITE_LICENSE_KEY</code>'
					).'
				</p></div>
			</div>';
		}

		// -------------- INFOS ----------------

		echo "</td></tr>
			<tr class='license_infos license_meta hidden' data-policy=''>
				<th scope='row'>".__( "Information", 'greyd_hub' ).'</th>
				<td>';

		echo '<table class="greyd_table vertical">
				<tr>
					<th>
						'.__( "License type", 'greyd_hub' ).'
					</th>
					<td>' .
						'<span class="'.$option.'_policy"></span>&nbsp;';
				foreach ( $policies as $name => $infos ) {
					echo '<span class="greyd_popup_wrapper greyd_policy_'.$name.'">' .
						'<span class="toggle dashicons dashicons-info"></span>' .
						'<span class="popup">
							<b>'.$infos['title'].'</b>
							<small>' .
								$infos['desc'] .
								'<br>'.$infos['updates'] .
								/**
								 * @todo extend date
								 */
								// ( empty($extendDate) ? '' : '<br>'.sprintf( __('Diese Lizenz wurde verlängert sie ist über den %s heraus noch 1 weiteres Jahr gültig.', 'greyd_hub'), $extendDate ) ).
							'</small>' .
						'</span>' .
					'</span>';
				}
					echo '</td>
				</tr>
				<tr>
					<th>
						'.__( "In use since", 'greyd_hub' ).'
					</th>
					<td class="'.$option.'_created"></td>
				</tr>
				<tr>
					<th>
						'.__( 'Domains', 'greyd_hub' ).'
					</th>
					<td class="">
						<span class="'.$option.'_count"></span>&nbsp;'.__( "of", 'greyd_hub' ).'&nbsp;'.$max_text.'
						'.__( "Active", 'greyd_hub' ).'
					</td>
				</tr>
				<tr>
					<th>
						'.__( "Updates & domain activations", 'greyd_hub' ).'
					</th>
					<td>
						<div class="greyd_policy_Basic greyd_policy_Bundle">
							<div class="isnotfrozen hidden">
								'.sprintf( __( 'until %s', 'greyd_hub' ), '<span class="'.$option.'_expire"></span>' ).'
							</div>
							<div class="isfrozen hidden">
								<div class="greyd_info_box red" style="margin-bottom:0;">'.__( "Expired", 'greyd_hub' ).'</div>
							</div>
						</div>
						<div class="greyd_policy_Corporate greyd_policy_Agency greyd_policy_Freelancer">
							<div class="isnotfrozen hidden">
								<div class="greyd_info_box green" style="margin-bottom:0;">'.__( "Active", 'greyd_hub' ).'</div>
							</div>
							<div class="isfrozen hidden">
								<div class="greyd_info_box red" style="margin-bottom:0;">'.__( "Expired", 'greyd_hub' ).'</div>
							</div>
						</div>
					</td>
				</tr>';
		echo '</table>';

		// -------------- MANAGE ----------------

		if ( !is_multisite() || is_super_admin() ) {

			// new table row
			echo "</td></tr>
				<tr class='license_infos domain_infos hidden'>
					<th scope='row'>".__( "Manage domains", 'greyd_hub' ).'</th>
					<td>';

			echo '<table class="greyd_table greyd_active_domains">
					<tr>
						<th>
							'.__( 'Domain', 'greyd_hub' ).'
						</th>
						<th colspan="2">
							'.__( "Activated on", 'greyd_hub' ).'
						</th>
					</tr>';
						echo '<tr class="greyd_active_domain hidden">
								<td class="greyd_active_domain_name"></td>
								<td class="greyd_active_domain_created"></td>
								<td style="text-align:right;" class="isnotfrozen hidden">
									<span class="button" role="delete" data-id="" data-fingerprint="" onclick="greyd.licenseAdmin.deactivateDomain(this)">'.__( "Disable", 'greyd_hub' ).'</span>
								</td>
							</tr>';
					echo '<tr>
						<th colspan="3">' .
							__( "By disabling your license for a domain, Greyd.Suite will be reset to the free trial version with banner.", 'greyd_hub' ) .
						'</th>
					</tr>
				</table>';
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
			'activate'  => array(
				'loading' => array(
					'descr' => __( "This domain will be activated for your license.", 'greyd_hub' ),
				),
				'success' => array(
					'title' => __( "Activation successful", 'greyd_hub' ),
					'descr' => __( "This domain is activated for your license.", 'greyd_hub' ),
				),
				'fail'    => array(
					'title' => __( "Activation failed", 'greyd_hub' ),
					'descr' => __( "The domain could not be activated.", 'greyd_hub' ),
				),
			),
			'delete'    => array(
				'confirm' => array(
					'title'  => __( "Are you sure?", 'greyd_hub' ),
					'descr'  => sprintf(
						__( "By disabling the domain \"%s\", Greyd.Suite will be reset to the free trial version with banner.", 'greyd_hub' ),
						'<b class="replace"></b>'
					),
					'button' => __( "Disable domain", 'greyd_hub' ),
				),
				'loading' => array(
					'descr' => __( "Disabling domain.", 'greyd_hub' ),
				),
				'success' => array(
					'title' => __( "Deactivation successful", 'greyd_hub' ),
					'descr' => __( "The domain is disabled.", 'greyd_hub' ),
				),
				'fail'    => array(
					'title' => __( "Deactivation failed", 'greyd_hub' ),
					'descr' => __( "The domain could not be disabled.", 'greyd_hub' ),
				),
			),
			'overwrite' => array(
				'confirm' => array(
					'title'  => __( "Are you sure?", 'greyd_hub' ),
					'descr'  => sprintf(
						__( 'By overwriting the domain "%s", Greyd.Suite will be set back there to the free trial version with banner.', 'greyd_hub' ),
						'<b class="replace"></b>'
					),
					'button' => __( "Overwrite domain", 'greyd_hub' ),
				),
				'loading' => array(
					'descr' => __( "Overwriting domain.", 'greyd_hub' ),
				),
				'success' => array(
					'title' => __( "Overwrite successful", 'greyd_hub' ),
					'descr' => __( "This domain is now activated for your license instead of the previous domain.", 'greyd_hub' ),
				),
				'fail'    => array(
					'title' => __( "Overwrite failed", 'greyd_hub' ),
					'descr' => __( "The domain could not be overwritten.", 'greyd_hub' ),
				),
			),
		);

		return array_merge(
			$contents,
			$overlay_contents
		);
	}


	/**
	 * =================================================================
	 *                          SAVE
	 * =================================================================
	 */

	public static $cache = null;

	/**
	 * Sanitize license key and save the option.
	 *
	 * @param array $values Submitted form values.
	 *
	 * @return bool|array Encrypted license array:
	 *      @property string licenseKey
	 *      @property string status
	 */
	public function save_license( $values ) {

		// Detect multiple sanitizing passes.
		// Workaround for: https://core.trac.wordpress.org/ticket/21989
		if ( self::$cache !== null ) {
			// return license saved in class var
			return $this->license;
		}
		self::$cache = true;

		delete_transient( 'greyd_suite_license_key' );

		// if empty
		if ( empty( $values['key'] ) ) {
			return $this->default_license;
		}

		// Vars
		$key     = sanitize_text_field( $values['key'] );
		$domain  = explode( '://', get_site_url(), 2 )[1];

		// Check the license key
		$license = $this->get_license( $key, $domain, GREYD_VERSION );

		// Server Error
		if ( !$license ) {
			add_settings_error( 'greyd_license_save', 'greyd_license_save', __( "Failed to save license.", 'greyd_hub' ), 'error' );
			return array();
		}

		return $this->license = $license;
	}

	/**
	 * Get the license by key.
	 *
	 * This saves the decrypted license key as a transient for 1 hour.
	 *
	 * @param string $key       Decrypted license key.
	 * @param string $domain    Current site url.
	 * @param string $version   Current SUITE version.
	 *
	 * @return bool|array Encrypted license array:
	 *      @property string licenseKey
	 *      @property string status
	 */
	private function get_license( $key, $domain, $version ) {

		$result = wp_remote_post(
			'https://update.greyd.io/license/api/v2/get/',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => json_encode(
					array(
						'key'         => $key,
						'fingerprint' => $domain,
						'version'     => $version,
					),
					true
				),
			)
		);
		// debug( $result );

		if ( is_wp_error( $result ) ) return false;

		try {
			$response = json_decode( $result['body'], true );

			// get reduced license to save as the option
			if (
				isset( $response['message'] ) &&
				isset( $response['message']['status'] ) &&
				isset( $response['key'] ) &&
				isset( $response['data'] ) &&
				isset( $response['data']['license'] ) &&
				isset( $response['data']['license']['policy'] )
			) {
				$final = array(
					'licenseKey' => $response['key'],
					'status'     => $response['message']['status'],
					'policy'     => $response['data']['license']['policy'],
					'frozen'     => isset($response['data']['license']['frozen']) ? $response['data']['license']['frozen'] : false,
				);

				// save decrypted license key as transient
				set_transient( 'greyd_suite_license_key', $key, $this->expiration );
			}
			else {
				return false;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return $final;
	}
}
