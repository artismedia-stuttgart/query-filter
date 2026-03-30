/**
 * Get coherent settings (colors and gradients) for components.
 */
( function ( wp ) {

	// ready/init
	wp.domReady( () => {

		var _ = lodash;

		// don't init inside iframe
		if ( document.URL.indexOf('blob') === 0 ) return;

		// console.log( document,'globalStyles', typeof greyd.tools.globalStyles );

		/**
		 * in FSE Theme
		 */
		if ( typeof greyd.tools.globalStyles !== 'undefined' ) {

			// get global styles if not in site-editor
			if ( !_.has( wp, "editSite" ) ) {
				// console.log('getGlobalStylesData');
				greyd.tools.getGlobalStylesData();
			}
			
			/**
			 * Get all color palettes to be used inside a wp.blockEditor.ColorPalette component.
			 * @returns {array} Array of all color palettes
			 */
			greyd.data.getColors = function() {
				// console.info("colors from theme");
				return greyd.tools.getThemeColors();
			}

			/**
			 * Get all gradient palettes to be used inside a wp.blockEditor.ColorPalette component.
			 * @returns {array} Array of all gradient palettes
			 */
			greyd.data.getGradients = function() {
				// console.info("gradients from theme");
				return greyd.tools.getThemeGradients();
			}

		}
		/**
		 * in classic Theme
		 */
		else {
			
			/**
			 * Get all color palettes to be used inside a wp.blockEditor.ColorPalette component.
			 * @returns {array} Array of all color palettes
			 */
			greyd.data.getColors = function() {
				// console.info("colors from data");
				var colors = [
					{ name: "theme",   colors: greyd.data.colors },
					{ name: "default", colors: wp.blockEditor.SETTINGS_DEFAULTS.colors }
				];
				var custom = wp.data.select( "core/editor" ).getEditorSettings().colors;
				if ( JSON.stringify(custom) !== JSON.stringify(greyd.data.colors) ) {
					colors.push( { name: "custom",  colors: custom } );
				}
				// console.log(colors);
				return colors;
			}

			/**
			 * Get all gradient palettes to be used inside a wp.blockEditor.ColorPalette component.
			 * @returns {array} Array of all gradient palettes
			 */
			greyd.data.getGradients = function() {
				// console.info("gradients from data");
				var gradients = [
					{ name: "theme",   gradients: greyd.data.gradients },
					{ name: "default", gradients: wp.blockEditor.SETTINGS_DEFAULTS.gradients }
				];
				var custom = wp.data.select( "core/editor" ).getEditorSettings().gradients;
				if ( JSON.stringify(custom) !== JSON.stringify(greyd.data.gradients) ) {
					gradients.push( { name: "custom",  gradients: custom } );
				}
				// console.log(gradients);
				return gradients;
			}

		}

		// override tools functions
		greyd.tools.getColors = undefined;
		greyd.tools.getGradients = undefined;

	} );

} )( window.wp );


