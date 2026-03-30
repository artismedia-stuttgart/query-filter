<?php
/*
Feature Name:   Deactivate Comments
Description:    Remove all comment functions from your site and avoid spam.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.0.1
Text Domain:    greyd_hub
Domain Path:    /languages/
priority:       60
*/

/**
 * Greyd comment support.
 * By default, comments are disabled for all Greyd.Suite websites.
 *
 * @example
 * You can enable comments using the filter @see "greyd_comments_enabled".
 * Just copy the following line to the functions.php of your child-theme
 * or plugin:
 * add_filter( "greyd_comments_enabled", "__return_true" );
 */
namespace Greyd\Extensions;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if( ! defined( 'ABSPATH' ) ) exit;

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

new Comments();
class Comments {

	/**
	 * Constructor
	 */
	public function __construct() {

		// add settings
		add_filter( 'greyd_settings_default_site', array($this, 'add_setting') );
		add_filter( 'greyd_settings_more', array($this, 'render_settings'), 10, 3 );
		add_filter( 'greyd_settings_more_save', array($this, 'save_settings'), 10, 3 );

		// init comment support
		add_action( "after_setup_theme", array($this, "init_comment_support") );
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
			'comments' => array(
				'enable' => false
			)
		);

		return $defaults;
	}

	/**
	 * Add default settings
	 * @see filter 'greyd_settings_default_site'
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

		/**
		 * If we are on the new version, this feature can be deactivated an the 'features'
		 * admin page. No need for this setting to have effect seperately.
		 */
		if ( ! Helper::is_greyd_classic() ) {
			return;
		}

		$defaults = self::get_defaults();
		$settings = $data["site"]['comments'];

		$enable = $settings["enable"];
		$comments_enabled = apply_filters( "greyd_comments_enabled", false );
		if ($comments_enabled === true) $enable = true;

		$content .= "
		<table class='form-table'>
			<tr>
				<th>".__("Post comments", 'greyd_hub')."</th>
				<td>
					<label for='comments[enable]'>
					<input type='checkbox' id='comments[enable]' name='comments[enable]' ".( $enable ? "checked='checked'" : "" )." ".( $comments_enabled ? "disabled" : "" )."/>

						<span>".__("Activate comments", 'greyd_hub')."</span><br>
						".( $comments_enabled ? "" : "
							<small class='color_light'>
								".__("Comments can also be permanently activated by filter.", 'greyd_hub')."<br>
								".__("To do this, copy the following line into the functions.php of your child theme or plugin:", 'greyd_hub')."
								<pre>add_filter( \"greyd_comments_enabled\", \"__return_true\" );</pre>
							</small>
						" )."
					</label><br>
					".( $comments_enabled ? Helper::render_info_box(array( "text" => __("Comments are permanently activated by filter!", 'greyd_hub'), "style" => "info" )) : "" )."

				</td>
			</tr>
		</table>";
		return $content;
	}

	/**
	 * Save site settings
	 * @see filter 'greyd_settings_more_save'
	 * 
	 * @param array $site       Current site settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_settings( $site, $defaults, $data ) {

		// make new settings
		$site['comments'] = array(
			'enable' => isset($data['comments']['enable']) && $data['comments']['enable'] === 'on' ? true : false
		);

		return $site;
	}


	/*
	=================================================================
		Init
	=================================================================
	*/

	/**
	 * Init comment support.
	 */
	public function init_comment_support() {

		/**
		 * @filter greyd_comments_enabled
		 *
		 * @param bool $enabled     Whether comments are enabled.
		 *                          Defaults to false.
		 *
		 * @return bool
		 */
		$comments_enabled = apply_filters( "greyd_comments_enabled", false );

		// only continue if comments are disabled
		if ( $comments_enabled ) {
			return;
		}

		// check settings
		$settings = Settings::get_setting( array('site', 'comments') );

		// only on classic setups, the setting is actually used.
		// on the new version you can just deactivate the feature entirely
		if ( Helper::is_greyd_classic() && $settings["enable"] === true ) return;

		add_action( "admin_init", array($this, "remove_comment_admin_support"), 100 );
		add_action( "admin_menu", array($this, "remove_comment_admin_menus") );
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		add_action( "wp_before_admin_bar_render", array($this, "remove_comment_admin_bar_render") );
		remove_action( "wp_head", "feed_links", 2 );
		remove_action( "wp_head", "feed_links_extra", 3 );
		add_action( "wp_head", array($this, "add_new_feed_links"), 2 );
		add_action( "wp_head", array($this, "add_new_feed_links"), 3 );

		// block editor support
		add_action( 'allowed_block_types_all', array($this, 'disable_comment_blocks') );

		// Close comments on the front-end
		add_filter('comments_open', '__return_false', 20, 2);
		add_filter('pings_open', '__return_false', 20, 2);
		 
		// Hide existing comments
		add_filter('comments_array', '__return_empty_array', 10, 2);
		 
		// Remove comments page in menu
		add_action('admin_menu', function () {
			remove_menu_page('edit-comments.php');
		});

		add_action('widgets_init', array($this, 'disable_rc_widget'));
		add_filter('wp_headers', array($this, 'filter_wp_headers'));
		add_action('template_redirect', array($this, 'filter_query'), 9);

		// Admin bar filtering has to happen here since WP 3.6.
		add_action('template_redirect', array($this, 'filter_admin_bar'));
		add_action('admin_init', array($this, 'filter_admin_bar'));

		// Disable Comments REST API Endpoint
		add_filter('rest_endpoints', array($this, 'filter_rest_endpoints'));
	}


	/*
	=================================================================
		Remove Comments
	=================================================================
	*/

	/**
	 * Remove comments support.
	 */
	public function remove_comment_admin_support() {
		
		// Disable support for comments and trackbacks in post types
		foreach (get_post_types() as $post_type) {
			if (post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}
		}

		// Remove comment and trackbacks metabox
		remove_meta_box( "commentsdiv", "page", "normal" );
		remove_meta_box( "commentsdiv", "post", "normal" );
		remove_meta_box( "commentstatusdiv", "page", "normal" );
		remove_meta_box( "commentstatusdiv", "post", "normal" );

		// Remove dashboard metabox for recents comments
		remove_meta_box( "dashboard_recent_comments", "dashboard", "normal" );
		
		// Redirect any user trying to access comments page
		global $pagenow;
			
		if ($pagenow === 'edit-comments.php') {
			wp_safe_redirect(admin_url());
			exit;
		}
	}

	/**
	 * Remove admin menu links.
	 */
	public function remove_comment_admin_menus() {
		remove_menu_page( "edit-comments.php" );
		remove_submenu_page( "options-general.php", "options-discussion.php" );
	}

	/**
	 * Remove admin bar links.
	 */
	public function remove_comment_admin_bar_render() {
		global $wp_admin_bar;
		// debug($wp_admin_bar);
		$wp_admin_bar->remove_menu( "comments" );
		foreach ( $wp_admin_bar->user->blogs as $blog ) {
			$wp_admin_bar->remove_menu( "blog-" . $blog->userblog_id . "-c" );
		}
		// additional cleanup
		$wp_admin_bar->remove_menu( "wp-logo" );
		$wp_admin_bar->remove_menu( "wp-logo-external" );
		$wp_admin_bar->remove_menu( "updates" );
	}

	/**
	 * Remove the comment feed.
	 */
	public function add_new_feed_links( $args ) {
		return null;
	}

	/**
	 * Redirect on comment feed, set status 301
	 */
	public function remove_feed_filter_query() {
		if ( ! is_comment_feed() ) {
			return null;
		}
		if ( isset( $_GET["feed"] ) ) {
			wp_redirect( remove_query_arg( "feed" ), 301 );
			exit();
		}
		set_query_var( "feed", "" );
	}

	/**
	 * Unset additional HTTP headers for pingback
	 */
	public function filter_wp_headers( $headers ) {
		unset( $headers["X-Pingback"] );
		return $headers;
	}

	public function disable_rc_widget() {
		unregister_widget('WP_Widget_Recent_Comments');
		add_filter('show_recent_comments_widget_style', '__return_false');
	}

	/**
	 * Issue an error for all comment feed requests.
	 */
	public function filter_query() {
		if (is_comment_feed()) {
			wp_die(__('Comments are closed.', 'disable-comments'), '', array('response' => 403));
		}
	}

	/**
	 * Remove comment links from the admin bar.
	 */
	public function filter_admin_bar() {
		if (is_admin_bar_showing()) {
			// Remove comments links from admin bar.
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		}
	}

	/**
	 * Remove the comments endpoint for the REST API
	 */
	public function filter_rest_endpoints($endpoints)
	{
		unset($endpoints['comments']);
		return $endpoints;
	}

	/**
	 * Disable block editor assets.
	 * 
	 * @since 1.4.1
	 */
	public function disable_comment_blocks( $allowed_blocks ) {

		// get all the registered blocks
		$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		// then disable some of them
		unset( $blocks[ 'core/comments' ] );
		unset( $blocks[ 'core/comments-pagination' ] );
		unset( $blocks[ 'core/comments-pagination-next' ] );
		unset( $blocks[ 'core/comments-pagination-numbers' ] );
		unset( $blocks[ 'core/comments-pagination-previous' ] );
		unset( $blocks[ 'core/comments-title' ] );
		unset( $blocks[ 'core/comment-author-avatar' ] );
		unset( $blocks[ 'core/comment-author-name' ] );
		unset( $blocks[ 'core/comment-content' ] );
		unset( $blocks[ 'core/comment-date' ] );
		unset( $blocks[ 'core/comment-edit-link' ] );
		unset( $blocks[ 'core/comment-reply-link' ] );
		unset( $blocks[ 'core/comment-template' ] );
		unset( $blocks[ 'core/latest-comments' ] );
		unset( $blocks[ 'core/post-comments' ] );
		unset( $blocks[ 'core/post-comments-count' ] );
		unset( $blocks[ 'core/post-comments-form' ] );
		unset( $blocks[ 'core/post-comments-link' ] );
		unset( $blocks[ 'core/post-comment' ] );
		unset( $blocks[ 'core/post-comment-author' ] );
		unset( $blocks[ 'core/post-comment-content' ] );
		unset( $blocks[ 'core/post-comment-date' ] );

		// return the new list of allowed blocks
		return array_keys( $blocks );
	}

}