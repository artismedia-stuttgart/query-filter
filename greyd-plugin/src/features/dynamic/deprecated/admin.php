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

new Admin_Deprecated( $config );
class Admin_Deprecated {

	/**
	 * Holds the plugin config.
	 *
	 * @var object
	 */
	private $config;


	/**
	 * Class constructor.
	 */
	public function __construct( $config ) {

		if ( ! Helper::is_greyd_classic() ) return;

		// set config
		$this->config = (object) $config;

		add_filter( 'greyd_template_types', array( $this, 'filter_template_types' ) );
		add_filter( 'greyd_admin_bar_items', array( $this, 'add_greyd_admin_bar_edit_link' ) );

		add_filter( 'manage_edit-dynamic_template_columns', array( $this, 'add_column_heading' ) );
		add_action( 'manage_dynamic_template_posts_custom_column', array( $this, 'add_column_value' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ), 90 );
		add_action( 'admin_notices', array( $this, 'add_backend_header' ), 99 );

		// add wizard
		add_action( 'admin_enqueue_scripts', array( $this, 'add_wizard_script' ), 99 );
		add_action( 'admin_footer', array( $this, 'add_wizard' ) );
	}

	/**
	 * Filter template types.
	 */
	public function filter_template_types( $types ) {
		$types = array_merge( $types, array(
			array(
				'title'       => __( 'Navigation', 'core' ),
				'slug'        => 'navigation',
				'description' => '',
				'color'       => 'blue',
			),
			array(
				'title'       => __( "Post", 'core' ),
				'slug'        => 'single',
				'description' => '',
				'color'       => 'blue',
			),
			array(
				'title'       => __( "Archive", 'core' ),
				'slug'        => 'archives',
				'description' => '',
				'color'       => 'blue',
			),
			array(
				'title'       => __( 'Suche', 'core' ),
				'slug'        => 'search',
				'description' => '',
				'color'       => 'blue',
			),
			array(
				'title'       => __( "Other", 'core' ),
				'slug'        => 'more',
				'description' => '',
				'color'       => 'blue',
			),
			array(
				'title'       => __( 'WooCommerce', 'core' ),
				'slug'        => 'woo',
				'description' => '',
				'color'       => 'purple',
			)
		) );
		return $types;
	}

	/**
	 * Add item to adminbar.
	 *
	 * @see filter 'greyd_admin_bar_items'
	 */
	public function add_greyd_admin_bar_edit_link( $items ) {
		// debug($items);

		// in frontend
		if ( is_admin() || ! current_user_can( Admin::get_page()['cap'] ) ) {
			return $items;
		}

		// add edit template item to normal admin bar
		if ( is_archive() || is_category() || is_single() || is_search() || is_404() ) {

			$post_type_name = __( 'Template', 'greyd_hub' );
			$template_slug  = \Greyd\Dynamic\Render::get_dynamic_name();
			$template       = \Greyd\Dynamic\Dynamic_Helper::get_template_post( $template_slug );

			/**
			 * Quicklinks to woocommerce templates
			 *
			 * @see woocommerce/includes/wc-conditional-functions.php
			 */
			if ( function_exists( 'is_woocommerce' ) ) {
				if ( is_shop() ) {
					$template       = get_post( wc_get_page_id( 'shop' ) );
					$post_type_name = __( "Page", 'greyd_hub' );
				} elseif ( is_product() ) {
					$template = \Greyd\Dynamic\Dynamic_Helper::get_template_post( 'woo-product' );
				} elseif ( is_product_category() ) {
					$template = \Greyd\Dynamic\Dynamic_Helper::get_template_post( 'woo-product-category' );
				} elseif ( is_product_tag() ) {
					$template = \Greyd\Dynamic\Dynamic_Helper::get_template_post( 'woo-product-tag' );
				}
			}

			if ( !$template || !is_object( $template ) ) {
				$args = array(
					'id'    => 'new-template',
					'title' => __( "Create new template for this page", 'greyd_hub' ),
					'href'  => Admin::get_page()['url'] . '&wizard=add',
				);
			} else {
				$args = array(
					'id'    => 'edit-template',
					'title' => sprintf( __( "edit %1\$s \"%2\$s\"", 'greyd_hub' ), $post_type_name, $template->post_title ),
					'href'  => admin_url( 'post.php?post=' . $template->ID . '&action=edit' ),
				);
			}

			// add item
			array_push( $items, $args );

		}

		return $items;
	}

