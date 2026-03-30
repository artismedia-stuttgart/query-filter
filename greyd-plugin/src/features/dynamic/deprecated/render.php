<?php
/**
 * Script to render Dynamic Features.
 * Enqueueing and Shortcode rendering is kind of deprecated,
 * but still needed for rendering of (old) nested Templates.
 */
namespace Greyd\Dynamic;

// depends on Theme vc\helper
// use vc\helper as Theme_Helper;

use Greyd\Helper as Helper;

if( ! defined( 'ABSPATH' ) ) exit;

new Render_Deprecated();
class Render_Deprecated {

    
    /**
     * Class constructor.
     */
    public function __construct() {

		// frontend
		add_shortcode( 'dynamic', array( $this, 'add_dynamic' ) );
		add_filter( 'template_include', array( $this, 'handle_dynamic' ) );
		add_action( 'dynamic_hook', array( $this, 'build_dynamic' ) );

        if (!is_admin()) return;
        
        // vc (deprecated)
        add_action( 'vc_before_init', array($this, 'vc_map_dynamic') );
        // add vc roles
        add_filter( 'greyd_add_vc_posttypes', array($this, 'add_vc_roles') );

    }

	/*
	====================================================================================
		Frontend
	====================================================================================
	*/

	// shortcode
	public static function add_dynamic( $atts = array(), $content = '' ) {
		return \Greyd\Dynamic\Render::add_dynamic( $atts, $content );
	}

	// system templates
	public function handle_dynamic( $template ) {
		// debug("hooking ...");
		// debug($template);
		// disable for certain custom post types
		$disable = array( 'rbx', 'form_gen' );
		$disable = apply_filters( 'tp_dynamic_exclude_post_type', $disable );
		if ( in_array( get_post_type(), $disable ) ) {
			return $template;
		}
		// enable dynamic template
		if ( is_archive() || is_category() || is_single() || is_search() || is_404() ) {
			if ( strpos( $template, 'index.php' ) || ! file_exists( $template ) ) {
				$template = plugin_dir_path( __FILE__ ) . '/template.php';
			}
		}
		$template = apply_filters( 'tp_dynamic_edit_template', $template );
		return $template;
	}

	public function build_dynamic() {
		$atts['template'] = \Greyd\Dynamic\Render::get_dynamic_name();
		echo self::add_dynamic( $atts );
	}

	/*
	====================================================================================
		Helper
	====================================================================================
	*/
    
    /*
    ====================================================================================
        Visual Composer (deprecated)
    ====================================================================================
    */

    public function add_vc_roles($rules=[]) {
        return array_merge( $rules, [ "dynamic_template" ] );
    }

