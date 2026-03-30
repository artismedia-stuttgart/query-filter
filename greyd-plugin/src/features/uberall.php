<?php
/*
Feature Name:   Uberall Extension
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Requires Features: posttypes
Priority:       96
Hidden:         true
*/

/**
 * Uberall API extension.
 * 
 * @since 1.0.8
 * * import uberall locations as posts of a custom post type.
 * * continously update them using wp-cron hooks.
 * @since 1.2.5
 * * changed settings handling: 
 * * deprecated 'uberall_settings' Option, settings are now saved in site Settings-Array
 */
namespace Greyd\Extensions;

use Greyd\Synced_Posttype as Synced_Posttype;
use Greyd\Automator as Automator;

use Greyd\Settings as Settings;
use Greyd\Helper as Helper;

if ( !defined('ABSPATH') ) exit;

/**
 * disable if plugin wants to run standalone
 * Standalone setup not possible - needs posttypes.
 */
if ( !class_exists( "Greyd\Admin" ) ) {
    // reject activation
    if (!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
	$plugin_name = get_plugin_data( __FILE__, false, false )['Name'];
    deactivate_plugins( plugin_basename( __FILE__ ) );
    // return reject message
    die(sprintf("%s can not be activated as standalone Plugin.", $plugin_name));
}

new Uberall();
class Uberall {
    
    /**
     * Action hook to update the locations.
     */
    private $slug = 'uberall-location';
    
    /**
     * Action hook to update the locations.
     */
    private $update_hook = 'import_uberall_locations';
    
    /**
     * Action hook to reset the locations.
     */
    private $reset_hook = 'reset_uberall_locations';

    /**
     * Holds the Synced_Posttype class object.
     * 
     * @var Synced_Posttype
     */
    private $posttype;

    /**
     * Holds the Automator class object.
     * 
     * @var Automator
     */
    private $automator;
    
    /**
     * Constructor
     */
    public function __construct() {
        // deprecate 'uberall_settings' Option
        add_action( 'init', array($this, 'fix_settings_option'), 1 );

        // settings
        add_filter( 'greyd_settings_default_site', array($this, 'add_setting') );
        add_filter( 'greyd_settings_more', array($this, 'render_settings'), 10, 3 );
        add_filter( 'greyd_settings_more_save', array($this, 'save_settings'), 10, 3 );

        // init extension
        add_action( 'init', array($this, 'init') );
    }

    
    /**
     * =================================================================
     *                          SETTINGS
     * =================================================================
     */

    // default settings
    public static function get_defaults() {

        $defaults = array( 
            'uberall' => array(
                'enable' => false,
                'privateKey' => '',
                'email_or_userId' => '',
                'access_token' => '',
                'import_images' => false
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
     * @see filter 'greyd_settings_more'
     * 
     * @param string $content   Content of all additional settings.
     * @param string $mode      'site' | 'network_site' | 'network_admin'
     * @param array $data       Current settings.
     */
    public function render_settings( $content, $mode, $data ) {

        if ( $mode == 'network_admin' ) return $content;

        $settings = $data['site']['uberall'];
        list( $enable, $privateKey, $email_or_userId, $access_token, $import_images ) = array_values($settings);

        // $locations = $this->get_locations();
        // debug( $locations );

        $info_style = 'info';
        $info_text = '';
        if ( empty($access_token) ) {
            if ( !empty($privateKey) && !empty($email_or_userId) ) {
                $info_style = 'red';
                $info_text .= '<b>'.__("Your API access is invalid.", 'greyd_hub').'</b> ';
            }
            $info_text .= sprintf(
                __("Please enter your private key as well as the corresponding e-mail address or user ID and save the settings. Here's how: %s", 'greyd_hub'),
                '<a href="https://uberall.com/en/developers" target="_blank">'.__("Uberall API", 'greyd_hub').'</a>'
            );
            if ( !$enable ) {
                $info_text .= '<br>'.__("After saving the settings, a post type is automatically generated, which can be filled with the Uberall locations.", 'greyd_hub');
            }
        }
        else {
            $info_style = 'success';
            $info_text = sprintf(
                __("Your API access is validated. You can now add your Uberall locations under %s.", 'greyd_hub'),
                '<a href="'.admin_url( 'edit.php?post_type='.$this->slug.'&page='.$this->slug.'-importer' ).'">'.__("Uberall > Import", 'greyd_hub').'</a>'
            );
        }

        $toggle = 'document.querySelector(".toggle_uberall").classList.toggle("hidden")';
        $content .= "
        <table class='form-table'>
            <tr>
                <th>".__('Uberall integration', 'greyd_hub')."</th>
                <td>
                    <label for='uberall[enable]'>
                        <input type='checkbox' id='uberall[enable]' name='uberall[enable]' ".( $enable ? "checked='checked'" : "" )." onchange='".$toggle."'/>
                        <span>".__("Enable Uberall integration", 'greyd_hub')."</span><br>
                        <small class='color_light'>".__("Enables automated import of Uberall locations.", 'greyd_hub')."</small>
                    </label><br>
                    
                    <div class='toggle_uberall ".( $enable ? "" : "hidden" )."'>
                        <label>".__('Private API key', 'greyd_hub')."</label><br>
                        <input type='text' id='uberall[privateKey]' name='uberall[privateKey]' value='{$privateKey}' class='large-text'/><br><br>
                        <label>".__("Email or user ID", 'greyd_hub')."</label><br>
                        <input type='text' id='uberall[email_or_userId]' name='uberall[email_or_userId]' value='{$email_or_userId}' class='regular-text'/><br><br>
                        "./*"<label for='uberall[import_images]'>
                            <input type='checkbox' id='uberall[import_images]' name='uberall[import_images]' ".( $import_images ? "checked='checked'" : "" )."/>
                            <span>".__('Bilder importieren', 'greyd_hub')."</span>
                        </label><br>".*/"
                        ".Helper::render_info_box(array( "style" => $info_style, "text" => $info_text ))."
                    </div>
                </td>
            </tr>
        </table>";
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
        if ( isset($data['uberall']) ) {

            // make new settings
            $site['uberall'] = array(
                'enable' => isset($data['uberall']['enable']) && $data['uberall']['enable'] === 'on' ? true : $defaults['uberall']['enable'],
                'privateKey' => isset($data['uberall']['privateKey']) ? esc_attr($data['uberall']['privateKey']) : $defaults['uberall']['privateKey'],
                'email_or_userId' => isset($data['uberall']['email_or_userId']) ? esc_attr($data['uberall']['email_or_userId']) : $defaults['uberall']['email_or_userId'],
                'access_token' => '',
                'import_images' => isset($data['uberall']['import_images']) && $data['uberall']['import_images'] === 'on' ? true : $defaults['uberall']['import_images'],
            );
            // get and save access token
            $site['uberall']['access_token'] = $this->get_access_token( $site['uberall'] ) ?? '';

        }
        return $site;
    }

    /**
     * Get the current settings for the integration.
     */
    public function get_settings() {

        // get from settings
        return Settings::get_setting( array('site', 'uberall') );

    }

    /**
     * The old version of the extension saves its settings to a new single Option 'uberall_settings'.
     * Instead it should save them to the site Settings-Array @see \Greyd\Plugin\Settings.
     * This function merges the single Option to the site Settings-Array.
     */
    public function fix_settings_option() {

        // check for old extra option
        $old = get_option( 'uberall_settings', null );
        if (!empty($old)) {
            // old option found 
            // save to site Settings-Array
            Settings::update_setting('site', array( 'site', 'uberall' ), $old);
            // delete old option
            delete_option( 'uberall_settings' );
            // reload
            header("Location: ".$_SERVER['REQUEST_URI']);
            exit;
        };

    }

    
    /**
     * =================================================================
     *                          IMPORTER
     * =================================================================
     */
    
    /**
     * Init the class
     */
    public function init() {

        if ( ! $this->get_settings()['enable'] ) return;

        $this->init_posttype();
        $this->init_automator();
        
        add_action( $this->update_hook, array($this, 'import_latest_posts'), 10, 1 );
        add_action( $this->reset_hook, array($this, 'reset_all_posts') );
    }

    /**
     * Set $this->posttype
     */
    public function init_posttype() {

        if ( !is_admin() ) return;

        $this->posttype = new Synced_Posttype( array(
            "post_type"     => "tp_posttypes",
            "post_name"     => $this->slug,
            "post_title"    => 'Uberall Location',
            "post_status"   => "publish",
            "meta_input"    => array(
                "posttype_settings" => array(
                    "slug"          => $this->slug,
                    "title"         => 'Uberall Location',
                    "icon"          => "location",
                    "tags"          => "tags",
                    "supports" => array(
                        "editor" => "editor",
                        "custom_taxonomies" => "custom_taxonomies"
                    ),
                    "custom_taxonomies" => array(
                        array(
                            "slug" => "country",
                            "singular" => __("Country", 'greyd_hub'),
                            "plural" => __("Countries", 'greyd_hub'),
                        )
                    ),
                    "arguments" => array(),
                    "capabilities" => array(
                        "posttype" => array(
                            "edit" => true,
                            "delete" => false,
                            "quickedit" => true,
                            "title" => false,
                            "setup" => true,
                            "setup-wording" => true,
                            "setup-menu" => true,
                            "setup-features" => true,
                            "setup-taxonomies" => false,
                            "setup-architecture" => true,
                            "add-fields" => true
                        ),
                        "posts" => array(
                            "add" => true,
                            "edit" => true,
                            "delete" => true,
                            "quickedit" => true,
                            "title" => true,
                            "edit-taxonomies" => true
                        )
                    ),
                    "fields" => $this->get_posttype_fields()
                )
            )
        ) );
    }

    /**
     * Get all posttype fields
     */
    public function get_posttype_fields() {
        return array(
            array(
                "name" => "descriptionShort",
                "label" => __("Description", 'greyd_hub'),
                "type" => "textarea",
                "edit" => false, "fill" => true
            ),
            // address
            array(
                "name" => "headlineAddress",
                "label" => __("Address", 'greyd_hub'),
                "type" => "headline",
                "h_unit" => "h2",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "streetAndNumber",
                "label" => __("Street and house number", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "zip",
                "label" => __("Postal code", 'greyd_hub'),
                "type" => "number",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "city",
                "label" => __("City", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "province",
                "label" => __("Location area", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "country",
                "label" => __("Country", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "lat",
                "label" => __("Latitude", 'greyd_hub'),
                "type" => "text_long",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "lng",
                "label" => __("Longitude", 'greyd_hub'),
                "type" => "text_long",
                "edit" => false, "fill" => true
            ),
            // contact
            array(
                "name" => "headlineContact",
                "label" => __("Contact", 'greyd_hub'),
                "type" => "headline",
                "h_unit" => "h2",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "phone",
                "label" => __("Phone number", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "fax",
                "label" => __("Fax number", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "website",
                "label" => __("Website", 'greyd_hub'),
                "type" => "url",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "email",
                "label" => __("Email", 'greyd_hub'),
                "type" => "email",
                "edit" => false, "fill" => true
            ),
            // openingHours
            array(
                "name" => "headlineOpeningHours",
                "label" => __("Opening hours", 'greyd_hub'),
                "type" => "headline",
                "h_unit" => "h2",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours1",
                "label" => __("Monday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours2",
                "label" => __("Tuesday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours3",
                "label" => __("Wednesday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours4",
                "label" => __("Thursday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours5",
                "label" => __("Friday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours6",
                "label" => __("Saturday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHours7",
                "label" => __("Sunday", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "openingHoursNotes",
                "label" => __("Note to the opening hours", 'greyd_hub'),
                "type" => "textarea",
                "edit" => false, "fill" => true
            ),
            // // photos
            // array(
            //     "name" => "headlinePhotos",
            //     "label" => __("Fotos", 'greyd_hub'),
            //     "type" => "headline",
            //     "h_unit" => "h2",
            //     "edit" => false, "fill" => true
            // ),
            // array(
            //     "name" => "photos-logo",
            //     "label" => __("Logo", 'greyd_hub'),
            //     "type" => "file",
            //     "edit" => false, "fill" => true
            // ),
            // array(
            //     "name" => "photo-squared_logo",
            //     "label" => __("eckiges Logo", 'greyd_hub'),
            //     "type" => "file",
            //     "edit" => false, "fill" => true
            // ),
            // array(
            //     "name" => "photo-main",
            //     "label" => __("Hauptfoto", 'greyd_hub'),
            //     "type" => "file",
            //     "edit" => false, "fill" => true
            // ),
            // meta infos
            array(
                "name" => "headlineMeta",
                "label" => __("Meta data", 'greyd_hub'),
                "type" => "headline",
                "h_unit" => "h2",
                "edit" => false, "fill" => true
            ),
            array(
                "name" => "id",
                "label" => __("Uberall ID", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => false
            ),
            array(
                "name" => "identifier",
                "label" => __("Uberall identifier", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => false
            ),
            array(
                "name" => "status",
                "label" => __("State", 'greyd_hub'),
                "type" => "text",
                "edit" => false, "fill" => false
            ),
        );
    }

    /**
     * Set $this->automator
     */
    public function init_automator() {
        $this->automator = new Automator([
            'slug'          => $this->slug.'-importer',
            'hook'          => $this->update_hook,
            'reset'         => $this->reset_hook,
            'menu'          => 'edit.php?post_type='.$this->slug,
            'title'         => __("Import Uberall locations", 'greyd_hub'),
            'menu_title'    => __("Import", 'greyd_hub'),
        ]);
    }
    
    /**
     * Import locations to the posttype
     * 
     * @param int $last_timestamp   Timestamp of the last update.
     */
    public function import_latest_posts( $last_timestamp ) {
        
        $locations = $this->get_locations();
        if ( !$locations || !is_array($locations) ) return;

        $posttype_fields = $this->get_posttype_fields();
        $settings = $this->get_settings();

        foreach( $locations as $location ) {
            
            $uberall_id     = isset($location['id']) ? esc_attr($location['id']) : null;
            $post_title     = isset($location['name']) ? esc_attr($location['name']) : null;
            $dynamic_meta   = array();

            if ( empty($uberall_id) || empty($post_title) ) continue;

            $this->automator->log( "Try to import location '$post_title'." );

            // basic post infos
            $postarr = array(
                "post_type"     => $this->slug,
                "post_title"    => $post_title,
                "post_status"   => isset($location['status']) && $location['status'] === 'ACTIVE' ? 'publish' : 'draft',
                "post_date"     => isset($location['dateCreated']) ? wp_date('Y-m-d H:i:s', strtotime($location['dateCreated'])) : null,
                "post_content"  => isset($location['descriptionLong']) ? strval($location['descriptionLong']) : '',
                "meta_input"    => array(
                    "uberall_id"    => $uberall_id,
                )
            );

            // // photos
            // if ( $settings['import_images'] && isset($location['photos']) && is_array($location['photos']) ) {
            //     foreach( $location['photos'] as $photo ) {
            //         $key = isset($photo['type']) ? 'photo-'.strtolower(esc_attr($photo['type'])) : '';
            //         $url = isset($photo['publicUrl']) ? $photo['publicUrl'] : '';
            //         if ( !empty($key) && !empty($url) ) {
            //             $dynamic_meta[$key] = $url;
            //         }
            //     }
            // }

            // openingHours
            if ( isset($location['openingHours']) && is_array($location['openingHours']) ) {
                foreach( $location['openingHours'] as $openingHour ) {
                    $key = isset($openingHour['dayOfWeek']) ? 'openingHours'.esc_attr($openingHour['dayOfWeek']) : '';
                    $hours = isset($openingHour['from1']) ? esc_attr($openingHour['from1']) : '';
                    if ( isset($openingHour['to1']) ) {
                        $hours .= (empty($hours) ? '' : ' - ').esc_attr($openingHour['to1']);
                    }
                    if ( isset($openingHour['from2']) ) {
                        $hours .= (empty($hours) ? '' : ', ').esc_attr($openingHour['from2']);
                        if ( isset($openingHour['to2']) ) {
                            $hours .= (empty($hours) ? '' : ' - ').esc_attr($openingHour['to2']);
                        }
                    }
                    
                    if ( !empty($key) && !empty($hours) ) {
                        $dynamic_meta[$key] = $hours;
                    }
                }
            }

            // other fields
            foreach( $posttype_fields as $field ) {
                $key = $field['name'];
                if ( isset($location[$key]) ) {
                    $dynamic_meta[$key] = esc_attr( $location[$key] );
                }
            }

            // set dynamic meta
            $postarr['meta_input']['dynamic_meta'] = $dynamic_meta;

            // see if we need to update the post
            $action = "created";
            $existing_post = $this->get_post_by_uberall_id( $uberall_id );
            if ( $existing_post ) {
                $action = "updated";
                $postarr['ID'] = $existing_post->ID;
                $this->automator->log( "Location found by uberall_id '{$uberall_id}': '{$existing_post->post_title}'" );
            }

            // insert the post
            $result = wp_insert_post( $postarr, true );
            if ( is_wp_error( $result ) ) {
                $this->automator->log( "Location could not be {$action}: ".$result->get_error_message() );
                continue;
            }
            else if ( !$result ) {
                $this->automator->log( "Location could not be {$action} (unknown error)." );
                continue;
            }

            // success
            $this->automator->log( "Location successfully {$action}." );
            $post_id = $result;

            // Set the taxonomies
            $tax_input = array(
                $this->slug."_tag" => isset($location['keywords']) ? (array) $location['keywords'] : array(),
                $this->slug."_country" => isset($location['country']) ? (array) esc_attr($location['country']) : array(),
            );
            foreach ( $tax_input as $taxonomy => $terms ) {

                // create terms
                foreach( $terms as $term ) {
                    $result = wp_insert_term( $term, $taxonomy );
                    if ( is_wp_error($result) ) {
                        // $this->automator->log( "term '$term' could not be created: ".$result->get_error_message() );
                    } else {
                        $this->automator->log( "term '$term' of taxonomy '$taxonomy' created" );
                    }
                }

                // link the term to the post
                $result = wp_set_object_terms( $post_id, $terms, $taxonomy );
                if ( is_wp_error($result) ) {
                    $this->automator->log( "$taxonomy of post <b>$post_title</b> could not be updated: ".$result->get_error_message() );
                } else {
                    $this->automator->log( "$taxonomy of post <b>$post_title</b> updated: ".implode(", ", $terms) );
                }
            }
        }
    }

    /**
     * Get a post by the meta option 'uberall_id'
     * 
     * @param string $uberall_id
     * 
     * @return WP_Post|null
     */
    public function get_post_by_uberall_id( $uberall_id ) {
        $args = array(
            'post_type'     => $this->slug,
            'meta_key'      => 'uberall_id',
            'meta_value'    => $uberall_id,
            'numberposts'   => 1,
            'post_status'   => array( 'publish', 'draft' )
        );
        $posts = get_posts( $args );

        return $posts && count($posts) ? $posts[0] : null;
    }
    
    /**
     * Reset all posts to the posttype
     */
    public function reset_all_posts() {
        $posts = get_posts([
            'post_type' => $this->slug,
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
        ]);
        if ($posts) {
            foreach ( (array) $posts as $post ) {
                wp_delete_post( $post->ID, true );
            }
        }
    }

    
    /**
     * =================================================================
     *                          API
     * =================================================================
     */

    /**
     * Try to verify the user and get an access token from the API.
     * 
     * @param array $settings
     * 
     * @return false|string
     */
    public function get_access_token( $settings=null ) {

        $settings = $settings ? $settings : $this->get_settings();
        list( $enable, $privateKey, $email_or_userId ) = array_values($settings);
        
        if ( !$enable || empty($privateKey) || empty($email_or_userId) ) return false;

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'privateKey' => $privateKey,
            ),
            'body' => json_encode( array(
                (is_email( $email_or_userId ) ? 'email' : 'userId') => $email_or_userId
            ) ),
        );
        $response = wp_remote_post( "https://uberall.com/api/users/login", $args );
        if ( !$response || !isset($response['body']) ) return false;

        $body = json_decode( $response['body'], true );
        if ( $body === null && json_last_error() !== JSON_ERROR_NONE ) return false;

        $status = isset($body['status']) ? esc_attr( $body['status'] ) : null;
        if ( $status !== 'SUCCESS' ) return false;

        $access_token = isset($body['response']) && isset($body['response']['access_token']) ? esc_attr( $body['response']['access_token'] ) : false;

        return $access_token;
    }

    /**
     * Try to get all the locations of the account.
     * 
     * @param int $offset   Offset where the location array should start.
     * 
     * @return false|array
     */
    public function get_locations( int $offset=0 ) {

        $settings       = $this->get_settings();
        $enable         = $settings['enable'];
        $access_token   = $settings['access_token'];
        
        if ( !$enable || empty($access_token) ) return false;

        $response = wp_remote_get( "https://uberall.com/api/locations?access_token={$access_token}&offset={$offset}" );
        if ( !$response || !isset($response['body']) ) {
            var_error_log( $response );
            return false;
        }

        $body = json_decode( $response['body'], true );
        if ( $body === null && json_last_error() !== JSON_ERROR_NONE ) return false;

        $status = isset($body['status']) ? esc_attr( $body['status'] ) : null;
        if ( $status !== 'SUCCESS' ) return false;

        $result = isset($body['response']) ? $body['response'] : null;
        if ( !is_array($result) ) return false;

        $max        = isset($result['max']) ? intval($result['max']) : 50;
        $count      = isset($result['count']) ? intval($result['count']) : 0;
        $locations  = isset($result['locations']) ? $result['locations'] : false;

        // add offset and get more locations
        if ( is_array($locations) && $max < ($count - $offset) ) {
            $more_locations = $this->get_locations( $offset + $max );
            if ( $more_locations ) {
                $locations = array_merge( $locations, $more_locations );
            }
        }

        return $locations;
    }
}