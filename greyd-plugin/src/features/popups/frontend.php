<?php
/**
 * Popup Feature Frontend.
 */
namespace Greyd\Popups;

use Greyd\Helper;
use Greyd\Enqueue;

if( ! defined( 'ABSPATH' ) ) exit;

new Render($config);
class Render {
    
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
     * Holds all popup ids to be rendered on the page.
     */
    public static $popup_ids = array();


    /**
     * Class constructor.
     */
    public function __construct($config) {
        // set config
        $this->config = (object)$config;
        
        // frontend
        if (!is_admin() && isset($_GET['popup_preview'])) {
            // hide adim bar in preview
            add_filter( 'show_admin_bar', '__return_false' );
        }
        else if (!isset($_GET['popup_preview'])) {
            // add active popups to end of page
            add_action( 'wp_footer', array($this, 'render_popups'), 1 );
        }
        
    }
    
    /**
     * Add a popup ID to be rendered.
     * 
     * @param int $id   post_id of a Popup to be rendered.
     */
    public static function add_popup_id($id) {
        self::$popup_ids[$id] = $id;
    }

    /**
     * Get all active Popups for the current page.
     * 
     * @return object[] $popups     Array of Popup Objects or false
     */
    public static function get_active_popups() {

        // debug(self::$popup_ids);
        if ( is_customize_preview() ) return array();

        $popups = array();
        $posts  = Helper::get_all_posts('greyd_popup', false);
        // debug($posts);
        
        foreach ( $posts as $popup ) {
            $show_popup = false;
            $meta_settings = (array)get_post_meta($popup->id, 'popup_settings', true);
            foreach( $meta_settings as $k => $v ) {
                if ( is_array($v) && isset($v['options']) ) $v['options'] = (object)$v['options'];
                $meta_settings[$k] = (object) $v;
            }

            // debug($meta_settings);

            if ( self::is_popup_active( $popup, $meta_settings ) ) {
                // add to array
                $popups[] = self::get_popup_settings( $popup, $meta_settings );
            }
            else if ( isset(self::$popup_ids[$popup->id]) || isset(self::$popup_ids[$popup->slug]) ) {
                // add additional popup, e.g. when added per button
                $popups[] = self::get_popup_settings( $popup, $meta_settings, false );
            }
            
        }
        
        // sort by prio
        usort($popups, function($a, $b) {
            if ($a['prio'] != $b['prio']) return intval($a['prio']) - intval($b['prio']);
            else return strtotime($a['date']) - strtotime($b['date']);
        });
        // debug($popups);

        return (count($popups) > 0) ? $popups : array();
    }

