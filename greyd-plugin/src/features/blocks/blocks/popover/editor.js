( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	greyd.states = greyd.states || {};

	/**
	 * Get the icon as React element (needed for variation display)
	 * @param {string} name Name of the icon
	 * @returns React Element
	 */
	const getIcon = ( name ) => {
		return {
			popover: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M9.18065 15.5L5.5 15.5L5.5 5.5L18.5 5.5L18.5 15.5L14.8193 15.5L12 18.7221L9.18065 15.5ZM8.5 17L5 17C4.44771 17 4 16.5523 4 16L4 5C4 4.44772 4.44772 4 5 4L19 4C19.5523 4 20 4.44772 20 5L20 16C20 16.5523 19.5523 17 19 17L15.5 17L12.7526 20.1399C12.3542 20.5952 11.6458 20.5952 11.2474 20.1399L8.5 17Z"
				} )
			] ),
			button: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					d: "M8 12.75H16V11.25H8V12.75Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M19 6.5H5C3.89543 6.5 3 7.39543 3 8.5V15.5C3 16.6046 3.89543 17.5 5 17.5H19C20.1046 17.5 21 16.6046 21 15.5V8.5C21 7.39543 20.1046 6.5 19 6.5ZM5 8H19C19.2761 8 19.5 8.22386 19.5 8.5V15.5C19.5 15.7761 19.2761 16 19 16H5C4.72386 16 4.5 15.7761 4.5 15.5V8.5C4.5 8.22386 4.72386 8 5 8Z"
				} )
			] ),
			burger: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					d: "M4 11H20V13H4V11Z"
				} ),
				el( "path", {
					d: "M4 6H20V8H4V6Z"
				} ),
				el( "path", {
					d: "M4 16H20V18H4V16Z"
				} )
			] ),
			dropdown: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M14.8193 9.5H18.5L18.5 17H5.5L5.5 9.5H9.18065L12 6.27789L14.8193 9.5ZM15.5 8H19C19.5523 8 20 8.44771 20 9L20 17.5C20 18.0523 19.5523 18.5 19 18.5L5 18.5C4.44772 18.5 4 18.0523 4 17.5L4 9C4 8.44771 4.44772 8 5 8H8.5L11.2474 4.86009C11.6458 4.40476 12.3542 4.40476 12.7526 4.86009L15.5 8Z"
				} )
			] ),
			offcanvas: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M6 18.5L18 18.5C18.2761 18.5 18.5 18.2761 18.5 18L18.5 6C18.5 5.72386 18.2761 5.5 18 5.5L6 5.5C5.72386 5.5 5.5 5.72386 5.5 6L5.5 18C5.5 18.2761 5.72386 18.5 6 18.5ZM18 20L6 20C4.89543 20 4 19.1046 4 18L4 6C4 4.89543 4.89543 4 6 4L18 4C19.1046 4 20 4.89543 20 6L20 18C20 19.1046 19.1046 20 18 20Z"
				} ),
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M14 19L14 5L15.5 5L15.5 19H14Z"
				} )
			] ),
			overlay: el( "svg", {
				width: "24",
				height: "24",
				viewBox: "0 0 24 24",
				fill: "none",
				xmlns: "http://www.w3.org/2000/svg"
			}, [
				el( "path", {
					fillRule: "evenodd",
					clipRule: "evenodd",
					d: "M18.5 5L18.5 19C18.5 19.2761 18.2761 19.5 18 19.5L15.5 19.5C15.2239 19.5 15 19.2761 15 19L15 18.5L15 5.5L15 5C15 4.72386 15.2239 4.5 15.5 4.5L18 4.5C18.2761 4.5 18.5 4.72386 18.5 5ZM13.7676 4C14.1134 3.4022 14.7597 3 15.5 3L18 3C19.1046 3 20 3.89543 20 5L20 19C20 20.1046 19.1046 21 18 21L15.5 21C14.7597 21 14.1134 20.5978 13.7676 20L13.5 20L6 20C4.89543 20 4 19.1046 4 18L4 6C4 4.89543 4.89543 4 6 4L13.5 4L13.7676 4ZM13.5 5.5L6 5.5C5.72386 5.5 5.5 5.72386 5.5 6L5.5 18C5.5 18.2761 5.72386 18.5 6 18.5L13.5 18.5L13.5 5.5Z"
				} )
			] ),
		}[ name ];
	};

	// shared helper component for behaviour controls
	const BehaviourControls = class extends wp.element.Component {
		constructor() {
			super();
		}
		render() {
			// console.log(this.props);
			var { popoverName = false, hideButton = false, openOnHover = false, isDropdown = false, setIsDropdown = false } = this.props;
			if ( popoverName && !popoverName.onChange ) popoverName.onChange = (value) => console.log("no 'popoverName.onChange' function");
			if ( hideButton && !hideButton.onChange ) hideButton.onChange = (value) => console.warn("no 'hideButton.onChange' function");
			if ( openOnHover && !openOnHover.onChange ) openOnHover.onChange = (value) => console.log("no 'openOnHover.onChange' function");
			if ( openOnHover && !setIsDropdown ) setIsDropdown = () => console.log("no 'setIsDropdown' function");
			return [
				popoverName && el( wp.components.TextControl, {
					label: __("Name of the popover", 'greyd_hub'),
					help: !popoverName.value || popoverName.value == "" ?
						__("If you enter a unique name for the popover, it can be connected to the 'Trigger' feature. That way the popover can be opened by any other block that supports trigger events.", 'greyd_hub') :
						__("The popover can be opened by any other block that supports trigger events by selecting it in the 'Trigger event' options of the 'Trigger' feature.", 'greyd_hub'),
					value: popoverName.value,
					onChange: ( value ) => popoverName.onChange(value),
				} ),
				hideButton && el( wp.components.ToggleControl, {
					label: __("Hide popover button", 'greyd_hub'),
					help: __("If enabled, the popover can only be opened by a trigger event.", 'greyd_hub'),
					checked: !!hideButton.value,
					onChange: (value) => hideButton.onChange(value),
				} ),
				( !hideButton || !hideButton.value ) && openOnHover && [
					el( wp.components.ToggleControl, {
						label: __("Open popover on hover", 'greyd_hub'),
						disabled: !isDropdown && setIsDropdown,
						checked: !isDropdown && setIsDropdown ? false : !!openOnHover.value,
						onChange: (value) => openOnHover.onChange(value),
						help: !isDropdown && setIsDropdown ?
							__("Opening the popover on hover is only supported by the 'Dropdown' variation.", 'greyd_hub') :
							undefined,
					} ),
					!isDropdown && setIsDropdown && el( wp.components.BaseControl, {}, [
						el( wp.components.Button, {
							variant: 'secondary',
							onClick: () => setIsDropdown()
						}, __("Convert to 'Dropdown'", 'greyd_hub') )
					] ),
				]
			];
		}
	};

	/**
	 * Popover main block (wrapper)
	 */
	wp.blocks.registerBlockType( 'greyd/popover', {
		title: __( 'Popover', 'greyd_hub' ),
		// description: __( "Displays a small popover at a specified location.", 'greyd_hub' ),
		icon: getIcon( 'popover' ),
		category: 'greyd-blocks',
		keywords: [ 'trigger', 'toggle', 'popup', 'popover', 'dropdown' ],
		supports: {
			anchor: true
		},
		attributes: {
			popoverName: { type: 'string', default: '' },
			hidden: { type: 'object', default: { xs: false, sm: false, md: false, lg: false } },
			hideButton: { type: 'bool', default: false },
			openOnHover: { type: 'bool', default: false },

			anchor: { type: 'string', default: '' },
			isNavpoint: { type: 'bool', default: false },
		},

		variations: [
			{
				name: 'popover',
				title: __( 'Popover', 'greyd_hub' ),
				icon: getIcon( 'popover' ),
				scope: [ 'inserter' ],
				isDefault: true,
				innerBlocks: [
					[ 'greyd/popover-button', { variation: '' } ],
					[ 'greyd/popover-popup', { variation: '' } ],
				]
			},
			{
				name: 'popover-burger-menu',
				title: __( "Burger menu", 'greyd_hub' ),
				icon: getIcon( 'burger' ),
				scope: [ 'inserter' ],
				innerBlocks: [
					[ 'greyd/popover-button', { variation: 'burger' } ],
					[ 'greyd/popover-popup', { variation: 'offcanvas' } ],
				]
			},
			{
				name: 'popover-dropdown-menu',
				title: __( "Dropdown menu", 'greyd_hub' ),
				icon: getIcon( 'dropdown' ),
				scope: [ 'inserter' ],
				innerBlocks: [
					[ 'greyd/popover-button', { variation: 'button' } ],
					[ 'greyd/popover-popup', { variation: 'dropdown' } ],
				]
			},
		],
		providesContext: {
			'greyd/popover/popoverName': 'popoverName',
			'greyd/popover/hideButton': 'hideButton',
			'greyd/popover/openOnHover': 'openOnHover'
		},
		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				className,
				setAttributes,
				attributes: atts
			} = props;

			const breakpoints = { xs: 'mobile', sm: 'tablet', md: 'laptop', lg: 'desktop' };

			if ( props.isSelectionEnabled ) {
				// check if parent is core/navigation and set isNav
				const parentId = wp.data.select("core/block-editor").getBlockParentsByBlockName(props.clientId, "core/navigation");
				const parent = wp.data.select("core/block-editor").getBlocksByClientId(parentId)[0];
				const isNavpoint = parent !== undefined ? true : false;
		
				if ( isNavpoint !== atts.isNavpoint ) {
					setAttributes({ isNavpoint: isNavpoint });
				}
			}

			// get other popover elements
			var children = wp.data.select( 'core/block-editor' ).getBlockOrder(props.clientId);
			var [ buttonBlock, popupBlock ] = wp.data.select('core/block-editor').getBlocksByClientId(children);
			var [ isDropdown, setIsDropdown ] = wp.element.useState( popupBlock.attributes.variation == 'dropdown' );
			if ( isDropdown != (popupBlock.attributes.variation == 'dropdown') ) setIsDropdown(!isDropdown);
			// console.log( buttonBlock, popupBlock, isDropdown );
		
			return [

				//  sidebar
				el( wp.blockEditor.InspectorControls, {}, [

					el( greyd.components.AdvancedPanelBody, {
						// title: __("Show", 'greyd_hub'),
						title: __("Settings", 'greyd_hub'),
						initialOpen: true,
						// holdsChange: atts.hidden.xs || atts.hidden.sm || atts.hidden.md || atts.hidden.lg
					}, [
						el( BehaviourControls, {
							popoverName: {
								value: atts.popoverName,
								onChange: ( value ) => {
									setAttributes( { popoverName: value } );
								}
							},
							hideButton: {
								value: atts.hideButton,
								onChange: ( value ) => {
									setAttributes( { hideButton: !!value } );
								}
							},
							openOnHover: {
								value: atts.openOnHover,
								onChange: ( value ) => {
									setAttributes( { openOnHover: !!value } );
								}
							},
							isDropdown: isDropdown,
							setIsDropdown: () => {
								wp.data.dispatch('core/block-editor').updateBlockAttributes( popupBlock.clientId, { 
									variation: 'dropdown'
								} );
								setIsDropdown(true);
							}
						} ),
						el( wp.components.BaseControl, {
							label: __("Show", 'greyd_hub'),
							help: __("If activated, the popup is shown on the respective breakpoint.", 'greyd_hub')
						}, [
							el( 'div', { className: 'greyd-inspector-wrapper greyd-icons-inline' }, [
								...Object.keys( breakpoints ).map( key => {
									return el( 'div', { className: 'greyd-icon-flex '+key }, [
										el( greyd.components.GreydIcon, {
											icon: breakpoints[ key ],
											title: greyd.tools.makeBreakpointTitle( key ) }
										),
										el( wp.components.ToggleControl, {
											checked: !atts.hidden[ key ],
											onChange: ( val ) => {
												setAttributes({ hidden: { ...atts.hidden, [key]: !val } })
											},
										} ),
									] )
								} )
							] ),
						] )
					] )
				] ),

				// toolbar
				el( wp.blockEditor.BlockControls, {
					// group: 'block'
				}, [
					el( wp.components.ToolbarGroup, {}, [
						el( wp.components.ToolbarButton, {
							// as: wp.components.ToolbarButton,
							icon: 'visibility',
							text: __( "Show popover", 'greyd_hub' ),
							onClick: () => {
								// select popup block
								wp.data.dispatch( 'core/block-editor' ).selectBlock( popupBlock.clientId );
							}
						} )
					] ),
				] ),

				// preview
				el( 'div', {
					className: [
						className,
						atts.greydClass,
						...Object.keys( props.attributes.hidden ).map( key => {
							return props.attributes.hidden[ key ] ? 'hidden-'+key : ''
						} )
					].join( ' ' )
				}, [
					el( wp.blockEditor.InnerBlocks, {
						allowedBlocks: [ 'greyd/popover-button', 'greyd/popover-popup' ],
						template: [
							[ 'greyd/popover-button', {} ],
							[ 'greyd/popover-popup', {} ],
						],
						renderAppender: null,
						templateLock: 'all'
					} )
				] )
			];
		},

		save: function ( props ) {
			return el( wp.blockEditor.InnerBlocks.Content );
		},

		deprecated: [
			/**
			 * @since 2.5.0 Move rendering to PHP render_callback
			 */
			{
				supports: {
					anchor: true
				},
				attributes: {
					hidden: { type: 'object', default: { xs: false, sm: false, md: false, lg: false } },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {
					return el( 'div', {
						className: Object.keys( props.attributes.hidden ).map( key => {
							return props.attributes.hidden[ key ] ? 'hidden-'+key : ''
						} ).join(' ')
					}, el( wp.blockEditor.InnerBlocks.Content ) );
				}
			},
			{
				supports: {
					anchor: true,
					align: true
				},
				styles: [
					{
						name: 'has-button-prim',
						label: __( 'Primary', 'greyd_hub' ),
						isDefault: true
					},
					{
						name: 'has-button-sec',
						label: __( 'Secondary', 'greyd_hub' )
					},
					{
						name: 'has-button-trd',
						label: __( 'Alternative', 'greyd_hub' )
					},
					{
						name: 'has-link-prim',
						label: __( "Link", 'greyd_hub' )
					},
					{
						name: 'has-link-sec',
						label: __( "Secondary link", 'greyd_hub' )
					},
					{
						name: 'has-clear',
						label: __( 'Text', 'greyd_hub' )
					}
				],
				attributes: {
					button: {
						type: 'object', default: {
							content: '',
							style: 'button is-style-prim',
							size: '',
							icon: {
								content: 'arrow_right-up_alt',
								position: 'after',
								size: '100%',
								margin: '10px'
							},
							custom: false
						}
					},
					popoverClassName: { type: 'string', default: '' },
					closeButton: { type: 'string', default: '' }, // add class to close-button: 'outside' | 'hidden'

					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					popoverStyles: { type: 'object', default: {} },
					buttonStyles: { type: 'object', default: {} },
				},
				migrate: function ( attributes, innerBlocks ) {
					
					console.log( 'migrate popover:', attributes, innerBlocks );

					const newAtts = {
						hidden: { xs: false, sm: false, md: false, lg: false }
					};
					const newInnerBlocks = [
						wp.blocks.createBlock( 'greyd/popover-button', {
							variation: '',
							content: _.has( attributes.button, 'content' ) ? attributes.button.content : '',
							icon: _.has( attributes.button, 'icon' ) ? attributes.button.icon : {
								content: '',
								position: 'after',
								size: '100%',
								margin: '10px'
							},
							size: _.has( attributes.button, 'size' ) ? attributes.button.size : '',
							buttonStyle: _.has( attributes.button, 'style' ) ? {
								'button is-style-prim': 'button-prim',
								'button is-style-sec': 'button-sec',
								'button is-style-trd': 'button-trd',
								'is-style-link-prim': 'link-prim',
								'is-style-link-sec': 'link-sec',
								'is-style-clear': 'clear',
							}[ attributes.button.style ] : '',
							custom: _.has( attributes.button, 'custom' ) ? attributes.button.custom : false,
							greydStyles: _.has( attributes, 'greydStyles' ) ? attributes.greydStyles : {},
							customStyles: _.has( attributes, 'buttonStyles' ) ? attributes.buttonStyles : {},
						} ),
						wp.blocks.createBlock( 'greyd/popover-popup', {
							variation: attributes.popoverClassName === 'is-style-dropdown' ? 'popover-dropdown' : '',
							position: _.isEmpty( attributes.popoverClassName ) ? 'center center' : {
								'is-style-dialog-default': 'center center',
								'is-style-dialog-bottom': 'bottom center',
								'is-style-dropdown': 'bottom',
								'is-style-banner-right': 'bottom right',
								'is-style-banner-left': 'bottom left',
								'is-style-notice-top': 'top center',
								'is-style-notice-bottom': 'bottom center',
							}[ attributes.popoverClassName ],
							closeButton: _.has( attributes, 'closeButton' ) && attributes.closeButton.length ? 'is-'+attributes.closeButton : '',
							greydStyles: _.has( attributes, 'popoverStyles' ) ? attributes.popoverStyles : '',
						}, [
							...innerBlocks
						] )
					];
					
					console.log( 'popover migrated:', newInnerBlocks );
					
					return [ newAtts, newInnerBlocks ];
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
		
					const ID = 'popover-ID';
		
					return el( 'div', {
						className: [ atts.greydClass, atts.popoverClassName ].join(' ')
					}, [
		
						// button
						el( 'button', {
							type: 'button',
							className: [ atts.button.style, atts.button.size ].join(' '),
							onclick: "openDialog('" + ID + "', this)",
							'aria-label': __( "Open dialog", 'greyd_hub' ),
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: atts.button.icon,
								position: 'before'
							} ),
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.button.content,
								style: { flex: '1' },
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: atts.button.icon,
								position: 'after'
							} ),
						] ),
		
						// popover
						el( 'div', {
							role: 'dialog',
							id: ID,
							ariaModal: true,
							'aria-label': __( 'Dialog', 'greyd_hub' ),
							className: _.has( atts.popoverStyles, '--dialog-color' ) ? 'has-text-color' : ''
						}, [
							el( 'button', {
								type: 'button',
								className: 'popover-close-button ' + atts.closeButton,
								onclick: 'closeDialog(this)',
								'aria-label': __( "Close dialog", 'greyd_hub' )
							} ),
							el( wp.blockEditor.InnerBlocks.Content, {} )
						] ),
						el( 'div', {
							className: 'dialog-backdrop',
							onclick: 'closeDialog(this)'
						} ),
		
						// styles
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass,
							styles: {
								"": atts.popoverStyles,
								" > button": atts.buttonStyles
							},
							important: true
						} ),
					] );
				}
			}
		]
	} );

	/**
	 * Button
	 */
	wp.blocks.registerBlockType( 'greyd/popover-button', {
		apiVersion: 2,
		title: __( "Popover button", 'greyd_hub' ),
		icon: getIcon( 'button' ),
		category: 'greyd-blocks',
		parent: [ 'greyd/popover' ],
		supports: {
			inserter: false,
			lock: false,
			reusable: false,
			anchor: true,
			align: true,
			typography: false,
			ariaLabel: true
		},
		attributes: {
			anchor: { type: 'string', default: '' },
			variation: { type: 'string', default: '' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
			ariaLabel: { type: 'string', default: '' },

			// button
			content: { type: 'string', default: '' },
			buttonStyle: { type: 'string', default: 'button-prim' },
			icon: {
				type: 'object',
				properties: {
					content: { type: "string" },
					position: { type: "string" },
					size: { type: "string" },
					margin: { type: "string" },
				}, default: {
					content: '',
					position: 'after',
					size: '100%',
					margin: '10px'
				}
			},
			size: { type: 'string', default: '' },
			custom: { type: 'bool', default: 0 },
			customStyles: { type: 'object' },

			// burger
			animation: { type: 'string', default: 'squeeze' },
			shape: { type: 'string', default: '' },
			burgerStyles: { type: 'object', default: {} },
		},
		variations: [
			{
				name: 'popover-button',
				title: __( "Popover button", 'greyd_hub' ),
				icon: getIcon( 'button' ),
				scope: [ 'transform' ],
				isDefault: true,
				attributes: { variation: '' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			{
				name: 'popover-burger',
				title: __( 'Burger', 'greyd_hub' ),
				icon: getIcon( 'burger' ),
				scope: [ 'transform' ],
				attributes: { variation: 'burger' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			}
		],
		usesContext: [
			'greyd/popover/popoverName',
			'greyd/popover/hideButton',
			'greyd/popover/openOnHover'
		],
		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// if inside a template and text is non-dynamic,
			// directly open the popover-popup to enable editing
			if (
				props.isSelected &&
				_.has(props.attributes, 'dynamic_parent') &&
				!_.isEmpty(props.attributes.dynamic_parent) && (
					!_.has(props.attributes, 'dynamic_fields') || (
						_.has(props.attributes, 'dynamic_fields') &&
						_.isEmpty(props.attributes.dynamic_fields)
					)
				)
			) {
				// console.log(props);
				wp.data.dispatch( 'core/block-editor' ).selectNextBlock( props.clientId );
			}

			const {
				className,
				setAttributes,
				attributes: atts
			} = props;

			const [ isActive, setActive ] = wp.element.useState( false );

			const defaultControls = [
				// toolbar button 'show popup'
				el( wp.blockEditor.BlockControls, {}, [
					el( wp.components.ToolbarGroup, {}, [
						el( wp.components.ToolbarButton, {
							// as: wp.components.ToolbarButton,
							icon: 'visibility',
							text: __( "Show popover", 'greyd_hub' ),
							onClick: () => {
								wp.data.dispatch( 'core/block-editor' ).selectNextBlock( props.clientId );
							}
						} )
					] ),
				] ),
				// aria-label
				el( wp.blockEditor.InspectorAdvancedControls, {}, [
					el( wp.components.TextControl, {
						label: __( 'Aria label', 'greyd_hub' ),
						value: atts.ariaLabel,
						onChange: val => setAttributes({ ariaLabel: val })
					} )
				] )
			];

			// get other popover elements
			var parentBlock = greyd.tools.isChildOf(props.clientId, 'greyd/popover');
			var nextBlock = wp.data.select( 'core/block-editor' ).getNextBlockClientId(props.clientId);
			var [ popupBlock ] = wp.data.select('core/block-editor').getBlocksByClientId(nextBlock);
			var [ isDropdown, setIsDropdown ] = wp.element.useState( popupBlock.attributes.variation == 'dropdown' );
			if ( isDropdown != (popupBlock.attributes.variation == 'dropdown') ) setIsDropdown(!isDropdown);
			// console.log( parentBlock, popupBlock, isDropdown );

			const behaviourPanel = [
				el( greyd.components.AdvancedPanelBody, {
					title: __( "Behaviour", 'greyd_hub' ),
					initialOpen: false
				}, [
					el( BehaviourControls, {
						popoverName: {
							value: props.context[ 'greyd/popover/popoverName' ],
							onChange: ( value ) => {
								wp.data.dispatch('core/block-editor').updateBlockAttributes( parentBlock.clientId, { 
									popoverName: value
								} );
							}
						},
						hideButton: {
							value: props.context[ 'greyd/popover/hideButton' ],
							onChange: ( value ) => {
								wp.data.dispatch('core/block-editor').updateBlockAttributes( parentBlock.clientId, { 
									hideButton: !!value
								} );
							}
						},
						openOnHover: {
							value: props.context[ 'greyd/popover/openOnHover' ],
							onChange: ( value ) => {
								wp.data.dispatch('core/block-editor').updateBlockAttributes( parentBlock.clientId, { 
									openOnHover: !!value
								} );
							}
						},
						isDropdown: isDropdown,
						setIsDropdown: () => {
							wp.data.dispatch('core/block-editor').updateBlockAttributes( popupBlock.clientId, { 
								variation: 'dropdown'
							} );
							setIsDropdown(true);
						}
					} ),
				] )
			]
			
			let extraClass = '';
			if ( atts.buttonStyle.indexOf('link-') !== -1 ) {
				extraClass = 'link'
			}
			else if ( atts.buttonStyle.indexOf('button-') !== -1 ) {
				extraClass = 'button'
			}
			// console.log( atts.buttonStyle, extraClass );

			var classNames = atts.variation === 'burger' ? [
				'greyd-burger-btn',
				props.attributes.className,
				atts.greydClass,
			] : [
				'is-style-' + atts.buttonStyle.replace('button-', ''),
				extraClass,
				props.attributes.className,
				atts.greydClass,
				'is-size-'+atts.size
			];
			if ( props.context[ 'greyd/popover/hideButton' ] === true ) {
				classNames.push( 'hidden-lg hidden-md hidden-sm hidden-xs' )
			}
			const blockProps = wp.blockEditor.useBlockProps({ 
				id: atts.anchor,
				className: classNames.join(' ')
			});

			// render burger
			if ( atts.variation === 'burger' ) {

				return [
					...defaultControls,

					//  sidebar
					el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
						el( greyd.components.AdvancedPanelBody, {
							title: __( "Shape", 'greyd_hub' ),
							holdsChange: !_.isEmpty(atts.shape) || atts.animation !== 'squeeze',
							initialOpen: true
						}, [
							el( greyd.components.ButtonGroupControl, {
								// label: __( "Shape", 'greyd_hub' ),
								value: atts.shape,
								initialOpen: true,
								options: [
									{
										label: __( "Default", 'greyd_hub' ),
										value: '',
										isDefault: true
									},
									{
										label: __( 'E', 'greyd_hub' ),
										value: 'shape-e'
									},
									{
										label: __( "E reversed", 'greyd_hub' ),
										value: 'shape-e-reverse'
									},
									{
										label: __( 'F', 'greyd_hub' ),
										value: 'shape-f'
									},
									{
										label: __( "F reversed", 'greyd_hub' ),
										value: 'shape-f-reverse'
									},
									{
										label: __( 'Kebab', 'greyd_hub' ),
										value: 'shape-kebab'
									},
									{
										label: __( "Two lines", 'greyd_hub' ),
										value: 'shape-equal'
									}
								],
								onChange: val => {
									setActive( false )
									setAttributes({ shape: val })
								},
							} ),
							el( greyd.components.BlockStyleControl, {
								// label: __( 'Animation', 'greyd_hub' ),
								value: atts.animation,
								options: [
									{
										label: __( "Unfold", 'greyd_hub' ),
										value: 'squeeze',
										isDefault: true
									},
									{
										label: __( "Rotate", 'greyd_hub' ),
										value: 'spin'
									},
									{
										label: __( "Fold", 'greyd_hub' ),
										value: 'collapse'
									},
									{
										label: __( "Elastic", 'greyd_hub' ),
										value: 'elastic'
									},
									{
										label: __( "Spring", 'greyd_hub' ),
										value: 'spring'
									},
									{
										label: __( "Without", 'greyd_hub' ),
										value: 'boring'
									}
								],
								onClick: () => setActive( false ),
								onChange: val => {
									setActive( true );
									setAttributes({ animation: val })
								},
							} ),
						] ),
						...behaviourPanel
					] ),
					el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
						el( greyd.components.StylingControlPanel, {
							title: __("Colors", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							blockProps: props,
							parentAttr: 'burgerStyles',
							controls: [
								{
									label: __( 'Burger', 'greyd_hub' ),
									attribute: "--burger-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color',
									preventConvertGradient: true
								},
								{
									label: __( 'Button', 'greyd_hub' ),
									attribute: "--button-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color'
								},
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __('Button', 'greyd_hub'),
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'burgerStyles',
							controls: [
								{
									label: __("Button size", 'greyd_hub'),
									attribute: "--button-size",
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Border radius", 'greyd_hub'),
									attribute: "--button-radius",
									control: greyd.components.DimensionControl,
									type: 'string',
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Dimensions", 'greyd_hub'),
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'burgerStyles',
							controls: [
								{
									label: __("Width", 'greyd_hub'),
									attribute: "--burger-width",
									control: greyd.components.RangeUnitControl,
									max: 100
								},
								{
									label: __("Line weight", 'greyd_hub'),
									attribute: "--burger-stroke",
									control: greyd.components.RangeUnitControl,
									max: 10
								},
								{
									label: __("Spaces", 'greyd_hub'),
									attribute: "--burger-gap",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
									max: 50
								}
							]
						} ),
					] ),

					// preview
					el( 'button', {
						// className: "greyd-burger-btn " + atts.greydClass,
						...blockProps,
						...( props.isSelected ? {
							onClick: () => setActive( !isActive )
						} : {} )
						
					}, [
						el( 'span', {
							className: [
								"greyd-burger",
								"greyd-burger--" + atts.animation,
								(isActive ? 'is-active' : ''),
								atts.shape
							].join(" ")
						}, [
							el( 'span', {
								className: 'greyd-burger-inner'
							} )
						] ),
					] ),

					// styles
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass,
						styles: {
							"": atts.burgerStyles,
						}
					} )
				];
			}
			
			// render default: button
			return [
				...defaultControls,

				// sidebar
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					// icon
					el( greyd.components.ButtonIconControl, {
						enableHideEmpty: true,
						value: atts.icon,
						onChange: function(value) {
							props.setAttributes({ icon: value });
						},
					} ),
					...behaviourPanel
				] ),
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [

					el( greyd.components.AdvancedPanelBody, {
						title: __( "Style", 'greyd_hub' ),
						holdsChange: atts.animation !== 'squeeze',
						initialOpen: true
					}, [
						el( greyd.components.BlockStyleControl, {
							value: atts.buttonStyle,
							options: [
								{
									value: 'button-prim',
									label: __( 'Primary', 'greyd_hub' ),
									isDefault: true
								},
								{
									value: 'button-sec',
									label: __( 'Secondary', 'greyd_hub' )
								},
								{
									value: 'button-trd',
									label: __( 'Alternative', 'greyd_hub' )
								},
								{
									value: 'link-prim',
									label: __( "Link", 'greyd_hub' )
								},
								{
									value: 'link-sec',
									label: __( "Secondary link", 'greyd_hub' )
								},
								// {
								// 	value: 'clear',
								// 	label: __( 'Text', 'greyd_hub' )
								// }
							],
							onChange: val => setAttributes({ buttonStyle: val })
						} )
					] ),

					// size
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Size", 'greyd_hub' ),
						holdsChange: !_.isEmpty(atts.size)
					}, [
						el( greyd.components.ButtonGroupControl, {
							value: atts.size,
							// label: __( "Size", 'greyd_hub' ),
							options: [
								{ value: "small", label: __( "Small", 'greyd_hub' ) },
								{ value: "", label: __( "Default", 'greyd_hub' ) },
								{ value: "big", label: __( "Big", 'greyd_hub' ) },
							],
							onChange: function(value) {
								props.setAttributes( { size: value } );
							},
						} ),
					] ),
					// width
					el( greyd.components.StylingControlPanel, {
						title: __("Width", 'greyd_hub'),
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Width", 'greyd_hub'),
								attribute: "width",
								control: greyd.components.RangeUnitControl,
								max: 500
							}
						]
					} ),

					// custom button
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Individual button", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: atts.custom ? true : false
					}, [
							el( wp.components.ToggleControl, {
								label: __( "Overwrite the design of the button individually", 'greyd_hub' ),
								checked: atts.custom,
								onChange: function(value) {
									props.setAttributes( { custom: !!value } );
								},
							} ),
						]
					),
					
					el( greyd.components.CustomButtonStyles, {
						enabled: atts.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} )
				] ),

				// preview
				el( 'div', { ...blockProps }, [
					el( greyd.components.RenderButtonIcon, {
						value: atts.icon,
						position: 'before'
					} ),
					el( wp.blockEditor.RichText, {
						format: 'string',
						tagName: 'span',
						// style: { flex: '1' },
						value: atts.content,
						placeholder: __( 'Button', 'greyd_hub' ),
						allowedFormats: [ 'core/bold', 'core/italic', 'core/strikethrough', 'greyd/dtag', 'core/highlight' ],
						onChange: function(value) {
							props.setAttributes( { content: value } );
						},
					} ),
					el( greyd.components.RenderButtonIcon, {
						value: atts.icon,
						position: 'after'
					} ),
				] ),
				// normal styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						"": atts.greydStyles,
					}
				} ),
				// custom styles
				!atts.custom ? null : el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						"": atts.customStyles,
					},
					important: true
				} )
			];
		},

		save: function ( props ) {

			if ( props.attributes?.variation === 'burger') {
				return null;
			}
			else {
				return el( wp.element.Fragment, {}, [
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes?.icon,
						position: 'before'
					} ),
					el( wp.blockEditor.RichText.Content, {
						tagName: 'span',
						value: props.attributes?.content
					} ),
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes?.icon,
						position: 'after'
					} )
				] );
			}
		},

		deprecated: [
			/**
			 * @since 2.5.0 Move rendering to PHP render_callback
			 */
			{
				supports: {
					inserter: false,
					lock: false,
					reusable: false,
					anchor: true,
					align: true,
					typography: false,
					ariaLabel: true
				},
				attributes: {
					variation: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					ariaLabel: { type: 'string', default: '' },
		
					// button
					content: { type: 'string', default: '' },
					buttonStyle: { type: 'string', default: 'button-prim' },
					icon: {
						type: 'object',
						properties: {
							content: { type: "string" },
							position: { type: "string" },
							size: { type: "string" },
							margin: { type: "string" },
						}, default: {
							content: '',
							position: 'after',
							size: '100%',
							margin: '10px'
						}
					},
					size: { type: 'string', default: '' },
					custom: { type: 'bool', default: 0 },
					customStyles: { type: 'object' },
		
					// burger
					animation: { type: 'string', default: 'squeeze' },
					shape: { type: 'string', default: '' },
					burgerStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
		
					const blockProps = wp.blockEditor.useBlockProps.save();
		
					if ( atts.variation === 'burger' ) {
						return el( wp.element.Fragment, {}, [
							el( 'button', {
								id: blockProps.id,
								className: [ blockProps.className, "greyd-burger-btn", atts.greydClass ].join(' '),
								tabindex: "0",
								role: "button",
								"aria-expanded": "false",
								"aria-label": atts.ariaLabel,
								"aria-controls": "popover-ID"
							}, [
								el( 'span', {
									className: [
										"greyd-burger",
										"greyd-burger--" + atts.animation,
										atts.shape
									].join(" ")
								}, [
									el( 'span', {
										className: 'greyd-burger-inner'
									} )
								] ),
							] ),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									"": atts.burgerStyles,
								}
							} ),
						] );
					}
					
					// render default: button
					let extraClass = '';
					if ( atts.buttonStyle.indexOf('link-') !== -1 ) {
						extraClass = 'link'
					}
					else if ( atts.buttonStyle.indexOf('button-') !== -1 ) {
						extraClass = 'button'
					}
					// console.log( atts.buttonStyle, extraClass );
		
					const classNames = [
						'is-style-' + atts.buttonStyle.replace('button-', ''),
						extraClass,
						blockProps.className,
						atts.greydClass,
						'is-size-'+atts.size // todo: deprecation
					].join(' ');
		
					return el( wp.element.Fragment, {}, [
						el( 'button', {
							id: blockProps.id,
							className: classNames,
							tabindex: "0",
							role: "button",
							"aria-expanded": "false",
							"aria-label": atts.ariaLabel,
							"aria-controls": "popover-ID"
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'before'
							} ),
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.content
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'after'
							} ),
						] ),
						!atts.custom ? null : el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass,
							styles: {
								"": atts.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			{
				supports: {
					inserter: false,
					lock: false,
					reusable: false,
					anchor: true,
					align: true,
					typography: false,
					ariaLabel: true
				},
				attributes: {
					variation: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'string', default: {} },
					ariaLabel: { type: 'string', default: '' },
		
					// button
					content: { type: 'string', default: '' },
					buttonStyle: { type: 'string', default: 'button-prim' },
					icon: {
						type: 'object',
						properties: {
							content: { type: "string" },
							position: { type: "string" },
							size: { type: "string" },
							margin: { type: "string" },
						}, default: {
							content: '',
							position: 'after',
							size: '100%',
							margin: '10px'
						}
					},
					size: { type: 'string', default: '' },
					custom: { type: 'bool', default: 0 },
					customStyles: { type: 'object' },
		
					// burger
					animation: { type: 'string', default: 'squeeze' },
					shape: { type: 'string', default: '' },
					burgerStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
		
					if ( atts.variation === 'burger' ) {
						return el( wp.element.Fragment, {}, [
							el( 'button', {
								id: atts.anchor,
								className: "greyd-burger-btn " + atts.greydClass,
								tabindex: "0",
								role: "button",
								"aria-expanded": "false",
								"aria-label": atts.ariaLabel,
								"aria-controls": "popover-ID"
							}, [
								el( 'span', {
									className: [
										"greyd-burger",
										"greyd-burger--" + atts.animation,
										atts.shape
									].join(" ")
								}, [
									el( 'span', {
										className: 'greyd-burger-inner'
									} )
								] ),
							] ),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									"": atts.burgerStyles,
								}
							} ),
						] );
					}
					
					// render default: button
					let extraClass = '';
					if ( atts.buttonStyle.indexOf('link-') !== -1 ) {
						extraClass = 'link'
					}
					else if ( atts.buttonStyle.indexOf('button-') !== -1 ) {
						extraClass = 'button'
					}
					// console.log( atts.buttonStyle, extraClass );
		
					const classNames = [
						'is-style-' + atts.buttonStyle.replace('button-', ''),
						extraClass,
						props.className,
						atts.greydClass,
						atts.size
					].join(' ');
		
					return el( wp.element.Fragment, {}, [
						el( 'button', {
							id: atts.anchor,
							className: classNames,
							tabindex: "0",
							role: "button",
							"aria-expanded": "false",
							"aria-label": atts.ariaLabel,
							"aria-controls": "popover-ID"
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'before'
							} ),
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.content
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'after'
							} ),
						] ),
						!atts.custom ? null : el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass,
							styles: {
								"": atts.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			{
				// fix button size class
				attributes: {
					variation: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'string', default: {} },
					ariaLabel: { type: 'string', default: '' },

					// button
					content: { type: 'string', default: '' },
					buttonStyle: { type: 'string', default: 'button-prim' },
					icon: {
						type: 'object',
						properties: {
							content: { type: "string" },
							position: { type: "string" },
							size: { type: "string" },
							margin: { type: "string" },
						}, default: {
							content: '',
							position: 'after',
							size: '100%',
							margin: '10px'
						}
					},
					size: { type: 'string', default: '' },
					custom: { type: 'bool', default: 0 },
					customStyles: { type: 'object' },

					// burger
					animation: { type: 'string', default: 'squeeze' },
					shape: { type: 'string', default: '' },
					burgerStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {
		
					const {
						attributes: atts
					} = props;
		
					const blockProps = wp.blockEditor.useBlockProps.save();
		
					if ( atts.variation === 'burger' ) {
						return el( wp.element.Fragment, {}, [
							el( 'button', {
								id: blockProps.id,
								className: [ blockProps.className, "greyd-burger-btn", atts.greydClass ].join(' '),
								tabindex: "0",
								role: "button",
								"aria-expanded": "false",
								"aria-label": atts.ariaLabel,
								"aria-controls": "popover-ID"
							}, [
								el( 'span', {
									className: [
										"greyd-burger",
										"greyd-burger--" + atts.animation,
										atts.shape
									].join(" ")
								}, [
									el( 'span', {
										className: 'greyd-burger-inner'
									} )
								] ),
							] ),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									"": atts.burgerStyles,
								}
							} ),
						] );
					}
					
					// render default: button
					let extraClass = '';
					if ( atts.buttonStyle.indexOf('link-') !== -1 ) {
						extraClass = 'link'
					}
					else if ( atts.buttonStyle.indexOf('button-') !== -1 ) {
						extraClass = 'button'
					}
					// console.log( atts.buttonStyle, extraClass );
		
					const classNames = [
						'is-style-' + atts.buttonStyle.replace('button-', ''),
						extraClass,
						blockProps.className,
						atts.greydClass,
						atts.size // deprecation: prefix 'is-size-'
					].join(' ');
		
					return el( wp.element.Fragment, {}, [
						el( 'button', {
							id: blockProps.id,
							className: classNames,
							tabindex: "0",
							role: "button",
							"aria-expanded": "false",
							"aria-label": atts.ariaLabel,
							"aria-controls": "popover-ID"
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'before'
							} ),
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.content
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: atts.icon,
								position: 'after'
							} ),
						] ),
						!atts.custom ? null : el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass,
							styles: {
								"": atts.customStyles,
							},
							important: true
						} )
					] );
				}
			}
		]
	} );

	/**
	 * Popup
	 */
	wp.blocks.registerBlockType( 'greyd/popover-popup', {
		title: __( 'Pop-up', 'greyd_hub' ),
		// description: __( "Displays a small popover at a specified location.", 'greyd_hub' ),
		icon: getIcon( 'popover' ),
		category: 'greyd-blocks',
		keywords: [ 'trigger', 'toggle', 'popup', 'popover', 'dropdown', 'pop-up' ],
		supports: {
			inserter: false,
			lock: false,
			reusable: false,
			anchor: true,
			ariaLabel: true,
		},
		attributes: {
			anchor: { type: 'string', default: '' },
			variation: { type: 'string', default: '' },
			position: { type: 'string', default: '' },
			closeButton: { type: 'string', default: '' }, // add class to close-button: 'outside' | 'hidden'
			closeButtonAriaLabel: { type: 'string', default: '' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
		},
		variations: [
			{
				name: 'popover-popup',
				title: __( 'Pop-up', 'greyd_hub' ),
				icon: getIcon( 'popover' ),
				scope: [ 'transform' ],
				isDefault: true,
				attributes: {
					variation: ''
				},
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			{
				name: 'popover-offcanvas',
				title: __( 'Offcanvas', 'greyd_hub' ),
				icon: getIcon( 'offcanvas' ),
				scope: [ 'transform' ],
				attributes: {
					variation: 'offcanvas'
				},
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			{
				name: 'popover-overlay',
				title: __( 'Overlay', 'greyd_hub' ),
				icon: getIcon( 'overlay' ),
				scope: [ 'transform' ],
				attributes: {
					variation: 'overlay'
				},
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			{
				name: 'popover-dropdown',
				title: __( "Dropdown", 'greyd_hub' ),
				icon: getIcon( 'dropdown' ),
				scope: [ 'transform' ],
				attributes: {
					variation: 'dropdown'
				},
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			}
		],
		usesContext: [
			'greyd/popover/popoverName',
			'greyd/popover/hideButton',
			'greyd/popover/openOnHover'
		],
		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				className,
				setAttributes,
				attributes: atts
			} = props;

			const hasChildBlocks = greyd.tools.hasChildBlocks( props.clientId );

			const getElement = ( query ) => {
				var element = document.querySelector( query );
				if ( !element ) {
					var iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
					if ( iframe?.contentWindow ) {
						element = iframe.contentWindow.document.querySelector( query );
					}
				}
				return element;
			}

			const addOffcanvas = (body) => {
				// console.log(body);
				if ( !body?.classList ) return;

				var current = 'is-position-'+(_.isEmpty(atts.position) ? 'default' : atts.position);
				if ( !body.classList.contains('is-offcanvas') ) {
					// console.log("set offcanvas class");
					body.classList.add('is-offcanvas');
				}
				if ( !body.classList.contains(current) ) {
					// console.log("set classes");
					// console.log(current);
					body.classList.forEach( (className) => {
						if ( className.indexOf('is-position-') == 0 ) {
							body.classList.remove(className);
						}
					} );
					body.classList.add(current);
				}
			}
			const removeOffcanvas = (body) => {
				// console.log(body);
				if ( !body ) {
					body = document.querySelector( '.edit-post-visual-editor' );
					if ( !body ) body = document.querySelector( '.edit-site-visual-editor' );
				}
				if ( !body?.classList ) return;
				
				body.classList.remove('is-offcanvas');
				body.classList.forEach( (className) => {
					if ( className.indexOf('is-position-') == 0 ) {
						body.classList.remove(className);
					}
				} );
			}

			// re-calculate position when variation has changed
			if ( atts.variation === '' ) {
				// popup
				if ( atts.position && atts.position.indexOf(' ') == -1 ) {
					var position = 'center center';
					if ( atts.position == 'top' || atts.position == 'bottom' ) position = atts.position+' center';
					else if ( atts.position == 'left' ) position = 'center '+atts.position;
					else if ( atts.position == '' ) position = 'center right';
					// else atts.position = 'center center';
					atts.position = position;
					setAttributes({ position: position });
				}
			}
			else {
				// other
				if ( atts.position && atts.position.indexOf(' ') > -1 ) {
					var position = '';
					if ( atts.position.indexOf('left') > -1 ) position = 'left';
					else if ( atts.position.indexOf('top') > -1 ) position = 'top';
					else if ( atts.position.indexOf('bottom') > -1 ) position = 'bottom';
					// else atts.position = 'right';
					atts.position = position;
					setAttributes({ position: position });
				}
			}
			// const AlignmentMatrix = [
			// 	[ 'top left', 'top center', 'top right' ],
			// 	[ 'center left', 'center center', 'center right' ],
			// 	[ 'bottom left', 'bottom center', 'bottom right' ],
			// ];

			// global block states
			if ( !greyd.states[props.clientId] || !greyd.states[props.clientId].viewport ) {
				greyd.states[props.clientId] = { 
					// current viewport
					viewport: greyd.tools.getDeviceType(), 
					// observe/subscribe to viewport change
					observe: wp.data.subscribe( () => {
						// compare values
						var newViewport = greyd.tools.getDeviceType();
						if (greyd.states[props.clientId].viewport !== newViewport) {
							// console.log("viewport changed to "+newViewport);
							greyd.states[props.clientId].viewport = newViewport;
							// console.log(popover);
							// console.log(popover.body);
							removeOffcanvas();
							setPopover( {
								editor: false,
								body: false,
								dialog: false,
								button: false,
								style: {}
							} );
						}
					} ),
					// active timeouts
					timeouts: {},
					// global setTimeout function
					timeout: ( name, callback, time=0 ) => {
						// abort old timeout
						var oldid = greyd.states[props.clientId].timeouts[name]?? false;
						if ( oldid ) {
							// console.log("aborting", name, oldid);
							clearTimeout(oldid);
						}
						// set new timout
						var id = setTimeout( () => {
							// console.log("calling", name, id);
							callback();
							greyd.states[props.clientId].timeouts[name] = false;
						}, time );
						// save id
						greyd.states[props.clientId].timeouts[name] = id;
					}
				};
			}
			// console.log(greyd.states[props.clientId]);

			// open state
			const [ isOpen, setIsOpen ] = wp.element.useState( false );
			
			// popover state
			const [ popover, setPopover ] = wp.element.useState( {
				editor: false,
				body: false,
				dialog: false,
				button: false,
				style: {}
			} );

			// toggle popover
			if ( isOpen && !props.isSelected ) {
				// check if a child is selected
				var clientId = wp.data.select( 'core/block-editor' ).getSelectedBlockClientId();
				var parents = wp.data.select( 'core/block-editor' ).getBlockParents( clientId );
				if ( parents.indexOf(props.clientId) == -1 ) {
					// close
					setIsOpen( false );
					setPopover( {} );
				}
			}
			if ( !isOpen && props.isSelected ) {
				var parent = wp.data.select( 'core/block-editor' ).getBlockParents( props.clientId ).slice(-1).pop();
				var element = getElement( '#block-'+parent );
				if ( element ) {
					// after block is moved, it has a trasform property which destroys the popup and overlay previews
					if ( element.style.getPropertyValue('transform').indexOf("translate") == 0 ) {
						element.style.setProperty('transform', 'none');
					}
					// open
					setIsOpen( true );
				}
			}
		
			// get editor elements
			if ( !popover.editor || !popover.body ) {
				// console.log("getting editor and body");
				greyd.states[props.clientId].timeout( 'editor', () => {
					var body = false;
					var iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
					if ( iframe ) {
						body = iframe.contentDocument.body;
					}
					if ( !iframe ) {
						iframe = document.querySelector( '.edit-post-visual-editor' );
						if ( !iframe ) iframe = document.querySelector( '.edit-site-visual-editor' );
						body = iframe;
					}
					// console.log(iframe);
					// console.log(body);
					
					if ( iframe && body ) {
						setPopover( {
							...popover,
							editor: iframe,
							body: body
						} );
					}
				} );
			}

			// calculate positions for variations
			if ( isOpen && popover.editor && popover.body ) {

				// get dialog
				if ( !popover.dialog ) {
					// console.log("getting dialog");
					greyd.states[props.clientId].timeout( 'dialog', () => {
						// console.log("-> getting dialog");
						var element = getElement( '#block-'+props.clientId+' [role="dialog"]' );
						// console.log(element);
						if (element) setPopover( {
							...popover,
							dialog: element?? false
						} );
					} );
				}
				// get button
				if ( popover.dialog && !popover.button ) {
					var btnId = wp.data.select( 'core/block-editor' ).getPreviousBlockClientId();
					var element = getElement( "#block-"+btnId );
					// console.log(element);
					if ( element ) {
						var btnBlock = wp.data.select( 'core/block-editor' ).getBlock(btnId);
						// console.log(btnBlock);
						var margin = btnBlock.attributes?.align == 'center' ? '0 auto' : '0';
						if ( btnBlock.attributes?.align == 'right' ) margin = '0 0 0 auto';
						// console.log( element.offsetWidth );
						// console.log( element.offsetHeight );
						setPopover( {
							...popover,
							button: element,
							style: {
								...popover.style?? {},
								// 'outline': '1px solid red',
								'--button-width': element.offsetWidth+'px',
								'--button-height': element.offsetHeight+'px',
								'--button-align': margin,
							}
						} );
					}
				}

				// get positioning vars
				if ( popover.dialog && popover.button && popover.style && popover.style['--button-width'] ) {
					// console.log("checking positions");
					greyd.states[props.clientId].timeout( 'style', () => {
						// console.log("-> checking positions");
						// console.log(dialog);

						// get margins
						var marginL = 'var(--dialog-margin)';
						var marginR = 'var(--dialog-margin)';
						var marginX = 'calc(var(--dialog-margin) * 2)';
						var marginY = 'calc(var(--dialog-margin) * 2)';
						var comp = getComputedStyle(popover.dialog);
						if ( comp?.getPropertyValue('--dialog-margin').trim().indexOf(' ') > -1) {
							var m = comp.getPropertyValue('--dialog-margin').trim().split(' ', 4);
							if ( m.length == 1) {
								marginL = m[0];
								marginR = m[0];
								marginX = m[0];
								marginY = m[0];
							}
							else if ( m.length == 2 ) {
								marginL = m[1];
								marginR = m[1];
								marginX = m[1];
								marginY = m[0];
							}
							else if ( m.length == 3 ) {
								marginL = m[1];
								marginR = m[1];
								marginX = m[1];
								marginY = 'calc('+m[0]+' + '+m[2]+')';
							}
							else if ( m.length == 4 ) {
								marginL = m[3];
								marginR = m[1];
								marginX = 'calc('+m[1]+' + '+m[3]+')';
								marginY = 'calc('+m[0]+' + '+m[2]+')';
							}
						}

						// measure
						var div = document.createElement('div');
						div.style.display = 'none';
						div.style.height = marginL;
						div.style.width = marginR;
						popover.dialog.appendChild(div);
						marginL = parseFloat(getComputedStyle(div).getPropertyValue('height'));
						marginR = parseFloat(getComputedStyle(div).getPropertyValue('width'));
						div.remove();

						// delta
						var delta = 0.5 * (parseInt(popover.style['--button-width']) - popover.dialog.offsetWidth);
						// console.log(delta);
						// console.log(popover.style['--button-delta']);
						// console.log(popover.button.getBoundingClientRect());
						var buttonBounds = popover.button.getBoundingClientRect();
						var clientWidth = popover.editor.clientWidth;
						if ( buttonBounds.x + delta - marginL < 0 ) {
							delta = 0 - buttonBounds.x + marginL;
						}
						if ( buttonBounds.x + buttonBounds.width - delta + marginR > clientWidth ) {
							delta = clientWidth - popover.dialog.offsetWidth - buttonBounds.x - marginR;
						}
						// console.log(delta);

						var newStyle = {
							...popover.style,
							'--button-left': Math.round(buttonBounds.x)+'px',
							'--button-right': Math.round(clientWidth - buttonBounds.width - buttonBounds.x)+'px',
							'--button-delta': delta+'px',
							'--dialog-margin-x': marginX,
							'--dialog-margin-y': marginY,
						}
						if ( JSON.stringify(popover.style) != JSON.stringify(newStyle) ) {
							// console.log("set delta", delta);
							setPopover( {
								...popover,
								style: newStyle
							} );
						}
					} );
				}

				// console.log("isOpen "+(isOpen ? "true" : "false")+" | "+
				// 			"body "+(popover.body ? "true" : "false")+" | "+
				// 			"dialog "+(popover.dialog ? "true" : "false")+" | "+
				// 			"button "+(popover.button ? "true" : "false")+" | "+
				// 			"style "+(popover.style && popover.style['--button-width'] ? "true" : "false"));
				
			}

			// offcanvas variation (body classes and styles)
			if ( isOpen && atts.variation === 'offcanvas' ) {
				// console.log(popover.dialog);
				if ( popover.body && popover.dialog && popover.style ) {
					addOffcanvas(popover.body);
					// console.log("getting offcanvas delta");
					greyd.states[props.clientId].timeout( 'delta', () => {
						// calculate offcanvas delta
						// console.log(popover.dialog);
						var comp = getComputedStyle(popover.dialog);
						// console.log(comp);
						var width = comp.getPropertyValue('width');
						var height = comp.getPropertyValue('height');
						var dialogMargin = comp.getPropertyValue('--dialog-margin') ?? '0px';
						if ( dialogMargin == '0' ) {
							dialogMargin = '0px';
						}
						var marginX = 'calc('+dialogMargin+' * 2)';
						var marginY = 'calc('+dialogMargin+' * 2)';
						if ( dialogMargin.trim().indexOf(' ') > -1) {
							var m = dialogMargin.trim().split(' ', 4);
							if ( m.length == 1) {
								marginX = m[0];
								marginY = m[0];
							}
							else if ( m.length == 2 ) {
								marginX = m[1];
								marginY = m[0];
							}
							else if ( m.length == 3 ) {
								marginX = m[1];
								marginY = 'calc('+m[0]+' + '+m[2]+')';
							}
							else if ( m.length == 4 ) {
								marginX = 'calc('+m[1]+' + '+m[3]+')';
								marginY = 'calc('+m[0]+' + '+m[2]+')';
							}
						}

						var delta = 'calc(0px - ('+width+' + '+marginX+'))';
						if ( atts.position == 'left' ) {
							delta = 'calc('+width+' + '+marginX+')';
						}
						if ( atts.position == 'top' ) {
							delta = 'calc('+height+' + '+marginY+')';
						}
						if ( atts.position == 'bottom' ) {
							delta = 'calc(0px - ('+height+' + '+marginY+'))';
						}
						// console.log(delta);
						popover.body.style.setProperty('--offcanvas-delta', delta);
					}, 250 ); // delay to wait for changed viewport size
				}
			}
			else {
				removeOffcanvas(popover.body);
			}


			const hidePopup = () => {
				setIsOpen( false );
				setPopover( {} );
				wp.data.dispatch( 'core/block-editor' ).selectBlock(
					wp.data.select( 'core/block-editor' ).getBlockParents( props.clientId ).slice( -1 ).pop()
				);
			};

			const classNames = [
				className,
				// atts.greydClass,
				_.isEmpty(atts.variation) ? 'is-variation-default' : 'is-variation-' + atts.variation,
				_.isEmpty(atts.position) ? 'is-position-default' : 'is-position-' + atts.position.replace(' ', '-'),
				_.has( atts.greydStyles, '--dialog-color' ) ? 'has-text-color' : '',
			].join(' ');

			const makePreview = () => {

				var styles = popover.style?? {};
				if ( !isOpen ) {
					// console.log(props.attributes.dynamic_parent);
					if ( !props.attributes.dynamic_parent ) {
						// closed and not in dynamic template: don't render
						return;
					}
					// closed and dynamic template: render but hide
					styles.display = 'none';
				}

				return [
					el( 'div', {
						className: 'wp-block-greyd-popover-popup '+atts.greydClass,
						style: styles // popover.style?? {}
					}, [
						el( 'div', {
							id: atts.anchor,
							role: 'dialog',
							open: true,
							className: classNames
						}, [
							el( 'button', {
								type: 'button',
								className: 'popover-close-button ' + atts.closeButton,
								onClick: hidePopup
							} ),
							el( wp.blockEditor.InnerBlocks, {
								renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender,
								templateLock: false
							} )
						] ),
						el( 'div', {
							className: 'dialog-backdrop',
							onClick: hidePopup
						} )
					] )
				];
			}

			return [

				// styles
				el( wp.blockEditor.InspectorControls, {
					group: 'styles',
				}, [

					el( greyd.components.StylingControlPanel, {
						title: __("Colors", 'greyd_hub'),
						initialOpen: true,
						blockProps: props,
						controls: [
							{
								label: __("Text color", 'greyd_hub'),
								attribute: "--dialog-color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							},
							{
								label: __("Background", 'greyd_hub'),
								attribute: "--dialog-background",
								control: greyd.components.ColorGradientPopupControl,
								// mode: 'color',
								preventConvertGradient: true,
								contrast: {
									default: _.has(atts.greydStyles, '--dialog-color') ? atts.greydStyles['--dialog-color'] : ''
								}
							},
							... atts.closeButton !== 'is-hidden' ? [ {
								label: __( "Close button", 'greyd_hub' ),
								attribute: "--close-color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color',
							} ] : [],
							// ... atts.closeButton === 'is-outside' ? [ {
							// 	label: __( 'Schließen Button Hintergrund', 'greyd_hub' ),
							// 	attribute: "--close-background",
							// 	control: greyd.components.ColorGradientPopupControl,
							// 	// colors: greyd.tools.getColors(),
							// 	// gradients: greyd.tools.getGradients(),
							// 	preventConvertGradient: true,
							// 	contrast: {
							// 		default: _.has(atts.greydStyles, '--close-color') ? atts.greydStyles['--close-color'] : ''
							// 	}
							// } ] : [],
							... atts.variation !== 'dropdown' ? [ {
								label: __( 'Backdrop', 'greyd_hub' ),
								attribute: "--backdrop-color",
								control: greyd.components.ColorGradientPopupControl,
								preventConvertGradient: true
							} ] : []
						]
					} ),
					el( greyd.components.StylingControlPanel, {
						title: __("Border", 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [
							{
								label: __("Border", 'greyd_hub'),
								attribute: "--dialog-border",
								control: greyd.components.BorderControl,
								type: "string"
							}
						]
					} ),
					el( greyd.components.StylingControlPanel, {
						title: __("Border radius", 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [
							{
								label: __("Border radius", 'greyd_hub'),
								attribute: "--dialog-radius",
								control: greyd.components.DimensionControl,
								sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
								type: "string"
							}
						]
					} ),

					// animation
					el( greyd.components.StylingControlPanel, {
						title: __("Show/Hide Transition", 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [
							{
								label: __("Effect Type", 'greyd_hub'),
								attribute: "--dialog-transition-property",
								control: wp.components.SelectControl,
								options: [
									{ label: __("Default", 'greyd_hub'), value: "" },
									{ label: __("Scale Only", 'greyd_hub'), value: "scale" },
									{ label: __("Fade Only", 'greyd_hub'), value: "opacity" },
									{ label: __("Scale & Fade", 'greyd_hub'), value: "scale, opacity" },
									{ label: __("None", 'greyd_hub'), value: "none" },
								],
							}, 
							...(atts.greydStyles?.['--dialog-transition-property'] !== "none" ? [
								{
									label: __("Duration", 'greyd_hub'),
									attribute: "--dialog-transition-duration",
									control: greyd.components.__RangeUnitControl,
									min: { s: 0 },
									max: { s: 2 },
									step: { s: 0.1 },
									units: [ 's' ],
								},
								{
									label: __("Easing", 'greyd_hub'),
									attribute: "--dialog-transition-timing-function",
									control: wp.components.SelectControl,
									options: [
										{ label: __("Linear", 'greyd_hub'), value: "linear" },
										{ label: __("Ease-in", 'greyd_hub'), value: "ease-in" },
										{ label: __("Ease-out", 'greyd_hub'), value: "ease-out" },
										{ label: __("Ease-in-out", 'greyd_hub'), value: "ease-in-out" },
										{ label: __("Cubic-bezier", 'greyd_hub'), value: "cubic-bezier(0.4, 0, 0.2, 1)" }
									],
								},
								...(
									(atts.greydStyles?.['--dialog-transition-property'] == "scale" || atts.greydStyles?.['--dialog-transition-property'] == "scale, opacity")
									? [
										{
											label: __("Origin", 'greyd_hub'),
											attribute: "--dialog-transition-origin",
											control: wp.components.__experimentalAlignmentMatrixControl,
										}
									]
									: []
								)
							] : [] )
						]
					} ),

					// shadow
					el( greyd.components.StylingControlPanel, {
						title: __("Shadow", 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [
							{
								label: __("Drop shadow", 'greyd_hub'),
								attribute: "--dialog-box-shadow",
								control: greyd.components.DropShadowControl,
							}
						]
					} ),
					// close button
					atts.closeButton !== 'is-hidden' && el( greyd.components.StylingControlPanel, {
						title: __("Close button layout", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Size", 'greyd_hub'),
								attribute: "--close-size",
								control: greyd.components.RangeUnitControl
							},
							// ... atts.closeButton === 'is-outside' ? [ {
							// 	label: __("Border radius", 'greyd_hub'),
							// 	attribute: "--close-radius",
							// 	control: greyd.components.DimensionControl,
							// 	sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
							// 	type: 'string',
							// } ] : []
						]
					} ),
					// backdrop
					atts.variation !== 'dropdown' && el( greyd.components.StylingControlPanel, {
						title: __('Backdrop', 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [
							{
								label: __("Blur effect", 'greyd_hub'),
								attribute: "--backdrop-blur",
								control: greyd.components.RangeUnitControl,
								units: [ 'px'],
								max: 30
							},
							{
								label: __("Opacity", 'greyd_hub'),
								attribute: "--backdrop-opacity",
								control: wp.components.RangeControl,
								min: 0,
								max: 100,
							}
						]
					} )
				] ),

				// settings
				el( wp.blockEditor.InspectorControls, {
					group: 'settings',
				}, [

					el( greyd.components.AdvancedPanelBody, {
						title: __( 'Position', 'greyd_hub' ),
						holdsChange: !_.isEmpty(atts.position),
						initialOpen: true
					}, [

						atts.variation !== '' && el( greyd.components.ButtonGroupControl, {
							// label: __( "Appearance", 'greyd_hub' ),
							value: atts.position,
							options: [
								{
									label: __( "Left", 'greyd_hub' ),
									value: 'left'
								},
								{
									label: __( "Top", 'greyd_hub' ),
									value: 'top'
								},
								{
									label: __( "Bottom", 'greyd_hub' ),
									value: 'bottom'
								},
								{
									label: __( "Right", 'greyd_hub' ),
									value: '',
									isDefault: true
								},
							],
							onChange: val => {
								setAttributes({ position: val })
							},
						} ),
						
						atts.variation === '' && el( wp.components.__experimentalAlignmentMatrixControl, {
							// label: __( "Appearance", 'greyd_hub' ),
							value: atts.position,
							onChange: val => {
								setAttributes({ position: val })
							},
						} ),
					] ),
					atts.variation === '' && el( greyd.components.StylingControlPanel, {
						title: __('Origin', 'greyd_hub'),
						initialOpen: true,
						blockProps: props,
						controls: [
							{
								// label: __('Origin', 'greyd_hub'),
								attribute: "--dialog-origin",
								control: wp.components.__experimentalAlignmentMatrixControl
							}
						]
					} ),

					el( greyd.components.StylingControlPanel, {
						title: __("Size", 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Width", 'greyd_hub'),
								attribute: "--dialog-width",
								control: greyd.components.RangeUnitControl,
								max: 1200,
							},
							{
								label: __("Height", 'greyd_hub'),
								attribute: "--dialog-height",
								control: greyd.components.RangeUnitControl,
								max: 1200,
							},
							... atts.variation === 'dropdown' ? [ {
								label: __("Triangle", 'greyd_hub'),
								attribute: "--tri-size",
								control: greyd.components.RangeUnitControl,
								units: [ 'px' ],
								max: 100
							} ] : []
						]
					} ),
					el( greyd.components.StylingControlPanel, {
						title: __("Spaces", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Inside", 'greyd_hub'),
								attribute: "--dialog-padding",
								control: greyd.components.DimensionControl,
								type: 'string',
							},
							{
								label: __('Margin', 'greyd_hub'),
								attribute: "--dialog-margin",
								control: greyd.components.DimensionControl,
								type: 'string',
							}
						]
					} ),

					// close button
					// ...( atts.variation === 'dropdown' ? [] : [
						el( greyd.components.AdvancedPanelBody, {
							title: __( "Close button", 'greyd_hub' ),
							holdsChange: !_.isEmpty(atts.closeButton),
							initialOpen: false
						}, [
							el( greyd.components.ButtonGroupControl, {
								label: __( "Appearance", 'greyd_hub' ),
								value: atts.closeButton,
								initialOpen: true,
								options: [
									{
										label: __( 'Normal', 'greyd_hub' ),
										value: '',
										isDefault: true
									},
									// {
									// 	label: __( 'Außerhalb', 'greyd_hub' ),
									// 	value: 'is-outside'
									// },
									{
										label: __( "Hide", 'greyd_hub' ),
										value: 'is-hidden'
									},
								],
								onChange: val => {
									setAttributes({ closeButton: val })
								},
							} ),
							// aria-label
							el( wp.components.TextControl, {
								label: __( 'Aria label', 'greyd_hub' ),
								value: atts.closeButtonAriaLabel,
								onChange: val => setAttributes({ closeButtonAriaLabel: val })
							} )
						] )
					// ] )
				] ),

				// toolbar
				el( wp.blockEditor.BlockControls, {
					// group: 'block'
				}, [
					el( wp.components.ToolbarGroup, {}, [
						el( wp.components.ToolbarButton, {
							// as: wp.components.ToolbarButton,
							text: __( "Hide popover", 'greyd_hub' ),
							icon: 'hidden',
							onClick: hidePopup
						} )
					] ),
				] ),

				// preview
				makePreview(),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						"": atts.greydStyles,
					}
				} ),
			];
		},

		save: function ( props ) {
			return el( wp.blockEditor.InnerBlocks.Content );
		},
		
		deprecated: [
			/**
			 * @since 2.5.0 Move rendering to PHP render_callback
			 */
			{
				supports: {
					inserter: false,
					lock: false,
					reusable: false,
					anchor: true,
					ariaLabel: true,
				},
				attributes: {
					variation: { type: 'string', default: '' },
					position: { type: 'string', default: '' },
					closeButton: { type: 'string', default: '' }, // add class to close-button: 'outside' | 'hidden'
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {

					const newGreydClass = greyd.tools.getGreydClass( props );
					if ( props.attributes?.greydClass !== newGreydClass ) {
						props.setAttributes( { greydClass: newGreydClass } );
					}
		
					const {
						className,
						attributes: atts
					} = props;
		
					const classNames = [
						_.isEmpty(atts.variation) ? 'is-variation-default' : 'is-variation-' + atts.variation,
						_.isEmpty(atts.position) ? 'is-position-default' : 'is-position-' + atts.position.replace(' ', '-'),
						_.has( atts.greydStyles, '--dialog-color' ) ? 'has-text-color' : '',
					].join(' ');
		
					return el( 'div', {
						id: atts.anchor,
						className: [ className, atts.greydClass ].join(' ')
					}, [
						el( 'div', {
							id: "popover-ID",
							role: 'dialog',
							className: classNames
						}, [
							el( 'button', {
								className: 'popover-close-button ' + atts.closeButton,
								tabindex: "0",
								role: "button",
								"aria-expanded": "false",
								"aria-label": atts.ariaLabel,
								"aria-controls": "popover-ID"
							} ),
							el( wp.blockEditor.InnerBlocks.Content )
						] ),
						el( 'div', {
							className: 'dialog-backdrop'
						} ),
					] );
				}
			},
			// remove styles from saved markup
			{
				supports: {
					inserter: false,
					lock: false,
					reusable: false,
					anchor: true,
					ariaLabel: true,
				},
				attributes: {
					variation: { type: 'string', default: '' },
					position: { type: 'string', default: '' },
					closeButton: { type: 'string', default: '' }, // add class to close-button: 'outside' | 'hidden'
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
				},
				save: function ( props ) {
		
					const newGreydClass = greyd.tools.getGreydClass( props );
					if ( props.attributes?.greydClass !== newGreydClass ) {
						props.setAttributes( { greydClass: newGreydClass } );
					}
		
					const {
						className,
						attributes: atts
					} = props;
		
					const classNames = [
						_.isEmpty(atts.variation) ? 'is-variation-default' : 'is-variation-' + atts.variation,
						_.isEmpty(atts.position) ? 'is-position-default' : 'is-position-' + atts.position.replace(' ', '-'),
						_.has( atts.greydStyles, '--dialog-color' ) ? 'has-text-color' : '',
					].join(' ');
		
					return el( 'div', {
						id: atts.anchor,
						className: [ className, atts.greydClass ].join(' ')
					}, [
						el( 'div', {
							id: "popover-ID",
							role: 'dialog',
							className: classNames
						}, [
							el( 'button', {
								className: 'popover-close-button ' + atts.closeButton,
								tabindex: "0",
								role: "button",
								"aria-expanded": "false",
								"aria-label": atts.ariaLabel,
								"aria-controls": "popover-ID"
							} ),
							el( wp.blockEditor.InnerBlocks.Content )
						] ),
						el( 'div', {
							className: 'dialog-backdrop'
						} ),
		
						// styles <- deprecated
						el( greyd.components.RenderPreviewStyles, {
							selector: atts.greydClass,
							styles: {
								"": atts.greydStyles,
							}
						} ),
					] );
				}
			}
		]

	} );
	
	/**
	 * Force popover-popup deprecation
	 * (removing styles from saved markup)
	 */
	var editBlockListHook = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {

			/**
			 * if a popover-popup block is not valid and has a validationIssue concerning the 'styles' tag,
			 * the deprecation is not working (probably because of saved color value in the greydStyles shadow property).
			 * We try to recover it by replacing it with a new version, re-saving it from the attributes in the process.
			 */
			if (props.name == "greyd/popover-popup" && props.block.isValid === false) {

				if (
					(
						// 'style' is in validation issues
						props.block.validationIssues?.length == 2 &&
						props.block.validationIssues[0].args?.length == 5 &&
						props.block.validationIssues[0].args[4].tagName == 'style'
					) || (
						// '<style>' tag is in orignal block content
						props.block.originalContent &&
						props.block.originalContent.indexOf('</style>') > -1
					)
				) {
					// console.log(props);

					// make new parent (greyd/popover) block and replace in editor
					// replacing only the child block doesn't work, the states get messed up and no new values are saved.					
					var parent = wp.data.select( 'core/block-editor' ).getBlockParents( props.block.clientId ).slice(-1).pop();
					// console.log(parent);
					var { name, attributes, innerBlocks } = wp.data.select('core/block-editor').getBlock(parent);
					var newInnerBlocks = [];
					innerBlocks.forEach( (block) => {
						newInnerBlocks.push( wp.blocks.createBlock( block.name, block.attributes, block.innerBlocks ) );
					} );
					var newBlock = wp.blocks.createBlock( name, attributes, newInnerBlocks );
					// console.log(newBlock);
					wp.data.dispatch( 'core/block-editor' ).replaceBlock( parent, newBlock );

					// render old block as valid while it is being replaced
					props.isValid = true;

					// log info
					console.groupCollapsed("Block `"+props.name+"` updated");
					console.info("New content generated by `save` function to remove rendered `styles` markup.");
					console.log(wp.blocks.getBlockType(props.name));
					console.log(props.attributes);
					console.groupEnd();
				}

			}

			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/list/popover', 
		editBlockListHook 
	);

} )( window.wp );