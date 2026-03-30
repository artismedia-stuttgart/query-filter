<?php
/*
Feature Name:   Performance Tools
Description:    Lazyload of images, fonts or libraries.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       12
Forced:         true
*/

/**
 * Pagespeed extensions.
 * - Autoptimize
 * - lazyloading (images/fonts)
 * - Lottie
 */
namespace Greyd\Extensions;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * disable if plugin wants to run standalone
 */
if ( ! class_exists( 'Greyd\Admin' ) ) {
	// reject activation
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
	deactivate_plugins( plugin_basename( __FILE__ ) );
	// return reject message
	die( sprintf( '%s can not be activated as standalone Plugin.', $plugin_name ) );
}

new Pagespeed();
class Pagespeed {

	/**
	 * Constructor
	 */
	public function __construct() {

		// settings
		add_filter( 'greyd_settings_default_site', array( $this, 'add_setting' ) );
		add_filter( 'greyd_settings_default_global', array( $this, 'add_setting_global' ) );
		add_filter( 'greyd_settings_basic', array( $this, 'render_settings' ), 8, 3 );

		add_filter( 'greyd_settings_before_save', array( $this, 'before_save_settings' ), 10, 2 );
		add_filter( 'greyd_settings_more_save', array( $this, 'save_settings' ), 10, 3 );
		add_filter( 'greyd_settings_more_global_save', array( $this, 'save_global_settings' ), 10, 3 );

		// int extension after plugins are initialized and \Greyd\Plugin functions are available
		// https://codex.wordpress.org/Plugin_API/Action_Reference
		add_action( 'after_setup_theme', array( $this, 'init' ) );

	}
	public function init() {

		if ( Helper::is_active_plugin( 'autoptimize/autoptimize.php' ) ) {

			// autoptimize extension
			add_action( 'admin_menu', array( $this, 'init_autoptimize_admin' ), 99 );
			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( $this, 'init_autoptimize_admin' ), 99 );
			}
			add_filter( 'autoptimize_filter_settingsscreen_tabs', array( $this, 'disable_autoptimize_tabs' ) );
			add_filter( 'autoptimize_filter_show_criticalcss_tabs', array( $this, 'disable_autoptimize_option' ) );
			add_filter( 'autoptimize_filter_show_partner_tabs', array( $this, 'disable_autoptimize_option' ) );
			add_filter( 'autoptimize_settingsscreen_remotehttp', array( $this, 'disable_autoptimize_option' ) );
			add_action( 'init', array( $this, 'init_autoptimize' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_autoptimize_script' ), 999 );
		}

	}


	/**
	 * =================================================================
	 *                          SETTINGS
	 * =================================================================
	 */

	// default settings
	public static function get_defaults( $mode ) {

		$defaults = array(
			'global' => array(
				'cache' => array(
					'settings'  => 'greyd',
					'enable'    => null,
					'max_size'  => 512,
					'no_emails' => null,
				),
			),
			'site'   => array(
				'cache'         => array(
					'settings'  => 'greyd',
					'enable'    => null,
					'max_size'  => 512,
					'no_emails' => null,
				),
				'lottie'        => array(
					'mode'   => 'default',
					'time'   => 1,
					'mobile' => null,
				),
				'lazyload'      => 'false',
				'font_lazyload' => 'false',
			),
		);

		/**
		 * Add more default settings.
		 *
		 * @filter 'greyd_pagespeed_settings_default'
		 *
		 * @param array $defaults       All current default settings.
		 */
		$defaults = apply_filters( 'greyd_pagespeed_settings_default', $defaults );

		return $defaults[ $mode ];
	}