    /**
     * Render the active Popups.
     */
    public function render_popups() {
        
        $popups = self::get_active_popups();

        if ( empty($popups) ) return;

        $style = "";
        $content = "";
        foreach ($popups as $popup) {

            $version = isset($popup['design']) && isset( $popup['design']['version'] ) ? intval( $popup['design']['version'] ) : 1;

            // render based on version
            if ( $version === 2 ) {
                $popup_rendered = $this->render_popup_v2($popup);
            }
            else {
                $popup_rendered = $this->render_popup_v1($popup);
            }
            if (isset($popup_rendered)) {
                $style .= $popup_rendered['style'];
                $content .= $popup_rendered['content'];
            }
        }

        // add base style and script
        wp_register_style("popups_css", plugin_dir_url(__FILE__).'assets/css/frontend.css', null, GREYD_VERSION, 'all');
        wp_enqueue_style("popups_css");

        wp_register_script(
			'popups_js',
			plugin_dir_url(__FILE__).'assets/js/frontend.js',
			array('jquery'),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
        wp_enqueue_script('popups_js');
        
        // return html
        echo "<section class='popups' aria-label='" . __( 'Pop-ups', 'greyd_hub') . "'>".$content."</section>";
        // add style
        echo "<style title='greyd_custom_css' type='text/css'>".$style."</style>";
    }

    /**
     * Get current page attributes
     * 
     * @return array
     */
    public static function get_page_atts() {

        // check cache
        if ( $cache = wp_cache_get( "greyd_popups_page_atts" ) ) {
            return $cache;
        }

        $page_atts = array(
            'post_type'         => null,
            'post_id'           => null,
            'post_categories'   => array(),
        );

        if ( is_front_page() && empty( get_option('page_on_front') ) ) {
            $page_atts['post_type']  = 'system';
            $page_atts['post_id']    = 'front';
        }
        else if ( is_404() ) {
            $page_atts['post_type']  = 'system';
            $page_atts['post_id']    = '404';
        }
        else if ( is_search() ) {
            $page_atts['post_type']  = 'system';
            $page_atts['post_id']    = 'search';
        }
        else if ( is_archive() ) {
            $page_atts['post_type']  = get_post_type();
            $page_atts['post_id']    = 'archive';
            if ( !$page_atts['post_type'] && is_tax() ) {
                $queried_obj = get_queried_object();
                if ( isset($queried_obj->taxonomy) ) {
                    $tax_slug = $queried_obj->taxonomy;
                    global $wp_taxonomies;
                    $page_atts['post_type'] = isset( $wp_taxonomies[$tax_slug] ) ? $wp_taxonomies[$tax_slug]->object_type[0] : '';
                }
            }
        }
        else {
            $page_atts['post_type']  = get_post_type();
            $page_atts['post_id']    = get_the_ID();
            if ( $page_atts['post_type'] != 'page' ) {
                $categories = Helper::get_categories(get_post());
                if (isset($categories) && is_array($categories) && count($categories) > 0) {
                    foreach ($categories as $category) {
                        array_push($page_atts['post_categories'], "cat_".$category->term_id);
                    }
                }
            }
        }
        // debug($page_atts);

        // set cache
        if ( !empty( $page_atts['post_type'] ) ) {
            wp_cache_set( "greyd_popups_page_atts", $page_atts );
        }

        return $page_atts;
    }

    /**
     * Wether or not a popup is active and therefore should be
     * rendered on the current page.
     * 
     * @param WP_Post $popup        Popup post object.
     * @param array $meta_settings  The Popup meta option.
     * 
     * @return bool
     */
    public static function is_popup_active( $popup, $meta_settings ) {

        $page_atts = self::get_page_atts();
        
        // return if posttype settings are not defined
        if ( !isset($meta_settings['on_'.$page_atts['post_type']]) ) return false;

        // return if the popup should not be displayed on this post type
        $meta = $meta_settings['on_'.$page_atts['post_type']];
        if ( !isset($meta->value) || $meta->value === "off" ) return false;

        // "on" | "off"
        $show_popup = $meta->value == "on" ? true : false;

        // check custom selection
        if ($meta->value == "custom") {
            if (isset($meta->options)) {
                if ( isset($meta->options->show_on) && $meta->options->show_on != "" ) {
                    $on = explode(',', $meta->options->show_on);
                    if (in_array($page_atts['post_id'], $on)) {
                        $show_popup = true;
                    }
                }
                if (isset($meta->options->items) && $meta->options->items != "") {
                    $on = explode(',', $meta->options->items);
                    if (in_array($page_atts['post_id'], $on)) {
                        $show_popup = true;
                    }
                    else if (count($page_atts['post_categories']) > 0) {
                        foreach ($page_atts['post_categories'] as $cat) {
                            if (in_array($cat, $on)) 
                                            if (in_array($cat, $on)) 
                            if (in_array($cat, $on)) 
                                $show_popup = true;
                        }
                    }
                }
            }
        }

        if ( !$show_popup ) return false;

        // check time condition
        $time = ($meta_settings['time']->value == 'off') ? 'off' : explode(',', $meta_settings['time']->options->select);
        if ( is_array($time) ) {
            if (in_array('custom', $time)) {
                $custom = strtolower(preg_replace("/\s/", "", $meta_settings['time']->options->custom));
                $time = array_merge($time, explode(',', $custom));
            }
            $show_popup = false;
            $now = date("H");
            foreach ($time as $t) {
                if ($t == 'custom') continue;
                $t = explode("-", $t); // explode start-end to ["start","end"]
                if (!isset($t[1])) $t[1] = $t[0]+1; // if only no end isset -> use start+1 (12 -> 12-13)
                if ( intval($t[0]) <= $now && $now < intval($t[1]) ) {
                    $show_popup = true;
                    break;
                }
            }

        }

        if ( !$show_popup ) return false;

        // check user condition
        $user = ($meta_settings['user']->value == 'off') ? 'off' : explode(',', $meta_settings['user']->options->select);
        if (is_array($user)) {
            $show_popup = false;
            $me = is_user_logged_in() ? wp_get_current_user()->roles : array("none");
            $flipped = array_flip($me); // get userroles as keys of array
            foreach ($user as $u) {
                if (isset($flipped[$u])) { // check if key isset
                    $show_popup = true;
                    break;
                }
            }
        }

        if ( !$show_popup ) return false;

        // check url-param condition
        $urlparam = ($meta_settings['urlparam']->value == 'off') ? 'off' : (array)$meta_settings['urlparam']->options;
        if (is_array($urlparam)) {
            $show_popup = false;
            $params = \Greyd\Extensions\Cookie_Handler::get_all_url_values(); // get all url_params
            // debug($params);
            $should = $urlparam['value'];
            $is = isset($params[$urlparam['name']]) ? $params[$urlparam['name']] : NULL;
            if ($urlparam['condition'] === "is") {
                if ($should === $is) $show_popup = true;
            } 
            else if ($urlparam['condition'] === "is_not") {
                if ($should !== $is) $show_popup = true;
            } 
            else if ($urlparam['condition'] === "has") {
                if (strpos($is, $should) !== false) $show_popup = true;
            } 
            else if ($urlparam['condition'] === "empty") {
                if (empty($is)) $show_popup = true;
            } 
            else if ($urlparam['condition'] === "not_empty") {
                if (!empty($is)) $show_popup = true;
            }
        }

        if ( !$show_popup ) return false;

        // check site cookie if setting 'once' is enabled
        if ( $meta_settings['once']->value === "true" && isset($_COOKIE["seenpopup_".$popup->id]) && $_COOKIE["seenpopup_".$popup->id] === "true" ) {
            $show_popup = false;
        }

        return $show_popup;
    }

    /**
     * Get all popup settings
     * 
     * @param WP_Post $popup        Popup post object.
     * @param array $meta_settings  The Popup meta option.
     * @param bool $trigger         Attach trigger settings and conditions (unwanted when added only by button)
     * 
     * @return array
     */
    public static function get_popup_settings( $popup, $meta_settings, $trigger = true ) {

        $popup_sets = (array) $popup;
        $popup_sets['prio']         = $meta_settings['prio']->value;
        $popup_sets['date']         = get_post_field('post_date', $popup->id);
        $popup_sets['highest']      = $meta_settings['highest']->value;
        $popup_sets['hashtag']      = $meta_settings['hashtag']->value;
        $popup_sets['once']         = $meta_settings['once']->value;
        $popup_sets['content']      = get_post_field('post_content', $popup->id);
        $popup_sets['trigger']      = array( 'on_init' => 'off',  'on_scroll' => 'off',  'on_idle' => 'off',  'on_exit' => 'off' );
        $popup_sets['conditions']   = array( 'delay' => 'off',  'referer' => 'off',  'device' => 'off',  'browser' => 'off' );
        $popup_sets['arialabel']    = isset($meta_settings['arialabel']->value) ? $meta_settings['arialabel']->value : '';

        if ( $trigger ) {
            $popup_sets['trigger'] = array(
                'on_init'   => ($meta_settings['on_init']->value == 'off') ? 'off' : $meta_settings['on_init']->options->time,
                'on_scroll' => ($meta_settings['on_scroll']->value == 'off') ? 'off' : $meta_settings['on_scroll']->options->height,
                'on_idle'   => ($meta_settings['on_idle']->value == 'off') ? 'off' : $meta_settings['on_idle']->options->time,
                'on_exit'   => $meta_settings['on_exit']->value,
            );
            $popup_sets['conditions'] = array(
                'delay'     => ($meta_settings['delay']->value == 'off') ? 'off' : $meta_settings['delay']->options->count,
                'referer'   => ($meta_settings['referer']->value == 'off') ? 'off' : (array)$meta_settings['referer']->options,
                'device'    => ($meta_settings['device']->value == 'off') ? 'off' : explode(',', $meta_settings['device']->options->select),
                'browser'   => ($meta_settings['browser']->value == 'off') ? 'off' : explode(',', $meta_settings['browser']->options->select),
            );
        }

        $popup_sets['design'] = self::get_popup_design( $popup, $meta_settings );

        return $popup_sets;
    }

    /**
     * Get the popup design settings.
     * 
     * @param WP_Post $popup        Popup post object.
     * @param array $meta_settings  The Popup meta option.
     * 
     * @return array
     */
    public static function get_popup_design( $popup, $meta_settings ) {

        $meta_design = get_post_meta($popup->id, 'popup_design', true);
        // debug( $meta_design, true );

        // default
        if ( empty($meta_design) ) {
            // $meta_design = array( 'version' => 2 );
            $meta_design = array(
                'version' => 2,
                'layout' => array(),
                'design' => array(),
                'more' => array()
            );
        }
        
        $meta_design = (array) $meta_design;
        
        if ( empty($meta_design['layout']) ) {
            $meta_design['version'] = 1;
            $meta_design['layout'] = array(
                'position' => 'center center',
                'size' => array( 'width' => 'auto', 'height' => 'auto', 'align' => 'center' ),
                'padding' => array( 'top' => '1em', 'left'  => '1em', 'right' => '1em', 'bottom'  => '1em' ),
                'margin' => array( 'top' => '1.5em', 'left'  => '1.5em', 'right' => '1.5em', 'bottom'  => '1.5em' ),
                'mobile' => array()
            );
        }

        if ( empty($meta_design['design']) ) {
            $color_foreground = 'var(--wp--preset--color--foreground)';
            $color_background = 'var(--wp--preset--color--background)';
            if ( Helper::is_greyd_classic() ) {
                $color_foreground = 'var(--wp--preset--color--color-21)';
                $color_background = 'var(--wp--preset--color--color-23)';
            }
            $meta_design['version'] = 1;
            $meta_design['design'] = array(
                'colors' => array(
                    'text' => $color_foreground,
                    'headline' => $color_foreground,
                    'background' => $color_background
                ),
                'border_radius' => array( 'topLeft' => '5px', 'topRight'  => '5px', 'bottomRight' => '5px', 'bottomLeft'  => '5px' ),
                'border' => array( 'width' => '0px', 'style' => 'solid', 'color' => $color_foreground ),
                'shadow' => 'none',
                'hover' => array()
            );
        }

        if ( empty($meta_design['more']) ) {
            $meta_design['version'] = 1;
            $meta_design['more'] = array(
                'overlay_enable' => 'true',
                'overlay_color' => 'var(--wp--preset--color--black)',
                'overlay_opacity' => '0.25',
                'overlay_effect' => array(
                    'effect' => 'blur',
                    'blur' => '10',
                    'brightness' => '110',
                    'contrast' => '110',
                    'grayscale' => '10',
                    'hue-rotate' => '10',
                    'invert' => '10',
                    'saturate' => '110',
                    'sepia' => '10'
                ),
                'overlay_close' => 'true',
                'overlay_noscroll' => 'false',
                'anim_type' => 'fade',
                'anim_time' => '300',
                'overflow' => 'auto',
                'css_class' => ''
            );
        }

        return $meta_design;
    }

    /*
    ====================================================================================
        Popup V1
    ====================================================================================
    */

    /**
     * Render a old version 1 Popup.
     * (pre blockeditor)
     * 
     * @param object $popup     The Popup Object
     * @return object
     *      @property string style      rendered inline css style
     *      @property string content    rendered content
     */
    public function render_popup_v1($popup) {
        
        $id = "popup_".$popup['id'];
        $slug = Helper::get_clean_slug(esc_attr($popup['title']));
        $class = isset($popup['design']['css_class']) ? $popup['design']['css_class'] : '';

        $default_design = array(
            // layout
            'position' => 'mc',
            'width' => 'auto',
            'height' => 'auto',
            'align' => 'middle',
            'padding_tb' => 20,
            'padding_lr' => 20,
            'margin_tb' => 30,
            'margin_lr' => 30,
            'mobile_enable' => 'false',
            'mobile_width' => 'auto',
            'mobile_height' => 'auto',
            'mobile_align' => 'middle',
            'mobile_padding_tb' => 20,
            'mobile_padding_lr' => 20,
            'mobile_margin_tb' => 30,
            'mobile_margin_lr' => 30,
            // design
            'text_color' => 'color_21',
            'head_color' => 'color_21',
            'bg_color' => 'color_23',
            'border_radius' => 5,
            'border_enable' => 'false',
            'border_width' => 1,
            'border_color' => 'color_21',
            'border_style' => 'solid',
            'shadow_enable' => 'false',
            'shadow' => 'none',
            'hover_enable' => 'false',
            'hover_text_color' => 'color_21',
            'hover_head_color' => 'color_21',
            'hover_bg_color' => 'color_23',
            'hover_shadow' => 'none',
            // more
            'overlay_enable' => 'true',
            'overlay_color' => 'color_61',
            'overlay_opacity' => '25',
            'overlay_effect' => 'blur',
            'overlay_effect_blur' => '10',
            'overlay_close' => 'true',
            'overlay_noscroll' => 'false',
            'anim_type' => 'fade',
            'anim_time' => 300,
            'overflow' => 'auto',
            'css_class' => ''
        );

        $design = array_merge( $default_design, $popup['design'] );

        // position
        $p = $design['position'];
        $jc = ($p=='tl' || $p=='ml' || $p=='bl') ? 'flex-start' : (($p=='tc' || $p=='mc' || $p=='bc') ? 'center' : 'flex-end');
        $ai = ($p=='tl' || $p=='tc' || $p=='tr') ? 'flex-start' : (($p=='ml' || $p=='mc' || $p=='mr') ? 'center' : 'flex-end');

        // width height and alignment
        $width = $this->make_size_value($design, 'width');
        $height = $this->make_size_value($design, 'height');
        $align = "flex-start";
        if ($height != 'auto' && isset($design['align']) ) {
            $align = ($design['align'] == 'top') ? 'flex-start' : (($design['align'] == 'middle') ? 'center' : 'flex-end');
        }
        $inner_width = ($design['margin_lr'] > 0) ? "calc(100% - ".(2*$design['margin_lr'])."px)" : "100%";
        $inner_height = ($design['margin_tb'] > 0) ? "calc(100% - ".(2*$design['margin_tb'])."px)" : "100%";
        $inner_width_max = ($design['margin_lr'] > 0) ? "calc(100vw - ".(2*$design['margin_lr'])."px)" : "100vw";
        $inner_height_max = ($design['margin_tb'] > 0) ? "calc(100vh - ".(2*$design['margin_tb'])."px)" : "100vh";

        // mobile
        $mobile_width = $this->make_size_value($design, 'mobile_width');
        $mobile_height = $this->make_size_value($design, 'mobile_height');
        $mobile_align = "flex-start";
        if ( $mobile_height != 'auto' && isset($design['mobile_align']) ) {
            $mobile_align = ($design['mobile_align'] == 'top') ? 'flex-start' : (($design['mobile_align'] == 'middle') ? 'center' : 'flex-end');
        }
        $mobile_inner_width = ($design['mobile_margin_lr'] > 0) ? "calc(100% - ".(2*$design['mobile_margin_lr'])."px)" : "100%";
        $mobile_inner_height = ($design['mobile_margin_tb'] > 0) ? "calc(100% - ".(2*$design['mobile_margin_tb'])."px)" : "100%";
        $mobile_inner_width_max = ($design['mobile_margin_lr'] > 0) ? "calc(100vw - ".(2*$design['mobile_margin_lr'])."px)" : "100vw";
        $mobile_inner_height_max = ($design['mobile_margin_tb'] > 0) ? "calc(100vh - 50px - ".(2*$design['mobile_margin_tb'])."px)" : "calc(100vh - 50px)";

        // textcolor
        $color = isset($design['text_color']) ? $this->make_color($design['text_color']) : '';
        $color_hover = isset($design['hover_text_color']) ? $this->make_color($design['hover_text_color']) : '';
        // headline color
        $head = isset($design['head_color']) ? $this->make_color($design['head_color']) : '';
        $head_hover = isset($design['hover_head_color']) ? $this->make_color($design['hover_head_color']) : '';
        // background color
        $background = isset($design['bg_color']) ? $this->make_color($design['bg_color']) : '';
        $background_hover = isset($design['hover_bg_color']) ? $this->make_color($design['hover_bg_color']) : '';
        // border
        $border = $border_hover = "none";
        if (isset($design['border_enable']) && $design['border_enable'] == 'true') {
            $bcolor = isset($design['border_color']) ? $this->make_color($design['border_color']) : '';
            $border = $design['border_width']."px ".$design['border_style']." ".$bcolor;
            $bcolor_hover = isset($design['hover_border_color']) ? $this->make_color($design['hover_border_color']) : '';
            $border_hover = $design['border_width']."px ".$design['border_style']." ".$bcolor_hover;
        }
        // shadow
        $shadow = $shadow_hover = "none";
        if ( isset($design['shadow_enable']) && $design['shadow_enable'] == 'true' ) {
            if (Helper::is_greyd_classic()) {
                $shadow = \vc\helper::get_shadow($design, 'shadow');
                $shadow_hover = \vc\helper::get_shadow($design, 'hover_shadow');
            }
        }

        // transition
        $transition_prop = "opacity, top, left";
        $transition_time = strval( intval($design['anim_time']) / 1000.0 ) . "s";

        // make style
        $inline_style = 
            "#".$id." { 
                justify-content: ".$jc.";
                align-items: ".$ai.";
                will-change: $transition_prop;
                transition-property: $transition_prop;
                transition-duration: $transition_time;
            }";
        // basic
        $inline_style .= 
            "#".$id." .popup_content { 
                width: ".$width.";
                height: ".$height.";
            }
            #".$id." .popup_content .popup_wrapper { 
                justify-content: ".$align.";
                width: ".$inner_width.";
                height: ".$inner_height.";
                max-width: ".$inner_width_max.";
                max-height: ".$inner_height_max.";
                margin: ".$design['margin_tb']."px ".$design['margin_lr']."px;
                padding: ".$design['padding_tb']."px ".$design['padding_lr']."px;
                border-radius: ".$design['border_radius']."px;
                overflow: ".( isset($design['overflow']) ? $design['overflow'] : "hidden" ).";
                color: ".$color.";
                background-color: ".$background.";
                border: ".$border.";
                box-shadow: ".$shadow.";
                transition-duration: $transition_time;
            } ";
		$inline_style .= ":where( " .
			"#".$id." .popup_content h1, ".
			"#".$id." .popup_content h2, ".
			"#".$id." .popup_content h3, ".
			"#".$id." .popup_content h4, ".
			"#".$id." .popup_content h5, ".
			"#".$id." .popup_content h6 ".
		") { 
			color: ".$head.";
		} ";
        // hover
        if (isset($design['hover_enable']) && $design['hover_enable'] == 'true') {
            $inline_style .= 
                "#".$id." .popup_content:hover .popup_wrapper { 
                    color: ".$color_hover.";
                    background-color: ".$background_hover.";
                    border: ".$border_hover.";
                    box-shadow: ".$shadow_hover.";
                } ";
			$inline_style .= ":where( " .
				"#".$id." .popup_content:hover h1, ".
				"#".$id." .popup_content:hover h2, ".
				"#".$id." .popup_content:hover h3, ".
				"#".$id." .popup_content:hover h4, ".
				"#".$id." .popup_content:hover h5, ".
				"#".$id." .popup_content:hover h6 ".
			") { 
				color: ".$head_hover.";
			} ";
        }
        // mobile
        if (isset($design['mobile_enable']) && $design['mobile_enable'] == 'true') {
            $inline_style .= 
                "@media (max-width: 992px) { 
                    #".$id." .popup_content { 
                        width: ".$mobile_width.";
                        height: ".$mobile_height.";
                    }
                    #".$id." .popup_content .popup_wrapper { 
                        justify-content: ".$mobile_align.";
                        width: ".$mobile_inner_width.";
                        height: ".$mobile_inner_height.";
                        max-width: ".$mobile_inner_width_max.";
                        max-height: ".$mobile_inner_height_max.";
                        margin: ".$design['mobile_margin_tb']."px ".$design['mobile_margin_lr']."px;
                        padding: ".$design['mobile_padding_tb']."px ".$design['mobile_padding_lr']."px;
                    } 
                } ";
        }

        // overlay
        $overlay_click = "";
        $overlay_effekt = "";
        if (isset($design['overlay_enable']) && $design['overlay_enable'] == 'true') {
            $overlay_data = $this->make_overlay($id, array(
                'color' => isset($design['overlay_color']) ? $this->make_color($design['overlay_color']) : '',
                'opacity' => $design['overlay_opacity']/100.0,
                'effect' => array(
                    'effect' => $design['overlay_effect'],
                    'value' => $design['overlay_effect'] != 'none' ? 
                        $design['overlay_effect_'.$design['overlay_effect']] : ''
                ),
                'noscroll' => $design['overlay_noscroll'],
                'close' => $design['overlay_close'],
                'transition_prop' => $transition_prop,
                'transition_time' => $transition_time
            ));
            $inline_style .= $overlay_data['style'];
            $overlay_click = $overlay_data['data'];
        }

        // animation
        $animation = "";
        if ($design['anim_type'] != 'none') {
            $animation_data = $this->make_animation($id, array(
                'type' => $design['anim_type'],
                'time' => $design['anim_time']
            ));
            $inline_style .= $animation_data['style'];
            $animation = $animation_data['data'];
        }
        // trigger, conditions and more
        $trigger = "data-trigger='".json_encode($popup['trigger'])."'";
        $conditions = "data-conditions='".json_encode($popup['conditions'])."'";
        $more = "";
        if ($popup['highest'] == 'true') $more .= "data-highest='true'";
        if ($popup['hashtag'] == 'true') $more .= " data-hashtag='true'";
        if ($popup['once'] == 'true') {
            $more .= " data-once='true'";
            $more .= " data-cookie='seen".$id."'";
        }
        
        // make content
        if (has_blocks($popup['content'])) {
            $post_content = apply_filters( 'the_content', $popup['content'] );
        }
        else {
            // fix css
            if (Helper::is_greyd_classic()) {
                $inline_style .= \vc\helper::get_vc_custom_css($popup['content']);
            }
            $post_content = do_shortcode($popup['content']);
        }

		$aria_label = isset($popup['arialabel']) && !empty($popup['arialabel']) ? "aria-label='".$popup['arialabel']."'" : "";
        
        $content = "<div id='".$id."' name='".$slug."' class='popup ".$class." hidden' ".$animation." ".$trigger." ".$conditions." ".$more." ".$aria_label.">
                        <div class='popup_overlay' ".$overlay_click." ".$overlay_effekt." ></div>
                        <div class='popup_content animate_fast'>
                            <div class='popup_wrapper animate_fast'>".$post_content."</div>
                        </div>
                    </div>";

        return array(
            'style' => $inline_style,
            'content' => $content
        );
    }

    /*
    ====================================================================================
        Popup V2
    ====================================================================================
    */

    /**
     * Render a version 2 Popup.
     * In v2 the popup_design postmeta data is different and has a deeper data structure.
     * This was extended to enable editing of the Popup Design in the blockeditor.
     * 
     * @param object $popup     The Popup Object
     * @return object
     *      @property string style      rendered inline css style
     *      @property string content    rendered content
     */
    public function render_popup_v2($popup) {

        $inline_style = "";
        $content = "";

		$popup = json_decode( json_encode( $popup ), true );
        
        $id = "popup_".$popup['id'];
        $slug = Helper::get_clean_slug(esc_attr($popup['title']));
        $class = $popup['design']['more']['css_class'];

        // transition
        $transition_prop = "opacity, top, left";
        $transition_time = strval( intval($popup['design']['more']['anim_time']) / 1000.0 ) . "s";

        // layout
        $inline_style .= $this->make_layout_css($id, array(
            'layout' => $popup['design']['layout'],
            'transition_prop' => $transition_prop,
            'transition_time' => $transition_time,
            'overflow' => $popup['design']['more']['overflow']
        ));
        if (isset($popup['design']['layout']['mobile']) && !empty($popup['design']['layout']['mobile'])) {
            $inline_style .= $this->make_layout_css($id, array(
                'layout' => $popup['design']['layout']['mobile']
            ), 'mobile');
        }

        // design
        $inline_style .= $this->make_design_css($id, $popup['design']['design']);
        if (isset($popup['design']['design']['hover']) && !empty($popup['design']['design']['hover'])) {
            $inline_style .= $this->make_design_css($id, $popup['design']['design']['hover'], 'hover');
        }
        
        // overlay
        $overlay = "";
        if ($popup['design']['more']['overlay_enable'] == 'true') {
            $overlay_data = $this->make_overlay($id, array(
                'color' => $popup['design']['more']['overlay_color'],
                'opacity' => $popup['design']['more']['overlay_opacity'],
                'effect' => array(
                    'effect' => $popup['design']['more']['overlay_effect']['effect'],
                    'value' => $popup['design']['more']['overlay_effect']['effect'] != 'none' ? 
                        $popup['design']['more']['overlay_effect'][$popup['design']['more']['overlay_effect']['effect']] : ''
                ),
                'noscroll' => $popup['design']['more']['overlay_noscroll'],
                'close' => $popup['design']['more']['overlay_close'],
                'transition_prop' => $transition_prop,
                'transition_time' => $transition_time
            ));
            $inline_style .= $overlay_data['style'];
            $overlay = $overlay_data['data'];
        }

        // animation
        $animation = "";
        if ($popup['design']['more']['anim_type'] != 'none') {
            $animation_data = $this->make_animation($id, array(
                'type' => $popup['design']['more']['anim_type'],
                'time' => $popup['design']['more']['anim_time']
            ));
            $inline_style .= $animation_data['style'];
            $animation = $animation_data['data'];
        }

        // trigger, conditions and more
        $data = "data-trigger='".json_encode($popup['trigger'])."'";
        $data .= " data-conditions='".json_encode($popup['conditions'])."'";
        if ($popup['highest'] == 'true') $data .= " data-highest='true'";
        if ($popup['hashtag'] == 'true') $data .= " data-hashtag='true'";
        if ($popup['once'] == 'true') {
            $data .= " data-once='true'";
            $data .= " data-cookie='seen".$id."'";
        }
        
        // body
        if (isset($popup['design']['more']['anim_body']['transform']) && $popup['design']['more']['anim_body']['transform'] != 'none') {
            $transform = $popup['design']['more']['anim_body']['transform'];
            if ($transform != 'none') {

                if ( Helper::is_greyd_classic() ) {
                    // fix for broken off canvas menu
                    $inline_style .= \processor::process_styles_string('
                    body.main_open_right .popup[data-transform^="translate"], body.main_open_right .popup[data-transform^="rotate"], body.main_open_right .popup[data-transform^="scale"] { right: 0px; }
                    body.main_open_left .popup[data-transform^="translate"], body.main_open_left .popup[data-transform^="rotate"], body.main_open_left .popup[data-transform^="scale"] { left: 0px; }
                    body.main_open_full_right .popup[data-transform^="translate"], body.main_open_full_right .popup[data-transform^="rotate"], body.main_open_full_right .popup[data-transform^="scale"] { right: 0px; }
                    body.main_open_right .popup[data-transform^="translate"], body.main_open_right .popup[data-transform^="rotate"], body.main_open_right .popup[data-transform^="scale"] { left: calc(0px - var(--HEDOFFwidth)); }
                    body.main_open_left .popup[data-transform^="translate"], body.main_open_left .popup[data-transform^="rotate"], body.main_open_left .popup[data-transform^="scale"] { left: var(--HEDOFFwidth); }

                    @media (max-width: 1200px) {
                        body.main_open_right .popup[data-transform^="translate"], body.main_open_right .popup[data-transform^="rotate"], body.main_open_right .popup[data-transform^="scale"] { left: calc(0px - var(--HEDOFFwidthLG)); }
                        body.main_open_left .popup[data-transform^="translate"], body.main_open_left .popup[data-transform^="rotate"], body.main_open_left .popup[data-transform^="scale"] { left: var(--HEDOFFwidthLG); }
                    }
                    @media (max-width: 992px) {
                        body.main_open_right .popup[data-transform^="translate"], body.main_open_right .popup[data-transform^="rotate"], body.main_open_right .popup[data-transform^="scale"] { left: calc(0px - var(--HEDOFFwidthMD)); }
                        body.main_open_left .popup[data-transform^="translate"], body.main_open_left .popup[data-transform^="rotate"], body.main_open_left .popup[data-transform^="scale"] { left: var(--HEDOFFwidthMD); }
                    }
                    @media (max-width: 576px) {
                        body.main_open_right .popup[data-transform^="translate"], body.main_open_right .popup[data-transform^="rotate"], body.main_open_right .popup[data-transform^="scale"] { left: calc(0px - var(--HEDOFFwidthSM)); }
                        body.main_open_left .popup[data-transform^="translate"], body.main_open_left .popup[data-transform^="rotate"], body.main_open_left .popup[data-transform^="scale"] { left: var(--HEDOFFwidthSM); }
                    }');
                }
                
                if ($transform == 'translate') {
                    $valX = isset($popup['design']['more']['anim_body']['translateX']) ? $popup['design']['more']['anim_body']['translateX'] : "0px";
                    $valY = isset($popup['design']['more']['anim_body']['translateY']) ? $popup['design']['more']['anim_body']['translateY'] : "0px";
                    $transform .= "(".$valX.", ".$valY.")";
                }
                else if ($transform == 'scale') {
                    $valX = isset($popup['design']['more']['anim_body']['scaleX']) ? $popup['design']['more']['anim_body']['scaleX']."%" : "100%";
                    $valY = isset($popup['design']['more']['anim_body']['scaleY']) ? $popup['design']['more']['anim_body']['scaleY']."%" : "100%";
                    $transform .= "(".$valX.", ".$valY.")";
                }
                else if ($transform == 'rotate') {
                    $val = isset($popup['design']['more']['anim_body']['rotateZ']) ? $popup['design']['more']['anim_body']['rotateZ']."deg" : "0deg";
                    $transform .= "(".$val.")";
                }
                else $transform = 'none';
            }
            $origin = $popup['design']['more']['anim_body']['origin'];
            $data .= " data-transform='".$transform."'";
            $data .= " data-origin='".$origin."'";
        }

        // a11y
		$aria_label = isset($popup['arialabel']) && !empty($popup['arialabel']) ? "aria-label='".$popup['arialabel']."'" : "";
        $a11y = "role='dialog' aria-modal='true' aria-live='assertive' ".$aria_label;
        
        // make content
        $content = $this->do_content( $popup['content'] );
        
        $content = "<div id='".$id."' name='".$slug."' class='popup ".$class." hidden' ".$a11y." ".$animation." ".$data.">
                        <div class='popup_overlay' ".$overlay."></div>
                        <div class='popup_content animate_fast'>
                            <div class='popup_wrapper animate_fast'>".$content."</div>
                        </div>
                    </div>";

        return array(
            'style' => $inline_style,
            'content' => $content
        );
    }

    /**
     * Do content, similar to apply_filters( 'the_content', $content ).
     */
    public function do_content( $content ) {

        $content = do_blocks( $content );
        global $wp_embed;
        $content = $wp_embed->autoembed( $content );
        $content = do_shortcode($content);

		$content = Enqueue::replace_core_css_classes_in_block_content( $content );
        
        return $content;

        // leads to multiple errors
        // return apply_filters( 'the_content', $content );
    }

    /*
    ====================================================================================
        Render Helper
    ====================================================================================
    */

    /**
     * Convert color value to css var. (v1)
     * 
     * @param string $color     Color value from old design settings.
     * @return string $color    Color as global styles css var.
     */
    public function make_color($color) {
        // todo: suite colors
        if (Helper::is_greyd_classic()) {
            if ($color == "") $color = 'transparent';
            if (strpos($color, "color_") === 0) $color = 'var(--wp--preset--color--'.str_replace("_", "-", $color).')';
        }
        else {
            if ($color == 'color_11') $color = 'var(--wp--preset--color--primary)';	    // primary
            if ($color == 'color_12') $color = 'var(--wp--preset--color--secondary)';	// secondary
            if ($color == 'color_13') $color = 'var(--wp--preset--color--tertiary)';	// alternate
            if ($color == 'color_31') $color = 'var(--wp--preset--color--foreground)';  // very dark
            if ($color == 'color_21') $color = 'var(--wp--preset--color--dark)';		// dark
            if ($color == 'color_32') $color = 'var(--wp--preset--color--mediumdark)';  // slightly dark
            if ($color == 'color_22') $color = 'var(--wp--preset--color--mediumlight)'; // slightly bright
            if ($color == 'color_33') $color = 'var(--wp--preset--color--base)';		// bright
            if ($color == 'color_23') $color = 'var(--wp--preset--color--background)';  // very bright
            if ($color == 'color_61') $color = 'var(--wp--preset--color--darkest)';	    // black
            if ($color == 'color_62') $color = 'var(--wp--preset--color--lightest)';	// white
            if ($color == 'color_63') $color = 'var(--wp--preset--color--transparent)'; // transparent
        }
        return $color;
    }

    /**
     * Convert width/height setting to css value. (v1)
     * Value is combined from two settings: the base value and a custom setting.
     * 
     * @param object $data      The settings Object.
     * @param string $key       'width' or 'height'.
     * @return string $value    Converted css value.
     */
    public function make_size_value($data, $key) {
        $value = isset($data[$key]) ? $data[$key] : 'auto';
        if ($value == 'custom') $value = $data[$key.'_custom'];
        if ($value != 'auto') {
            if (strpos($value, 'px') === false && strpos($value, '%') === false) {
                if (intval($value) <= 100) $value .= "%";
                else $value .= "px";
            }
        }
        return $value;
    }

    /**
     * Render overlay from attributes. (v1 and v2)
     * 
     * @param string $id    Popup css ID.
     * @param object $atts  The overlay attributes.
     * @return object
     *      @property string style  Rendered inline css style.
     *      @property string data   Rendered data attributes.
     */
    public function make_overlay($id, $atts) {

        $inline_style = 
            "#".$id." .popup_overlay { 
                top: 0; left: 0; width: 100%; height: 100%;
                background: ".$atts['color'].";
                opacity: ".$atts['opacity'].";
                will-change: ".$atts['transition_prop'].";
                transition-property: ".$atts['transition_prop'].";
                transition-duration: ".$atts['transition_time'].";
            } ";
        $data = "data-opacity='".$atts['opacity']."'";
        if ($atts['effect']['effect'] != 'none') {
            $effect = $atts['effect']['effect'];
            $value = $atts['effect']['value'];
            $unit = "%";
            if ($effect == 'blur') $unit = "px";
            if ($effect == 'brightness') $unit = "";
            if ($effect == 'hue-rotate') $unit = "deg";
            $data .= " data-effect='".$effect."(".$value.$unit.")'";
        }
        if ($atts['noscroll'] == 'true') {
            $data .= " data-noscroll='true'";
        }
        if ($atts['close'] == 'true') 
            $data .= " onclick='popups.close(this)'";

        return array(
            'style' => $inline_style,
            'data' => $data
        );
    }

    /**
     * Render animation from attributes. (v1 and v2)
     * 
     * @param string $id    Popup css ID.
     * @param object $atts  The animation attributes.
     * @return object
     *      @property string style  Rendered inline css style.
     *      @property string data   Rendered data attributes.
     */
    public function make_animation($id, $atts) {

        $inline_style = "";
        $animation = "data-anim_type='".$atts['type']."'";
        $animation .= " data-anim_time='".$atts['time']."'";
        if ($atts['type'] == 'fade') {
            $inline_style .= "#".$id." { opacity: 0; } ";
        }
        else if ($atts['type'] == 'slide_top') {
            $inline_style .= "#".$id." { top: -100%; } ";
            $inline_style .= "#".$id." .popup_overlay { top: 100%; opacity: 0; } ";
        }
        else if ($atts['type'] == 'slide_bottom') {
            $inline_style .= "#".$id." { top: 100%; } ";
            $inline_style .= "#".$id." .popup_overlay { top: -100%; opacity: 0; } ";
        }
        else if ($atts['type'] == 'slide_left') {
            $inline_style .= "#".$id." { left: -100%; } ";
            $inline_style .= "#".$id." .popup_overlay { left: 100%; opacity: 0; } ";
        }
        else if ($atts['type'] == 'slide_right') {
            $inline_style .= "#".$id." { left: 100%; } ";
            $inline_style .= "#".$id." .popup_overlay { left: -100%; opacity: 0; } ";
        }
        else {
            $inline_style .= "#".$id." .popup_overlay { opacity: 0; } ";
            if ($atts['type'] == 'scale')
                $inline_style .= "#".$id." .popup_content .popup_wrapper { transform: scale(0); } ";
            else if ($atts['type'] == 'flip_vertical')
                $inline_style .= "#".$id." .popup_content .popup_wrapper { transform: rotateY(90deg); } ";
            else if ($atts['type'] == 'flip_horizontal')
                $inline_style .= "#".$id." .popup_content .popup_wrapper { transform: rotateX(90deg); } ";
            else if ($atts['type'] == 'flip_3d')
                $inline_style .= "#".$id." .popup_content .popup_wrapper { transform: rotate3d(1,1,1,240deg) scale(0); } ";
        }

        return array(
            'style' => $inline_style,
            'data' => $animation
        );
    }

    /**
     * Render layout css from attributes. (v2)
     * 
     * @param string $id    Popup css ID.
     * @param object $atts  The layout attributes.
     * @param string $mode  Empty or 'mobile'.
     * @return string style     Rendered inline css style.
     */
    public function make_layout_css($id, $atts, $mode='') {

        // position
        $position = isset($atts['layout']['position']) ? $atts['layout']['position'] : ' ';
        list( $align, $justify ) = explode(' ', $position);
        if ($align == 'top') $align = 'start';
        if ($align == 'bottom') $align = 'end';
        // padding
        $padding = isset($atts['layout']['padding']) ? array(
            isset($atts['layout']['padding']['top']) && strpos($atts['layout']['padding']['top'], 'null') === false ? $atts['layout']['padding']['top'] : '',
            isset($atts['layout']['padding']['right']) && strpos($atts['layout']['padding']['right'], 'null') === false ? $atts['layout']['padding']['right'] : '',
            isset($atts['layout']['padding']['bottom']) && strpos($atts['layout']['padding']['bottom'], 'null') === false ? $atts['layout']['padding']['bottom'] : '',
            isset($atts['layout']['padding']['left']) && strpos($atts['layout']['padding']['left'], 'null') === false ? $atts['layout']['padding']['left'] : ''
        ) : array();
        $padding = array_map( function($side) { return \greyd\blocks\helper::get_spacing_preset_css_var( $side ); }, array_filter($padding) );
        // margin
        $margin = isset($atts['layout']['margin']) ? array(
            isset($atts['layout']['margin']['top']) && strpos($atts['layout']['margin']['top'], 'null') === false ? $atts['layout']['margin']['top'] : '',
            isset($atts['layout']['margin']['right']) && strpos($atts['layout']['margin']['right'], 'null') === false ? $atts['layout']['margin']['right'] : '',
            isset($atts['layout']['margin']['bottom']) && strpos($atts['layout']['margin']['bottom'], 'null') === false ? $atts['layout']['margin']['bottom'] : '',
            isset($atts['layout']['margin']['left']) && strpos($atts['layout']['margin']['left'], 'null') === false ? $atts['layout']['margin']['left'] : ''
        ) : array();
        $margin = array_map( function($side) { return \greyd\blocks\helper::get_spacing_preset_css_var( $side ); }, array_filter($margin) );
        // size
        $width = isset($atts['layout']['size']['width']) ? $atts['layout']['size']['width'] : '';
        if (strpos($width, 'px') === false && strpos($width, '%') === false && $width !== 'auto') {
            if (intval($width) <= 100) $width .= "%";
            else $width .= "px";
        } 
        $height = isset($atts['layout']['size']['height']) ? $atts['layout']['size']['height'] : '';
        if (strpos($height, 'px') === false && strpos($height, '%') === false && $height !== 'auto') {
            if (intval($height) <= 100) $height .= "%";
            else $height .= "px";
        } 
        $inner_align = isset($atts['layout']['size']['align']) ? $atts['layout']['size']['align'] : '';

        $inner_width      = (!empty($margin) && isset($margin[3])) ? "calc(100%  - ".$margin[3]." - ".$margin[1].")" : '';
        $inner_height     = (!empty($margin) && isset($margin[2])) ? "calc(100%  - ".$margin[0]." - ".$margin[2].")" : '';
        $inner_width_max  = (!empty($margin) && isset($margin[3])) ? "calc(100vw - ".$margin[3]." - ".$margin[1].")" : '';
        $inner_height_max = (!empty($margin) && isset($margin[2])) ? "calc(100vh - ".$margin[0]." - ".$margin[2].")" : '';

        // make layout css
        $style =
            "#".$id." { ".
                ($justify != '' ? "justify-content: ".$justify.";" : "").
                ($align != '' ? "align-items: ".$align.";" : "").
                (isset($atts['transition_prop']) ? "will-change: ".$atts['transition_prop'].";" : "").
                (isset($atts['transition_prop']) ? "transition-property: ".$atts['transition_prop'].";" : "").
                (isset($atts['transition_time']) ? "transition-duration: ".$atts['transition_time'].";" : "")."
            }
            #".$id." .popup_content { ".
                ($width != '' ? "width: ".$width.";" : "").
                ($height != '' ? "height: ".$height.";" : "")."
            }
            #".$id." .popup_content .popup_wrapper { ".
                // ($inner_align != '' ? "justify-content: ".$inner_align.";" : "").
                ($inner_width != '' ? "width: ".$inner_width.";" : "").
                ($inner_height != '' ? "height: ".$inner_height.";" : "").
                ($inner_width_max != '' ? "max-width: ".$inner_width_max.";" : "").
                ($inner_height_max != '' ? "max-height: ".$inner_height_max.";" : "").
                (!empty($margin) ? "margin: ".implode(' ', $margin).";" : "").
                (!empty($padding) ? "padding: ".implode(' ', $padding).";" : "").
                (isset($atts['overflow']) ? "overflow: ".$atts['overflow'].";" : "").
                (isset($atts['transition_time']) ? "transition-duration: ".$atts['transition_time'].";" : "")."
            } ";
        if ( $inner_align == 'center' || $inner_align == 'end' ) {
            $style .= "#".$id." .popup_content .popup_wrapper > :first-child { margin-top: auto !important; } ";
        }
        if ( $inner_align == 'center' ) {
            $style .= "#".$id." .popup_content .popup_wrapper > :last-child { margin-bottom: auto !important; } ";
        }

        if ($mode == 'mobile') $style = "@media (max-width: 992px) { ".$style." } ";
        return $style;
    }

    /**
     * Render design css from attributes. (v2)
     * 
     * @param string $id    Popup css ID.
     * @param object $atts  The layout attributes.
     * @param string $mode  Empty or 'hover'.
     * @return string style     Rendered inline css style.
     */
    public function make_design_css($id, $atts, $mode='') {

        // colors
        $color = isset($atts['colors']['text']) ? $atts['colors']['text'] : '';
        $head = isset($atts['colors']['headline']) ? $atts['colors']['headline'] : '';
        $background = isset($atts['colors']['background']) ? $atts['colors']['background'] : '';
        // border radius
        $border_radius = isset($atts['border_radius']) ? array(
            isset($atts['border_radius']['topLeft']) ? $atts['border_radius']['topLeft'] : $atts['border_radius'],
            isset($atts['border_radius']['topRight']) ? $atts['border_radius']['topRight'] : $atts['border_radius'],
            isset($atts['border_radius']['bottomRight']) ? $atts['border_radius']['bottomRight'] : $atts['border_radius'],
            isset($atts['border_radius']['bottomLeft']) ? $atts['border_radius']['bottomLeft'] : $atts['border_radius']
        ) : array();
        // border
        if (isset($atts['border']) && !isset($atts['border']['top'])) {
            $b_width = isset($atts['border']['width']) ? $atts['border']['width'] : "0px";
            $b_style = isset($atts['border']['style']) ? $atts['border']['style'] : "solid";
            $b_color = isset($atts['border']['color']) ? $atts['border']['color'] : "transparent";
            $atts['border']['top'] = array( 'width' => $b_width, 'style' => $b_style, 'color' => $b_color );
            $atts['border']['right'] = array( 'width' => $b_width, 'style' => $b_style, 'color' => $b_color );
            $atts['border']['bottom'] = array( 'width' => $b_width, 'style' => $b_style, 'color' => $b_color );
            $atts['border']['left'] = array( 'width' => $b_width, 'style' => $b_style, 'color' => $b_color );
        }
        $border = isset($atts['border']) ? array(
            'top' => array(
                isset($atts['border']['top']['width']) ? $atts['border']['top']['width'] : "0px",
                isset($atts['border']['top']['style']) ? $atts['border']['top']['style'] : "solid",
                isset($atts['border']['top']['color']) ? $atts['border']['top']['color'] : "transparent"
            ),
            'right' => array(
                isset($atts['border']['right']['width']) ? $atts['border']['right']['width'] : "0px",
                isset($atts['border']['right']['style']) ? $atts['border']['right']['style'] : "solid",
                isset($atts['border']['right']['color']) ? $atts['border']['right']['color'] : "transparent"
            ),
            'bottom' => array(
                isset($atts['border']['bottom']['width']) ? $atts['border']['bottom']['width'] : "0px",
                isset($atts['border']['bottom']['style']) ? $atts['border']['bottom']['style'] : "solid",
                isset($atts['border']['bottom']['color']) ? $atts['border']['bottom']['color'] : "transparent"
            ),
            'left' => array(
                isset($atts['border']['left']['width']) ? $atts['border']['left']['width'] : "0px",
                isset($atts['border']['left']['style']) ? $atts['border']['left']['style'] : "solid",
                isset($atts['border']['left']['color']) ? $atts['border']['left']['color'] : "transparent"
            ),
        ) : array();
        // shadow
        $shadow = isset($atts['shadow']) ? $atts['shadow'] : '';

        // make design css
        $selector = $mode == 'hover' ? ':hover' : '';
        return
            "#".$id." .popup_content".$selector." .popup_wrapper { ".
				/**
				 * Set var(--text-color) to use for outline color
				 * @since 2.2.0
				 */
                ($color != '' ? "color: ".$color."; --text-color: ".$color.";" : "").
                ($background != '' ? "background: ".$background.";" : "background: none;").
                (!empty($border_radius) ? "border-radius: ".implode(' ', $border_radius).";" : "").
                (!empty($border) ? "border-top: ".implode(' ', $border['top']).";" : "").
                (!empty($border) ? "border-right: ".implode(' ', $border['right']).";" : "").
                (!empty($border) ? "border-bottom: ".implode(' ', $border['bottom']).";" : "").
                (!empty($border) ? "border-left: ".implode(' ', $border['left']).";" : "").
                ($shadow != '' ? "box-shadow: ".$shadow.";" : "")."
            } ".
			($head != '' ? ":where(".
				"#".$id." .popup_content".$selector." h1,".
				"#".$id." .popup_content".$selector." h2,".
				"#".$id." .popup_content".$selector." h3,".
				"#".$id." .popup_content".$selector." h4,".
				"#".$id." .popup_content".$selector." h5,".
				"#".$id." .popup_content".$selector." h6
			) { 
				color: ".$head.";
			} " : "");

    }

}