    public function vc_map_dynamic() {

        if (!is_callable('vc_map') || !is_callable('vc_add_param')) return;
        if (!method_exists('\vc\helper', 'get_vc_templates') || !method_exists('\vc\helper', 'trigger_action_params_vc')) return;

        vc_map(array(
            "name"          => __('Template', 'core'),
            "description"   => __("Insert a Dynamic Template", 'core'),
            "base"          => "dynamic",
            "category"      => __("Content", 'core'),
            "icon"          => get_template_directory_uri()."/assets/icon/template.svg",
            'js_view'       => 'VcTemplateView',
            "params"        => array_merge(array(
                array(
                    "type"          => "seperator",
                    "heading"       => __('Template', 'core'),
                    "param_name"    => "seperator_basic",
                ),
                array(
                    "type"          => "select",
                    "heading"       => __('Template', 'core'),
                    "description"   => __('Welches Template soll eingefügt werden? Templates kannst du links unter "Templates" erstellen.', 'core'),
                    "param_name"    => "template",
                    'holder'        => 'div',
                    'options'         => \vc\helper::get_vc_templates((isset($_POST['post_id']) ? $_POST['post_id'] : false)),
                    "edit_field_class" => 'vc_col-xs-6',
                ),
                array(
                    'type'          => 'checkbox',
                    'heading'       => __('Seitliches Gutter', 'core'),
                    "description"   => __('Durch diese Option kannst du das Template seitlich bündig darstellen.', 'core'),
                    'param_name'    => 'options',
                    'value' => array(
                        // _x('erste Zeile auf volle Breite setzen', 'small', 'core') => 'ignore_row',
                        _x('ignorieren (empfohlen)', 'small', 'core') => 'ignore_gutter',
                    ),
                    'edit_field_class' => 'vc_col-xs-6',
                ),
                
                array(
                    "type"          => "seperator",
                    "heading"       => __("Content", 'core'),
                    "param_name"    => "seperator_content",
                ),
                // dynamic dynamic content
                array(
                    'type'          => 'dynamic',
                    'param_name'    => 'dynamic_dynamic_content',
                    'heading'       => __( 'Einbindung', 'core' ),
                    'value'         => 'static',
                    'params'        => array(
                        'type' => 'dynamic_content',
                        'field' => 'dynamic_content',
                        'static' => _x( 'Inhalte eingeben', 'small', 'core'),
                        'dynamic' => _x( "leave content dynamic", 'small', 'core' ),
                    ),
                ),
                // dynamic content
                array(
                    'type'          => 'dynamic_content',
                    'param_name'    => 'dynamic_content',
                    'params'        => array(
                        'field' => 'template',
                    ),
                    'edit_field_class' => 'vc_col-xs-12',
                ),
                array(
                    'type'          => 'textarea',
                    'heading'       => __('Backend-Beschreibung', 'core'),
                    'description'   => __('Die Beschreibung dient der Übersichtlichkeit im Backend Editor.', 'core'),
                    'param_name'    => 'description',
                    'holder'        => 'div',
                    'group'         => __('Info', 'core'),
                ), 
            ),
            \vc\helper::trigger_action_params_vc()
            )
        ));
    }

    /*
    ====================================================================================
        Enqueue
    ====================================================================================
    */

    // resolve all content from templates
    public static function get_enqueue_content() {
        $content = "";

        $template = \Greyd\Dynamic\Render::get_dynamic_name();
        if ($template != "") {
            $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post( $template );
            if ( is_object($template) && isset($template->post_content) ) {
                $content = $template->post_content;
			}
        }
        // single & archive pages
        if (is_page() || is_single()) { 
            global $post;
            if ($post != "" && isset($post->post_content))
                $content .= $post->post_content;
        }
        // footer
        if (get_theme_mod('navi_footer_mode', 'on') != 'none' && get_theme_mod('navi_footer_content', '') != '') {
            $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post('footer');
            if ( is_object($template) && isset($template->post_content) ) {
                $content = $template->post_content;
			}
        }
        // header
        if (get_theme_mod('navi_header_mode', 'on')  != 'none' && get_theme_mod('navi_header_off_content', '') != '') {
            $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post('main-menu-offcanvas');
            if ( is_object($template) && isset($template->post_content) ) {
                $content = $template->post_content;
			}
        }
        // header sec
        if (get_theme_mod('navi_header_sec_mode', 'none') != 'none' && get_theme_mod('navi_header_sec_off_content', '') != '') {
            $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post('meta-menu-offcanvas');
            if ( is_object($template) && isset($template->post_content) ) {
                $content = $template->post_content;
			}
        }

        return $content;
    }

