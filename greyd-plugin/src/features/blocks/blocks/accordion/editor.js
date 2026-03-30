( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the accordion wrapper.
	 */
	wp.blocks.registerBlockType( 'greyd/accordion', {
		title: __("Accordion", 'greyd_hub'),
		description: __("Displays collapsible content. You can insert other blocks into it.", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('accordion'),
		category: 'greyd-blocks',
		keywords: [ 'trigger', 'toggle', 'tabs', 'accordion' ],
		supports: {
			anchor: true,
			spacing: {
				margin: true,
				padding: true
			}
		},
		example: {
			attributes: {
			},
			innerBlocks: [
				{
					name: 'greyd/accordion-item',
					attributes: {
						title: __("Accordion Section", 'greyd_hub'),
					},
					innerBlocks: [
						{
							name: 'core/paragraph',
							attributes: {
								content: __("Displays collapsible content. You can insert other blocks into it.", 'greyd_hub')
							}
						},
					]
				},
				{
					name: 'greyd/accordion-item',
					attributes: {
						title: __("Accordion Section", 'greyd_hub'),
					}
				},
			]
		},
		attributes: {
			iconNormal: { type: 'string', default: 'arrow_triangle-down' },
			iconActive: { type: 'string', default: 'arrow_triangle-up' },
			iconPosition: { type: 'string', default: '' },
			titleTag: { type: 'string', default: '' }, /** @since 2.6.0 */
			autoClose: { type: 'boolean', default: false },
			openFirst: { type: 'boolean', default: true },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
			titleStyles: { type: 'object', default: {} },
			contentStyles: { type: 'object', default: {} },
			renderStructuredData: { type: 'boolean', default: false }
		},
		providesContext: {
			'greyd/accordion-iconNormal': 'iconNormal',
			'greyd/accordion-iconActive': 'iconActive',
			'greyd/accordion-titleTag': 'titleTag',
		},

		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				className,
				setAttributes,
				attributes: atts
			} = props;

			const [ mode, setMode ] = wp.element.useState("");
			if ( !props.isSelected && mode != "" ) setMode("");

			return [
				
				//  sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [

						// icon
						el( wp.components.PanelBody, {
							title: __('Icon', 'greyd_hub'),
							initialOpen: true
						}, [
							el( greyd.components.IconPicker, {
								label: __("Normal icon", 'greyd_hub'),
								value: atts.iconNormal,
								onChange: value => setAttributes({ iconNormal: value })
							} ),
							el( greyd.components.IconPicker, {
								label: __("Active icon", 'greyd_hub'),
								value: atts.iconActive,
								onChange: value => setAttributes({ iconActive: value })
							} ),
							el( greyd.components.ButtonGroupControl, {
								label: __('Position', 'greyd_hub'),
								value: atts.iconPosition,
								onChange: value => setAttributes({ iconPosition: value }),
								options: [
									{ label: __("Left", 'greyd_hub'), value: 'hasiconleft' },
									{ label: __("Right", 'greyd_hub'), value: '' },
								]
							} ),
						] ),
						
						// behaviour
						el( wp.components.PanelBody, {
							title: __("Behavior", 'greyd_hub'),
							initialOpen: false
						}, [
							el( wp.components.ToggleControl, {
								label: __("Only one section at a time", 'greyd_hub'),
								checked: atts.autoClose,
								onChange: value => setAttributes({ autoClose: value }),
								help: __("This change only affects the frontend.", 'greyd_hub'),
							} ),
							el( wp.components.ToggleControl, {
								label: __("First section open", 'greyd_hub'),
								checked: atts.openFirst,
								onChange: value => setAttributes({ openFirst: value }),
								help: __("This change only affects the frontend.", 'greyd_hub'),
							} ),
						] ),

						/**
						 * HTML Tag Selection Panel
						 * @since 2.6.0
						 */
						el( wp.components.PanelBody, {
							title: __("HTML Structure", 'greyd_hub'),
							initialOpen: false
						}, [
							el( greyd.components.HTMLTagSelectControl, {
								label: __("Title Tag", 'greyd_hub'),
								value: atts.titleTag,
								parentTagLabel: __("HTML Tag", 'greyd_hub'),
								parentTags: [
									{ label: 'Button only', value: '' },
									{ label: 'H1', value: 'h1' },
									{ label: 'H2', value: 'h2' },
									{ label: 'H3', value: 'h3' },
									{ label: 'H4', value: 'h4' },
									{ label: 'H5', value: 'h5' },
									{ label: 'H6', value: 'h6' },
									// { label: 'Div', value: 'div' },
								],
								onChange: (value) => setAttributes({ titleTag: value }),
								help: __("Change the HTML tag for the accordion title. The title will still be wrapped in a button element.", 'greyd_hub'),
							}),
						] ),

						// layout
						el( greyd.components.StylingControlPanel, {
							title: __('Layout', 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Width", 'greyd_hub'),
									attribute: "--accord-width",
									control: greyd.components.RangeUnitControl,
									max: 1400
								},
								{
									label: __("Alignment", 'greyd_hub'),
									attribute: "--accord-align-items",
									control: greyd.components.ButtonGroupControl,
									options: [
										{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignLeft'), value: 'flex-start' },
										{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignCenter'), value: 'center' },
										{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignRight'), value: 'flex-end' },
									]
								},
								{
									label: __("Space between", 'greyd_hub'),
									attribute: "--accord-content-margin-bottom",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
								}
							]
						}),

						// renderStructuredData
						el( wp.components.PanelBody, {
							title: __("SEO structured data", 'greyd_hub'),
							initialOpen: false
						}, [
							el( wp.components.ToggleControl, {
								label: __( "Render structured data", 'greyd_hub' ) + ' ',
								checked: atts.renderStructuredData,
								onChange: value => setAttributes({ renderStructuredData: value }),
								help: el( 'p', {}, [
									__("This will render the FAQPage JSON-LD schema markup.", 'greyd_hub') + ' ',
									el( 'a', { href: 'https://developers.google.com/search/docs/appearance/structured-data/faqpage', target: '_blank' }, __("Learn more about the schema.", 'greyd_hub') )
								] )
							} ),
						] ),
				] ),

				//  sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [

					// general
					mode == "" ? [

						// elements
						el( wp.components.PanelBody, { title: __("Elements", 'greyd_hub'), initialOpen: true }, [

							el( greyd.components.SectionControl, {
								title: __("Title", 'greyd_hub'),
								onClick: () => setMode("title")
							} ),
							el( greyd.components.SectionControl, {
								title: __("Content", 'greyd_hub'),
								onClick: () => setMode("content")
							} ),
	
						]),

						// border
						el( greyd.components.StylingControlPanel, {
							title: __("Border", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: false,
							blockProps: props,
							controls: [
								{
									label: __("Border", 'greyd_hub'),
									attribute: "border",
									control: greyd.components.BorderControl
								}
							]
						}),
						// radius
						el( greyd.components.StylingControlPanel, {
							title: __("Border radius", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: false,
							blockProps: props,
							controls: [
								{
									label: __("Border radius", 'greyd_hub'),
									attribute: "border-radius",
									control: greyd.components.DimensionControl,
									type: 'string',
									max: 50,
									labels: {
										"all": __("All corners", "greyd_hub"),
									},
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ]
								}
							]
						}),
						// transition
						el( greyd.components.StylingControlPanel, {
							title: __("Transition", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Duration", 'greyd_hub'),
									attribute: "--accord-transition-duration",
									control: greyd.components.__RangeUnitControl,
									units: ['s'],
									max: { s: 2 },
									step: { s: 0.1 }
								},
								{
									label: __("Timing", 'greyd_hub'),
									attribute: "--accord-transition-timing-function",
									control: wp.components.SelectControl,
									options: [
										{ label: __('None', 'greyd_hub'), value: 'none' },
										{ label: __('Ease-in-out', 'greyd_hub'), value: 'ease-in-out' },
										{ label: __('Ease-in', 'greyd_hub'), value: 'ease-in' },
										{ label: __('Ease-out', 'greyd_hub'), value: 'ease-out' },
										{ label: __('Linear', 'greyd_hub'), value: 'linear' },
									],
									onChange: (value) => {
										// show a snackbar if no duration is set but timing is set to other than none
										if ( !_.has(atts.greydStyles, '--accord-transition-duration') || _.isEmpty( atts.greydStyles['--accord-transition-duration'] ) ) {
											greyd.tools.showSnackbar( __("If no duration is set, the transition has no effect.", 'greyd_hub') );
										}
									}
								}
							]
						}),
					] : null,

					// title
					mode == "title" ? [
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __("Title", 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Alignment", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Alignment", 'greyd_hub'),
									attribute: "--accord-title-align",
									control: greyd.components.ButtonGroupControl,
									options: [
										{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignLeft'), value: 'left' },
										{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignCenter'), value: 'center' },
										{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignRight'), value: 'right' },
									]
								},
							]
						}),
						el( greyd.components.StylingControlPanel, {
							title: __("Colors", 'greyd_hub'),
							initialOpen: false,
							supportsHover: true,
							supportsActive: true,
							blockProps: props,
							controls: [
								{
									label: __("Text color", 'greyd_hub'),
									attribute: "--accord-title-text-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color'
								},
								{
									label: __("Background", 'greyd_hub'),
									attribute: "--accord-title-bg-color",
									control: greyd.components.ColorGradientPopupControl,
									// mode: 'color',
									preventConvertGradient: true,
									contrast: {
										default: _.has(atts.greydStyles, '--accord-title-text-color') ? atts.greydStyles['--accord-title-text-color'] : '',
										hover: _.has(atts.greydStyles, 'hover.--accord-title-text-color') ? atts.greydStyles.hover['--accord-title-text-color'] : ''
									}
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
									label: __("Font size", 'greyd_hub'),
									attribute: "--accord-title-font-size",
									control: greyd.components.RangeUnitControl,
									units: [ 'em', 'px', 'rem' ],
									max: {
										em: 4,
										px: 100,
										rem: 4
									}
								},
								{
									label: __("Icon size", 'greyd_hub'),
									attribute: "--accord-icon-font-size",
									control: greyd.components.RangeUnitControl,
									units: [ 'em', 'px', 'rem' ],
									max: {
										em: 4,
										px: 100,
										rem: 4
									}
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Border", 'greyd_hub'),
							initialOpen: false,
							supportsHover: true,
							supportsActive: true,
							blockProps: props,
							parentAttr: 'titleStyles',
							controls: [
								{
									label: __("Border", 'greyd_hub'),
									attribute: "border",
									control: greyd.components.BorderControl
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Border radius", 'greyd_hub'),
							initialOpen: false,
							supportsHover: true,
							supportsActive: true,
							blockProps: props,
							controls: [
								{
									label: __("Border radius", 'greyd_hub'),
									attribute: "--accord-title-radius",
									control: greyd.components.DimensionControl,
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
									type: "string"
								}
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
									attribute: "--accord-padding",
									control: greyd.components.DimensionControl,
									type: 'string',
								}
							]
						} )

					] : null,

					// content
					mode == "content" ? [
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __("Content", 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),

						el( greyd.components.StylingControlPanel, {
							title: __("Colors", 'greyd_hub'),
							initialOpen: true,
							blockProps: props,
							controls: [
								{
									label: __("Text color", 'greyd_hub'),
									attribute: "--accord-text-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color'
								},
								{
									label: __("Background", 'greyd_hub'),
									attribute: "--accord-bg-color",
									control: greyd.components.ColorGradientPopupControl,
									// mode: 'color',
									preventConvertGradient: true,
									contrast: {
										default: _.has(atts.greydStyles, '--accord-text-color') ? atts.greydStyles['--accord-text-color'] : '',
									}
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Border", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							parentAttr: 'contentStyles',
							controls: [
								{
									label: __("Border", 'greyd_hub'),
									attribute: "border",
									control: greyd.components.BorderControl
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Border radius", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							parentAttr: 'contentStyles',
							controls: [
								{
									label: __("Border radius", 'greyd_hub'),
									attribute: "borderRadius",
									control: greyd.components.DimensionControl,
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
									type: "string"
								}
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
									attribute: "--accord-content-padding",
									control: greyd.components.DimensionControl,
									type: 'string',
								}
							]
						} ),

					] : null,
				] ),

				// preview
				el( 'div', {
					id: atts.anchor,
					className: [ className, atts.greydClass, atts.iconPosition ].join(' ')
				}, [
					el( wp.blockEditor.InnerBlocks, {
						template: [ [ 'greyd/accordion-item', {} ] ],
						allowedBlocks: [ 'greyd/accordion-item' ],
						renderAppender: wp.blockEditor.InnerBlocks.ButtonBlockAppender
					} )
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + ' .wp-block-greyd-accordion-item',
					styles: {
						'': atts.greydStyles,
						' .wp-block-greyd-accordion__title': atts.titleStyles,
						' .wp-block-greyd-accordion__content': atts.contentStyles,
					}
				}),

				// active styles
				el( greyd.components.RenderPreviewStyles, {
					activeSelector: atts.greydClass + ' .wp-block-greyd-accordion__title[aria-expanded="true"]',
					styles: {
						'': atts.greydStyles,
						' ': atts.titleStyles,
						' .wp-block-greyd-accordion__content': atts.contentStyles,
					}
				}),
			];

			
		},

		save: function( props ) {

			const {
				attributes: atts
			} = props;

			return el( 'div', {
				id: atts.anchor,
				className: [ atts.greydClass, atts.iconPosition ].join(' '),
				'data-autoclose': atts.autoClose,
				'data-openfirst': atts.openFirst,
			}, [
				el( wp.blockEditor.InnerBlocks.Content, {} ),
				el( greyd.components.RenderSavedStyles, {
					selector: atts.greydClass + ' .wp-block-greyd-accordion-item',
					styles: {
						'': atts.greydStyles,
						' .wp-block-greyd-accordion__title': atts.titleStyles,
						' .wp-block-greyd-accordion__content': atts.contentStyles,
					}
				} ),
				el( greyd.components.RenderSavedStyles, {
					activeSelector: atts.greydClass + ' .wp-block-greyd-accordion__title[aria-expanded="true"]',
					styles: {
						'': atts.greydStyles,
						' ': atts.titleStyles,
						' .wp-block-greyd-accordion__content': atts.contentStyles,
					}
				} )
			] );
		},

		deprecated: [
			/**
			 * Support for accordion blocks before HTML tag selection was added.
			 */
			{
				supports: {
					anchor: true,
					spacing: {
						margin: true,
						padding: true
					}
				},
				attributes: {
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					iconPosition: { type: 'string', default: '' },
					autoClose: { type: 'boolean', default: false },
					openFirst: { type: 'boolean', default: true },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					titleStyles: { type: 'object', default: {} },
					contentStyles: { type: 'object', default: {} },
					renderStructuredData: { type: 'boolean', default: false }
				},
				providesContext: {
					'greyd/accordion-iconNormal': 'iconNormal',
					'greyd/accordion-iconActive': 'iconActive',
				},
				save: function( props ) {

					const {
						attributes: atts
					} = props;

					return el( 'div', {
						id: atts.anchor,
						className: [ atts.greydClass, atts.iconPosition ].join(' '),
						'data-autoclose': atts.autoClose,
						'data-openfirst': atts.openFirst,
					}, [
						el( wp.blockEditor.InnerBlocks.Content, {} ),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass + ' .wp-block-greyd-accordion-item',
							styles: {
								'': atts.greydStyles,
								' .wp-block-greyd-accordion__title': atts.titleStyles,
								' .wp-block-greyd-accordion__content': atts.contentStyles,
							}
						} ),
						el( greyd.components.RenderSavedStyles, {
							activeSelector: atts.greydClass + ' .wp-block-greyd-accordion__title[aria-expanded="true"]',
							styles: {
								'': atts.greydStyles,
								' ': atts.titleStyles,
								' .wp-block-greyd-accordion__content': atts.contentStyles,
							}
						} )
					] );
				},
			},
			/**
			 * Accordion active state selector.
			 */
			{
				supports: {
					anchor: true,
				},
				attributes: {
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					iconPosition: { type: 'string', default: '' },
					autoClose: { type: 'boolean', default: false },
					openFirst: { type: 'boolean', default: true },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					titleStyles: { type: 'object', default: {} },
					contentStyles: { type: 'object', default: {} }
				},
				providesContext: {
					'greyd/accordion-iconNormal': 'iconNormal',
					'greyd/accordion-iconActive': 'iconActive',
				},
				save: function( props ) {
		
					const {
						attributes: atts
					} = props;
		
					return el( 'div', {
						id: atts.anchor,
						className: [ atts.greydClass, atts.iconPosition ].join(' '),
						'data-autoclose': atts.autoClose,
						'data-openfirst': atts.openFirst,
					}, [
						el( wp.blockEditor.InnerBlocks.Content, {} ),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass+" .wp-block-greyd-accordion-item",
							styles: {
								"": atts.greydStyles,
								" .wp-block-greyd-accordion__title": atts.titleStyles,
								" .wp-block-greyd-accordion__content": atts.contentStyles,
							}
						} )
					] );
				}
			},

			/**
			 * Bug in Accordion active state selector.
			 */
			{
				supports: {
					anchor: true,
					align: true,
					spacing: {
						margin: true,
						padding: true
					}
				},
				attributes: {
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					iconPosition: { type: 'string', default: '' },
					autoClose: { type: 'boolean', default: false },
					openFirst: { type: 'boolean', default: true },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					titleStyles: { type: 'object', default: {} },
					contentStyles: { type: 'object', default: {} }
				},
				providesContext: {
					'greyd/accordion-iconNormal': 'iconNormal',
					'greyd/accordion-iconActive': 'iconActive',
				},
				save: function( props ) {
		
					const {
						attributes: atts
					} = props;

					return el( 'div', {
						id: atts.anchor,
						className: [ atts.greydClass, atts.iconPosition ].join(' '),
						'data-autoclose': atts.autoClose,
						'data-openfirst': atts.openFirst,
					}, [
						el( wp.blockEditor.InnerBlocks.Content, {} ),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass + ' .wp-block-greyd-accordion-item',
							activeSelector: atts.greydClass + ' .wp-block-greyd-accordion__title[aria-expanded="true"]',
							styles: {
								'': atts.greydStyles,
								' .wp-block-greyd-accordion__title': atts.titleStyles,
								' .wp-block-greyd-accordion__content': atts.contentStyles,
							}
						} )
					] );
				}
			}
		]
	} );


	/**
	 * Register the accordion child items.
	 */
	wp.blocks.registerBlockType( 'greyd/accordion-item', {
		title: __("Accordion Section", 'greyd_hub'),
		description: __("Collapsible section with title, icon and contents", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('accordion'),
		category: 'greyd-blocks',
		parent: [ 'greyd/accordion' ],
		supports: {
			anchor: true,
			className: true
		},
		example: {
			attributes: {
				title: __("Accordion Section", 'greyd_hub'),
			},
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: {
						content: __("Displays collapsible content. You can insert other blocks into it.", 'greyd_hub')
					}
				},
			]
		},
		attributes: {
			title: {
				type: 'string',
				source: 'html',
				selector: 'span',
				default: ''
			},
			iconNormal: { type: 'string', default: 'arrow_triangle-down' },
			iconActive: { type: 'string', default: 'arrow_triangle-up' },
			uniqueId: { type: 'string', default: '' },
			titleTag: { type: 'string', default: '' }, /** @since 2.6.0 */
		},
		usesContext: [
			'greyd/accordion-iconNormal',
			'greyd/accordion-iconActive',
			'greyd/accordion-titleTag'
		],

		edit: function( props ) {

			const {
				className,
				setAttributes,
				attributes: atts,
				context
			} = props;

			const iconNormal = context['greyd/accordion-iconNormal'];
			const iconActive = context['greyd/accordion-iconActive'];
			const titleTag = context['greyd/accordion-titleTag'] || ''; /** @since 2.6.0 */
			const newAtts = {};

			if ( typeof iconNormal !== 'undefined' && iconNormal !== atts.iconNormal ) {
				newAtts.iconNormal = iconNormal;
			}
			if ( typeof iconActive !== 'undefined' && iconActive !== atts.iconActive ) {
				newAtts.iconActive = iconActive;
			}
			if ( typeof titleTag !== 'undefined' && titleTag !== atts.titleTag ) {
				newAtts.titleTag = titleTag;
			}
			if ( _.isEmpty(atts.uniqueId) ) {
				newAtts.uniqueId = greyd.tools.generateRandomID();
			}

			if ( !_.isEmpty(newAtts) ) {
				setAttributes(newAtts);
			}

			const [ mode, setMode ] = wp.element.useState("is-active");

			return [

				el( wp.blockEditor.InspectorControls, {}, [

					el( wp.components.PanelBody, { }, [
						el( 'p', { className: "greyd-inspector-help" }, __("You can customize the design of this section in the parent accordion block.", 'greyd_hub') ),
						el( wp.components.Button, {
							variant: 'secondary',
							icon: 'visibility',
							onClick: () => {
								wp.data.dispatch('core/block-editor').selectBlock(
									wp.data.select('core/block-editor').getBlockParents(props.clientId).slice(-1).pop()
								)
							}
						}, __("Focus accordion", 'greyd_hub') )
					] )
				] ),

				// preview
				el( 'div', { 
					id: atts.anchor, 
					className: [ 'wp-block-greyd-accordion-item', className ].join(' ')
				}, [
					el( 'button', {
						className: 'wp-block-greyd-accordion__title',
						'aria-expanded': mode ? 'true' : 'false'
					}, [
						el( wp.blockEditor.RichText, {
							tagName: 'span',
							className: 'title',
							value: atts.title,
							onChange: value => setAttributes({ title: value }),
							placeholder: __("Title", 'greyd_hub'),
							style: { flexGrow: '1' }
						} ),
						el( 'span', {
							className: 'icon ' + (mode === 'is-active' ? iconActive : iconNormal),
							'aria-hidden': 'true',
							onClick: () => setMode( mode === 'is-active' ? '' : 'is-active' ),
							style: {
								cursor: 'pointer',
								padding: 'var(--accord-padding)',
								margin: 'calc( -1 * var(--accord-padding) )',
								fontSize: 'var(--accord-icon-font-size)'
							}
						} )
					] ),
					el( 'div', {
						className: 'wp-block-greyd-accordion__content'/*+( mode === 'is-active' ? '' : ' hidden' )*/
					}, [
						el( wp.blockEditor.InnerBlocks, {
							template: [[ 'core/paragraph', {} ]],
						} )
					] ),
				] )
			];
		},
		save: function( props ) {
			
			const {
				attributes: atts
			} = props;
			const titleTag = _.isEmpty(atts.titleTag) ? 'button' : atts.titleTag; /** @since 2.6.0 */

			const innerContent = [
				el( wp.blockEditor.RichText.Content, {
					tagName: 'span',
					value: atts.title
				} ),
				el( 'span', {
					className: 'icon icon-normal '+( atts.iconNormal ?? 'arrow_triangle-down' ),
					'aria-hidden': 'true'
				} ),
				el( 'span', {
					className: 'icon icon-active '+( atts.iconActive ?? 'arrow_triangle-up' ),
					'aria-hidden': 'true'
				} )
			];


			return el( 'div', {
				id: atts.anchor,
				className: 'wp-block-greyd-accordion-item'
			}, [
				el( 'button', {
					className: 'wp-block-greyd-accordion__title',
					role: 'button', /** @since 2.6.0 */
					type: 'button',
					'aria-expanded': 'false',
					'aria-controls': 'sect-' + atts.uniqueId,
					id: 'title-' + atts.uniqueId
				}, (
					/**
					 * A11y changes:
					 * - different HTML tags still hold a button inside.
					 * @since 2.17.5
					 */
					titleTag === 'button'
					? innerContent
					: [
						el( titleTag, {
							className: 'wp-block-greyd-accordion__title-heading'
						}, innerContent )
					]
				) ),
				el( 'div', {
					className: 'wp-block-greyd-accordion__content',
					role: 'region',
					id: 'sect-' + atts.uniqueId,
					'aria-labelledby': 'title-' + atts.uniqueId,
					/**
					 * A11y changes:
					 * - hide content from screen readers by default if not expanded
					 * @since 2.17.5
					 */
					hidden: true
				}, [
					el( wp.blockEditor.InnerBlocks.Content, {} )
				] ),
			] );
		},


		deprecated: [

			/**
			 * A11y changes:
			 * - hide content from screen readers by default if not expanded
			 * - different HTML tags still hold a button inside.
			 * 
			 * @deprecated since 2.17.5
			 */
			{
				supports: {
					anchor: true,
					className: true
				},
				attributes: {
					title: {
						type: 'string',
						source: 'html',
						selector: 'span',
						default: ''
					},
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					uniqueId: { type: 'string', default: '' },
					titleTag: { type: 'string', default: 'button' },
				},
				save: function( props ) {
			
					const {
						attributes: atts,
					} = props;
					const titleTag = _.isEmpty(atts.titleTag) ? 'button' : atts.titleTag;
		
					return el( 'div', {
						id: atts.anchor,
						className: 'wp-block-greyd-accordion-item'
					}, [
						el( titleTag, {
							className: 'wp-block-greyd-accordion__title',
							role: 'button',
							type: titleTag === 'button' ? 'button' : null,
							'aria-expanded': 'false',
							'aria-controls': 'sect-' + atts.uniqueId,
							id: 'title-' + atts.uniqueId
						}, [
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.title
							} ),
							el( 'span', {
								className: 'icon icon-normal '+( atts.iconNormal ?? 'arrow_triangle-down' ),
								'aria-hidden': 'true'
							} ),
							el( 'span', {
								className: 'icon icon-active '+( atts.iconActive ?? 'arrow_triangle-up' ),
								'aria-hidden': 'true'
							} )
						] ),
						el( 'div', {
							className: 'wp-block-greyd-accordion__content',
							role: 'region',
							id: 'sect-' + atts.uniqueId,
							'aria-labelledby': 'title-' + atts.uniqueId
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} )
						] ),
					] );
				}
			},
			/**
			 * Fixed the titleTag not being registered as an attribute.
			 * 
			 * @deprecated since 2.17.5
			 */
			{
				attributes: {
					title: {
						type: 'string',
						source: 'html',
						selector: 'span',
						default: ''
					},
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					uniqueId: { type: 'string', default: '' },
					titleTag: { type: 'string', default: '' },
				},
				usesContext: [
					'greyd/accordion-iconNormal',
					'greyd/accordion-iconActive',
					'greyd/accordion-titleTag'
				],
				save: function( props ) {
					
					const {
						className,
						attributes: atts,
					} = props;
					const titleTag = _.isEmpty(atts.titleTag) ? 'button' : atts.titleTag;

					return el( 'div', {
						id: atts.anchor,
						className: 'wp-block-greyd-accordion-item'
					}, [
						el( titleTag, {
							className: 'wp-block-greyd-accordion__title',
							role: 'button',
							type: titleTag === 'button' ? 'button' : null,
							'aria-expanded': 'false',
							'aria-controls': 'sect-' + atts.uniqueId,
							id: 'title-' + atts.uniqueId
						}, [
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.title
							} ),
							el( 'span', {
								className: 'icon icon-normal '+( atts.iconNormal ?? 'arrow_triangle-down' ),
								'aria-hidden': 'true'
							} ),
							el( 'span', {
								className: 'icon icon-active '+( atts.iconActive ?? 'arrow_triangle-up' ),
								'aria-hidden': 'true'
							} )
						] ),
						el( 'div', {
							className: 'wp-block-greyd-accordion__content',
							role: 'region',
							id: 'sect-' + atts.uniqueId,
							'aria-labelledby': 'title-' + atts.uniqueId
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} )
						] ),
					] );
				}
			},
			/**
			 * Add type="button" to the accordion title.
			 * 
			 * @deprecated since 2.6.0
			 */
			{
				supports: {
					anchor: true,
					className: true
				},
				attributes: {
					title: {
						type: 'string',
						source: 'html',
						selector: 'span',
						default: ''
					},
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
					uniqueId: { type: 'string', default: '' },
				},
				save: function( props ) {
			
					const {
						attributes: atts,
					} = props;
		
					return el( 'div', {
						id: atts.anchor,
						className: 'wp-block-greyd-accordion-item'
					}, [
						el( 'button', {
							className: 'wp-block-greyd-accordion__title',
							role: 'button',
							'aria-expanded': 'false',
							'aria-controls': 'sect-' + atts.uniqueId,
							id: 'title-' + atts.uniqueId
						}, [
		
							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.title
							} ),
							el( 'span', {
								className: 'icon icon-normal '+( atts.iconNormal ?? 'arrow_triangle-down' ),
								'aria-hidden': 'true'
							} ),
							el( 'span', {
								className: 'icon icon-active '+( atts.iconActive ?? 'arrow_triangle-up' ),
								'aria-hidden': 'true'
							} )
						] ),
						el( 'div', {
							className: 'wp-block-greyd-accordion__content',
							role: 'region',
							id: 'sect-' + atts.uniqueId,
							'aria-labelledby': 'title-' + atts.uniqueId
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} )
						] ),
					] );
				}
			},
			/**
			 * Improved accordion accessibility.
			 * @see https://www.w3.org/WAI/ARIA/apg/patterns/accordion/
			 * 
			 * @deprecated since 1.3.0
			 */
			{
				attributes: {
					title: { type: 'string', default: '' },
					iconNormal: { type: 'string', default: 'arrow_triangle-down' },
					iconActive: { type: 'string', default: 'arrow_triangle-up' },
				},
				save: function( props ) {
					const {
						attributes: atts,
					} = props;

					return el( 'div', {
						id: atts.anchor,
						className: 'wp-block-greyd-accordion-item'
					}, [
						el( 'div', {
							className: 'wp-block-greyd-accordion__title'
						}, [

							el( wp.blockEditor.RichText.Content, {
								tagName: 'span',
								value: atts.title
							} ),
							el( 'span', {
								className: 'icon icon-normal '+atts.iconNormal,
								'aria-hidden': 'true'
							} ),
							el( 'span', {
								className: 'icon icon-active '+atts.iconActive,
								'aria-hidden': 'true'
							} )
						] ),
						el( 'div', {
							className: 'wp-block-greyd-accordion__content'
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} )
						] ),
					] );
				}
			}
		],
	} );

} )( window.wp );