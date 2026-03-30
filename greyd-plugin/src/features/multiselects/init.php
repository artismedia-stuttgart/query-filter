<?php
/*
Feature Name:   Multiselects
Description:    Multiselects for the block editor
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Forced:         true
*/
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) exit;

new Multiselects( $config );
class Multiselects {

	private $config;

	public function __construct($config) {

		// set config
		$this->config = (object) $config;

		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 99 );
	}

	/**
	 * Render a multiselect dropdown
	 * 
	 * @param string $name      name of the input
	 * @param array  $options   all options as $value => $label
	 * @param array  $args      optional arguments for the element (value, placeholder, classes...)
	 * 
	 * @return string html element
	 */
	public static function render($name, $options, $args=[]) {

		/**
		 * Filter the options for the multiselect dropdown
		 * @since 2.11.0
		 * 
		 * @param array  $options   all options as $value => $label
		 * @param string $name      name of the input
		 * @param array  $args      optional arguments for the element (value, placeholder, classes...)
		 * 
		 * @return array $options   all options as $value => $label
		 */
		$options = apply_filters( 'greyd_multiselect_options', $options, $name, $args );

		/**
		 * Filter the arguments for the multiselect dropdown
		 * @since 2.11.0
		 * 
		 * @param array  $args      optional arguments for the element (value, placeholder, classes...)
		 * @param string $name      name of the input
		 * @param array  $options   all options as $value => $label
		 * 
		 * @return array $args      optional arguments for the element (value, placeholder, classes...)
		 */
		$args = apply_filters( 'greyd_multiselect_args', $args, $name, $options );

		if ( !is_array( $options ) ) return;
		
		// make args
		$default_args = [
			'value'         => '',
			'id'            => '',
			'class'         => '',
			'placeholder'   => '',
			'required'      => false,
			'style'         => '',
			'width'         => '',
			'title'         => '',
			'atts'          => []
		];
		$args = array_merge( $default_args, (array) $args );
		
		// atts
		$value      = $args['value'];
		$values     = explode(',', $args['value']);
		$id         = !empty($args['id']) ? "id='".$args['id']."'" : "";
		$class      = $args['class'];
		$ph         = !empty($args['placeholder']) ? $args['placeholder'] : __("Please select", 'greyd_hub');
		$required   = $args['required'] ? "required" : "";
		$style      = !empty($args['style']) ? "style='".$args['style']."'" : "";
		$width      = !empty($args['width']) ? "style='width: ".$args['width'].";'" : "";
		$title      = !empty($args['title']) ? "title='".$args['title']."'" : "";
		$attributes = !empty($args['atts']) ? implode($args['atts']) : "";
		$tag        = "<span class=\"tag\" data-value=\"{value}\" onclick=\"greyd.input.multiselects.removeValue(this, event);\">{name}<span class=\"icon_close\"></span></span>";
		
		ob_start();
		
		// wrapper
		echo "<div $id class='greyd_multiselect' $width>";
		
		// hidden input
		echo "<input type='text' name='$name' value='$value' $required autocomplete='off' $title>";
		
		// input
		echo "<div class='input $class' data-tag='".rawurlencode($tag)."' $style $attributes $title>
			<span class='tags' data-placeholder='$ph'>";
				foreach( $values as $val ) {
					if ( empty($val) ) continue;
					$label = isset($options[$val]) && !empty($options[$val]) ? $options[$val] : $val;
					echo str_replace( [ "{value}", "{name}" ], [ $val , $label ], $tag );
				}
			echo "</span>
		</div>";
		
		// dropdown
		echo "<div class='dropdown'>";
			if ( count($options) > 0 ) {
				/**
				 * Render the Options recursively, to enable hierarchical view in multiselects
				 * @since 2.2.0
				 */
				self::render_options( $options, $values );
			}
			else {
				echo "<div class='option disabled'>".__("No options available", 'greyd_hub')."</div>";
			}
		echo "</div>";
		
		// wrapper
		echo "</div>";
		
		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}
	
	/**
	 * Render Options recursively.
	 * If an Option has children, the Options get nested. (aka hierarchical view)
	 * This can be achieved with the 'greyd_search_filter_terms' filter.
	 * @since 2.11.0
	 * 
	 * @param array $options    The options.
	 * @param bool $values      The selected values.
	 */
	public static function render_options( $options, $values ) {

		foreach( $options as $value => $label ) {
			if ( is_array($label) ) {
				echo "<div class='option ".( isset( array_flip($values)[$value] ) ? "selected" : "" )."' data-value='".trim($value)."'><span class='icn'></span>".trim($label['name'])."</div>";
				if ( isset( $label['children'] ) && !empty( $label['children'] ) ) {
					echo "<div class='children'>";
					self::render_options( $label['children'], $values );
					echo "</div>";
				}
				continue;
			}
			echo "<div class='option ".( isset( array_flip($values)[$value] ) ? "selected" : "" )."' data-value='".trim($value)."'><span class='icn'></span>".trim($label)."</div>";
		}

	}


	/**
	 * Enqueue assets.
	 */
	public function enqueue_frontend_assets() {

		// early exit if already loaded
		if ( wp_style_is( 'greyd-multiselects-style', 'enqueued' ) ) return;

		// enqueue stylesheet
		if ( function_exists( '\wp_process_style' ) ) {
			wp_process_style(
				'default_style',
				plugin_dir_path(__FILE__).'deprecated/classic.css'
			);
		}
		else {
			wp_enqueue_style(
				'greyd-multiselects-style',
				plugin_dir_url(__FILE__).'style.css',
				array( 'elegant-icons' ),
				GREYD_VERSION
			);
		}

		// enqueue script
		wp_enqueue_script(
			'greyd-multiselects-script',
			plugin_dir_url(__FILE__).'public.js',
			array( 'jquery' ),
			GREYD_VERSION,
			array(
				'strategy' => 'defer',
				'in_footer' => true
			)
		);
	}
}