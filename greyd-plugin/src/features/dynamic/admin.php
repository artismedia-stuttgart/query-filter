<?php
/**
 * Main Script to manage Dynamic Features.
 */
namespace Greyd\Dynamic;

use Greyd\Helper as Helper;

use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Admin( $config );
class Admin {

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
		$this->config->assets_url = plugin_dir_url( __FILE__ ).'assets';
		$this->config->assets_path = trailingslashit( __DIR__ ).'assets';
		if ( isset( $this->config->is_standalone ) && $this->config->is_standalone == true ) {
			// standalone mode
			self::$is_standalone = true;
		}

		// admin bar items
		add_filter( 'greyd_admin_bar_group', array( $this, 'add_greyd_admin_bar_group_item' ) );

		if ( is_admin() ) {

			// greyd backend
			add_filter( 'greyd_dashboard_active_panels', array( $this, 'add_greyd_dashboard_panel' ) );
			add_filter( 'greyd_dashboard_panels', array( $this, 'add_greyd_classic_dashboard_panel' ) ); // for classic
			add_filter( 'greyd_misc_pages', array( $this, 'add_other_links_to_menu' ) );

			add_filter( 'pre_get_posts', array($this, 'set_default_sorting') );

			// // blockeditor
			// add_action( 'enqueue_block_editor_assets', array($this, 'block_editor_scripts'), 99 );
			// add_action( 'init', array($this, 'register_blocks'), 99 );

			add_filter( 'manage_edit-dynamic_template_columns', array( $this, 'add_column_heading' ) );
			add_action( 'manage_dynamic_template_posts_custom_column', array( $this, 'add_column_value' ), 10, 2 );

			add_action( 'restrict_manage_posts', array( $this, 'add_category_dropdown' ), 20, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ), 90 );
			add_filter( 'block_editor_settings_all', array($this, 'add_editor_styles'), 99, 2 );
			// add to JS @var greyd.data
			add_filter( 'greyd_blocks_editor_data', array( $this, 'add_editor_data' ) );
	
			/**
			 * 'enqueue_block_assets' action should be used here. 
			 * @see features/blocks/enqueue.php
			 */
			$action = \Greyd\Helper::is_active_plugin('gutenberg/gutenberg.php') ? 'enqueue_block_assets' : 'current_screen';
			add_action( $action, array($this, 'register_blocks_assets') );

