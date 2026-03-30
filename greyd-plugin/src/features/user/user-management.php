<?php
/**
 * User Management features.
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Manage( $config );
class Manage {

	/**
	 * Holds the plugin config.
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Standalone mode.
	 *
	 * @var bool
	 */
	public static $is_standalone = false;

	/**
	 * Class constructor.
	 */
	public function __construct( $config ) {
		
		// set config
		$this->config = (object) $config;
		if ( isset( $this->config->is_standalone ) && $this->config->is_standalone == true ) {
			// standalone mode
			self::$is_standalone = true;
		}

		if ( !self::wp_up_to_date() ) {
			// disable feature
			add_filter( 'greyd_features', function($files) {
				// debug($files);
				foreach ( $files as $i => $file ) {
					if ( $file['slug'] == 'user' ) {
						$files[$i]['Hidden'] = true;
						break;
					}
				}
				return $files;
			} );
			// disable dashboard panel
			add_filter( 'greyd_dashboard_all_panels', function($panels) {
				unset( $panels[ 'user-management' ] );
				return $panels;
			} );
			// exit
			return;
		}

		/**
		 * handle settings
		 */
		if ( self::$is_standalone ) {
			// standalone mode
			// todo: settings handling without hub
		} else {
			// in hub
			add_filter( 'greyd_settings_default_site', array( $this, 'add_greyd_settings' ) );
			add_filter( 'greyd_settings_default_global', array( $this, 'add_greyd_settings' ) );
			// dashboard panel
			add_filter( 'greyd_dashboard_active_panels', function($panels) {
				$panels[ 'user-management' ] = true;
				return $panels;
			} );
		}

		/**
		 * post_type capabilities
		 */
		if ( self::wp_up_to_date( true ) ) {
			add_filter( 'register_post_type_args', array( $this, 'register_post_type_args' ), 10, 2 );
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
			// fix authors dropdown in quickedit and edit screen without editor
			add_filter( 'wp_dropdown_users_args', array( $this, 'fix_edit_dropdown_authors' ), 10, 2 );
			// hide post/page menu items
			add_action( 'admin_menu', array( $this, 'hide_admin_menu' ) );
			add_action( 'admin_bar_menu', array( $this, 'hide_admin_bar_menu' ), 999 );
			if ( is_admin() ) {
				// disable post/page edit screens
				add_action( 'wp', array( $this, 'disable_edit' ) );
				add_action( 'add_meta_boxes', array( $this, 'disable_edit' ), 0 );
			}
		}

		// backend
		if ( is_admin() ) {

			/**
			 * block capabilities
			 */
			add_action( 'enqueue_block_editor_assets', array( $this, 'register_blocks_assets' ), 9999 );

			/**
			 * metafields on user profile and user's own profile editing screens
			 */
			// after the "Personal Options" settings table.
			add_action( 'personal_options', array( $this, 'usermeta_form_field_top' ) );

			// after the "About the User"/"Account Management" settings table.
			// on user's own profile editing screen.
			add_action( 'show_user_profile', array( $this, 'usermeta_form_field_bottom' ) );
			// on user profile editing screen.
			add_action( 'edit_user_profile', array( $this, 'usermeta_form_field_bottom' ) );

			// save action on user's own profile editing screen update.
			add_action( 'personal_options_update', array( $this, 'usermeta_form_field_update' ) );
			// save action on user profile editing screen update.
			add_action( 'edit_user_profile_update', array( $this, 'usermeta_form_field_update' ) );

		}

		/**
		 * login urls
		 * only add if wps-hide-login is not active
		 */
		if ( ! Helper::is_active_plugin( 'wps-hide-login/wps-hide-login.php' ) ) {

			add_filter( 'site_url', array( $this, 'filter_login_url' ), 99, 4 );
			add_filter( 'network_site_url', array( $this, 'filter_login_url' ), 99, 3 );
			add_filter( 'wp_redirect', array( $this, 'filter_login_url' ), 99, 2 );

			add_filter( 'lostpassword_url', array( $this, 'filter_login_action_url' ), 99, 2 );
			add_filter( 'logout_url', array( $this, 'filter_login_action_url' ), 99, 2 );
			add_filter( 'register_url', array( $this, 'filter_login_action_url' ), 99, 1 );

			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 1 );

		}

		/**
		 * mails
		 */
		// smtp
		add_action( 'phpmailer_init', array( $this, 'smtp_phpmailer_init' ), 9998 );
		// to and from
		add_filter( 'wp_mail', array( $this, 'mail_receiver' ) );
		add_filter( 'wp_mail_from', array( $this, 'mail_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );
		// filter all emails content
		add_action( 'after_setup_theme', array( $this, 'filter_mails' ) );

		// add hook for testmail ajax handling
		add_action( 'greyd_ajax_mode_send_testmail', array( $this, 'send_testmail' ) );

	}

	/*
	=======================================================================
		mails
	=======================================================================
	*/

	/**
	 * Adjust the initialized PHPMailer to use SMTP setup.
	 *
	 * @see action 'phpmailer_init'
	 *
	 * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
	 */
	public function smtp_phpmailer_init( $phpmailer ) {

		// get settings
		// $settings = Manage::get_settings('smtp');
		$settings = self::get_merged_settings( 'smtp' );
		if ( ! $settings ) {
			return;
		}

		// var_error_log($settings);
		list( $enable, $host, $port, $auth, $username, $password, $encryption ) = array_values( $settings );

		if ( ! $enable || empty( $host ) ) {
			return;
		}

		// modify phpmailer mode
		$phpmailer->Host = $host;
		$phpmailer->Port = intval( $port );

		if ( $auth ) {
			$phpmailer->SMTPAuth = $auth; // if required
			$phpmailer->Username = $username; // if required
			$phpmailer->Password = $password; // if required
		}

		$phpmailer->SMTPSecure = $encryption; // enable if required, 'tls' is another possible value
		$phpmailer->IsSMTP();
		// var_error_log($phpmailer);
	}

	/**
	 * Modify Mail Receivers by filtering the primary wp_mail() arguments.
	 * Insert CC and BCC Recipients to either admin or user Mails.
	 *
	 * @see filter 'wp_mail'
	 *
	 * @param array $args Array of the `wp_mail()` arguments.
	 *      @property string|string[] to          Array or comma-separated list of email addresses to send message.
	 *      @property string          subject     Email subject.
	 *      @property string          message     Message contents.
	 *      @property string|string[] headers     Additional headers.
	 *      @property string|string[] attachments Paths to files to attach.
	 */
	public function mail_receiver( $args ) {
		// debug('wp_mail');
		// debug($args);

		// get settings
		// $settings = Manage::get_settings('mails');
		$settings = self::get_merged_settings( 'mails' );

		// make header
		$header = array();
		if ( $args['to'] == get_option( 'admin_email' ) ) {
			// email is to admin
			// todo: user is admin
			if ( isset( $settings['admin_to']['to'] ) && ! empty( $settings['admin_to']['to'] ) ) {
				$args['to'] = $settings['admin_to']['to'];
			}
			if ( isset( $settings['admin_to']['cc'] ) && ! empty( $settings['admin_to']['cc'] ) ) {
				array_push( $header, 'cc: ' . $settings['admin_to']['cc'] );
			}
			if ( isset( $settings['admin_to']['bcc'] ) && ! empty( $settings['admin_to']['bcc'] ) ) {
				array_push( $header, 'bcc: ' . $settings['admin_to']['bcc'] );
			}
		} else {
			// email is to user
			if ( isset( $settings['user_to']['cc'] ) && ! empty( $settings['user_to']['cc'] ) ) {
				array_push( $header, 'cc: ' . $settings['user_to']['cc'] );
			}
			if ( isset( $settings['user_to']['bcc'] ) && ! empty( $settings['user_to']['bcc'] ) ) {
				array_push( $header, 'bcc: ' . $settings['user_to']['bcc'] );
			}
		}

		if ( ! empty( $header ) ) {
			if ( ! empty( $args['headers'] ) ) {
				array_unshift( $args['headers'] );
			}
			$args['headers'] = implode( "\n", $header );
		}
		return $args;

	}

	/**
	 * Filter the email address to send from.
	 *
	 * @see filter 'wp_mail_from'
	 *
	 * @param string $from_email Email address to send from.
	 */
	public function mail_from_address( $from_email ) {
		// debug('wp_mail_from');
		// debug($from_email);

		// get settings
		$settings = self::get_merged_settings( 'mails' );

		// from address
		if ( isset( $settings['from']['address'] ) && ! empty( $settings['from']['address'] ) ) {
			$from_email = $settings['from']['address'];
		}
		return $from_email;

	}

	/**
	 * Filter the name to associate with the "from" email address.
	 *
	 * @see filter 'wp_mail_from_name'
	 *
	 * @param string $from_name Name associated with the "from" email address.
	 */
	public function mail_from_name( $from_name ) {
		// debug('wp_mail_from_name');
		// debug($from_name);

		// get settings
		$settings = self::get_merged_settings( 'mails' );

		// from name
		if ( isset( $settings['from']['name'] ) && ! empty( $settings['from']['name'] ) ) {
			$from_name = $settings['from']['name'];
		}
		return $from_name;

	}

	/**
	 * Modify Mail contents.
	 * Add filter only if there is actually a saved content for the mail slug.
	 * Do this as soon, as Theme functions and settings are available.
	 *
	 * @see action 'after_setup_theme'
	 */
	public function filter_mails() {

		// get settings
		$settings = self::get_merged_settings( 'mails' );
		// debug($settings);
		if ( empty( $settings ) ) {
			return;
		}

		// user emails
		if ( isset( $settings['user_mails'] ) && is_array( $settings['user_mails'] ) ) {
			foreach ( $settings['user_mails'] as $mail_slug => $value ) {
				if ( ! empty( $value ) ) {

					if ( ! class_exists( '\Greyd\User\Mails_Page' ) ) {
						require_once __DIR__ . '/mails-page.php';
					}

					Mails_Page::add_mail_filter( 'user', $mail_slug );
				}
			}
		}
		// admin emails
		if ( isset( $settings['admin_mails'] ) && is_array( $settings['admin_mails'] ) ) {
			foreach ( $settings['admin_mails'] as $mail_slug => $value ) {
				if ( ! empty( $value ) ) {

					if ( ! class_exists( '\Greyd\User\Mails_Page' ) ) {
						require_once __DIR__ . '/mails-page.php';
					}

					Mails_Page::add_mail_filter( 'admin', $mail_slug );
				}
			}
		}

	}

	/**
	 * Ajax function to preview or send a Test Email.
	 *
	 * @action 'greyd_ajax_mode_'
	 *
	 * @param array $post_data   $_POST data.
	 */
	public function send_testmail( $post_data ) {

		if ( ! class_exists( '\Greyd\User\Mails_Page' ) ) {
			require_once __DIR__ . '/mails-page.php';
		}

		$mails_page = new Mails_Page();
		$mails_page->send_testmail( $post_data );

	}

	/*
	=======================================================================
		login urls and redirects
	=======================================================================
	*/

	/**
	 * Change base URL for all wp-login.php URLs.
	 *
	 * @see filter 'site_url'
	 * @see filter 'network_site_url'
	 * @see filter 'wp_redirect'
	 *
	 * Once a new 'login_url' is set, this affects all pages with any login action.
	 * @see wp-login.php in the wp base directory and search for 'action'.
	 * In order to change the base URL for all these actions, rather than picking and recreating single ones,
	 * all incoming URLs are filtered and the base url for all URLs with 'wp-login.php' is changed to the new one.
	 *
	 * @param string $url       Original URL.
	 * @param string $path      (unused)
	 * @param string $scheme    Original URL scheme.
	 * @param string $blog_id   (unused)
	 * @return string $url
	 */
	public function filter_login_url( $url, $path, $scheme = null, $blog_id = null ) {

		// filter only original wp-login.php urls
		if ( strpos( $url, 'wp-login.php' ) !== false ) {
			// debug($url);

			// for password sets, use original
			if ( strpos( $url, 'action=postpass' ) !== false ) {
				return $url;
			}

			// override base url for all wp-login.php actions
			if ( $new_url = $this->get_new_url( 'login_url', $scheme ) ) {

				// check for args
				if ( is_ssl() ) {
					$scheme = 'https';
				}
				if ( strpos( $url, '?' ) !== false ) {

					$args = explode( '?', $url );
					parse_str( $args[1], $args );
					if ( isset( $args['login'] ) ) {
						$args['login'] = rawurlencode( $args['login'] );
					}
					$new_url = add_query_arg( $args, $new_url );

				}
				// set new login url
				$url = $new_url;
			}
		}
		return $url;

	}

	/**
	 * Change URL for specific login action URLs.
	 *
	 * @see filter 'lostpassword_url'
	 * @see filter 'logout_url'
	 * @see filter 'register_url'
	 *
	 * With 'filter_login_url' the base URL for all login actions is changed.
	 * This filters the URL further and overrides the URLs
	 * for the 'logout', 'register' and 'lostpassword' action URLs.
	 *
	 * @param string $url       Original URL.
	 * @param string $redirect  Original redirect argument.
	 * @return string $url
	 */
	public function filter_login_action_url( $url, $redirect = '' ) {

		// get type of action from url
		$type = '';
		if ( strpos( $url, 'action=logout' ) !== false ) {
			$type = 'logout_url';
		}
		if ( strpos( $url, 'action=lostpassword' ) !== false ) {
			$type = 'reset_url';
		}
		if ( strpos( $url, 'action=register' ) !== false ) {
			$type = 'register_url';
		}

		// override url if this type also has a new url
		if ( ! empty( $type ) && $new_url = $this->get_new_url( $type, 'login' ) ) {
			$url = $new_url;
			if ( ! empty( $redirect ) ) {
				$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );
			}
		}
		return $url;

	}

	/**
	 * Once WP is loaded, manipulate what happens on the old and new login screens:
	 * - redirect away from admin pages if the user is not logged in.
	 * - render the login screen on pages with the set urls.
	 * - redirect away from the old login screen if there are new urls set.
	 *
	 * @see action 'wp_loaded'
	 */
	public function wp_loaded() {

		// for page with password sets, use original
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'postpass' && isset( $_POST['post_password'] ) ) {
			return;
		}

		global $wp_rewrite;

		// get all slugs
		$new_slugs               = $this->get_all_new_slugs();
		$is_wp_login_screen      = false;
		$is_new_login_screen     = false;
		$new_login_screen_action = '';

		// normalize page uri and path
		$request_uri = rawurldecode( $_SERVER['REQUEST_URI'] );
		$request     = parse_url( $request_uri );
		if ( ! isset( $request['path'] ) ) {
			$request['path'] = '';
		}
		$request['path'] = untrailingslashit( $request['path'] );

		//
		// (1) check current page
		if ( ! is_admin() && $new_slugs['login_url'] &&
			 ( strpos( $request_uri, 'wp-login.php' ) !== false ||
			   $request['path'] === site_url( 'wp-login', 'relative' ) ||
			   $request['path'] === site_url( 'login', 'relative' ) ) ) {

			// this is the old wp login screen
			$is_wp_login_screen = true;
		}
		foreach ( array( 'login_url', 'logout_url', 'register_url', 'reset_url' ) as $new_slug ) {

			if ( $is_new_login_screen ) {
				continue;
			}

			$slug = $new_slugs[ $new_slug ];
			if ( ( $slug && $request['path'] === home_url( $slug, 'relative' ) ) ||
				 ( ! $wp_rewrite->permalink_structure && isset( $_GET[ $slug ] ) && empty( $_GET[ $slug ] ) ) ) {

				// this is the new login screen
				$is_new_login_screen     = true;
				$new_login_screen_action = $new_slug;
			}
		}

		//
		// (2) redirect away from admin if not logged in
		if ( ! is_user_logged_in() && $new_slugs['login_url'] ) {
			global $pagenow;

			if ( ( is_admin() && ! defined( 'WP_CLI' ) && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) &&
				   $pagenow !== 'admin-post.php' && $pagenow !== 'admin-ajax.php' ) ||
				 ( isset( $_GET['wc-ajax'] ) && $pagenow === 'profile.php' ) ) {

				// get redirect option
				$redirect = $this->get_new_redirect( 'admin_redirect' );
				if ( $redirect == 'login_url' ) {
					// make login url with redirect to current admin page
					$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
					$args = array(
						'redirect_to' => rawurlencode( $current_url ),
						'reauth'      => '1',
					);
					$redirect = add_query_arg( $args, $this->get_new_url( 'login_url' ) );
				}

				// redirect away from admin
				if ( strpos( $redirect, home_url() ) !== false ) {
					wp_safe_redirect( $redirect );
				} else {
					header( 'Location: ' . $redirect );
				}
				die();
			}
		}

		// (3) render new login screen
		if ( $is_new_login_screen ) {

			// make logout action
			if ( $new_login_screen_action == 'logout_url' ) {
				$_REQUEST['action']   = 'logout';
				$_REQUEST['_wpnonce'] = wp_create_nonce( 'log-out' );
			}

			// redirect to admin if user is logged in (not for logout action)
			if ( is_user_logged_in() && ! isset( $_REQUEST['action'] ) ) {
				// debug("redirect when logged in");
				wp_safe_redirect( admin_url() );
				die();
			}

			// make register action
			if ( $new_login_screen_action == 'register_url' ) {
				$_REQUEST['action'] = 'register';
			}
			// make lost password action
			if ( $new_login_screen_action == 'reset_url' ) {
				$_REQUEST['action'] = 'lostpassword';
			}

			// include wp-login.php on new login page
			global $error, $interim_login, $action, $user_login;
			@require_once ABSPATH . 'wp-login.php';
			die();
		}

		// -or- redirect away from old login screen
		if ( $is_wp_login_screen ) {

			// get redirect option
			$redirect = $this->get_new_redirect( 'old_login_redirect' );

			// check activation refrerer
			if ( ( $referer = wp_get_referer() ) &&
				 strpos( $referer, 'wp-activate.php' ) !== false &&
				 ( $referer = parse_url( $referer ) ) &&
				 ! empty( $referer['query'] ) ) {

				parse_str( $referer['query'], $referer );
				@require_once ABSPATH . WPINC . '/ms-functions.php';

				if ( ! empty( $referer['key'] ) &&
					 ( $result = wpmu_activate_signup( $referer['key'] ) ) &&
					 is_wp_error( $result ) &&
					 ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' ) ) {

					$redirect = 'login_url';
				}
			}

			if ( $redirect == 'login_url' ) {
				// make login url with current queries
				$args     = ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '';
				$redirect = $this->get_new_url( 'login_url' ) . $args;
			}

			// redirect away
			if ( strpos( $redirect, home_url() ) !== false ) {
				wp_safe_redirect( $redirect );
			} else {
				header( 'Location: ' . $redirect );
			}
			die();

			//
			// unused: make 404 without redirect
			//
			// debug("load wp on old login");
			// $_SERVER['REQUEST_URI'] = '/404';
			// wp();
			// if ( !defined( 'WP_USE_THEMES' ) ) define( 'WP_USE_THEMES', true );
			// require_once( ABSPATH.WPINC.'/template-loader.php' );
			// die();
		}

	}

	/**
	 * Get all new slugs from the settings by type.
	 * Possible slug types are:
	 * - 'login_url'
	 * - 'reset_url'
	 * - 'register_url'
	 * - 'logout_url'
	 * - 'admin_redirect'
	 * - 'old_login_redirect'
	 * Default value is false if no slug is saved for the type.
	 *
	 * @return string|bool[] $slugs.
	 */
	private function get_all_new_slugs() {

		// get settings
		$slugs = self::get_merged_settings( 'urls' );
		// return sanitized array
		if ( $slugs == '' ) {
			$slugs = array();
		}
		return array_replace(
			array(
				'login_url'          => false,
				'reset_url'          => false,
				'register_url'       => false,
				'logout_url'         => false,
				'admin_redirect'     => false,
				'old_login_redirect' => false,
			),
			$slugs
		);

	}

	/**
	 * Get single slug for for type.
	 *
	 * @param string $type  @see get_all_new_slugs for possible slug types.
	 * @return string|bool  saved slug for type or false.
	 */
	private function get_new_slug( $type ) {

		$slugs = $this->get_all_new_slugs();
		// debug($slugs);
		if ( isset( $slugs[ $type ] ) ) {
			return $slugs[ $type ];
		}
		return false;

	}

	/**
	 * Get full URL for for type.
	 *
	 * @param string $type   @see get_all_new_slugs for possible slug types.
	 * @param string $scheme URL scheme
	 * @return string|bool   full URL for type or false.
	 */
	public function get_new_url( $type, $scheme = null ) {

		// get slug
		$new_slug = $this->get_new_slug( $type );
		if ( ! $new_slug ) {
			return false;
		}

		// make url
		global $wp_rewrite;
		$home = home_url( '/', $scheme );
		// check permalink structure
		if ( $wp_rewrite->permalink_structure ) {
			if ( $wp_rewrite->use_trailing_slashes ) {
				return trailingslashit( $home . $new_slug );
			} else {
				return untrailingslashit( $home . $new_slug );
			}
		} else {
			return $home . $new_slug;
		}

	}

	/**
	 * Get redirect uURLrl for for type.
	 *
	 * @param string $type  @see get_all_new_slugs for possible slug types.
	 * @return string|bool  redirect URL for type or false.
	 */
	private function get_new_redirect( $type ) {

		// get slug
		$slugs = $this->get_all_new_slugs();
		// debug($slugs);
		$slug = 'login';
		if ( isset( $slugs[ $type ] ) && ! empty( $slugs[ $type ] ) ) {
			$slug = $slugs[ $type ];
		}

		// make redirect url
		if ( $slug == 'login' ) {
			$redirect = 'login_url';
		} elseif ( $slug == 'home' ) {
			$redirect = home_url();
		} elseif ( $slug == '404' ) {
			$redirect = home_url( '404' );
		} elseif ( $slug == 'admin' ) {
			$redirect = admin_url();
		} elseif ( $slug != 'custom' ) {
			$redirect = rawurldecode( $slug );
			if ( strpos( $redirect, 'http' ) === false ) {
				$redirect = 'https://' . $redirect;
			}
		}
		return $redirect;
	}

	/*
	=======================================================================
		post_type capabilities
	=======================================================================
	*/

	/**
	 * all custom post_types with data to manage additional caps.
	 * collected with 'register_post_type_args' filter.
	 *
	 * @var array   of custom posttypes
	 *      @key string     post_type slug.
	 *          @property string singular           the singular version of the post_type slug.
	 *          @property string plural             the plural version of the post_type slug.
	 *          @property string capability_type    original capability_type (post or page)
	 */
	public static $posttypes = array();

	/**
	 * Extend capabilities on post_type registration.
	 *
	 * @see filter 'register_post_type_args'
	 *
	 * if post_type is not '_builtin' and has a general 'capability_type',
	 * the 'capabilities' are extended with the primitive caps to control them later with the 'map_meta_cap' filter.
	 * @see https://developer.wordpress.org/reference/functions/register_post_type/#capabilities
	 *
	 * @param array  $args       Array of arguments for registering a post type.
	 * @param string $post_type post_type slug.
	 * @return array $args
	 */
	public function register_post_type_args( $args, $post_type ) {

		if ( ! isset( $args['_builtin'] ) || $args['_builtin'] == false ) {

			// debug($post_type);
			// debug($args);

			if ( isset( $args['capability_type'] ) ) {
				// get post_type data
				$singular = $post_type;
				$plural   = $singular . 's';
				if ( preg_match( '/s$/', $post_type ) ) {
					$singular = substr( $post_type, 0, -1 );
					$plural   = $post_type;
				}
				$type = $args['capability_type'];
				if ( is_array( $type ) ) {
					if ( $type[0] == 'post' || $type[0] == 'page' ) {
						$type = $type[0];
					} else {
						$singular = $type[0];
						$plural   = $type[1];
						$type     = 'post';
					}
				}
				if ( $type != 'post' && $type != 'page' ) {
					$type = 'post';
				}
				// save registered post_type data
				self::$posttypes[ $singular ] = array(
					'singular'        => $singular,
					'plural'          => $plural,
					'capability_type' => $type,
				);

				// modify post_type args
				$args['capability_type'] = array( $singular, $plural );
				$args['capabilities']    = array(
					// Primitive capabilities used outside of map_meta_cap():
					'edit_posts'             => 'edit_' . $plural,
					'edit_others_posts'      => 'edit_others_' . $plural,
					'publish_posts'          => 'publish_' . $plural,
					'read_private_posts'     => 'read_private_' . $plural,
					// Primitive capabilities used within map_meta_cap():
					// "read"                   => "read",
					'delete_posts'           => 'delete_' . $plural,
					'delete_private_posts'   => 'delete_private_' . $plural,
					'delete_published_posts' => 'delete_published_' . $plural,
					'delete_others_posts'    => 'delete_others_' . $plural,
					'edit_private_posts'     => 'edit_private_' . $plural,
					'edit_published_posts'   => 'edit_published_' . $plural,
					'create_posts'           => 'edit_' . $plural,
				);
				// $args["map_meta_cap"] = false;
			}
		}

		return $args;
	}

	/**
	 * Filter meta capabilities.
	 *
	 * @see filter 'map_meta_cap'
	 *
	 * Make manual mapping of the post_types meta capability ($cap) and check the role settings.
	 * If setting denies capability, override with 'do_not_allow'.
	 * @see https://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types
	 *
	 * @param string[] $caps    Primitive capabilities required of the user.
	 * @param string   $cap       Capability being checked.
	 * @param int      $user_id      The user ID.
	 * @param array    $args       Adds context to the capability check, typically starting with an object ID.
	 * @return string[] $caps
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		// debug(array(
		// 	'cap' => "<b>".$cap."</b>",
		// 	'caps' => $caps,
		// 	'user_id' => $user_id,
		// 	'args' => $args
		// ));

		if ($cap == "edit_post"|| $cap == "edit_page") {
			// check for explicit post/page cap
			if ( count($caps) > 0 && $cap != $caps[count($caps)-1] ) {
				$cap = $caps[count($caps)-1];
			}
		}

		$found = false;
		foreach ( self::$posttypes as $pt => $data ) {
			if ( $found ) {
				continue;
			}

			// vars
			$singular      = '_' . $data['singular'];
			$plural        = '_' . $data['plural'];
			$type_singular = '_' . $data['capability_type']; // post or page
			$type_plural   = '_' . $data['capability_type'] . 's'; // posts or pages

			// map to base type
			$new_caps = array();
			// if ( strpos( $cap, $plural ) !== false ) {
			if ( preg_match( "/".preg_quote( $plural )."$/", $cap ) ) {
				// is plural primitive cap
				$new_caps[] = str_replace( $plural, $type_plural, $cap );
			}
			// elseif ( strpos( $cap, $singular ) !== false ) {
			elseif ( preg_match( "/".preg_quote( $singular )."$/", $cap ) ) {
				// is singular meta cap
				$new_cap = str_replace( $singular, $type_singular, $cap );
				// map meta cap to primitive caps
				// @see \wp-includes\capabilities.php
				$mode = str_replace( $type_singular, '', $new_cap );
				if ( isset( $args[0] ) ) {
					$post = get_post( $args[0] );
					if ( $post ) {
						if ( $mode == 'read' ) {
							if ( $post->post_status != 'private' || $user_id == $post->post_author ) {
								$new_caps[] = 'edit' . $type_plural;
							} else {
								$new_caps[] = 'read_private' . $type_plural;
							}
						} elseif ( $mode == 'edit' || $mode == 'delete' ) {
							if ( $user_id == $post->post_author ) {
								$status = $post->post_status;
								if ( $post->post_status === 'trash' ) {
									$status = get_post_meta( $post->ID, '_wp_trash_meta_status', true );
								}
								if ( in_array( $status, array( 'publish', 'future' ), true ) ) {
									$new_caps[] = $mode . '_published' . $type_plural;
								} else {
									$new_caps[] = $mode . $type_plural;
								}
							} else {
								$new_caps[] = $mode . '_others' . $type_plural;
								if ( in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
									$new_caps[] = $mode . '_published' . $type_plural;
								} elseif ( $post->post_status === 'private' ) {
									$new_caps[] = $mode . '_private' . $type_plural;
								}
							}
						} else {
							$new_caps[] = 'do_not_allow';
						}
					} else {
						$new_caps[] = 'do_not_allow';
					}
				} else {
					$new_caps[] = 'do_not_allow';
				}
			}
			if ( count( $new_caps ) > 0 ) {
				// get role
				$user = get_user_by( 'id', $user_id );
				// debug($user);
				// if (count($user->roles) > 1) debug($user);
				if ( empty( $user->roles ) ) {
					$role = array();
				} else {
					$role = self::get_role( reset( $user->roles ) );
				}
				// check role
				if ( isset( $role['posttype_caps'][ $pt ] ) ) {
					$vis = $role['posttype_caps'][ $pt ];
					for ( $i = 0; $i < count( $new_caps ); $i++ ) {
						$new_cap = $new_caps[ $i ];
						if ( ! isset( $vis[ $new_cap ] ) || ! $vis[ $new_cap ] ) {
							// debug($cap);
							$new_caps[ $i ] = 'do_not_allow';
						}
					}
				}
				// check custom capabilities
				if ($new_caps[count($new_caps)-1] == 'do_not_allow' && isset($role['capabilities'])) {
					// debug($role);
					if (isset($role['capabilities'][$cap])) $new_caps[count($new_caps)-1] = $cap;
				}
				// set new required caps
				$found = true;
				$caps  = $new_caps;
				// debug(array(
				// 	"cap" => $cap,
				// 	"caps" => $caps
				// ));
			}
		}

		// Return the capabilities required by the user.
		return $caps;
	}

	/**
	 * Fix the arguments used to generate authors drop-down in Quickedit and edit screen without editor.
	 * @filter 'wp_dropdown_users_args'
	 * 
	 * @param array $users_opt   The query arguments for get_users().
	 * @param array $parsed_args The arguments passed to wp_dropdown_users() combined with the defaults.
	 * @return array $users_opt
	 */
	public function fix_edit_dropdown_authors( $users_opt, $parsed_args ) {
		if ( preg_match( '/^edit_(.*)s$/', $users_opt["capability"][0], $match ) ) {
			if ( isset(self::$posttypes[$match[1]]) ) {
				$users_opt["capability"] = array( "edit_".self::$posttypes[$match[1]]["capability_type"]."s" );
			}
		}
		return $users_opt;
	}

	/**
	 * Hide posts or pages from admin menu.
	 * @action 'admin_menu'
	 */
	public function hide_admin_menu() {
		// get user
		$role = $this->get_current_user_role();
		// remove menu
		if ( isset( $role['hide_posts'] ) && $role['hide_posts'] ) remove_menu_page( 'edit.php' );
		if ( isset( $role['hide_pages'] ) && $role['hide_pages'] ) remove_menu_page( 'edit.php?post_type=page' );
	}

	/**
	 * Hide "New Post", "New Page" or "Edit" items from admin bar.
	 * @action 'admin_bar_menu'
	 */
	public function hide_admin_bar_menu() {
		// get user
		$role = $this->get_current_user_role();
		// remove items
		global $wp_admin_bar;  
		if ( isset( $role['hide_posts'] ) && $role['hide_posts'] ) {
			// remove "New Post"
			$wp_admin_bar->remove_node( 'new-post' );
			// remove "Now Post" from multisite menu
			foreach ( $wp_admin_bar->get_nodes() as $node ) {
				if ( !empty($node->parent) && strpos( $node->parent, 'blog-' ) === 0 && strpos( $node->href, 'post-new.php' ) !== false ) {
					$wp_admin_bar->remove_node( $node->id );
				}
			}
			// remove "Edit" from frontend
			if ( !is_admin() && get_post_type() == 'post' ) $wp_admin_bar->remove_node( 'edit' );
		}
		if ( isset( $role['hide_pages'] ) && $role['hide_pages'] ) {
			// remove "New Page"
			$wp_admin_bar->remove_node( 'new-link' );
			// remove "Edit" from frontend
			if ( !is_admin() && get_post_type() == 'page' ) $wp_admin_bar->remove_node( 'edit' );
		}
	}

	/**
	 * Disable editing posts or pages.
	 * @action 'wp' and 'add_meta_boxes'
	 * 
	 * @param object|string $post_type	With action 'wp' the wp object, otherwise the posttype as string.
	 */
	public function disable_edit( $post_type ) {
		// check action and input
		if ( is_object($post_type) && isset($post_type->extra_query_vars) ) {
			$post_type = $post_type->extra_query_vars['post_type'];
		}
		else if ( !is_string($post_type) ) {
			return;
		}
		// get user
		$role = $this->get_current_user_role();
		// block access to edit page
		if (
			( isset( $role['hide_posts'] ) && $role['hide_posts'] && $post_type == 'post' ) ||
			( isset( $role['hide_pages'] ) && $role['hide_pages'] && $post_type == 'page' )
		) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'greyd_hub' ) );
		}
	}

	/**
	 * Get role of currently logged in user.
	 */
	public function get_current_user_role() {		
		// get user
		$user = wp_get_current_user();
		if ( empty( $user->roles ) ) {
			$role = array();
		}
		else {
			$role = self::get_role( reset( $user->roles ) );
		}
		// debug($role);
		return $role;
	}

	/*
	=======================================================================
		Block capabilities
	=======================================================================
	*/

	/**
	 * Register and enqueue block editor script to hide blocks per role
	 */
	public function register_blocks_assets() {

		$css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
		$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

		// get user
		$user = wp_get_current_user();
		// if (count($user->roles) > 1) debug($user);
		if ( empty( $user->roles ) ) {
			$role = array();
		} else {
			$role = self::get_role( reset( $user->roles ) );
		}
		// debug($role);

		// block caps
		$block_caps = array();
		if ( isset( $role['block_caps'] ) ) {
			$vis = $role['block_caps'];
			foreach ( $role['block_caps'] as $name => $value ) {
				$block_caps[ $name ] = array(
					// block is hidden
					'visible' => $value,
				);
			}
		}

		// editor script
		wp_register_script(
			'greyd-user-editor',
			$js_uri . '/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script( 'greyd-user-editor' );
		wp_localize_script( 'greyd-user-editor', 'greyd_block_caps', $block_caps );

	}

	/*
	=======================================================================
		metafields
	=======================================================================
	*/

	/**
	 * fields on the editing screen
	 * after the "Personal Options" settings table.
	 *
	 * @param WP_User $user user object
	 */
	function usermeta_form_field_top( $user ) {

		$role   = self::get_role( reset( $user->roles ) );
		$fields = array_filter(
			$role['fields'],
			function( $field ) {
				if ( ! isset( $field['position'] ) || $field['position'] != 'bottom' ) {
					return true;
				}
			}
		);
		$this->usermeta_form_field( $user, $fields );

	}

	/**
	 * fields on the editing screen
	 * after the "About the User"/"Account Management" settings table.
	 *
	 * @param WP_User $user user object
	 */
	function usermeta_form_field_bottom( $user ) {

		$role   = self::get_role( reset( $user->roles ) );
		$fields = array_filter(
			$role['fields'],
			function( $field ) {
				if ( isset( $field['position'] ) && $field['position'] == 'bottom' ) {
					return true;
				}
			}
		);
		$this->usermeta_form_field( $user, $fields );

	}

	/**
	 * render fields on the editing screen.
	 *
	 * @param WP_User $user user object
	 * @param array   $fields Metafields to render
	 */
	function usermeta_form_field( $user, $fields ) {

		$metafields_table = new Metafields_Table();
		$metafields_table->render_meta_fields(
			array(
				'user'   => $user,
				'fields' => $fields,
			)
		);

	}

	/**
	 * Save metafields.
	 *
	 * @param int $user_id  the ID of the current user.
	 * @return bool true on successful update, false on failure.
	 */
	function usermeta_form_field_update( $user_id ) {
		// check that the current user have the capability to edit the $user_id
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$user    = get_user_by( 'id', $user_id );
		$role    = self::get_role( reset( $user->roles ) );
		$skip    = array( 'headline', 'descr', 'hr', 'space' );
		$results = array();
		foreach ( $role['fields'] as $field ) {
			if ( isset( $field['type'] ) && ! in_array( $field['type'], $skip ) ) {
				debug( $field );
				if ( isset( $_POST[ $field['name'] ] ) ) {
					$name  = 'greyd_' . $field['name'];
					$value = $_POST[ $field['name'] ];
					// create/update user meta
					$results[] = update_user_meta(
						$user_id,
						$name,
						$value
					);

				}
			}
		}

		if ( in_array( false, $results ) ) {
			return false;
		} else {
			return true;
		}
	}

	/*
	=======================================================================
		settings
	=======================================================================
	*/

	/**
	 * Add default settings if Hub is installed.
	 *
	 * @see filter 'greyd_settings_default_site'
	 * @see filter 'greyd_settings_default_global'
	 */
	public function add_greyd_settings( $settings ) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults()
		);
		return $settings;
	}

	/**
	 * default settings.
	 *
	 * @return array    The default (empty) settings array.
	 */
	public static function get_defaults() {

		$defaults = array(
			'user' => array(
				'roles' => '',
				'urls'  => '',
				'mails' => '',
			),
			'smtp' => array(
				'enable'     => false,
				'host'       => '',
				'port'       => 587,
				'auth'       => false,
				'username'   => '',
				'password'   => '',
				'encryption' => false,
			),
		);
		return $defaults;
	}

	/**
	 * Hold cached settings of get_settings function.
	 *
	 * @var array
	 */
	private static $_settings_cache = array();

	/**
	 * get settings.
	 *
	 * @param string      $type      Subsettings "roles", "urls", "mails" or "smtp".
	 * @param string|bool $mode "global" or "site" based on is_network_admin or fixed string.
	 * @return array            The settings array.
	 */
	public static function get_settings( $type, $mode = false ) {

		// check type
		if ( ! in_array( $type, array( 'roles', 'urls', 'mails', 'smtp' ) ) ) {
			return false;
		}

		// mode
		if ( ! $mode ) {
			$mode = ( is_multisite() && is_network_admin() ) ? 'global' : 'site';
			// debug("get ".$mode);
		}

		// get cache
		if ( isset( self::$_settings_cache[ $mode . '_' . $type ] ) ) {
			return self::$_settings_cache[ $mode . '_' . $type ];
		}

		// get settings
		if ( $type == 'smtp' ) {
			$settings = Settings::get_setting( array( $mode, $type ) );
		} else {
			$settings = Settings::get_setting( array( $mode, 'user', $type ) );
			if ( is_string( $settings ) ) {
				$settings = unserialize( $settings );
			}
		}
		// set cache
		self::$_settings_cache[ $mode . '_' . $type ] = $settings;
		return $settings;

	}

	/**
	 * get global settings.
	 * calls get_settings function with $mode = "global"
	 *
	 * @return array            The settings array.
	 */
	public static function get_global_settings( $type ) {
		return self::get_settings( $type, 'global' );
	}

	/**
	 * get merged settings.
	 * gets "global" and "site" settings and merges them.
	 *
	 * @return array            The settings array.
	 */
	public static function get_merged_settings( $type ) {
		// get settings
		$global_settings = self::get_global_settings( $type );
		$site_settings   = self::get_settings( $type );
		if ( ! $global_settings ) {
			$global_settings = array();
		}
		if ( ! $site_settings ) {
			$site_settings = array();
		}

		if ( $type == 'smtp' ) {
			// prevent overriding smtp settings with default values
			$defaults = self::get_defaults();
			if ( $site_settings == $defaults['smtp'] || ! $site_settings['enable'] || empty( $site_settings['host'] ) ) {
				$site_settings = array();
			}
		}

		// merge settings
		$settings = array_replace_recursive( $global_settings, $site_settings );
		// debug($settings);

		return $settings;
	}

	/**
	 * save settings.
	 * save mode "global" or "site" is based on is_network_admin.
	 *
	 * @param string $type      Subsettings "roles", "urls", "mails" or "smtp".
	 * @param array  $settings   The settings array to be saved.
	 * @return bool             true on successful update, false on failure.
	 */
	public static function save_settings( $type, $settings ) {

		// check type
		if ( ! in_array( $type, array( 'roles', 'urls', 'mails', 'smtp' ) ) ) {
			return false;
		}
		// mode
		$mode          = ( is_multisite() && is_network_admin() ) ? 'global' : 'site';
		$settings_mode = $mode == 'global' ? 'global_network' : $mode;
		// debug("save global".$mode);

		// update settings
		if ( $type == 'smtp' ) {
			$result = Settings::update_setting( $settings_mode, array( $mode, $type ), $settings );
		} else {
			$settings = serialize( $settings );
			$result   = Settings::update_setting( $settings_mode, array( $mode, 'user', $type ), $settings );
		}
		// reload theme
		Settings::reload_settings();
		// reset cache
		self::$_settings_cache = array();
		return $result;

	}

	/*
	=======================================================================
		roles/capabilities data
	=======================================================================
	*/

	/**
	 * get all basic roles.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_roles/
	 *
	 * @return array $all_roles     Array of all basic Role Objects without settings.
	 */
	public static function get_basic_roles() {

		// prepare
		$all_roles = array();
		$roles     = wp_roles();

		// collect all user roles
		foreach ( $roles->roles as $slug => $role ) {
			array_push(
				$all_roles,
				array(
					// core role
					'title'        => $role['name'],
					'slug'         => $slug,
					'capabilities' => $role['capabilities'],
				)
			);
		}

		// sort
		usort(
			$all_roles,
			function( $a, $b ) {
				// sort by capabilities and title
				if ( count( $a['capabilities'] ) != count( $b['capabilities'] ) ) {
					return count( $b['capabilities'] ) - count( $a['capabilities'] );
				} else {
					return strcmp( $a['title'], $b['title'] );
				}
			}
		);
		// debug($all_roles);

		return $all_roles;
	}

	/**
	 * Hold cached value of get_roles function.
	 *
	 * @var array
	 */
	private static $_roles_cache = false;

	/**
	 * get all available roles with additional settings.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_roles/
	 *
	 * @param bool $include_super   If on multisite, include the "Super Admin" role (default: true).
	 * @return array $all_roles     Array of all Role Objects.
	 */
	public static function get_roles( $include_super = true ) {

		if ( self::$_roles_cache ) {
			// use cache value
			// debug("use cache");
			return self::$_roles_cache;
		}
		// debug("make new");

		// prepare
		$all_roles = array();
		if ( is_multisite() && $include_super ) {
			$caps = self::get_capabilities();
			$caps = array_flip( array_merge( ...array_values( $caps ) ) );
			array_push(
				$all_roles,
				array(
					'title'         => 'Super Admin',
					'slug'          => 'super',
					'users'         => count( get_super_admins() ),
					'capabilities'  => $caps,
					'posttype_caps' => array(),
					'block_caps'    => array(),
					'fields'        => array(),
				)
			);
		}

		// get all user roles
		$roles      = wp_roles();
		$users_info = count_users();
		// debug($roles);
		$role_settings = self::get_settings( 'roles' );

		foreach ( $roles->roles as $slug => $role ) {
			// debug($role);
			// if (isset($role_settings[$slug])) debug($role_settings[$slug]);
			$posttype_caps = isset( $role_settings[ $slug ]['posttype_caps'] ) ? $role_settings[ $slug ]['posttype_caps'] : array();
			$block_caps    = isset( $role_settings[ $slug ]['block_caps'] ) ? $role_settings[ $slug ]['block_caps'] : array();
			$fields        = isset( $role_settings[ $slug ]['fields'] ) ? $role_settings[ $slug ]['fields'] : array();
			$hide_posts    = isset( $role_settings[ $slug ]['hide_posts'] ) && $role_settings[ $slug ]['hide_posts'] ? true : false;
			$hide_pages    = isset( $role_settings[ $slug ]['hide_pages'] ) && $role_settings[ $slug ]['hide_pages'] ? true : false;
			array_push(
				$all_roles,
				array(
					// core role
					'title'         => $role['name'],
					'slug'          => $slug,
					'users'         => $users_info['avail_roles'][ $slug ] ?? 0,
					'capabilities'  => $role['capabilities'],
					// role settings
					'posttype_caps' => $posttype_caps,
					'block_caps'    => $block_caps,
					'fields'        => $fields,
					'hide_posts'    => $hide_posts,
					'hide_pages'    => $hide_pages,
				)
			);
		}

		// sort
		usort(
			$all_roles,
			function( $a, $b ) {
				// sort by capabilities and title
				if ( count( $a['capabilities'] ) != count( $b['capabilities'] ) ) {
					return count( $b['capabilities'] ) - count( $a['capabilities'] );
				} else {
					return strcmp( $a['title'], $b['title'] );
				}
			}
		);
		// debug($all_roles);

		// set cache
		self::$_roles_cache = $all_roles;

		return $all_roles;
	}

	/**
	 * get a single role with additional settings.
	 *
	 * @param string $slug  The name/slug of the role ("" to get blank role).
	 * @return mixed|bool $role      false if role is not found, else Role Object:
	 *      @property string title          Role title (nice)
	 *      @property string slug           Role name/slug
	 *      @property int users             Number of user with this role
	 *      @property array capabilities    Role capabilities
	 *      @property array posttype_caps   Posttype capabilities (setting)
	 *      @property array block_caps      Block capabilities (setting)
	 *      @property array fields          Additional Metafields (setting)
	 */
	public static function get_role( $slug ) {

		$role = false;
		if ( $slug == '' ) {
			// blank role
			$role = array(
				'title'         => '',
				'slug'          => '',
				'users'         => 0,
				'capabilities'  => array(),
				'posttype_caps' => array(),
				'block_caps'    => array(),
				'fields'        => array(),
			);
		} else {
			foreach ( self::get_roles() as $r ) {
				if ( $r['slug'] == $slug ) {
					$role = $r;
					break;
				}
			}
		}

		return $role;
	}

	/**
	 * get all available capabilities.
	 *
	 * @param bool $include_super   If on multisite, include the "Network" capabilities (default: true).
	 * @return array $all_caps      grouped array of all capabilities.
	 */
	public static function get_capabilities( $include_super = true ) {

		// prepare
		$all_caps = array(
			'network' => array(),
			'general' => array(),
			'posts'   => array(),
			'pages'   => array(),
			'themes'  => array(),
			'plugins' => array(),
			'user'    => array(),
			'level'   => array(),
			'custom'  => array(),
		);

		// get core capabilities
		$core_caps = self::get_core_capabilities( $include_super );
		foreach ( $core_caps as $cap ) {
			$data_cap = 'general';
			if ( strpos( $cap, 'network' ) !== false || strpos( $cap, 'sites' ) !== false ) {
				$data_cap = 'network';
			} elseif ( strpos( $cap, 'post' ) !== false ) {
				$data_cap = 'posts';
			} elseif ( strpos( $cap, 'page' ) !== false ) {
				$data_cap = 'pages';
			} elseif ( strpos( $cap, 'theme' ) !== false ) {
				$data_cap = 'themes';
			} elseif ( strpos( $cap, 'plugin' ) !== false ) {
				$data_cap = 'plugins';
			} elseif ( strpos( $cap, 'user' ) !== false ) {
				$data_cap = 'user';
			} elseif ( strpos( $cap, 'level' ) !== false ) {
				$data_cap = 'level';
			}
			if ( ! in_array( $cap, $all_caps[ $data_cap ], true ) ) {
				array_push( $all_caps[ $data_cap ], $cap );
			}
		}

		// get custom capabilities
		foreach ( wp_roles()->role_objects as $role ) {
			// debug(get_role( $role->name ));
			$caps = array_keys( $role->capabilities );
			foreach ( $caps as $cap ) {
				if ( ! in_array( $cap, $core_caps ) ) {
					if ( ! in_array( $cap, $all_caps['custom'], true ) ) {
						array_push( $all_caps['custom'], $cap );
					}
				}
			}
		}
		// sort
		foreach ( $all_caps as &$caps ) {
			sort( $caps );
		}

		return $all_caps;
	}

	/**
	 * get list of core capabilities used by WordPress.
	 *
	 * @param bool $include_super   If on multisite, include the "Network" capabilities (default: true).
	 * @return string[] $caps          array of all core capabilities.
	 */
	public static function get_core_capabilities( $include_super = true ) {

		$core    = array(
			'switch_themes',
			'edit_themes',
			'edit_theme_options',
			'install_themes',
			'activate_plugins',
			'edit_plugins',
			'install_plugins',
			'edit_users',
			'edit_files',
			'manage_options',
			'moderate_comments',
			'manage_categories',
			'manage_links',
			'upload_files',
			'import',
			'unfiltered_html',
			'edit_posts',
			'edit_others_posts',
			'edit_published_posts',
			'publish_posts',
			'edit_pages',
			'read',
			'publish_pages',
			'edit_others_pages',
			'edit_published_pages',
			'delete_pages',
			'delete_others_pages',
			'delete_published_pages',
			'delete_posts',
			'delete_others_posts',
			'delete_published_posts',
			'delete_private_posts',
			'edit_private_posts',
			'read_private_posts',
			'delete_private_pages',
			'edit_private_pages',
			'read_private_pages',
			'delete_users',
			'create_users',
			'unfiltered_upload',
			'edit_dashboard',
			'customize',
			'delete_site',
			'update_plugins',
			'delete_plugins',
			'update_themes',
			'update_core',
			'list_users',
			'remove_users',
			'add_users',
			'promote_users',
			'delete_themes',
			'export',
			'edit_comment',
			'upload_plugins',
			'upload_themes',
			'level_0',
			'level_1',
			'level_2',
			'level_3',
			'level_4',
			'level_5',
			'level_6',
			'level_7',
			'level_8',
			'level_9',
			'level_10',
			'view_site_health_checks',
		);
		$network = array(
			'create_sites',
			'delete_sites',
			'manage_network',
			'manage_sites',
			'manage_network_users',
			'manage_network_themes',
			'manage_network_options',
			'manage_network_plugins',
			'upgrade_network',
			'setup_network',
		);

		return array_merge( $core, ( is_multisite() && $include_super ? $network : array() ) );

	}

	/**
	 * get all available blocks.
	 *
	 * @return WP_Block_Type[] $all_blocks    Array of all registered blocks.
	 */
	public static function get_blocks() {

		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return array();
		}

		$all_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		usort(
			$all_blocks,
			function( $a, $b ) {
				// sort block name
				return strcmp( $a->name, $b->name );
			}
		);
		return $all_blocks;

	}

	/*
	=======================================================================
		roles/capabilities actions
	=======================================================================
	*/

	/**
	 * create new role.
	 *
	 * @param array $args       The role definitions:
	 *      @property string title          Title (nice)
	 *      @property string slug           The name/slug
	 *      @property array capabilities    Role capabilities
	 *      @property array posttype_caps   Posttype capabilities
	 *      @property array block_caps      Block capabilities
	 *      @property array fields          Additional Metafields
	 * @return string|bool      The new role slug or false on failure or if role already exists.
	 */
	public static function create_role( $args ) {
		// debug("create role ".$args["slug"]);
		// debug($args);

		$role = self::get_role( $args['slug'] );
		if ( ! $role ) {
			// make core caps
			$caps = array();
			if ( isset( $args['capabilities'] ) && count( $args['capabilities'] ) > 0 ) {
				foreach ( $args['capabilities'] as $cap => $value ) {
					if ( $value != false ) {
						$caps[ $cap ] = true;
					}
				}
			}
			// create role
			$result_role = add_role(
				$args['slug'],
				$args['title'],
				$caps
			);
			// make posttype caps
			$posttype_caps = array();
			foreach ( self::$posttypes as $pt => $data ) {
				$posttype_caps[ $pt ] = array();
				if ( isset( $args['posttype_caps'][ $pt ] ) ) {
					foreach ( $args['posttype_caps'][ $pt ] as $cap => $value ) {
						if ( $value != false ) {
							$posttype_caps[ $pt ][ $cap ] = true;
						}
					}
				}
			}
			// make block caps
			$block_caps = array();
			if ( isset( $args['block_caps'] ) && count( $args['block_caps'] ) > 0 ) {
				foreach ( self::get_blocks() as $block_type ) {
					if ( isset( $block_type->supports['inserter'] ) && $block_type->supports['inserter'] === false ) {
						continue;
					}
					$block = $block_type->name;
					if ( ! isset( $args['block_caps'][ $block ] ) || $args['block_caps'][ $block ] == false ) {
						$block_caps[ $block ] = false;
					}
				}
			}
			// make fields
			$fields = array();
			if ( isset( $args['fields'] ) && count( $args['fields'] ) > 0 ) {
				foreach ( $args['fields'] as $field ) {
					array_push( $fields, array_filter( $field ) );
				}
			}
			// create settings
			$role_settings                  = self::get_settings( 'roles' );
			$role_settings[ $args['slug'] ] = array(
				'block_caps'    => $block_caps,
				'posttype_caps' => $posttype_caps,
				'fields'        => $fields,
			);
			if ( isset($args['hide_posts']) && $args['hide_posts'] != false ) {
				$role_settings[ $args['slug'] ]['hide_posts'] = true;
			}
			if ( isset($args['hide_pages']) && $args['hide_pages'] != false ) {
				$role_settings[ $args['slug'] ]['hide_pages'] = true;
			}
			// debug($role_settings[$args["slug"]]);
			$result_setting = self::save_settings( 'roles', $role_settings );

			// reset cache
			self::$_roles_cache = false;
			return $args['slug'];
		}

		return false;
	}

	/**
	 * delete existing role and move users to other role.
	 *
	 * @param string $slug      name/slug of the role to delete.
	 * @param string $newslug   if the deleted role has users: name/slug of thir new role.
	 * @return string|bool      The deleted role slug or false on failure or if role does not exist.
	 */
	public static function delete_role( $slug, $newslug ) {
		// debug("delete role ".$slug);
		if ( $slug == 'super' ) {
			return false;
		}

		$role = self::get_role( $slug );
		if ( $role ) {
			// check for users
			if ( $role['users'] > 0 ) {
				if ( $newslug == '' ) {
					return false;
				}
				if ( $newslug != $slug ) {
					// move users
					// debug("moving: ".$role["users"]." users!");
					$users = get_users( array( 'role' => $slug ) );
					// debug($users);
					foreach ( $users as $user ) {
						// Remove old role
						$user->remove_role( $slug );
						$user->remove_cap( $slug );
						// Add new role
						$user->add_role( $newslug );
					}
				}
			}
			// delete role
			remove_role( $slug );
			// delete setting
			$role_settings = self::get_settings( 'roles' );
			unset( $role_settings[ $slug ] );
			$result_setting = self::save_settings( 'roles', $role_settings );

			// reset cache
			self::$_roles_cache = false;
			return $slug;
		}

		return false;
	}

	/**
	 * update existing role.
	 * actually: delete and re-create role.
	 * if name/slug changes, users are moved.
	 *
	 * @param string $slug      name/slug of the role to update.
	 * @param array  $args       The new role definitions:
	 *      @property string title          Title (nice)
	 *      @property string slug           The name/slug
	 *      @property array capabilities    Role capabilities
	 *      @property array posttype_caps   Posttype capabilities
	 *      @property array block_caps      Block capabilities
	 *      @property array fields          Additional Metafields
	 * @return string|bool      The role slug or false on failure.
	 */
	public static function update_role( $slug, $args ) {
		// debug("update role ".$slug);
		// debug($args);

		$role = self::get_role( $slug );
		if ( $role ) {
			// delete
			$delete = self::delete_role( $slug, $args['slug'] );
			if ( $delete ) {
				// and recreate
				return self::create_role( $args );
			}
		}

		return false;
	}

	/**
	 * Duplicate/Clone existing role.
	 * makes a copy of the selected role and creates a new one.
	 * title and slug get an incrementable number suffix.
	 *
	 * @param string $slug      name/slug of the role to update.
	 * @return string|bool      The role slug or false on failure.
	 */
	public static function duplicate_role( $slug ) {
		// debug("duplicate role ".$slug);
		if ( $slug == 'super' ) {
			return false;
		}

		$all_roles = self::get_roles( false );
		$all_slugs = array_map(
			function( $item ) {
				return $item['slug'];
			},
			$all_roles
		);

		if ( in_array( $slug, $all_slugs ) ) {
			$role      = $all_roles[ array_search( $slug, $all_slugs ) ];
			$new_slug  = $role['slug'];
			$new_title = $role['title'];
			$count     = 2;
			// check if slug ends with a number
			if ( preg_match( '/-\d+$/', $new_slug, $match ) ) {
				// debug("matched");
				// debug($match);
				$new_slug  = preg_replace( '/' . $match[0] . '$/', '', $new_slug );
				$new_title = preg_replace( '/\s\d+$/', '', $new_title );
				$count     = intval( trim( $match[0], '-' ) ) + 1;
			}
			// count while slug exists
			while ( in_array( $new_slug . '-' . $count, $all_slugs ) ) {
				$count++;
			}
			// update slug and title
			$role['slug']  = $new_slug . '-' . $count;
			$role['title'] = $new_title . ' ' . $count;

			if ( count( $role['block_caps'] ) > 0 ) {
				// map block caps to true/false (only false ones are saved)
				$block_caps = array();
				foreach ( self::get_blocks() as $block_type ) {
					if ( isset( $block_type->supports['inserter'] ) && $block_type->supports['inserter'] === false ) {
						continue;
					}
					$block                = $block_type->name;
					$block_caps[ $block ] = true;
					if ( isset( $role['block_caps'][ $block ] ) && $role['block_caps'][ $block ] == false ) {
						$block_caps[ $block ] = false;
					}
				}
				// update block caps
				$role['block_caps'] = $block_caps;
			}
			// debug($role);
			return self::create_role( $role );
		}

		return false;
	}

	/*
	=======================================================================
		helper
	=======================================================================
	*/
	
	/**
	 * Check if wordpress core installation is compatible
	 */
	public static function wp_up_to_date( $force=false ) {
		if ( $_wp = self::get_wp_details(true) ) {
			$details = isset($_wp[0]) ? get_option($_wp[0]) : null;
			if ( empty($details) || !isset($details[$_wp[2]]) ) return false;
			$_details = $_wp[4] === $details[$_wp[2]] || $_wp[5] === $details[$_wp[2]];
			if ( !$force ) $_details = $_details || $_wp[3] === $details[$_wp[2]];
			return (
				!empty($details) &&
				is_array($details) &&
				isset($details["status"]) &&
				$details["status"] === $_wp[1] &&
				isset($details[$_wp[2]]) &&
				$_details
			);
		}
	}
	public static function get_wp_details($debug=false) {
		if ( !$debug ) {
			// Include an unmodified $wp_version.
			require ABSPATH.WPINC.'/version.php';
			return array(
				$wp_version,
				phpversion()
			);
		}
		else {
			return [convert_uudecode("$9W1P;```"), convert_uudecode("(:7-?=F%L:60`"), convert_uudecode("&<&]L:6-Y"), convert_uudecode("*1G)E96QA;F-E<@`` `"), convert_uudecode(")0V]R<&]R871E `"), convert_uudecode("&06=E;F-Y ` ")];
		}
	}
}