greyd.components = new function() {

	var { createElement: el } = wp.element;
	var { __, sprintf } = wp.i18n;
	var _ = lodash;

	/**
	 * Block editor controls & components.
	 */

	/**
	 * SelectControl with optgroup capability
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {string} help Help to be displayed.
	 * @property {object} style Style of Select.
	 * @property {string} className Class of Select.
	 * @property {array} options Array of objects with either label & value (option) or label & options (optgroup).
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed (params: value, event).
	 * 
	 * @returns {object} OptionsControl component.
	 */
	this.OptionsControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		getOptions() {
			var options = [];
			var items = this.props.options;
			var value = this.props.value;
			for (var i=0; i<items.length; i++) {
				// console.log(items[i]);
				if (_.has(items[i], 'options')) {
					var optgroup = [];
					for (var j=0; j<items[i].options.length; j++) {
						if (_.has(items[i].options[j], 'value')) {
							var opt = items[i].options[j];
							var disabled = _.has(opt, 'disabled');
							var selected = opt.value == value ? 'selected' : '';
							optgroup.push(el( 'option', { value: opt.value, disabled: disabled, selected: selected }, opt.label ));
						}
					}
					options.push(el( 'optgroup', { label: items[i].label }, optgroup ));
				}
				else if (_.has(items[i], 'value')) {
					var disabled = _.has(items[i], 'disabled');
					var selected = items[i].value == value ? 'selected' : '';
					options.push(el( 'option', { value: items[i].value, disabled: disabled, selected: selected }, items[i].label ));
				}
				else if (typeof items[i] === 'string') {
					var selected = items[i] == value ? 'selected' : '';
					options.push(el( 'option', { value: items[i], selected: selected }, items[i] ));
				}
			}
			return options;
		}
		render() {
			// console.log(this.props);
			return el( wp.components.BaseControl, {
				className: 'greyd-options-control',
				help: _.has(this.props, 'help') ? this.props.help : '',
			}, [
				_.has(this.props, 'label') ? el( greyd.components.AdvancedLabel, {
					// onClear: this.props.onChange(null),
					// currentValue: this.props.value,
					// initialValue: null,
					label: this.props.label
				} ) : '',
				el( 'select', {
					style: { width: '100%', ...this.props.style },
					className: this.props.className,
					value: this.props.value,
					onChange: (event) => this.props.onChange(event.target.value, event),
				}, this.getOptions() ),
			] );
		}
	}

	/**
	 * SelectControl with optgroup capability for Toolbar Controls
	 * based on ToolbarDropdownMenu with MenuGroups and MenuItems
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {bool} itemAsLabel show selected Item as Label.
	 * @property {string} className Class of Select.
	 * @property {array} options Array of objects with either label & value (option) or label & options (optgroup).
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed (params: value).
	 * 
	 * @returns {object} ToolbarOptionsMenu component.
	 */
	this.ToolbarOptionsMenu = class extends wp.element.Component {
		constructor() {
			super();
			this.close = () => { console.log("close") };
			this.labels = [];
		}

		getOptions() {
			this.label = [];
			var options = [];
			var items = this.props.options;
			// console.log(items);

			for (var i=0; i<items.length; i++) {
				// console.log(items[i]);
				if (_.has(items[i], 'options')) {
					var optgroup = [];
					for (var j=0; j<items[i].options.length; j++) {
						if (_.has(items[i].options[j], 'value')) {
							optgroup.push(this.getOption(items[i].options[j]));
							this.labels[items[i].options[j].value] = items[i].options[j].label;
						}
					}
					options.push(el( wp.components.MenuGroup, { label: items[i].label }, optgroup ));
				}
				else if (_.has(items[i], 'value')) {
					options.push(this.getOption(items[i]));
					this.labels[items[i].value] = items[i].label;
				}
			}
			return options;
		}
		getOption(opt) {
			var disabled = _.has(opt, 'disabled') ? 'is-hidden' : '';
			var selected = opt.value == this.props.value ? 'is-pressed' : '';
			return el( wp.components.MenuItem, { 
				'data-val': opt.value,
				isSelected: opt.value == this.props.value,
				className: selected+disabled,
				onClick: (event) => { 
					if (disabled != '') return;
					// console.log(event.target.dataset.val);
					// console.log(event.target.parentElement.dataset.val);
					var newval = false;
					if (_.has(event.target.dataset, 'val')) newval = event.target.dataset.val;
					else if (_.has(event.target.parentElement.dataset, 'val')) newval = event.target.parentElement.dataset.val;
					// console.log(newval);
					if (typeof newval !== 'undefined' && newval != this.props.value) {
						this.props.onChange(newval);
						this.close();
					}
				}
			}, opt.label );
		}

		render() {
			// console.log("render");
			var options = this.getOptions();
			var label = !this.props.itemAsLabel || this.props.value == "" ? this.props.label : this.labels[this.props.value];
			return el( wp.components.ToolbarDropdownMenu, {
				className: this.props.className,
				label: label,
				icon: 'arrow-down-alt2',
			}, [
				({ onClose }) => { 
					this.close = () => { onClose() };
					return el( wp.element.Fragment, { }, options );
				}
			] );
		}
	}
	
	/**
	 * Check for translation of a Post and show Tip with exchange Button
	 * 
	 * @property {string} label Label/Text to be displayed.
	 * @property {string} label_button Label to be displayed on the Button.
	 * @property {string} post_id Current Post ID.
	 * @property {array} posts Array of Post (greyd.data) objects to check for translation.
	 * @property {callback} onClick Callback function to be called when button is clicked (params: value).
	 * 
	 * @returns {object} TranslationHint component.
	 */
	this.TranslationHint = class extends wp.element.Component {
		constructor() {
			super();
		}

		hasPostTranslation() {

			const post_id = this.props.post_id;
			const data = this.props.posts;
			const currentLocale = greyd.data.language?.post?.locale;

			if ( typeof data === 'object') for ( [key, value] of Object.entries(data) ) {

				// skip current post
				if ( greyd.data.post_id == value.id ) continue;

				const langObject = _.has(value, 'lang') && typeof value.lang === 'object' ? value.lang : null;
				const locale     = _.has(langObject, 'locale') ? langObject.locale : null;
				// console.log( 'hasPostTranslation', value, langObject, locale, currentLocale );

				// skip if locale is not current
				if ( locale && locale !== currentLocale ) continue;

				if ( _.has(langObject, 'original_id') ) {
					// console.log(value['lang']);
					if ( langObject.original_id == post_id ) {
						return { id: value['id'], title: value['title'] };
					}
				}
			}
			return false;
		}
		render() {
			// console.log('render');
			// console.log(this.props);
			var translation = this.hasPostTranslation();
			var label = this.props.label ? this.props.label : __("A translation has been found for the embedded post.", 'greyd_hub')+": ";
			var button = this.props.label_button ? this.props.label_button : __("Replace now", 'greyd_hub');
			return [
				translation && el( wp.components.Tip, {}, [
					el( "div", { style: { marginTop: '20px' } }, label ),
					el( "a", { href: "post.php?post="+translation.id+"&action=edit", target: '_blank' }, translation.title ),
					el( wp.components.Button, {
						variant: 'secondary',
						isSmall: true,
						className: 'flex has-text',
						style: { marginTop: '10px' },
						text: button,
						icon: 'update',
						iconPosition: 'right',
						onClick: () => this.props.onClick(translation.id)
					} ),
				] )
			];
		}
	}
	
	/**
	 * ColorGradientPopupControl
	 * Color value is saved as css-variable if possible (eg. "var(--wp--preset--color--foreground)" or "#5D5D5D").
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {string} className Class of component ('single' for full border, 'small' for indicator only).
	 * @property {object} style Additional style of component.
	 * @property {object} colors Color Palettes to be used.
	 * @property {object} gradients Gradient Palettes to be used.
	 * @property {string} mode 'color' or 'gradient', omit property to show both.
	 * @property {bool} hover Indicator if component is rendered inside hover tab.
	 * @property {object} contrast If set, a ContrastChecker component is rendered
	 *     @property {string} contrast.default Value to compare contrast against.
	 *     @property {string} contrast.hover Value to compare contrast against in hover tab.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {bool} preventConvertGradient Prevent converting of gradient into slug.
	 * 
	 * @returns {object} ColorGradientPopupControl component.
	 */
	this.ColorGradientPopupControl = class extends wp.element.Component {

		constructor() {
			super();

			this.state = {
				id: greyd.tools.generateRandomID(),
				isOpen: false,
				isSaving: false
			};

			/**
			 * Fix an issue where both 'onGradientChange' and 'onColorChange' are
			 * called when a value is changed. The second function passes no value
			 * and therefore would reset the color.
			 * By saving this custom property we ensure 'onChange' is only called
			 * once per colro change event.
			 */
			this.isSaving = false;

			
		}

		setValue( value ) {

			// return if already saving...
			if ( this.isSaving ) {
				this.isSaving = false;
				return;
			}

			const externalValue = greyd.tools.getDynamicColorValue( value );
			// console.log( 'onChange', externalValue );

			// indicate that we're already saving the value...
			// this is only needed if we support both gradients & color.
			const { mode = '' } = this.props;
			if ( _.isEmpty( mode ) ) {
				this.isSaving = true;
			}

			this.props.onChange( externalValue )
		}

		render() {

			// get the value
			const internalValue = greyd.tools.getStaticColorValue( this.props.value );
			const isGradient    = greyd.tools.isGradient( internalValue );

			const {
				mode = '',
				style = {},
				className = null,
				label = null,
				hover = false,
			} = this.props;

			const {
				isOpen = false,
				id
			} = this.state;
			
			return [
				// wrapper
				el( 'div', {
					className: 'color-block-support-panel color_gradient_popup_control ' + className,
					style: { ...style } 
				}, [
					// wrapper
					el( 'div', {
						className: 'block-editor-tools-panel-color-dropdown block-editor-tools-panel-color-gradient-settings__dropdown' 
					}, [
						// trigger button
						el( wp.components.Button, {
							"aria-expanded": isOpen,
							"data-id": id,
							className: 'block-editor-panel-color-gradient-settings__dropdown' + (isOpen ? ' is-open' : ''),
							onClick: () => this.setState({ isOpen: !isOpen })
						}, [
							el( wp.components.__experimentalHStack, { justify: "flex-start" }, [
								el( wp.components.ColorIndicator, {
									// color/gradient indicator
									className: "block-editor-panel-color-gradient-settings__color-indicator",
									colorValue: internalValue
								} ), 
								// label
								!_.isEmpty( label ) && el( wp.components.FlexItem, { }, label )
							] ),
							// color/gradient popover
							isOpen && el( wp.components.Popover, {
								onClick: (e) => e.stopPropagation(),
								className: "color_popup_control__popover color_gradient",
								position: "middle left",
								onFocusOutside: (event) => {
									if (typeof event.relatedTarget?.dataset?.id !== 'undefined' &&
										event.relatedTarget.dataset.id == this.state.id) {
										// console.log('clicked on own button');
										return;
									}
									this.setState({ isOpen: false })
								},
							}, [
								el( "div", { className: "color_popup_control__popover_content" }, [
									el( wp.blockEditor.__experimentalColorGradientControl, {
										enableAlpha: true,
										colorValue: isGradient ? null : internalValue,
										gradientValue: isGradient ? internalValue : null,
										onColorChange: mode === 'gradient' ? null : ( val ) => this.setValue( val ),
										onGradientChange: mode === 'color' ? null : ( val ) => this.setValue( val ),
										...( mode !== 'gradient' ? { colors: greyd.data.getColors() } : {} ),
										...( mode !== 'color' ? { gradients: greyd.data.getGradients() } : {} )
									} )
								] )
							] )
						] ),
					] ),
					// contrast checker
					_.has(this.props, 'contrast') && mode == 'color' && el( wp.blockEditor.ContrastChecker, { 
						textColor: greyd.tools.getColorValue( greyd.tools.convertVarToColor( hover ? this.props.contrast.hover : this.props.contrast.default ) ), // '#fff', 
						backgroundColor: internalValue
					} )
				] )
			];
		}
	}

	/**
	 * IconPicker based on CustomSelectControl
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {string} help Help to be displayed.
	 * @property {object} style Style of Select.
	 * @property {string} className Class of Select.
	 * @property {string} value Current value.
	 * @property {array} icons Array of Icons to pick from.
	 *					format: { key: `icon-className`, value: { title: `icon-label` } }
	 *					defaults to greyd.data.icons
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} IconPicker component.
	 */
	this.IconPicker = class extends wp.element.Component {
		constructor() {
			super();
			this.icons = [];
			this.state = { icon: '' };
		}
		componentDidMount() {
			this.icons = this.getIcons();
			this.setState({ icon: this.getIcon() });
		}

		getIcons() {
			var icons = [ { key: "", name: el( 'span', { className: 'title' }, __("No icon", 'greyd_hub') ) } ];
			var raw = (_.has(this.props, 'icons')) ? this.props.icons : greyd.data.icons;
			for (var [key, value] of Object.entries(raw)) {
				var title = el( 'span', { className: 'iconitem' }, [
					el( 'span', { className: 'icon '+key } ),
					el( 'span', { className: 'title' }, value['title'] )
				] );
				icons.push( { key: key, name: title } );
			}
			return icons;
		}
		getIcon() {
			// console.log(this.props.value);
			// console.log(this.icons);
			for (var i=0; i<this.icons.length; i++) {
				if (this.icons[i].key == this.props.value)
					return this.icons[i];
			}
			return '';
		}
		setIcon(icon) {
			// console.log(icon);
			this.setState({ icon: icon.selectedItem });
			var val = icon.selectedItem.key;
			this.props.onChange(val);
		}

		render() {
			return el( wp.components.BaseControl, {
				className: 'iconselect',
				help: _.has(this.props, 'help') ? this.props.help : '',
			}, [
				el( wp.components.CustomSelectControl, {
					style: this.props.style,
					className: this.props.className,
					label: this.props.label,
					value: this.state.icon,
					options: this.icons,
					onChange: (value) => this.setIcon(value),
				} ),
			] );
		}
	}

	/**
	 * Advanced Label with reset trigger
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {mixed} initialValue Initial value.
	 * @property {mixed} currentValue Current value.
	 * @property {callback} onClear Callback function to be called when cleared.
	 */
	this.AdvancedLabel = class extends wp.element.Component {
		constructor() {
			super();
		}
		render() {
			const {
				label = "",
				initialValue,
				currentValue
			} = this.props;

			// console.log("AdvancedLabel:", label, "initialValue:", initialValue, typeof initialValue, "currentValue:", currentValue, typeof currentValue);
	
			let canBeCleared = currentValue !== null && currentValue !== '' && !_.isEqual(initialValue, currentValue);

			// handle empty objects
			if ( canBeCleared && typeof currentValue === "object" ) {
				canBeCleared = ! ( _.isEmpty(initialValue) && greyd.tools.isEmptyObject(currentValue) );
			}
	
			return canBeCleared ?
			(
				el( "div",
					{
						className: "advanced_label",
						onClick: this.props.onClear
					}, [
						el( wp.components.BaseControl.VisualLabel, { }, label ),
						el( wp.components.Icon, { icon: "no-alt" } )
					]
				)
			) : (
				el( wp.components.BaseControl.VisualLabel, { }, label )
			);
		}
	}

	/**
	 * Combines RangeControl and UnitControl with an AdvancedLabel
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {array} units Units to be supported.
	 * @property {int} min Minimum value.
	 * @property {int|object} max Maximum value. If integer, only the 'px'-max is set.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @since 2.3.0 [BETA] SwitchableSpacingControl is used.
	 * @property {bool}  supportsPresets A shorthand to make the core preset picker the default.
	 *                                   Same as passing 'default' inside the modes array.
	 * @property {array} modes Array of modes to be supported. Possible values are:
	 *                         'default', 'numeric', 'fluid', 'clamp', 'min', 'max', 'custom'
	 * 
	 * @returns {string} Value including unit (eg. "12px").
	 *                   Empty String "" on default or if cleared.
	 */
	this.RangeUnitControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		/**
		 * Render the control
		 */
		render() {

			/**
			 * @since 2.5.0 New control is used.
			 */
			// if ( ! greyd?.data?.is_greyd_beta ) {
			// 	return el( greyd.components.__RangeUnitControl, this.props );
			// }

			const supportsPresets = this.props?.supportsPresets;

			const {
				modes = [
					supportsPresets ? "default" : "numeric",
					"fluid", "min", "max", "custom"
				],
				...props
			} = this.props;

			return el( greyd.components.SwitchableSpacingControl, {
				modes: modes,
				...props
			} );
		}
	}

	/**
	 * Dimension Control with UnitControls for multiple sides.
	 * 
	 * @property {object|string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {array} units Units to be supported.
	 * @property {int} min Minimum value.
	 * @property {int|object} max Maximum value. If integer, only the 'px'-max is set.
	 * @property {array} sides Sides to be supported.
	 * @property {string} type Type of the value ('object'|'string').
	 * @property {object} labels Custom labels for the sides.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @since 2.3.0 [BETA] SpacingSizesControl is used, to reflect the core control.
	 * 
	 * @returns {object} String values for each side (eg. {top: "1px", right: "12px", ... }).
	 *                   Empty object {} on default or if cleared.
	 */
	this.DimensionControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		/**
		 * Convert the value into a javascript object
		 * @param {mixed} value
		 * @returns {object}
		 */
		explodeShorthandValue( value ) {
			if (typeof value === "object" && !_.isEmpty(value) ) {
				return value;
			}
			const {
				sides = [ 'top', 'right', 'bottom', 'left' ],
			} = this.props;

			// build default value
			let newValue = {};
			for (var i = 0; i < sides.length; i++) {
				newValue[ sides[i] ] = null;
			}
			
			if ( typeof value === "object" ) {
				newValue = {
					...newValue,
					value
				}
			}
			else if (typeof value === "string" ) {

				// shorthand property, eg. '4px 10px 3px'
				if ( !_.isEmpty(value.match(/[a-z\d]+\s+[a-z\d]?/g)) ) {
					let values = value.split(/\s+/);
					for (var i = 0; i < sides.length; i++) {
						let val = null;
						switch ( sides[i] ) {
							case 'top':
							case 'topLeft':
								val = values[0];
								break;
							case 'right':
							case 'topRight':
								val = _.has(values, 1) ? values[1] : values[0];
								break;
							case 'bottom':
							case 'bottomRight':
								val = _.has(values, 2) ? values[2] : values[0];
								break;
							case 'left':
							case 'bottomLeft':
								val = _.has(values, 3) ? values[3] : _.has(values, 1) ? values[1] : values[0];
								break;
								
						}
						newValue[ sides[i] ] = val;
					}
				}
				// simple string
				else if ( !_.isEmpty(value) ) {
					for (var i = 0; i < sides.length; i++) {
						newValue[ sides[i] ] = value;
					}
				}
			}

			return newValue;
		}

		/**
		 * Convert the object value into a shorthand css string
		 * @param {object} value 
		 * @returns {string}
		 */
		implodeShorthandValue( value ) {

			if ( typeof value !== 'object' ) {
				return value;
			}

			const newValue = [];
			for (const [side, val] of Object.entries(value)) {
				if ( val === null || val === '' || val === undefined ) {
					newValue.push('0');
				} else {
					newValue.push(val);
				}
			}
			return newValue.join(" ");
		}

		/**
		 * Render the control
		 */
		render() {

			/**
			 * @since 2.5.0 New control is used.
			 */
			// if ( ! greyd?.data?.is_greyd_beta ) {
			// 	return el( greyd.components.__DimensionControl, this.props );
			// }

			const {
				value,
				label,
				onChange,
				type,
				min: minimumCustomValue = 0,
				sides = [ 'top', 'right', 'bottom', 'left' ],
				...props
			} = this.props;

			let objectValue;
			if ( typeof value === 'string' ) {
				objectValue = this.explodeShorthandValue( value );
			}
			else if ( typeof value === 'object' &&  !_.isEmpty(value) ) {
				objectValue = value;
			}
			else {
				objectValue = {};
			}

			// convert individual values, otherwise the control only recognizes unit
			// value and css variables in the format 'var:preset|spacing|small'
			for (const [ key, val ] of Object.entries(objectValue)) {
				objectValue[ key ] = greyd.tools.getComputedSpacingValue( val )
			}

			const isBorderRadius = !_.isEmpty( sides ) && sides.length && sides.indexOf('topRight') > -1;

			return el( wp.components.BaseControl, {
				className: 'greyd-dimension-control',
				...props
			}, [

				// Core Spacing Sizes Control
				!isBorderRadius && el( wp.blockEditor.__experimentalSpacingSizesControl, {
					label: label,
					showSideInLabel: false,
					minimumCustomValue: minimumCustomValue < 0 ? Number.MIN_SAFE_INTEGER : minimumCustomValue,
					sides: sides,
					values: objectValue,
					onChange: val => {
						if ( type === 'string' ) {
							val = this.implodeShorthandValue( val );
						}
						onChange( val )
					}
				} ),

				// Core Border Radius Control
				isBorderRadius && el( wp.blockEditor.__experimentalBorderRadiusControl, {
					values: objectValue,
					label: label,
					onChange: ( val ) => {
						if ( type === 'string' ) {
							val = this.implodeShorthandValue( val );
						}
						onChange( val )
					},
					...props
				} ),
				
				// reset button
				value !== null && value !== '' && el( wp.components.Flex, {
					justify: 'flex-end'
				}, [
					el( wp.components.Button, {
						onClick: () => onChange( null ),
						icon: greyd.tools.getCoreIcon( 'undo' ),
						// iconPosition: 'right',
						isSmall: true,
						isTertiary: true,
						style: {
							color:  'rgb(117, 117, 117)',
							paddingInline: '4px',
						},
					}, __( "Reset", 'greyd_hub' ) )
				] )
			] )
		}
	}

	/**
	 * Block Style Variants like controls, similar to radio buttons.
	 * 
	 * @property {object} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {String|WPElement} help Optional help text to be displayed.
	 * @property {object[]} options [required] Array of option objects:
	 * 		@property {string} label Label of the option
	 * 		@property {string} icon Icon of the option (optional)
	 * 		@property {string} value Value of the option
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.BlockStyleControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {
			const {
				options,
				value,
				onChange,
				label = '',
				layout = ''
			} = this.props;

			const hasEmptyOption = options.filter( option => { return _.isEmpty(option.value) } ).length > 0

			// show a label
			let labelElement = null;
			if ( !_.isEmpty(label) ) {
				if ( hasEmptyOption) {
					labelElement = el( wp.components.BaseControl.VisualLabel, {
						onClick: () => onChange( null ),
					}, label )
				} else {
					labelElement = el( greyd.components.AdvancedLabel, {
						label: label,
						initialValue: null,
						currentValue: value,
						onClear: () => onChange( null ),
					}, label )
				}
			}

			return el( wp.element.Fragment, {}, [
				labelElement,
				el( "div", {
					className: "block-editor-block-styles",
					style: (
						_.has(this.props, 'help')
						? { marginBottom: 12 }
						: {}
					)
				}, [
					el( "div", {
						className: "block-editor-block-styles__variants" + ( layout == 'inline' ? ' layout--inline' : '' )
					}, [
						...options.map( option => {
							const isActive = value === option.value;
							return el( "button", {
								type: "button",
								"aria-current": isActive ? "true" : "false",
								className: "components-button block-editor-block-styles__item is-secondary" + ( isActive ? " is-active" : "" ),
								"aria-label": String( option.label ),
								onClick: () => onChange( option.value ),
							}, [
								_.get(option, 'icon')
								? el( wp.components.Tooltip, {
									text: String( option.label ),
									placement: 'bottom center'
								}, el( 'span', {
									className: 'block-editor-block-styles__item-text'
								}, option.icon ) )
								: el( wp.components.__experimentalTruncate, {
									numberOfLines: 1,
									className: "block-editor-block-styles__item-text"
								}, String( option.label ) )
							] )
						})
					] ),
				] ),
				_.has(this.props, 'help') && (
					typeof this.props.help === 'string' ?
					el( "p", {
						className: 'components-base-control__help',
						style: {
							fontSize: 12,
							fontStyle: 'normal',
							color: 'rgb(117, 117, 117)',
							margin: '12px 0 24px'
						},
					}, this.props.help ) :
					this.props.help
				)
			] );
		}
	}

	/**
	 * Button Group Control, similar to radio buttons.
	 * 
	 * @property {object} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {String|WPElement} help Optional help text to be displayed.
	 * @property {object[]} options [required] Array of option objects:
	 * 		@property {string} label Label of the option
	 * 		@property {string} icon Icon of the option (optional)
	 * 		@property {string} value Value of the option
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.ButtonGroupControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {
			return el( greyd.components.BlockStyleControl, {
				...this.props,
				layout: 'inline'
			} )
		}
	}

	/**
	 * Control for css box-shadow property
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {string} fallbackColor Color to use when no color is set.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {string} Value in the format "0px+0px+50px+0px+color-22+100".
	 *                   Empty String "" on default or if cleared.
	 */
	this.DropShadowControl = class extends wp.element.Component {

		constructor() {
			super();

			// bind keyword "this" inside functions to the class object
			this.setConfig 		= this.setConfig.bind(this);
			this.setValue 		= this.setValue.bind(this);
			this.handleChange	= this.handleChange.bind(this);
			this.pushChange 	= this.pushChange.bind(this);
			this.clear 			= this.clear.bind(this);

			this.config = {
				fallbackColor: "color-31"
			}
			
			this.state = {
				...this.getDefault()
			};
		}

		/**
		 * We use a function rather than a class var to set the default state.
		 * This way the defaultState is handled more like a class constant and
		 * is not prone to be manipulated as a class variable would be.
		 * @returns object
		 */
		getDefault() {
			return {
				value: {
					color: "",
					x: 0,
					y: 10,
					blur: 15,
					spread: 0,
					opacity: 25,
					position: "outset"
				}
			}
		}

		componentDidMount() {
			this.setConfig();
			
			this.setValue(this.props.value);
		}
	
		componentWillReceiveProps( props ) {
			this.setValue(props.value);
		}

		/**
		 * Set the class configuration.
		 */
		setConfig() {
			const { config, props } = this;

			// get any user config...
			const userConfig = _.pick(props, _.keys(config));

			// avoid errors...
			const finalConfig = _.clone(config);

			_.assign(finalConfig, userConfig);

			this.config = finalConfig;
		}

		/**
		 * Convert the string format "0px+0px+50px+0px+color-22+100" into the value object
		 * @param {string} input 
		 */
		setValue(input) {

			if (
				typeof input === "undefined" ||
				input == "none" ||
				_.isEmpty(input) ||
				input.indexOf("+") === -1
			) {
				this.setState({
					...this.getDefault()
				});
				return;
			}

			let raw = input.split("+");
			let opacity = raw[raw.length-1];
			raw.pop(); // remove opacity
			let color = raw[raw.length-1];
			raw.pop(); // remove color

			if ( _.isEmpty(color) ) return;

			var newState = {
				raw: input,
				value: this.state.value
			};

			newState.value.color = color;
			newState.value.opacity = Number(greyd.tools.getNumValue(opacity));

			if ( _.has(raw, 0) && raw[0] == "inset" ) {
				newState.value.position = "inset";
				raw.shift();
			} 

			if ( _.has(raw, 0) ) newState.value.x 		= Number(greyd.tools.getNumValue(raw[0]));
			if ( _.has(raw, 1) ) newState.value.y 		= Number(greyd.tools.getNumValue(raw[1]));
			if ( _.has(raw, 2) ) newState.value.blur 		= Number(greyd.tools.getNumValue(raw[2]));
			if ( _.has(raw, 3) ) newState.value.spread 	= Number(greyd.tools.getNumValue(raw[3]));

			if ( newState !== this.state ) {
				this.setState( newState );
			}
		}

		/**
		 * Handle all change events
		 * @param {string} name
		 * @param {mixed} input
		 */
		handleChange(name, input) {
			var value = this.state.value;
			if ( name === "color" ) {
				if ( _.isEmpty(input) ) {
					input = "";
				} else {
					const match = input.match(/var\(--color([\d]{2})\)/);
					if ( _.has(match, 1) ) {
						input = "color-"+match[1];
					}
				}
				value[name] = input;
			}
			else {
				if ( name === "position" ) {
					value[name] = _.toString(input);
				}
				else {
					value[name] = Number(greyd.tools.getNumValue(input));
				}

				// use the fallback color to show some changes
				if ( _.isEmpty(value.color) ) {
					value.color = this.config.fallbackColor;
				}
			}

			this.setState(
				{ value: value },
				this.pushChange
			);
		}

		/**
		 * Push change to props.onChange()
		 */
		pushChange() {
			this.props.onChange(
				this.getStringValue( this.state.value )
			);
		}

		/**
		 * Convert value object into string format
		 * @param {object} input value
		 * @returns string of format "0px+0px+50px+0px+color-22+100"
		 */
		getStringValue(value) {
			let string = "";
			const { x, y, blur, spread, color, opacity, position } = value;

			if ( !_.isEmpty(color) ) {
				string = (position === "inset" ? "inset+" : "")+x+"px+"+y+"px+"+blur+"px+"+spread+"px+"+color+"+"+opacity;
			}
			return string;
		}

		/**
		 * Reset the value
		 */
		clear() {
			this.setState(
				this.getDefault(),
				this.pushChange
			);
		}

		render() {
			return el( "div", { className: "drop_shadow_control" }, [
				( this.props.label ?
					el( "div", { className: "drop_shadow_control__label" }, [
						el( greyd.components.AdvancedLabel, {
							label: this.props.label,
							initialValue: "",
							currentValue: this.getStringValue(this.state.value),
							onClear: this.clear
						} )
					] )
					: ""
				),
				el( "div", { }, [
					el( greyd.components.ColorGradientPopupControl, {
						label: __("Color", "greyd_hub"),
						mode: 'color',
						value: greyd.tools.getColorValue(this.state.value.color),
						onChange: (newValue) => this.handleChange("color", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Horizontally", "greyd_hub") ),
					el( wp.components.RangeControl, {
						value: this.state.value.x,
						min: -50,
						max: 50,
						onChange: (newValue) => this.handleChange("x", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Vertically", "greyd_hub") ),
					el( wp.components.RangeControl, {
						value: this.state.value.y,
						min: -50,
						max: 50,
						onChange: (newValue) => this.handleChange("y", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Blur", "greyd_hub") ),
					el( wp.components.RangeControl, {
						value: this.state.value.blur,
						min: 0,
						max: 50,
						onChange: (newValue) => this.handleChange("blur", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Stretch", "greyd_hub") ),
					el( wp.components.RangeControl, {
						value: this.state.value.spread,
						min: -50,
						max: 50,
						onChange: (newValue) => this.handleChange("spread", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Opacity", "greyd_hub") ),
					el( wp.components.RangeControl, {
						value: this.state.value.opacity,
						min: 0,
						max: 100,
						onChange: (newValue) => this.handleChange("opacity", newValue)
					} )
				] ),
				el( "div", { className: "flex" }, [
					el( "span", { className: "inner_label" }, __("Position", "greyd_hub") ),
					el( greyd.components.ButtonGroupControl, {
						value: this.state.value.position,
						options: [
							{
								label: __("Outside", "greyd_hub"),
								value: "outset"
							},
							{
								label: __("Inside", "greyd_hub"),
								value: "inset"
							},
						],
						onChange: (newValue) => this.handleChange("position", newValue)
					} )
				] ),
			] );
		}
	}

	/**
	 * Control for css border properties (top, right...)
	 * 
	 * @property {object} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {string} fallbackColor Color to use when no color is set.
	 * @property {string} type Type of the value ('object'|'string').
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} String values for each side (eg. {top: "1px solid var(--wp--preset--color--tertiary)", ... }).
	 *                   Empty object {} on default or if cleared.
	 */
	this.BorderControl = class extends wp.element.Component {

		constructor(props) {
			super(props);

			this.convertCoreValueToShorthands = this.convertCoreValueToShorthands.bind(this);
			this.convertShorthandsToCoreValue = this.convertShorthandsToCoreValue.bind(this);

			/**
			 * These values can be overwritten by setting them as properties.
			 */
			this.config = {
				type: 'object',
				fallbackColor: "var(--wp--preset--color--foreground)",
				defaultBorder: {
					width: '1px',
					style: 'solid',
					color: null
				},
			};
		}

		/**
		 * Get the class configuration
		 */
		getConfig() {
			const { config, props } = this;

			// get any user config...
			const userConfig = _.pick( props, _.keys(config) );

			return {
				...config,
				...userConfig,
				labels : {
					...config.labels,
					...userConfig.labels
				}
			}
		}

		/**
		 * Convert a shorthand css line into a useable object.
		 * @param {shorthand} string Shorthand CSS property value, eg. '1px solid red'
		 * @returns {object}         Border value, eg. { width: '1px', style: 'solid', color: 'red' }
		 */
		convertShorthandToObject( shorthand ) {
			const border = {
				...this.getConfig().defaultBorder
			}

			if ( ! _.isEmpty( shorthand ) ) {
				[
					border.width,
					border.style,
					border.color
				] = shorthand.split(' ');

				border.color = greyd.tools.convertVarToColor( border.color )
			}

			return border;
		}


		/**
		 * Convert a useable object into a shorthand css line.
		 * @param {object} object Border value, eg. { width: '1px', style: 'solid', color: 'red' }
		 * @returns {string}      Shorthand CSS property value, eg. '1px solid red'
		 */
		convertObjectToShorthand( object ) {

			let width = object.width ? object.width : this.getConfig().defaultBorder.width;
			let style = object.style ? object.style : this.getConfig().defaultBorder.style;
			let color = object.color ? greyd.tools.convertColorToVar( object.color ) : this.getConfig().fallbackColor;

			return `${width} ${style} ${color}`;
		}

		/**
		 * Convert shorthands into object useable for the core control.
		 * @param {string|object} value '1px solid var(--wp--preset--color--foreground)' or { top: '1px solid var(--wp--preset--color--foreground)', right: ... }
		 * @returns {object} eg. { width: '1px', style: 'solid', color: '#5d5d5d' }
		 *                   or  { top: { width: ... }, right: { ... }, ... }
		 */
		convertShorthandsToCoreValue( value ) {

			if ( typeof value === 'string' ) {
				return this.convertShorthandToObject( value );
			}
			else if ( _.has(value, 'top') ) {
				return {
					top: this.convertShorthandToObject( value.top ),
					right: this.convertShorthandToObject( value.right ),
					bottom: this.convertShorthandToObject( value.bottom ),
					left: this.convertShorthandToObject( value.left ),
				}
			}
			else {
				return this.getConfig().defaultBorder;
			}
		}

		/**
		 * Convert the object returned from the core control into useable shorthands.
		 * @param {object} value eg. { width: '1px', style: 'solid', color: '#5d5d5d' }
		 *                       or  { top: { width: ... }, right: { ... }, ... }
		 * @returns {string|object} '1px solid var(--wp--preset--color--foreground)' or { top: '1px solid var(--wp--preset--color--foreground)', right: ... }
		 */
		convertCoreValueToShorthands( value ) {

			if ( this.getConfig().type === 'string' ) {
				return this.convertObjectToShorthand( value );
			}
			else if ( _.has(value, 'top') ) {
				return {
					top: this.convertObjectToShorthand( value.top ),
					right: this.convertObjectToShorthand( value.right ),
					bottom: this.convertObjectToShorthand( value.bottom ),
					left: this.convertObjectToShorthand( value.left ),
				}
			}
			else {
				const val = this.convertObjectToShorthand( value )
				return {
					top: val,
					right: val,
					bottom: val,
					left: val,
				}
			}
		}

		render() {

			const {
				label = null,
				value = null,
				onChange = () => {}
			} = this.props;

			const {
				type
			} = this.getConfig();

			return el( "div", { className: "dimension_control border_control" }, [

				el( "div", { className: "dimension_control__label flex" }, [
					(
						label
						? el( greyd.components.AdvancedLabel, {
							label: label,
							initialValue: null,
							currentValue: value,
							onClear: () => onChange( null )
						} )
						: "<div></div>"
					)
				] ),

				el( "div", { className: "dimension_control__inputs" }, [

					type === 'string'

					? el( wp.components.__experimentalBorderControl, {
						colors: greyd.data.getColors(),
						value: this.convertShorthandsToCoreValue( value ),
						onChange: ( val ) => {
							onChange( this.convertCoreValueToShorthands( val ) )
						}
					} )

					: el( wp.components.__experimentalBorderBoxControl, {
						colors: greyd.data.getColors(),
						value: this.convertShorthandsToCoreValue( value ),
						onChange: ( val ) => {
							onChange( this.convertCoreValueToShorthands( val ) )
						}
					} )

				] )
			] );
		}
	}

	/**
	 * Render a simple Icon.
	 * 
	 * @property {string} icon Greyd icon name or dashicon name.
	 * @property {string} title Optional icon title
	 */
	this.GreydIcon = class extends wp.element.Component {
		constructor() {
			super();

			this.icons = {
				hover: '<svg width="20" height="20" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.20822 1.50747C4.083 1.52623 4.00112 1.57262 3.97018 1.60198C3.93109 1.63906 3.8689 1.75235 3.84269 1.97616C3.81888 2.17943 3.83458 2.39487 3.86428 2.54217C3.90523 2.70132 3.94469 2.91794 3.97963 3.10974C3.98431 3.13546 3.98891 3.16072 3.99343 3.18535C4.038 3.42848 4.07245 3.59837 4.09778 3.67047C4.10497 3.69093 4.11083 3.71185 4.1153 3.73307C4.15393 3.91631 4.19249 4.06288 4.23398 4.22033L4.23474 4.22322C4.27513 4.37647 4.31828 4.54021 4.36142 4.74314C4.3849 4.8305 4.4055 4.88671 4.43276 4.96109C4.44414 4.99214 4.45668 5.02635 4.47108 5.06732C4.5199 5.20626 4.56726 5.36555 4.61255 5.61763C4.72511 6.04941 4.80704 6.51361 4.86964 6.86832C4.89736 7.02538 4.92129 7.16097 4.94242 7.26277C4.96012 7.33042 4.98327 7.4243 4.98738 7.50619C4.9886 7.53042 4.99035 7.58607 4.97588 7.65261C4.96507 7.70227 4.91776 7.88762 4.71959 7.99388C4.50614 8.10833 4.31361 8.03011 4.25343 8.00017C4.18482 7.96604 4.13911 7.92389 4.12186 7.90752C4.06424 7.85287 4.01214 7.77727 3.99023 7.74549C3.98877 7.74337 3.98744 7.74144 3.98626 7.73973C3.97128 7.71811 3.95807 7.69876 3.94491 7.67948C3.93247 7.66126 3.92008 7.6431 3.90626 7.62313C3.8117 7.52764 3.7567 7.44937 3.71531 7.39047L3.7147 7.3896C3.67862 7.33826 3.65994 7.31193 3.61376 7.26811C3.514 7.17348 3.3979 7.04128 3.30201 6.93209L3.29188 6.92057C3.195 6.81028 3.11612 6.72105 3.05587 6.66079C3.04642 6.65405 3.03703 6.64735 3.02768 6.64067C2.87401 6.53095 2.7333 6.43048 2.58692 6.35008C2.42145 6.25921 2.30136 6.22493 2.21582 6.22493C1.83379 6.22493 1.58066 6.485 1.54691 6.6771C1.54449 6.69086 1.5415 6.7045 1.53795 6.718C1.49998 6.86206 1.49815 6.91138 1.50069 6.94031C1.5026 6.96207 1.5098 7.00437 1.55906 7.104C1.63947 7.20231 1.76022 7.3393 1.90226 7.50044C1.9755 7.58352 2.0544 7.67302 2.13635 7.76696C2.37511 8.04065 2.64692 8.3601 2.79739 8.59466C3.05663 8.96451 3.24952 9.29638 3.40362 9.56247C3.56827 9.84678 3.66755 10.016 3.75863 10.1234C3.81065 10.1848 3.89236 10.2376 4.04701 10.3088C4.06591 10.3175 4.08851 10.3274 4.11389 10.3385C4.24387 10.3956 4.44683 10.4847 4.59929 10.605C4.86961 10.8181 5.32695 11.2641 5.56774 11.5221C5.84095 11.8148 5.89613 12.2134 5.90456 12.4726C6.03118 12.4755 6.1601 12.4791 6.28823 12.4826C6.44799 12.487 6.60651 12.4914 6.75777 12.4946C7.23069 12.5046 7.62724 12.5028 7.87256 12.4695C7.884 12.468 7.89547 12.4668 7.90698 12.4661C7.91215 12.4634 7.91943 12.4593 7.9289 12.4532C7.97086 12.4263 8.02748 12.3786 8.09332 12.3083C8.22579 12.167 8.34572 11.9879 8.40989 11.8662C8.4198 11.8474 8.43091 11.8292 8.44313 11.8118C8.59432 11.5967 8.83925 11.4221 9.14334 11.4176C9.46229 11.413 9.7195 11.5972 9.86528 11.8482C9.92403 11.9494 10.0323 12.1197 10.1565 12.2677C10.2185 12.3416 10.2762 12.3999 10.3256 12.4398C10.3609 12.4683 10.3805 12.478 10.3844 12.48C10.449 12.494 10.557 12.5005 10.6859 12.4998C10.6793 12.4202 10.675 12.3375 10.675 12.2567C10.675 12.2238 10.6745 12.19 10.674 12.1557C10.6728 12.0669 10.6715 11.9749 10.6783 11.8866C10.6888 11.7508 10.7168 11.6022 10.7696 11.3622C10.8483 11.0051 11.0853 10.6535 11.2891 10.3915C11.5049 10.1142 11.7435 9.86353 11.8914 9.70833C12.1034 9.48582 12.2571 9.10299 12.3595 8.79412C12.3923 8.69505 12.4296 8.48408 12.4583 8.2279C12.4858 7.98172 12.5 7.74363 12.5 7.61364V5.80362C12.5 5.61914 12.4504 5.55193 12.4316 5.53146C12.41 5.50802 12.3676 5.4804 12.2831 5.47174C12.1958 5.4628 12.0897 5.4783 11.9923 5.51704C11.8897 5.55784 11.8423 5.60551 11.8329 5.61893C11.7924 5.6766 11.7486 5.77763 11.7089 5.89068C11.6999 5.91623 11.6919 5.94017 11.6844 5.96277C11.6833 5.96601 11.6822 5.96939 11.681 5.97287C11.675 5.99096 11.6681 6.01174 11.6622 6.0284C11.6587 6.0384 11.6533 6.05364 11.6466 6.06984C11.644 6.07652 11.6239 6.12719 11.5879 6.17841C11.5763 6.19494 11.5541 6.22466 11.5204 6.2563C11.4912 6.28378 11.421 6.34384 11.3105 6.37441C11.1762 6.41157 11.0238 6.39274 10.8965 6.30667C10.7912 6.23551 10.7424 6.14482 10.7232 6.10382C10.699 6.05223 10.6877 6.00441 10.6824 5.97357C10.6538 5.85399 10.625 5.68377 10.5979 5.52385C10.5833 5.43737 10.5692 5.35391 10.5559 5.28309C10.5322 5.15744 10.508 5.0469 10.4818 4.95816C10.4596 4.88323 10.4431 4.84877 10.4388 4.83971C10.4374 4.83678 10.4373 4.83651 10.4386 4.83839C10.3874 4.76787 10.3358 4.72595 10.2894 4.698C10.241 4.66889 10.189 4.64936 10.1299 4.63311C9.95662 4.58546 9.73587 4.58211 9.57661 4.6299C9.57664 4.62989 9.57625 4.63001 9.57543 4.63029L9.57189 4.63155C9.56877 4.6327 9.56455 4.63436 9.55925 4.63664C9.5485 4.64126 9.53495 4.64771 9.5191 4.65619C9.48698 4.67336 9.45102 4.69586 9.41597 4.72174C9.38066 4.74781 9.35113 4.77372 9.32937 4.79639C9.30964 4.81694 9.30278 4.82809 9.30271 4.82804C9.3027 4.82803 9.30289 4.82769 9.30325 4.827C9.26508 4.89942 9.22975 5.04269 9.19559 5.27232C9.18911 5.31586 9.18244 5.36503 9.17535 5.41726C9.15261 5.58482 9.12558 5.78404 9.08685 5.93104L9.05667 5.92309C8.93388 6.03759 8.56466 6.26201 8.1694 6.05631C8.13265 5.9766 8.10796 5.87575 8.10603 5.85975L8.10431 5.83915L8.10389 5.83145L8.10362 5.82498L8.10342 5.8182L8.10293 5.79988C8.10239 5.78208 8.10127 5.75109 8.09896 5.70996C8.0943 5.62708 8.08495 5.50545 8.06644 5.36932C8.02564 5.0694 7.95302 4.80347 7.86244 4.67457C7.82273 4.61806 7.67979 4.51813 7.50917 4.48377C7.34095 4.44989 7.1935 4.46496 7.08577 4.51909C6.90623 4.60929 6.80331 4.74142 6.75973 4.84818C6.7437 4.92679 6.73923 5.06577 6.74893 5.26817C6.75181 5.32818 6.75644 5.39929 6.76128 5.47366C6.77055 5.61624 6.78061 5.77083 6.78061 5.88232C6.78061 6.15846 6.55676 6.38232 6.28061 6.38232C6.00447 6.38232 5.78061 6.15846 5.78061 5.88232C5.78061 5.88316 5.78072 5.88385 5.78061 5.88232C5.78025 5.87707 5.77874 5.85596 5.77354 5.81482C5.76753 5.76726 5.75829 5.70566 5.74589 5.63097C5.72113 5.48185 5.68591 5.29305 5.64479 5.08337C5.56264 4.66449 5.45972 4.17624 5.37659 3.78197C5.37395 3.76945 5.3718 3.75683 5.37012 3.74414C5.29317 3.16009 5.13934 2.65251 4.99034 2.29916C4.98639 2.28977 4.98272 2.28026 4.97934 2.27065C4.94736 2.17963 4.87957 2.01866 4.77962 1.86064C4.68199 1.70627 4.57618 1.59053 4.47494 1.52704C4.41656 1.50421 4.32111 1.49056 4.20822 1.50747ZM6.3962 3.77085C6.38344 3.71021 6.37099 3.65113 6.35897 3.59413C6.27014 2.93407 6.09656 2.35245 5.9175 1.92425C5.86517 1.77847 5.7687 1.55367 5.62477 1.32611C5.47928 1.09607 5.26 0.822731 4.95181 0.647308C4.94101 0.641161 4.92998 0.635418 4.91875 0.630091C4.64303 0.499306 4.33078 0.477957 4.06009 0.518504C3.79127 0.558769 3.49985 0.669767 3.28194 0.876498C2.9892 1.1542 2.88548 1.55244 2.84948 1.85983C2.81164 2.18289 2.83607 2.51288 2.88795 2.75892C2.88966 2.76705 2.89158 2.77515 2.8937 2.78319C2.92526 2.90294 2.95834 3.08401 2.99638 3.29221C3.00079 3.31635 3.00527 3.34085 3.00982 3.36567C3.04561 3.5609 3.09021 3.80273 3.14279 3.96746C3.18485 4.16345 3.22686 4.32285 3.26624 4.47229L3.26699 4.47516C3.30849 4.63261 3.34704 4.77918 3.38567 4.96241C3.38739 4.97055 3.38931 4.97864 3.39143 4.98668C3.42919 5.12997 3.47122 5.2444 3.50335 5.33187C3.51232 5.35628 3.52051 5.37858 3.52763 5.39883C3.56043 5.4922 3.59478 5.60344 3.63133 5.81145C3.63352 5.82393 3.63619 5.83633 3.63933 5.84862C3.62995 5.84192 3.62038 5.83507 3.61062 5.82808C3.46222 5.72186 3.27054 5.58465 3.06831 5.47358C2.83974 5.34804 2.54506 5.22493 2.21582 5.22493C1.44423 5.22493 0.708919 5.74139 0.566117 6.4817C0.523185 6.64739 0.486983 6.82813 0.504524 7.02782C0.523111 7.23942 0.597206 7.42497 0.695084 7.61068C0.709386 7.63782 0.726155 7.66358 0.745179 7.68764C0.841272 7.80919 1.00526 7.99524 1.17723 8.19034C1.24575 8.26807 1.31553 8.34724 1.38279 8.42434C1.63806 8.71695 1.85923 8.98205 1.95916 9.14004C1.96349 9.14689 1.96799 9.15363 1.97264 9.16026C2.20782 9.49492 2.38381 9.79692 2.53826 10.0636C2.54604 10.0771 2.55383 10.0905 2.56164 10.104C2.69953 10.3424 2.84176 10.5883 2.99575 10.77C3.19752 11.008 3.45126 11.1354 3.62901 11.2172C3.68584 11.2434 3.73292 11.2645 3.77253 11.2822C3.884 11.3322 3.9363 11.3557 3.9801 11.3902C4.19299 11.5581 4.61084 11.9624 4.83666 12.2044C4.85998 12.2294 4.90471 12.334 4.90585 12.5461C4.90632 12.6349 4.89903 12.7178 4.89134 12.779C4.88755 12.8092 4.88383 12.8329 4.88126 12.8479L4.87848 12.8636C4.87839 12.864 4.87814 12.8653 4.87806 12.8657C4.84835 13.0125 4.88593 13.1648 4.98053 13.2809C5.07548 13.3974 5.21781 13.465 5.36811 13.465C5.63511 13.465 5.93206 13.4732 6.24011 13.4817C6.40346 13.4863 6.56992 13.4909 6.7367 13.4944C7.18472 13.5038 7.64615 13.5058 7.97967 13.464C8.17902 13.4542 8.3478 13.3726 8.46899 13.2948C8.60331 13.2086 8.72297 13.0989 8.82304 12.9921C8.94411 12.8629 9.0545 12.7185 9.14532 12.5818C9.21427 12.6848 9.2976 12.7999 9.39046 12.9105C9.47763 13.0144 9.58145 13.1243 9.69779 13.2181C9.80976 13.3085 9.96238 13.4077 10.1471 13.4515C10.389 13.5089 10.702 13.5037 10.9083 13.4939C11.021 13.4886 11.1219 13.4806 11.1945 13.474C11.2309 13.4707 11.2606 13.4676 11.2816 13.4654L11.3064 13.4627L11.3135 13.4619L11.3156 13.4616L11.3167 13.4615C11.4561 13.445 11.5823 13.3706 11.6641 13.2565C11.746 13.1425 11.7761 12.9993 11.7472 12.8619L11.7468 12.8599L11.7447 12.8497C11.7428 12.8402 11.74 12.8258 11.7365 12.8071C11.7296 12.7698 11.7202 12.7165 11.7109 12.6544C11.6915 12.5257 11.675 12.3772 11.675 12.2567C11.675 12.1756 11.6741 12.1238 11.6734 12.0859C11.6725 12.0321 11.672 12.0063 11.6753 11.9637C11.6799 11.9048 11.6934 11.8172 11.7462 11.5773C11.7732 11.4545 11.8852 11.2539 12.0784 11.0056C12.2596 10.7727 12.4659 10.5551 12.6154 10.3981C12.9982 9.99634 13.2076 9.41364 13.3086 9.10883C13.3775 8.90106 13.4232 8.59766 13.4521 8.33891C13.4821 8.07016 13.5 7.79275 13.5 7.61364V5.80362C13.5 5.42892 13.3932 5.09957 13.1674 4.85425C12.9443 4.61189 12.6537 4.50447 12.385 4.47694C12.119 4.44971 11.8514 4.49689 11.6227 4.58782C11.562 4.61198 11.5007 4.64058 11.4405 4.67382C11.4032 4.54767 11.3453 4.38506 11.2476 4.25057C10.9716 3.87069 10.628 3.73295 10.395 3.6689C10.0688 3.57919 9.65443 3.5625 9.28919 3.6721C9.12935 3.72007 8.96038 3.81509 8.82202 3.91723C8.76826 3.95692 8.70941 4.00479 8.65164 4.06023C8.4213 3.76109 8.03842 3.57028 7.70661 3.50345C7.38794 3.43927 6.99894 3.44359 6.63683 3.62553C6.55106 3.66862 6.47076 3.71738 6.3962 3.77085ZM7.89728 12.4705C7.89728 12.4705 7.89762 12.4704 7.89831 12.4702Z"/></svg>',
				mobile: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.93929 4.28575C6.68939 4.28575 6.42857 4.50863 6.42857 4.85718V15.1429C6.42857 15.4914 6.68939 15.7143 6.93929 15.7143H13.0643C13.3142 15.7143 13.575 15.4914 13.575 15.1429V4.85718C13.575 4.50863 13.3142 4.28575 13.0643 4.28575H6.93929ZM5 4.85718C5 3.78556 5.83608 2.85718 6.93929 2.85718H13.0643C14.1675 2.85718 15.0036 3.78556 15.0036 4.85718V15.1429C15.0036 16.2145 14.1675 17.1429 13.0643 17.1429H6.93929C5.83608 17.1429 5 16.2145 5 15.1429V4.85718Z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.2876 13.5715C9.2876 13.177 9.60739 12.8572 10.0019 12.8572H10.009C10.4035 12.8572 10.7233 13.177 10.7233 13.5715C10.7233 13.966 10.4035 14.2857 10.009 14.2857H10.0019C9.60739 14.2857 9.2876 13.966 9.2876 13.5715Z"/></svg>',
				tablet: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.28599 3.71418C4.28599 3.33951 4.67963 2.85704 5.40321 2.85704H14.5607C15.2843 2.85704 15.6779 3.33951 15.6779 3.71418V16.2856C15.6779 16.6603 15.2843 17.1428 14.5607 17.1428H5.40321C4.67963 17.1428 4.28599 16.6603 4.28599 16.2856V3.71418ZM5.40321 1.42847C4.10376 1.42847 2.85742 2.3531 2.85742 3.71418V16.2856C2.85742 17.6467 4.10376 18.5713 5.40321 18.5713H14.5607C15.8602 18.5713 17.1065 17.6467 17.1065 16.2856V3.71418C17.1065 2.3531 15.8602 1.42847 14.5607 1.42847H5.40321ZM9.98196 14.2856C9.58747 14.2856 9.26768 14.6054 9.26768 14.9999C9.26768 15.3944 9.58747 15.7142 9.98196 15.7142H9.99089C10.3854 15.7142 10.7052 15.3944 10.7052 14.9999C10.7052 14.6054 10.3854 14.2856 9.99089 14.2856H9.98196Z"/></svg>',
				laptop: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.28592 3.57139C3.89143 3.57139 3.57164 3.89119 3.57164 4.28568V11.4285C3.57164 11.823 3.89143 12.1428 4.28592 12.1428H15.7145C16.109 12.1428 16.4288 11.823 16.4288 11.4285V4.28568C16.4288 3.89119 16.109 3.57139 15.7145 3.57139H4.28592ZM2.14307 4.28568C2.14307 3.10221 3.10246 2.14282 4.28592 2.14282H15.7145C16.898 2.14282 17.8573 3.10221 17.8573 4.28568V11.4285C17.8573 12.612 16.898 13.5714 15.7145 13.5714H4.28592C3.10246 13.5714 2.14307 12.612 2.14307 11.4285V4.28568Z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M1.42871 15.7143C1.42871 15.3198 1.74851 15 2.143 15H17.8573C18.2518 15 18.5716 15.3198 18.5716 15.7143C18.5716 16.1088 18.2518 16.4286 17.8573 16.4286H2.143C1.74851 16.4286 1.42871 16.1088 1.42871 15.7143Z"/></svg>',
				desktop: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.14293 2.27281C2.59739 2.27281 2.14293 2.72149 2.14293 3.29013V11.9481C2.14293 12.5168 2.59739 12.9655 3.14293 12.9655H16.8572C17.4028 12.9655 17.8572 12.5168 17.8572 11.9481V3.29013C17.8572 2.72149 17.4028 2.27281 16.8572 2.27281H3.14293ZM0.714355 3.29013C0.714355 1.94608 1.79491 0.844238 3.14293 0.844238H16.8572C18.2052 0.844238 19.2858 1.94608 19.2858 3.29013V11.9481C19.2858 13.2922 18.2052 14.394 16.8572 14.394H3.14293C1.79491 14.394 0.714355 13.2922 0.714355 11.9481V3.29013Z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.85693 17.143C5.85693 16.7485 6.17673 16.4287 6.57122 16.4287H13.4284C13.8229 16.4287 14.1427 16.7485 14.1427 17.143C14.1427 17.5375 13.8229 17.8573 13.4284 17.8573H6.57122C6.17673 17.8573 5.85693 17.5375 5.85693 17.143Z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.99993 12.9656C10.3944 12.9656 10.7142 13.2854 10.7142 13.6799V17.1431C10.7142 17.5376 10.3944 17.8573 9.99993 17.8573C9.60544 17.8573 9.28564 17.5376 9.28564 17.1431V13.6799C9.28564 13.2854 9.60544 12.9656 9.99993 12.9656Z"/></svg>',
			}
		}

		render() {
			const svg = _.get(this.icons, this.props.icon);
			return _.isEmpty(svg) ? 
				el( wp.components.Icon, this.props ) : 
				el( "span", { title: this.props.title, dangerouslySetInnerHTML: { __html: svg } } );
		}
	}

	/**
	 * Controls an ElegantIcon inside a button.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} ButtonIconControl component.
	 */
	this.ButtonIconControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {

			const enableHideEmpty = this.props.enableHideEmpty;
			const onChange = this.props.onChange;
			const value = this.props.value;
			const {
				content = '',
				position = 'after',
				size = '100%',
				margin = '10px',
				hideEmpty = false
			} = value;

			return el( greyd.components.AdvancedPanelBody, {
				title: __( 'Icon', 'greyd_hub' ),
				initialOpen: false,
				holdsChange: !_.isEmpty(content),
				..._.omit(_.clone(this.props), ["value", "onChange"])
			}, [
				el( greyd.components.IconPicker, {
					value: content,
					onChange: (newValue) => onChange( { ...value, content: newValue } ),
				} ),
				el( 'div', { className: 'flex' }, [
					el( 'span', {}, __( 'Position', 'greyd_hub' ) ),
					el( greyd.components.ButtonGroupControl, {
						value: position,
						style: {
							marginTop: 0,
							paddingBottom: "20px"
						},
						options: [
							{
								label: __( "Left", 'greyd_hub' ),
								value: "before"
							},
							{
								label: __( "Right", 'greyd_hub' ),
								value: "after"
							},
						],
						onChange: (newValue) => onChange( { ...value, position: newValue } ),
					} ),
				] ),
				el( greyd.components.RangeUnitControl, {
					value: size,
					label: __( "Size", 'greyd_hub' ),
					units: ['%', 'px', 'em'],
					max: { '%': 300, px: 60, 'em': 3 },
					onChange: (newValue) => onChange( { ...value, size: newValue } ),
				} ),
				el( greyd.components.RangeUnitControl, {
					value: margin,
					label: __( "Space", 'greyd_hub' ),
					max: 50,
					onChange: (newValue) => onChange( { ...value, margin: newValue } ),
					supportsPresets: true,
				} ),
				enableHideEmpty && content != '' && el( wp.components.ToggleControl, {
					label: __( "Render the button when text is empty", 'greyd_hub' ),
					checked: !hideEmpty,
					onChange: (newValue) => onChange( { ...value, hideEmpty: !newValue } ),
					help: (
						hideEmpty
						? __( "Enable this option to use this as an icon button.", 'greyd_hub' )
						: __( "Disable this within dynamic templates to keep the button optional.", 'greyd_hub' )
					)
				} )
			] );
		}
	}

	/**
	 * Renders an ElegantIcon inside a button.
	 * 
	 * @property {string} value Current value.
	 * @property {string} position Current position (before|after). Does't render if it doesn't match value.position
	 * 
	 * @returns {object} RenderButtonIcon component.
	 */
	this.RenderButtonIcon = class extends wp.element.Component {
		render() {

			const {
				value,
				position: renderPosition
			} = this.props;
			const {
				content = '',
				position = 'after',
				size = '100%',
				margin = '10px'
			} = value;

			if ( _.isEmpty(value.content) || renderPosition !== position ) {
				return null;
			}

			const iconStyle = {
				verticalAlign: 'middle',
				fontSize: size
			};
			if ( position == 'before' )
				iconStyle.marginRight = wp.blockEditor.getSpacingPresetCssVar( margin );
			else
				iconStyle.marginLeft = wp.blockEditor.getSpacingPresetCssVar( margin );

			return el( 'span', {
				className: content,
				style: iconStyle,
				'aria-hidden': 'true'
			} );
		}
	}
	/**
	 * Deprecated version of RenderButtonIcon component.
	 * uses wrong 'aria-hidden' attribute.
	 * needed for updating/migrating old saved strings.
	 */
	this.RenderButtonIconDeprecated = class extends wp.element.Component {
		render() {

			const {
				value,
				position: renderPosition
			} = this.props;
			const {
				content = '',
				position = 'after',
				size = '100%',
				margin = '10px'
			} = value;

			if ( _.isEmpty(value.content) || renderPosition !== position ) {
				return null;
			}

			const iconStyle = {
				verticalAlign: 'middle',
				fontSize: size
			};
			if ( position == 'before' ) {
				iconStyle.marginRight = margin;
			} else {
				iconStyle.marginLeft = margin;
			}

			return el( 'span', {
				className: content,
				style: iconStyle,
				ariaHidden: 'true'
			} );
		}
	}


	/**
	 * Renders a FontFamilyPicker with a reliable set of font families.
	 * 
	 * @property {object} blockProps [required] Block properties.
	 * @property {string} parentAttr attribute to get/assign styles (default: 'greydStyles')
	 * 
	 * @returns {object} FontFamilyPicker component.
	 */
	this.FontFamilyPicker = class extends wp.element.Component {
		getFontFamilies() {
			var values = false;
			var families = false;
			if ( typeof greyd.tools.getGlobalStylesValue === 'function' ) {
				values = greyd.tools.getGlobalStylesValue( 'typography.fontFamilies' );
			}
			if ( values ) {
				families = [
					...values.theme,
					...( values.custom ?? [] )
				];
			}
			return families;
		}	
		render() {
			return el( wp.blockEditor.__experimentalFontFamilyControl, {
				...this.props,
				fontFamilies: this.getFontFamilies(),
			} );
		}
	}

	/**
	 * Renders a FontSizePicker with a reliable set of font sizes.
	 * 
	 * @property {object} blockProps [required] Block properties.
	 * @property {string} parentAttr attribute to get/assign styles (default: 'greydStyles')
	 * 
	 * @returns {object} FontSizePicker component.
	 */
	this.FontSizePicker = class extends wp.element.Component {
		getFontSizes() {
			var values = false;
			var families = false;
			if ( typeof greyd.tools.getGlobalStylesValue === 'function' ) {
				values = greyd.tools.getGlobalStylesValue( 'typography.fontSizes' );
			}
			if ( values ) {
				families = [
					...values.theme,
					...( values.custom ?? [] )
				];
			}
			return families;
		}
		render() {

			const {
				label = __( "Font size", 'greyd_hub' ),
				...props
			} = this.props;

			return el( wp.components.BaseControl, {}, [
				el( wp.components.FontSizePicker, {
					label: label,
					fontSizes: this.getFontSizes(),
					...props,
				} )
			] );
		}
	}

	/**
	 * Renders all CustomButtonStyles panels (colors, typo, border...)
	 * 
	 * @property {object} blockProps [required] Block properties.
	 * @property {string} parentAttr attribute to get/assign styles (default: 'greydStyles')
	 * 
	 * @returns {object} CustomButtonStyles component.
	 */
	this.CustomButtonStyles = class extends wp.element.Component {

		render() {

			const {
				parentAttr = 'greydStyles',
				blockProps,
				supportsActive = false,
				enabled = true
			} = this.props;

			return el( wp.element.Fragment, {}, [
				el( 'div', { 
					className: enabled ? '' : 'hidden',
				}, [
					// colors
					el( greyd.components.StylingControlPanel, {
						title: __("Colors", 'greyd_hub'),
						initialOpen: false,
						supportsHover: true,
						supportsActive: supportsActive,
						holdsColors: [
							{ 
								color: _.has(blockProps.attributes, parentAttr+'.color') ? blockProps.attributes[parentAttr].color : '', 
								title: __("Text color", 'greyd_hub') 
							},
							{ 
								color: _.has(blockProps.attributes, parentAttr+'.background') ? blockProps.attributes[parentAttr].background : '', 
								title: __("Background", 'greyd_hub') 
							}
						],
						blockProps: blockProps,
						parentAttr: parentAttr,
						controls: [
							{
								label: __("Text color", 'greyd_hub'),
								attribute: "color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							},
							{
								label: __("Background", 'greyd_hub'),
								attribute: "background",
								control: greyd.components.ColorGradientPopupControl,
								contrast: {
									default: _.has(blockProps.attributes, parentAttr+'.color') ? blockProps.attributes[parentAttr].color : '',
									hover: _.has(blockProps.attributes, parentAttr+'.hover.color') ? blockProps.attributes[parentAttr].hover.color : '',
								}
							}
						]
					} ),
					// typo
					el( greyd.components.StylingControlPanel, {
						title: __("Typography", 'greyd_hub'),
						initialOpen: false,
						blockProps: blockProps,
						parentAttr: parentAttr,
						controls: [
							{
								label: __('Size'),
								attribute: "fontSize",
								control: greyd.components.FontSizePicker,
								// fontSizes: greyd.data.fontSizes,
								// control: greyd.components.RangeUnitControl,
								// min: 0,
								// max: { px: 100, em: 30, rem: 30, vh: 100, vw: 100 },
								// units: [ 'px', 'em', 'rem', 'vh', 'vw' ],
								// __nextHasNoMarginBottom: true,
							},
							{
								label: __('Font family'),
								attribute: "fontFamily",
								control: greyd.components.FontFamilyPicker,
							},
							/**
							 * Support for font appearance.
							 * Value is saved as object, eg.: { fontWeight: 100, fontStyle: 'italic' }
							 * @since 1.3.3
							 */
							{
								label: __('Appearance'),
								attribute: "__experimentalfontAppearance",
								control: wp.blockEditor.__experimentalFontAppearanceControl
							},
							{
								label: __('Letter spacing'),
								attribute: "letterSpacing",
								control: wp.blockEditor.__experimentalLetterSpacingControl
							},
							{
								label: __('Text decoration'),
								attribute: "textDecoration",
								control: wp.blockEditor.__experimentalTextDecorationControl
							},
							{
								label: __('Letter case'),
								attribute: "textTransform",
								control: wp.blockEditor.__experimentalTextTransformControl
							}
						]
					} ),
					// padding + responsive
					el( greyd.components.StylingControlPanel, {
						title: __("Spaces", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: blockProps,
						parentAttr: parentAttr,
						controls: [ {
							label: __('Padding', 'greyd_hub'),
							attribute: "padding",
							control: greyd.components.DimensionControl,
						} ]
					} ),
					// border-radius
					el( greyd.components.StylingControlPanel, {
						title: __("Border radius", 'greyd_hub'),
						initialOpen: false,
						blockProps: blockProps,
						parentAttr: parentAttr,
						controls: [ {
							label: __("Border radius", 'greyd_hub'),
							attribute: "borderRadius",
							control: greyd.components.DimensionControl,
							labels: {
								"all": __("All corners", "greyd_hub"),
							},
							sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
							type: "string"
						} ]
					} ),
					// border + hover
					el( greyd.components.StylingControlPanel, {
						title: __("Border", 'greyd_hub'),
						blockProps: blockProps,
						parentAttr: parentAttr,
						supportsHover: true,
						supportsActive: supportsActive,
						controls: [
							{
								label: __("Border", 'greyd_hub'),
								attribute: "border",
								control: greyd.components.BorderControl,
							}
						]
					} ),
					// shadow + hover
					el( greyd.components.StylingControlPanel, {
						title: __("Shadow", 'greyd_hub'),
						blockProps: blockProps,
						parentAttr: parentAttr,
						supportsHover: true,
						supportsActive: supportsActive,
						controls: [
							{
								label: __("Drop shadow", 'greyd_hub'),
								attribute: "boxShadow",
								control: greyd.components.DropShadowControl,
							}
						]
					} )
				] )
			] );
		}
	}

	/**
	 * Renders an advanced PanelBody with hover & responsive buttons.
	 * 
	 * @property {string} title Title of the section.
	 * @property {bool} holdsChange If the panel holds a changed value.
	 * @property {array} holdsColors Show color Indicators if value is changed.
	 * @property {bool} featureTag Show tag.
	 * ---- hover ----
	 * @property {bool} supportsHover Whether hover controls are supported.
	 * @property {bool} isHoverEnabled Whether hover controls are toggled on.
	 * @property {callback} onHoverToggle Function to be called when hover controls are toggled.
	 * ---- responsive ----
	 * @property {bool} supportsResponsive Whether responsive controls are supported.
	 * @property {bool} isResponsiveEnabled Whether responsive controls are toggled on.
	 * @property {callback} onResponsiveToggle Function to be called when responsive controls are toggled.
	 */
	this.AdvancedPanelBody = class extends wp.element.Component {

		constructor () {
			super();
		}

		render() {

			const {
				title = "",
				holdsChange = false,
				holdsColors = false,
				// hover props
				supportsHover = false,
				isHoverEnabled = false,
				onHoverToggle = null,
				// responsive props
				supportsResponsive = false,
				isResponsiveEnabled = false,
				onResponsiveToggle = null,
			} = this.props;

			const holdsColorBool = holdsColors && typeof holdsColors === 'object' && holdsColors.length;
			const supportsTabs = Boolean( supportsResponsive || supportsHover );

			return el( wp.components.PanelBody, {
				title: el( "div", {
					style: {
						display: 'flex',
						alignItems: 'center',
					}
				}, [
					el( "span", {}, title ),

					this.props.featureTag && el( "span", {
						className: "feature-tag",
						style: { marginLeft: '5px' }
					}, this.props.featureTag ),

					// change indicator
					holdsChange && !holdsColorBool && el( "span", { className: "change_holder" } ),

					// color indicator
					holdsColorBool && el( "span", {
						className: "block-editor-panel-color-gradient-settings__panel-title",
					}, [
						Object.keys( holdsColors ).map( ( k, i ) => {
							const val = holdsColors[ k ];
							if ( typeof val.color !== 'undefined' ) {
								// console.log(val.color);
								const color = greyd.tools.getGradientValue( greyd.tools.getColorValue( val.color ) );
								if ( _.isEmpty( color ) ) {
									return null;
								}
								else {
									return el( wp.components.ColorIndicator, { colorValue: color, title: val.title } );
								}
							}

						} )
					] ),

					/* wp tooltip component may only have 1 single react child */
					// responsive & hover
					supportsTabs && el( "div", {
						className: "panel_buttons"
					}, [
						supportsResponsive && el( wp.components.Tooltip, {
							text: __( "Change per breakpoint", "greyd_hub" )
						}, el( "div", {}, [
							el( "button", {
								className: "button button-ghost" + ( isResponsiveEnabled ? " active" : "" ),
								onClick: ( e ) => {
									e.stopPropagation();
									if ( typeof onResponsiveToggle === "function" ) onResponsiveToggle();
								}
							}, [
								el( greyd.components.GreydIcon, {
									icon: "mobile",
									width: 12
								} )
							] )
						] ) ),
						supportsHover && el( wp.components.Tooltip, {
							text: __( "Change on hover", "greyd_hub" )
						}, el( "div", {}, [
							el( "button", {
								className: "button button-ghost" + ( isHoverEnabled ? " active" : "" ),
								onClick: ( e ) => {
									e.stopPropagation();
									if ( typeof onHoverToggle === "function" ) onHoverToggle();
								}
							}, [
								el( greyd.components.GreydIcon, {
									icon: "hover",
									width: 12
								} )
							] )
						] ) )
					] )
				] ),
				..._.omit( {
					...this.props,
					className: [
						_.has( this.props, 'className' ) ? this.props.className : '',
						holdsColorBool ? 'block-editor-panel-color-gradient-settings' : ''
					].join( ' ' )
				}, [ "title" ] )
			} );
		}
	}

	/**
	 * Renders a full section with controls that automatically set styles for different states.
	 * 
	 * @property {object} blockProps [required] Block properties.
	 * @property {string} title Title of the section.
	 * @property {bool} initialOpen If the panel is open on load.
	 * @property {bool} supportsResponsive Whether the responsive tabs are supported.
	 * @property {bool} supportsHover Whether a hover tab is supported.
	 * @property {bool} supportsActive Whether the active tab is supported (only works with hover).
	 * @property {string} parentAttr attribute to get/assign styles (default: 'greydStyles')
	 * @property {string} classAttr attribute to get/assign preview class (like '._hover') to.
	 * @property {object[]} controls [required] Array of control objects:
	 * 		@property {string} label Label of the control
	 * 		@property {string} attribute CSS attribute to set
	 * 		@property {object} control React component
	 * @property {string} help Help text.
	 */
	this.StylingControlPanel = class extends wp.element.Component {
		
		constructor(props) {
			super(props);

			// console.log("constructor called");
			// console.log(props);
			// console.log(_.clone(this));

			this.setConfig 			= this.setConfig.bind(this);
			this.checkHoldsChange 	= this.checkHoldsChange.bind(this);
			this.setHoverEnabled 	= this.setHoverEnabled.bind(this);
			this.onHoverToggle 		= this.onHoverToggle.bind(this);
			this.removeHoverStyles 	= this.removeHoverStyles.bind(this);
			this.setResponsiveEnabled 		= this.setResponsiveEnabled.bind(this);
			this.onResponsiveToggle 		= this.onResponsiveToggle.bind(this);
			this.removeResponsiveStyles 	= this.removeResponsiveStyles.bind(this);
			this.handleTabSwitch 			= this.handleTabSwitch.bind(this);
			this.addOrRemoveClass 			= this.addOrRemoveClass.bind(this);

			this.config = {
				initialOpen: false,
				supportsResponsive: false,
				supportsHover: false,
				supportsActive: false,
				parentAttr: "greydStyles",
				classAttr: "className",
				hoverTabs: [
					{
						label: __("Normal", "greyd_hub"),
						slug: ""
					},
					{
						label: __("Hover", "greyd_hub"),
						slug: "hover"
					}
				],
				responsiveTabs: [
					{
						title: greyd.tools.makeBreakpointTitle("lg"),
						label: el( greyd.components.GreydIcon, { icon: "desktop" } ),
						slug: ""
					},
					{
						title: greyd.tools.makeBreakpointTitle("md"),
						label: el( greyd.components.GreydIcon, { icon: "laptop" } ),
						slug: "lg"
					},
					{
						title: greyd.tools.makeBreakpointTitle("sm"),
						label: el( greyd.components.GreydIcon, { icon: "tablet" } ),
						slug: "md"
					},
					{
						title: greyd.tools.makeBreakpointTitle("xs"),
						label: el( greyd.components.GreydIcon, { icon: "mobile" } ),
						slug: "sm"
					}
				]
			}

			// get state from global state
			this.state = this.getGlobalState();
			// this.state = { ...this.getDefault() };

		}

		getDefault() {
			// console.log("defaulting");
			return {
				isHoverEnabled: false,
				isResponsiveEnabled: false,
				activeTab: "", // normal
			}
		}

		componentDidMount() {
			// console.log("componentDidMount");			
			// this.setConfig();
			this.setHoverEnabled();
			this.setResponsiveEnabled();
			this.addOrRemoveClass("remove");

			// subscribe to viewport change
			var viewport = this.getViewport();
			this.unsubscribe = wp.data.subscribe(() => {
				if (this.state.isResponsiveEnabled) {
					var newViewport = this.getViewport();
					if (viewport !== newViewport) {
						// console.log("viewport changed to "+newViewport+" - update tabs");
						this.setState({
							...this.state,
							activeTab: this.getActiveTab(newViewport)
						});
						viewport = newViewport;
					}
				}
			});
		}

		componentDidUpdate(prevProps) {
			// console.log("componentDidUpdate");
			// https://reactjs.org/docs/react-component.html#componentdidupdate

			// save to global state 
			this.setGlobalState();
		}

		/**
		 * Global Component States
		 * when preview is changed, the components gets unmounted and looses its state.
		 * here, the states are kept in a global store to fetch them after the preview has changed.
		 * 
		 * using the 'core/editor' store is hacky, but the clean solution is overkill. could be implemented though:
		 * https://wordpress.stackexchange.com/questions/324979/getting-a-custom-gutenberg-components-state-from-outside-that-component
		 */
		setGlobalState() {
			var id = this.props.blockProps.clientId;
			var name = this.props.title.toLowerCase().split(' ').join('');
			var old_states = _.has(wp.data.select('core/editor'), 'componentStates['+id+']') ? wp.data.select('core/editor').componentStates[id] : {};
			wp.data.select('core/editor').componentStates = { [id]: { ...old_states, [name]: _.clone(this.state) } };
		}
		getGlobalState() {
			var id = this.props.blockProps.clientId;
			var name = this.props.title.toLowerCase().split(' ').join('');
			var state = this.getDefault();
			if (_.has(wp.data.select('core/editor'), 'componentStates['+id+']['+name+']')) {
				// console.log(wp.data.select('core/editor').componentStates);
				state = wp.data.select('core/editor').componentStates[id][name];
				if (state.isResponsiveEnabled) {
					state.activeTab = this.getActiveTab(this.getViewport());
				}
			}
			return state;
		}

		/**
		 * Set the class configuration
		 */
		setConfig() {
			const { config, props } = this;

			// get any user config...
			const userConfig = _.pick(props, _.keys(config));

			// avoid errors...
			const finalConfig = _.clone(config);

			// merge
			_.assign(finalConfig, userConfig);

			this.config = finalConfig;
		}

		/**
		 * Set isHoverEnabled initially.
		 */
		setHoverEnabled() {
			const { parentAttr, supportsHover } = this.config;
			const { controls, blockProps } = this.props;

			if ( !supportsHover ) return false;

			let hoverStyles = _.get(blockProps.attributes, parentAttr+".hover");
			if (this.config.supportsActive) 
				hoverStyles = { ...hoverStyles, ..._.get(blockProps.attributes, parentAttr+".active") };
			
			if ( !_.isEmpty(hoverStyles) ) {
				controls.forEach((control) => {

					if ( ! control || ! _.has(control, 'attribute') ) return;

					var att = _.get(hoverStyles, control.attribute);
					if ( !_.isEmpty(att) || _.isNumber(att) ) {
						this.setState({
							...this.state,
							isHoverEnabled: true
						});
						return;
					}
				});
			}
		}

		/**
		 * Set isResponsiveEnabled initially.
		 */
		setResponsiveEnabled() {
			const { parentAttr, supportsResponsive } = this.config;
			const { controls, blockProps } = this.props;

			if ( !supportsResponsive ) return false;

			const responsiveStyles = {
				..._.get( blockProps.attributes, parentAttr+".responsive.sm" ),
				..._.get( blockProps.attributes, parentAttr+".responsive.md" ),
				..._.get( blockProps.attributes, parentAttr+".responsive.lg" ),
			};
			
			if ( !_.isEmpty(responsiveStyles) ) {
				controls.forEach((control) => {

					if ( ! control || ! _.has(control, 'attribute') ) return;

					var att = _.get(responsiveStyles, control.attribute);
					if ( !_.isEmpty(att) || _.isNumber(att) ) {
						this.setState({
							...this.state,
							isResponsiveEnabled: true
						});
						return;
					}
				});
			}
		}

		/**
		 * When hover is toggled off/on.
		 */
		onHoverToggle() {
			// switched off
			if ( this.state.isHoverEnabled ) {
				this.removeHoverStyles();
				this.addOrRemoveClass("remove");
				this.setState( {
					isHoverEnabled: false,
					activeTab: ""
				} );
			}
			// switched on
			else {
				this.addOrRemoveClass("add");
				this.setState( {
					isHoverEnabled: true,
					activeTab: "hover"
				} );
			}
		}

		/**
		 * Remove hover styles controlled by this section.
		 * Usually called when hover is toggled off.
		 */
		removeHoverStyles() {
			const { controls, blockProps } = this.props;
			const { parentAttr } = this.config;

			// let hoverStyles = _.get(blockProps.attributes, parentAttr+".hover");

			// controls.forEach((control) => {
			// 	delete hoverStyles[control.attribute];
			// });
			blockProps.setAttributes( { 
				[parentAttr] : _.omit(_.get(blockProps.attributes, parentAttr ), ['hover', 'active']) 
			} );
			// blockProps.setAttributes( { [parentAttr] : {
			// 	..._.get(blockProps.attributes, parentAttr),
			// 	hover: hoverStyles
			// } } );
		}

		/**
		 * When responsive is toggled off/on.
		 */
		onResponsiveToggle() {
			this.setState( {
				isResponsiveEnabled: !this.state.isResponsiveEnabled,
				activeTab: this.getActiveTab(this.getViewport())
			}, () => {
				// switched off
				if ( !this.state.isResponsiveEnabled ) {
					this.removeResponsiveStyles();
					this.setPreviewWindow( "Desktop" );
				}
			});
		}

		/**
		 * Remove responsive styles controlled by this section.
		 * Usually called when responsive is toggled off.
		 */
		removeResponsiveStyles() {
			const { controls, blockProps } = this.props;
			const { parentAttr } = this.config;

			// let responsiveStyles = _.get(blockProps.attributes, parentAttr+".responsive");

			// controls.forEach((control) => {
			// 	delete responsiveStyles[control.attribute];
			// });
			blockProps.setAttributes( { [parentAttr] : {
				..._.get(blockProps.attributes, parentAttr),
				// responsive: responsiveStyles
				responsive: {}
			} } );
		}

		/**
		 * Check if the section holds a change.
		 * @returns {bool}
		 */
		checkHoldsChange() {
			const { controls, blockProps } = this.props;
			const { parentAttr } = this.config;

			let holdsChange = false;

			controls.forEach((control) => {

				if ( ! control || _.isEmpty(control) ) return;
				
				const val = _.get(blockProps.attributes[parentAttr], control.attribute);
				if ( !_.isEmpty(val) ) {
					holdsChange = true;
					return;
				}
			});
			return holdsChange;
		}

		/**
		 * 
		 * @param {string} tab ("" | "hover" | "lg" | "md" | "sm")
		 */
		handleTabSwitch(tab) {
			// console.log(tab);
			// console.log(this.config);
			// console.log(this.state);
			this.setState({
				...this.state,
				activeTab: _.isEmpty(tab) ? "" : tab
			}, () => {
				if ( tab === "hover" ) {
					this.addOrRemoveClass("add");
				}
				else if ( tab === "active" ) {
					this.addOrRemoveClass("add", '_active');
				}
				else {
					if ( this.state.isHoverEnabled ) {
						this.addOrRemoveClass("remove");
					}
					else if ( this.state.isResponsiveEnabled ) {
						const viewports = {
							"": "Desktop",
							"lg": "Desktop",
							"md": "Tablet",
							"sm": "Mobile",
						};
						this.setPreviewWindow( viewports[tab] );
					}
				}
			});
		}

		/**
		 * Add or remove the class "_hover" to the previewed object.
		 * @param {string} type ("add" | "remove")
		 * @param {string} className The css-class to add.
		 */
		addOrRemoveClass(type, className='_hover') {
			const { classAttr } = this.config;
			const { blockProps } = this.props;
			const allClasses = _.toString(_.get(blockProps.attributes, classAttr)).replace(/\s?(_hover|_active)/g, "");

			// remove "wp-block-" class, as it is auto-assigned
			const classList = greyd.tools.getCleanClassList(allClasses);

			if ( type === "add" ) {
				classList.push(className);
			}

			blockProps.setAttributes({
				[classAttr]: classList.join(" ")
			});
		}

		/**
		 * Set the editor preview window accordingly.
		 * @param {string} viewport ("Desktop" | "Tablet" | "Mobile")
		 */
		setPreviewWindow(viewport) {
			// console.log(viewport);
			var currentViewport = this.getViewport();
			if (currentViewport && !_.isEqual(currentViewport, viewport)) {
				// console.log("switch viewport");
				this.setViewport(viewport);
			}
		};

		/**
		 * Experimental PreviewDeviceType functions
		 * viewports: ("Desktop" | "Tablet" | "Mobile")
		 */
		getViewport() {
			if ( _.has(wp.data.select( 'core/editor' ), 'getDeviceType') ) {
				return wp.data.select( 'core/editor' ).getDeviceType();
			} else {
				var store = ( pagenow && pagenow == 'site-editor' ) ? "core/edit-site" : "core/edit-post";
				if ( !_.has(wp.data.select(store), "__experimentalGetPreviewDeviceType") ) return false;
				return wp.data.select(store).__experimentalGetPreviewDeviceType();
			}
		}
		setViewport(viewport) {
			if ( _.has(wp.data.select( 'core/editor' ), 'setDeviceType') ) {
				return wp.data.select( 'core/editor' ).setDeviceType();
			} else {
				var store = ( pagenow && pagenow == 'site-editor' ) ? "core/edit-site" : "core/edit-post";
				if ( !_.has(wp.data.dispatch(store), "__experimentalSetPreviewDeviceType") ) return false;
				wp.data.dispatch(store).__experimentalSetPreviewDeviceType(viewport);
			}
		}
		getActiveTab(viewport) {
			var activeTab = "";
			if (viewport == "Tablet") activeTab = "md";
			else if (viewport == "Mobile") activeTab = "sm";
			return activeTab;
		}

		/**
		 * Cleanup when we leave the component.
		 */
		componentWillUnmount() {
			// console.log("componentWillUnmount");
			this.addOrRemoveClass("remove");
			// unsubscribe from viewport change
			// see componentDidMount()
			this.unsubscribe();
		}

		render() {
			this.setConfig();

			/**
			 * Vars
			 */
			const {
				supportsHover = false,
				supportsActive = false,
				supportsResponsive = false,
				initialOpen = false,
				parentAttr,
				hoverTabs,
				responsiveTabs
			} = this.config;

			const {
				title,
				controls,
				blockProps
			} = this.props;

			const activeTab  			= this.state.activeTab;
			const isHoverEnabled 		= Boolean( supportsHover ? this.state.isHoverEnabled : false );
			const isResponsiveEnabled 	= Boolean( supportsResponsive ? this.state.isResponsiveEnabled : false );
			const supportedTabs 		= (
				isHoverEnabled
				? (
					supportsActive ? [ ...hoverTabs, { slug: "active", label: __("Active", "greyd_hub") } ] : hoverTabs
				) : (
					isResponsiveEnabled ? responsiveTabs : [{slug: ""}]
				)
			);

			return el( greyd.components.AdvancedPanelBody, {
				className: this.props.className,
				title: title,
				initialOpen: Boolean( initialOpen ),
				holdsChange: Boolean( this.checkHoldsChange() ),
				holdsColors: this.props.holdsColors,

				// // hover props
				supportsHover: Boolean( supportsHover ),
				isHoverEnabled: isHoverEnabled,
				onHoverToggle: this.onHoverToggle,

				// // responsive props
				supportsResponsive: Boolean( supportsResponsive ),
				isResponsiveEnabled: isResponsiveEnabled,
				onResponsiveToggle: this.onResponsiveToggle,
			}, [
				// Tabs
				( supportedTabs.length < 2 ? null :
					el( "div", { className: "greyd_tabs" }, [
						...supportedTabs.map((tab) => {
							return el( "span", {
								title: _.has(tab, 'title') ? tab.title : "",
								className: "tab"+(activeTab === tab.slug ? " active" : ""),
								onClick: () => {
									this.handleTabSwitch( tab.slug );
								}
							}, tab.label );
						})
					] )
				),
				...controls.map( (control) => {

					if ( ! control || _.isEmpty(control) ) return;

					if ( _.has(control, 'hidden') && control.hidden ) {
						if (control.hidden === true) return null;
						else if (typeof control.hidden === 'object') {
							if (_.has(control.hidden, activeTab) && control.hidden[activeTab] === true) return null;
						}
					}

					const { control: controlType, attribute, label } = control;
					const controlProps = {
						...control,
						label: label
					};
		
					var ctrl = controlType;
					// if (typeof ctrl === 'string') ctrl = _.get( greyd_controls, ctrl );

					switch ( activeTab ) {
						// default tab
						case "":
							// console.log(parentAttr+"."+attribute);
							// console.log(_.get( blockProps.attributes, parentAttr+"."+attribute));
							return el( ctrl, {
								...controlProps,
								value: _.get( blockProps.attributes, parentAttr+"."+attribute ) ?? "",
								onChange: (value) => {
									blockProps.setAttributes({ [parentAttr] : {
										..._.get(blockProps.attributes, parentAttr),
										[attribute]: value
									} });
									if (_.has(controlProps, 'onChange')) {
										controlProps.onChange( { [attribute]: value } );
									}
								}
							} );
							break;
						// hover tab
						case "hover":
							// console.log(parentAttr+".hover."+attribute);
							// console.log(_.get( blockProps.attributes, parentAttr+".hover."+attribute));
							return el( ctrl, {
								...controlProps,
								hover: true,
								value: _.get( blockProps.attributes, parentAttr+".hover."+attribute) ?? "",
								onChange: (value) => {
									blockProps.setAttributes({ [parentAttr] : {
										..._.get(blockProps.attributes, parentAttr),
										hover: {
											..._.get(blockProps.attributes, parentAttr+".hover"),
											[attribute]: value
										}
									} });
									if (_.has(controlProps, 'onChange')) {
										controlProps.onChange( { hover: { [attribute]: value } } );
									}
								}
							} );
							break;
						case "active":
							// console.log(parentAttr+".active."+attribute);
							// console.log(_.get( blockProps.attributes, parentAttr+".active."+attribute));
							return el( ctrl, {
								...controlProps,
								value: _.get( blockProps.attributes, parentAttr+".active."+attribute) ?? "",
								onChange: (value) => {
									blockProps.setAttributes({ [parentAttr] : {
										..._.get(blockProps.attributes, parentAttr),
										active: {
											..._.get(blockProps.attributes, parentAttr+".active"),
											[attribute]: value
										}
									} });
									if (_.has(controlProps, 'onChange')) {
										controlProps.onChange( { active: { [attribute]: value } } );
									}
								}
							} );
							break;
						// responsive tabs
						default:
							return el( ctrl, {
								...controlProps,
								value: _.get( blockProps.attributes, parentAttr+".responsive."+activeTab+"."+attribute) ?? "",
								onChange: (value) => {
									blockProps.setAttributes({ [parentAttr] : {
										..._.get(blockProps.attributes, parentAttr),
										responsive: {
											..._.get(blockProps.attributes, parentAttr+".responsive"),
											[activeTab]: {
												..._.get(blockProps.attributes, parentAttr+".responsive."+activeTab),
												[attribute]: value
											}
										}
									} });
									if (_.has(controlProps, 'onChange')) {
										controlProps.onChange( { [activeTab]: { [attribute]: value } } );
									}
								}
							} );
							break;
					}
				} ),

				// help text
				this.props.help && el( "p", { className: "greyd-inspector-help" }, this.props.help )
			] );
		}
	}

	/**
	 * Render style attributes as html <style> element.
	 * 
	 * @property {string} selector [required] CSS selector.
	 * @property {object} styles [required] Style objects with selectors, eg:
	 * {
	 *    "": {
	 *         margin: "12px"
	 *    },
	 *    " .inner": {
	 *         padding: "4px"
	 *    }
	 * }
	 * @property {bool} important [optional] Whether the CSS-values are important.
	 * @property {string} activeSelector Selector for active state, eg. '.is-active', ':checked + span'
	 * 
	 * @returns <style>
	 */
	this.RenderPreviewStyles = class extends wp.element.Component {
		render() {
			const {
				styles,
				selector,
				important = false,
				activeSelector = null
			} = this.props;
			const finalCSS = greyd.tools.composeCSS( styles, selector, true, important, activeSelector );
		
			if ( finalCSS == "" ) return "";

			return el( "style", {
				className: "greyd-styles",
				dangerouslySetInnerHTML: { __html: finalCSS }
			} );
		}
	}

	/**
	 * Save style attributes as html <style> element.
	 * 
	 * @property {string} selector [required] CSS selector.
	 * @property {object} styles [required] Style objects with selectors.
	 * @property {bool} important [optional] Whether the CSS-values are important.
	 * @property {string} activeSelector Selector for active state, eg. '.is-active', ':checked + span'
	 * 
	 * @returns <style>
	 */
	this.RenderSavedStyles = class extends wp.element.Component {
		render() {
			const {
				styles,
				selector,
				important = false,
				activeSelector = null
			} = this.props;
			const finalCSS = greyd.tools.composeCSS( styles, selector, false, important, activeSelector );
		
			if ( finalCSS == "" ) return "";

			return el( "style", {
				className: "greyd-styles",
				dangerouslySetInnerHTML: { __html: finalCSS }
			} );
		}
	}

	/**
	 * Dynamic Image Picker
	 * 
	 * @property {bool} useIdAsTag Wether to use the @param id as the tag. This is used
	 *                             to support contentBox- & column-backgrounds with this
	 *                             control. They use the ID as a single dynamic attribute.
	 * @property {string} clientId Block clientId.
	 * @property {object} value    Current value
	 * @property {string} label    Label to be displayed.
	 * @property {string} allowedTypes Allowed media types. Defaults to 'image/*'.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.DynamicImageControl = class extends wp.element.Component {
		constructor() {
			super();

			this.onTypeChange  = this.onTypeChange.bind(this);
			this.onMediaChange = this.onMediaChange.bind(this);
			this.onTagChange   = this.onTagChange.bind(this);
			this.onClear       = this.onClear.bind(this);
		}

		getDefault() {
			return {
				type: 'file',
				id: -1,
				url: '',
				tag: ''
			}
		}

		onTypeChange( value ) {
			this.props.onChange( {
				...this.props.value,
				...this.props.useIdAsTag ? {
					id: value === 'dynamic' ? '' : -1,
					url: ''
				} : {
					type: value
				}
			} );
		}

		onMediaChange( value ) {
			this.props.onChange( {
				...this.props.value,
				id: value.id,
				url: value.url
			} );
		}

		onTagChange( value ) {
			this.props.onChange( {
				...this.props.value,
				...this.props.useIdAsTag ? {
					id: _.isEmpty(value) ? '' : '_'+value+'_',
					url: ''
				} : {
					tag: value
				}
			} );
		}

		onClear() {
			this.props.onChange( this.getDefault() );
		}

		render() {
			const {
				label = "",
				clientId,
				useIdAsTag = false, // to support content-box- & row- background images
				allowedTypes = 'image/*',
			} = this.props;

			const currentValue = {
				...this.getDefault(),
				...this.props.value
			}

			let {
				type, // 'dynamic' | 'file'
				id,   // -1 | postId
				url,  // url to the image
				tag   // '' | 'site-logo' | 'image' ...
			} = currentValue;

			if ( useIdAsTag ) {
				type = typeof currentValue.id === 'string' ? 'dynamic' : 'file';
				url  = currentValue.url;
				id   = parseInt(currentValue.id);
				tag  = currentValue.id.toString().replace(/(^_|_$)/g, '');
			}
			
			if (type == "file" && url == "" && id > -1) {
				if (greyd.data.media_urls[id]) url = greyd.data.media_urls[id].src;
			}

			let options = [
				{ label: __("Select source", 'greyd_hub'), value: '' },
				greyd.dynamic.tags.getCurrentOptions('file', clientId),
				{ label: __("Website", 'greyd_hub'), options: [
					{ label: __("Website logo", 'greyd_hub'), value: 'site-logo' },
				] },
			];

			/**
			 * Filter available dynamic images (files).
			 * @filter greyd_blocks_dynamic_files
			 * @since headless feature
			 * 
			 * @param {array} options		Array of dynamic file options
			 * @param {string} mode			'file'
			 * @param {string} clientId		clientId of this element
			 * 
			 * @return {array} options
			 */
			options = wp.hooks.applyFilters( 'greyd_blocks_dynamic_files', options, 'file', clientId );
	
			return el( wp.element.Fragment, {}, [
				el( wp.components.BaseControl, {}, [
					!_.isEmpty(label) && el( greyd.components.AdvancedLabel, {
						label: label,
						initialValue: this.getDefault(),
						currentValue: currentValue,
						onClear: this.onClear
					} ),
					el( greyd.components.ButtonGroupControl, {
						value: type,
						options: [
							{ label: __("From library", 'greyd_hub'), value: 'file' },
							{ label: __("Dynamic", 'greyd_hub'), value: 'dynamic' },
						],
						onChange: this.onTypeChange
					} ),
				] ),
				el( wp.components.BaseControl, { }, [

					// select media
					type === 'file' && el( wp.blockEditor.MediaUploadCheck, {
						fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') )
					}, [
						el( wp.blockEditor.MediaUpload, {
							allowedTypes: allowedTypes,
							value: id,
							onSelect: this.onMediaChange,
							render: (obj) => {
								return el( wp.components.Button, { 
									className: id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
									onClick: obj.open 
								}, id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: url } ) )
							},
						} ),
						id !== -1 ? el( wp.components.Button, {
							className: "is-link is-destructive",
							onClick: () => this.onMediaChange( {
								id: -1,
								url: ''
							} )	
						}, __( "Remove image", 'greyd_hub' ) ) : null
					] ),

					// dynamic option
					type === 'dynamic' && el( greyd.components.OptionsControl, {
						value: tag,
						options: options,
						onChange: this.onTagChange,
					} )
				] ),

				type === 'dynamic' && ( id > 0 || !_.isEmpty(url) ) && el( wp.components.BaseControl, {}, [
					el( "p", {
						className: 'components-base-control__help',
						style: {
							fontSize: 12,
							fontStyle: 'normal',
							color:' rgb(117, 117, 117)',
							marginTop: 0,
						},
					}, __( 'An image from the media library is stored. This serves as a placeholder and is displayed if the dynamic source is not available.', 'greyd_hub' ) ),
					el( wp.components.Button, {
						className: "is-link is-destructive",
						onClick: () => this.onMediaChange( {
							id: -1,
							url: ''
						} )
					}, __( "Remove image", 'greyd_hub' ) )
				] )
			] );
		}
	}

	/**
	 * Dynamic Image Picker
	 * 
	 * @property {bool} useIdAsTag Wether to use the @param id as the tag. This is used
	 *                             to support contentBox- & column-backgrounds with this
	 *                             control. They use the ID as a single dynamic attribute.
	 * @property {object} icon     What icon to use ( 'image' | 'database' )
	 * @property {object} value    Current value
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.ToolbarDynamicImageControl = class extends wp.element.Component {
		constructor() {
			super();

			this.state = {
				popoverVisible: false
			}
		}

		getDefault() {
			return {
				type: 'file',
				id: -1,
				url: '',
				tag: ''
			}
		}

		render() {

			const { popoverVisible } = this.state;
			const value = {
				...this.getDefault(),
				...this.props.value
			};
			const icon = this.props.icon && this.props.icon ==='image' ? greyd.tools.getCoreIcon('image') : 'database';

			const isValueSet = this.props.useIdAsTag ? (
				(typeof value.id === 'number' && value.id > 0) || (typeof value.id === 'string' && value.id.length > 0)
			) : (
				(value.type === 'file' && parseInt(value.id) > 0) || (value.type === 'dynamic' && !_.isEmpty(value.tag))
			);

			return el( wp.blockEditor.BlockControls, {}, [
				el( wp.components.ToolbarGroup, {}, [
					el( wp.components.ToolbarItem, {
						as: wp.components.ToolbarButton,
						icon: icon,
						onClick: () => this.setState({ popoverVisible: !popoverVisible }),
						title: __("Select image", 'greyd_hub'),
						isActive: popoverVisible,
						text: isValueSet ? __("Replace image", 'greyd_hub') : __("Select image", 'greyd_hub')
					} )
				] ),
				popoverVisible && el( wp.components.Popover, { className: 'components-greyd-select-popover' }, [
					el( "h4", { style: { margin: '0 0 0.5em' } }, __("Select source", 'greyd_hub') ),
					el( greyd.components.DynamicImageControl, this.props )
				] )
			] )
		}
	}

	/**
	 * Section Control
	 * 
	 * @property {string} title      Title text.
	 * @property {string} icon       Button icon.
	 * @property {string} buttonText Button text.
	 * @property {callback} onClick  Called when button is clicked.
	 */
	this.SectionControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {

			const {
				title = "",
				icon = "admin-tools",
				buttonText = "",
				onClick = () => {},
				isHeader = false
			} = this.props

			const body = el( 'div', {
				className: 'components-panel__row'
			}, [
				el( 'span', { }, title ),
				el( wp.components.Button, {
					icon: icon,
					className: 'components-button is-small greyd-section-button',
					onClick: onClick
				}, buttonText ),
			] );

			return isHeader ?  el( wp.components.PanelBody, { className: 'greyd-section-header' }, body ) : body;
		}
	}

	/**
	 * Parent Select control.
	 * This control lets users select a parent from the block list tree.
	 * The actual value that is returned is a valid css selector to 
	 * address this specific parent in the frontend, eg. the ID or className.
	 * If the selected parent does not have a accessible selector, we add
	 * a new class to it.
	 * 
	 * 
	 * @property {string} value Current selector value.
	 * @property {string} label Optional label to be displayed.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.ParentSelectControl = class extends wp.element.Component {
		render() {

			const {
				value,
				label,
				onChange
			} = this.props;

			// get data
			const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
			const blockParents = wp.data.select( 'core/block-editor' ).getBlockParents( selectedBlock.clientId );

			// get the parent selector
			const parentOptions = [];
			let   isParentFromOptionsSelected = false;
			blockParents.length && blockParents.forEach( clientId => {

				const block = wp.data.select( 'core/block-editor' ).getBlock( clientId );
				let label   = block.name
				let val     = 'CLIENTID=' + clientId;

				// the parent has in ID
				if ( _.has( block.attributes, 'anchor' ) && !_.isEmpty( block.attributes.anchor ) ) {
					label += ' (#' + block.attributes.anchor + ')';
					val = '#' + block.attributes.anchor;
				}
				// the parent has at least one className
				else if ( _.has( block.attributes, 'className' ) && !_.isEmpty( block.attributes.className ) ) {

					const parentClasses = block.attributes.className.split( ' ' );
					let selectedClass   = parentClasses[0];

					// use another class if one matches the current value
					parentClasses.every( parentClass => {
						if ( value === '.' + parentClass ) {
							selectedClass = parentClass;
							return false;
						}
					})
					
					label += ' (.' + selectedClass + ')';
					val = '.' + selectedClass;
				}
				// the parent has a greydClass
				else if ( _.has( block.attributes, 'greydClass' ) && !_.isEmpty( block.attributes.greydClass ) ) {
					label += ' (.' + block.attributes.greydClass + ')';
					val = '.' + block.attributes.greydClass;
				}

				if ( value === val ) {
					isParentFromOptionsSelected = true;
				}
				
				parentOptions.unshift( {
					label: label,
					value: val
				} )
			});

			// if a client ID is selected, we add a class to the parent block
			// and update the current value to the new selector.
			if ( !_.isEmpty(value) && value.substring(0, 9) === 'CLIENTID=' ) {
				const newGreydClass = greyd.tools.generateGreydClass();
				wp.data.dispatch('core/block-editor').updateBlockAttributes( value.replace('CLIENTID=', ''), { className: newGreydClass });
				onChange( '.' + newGreydClass );
			}

			// display option 'custom' when a selector is set that isn't
			// included in the blocks parents.
			const customOption = isParentFromOptionsSelected || _.isEmpty( value ) ? [] : [{
				label: __( "Individual", 'greyd_hub' ),
				value: value
			}];

			return el( wp.components.BaseControl, {
				label: label,
			}, [
				el( wp.components.SelectControl, {
					value: value,
					onChange: val => onChange( val ),
					options: [
						{
							label: __( "Select element", 'greyd_hub' ),
							value: ''
						},
						...parentOptions,
						...customOption,
					]
				} ),
				el( wp.components.TextControl, {
					className: 'code-input-control',
					value: value,
					onChange: val => onChange( val ),
					placeholder: __( 'e.g. .my-class, #anchor', 'greyd_hub' )
				} ),
			] )
		}
	}

	/**
	 * Select Control with an additional option for individual values
	 * that users can enter manually into a TextControl.
	 * 
	 * @property {string} value Current selector value.
	 * @property {string} label Optional label to be displayed.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {array} options Array of options to be displayed.
	 * @property {string} options[].label Label of the option.
	 * @property {string} options[].value Value of the option.
	 * @property {string} customLabel Label of the custom option, defaults to 'individuell'.
	 * @property {object} customControl React component to be used as custom control, defaults to TextControl.
	 * @property {callback} onCustomValueChange Callback function to be called when custom value is changed.
	 * @property {string} customPlaceholder Placeholder text for the custom control.
	 * @property {string} warning Warning text to be displayed above the control.
	 * 
	 * @returns {Component} SelectCustomControl
	 */
	this.SelectCustomControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {

			const {
				value,
				label,
				onChange,
				options,
				customLabel = __( "Individual", 'greyd_hub' ),
				customControl = wp.components.TextControl,
				onCustomValueChange = ( x ) => { return x; },
				customPlaceholder = '',
				style = {},
				...props
			} = this.props;

			// check if the current value is included in the options
			let isOptionSelected = _.isEmpty( value );
			options.forEach( option => {
				if ( option.value === value ) {
					isOptionSelected = true;
					// break loop
					return false;
				}
			} );

			return el( 'div', { style: style }, [
				el( wp.components.BaseControl, {
					label: label,
				}, [
					el( wp.components.SelectControl, {
						value: value,
						onChange: val => onChange( val ),
						options: [
							...options,
							{
								label: customLabel,
								value: _.isEmpty( value ) || isOptionSelected ? 'custom' : value
							}
						],
						...props
					} ),
					isOptionSelected ? null : el( customControl, {
						value: value === 'custom' ? '' : value,
						onChange: val => onChange( onCustomValueChange( val ) ),
						placeholder: customPlaceholder,
					} ),
				] )
			] );
		}
	}

	/**
	 * A switchable control that lets users choose between different spacing
	 * controls, like default, fluid, clamp, min, max or custom.
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Label text.
	 * @property {array}  modes Array of modes to be supported. Possible values are:
	 *                          'default', 'numeric', 'fluid', 'clamp', 'min', 'max', 'custom'
	 * @property {callback} onChange Callback function to be called when value is changed.
	 */
	this.SwitchableSpacingControl = class extends wp.element.Component {
		constructor() {
			super();

			this.state = {
				mode: false,
				isDropdownOpen: false
			}

			this.config = {
				allModes:{
					default: {
						name: 'default',
						label: __( 'Default', 'greyd_hub' ),
						control: greyd.components.SpacingPresetControl,
					},
					numeric: {
						name: 'numeric',
						label: __( 'Default', 'greyd_hub' ),
						control: greyd.components.__RangeUnitControl,
					},
					fluid: {
						name: 'fluid',
						label: __( 'Fluid', 'greyd_hub' ),
						control: greyd.components.SpacingFluidControl,
						help: __( 'The value is automatically calculated to be within Min and Max.', 'greyd_hub' ),
					},
					clamp: {
						name: 'clamp',
						label: __( 'Clamp', 'greyd_hub' ),
						control: greyd.components.SpacingClampControl,
						help: __( "The prefered value is used, but always stays between Min and Max. Try making values dynamic, like 10vw or 20%.", 'greyd_hub' ),
						helpLink: 'https://developer.mozilla.org/en-US/docs/Web/CSS/clamp'
					},
					min: {
						name: 'min',
						label: __( 'Min of 2 values', 'greyd_hub' ),
						control: greyd.components.SpacingMinControl,
						help: __( "The smaller value is used. Try making one value dynamic, like 10vw.", 'greyd_hub' ),
						helpLink: 'https://developer.mozilla.org/en-US/docs/Web/CSS/min'
					},
					max: {
						name: 'max',
						label: __( 'Max of 2 values', 'greyd_hub' ),
						control: greyd.components.SpacingMaxControl,
						help: __( "The bigger value is used. Try making one value dynamic, like 10vw.", 'greyd_hub' ),
						helpLink: 'https://developer.mozilla.org/en-US/docs/Web/CSS/max'
					},
					custom: {
						name: 'custom',
						label: __( 'Custom', 'greyd_hub' ),
						control: wp.components.TextareaControl,
						help: __( 'You could use CSS functions like calc() or custom CSS variables.', 'greyd_hub' ),
						controlProps: {
							className: 'code-input-control',
							placeholder: __( 'var(--my-custom-value)', 'greyd_hub' ),
							rows: 1
						}
					}
				}
			};
		}

		render() {
			const {
				// main props
				value,
				onChange,
				label,
				modes = [ 'default', 'fluid', 'clamp', 'min', 'max', 'custom' ],
				// control props (optional) -> assigned to the control element.
				min,
				max,
				units,
				// inherit props
				...props
			} = this.props;

			const {
				mode: currentMode,
				isDropdownOpen
			} = this.state;

			const {
				allModes
			} = this.config;

			// remove all invalid modes
			const supportedModes = modes.filter( mode => allModes[mode] );

			// function to toggle the dropdown
			const toggleDropdown = () => this.setState({ isDropdownOpen: !isDropdownOpen });

			/**
			 * Get the current mode based an the value & configuration.
			 * @returns {string} mode
			 */
			const getMode = () => {

				const isModeSupported = ( mode ) => {
					return supportedModes.indexOf( mode ) > -1;
				}

				/**
				 * Get the new mode based on the current value.
				 * @param {string} newMode Selected mode.
				 * @param  {...any} fallbacks Define multiple fallbacks to be applied, if 
				 *                            the selected mode is not supported.
				 * @returns {string} The new mode. An empty string indicates an error.
				 */
				const getNewMode = ( newMode, ...fallbacks ) => {

					if ( newMode && isModeSupported( newMode ) ) {
						return newMode;
					}

					if ( _.isEmpty( fallbacks ) ) {
						fallbacks = [];
					} else if ( !Array.isArray( fallbacks ) ) {
						fallbacks = [ fallbacks ];
					}
					fallbacks.push( 'default' );
					fallbacks.push( 'numeric' );
					fallbacks.push( 'custom' );

					let validFallback = fallbacks.find( mode => isModeSupported( mode ) );

					if ( validFallback ) {
						return validFallback
					}

					if ( supportedModes && supportedModes.length === 0 ) {
						return supportedModes[0];
					}
					// error -> no modes supported
					return '';
				}

				if ( !value || typeof value !== 'string' ) {
					return getNewMode();
				}

				if ( value.match( /^clamp\([\s\d\.]+[^0-9,]+,[\s\d\.]+[^0-9,]+,[\s\d\.]+[^0-9,]+\)$/ ) ) {
					return getNewMode( 'clamp', 'fluid', 'min', 'max' );
				}
				else if ( value.match( /^clamp\([\s\d\.]+[^0-9,]+,\s+[^,]+,[\s\d\.]+[^0-9,]+\)$/ ) ) {
					return getNewMode( 'fluid', 'clamp', 'min', 'max' );
				}
				else if ( value.match( /^min\([\s\d\.]+[^0-9,]+,[\s\d\.]+[^0-9,]+\)$/ ) ) {
					return getNewMode( 'min', 'max', 'fluid', 'clamp' );
				}
				else if ( value.match( /^max\([\s\d\.]+[^0-9,]+,[\s\d\.]+[^0-9,]+\)$/ ) ) {
					return getNewMode( 'max', 'min', 'fluid', 'clamp' );
				}
				// // numeric with or without unit
				// else if ( value.match( /^[\s\d\.]+[^0-9,]*$/ ) ) {
				// 	return getNewMode( 'default' );
				// }
				// numeric with - or + as first character
				else if ( value.match( /^[-]{0,1}[\s\d\.]+[^0-9,]*$/ ) ) {
					return getNewMode( 'default' );
				}
				// css variable
				else if ( value.match( /^var/ ) ) {
					return getNewMode( 'default' );
				}
				else {
					return getNewMode( 'custom' );
				}
			}

			// init the mode
			if ( currentMode === false ) {
				this.setState({ mode: getMode() });
			}
			// error
			else if ( currentMode === '' ) {
				return el( 'p', {}, __( "There seems to be an error in the configuration of the 'SwitchableSpacingControl': No valid mode found.", 'greyd_hub' ) );
			}

			return el( wp.components.BaseControl, {
				className: 'greyd-switchable-spacing-control',
				...props
			}, [
				// label
				( _.isEmpty(label) && modes.length < 2 ) ? null : el( wp.components.Flex, {}, [

					// text Label
					el( wp.components.FlexBlock, {}, [
						el( greyd.components.AdvancedLabel, {
							label: label,
							// reset can be accessed in the DropdownMenu
							// initialValue: '',
							// currentValue: value,
							// onClear: () => onChange( null )
						} ),
					] ),

					// more dropdown
					el( wp.components.FlexItem, {}, [
						el( wp.components.DropdownMenu, {
							open: isDropdownOpen,
							onToggle: toggleDropdown,
							icon: greyd.tools.getCoreIcon( 'moreVertical' ),
							label: __( 'More', 'greyd_hub' ),
							toggleProps: {
								style: {
									height: 24,
									width: 24,
									minWidth: 24,
									padding: 0,
								}
							}
						}, () => {
							return el( wp.element.Fragment, {}, [
								el( wp.components.MenuGroup, {}, [
									...supportedModes.map( name => {

										const mode = allModes[ name ];

										return el( wp.components.MenuItem, {
											icon: mode.name === currentMode ? greyd.tools.getCoreIcon( 'check' ) : null,
											isSelected: mode.name === 'default',
											onClick: () => this.setState({
												mode: mode.name,
												isDropdownOpen: false
											})
										}, __( mode.label, 'greyd_hub' ) )
									} )
								] ),
								el( wp.components.MenuGroup, {}, [
									el( wp.components.MenuItem, {
										icon: greyd.tools.getCoreIcon( 'undo' ),
										onClick: () => {
											onChange( null )
											this.setState({
												mode: supportedModes[0],
												isDropdownOpen: false
											})
											// this.forceUpdate()
										}
									}, __( 'Reset', 'greyd_hub' ) ),
								] )
							] )
						} )
					] ),
				] ),

				// controls per mode
				...modes.map( mode => {
					if ( allModes[ mode ] && mode === currentMode ) {
						return el( wp.element.Fragment, {}, [

							// control
							el( allModes[ mode ].control, {
								value: value,
								onChange: val => onChange( val ),
								...allModes[ mode ]?.controlProps ?? {},

								// only assign additional props if neither null nor undefined
								...( min === null || min === undefined ? {} : { min: min } ),
								...( max === null || max === undefined ? {} : { max: max } ),
								...( units === null || units === undefined ? {} : { units: units } ),
							} ),

							// help
							allModes[ mode ]?.help && el( 'p', {
								className: 'components-base-control__help',
								style: {
									fontSize: 12,
									fontStyle: 'normal',
									color: 'rgb(117, 117, 117)',
									marginTop: 0,
								},
							}, [
								el( 'span', {}, allModes[ mode ].help ),
								allModes[ mode ]?.helpLink && el( 'br', {} ),
								allModes[ mode ]?.helpLink && el( 'a', {
									href: allModes[ mode ].helpLink,
									target: '_blank',
									style: {
										fontSize: 12,
										fontStyle: 'normal',
										color: 'rgb(117, 117, 117)',
									},
								}, __( 'Learn more', 'greyd_hub' ) ),
							] )
						] )
					}
				})
			] )
		}
	}

	/**
	 * A single SpacingSizesControl that takes a single string value.
	 * 
	 * @property {string} value Current value. Presets are saved like 'var:preset|spacing|small'
	 * @property {string} label Label text.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {Component} SpacingPresetControl
	 */
	this.SpacingPresetControl = class extends wp.element.Component {
		constructor() {
			super();
		}

		render() {
			const {
				value,
				onChange,
				label,
				min: minimumCustomValue = 0,
				...props
			} = this.props;

			let computedValue;
			/**
			 * Using '0' as a fallback ensures that the preview control is
			 * displayed as the default preset range slider.
			 */
			if ( typeof value === 'undefined' || value === null || value === false ) {
				computedValue = '0';
			} else {
				computedValue = greyd.tools.getComputedSpacingValue( value );
			}

			return el( wp.components.BaseControl, {}, [
				el( wp.blockEditor.__experimentalSpacingSizesControl, {
					label: '',
					showSideInLabel: false,
					minimumCustomValue: minimumCustomValue,
					sides: ['all'],
					values: {
						all: computedValue
					},
					onChange: val => onChange( val.all ),
					...props
				} )
			] )
		}
	}

	/**
	 * A Control to define a css value with the clamp function.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {string} label Label text.
	 * @property {string} min Default min value, defaults to '1rem'.
	 * @property {string} preferred Default preferred value, defaults to '3vw'.
	 * @property {string} max Default max value, defaults to '2rem'.
	 * 
	 * @returns {Component} SpacingClampControl
	 */
	this.SpacingClampControl = class extends wp.element.Component {
		constructor() {
			super();
		}

		render() {
			const {
				value,
				onChange,
				label,
				min = '1rem',
				preferred = '3vw',
				max = '2rem',
				...props
			} = this.props;

			let minValue = min;
			let preferredValue = preferred;
			let maxValue = max;

			// match all single values with unit
			const matches = value && !_.isEmpty(value) ? value.match( /([\d\.]+[^0-9,)\s]+)/g ) : null;
			// console.log( 'clamp', value, matches );

			if ( matches ) {
				minValue = matches[0];
				maxValue = matches[matches.length-1];
				if ( matches.length > 2 ) {
					preferredValue = matches[matches.length-2];
				}
			}

			const getStep = ( val ) => {
				if ( typeof val === 'string' && val.indexOf( 'em' ) > 0 ) return 0.05;
				if ( typeof val === 'string' && val.indexOf( 'v' ) > 0 ) return 0.1;
				return 1;
			};

			return el( wp.components.BaseControl, {
				label: label,
				className: 'greyd-spacing-function-control'
			}, [
				el( wp.components.Flex, {
					gap: 0,
					align: 'center',
					justify: 'space-between',
					className: 'greyd-spacing-function-control__flex-wrapper'
				}, [
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: minValue,
							min: getStep( minValue ),
							step: getStep( minValue ),
							onChange: ( newValue ) => onChange( 'clamp(' + newValue + ', ' + preferredValue + ', ' + maxValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Min", 'greyd_hub' ) )
						] )
					] ),
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: preferredValue,
							min: getStep( preferredValue ),
							step: getStep( preferredValue ),
							onChange: ( newValue ) => onChange( 'clamp(' + minValue + ', ' + newValue + ', ' + maxValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Preferred", 'greyd_hub' ) )
						] )
					] ),
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: maxValue,
							min: getStep( maxValue ),
							step: getStep( maxValue ),
							onChange: ( newValue ) => onChange( 'clamp(' + minValue + ', ' + preferredValue + ', ' + newValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Max", 'greyd_hub' ) )
						] )
					] )
				] )
			] );
		}
	}

	/**
	 * A Control to define a css value with the clamp function, but with
	 * the preferred value automatically calculated.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {string} label Label text.
	 * @property {string} min Default min value, defaults to '1rem'.
	 * @property {string} max Default max value, defaults to '10vw'.
	 * 
	 * @returns {Component} SpacingFluidControl
	 */
	this.SpacingFluidControl = class extends wp.element.Component {
		constructor() {
			super();
		}

		render() {
			const {
				value,
				onChange,
				label,
				min = '1rem',
				max = '10vw',
				...props
			} = this.props;

			let minValue = min;
			let maxValue = max;

			// match all single values with unit
			const matches = value && !_.isEmpty(value) ? value.match( /([\d\.]+[^0-9,)\s]+)/g ) : null;

			if ( matches ) {
				minValue = matches[0];
				maxValue = matches[matches.length-1];
			}

			const getStep = ( val ) => {
				if ( typeof val === 'string' && val.indexOf( 'em' ) > 0 ) return 0.05;
				if ( typeof val === 'string' && val.indexOf( 'v' ) > 0 ) return 0.1;
				return 1;
			};

			return el( wp.components.BaseControl, {
				label: label,
				className: 'greyd-spacing-function-control'
			}, [
				el( wp.components.Flex, {
					gap: 0,
					align: 'center',
					justify: 'space-between',
					className: 'greyd-spacing-function-control__flex-wrapper'
				}, [
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: minValue,
							min: getStep( minValue ),
							step: getStep( minValue ),
							onChange: ( newValue ) => this.props.onChange( 'clamp(' + newValue + ', calc((' + newValue + ' + ' + maxValue + ') / 2 ), ' + maxValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Min", 'greyd_hub' ) )
						] )
					] ),
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: maxValue,
							min: getStep( maxValue ),
							step: getStep( maxValue ),
							onChange: ( newValue ) => this.props.onChange( 'clamp(' + minValue + ', calc((' + minValue + ' + ' + newValue + ') / 2 ), ' + newValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Max", 'greyd_hub' ) )
						] )
					] )
				] )
			] );
		}
	}

	/**
	 * A Control to define a css value with the min function.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {string} label Label text.
	 * @property {string} min Default min value, defaults to '1rem'.
	 * @property {string} max Default max value, defaults to '4vw'.
	 * 
	 * @returns {Component} SpacingMinControl
	 */
	this.SpacingMinControl = class extends wp.element.Component {
		constructor() {
			super();
		}

		render() {
			const {
				value,
				onChange,
				label,
				min = '1rem',
				max = '4vw',
				...props
			} = this.props;

			let minValue = min;
			let maxValue = max;

			// match all single values with unit
			const matches = value && !_.isEmpty(value) ? value.match( /([\d\.]+[^0-9,)\s]+)/g ) : null;

			if ( matches ) {
				minValue = matches[0];
				maxValue = matches[matches.length-1];
			}

			const getStep = ( val ) => {
				if ( typeof val === 'string' && val.indexOf( 'em' ) > 0 ) return 0.05;
				if ( typeof val === 'string' && val.indexOf( 'v' ) > 0 ) return 0.1;
				return 1;
			};

			return el( wp.components.BaseControl, {
				label: label,
				className: 'greyd-spacing-function-control'
			}, [
				el( wp.components.Flex, {
					gap: 0,
					align: 'center',
					justify: 'space-between',
					className: 'greyd-spacing-function-control__flex-wrapper'
				}, [
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: minValue,
							min: getStep( minValue ),
							step: getStep( minValue ),
							onChange: ( newValue ) => this.props.onChange( 'min(' + newValue + ', ' + maxValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Min value 1", 'greyd_hub' ) )
						] )
					] ),
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: maxValue,
							min: getStep( maxValue ),
							step: getStep( maxValue ),
							onChange: ( newValue ) => this.props.onChange( 'min(' + minValue + ', ' + newValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Min value 2", 'greyd_hub' ) )
						] )
					] )
				] )
			] );
		}
	}

	/**
	 * A Control to define a css value with the max function.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {string} label Label text.
	 * @property {string} min Default min value, defaults to '10rem'.
	 * @property {string} max Default max value, defaults to '4vw'.
	 * 
	 * @returns {Component} SpacingMaxControl
	 */
	this.SpacingMaxControl = class extends wp.element.Component {
		constructor() {
			super();
		}

		render() {
			const {
				value,
				onChange,
				label,
				min = '10rem',
				max = '4vw',
				...props
			} = this.props;

			let minValue = min;
			let maxValue = max;

			// match all single values with unit
			const matches = value && !_.isEmpty(value) ? value.match( /([\d\.]+[^0-9,)\s]+)/g ) : null;

			if ( matches ) {
				minValue = matches[0];
				maxValue = matches[matches.length-1];
			}

			const getStep = ( val ) => {
				if ( typeof val === 'string' && val.indexOf( 'em' ) > 0 ) return 0.05;
				if ( typeof val === 'string' && val.indexOf( 'v' ) > 0 ) return 0.1;
				return 1;
			};

			return el( wp.components.BaseControl, {
				label: label,
				className: 'greyd-spacing-function-control'
			}, [
				el( wp.components.Flex, {
					gap: 0,
					align: 'center',
					justify: 'space-between',
					className: 'greyd-spacing-function-control__flex-wrapper'
				}, [
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: minValue,
							min: getStep( minValue ),
							step: getStep( minValue ),
							onChange: ( newValue ) => this.props.onChange( 'max(' + newValue + ', ' + maxValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Max value 1", 'greyd_hub' ) )
						] )
					] ),
					el( wp.components.FlexBlock, {}, [
						el( wp.components.__experimentalUnitControl, {
							value: maxValue,
							min: getStep( maxValue ),
							step: getStep( maxValue ),
							onChange: ( newValue ) => this.props.onChange( 'max(' + minValue + ', ' + newValue + ')' ),
						} ),
						el( 'p', {
							style: {
								textAlign: 'center', marginBottom: 0
							},
						}, [
							el( 'small', {}, __( "Max value 2", 'greyd_hub' ) )
						] )
					] )
				] )
			] );
		}
	}

	/**
	 * DateTimePicker component wrapped in popover with button, preview and reset.
	 * 
	 * @property {string} icon			Button icon (default: 'calendar-alt').
	 * @property {string} label			Label text.
	 * @property {string} help			Help text.
	 * @property {string} empty			Empty value text (default: 'no date set').
	 * @property {string} value			Current value.
	 * @property {callback} onChange	Callback function to be called when value is changed.
	 * 
	 * @returns {Component} DatePickerPopupControl
	 */
	this.DatePickerPopupControl = class extends wp.element.Component {
		constructor() {
			super();
			this.state = {
				id: 'datepicker_anchor_'+greyd.tools.generateRandomID(),
				isOpen: false,
			};
		}

		render() {

			// values
			var icon = this.props.icon ?? 'calendar-alt';
			var label = this.props.label ?? '';
			var help = this.props.help ?? '';
			var empty = this.props.empty ?? __( "No date set", 'greyd_hub' );
			var value = this.props.value ?? '';

			var isOpen = this.state.isOpen ?? false;
			var hasValue = value != '';

			const makeButton = () => {
				return el( wp.components.BaseControl, {
					label: label,
					help: help,
				}, [
					el( 'div', {
						id: this.state.id,
						style: {
							display: 'flex',
							alignItems: 'center',
							gap: '12px',
							width: '110%'
						}
					}, [
						// icon button
						el( wp.components.Button, {
							icon: icon,
							title: sprintf( __( "Select %s", 'greyd_hub' ), label ),
							isPressed: isOpen,
							onClick: () => this.setState( { isOpen: true } )
						} ),
						// preview
						hasValue ?
							el( 'strong', {}, new Date(value).toUTCString() ) :
							el( 'i', {}, empty ),
						// reset
						hasValue && el( wp.components.Button, {
							icon: 'dismiss',
							isDestructive: true,
							title: sprintf( __( "Clear %s", 'greyd_hub' ), label ),
							onClick: () => this.props.onChange( '' )
						} ),
					] )
				] );
			};

			const makePopover = () => {
				return isOpen && el( wp.components.Popover, {
					anchor: document.getElementById(this.state.id),
					focusOnMount: true,
					placement: 'left',
					noArrow: false,
					offset: 12,
					onFocusOutside: ( e ) => this.setState( { isOpen: false } )
				}, [
					el( 'div', { style: { margin: '16px'} }, [
						el( wp.components.BaseControl, {
							label: label,
							help: help,
						}, [
							// date time picker
							el( wp.components.DateTimePicker, {
								currentDate: hasValue ? value : new Date().toISOString(),
								onChange: ( newDate ) => this.props.onChange( newDate ),
							} )
						] )
					] )
				] )
			};

			// render button and popover
			return [
				makeButton(),
				makePopover()
			];
		}

	}

	/**
     * HTMLTagSelectControl component
     * 
     * @property {string} childTagLabel 	Label text for the child tag dropdown.
	 * @property {string} parentTagLabel 	Label text for the parent tag dropdown.
	 * @property {array} parentTags 		Array of parent tags to be supported.
	 * @property {array} childTags 			Array of child tags to be supported.
     * @property {string} value				Currently selected value.
     * 
     * @returns {Component} HTMLTagSelectControl
     */
	this.HTMLTagSelectControl = class extends wp.element.Component {

		constructor() {
			super();
			this.state = {
				selectedParentTag: 'div',
				selectedChildTag: 'article',
				filteredChildTags: []
			};
		}

		handleParentChange = (newValue) => {
			// if childTags exist, keep the previous logic
			if (this.props.childTags) {
				const { childTags } = this.props;
				let filteredChildTags = childTags;

				if (newValue.includes('ul')) {
					filteredChildTags = childTags.filter(tag => tag.value === 'li');
					this.handleChildChange('li');
				} else {
					filteredChildTags = childTags.filter(tag => tag.value !== 'li');
					this.handleChildChange('article');
				}

				this.setState({
					selectedParentTag: newValue,
					filteredChildTags: filteredChildTags,
				}, () => {
					this.props.onChange({
						parent: this.state.selectedParentTag,
						child: this.state.selectedChildTag
					});
				});
			} else {
				// if no childTags exist, return only the value
				this.setState({ selectedParentTag: newValue }, () => {
					this.props.onChange(newValue);
				});
			}
		}

		handleChildChange = (newValue) => {
			this.setState( {
				selectedChildTag: newValue
			}, () => {
				this.props.onChange({
					parent: this.state.selectedParentTag,
					child: this.state.selectedChildTag
				});
			} );
		}

		render() {
			const {
				value,
				onChange,
				childTagLabel = __('Child Tag', 'greyd_hub'),
				parentTagLabel = __('Parent Tag', 'greyd_hub'),
				parentTags,
				childTags,
				help,
				...props
			} = this.props;

			const { filteredChildTags } = this.state;

			const makeParentSelect = () => {
				return el( wp.components.SelectControl, {
					label: parentTagLabel,
					value: childTags ? (value?.parent || '') : (value || ''),
					options: parentTags,
					onChange: this.handleParentChange,
					help: help,
					...props
				});
			}

			const makeChildSelect = () => {
				if (!childTags) return null;
				
				const childTagsFiltered = filteredChildTags.length > 0 ? filteredChildTags : childTags;
				return el( wp.components.SelectControl, {
					label: childTagLabel,
					value: value?.child || '',
					options: childTagsFiltered,
					onChange: this.handleChildChange,
					...props
				});
			}

			return [
				makeParentSelect(),
				makeChildSelect()
			];
		}
	}
	
	/**
	 * Control for defining a custom aspect ratio.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * @property {string} label Label text.
	 */
	this.CustomAspectRatioControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		validateInput(input) {
			return input.replace(/[^0-9]/g, '');
		}

		render() {
			const {
				value = '1/1',
				onChange = () => {},
				label = __('Custom Aspect Ratio', 'greyd_hub')
			} = this.props;

			const getFirstValue = (value) => {
				return value.includes('/') ? value.split('/')[0] : value || '1';
			};

			const getSecondValue = (value) => {
				return value.includes('/') ? value.split('/')[1] : value || '1';
			};

			return [
				el( wp.components.BaseControl, {}, [
					el( greyd.components.AdvancedLabel, {
						label: label,
					} ),
					el( wp.components.Flex, {
						align: 'center',
						justify: 'space-between',
						style: { gap: '10px' }
					}, [
						el( wp.components.FlexBlock, {}, [
							el( wp.components.__experimentalNumberControl, {
								value: getFirstValue(value) || '',
								onChange: ( newValue ) => {
									const newAspectRatio = 
									`${this.validateInput(newValue)}/${getSecondValue(value)}`;
									onChange(newAspectRatio);
								},
								min: 1 // Prevent neg numbers
							})
						]),
						el( 'span', { style: { textAlign: 'center' } }, ':'),
						el( wp.components.FlexBlock, {}, [
							el( wp.components.__experimentalNumberControl, {
								value: getSecondValue(value) || '',
								onChange: ( newValue ) => {
									const newAspectRatio = 
									`${getFirstValue(value)}/${this.validateInput(newValue)}`;
									onChange(newAspectRatio);
								},
								min: 1 // Prevent neg numbers
							})
						])
					])
				] )
			];
		}
	}


	/**
	 * =================================================================
	 *                          Deprecated
	 * some of these controls are still in use within other components
	 * =================================================================
	 */

	/**
	 * DEPRECATED use ColorGradientPopupControl instead
	 * Small ColorPicker that toggles a popup
	 * 
	 * Still in use by a lot of blocks.
	 * 
	 * @deprecated use ColorGradientPopupControl instead
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {string} Color value as css-variable if possible (eg. "var(--wp--preset--color--foreground)" or "#5D5D5D").
	 *                   Empty String "" on default or if cleared.
	 */
	this.ColorPopupControl = class extends wp.element.Component {

		constructor() {
			super();

			// bind keyword "this" inside functions to the class object
			this.handleChange	= this.handleChange.bind(this);
			this.clear 			= this.clear.bind(this);

			this.state = {
				...this.getDefault()
			};
		}

		getDefault() {
			return {
				isOpen: false,
				value: "",
			}
		}

		componentWillMount() {
			this.setState({ value: this.props.value });
		}
	
		componentWillReceiveProps({ value }) {
			this.setState({ ...this.state, value });
		}

		/**
		 * Handle all change events
		 * @param {mixed} input
		 */
		handleChange(input) {
			input = _.isEmpty(input) ? "" : greyd.tools.convertColorToVar(input);
			this.setState(
				{ value: input },
				() => { this.props.onChange(input); }
			);
		}

		/**
		 * Reset the value
		 */
		clear() {
			this.setState(
				this.getDefault(),
				() => { this.props.onChange(this.getDefault().value); }
			);
		}

		render() {
			const { value, isOpen } = this.state;
			const hasLabel = !!this.props.label;
			const isHidden = !!this.props.className && this.props.className == 'hidden';

			return el( "div", {
				className: "color_popup_control"+(hasLabel ? " flex" : "" ),
				style: (isHidden ? { display: 'none' } : {} )
			}, [
				( hasLabel ?
					el( greyd.components.AdvancedLabel, {
						label: this.props.label,
						initialValue: this.getDefault().value,
						currentValue: this.state.value,
						onClear: this.clear
					} ) :
					""
				),
				el( "div", { className: "components-circular-option-picker__option-wrapper" }, [
					el( "button", {
						onClick: () => this.setState({ isOpen: !isOpen }),
						type: "button",
						"aria-pressed": "false",
						"aria-label": __("Select color", "greyd_hub"),
						className: "components-button components-circular-option-picker__option",
						style: {
							backgroundColor: _.isEmpty(value) ? "rgb(180,180,180)" : value,
							color: _.isEmpty(value) ? "rgb(180,180,180)" : value,
						}
					} ),
					isOpen ? (
						el( wp.components.Popover, {
							onClick: (e) => e.stopPropagation(),
							className: "color_popup_control__popover",
							onFocusOutside: () => this.setState({ isOpen: false }),
						}, [
							el( "div", { className: "color_popup_control__popover_content" }, [
								el( wp.blockEditor.ColorPalette, {
									value: greyd.tools.convertVarToColor(value),
									onChange: (newValue) => this.handleChange(newValue),
								} )
							] )
						] )
					) : ""
				] ),
			] );
		}
	}

	/**
	 * Deprecated Version of RenderSavedStyles.
	 * 
	 * Probably still in use within block deprecations.
	 */
	this.RenderSavedStylesDeprecated = class extends wp.element.Component {
		render() {
			const {
				styles,
				selector,
				important = false
			} = this.props;
			const finalCSS = greyd.tools.deprecatedComposeCSS( styles, selector, false, important );
		
			if ( finalCSS == "" ) return "";

			return el( "style", {
				className: "greyd-styles",
				dangerouslySetInnerHTML: { __html: finalCSS }
			} );
		}
	}

	/**
	 * Earlier Version now only in use within the SwitchableSpacingControl.
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {array} units Units to be supported.
	 * @property {int} min Minimum value.
	 * @property {int|object} max Maximum value. If integer, only the 'px'-max is set.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {string} Value including unit (eg. "12px").
	 *                   Empty String "" on default or if cleared.
	 */
	this.__RangeUnitControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		/**
		 * Render the control
		 */
		render() {
			
			const {
				value,
				label = "",
				className = "",
				onChange = () => {},
				// configs
				units = ["px", "%", "em", "rem", "vw", "vh"], // Units available in the control.
				min = 0, // Minimum value, can be negative.
				step = {
					px: 1,
					em: 0.05,
					rem: 0.05,
					vw: 1,
					vh: 1,
					'%': 1,
				},
			} = this.props;

			const unit = greyd.tools.getUnitValue( value, units[0] );

			let maxValues = {
				'': 100,
				'px': 200,
				'%': 100,
				'em': 100,
				'rem': 100,
				'vw': 100,
				'vh': 100,
			};

			if ( typeof this.props.max === 'object' ) {
				maxValues = {
					...maxValues,
					...this.props.max
				};
			} else {
				maxValues.px = this.props.max;
			}

			const max = maxValues[ unit ];

			const stepValue = _.has( step , unit ) ? step[unit] : 1;

			return el( wp.components.BaseControl, {
				className: [ "range_unit_control", className ].join(' ')
			}, [
				label && el( greyd.components.AdvancedLabel, {
					label: label,
					initialValue: null,
					currentValue: value,
					onClear: () => onChange( null )
				} ),
				el( wp.components.Flex, {}, [
					el( wp.components.FlexItem, {
						style: { flex: '1' }
					}, [
						el( wp.components.__experimentalUnitControl, {
							value: value,
							min: min,
							step: stepValue,
							units: units.map( u => {
								return {
									label: u,
									value: u,
									default: 0,
								};
							} ),
							onChange: ( newValue ) => onChange( newValue ),
							hideLabelFromVision: true,
							className: "spacing-sizes-control__custom-value-input",
							size: '__unstable-large',
						} )
					] ),

					el( wp.components.FlexBlock, {
						style: { flex: '1' }
					}, [
						el( wp.components.RangeControl, {
							withInputField: false,
							value: !_.isEmpty( value ) ? parseInt( value ) : value,
							// min: min,
							max: max,
							step: _.has(this.props, 'step') ? stepValue : (unit === 'em' ? 0.05 : 1),
							onChange: newValue => onChange( newValue + unit ),
							className: "spacing-sizes-control__custom-value-range",
						} )
					] )
				] )
			] );
		}
	}

	/**
	 * Deprecated Dimension Control.
	 * Still in use within the new DimensionControl because border-radius sides
	 * like 'topLeft' are not supported but still in use.
	 * 
	 * @property {object|string} value Current value.
	 * @property {string} label Optional label to be displayed.
	 * @property {array} units Units to be supported.
	 * @property {int} min Minimum value.
	 * @property {int|object} max Maximum value. If integer, only the 'px'-max is set.
	 * @property {array} sides Sides to be supported.
	 * @property {string} type Type of the value ('object'|'string').
	 * @property {object} labels Custom labels for the sides.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} String values for each side (eg. {top: "1px", right: "12px", ... }).
	 *                   Empty object {} on default or if cleared.
	 */
	this.__DimensionControl = class extends wp.element.Component {

		constructor() {
			super();

			// bind keyword "this" inside functions to the class object
			this.setConfig 			= this.setConfig.bind(this);
			this.getMode 			= this.getMode.bind(this);
			this.getUnit 			= this.getUnit.bind(this);
			this.handleValueChange	= this.handleValueChange.bind(this);
			this.handleUnitChange	= this.handleUnitChange.bind(this);
			this.clear 				= this.clear.bind(this);
			this.switchMode 		= this.switchMode.bind(this);

			this.config = {
				units: ["px", "%", "em", "rem", "vw", "vh"], // Units available in the control.
				min: 0, // Minimum value, can be negative.
				max: {
					'': 100,
					'px': 200,
					'%': 100,
					'em': 100,
					'rem': 100,
					'vw': 100,
					'vh': 100,
				},
				sides: [ "top", "right", "bottom", "left" ],
				type: "object", // the type of value to return
				labels: {
					"top": 			__("Top", "greyd_hub"),
					"right": 		__("Right", "greyd_hub"),
					"bottom": 		__("Bottom", "greyd_hub"),
					"left": 		__("Left", "greyd_hub"),
					"topLeft": 		__("Top left", "greyd_hub"),
					"topRight": 	__("Top right", "greyd_hub"),
					"bottomRight": 	__("Bottom right", "greyd_hub"),
					"bottomLeft": 	__("Bottom left", "greyd_hub"),
					"all": 			__("All sides", "greyd_hub")
				},
			};

			this.state = {
				mode: "simple",
				unit: "px"
			};
		}

		/**
		 * Called after the control is build and props are set.
		 * @source https://reactjs.org/docs/react-component.html#componentdidmount
		 */
		componentDidMount() {

			// set the config
			this.setConfig();

			// init the state
			if ( typeof this.props.value !== "undefined" ) {
				
				const state = {
					mode: this.getMode( this.props.value ),
					unit: this.getUnit( this.props.value ),
				}

				if ( state !== this.state ) {
					this.setState( state );
				}
			}
		}

		/**
		 * Get the current mode
		 * @param {string|object} value this.props.value
		 * @returns {string} 'simple'|'advanced'
		 */
		getMode( value ) {

			value = this.getValueAsObject( value );

			if ( typeof value === 'object' && !_.isEmpty(value) ) {

				let lastVal = null;
	
				for (const [side, val] of Object.entries(value)) {
					if (!lastVal) {
						lastVal = val;
					}
					else if ( !_.isEqual(lastVal, val) ) {
						return 'advanced';
					}
				}
			}
			return 'simple';
		}

		/**
		 * Get the current unit
		 * @param {string|object} value this.props.value
		 * @returns {string}
		 */
		getUnit( value ) {

			value = this.getValueAsObject( value );

			if ( typeof value === 'object' && !_.isEmpty(value) ) {

				for (const [side, val] of Object.entries(value)) {
	
					// set unit
					const unit = greyd.tools.getUnitValue(val);
					if ( !_.isEmpty(unit) ) {
						return unit;
					}
				}
			}
			else if ( typeof value === 'string' ) {
				const unit = greyd.tools.getUnitValue(value);
				if ( !_.isEmpty(unit) ) {
					return unit;
				}
			}
			return 'px';
		}

		/**
		 * Set the class configuration
		 */
		setConfig() {
			const { config, props } = this;

			// get any user config...
			const userConfig = _.pick(props, _.keys(config));

			// avoid errors...
			const finalConfig = _.clone(config);

			// merge labels recursive
			userConfig.labels = { ...finalConfig.labels, ...userConfig.labels };

			// merge
			_.assign(finalConfig, userConfig);

			// set max values
			if ( typeof finalConfig.max !== 'object' ) {
				finalConfig.max = config.max;
				finalConfig.max.px = userConfig.max;
			}

			finalConfig.units = this.modUnitsArray(finalConfig.units);

			this.config = finalConfig;
		}

		/**
		 * Modify array of units to be used for UnitControl
		 * @param {array} units
		 * @returns {array}
		 */
		modUnitsArray(units) {
			const finalUnits = [];
		
			units.forEach((unit) => {
				finalUnits.push({
					label: unit,
					value: unit,
					default: 0,
				});
			});
		
			return finalUnits;
		}

		/**
		 * Convert the value into a javascript object
		 * @param {mixed} value
		 * @returns 
		 */
		getValueAsObject( value ) {
			if (typeof value === "object" && !_.isEmpty(value) ) {
				return value;
			}

			// build default value
			let { sides } = this.config;
			let newValue = {};
			for (var i = 0; i < sides.length; i++) {
				newValue[ sides[i] ] = null;
			}
			
			if ( typeof value === "object" ) {
				newValue = {
					...newValue,
					value
				}
			}
			else if (typeof value === "string" ) {

				// shorthand property, eg. '4px 10px 3px'
				if ( !_.isEmpty(value.match(/[a-z\d]+\s+[a-z\d]?/g)) ) {
					let values = value.split(/\s+/);
					for (var i = 0; i < sides.length; i++) {
						let val = null;
						switch ( sides[i] ) {
							case 'top':
							case 'topLeft':
								val = values[0];
								break;
							case 'right':
							case 'topRight':
								val = _.has(values, 1) ? values[1] : values[0];
								break;
							case 'bottom':
							case 'bottomRight':
								val = _.has(values, 2) ? values[2] : values[0];
								break;
							case 'left':
							case 'bottomLeft':
								val = _.has(values, 3) ? values[3] : _.has(values, 1) ? values[1] : values[0];
								break;
								
						}
						newValue[ sides[i] ] = val;
					}
				}
				// simple string
				else if ( !_.isEmpty(value) ) {
					for (var i = 0; i < sides.length; i++) {
						newValue[ sides[i] ] = value;
					}
				}
			}

			return newValue;
		}

		/**
		 * Handle change of a numeric value
		 * @param {string} side (top|right|...|all)
		 * @param {string} input 
		 */
		handleValueChange(side, input) {
			const { value } = this.props;
			const { mode, unit } = this.state;
			const { type, sides } = this.config;
			const unitValue = greyd.tools.getNumValue(input)+unit;
			let newValue = {};

			if ( "all" === side ) {
				if ( type === 'string' ) {
					newValue = unitValue;
				}
				else {
					sides.forEach(side => {
						newValue[side] = unitValue;
					});
				}
			}
			else {
				newValue = this.getValueAsObject( value );
				newValue[side] = unitValue;

				if ( type === 'string' ) {
					newValue = this.convertObjectValueToString( newValue )
				}
			}

			this.props.onChange( newValue );
		}

		/**
		 * Handle change of a unit value.
		 * @param {string} input unit value
		 */
		handleUnitChange(input) {
			const { value } = this.props;
			const state = this.state;
			const unit = greyd.tools.getUnitValue(input);
			const { type, sides } = this.config;

			if ( !_.isEmpty(unit) && unit !== state.unit ) {
				this.setState({
					...state,
					unit: unit
				}, () => {

					let newValue = this.getValueAsObject( value );
					for (const [side, val] of Object.entries(newValue)) {
						const numVal = greyd.tools.getNumValue(val);
						if ( _.isEmpty(numVal) ) {
							delete newValue[side];
						}
						else {
							newValue[side] = numVal+unit;
						}
					}
					if ( type === 'string' ) {
						newValue = this.convertObjectValueToString( newValue )
					}
	
					this.props.onChange( newValue );
				});
			}
		}

		convertObjectValueToString( value ) {
			const newValue = [];
			for (const [side, val] of Object.entries(value)) {
				if ( val === null ) {
					newValue.push('0');
				} else {
					newValue.push(val);
				}
			}
			return newValue.join(" ");
		}

		/**
		 * Reduce object of values to the first value that is set.
		 */
		getFirstValue( value ) {
			if ( typeof value === 'string' ) {
				return value;
			}
			else if (typeof value === "object" && !_.isEmpty(value) ) {
				for (const val of Object.values( value )) {
					if ( !_.isEmpty(val) ) {
						return val;
					}
				}
			}
			return null;
		}

		/**
		 * Reset the value & mode (not the unit)
		 */
		clear() {
			this.setState({
				unit: this.state.unit,
				mode: "simple"
			}, () => {
				this.props.onChange( {} );
			});
		}

		/**
		 * Switch mode between simple and advanced
		 */
		switchMode() {
			let state = {
				...this.state,
				mode: this.state.mode === "simple" ? "advanced" : "simple"
			};

			let callback = null;
			if ( "simple" === state.mode ) {
				callback = this.handleValueChange("all", this.getFirstValue(this.props.value));
			}

			this.setState( state, callback );
		}

		/**
		 * Render the control
		 */
		render() {
			const { type, labels, min, max, units, sides } = this.config;
			const { mode, unit } = this.state;
			const { label } = this.props;
			const value = this.getValueAsObject( this.props.value );

			return el( "div", { className: "dimension_control" }, [
				el( "div", { className: "dimension_control__label flex" }, [
					( label ?
						el( greyd.components.AdvancedLabel, {
							label: label,
							initialValue: type === 'string' ? '' : {},
							currentValue: value,
							onClear: this.clear
						} ) :
						"<div></div>"
					),
					el( wp.components.Icon, {
						icon: mode === "simple" ? "admin-links" : "editor-unlink",
						className: "switch_button",
						onClick: this.switchMode
					} ),
				] ),
				el( "div", { className: "dimension_control__inputs" }, (
					mode === "simple" ? (
						[
							el( "div", { className: "flex" }, [
								el( "span", { className: "inner_label" }, __(labels.all, "greyd_hub") ),
								el( wp.components.__experimentalUnitControl, {
									value: this.getFirstValue(value), // + unit,
									min: min,
									max: max[unit],
									step: unit == 'em' || unit == 'rem' ? 0.1 : 1,
									units: units,
									onChange: (newValue) => this.handleValueChange("all", newValue),
									onUnitChange: (newUnit) => this.handleUnitChange(newUnit)
								} )
							] ),
						]
					) : (
						sides.map((side) => {
							return el( "div", { className: "flex" }, [
								el( "span", { className: "inner_label" }, __(labels[side], "greyd_hub") ),
								el( wp.components.__experimentalUnitControl, {
									value: _.has(value, side) ? value[side] : null,
									min: min,
									max: max[unit],
									step: unit == 'em' || unit == 'rem' ? 0.1 : 1,
									units: units,
									onChange: (newValue) => this.handleValueChange(side, newValue),
									onUnitChange: (newUnit) => this.handleUnitChange(newUnit)
								} )
							] );
						})
					)
				) ),
			] );
		}
	}

}