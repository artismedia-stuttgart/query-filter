<?php
/*
    Comments: Plugin File to define all default contents
*/
if( ! defined( 'ABSPATH' ) ) exit;

new default_content($config);
class default_content {

    private static $config;
    
    // constants
    const DEBUG = true;

    public function __construct($config) {
        // set config
        self::$config = (object) $config;

        // content and wordings
        add_action( 'after_setup_theme', array($this, 'init_contents'), 2 );

        // if (defined('ENABLE_GREYD_EXPERIMENTS') && ENABLE_GREYD_EXPERIMENTS === 'true') {
        //     add_action( 'after_setup_theme', array($this, 'init_gutenberg_default_contents'), 1 );
        // } else {
        //     add_action( 'after_setup_theme', array($this, 'init_default_contents'), 1 );
        // }
    }
    
    public static function init_contents() {
        self::init_gutenberg_default_contents();
        self::init_default_contents();
    }
    
    /*
    =======================================================================
        DEFAULT CONTENTS
    =======================================================================
    */
    public static $wordings = array(
        "shortcodes" => array(),
        "blocks" => array(),
    );
    public static $default_contents = array(
        "shortcodes" => array(),
        "blocks" => array(),
    );

    public static function get_default_wordings($mode = "shortcodes") {
        if (!isset(self::$wordings[$mode])) return array();
        return self::$wordings[$mode];
    }

    public static function get_default_contents($mode = "shortcodes") {
        if (!isset(self::$default_contents[$mode])) return array();
        return self::$default_contents[$mode];
    }

