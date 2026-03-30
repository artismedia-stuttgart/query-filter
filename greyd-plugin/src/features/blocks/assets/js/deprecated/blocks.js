/**
 * Registers all greyd/ blocks.
 * 
 * @deprecated since 1.7.0
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	var transformDefaultAtts = function(attributes) {

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


	wp.blocks.registerBlockType( 'greyd/iframe', {
		title: __('iFrame', 'greyd_hub'),
		description: __("Integration of multimedia content", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('iframe'),
		category: 'greyd-blocks',
		
		supports: {
			anchor: true,
			align: true,
			spacing: {
				padding: true,
			},
		},
		attributes: {
			css_animation: { type: 'string' },
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			url: { type: 'string', default: '' },
			greydClass: { type: "string", default: "" },
			greydStyles: { type: 'object'},
		},
		
		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}
			var [ url, setPlaceholderUrl ] = wp.element.useState(props.attributes.url);
			var setUrl = function(value) {
				if (value.indexOf('https://youtu.be/') === 0) value = value.split('https://youtu.be/').join('http://www.youtube.com/embed/');
				else if (value.indexOf('https://www.youtube.com/watch?v=') === 0) value = value.split('https://www.youtube.com/watch?v=').join('http://www.youtube.com/embed/');
				else if (value.indexOf('https://vimeo.com/video/') === 0) value = value.split('https://vimeo.com/video/').join('https://player.vimeo.com/video/');
				else if (value.indexOf('https://vimeo.com/') === 0) value = value.split('https://vimeo.com/').join('https://player.vimeo.com/video/');
				props.setAttributes( { url: value } );
			}
			
			return [
				el( wp.blockEditor.InspectorControls, {}, [
					el( wp.components.PanelBody, {
						title: __("General", 'greyd_hub'),
						initialOpen: true
					}, [
						el( wp.components.TextControl, {
							label: __('iFrame URL', 'greyd_hub'),
							value: props.attributes.url,
							onChange: function(value) { setUrl(value); },
						} )
					] ),

					el( greyd.components.StylingControlPanel, {
						title: __("Size", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Width", 'greyd_hub'),
								attribute: "width",
								control: greyd.components.RangeUnitControl, 
								units: ["px", "%", "vw", "em", "rem"],
								max: 2000,
							},
							{
								label: __("Height", 'greyd_hub'),
								attribute: "height",
								control: greyd.components.RangeUnitControl, 
								units: ["px", "%", "vh", "em", "rem"],
								max: 2000,
							},
						]
					} ),
				] ),

				// preview
				props.attributes.url == "" ? el( wp.components.Placeholder, {
					// render placeholder
					icon: greyd.tools.getBlockIcon('iframe'),
					label: __( 'iFrame URL', 'greyd_hub' ),
				}, [
					el( 'input', {
						type: 'url',
						placeholder: __("Enter URL", 'greyd_hub'),
						className: 'components-placeholder__input',
						style: { width: 'auto' },
						value: url,
						onChange: function(value) { setPlaceholderUrl(value.target.value) },
					} ),
					el( wp.components.Button, {
						className: 'is-primary',
						disabled: url == "" ? 'disabled' : '',
						onClick: function() { setUrl(url); },
					}, __("Embed", 'greyd_hub') ) 
				] ) 
				: el('iframe', {
					id: props.attributes.anchor,
					className: props.className+" "+props.attributes.greydClass,
					style: { pointerEvents: 'none' },
					src: props.attributes.url,
				} ),

				el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass,
					styles: {
						"": props.attributes.greydStyles,
					}
				})
			];
		},
		save: function( props ) {
			
			return el( wp.element.Fragment, {}, [
				el( 'div', {
					className: "wp-block-greyd-iframe "+props.attributes.align,
				}, [
					el( 'iframe', {
						id: props.attributes.anchor,
						className: props.attributes.greydClass,
						src: props.attributes.url,
						
					}),
					el( greyd.components.RenderSavedStyles, {
						selector: props.attributes.greydClass,
						styles: {
							"": props.attributes.greydStyles,
						}
					})
				])
				
			]);
		},

		deprecated: [
			/**
			 * RenderSavedStylesDeprecated
			 * @deprecated since 1.1.2
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' },
					dynamic_value: { type: 'string' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					url: { type: 'string', default: '' },
					greydClass: { type: "string", default: "" },
					greydStyles: { type: 'object'},
				},
				save: function( props ) {
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: "wp-block-greyd-iframe "+props.attributes.align,
						}, [
							el( 'iframe', {
								id: props.attributes.anchor,
								className: props.attributes.greydClass,
								src: props.attributes.url,
								
							}),
							el( greyd.components.RenderSavedStylesDeprecated, {
								selector: props.attributes.greydClass,
								styles: {
									"": props.attributes.greydStyles,
								}
							})
						])
						
					]);
				}
			}
		],
		
		transforms: {
			to: [
				{
					type: 'block',
					blocks: [ 'core/embed' ],
					isMatch: function( attributes ) {
						// console.log(attributes);
						if (attributes.url.indexOf('http://www.youtube.com/embed/') === 0 ||
							attributes.url.indexOf('https://player.vimeo.com/video/') === 0) {
							return true;
						}
						return false;
					},
					transform: function ( attributes, innerBlocks ) {
						console.log('convert iframe to video');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = transformDefaultAtts(attributes);
						if (attributes.url.indexOf('http://www.youtube.com/embed/') === 0) {
							newatts = {
								url: attributes.url.split('http://www.youtube.com/embed/').join('https://youtu.be/'),
								type: "video",
								providerNameSlug: "youtube",
								responsive: true,
								className: "wp-embed-aspect-16-9 wp-has-aspect-ratio"
							};
						}
						if (attributes.url.indexOf('https://player.vimeo.com/video/') === 0) {
							newatts = {
								url: attributes.url.split('https://player.vimeo.com/video/').join('https://vimeo.com/'),
								type: "video",
								providerNameSlug: "vimeo",
								responsive: true,
								className: "wp-embed-aspect-16-9 wp-has-aspect-ratio"
							};
						}

						return wp.blocks.createBlock(
							'core/embed',
							newatts,
							[]
						);
					},
				}
			]
		}
	} );

	if (greyd.data.post_type == "greyd_popup" && _.has( greyd, '__useDeprecatedPopupClose' ) ) {

		wp.blocks.registerBlockType( 'greyd/popup-close', {
			title: __("Close popup", 'greyd_hub'),
			description: __("Button for closing popups", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('close'),
			category: 'greyd-blocks',
			supports: {
				anchor: true,
				align: true
			},
			attributes: {
				align: { type: 'string', default: "right" },
				greydClass: { type: 'string', default: '' },
				greydStyles: { type: 'object', default: {
					width: '40px',
					fontSize: '4px'
				} },
			},
	
			edit: function( props ) {

				const newGreydClass = greyd.tools.getGreydClass( props );
				if ( props.attributes?.greydClass !== newGreydClass ) {
					props.setAttributes( { greydClass: newGreydClass } );
				}

				return [

					// sidebar
					el( wp.blockEditor.InspectorControls, {}, [
						// colors
						el( greyd.components.StylingControlPanel, {
							title: __("Colors", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							holdsColors: [
								{ 
									color: (_.has(props.attributes, 'greydStyles.color')) ? props.attributes.greydStyles.color : '', 
									title: __("Icon color", 'greyd_hub') 
								},
								{ 
									color: (_.has(props.attributes, 'greydStyles.backgroundColor')) ? props.attributes.greydStyles.backgroundColor : '', 
									title: __("Background", 'greyd_hub') 
								}
							],
							blockProps: props,
							controls: [
								{
									label: __("Icon color", 'greyd_hub'),
									attribute: "color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color'
								},
								{
									label: __("Background color", 'greyd_hub'),
									attribute: "backgroundColor",
									control: greyd.components.ColorGradientPopupControl,
									contrast: {
										default: (_.has(props.attributes, 'greydStyles.color')) ? props.attributes.greydStyles.color : '', // '#000'
										hover: (_.has(props.attributes, 'greydStyles.hover.color')) ? props.attributes.greydStyles.hover.color : '', // '#000'
									}
								},
							]
						} ),
						// size
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									attribute: "width",
									control: greyd.components.RangeUnitControl,
									units: [ 'px', 'em', 'rem', 'vw', 'vh' ],
									min: 10,
									max: 100,
								},
							]
						} ),
						// strength
						el( greyd.components.StylingControlPanel, {
							title: __("Line width", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									attribute: "fontSize",
									control: greyd.components.RangeUnitControl,
									units: [ 'px' ],
									min: 1,
									max: 15,
								},
							]
						} ),
						( !_.has( props.attributes.greydStyles, 'backgroundColor') || _.isEmpty( props.attributes.greydStyles.backgroundColor ) ? null : [
							// radius
							el( greyd.components.StylingControlPanel, {
								title: __("Border radius", 'greyd_hub'),
								blockProps: props,
								controls: [
									{
										label: __('Radius', 'greyd_hub'),
										attribute: "borderRadius",
										control: greyd.components.DimensionControl,
										type: "string",
										sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
										max: 20
									},
								]
							} ),
						] )
					] ),

					// preview
					el( 'div' , {
						className: [ 'popup_close_button', props.attributes.className, props.attributes.greydClass ].join(' ')
					}, [
						el( 'div' , {
						className: [ 'close_icon' ].join(' ')
						}, [
							el( 'span' ),
							el( 'span' ),
						] ),
					] ),
					// styles
					el( greyd.components.RenderPreviewStyles, {
						selector: props.attributes.greydClass,
						styles: {
							"": props.attributes.greydStyles
						}
					} ),
				];
			},
			save: function( props ) {
				return el( 'div' , {
					id: props.attributes.anchor,
					className: props.attributes.align
				}, [
					el( 'div' , {
						className: [ 'popup_close_button', props.attributes.greydClass ].join(' '),
						onclick: 'popups.close(this)',
						"aria-label": __("Close popup", 'greyd_hub'),
					}, [
						el( 'div' , {
						className: [ 'close_icon' ].join(' ')
						}, [
							el( 'span' ),
							el( 'span' ),
						] ),
					] ),
				] );
			},
			deprecated: [
				/**
				 * RenderSavedStylesDeprecated
				 * @deprecated since 1.1.2
				 */
				{
					
					attributes: {
						align: { type: 'string', default: "right" },
						greydClass: { type: 'string', default: '' },
						greydStyles: { type: 'object', default: {
							width: '40px',
							fontSize: '4px'
						} },
					},
					save: function(props) {
						return el( 'div' , {
							id: props.attributes.anchor,
							className: props.attributes.align
						}, [
							el( 'div' , {
								className: [ 'popup_close_button', props.attributes.greydClass ].join(' '),
								onclick: 'popups.close(this)',
							}, [
								el( 'div' , {
								className: [ 'close_icon' ].join(' ')
								}, [
									el( 'span' ),
									el( 'span' ),
								] ),
							] ),
						] );
					}
				},
			],
		} );
	}
	
	wp.blocks.registerBlockType( 'greyd/anchor', {
		title: __("Anchor target", 'greyd_hub'),
		description: __("Definition of anchors", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('anchor'),
		category: 'greyd-blocks',
		
		supports: {
			className: false,
			customClassName: false,
		},
		attributes: {
			anchor: { type: 'string', default: '' },
			greydStyles: { type: 'object' },
		},

		edit: function( props ) {
			var offset = "";
			if (_.has(props.attributes, "greydStyles.marginTop")) {
				props.attributes.greydStyles['--anchorcustommargin'] = props.attributes.greydStyles.marginTop;
				delete props.attributes.greydStyles.marginTop;
			}
			if (_.has(props.attributes, "greydStyles.--anchorcustommargin")) offset = props.attributes.greydStyles['--anchorcustommargin'];

			return [
				// inspector
				el( wp.blockEditor.InspectorControls, {}, [
					el( wp.components.PanelBody, { title: __("General", 'greyd_hub'), initialOpen: true }, [
						el( wp.components.TextControl, {
							label: __("Anchor title", 'greyd_hub'),
							value: props.attributes.anchor,
							onChange: function(value) { props.setAttributes( { anchor: value } ); },
						} )
					] ),
					el( greyd.components.StylingControlPanel, {
						title: __("Offset", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __("Vertical offset", 'greyd_hub'),
							attribute: "--anchorcustommargin",
							control: greyd.components.RangeUnitControl,
							min: -500,
							max: 500
						} ]
					} ),
				] ),
				// preview
				el( 'div', { className: props.className+' preview-info-wrapper' }, [
					props.isSelected && offset !== "" && el( 'div', { className: 'preview-anchor-helper', style: { marginTop: offset } } ),
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('anchor'),
						el( 'div', { className: 'preview-info-title' }, [
							el( 'ul', {}, el( 'li', { id: props.attributes.anchor, }, [
								el( 'strong', {}, __("Anchor target", 'greyd_hub')+': ' ),
								el( wp.blockEditor.RichText, {
									format: 'string',
									tagName: 'span',
									style: { flex: '1' },
									value: props.attributes.anchor,
									placeholder: __( "Enter anchor name", 'greyd_hub' ),
									allowedFormats: [],
									onChange: function(value) { 
										props.setAttributes( { anchor: value } ); 
									},
								} ),
							] ) ),
						] ),
					] ),
				] )
			];
		},
		save: function( props ) {
			return el( 'div', { 
				id: props.attributes.anchor, 
				className: 'greyd-anchor-target',
			} );
		},

		transforms: {
			to: [
				{
					type: 'block',
					blocks: [ 'core/group' ],
					isMatch: function( attributes ) {
						// console.log(attributes);
						if (_.has(attributes, 'anchor') && attributes["anchor"] != "") {
							return true;
						}
						return false;
					},
					transform: function ( attributes, innerBlocks ) {
						console.log('convert group to anchor');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = transformDefaultAtts(attributes);

						return wp.blocks.createBlock(
							'core/group',
							newatts,
							[]
						);
					},
				}
			]
		}
	} );
	
	wp.blocks.registerBlockType( 'greyd/image', {
		title: __("Dynamic image", 'greyd_hub'),
		description: __("Dynamic images from posts and post types.", 'greyd_hub'),
		icon: '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z"></path></svg>',
		icon: greyd.tools.getCoreIcon('image'),
		category: 'greyd-blocks',
		
		keywords: [ 'image', 'img', 'icon', 'svg', 'png', 'jpg', 'dynamic' ],
		styles: [
			{
				name: 'normal',
				label: __( 'Normal', 'greyd_hub' ),
				isDefault: true
			},
			{
				name: 'rounded',
				label: __( "Rounded", 'greyd_hub' )
			},
			{
				name: 'rounded-corners',
				label: __( "Rounded corners", 'greyd_hub' )
			},
			{
				name: 'has-shadow',
				label: __( "Shadow", 'greyd_hub' )
			},
			{
				name: 'diagonal-up',
				label: __( "Diagonal (up)", 'greyd_hub' )
			},
			{
				name: 'diagonal-down',
				label: __( "Diagonal (down)", 'greyd_hub' )
			},
			{
				name: 'tilt-left',
				label: __( "3D (left)", 'greyd_hub' )
			},
			{
				name: 'tilt-right',
				label: __( "3D (right)", 'greyd_hub' )
			},
		],
		example: {
			attributes: {
				image: {
					type: 'file',
					id: -1,
					url: 'https://update.greyd.io/files/img/greyd-block-image-example.svg',
					tag: '',
				},
				greydClass: 'gs_123456',
				greydStyles: {
					width: '300px'
				}
			},
		},
		supports: {
			anchor: true,
			align: true,
			defaultStylePicker: false
		},
		attributes: {
			anchor: { type: 'string' },
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			// trigger: { type: 'object' },
			image: { type: 'object', default: {
				type: 'file',
				id: -1,
				url: '',
				tag: '',
			} },
			caption: { type: "string", default: '' },
			focalPoint: { type: 'object', default: { x: 0.5, y: 0.5 } },
			greydClass: { type: "string", default: "" },
			greydStyles: { type: 'object' },
		},

		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const { greydStyles, image } = props.attributes;
			const width      = _.has(greydStyles, 'width') && !_.isEmpty(greydStyles.width) ? greydStyles.width : '100%';
			const widthNum   = parseInt( width, 10 );
			const widthUnit  = greyd.tools.getUnitValue(width);
			const height     = _.has(greydStyles, 'height') && !_.isEmpty(greydStyles.height) ? greydStyles.height : '';
			const heightNum  = parseInt( height, 10 );
			// const heightUnit = greyd.tools.getUnitValue(height);

			// set default object fit
			let objectFit = 'cover';
			if ( _.has(greydStyles, 'objectFit') && !_.isEmpty(greydStyles.objectFit) ) {
				objectFit = greydStyles.objectFit;
			}

			if ( typeof image !== 'undefined' && image.id !== -1 ) {
				// console.log(image);
				const mediaObj = wp.data.select("core").getMedia(image.id);
				if ( mediaObj && _.has(mediaObj, 'source_url') && mediaObj.source_url != image.url) {
					console.info("dynamic url updated");
					image.url = mediaObj.source_url;
					props.setAttributes({ image: image });
				}
			}
			const hasCustomImage = image.type === 'file' && !_.isEmpty(image.url);

			const [ showCaption, setShowCaption ] = wp.element.useState( !!props.attributes.caption );

			// call function to make sure Block is updated when inside a template
			var bp = wp.blockEditor.useBlockProps();

			return [
				el( wp.blockEditor.BlockControls, { group: 'block' }, [
					el( wp.components.ToolbarButton, {
						label: __('Caption', 'greyd_hub'),
						icon: greyd.tools.getCoreIcon('caption'),
						isPressed: showCaption,
						onClick: () => { 
							setShowCaption( ! showCaption );
						}
					} ),
				] ),
				// sidebar
				el( wp.blockEditor.InspectorControls, {},
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Select image", 'greyd_hub' ),
						holdsChange: (image.type === 'file' && !_.isEmpty(image.url)) || (image.type === 'dynamic' && !_.isEmpty(image.tag))
					}, [
						el( greyd.components.DynamicImageControl, {
							clientId: props.clientId,
							value: image,
							onChange: (value) => {
								props.setAttributes({
									image: value
								});
							}
						} )
					] ),
					el( greyd.components.StylingControlPanel, {
						title: __("Size", 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Width", 'greyd_hub'),
								attribute: "width",
								control: greyd.components.RangeUnitControl,
								units: ["px", "%", "em", "rem", "vw", "vh"],
								max: 1200,
							},
							{
								label: __("Height", 'greyd_hub'),
								attribute: "height",
								control: greyd.components.RangeUnitControl,
								units: ["px", "em", "rem", "vw", "vh"],
								max: 1200,
							},
							{
								label: __("Scale", 'greyd_hub'),
								attribute: "objectFit",
								control: greyd.components.ButtonGroupControl,
								options: [
									{
										label: __('Cover', 'greyd_hub'),
										value: 'cover'
									},
									{
										label: __('Contain', 'greyd_hub'),
										value: 'contain'
									},
									{
										label: __("Fill", 'greyd_hub'),
										value: 'fill'
									}
								],
								help: (
									_.has(greydStyles, 'objectFit') ? (
										greydStyles.objectFit == 'fill' ? (
											__("The image is stretched and compressed to completely fill the space.", 'greyd_hub')
										) : ( greydStyles.objectFit == 'contain' ? (
											__("The image is scaled to fill the space without cropping or distorting.", 'greyd_hub')
										) : (
											__("The image is scaled and cropped to fill the entire space without distorting it.", 'greyd_hub')
										) )
									) : __("The image is scaled and cropped to fill the entire space without distorting it.", 'greyd_hub')
								)
							}
						]
					} ),
					el( greyd.components.AdvancedPanelBody, {
						title: __("Focal point", 'greyd_hub'),
						holdsChange: props.attributes.focalPoint.x != '0.50' || props.attributes.focalPoint.y != '0.50',
						initialOpen: false
					}, [
						el( wp.components.FocalPointPicker, {
							value: props.attributes.focalPoint,
							url: image.url,
							onChange: (value) => {
								props.setAttributes({
									focalPoint: value
								});
							},
							help: __("This property is only applied when the scaling is set to 'Cover'.", 'greyd_hub')
						} ),
					] ),
				),

				// toolbar
				el( greyd.components.ToolbarDynamicImageControl, {
					value: image,
					clientId: props.clientId,
					onChange: (value) => {
						props.setAttributes({
							image: value
						});
					}
				} ),

				// preview
				el( 'div', {
					className: 'wp-block-image '+props.className
				}, [
					!_.isEmpty(props.attributes.dynamic_parent) || !props.isSelected ? (
						// without ResizableBox (in Templates)
						hasCustomImage ? el( 'img', {
							id: props.attributes.anchor,
							className: props.attributes.greydClass,
							src: image.url,
							style: {
								height: _.isEmpty(height) ? 'auto' : height,
								width: width,
								transition: 'none',
								objectFit: objectFit,
								...( objectFit === 'cover' ? {
									objectPosition: (props.attributes.focalPoint.x * 100) + '% ' + (props.attributes.focalPoint.y * 100) + '%'
								} : null )
							}
						} ) : el( 'div', {
							id: props.attributes.anchor,
							className: 'dynamic_img__preview',
							style: {
								height: _.isEmpty(height) ? '200px' : height,
								width: width
							}
						}, [ _.isEmpty(image.tag) ? "" : el( "span", {}, image.tag ), greyd.tools.getCoreIcon('image') ] )
					) : (
						// with ResizableBox
						el( wp.components.ResizableBox, {
							size: {
								height: _.isEmpty(height) ? (hasCustomImage ? 'auto' : '200px') : height,
								width: width
							},
							minHeight: 20,
							enable: {
								top: false,
								topRight: false,
								right: true,
								bottomRight: true,
								bottom: true,
								bottomLeft: false,
								left: false,
								topLeft: false,
							},
							onResizeStop: ( event, direction, elt, delta ) => {

								if ( delta.height === 0 && delta.width === 0 ) return;

								const newGreydStyles = { ...greydStyles };

								// set width
								if ( delta.width !== 0 ) {
									if (widthUnit === '%' ) {
										newGreydStyles.width = parseInt( widthNum + ( delta.width / (elt.parentNode.offsetWidth / 100) ), 10 ) + widthUnit;
									} else {
										newGreydStyles.width = parseInt( widthNum + delta.width, 10 ) + 'px';
									}
								}

								// set height
								if ( delta.height !== 0 ) {
									if ( isNaN(heightNum) ) {
										if ( direction !== 'right' ) {
											newGreydStyles.height = elt.parentNode.offsetHeight + 'px';
										}
									} else {
										newGreydStyles.height = parseInt( heightNum + delta.height, 10 ) + 'px';
									}
								}
	
								props.setAttributes( {
									greydStyles: newGreydStyles,
								} );
							}
						}, (
							hasCustomImage ?  el( 'img', {
								id: props.attributes.anchor,
								className: props.attributes.greydClass,
								src: image.url,
								style: {
									width: '100%',
									transition: 'none',
									objectFit: objectFit,
									...( objectFit === 'cover' ? {
										objectPosition: (props.attributes.focalPoint.x * 100) + '% ' + (props.attributes.focalPoint.y * 100) + '%'
									} : null )
								}
							} ) :  el( 'div', {
								id: props.attributes.anchor,
								className: 'dynamic_img__preview'
							}, [ _.isEmpty(image.tag) ? "" : el( "span", {}, image.tag ), greyd.tools.getCoreIcon('image') ] )
						) )
					),
					showCaption ?  el( wp.blockEditor.RichText, {
						tagName: 'figcaption',
						// multiline: "br",
						value: props.attributes.caption,
						placeholder: __( "Insert caption", 'greyd_hub' ),
						onChange: (value) => {
							// console.log("change");
							props.setAttributes( { caption: value } ); 
						},
					} ) : ''
				] ),
				// el( greyd.components.RenderPreviewStyles, {
				// 	selector: props.attributes.greydClass,
				// 	styles: {
				// 		"": _.omit(
				// 			props.attributes.greydStyles,
				// 			[ 'width', 'height' ]
				// 		),
				// 	}
				// })
				
			];
		},

		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'core/image' ],
					transform: function ( attributes ) {
						// console.log(attributes);
						// console.log(innerBlocks);

						const newAtts = {
							className: _.has(attributes, 'className') ? attributes.className : null,
							align: _.has(attributes, 'align') ? attributes.align : null,
							image: {
								id: _.has(attributes, 'id') ? attributes.id : -1,
								url: _.has(attributes, 'url') ? attributes.url : '',
								tag: '',
								type: 'file',
							},
							greydStyles: {
								width: _.has(attributes, 'width') ?  attributes.width +'px' : null,
								height: _.has(attributes, 'height') ?  attributes.height +'px' : null
							}
						};

						if ( _.has(attributes, 'dynamic_fields') && typeof attributes.dynamic_fields[0] !== 'undefined' ) {
							newAtts.dynamic_fields = [{
								key: 'image',
								title: attributes.dynamic_fields[0].title,
								enable: true,
							}];
						}
						if ( _.has(attributes, 'dynamic_parent') ) {
							newAtts.dynamic_parent = attributes.dynamic_parent;
						}
						if ( _.has(attributes, 'dynamic_value') ) {
							newAtts.dynamic_value = attributes.dynamic_value;
						}

						return wp.blocks.createBlock(
							'greyd/image',
							newAtts
						);
					},
				}
			],
			to: [
				{
					type: 'block',
					blocks: [ 'core/image' ],
					transform: function ( attributes ) {
						// console.log(attributes);
						// console.log(innerBlocks);

						const newAtts = {
							className: _.has(attributes, 'className') ? attributes.className : null,
							align: _.has(attributes, 'align') ? attributes.align : null,
							id: _.has(attributes.image, 'id') ? attributes.image.id : null,
							url: _.has(attributes.image, 'url') ? attributes.image.url : "https://s.w.org/images/core/5.3/MtBlanc1.jpg",
							width: _.has(attributes.greydStyles, 'width') ? parseInt(attributes.greydStyles.width) : null,
						};

						if ( _.has(attributes, 'dynamic_fields') ) {
							newAtts.dynamic_fields = [{
								key: 'id',
								title: attributes.dynamic_fields[0].title,
								enable: true,
							}];
						}
						if ( _.has(attributes, 'dynamic_parent') ) {
							newAtts.dynamic_parent = attributes.dynamic_parent;
						}
						if ( _.has(attributes, 'dynamic_value') ) {
							newAtts.dynamic_value = attributes.dynamic_value;
						}

						return wp.blocks.createBlock(
							'core/image',
							newAtts
						);
					},
				}

			]
		}
	} );
	
	// if Greyd.Forms is inactive
	if ( greyd.data.forms === false ) {
		wp.blocks.registerBlockType( 'greyd/form', {
			title: __("Shape", 'greyd_hub'),
			description: __("Integrates a form", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('form'),
			category: 'greyd-blocks',
			// parent: parent_blocks,
	
			edit: function( props ) {
				var atts = {
					id: parseInt(props.attributes.id),
				}
	
				var forms = [ { value: "", label: __("Please select", 'greyd_hub') } ];
				for (var i=0; i<greyd.data.forms.length; i++) {
					forms.push( { value: greyd.data.forms[i].id, label: greyd.data.forms[i].title } )
				}
	
				return [
					el( wp.serverSideRender, {
						block: 'greyd/form',
						attributes: atts,
						className: props.className,
					} ),
					// el( wp.blockEditor.InspectorControls, {},
					// 	el( wp.components.PanelBody, { title: __("General", 'greyd_hub'), initialOpen: true },
					// 		// todo
					// 		el( wp.components.BaseControl, { },
					// 			// choose form
					// 			el( greyd.components.OptionsControl, {
					// 				style: { maxWidth: '100%' },
					// 				value: props.attributes.id,
					// 				options: forms,
					// 				onChange: function(value) { 
					// 					// console.log(value);
					// 					props.setAttributes( { id: value } );
					// 				},
					// 			} ),
					// 		),
					// 	)
					// ),
				];
			},
		} );
	}
	
} )( window.wp );