	/**
	 * Add edit column headings.
	 */
	public function add_column_heading( $columns ) {

		$slug_column = array(
			'dynamic_template_slug'     => __( 'Name', 'greyd_hub' ),
			'dynamic_template_type'     => __( "Type", 'greyd_hub' )
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
			case 'dynamic_template_slug':
				$slug = get_post_field( 'post_name', $post_id, 'raw' );
				// $msg = "edit: ".$slug;
				$span  = '<span>' . $slug . '</span>';
				$edit  = "<a href='#' style='display:none' class='dashicons dashicons-edit' onclick='greyd.templates.editTitle(" . $post_id . ")'></a>";
				$input = "<input style='width:100%' value=" . $slug . '>';
				$reset = "<a href='#' class='dashicons dashicons-no' style='color:darkred' onclick='greyd.templates.resetTitle(" . $post_id . ', "' . $slug . "\")'></a>";
				$set   = "<a href='#' class='dashicons dashicons-saved' onclick='greyd.templates.setTitle(" . $post_id . ', "' . $slug . "\")'></a>";
				echo "<div id='title_" . $post_id . "' onmouseenter='greyd.templates.enterTitle(" . $post_id . ")' onmouseleave='greyd.templates.leaveTitle(" . $post_id . ")'>" . $span . ' ' . $edit . "</div>
                    <div id='title_edit_" . $post_id . "' style='display:none;text-align:right'>" . $input . ' ' . $reset . ' ' . $set . '</div>';
				break;

			case 'dynamic_template_type':
				$ttype = get_the_terms( $post_id, 'template_type' );
				if ( $ttype && !is_wp_error( $ttype ) && is_countable( $ttype) && count( $ttype ) > 0 ) {
					$color = '';
					foreach ( Dynamic_Helper::get_template_types() as $type ) {
						if ( $ttype[0]->slug == $type['slug'] ) {
							$color = $type['color'];
							break;
						}
					}
					$span = '<span class="greyd_info_box ' . $color . '">' . __( $ttype[0]->name, 'greyd_hub' ) . '</span>';
				} else {
					$span = '--';
				}
				$edit  = "<a href='#' style='display:none' class='dashicons dashicons-edit' onclick='greyd.templates.editType(" . $post_id . ")'></a>";
				$input = "<select style='width:100%' value=" . $ttype[0]->slug . '>';
				foreach ( Dynamic_Helper::get_template_types() as $type ) {
					if ( $type['slug'] == 'woo' && ! class_exists( 'woocommerce' ) ) {
						continue;
					}
					$sel    = $type['slug'] == $ttype[0]->slug ? "selected='selected'" : '';
					$input .= "<option value='" . $type['slug'] . "' " . $sel . '>' . $type['title'] . '</option>';
				}
				$input .= '</select>';
				$reset  = "<a href='#' class='dashicons dashicons-no' style='color:darkred' onclick='greyd.templates.resetType(" . $post_id . ', "' . $ttype[0]->slug . "\")'></a>";
				$set    = "<a href='#' class='dashicons dashicons-saved' onclick='greyd.templates.setType(" . $post_id . ', "' . $ttype[0]->slug . "\")'></a>";
				echo "<div id='type_" . $post_id . "' onmouseenter='greyd.templates.enterType(" . $post_id . ")' onmouseleave='greyd.templates.leaveType(" . $post_id . ")'>" . $span . ' ' . $edit . "</div>
                    <div id='type_edit_" . $post_id . "' style='display:none;text-align:right'>" . $input . ' ' . $reset . ' ' . $set . '</div>';
				break;
		}
	}

	// enqueue scripts
	public function load_admin_scripts() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		$screen = get_current_screen();
		// debug($screen);

