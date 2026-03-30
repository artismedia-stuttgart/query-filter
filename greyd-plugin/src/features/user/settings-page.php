<?php
/**
 * Admin page to manage WordPress login urls
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

	}

	/**
	 * Render login url settings page
	 */
	public function render_settings_page() {

		if ( !Manage::wp_up_to_date() ) {
			return;
		}

		// handle POST Data for saving
		if ( ! empty( $_POST ) && isset( $_POST['submit'] ) ) {
			$response = $this->save_data( $_POST );
			if ( $response ) {
				echo Helper::show_message( __( 'Settings saved.', 'greyd_hub' ), 'success inline' );
			} else {
				echo Helper::show_message( __( 'Unable to save settings.', 'greyd_hub' ), 'error inline' );
			}
		}

		// check if WPS Hide Login is active
		$locked = '';
		if ( Helper::is_active_plugin( 'wps-hide-login/wps-hide-login.php' ) ) {

			$locked = 'locked';

			// handle plugin deactivation
			if ( ! empty( $_GET ) && isset( $_GET['action'] ) && $_GET['action'] == 'deactivate_whl' ) {
				$response = $this->deactivate_plugin( $_GET );
				if ( $response ) {
					echo Helper::show_message( __( 'WPS Hide Login is deactivated.', 'greyd_hub' ), 'success inline' );
					$locked = '';
				} else {
					echo Helper::show_message( __( 'Unable to deactivate WPS Hide Login.', 'greyd_hub' ), 'error inline' );
				}
			}

			// if still locked, render notice to deactivate
			if ( $locked != '' ) {

				// deactivate url
				$query_args_delete = array(
					'page'     => wp_unslash( $_REQUEST['page'] ),
					'tab'      => 'settings',
					'action'   => 'deactivate_whl',
					'_wpnonce' => wp_create_nonce( 'greyd_users_deactivate_plugin' ),
				);
				$deactivate_url    = remove_query_arg( array( 'action', '_wpnonce' ) );
				$deactivate_url    = esc_url( add_query_arg( $query_args_delete, $deactivate_url ) );

				// default text to deactivate Plugin
				$text = sprintf(
					__( 'You can %1$sdeactivate%2$s the plugin to use the following settings.', 'greyd_hub' ),
					'<a href="' . $deactivate_url . '">',
					'</a>'
				);
				// $text .= "<br><strong><i>".__( 'If a custom Login URL was set, this value will be saved here.', 'greyd_hub' )."</i></strong>";
				// check if it is network wide active
				if ( is_multisite() ) {
					$network_current = get_site_option( 'active_sitewide_plugins', array() );
					// debug($network_current);
					if ( isset( $network_current['wps-hide-login/wps-hide-login.php'] ) ) {
						$text = '<strong><i>' . __( 'The plugin is activated network wide. Contact your network admin to savely deactivate it.', 'greyd_hub' ) . '</i></strong>';
					}
				}

				// make notice
				echo '<br>' . Helper::render_info_box(
					array(
						'style' => 'warning',
						'text'  => sprintf(
							__( 'These features can not be used because the plugin %s is installed and active.', 'greyd_hub' ) . '<br><br>',
							'<strong>WPS Hide Login</strong>'
						) . $text,
					)
				);

			}
		}

		// title
		echo '<h1>' . __( 'Login link settings', 'greyd_hub' ) . '</h1>';

		// form
		echo "<form id='settings-form' method='post' class='" . $locked . "'>";
		wp_nonce_field( 'greyd_users_settings' );

			// table
			echo "<table class='form-table settings_table'>";

				// worpress links debugging
				// debug(wp_login_url());
				// debug(wp_logout_url());
				// debug(wp_registration_url());
				// debug(wp_lostpassword_url());

				// get data
				$global_urls = Manage::get_global_settings( 'urls' );
				$urls        = Manage::get_settings( 'urls' );
				// debug($global_urls);
				// debug($urls);

				// check global setting and use as default
				$check_global = function( $global_urls, $slug, $link ) {
					if ( is_multisite() && ! is_network_admin() ) {
						$global_value = isset( $global_urls[ $slug ] ) ? $global_urls[ $slug ] : false;
						if ( $global_value ) {
							$link['data']['global'] = $global_value;
							$link['info']           = '<small>' . Helper::render_info_box(
								array(
									'text' => sprintf(
										__( 'Set to %1$s in %2$sNetwork User Settings%3$s.', 'greyd_hub' ),
										'<strong>' . $global_value . '</strong>',
										'<a href="' . network_admin_url( 'admin.php?page=greyd_user&tab=settings' ) . '" target="_blank">',
										'</a>'
									),
								)
							) . '</small>';
						}
					}
					$link['dataset'] = "data-default='" . $link['data']['default'] . "'";
					if ( isset( $link['data']['global'] ) ) {
						$link['dataset'] .= " data-global='" . $link['data']['global'] . "'";
					}
					return $link;
				};

				// default url
				$default_url = 'wp-login.php';
		if ( is_multisite() && ! is_network_admin() ) {
			if ( isset( $global_urls['login_url'] ) ) {
				$default_url = $global_urls['login_url'];
			}
		}
				$links     = array(
					'login_url'    => array(
						'title' => __( 'Login URL', 'greyd_hub' ),
						'value' => isset( $urls['login_url'] ) ? $urls['login_url'] : '',
						'data'  => array( 'default' => $default_url ),
						'info'  => '',
					),
					'logout_url'   => array(
						'title' => __( 'Logout URL', 'greyd_hub' ),
						'value' => isset( $urls['logout_url'] ) ? $urls['logout_url'] : '',
						'data'  => array( 'default' => ( isset( $urls['login_url'] ) ? $urls['login_url'] : $default_url ) . '?action=logout' ),
						'info'  => '',
					),
					'register_url' => array(
						'title' => __( 'Register URL', 'greyd_hub' ),
						'value' => isset( $urls['register_url'] ) ? $urls['register_url'] : '',
						'data'  => array( 'default' => ( isset( $urls['login_url'] ) ? $urls['login_url'] : $default_url ) . '?action=register' ),
						'info'  => '',
					),
					'reset_url'    => array(
						'title' => __( 'Password reset URL', 'greyd_hub' ),
						'value' => isset( $urls['reset_url'] ) ? $urls['reset_url'] : '',
						'data'  => array( 'default' => ( isset( $urls['login_url'] ) ? $urls['login_url'] : $default_url ) . '?action=lostpassword' ),
						'info'  => '',
					),
				);
				$redirects = array(
					'admin_redirect'     => array(
						'title' => __( 'If a not logged in user tries to access any admin screen', 'greyd_hub' ),
						'value' => isset( $urls['admin_redirect'] ) ? $urls['admin_redirect'] : '',
						'data'  => array( 'default' => 'login' ),
						'info'  => '',
					),
					'old_login_redirect' => array(
						'title' => __( 'If a new login URL is set and any user tries to access the old login screen', 'greyd_hub' ),
						'value' => isset( $urls['old_login_redirect'] ) ? $urls['old_login_redirect'] : '',
						'data'  => array( 'default' => 'login' ),
						'info'  => '',
					),
				);

				// get blacklist
				global $wp;
				$blacklist              = array_merge( $wp->public_query_vars, $wp->private_query_vars );
				$blacklisted_name_alert = '<div class="blacklisted_name_alert right">' . __( 'Attention! You have chosen a name used by WordPress. Conflicts can arise.', 'greyd_hub' ) . '</div>'; // Text ändern

				// render urls
				echo '<tr><th>' . __( 'Login links', 'greyd_hub' ) . "</th><td data-blacklist='" . json_encode( $blacklist ) . "'>";
				foreach ( $links as $slug => $link ) {
					$link = $check_global( $global_urls, $slug, $link );
					echo "<label for='" . $slug . "'>
							" . $link['title'] . ":<br>
							<strong style='text-decoration:underline'>" . home_url() . '/</strong>' .
							"<input class='url_input' type='text' name='" . $slug . "' id='" . $slug . "' " . $link['dataset'] . " placeholder='' value='" . $link['value'] . "' autocomplete='off'>" .
							$blacklisted_name_alert .
							$link['info'] .
						'</label>';
				}
				echo '</td></tr>';

				// render redirects
				echo '<tr><th>' . __( 'Redirects', 'greyd_hub' ) . '</th><td>';
				foreach ( $redirects as $slug => $link ) {
					$link = $check_global( $global_urls, $slug, $link );
					echo "<label for='" . $slug . "'>
							<strong style='display:block'>" . $link['title'] . ':</strong><br>';

						$sel = empty( $link['value'] ) || $link['value'] == 'login' ? 'checked' : '';
						echo "<label for='" . $slug . "_login'>
								<input type='radio' name='" . $slug . "' id='" . $slug . "_login' value='login' " . $sel . " autocomplete='off'>" .
								__( 'Redirect to login URL', 'greyd_hub' ) .
							'</label><br>';

						$sel = $link['value'] == 'home' ? 'checked' : '';
						echo "<label for='" . $slug . "_home'>
								<input type='radio' name='" . $slug . "' id='" . $slug . "_home' value='home' " . $sel . " autocomplete='off'>" .
								__( 'Redirect to home URL', 'greyd_hub' ) .
							'</label><br>';

						$sel = $link['value'] == '404' ? 'checked' : '';
						echo "<label for='" . $slug . "_404'>
								<input type='radio' name='" . $slug . "' id='" . $slug . "_404' value='404' " . $sel . " autocomplete='off'>" .
								__( 'Redirect to 404', 'greyd_hub' ) .
							'</label><br>';

					if ( $slug == 'old_login_redirect' ) {
						$sel = $link['value'] == 'admin' ? 'checked' : '';
						echo "<label for='" . $slug . "_admin'>
									<input type='radio' name='" . $slug . "' id='" . $slug . "_admin' value='admin' " . $sel . " autocomplete='off'>" .
								__( 'Redirect to admin (not logged in user will be further redirected)', 'greyd_hub' ) .
							'</label><br>';
					}

						$sel = ! in_array( $link['value'], array( '', 'login', 'home', '404', 'admin' ) ) ? 'checked' : '';
						$val = $sel != '' ? rawurldecode( $link['value'] ) : '';
						echo "<label for='" . $slug . "_custom'>
								<input type='radio' name='" . $slug . "' id='" . $slug . "_custom' value='custom' " . $sel . " autocomplete='off'>" .
								__( 'Redirect to custom URL', 'greyd_hub' ) .
								"<input class='custom_hide' type='text' name='" . $slug . "_custom' id='" . $slug . "_custom_input' value='" . $val . "' autocomplete='off'>" .
							'</label>';

					echo $link['info'];
					echo '</label>';
				}
				echo '</td></tr>';

				// end of table
				echo '</table>';

				// Submit
				if ( $locked == '' ) {
					echo submit_button();
				}

				echo '</form>';

	}


	/**
	 * handle POST Data for saving
	 *
	 * @param array $post_data  Raw $_POST data.
	 */
	public function save_data( $post_data ) {

		$nonce = isset( $post_data['_wpnonce'] ) ? $post_data['_wpnonce'] : null;
		if ( $nonce && wp_verify_nonce( $nonce, 'greyd_users_settings' ) ) {
			// debug("save settings:");
			// debug($post_data);
			// saved urls
			$urls = Manage::get_settings( 'urls' );
			if ( empty( $urls ) ) {
				$urls = array();
			}
			// urls
			foreach ( array( 'login_url', 'logout_url', 'register_url', 'reset_url' ) as $slug ) {
				if ( isset( $post_data[ $slug ] ) && ! empty( $post_data[ $slug ] ) ) {
					$urls[ $slug ] = $post_data[ $slug ];
				} else {
					unset( $urls[ $slug ] );
				}
			}
			// redirects
			foreach ( array( 'admin_redirect', 'old_login_redirect' ) as $slug ) {
				if ( isset( $post_data[ $slug ] ) && ! empty( $post_data[ $slug ] ) ) {
					if ( $post_data[ $slug ] == 'custom' ) {
						if ( ! empty( $post_data[ $slug . '_custom' ] ) ) {
							$urls[ $slug ] = rawurlencode( $post_data[ $slug . '_custom' ] );
						} else {
							unset( $urls[ $slug ] );
						}
					} else {
						$urls[ $slug ] = $post_data[ $slug ];
					}
				} else {
					unset( $urls[ $slug ] );
				}
			}
			// save
			if ( empty( $urls ) ) {
				$urls = '';
			}
			return Manage::save_settings( 'urls', $urls );
		}
		return false;

	}

	/**
	 * handle Plugin deactivation via get request.
	 *
	 * @param array $post_data  Raw $_GET data.
	 */
	public function deactivate_plugin( $post_data ) {

		$result = false;
		$nonce  = isset( $post_data['_wpnonce'] ) ? $post_data['_wpnonce'] : null;
		if ( $nonce && wp_verify_nonce( $nonce, 'greyd_users_deactivate_plugin' ) ) {
			// deactivate WPS Hide Login
			$plugin = 'wps-hide-login/wps-hide-login.php';
			// search
			$current = get_option( 'active_plugins', array() );
			// debug($current);
			$key = array_search( $plugin, $current, true );
			if ( $key !== false ) {
				// deactivate plugin
				unset( $current[ $key ] );
				$result = update_option( 'active_plugins', $current );
			}
		}

		if ( isset( $_GET['action'] ) ) {
			$url = remove_query_arg( array( 'action', '_wpnonce' ) );
			echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
		}

		return $result;
	}

}
