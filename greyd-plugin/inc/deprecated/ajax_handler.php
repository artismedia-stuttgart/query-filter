<?php
/*
    Comments: Plugin File for install wizard
*/
if( ! defined( 'ABSPATH' ) ) exit;

new ajax_handler($config);
class ajax_handler {

    private $config;
    private $path;
    
    // constants
    const DEBUG = true;

    public function __construct($config) {
        // set config
        $this->config = (object) $config;
        $this->path = $this->config->plugin_path.'/inc/deprecated/install-wizard/assets/';

        // content and wordings
        add_action( 'init', array($this, 'init_content_mode') );
        // add_action( 'after_setup_theme', array($this, 'init_default_contents'), 3 );
        
        // ajax
        add_action( 'wp_ajax_nopriv_greyd_ajax', array( $this, 'ajax' ) );
        add_action( 'wp_ajax_greyd_ajax', array($this, 'ajax') );
    }
    
    
    /*
    =======================================================================
        AJAX HANDLER
    =======================================================================
    */
    public function ajax() {
        if (check_ajax_referer('install')) {
            
            // early exit
            if (!isset($_POST['mode'])) wp_die("error::The POST variable 'mode' needs to be set.");
            debug($_POST['mode']);
            
            // start
            //debug($_POST);
            $mode = $_POST['mode'];
            if (self::DEBUG)
                echo "\r\n\r\n"."------------- debug start -------------"."\r\n\r\n"."MODE: ".$mode."\r\n";
            
            /*
             *  Init Setup
             *  
             *  called after switch theme 
             */
            if ($mode === 'check_init_setup') {
                
                // if less than 15 theme mods are set
                // it's highly unlikely that this is a customized greyd installation
                $theme_mods = get_theme_mods();
                if (!$theme_mods || count($theme_mods) < 15) {
                    // setup basic design
                    $result = $this->set_design(false);
                    if (!$result) $this->finish("error::Unable to setup basic theme designs. See browser console for details.");
                }

                /**
                 * Check if this is the first time activating the SUITE.
                 * The easiest way to do this, is to find out if there are any posts of the post_type 'dynamic_templates'
                 */
                $firstrun = empty( get_posts( array(
                    'posts_per_page' => 1,
                    'post_type' => 'dynamic_template',
                    'fields' => 'ids'
                ) ) );
                
                if ( $firstrun ) {
                    self::update_content_mode();
                    $this->basic_installation();
                } else { 
                    $this->finish('exit::not first run.');
                }
            }
                
            /*
             *  Install Basics
             *  
             *  called after confirmation when not first run
             */
            else if ($mode === 'install_basics') {
                self::update_content_mode();
                $this->basic_installation();
            }
            
            // from this point on, we need post-data ...
            $post_data = isset($_POST['data']) ? $_POST['data'] : null;
            if ( !$post_data ) {
                $post_data = isset($_FILES['data']) ? $_FILES['data'] : $post_data;
                if ( !$post_data ) {
                    $this->finish("error::".__("No data found", "greyd_hub"));
                }
            }
                
            /*
             *  Install Basics without wizard
             *  
             *  called from hub
             */
            if ($mode === 'install_basics_silent') {
                $blog_id = $post_data;
                $theme = wp_get_theme();
                if (is_multisite()) switch_to_blog($blog_id);
                    self::update_content_mode();
                    update_option('template', $theme->template);
                    update_option('stylesheet', $theme->stylesheet);
                    update_option('current_theme', $theme->get( 'Name' ));
					if ( \Greyd\Helper::is_greyd_classic() ) {
						if (self::$content_mode == 'blocks') {
							update_option('active_plugins', array( "greyd-plugin/init.php" ));
						} else {
							update_option('active_plugins', array( "greyd-plugin/init.php", "js_composer/js_composer.php" ));
						}
					}
					else {
						update_option('active_plugins', array( "greyd-plugin/init.php" ));
					}
                    update_option('fresh_site', "1");
                    update_option('greyd_installed', "0");
                    $this->basic_installation();
                if (is_multisite()) restore_current_blog();
            }
            
            /*
             *  Install mods
             *  
             *  called after finishing the design-wizard
             */
            else if ($mode === 'install_mods') {
                if (self::DEBUG) echo "\r\n\r\n"."CREATE & INSTALL NEW THEME MODS";

                $datasrc = array(
                    'basic' => $this->get_mod_file('general'),
                    'font' => $this->get_mod_file('font'),
                    'color' => $this->get_mod_file('colors'),
                    'box' => $this->get_mod_file('box'),
                    'menu' => $this->get_mod_file('menu'),
                );
                // debug($datasrc);
                foreach ( $datasrc as $data ) {
                    if (!$data) $this->finish("error::Unable to get design source files. See browser console for details.");
                }

                $basic = $font = $color = $box = $menu = null;
                foreach((array) $post_data as $data) {
                    if ($data['name'] == 'font') {
                        if ($data['value'] == 0) $font = $datasrc['font']['slab'];
                        if ($data['value'] == 1) $font = $datasrc['font']['sans'];
                        if ($data['value'] == 2) $font = $datasrc['font']['serif'];
                    }
                    if ($data['name'] == 'color') {
                        if ($data['value'] == 0) $color = $datasrc['color']['red'];
                        if ($data['value'] == 1) $color = $datasrc['color']['blue'];
                        if ($data['value'] == 2) $color = $datasrc['color']['reduced'];
                    }
                    if ($data['name'] == 'box') {
                        if ($data['value'] == 0) $box = $datasrc['box']['hard'];
                        if ($data['value'] == 1) $box = $datasrc['box']['rounded'];
                        if ($data['value'] == 2) $box = $datasrc['box']['round'];
                    }
                    if ($data['name'] == 'menu') {
                        if ($data['value'] == 0) $menu = $datasrc['menu']['left'];
                        if ($data['value'] == 1) $menu = $datasrc['menu']['center'];
                        if ($data['value'] == 2) $menu = $datasrc['menu']['right'];
                    }
                }
                $basic  = $this->get_mod_values($datasrc['basic']);
                $font   = $this->get_mod_values($font);
                $color  = $this->get_mod_values($color);
                $box    = $this->get_mod_values($box);
                $menu   = $this->get_mod_values($menu);
                $final  = array_merge($basic, $font, $color, $box, $menu);

                foreach($final as $key => $value) {
                    set_theme_mod($key, $value);
                }

                $this->finish("success::thememods installed.");
            }
            
            /*
             *  Install setup
             *  
             *  called via wizard basic setup
             */
            else if ($mode === 'install_setup') {
                if (self::DEBUG) echo "\r\n\r\n"."OPTIONAL SETUP";

                    // debug($post_data);
                    $template   = $post_data['template'];
                    $plugin     = $post_data['plugin'];

                    $is_webshop = class_exists('woocommerce');
                    
                    foreach($plugin as $data) {
                        // debug($data);
                        $result = "";
                        $name   = isset($data['name']) ? $data['name'] : '';
                        $val    = isset($data['value']) ? $data['value'] : '';
                        if (empty($name)) continue;
                        
                        if ($name == 'plugin_forms') {
                            if ($val == 'checked') $result = $this->set_plugin('greyd_tp_forms', 'https://update.greyd.io/' . ( defined('GREYD_BRANCH') ? constant('GREYD_BRANCH') : 'public' ) . '/plugins/greyd_forms/greyd_tp_forms.zip');
                            else $this->unset_plugin('greyd_tp_forms');
                        }
                        // if ($name == 'plugin_tinymce') {
                        //     if ($val == 'checked') $result = $this->set_plugin('tinymce-advanced');
                        //     else $this->unset_plugin('tinymce-advanced');
                        // }
                        if ($name == 'plugin_autoptimize') {
                            if ($val == 'checked') $result = $this->set_plugin('autoptimize');
                            else $this->unset_plugin('autoptimize');
                        }
                        if ($name == 'plugin_hidelogin') {
                            if ($val == 'checked') $result = $this->set_plugin('wps-hide-login');
                            else $this->unset_plugin('wps-hide-login');
                        }
                        if ($name == 'plugin_yoast') {
                            if ($val == 'checked') $result = $this->set_plugin('wordpress-seo');
                            else $this->unset_plugin('wordpress-seo');
                        }
                        if ($name == 'plugin_mediareplace') {
                            if ($val == 'checked') $result = $this->set_plugin('enable-media-replace');
                            else $this->unset_plugin('enable-media-replace');
                        }
                        if ($name == 'plugin_woocommerce') {
                            if ($val == 'checked') {
                                $result = $this->set_plugin('woocommerce');
                                $is_webshop = true;
                            }
                            // else $this->unset_plugin('woocommerce');
                        }
                        if ($name == 'plugin_woocommerce-germanized') {
                            if ($val == 'checked') $result = $this->set_plugin('woocommerce-germanized');
                            else $this->unset_plugin('woocommerce-germanized');
                        }
                        // if ($name == 'plugin_borlabs') {
                        //     if ($val == 'checked') $result = $this->set_plugin('borlabs-cookie');
                        //     else $this->unset_plugin('borlabs-cookie');
                        // }
                        // if ($name == 'plugin_wpml') {
                        //     if ($val == 'checked') $result = $this->set_plugin('sitepress-multilingual-cms');
                        //     else $this->unset_plugin('sitepress-multilingual-cms');
                        // }
                    }
                        
                    if ( $is_webshop ) {

                        self::update_content_mode();
                        
                        // setup attachements
                        if ( $attachments = $this->set_attachments('-product') ) {
                            $this->contents['media'] = $attachments;
                        } else {
                            $result = 'error::unable to create media files.';
                        }
                        
                        // setup products
                        if ( $products = $this->set_posts('products') ) {
                            $this->contents['products'] = $products;
                        } else {
                            $result = 'error::unable to create products.';
                        }
                    }
                    // echo "error::unable to install plugins!";
                    // echo "error::unable to activate plugins!";
                    if (is_string($result) && strpos($result, "error::") === 0) $this->finish($result);

                    echo "success::setup, templates and plugins installed.";
            }
            
            /*
             *  Install Template
             *  
             *  called via dynamic wizard
             */
            else if ($mode === 'install_template') {
                if (self::DEBUG) echo "\r\n\r\n"."INSTALL NEW TEMPLATE";

                self::update_content_mode();

                // debug($post_data);
                if (isset(self::$default_contents['templates'][$post_data['name']]))
                    $template = self::$default_contents['templates'][$post_data['name']];
                else {
                    foreach (self::$default_contents['templates'] as $content) {
                        if ($content['slug'] == $post_data['name']) {
                            $template = $content;
                            break;
                        }
                    }
                }
                // debug($template);
                if ($template) {
                    $slug = $template['slug'];
                    $result = $this->create_post($template);
                }
                else {
                    $slug = str_replace('template_', '', $post_data['name']);
                    $result = $this->create_post(array(
                        'slug' => $slug,
                        'title' => $slug,
                        'type' => 'dynamic_template',
                        'template_type' => 'dynamic',
                        'state' => 'publish',
                        'content' => self::$wordings['default_content'],
                    ));
                }
                if (!$result) 
                    $this->finish("error::unable to create template: ".$slug);
                else {
                    if (strpos($result, 'exists::') === 0) 
                        $this->finish("info::Template <strong>".$slug."</strong> (id ".str_replace('exists::', '', $result).") already exists!");
                    else 
                        $this->finish("success::Template <strong>".$slug."</strong> (id ".$result.") created!");
                }
            }
            
            /*
             *  Create Template
             *  
             *  called via dynamic wizard
             */
            else if ($mode === 'create_template') {
                if (self::DEBUG) echo "\r\n\r\n"."CREATE NEW TEMPLATE";

                self::update_content_mode();
                
                if (empty(self::$default_contents['templates'])) {
                    self::update_content_mode(); 
                }

                if ($post_data['slug'] == "") $post_data['slug'] = sanitize_title($post_data['name']);
                if ($post_data['content'] == "empty") $post_data['content'] = self::$wordings['empty_content'];
                else if ($post_data['content'] == "default") {
                    $slug = $post_data['slug'];
                    $slgs = explode('-', $post_data['slug']);
                    if ($slgs[0] == 'single' || $slgs[0] == 'archives' || $slgs[0] == 'search') {
                        $slug = $slgs[0];
                    }
                    foreach (self::$default_contents['templates'] as $content) {
                        if ($content['slug'] == $slug) {
                            $post_data['content'] = $content['content'];
                            break;
                        }
                    }
                    if ($post_data['content'] == "default") {
                        $post_data['content'] = sprintf(self::$wordings['default_content'], $post_data['name']);
                    }
                }
                else if ($post_data['content'] == "copy") {
                    if (isset($post_data['copy'])) {
                        $post_data['content'] = get_post_field('post_content', $post_data['copy']);
                        if ($post_data['content'] == "") {
                            $post_data['content'] = self::$wordings['empty_content'];
                        }
                    }
                }
                else if ($post_data['content'] == "import") {
                    if (isset($post_data['import'])) {
                        $post_data['content'] = rawurldecode($post_data['import']);
                        if ($post_data['content'] == "") {
                            $post_data['content'] = self::$wordings['empty_content'];
                        }
                    }
                }
                else $this->finish("error::unable to create template data: ".$post_data['slug']);

                $result = $this->create_post(array(
                    'slug' => $post_data['slug'],
                    'title' => $post_data['name'],
                    'type' => 'dynamic_template',
                    'ttype' => $post_data['ttype'],
                    'state' => 'publish',
                    'content' => $post_data['content'],
                    'force' => isset($post_data['force']) ? $post_data['force'] : false
                ));
                if (!$result) 
                    $this->finish("error::unable to create template: ".$post_data['slug']);
                else {
                    if (strpos($result, 'exists::') === 0) 
                        $this->finish("info::Template <strong>".$post_data['slug']."</strong> (id ".str_replace('exists::', '', $result).") already exists!");
                    else 
                        $this->finish("success::Template <strong>".$post_data['slug']."</strong> (id ".$result.") created!");
                }
            }
            
            /*
             *  Create Pop-up
             *  
             *  called via popup wizard
             */
            else if ($mode === 'create_popup') {
                if (self::DEBUG) echo "\r\n\r\n"."CREATE NEW POPUP";

                self::update_content_mode();
                
                if ($post_data['slug'] == "") $post_data['slug'] = sanitize_title($post_data['name']);
                if ($post_data['content'] == "copy") {
                    if (isset($post_data['copy'])) {
                        $post_data['content'] = get_post_field('post_content', $post_data['copy']);
                        $post_data['meta'] = array(
                            'popup_settings' => get_post_meta($post_data['copy'], 'popup_settings', true),
                            'popup_design' => get_post_meta($post_data['copy'], 'popup_design', true),
                        );
                    }
                }
                else {
                    $found = false;
                    foreach (self::$default_contents['popups'] as $id => $content) {
                        if ($post_data['content'] == $id) {
                            $post_data['content'] = $content['content'];
                            $post_data['meta'] = $content['meta'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) $this->finish("error::unable to create popup data: ".$post_data['slug']);
                }
                if ($post_data['content'] == "") {
                    $post_data['content'] = self::$default_contents['popups']['empty']['content'];
                    $post_data['meta'] = self::$default_contents['popups']['empty']['meta'];
                }

                // echo "\r\n\r\n";
                // debug($post_data);
                // echo "\r\n\r\n";

                $result = $this->create_post(array(
                    'slug' => $post_data['slug'],
                    'title' => $post_data['name'],
                    'type' => 'greyd_popup',
                    'state' => 'draft',
                    'content' => $post_data['content'],
                    'meta' => $post_data['meta'],
                    'force' => isset($post_data['force']) ? $post_data['force'] : false
                ));
                if (!$result) 
                    $this->finish("error::unable to create popup: ".$post_data['slug']);
                else {
                    if (strpos($result, 'exists::') === 0) 
                        $this->finish("info::Pop-up <strong>".$post_data['slug']."</strong> (id ".str_replace('exists::', '', $result).") already exists!");
                    else 
                        $this->finish("success::Pop-up <strong>".$post_data['slug']."</strong> (id ".$result.") created!");
                }
            }

            /*
             *  Modify Pop-up
             *  @deprecated moved to popups.php
             *  still used by deprecated setup
             *  
             *  called from popups edit backend
             */
            else if ($mode === 'mod_popup') {
                if (self::DEBUG) echo "\r\n\r\n"."MODIFY POPUP";
                
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
                            if (self::DEBUG) echo sprintf("\r\n"."* popup „%s“ successfully updated.", $post_data['id']);
                            $this->finish("success::Pop-up <strong>".$post_data['id']."</strong> modified with priority value ".$post_data['value']."!");
                        }
                        else {
                            if (self::DEBUG) echo sprintf("\r\n"."* error updating popup „%s“:", $post_data['id']);
                            echo "\r\n".$post_id->get_error_message();
                        } 
                    }

                }
                $this->finish("error::unable to modify popup: ".$post_data['id']);
            }

             /*
             *  Create and edit Posttypes
             *  
             *  called via posttype wizard
             */
            else if ($mode === 'create_posttype') {
                if (self::DEBUG) echo "\r\n\r\n"."CREATE NEW POSTTYPE";
                
                if ($post_data['slug'] == "") $post_data['slug'] = sanitize_title($post_data['name']);
                
                echo "\r\n\r\n";
                debug($post_data);
                echo "\r\n\r\n";

                $meta = get_post_meta($post_data['id']);

                echo "\r\n\r\n";
                debug($meta);
                echo "\r\n\r\n";

                $result = $this->create_post(array(
                    'slug' => Greyd\Posttypes\Posttype_Helper::get_nice_slug( $post_data['slug'] ),
                    'title' => $post_data['name'],
                    'type' => 'tp_posttypes',
                    'state' => 'publish'
                ));

                if (!$result) 
                    $this->finish("error::unable to create post type: ".$post_data['slug']);
                else {
                    if (strpos($result, 'exists::') === 0) 
                        $this->finish("info::Posttype <strong>".$post_data['slug']."</strong> (id ".str_replace('exists::', '', $result).") already exists!");
                    else 
                        $this->finish("success::Posttype <strong>".$post_data['slug']."</strong> (id ".$result.") created!");
                }
            }

            
            /*
             *  Get Template
             */
            else if ($mode === 'get_template') {
                if (self::DEBUG) echo "\r\n\r\n"."CREATE NEW TEMPLATE";

                $content = get_post_field('post_content', $post_data['id']);
                if ($content == "") 
                    $this->finish("error::unable to read template content of post with id „".$post_data['id']."“");
                else
                    $this->finish("success::".rawurlencode($content));
            }

            else if ($mode === 'mod_template_name') {
                if (self::DEBUG) echo "\r\n\r\n"."MODIFY TEMPLATE";
                
                if (isset($post_data['id']) && isset($post_data['value'])) {

                    // $meta = get_post_meta($post_data['id'], 'popup_settings', true);
                    $name = sanitize_title_with_dashes($post_data['value']);
                    if (is_string($name)) {
                        // $meta->prio->value = $post_data['value'];
                        // update template
                        wp_update_post(array(
                            "ID"            => $post_data['id'],
                            'post_name'     => $name,
                        ));
                        // check if there was an error updating
                        if (!is_wp_error($post_id)) {
                            if (self::DEBUG) echo sprintf("\r\n"."* template „%s“ successfully updated.", $post_data['id']);
                            $this->finish("success::Template <strong>".$post_data['id']."</strong> modified with new name value: ".$name."");
                        }
                        else {
                            if (self::DEBUG) echo sprintf("\r\n"."* error updating template „%s“:", $post_data['id']);
                            echo "\r\n".$post_id->get_error_message();
                        } 
                    }

                }
                $this->finish("error::unable to modify template: ".$post_data['id']);
            }
            else if ($mode === 'mod_template_type') {
                if (self::DEBUG) echo "\r\n\r\n"."MODIFY TEMPLATE";
                
                if (isset($post_data['id']) && isset($post_data['value'])) {

                    $type = $post_data['value'];
                    wp_set_object_terms( $post_data['id'], $type, 'template_type' );

                    // check if there was an error updating
                    if (!is_wp_error($post_id)) {
                        if (self::DEBUG) echo sprintf("\r\n"."* template „%s“ successfully updated.", $post_data['id']);
                        $this->finish("success::Template <strong>".$post_data['id']."</strong> modified with new type value: ".$type."");
                    }
                    else {
                        if (self::DEBUG) echo sprintf("\r\n"."* error updating template „%s“:", $post_data['id']);
                        echo "\r\n".$post_id->get_error_message();
                    } 

                }
                $this->finish("error::unable to modify template: ".$post_data['id']);
            }
            
            /*
             *  Reset wizard
             */
            else if ($mode === 'install_reset') {
                self::update_content_mode();
                $this->reset_installation($post_data);
            }
        
            /*
             *  Reset without wizard
             *  
             *  called from hub
             */
            else if ($mode === 'install_reset_silent') {
                $blog_id = $post_data;
                if (is_multisite()) switch_to_blog($blog_id);
                    self::update_content_mode();
                    update_option('fresh_site', "1");
                    update_option('greyd_installed', "0");
                    $data = array(
                        array( 'name' => 'reset_thememods', 'value' => 'checked'),
                        array( 'name' => 'reset_pages', 'value' => 'checked'),
                        array( 'name' => 'reset_templates', 'value' => 'checked'),
                        array( 'name' => 'reset_posts', 'value' => 'checked'),
                        array( 'name' => 'reset_forms', 'value' => 'checked'),
                        array( 'name' => 'reset_posttypes', 'value' => 'checked'),
                        array( 'name' => 'reset_popups', 'value' => 'checked'),
                        array( 'name' => 'reset_menus', 'value' => 'checked'),
                        array( 'name' => 'reset_media', 'value' => 'checked'),
                        array( 'name' => 'reset_plugins', 'value' => 'checked'),
                        array( 'name' => 'install_basics', 'value' => 'checked'),
                    );
                    $this->reset_installation($data);
                if (is_multisite()) restore_current_blog();
            }
            
            /*
             *  Crawl Template
             */
            else if ($mode === 'crawl_template') {
                if (self::DEBUG) echo "\r\n\r\n"."CRAWLING TEMPLATE";

                    $slug = $post_data['slug'];

                    if ($slug != "404" && is_numeric($slug)) {
                        // debug("template by id");
                        // debug($slug);
                        $template = array( get_post($slug) );
                        // $template = get_posts(array(
                        //     'p'           => $slug,
                        //     'post_type'   => 'dynamic_template',
                        //     'post_status' => 'publish',
                        //     'numberposts' => 1
                        // ));
                    }
                    else {
                        $template = get_posts(array(
                            'name'        => $slug,
                            'post_type'   => 'dynamic_template',
                            'post_status' => 'publish',
                            'numberposts' => 1
                        ));
                    }
                    if ($template) {
                        $content = $template[0]->post_content;
                        $result = $this->crawlTemplate($content, $post_data['post_id']);
                        if (!$result) {
                            $json = json_encode(array(
                                'ID' => $template[0]->ID,
                                'result' => "no_content",
                                'title' => $template[0]->post_title
                            ));
                            $this->finish("error::".$json);
                        }
                        else {
                            $json = json_encode(array(
                                'ID' => $template[0]->ID,
                                'result' => $result,
                                'title' => $template[0]->post_title
                            ));
                            $this->finish("success::".$json);
                        }
                    }
                    // $this->finish($content);
                    $this->finish('error::not_found');
            }
            
            /*
             *  Change Website Info
             *  @deprecated moved to hub.php
             *  
             *  called from hub
             */
            // else if ($mode === 'change_bloginfo') {
            //     $name   = isset($post_data['name']) ? $post_data['name'] : '';
            //     $value  = isset($post_data['value']) ? $post_data['value'] : '';
            //     $blogid = isset($post_data['blogid']) ? $post_data['blogid'] : '';
                
            //     $response = $this->set_bloginfo( $name, $value, $blogid );
                
            //     if ($response === false) 
            //         $this->finish("error::");
            //     else
            //         $this->finish("success::");
            // }

            else {
                // call custom action to trigger third party functions
                if (self::DEBUG)  echo "\r\n\r\n"."TRIGGER ACTION: greyd_ajax_mode_".$mode;
                do_action('greyd_ajax_mode_'.$mode, $post_data);
                
                // die otherwise
                $this->finish("error::unknown inquery");
            }
        }
        $this->finish();
    }
    
    
    /*
    =======================================================================
        SETUP INSTALLATION
    =======================================================================
    */
    public $contents = [
        'media'         => [],
        'posts'         => [],
        'templates'     => [],
        'forms'         => [],
        'posttypes'     => [],
        'custom_posts'  => [],
        'pages'         => [],
        'menus'         => []
    ];

    /*
     *  Setup the whole basic installation
     *  
     *  - setup media attachments
     *  - setup posts
     *  - setup templates
     *  - setup forms
     *  - setup dynamic posts
     *  - setup dynamic post types
     *  - setup default page
     *  - setup design (theme mods)
     *  - setup wp menu
     *  - setup options
     */
    public function basic_installation($overwrite_design=true) {

		if ( ! \Greyd\Helper::is_greyd_classic() ) {
			/**
			 * @todo add FSE installation
			 */
			$this->finish("success::This is not the classic version of Greyd. No install actions are needed.");
		}
        
        $result = $this->set_required_plugins();
        if ( $result !== true ) $this->finish($result);

        self::update_content_mode();
        
        // setup all attachements
        $result = $this->set_attachments();
        if (!$result) $this->finish('error::unable to create all media files.');
        else $this->contents['media'] = $result;
        
        // setup post
        $result = $this->set_posts('posts');
        if (!$result) $this->finish('error::unable to install default post.');
        else $this->contents['posts'] = $result;
        
        // setup templates
        $result = $this->set_posts('templates');
        if (!$result) $this->finish('error::unable to install templates.');
        else $this->contents['templates'] = $result;
        
        // setup forms
        $result = $this->set_posts('forms');
        if (!$result) $this->finish('error::unable to install forms.');
        else $this->contents['forms'] = $result;
        
        // setup custom post types
        $result = $this->set_posts('posttypes');
        if (!$result) $this->finish('error::unable to install post types.');
        else $this->contents['posttypes'] = $result;
        
        // setup custom posts
        $result = $this->set_posts('custom_posts');
        if (!$result) $this->finish('error::unable to install custom dynamic post types.');
        else $this->contents['custom_posts'] = $result;
        
        // setup default page
        $result = $this->set_posts('pages');
        if (!$result) $this->finish('error::unable to install default page.');
        else $this->contents['pages'] = $result;
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $result['homepage'] );
        
        // setup basic design
        if ($overwrite_design) {
            $result = $this->set_design();
            if (!$result) $this->finish("error::Unable to setup theme designs. See browser console for details.");
        }
        
        // setup wp menu
        $result = $this->set_menu();
        if (!$result) $this->finish('error::unable to install menus.');
        else $this->contents['menus'] = $result;
        
        // setup basic options
        $this->set_options();
        
        // if (self::DEBUG) {
        //     echo "\r\n\r\n"."DEFAULT CONTENTS ARE SET:"."\r\n";
        //     print_r($this->contents);
        // }
        $this->finish("success::basic installation installed.");
    }
    
    /*
     *  set all media attachments and save in array
     *  
     *  @return (array) $attachments | false
     */
    public function set_attachments($extra='') {
        $return = [];
        if (self::DEBUG) echo "\r\n\r\n"."SET MEDIA FILES";

		if ( ! \Greyd\Helper::is_greyd_classic() ) {
			/**
			 * @todo add FSE theme design installation
			 */
			if (self::DEBUG) echo "\r\n"."* no media required for FSE themes.";
			return true;
		}
        
        $path = $this->path.'content/img/';
        $attachments = (array) self::$default_contents['media'.$extra];
        
        $error = false;
        foreach ($attachments as $name => $atts) {
            echo "\r\n".$name;
            
            // find attachement
            $post = $this->get_media_by_slug($atts['slug']);
            
            // attachment not found --> create
            if ($post === false) {
                $format = isset($atts['format']) ? $atts['format'] : 'svg';
                $id = $this->upload_media($path.$atts['slug'].'.'.$format);
                
                // return if error
                if ($id === false) {
                    echo "\r\n* could not upload file „".$atts['slug']."“";
                    $error = true;
                    continue;
                }
                if (self::DEBUG) echo sprintf("\r\n* media-file „%s“ uploaded.", $atts['slug']);
                
                // set post var
                $post = ['slug' => $atts['slug'], 'id' => $id];
            } else {
                if (self::DEBUG) echo sprintf("\r\n* media-file „%s“ found.", $atts['slug']);
            }
            // push to array
            $return[$name] = $post['id'];
        }
        return $error ? false : $return;
    }
    
    /*
     *  Create posts
     *  
     *  @return (array) $posts | false
     */
    public function set_posts($type='posts') {
        $return = [];
        if (self::DEBUG) echo "\r\n\r\n".sprintf("SET %s", strtoupper($type));
        
        if (!isset(self::$default_contents[$type])) {
            debug( self::$default_contents[$type] );
            if (self::DEBUG) echo "\r\n".sprintf("* type „%s“ does not exists.", $type);
            return false;
        }
        $posts = self::$default_contents[$type];
        
        $error = false;
        foreach ((array) $posts as $name => $atts) {
            if (  isset($atts['default']) && $atts['default'] === false ) continue;
            // create post...
            $post_id = $this->create_post($atts);
            if ($post_id === false) {
                $error = true;
                continue;
            }
            $return[$name] = $post_id;
        }
        return $error ? false : $return;
    }
    
    /*
     *  Set default design (theme mods)
     *  
     *  @return true | false
     */
    public function set_design($overwrite_logo=true) {
        if (self::DEBUG) echo "\r\n\r\n"."SET DESIGN";

		if ( ! \Greyd\Helper::is_greyd_classic() ) {
			/**
			 * @todo add FSE theme design installation
			 */
			if (self::DEBUG) echo "\r\n"."* no theme mods required for FSE themes.";
			return true;
		}
        
        // get mod file
        $data = $this->get_mod_file('raw');
        if (!$data) return false;
        
        // get values
        $mods = $this->get_mod_values($data);
        
        // overwrite custom logo
        if ($overwrite_logo && isset($this->contents["media"]["logo_dark"]) )
            $mods["custom_logo"] = $this->contents["media"]["logo_dark"];
        
        // set values
        foreach($mods as $key => $value) {
            set_theme_mod($key, $value);
        }
        
        if (self::DEBUG) echo "\r\n"."* theme mods successfully installed.";
        return true;
    }
    
    /*
     *  Set default wp-menu
     *  
     *  @return array('main_menu' => $menu_id) | false
     */
    public function set_menu() {
        if (self::DEBUG) echo "\r\n\r\n"."SET MENU";

		if ( ! \Greyd\Helper::is_greyd_classic() ) {
			/**
			 * @todo add FSE theme design installation
			 */
			if (self::DEBUG) echo "\r\n"."* no menu required for FSE themes.";
			return true;
		}
        
        $name = self::$default_contents['menu']['name'];
        $location = self::$default_contents['menu']['location'];
        
        $menu = wp_get_nav_menu_object( $name );
        
        if ( $menu !== false ) {
            $menu_id = $menu->term_id;
            if (self::DEBUG) echo "\r\n".sprintf("* menu already exists (id: %s).", $menu_id);
        } else {
            $menu_id = wp_create_nav_menu($name);
            // check if there was an error creating the menu
            if (is_wp_error($menu_id)) {
                if (self::DEBUG) echo "\r\n"."* error creating menu:";
                echo "\r\n".$menu_id->get_error_message();
                return false;
            } else {
                if (self::DEBUG) echo "\r\n".sprintf("* menu with the id „%s“ created.", $menu_id);
            }
            
            // homepage link
            wp_update_nav_menu_item( $menu_id, 0, array(
                    'menu-item-title' =>  __("Example page", 'greyd_hub'),
                    'menu-item-object-id' => get_option('page_on_front'),
                    'menu-item-object' => 'page',
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'post_type',
            ));
            
            // tutorials
            $parent_id = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __('Tutorials', 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter, 'greyd_hub'),
                'menu-item-status' => 'publish'
            ));
            // child items
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __("How to start", 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/funktionen/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __('Customizer', 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/customizer/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __('Greyd.Hub', 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/greyd-hub/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __('Greyd.Forms', 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/greyd-forms/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __("Widgets", 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/greyd-blocks/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title' =>  __('Templates', 'greyd_hub'),
                'menu-item-url' => __($this->config->helpcenter.'/templates-post-types/', 'greyd_hub'),
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ));
        
            if (self::DEBUG) echo "\r\n"."* all menu items set.";
        }

        // assign as main-menu
        $locations = get_theme_mod('nav_menu_locations');
        $locations[$location] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
        
        if (self::DEBUG) echo "\r\n".sprintf("* menu set to location '%s'.", $location);
        return [$location => $menu_id];
    }
    
    /*
     *  Set basic options
     *  
     *  @return true | false
     */
    public function set_options() {
        if (self::DEBUG) echo "\r\n\r\n"."SET OPTIONS";

		if ( ! \Greyd\Helper::is_greyd_classic() ) {
			/**
			 * @todo add FSE theme design installation
			 */
			if (self::DEBUG) echo "\r\n"."* no options required for FSE themes.";
			return true;
		}
        
        // wpb defaults
        $result = update_option('wpb_js_gutenberg_disable', '1');
        if (self::DEBUG) echo "\r\n"."* vc gutenberg disabled.";
        // $result = update_option('wpb_js_composer_license_activation_notified', 'yes');
        $roles = wp_roles();
        $editable_roles = get_editable_roles();
        // debug($roles);
        // debug($editable_roles);
        foreach ($editable_roles as $key => $value) {
            // debug($key);
            // debug($value['capabilities']);
            if (isset($value['capabilities']['edit_posts'])) {
                // $roles->remove_cap( $key, 'vc_access_rules_frontend_editor' );
                $roles->add_cap( $key, 'vc_access_rules_post_types', 'custom' );
                $roles->add_cap( $key, 'vc_access_rules_post_types/post', '1' );
                $roles->add_cap( $key, 'vc_access_rules_post_types/page', '1' );
                $roles->add_cap( $key, 'vc_access_rules_post_types/dynamic_template', '1' );
                $roles->add_cap( $key, 'vc_access_rules_post_types/tp_forms', '1' );
                $roles->add_cap( $key, 'vc_access_rules_post_types/greyd_popup', '1' );
                $roles->add_cap( $key, 'vc_access_rules_backend_editor', 'default' );
                $roles->add_cap( $key, 'vc_access_rules_frontend_editor', false );
                $roles->add_cap( $key, 'vc_access_rules_templates', false );
                $roles->add_cap( $key, 'vc_access_rules_grid_builder', false );
            }
        }
        if (self::DEBUG) echo "\r\n"."* visual composer roles set.";
        
        // disable lottie
        $greyd_settings = get_option('settings_site_greyd_tp', (class_exists("\basics") ? \basics::$defaults['site'] : array()));
        $greyd_settings['lottie']['mode'] = 'disable';
        $greyd_settings['lazyload'] = 'true';
        $greyd_settings['font_lazyload'] = 'true';
        $greyd_settings['advanced_search']['relevance'] = 'true';
        $greyd_settings['advanced_search']['live_search'] = 'true';
        update_option('settings_site_greyd_tp', $greyd_settings);
        
        update_option('greyd_installed', "1");
        
        return true;
    }
    
    /*
     * Set bloginfo
     * @deprecated moved to hub.php
     *  
     * @return true | false
     */
    // public function set_bloginfo($option='', $value='', $blogid='') {
    //     if (self::DEBUG) echo "\r\n\r\n"."CHANGE BLOGINFO";
        
    //     if ( empty($option) || ( is_multisite() && empty($blogid) ) ) {
    //         if (self::DEBUG) echo "\r\n\r\n"."not all necessary options set --> aborted";
    //         return false;
    //     }

    //     $name   = strval( esc_attr( trim( $option ) ) );
    //     $value  = strval( esc_attr( trim( $value ) ) );
    //     $blogid = esc_attr( trim( $blogid ) );

    //     // invalid name
    //     if ( $name !== 'blogname' && $name !== 'blogdescription' ) {
    //         if (self::DEBUG) echo "\r\n\r\n"."invalid option '$name', needs to be 'blogname' or 'blogdescription'";
    //         return false;
    //     }

    //     // maxlengths
    //     if ( $name === 'blogname' && strlen($value) > 41 ) {
    //         $value = substr( $value, 0, 41 );
    //     }
    //     else if ( $name === 'blogdescription' && strlen($value) > 255 ) {
    //         $value = substr( $value, 0, 255 );
    //     }

    //     if (is_multisite()) switch_to_blog($blogid);
        
    //     // update the option
    //     if (self::DEBUG) echo "\r\n\r\n"."changing '$name' to '$value'...";
    //     $response = update_option($name, $value);
        
    //     if (is_multisite()) restore_current_blog();
        
    //     if (self::DEBUG)  {
    //         if ($response) echo "\r\n\r\n"."option '$name' updated to '$value'";
    //         else echo "\r\n\r\n"."unable to update option '$name'";
    //     }
    //     return $response;
    // }
    
    /*
     *  Reset Installation
     */
    public function reset_installation($args) {
        if (self::DEBUG) echo "\r\n\r\n"."RESET CONTENT";
                
        $reset_mods     = false;
        $install_basics = false;

        foreach ((array)$args as $data) {
            if ( !isset($data['name']) ) continue;
            if ( isset($data['value']) && $data['value'] == 'checked' ) {
                $name = isset($data['name']) ? $data['name'] : '';
                
                // delete posts
                if ($name == 'reset_templates') {
                    self::delete_all_posts('dynamic_template');
                }
                else if ($name == 'reset_pages') {
                    self::delete_all_posts('page');
                }
                else if ($name == 'reset_posts') {
                    self::delete_all_posts('post');
                }
                else if ($name == 'reset_forms') {
                    self::delete_all_posts('tp_forms');
                    self::delete_all_posts('tp_forms_entry');
                }
                else if ($name == 'reset_products') {
                    self::delete_all_posts('product');
                }
                else if ($name == 'reset_shop_order') {
                    self::delete_all_posts('shop_order');
                }
                else if ($name == 'reset_posttypes') {
                    $posttypes = (array)Greyd\Posttypes\Posttype_Helper::get_dynamic_posttypes();
                    if ( count($posttypes) > 0 ) {
                        foreach($posttypes as $id => $atts) {
                            if ( isset($atts['slug']) && !empty($atts['slug']) )
                                self::delete_all_posts($atts['slug']);
                        }
                    }
                    self::delete_all_posts('tp_posttypes');
                }
                else if ($name == 'reset_popups') {
                    self::delete_all_posts('greyd_popup');
                }
                else if ($name == 'reset_media') {
                    self::delete_all_posts('attachment');
                }
                else if ($name == 'reset_menus') {
                    if (self::DEBUG) echo "\r\n\r\n"."DELETE MENUS";
                    $allmenus = wp_get_nav_menus();
                    foreach ((array) $allmenus as $post) wp_delete_nav_menu( $post->slug, true );
                    if (self::DEBUG) echo "\r\n* all menus deleted.";
                }
                // deactivate plugins
                else if ($name == 'reset_plugins') {
                    $this->reset_non_required_plugins();
                }
                // reset thememods
                else if ($name == 'reset_thememods') {
                    // make backup
                    $active_thememods = 'theme_mods_'.get_option('stylesheet');
                    $mods_data_backup = get_option($active_thememods);
                    $option_backup = "_backup_wizard_".$active_thememods;
                    if (get_option($option_backup) === false && update_option($option_backup, false) === false) 
                        add_option($option_backup, $mods_data_backup);
                    else update_option($option_backup, $mods_data_backup);
                    
                    // reset mods
                    // delete_option($active_thememods);
                    $result = $this->set_design(false);
                    if (!$result) $this->finish("error::Unable to reset design. See browser console for details.");
                    else if (self::DEBUG) echo "\r\n* thememods deleted.";
                    // set var to true to overwrite while installing basics
                    $reset_mods = true;
                }
                // install basics
                else if ($name == 'install_basics') {
                    $install_basics = true;
                }
            }
        }
        
        if ($install_basics) $this->basic_installation($reset_mods);
        
        $this->finish("success::reset successfull");
    }
    
    
    /*
    =======================================================================
        HELPERS
    =======================================================================
    */

    /**
     * Install and update all required plugins
     */
    public function set_required_plugins() {
        if (self::DEBUG) echo "\r\n\r\n"."SET REQUIRED PLUGINS";
        $required_plugins = class_exists("\basics") ? \basics::get_required_plugins() : array();
        foreach($required_plugins as $plugin) {
            if ( (isset($plugin['default']) && $plugin['default'] === true) || (isset($plugin['required']) && $plugin['required'] === true) ) {
                $result = $this->set_plugin($plugin['slug'], isset($plugin['source']) ? $plugin['source'] : '');
                if (strpos($result, "error::") === 0) return $result;
            }
        }
        if (self::DEBUG) echo "\r\n* all required plugins up to date.";
        return true;
    }

    /**
     * Reset all non required plugins
     */
    public function reset_non_required_plugins() {
        if (self::DEBUG) echo "\r\n\r\n"."RESET NOT REQUIRED PLUGINS";

		$required_plugin_slugs = array_flip(
			array_map( function($plugin) {
				return isset($plugin['slug']) && isset($plugin['required']) && $plugin['required'] === true ? $plugin['slug'] : '';
			},
			class_exists("\basics") ? \basics::get_required_plugins() : array()
		) );

        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin_file) {
            $plugin_slug = explode('/', $plugin_file)[0];
            if ( !isset($required_plugin_slugs[$plugin_slug]) ) {
                $this->unset_plugin($plugin_slug);
            }
        }

        if (self::DEBUG) echo "\r\n* all not required plugins deactivated.";
    }
    
    /*
     *  Set a plugin
     *  
     *  - check if installed
     *  - install plugin if not
     *  - check for upgrades
     *  - update plugin if not the latest version
     *  - activate plugin
     */
    public function set_plugin($plugin_slug, $plugin_zip="") {
        if ($plugin_zip == "") {
            if (!function_exists('plugins_api')) {
                require_once ABSPATH.'wp-admin/includes/plugin-install.php';
            }
            $api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug, 'fields' => array( 'sections' => false ) ) );
            if ($api !== false && isset($api->download_link) ) {
                $plugin_zip = $api->download_link;
            }
        }
        // debug($plugin_zip);
        if (self::DEBUG) echo sprintf("\r\n\r\nCheck if plugin „%s“ is already installed ...", $plugin_slug);
        $plugin_php = $this->is_plugin_installed($plugin_slug);
        if ($plugin_php !== false) {
            // debug($plugin_php);
            if (self::DEBUG) echo "\r\n* it's installed --> Making sure it's the latest version...\r\n* ";
            $this->upgrade_plugin($plugin_php);
            $installed = true;
        } 
        else {
            if (self::DEBUG) echo "\r\n* it's not installed --> installing...";
            $installed = $this->install_plugin($plugin_zip);
            $plugin_php = $this->is_plugin_installed($plugin_slug);
        }
        // debug($installed);
        if (!is_wp_error($installed) && $installed) {
            if (self::DEBUG) echo "* activating plugin...";
            $activate = activate_plugin($plugin_php);
            if (is_null($activate)) {
                if (self::DEBUG) echo "\r\n* Done! Everything went smooth.";
                return true;
            }
            else {
                if (self::DEBUG) echo "\r\n* Could not activate the plugin.";
                return "error::unable to activate plugin: ".$plugin_slug;
            }
        } 
        else {
            if (self::DEBUG) echo "* Could not install the plugin.";
            return "error::unable to install plugin: ".$plugin_slug;
        }
    }

    public function is_plugin_installed($slug) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        foreach($all_plugins as $plugin => $details)
            if (strpos($plugin, $slug.'/') === 0) 
                return $plugin;
        return false;
    }

    public function is_plugin_network_active($slug) {
        if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
            require_once( ABSPATH.'/wp-admin/includes/plugin.php' );
        }
        return is_plugin_active_for_network($slug);
    }
    
    public function install_plugin($plugin_zip) {
        if (!class_exists('Plugin_Upgrader', false)) {
            require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        }
        wp_cache_flush();
        $upgrader = new \Plugin_Upgrader();
        $installed = $upgrader->install($plugin_zip);
        return $installed;
    }
    
    /**
     * Never update a plugin during this process.
     * 
     * This can lead to serious problems, especially on multisite setups,
     * where it is updated for the entire stage.
     * @since 1.2.7
     */
    public function upgrade_plugin($plugin_php) {
        return false;
        // if (!class_exists('Plugin_Upgrader', false)) {
        //     require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        // }
        // wp_cache_flush();
        // $upgrader = new \Plugin_Upgrader();
        // $upgraded = $upgrader->upgrade($plugin_php);
        // return $upgraded;
    }

    public function unset_plugin($plugin_slug) {
        $plugin_php = $this->is_plugin_installed($plugin_slug);
        if ($plugin_php !== false) {

            if ( $this->is_plugin_network_active($plugin_slug) ) {
                if (self::DEBUG) echo "\r\n* the plugin '$plugin_slug' is active on the network --> will not be deactivated...\r\n* ";
            }
            else {
                if (self::DEBUG) echo "\r\n* the plugin '$plugin_slug' is locally installed --> deactivating plugin...\r\n* ";
                deactivate_plugins( array( $plugin_php ) );
            }
        } 
    }
    
    /*
     *  Find media by its slug
     *  
     *  @return array('id' => $post_id, 'slug' => $slug) | false
     */
    public function get_media_by_slug( $slug ) {
        $args = array(
            'post_type' => 'attachment',
            'name' => sanitize_title($slug),
            'posts_per_page' => 1,
            'post_status' => 'inherit',
        );
        $post = get_posts( $args );
        if (empty($post) || !array($post)) return false;
        $post = $post[0];
        return array(
            'slug'  => $slug,
            'id'    => $post->ID,
            // 'url'   => wp_get_attachment_url($post->ID) // in case we need this later on...
        );
    }
    
    /*
     *  Upload media file
     *  
     *  @return $post_id | false
     */
    public function upload_media($path_to_file) {
        $file = $path_to_file;
        $filename = basename($file);

        $upload_file = wp_upload_bits($filename, null, self::get_file_contents($file));
        if (!$upload_file['error']) {
            $parent_post_id = -1;
            $wp_filetype = wp_check_filetype($filename, null );
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => $parent_post_id,
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id,  $attachment_data );
            }
            return $attachment_id;
        }
        return false;
    }
    
    /*
     *  Create post
     *  
     *  @return $post_id | false
     */
    public function create_post($args) {
        $post_id = null;
        $slug    = isset($args['slug'])     ? $args['slug']     : '';
        $title   = isset($args['title'])    ? $args['title']    : '';
        $type    = isset($args['type'])     ? $args['type']     : 'post';
        $content = isset($args['content'])  ? $this->replace_placeholder($args['content'])  : '';
        $meta    = isset($args['meta'])     ? $this->replace_placeholder($args['meta'])     : null;
        $img     = isset($args['img'])      ? $this->replace_placeholder($args['img'])      : null;
        $force   = isset($args['force'])    ? $args['force']    : false;
        $state   = isset($args['state'])    ? $args['state']    : 'publish';
        $excerpt = isset($args['excerpt'])  ? $args['excerpt']  : '';
        
        // sanitize content
        if ($type == 'greyd_popup') $meta = $args['meta'];
        if (self::$content_mode == 'shortcodes') {
            $content = preg_replace("/[\r\\n]+/", "", $content);
            $content = preg_replace('/\s+/', ' ', $content);
        }

        // check if page already exists
        $query = array(
            'name'        => $slug,
            'post_type'   => $type,
            'post_status' => $state,
            'numberposts' => 1
        );
        $exists = get_posts($query);
        // if no template found -> search for copies
        if (!$exists) {
            $i = 1;
            while ($i <= 10) {
                $query['name'] = $slug."-".$i;
                $exists = get_posts($query);
                if ($exists) break;
                $i++;
            }
        }
        
        // post already exists
        if ($exists) {
            $post_id = $exists[0]->ID;
            if (self::DEBUG) echo sprintf("\r\n"."* post „%s“ already exists", $slug);
            
            // change if force is true
            if ($force) {
                wp_update_post(array(
                    "ID"            => $post_id,
                    "post_title"    => $title,
                    "meta_input"    => $meta,
                    "post_content"  => $content
                ));
                // check if there was an error updating the post
                if (is_wp_error($post_id)) {
                    if (self::DEBUG) echo sprintf("\r\n"."* error updating post „%s“:", $slug);
                    echo "\r\n".$post_id->get_error_message();
                    return false;
                } else {
                    if (self::DEBUG) echo sprintf("\r\n"."* post „%s“ successfully updated.", $slug);
                }
            }
        }
        // post doesn't exist
        else {
            if (self::DEBUG) echo sprintf("\r\n"."* post „%s“ doesn't exist.", $slug);
            $post = [
                "post_name"     => $slug,
                "post_title"    => $title,
                "post_status"   => $state,
                "post_type"     => $type,
                "meta_input"    => $meta,
                "post_excerpt"  => $excerpt,
                "post_content"  => $content,
                "menu_order"    => -1
            ];
            
            $post_id = wp_insert_post( $post );
            // check if there was an error in the post insertion
            if (is_wp_error($post_id)) {
                if (self::DEBUG) echo sprintf("\r\n"."* error creating post „%s“:", $slug);
                echo "\r\n".$post_id->get_error_message();
                return false;
            }
            else {
                if (self::DEBUG) echo sprintf("\r\n"."* post „%s“ successfully created", $slug);
                // set post thumbnail
                if (isset($img)) {
                    $result = set_post_thumbnail($post_id, $img);
                    if (self::DEBUG) {
                        if ($result)
                            echo "\r\n"."* post thumbnail set.";
                        else
                            echo "\r\n"."* post thumbnail could not be set.";
                    }
                }
            }
        }
        // set template type
        if (isset($args['template_type'])) 
            wp_set_object_terms( $post_id, $args['template_type'], 'template_type' );
        else if (isset($args['ttype'])) 
            wp_set_object_terms( $post_id, $args['ttype'], 'template_type' );
        
        return $post_id;
    }
    
    /*
     *  replace all placholders
     *  
     *  @return input with replaced values
     */
    public function replace_placeholder($input='') {
        if (empty($input)) return $input;
        if ( is_object($input) ) {
            return $this->replace_placeholder((array) $input);
        }
        else if ( is_array($input) ) {
            foreach($input as $key => $val) {
                $input[$key] = $this->replace_placeholder($val);
            }
        }
        else {
            $input = strval($input);
            // replace all normal placeholders like {{img_dark}}
            $placeholder = $this->get_placeholders($this->contents);
            foreach ((array) $placeholder as $name => $id) {
                if (!empty($name))
                    $input = preg_replace("/\{\{".strval($name)."\}\}/", intval($id), $input);
            }
            // replace advanced img-placeholders like {{img_dark::url}}
            while (preg_match("/\{\{.+::.+\}\}/", $input)) {
                $old = $this->get_string_between($input, "{{", "}}");
                if (strpos($old, '::') !== false) {
                    $ph = explode("::", $old);
                    $new = $ph[1] == 'url' ? wp_get_attachment_url($placeholder[$ph[0]]) : "{{".$old."}}";
                    $input = preg_replace("/\{\{".$old."\}\}/", $new, $input);
                }
            }
        }
        return $input;
    }
    
    /*
     *  Get all placeholder
     *  
     *  @return (array) $placeholders
     */
    public function get_placeholders($contents) {
        $placeholders = array();
        foreach($contents as $key => $value) {
            if (is_array($value))
                $placeholders = array_merge($placeholders, $this->get_placeholders($value));
            else
                $placeholders[$key] = $value;
        }
        return $placeholders;
    }
    
    /*
     *  Get first occurence of string between two strings
     *  
     *  @return (string) first occurence of string between $start and $end
     */
    public function get_string_between($string, $start, $end){
        $string = ' '.$string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    
    /*
     *  Get contents of mod-file as array
     *
     *  @return (array) file data | false
     */
    public function get_mod_file($filename="") {
        if ( empty($filename) ) return false;
        
        $data = false;
        $path = str_replace("http://", "", $this->path."mods/");
        $filename = strpos($filename, ".json") === false ? $filename.".json" : $filename;
        
        // get file
        $file = file_exists($path.$filename) ? self::get_file_contents($path.$filename) : false;
        
        // get values
        if ( $file ) {
            $data = json_decode($file, true);
            if ( self::DEBUG ) {
                if ( $data )
                    echo "\r\n".sprintf("* file '%s' found and decoded.", $filename );
                else
                    echo "\r\n".sprintf("* an error occurred decoding the file %s: %s", $filename, ( function_exists('json_last_error_msg') ? json_last_error_msg() : "unidentified error" ) );
            }
        }
        
        return $data;
    }
    
    /*
     *  Basically the same as 'file_get_contents', but with debug logging
     *
     *  @return (string) $contents | false
     */
    public static function get_file_contents($file) {
        $contents = file_get_contents($file);
        
        if ( !$contents && self::DEBUG) {
            echo "\r\n".sprintf("* HTTP request failed. Error was: %s", ( function_exists('error_get_last') ? error_get_last()['message'] : "unidentified error" ) );
            if (ini_get('allow_url_fopen') == false) {
                echo "\r\n"."* Your server's PHP settings are not compatible with the Greyd.Suite. The variable 'allow_url_fopen' is deactivated, which leads to the website being displayed incorrectly. Please contact your server administrator to resolve the problem.";
            }
        }
        return $contents;
    }
    
    /*
     *  Get all theme mod values from converted json-object
     *  
     *  @return (array) $values
     */
    public function get_mod_values($data) {
        $values = array();
        foreach($data as $key => $value) {
            if (is_object($value) || is_array($value)) $values = array_merge($values, $this->get_mod_values($value));
            else $values[$key] = $value;
        }
        return $values;
    }
    
    /*
     *  crawl template
     *  
     *  @return (array) $dynamic_fields
     */
    public function crawlTemplate($content, $post_id) {
		if ( !class_exists('\vc\helper') ) {
			return array();
		}
        $shortcodes = \vc\helper::get_dynamic_shortcodes($content);
        // debug($shortcodes);
        if (!$shortcodes) return false;
        
        $dynamic_fields = array();
        foreach ((array) $shortcodes as $shortcode) {
            // debug($shortcode);
            if (!isset($shortcode['atts']) || $shortcode['atts'] == "") continue;
            
            $fields = \vc\helper::get_dynamic_module_fields($shortcode['shortcode']);
            // debug($fields);
            if (!$fields) continue;
            
            foreach ((array) $fields as $field) {
                $dfield = '';
                
                foreach ((array) $shortcode['atts'] as $att) {
                    // debug($att['key']);
                    if ($att['key'] != $field) continue;
                    if (strpos($att['value'], '|') === false) break;
                    
                    $dfield = $att;
                    $tmp = explode('|', $dfield['value']);
                    $sfield = array(
                        'key' => $tmp[0],
                        'value' => '',
                    );
                    foreach ((array) $shortcode['atts'] as $att) {
                        if ($att['key'] == $sfield['key']) {
                            $sfield = $att;
                            break;
                        }
                    }
                    if ($sfield['key'] == 'content' && isset($shortcode['content'])) {
                        $sfield['value'] = $shortcode['content'];
                    }
                    $dynamic_field = array(
                        'shortcode' => $shortcode['shortcode'],
                        'dynamic_field' => $dfield,
                        'static_field' => $sfield
                    );
                    if ($tmp[1] == 'textarea')
                        $dynamic_field['markup'] = \vc\defaults::vc_textarea_form_field( array( 'param_name' => '', 'type' => 'textarea' ), "" );
                    if ($tmp[1] == 'textarea_html')
                        $dynamic_field['markup'] = \vc\defaults::vc_textarea_html_form_field( array( 'param_name' => '', 'type' => 'textarea_html' ), "" );
                    if ($tmp[1] == 'textfield')
                        $dynamic_field['markup'] = \vc\defaults::vc_textfield_form_field( array( 'param_name' => '', 'type' => 'textfield' ), "" );
                    if ($tmp[1] == 'vc_link')
                        $dynamic_field['markup'] = \vc\defaults::vc_vc_link_form_field( array( 'param_name' => '', 'type' => 'vc_link' ), "" );
                    if ($tmp[1] == 'dropdown_anchor')
                        $dynamic_field['markup'] = \vc\defaults::vc_dropdown_form_field( array( 'param_name' => '', 'type' => 'dropdown', 'value' => \vc\helper::get_vc_cbutton_anchor_params($post_id) ), "" );
                    if ($tmp[1] == 'dropdown_menu')
                        $dynamic_field['markup'] = \vc\defaults::vc_dropdown_form_field( array( 'param_name' => '', 'type' => 'dropdown', 'value' => \vc\helper::get_vc_menus() ), "" );
                    if ($tmp[1] == 'dropdown_forms')
                        $dynamic_field['markup'] = \vc\defaults::vc_dropdown_form_field( array( 'param_name' => '', 'type' => 'dropdown', 'value' => \vc\helper::get_vc_forms() ), "" );
                    if ($tmp[1] == 'dropdown_h')
                        $dynamic_field['markup'] = \vc\defaults::vc_dropdown_form_field( array( 'param_name' => '', 'type' => 'dropdown', 'value' => \vc\helper::get_vc_headline_params() ), "" );
                    if ($tmp[1] == 'file_picker')
                        $dynamic_field['markup'] = \vc\defaults::vc_file_picker_form_field( array( 'param_name' => '', 'type' => 'file_picker', 'filetype' => array('json','image') ), "" );
                    if ($tmp[1] == 'file_picker_file')
                        $dynamic_field['markup'] = \vc\defaults::vc_file_picker_form_field( array( 'param_name' => '', 'type' => 'file_picker' ), "" );
                    if ($tmp[1] == 'file_picker_json')
                        $dynamic_field['markup'] = \vc\defaults::vc_file_picker_form_field( array( 'param_name' => '', 'type' => 'file_picker', 'filetype' => array('json') ), "" );
                    if ($tmp[1] == 'file_picker_image')
                        $dynamic_field['markup'] = \vc\defaults::vc_file_picker_form_field( array( 'param_name' => '', 'type' => 'file_picker', 'filetype' => array('image') ), "" );
                    $dynamic_fields[] = $dynamic_field;
                    break;
                }
            }
        }
        if (count($dynamic_fields) > 0) return $dynamic_fields;
        
        return false;
    }
    
    /*
     *  delete all posts by posttype
     */
    public function delete_all_posts($posttype='post') {
        if (self::DEBUG) echo "\r\n\r\n".sprintf("DELETING POSTS of posttype „%s“...", $posttype);
        if (empty($posttype)) return false;
        
        if ( !post_type_exists($posttype) ) {
            if (self::DEBUG) echo "\r\n".sprintf("* posttype „%s“ doesn't exist.", $posttype);
            return false;
        }
        
        $allposts = get_posts(array(
            'post_type' => $posttype,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
            'numberposts' => -1
        ));
        if ( !$allposts ) {
            if (self::DEBUG) echo "\r\n".sprintf("* no posts of posttype „%s“ found.", $posttype);
            return false;
        }
        
        foreach ((array) $allposts as $post)
            wp_delete_post( $post->ID, true );
        if (self::DEBUG) echo "\r\n".sprintf("* all posts of posttype „%s“ deleted.", $posttype);
        return true;
    }
    
    /*
     *  Die and send answer back to JS
     *
     *  basically the same as 'wp_die', but with debug logging
     */
    public function finish($msg="") {
        if (self::DEBUG) echo "\r\n\r\n"."------------- debug end -------------"."\r\n\r\n";
        wp_die($msg);
    }
    
    
    /*
    =======================================================================
        DEFAULT CONTENTS
    =======================================================================
    */
    /**
     * Content mode
     * @var string $content_mode (blocks|shortcodes|fse)
     */
    public static $content_mode = "";
    public static $wordings = [];
    public static $default_contents = [];

    public static function init_content_mode() {
        self::$content_mode = self::get_content_mode();
        add_action( 'after_setup_theme', array('self', 'init_default_contents'), 3 );
    }
    public static function init_default_contents() {
        // debug(self::$content_mode);
        self::$wordings = default_content::get_default_wordings(self::$content_mode);
        self::$default_contents = default_content::get_default_contents(self::$content_mode);
    }

    public static function get_content_mode() {

		if ( \Greyd\Helper::is_greyd_fse() ) return 'fse';
		
		if ( \Greyd\Helper::is_greyd_blocks() ) return 'blocks';

        $firstrun = empty( get_posts( array(
            'posts_per_page'   => 1,
            'post_type'        => 'dynamic_template',
        ) ) );
        
        $greyd_gutenberg = get_option('greyd_gutenberg', false);
        // debug(get_option('settings_site_greyd_tp'));
        if ($firstrun || $greyd_gutenberg === true || $greyd_gutenberg == '1') {
            if (!$greyd_gutenberg) update_option('greyd_gutenberg', "1");
            if (class_exists("\basics")) \basics::$defaults['site']['builder'] = 'gg-gg';
            $content_mode = "blocks";

            // set cache for function is_greyd_blocks()
            wp_cache_set( 'is_blocks_site', strval(true), 'greyd' );
        }
        else {
            $greyd_settings = get_option( 'settings_site_greyd_tp', (class_exists("\basics") ? \basics::$defaults['site'] : array()) );
            // debug($greyd_settings);
            $content_mode = isset($greyd_settings['builder']) && $greyd_settings['builder'] == 'gg-gg' ? "blocks" : "shortcodes";
        }
        
        return $content_mode;
    }
    public static function update_content_mode() {
        self::$content_mode = self::get_content_mode();
        self::init_default_contents();
    }
}

