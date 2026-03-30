<?php
/*
Feature Name:   Admin Search & Replace
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Requires Features: posttypes, post-export
Priority:       95
*/

/**
 * Feature: Search & replace
 * 
 * (1) Posts
 * Searches posts and replacing all references to it on the entire
 * site. Works with everything that is referenced by ID:
 * Dynamic Templates, Forms, Popups, Attachments, Terms
 * @todo check meta infos
 * 
 * (2) Terms
 * Searches for posts connected with the term and replacing that
 * connection with another term. Also searches post contents for
 * references to the term as well and replacing them.
 * 
 * (3) Regex
 * Search & replace posts with your own custom regex patterns.
 * @todo not yet implemented
 * 
 * @since 1.2.2
 */
namespace Greyd\Plugin;

use Greyd\Helper as Helper;
use Greyd\Post_Export_Helper as Post_Export_Helper;
use Greyd\Posttypes\Posttype_Helper as Posttypes;

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * disable if plugin wants to run standalone
 * Standalone setup not possible - needs posttypes and post-export.
 */
if ( !class_exists( "Greyd\Admin" ) ) {
    // reject activation
    if (!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
    $plugin_name = get_plugin_data(__FILE__, false, false)['Name'];
    deactivate_plugins( plugin_basename( __FILE__ ) );
    // return reject message
    die(sprintf("%s can not be activated as standalone Plugin.", $plugin_name));
}
    
new Search_And_Replace($config);
class Search_And_Replace {
    
    /**
     * Holds global config args.
     */
    private $config;

    /**
     * Holds the slug of the admin menu
     */
    public $menu_slug = "search_and_replace";

    /**
     * Caching the posts.
     */
    public static $all_posts = null;

    /**
     * Holds logs. Especially usefull when debugging ajax actions.
     * 
     * @var array
     */
    public static $logs = array();

    /**
     * Init class
     */
    public function __construct($config) {
        // set config
        $this->config = (object)$config;

        // add the menu items & pages
        add_action( 'admin_menu', array($this, 'add_submenu'), 40 );
        // add_filter( 'greyd_submenu_pages', array($this, 'add_greyd_submenu_page') );
        add_action( 'admin_enqueue_scripts', array($this, 'load_backend_scripts') );
    }


    /**
     * =================================================================
     *                          Admin
     * =================================================================
     */

    /**
     * Add a standalone submenu item
     */
    public function add_submenu() {
        add_submenu_page(
            'tools.php', // parent slug
            'Greyd ' . __( "Search & Replace", 'greyd_hub' ),  // page title
            'Greyd ' . __( "Search & Replace", 'greyd_hub' ), // menu title
            'manage_options', // capability
            $this->menu_slug, // slug
            array( $this, 'render_page' ), // function
            80 // position
        );
    }

    /**
     * Add the submenu item to Greyd.Suite
     * @see filter 'greyd_submenu_pages'
     */
    public function add_greyd_submenu_page($submenu_pages) {
        // debug($submenu_pages);

        array_push($submenu_pages, array(
            'page_title'    => __( "Search & Replace", 'greyd_hub' ),
            'menu_title'    => __( "Search & Replace", 'greyd_hub' ),
            'cap'           => 'manage_options',
            'slug'          => $this->menu_slug,
            'callback'      => array( $this, 'render_page' ),
            'position'      => 40,
        ));

        return $submenu_pages;
    }

    /**
     * add scripts
     */
    public function load_backend_scripts() {

        if ( isset($_GET['page']) && $_GET['page'] === $this->menu_slug ) {

            $css_uri = plugin_dir_url(__FILE__).'assets/css';
            $js_uri = plugin_dir_url(__FILE__).'assets/js';

            // css
            wp_register_style($this->config->plugin_name."_search-and-replace_css", $css_uri.'/search-and-replace.css', null, GREYD_VERSION, 'all');
            wp_enqueue_style($this->config->plugin_name."_search-and-replace_css");
        }

    }


    /**
     * =================================================================
     *                          Render
     * =================================================================
     */

    /**
     * Render the page
     */
    public function render_page() {

        // start of wrapper
        echo "<div class='wrap search-and-replace'><h1>".get_admin_page_title()."</h1>";

        $this->undo_trash_post();

        $mode       = null;
        $do_replace = false;

        if ( !empty($_POST) ) {

            if ( !isset($_POST['_nonce']) || wp_verify_nonce( $_POST['_nonce'], $this->menu_slug ) !== 1 ) {
                self::log( __("Your request could not be verified.", 'greyd_hub'), "error" );
            }
            else {
                $mode = esc_attr($_POST['search-mode']);
                $do_replace = isset($_POST['do_replace']) && $_POST['do_replace'] === "true";
            }
        }

        if ( $mode === "posts" ) {
            $this->render_verify_posts_screen( $do_replace );
        }
        else if ( $mode === "terms" ) {
            $this->render_verify_terms_screen( $do_replace );
        }
        else if ( $mode === "regex" ) {
            // under construction
        }

        if ( empty($mode) ) {
            $this->render_default_screen();
        }

        // end of wrapper
        echo "</div>";
    }

    /**
     * Render the default form.
     */
    public function render_default_screen() {

        $current_mode = isset($_GET['searchmode']) ? esc_attr($_GET['searchmode']) : 'posts';

        $search_modes = array(
            array(
                'value' => 'posts',
                'icon'  => 'admin-post',
                'title' => __( 'Posts', 'greyd_hub' ),
                'text'  => __( "Automatically replace posts across your entire site.", 'greyd_hub' ),
                'callback' => array($this, 'render_mode_posts')
            ),
            array(
                'value' => 'terms',
                'icon'  => 'tag',
                'title' => __( "Taxonomies", 'greyd_hub' ),
                'text'  => __( "Automatically exchange categories and keywords.", 'greyd_hub' ),
                'callback' => array($this, 'render_mode_terms')
            ),
            array(
                'value' => 'regex',
                'icon'  => 'code-standards',
                'title' => __( "Manually", 'greyd_hub' ),
                'text'  => __( "Search for occurrences of manual input and replace them.", 'greyd_hub' ),
                'callback' => array($this, 'render_mode_regex')
            ),
        );
        echo "<form method='post' class='settings_wrap'>
            <input type='hidden' name='_nonce' value='".wp_create_nonce($this->menu_slug)."' />";
        
        // mode
        echo "<fieldset class='search-replace-mode'>";
        foreach ( $search_modes as $mode ) {
            echo "<input type='radio' name='search-mode' id='{$mode['value']}' value='{$mode['value']}'
                ".( $current_mode == $mode['value'] ? "checked" : "" )."
                onchange='changeSearchMode(this)'>
                <label for='{$mode['value']}'>
                    <span class='dashicons dashicons-{$mode['icon']}'></span>
                    <div>
                        <strong>{$mode['title']}</strong>
                        <small>{$mode['text']}</small>
                    </div>
                </label>";
        }
        echo "</fieldset>";

        // content
        foreach ( $search_modes as $mode ) {
            echo "<div class='search-mode_{$mode['value']} ".( $current_mode == $mode['value'] ? "" : "hidden" )."'>";
            call_user_func( $mode['callback'] );
            echo "</div>";
        }

        // end of form
        echo "</form>";

        ?>
        <script>
            function changeSearchMode( elem ) {
                var mode = elem.value;
                greyd.backend.toggleRadioByClass('search-mode', mode);
                var location = window.location.search
                if ( location.indexOf('searchmode=') == -1 ) {
                    location += '&searchmode=' + mode;
                } else {
                    var regex = new RegExp('(^|\\?|&)(searchmode)=.*?(&|#|$)', 'g');
                    location = location.replace( regex, '$1$2='+mode+'$3' );
                }
                console.log(location);
                window.history.replaceState( null, null, location );
            }
        </script>
        <?php
    }

    /**
     * Render the post replace form.
     */
    public function render_mode_posts() {

        $supported_post_types   = self::get_supported_post_types();
        $all_posts              = self::get_all_posts();
        $selected_post_type     = "dynamic_template";
        
        echo "<table class='form-table'>
            <tbody>
                <tr>
                    <th>
                        ".__( "Post Type", 'greyd_hub' )."
                    </th>
                    <td>";
                    foreach ( $supported_post_types as $post_type => $args ) {

                        $disabled = false;

                        // no posts found
                        if ( !isset($all_posts[$post_type]) ) {
                            $disabled = true;
                            unset($supported_post_types[$post_type]);
                        }

                        $selected = $selected_post_type == $post_type;
                        echo "<label for='{$post_type}' class='".($disabled ? "disabled" : "")."'>
                            <input type='radio' id='{$post_type}' name='posts[type]' value='{$post_type}' ".($selected ? "checked" : "")."
                                onchange='greyd.backend.toggleRadioByClass(\"posts-search-type\", \"{$post_type}\")'>
                            <span>{$args['plural']}</span>
                        </label><br>";
                    }
                    echo "</td>
                </tr>
                <tr>
                    <th>
                        ".__( 'Posts', 'greyd_hub' )."
                    </th>
                    <td>";
                    foreach ( $supported_post_types as $post_type => $args ) {
                        $selected = $selected_post_type == $post_type;
                        $label = $args['singular'];
                        echo "<div class='posts-search-type_{$post_type} ".($selected ? "" : "hidden")."'>
                            <select name='posts[{$post_type}][search]'>
                                <option value='0'>".sprintf(__( "Select %s", 'greyd_hub' ), $label)."</option>";
                                foreach( $all_posts[$post_type] as $post_id => $post ) {
                                    $sel = isset($_POST["delete_{$post_type}"]) && $_POST["delete_{$post_type}"] == $post_id;
                                    echo "<option value='{$post_id}' ".($sel ? "selected" : "").">{$post->post_title} (#{$post_id})</option>";
                                }
                            echo "</select>
                            <br><br>".__( "Will be replaced by:", 'greyd_hub' )."<br><br>
                            <select name='posts[{$post_type}][replace]'>
                                <option value='0'>".sprintf(__( "Select %s", 'greyd_hub' ), $label)."</option>";
                                foreach( $all_posts[$post_type] as $post_id => $post ) {
                                    $sel = isset($_POST["replace_{$post_type}"]) && $_POST["replace_{$post_type}"] == $post_id;
                                    echo "<option value='{$post_id}' ".($sel ? "selected" : "").">{$post->post_title} (#{$post_id})</option>";
                                }
                            echo "</select>
                        </div>";
                    }
                    echo "</td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type='submit' name='submit' class='button button-primary huge'>".__( "Search & Review", 'greyd_hub' )."</button><br><br>";

        echo Helper::render_info_box(array(
            'text' => __("No changes will be made yet. Take your time to check everything.", 'greyd_hub'),
            'style' => 'info'
        ));
    }

    /**
     * Render the term replace form.
     */
    public function render_mode_terms() {

        $all_taxonomy_terms = self::get_all_taxonomy_terms();
        
        echo "<table class='form-table'>
            <tbody>
                <tr>
                    <th>
                        ".__( "Post Type", 'greyd_hub' )."
                    </th>
                    <td>
                        <div>
                            <select name='terms[type]' onchange='greyd.backend.toggleRadioByClass(\"terms-search-type\", this.value)'>";
                                foreach ( $all_taxonomy_terms as $i => $args ) {
                                    $disabled = $args['empty'] ? "disabled" : "";
                                    echo "<option value='{$args['name']}' {$disabled}>{$args['label']}</option>";
                                }
                            echo "</select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        ".__( "Taxonomy", 'greyd_hub' )."
                    </th>
                    <td>";
                    foreach ( $all_taxonomy_terms as $i => $args ) {
                        $hidden = $i == 0 ? "" : "hidden";
                        echo "<div class='terms-search-type_{$args['name']} {$hidden}'>
                            <select name='terms[{$args['name']}][taxonomy]' onchange='greyd.backend.toggleRadioByClass(\"terms-taxonomy\", this.value)'>";
                                foreach ( $args['taxonomies'] as $taxonomy ) {
                                    $disabled = empty($taxonomy['terms']) ? "disabled" : "";
                                    echo "<option value='{$taxonomy['name']}' {$disabled}>{$taxonomy['label']}</option>";
                                }
                            echo "</select>
                        </div>";
                    }
                    echo "</td>
                </tr>";
                foreach ( $all_taxonomy_terms as $i => $args ) {
                    $hidden = $i == 0 ? "" : "hidden";
                    echo "<tr class='terms-search-type_{$args['name']} {$hidden}'>";
                    foreach ( $args['taxonomies'] as $n => $taxonomy ) {
                        $hidden = $n == 0 ? "" : "hidden";
                        echo "<th class='terms-taxonomy_{$taxonomy['name']} {$hidden}'>{$taxonomy['label']}</th>
                        <td class='terms-taxonomy_{$taxonomy['name']} {$hidden}'>
                            <div>
                                <select name='terms[{$args['name']}][{$taxonomy['name']}_search]'>";
                                    foreach ( $taxonomy['terms'] as $term ) {
                                        echo "<option value='{$term['ID']}'>{$term['name']} ({$term['count']})</option>";
                                    }
                                echo "</select>
                                <br><br>".__( "Will be replaced by:", 'greyd_hub' )."<br><br>
                                <select name='terms[{$args['name']}][{$taxonomy['name']}_replace]'>";
                                    foreach ( $taxonomy['terms'] as $term ) {
                                        echo "<option value='{$term['ID']}'>{$term['name']} ({$term['count']})</option>";
                                    }
                                echo "</select>
                            </div>
                        </td>";
                    }
                    echo "</tr>";
                }
            echo "</tbody>
        </table>
        <br>
        <button type='submit' name='submit' class='button button-primary huge'>".__( "Search & Review", 'greyd_hub' )."</button><br><br>";

        echo Helper::render_info_box(array(
            'text' => __("No changes will be made yet. Take your time to check everything.", 'greyd_hub'),
            'style' => 'info'
        ));
    }

    /**
     * Render the manual replace form.
     */
    public function render_mode_regex() {
        echo "<table class='form-table'>
            <tbody>
                <tr>
                    <th>
                        ".__( "Search", 'greyd_hub' )."
                    </th>
                    <td>
                        <textarea name='regex[search]' placeholder='<!-- wp:block {\"ref\":\"42\"'></textarea>
                    </td>
                </tr>
                <tr>
                    <th>
                        ".__( "Replace", 'greyd_hub' )."
                    </th>
                    <td>
                        <textarea name='regex[replace]' placeholder='<!-- wp:block {\"ref\":\"69\"'></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type='submit' name='submit' class='button button-primary huge' disabled>".__( "Search & Review", 'greyd_hub' )."</button><br><br>";
        echo Helper::render_info_box(array(
            'text' => __("Unfortunately, this mode is still under development and cannot be used yet.", 'greyd_hub'),
            'style' => 'warning'
        ));
    }

    /**
     * Render the verfify form for posts.
     */
    public function render_verify_posts_screen( $do_replace ) {

        $mode               = "posts";
        $data               = $_POST[$mode];
        $selected_post_type = isset($data["type"]) ? esc_attr($data["type"]) : "dynamic_template";
        $search_post_id     = isset($data[$selected_post_type]['search']) ? intval($data[$selected_post_type]['search']) : 0;
        $replace_post_id    = isset($data[$selected_post_type]['replace']) ? intval($data[$selected_post_type]['replace']) : 0;
        $searched_post      = get_post( $search_post_id );

        if ( ! $search_post_id || !$replace_post_id || !$searched_post ) {
            $this->notice(
                __( "The selected data is invalid, please try again.", 'greyd_hub' ),
                "error"
            );
            echo "<a class='button button-primary' href='".remove_query_arg( 'doaction' )."'>".__( "Try again", 'greyd_hub' )."</a>";
            return;
        }

        // perform the action
        $result = self::search_and_replace_post_id( $search_post_id, $replace_post_id, $do_replace );

        if ( $do_replace ) {

            // trash post
            $trash_post = isset($_POST['do_trash']) && $_POST['do_trash'] == "true";
            if ( $trash_post ) {
                $trashed = boolval( wp_trash_post( $search_post_id ) );
                if ( $trashed ) {
                    $this->notice( sprintf(
                        __("The post '%s' was moved to the trash.", 'greyd_hub'),
                        $searched_post->post_title
                    )." <a href='".add_query_arg( array(
                        'doaction'  => 'untrash',
                        'id'        => $search_post_id
                    ) )."'>".__("Revert", 'greyd_hub')."</a>" );
                } else {
                    self::log( sprintf(
                        __("The post '%s' could not be moved to the trash.", 'greyd_hub'),
                        $searched_post->post_title
                    ), "error" );
                }
            }

            
            echo "<h3>".sprintf(
                __( "All places where the post %s was used have been replaced:", 'greyd_hub' ),
                "<a href='".get_edit_post_link($searched_post->ID)."' target='_blank'>".$searched_post->post_title."</a>"
            )."</h3>";
        }

        if ( !$do_replace ) {
            echo "<h3>".sprintf(
                __( "Searching for places where the post %s is used:", 'greyd_hub' ),
                "<a href='".get_edit_post_link($searched_post->ID)."' target='_blank'>".$searched_post->post_title."</a>"
            )."</h3>";
        }

        // echo the logs
        self::render_logs();

        if ( $do_replace || !$result ) {
            echo "<a class='button button-primary huge' href='".remove_query_arg( 'doaction' )."'>".__( "Start next search", 'greyd_hub' )."</a>";
        }
        else {

            $replace_post = get_post( $replace_post_id );
            echo "<br><h3>".sprintf(
                __( "All listed places will be replaced with the post %s.", 'greyd_hub' ),
                "<a href='".get_edit_post_link($replace_post->ID)."' target='_blank'>".$replace_post->post_title."</a>"
            )."</h3>";
    
            echo "<form method='post'>
                <input type='hidden' name='_nonce' value='".wp_create_nonce($this->menu_slug)."' />
                <input type='hidden' name='search-mode' value='$mode' />
                <input type='hidden' name='posts[type]' value='$selected_post_type' />
                <input type='hidden' name='posts[{$selected_post_type}][search]' value='$search_post_id' />
                <input type='hidden' name='posts[{$selected_post_type}][replace]' value='$replace_post_id' />
                <input type='hidden' name='do_replace' value='true' />
                <p>".__( "Additional options:", 'greyd_hub' )."</p>
                <label for='do_trash'>
                    <input type='checkbox' id='do_trash' name='do_trash' value='true'>
                    <span>".__( "Move the searched post to the trash.", 'greyd_hub' )."</span>
                </label><br>
                <br><br>
                <div class='submit-buttons'>
                    <button type='submit' name='submit' class='button button-primary large'>".__( "Replace now", 'greyd_hub' )."</button>
                    <a class='button button-ghost' href='".remove_query_arg( 'doaction' )."'>".__( "Cancel", 'greyd_hub' )."</a>
                </div>
                <p style='color:darkred'>".__("This action cannot be made undone!", 'greyd_hub')."</p>
            </form>";
        }
    }

    /**
     * Render the verfify form for posts.
     */
    public function render_verify_terms_screen( $do_replace ) {

        $mode               = "terms";
        $data               = $_POST[$mode];
        $post_type          = isset($data["type"]) ? esc_attr($data["type"]) : "post";
        $selected_data      = isset($data[$post_type]) ? $data[$post_type] : array();
        $taxonomy_name      = isset($selected_data['taxonomy']) ? esc_attr($selected_data['taxonomy']) : "";
        $search_term_id     = isset($selected_data[$taxonomy_name.'_search']) ? intval($selected_data[$taxonomy_name.'_search']) : 0;
        $replace_term_id    = isset($selected_data[$taxonomy_name.'_replace']) ? intval($selected_data[$taxonomy_name.'_replace']) : 0;

        if ( !$search_term_id || !$replace_term_id || empty($taxonomy_name) || $search_term_id == $replace_term_id ) {
            $this->notice(
                __( "The selected data is invalid, please try again.", 'greyd_hub' ),
                "error"
            );
            echo "<a class='button button-primary' href='".remove_query_arg( 'doaction' )."'>".__( "Try again", 'greyd_hub' )."</a>";
            return;
        }

        $searched_term_name = get_term( $search_term_id , $taxonomy_name )->name;
        $replace_term_name  = get_term( $replace_term_id , $taxonomy_name )->name;

        // do the action
        $result = self::search_and_replace_term_ids( $post_type, $taxonomy_name, $search_term_id, $replace_term_id, $do_replace );

        if ( $do_replace ) {

            // trash term
            $trash_term = isset($_POST['do_trash']) && $_POST['do_trash'] == "true";
            if ( $trash_term ) {
                $trashed = boolval( wp_delete_term( $search_term_id, $taxonomy_name ) );
                if ( $trashed ) {
                    self::log( sprintf(
                        __("The term %s was deleted.", 'greyd_hub'),
                        "<u>$searched_term_name</u>"
                    ), "success" );
                } else {
                    self::log( sprintf(
                        __("The term %s could not be deleted.", 'greyd_hub'),
                        "<u>$searched_term_name</u>"
                    ), "error" );
                }
            }

            echo "<h3>".sprintf(
                __( "All places where the term %s was used have been replaced:", 'greyd_hub' ),
                "<u>$searched_term_name</u>"
            )."</h3>";
        }

        if ( !$do_replace ) {
            echo "<h3>".sprintf(
                __( "Searching for places where %s is used:", 'greyd_hub' ),
                "<u>$searched_term_name</u>"
            )."</h3>";
        }

        // echo the logs
        self::render_logs();

        if ( $do_replace || !$result ) {
            echo "<a class='button button-primary huge' href='".remove_query_arg( 'doaction' )."'>".__( "Start next search", 'greyd_hub' )."</a>";
        }
        else {

            echo "<br><h3>".__( "All selected places have been successfully replaced.", 'greyd_hub' )."</h3>";
    
            echo "<form method='post'>
                <input type='hidden' name='_nonce' value='".wp_create_nonce($this->menu_slug)."' />
                <input type='hidden' name='search-mode' value='$mode' />
                <input type='hidden' name='terms[type]' value='$post_type' />
                <input type='hidden' name='terms[{$post_type}][taxonomy]' value='$taxonomy_name' />
                <input type='hidden' name='terms[{$post_type}][{$taxonomy_name}_search]' value='$search_term_id' />
                <input type='hidden' name='terms[{$post_type}][{$taxonomy_name}_replace]' value='$replace_term_id' />
                <input type='hidden' name='do_replace' value='true' />
                <p>".__( "Additional options:", 'greyd_hub' )."</p>
                <label for='do_trash'>
                    <input type='checkbox' id='do_trash' name='do_trash' value='true'>
                    <span>".__( "Delete the searched taxonomy term. This cannot be made undone. Make a backup before that.", 'greyd_hub' )."</span>
                </label><br>
                <br><br>
                <div class='submit-buttons'>
                    <button type='submit' name='submit' class='button button-primary large'>".__( "Replace now", 'greyd_hub' )."</button>
                    <a class='button button-ghost' href='".remove_query_arg( 'doaction' )."'>".__( "Cancel", 'greyd_hub' )."</a>
                </div>
                <p style='color:darkred'>".__("This action cannot be made undone!", 'greyd_hub')."</p>
            </form>";
        }
    }


    /**
     * =================================================================
     *                          Actions
     * =================================================================
     */

    /**
     * Search and replace a post ID.
     * 
     * @param int $search_post_id   Post ID to search for.
     * @param int $replace_post_id  Post ID to replace the searched ID with.
     * @param bool $do_replace      Whether we actually do the replace.
     * 
     * @return bool
     */
    public static function search_and_replace_post_id( $search_post_id, $replace_post_id, $do_replace=true ) {

        // return if IDs not set or if they are the same
        if ( !$search_post_id || !$replace_post_id || $search_post_id == $replace_post_id ) return false;

        $searched_post = get_post( $search_post_id );

        if ( !$searched_post || !isset($searched_post->post_type) ) return false;


        // get post type
        $post_type = $searched_post->post_type;
        if ( !isset(self::get_supported_post_types()[$post_type]) ) {
            self::log( sprintf(
                __("The post type '%s' is not supported by %s.", 'greyd_hub'),
                $post_type,
                "<strong>".__("Search & Replace", 'greyd_hub')."</strong>"
            ), "error" );
            return false;
        }

        // get homepage
        $homepage_post    = get_post( get_option('page_on_front') );

        // get patterns
        $all_patterns       = get_nested_post_patterns( $search_post_id, $homepage_post ? $homepage_post : $searched_post );
        $allowed_patterns   = self::get_supported_post_types()[$post_type]['patterns'];
        $patterns = array_filter(
            $all_patterns,
            function ($key) use ($allowed_patterns) {
                return in_array($key, $allowed_patterns);
            },
            ARRAY_FILTER_USE_KEY
        );

        // attachments are working a bit different...
        if ( $post_type === "attachment" ) {

            // replace url in contents
            $search_url = wp_get_attachment_image_url( $search_post_id );
            if ( $search_url ) {
                $search_path = preg_quote( str_replace( wp_upload_dir()["baseurl"], "", $search_url), '/' );

                $replace_url = wp_get_attachment_image_url( $replace_post_id );
                if ( $replace_url ) {
                    $replace_path = preg_quote( str_replace( wp_upload_dir()["baseurl"], "", $replace_url ), '/' );

                    $patterns['attachment_url'] = array(
                        'search'  => array( $search_path ),
                        'replace' => array( $replace_path ),
                        'group'   => 1
                    );
                }
            }

            // replace thumbnails
            $thumbnail_posts = get_posts( array(
                'post_type'     => Post_Export_Helper::get_supported_post_types(),
                'numberposts'   => -1,
                'post_status'   => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'value' => $search_post_id
                    )
                ) 
            ) );
            if ( $thumbnail_posts ) {
                self::log( __("Found posts using the image as a thumbnail:", 'greyd_hub'), "head" );

                foreach( $thumbnail_posts as $thumbnail_post ) {
                    if ( !$do_replace ) {
                        self::log( sprintf(
                            __("The thumbnail ID %s of the post %s will be replaced with %s.", 'greyd_hub'),
                            "<mark>$search_post_id</mark>",
                            "<a href='".get_edit_post_link($thumbnail_post->ID)."' target='_blank'>".$thumbnail_post->post_title."</a>",
                            "<mark>$replace_post_id</mark>"
                        ) );
                    }
                    else {
                        set_post_thumbnail($thumbnail_post->ID, $replace_post_id);
                        self::log( sprintf(
                            __("The thumbnail ID %s of the post %s was replaced with %s.", 'greyd_hub'),
                            "<mark>$search_post_id</mark>",
                            "<a href='".get_edit_post_link($thumbnail_post->ID)."' target='_blank'>".$thumbnail_post->post_title."</a>",
                            "<mark>$replace_post_id</mark>"
                        ) );
                    }
                }
                self::log("<hr>", "separator");
            }

            // replace files in dynamic meta
            $meta_image_posts = get_posts( array(
                'post_type'     => Post_Export_Helper::get_supported_post_types(),
                'numberposts'   => -1,
                'post_status'   => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
                'meta_query' => array(
                    array(
                        'key' => 'dynamic_meta',
                        'value' => ':"'.$search_post_id.'";',
                        'compare' => 'LIKE'
                    )
                ) 
            ) );
            if ( $meta_image_posts ) {
                self::log( __("Found posts using the image in a dynamic field:", 'greyd_hub'), "head" );

                foreach( $meta_image_posts as $meta_image_post ) {

                    $update = false;
                    $values = Posttypes::get_dynamic_meta($meta_image_post->ID);
                    foreach( (array) $values as $name => $value ) {
                        if ( $value == $search_post_id ) {
                            if ( !$do_replace ) {
                                self::log( sprintf(
                                    __("For the post %s, the value of the field %s will be replaced by %s with %s", 'greyd_hub'),
                                    "<a href='".get_edit_post_link($meta_image_post->ID)."' target='_blank'>".$meta_image_post->post_title."</a>",
                                    "<code>$name</code>",
                                    "<mark>$search_post_id</mark>",
                                    "<mark>$replace_post_id</mark>"
                                ) );
                            }
                            else {
                                $values[$name] = $replace_post_id;
                                Posttypes::update_dynamic_meta($meta_image_post->ID, $values);
                                self::log( sprintf(
                                    __("On the post %s, the value of the field %s was changed from %s to %s", 'greyd_hub'),
                                    "<a href='".get_edit_post_link($meta_image_post->ID)."' target='_blank'>".$meta_image_post->post_title."</a>",
                                    "<code>$name</code>",
                                    "<mark>$search_post_id</mark>",
                                    "<mark>$replace_post_id</mark>"
                                ) );
                            }
                        }
                    }
                }
                self::log("<hr>", "separator");
            }
        }
        
        // do the action
        $result = self::match_post_contents_with_patterns(
            $search_post_id,
            $replace_post_id,
            $patterns,
            $do_replace,
            $post_type
        );

        return true;
    }

    /**
     * Search and replace a term ID.
     * 
     * (1) Replace the term connection for posts connected to the term.
     * (2) Look for references to the term in post contents.
     * 
     * @param string $post_type     Post type slug of the taxonomy.
     * @param string $taxonomy_name Taxonomy name of the term.
     * @param int $term_id          Term ID to search for.
     * @param int $replace_term_id  Term ID to replace it.
     * @param bool $do_replace      Whether we actually do the replace.
     * 
     * @return bool
     */
    public static function search_and_replace_term_ids( $post_type, $taxonomy_name, $search_term_id, $replace_term_id, $do_replace=true ) {

        $searched_term_name = get_term( $search_term_id , $taxonomy_name )->name;
        $replace_term_name  = get_term( $replace_term_id , $taxonomy_name )->name;

        // (1) look for posts associated with the term
        $the_query = new \WP_Query( array(
            'post_type' => $post_type,
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy_name,
                    'field' => 'term_id',
                    'terms' => $search_term_id
                )
            )
        ) );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $post_id = get_the_ID();

                if ( !$do_replace ) {
                    self::log( sprintf(
                        __('Post %1$s will be linked to %3$s instead of %2$s.', 'greyd_hub'),
                        "<a href='".get_edit_post_link($post_id)."' target='_blank'>".get_the_title()."</a>",
                        "<mark>$searched_term_name</mark>",
                        "<mark>$replace_term_name</mark>"
                    ) );
                }
                else {
                    wp_remove_object_terms( $post_id, $searched_term_name, $taxonomy_name );
                    wp_add_object_terms( $post_id, $replace_term_id, $taxonomy_name );
                    self::log( sprintf(
                        __('Post %1$s was linked to %3$s instead of %2$s.', 'greyd_hub'),
                        "<a href='".get_edit_post_link($post_id)."' target='_blank'>".get_the_title()."</a>",
                        "<mark>$searched_term_name</mark>",
                        "<mark>$replace_term_name</mark>"
                    ) );
                }
            }
        } else {
            // no posts found
            self::log( sprintf(
                __("No posts were found using '%s' as a term.", 'greyd_hub'),
                $searched_term_name
            ) );
        }

        self::log("<hr>", "separator");

        // (2) Get all posts that have a reference to this post
        $result = self::match_post_contents_with_patterns(
            $search_term_id,
            $replace_term_id,
            get_nested_term_patterns( null, null ),
            $do_replace
        );

        return true;
    }

    /**
     * Look for posts that match the patterns imploded by $search_id and
     * replace the matches with the $replace_id.
     * 
     * @param int $search_id        The ID to implode the patterns with. Usually a post ID.
     * @param int $replace_id       The ID to replace the $search_id.
     * @param array $patterns       @see get_nested_post_patterns() for details.
     * @param bool $do_replace      Whether we actually do the replace.
     * @param string $post_type     Post type slug for attachment support.
     */
    public static function match_post_contents_with_patterns( $search_id, $replace_id, $patterns, $do_replace=true, $post_type=null ) {

        // doing it wrong
        if ( !$search_id || !$replace_id || !is_array($patterns) ) {
            return false;
        }

        // get all posts that have a reference to this post
        $posts = self::get_all_posts_by_patterns( $search_id, $patterns );

        if ( ! count($posts) ) {
            self::log( sprintf(
                __("No posts found that use the ID %s.", 'greyd_hub'),
                "<code>$search_id</code>"
            ), "head" );
            return false;
        }
        else {
    
            // loop through posts and update their content
            foreach( $posts as $post ) {

                self::log( sprintf(
                    __("In the content of the post %s:", 'greyd_hub'),
                    "<a href='".get_edit_post_link($post->ID)."' target='_blank'>".$post->post_title."</a>"
                ), "head" );

                // get the content
                $new_content = $post->post_content;
                // debug( esc_attr($new_content) );
    
                foreach ( $patterns as $key => $pattern ) {
        
                    // doing it wrong
                    if (
                        !isset( $pattern['search'] ) ||
                        !is_array( $pattern['search'] ) ||
                        !isset( $pattern['replace'] ) ||
                        !is_array( $pattern['replace'] )
                    ) {
                        continue;
                    }
        
                    // implode the regex with the post ID
                    $search_regex = '/'.implode( '('.$search_id.')', $pattern['search'] ).'/';
                    // debug( esc_attr($search_regex) );

                    $replace_string = implode( '{{'.$replace_id.'}}', $pattern['replace'] );
                    // debug( esc_attr($replace_string) );

                    // see if we have something to replace...
                    if ( preg_match($search_regex, $new_content, $matches) ) {

                        // log search
                        if ( ! $do_replace ) {
                            $match = $matches[0];
                            // attachment url regex
                            if ( $post_type === "attachment" && count($pattern['search']) == 1 ) {
                                self::log( sprintf(
                                    __("The URL %s will be replaced with %s.", 'greyd_hub'),
                                    "<code>".esc_attr($match)."</code>",
                                    "<mark>".esc_attr(stripslashes($pattern['replace'][0]))."</mark>"
                                ) );
                            }
                            // normal regex
                            else {
                                if ( strpos($match, strval($search_id)) !== false ) {
                                    $marked_match = str_replace( $search_id, "<mark>$search_id</mark>", esc_attr($match) );
                                    self::log( sprintf(
                                        __("The mark in %s will be replaced with %s.", 'greyd_hub'),
                                        "<code>$marked_match</code>",
                                        "<mark>".esc_attr($replace_id)."</mark>"
                                    ) );
                                }
                            }
                        }
                    
                        // replace in post content
                        // we do this even when we're not actually updating the post, to prevent
                        // multiple matches for the same ID.
                        $new_content = preg_replace( $search_regex, $replace_string, $new_content );
                        $new_content = str_replace( '{{'.$replace_id.'}}', strval($replace_id), $new_content );
                        // debug( esc_attr($new_content) );
                        
                        // log replace
                        if ( $do_replace ) {
                            self::log( sprintf(
                                __("The place %s was overwritten with %s.", 'greyd_hub'),
                                "<code>".esc_attr(stripslashes(implode($search_id, $pattern['search'])))."</code>",
                                "<mark>".esc_attr(stripslashes(str_replace( '{{'.$replace_id.'}}', strval($replace_id), $replace_string)))."</mark>"
                            ) );
                        }
                    }
                }

                // update post
                if ( $do_replace ) {

                    $result = wp_update_post( array(
                        'ID' => $post->ID,
                        'post_content' => $new_content
                    ) );
    
                    if ( is_wp_error( $result ) ) {
                        self::log( $result->get_error_message(), "error" );
                    }
                    else if ( !$result ) {
                        self::log( __("The post could not be updated.", 'greyd_hub'), "error" );
                    }
                    else {
                        self::log( __("Post was successfully updated.", 'greyd_hub'), "success" );
                    }
                }

                self::log("<hr>", "separator");
            }
        }
    }

    /**
     * Get all posts by matching post_content with MySQL REGEX.
     * 
     * @param int   $post_id    WP_Post ID.
     * @param array $patterns   @see get_nested_post_patterns()
     * 
     * @return WP_Post[]        ID, post_type, post_title, post_content, post_name
     */
    public static function get_all_posts_by_patterns( $post_id, $patterns ) {
        global $wpdb;

        $sql_patterns = array();

        foreach ( $patterns as $key => $pattern ) {

            // doing it wrong
            if (
                !isset( $pattern['search'] ) ||
                !is_array( $pattern['search'] )
            ) {
                continue;
            }

            // implode the regex with the post ID
            $regex = implode( '('.$post_id.')', $pattern['search'] );
            // debug( esc_attr($regex), true );

            /**
             * MySQL regexes don't support using the question mark ? as a non-greedy (lazy) modifier
             * to the star and plus quantifiers like PCRE (Perl Compatible Regular Expressions).
             * This means you can't use +? and *?
             * 
             * @see https://dev.mysql.com/doc/refman/8.0/en/regexp.html
             * 
             * Additionally, we need to further escape string like '\[', '\|' etc.
             */
            $regex = str_replace( array(
                '*?', '+?', '\|'   , '[^\[\]]', '\]'   , '\\['
            ), array(
                '*' , '+' , '\\\\|', '.'      , '\\\\]', '\\\\['
            ), $regex );
            
            $sql = "post_content REGEXP '$regex'";
            // debug( esc_attr($sql), true );

            $sql_patterns[$key] = $sql;
        }

        $results = $wpdb->get_results(
           "SELECT ID, post_type, post_title, post_content, post_name
            FROM {$wpdb->posts} 
            WHERE ( post_type = '".implode( "' OR post_type = '", Post_Export_Helper::get_supported_post_types() )."' )
            AND (".implode( " OR ", $sql_patterns ).")",
            OBJECT
        );
        return $results;
    }


    /**
     * =================================================================
     *                          Utils
     * =================================================================
     */

    /**
     * Get all supported post types.
     * 
     * @param string $mode (posts|terms)
     */
    public static function get_supported_post_types( $mode="posts" ) {
        if ( $mode == "terms" ) {
            $exclude = array( 'page', 'tp_posttypes', 'greyd_popup', 'tp_forms' );
            return array_diff( Post_Export_Helper::get_supported_post_types(), $exclude );
        }
        // $mode == posts
        return array(
            'dynamic_template' => array(
                'plural' => __('Dynamic Templates', 'greyd_hub'),
                'singular' => __('Dynamic Template', 'greyd_hub'),
                'patterns' => array(
                    'greyd/dynamic',
                    'template',
                    'dynamic_content',
                    'dynamic_dynamic_content'
                )
            ),
            'tp_forms' => array(
                'plural' => __("Forms", 'greyd_hub'),
                'singular' => __("Form", 'greyd_hub'),
                'patterns' => array(
                    'greyd/form',
                    'dynamic/greyd/form',
                    'vc_form',
                    'dynamic_form'
                )
            ),
            'attachment' => array(
                'plural' => __("Media & files", 'greyd_hub'),
                'singular' => __("Image/file", 'greyd_hub'),
                'patterns' => array(
                    'core/image',
                    'core/image-class',
                    'core/cover',
                    'core/media-text',
                    'core/file',
                    'greyd/image',
                    'greyd/list',
                    'greyd/list-item',
                    'greyd/background',
                    'greyd/background-pattern',
                    'dynamic/core/image',
                    'dynamic/greyd/image',
                    'dynamic/greyd/background',
                    'greyd-forms/iconpanel-normal',
                    'greyd-forms/iconpanel-hover',
                    'greyd-forms/iconpanel-active',
                    'vc_icons',
                    'vc_image',
                    'dynamic_image',
                    'icon_panel',
                    'icon_panel_hover',
                    'icon_panel_select'
                )
            ),
            'greyd_popup' => array(
                'plural' => __("Pop-ups", 'greyd_hub'),
                'singular' => __("Pop-up", 'greyd_hub'),
                'patterns' => array(
                    'greyd/popup',
                    'dynamic/greyd/popup',
                    'popup_button',
                    'popup_contentbox'
                )
            ),
        );
    }

    /**
     * Get all posts of supported post types grouped by post type.
     * 
     * @return array( WP_Post[] )
     */
    public static function get_all_posts() {

        if ( self::$all_posts ) return self::$all_posts;

        $all_posts = array();

        $found_posts = get_posts(array(
            'numberposts'       => '-1',
            'post_type'         => array_keys(self::get_supported_post_types()),
            'post_status'       => array('publish', 'inherit'),
            'suppress_filters'  => false
        ));
        if ( $found_posts ) {
            foreach( $found_posts as $post ) {
                $post_type = $post->post_type;
    
                if ( !isset( $all_posts[$post_type] ) ) {
                    $all_posts[$post_type] = array();
                }
    
                $all_posts[$post_type][$post->ID] = $post;
            }
        }

        self::$all_posts = $all_posts;

        return $all_posts;
    }

    /**
     * Get all terms of all supported posttype taxonomies.
     * 
     * @return array    Terms keyed by taxonomy keyed by post type.
     */
    public static function get_all_taxonomy_terms() {

        $all_taxonomy_terms = array();
        $supported_post_types = self::get_supported_post_types('terms');

        foreach ( $supported_post_types as $post_type ) {

            if ( $post_type === 'page' ) continue;

            $args = array(
                'name'          => $post_type,
                'label'         => get_post_type_object($post_type)->labels->singular_name,
                'taxonomies'    => array(),
                'empty'         => true
            );
            
            $taxonomies = get_object_taxonomies( $post_type, 'objects' );
            if ( !empty($taxonomies) ) {
                foreach( $taxonomies as $tax_name => $taxonomy ) {

                    if ( $tax_name == 'template_type' && $post_type == 'dynamic_template' ) continue;

                    $terms = array();

                    $the_terms = get_terms( array(
                        'taxonomy'   => $tax_name,
                        'hide_empty' => false
                    ) );
                    if ( !empty($the_terms) ) {
                        foreach( $the_terms as $term ) {
                            $terms[] = array(
                                'ID'    => $term->term_id,
                                'name'  => $term->name,
                                'count' => $term->count
                            );
                        }
                        $args['empty'] = false;
                    }

                    $args['taxonomies'][] = array(
                        'name'  => $taxonomy->name,
                        'label' => $taxonomy->label,
                        'terms' => $terms
                    );
                }
            }

            $all_taxonomy_terms[] = $args;
        }

        return $all_taxonomy_terms;
    }

    /**
     * Undo trashing of a post.
     */
    public function undo_trash_post() {
        if ( isset($_GET['doaction']) && isset($_GET['id']) && $_GET['doaction'] == 'untrash' ) {
            $result = wp_untrash_post($_GET['id']);
            if ( $result != false ) {
                $this->notice( __("The post has been restored", 'greyd_hub') );
            }
        }
    }

    /**
     * Display an admin notice.
     */
    public function notice( $text, $class="success" ) {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            $class,
            $text
        );
    }

    /**
     * Add a log.
     */
    public static function log( $text, $class="info" ) {
        self::$logs[] = array(
            "text" => $text,
            "class" => $class
        );
    }

    /**
     * Echo the logs.
     */
    public static function render_logs() {
        if ( is_array(self::$logs) && count(self::$logs) ) {
            echo "<ul class='logs'>";
            foreach ( self::$logs as $log ) {
                echo "<li class='log log-{$log['class']}'>{$log['text']}</li>";
            }
            echo "</ul>";
        }
    }
}