	/**
	 * Add default site settings
	 *
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_setting( $settings ) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults( 'site' )
		);

		return $settings;
	}

	/**
	 * Add default global settings
	 *
	 * @see filter 'greyd_settings_default_global'
	 */
	public function add_setting_global( $settings ) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults( 'global' )
		);

		return $settings;
	}

	/**
	 * Render the settings
	 *
	 * @see filter 'greyd_settings_basic'
	 *
	 * @param string $content   Content of all additional settings.
	 * @param string $mode      'site' | 'network_site' | 'network_admin'
	 * @param array  $data       Current settings.
	 */
	public function render_settings( $content, $mode, $data ) {

		$show_site_settings  = ( $mode == 'network_site' || $mode == 'site' ) && isset( $data['site'] );
		$show_admin_settings = ( $mode == 'network_admin' && isset( $data['global'] ) );

		ob_start();

		if ( $show_site_settings || class_exists( 'autoptimizeCache' ) ) {
			echo '<h2>' . __( 'Pagespeed', 'greyd_hub' ) . '</h2>';
		}

		// Lazyload
		if ( $show_site_settings ) {

			echo "<table class='form-table'>";

			// images
			$lazyload = isset( $data['site']['lazyload'] ) ? strval( $data['site']['lazyload'] ) : 'false';
			echo '<tr><th>' . __( "Images and animations", 'greyd_hub' ) . '</th>
				<td>';
				$_val = 'false';
				echo "<label for='lazyload_$_val'>
						<input type='radio' name='lazyload' value='$_val' id='lazyload_$_val' " . ( $_val === $lazyload ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"lazyload\", \"$_val\")'/>
						<span>" . __( "Include images and animations normally", 'greyd_hub' ) . "</span><br>
						<small class='color_light'>" . __( "All images and animations will be loaded when the page is called. This can affect the page speed.", 'greyd_hub' ) . '</small>
					</label><br>';
				$_val = 'true';
				echo "<label for='lazyload_$_val'>
						<input type='radio' name='lazyload' value='$_val' id='lazyload_$_val' " . ( $_val === $lazyload ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"lazyload\", \"$_val\")'/>
						<span>" . __( "Optimized loading of images and animations (recommended)", 'greyd_hub' ) . "</span><br>
						<small class='color_light'>" . __( "Images and animations will initially be loaded in low resolution. This significantly improves the page speed.", 'greyd_hub' ) . '</small>
					</label><br>';
			echo '</td></tr>';

			// fonts
			$font_lazyload = isset( $data['site']['font_lazyload'] ) ? strval( $data['site']['font_lazyload'] ) : 'false';
			echo '<tr><th>' . __( "Fonts", 'greyd_hub' ) . '</th>
				<td>';
				$_val = 'false';
				echo "<label for='font_lazyload_$_val'>
						<input type='radio' name='font_lazyload' value='$_val' id='font_lazyload_$_val' " . ( $_val === $font_lazyload ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"font_lazyload\", \"$_val\")'/>
						<span>" . __( "Include fonts normally", 'greyd_hub' ) . "</span><br>
						<small class='color_light'>" . __( "All fonts are loaded before the actual content. This may affect the loading times of the website. We recommend using as few fonts as possible.", 'greyd_hub' ) . '</small>
					</label><br>';
				$_val = 'true';
				echo "<label for='font_lazyload_$_val'>
						<input type='radio' name='font_lazyload' value='$_val' id='font_lazyload_$_val' " . ( $_val === $font_lazyload ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"font_lazyload\", \"$_val\")'/>
						<span>" . __( "Lazyload fonts", 'greyd_hub' ) . "</span><br>
						<small class='color_light'>" . __( "All fonts are dynamically loaded after the content. This can lead to a short flash of the texts and headings. Usually the page speed is improved significantly.", 'greyd_hub' ) . '</small>
					</label><br>';
			echo '</td></tr>';

			echo '</table>';
		}

		// Lottie
		if ( $show_site_settings ) {
			echo "<table class='form-table'>";

			echo '<tr><th>' . __( 'Lottie', 'greyd_hub' ) . "<br>
					<small><a href='https://lottiefiles.com/' target='_blank'>" . __( "SVG animations on the web", 'greyd_hub' ) . '</a></small>
				</th><td>';

			$lottie_mode   = isset( $data['site']['lottie']['mode'] ) ? strval( $data['site']['lottie']['mode'] ) : 'default';
			$lottie_time   = isset( $data['site']['lottie']['time'] ) ? intval( $data['site']['lottie']['time'] ) : 2;
			$lottie_mobile = isset( $data['site']['lottie']['mobile'] ) && $data['site']['lottie']['mobile'] === 'true' ? "checked='checked'" : '';

			$_val = 'default';
			echo "<label for='$_val'>
					<input type='radio' name='lottie_mode' value='$_val' id='$_val' " . ( $_val === $lottie_mode ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"lottie_mode\", \"$_val\")'/>
					<span>" . __( "Include Lottie normally", 'greyd_hub' ) . "</span><br>
					<small class='color_light'>" . __( "The JavaScript library is loaded when the page is called. This can affect the page speed.", 'greyd_hub' ) . '</small>
				</label><br>';
			$_val = 'lazy';
			echo "<label for='$_val'>
					<input type='radio' name='lottie_mode' value='$_val' id='$_val' " . ( $_val === $lottie_mode ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"lottie_mode\", \"$_val\")'/>
					<span>" . __( "Delayed loading of Lottie (recommended)", 'greyd_hub' ) . "</span><br>
					<small class='color_light'>" . __( "The library will be loaded as soon as all other content has been loaded. This significantly improves the page speed and still allows animations to be displayed.", 'greyd_hub' ) . '</small>
				</label><br>';

			echo "<div class='inner_option lottie_mode_$_val " . ( $_val !== $lottie_mode ? 'hidden' : '' ) . "'>" .
					// Helper::render_info_box(
					// 	array(
					// 		'style' => 'info',
					// 		'text'  => __( 'Du kannst für jede Animation ein Platzhalterbild hinterlegen.', 'greyd_hub' ),
					// 	)
					// ) .
					'<br><div>' . __( "Additional delay", 'greyd_hub' ) . ":
						<input type='number' name='lottie_time' id='lottie_time' min='0' max='10' steps='1' style='padding-right:0;' value='$lottie_time' placeholder='$lottie_time' />&nbsp;s
					</div><br>
					<label for='lottie_mobile'>
						<input type='checkbox' id='lottie_mobile' name='lottie_mobile' $lottie_mobile />
						<span>" . __( "Loading the library on mobile devices", 'greyd_hub' ) . '</span>
					</label><br><br>
				</div>';

			$_val = 'disable';
			echo "<label for='$_val'>
					<input type='radio' name='lottie_mode' value='$_val' id='$_val' " . ( $_val === $lottie_mode ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"lottie_mode\", \"$_val\")'/>
					<span>" . __( "Disable Lottie", 'greyd_hub' ) . "</span>
					<small class='color_light'>" . __( "The library will not be loaded. This massively improves your page speed, but Lottie animations cannot be displayed.", 'greyd_hub' ) . '</small>
				</label><br>';

			echo '</td></tr>';

			echo '</table>';
		}

		// Autoptimize
		if ( class_exists( 'autoptimizeCache' ) ) {
			echo "<table class='form-table'>
					<tr><th>" . __( 'Autoptimize', 'greyd_hub' ) . '</th>
					<td>';

					$site_config = \autoptimizeOptionWrapper::get_option( 'autoptimize_enable_site_config', 'on' );
			if ( $site_config == 'on' ) {
				$site_config = 'site';
			} else {
				$site_config = 'global';
			}
					// cache info
					$cache_stats = \autoptimizeCache::stats();
					$cache_files = $cache_stats[0];
					$cache_size  = number_format( (float) ( $cache_stats[1] / 1024 / 1024 ), 2, '.', '' );

					// render multisite options radio
			if ( $mode == 'network_admin' ) {
				$toggle = 'document.querySelector(".toggle_multi").classList.toggle("hidden")';
				echo "<label for='cache_settings_multi_site'>
								<input type='radio' id='cache_settings_multi_site' name='cache_settings_multi' value='site' " . ( $site_config == 'site' ? "checked='checked'" : '' ) . " onchange='" . $toggle . "' />" .
						__( "Set up Autoptimize individually on each website of the installation", 'greyd_hub' ) .
					'</label><br>';
				echo "<label for='cache_settings_multi_global'>
								<input type='radio' id='cache_settings_multi_global' name='cache_settings_multi' value='global' " . ( $site_config == 'global' ? "checked='checked'" : '' ) . " onchange='" . $toggle . "' />" .
						__( "Set up Autoptimize globally for all websites of the installation", 'greyd_hub' ) .
					'</label><br><br>';
				echo "<div class='inner_option toggle_multi " . ( $site_config == 'site' ? 'hidden' : '' ) . "'><hr><br>";
			}

			if ( ( $site_config == 'site' && $show_site_settings ) || ( $site_config == 'global' && $show_admin_settings ) ) {

				// enabled
				$enabled = isset( $data[ $site_config ]['cache']['enable'] ) && $data[ $site_config ]['cache']['enable'] == 'true' ? "checked='checked'" : '';
				// no_emails
				$no_emails = isset( $data[ $site_config ]['cache']['no_emails'] ) && $data[ $site_config ]['cache']['no_emails'] == 'true' ? "checked='checked'" : '';
				// settings
				$settings = isset( $data[ $site_config ]['cache']['settings'] ) ? $data[ $site_config ]['cache']['settings'] : 'greyd';
				// max cache size
				$max_size = isset( $data[ $site_config ]['cache']['max_size'] ) ? $data[ $site_config ]['cache']['max_size'] : 512;

				// render basic options radio
				echo "<label for='cache_settings_greyd'>
								<input type='radio' id='cache_settings_greyd' name='cache_settings' value='greyd' " . ( $settings == 'greyd' ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"cache_settings\", \"greyd\")' />
								<span>" . __( "Use optimized Greyd settings (recommended)", 'greyd_hub' ) . '</span>
							</label><br>';
					// render optimize admin
					$cache_admin = \autoptimizeOptionWrapper::get_option( 'autoptimize_optimize_logged', '1' );
					$cache_admin = ( $cache_admin == 'on' ) ? "checked='checked'" : '';
					echo "<div class='inner_option cache_settings_greyd " . ( $settings !== 'greyd' ? 'hidden' : '' ) . "'>
									<label for='cache_admin'>
									<input type='checkbox' id='cache_admin' name='cache_admin' " . $cache_admin . ' />
									<span>' . __( "Also optimize for logged-in editors / administrators", 'greyd_hub' ) . "</span>
									<small class='color_light'>" . __( "By default, Autoptimize is also active for registered editors/administrators. Select this option if you don't want Autoptimize to optimize even when someone is signed in, for example to make changes to the website.", 'greyd_hub' ) . '</small>
									</label><br>
								</div>';
				echo "<label for='cache_settings_custom'>
								<input type='radio' id='cache_settings_custom' name='cache_settings' value='custom' " . ( $settings == 'custom' ? "checked='checked'" : '' ) . " onchange='greyd.backend.toggleRadioByClass(\"cache_settings\", \"custom\")' />
								<span>" . __( "Customize Autoptimize settings yourself", 'greyd_hub' ) . '</span>
							</label><br>';
					// render link to original options
					$url = 'options-general.php?page=autoptimize';
				if ( $mode == 'network_admin' ) {
					$url = 'settings.php?page=autoptimize';
				}
					echo "<div class='inner_option cache_settings_custom " . ( $settings !== 'custom' ? 'hidden' : '' ) . "'>
									<a href='" . $url . "' target='_blank'>" . __( "View Autoptimize settings", 'greyd_hub' ) . '</a><br>
								</div><br>';

				if ( $mode != 'network_admin' ) {
					// render cache info
					echo "<p style='margin-bottom:10px;'>" . sprintf( __( "Current size of the cache: %1\$s megabytes (%2\$s files)", 'greyd_hub' ), $cache_size, $cache_files ) . '</p>';
					echo "<button class='button button-danger' name='empty_cache'>" . __( "Clear cache now", 'greyd_hub' ) . " <span class='dashicons dashicons-trash'></span></button><br><br>";
				}

				// render autocache options
				$toggle = 'document.querySelector(".toggle_cache").classList.toggle("hidden")';
				echo "<label for='cache_enable'>
								<input type='checkbox' id='cache_enable' name='cache_enable' " . $enabled . " onchange='" . $toggle . "'/>
								<span>" . __( "Delete cache files regularly", 'greyd_hub' ) . "</span>
								<small class='color_light'>" . __( 'Greyd.Suite has an optimized connection to Autoptimize. This option allows you to automatically clear the cache from the size set instead of always having to do it manually.', 'greyd_hub' ) . '</small>
							</label><br>';
				echo "<div class='inner_option toggle_cache " . ( $enabled == '' ? 'hidden' : '' ) . "'>";
					echo "<p style='margin-bottom:4px;'>" . __( "Maximum size of cache files", 'greyd_hub' ) . '</p>';
					echo "<select name='cache_max_size' id='cache_max_size'>
									<option value='8' " . ( $max_size == 8 ? 'selected' : '' ) . '>8 ' . __( 'Megabytes', 'greyd_hub' ) . "</option>
									<option value='64' " . ( $max_size == 64 ? 'selected' : '' ) . '>64 ' . __( 'Megabytes', 'greyd_hub' ) . "</option>
									<option value='128' " . ( $max_size == 128 ? 'selected' : '' ) . '>128 ' . __( 'Megabytes', 'greyd_hub' ) . "</option>
									<option value='256' " . ( $max_size == 256 ? 'selected' : '' ) . '>256 ' . __( 'Megabytes', 'greyd_hub' ) . "</option>
									<option value='512' " . ( $max_size == 512 ? 'selected' : '' ) . '>512 ' . __( 'Megabytes', 'greyd_hub' ) . ' ' . __( "(recommended)", 'greyd_hub' ) . "</option>
									<option value='768' " . ( $max_size == 768 ? 'selected' : '' ) . '>768 ' . __( 'Megabytes', 'greyd_hub' ) . "</option>
									<option value='1024' " . ( $max_size == 1024 ? 'selected' : '' ) . '>1 ' . __( 'Gigabyte', 'greyd_hub' ) . '</option>
								</select><br><br>';
				echo '</div>';

				echo "<label for='cache_no_emails'>
								<input type='checkbox' id='cache_no_emails' name='cache_no_emails' " . $no_emails . ' />
								<span>' . __( "Do not send \"Autoptimize cache size\" alert emails", 'greyd_hub' ) . "</span>
								<small class='color_light'>" . __( "This option allows you to disable the default emails for cache size.", 'greyd_hub' ) . '</small>
							</label><br>';
			}

					// render multisite options radio toggle close wrapper
			if ( $mode == 'network_admin' ) {
				echo '</div>';
			} elseif ( $site_config == 'global' ) {
				echo '<div>' . __( "Autoptimize is enabled and configured at the WordPress network level. Please contact your network administrator if you want to change Autoptimize settings.", 'greyd_hub' ) . '</div><br>';
				// render cache info
				echo '<label>' . sprintf( __( "Current size of the cache: %1\$s megabytes (%2\$s files)", 'greyd_hub' ), $cache_size, $cache_files ) . '</label><br>';
				echo "<button class='button' name='empty_cache'>" . __( 'Clear cache now', 'greyd_hub' ) . " <span class='dashicons dashicons-trash'></span></button><br><br>";

			}

			echo '</td>
					</tr>
				</table>';
		}

		/**
		 * Add more settings to settings page.
		 * Return full table like this:
		 * <table class='form-table'><tr><th>...</th><tb>...</tb></tr></table>
		 *
		 * @filter 'greyd_pagespeed_settings_more'
		 *
		 * @param string $content   More settings string.
		 * @param string $mode      Settings mode (site, network_site or network_admin).
		 * @param array $data       All current settings.
		 */
		echo apply_filters( 'greyd_pagespeed_settings_more', '', $mode, $data );

		$content .= ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Action before saving the settings
	 *
	 * @see filter 'greyd_settings_before_save'
	 *
	 * @param bool  $did_action  True if some action was fired that doesn't need saving after.
	 * @param array $data       Raw $_POST data.
	 */
	public function before_save_settings( $did_action, $data ) {

		// trigger autoptimize clear cache
		if ( class_exists( 'autoptimizeCache' ) && isset( $data['empty_cache'] ) ) {
			\autoptimizeCache::clearall();
			Helper::show_message(
				__( "Autoptimize Cache has been deleted.", 'greyd_hub' ) . ' ' .
							__( "To update all statistics, please refresh the page:", 'greyd_hub' ) .
				" <a href='#' onclick='location.reload()'>klick</a>",
				'success'
			);
			return true;
		}

		return $did_action;
	}

	/**
	 * Save the settings
	 *
	 * @see filter 'greyd_settings_more_save'
	 *
	 * @param array $site       Current site settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_settings( $site, $defaults, $data ) {

		// autoptimize settings
		if ( class_exists( 'autoptimizeCache' ) ) {
			if ( isset( $data['cache_settings'] ) && $data['cache_settings'] == 'greyd' ) {
				$cache_admin = isset( $data['cache_admin'] ) ? 'on' : '';
				\autoptimizeOptionWrapper::update_option( 'autoptimize_optimize_logged', $cache_admin );
			}
		}

		// make new settings
		$site['cache'] = array();
		foreach ( (array) $defaults['cache'] as $key => $value ) {
			// convert checkboxes to true | false
			if ( $key === 'enable' || $key === 'no_emails' ) {
				$post_value = isset( $data[ 'cache_' . $key ] ) ? 'true' : 'false';
			} else {
				$post_value = isset( $data[ 'cache_' . $key ] ) ? esc_attr( $data[ 'cache_' . $key ] ) : $value;
			}
			$site['cache'][ $key ] = $post_value;
		}
		$site['lottie'] = array();
		foreach ( (array) $defaults['lottie'] as $key => $value ) {
			if ( $key === 'mobile' ) {
				$post_value = isset( $data[ 'lottie_' . $key ] ) ? 'true' : 'false';
			} else {
				$post_value = isset( $data[ 'lottie_' . $key ] ) ? esc_attr( $data[ 'lottie_' . $key ] ) : $value;
			}
			$site['lottie'][ $key ] = $post_value;
		}
		$site['lazyload']      = isset( $data['lazyload'] ) ? esc_attr( $data['lazyload'] ) : 'false';
		$site['font_lazyload'] = isset( $data['font_lazyload'] ) ? esc_attr( $data['font_lazyload'] ) : 'false';

		/**
		 * Save more site settings.
		 *
		 * @filter 'greyd_pagespeed_settings_more_save'
		 *
		 * @param array $site           New site settings.
		 * @param array $defaults       Default site settings.
		 * @param array $_POST          Raw $_POST data.
		 */
		$site = apply_filters( 'greyd_pagespeed_settings_more_save', $site, $defaults, $data );

		return $site;
	}

	/**
	 * Save global settings
	 *
	 * @see filter 'greyd_settings_more_global_save'
	 *
	 * @param array $global     Current global settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_global_settings( $global, $defaults, $data ) {
		if ( ! is_network_admin() ) {
			return $global;
		}

		// autoptimize settings
		if ( class_exists( 'autoptimizeCache' ) ) {
			if ( isset( $data['cache_settings_multi'] ) ) {
				if ( $data['cache_settings_multi'] == 'site' ) {
					\autoptimizeOptionWrapper::update_option( 'autoptimize_enable_site_config', 'on' );
				} elseif ( $data['cache_settings_multi'] == 'global' ) {
					\autoptimizeOptionWrapper::update_option( 'autoptimize_enable_site_config', '' );
				}
			}
		}

		// make new settings
		$global['cache'] = array();
		foreach ( (array) $defaults['cache'] as $key => $value ) {
			// convert checkboxes to true | false
			if ( $key === 'enable' || $key === 'no_emails' ) {
				$post_value = isset( $data[ 'cache_' . $key ] ) ? 'true' : 'false';
			} else {
				$post_value = isset( $data[ 'cache_' . $key ] ) ? esc_attr( $data[ 'cache_' . $key ] ) : $value;
			}
			$global['cache'][ $key ] = $post_value;
		}

		/**
		 * Save more global settings.
		 *
		 * @filter 'greyd_pagespeed_settings_more_save'
		 *
		 * @param array $global         New global settings.
		 * @param array $defaults       Default global settings.
		 * @param array $_POST          Raw $_POST data.
		 */
		$global = apply_filters( 'greyd_pagespeed_settings_more_global_save', $global, $defaults, $data );

		return $global;
	}


	/*
	====================================================================================
		Autoptimize
	====================================================================================
	*/
	public function disable_autoptimize_option( $enabled ) {
		// debug($enabled);
		return false;
	}
	public function disable_autoptimize_tabs( $enabled ) {
		return array();
	}
	public function init_autoptimize_admin() {
		if ( class_exists( 'autoptimizeCache' ) ) {
			$site_config = \autoptimizeOptionWrapper::get_option( 'autoptimize_enable_site_config', 'on' ) == 'on' ? 'site' : 'global';
			if ( $site_config == 'global' ) {
				remove_submenu_page( 'options-general.php', 'autoptimize' ); // site options
			}
			if ( $site_config == 'site' ) {
				remove_submenu_page( 'settings.php', 'autoptimize' ); // network options
			}
			if ( Settings::get_setting( array( $site_config, 'cache', 'settings' ) ) == 'greyd' ) {
				$this->check_autoptimize_options();
				if ( $site_config == 'site' ) {
					remove_submenu_page( 'options-general.php', 'autoptimize' ); // site options
				}
				if ( $site_config == 'global' ) {
					remove_submenu_page( 'settings.php', 'autoptimize' ); // network options
				}
			}
		}
	}
	public function check_autoptimize_options() {
		$options = array(
			'autoptimize_html'               => 'on',               // HTML-Code optimieren?
			'autoptimize_html_keepcomments'  => '',                 // HTML-Kommentare beibehalten?
			// "autoptimize_enable_site_config" => "on",               // Enable site configuration? (multisite)
			'autoptimize_js'                 => 'on',               // JavaScript-Code optimieren?
			'autoptimize_js_aggregate'       => 'on',               // JS-Dateien zusammenfügen?
			'autoptimize_js_exclude'         => 'wp-includes/js/dist/, wp-includes/js/tinymce/, js/jquery/jquery.js, jquery.min.js', // Folgende Skripte von Autoptimize ausschließen:
			'autoptimize_js_trycatch'        => '',                 // Try-Catch-Block hinzufügen?
			// "autoptimize_js_justhead"        => "",                 // internal
			'autoptimize_js_forcehead'       => '',                 // JavaScript im <head> erzwingen?
			'autoptimize_js_include_inline'  => '',                 // Auch Inline-JS zusammenfügen?
			'autoptimize_css'                => 'on',               // CSS-Code optimieren?
			'autoptimize_css_aggregate'      => 'on',               // CSS-Dateien zusammenfügen?
			'autoptimize_css_exclude'        => 'wp-content/cache/, wp-content/uploads/, admin-bar.min.css, dashicons.min.css', // Folgende CSS-Dateien von Autoptimize ausschließen:
			// "autoptimize_css_justhead"       => "",                 // internal
			'autoptimize_css_datauris'       => '',                 // Data: URIs für Bilder generieren (Inline Images)?
			'autoptimize_css_defer'          => '',                 // CSS-Code inline einfügen und verzögern?
			'autoptimize_css_defer_inline'   => '',                 // CSS-Code inline einfügen und verzögern?
			'autoptimize_css_inline'         => '',                 // Gesamten CSS-Code Inline einfügen?
			'autoptimize_css_include_inline' => 'on',               // Auch Inline-CSS zusammenfügen?
			'autoptimize_cdn_url'            => '',                 // CDN-Basis-URL
			// "autoptimize_cache_clean"        => "",                 // internal
			'autoptimize_cache_nogzip'       => '',                 // Zusammengefügte CSS-/Skript-Dateien als statische Dateien speichern?
			// "autoptimize_optimize_logged"    => "on",               // Auch für angemeldete Redakteure/Administratoren optimieren?
			'autoptimize_optimize_checkout'  => '',                 // woocommerce?
			'autoptimize_minify_excluded'    => 'on',               // Ausgeschlossene CSS- und JS-Dateien minimieren?
			'autoptimize_cache_fallback'     => '',                 // Experminentell: 404-Fallbacks aktivieren.
			'autoptimize_imgopt_settings'    => array(
				'autoptimize_imgopt_checkbox_field_1' => false,     // Bilder optimieren
				'autoptimize_imgopt_select_field_2'   => '2',       // Bildoptimierungs-Qualität
				'autoptimize_imgopt_checkbox_field_4' => false,     // WebP in unterstützten Browsern laden?
				'autoptimize_imgopt_checkbox_field_3' => false,     // Bilder verzögert laden?
				'autoptimize_imgopt_text_field_5'     => '',        // Von Verzögerung ausschließen
			),
			'autoptimize_extra_settings'     => array(
				'autoptimize_extra_radio_field_4'    => '1',       // Google Fonts
				'autoptimize_extra_checkbox_field_1' => '1',       // Emojis entfernen
				'autoptimize_extra_checkbox_field_0' => '1',       // Abfragezeichenfolgen von statischen Ressourcen entfernen
				'autoptimize_extra_text_field_2'     => '',        // Zu Dritt-Domains vorverbinden
				'autoptimize_extra_text_field_7'     => '',        // Spezifische Anfragen im Voraus laden
				'autoptimize_extra_text_field_3'     => '',        // Asynchrone JavaScript-Dateien
			),
		);
		// debug($options);
		foreach ( $options as $option => $default ) {
			$value = \autoptimizeOptionWrapper::get_option( $option );
			// debug($value);
			if ( is_array( $default ) ) {
				$changed = false;
				foreach ( $default as $soption => $sdefault ) {
					if ( isset( $value[ $soption ] ) && $value[ $soption ] != $sdefault ) {
						$value[ $soption ] = $sdefault;
						$changed           = true;
					}
				}
				if ( $changed ) {
					\autoptimizeOptionWrapper::update_option( $option, $value );
				}
			} else {
				if ( $value != $default ) {
					\autoptimizeOptionWrapper::update_option( $option, $default );
				}
			}
		}
	}

	public function add_autoptimize_script() {
		$screen = get_current_screen();
		// debug($screen);
		if ( $screen->base == 'settings_page_autoptimize' ) {
			wp_add_inline_script(
				'autoptimize-toolbar',
				"
				if (jQuery('#autoptimize_admin_feed').length > 0) {
					jQuery('#autoptimize_admin_feed').next('script').remove();
					jQuery('#autoptimize_admin_feed').remove();
					jQuery('#autoptimize_main').children().insertBefore('#autoptimize_main');
					jQuery('#autoptimize_main').remove();
				}
				if (jQuery('.itemDetail.multiSite').length > 0) {
					jQuery('.itemDetail.multiSite').remove();
				}
			"
			);
		}
	}
	public function init_autoptimize() {
		// manage autoptimize cache
		if ( class_exists( 'autoptimizeCache' ) ) {
			$site_config = \autoptimizeOptionWrapper::get_option( 'autoptimize_enable_site_config', 'on' ) == 'on' ? 'site' : 'global';
			if ( Settings::get_setting( array( $site_config, 'cache', 'enable' ) ) == 'true' ) {
				// add_action('init', array($this, 'check_autoptimize_cache'));
				$this->check_autoptimize_cache();
				add_filter( 'autoptimize_filter_cachecheck_maxsize', array( $this, 'adjust_autoptimize_cachesize' ) );

				if ( Settings::get_setting( array( $site_config, 'cache', 'no_emails' ) ) == 'true' ) {
					add_filter( 'autoptimize_filter_cachecheck_sendmail', array( $this, 'disable_autoptimize_option' ) );
				}
			}
		}
	}
	public function adjust_autoptimize_cachesize( $size ) {
		$site_config    = \autoptimizeOptionWrapper::get_option( 'autoptimize_enable_site_config', 'on' ) == 'on' ? 'site' : 'global';
		$max_cache_size = Settings::get_setting( array( $site_config, 'cache', 'max_size' ) );
		$size           = ( $max_cache_size + 1 ) * 1024 * 1024;
		// debug($size);
		return $size;
	}
	public function check_autoptimize_cache() {
		$site_config = \autoptimizeOptionWrapper::get_option( 'autoptimize_enable_site_config', 'on' ) == 'on' ? 'site' : 'global';
		// get maximum cache size setting
		$max_cache_size = Settings::get_setting( array( $site_config, 'cache', 'max_size' ) );
		// debug("max_cache_size: ".$max_cache_size);
		if ( $max_cache_size != '' && $max_cache_size > 0 ) {
			// current autoptimize cache size
			$ao_cache_stats = \autoptimizeCache::stats();
			$ao_cache_size  = $ao_cache_stats[1] / 1024 / 1024;
			// debug("ao_cache_size: ".$ao_cache_size);
			if ( $ao_cache_size > $max_cache_size ) {
				// debug("clear cache");
				\autoptimizeCache::clearall();
				if ( is_admin() ) {
					// debug("refresh admin backend");
					header( 'Refresh:0' );
				}
			}
		}
	}

}
