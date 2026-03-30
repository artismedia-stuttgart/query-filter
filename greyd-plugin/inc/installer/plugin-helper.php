<?php
/**
 * Plugin Helper
 * Helper functions for installing plugins and themes.
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\Greyd\Plugin_Helper' ) ) {
	return;
}

class Plugin_Helper {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Settings.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * =================================================================
	 *                          PLUGINS
	 * =================================================================
	 */

	/**
	 * Install & activate plugin.
	 *
	 * Similar to active_plugin() but also installs the plugin if it's not installed yet.
	 *
	 * @link https://developer.wordpress.org/reference/functions/activate_plugin/
	 *
	 * @param string $slug    Plugin folder name.
	 * @param string $zip     URL to the plugin zip file.
	 *
	 * @return bool|\WP_Error True if the installation was successful, a WP_Error otherwise.
	 */
	public static function activate_plugin( $slug, $zip = null ) {

		$plugin_file = self::get_plugin_file( $slug );

		if ( ! $plugin_file ) {

			$installed = self::install_plugin( $slug, $zip );

			if ( is_wp_error( $installed ) ) {
				return $installed;
			} elseif ( ! $installed ) {
				return new \WP_Error(
					'not_installed',
					'Plugin could not be installed.'
				);
			}

			// get plugin file
			$plugin_file = self::get_plugin_file( $slug );
			if ( ! $plugin_file ) {
				return new \WP_Error(
					'not_installed',
					'Plugin could not be installed.'
				);
			}
		}

		$activated = activate_plugin(
			$plugin_file,
			'', // redirect
			false, // network_wide
			true // silent
		);

		if ( is_wp_error( $activated ) ) {
			return $activated;
		}

		return true;
	}

	/**
	 * Install plugin.
	 *
	 * @param string $slug    Plugin folder name.
	 * @param string $zip     URL to the plugin zip file.
	 *
	 * @return bool|\WP_Error True if the installation was successful, false or a WP_Error otherwise.
	 */
	public static function install_plugin( $slug, $zip = null ) {

		if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( empty( $zip ) ) {
			$zip = self::get_plugin_zip( $slug );

			if ( empty( $zip ) ) {
				return new \WP_Error(
					'no_zip',
					'No ZIP file found.'
				);
			}
		}

		wp_cache_flush();
		$upgrader  = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$installed = $upgrader->install( $zip );

		// error installing
		if ( ! $installed ) {

			/**
			 * Check if plugin folder already exists without the plugin
			 * being installed.
			 *
			 * When the installation of a plugin was aborted, sometimes the
			 * plugin folder exists, without it being an installed plugin.
			 * This causes the plugin to not be installable anymore. To
			 * prevent this, we're deleting the orphaned folder and restart
			 * the plugin installation.
			 */
			$plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR . '/' . $slug : null;
			if ( is_dir( $plugin_dir ) && ! self::get_plugin_file( $slug ) ) {
				$result = self::delete_directory( $plugin_dir );
				if ( $result ) {
					return self::install_plugin( $zip, $slug );
				}
			}
		}

		return $installed;
	}

	/**
	 * Get path to the plugin file relative to the plugins directory.
	 * This is needed to activate the plugin.
	 *
	 * @param string $slug    Plugin folder name.
	 *
	 * @return null|string    Path to the plugin file or null if not found.
	 */
	public static function get_plugin_file( $slug ) {

		// greyd tools
		if ( $slug === 'greyd-forms' || $slug === 'greyd_forms' ) {
			$slug = 'greyd_tp_forms';
		} elseif ( $slug === 'greyd-blocks' || $slug === 'greyd_blocks' ) {
			$slug = 'greyd_blocks';
		} elseif ( $slug === 'greyd-globalcontent' || $slug === 'greyd_globalcontent' ) {
			$slug = 'greyd_globalcontent';
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		foreach ( $all_plugins as $plugin => $details ) {
			if ( strpos( $plugin, $slug . '/' ) === 0 ) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * Get plugin zip from the WordPress directory API.
	 *
	 * @param string $slug    Plugin folder name.
	 *
	 * @return string|bool
	 */
	public static function get_plugin_zip( $slug ) {

		// greyd tools
		if ( $slug === 'greyd-forms' || $slug === 'greyd_forms' ) {
			return 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_forms/greyd_tp_forms.zip';
		} elseif ( $slug === 'greyd-blocks' || $slug === 'greyd_blocks' ) {
			return 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_blocks/greyd_blocks.zip';
		} elseif ( $slug === 'greyd-globalcontent' || $slug === 'greyd_globalcontent' ) {
			return 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_globalcontent/greyd_globalcontent.zip';
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array( 'sections' => false ),
			)
		);

		if ( $api !== false && isset( $api->download_link ) ) {
			return $api->download_link;
		}

		return false;
	}

	/**
	 * =================================================================
	 *                          THEMES
	 * =================================================================
	 */

	/**
	 * Install & activate theme.
	 *
	 * @param string $slug    Plugin folder name.
	 * @param string $zip     URL to the theme zip file.
	 *
	 * @return bool|\WP_Error True if the installation was successful, a WP_Error otherwise.
	 */
	public static function activate_theme( $slug, $zip = null ) {

		if ( $slug == 'greyd-theme' || $slug == 'greyd-wp' ) {

			// if GREYD_THEME_CONFIG is defined, the theme is already active
			if ( defined( 'GREYD_THEME_CONFIG' ) ) {
				return true;
			}
		}

		$theme_file = self::get_theme_file( $slug );

		if ( ! $theme_file ) {

			$installed = self::install_theme( $slug, $zip );

			if ( is_wp_error( $installed ) ) {
				return $installed;
			} elseif ( ! $installed ) {
				return new \WP_Error(
					'not_installed',
					'Theme could not be installed.'
				);
			}

			// get theme file
			$theme_file = self::get_theme_file( $slug );
			if ( ! $theme_file ) {
				return new \WP_Error(
					'not_installed',
					'Theme could not be installed.'
				);
			}
		}

		/**
		 * Add filter to prevent the theme from deactivating required plugins.
		 * This is necessary because the classic GREYD.SUITE theme checks
		 * for required plugins and deactivates them automatically below
		 * the version 1.8.7
		 */
		add_filter( 'greyd_required_plugins', function( $plugins ) {
			return array( array() );
		}, 99, 1 );

		switch_theme( $theme_file );

		return true;
	}

	/**
	 * Install theme.
	 *
	 * @param string $slug    Theme folder name.
	 * @param string $zip     URL to the theme zip file.
	 *
	 * @return bool|\WP_Error True if the installation was successful, false or a WP_Error otherwise.
	 */
	public static function install_theme( $slug, $zip = null ) {

		if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( empty( $zip ) ) {

			$zip = self::get_theme_zip( $slug );

			if ( empty( $zip ) ) {
				return new \WP_Error(
					'no_zip',
					'No ZIP file found.'
				);
			}
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();
		$upgrader  = new \Theme_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$installed = $upgrader->install( $zip );

		return $installed;
	}

	/**
	 * Get path to the theme file relative to the themes directory.
	 * This is needed to activate the theme.
	 *
	 * @param string $slug    Theme folder name.
	 *
	 * @return null|string    Path to the theme file or null if not found.
	 */
	public static function get_theme_file( $slug ) {

		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		$all_themes = wp_get_themes();
		foreach ( $all_themes as $theme => $details ) {
			if ( $theme === $slug ) {
				return $theme;
			}
		}
		return null;
	}

	/**
	 * Get theme zip from the WordPress directory API.
	 *
	 * @param string $slug    Theme folder name.
	 *
	 * @return string|bool
	 */
	public static function get_theme_zip( $slug ) {

		// greyd theme
		if ( $slug === 'greyd-theme' ) {
			return 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/themes/greyd-theme/greyd-theme.zip';
		}

		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $slug,
				'fields' => array( 'sections' => false ),
			)
		);

		if ( $api !== false && isset( $api->download_link ) ) {
			return $api->download_link;
		}

		return false;
	}

	/**
	 * Create child theme of Greyd Theme
	 *
	 * @return true|\WP_Error True if the child theme was created successfully, a WP_Error otherwise.
	 */
	public static function create_child_theme() {

		// Prepare directories.
		$child_theme_file_dir = __DIR__ . '/greyd-child-theme';
		$greyd_theme_dir      = get_template_directory();
		$greyd_child_dir      = str_replace( '/themes/greyd-theme', '/themes/greyd-child-theme', $greyd_theme_dir );

		// Create directory.
		if ( ! file_exists( $greyd_child_dir ) ) {
			wp_mkdir_p( $greyd_child_dir );
		}

		// Copy CSS file.
		if ( ! copy( $child_theme_file_dir . '/style.css', $greyd_child_dir . '/style.css' ) ) {
			return new \WP_Error(
				'style.css',
				'Failed to copy style.css'
			);
		}

		// Copy screenshot.
		if ( ! copy( $child_theme_file_dir . '/screenshot.jpg', $greyd_child_dir . '/screenshot.jpg' ) ) {
			return new \WP_Error(
				'screenshot.jpg',
				'Failed to copy screenshot.jpg'
			);
		}

		// Copy functions.php file.
		if ( ! copy( $child_theme_file_dir . '/functions.php', $greyd_child_dir . '/functions.php' ) ) {
			return new \WP_Error(
				'functions.php',
				'Failed to copy functions.php'
			);
		}

		// Activate child theme.
		switch_theme( 'greyd-child-theme' );

		return true;
	}

	/**
	 * =================================================================
	 *                          HELPER
	 * =================================================================
	 */

	/**
	 * Delete a directory.
	 *
	 * @param string $dir    Directory to delete.
	 *
	 * @return bool
	 */
	public static function delete_directory( $dir ) {
		if ( ! file_exists( $dir ) ) {
			return true;
		}
		if ( ! is_dir( $dir ) ) {
			return unlink( $dir );
		}
		foreach ( scandir( $dir ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}
			if ( ! self::delete_directory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}
		}
		return rmdir( $dir );
	}
}
