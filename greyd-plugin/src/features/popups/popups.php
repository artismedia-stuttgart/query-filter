<?php
/**
 * Main Admin page to manage Popups.
 */
namespace Greyd\Popups;

use Greyd\Helper as Helper;

if( ! defined( 'ABSPATH' ) ) exit;

new Admin($config);
class Admin {
    
    /**
     * Holds the plugin config.
     * 
     * @var object
     */
    private $config;
    
    /**
     * Holds the post_type Slug.
     * 
     * @var string
     */
    private $post_type = "greyd_popup";

    /**
     * Standalone mode.
     * 
     * @var bool
     */
    public static $is_standalone = false;

    /**
     * Hold the feature page config
     * slug, title, url, cap, callback
     */
    public static $page = array();

    
    /**
     * Class constructor.
     */
    public function __construct($config) {
        // set config
        $this->config = (object)$config;
        if (isset($this->config->is_standalone) && $this->config->is_standalone == true) {
            // standalone mode
            self::$is_standalone = true;
        }
        
        // define page details
        add_action( 'init', function() {
            self::$page = array(
                'slug'      => 'popups',
                'title'     => __("Pop-ups", 'greyd_hub'),
                'descr'     => __("Create custom pop-ups with individual designs and easy maintenance of conditions.", 'greyd_hub'),
                'url'       => admin_url( 'edit.php?post_type=greyd_popup'),
                'cap'       => get_post_type_object('greyd_popup') ? get_post_type_object('greyd_popup')->cap->edit_posts : 'edit_posts'
            );
            // debug(self::$page);
        }, 2 );


        // greyd backend
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts') );

            add_filter( 'greyd_dashboard_tabs', array($this, 'add_greyd_dashboard_tab') );
            add_filter( 'greyd_dashboard_active_panels', array($this, 'add_greyd_dashboard_panel') );
            add_filter( 'greyd_dashboard_panels', array($this, 'add_greyd_classic_dashboard_panel') ); // for classic
        }
        add_filter( 'greyd_admin_bar_group', array($this, 'add_greyd_admin_bar_group_item') );

        // define and add custom post type
        add_action( 'init', array($this, 'add_posttype'), 1 );
        add_filter( 'manage_edit-greyd_popup_columns', array($this, 'add_column_heading') );
        add_action( "manage_greyd_popup_posts_custom_column", array($this, 'add_column_value'), 10, 2 );

        // hook for ajax handling
        add_action( 'greyd_ajax_mode_mod_popup', array( $this, 'mod_popup_prio' ) );
        add_action( 'greyd_ajax_mode_create_popup', array( $this, 'create_popup' ) );
        
