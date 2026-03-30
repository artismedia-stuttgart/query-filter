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

new Converter_Ajax_Handler();
class Converter_Ajax_Handler {

	/**
	 * Whether debug mode is enabled.
	 */
	const DEBUG = true;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// ajax handler
		add_action( 'wp_ajax_greyd_transform_ajax', array( $this, 'handle_greyd_ajax_request' ) );
	}

	/**
	 * =================================================================
	 *                          AJAX HANDLER
	 * =================================================================
	 */

	/**
	 * Handle the basic ajax request
	 */
	public function handle_greyd_ajax_request() {

		// if the old extension is still active inside the theme, do nothing
		if ( class_exists( '\Greyd\Theme\Converter_Ajax_Handler') ) {
			return;
		}

		// invalid ajax request
		if ( ! check_ajax_referer( 'greyd-transform' ) ) {
			$this->finish();
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			$this->finish();
			return;
		}

		// early exit
		if ( ! isset( $_POST['mode'] ) ) {
			wp_die( 'error::' . __( "The POST variable 'mode' needs to be set.", 'greyd_hub' ) );
		}
		// debug($_POST['mode']);

		// start
		$mode = $_POST['mode'];
		if ( self::DEBUG ) {
			echo "\r\n\r\n" . '------------- debug start -------------' . "\r\n\r\n" . 'MODE: ' . $mode . "\r\n";
		}

		// get post-data
		if ( ! isset( $_POST['data'] ) ) {
			$this->finish( 'error::' . __( 'No data found', 'greyd_hub' ) );
		}
		// $post_data = $_POST['data'];
		$post_data = json_decode( rawurldecode( $_POST['data'] ), true );
		// debug(json_last_error_msg());

		// debug($post_data);

		/**
		 * Convert Global Styles.
		 */
		if ( $mode === 'convert_styles_data' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'CONVERT STYLES';
			}

			list( $id, $data ) = self::convert_styles( $post_data );

			/**
			 * Update styles post.
			 *
			 * @see https://developer.wordpress.org/reference/functions/wp_update_post/
			 * @return int|WP_Error
			 */
			// $result = new \WP_Error( 422, 'debugging ...' );
			$result = wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => json_encode( $data ),
				)
			);

			// check if there was an error updating
			if ( is_wp_error( $result ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error updating styles post "' . $id . '":';
				}
				$this->finish( 'error::' . $result->get_error_message() );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* styles post "' . $id . '" successfully updated.';
			}
			$this->finish( 'success::' . __( 'Global Styles successfully modified with new values', 'greyd_hub' ) );
		}

		/**
		 * Convert Fonts.
		 */
		if ( $mode === 'convert_fonts' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'CONVERT FONTS';
			}

			$result = self::convert_fonts( $post_data );

			// check if there was an error
			if ( is_wp_error( $result ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error converting fonts ' . $result . ':';
				}
				$this->finish( 'error::' . $result->get_error_message() );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* fonts successfully converted ' . $result . '.' . "\r\n";
			}
			$this->finish( 'success::' . sprintf( __( 'Fonts %s successfully converted.', 'greyd_hub' ), '<strong>' . $result . '</strong>' ) );
		}

		/**
		 * Convert WP Template Part
		 * header and footer
		 */
		if (
			$mode === 'convert_header_data' ||
			$mode === 'convert_footer_data'
		) {
			if ( $mode === 'convert_header_data' ) {
				if ( self::DEBUG ) {
					echo "\r\n\r\n" . 'CONVERT HEADER';
				}

				// convert header
				$atts = self::convert_header( $post_data );

			}

			if ( $mode === 'convert_footer_data' ) {
				if ( self::DEBUG ) {
					echo "\r\n\r\n" . 'CONVERT FOOTER';
				}

				// convert footer
				$atts = self::convert_footer( $post_data );

			}

			$id                  = false;
			$template_part_posts = get_posts(
				array(
					'post_type'   => 'wp_template_part',
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);
			// debug($template_part_posts);
			if ( ! empty( $template_part_posts ) ) {
				foreach ( $template_part_posts as $post ) {
					if ( $post->post_name == $atts['slug'] ) {
						$id = $post->ID;
						break;
					}
				}
			}

			if ( ! $id ) {
				// create post
				// $result = new \WP_Error( 422, 'create ...' );
				$result = wp_insert_post(
					array(
						'post_title'   => $atts['title'],
						'post_name'    => $atts['slug'],
						'post_content' => $atts['content'],
						'post_status'  => 'publish',
						'post_author'  => 1,
						'post_type'    => 'wp_template_part',
						'tax_input'    => array(
							'wp_theme'              => wp_get_theme()->get_stylesheet(),
							'wp_template_part_area' => $atts['slug'],
						),
						'meta_input'   => array(
							'origin' => 'theme',
						),
					),
					true // throw WP_Error on fail
				);
				if ( ! is_wp_error( $result ) ) {
					$id = $result;
				}
			} else {
				// update post
				// $result = new \WP_Error( 422, 'update '.$id.' ...' );
				$result = wp_update_post(
					array(
						'ID'           => $id,
						'post_content' => $atts['content'],
					)
				);
			}

			// check if there was an error updating
			if ( is_wp_error( $result ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error updating wp_template_part post "' . $id . '":';
				}
				$this->finish( 'error::' . $result->get_error_message() );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* wp_template_part post "' . $id . '" successfully updated.';
			}
			$this->finish( 'success::' . sprintf( __( 'Template Part %s successfully modified with new content', 'greyd_hub' ), '<strong>' . $atts['title'] . '</strong>' ) );
		}

		/**
		 * Convert WP Template
		 */
		if ( $mode === 'convert_template' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'CONVERT TEMPLATE';
			}

			$new_slug = false;
			$old_slug = explode( '-', $post_data['name'] );
			if ( $old_slug[0] == '404' ) {
				$new_slug = '404';
			} elseif ( $post_data['name'] == 'single' ) {
				$new_slug = 'single';
			} elseif ( $post_data['name'] == 'archives' ) {
				$new_slug = 'archive';
			} elseif ( $post_data['name'] == 'search' ) {
				$new_slug = 'search';
			}
			if ( ! $new_slug ) {
				// todo: cpt, categories, tags, taxonomies
				$this->finish( 'error::' . sprintf( __( "Template '%s' not valid.", 'greyd_hub' ), $post_data['name'] ) );
			}

			// get template content
			$inner = get_post_field( 'post_content', $post_data['id'] );
			// make new template
			$content = '<!-- wp:template-part {"slug":"header","theme":"greyd_hub","tagName":"header"} /-->' .
						'<!-- wp:group {"tagName":"main","layout":{"inherit":true,"type":"constrained"}} -->' .
						'<main class="wp-block-group">' . $inner . '</main>' .
						'<!-- /wp:group -->' .
						'<!-- wp:template-part {"slug":"footer","theme":"greyd_hub","tagName":"footer","className":"site-footer-container"} /-->';

			$id             = false;
			$template_posts = get_posts(
				array(
					'post_type'   => 'wp_template',
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);
			// debug($template_posts);
			if ( ! empty( $template_posts ) ) {
				foreach ( $template_posts as $post ) {
					if ( $post->post_name == $new_slug ) {
						$id = $post->ID;
						break;
					}
				}
			}

			if ( ! $id ) {
				// create post
				$types = get_default_block_template_types();
				// $result = new \WP_Error( 422, 'create ...' );
				$result = wp_insert_post(
					array(
						'post_title'   => $types[ $new_slug ]['title'],
						'post_name'    => $new_slug,
						'post_content' => $content,
						'post_excerpt' => $types[ $new_slug ]['description'],
						'post_status'  => 'publish',
						'post_author'  => 1,
						'post_type'    => 'wp_template',
						'tax_input'    => array(
							'wp_theme' => wp_get_theme()->get_stylesheet(),
						),
						'meta_input'   => array(
							'origin' => 'theme',
						),
					),
					true // throw WP_Error on fail
				);
				if ( ! is_wp_error( $result ) ) {
					$id = $result;
				}
			} else {
				// update post
				// $result = new \WP_Error( 422, 'update '.$id.' ...' );
				$result = wp_update_post(
					array(
						'ID'           => $id,
						'post_content' => $content,
					)
				);
			}

			// check if there was an error updating
			if ( is_wp_error( $result ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error updating wp_template post "' . $id . '":';
				}
				$this->finish( 'error::' . $result->get_error_message() );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* wp_template post "' . $id . '" successfully updated.';
			}
			$this->finish( 'success::' . sprintf( __( 'WP Template %s successfully modified with new content', 'greyd_hub' ), '<strong>' . $types[ $new_slug ]['title'] . '</strong>' ) );
		}

		/**
		 * Convert custom css
		 */
		if ( $mode === 'convert_custom_css' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'CONVERT CUSTOM CSS';
			}

			// convert custom css
			list( $id, $data ) = self::convert_custom_css( $post_data );

			/**
			 * Update styles post.
			 *
			 * @see https://developer.wordpress.org/reference/functions/wp_update_post/
			 * @return int|WP_Error
			 */
			// $result = new \WP_Error( 422, 'debugging ...' );
			$result = wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => wp_slash( wp_json_encode( $data ) ),
				)
			);

			// check if there was an error updating
			if ( is_wp_error( $result ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error updating custom css:';
				}
				$this->finish( 'error::' . $result->get_error_message() );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* custom css successfully updated.';
			}
			$this->finish( 'success::' . __( 'Custom CSS successfully modified with new values', 'greyd_hub' ) );
		}

		/**
		 * Delete Theme Mods
		 */
		if ( $mode === 'delete_theme_mods' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'DELETE THEME MODS';
			}

			$theme_mods_option = $post_data;
			$theme_mods = get_option( $theme_mods_option, false );

			if ( ! $theme_mods || empty( $theme_mods ) ) {
				$this->finish( 'error::' . __( 'No theme mods found', 'greyd_hub' ) );
			}

			$result = delete_option( $theme_mods_option );

			if ( ! $result ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error deleting theme mods:';
				}
				$this->finish( 'error::' . __( 'Unable to delete theme mods', 'greyd_hub' ) );
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* theme mods successfully deleted.';
			}
			$this->finish( 'success::' . __( 'Theme mods deleted', 'greyd_hub' ) );
		}

		/**
		 * Delete Nav Menus
		 */
		if ( $mode === 'delete_menus' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'DELETE NAV MENUS';
			}

			// Get all existing menus.
			$menus = wp_get_nav_menus();

			// Loop through and delete each menu.
			foreach ( $menus as $menu ) {
				$result = wp_delete_nav_menu( $menu->term_id );

				if ( is_wp_error( $result ) ) {
					if ( self::DEBUG ) {
						echo "\r\n" . '* error deleting menu:' . $result->get_error_message();
					}
					$this->finish( 'error::' . __( 'Unable to delete menu', 'greyd_hub' ) );
				}
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* menus successfully deleted.';
			}
			$this->finish( 'success::' . __( 'Menus deleted', 'greyd_hub' ) );
		}

		/**
		 * Delete Templates
		 */
		if ( $mode === 'delete_templates' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'DELETE TEMPLATES';
			}

			// Get all existing templates.
			$template_posts = get_posts(
				array(
					'post_type'   => 'dynamic_template',
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);

			if ( ! $template_posts ) {
				$this->finish( 'error::' . __( 'No templates found', 'greyd_hub' ) );
			}

			register_taxonomy( 'template_type', 'dynamic_template' );
			foreach ( $template_posts as $template ) {

				$terms = get_the_terms( $template->ID, 'template_type' );

				if ( ! $terms || ! is_array( $terms ) ) {
					continue;
				}

				$term  = array_shift( $terms );

				$type = $term->slug;

				if ( $type == 'dynamic' ) {
					continue;
				}

				$result = wp_delete_post( $template->ID, true );

				if ( is_wp_error( $result ) ) {
					if ( self::DEBUG ) {
						echo "\r\n" . '* error deleting template:' . $result->get_error_message();
					}
					$this->finish( 'error::' . __( 'Unable to delete template', 'greyd_hub' ) );
				}
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* templates successfully deleted.';
			}
			$this->finish( 'success::' . __( 'Templates deleted', 'greyd_hub' ) );
		}

		/**
		 * Delete Fonts
		 */
		if ( $mode === 'delete_fonts' ) {

			$uploads   = wp_upload_dir();
			$fonts_url = $uploads['basedir'].'/greyd_tp/custom_fonts';

			echo "\r\n" . '* delete: '.$fonts_url;

			self::delete_directory($fonts_url);

			if ( self::DEBUG ) {
				echo "\r\n" . '* fonts successfully deleted.';
			}
			$this->finish( 'success::' . __( 'Custom fonts deleted', 'greyd_hub' ) );
		}

		/**
		 * Activate Plugin
		 * greyd_tp_management and greyd_blocks
		 */
		if ( $mode === 'activate_plugin' ) {
			if ( self::DEBUG ) {
				echo "\r\n\r\n" . 'ACTIVATE PLUGIN';
			}

			// activate plugins
			$hub    = self::activate_plugin( 'greyd-plugin/init.php', 'https://update.greyd.io/public/plugins/greyd-plugin/greyd-plugin.zip' );

			// check if there was an error
			if ( is_wp_error( $hub ) ) {
				if ( self::DEBUG ) {
					echo "\r\n" . '* error activating plugin:';
				}
				if ( is_wp_error( $hub ) ) {
					$this->finish( 'error::' . $hub->get_error_message() );
				}
			}

			if ( self::DEBUG ) {
				echo "\r\n" . '* plugin successfully activated.';
			}
			$this->finish( 'success::' . __( 'Plugin activated', 'greyd_hub' ) );
		}

		/**
		 * Todo.
		 */
		if (
			$mode === 'convert_menus' ||
			// features
			$mode === 'convert_announcement' ||
			$mode === 'convert_cookiebar' ||
			$mode === 'convert_compatibility' ||
			$mode === 'convert_woocommerce' 
		) {
			$this->finish( 'error::' . __( 'Not implemented yet', 'greyd_hub' ) );
		}

		$this->finish( 'error::' . __( 'Unknown AJAX query', 'greyd_hub' ) );
	}

	/*
	 * Die and send answer back to JS
	 * basically the same as 'wp_die', but with debug logging
	 */
	public function finish( $msg = '' ) {
		if ( self::DEBUG ) {
			echo "\r\n\r\n" . '------------- debug end -------------' . "\r\n\r\n";
		}
		wp_die( $msg );
	}

	/**
	 * =================================================================
	 *                          AJAX Functions
	 * =================================================================
	 */

	public static function activate_plugin( $plugin_php, $plugin_url ) {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		// debug($all_plugins);

		$installed = true;
		if ( ! isset( $all_plugins[ $plugin_php ] ) ) {
			$installed = false;
			// get from url
			if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			wp_cache_flush();
			$upgrader  = new \Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
			$installed = $upgrader->install( $plugin_zip );
		}
		if ( $installed ) {
			// activate
			$activate = activate_plugin( $plugin_php );
			if ( is_null( $activate ) ) {
				// Done! Everything went smooth
				return true;
			}
		}
		// Could not activate the new plugin
		return new \WP_Error( 422, 'unable to activate plugin: ' . $plugin_php );

	}

	/**
	 * =================================================================
	 *                          Convert Styles
	 * =================================================================
	 */

	public static function convert_styles( $mods ) {

		$global_styles_id = false;
		if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			$global_styles_id = \WP_Theme_JSON_Resolver_Gutenberg::get_user_global_styles_post_id();
		} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$global_styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		}

		if ( $global_styles_id ) {

			$content = get_post_field( 'post_content', $global_styles_id );
			$data    = json_decode( $content, true );

			// check if settings are set
			if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) || empty( $data['settings'] ) ) {
				// set default settings from theme.json
				if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
					$current_data = \WP_Theme_JSON_Resolver_Gutenberg::get_merged_data()->get_raw_data();
				} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
					$current_data = \WP_Theme_JSON_Resolver::get_merged_data()->get_raw_data();
				}

				$data = array_merge( $data, json_decode( json_encode( $current_data ), true ) );
			}

			foreach ( $mods as $section => $values ) {
				foreach ( $values as $mod ) {

					if ( ! empty( $mod['global-style'] ) ) {
						if ( is_string( $mod['global-style'] ) ) {
							$mod['global-style'] = array( $mod['global-style'] );
						}
						// debug($mod['global-style']);
						foreach ( $mod['global-style'] as $global_style ) {
							$data = self::set_global_styles_value( $data, $global_style, $mod['value'] );
						}
					}
				}
			}

			return array( $global_styles_id, $data );
		}
		return false;

	}

	/**
	 * Set value theme data for Setting
	 *
	 * @param object $data json_decoded raw global_styles post content.
	 * @param string $path Path to Setting in theme.json.
	 * @param string $value Value to set.
	 *
	 * @return object   $data with updated setting value.
	 */
	public static function set_global_styles_value( $data, $path, $value ) {

		if ( strpos( $path, 'settings|typography|fontFamilies|' ) === 0 ) {

			$path = explode( '|', $path );
			// set fontfamily
			$data  = self::check_global_styles_settings( $data, array( 'settings', 'typography', 'fontFamilies' ) );
			$slug  = $path[ count( $path ) - 1 ];
			$fonts = isset( $data['settings']['typography']['fontFamilies']['theme'] ) ? $data['settings']['typography']['fontFamilies']['theme'] : array();
			for ( $i = 0; $i < count( $fonts ); $i++ ) {
				if ( $fonts[ $i ]['slug'] == $slug ) {

					// adjust value
					if ( empty( $value ) ) {
						break;
					}
					
					if ( strpos( $value, ":" ) !== false ) {
						$value = explode( ":", $value )[0];
					}
					$values = explode( ",", $value );
					foreach ( $values as $key => $val ) {
						if ( $val == "serif" || $val == "sans-serif" || $val == "monospace" || $val == "system-ui" ) {
							continue;
						}
						if ( strpos( $value, "\"" ) === false && strpos( $value, "'" ) === false ) {
							$value = "'" . $value . "'";
						}
						$values[ $key ] = $value;
					}
					$value = implode( ",", $values ) . ", system-ui, sans-serif";

					// set theme value
					$data['settings']['typography']['fontFamilies']['theme'][ $i ]['fontFamily'] = $value;
					unset( $data['settings']['typography']['fontFamilies']['theme'][ $i ]['fontFace'] );

					// todo: custom font
					// $font = Fonts::get_font_style( $value );
					// $data['settings']['typography']['fontFamilies']['theme'][ $i ]['fontFamily'] = $font['fontFamily'];
					// if ( $font['fontFace'] ) {
					// $data['settings']['typography']['fontFamilies']['theme'][ $i ]['fontFace'] = $font['fontFace'];
					// }
					// else {
					// unset( $data['settings']['typography']['fontFamilies']['theme'][ $i ]['fontFace'] );
					// }
					break;
				}
			}
		} elseif ( strpos( $path, 'settings|color|palette|' ) === 0 ) {

			$path = explode( '|', $path );
			
			// make sure data has palette
			$data    = self::check_global_styles_settings( $data, array( 'settings', 'color', 'palette' ) );

			// get slug
			$slug    = $path[ count( $path ) - 1 ];
			$path    = array_slice( $path, 0, count( $path ) - 1 );

			// get palette
			$palette = self::array_get( $data, $path ) ?? array();

			$isset = false;
			for ( $i = 0; $i < count( $palette ); $i++ ) {
				if ( $palette[ $i ]['slug'] == $slug ) {

					// set color value
					self::array_set( $data, array_merge( $path, array( $i, 'color' ) ), $value );
					$isset = true;
					break;
				}
			}

			if ( ! $isset ) {
				// add new color
				$palette[] = array(
					'slug'  => $slug,
					'name'  => ucfirst( str_replace( 'custom-', '', $slug ) ),
					'color' => $value,
				);
				self::array_set( $data, $path, $palette );
			}

		} else {
			$path = explode( '|', $path );
			// set other value
			$data = self::set_global_styles_settings( $data, $path, $value );
		}

		return $data;
	}

	/**
	 * Set value theme data for Setting
	 *
	 * @param object $data json_decoded raw global_styles post content.
	 * @param array  $path Path to Setting in theme.json.
	 * @param string $value Value to set.
	 * @return object   $data with updated setting value.
	 */
	public static function set_global_styles_settings( $data, $path, $value ) {

		// convert value to fse
		$value = self::convert_global_styles_value( $data, $path, $value );
		// Check theme data for Setting
		// $data = self::check_global_styles_settings( $data, $path );

		self::array_set( $data, $path, $value );
		return $data;
	}

	/**
	 * Check theme data for Setting
	 * add default from theme.json if not present.
	 *
	 * @param object $data json_decoded raw global_styles post content.
	 * @param array  $path Path to Setting in theme.json.
	 * @return object   $data with added default setting.
	 */
	public static function check_global_styles_settings( $data, $path ) {
		// var_error_log($data);
		// var_error_log($path);

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$search = $data;
		for ( $i = 0; $i < count( $path ); $i++ ) {
			if ( ! isset( $search[ $path[ $i ] ] ) ) {
				if ( $i == count( $path ) - 1 ) {
					$search[ $path[ $i ] ] = self::get_default_settings( $path );
				} else {
					$search[ $path[ $i ] ] = array();
				}
			}
			$search = $search[ $path[ $i ] ];
		}

		return $data;
	}

	/**
	 * Get default values from theme data (theme.json)
	 *
	 * @param array $path Path to Setting in theme.json.
	 * @return object   Default Values for Setting or empty object.
	 */
	public static function get_default_settings( $path ) {

		if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			$data = \WP_Theme_JSON_Resolver_Gutenberg::get_theme_data()->get_data();
		} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$data = \WP_Theme_JSON_Resolver::get_theme_data()->get_data();
		}

		if ( empty( $data ) ) {
			$data = array();
		} else {
			$data = json_decode( json_encode( $data ), true );
		}

		if ( ! empty( $data ) ) {
			$error = false;
			// debug($data);
			for ( $i = 0; $i < count( $path ); $i++ ) {
				if ( isset( $data[ $path[ $i ] ] ) ) {
					$data = $data[ $path[ $i ] ];
				} elseif ( isset( $data[ $path[ $i ] ] ) ) {
					$data = $data[ $path[ $i ] ];
				} else {
					$error = true;
					break;
				}
			}
			if ( ! $error ) {
				return $data;
			}
		}

		return array();
	}


	public static function convert_global_styles_value( $data, $path, $value ) {

		if ( in_array( 'fontFamily', $path ) && strpos( $value, 'fontFamily' ) === 0 ) {
			if ( $value == 'fontFamily1' ) {
				$value = 'var(--wp--preset--font-family--body)';
			}
			if ( $value == 'fontFamily2' ) {
				$value = 'var(--wp--preset--font-family--heading)';
			}
			if ( $value == 'fontFamily3' ) {
				$value = 'var(--wp--preset--font-family--highlight)';
			}
		} elseif ( in_array( 'color', $path ) && strpos( $value, 'color' ) === 0 ) {
			if ( $value == 'color11' ) {
				$value = 'var(--wp--preset--color--primary)';
			}
			if ( $value == 'color12' ) {
				$value = 'var(--wp--preset--color--secondary)';
			}
			if ( $value == 'color13' ) {
				$value = 'var(--wp--preset--color--tertiary)';
			}
			if ( $value == 'color31' ) {
				$value = 'var(--wp--preset--color--foreground)';
			}
			if ( $value == 'color21' ) {
				$value = 'var(--wp--preset--color--dark)';
			}
			if ( $value == 'color32' ) {
				$value = 'var(--wp--preset--color--mediumdark)';
			}
			if ( $value == 'color22' ) {
				$value = 'var(--wp--preset--color--mediumlight)';
			}
			if ( $value == 'color33' ) {
				$value = 'var(--wp--preset--color--base)';
			}
			if ( $value == 'color23' ) {
				$value = 'var(--wp--preset--color--background)';
			}
			// if ( $value == "color41" ) $value = "var(--wp--preset--color--";
			// if ( $value == "color51" ) $value = "var(--wp--preset--color--";
			// if ( $value == "color42" ) $value = "var(--wp--preset--color--";
			// if ( $value == "color52" ) $value = "var(--wp--preset--color--";
			// if ( $value == "color43" ) $value = "var(--wp--preset--color--";
			// if ( $value == "color53" ) $value = "var(--wp--preset--color--";
			if ( $value == 'color61' ) {
				$value = 'var(--wp--preset--color--darkest)';
			}
			if ( $value == 'color62' ) {
				$value = 'var(--wp--preset--color--lightest)';
			}
			if ( $value == 'color63' ) {
				$value = 'var(--wp--preset--color--transparent)';
			}
		} elseif ( in_array( 'lineHeight', $path ) ) {
			$value = intval( $value ) / 100.0;
		} elseif ( in_array( 'shadow', $path ) ) {
			// debug($path);
			// debug($value);
			if ( strpos( $value, '+' ) === false ) {
				// first: enable/disable switch
				$value = empty( $value ) ? 'off' : 'on';
			} elseif ( strpos( $value, '+' ) > 0 ) {
				// then: value
				// get first value (enable/disable)
				$val = $data;
				foreach ( $path as $p ) {
					$val = $val[ $p ];
				}
				if ( $val == 'off' ) {
					$value = 'none';
				} else {
					// convert value
					$tmp = explode( '+', $value );
					if ( count( $tmp ) > 6 ) {
						$inset = 'inset';
						array_shift( $tmp );
					}
					list( $x, $y, $blur, $spread, $color, $a ) = $tmp;
					// get color
					if ( strpos( $color, 'color' ) === 0 ) {
						$color = self::convert_global_styles_value( $data, array( 'color' ), $color );
						$color = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
						foreach ( $data['settings']['color']['palette']['theme'] as $col ) {
							if ( $col['slug'] == $color ) {
								$color = $col['color'];
								break;
							}
						}
					}
					if ( $color == 'transparent' || $color == '' ) {
						$color = array(
							'r' => 0,
							'g' => 0,
							'b' => 0,
							'a' => 0,
						);
					} elseif ( strpos( $color, '#' ) === 0 ) {
						$color = self::hex2RGB( $color );
					} elseif ( strpos( $color, 'rgb' ) === 0 ) {
						$color = self::rgb2RGB( $color );
					}
					// make alpha
					if ( ! isset( $color['a'] ) ) {
						$color['a'] = 1;
					}
					$color['a'] = $color['a'] * ( $a / 100.0 );

					$value  = isset( $inset ) && $inset == 'inset' ? 'inset ' : '';
					$value .= $x . ' ' . $y . ' ' . $blur . ' ' . $spread . ' ';
					$value .= 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ',' . $color['a'] . ')';
					// debug($value);
				}
			}
		}

		return $value;
	}

	/**
	 * Convert Color hex string to rgb array.
	 *
	 * @param string $hex   Color string ( '#xxx' or '#xxxxxx' )
	 * @return array $rgb   Color as array/object with properties r,g,b
	 */
	public static function hex2RGB( $hex ) {
		// Regexp for a valid hex digit
		$d = '[a-fA-F0-9]';
		if ( preg_match( "/^#?($d$d)($d$d)($d$d)\$/", $hex, $rgb ) ) { // #rrggbb
			return array(
				'r' => hexdec( $rgb[1] ),
				'g' => hexdec( $rgb[2] ),
				'b' => hexdec( $rgb[3] ),
			);
		}
		if ( preg_match( "/^#?($d)($d)($d)$/", $hex, $rgb ) ) { // #rgb
			return array(
				'r' => hexdec( $rgb[1] . $rgb[1] ),
				'g' => hexdec( $rgb[2] . $rgb[2] ),
				'b' => hexdec( $rgb[3] . $rgb[3] ),
			);
		}
		return false;
	}

	/**
	 * Convert Color rgb string to rgba array.
	 *
	 * @param string $rgb   Color string ( 'rgb(xx,xx,xx)' or 'rgba(xx,xx,xx,x.x)' )
	 * @return array $rgb   Color as array/object with properties r,g,b,a
	 */
	public static function rgb2RGB( $rgb ) {
		$tmp = explode( '(', $rgb );
		$tmp = explode( ')', $tmp[1] );
		$tmp = explode( ',', $tmp[0] );
		if ( count( $tmp ) == 3 ) {
			return array(
				'r' => trim( $tmp[0] ),
				'g' => trim( $tmp[1] ),
				'b' => trim( $tmp[2] ),
				'a' => 1,
			);
		}
		if ( count( $tmp ) == 4 ) {
			return array(
				'r' => trim( $tmp[0] ),
				'g' => trim( $tmp[1] ),
				'b' => trim( $tmp[2] ),
				'a' => trim( $tmp[3] ),
			);
		}
		return false;
	}

	/**
	 * =================================================================
	 *                          Convert Fonts
	 * =================================================================
	 */

	public static function convert_fonts( $old_fonts ) {

		// if WP_Font_Family_Utils is not available, the conversion should be aborted
		// the functions needed for saving to the new font library are only available in the Gutenberg plugin
		// if ( class_exists( 'WP_Font_Family_Utils' ) ) {
		// 	echo "\r\n" . '* WP_Font_Family_Utils class exists. Gutenberg plugin is active.';
		// } else {
		// 	echo "\r\n" . '* WP_Font_Family_Utils class does not exist. Please install and activate the Gutenberg plugin, at least version 17.6 RC2.';
		// 	return;
		// }

		// init new fonts
		$new_fontfamilies = array();
		// debug($old_fonts);

		// convert custom fonts
		$uploads   = wp_upload_dir();
		$fonts_url = $uploads['basedir'].'/greyd_tp/custom_fonts/';
		foreach ( $old_fonts['custom'] as $font ) {

			// read fonts
			if ( isset($font["file"]) && file_exists($fonts_url.$font["file"]) ) {
				
				$raw = file_get_contents( $fonts_url.$font["file"] );
				// debug($font);
				// debug(htmlspecialchars($raw));

				preg_match_all( '/@font-face ?{(?<fontface>[^}]*?)(?>}|\z)/', $raw, $font_faces );
				// debug($font_faces['fontface']);

				foreach ( $font_faces['fontface'] as $font_face ) {
					if ( strpos( $font_face, 'font-family' ) === false ) continue;
			
					// parse font-face rules
					$expressions = array(
						'font-family: \'(?<fontFamily>.*?)\'\;',
						'font-style: (?<fontStyle>.*?)\;',
						'font-weight: (?<fontWeight>.*?);',
						'url\((?<src>.*?)\)',
					);
					preg_match_all( '/'.implode('|', $expressions).'/', $font_face, $matches );
					// filter groups
					$matches = array_intersect_key($matches, array_flip(array('fontFamily', 'fontStyle', 'fontWeight', 'src')));
					// clean matches
					$matches = array_map( function($value) { 
						// remove empty and re-index
						$value = array_values(array_filter($value));
						// flatten
						if ( count($value) == 0 ) $value = false;
						else if ( count($value) == 1 ) $value = $value[0];
						// return
						return $value;
					}, $matches);
					// debug($matches);

					// add result
					if ( isset($matches['fontFamily']) && isset($matches['src']) ) {
						// $slug = slugify($matches['fontFamily']);
						$slug = preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '-', strtolower($matches['fontFamily']) ) );
						if ( !isset($new_fontfamilies[$slug]) ) {
							// add font-family
							$new_fontfamilies[$slug] = array(
								'name' => $matches['fontFamily'],
								'slug' => $slug,
								'fontFamily' => $matches['fontFamily'],
								'fontFace' => array()
							);
						}
						// copy old src
						$dir = dirname( $fonts_url.$font["file"] );
						if ( is_array($matches['src']) ) {
							$matches['src'] = $matches['src'][0];
						}
						$matches['src'] = $dir."/".trim($matches['src'], "'");
						if ( !isset($matches['fontWeight']) ) $matches['fontWeight'] = '400';
						if ( !isset($matches['fontStyle']) ) $matches['fontStyle'] = 'normal';
						$font_face_new = \Greyd\Theme\Fonts::copy_font_file( $matches );
						// add font-face
						$new_fontfamilies[$slug]['fontFace'][] = $font_face_new;
					}
			
				}
			}
		}

		// convert google fonts
		foreach ( $old_fonts['google'] as $font ) {

			$tmp     = explode( ":", $font );
			$name    = $tmp[0];
			$weights = isset($tmp[1]) ? $tmp[1] : "";

			if ( ! empty( $weights ) ) {
				// selected weights
				$raw_weights = explode( ',', $weights );
				// debug($raw_weights);
				$normal = $italic = array();
				foreach ( $raw_weights as $weight ) {
					if ( strpos($weight, "italic") > 0 ) {
						$italic[] = str_replace("italic", "", $weight);
					}
					else $normal[] = $weight;
				}
				if ( count($normal) > 0 && count($italic) == 0 ) {
					$weights = ":wght@".implode(';', $normal);
				}
				else {
					$all = array();
					if ( count($normal) > 0 ) {
						$all[] = "0,".implode(';0,', $normal);
					}
					if ( count($italic) > 0 ) {
						$all[] = "1,".implode(';1,', $italic);
					}
					$weights = ":ital,wght@".implode(';', $all);
				}
			}
			// debug($name);
			// debug($weights);

			$google_font = \Greyd\Theme\Fonts::get_font_style( $name.$weights );
			if ( $google_font["fontFace"] ) {
				// debug($google_font);
				$fontname = str_replace("'", "", $google_font['fontFamily']);
				$fontslug = preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '-', strtolower($fontname) ) );
				$fontfamily = array(
					'name' => $fontname,
					'slug' => $fontslug,
					'fontFamily' => $fontname,
					'fontFace' => array()
				);
				foreach ( $google_font['fontFace'] as $fontface ) {
					// debug($fontface);
					$fontface_new = \Greyd\Theme\Fonts::download_font_file( $fontface );
					if ( is_object($fontface_new) ) {
						array_push( $fontfamily['fontFace'], $fontface_new );
					}
				}
				if ( !empty($fontfamily['fontFace']) ) {
					// debug($fontfamily);
					$new_fontfamilies[$fontslug] = $fontfamily;
				}
			}

		}

		// convert web fonts
		foreach ( $old_fonts['websafe'] as $font ) {
			$slug = str_replace( [ "\"", " " ], [ "", "-" ], strtolower(explode(", ", $font)[0]) );
			$fontfamily = str_replace("\"", "'", $font);
			$new_font = array(
				'name'       => $fontfamily,
				'slug'       => $slug,
				'fontFamily' => $fontfamily,
			);
			// debug($new_font);
			$new_fontfamilies[$slug] = $new_font;

			// $new_font = array();
			// // prepare the slug
			// $get_first_font = str_replace("\"", "", explode(", ", $font)[0]);
			// $slug = str_replace(" ", "-", strtolower($get_first_font));

			// // create array with data, replace double quotes with single quotes to avoid json errors
			// $new_font = array(
			// 	'name'       => str_replace("\"", "'", $font),
			// 	'slug'       => $slug,
			// 	'fontFamily' => str_replace("\"", "'", $font),
			// );

			// // add the font as a wp_font_family post type
			// $request = new \WP_REST_Request( 'POST', '/wp/v2/font-families' );
			// $request->set_header( 'content-type', 'application/json' );
			// $request->set_param( 'font_family_settings', wp_json_encode( $new_font ) );
			// $response = rest_do_request( $request );
			// $server = rest_get_server();
			// $data = $server->response_to_data( $response, false );

			// // until here it only adds the font to the library, but it does not "activate" it, aka adding it to the custom styles
			// // in $data, with the key "font_family_settings" is the content for adding it to the custom styles

			// if ( isset( $data['font_family_settings'] ) ) {
			// 	self::add_fonts_to_custom_styles( $new_font );
			// }
		}
		
		debug($new_fontfamilies);
		$result = "";
		if ( !empty($new_fontfamilies) ) {

			// insert data
			$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
			$content = get_post_field( 'post_content', $post_id );
			$data    = json_decode( $content );
			// init custom fonts
			if ( !isset($data->settings->typography->fontFamilies->custom) ) {
				$data->settings->typography->fontFamilies->custom = array();
			}
			// debug($data);

			$result = array();
			foreach ( $new_fontfamilies as $slug => $fontfamily ) {
				$family = (object)$fontfamily;
				$result[] = $family->name;
				// add or update custom styles
				$found = false;
				foreach ( $data->settings->typography->fontFamilies->custom as $i => $font ) {
					if ( $font->name == $family->name && $font->slug == $family->slug ) {
						$found = true;
						$data->settings->typography->fontFamilies->custom[$i] = $family;
					}
				}
				if ( !$found) array_push($data->settings->typography->fontFamilies->custom, $family);
				// insert or update wp_font_family post
				\Greyd\Theme\Fonts::create_font_post($family);
			}
			// debug($data);

			// save global-styles post
			debug("save global-styles post");
			// debug($data->settings->typography->fontFamilies);
			
			$new_content = json_encode( $data );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash($new_content),
				)
			);

			// return $post_id;
			$result = '"' . implode('", "', $result) . '"';

		}

		return $result;
		
	}

	// public static function add_fonts_to_custom_styles( $new_font ) {
	// 	// if the font got added successfully, add it to the custom styles
	// 	// get the ID of the current Custom Styles post first
	// 	$global_styles_post = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();

	// 	// fetch the current Custom Styles post
	// 	$request = new \WP_REST_Request( 'GET', '/wp/v2/global-styles/' . $global_styles_post );
	// 	$response = rest_do_request( $request );
	// 	$server = rest_get_server();
	// 	$data = $server->response_to_data( $response, false );
		
	// 	// add the new font to the custom styles
	// 	if ( isset( $data['settings']['typography']['fontFamilies']['custom'] ) ) {
	// 		$data['settings']['typography']['fontFamilies']['custom'][] = $new_font;
	// 	}

	// 	// save the updated Custom Styles post
	// 	$global_styles_controller = new \WP_REST_Global_Styles_Controller();
	// 	$update_request           = new \WP_REST_Request( 'PUT', '/wp/v2/global-styles/' );
	// 	$update_request->set_param( 'id', $global_styles_post );
	// 	$update_request->set_param( 'settings', $data['settings'] );
	// 	$updated_global_styles = $global_styles_controller->update_item( $update_request );
	// }

	public static function convert_custom_css( $custom_css_string ) {

		$global_styles_id = false;
		if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
			$global_styles_id = \WP_Theme_JSON_Resolver_Gutenberg::get_user_global_styles_post_id();
		} elseif ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$global_styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		}

		if ( $global_styles_id ) {

			$content = get_post_field( 'post_content', $global_styles_id );
			$data    = json_decode( $content, true );

			// set as ["styles"]["css"]
			if ( ! isset( $data['styles'] ) ) {
				$data['styles'] = array();
			}
			$data['styles']['css'] = $custom_css_string;

			return array( $global_styles_id, $data );
		}
		return false;
	}

	/**
	 * Delete directory.
	 * 
	 * @param string $dir_path			Path of directory to delete.
	 * @param array|string $exclude		Array of Paths to not delete.
	 */
	public static function delete_directory( $dir_path, $exclude=[] ) {
		
		if (is_string($exclude)) $exclude = array($exclude);

		$dir_path = trailingslashit( $dir_path );

		// $files = glob($dir_path.'*', GLOB_MARK);
		$files = glob($dir_path.'{,.}*', GLOB_BRACE);
		$del = true;
		foreach ($files as $file) {
			$tmp = explode('/', $file);
			if ($tmp[count($tmp)-1] == ".." || $tmp[count($tmp)-1] == ".") continue;
			if (is_array($exclude) && !empty($exclude)) {
				$skip = false;
				foreach ($exclude as $ex) {
					if ($ex != "" && strpos($file, $ex) === 0) {
						$del = false;
						$skip = true;
						break;
					}
				}
				if ($skip) continue;
			}
			if (is_dir($file)) self::delete_directory( $file, $exclude );
			else unlink($file);
		}
		
		$dir_path = untrailingslashit( $dir_path );

		if ( is_dir($dir_path) && $del ) {
			rmdir( $dir_path );
		}
	}

	/**
	 * =================================================================
	 *                          Convert Header and Footer
	 * =================================================================
	 */

	public static function convert_header( $data ) {

		// template data
		$title = 'Header';
		$slug  = 'header';

		// todo: menus/navigation, layout

		$logo_atts          = self::convert_group_data( $data['theme_mods']['navi_header'], 'navi_header_logo' );
		$logo_atts['inner'] = '<!-- wp:site-logo {"align":"left","style":{"layout":{"selfStretch":"fixed","flexSize":"40px"}}} /-->';
		$logo               = self::render_group( $logo_atts );

		$navi_atts          = self::convert_group_data( $data['theme_mods']['navi_header'], 'navi_header_menu' );
		$navi_atts['inner'] = '<!-- wp:navigation /-->';
		$navi               = self::render_group( $navi_atts );

		$burger_atts          = self::convert_group_data( $data['theme_mods']['navi_header'], 'navi_header_burger' );
		$burger_atts['inner'] = '<!-- wp:greyd/popover -->
				<div class="wp-block-greyd-popover">

					<!-- wp:greyd/popover-button {
						"variation":"burger",
						"greydClass":"gs_H9PcFF",
						"burgerStyles":{
							"\u002d\u002dburger-width":"20px",
							"\u002d\u002dburger-stroke":"2px",
							"\u002d\u002dburger-gap":"3px"
						},
						"align":"center"
					} -->
					<button class="wp-block-greyd-popover-button aligncenter greyd-burger-btn gs_H9PcFF" tabindex="0" role="button" aria-expanded="false" aria-label="" aria-controls="popover-ID">
						<span class="greyd-burger greyd-burger--squeeze ">
							<span class="greyd-burger-inner"></span>
						</span>
					</button>
					<style class="greyd-styles">
						.gs_H9PcFF { 
							--burger-width: 20px; 
							--burger-stroke: 2px; 
							--burger-gap: 3px; 
						} 
					</style>
					<!-- /wp:greyd/popover-button -->
					
					<!-- wp:greyd/popover-popup {
						"variation":"offcanvas",
						"greydClass":"gs_fap7ED"
					} -->
					<div class="wp-block-greyd-popover-popup  gs_fap7ED">
						<div id="popover-ID" role="dialog" class="is-variation-offcanvas is-position-default ">
							<button class="popover-close-button" tabindex="0" role="button" aria-expanded="false" aria-controls="popover-ID"></button>
						</div>
						<div class="dialog-backdrop"></div>
					</div>
					<!-- /wp:greyd/popover-popup -->

				</div>
				<!-- /wp:greyd/popover -->';
		$burger               = self::render_group( $burger_atts );

		// group data
		debug( $data );
		$atts = self::convert_group_data( $data['theme_mods']['navi_header'], 'navi_header' );
		// group inner
		$atts['inner'] = $logo . $navi . $burger;
		$content       = self::render_group( $atts );

		return array(
			'title'   => $title,
			'slug'    => $slug,
			'content' => $content,
		);
	}

	public static function convert_footer( $data ) {

		// template data
		$title = 'Footer';
		$slug  = 'footer';

		// group data
		$atts = self::convert_group_data( $data['theme_mods']['navi_footer'], 'navi_footer' );
		// group inner
		if ( $atts['inner'] == 'template' && isset( $data['templates']['footer'] ) ) {
			// get template content
			$atts['inner'] = get_post_field( 'post_content', $data['templates']['footer']['id'] );
		} else {
			// make menu/navigation
			$atts['inner'] = '<!-- wp:navigation /-->';
		}
		$content = self::render_group( $atts );

		return array(
			'title'   => $title,
			'slug'    => $slug,
			'content' => $content,
		);
	}

	public static function convert_group_data( $data, $prefix ) {

		// group data
		// $atts = array( "layout" => array( "inherit" => true,"type" => "constrained" ) );
		$atts    = array();
		$classes = array( 'wp-block-group' );
		$styles  = array();
		$inner   = '';

		if ( $prefix == 'navi_footer' ) {
			$atts['layout'] = array(
				'inherit' => true,
				'type'    => 'constrained',
			);
		} elseif ( $prefix == 'navi_header' ) {
			$atts['layout'] = array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'space-between',
				'verticalAlignment' => 'stretch',
			);
		} else {
			$atts['layout'] = array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'justifyContent' => 'center',
			);
		}
		if ( $prefix == 'navi_header_menu' ) {
			$atts['style'] = array( 'layout' => array( 'selfStretch' => 'fill' ) );
		}

		$more   = false;
		$border = false;
		$shadow = false;
		foreach ( $data as $mod ) {
			if ( $mod['mod'] == $prefix . '_content' ) {
				$inner = $mod['value'];
			}
			if ( $mod['mod'] == $prefix . '_more' && $mod['value'] ) {
				$more = true;
			}
			if ( $mod['mod'] == $prefix . '_border_enable' && $mod['value'] ) {
				$border = true;
			}
			if ( $mod['mod'] == $prefix . '_shadow_enable' && $mod['value'] ) {
				$shadow = true;
			}
		}
		foreach ( $data as $mod ) {
			// colors
			if ( $mod['mod'] == $prefix . '_background_color2' ) {
				$color                   = self::convert_global_styles_value( null, array( 'color' ), $mod['value'] );
				$color                   = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
				$atts['backgroundColor'] = $color;
				$classes[]               = 'has-background';
				$classes[]               = 'has-' . $color . '-background-color';
			}
			if ( $mod['mod'] == $prefix . '_text_color2' ) {
				$color             = self::convert_global_styles_value( null, array( 'color' ), $mod['value'] );
				$color             = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
				$atts['textColor'] = $color;
				$classes[]         = 'has-text-color';
				$classes[]         = 'has-' . $color . '-color';
			}
			if ( $mod['mod'] == $prefix . '_item_text_color2' ) {
				$color = self::convert_global_styles_value( null, array( 'color' ), $mod['value'] );
				$color = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['elements'] ) ) {
					$atts['style']['elements'] = array();
				}
				if ( ! isset( $atts['style']['elements']['link'] ) ) {
					$atts['style']['elements']['link'] = array();
				}
				$atts['style']['elements']['link']['color'] = array( 'text' => 'var:preset|color|' . $color );
				if ( ! in_array( 'has-link-color', $classes ) ) {
					$classes[] = 'has-link-color';
				}
			}
			if ( $mod['mod'] == $prefix . '_item_text_hover_color2' ) {
				$color = self::convert_global_styles_value( null, array( 'color' ), $mod['value'] );
				$color = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['elements'] ) ) {
					$atts['style']['elements'] = array();
				}
				if ( ! isset( $atts['style']['elements']['link'] ) ) {
					$atts['style']['elements']['link'] = array();
				}
				$atts['style']['elements']['link'][':hover']['color'] = array( 'text' => 'var:preset|color|' . $color );
				if ( ! in_array( 'has-link-color', $classes ) ) {
					$classes[] = 'has-link-color';
				}
			}
			// -> more
			if ( $more ) {
				// border
				if ( $border ) {
					if ( $mod['mod'] == $prefix . '_border_radius' ) {
						if ( ! isset( $atts['style'] ) ) {
							$atts['style'] = array();
						}
						if ( ! isset( $atts['style']['border'] ) ) {
							$atts['style']['border'] = array();
						}
						$atts['style']['border']['radius'] = $mod['value'];
						$styles['border-radius']           = $mod['value'];
					}
					if ( $mod['mod'] == $prefix . '_border_color2' ) {
						$color               = self::convert_global_styles_value( null, array( 'color' ), $mod['value'] );
						$color               = str_replace( array( 'var(--wp--preset--color--', ')' ), '', $color );
						$atts['borderColor'] = $color;
						$classes[]           = 'has-border-color';
						$classes[]           = 'has-' . $color . '-border-color';
					}
					if ( $mod['mod'] == $prefix . '_border_width' ) {
						if ( ! isset( $atts['style'] ) ) {
							$atts['style'] = array();
						}
						if ( ! isset( $atts['style']['border'] ) ) {
							$atts['style']['border'] = array();
						}
						$atts['style']['border']['width'] = $mod['value'];
						$styles['border-width']           = $mod['value'];
					}
					if (
						$mod['mod'] == $prefix . '_border_style_top' ||
						$mod['mod'] == $prefix . '_border_style_left' ||
						$mod['mod'] == $prefix . '_border_style_right' ||
						$mod['mod'] == $prefix . '_border_style_bottom'
					) {
						if ( ! isset( $atts['style'] ) ) {
							$atts['style'] = array();
						}
						if ( ! isset( $atts['style']['border'] ) ) {
							$atts['style']['border'] = array();
						}
						$atts['style']['border']['style'] = $mod['value'];
						$styles['border-style']           = $mod['value'];
					}
				}
				// spacings
				if ( $mod['mod'] == $prefix . '_margin_bottom' ) {
					if ( ! isset( $atts['style'] ) ) {
						$atts['style'] = array();
					}
					if ( ! isset( $atts['style']['spacing'] ) ) {
						$atts['style']['spacing'] = array();
					}
					$atts['style']['spacing']['margin'] = array(
						'top'    => '0px',
						'bottom' => $mod['value'],
					);
					$styles['margin-top']               = '0px';
					$styles['margin-bottom']            = $mod['value'];
				}
				if ( $mod['mod'] == $prefix . '_topbottom' ) {
					if ( ! isset( $atts['style'] ) ) {
						$atts['style'] = array();
					}
					if ( ! isset( $atts['style']['spacing'] ) ) {
						$atts['style']['spacing'] = array();
					}
					if ( ! isset( $atts['style']['spacing']['padding'] ) ) {
						$atts['style']['spacing']['padding'] = array();
					}
					$atts['style']['spacing']['padding']['top']    = $mod['value'];
					$atts['style']['spacing']['padding']['bottom'] = $mod['value'];
					$styles['padding-top']                         = $mod['value'];
					$styles['padding-bottom']                      = $mod['value'];
				}
				if ( $mod['mod'] == $prefix . '_leftright' ) {
					if ( ! isset( $atts['style'] ) ) {
						$atts['style'] = array();
					}
					if ( ! isset( $atts['style']['spacing'] ) ) {
						$atts['style']['spacing'] = array();
					}
					if ( ! isset( $atts['style']['spacing']['padding'] ) ) {
						$atts['style']['spacing']['padding'] = array();
					}
					$atts['style']['spacing']['padding']['left']  = $mod['value'];
					$atts['style']['spacing']['padding']['right'] = $mod['value'];
					$styles['padding-left']                       = $mod['value'];
					$styles['padding-right']                      = $mod['value'];
				}
			}
			// dimensions
			if ( $mod['mod'] == $prefix . '_height' ) {
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['dimensions'] ) ) {
					$atts['style']['dimensions'] = array();
				}
				$atts['style']['dimensions']['minHeight'] = $mod['value'];
				$styles['min-height']                     = $mod['value'];
			}

			// typography
			if ( $mod['mod'] == $prefix . '_item_fontfamily' ) {
				$font               = self::convert_global_styles_value( null, array( 'fontFamily' ), $mod['value'] );
				$font               = str_replace( array( 'var(--wp--preset--font-family--', ')' ), '', $font );
				$atts['fontFamily'] = $color;
				$classes[]          = 'has-' . $color . '-font-family';
			}
			if ( $mod['mod'] == $prefix . '_item_font_size' ) {
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['typography'] ) ) {
					$atts['style']['typography'] = array();
				}
				$atts['style']['typography']['fontSize'] = $mod['value'];
				$styles['font-size']                     = $mod['value'];
			}
			if ( $mod['mod'] == $prefix . '_item_font_weight' ) {
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['typography'] ) ) {
					$atts['style']['typography'] = array();
				}
				$atts['style']['typography']['fontWeight'] = $mod['value'];
				$styles['font-weight']                     = $mod['value'];
			}
			if ( $mod['mod'] == $prefix . '_item_line_height' ) {
				$value = self::convert_global_styles_value( null, array( 'lineHeight' ), $mod['value'] );
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['typography'] ) ) {
					$atts['style']['typography'] = array();
				}
				$atts['style']['typography']['lineHeight'] = $value;
				$styles['line-height']                     = $value;
			}
			if ( $mod['mod'] == $prefix . '_item_text_transform' ) {
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['typography'] ) ) {
					$atts['style']['typography'] = array();
				}
				$atts['style']['typography']['textTransform'] = $mod['value'];
				$styles['text-transform']                     = $mod['value'];
			}
			if ( $mod['mod'] == $prefix . '_item_letter_spacing' ) {
				if ( ! isset( $atts['style'] ) ) {
					$atts['style'] = array();
				}
				if ( ! isset( $atts['style']['typography'] ) ) {
					$atts['style']['typography'] = array();
				}
				$atts['style']['typography']['letterSpacing'] = $mod['value'];
				$styles['letter-spacing']                     = $mod['value'];
			}
		}

		$atts1    = array(
			'style'           => array(
				'border'     => array(
					'radius' => '89px',
					'width'  => '13px',
					'style'  => 'dotted',
				),
				'spacing'    => array(
					'padding' => array(
						'top'    => '32px',
						'bottom' => '32px',
						'left'   => '59px',
						'right'  => '59px',
					),
					'margin'  => array(
						'top'    => '34px',
						'bottom' => '34px',
					),
				),
				'elements'   => array(
					'link' => array(
						'color'  => array(
							'text' => 'var:preset|color|tertiary',
						),
						':hover' => array(
							'color' => array(
								'text' => 'var:preset|color|secondary',
							),
						),
					),
				),
				'typography' => array(
					'fontStyle'     => 'normal',
					'fontWeight'    => '100',
					'lineHeight'    => 1.6,
					'letterSpacing' => '4px',
					'textTransform' => 'uppercase',
				),
			),
			'fontFamily'      => 'highlight',
			'borderColor'     => 'primary',
			'backgroundColor' => 'base',
			'textColor'       => 'tertiary',
			'layout'          => array(
				'inherit' => true,
				'type'    => 'constrained',
			),
		);
		$classes1 = array(
			'wp-block-group',
			'has-border-color',
			'has-primary-border-color',
			'has-tertiary-color',
			'has-base-background-color',
			'has-text-color',
			'has-background',
			'has-link-color',
			'has-highlight-font-family',
		);
		$styles1  = array(
			'border-style'   => 'dotted',
			'border-width'   => '13px',
			'border-radius'  => '89px',

			'margin-top'     => '34px',
			'margin-bottom'  => '34px',
			'padding-top'    => '32px',
			'padding-right'  => '59px',
			'padding-bottom' => '32px',
			'padding-left'   => '59px',

			'font-style'     => 'normal',
			'font-weight'    => '100',
			'letter-spacing' => '4px',
			'line-height'    => '1.6',
			'text-transform' => 'uppercase',
		);

		return array(
			'attributes' => $atts,
			'classes'    => $classes,
			'styles'     => $styles,
			'inner'      => $inner,
		);
	}

	public static function render_group( $atts ) {

		// render group
		$css = array();
		foreach ( $atts['styles'] as $key => $value ) {
			$css[] = $key . ':' . $value;
		}
		$css     = empty( $css ) ? '' : ' style="' . implode( ';', $css ) . '"';
		$inner   = '<div class="' . implode( ' ', $atts['classes'] ) . '"' . $css . '>' . $atts['inner'] . '</div>';
		$content = '<!-- wp:group ' . json_encode( $atts['attributes'] ) . ' -->' . $inner . '<!-- /wp:group -->';

		// $content = '<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->'.
		// '<div class="wp-block-group">'.$post_data['templates']['footer']['content'][0].'</div>'.
		// '<!-- /wp:group -->';

		return $content;
	}

	public static function array_get( $arr, $path ) {
		if ( ! $path ) {
			return null;
		}

		$segments = is_array( $path ) ? $path : explode( '|', $path );
		$cur      =& $arr;
		foreach ( $segments as $segment ) {
			if ( ! isset( $cur[ $segment ] ) ) {
				return null;
			}

			$cur = $cur[ $segment ];
		}

		return $cur;
	}

	public static function array_set( &$arr, $path, $value ) {
		if ( ! $path ) {
			return null;
		}

		$segments = is_array( $path ) ? $path : explode( '|', $path );
		$cur      =& $arr;
		foreach ( $segments as $segment ) {
			if ( ! isset( $cur[ $segment ] ) ) {
				$cur[ $segment ] = array();
			}
			$cur =& $cur[ $segment ];
		}
		$cur = $value;
	}
}
