<?php
/**
 * Helper & Utils.
 */
namespace greyd\blocks;

if ( !defined( 'ABSPATH' ) ) exit;

new helper();
class helper {

	/**
	 * Generate a unique custom css class (eg. "gs_jsI73A")
	 * 
	 * @return string className
	 */
	public static function generate_greydClass($length = 6) {
		$result = "gs_";
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for ($i = 0; $i < $length; $i++) {
			$result .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $result;
	}

	/**
	 * Compose array of styles into a clean CSS.
	 * 
	 * @param array $styleArray         All default, hover & responsive styles.
	 * @param string $parentSelector    Parent css class or ID, usually a greydClass.
	 * @param array $atts               Additional attributes (deprecated: @param bool $important)
	 *   @property string $path_hover      Path to the hover selector.
	 *   @property string $pseudo_hover    Pseudo class for the hover state.
	 *   @property string $path_active     Path to the active selector.
	 *   @property string $pseudo_active   Pseudo class for the active state.
	 * 
	 * @param bool $editor              Whether to render inside the editor.
	 * @return string
	 */
	public static function compose_css( $styleArray, $parentSelector, $atts=array() ) {
		if ( !is_array($styleArray) || empty($styleArray) ) {
			return "";
		}

		// deprecated
		if ( !is_array($atts) ) {
			$atts = array('important' => $atts);
		}

		$atts = array_merge( array(
			'important' => false,
			"path_hover" => "",
			'pseudo_hover' => ':hover',
			"path_active" => "",
			'pseudo_active' => '.active',
		), $atts );

		// vars
		$parentSelector     = $parentSelector ?? "";
		$finalCSS           = "";
		$finalStyles        = array( "default" => "", "lg" => "", "md" => "", "sm" => "" );

		// loop through all selectors
		foreach ( $styleArray as $selector => $attributes ) {

			if ( empty($attributes) || !is_array($attributes) ) continue;

			// collect all styles for this selector
			$selectorStyles = array( "default" => "", "hover" => "", "lg" => "", "md" => "", "sm" => "", "active" => "" );

			// loop through all attributes
			foreach ( $attributes as $name => $value ) {

				if ( $name === "hover" ) {
					$selectorStyles["hover"] .= self::get_css_line( "", $value, $atts['important'] );
				}
				else if ( $name === "active" ) {
					$selectorStyles["active"] .= self::get_css_line( "", $value, $atts['important'] );
				}
				else if ( $name === "responsive" ) {
					$selectorStyles["lg"] .= self::get_css_line( "", $value["lg"] ?? null, $atts['important'] );
					$selectorStyles["md"] .= self::get_css_line( "", $value["md"] ?? null, $atts['important'] );
					$selectorStyles["sm"] .= self::get_css_line( "", $value["sm"] ?? null, $atts['important'] );
				}
				else {
					$selectorStyles["default"] .= self::get_css_line( $name, $value, $atts['important'] );
				}
			}

			// split selector into pieces to enable mutliple selectors (eg. ".selector, .selector h3")
			$selector = explode(",", $selector);

			foreach( $selectorStyles as $type => $css ) {

				if ( empty($css) ) continue;

				$pseudo = "";
				$path   = "";
				if ( $type === "hover" || $type === "active" ) {
					$pseudo = isset( $atts[ "pseudo_".$type ] ) ? $atts[ "pseudo_".$type ] : "";
					$path   = isset( $atts[ "path_".$type ] ) ? $atts[ "path_".$type ] : "";
					$type = "default";
				}

				/**
				 * add pseudo & path to multiple parent selectors, eg.:
				 * $parentSelector = ".wrapper, .container"
				 * $pseudo         = ":hover"
				 * $path           = "> a"
				 * 
				 * eg. ".wrapper, .container" --> ".wrapper > a:hover, .container > a:hover"
				 */
				$parentSelectorWithPseudo = implode(
					$path.$pseudo.', ',
					explode(
						',',
						$parentSelector
					)
				) . $path.$pseudo;


				// eg. .wrapper .gs_123456.selector, .wrapper .gs_123456.selector h3 { color: #fff; }
				$finalSelector = $parentSelectorWithPseudo.implode(
					', '.$parentSelectorWithPseudo,
					$selector
				);
				// debug($finalSelector);

				$finalStyles[$type] .= $finalSelector.' { '.$css.'} ';
			}
		}

		/**
		 * Get Grid values with Layout function
		 * @since 1.4.4
		 */
		// if ( method_exists( '\Greyd\Layout\Enqueue', 'get_breakpoints' ) ) {
		// 	$grid = \Greyd\Layout\Enqueue::get_breakpoints();
		// } else {
		// 	$grid = \greyd\blocks\deprecated\Functions::get_breakpoints();
		// }
		$grid = \greyd\blocks\layout\Enqueue::get_breakpoints();

		foreach ( $finalStyles as $type => $css ) {

			if ( empty($css) ) continue;

			$prefix = "";
			$suffix = "";

			if ( $type !== "default" ) {
				$prefix = "@media (max-width: ".($grid[$type] - 0.02 )."px) { ";
				$suffix = "} ";
			}

			$finalCSS .= $prefix.$css.$suffix;
		}

		return $finalCSS;
	}

	/**
	 * Get CSS formated line.
	 * 
	 * @param string $name      Name of the css property (eg. "margin").
	 * @param mixed $value      Value for the css property (eg. "12px").
	 * @param bool $important   Whether the line should be made important.
	 * 
	 * @return string CSS line (eg. "margin: 12px; "). If @param $value is an object, multiple
	 *                lines are returned (eg. "margin-top: 12px; margin-bottom: 0px; ")
	 */
	public static function get_css_line( $name, $value, $important=false ) {

		/**
		 * @since 2.4.0 new SpacingPresetControl produces "0"-value without unit.
		 * By default, this would be considered as empty value and not rendered.
		 * This fix prevents this behavior.
		 */
		if ( empty($value) && $value !== "0" ) return "";

		$property	= self::kebabcase($name);
		$styles		= "";

		if ( !is_array($value) && !is_object($value) && !is_string($value) ) {
			/**
			 * Cast value to string
			 * @since headless feature
			 */
			$value = (string) $value;
		}

		if ( is_array($value) ) {

			/**
			 * Support for object-position
			 * @since 1.2.9
			 */
			if ( $property === 'object-position' ) {
				$styles = $property.': '.($value['x'] * 100).'% '.($value['y'] * 100).'%; ';
			}
			else {

				/**
				 * Support for font appearance.
				 * Value is saved as array, eg.: [ 'fontWeight' => 100, 'fontStyle' => 'italic' ]
				 * @since 1.3.3
				 */
				if ( $name === '__experimentalfontAppearance' ) {
					$property = "";
				}

				$property .= empty($property) ? "" : "-";

				foreach( $value as $side => $val ) {
					$styles .= self::get_css_line( $property.$side, $val, $important );
				}
			}
		}
		else if ( !empty($property) && is_string($value) && substr($value, 0, 4) !== "null" ) {

			/**
			 * Convert colors, gradients and box-shadows with Layout render helper
			 * @since 1.4.4
			 */
			$val = $value;
			if ( strpos( $property, "color" ) !== false || strpos( $property, "background" ) !== false || strpos( $property, "box-shadow" ) !== false ) {
				if ( class_exists("greyd\blocks\layout\Render") ) {
					if ( strpos( $property, "box-shadow" ) !== false ) {
						$val = \greyd\blocks\layout\Render::get_shadow( $value );
					}
					else {
						$val = \greyd\blocks\layout\Render::get_gradient($value);
						if ($val == $value) $val = \greyd\blocks\layout\Render::get_color($value);
					}
				}
				else if ( class_exists("Greyd\Layout\Render") ) {
					if ( strpos( $property, "box-shadow" ) !== false ) {
						$val = \Greyd\Layout\Render::get_shadow( $value );
					}
					else {
						$val = \Greyd\Layout\Render::get_gradient($value);
						if ($val == $value) $val = \Greyd\Layout\Render::get_color($value);
					}
				}
			}
			// if ( $property === "box-shadow" ) {
			// 	$val = \vc\helper::get_shadow( array("" => str_replace("color-", "color_", $value)), "" );
			// }
			// else if ( $property === "color" || $property === "background" ) {
			// 	$val = helper::get_gradient_new($value);
			// 	if ($val == $value) $val = helper::get_color($value);
			// }
			else {
				$val = trim(strval($value));
			}

			/**
			 * Convert URL encoded CSS variables
			 * @since 2.3.0
			 */
			if ( strpos($val, 'var(u002du002d') === 0 ) {
				$val = str_replace('var(u002du002d', 'var(--', $val);
			}
			/**
			 * Convert preset into cssVar 
			 * eg. 'var:preset|spacing|small' -> 'var(--wp--preset--spacing--small)'
			 * @since 2.3.0
			 */
			else if ( strpos($val, 'var:preset|') > -1 ) {
				$val = self::get_spacing_preset_css_var( $val );
			}

			/**
			 * Convert URL encoded CSS variables
			 * @since 2.3.0
			 */
			if ( strpos($name, 'u-002-du-002-d') === 0 ) {
				$name = str_replace('u-002-du-002-d', '--', $name);
			}

			// enable css variables
			if ( strlen($name) > 2 && substr($name, 0, 2) === '--' ) {
				$property = '--'.$property;
			}

			// Qickfix for leading '-' in line-clamp feature
			// Bug is in kebabCase function wich removes all leading '-'
			if ( strlen($name) > 8 && substr($name, 0, 8) === '-webkit-' ) {
				$property = '-'.$property;
			}

			/**
			 * @since 2.4.0 new SpacingPresetControl produces "0"-value without unit.
			 * By default, this would be considered as empty value and not rendered.
			 * This fix prevents this behavior.
			 */
			$value_is_empty = empty($val) && $val !== "0";

			if ( !$value_is_empty && !empty($property) ) {
				$styles .= $property.": ".$val.( $important ? " !important" : "" )."; ";

				/**
				 * Set var(--text-color) to use for outline color
				 * @since 2.2.0
				 */
				if ( $property === "color" ) {
					$styles .= "--text-color: ".$val."; ";
				}
			}
		}
		return $styles;
	}

	/**
	 * Convert preset into cssVar 
	 * eg. 'var:preset|spacing|small' -> 'var(--wp--preset--spacing--small)'
	 */
	public static function get_spacing_preset_css_var( $val, $default=false ) {
		if ( !$val && $default ) {
			$val = $default;
			$default = false;
		}
		if ( $val && strpos($val, 'var:preset|') > -1 ) {
			// preg replace 'var:preset|{type}|{slug}' with 'var(--wp--preset--{type}--{slug})'
			$val = preg_replace('/var:preset\|([a-z0-9-]+)\|([a-z0-9-]+)/', 'var(--wp--preset--$1--$2)', $val);
			if ( $default ) {
				$val = str_replace( ')', ', '.$default.')', $val );
			}
		}
		return $val;
	}

	/**
	 * Convert string into kebabcase
	 * @see https://stackoverflow.com/questions/1993721/how-to-convert-pascalcase-to-pascal-case
	 * 
	 * @param string $input  marginLeft
	 * @return string        margin-left
	 */
	public static function kebabcase( $input ) {
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
		  $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('-', $ret);
	}

	/**
	 * Convert string into snakcase
	 * 
	 * @param string $input  randomEventName
	 * @return string        random_event_name
	 */
	public static function snakecase( $input ) {
		preg_match_all('!([A-Z0-9]+(?=$|[A-Z0-9][a-z0-9])|[A-Za-z0-9][a-z0-9]*)!', $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	/**
	 * Check if the currently visited page is a block editor page.
	 * 
	 * @link https://wordpress.stackexchange.com/questions/309862/check-if-gutenberg-is-currently-in-use
	 */
	public static function is_gutenberg_page() {
		if ( function_exists( 'is_gutenberg_page' ) &&
			is_gutenberg_page()
		) {
			// The Gutenberg plugin is on.
			return true;
		}
		$current_screen = get_current_screen();
		if ( method_exists( $current_screen, 'is_block_editor' ) &&
			$current_screen->is_block_editor()
		) {
			// Gutenberg page on 5+.
			return true;
		}
		return false;
	}

	/**
	 * Implode an array of html-attributes keyed by name into a valid html string
	 * 
	 * @param array $atts    array( 'class' => array('link', 'button'), 'style' => 'margin-left: 10px;', 'download' => true, 'data' => array( 'type' => 'page', 'slug' => 'post' ) )
	 * @param string $html   Optional already existing HTMl atts (eg. 'id="anchor1" class="wp-block"').
	 * 
	 * @return string        'class="link button" style="margin-left: 10px;" download'
	 */
	public static function implode_html_attributes( $atts, string $html='' ) {

		foreach ( $atts as $att => $val ) {

			if ( $val === true ) {
				// attributes like 'async' or 'download' don't have values
				if ( !preg_match( '/\s'.$att.'\s/', $html ) ) {
					$html .= ' '.$att;
				}
			}
			else if ( empty($val) && $val !== "0" ) {
				// empty attributes are not returned
			}
			else if ( $att == 'data' && is_array($val) ) {
				foreach( $val as $data_att => $data_val ) {
					if ( preg_match( '/\sdata-'.$data_att.'\="[^"]+?"/', $html ) ) {
						$html = preg_replace( '/\sdata-'.$data_att.'\="[^"]+?"/', ' data-'.$data_att.'="'.strval( esc_attr($data_val) ).'"', $html );
					}
					else {
						$html .= sprintf( ' data-%s="%s"', $data_att, strval( esc_attr($data_val) ) );
					}
				}
			}
			else {

				// value already exists
				if ( preg_match( '/\s'.$att.'\="[^"]+?"/', $html ) ) {

					// replace the old value
					if ( $att == 'id' ) {
						$html = preg_replace( '/\s'.$att.'\="([^"]+?)"/', ' '.$att.'="'.esc_attr( $val ).'"', $html );
					}
					// add the new value to the existing one
					else {
						$html = preg_replace( '/\s'.$att.'\="([^"]+?)"/', ' '.$att.'="$1 '.(is_array($val) ? implode(' ', array_map( 'esc_attr', $val )) : strval( esc_attr($val) )).'"', $html );
					}
				}
				else {
					$html .= sprintf( ' %s="%s"', $att, (is_array($val) ? implode(' ', array_map( 'esc_attr', $val )) : strval( esc_attr($val) )) );
				}
			}
		}

		return $html;
	}

	/**
	 * Get a clean date from a formatted and translated date string
	 * 
	 * @param string $date_string_locale eg. 'September 2021'
	 * 
	 * @return string 'YYYY-MM-DD'
	 */
	public static function get_date_from_localed_string($date_string_locale) {
		global $wp_locale;
		$d = "01"; $m = "01"; $y = $date_string_locale;
		foreach ($wp_locale->month as $key => $value) {
			if (strpos($date_string_locale, $value) > -1) {
				$m = $key;
				$tmp = explode($value, $date_string_locale);
				if (count($tmp) == 2) {
					$d = preg_replace("/[^0-9]/", "", $tmp[0]);
					if ($d == "") $d = "01";
					$y = preg_replace("/[^0-9]/", "", $tmp[1]);
				}
				break;
			}
		}
		$clean_date = $y."-".$m."-".$d;
		return $clean_date;
	}

	/**
	 * Match condition for conditional fields
	 * 
	 * @param mixed $is         Current value.
	 * @param mixed $should     What the value should be.
	 * @param string $type      Type of the condition
	 * @param mixed $condition  Actual condition.
	 * 
	 * @return bool
	 */
	public static function match_condition( $is, $should, $type="urlparam", $condition="is" ) {
		// COOKIE & URL_PARAMS
		if ($type === "urlparam" || $type === "cookie") {
			if ($condition === "is") {
				if ($should === $is) return true;
				else return false;
			} else if ($condition === "is_not") {
				if ($should !== $is) return true;
				else return false;
			} else if ($condition === "has") {
				if (strpos($is, $should) !== false) return true;
				else return false;
			} else if ($condition === "empty") {
				if (empty($is)) return true;
				else return false;
			} else if ($condition === "not_empty") {
				if (!empty($is)) return true;
				else return false;
			}
		}
		// USERROLES
		else if ($type === "userrole") {
			$flipped = array_flip($is); // get userroles as keys of array
			if ($condition === "is") {
				if (isset($flipped[$should])) return true; // check if key isset
				else return false;
			} else if ($condition === "is_not") {
				if (!isset($flipped[$should])) return true;
				else return false;
			}
		}
		// TIME
		else if ($type === "time") {
			foreach ((array) explode(",", $should) as $time) {
				$time = explode("-", $time); // explode "start-end" to ["start","end"]
				if (!isset($time[1])) $time[1] = intval($time[0])+1; // if no end isset -> use start+1 (12 -> 12-13)

				// enable minutes
				foreach ( $time as $i => $t ) {
					$t = strval($t);
					// convert hour to hour + minutes (13 -> 1300, 08 -> 800, 5 -> 500)
					if ( strpos($t, ":") === false ) {
						$t = str_replace('0', '', $t)."00";
					}
					// convert hour and minutes to string (13:15 -> 1315, 07:1 -> 0710, 9:4 -> 0940)
					else {
						$t = explode(":", $t);
						if ( isset($t[1]) && !empty($t[1]) ) {
							$t[1] = strlen(strval($t[1])) > 2 ? substr($t[1], 0, 2) : strval($t[1]); // max length of 2
							$t[1] = strlen($t[1]) < 2 ? "0"+$t[1] : $t[1]; // min length of 2
						} else {
							$t[1] = "00";
						}
						$t = implode("", $t);
					}
					$time[$i] = $t;
				}

				$start_time = $time[0];
				$end_time   = $time[1];

				// debug( "$start_time <= $is" );
				// debug( intval($start_time) <= $is, true );
				// debug( "$is < $end_time" );
				// debug( $is < intval($end_time), true );

				if ($condition === "is") {
					if ( intval($start_time) <= $is && $is < intval($end_time) ) return true;
				} else if ($condition === "is_not") {
					if ( $is < intval($start_time) || intval($end_time) <= $is ) return true;
				}
			}
		}
		// SEARCH
		else if ($type === "search") {
			return $is === $should;
		}
		return false;
	}

	/**
	 * @deprecated since 1.4.5
	 * Can be removed in future versions
	 */
	public static function get_color($color) {
		return \Greyd\Layout\Render::get_color($color);
	}

	/*
	====================================================================================
		Custom styles from Core Helper 
	====================================================================================
	*/

	/**
	 * Add css line to styles queue.
	 * @see \Greyd\Helper::add_custom_style()
	 */
	public static function add_custom_style( $css, $in_footer=true ) {
		if ( method_exists( '\Greyd\Helper', 'add_custom_style' ) ) {
			return \Greyd\Helper::add_custom_style( $css, $in_footer );
		}
		else if ( method_exists( '\enqueue', 'add_custom_style' ) ) {
			return \enqueue::add_custom_style( $css, $in_footer );
		}
		else {
			return false;
		}
	}
}