		if ( $screen->base === 'post' || $screen->base === 'edit' ) {
			$css_uri = plugin_dir_url( __FILE__ ) . 'assets/css';
			$js_uri  = plugin_dir_url( __FILE__ ) . 'assets/js';

			/**
			 * standalone mode (without greyd_hub)
			 * 'greyd-admin-style' and 'greyd-admin-script'
			 * are the basic admin scripts, usually provided by the greyd_hub.
			 * If this feature runs without greyd_hub, these scripts are not registered,
			 * so they are added here as fallback.
			 */
			global $wp_styles;
			global $wp_scripts;

			if ( ! isset( $wp_styles->registered['greyd-admin-style'] ) ) {
				// debug("add backend styles");
				wp_register_style( 'greyd-admin-style', $css_uri . '/backend.css', null, GREYD_VERSION, 'all' );
				wp_enqueue_style( 'greyd-admin-style' );
			}

			// admin styles
			wp_register_style( 'dynamic_admin_css', $css_uri . '/dynamic_admin.css', null, GREYD_VERSION, 'all' );
			wp_enqueue_style( 'dynamic_admin_css' );

			// register & enqueue admin scripts
			wp_register_script( 'dynamic_admin_js', $js_uri . '/dynamic_admin.js', array( 'jquery' ), GREYD_VERSION, true );
			wp_enqueue_script( 'dynamic_admin_js' );
			wp_add_inline_script( 'dynamic_admin_js', 'var greyd = greyd || {};', 'before' );

			// standalone mode
			if ( ! isset( $wp_scripts->registered[ 'greyd-admin-script' ] ) ) {
				// inline script before
				// define global greyd var and ajax vars
				wp_add_inline_script(
					'greyd-admin-script',
					'if (typeof greyd === "undefined") var greyd = {}; greyd = {'.
						'upload_url:   "' . wp_upload_dir()['baseurl'] . '",'.
						'ajax_url:     "' . admin_url( 'admin-ajax.php' ) . '",'.
						'nonce:        "' . wp_create_nonce( 'install' ) . '",'.
						'is_multisite: "' . ( is_multisite() ? 'true' : 'false' ) . '",'.
						'...greyd'.
					'};',
					'before'
				);
			}
		}

