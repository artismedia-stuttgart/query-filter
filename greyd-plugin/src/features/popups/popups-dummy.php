<?php
/**
 * Popup Feature compatibility dummy.
 * The old version of of the Popup Feature exposed three static functions that are used by Theme and Plugins.
 * Those are re-created here and re-routed to their versions within the new namespaces.
 */

if( ! defined( 'ABSPATH' ) ) exit;

// new popup();
if ( ! class_exists('popup') ) {
    class popup {
        
        /**
         * Add a popup ID to be rendered.
         * 
         * @param int $id   post_id of a Popup to be rendered.
         */
        public static function add_popup_id($id) {
            
            \Greyd\Popups\Render::$popup_ids[$id] = $id;
    
        }
    
        /**
         * Get all active Popups for the current page.
         * 
         * @return object[] $popups     Array of Popup Objects or false
         */
        public static function check_popup() {
    
            return \Greyd\Popups\Render::get_active_popups();
    
        }
    
        /**
         * Render Setting Params shown when a setting is active.
         * (called from global_content)
         * 
         * @param object[] $params  Array of Additional Parameter.
         * @param string $value     The Value.
         * @return string
         */
        public static function render_setting_params( $params, $value ) {
    
            return \Greyd\Popups\Admin::render_setting_params( $params, $value );
    
        }
        
    }
}