			// heartbeat hook to update templates in editor
			add_filter( 'heartbeat_settings', function( $settings ) {
				// change heartbeat interval to 15 seconds (default 60)
				$settings['interval'] = 15;
				return $settings;
			} );
			add_filter( 'heartbeat_received', function( $response, $data ) {
				if ( !empty( $data['get_templates'] ) ) {
					// get current/new templates
					$response['templates'] = $this->add_editor_data( array() );
				}
				return $response;
			}, 10, 2 );

		}

	}

	/**
	 * Get admin page.
	 */
	public static function get_page() {
		return array(
			'slug'  => Dynamic_Helper::get_slug(),
			'title' => __( 'Dynamic Templates', 'greyd_hub' ),
			'descr' => __( 'Create dynamic templates that you can fill with different content at different places on your website.', 'greyd_hub' ),
			'url'   => admin_url( 'edit.php?post_type=dynamic_template' ),
			'cap'   => get_post_type_object( 'dynamic_template' ) ? get_post_type_object( 'dynamic_template' )->cap->edit_posts : 'edit_pages',
		);
	}

	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add item to Greyd.Suite adminbar group.
	 *
	 * @see filter 'greyd_admin_bar_group'
	 */
	public function add_greyd_admin_bar_group_item( $items ) {

		// in frontend
		if ( is_admin() || ! current_user_can( self::get_page()['cap'] ) ) {
			return $items;
		}

		$args = array(
			'parent'   => 'greyd_toolbar',
			'id'       => 'templates',
			'title'    => __( 'Templates', 'greyd_hub' ),
			'href'     => self::get_page()['url'],
			'priority' => 20,
		);
		array_push( $items, $args );

		// submenu
		$parent_slug = 'templates';
		$parent_href = 'edit.php?post_type=dynamic_template';

		$args = array(
			'id'     => $parent_slug . '-show',
			'title'  => __( "All templates", 'greyd_hub' ),
			'parent' => $parent_slug,
			'href'   => admin_url( $parent_href ),
		);
		array_push( $items, $args );

		$args = array(
			'id'     => $parent_slug . '-add',
			'title'  => __( "Create", 'greyd_hub' ),
			'parent' => $parent_slug,
			'href'   => (
				Helper::is_greyd_classic()
				? admin_url( $parent_href . '&wizard=add' )
				: admin_url('post-new.php?post_type=dynamic_template')
			),
		);
		array_push( $items, $args );


		/**
		 * Add group for all template types
		 */
		$template_types = Dynamic_Helper::get_template_types();

		if ( count($template_types) > 1 ) {
			$args = array(
				'id'     => $parent_slug . '-types',
				'title'  => __( "Types", 'greyd_hub' ),
				'parent' => $parent_slug,
				'group'  => true,
				'href'   => admin_url( $parent_href ),
			);
			array_push( $items, $args );
	
			$parent_slug .= '-types';
			$parent_href .= '&template_type=';

			foreach( $template_types as $type ) {
	
				$args = array(
					'id'     => $parent_slug . '-' . $type['slug'],
					'title'  => $type['title'],
					'parent' => $parent_slug,
					'href'   => admin_url( $parent_href . $type['slug'] ),
				);
				array_push( $items, $args );
			}
		}

		return $items;
	}

	/**
	 * Add dashboard panel
	 *
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_dashboard_panel( $panels ) {

		$panels[ 'dynamic-templates' ] = true;
			
		return $panels;
	}

	/**
	 * Add dashboard panel
	 *
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_classic_dashboard_panel( $panels ) {
		
		$panels[ self::get_page()['slug'] ] = array(
			'icon'     => 'dynamic',
			'title'    => self::get_page()['title'],
			'descr'    => self::get_page()['descr'],
			'btn'      => array(
				array(
					'text' => __( 'View templates', 'greyd_hub' ),
					'url'  => self::get_page()['url'],
				),
				array(
					'text'  => __( 'Create', 'greyd_hub' ),
					'url'   => self::get_page()['url'] . '&wizard=add',
					'class' => 'button button-ghost',
				),
			),
			'cap'      => self::get_page()['cap'],
			'priority' => 80,
		);

		return $panels;
	}

	/**
	 * Add dashboard panel
	 *
	 * @see filter 'greyd_misc_pages'
	 */
	public function add_other_links_to_menu( $pages ) {
		// debug($pages);

		// add 'wp_template' to dynamic template menu
		if ( Helper::is_fse() && post_type_exists( 'wp_template' ) ) {
			$pages['wp_template'] = array(
				'parent'     => 'edit.php?post_type=dynamic_template',
				'title'      => __( 'WP Templates', 'greyd_hub' ),
				'menu_title' => __( 'WP Templates', 'greyd_hub' ),
				'cap'        => 'edit_theme_options',
				'slug'       => 'site-editor.php?path=%2Fwp_template%2Fall',
				'callback'   => '',
				'position'   => 10,
			);
		}

		// add 'wp_template_part' to dynamic template menu
		if ( Helper::is_fse() && post_type_exists( 'wp_template_part' ) ) {
			$pages['wp_template_part'] = array(
				'parent'     => 'edit.php?post_type=dynamic_template',
				'title'      => __( 'WP Template Parts', 'greyd_hub' ),
				'menu_title' => __( 'WP Template Parts', 'greyd_hub' ),
				'cap'        => 'edit_theme_options',
				'slug'       => 'site-editor.php?path=%2Fwp_template_part%2Fall',
				'callback'   => '',
				'position'   => 11,
			);
		}

		// add 'reusables' to dynamic template menu
		if ( post_type_exists( 'wp_block' ) ) {
			$pages['wp_block'] = array(
				'parent'     => 'edit.php?post_type=dynamic_template',
				'title'      => __( 'Synced Patterns', 'greyd_hub' ),
				'menu_title' => __( 'Patterns', 'greyd_hub' ),
				'cap'        => 'edit_dynamic_templates',
				'slug'       => 'edit.php?post_type=wp_block',
				'callback'   => '',
				'position'   => 12,
			);
		}

		return $pages;
	}

	/*
	====================================================================================
		Dynamic Template Posttype Edit
	====================================================================================
	*/

	/**
	 * Set default backend post sorting by date
	 */
	public function set_default_sorting( $query ) {
		global $pagenow;
		if (
			is_admin()
			&& 'edit.php' == $pagenow
			&& !isset($_GET['orderby'])
			&& isset($_GET['post_type'])
			&& $_GET['post_type'] === 'dynamic_template'
			&& $query->is_main_query()
		) {
			$query->set( 'orderby', array( 'date' => 'DESC' ) );
		}
	}

	/**
	 * Add edit column headings.
	 */
	public function add_column_heading( $columns ) {

		$slug_column = array(
			'dynamic_template_category' => __( "Categories", 'greyd_hub' ),
			'dynamic_template_usage'    => __( "Usage", 'greyd_hub' ),
		);

		$columns = array_slice( $columns, 0, 2, true ) + $slug_column + array_slice( $columns, 2, count( $columns ) - 1, true );

		return $columns;
	}

	/**
	 * Display the column value.
	 */
	public function add_column_value( $column_name, $post_id ) {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		switch ( $column_name ) {

			case 'dynamic_template_category':
				$cats = get_the_terms( $post_id, 'template_categories' );
				if ( $cats && !is_wp_error( $cats ) ) {
					$first = '';
					foreach ( $cats as $cat ) {
						echo $first . $cat->name;
						$first = ', ';
					}
				} else {
					echo '--';
				}
				break;

			case 'dynamic_template_usage':
				$ttype = get_the_terms( $post_id, 'template_type' );
				if ( ! $ttype || is_wp_error( $ttype ) ) {
					break;
				}

				// fallback
				$render = '--';

				// dynamic templates
				if ( $ttype[0]->slug === 'dynamic' ) {
					$slug         = get_post_field( 'post_name', $post_id, 'raw' );
					$template_log = self::get_template_log( $post_id, $slug );
					$total        = self::calc_log_total( $template_log );
					$render       = $total . 'x ' . Helper::render_info_popup( self::get_template_log_html( $template_log ) );

				}
				// system templates
				else if ( Helper::is_greyd_classic() ) {
					$render = __( 'System', 'greyd_hub' );
				}
				echo $render;
				break;
		}
	}

	/**
	 * add category filter
	 */
	public function add_category_dropdown( $post_type ) {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		if ( $post_type != Dynamic_Helper::get_slug() ) {
			return;
		}

		$tax  = 'template_categories';
		$args = array(
			'taxonomy' => $tax,
			'orderby'  => 'name',
			'order'    => 'ASC',
		);
		$cats = get_categories( $args );
		// debug($cats);
		$current_cat = isset( $_GET[ $tax ] ) ? esc_attr( $_GET[ $tax ] ) : '';

		// render
		echo "<select name='$tax'>";
		echo "<option value=''>" . _x( "All categories", 'small', 'greyd_hub' ) . '</option>';
		if ( is_array( $cats ) ) {
			foreach ( $cats as $cat ) {
				echo sprintf(
					'<option value="%1$s" %2$s >%3$s (%4$s)</option>',
					$cat->slug,
					( $current_cat === $cat->slug ? 'selected' : '' ),
					$cat->name,
					$cat->count
				);
			}
		}
		echo '</select>';
	}

	/**
	 * Enqueue backend styles
	 * @action admin_enqueue_scripts
	 */
	public function load_admin_scripts() {
		
		wp_register_style(
			'dynamic_template_admin_css',
			$this->config->assets_url.'/css/dynamic_admin.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style( 'dynamic_template_admin_css' );
		
		$screen = get_current_screen();
		if (
			$screen->base === 'post' &&
			$screen->action !== 'add' &&
			$screen->post_type === "dynamic_template" &&
			Helper::is_greyd_classic()
		) {
			
			$post_id	= $_GET['post'];
			$post		= get_post( $post_id );
			$slug		= $post ? $post->post_name : null;

			// enqueue template-specific stylesheets
			if ( strpos($slug, 'footer') === 0 ) {
				wp_process_style('default_admin_css', $this->config->assets_path.'/css/classic/editor-footer.css' );
			}
			else if ( strpos($slug, 'main-menu-offcanvas') === 0 ) {
				wp_process_style('default_admin_css', $this->config->assets_path.'/css/classic/editor-mainmenuoff.css' );
				$type = get_theme_mod( 'navi_header_off_type', 'offcanvas' );
				if ( $type === 'full' ) {
					echo '<style>.editor-styles-wrapper > .is-root-container.wp-block-post-content { --HEDOFFwidth: 100% !important; }</style>';
				}
			}
			else if ( strpos($slug, 'meta-menu-offcanvas') === 0 ) {
				wp_process_style('default_admin_css', $this->config->assets_path.'/css/classic/editor-metamenuoff.css' );
				$type = get_theme_mod( 'navi_header_sec_off_type', 'offcanvas' );
				if ( $type === 'full' ) {
					echo '<style>.editor-styles-wrapper > .is-root-container.wp-block-post-content { --HED2OFFwidth: 100% !important; }</style>';
				}
			}

		}

	}

	/**
	 * Add styles to the editor preview
	 */
	public function add_editor_styles($editor_settings, $editor_context) {

		if ( !Helper::is_greyd_classic() ) {
			return $editor_settings;
		}
		
		if ( isset($editor_settings["__experimentalBlockPatterns"]) && is_array($editor_settings["__experimentalBlockPatterns"]) ) {
			// add block pattern with dynamic template
			$query_template_content = '<!-- wp:query {"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->';
			$query_template_content .= '<div class="wp-block-query"><!-- wp:post-template --><!-- wp:greyd/dynamic /--><!-- /wp:post-template --></div>';
			$query_template_content .= '<!-- /wp:query -->';
			$query_template = array(
				"title" => "Template",
				"blockTypes" => array( "core/query" ),
				"categories" => array(),
				"content" => $query_template_content,
				"name" => "core/query-template-posts"
			);
			array_unshift($editor_settings["__experimentalBlockPatterns"], $query_template);
			// modify default query pattern
			$query_default = '<!-- wp:query {"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->';
			$query_default .= '<div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- wp:post-date /--><!-- /wp:post-template --></div>';
			$query_default .= '<!-- /wp:query -->';
			$editor_settings["__experimentalBlockPatterns"][1]["content"] = $query_default;
		}

		if ( class_exists('processor') ) {
			$editor_settings['styles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/editor-dynamic-blocks.css'));
			$editor_settings['defaultEditorStyles'][] = array('css' => \processor::process_styles($this->config->assets_path.'/css/editor-dynamic-blocks.css'));
		}

		return $editor_settings;
	}

	/**
	 * Enqueue preview styles
	 * @action enqueue_block_assets
	 */
	public function register_blocks_assets() {

		if ( ! is_admin() ) return;

		/**
		 * load styles only on editor pages.
		 * can be removed when proper action 'enqueue_block_assets' can be used.
		 */
		$screen = get_current_screen();
		if (
			$screen->base != 'site-editor' &&
			method_exists($screen, 'is_block_editor') &&
			!$screen->is_block_editor()
		) {
			// debug("not editor");
			return;
		}

		add_editor_style( $this->config->assets_url.'/css/editor-blocks.css' );

		if ( ! Helper::is_greyd_classic() ) {
			add_editor_style( $this->config->assets_url.'/css/editor-dynamic-blocks.css' );
		}

		wp_register_style(
			'greyd-dynamic-blocks-style',
			$this->config->assets_url.'/css/editor-dynamic.css',
			array( ),
			GREYD_VERSION
		);
		wp_enqueue_style( 'greyd-dynamic-blocks-style' );

	}

	/**
	 * Filter Blockeditor Data values.
	 *
	 * @filter 'greyd_blocks_editor_data'
	 *
	 * @param object $data     Original Data Array
	 * @return object $data    Data Array with filtered Values
	 */
	public function add_editor_data( $data ) {

		// get template_type
		$template_type = "";
		if (
			!empty($data["post_type"]) && 
			$data["post_type"] == 'dynamic_template' && 
			$data["post_id"] &&
			class_exists('\Greyd\Helper')
		) {
			$template_type = \Greyd\Helper::get_post_taxonomy_terms( $data["post_id"], 'template_type' )[0]->slug;
			// debug($template_type);
		}

		// get all templates
		$all_templates = array();
		$templates = \Greyd\Helper::get_all_posts('dynamic_template');
		if ($templates) {
			// debug($templates);
			foreach ($templates as $template) {

				// only dynamic templates
				if ( 'dynamic' !== \Greyd\Helper::get_post_taxonomy_terms( $template->id, 'template_type' )[0]->slug ) continue;

				$title = $template->title;
				$categories = \Greyd\Helper::get_post_taxonomy_terms( $template->id, 'template_categories' );
				if ($categories && is_array($categories)) {
					// add categories to title
					$cat_names = array();
					foreach ($categories as $cat) $cat_names[] = $cat->name; 
					$title .= " (".implode(", ", $cat_names).")";
				}
				$all_templates[] = (object) array(
					'id' => $template->id, 
					'slug' => $template->slug, 
					'title' => $title, 
					'type' => 'dynamic_template', 
					'blocks' => has_blocks(get_post_field('post_content', $template->id)),
					'lang' => $template->lang,
					'edit_link' => Helper::get_edit_post_link( $template->id ),
					'modified' => get_post_field('post_modified', $template->id)
				);
			}
		}
		foreach (array( 'wp_block', 'wp_template', 'wp_template_part' ) as $type) {
			$templates = \Greyd\Helper::get_all_posts($type);
			if ($templates) {
				$parts = array();
				foreach ($templates as $template) {
					$parts[] = (object)array( 
						'id' => $template->id, 
						'slug' => $template->slug, 
						'title' => $template->title, 
						'type' => $type, 
						'blocks' => true,
						'lang' => $template->lang,
						'edit_link' => Helper::get_edit_post_link( $template->id ),
						'modified' => get_post_field('post_modified', $template->id)
					);
				}
				$all_templates = array_merge($all_templates, $parts);
			}
		}

		$data['template_type'] = $template_type;
		$data['all_templates'] = $all_templates;

		return $data;

	}

	/*
	====================================================================================
		Template Log
	====================================================================================
	*/

	public static function get_all_posts_with_templates() {
		global $wpdb;
		return $wpdb->get_results(
			"
            SELECT ID, post_type, post_title, post_content 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_content LIKE '% template=\"%' OR post_content LIKE '% post_template=\"%' OR post_content LIKE '%\"template\":\"%')
        ",
			OBJECT
		);
	}

	public static function get_all_posts_using_template( $template_id = '', $slug = '' ) {
		if ( empty( $template_id ) ) {
			return false;
		}

		global $wpdb;
		if ( empty( $slug ) ) {
			$slug = get_post_field( 'post_name', $template_id, 'raw' );
		}

		$args = "
            SELECT ID, post_type, post_title, post_content 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (
                post_content LIKE '% template=\"{$template_id}\"%' OR post_content LIKE '% post_template=\"{$template_id}\"%' OR post_content LIKE '%\"template\":\"{$template_id}\"%'" .
				( ! empty( $slug ) ? "OR post_content LIKE '% template=\"{$slug}\"%' OR post_content LIKE '% post_template=\"{$slug}\"%' OR post_content LIKE '%\"template\":\"{$slug}\"%'" : '' ) .
			')
         ';

		$result = $wpdb->get_results( $args, OBJECT );
		// debug($result);

		$metas = $wpdb->get_results(
			$wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta where meta_key = %s AND meta_value LIKE '{$template_id}|%'", 'menu-item-template' )
		);
		foreach ( $metas as $meta ) {
			array_push(
				$result,
				(object) array(
					'ID'           => $meta->post_id,
					'post_type'    => 'nav_menu_item',
					'post_title'   => get_post_field( 'post_title', $meta->post_id, 'raw' ),
					'post_content' => $meta->meta_value,
				)
			);
		}
		// debug($result);

		return $result;
	}

	public static function calc_log_total( $input ) {
		$count = 0;
		if ( is_array( $input ) ) {
			foreach ( $input as $val ) {
				$count += self::calc_log_total( $val );
			}
		}
		if ( is_int( $input ) ) {
			$count += $input;
		}
		return $count;
	}

	public static function get_template_log( $template_id = '', $slug = '' ) {
		if ( empty( $template_id ) ) {
			return false;
		}

		$posts = self::get_all_posts_using_template( $template_id, $slug );

		$template_log = array();
		foreach ( $posts as $post ) {
			if ( ! isset( $post->post_content ) ) {
				continue;
			}

			$id      = intval( $post->ID );
			$content = $post->post_content;
			$log     = array(
				'type'  => $post->post_type,
				'title' => $post->post_title,
				'as'    => array(),
			);

			// as template
			if ( strpos( $content, ' template="' . $template_id . '"' ) !== false || strpos( $content, ' template="' . $slug . '"' ) !== false ) {
				$tmp   = explode( ' template="' . $template_id . '"', $content );
				$tmp2  = explode( ' template="' . $slug . '"', $content );
				$count = count( $tmp ) - 1 + count( $tmp2 ) - 1;
				if ( $count > 0 ) {
					$log['as']['template'] = intval( $count );
				}
			}
			// as blocks template
			if ( strpos( $content, '"template":"' . $template_id . '"' ) !== false || strpos( $content, '"template":"' . $slug . '"' ) !== false ) {
				$tmp   = explode( '"template":"' . $template_id . '"', $content );
				$tmp2  = explode( '"template":"' . $slug . '"', $content );
				$count = count( $tmp ) - 1 + count( $tmp2 ) - 1;
				if ( $count > 0 ) {
					$log['as']['block_template'] = intval( $count );
				}
			}
			// as post overview
			if ( strpos( $content, ' post_template="' . $template_id . '"' ) !== false || strpos( $content, ' post_template="' . $slug . '"' ) !== false ) {
				$tmp   = explode( ' post_template="' . $template_id . '"', $content );
				$tmp2  = explode( ' post_template="' . $slug . '"', $content );
				$count = count( $tmp ) - 1 + count( $tmp2 ) - 1;
				if ( $count > 0 ) {
					$log['as']['post_overview'] = intval( $count );
				}
			}
			// as menu template
			if ( strpos( $content, $template_id . '|' ) === 0 ) {
				$log['as']['menu_template'] = 1;
			}

			$template_log[ $id ] = $log;
		}

		// form popup
		if ( preg_match( '/form_popup_[\d]+/', $slug ) ) {
			$form_id  = intval( preg_replace( '/form_popup_/', '', $slug ) );
			$posttype = 'tp_forms';
			if ( get_post_type( $form_id ) === $posttype ) {
				// posts already in log
				if ( isset( $template_log[ $form_id ] ) ) {
					$template_log[ $form_id ]['as']['form_popup'] = 1;
				}
				// posts doesn't exist in log
				else {
					$template_log[ $form_id ] = array(
						'type'  => $posttype,
						'title' => get_the_title( $form_id ),
						'as'    => array(
							'form_popup' => 1,
						),
					);
				}
			}
		}
		return $template_log;
	}

	public static function get_template_log_html( $template_log = array() ) {
		if ( ! is_array( $template_log ) || count( $template_log ) == 0 ) {
			return '';
		}

		$total = 0;
		$total = self::calc_log_total( $template_log );

		ob_start();
		// not used
		if ( $total <= 0 || count( $template_log ) <= 0 ) {
			echo "<div class='log_title'>" . __( "Template is not used", 'greyd_hub' ) . '</div>';
		}
		// used
		else {
			// title
			echo "<div class='log_title " . ( $total > 0 ? 'toggle' : '' ) . "'>" .
				sprintf( __( "Template is used %s", 'greyd_hub' ), '<strong>' . sprintf( __( "%s times", 'greyd_hub' ), $total ) . '</strong>' ) .
				'</div>';

			// items
			echo "<div class='log_items'>";
			foreach ( $template_log as $id => $log ) {
				// debug($log);
				if ( $log['type'] == 'nav_menu_item' ) {
					$title     = get_post_type_object( $log['type'] )->labels->singular_name;
					$detail    = '';
					$edit_link = admin_url( 'nav-menus.php' );
				} else {
					$title     = $log['title'];
					$detail    = ' (' . get_post_type_object( $log['type'] )->labels->singular_name . ')';
					$edit_link = admin_url( 'post.php?action=edit&post=' . $id );
				}
				echo "<div class='log_item'>";
				echo "<a href='$edit_link' target='_blank' title='" . __( "Open in new tab", 'greyd_hub' ) . "'>" . $title . '</a>' . $detail;
				$modules = array(
					'template'       => __( "%s as a normal template", 'greyd_hub' ),
					'block_template' => __( "%s as blocks template", 'greyd_hub' ),
					'menu_template'  => __( "%s as dropdown menu", 'greyd_hub' ),
					'post_overview'  => __( "%s as post overview", 'greyd_hub' ),
					'form_popup'     => __( "As form pop-up", 'greyd_hub' ),
				);
				foreach ( $log['as'] as $module => $count ) {
					echo '<br>&nbsp;- ' . sprintf( $modules[ $module ], sprintf( '<strong>%sx</strong>', $count ) );
				}
				echo '</div>';
			}
			echo '</div>';
		}
		$render = ob_get_contents();
		ob_end_clean();
		return $render;
	}
}