        // edit screen for posttype
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'save_post', array($this, 'save_post'), 20, 2 );
        
        // add wizard
        add_action( 'current_screen', array($this, 'add_wizard') );

    }
    
    /*
    =======================================================================
        admin menu
    =======================================================================
    */

	/**
	 * Add scripts
	 */
	public function load_backend_scripts() {

		$screen   = get_current_screen();
		$posttype = get_post_type() !== false ? get_post_type() : ( isset( $screen->post_type ) ? $screen->post_type : '' );
		// debug($screen);
		// debug($posttype);

		// load scripts only on posttype screens
		if ( $posttype === $this->post_type ) {
			
			$css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
			$js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

			/**
			 * standalone mode (without greyd_hub)
			 * 'greyd-admin-style' and 'greyd-admin-script'
			 * are the basic admin scripts, usually provided by the greyd_hub.
			 * If this feature runs without greyd_hub, these scripts are not registered,
			 * so they are added here as fallback.
			 */
			if ( ! wp_style_is( 'greyd-admin-style', 'registered' ) ) {
				// debug("add backend styles");
				wp_register_style(
					'greyd-admin-style',
					$css_uri . '/backend.css',
					null,
					GREYD_VERSION,
					'all'
				);
				wp_enqueue_style( 'greyd-admin-style' );
			}

			// Styles
			wp_register_style(
				'greyd-popups-admin-style',
				$css_uri . '/popups-admin.css',
				null,
				GREYD_VERSION,
				'all'
			);
			wp_enqueue_style( 'greyd-popups-admin-style' );

			// Scripts
			if ( $screen->base === 'post' || $screen->base === 'edit' ) {
				wp_register_script(
					'greyd-popups-admin-script',
					$js_uri . '/popups-admin.js',
					array( 'jquery' ),
					GREYD_VERSION
				);
				wp_enqueue_script( 'greyd-popups-admin-script' );

				// standalone mode
				if ( ! wp_style_is( 'greyd-admin-script', 'registered' ) ) {
					// inline script before
					// define global greyd var and ajax vars
					wp_add_inline_script(
						'greyd-popups-admin-script',
						'if (typeof greyd === "undefined") var greyd = {}; greyd = {' .
							'upload_url:   "' . wp_upload_dir()['baseurl'] . '",' .
							'ajax_url:     "' . admin_url( 'admin-ajax.php' ) . '",' .
							'nonce:        "' . wp_create_nonce( 'install' ) . '",' .
							'is_multisite: "' . ( is_multisite() ? 'true' : 'false' ) . '",' .
							'...greyd' .
						'};',
						'before'
					);
				}

				// script translations
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations(
						'greyd-popups-admin-script',
						'greyd_hub',
						$this->config->plugin_path . '/languages'
					);
				}
			}
		}
	}

    /**
     * Add item to Greyd.Suite adminbar group.
     * @see filter 'greyd_admin_bar_group'
     */
    public function add_greyd_admin_bar_group_item($items) {
        // debug($items);

        // in frontend
        if (!is_admin()) {

            if ( current_user_can(self::$page['cap']) ) {
                array_push($items, array(
                    'parent' => "greyd_toolbar",
                    'id'     => self::$page['slug'],
                    'title'  => self::$page['title'],
                    'href'   => self::$page['url'],
                    'priority' => 50
                ));
            }

        }

        return $items;
    }

    /**
     * Add dashboard tab if Hub is installed
     * @see filter 'greyd_dashboard_tabs'
     */
    public function add_greyd_dashboard_tab($tabs) {
        // debug($tabs);

        $tabs[self::$page['slug']] = array(
            'title'     => self::$page['title'],
            'slug'      => self::$page['slug'],
            'url'       => self::$page['url'],
            'cap'       => self::$page['cap'],
            'priority'  => 40,
            'condition' => 'post_type',
            'color'     => 'orange'
        );

        return $tabs;
    }

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_active_panels'
	 */
	public function add_greyd_dashboard_panel($panels) {
		
		$panels[ 'greyd-popups' ] = true;
		
		return $panels;
	}

	/**
	 * Add dashboard panel
	 * @see filter 'greyd_dashboard_panels'
	 */
	public function add_greyd_classic_dashboard_panel($panels) {
		
		$panels[self::$page['slug']] = array(
			'icon'  => 'popups',
			'title' => self::$page['title'],
			'descr' => self::$page['descr'],
			'btn'   => array(
				array( 'text' => __("View pop-ups", 'greyd_hub'), 'url' => self::$page['url'] ),
				array( 'text' => __("Create", 'greyd_hub'), 'url' => admin_url('post-new.php?post_type=greyd_popup'), 'class' => 'button button-ghost' )
			),
			'cap'       => self::$page['cap'],
			'priority'  => 70,
		);

		return $panels;
	}


    /*
    ====================================================================================
        Popup Posttype
    ====================================================================================
    */

    /**
     * Add custom post type.
     */
    public function add_posttype() {
        
        $name = __("Pop-ups", 'greyd_hub');
        $singular = __("Pop-up", 'greyd_hub');
        
        // define new post type
        $post_type_labels = array(
            'name'               => $name,
            'singular_name'      => $singular,
            'menu_name'          => $name,
            'name_admin_bar'     => $singular,
            'add_new'            => sprintf(__( "Create new %s", 'greyd_hub' ), $singular),
            'add_new_item'       => sprintf(__( "Create %s", 'greyd_hub' ), $singular),
            'new_item'           => sprintf(__( "New %s", 'greyd_hub' ), $singular),
            'edit_item'          => sprintf(__( "Edit %s", 'greyd_hub' ), $singular),
            'view_item'          => sprintf(__( "Show %s", 'greyd_hub'), $singular),
            // 'all_items'          => sprintf(__( "All %s", 'greyd_hub'), $name),
            'all_items'          => $name,
            'search_items'       => sprintf(__( "Search %s", 'greyd_hub'), $singular),
            'parent_item_colon'  => sprintf(__( "Parent %s", 'greyd_hub'), $singular),
            'not_found'          => sprintf(__( "No %s found", 'greyd_hub'), $name),
            'not_found_in_trash' => sprintf(__( "No %s found in the trash", 'greyd_hub'), $singular),
        );
        $post_type_arguments = array(
            'labels'             => $post_type_labels,
            'description'        => __( "Description", 'greyd_hub' ),
            'public'             => true,
            'exclude_from_search'=> true,
            'publicly_queryable' => false,
            'show_ui'            => true,
            // 'show_in_menu'       => 'greyd_dashboard',
            'show_in_menu'       => self::$is_standalone ? true : 'greyd_dashboard',
            'menu_position'      => 82, // 20,
            'menu_icon'          => 'dashicons-external',
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'supports'           => array( 'title', 'editor', 'custom-fields' ),
            'admin_column_sortable' => true,
            'admin_column_filter'   => true,
            'show_in_rest' => true
        );

        // register post_type and taxonony
        register_post_type($this->post_type, $post_type_arguments);

		/**
		 * Register post meta to access it in block editor
		 * 
		 * @since 1.5.6 including detailed schema
		 * @see https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
		 */
		register_post_meta(
			$this->post_type,
			'popup_design',
			array(
				'type'         => 'object',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'object',
						'additionalProperties' => true,
						'properties' => array(
							'version' => array(
								'type' => 'number',
							),
							'modified' => array(
								'type' => 'number',
							),
							'layout'  => array(
								'type' => 'object',
								// 'additionalProperties' => true,
								'properties' => array(
									'position' => array(
										'type' => 'string'
									),
									'size' => array(
										'type' => 'object',
										'properties' => array(
											'width' => array(
												'type' => 'string',
											),
											'height' => array(
												'type' => 'string',
											),
											'align' => array(
												'type' => 'string',
											),
										)
									),
									'padding' => array(
										'type' => 'object',
										'properties' => array(
											'top' => array(
												'type' => 'string',
											),
											'right' => array(
												'type' => 'string',
											),
											'bottom' => array(
												'type' => 'string',
											),
											'left' => array(
												'type' => 'string',
											)
										)
									),
									'margin' => array(
										'type' => 'object',
										'properties' => array(
											'top' => array(
												'type' => 'string',
											),
											'right' => array(
												'type' => 'string',
											),
											'bottom' => array(
												'type' => 'string',
											),
											'left' => array(
												'type' => 'string',
											)
										)
									),
									'mobile' => array(
										'type' => 'object',
										'properties' => array(
											'position' => array(
												'type' => 'string'
											),
											'size' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'height' => array(
														'type' => 'string',
													),
													'align' => array(
														'type' => 'string',
													),
												)
											),
											'padding' => array(
												'type' => 'object',
												'properties' => array(
													'top' => array(
														'type' => 'string',
													),
													'right' => array(
														'type' => 'string',
													),
													'bottom' => array(
														'type' => 'string',
													),
													'left' => array(
														'type' => 'string',
													)
												)
											),
											'margin' => array(
												'type' => 'object',
												'properties' => array(
													'top' => array(
														'type' => 'string',
													),
													'right' => array(
														'type' => 'string',
													),
													'bottom' => array(
														'type' => 'string',
													),
													'left' => array(
														'type' => 'string',
													)
												)
											),
										)
									)
								)
							),
							'design'  => array(
								'type' => 'object',
								// 'additionalProperties' => true,
								'properties' => array(
									'colors' => array(
										'type' => 'object',
										'properties' => array(
											'text' => array(
												'type' => 'string',
											),
											'headline' => array(
												'type' => 'string',
											),
											'background' => array(
												'type' => 'string',
											)
										)
									),
									'border' => array(
										'type' => 'object',
										'properties' => array(
											'width' => array(
												'type' => 'string',
											),
											'style' => array(
												'type' => 'string',
											),
											'color' => array(
												'type' => 'string',
											),
											'top' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'style' => array(
														'type' => 'string',
													),
													'color' => array(
														'type' => 'string',
													)
												)
											),
											'right' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'style' => array(
														'type' => 'string',
													),
													'color' => array(
														'type' => 'string',
													)
												)
											),
											'bottom' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'style' => array(
														'type' => 'string',
													),
													'color' => array(
														'type' => 'string',
													)
												)
											),
											'left' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'style' => array(
														'type' => 'string',
													),
													'color' => array(
														'type' => 'string',
													)
												)
											),
										)
									),
									'border_radius' => array(
										'type' => array( 'object', 'string' ),
										'properties' => array(
											'topLeft' => array(
												'type' => 'string',
											),
											'topRight' => array(
												'type' => 'string',
											),
											'bottomRight' => array(
												'type' => 'string',
											),
											'bottomLeft' => array(
												'type' => 'string',
											)
										)
									),
									'shadow' => array(
										'type' => 'string'
									),
									'hover' => array(
										'type' => 'object',
										'properties' => array(
											'colors' => array(
												'type' => 'object',
												'properties' => array(
													'text' => array(
														'type' => 'string',
													),
													'headline' => array(
														'type' => 'string',
													),
													'background' => array(
														'type' => 'string',
													)
												)
											),
											'border' => array(
												'type' => 'object',
												'properties' => array(
													'width' => array(
														'type' => 'string',
													),
													'style' => array(
														'type' => 'string',
													),
													'color' => array(
														'type' => 'string',
													),
													'top' => array(
														'type' => 'object',
														'properties' => array(
															'width' => array(
																'type' => 'string',
															),
															'style' => array(
																'type' => 'string',
															),
															'color' => array(
																'type' => 'string',
															)
														)
													),
													'right' => array(
														'type' => 'object',
														'properties' => array(
															'width' => array(
																'type' => 'string',
															),
															'style' => array(
																'type' => 'string',
															),
															'color' => array(
																'type' => 'string',
															)
														)
													),
													'bottom' => array(
														'type' => 'object',
														'properties' => array(
															'width' => array(
																'type' => 'string',
															),
															'style' => array(
																'type' => 'string',
															),
															'color' => array(
																'type' => 'string',
															)
														)
													),
													'left' => array(
														'type' => 'object',
														'properties' => array(
															'width' => array(
																'type' => 'string',
															),
															'style' => array(
																'type' => 'string',
															),
															'color' => array(
																'type' => 'string',
															)
														)
													),
												)
											),
											'border_radius' => array(
												'type' => array( 'object', 'string' ),
												'properties' => array(
													'topLeft' => array(
														'type' => 'string',
													),
													'topRight' => array(
														'type' => 'string',
													),
													'bottomRight' => array(
														'type' => 'string',
													),
													'bottomLeft' => array(
														'type' => 'string',
													)
												)
											),
											'shadow' => array(
												'type' => 'string'
											)
										)
									)
								)
							),
							'more'  => array(
								'type' => 'object',
								'additionalProperties' => true,
							),
						),
					),
				),
				'auth_callback' => function() {
					return current_user_can('edit_posts');
				}
			)
		);
		
    }

    /**
     * Add custom columns to edit table.
     * 
     * @param object $columns   Table columns.
     * @return object $columns  Altered table columns.
     */
    public function add_column_heading( $columns ) {
        $slug_column = array(
            'greyd_popup_trigger' => __( "Trigger", 'greyd_hub' ),
            'greyd_popup_display' => __( "Appearance", 'greyd_hub' ),
            'greyd_popup_rules' => __( "Rules", 'greyd_hub' ),
            'greyd_popup_prio' => __( "Priority", 'greyd_hub' )
        );
        $columns = array_slice( $columns, 0, 2, true ) + $slug_column + array_slice( $columns, 2, count( $columns ) - 1, true );

        return $columns;
    }

    /**
     * Show values to the custom columns.
     * 
     * @param string $column_name   Name of the column.
     * @param int $id               post_id of the current Popup.
     */
    public function add_column_value( $column_name, $id ) {
        $meta = (array)get_post_meta($id, 'popup_settings', true);

        foreach( $meta as $k => $v ) {
            $meta[$k] = (object)$v;
        }

        if ($column_name == 'greyd_popup_trigger') {
            $trigger = array();
            if (isset($meta['on_init']) && $meta['on_init']->value != 'off') $trigger[] = __("Page load", 'greyd_hub');
            if (isset($meta['on_scroll']) && $meta['on_scroll']->value != 'off') $trigger[] = __("Scroll", 'greyd_hub');
            if (isset($meta['on_idle']) && $meta['on_idle']->value != 'off') $trigger[] = __("Inactivity", 'greyd_hub');
            if (isset($meta['on_exit']) && $meta['on_exit']->value != 'off') $trigger[] = __("Exit intent", 'greyd_hub');
            if (count($trigger) > 0) 
                echo implode(', ', $trigger);
            else echo "--";
        }
        else if ($column_name == 'greyd_popup_display') {
            $display = array();
            foreach ($meta as $key => $value) {
                if (strpos($key, 'on_') === 0) {
                    if ($key != 'on_init' && $key != 'on_scroll' && $key != 'on_idle' && $key != 'on_exit') {
                        if ($value->value != 'off') {
                            $posttype = get_post_type_object(substr($key, 3));
                            if ($posttype) {
                                $pre = __("All", 'greyd_hub');
                                if ($value->value == 'custom') $pre = __("Some", 'greyd_hub');
                                $display[] = $pre." ".$posttype->label;
                            }
                        }
                    }
                }
            }
            if (count($display) > 0) 
                echo implode(', ', $display);
            else echo "--";
        }
        else if ($column_name == 'greyd_popup_rules') {
            $rules = array();
            if (isset($meta['delay']) && $meta['delay']->value != 'off') $rules[] = __("After X pages", 'greyd_hub');
            if (isset($meta['urlparam']) && $meta['urlparam']->value != 'off') $rules[] = __("URL parameter", 'greyd_hub');
            if (isset($meta['referer']) && $meta['referer']->value != 'off') $rules[] = __("Previous page", 'greyd_hub');
            if (isset($meta['user']) && $meta['user']->value != 'off') $rules[] = __("User", 'greyd_hub');
            if (isset($meta['time']) && $meta['time']->value != 'off') $rules[] = __("Time", 'greyd_hub');
            if (isset($meta['device']) && $meta['device']->value != 'off') $rules[] = __("Device", 'greyd_hub');
            if (isset($meta['browser']) && $meta['browser']->value != 'off') $rules[] = __('Browser', 'greyd_hub');
            if (count($rules) > 0) 
                echo implode(', ', $rules);
            else echo "--";
        }
        else if ($column_name == 'greyd_popup_prio') {
            if (isset($meta['prio'])) {
                if (self::$is_standalone) echo $meta['prio']->value;
                else {
                    $off1 = $meta['prio']->value == 1 ? 'off' : '';
                    $off2 = $meta['prio']->value == 9 ? 'off' : '';
                    echo "<span class='popup_prio ".$off1."' onclick='greyd.popup.prio_dec(".$id.")'>-</span>
                        <span id='popup_".$id."_priority'>".(isset($meta['prio']) ? $meta['prio']->value : 5)."</span>
                        <span class='popup_prio ".$off2."' onclick='greyd.popup.prio_inc(".$id.")'>+</span>";
                }
            }
            else echo "--";
        }
    }
    
    /**
     * Ajax function to increase or decrease priority on edit screen.
     * @see action 'greyd_ajax_mode_'
     * 
     * @param array $post_data   $_POST data.
     */
    public function mod_popup_prio( $post_data ) {

        // debug
        echo "\r\n\r\n"."MODIFY POPUP PRIORITY";
           
        $return = "";
        if (isset($post_data['id']) && isset($post_data['value'])) {

            $meta = get_post_meta($post_data['id'], 'popup_settings', true);
            if ($meta && isset($meta->prio)) {
                $meta->prio->value = $post_data['value'];
                // update popup
                wp_update_post(array(
                    "ID"            => $post_data['id'],
                    'meta_input'    => array( 'popup_settings' => $meta ),
                ));
                // check if there was an error updating
                if (!is_wp_error($post_id)) {
                    echo sprintf("\r\n"."* pop-up \"%s\" successfully updated.", $post_data['id']);
                    $return = "Pop-up <strong>".$post_data['id']."</strong> modified with priority value ".$post_data['value']."!";
                }
                else {
                    echo sprintf("\r\n"."* error updating pop-up \"%s\":", $post_data['id']);
                    echo "\r\n".$post_id->get_error_message();
                } 
            }

        }

        // echo $return;
        echo "\r\n\r\n"."------------- debug end -------------"."\r\n\r\n";

		// return
		if ( empty( $return ) ) {
			echo "error::unable to create popup.";
		} else {
			if (strpos($return, '<pre>') !== false) {
				// remove debugs
				$return = preg_replace('/<pre>(.|\n)*<\/pre>/', '', $return);
			}
			echo "success::".$return;
		}

        // end ajax request
        die();

    }
    
	/**
	 * Ajax function to increase or decrease priority on edit screen.
	 * @see action 'greyd_ajax_mode_'
	 * 
	 * @param array $post_data   $_POST data.
	 */
	public function create_popup( $post_data ) {

		// debug
		echo "\r\n\r\n"."CREATE POPUP (v2)";
		
		if ( ! isset( $post_data['name'] ) ) {
			wp_die( "error::unable to create pop-up." );
		}

		$wp_post = array(
			'post_type'    => 'greyd_popup',
			'post_status'  => 'draft',
			'post_title'   => $post_data['name'],
			'post_name'    => sanitize_title( empty($post_data['slug']) ? $post_data['name'] : $post_data['slug'] ),
			'post_content' => $this->default_contents()['empty']['content'],
			'meta_input'   => $this->default_contents()['empty']['meta']
		);

		if ( $post_data['content'] == "copy" && isset( $post_data['copy'] ) ) {
			$wp_post['post_content'] = get_post_field('post_content', $post_data['copy']);
			$wp_post['meta_input']   = array(
				'popup_settings' => get_post_meta($post_data['copy'], 'popup_settings', true),
				'popup_design' => get_post_meta($post_data['copy'], 'popup_design', true),
			);
		}
		else {
			foreach ( $this->default_contents() as $id => $content) {
				if ( $post_data['content'] == $id ) {
					$wp_post['post_content'] = $content['content'];
					$wp_post['meta_input']   = $content['meta'];
					$found = true;
					break;
				}
			}
		}
		// debug( $wp_post );
		// wp_die( "error::debug" );

		// create popup
		$post_id = wp_insert_post( $wp_post );

		// check if there was an error
		if ( is_wp_error($post_id) ) {
			wp_die( "error::unable to create pop-up: ".$post_id->get_error_message() );
		}

		echo "\r\n\r\n"."------------- debug end -------------"."\r\n\r\n";

		wp_die( "success::Pop-up <strong>{$post_data['slug']}</strong> (id {$post_id}) created!" );
	}

    /*
    ====================================================================================
        Pop-up Meta
    ====================================================================================
    */

    /**
     * Add Metabox to Popup post screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'popup_settings', // ID
            __("Display", 'greyd_hub'), // Title
            array($this, 'render_settings_box'), // Callback
            $this->post_type, // CPT name
            'normal', // advanced = at bottom of page
            'low' // Priority
            //array $callback_args = null // Arguments passed to the callback
        );
    }

    /**
     * Render Settings Metabox.
     * 
     * @param WP_Post $post     The current post object.
     */
    public function render_settings_box($post) {
        $rows       = [];
        $post_id    = $post->ID;
        $name       = 'popup_settings';
        $meta       = (array)get_post_meta($post_id, $name, true);
        // debug($meta);

        $trigger = array(
            'on_init' => array(
                'type'          => 'switch', 
                'param_name'    => 'on_init', 
                'head'          => __("On page load", 'greyd_hub'), 
                'label'         => __("After %s seconds", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'numfield', 
                        'param_name'    => "time",
                        'default'       => '15', // string
                        'steps'         => '1',
                        'min'           => '0',
                        'max'           => '360'
                    ),
                ),
            ),
            'on_scroll' => array(
                'type'          => 'switch', 
                'param_name'    => 'on_scroll', 
                'head'          => __("On scroll", 'greyd_hub'), 
                'label'         => __("After %s of the page (px, % or vh)", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'textfield', 
                        'param_name'    => "height",
                        'default'       => '50%', // string
                    ),
                ),
            ),
            'on_idle' => array(
                'type'          => 'switch', 
                'param_name'    => 'on_idle', 
                'head'          => __("On inactivity", 'greyd_hub'), 
                'label'         => __("After %s seconds inactivity", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'numfield', 
                        'param_name'    => "time",
                        'default'       => '15', // string
                        'steps'         => '1',
                        'min'           => '0',
                        'max'           => '360'
                    ),
                ),
            ),
            'on_exit' => array(
                'type'          => 'switch', 
                'param_name'    => 'on_exit', 
                'head'          => __("Exit intent", 'greyd_hub'), 
                'label'         => __("When the user leaves the page", 'greyd_hub'), 
                'default'       => 'on', // 'on'/'off'
            ),
            'button_popup' => array(
                'type'          => 'info', 
                'param_name'    => 'button_popup', 
                'head'          => __("By a button", 'greyd_hub'), 
                'label'         => sprintf(
                    "<p>".__("To trigger a pop-up with a button, select the button type '%s' for any button on a page and select the respective pop-up.", 'greyd_hub')."</p>".
                    "<p>".__("It is best not to set any additional trigger, appearances or rules - the pop-up will be displayed automatically. We also recommend unchecking the checkbox for '%s' below.", 'greyd_hub')."</p>",
                    '<b>'._x("Open pop-up", 'small', 'greyd_hub').'</b>',
                    '<b>'.__("Show pop-up only once", 'greyd_hub').'</b>' )
            ),
        );

        $display = array(
            'on_page' => array(
                'type'          => 'radio_switch', 
                'radio'         => array(
                    __("Show", 'greyd_hub') => 'on',
                    __("Custom", 'greyd_hub') => 'custom', 
                    __("Hide", 'greyd_hub') => 'off', 
                ),
                'param_name'    => 'on_page', 
                'head'          => __("On normal pages", 'greyd_hub'), 
                'default'       => 'on', // 'on'/'custom'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'autotags', 
                        'param_name'    => "items", 
                        'label'         => __("Choose pages", 'greyd_hub'), 
                        'values'        => array(), 
                        'default'       => '', // string
                    ),
                ),
            ),
            'on_system' => array(
                'type'          => 'radio_switch', 
                'radio'         => array(
                    __("Show", 'greyd_hub') => 'on',
                    __("Custom", 'greyd_hub') => 'custom', 
                    __("Hide", 'greyd_hub') => 'off', 
                ),
                'param_name'    => 'on_system', 
                'head'          => __("On system pages (front page, 404, search...)", 'greyd_hub'),
                'default'       => 'off', // 'on'/'custom'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "show_on", 
                        'values'        => array( 
                            __("On the front page", 'greyd_hub') => 'front',
                            __("On the 404 page", 'greyd_hub') => '404',
                            __("In the search", 'greyd_hub') => 'search',
                        ), 
                        'default'       => '', // string
                    ),
                ),
            ),
            'on_post' => array(
                'type'          => 'radio_switch', 
                'radio'         => array(
                    __("Show", 'greyd_hub') => 'on',
                    __("Custom", 'greyd_hub') => 'custom', 
                    __("Hide", 'greyd_hub') => 'off', 
                ),
                'param_name'    => 'on_post', 
                'head'          => __("On post pages", 'greyd_hub'), 
                'default'       => 'custom', // 'on'/'custom'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'autotags', 
                        'param_name'    => "items", 
                        'label'         => __("Choose posts", 'greyd_hub'), 
                        'values'        => array(), 
                        'default'       => '', // string
                    ),
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "show_on", 
                        'values'        => array( 
                            __("On archive pages", 'greyd_hub') => 'archive', 
                        ), 
                        'default'       => '', // string
                    ),
                ),
            ),
            // 'dpts' => ...
        );

        foreach (Helper::get_all_posttypes() as $pt) {
            if ($pt->count == 0) continue;
            
            // posts
            $all        = Helper::get_all_posts($pt->slug);
            $content    = array();
            $single     = $pt->slug == 'page' ? __("Pages", 'greyd_hub') : __("Posts", 'greyd_hub');
            $values     = array( $single => array() );
            foreach ($all as $item) {
                $title = urlencode($item->title);
                $content[$title] = $item->id;
                $values[$single][$title] = $item->id;
            }
            
            // categories
            $cats = Helper::get_category_slug($pt->slug);
            if ($cats) {
                $values[__("Categories", 'greyd_hub')] = array();
                $cats = Helper::get_all_terms($cats);
                foreach ($cats as $item) {
                    $title = urlencode($item->title);
                    $content[$title.__(" (category)", 'greyd_hub')] = "cat_".$item->id;
                    $values[__("Categories", 'greyd_hub')][$title] = "cat_".$item->id;
                }
            }
            $slug = "on_".$pt->slug;
            if ($pt->slug == 'post' || $pt->slug == 'page')
                $display[$slug]['params'][0]['values'] = $values;
            else {
                $display[$slug] = array(
                    'type'          => 'radio_switch', 
                    'radio'         => array(
                        __("Show", 'greyd_hub') => 'on',
                        __("Custom", 'greyd_hub') => 'custom', 
                        __("Hide", 'greyd_hub') => 'off', 
                    ),
                    'param_name'    => $slug, 
                    'head'          => sprintf(__('On "%s" pages', 'greyd_hub'), $pt->title), 
                    'options'       => $content, 
                    'default'       => 'off', // 'on'/'custom'/'off'
                    'params'        => array(
                        array( 
                            'type'          => 'autotags', 
                            'param_name'    => "items", 
                            'label'         => sprintf(__("Select %s", 'greyd_hub'), $pt->title), 
                            'values'        => $values, 
                            'default'       => '', // string
                        ),
                    ),
                );
                // check for archive
                $pt_data = get_post_type_object($pt->slug);
                if ($pt_data->has_archive == true) {
                    $display[$slug]['params'][] = array( 
                        'type'          => 'checkbox', 
                        'param_name'    => 'show_on', 
                        'values'        => array( 
                            __("On archive pages", 'greyd_hub') => "archive", 
                        ), 
                        'default'       => '', // string
                    );
                }
            }
        }

        $conditions = array( 
            _x("Has the value:", 'small', 'greyd_hub') => 'is',
            _x("Contains the value:", 'small', 'greyd_hub') => 'has',
            _x("Has not the value:", 'small', 'greyd_hub') => 'is_not',
            _x("Is set", 'small', 'greyd_hub') => 'not_empty',
            _x("Is not set", 'small', 'greyd_hub') => 'empty',
        );
        $conditions2 = array( 
            _x("Contains:", 'small', 'greyd_hub') => 'has',
            _x("Doesn't contain:", 'small', 'greyd_hub') => 'has_not',
            _x("Is:", 'small', 'greyd_hub') => 'is',
            _x("Is not:", 'small', 'greyd_hub') => 'is_not',
        );
        $user_roles = array( _x("Unknown (not logged in)", 'small', 'greyd_hub') => "none" );
        foreach (get_editable_roles() as $role => $atts) {
            $user_roles[$atts["name"]] = $role;
        }
        $times = array(
            _x("In the morning (from 5am)", 'small', 'greyd_hub') => "5-12",
            _x("In the afternoon (from 12 pm)", 'small', 'greyd_hub') => "12-17",
            _x("In the evening (from 5 pm)", 'small', 'greyd_hub') => "17-23",
            _x("At night (11 pm to 5 am)", 'small', 'greyd_hub') => "23-24,0-5",
            _x("Custom time", 'small', 'greyd_hub') => "custom"
        );
        $rules = array(
            'delay' => array(
                'type'          => 'switch', 
                'param_name'    => 'delay', 
                'head'          => __("After X pages", 'greyd_hub'), 
                'label'         => __("After %s visited pages", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'numfield', 
                        'param_name'    => "count",
                        'default'       => '3', // string
                        'steps'         => '1',
                        'min'           => '0',
                        'max'           => '20'
                    ),
                ),
            ),
            'urlparam' => array(
                'type'          => 'switch', 
                'param_name'    => 'urlparam', 
                'head'          => __("Depending on the URL parameter", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'textfield', 
                        'param_name'    => "name", 
                        'label'         => __("URL parameter", 'greyd_hub' ),
                        'placeholder'   => __("Name", 'greyd_hub' ),
                        'default'       => '', // string
                    ),
                    array( 
                        'type'          => 'select', 
                        'param_name'    => "condition", 
                        'options'       => $conditions,
                        'default'       => 'is', // option
                    ),
                    array( 
                        'type'          => 'textfield', 
                        'param_name'    => "value", 
                        'placeholder'   => __("Value", 'greyd_hub' ),
                        'default'       => '', // string
                    ),
                ),
                'class'         => 'inline',
            ),
            'referer' => array(
                'type'          => 'switch', 
                'param_name'    => 'referer', 
                'head'          => __("Depending on the previous page", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'select', 
                        'param_name'    => "condition", 
                        'label'         => __("Previous URL", 'greyd_hub' ),
                        'options'       => $conditions2,
                        'default'       => 'is', // option
                    ),
                    array( 
                        'type'          => 'textfield', 
                        'param_name'    => "value", 
                        'placeholder'   => __("URL or part of a URL", 'greyd_hub' ),
                        'default'       => '', // string
                    ),
                ),
                'class'         => 'inline',
            ),
            'user' => array(
                'type'          => 'switch', 
                'param_name'    => 'user', 
                'head'          => __("Only for certain users", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "select", 
                        'values'        => $user_roles, 
                        'default'       => '', // string
                    ),
                ),
            ),
            // date todo
            'time' => array(
                'type'          => 'switch', 
                'param_name'    => 'time', 
                'head'          => __("Only at certain times", 'greyd_hub'),
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "select", 
                        // 'label'         => __('Tageszeiten auswählen', 'greyd_hub'), 
                        'values'        => $times, 
                        'default'       => '', // string
                    ),
                    array( 
                        'type'          => 'textfield', 
                        'param_name'    => "custom", 
                        // 'label'         => __("Custom time", 'greyd_hub' ),
                        'description'   => __("Set times in full hours and separate them with hyphens (e.g. for 12 p.m. to 1 p.m.: 12-13).<br>Separate several time periods with comma (e.g. 12-13,15-16). <br>Specify times before and after midnight as individual sections (e.g. for 10 p.m. to 4 a.m.: 22-24,0-4).", 'greyd_hub'),
                        'default'       => '', // string
                    ),
                ),
            ),
            'device' => array(
                'type'          => 'switch', 
                'param_name'    => 'device', 
                'head'          => __("Depending on the device", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "select", 
                        'values'        => array( 
                            __('Windows', 'greyd_hub') => 'win', 
                            __('OSX', 'greyd_hub') => 'osx', 
                            __('iOS', 'greyd_hub') => 'ios', 
                            __('Android', 'greyd_hub') => 'android', 
                            __("Unknown device", 'greyd_hub') => 'unknown',
                        ), 
                        'default'       => '', // string
                    ),
                ),
            ),
            'browser' => array(
                'type'          => 'switch', 
                'param_name'    => 'browser', 
                'head'          => __("Depending on the browser", 'greyd_hub'), 
                'default'       => 'off', // 'on'/'off'
                'params'        => array(
                    array( 
                        'type'          => 'checkbox', 
                        'param_name'    => "select", 
                        'values'        => array( 
                            __('Firefox', 'greyd_hub') => 'firefox', 
                            __('Chrome', 'greyd_hub') => 'chrome', 
                            __('Opera', 'greyd_hub') => 'opera', 
                            __('Edge', 'greyd_hub') => 'edge', 
                            __('Internet Explorer', 'greyd_hub') => 'ie', 
                            __('Safari', 'greyd_hub') => 'safari', 
                            __("Unknown browser", 'greyd_hub') => 'unknown', 
                        ), 
                        'default'       => '', // string
                    ),
                    // todo
                    // array( 
                    //     'type'          => 'textfield', 
                    //     'param_name'    => "version", 
                    //     'label'         => __("version", 'greyd_hub' ),
                    //     // 'description'   => __("Set times in full hours and separate them with hyphens (e.g. for 12 p.m. to 1 p.m.: 12-13).<br>Separate several time periods with comma (e.g. 12-13,15-16). <br>Specify times before and after midnight as individual sections (e.g. for 10 p.m. to 4 a.m.: 22-24,0-4).", 'greyd_hub'),
                    //     'default'       => '', // string
                    // ),
                ),
            ),
        );

        $more = array(
            'prio' => array( 
                'type'          => 'select', 
                'param_name'    => 'prio', 
                'label'         => __("Priority", 'greyd_hub'), 
                'description'   => __("Pop-ups with higher priority are displayed <b>on top of</b> other pop-ups.<br>If the priority is the same, the date is used (newer = more important).", 'greyd_hub'), 
                'options'       => array( 
                    '10' => '10', '9' => '9', '8' => '8', '7' => '7', '6' => '6',
                    '5'.__(" (Default)", 'greyd_hub') => '5', '4' => '4',  '3' => '3', '2' => '2', '1' => '1',
                    
                ), 
                'default'       => '5',  // 0-9
            ),
            'highest' => array( 
                'type'          => 'checkbox', 
                'param_name'    => 'highest', 
                'label'         => __("As soon as the pop-up is triggered, hide all pop-ups with a lower priority", 'greyd_hub'), 
                'default'       => 'false', // 'true'/'false'
            ),
            'hashtag' => array( 
                'type'          => 'checkbox', 
                'param_name'    => 'hashtag', 
                'label'         => __("Set hashtags in URL", 'greyd_hub'), 
                'description'   => __("This function changes the URL of the page when the pop-up is opened by setting a hashtag.<br>This enables the pop-up to be tracked with analytic tools and thus the user behavior to be understood.", 'greyd_hub'), 
                'default'       => 'false', // 'true'/'false'
            ),
            'once' => array( 
                'type'          => 'checkbox', 
                'param_name'    => 'once', 
                'label'         => __("Show pop-up only once", 'greyd_hub'), 
                'description'   => __("If this function is active, the pop-up will be removed completely after closing.<br>Thus it cannot be triggered multiple times on the page.", 'greyd_hub').'<br><strong>'.__("After closing the browser, the pop-up is displayed once again.", 'greyd_hub').'</strong>', 
                'default'       => 'false', // 'true'/'false'
            ),
			'arialabel' => array(
				'type'          => 'textfield',
				'param_name'    => 'arialabel',
				'label'         => __("Aria Label", 'greyd_hub'),
				'description'   => __("Set an aria label for the pop-up.", 'greyd_hub'),
				'placeholder'   => __("Describe your pop-up", 'greyd_hub'),
				'default'       => '', // string
			),

        );

        $default_options = array(
            'trigger' => $trigger,
            'display' => $display,
            'rules' => $rules,
            'more' => $more,
        );
        
        $controls = array(
            'trigger' => '',
            'display' => '',
            'rules' => '',
            'more' => '',
        );
        
        foreach ($default_options as $slug => $options) {
            $inputs = "";
            foreach ($options as $control => $params) {
                if (!is_array($params)) continue;
                $type = ' data-param_type="'.$params['type'].'"';
                $value = isset($params['default']) ? $params['default'] : NULL;
                // debug($meta[$control]);
                $value = (isset($meta[$control])) ? (array)$meta[$control] : $value;
                switch ($params['type']) {
                    case 'switch': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_switch_setting( $params, $value ).'</div>'; break;
                    case 'radio_switch': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_rswitch_setting( $params, $value ).'</div>'; break;
                    case 'checkbox': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_checkbox_setting( $params, $value ).'</div>'; break;
                    case 'select': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_select_setting( $params, $value ).'</div>'; break;
                    case 'info': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_info_setting( $params, $value ).'</div>'; break;
					case 'textfield': $inputs .= '<div class="settings_input" '.$type.'>'.self::render_textfield_setting( $params, $value ).'</div>'; break;
                }
            }
            $controls[$slug] = $inputs;
        }

        // -------------------- Row: Trigger
        $rows[] = [
            "title" => __("Trigger", 'greyd_hub'),
            "desc" => __("When should the pop-up be triggered?", 'greyd_hub'),
            "content" => '<div class="input_section">'.$controls['trigger'].'</div>'
        ];
        // -------------------- Row: Appearance
        $rows[] = [
            "title" => __("Appearance", 'greyd_hub'),
            "desc" => __("Where should the pop-up be triggerable?", 'greyd_hub'),
            "content" => '<div class="input_section">'.$controls['display'].'</div>'
        ];
        // -------------------- Row: Rules
        $rows[] = [
            "title" => __("Rules", 'greyd_hub'),
            "desc" => __("Conditions that must be met for the pop-up to be displayed", 'greyd_hub'),
            "content" => '<div class="input_section">'.$controls['rules'].'</div>'
        ];
        // -------------------- Row: Extended
        $rows[] = [
            "title" => __("Extended", 'greyd_hub'),
            "desc" => __("How does the pop-up behave?", 'greyd_hub'),
            "content" => '<div class="input_section">'.$controls['more'].'</div>'
        ];
        
        // -------------------- Render
        echo '<input class="popup_settings_data" type="hidden" name="'.$name.'" value="" style="width:100%">';
        echo '<table class="greyd_table vertical big posttype_table">';
        foreach($rows as $row) {
            echo $this->render_table_row($row);
        }
        echo '</table>';
        
        // render hidden action
        echo '<input type="hidden" name="meta_action" value="wp_backend" />';

    }

    /**
     * Save post action.
     * 
     * @param int $post_id  The current Popup id.
     * @param WP_Post $post The saving Popup post.
     */
    public function save_post($post_id, $post) {
        if ($post->post_type != $this->post_type) return;
        if (!is_admin()) return;
        if (empty($_POST)) return;
        if ( !isset($_POST['meta_action']) || $_POST['meta_action'] !== 'wp_backend' ) return;
        
        // debug($_POST);
        // debug($_POST['popup_settings']);
        // debug(json_decode(stripslashes($_POST['popup_settings'])));
        // wp_die();

        if (isset($_POST['popup_settings']) && $_POST['popup_settings'] != "") {
            $this->save_metadata( $post_id, 'popup_settings', json_decode(stripslashes($_POST['popup_settings'])) );
        }
    }

    /**
     * Handle Postmeta saving.
     * 
     * @param int $post_id  The current Popup id.
     * @param string $name  The slug of the Postmeta to save.
     * @param object $value The Settings Object to save.
     */
    public function save_metadata($post_id, $name, $value) {
        if (metadata_exists('post', $post_id, $name)) {
            update_post_meta($post_id, $name, $value);
        } 
        else {
            add_post_meta($post_id, $name, $value);
        }
    }

    /*
    ====================================================================================
        Render Helper
    ====================================================================================
    */

    /**
     * Render single settings table row.
     * 
     * @param string[] atts
     *      @property string title
     *      @property string desc
     *      @property string content
     */
    public function render_table_row( $atts=[], $echo=true) {
        
        $title      = isset($atts["title"]) ? "<h3>".$atts["title"]."</h3>" : "";
        $desc       = isset($atts["desc"]) ? $atts["desc"] : "";
        $content    = isset($atts["content"]) ? $atts["content"] : "";
        
        echo '<tr>
                <th>
                    '.$title.'
                    '.$desc.'
                </th>
                <td>
                    '.$content.'
                </td>
            </tr>';

    }
    
    /**
     * Render Setting with Radio Switch (Button Group).
     * 
     * @param object $settings  The Settings Object.
     *      @property string type       Type of setting.
     *      @property string param_name Name of the Setting.
     *      @property string default    Default value.
     *      @property string head       Headline (Main Text).
     *      @property string label      Label (Secondary Text).
     *      @property string description Description (only for type = checkbox or select).
     *      @property string[] radio    Radio Switch options (only for type = radio_switch).
     *      @property object[] params   Additional Parameter for on/off settings (optional).
     *      @property string class      Extra css class (optional).
     * @param string $value     The Value.
     * @return string
     */
    public static function render_rswitch_setting( $settings, $value ) {
        $param = esc_attr($settings['param_name']);
        $val = isset($value['value']) ? $value['value'] : $settings['default'];
        $head = (isset($settings['head']) && !empty($settings['head'])) ? "<span class='head_label'>".esc_attr($settings['head'])."</span>" : "";
        $label = (isset($settings['label']) && !empty($settings['label'])) ? "<label>".esc_attr($settings['label'])."</label>" : "";
        $input = "<input type='hidden' name='$param' class='settings_input_value $param' value='$val' autocomplete='off'>";

        $radio = "<select autocomplete='off'>";
        foreach ($settings['radio'] as $title => $id)
            $radio .= "<option value='".$id."' ".selected($id, $val, false).">".$title."</option>";
        $radio .= "</select>";
        $radio = "<div class='settings_options'>".$radio."</div>";
    
        $class = (isset($settings['class']) && !empty($settings['class'])) ? $settings['class'] : "";
        $options = "";
        if (isset($settings['params'])) {
            $options = "<div class='settings_input_options ".$class."' style='display:none;' data-param_name='".$param."'>
                            ".self::render_setting_params($settings['params'], $value)."
                        </div>";
        }

        return "<div class='settings_input_switch'>".$head.$label.$radio."</div>".$input.$options;
    }

    /**
     * Render Setting with Checkbox (ios Switch).
     * @param object $settings  The Settings Object.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_switch_setting( $settings, $value ) {
        $param = esc_attr($settings['param_name']);
        $val = isset($value['value']) ? $value['value'] : $settings['default'];
        $head = (isset($settings['head']) && !empty($settings['head'])) ? "<span class='head_label'>".esc_attr($settings['head'])."</span>" : "";
        $label = (isset($settings['label']) && !empty($settings['label'])) ? "<label>".esc_attr($settings['label'])."</label>" : "";
        $input = "<input type='hidden' name='$param' class='settings_input_value $param' value='$val' autocomplete='off'>";
        
        $slide = "<div class='greyd_switch'><label>
                    <span>&nbsp;</span><span>
                    <input type='checkbox' ".($val != 'off' ? 'checked="checked"' : '')." autocomplete='off'>
                    <span>&nbsp;</span></span>
                </label></div>";

        $class = (isset($settings['class']) && !empty($settings['class'])) ? $settings['class'] : "";
        $options = "";
        if (isset($settings['params'])) {
            if (strpos($label, '%s') !== false) {
                $pname = $settings['params'][0]['param_name'];
                $poptions = isset($value['options']) ? (array)$value['options'] : array();
                $pval = isset($poptions[$pname]) ? $poptions[$pname] : $settings['params'][0]['default'];
                $pinput = self::render_settings_option($settings['params'][0], $pval, $pname);
                $label = str_replace('%s', $pinput, $label);
            }
            else {
                $options = "<div class='settings_input_options ".$class."' style='display:none;' data-param_name='".$param."'>
                                ".self::render_setting_params($settings['params'], $value)."
                            </div>";
            }
        }

        return "<div class='settings_input_switch'>".$head.$label.$slide."</div>".$input.$options;
    }

    /**
     * Render Setting Params shown when a setting is active.
     * @param object[] $params  Array of Additional Parameter.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_setting_params( $params, $value ) {
        $value = isset($value['options']) ? (array)$value['options'] : array();
        $options = "";
        foreach ($params as $param) {
            $name = esc_attr($param['param_name']);
            $val = isset($value[$name]) ? $value[$name] : $param['default'];
            $class = (isset($param['class']) && $param['class'] != "") ? $param['class'] : "";
            $options .= "<div class='settings_input_option ".$param['type']." ".$class."' data-param_name='".$name."'>";
            if (isset($param['label']) && $param['label'] != "") $options .= "<label class='head_label'>".esc_attr($param['label'])."</label>";
            
            $options .= self::render_settings_option($param, $val, $name);
            
            if (isset($param['description']) && $param['description'] != "") $options .= "<label class='description_label'>".$param['description']."</label>";
            $options .= "</div>";
        }
        return $options;
    }
    
    /**
     * Render Settings Additional Parameter activated with setting.
     * @param object $param     Parameter object (similar to Settings Object).
     * @param string $val       Value of Additional Parameter.
     * @param string $name      Name of the Additional Parameter.
     * @return string
     */
    public static function render_settings_option( $param, $val, $name ) {
        $return = "";
        if ($param['type'] == 'autotags') {
            $return .= "<input type='hidden' name='".$name."' class='settings_input_option_value' value='".$val."' data-tags='".json_encode($param['values'])."'  autocomplete='off'>";
        }
        else if ($param['type'] == 'checkbox') {
            $vals = explode(',', $val);
            $boxes = "";
            $final_val = array();
            foreach ($param['values'] as $title => $id) {
                $checked = "";
                if (in_array($id, $vals)) {
                    $checked = 'checked="checked"';
                    $final_val[] = $id;
                }
                $boxes .= "<label><input type='checkbox' name='".$id."' $checked  autocomplete='off'>".$title."</label>";
            }
            $final_val = implode(',', $final_val);
            $return .= "<input type='hidden' name='".$name."' class='settings_input_option_value' value='".$final_val."'  autocomplete='off'>".$boxes;
        }
        else if ($param['type'] == 'textfield') {
            $placeholder = (isset($param['placeholder']) && $param['placeholder'] != "") ? "placeholder='".esc_attr($param['placeholder'])."'" : "";
            $return .= "<input type='text' name='".$name."' class='settings_input_option_value' value='".$val."' ".$placeholder."  autocomplete='off'>";
        }
        else if ($param['type'] == 'numfield') {
            $placeholder    = (isset($param['placeholder']) && $param['placeholder'] != "") ? "placeholder='".esc_attr($param['placeholder'])."'" : "";
            $steps          = (isset($param['steps']) && $param['steps'] != "") ? "steps='".esc_attr($param['steps'])."'" : "";
            $max            = (isset($param['max']) && $param['max'] != "") ? "max='".esc_attr($param['max'])."'" : "";
            $min            = (isset($param['min']) && $param['min'] != "") ? "min='".esc_attr($param['min'])."'" : "";
            $return .= "<input type='number' name='".$name."' class='settings_input_option_value' value='".$val."' ".$placeholder." ".$steps." ".$min." ".$max."  autocomplete='off'>";
        }
        else if ($param['type'] == 'select') {
            $return .= "<select name='".$name."' class='settings_input_option_value' autocomplete='off'>";
            foreach ($param['options'] as $title => $id)
                $return .= "<option value='".$id."' ".selected($id, $val, false).">".$title."</option>";
            $return .= "</select>"; 
        }
        return $return;
    }

    /**
     * Render simple Checkbox Setting.
     * @param object $settings  The Settings Object.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_checkbox_setting( $settings, $value ) {
        $val = isset($value['value']) ? $value['value'] : $settings['default'];
        $input = self::render_checkbox_control( $settings, $val );
        $description = (isset($settings['description']) && !empty($settings['description'])) ? "<span class='description_label'>".$settings['description']."</span>" : "";
        return "<div class='settings_input_element'>".$input.$description."</div>";
    }

    /**
     * Checkbox Setting input field.
     * @param object $settings  The Settings Object.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_checkbox_control( $settings, $value ) {
        $param = esc_attr($settings['param_name']);
        $head = (isset($settings['head']) && !empty($settings['head'])) ? "<span class='head_label'>".esc_attr($settings['head'])."</span>" : "";
        $label = (isset($settings['label']) && !empty($settings['label'])) ? esc_attr($settings['label']) : "";
        $value = ($value == 'true') ? "checked='checked'" : '';

        return $head."<label><input type='checkbox' name='$param' class='settings_input_value $param' $value  autocomplete='off'>$label</label>";
    }

    /**
     * Render simple Select Setting.
     * @param object $settings  The Settings Object.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_select_setting( $settings, $value ) {
        $val = isset($value['value']) ? $value['value'] : $settings['default'];
        $description = (isset($settings['description']) && !empty($settings['description'])) ? "<span class='description_label'>".$settings['description']."</span>" : "";
        unset($settings['description']);
        $input = self::render_select_control( $settings, $val );
        return "<div class='settings_input_element'>".$input.$description."</div>";
    }

    /**
     * Select Setting input.
     * @param object $settings  The Settings Object.
     * @param string $value     The Value.
     * @return string
     */
    public static function render_select_control( $settings, $value ) {
        $param = esc_attr($settings['param_name']);
        $head = (isset($settings['head']) && !empty($settings['head'])) ? "<span class='head_label'>".esc_attr($settings['head'])."</span>" : "";
        $label = (isset($settings['label']) && !empty($settings['label'])) ? "<label>".esc_attr($settings['label'])."</label>" : "";
        $select = "<select name='$param' class='settings_input_value $param' data-option='$value' autocomplete='off'>";
        foreach ($settings['options'] as $title => $id)
            $select .= "<option value='".$id."' ".selected($id, $value, false).">".$title."</option>";
        $select .= "</select>"; 
        $description = (isset($settings['description']) && !empty($settings['description'])) ? "<i class='descr'>".esc_attr($settings['description'])."</i>" : "";

        return $head.$label.$select.$description;
    }
    
    /**
     * Render Info Setting.
     * @param object $settings  The Settings Object.
     * @param string $value     The Value (empty).
     * @return string
     */
    public static function render_info_setting( $settings, $value ) {
        // debug($settings);
        $head = (isset($settings['head']) && !empty($settings['head'])) ? "<span class='head_label'>".esc_attr($settings['head'])."</span>" : "";
        $text  = isset($settings['label']) ? $settings['label'] : '';
        $return = "<div class='settings_input_switch'>".$head."<div class='info_toggle'><span class='dashicons dashicons-info'></div></div>";
        $return .= "<div class='settings_input_options' style='display:none'>".$text."</div>";
        return $return;
    }

	/**
	 * Render Textfield Setting.
	 * @param object $settings  The Settings Object.
	 * @param string $value     The Value.
	 * @return string
	 */
	public static function render_textfield_setting( $settings, $value ) {
		$val = isset($value['value']) ? $value['value'] : $settings['default'];
		$param = esc_attr($settings['param_name']);
		$placeholder = (isset($settings['placeholder']) && $settings['placeholder'] != "") ? "placeholder='".esc_attr($settings['placeholder'])."'" : "";
		$description = (isset($settings['description']) && !empty($settings['description'])) ? "<span class='description_label'>".$settings['description']."</span>" : "";
		$label = (isset($settings['label']) && !empty($settings['label'])) ? "<label for='$param'>".esc_attr($settings['label'])."</label>" : "";
		return "<div class='settings_input_element'>".$label."<input type='text' name='$param' class='settings_input_value $param' value='".$val."' ".$placeholder."  autocomplete='off' style='margin-bottom:5px'>".$description."</div>";
    }

    /*
    ====================================================================================
        Wizard
    ====================================================================================
    */

    /**
     * Add Popup Wizard
     */
    public function add_wizard() {

        $screen = get_current_screen();
        if ($screen->post_type == $this->post_type) {
            if ($screen->base == 'edit') {

                add_action( 'admin_enqueue_scripts', array($this, 'add_script'), 99 );
                add_action( 'admin_notices', array($this, 'render_wizard'), 999 );

            }
        }
    }

    /**
     * Add Wizard script
     */
    public function add_script() {

        $plist = array();
        $posts = Helper::get_all_posts($this->post_type);
        foreach($posts as $post) {
            $plist[] = array( 'id' => $post->id, 'slug' => $post->slug, 'title' => $post->title );
        }
        wp_add_inline_script( $this->config->plugin_name."_popups_js", 'var popups = '.json_encode($plist).';' );

    }

    /**
     * Render Popup Wizard
     */
    public function render_wizard() {

        $asset_url = plugin_dir_url(__FILE__)."assets/wizard";
        
        $popup_presets = array();
        $active_preset = "active";
        foreach ($this->default_contents() as $id => $content) {
            $big = ($id != "empty") ? 'big' : '';
            $popup_presets[] = '<span id="'.$id.'" class="content_option '.$active_preset.'"><img class="option_icon '.$big.'" src="'.$asset_url.'/'.$content['icon'].'"><p>'.$content['title'].'</p></span>';
            $active_preset = "";
        }

        $popups = array();
        $posts = Helper::get_all_posts($this->post_type);
        foreach($posts as $post) {
            $plist[] = array( 'id' => $post->id, 'slug' => $post->slug, 'title' => $post->title );
            $language = "";
            if (is_array($post->lang) && $post->lang['display_name'] != "") 
                $language = ' ('.$post->lang['language_code'].')';
            $popups[] = '<option value="'.$post->id.'">'.$post->title.$language.'</option>';
        }

        echo '
        <div id="greyd-wizard" class="wizard popup_wizard">
            <div class="wizard_box big">
                <div class="wizard_head">
                    <img class="wizard_icon" src="'.$asset_url.'/logo_light.svg">
                    <span class="close_wizard dashicons dashicons-no-alt"></span>
                </div>
                <div class="wizard_content">
                    <div data-slide="0-error">
                        <h2>'.__('Oops!', 'greyd_hub').'</h2>
                        <div class="greyd_info_box orange" style="width: 100%; box-sizing: border-box;">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <p>'.__("There was a problem:", 'greyd_hub').'</p><br>
                                <p class="error_msg"></p>
                            </div>
                        </div>
                    </div>
                    <div data-slide="1">
                        <h2>'.__("Create new pop-up", 'greyd_hub').'</h2>
                        
                        <div class="row flex">
                            <div class="element grow">
                                <div class="label">'.__("Name", 'greyd_hub').'</div>
                                <input id="name" name="name" type="text" value="" placeholder="'.__("Enter here", 'greyd_hub').'" >
                                <input id="slug" name="slug" type="hidden" value="" >
                                <input id="ttype" name="ttype" type="hidden" value="popup" >
                            </div>
                        </div>
                        <div class="row flex">
                            <div class="element grow name_double" style="display:none">
                                <div class="greyd_info_box warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <div>
                                        <div style="margin-bottom:10px"><strong>'.__("Attention:", 'greyd_hub').'</strong></div>
                                        <div style="margin-bottom:10px">'.sprintf(__("The selected pop-up already exists (name: '%s'). To recreate the pop-up, you must first trash the existing one. You can also easily edit it or reset it to the default.", 'greyd_hub'), '<span class="double_name">Templatename</span>').'</div>
                                        <div style="margin-bottom:10px"><a class="double_reset" style="cursor:pointer;text-decoration:underline">'.__("Reset to default", 'greyd_hub').'</a> | <a class="double_edit" href="#">'._x("Edit pop-up", 'small', 'greyd_hub').'</a></div>
                                    </div>
                                </div>
                            </div>
                            <div class="element grow name_ok">
                                <div class="label">'.__("Content", 'greyd_hub').'</div>
                                <div class="content_options">
                                    '.implode('', $popup_presets).'
                                    <span id="copy" class="content_option"><img class="option_icon" src="'.$asset_url.'/copy.png"><p>'._x("Copy", 'small', 'greyd_hub').'</p>
                                        <select id="copy_popup" class="copy" name="copy_popup">
                                            <option value="0">'._x("Choose pop-up", 'small', 'greyd_hub').'</option>
                                            '.implode('', $popups).'
                                        </select>
                                    </span>
                                </div>
                            </div>
                        </div>

                        
                    </div>
                    <div data-slide="11">
                        <h2>'.__("Congratulations!", 'greyd_hub').'</h2>
                        <div class="install_txt">'.__('The pop-up "%s" was created successfully.', 'greyd_hub').'</div>
                        <div class="success_mark"><div class="checkmark"></div></div>
                    </div>
                </div>
                <div class="wizard_foot">
                    <div data-slide="0-error">
                        <div class="flex">
                            <span class="back button button-secondary">'.__("Back", 'greyd_hub').'</span>
                            <span class="close_wizard reload button button-primary">'.__("Close", 'greyd_hub').'</span>
                        </div>
                    </div>
                    <div data-slide="1">
                        <div class="flex">
                            <span class="close_wizard button button-secondary">'.__("Back", 'greyd_hub').'</span>
                            <span class="create button button-primary">'._x("Create pop-up", 'small', 'greyd_hub').'</span>
                        </div>
                    </div>
                    <div data-slide="11">
                        <div class="flex">
                            <span class="close_wizard reload button button-secondary">'.__("Close", 'greyd_hub').'</span>
                            <a class="finish_wizard button button-primary" href="">'._x("Edit pop-up", 'small', 'greyd_hub').'</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }

	// make popup block content
	public function popup_default_content_blocks ( $atts=[] ) {
		return '<!-- wp:greyd/popup-close {"align":"","greydClass":"gs_pDGEnF","greydStyles":{"width":"40px","fontSize":"4px","hover":{}},"className":""} -->
		<div class="wp-block-greyd-popup-close"><div class="popup_close_button gs_pDGEnF" onclick="popups.close(this)" aria-label="' . __( 'Close pop-up', 'greyd_hub' ) . '"><div class="close_icon"><span></span><span></span></div></div></div>
		<!-- /wp:greyd/popup-close -->

		<!-- wp:heading -->
		<h2></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"alignright"} -->
		<p class="alignright">'.( isset($atts['text']) ? $atts['text'] : __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit.", 'greyd_hub') ).'</p>
		<!-- /wp:paragraph -->

		<!-- wp:spacer -->
		<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:greyd/buttons -->
		<div class="wp-block-greyd-buttons"><!-- wp:greyd/button -->
		<a role="trigger" class="button"></a>
		<!-- /wp:greyd/button --></div>
		<!-- /wp:greyd/buttons -->';
	}

    /**
     * Get collection of default Popup Setups.
     * 
     * @return object[]
     *      @property string icon   An Icon to show in the Wizard.
     *      @property string type   Posttype: 'greyd_popup'
     *      @property string slug   Slug/Name of the Popup.
     *      @property string title  Title.
     *      @property string content Block Content string.
     *      @property object meta
     *          @property object popup_settings The Popup settings Object.
     *          @property object popup_design   The Popup design Object.
     */
    public function default_contents() {
        $popup_design_default = array(
        );
        $popup_settings_default = array(
        );

        // return popup presets
        return array(
            'empty' => array(
                'icon'         => 'new_select.png',
                'type'         => 'greyd_popup',
                'slug'         => __("empty", 'greyd_hub'),
                'title'        => __("Empty", 'greyd_hub'),
                'content'      =>   '<!-- wp:columns {"className":"","row":{"type":"row_xxxl"}} -->
                                    <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                    <div class="wp-block-column col-12"></div>
                                    <!-- /wp:column --></div>
                                    <!-- /wp:columns -->',
                'meta' => array(
                    'popup_settings' => (object)$popup_settings_default,
                    'popup_design' => (object)array( ),
                )
            ),
            'classic' => array(
                'icon'         => 'popup_classic.png',
                'type'         => 'greyd_popup',
                'slug'         => __('classic', 'greyd_hub'),
                'title'        => __("Classic", 'greyd_hub'),
                'content'      => $this->popup_default_content_blocks( [ 'head' => __("Classic", 'greyd_hub') ] ),
                'meta' => array(
                    'popup_settings' => (object)$popup_settings_default,
                    'popup_design' => (object)$popup_design_default,
                )
            ),
            'slidein' => array(
                'icon'         => 'popup_slidein.png',
                'type'         => 'greyd_popup',
                'slug'         => __('slidein', 'greyd_hub'),
                'title'        => __("Slide in", 'greyd_hub'),
                'content'      => $this->popup_default_content_blocks( [
                    'head' => __("Slide in", 'greyd_hub'),
                    'close_size' => '20',
                    'h_tag' => 'h5',
                    'button' => false,
                ] ),
                'meta' => array(
                    'popup_settings' => (object)array_merge(
                        $popup_settings_default,
                        array(
                            'on_scroll' => (object)array( 'value' => 'on', 'options' => (object)array( 'height' => '50%' ) ),
                            'on_idle'   => (object)array( 'value' => 'off' ),
                            'on_exit'   => (object)array( 'value' => 'off' ),
                            'highest'   => (object)array( 'value' => 'false' ),
                            'hashtag'   => (object)array( 'value' => 'false' ),
                        )
                    ),
                    'popup_design' => (object)array_merge(
                        $popup_design_default,
                        array(
                            'position' => 'br',
                            'width' => 'custom',
                            'width_custom' => '500px',
                            'padding_tb' => '15',
                            'padding_lr' => '5',
                            'margin_tb' => '20',
                            'margin_lr' => '20',
                            'mobile_width' => 'custom',
                            'mobile_width_custom' => '90%',
                            'mobile_padding_tb' => '10',
                            'mobile_padding_lr' => '5',
                            'mobile_margin_tb' => '10',
                            'mobile_margin_lr' => '10',
                            'border_radius' => '3',
                            'anim_type' => 'slide_right',
                            'overlay_enable' => 'false',
                        )
                    ),
                )
            ),
            'full' => array(
                'icon'         => 'popup_full.png',
                'type'         => 'greyd_popup',
                'slug'         => __('full', 'greyd_hub'),
                'title'        => __("Fullscreen", 'greyd_hub'),
                'content'      => $this->popup_default_content_blocks( [
                    'head' => __("Fullscreen", 'greyd_hub'),
                    'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                    'close_size' => '50',
                    'close_width' => '4',
                    'h_tag' => 'h2',
                    'empty' => '50',
                    'button_text' => _x("Close pop-up", 'small', 'greyd_hub')
                ] ),
                'meta' => array(
                    'popup_settings' => (object)$popup_settings_default,
                    'popup_design' => (object)array_merge(
                        $popup_design_default,
                        array(
                            'width' => '100',
                            'height' => '100',
                            'border_radius' => '10',
                            'padding_tb' => '100',
                            'padding_lr' => '100',
                            'margin_tb' => '10',
                            'margin_lr' => '10',
                            'mobile_width' => '100',
                            'mobile_height' => '100',
                            'mobile_padding_tb' => '50',
                            'mobile_padding_lr' => '50',
                            'mobile_margin_tb' => '5',
                            'mobile_margin_lr' => '5',
                            'anim_type' => 'fade',
                        )
                    )
                )
            ),
            'announce' => array(
                'icon'         => 'popup_announce.png',
                'type'         => 'greyd_popup',
                'slug'         => __('announce', 'greyd_hub'),
                'title'        => __('Announcement', 'greyd_hub'),
                'content'      => $this->popup_default_content_blocks( [
                    'head' => __('Announcement', 'greyd_hub'),
                    'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                ] ),
                'meta' => array(
                    'popup_settings' => (object)array_merge(
                        $popup_settings_default,
                        array(
                            'on_init'   => (object)array( 'value' => 'on', 'options' => (object)array( 'time' => '5' ) ),
                            'on_idle'   => (object)array( 'value' => 'off' ),
                            'on_exit'   => (object)array( 'value' => 'off' ),
                            'highest'   => (object)array( 'value' => 'false' ),
                            'hashtag'   => (object)array( 'value' => 'false' ),
                        )
                    ),
                    'popup_design' => (object)array_merge(
                        $popup_design_default,
                        array(
                            'position' => 'bc',
                            'width' => '100',
                            'padding_tb' => '20',
                            'padding_lr' => '10',
                            'margin_tb' => '20',
                            'margin_lr' => '20',
                            'mobile_enable' => 'false',
                            'mobile_width' => '100',
                            'mobile_padding_tb' => '6',
                            'mobile_padding_lr' => '12',
                            'mobile_margin_tb' => '10',
                            'mobile_margin_lr' => '10',
                            'anim_type' => 'slide_bottom',
                            'overlay_enable' => 'false',
                        )
                    )
                )
            ),
            'sidebar' => array(
                'icon'         => 'popup_sidebar.png',
                'type'         => 'greyd_popup',
                'slug'         => __('sidebar', 'greyd_hub'),
                'title'        => __('Sidebar', 'greyd_hub'),
                'content'      => $this->popup_default_content_blocks( [
                    'head' => __('Sidebar', 'greyd_hub'),
                    'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                    'close_size' => '40',
                    'close_width' => '3',
                    'empty' => '100',
                ] ),
                'meta' => array(
                    'popup_settings' => (object)array_merge(
                        $popup_settings_default,
                        array(
                            'on_init'   => (object)array( 'value' => 'on', 'options' => (object)array( 'time' => '5' ) ),
                            'on_idle'   => (object)array( 'value' => 'off' ),
                            'on_exit'   => (object)array( 'value' => 'off' ),
                            'highest'   => (object)array( 'value' => 'false' ),
                            'hashtag'   => (object)array( 'value' => 'false' ),
                        )
                    ),
                    'popup_design' => (object)array_merge(
                        $popup_design_default,
                        array(
                            'position' => 'mr',
                            'width' => 'custom',
                            'width_custom' => '25%',
                            'height' => '100',
                            'align' => 'top',
                            'border_radius' => '0',
                            'padding_tb' => '100',
                            'padding_lr' => '10',
                            'margin_tb' => '0',
                            'margin_lr' => '0',
                            'mobile_enable' => 'false',
                            'mobile_width' => 'custom',
                            'mobile_width_custom' => '50%',
                            'mobile_height' => '100',
                            'mobile_align' => 'top',
                            'mobile_padding_tb' => '30',
                            'mobile_padding_lr' => '5',
                            'mobile_margin_tb' => '0',
                            'mobile_margin_lr' => '0',
                            'anim_type' => 'slide_right',
                        )
                    )
                )
            ),
            // 'slideup' => array(
            //     'icon'         => 'popup_slideup.png',
            //     'type'         => 'greyd_popup',
            //     'slug'         => __('slideup', 'greyd_hub'),
            //     'title'        => __('Slide Up', 'greyd_hub'),
            //     'content'      => $this->popup_default_content_blocks( [ 'head' => __('Slide Up', 'greyd_hub') ] ),
            //     'meta' => array(
            //         'popup_settings' => (object)array_merge(
            //             $popup_settings_default,
            //             array(
            //                 'xxx' => 'xxx',
            //             )
            //         ),
            //         'popup_design' => (object)array_merge(
            //             $popup_design_default,
            //             array(
            //                 'position' => 'bc',
            //                 'width' => 'custom',
            //                 'width_custom' => '50%',
            //                 'padding_tb' => '20',
            //                 'padding_lr' => '10',
            //                 'margin_tb' => '20',
            //                 'margin_lr' => '20',
            //                 'mobile_enable' => 'false',
            //                 'mobile_width' => 'custom',
            //                 'mobile_width_custom' => '75%',
            //                 'mobile_padding_tb' => '6',
            //                 'mobile_padding_lr' => '12',
            //                 'mobile_margin_tb' => '10',
            //                 'mobile_margin_lr' => '10',
            //                 'anim_type' => 'slide_bottom',
            //                 'overlay_enable' => 'false',
            //             )
            //         )
            //     )
            // ),
            
        );
    }

}