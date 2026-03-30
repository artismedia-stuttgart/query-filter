/**
 * Helper functions & utils for the block editor.
 */
greyd.tools = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Check if this is an object and all properties are empty.
	 * @param {mixed} object 
	 * @returns bool
	 */
	this.isEmptyObject = function(object) {
		if ( object !== null && typeof object === 'object' ) {
			return _.values(object).every(_.isEmpty);
		}
		return false;
	}

	/**
	 * Get numeric value from string.
	 * @param {string} value
	 * @param {mixed} fallback
	 * 
	 * @returns {string|mixed} Numeric stringified value or fallback.
	 */
	this.getNumValue = function( value, fallback = null ) {

		/**
		 * @since 2.3.0
		 * Change to this in future versions. Needs some testing.
		 * return greyd.tools.ParseQuantityAndUnitFromRawValue( value )[0];
		 */

		value = String(value).match(/[-]{0,1}[\d]*[.]{0,1}[\d]+/g);
		return _.isEmpty(value) ? fallback : String(_.get(value, 0));
	}

	/**
	 * Get unit from string
	 * @param {string} value
	 * @param {mixed} fallback
	 * 
	 * @returns {string|mixed} Unit value or fallback.
	 */
	this.getUnitValue = function( value, fallback = null ) {

		/**
		 * @since 2.3.0
		 * Change to this in future versions. Needs some testing.
		 * return greyd.tools.ParseQuantityAndUnitFromRawValue( value )[1];
		 */

		value = String(value).match(/(px|%|vw|vh|rem|em)/g);
		return _.isEmpty(value) ? fallback : String(_.get(value, 0));
	}

	/**
	 * Generate a random ID.
	 * @param {int} length Length of the ID to generate.
	 * @return {string} The generated ID.
	 */
	this.generateRandomID = function(length = 6) {
		return Math.random().toString(36).substring(2, length);
	}
	
	/**
	 * This function is used to compare strings that contain html
	 * and other special characters, eg. the post content defined
	 * by the block editor.
	 * @param {string} value
	 * @returns string
	 */
	this.hashCode = function(value) {
		var string = typeof value === 'object' ? JSON.stringify(value) : value;
		return string.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);
	}

	/**
	 * Clean an array of classes.
	 * Filters empty and duplicates and trims each element.
	 * @param {array} classes 
	 * @returns cleaned array of classes.
	 */
	this.cleanClassArray = function(classes) {

		return classes.reduce(function(filtered, item, pos) {
			// remove empty
			if (_.isEmpty(item.replace(/\s/g, ''))) return filtered;
			// remove duplicates
			if (classes.indexOf(item) != pos) return filtered;
			// trim
			filtered.push(item.trim().replace(/^\s|\s$/g, ''));
			  return filtered;
		}, []);

	}

	/**
	 * Saves the most basic attributes of a block while transforming to other blocktype.
	 * anchor, className, inline_css and trigger
	 * @param {object} attributes old block attributes
	 * @returns transformed attributes
	 */
	this.transformDefaultAtts = function(attributes) {

		var newatts = {};
		if (_.has(attributes, 'anchor') && attributes["anchor"] != "") {
			newatts.anchor = attributes.anchor;
		}
		if (_.has(attributes, 'className') && attributes["className"] != "") {
			newatts.className = attributes.className;
		}
		if (_.has(attributes, 'inline_css') && attributes["inline_css"] != "") {
			newatts.inline_css = attributes.inline_css;
			newatts.inline_css_id = attributes.inline_css_id;
		}
		if (_.has(attributes, 'trigger') && attributes["trigger"] != "") {
			newatts.trigger = attributes.trigger;
		}
		return newatts;

	}

	/**
	 * Renders the Title for a breakpoint, dynamic values are used.
	 * @param {string} breakpoint 'title_xs', 'title_sm', 'title_md' or 'title_lg'
	 * @returns nice title
	 */
	this.makeBreakpointTitle = function(breakpoint) {

		if ( breakpoint == "title_xs" || breakpoint == "xs" )
			return __("Mobile (< %1px)", 'greyd_hub').replace('%1', greyd.data.grid.sm);
		if ( breakpoint == "title_sm" || breakpoint == "sm" )
			return __("Tablet small (%1-%2px)", 'greyd_hub').replace('%1', greyd.data.grid.sm).replace('%2', greyd.data.grid.md-1);
		if ( breakpoint == "title_md" || breakpoint == "md" )
			return __('Tablet (%1-%2px)', 'greyd_hub').replace('%1', greyd.data.grid.md).replace('%2', greyd.data.grid.lg-1);
		if ( breakpoint == "title_lg" || breakpoint == "lg" )
			return __('Desktop (> %1px)', 'greyd_hub').replace('%1', greyd.data.grid.lg);
		return "";

	}

	/**
	 * Holds all current greydClasses keyed by clientId + path
	 * 
	 * @see getGreydClass() for details.
	 */
	this.allGreydClasses = {};

	/**
	 * Retrieve the greydClass or generate a new one if necessary.
	 * 
	 * @param {object} props    Block properties.
	 * @param {string} path     Path of the class attribute relative to props.attributes.
	 *                          Defaults to 'greydClass'.
	 * 
	 * @return {string} greydClass
	 */
	this.getGreydClass = function( props, path ) {

		// get props.attributes
		const attributes = _.has(props, 'attributes') ? props.attributes : null;

		// return if no attributes found
		if ( !attributes ) {
			return greyd.tools.generateGreydClass();
		}

		// get the current greydClass
		if ( typeof path === 'undefined' || _.isEmpty(path) ) {
			path = 'greydClass';
		}

		// get the greydClass
		var greydClass = _.get(attributes, path);

		// get the clientId
		const clientId = _.has(props, 'clientId') ? props.clientId : null;
		const classKey = _.isEmpty(clientId) ? null : clientId +'.'+ path;

		// never generate new classes inside a dynamic template or template-part block
		// neither in site-editor previews nor for navigation elements
		if (
			( _.has(attributes, 'dynamic_parent') && !_.isEmpty(attributes.dynamic_parent) ) ||
			greyd.tools.isChildOf( props.clientId, "core/template-part" ) !== false ||
			( location.pathname.indexOf('site-editor.php') > -1 && location.search.includes('canvas=edit') == false ) ||
			props.name == 'core/navigation-submenu' || props.name == 'core/navigation-link' ||
			greyd.tools.isChildOf( props.clientId, "core/navigation" ) !== false
		) {
			if ( _.isEmpty(greydClass) && (
				props.name == 'core/navigation-submenu' || props.name == 'core/navigation-link' ||
				greyd.tools.isChildOf( props.clientId, "core/navigation" ) !== false
			) ) {
				// generate new class once when a navigation class is empty (item is created)
				greydClass = greyd.tools.generateGreydClass();
			}
			if ( classKey ) {
				// still save its greydClass to avoid duplicates in content
				greyd.tools.allGreydClasses[classKey] = greydClass;
			}
			return greydClass;
		}

		// get greydClass by classKey
		if ( classKey && _.has(greyd.tools.allGreydClasses, classKey) ) {

			/**
			 * Check if the class already exists.
			 * This reduces the loading time of large pages significantly, but it
			 * could lead to errors duplicating pages, posts or contents.
			 * 
			 * In order to activate this properly, only generate a greydClass below
			 * if the greydClass is empty.
			 */
			const otherGreydClasses = Object.values( { ...greyd.tools.allGreydClasses, [classKey]: null } );
			if ( otherGreydClasses.includes(greydClass) ) {
				// console.log( 'greydClass is a duplicate of: ', classKey );
				greydClass = greyd.tools.generateGreydClass();
				greyd.tools.allGreydClasses[classKey] = greydClass;
				return greydClass;
			}
			
			// console.log( 'greydClass found by classKey: ', { [classKey]: greyd.tools.allGreydClasses[classKey] } );
			return greyd.tools.allGreydClasses[classKey];
		}

		// generate a new class (once per pageload, to prevent copy-paste errors)
		if ( _.isEmpty(greydClass) ) greydClass = greyd.tools.generateGreydClass();
		
		// cache the class
		if ( classKey ) {
			greyd.tools.allGreydClasses[classKey] = greydClass;
			// console.log( 'cached the greydClass: ', { [classKey]: greydClass } );
		}

		return greydClass;
	}

	/**
	 * Generate a greyd custom css class (eg. "gs_jsI73A")
	 * 
	 * @returns {string} className
	 */
	this.generateGreydClass = function() {
		var result = "gs_";
		var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		for (var i = 0; i < 6; i++) {
			result += chars.charAt(Math.floor(Math.random() * chars.length));
		}
		// console.log( 'Generated a new greydClass: ', result );
		return result;
	}

	/**
	 * Remove "wp-block-" class from class list
	 * @param {array|string} classList Array of all class names.
	 * @returns {array}
	 */
	this.getCleanClassList = function(classList) {
		if ( typeof classList === "string" ) {
			classList = classList.split(" ");
		}
		if ( !Array.isArray(classList) || classList.length === 0 ) {
			return [];
		}

		if ( _.get(classList, 0).lastIndexOf("wp-block-", 0) === 0 ) {
			classList.shift();
			classList = greyd.tools.getCleanClassList(classList);
		}
		return classList;
	}
	
	/**
	 * Get CSS formated line.
	 * 
	 * @param {string} name Name of the css property (eg. "margin" or "--gutter").
	 * @param {mixed} value Value for the css property (eg. "12px").
	 * @param {bool} important Whether the CSS-values are important.
	 * 
	 * @returns CSS line (eg. "margin: 12px; "). If @param value is an object, multiple
	 *          lines are returned (eg. "margin-top: 12px; margin-bottom: 0px; ")
	 */
	this.getCssLine = function( name, value, important ) {

		if ( Number.isInteger(value) && value !== 0 ) value = String( value );

		/**
		 * @since 2.4.0 new SpacingPresetControl produces "0"-value without unit.
		 * By default, this would be considered as empty value and not rendered.
		 * This fix prevents this behavior.
		 */
		if ( _.isEmpty(value) && value !== "0" ) return "";

		var property = _.kebabCase(name);
		var styles = "";
		
		if ( typeof value === 'object' ) {

			/**
			 * Support for object-position
			 * @since 1.2.9
			 */
			if ( property === 'object-position' ) {
				styles = property + ': ' + (value.x * 100) + '% ' + (value.y * 100) + '%; ';
			}
			else {

				/**
				 * Support for font appearance.
				 * Value is saved as object, eg.: { fontWeight: 100, fontStyle: 'italic' }
				 * @since 1.3.3
				 */
				if ( name === '__experimentalfontAppearance' ) {
					property = "";
				}
				
				property += _.isEmpty(property) ? "" : "-";

				/**
				 * Support for CSS variables
				 * @since 1.2.10
				 */
				if ( name.length > 2 && name.substring(0, 2) === '--' ) {
					property = '--'+property;
				}
	
				for (const [side, val] of Object.entries(value)) {
					styles += greyd.tools.getCssLine( property+side, val, important );
				}
			}
		}
		else if ( !_.isEmpty(property) ) {
			if ( property.indexOf("box-shadow") !== -1 ) {
				var val = greyd.tools.getShadow(value);
			}
			else if ( property.indexOf("background") !== -1 ) {
				var val = greyd.tools.getGradientValue(value);
				if (val == value) {
					val = greyd.tools.convertColorToVar( value );
					if ( val.indexOf('var(--') !== 0 ) {
						val = greyd.tools.getColorValue( val );
					}
				}
			}
			else if ( property.indexOf("color") !== -1 && value.indexOf('color-') === 0 ) {
				var val = greyd.tools.convertColorToVar( value );
				// console.log( 'getCSSLine color:', value, '->', val )
			}
			else {
				var val = String(value).trim();
			}

			/**
			 * Convert URL encoded CSS variables
			 * @since 1.3.9
			 */
			if (val.indexOf('var(u002du002d') > -1) {
				val = val.replaceAll('var(u002du002d', 'var(--');
			}
			/**
			 * Convert preset into cssVar 
			 * eg. 'var:preset|spacing|small' -> 'var(--wp--preset--spacing--small)'
			 * @since 2.3.0
			 */
			else if (val.indexOf('var:') > -1) {
				if (val.indexOf(' ') > -1) {
					let parts = val.split(' ');
					// loop through all parts
					for (let i = 0; i < parts.length; i++) {
						if (parts[i].indexOf('var:') === 0) {
							parts[i] = wp.blockEditor.getSpacingPresetCssVar( parts[i] );
						}
					}
					// implode
					val = parts.join(' ');
				} else {
					val = wp.blockEditor.getSpacingPresetCssVar( val );
				}
			}

			if (property.indexOf('u-002-du-002-d') > -1) {
				property = property.replaceAll('u-002-du-002-d', '--');
			}

			// enable css variables
			if ( name.length > 2 && name.substring(0, 2) == '--' ) {
				property = '--'+property;
			}
			
			// temporary fix for hotspot block post import problems
			if ( (name.includes('popover-') || name.includes('hotspot-')) && name.substring(0, 2) != '--' ) {
				property = '--'+property;
			}

			// Qickfix for leading '-' in line-clamp feature
			// Bug is in kebabCase function wich removes all leading '-'
			if ( name.startsWith('-webkit-') ) {
				property = '-'+property;
				if ( name == "-webkit-line-clamp") {
					// adding needed additional styles
					// https://css-tricks.com/almanac/properties/l/line-clamp/
					styles += "display: -webkit-box; ";
					styles += "overflow: hidden; ";
					styles += "-webkit-box-orient: vertical; ";
				}
			}

			/**
			 * @since 2.4.0 new SpacingPresetControl produces "0"-value without unit.
			 * By default, this would be considered as empty value and not rendered.
			 * This fix prevents this behavior.
			 */
			const valueIsEmpty = _.isEmpty(val) && val !== "0";
			if ( !valueIsEmpty && !_.isEmpty(property) ) {
				styles += property+": "+val+( important ? " !important" : "" )+"; ";
			}
		}
		return styles;
	}

	/**
	 * Compose object of styles into a clean CSS.
	 * @param {object} styleObj All default, hover & responsive styles.
	 * @param {string} parentSelector Parent css class or ID, usually a greydClass.
	 * @param {bool} editor Whether to render inside the editor.
	 * @param {bool} important Whether the CSS-values are important.
	 * @property {string} activeSelector Selector for active state, eg. '.is-active', ':checked + span'
	 * @returns {string}
	 */
	this.composeCSS = function(styleObj, parentSelector, editor, important, activeSelector) {
		if (typeof styleObj !== "object" || _.isEmpty(styleObj) ) {
			return "";
		}

		// vars
		parentSelector   = parentSelector ?? "";
		var finalCSS     = "";
		var finalStyles  = { default: "", lg: "", md: "", sm: "" };
		const deviceType = greyd.tools.getDeviceType();

		// loop through all selectors
		for (var [selector, attributes] of Object.entries( styleObj )) {

			if ( _.isEmpty(attributes) || typeof attributes !== "object" ) continue;

			// collect all styles for this selector
			var selectorStyles = { default: "", hover: "", lg: "", md: "", sm: "", active: "" };

			// merge responsive styles depending on the current device type
			if ( editor && _.has(attributes, 'responsive') ) {

				if ( deviceType === "Tablet" ) {

					// merge as normal styles
					if ( _.has(attributes.responsive, 'md') ) {
						attributes = {
							...attributes,
							...attributes.responsive.md
						};
					}
					// remove responsive styles
					attributes = _.omit( attributes, 'responsive' );
				}
				else if ( deviceType === "Mobile" ) {

					// merge as normal styles
					if ( _.has(attributes.responsive, 'sm') ) {
						attributes = {
							...attributes,
							...attributes.responsive.sm
						};
					}
					// remove responsive styles
					attributes = _.omit( attributes, 'responsive' );
				}
				else {
					// remove the mobile styles in desktop preview
					attributes = {
						...attributes,
						responsive: _.omit( attributes.responsive, ['sm', 'md'] )
					};
				}
			}

			// loop through all attributes
			for (const [name, value] of Object.entries(attributes)) {

				if ( name === "hover" ) {
					selectorStyles.hover += greyd.tools.getCssLine( "", value, important );
				}
				else if ( name === "active" ) {
					selectorStyles.active += greyd.tools.getCssLine( "", value, important );
				}
				else if ( name === "responsive" ) {
					selectorStyles.lg += greyd.tools.getCssLine("", _.get(value, "lg"), important);
					if ( ! editor ) {
						selectorStyles.md += greyd.tools.getCssLine("", _.get(value, "md"), important);
						selectorStyles.sm += greyd.tools.getCssLine("", _.get(value, "sm"), important);
					}
				}
				else {
					selectorStyles.default += greyd.tools.getCssLine( name, value, important );
				}
			}

			// split selector into pieces to enable mutliple selectors (eg. ".selector, .selector h3")
			selector = selector.split(",");

			// console.log( selector );

			// loop through all selectorStyles
			for (var [type, css] of Object.entries(selectorStyles)) {
				if ( _.isEmpty(css) ) continue;

				// basic selectors
				let wrapper = editor ? ".editor-styles-wrapper " : "";
				let pseudo  = "";
				if ( type === "hover" ) {
					pseudo = ":hover";
				}
				else if ( type === "active" ) {
					let __selector = selector.join(",");
					if ( activeSelector ) {
						parentSelector = activeSelector;
					}
					else if ( __selector.indexOf('input') > -1 ) {
						__selector = __selector.replace( 'input', 'input:checked' );
						if ( __selector.indexOf('.option') > -1 ) {
							__selector = __selector.replace( '.option', '.option.selected' );
						}
						selector = __selector.split(",");
					}
					else {
						pseudo = " :checked + "+(__selector == '' ? '*' : '');
					}
				}
				
				// eg. .wrapper .gs_123456.selector, .wrapper .gs_123456.selector h3 { color: #fff; }
				var finalSelector = wrapper+"."+parentSelector+pseudo+selector.join(", "+wrapper+"."+parentSelector+pseudo);

				if ( type === "hover" || type === "active" ) {

					// add all selectors again with the pseudo "._hover" instead of ":hover"
					if ( editor && type === "hover" ) {
						pseudo = "._"+type;
						wrapper += "[class*='-selected'] "; // only '.is-selected' or 'is-child-selected'
						finalSelector += ", "+wrapper+"."+parentSelector+pseudo+selector.join(", "+wrapper+"."+parentSelector+pseudo);
					}

					// make type default to not add @media queries
					type = "default";
				}
				
				finalStyles[type] += finalSelector+" { "+css+"} ";
			}
		}

		// loop through all finalStyles
		for (var [type, css] of Object.entries(finalStyles)) {
			if ( _.isEmpty(css) ) continue;

			var prefix = "", suffix = "";
			if ( type !== "default" ) {
				prefix = "@media (max-width: "+( _.get(greyd.data.grid, type) - 0.02 )+"px) { ";
				suffix = "} ";
			}

			finalCSS += prefix+css+suffix;
		}
		
		return finalCSS;
	}

	/**
	 * Converts a string into an object, where each CSS property is used as
	 * a key in the object, and each value is used as the value for that key.
	 * 
	 * @example
	 * * input:  'transform: translateX(-42px); color: #ddd;'
	 * * return: { transform: "translateX(-42px)", color: "#ddd" }
	 * 
	 * @param {string} cssString CSS styles.
	 * @returns {object}
	 */
	this.cssStringToObject = function (cssString) {
		let cssObject = {};
		let cssProperties = cssString.split(';');
		cssProperties.forEach(property => {
			let keyValue = property.split(':');
			if (keyValue.length === 2) {
				cssObject[keyValue[0].trim()] = keyValue[1].trim();
			}
		});
		return cssObject;
	}

	/**
	 * Get the currently previewed device type
	 * @returns {string} 'Desktop' | 'Tablet' | 'Mobile' | ''
	 */
	this.getDeviceType = function() {
		if ( _.has(wp.data.select( 'core/editor' ), 'getDeviceType') ) {
			return wp.data.select( 'core/editor' ).getDeviceType();
		} else {
			var store = ( pagenow && pagenow == 'site-editor' ) ? "core/edit-site" : "core/edit-post";
			if ( _.has(wp.data.select(store), "__experimentalGetPreviewDeviceType") ) {
				return wp.data.select(store).__experimentalGetPreviewDeviceType();
			}
			return '';
		}
	}

	/**
	 * Fix the flex-alignment when flex-direction changes between 'row' & 'column'
	 * @see block greyd/menu
	 * 
	 * @param {object} obj The current style object
	 * @param {object} atts Custom attributes
	 * @returns object
	 */
	this.fixResponsiveFlexAlignment = function(obj, atts) {
		if ( !obj || typeof obj !== 'object' ) return obj;

		// row alignment: alignItems --> justifyContent
		if ( _.has(obj, 'flexDirection') && obj['flexDirection'] && obj['flexDirection'] === 'row' ) {
			if ( _.has(obj, 'alignItems') && obj['alignItems'] && !_.isEmpty(obj['alignItems']) ) {
				obj['justifyContent'] = obj['alignItems'];
			}
			// set the alignment of the column via @param atts.columnAlignment
			obj['alignItems'] = atts && typeof atts.columnAlignment !== 'undefined' ? atts.columnAlignment : 'flex-start';
		}
		// column alignment: + textAlign
		else {
			if ( _.has(obj, 'alignItems') && obj['alignItems'] && obj['alignItems'] === 'flex-start' ) {
				obj['textAlign'] = 'left';
			} else if ( _.has(obj, 'alignItems') && obj['alignItems'] && obj['alignItems'] === 'flex-end' ) {
				obj['textAlign'] = 'right';
			}  else if ( _.has(obj, 'alignItems') && obj['alignItems'] && obj['alignItems'] === 'center' ) {
				obj['textAlign'] = 'center';
			} else if ( _.has(obj, 'textAlign') ) {
				delete(obj['textAlign']);
			}
		}
		for (const [key, val] of Object.entries(obj)) {
			if ( val && typeof val === 'object' ) obj[key] = greyd.tools.fixResponsiveFlexAlignment(val);
		}
		return obj;
	}

	/**
	 * Get the color value of a CSS-variable
	 * @param {string} color    Color slug (eg. "color-31" or "foreground")
	 * @returns {string}        Value of the color (eg. "#5D5D5D")
	 */
	this.getColorValue = function(color) {
		// console.log("editor script getColorValue");
		if ( typeof color === 'undefined' || _.isEmpty(color) ) {
			color = null;
		}

		// convert enitre variable to hex color.
		// eg. "var(--wp--preset--color--foreground)" --> "#5D5D5D"
		if ( color && color.indexOf('var(--') === 0 ) {
			return greyd.tools.convertVarToColor( color );
		}

		if ( color ) {
			for (var i=0; i<greyd.data.colors.length; i++) {
				if (greyd.data.colors[i].slug == color) {
					color = greyd.data.colors[i].color;
					break;
				}
			}
		}
		// else if (color.indexOf('var(u002du002d') === 0) {
		// 	color = color.replace('var(u002du002d', 'var(--');
		// }
		return color;
	}

	/**
	 * Get the variable name (slug) of a color value if possible.
	 * @param {string} color    Value of the color (eg. "#5D5D5D")
	 * @returns {string}        Color slug (eg. "color-31" or "foreground")
	 */
	this.getColorSlug = function(color) {
		// console.log("editor script getColorSlug");
		if (typeof color === 'undefined') color = "";
		if (
			color.indexOf('#') !== -1 ||
			color.indexOf('rgb(') !== -1 ||
			color.indexOf('rgba(') !== -1
		) {
			for (var i=0; i<greyd.data.colors.length; i++) {
				if (greyd.data.colors[i].color == color) {
					color = greyd.data.colors[i].slug;
					break;
				}
			}
		}
		// console.log(color);
		return color;
	}

	/**
	 * Convert color value to var(--colorXX)
	 * @param {string} value Value of the color, eg. '#5D5D5D'.
	 * @returns {string} CSS variable, eg. 'var(--wp--preset--color--color-31)'.
	 */
	this.convertColorToVar = function( value ) {
		if ( !_.isEmpty(value) ) {
			value = this.getColorSlug(value);
			if (
				value.indexOf("#") === -1 &&
				value.indexOf("linear-gradient(") == -1 &&
				value.indexOf("radial-gradient(") == -1 &&
				value.indexOf("var(") == -1 &&
				value.indexOf("rgb(") == -1 &&
				value.indexOf("rgba(") == -1
			) {
				value = "var(--wp--preset--color--"+value+")";
			}
		}
		return value;
	}

	/**
	 * Convert var(--colorXX) to color value
	 * @param {string} value CSS variable, eg. 'var(--wp--preset--color--color-31)'.
	 * @returns {string} Value of the color, eg. '#5D5D5D'.
	 */
	this.convertVarToColor = function (value) {
		if ( !_.isEmpty(value) ) {
			let match = value.match(/var\(--wp--preset--color--(.*)\)/);
			if ( _.has(match, 1) ) {
				value = this.getColorValue( match[1] );
			}
			else {
				match = value.match(/var\(--color([\d]{2})\)/);
				if ( _.has(match, 1) ) {
					value = this.getColorValue( "color-"+match[1] );
				}
			}
		}
		return value;
	}

	/**
	 * Convert a gradient slug to it's CSS value if possible.
	 * @param {string} gradient The gradient slug, eg. 'color33-to-color31'
	 * @returns {string} Gradient CSS value, eg. 'linear-gradient(90deg, #ffffff 0%, #000000 100%)'
	 */
	this.getGradientValue = function(gradient) {
		// console.log("editor script getGradientValue", gradient);
		// console.log(gradient);
		if ( typeof gradient === 'undefined' || _.isEmpty(gradient) ) {
			gradient = null;
		}
		// if (gradient.indexOf('color-') > -1) {
		if (
			gradient
			&& gradient.indexOf("linear-gradient(") == -1
			&& gradient.indexOf("radial-gradient(") == -1
		) {
			for (var i=0; i<greyd.data?.gradients?.length; i++) {
				if (greyd.data.gradients[i].slug == gradient) {
					gradient = greyd.data.gradients[i].gradient;
					break;
				}
			}
		}
		// console.log("editor script getGradientValue", gradient);
		return gradient;
	}

	/**
	 * Convert a gradient value to it's slug if possible.
	 * @param {string} gradient The gradient CSS value, eg. 'linear-gradient(90deg, #ffffff 0%, #000000 100%)'
	 * @returns {string} slug of the gradient, eg. 'color33-to-color31'
	 */
	this.getGradientSlug = function(gradient) {
		// console.log("editor script getGradientSlug");
		// console.log(gradient);
		if (typeof gradient === 'undefined') gradient = "";
		// if (gradient.indexOf('color-') == -1) {
		if (gradient.indexOf("linear-gradient(") === 0 || gradient.indexOf("radial-gradient(") === 0) {
			for (var i=0; i<greyd.data.gradients.length; i++) {
				if (greyd.data.gradients[i].gradient == gradient) {
					gradient = greyd.data.gradients[i].slug;
					break;
				}
			}
		}
		return gradient;
	}

	/**
	 * Check if a value is a gradient
	 * @param {string} color Whether the value is a gradient.
	 * @returns bool
	 */
	this.isGradient = function( color ) {
		if (
			!color ||
			_.isEmpty(color) ||
			color.indexOf('#') === 0 ||
			color.indexOf('var(--') === 0 ||
			color.indexOf('rgb(') === 0 ||
			color.indexOf('rgba(') === 0
		) {
			return false;
		}
		if (
			color.indexOf('linear-gradient(') === 0 ||
			color.indexOf('radial-gradient(') === 0 ||
			greyd.tools.getGradientValue( color ) !== color
		) {
			return true;
		}
		return false;
	}

	/**
	 * Get static color value.
	 * Often used to properly display a ColorPicker.
	 * 
	 * @param {string} value Dynamic value.
	 *      @example "var(--color13)"
	 *      @example "color12-to-color13"
	 * @returns {string} Internal static color or gradient.
	 *      @example "#25f6ef"
	 *      @example "linear-gradient(135deg,rgb(152,177,191) 0%,rgb(125,47,198) 100%)"
	 */
	this.getStaticColorValue = function( color ) {

		if ( greyd.tools.isGradient( color ) ) {
			return greyd.tools.getGradientValue( color );
		}
		
		return greyd.tools.convertVarToColor( color );
	}

	/**
	 * Get dynamic color value to save.
	 * Often use to save the color value returned by a ColorPicker.
	 * 
	 * @param {string} value Internal static color or gradient.
	 *      @example "#25f6ef"
	 *      @example "linear-gradient(135deg,rgb(152,177,191) 0%,rgb(125,47,198) 100%)"
	 * @returns {string} Dynamic value to save to attribute.
	 *      @example "var(--color13)"
	 *      @example "color12-to-color13"
	 */
	this.getDynamicColorValue = function( color ) {

		if ( greyd.tools.isGradient( color ) ) {
			return greyd.tools.getGradientSlug( color );
		}
		
		return greyd.tools.convertColorToVar( color );
	}
	
	/**
	 * Get clean CSS shorthand value for box-shadow property
	 * @param {string} shadow of format "0px+0px+50px+0px+color_22+100"
	 * @returns CSS value for box-shadow
	 */
	this.getShadow = function ( shadow ) {
		if ( shadow === 'none' || _.isEmpty( shadow ) || shadow.indexOf( '+' ) === -1 ) {
			return shadow;
		}

		let raw = shadow.split( '+' );
		let opacity = raw[ raw.length - 1 ];
		raw.pop(); // remove opacity
		let color = raw[ raw.length - 1 ];
		raw.pop(); // remove color

		// Classic SUITE: get color value from slug 'color_22'
		if ( color.indexOf( 'color' ) > -1 ) {
			color = this.getColorValue( color.replace( '_', '-' ) );
		}
		// FSE: get color value from variable 'var(--wp--preset--color--foreground)'
		else {
			color = this.convertVarToColor( color );
		}

		// make object
		if ( !color || color == 'transparent' || color == '' ) {
			color = { 'r': 0, 'g': 0, 'b': 0, 'a': 0 };
		}
		else if ( color.indexOf( '#' ) === 0 ) {
			color = this.hex2rgba( color );
		}

		if ( !_.has( color, 'a' ) ) {
			color[ 'a' ] = 1;
		}

		// calculate opacity
		color[ 'a' ] = color[ 'a' ] * ( opacity / 100.0 );

		// set string value
		return raw.join( ' ' ) + ' rgba(' + color[ 'r' ] + ',' + color[ 'g' ] + ',' + color[ 'b' ] + ',' + color[ 'a' ] + ')';
	}

	
	this.hex2rgbString = function(hex) {
		var rgba = this.hex2rgba(hex);
		if (rgba) {
			return 'rgb('+rgba.r+','+rgba.g+','+rgba.b+')';
		}
		return false;
	}

	this.hex2rgba = function(hex) {
        // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
        var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
        hex = hex.replace(shorthandRegex, function(m, r, g, b) {
            return r + r + g + g + b + b;
        });
        // decode hex
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
            a: 1
        } : false;
    }

    this.rgb2rgba = function(rgb) {
        // console.log(rgb);
        var tmp = rgb.split('(');
        tmp = tmp[1].split(')');
        tmp = tmp[0].split(',');
        if (tmp.length == 3) return { r: tmp[0], g: tmp[1], b: tmp[2], a: 1 };
        if (tmp.length == 4) return { r: tmp[0], g: tmp[1], b: tmp[2], a: tmp[3] };
        return false;
    }

	/**
	 * Converts a spacing preset into a CSS var string.
	 *
	 * @param {string} value Value to convert, eg. 'var:preset|spacing|small'
	 *
	 * @return {string | undefined} CSS var string for given spacing preset value.
	 *                              eg. 'var(--wp--preset--spacing--small)'
	 */
	this.getSpacingPresetCssVar = function( value ) {
		return wp.blockEditor.getSpacingPresetCssVar( value );
	}

	/**
	 * Converts a CSS var into a spacing preset.
	 *
	 * @param {string} value CSS var string to convert, eg. 'var(--wp--preset--spacing--small)'
	 *
	 * @return {string | undefined} spacing preset value, eg. 'var:preset|spacing|small'
	 */
	this.getSpacingPresetFromCssVar = function( value ) {

		if ( value && typeof value === 'string' ) {
			if ( value.indexOf('u002d') > -1 ) {
				value = value.replaceAll('u002d', '-');
			}

			if ( value.indexOf('var(--wp--preset--spacing--') === 0 ) {
				return value.replace('var(--wp--preset--spacing--', 'var:preset|spacing|').replace(')', '');
			}
		}

		return value;
	}

	/**
	 * Parse a raw value into a quantity and a unit.
	 * @since 2.3.0
	 * @param {mixed} value
	 * @returns {array} First element is the quantity, second element is the unit.
	 */
	this.ParseQuantityAndUnitFromRawValue = function ( value ) {
		return [parsedQuantity, parsedUnit] = ( 0, wp.components.__experimentalParseQuantityAndUnitFromRawValue )( value );
	}

	/**
	 * Get the computed spacing value.
	 * @since 2.3.0
	 * @param {string} value Spacing value.
	 * @returns {string} Computed spacing value.
	 */
	this.getComputedSpacingValue = function( value ) {

		if ( value && typeof value === 'string' ) {
			value = greyd.tools.getSpacingPresetFromCssVar( value );
		}

		const [parsedQuantity, parsedUnit] = greyd.tools.ParseQuantityAndUnitFromRawValue( value );

		const computedValue = ( 0, wp.blockEditor.isValueSpacingPreset )( value ) ? value : [ parsedQuantity, parsedUnit ].join('');

		// console.log( 'computedValue:', computedValue, '('+typeof computedValue+')', 'parsedQuantity:', 'parsedUnit:', parsedUnit );

		return computedValue;
	}

	this.getCoreIcon = function(icon) {
		// https://wp-icon.wild-works.net/
		// icon polyfills can be found in gutenberg plugin files
		var svg_atts = {
			'width': "24",
			'height': "24",
			'viewBox': "0 0 24 24",
			'xmlns': "http://www.w3.org/2000/svg"
		};
		return {
			// icons from core
			image: el( 'svg', svg_atts, el( 'path', {
				d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z"
			} ) ),
			// query layout type
			layoutList: el( "svg", svg_atts, el( "path", {
				d: "M4 4v1.5h16V4H4zm8 8.5h8V11h-8v1.5zM4 20h16v-1.5H4V20zm4-8c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2z"
			} ) ),
			layoutFlex: el( "svg", svg_atts, el( "path", {
				d: "m3 5c0-1.10457.89543-2 2-2h13.5c1.1046 0 2 .89543 2 2v13.5c0 1.1046-.8954 2-2 2h-13.5c-1.10457 0-2-.8954-2-2zm2-.5h6v6.5h-6.5v-6c0-.27614.22386-.5.5-.5zm-.5 8v6c0 .2761.22386.5.5.5h6v-6.5zm8 0v6.5h6c.2761 0 .5-.2239.5-.5v-6zm0-8v6.5h6.5v-6c0-.27614-.2239-.5-.5-.5z",
				fillRule: "evenodd", clipRule: "evenodd"
			} ) ),
			// list format
			formatIndent: el( "svg", svg_atts, el( "path", {
				d: "M4 7.2v1.5h16V7.2H4zm8 8.6h8v-1.5h-8v1.5zm-8-3.5l3 3-3 3 1 1 4-4-4-4-1 1z"
			} ) ),
			formatOutdent: el( "svg", svg_atts, el( "path", {
				d: "M4 7.2v1.5h16V7.2H4zm8 8.6h8v-1.5h-8v1.5zm-4-4.6l-4 4 4 4 1-1-3-3 3-3-1-1z"
			} ) ),
			// text align
			textAlignLeft: el( "svg", svg_atts, el( "path", {
				d: "M4 19.8h8.9v-1.5H4v1.5zm8.9-15.6H4v1.5h8.9V4.2zm-8.9 7v1.5h16v-1.5H4z"
			} ) ),
			textAlignCenter: el( "svg", svg_atts, el( "path", {
				d: "M16.4 4.2H7.6v1.5h8.9V4.2zM4 11.2v1.5h16v-1.5H4zm3.6 8.6h8.9v-1.5H7.6v1.5z"
			} ) ),
			textAlignRight: el( "svg", svg_atts, el( "path", {
				d: "M11.1 19.8H20v-1.5h-8.9v1.5zm0-15.6v1.5H20V4.2h-8.9zM4 12.8h16v-1.5H4v1.5z"
			} ) ),
			// align
			alignLeft: el( "svg", svg_atts, el( "path", {
				d: "M9 9v6h11V9H9zM4 20h1.5V4H4v16z"
			} ) ),
			alignCenter: el( "svg", svg_atts, el( "path", {
				d: "M20 9h-7.2V4h-1.6v5H4v6h7.2v5h1.6v-5H20z"
			} ) ),
			alignRight: el( "svg", svg_atts, el( "path", {
				d: "M4 15h11V9H4v6zM18.5 4v16H20V4h-1.5z"
			} ) ),
			alignSpaceBetween: el( "svg", svg_atts, el( "path", {
				d: "M9 15h6V9H9v6zm-5 5h1.5V4H4v16zM18.5 4v16H20V4h-1.5z"
			} ) ),
			// justify
			justifyTop: el( "svg", svg_atts, el( "path", {
				d: "M15,20V9H9v11H15z M4,5.5h16V4H4V5.5z"
			} ) ),
			justifyCenter: el( "svg", svg_atts, el( "path", {
				d: "M15,20v-7.2h5v-1.6h-5V4H9v7.2H4v1.6h5V20H15z"
			} ) ),
			justifyBottom: el( "svg", svg_atts, el( "path", {
				d: "M9,4v11h6V4H9z M20,18.5H4V20h16V18.5z"
			} ) ),
			justifySpaceBetween: el( "svg", svg_atts, el( "path", {
				d: "M9,9v6h6V9H9z M4,4v1.5h16V4H4z M20,18.5H4V20h16V18.5z"
			} ) ),
			// table
			table: el( "svg", svg_atts, el( "path", {
				d: "M4 6v11.5h16V6H4zm1.5 1.5h6V11h-6V7.5zm0 8.5v-3.5h6V16h-6zm13 0H13v-3.5h5.5V16zM13 11V7.5h5.5V11H13z"
			} ) ),
			tableColumnBefore: el( "svg", svg_atts, el( "path", {
				d: "M6.4 3.776v3.648H2.752v1.792H6.4v3.648h1.728V9.216h3.712V7.424H8.128V3.776zM0 17.92V0h20.48v17.92H0zM12.8 1.28H1.28v14.08H12.8V1.28zm6.4 0h-5.12v3.84h5.12V1.28zm0 5.12h-5.12v3.84h5.12V6.4zm0 5.12h-5.12v3.84h5.12v-3.84z"
			} ) ),
			tableColumnAfter: el( "svg", svg_atts, el( "path", {
				d: "M14.08 12.864V9.216h3.648V7.424H14.08V3.776h-1.728v3.648H8.64v1.792h3.712v3.648zM0 17.92V0h20.48v17.92H0zM6.4 1.28H1.28v3.84H6.4V1.28zm0 5.12H1.28v3.84H6.4V6.4zm0 5.12H1.28v3.84H6.4v-3.84zM19.2 1.28H7.68v14.08H19.2V1.28z"
			} ) ),
			tableColumnDelete: el( "svg", svg_atts, el( "path", {
				d: "M6.4 9.98L7.68 8.7v-.256L6.4 7.164V9.98zm6.4-1.532l1.28-1.28V9.92L12.8 8.64v-.192zm7.68 9.472V0H0v17.92h20.48zm-1.28-2.56h-5.12v-1.024l-.256.256-1.024-1.024v1.792H7.68v-1.792l-1.024 1.024-.256-.256v1.024H1.28V1.28H6.4v2.368l.704-.704.576.576V1.216h5.12V3.52l.96-.96.32.32V1.216h5.12V15.36zm-5.76-2.112l-3.136-3.136-3.264 3.264-1.536-1.536 3.264-3.264L5.632 5.44l1.536-1.536 3.136 3.136 3.2-3.2 1.536 1.536-3.2 3.2 3.136 3.136-1.536 1.536z"
			} ) ),
			tableColumnMoveLeft: el( "svg", svg_atts, el( "path", {
				d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"
			} ) ),
			tableColumnMoveRight: el( "svg", svg_atts, el( "path", {
				d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z"
			} ) ),
			// custom icons
			// list layout position
			listPositionLeft: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "12", cx: "6" } ),
				el( 'rect', { height: "1.5", width: "9", y: "11.25", x: "11" } )
			] ),
			listPositionTop: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "9", cx: "12" } ),
				el( 'rect', { height: "1.5", width: "14", y: "14", x: "5" } ),
			] ),
			listPositionRight: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "12", cx: "18" } ),
				el( 'rect', { height: "1.5", width: "9", y: "11.25", x: "4" } ),
			] ),
			listPositionBottom: el( "svg", svg_atts, [
				el( 'rect', { height: "1.5", width: "14", y: "9", x: "5" } ),
				el( 'ellipse', { ry: "2", rx: "2", cy: "15.5", cx: "12" } ),
			] ),
			// list layout align y
			listAlignYStart: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "9", cx: "6" } ),
				el( 'rect', { height: "1.5", width: "9", y: "7.25", x: "11" } ),
				el( 'rect', { height: "1.5", width: "9", y: "14.25", x: "11" } ),
			] ),
			listAlignYFirst: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "8", cx: "6" } ),
				el( 'rect', { height: "1.5", width: "9", y: "7.25", x: "11" } ),
				el( 'rect', { height: "1.5", width: "9", y: "14.25", x: "11" } ),
			] ),
			listAlignYCenter: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "11", cx: "6" } ),
				el( 'rect', { height: "1.5", width: "9", y: "7.25", x: "11" } ),
				el( 'rect', { height: "1.5", width: "9", y: "14.25", x: "11" } ),
			] ),
			listAlignYEnd: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "14", cx: "6" } ),
				el( 'rect', { height: "1.5", width: "9", y: "7.25", x: "11" } ),
				el( 'rect', { height: "1.5", width: "9", y: "14.25", x: "11" } ),
			] ),
			// list layout align x
			listAlignXStart: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "10", cx: "7" } ),
				el( 'rect', { height: "1.5", width: "14", y: "15", x: "5" } ),
			] ),
			listAlignXCenter: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "10", cx: "12" } ),
				el( 'rect', { height: "1.5", width: "14", y: "15", x: "5" } ),
			] ),
			listAlignXEnd: el( "svg", svg_atts, [
				el( 'ellipse', { ry: "2", rx: "2", cy: "10", cx: "17" } ),
				el( 'rect', { height: "1.5", width: "14", y: "15", x: "5" } ),
			] ),
			search: el( "svg", svg_atts, [
				el( 'path', { d: "M13.5 6C10.5 6 8 8.5 8 11.5c0 1.1.3 2.1.9 3l-3.4 3 1 1.1 3.4-2.9c1 .9 2.2 1.4 3.6 1.4 3 0 5.5-2.5 5.5-5.5C19 8.5 16.5 6 13.5 6zm0 9.5c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z" } ),
			] ),
			edit: el( "svg", svg_atts, [
				el( 'path', { d: "M20.0668 5.08541L16.8221 1.94543L6.20085 12.671L4.89833 17.0827L9.42571 15.8309L20.0668 5.08541ZM4 20.75H12V19.25H4V20.75Z" } ),
			] ),
			add: el( "svg", svg_atts, [
				el( 'path', { d: "M11.25 12.75V18H12.75V12.75H18V11.25H12.75V6H11.25V11.25H6V12.75H11.25Z" } ),
			] ),
			trash: el( "svg", svg_atts, [
				el( 'path', {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M12 2.71429C10.7376 2.71429 9.71429 3.73764 9.71429 5.00001H4V7.00001H5.46699C5.46718 7.09832 5.47463 7.19803 5.4898 7.29857L7.17174 18.4414C7.3194 19.4196 8.16005 20.1429 9.14934 20.1429H14.8507C15.84 20.1429 16.6806 19.4196 16.8283 18.4414L18.5102 7.29858C18.5254 7.19803 18.5328 7.09832 18.533 7.00001H20V5.00001H14.2857C14.2857 3.73764 13.2624 2.71429 12 2.71429ZM16.8151 7.04271C16.8173 7.02833 16.8183 7.01407 16.8184 7.00001H7.18163C7.18165 7.01407 7.18271 7.02833 7.18489 7.04271L8.86683 18.1855C8.88792 18.3253 9.00801 18.4286 9.14934 18.4286H14.8507C14.992 18.4286 15.1121 18.3253 15.1332 18.1855L16.8151 7.04271Z"
				} ),
			] ),
			media: el( "svg", svg_atts, [
				el( 'path', {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M5.28571 4.5H18.7143C19.1482 4.5 19.5 4.85178 19.5 5.28571V18.7143C19.5 19.1482 19.1482 19.5 18.7143 19.5H5.28571C4.85178 19.5 4.5 19.1482 4.5 18.7143V5.28571C4.5 4.85178 4.85178 4.5 5.28571 4.5ZM3 5.28571C3 4.02335 4.02335 3 5.28571 3H18.7143C19.9767 3 21 4.02335 21 5.28571V18.7143C21 19.9767 19.9767 21 18.7143 21H5.28571C4.02335 21 3 19.9767 3 18.7143V5.28571ZM15 12L10 9V15L15 12Z"
				} ),
			] ),
			//caption
			caption: el( "svg", svg_atts, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M6 5.5h12a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5V6a.5.5 0 0 1 .5-.5ZM4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm4 10h2v-1.5H8V16Zm5 0h-2v-1.5h2V16Zm1 0h2v-1.5h-2V16Z"
			} ) ),
			// lock
			locked: el("svg", svg_atts, el( "path", {
				d: "M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zm-2.8 0H9.8V7c0-1.2 1-2.2 2.2-2.2s2.2 1 2.2 2.2v3z"
			} ) ),
			unlocked: el("svg", svg_atts, el( "path", {
				d: "M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8h1.5c0-1.2 1-2.2 2.2-2.2s2.2 1 2.2 2.2v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1z"
			} ) ),
			// other
			moreVertical: el( "svg", svg_atts, el( "path", {
				d: "M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z"
			} ) ),
			check: el( "svg", svg_atts, el( "path", {
				d: "M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"
			} ) ),
			undo: el( "svg", svg_atts, el( "path", {
				d: "M18.3 11.7c-.6-.6-1.4-.9-2.3-.9H6.7l2.9-3.3-1.1-1-4.5 5L8.5 16l1-1-2.7-2.7H16c.5 0 .9.2 1.3.5 1 1 1 3.4 1 4.5v.3h1.5v-.2c0-1.5 0-4.3-1.5-5.7z"
			} ) ),
			pageList: el( "svg", svg_atts, el( "path", {
				d: "M14.5 5.5h-7V7h7V5.5ZM7.5 9h7v1.5h-7V9Zm7 3.5h-7V14h7v-1.5Z"
			} ), el( "path", {
				d: "M16 2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2ZM6 3.5h10a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5Z"
			} ), el( "path", {
				d: "M20 8v11c0 .69-.31 1-.999 1H6v1.5h13.001c1.52 0 2.499-.982 2.499-2.5V8H20Z"
			} ) ),
		}[icon];
	}


	/**
	 * Get block icons.
	 * @see https://wphelpers.dev/icons
	 */
	this.getBlockIcon = function(icon) {
		const svgAttributes = {
			'width': "24",
			'height': "24",
			'viewBox': "0 0 24 24",
			'xmlns': "http://www.w3.org/2000/svg"
		};
		return {
			accordion: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M4 9.23244C3.4022 8.88663 3 8.24028 3 7.5V6C3 4.89543 3.89543 4 5 4H19C20.1046 4 21 4.89543 21 6V7.5C21 8.24028 20.5978 8.88663 20 9.23244V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V9.23244ZM5 5.5H19C19.2761 5.5 19.5 5.72386 19.5 6V7.5C19.5 7.77614 19.2761 8 19 8H5C4.72386 8 4.5 7.77614 4.5 7.5V6C4.5 5.72386 4.72386 5.5 5 5.5ZM5.5 9.5V18C5.5 18.2761 5.72386 18.5 6 18.5H18C18.2761 18.5 18.5 18.2761 18.5 18V9.5H5.5Z"
			} ) ),
			anchor: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M9.05371 6.64281C9.05371 5.01555 10.3729 3.69641 12.0001 3.69641C13.6274 3.69641 14.9466 5.01555 14.9466 6.64281C14.9466 7.99149 14.0404 9.12852 12.8037 9.4783L12.8037 18.6487C15.8597 18.2832 18.2833 15.8595 18.6488 12.8035H17.2501V11.1964H20.3037V12C20.3037 16.5858 16.586 20.3036 12.0001 20.3036C7.41416 20.3036 3.69653 16.5858 3.69653 12V11.1964H6.7501V12.8035H5.35138C5.71686 15.8595 8.14053 18.2832 11.1965 18.6487L11.1966 9.47829C9.95987 9.12852 9.05371 7.99149 9.05371 6.64281ZM12.0001 5.30354C11.2605 5.30354 10.6608 5.90316 10.6608 6.64281C10.6608 7.38245 11.2605 7.98207 12.0001 7.98207C12.7398 7.98207 13.3394 7.38245 13.3394 6.64281C13.3394 5.90316 12.7398 5.30354 12.0001 5.30354Z"
			} ) ),
			animation: el( "svg", svgAttributes, [
				el( "path", {
					d: "M5.89874 13.246C5.25574 13.1944 4.75 12.6563 4.75 12C4.75 11.3437 5.25574 10.8056 5.89874 10.754C6.02555 10.233 6.23012 9.74242 6.49944 9.29524C6.33748 9.26553 6.17055 9.25 6 9.25C4.48122 9.25 3.25 10.4812 3.25 12C3.25 13.5188 4.48122 14.75 6 14.75C6.17055 14.75 6.33748 14.7345 6.49944 14.7048C6.23012 14.2576 6.02555 13.767 5.89874 13.246Z"
				} ),
				el( "path", {
					d: "M10.0981 14.062C9.30447 13.7143 8.75 12.9219 8.75 12C8.75 11.0781 9.30447 10.2857 10.0981 9.93804C10.3118 9.32643 10.6178 8.75818 10.9995 8.25C8.92868 8.25025 7.25 9.92909 7.25 12C7.25 14.0709 8.92868 15.7498 10.9995 15.75C10.6178 15.2418 10.3118 14.6736 10.0981 14.062Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M11.25 12C11.25 9.37665 13.3766 7.25 16 7.25C18.6234 7.25 20.75 9.37665 20.75 12C20.75 14.6234 18.6234 16.75 16 16.75C13.3766 16.75 11.25 14.6234 11.25 12ZM16 8.75C14.2051 8.75 12.75 10.2051 12.75 12C12.75 13.7949 14.2051 15.25 16 15.25C17.7949 15.25 19.25 13.7949 19.25 12C19.25 10.2051 17.7949 8.75 16 8.75Z"
				} )
			] ),
			box: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M4.25 4.25H10V5.75H5.75V10H4.25V4.25ZM18.25 5.75H14V4.25H19.75V10H18.25V5.75ZM5.75 18.25V14H4.25V19.75H10V18.25H5.75ZM18.25 18.25V14H19.75V19.75H14V18.25H18.25Z"
			} ) ),
			button: el( "svg", svgAttributes, [
				el( "path", {
					d: "M8 12.75H16V11.25H8V12.75Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M19 6.5H5C3.89543 6.5 3 7.39543 3 8.5V15.5C3 16.6046 3.89543 17.5 5 17.5H19C20.1046 17.5 21 16.6046 21 15.5V8.5C21 7.39543 20.1046 6.5 19 6.5ZM5 8H19C19.2761 8 19.5 8.22386 19.5 8.5V15.5C19.5 15.7761 19.2761 16 19 16H5C4.72386 16 4.5 15.7761 4.5 15.5V8.5C4.5 8.22386 4.72386 8 5 8Z"
				} )
			] ),
			buttons: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M17 4.5H7C6.72386 4.5 6.5 4.72386 6.5 5V9C6.5 9.27614 6.72386 9.5 7 9.5H17C17.2761 9.5 17.5 9.27614 17.5 9V5C17.5 4.72386 17.2761 4.5 17 4.5ZM7 3H17C18.1046 3 19 3.89543 19 5V9C19 10.1046 18.1046 11 17 11H7C5.89543 11 5 10.1046 5 9V5C5 3.89543 5.89543 3 7 3ZM14.5 7.75H9.5V6.25H14.5V7.75ZM17 14.5H7C6.72386 14.5 6.5 14.7239 6.5 15V19C6.5 19.2761 6.72386 19.5 7 19.5H17C17.2761 19.5 17.5 19.2761 17.5 19V15C17.5 14.7239 17.2761 14.5 17 14.5ZM7 13H17C18.1046 13 19 13.8954 19 15V19C19 20.1046 18.1046 21 17 21H7C5.89543 21 5 20.1046 5 19V15C5 13.8954 5.89543 13 7 13ZM14.5 17.75H9.5V16.25H14.5V17.75Z"
			} ) ),
			condition: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M8.25 8.35352C7.09575 8.67998 6.25 9.74122 6.25 11C6.25 12.5188 7.48122 13.75 9 13.75C10.5188 13.75 11.75 12.5188 11.75 11C11.75 9.74122 10.9043 8.67998 9.75 8.35352V3H8.25V8.35352ZM9 9.75C8.30964 9.75 7.75 10.3096 7.75 11C7.75 11.6904 8.30964 12.25 9 12.25C9.69036 12.25 10.25 11.6904 10.25 11C10.25 10.3096 9.69036 9.75 9 9.75ZM13 10.25H16.75V15.3535C17.9043 15.68 18.75 16.7412 18.75 18C18.75 19.5188 17.5188 20.75 16 20.75C14.4812 20.75 13.25 19.5188 13.25 18C13.25 16.7412 14.0957 15.68 15.25 15.3535V11.75H13V10.25ZM16 16.75C15.3096 16.75 14.75 17.3096 14.75 18C14.75 18.6904 15.3096 19.25 16 19.25C16.6904 19.25 17.25 18.6904 17.25 18C17.25 17.3096 16.6904 16.75 16 16.75Z"
			} ) ),
			dropdown: el( "svg", svgAttributes, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M5 6.5H19C20.1046 6.5 21 7.39543 21 8.5V15.5C21 16.6046 20.1046 17.5 19 17.5H5C3.89543 17.5 3 16.6046 3 15.5V8.5C3 7.39543 3.89543 6.5 5 6.5ZM19 8H5C4.72386 8 4.5 8.22386 4.5 8.5V15.5C4.5 15.7761 4.72386 16 5 16H19C19.2761 16 19.5 15.7761 19.5 15.5V8.5C19.5 8.22386 19.2761 8 19 8Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M16 11.9393L14.5303 10.4697L13.4697 11.5303L16 14.0607L18.5303 11.5303L17.4697 10.4697L16 11.9393Z"
				} )
			] ),
			dynamic: el( "svg", {
				width: 24,
				height: 24,
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg",
				className: "greyd-dynamic-template-block-icon"
			}, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M12.0038 3.13574L19.7464 7.57019V16.4652L12.0038 20.8626L4.26123 16.4652V7.57019L12.0038 3.13574ZM12.7537 18.7116L18.2464 15.5921V9.27025L12.7537 12.4476V18.7116ZM12.0038 4.86434L17.4625 7.99077L12.0038 11.1485L6.54502 7.99078L12.0038 4.86434Z"
				} )
			] ),
			form: el( "svg", svgAttributes, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M3.25 5C3.25 4.0335 4.0335 3.25 5 3.25H15C15.9665 3.25 16.75 4.0335 16.75 5V14H15.25V5C15.25 4.86193 15.1381 4.75 15 4.75H5C4.86193 4.75 4.75 4.86193 4.75 5V18C4.75 18.1381 4.86193 18.25 5 18.25H10V19.75H5C4.0335 19.75 3.25 18.9665 3.25 18V5Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M20.5203 15.0402L14.8094 20.5414L11.9797 17.8156L13.0203 16.7353L14.8094 18.4586L19.4797 13.9598L20.5203 15.0402Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M13.5 8.25H6.5V6.75H13.5V8.25Z"
				} ),
				el( "path", {
					d: "M8.2616 11.0076C8.2616 11.5599 7.81389 12.0076 7.2616 12.0076C6.70932 12.0076 6.2616 11.5599 6.2616 11.0076C6.2616 10.4553 6.70932 10.0076 7.2616 10.0076C7.81389 10.0076 8.2616 10.4553 8.2616 11.0076Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M12.5 11.75H9.5V10.25H12.5V11.75Z"
				} )
			] ),
			hotspotItem: el( "svg", svgAttributes, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M11.9999 6.25C11.3095 6.25 10.7499 6.80964 10.7499 7.5C10.7499 8.19036 11.3095 8.75 11.9999 8.75C12.6902 8.75 13.2499 8.19036 13.2499 7.5C13.2499 6.80964 12.6902 6.25 11.9999 6.25ZM9.24988 7.5C9.24988 5.98122 10.4811 4.75 11.9999 4.75C13.5187 4.75 14.7499 5.98122 14.7499 7.5C14.7499 9.01878 13.5187 10.25 11.9999 10.25C10.4811 10.25 9.24988 9.01878 9.24988 7.5Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M6 18.5C6 19.0523 6.44772 19.5 7 19.5H17C17.5523 19.5 18 19.0523 18 18.5V14.7998C18 14.2475 17.5523 13.7998 17 13.7998H14.1429L12.7941 12.0376C12.3938 11.5146 11.6062 11.5146 11.2059 12.0376L9.85714 13.7998H7C6.44772 13.7998 6 14.2475 6 14.7998V18.5Z"
				} )
			] ),
			hotspot: el( "svg", svgAttributes, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M11.9999 6.25C11.3095 6.25 10.7499 6.80964 10.7499 7.5C10.7499 8.19036 11.3095 8.75 11.9999 8.75C12.6902 8.75 13.2499 8.19036 13.2499 7.5C13.2499 6.80964 12.6902 6.25 11.9999 6.25ZM9.24988 7.5C9.24988 5.98122 10.4811 4.75 11.9999 4.75C13.5187 4.75 14.7499 5.98122 14.7499 7.5C14.7499 9.01878 13.5187 10.25 11.9999 10.25C10.4811 10.25 9.24988 9.01878 9.24988 7.5Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M5.75 18.5001C5.75 19.1904 6.30964 19.7501 7 19.7501H17C17.6904 19.7501 18.25 19.1904 18.25 18.5001V15.0001C18.25 14.3097 17.6904 13.7501 17 13.7501H14.3605L12.7809 11.7756C12.3805 11.2752 11.6195 11.2752 11.2191 11.7756L9.63953 13.7501H7C6.30964 13.7501 5.75 14.3097 5.75 15.0001V18.5001ZM7.25 18.2501V15.2501H10.3605L12 13.2007L13.6395 15.2501H16.75V18.2501H7.25Z"
				} )
			] ),
			iframe: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M4.5 9.81504V19C4.5 19.2761 4.72386 19.5 5 19.5H19C19.2761 19.5 19.5 19.2761 19.5 19V5C19.5 4.72386 19.2761 4.5 19 4.5H9.22448L4.5 9.81504ZM3 5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5ZM17.5303 11.4697L14.5303 8.46967L13.4697 9.53033L15.9629 12.0236L13.4461 14.78L14.5539 15.7914L17.5539 12.5057L18.0371 11.9764L17.5303 11.4697ZM9.46967 8.46967L6.46967 11.4697L5.9629 11.9764L6.44614 12.5057L9.44614 15.7914L10.5539 14.78L8.0371 12.0236L10.5303 9.53033L9.46967 8.46967Z"
			} ) ),
			image: el( 'svg', svgAttributes, el( 'path', {
				d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z"
			} ) ),
			input: el( "svg", svgAttributes, [
				el( "rect", {
					x: "4.75",
					y: "17.25",
					width: "5.5",
					height: "14.5",
					transform: "rotate(-90 4.75 17.25)",
					stroke: "#1E1E1E",
					strokeWidth: "1.5"
				} ),
				el( "rect", {
					x: "4",
					y: "7",
					width: "10",
					height: "1.5",
				} )
			] ),
			listItem: el( "svg", svgAttributes, [
				el( "path", {
					d: "M12.0001 12.5001H20.0001V11.0001H12.0001V12.5001Z",
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M9.52037 10.5403L4.84754 15.0415L2.47974 12.7606L3.52037 11.6803L4.84754 12.9588L8.47974 9.45996L9.52037 10.5403Z",
				} )
			] ),
			list: el( "svg", svgAttributes, [
				el( "path", {
					d: "M4.00005 5.5H20.0001V4H4.00005V5.5ZM12.0001 12.5H20.0001V11H12.0001V12.5ZM4.00005 20V18.5H20.0001V20H4.00005Z",
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M9.52037 10.5402L4.84754 15.0414L2.47974 12.7605L3.52037 11.6802L4.84754 12.9586L8.47974 9.45984L9.52037 10.5402Z",
				} )
			] ),
			login: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M7.75 17V15.5H9.25V17C9.25 17.2761 9.47386 17.5 9.75 17.5H16.75C17.0261 17.5 17.25 17.2761 17.25 17V7C17.25 6.72386 17.0261 6.5 16.75 6.5L9.75 6.5C9.47386 6.5 9.25 6.72386 9.25 7V8.5L7.75 8.5V7C7.75 5.89543 8.64543 5 9.75 5H16.75C17.8546 5 18.75 5.89543 18.75 7V17C18.75 18.1046 17.8546 19 16.75 19H9.75C8.64543 19 7.75 18.1046 7.75 17ZM12.0303 8.46967L10.9697 9.53033L12.6893 11.25L5 11.25V12.75L12.6893 12.75L10.9697 14.4697L12.0303 15.5303L15.5607 12L12.0303 8.46967Z"
			} ) ),
			menu: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M18.5 12C18.5 15.5899 15.5899 18.5 12 18.5C8.41015 18.5 5.5 15.5899 5.5 12C5.5 8.41015 8.41015 5.5 12 5.5C15.5899 5.5 18.5 8.41015 18.5 12ZM20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12ZM14.9522 8.36511L10.5018 11.3502L9.04777 16.0467L13.5036 13.0934L14.9522 8.36511Z"
			} ) ),
			popover: el( "svg", svgAttributes, [
				el( "path", {
					d: "M8.5 16.25L8.84033 16.25L9.06443 16.5061L11.8119 19.646C11.9115 19.7599 12.0885 19.7599 12.1881 19.646L14.9356 16.5061L15.1597 16.25L15.5 16.25L19 16.25C19.1381 16.25 19.25 16.1381 19.25 16L19.25 5C19.25 4.86193 19.1381 4.75 19 4.75L5 4.75C4.86193 4.75 4.75 4.86193 4.75 5L4.75 16C4.75 16.1381 4.86193 16.25 5 16.25L8.5 16.25Z",
					strokeWidth: "1.5"
				} )
			] ),
			close: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M12 13.0607L15.7123 16.773L16.773 15.7123L13.0607 12L16.773 8.28772L15.7123 7.22706L12 10.9394L8.28771 7.22705L7.22705 8.28771L10.9394 12L7.22706 15.7123L8.28772 16.773L12 13.0607Z"
			} ) ),
			query: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M18.1823 11.6392C18.1823 13.0804 17.0139 14.2487 15.5727 14.2487C14.3579 14.2487 13.335 13.4179 13.0453 12.2922L13.0377 12.2625L13.0278 12.2335L12.3985 10.377L12.3942 10.3785C11.8571 8.64997 10.246 7.39405 8.33961 7.39405C5.99509 7.39405 4.09448 9.29465 4.09448 11.6392C4.09448 13.9837 5.99509 15.8843 8.33961 15.8843C8.88499 15.8843 9.40822 15.781 9.88943 15.5923L9.29212 14.0697C8.99812 14.185 8.67729 14.2487 8.33961 14.2487C6.89838 14.2487 5.73003 13.0804 5.73003 11.6392C5.73003 10.1979 6.89838 9.02959 8.33961 9.02959C9.55444 9.02959 10.5773 9.86046 10.867 10.9862L10.8772 10.9836L11.4695 12.7311C11.9515 14.546 13.6048 15.8843 15.5727 15.8843C17.9172 15.8843 19.8178 13.9837 19.8178 11.6392C19.8178 9.29465 17.9172 7.39404 15.5727 7.39404C15.0287 7.39404 14.5066 7.4968 14.0264 7.6847L14.6223 9.20781C14.9158 9.093 15.2358 9.02959 15.5727 9.02959C17.0139 9.02959 18.1823 10.1979 18.1823 11.6392Z"
			} ) ),
			postTempate: el( "svg", svgAttributes, el( "path", {
				d: "M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"
			} ) ),
			postSlider: el( "svg", svgAttributes, [
				el( "path", {
					style: { scale: "0.9" },
					d: "M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"
				} ),
				el( "path", {
					d: "M20 8v11c0 .69-.31 1-.999 1H6v1.5h13.001c1.52 0 2.499-.982 2.499-2.5V8H20Z"
				} )
			] ),
			search: el( "svg", svgAttributes, [
				el( 'path', { d: "M13.5 6C10.5 6 8 8.5 8 11.5c0 1.1.3 2.1.9 3l-3.4 3 1 1.1 3.4-2.9c1 .9 2.2 1.4 3.6 1.4 3 0 5.5-2.5 5.5-5.5C19 8.5 16.5 6 13.5 6zm0 9.5c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z" } ),
			] ),
			table: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M5 4.5H19C19.2761 4.5 19.5 4.72386 19.5 5V8.5H4.5V5C4.5 4.72386 4.72386 4.5 5 4.5ZM4.5 10V13.5H11.5V10H4.5ZM13 10V13.5H19.5V10H13ZM11.5 15H4.5V19C4.5 19.2761 4.72386 19.5 5 19.5H11.5V15ZM13 19.5V15H19.5V19C19.5 19.2761 19.2761 19.5 19 19.5H13ZM19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z"
			} ) ),
			tab: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M5 9.75C4.86193 9.75 4.75 9.86193 4.75 10V13.0001C4.75 13.9666 3.9665 14.7501 3 14.7501H2.25V13.2501H3C3.13807 13.2501 3.25 13.1381 3.25 13.0001V10C3.25 9.0335 4.0335 8.25 5 8.25H11C11.8816 8.25 12.611 8.90193 12.7323 9.75H17.5C18.4665 9.75 19.25 10.5335 19.25 11.5V13.2501H20.75V14.7501H13C12.0335 14.7501 11.25 13.9666 11.25 13.0001V10C11.25 9.86193 11.1381 9.75 11 9.75H5ZM12.75 11.25V13.0001C12.75 13.1381 12.8619 13.2501 13 13.2501H17.75V11.5C17.75 11.3619 17.6381 11.25 17.5 11.25H12.75Z"
			} ) ),
			tabs: el( "svg", svgAttributes, el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M18.9103 8.24994L18.6087 7.07193C18.2972 5.85507 17.2007 5.00396 15.9446 5.00397L11.1821 5.00401C10.8322 4.39452 10.1769 3.99994 9.44753 3.99994H5C3.89543 3.99994 3 4.89537 3 5.99994V17.7499C3 18.8545 3.89543 19.7499 5 19.7499H19C20.1046 19.7499 21 18.8545 21 17.7499V10.2499C21 9.14537 20.1046 8.24994 19 8.24994H18.9103ZM9.93191 5.87593L10.9237 9.74994H19C19.2761 9.74994 19.5 9.9738 19.5 10.2499V17.7499C19.5 18.0261 19.2761 18.2499 19 18.2499H5C4.72386 18.2499 4.5 18.0261 4.5 17.7499V5.99994C4.5 5.7238 4.72386 5.49994 5 5.49994H9.44753C9.67591 5.49994 9.87526 5.65469 9.93191 5.87593ZM17.3619 8.24994H12.0881L11.6411 6.50401L15.9446 6.50397C16.5155 6.50396 17.0139 6.89083 17.1555 7.44395L17.3619 8.24994Z"
			} ) ),
			// woocommerce
			woo: el( "svg", { ...svgAttributes, 'viewBox': "0 0 60 60" } , el( "path", {
				d: "M7.35741113 21.5127354 C7.6482476 21.1208063 8.08451228 20.9145143 8.66618522 20.8732638 C9.7256652 20.7907509 10.3281193 21.2858281 10.4735475 22.3584991 C11.117544 26.669878 11.823864 30.3209717 12.5718261 33.3121767 L17.1213237 24.7102324 C17.5368073 23.9263544 18.0561567 23.5137901 18.6793919 23.4725396 C19.5934637 23.410654 20.1543554 23.98824 20.3828684 25.2053175 C20.9022178 27.9489403 21.5669954 30.27986 22.3564201 32.2601209 C22.8965507 27.0204576 23.8106224 23.2456719 25.0986952 20.9145539 C25.4103128 20.3369679 25.8673387 20.0481749 26.4697928 20.0069046 C26.9475998 19.9656482 27.3838645 20.1100467 27.7785668 20.4194689 C28.1732692 20.728897 28.3810209 21.1208261 28.4225633 21.5952761 C28.4433365 21.9665898 28.381015 22.2759982 28.2148116 22.5854263 C27.4046257 24.0706617 26.7398481 26.5667617 26.1997774 30.0321192 C25.680428 33.3945983 25.4934574 36.0143309 25.6181044 37.8915152 C25.6596528 38.4072156 25.5765561 38.8610501 25.3688103 39.2529793 C25.1195162 39.706794 24.7455751 39.9543365 24.267788 39.995587 C23.7276574 40.0368434 23.1667657 39.7893148 22.6266351 39.2323441 C20.6946457 37.2726589 19.1573188 34.3435378 18.0356151 30.4446637 C16.6852986 33.0850116 15.688202 35.0654707 15.0442056 36.3856447 C13.8185362 38.7165644 12.7798374 39.9130464 11.9072681 39.9748924 C11.3463564 40.0161488 10.8685493 39.541693 10.4530658 38.5515427 C9.39358581 35.8491506 8.25098118 30.6303008 7.02527186 22.894795 C6.94217515 22.3584595 7.06682021 21.8840096 7.35765069 21.5126958 L7.35741113 21.5127354 Z M52.0019767 24.7513243 C51.2541143 23.4517459 50.153072 22.6677885 48.6781884 22.3583604 C48.283486 22.2758475 47.9095449 22.2345911 47.5563849 22.2345911 C45.562072 22.2345911 43.9417401 23.2660117 42.6745084 25.3288727 C41.5942473 27.0822659 41.0541167 29.0213953 41.0541167 31.1459635 C41.0541167 32.7343349 41.3864955 34.0957395 42.0512732 35.2303755 C42.7991355 36.529954 43.9001778 37.3139113 45.3750615 37.6233395 C45.7697638 37.7058523 46.143705 37.7471088 46.496865 37.7471088 C48.5118991 37.7471088 50.1322709 36.7156882 51.3787414 34.6528272 C52.4590026 32.8787988 52.9991331 30.9396893 52.9991331 28.8151211 C53.0199063 27.2061145 52.6667543 25.865345 52.0019767 24.7513243 Z M49.3844684 30.4653385 C49.093632 31.8268026 48.5742826 32.8374889 47.805639 33.5183894 C47.2031849 34.054725 46.6422932 34.2816323 46.1229238 34.1784962 C45.6243356 34.0753542 45.2088521 33.6421607 44.8972544 32.8376673 C44.6479603 32.1981956 44.5233133 31.558724 44.5233133 30.9605028 C44.5233133 30.4448025 44.5648616 29.9290822 44.6687335 29.4546522 C44.8557021 28.6088885 45.2088641 27.7837599 45.7697558 26.9998422 C46.4552946 25.9890568 47.1823958 25.5764925 47.930318 25.7208989 C48.4289062 25.824041 48.8443898 26.2572345 49.1559874 27.0617279 C49.4052815 27.7011995 49.5299286 28.3406712 49.5299286 28.9388923 C49.5299286 29.4752279 49.4883802 29.9909282 49.3845084 30.4653781 L49.3844684 30.4653385 Z M38.9972806 24.7513243 C38.2494183 23.4517459 37.1275948 22.6677885 35.6734923 22.3583604 C35.27879 22.2758475 34.9048488 22.2345911 34.5516888 22.2345911 C32.5573759 22.2345911 30.9370441 23.2660117 29.6698124 25.3288727 C28.5895512 27.0822659 28.0494207 29.0213953 28.0494207 31.1459635 C28.0494207 32.7343349 28.3817995 34.0957395 29.0465771 35.2303755 C29.7944395 36.529954 30.8954818 37.3139113 32.3703654 37.6233395 C32.7650678 37.7058523 33.1390089 37.7471088 33.4921689 37.7471088 C35.5072031 37.7471088 37.1275749 36.7156882 38.3740454 34.6528272 C39.4543065 32.8787988 39.9944371 30.9396893 39.9944371 28.8151211 C39.9944371 27.2061145 39.6620583 25.865345 38.9972806 24.7513243 L38.9972806 24.7513243 Z M36.3590112 30.4653385 C36.0681747 31.8268026 35.5488253 32.8374889 34.7801818 33.5183894 C34.1777277 34.054725 33.6168359 34.2816323 33.0974666 34.1784962 C32.5988784 34.0753542 32.1833948 33.6421607 31.8717972 32.8376673 C31.6225031 32.1981956 31.497856 31.558724 31.497856 30.9605028 C31.497856 30.4448025 31.5394044 29.9290822 31.6432762 29.4546522 C31.8302428 28.6088885 32.1834068 27.7837599 32.7442986 26.9998422 C33.4298374 25.9890568 34.1569386 25.5764925 34.9048608 25.7208989 C35.403449 25.824041 35.8189325 26.2572345 36.1305302 27.0617279 C36.3798243 27.7011995 36.5044713 28.3406712 36.5044713 28.9388923 C36.5252445 29.4752279 36.462923 29.9909282 36.3590511 30.4653781 L36.3590112 30.4653385 Z"
			} ) ),
			woo_de: el( "svg", { ...svgAttributes, 'viewBox': "0 0 60 60" }, [
				el( "path", {
					fill: "#000000",
					d: "M7.35741113 21.5127354 C7.6482476 21.1208063 8.08451228 20.9145143 8.66618522 20.8732638 C9.7256652 20.7907509 10.3281193 21.2858281 10.4735475 22.3584991 C11.117544 26.669878 11.823864 30.3209717 12.5718261 33.3121767 L17.1213237 24.7102324 C17.5368073 23.9263544 18.0561567 23.5137901 18.6793919 23.4725396 C19.5934637 23.410654 20.1543554 23.98824 20.3828684 25.2053175 C20.9022178 27.9489403 21.5669954 30.27986 22.3564201 32.2601209 C22.8965507 27.0204576 23.8106224 23.2456719 25.0986952 20.9145539 C25.4103128 20.3369679 25.8673387 20.0481749 26.4697928 20.0069046 C26.9475998 19.9656482 27.3838645 20.1100467 27.7785668 20.4194689 C28.1732692 20.728897 28.3810209 21.1208261 28.4225633 21.5952761 C28.4433365 21.9665898 28.381015 22.2759982 28.2148116 22.5854263 C27.4046257 24.0706617 26.7398481 26.5667617 26.1997774 30.0321192 C25.680428 33.3945983 25.4934574 36.0143309 25.6181044 37.8915152 C25.6596528 38.4072156 25.5765561 38.8610501 25.3688103 39.2529793 C25.1195162 39.706794 24.7455751 39.9543365 24.267788 39.995587 C23.7276574 40.0368434 23.1667657 39.7893148 22.6266351 39.2323441 C20.6946457 37.2726589 19.1573188 34.3435378 18.0356151 30.4446637 C16.6852986 33.0850116 15.688202 35.0654707 15.0442056 36.3856447 C13.8185362 38.7165644 12.7798374 39.9130464 11.9072681 39.9748924 C11.3463564 40.0161488 10.8685493 39.541693 10.4530658 38.5515427 C9.39358581 35.8491506 8.25098118 30.6303008 7.02527186 22.894795 C6.94217515 22.3584595 7.06682021 21.8840096 7.35765069 21.5126958 L7.35741113 21.5127354 Z"
				} ),
				el( "path", {
					fill: "#C8B906",
					d: "M52.0019767 24.7513243 C52.6667543 25.865345 53.0199063 27.2061145 52.9991331 28.8151211 C52.9991331 30.9396893 52.4590026 32.8787988 51.3787414 34.6528272 C50.1322709 36.7156882 48.5118991 37.7471088 46.496865 37.7471088 C46.143705 37.7471088 45.7697638 37.7058523 45.3750615 37.6233395 C43.9001778 37.3139113 42.7991355 36.529954 42.0512732 35.2303755 C41.3864955 34.0957395 41.0541167 32.7343349 41.0541167 31.1459635 C41.0541167 29.0213953 41.5942473 27.0822659 42.6745084 25.3288727 C43.9417401 23.2660117 45.562072 22.2345911 47.5563849 22.2345911 C47.9095449 22.2345911 48.283486 22.2758475 48.6781884 22.3583604 C50.153072 22.6677885 51.2541143 23.4517459 52.0019767 24.7513243 Z M49.3844684 30.4653385 L49.3845084 30.4653781 C49.4883802 29.9909282 49.5299286 29.4752279 49.5299286 28.9388923 C49.5299286 28.3406712 49.4052815 27.7011995 49.1559874 27.0617279 C48.8443898 26.2572345 48.4289062 25.824041 47.930318 25.7208989 C47.1823958 25.5764925 46.4552946 25.9890568 45.7697558 26.9998422 C45.2088641 27.7837599 44.8557021 28.6088885 44.6687335 29.4546522 C44.5648616 29.9290822 44.5233133 30.4448025 44.5233133 30.9605028 C44.5233133 31.558724 44.6479603 32.1981956 44.8972544 32.8376673 C45.2088521 33.6421607 45.6243356 34.0753542 46.1229238 34.1784962 C46.6422932 34.2816323 47.2031849 34.054725 47.805639 33.5183894 C48.5742826 32.8374889 49.093632 31.8268026 49.3844684 30.4653385 Z"
				} ),
				el( "path", {
					fill: "#DD0000",
					d: "M38.9972806 24.7513243 C39.6620583 25.865345 39.9944371 27.2061145 39.9944371 28.8151211 C39.9944371 30.9396893 39.4543065 32.8787988 38.3740454 34.6528272 C37.1275749 36.7156882 35.5072031 37.7471088 33.4921689 37.7471088 C33.1390089 37.7471088 32.7650678 37.7058523 32.3703654 37.6233395 C30.8954818 37.3139113 29.7944395 36.529954 29.0465771 35.2303755 C28.3817995 34.0957395 28.0494207 32.7343349 28.0494207 31.1459635 C28.0494207 29.0213953 28.5895512 27.0822659 29.6698124 25.3288727 C30.9370441 23.2660117 32.5573759 22.2345911 34.5516888 22.2345911 C34.9048488 22.2345911 35.27879 22.2758475 35.6734923 22.3583604 C37.1275948 22.6677885 38.2494183 23.4517459 38.9972806 24.7513243 Z M36.3590112 30.4653385 L36.3590511 30.4653781 C36.462923 29.9909282 36.5252445 29.4752279 36.5044713 28.9388923 C36.5044713 28.3406712 36.3798243 27.7011995 36.1305302 27.0617279 C35.8189325 26.2572345 35.403449 25.824041 34.9048608 25.7208989 C34.1569386 25.5764925 33.4298374 25.9890568 32.7442986 26.9998422 C32.1834068 27.7837599 31.8302428 28.6088885 31.6432762 29.4546522 C31.5394044 29.9290822 31.497856 30.4448025 31.497856 30.9605028 C31.497856 31.558724 31.6225031 32.1981956 31.8717972 32.8376673 C32.1833948 33.6421607 32.5988784 34.0753542 33.0974666 34.1784962 C33.6168359 34.2816323 34.1777277 34.054725 34.7801818 33.5183894 C35.5488253 32.8374889 36.0681747 31.8268026 36.3590112 30.4653385 Z"
				} ),
			] ),

			// used in backgrounds preview - return data string instead of react el
			dyn_img: 'data:image/svg+xml;utf8,'+
					'<svg width="240" height="240" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg">'+
					'<path d="M119.988 96C152.297 96 178.488 87.2696 178.488 76.5C178.488 65.7304 152.297 57 119.988 57C87.6796 57 61.4883 65.7304 61.4883 76.5C61.4883 87.2696 87.6796 96 119.988 96Z" stroke="white" stroke-width="7.85714" stroke-linecap="round" stroke-linejoin="round"/>'+
					'<path d="M178.488 122C178.488 132.79 152.488 141.5 119.988 141.5C87.4883 141.5 61.4883 132.79 61.4883 122" stroke="white" stroke-width="7.85714" stroke-linecap="round" stroke-linejoin="round"/>'+
					'<path d="M61.4883 76.501V167.501C61.4883 178.291 87.4883 187.001 119.988 187.001C152.488 187.001 178.488 178.291 178.488 167.501V76.501" stroke="white" stroke-width="7.85714" stroke-linecap="round" stroke-linejoin="round"/>'+
				'</svg>',
		}[icon];
	}

	this.getForms = function(form_id) {
		// get forms options
		return greyd.dynamic.getForms(form_id);
	}


	/**
	 * Whether the WoooCommerce Germanized plugin is active.
	 * @returns bool
	 */
	this.wooGermanizedPluginActive = function() {
		return this.isPluginActive( "woocommerce-germanized/woocommerce-germanized.php" );
	}
	
	this.isPluginActive = function( plugin ) {
		return (
			typeof greyd.data.plugins === 'object' &&
			greyd.data.plugins !== null  &&
			Object.values(greyd.data.plugins).includes( plugin )
		);
	}

	//
	// helper
	//

	this.isChildOf = function(clientId, parent) {
		var parentBlocks = wp.data.select( 'core/block-editor' ).getBlockParents(clientId);
		var parentAtts = wp.data.select('core/block-editor').getBlocksByClientId(parentBlocks);
		for (var i=parentAtts.length-1; i>-1; i--) {
			// console.log(parentAtts[i]);
			if (parentAtts[i].name == parent) 
				return parentAtts[i];
		}
		return false;
	}

	/**
	 * Check if a block has child blocks
	 * @see "https://github.com/WordPress/gutenberg/blob/430bf47e5e796d8c326f1cff1bcb161b4bae42ca/packages/block-library/src/column/edit.js"
	 */
	this.hasChildBlocks = function(clientId) {
		return wp.data.useSelect( select => {
			const { getBlockOrder } = select( wp.blockEditor.store );
			return getBlockOrder( clientId ).length > 0;
		}, [ clientId ] );
	}

	/**
	 * search for attribute in blocks
	 * @param {array} blocks array of blocks to search in
	 * @param {string} attribute the attribute to search for
	 * @returns array 
	 */
	this.searchAttribute = function(blocks, attribute) {
		var a = [];
		for (var i=0; i<blocks.length; i++) {
			if (_.has(blocks[i], 'attributes') && 
				_.has(blocks[i].attributes, attribute) && 
				!_.isEmpty(blocks[i].attributes[attribute]) ) {
				var val = blocks[i].attributes[attribute];
				var block_type = wp.blocks.getBlockType(blocks[i].name);
				// console.log(block_type);
				if (attribute == 'anchor' || attribute == 'popoverName') {
					if (val.indexOf('block-') !== 0) {
						a.push( { id: val, title: { rendered: val+' ('+block_type.title+')' } } );
					}
				}
				if (attribute == 'trigger_event') {
					if (_.has(val, 'actions')) {
						for (var j=0; j<val.actions.length; j++) {

							if ( _.isEmpty(val.actions[j].name) || _.isEmpty(val.actions[j].action) ) continue;

							// var slug = _.kebabCase(val.actions[j].name).split('-').join('_');
							var slug = val.actions[j].name.replace(/\s/g, '_').replace('-', '_'); //.toLowerCase();
					
							var action = val.actions[j].action;
							if (action == 'show') action = __("Show", 'greyd_hub');
							if (action == 'hide') action = __("Hide", 'greyd_hub');
							if (action == 'toggle') action = __("Hide and show", 'greyd_hub');
							if (action == 'fadeIn') action = __("Fade in", 'greyd_hub');
							if (action == 'fadeOut') action = __("Fade out", 'greyd_hub');
							if (action == 'fadeToggle') action = __("Fade in and out", 'greyd_hub');
							if (action == 'slideDown') action = __("Open", 'greyd_hub');
							if (action == 'slideUp') action = __("Close", 'greyd_hub');
							if (action == 'slideToggle') action = __("Open and close", 'greyd_hub');
							var label = val.actions[j].name+' ('+block_type.title+': '+action+')';
							a.push( { id: slug, title: { rendered: label } } );
						}
					}
				}
			}
			if (_.has(blocks[i], 'innerBlocks') && blocks[i].innerBlocks.length > 0) {
				a = a.concat(greyd.tools.searchAttribute(blocks[i].innerBlocks, attribute));
			}
			else {
				// core/post-content, core/template-part and other blocks with children but no innerBlocks
				// console.log(blocks[i]);
				var inner = wp.data.select("core/block-editor").getBlockOrder(blocks[i].clientId);
				if ( inner && inner.length > 0 ) {
					var innerblocks = wp.data.select('core/block-editor').getBlocksByClientId(inner);
					if ( innerblocks && innerblocks.length > 0 ) {
						a = a.concat(greyd.tools.searchAttribute(innerblocks, attribute));
					}
				}
			}
		}
		return a;
	}

	/**
	 * get current Block Attributes Values from attributes and defaults.
	 * @param {object} defaults 
	 * @param {object} attributes 
	 * @returns object
	 */
	this.getValues = function(defaults, attributes) {
		var vals = JSON.parse(JSON.stringify(defaults));
		for (var [key, value] of Object.entries(defaults)) {
			if (typeof value === 'object') {
				// console.log(key+' ->');
				if (key == 'background') 
					vals[key] = greyd.tools.getValues(value, attributes);
				else if (_.has(attributes, key)) {
					if (key == 'greydStyles' || key == 'customStyles') vals[key] = attributes[key]
					else vals[key] = greyd.tools.getValues(value, attributes[key]);
				}
			}
			else if (_.has(attributes, key)) {
				// console.log(key+' - '+value+' - '+attributes[key]);
				if (value != attributes[key]) 
					vals[key] = attributes[key];
			}
		}
		return vals;
	}
	/**
	 * deprecated - use greyd.tools.getValues()
	 */
	this.getBackgroundvalues = function(defaults, attributes) {
		return this.getValues(defaults, attributes);
	}

	/**
	 * set Block Attributes Values from defaults and actual attributes.
	 * @param {object} defaults 
	 * @param {object} attributes 
	 * @returns 
	 */
	this.makeValues = function(defaults, attributes) {
		var vals = {};
		for (var [key, value] of Object.entries(defaults)) {
			if (typeof value === 'object') {
				// console.log(key+' ->');
				if (key == 'greydStyles' || key == 'customStyles') vals[key] = attributes[key];
				else {
					var sub = greyd.tools.makeValues(value, attributes[key]);
					if (!_.isEmpty(sub)) {
						if (key == 'background') 
							vals = {...vals, ...sub};
						else vals[key] = sub;
					}
				}
			}
			else if (_.has(attributes, key)) {
				// console.log(key+' - '+value+' - '+attributes[key]);
				if (value != attributes[key]) 
					vals[key] = attributes[key];
			}
		}
		return vals;
	}
	/**
	 * deprecated - use greyd.tools.makeValues()
	 */
	this.makeBackgroundvalues = function(defaults, attributes) {
		return this.makeValues(defaults, attributes);
	}

	/**
	 * Trigger a snackbar
	 * @param {string} text Text of the snackbar
	 * @param {string} style Can be one of: success, info, warning, error.
	 * @param {bool} isDismissible Whether the snackbar has a close icon
	 */
	this.showSnackbar = function(text, style, isDismissible) {
		if ( _.isEmpty(text) ) return;

		style = typeof style === 'undefined' ? 'info' : style;
		isDismissible = typeof isDismissible === 'undefined' ? true : isDismissible;

		const icon = { success: '✅', warning: '⚠️', error: '❌', info: null }[ style ];

		wp.data.dispatch("core/notices").createNotice(
			style,
			text,
			{
				type: "snackbar",
				isDismissible: false,
				icon: icon
			}
		);
	}

	/**
	 * Filter html tags
	 * @param {string} value Raw input including html-elements
	 * @returns {string} filtered value
	 */
	this.stripTags = function(value) {
		return value.replace(/(<([^>]+)>)/gi, '');
	}

	/**
	 * Compares two version number strings.
	 * 
	 * @param {string} a		First version number.
	 * @param {string} b		Second version number.
	 * @param {string|null} op	Optional operator: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
	 * @returns {int|bool}		-1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
	 * 							Using the operator: the function will return true if the relationship is the one specified by the operator, false otherwise.
	 */
	this.versionCompare = function(a, b, op=null) {
		var compare = a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
		switch (op) {
			case '<': case 'lt':
				return compare == -1;
			case '<=': case 'le':
				return compare != 1;
			case '>': case 'gt':
				return compare == 1;
			case '>=': case 'ge':
				return compare != -1;
			case '=': case '==': case 'eq':
				return compare == 0;
			case '!=': case '<>': case 'ne':
				return compare != 0;
			default:
				return compare;
		}
	}

	/**
	 * =================================================================
	 *                          Deprecated
	 * =================================================================
	 */

	/**
	 * Deprecated version of getCssLine
	 */
	 this.deprecatedGetCssLine = function( name, value, important ) {

		if ( _.isEmpty(value) ) return "";

		var property = _.kebabCase(name);
		var styles = "";
		
		if ( typeof value === 'object' ) {

			/**
			 * Support for object-position
			 * @since 1.2.9
			 */
			if ( property === 'object-position' ) {
				styles = property + ': ' + (value.x * 100) + '% ' + (value.y * 100) + '%; ';
			}
			else {

				/**
				 * Support for font appearance.
				 * Value is saved as object, eg.: { fontWeight: 100, fontStyle: 'italic' }
				 * @since 1.3.3
				 */
				if ( name === '__experimentalfontAppearance' ) {
					property = "";
				}
				
				property += _.isEmpty(property) ? "" : "-";
	
				for (const [side, val] of Object.entries(value)) {
					styles += greyd.tools.deprecatedGetCssLine( property+side, val, important );
				}
			}
		}
		else if ( !_.isEmpty(property) ) {
			if ( property.indexOf("box-shadow") !== -1 ) {
				var val = greyd.tools.getShadow(value);
			}
			else if ( property.indexOf("color") !== -1 || property.indexOf("background") !== -1 ) {
				var val = greyd.tools.getGradientValue(value);
				if (val == value) {
					val = greyd.tools.getColorValue( value );
				}
			}
			else {
				var val = String(value).trim();
			}

			// enable css variables
			if ( name.length > 2 && name.substr(0, 2) == '--' ) {
				property = '--'+property;
			}
			
			// temporary fix for hotspot block post import problems
			if ( (name.includes('popover-') || name.includes('hotspot-')) && !name.startsWith('--')) {
				property = '--'+property;
			}

			if ( !_.isEmpty(val) && !_.isEmpty(property) ) {
				styles += property+": "+val+( important ? " !important" : "" )+"; ";
			}
		}
		return styles;
	}
	
	/**
	 * Deprecated version of composeCSS
	 */
	this.deprecatedComposeCSS = function(styleObj, parentSelector, editor, important) {
		if (typeof styleObj !== "object" || _.isEmpty(styleObj) ) {
			return "";
		}

		// vars
		parentSelector 	= parentSelector ?? "";
		var finalCSS 	= "";
		var finalStyles = { default: "", lg: "", md: "", sm: "" };
		const deviceType = greyd.tools.getDeviceType();

		// loop through all selectors
		for (var [selector, attributes] of Object.entries(styleObj)) {

			if ( _.isEmpty(attributes) || typeof attributes !== "object" ) continue;

			// collect all styles for this selector
			var selectorStyles = { default: "", hover: "", lg: "", /*md: "", sm: "",*/ active: "" };

			// merge responsive styles depending on the current device type
			if ( _.has(attributes, 'responsive') ) {
				if ( deviceType === "Tablet" && _.has(attributes.responsive, 'md') ) {
					attributes = {
						...attributes,
						...attributes.responsive.md
					};
				}
				else if ( deviceType === "Mobile" && _.has(attributes.responsive, 'sm') ) {
					attributes = {
						...attributes,
						...attributes.responsive.sm
					};
				}
				attributes = _.omit( attributes, [ 'md', 'sm' ] );
				// console.log(attributes);
			}

			// loop through all attributes
			for (const [name, value] of Object.entries(attributes)) {

				if ( name === "hover" ) {
					selectorStyles.hover += greyd.tools.deprecatedGetCssLine( "", value, important );
				}
				else if ( name === "active" ) {
					selectorStyles.active += greyd.tools.deprecatedGetCssLine( "", value, important );
				}
				else if ( name === "responsive" ) {
					selectorStyles.lg += greyd.tools.deprecatedGetCssLine("", _.get(value, "lg"), important);
					// if ( ! editor ) {
					// 	selectorStyles.md += greyd.tools.deprecatedGetCssLine("", _.get(value, "md"), important);
					// 	selectorStyles.sm += greyd.tools.deprecatedGetCssLine("", _.get(value, "sm"), important);
					// }
				}
				else {
					selectorStyles.default += greyd.tools.deprecatedGetCssLine( name, value, important );
				}
			}

			// split selector into pieces to enable mutliple selectors (eg. ".selector, .selector h3")
			selector = selector.split(",");

			// console.log( selector );

			// loop through all selectorStyles
			for (var [type, css] of Object.entries(selectorStyles)) {
				if ( _.isEmpty(css) ) continue;

				// basic selectors
				let wrapper = editor ? ".editor-styles-wrapper " : "";
				let pseudo  = "";
				if ( type === "hover" ) {
					pseudo = ":hover";
				}
				else if ( type === "active" ) {
					let __selector = selector.join(",");
					if ( __selector.indexOf('input') > -1 ) {
						selector = __selector.replace( 'input', 'input:checked' ).split(",");
					} else {
						pseudo = " :checked + "+(__selector == '' ? '*' : '');
					}
				}
				
				// eg. .wrapper .gs_123456.selector, .wrapper .gs_123456.selector h3 { color: #fff; }
				var finalSelector = wrapper+"."+parentSelector+pseudo+selector.join(", "+wrapper+"."+parentSelector+pseudo);

				// add all selectors again with the pseudo "._hover" instead of ":hover"
				if ( type === "hover" || type === "active" ) {
					if ( editor ) {
						pseudo = "._"+type;
						wrapper += "[class*='-selected'] ";
						finalSelector += ", "+wrapper+"."+parentSelector+pseudo+selector.join(", "+wrapper+"."+parentSelector+pseudo);
					}
					type = "default";
				}
				
				finalStyles[type] += finalSelector+" { "+css+"} ";
			}
		}

		// loop through all finalStyles
		for (var [type, css] of Object.entries(finalStyles)) {
			if ( _.isEmpty(css) ) continue;

			var prefix = "", suffix = "";
			if ( type !== "default" ) {
				prefix = "@media (max-width: "+( _.get(greyd.data.grid, type) - 0.02 )+"px) { ";
				suffix = "} ";
			}

			finalCSS += prefix+css+suffix;
		}
		
		return finalCSS;
	}
};