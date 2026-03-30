<?php
/*
Feature Name:   SMTP Extension
Description:    Send all your WordPress E-Mails via your own SMTP server.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
*/

/**
 * SMTP Mailer configuration
 * 
 * @since 1.3.6
 */
namespace Greyd\Extensions;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if( ! defined( 'ABSPATH' ) ) exit;

// escape if User Features are present
// if (class_exists("Greyd\User\Manage")) return;

/**
 * disable if plugin wants to run standalone
 */
if ( !class_exists( "Greyd\Admin" ) ) {
	// reject activation
	if (!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die(sprintf("%s can not be activated as standalone Plugin.", $plugin_name));
}
	
new SMTP();
class SMTP {

	/**
	 * Constructor
	 */
	public function __construct() {

		// add settings
		add_filter( 'greyd_settings_default_site', array($this, 'add_setting') );
		add_filter( 'greyd_settings_default_global', array($this, 'add_setting') );
		// save settings
		add_filter( 'greyd_settings_more', array($this, 'render_settings'), 10, 3 );
		add_filter( 'greyd_settings_more_save', array($this, 'save_settings'), 10, 3 );
		add_filter( 'greyd_settings_more_global_save', array($this, 'save_settings'), 10, 3 );

		// display info in forms settings
		add_action( 'formsettings_interfaces', array($this, 'register_forms_setting'), 1 );
		
		// modify the mailer
		add_action( 'phpmailer_init', array($this, 'modify_phpmailer') );
	}

	/*
	=================================================================
		SETTINGS
	=================================================================
	*/

	/**
	 * Get default settings
	 */
	public static function get_defaults() {

		$defaults = array( 
			'smtp' => array(
				'enable' => false,
				'host' => '',
				'port' => 587,
				'auth' => false,
				'username' => '',
				'password' => '',
				'encryption' => false
			)
		);

		return $defaults;
	}

	/**
	 * Add default settings
	 * @see filter 'greyd_settings_default_site'
	 * @see filter 'greyd_settings_default_global'
	 */
	public function add_setting($settings) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults()
		);

		return $settings;
	}


	/**
	 * Render the settings
	 * 
	 * @param string $content   Content of all additional settings.
	 * @param string $mode      'site' | 'network_site' | 'network_admin'
	 * @param array $data       Current settings.
	 * 
	 */
	public function render_settings( $content, $mode, $data ) {
	
		$defaults = self::get_defaults();
		$settings = $mode == "network_admin" ? $data["global"]['smtp'] : $data["site"]['smtp'];

		list( $enable, $host, $port, $auth, $username, $password, $encryption ) = array_values($settings);

		$content .= "
		<table class='form-table'>
			<tr>
				<th>".__("SMTP settings", 'greyd_hub')."</th>
				<td>
					<label for='smtp[enable]'>
					<input type='checkbox' id='smtp[enable]' name='smtp[enable]' ".( $enable ? "checked='checked'" : "" )." onchange='document.querySelector(\".toggle_smtp\").classList.toggle(\"hidden\")'/>

						<span>".__("Activate SMTP support", 'greyd_hub')."</span><br>
						<small class='color_light'>".__("Configure an SMTP server to send emails via a verified address - and thus no longer end up in the spam folder.", 'greyd_hub')."</small>
					</label><br>
					
					<div class='toggle_smtp ".( $enable ? "" : "hidden" )."'>

						<label>".__('SMTP host', 'greyd_hub')."</label><br>
						<input type='text' id='smtp[host]' name='smtp[host]' value='{$host}' class='large-text'/><br><br>

						<label>".__('SMTP port', 'greyd_hub')."</label><br>
						<input type='number' id='smtp[port]' name='smtp[port]' value='{$port}' placeholder='{$defaults['smtp']['port']}' step='1' min='0' max='65535' class='regular-text'/><br><br>

						<label>
							<input type='checkbox' id='smtp[auth]' name='smtp[auth]' ".( $auth ? "checked='checked'" : "" )." onchange='document.querySelector(\".toggle_smtp_auth\").classList.toggle(\"hidden\")'/>
							".__("Activate authentification", 'greyd_hub')."
						</label><br><br>

						<div class='toggle_smtp_auth ".( $auth ? "" : "hidden" )."'>
							<label>".__('Username', 'greyd_hub')."</label><br>
							<input type='text' id='smtp[username]' name='smtp[username]' value='{$username}' class='regular-text'/><br><br>

							<label>".__("Password", 'greyd_hub')."</label><br>
							<input type='password' id='smtp[password]' name='smtp[password]' value='{$password}' class='regular-text'/><br><br>
						</div>

						<span>".__("Encryption", 'greyd_hub')."</span><br><br>
						<label for='smtp[encryption]-false' style='margin-right: 10px !important;'>
								<input type='radio' name='smtp[encryption]' value='false' id='smtp[encryption]-false' ".( false == $encryption ? "checked='checked'" : "" )." '/>
								<span>".__("None", 'greyd_hub')."</span><br>
						</label>
						<label for='smtp[encryption]-ssl' style='margin-right: 10px !important;'>
								<input type='radio' name='smtp[encryption]' value='ssl' id='smtp[encryption]-ssl' ".("ssl" === $encryption ? "checked='checked'" : "" )." '/>
								<span>".__("SSL", 'greyd_hub')."</span><br>
						</label>
						<label for='smtp[encryption]-tls'>
								<input type='radio' name='smtp[encryption]' value='tls' id='smtp[encryption]-tls' ".("tls" === $encryption ? "checked='checked'" : "" )." '/>
								<span>".__("TLS", 'greyd_hub')."</span><br>
						</label><br>
		 

					</div>


				</td>
			</tr>
		</table>";
		return $content;
	}

	/**
	 * Save site/global settings
	 * @see filter 'greyd_settings_more_save'
	 * @see filter 'greyd_settings_more_global_save'
	 * 
	 * @param array $site       Current site/global settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_settings( $site, $defaults, $data ) {

		if ( isset($data['smtp']) ) {
			// make new settings
			$site['smtp'] = array(
				'enable' => isset($data['smtp']['enable']) && $data['smtp']['enable'] === 'on' ? true : $defaults['smtp']['enable'],
				'host' => isset($data['smtp']['host']) ? esc_attr($data['smtp']['host']) : $defaults['smtp']['host'],
				'port' => isset($data['smtp']['port']) ? esc_attr($data['smtp']['port']) : $defaults['smtp']['port'],
				'auth' => isset($data['smtp']['auth']) && $data['smtp']['auth'] === 'on' ? true : $defaults['smtp']['auth'],
				'username' => isset($data['smtp']['username']) ? esc_attr($data['smtp']['username']) : $defaults['smtp']['username'],
				'password' => isset($data['smtp']['password']) ? esc_attr($data['smtp']['password']) : $defaults['smtp']['password'],
				'encryption' => isset($data['smtp']['encryption']) ? esc_attr($data['smtp']['encryption']) : $defaults['smtp']['encryption'],
			);
		

		}
		return $site;
	}


	/*
	=================================================================
		FORMS SETTINGS
	=================================================================
	*/

	/**
	 * Register a settings section for the Greyd.Forms settings.
	 */
	public function register_forms_setting( $pre ) {
		
		$section = $pre.'_smtp';
		
		// add section
		add_settings_section(
			$section, // id of the section
			__("SMTP Settings", 'greyd_hub'), // title to be displayed
			function(){ echo __("Configure an SMTP server to send emails via a verified address - and thus no longer end up in the spam folder.", 'greyd_hub'); }, // add section description function here
			$pre // page on which to display the section
		);
		
		// register setting
		register_setting( $pre, $section, null );
		
		// add setting
		add_settings_field(
			$section, // id of the settings field
			__('SMTP'), // title
			array($this, 'render_forms_setting'), // callback function
			$pre, // page on which settings display
			$section // section on which to show settings
		);
		
	}

	/**
	 * Display the Greyd.Forms settings section with a link to the actual setting.
	 */
	public function render_forms_setting() {
		echo Helper::render_info_box( array(
			'text' => sprintf(
				__("You can configure the SMTP server in the general settings: %s", 'greyd_hub'),
				"<a href=".admin_url( 'admin.php?page=greyd_settings#smtp[enable]' ).">".__("Open settings", "greyd_hub")."</a>"
			)
		) );
	}


	/*
	=================================================================
		PHP Mailer
	=================================================================
	*/

	/**
	 * Modify the PHP Mailer.
	 * @see https://developer.wordpress.org/reference/hooks/phpmailer_init/
	 * 
	 * @param PHPMailer $phpmailer
	 */
	public function modify_phpmailer( $phpmailer ) {

		$global_settings = Settings::get_setting( array('global', 'smtp') );
		$site_settings   = Settings::get_setting( array('site', 'smtp') );
		$settings        = array_replace_recursive($global_settings, $site_settings);

		list( $enable, $host, $port, $auth, $username, $password, $encryption ) = array_values($settings);

		if ( !$enable || empty($host) ) return;

		$phpmailer->Host = $host;
		$phpmailer->Port = intval($port);

		if ($auth) {
			$phpmailer->SMTPAuth = $auth; // if required
			$phpmailer->Username = $username; // if required
			$phpmailer->Password = $password; // if required
		}

		$phpmailer->SMTPSecure = $encryption; // enable if required, 'tls' is another possible value
		$phpmailer->IsSMTP();
	}

}