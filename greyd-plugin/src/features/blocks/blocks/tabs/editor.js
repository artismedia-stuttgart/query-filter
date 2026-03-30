( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the accordion wrapper.
	 */
	wp.blocks.registerBlockType( 'greyd/tabs', {
		title: __( 'Tabs', 'greyd_hub' ),
		description: __( "Organize content into easy-to-read tabs.", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('tabs'),
		category: 'greyd-blocks',
		keywords: [ 'trigger', 'toggle', 'tabs', 'panelr' ],
		supports: {
			anchor: true,
			align: true,
			defaultStylePicker: false,
			spacing: {
				margin: true,
				padding: true
			}
		},
		styles: [
			{
				name: 'tabs',
				label: __( "Classic", 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'chips',
				label: __( 'Chips', 'greyd_hub' )
			},
			{
				name: 'prim',
				label: __( "Primary buttons", 'greyd_hub' )
			},
			{
				name: 'sec',
				label: __( "Secondary buttons", 'greyd_hub' )
			},
			{
				name: 'trd',
				label: __( "Alternative buttons", 'greyd_hub' )
			}
		],
		example: {
			attributes: {
			},
			innerBlocks: [
				{
					name: 'greyd/tab',
					attributes: {
						title: __('Tab 1', 'greyd_hub'),
						active: true,
					},
					innerBlocks: [
						{
							name: 'core/paragraph',
							attributes: {
								content: __("Organize content into neat tabs and enable easy switching between different information on your website.", 'greyd_hub')
							}
						},
					]
				},
				{
					name: 'greyd/tab',
					attributes: {
						title: __('Tab 2', 'greyd_hub'),
					}
				},
				{
					name: 'greyd/tab',
					attributes: {
						title: __('Tab 3', 'greyd_hub'),
					}
				}
			]
		},
		attributes: {
			iconPosition: { type: 'string', default: '' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
			customStyles: { type: 'object', default: {} },
			transition: { type: 'string', default: '' },
			stackOnMobile: { type: 'boolen', default: false },

			activeTab: { type: 'string', default: '' }
		},
		providesContext: {
			'greyd/tab-iconPosition': 'iconPosition',
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

			let extraClass = '';
			if ( className.indexOf( 'is-style-prim' ) > -1 || className.indexOf( 'is-style-sec' ) > -1 || className.indexOf( 'is-style-trd' ) > -1 ) {
				extraClass = 'button';
			}
			const tabClassNames = [ extraClass, atts.size, className.replace( "wp-block-greyd-tabs", "" ).trim(), 'greyd_tab' ].join( ' ' );
			const defaultClass = className.indexOf( 'is-style-' ) > -1 ? '' : 'is-style-tabs';

			const [ mode, setMode ] = wp.element.useState( "" );
			if ( !props.isSelected && mode != "" ) setMode( "" );

			const container = wp.data.select( "core/block-editor" ).getBlocksByClientId( props.clientId )[ 0 ];
			const tabs = container ? container.innerBlocks : [];

			const isWithinDynamic = _.has(atts, 'dynamic_parent') && atts.dynamic_parent.length;

			if ( !atts.activeTab && tabs.length && !isWithinDynamic ) {
				if ( ! wp.data.select('core/block-editor').getBlockAttributes( tabs[ 0 ].clientId ).active ) {
					wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( tabs[ 0 ].clientId, { active: true } );
				}
			}
			else if ( !isWithinDynamic ) {
				if ( ! wp.data.select('core/block-editor').getBlockAttributes( atts.activeTab )?.active ) {
					wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( atts.activeTab, { active: true } );
				}
			}

			const ClickThroughWrapper = wp.components.withFocusOutside(
				class extends wp.element.Component {
					render() {
						return el(
							'div',
							_.omit( _.clone( this.props ), [ "children" ] ),
							_.has( this.props, 'children' ) ? this.props.children : null
						);
					}
				}
			);

			var { tabsPreview } = wp.element.useMemo( () => {
				return {
					tabsPreview: tabs?.map( ( tab, index ) => {
						const tabAtts = tab.attributes;

						return el( 'div', {
							className: tabClassNames + ( tab.attributes.active ? ' is-active' : '' ),
							onClick: () => {

								console.log( 'update parent "activeTab" from wrapper' );
								props.setAttributes( { activeTab: tab.clientId } );

								console.log( 'update child tabs "active" from wrapper' );
								/**
								 * @param {string|string[]} clientIds Block client IDs.
								 * @param {Object} attributes         Block attributes to be merged. Should be keyed by clientIds if uniqueByBlock is true.
								 * @param {boolean} uniqueByBlock     true if each block in clientIds array has a unique set of attributes
								 * 
								 * @link https://developer.wordpress.org/block-editor/reference-guides/data/data-core-block-editor/#updateblockattributes
								 */
								wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes(
									tabs.map( t => t.clientId ),
									tabs.reduce( ( a, t ) => ({ ...a, [t.clientId]: { active: t.clientId === tab.clientId } }), {}),
									true
								);
							}
						}, [
							atts.iconPosition === 'hasiconleft' ? el( 'span', {
								className: tab.attributes.active ? tabAtts.iconActive : tabAtts.iconNormal,
								'aria-hidden': 'true',

								style: {
									cursor: 'pointer',
									fontSize: 'var(--tabs-icon-font-size)'
								}
							} ) : null,
							el( wp.blockEditor.RichText, {
								tagName: 'span',
								className: 'title',
								clientId: tab.clientId,
								value: tabAtts.title,
								onChange: value => {
									wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( tab.clientId, { title: value } );
								},
								placeholder: __( "Title", 'greyd_hub' ),
								style: { flexGrow: '1' }
							} ),
							!atts.iconPosition ? el( 'span', {
								className: tab.attributes.active ? tabAtts.iconActive : tabAtts.iconNormal,
								'aria-hidden': 'true',

								style: {
									cursor: 'pointer',
									fontSize: 'var(--tabs-icon-font-size)'
								}
							} ) : null
						] );

					} )
				};
			}, [ tabs ] );

			return [

				// sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					// icon
					el( wp.components.PanelBody, {
						title: __( "Behavior", 'greyd_hub' ),
						initialOpen: true
					}, [

						el( wp.components.SelectControl, {
							label: __( "Transition", 'greyd_hub' ),
							value: atts.transition,
							options: [
								{ label: __( "No animation", 'greyd_hub' ), value: "" },
								{ label: __( "Fade", 'greyd_hub' ), value: "fade" },
								{ label: __("Swipe horizontally", 'greyd_hub'), value: "horizontal" },
								{ label: __("Move vertically", 'greyd_hub'), value: "vertical" },
							],
							onChange: ( value ) => props.setAttributes( { transition: value } )
						} ),
					] ),
				] ),

				//  sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [

					mode == "" ? [
						// elements
						el( wp.components.PanelBody, { title: __( "Elements", 'greyd_hub' ), initialOpen: true }, [

							el( greyd.components.SectionControl, {
								title: __( 'Tabs', 'greyd_hub' ),
								onClick: () => setMode( "tabs" )
							} ),
							el( greyd.components.SectionControl, {
								title: __( "Content", 'greyd_hub' ),
								onClick: () => setMode( "content" )
							} ),
						] ),
					] : null,

					mode == "tabs" ? [
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __( 'Tabs', 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( "" ),
							isHeader: true
						} ),
						// layout
						el( greyd.components.StylingControlPanel, {
							title: __( 'Layout', 'greyd_hub' ),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __( "Alignment", 'greyd_hub' ),
									attribute: "--tabs-align-tabs",
									control: greyd.components.ButtonGroupControl,
									options: [
										{ label: __( "Left", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignLeft' ), value: 'flex-start' },
										{ label: __( "Center", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignCenter' ), value: 'center' },
										{ label: __( "Right", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignRight' ), value: 'flex-end' },
										{ label: __( "Spreaded", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignSpaceBetween' ), value: 'space-between' },
									]
								},
								{
									label: __( "Space between", 'greyd_hub' ),
									attribute: "--tabs-tab-gap",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
								},
							]
						} ),
						// responsive
						el( wp.components.PanelBody, {
							title: __( "Responsive behavior", 'greyd_hub' ),
							initialOpen: false,
						}, [
							el( wp.components.ToggleControl, {
								label: __( "Stack on mobile devices", 'greyd_hub' ),
								checked: atts.stackOnMobile,
								onChange: ( value ) => {
									console.log( value );
									props.setAttributes( { stackOnMobile: value } )
								}
							} )
						] ),
						// icon
						el( wp.components.PanelBody, {
							title: __( 'Icon', 'greyd_hub' ),
							initialOpen: false,
						}, [
							el( greyd.components.ButtonGroupControl, {
								label: __( 'Position', 'greyd_hub' ),
								value: atts.iconPosition,
								onChange: value => setAttributes( { iconPosition: value } ),
								options: [
									{ label: __( "Left", 'greyd_hub' ), value: 'hasiconleft' },
									{ label: __( "Right", 'greyd_hub' ), value: '' },
								]
							} ),
							el( wp.components.Tip, { }, __("You can add icons individually per tab.", 'greyd_hub') )
						] ),
						el( greyd.components.CustomButtonStyles, {
							blockProps: props,
							parentAttr: 'customStyles',
							supportsActive: true
						} )
					] : null,

					mode == "content" ? [
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __( "Content", 'greyd_hub' ),
							icon: 'arrow-left-alt',
							buttonText: __( "Back", 'greyd_hub' ),
							onClick: () => setMode( "" ),
							isHeader: true
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __( "Colors", 'greyd_hub' ),
							initialOpen: true,
							blockProps: props,
							controls: [
								{
									label: __( "Text color", 'greyd_hub' ),
									attribute: "--tabs-content-color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color',
									preventConvertGradient: true
								},
								{
									label: __( "Background color", 'greyd_hub' ),
									attribute: "--tabs-content-background",
									control: greyd.components.ColorGradientPopupControl,
									preventConvertGradient: true
								},
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __( 'Layout', 'greyd_hub' ),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __( "Width", 'greyd_hub' ),
									attribute: "--tabs-content-width",
									control: greyd.components.RangeUnitControl,
									max: 1400
								},
								{
									label: __( "Alignment", 'greyd_hub' ),
									attribute: "--tabs-content-align",
									control: greyd.components.ButtonGroupControl,
									options: [
										{ label: __( "Left", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignLeft' ), value: 'flex-start' },
										{ label: __( "Center", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignCenter' ), value: 'center' },
										{ label: __( "Right", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'alignRight' ), value: 'flex-end' },
									]
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __( "Spaces", 'greyd_hub' ),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __( 'Padding', 'greyd_hub' ),
									attribute: "--tabs-content-padding",
									control: greyd.components.DimensionControl,
									type: "string"
								}
							]
						} ),

						// border radius
						el( greyd.components.StylingControlPanel, {
							title: __( "Border radius", 'greyd_hub' ),
							initialOpen: false,
							blockProps: props,
							controls: [ {
								label: __( "Border radius", 'greyd_hub' ),
								attribute: "--tabs-content-radius",
								control: greyd.components.DimensionControl,
								labels: {
									"all": __( "All corners", "greyd_hub" ),
								},
								sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
								type: "string"
							} ]
						} ),
					] : null
				] ),

				// preview
				el( 'div', {
					id: atts.anchor,
					className: [ className, defaultClass, atts.greydClass ].join( ' ' ),
					"data-transition": atts.transition,
					"data-overflow": atts.stackOnMobile ? "stack" : ""
				}, [
					el( 'div', {
						className: 'tabs',
					}, [
						// select steps
						tabsPreview,
						// new tab
						el( 'div', {
							className: tabClassNames,
							onClick: function () {
								const innerCount = wp.data.select( "core/block-editor" ).getBlocksByClientId( props.clientId )[ 0 ].innerBlocks.length;
								let block = wp.blocks.createBlock( "greyd/tab" );
								wp.data.dispatch( "core/block-editor" ).insertBlock( block, innerCount, props.clientId );
							},
						}, "+" )
					] ),
					// custom styles
					el( greyd.components.RenderPreviewStyles, {
						selector: props.attributes.greydClass + " .greyd_tab",
						activeSelector: props.attributes.greydClass + " .greyd_tab.is-active",
						styles: {
							"": props.attributes.customStyles,
						},
						important: true
					} ),

					el( 'div', {
						className: 'panels' + (
							_.has( atts.greydStyles, '--tabs-content-background' ) ? ' has-background-color' : ''
						) + (
							_.has( atts.greydStyles, '--tabs-content-color' ) ? ' has-text-color' : ''
						),
					},[
						// el( ClickThroughWrapper, {
						// 	onClick: ( e ) => {
						// 		// console.log(e);
						// 	}
						// },
							el( wp.blockEditor.InnerBlocks, {
								allowedBlocks: [ 'greyd/tab' ],
								template: [ [ 'greyd/tab', { active: true, title: 'Tab 1' } ] ],
								renderAppender: false
							} )
						// )
					]),

				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						".wp-block-greyd-tabs": atts.greydStyles,
					}
				} ),
			];
		},

		save: function ( props ) {

			const {
				attributes: atts
			} = props;
			
			const defaultClass = atts.className && atts.className.length && atts.className.indexOf( 'is-style-' ) > -1 ? '' : 'is-style-tabs';

			const blockProps = wp.blockEditor.useBlockProps.save( {
				className: [ defaultClass, atts.greydClass, atts.iconPosition ].join( ' ' ),
				"data-transition": atts.transition,
				...(
					atts.stackOnMobile ? { "data-overflow": "stack" } : {}
				)
			} );

			return el( 'div', blockProps, [

				el( 'div', {
					className: 'tabs',
					role: 'tablist'
				} ),
				el( greyd.components.RenderSavedStyles, {
					selector: props.attributes.greydClass + " .greyd_tab",
					activeSelector: props.attributes.greydClass + " .greyd_tab.is-active",
					styles: {
						"": props.attributes.customStyles,
					},
					important: true
				} ),
				el( 'div', {
					className: 'panels' + (
						_.has( atts.greydStyles, '--tabs-content-background' ) ? ' has-background-color' : ''
					) + (
						_.has( atts.greydStyles, '--tabs-content-color' ) ? ' has-text-color' : ''
					),
				}, [
					el( wp.blockEditor.InnerBlocks.Content, {} ),
				] ),
				el( greyd.components.RenderSavedStyles, {
					selector: atts.greydClass + ".wp-block-greyd-tabs",
					styles: {
						"": atts.greydStyles,
					}
				} )
			] );
		},

		deprecated: [
			/**
			 * Alignment support.
			 */
			{
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				attributes: {
					iconPosition: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					customStyles: { type: 'object', default: {} },
					transition: { type: 'string', default: '' },
		
					activeTab: { type: 'string', default: '' }
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
					
					const defaultClass = atts.className && atts.className.length && atts.className.indexOf( 'is-style-' ) > -1 ? '' : 'is-style-tabs';
		
					return el( 'div', {
						id: atts.anchor,
						className: [ atts.className, defaultClass, atts.greydClass, atts.iconPosition ].join( ' ' ),
						"data-transition": atts.transition
						// className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size, 'greyd_tab' ].join(' ')
					}, [
		
						el( 'div', {
							className: 'tabs',
							role: 'tablist'
						} ),
						el( greyd.components.RenderSavedStyles, {
							selector: props.attributes.greydClass + " .greyd_tab",
							activeSelector: props.attributes.greydClass + " .greyd_tab.is-active",
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} ),
						el( 'div', {
							className: 'panels' + (
								_.has( atts.greydStyles, '--tabs-content-background' ) ? ' has-background-color' : ''
							) + (
								_.has( atts.greydStyles, '--tabs-content-color' ) ? ' has-text-color' : ''
							),
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} ),
						] ),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass + ".wp-block-greyd-tabs",
							styles: {
								"": atts.greydStyles,
							}
						} )
					] );
				}
			},

			/**
			 * add 'has-text-color' and 'has-background-color' classes to the panels
			 * if the color is set in the inspector.
			 * @since 1.7.4
			 */
			{
				attributes: {
					iconPosition: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					customStyles: { type: 'object', default: {} },
					transition: { type: 'string', default: '' },

					activeTab: { type: 'string', default: '' }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
					
					const defaultClass = atts.className && atts.className.length && atts.className.indexOf( 'is-style-' ) > -1 ? '' : 'is-style-tabs';
		
					return el( 'div', {
						id: atts.anchor,
						className: [ atts.className, defaultClass, atts.greydClass, atts.iconPosition ].join( ' ' ),
						"data-transition": atts.transition
					}, [
		
						el( 'div', {
							className: 'tabs',
							role: 'tablist'
						} ),
						el( greyd.components.RenderSavedStyles, {
							selector: props.attributes.greydClass + " .greyd_tab",
							activeSelector: props.attributes.greydClass + " .greyd_tab.is-active",
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} ),
						el( 'div', {
							className: 'panels',
						}, [
							el( wp.blockEditor.InnerBlocks.Content, {} ),
						] ),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass + ".wp-block-greyd-tabs",
							styles: {
								"": atts.greydStyles,
							}
						} )
					] );
				}
			},
			/**
			 * Do not save the word {{greyd_tabs}} unescaped, it will be found by 
			 * the search. Replace the entire <div> instead.
			 * @since 1.7.0
			 */
			{
				attributes: {
					iconPosition: { type: 'string', default: '' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
					customStyles: { type: 'object', default: {} },
					transition: { type: 'string', default: '' },

					activeTab: { type: 'string', default: '' }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function ( props ) {

					const {
						attributes: atts
					} = props;
					
					const defaultClass = atts.className && atts.className.length && atts.className.indexOf( 'is-style-' ) > -1 ? '' : 'is-style-tabs';
		
					return el( 'div', {
						id: atts.anchor,
						className: [ atts.className, defaultClass, atts.greydClass, atts.iconPosition ].join( ' ' ),
						"data-transition": atts.transition
						// className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size, 'greyd_tab' ].join(' ')
					}, [
		
						el( 'div', {
							className: 'tabs',
							role: 'tablist'
						}, [
							"{{greyd_tabs}}"
						] ),
						el( greyd.components.RenderSavedStyles, {
							selector: props.attributes.greydClass + " .greyd_tab",
							activeSelector: props.attributes.greydClass + " .greyd_tab.is-active",
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} ),
						el( 'div', {
							className: "panels",
						},
							el( wp.blockEditor.InnerBlocks.Content, {} ),
						),
						el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass + ".wp-block-greyd-tabs",
							styles: {
								"": atts.greydStyles,
							}
						} )
					]
					);
				
				}
			}
		]
	} );


	/**
	 * Register the accordion child items.
	 */
	wp.blocks.registerBlockType( 'greyd/tab', {
		title: __( 'Tab', 'greyd_hub' ),
		description: __( "Collapsible section with title, icon and contents", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('tab'),
		category: 'greyd-blocks',
		parent: [ 'greyd/tabs' ],
		supports: {
			anchor: true,
			className: true
		},
		example: {
			attributes: {
				title: __('Tab 1', 'greyd_hub'),
				active: true,
			},
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: {
						content: __("Organize content into neat tabs and enable easy switching between different information on your website.", 'greyd_hub')
					}
				},
			]
		},
		attributes: {
			active: { type: "boolean", default: false },
			title: {
				type: 'string', default: ''
			},
			iconNormal: { type: 'string', default: '' },
			iconActive: { type: 'string', default: '' },
			uniqueId: { type: 'string', default: '' },
		},
		usesContext: [
			'greyd/tab-iconPosition',
		],

		edit: function ( props ) {

			const {
				className,
				setAttributes,
				attributes: atts,
				context
			} = props;

			const iconPosition = context[ 'greyd/tab-iconPosition' ];
			const parentId = wp.data.select( "core/block-editor" ).getBlockParentsByBlockName( props.clientId, "greyd/tabs" )[ 0 ];

			const isWithinDynamic = _.has(atts, 'dynamic_parent') && atts.dynamic_parent.length;

			if (
				//  we only update from the selected tab	
				props.isSelected
				// we only update if this tab is not already active
				&& !atts.active
				// we only change attributes if the block is not within a dynamic block
				&& !isWithinDynamic
			) {
				// ...set active to true
				setAttributes( { active: true } );

				const parent = wp.data.select( "core/block-editor" ).getBlocksByClientId( parentId )[ 0 ];

				// ...set all other tabs to active: false
				const tabs = parent.innerBlocks;
				/**
				 * @param {string|string[]}
				 * @param {Object}
				 * @param {boolean}
				 * 
				 * @link https://developer.wordpress.org/block-editor/reference-guides/data/data-core-block-editor/#updateblockattributes
				 */
				wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes(
					tabs.map( t => t.clientId ).filter( t => t !== props.clientId ),
					tabs.reduce( ( a, t ) => ({ ...a, [t.clientId]: { active: false } }), {}),
					true
				);

				// if the parent activeTab is not this tab, set it to this tab
				if ( parent.attributes.activeTab !== props.clientId ) {
					wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( parentId, { activeTab: props.clientId } );
				}
			}

			if ( iconPosition !== atts.iconNormal && !isWithinDynamic ) {
				setAttributes( {
					iconPosition: iconPosition,
				} );
			}

			if ( _.isEmpty( atts.uniqueId ) && !isWithinDynamic ) {
				setAttributes( {
					uniqueId: greyd.tools.generateRandomID()
				} );
			}

			const hasChildBlocks = greyd.tools.hasChildBlocks( props.clientId );

			return [

				el( wp.blockEditor.InspectorControls, {}, [

					el( wp.components.PanelBody, {}, [
						el( 'p', { className: "greyd-inspector-help" }, __( "You can customize the design of this section in the parent tabs block.", 'greyd_hub' ) ),
						el( wp.components.Button, {
							variant: 'secondary',
							icon: 'visibility',
							onClick: () => {
								wp.data.dispatch( 'core/block-editor' ).selectBlock(
									wp.data.select( 'core/block-editor' ).getBlockParents( props.clientId ).slice( -1 ).pop()
								);
							}
						}, __( 'Tabs', 'greyd_hub' ) ),
						el( wp.components.Button, {
							variant: 'tertiary',
							icon: 'no',
							onClick: () => {
								// setTimeout(function() {
								// 	wp.data.dispatch('core/block-editor').selectBlock(parentId);
								// 	$('#block-'+parentId).focus();
								// }, 0);
								wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( parentId, { activeTab: '' } );
								wp.data.dispatch( "core/block-editor" ).removeBlock( props.clientId );
							}
						}, __( "Remove tab", 'greyd_hub' ) )
					] ),
					// icon
					el( wp.components.PanelBody, {
						title: __( 'Icon', 'greyd_hub' ),
						initialOpen: true
					}, [
						el( greyd.components.IconPicker, {
							label: __( "Normal icon", 'greyd_hub' ),
							value: atts.iconNormal,
							onChange: value => {
								setAttributes( { iconNormal: value } );
								setTimeout( function () {
									wp.data.dispatch( 'core/block-editor' ).selectBlock( parentId );
									$( '#block-' + parentId ).focus();
								}, 0 );
							}
						} ),
						el( greyd.components.IconPicker, {
							label: __( "Active icon", 'greyd_hub' ),
							value: atts.iconActive,
							onChange: value => {
								setAttributes( { iconActive: value } );
								setTimeout( function () {
									wp.data.dispatch( 'core/block-editor' ).selectBlock( parentId );
									$( '#block-' + parentId ).focus();
								}, 0 );
							}
						} ),
					] )
				] ),

				// preview
				el( 'div', {
					id: "tabpanel_" + atts.uniqueId,
					className: [ className, atts.active ? "is-active" : "" ].join( ' ' )
				}, [
					el( 'div', {
						className: 'wp-block-greyd-tabs__content'
					}, [
						el( wp.blockEditor.InnerBlocks, {
							renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender
						} )
					] ),
				] ),

			];
		},
		save: function ( props ) {

			const {
				className,
				attributes: atts,
			} = props;

			return el( wp.element.Fragment, {}, [
				el( 'div', {
					id: "tabpanel_" + atts.uniqueId,
					className: "panel",
					role: 'tabpanel',
					"aria-labelledby": "tab_" + atts.uniqueId,
					tabindex: '0'
				},
					el( wp.blockEditor.InnerBlocks.Content, {} )
				)
			] );
		},

		deprecated: [
			{
				attributes: {
					active: { type: "boolean", default: false },
					title: {
						type: 'string', default: ''
					},
					iconNormal: { type: 'string', default: '' },
					iconActive: { type: 'string', default: '' },
					uniqueId: { type: 'string', default: '' },
				},
				supports: {
					anchor: true,
					className: true
				},
				save: function ( props ) {

					const {
						className,
						attributes: atts,
					} = props;

					return el( wp.element.Fragment, {}, [
						el( 'div', {
							id: "tabpanel_" + atts.uniqueId,
							// tabindex: "0",
							className: "panel",
							role: 'tabpanel',
							"aria-labelledby": "tab_" + atts.uniqueId
						},
							el( wp.blockEditor.InnerBlocks.Content, {} )
						)
					] );
				}
			}
		]
	} );

} )( window.wp );