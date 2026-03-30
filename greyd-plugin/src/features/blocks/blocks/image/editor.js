/**
 * Registers all greyd/ blocks.
 */
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	wp.blocks.registerBlockType( 'greyd/image', {
		apiVersion: 2,
		title: __( "Dynamic image", 'greyd_hub' ),
		description: __( "Dynamic images from posts and post types.", 'greyd_hub' ),
		icon: '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z"></path></svg>',
		icon: greyd.tools.getCoreIcon( 'image' ),
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
			defaultStylePicker: false,
			spacing: {
				margin: true,
				padding: true
			}
		},
		attributes: {
			anchor: { type: 'string' },
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			// trigger: { type: 'object' },
			image: {
				type: 'object', default: {
					type: 'file',
					id: -1,
					url: '',
					tag: '',
				}
			},
			sizeSlug: { type: "string", default: 'large' },
			caption: { type: "string", default: '' },
			focalPoint: { type: 'object', default: { x: 0.5, y: 0.5 } },
			greydClass: { type: "string", default: "" },
			greydStyles: { type: 'object' },
			downloadLink: { type: 'object', default: {
				enable: false,
				text: '',
				showFileType: true,
			} }
		},

		edit: function ( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const { greydStyles, image } = props.attributes;
			const width = _.has( greydStyles, 'width' ) && !_.isEmpty( greydStyles.width ) ? greydStyles.width : ''; // '100%';
			const widthNum = parseInt( greyd.tools.getNumValue( width ), 10 );
			const widthUnit = greyd.tools.getUnitValue( width, 'px' );
			const height = _.has( greydStyles, 'height' ) && !_.isEmpty( greydStyles.height ) ? greydStyles.height : '';
			const heightNum = parseInt( greyd.tools.getNumValue( height ), 10 );

			if ( greydStyles?.aspectRatio == 'auto' ) {
				greydStyles.aspectRatio = '';
			}

			// Parse and calculate the aspect ratio
			let calculatedAspectRatio = null;
			if (greydStyles?.aspectRatio && greydStyles?.aspectRatio !== '') {
				if (greydStyles.aspectRatio.includes('/')) {
					const [widthRatio, heightRatio] = greydStyles.aspectRatio.split('/').map(Number);
					if (!isNaN(widthRatio) && !isNaN(heightRatio) && heightRatio !== 0) {
						calculatedAspectRatio = widthRatio / heightRatio;
					}
				}
			} else if (greydStyles?.aspectRatio === '0/0') {
				console.log('0/0 aspect ratio detected');
			};

			// Ensure aspectRatio is defined
			const aspectRatio = calculatedAspectRatio || greydStyles?.aspectRatio || '';

			// set default object fit
			let objectFit = 'cover';
			if ( _.has( greydStyles, 'objectFit' ) && !_.isEmpty( greydStyles.objectFit ) ) {
				objectFit = greydStyles.objectFit;
			}
			if ( !greydStyles?.objectFit &&
				props.isSelected && _.isEmpty(props.attributes.dynamic_parent )
			) {
				// set attribute only if block is selected and not in a template
				props.setAttributes({
					greydStyles: {
						...props.attributes.greydStyles,
						objectFit: 'cover',
					},
				});
			}

			/**
			 * Get media details for size selection.
			 * Resolution of rendered image.
			 * @since 2.1.0
			 */
			const mediaObj = wp.data.useSelect( (select) => {
				return image && image.type === 'file' && image.id !== -1 ? select( "core" ).getMedia( image.id ) : false;
			} );

			// get possible media sizes for prefered resolution
			const getPossibleMediaSizes = () => {
				options = [];
				if ( image.type === 'dynamic' ) {
					wp.data.select("core/block-editor").getSettings().imageSizes.forEach( size => {
						options.push( { value: size.slug, label: size.name } );
					} );
				}
				return options;
			};
			// get sizes of the current image
			const getMediaSizes = () => {
				options = [];
				if ( mediaObj?.media_details?.sizes ) {
					Object.keys(mediaObj.media_details.sizes).forEach( (size) => {
						var title = mediaObj.media_details.sizes[size].width+" x "+mediaObj.media_details.sizes[size].height;
						wp.blockEditor.SETTINGS_DEFAULTS.imageSizes.forEach( defaultSize => {
							if ( defaultSize.slug == size) {
								title = defaultSize.name+" ("+title+")";
							}
						} );
						options.push( { slug: size, title: title, width: mediaObj.media_details.sizes[size].width } );
					} );
					options.sort((a,b) => a.width - b.width);
					options.forEach( (option, i) => {
						options[i] = { value: option.slug, label: option.title };
					} );
				}
				return options;
			};
			// get url of current image size
			const getMediaUrl = () => {
				if ( mediaObj?.media_details?.sizes?.[props.attributes.sizeSlug] ) {
					return mediaObj.media_details.sizes[props.attributes.sizeSlug].source_url;
				}
				return image.url;
			};

			if ( typeof image !== 'undefined' && image.id !== -1 ) {
				// console.log(image);
				if ( mediaObj && _.has( mediaObj, 'source_url' ) && mediaObj.source_url != image.url ) {
					console.info( "dynamic url updated" );
					image.url = mediaObj.source_url;
					props.setAttributes( { image: image } );
				}
			}
			const hasCustomImage = image.type === 'file' && !_.isEmpty( image.url );

			const [ showCaption, setShowCaption ] = wp.element.useState( !!props.attributes.caption );

			// call function to make sure Block is updated when inside a template
			const blockProps = wp.blockEditor.useBlockProps({
				className: 'wp-block-image ' + props.attributes.className
			});

			const maybeGif = () => {
				return props.attributes.sizeSlug != 'full' && (
						( mediaObj?.mime_type == "image/gif" && getMediaSizes().length > 0 ) ||
						( image.type === 'dynamic' && !_.isEmpty( image.tag ) )
					);
			};

			return [
				el( wp.blockEditor.BlockControls, { group: 'block' }, [
					el( wp.components.ToolbarButton, {
						label: __( 'Caption', 'greyd_hub' ),
						icon: greyd.tools.getCoreIcon( 'caption' ),
						isPressed: showCaption,
						onClick: () => {
							setShowCaption( !showCaption );
						}
					} ),
				] ),
				// sidebar
				el( wp.blockEditor.InspectorControls, {}, [
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Select image", 'greyd_hub' ),
						holdsChange: ( image.type === 'file' && !_.isEmpty( image.url ) ) || ( image.type === 'dynamic' && !_.isEmpty( image.tag ) )
					}, [
						el( greyd.components.DynamicImageControl, {
							clientId: props.clientId,
							value: image,
							onChange: ( value ) => {
								props.setAttributes( {
									image: value
								} );
							}
						} ),
						mediaObj && getMediaSizes().length > 0 && el( greyd.components.OptionsControl, {
							label: __( "Resolution", 'greyd_hub' ),
							value: props.attributes.sizeSlug,
							options: getMediaSizes(),
							onChange: value => {
								props.setAttributes( { sizeSlug: value });
							}
						} ),
						image.type === 'dynamic' && !_.isEmpty( image.tag ) && el( greyd.components.OptionsControl, {
							label: __( "Preferred resolution", 'greyd_hub' ),
							help: __( "just a suggestion, the rendered image is selected from the actual available image sizes.", 'greyd_hub' ),
							value: props.attributes.sizeSlug,
							options: getPossibleMediaSizes(),
							onChange: value => {
								props.setAttributes( { sizeSlug: value });
							}
						} ),
						maybeGif() && el( "p", {
							className: 'components-base-control__help',
							style: {
								fontSize: 12,
								fontStyle: 'normal',
								color:' rgb(117, 117, 117)',
								marginTop: 0,
							},
						}, __( 'Animated gif images only preserve their animation with the \"Full Size\" resolution. Other sizes show one frame of animation only.', 'greyd_hub' ) ),
						
					] ),
					el( greyd.components.StylingControlPanel, {
						title: __( "Size", 'greyd_hub' ),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __( "Width", 'greyd_hub' ),
								attribute: "width",
								control: greyd.components.RangeUnitControl,
								units: [ "px", "%", "vw", "vh" ],
								max: 1200,
							},
							{
								label: __( "Height", 'greyd_hub' ),
								attribute: "height",
								control: greyd.components.RangeUnitControl,
								units: [ "px", "vw", "vh" ],
								max: 1200,
							},
							{
								label: __( "Aspect ratio", 'greyd_hub' ),
								attribute: "aspectRatio",
								control: greyd.components.SelectCustomControl,
								customControl: greyd.components.CustomAspectRatioControl,
								options: [
									{ value: "", label: __( "Original", 'greyd_hub' ) },
									{ value: "1", label: __( "Square - 1:1", 'greyd_hub' ) },
									{ value: "2/1", label: __( "Ultrawide - 2:1", 'greyd_hub' ) },
									{ value: "1/2", label: __( "Ultrahigh - 1:2", 'greyd_hub' ) },
									{ value: "4/3", label: __( "Default - 4:3", 'greyd_hub' ) },
									{ value: "3/4", label: __( "Portrait - 3:4", 'greyd_hub' ) },
									{ value: "3/2", label: __( "Classic - 3:2", 'greyd_hub' ) },
									{ value: "2/3", label: __( "Classic portrait - 2:3", 'greyd_hub' ) },
									{ value: "5/4", label: __( "Social - 5:4", 'greyd_hub' ) },
									{ value: "4/5", label: __( "Social portrait - 4:5", 'greyd_hub' ) },
									{ value: "16/9", label: __( "Wide - 16:9", 'greyd_hub' ) },
									{ value: "9/16", label: __( "High - 9:16", 'greyd_hub' ) },
								],
								style: (greydStyles?.width && greydStyles?.height) ? {
									opacity: 0.5,
									cursor: 'not-allowed',
								} : {},
							},
							...( (greydStyles?.width && greydStyles?.height) ? [
								{
									control: wp.components.BaseControl,
									children: [
										el( wp.components.Notice, {
											status: 'warning',
											isDismissible: false,
											children: [
												__( "When both the width & height are set, the aspect ratio is not used. Remove one of the values to use the aspect ratio.", 'greyd_hub' )
											],
										} ),
									]
								}
							] : []),
							{
								label: __( "Scale", 'greyd_hub' ),
								attribute: "objectFit",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ value: 'cover', label: __( 'Cover', 'greyd_hub' ) },
									{ value: 'contain', label: __( 'Contain', 'greyd_hub' ) },
									{ value: 'fill', label: __( "Fill", 'greyd_hub' ) }
								],
								help: (
									_.has( greydStyles, 'objectFit' ) ? (
										greydStyles.objectFit == 'fill' ? (
											__( "The image is stretched and compressed to completely fill the space.", 'greyd_hub' )
										) : ( greydStyles.objectFit == 'contain' ? (
											__( "The image is scaled to fill the space without cropping or distorting.", 'greyd_hub' )
										) : (
											__( "The image is scaled and cropped to fill the entire space without distorting it.", 'greyd_hub' )
										) )
									) : __( "The image is scaled and cropped to fill the entire space without distorting it.", 'greyd_hub' )
								)
							}
						]
					} ),
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Focal point", 'greyd_hub' ),
						holdsChange: props.attributes.focalPoint.x != '0.50' || props.attributes.focalPoint.y != '0.50',
						initialOpen: false
					}, [
						el( wp.components.FocalPointPicker, {
							value: props.attributes.focalPoint,
							url: image.url,
							onChange: ( value ) => {
								props.setAttributes( {
									focalPoint: value
								} );
							},
							help: __( "This property is only applied when the scaling is set to 'Cover'.", 'greyd_hub' )
						} ),
					] ),
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Download", 'greyd_hub' ),
						holdsChange: props.attributes.downloadLink && props.attributes.downloadLink.enable,
						initialOpen: false
					}, [
						// toggle enable
						el( wp.components.ToggleControl, {
							label: __( 'Show download link below', 'greyd_hub' ),
							checked: props.attributes.downloadLink && props.attributes.downloadLink.enable,
							onChange: ( value ) => {
								props.setAttributes( {
									downloadLink: {
										...props.attributes.downloadLink,
										enable: value
									}
								} );
							}
						} ),

						// text input
						props.attributes.downloadLink && props.attributes.downloadLink.enable ? el( wp.components.TextControl, {
							label: __( 'Link text', 'greyd_hub' ),
							value: props.attributes.downloadLink.text,
							onChange: ( value ) => {
								props.setAttributes( {
									downloadLink: {
										...props.attributes.downloadLink,
										text: value
									}
								} );
							}
						} ) : '',

						// toggle to show file type & size
						props.attributes.downloadLink && props.attributes.downloadLink.enable ? el( wp.components.ToggleControl, {
							label: __( 'Display file type and size', 'greyd_hub' ),
							checked: props.attributes.downloadLink?.showFileType,
							onChange: ( value ) => {
								props.setAttributes( {
									downloadLink: {
										...props.attributes.downloadLink,
										showFileType: value
									}
								} );
							},
							help: props.attributes.downloadLink?.showFileType ? __( 'As the image is loaded dynamically in the front end, only a sample value is displayed here.', 'greyd_hub' ) : ''
						} ) : '',
					] ),
				] ),

				// toolbar
				el( greyd.components.ToolbarDynamicImageControl, {
					value: image,
					clientId: props.clientId,
					onChange: ( value ) => {
						props.setAttributes( {
							image: value
						} );
					}
				} ),

				// preview
				el( 'div', { ...blockProps }, [
					!_.isEmpty( props.attributes.dynamic_parent ) || !props.isSelected ? (
						// without ResizableBox (in Templates or when not selected)
						hasCustomImage ? el( 'img', {
							id: props.attributes.anchor,
							className: props.attributes.greydClass,
							src: getMediaUrl(),
							style: {
								height: _.isEmpty( height ) ? 'auto' : height,
								width:  _.isEmpty( width ) ? 'fit-content' : width,
								transition: 'none',
								objectFit: objectFit,
								...( objectFit === 'cover' ? {
									objectPosition: ( props.attributes.focalPoint.x * 100 ) + '% ' + ( props.attributes.focalPoint.y * 100 ) + '%'
								} : null ),
								...(calculatedAspectRatio ? { aspectRatio: `${calculatedAspectRatio}` } : {})
								//aspectRatio: aspectRatio

							}
						} ) : el( 'div', {
							id: props.attributes.anchor,
							className: 'dynamic_img__preview',
							style: {
								height: _.isEmpty( height ) ? '200px' : height,
								width: width
							}
						}, [ _.isEmpty( image.tag ) ? "" : el( "span", {}, image.tag ), greyd.tools.getCoreIcon( 'image' ) ] )
					) : (
						// with ResizableBox
						el( wp.components.ResizableBox, {
							size: {
								height: _.isEmpty( height ) ? ( hasCustomImage ? 'auto' : '200px' ) : height,
								width: _.isEmpty( width ) ? 'fit-content' : width
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
							onResizeStop: ( event, direction, element, delta ) => {

								if ( delta.height === 0 && delta.width === 0 ) return;

								const newGreydStyles = { ...greydStyles };

								// set width
								if ( delta.width !== 0 ) {
									if ( widthNum && widthUnit === '%' ) {
										newGreydStyles.width = parseInt( Math.min( widthNum, 100 ) + ( delta.width / ( element.parentNode.offsetWidth / 100 ) ), 10 ) + widthUnit;
									} else {
										newGreydStyles.width = parseInt( element.offsetWidth, 10 ) + 'px';
									}
								}

								// set height
								if ( delta.height !== 0 ) {
									if ( isNaN( heightNum ) ) {
										if ( direction !== 'right' ) {
											newGreydStyles.height = element.parentNode.offsetHeight + 'px';
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
							hasCustomImage ? el( 'img', {
								id: props.attributes.anchor,
								className: props.attributes.greydClass,
								src: getMediaUrl(),
								style: {
									width: _.isEmpty( width ) ? 'fit-content' : width,
									transition: 'none',
									objectFit: objectFit,
									...( objectFit === 'cover' ? {
										objectPosition: ( props.attributes.focalPoint.x * 100 ) + '% ' + ( props.attributes.focalPoint.y * 100 ) + '%'
									} : null ),
									aspectRatio: aspectRatio
								}
							} ) : el( 'div', {
								id: props.attributes.anchor,
								className: 'dynamic_img__preview'
							}, [ _.isEmpty( image.tag ) ? "" : el( "span", {}, image.tag ), greyd.tools.getCoreIcon( 'image' ) ] )
						) )
					),
					showCaption ? el( wp.blockEditor.RichText, {
						tagName: 'figcaption',
						// multiline: "br",
						value: props.attributes.caption,
						placeholder: __( "Insert caption", 'greyd_hub' ),
						onChange: ( value ) => {
							// console.log("change");
							props.setAttributes( { caption: value } );
						},
					} ) : ''
				] ),
				// download link
				props.attributes.downloadLink && props.attributes.downloadLink.enable ? el( 'div', {
					className: 'wp-block-image__download'
				}, [
					el( 'a', {
						href: image.url,
						className: props.attributes.downloadLink.classes,
						...props.attributes.downloadLink.attributes
					}, (
						( props.attributes.downloadLink.text ? props.attributes.downloadLink.text : 'Download' )
						+
						( props.attributes.downloadLink?.showFileType ? ' (IMG, 42 MB)' : '' )
					) )
				] ) : '',
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
							className: _.has( attributes, 'className' ) ? attributes.className : null,
							align: _.has( attributes, 'align' ) ? attributes.align : null,
							image: {
								id: _.has( attributes, 'id' ) ? attributes.id : -1,
								url: _.has( attributes, 'url' ) ? attributes.url : '',
								tag: '',
								type: 'file',
							},
							greydStyles: {
								width: _.has( attributes, 'width' ) ? attributes.width + 'px' : null,
								height: _.has( attributes, 'height' ) ? attributes.height + 'px' : null
							}
						};

						if ( _.has( attributes, 'dynamic_fields' ) && typeof attributes.dynamic_fields[ 0 ] !== 'undefined' ) {
							newAtts.dynamic_fields = [ {
								key: 'image',
								title: attributes.dynamic_fields[ 0 ].title,
								enable: true,
							} ];
						}
						if ( _.has( attributes, 'dynamic_parent' ) ) {
							newAtts.dynamic_parent = attributes.dynamic_parent;
						}
						if ( _.has( attributes, 'dynamic_value' ) ) {
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
							className: _.has( attributes, 'className' ) ? attributes.className : null,
							align: _.has( attributes, 'align' ) ? attributes.align : null,
							id: _.has( attributes.image, 'id' ) ? attributes.image.id : null,
							url: _.has( attributes.image, 'url' ) ? attributes.image.url : "https://s.w.org/images/core/5.3/MtBlanc1.jpg",
							width: _.has( attributes.greydStyles, 'width' ) ? parseInt( attributes.greydStyles.width ) : null,
						};

						if ( _.has( attributes, 'dynamic_fields' ) ) {
							newAtts.dynamic_fields = [ {
								key: 'id',
								title: attributes.dynamic_fields[ 0 ].title,
								enable: true,
							} ];
						}
						if ( _.has( attributes, 'dynamic_parent' ) ) {
							newAtts.dynamic_parent = attributes.dynamic_parent;
						}
						if ( _.has( attributes, 'dynamic_value' ) ) {
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

} )( window.wp );