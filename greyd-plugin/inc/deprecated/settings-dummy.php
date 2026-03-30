<?php
/**
 * Settings compatibility dummy.
 * The old version of of the Settings exposed a few static functions that are used by Theme and Plugins.
 * Those are re-created here and re-routed to their versions within the new namespaces.
 */

namespace tp\management;

if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists('settings') ) {
	class settings {

		/**
		 * Get single Setting or subset of Settings
		 * 
		 * @param string|array $key     Usually array of strings which defines the path to the Setting.
		 * @param bool $default         If the Setting is not found, give back the default value.
		 * @return string|array $value  The Setting value or subset.
		 */
		public static function get_setting($key, $default=true) {

			return \Greyd\Settings::get_setting($key, $default);

		}

		/**
		 * Show wordpress style notice in top of page.
		 * @param string $msg   The message to show.
		 * @param string $mode  Style of the notice (error, warning, success, info).
		 * @param bool $list    Add to hub msg list (default: false).
		 */
		public static function showMessage($msg, $mode='info', $list=false) {

			\Greyd\Helper::show_message($msg, $mode, $list);

		}

	}
}