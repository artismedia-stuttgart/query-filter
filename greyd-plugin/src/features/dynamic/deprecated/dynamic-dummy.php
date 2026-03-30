<?php
/**
 * Dynamic Features compatibility dummy.
 * The old version of of the Dynamic Features exposed three static functions that are used by Theme and Plugins.
 * Those are re-created here and re-routed to their versions within the new namespaces.
 */

if( ! defined( 'ABSPATH' ) ) exit;

add_action( 'after_setup_themes', function(){
    if ( ! class_exists('dynamic') ) {
        class dynamic {
    
            public static function add_dynamic($atts=[], $content='') {
                return \Greyd\Dynamic\Admin::add_dynamic($atts, $content);
            }
    
            public static function get_enqueue_content() {
                return \Greyd\Dynamic\Render::get_enqueue_content();
            }
            public static function check_enqueue_template($content, $sc) {
                return \Greyd\Dynamic\Render::check_enqueue_template($content, $sc);
            }
    
            public static function content_match_dynamic($dynamic_values, $content) {
                return \Greyd\Dynamic\Render::content_match_dynamic($dynamic_values, $content);
            }
            public static function match_tags($post, $tags, $value) {
                return \Greyd\Dynamic\Render::match_tags($post, $tags, $value);
            }
    
            public static function get_dynamic_name() {
                return \Greyd\Dynamic\Admin::get_dynamic_name();
            }
            public static function get_template($template_name) {
                return array( \Greyd\Dynamic\Dynamic_Helper::get_template_post($template_name) );
            }
    
        }
    }
} );