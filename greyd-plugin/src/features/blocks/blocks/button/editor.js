/**
 * Editor Script for Greyd Button Blocks.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	const buttonStyles = [
		{
			name: 'prim',
			label: __( "Primary button", 'greyd_hub' ),
			isDefault: true
		},
		{
			name: 'sec',
			label: __( "Secondary button", 'greyd_hub' )
		},
		{
			name: 'trd',
			label: __( "Alternative button", 'greyd_hub' )
		},
		...(
			greyd.data?.is_greyd_classic
			? [
				{
					name: 'link-prim',
					label: __( "Link", 'greyd_hub' )
				},
				{
					name: 'link-sec',
					label: __( "Secondary link", 'greyd_hub' )
				}
			]
			: [
				{
					name: 'clear',
					label: __( 'Clear', 'greyd_hub' )
				}
			]
		)
	];

	// ready/init
	wp.domReady(function () {

		if ( greyd.data?.is_greyd_classic ) {
			buttonStyles.forEach( style => {
				style && wp.blocks.registerBlockStyle( 'core/button', style );
			} )
			wp.blocks.unregisterBlockStyle( 'core/button', 'fill' );
			wp.blocks.unregisterBlockStyle( 'core/button', 'outline' );
		}
	});

	/**
	 * Additional tools/helper functions.
	 * moved from greyd.tools
	 */
	greyd.tools.button = new function() {

	};

	/**
	 * Register Buttons Block (wrapper).
	 */
	wp.blocks.registerBlockType( 'greyd/buttons', {
		title: __('Buttons (Greyd)', 'greyd_hub'),
		description: __("A group of buttons or links", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('buttons'),
		category: 'greyd-blocks',
		keywords: [ 'button', 'link', 'trigger', 'toggle', 'button', 'btn' ],
		supports: {
			anchor: true,
			align: true,
			spacing: {
				margin: true,
				padding: true,
				blockGap: true,
				__experimentalDefaultControls: {
					blockGap: true
				}
			},
			layout: {
				allowSwitching: false,
				allowEditing: false,
				default: {
					type: 'flex',
				}
			}
		},
		example: {
			attributes: {
				align: 'center',
			},
			innerBlocks: [
				{
					name: 'greyd/button',
					attributes: {
						content: __( 'Button', 'greyd_hub' ),
					},
				},
			]
		},
		attributes: {
			align: { type: 'string', default: "" },
		},

		edit: function( props ) {
			return el( 'div', { 
				id: props.attributes.anchor, 
				className: props.attributes?.className 
			}, el( wp.blockEditor.InnerBlocks, {
				template: [[ 'greyd/button', {} ]],
				// templateLock: false,
				allowedBlocks: [ 'greyd/button' ],
				orientation: 'horizontal'
			} ) );
		},
		save: function( props ) {
			return el( 'div', { 
				id: props.attributes.anchor, 
				className: props.attributes?.className 
			}, el( wp.blockEditor.InnerBlocks.Content ) );
		},

		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'core/buttons' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert core buttons to greyd buttons');
						// console.log(attributes);
						// console.log(innerBlocks);
						
						var inner = [];
						for (var i=0; i<innerBlocks.length; i++) {
							// console.log(innerBlocks[i]);
							var inneratts = {};
							if (_.has(innerBlocks[i], 'attributes')) {
								inneratts = greyd.tools.transformDefaultAtts(innerBlocks[i].attributes);
								if (_.has(innerBlocks[i].attributes, 'text')) inneratts.content = innerBlocks[i].attributes.text;
								if (_.has(innerBlocks[i].attributes, 'size')) inneratts.size = innerBlocks[i].attributes.size;
								var icon = { content: '', position: 'after', margin: '10px', size: '100%' };
								if (_.has(innerBlocks[i].attributes, 'icon')) icon.content = innerBlocks[i].attributes.icon;
								if (_.has(innerBlocks[i].attributes, 'icon_align') && innerBlocks[i].attributes.icon_align == 'left') icon.position = 'before';
								if (_.has(innerBlocks[i].attributes, 'icon_margin')) icon.margin = innerBlocks[i].attributes.icon_margin;
								if (_.has(innerBlocks[i].attributes, 'icon_size')) icon.size = innerBlocks[i].attributes.icon_size;
								if (!_.isEmpty(icon)) inneratts.icon = icon;
								if (_.has(innerBlocks[i].attributes, 'url')) {
									var trigger = {
										type: 'link',
										params: { url: innerBlocks[i].attributes.url}
									};
									if (_.has(innerBlocks[i].attributes, 'linkTarget') && innerBlocks[i].attributes.linkTarget == '_blank')
										trigger.params.opensInNewTab = true;
									inneratts.trigger = trigger;
								}

							}
							// console.log(inneratts);
							inner.push(wp.blocks.createBlock(
								'greyd/button',
								inneratts
							));
						}

						return wp.blocks.createBlock(
							'greyd/buttons',
							attributes,
							inner
						);
					},
				},
				{
					type: 'block',
					blocks: [ 'core/heading', 'core/paragraph' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert heading/paragraph to greyd buttons');
						// console.log(attributes);
						// console.log(innerBlocks);
						
						var newatts = greyd.tools.transformDefaultAtts(attributes);
						var inner = [];
						var inneratts = {};
						if (_.has(attributes, 'content')) {
							inneratts.content = attributes.content;
						}

						// console.log(inneratts);
						inner.push(wp.blocks.createBlock(
							'greyd/button',
							inneratts
						));

						return wp.blocks.createBlock(
							'greyd/buttons',
							newatts,
							inner
						);
					},
				}

			],
			to: [
				{
					type: 'block',
					blocks: [ 'core/buttons' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert greyd buttons to core buttons');
						// console.log(attributes);
						// console.log(innerBlocks);

						var inner = [];
						for (var i=0; i<innerBlocks.length; i++) {
							// console.log(innerBlocks[i]);
							var inneratts = {};
							if (_.has(innerBlocks[i], 'attributes')) {
								inneratts = greyd.tools.transformDefaultAtts(innerBlocks[i].attributes);
								if (_.has(innerBlocks[i].attributes, 'content')) inneratts.text = innerBlocks[i].attributes.content;
								if (_.has(innerBlocks[i].attributes, 'size')) inneratts.size = innerBlocks[i].attributes.size;
								if (_.has(innerBlocks[i].attributes, 'icon')) {
									inneratts.icon = innerBlocks[i].attributes.icon.content;
									inneratts.icon_align = innerBlocks[i].attributes.icon.position == 'before' ? 'left' : '';
									inneratts.icon_margin = innerBlocks[i].attributes.icon.margin;
									inneratts.icon_size = innerBlocks[i].attributes.icon.size;
								}
							}
							// console.log(inneratts);
							inner.push(wp.blocks.createBlock(
								'core/button',
								inneratts
							))
						}

						return wp.blocks.createBlock(
							'core/buttons',
							attributes,
							inner
						);
					},
				}

			]
		}
	} );


	/**
	 * Register Button Block.
	 */
	wp.blocks.registerBlockType( 'greyd/button', {
		apiVersion: 2,
		title: __('Button (Greyd)', 'greyd_hub'),
		description: __("Button/link with trigger picker", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('button'),
		category: 'greyd-blocks',
		parent: [ 'greyd/buttons' ],
		keywords: [ 'link', 'trigger' ],
		styles: buttonStyles,
		supports: {
			anchor: true,
			defaultStylePicker: false
			// color: {
			// 	text: true,
			// 	background: false,
			// 	gradients: false,
			// },
		},
		example: {
			attributes: {
				content: 'Button',
				icon: {
					content: 'arrow_right',
					position: 'after',
					size: '100%',
					margin: '10px'
				}
			},
		},

		attributes: {
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object' },
			customStyles: { type: 'object' },
			trigger: { type: 'object' },
			size: { type: 'string', default: '' },
			content: { type: 'string' },
			icon: { type: 'object', properties: {
				content: { type: "string" },
				position: { type: "string" },
				size: { type: "string" },
				margin: { type: "string" },
			}, default: {
				content: '',
				position: 'after',
				size: '100%',
				margin: '10px'
			} },
			custom: { type: 'bool', default: 0 }
		},

		edit: function( props ) {

			// check if any 'width' is set.
			const hasWidth = () => !_.isEmpty(props.attributes.greydStyles?.width) ||
									!_.isEmpty(props.attributes.greydStyles?.responsive?.lg?.width) ||
									!_.isEmpty(props.attributes.greydStyles?.responsive?.md?.width) ||
									!_.isEmpty(props.attributes.greydStyles?.responsive?.sm?.width);

			// apply 'width' style to block wrapper element.
			// the editor preview has an additional wrapper around the greydClass element which falsifies the 'width' calculation.
			const adjustStyles = () => {
				// console.log(props.attributes.greydStyles);
				return props.clientId && hasWidth() && [
					el( greyd.components.RenderPreviewStyles, {
						selector: "wp-block#block-"+props.clientId,
						styles: {
							"": {
								width: props.attributes.greydStyles.width ?? null,
								responsive: props.attributes.greydStyles.responsive ? {
									lg: props.attributes.greydStyles.responsive.lg?.width ? { width: props.attributes.greydStyles.responsive.lg.width } : null,
									md: props.attributes.greydStyles.responsive.md?.width ? { width: props.attributes.greydStyles.responsive.md.width } : null,
									sm: props.attributes.greydStyles.responsive.sm?.width ? { width: props.attributes.greydStyles.responsive.sm.width } : null,
								} : null
							},
							[" ."+props.attributes.greydClass]: { width: "100%"},
						}
					} )
				];
			};

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}
			let className = props.attributes?.className || '';
			const extraClass = className.indexOf('is-style-link-') === -1 ? 'button' : 'link';

			var classNames = [ extraClass, className, props.attributes.greydClass, props.attributes.size ].join(' ');

			// call function to make sure Block is updated when inside a template
			const blockProps = wp.blockEditor.useBlockProps( { id: props.attributes.anchor, className: classNames } );

			return [

				// sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					// icon
					el( greyd.components.ButtonIconControl, {
						enableHideEmpty: true,
						value: props.attributes.icon,
						onChange: function(value) {
							props.setAttributes({ icon: value });
						},
					} ),
				] ),

				// sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// size
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Size", 'greyd_hub' ),
						holdsChange: !_.isEmpty(props.attributes.size)
					},
						[
							el( greyd.components.ButtonGroupControl, {
								value: props.attributes.size,
								// label: __( "Size", 'greyd_hub' ),
								options: [
									{ value: "is-size-small", label: __( "Small", 'greyd_hub' ) },
									{ value: "", label: __( "Default", 'greyd_hub' ) },
									{ value: "is-size-big", label: __( "Big", 'greyd_hub' ) },
								],
								onChange: function(value) {
									props.setAttributes( { size: value } );
								},
							} ),
						]
					),
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
							},
							// if any 'width' is set, the button can be bigger than the content, so we can give alignment options.
							{
								...(
									props.attributes.icon && props.attributes.icon?.content
									? {
										label: __( "Align Icon with Text", 'greyd_hub' ),
										help: __( "Align the icon next to the text of the button.", 'greyd_hub' ),
									}
									: {
										label: __( "Align Content", 'greyd_hub' ),
										// help: __( "Align the content of the button.", 'greyd_hub' ),
									}
								),
								attribute: "--align-content",
								control: wp.components.ToggleControl,
								hidden: !hasWidth() ? true : { 'lg': true, 'md': true, 'sm': true },
								checked: props.attributes.greydStyles?.["--align-content"],
								onChange: function(value) {
									// console.log( "set --align-content: "+(!props.attributes.greydStyles?.["--align-content"] ? "true" : "false"));
									if ( !props.attributes.greydStyles?.["--align-content"] === false ) {
										// console.log( "unset justifyContent ..." );
										delete props.attributes.greydStyles["--align-content"];
										delete props.attributes.greydStyles.justifyContent;
										delete props.attributes.greydStyles.responsive?.lg?.justifyContent;
										delete props.attributes.greydStyles.responsive?.md?.justifyContent;
										delete props.attributes.greydStyles.responsive?.sm?.justifyContent;
										props.setAttributes( { greydStyles: props.attributes.greydStyles } );
									}
								},
							},
							{
								// label: __("Alignment of the content", 'greyd_hub'),
								attribute: "justifyContent",
								control: greyd.components.ButtonGroupControl,
								hidden: !hasWidth() || !props.attributes.greydStyles?.["--align-content"] ? true : false,
								options: [
									{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignLeft'), value: 'flex-start' },
									{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignCenter'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignRight'), value: 'flex-end' },
									{ label: __("Spreaded", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignSpaceBetween'), value: 'space-between' },
								],
							}
						]
					} ),

					// custom button
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Individual button", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: props.attributes.custom ? true : false
					},
						[
							el( wp.components.ToggleControl, {
								label: __( "Overwrite the design of the button individually", 'greyd_hub' ),
								checked: props.attributes.custom,
								onChange: function(value) {
									props.setAttributes( { custom: !!value } );
								},
							} ),
						]
					),
					el( greyd.components.CustomButtonStyles, {
						enabled: props.attributes.custom ? true : false,
						blockProps: props,
						parentAttr: 'customStyles'
					} )
				] ),

				// preview
				el( 'div', { ...blockProps }, [
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes.icon,
						position: 'before'
					} ),
					el( wp.blockEditor.RichText, {
						format: 'string',
						tagName: 'span',
						style: { flex: props.attributes.greydStyles?.["--align-content"] ? '0 1 auto' : '1' },
						value: props.attributes.content,
						placeholder: __( 'Button', 'greyd_hub' ),
						allowedFormats: [ 'core/bold', 'core/italic', 'core/strikethrough', 'greyd/dtag', 'core/highlight' ],
						onChange: function(value) {
							props.setAttributes( { content: value } );
						},
					} ),
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes.icon,
						position: 'after'
					} ),
				] ),
				// normal styles
				el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass,
					styles: {
						"": props.attributes.greydStyles,
					}
				} ),
				// fix 'width'
				adjustStyles(),
				// custom styles
				!props.attributes.custom ? null : el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass,
					styles: {
						"": props.attributes.customStyles,
					},
					important: true
				} )
			];
		},
		save: function( props ) {

			const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';

			return el( wp.element.Fragment, {}, [
				el( 'a', {
					id: props.attributes.anchor,
					role: "trigger", // we replace that trigger-placeholder via render.php
					className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
				}, [
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes.icon,
						position: 'before'
					} ),
					(
						_.isEmpty( props.attributes.icon.content )
						? el( wp.blockEditor.RichText.Content, {
							tagName: null,
							value: props.attributes.content
						} )
						: el( wp.blockEditor.RichText.Content, {
							tagName: 'span',
							style: { flex: props.attributes.greydStyles?.["--align-content"] ? '0 1 auto' : '1' },
							value: props.attributes.content
						} )
					),
					el( greyd.components.RenderButtonIcon, {
						value: props.attributes.icon,
						position: 'after'
					} ),
				] ),
				!props.attributes.custom ? null : el( greyd.components.RenderSavedStyles, {
					selector: props.attributes.greydClass,
					styles: {
						"": props.attributes.customStyles,
					},
					important: true
				} )
			] );
		},

		deprecated: [
			/**
			 * Do not render empty span when content is empty.
			 * @since 1.5.1
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					trigger: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string' },
					icon: { type: 'object', properties: {
						content: { type: "string" },
						position: { type: "string" },
						size: { type: "string" },
						margin: { type: "string" },
					}, default: {
						content: '',
						position: 'after',
						size: '100%',
						margin: '10px'
					} },
					custom: { type: 'bool', default: 0 }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function( props ) {

					const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'a', {
							id: props.attributes.anchor,
							role: "trigger", // we replace that trigger-placeholder via render.php
							className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'before'
							} ),
							el( 'span', {
								style: { flex: '1' },
								dangerouslySetInnerHTML: { __html: props.attributes.content }
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'after'
							} ),
						] ),
						!props.attributes.custom ? null : el( greyd.components.RenderSavedStyles, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			/**
			 * RenderSavedStylesDeprecated
			 * @deprecated since 1.3.7
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					trigger: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string' },
					icon: { type: 'object', properties: {
						content: { type: "string" },
						position: { type: "string" },
						size: { type: "string" },
						margin: { type: "string" },
					}, default: {
						content: '',
						position: 'after',
						size: '100%',
						margin: '10px'
					} },
					custom: { type: 'bool', default: 0 }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function( props ) {

					const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'a', {
							id: props.attributes.anchor,
							role: "trigger", // we replace that trigger-placeholder via render.php
							className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'before'
							} ),
							el( 'span', {
								style: { flex: '1' },
								dangerouslySetInnerHTML: { __html: props.attributes.content }
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'after'
							} ),
						] ),
						!props.attributes.custom ? null : el( greyd.components.RenderSavedStylesDeprecated, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			/**
			 * trigger="placeholder"
			 * @deprecated since 1.3.3
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					trigger: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string' },
					icon: { type: 'object', properties: {
						content: { type: "string" },
						position: { type: "string" },
						size: { type: "string" },
						margin: { type: "string" },
					}, default: {
						content: '',
						position: 'after',
						size: '100%',
						margin: '10px'
					} },
					custom: { type: 'bool', default: 0 }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function( props ) {

					const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'a', {
							id: props.attributes.anchor,
							trigger: "placeholder", // we replace that trigger-placeholder via render.php
							className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'before'
							} ),
							el( 'span', {
								style: { flex: '1' },
								dangerouslySetInnerHTML: { __html: props.attributes.content }
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'after'
							} ),
						] ),
						!props.attributes.custom ? null : el( greyd.components.RenderSavedStylesDeprecated, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			/**
			 * RenderSavedStylesDeprecated
			 * @deprecated since 1.1.2
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					trigger: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string' },
					icon: { type: 'object', properties: {
						content: { type: "string" },
						position: { type: "string" },
						size: { type: "string" },
						margin: { type: "string" },
					}, default: {
						content: '',
						position: 'after',
						size: '100%',
						margin: '10px'
					} },
					custom: { type: 'bool', default: 0 }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function( props ) {

					const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'a', {
							id: props.attributes.anchor,
							trigger: "placeholder",
							className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
						}, [
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'before'
							} ),
							el( 'span', {
								style: { flex: '1' },
								dangerouslySetInnerHTML: { __html: props.attributes.content }
							} ),
							el( greyd.components.RenderButtonIcon, {
								value: props.attributes.icon,
								position: 'after'
							} ),
						] ),
						!props.attributes.custom ? null : el( greyd.components.RenderSavedStylesDeprecated, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					] );
				}
			},
			/**
			 * RenderButtonIconDeprecated
			 * @deprecated
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					customStyles: { type: 'object' },
					trigger: { type: 'object' },
					size: { type: 'string', default: '' },
					content: { type: 'string' },
					icon: { type: 'object', properties: {
						content: { type: "string" },
						position: { type: "string" },
						size: { type: "string" },
						margin: { type: "string" },
					}, default: {
						content: '',
						position: 'after',
						size: '100%',
						margin: '10px'
					} },
					custom: { type: 'bool', default: 0 }
				},
				supports: {
					anchor: true,
					defaultStylePicker: false
				},
				save: function( props ) {
					const extraClass = _.has(props.attributes, "className") && props.attributes.className.indexOf('is-style-link-') > -1 ? 'link' : 'button';
		
					return el( wp.element.Fragment, {}, [
						el( 'a', {
							id: props.attributes.anchor,
							trigger: "placeholder", // we replace that trigger-placeholder via render.php
							className: [ extraClass, props.attributes.className, props.attributes.greydClass, props.attributes.size ].join(' ')
						}, [
							el( greyd.components.RenderButtonIconDeprecated, {
								value: props.attributes.icon,
								position: 'before'
							} ),
							el( 'span', {
								style: { flex: '1' },
								dangerouslySetInnerHTML: { __html: props.attributes.content }
							} ),
							el( greyd.components.RenderButtonIconDeprecated, {
								value: props.attributes.icon,
								position: 'after'
							} ),
						] ),
						!props.attributes.custom ? null : el( greyd.components.RenderSavedStylesDeprecated, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					] );
				},

			},
		]
	} );


	/**
	 * Register custom attributes to core blocks.
	 * - core/button
	 *
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (_.has(settings, 'apiVersion') && settings.apiVersion > 1) {
			// console.log(name);
			// console.log(settings);

			if (name == 'core/button') {
				delete settings.attributes.width;
				settings.attributes.size = { type: 'string', default: '' };
				settings.attributes.min_width = { type: 'string', default: '' };
				// settings.attributes.versal = { type: 'boolean', default: false };
				settings.attributes.icon = { type: 'string', default: '' };
				settings.attributes.icon_align = { type: 'string', default: '' };
				settings.attributes.icon_margin = { type: 'string', default: '10px' };
				settings.attributes.icon_size = { type: 'string', default: '100%' };
				settings.attributes.icon_hideEmpty = { type: 'boolean', default: true };
				// settings.supports.align = false;
				// console.log(settings);
			}

		}
		return settings;

	}

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/button',
		registerBlockTypeHook
	);


	/**
	 * Add custom edit controls to core Blocks.
	 *
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {

			/**
			 * Extend the core button
			 */
			if (props.name == "core/button") {
				// console.log("add size support to: "+props.name);

				var icon_hidden = "";
				if (props.attributes.icon == "") icon_hidden = " hidden";

				const makeInspectorControls = function() {
					return [
				
						el( wp.components.PanelBody, {
							title: __('Design', 'greyd_hub'),
							initialOpen: true
						}, [
							el( 'div', {}, [
								el( greyd.components.ButtonGroupControl, {
									label: __("Size", 'greyd_hub'),
									value: props.attributes.size,
									options: [
										{ label: __("Small", 'greyd_hub'), value: 'is-size-small' },
										{ label: __("Default", 'greyd_hub'), value: 'is-size-normal' },
										{ label: __("Big", 'greyd_hub'), value: 'is-size-big' },
									],
									onChange: function(value) { 

										// onClear
										if ( _.isEmpty(value) ) {
											if ( !_.has(props.attributes, 'className') || _.isEmpty(props.attributes.className)) return;
	
											// remove 'is-size-big', 'is-size-small' and 'is-size-normal' from className
											var classNames = props.attributes.className.split(/is-size-big\s*|is-size-small\s*|is-size-normal\s*/g);
											// clean
											classNames = greyd.tools.cleanClassArray(classNames);
											// console.log(classNames);
											props.setAttributes( { size: '', className: classNames.join(' ') } );
										}

										// onChange
										else {
											var classNames = [ value ];
											// saved className
											if (_.has(props.attributes, 'className') && !_.isEmpty(props.attributes.className)) {
												// remove 'is-size-big', 'is-size-small' and 'is-size-normal'
												var oldClasses = props.attributes.className.split(/is-size-big\s*|is-size-small\s*|is-size-normal\s*/g);
												// add all other
												classNames.push( ...oldClasses );
											}
											// clean
											classNames = greyd.tools.cleanClassArray(classNames);
											// console.log(classNames);
											props.setAttributes( { size: value, className: classNames.join(' ') } ); 
										}
									}
								} ),
								el( wp.components.__experimentalUnitControl, {
									label: __("Width", 'greyd_hub'),
									className: 'is-edge-layout',
									value: props.attributes.min_width,
									onChange: function(value) { 
										var css_id = (_.has(props.attributes, 'anchor') && props.attributes.anchor != "") ? props.attributes.anchor : 'block-'+props.clientId;
										props.setAttributes( { min_width: value, inline_css_id: css_id } ); 
									},
								} ),
							] ),
						] ),
						el( wp.components.PanelBody, {
							title: __('Icon', 'greyd_hub'),
							initialOpen: true
						}, [
							el( greyd.components.IconPicker, {
								value: props.attributes.icon,
								// icons: greyd.data.icons,
								onChange: function(value) { 
									var css_id = (_.has(props.attributes, 'anchor') && props.attributes.anchor != "") ? props.attributes.anchor : 'block-'+props.clientId;
									props.setAttributes( { icon: value, inline_css_id: css_id } ); 
								},
							} ),
							el( wp.components.BaseControl, { className: icon_hidden }, [
								el( greyd.components.ButtonGroupControl, {
									label: __('Position', 'greyd_hub'),
									value: props.attributes.icon_align,
									options: [
										{ label: __("Left", 'greyd_hub'), value: 'left' },
										{ label: __("Right", 'greyd_hub'), value: '' },
									],
									onChange: function(value) { props.setAttributes( { icon_align: value } ); },
								} ),
							] ),
							el( 'div', { className: "greyd-inspector-wrapper greyd-2"+icon_hidden }, [
								el( wp.components.__experimentalUnitControl, {
									label: __("Space", 'greyd_hub'),
									className: 'is-edge-layout',
									value: props.attributes.icon_margin,
									onChange: function(value) { props.setAttributes( { icon_margin: value } ); },
								} ),
								el( wp.components.__experimentalUnitControl, {
									label: __("Size", 'greyd_hub'),
									className: 'is-edge-layout',
									value: props.attributes.icon_size,
									onChange: function(value) { props.setAttributes( { icon_size: value } ); },
								} ),
							] ),
							el( wp.components.BaseControl, { className: icon_hidden }, [
								el( wp.components.ToggleControl, {
									label: __( "Render the button when text is empty", 'greyd_hub' ),
									checked: !props.attributes.icon_hideEmpty,
									onChange: function(value) { props.setAttributes( { icon_hideEmpty: !value } ); },
									help: (
										props.attributes.icon_hideEmpty
										? __( "Enable this option to use this as an icon button.", 'greyd_hub' )
										: __( "Disable this within dynamic templates to keep the button optional.", 'greyd_hub' )
									)
								} )
							] ),
						] )
					]
				};
				
				var style = "";
				if (_.has(props.attributes, 'min_width') && props.attributes.min_width != "") {
					var min_width = props.attributes.min_width;
					style += "#block-"+props.clientId+" { width: "+min_width+"; } ";
					style += "#block-"+props.clientId+" > div { width: 100%; } ";
				}
				if (_.has(props.attributes, 'icon') && props.attributes.icon != "") {
					var pos = "after";
					var margin = "left";
					if (_.has(props.attributes, 'icon_align') && props.attributes.icon_align == 'left') {
						pos = "before";
						margin = "right";
					}
					var icon_margin = _.has(props.attributes, 'icon_margin') ? props.attributes.icon_margin : "10px";
					var icon_size = _.has(props.attributes, 'icon_size') ? props.attributes.icon_size : "100%";
					style += "#block-"+props.clientId+" > div:"+pos+" { "+
								"content: '"+greyd.data.icons[props.attributes.icon]['content']+"' / ''; "+
								"font-family: ElegantIcons; "+
								"vertical-align: middle; "+
								"margin-"+margin+": "+icon_margin+"; "+
								"font-size: "+icon_size+"; "+
							"} ";
				}
				
				return el( wp.element.Fragment, { }, [
					// inspector
					el( wp.blockEditor.InspectorControls, { }, [
						// design and icon
						makeInspectorControls()
					] ),
					// style
					(style != "") ? el( 'style', { className: 'greyd_styles' }, style ) : null,
					// original block
					el( BlockEdit, props )
				] );
			}
			
			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/button/edit',
		editBlockHook
	);

} )( window.wp );