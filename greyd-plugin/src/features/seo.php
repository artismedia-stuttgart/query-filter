<?php
/*
Feature Name:   SEO Extensions
Description:    Extensions for Yoast & Rankmath.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       92
*/

/**
 * SEO plugin extensions.
 * - sitemap
 * - Yoast
 * - Rankmath
 * 
 * @since 1.2.8
 * * Enable yoast metabox in dynamic-template archives.
 * 
 * @since 1.4.1
 * * Enable rankmath metabox in dynamic-template archives.
 */
namespace Greyd\Extensions;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( !defined('ABSPATH') ) exit;

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

new Seo();
class Seo {
	
	/**
	 * Constructor
	 */
	public function __construct() {

        // settings
        add_filter( 'greyd_settings_default_site', array($this, 'add_setting') );
        add_filter( 'greyd_settings_basic', array($this, 'render_settings'), 5, 3 );
        add_filter( 'greyd_settings_more_save', array($this, 'save_settings'), 10, 3 );

		// int extension after plugins are initialized and \Greyd\Plugin functions are available
		// https://codex.wordpress.org/Plugin_API/Action_Reference
		add_action( 'init', array($this, 'init') );

    }
    public function init() {

        if (Helper::is_active_plugin('wordpress-seo/wp-seo.php')) {
            // yoast sitemap
            if (Settings::get_setting( array('site', 'seo', 'sitemap') ) == 'greyd') {
				// greyd setup
                add_action( 'admin_enqueue_scripts', array($this, 'add_yoast_script'), 999 );
                add_action( 'init', array($this, 'yoast_check_options'), 999 );
                add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'yoast_exclude_post_type'), 10, 2 );
                add_filter( 'wpseo_sitemap_exclude_taxonomy', array( $this, 'yoast_exclude_taxonomy'), 10, 2 );
                add_filter( 'wpseo_sitemap_exclude_author', array( $this, 'yoast_exclude_authors'), 10, 1 );
			}

