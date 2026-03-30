/**
 * Greyd Search Blocks - Gutenberg Blocks for Search Forms
 * 
 * @deprecated and moved to feature/search
 * load here if search feature is deactivated.
 */
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	const ALLOWED_BLOCKS = [ 'greyd/search-input', 'greyd/search-submit', 'greyd/search-sorting', 'greyd/search-filter', 'greyd/search-spacer', 'greyd/search-break', 'greyd/search-datepicker', 'greyd/search-filter-buttons' ];

	const sortingLabels = {
		date_DESC: __( "Chronological (newest first)", 'greyd_hub' ),
		date_ASC: __( "Chronological (oldest first)", 'greyd_hub' ),
		title_ASC: __( "Alphabetical (ascending)", 'greyd_hub' ),
		title_DESC: __( "Alphabetical (descending)", 'greyd_hub' ),
		...( greyd.data.settings?.advanced_search?.postviews_counter == "true" ? {
			views_DESC: __( "Post views", 'greyd_hub' )
		} : {} ),
		...( greyd.data.settings?.advanced_search?.relevance == "true" ? {
			relevance_DESC: __( "Relevance", 'greyd_hub' )
		} : {} ),
	};


	/**
	 * Search wrapper
	 */
	wp.blocks.registerBlockType( 'greyd/search', {
		title: __( "Search (Greyd)", 'greyd_hub' ),
		description: __( "Design a custom search form", 'greyd_hub' ),
		icon: greyd.tools.getCoreIcon( 'search' ),
		category: 'greyd-blocks',
		supports: {
			anchor: true,
			align: true,
			defaultStylePicker: false
		},
		example: {
			attributes: {
				layout: 'row',
			},
			innerBlocks: [
				{
					name: 'greyd/search-input',
					attributes: {}
				},
				{
					name: 'greyd/search-submit',
					attributes: {
						content: __( "Search", 'greyd_hub' ),
						icon: {
							content: 'arrow_right',
							position: 'after',
							size: '100%',
							margin: '10px'
						},
					},
				}
			]
		},
		attributes: {
			layout: { type: 'string', default: 'row' },
			posttype: { type: 'string', default: '' },
			inherit: { type: 'boolean', default: false },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
		},
		providesContext: {
			'greyd/search/posttype': 'posttype',
			'greyd/search/inherit': 'inherit'
		},

		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				attributes: atts
			} = props;

			const posttypes = [
				{
					label: __( 'Post' ),
					value: 'post'
				},
				{
					label: __( 'Page' ),
					value: 'page'
				},
				...greyd.data.post_types.filter( posttype => _.has( posttype.arguments, 'search' ) && posttype.arguments.search === "search" ).map( posttype => {
					return {
						label: posttype.title,
						value: posttype.slug
					}
				} )
			];

			const isSearchTemplate = greyd.data.template_type === "search";

			const [ snackbarShown, setSnackbarShown ] = wp.element.useState( false );
			if ( props.isSelected && atts.layout === "custom" && !snackbarShown) {
				greyd.tools.showSnackbar( __( "All blocks can be inserted into the search form", 'greyd_hub' ) );
				setSnackbarShown(true);
			}


			return [

				el( wp.blockEditor.InspectorControls, {}, [

					// ALLGEMEIN
					el( wp.components.PanelBody, {
						title: __( "General", 'greyd_hub' ),
						initialOpen: true
					}, [
						isSearchTemplate ? el( wp.components.ToggleControl, {
							label: __( "Use query context of the template", 'greyd_hub' ),
							checked: atts.inherit,
							onChange: ( value ) => props.setAttributes( { inherit: value } )
						} ) : '',

						!atts.inherit ? el( wp.components.SelectControl, {
							label: __( "Limit post types", 'greyd_hub' ),
							value: atts.posttype,
							options: [
								{ label: __( "All post types", 'greyd_hub' ), value: '' },
								...posttypes
							],
							onChange: ( value ) => {
								props.setAttributes( { posttype: value } );

								const wrapper = wp.data.select( "core/block-editor" ).getBlocksByClientId( props.clientId )[ 0 ];
								const children = wrapper.innerBlocks;
								children.forEach( child => {
							
									if ( child.name == "greyd/search-filter" ) {
										
										wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( child.clientId, { parentPosttype: value } );
										if ( value !== "" ) {
											wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( child.clientId, { type: "category" } );
										} else {
											wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( child.clientId, { type: "posttype" } );
										}
									}
								} );
							}
						} ) : '',
					] ),


					// ALLGEMEIN
					el( wp.components.PanelBody, { title: __( "Arrangement", 'greyd_hub' ), initialOpen: true }, [

						el( greyd.components.ButtonGroupControl, {
							label: __( "Arrangement", 'greyd_hub' ),
							value: atts.layout,
							options: [
								{ label: __( "Next to each other", 'greyd_hub' ), value: 'row' },
								{ label: __( 'Custom layout', 'greyd_hub' ), value: 'custom' },
							],
							onChange: ( value ) => {
								props.setAttributes( { layout: value } );
								if (value === "row") setSnackbarShown(false);
							}
						} ),
					] ),

					atts.layout === 'custom' ? null : el( greyd.components.StylingControlPanel, {
						title: __( 'Layout', 'greyd_hub' ),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						parentAttr: 'greydStyles',
						controls: [
							{
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								label: __( "Distance between the blocks", 'greyd_hub' ),
								attribute: "gap",
							},
							{
								control: greyd.components.ButtonGroupControl,
								label: __( "Alignment", 'greyd_hub' ),
								attribute: "align-items",
								options: [
									{ label: __( "Top", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'justifyTop' ), value: 'flex-start' },
									{ label: __( "Centered", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'justifyCenter' ), value: 'center' },
									{ label: __( "Bottom", 'greyd_hub' ), icon: greyd.tools.getCoreIcon( 'justifyBottom' ), value: 'flex-end' },
								],
							}
						]
					} ),
				] ),

				// preview
				el( 'div', {
					id: atts.anchor,
					className: [ "greyd-search-form", atts.greydClass, atts.layout ].join( ' ' ),
				}, [
					el( wp.blockEditor.InnerBlocks, {
						allowedBlocks: atts.layout === "custom" ? null : ALLOWED_BLOCKS,
						renderAppender: wp.blockEditor.InnerBlocks.ButtonBlockAppender,
						template: [ [ 'greyd/search-input', {} ], [ 'greyd/search-submit', {} ] ],
						orientation: atts.layout === 'custom' ? 'vertical' : 'horizontal'
					} )
				] ),
				atts.layout === 'custom' ? null : el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .block-editor-inner-blocks > .block-editor-block-list__layout",
					styles: {
						"": atts.greydStyles,
					},
				} ),
			];
		},
		save: function ( props ) {

			const atts = props.attributes;
			return el( 'form', {
				id: atts.anchor,
				className: [ "greyd-search-form", atts.greydClass, atts.layout ].join( ' ' ),
				method: "get",
				role: "search"
			}, [
				(
					( atts.posttype && ! _.isEmpty( atts.posttype ) ) || atts.inherit
					? el( "input", {
						type: "hidden",
						name: "post_type",
						value: atts.posttype ?? ''
					} )
					: null
				),
				el( wp.blockEditor.InnerBlocks.Content ),
				atts.layout === 'custom' ? null : el( greyd.components.RenderSavedStyles, {
					selector: atts.greydClass,
					styles: {
						'': props.attributes.greydStyles
					}
				} ),
			] );
		},

		deprecated: [
			{
				attributes: {
					layout: { type: 'string', default: 'row' },
					posttype: { type: 'string', default: '' },
					inherit: { type: 'boolean', default: false },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object', default: {} },
				},
				supports: {
					anchor: true,
					align: true,
					defaultStylePicker: false
				},
				save: function ( props ) {

					const atts = props.attributes;
					return el( 'form', {
						id: atts.anchor,
						className: [ "greyd-search-form", atts.greydClass, atts.layout ].join( ' ' ),
						method: "get",
						role: "search"
					}, [
						el( "input", {
							type: "hidden",
							name: "post_type",
							value: atts.posttype
						} ),
						el( wp.blockEditor.InnerBlocks.Content ),
						atts.layout === 'custom' ? null : el( greyd.components.RenderSavedStyles, {
							selector: atts.greydClass,
							styles: {
								'': props.attributes.greydStyles
							}
						} ),
					] );
				}
			}
		]
	} );

	/**
	 * Search input block
	 */
	wp.blocks.registerBlockType( 'greyd/search-input', {
		title: __( "Search field", 'greyd_hub' ),
		description: __( "Here users can enter the search term", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('input'),
		category: 'greyd-blocks',
		ancestor: [ "greyd/search" ],
		supports: {
			align: true,
			customClassName: true,
			defaultStylePicker: false
		},
		example: {
			attributes: {}
		},
		styles: [
			{
				name: 'prim',
				label: __( "Primary field", 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'sec',
				label: __( "Secondary field", 'greyd_hub' )
			},
		],
		attributes: {
			autosearch: { type: 'object', default: {} },

			// label
			label: {
				type: 'string',
				default: ''
			},
			placeholder: {
				type: 'string',
				default: __( "Search..." )
			},
			labelStyles: { type: "object", default: {} },

			// styling
			greydStyles: { type: "object" },
			greydClass: { type: "string", default: "" },
			customStyles: { type: 'object' },
			custom: { type: 'boolean', default: false },
		},

		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// atts
			const atts = props.attributes;

			return [

				el( wp.blockEditor.InspectorControls, {}, [

					el( wp.components.PanelBody, { title: __( 'Auto Search', 'greyd_hub' ), initialOpen: true }, [
						el( wp.components.ToggleControl, {
							label: __( 'Auto Search', 'greyd_hub' ),
							checked: atts.autosearch.enable,
							onChange: ( value ) => props.setAttributes( { autosearch: { ...atts.autosearch, enable: value } } )
						} ),
						atts.autosearch.enable && [
							el( wp.components.RangeControl, {
								label: __( "Number of search results", 'greyd_hub' ),
								value: atts.autosearch.maxResults,
								min: 3,
								max: 15,
								onChange: ( value ) => props.setAttributes( { autosearch: { ...atts.autosearch, maxResults: value } } )
							} ),
							el( wp.components.SelectControl, {
								label: __( "Order", 'greyd_hub' ),
								value: atts.autosearch.sorting,
								options: Object.keys( sortingLabels ).map( key => {
									return { label: sortingLabels[ key ], value: key };
								} ),
								onChange: ( value ) => props.setAttributes( { autosearch: { ...atts.autosearch, sorting: value } } )
							} ),
							el( wp.components.TextControl, {
								label: __( "Text loading indicator", 'greyd_hub' ),
								value: atts.autosearch.loading,
								onChange: ( value ) => props.setAttributes( { autosearch: { ...atts.autosearch, loading: value } } )
							} ),
							el( wp.components.TextControl, {
								label: __( "Text for 'no result'", 'greyd_hub' ),
								value: atts.autosearch.noResult,
								onChange: ( value ) => props.setAttributes( { autosearch: { ...atts.autosearch, noResult: value } } )
							} ),
							el( wp.components.ToggleControl, {
								label: __( "Go directly to the search result.", 'greyd_hub' ),
								help: __( "When the user clicks on a search result in Autos Search, he is directly redirected to the result.", 'greyd_hub' ),
								checked: atts.autosearch.forwardOnClick,
								onChange: ( value ) => {
									console.log( value, atts.autosearch )
									props.setAttributes( { autosearch: { ...atts.autosearch, forwardOnClick: value } } )
								}
							} )
						]
					] ),
				] ),

				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// width
					el( greyd.components.StylingControlPanel, {
						title: __( "Width", 'greyd_hub' ),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __( "Width", 'greyd_hub' ),
							max: 800,
							attribute: "width",
							control: greyd.components.RangeUnitControl,
						} ]
					} ),

					// label styles
					el( greyd.components.StylingControlPanel, {
						title: __( 'Label', 'greyd_hub' ),
						supportsHover: true,
						parentAttr: 'labelStyles',
						blockProps: props,
						controls: [
							{
								label: __( "Font size", 'greyd_hub' ),
								attribute: "fontSize",
								units: [ "px", "em", "rem" ],
								max: { px: 60, em: 3, rem: 3 },
								control: greyd.components.RangeUnitControl,
							},
							{
								label: __( "Color", 'greyd_hub' ),
								attribute: "color",
								control: greyd.components.ColorPopupControl,
							},
						]
					} ),

					// custom field
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Individual field", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: atts.custom ? true : false
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Overwrite the design of the field individually", 'greyd_hub' ),
							checked: atts.custom,
							onChange: ( value ) => props.setAttributes( { custom: value } )
						} ),
					] ),
					el( greyd.components.CustomButtonStyles, {
						enabled: atts.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} ),
				] ),

				// PREVIEW
				el( 'div', {
					className: "input-outer-wrapper"
				}, [
					// Input Wrapper
					el( 'div', {
						className: [ "input-wrapper", atts.greydClass ].join( ' ' ),
					}, [
						// Label 
						props.isSelected || atts.label.length > 0 ? el( 'div', {
							className: "label_wrap",
						}, [
							el( 'span', {
								className: "label",
							}, [
								el( wp.blockEditor.RichText, {
									tagName: "span",
									className: "label-content",
									value: atts.label,
									onChange: ( value ) => props.setAttributes( { label: value } ),
									placeholder: props.isSelected ? __( "Enter a label", 'greyd_hub' ) : "",
									allowedFormats: [ 'core/bold', 'core/italic', 'core/subscript', 'core/superscript', 'greyd/versal', 'greyd/text-background' ],
								} ),
							] ),

						] ) : null,
						// Inner Input Wrapper
						el( 'div', {
							className: "input-inner-wrapper",
							style: { display: 'flex' }
						}, [
							// Input Field
							el( wp.blockEditor.RichText, {
								tagName: 'div',
								className: "input " + ( atts.className ?? '' ),
								value: atts.placeholder,
								placeholder: props.isSelected ? __( "Enter a placeholder", 'greyd_hub' ) : atts.placeholder,
								withoutInteractiveFormatting: true,
								format: 'string',
								allowedFormats: [],
								onChange: ( value ) => props.setAttributes( { placeholder: greyd.tools.stripTags( value ) } ),
							} )
						] )
					] )
				] ),
				el( greyd.components.RenderPreviewStyles, {
					selector: 'wp-block#block-' + props.clientId,
					styles: {
						"": atts.greydStyles,
					},
				} ),
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .label",
					styles: {
						"": atts.labelStyles,
					},
				} ),
				!atts.custom ? null : el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .input",
					styles: {
						"": atts.customStyles,
						".rich-text [data-rich-text-placeholder]:after": { color: 'inherit', opacity: 0.5 },
					},
					important: true
				} )

			];
		},

		save: function ( props ) {
			return null;
		},
	} );

	/**
	 * Search submit button
	 */
	wp.blocks.registerBlockType( 'greyd/search-submit', {
		title: __( "Search button", 'greyd_hub' ),
		description: __( "Trigger to start the search", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('button'),
		category: 'greyd-blocks',
		keywords: [ 'link', 'trigger', 'submit', 'button', 'start' ],
		ancestor: [ "greyd/search" ],
		styles: [
			{
				name: 'prim',
				label: __( 'Primary', 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'sec',
				label: __( 'Secondary', 'greyd_hub' )
			},
			{
				name: 'trd',
				label: __( 'Alternative', 'greyd_hub' )
			},
			{
				name: 'link-prim',
				label: __( "Link", 'greyd_hub' )
			},
			{
				name: 'link-sec',
				label: __( "Secondary link", 'greyd_hub' )
			},
		],
		supports: {
			anchor: true,
			align: true,
			defaultStylePicker: false
		},
		example: {
			attributes: {
				content: __( "Search", 'greyd_hub' ),
				icon: {
					content: 'arrow_right',
					position: 'after',
					size: '100%',
					margin: '10px'
				},
			},
		},
		attributes: {
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object' },
			customStyles: { type: 'object' },
			size: { type: 'string', default: '' },
			content: { type: 'string', default: '' },
			icon: {
				type: 'object', properties: {
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
			custom: { type: 'boolean', default: false }
		},

		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const extraClass = props.className.indexOf( 'is-style-link-' ) === -1 ? 'button' : 'link';

			return [
				// sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					// icon
					el( greyd.components.ButtonIconControl, {
						value: props.attributes.icon,
						onChange: function ( value ) {
							props.setAttributes( { icon: value } );
						},
					} ),
				] ),

				// sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// size
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Size", 'greyd_hub' ),
						holdsChange: !_.isEmpty( props.attributes.size )
					}, [
						el( greyd.components.ButtonGroupControl, {
							value: props.attributes.size,
							// label: __( "Size", 'greyd_hub' ),
							options: [
								{ value: "is-size-small", label: __( "Small", 'greyd_hub' ) },
								{ value: "", label: __( "Default", 'greyd_hub' ) },
								{ value: "is-size-big", label: __( "Big", 'greyd_hub' ) },
							],
							onChange: function ( value ) {
								props.setAttributes( { size: value } );
							},
						} ),
					] ),
					// width
					el( greyd.components.StylingControlPanel, {
						title: __( "Width", 'greyd_hub' ),
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __( "Width", 'greyd_hub' ),
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
						holdsChange: props.attributes.custom ? true : false
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Overwrite the design of the button individually", 'greyd_hub' ),
							checked: props.attributes.custom,
							onChange: function ( value ) {
								props.setAttributes( { custom: !!value } );
							},
						} ),
					] ),
					el( greyd.components.CustomButtonStyles, {
						enabled: props.attributes.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} )
				] ),

				// preview
				el( 'div', {
					className: "input-outer-wrapper " + ( props.attributes.align ? ' flex-' + props.attributes.align : '' )
				}, [
					el( 'div', {
						id: props.attributes.anchor,
						className: [ 'submitbutton', extraClass, props.attributes.greydClass, ( props.attributes.className ?? '' ), props.attributes.size ].join( ' ' ),
					}, [
						el( greyd.components.RenderButtonIcon, {
							value: props.attributes.icon,
							position: 'before'
						} ),
						el( wp.blockEditor.RichText, {
							format: 'string',
							tagName: 'span',
							style: { flex: '1' },
							value: props.attributes.content,
							placeholder: __( "Search", 'greyd_hub' ),
							allowedFormats: [ 'core/bold', 'core/italic', 'core/strikethrough', 'greyd/versal', 'greyd/text-background' ],
							onChange: function ( value ) {
								props.setAttributes( { content: value } );
							},
						} ),
						el( greyd.components.RenderButtonIcon, {
							value: props.attributes.icon,
							position: 'after'
						} ),
					] ),
				] ),
				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass + '.submitbutton',
					styles: {
						'': {
							...props.attributes.greydStyles,
							...props.attributes.custom ? props.attributes.customStyles : {}
						},
					}
				} ),
			];
		},

		save: function ( props ) {

			const extraClass = _.has( props.attributes, "className" ) && props.attributes.className.indexOf( 'is-style-link-' ) > -1 ? 'link' : 'button';

			return el( wp.element.Fragment, {}, [
				el( 'div', {
					className: "input-outer-wrapper " + ( props.attributes.align ? ' flex-' + props.attributes.align : '' )
				}, [
					el( "button", {
						id: props.attributes.anchor,
						className: [ 'submitbutton', extraClass, props.attributes.greydClass, ( props.attributes.className ?? '' ), props.attributes.size ].join( ' ' ),
						type: "submit",
					}, [
						// loader & progress bar
						el( 'span', {
							className: "greyd_upload_progress"
						}, [
							el( 'span', { className: "spinner" }, [
								el( 'span', { className: "dot-loader" } ),
								el( 'span', { className: "dot-loader dot-loader--2" } ),
								el( 'span', { className: "dot-loader dot-loader--3" } ),
							] ),
							el( 'span', { className: "bar" } ),
						] ),
						// icon
						el( greyd.components.RenderButtonIcon, {
							value: props.attributes.icon,
							position: 'before'
						} ),
						// text
						el( 'span', {
							style: { flex: '1' },
							dangerouslySetInnerHTML: {
								__html: props.attributes.content.length ? props.attributes.content : __( "Search", 'greyd_hub' )
							}
						} ),
						// icon
						el( greyd.components.RenderButtonIcon, {
							value: props.attributes.icon,
							position: 'after'
						} )
					] ),
				] ),
				el( greyd.components.RenderSavedStyles, {
					selector: props.attributes.greydClass + '.submitbutton',
					styles: {
						'': {
							...props.attributes.greydStyles,
							...props.attributes.custom ? props.attributes.customStyles : {}
						},
					}
				} ),
			] );
		},

		deprecated: [
			{
				attributes: {
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string', default: '' },
					icon: {
						type: 'object', properties: {
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
					custom: { type: 'boolean', default: false }
				},
				supports: {
					anchor: true,
					align: true,
					defaultStylePicker: false
				},
				save: function ( props ) {

					const extraClass = _.has( props.attributes, "className" ) && props.attributes.className.indexOf( 'is-style-link-' ) > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: "input-outer-wrapper " + ( props.attributes.align ? ' flex-' + props.attributes.align : '' )
						}, [
							el( "button", {
								id: props.attributes.anchor,
								className: [ 'submitbutton', extraClass, props.attributes.greydClass, ( props.attributes.className ?? '' ), props.attributes.size ].join( ' ' ),
								type: "submit",
								title: __( "Search", 'greyd_hub' ),
								"data-text": props.attributes.content/*.replace(/(<([^>]+)>)/gi, "")*/,
							}, [
								// loader & progress bar
								el( 'span', {
									className: "greyd_upload_progress"
								}, [
									el( 'span', { className: "spinner" }, [
										el( 'span', { className: "dot-loader" } ),
										el( 'span', { className: "dot-loader dot-loader--2" } ),
										el( 'span', { className: "dot-loader dot-loader--3" } ),
									] ),
									el( 'span', { className: "bar" } ),
								] ),
								// icon
								el( greyd.components.RenderButtonIcon, {
									value: props.attributes.icon,
									position: 'before'
								} ),
								// text
								el( 'span', {
									style: { flex: '1' },
									dangerouslySetInnerHTML: {
										__html: props.attributes.content.length ? props.attributes.content : __( "Search", 'greyd_hub' )
									}
								} ),
								// icon
								el( greyd.components.RenderButtonIcon, {
									value: props.attributes.icon,
									position: 'after'
								} )
							] ),
						] ),
						el( greyd.components.RenderSavedStyles, {
							selector: props.attributes.greydClass + '.submitbutton',
							styles: {
								'': {
									...props.attributes.greydStyles,
									...props.attributes.custom ? props.attributes.customStyles : {}
								},
							}
						} ),
					] );
				},
			}
		]


	} );

	/**
	 * Search filter dropdown
	 */
	wp.blocks.registerBlockType( 'greyd/search-filter', {
		title: __( "Filter (dropdown)", 'greyd_hub' ),
		description: __( "Let users filter by taxonomies or post type", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('dropdown'),
		category: 'greyd-blocks',
		// parent: [ 'greyd/search' ],
		ancestor: [ "greyd/search" ],
		styles: [
			{
				name: 'prim',
				label: __( "Primary dropdown", 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'sec',
				label: __( "Secondary dropdown", 'greyd_hub' )
			},
		],
		example: {
			attributes: {
				placeholder: __( "Filter by...", 'greyd_hub' )
			},
		},
		attributes: {
			inherit: { type: "boolean", default: false },
			parentPosttype: { type: 'string', default: '' },
			filterBy: { type: 'string', default: '' },
			multiselect: { type: "boolean", default: false },
			// label & placeholder
			label: {
				type: 'string',
				default: ''
			},
			placeholder: {
				type: 'string',
				default: ''
			},
			// styles
			greydClass: { type: "string", default: "" },
			greydStyles: { type: "object", default: {} },
			custom: { type: "boolean", default: false },
			customStyles: { type: "object", default: {} },
		},
		usesContext: [
			'greyd/search/posttype',
			'greyd/search/inherit'
		],

		edit: function ( props ) {

			const {
				setAttributes,
				attributes: atts
			} = props;

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// inherit args from search container
			const newAtts = {};
			if ( props.context[ 'greyd/search/posttype' ] && atts.parentPosttype !== props.context[ 'greyd/search/posttype' ] ) {
				newAtts.parentPosttype = props.context[ 'greyd/search/posttype' ];
			}
			if ( atts.inherit !== props.context[ 'greyd/search/inherit' ] ) {
				newAtts.inherit = props.context[ 'greyd/search/inherit' ];
			}

			if ( !_.isEmpty(newAtts) ) {
				setAttributes( newAtts );
			}
		

			let postType = '';
			if ( atts.inherit ) {
				const ptName = greyd.data.post_name.split( "-" );
				postType = ptName[ 1 ];
			} else if ( !_.isEmpty( atts.parentPosttype ) ) {
				postType = '' + atts.parentPosttype; // clone without reference
			}

			let filterOptions = [
				{ label: __( "Please select", 'greyd_hub' ), value: '' }
			];
			if ( !_.isEmpty( postType ) ) {

				if ( postType === 'post' ) {
					let postTypeConfig = greyd.data.post_types.find( config => {
						return config.slug === postType;
					} );
					if ( postTypeConfig ) {
						filterOptions = [
							...filterOptions,
							...postTypeConfig.taxes.map( taxonomy => {
								return { label: taxonomy.title, value: taxonomy.slug };
							} )
						];
					} else {
						filterOptions.push( {
							label: __( 'Category' ),
							value: 'category'
						} );
						filterOptions.push( {
							label: __( 'Tag' ),
							value: 'post_tag'
						} );
					}
				}
				else {
					let postTypeConfig = greyd.data.post_types.find( config => {
						return config.slug === postType && !_.isEmpty( config.arguments ) && _.has( config.arguments, 'search' ) && config.arguments.search == 'search';
					} );
					if ( postTypeConfig ) {
						filterOptions = [
							...filterOptions,
							...postTypeConfig.taxes.map( taxonomy => {
								return { label: taxonomy.title, value: taxonomy.slug };
							} )
						];
					}
				}
			}


			let placeholder = __( "Select filter", 'greyd_hub' );
			let selectedOption = filterOptions.filter( option => option.value === atts.filterBy );
			if ( selectedOption.length > 0 && !_.isEmpty( atts.filterBy ) ) {
				placeholder = __( "%s select", 'greyd_hub' ).replace( '%s', selectedOption[ 0 ].label );
			}

			return [

				// Sidebar
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [

					// filter
					el( wp.components.PanelBody, { title: __( 'Filter', 'greyd_hub' ), initialOpen: true }, [
						(
							_.isEmpty( postType )
							? el( wp.components.BaseControl, {}, [
								el( wp.components.Tip, { style: { marginBottom: '10px' } }, __( "Since no explicit post type is searched, you can only filter for a post type here.", 'greyd_hub' ) )
							] )
							: (
								filterOptions.length == 1
									? el( wp.components.BaseControl, {}, [
										el( wp.components.Tip, { style: { marginBottom: '10px' } }, __( "Unfortunately, there are no filterable taxonomies available for the selected post type.", 'greyd_hub' ) )
									] )
									: el( wp.components.SelectControl, {
										label: __( "Select filter type", 'greyd_hub' ),
										value: atts.filterBy,
										options: filterOptions,
										onChange: value => setAttributes( { filterBy: value } )
									} )
							)
						),
						_.isEmpty( postType ) ? null : el( wp.components.ToggleControl, {
							label: __( "Enable multiselect", 'greyd_hub' ),
							help: __( "Users can select multiple filters at once", 'greyd_hub' ),
							checked: atts.multiselect,
							onChange: value => props.setAttributes( { multiselect: value } ),
						} )
					] ),
				] ),

				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// width
					el( greyd.components.StylingControlPanel, {
						title: __( "Width", 'greyd_hub' ),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __( "Width", 'greyd_hub' ),
							attribute: "width",
							control: greyd.components.RangeUnitControl,
							max: 1000
						} ]
					} ),

					// label style
					el( greyd.components.StylingControlPanel, {
						title: __( 'Label', 'greyd_hub' ),
						supportsHover: true,
						parentAttr: 'labelStyles',
						blockProps: props,
						controls: [
							{
								label: __( "Font size", 'greyd_hub' ),
								attribute: "fontSize",
								units: [ "px", "em", "rem" ],
								max: { px: 60, em: 3, rem: 3 },
								control: greyd.components.RangeUnitControl,
							},
							{
								label: __( "Color", 'greyd_hub' ),
								attribute: "color",
								control: greyd.components.ColorPopupControl,
							},
						]
					} ),

					// custom button
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Individual design", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: atts.custom ? true : false
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Overwrite design individually", 'greyd_hub' ),
							checked: atts.custom,
							onChange: ( value ) => props.setAttributes( { custom: value } ),
						} ),
					] ),
					el( greyd.components.CustomButtonStyles, {
						enabled: atts.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} )
				] ),

				// preview
				el( 'div', {
					className: "input-outer-wrapper " + atts.greydClass
				}, [
					// Input Wrapper
					el( 'div', {
						className: "input-wrapper",
					}, [
						// Label 
						props.isSelected || atts.label.length > 0 ? el( 'div', {
							className: "label_wrap",
						}, [
							el( 'span', {
								className: "label",
							}, [
								el( wp.blockEditor.RichText, {
									tagName: "span",
									className: "label-content",
									value: atts.label,
									onChange: value => props.setAttributes( { label: value } ),
									placeholder: props.isSelected ? __( "Enter a label", 'greyd_hub' ) : "",
									allowedFormats: [ 'core/bold', 'core/italic', 'core/subscript', 'core/superscript', 'greyd/versal', 'greyd/text-background' ],
								} ),
							] ),

						] ) : null,
						// Inner Input Wrapper
						el( 'div', {
							className: "input-inner-wrapper",
							style: { display: 'flex' }
						}, [
							el( wp.blockEditor.RichText, {
								format: 'string',
								tagName: 'span',
								className: 'input dropdown ' + atts.className,
								value: atts.placeholder,
								placeholder: placeholder,
								withoutInteractiveFormatting: true,
								allowedFormats: [],
								onChange: value => setAttributes( { placeholder: value } )
							} )
						] )
					] )
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: 'wp-block#block-' + props.clientId,
					styles: {
						"": atts.greydStyles,
					},
				} ),
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .label",
					styles: {
						"": atts.labelStyles,
					},
				} ),
				!atts.custom ? null : el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .input",
					styles: {
						"": atts.customStyles,
						".rich-text [data-rich-text-placeholder]:after": { color: 'inherit', opacity: 0.5 },
					},
					important: true
				} )
			];
		},
		save: function ( props ) {
			return null;
		}
	} );

	/**
	 * Search sort dropdown
	 */
	wp.blocks.registerBlockType( 'greyd/search-sorting', {
		title: __( "Sort (Dropdown)", 'greyd_hub' ),
		description: __( "Let users determine the order themselves", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('dropdown'),
		category: 'greyd-blocks',
		ancestor: [ "greyd/search" ],
		styles: [
			{
				name: 'prim',
				label: __( "Primary dropdown", 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'sec',
				label: __( "Secondary dropdown", 'greyd_hub' )
			},
		],
		example: {
			attributes: {}
		},
		attributes: {
			options: {
				type: "object", default: {
					date_DESC: '',
					date_ASC: '',
					title_DESC: '',
					title_ASC: '',
					views_DESC: '',
					relevance_DESC: '',
				}
			},
			// label & placeholder
			label: {
				type: 'string',
				default: ''
			},
			placeholder: {
				type: 'string',
				default: ''
			},
			// styles
			greydClass: { type: "string", default: "" },
			greydStyles: { type: "object", default: {} },
			labelStyles: { type: "object", default: {} },
			custom: { type: "boolean", default: false },
			customStyles: { type: "object", default: {} },
		},

		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				setAttributes,
				attributes: atts
			} = props;

			// basics
			return [

				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [

					// options
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Designations", 'greyd_hub' ),
						initialOpen: true,
					}, Object.keys( sortingLabels ).map( ( key ) => {
						// console.log(key);
						return el( wp.components.TextControl, {
							label: sortingLabels[ key ],
							value: _.has( atts.options, key ) ? atts.options[ key ] : '',
							onChange: ( value ) => setAttributes( {
								options: {
									...atts.options,
									[ key ]: value
								}
							} ),
						} );
					} ) ),
				] ),

				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// width
					el( greyd.components.StylingControlPanel, {
						title: __( "Width", 'greyd_hub' ),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __( "Width", 'greyd_hub' ),
							attribute: "width",
							control: greyd.components.RangeUnitControl,
							max: 1000
						} ]
					} ),

					// label style
					el( greyd.components.StylingControlPanel, {
						title: __( 'Label', 'greyd_hub' ),
						supportsHover: true,
						parentAttr: 'labelStyles',
						blockProps: props,
						controls: [
							{
								label: __( "Font size", 'greyd_hub' ),
								attribute: "fontSize",
								units: [ "px", "em", "rem" ],
								max: { px: 60, em: 3, rem: 3 },
								control: greyd.components.RangeUnitControl,
							},
							{
								label: __( "Color", 'greyd_hub' ),
								attribute: "color",
								control: greyd.components.ColorPopupControl,
							},
						]
					} ),

					// custom design
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Individual field", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: atts.custom ? true : false
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Overwrite design individually", 'greyd_hub' ),
							checked: atts.custom,
							onChange: ( value ) => setAttributes( { custom: value } ),
						} ),
					] ),
					el( greyd.components.CustomButtonStyles, {
						enabled: atts.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} )
				] ),

				// preview
				el( 'div', {
					className: "input-outer-wrapper " + atts.greydClass
				}, [
					// Input Wrapper
					el( 'div', {
						className: "input-wrapper",
					}, [
						// Label 
						props.isSelected || atts.label.length > 0 ? el( 'div', {
							className: "label_wrap",
						}, [
							el( 'span', {
								className: "label",
							}, [
								el( wp.blockEditor.RichText, {
									tagName: "span",
									className: "label-content",
									value: atts.label,
									onChange: value => setAttributes( { label: value } ),
									placeholder: props.isSelected ? __( "Enter a label", 'greyd_hub' ) : "",
									allowedFormats: [ 'core/bold', 'core/italic', 'core/subscript', 'core/superscript', 'greyd/versal', 'greyd/text-background' ],
								} ),
							] ),

						] ) : null,
						// Inner Input Wrapper
						el( 'div', {
							className: "input-inner-wrapper",
							style: { display: 'flex' }
						}, [
							el( wp.blockEditor.RichText, {
								format: 'string',
								tagName: 'span',
								className: 'input dropdown ' + atts.className,
								value: atts.placeholder,
								placeholder: __( "Sort by...", 'greyd_hub' ),
								withoutInteractiveFormatting: true,
								allowedFormats: [],
								onChange: value => setAttributes( { placeholder: value } )
							} )
						] ),
					] )
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: 'wp-block#block-' + props.clientId,
					styles: {
						"": atts.greydStyles,
					},
				} ),
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .label",
					styles: {
						"": atts.labelStyles,
					},
				} ),
				!atts.custom ? null : el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass + " .input",
					styles: {
						"": atts.customStyles,
						".rich-text [data-rich-text-placeholder]:after": { color: 'inherit', opacity: 0.5 },
					},
					important: true
				} )
			];
		},
		save: function ( props ) {
			return null;
		}
	} );
} )( window.wp );