    public static function init_default_contents() {
        if(!is_admin()) return;
        
        function vc_encode($string) {
            if (method_exists("vc\helper", "vc_encode")) return \vc\helper::vc_encode($string);
            else return str_replace("+", "%20", urlencode($string));
        }
        
        // add wordings
        $default_message = "<p>".__("The content of this section can be set within the template \"%s\".", 'greyd_hub')."<br><a href='".home_url()."/wp-admin/edit.php?post_type=dynamic_template'>".__("View Templates", 'greyd_hub')."</a></p>";
        
        self::$wordings["shortcodes"] = array(
            "default_content"   => "[vc_row][vc_column][vc_blank_space height=\"20px\"][vc_column_text]".$default_message."[/vc_column_text][vc_blank_space][/vc_column][/vc_row]",
            "empty_content"     => "[vc_row][vc_column][/vc_column][/vc_row]"
        );
        
        // Menu content
        $menu_content = 
        '[vc_row][vc_column]'.
            '[vc_blank_space height="20px"]'.
            '[vc_footernav menu="greyd-beispiel-menue" direction="menu_column" align="menu_column_flex-start"]'.
            '[vc_blank_space height="20px"]'.
            '[vc_column_text]'.$default_message.'[/vc_column_text]'.
            '[vc_blank_space height="20px"]'.
        '[/vc_column][/vc_row]';
        
        // footer
        $footer_content = 
        '[vc_row][vc_column]
            [vc_blank_space height="20px"]
            [vc_footernav menu="greyd-beispiel-menue" direction="menu_column" align="menu_column_flex-start"]
            [vc_blank_space height="20px"]
            [vc_copyright layout="right"]
        [/vc_column][/vc_row]';
        
        // 404 content
        $fourofour_content = 
        '[vc_row row_type="row_l"][vc_column]
            [vc_blank_space height="140px"]
            [vc_headlines h="h1" title="'.__("Error", 'greyd_hub').' 404"]
            [vc_column_text]'.__("The page could not be found.", 'greyd_hub').'[/vc_column_text]
            [vc_blank_space height="30px"]'.
            // inserting the link manually can cause errors:
            // eg. reseting the content via Hub, causes the home-url to be the network home-url.
            // If we then move the page, the home-url is never actually replaced
            // '[vc_cbutton style="normal" mode="_button" linkk="url:'.urlencode(home_url()).'|title:'.vc_encode(__("to the home page", 'greyd_hub')).'||"][/vc_cbutton]'.
            '[vc_cbutton style="back" mode="_button" icon_align="left" icon="arrow_left"]'.__("go back", 'greyd_hub').'[/vc_cbutton]'.
            '[vc_blank_space height="180px"]
        [/vc_column][/vc_row]';
        
        // Compatibility content
        $compatibility_content = 
        '[vc_row][vc_column]
            [vc_blank_space]
            [vc_headlines dynamic_title="" h="h3" align="_center" title="'.__("Sorry, your browser does not support all features of this website.", 'greyd_hub').'"]
            [vc_column_text dynamic_content=""]'.__("Please update it to a new version or install the more modern browsers below.", 'greyd_hub').'[/vc_column_text]
            [vc_blank_space]
            [vc_cbutton style="normal" dynamic_linkk="" linkk="url:https%3A%2F%2Fwww.google.com%2Fchrome%2F|title:'.vc_encode(__("download Google Chrome", 'greyd_hub')).'|target:%20_blank|"][/vc_cbutton]
            [vc_cbutton style="normal" dynamic_linkk="" linkk="url:https%3A%2F%2Fwww.mozilla.org%2Fde%2Ffirefox%2F|title:'.vc_encode(__("download Google Firefox", 'greyd_hub')).'|target:%20_blank|"][/vc_cbutton]
            [vc_cbutton style="normal" dynamic_linkk="" linkk="url:https%3A%2F%2Fwww.opera.com%2Fde%2Fdownload|title:'.vc_encode(__("download Opera", 'greyd_hub')).'|target:%20_blank|"][/vc_cbutton]
            [vc_blank_space]
        [/vc_column][/vc_row]';
        
        // single content
        $single_content = 
        '[vc_row][vc_column][vc_blank_space][vc_cbutton style="back" mode="_button _sec" icon_align="left" icon="arrow_left"]zurück[/vc_cbutton][vc_blank_space][/vc_column][/vc_row][vc_row][vc_column][vc_row_inner][vc_column_inner width="2/3"][vc_icons dynamic_icon="" icon="_image_" width="300px"][vc_blank_space dynamic_height="" height="20px" height_sm="70" height_md="80" height_lg="90" height_xl="100"][vc_column_text dynamic_content=""]<span class="has-color-32">_date_</span>[/vc_column_text][vc_headlines dynamic_title="" h="h2" dynamic_h_tag="" title="_title_"][vc_column_text dynamic_content=""]_content_[/vc_column_text][vc_blank_space height="100px"][/vc_column_inner][vc_column_inner width="1/3" offset="vc_col-lg-offset-1 vc_col-lg-3"][vc_column_text dynamic_content=""]<p><span class="has-fontsize-small">'.__("Author", 'greyd_hub').':</span></p><p>_author_</p><p><hr /></p><p><span class="has-fontsize-small">'.__("Categories", 'greyd_hub').':</span></p><p>_categories|vert|1_</p>[/vc_column_text][vc_blank_space][/vc_column_inner][/vc_row_inner][/vc_column][/vc_row]';
        
        $search_content = 
        '[vc_row][vc_column]
            [vc_blank_space dynamic_height=""]
            [vc_cond_content]
                [vc_cond_content_item title="'.__("more than 1 result", 'greyd_hub').'" options="%5B%7B%22type%22%3A%22search%22%2C%22condition_urlparam%22%3A%22is%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%2C%22condition_search%22%3A%22multiple%22%7D%5D"]
                    [vc_headlines dynamic_title="" h="h1" dynamic_h_tag="" title="'.sprintf(__("%s posts found", 'greyd_hub'), '_post-count_').'"]
                [/vc_cond_content_item]
                [vc_cond_content_item title="'.__("exactly 1 result", 'greyd_hub').'" options="%5B%7B%22type%22%3A%22search%22%2C%22condition_urlparam%22%3A%22is%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%2C%22condition_search%22%3A%22single%22%7D%5D"]
                    [vc_headlines dynamic_title="" h="h1" dynamic_h_tag="" title="'.sprintf(__("%s post found", 'greyd_hub'), '_post-count_').'"]
                [/vc_cond_content_item]
                [vc_cond_content_item title="'.__("no post found", 'greyd_hub').'" options="%5B%7B%22type%22%3A%22search%22%2C%22condition_urlparam%22%3A%22is%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%2C%22condition_search%22%3A%22empty%22%7D%5D"]
                    [vc_headlines dynamic_title="" h="h1" dynamic_h_tag="" title="'.__("Ooops...", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]
                        <p>'.__("Unfortunately no posts for your search query were found.", 'greyd_hub').'</p><p>'.__("Query: \"_query_\"", 'greyd_hub').'</p><p>_filter_</p><p>'.__("Just try again:", 'greyd_hub').'</p>
                    [/vc_column_text]
                    [vc_blank_space]
                [/vc_cond_content_item]
            [/vc_cond_content]
            [vc_search display="horizontal" stretch="height" fields="%5B%7B%22type%22%3A%22input%22%2C%22input_style%22%3A%22_input%22%2C%22input_border_left%22%3A%22none%22%2C%22input_border_top%22%3A%22none%22%2C%22input_border_right%22%3A%22none%22%2C%22input_border_bottom%22%3A%22none%22%2C%22input_shadow%22%3A%22none%22%2C%22input_shadow_hover%22%3A%22none%22%2C%22input_icon_position%22%3A%22left%22%2C%22input_icon%22%3A%22icon_search%22%2C%22input_icon_margin%22%3A%2210px%22%2C%22input_icon_size%22%3A%22100%25%22%2C%22filter_type_all%22%3A%22post_type%22%2C%22filter_style%22%3A%22_input%22%2C%22filter_border_left%22%3A%22none%22%2C%22filter_border_top%22%3A%22none%22%2C%22filter_border_right%22%3A%22none%22%2C%22filter_border_bottom%22%3A%22none%22%2C%22filter_shadow%22%3A%22none%22%2C%22filter_shadow_hover%22%3A%22none%22%2C%22button_style%22%3A%22_button%22%2C%22button_border_left%22%3A%22none%22%2C%22button_border_top%22%3A%22none%22%2C%22button_border_right%22%3A%22none%22%2C%22button_border_bottom%22%3A%22none%22%2C%22button_shadow%22%3A%22none%22%2C%22button_shadow_hover%22%3A%22none%22%2C%22button_icon_margin%22%3A%2210px%22%2C%22button_icon_size%22%3A%22100%25%22%7D%2C%7B%22type%22%3A%22_button%22%2C%22input_style%22%3A%22_input%22%2C%22input_border_left%22%3A%22none%22%2C%22input_border_top%22%3A%22none%22%2C%22input_border_right%22%3A%22none%22%2C%22input_border_bottom%22%3A%22none%22%2C%22input_shadow%22%3A%22none%22%2C%22input_shadow_hover%22%3A%22none%22%2C%22input_icon_position%22%3A%22left%22%2C%22input_icon%22%3A%22icon_search%22%2C%22input_icon_margin%22%3A%2210px%22%2C%22input_icon_size%22%3A%22100%25%22%2C%22filter_type_all%22%3A%22post_type%22%2C%22filter_style%22%3A%22_input%22%2C%22filter_border_left%22%3A%22none%22%2C%22filter_border_top%22%3A%22none%22%2C%22filter_border_right%22%3A%22none%22%2C%22filter_border_bottom%22%3A%22none%22%2C%22filter_shadow%22%3A%22none%22%2C%22filter_shadow_hover%22%3A%22none%22%2C%22button_style%22%3A%22_button%22%2C%22button_border_left%22%3A%22none%22%2C%22button_border_top%22%3A%22none%22%2C%22button_border_right%22%3A%22none%22%2C%22button_border_bottom%22%3A%22none%22%2C%22button_shadow%22%3A%22none%22%2C%22button_shadow_hover%22%3A%22none%22%2C%22button_icon_margin%22%3A%2210px%22%2C%22button_icon_size%22%3A%22100%25%22%7D%5D"]
            [vc_blank_space]
            [vc_posts_tp pagination_numbers="icons" posts_layout="vertical" posts_rows="8" posts_padding="20px" post_linkfull="no" post_element_padding="10px" post_padding_top="20px" post_border_left="solid" post_border_top="solid" post_border_right="solid" post_border_bottom="solid" post_setup="%5B%7B%22post_element%22%3A%22date%22%2C%22empty_size%22%3A%2220px%22%2C%22empty_size_xs%22%3A%22100%25%22%2C%22empty_size_sm%22%3A%22100%25%22%2C%22empty_size_md%22%3A%22100%25%22%2C%22empty_size_lg%22%3A%22100%25%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22small%22%2C%22text_color%22%3A%22color_32%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22title_versal%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%220px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22link_shadow%22%3A%22none%22%2C%22link_shadow_hover%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22cart_shadow%22%3A%22none%22%2C%22cart_shadow_hover%22%3A%22none%22%2C%22woo_ajax_redirect%22%3A%22default%22%2C%22woo_ajax_icon_margin%22%3A%2210px%22%2C%22woo_ajax_icon_size%22%3A%22100%25%22%2C%22woo_ajax_shadow%22%3A%22none%22%2C%22woo_ajax_shadow_hover%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_style%22%3A%22solid%22%2C%22sep_color%22%3A%22%23444444%22%7D%2C%7B%22post_element%22%3A%22title%22%2C%22empty_size%22%3A%2220px%22%2C%22empty_size_xs%22%3A%22100%25%22%2C%22empty_size_sm%22%3A%22100%25%22%2C%22empty_size_md%22%3A%22100%25%22%2C%22empty_size_lg%22%3A%22100%25%22%2C%22title_size%22%3A%22h4%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%220px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22link_shadow%22%3A%22none%22%2C%22link_shadow_hover%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22cart_shadow%22%3A%22none%22%2C%22cart_shadow_hover%22%3A%22none%22%2C%22woo_ajax_redirect%22%3A%22default%22%2C%22woo_ajax_icon_margin%22%3A%2210px%22%2C%22woo_ajax_icon_size%22%3A%22100%25%22%2C%22woo_ajax_shadow%22%3A%22none%22%2C%22woo_ajax_shadow_hover%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_style%22%3A%22solid%22%2C%22sep_color%22%3A%22%23444444%22%7D%2C%7B%22post_element%22%3A%22content%22%2C%22empty_size%22%3A%2220px%22%2C%22empty_size_xs%22%3A%22100%25%22%2C%22empty_size_sm%22%3A%22100%25%22%2C%22empty_size_md%22%3A%22100%25%22%2C%22empty_size_lg%22%3A%22100%25%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22100%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22link_shadow%22%3A%22none%22%2C%22link_shadow_hover%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22cart_shadow%22%3A%22none%22%2C%22cart_shadow_hover%22%3A%22none%22%2C%22woo_ajax_redirect%22%3A%22default%22%2C%22woo_ajax_icon_margin%22%3A%2210px%22%2C%22woo_ajax_icon_size%22%3A%22100%25%22%2C%22woo_ajax_shadow%22%3A%22none%22%2C%22woo_ajax_shadow_hover%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_style%22%3A%22solid%22%2C%22sep_color%22%3A%22%23444444%22%7D%2C%7B%22post_element%22%3A%22link%22%2C%22link_text%22%3A%22Mehr%20erfahren%22%2C%22empty_size%22%3A%2220px%22%2C%22empty_size_xs%22%3A%22100%25%22%2C%22empty_size_sm%22%3A%22100%25%22%2C%22empty_size_md%22%3A%22100%25%22%2C%22empty_size_lg%22%3A%22100%25%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22title_versal%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22link_shadow%22%3A%22none%22%2C%22link_shadow_hover%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22cart_shadow%22%3A%22none%22%2C%22cart_shadow_hover%22%3A%22none%22%2C%22woo_ajax_redirect%22%3A%22default%22%2C%22woo_ajax_icon_margin%22%3A%2210px%22%2C%22woo_ajax_icon_size%22%3A%22100%25%22%2C%22woo_ajax_shadow%22%3A%22none%22%2C%22woo_ajax_shadow_hover%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_style%22%3A%22solid%22%2C%22sep_color%22%3A%22%23444444%22%7D%5D" sub_seperator_preview="" post_setup_mode="manual"]
        [/vc_column][/vc_row]';
        
        $archive_content = 
        '[vc_row ignore_inner_gutter="true"][vc_column]
            [vc_blank_space]
            [vc_cbutton style="back" mode="_button _sec" icon_align="left" icon="arrow_left"]'.__("back", 'greyd_hub').'[/vc_cbutton]
            [vc_headlines dynamic_title="" h="h2" title="'.__("Posts", 'greyd_hub').'"]
            [vc_row_inner][vc_column_inner width="2/3"]
                [vc_posts_tp pagination_layout="bottom" pagination_numbers="icons" posts_layout="vertical" posts_rows="8" posts_padding="20px" post_linkfull="no" post_element_padding="10px" post_padding_top="20px" post_border_left="solid" post_border_top="solid" post_border_right="solid" post_border_bottom="solid" post_setup="%5B%7B%22post_element%22%3A%22date%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22title_versal%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%220px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22title%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22h4%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%220px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22content%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22100%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22link%22%2C%22link_text%22%3A%22'.vc_encode(__("Learn More", 'greyd_hub')).'%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22link_to%22%3A%22true%22%2C%22title_versal%22%3A%22true%22%2C%22content_length%22%3A%22200%22%2C%22link_icon%22%3A%22arrow_right%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22150%25%22%2C%22link_border_left%22%3A%22solid%22%2C%22link_border_top%22%3A%22solid%22%2C%22link_border_right%22%3A%22solid%22%2C%22link_border_bottom%22%3A%22solid%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22solid%22%2C%22cart_border_top%22%3A%22solid%22%2C%22cart_border_right%22%3A%22solid%22%2C%22cart_border_bottom%22%3A%22solid%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%5D" sub_seperator_preview="" pagination_icon_previous="arrow_left" pagination_numbers_icon="icon_circle-empty" pagination_numbers_icon_active="icon_circle-slelected" pagination_icon_next="arrow_right" post_setup_mode="manual"][vc_blank_space height="100px" height_sm="100%" height_md="100%" height_lg="100%" height_xl="0%"]
            [/vc_column_inner]
            [vc_column_inner width="1/3" offset="vc_col-lg-offset-1 vc_col-lg-3 vc_col-md-4"]
                [vc_posts_links title_size="h5" title="'.__("Archive", 'greyd_hub').'"][vc_posts_links archive_type="category" link_style="_link _sec" title_size="h5" title="'.__("Categories", 'greyd_hub').'"]
                [vc_blank_space]
                [vc_search_form search_layout="horizontal trd" search_responsive="responsive" btn_style="_button _sec" btn_icon_size="150%" holder="'.__("Enter search term...", 'greyd_hub').'" btn="'.__("Start search", 'greyd_hub').'" btn_icon="arrow_right"]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]';
        
        $starter_page_content = 
        '[vc_row vc_bg_type="vc_bg_image" dynamic_vc_bg_image="" vc_bg_image_repeat="no-repeat" vc_bg_image_position="center_center" vc_bg_overlay_type="vc_bg_gradient2" vc_bg_overlay_color_opacity="30" ignore_inner_gutter="true" vc_bg_overlay="true" seperator_bgtype="" new_line_grad="" new_line_grad_more="" sub_seperator_sep="" vc_bg_colorselect="color_33" vc_bg_image="{{img_light_wide}}" vc_bg_overlay_gradient2="scroll|angle|192|color_31:0|color_63:100"][vc_column]
            [vc_blank_space height="80px" height_sm="20%" height_md="20%" height_lg="50%"]
            [vc_row_inner][vc_column_inner]
                [vc_headlines dynamic_title="" h="h4" align="_center" title="'.__("This Is Your New Page", 'greyd_hub').'"]
                [vc_headlines dynamic_title="" h="h1" align="_center" title="'.__("With Greyd.Suite", 'greyd_hub').'"]
                [vc_cbutton style="down" dynamic_content="" dynamic_anchor="" anchor="features" pull="_center" size="_big" mode="_button" icon_size="0%"]'._x("view features", 'small', 'greyd_hub').'[/vc_cbutton]
                [/vc_column_inner]
            [/vc_row_inner]
            [vc_blank_space height="140px" height_sm="60%"]
        [/vc_column][/vc_row]'.
        '[vc_row ignore_inner_gutter="true"][vc_column]
            [vc_blank_space height="100px" height_sm="60%"]
            [vc_row_inner][vc_column_inner offset="vc_col-lg-7 vc_col-md-8"]
                [vc_headlines dynamic_title="" h="h5" title="'.__("Intuitive Design", 'greyd_hub').'"]
                [vc_headlines dynamic_title="" h="h3" title="'.__("With Native Gutenberg Integration", 'greyd_hub').'"]
                [vc_column_text dynamic_content=""]'.__("Set design settings in the Customizer centrally for your entire website - from colors to fonts to the appearance of buttons, form fields or headings. You want to change something? No problem! One click in the Customizer and all affected areas of your page automatically adjust.", 'greyd_hub').'[/vc_column_text]
                [vc_blank_space]
                [vc_cbutton dynamic_linkk="" mode="_button _sec" linkk="url:%2Fwp-admin%2Fcustomize.php|title:'.vc_encode(_x("Open Customizer", 'small', 'greyd_hub')).'|target:%20_blank|" icon="icon_tool"][/vc_cbutton]
            [/vc_column_inner]
            [vc_column_inner el_class="vc_col-lg-push-1" width="1/2" offset="vc_col-lg-offset-0 vc_col-lg-5 vc_col-md-offset-0 vc_col-md-4 vc_col-sm-offset-3 vc_col-xs-offset-1 vc_col-xs-10"]
            [/vc_column_inner][/vc_row_inner]
            [vc_blank_space height="100px" height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row override_type="override_sm,override_md" override_gutter="override_sm,override_md"][vc_column]
            [vc_row_inner][vc_column_inner]
                [vc_cbutton_anchor anchor="features"]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner override_gutter="override_md,override_lg,override_xl" content_placement="bottom" equal_height="yes"][vc_column_inner width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0" el_id="boxborder"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__("The Customizer", 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines dynamic_title="" h="h5" title="'.__("Global design settings", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("Define your design regardless of the content of your site. This allows you to easily make design adjustments later without editing each content item individually.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fcustomize.php|title:'.vc_encode(__("View Customizer", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner]
            [vc_column_inner width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0" el_id="boxborder"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_padding_right_xl="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Greyd.Hub', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines h="h5" title="'.__("Manage your entire multisite", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("No matter how many pages your installation contains: In Greyd.Hub, you can manage all pages in one backend, create back-ups, export design settings and transfer pages in a few seconds.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fadmin.php%3Fpage%3Dgreyd_hub|title:'.vc_encode(__("View Dashboard", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner override_gutter="override_sm,override_md,override_lg,override_xl" content_placement="top" equal_height="yes"][vc_column_inner el_class="boxborder" width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Greyd.Forms', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines dynamic_title="" h="h5" title="'.__("Create Professional Forms", 'greyd_hub').'" padding="0" css=".vc_custom_1587022528502{padding-top: 0px !important;padding-right: 0px !important;padding-bottom: 0px !important;padding-left: 0px !important;}"]
                    [vc_column_text dynamic_content=""]'.__("Whether it’s a simple contact form with double opt-in, a conversion-optimized lead form or a complex multi-level form with CRM connection and mathematical calculations - with Greyd.Forms you won’t need any additional plugins!", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fedit.php%3Fpost_type%3Dtp_forms|title:'.vc_encode(__("View Forms", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][vc_column_inner el_class="boxborder" width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_gradient2" vc_content_box_sdw_x="0px" vc_content_box_sdw_y="0px" vc_content_box_sdw_blur="50px" vc_content_box_sdw_x_hover="0px" vc_content_box_sdw_y_hover="0px" vc_content_box_sdw_blur_hover="30px" vc_content_box_sdw="true" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_lg="40px" vc_content_box_margin_right_lg="30px" vc_content_box_sdw_colorselect="color_22" vc_content_box_sdw_colorselect_hover="color_22" vc_content_box_padding_right_sm="30px" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62" vc_content_box_gradient2="scroll|angle|145|color_11:0|color_12:100" vc_content_box_shadow="0px+0px+50px+0px+color_22+100" vc_content_box_shadow_hover="0px+0px+30px+0px+color_22+100"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_light}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Dynamic Templates', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines h="h5" title="'.__("Use the full power of WordPress", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" mode="_button _sec" icon_size="150%" linkk="url:%2Fwp-admin%2Fedit.php%3Fpost_type%3Ddynamic_template|title:'.vc_encode(__("View Templates", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner][vc_column_inner]
                [vc_blank_space height="100px" height_sm="50%"]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_gradient2="scroll|angle|202|color_11:0.0|color_12:100"][vc_column]
            [vc_row_inner content_placement="middle" equal_height="yes"][vc_column_inner width="5/6" offset="vc_col-md-10 vc_col-sm-offset-1 vc_col-xs-12"]
                [vc_blank_space height="120px" height_sm="50%"]
                [vc_headlines dynamic_title="" h="h3" align="_center" title="'.__("“It’s not an experiment if you know it’s going to work.”", 'greyd_hub').'" color="color_62"]
                [vc_headlines dynamic_title="" h="h6" align="_center" title="'.__('Jeff Bezos', 'greyd_hub').'" color="color_62"]
                [vc_blank_space height="120px" height_sm="50%"]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_color" vc_bg_colorselect="color_23"][vc_column]
            [vc_blank_space]
            [vc_headlines dynamic_title="" h="h5" align="_center" title="'.__("Get Started Now", 'greyd_hub').'"]
            [vc_headlines h="h4" align="_center" title="'.__("Find In-Depth Tutorials, Quick Learning Videos & FAQ here:", 'greyd_hub').'"]
            [vc_cbutton dynamic_linkk="" pull="_center" mode="_button" linkk="url:'.urlencode(__(self::$config->helpcenter, 'greyd_hub')).'|title:'.vc_encode(__("Open Helpcenter", 'greyd_hub')).'|target:%20_blank|"][/vc_cbutton]
        [/vc_column][/vc_row]';


        $default_page_content = 
        '[vc_row vc_bg_type="vc_bg_image" dynamic_vc_bg_image="" vc_bg_image_repeat="no-repeat" vc_bg_image_position="center_center" vc_bg_overlay_type="vc_bg_gradient2" vc_bg_overlay_color_opacity="30" ignore_inner_gutter="true" vc_bg_overlay="true" seperator_bgtype="" new_line_grad="" new_line_grad_more="" sub_seperator_sep="" vc_bg_colorselect="color_33" vc_bg_image="{{img_light_wide}}" vc_bg_overlay_gradient2="scroll|angle|192|color_31:0|color_63:100"][vc_column]
            [vc_blank_space height="80px" height_sm="20%" height_md="20%" height_lg="50%"]
            [vc_row_inner][vc_column_inner]
                [vc_headlines dynamic_title="" h="h4" align="_center" title="'.__("This Is Your New Page", 'greyd_hub').'"]
                [vc_headlines dynamic_title="" h="h1" align="_center" title="'.__("With Greyd.Suite", 'greyd_hub').'"]
                [vc_cbutton style="down" dynamic_content="" dynamic_anchor="" anchor="features" pull="_center" size="_big" mode="_button" icon_size="0%"]'._x("view features", 'small', 'greyd_hub').'[/vc_cbutton]
                [/vc_column_inner]
            [/vc_row_inner]
            [vc_blank_space height="140px" height_sm="60%"]
        [/vc_column][/vc_row]'.
        '[vc_row ignore_inner_gutter="true"][vc_column]
            [vc_blank_space height="100px" height_sm="60%"]
            [vc_row_inner][vc_column_inner offset="vc_col-lg-7 vc_col-md-8"]
                [vc_headlines dynamic_title="" h="h5" title="'.__("Intuitive Design", 'greyd_hub').'"]
                [vc_headlines dynamic_title="" h="h3" title="'.__("With Native Gutenberg Integration", 'greyd_hub').'"]
                [vc_column_text dynamic_content=""]'.__("Set design settings in the Customizer centrally for your entire website - from colors to fonts to the appearance of buttons, form fields or headings. You want to change something? No problem! One click in the Customizer and all affected areas of your page automatically adjust.", 'greyd_hub').'[/vc_column_text]
                [vc_blank_space]
                [vc_cbutton dynamic_linkk="" mode="_button _sec" linkk="url:%2Fwp-admin%2Fcustomize.php|title:'.vc_encode(_x("Open Customizer", 'small', 'greyd_hub')).'|target:%20_blank|" icon="icon_tool"][/vc_cbutton]
            [/vc_column_inner]
            [vc_column_inner el_class="vc_col-lg-push-1" width="1/2" offset="vc_col-lg-offset-0 vc_col-lg-5 vc_col-md-offset-0 vc_col-md-4 vc_col-sm-offset-3 vc_col-xs-offset-1 vc_col-xs-10"]
            [/vc_column_inner][/vc_row_inner]
            [vc_blank_space height="100px" height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row][vc_column]
            [dynamic template="'.__("example-what-is-a-dynamic-template", 'greyd_hub').'" options="ignore_gutter" dynamic_dynamic_content="" dynamic_content="'.__("example-what-is-a-dynamic-template", 'greyd_hub').'::icon|file_picker|Bild|::title|textarea|'.vc_encode(__("headline", 'greyd_hub')).'|'.vc_encode(__("Dynamic Templates Responsively Designed", 'greyd_hub')).'::content|textarea_html|'.vc_encode(__("Text block 01", 'greyd_hub')).'|'.vc_encode('<p>'.__("Try shrinking your browser window and see how this area changes. How does it work?", 'greyd_hub').'</p>').'::content|textarea_html|'.vc_encode(__("Text block 02", 'greyd_hub')).'|'.vc_encode('<p>'.__("With Greyd.Suite’s Dynamic Templates! They allow you to create different layouts for each breakpoint, but you only need to enter your content once.", 'greyd_hub').'</p>').'::content|textarea|'.vc_encode(__('Button', 'greyd_hub')).'|::anchor|dropdown_anchor|'.vc_encode(__("Anchor", 'greyd_hub')).'|features"]
        [/vc_column][/vc_row]'.
        '[vc_row override_type="override_sm,override_md" override_gutter="override_sm,override_md"][vc_column]
            [vc_row_inner][vc_column_inner]
                [vc_blank_space height="100px" height_lg="100%"]
                [vc_cbutton_anchor anchor="features"]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner override_gutter="override_md,override_lg,override_xl" content_placement="bottom" equal_height="yes"][vc_column_inner width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0" el_id="boxborder"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__("The Customizer", 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines dynamic_title="" h="h5" title="'.__("Global design settings", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("Define your design regardless of the content of your site. This allows you to easily make design adjustments later without editing each content item individually.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fcustomize.php|title:'.vc_encode(__("View Customizer", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner]
            [vc_column_inner width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0" el_id="boxborder"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_padding_right_xl="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Greyd.Hub', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines h="h5" title="'.__("Manage your entire multisite", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("No matter how many pages your installation contains: In Greyd.Hub, you can manage all pages in one backend, create back-ups, export design settings and transfer pages in a few seconds.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fadmin.php%3Fpage%3Dgreyd_hub|title:'.vc_encode(__("View Dashboard", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner override_gutter="override_sm,override_md,override_lg,override_xl" content_placement="top" equal_height="yes"][vc_column_inner el_class="boxborder" width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_md="30px" vc_content_box_padding_right_lg="40px" vc_content_box_colorselect="color_63" vc_content_box_colorselect_hover="color_23" vc_content_box_margin_right_md="0px"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_dark}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Greyd.Forms', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines dynamic_title="" h="h5" title="'.__("Create Professional Forms", 'greyd_hub').'" padding="0" css=".vc_custom_1587022528502{padding-top: 0px !important;padding-right: 0px !important;padding-bottom: 0px !important;padding-left: 0px !important;}"]
                    [vc_column_text dynamic_content=""]'.__("Whether it’s a simple contact form with double opt-in, a conversion-optimized lead form or a complex multi-level form with CRM connection and mathematical calculations - with Greyd.Forms you won’t need any additional plugins!", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" size="_small" mode="_button _trd" icon_size="150%" linkk="url:%2Fwp-admin%2Fedit.php%3Fpost_type%3Dtp_forms|title:'.vc_encode(__("View Forms", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][vc_column_inner el_class="boxborder" width="1/2" offset="vc_col-lg-offset-0 vc_col-md-offset-0 vc_col-md-6 vc_col-sm-offset-0"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_gradient2" vc_content_box_sdw_x="0px" vc_content_box_sdw_y="0px" vc_content_box_sdw_blur="50px" vc_content_box_sdw_x_hover="0px" vc_content_box_sdw_y_hover="0px" vc_content_box_sdw_blur_hover="30px" vc_content_box_sdw="true" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="20px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_lg="40px" vc_content_box_margin_right_lg="30px" vc_content_box_sdw_colorselect="color_22" vc_content_box_sdw_colorselect_hover="color_22" vc_content_box_padding_right_sm="30px" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62" vc_content_box_gradient2="scroll|angle|145|color_11:0|color_12:100" vc_content_box_shadow="0px+0px+50px+0px+color_22+100" vc_content_box_shadow_hover="0px+0px+30px+0px+color_22+100"]
                    [vc_list vc_list_type="img" vc_list_icon_size="40px" vc_list_icon_margin="15px" list_icon_align_y="center" vc_list_image="{{logo_light}}"]'.
                        '[vc_list_item dynamic_content=""]'.__('Dynamic Templates', 'greyd_hub').'[/vc_list_item]'.
                    '[/vc_list]
                    [vc_headlines h="h5" title="'.__("Use the full power of WordPress", 'greyd_hub').'"]
                    [vc_column_text dynamic_content=""]'.__("Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="40px" height_sm="50%" height_lg="80%"]
                    [vc_cbutton dynamic_linkk="" mode="_button _sec" icon_size="150%" linkk="url:%2Fwp-admin%2Fedit.php%3Fpost_type%3Ddynamic_template|title:'.vc_encode(__("View Templates", 'greyd_hub')).'||" icon="arrow_right"][/vc_cbutton]
                [/vc_content_box]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner][vc_column_inner]
                [vc_blank_space height="100px" height_sm="50%"]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_gradient2="scroll|angle|202|color_11:0.0|color_12:100"][vc_column]
            [vc_row_inner content_placement="middle" equal_height="yes"][vc_column_inner width="5/6" offset="vc_col-md-10 vc_col-sm-offset-1 vc_col-xs-12"]
                [vc_blank_space height="120px" height_sm="50%"]
                [vc_headlines dynamic_title="" h="h3" align="_center" title="'.__("“It’s not an experiment if you know it’s going to work.”", 'greyd_hub').'" color="color_62"]
                [vc_headlines dynamic_title="" h="h6" align="_center" title="'.__('Jeff Bezos', 'greyd_hub').'" color="color_62"]
                [vc_blank_space height="120px" height_sm="50%"]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_overlay_type="vc_bg_gradient2" vc_bg_overlay_color_opacity="16" ignore_inner_gutter="true" vc_bg_overlay="true" vc_bg_gradient2="scroll|bottom|0|color_33:0|color_62:100.0" vc_bg_overlay_gradient2="scroll|top|180|color_31:0.0|color_63:80.6" el_id="posttypes"][vc_column]
            [vc_blank_space height="100px" height_sm="60%"]
            [vc_row_inner][vc_column_inner offset="vc_col-lg-7 vc_col-md-8"]
                [vc_headlines dynamic_title="" h="h5" title="'.__("More Than Just Posts", 'greyd_hub').'"]
                [vc_headlines dynamic_title="" h="h3" title="'.__("Custom Post Types", 'greyd_hub').'"]
                [vc_column_text dynamic_content=""]'.__("From locations to employees or FAQ: Greyd.Suite makes creating custom post types and custom taxonomies super easy. Simplify your content maintenance in the backend and display your content individually.", 'greyd_hub').'[/vc_column_text]
            [/vc_column_inner]
            [vc_column_inner el_class="vc_col-lg-push-1" width="1/2" offset="vc_col-lg-offset-0 vc_col-lg-5 vc_col-md-offset-0 vc_col-md-4 vc_col-sm-offset-3 vc_col-xs-offset-1 vc_col-xs-10"]
            [/vc_column_inner][/vc_row_inner]
            [vc_blank_space height_sm="100%" height_md="100%" height_lg="100%"]
        [/vc_column]
        [vc_column width="1/2" offset="vc_col-lg-8"]
            [vc_row_inner][vc_column_inner offset="vc_col-lg-11"]
                [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_padding_right_sm="15" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62" vc_content_box_colorselect="color_13" vc_content_box_colorselect_hover="color_13" vc_content_box_padding_top_sm="10"]
                    [vc_headlines dynamic_title="" h="h6" title="'.__("Show Your Custom Post Types in Post Overviews with Any Layout:", 'greyd_hub').'" css=".vc_custom_1588001813361{margin-top: 0px !important;margin-bottom: 0px !important;}"]
                [/vc_content_box]
            [/vc_column_inner][/vc_row_inner]
            [vc_blank_space height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_row_inner][vc_column_inner offset="vc_col-lg-5"]'.
                '[vc_posts_overview pt="'.__("myposttype", 'greyd_hub').'" ppp="1" posts_columns="1" posts_rows="1" posts_padding="0px" post_template="'.__("example-display-post-type", 'greyd_hub').'" post_setup_mode="template" dynamic_post_content="'.__("example-display-post-type", 'greyd_hub').'::title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'|_topic_::title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'|_question_::linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'|url%3A_link_%7Ctitle%3A'.urlencode(vc_encode(__("View Video", 'greyd_hub'))).'%7Ctarget%3A%7Crel%3A::content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'|%3Cp%3E_description_%3C%2Fp%3E::vc_bg_video|textfield|'.vc_encode(__("Background video", 'greyd_hub')).'|::vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'|_picture_"]'.
            '[/vc_column_inner]
            [vc_column_inner offset="vc_col-lg-offset-1 vc_col-lg-6"]
                [vc_blank_space height="60px" height_sm="100%" height_md="100%" height_lg="0%" height_xl="0%"]'.
                '[vc_posts_overview pt="'.__("myposttype", 'greyd_hub').'" ppp="1" posts_columns="1" posts_rows="1" posts_padding="0px" post_template="'.__("example-dynamic-content", 'greyd_hub').'" post_setup_mode="template" dynamic_post_content="'.__("example-dynamic-content", 'greyd_hub').'::icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'|_picture_::title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'|_topic_::title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'|_question_::content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'|%3Cp%3E_description_%3C%2Fp%3E::linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'|url%3A_link_%7Ctitle%3A'.urlencode(vc_encode(__("View Video", 'greyd_hub'))).'%7Ctarget%3A%7Crel%3A::vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'|_picture_"]'.
            '[/vc_column_inner][/vc_row_inner]
        [/vc_column]
        [vc_column width="1/2" offset="vc_col-lg-4"]
            [vc_blank_space height="60px" height_sm="100%" height_md="100%" height_lg="0%" height_xl="0%"]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_sdw_x="0px" vc_content_box_sdw_y="0px" vc_content_box_sdw_blur="50px" vc_content_box_sdw_x_hover="0px" vc_content_box_sdw_y_hover="0px" vc_content_box_sdw_blur_hover="30px" vc_content_box_sdw="true" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="0px" vc_content_box_padding_top_sm="15px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_sdw_colorselect="color_22" vc_content_box_sdw_colorselect_hover="color_22" vc_content_box_padding_right_sm="20px" vc_content_box_colorselect="color_62" vc_content_box_colorselect_hover="color_62" vc_content_box_margin_top_lg="0px"]'.
                '[vc_posts_overview pt="'.__("myposttype", 'greyd_hub').'" ppp="-1" pagination_text_size="big" pagination_numbers="icons" posts_columns="1" posts_rows="1" posts_padding="0px" post_template="'.__("example-display-post-type", 'greyd_hub').'" post_setup_mode="template" dynamic_post_content="'.__("example-display-post-type", 'greyd_hub').'::title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'|_topic_::title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'|_question_::linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'|url%3A_link_%7Ctitle%3A'.urlencode(vc_encode(__("Learn More", 'greyd_hub'))).'%7Ctarget%3A%7Crel%3A::content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'|%3Cp%3E_content_%3C%2Fp%3E::vc_bg_video|textfield|'.vc_encode(__("Background video", 'greyd_hub')).'|::vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'|_image_"]'.
            '[/vc_content_box]
        [/vc_column]
        [vc_column]
            [vc_blank_space height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" ignore_inner_gutter="true" vc_bg_gradient2="scroll|top|180|color_33:49.99|color_23:50"][vc_column offset="vc_col-md-8"]
            [vc_blank_space]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_type="vc_content_box_color" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62" vc_content_box_colorselect="color_13" vc_content_box_colorselect_hover="color_13" vc_content_box_padding_right_sm="15" vc_content_box_padding_top_sm="10px"]
                [vc_headlines dynamic_title="" h="h6" title="'.__("Or Use the WordPress Standard Posts for Different Use Cases:", 'greyd_hub').'" css=".vc_custom_1588001983898{margin-top: 0px !important;margin-bottom: 0px !important;}"]
            [/vc_content_box]
            [vc_blank_space]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_color" vc_bg_colorselect="color_23"][vc_column width="1/3"]
            [vc_posts_overview categoryid="0" tagid="0" ppp="-1" posts_columns="1" posts_rows="1" posts_padding="0px" post_linkfull="no" post_element_padding="10px" post_border_left="solid" post_border_top="solid" post_border_right="solid" post_border_bottom="solid" post_setup="%5B%7B%22post_element%22%3A%22empty%22%2C%22empty_size%22%3A%2230px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22title%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22h4%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22content%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22100%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22link%22%2C%22link_text%22%3A%22'.vc_encode(__("Learn More", 'greyd_hub')).'%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon%22%3A%22arrow_right%22%2C%22link_icon_margin%22%3A%224px%22%2C%22link_icon_size%22%3A%22140%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%5D" post_setup_mode="manual"]
            [vc_blank_space height="15px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_posts_overview categoryid="0" tagid="0" ppp="-1" posts_columns="1" posts_rows="1" posts_padding="0px" post_element_padding="10px" post_border_left="solid" post_border_top="solid" post_border_right="solid" post_border_bottom="solid" post_setup="%5B%7B%22post_element%22%3A%22date%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22small%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22title%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22strong%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22content%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22small%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22100%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%5D" post_setup_mode="manual"]
        [/vc_column][vc_column width="1/3"]
            [vc_posts_overview categoryid="0" tagid="0" ppp="-1" posts_columns="1" posts_rows="1" posts_padding="0px" post_linkfull="no" post_element_padding="10px" post_padding_right="15px" post_border_width="2px" post_border_left="solid" post_setup="%5B%7B%22post_element%22%3A%22empty%22%2C%22empty_size%22%3A%2215px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22image%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100px%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22empty%22%2C%22empty_size%22%3A%2215px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22date%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22small%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22title%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22h4%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22content%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%2280%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22link%22%2C%22link_text%22%3A%22'.vc_encode(__("Learn More", 'greyd_hub')).'%22%2C%22empty_size%22%3A%2220px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon%22%3A%22arrow_right%22%2C%22link_icon_margin%22%3A%224px%22%2C%22link_icon_size%22%3A%22140%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%2C%7B%22post_element%22%3A%22empty%22%2C%22empty_size%22%3A%2215px%22%2C%22title_size%22%3A%22normal%22%2C%22text_size%22%3A%22normal%22%2C%22content_width%22%3A%22100%25%22%2C%22content_length%22%3A%22200%22%2C%22link_icon_margin%22%3A%2210px%22%2C%22link_icon_size%22%3A%22100%25%22%2C%22link_border_left%22%3A%22none%22%2C%22link_border_top%22%3A%22none%22%2C%22link_border_right%22%3A%22none%22%2C%22link_border_bottom%22%3A%22none%22%2C%22cart_icon_margin%22%3A%2210px%22%2C%22cart_icon_size%22%3A%22100%25%22%2C%22cart_border_left%22%3A%22none%22%2C%22cart_border_top%22%3A%22none%22%2C%22cart_border_right%22%3A%22none%22%2C%22cart_border_bottom%22%3A%22none%22%2C%22sep_size%22%3A%22100%25%22%2C%22sep_width%22%3A%225px%22%2C%22sep_color%22%3A%22%23444444%22%2C%22sep_style%22%3A%22solid%22%7D%5D" post_setup_mode="manual" post_border_color="#3d3549"]
        [/vc_column][vc_column width="1/3"]
            [vc_blank_space height="60px" height_sm="100%" height_md="100%" height_lg="40%" height_xl="0%"]
            [vc_posts_overview categoryid="0" tagid="0" ppp="-1" posts_columns="1" posts_rows="1" posts_padding="0px" post_template="'.__("example-dynamic-content", 'greyd_hub').'" post_setup_mode="template" dynamic_post_content="'.__("example-dynamic-content", 'greyd_hub').'::icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'|_image_::title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'|_date_::title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'|_title_::content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'|%3Cp%3E_content%7C150_%3C%2Fp%3E::linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'|url%3A_post-link_%7Ctitle%3A'.urlencode(vc_encode(__("Learn More", 'greyd_hub'))).'%7Ctarget%3A%7Crel%3A::vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'|_image_"]
        [/vc_column][vc_column]
            [vc_blank_space height="100px" height_sm="60%" height_md="70%" height_lg="80%"]
        [/vc_column][/vc_row]'.
        '[vc_row el_id="form"][vc_column]
            [vc_blank_space height="100px" height_sm="60%" height_md="70%" height_lg="80%"]
        [/vc_column]
        [vc_column width="1/2"]
            [vc_headlines dynamic_title="" h="h5" title="'.__("The Perfect Form", 'greyd_hub').'"]
            [vc_headlines dynamic_title="" h="h3" title="'.__("with Greyd.Forms", 'greyd_hub').'"]
            [vc_column_text dynamic_content=""]'.__("Greyd.Suite’s built-in form generator contains everything you need for professional forms:", 'greyd_hub').'[/vc_column_text]
            [vc_blank_space height_sm="50%"]
            [vc_list vc_list_type="icon" list_icon_align_y="center" vc_list_icon="icon_check_alt" vc_list_colorselect="color_13"]'.
                '[vc_list_item dynamic_content=""]'.__("Native double opt-in", 'greyd_hub').'[/vc_list_item]'.
                '[vc_list_item dynamic_content=""]'.__("CRM interface", 'greyd_hub').'[/vc_list_item]'.
                '[vc_list_item dynamic_content=""]'.__("Image tiles & multi-level forms to boost your conversion", 'greyd_hub').'[/vc_list_item]'.
                '[vc_list_item dynamic_content=""]'.__("Conditional content for your campaigns", 'greyd_hub').'[/vc_list_item]'.
            '[/vc_list]
            [vc_blank_space]
        [/vc_column][vc_column width="1/2"]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_sdw_x="0px" vc_content_box_sdw_y="0px" vc_content_box_sdw_blur="50px" vc_content_box_sdw_x_hover="0px" vc_content_box_sdw_y_hover="0px" vc_content_box_sdw_blur_hover="30px" vc_content_box_sdw="true" vc_content_box_margin_top_sm="15px" vc_content_box_margin_right_sm="0px" vc_content_box_padding_top_sm="30px" vc_content_boxpadding_right_sm="30px" vc_content_box_link="|||" vc_content_box_padding_right_lg="40px" vc_content_box_margin_right_lg="30px" vc_content_box_sdw_colorselect="color_22" vc_content_box_sdw_colorselect_hover="color_22" vc_content_box_padding_right_sm="30px"]
                [vc_headlines dynamic_title="" h="h6" title="'.__("You Are Interested in Other Topics or Have Questions About Greyd.Suite?", 'greyd_hub').'"]
                [vc_blank_space height="15px" height_sm="100%" height_md="100%" height_lg="100%"]
                [vc_form id="{{default_contact_form}}"]
            [/vc_content_box]
        [/vc_column][vc_column]
            [vc_blank_space height="100px" height_sm="60%" height_md="70%" height_lg="80%"]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_color" vc_bg_colorselect="color_23"][vc_column]
            [vc_blank_space]
            [vc_headlines dynamic_title="" h="h5" align="_center" title="'.__("Get Started Now", 'greyd_hub').'"]
            [vc_headlines h="h4" align="_center" title="'.__("Find In-Depth Tutorials, Quick Learning Videos & FAQ here:", 'greyd_hub').'"]
            [vc_cbutton dynamic_linkk="" pull="_center" mode="_button" linkk="url:'.urlencode(__(self::$config->helpcenter, 'greyd_hub')).'|title:'.vc_encode(__("Open Helpcenter", 'greyd_hub')).'|target:%20_blank|"][/vc_cbutton]
        [/vc_column][/vc_row]';
        
        $thanks_page_content = 
        '[vc_row][vc_column width="1/4"]
        [/vc_column]
        [vc_column width="1/2"]
            [vc_blank_space height="140px"]
            [vc_cond_content]
                [vc_cond_content_item condition_type="&amp;&amp;" title="kein URL Parameter" options="%5B%7B%22type%22%3A%22urlparam%22%2C%22urlparam%22%3A%22custom%22%2C%22custom_urlparam%22%3A%22optin%22%2C%22condition_urlparam%22%3A%22empty%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%7D%2C%7B%22type%22%3A%22urlparam%22%2C%22urlparam%22%3A%22custom%22%2C%22custom_urlparam%22%3A%22optout%22%2C%22condition_urlparam%22%3A%22empty%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%7D%5D"]
                    [vc_headlines h="h1" title="'.__("Something went wrong...", 'greyd_hub').'"]
                    [vc_headlines dynamic_title="" h="h3" title="'.__("Sorry, we were unable to process your request because we could not confirm your identity.", 'greyd_hub').'"]
                [/vc_cond_content_item]
                [vc_cond_content_item title="optin" options="%5B%7B%22type%22%3A%22urlparam%22%2C%22urlparam%22%3A%22custom%22%2C%22custom_urlparam%22%3A%22optin%22%2C%22condition_urlparam%22%3A%22not_empty%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%7D%5D"]
                    [vc_headlines h="h1" title="'.__("Thank you!", 'greyd_hub').'"]
                    [vc_headlines dynamic_title="" h="h3" title="'.__("Your request will be processed. We will get back to you shortly.", 'greyd_hub').'"]
                [/vc_cond_content_item]
                [vc_cond_content_item title="optout" options="%5B%7B%22type%22%3A%22urlparam%22%2C%22urlparam%22%3A%22custom%22%2C%22custom_urlparam%22%3A%22optout%22%2C%22condition_urlparam%22%3A%22not_empty%22%2C%22condition_userrole%22%3A%22is%22%2C%22condition_time%22%3A%22is%22%7D%5D"]
                    [vc_headlines h="h1" title="'.__("What a pity...", 'greyd_hub').'"]
                    [vc_headlines dynamic_title="" h="h3" title="'.__("Your data has been deleted from our system.", 'greyd_hub').'"]
                [/vc_cond_content_item]
            [/vc_cond_content]
            [vc_blank_space height="100px"]
        [/vc_column]
        [vc_column width="1/4"]
        [/vc_column][/vc_row]';
        
        
        $default_form = 
        '[vc_row][vc_column]
            [vc_form_icon_panels box_shadow="yes" opacity=".8" required="true" label="'.__("I am interested in:", 'greyd_hub').'" name="preference" icon_size="32px" between_inner="15px" border_radius="4px" font_size=".6em" line_height="130%" between="10px" label_font_size=".8em" bgcolor="color_23" textcolor="color_21" bgcolor_hover="color_33" textcolor_hover="color_31" bgcolor_select="color_13" textcolor_select="color_62"]
                [vc_form_icon_panel icon="{{logo_dark}}" label="'.__('Customizer', 'greyd_hub').'" name="customizer" icon_select="{{logo_light}}"]
                [vc_form_icon_panel icon="{{logo_dark}}" label="'.__('Greyd.Hub', 'greyd_hub').'" name="hub" icon_select="{{logo_light}}"]
                [vc_form_icon_panel icon="{{logo_dark}}" label="'.__('Greyd.Forms', 'greyd_hub').'" name="forms" icon_select="{{logo_light}}"]
                [vc_form_icon_panel icon="{{logo_dark}}" label="'.__('Templates', 'greyd_hub').'" name="templates" icon_select="{{logo_light}}"]
            [/vc_form_icon_panels]
        [/vc_column][/vc_row]'.
        '[vc_row][vc_column]
            [vc_blank_space height="30px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_form_conditional dependant_field="preference" reset_after="true"]
                [vc_form_condition default_view="show" title="'.__("if nothing is filled in", 'greyd_hub').'"]
                    [vc_column_text dynamic_content="" css=".vc_custom_1587026973229{margin-top: -10px !important;}"]'.__("Choose which topic you're interested in by clicking on one of the image tiles.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height="30px" height_sm="100%" height_md="100%" height_lg="100%"]
                [/vc_form_condition]
                [vc_form_condition condition_type="&&" title="'.__("if interest has been selected", 'greyd_hub').'" options="%5B%7B%22field%22%3A%220%22%2C%22condition%22%3A%22not_empty%22%7D%5D"]
                    [vc_row_inner][vc_column_inner width="1/2"]
                        [vc_form_input autocomplete="name" required="true" label="'.__("My name", 'greyd_hub').'" name="name" placeholder="'.__("John Doe", 'greyd_hub').'" maxlength="50"]
                    [/vc_column_inner]
                    [vc_column_inner width="1/2"]
                        [vc_form_input type="email" autocomplete="email" required="true" label="'.__("Email (business)", 'greyd_hub').'" name="email" placeholder="'.__("jd@example.com", 'greyd_hub').'" maxlength="50"]
                    [/vc_column_inner][/vc_row_inner]
                    [vc_form_input label="'.__("I work at:", 'greyd_hub').'" name="company" maxlength="50"]
                    [vc_form_input autocomplete="off" area="true" label="'.__("My message", 'greyd_hub').'" name="message" maxlength="256" rows="3"]
                    [vc_form_checkbox required="true" name="'.__("Privacy policy", 'greyd_hub').'" fontsize=".8em" lineheight="120%"]'.__("You agree that your data will be used to process your request. Further information on the revocation can be found in the privacy policy.", 'greyd_hub').'[/vc_form_checkbox]
                    [vc_form_button]
                [/vc_form_condition]
            [/vc_form_conditional]
        [/vc_column][/vc_row]';

        $default_post_content = '
            <!-- wp:paragraph -->
            <p>'.__("This is your first post in Greyd.Suite.", 'greyd_hub').'</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph -->
            <p>'.__("With posts you can display for example company news or blog posts. If you want to show posts on a page, use the Greyd.Suite widget „Post Overview\". You can define the basic layout of your posts via the „single“ template.", 'greyd_hub').'</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph -->
            <p>'.__("Click here for Greyd's posts:", 'greyd_hub').' <a href="'.__(self::$config->homepage, 'greyd_hub').'">'.__("Visit the news page", 'greyd_hub').'</a>.</p>
            <!-- /wp:paragraph -->
        ';

        
        // dynamic templates
        $dt_1_content = 
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_gradient2="scroll|angle|29|color_21:0.0|color_31:100" el_id="Desktop Large"][vc_column offset="vc_hidden-md vc_hidden-sm vc_hidden-xs"]
            [vc_blank_space height="80px" height_sm="50%"]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62"]
                [vc_row_inner content_placement="middle"][vc_column_inner width="1/3"]
                    [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'" icon="{{img_light}}" width="80%"]
                [/vc_column_inner]
                [vc_column_inner width="2/3"]
                    [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("headline", 'greyd_hub')).'" h="h3" title="'.__("headline", 'greyd_hub').'" color="color_62"]
                    [vc_blank_space height="30px" height_sm="75%" height_md="80%" height_lg="90%"]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 01", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 02", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height_sm="75%" height_md="80%" height_lg="90%"]
                    [vc_cbutton style="down" dynamic_content="content|textarea|'.vc_encode(__('Button', 'greyd_hub')).'" dynamic_anchor="anchor|dropdown_anchor|'.vc_encode(__("Anchor", 'greyd_hub')).'" mode="_button" icon="arrow_down"]'.__("More Features", 'greyd_hub').'[/vc_cbutton]
                [/vc_column_inner][/vc_row_inner]
            [/vc_content_box]
            [vc_blank_space height="80px" height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_gradient2="scroll|angle|29|color_11:0.0|color_12:100.0" el_id="Desktop Mid"][vc_column offset="vc_hidden-lg vc_hidden-sm vc_hidden-xs"]
            [vc_blank_space height="80px" height_sm="50%"]
            [vc_row_inner][vc_column_inner]
                [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("headline", 'greyd_hub')).'" h="h3" title="'.__("headline", 'greyd_hub').'" color="color_62"]
                [vc_blank_space height="30px" height_sm="75%" height_md="80%" height_lg="90%"]
            [/vc_column_inner][/vc_row_inner]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62"]
                [vc_row_inner][vc_column_inner width="1/2"]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 01", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 02", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                    [vc_blank_space height_sm="75%" height_md="80%" height_lg="90%"]
                    [vc_cbutton style="down" dynamic_content="content|textarea|'.vc_encode(__('Button', 'greyd_hub')).'" dynamic_anchor="anchor|dropdown_anchor|'.vc_encode(__("Anchor", 'greyd_hub')).'" mode="_button _sec" icon="arrow_down"]'.__("More Features", 'greyd_hub').'[/vc_cbutton]
                [/vc_column_inner]
                [vc_column_inner width="1/2"]
                    [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'" pull="_center" icon="{{img_light}}"]
                [/vc_column_inner][/vc_row_inner]
            [/vc_content_box]
            [vc_blank_space height="80px" height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_color" el_id="Tablet" vc_bg_colorselect="color_12"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-xs"]
            [vc_blank_space height="80px" height_sm="50%"]
            [vc_row_inner][vc_column_inner]
                [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("headline", 'greyd_hub')).'" h="h3" align="_center" title="'.__("headline", 'greyd_hub').'" color="color_62"]
            [/vc_column_inner][/vc_row_inner]
            [vc_blank_space height="30px" height_sm="75%" height_md="80%" height_lg="90%"]
            [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'" pull="_center" icon="{{img_light}}" width="360px"]
            [vc_blank_space height="30px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62"]
                [vc_row_inner][vc_column_inner width="1/2"]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 01", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                [/vc_column_inner]
                [vc_column_inner width="1/2"]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 02", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                [/vc_column_inner][/vc_row_inner]
            [/vc_content_box]
            [vc_blank_space height_sm="75%" height_md="80%" height_lg="90%"]
            [vc_cbutton style="down" dynamic_content="content|textarea|'.vc_encode(__('Button', 'greyd_hub')).'" dynamic_anchor="anchor|dropdown_anchor|'.vc_encode(__("Anchor", 'greyd_hub')).'" pull="_center" mode="_button _sec" icon="arrow_down"]'.__("More Features", 'greyd_hub').'[/vc_cbutton]
            [vc_blank_space height="80px" height_sm="50%"]
        [/vc_column][/vc_row]'.
        '[vc_row vc_bg_type="vc_bg_gradient2" vc_bg_gradient2="scroll|angle|29|color_21:0.0|color_31:100" el_id="Smartphone"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-sm"]
            [vc_blank_space height="80px" height_sm="50%"]
            [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'" pull="_center" icon="{{img_light}}" width="70%"]
            [vc_blank_space height="30px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_row_inner][vc_column_inner]
                [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("headline", 'greyd_hub')).'" h="h3" align="_center" title="'.__("headline", 'greyd_hub').'" color="color_62"]
                [vc_blank_space height="30px" height_sm="75%" height_md="80%" height_lg="90%"]
            [/vc_column_inner][/vc_row_inner]
            [vc_content_box dynamic_vc_content_box_link="" vc_content_box_text_colorselect="color_62" vc_content_box_text_colorselect_hover="color_62"]
                [vc_row_inner][vc_column_inner]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 01", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Text block 02", 'greyd_hub')).'"]'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'[/vc_column_text]
                [/vc_column_inner][/vc_row_inner]
            [/vc_content_box]
            [vc_blank_space height_sm="75%" height_md="80%" height_lg="90%"]
            [vc_cbutton style="down" dynamic_content="content|textarea|'.vc_encode(__('Button', 'greyd_hub')).'" dynamic_anchor="anchor|dropdown_anchor|'.vc_encode(__("Anchor", 'greyd_hub')).'" pull="_center" mode="_button" icon="arrow_down"]'.__("More Features", 'greyd_hub').'[/vc_cbutton]
            [vc_blank_space height="80px" height_sm="50%"]
        [/vc_column][/vc_row]';

        $dt_2_content = 
        '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl" vc_bg_size="vc_bg_default" vc_bg_type="vc_bg_image" vc_bg_opacity="10" dynamic_vc_bg_image="vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'" vc_bg_image_repeat="no-repeat" vc_bg_image_position="center_center" ignore_inner_gutter="true" el_id="Desktop Large"][vc_column css=".vc_custom_1586868447623{margin-top: -20px !important;}" offset="vc_hidden-md vc_hidden-sm vc_hidden-xs"]
            [vc_content_box vc_content_box_padding_top_sm="30" vc_content_box_padding_right_sm="15"]
                [vc_row_inner content_placement="middle"][vc_column_inner width="1/3"]
                    [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'"]
                [/vc_column_inner][vc_column_inner width="2/3"]
                    [vc_blank_space height="25px"]
                    [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'"]
                    [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'" h="h5"]
                [/vc_column_inner][/vc_row_inner]
                [vc_row_inner][vc_column_inner]
                    [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'"][/vc_column_text]
                    [vc_blank_space height="25px"]
                    [vc_cbutton style="normal" dynamic_linkk="linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'" size="_small" mode="_button _sec"][/vc_cbutton]
                [/vc_column_inner][/vc_row_inner]
            [/vc_content_box]
        [/vc_column][/vc_row]'.
        '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl" vc_bg_size="vc_bg_default" ignore_inner_gutter="true" el_id="Desktop Mid - Tablet"][vc_column offset="vc_hidden-lg vc_hidden-xs"]
            [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'" width="50%"]
            [vc_blank_space height="25px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'" h="h5"]
            [vc_blank_space height="25px"]
            [vc_cbutton style="normal" dynamic_linkk="linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'" size="_small" mode="_button _sec"][/vc_cbutton]
        [/vc_column][/vc_row]'.
        '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl" vc_bg_size="vc_bg_default" ignore_inner_gutter="true" el_id="Smartphone"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-sm"]
            [vc_icons dynamic_icon="icon|file_picker|'.vc_encode(__("Image", 'greyd_hub')).'"]
            [vc_blank_space height="25px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'" h="h5"]
            [vc_blank_space height="25px"]
            [vc_cbutton style="normal" dynamic_linkk="linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'" size="_small" mode="_button _sec"][/vc_cbutton]
        [/vc_column][/vc_row]';

        $dt_3_content = 
        '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl" vc_bg_size="vc_bg_default" vc_bg_type="vc_bg_video" vc_bg_opacity="10" dynamic_vc_bg_video="vc_bg_video|textfield|'.vc_encode(__("Background video", 'greyd_hub')).'" ignore_inner_gutter="true" el_id="Desktop Large"][vc_column offset="vc_hidden-md vc_hidden-sm vc_hidden-xs"]
            [vc_row_inner content_placement="top"][vc_column_inner width="1/2" offset="vc_col-md-7"]
                [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'"]
                [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'" h="h5" css=".vc_custom_1586965519218{margin-right: -30% !important;}"]
            [/vc_column_inner]
            [vc_column_inner width="1/2" offset="vc_col-md-5"]
                [vc_cbutton style="normal" dynamic_linkk="linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'" pull="_right" size="_small" mode="_button _trd"][/vc_cbutton]
            [/vc_column_inner][/vc_row_inner]
            [vc_row_inner][vc_column_inner]
                [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'"]'.__('I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'greyd_hub').'[/vc_column_text]
            [/vc_column_inner][/vc_row_inner]
        [/vc_column][/vc_row]'.
        '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl" vc_bg_size="vc_bg_default" vc_bg_type="vc_bg_image" vc_bg_opacity="10" dynamic_vc_bg_image="vc_bg_image|file_picker_image|'.vc_encode(__("Background image", 'greyd_hub')).'" vc_bg_image_repeat="no-repeat" vc_bg_image_position="center_center" ignore_inner_gutter="true" el_id="Desktop Mid - Smartphone"][vc_column offset="vc_hidden-lg"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("short headline", 'greyd_hub')).'"]
            [vc_headlines dynamic_title="title|textarea|'.vc_encode(__("large headline", 'greyd_hub')).'" h="h5"]
            [vc_column_text dynamic_content="content|textarea_html|'.vc_encode(__("Description", 'greyd_hub')).'"]'.__('I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'greyd_hub').'[/vc_column_text]
            [vc_blank_space height="30px" height_sm="100%" height_md="100%" height_lg="100%"]
            [vc_cbutton style="normal" dynamic_linkk="linkk|vc_link|'.vc_encode(__("link", 'greyd_hub')).'" size="_small" mode="_button _trd"][/vc_cbutton]
        [/vc_column][/vc_row]';
        
        // media files
        $attachments = array(
            // Logo
            'logo_dark' => array(
                'slug'  => 'greyd-LogoPh-dark',
                'id'    => null,
                'url'   => null
            ),
            'logo_mid' => array(
                'slug'  => 'greyd-LogoPh-mid',
                'id'    => null,
                'url'   => null
            ),
            'logo_light' => array(
                'slug'  => 'greyd-LogoPh-light',
                'id'    => null,
                'url'   => null
            ),
            // Portrait
            'img_dark' => array(
                'slug'  => 'greyd-ImgPh-dark',
                'id'    => null,
                'url'   => null
            ),
            'img_mid' => array(
                'slug'  => 'greyd-ImgPh-mid',
                'id'    => null,
                'url'   => null
            ),
            'img_light' => array(
                'slug'  => 'greyd-ImgPh-light',
                'id'    => null,
                'url'   => null
            ),
            // Landscape
            'img_dark_wide' => array(
                'slug'  => 'greyd-ImgPh-dark-wide',
                'id'    => null,
                'url'   => null
            ),
            'img_mid_wide' => array(
                'slug'  => 'greyd-ImgPh-mid-wide',
                'id'    => null,
                'url'   => null
            ),
            'img_light_wide' => array(
                'slug'  => 'greyd-ImgPh-light-wide',
                'id'    => null,
                'url'   => null
            ),
        );

        // popup defaults
        function popup_default_content ( $atts=[] ) {
            return '[vc_row row_type="row_xxxl" ignore_inner_gutter="true"][vc_column]
                [vc_close align="_right" size="'.( isset($atts['close_size']) ? $atts['close_size'] : '30' ).'" width="'.( isset($atts['close_width']) ? $atts['close_width'] : '2' ).'"]
                [vc_headlines h="'.( isset($atts['h_tag']) ? $atts['h_tag'] : 'h3' ).'" title="'.( isset($atts['head']) ? $atts['head'] : __("headline", 'core') ).'"]
                [vc_column_text]'.( isset($atts['text']) ? $atts['text'] : __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit.", 'greyd_hub') ).'[/vc_column_text]
                [vc_blank_space height="'.( isset($atts['empty']) ? $atts['empty'] : '20' ).'px" height_sm="70" height_md="80" height_lg="90" height_xl="100"]
                '.( isset($atts['button']) && $atts['button'] === false ? '' :
                    '[vc_cbutton style="popup_close" mode="_button"]'.( isset($atts['button_text']) ? $atts['button_text'] : __("close", 'greyd_hub') ).'[/vc_cbutton]'
                ).'
            [/vc_column][/vc_row]';
        }
        $popup_design_default = array(
            // layout
            'position' => 'mc',
            'width' => 'auto',
            'width_custom' => '',
            'height' => 'auto',
            'height_custom' => '',
            'align' => 'middle',
            'padding_tb' => '30',
            'padding_lr' => '15',
            'margin_tb' => '30',
            'margin_lr' => '30',
            'mobile_enable' => 'false',
            'mobile_width' => 'auto',
            'mobile_width_custom' => '',
            'mobile_height' => 'auto',
            'mobile_height_custom' => '',
            'mobile_align' => 'middle',
            'mobile_padding_tb' => '20',
            'mobile_padding_lr' => '10',
            'mobile_margin_tb' => '20',
            'mobile_margin_lr' => '20',
            // design
            'text_color' => 'color_21',
            'head_color' => 'color_21',
            'bg_color' => 'color_23',
            'border_radius' => '5',
            'border_enable' => 'false',
            'border_width' => '1',
            'border_color' => 'color_21',
            'border_style' => 'solid',
            'shadow_enable' => 'true',
            'shadow' => '0px+5px+8px+-1px+color_31+25',
            'hover_enable' => 'false',
            'hover_text_color' => 'color_21',
            'hover_head_color' => 'color_21',
            'hover_bg_color' => 'color_23',
            'hover_border_color' => 'color_21',
            'hover_shadow' => '0px+5px+8px+-1px+color_31+25',
            // extended
            'overlay_enable' => 'true',
            'overlay_color' => 'color_61',
            'overlay_opacity' => '25',
            'overlay_effect' => 'none',
            'overlay_effect_blur' => '10',
            'overlay_effect_brightness' => '110',
            'overlay_effect_contrast' => '110',
            'overlay_effect_grayscale' => '10',
            'overlay_effect_hue-rotate' => '10',
            'overlay_effect_invert' => '10',
            'overlay_effect_saturate' => '110',
            'overlay_effect_sepia' => '10',
            'overlay_close' => 'true',
            'overlay_noscroll' => 'false',
            'anim_type' => 'scale',
            'anim_time' => '300',
            'overflow' => 'auto',
            'css_class' => '',
        );
        $popup_settings_default = array(
            'on_init'   => (object) array( 'value' => 'off' ),
            'on_scroll' => (object) array( 'value' => 'off' ),
            'on_idle'   => (object) array( 'value' => 'on', 'options' => (object) array( 'time' => '15' ) ),
            'on_exit'   => (object) array( 'value' => 'on' ),
            'on_page'   => (object) array( 'value' => 'on' ),
            'on_post'   => (object) array( 'value' => 'custom', 'options' => (object) array( 'items' => '', 'show_on' => '' ) ),
            'delay'     => (object) array( 'value' => 'off' ),
            'urlparam'  => (object) array( 'value' => 'off' ),
            'referer'   => (object) array( 'value' => 'off' ),
            'user'      => (object) array( 'value' => 'off' ),
            'time'      => (object) array( 'value' => 'off' ),
            'device'    => (object) array( 'value' => 'off' ),
            'browser'   => (object) array( 'value' => 'off' ),
            'prio'      => (object) array( 'value' => '5' ),
            'highest'   => (object) array( 'value' => 'true' ),
            'hashtag'   => (object) array( 'value' => 'true' ),
            'once'      => (object) array( 'value' => 'true' ),
        );


        // define all default contents
        self::$default_contents["shortcodes"] = array(
            'starter' => array(
                'frontpage' => array(
                    'type'         => 'page',
                    'slug'         => __("example-page", 'greyd_hub'),
                    'title'        => __("Example page", 'greyd_hub'),
                    'content'      => $starter_page_content,
                )
            ),
            'pages' => array(
                'homepage' => array(
                    'type'         => 'page',
                    'slug'         => __("example-page", 'greyd_hub'),
                    'title'        => __("Example page", 'greyd_hub'),
                    'content'      => $default_page_content,
                    'force'        => true,
                    'meta'         => array(
                        '_announce_active' => 'on',
                        '_announce_content' => __("Welcome to Greyd.Suite -", 'greyd_hub'),
                        '_announce_read_more_text' => _x("view website", 'small', 'greyd_hub'),
                        '_announce_read_more_link' => __(self::$config->homepage, 'greyd_hub'),
                        '_announce_image' => '{{logo_light::url}}'
                    )
                ),
                'thanks' => array(
                    'type'         => 'page',
                    'slug'         => __('thank-you-page', 'greyd_hub'),
                    'title'        => __("Thank you page", 'greyd_hub'),
                    'content'      => $thanks_page_content
                )
            ),
            'posts' => array(
                'hallo-greyd' => array(
                    'type'         => 'post',
                    'slug'         => __("hello-greyd", 'greyd_hub'),
                    'title'        => __("Hello Greyd.", 'greyd_hub'),
                    'content'      => $default_post_content,
                    'img'          => '{{img_dark}}'
                )
            ),
            // 'menus' => array(
            //     'main-menu' => array(
            //         'name'         => __('Greyd Standard Menü', 'greyd_hub'),
            //         'location'     => 'main-menu',
            //         'items'         => [
            //             [
            //                 'title'     => __("Example page", 'greyd_hub'),
            //                 'type'      => 'page',
            //                 'url'       => ''
            //             ],
            //             [
            //                 'title'     => __('Tutorials', 'greyd_hub'),
            //                 'type'      => 'link',
            //                 'url'       => ''
            //             ]
            //         ]
            //     )
            // ),
            'media' => $attachments,
            'forms' => array(
                'default_contact_form' => array(
                    'type'         => 'tp_forms',
                    'slug'         => __('greyd-basic-contactform', 'greyd_hub'),
                    'title'        => __("Example form", 'greyd_hub'),
                    'content'      => $default_form,
                    'meta'         => array(
                        'ignore_gutter'     => 'ignore_gutter',
                        'success_message'   => __("Your request was successful - but this is an automatically generated test form. The content belongs to the owner of the site and cannot be forwarded to Greyd.", 'greyd_hub'),
                        'mail_to'           => get_option('admin_email'),
                    )
                )
            ),
            'posttypes' => array(
                'my_post_type' => array(
                    'type'         => 'tp_posttypes',
                    'slug'         => __("myposttype", 'greyd_hub'),
                    'title'        => __("My post type", 'greyd_hub'),
                    'content'      => '',
                    'meta'        => array(
                        'posttype_settings' => array(
                            'title' => __("My post type", 'greyd_hub'),
                            'slug' => __("myposttype", 'greyd_hub'),
                            'singular' => __("My post type", 'greyd_hub'),
                            'plural' => __("My post types", 'greyd_hub'),
                            'icon' => 'lightbulb',
                            'position' => '6',
                            'supports' => [
                                'editor' => 'editor'
                            ],
                            'arguments' => [
                                'search' => 'search',
                                'archive' => 'archive'
                            ],
                            'fields' => [
                                [
                                    'label' => __("Question", 'greyd_hub'),
                                    'name' => 'question',
                                    'type' => 'text',
                                    'required' => 'required',
                                ],
                                [
                                    'label' => __("Description", 'greyd_hub'),
                                    'name' => 'description',
                                    'type' => 'textarea',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("link", 'greyd_hub'),
                                    'name' => 'link',
                                    'type' => 'url',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("Image", 'greyd_hub'),
                                    'name' => 'picture',
                                    'type' => 'file',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("Topic", 'greyd_hub'),
                                    'name' => 'topic',
                                    'type' => 'text',
                                    'required' => 'required',
                                ]
                            ]
                        )
                    )
                )
            ),
            'custom_posts' => array(
                'mypost_contactform' => array(
                    'type'         => __("myposttype", 'greyd_hub'),
                    'slug'         => __('contactform', 'greyd_hub'),
                    'title'        => __("Contact form", 'greyd_hub'),
                    'content'      => '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl"][vc_column]
                                            [vc_video dynamic_link="" link="https://vimeo.com/402085198"]
                                        [/vc_column][/vc_row]',
                    'meta' => array(
                        'dynamic_meta' => [
                            'topic' => __("Contact form", 'greyd_hub'),
                            'question' => __("How do I create a contact form?", 'greyd_hub'),
                            'description' => __("In this video, we will show you which widgets you use to create a simple contact form. You can also learn how to set up email notifications and secure your form with the integrated double opt-in function.", 'greyd_hub'),
                            'link' => __("https://docs.greyd.io/video/how-do-i-create-a-contact-form/", 'greyd_hub'),
                            'picture' => '{{img_dark}}'
                        ]
                    )
                ),
                'mypost_seitenumbau' => array(
                    'type'         => __("myposttype", 'greyd_hub'),
                    'slug'         => __("page rebuild", 'greyd_hub'),
                    'title'        => __("Page rebuild", 'greyd_hub'),
                    'content'      => '[vc_row row_type="row_xxxl" override_gutter="override_sm,override_md,override_lg,override_xl"][vc_column]
                                            [vc_video dynamic_link="" link="https://vimeo.com/402085198"]
                                        [/vc_column][/vc_row]',
                    'meta' => array(
                        'dynamic_meta' => [
                            'topic' => __("Page rebuild", 'greyd_hub'),
                            'question' => __("How do I change an existing page?", 'greyd_hub'),
                            'description' => __("Learn here how to completely redesign the design and layout of an existing page with just a few clicks. An exciting and time-saving alternative to starting with a completely empty page!", 'greyd_hub'),
                            'link' => __("https://helpcenter.greyd.io/topics/how-to-start/?post_type=tutorial", 'greyd_hub'),
                            'picture' => '{{img_dark}}'
                        ]
                    )
                )
            ),
            'popups' => array(
                // presets
                'empty' => array(
                    'icon'         => 'new_select.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('empty', 'greyd_hub'),
                    'title'        => __("empty", 'greyd_hub'),
                    'content'      => '[vc_row row_type="row_xxxl"][vc_column][/vc_column][/vc_row]',
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) array( ),
                    )
                ),
                'classic' => array(
                    'icon'         => 'popup_classic.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('classic', 'greyd_hub'),
                    'title'        => __("Classic", 'greyd_hub'),
                    'content'      => popup_default_content( [ 'head' => __("Classic", 'greyd_hub') ] ),
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) $popup_design_default,
                    )
                ),
                'slidein' => array(
                    'icon'         => 'popup_slidein.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('slidein', 'greyd_hub'),
                    'title'        => __("Slide in", 'greyd_hub'),
                    'content'      => popup_default_content( [
                        'head' => __("Slide in", 'greyd_hub'),
                        'close_size' => '20',
                        'h_tag' => 'h5',
                        'button' => false,
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_scroll' => (object) array( 'value' => 'on', 'options' => (object) array( 'height' => '50%' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                    'title'        => __('Fullscreen', 'greyd_hub'),
                    'content'      => popup_default_content( [
                        'head' => __('Fullscreen', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                        'close_size' => '50',
                        'close_width' => '4',
                        'h_tag' => 'h2',
                        'empty' => '50',
                        'button_text' => _x("close popup", 'small', 'greyd_hub')
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) array_merge(
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
                    'content'      => popup_default_content( [
                        'head' => __('Announcement', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_init'   => (object) array( 'value' => 'on', 'options' => (object) array( 'time' => '5' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                    'content'      => popup_default_content( [
                        'head' => __('Sidebar', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                        'close_size' => '40',
                        'close_width' => '3',
                        'empty' => '100',
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_init'   => (object) array( 'value' => 'on', 'options' => (object) array( 'time' => '5' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                //     'content'      => popup_default_content( [ 'head' => __('Slide Up', 'greyd_hub') ] ),
                //     'meta' => array(
                //         'popup_settings' => (object) array_merge(
                //             $popup_settings_default,
                //             array(
                //                 'xxx' => 'xxx',
                //             )
                //         ),
                //         'popup_design' => (object) array_merge(
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
            ),
            'templates' => array(
                // dynamic templates
                'dt_1' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-what-is-a-dynamic-template", 'greyd_hub'),
                    'title'        => __("Example: What is a Dynamic Template", 'greyd_hub'),
                    'content'      => $dt_1_content
                ),
                'dt_2' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-dynamic-content", 'greyd_hub'),
                    'title'        => __("Example: Displaying dynamic content", 'greyd_hub'),
                    'content'      => $dt_2_content
                ),
                'dt_3' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-display-post-type", 'greyd_hub'),
                    'title'        => __("Example: Display of a post type", 'greyd_hub'),
                    'content'      => $dt_3_content
                ),
                // templates
                'footer' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'footer',
                    'title'        => __('Footer', 'greyd_hub'),
                    'content'      => $footer_content
                ),
                'main-menu-offcanvas' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'main-menu-offcanvas',
                    'title'        => __("Main menu (off-canvas)", 'greyd_hub'),
                    'content'      => sprintf($menu_content, 'main-menu-offcanvas')
                ),
                'meta-menu-offcanvas' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'meta-menu-offcanvas',
                    'title'        => __("Meta menu (off-canvas)", 'greyd_hub'),
                    'content'      => sprintf($menu_content, 'meta-menu-offcanvas')
                ),
                'single' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'single',
                    'slug'         => 'single',
                    'title'        => __("Post page", 'greyd_hub'),
                    'content'      => $single_content
                ),
                'archives' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'archives',
                    'slug'         => 'archives',
                    'title'        => __("Post archive", 'greyd_hub'),
                    'content'      => $archive_content
                ),
                'search' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'search',
                    'slug'         => 'search',
                    'title'        => __("Search results", 'greyd_hub'),
                    'content'      => $search_content
                ),
                '404' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'more',
                    'slug'         => '404',
                    'title'        => __("404 page", 'greyd_hub'),
                    'content'      => $fourofour_content
                ),
                'compatibility' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'more',
                    'slug'         => 'compatibility',
                    'title'        => __("Compatibility message", 'greyd_hub'),
                    'content'      => '[vc_row][vc_column][vc_column_text]'.__("Compatibility message", 'greyd_hub').'[/vc_column_text][/vc_column][/vc_row]' // $compatibility_content
                ),
                // woocommerce templates
                'woo_product' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product',
                    'title'        => __("Product page (WooCommerce)", 'greyd_hub'),
                    'content'      => '[vc_row][vc_column]
                                            [vc_headlines h="h2" title="'.__("Product page", 'greyd_hub').'"]
                                            [woo_content]
                                            [vc_blank_space height="20px"]
                                            [vc_column_text]'.sprintf($default_message, 'woo-product').'[/vc_column_text]
                                            [vc_blank_space height="20px"]
                                        [/vc_column][/vc_row]'
                ),
                'woo-product-category' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product-category',
                    'title'        => __("Shop overview (by category)", 'greyd_hub'),
                    'content'      => '[vc_row][vc_column]
                                            [vc_headlines h="h2" title="'.__("Shop overview by category", 'greyd_hub').'"]
                                            [woo_content]
                                            [vc_blank_space height="20px"]
                                            [vc_column_text]'.sprintf($default_message, 'woo-product-category').'[/vc_column_text]
                                            [vc_blank_space height="20px"]
                                        [/vc_column][/vc_row]'
                ),
                'woo-product-tag' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product-tag',
                    'title'        => __("Shop overview by tag", 'greyd_hub'),
                    'content'      => '[vc_row][vc_column]
                                            [vc_headlines h="h2" title="'.__("Shop overview by keyword", 'greyd_hub').'"]
                                            [woo_content]
                                            [vc_blank_space height="20px"]
                                            [vc_column_text]'.sprintf($default_message, 'woo-product-tag').'[/vc_column_text]
                                            [vc_blank_space height="20px"]
                                        [/vc_column][/vc_row]'
                ),
            ),
            'products' => array(
                // dynamic templates
                'greyd_product' => array(
                    'type'         => 'product',
                    'slug'         => __("greyd-example-product", 'greyd_hub'),
                    'title'        => __("Example product", 'greyd_hub'),
                    'content'      => __("This is the content of the sample product.", 'greyd_hub'),
                    'excerpt'      => __("This is the short description of the sample product.", 'greyd_hub'),
                    'img'          => '{{product01}}',
                    'meta'         => array(
                        '_product_image_gallery' => '{{product02}}',
                        '_mini_desc'        => __( "This is the shopping cart short description.", 'greyd_hub' ),
                        '_regular_price'    => '99.99',
                        '_price'            => '99.99',
                        '_virtual'          => 'yes',
                    )
                ),
            ),
            'media-product' => array(
                'product01' => array(
                    'slug'  => 'greyd-product-01',
                    'id'    => null,
                    'url'   => null,
                    'format'=> 'png'
                ),
                'product02' => array(
                    'slug'  => 'greyd-product-02',
                    'id'    => null,
                    'url'   => null,
                    'format'=> 'png'
                )
            ),
            'menu' => array(
                'name'      => __("Greyd Example menu", 'greyd_hub'),
                'location'  => 'main-menu'
            )
        );
    }

    public static function init_gutenberg_default_contents() {
        if(!is_admin()) return;
        
        // add wordings
        $default_message = "<p>".__("The content of this section can be set within the template \"%s\".", 'greyd_hub')."<br><a href='".home_url()."/wp-admin/edit.php?post_type=dynamic_template'>".__("View Templates", 'greyd_hub')."</a></p>";
        
        self::$wordings["blocks"] = [
           "default_content" => '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
                                <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                <div class="wp-block-column col-12"><!-- wp:spacer {"className":""} -->
                                <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
                                <!-- /wp:spacer -->
                                
                                <!-- wp:paragraph {"className":""} -->
                                <p>".$default_message."</p>
                                <!-- /wp:paragraph -->
                                
                                <!-- wp:spacer {"className":""} -->
                                <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
                                <!-- /wp:spacer --></div>
                                <!-- /wp:column --></div>
                                <!-- /wp:columns -->',
            // "empty_content" => '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
            //                     <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
            //                     <div class="wp-block-column col-12"></div>
            //                     <!-- /wp:column --></div>
            //                     <!-- /wp:columns -->',
            "empty_content" => '',
        
        ];
        
        // Menu content
        $menu_content = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":20} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:greyd/menu {"menu":"greyd-beispiel-menue","greydClass":"gs_CDrqiI","listStyles":{"flexDirection":"column","alignItems":"flex-start","responsive":{"lg":{"flexDirection":"","alignItems":""},"md":{"flexDirection":"","alignItems":""},"sm":{"flexDirection":"","alignItems":""}}},"itemStyles":{"fontSize":""},"subStyles":{}} -->
        <style class="greyd-styles">.gs_CDrqiI { flex-direction: column !important; align-items: flex-start !important; text-align: left !important; } </style>
        <!-- /wp:greyd/menu -->
        
        <!-- wp:spacer {"height":20} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:paragraph -->
       '.$default_message.'
        <!-- /wp:paragraph -->
        
        <!-- wp:spacer {"height":20} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // footer
        $footer_content = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":20} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:greyd/menu {"menu":"greyd-beispiel-menue","greydClass":"gs_nY2Rqz","listStyles":{"flexDirection":"column","alignItems":"flex-start","responsive":{"lg":{"flexDirection":"","alignItems":""},"md":{"flexDirection":"","alignItems":""},"sm":{"flexDirection":"","alignItems":""}}},"itemStyles":{"fontSize":""},"subStyles":{}} -->
        <style class="greyd-styles">.gs_nY2Rqz { flex-direction: column !important; align-items: flex-start !important; text-align: left !important; } </style>
        <!-- /wp:greyd/menu -->
        
        <!-- wp:spacer {"height":20} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:columns {"className":"row_xxl "} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"xs":"col-12","sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:paragraph {"align":"right"} -->
        <p class="has-text-align-right"><span data-tag="symbol" data-params="{&quot;symbol&quot;:&quot;copyright&quot;}" class="is-tag">Symbol</span> <span data-tag="now" data-params="{&quot;format&quot;:&quot;Y&quot;}" class="is-tag">Heute</span> <span data-tag="site-title" data-params="{&quot;isLink&quot;:true}" class="is-tag">Website Titel</span></p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // 404 content
        $fourofour_content = 
        '<!-- wp:columns {"className":"row_l ","row":{"type":"row_l"}} -->
        <div class="wp-block-columns row_l"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":140} -->
        <div style="height:140px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:heading {"level":1} -->
        <h1>'.__("Error", 'greyd_hub').' 404</h1>
        <!-- /wp:heading -->
        
        <!-- wp:paragraph -->
        <p>'.__("The page could not be found.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-16a8d7b6-d913-4df7-8ee5-dfe1515b336b","greydClass":"gs_EEKa0Z","trigger":{"type":"link","params":{"url":"'.home_url().'","title":"'.(__("to the home page", 'greyd_hub')).'","opensInNewTab":0}},"content":"'.(__("to the home page", 'greyd_hub')).'","className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_EEKa0Z "><span style="flex:1">'.(__("to the home page", 'greyd_hub')).'</span></a>
        <!-- /wp:greyd/button -->

        <!-- wp:greyd/button {"inline_css_id":"block-d2f7db5c-4bf7-4d9a-aceb-4726379657ef","greydClass":"gs_e6cMfp","trigger":{"type":"back"},"content":"'.__("go back", 'greyd_hub').'","icon":{"content":"arrow_left","position":"before","size":"100%","margin":"10px"},"className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_e6cMfp "><span class="arrow_left" style="vertical-align:middle;font-size:100%;margin-right:10px" aria-hidden="true"></span><span style="flex:1">'.__("go back", 'greyd_hub').'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
    
        
        <!-- wp:spacer {"height":180} -->
        <div style="height:180px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // Compatibility content
        $compatibility_content = '
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"className":""} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->

        <!-- wp:heading {"textAlign":"center","level":3,"className":""} -->
        <h3 class="has-text-align-center">'.__("Sorry, your browser does not support all features of this website.", 'greyd_hub').'</h3>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"className":""} -->
        <p>'.__("Please update it to a new version or install the more modern browsers below.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->

        <!-- wp:spacer {"className":""} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->

        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-c03a5f81-ee18-4e10-a3e2-a730ab258524","greydClass":"gs_KKZIwj","trigger":{"type":"link","params":{"url":"https://www.google.com/chrome/","title":"'.(__("download Google Chrome", 'greyd_hub')).'","opensInNewTab":1}},"content":"'.(__("download Google Chrome", 'greyd_hub')).'","className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_KKZIwj "><span style="flex:1">'.(__("download Google Chrome", 'greyd_hub')).'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->

        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-d88d6e4d-d994-4e12-9c02-63acd4e312ad","greydClass":"gs_pscZnT","trigger":{"type":"link","params":{"url":"https://www.mozilla.org/de/firefox/","title":"'.(__("download Google Firefox", 'greyd_hub')).'","opensInNewTab":1}},"content":"'.(__("download Google Firefox", 'greyd_hub')).'","className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_pscZnT "><span style="flex:1">'.(__("download Google Firefox", 'greyd_hub')).'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->

        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-adbb679b-8ee8-4d40-87f1-97172cb3fac5","greydClass":"gs_GUpnOe","trigger":{"type":"link","params":{"url":"https://www.opera.com/de/download","title":"'.(__("download Opera", 'greyd_hub')).'","opensInNewTab":1}},"content":"'.(__("download Opera", 'greyd_hub')).'","className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_GUpnOe "><span style="flex:1">'.(__("download Opera", 'greyd_hub')).'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->

        <!-- wp:spacer {"className":""} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // single content
        $single_content = 
        '<!-- wp:group -->
        <div class="wp-block-group"><!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-84e71dfa-8b1b-4f08-8426-c52766aeab4f","greydClass":"gs_LfGDzE","trigger":{"type":"back"},"content":"zurück","icon":{"content":"arrow_left","position":"before","size":"100%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_LfGDzE "><span class="arrow_left" style="vertical-align:middle;font-size:100%;margin-right:10px" aria-hidden="true"></span><span style="flex:1">zurück</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 ","responsive":{"width":{"md":"col-md-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8"><!-- wp:greyd/image {"image":{"id":-1,"url":"","tag":"image","type":"dynamic"},"greydClass":"gs_klChAt","greydStyles":{},"align":null,"className":""} /-->
        <!-- wp:spacer {"height":20,"responsive":{"height":{"sm":"70","md":"80","lg":"90","xl":"100"}}} -->
        <div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:paragraph {"className":""} -->
        <p><span data-tag="date" data-params="&quot;&quot;" class="is-tag">Date</span></p>
        <!-- /wp:paragraph -->
        <!-- wp:heading {"className":""} -->
        <h2 id="titel"><span data-tag="title" data-params="&quot;&quot;" class="is-tag">Titel</span></h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":""} -->
        <p><span data-tag="content" data-params="&quot;&quot;" class="is-tag">Inhalt</span></p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-4 col-lg-3 offset-lg-1 ","responsive":{"width":{"md":"col-md-4","lg":"col-lg-3","sm":""},"offset":{"lg":"offset-lg-1"}}} -->
        <div class="wp-block-column col-12 col-md-4 col-lg-3 offset-lg-1"><!-- wp:paragraph {"className":""} -->
        <p><span class="has-fontsize-small">Autor:</span><br><br><span data-tag="author" data-params="&quot;&quot;" class="is-tag">Autor</span><br><br><br><br><span class="has-fontsize-small">Kategorien:</span><br><br><span data-tag="categories" data-params="&quot;&quot;" class="is-tag">Kategorien</span></p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:group -->
        <!-- wp:separator -->
        <hr class="wp-block-separator"/>
        <!-- /wp:separator -->';
        
        $search_content = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:search {"showLabel":false} /-->
        
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:query {"queryId":0,"query":{"inherit":true,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":10,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"icon","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:greyd/box {"greydClass":"gs_FFpVV9","greydStyles":{"padding":{"top":"20px","right":"0px","bottom":"20px","left":"0px"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:post-date {"style":{"typography":{"fontSize":"13px"}},"textColor":"color-32"} /-->
        
        <!-- wp:post-title {"level":4} /-->
        
        <!-- wp:post-excerpt /-->
        
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-bb44b6a0-86db-4c66-beb6-7253a06b64da","greydClass":"gs_D20H05","trigger":{"type":"dynamic","params":{"tag":"post"}},"content":"learn more","className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_D20H05 "><span style="flex:1">Mehr erfahren</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box -->
        <!-- /wp:post-template -->
        
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        $archive_content = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-5878fa21-6570-4f2d-9750-3dc10576dfaa","greydClass":"gs_fyif6n","trigger":{"type":"back"},"content":"'.__("back", 'greyd_hub').'","icon":{"content":"arrow_left","position":"before","size":"100%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_fyif6n "><span class="arrow_left" style="vertical-align:middle;font-size:100%;margin-right:10px" aria-hidden="true"></span><span style="flex:1">'.__("back", 'greyd_hub').'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        
        <!-- wp:heading -->
        <h2>'.__("Posts", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 ","responsive":{"width":{"md":"col-md-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8"><!-- wp:query {"queryId":1,"query":{"inherit":true,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":10,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"icon","icon_normal":"icon_circle-empty","icon_active":"icon_circle-slelected","icon_previous":"arrow_left","icon_next":"arrow_right","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:greyd/box {"greydClass":"gs_ASFSkC","greydStyles":{"padding":{"top":"20px","right":"0px","bottom":"20px","left":"0px"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:post-date /-->
        
        <!-- wp:post-title {"level":4} /-->
        
        <!-- wp:post-excerpt /-->
        
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-a271b82a-8357-44c7-9150-31df65a1aaa7","greydClass":"gs_MzNAAT","trigger":{"type":"dynamic","params":{"tag":"post"}},"content":"'.(__("Learn More", 'greyd_hub')).'","icon":{"content":"arrow_right","margin":"10px","size":"150%"},"className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_MzNAAT "><span style="flex:1">'.(__("Learn More", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box -->
        <!-- /wp:post-template -->
        
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query -->
        
        <!-- wp:spacer {"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%","xl":"0%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        
        <!-- wp:column {"className":"col-12 col-md-4 col-lg-3 offset-lg-1 ","responsive":{"width":{"md":"col-md-4","lg":"col-lg-3","sm":""},"offset":{"lg":"offset-lg-1"}}} -->
        <div class="wp-block-column col-12 col-md-4 col-lg-3 offset-lg-1"><!-- wp:archives {"displayAsDropdown":0,"showPostCounts":0,"filter":{"post_type":"post","type":"yearly","order":"","hierarchical":0,"date_format":""},"styles":{"style":"","size":"","custom":0,"icon":{"content":"","position":"after","size":"100%","margin":"10px"}},"customStyles":{},"align":"left"} /-->
        
        <!-- wp:archives {"displayAsDropdown":0,"showPostCounts":0,"filter":{"post_type":"post","type":"category","order":"","hierarchical":0,"date_format":""},"styles":{"style":"sec","size":"","custom":0,"icon":{"content":"","position":"after","size":"100%","margin":"10px"}},"customStyles":{},"align":"left"} /-->
        
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:search {"showLabel":false,"placeholder":"'.__("Enter search term...", 'greyd_hub').'","buttonText":"'.__("Start search", 'greyd_hub').'"} /--></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        $starter_page_content = 
        '<!-- wp:columns {"backgroundColor":"color-33","className":"row_xxl ","row":{"type":"row_xxl"},"background":{"type":"image","opacity":100,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"image":{"id": {{img_light_wide}} ,"url":"{{img_light_wide::url}}","size":"contain","repeat":"no-repeat","position":"top center"},"overlay":{"type":"gradient","opacity":30,"color":"","gradient":"color-31-to-color-63-s"}}} -->
        <div class="wp-block-columns row_xxl has-color-33-background-color has-background"><!-- wp:column {"className":"col-12 wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":80,"responsive":{"height":{"sm":"20%","md":"20%","lg":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12, wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:heading {"textAlign":"center","level":4,"className":"has-text-align-center"} -->
        <h4 class="has-text-align-center">'.__("This Is Your New Page", 'greyd_hub').'</h4>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":1} -->
        <h1 class="has-text-align-center">'.__("With Greyd.Suite", 'greyd_hub').'</h1>
        <!-- /wp:heading -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_xurjKj","trigger":{"type":"scroll","params":"features"},"size":"big","content":"'._x("Show Me More", 'small', 'greyd_hub').'","className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_xurjKj big"><span style="flex:1">'._x("Show Me More", 'small', 'greyd_hub').'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"height":140,"responsive":{"height":{"sm":"60%"}}} -->
        <div style="height:140px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 col-lg-7 wp-block-column","responsive":{"width":{"md":"col-md-8","lg":"col-lg-7","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8 col-lg-7"><!-- wp:heading {"level":5} -->
        <h5>'.__("Intuitive Design", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":2} -->
        <h2>'.__("Thanks to natively integrated Gutenberg Editor", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Greyd.Suite is the only all-in-one solution that is fully integrated in the new WordPress editor. We have enhanced the native functions and added powerful Greyd.Suite features. For the design you will find global settings for your entire website in the Customizer.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-920533f7-2654-47ec-85e9-42726ffa5a9d","greydClass":"gs_Qo2uvO","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"'._x("Open Customizer", 'small', 'greyd_hub').'","opensInNewTab":1}},"content":"'._x("Open Customizer", 'small', 'greyd_hub').'","icon":{"content":"icon_tool","position":"after","size":"100%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_Qo2uvO "><span style="flex:1">'._x("Open Customizer", 'small', 'greyd_hub').'</span><span class="icon_tool" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1","responsive":{"width":{"xs":"col-10","md":"col-md-4","lg":"col-lg-5","sm":""},"offset":{"xs":"offset-1","sm":"offset-sm-3","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1"></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:greyd/dynamic {"template":"'.__("example-what-is-a-dynamic-template", 'greyd_hub').'","dynamic_content":[{"dkey":"icon","dtype":"file_picker","dtitle":"Bild","dvalue":""},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("headline", 'greyd_hub')).'","dvalue":"'.(__("Dynamic Templates Responsively Designed", 'greyd_hub')).'"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Text block 01", 'greyd_hub')).'","dvalue":"'.__("Try shrinking your browser window and see how this area changes. How does it work?", 'greyd_hub').'"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Text block 02", 'greyd_hub')).'","dvalue":"'.__("With Greyd.Suite’s Dynamic Templates! They allow you to create different layouts for each breakpoint, but you only need to enter your content once.", 'greyd_hub').'"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Button', 'greyd_hub')).'","dvalue":"'.__("More Features", 'greyd_hub').'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__("Anchor", 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22scroll%22%2C%22params%22%3A%22features%22%7D"}]} /--></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"lg":"100%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/anchor {"anchor":"features"} -->
        <div id="features" class="greyd-anchor-target"></div>
        <!-- /wp:greyd/anchor --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"verticalAlignment":"bottom","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-bottom row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 ","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_j51agd","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Popups', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("Automated Popups in Your Website's Design", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Create unique popups that will automatically adapt to your website’s design with the integrated popup builder. You will find lots of different triggers, animations, rules and modules to choose from.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_9oQDI5","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"'.(__("View Popups", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Popups", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_9oQDI5 small"><span style="flex:1">'.(__("View Popups", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 ","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_fJGb5f","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Hub', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("All Your Projects In One Backend", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("No matter how many pages your installation contains: In Greyd. Hub you can centrally manage all your websites, import and export content, designs or entire websites with just one click, and manage backups easily.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_rXaywR","trigger":{"type":"link","params":{"url":"/wp-admin/admin.php?page=greyd_hub","title":"'.(__("View Dashboard", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Dashboard", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_rXaywR small"><span style="flex:1">'.(__("View Dashboard", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"verticalAlignment":"top","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-top row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_HLljXL","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Forms', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5,"inline_css":"padding-top: 0px !important;\npadding-right: 0px !important;\npadding-bottom: 0px !important;\npadding-left: 0px !important;\n"} -->
        <h5>'.__("Create Professional Forms", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Whether simple contact form with double opt-in, conversion-optimized lead form or complex multistep forms with CRM connection and mathematical calculations – with Greyd. Forms you don't need any additional plugins!", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_QjKw7p","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=tp_forms","title":"'.(__("View Forms", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Forms", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_QjKw7p small"><span style="flex:1">'.(__("View Forms", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_AgIzsm","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"color":"color-62","background":"color-11-to-color-12-se","boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_light::url}}","id":{{logo_light}},"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Dynamic Templates', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("Use the full power of WordPress", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_UZh7et","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=dynamic_template","title":"'.(__("View Templates", 'greyd_hub')).'","opensInNewTab":0}},"content":"'.(__("View Templates", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_UZh7et "><span style="flex:1">'.(__("View Templates", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-11-to-color-12-sw","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-11-to-color-12-sw-gradient-background has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:columns {"verticalAlignment":"middle","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-middle row_xxxl"><!-- wp:column {"className":"col-12 col-md-10 offset-sm-0 offset-md-1 wp-block-column ","responsive":{"width":{"md":"col-md-10","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-1"}}} -->
        <div class="wp-block-column col-12 col-md-10 offset-sm-0 offset-md-1"><!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:heading {"textAlign":"center","level":3,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
        <h3 class="has-text-align-center has-color-62-color has-text-color">'.__("“It’s not an experiment if you know it’s going to work.”", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":6,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
        <h6 class="has-text-align-center has-color-62-color has-text-color">'.__('Jeff Bezos', 'greyd_hub').'</h6>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
 
        <!-- wp:columns {"backgroundColor":"color-23","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-23-background-color has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:heading {"textAlign":"center","level":5} -->
        <h5 class="has-text-align-center">'.__("Get Started Now", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":4} -->
        <h4 class="has-text-align-center">'.__("Tutorials, Quick Learning Videos & FAQ can be found here:", 'greyd_hub').'</h4>
        <!-- /wp:heading -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"inline_css_id":"block-62785b2a-3fb3-4cdc-b8fb-ee7e63ee1531","greydClass":"gs_12yPWZ","trigger":{"type":"link","params":{"url":"'.__(self::$config->helpcenter, 'greyd_hub').'", "title":"'.(__("Open Helpcenter", 'greyd_hub')).'","opensInNewTab":1}},"content":"'.(__("Open Helpcenter", 'greyd_hub')).'","className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_12yPWZ "><span style="flex:1">'.(__("Open Helpcenter", "greyd_hub")).'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';


        $default_page_content = 
        '<!-- wp:columns {"backgroundColor":"color-33","className":"row_xxl ","row":{"type":"row_xxl"},"background":{"type":"image","opacity":100,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"image":{"id": {{img_light_wide}} ,"url":"{{img_light_wide::url}}","size":"contain","repeat":"no-repeat","position":"center center"},"overlay":{"type":"gradient","opacity":30,"color":"","gradient":"color-31-to-color-63-s"}}} -->
        <div class="wp-block-columns row_xxl has-color-33-background-color has-background"><!-- wp:column {"className":"col-12 wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":80,"responsive":{"height":{"sm":"20%","md":"20%","lg":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12, wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:heading {"textAlign":"center","level":4,"className":"has-text-align-center"} -->
        <h4 class="has-text-align-center">'.__("This Is Your New Page", 'greyd_hub').'</h4>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":1} -->
        <h1 class="has-text-align-center">'.__("With Greyd.Suite", 'greyd_hub').'</h1>
        <!-- /wp:heading -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_xurjKj","trigger":{"type":"scroll","params":"features"},"size":"big","content":"'._x("Show Me More", 'small', 'greyd_hub').'","className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_xurjKj big"><span style="flex:1">'._x("Show Me More", 'small', 'greyd_hub').'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"height":140,"responsive":{"height":{"sm":"60%"}}} -->
        <div style="height:140px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 wp-block-column ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 col-lg-7 wp-block-column","responsive":{"width":{"md":"col-md-8","lg":"col-lg-7","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8 col-lg-7"><!-- wp:heading {"level":5} -->
        <h5>'.__("Intuitive Design", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":2} -->
        <h2>'.__("Thanks to natively integrated Gutenberg Editor", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Greyd.Suite is the only all-in-one solution that is fully integrated in the new WordPress editor. We have enhanced the native functions and added powerful Greyd.Suite features. For the design you will find global settings for your entire website in the Customizer.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_Qo2uvO","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"'._x("Open Customizer", 'small', 'greyd_hub').'","opensInNewTab":1}},"content":"'._x("Open Customizer", 'small', 'greyd_hub').'","icon":{"content":"icon_tool","position":"after","size":"100%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_Qo2uvO "><span style="flex:1">'._x("Open Customizer", 'small', 'greyd_hub').'</span><span class="icon_tool" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1","responsive":{"width":{"xs":"col-10","md":"col-md-4","lg":"col-lg-5","sm":""},"offset":{"xs":"offset-1","sm":"offset-sm-3","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1"></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:greyd/dynamic {"template":"'.__("example-what-is-a-dynamic-template", 'greyd_hub').'","dynamic_content":[{"dkey":"icon","dtype":"file_picker","dtitle":"Bild","dvalue":""},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("headline", 'greyd_hub')).'","dvalue":"'.(__("Dynamic Templates Responsively Designed", 'greyd_hub')).'"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Text block 01", 'greyd_hub')).'","dvalue":"'.__("Try shrinking your browser window and see how this area changes. How does it work?", 'greyd_hub').'"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Text block 02", 'greyd_hub')).'","dvalue":"'.__("With Greyd.Suite’s Dynamic Templates! They allow you to create different layouts for each breakpoint, but you only need to enter your content once.", 'greyd_hub').'"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Button', 'greyd_hub')).'","dvalue":"'.__("More Features", 'greyd_hub').'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__("Anchor", 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22scroll%22%2C%22params%22%3A%22features%22%7D"}]} /--></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"lg":"100%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/anchor {"anchor":"features"} -->
        <div id="features" class="greyd-anchor-target"></div>
        <!-- /wp:greyd/anchor --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"verticalAlignment":"bottom","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-bottom row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 ","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_j51agd","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Popups', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("Automated Popups in Your Website's Design", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Create unique popups that will automatically adapt to your website’s design with the integrated popup builder. You will find lots of different triggers, animations, rules and modules to choose from.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_9oQDI5","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"'.(__("View Popups", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Popups", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_9oQDI5 small"><span style="flex:1">'.(__("View Popups", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 ","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_fJGb5f","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Hub', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("All Your Projects In One Backend", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("No matter how many pages your installation contains: In Greyd. Hub you can centrally manage all your websites, import and export content, designs or entire websites with just one click, and manage backups easily.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_rXaywR","trigger":{"type":"link","params":{"url":"/wp-admin/admin.php?page=greyd_hub","title":"'.(__("View Dashboard", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Dashboard", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_rXaywR small"><span style="flex:1">'.(__("View Dashboard", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"verticalAlignment":"top","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-top row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_HLljXL","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_dark::url}}","id": {{logo_dark}} ,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Greyd.Forms', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5,"inline_css":"padding-top: 0px !important;\npadding-right: 0px !important;\npadding-bottom: 0px !important;\npadding-left: 0px !important;\n"} -->
        <h5>'.__("Create Professional Forms", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Whether simple contact form with double opt-in, conversion-optimized lead form or complex multistep forms with CRM connection and mathematical calculations – with Greyd. Forms you don't need any additional plugins!", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_QjKw7p","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=tp_forms","title":"'.(__("View Forms", 'greyd_hub')).'","opensInNewTab":0}},"size":"small","content":"'.(__("View Forms", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
        <a role="trigger" class="button is-style-trd gs_QjKw7p small"><span style="flex:1">'.(__("View Forms", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_AgIzsm","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"color":"color-62","background":"color-11-to-color-12-se","boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"{{logo_light::url}}","id":{{logo_light}},"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
        <li><span class="list_icon"></span><span class="list_content"><p>'.__('Dynamic Templates', 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item --></ul>
        <!-- /wp:greyd/list -->
        <!-- wp:heading {"level":5} -->
        <h5>'.__("Use the full power of WordPress", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
        <div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_UZh7et","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=dynamic_template","title":"'.(__("View Templates", 'greyd_hub')).'","opensInNewTab":0}},"content":"'.(__("View Templates", 'greyd_hub')).'","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-sec"} -->
        <a role="trigger" class="button is-style-sec gs_UZh7et "><span style="flex:1">'.(__("View Templates", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-11-to-color-12-sw","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-11-to-color-12-sw-gradient-background has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:columns {"verticalAlignment":"middle","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-middle row_xxxl"><!-- wp:column {"className":"col-12 col-md-10 offset-sm-0 offset-md-1 wp-block-column ","responsive":{"width":{"md":"col-md-10","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-1"}}} -->
        <div class="wp-block-column col-12 col-md-10 offset-sm-0 offset-md-1"><!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:heading {"textAlign":"center","level":3,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
        <h3 class="has-text-align-center has-color-62-color has-text-color">'.__("“It’s not an experiment if you know it’s going to work.”", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":6,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
        <h6 class="has-text-align-center has-color-62-color has-text-color">'.__('Jeff Bezos', 'greyd_hub').'</h6>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-33-to-color-62-n","className":"row_xxl ","row":{"type":"row_xxl"},"background":{"overlay":{"type":"gradient","opacity":16,"color":"","gradient":"color-31-to-color-63-s"}}} -->
        <div class="wp-block-columns row_xxl has-color-33-to-color-62-n-gradient-background has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 col-lg-7 ","responsive":{"width":{"md":"col-md-8","lg":"col-lg-7","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8 col-lg-7"><!-- wp:heading {"level":5} -->
        <h5>'.__("More Than Just Posts", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":3} -->
        <h3>'.__("Custom Post Types", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Whether locations, employees or FAQ: The Greyd.Suite makes it as easy as it is for you to create custom post types and custom taxonomies. Simplify data maintenance in the back-end and design your content flexibly.", 'greyd_hub').'</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1","responsive":{"width":{"xs":"col-10","md":"col-md-4","lg":"col-lg-5","sm":""},"offset":{"xs":"offset-1","sm":"offset-sm-3","md":"offset-md-0","lg":"offset-lg-0"}}} -->
        <div class="wp-block-column col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1"></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 col-lg-8 ","responsive":{"width":{"md":"col-md-6","lg":"col-lg-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6 col-lg-8"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-lg-11 ","responsive":{"width":{"lg":"col-lg-11","sm":""}}} -->
        <div class="wp-block-column col-12 col-lg-11"><!-- wp:greyd/box {"greydClass":"gs_Rc6R8t","greydStyles":{"padding":{"top":"10px","bottom":"10px","right":"15px","left":"15px"},"color":"color-62","background":"color-13"}} -->
        <div class="wp-block-greyd-box"><!-- wp:heading {"level":6,"inline_css":"margin-top: 0px !important;\nmargin-bottom: 0px !important;\n"} -->
        <h6>'.__("Show your custom post types in post overviews with any layout:", 'greyd_hub').'</h6>
        <!-- /wp:heading --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-lg-5 ","responsive":{"width":{"lg":"col-lg-5","sm":""}}} -->
        <div class="wp-block-column col-12 col-lg-5"><!-- wp:query {"queryId":0,"query":{"inherit":false,"postType":"'.__("myposttype", 'greyd_hub').'","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":1}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":false}} -->
        <!-- wp:greyd/dynamic {"template":"'.__("example-display-post-type", 'greyd_hub').'","dynamic_content":[{"dkey":"title","dtype":"textarea","dtitle":"'.(__("short headline", 'greyd_hub')).'","dvalue":"_topic_"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("large headline", 'greyd_hub')).'","dvalue":"_question_"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Link Name', 'greyd_hub')).'","dvalue":"'.(__("View Video", 'greyd_hub')).'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__('Link Trigger', 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22dynamic%22%2C%22params%22%3A%7B%22tag%22%3A%22link%22%7D%7D"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Description", 'greyd_hub')).'","dvalue":"%3Cp%3E_description_%3C%2Fp%3E"},{"dkey":"vc_bg_video","dtype":"textfield","dtitle":"'.(__("Background video", 'greyd_hub')).'","dvalue":""},{"dkey":"vc_bg_image","dtype":"file_picker_image","dtitle":"'.(__("Background image", 'greyd_hub')).'","dvalue":"_picture_"}]} /-->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-lg-6 offset-lg-1 ","responsive":{"width":{"lg":"col-lg-6","sm":""},"offset":{"lg":"offset-lg-1"}}} -->
        <div class="wp-block-column col-12 col-lg-6 offset-lg-1"><!-- wp:spacer {"height":60,"responsive":{"height":{"sm":"100%","md":"100%","lg":"0%","xl":"0%"}}} -->
        <div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:query {"queryId":1,"query":{"inherit":false,"postType":"'.__("myposttype", 'greyd_hub').'","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":1}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":false}} -->
        <!-- wp:greyd/dynamic {"template":"'.__("example-dynamic-content", 'greyd_hub').'","dynamic_content":[{"dkey":"image","dtype":"textfield","dtitle":"'.(__("Image", 'greyd_hub')).'","dvalue":"%7B%220%22%3A%22_%22%2C%221%22%3A%22p%22%2C%222%22%3A%22i%22%2C%223%22%3A%22c%22%2C%224%22%3A%22t%22%2C%225%22%3A%22u%22%2C%226%22%3A%22r%22%2C%227%22%3A%22e%22%2C%228%22%3A%22_%22%2C%22type%22%3A%22dynamic%22%2C%22tag%22%3A%22picture%22%7D"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("short headline", 'greyd_hub')).'","dvalue":"_topic_"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("large headline", 'greyd_hub')).'","dvalue":"_question_"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Description", 'greyd_hub')).'","dvalue":"%3Cp%3E_description_%3C%2Fp%3E"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Link Name', 'greyd_hub')).'","dvalue":"'.(__("View Video", 'greyd_hub')).'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__('Link Trigger', 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22dynamic%22%2C%22params%22%3A%7B%22tag%22%3A%22link%22%7D%7D"},{"dkey":"vc_bg_image","dtype":"file_picker_image","dtitle":"'.(__("Background image", 'greyd_hub')).'","dvalue":"_picture_"}]} /-->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 col-lg-4 ","responsive":{"width":{"md":"col-md-6","lg":"col-lg-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6 col-lg-4"><!-- wp:spacer {"height":60,"responsive":{"height":{"sm":"100%","md":"100%","lg":"0%","xl":"0%"}}} -->
        <div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/box {"greydClass":"gs_sBa6w9","greydStyles":{"padding":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"margin":{"top":"0px","bottom":"0px","right":"0px","left":"0px"},"responsive":{"lg":{"margin":{"top":"0px","bottom":"0px"}},"sm":{"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"}}},"background":"color-62"}} -->
        <div class="wp-block-greyd-box"><!-- wp:query {"queryId":2,"query":{"inherit":false,"postType":"'.__("myposttype", 'greyd_hub').'","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"icon","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px","fontSize":"17px"}}} -->
        <!-- wp:greyd/dynamic {"template":"'.__("example-display-post-type", 'greyd_hub').'","dynamic_content":[{"dkey":"title","dtype":"textarea","dtitle":"'.(__("short headline", 'greyd_hub')).'","dvalue":"_topic_"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("large headline", 'greyd_hub')).'","dvalue":"_question_"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Link Name', 'greyd_hub')).'","dvalue":"'.(__("Learn More", 'greyd_hub')).'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__('Link Trigger', 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22dynamic%22%2C%22params%22%3A%7B%22tag%22%3A%22link%22%7D%7D"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Description", 'greyd_hub')).'","dvalue":"_content_"},{"dkey":"vc_bg_video","dtype":"textfield","dtitle":"'.(__("Background video", 'greyd_hub')).'","dvalue":""},{"dkey":"vc_bg_image","dtype":"file_picker_image","dtitle":"'.(__("Background image", 'greyd_hub')).'","dvalue":"_image_"}]} /-->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-33-to-color-23-s","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-33-to-color-23-s-gradient-background has-background"><!-- wp:column {"className":"col-12 col-md-8 ","responsive":{"width":{"md":"col-md-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/box {"greydClass":"gs_i8pdPJ","greydStyles":{"padding":{"top":"10px","bottom":"10px","right":"15px","left":"15px"},"color":"color-62","background":"color-13"}} -->
        <div class="wp-block-greyd-box"><!-- wp:heading {"level":6,"inline_css":"margin-top: 0px !important;\n margin-bottom: 0px !important;\n"} -->
        <h6>'.__("Or Use the WordPress Standard Posts for Different Use Cases:", 'greyd_hub').'</h6>
        <!-- /wp:heading --></div>
        <!-- /wp:greyd/box -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"backgroundColor":"color-23","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-23-background-color has-background"><!-- wp:column {"className":"col-12 col-md-4 ","responsive":{"width":{"md":"col-md-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-4"><!-- wp:query {"queryId":3,"query":{"inherit":false,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"text","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:spacer {"height":30,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%","xl":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:post-title {"level":4} /-->
        <!-- wp:post-excerpt /-->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_uWGTIB","trigger":{"type":"dynamic","params":{"tag":"post"}},"content":"'.(__("Learn More", 'greyd_hub')).'","icon":{"content":"arrow_right","margin":"4px","size":"140%"},"className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_uWGTIB "><span style="flex:1">'.(__("Learn More", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:140%;margin-left:4px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query -->
        <!-- wp:spacer {"height":15,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:query {"queryId":4,"query":{"inherit":false,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"text","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:greyd/box {"trigger":{"type":"dynamic","params":{"tag":"post"}},"greydClass":"gs_Xq27CZ"} -->
        <div class="wp-block-greyd-box"><!-- wp:post-date {"style":{"typography":{"fontSize":"13px"}}} /-->
        <!-- wp:post-title {"fontSize":"txt"} /-->
        <!-- wp:post-excerpt {"style":{"typography":{"fontSize":"13px"}}} /--></div>
        <!-- /wp:greyd/box -->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-4 ","responsive":{"width":{"md":"col-md-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-4"><!-- wp:query {"queryId":5,"query":{"inherit":false,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"text","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:greyd/box {"greydClass":"gs_hjjPAZ","greydStyles":{"padding":{"top":"0px","right":"15px","bottom":"0px","left":"15px"},"border":{"top":"2px none #3d3549","right":"2px none #3d3549","bottom":"2px none #3d3549","left":"2px solid #3d3549"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:spacer {"height":15,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%","xl":"100%"}}} -->
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:post-featured-image {"align":"left"} /-->
        <!-- wp:spacer {"height":15,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%","xl":"100%"}}} -->
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:post-date {"style":{"typography":{"fontSize":"13px"}}} /-->
        <!-- wp:post-title {"level":4} /-->
        <!-- wp:post-excerpt /-->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_dkClkt","trigger":{"type":"dynamic","params":{"tag":"post"}},"content":"'.(__("Learn More", 'greyd_hub')).'","icon":{"content":"arrow_right","margin":"4px","size":"140%"},"className":"is-style-link-prim"} -->
        <a role="trigger" class="link is-style-link-prim gs_dkClkt "><span style="flex:1">'.(__("Learn More", 'greyd_hub')).'</span><span class="arrow_right" style="vertical-align:middle;font-size:140%;margin-left:4px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        <!-- wp:spacer {"height":15,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%","xl":"100%"}}} -->
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:greyd/box -->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-4 ","responsive":{"width":{"md":"col-md-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-4"><!-- wp:spacer {"height":60,"responsive":{"height":{"sm":"100%","md":"100%","lg":"40%","xl":"0%"}}} -->
        <div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:query {"queryId":6,"query":{"inherit":false,"postType":"post","orderBy":"date","order":"desc","sticky":"","categoryIds":[],"tagIds":[],"author":"","search":"","exclude":[],"perPage":1,"offset":0,"pages":0}} -->
        <div class="wp-block-query"><!-- wp:post-template {"pagination":{"enable":true,"type":"text","greydStyles":{"padding":{"top":"30px","right":"5px","bottom":"0px","left":"5px"},"gutter":"10px"}}} -->
        <!-- wp:greyd/dynamic {"template":"'.__("example-dynamic-content", 'greyd_hub').'","dynamic_content":[{"dkey":"image","dtype":"textfield","dtitle":"'.(__("Image", 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22dynamic%22%2C%22tag%22%3A%22image%22%2C%22url%22%3A%22%22%7D"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("short headline", 'greyd_hub')).'","dvalue":"_date_"},{"dkey":"title","dtype":"textarea","dtitle":"'.(__("large headline", 'greyd_hub')).'","dvalue":"_title_"},{"dkey":"content","dtype":"textarea_html","dtitle":"'.(__("Description", 'greyd_hub')).'","dvalue":"%3Cp%3E_content%7C150_%3C%2Fp%3E"},{"dkey":"content","dtype":"textarea","dtitle":"'.(__('Link Name', 'greyd_hub')).'","dvalue":"'.(__("Learn More", 'greyd_hub')).'"},{"dkey":"trigger","dtype":"textfield","dtitle":"'.(__('Link Trigger', 'greyd_hub')).'","dvalue":"%7B%22type%22%3A%22dynamic%22%2C%22params%22%3A%7B%22tag%22%3A%22post%22%7D%7D"},{"dkey":"vc_bg_image","dtype":"file_picker_image","dtitle":"'.(__("Background image", 'greyd_hub')).'","dvalue":"_image_"}]} /-->
        <!-- /wp:post-template -->
        <!-- wp:spacer {"height":30} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:query --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%","md":"70%","lg":"80%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%","md":"70%","lg":"80%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:heading {"level":5} -->
        <h5>'.__("The Perfect Form", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":3} -->
        <h3>'.__("with Greyd.Forms", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>'.__("Greyd.Suite’s built-in form generator contains everything you need for professional forms:", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_check_alt","url":"","id":-1,"color":"color-13","size":"20px","margin":"10px","position":"left","align_y":"center","align_x":"start"}} -->
        <ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Native double opt-in", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        <!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Interfaces to CRMs, newsletter tools, Zapier & Co. ", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        <!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Image tiles, multistep &amp; other conversion boosters", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        <!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Conditional content for your campaigns", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        <!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Mathematical calculations", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        <!-- wp:greyd/list-item {"type":"icon","icon":"icon_check_alt"} -->
        <li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>'.__("Easy management of follow-up actions, emails & form entries", 'greyd_hub').'</p></span></li>
        <!-- /wp:greyd/list-item -->
        </ul>
        <!-- /wp:greyd/list -->
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/box {"greydClass":"gs_j0duqe","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"}}}, "boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}}} -->
        <div class="wp-block-greyd-box"><!-- wp:heading {"level":6} -->
        <h6>'.__("You Are Interested in Other Topics or Have Questions About Greyd.Suite?", 'greyd_hub').'</h6>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":15,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/form {"id":"{{default_contact_form}}"} /--></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"60%","md":"70%","lg":"80%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"backgroundColor":"color-23","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-23-background-color has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:heading {"textAlign":"center","level":5} -->
        <h5 class="has-text-align-center">'.__("Get Started Now", 'greyd_hub').'</h5>
        <!-- /wp:heading -->
        <!-- wp:heading {"textAlign":"center","level":4} -->
        <h4 class="has-text-align-center">'.__("Tutorials, Quick Learning Videos & FAQ can be found here:", 'greyd_hub').'</h4>
        <!-- /wp:heading -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"inline_css_id":"block-62785b2a-3fb3-4cdc-b8fb-ee7e63ee1531","greydClass":"gs_12yPWZ","trigger":{"type":"link","params":{"url":"'.__(self::$config->helpcenter, 'greyd_hub').'", "title":"'.(__("Open Helpcenter", 'greyd_hub')).'","opensInNewTab":1}},"content":"'.(__("Open Helpcenter", 'greyd_hub')).'","className":"is-style-prim"} -->
        <a role="trigger" class="button is-style-prim gs_12yPWZ "><span style="flex:1">'.(__("Open Helpcenter", "greyd_hub")).'</span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        $thanks_page_content = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 col-md-3 ","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-3"></div>
        <!-- /wp:column -->
        
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:spacer {"height":140} -->
        <div style="height:140px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:greyd/conditional-content {"operator":"AND","conditions":[{"type":"urlparam","operator":"empty","value":"","detail":"optin","custom":""},{"type":"urlparam","operator":"empty","value":"","detail":"optout","custom":""}]} -->
        <!-- wp:heading {"level":1,"className":"","fontSize":"h-1"} -->
        <h1 class="has-h-1-font-size">'.__("Something went wrong...", 'greyd_hub').'</h1>
        <!-- /wp:heading -->
        
        <!-- wp:heading {"className":"","fontSize":"h-3"} -->
        <h2 class="has-h-3-font-size">'.__("Sorry, we were unable to process your request because we could not confirm your identity.", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        <!-- /wp:greyd/conditional-content -->
        
        <!-- wp:greyd/conditional-content {"conditions":[{"type":"urlparam","operator":"not_empty","value":"","detail":"optin","custom":""}]} -->
        <!-- wp:heading {"level":1,"className":"","fontSize":"h-1"} -->
        <h1 class="has-h-1-font-size">'.__("Thank you!", 'greyd_hub').'</h1>
        <!-- /wp:heading -->
        
        <!-- wp:heading {"className":"","fontSize":"h-3"} -->
        <h2 class="has-h-3-font-size">'.__("Your request will be processed. We will get back to you shortly.", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        <!-- /wp:greyd/conditional-content -->
        
        <!-- wp:greyd/conditional-content {"conditions":[{"type":"urlparam","operator":"not_empty","value":"","detail":"optout","custom":""}]} -->
        <!-- wp:heading {"level":1,"className":"","fontSize":"h-1"} -->
        <h1 class="has-h-1-font-size">'.__("What a pity...", 'greyd_hub').'</h1>
        <!-- /wp:heading -->
        
        <!-- wp:heading {"className":"","fontSize":"h-3"} -->
        <h2 class="has-h-3-font-size">'.__("Your data has been deleted from our system.", 'greyd_hub').'</h2>
        <!-- /wp:heading -->
        <!-- /wp:greyd/conditional-content -->
        
        <!-- wp:spacer -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column -->
        
        <!-- wp:column {"className":"col-12 col-md-3 ","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-3"></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // done
        $default_form = 
        '<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:greyd-forms/iconpanels {"name":"preference","required": true,"greydClass":"gs_W2Lrgg","label":"'.__("I am interested in:", 'greyd_hub').'","labelStyles":{"fontSize":".8em"},"tooltip":{"visible":false,"content":""},"wrapperStyles":{"flexDirection":"","alignItems":"center"},"panelStyles":{"hover":{"backgroundColor":"var(--wp--preset--color--light)","color":"var(--wp--preset--color--foreground)"},"active":{"backgroundColor":"var(--wp--preset--color--tertiary)","color":"var(--wp--preset--color--lightest)"},"flexDirection":"","backgroundColor":"var(--wp--preset--color--background)","color":"var(--wp--preset--color--dark)","opacity":80,"fontSize":".6em","borderRadius":"4px 4px 4px 4px "},"imgStyles":{"height":"32px"}} -->
        <div class="input-outer-wrapper"><div class="input-wrapper gs_W2Lrgg "><div class="label_wrap"><label class="label"><span>'.__("I am interested in:", 'greyd_hub').'</span><span class="requirement-required"> *</span></label></div><fieldset required data-required="required"><div class="greyd_icon_panels img_pnl_wrapper"><!-- wp:greyd-forms/iconpanel {"title":"'.__('Customizer', 'greyd_hub').'","value":"customizer","name":"preference","required":true,"images":{"normal":{"id": {{logo_dark}}, "url": "{{logo_dark::url}}" },"hover":{"id":-1} ,"active":{"id": {{logo_light}}, "url": "{{logo_light::url}}" },"current":"normal"}} -->
        <input type="radio" id="customizer___idXXX" value="customizer" name="preference___idXXX" required data-required="required"/><label class="greyd_icon_panel img_pnl animate_fast option" for="customizer___idXXX"><span class="img_wrap"><img src="{{logo_light::url}}" class="icon_select animate_fast"/><img src="{{logo_dark::url}}" class="icon_hover icon_default animate_fast"/><img src="{{logo_dark::url}}" class="icon_ghost"/></span><span>'.__('Customizer', 'greyd_hub').'</span></label>
        <!-- /wp:greyd-forms/iconpanel -->
        
        <!-- wp:greyd-forms/iconpanel {"title":"'.__('Greyd.Hub', 'greyd_hub').'","value":"hub","name":"preference","required": true ,"images":{"normal":{"id": {{logo_dark}}, "url": "{{logo_dark::url}}" },"hover":{"id":-1} ,"active":{"id": {{logo_light}}, "url": "{{logo_light::url}}" },"current":"normal"}} -->
        <input type="radio" id="hub___idXXX" value="hub" name="preference___idXXX" required data-required="required"/><label class="greyd_icon_panel img_pnl animate_fast option" for="hub___idXXX"><span class="img_wrap"><img src="{{logo_light::url}}" class="icon_select animate_fast"/><img src="{{logo_dark::url}}" class="icon_hover icon_default animate_fast"/><img src="{{logo_dark::url}}" class="icon_ghost"/></span><span>'.__('Greyd.Hub', 'greyd_hub').'</span></label>
        <!-- /wp:greyd-forms/iconpanel -->
        
        <!-- wp:greyd-forms/iconpanel {"title":"'.__('Greyd.Forms', 'greyd_hub').'","value":"forms","name":"preference","required":true,"images":{"normal":{"id": {{logo_dark}}, "url": "{{logo_dark::url}}" },"hover":{"id":-1} ,"active":{"id": {{logo_light}}, "url": "{{logo_light::url}}" },"current":"normal"}} -->
        <input type="radio" id="forms___idXXX" value="forms" name="preference___idXXX" required data-required="required"/><label class="greyd_icon_panel img_pnl animate_fast option" for="forms___idXXX"><span class="img_wrap"><img src="{{logo_light::url}}" class="icon_select animate_fast"/><img src="{{logo_dark::url}}" class="icon_hover icon_default animate_fast"/><img src="{{logo_dark::url}}" class="icon_ghost"/></span><span>'.__('Greyd.Forms', 'greyd_hub').'</span></label>
        <!-- /wp:greyd-forms/iconpanel -->
        
        <!-- wp:greyd-forms/iconpanel {"title":"'.__('Templates', 'greyd_hub').'","value":"templates","name":"preference","required":true,"images":{"normal":{"id": {{logo_dark}}, "url": "{{logo_dark::url}}" },"hover":{"id":-1} ,"active":{"id": {{logo_light}}, "url": "{{logo_light::url}}" },"current":"normal"}} -->
        <input type="radio" id="templates___idXXX" value="templates" name="preference___idXXX" required data-required="required"/><label class="greyd_icon_panel img_pnl animate_fast option" for="templates___idXXX"><span class="img_wrap"><img src="{{logo_light::url}}" class="icon_select animate_fast"/><img src="{{logo_dark::url}}" class="icon_hover icon_default animate_fast"/><img src="{{logo_dark::url}}" class="icon_ghost"/></span><span>'.__('Templates', 'greyd_hub').'</span></label>
        <!-- /wp:greyd-forms/iconpanel --></div></fieldset></div></div><style class="greyd-styles">.gs_W2Lrgg .img_pnl_wrapper { align-items: center !important; } .gs_W2Lrgg .label { font-size: .8em !important; } </style><style class="greyd-styles">.gs_W2Lrgg .img_pnl { background-color: var(--wp--preset--color--background) !important; color: var(--wp--preset--color--dark) !important; font-size: .6em !important; border-radius: 4px 4px 4px 4px !important; } .gs_W2Lrgg .img_pnl:hover { background-color: var(--wp--preset--color--light) !important; color: var(--wp--preset--color--foreground) !important; } .gs_W2Lrgg .img_pnl img { height: 32px !important; } </style><style class="greyd-styles">.gs_W2Lrgg .img_pnl.selected, .gs_W2Lrgg input:checked + .img_pnl, .gs_W2Lrgg input:checked + span + .img_pnl { background-color: var(--wp--preset--color--tertiary) !important; color: var(--wp--preset--color--lightest) !important; } </style>
        <!-- /wp:greyd-forms/iconpanels --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        
        <!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:spacer {"height":30,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        
        <!-- wp:group {"className":""} -->
        <div class="wp-block-group">

        <!-- wp:greyd-forms/condition {"conditions":[{"field":"preference","condition":"empty","value":""}],"hideOnload":false} -->
        <div class="wp-block-greyd-forms-condition condition_wrapper open slide" data-after="reset"><div data-condition="%preference%%empty%%%" class="condition_block show"><!-- wp:paragraph {"inline_css":"margin-top: -10px !important;\n"} -->
        <p>'.__("Choose which topic you're interested in by clicking on one of the image tiles.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        
        <!-- wp:spacer {"height":30,"responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div></div>
        <!-- /wp:greyd-forms/condition -->
        
        <!-- wp:greyd-forms/condition {"operator":"AND","conditions":[{"condition":"not_empty","field":"preference","value":""}]} -->
        <div class="wp-block-greyd-forms-condition condition_wrapper open slide" data-after="reset"><div data-condition="%preference%%not_empty%%%" class="condition_block hide"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:greyd-forms/input {"name":"name","required":true,"placeholder":"'.__("John Doe", 'greyd_hub').'","autocomplete":"name","restrictions":{"maxlength":50},"label":"'.__("My name", 'greyd_hub').'","tooltip":{"visible":false,"content":""},"greydClass":"gs_iAZNa8","className":"is-style-prim"} -->
        <div class="input-outer-wrapper"><div class="input-wrapper gs_iAZNa8"><div class="label_wrap"><label class="label" for="name___idXXX"><span>'.__("My name", 'greyd_hub').'</span><span class="requirement-required"> *</span></label></div><div class="input-inner-wrapper"><input class="input validation " type="text" name="name" id="name___idXXX" placeholder="'.__("John Doe", 'greyd_hub').'" autocomplete="name" max="50" required data-required="required" title="'.__("Please fill out this field", 'greyd_hub').'" rows=""/><span class="input-field-icon"></span></div></div></div>
        <!-- /wp:greyd-forms/input --></div>
        <!-- /wp:column -->
        
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:greyd-forms/input {"name":"email","type":"email","required":true,"placeholder":"'.__("jd@example.com", 'greyd_hub').'","autocomplete":"email","restrictions":{"maxlength":50},"label":"'.__("Email (business)", 'greyd_hub').'","tooltip":{"visible":false,"content":""},"greydClass":"gs_4EMSIZ","className":"is-style-prim"} -->
        <div class="input-outer-wrapper"><div class="input-wrapper gs_4EMSIZ"><div class="label_wrap"><label class="label" for="email___idXXX"><span>'.__("Email (business)", 'greyd_hub').'</span><span class="requirement-required"> *</span></label><span class="forms-tooltip"><span class="forms-tooltip-toggle"></span><span class="forms-tooltip-popup" id="tt-email___idXXX">{tooltip}</span></span></div><div class="input-inner-wrapper"><input class="input validation " type="email" name="email" id="email___idXXX" aria-describedby="tt-email___idXXX" placeholder="'.__("jd@example.com", 'greyd_hub').'" autocomplete="email" max="50" pattern="\\\w+([-+.\']\\\w+)*@\\\w+([-.]\\\w+)*\\\.\\\w+([-.]\\\w+)*" required data-required="required" title="{required}" rows=""/><span class="input-field-icon"></span></div></div></div>
        <!-- /wp:greyd-forms/input --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        
        <!-- wp:greyd-forms/input {"name":"company","restrictions":{"maxlength":50},"label":"'.__("I work at:", 'greyd_hub').'","tooltip":{"visible":false,"content":""},"greydClass":"gs_aXKTVg","className":"is-style-prim"} -->
        <div class="input-outer-wrapper"><div class="input-wrapper gs_aXKTVg"><div class="label_wrap"><label class="label" for="company___idXXX"><span>'.__("I work at:", 'greyd_hub').'</span><span class="requirement-optional"> (optional)</span></label></div><div class="input-inner-wrapper"><input class="input validation " type="text" name="company" id="company___idXXX" placeholder="'.__("enter here", 'greyd_hub').'" autocomplete="on" max="50" data-required="optional" title="'.__("Please fill out this field", 'greyd_hub').'" rows=""/><span class="input-field-icon"></span></div></div></div>
        <!-- /wp:greyd-forms/input -->
        
        <!-- wp:greyd-forms/input {"name":"message","autocomplete":"off","restrictions":{"maxlength":256},"area":true,"rows":3,"label":"'.__("My message", 'greyd_hub').'","tooltip":{"visible":false,"content":""},"greydClass":"gs_wgEYb6","className":"is-style-prim"} -->
        <div class="input-outer-wrapper"><div class="input-wrapper gs_wgEYb6"><div class="label_wrap"><label class="label" for="message___idXXX"><span>'.__("My message", 'greyd_hub').'</span><span class="requirement-optional"> (optional)</span></label></div><div class="input-inner-wrapper"><textarea class="input validation " type="text" name="message" id="message___idXXX" placeholder="'.__("enter here", 'greyd_hub').'" autocomplete="off" max="256" data-required="optional" title="'.__("Please fill out this field", 'greyd_hub').'" rows="3"></textarea><span class="input-field-icon"></span></div></div></div>
        <!-- /wp:greyd-forms/input -->
        
        <!-- wp:greyd-forms/checkbox {"name":"'.__("Privacy policy", 'greyd_hub').'","content":"'.__("You agree that your data will be used to process your request. Further information on the revocation can be found in the privacy policy.", 'greyd_hub').'","required":true,"greydClass":"gs_fcq80f","greydStyles":{"fontSize":".8em","lineHeight":"120%"},"className":"is-style-checkbox"} -->
        <div class="wp-block-greyd-forms-checkbox input-wrapper gs_fcq80f is-style-checkbox"><label class="label checkbox-label" for="'.__("Privacy policy", 'greyd_hub').'___idXXX"><input class="checkbox_placeholder_value" type="hidden" name="'.__("Privacy policy", 'greyd_hub').'" value="false"/><input type="checkbox" id="'.__("Privacy policy", 'greyd_hub').'___idXXX" name="'.__("Privacy policy", 'greyd_hub').'" value="true" required data-required="required" title="'.__("please confirm the checkbox", 'greyd_hub').'"/><div><span>'.__("You agree that your data will be used to process your request. Further information on the revocation can be found in the privacy policy.", 'greyd_hub').'</span><span class="requirement-required"> *</span></div></label><style class="greyd-styles">.gs_fcq80f { font-size: .8em !important; line-height: 120% !important; } </style></div>
        <!-- /wp:greyd-forms/checkbox -->
        
        <!-- wp:greyd-forms/submitbutton {"greydClass":"gs_dlYQ9w","className":"is-style-prim"} -->
        <div class="input-outer-wrapper input-wrapper"><button class="submitbutton button gs_dlYQ9w " type="submit" name="submit" title="'.__("submit", 'greyd_hub').'" data-text=""><span class="greyd_upload_progress"><span class="spinner"><span class="dot-loader"></span><span class="dot-loader dot-loader--2"></span><span class="dot-loader dot-loader--3"></span></span><span class="bar"></span></span><span style="flex:1">'.__("submit", 'greyd_hub').'</span></button></div>
        <!-- /wp:greyd-forms/submitbutton --></div></div>
        <!-- /wp:greyd-forms/condition --></div>
        <!-- /wp:group --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';

        $default_post_content = '
            <!-- wp:paragraph -->
            <p>'.__("This is your first post in Greyd.Suite.", 'greyd_hub').'</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph -->
            <p>'.__("With posts you can display for example company news or blog posts. If you want to show posts on a page, use the Greyd.Suite widget „Post Overview\". You can define the basic layout of your posts via the „single“ template.", 'greyd_hub').'</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph -->
            <p>'.__("Click here for Greyd's posts:", 'greyd_hub').' <a href="'.__(self::$config->homepage, 'greyd_hub').'">'.__("Visit the news page", 'greyd_hub').'</a>.</p>
            <!-- /wp:paragraph -->
        ';

        
        // dynamic templates
        $dt_1_content = 
        '<!-- wp:columns {"gradient":"color-21-to-color-31-ne","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-21-to-color-31-ne-gradient-background has-background"><!-- wp:column {"className":"hidden-xs hidden-sm hidden-md col-12 ","responsive":{"disable":{"xs":true,"sm":true,"md":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-sm hidden-md col-12"><!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/box {"greydClass":"gs_rvO2FQ","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
        <div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"middle","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-middle row_xxxl"><!-- wp:column {"className":"col-12 col-md-4 ","responsive":{"width":{"md":"col-md-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-4"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":"{{img_light::url}}","id":"{{img_light}}"},"greydClass":"gs_vmroXc","greydStyles":{"width":"80%"},"className":"dyn "} /--></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-8 ","responsive":{"width":{"md":"col-md-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8"><!-- wp:heading {"level":3,"textColor":"color-62","className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("headline", 'greyd_hub').'","enable":true}]} -->
        <h3 class="dyn has-color-62-color has-text-color" id="uberschrift-greyd-hub">'.__("headline", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 01", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 02", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.__('Button', 'greyd_hub').'","enable":true},{"key":"trigger","title":"'.__("Anchor", 'greyd_hub').'","enable":true}],"greydClass":"gs_jITF9v","trigger":{"type":"scroll","params":""},"content":"'.__("More Features", 'greyd_hub').'","icon":{"content":"arrow_down","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-prim"} -->
        <a role="trigger" class="button dyn is-style-prim gs_jITF9v "><span style="flex:1">'.__("More Features", 'greyd_hub').'</span><span class="arrow_down" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:greyd/box -->
        <!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-11-to-color-12-ne","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-11-to-color-12-ne-gradient-background has-background"><!-- wp:column {"className":"hidden-xs hidden-sm hidden-lg col-12 ","responsive":{"disable":{"xs":true,"sm":true,"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-sm hidden-lg col-12"><!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:heading {"level":3,"textColor":"color-62","className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("headline", 'greyd_hub').'","enable":true}]} -->
        <h3 class="dyn has-color-62-color has-text-color" id="uberschrift-greyd-hub">'.__("headline", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:greyd/box {"greydClass":"gs_kPwRuk","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
        <div class="wp-block-greyd-box"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 01", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 02", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.__('Button', 'greyd_hub').'","enable":true},{"key":"trigger","title":"'.__("Anchor", 'greyd_hub').'","enable":true}],"greydClass":"gs_1ee4YM","trigger":{"type":"scroll","params":""},"content":"'.__("More Features", 'greyd_hub').'","icon":{"content":"arrow_down","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-sec"} -->
        <a role="trigger" class="button dyn is-style-sec gs_1ee4YM "><span style="flex:1">'.__("More Features", 'greyd_hub').'</span><span class="arrow_down" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":"{{img_light::url}}","id":"{{img_light}}"},"greydClass":"gs_B58U85","align":"center","className":"dyn "} /--></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:greyd/box -->
        <!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"backgroundColor":"color-12","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-12-background-color has-background"><!-- wp:column {"className":"hidden-xs hidden-md hidden-lg col-12 ","responsive":{"disable":{"xs":true,"md":true,"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-md hidden-lg col-12"><!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:heading {"textAlign":"center","level":3,"textColor":"color-62","className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("headline", 'greyd_hub').'","enable":true}]} -->
        <h3 class="has-text-align-center dyn has-color-62-color has-text-color" id="uberschrift-greyd-hub">'.__("headline", 'greyd_hub').'</h3>
        <!-- /wp:heading --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":"{{img_light::url}}","id":"{{img_light}}"},"greydClass":"gs_c285Sy","greydStyles":{"width":"360px"},"align":"center","className":"dyn "} /-->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/box {"greydClass":"gs_NK0FwH","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
        <div class="wp-block-greyd-box"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 01", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-6"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 02", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:greyd/box -->
        <!-- wp:spacer {"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.__('Button', 'greyd_hub').'","enable":true},{"key":"trigger","title":"'.__("Anchor", 'greyd_hub').'","enable":true}],"greydClass":"gs_N8Xd3S","trigger":{"type":"scroll","params":""},"content":"'.__("More Features", 'greyd_hub').'","icon":{"content":"arrow_down","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-sec"} -->
        <a role="trigger" class="button dyn is-style-sec gs_N8Xd3S "><span style="flex:1">'.__("More Features", 'greyd_hub').'</span><span class="arrow_down" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        <!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"gradient":"color-21-to-color-31-ne","className":"row_xxl ","row":{"type":"row_xxl"}} -->
        <div class="wp-block-columns row_xxl has-color-21-to-color-31-ne-gradient-background has-background"><!-- wp:column {"className":"hidden-sm hidden-md hidden-lg col-12 ","responsive":{"disable":{"sm":true,"md":true,"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-sm hidden-md hidden-lg col-12"><!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":"{{img_light::url}}","id":"{{img_light}}"},"greydClass":"gs_kIJvzV","greydStyles":{"width":"70%"},"align":"center","className":"dyn "} /-->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:heading {"textAlign":"center","level":3,"textColor":"color-62","className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("headline", 'greyd_hub').'","enable":true}]} -->
        <h3 class="has-text-align-center dyn has-color-62-color has-text-color" id="uberschrift-greyd-hub">'.__("headline", 'greyd_hub').'</h3>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:greyd/box {"greydClass":"gs_NjkuQM","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
        <div class="wp-block-greyd-box"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 01", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Text block 02", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn">'.__("I am a block of text. Click on the Edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub').'</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:greyd/box -->
        <!-- wp:spacer {"className":"","responsive":{"height":{"sm":"75%","md":"80%","lg":"90%"}}} -->
        <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons {"align":"center"} -->
        <div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.__('Button', 'greyd_hub').'","enable":true},{"key":"trigger","title":"'.__("Anchor", 'greyd_hub').'","enable":true}],"greydClass":"gs_nij20w","trigger":{"type":"scroll","params":""},"content":"'.__("More Features", 'greyd_hub').'","icon":{"content":"arrow_down","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-prim"} -->
        <a role="trigger" class="button dyn is-style-prim gs_nij20w "><span style="flex:1">'.__("More Features", 'greyd_hub').'</span><span class="arrow_down" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons -->
        <!-- wp:spacer {"height":80,"className":"","responsive":{"height":{"sm":"50%"}}} -->
        <div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';

        $dt_2_content = 
        '<!-- wp:columns {"backgroundColor":"","className":"row_xxxl dyn ","dynamic_fields":[{"key":"background/image/id","title":"'.__("Background image", 'greyd_hub').'","enable":true}],"row":{"type":"row_xxxl"},"background":{"type":"image","opacity":10,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"image":{"id":-1,"url":"","size":"cover","repeat":"no-repeat","position":"center center"}}} -->
        <div class="wp-block-columns row_xxxl dyn"><!-- wp:column {"className":"hidden-xs hidden-sm hidden-md col-12 ","inline_css":"margin-top: -20px !important;\n","responsive":{"disable":{"xs":true,"sm":true,"md":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-sm hidden-md col-12"><!-- wp:greyd/box {"greydClass":"gs_Jh2yi1","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"15px","left":"15px"}},"className":""} -->
        <div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"middle","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-middle row_xxxl"><!-- wp:column {"className":"col-12 col-md-4 ","responsive":{"width":{"md":"col-md-4","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-4"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":""},"greydClass":"gs_qAHdz3","className":"dyn "} /--></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-8 ","responsive":{"width":{"md":"col-md-8","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-8"><!-- wp:spacer {"height":25,"className":""} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("short headline", 'greyd_hub')).'","enable":true}]} -->
        <p class="dyn"></p>
        <!-- /wp:paragraph -->
        <!-- wp:heading {"level":5,"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("large headline", 'greyd_hub')).'","enable":true}]} -->
        <h5 class="dyn"></h5>
        <!-- /wp:heading --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.__("Description", 'greyd_hub').'","enable":true}]} -->
        <p class="dyn"></p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":25,"className":""} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.(__('Link Name', 'greyd_hub')).'","enable":true},{"key":"trigger","title":"'.(__('Link Trigger', 'greyd_hub')).'","enable":true}],"greydClass":"gs_2Wzes8","size":"small","className":"is-small dyn is-style-sec"} -->
        <a role="trigger" class="button is-small dyn is-style-sec gs_2Wzes8 small"><span style="flex:1"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:greyd/box --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"hidden-xs hidden-lg col-12 ","responsive":{"disable":{"xs":true,"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-lg col-12"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":""},"greydClass":"gs_CBZXSU","greydStyles":{"width":"50%"},"className":"dyn "} /-->
        <!-- wp:spacer {"height":25,"className":"","responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("short headline", 'greyd_hub')).'","enable":true}]} -->
        <p class="dyn"></p>
        <!-- /wp:paragraph -->
        <!-- wp:heading {"level":5,"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("large headline", 'greyd_hub')).'","enable":true}]} -->
        <h5 class="dyn"></h5>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":25,"className":""} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.(__('Link Name', 'greyd_hub')).'","enable":true},{"key":"trigger","title":"'.(__('Link Trigger', 'greyd_hub')).'","enable":true}],"greydClass":"gs_ijTxwl","size":"small","className":"is-small dyn is-style-sec"} -->
        <a role="trigger" class="button is-small dyn is-style-sec gs_ijTxwl small"><span style="flex:1"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"hidden-sm hidden-md hidden-lg col-12 ","responsive":{"disable":{"sm":true,"md":true,"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-sm hidden-md hidden-lg col-12"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"'.__("Image", 'greyd_hub').'","enable":true}],"image":{"type":"file","tag":"","url":""},"greydClass":"gs_Xm6Wst","className":"dyn "} /-->
        <!-- wp:spacer {"height":25,"className":"","responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:heading {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("short headline", 'greyd_hub')).'","enable":true}]} -->
        <h2 class="dyn"></h2>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":5,"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("large headline", 'greyd_hub')).'","enable":true}]} -->
        <h5 class="dyn"></h5>
        <!-- /wp:heading -->
        <!-- wp:spacer {"height":25,"className":""} -->
        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.(__('Link Name', 'greyd_hub')).'","enable":true},{"key":"trigger","title":"'.(__('Link Trigger', 'greyd_hub')).'","enable":true}],"greydClass":"gs_ECm8G8","size":"small","className":"is-small dyn is-style-sec"} -->
        <a role="trigger" class="button is-small dyn is-style-sec gs_ECm8G8 small"><span style="flex:1"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';

        $dt_3_content = 
        '<!-- wp:columns {"backgroundColor":"","className":"row_xxxl dyn ","dynamic_fields":[{"key":"background/video/url","title":"'.(__("Background video", 'greyd_hub')).'","enable":true}],"row":{"type":"row_xxxl"},"background":{"type":"video","opacity":10,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"video":{"url":"","aspect":"16/9"}}} -->
        <div class="wp-block-columns row_xxxl dyn"><!-- wp:column {"className":"hidden-xs hidden-sm hidden-md col-12 ","responsive":{"disable":{"xs":true,"sm":true,"md":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-xs hidden-sm hidden-md col-12"><!-- wp:columns {"verticalAlignment":"top","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns are-vertically-aligned-top row_xxxl"><!-- wp:column {"className":"col-12 col-md-7 ","responsive":{"width":{"md":"col-md-7","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-7"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("short headline", 'greyd_hub')).'","enable":true}]} -->
        <p class="dyn"></p>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":5,"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("large headline", 'greyd_hub')).'","enable":true}],"inline_css":"margin-right: -30% !important;\n"} -->
        <h5 class="dyn"></h5>
        <!-- /wp:heading --></div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"col-12 col-md-5 ","responsive":{"width":{"md":"col-md-5","sm":""}}} -->
        <div class="wp-block-column col-12 col-md-5"><!-- wp:greyd/buttons {"align":"right"} -->
        <div class="wp-block-greyd-buttons alignright"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.(__('Link Name', 'greyd_hub')).'","enable":true},{"key":"trigger","title":"'.(__('Link Trigger', 'greyd_hub')).'","enable":true}],"greydClass":"gs_jFkXC3","size":"small","className":"is-small dyn is-style-trd"} -->
        <a role="trigger" class="button is-small dyn is-style-trd gs_jFkXC3 small"><span style="flex:1"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
        <div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
        <div class="wp-block-column col-12"><!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("Description", 'greyd_hub')).'","enable":true}]} -->
        <p class="dyn">'.__('I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'greyd_hub').'</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->
        <!-- wp:columns {"backgroundColor":"","className":"row_xxxl dyn ","dynamic_fields":[{"key":"background/image/id","title":"'.(__("Background image", 'greyd_hub')).'","enable":true}],"row":{"type":"row_xxxl"},"background":{"type":"image","opacity":10,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"image":{"id":-1,"url":"","size":"cover","repeat":"no-repeat","position":"center center"}}} -->
        <div class="wp-block-columns row_xxxl dyn" ><!-- wp:column {"className":"hidden-lg col-12 ","responsive":{"disable":{"lg":true},"width":{"sm":""}}} -->
        <div class="wp-block-column hidden-lg col-12"><!-- wp:heading {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("short headline", 'greyd_hub')).'","enable":true}]} -->
        <h2 class="dyn"></h2>
        <!-- /wp:heading -->
        <!-- wp:heading {"level":5,"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("large headline", 'greyd_hub')).'","enable":true}]} -->
        <h5 class="dyn"></h5>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"'.(__("Description", 'greyd_hub')).'","enable":true}]} -->
        <p class="dyn">'.__('I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'greyd_hub').'</p>
        <!-- /wp:paragraph -->
        <!-- wp:spacer {"height":30,"className":"","responsive":{"height":{"sm":"100%","md":"100%","lg":"100%"}}} -->
        <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
        <!-- /wp:spacer -->
        <!-- wp:greyd/buttons -->
        <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"'.(__('Link Name', 'greyd_hub')).'","enable":true},{"key":"trigger","title":"'.(__('Link Trigger', 'greyd_hub')).'","enable":true}],"greydClass":"gs_9SiKtZ","size":"small","className":"is-small dyn is-style-trd"} -->
        <a role="trigger" class="button is-small dyn is-style-trd gs_9SiKtZ small"><span style="flex:1"></span></a>
        <!-- /wp:greyd/button --></div>
        <!-- /wp:greyd/buttons --></div>
        <!-- /wp:column --></div>
        <!-- /wp:columns -->';
        
        // media files
        $attachments = array(
            // Logo
            'logo_dark' => array(
                'slug'  => 'greyd-LogoPh-dark',
                'id'    => null,
                'url'   => null
            ),
            'logo_mid' => array(
                'slug'  => 'greyd-LogoPh-mid',
                'id'    => null,
                'url'   => null
            ),
            'logo_light' => array(
                'slug'  => 'greyd-LogoPh-light',
                'id'    => null,
                'url'   => null
            ),
            // Portrait
            'img_dark' => array(
                'slug'  => 'greyd-ImgPh-dark',
                'id'    => null,
                'url'   => null
            ),
            'img_mid' => array(
                'slug'  => 'greyd-ImgPh-mid',
                'id'    => null,
                'url'   => null
            ),
            'img_light' => array(
                'slug'  => 'greyd-ImgPh-light',
                'id'    => null,
                'url'   => null
            ),
            // Landscape
            'img_dark_wide' => array(
                'slug'  => 'greyd-ImgPh-dark-wide',
                'id'    => null,
                'url'   => null
            ),
            'img_mid_wide' => array(
                'slug'  => 'greyd-ImgPh-mid-wide',
                'id'    => null,
                'url'   => null
            ),
            'img_light_wide' => array(
                'slug'  => 'greyd-ImgPh-light-wide',
                'id'    => null,
                'url'   => null
            ),
        );

        // popup defaults
        function popup_default_content_blocks ( $atts=[] ) {
            return '<!-- wp:columns {"className":"","row":{"type":"row_xxxl"}} -->
            <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
            <div class="wp-block-column col-12"><!-- wp:greyd/popup-close {"greydClass":"gs_pDGEnF","greydStyles":{"width":"40px","fontSize":"4px","hover":{}}} -->
            <div class="wp-block-greyd-popup-close alignright right"><div class="popup_close_button gs_pDGEnF" onclick="popups.close(this)"><div class="close_icon"><span></span><span></span></div></div></div>
            <!-- /wp:greyd/popup-close -->
            
            <!-- wp:heading -->
            <h2></h2>
            <!-- /wp:heading -->
            
            <!-- wp:paragraph -->
            <p>'.( isset($atts['text']) ? $atts['text'] : __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit.", 'greyd_hub') ).'</p>
            <!-- /wp:paragraph -->
            
            <!-- wp:spacer -->
            <div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
            <!-- /wp:spacer -->
            
            <!-- wp:greyd/buttons -->
            <div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-9f5f3386-a733-46a5-82c8-81a9a824e37c","greydClass":"gs_k1MvqX","trigger":{"type":"popup_close"},"content":"'.( isset($atts['button_text']) ? $atts['button_text'] : __("close", 'greyd_hub') ).'","className":"is-style-prim"} -->
            <a role="trigger" class="button is-style-prim gs_k1MvqX "><span style="flex:1">'.( isset($atts['button_text']) ? $atts['button_text'] : __("close", 'greyd_hub') ).'</span></a>
            <!-- /wp:greyd/button --></div>
            <!-- /wp:greyd/buttons --></div>
            <!-- /wp:column --></div>
            <!-- /wp:columns -->';
        }
        $popup_design_default = array(
        );
        $popup_settings_default = array(
        );


        // define all default contents
        self::$default_contents["blocks"] = array(
            'starter' => array(
                'frontpage' => array(
                    'type'         => 'page',
                    'slug'         => __("example-page", 'greyd_hub'),
                    'title'        => __("Example page", 'greyd_hub'),
                    'content'      => $starter_page_content,
                )
            ),
            'pages' => array(
                'homepage' => array(
                    'type'         => 'page',
                    'slug'         => __("example-page", 'greyd_hub'),
                    'title'        => __("Example page", 'greyd_hub'),
                    'content'      => $default_page_content,
                    'force'        => true,
                    'meta'         => array(
                        '_announce_active' => 'on',
                        '_announce_content' => __("Welcome to Greyd.Suite -", 'greyd_hub'),
                        '_announce_read_more_text' => _x("view website", 'small', 'greyd_hub'),
                        '_announce_read_more_link' => __(self::$config->homepage, 'greyd_hub'),
                        '_announce_image' => '{{logo_light::url}}'
                    )
                ),
                'thanks' => array(
                    'type'         => 'page',
                    'slug'         => __('thank-you-page', 'greyd_hub'),
                    'title'        => __("Thank you page", 'greyd_hub'),
                    'content'      => $thanks_page_content
                )
            ),
            'posts' => array(
                'hallo-greyd' => array(
                    'type'         => 'post',
                    'slug'         => __("hello-greyd", 'greyd_hub'),
                    'title'        => __("Hello Greyd.", 'greyd_hub'),
                    'content'      => $default_post_content,
                    'img'          => '{{img_dark}}'
                )
            ),
            // 'menus' => array(
            //     'main-menu' => array(
            //         'name'         => __('Greyd Standard Menü', 'greyd_hub'),
            //         'location'     => 'main-menu',
            //         'items'         => [
            //             [
            //                 'title'     => __("Example page", 'greyd_hub'),
            //                 'type'      => 'page',
            //                 'url'       => ''
            //             ],
            //             [
            //                 'title'     => __('Tutorials', 'greyd_hub'),
            //                 'type'      => 'link',
            //                 'url'       => ''
            //             ]
            //         ]
            //     )
            // ),
            'media' => $attachments,
            'forms' => array(
                'default_contact_form' => array(
                    'type'         => 'tp_forms',
                    'slug'         => __('greyd-basic-contactform', 'greyd_hub'),
                    'title'        => __("Example form", 'greyd_hub'),
                    'content'      => $default_form,
                    'meta'         => array(
                        'ignore_gutter'     => 'ignore_gutter',
                        'success_message'   => __("Your request was successful - but this is an automatically generated test form. The content belongs to the owner of the site and cannot be forwarded to Greyd.", 'greyd_hub'),
                        'mail_to'           => get_option('admin_email'),
                    )
                )
            ),
            'posttypes' => array(
                'my_post_type' => array(
                    'type'         => 'tp_posttypes',
                    'slug'         => __("myposttype", 'greyd_hub'),
                    'title'        => __("My post type", 'greyd_hub'),
                    'content'      => '',
                    'meta'        => array(
                        'posttype_settings' => array(
                            'title' => __("My post type", 'greyd_hub'),
                            'slug' => __("myposttype", 'greyd_hub'),
                            'singular' => __("My post type", 'greyd_hub'),
                            'plural' => __("My post types", 'greyd_hub'),
                            'icon' => 'lightbulb',
                            'position' => '6',
                            'supports' => [
                                'editor' => 'editor'
                            ],
                            'arguments' => [
                                'search' => 'search',
                                'archive' => 'archive'
                            ],
                            'fields' => [
                                [
                                    'label' => __("Question", 'greyd_hub'),
                                    'name' => 'question',
                                    'type' => 'text',
                                    'required' => 'required',
                                ],
                                [
                                    'label' => __("Description", 'greyd_hub'),
                                    'name' => 'description',
                                    'type' => 'textarea',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("link", 'greyd_hub'),
                                    'name' => 'link',
                                    'type' => 'url',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("Image", 'greyd_hub'),
                                    'name' => 'picture',
                                    'type' => 'file',
                                    'required' => 'required'
                                ],
                                [
                                    'label' => __("Topic", 'greyd_hub'),
                                    'name' => 'topic',
                                    'type' => 'text',
                                    'required' => 'required',
                                ]
                            ]
                        )
                    )
                )
            ),
            'custom_posts' => array(
                'mypost_contactform' => array(
                    'type'         => __("myposttype", 'greyd_hub'),
                    'slug'         => __('contactform', 'greyd_hub'),
                    'title'        => __("Contact form", 'greyd_hub'),
                    'content'      =>   '<!-- wp:columns {"className":"","row":{"type":"row_xxxl"}} -->
                                        <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                        <div class="wp-block-column col-12"><!-- wp:embed {"url":"https://vimeo.com/402085198","type":"video","providerNameSlug":"vimeo","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
                                        <figure class="wp-block-embed is-type-video is-provider-vimeo wp-block-embed-vimeo wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
                                        https://vimeo.com/402085198
                                        </div></figure>
                                        <!-- /wp:embed --></div>
                                        <!-- /wp:column --></div>
                                        <!-- /wp:columns -->',
                    'meta' => array(
                        'dynamic_meta' => [
                            'topic' => __("Contact form", 'greyd_hub'),
                            'question' => __("How do I create a contact form?", 'greyd_hub'),
                            'description' => __("In this video, we will show you which widgets you use to create a simple contact form. You can also learn how to set up email notifications and secure your form with the integrated double opt-in function.", 'greyd_hub'),
                            'link' => __("https://docs.greyd.io/video/how-do-i-create-a-contact-form/", 'greyd_hub'),
                            'picture' => '{{img_dark}}'
                        ]
                    )
                ),
                'mypost_seitenumbau' => array(
                    'type'         => __("myposttype", 'greyd_hub'),
                    'slug'         => __('tutorialvideo', 'greyd_hub'),
                    'title'        => __('Tutorial Video', 'greyd_hub'),
                    'content'      =>   '<!-- wp:columns {"className":"","row":{"type":"row_xxxl"}} -->
                                        <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                        <div class="wp-block-column col-12"><!-- wp:embed {"url":"https://vimeo.com/636975462/105b68cd2e","type":"video","providerNameSlug":"vimeo","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
                                        <figure class="wp-block-embed is-type-video is-provider-vimeo wp-block-embed-vimeo wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
                                        https://vimeo.com/636975462/105b68cd2e
                                        </div></figure>
                                        <!-- /wp:embed --></div>
                                        <!-- /wp:column --></div>
                                        <!-- /wp:columns -->',
                    'meta' => array(
                        'dynamic_meta' => [
                            'topic' => __('Tutorial Video', 'greyd_hub'),
                            'question' => __("Gutenberg Editor in Greyd.Suite", 'greyd_hub'),
                            'description' => __("Watch this video to get to know the most important features of Gutenberg and the specialties in Greyd.Suite. Start right now!", 'greyd_hub'),
                            'link' => __("https://helpcenter.greyd.io/topics/how-to-start/?post_type=tutorial", 'greyd_hub'),
                            'picture' => '{{img_dark}}'
                        ]
                    )
                )
            ),
            'popups' => array(
                // presets
                'empty' => array(
                    'icon'         => 'new_select.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('empty', 'greyd_hub'),
                    'title'        => __("empty", 'greyd_hub'),
                    'content'      =>   '<!-- wp:columns {"className":"","row":{"type":"row_xxxl"}} -->
                                        <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                        <div class="wp-block-column col-12"></div>
                                        <!-- /wp:column --></div>
                                        <!-- /wp:columns -->',
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) array( ),
                    )
                ),
                'classic' => array(
                    'icon'         => 'popup_classic.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('classic', 'greyd_hub'),
                    'title'        => __("Classic", 'greyd_hub'),
                    'content'      => popup_default_content_blocks( [ 'head' => __("Classic", 'greyd_hub') ] ),
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) $popup_design_default,
                    )
                ),
                'slidein' => array(
                    'icon'         => 'popup_slidein.png',
                    'type'         => 'greyd_popup',
                    'slug'         => __('slidein', 'greyd_hub'),
                    'title'        => __("Slide in", 'greyd_hub'),
                    'content'      => popup_default_content_blocks( [
                        'head' => __("Slide in", 'greyd_hub'),
                        'close_size' => '20',
                        'h_tag' => 'h5',
                        'button' => false,
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_scroll' => (object) array( 'value' => 'on', 'options' => (object) array( 'height' => '50%' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                    'title'        => __('Fullscreen', 'greyd_hub'),
                    'content'      => popup_default_content_blocks( [
                        'head' => __('Fullscreen', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                        'close_size' => '50',
                        'close_width' => '4',
                        'h_tag' => 'h2',
                        'empty' => '50',
                        'button_text' => _x("close popup", 'small', 'greyd_hub')
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) $popup_settings_default,
                        'popup_design' => (object) array_merge(
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
                    'content'      => popup_default_content_blocks( [
                        'head' => __('Announcement', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_init'   => (object) array( 'value' => 'on', 'options' => (object) array( 'time' => '5' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                    'content'      => popup_default_content_blocks( [
                        'head' => __('Sidebar', 'greyd_hub'),
                        'text' => __("I am a block of text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", 'greyd_hub'),
                        'close_size' => '40',
                        'close_width' => '3',
                        'empty' => '100',
                    ] ),
                    'meta' => array(
                        'popup_settings' => (object) array_merge(
                            $popup_settings_default,
                            array(
                                'on_init'   => (object) array( 'value' => 'on', 'options' => (object) array( 'time' => '5' ) ),
                                'on_idle'   => (object) array( 'value' => 'off' ),
                                'on_exit'   => (object) array( 'value' => 'off' ),
                                'highest'   => (object) array( 'value' => 'false' ),
                                'hashtag'   => (object) array( 'value' => 'false' ),
                            )
                        ),
                        'popup_design' => (object) array_merge(
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
                //     'content'      => popup_default_content_blocks( [ 'head' => __('Slide Up', 'greyd_hub') ] ),
                //     'meta' => array(
                //         'popup_settings' => (object) array_merge(
                //             $popup_settings_default,
                //             array(
                //                 'xxx' => 'xxx',
                //             )
                //         ),
                //         'popup_design' => (object) array_merge(
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
            ),
            'templates' => array(
                // dynamic templates
                'dt_1' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-what-is-a-dynamic-template", 'greyd_hub'),
                    'title'        => __("Example: What is a Dynamic Template", 'greyd_hub'),
                    'content'      => $dt_1_content
                ),
                'dt_2' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-dynamic-content", 'greyd_hub'),
                    'title'        => __("Example: Displaying dynamic content", 'greyd_hub'),
                    'content'      => $dt_2_content
                ),
                'dt_3' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'dynamic',
                    'slug'         => __("example-display-post-type", 'greyd_hub'),
                    'title'        => __("Example: Display of a post type", 'greyd_hub'),
                    'content'      => $dt_3_content
                ),
                // templates
                'footer' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'footer',
                    'title'        => __('Footer', 'greyd_hub'),
                    'content'      => $footer_content
                ),
                'main-menu-offcanvas' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'main-menu-offcanvas',
                    'title'        => __("Main menu (off-canvas)", 'greyd_hub'),
                    'content'      => sprintf($menu_content, 'main-menu-offcanvas')
                ),
                'meta-menu-offcanvas' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'navigation',
                    'slug'         => 'meta-menu-offcanvas',
                    'title'        => __("Meta menu (off-canvas)", 'greyd_hub'),
                    'content'      => sprintf($menu_content, 'meta-menu-offcanvas')
                ),
                'single' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'single',
                    'slug'         => 'single',
                    'title'        => __("Post page", 'greyd_hub'),
                    'content'      => $single_content
                ),
                'archives' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'archives',
                    'slug'         => 'archives',
                    'title'        => __("Post archive", 'greyd_hub'),
                    'content'      => $archive_content
                ),
                'search' => array(
                    'disabled'     => true,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'search',
                    'slug'         => 'search',
                    'title'        => __("Search results", 'greyd_hub'),
                    'content'      => $search_content
                ),
                '404' => array(
                    'type'         => 'dynamic_template',
                    'template_type'=> 'more',
                    'slug'         => '404',
                    'title'        => __("404 page", 'greyd_hub'),
                    'content'      => $fourofour_content
                ),
                'compatibility' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'more',
                    'slug'         => 'compatibility',
                    'title'        => __("Compatibility message", 'greyd_hub'),
                    'content'      => '<!-- wp:columns {"className":"","row":{"type":"row_xxl"}} -->
                                        <div class="wp-block-columns"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
                                        <div class="wp-block-column col-12"><!-- wp:paragraph -->
                                        <p>'.__("Compatibility message", 'greyd_hub').'</p>
                                        <!-- /wp:paragraph --></div>
                                        <!-- /wp:column --></div>
                                        <!-- /wp:columns -->' // $compatibility_content
                ),
                // woocommerce templates
                'woo_product' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product',
                    'title'        => __("Product page (WooCommerce)", 'greyd_hub'),
                    'content'      => '<!-- wp:greyd/woocontent -->WOO_CONTENT<!-- /wp:greyd/woocontent -->'
                ),
                'woo-product-category' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product-category',
                    'title'        => __("Shop overview (by category)", 'greyd_hub'),
                    'content'      => '<!-- wp:greyd/woocontent -->WOO_CONTENT<!-- /wp:greyd/woocontent -->'
                ),
                'woo-product-tag' => array(
                    'default'      => false,
                    'type'         => 'dynamic_template',
                    'template_type'=> 'woo',
                    'slug'         => 'woo-product-tag',
                    'title'        => __("Shop overview by tag", 'greyd_hub'),
                    'content'      => '<!-- wp:greyd/woocontent -->WOO_CONTENT<!-- /wp:greyd/woocontent -->'
                ),
            ),
            'products' => array(
                // dynamic templates
                'greyd_product' => array(
                    'type'         => 'product',
                    'slug'         => __("greyd-example-product", 'greyd_hub'),
                    'title'        => __("Example product", 'greyd_hub'),
                    'content'      => __("This is the content of the sample product.", 'greyd_hub'),
                    'excerpt'      => __("This is the short description of the sample product.", 'greyd_hub'),
                    'img'          => '{{product01}}',
                    'meta'         => array(
                        '_product_image_gallery' => '{{product02}}',
                        '_mini_desc'        => __( "This is the shopping cart short description.", 'greyd_hub' ),
                        '_regular_price'    => '99.99',
                        '_price'            => '99.99',
                        '_virtual'          => 'yes',
                    )
                ),
            ),
            'media-product' => array(
                'product01' => array(
                    'slug'  => 'greyd-product-01',
                    'id'    => null,
                    'url'   => null,
                    'format'=> 'png'
                ),
                'product02' => array(
                    'slug'  => 'greyd-product-02',
                    'id'    => null,
                    'url'   => null,
                    'format'=> 'png'
                )
            ),
            'menu' => array(
                'name'      => __("Greyd Example menu", 'greyd_hub'),
                'location'  => 'main-menu'
            )
        );
    }


}