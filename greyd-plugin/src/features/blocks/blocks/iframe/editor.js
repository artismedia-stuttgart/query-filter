/**
 * Registers all greyd/ blocks.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	const transformDefaultAtts = function(attributes) {

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
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
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
				] ),

				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
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

			const blockProps = wp.blockEditor.useBlockProps.save();
			
			return el( wp.element.Fragment, {}, [
				el( 'div', blockProps, [
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
			 * proper blockProps support
			 */
			{
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
				}
			},

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
	
} )( window.wp );