			/**
			 * new filters: Enable yoast metabox in dynamic-template archives.
			 * Load only if dynamic feature exists
			 */
			if ( class_exists( '\Greyd\Dynamic\Admin' ) ) {
				// enable the yoast seo metabox when editing archive templates
				add_filter( 'wpseo_accessible_post_types', array($this, 'add_dynamic_templates_to_accessible_post_types') );
				add_filter( 'wpseo_enable_editor_features_dynamic_template', array($this, 'enable_metabox_for_dynamic_templates') );

				// filter the seo title & description of archives
				add_filter( 'wpseo_title', array($this, 'filter_title') );
				add_filter( 'wpseo_opengraph_title', array($this, 'filter_title') );
				add_filter( 'wpseo_twitter_title', array($this, 'filter_title') );
				add_filter( 'wpseo_metadesc', array($this, 'filter_description') );
				add_filter( 'wpseo_opengraph_desc', array($this, 'filter_description') );
				add_filter( 'wpseo_twitter_description', array($this, 'filter_description') );
			}
        }
        else {
            // wp 5.5 sitemap
            if (Settings::get_setting( array('site', 'seo', 'sitemap') ) == 'greyd') {
				// greyd setup
                add_filter( 'wp_sitemaps_enabled', '__return_true' );
                add_filter( 'wp_sitemaps_add_provider', array( $this, 'sitemaps_add_provider'), 10, 2 );
                add_filter( 'wp_sitemaps_post_types', array( $this, 'sitemaps_post_types') );
                add_filter( 'wp_sitemaps_posts_entry', array( $this, 'sitemaps_posts_entry') );
            }
            else {
				// no sitemap
                add_filter( 'wp_sitemaps_enabled', '__return_false' );
            }
        }

		/**
		 * enable the rankmath seo metabox when editing archive templates.
		 * 
		 * The hook name 'rank_math/excluded_post_types is a bit misleading. It's actually the opposite.
		 * If you add a post_type to the array it will be included not excluded.
		 */
		add_filter( 'rank_math/excluded_post_types', array($this, 'add_dynamic_templates_to_accessible_post_types') );

		/**
		 * Hook into rankmath content analysis data.
		 * @since 2.15.0
		 */
		add_action( 'enqueue_block_editor_assets', array($this, 'register_block_editor_scripts') );

	}

	/**
	 * Register and enqueue rankmath integration script.
	 * @action enqueue_block_editor_assets
	 */
	public function register_block_editor_scripts() {
		
		wp_register_script(
			'greyd-rank-math-integration',
			plugin_dir_url( __FILE__ ).'seo-rank-math.js',
			array( 'wp-data', 'wp-blocks', 'wp-hooks', 'rank-math-analyzer' ),
			GREYD_VERSION,
			true
		); 
		wp_enqueue_script( 'greyd-rank-math-integration' );

	}

    /**
     * =================================================================
     *                          SETTINGS
     * =================================================================
     */

    // default settings
    public static function get_defaults() {

        $defaults = array( 
            'seo' => array(
                'sitemap' => 'greyd',
                'show_taxes' => 'false',
                'show_authors' => 'false',
                'show_archives' => 'false',
                'add_dates' => 'true',
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
     * @see filter 'greyd_settings_basic'
     * 
     * @param string $content   Content of all additional settings.
     * @param string $mode      'site' | 'network_site' | 'network_admin'
     * @param array $data       Current settings.
     */
    public function render_settings( $content, $mode, $data ) {

        if ( $mode == 'network_admin' ) return $content;

		$yoast = Helper::is_active_plugin('wordpress-seo/wp-seo.php');
		$sitemap_link = ($yoast) ? home_url('sitemap_index.xml') : home_url('wp-sitemap.xml');
		$sitemap = isset($data['site']['seo']['sitemap']) ? strval($data['site']['seo']['sitemap']) : 'greyd';

		ob_start();

		echo "<table class='form-table'>
				<tr><th>".__("SEO Sitemap", 'greyd_hub')."<br>
					<small><a href=".$sitemap_link." target='_blank'>"._x("View sitemap", "small", 'greyd_hub')."</a></small>
				</th><td>";

			$reading_link = "<a href=".admin_url('options-reading.php')." target='_blank'>".__("Go to the reading settings", 'greyd_hub')."</a>";
			if (get_option('blog_public') == 0) {
				if (!(!$yoast && $sitemap == 'false')) {
					echo Helper::render_info_box(array(
						"style" => "danger",
						"text" => "<b>".__("We have found that search engines are blocked on this website.", 'greyd_hub')."</b><br>".
								__("If you want search engines to display this website in their search results, you need to uncheck the search engine visibility setting in the reading settings.", 'greyd_hub')."<br>".
								$reading_link,
					));
				}
			}
			else {
				echo Helper::render_info_box(array(
					"style" => "info",
					"text" => "<b>".__("We have determined that search engine access to this website is permitted.", 'greyd_hub')."</b><br>".
							__("If this is not wanted, you have to activate the checkbox under Search Engine Visibility in the Reading settings.", 'greyd_hub')."<br>".
							$reading_link,
				));
			}

			$_val = 'greyd';
			$title = __("Use optimized Greyd sitemap (recommended)", 'greyd_hub');
			if ($yoast) $title = __("Optimize Yoast Sitemap for Greyd.Suite (recommended)", 'greyd_hub');
			echo "<label for='seo_".$_val."'>
					<input type='radio' name='seo_sitemap' value='".$_val."' id='seo_".$_val."' ".($_val === $sitemap ? "checked='checked'" : "" )." onchange='greyd.backend.toggleRadioByClass(\"seo\", \"$_val\")'/>
					<span>".$title."</span>
				</label><br>";

			if ($yoast) {
				$title = __("Customize Yoast sitemap yourself", 'greyd_hub');
			}
			else {
				echo "<div class='inner_option seo_greyd ".($_val !== $sitemap ? "hidden" : "")."'>
						<label for='seo_show_taxes'>
							<input type='checkbox' id='seo_show_taxes' name='seo_show_taxes' ".(isset($data['site']['seo']['show_taxes']) && $data['site']['seo']['show_taxes'] == 'true' ? "checked='checked'" : "")." />
							<span>"._x("Show categories and keywords", "small", 'greyd_hub')."</span>
							<small class='color_light'></small>
						</label><br>
						<label for='seo_show_authors'>
							<input type='checkbox' id='seo_show_authors' name='seo_show_authors' ".(isset($data['site']['seo']['show_authors']) && $data['site']['seo']['show_authors'] == 'true' ? "checked='checked'" : "")." />
							<span>"._x("Show authors archives", "small", 'greyd_hub')."</span>
							<small class='color_light'></small>
						</label><br>
						<!--<label for='seo_show_archives'>
							<input type='checkbox' id='seo_show_archives' name='seo_show_archives' ".(isset($data['site']['seo']['show_archives']) && $data['site']['seo']['show_archives'] == 'true' ? "checked='checked'" : "")." />
							<span>"._x("Show post type archive", "small", 'greyd_hub')."</span>
							<small class='color_light'></small>
						</label><br>-->
						<label for='seo_add_dates'>
							<input type='checkbox' id='seo_add_dates' name='seo_add_dates' ".(isset($data['site']['seo']['add_dates']) && $data['site']['seo']['add_dates'] == 'true' ? "checked='checked'" : "")." />
							<span>"._x("Show the date of the last edit", "small", 'greyd_hub')."</span>
							<small class='color_light'></small>
						</label><br>
					</div>";
				$title = __("Don't use a sitemap", 'greyd_hub');
			}

			$_val = 'false';
			echo "<label for='seo_".$_val."'>
					<input type='radio' name='seo_sitemap' value='".$_val."' id='seo_".$_val."' ".($_val === $sitemap ? "checked='checked'" : "" )." onchange='greyd.backend.toggleRadioByClass(\"seo\", \"$_val\")'/>
					<span>".$title."</span>
				</label><br>";

			if ($yoast) {
				// infobox with link to original options
				$link = "<a href=".admin_url('admin.php?page=wpseo_titles')." target='_blank'>".__("Go to the Yoast SEO settings", 'greyd_hub')."</a>";
				echo Helper::render_info_box(array(
					"style" => "info",
					"text" => __("We have found that the <b>plugin Yoast SEO</b> is active.", 'greyd_hub')."<br>".
							__("If the optimized sitemap is selected, no categories or keywords are displayed. In addition, Dynamic Post Types are automatically filtered according to the <i>Show In Frontend Search</i> settings.", 'greyd_hub')."<br>".
							__("All other settings are available as usual: ", 'greyd_hub').$link,
				));
			}

		echo "</td></tr>
			</table>";

		$content .= ob_get_contents();
		ob_end_clean();

        return $content;
    }

    /**
     * Save the settings
     * @see filter 'greyd_settings_more_save'
     * 
     * @param array $site       Current site settings.
     * @param array $defaults   Default values.
     * @param array $data       Raw $_POST data.
     */
    public function save_settings( $site, $defaults, $data ) {

		// make new settings
		$site['seo'] = [];
		foreach ((array) $defaults['seo'] as $key => $value) {
			// convert checkboxes to true | false
			if ($key !== 'sitemap' ) $post_value = isset($data['seo_'.$key]) ? 'true' : 'false';
			else $post_value = isset($data['seo_'.$key]) ? esc_attr(stripslashes($data['seo_'.$key])) : $value;
			$site['seo'][$key] = $post_value;
		}

        return $site;
    }


	/**
	 * =================================================================
	 *                          wp sitemap - greyd setup
	 * =================================================================
	 */
	
    public function sitemaps_add_provider($provider, $name) {
        if ($name == 'taxonomies' && Settings::get_setting( array('site', 'seo', 'show_taxes') ) == 'false') return false; 
        if ($name == 'users' && Settings::get_setting( array('site', 'seo', 'show_authors') ) == 'false') return false; 
        return $provider;
    }
    public function sitemaps_post_types($post_types) {
        foreach($post_types as $pt => $post_type) {
            if ($post_type->exclude_from_search == true) {
                unset($post_types[$pt]);
            }
        }
        return $post_types;
    }
    public function sitemaps_posts_entry($post_type) {
        if (Settings::get_setting( array('site', 'seo', 'add_dates') ) == 'true') {
            $id = url_to_postid($post_type['loc']);
            $post = get_post($id);
            $post_type['lastmod'] = date("Y-m-d", strtotime($post->post_modified_gmt));
        }
        return $post_type;
    }

	/**
	 * =================================================================
	 *                          Yoast - greyd setup
	 * =================================================================
	 */
	
	public function add_yoast_script() {
        $screen = get_current_screen();
        // debug($screen);
        if ($screen->base == 'seo_page_wpseo_titles') {
			// only if Posttypes feature exists (todo: refactor)
            $posttypes = class_exists( 'Greyd\Posttypes\Admin' ) ? Greyd\Posttypes\Posttype_Helper::get_dynamic_posttypes() : array();
            wp_add_inline_script( 'yoast-seo-settings', "			
				var dynamic_posttypes = ".json_encode($posttypes).";
				for (var key of Object.keys(dynamic_posttypes)) {
					const postType = dynamic_posttypes[key];
					/**
					 * Continue if we're editing a core posttype.
					 * @since 1.4.1
					 */
					if ( postType.slug == 'post' || postType.slug == 'page' ) continue;
					if ( postType.singular == '' && postType.plural == '' ) continue;
					if ( postType.singular == postType.slug && postType.plural == postType.slug && postType.title == postType.slug ) continue;
	
					if (typeof postType['arguments']['search'] === 'undefined' ||
						postType['arguments']['search'] != 'search') {
						// console.log('remove posttype');
						jQuery('#wpseo-settings-'+postType['slug']).css('display', 'none');
					}
					jQuery('#wpseo-settings-'+postType['slug']+'_category').css('display', 'none');
					jQuery('#wpseo-settings-'+postType['slug']+'_tag').css('display', 'none');
				}
				jQuery( '#wpseo-settings-greyd_popup, '+
						'#wpseo-settings-tp_forms, '+
						'#wpseo-settings-dynamic_template, '+
						'#wpseo-settings-category, '+
						'#wpseo-settings-post_tag, '+
						'#wpseo-settings-template_categories, '+
						'#wpseo-settings-template_type' ).css('display', 'none');
			" );
        }
    }
    public function yoast_check_options() {
        // debug(get_option('wpseo_titles'));
        $options = get_option('wpseo_titles');
        foreach ($options as $key => $option) {
            if ($key == 'noindex-author-wpseo' ||
                $key == 'noindex-author-noposts-wpseo' ||
                $key == 'noindex-archive-wpseo') $options[$key] = $option;

            else if (strpos($key, "noindex-ptarchive-") === 0) $options[$key] = $option;
            else if (strpos($key, "noindex-tax-") === 0) $options[$key] = true;
            else if (strpos($key, "noindex-") === 0) {
                $post_type = get_post_type_object(str_replace("noindex-", "", $key));
                if (is_object($post_type) && $post_type->exclude_from_search)
                    $options[$key] = true;
            }

            else if (strpos($key, "display-metabox-tax-") === 0) $options[$key] = false;
            else if (strpos($key, "display-metabox-pt-") === 0) {
                $post_type = get_post_type_object(str_replace("display-metabox-pt-", "", $key));
                if (is_object($post_type) && $post_type->exclude_from_search)
                    $options[$key] = false;
            }
        }
        // debug($options);
        update_option('wpseo_titles', $options);
    }
    public function yoast_exclude_post_type($excluded, $post_type) {
        return get_post_type_object($post_type)->exclude_from_search;
    }
    public function yoast_exclude_taxonomy($excluded, $taxonomy) {
        return true;
    }
    public function yoast_exclude_authors($users) {
        return array();
    }


	/**
	 * =================================================================
	 *                          Backend
	 * =================================================================
	 */
	
	/**
	 * Add posttype 'dynamic_template' to array of accessible post types.
	 *
	 * @param array $post_types The public post types.
	 * @return array
	 */
	public function add_dynamic_templates_to_accessible_post_types( $post_types ) {

		if ( $this->is_archive_template_edit_screen() ) {
			$post_types['dynamic_template'] = 'dynamic_template';
		}
		
		return $post_types;
	}

	/**
	 * Enable metabox for dynamic archive templates.
	 * 
	 * @return bool
	 */
	public function enable_metabox_for_dynamic_templates( $option ) {

		return $this->is_archive_template_edit_screen();
	}

	/**
	 * Whether the user currently edits a dynamic archive template.
	 * 
	 * @return bool
	 */
	public function is_archive_template_edit_screen() {

		// get cache
		if ( $this->is_archive_template_edit_screen_cache !== null ) {
			return (bool) $this->is_archive_template_edit_screen_cache;
		}

		// get current post
		$post_id = isset($_GET['post']) ? intval( $_GET['post'] ) : null;
		if ( !empty($post_id) ) {

			if ( has_term( 'archives', 'template_type', $post_id ) ) {
				$this->is_archive_template_edit_screen_cache = true;
			}
			// only set cache if taxonomy is loaded
			else if ( taxonomy_exists( 'template_type' ) ) {
				$this->is_archive_template_edit_screen_cache = false;
			}

			return (bool) $this->is_archive_template_edit_screen_cache;
		}

		return false;
	}

	/**
	 * Cache for the function is_archive_template_edit_screen()
	 */
	public $is_archive_template_edit_screen_cache = null;


	/**
	 * =================================================================
	 *                          Frontend
	 * =================================================================
	 */

	/**
	 * Filter the yoast meta title.
	 * 
	 * @return string
	 */
	public function filter_title( $title ) {

		$template_id = $this->get_dynamic_archive_template_id();

		if ( $template_id ) {

			$metatitle = get_post_meta( $template_id, '_yoast_wpseo_title', true );

			if ( !empty($metatitle) && \function_exists('wpseo_replace_vars') ) {
				return \wpseo_replace_vars( $metatitle, get_post($template_id) );
			}
		}

		return $title;
	}

	/**
	 * Filter the yoast meta description.
	 * 
	 * @return string
	 */
	public function filter_description( $desc ) {

		$template_id = $this->get_dynamic_archive_template_id();

		if ( $template_id ) {
			
			$metadesc = get_post_meta( $template_id, '_yoast_wpseo_metadesc', true );

			if ( !empty($metadesc) && \function_exists('wpseo_replace_vars') ) {
				return \wpseo_replace_vars( $metadesc, get_post($template_id) );
			}
		}

		return $desc;
	}

	/**
	 * Get the post id of the current dynamic archive template
	 * 
	 * @return int Post ID if this is a dynamic archive, 0 if not.
	 */
	public function get_dynamic_archive_template_id() {

		// get cache
		if ( $this->dynamic_archive_template_id !== null ) {
			return intval( $this->dynamic_archive_template_id );
		}

		// set cache
		$this->dynamic_archive_template_id = 0;

		$template_name = \Greyd\Dynamic\Render::get_dynamic_name();

		if ( !empty( $template_name ) && strpos( $template_name, 'archives' ) === 0 ) {
			$template = \Greyd\Dynamic\Dynamic_Helper::get_template_post( $template_name );

			if (
				$template
				&& is_object($template)
				&& isset($template->ID)
			) {
				$this->dynamic_archive_template_id = intval( $template->ID );
			}
		}
			
		return $this->dynamic_archive_template_id;
	}

	/**
	 * Cache for the function get_dynamic_archive_template_id()
	 */
	public $dynamic_archive_template_id = null;
}