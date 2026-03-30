/**
 * Deprecated version of the popover editor.js, before
 * the block was separated into parent & child blocks with variations.
 */
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the popover.
	 */
	wp.blocks.registerBlockType( 'greyd/popover', {
		title: __( 'Popover', 'greyd_hub' ),
		description: __( "Displays a small popover at a specified location.", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('popover'),
		category: 'greyd-blocks',
		keywords: [ 'trigger', 'toggle', 'popup', 'popover', 'dropdown' ],
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
			popoverStyles: { type: 'object', default: {} },
			buttonStyles: { type: 'object', default: {} },
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

			const hasChildBlocks = greyd.tools.hasChildBlocks(props.clientId);

			// states
			const [ isOpen, setOpen ] = wp.element.useState( false );
			const [ mode, setMode ] = wp.element.useState( '' );
			if ( !props.isSelected && mode != '' ) setMode( '' );
			const setModeOpen = ( m, o ) => {
				setOpen( typeof o === 'undefined' ? true : false );
				setMode( m );
			};

			// button styling
			let buttonClass = 'button is-style-prim';
			if ( className.indexOf('has-button-sec') !== -1 ) {
				buttonClass = 'button is-style-sec';
			} else if ( className.indexOf('has-button-trd') !== -1 ) {
				buttonClass = 'button is-style-trd';
			} else if ( className.indexOf('has-link-prim') !== -1 ) {
				buttonClass = 'is-style-link-prim';
			} else if ( className.indexOf('has-link-sec') !== -1 ) {
				buttonClass = 'is-style-link-sec';
			} else if ( className.indexOf('has-clear') !== -1 ) {
				buttonClass = 'is-style-clear';
			}
			if ( buttonClass !== atts.button.style ) {
				setAttributes({ button: { ...atts.button, style: buttonClass }});
			}

			return [

				//  sidebar
				el( wp.blockEditor.InspectorControls, {}, [

					mode == '' && el( wp.components.PanelBody, {
						title: __( "Elements", 'greyd_hub' ),
					}, [

						el( greyd.components.SectionControl, {
							title: __( 'Button', 'greyd_hub' ),
							onClick: () => setModeOpen( 'button', false )
						} ),

						el( greyd.components.SectionControl, {
							title: __( 'Popover', 'greyd_hub' ),
							onClick: () => setModeOpen( 'popover' )
						} ),

						atts.popoverClassName !== 'is-style-dropdown' && el( greyd.components.SectionControl, {
							title: __( "Background", 'greyd_hub' ),
							onClick: () => setModeOpen( 'backdrop' )
						} ),

						el( greyd.components.SectionControl, {
							title: __( "Close button", 'greyd_hub' ),
							onClick: () => setModeOpen( 'close' )
						} ),
					] ),

					mode == 'button' && [
						el( greyd.components.SectionControl, {
							title: __( 'Button', 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( '' ),
							isHeader: true
						} ),

						// size
						el( greyd.components.AdvancedPanelBody, {
							title: __( "Size", 'greyd_hub' ),
							holdsChange: !_.isEmpty(atts.button.size),
							initialOpen: true,
						}, [
							el( greyd.components.ButtonGroupControl, {
								value: atts.button.size,
								// label: __( "Size", 'greyd_hub' ),
								options: [
									{ value: "small", label: __( "Small", 'greyd_hub' ) },
									{ value: "", label: __( "Default", 'greyd_hub' ) },
									{ value: "big", label: __( "Big", 'greyd_hub' ) },
								],
								onChange: value => setAttributes( { button: { ...atts.button, size: value }} ),
							} ),
						] ),

						// width
						el( greyd.components.StylingControlPanel, {
							title: __("Width", 'greyd_hub'),
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'buttonStyles',
							controls: [
								{
									label: __("Width", 'greyd_hub'),
									attribute: "width",
									control: greyd.components.RangeUnitControl,
									max: 500
								}
							]
						} ),

						el( greyd.components.ButtonIconControl, {
							value: atts.button.icon,
							onChange: value => setAttributes( { button: { ...atts.button, icon: value }} ),
							// initialOpen: true
						} ),
						
						// custom button
						el( greyd.components.AdvancedPanelBody, {
							title: __( "Individual button", 'greyd_hub' ),
							// initialOpen: true,
							holdsChange: atts.button.custom
						}, [
							el( wp.components.ToggleControl, {
								label: __( "Overwrite the design of the button individually", 'greyd_hub' ),
								checked: atts.button.custom,
								onChange: value => setAttributes( { button: { ...atts.button, custom: !!value } } )
							} ),
						] ),
						atts.button.custom && el( greyd.components.CustomButtonStyles, {
							blockProps: props,
							parentAttr: 'buttonStyles'
						} )
					],

					mode == 'popover' && [
						el( greyd.components.SectionControl, {
							title: __( 'Popover', 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( '' ),
							isHeader: true
						} ),

						// style / className
						el( greyd.components.AdvancedPanelBody, {
							title: __( "Appearance", 'greyd_hub' ),
							holdsChange: !_.isEmpty(atts.popoverClassName) && atts.popoverClassName !== 'is-style-dialog-default',
							initialOpen: true,
						}, [
							el( wp.components.SelectControl, {
								label: __( "Appearance", 'greyd_hub' ),
								value: atts.popoverClassName,
								onChange: value => setAttributes( { popoverClassName: value } ),
								options: [
									{
										value: 'is-style-dialog-default',
										label: __( "Standard dialog", 'greyd_hub' ),
										isDefault: true
									},
									{
										value: 'is-style-dialog-bottom',
										label: __( "Dialogue from below", 'greyd_hub' )
									},
									{
										value: 'is-style-dropdown',
										label: __( "Dropdown", 'greyd_hub' )
									},
									{
										value: 'is-style-banner-right',
										label: __( "Banner from right", 'greyd_hub' )
									},
									{
										value: 'is-style-banner-left',
										label: __( "Banner from left", 'greyd_hub' )
									},
									{
										value: 'is-style-notice-top',
										label: __( "Announcement above", 'greyd_hub' )
									},
									{
										value: 'is-style-notice-bottom',
										label: __( "Announcement below", 'greyd_hub' )
									},
								]
							} ),
						] ),

						// colors
						el( greyd.components.StylingControlPanel, {
							title: __( "Colors", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( "Text color", 'greyd_hub' ),
									attribute: "--dialog-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color',
									preventConvertGradient: true
								},
								{
									label: __( "Background color", 'greyd_hub' ),
									attribute: "--dialog-background",
									control: greyd.components.ColorGradientPopupControl,
									preventConvertGradient: true
								},
							]
						} ),

						// layout
						el( greyd.components.StylingControlPanel, {
							title: __( 'Layout', 'greyd_hub' ),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( "Width", 'greyd_hub' ),
									attribute: "--dialog-width",
									control: greyd.components.RangeUnitControl,
									max: 2000,
								},
								{
									label: __( 'Padding', 'greyd_hub' ),
									attribute: "--dialog-padding",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
									units: [ 'px', 'em' ],
									max: {
										em: 5,
										px: 100
									}
								},
								{
									label: __( "Distance to page margin", 'greyd_hub' ),
									attribute: "--dialog-margin",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
									units: [ 'px', 'em' ],
									max: {
										em: 5,
										px: 100
									}
								}
							]
						} ),

						// border radius
						el( greyd.components.StylingControlPanel, {
							title: __( "Border radius", 'greyd_hub' ),
							initialOpen: false,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [ {
								label: __( "Border radius", 'greyd_hub' ),
								attribute: "--dialog-radius",
								control: greyd.components.DimensionControl,
								labels: {
									"all": __( "All corners", "greyd_hub" ),
								},
								sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
								type: "string"
							} ]
						} ),
					],

					mode == 'backdrop' && [
						el( greyd.components.SectionControl, {
							title: __( "Background", 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( '' ),
							isHeader: true
						} ),

						el( greyd.components.StylingControlPanel, {
							title: __( "Color", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( "Color", 'greyd_hub' ),
									attribute: "--backdrop-color",
									control: greyd.components.ColorGradientPopupControl,
									preventConvertGradient: true
								}
							]
						} ),

						el( greyd.components.StylingControlPanel, {
							title: __( "Effect", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( "Opacity", 'greyd_hub' ),
									attribute: "--backdrop-opacity",
									control: wp.components.RangeControl,
									min: 0,
									max: 100,
								},
								{
									label: __( "Blur", 'greyd_hub' ),
									attribute: "--backdrop-blur",
									control: greyd.components.RangeUnitControl,
									units: [ 'px' ],
									max: 10
								}
							]
						} ),
					],

					mode == 'close' && [
						el( greyd.components.SectionControl, {
							title: __( "Close button", 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( '' ),
							isHeader: true
						} ),

						el( greyd.components.AdvancedPanelBody, {
							title: __( "Appearance", 'greyd_hub' ),
							holdsChange: !_.isEmpty(atts.closeButton),
						}, [
							el( wp.components.ButtonGroup, {}, [
								el( wp.components.Button, {
									variant: atts.closeButton == 'hidden' ? 'primary' : null,
									onClick: () => setAttributes( { closeButton: 'hidden' } )
								}, __( "Hide", 'greyd_hub' ) ),
								el( wp.components.Button, {
									variant: atts.closeButton == '' ? 'primary' : null,
									onClick: () => setAttributes( { closeButton: '' } )
								}, __( "Inside", 'greyd_hub' ) ),
								el( wp.components.Button, {
									variant: atts.closeButton == 'outside' ? 'primary' : null,
									onClick: () => setAttributes( { closeButton: 'outside' } )
								}, __( "Outside", 'greyd_hub' ) ),
							] )
						] ),

						el( greyd.components.StylingControlPanel, {
							title: __( "Colors", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( 'Icon', 'greyd_hub' ),
									attribute: "--close-color",
									control: greyd.components.ColorGradientPopupControl,
									preventConvertGradient: true,
									mode: 'color',
								},
								...(
									atts.closeButton == 'outside' ? [ {
										label: __( "Background", 'greyd_hub' ),
										attribute: "--close-background",
										control: greyd.components.ColorGradientPopupControl,
										preventConvertGradient: true
									} ] : []
								)
							]
						} ),

						el( greyd.components.StylingControlPanel, {
							title: __( "Size", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __( "Icon size", 'greyd_hub' ),
									attribute: "--close-size",
									control: greyd.components.RangeUnitControl,
									units: [ 'px', 'em' ],
									max: {
										em: 3,
										px: 60
									}
								}
							]
						} ),

						atts.closeButton == 'outside' && el( greyd.components.StylingControlPanel, {
							title: __( "Border radius", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [ {
								label: __( "Border radius", 'greyd_hub' ),
								attribute: "--close-radius",
								control: greyd.components.DimensionControl,
								type: 'string',
								units: [ 'px', 'em', '%' ],
								max: {
									em: 2,
									px: 40,
									'%': 100
								}
							} ]
						} ),

					],
				] ),

				// toolbar
				el( wp.blockEditor.BlockControls, {
					// group: 'block'
				}, [
					el( wp.components.ToolbarGroup, {}, [
						el( wp.components.ToolbarItem, {
							as: wp.components.ToolbarButton,
							icon: isOpen ? 'hidden' : 'visibility',
							onClick: () => setOpen( !isOpen ),
							text: isOpen ? __( "Hide popover", 'greyd_hub' ) : __( "Show popover", 'greyd_hub' )
						} )
					] ),
				] ),

				// preview
				el( 'div', {
					className: [ className, atts.greydClass, atts.popoverClassName ].join( ' ' )
				}, [

					el( 'div', {
						className: [ atts.button.style, atts.button.size ].join(' '),
					}, [
						el( greyd.components.RenderButtonIcon, {
							value: atts.button.icon,
							position: 'before'
						} ),
						el( wp.blockEditor.RichText, {
							format: 'string',
							tagName: 'span',
							style: { flex: '1' },
							value: atts.button.content,
							placeholder: __( 'Popover', 'greyd_hub' ),
							allowedFormats: [ 'core/bold', 'core/italic', 'core/strikethrough', 'greyd/dtag', 'core/highlight' ],
							onChange: value => setAttributes( { button: { ...atts.button, content: value } } )
						} ),
						el( greyd.components.RenderButtonIcon, {
							value: atts.button.icon,
							position: 'after'
						} ),
					] ),

					isOpen && [
						el( 'div', {
							id: atts.anchor,
							role: 'dialog',
							open: true,
							className: _.has( atts.popoverStyles, '--dialog-color' ) ? 'has-text-color' : ''
						}, [
							el( 'button', {
								type: 'button',
								className: 'popover-close-button ' + atts.closeButton,
								onClick: () => setOpen( false ),
							} ),
							el( wp.blockEditor.InnerBlocks, {
								// renderAppender: wp.blockEditor.InnerBlocks.ButtonBlockAppender
								renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender
							} )
						] ),
						el( 'div', {
							className: 'dialog-backdrop',
							onClick: () => setOpen( false ),
						} )
					]
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						"": atts.popoverStyles,
					}
				} ),
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + ' > *:first-child',
					styles: {
						"": atts.buttonStyles,
					},
					important: true
				} ),
			];
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
	} );

} )( window.wp );