    // check content for enqueueing
    public static function check_enqueue_template($content, $sc) {
        $found = false;
        // check shorcode list
        if (!is_array($sc)) $sc = [$sc];
        foreach ($sc as $s) {
            if (has_shortcode($content, esc_attr($s))) {
                // debug('found '.$s);
                return true;
            }
        }
        // check templates
        if (!$found) {
            $shortcodes = false;
            if (method_exists('\vc\helper', 'get_shortcodes'))
                $shortcodes = \vc\helper::get_shortcodes($content, array( 'dynamic' ));
            if ($shortcodes) {
                // debug($shortcodes);
                foreach ($shortcodes as $shortcode) {
                    // debug($shortcode);
                    if ($shortcode['atts'] != "") {
                        foreach ($shortcode['atts'] as $att) {
                            // debug($att['key'].' = '.$att['value']);
                            if ($att['key'] == 'template') {
                                $slug = $att['value'];
                                $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post($slug);
								if ( is_object($template) && isset($template->post_content) ) {
                                    $found = self::check_enqueue_template($template->post_content, $sc);
                                    if ($found) return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $found;
    }

    /*
    ====================================================================================
        Frontend
    ====================================================================================
    */
    
    public static function process_shortcode_content($atts, $template) {
        // dynamic content
        $content = $template->post_content;
        // debug($atts);
        $dynamic_content = isset($atts['dynamic_content']) ? esc_attr($atts['dynamic_content']) : '';
        $dynamic_values = explode('::', $dynamic_content);
        if ($dynamic_values[0] == $atts['template'] && count($dynamic_values) > 1) {
            $content = self::content_match_dynamic($dynamic_values, $content);
            // debug($content);
        }
        // rows in templates
        $term_obj = $template && is_object($template) ? get_the_terms($template->ID, 'template_type') : null;

        if (
			$term_obj
			&& !is_wp_error($term_obj)
			&& isset($term_obj[0])
			&& $term_obj[0]->slug == 'dynamic'
		) {
            $shortcodes = false;
            if (method_exists('\vc\helper', 'get_shortcodes')) {
                $shortcodes = \vc\helper::get_shortcodes($content, array( 'vc_row' ));
            }

            if ($shortcodes) {
                // debug($shortcodes);
                for ($j=0; $j<count($shortcodes); $j++) {
                    // debug($shortcodes[$j]['atts']);
                    $found = false;
                    if ($shortcodes[$j]['atts'] != "") {
                        foreach ($shortcodes[$j]['atts'] as $att) {
                            if ($att['key'] == 'row_type') {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if (!$found) {
                        // debug($shortcodes[$j]['raw']);
                        $raw = explode('][', $shortcodes[$j]['raw']);
                        $old = $raw[0];
                        $raw[0] = str_replace('[vc_row', '[vc_row row_type="'.get_theme_mod('grid_row_template_default', 'row_xxl').'" ', $old);
                        $raw = implode('][', $raw);
                        $content = str_replace($shortcodes[$j]['raw'], $raw, $content);
                    }
                }
            }
        }
        // dynamic tags
        if (is_archive() || is_search()) {
            global $wp_query;
            // debug($wp_query);
            $tags = Dynamic_Helper::get_dynamic_tags('archive');
            // debug(array_keys($tags));
            $content = self::content_match_tags($wp_query, $tags, $content);
        }
        else if (is_single()) {
            $post = get_post(get_the_ID());
            // debug($post);
            // dynamic posttype tags
            $dtags = Dynamic_Helper::get_dynamic_posttype_tags($post);
            // debug($dtags);
            // standard tags
            $tags = Dynamic_Helper::get_dynamic_tags('single');
            // debug(array_keys($tags));
            $content = self::content_match_tags($post, $dtags, $content);
            $content = self::content_match_tags($post, $tags, $content);
        }

        // fix css
        $styles = "";
		if ( class_exists('\vc\helper') ) {
			Helper::add_custom_style(\vc\helper::get_vc_custom_css($content));
		}

        // render shortcode
        $content = shortcode_unautop(preg_replace('/<\/?p\>/', "\n", $content));
        $content = do_shortcode($content).$styles;

        // wrapper
        $options = isset($atts['options']) ? esc_attr($atts['options']) : '';
        $options = explode( ',', $options );
        $classes = array();
        $trigger_events = self::make_trigger_action($atts);
        // if (in_array('ignore_row', $options)) $classes[] = 'ignore_row';
        if (in_array('ignore_gutter', $options)) $classes[] = 'ignore_gutter';
        $content = "<div class='dynamic ".implode(' ', $classes)."' $trigger_events>".$content."</div>";

        return $content;
    }
        
	/**
	 * Render trigger event params
	 * 
	 * @since 0.8.6
	 * 
	 * @param array  $atts  All shortcode attributes.
	 * @param string $pre   Prefix before the trigger attributes.
	 * 
	 * @return string       HTML attributes to append to a wrapper.
	 */
	public static function make_trigger_action($atts, $pre="") {

		if (!is_array($atts) || !isset($atts[$pre.'trigger_enable']) || $atts[$pre.'trigger_enable'] !== 'enable') {
			return "";
		}
		$html = "";

		$options = isset($atts[$pre.'trigger_options']) ? \vc_param_group_parse_atts($atts[$pre.'trigger_options']) : array();
		if ($options && count($options)) {
			$triggers = array();
			foreach($options as $option) {
				$name   = isset($option['name']) ? helper::get_clean_slug($option['name']) : null;
				$action = isset($option['action']) ? esc_attr($option['action']) : null;
				if ( !empty($name) && !empty($action) ) {
					$triggers[] = $name."|".$action;
				}
			}
			if (count($triggers)) {
				$html .= "data-ontrigger='".implode("::", $triggers)."'";
			}

			$onload     = isset($atts[$pre.'trigger_onload']) ? esc_attr($atts[$pre.'trigger_onload']) : "show";
			$html      .= !empty($onload) && $onload !== "show" ? " data-onload='$onload'" : "";

			$siblings   = isset($atts[$pre.'trigger_siblings']) ? esc_attr($atts[$pre.'trigger_siblings']) : "";
			$html      .= $siblings === "hide" ? " data-siblings='$siblings'" : "";
		}

		return $html;
	}

    public static function content_match_dynamic($dynamic_values, $content) {
        $shortcodes = false;
        if (method_exists('\vc\helper', 'get_dynamic_shortcodes'))
            $shortcodes = \vc\helper::get_dynamic_shortcodes($content, array( 'dynamic' ));
        if ($shortcodes) {
            // debug($shortcodes);
            // set dynamic_content atts for nested templates
            for ($j=0; $j<count($shortcodes); $j++) {
                if ($shortcodes[$j]['shortcode'] == 'dynamic' && is_array($shortcodes[$j]['atts'])) {
                    // debug($shortcodes[$j]);

                    foreach ((array)$shortcodes[$j]['atts'] as $att) {

                        // only handle dynamic content
                        if ($att['key'] != 'dynamic_dynamic_content') continue;

                        // get the arguments
                        $args = explode('|', $att['value']);
                        if ( count($args) < 3 ) break;

                        // get the template
                        list( $field, $type, $slug ) = $args;
                        $template = \Greyd\Dynamic\Dynamic_Helper::get_template_post($slug);
                        if ( !$template) break;

                        // vars
                        $value      = $dynamic_values;
                        $value[0]   = $slug;
                        $value      = implode('::', $value);
                        $old        = $shortcodes[$j]['raw'];
                        $found      = false;

                        // replace tags
                        foreach ( (array)$shortcodes[$j]['atts'] as $att) {
                            if ($att['key'] == $field) {
                                $found = true;
                                $new = str_replace($att['value'], $value, $old);
                                break;
                            }
                        }
                        if (!$found) {
                            $new = str_replace('['.$shortcodes[$j]['shortcode'].' ', '['.$shortcodes[$j]['shortcode'].' '.$field.'="'.$value.'" ', $old);
                        }
                        $content = str_replace($old, $new, $content);
                        $shortcodes[$j]['raw'] = $new;

                        break;
                    }
                }
            }

            // set dynamic_content values for this template
            for ($i=1; $i<count($dynamic_values); $i++) {

                $args   = explode('|', $dynamic_values[$i]);
                $field  = $args[0];
                $type   = $args[1];
                $title  = $args[2];
                $value  = rawurldecode($args[3]);
                $key    = $field.'|'.$type.'|'.$title;

                if ($value != "") {
                    for ($j=0; $j<count($shortcodes); $j++) {
                        if ($shortcodes[$j]['shortcode'] == 'dynamic') continue;
                        // debug($shortcodes[$j]);

                        if ($shortcodes[$j]['atts'] != "" && is_array($shortcodes[$j]['atts'])) {
                            foreach ($shortcodes[$j]['atts'] as $att) {
                                if ($att['value'] == $key) {
                                    // debug($shortcodes[$j]);

                                    $raw    = explode('][', $shortcodes[$j]['raw']);
                                    $found  = false;
                                    // $old = $raw[0];

                                    foreach ( (array)$shortcodes[$j]['atts'] as $att) {
                                        if ($att['key'] == $field) {
                                            $found  = true;
                                            $old    = $raw[0];
                                            $new    = str_replace($att['value'], $value, $old);
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        if ($field == 'content') {
                                            if (isset($shortcodes[$j]['content'])) {
                                                $old = $raw[0];
                                                $new = str_replace($shortcodes[$j]['content'], $value, $old);
                                            }
                                            else {
                                                $raw = array( implode('][', $raw) );
                                                $old = $raw[0];
                                                $new = str_replace('][', ']'.$value.'[', $old);
                                                $shortcodes[$j]['content'] = $value;
                                            }
                                        }
                                        else {
                                            $old = $raw[0];
                                            $val = $value;
                                            if ($type == 'textarea') $val = str_replace(array('"', "''"), '``', $val);
                                            $new = str_replace('['.$shortcodes[$j]['shortcode'].' ', '['.$shortcodes[$j]['shortcode'].' '.$field.'="'.$val.'" ', $old);
                                        }
                                    }
                                    $content    = str_replace($old, $new, $content);
                                    $raw[0]     = $new;
                                    $shortcodes[$j]['raw'] = implode('][', $raw);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }

    public static function content_match_tags($post, $tags, $content) {
        $shortcodes = false;
        if (method_exists('\vc\helper', 'get_dynamic_shortcodes'))
            $shortcodes = \vc\helper::get_dynamic_shortcodes($content, array( 'dynamic' ));
        if ($shortcodes) {
            // debug($shortcodes);
            for ($i=0; $i<count($shortcodes); $i++) {
                // debug($shortcodes[$i]);
                if ($shortcodes[$i]['shortcode'] == 'dynamic') continue;
                if ($shortcodes[$i]['atts'] != "") {
                    foreach ( (array)$shortcodes[$i]['atts'] as $att) {
                        $oldval = $att['value'];
                        $newval = \Greyd\Dynamic\Render::match_tags($post, $tags, $oldval);
                        if ($oldval != $newval) {
                            $raw        = explode('][', $shortcodes[$i]['raw']);
                            $old        = $raw[0];
                            $new        = str_replace($att['key'].'="'.$oldval.'"', $att['key'].'="'.$newval.'"', $old);
                            $content    = str_replace($old, $new, $content);
                            $raw[0]     = $new;
                            $shortcodes[$i]['raw'] = implode('][', $raw);
                            $att['value'] = $newval;
                        }
                    }
                }
                if (isset($shortcodes[$i]['content'])) {
                    $oldval = $shortcodes[$i]['content'];
                    $newval = \Greyd\Dynamic\Render::match_tags($post, $tags, $oldval);
                    if ($oldval != $newval) {
                        $raw        = explode('][', $shortcodes[$i]['raw']);
                        $old        = $raw[0];
                        $new        = str_replace(']'.$oldval.'[', ']'.$newval.'[', $old);
                        $content    = str_replace($old, $new, $content);
                        $raw[0]     = $new;
                        $shortcodes[$i]['raw'] = implode('][', $raw);
                        $shortcodes[$i]['content'] = $newval;
                    }
                }
            }
        }
        return $content;
    }

	/**
	 * Get currently enqueued styles.
	 * 
	 * @see Helper::render_custom_styles()
	 * 
	 * @return string eg. '<style> .gs_12345 { margin-bottom: 12px } </style>'
	 */
	public static function get_style() {
		$style = "";
		if (strpos($_SERVER['REQUEST_URI'], "/wp-json/wp/v2/block-renderer") === 0) {
			if (Helper::has_custom_styles()) {
				ob_start();
				Helper::render_custom_styles();
				$style = ob_get_contents();
				ob_end_clean();
			}
		}
		return $style;
	}

}