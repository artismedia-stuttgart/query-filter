<?php
/**
 * Data for block editor assets.
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

new data($config);
class data {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;

		if ( is_admin() ) {
			add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts'), 11 );
		}
	}

	/**
	 * Register and enqueue all the scripts for the editor.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {

		if ( ! is_admin() ) return;

		// add data to tools script
		wp_localize_script('greyd-tools', 'greyd', array( 'data' => $this->get_greyd_data() ));

	}

	/**
	 * Get all the data for JS
	 */
	public function get_greyd_data() {

		// get urls
		$urls = array(
			'home' => esc_url(get_bloginfo('url')),
			'current' => esc_url(get_bloginfo('url')),
			'rss' => esc_url(get_bloginfo('rss2_url')),
			'comments_rss' => esc_url(get_bloginfo('comments_rss2_url')),
		);

		// get language
		$language_default = \Greyd\Helper::is_translation_tool_active() ? \Greyd\Helper::get_default_language() : null;
		$language_post = "";

		// the current post
		$current_post_id = isset($_REQUEST['post']) && !empty($_REQUEST['post']) ? intval($_REQUEST['post']) : null;
		$current_post = (object)array(
			'id' => $current_post_id,
			'name' => "",
			'title' => "",
			'post_type' => get_post_type(),
		);
		if ($current_post->id) {
			// get post
			$post = get_post($current_post->id);
			// make values
			$urls['current'] = esc_url(get_permalink($current_post->id));
			$current_post->name = $post->post_name;
			$current_post->title = $post->post_title;
			$language_post = \Greyd\Helper::get_post_language( $current_post->id );
		}

		// get contents
		// all posttypes, taxes and posts
		$all_post_types = array();
		$all_taxes = array( 'post' => array() );
		$all_posts = array( 'post' => array(), 'page' => array());	

		if (is_plugin_active('woocommerce/woocommerce.php')) {
			$all_taxes['product'] = array();
			$all_posts['product'] = array();
		}

		if ( method_exists('\Greyd\Posttypes\Posttype_Helper', 'get_dynamic_posttypes' ) ) {
			foreach (\Greyd\Posttypes\Posttype_Helper::get_dynamic_posttypes() as $id => $pt) {
				$all_post_types[$pt['slug']] = $pt;
				$all_taxes[$pt['slug']] = array();
				$all_posts[$pt['slug']] = array();
			}
		}

		// get public post types
		$include   = array_merge( array( 'page', 'post' ), array_keys( $all_post_types ) );
		$exclude   = array( 'attachment' );
		$posttypes = array_keys( get_post_types( array( 'public' => true, 'exclude_from_search' => false ) ) );
		$all_public_posttypes = array_values( array_unique( array_diff( array_merge( $include, $posttypes ), $exclude ) ) );

		foreach ($all_public_posttypes as $pt) {

			// get all taxonomies (categories, tags, customtaxes)
			$taxes = \Greyd\Helper::get_all_taxonomies($pt);
			foreach ($taxes as $j => $tax) {
				$terms = \Greyd\Helper::get_all_terms($tax->slug);
				$taxes[$j]->values = $terms;
			}
			$all_taxes[$pt] = $taxes;
			$all_post_types[$pt]['slug'] = $pt;
			$all_post_types[$pt]['taxes'] = $taxes;

			// get all posts
			$posts = \Greyd\Helper::get_all_posts($pt);
			if ($posts) {
				// debug($posts);
				foreach ($posts as $post) {
					$all_posts[$pt][] = (object)array( 
						'id' => $post->id, 
						'slug' => $post->slug, 
						'title' => $post->title, 
						'type' => $pt, 
						'lang' => $post->lang
					);
				}
			}
		}

		// get all forms
		// todo: use filter in Forms
		$all_forms = false; // 'false' if Greyd.Forms is inactive
		if (\Greyd\Helper::is_active_plugin('greyd_tp_forms/init.php')) {
			$all_forms = array(); // 'array()' if Greyd.Forms is active
			$forms = \Greyd\Helper::get_all_posts('tp_forms');
			if ($forms) {
				// debug($forms);
				foreach ($forms as $form) {
					$all_forms[] = (object)array( 
						'id' => $form->id, 
						'title' => $form->title, 
						'lang' => $form->lang
					);
				}
			}
		}

		// get sources of all media files
		$media_urls = array();
		$attachments = get_posts(array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null, // any parent
		));
		if ($attachments) {
			// debug($attachments);
			foreach ($attachments as $attachment) {
				$src = "";
				if (strpos($attachment->post_mime_type, 'image/') === 0) {
					$src = wp_get_attachment_image_src( $attachment->ID, 'full' )[0];
				}
				else {
					$src = $attachment->guid;
				}

				$media_urls[$attachment->ID] = array(
					'id' => $attachment->ID,
					'src' => $src,
					'type' => $attachment->post_mime_type,
					'title' => $attachment->post_title,
					'lang' => \Greyd\Helper::get_post_language($attachment)
				);
			}
		}
		// debug($media_urls);

		// get all nav menus
		$nav_menus = array();
		$menus = wp_get_nav_menus();
		if ($menus) {
			// debug($menus);
			foreach ($menus as $menu) {
				$items = array();
				$sub = array();
				foreach (wp_get_nav_menu_items($menu->term_id) as $item) {
					if ($item->menu_item_parent == 0)
						$items[] = array( 'id' => $item->ID, 'title' => $item->title, 'url' => $item->url );
					else
						$sub[$item->menu_item_parent][] = array( 'id' => $item->ID, 'title' => $item->title, 'url' => $item->url );
				}
				for ($i=0; $i<count($items); $i++) {
					if (isset($sub[$items[$i]['id']])) 
						$items[$i]['items'] = $sub[$items[$i]['id']];
				}
				// debug($menu);
				$nav_menus[] = array( 'id' => $menu->term_id, 'slug' => $menu->slug, 'title' => $menu->name, 'items' => $items );
			}
		}
		// debug($nav_menus);

		// FSE navigation menus
		$all_navigations = array();
		if ( class_exists('\Greyd\Helper') ) {
			$navigations = \Greyd\Helper::get_all_posts('wp_navigation');
			if ($navigations) $all_navigations = $navigations;
		}

		// get all blocktypes
		$all_block_types = array();
		$block_types = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		// debug($block_types);
		foreach($block_types as $name => $type) {
			if ($type->api_version < 2 && strpos($name, 'greyd/') !== 0) continue;
			if (!empty($type->parent)) continue;
			if (strpos($name, 'core/post-') > -1) continue;
			if (strpos($name, 'core/site-') > -1) continue;
			if (strpos($name, 'core/query-') > -1) continue;
			// debug($name);
			// debug($type);
			array_push($all_block_types, $name);
		}
		// debug($all_block_types);

		// url params
		$url_params = array();
		if ( class_exists( '\Greyd\Extensions\Cookie_Handler' ) ) {
			$url_params = \Greyd\Extensions\Cookie_Handler::get_supported_params();
		} else if ( class_exists('\url_handler') ) {
			$url_params = \url_handler::get_params();
		}
		
		// versions
		global $wp_version;
		$versions = array( "wp" => $wp_version );
		// theme
		$theme = is_child_theme() ? wp_get_theme(get_template()) : wp_get_theme();
		$versions[$theme->get_stylesheet()] = $theme->exists() ? $theme->get( 'Version' ) : '0';
		// plugins
		$plugins = class_exists('\Greyd\Helper') ? (object) \Greyd\Helper::active_plugins() : array();
		if ( !function_exists('get_plugin_data') ) require_once ABSPATH.'wp-admin/includes/plugin.php';
		foreach( $plugins as $plugin ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR."/".$plugin, false, false );
			$version = isset($plugin_data['Version']) && !empty($plugin_data['Version']) ? $plugin_data['Version'] : '0';
			$versions[$plugin_data['TextDomain']] = $version;
		}
		// debug($versions);

		$data = array(
			// info
			'theme' => $theme->get_stylesheet(),
			'urls' => $urls,
			'icon_url' => plugin_dir_url( __FILE__ ) . 'assets/icon', // deprecated (maybe used in forms)
			'language' => array( 'default' => $language_default, 'post' => $language_post ),
			'post_id' => $current_post->id,
			'post_name' => $current_post->name,
			'post_title' => $current_post->title,
			'post_type' => $current_post->post_type,
			'posts_per_page' => get_option('posts_per_page'),
			// content
			'post_types' => array_values($all_post_types),
			'all_posts' => $all_posts,
			'all_taxes' => $all_taxes,
			'media_urls' => $media_urls,
			'nav_menus' => $nav_menus,
			'navigation_menus' => $all_navigations,
			'all_block_types' => $all_block_types,
			'all_posttypes' => $all_public_posttypes,
			'forms' => $all_forms, // todo
			// settings
			'settings' => class_exists('\Greyd\Settings') ? \Greyd\Settings::get_setting( array( 'site' ) ) : array(),
			'icons' => class_exists('\Greyd\Icons') ? \Greyd\Icons::get_icons() : icons::get_icons(),
			'url_params' => $url_params,
			'user_roles' => get_editable_roles(),
			'users' => get_users( array( 'fields' => array( 'ID', 'display_name' ) ) ),
			'plugins' => $plugins,
			'versions' => $versions,
			// compatibility utils
			'is_greyd_classic' => \Greyd\Helper::is_greyd_classic(),
			'is_greyd_beta' => \Greyd\Helper::is_greyd_beta(),
			'is_greyd_alpha' => defined( 'IS_GREYD_ALPHA' )
		);

		/**
		 * Filter Blockeditor Data values.
		 * The whole Data Array is added as inline javascript.
		 * It is accessible in the Blockeditor js script under the var greyd.data
		 * 
		 * @filter 'greyd_blocks_editor_data'
		 * 
		 * @param object $data     Original Data Array
		 * 
		 * @return object $data    Data Array with filtered Values
		 */
		return apply_filters( 'greyd_blocks_editor_data', $data );

	}
}
