/**
 * Greyd Block Editor Components for Popups.
 * 
 * This file is loaded in the editor.
 */

greyd.components.popup = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Renders an even more advanced PanelBody 
	 * with state (normal/hover/active) OR responsive (xl/lg/md/sm) buttons OR custon set of tabs.
	 * 
	 * @property {string} title Title of the section.
	 * @property {bool} holdsChange If the panel holds a changed value.
	 * @property {array} holdsColors Show color Indicators if value is changed.
	 * ---- state ----
	 * @property {bool} supportsState Whether state controls are supported.
	 * @property {bool} isStateEnabled Whether state controls are toggled on initially.
	 * @property {callback|bool} onStateToggle Function to be called when state controls are toggled. Optional, false to disable toggle.
	 * ---- responsive ----
	 * @property {bool} supportsResponsive Whether responsive controls are supported.
	 * @property {bool} isResponsiveEnabled Whether responsive controls are toggled on initially.
	 * @property {callback|bool} onResponsiveToggle Function to be called when responsive controls are toggled. Optional, false to disable toggle.
	 * ---- custom tabs ----
	 * @property {array} tabs custom array of { title, slug } objects to modify tabs.
	 * @property {callback} onTabSwitch Function to be called when tabs are switched. Optional.
	 * 
	 * @returns {object} AdvancedPanelBody component.
	 */
	this.MoreAdvancedPanelBody = class extends wp.element.Component {

		constructor(props) {
			super(props);
			
			this.onStateToggle 		= this.onStateToggle.bind(this);
			this.onResponsiveToggle = this.onResponsiveToggle.bind(this);
			this.onTabSwitch 		= this.onTabSwitch.bind(this);
			
			// check if component is in site-editor
			// var editor = document.getElementById("edit-site-editor");
			var editor = document.querySelector(".edit-site-visual-editor");
			if (editor) {
				var iframe = document.querySelector(".edit-site-visual-editor__editor-canvas");
				if (iframe) {
					editor = iframe.contentWindow.document.querySelector('.wp-site-blocks');
				}
			}
			if (!editor) {
				editor = document.querySelector(".editor-styles-wrapper");
				if (!editor) {
					var iframe = document.querySelector("iframe[name=editor-canvas]");
					if (iframe) {
						editor = iframe.contentWindow.document.querySelector('.editor-styles-wrapper');
					}
				}
			}
			this.config = {
				editor: editor,
				stateTabs: [
					{
						label: __("Default", 'greyd_hub'),
						slug: ""
					},
					{
						label: __("Hover", 'greyd_hub'),
						slug: "hover",
						previewClass: "has-hover"
					},
					{
						label: __("Active", 'greyd_hub'),
						slug: "active",
						previewClass: "has-active"
					}
				],
				responsiveTabs: [
					{
						label: el( greyd.components.Icon, { icon: "desktop" } ),
						slug: ""
					},
					{
						label: el( greyd.components.Icon, { icon: "laptop" } ),
						slug: "lg"
					},
					{
						label: el( greyd.components.Icon, { icon: "tablet" } ),
						slug: "md"
					},
					{
						label: el( greyd.components.Icon, { icon: "mobile" } ),
						slug: "sm"
					}
				]
			}

			this.state = {
				isStateEnabled: false,
				isResponsiveEnabled: false,
				tabs: [],
				activeTab: "", // normal
			};
		}

		componentDidMount() {
			// console.log("componentDidMount");
			// console.log(this.props);

			var newState = { tabs: [] };
			// states
			if (_.has(this.props, "supportsState") && this.props.supportsState) {
				newState.tabs = this.config.stateTabs;
				if (_.has(this.props, "isStateEnabled") && this.props.isStateEnabled) {
					newState.isStateEnabled = true;
				}
			}
			// responsive
			else if (_.has(this.props, "supportsResponsive") && this.props.supportsResponsive) {
				newState.tabs = this.config.responsiveTabs;
				if (_.has(this.props, "isResponsiveEnabled") && this.props.isResponsiveEnabled) {
					newState.isResponsiveEnabled = true;
				}
				// responsive preview
				var viewport = this.getViewport();
				// get active tab
				newState.activeTab = this.getActiveTab(viewport);
				// subscribe to viewport change
				this.unsubscribeViewportChange = wp.data.subscribe(() => {
					if (this.state.isResponsiveEnabled) {
						// console.log("checking viewport");
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
			// tabs
			else if (_.has(this.props, "tabs") && this.props.tabs && _.isArray(this.props.tabs)) {
				newState.tabs = this.props.tabs;
			}
			
			// check state
			if (this.config.editor && typeof this.config.editor !== 'undefined') {
				// check global preview class
				// console.log(newState.tabs);
				// console.log(this.config.editor.classList);
				newState.tabs.forEach((tab) => {
					if (_.has(tab, 'previewClass') && this.config.editor.classList.contains(tab.previewClass))
						newState.activeTab = tab.slug;
				});

				// subscribe to change in classList
				// buggy -> todo
				var classList = JSON.stringify(this.config.editor.classList);
				// console.log(classList);
				this.unsubscribePreviewChange = wp.data.subscribe(() => {
					// console.log("checking classlist");
					var newClassList = JSON.stringify(this.config.editor.classList);
					// console.log(newClassList);
					if (!_.isEqual(classList, newClassList)) {
						// console.log("classlist changed ...");
						classList = newClassList;
						var newTab = "";
						this.state.tabs.forEach((tab) => {
							if (_.has(tab, 'previewClass') && this.config.editor.classList.contains(tab.previewClass)) {
								newTab = tab.slug;
							}
						});
						this.setState({
							...this.state,
							activeTab: newTab
						});
					}
				});
	
			}
			else {
				// set individual block preview
			}

			// set state
			if (!_.isEmpty(newState)) {
				this.setState({
					...this.state,
					...newState
				});
			}

		}

		componentDidUpdate(prevProps) {
			// console.log("componentDidUpdate");
		}
		
		componentWillUnmount() {
			// console.log("componentWillUnmount");
			if (_.has(this, "unsubscribeViewportChange")) this.unsubscribeViewportChange();
			if (_.has(this, "unsubscribePreviewChange")) this.unsubscribePreviewChange();
		}

		/**
		 * Default On State Toggle Event. Overridable by @property onStateToggle
		 */
		onStateToggle() {
			// console.log("toggle state");
			var newState = {};
			// switched off
			if (this.state.isStateEnabled) {
				newState.isStateEnabled = false;
				newState.activeTab = "";
			}
			// switched on
			else {
				newState.isStateEnabled = true;
				newState.activeTab = "hover"; // maybe
			}
			// set state
			if (!_.isEmpty(newState)) {
				this.setState({
					...this.state,
					...newState
				});
			}
		}

		/**
		 * Default On Responsive Toggle Event. Overridable by @property onResponsiveToggle
		 */
		onResponsiveToggle() {
			// console.log("toggle responsive");
			var newState = {};
			// switched off
			if (this.state.isResponsiveEnabled) {
				newState.isResponsiveEnabled = false;
				newState.activeTab = "";
			}
			// switched on
			else {
				newState.isResponsiveEnabled = true;
			}
			// set state
			if (!_.isEmpty(newState)) {
				this.setState({
					...this.state,
					...newState
				});
			}
		}

		/**
		 * Default On Tab Switch Event. Overridable by @property onTabSwitch
		 * @param {string} tab ("" | "hover" | "active" | "lg" | "md" | "sm")
		 */
		onTabSwitch(tab) {
			// console.log("toggle tab: "+tab);
			// console.log(this.config.editor);
			if (this.config.editor && typeof this.config.editor !== 'undefined') {
				if (this.state.isResponsiveEnabled) {
					// console.log("toggle responsive preview "+tab);
					this.setPreviewWindow(tab);
				}
				else {
					// set global preview class in site-editor
					this.state.tabs.forEach((element) => {
						if (_.has(element, 'previewClass')) {
							this.config.editor.classList.remove(element.previewClass);
							if (tab == element.slug) {
								this.config.editor.classList.add(element.previewClass);
							}
						}
					});
					// wp.data.dispatch("core/edit-site").updateSettings();
				}
			}
			else {
				// set individual block preview
			}

			if (this.state.activeTab != tab) {
				this.setState({
					...this.state,
					activeTab: _.isEmpty(tab) ? "" : tab
				});
			}
		}


		/**
		 * Set the editor preview window ("Desktop" | "Tablet" | "Mobile")
		 * @param {string} tab ("" | "lg" | "md" | "sm")
		 */
		setPreviewWindow(tab) {
			const viewports = {
				"": "Desktop",
				"lg": "Desktop",
				"md": "Tablet",
				"sm": "Mobile",
			};
			if (_.has(viewports, tab)) {
				// console.log(viewport);
				var viewport = viewports[tab];
				var currentViewport = this.getViewport();
				if (currentViewport && !_.isEqual(currentViewport, viewport)) {
					// console.log("switch viewport");
					this.setViewport(viewport);
				}
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
				var store = (this.config.editor && typeof this.config.editor !== 'undefined') ? "core/edit-site" : "core/edit-post";
				if ( !_.has(wp.data.select(store), "__experimentalGetPreviewDeviceType") ) return false;
				return wp.data.select(store).__experimentalGetPreviewDeviceType();
			}
		}
		setViewport(viewport) {
			if ( _.has(wp.data.select( 'core/editor' ), 'setDeviceType') ) {
				return wp.data.select( 'core/editor' ).setDeviceType();
			} else {
				var store = (this.config.editor && typeof this.config.editor !== 'undefined') ? "core/edit-site" : "core/edit-post";
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


		// todo
		make_color_indicators = function() {
			var colors = [];
			this.props.holdsColors.forEach(function(value, i) {
				if (typeof value.color !== 'undefined') {
					// console.log(value.color);
					var col = greyd.tools.getGradientValue(greyd.tools.getColorValue(value.color));
					if (col != "") colors.push(el( wp.components.ColorIndicator, { colorValue: col, title: value.title } ));
				}

			});
			return el( "span", { className: "block-editor-panel-color-gradient-settings__panel-title", style: { display: 'inline-flex' } }, colors );
		};

		make_controls = function(children, activeTab) {
			// console.log("render "+activeTab);

			if (typeof children === 'string') {
				// just label or text
				return children;
			}
			else {
				// make child controls with tab specific controls
				return children.map((control) => {

					if ( control ) {
						if (_.isArray(control)) {
							// controls in nested array
							return this.make_controls(control, activeTab);
						}
						else if (_.has(control, 'type') && _.has(control, 'props') ) {
							// child control
							var props = _.clone(control.props);
							if ( _.has(control.props, "children") && !_.isEmpty(control.props.children) ) {
								// make inner controls
								props.children = this.make_controls(control.props.children, activeTab);
							}
							if ( activeTab != "" ) {
								// other tab
								if ( _.has(control.props, activeTab) && !_.isEmpty(control.props[activeTab]) ) {
									// add tab specific controls
									props = { ...props, ...control.props[activeTab] };
								}
							}
							return el( control.type, { ...props } );
						}
					}

				});
			}

		}

		render() {
			// console.log("render");
			// console.log(this.props);

			const {
				title = "",
				holdsChange = false,
				holdsColors = false,

				// state props
				supportsState = false,
				onStateToggle = this.onStateToggle,
				// responsive props
				supportsResponsive = false,
				onResponsiveToggle = this.onResponsiveToggle,
				// tabs
				onTabSwitch = this.onTabSwitch,
				children = []
			} = this.props;

			const {
				// state state
				isStateEnabled,
				// responsive state
				isResponsiveEnabled,
				// tabs state
				tabs,
				activeTab,
			} = this.state;

			// todo
			if (holdsColors) {
				if (typeof holdsColors === 'object' && holdsColors.length == 0) holdsColors = false;
				else {
					var classname = _.has(this.props, 'className') ? this.props.className+' ' : '';
					this.props.className = classname+'block-editor-panel-color-gradient-settings';
				}
			}

			return el( wp.components.PanelBody, {
				...this.props,
				title: el( "div", { }, [
						el( "span", {}, title),
						holdsChange && !holdsColors ? el( "span", { className: "change_holder" } ) : "",
						// holdsColors ? this.make_color_indicators() : "",
						el( "div", { className: "panel_buttons" }, [
							supportsState && onStateToggle ? 
								el( wp.components.Tooltip, {
										text: __( "Change on hover", 'greyd_hub' )
									}, el( "button", {
										className: "button button-ghost"+( isStateEnabled ? " active" : "" ),
										onClick: (e) => {
											e.stopPropagation();
											typeof onStateToggle === "function" && onStateToggle();
										}
									}, el( greyd.components.Icon, {
										icon: "hover",
										width: 12
									} ) )
								) : "",
							!supportsState && supportsResponsive && onResponsiveToggle ? 
								el( wp.components.Tooltip, {
										text: __("Change on screen sizes", 'greyd_hub')
									}, el( "button", {
										className: "button button-ghost"+(isResponsiveEnabled ? " active" : ""),
										onClick: (e) => {
											e.stopPropagation();
											typeof onResponsiveToggle === "function" && onResponsiveToggle();
										}
									}, el( greyd.components.Icon, {
										icon: "mobile",
										width: 12
									} ) )
								) : ""
						] ),
					]
				),
				children: [
					// tabs
					tabs.length < 2 ? "" :
						el( "div", { className: "greyd_tabs" },
							tabs.map((tab) => {
								return el( "span", {
									className: "tab"+(activeTab === tab.slug ? " active" : ""),
									onClick: () => {
										typeof onTabSwitch === "function" && onTabSwitch(tab.slug);
									}
								}, tab.label );
							})
						),

					// children controls
					this.make_controls(children, activeTab)				

				]
			});
		}
	}

	/**
	 * ColorGradientPopupControl
	 * Color value is saved as css-variable if possible (eg. "var(--wp--preset--color--foreground)" or "#5D5D5D").
	 * 
	 * @property {string} label Label to be displayed.
	 * @property {string} className Class of component ('single' for full border, 'small' for indicator only).
	 * @property {object} style Additional style of component.
	 * @property {string} mode 'color' or 'gradient', omit property to show both.
	 * @property {bool} enableAlpha enable/disable alpha value in color picker. (default = false)
	 * 
	 * @property {bool} hover Indicator if component is rendered inside hover tab.
	 * @property {object} contrast If set, a ContrastChecker component is rendered
	 *     @property {string} contrast.default Value to compare contrast against.
	 *     @property {string} contrast.hover Value to compare contrast against in hover tab.
	 * 
	 * @property {string} value Current value.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} ColorGradientPopupControl component.
	 */
	this.ColorGradientPopupControl = class extends wp.element.Component {
		constructor(props) {
			super(props);

			// state
			this.state = {
				id: greyd.tools.generateRandomID(),
				isOpen: false,
				mode: '', color: '', gradient: ''
			};
			this.nextState = '';
		}
		componentWillMount() {
			this.loadValue(this.props);
		}
		componentWillReceiveProps(props) {
			this.loadValue(props);
		}

		/**
		 * Load value and mode based on props
		 * @param {mixed} props new props object
		 */
		loadValue(props) {
			var mode = '';
			var color = '';
			var gradient = '';
			// always convert var(--wp--preset--color--xxx) to color internally
			var value = this.convertVarToColor(props.value);
			if (_.has(props, 'mode')) {
				mode = props.mode;
				if (props.mode == 'color') color = value;
				if (props.mode == 'gradient') gradient = value;
			}
			else if (value.indexOf('#') === 0 || value.indexOf('rgb') === 0) {
				mode = 'color';
				color = value;
			}
			else if (value.indexOf('linear-gradient(') === 0 || value.indexOf('radial-gradient(') === 0) {
				mode = 'gradient';
				gradient = value;
			}
			else {
				// console.log(value);
				// todo
				// for (var i=0; i<greyd.data.colors.length; i++) {
				// 	if (greyd.data.colors[i].slug == value) {
				// 		mode = 'color';
				// 		color = greyd.data.colors[i].color;
				// 		break;
				// }	}
				// if (mode == '') for (var i=0; i<greyd.data.gradients.length; i++) {
				// 	if (greyd.data.gradients[i].slug == value) {
				// 		mode = 'gradient';
				// 		gradient = greyd.data.gradients[i].gradient;
				// 		break;
				// }	}
			}
			this.nextState = mode;
			this.setState({ mode: mode, color: color, gradient: gradient });
		}

		/**
		 * Get value from state for rendering
		 */
		getValue() {
			var value = '';
			// always convert var(--wp--preset--color--xxx) to color for rendering
			if (this.state.mode == 'color') value = greyd.tools.popup.getColorValue(this.state.color);
			if (this.state.mode == 'gradient') value = greyd.tools.popup.getGradientValue(this.state.gradient);
			return value;
		}

		/**
		 * Handle change and save event
		 * @param {string} mode current mode (color or gradient)
		 * @param {string} value new value
		 */
		setValue(mode, value) {
			// console.log(mode+': '+value);
			// console.log(this.state);
			// console.log(this.props.value);

			var color = this.state.color;
			var gradient = this.state.gradient;

			if (typeof value != 'undefined') {
				// get var values for saving
				if (mode == 'color') color = this.convertColorToVar(value);
				if (mode == 'gradient') gradient = this.convertGradientToVar(value);

				// console.log(color);
				// console.log(gradient);
				this.nextState = mode;
				this.setState({ mode: mode, color: color, gradient: gradient });
				if (mode == 'color') this.props.onChange(color);
				else if (mode == 'gradient') this.props.onChange(gradient);
			}
			else if (mode == this.nextState) {
				if (mode == 'color') color = '';
				else if (mode == 'gradient') gradient = '';

				this.nextState = '';
				this.setState({ mode: '', color: color, gradient: gradient });
				this.props.onChange('');
			}
		}

		/**
		 * Convert var(--wp--preset--color--xxx) in gradients to color
		 * or
		 * Convert var(--wp--preset--color--xxx) to color
		 */
		convertVarToColor(value) {
			if ( !_.isEmpty(value) ) {
				if (value.indexOf('linear-gradient(') === 0 || value.indexOf('radial-gradient(') === 0) {
					return greyd.tools.popup.getGradientValue(value);
				}
				else {
					return greyd.tools.popup.getColorValue(value);
				}
			}
			return value;
		}

		/**
		 * Convert color value to var(--wp--preset--color--xxx)
		 */
		convertColorToVar(value) {
			if ( !_.isEmpty(value) ) {
				return greyd.tools.popup.getColorVariable(value);
			}
			return value;
		}

		/**
		 * Convert color values in gradient to var(--wp--preset--color--xxx)
		 */
		convertGradientToVar(value) {
			if ( !_.isEmpty(value) ) {
				return greyd.tools.popup.getGradientVariable(value);
			}
			return value;
		}

		render() {
			// console.log('render');
			// console.log(this);

			var hover = _.has(this.props, 'hover') && this.props.hover == true ? true : false;
			var showColor = (!hover || (hover && this.state.mode != 'gradient'));
			var showGradient = (!hover || (hover && this.state.mode != 'color'));
			var enableAlpha = _.has(this.props, 'enableAlpha') && this.props.enableAlpha == true ? true : false;

			var hasLabel = _.has(this.props, 'label') && this.props.label != '' ? true : false;
			var isOpen = this.state.isOpen;
			var value = this.getValue();

			return [
				el( 'div', { 
					// wrapper
					className: 'color-block-support-panel color_gradient_popup_control'+(_.has(this.props, 'className') ? ' '+this.props.className : ''),
					style: { ...this.props.style } 
				}, el( 'div', { 
					// wrapper
						className: 'block-editor-tools-panel-color-dropdown' 
					}, el( wp.components.Button, { 
							// trigger button
							"aria-expanded": isOpen,
							"data-id": this.state.id,
							className: 'block-editor-panel-color-gradient-settings__dropdown'+(isOpen ? ' is-open' : ''),
							onClick: () => this.setState({ isOpen: isOpen ? false : true })
						}, el( wp.components.__experimentalHStack, { justify: "flex-start" },
							el( wp.components.ColorIndicator, {
								// color/gradient indicator
								className: "block-editor-panel-color-gradient-settings__color-indicator",
								colorValue: value
							} ), 
							// label
							hasLabel && el( wp.components.FlexItem, { }, this.props.label ) ),
							isOpen && el( wp.components.Popover, {
								// color/gradient popover
								onClick: (e) => e.stopPropagation(),
								className: "color_popup_control__popover color_gradient",
								position: "middle left",
								onFocusOutside: (event) => {
									if (typeof event.relatedTarget !== 'undefined' &&
										_.has(event.relatedTarget, "dataset.id") &&
										event.relatedTarget.dataset.id == this.state.id) {
										// console.log('clicked on own button');
										return;
									}
									this.setState({ isOpen: false })
								},
							}, [
								el( "div", { className: "color_popup_control__popover_content" }, [
									this.props.mode == 'color' && el( wp.blockEditor.ColorPalette, {
										// only solids
										__experimentalHasMultipleOrigins: true,
										enableAlpha: enableAlpha,
										colors: greyd.tools.popup.getColors(),
										value: value,
										onChange: (newValue) => this.setValue('color', newValue),
									} ),
									this.props.mode == 'gradient' && el( wp.components.GradientPicker, {
										// only gradients
										__experimentalHasMultipleOrigins: true,
										enableAlpha: enableAlpha,
										gradients: greyd.tools.popup.getGradients(),
										value: value,
										onChange: (newValue) => this.setValue('gradient', newValue),
									} ),
									!_.has(this.props, 'mode') && el( wp.blockEditor.__experimentalColorGradientControl, {
										// solids and gradients
										__experimentalHasMultipleOrigins: true,
										enableAlpha: enableAlpha,
										colors: greyd.tools.popup.getColors(),
										gradients: greyd.tools.popup.getGradients(),
										colorValue: (showColor && this.state.mode == 'color') ? value : undefined,
										onColorChange: (showColor) ? (newValue) => this.setValue('color', newValue) : undefined,
										gradientValue: (showGradient && this.state.mode == 'gradient') ? value : undefined,
										onGradientChange: (showGradient) ? (newValue) => this.setValue('gradient', newValue) : undefined,
									} )
								] )
							] )
						),
					),
					// contrast checker
					_.has(this.props, 'contrast') && this.state.mode == 'color' ? el( wp.blockEditor.ContrastChecker, { 
						textColor: greyd.tools.popup.getColorValue(this.convertVarToColor(hover ? this.props.contrast.hover : this.props.contrast.default)), // '#fff', 
						backgroundColor: value 
					} ) : ''
				)
			];
		}
	}

	/**
	 * Control for css box-shadow property
	 * 
	 * @property {string} label Optional label to be displayed.
	 * @property {string} value Current value.
	 * @property {object} default var and value of default setup.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {string} Value in css format. Empty String "" on default or if cleared.
	 */	
	this.DropShadowControl = class extends wp.element.Component {
		constructor(props) {
			super(props);

			// state
			this.state = {
				value: this.getDefault()
			};
		}
		getDefault() {
			return {
				color: "",
				x: 0,
				y: 10,
				blur: 15,
				spread: 0,
				opacity: 25,
				position: "outset"
			};
		}
		componentWillMount() {
			this.loadValue(this.props);
		}
		componentWillReceiveProps(props) {
			this.loadValue(props);
		}

		/**
		 * Load value and mode based on props
		 * @param {mixed} props new props object
		 */
		loadValue(props) {

			var newValue = this.state.value;
			var values = props.value;

			// check default
			if (_.has(props, "default")) {
				if (values == props.default.var) {
					values = props.default.value;
				}
			}

			// read css string
			if (values != "none") {
				// read position
				if (values.indexOf('inset') == 0) {
					newValue.position = 'inset';
					values = values.replace('inset ', '');
				}
				// read sets
				var [ x, y, blur, spread, color ] = values.split('px ');
				var opacity = newValue.opacity;
				// read color
				if (color && color.indexOf('#') === 0) {
					// color = greyd.tools.popup.hex2rgba(color);
					opacity = 100;
				}
				else if (color && color.indexOf('rgb') === 0) {
					// if a preset has alpha (rgba), the opacity can't be extracted
					// alpha value becomes opacity and solid color stays
					color = greyd.tools.popup.rgb2rgba(color);
					opacity = color.a*100.0;
					color = greyd.tools.popup.rgb2hex(color);
				}
				// set value
				newValue = { 
					...newValue, 
					x: parseInt(x), 
					y: parseInt(y), 
					blur: parseInt(blur), 
					spread: parseInt(spread), 
					opacity: parseInt(opacity), 
					color: color 
				};
				// console.log(newValue);
			}

			// set state
			this.setState({ value: newValue });
		}

		/**
		 * Handle change and save event
		 * @param {string} name Name of edited sub-value
		 * @param {string} value new value
		 */
		setValue(name, value) {
			// make new state value
			var newValue = { ...this.state.value, [name]: value };

			// make color
			var color = greyd.tools.popup.getColorValue(newValue.color);
			if (!color || color == 'transparent' || color == '') {
				color = { 'r': 0, 'g': 0, 'b': 0, 'a': 0 };
			}
			else if (color.indexOf('#') === 0) {
				color = greyd.tools.popup.hex2rgba(color);
			}
			else if (color.indexOf('rgb') === 0) {
				color = greyd.tools.popup.rgb2rgba(color);
			}
			if (!_.has(color, 'a')) {
				color['a'] = 1;
			}
			// calculate opacity
			color['a'] = color['a'] * (newValue.opacity / 100.0);

			// make css string
			var cssValue = newValue.position === "inset" ? "inset " : "";
			cssValue += newValue.x+"px "+newValue.y+"px "+newValue.blur+"px "+newValue.spread+"px ";
			cssValue +='rgba('+color['r']+','+color['g']+','+color['b']+','+color['a']+')';

			// check default
			if (_.has(this.props, "default")) {
				if (cssValue == this.props.default.value) {
					cssValue = this.props.default.var;
				}
			}

			// set state and value
			this.setState({ ...this.state, value: newValue });
			this.props.onChange(cssValue);
		}

		render() {
			// console.log('render');
			// console.log(this.state);

			var value = this.state.value;

			return [
				el( "div", { className: "drop_shadow_control" }, [
					this.props.label ?
						el( "div", { className: "drop_shadow_control__label" }, [
							// el( greyd.components.AdvancedLabel, {
							// 	label: this.props.label,
							// 	initialValue: "",
							// 	currentValue: this.getStringValue(this.state.value),
							// 	onClear: this.clear
							// } )
							el( 'label', {}, this.props.label ),
						] ) : "",
					el( "div", { }, [
						el( greyd.components.popup.ColorGradientPopupControl, {
							label: __("Color", 'greyd_hub'),
							mode: 'color',
							value: value.color,
							onChange: (newValue) => this.setValue("color", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Horizontally", 'greyd_hub') ),
						el( wp.components.RangeControl, {
							value: value.x,
							min: -50,
							max: 50,
							onChange: (newValue) => this.setValue("x", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Vertical", 'greyd_hub') ),
						el( wp.components.RangeControl, {
							value: value.y,
							min: -50,
							max: 50,
							onChange: (newValue) => this.setValue("y", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Blur", 'greyd_hub') ),
						el( wp.components.RangeControl, {
							value: value.blur,
							min: 0,
							max: 50,
							onChange: (newValue) => this.setValue("blur", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Spread", 'greyd_hub') ),
						el( wp.components.RangeControl, {
							value: value.spread,
							min: -50,
							max: 50,
							onChange: (newValue) => this.setValue("spread", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Opacity", 'greyd_hub') ),
						el( wp.components.RangeControl, {
							value: value.opacity,
							min: 0,
							max: 100,
							onChange: (newValue) => this.setValue("opacity", newValue)
						} )
					] ),
					el( wp.components.__experimentalHStack, { }, [
						el( "span", { className: "inner_label" }, __("Position", 'greyd_hub') ),
						el( greyd.components.ButtonGroupControl, {
							value: value.position,
							options: [
								{
									label: __("Outset", 'greyd_hub'),
									value: "outset"
								},
								{
									label: __("Inset", 'greyd_hub'),
									value: "inset"
								},
							],
							onChange: (newValue) => this.setValue("position", newValue)
						} )
					] ),
				] )
			];
		}
	}

	// console.log("popups editor components loaded");


	/*
	=======================================================================
		Additional components
	=======================================================================
	*/


	/**
	 * SizeControl grouping width, height and alignment controls.
	 * 
	 * @property {object} value
	 * 		@property {string} width
	 * 		@property {string} height
	 * 		@property {string} align
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} SizeControl component.
	 */
	this.SizeControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		updateValue(mode, newValue) {
			var newValues = { ...this.props.value, [mode]: newValue };
			this.props.onChange(newValues);
		}
		render() {

			var elements = [];

			// width
			var width = this.props.value.width;
			var widthValue = width;
			if (width != 'auto' && width != '100') widthValue = 'custom';
			if (width == '100%') widthValue = '100';
			elements.push(
				el( 'label', {}, __("Width", 'greyd_hub') ),
				el( greyd.components.ButtonGroupControl, {
					value: widthValue,
					options: [
						{ value: 'auto',		label: __('Auto', 'greyd_hub') }, 
						{ value: '100',			label: __('100%', 'greyd_hub') }, 
						{ value: 'custom',		label: __("Custom", 'greyd_hub') }, 
					],
					onChange: (newValue) => {
						// console.log(newValue);
						this.updateValue('width', newValue);
					},
				} )
			);
			if (widthValue == 'custom') {
				var widthValueCustom = width;
				if (widthValueCustom == 'custom') widthValueCustom = '100%';
				elements.push(
					el( wp.components.__experimentalUnitControl, {
						value: widthValueCustom,
						min: 0,
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue("width", newValue)
						}
					} )
				);
			}

			// height
			var height = this.props.value.height;
			var heightValue = height;
			if (height != 'auto' && height != '100') heightValue = 'custom';
			if (height == '100%') heightValue = '100';
			elements.push(
				el( 'label', {}, __("Height", 'greyd_hub') ),
				el( greyd.components.ButtonGroupControl, {
					value: heightValue,
					options: [
						{ value: 'auto',		label: __('Auto', 'greyd_hub') }, 
						{ value: '100',			label: __('100%', 'greyd_hub') }, 
						{ value: 'custom',		label: __("Custom", 'greyd_hub') }, 
					],
					onChange: (newValue) => {
						// console.log(newValue);
						this.updateValue('height', newValue);
					},
				} )
			);
			if (heightValue == 'custom') {
				var heightValueCustom = height;
				if (heightValueCustom == 'custom') heightValueCustom = '100%';
				elements.push(
					el( wp.components.__experimentalUnitControl, {
						value: heightValueCustom,
						min: 0,
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue("height", newValue)
						}
					} )
				);
			}
			if (heightValue != 'auto') {
				elements.push(
					el( 'label', {}, __("Alignment of the content", 'greyd_hub') ),
					el( greyd.components.ButtonGroupControl, {
						value: this.props.value.align,
						options: [
							{ value: 'start',	label: __("Top", 'greyd_hub') }, 
							{ value: 'center',	label: __("Centered", 'greyd_hub') }, 
							{ value: 'end',		label: __("Bottom", 'greyd_hub') }, 
						],
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue('align', newValue);
						},
					} )
				);
			}

			return elements;
		}
	}

	/**
	 * ColorsControl grouping text, headline and background color controls.
	 * 
	 * @property {object} value
	 * 		@property {string} text
	 * 		@property {string} headline
	 * 		@property {string} background
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} ColorsControl component.
	 */
	this.ColorsControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		ColorControl(mode, atts) {
			return el( greyd.components.popup.ColorGradientPopupControl, {
				...atts,
				onChange: (newValue) => {
					// console.log(mode, newValue);
					this.updateValue(mode, newValue);
				}
			} );
		}
		updateValue(mode, newValue) {
			var newValues = { ...this.props.value, [mode]: newValue };
			this.props.onChange(newValues);
		}
		render() {

			var elements = [];

			elements.push(
				this.ColorControl('text', {
					'mode'			: 'color',
					'label'         : __("Text color", 'greyd_hub'), 
					'value'			: this.props.value.text
				}),
				this.ColorControl('headline', {
					'mode'			: 'color',
					'label'         : __("Headline color", 'greyd_hub'), 
					'value'			: this.props.value.headline
				}),
				this.ColorControl('background', {
					'label'         : __("Background color", 'greyd_hub'), 
					'value'			: this.props.value.background
				})
			);

			return el( "div", { }, elements );

		}
	}

	/**
	 * OverlayEffectControl grouping effect select and individual value controls.
	 * 
	 * @property {string} label
	 * @property {object} value
	 * 		@property {string} effect
	 * 		@property {int} blur
	 * 		@property {int} brightness
	 * 		@property {int} contrast
	 * 		@property {int} grayscale
	 * 		@property {int} hue-rotate
	 * 		@property {int} invert
	 * 		@property {int} saturate
	 * 		@property {int} sepia
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} OverlayEffectControl component.
	 */
	this.OverlayEffectControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		EffectRangeControl(effect, atts) {
			return el( wp.components.RangeControl, {
				label: atts.label,
				value: parseInt(atts.value),
				...atts.input_attrs,
				onChange: (newValue) => {
					// console.log(effect, newValue);
					this.updateValue(effect, newValue);
				}
			} );
		}
		updateValue(effect, newValue) {
			var newValues = { ...this.props.value, [effect]: newValue };
			this.props.onChange(newValues);
		}
		render() {

			var elements = [];

			elements.push(
				el( greyd.components.OptionsControl, {
					style: { width: "100%" },
					label: this.props.label,
					value: this.props.value.effect,
					options: [
						{ value: 'none',		label: __("No effect", 'greyd_hub') },
						{ value: 'blur',		label: __("Blur", 'greyd_hub') },
						{ value: 'brightness',	label: __("Brightness", 'greyd_hub') },
						{ value: 'contrast',	label: __("Contrast", 'greyd_hub') },
						{ value: 'grayscale',	label: __("Grayscale", 'greyd_hub') },
						{ value: 'hue-rotate',	label: __("Hue rotation", 'greyd_hub') },
						{ value: 'invert',		label: __("Invert", 'greyd_hub') },
						{ value: 'saturate',	label: __("Saturation", 'greyd_hub') },
						{ value: 'sepia',		label: __("Sepia", 'greyd_hub') },
					],
					onChange: (newValue) => {
						// console.log(newValue);
						this.updateValue('effect', newValue);
					}
				} )
			);

			if (this.props.value.effect == 'blur') {
				elements.push(
					this.EffectRangeControl('blur', {
						'label'         : __("Blur", 'greyd_hub')+' (px)', 
						'input_attrs'   : { 'min' : 0, 'max' : 100, 'step' : 1 }, 
						'value'			: this.props.value.blur
					})
				);
			}
			if (this.props.value.effect == 'brightness') {
				elements.push(
					this.EffectRangeControl('brightness', {
						'label'         : __("Brightness", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 200, 'step' : 1 }, 
						'value'			: this.props.value.brightness
					})
				);
			}
			if (this.props.value.effect == 'contrast') {
				elements.push(
					this.EffectRangeControl('contrast', {
						'label'         : __("Contrast", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 200, 'step' : 1 }, 
						'value'			: this.props.value.contrast
					})
				);
			}
			if (this.props.value.effect == 'grayscale') {
				elements.push(
					this.EffectRangeControl('grayscale', {
						'label'         : __("Grayscale", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 100, 'step' : 1 }, 
						'value'			: this.props.value.grayscale
					})
				);
			}
			if (this.props.value.effect == 'hue-rotate') {
				elements.push(
					this.EffectRangeControl('hue-rotate', {
						'label'         : __("Hue rotation", 'greyd_hub')+' (°)', 
						'input_attrs'   : { 'min' : 0, 'max' : 360, 'step' : 1 }, 
						'value'			: this.props.value['hue-rotate']
					})
				);
			}
			if (this.props.value.effect == 'invert') {
				elements.push(
					this.EffectRangeControl('invert', {
						'label'         : __("Invert", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 100, 'step' : 1 }, 
						'value'			: this.props.value.invert
					})
				);
			}
			if (this.props.value.effect == 'saturate') {
				elements.push(
					this.EffectRangeControl('saturate', {
						'label'         : __("Saturation", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 200, 'step' : 1 }, 
						'value'			: this.props.value.saturate
					})
				);
			}
			if (this.props.value.effect == 'sepia') {
				elements.push(
					this.EffectRangeControl('sepia', {
						'label'         : __("Sepia", 'greyd_hub')+' (%)', 
						'input_attrs'   : { 'min' : 0, 'max' : 100, 'step' : 1 }, 
						'value'			: this.props.value.sepia
					})
				);
			}

			return el( wp.components.BaseControl, {
				className: 'greyd-options-control',
				help: this.props.help,
			}, elements );

		}
	}

	/**
	 * BodyTransformControl grouping css transform controls.
	 * 
	 * @property {string} label
	 * @property {object} value
	 * 		@property {string} transform
	 * 		@property {string} origin
	 * 		@property {string} translateX
	 * 		@property {string} translateY
	 * 		@property {number} scaleX
	 * 		@property {number} scaleY
	 * 		@property {number} rotateZ
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} BodyTransformControl component.
	 */
	this.BodyTransformControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		updateValue(transform, newValue) {
			var newValues = { ...this.props.value, [transform]: newValue };
			this.props.onChange(newValues);
		}
		render() {

			var elements = [];

			elements.push(
				el( greyd.components.OptionsControl, {
					style: { width: "100%" },
					label: this.props.label,
					value: this.props.value.transform,
					options: [
						{ value: 'none',		label: __("No transformation", 'greyd_hub') },
						{ value: 'translate',	label: __("Move", 'greyd_hub') },
						{ value: 'scale',		label: __("Scale", 'greyd_hub') },
						{ value: 'rotate',		label: __("Rotate", 'greyd_hub') },
					],
					onChange: (newValue) => {
						// console.log(newValue);
						this.updateValue('transform', newValue);
					}
				} )
			);

			if (this.props.value.transform == 'translate') {
				elements.push(
					el( wp.components.__experimentalUnitControl, {
						label: __("Horizontally", 'greyd_hub'), 
						value: _.has(this.props.value, 'translateX') ? this.props.value.translateX : "0px",
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue("translateX", newValue)
						}
					} ),
					el( wp.components.__experimentalUnitControl, {
						label: __('Vertically', 'greyd_hub'), 
						value: _.has(this.props.value, 'translateY') ? this.props.value.translateY : "0px",
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue("translateY", newValue)
						}
					} )
				);
			}

			if (this.props.value.transform == 'scale' || this.props.value.transform == 'rotate') {
				elements.push(
					el( 'label', {}, __('Origin', 'greyd_hub') ),
					el( wp.components.__experimentalAlignmentMatrixControl, {
						value: this.props.value.origin,
						onChange: (newValue) => {
							// console.log(newValue);
							this.updateValue('origin', newValue);
						}
					} )
				);

				if (this.props.value.transform == 'scale') {
					elements.push(
						el( wp.components.RangeControl, {
							label: __("Horizontally", 'greyd_hub')+' (%)', 
							value: _.has(this.props.value, 'scaleX') ? this.props.value.scaleX : 100,
							min : -200, max : 200, step : 0.1,
							onChange: (newValue) => {
								// console.log(newValue);
								this.updateValue("scaleX", newValue)
							}
						} ),
						el( wp.components.RangeControl, {
							label: __('Vertically', 'greyd_hub')+' (%)', 
							value: _.has(this.props.value, 'scaleY') ? this.props.value.scaleY : 100,
							min : -200, max : 200, step : 0.1,
							onChange: (newValue) => {
								// console.log(newValue);
								this.updateValue("scaleY", newValue)
							}
						} )
					);
				}

				if (this.props.value.transform == 'rotate') {
					elements.push(
						el( wp.components.RangeControl, {
							label: __('Angle', 'greyd_hub')+' (deg)', 
							value: _.has(this.props.value, 'rotateZ') ? this.props.value.rotateZ : 0,
							min : -180, max : 180, step : 0.1,
							onChange: (newValue) => {
								// console.log(newValue);
								this.updateValue("rotateZ", newValue)
							}
						} )
					);
				}

			}

			return el( wp.components.BaseControl, {
				// className: 'greyd-options-control',
				// help: this.props.help,
			}, elements );

		}
	}

	// console.log( "additional popup components loaded" );

};