		// additional scripts and vars
		if ( $screen->base == 'post' ) {
			// debug($screen);
			$all_dtags = array(
				'product' => array(
					array(
						'label' => 'Warenkorb Auszug',
						'name'  => 'cart-excerpt',
						'type'  => 'text',
					),
					array(
						'label' => 'Preis',
						'name'  => 'price',
						'type'  => 'text',
					),
					array(
						'label' => 'Mehrwertsteuer',
						'name'  => 'vat',
						'type'  => 'text',
					),
					array(
						'label' => 'Warenkorb',
						'name'  => 'cart',
						'type'  => 'url',
					),
					array(
						'label' => 'Woo Ajax Button',
						'name'  => 'woo_ajax',
						'type'  => 'url',
					),
				),
			);
			$all_vtags = array(
				'post'    => array( 'content', 'thumbnail', 'excerpt', 'categories', 'tags' ),
				'product' => array( 'content', 'thumbnail', 'excerpt', 'categories', 'tags' ),
				'page'    => array( 'content', 'thumbnail' ),
			);
			$posttypes = Posttype_Helper::get_dynamic_posttypes();
			if ( count( $posttypes ) > 0 ) {
				foreach ( $posttypes as $posttype ) {
					// debug($posttype);
					$fields = isset( $posttype['fields'] ) ? (array) $posttype['fields'] : array();
					if ( count( $fields ) > 0 ) {
						// debug($fields);
						$dtags = array();
						foreach ( $fields as $field ) {
							if ( is_array( $field ) && $field['type'] != 'headline' && $field['type'] != 'descr' && $field['type'] != 'hr' ) {
								$dtags[] = array(
									'label' => $field['label'],
									'name'  => $field['name'],
									'type'  => $field['type'],
								);
							}
						}
						// debug($dtags);
						$all_dtags[ $posttype['slug'] ] = $dtags;
					}
					$supports = isset( $posttype['supports'] ) ? (array) $posttype['supports'] : array();
					if ( isset( $posttype['categories'] ) ) {
						$supports['categories'] = $posttype['categories'];
					}
					if ( isset( $posttype['tags'] ) ) {
						$supports['tags'] = $posttype['tags'];
					}
					if ( count( $supports ) > 0 ) {
						// debug($supports);
						$vtags = array();
						foreach ( $supports as $support ) {
							if ( $support === 'custom_taxonomies' ) {
								foreach ( $posttype['custom_taxonomies'] as $taxonomy ) {
									if ( is_object( $taxonomy ) ) {
										$taxonomy = (array) $taxonomy;
									}
									$vtags[] = 'customtax-' . $taxonomy['slug'];
								}
							} elseif ( $support === 'editor' ) {
								$vtags[] = 'content';
							} else {
								$vtags[] = $support;
							}
						}
						// debug($vtags);
						$all_vtags[ $posttype['slug'] ] = $vtags;
					}
				}
			}
			if ( isset( $_GET['post'] ) ) {
				$post = get_post( $_GET['post'] );
				// debug($post);
				if ( get_the_terms( $post, 'template_type' ) && count( get_the_terms( $post, 'template_type' ) ) > 0 &&
					( get_the_terms( $post, 'template_type' )[0]->slug == 'single' ||
					get_the_terms( $post, 'template_type' )[0]->slug == 'archives' ||
					get_the_terms( $post, 'template_type' )[0]->slug == 'search' ) ) {
					// debug($post->post_name);
					$default_dtags = $default_vtags = array();
					$tmp           = explode( '-', $post->post_name );
					if ( count( $tmp ) > 1 ) {
						$dpt   = '';
						$found = false;
						for ( $i = 1; $i < count( $tmp ); $i++ ) {
							$dpt = ( $dpt == '' ) ? $tmp[ $i ] : $dpt . '-' . $tmp[ $i ];
							if ( isset( $all_dtags[ $dpt ] ) ) {
								$default_dtags = $all_dtags[ $dpt ];
								$found         = true;
							}
							if ( isset( $all_vtags[ $dpt ] ) ) {
								$default_vtags = $all_vtags[ $dpt ];
								$found         = true;
							}
							if ( $found ) {
								break;
							}
						}
						if ( ! $found ) {
							wp_add_inline_script( 'dynamic_admin_js', 'var default_variable_tags = ' . json_encode( $all_vtags['post'] ) . ';' );
						} else {
							if ( count( $default_dtags ) > 0 ) {
								wp_add_inline_script( 'dynamic_admin_js', 'var default_dynamic_tags = ' . json_encode( $default_dtags ) . ';' );
							}
							if ( count( $default_vtags ) > 0 ) {
								wp_add_inline_script( 'dynamic_admin_js', 'var default_variable_tags = ' . json_encode( $default_vtags ) . ';' );
							}
						}
					} else {
						wp_add_inline_script( 'dynamic_admin_js', 'var default_variable_tags = ' . json_encode( $all_vtags['post'] ) . ';' );
					}
				}
			}
			wp_add_inline_script( 'dynamic_admin_js', 'var all_dynamic_tags = ' . json_encode( $all_dtags ) . ', dynamic_tags = [];' );
			wp_add_inline_script( 'dynamic_admin_js', 'var all_variable_tags = ' . json_encode( $all_vtags ) . ', variable_tags = [];' );

			/**
			 * Add default dynamic tags via the filter:
			 *
			 * @filter 'greyd_default_dynamic_tags'
			 */
			$default_dynamic_tags = apply_filters( 'greyd_default_dynamic_tags', array( 'post-type', 'title', 'author', 'date' ) );
			wp_add_inline_script( 'dynamic_admin_js', 'var all_default_dynamic_tags = ' . json_encode( $default_dynamic_tags ) . ';' );
		}

	}

	/**
	 * add Custom Taxonomy Tabs
	 */
	public function add_backend_header() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		$screen = get_current_screen();
		// debug($screen);

		// only in template edit screen
		if ( $screen->post_type !== Dynamic_Helper::get_slug() ) {
			return false;
		}

		// on overview screens
		if ( $screen->base === 'edit' ) {

			/**
			 * Tabs
			 */
			$filter_type = isset( $_GET['template_type'] ) ? $_GET['template_type'] : '';
			$url         = get_admin_url( null, '/' . $screen->parent_file );
			echo '<div id="templates_tabs" class="greyd_tabs">
                    <a class="tab ' . ( $filter_type == '' ? 'active' : '' ) . ' blue" href="' . $url . '">' . __( "All", 'greyd_hub' ) . '</a>';
			foreach ( Dynamic_Helper::get_template_types() as $type ) {
				if ( $type['slug'] == 'woo' && ! class_exists( 'woocommerce' ) ) {
					continue;
				}
				echo '<a class="tab ' . ( $filter_type == $type['slug'] ? 'active' : '' ) . ' ' . $type['color'] . '" href="' . $url . '&template_type=' . $type['slug'] . '">' . $type['title'] . '</a>';
			}
			echo '</div>';
		}

		// on edit screens
		if ( $screen->base === 'post' && $screen->action !== 'add' ) {

			$post_id = $_GET['post'];
			$ttype   = get_the_terms( $post_id, 'template_type' );

			if ( ! $ttype ) {
				return false;
			}

			$ttype = $ttype[0]->slug;

			// template type
			$color = '';
			foreach ( Dynamic_Helper::get_template_types() as $type ) {
				if ( $ttype == $type['slug'] ) {
					$color = $type['color'];
					break;
				}
			}
			echo '<span id="template_type" class="greyd_info_box ' . $color . '" style="display:none">' . get_the_terms( $post_id, 'template_type' )[0]->name . '</span>';

			// logging
			if ( $ttype === 'dynamic' ) {
				$slug         = get_post_field( 'post_name', $post_id, 'raw' );
				$template_log = Admin::get_template_log( $post_id, $slug );
				$render       = Admin::get_template_log_html( $template_log );
				echo "<div id='template_log'><script>var template_log=" . Admin::calc_log_total( $template_log ) . '</script>' . Admin::get_template_log_html( $template_log ) . '</div>';
			}
		}
	}

	/*
	====================================================================================
		Wizard
	====================================================================================
	*/

	// Add Template Wizard script and vars
	public function add_wizard_script() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		global $config;
		$screen = get_current_screen();
		if ( $screen->base === 'post' || $screen->base === 'edit' ) {
			$templates = Helper::get_all_posts( Dynamic_Helper::get_slug() );
			$plist     = array();
			foreach ( $templates as $template ) {
				$plist[] = array(
					'id'    => $template->id,
					'slug'  => $template->slug,
					'title' => $template->title,
				);
			}
			wp_add_inline_script( 'dynamic_admin_js', 'var dynamic_templates = ' . json_encode( $plist ) . ';' );
			wp_enqueue_media();
			wp_enqueue_editor();
		}
	}
	// Add Template Wizard
	public function add_wizard() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->post_type == Dynamic_Helper::get_slug() ) {
			if ( $screen->base == 'edit' ) {

				$this->render_wizard();
			}
		}
	}
	// render Templatep Wizard
	public function render_wizard() {

		if ( method_exists( '\dynamic', 'custom_post' ) ) {
			return;
		}

		$asset_url = plugin_dir_url( $this->config->plugin_file )."inc/wizard/assets/img";

		$pts        = Helper::get_all_posttypes();
		$posttypes  = '';
		$categories = array();
		foreach ( $pts as $posttype ) {
			if ( $posttype->slug != Dynamic_Helper::get_slug() && $posttype->slug != 'page' ) {
				if ( $posttype->slug == 'product' && class_exists( 'woocommerce' ) ) {
					continue;
				}
				$posttypes .= '<option value="' . $posttype->slug . '">' . $posttype->title . ' (' . $posttype->count . ')</option>';
				$taxonomies = Helper::get_all_taxonomies( $posttype->slug );
				foreach ( $taxonomies as $taxonomy ) {
					$tax_slug = isset( $taxonomy->slug ) ? $taxonomy->slug : null;
					if ( $tax_slug === 'category' || $tax_slug === $posttype->slug . '_category' ) {
						$categories[ $posttype->slug ] = '';
						$terms                         = Helper::get_all_terms( $taxonomy->slug );
						foreach ( $terms as $term ) {
							$categories[ $posttype->slug ] .= '<option value="' . $term->slug . '">' . $term->title . ' (' . $term->count . ')</option>';
						}
						break;
					}
				}
			}
		}
		$templates = array();
		foreach ( Dynamic_Helper::get_template_types() as $type ) {
			$posts = Helper::get_all_posts( Dynamic_Helper::get_slug() );
			$temp  = '';
			foreach ( $posts as $post ) {
				$ttype = wp_get_post_terms( $post->id, 'template_type' );
				if ( $ttype && is_array($ttype) && isset($ttype[0]) && $ttype[0]->slug == $type['slug'] ) {
					$language = '';
					if ( is_array( $post->lang ) && $post->lang['display_name'] != '' ) {
						$language = ' (' . $post->lang['language_code'] . ')';
					}
					$temp .= '<option value="' . $post->id . '">' . $post->title . $language . '</option>';
				}
			}
			if ( $temp != '' ) {
				$templates[ $type['slug'] ] = $temp;
			}
		}
		// debug($posttypes);
		// debug($categories);
		// debug($templates);

		echo '
        <div id="greyd-wizard" class="wizard template_wizard">
            <div class="wizard_box big">
                <div class="wizard_head">
                    <img class="wizard_icon" src="' . $asset_url . '/logo_light.svg">
                    <span class="close_wizard dashicons dashicons-no-alt"></span>
                </div>
                <div class="wizard_content">
                    <div data-slide="0-error">
                        <h2>' . __( 'Ooooops!', 'greyd_hub' ) . '</h2>
                        <div class="greyd_info_box orange" style="width: 100%; box-sizing: border-box;">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <p>' . __( "There was a problem:", 'greyd_hub' ) . '</p><br>
                                <p class="error_msg"></p>
                            </div>
                        </div>
                    </div>
                    <div data-slide="1">
                        <h2>' . __( "Create new template", 'greyd_hub' ) . '</h2>
                        <div class="row flex">
                            <div class="element">
                                <div class="label">' . __( "Type", 'greyd_hub' ) . '</div>
                                <select id="type" name="type">
                                    <optgroup label="' . __( "Default", 'greyd_hub' ) . '">
                                        <option value="dynamic">' . __( 'Dynamic Template', 'greyd_hub' ) . '</option>
                                    </optgroup> 
                                    <optgroup label="' . __( 'Navigation', 'greyd_hub' ) . '">
                                        <option value="footer">' . __( 'Footer', 'greyd_hub' ) . '</option>
                                        <option value="offmenu">' . __( "Off-menu", 'greyd_hub' ) . '</option>
                                    </optgroup> 
                                    <optgroup label="' . __( "Posts", 'greyd_hub' ) . '">
                                        <option value="single">' . __( "Post page", 'greyd_hub' ) . '</option>
                                        <option value="archives">' . __( "Post archive", 'greyd_hub' ) . '</option>
                                        <option value="search">' . __( "Search results", 'greyd_hub' ) . '</option>
                                    </optgroup> 
                                    <optgroup label="' . __( "Other", 'greyd_hub' ) . '">
                                        <option value="404">' . __( "404 page", 'greyd_hub' ) . '</option>' .
										'<option value="compatibility">' . __( "Compatibility message", 'greyd_hub' ) . '</option>
                                    </optgroup>';
		if ( class_exists( 'woocommerce' ) ) {
			echo '<optgroup label="' . __( 'WooCommerce', 'greyd_hub' ) . '">
                                        <option value="woo_product">' . __( "Product page", 'greyd_hub' ) . '</option>
                                        <option value="woo_shop">' . __( "Shop overview", 'greyd_hub' ) . '</option>
                                        <option value="woo_myaccount">' . __( "My account", 'greyd_hub' ) . '</option>
                                    </optgroup>';
		}
						echo '</select>
                            </div>
                            <div class="element" style="display:none">
                                <div class="label">' . __( 'Header', 'greyd_hub' ) . '</div>
                                <select id="header" name="header">
                                    <option value="main">' . __( "first header", 'greyd_hub' ) . '</option>
                                    <option value="meta">' . __( "second header", 'greyd_hub' ) . '</option>
                                </select>
                            </div>
                            <div class="element" style="display:none">
                                <div class="label">' . __( "Post type", 'greyd_hub' ) . '</div>
                                <select id="post_type" name="post_type">
                                    <option value="0">' . _x( "all", 'small', 'greyd_hub' ) . '</option>
                                    ' . $posttypes . '
                                </select>
                            </div>';
		foreach ( $categories as $posttype => $cats ) {
			echo '<div class="element" style="display:none">
                                <div class="label">' . __( "Category", 'greyd_hub' ) . '</div>
                                <select id="category_' . $posttype . '" class="category" name="category_' . $posttype . '">
                                    <option value="0">' . _x( "all categories", 'small', 'greyd_hub' ) . '</option>
                                    ' . $cats . '
                                </select>
                            </div>';
		}
		if ( class_exists( 'woocommerce' ) ) {
			echo '<div class="element" style="display:none">
                                <div class="label">' . __( "Type", 'greyd_hub' ) . '</div>
                                <select id="shop" name="shop">
                                    <option value="category">' . _x( "by category", 'small', 'greyd_hub' ) . '</option>
                                    <option value="tag">' . _x( "by tag", 'small', 'greyd_hub' ) . '</option>
                                </select>
                            </div>';
			$hooks = method_exists( 'vc\helper', 'get_dynamic_woocommerce_hooks' ) ? \vc\helper::get_dynamic_woocommerce_hooks() : array();
			echo '<div class="element" style="display:none">
                                <div class="label">' . __( 'Position', 'greyd_hub' ) . '</div>
                                <select id="myaccount" name="myaccount">';
			foreach ( $hooks as $hook => $name ) {
						echo '<option value="' . $hook . '">' . $name . '</option>';
			}
				echo '</select>
                            </div>';
		}
					echo ' </div>
                        <div class="row flex">
                            <div class="element grow">
                                <div class="label">' . __( 'Name', 'greyd_hub' ) . '</div>
                                <input id="name" name="name" type="text" value="" placeholder="' . __( "enter here", 'greyd_hub' ) . '" >
                                <input id="slug" name="slug" type="hidden" value="" >
                                <input id="ttype" name="ttype" type="hidden" value="" >
                            </div>
                        </div>
                        <div class="row flex">
                            <div class="element grow name_double" style="display:none">
                                <div class="greyd_info_box warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <div>
                                        <div style="margin-bottom:10px"><strong>' . __( "Attention:", 'greyd_hub' ) . '</strong></div>
                                        <div style="margin-bottom:10px">' . sprintf( __( "The selected template already exists (name: '%s'). To recreate the template, you must first put the existing one in the trash. You can also easily edit it or reset it to the default.", 'greyd_hub' ), '<span class="double_name">Templatename</span>' ) . '</div>
                                        <div style="margin-bottom:10px"><a class="double_reset" style="cursor:pointer;text-decoration:underline">' . __( "reset to default", 'greyd_hub' ) . '</a> | <a class="double_edit" href="#">' . __( "edit template", 'greyd_hub' ) . '</a></div>
                                    </div>
                                </div>
                            </div>
                            <div class="element grow name_ok">
                                <div class="label">' . __( "Content", 'greyd_hub' ) . '</div>
                                <div class="content_options">
                                    <span id="empty" class="content_option active"><img class="option_icon" src="' . $asset_url . '/new_select.png"><p>' . _x( "empty", 'small', 'greyd_hub' ) . '</p></span>
                                    <span id="default" class="content_option"><img class="option_icon" src="' . $asset_url . '/default.png"><p>' . _x( "Greyd template", 'small', 'greyd_hub' ) . '</p></span>
                                    <span id="copy" class="content_option"><img class="option_icon" src="' . $asset_url . '/copy.png"><p>' . _x( "Copy", 'small', 'greyd_hub' ) . '</p>';
		foreach ( $templates as $ttype => $template ) {
			echo '<select id="copy_' . $ttype . '" class="copy" name="copy_' . $ttype . '">
                                            <option value="0">' . _x( "select template", 'small', 'greyd_hub' ) . '</option>
                                            ' . $template . '
                                        </select>';
		}
					echo '</span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div data-slide="11">
                        <h2>' . __( "Congratulations!", 'greyd_hub' ) . '</h2>
                        <div class="install_txt">' . __( 'The template "%s" was successfully created.', 'greyd_hub' ) . '</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                </div>
                <div class="wizard_foot">
                    <div data-slide="0-error">
                        <div class="flex">
                            <span class="back button button-secondary">' . __( "back", 'greyd_hub' ) . '</span>
                            <span class="close_wizard reload button button-primary">' . __( "close", 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="1">
                        <div class="flex">
                            <span class="close_wizard button button-secondary">' . __( "back", 'greyd_hub' ) . '</span>
                            <span class="create button button-primary">' . _x( "create template", 'small', 'greyd_hub' ) . '</span>
                        </div>
                    </div>
                    <div data-slide="11">
                        <div class="flex">
                            <span class="close_wizard reload button button-secondary">' . __( "close", 'greyd_hub' ) . '</span>
                            <a class="finish_wizard button button-primary" href="" target="_blank">' . _x( "edit template", 'small', 'greyd_hub' ) . '</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
	}

}
