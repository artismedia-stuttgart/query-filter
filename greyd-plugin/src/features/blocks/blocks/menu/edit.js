
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __, _x } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the menu wrapper.
	 */
	wp.blocks.registerBlockType( 'greyd/menu', {
		title: __( "Menu", 'greyd_hub' ),
		description: __( "Integration of a WordPress menu, e.g. in the footer", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('menu'),
		category: 'greyd-blocks',
		inserter: !!greyd.data?.is_greyd_classic,

		edit: function ( props ) {

			// don't change the class on every re-render of the content
			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const menu_options = [
				{ value: '', label: _x( "Select menu", 'small', 'greyd_hub' ) },
				...greyd.data.nav_menus.map( item => {
					return { value: item.slug, label: item.title };
				} )
			];

			/**
			 * This is used for backward compatibility.
			 * @todo Can be remove in future versions.
			 * @deprecated since 1.2.0
			 */
			if ( _.has( props.attributes, 'greydStyles.marginRight' ) ) {
				_.omit( props.attributes.greydStyles.marginRight );
			}

			const emptyPlaceholder = () => {
				return el( wp.components.Placeholder, {
					// render placeholder
					// icon: greyd.tools.getBlockIcon('menu'),
					// label: __( "Select menu", 'greyd_hub' ),
					instructions: __( "Select a menu from the list or create a new one.", 'greyd_hub' ),
				}, el( wp.components.BaseControl, {},
					el( greyd.components.OptionsControl, {
						style: { margin: '0 0 10px', alignSelf: 'center' },
						value: '',
						options: menu_options,
						onChange: ( value ) => props.setAttributes( { menu: value } ),
					} ),
				),
					el( "div", {
						style: { margin: '0 10px 10px', alignSelf: 'center' },
					}, __( "or", 'greyd_hub' ) ),
					el( wp.components.Button, {
						className: 'is-secondary',
						style: { margin: '0 0 10px', alignSelf: 'center' },
						onClick: function () { window.open( 'nav-menus.php?action=edit&menu=0', '_blank' ); },
					}, __( "Create new menu", 'greyd_hub' ) )
				);
			};

			return [

				// sidebar
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					el( wp.components.PanelBody, {
						title: __( "Select menu", 'greyd_hub' ),
						initialOpen: true
					}, [
						el( greyd.components.OptionsControl, {
							value: props.attributes.menu,
							options: menu_options,
							onChange: ( value ) => props.setAttributes( { menu: value, greydClass: greyd.tools.generateGreydClass() } ), // change greydClass when this attribute changes
						} ),
						el( wp.components.ToggleControl, {
							label: __( "Display subitems", 'greyd_hub' ),
							checked: !props.attributes.depth,
							onChange: ( value ) => props.setAttributes( { depth: !value, greydClass: greyd.tools.generateGreydClass() } ), // change greydClass when this attribute changes
						} )
					] ),
					// listStyles
					el( greyd.components.StylingControlPanel, {
						title: __( 'Layout', 'greyd_hub' ),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						parentAttr: 'listStyles',
						controls: [
							{
								label: __( "Arrangement", 'greyd_hub' ),
								attribute: "flexDirection",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ value: 'column', label: __( "Below each other", 'greyd_hub' ) },
									{ value: 'row', label: __( "Next to each other", 'greyd_hub' ) },
								]
							},
							{
								label: __( "Alignment", 'greyd_hub' ),
								attribute: "alignItems",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ value: 'flex-start', label: __( "Left", 'greyd_hub' ) },
									{ value: 'center', label: __( "Center", 'greyd_hub' ) },
									{ value: 'flex-end', label: __( "Right", 'greyd_hub' ) },
								]
							},
						]
					} ),
				] ),

				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// itemStyles
					el( greyd.components.StylingControlPanel, {
						title: __( "Sizes & Spaces", 'greyd_hub' ),
						supportsResponsive: true,
						blockProps: props,
						parentAttr: 'listStyles',
						controls: [
							{
								label: __( "Space between", 'greyd_hub' ),
								attribute: "--gap",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', 'em' ],
								max: {
									em: 2,
									px: 40
								}
							},
							// {
							// 	label: __("Margin bottom", 'greyd_hub'),
							// 	attribute: "marginBottom",
							// 	control: greyd.components.RangeUnitControl,
							// 	units: [ 'px', 'em' ],
							// 	max: {
							// 		em: 2,
							// 		px: 40
							// 	}
							// },
							// {
							// 	label: __('Abstand daneben', 'greyd_hub'),
							// 	attribute: "marginRight",
							// 	control: greyd.components.RangeUnitControl,
							// 	units: [ 'px', 'em' ],
							// 	max: {
							// 		em: 3,
							// 		px: 60
							// 	}
							// },
							{
								label: __( "Font size", 'greyd_hub' ),
								attribute: "fontSize",
								control: greyd.components.RangeUnitControl,
								units: [ 'px', 'em', '%' ],
								max: {
									em: 3,
									px: 60,
									'%': 200
								}
							},
						]
					} ),
					// subStyles
					props.attributes.depth ? null : el( greyd.components.StylingControlPanel, {
						title: __( "Subitems", 'greyd_hub' ),
						supportsResponsive: true,
						blockProps: props,
						parentAttr: 'subStyles',
						controls: [
							{
								label: __( "Font size", 'greyd_hub' ),
								attribute: "fontSize",
								control: greyd.components.RangeUnitControl,
								units: [ '%', 'px', 'em' ],
								max: {
									em: 3,
									px: 60,
									'%': 100
								}
							},
							{
								label: __( "Indentation", 'greyd_hub' ),
								attribute: "marginLeft",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', 'em' ],
								max: {
									em: 2,
									px: 40
								}
							},
							{
								label: __( "Space between", 'greyd_hub' ),
								attribute: "--gap",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', 'em' ],
								max: {
									em: 2,
									px: 40
								}
							},
							{
								label: __( "Margin bottom", 'greyd_hub' ),
								attribute: "marginBottom",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', 'em' ],
								max: {
									em: 2,
									px: 40
								}
							},
						]
					} ),
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass,
					styles: {
						"": greyd.tools.fixResponsiveFlexAlignment( { ...props.attributes.listStyles }, { columnAlignment: props.attributes.depth ? 'center' : 'flex-start' } ),
						" ul.sub-menu": props.attributes.subStyles,
					},
					// important: true
				} ),
				// preview
				_.isEmpty( props.attributes.menu ) ? emptyPlaceholder() : el( wp.serverSideRender, {
					block: 'greyd/menu',
					attributes: props.attributes,
					EmptyResponsePlaceholder: emptyPlaceholder
					// EmptyResponsePlaceholder: ["span", {className: 'test'}, "empty shit"]
				} ),
			];
		},
		save: function ( props ) {
			return el( greyd.components.RenderSavedStyles, {
				selector: props.attributes.greydClass,
				styles: {
					", .menu-item": greyd.tools.fixResponsiveFlexAlignment(
						{ ...props.attributes.listStyles },
						{ columnAlignment: props.attributes.depth ? 'center' : 'flex-start' }
					),
					" ul.sub-menu": props.attributes.subStyles,

				},
				important: true
			} );
		},

		deprecated: [
			/**
			 * RenderSavedStylesDeprecated
			 * @deprecated since 1.1.2
			 */
			{
				attributes: {
					anchor: { type: 'string' },
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'object' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					menu: { type: 'string', default: '' },
					depth: { type: 'boolean', default: 0 },
					greydClass: { type: 'string' },
					listStyles: { type: 'object' },
					itemStyles: { type: 'object' }, // deprecated
					subStyles: { type: 'object' },
					lock: { type: 'object' },
				},

				migrate: function ( attributes ) {

					let newStyles = { ...attributes.listStyles, ...attributes.itemStyles };
					let flexDirection = _.has( newStyles, 'flexDirection' ) ? newStyles.flexDirection : 'column';

					if ( flexDirection == 'row' && _.has( newStyles, 'marginRight' ) ) {
						newStyles[ '--gap' ] = newStyles.marginRight;
					}
					else if ( _.has( newStyles, 'marginBottom' ) ) {
						newStyles[ '--gap' ] = newStyles.marginBottom;
					}

					if ( _.has( newStyles, 'marginRight' ) ) _.omit( newStyles.marginRight );
					if ( _.has( newStyles, 'marginBottom' ) ) _.omit( newStyles.marginBottom );

					// migrate responsive styles
					if ( _.has( newStyles, 'responsive' ) ) {

						Object.keys( newStyles.responsive ).forEach( device => {
							let deviceStyles = newStyles.responsive[ device ];

							if ( _.has( deviceStyles, 'flexDirection' ) ) {
								flexDirection = deviceStyles.flexDirection;
							}

							if ( flexDirection == 'row' && _.has( deviceStyles, 'marginRight' ) ) {
								deviceStyles[ '--gap' ] = deviceStyles.marginRight;
							}
							else if ( _.has( deviceStyles, 'marginBottom' ) ) {
								deviceStyles[ '--gap' ] = deviceStyles.marginBottom;
							}

							if ( _.has( deviceStyles, 'marginRight' ) ) _.omit( deviceStyles.marginRight );
							if ( _.has( deviceStyles, 'marginBottom' ) ) _.omit( deviceStyles.marginBottom );

							newStyles.responsive[ device ] = deviceStyles;
						} );
					}

					attributes.listStyles = newStyles;
					_.omit( attributes.itemStyles );

					return attributes;
				},

				isEligible: function ( attributes ) {
					return (
						_.has( attributes, 'itemStyles' ) ||
						_.has( attributes, 'listStyles.responsive' ) ||
						_.has( attributes, 'subStyles.responsive' )
					);
				},

				save: function ( props ) {
					return el( greyd.components.RenderSavedStylesDeprecated, {
						selector: props.attributes.greydClass,
						styles: {
							"": greyd.tools.fixResponsiveFlexAlignment(
								{ ...props.attributes.listStyles },
								{ columnAlignment: props.attributes.depth ? 'center' : 'flex-start' }
							),
							" > li a": props.attributes.itemStyles,
							" ul.sub-menu": props.attributes.subStyles,
						},
						important: true
					} );
				}
			},
		],

		transforms: {
			to: [
				{
					type: 'block',
					blocks: [ 'core/navigation' ],
					transform: function ( attributes, innerBlocks ) {
						console.log( 'convert menu to navigation' );
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = {};
						if ( _.has( attributes.listStyles, 'flexDirection' ) ) {
							if ( attributes.listStyles.flexDirection == 'row' ) newatts.orientation = 'horizontal';
							else newatts.orientation = 'vertical';
						}

						var inner = [];
						if ( _.has( attributes, 'menu' ) ) {
							var nav_menu = false;
							for ( var i = 0; i < greyd.data.nav_menus.length; i++ ) {
								if ( greyd.data.nav_menus[ i ].slug == attributes[ "menu" ] ) {
									nav_menu = greyd.data.nav_menus[ i ];
									break;
								}
							}
							if ( nav_menu ) {
								for ( var j = 0; j < nav_menu.items.length; j++ ) {
									var sub = [];
									if ( _.has( nav_menu.items[ j ], 'items' ) ) {
										for ( var k = 0; k < nav_menu.items[ j ].items.length; k++ ) {
											sub.push( wp.blocks.createBlock( "core/navigation-link", { label: nav_menu.items[ j ].items[ k ].title, url: nav_menu.items[ j ].items[ k ].url } ) );
										}
									}
									if ( sub.length > 0 ) inner.push( wp.blocks.createBlock( "core/navigation-link", { label: nav_menu.items[ j ].title, url: nav_menu.items[ j ].url }, sub ) );
									else inner.push( wp.blocks.createBlock( "core/navigation-link", { label: nav_menu.items[ j ].title, url: nav_menu.items[ j ].url } ) );
								}

							}
						}

						return wp.blocks.createBlock(
							'core/navigation',
							newatts,
							inner
						);
					},
				}
			]
		}
	} );

} )( window.wp );