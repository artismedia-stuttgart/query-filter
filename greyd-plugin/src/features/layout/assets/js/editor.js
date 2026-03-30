/**
 * Greyd.Blocks Editor Script for Layout Blocks.
 * - greyd/box block
 * - core/columns extensions
 * - core/column extensions
 *
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __, _x, sprintf } = wp.i18n;
	var _ = lodash;

	/**
	 * Initialise function when editor DOM is ready.
	 */
	wp.domReady(function () {

		// backgrounds
		greyd.backgrounds.init();
		// console.log("Block Background Scripts: initialised");

		// css animations
		greyd.cssAnims.init();
		// console.log("Block css animation Scripts: initialised");

		if ( ! greyd.data?.is_greyd_classic ) {
			/**
			 * Add bootstrap style to columns
			 * @since 2.5.0
			 */
			wp.blocks.registerBlockStyle( 'core/columns', {
				name: 'bootstrap',
				label: __( "Bootstrap", 'greyd_hub' )
			} );
		}
	});
	

	/**
	 * Register Content-Box block.
	 */
	wp.blocks.registerBlockType( 'greyd/box', {
		apiVersion: 2,
		title: __('Content Box', 'greyd_hub'),
		description: __("Box with any content", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('box'),
		category: 'greyd-blocks',

		supports: {
			anchor: true,
			align: true
		},

		variations: [
			{
				name: '',
				title: __('Content Box', 'greyd_hub'),
				description: __("Box that encloses and groups any content.", 'greyd_hub'),
				icon: el( "svg", {
					width: "24",
					height: "24",
					viewBox: "0 0 24 24",
					fill: "none",
					xmlns: "http://www.w3.org/2000/svg"
				}, [
					el( "path", {
						d: "M4.25 4.25H10V5.75H5.75V10H4.25V4.25ZM18.25 5.75H14V4.25H19.75V10H18.25V5.75ZM5.75 18.25V14H4.25V19.75H10V18.25H5.75ZM18.25 18.25V14H19.75V19.75H14V18.25H18.25Z",
						fillRule: "evenodd",
						clipRule: "evenodd"
					} )
				] ),
				scope: [ 'transform' ],
				isDefault: true,
				attributes: { variation: '' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			/**
			 * Variation: "Sticky Box"
			 * @since 1.7.2
			 */
			{
				name: 'sticky',
				title: __( 'Sticky Box', 'greyd_hub' ),
				description: __("Sticks to the top of the window when scrolling.", 'greyd_hub'),
				icon: el( "svg", {
					width: "24",
					height: "24",
					viewBox: "0 0 24 24",
					fill: "none",
					xmlns: "http://www.w3.org/2000/svg"
				}, [
					el( "path", {
						d: "m21.5 9.1-6.6-6.6-4.2 5.6c-1.2-.1-2.4.1-3.6.7-.1 0-.1.1-.2.1-.5.3-.9.6-1.2.9l3.7 3.7-5.7 5.7v1.1h1.1l5.7-5.7 3.7 3.7c.4-.4.7-.8.9-1.2.1-.1.1-.2.2-.3.6-1.1.8-2.4.6-3.6l5.6-4.1zm-7.3 3.5.1.9c.1.9 0 1.8-.4 2.6l-6-6c.8-.4 1.7-.5 2.6-.4l.9.1L15 4.9 19.1 9l-4.9 3.6z"
					} )
				] ),
				scope: [ 'inserter', 'transform' ],
				attributes: { variation: 'sticky' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			/**
			 * Variation: "Fixed Box"
			 * @since 1.7.2
			 */
			{
				name: 'fixed',
				title: __( 'Pinned Box', 'greyd_hub' ),
				description: __("Is always fixed in the visible area.", 'greyd_hub'),
				icon: el( "svg", {
					width: "24",
					height: "24",
					viewBox: "0 0 24 24",
					fill: "none",
					xmlns: "http://www.w3.org/2000/svg"
				}, [
					el( "path", {
						d: "M12.9384 6.50755C13.569 6.17838 14.2819 6.06207 14.9706 6.15862L17.3053 3L21 6.69462L17.8413 9.02925C17.9378 9.71795 17.8215 10.4308 17.4924 11.0614C17.4654 11.1131 17.437 11.1642 17.4071 11.2147C17.2655 11.4545 17.0917 11.6805 16.8857 11.8865L15.1628 10.1632L12.2506 13H11L11.0005 11.75L13.837 8.83722L12.1133 7.11415C12.3193 6.90817 12.5453 6.73438 12.785 6.59279C12.8356 6.56295 12.8867 6.53453 12.9384 6.50755ZM6 8H10V9.5H6C5.72386 9.5 5.5 9.72386 5.5 10V18C5.5 18.2761 5.72386 18.5 6 18.5H14C14.2761 18.5 14.5 18.2761 14.5 18V14H16V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z",
						fillRule: "evenodd",
						clipRule: "evenodd"
					} )
				] ),
				scope: [ 'transform' ],
				attributes: { variation: 'fixed' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			},
			/**
			 * Variation: "Absolute Box"
			 * @since 1.7.2
			 */
			{
				name: 'absolute',
				title: __( "Floating Box", 'greyd_hub' ),
				description: __("Positions itself independently from other elements in the layout.", 'greyd_hub'),
				icon: el( "svg", {
					width: "24",
					height: "24",
					viewBox: "0 0 24 24",
					fill: "none",
					xmlns: "http://www.w3.org/2000/svg"
				}, [
					el( "path", {
						d: "M14 10.5C13.7 10.5 13.5 10.3 13.5 10V6C13.5 5.7 13.7 5.5 14 5.5L18 5.5C18.3 5.5 18.5 5.7 18.5 6V10C18.5 10.3 18.3 10.5 18 10.5H14ZM14 12C12.9 12 12 11.1 12 10V6C12 4.9 12.9 4 14 4H18C19.1 4 20 4.9 20 6V10C20 11.1 19.1 12 18 12H14ZM6 8H10V9.5H6C5.72386 9.5 5.5 9.72386 5.5 10V18C5.5 18.2761 5.72386 18.5 6 18.5H14C14.2761 18.5 14.5 18.2761 14.5 18V14H16V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z",
						fillRule: "evenodd",
						clipRule: "evenodd"
					} )
				] ),
				scope: [ 'transform' ],
				attributes: { variation: 'absolute' },
				isActive: ( blockAttributes, variationAttributes ) => {
					return blockAttributes.variation === variationAttributes.variation;
				}
			}
		],

		attributes: {
			css_animation: { type: 'string' },
			inline_css: { type: 'string' },
			// inline_css_id: { type: 'string' },
			// trigger: { type: 'object' }, //
			// trigger_event: { type: 'object' },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object' },
			background: { type: 'object' },
			transition: { type: 'object', properties: {
				duration: { type: 'number' },
				effect: { type: 'string' },
			} },

			/**
			 * Variation: "Sticky Box"
			 * @since 1.7.2
			 */
			variation: { type: 'string', default: '' },
		},
		
		/**
		 * Variation: "Sticky Box"
		 * @since 1.7.2
		 */
		providesContext: {
			'greyd/is-position-variation': 'variation',
		},

		edit: function( props ) {
			// console.log(props);

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}
			var classNames = ["greyd-content-box", props.attributes.greydClass, props.attributes.className];

			// color
			if ( _.has(props.attributes, 'greydStyles.color') ) {
				classNames.push( 'has-text-color' );
			}

			// transition
			var style = "";
			if ( !props.attributes.greydAnim ) {
				var transition_duration = 0.3;
				if (_.has(props.attributes, 'transition.duration') && props.attributes.transition.duration != "") {
					transition_duration = props.attributes.transition.duration;
				}
				var transition_effect = 'ease';
				if (_.has(props.attributes, 'transition.effect') && props.attributes.transition.effect != "") {
					transition_effect = props.attributes.transition.effect;
				}
				var transition = "transition-property: box-shadow, border, border-radius, background, color; will-change: box-shadow, border, border-radius, background, color !important; ";
				transition += "transition-duration: "+transition_duration+"s !important; transition-timing-function: "+transition_effect+" !important; ";

				style = ".editor-styles-wrapper ."+props.attributes.greydClass+" { "+transition+" } ";
			}

			// align
			if ( props.attributes?.greydStyles?.align ) {
				/**
				 * Soft-deprecate greydstyles align attribute:
				 * keep the control but map value to core align property,
				 * that way core layout tools work better together
				 * @since 2.15.0
				 */
				// console.log( props.attributes.align, props.attributes.greydStyles.align );
				console.info( "deprecated align attribute" );
				props.setAttributes( { 
					align: props.attributes.greydStyles.align,
					greydStyles: { ...props.attributes.greydStyles, align: null } 
				} );
			}
			// if (_.has(props.attributes, 'greydStyles.align') && props.attributes.greydStyles.align != "") {
			// 	var align = "";
			// 	if (props.attributes.greydStyles.align == "left" || props.attributes.greydStyles.align == "center")
			// 		align += "margin-right: auto !important; ";
			// 	if (props.attributes.greydStyles.align == "right" || props.attributes.greydStyles.align == "center")
			// 		align += "margin-left: auto !important; ";
			// 	if (align != "") style += "."+props.attributes.greydClass+" { "+align+" } ";
			// }

			// justify content
			if (_.has(props.attributes, 'greydStyles.justifyContent') && props.attributes.greydStyles.justifyContent != "") {
				classNames.push( 'flex-justify-' + props.attributes.greydStyles.justifyContent );
			}

			// get background elements
			var backgroundElements = [];
			// basic
			var basic = greyd.tools.layout.makeBackground(_.clone(props));
			if (basic['style'] != "") style += basic['style'];
			if (basic['blocks'].length > 0)
				backgroundElements = backgroundElements.concat(basic['blocks']);

			// render background elements
			if (style != "") {
				style = el( 'style', { className: 'greyd_styles' }, style );
			}
			var background = "";
			if (backgroundElements.length > 0) {
				background = el( 'div', { id: "bg-"+props.clientId, className: 'greyd-background' }, backgroundElements );
			}
			const hasChildBlocks = greyd.tools.hasChildBlocks(props.clientId);

			if (backgroundElements.length > 0) classNames.push( 'has-background' );

			/**
			 * helper component to make position preview
			 * @since 1.7.2
			 */
			let [ hasPreview, setPreview ] = wp.element.useState( false );

			const blockProps = wp.blockEditor.useBlockProps({
				id: props.attributes.anchor,
				className: [
					...classNames,
					// fixed & absolute preview
					...(
						props.isSelected && hasPreview && ( props.attributes.variation === 'fixed' || props.attributes.variation === 'absolute' )
						? [ 'is-position-' + props.attributes.variation ]
						: []
					)
				].join(' ')
			});

			/**
			 * helper component to make toggleControl for position sticky
			 * @since 1.7.2
			 */
			const ToggleControlPosition = class extends wp.element.Component {
				constructor() {
					super();
				}
				render() {
					return el( wp.components.ToggleControl, {
						label: this.props.label,
						checked: this.props.value === "relative",
						onChange: value => this.props.onChange( value ? "relative" : "" ),
					} );
				}
			};

			return [

				// toolbar
				_.has(props.attributes.background, 'type') && props.attributes.background.type == 'image' ?
				el( greyd.components.ToolbarDynamicImageControl, {
					clientId: props.clientId,
					useIdAsTag: true,
					icon: 'image',
					value: _.has(props.attributes.background, 'image') ? props.attributes.background.image : null,
					onChange: (value) => {
						props.setAttributes({
							background: {
								...props.attributes.background,
								image: {
									...props.attributes.background.image,
									...value
								}
							}
						});
					}
				} ) : null,

				// sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [

					/**
					 * Sticky Settings
					 * @since 1.7.2
					 */
					props.attributes.variation === 'sticky' && el( greyd.components.StylingControlPanel, {
						title: __( "Sticky settings", 'greyd_hub' ),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __( "Offset", 'greyd_hub' ),
								attribute: '--sticky-offset',
								control: greyd.components.RangeUnitControl,
								min: -200,
								max: 200,
								step: 1,
								unit: [ 'px' ],
								defaultUnit: 'px',
							},
							{
								hidden: { 
									'': ! (
										_.has(props.attributes, 'greydStyles.responsive.lg["--sticky-offset"]')
										|| _.has(props.attributes, 'greydStyles.responsive.md["--sticky-offset"]')
										|| _.has(props.attributes, 'greydStyles.responsive.sm["--sticky-offset"]')
									)
								},
								label: __("Not sticky on that breakpoint", 'greyd_hub'),
								attribute: "--sticky-position",
								control: ToggleControlPosition,
							},
							{
								control: wp.components.Button,
								icon: hasPreview ? 'hidden' : 'visibility',
								onClick: () => setPreview( ! hasPreview ),
								isSecondary: true,
								// isSmall: true,
								style: { marginBottom: '1em' },
								children: hasPreview ? __( "Hide preview", 'greyd_hub' ) : __( "Show preview", 'greyd_hub' )
							},
							{
								control: wp.components.Tip,
								children: __("A sticky element remains at the top of the page when scrolling - but only within the parent container.", 'greyd_hub'),
							},
						]
					} ),

					/**
					 * Fixed & absolute Settings
					 * @since 1.7.2
					 */
					( props.attributes.variation === 'fixed' || props.attributes.variation === 'absolute' ) && el( greyd.components.StylingControlPanel, {
						title: __( 'Position', 'greyd_hub' ),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								control: wp.components.Button,
								icon: hasPreview ? 'hidden' : 'visibility',
								onClick: () => setPreview( ! hasPreview ),
								isSecondary: true,
								// isSmall: true,
								children: hasPreview ? __( "Hide preview", 'greyd_hub' ) : __( "Show preview", 'greyd_hub' ),
								style: { marginBottom: '1em' },
							},
							{
								attribute: '--offset-top',
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								label: __( "Top", 'greyd_hub' )
							},
							{
								attribute: '--offset-right',
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								label: __( "Right", 'greyd_hub' )
							},
							{
								attribute: '--offset-bottom',
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								label: __( "Bottom", 'greyd_hub' )
							},
							{
								attribute: '--offset-left',
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								label: __( "Left", 'greyd_hub' )
							},

							// Accessibility notice for fixed variation - placed at the top
							props.attributes.variation === 'fixed' ? {
								control: wp.components.Notice,
								isDismissible: false,
								status: 'warning',
								children: __( 'If your site must comply with accessibility laws, note that this element obstructs content below when the page is zoomed in. Be mindful of how and where you use it.', 'greyd_hub' ),
							} : null
						]
					} ),
					
					// background
					greyd.components.layout.backgroundInspectorControls( props, {
						overlay: false, pattern: false, seperator: false
					} ),
					//
					// transition
					el( wp.components.PanelBody, { title: __( "Behavior", 'greyd_hub' ), initialOpen: false }, [
						// transition
						el( props.attributes.greydAnim ? wp.components.Disabled : 'div', {
							style: { opacity: props.attributes.greydAnim ? 0.5 : 1 }
						}, [
							el( wp.components.RangeControl, {
								label: __("Transition duration in s", 'greyd_hub'),
								value: _.has(props.attributes, 'transition.duration') ? props.attributes.transition.duration : 0.3,
								step: 0.01, min: 0, max: 2,
								onChange: function(value) { props.setAttributes( { transition: { ...props.attributes.transition, duration: value } } ); },
							} ),
							el( wp.components.SelectControl, {
								label: __("Transition animation", 'greyd_hub'),
								value: _.has(props.attributes, 'transition.effect') ? props.attributes.transition.effect : '',
								options: [
									{ label: __("Ease", 'greyd_hub'), value: '' },
									{ label: __("Linear", 'greyd_hub'), value: 'linear' },
									{ label: __("Ease in", 'greyd_hub'), value: 'ease-in' },
									{ label: __("Ease out", 'greyd_hub'), value: 'ease-out' },
									{ label: __("Ease in out", 'greyd_hub'), value: 'ease-in-out' },
								],
								onChange: function(value) { props.setAttributes( { transition: { ...props.attributes.transition, effect: value } } ); },
							} )
						] ),
						props.attributes.greydAnim && el( wp.components.Tip, {}, [
							__( "If an Animation is set, the transition values are not used. Instead the transition setup of the Animation is used for the hover styles.", 'greyd_hub' )
					 	] )
					] )
				] ),

				// sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// colors + hover
					el( greyd.components.StylingControlPanel, {
						title: __("Colors", 'greyd_hub'),
						initialOpen: true,
						supportsHover: true,
						holdsColors: [
							{
								color: (_.has(props.attributes, 'greydStyles.color')) ? props.attributes.greydStyles.color : '',
								title: __("Text color", 'greyd_hub')
							},
							{
								color: (_.has(props.attributes, 'greydStyles.background')) ? props.attributes.greydStyles.background : '',
								title: __("Background", 'greyd_hub')
							}
						],
						blockProps: props,
						controls: [
							{
								label: __("Text color", 'greyd_hub'),
								attribute: "color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							}, {
								label: __("Background", 'greyd_hub'),
								attribute: "background",
								control: greyd.components.ColorGradientPopupControl,
								contrast: {
									default: (_.has(props.attributes, 'greydStyles.color')) ? props.attributes.greydStyles.color : '', // '#000'
									hover: (_.has(props.attributes, 'greydStyles.hover.color')) ? props.attributes.greydStyles.hover.color : '', // '#000'
								}
							}
						]
					} ),
					// greydStyles + responsive
					el( greyd.components.StylingControlPanel, {
						title: __("Spaces", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __("Inside", 'greyd_hub'),
							attribute: "padding",
							control: greyd.components.DimensionControl,
						}, {
							label: __("Outside", 'greyd_hub'),
							attribute: "margin",
							control: greyd.components.DimensionControl,
							min: -300,
							max: 300,
						} ]
					} ),
					el( greyd.components.StylingControlPanel, {
						title: __("Size", 'greyd_hub'),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __("Minimum height", 'greyd_hub'),
								attribute: "minHeight",
								control: greyd.components.RangeUnitControl,
								max: 1000
							},
							{
								label: __("Maximum width", 'greyd_hub'),
								attribute: "maxWidth",
								control: greyd.components.RangeUnitControl,
								max: 2000
							},
							{
								label: __("Alignment", 'greyd_hub'),
								attribute: "align",
								control: greyd.components.ButtonGroupControl,
								hidden: { '': !_.has(props.attributes, 'greydStyles.maxWidth') || _.isEmpty(props.attributes.greydStyles.maxWidth), 'lg': true, 'md': true, 'sm': true },
								options: [
									{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignLeft'), value: 'left' },
									{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignCenter'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('alignRight'), value: 'right' },
								],
							},
							{
								label: __("Alignment of the content", 'greyd_hub'),
								attribute: "justifyContent",
								control: greyd.components.ButtonGroupControl,
								hidden: { 'lg': true, 'md': true, 'sm': true },
								options: [
									{ label: __("Top", 'greyd_hub'), icon: greyd.tools.getCoreIcon('justifyTop'), value: 'flex-start' },
									{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('justifyCenter'), value: 'center' },
									{ label: __("Bottom", 'greyd_hub'), icon: greyd.tools.getCoreIcon('justifyBottom'), value: 'flex-end' },
									{ label: __("Spreaded", 'greyd_hub'), icon: greyd.tools.getCoreIcon('justifySpaceBetween'), value: 'space-between' },
								],
							}
						]
					} ),
					// border-radius
					el( greyd.components.StylingControlPanel, {
						title: __("Border radius", 'greyd_hub'),
						initialOpen: false,
						blockProps: props,
						controls: [ {
							label: __("Border radius", 'greyd_hub'),
							attribute: "borderRadius",
							control: greyd.components.DimensionControl,
							labels: {
								"all": __("All corners", "greyd_hub"),
							},
							sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
							type: "string"
						} ]
					} ),
					// border + hover
					el( greyd.components.StylingControlPanel, {
						title: __("Border", 'greyd_hub'),
						blockProps: props,
						supportsHover: true,
						controls: [
							{
								label: __("Border", 'greyd_hub'),
								attribute: "border",
								control: greyd.components.BorderControl,
							}
						]
					} ),
					// shadow + hover
					el( greyd.components.StylingControlPanel, {
						title: __("Shadow", 'greyd_hub'),
						blockProps: props,
						supportsHover: true,
						controls: [
							{
								label: __("Drop shadow", 'greyd_hub'),
								attribute: "boxShadow",
								control: greyd.components.DropShadowControl,
							}
						]
					} ),
				] ),

				// preview
				style,
				el( 'div', { ...blockProps }, [
					background,
					el( 'div', { className: props.className }, [
						el( wp.blockEditor.InnerBlocks, {
							renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender
						} )
					] )
				] ),
				el( greyd.components.RenderPreviewStyles, {
					selector: props.attributes.greydClass,
					styles: { "": props.attributes.greydStyles }
				} ),

				// sticky preview
				props.isSelected && hasPreview && props.attributes.variation === 'sticky' && el( greyd.components.RenderPreviewStyles, {
					selector: 'wp-block#block-' + props.clientId,
					styles: { "": {
						position: 'sticky',
						top: '0',
						marginTop: ( props.attributes.greydStyles['--sticky-offset'] ?? '0' ),
						'z-index': '20'
					} }
				} )

			];
		},
		save: function( props ) {
			const blockProps = wp.blockEditor.useBlockProps.save();
			// save only the inner content-box-content
			// wrapper and background are rendered based on attributes
			return el( 'div', { ...blockProps }, el( wp.blockEditor.InnerBlocks.Content ) );
		},

		deprecated: [
			{
				apiVersion: 2,
				supports: {
					anchor: true,
					align: true
				},
				attributes: {
					css_animation: { type: 'string' },
					inline_css: { type: 'string' },
					// inline_css_id: { type: 'string' },
					// trigger: { type: 'object' }, //
					// trigger_event: { type: 'object' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					background: { type: 'object' },
					transition: { type: 'object', properties: {
						duration: { type: 'number' },
						effect: { type: 'string' },
					} },
					variation: { type: 'string', default: '' },
				},
				save: function( props ) {
					// save only the inner content-box-content
					// wrapper and background are rendered based on attributes
					return el( 'div', {}, el( wp.blockEditor.InnerBlocks.Content ) );
				}
			},
			/**
			 * Added apiVersion 2.
			 * @since 2.7.0
			 */
			{
				supports: {
					anchor: true,
					align: true
				},
				attributes: {
					css_animation: { type: 'string' },
					inline_css: { type: 'string' },
					// inline_css_id: { type: 'string' },
					// trigger: { type: 'object' }, //
					// trigger_event: { type: 'object' },
					greydClass: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
					background: { type: 'object' },
					transition: { type: 'object', properties: {
						duration: { type: 'number' },
						effect: { type: 'string' },
					} },
		
					/**
					 * Variation: "Sticky Box"
					 * @since 1.7.2
					 */
					variation: { type: 'string', default: '' },
				},
				save: function( props ) {
					// save only the inner content-box-content
					// wrapper and background are rendered based on attributes
					return el( 'div', {}, el( wp.blockEditor.InnerBlocks.Content ) );
				},
			},
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					css_animation: { type: 'string' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					link: { type: 'object', properties: {
						url: { type: 'string' },
						title: { type: 'string' },
						opensInNewTab: { type: 'boolean' },
					}, default: { url: "", title: "", opensInNewTab: false } },
					// spacing
					height: { type: 'string', default: "" },
					padding: { type: 'object', properties: {
						top: { type: 'string' },
						right: { type: 'string' },
						bottom: { type: 'string' },
						left: { type: 'string' },
					}, default: { top: "0px", right: "0px", bottom: "0px", left: "0px" } },
					margin: { type: 'object', properties: {
						top: { type: 'string' },
						right: { type: 'string' },
						bottom: { type: 'string' },
						left: { type: 'string' },
					}, default: { top: "0px", right: "0px", bottom: "0px", left: "0px" } },
					textColor: { type: 'string', default: "" },
					border_radius: { type: 'integer', default: 0 },
					// background
					background: { type: 'object', properties: {
						type: { type: 'string' },
						opacity: { type: 'integer' },
						color: { type: 'string' },
						gradient: { type: 'string' },
						image: { type: 'object', properties: {
							url: { type: 'string' },
							id: { type: 'integer' },
							size: { type: 'string' },
							repeat: { type: 'string' },
							position: { type: 'string' },
						} },
						anim: { type: 'object', properties: {
							id: { type: 'integer' },
							url: { type: 'string' },
							anim: { type: 'string' },
							width: { type: 'string' },
							position: { type: 'string' },
						} },
						video: { type: 'object', properties: {
							url: { type: 'string' },
							aspect: { type: 'string' },
						} },
						scroll: { type: 'object', properties: {
							type: { type: 'string' },
							parallax: { type: 'integer' },
							parallax_mobile: { type: 'boolean' },
						} },
						border: { type: 'object', properties: {
							enable: { type: 'boolean' },
							style: { type: 'string' },
							width: { type: 'object', properties: {
								top: { type: 'string' },
								right: { type: 'string' },
								bottom: { type: 'string' },
								left: { type: 'string' },
							} },
							color: { type: 'string' },
						} },
						shadow: { type: 'object', properties: {
							enable: { type: 'boolean' },
							shadow: { type: 'string' },
						} },
					}, default: {
						type: "",
						opacity: 100,
						color: "",
						gradient: "",
						image: {
							url: "",
							id: -1,
							size: "contain",
							repeat: "repeat",
							position: "top center",
						},
						anim: {
							id: -1,
							url: "",
							anim: "auto",
							width: "auto",
							position: 'top_center',
						},
						video: {
							url: '',
							aspect: '16/9',
						},
						scroll: {
							type: "scroll",
							parallax: 30,
							parallax_mobile: false,
						},
						border: {
							enable: false,
							style: "solid",
							width: { top: "1px", right: "1px", bottom: "1px", left: "1px" },
							color: "",
						},
						shadow: {
							enable: false,
							shadow: "none",
						},
					} },
					// hover
					hover: { type: 'object', properties: {
						duration: { type: 'number' },
						effect: { type: 'string' },
						textColor: { type: 'string' },
						border_radius: { type: 'integer' },
						background: { type: 'object', properties: {
							opacity: { type: 'integer' },
							color: { type: 'string' },
							border: { type: 'object', properties: {
								style: { type: 'string' },
								width: { type: 'object', properties: {
									top: { type: 'string' },
									right: { type: 'string' },
									bottom: { type: 'string' },
									left: { type: 'string' },
								} },
								color: { type: 'string' },
							} },
							shadow: { type: 'string' },
						} },
					}, default: {
						duration: 0.3,
						effect: "",
						textColor: "",
						border_radius: 0,
						background: {
							opacity: 100,
							color: "",
							border: {
								style: "solid",
								width: { top: "1px", right: "1px", bottom: "1px", left: "1px" },
								color: "",
							},
							shadow: "none",
						},
					} },
				},

				migrate: function (attributes) {
					// console.log(attributes);

					// make new attributes
					var new_atts = {};
					if (!_.isEmpty(attributes.dynamic_parent)) new_atts.dynamic_parent = attributes.dynamic_parent;
					if (!_.isEmpty(attributes.dynamic_value)) new_atts.dynamic_value = attributes.dynamic_value;
					if (!_.isEmpty(attributes.css_animation)) new_atts.css_animation = attributes.css_animation;
					if (!_.isEmpty(attributes.inline_css)) new_atts.inline_css = attributes.inline_css;
					if (!_.isEmpty(attributes.inline_css_id)) new_atts.inline_css_id = attributes.inline_css_id;

					// trigger
					var new_trigger = {};
					if (attributes.link.url != "") {
						new_trigger.type = "link";
						new_trigger.params = {
							url: attributes.link.url,
							opensInNewTab: attributes.link.opensInNewTab
						};
					}
					if (!_.isEmpty(new_trigger)) {
						new_atts.trigger = new_trigger;
					}

					// greydStyles
					var new_greydStyles = {};
					if (!_.isEqual(attributes.padding, { top: "0px", right: "0px", bottom: "0px", left: "0px" } )) {
						new_greydStyles.padding = attributes.padding;
					}
					if (!_.isEqual(attributes.margin, { top: "0px", right: "0px", bottom: "0px", left: "0px" } )) {
						new_greydStyles.margin = attributes.margin;
					}
					if (attributes.height != "") {
						new_greydStyles.minHeight = attributes.height;
					}
					if (attributes.textColor != "") {
						new_greydStyles.color = attributes.textColor;
					}
					if (attributes.background.type != "") {
						if (attributes.background.type == "gradient" ) {
							new_greydStyles.background = attributes.background.gradient;
						}
						else {
							new_greydStyles.background = attributes.background.color;
						}
					}
					if (attributes.border_radius != 0) {
						new_greydStyles.borderRadius = attributes.border_radius+'px';
					}
					if (attributes.background.border.enable) {
						var color = attributes.background.border.color;
						if (color.indexOf("color-") === 0 ) {
							color = "var(--"+value.replace("-", "")+")";
						}
						var style = attributes.background.border.style;
						new_greydStyles.border = {
							top: attributes.background.border.width.top+' '+style+' '+color,
							right: attributes.background.border.width.right+' '+style+' '+color,
							bottom: attributes.background.border.width.bottom+' '+style+' '+color,
							left: attributes.background.border.width.left+' '+style+' '+color,
						};
					}
					if (attributes.background.shadow.enable) {
						new_greydStyles.boxShadow = attributes.background.shadow.shadow;
					}

					// greydStyles hover
					var new_greydStyles_hover = {};
					if (attributes.hover.textColor != "" && attributes.hover.textColor != attributes.textColor) {
						new_greydStyles_hover.color = attributes.hover.textColor;
					}
					if (attributes.background.type != "") {
						if (attributes.background.type != "gradient" && attributes.hover.background.color != attributes.background.color) {
							new_greydStyles_hover.background = attributes.hover.background.color;
						}
					}
					if (attributes.background.border.enable) {
						var color = attributes.hover.background.border.color;
						if (color.indexOf("color-") === 0 ) {
							color = "var(--"+value.replace("-", "")+")";
						}
						var style = attributes.hover.background.border.style;
						var new_border_hover = {
							top: attributes.hover.background.border.width.top+' '+style+' '+color,
							right: attributes.hover.background.border.width.right+' '+style+' '+color,
							bottom: attributes.hover.background.border.width.bottom+' '+style+' '+color,
							left: attributes.hover.background.border.width.left+' '+style+' '+color,
						};
						if (!_.isEqual(new_border_hover, new_greydStyles.border)) {
							new_greydStyles_hover.border = new_border_hover;
						}
					}
					if (attributes.background.shadow.enable && attributes.hover.background.shadow != attributes.background.shadow.shadow) {
						new_greydStyles_hover.boxShadow = attributes.hover.background.shadow;
					}
					if (!_.isEmpty(new_greydStyles_hover)) new_greydStyles.hover = new_greydStyles_hover;
					// greydClass
					if (!_.isEmpty(new_greydStyles)) {
						new_atts.greydStyles = new_greydStyles;
						new_atts.greydClass = greyd.tools.generateGreydClass();
					}

					// background
					var new_background = {};
					if (attributes.background.type != "") {
						if (attributes.background.type == "image" ) {
							new_background.image = attributes.background.image;
						}
						if (attributes.background.type == "anim" ) {
							new_background.anim = attributes.background.anim;
						}
						if (attributes.background.type == "video" ) {
							new_background.video = attributes.background.video;
						}
						if (!_.isEmpty(new_background)) {
							new_background.type = attributes.background.type;
							new_background.opacity = attributes.background.opacity;
							new_background.scroll = attributes.background.scroll;
							new_atts.background = new_background;
						}
					}

					// transition
					var new_transition = {};
					if (attributes.hover.duration != 0.3) {
						new_transition.duration = attributes.hover.duration;
					}
					if (attributes.hover.effect != "") {
						new_transition.effect = attributes.hover.effect;
					}
					if (!_.isEmpty(new_transition)) {
						new_atts.transition = new_transition;
					}

					console.info("Block `greyd/box` successfully updated", new_atts);
					return new_atts;
				},

				isEligible: function (attributes, inner) {
					// console.log(attributes);
					// console.log(inner);
					if (_.has(attributes, 'textColor')) {
						// convert attributes and saved element
						// console.info("Old `greyd/box` Block detected");
						return true;
					}
				},

				save: function(props) {
					// console.log(props);
					return el( 'div', {
						id: props.attributes.anchor,
						className: [props.className, "vc-content-box-content"].join(' ')
					}, el( wp.blockEditor.InnerBlocks.Content ) );
				}
			},
		],

		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'core/group' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert group to box');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						var greydStyles = {};
						if (_.has(attributes, 'textColor')) {
							greydStyles.color = attributes.textColor;
						}
						if (_.has(attributes, 'backgroundColor')) {
							greydStyles.background = attributes.backgroundColor;
						}
						if (_.has(attributes, 'gradient')) {
							greydStyles.background = attributes.gradient;
						}
						if (!_.isEmpty(greydStyles)) newatts.greydStyles = greydStyles;

						return wp.blocks.createBlock(
							'greyd/box',
							newatts,
							innerBlocks
						);
					},
				},
				{
					type: 'block',
					blocks: [ 'core/columns' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert columns to box');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						var greydStyles = {};
						if (_.has(attributes, 'textColor')) {
							greydStyles.color = attributes.textColor;
						}
						if (_.has(attributes, 'backgroundColor')) {
							greydStyles.background = attributes.backgroundColor;
						}
						if (_.has(attributes, 'gradient')) {
							greydStyles.background = attributes.gradient;
						}
						if (!_.isEmpty(greydStyles)) newatts.greydStyles = greydStyles;

						if (_.has(attributes, 'background')) {
							newatts.background = attributes.background;
						}

						var inner = [];
						if (innerBlocks.length > 0) {
							innerBlocks.forEach(function(val, i) {
								inner = [...inner, ...val.innerBlocks];
							});
						}

						return wp.blocks.createBlock(
							'greyd/box',
							newatts,
							inner
						);
					},
				},
				{
					type: 'block',
					blocks: [ 'core/image' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert image to box');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						var image = {};
						if (_.has(attributes, 'id')) image.id = attributes.id;
						if (_.has(attributes, 'url')) image.url = attributes.url;
						if (!_.isEmpty(image)) {
							image.position = 'center center';
							image.size = 'cover';
							newatts.background = {
								type: 'image',
								opacity: 50,
								image: image
							};
						}

						var inner = [];
						if (_.has(attributes, 'caption') && attributes.caption != "") {
							var inneratts = {
								content: attributes.caption
							};
							// console.log(inneratts);
							inner.push(wp.blocks.createBlock(
								'core/heading',
								inneratts
							));
						}

						return wp.blocks.createBlock(
							'greyd/box',
							newatts,
							inner
						);
					},
				},
				{
					type: 'block',
					blocks: [ 'greyd/anim' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert anim to box');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						var anim = {};
						if (_.has(attributes, 'id')) anim.id = attributes.id;
						if (_.has(attributes, 'url')) anim.url = attributes.url;
						if (!_.isEmpty(anim)) {
							anim.position = 'center_center';
							newatts.background = {
								type: 'animation',
								// opacity: 50,
								anim: anim
							};
						}

						return wp.blocks.createBlock(
							'greyd/box',
							newatts,
							[]
						);
					},
				},
				{
					type: 'block',
					blocks: [ '*' ],
					isMultiBlock: true,
					__experimentalConvert: function(innerBlocks) {
						if (innerBlocks.length === 1 &&
							innerBlocks[0].name === 'greyd/box') {
							console.log("don't nest single contentbox");
							return innerBlocks[0];
						}
						console.log('__experimentalConvert * to box');
						// console.log(innerBlocks);

						var inner = innerBlocks.map( ( block ) => {
							return wp.blocks.createBlock(
								block.name,
								block.attributes,
								block.innerBlocks
							);
						} );

						return wp.blocks.createBlock(
							'greyd/box',
							{},
							inner
						);
					},
				}
			],
			to: [
				{
					type: 'block',
					blocks: [ 'core/group' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert box to group');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						if (_.has(attributes, 'greydStyles')) {
							if (_.has(attributes.greydStyles, 'color')) {
								newatts.textColor = greyd.tools.getColorSlug(attributes.greydStyles.color);
							}
							if ( _.has(attributes.greydStyles, 'background') && attributes.greydStyles.background) {
								if (attributes.greydStyles.background.indexOf("color-") === 0) newatts.backgroundColor = attributes.greydStyles.background;
								else newatts.gradient = attributes.greydStyles.background;
							}
						}

						return wp.blocks.createBlock(
							'core/group',
							newatts,
							innerBlocks
						);
					},
				},
				{
					type: 'block',
					blocks: [ 'core/columns' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert box to columns');
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						if (_.has(attributes, 'greydStyles')) {
							if (_.has(attributes.greydStyles, 'color')) {
								newatts.textColor = attributes.greydStyles.color;
							}
							if ( _.has(attributes.greydStyles, 'background') && attributes.greydStyles.background ) {
								if (attributes.greydStyles.background.indexOf("color-") === 0) newatts.backgroundColor = attributes.greydStyles.background;
								else newatts.gradient = attributes.greydStyles.background;
							}
						}
						if (_.has(attributes, 'background')) {
							newatts.background = attributes.background;
						}

						return wp.blocks.createBlock(
							'core/columns',
							newatts,
							[ wp.blocks.createBlock(
								'core/column',
								{},
								innerBlocks
							) ]
						);
					},
				}
			]
		}
	} );


	/**
	 * Register custom attributes to core blocks.
	 * - core/columns
	 * - core/column
	 * - css_animation
	 * - hide
	 * - disable
	 * - inline css
	 *
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		// if (_.has(settings, 'apiVersion') && settings.apiVersion > 1) {
		if ( settings.apiVersion && settings.supports && settings.attributes ) {
			
			// console.log(name);
			// console.log(settings);
			// console.log( name, settings.apiVersion, settings.supports.className, settings.supports.customClassName );

			/**
			 * custom supports
			 */

			// add support for css animations
			if (
				name == 'core/group' || 
				name == 'core/columns' || 
				name == 'core/column' || 
				name == 'core/paragraph' || 
				name == 'core/embed' || 
				name == 'core/heading' ||
				name == 'core/button' ||
				name == 'core/image' ||
				name == 'core/gallery' ||
				name == 'core/video' ||
				name == 'core/separator' ||
				name == 'core/query'
			) {
				settings.attributes.css_animation = { type: 'string' };
			}

			// add support for hiding per breakpoint
			/**
			 * TBD: include/exclude list or other condition
			 * customClassName checks if className is used.
			 * (not working for a few blocks - e.g. custom-html is false, but class can still be set)
			 */
			// if (
			// 	name == 'core/group' || 
			// 	name == 'core/spacer' ||
			// 	name == 'core/paragraph' || 
			// 	name == 'core/embed' || 
			// 	name == 'core/heading' ||
			// 	name == 'core/html' ||
			// 	name == 'core/button' ||
			// 	name == 'core/image' ||
			// 	name == 'core/gallery' ||
			// 	name == 'core/navigation' ||
			// 	name == 'core/video' ||
			// 	name == 'core/separator' ||
			// 	name == 'core/query' ||
			// 	name == 'greyd/box' || 
			// 	name == 'greyd/image' 
			// ) {
			if (
				name !== 'core/column' &&
				( typeof settings.supports.customClassName === 'undefined' ||	// className enabled by default
				  settings.supports.customClassName === true )					// sometimes set to 'true'
				&& name !== 'greyd-forms/hidden-field'
			) {
				settings.attributes.hide = { type: 'object' };
			}

			// add support for disabling (not rendering)
			if (
				name == 'core/group' || 
				name == 'core/columns'
			) {
				settings.attributes.disable_element = { type: 'boolean' };
			}

			// add support for inline css
			if (
				name == 'core/group' || 
				name == 'core/columns' || 
				name == 'core/column' || 
				name == 'core/spacer' ||
				name == 'core/paragraph' || 
				name == 'core/embed' || 
				name == 'core/heading' ||
				name == 'core/html' ||
				name == 'core/button' ||
				name == 'core/image' ||
				name == 'core/gallery' ||
				name == 'core/navigation' ||
				name == 'core/video' ||
				name == 'core/separator' ||
				name == 'core/query'
			) {
				settings.attributes.inline_css = { type: 'string' };
			}

			// add anchor support
			if (
				name == 'core/embed' || 
				name == 'core/html' ||
				name == 'core/query' ||
				name == 'core/search' ||
				name == 'core/latest-comments' ||
				name == 'core/calendar' ||
				name == 'core/page-list' ||
				name == 'core/tag-cloud' ||
				name == 'core/latest-posts' ||
				name == "core/categories" ||
				name == "core/archives" ||
				name == "core/rss"
			) {
				settings.supports.anchor = true;
				settings.attributes.anchor = { type: 'string' };
			}

			/**
			 * core block extensions
			 */

			if (name == 'core/columns') {
				settings.attributes.wrapper_class = { type: 'string', default: 'row_wrap' };

				if ( greyd.data?.is_greyd_classic ) {
					// row
					settings.attributes.row = { type: 'object', properties: {
						type: { type: 'string' },
					}, default: { type: 'row_xxl' } };
					settings.supports.spacing.padding = false;
				}

				// background
				settings.attributes.background = { type: 'object' };
				// set transform.from from '*' to array of all registered blocks without exclude
				var transforms = [];
				var exclude = [ name, 'greyd/box' ];
				greyd.data.all_block_types.forEach(function(val, i) {
					if (exclude.indexOf(val) == -1) transforms.push(val);
				});
				settings.transforms.from[0].blocks = transforms;
				// console.log(settings);
			}

			if (name == 'core/column') {
				// responsive
				settings.attributes.responsive = { type: 'object' };
				// console.log(settings);
			}

		}
		return settings;

	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/layout',
		registerBlockTypeHook
	);


	/**
	 * Add custom edit controls to core blocks.
	 * - greydClass
	 * - css_animation
	 * - hide
	 * - disable
	 * - inline css
	 *
	 * @hook editor.BlockEdit
	 */
	var extendEditBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {

			var block_type = wp.blocks.getBlockType(props.name);

			/**
			 * regenerate/reset greydClass
			 */
			var greydClassControl = false;
			if (_.has(block_type.attributes, "greydClass")) {

				greydClassControl = el( wp.components.BaseControl, {
					label: __("Styles class", 'greyd_hub'),
					help: __("If styles between Blocks and dynamic Templates seem to be synched, it may help to generate a new styles class.", 'greyd_hub'),
				}, [
					el( wp.components.TextControl, {
						value: props.attributes.greydClass,
						disabled: 'disabled',
					} ),
					el( wp.components.Button, {
						variant: 'secondary',
						text: __("Generate new class", 'greyd_hub'),
						style: {
							position: 'absolute',
							right: '16px',
							marginTop: '-50px',
							background: 'white'
						},
						onClick: function() { 
							var greydClass = greyd.tools.generateGreydClass();
							// console.log("make new greydClass - "+props.attributes.greydClass+" -> "+greydClass);
							const classKey = _.has(props, 'clientId') ? props.clientId +'.greydClass' : null;
							if ( classKey && _.has(greyd.tools.allGreydClasses, classKey) ) {
								greyd.tools.allGreydClasses[classKey] = greydClass;
							}
							props.setAttributes( { greydClass: greydClass } ); 
						}
					} )
				] );
			}

			/**
			 * CSS animation support.
			 */
			var animationControl = false;
			if (_.has(block_type.attributes, "css_animation")) {
				
				if ( props.name != "core/columns" && props.name != "core/column" ) {
					// console.log("add css_animation support to: "+props.name);

					// make css animation controls
					animationControl = el( greyd.components.OptionsControl, {
						style: { width: '100%' },
						label: __("CSS animation", 'greyd_hub'),
						value: props.attributes.css_animation,
						options: [
							{ label: __("None", 'greyd_hub'), value: "none" },
							{ label: __("From top to bottom", 'greyd_hub'), value: "top-to-bottom" },
							{ label: __("From bottom to top", 'greyd_hub'), value: "bottom-to-top" },
							{ label: __("From left to right", 'greyd_hub'), value: "left-to-right" },
							{ label: __("From right to left", 'greyd_hub'), value: "right-to-left" },
							{ label: __("Zoom in", 'greyd_hub'), value: "appear" },
							{ label: __("Bounce animations", 'greyd_hub'), options: [
								{ label: __("Bounce in", 'greyd_hub'), value: "bounceIn" },
								{ label: __("Bounce in, down", 'greyd_hub'), value: "bounceInDown" },
								{ label: __("Bounce in, left", 'greyd_hub'), value: "bounceInLeft" },
								{ label: __("Bounce in, right", 'greyd_hub'), value: "bounceInRight" },
								{ label: __("Bounce in, up", 'greyd_hub'), value: "bounceInUp" }
							] },
							{ label: __("Fade animations", 'greyd_hub'), options: [
								{ label: __("Fade in", 'greyd_hub'), value: "fadeIn" },
								{ label: __("Fade in, down", 'greyd_hub'), value: "fadeInDown" },
								{ label: __("Fade in, down big", 'greyd_hub'), value: "fadeInDownBig" },
								{ label: __("Fade in, left", 'greyd_hub'), value: "fadeInLeft" },
								{ label: __("Fade in, left big", 'greyd_hub'), value: "fadeInLeftBig" },
								{ label: __("Fade in, right", 'greyd_hub'), value: "fadeInRight" },
								{ label: __("Fade in, right big", 'greyd_hub'), value: "fadeInRightBig" },
								{ label: __("Fade in, up", 'greyd_hub'), value: "fadeInUp" },
								{ label: __("Fade in, up big", 'greyd_hub'), value: "fadeInUpBig" }
							] },
							{ label: __("Spin animations", 'greyd_hub'), options: [
								{ label: __("Rotate in", 'greyd_hub'), value: "rotateIn" },
								{ label: __("Rotate in, down left", 'greyd_hub'), value: "rotateInDownLeft" },
								{ label: __("Rotate in, down right", 'greyd_hub'), value: "rotateInDownRight" },
								{ label: __("Rotate in, up left", 'greyd_hub'), value: "rotateInUpLeft" },
								{ label: __("Rotate in, up right", 'greyd_hub'), value: "rotateInUpRight" }
							] },
							{ label: __("Zoom animations", 'greyd_hub'), options: [
								{ label: __("Zoom in", 'greyd_hub'), value: "zoomIn" },
								{ label: __("Zoom in, down", 'greyd_hub'), value: "zoomInDown" },
								{ label: __("Zoom in, left", 'greyd_hub'), value: "zoomInLeft" },
								{ label: __("Zoom in, right", 'greyd_hub'), value: "zoomInRight" },
								{ label: __("Zoom in, up", 'greyd_hub'), value: "zoomInUp" }
							] },
							{ label: __("Slide animations", 'greyd_hub'), options: [
								{ label: __("Slide in, down", 'greyd_hub'), value: "slideInDown" },
								{ label: __("Slide in, left", 'greyd_hub'), value: "slideInLeft" },
								{ label: __("Slide in, right", 'greyd_hub'), value: "slideInRight" },
								{ label: __("Slide in, up", 'greyd_hub'), value: "slideInUp" }
							] },
							{ label: __("Special animations", 'greyd_hub'), options: [
								{ label: __("Roll in", 'greyd_hub'), value: "rollIn" },
								{ label: __("Flip in, horizontally", 'greyd_hub'), value: "flipInX" },
								{ label: __("Flip in, vertically", 'greyd_hub'), value: "flipInY" },
								{ label: __("Light speed in", 'greyd_hub'), value: "lightSpeedIn" }
							] }
						],
						onChange: function(value) { 
							if (value == "none") value = "";
							props.setAttributes( { css_animation: value } );
							setTimeout(function() {
								greyd.cssAnims.init();
							}, 0);
						},
						// replay button
						help: el( wp.components.Button, {
							variant: 'ghost',
							isSmall: true,
							className: 'has-text',
							text: __("Play animation", 'greyd_hub'),
							icon: 'controls-play',
							iconPosition: 'left',
							// trigger replay
							onClick: function() { 
								var value = props.attributes.css_animation;
								if (value == "none") value = "";
								props.setAttributes( { css_animation: "" } );
								setTimeout(function() {
									// console.log("replay");
									props.setAttributes( { css_animation: value } );
									greyd.cssAnims.init();
								}, 0);
							},
						} )
					} );

					// init css animation
					if (_.has(props.attributes, "css_animation") && props.attributes.css_animation != 'none') {
						setTimeout(function() {
							greyd.cssAnims.init();
						}, 0);
					}

				}

			}

			/**
			 * Hide block per breakpoint
			 */
			var hideBlockControl = false;
			if ( _.has(block_type.attributes, "hide") ) {

				// make classnames
				const makeClassnames = (value, atts) => {
					var classNames = _.has(atts, 'className') && !_.isEmpty(atts.className) ? atts.className.split(' ') : [];
					if ( typeof value === 'object' ) Object.keys(value).forEach( (bp) => {
						if ( value[bp] === true ) classNames.unshift('hidden-'+bp);
						else if ( classNames.indexOf('hidden-'+bp) > -1 ) classNames.splice( classNames.indexOf('hidden-'+bp), 1 );
					} );
					// clean
					classNames = greyd.tools.cleanClassArray(classNames);
					return classNames.join(' ');
				}

				/**
				 * test if 'hide' and 'className' attributes fit together
				 * @since 2.1.0
				 */
				var inTemplate = greyd.tools.isChildOf(props.clientId, 'greyd/dynamic');
				var [ timer, setTimer ] = wp.element.useState( {
					classNames: false,
					hide: false,
					timeout: false
				} );
				if (
					// if not in template an either 'className' or 'hide' has changed, trigger the test
					!inTemplate && 
					(timer.classNames != props.attributes.className || timer.hide != props.attributes.hide)
				) {
					if ( timer.timeout ) {
						// clear if e.g. another character is typed
						clearTimeout(timer.timeout);
					}
					setTimer( {
						classNames: props.attributes.className,
						hide: props.attributes.hide,
						// wait a bit (750ms) after attributes change (e.g. typing or paste) 
						// to check (and maybe adjust) the classNames(s) based on the 'hide' attribute.
						timeout: setTimeout(() => {
							var test = makeClassnames(props.attributes.hide, props.attributes);
							// console.log("test classnames:", test);
							if ( props.attributes.className != test ) {
								// console.info("adjust classnames")
								props.setAttributes( { className: test } );
							}
						}, 750)
					} );
				}

				// make hide controls
				hideBlockControl = el( greyd.components.layout.HideBlockControl, {
					label: __("Hide", 'greyd_hub'),
					help: __("If activated, the block is hidden from the respective breakpoint.", 'greyd_hub'),
					value: props.attributes.hide,
					onChange: (value) => {
						// make classnames
						var classNames = makeClassnames(value, props.attributes);
						// console.log(classNames);
						props.setAttributes( { hide: value, className: classNames } );
					}
				} );

			}
			

			/**
			 * Disable element support.
			 */
			var disabledControl = false;
			if (_.has(block_type.attributes, "disable_element")) {
				// console.log("add disable_element support to: "+props.name);

				// make disabled controls
				disabledControl = el( wp.components.ToggleControl, {
					label: __("Hide", 'greyd_hub'),
					checked: props.attributes.disable_element,
					onChange: function(value) { 
						var classNames = [];
						if ( value ) classNames.push('is-hidden');
						// saved className
						if (_.has(props.attributes, 'className') && !_.isEmpty(props.attributes.className)) {
							// remove 'is-hidden'
							var oldClasses = props.attributes.className.split(/is-hidden\s*/g);
							// add all other
							classNames.push( ...oldClasses );
						}
						// clean
						classNames = greyd.tools.cleanClassArray(classNames);
						// console.log(classNames);
						props.setAttributes( { disable_element: value, className: classNames.join(' ') } ); 
					},
				} );
					
			}

			/**
			 * Inline CSS support.
			 */
			var inlinecssStyle = false;
			var inlinecssControl = false;
			if (_.has(block_type.attributes, "inline_css")) {
				// console.log("add inline_css support to: "+props.name);

				// render inline style
				if ( props.attributes.inline_css && props.attributes.inline_css != "") {
					inlinecssStyle = el( 'style', { className: 'greyd_styles' }, "#block-"+props.clientId+" { "+props.attributes.inline_css+" } " );
				}

				// make inlinecss controls
				inlinecssControl = el( wp.components.TextareaControl, {
					label: __('Inline styling', 'greyd_hub'),
					// help: __('Notice: This inline style will override any other inline style generated by Gutenberg.', 'greyd_hub'),
					value: props.attributes.inline_css,
					// https://wpreset.com/add-codemirror-editor-plugin-theme/
					// onLoad: function() { console.log(this); wp.codeEditor.initialize($(this)) },
					onChange: function(value) { 
						props.setAttributes( { inline_css: value } ); 
					},
				} );

			}
			

			if ( greydClassControl || animationControl || hideBlockControl || disabledControl || inlinecssControl ) {

				// make block with avanced controls
				return el( wp.element.Fragment, {}, [
					// inlinecss styles
					inlinecssStyle?? null,
					// original block
					el( BlockEdit, {...props } ),
					// inspector advanced
					el( wp.blockEditor.InspectorAdvancedControls, {}, [
						// reset greydClass
						greydClassControl?? null,
						// css anims
						animationControl?? null,
						// hide
						hideBlockControl?? null,
						// disabled
						disabledControl?? null,
						// inline css
						inlinecssControl?? null,
					] ),
				] );

			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/layout/extend',
		extendEditBlockHook,
		90
	);

	/**
	 * Add custom edit controls to core blocks.
	 * - core/columns
	 * - core/column
	 *
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {

			/*
			=======================================================================
				Columns extensions
			=======================================================================
			*/

			/**
			 * Extend columns block.
			 */
			if (props.name == "core/columns") {

				// convert old stuff
				if (_.has(props.attributes, "background")) {
					// convert old background color
					if (_.has(props.attributes.background, "color")) {
						var color = props.attributes.background.color;
						if (color.indexOf('#') === 0) props.attributes.style = {
							color: { background: color }
						};
						else props.attributes.backgroundColor = color;
						delete props.attributes.background.color;
						props.attributes.background.type = "";
						console.info("columns background color converted.");
					}
					// convert old background gradient
					if (_.has(props.attributes.background, "gradient")) {
						var gradient = props.attributes.background.gradient;
						if (color.indexOf('linear-gradient') === 0) props.attributes.style = {
							color: { gradient: gradient }
						};
						else props.attributes.gradient = gradient;
						delete props.attributes.background.gradient;
						props.attributes.background.type = "";
						console.info("columns background gradient converted.");
					}
					// convert old nested pattern
					if (_.has(props.attributes.background, "overlay") && _.has(props.attributes.background.overlay, "pattern")) {
						props.attributes.background.pattern = props.attributes.background.overlay.pattern;
						delete props.attributes.background.overlay.pattern;
						console.info("columns background pattern converted.");
					}
				}

				var getAllClasses = function(attributes) {
					
					var classNames = [];
					
					// base class row_type
					if ( greyd.data?.is_greyd_classic ) {
						classNames = _.has(attributes, 'row') && _.has(attributes.row, 'type') ? [ attributes.row.type ] : [];
					}
					
					// saved className
					if (_.has(attributes, 'className') && !_.isEmpty(attributes.className)) {
						// remove stdClasses from old classes
						var oldClasses = attributes.className.split(/row_l\s*|row_xl\s*|row_xxl\s*|row_xxxl\s*/g);
						// add all other
						classNames.push( ...oldClasses );
					}

					// clean
					classNames = greyd.tools.cleanClassArray(classNames);
					// console.log(classNames);
					
					return classNames.join(' ');
				}

				// clean classes
				var allclasses = getAllClasses( props.attributes );
				if (props.isSelected && props.attributes.className != allclasses)
					props.setAttributes( { className: allclasses } );
				// console.log(props.attributes.className);

				// row type (toolbar)
				const makeRowTypeToolbar = function() {

					var icon_s = el( 'div', { className: "greyd-toolbar-icon" }, "S" );
					var icon_m = el( 'div', { className: "greyd-toolbar-icon" }, "M" );
					var icon_l = el( 'div', { className: "greyd-toolbar-icon" }, "L" );
					var icon_xl = el( 'div', { className: "greyd-toolbar-icon" }, "XL" );
					var icon = icon_l;
					if ( _.has(props.attributes, 'row') && _.has(props.attributes.row, 'type') ) {
						if (props.attributes.row.type == "row_l") { icon_s.props.className += " active"; icon = icon_s; }
						if (props.attributes.row.type == "row_xl") { icon_m.props.className += " active"; icon = icon_m; }
						if (props.attributes.row.type == "row_xxl") { icon_l.props.className += " active"; icon = icon_l; }
						if (props.attributes.row.type == "row_xxxl") { icon_xl.props.className += " active"; icon = icon_xl; }
					}

					// row_type toolbar
					return el( wp.components.ToolbarDropdownMenu, {
						// className: "components-toolbar",
						className: (_.has(props.attributes, "dynamic_parent") || !props.isSelected) ? 'disabled' : '',
						icon: icon,
						label: __("Change row width", 'greyd_hub'),
						controls: [
							{
								title: __("Row width", 'greyd_hub')+" S",
								icon: icon_s,
								onClick: function() { props.setAttributes( {
									row: { ...props.attributes.row, type: "row_l" }
								} ); },
							},
							{
								title: __("Row width", 'greyd_hub')+" M",
								icon: icon_m,
								onClick: function() { props.setAttributes( {
									row: { ...props.attributes.row, type: "row_xl" }
								} ); },
							},
							{
								title: __("Row width", 'greyd_hub')+" L",
								icon: icon_l,
								onClick: function() { props.setAttributes( {
									row: { ...props.attributes.row, type: "row_xxl" }
								} ); },
							},
							{
								title: __("Row width", 'greyd_hub')+" XL",
								icon: icon_xl,
								onClick: function() { props.setAttributes( {
									row: { ...props.attributes.row, type: "row_xxxl" }
								} ); },
							},
						],
					} );

				};

				// background image (toolbar)
				const makeBackgroundImageToolbar = function() {

					if (_.has(props.attributes, "background") &&
						_.has(props.attributes.background, 'type') &&
						props.attributes.background.type == 'image'
					) {
						return el( wp.blockEditor.BlockControls, { group: 'inline' }, [
							el( greyd.components.ToolbarDynamicImageControl, {
								clientId: props.clientId,
								useIdAsTag: true,
								icon: 'image',
								value: _.has(props.attributes.background, 'image') ? props.attributes.background.image : null,
								onChange: (value) => {
									props.setAttributes({
										background: {
											...props.attributes.background,
											image: {
												...props.attributes.background.image,
												...value
											}
										}
									});
								}
							} )
						] );
					}
					return null;

				};

				// stretch content (inspector)
				const makeStretchInspectorControls = function() {

					var stretchValue = "";
					if ( _.has(props.attributes, 'className') && !_.isEmpty(props.attributes.className) ) {
						if ( props.attributes.className.indexOf('flex-stretch-content-sm') !== -1 ) {
							stretchValue = 'sm';
						}
						else if ( props.attributes.className.indexOf('flex-stretch-content-md') !== -1 ) {
							stretchValue = 'md';
						}
						else if ( props.attributes.className.indexOf('flex-stretch-content-lg') !== -1 ) {
							stretchValue = 'lg';
						}
					}

					return el( wp.components.SelectControl, {
						label: __("Stretch elements to same height", 'greyd_hub'),
						help: __("Columns and content such as content boxes are stretched to the same height.", 'greyd_hub'),
						value: stretchValue,
						options: [
							{ label: _x("Don't stretch", 'small', 'greyd_hub'), value: "" },
							{ label: sprintf( _x("Tablet and larger (from %spx)", 'small', 'greyd_hub'), greyd.data.grid.sm ), value: "sm" },
							{ label: sprintf( _x("Laptops and larger (from %spx)", 'small', 'greyd_hub'), greyd.data.grid.md ), value: "md" },
							{ label: sprintf( _x("Desktop only (from %spx)", 'small', 'greyd_hub'), greyd.data.grid.lg ), value: "lg" },
						],
						onChange: function(value) {
							var currentClass = String(props.attributes.className).replaceAll(/flex-stretch-content-(lg|md|sm)/g, '');
							if ( value !== "" ) {
								currentClass += ' flex-stretch-content-'+value;
							}
							props.setAttributes( { className: currentClass } );
						},
					} );

				};

				// preview
				const makePreview = function() {

					// style
					var style = '#block-'+props.clientId+' { background: none !important; } ';

					// get background elements
					var backgroundElements = [];
					// basic
					var basic = greyd.tools.layout.makeBackground(props);
					if (basic['style'] != "") style += basic['style'];
					if (basic['blocks'].length > 0) backgroundElements = backgroundElements.concat(basic['blocks']);
					// overlay
					var overlay = greyd.tools.layout.makeOverlay(props);
					if (overlay['style'] != "") style += overlay['style'];
					if (overlay['blocks'].length > 0) backgroundElements = backgroundElements.concat(overlay['blocks']);
					// pattern
					var pattern = greyd.tools.layout.makePattern(props);
					if (pattern['style'] != "") style += pattern['style'];
					if (pattern['blocks'].length > 0) backgroundElements = backgroundElements.concat(pattern['blocks']);
					// seperator
					var seperator = greyd.tools.layout.makeSeperator(props);
					if (seperator['style'] != "") style += seperator['style'];
					if (seperator['blocks'].length > 0) backgroundElements = backgroundElements.concat(seperator['blocks']);

					// render background elements
					var background = null;
					if ( backgroundElements.length > 0 ) {
						background = el( 'div', {
							id: "bg-"+props.clientId,
							className: 'greyd-background'
						}, backgroundElements );
					}

					// css animation
					var wrapper_classes = [];
					if (_.has(props.attributes, 'css_animation') && props.attributes.css_animation != "none" && !_.isEmpty(props.attributes.css_animation) ) {
						wrapper_classes.push('animate-when-almost-visible '+props.attributes.css_animation);
					}

					return [
						// background styles
						(style != "" ? el( 'style', { className: 'greyd_styles' }, style ) : null),
						// wrap background and original block
						el( 'div', {
							id: "row-"+props.clientId,
							className: "row_wrap "+wrapper_classes.join(' '),
							/**
							 * Focus columns when clicking on the .row_wrap or .greyd-background
							 * @param {object} event JS default click event.
							 */
							onClick: (event) => {
								if ( $(event.target).hasClass('greyd-background') ) {
									$(event.target).next('.wp-block-columns').focus();
								}
								else {
									$(event.target).children('.wp-block-columns').focus();
								}
							}
						}, [
							// background preview
							background,
							// original block
							el( BlockEdit, props )
						] )
					];

				}

				return el( wp.element.Fragment, { }, [
					// toolbar
					greyd.data?.is_greyd_classic && el( wp.blockEditor.BlockControls, { }, [
						makeRowTypeToolbar()
					] ),
					makeBackgroundImageToolbar(),
					// inspector advanced
					el( wp.blockEditor.InspectorAdvancedControls, { }, [
						makeStretchInspectorControls()
					] ),
					// preview wrapper
					...makePreview(),
					// inspector
					el( wp.blockEditor.InspectorControls, { }, [
						// background controls
						greyd.components.layout.backgroundInspectorControls(props)
					] )
				] );

			}

			/**
			 * Extend column block (direct child of core/columns)
			 */
			if (props.name == "core/column") {

				var getAllClasses = function(attributes) {
					var classNames = [];
					
					// disable
					if (attributes.responsive.disable.xs) classNames.push("hidden-xs");
					if (attributes.responsive.disable.sm) classNames.push("hidden-sm");
					if (attributes.responsive.disable.md) classNames.push("hidden-md");
					if (attributes.responsive.disable.lg) classNames.push("hidden-lg");

					// width
					if (attributes.responsive.width.xs != "") classNames.push(attributes.responsive.width.xs);
					if (attributes.responsive.width.sm != "") classNames.push(attributes.responsive.width.sm);
					if (attributes.responsive.width.md != "") classNames.push(attributes.responsive.width.md);
					if (attributes.responsive.width.lg != "") classNames.push(attributes.responsive.width.lg);

					// order
					if (attributes.responsive.order.xs != "") classNames.push(attributes.responsive.order.xs);
					if (attributes.responsive.order.sm != "") classNames.push(attributes.responsive.order.sm);
					if (attributes.responsive.order.md != "") classNames.push(attributes.responsive.order.md);
					if (attributes.responsive.order.lg != "") classNames.push(attributes.responsive.order.lg);

					// offset
					if (attributes.responsive.offset.xs != "") classNames.push(attributes.responsive.offset.xs);
					if (attributes.responsive.offset.sm != "") classNames.push(attributes.responsive.offset.sm);
					if (attributes.responsive.offset.md != "") classNames.push(attributes.responsive.offset.md);
					if (attributes.responsive.offset.lg != "") classNames.push(attributes.responsive.offset.lg);

					// push
					// if (attributes.responsive.push_pull.xs != "" && attributes.responsive.push.xs != "") classNames.push(attributes.responsive.push.xs);
					// if (attributes.responsive.push_pull.sm != "" && attributes.responsive.push.sm != "") classNames.push(attributes.responsive.push.sm);
					// if (attributes.responsive.push_pull.md != "" && attributes.responsive.push.md != "") classNames.push(attributes.responsive.push.md);
					// if (attributes.responsive.push_pull.lg != "" && attributes.responsive.push.lg != "") classNames.push(attributes.responsive.push.lg);

					// pull
					// if (attributes.responsive.push_pull.xs != "" && attributes.responsive.pull.xs != "") classNames.push(attributes.responsive.pull.xs);
					// if (attributes.responsive.push_pull.sm != "" && attributes.responsive.pull.sm != "") classNames.push(attributes.responsive.pull.sm);
					// if (attributes.responsive.push_pull.md != "" && attributes.responsive.pull.md != "") classNames.push(attributes.responsive.pull.md);
					// if (attributes.responsive.push_pull.lg != "" && attributes.responsive.pull.lg != "") classNames.push(attributes.responsive.pull.lg);

					// saved className
					if (_.has(attributes, 'className') && !_.isEmpty(attributes.className)) {
						var stdClasses = [
							"hidden(?:-xs|-sm|-md|-lg)\s*",
							"col(?:|-xs|-sm|-md|-lg)-(?:auto|\\d\\/\\d|\\d+)\s*",
							"order(?:|-xs|-sm|-md|-lg)-(?:first|0|1|2|3|4|5|last)\s*",
							"offset(?:|-xs|-sm|-md|-lg)-(?:\\d\\/\\d|\\d+)\s*",
							// "push(?:|-xs|-sm|-md|-lg)-(?:\\d\\/\\d|\\d+)\s*",
							// "pull(?:|-xs|-sm|-md|-lg)-(?:\\d\\/\\d|\\d+)\s*"
						];
						// remove stdClasses from old classes
						var oldClasses = attributes.className.split(new RegExp(stdClasses.join('|'), 'g'));
						// add all other
						classNames.push( ...oldClasses );
					}

					// clean
					classNames = greyd.tools.cleanClassArray(classNames);
					// console.log(classNames);
					
					return classNames.join(' ');
				}

				var defaults = {
					width: { xs: "col-12", sm: "col-sm-auto", md: "", lg: "" },
					order: { xs: "", sm: "", md: "", lg: "" },
					offset: { xs: "", sm: "", md: "", lg: "" },
					push_pull: { xs: "", sm: "", md: "", lg: "" }, // unused
					push: { xs: "", sm: "", md: "", lg: "" }, // unused
					pull: { xs: "", sm: "", md: "", lg: "" }, // unused
					disable: { xs: false, sm: false, md: false, lg: false },
				};
				var holdsChange = function(att) {
					// console.log(values);
					if (!_.has(values, att)) return false;
					// console.log(values[att]);
					if (values[att].xs != defaults[att].xs) return true;
					else if (values[att].sm != defaults[att].sm) return true;
					else if (values[att].md != defaults[att].md) return true;
					else if (values[att].lg != defaults[att].lg) return true;
					else return false;
				};

				var values = greyd.tools.getValues(defaults, props.attributes.responsive);
				var setValues = function(val) {
					// console.log(val);
					// make new value
					var value = greyd.tools.makeValues(defaults, val);
					// console.log(value);
					props.setAttributes( { responsive: value } );
				}

				// clean classes
				var allclasses = getAllClasses( { ...props.attributes, responsive: values } );
				if (props.isSelected && props.attributes.className != allclasses)
					props.setAttributes( { className: allclasses } );
				// console.log(props.attributes.className);


				// width (inspector)
				const makeWidthControls = function() {

					/**
					 * Check if parent is set to 'Bootstrap' and add the class if not.
					 * @since 2.5.0
					 */
					let bootstrapControl = null

					if ( ! greyd.data?.is_greyd_classic ) {
						const { parent, parentAtts } = wp.data.useSelect(select => ({
							parent: select('core/block-editor').getBlockParents( props.clientId ),
							parentAtts: select('core/block-editor').getBlockAttributes( select('core/block-editor').getBlockParents( props.clientId )[0] )
						}));
						const isBootstrap = _.has(parentAtts, 'className') && parentAtts.className && String(parentAtts.className).indexOf('is-style-bootstrap') !== -1;

						if ( ! isBootstrap ) {
							bootstrapControl = el( wp.element.Fragment, { }, [
								el( 'p', { className: 'greyd-inspector-help' }, __("By setting the columns style to \"Bootstrap\", the columns no longer break unintentionally.", 'greyd_hub') ),
								el( wp.components.Button, {
									variant: 'secondary',
									isSmall: true,
									text: __("Set parent to 'Bootstrap'", 'greyd_hub'),
									onClick: () => {
										// add bootstrap class to parent
										const newParentAtts = { ...parentAtts };
										newParentAtts.className = newParentAtts.className.replace( 'is-style-default', '' ) + ' is-style-bootstrap';
										wp.data.dispatch('core/block-editor').updateBlockAttributes( parent[0], newParentAtts );
										setIsBootstrap(true);
									}
								} )
							] );
						}
					}
					
					return el( greyd.components.AdvancedPanelBody, {
						title: __("Width", 'greyd_hub'),
						initialOpen: true,
						holdsChange: holdsChange('width')
					}, [
						el( 'div', { className: 'greyd-icon-flex flex lg' }, [
							el( greyd.components.GreydIcon, {
								icon: 'desktop',
								title: greyd.tools.makeBreakpointTitle("lg") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.width.lg,
								onChange: (value) => setValues({
									...values,
									width: {
										...values.width,
										lg: value === '' ? '' : 'col-lg-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex md' }, [
							el( greyd.components.GreydIcon, {
								icon: 'laptop',
								title: greyd.tools.makeBreakpointTitle("md") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.width.md,
								onChange: (value) => setValues({
									...values,
									width: {
										...values.width,
										md: value === '' ? '' : 'col-md-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex sm' }, [
							el( greyd.components.GreydIcon, {
								icon: 'tablet',
								title: greyd.tools.makeBreakpointTitle("sm") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.width.sm,
								onChange: (value) => setValues({
									...values,
									width: {
										...values.width,
										sm: value === '' ? '' : 'col-sm-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex xs' }, [
							el( greyd.components.GreydIcon, {
								icon: 'mobile',
								title: greyd.tools.makeBreakpointTitle("xs") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.width.xs,
								options: [
									{
										label: __('100%', 'greyd_hub'),
										value: ''
									},
									{
										label: __("Automatically", 'greyd_hub'),
										value: 'auto'
									}
								],
								onChange: (value) => setValues({
									...values,
									width: {
										...values.width,
										xs: value === '' ? '' : 'col-' + value
									},
								}),
							} ),
						] ),

						bootstrapControl
					] );

				};

				// offset (inspector)
				const makeOffsetControls = function() {

					return el( greyd.components.AdvancedPanelBody, {
						title: __("Offset", 'greyd_hub'),
						initialOpen: false,
						holdsChange: holdsChange('offset')
					}, [
						el( 'label', { className: 'greyd-label'}, __("Offset", 'greyd_hub') ),
						el( 'div', { className: 'greyd-icon-flex flex lg' }, [
							el( greyd.components.GreydIcon, {
								icon: 'desktop',
								title: greyd.tools.makeBreakpointTitle("lg") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.offset.lg,
								options: [
									{
										label: __("Inherit ↓", 'greyd_hub'),
										value: ''
									},
									{
										label: __("No offset", 'greyd_hub'),
										value: '0'
									}
								],
								onChange: (value) => setValues({
									...values,
									offset: {
										...values.offset,
										lg: value === '' ? '' : 'offset-lg-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex md' }, [
							el( greyd.components.GreydIcon, {
								icon: 'laptop',
								title: greyd.tools.makeBreakpointTitle("md") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.offset.md,
								options: [
									{
										label: __("Inherit ↓", 'greyd_hub'),
										value: ''
									},
									{
										label: __("No offset", 'greyd_hub'),
										value: '0'
									}
								],
								onChange: (value) => setValues({
									...values,
									offset: {
										...values.offset,
										md: value === '' ? '' : 'offset-md-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex sm' }, [
							el( greyd.components.GreydIcon, {
								icon: 'tablet',
								title: greyd.tools.makeBreakpointTitle("sm") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.offset.sm,
								options: [
									{
										label: __("Inherit ↓", 'greyd_hub'),
										value: ''
									},
									{
										label: __("No offset", 'greyd_hub'),
										value: '0'
									}
								],
								onChange: (value) => setValues({
									...values,
									offset: {
										...values.offset,
										sm: value === '' ? '' : 'offset-sm-' + value
									},
								}),
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex xs' }, [
							el( greyd.components.GreydIcon, {
								icon: 'mobile',
								title: greyd.tools.makeBreakpointTitle("xs") }
							),
							el( greyd.components.layout.GridControl, {
								value: values.offset.xs,
								options: [
									{
										label: __("No offset", 'greyd_hub'),
										value: ''
									}
								],
								onChange: (value) => setValues({
									...values,
									offset: {
										...values.offset,
										xs: value === '' ? '' : 'offset-' + value
									},
								}),
							} ),
						] ),
					] );

				};

				// order (inspector)
				const makeOrderControls = function() {

					const getOrderOptions = function(breakpoint) {

						var options = [];
						if (breakpoint.indexOf("-") === -1) {
							options.push(
								{ label: __("Unchanged", 'greyd_hub'), value: "" }
							);
						}
						else {
							options.push(
								{ label: __("Inherit from smaller size", 'greyd_hub'), value: "" }
							);
						}
						options.push(
							{ label: __("Start", 'greyd_hub'), value: breakpoint+"-first" },
							{ label: __('1.', 'greyd_hub'), value: breakpoint+"-0" },
							{ label: __('2.', 'greyd_hub'), value: breakpoint+"-1" },
							{ label: __('3.', 'greyd_hub'), value: breakpoint+"-2" },
							{ label: __('4.', 'greyd_hub'), value: breakpoint+"-3" },
							{ label: __('5.', 'greyd_hub'), value: breakpoint+"-4" },
							{ label: __('6.', 'greyd_hub'), value: breakpoint+"-5" },
							{ label: __("End", 'greyd_hub'), value: breakpoint+"-last" },
						);
						return options;

					};

					return el( greyd.components.AdvancedPanelBody, {
						title: __("Order", 'greyd_hub'),
						initialOpen: false,
						holdsChange: holdsChange('order')
					}, [
						el( 'div', { className: 'greyd-icon-flex flex lg' }, [
							el( greyd.components.GreydIcon, {
								icon: 'desktop',
								title: greyd.tools.makeBreakpointTitle("lg") }
							),
							el( wp.components.SelectControl, {
								value: values.order.lg,
								options: getOrderOptions("order-lg"),
								onChange: function(value) { setValues( {
									...values, order: { ...values.order, lg: value },
								} ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex md' }, [
							el( greyd.components.GreydIcon, {
								icon: 'laptop',
								title: greyd.tools.makeBreakpointTitle("md") }
							),
							el( wp.components.SelectControl, {
								value: values.order.md,
								options: getOrderOptions("order-md"),
								onChange: function(value) { setValues( {
									...values, order: { ...values.order, md: value },
								} ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex sm' }, [
							el( greyd.components.GreydIcon, {
								icon: 'tablet',
								title: greyd.tools.makeBreakpointTitle("sm") }
							),
							el( wp.components.SelectControl, {
								value: values.order.sm,
								options: getOrderOptions("order-sm"),
								onChange: function(value) { setValues( {
									...values, order: { ...values.order, sm: value },
								} ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex xs' }, [
							el( greyd.components.GreydIcon, {
								icon: 'mobile',
								title: greyd.tools.makeBreakpointTitle("xs") }
							),
							el( wp.components.SelectControl, {
								value: values.order.xs,
								options: getOrderOptions("order"),
								onChange: function(value) { setValues( {
									...values, order: { ...values.order, xs: value },
								} ); },
							} ),
						] ),
					] );

				};

				// push/pull (inspector - unused)
				const makePushPullControls = function() {

					// disable
					return null;

					const getPushPullOptions = function(breakpoint) {

						var options = [
							{ label: __("No offset", 'greyd_hub'), value: breakpoint+"-0" },
							{ label: __("1 column - 1/12", 'greyd_hub'), value: breakpoint+"-1" },
							{ label: __("2 columns - 1/6", 'greyd_hub'), value: breakpoint+"-2" },
							{ label: __("3 columns - 1/4", 'greyd_hub'), value: breakpoint+"-3" },
							{ label: __("4 columns - 1/3", 'greyd_hub'), value: breakpoint+"-4" },
							{ label: __("5 columns - 5/12", 'greyd_hub'), value: breakpoint+"-5" },
							{ label: __("6 columns - 1/2", 'greyd_hub'), value: breakpoint+"-6" },
							{ label: __("7 columns - 7/12", 'greyd_hub'), value: breakpoint+"-7" },
							{ label: __("8 columns - 2/3", 'greyd_hub'), value: breakpoint+"-8" },
							{ label: __("9 columns - 3/4", 'greyd_hub'), value: breakpoint+"-9" },
							{ label: __("10 columns - 5/6", 'greyd_hub'), value: breakpoint+"-10" },
							{ label: __("11 columns - 11/12", 'greyd_hub'), value: breakpoint+"-11" },
							{ label: __("12 columns - 1/1", 'greyd_hub'), value: breakpoint+"-12" },
							{ label: __('20% - 1/5', 'greyd_hub'), value: breakpoint+"-1/5" },
							{ label: __('40% - 2/5', 'greyd_hub'), value: breakpoint+"-2/5" },
							{ label: __('60% - 3/5', 'greyd_hub'), value: breakpoint+"-3/5" },
							{ label: __('80% - 4/5', 'greyd_hub'), value: breakpoint+"-4/5" },
						];
						return options;

					}

					var push_lg_hidden = ""; var pull_lg_hidden = "";
					if (values.push_pull.lg == "") { push_lg_hidden = " hidden"; pull_lg_hidden = " hidden"; }
					if (values.push_pull.lg == "push") { pull_lg_hidden = " hidden"; }
					if (values.push_pull.lg == "pull") { push_lg_hidden = " hidden"; }
					var push_md_hidden = ""; var pull_md_hidden = "";
					if (values.push_pull.md == "") { push_md_hidden = " hidden"; pull_md_hidden = " hidden"; }
					if (values.push_pull.md == "push") { pull_md_hidden = " hidden"; }
					if (values.push_pull.md == "pull") { push_md_hidden = " hidden"; }
					var push_sm_hidden = ""; var pull_sm_hidden = "";
					if (values.push_pull.sm == "") { push_sm_hidden = " hidden"; pull_sm_hidden = " hidden"; }
					if (values.push_pull.sm == "push") { pull_sm_hidden = " hidden"; }
					if (values.push_pull.sm == "pull") { push_sm_hidden = " hidden"; }
					var push_xs_hidden = ""; var pull_xs_hidden = "";
					if (values.push_pull.xs == "") { push_xs_hidden = " hidden"; pull_xs_hidden = " hidden"; }
					if (values.push_pull.xs == "push") { pull_xs_hidden = " hidden"; }
					if (values.push_pull.xs == "pull") { push_xs_hidden = " hidden"; }

					return el( greyd.components.AdvancedPanelBody, {
						title: __('Push & Pull', 'greyd_hub'),
						initialOpen: false,
						holdsChange: holdsChange('push_pull')
					}, [
						// push and pull
						el( 'label', { className: 'greyd-label'}, __("Push or Pull", 'greyd_hub') ),
						el( 'div', { className: 'greyd-inspector-wrapper greyd-icon-2 xs' }, [
							el( wp.components.Icon, {
								icon: 'smartphone',
								title: greyd.tools.makeBreakpointTitle("xs") }
							),
							el( wp.components.SelectControl, {
								value: values.push_pull.xs,
								options: [
									{ label: __('', 'greyd_hub'), value: "" },
									{ label: __('Push', 'greyd_hub'), value: "push" },
									{ label: __('Pull', 'greyd_hub'), value: "pull" }
								],
								onChange: function(value) { setValues( {
									...values,
									push_pull: { ...values.push_pull, xs: value },
									pull: { ...values.pull, xs: "pull-0" },
									push: { ...values.push, xs: "push-0" }
								} ); },
							} ),
							el( 'div', { className: "components-base-control"+push_xs_hidden }, [
								el( wp.components.SelectControl, {
									value: values.push.xs,
									options: getPushPullOptions("push"),
									onChange: function(value) { setValues( {
										...values, push: { ...values.push, xs: value }
									} ); },
								} ),
							] ),
							el( 'div', { className: "components-base-control"+pull_xs_hidden }, [
								el( wp.components.SelectControl, {
									value: values.pull.xs,
									options: getPushPullOptions("pull"),
									onChange: function(value) { setValues( {
										...values, pull: { ...values.pull, xs: value }
									} ); },
								} ),
							] ),
						] ),
						el( 'div', { className: 'greyd-inspector-wrapper greyd-icon-2 sm' }, [
							el( wp.components.Icon, {
								icon: 'tablet',
								title: greyd.tools.makeBreakpointTitle("sm") }
							),
							el( wp.components.SelectControl, {
								value: values.push_pull.sm,
								options: [
									{ label: __("inherit", 'greyd_hub'), value: "" },
									{ label: __('Push', 'greyd_hub'), value: "push" },
									{ label: __('Pull', 'greyd_hub'), value: "pull" }
								],
								onChange: function(value) { setValues( {
									...values,
									push_pull: { ...values.push_pull, sm: value },
									pull: { ...values.pull, sm: "pull-sm-0" },
									push: { ...values.push, sm: "push-sm-0" }
								} ); },
							} ),
							el( 'div', { className: "components-base-control"+push_sm_hidden }, [
								el( wp.components.SelectControl, {
									value: values.push.sm,
									options: getPushPullOptions("push-sm"),
									onChange: function(value) { setValues( {
										...values, push: { ...values.push, sm: value }
									} ); },
								} ),
							] ),
							el( 'div', { className: "components-base-control"+pull_sm_hidden }, [
								el( wp.components.SelectControl, {
									value: values.pull.sm,
									options: getPushPullOptions("pull-sm"),
									onChange: function(value) { setValues( {
										...values, pull: { ...values.pull, sm: value }
									} ); },
								} ),
							] ),
						] ),
						el( 'div', { className: 'greyd-inspector-wrapper greyd-icon-2 md' }, [
							el( wp.components.Icon, {
								icon: 'laptop',
								title: greyd.tools.makeBreakpointTitle("md") }
							),
							el( wp.components.SelectControl, {
								value: values.push_pull.md,
								options: [
									{ label: __("inherit", 'greyd_hub'), value: "" },
									{ label: __('Push', 'greyd_hub'), value: "push" },
									{ label: __('Pull', 'greyd_hub'), value: "pull" }
								],
								onChange: function(value) { setValues( {
									...values,
									push_pull: { ...values.push_pull, md: value },
									pull: { ...values.pull, md: "pull-md-0" },
									push: { ...values.push, md: "push-md-0" }
								} ); },
							} ),
							el( 'div', { className: "components-base-control"+push_md_hidden }, [
								el( wp.components.SelectControl, {
									value: values.push.md,
									options: getPushPullOptions("push-md"),
									onChange: function(value) { setValues( {
										...values, push: { ...values.push, md: value }
									} ); },
								} ),
							] ),
							el( 'div', { className: "components-base-control"+pull_md_hidden }, [
								el( wp.components.SelectControl, {
									value: values.pull.md,
									options: getPushPullOptions("pull-md"),
									onChange: function(value) { setValues( {
										...values, pull: { ...values.pull, md: value }
									} ); },
								} ),
							] ),
						] ),
						el( 'div', { className: 'greyd-inspector-wrapper greyd-icon-2 lg' }, [
							el( wp.components.Icon, {
								icon: 'desktop',
								title: greyd.tools.makeBreakpointTitle("lg") }
							),
							el( wp.components.SelectControl, {
								value: values.push_pull.lg,
								options: [
									{ label: __("inherit", 'greyd_hub'), value: "" },
									{ label: __('Push', 'greyd_hub'), value: "push" },
									{ label: __('Pull', 'greyd_hub'), value: "pull" }
								],
								onChange: function(value) { setValues( {
									...values,
									push_pull: { ...values.push_pull, lg: value },
									pull: { ...values.pull, lg: "pull-lg-0" },
									push: { ...values.push, lg: "push-lg-0" }
								} ); },
							} ),
							el( 'div', { className: "components-base-control"+push_lg_hidden }, [
								el( wp.components.SelectControl, {
									value: values.push.lg,
									options: getPushPullOptions("push-lg"),
									onChange: function(value) { setValues( {
										...values, push: { ...values.push, lg: value }
									} ); },
								} ),
							] ),
							el( 'div', { className: "components-base-control"+pull_lg_hidden }, [
								el( wp.components.SelectControl, {
									value: values.pull.lg,
									options: getPushPullOptions("pull-lg"),
									onChange: function(value) { setValues( {
										...values, pull: { ...values.pull, lg: value }
									} ); },
								} ),
							] ),
						] )
					] );

				};

				// disable/hide (inspector)
				const makeHideControls = function() {

					return el( greyd.components.AdvancedPanelBody, {
						title: __("Hide", 'greyd_hub'),
						initialOpen: false,
						holdsChange: holdsChange('disable')
					}, [
						el( greyd.components.layout.HideBlockControl, {
							help: __("If activated, the column is hidden from the respective breakpoint.", 'greyd_hub'),
							value: values.disable,
							onChange: (value) => setValues( {
								...values, disable: value
							} )
						} ),
					] );

				};

				return el( wp.element.Fragment, { }, [
					(
						// set style for original width attribute
						_.has(props.attributes, 'width') && props.attributes.width != "" ?
						el( 'style', { className: 'greyd_styles' }, "#block-"+props.clientId+" { width: "+props.attributes.width+" }" ) :
						null
					),
					// original block
					el( BlockEdit, props ),
					// inspector
					el( wp.blockEditor.InspectorControls, { }, [
						// width
						makeWidthControls(),
						// offset
						makeOffsetControls(),
						// order
						makeOrderControls(),
						// push/pull
						makePushPullControls(),
						// disable
						makeHideControls(),
					] )
				] );

			}

			// return original block
			return el( BlockEdit, {...props } );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/layout/edit',
		editBlockHook
	);

	
	/**
	 * Change values on saving of blocks.
	 * - css_animation classes
	 * 
	 * @hook blocks.getSaveContent.extraProps
	 */
	var saveBlockHook = function(props, name, atts) {
		// console.log(name);

		var block_type = wp.blocks.getBlockType(name.name);

		/**
		 * Add css animation class to blocks with support.
		 * columns and box render their own wrapper with class in php.
		 */
		if (
			_.has(block_type.attributes, "css_animation") && 
			name.name != 'core/columns' && 
			name.name != 'greyd/box' &&
			_.has(atts, 'css_animation')
		) {

			let className = '';
			if ( props.attributes?.className && !_.isEmpty(props.attributes.className) ) {
				className = props.attributes.className;
			}
			else if ( props.className && !_.isEmpty(props.className) ) {
				className = props.className;
			}

			var animClass = className;
			/**
			 * We still save the old trigger-class to make sure old content works.
			 * The class is replaced with 'animate-when-almost-visible' in frontend while rendering in php.
			 */
			var triggerClass = 'wpb_animate_when_almost_visible';
			if (!_.isEmpty(atts.css_animation) && atts.css_animation != "none") {
				// add anim and trigger class
				animClass = triggerClass+' '+atts.css_animation;
				if (className.indexOf(triggerClass) == -1) {
					// add classNames
					animClass = className+' '+animClass;
				}
				else {
					// change anim class
					var classNames = className.split(' ');
					for (var i=0; i<classNames.length-1; i++) {
						if (classNames[i] == triggerClass) {
							classNames[i+1] = atts.css_animation;
							break;
						}
					}
					animClass = classNames.join(' ');
				}
			}
			else {
				// remove anim and trigger class
				if (animClass.indexOf(triggerClass) > -1) {
					var classNames = animClass.split(' ');
					for (var i=0; i<classNames.length-1; i++) {
						if (classNames[i] == triggerClass) {
							// remove classNames
							classNames.splice(i, 2);
							break;
						}
					}
					animClass = classNames.join(' ');
				}
			}
			_.assign(props, { className: animClass });
		}

		return props;
	};

	wp.hooks.addFilter(
		'blocks.getSaveContent.extraProps',
		'greyd/hook/layout/save',
		saveBlockHook
	);


	/**
	 * Change Block props values before rendering.
	 * - css_animation classes
	 * 
	 * @hook editor.BlockListBlock
	 */
	var editBlockListHook = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {

		function isInViewport(el) {
			if (!el) return false;
			var rect = el.getBoundingClientRect();
			var viewportHeight = document.getElementsByClassName('interface-interface-skeleton__content')[0].clientHeight ;

			return rect.top >= 0 || rect.bottom <= viewportHeight;
		}

		return function ( props ) {
			// console.log(BlockListBlock);
			// console.log(props);

			/**
			 * Inject css animation classes to blocks with support.
			 */
			var block_type = wp.blocks.getBlockType(props.name);
			if (
				_.has(block_type.attributes, "css_animation") && 
				props.name != 'core/columns' && 
				_.has(props.attributes, 'css_animation')
			) {
				var classNames = props.className && !_.isEmpty(props.className) ? props.className.split(' ') : [];
				var triggerClass = 'animate-when-almost-visible';
				if (!_.isEmpty(props.attributes.css_animation) && props.attributes.css_animation != "none") {
					if (classNames.indexOf(triggerClass) > -1) {
						// change anim class
						for (var i=0; i<classNames.length-1; i++) {
							if (classNames[i] == triggerClass) {
								classNames[i+1] = props.attributes.css_animation;
								break;
							}
						}
					}
					else {
						// add anim classes
						classNames.push( triggerClass );
						classNames.push( props.attributes.css_animation );
					}
					if (isInViewport(document.getElementById("block-"+props.clientId))) {
						// add triggered state
						classNames.push( "start-animation" );
						classNames.push( "animated" );
					}
				}
				else if (classNames.indexOf(triggerClass) > -1) {
					// remove anim classes
					for (var i=0; i<classNames.length-1; i++) {
						if (classNames[i] == triggerClass) {
							// remove classes
							classNames.splice(i, 2);
							break;
						}
					}
				}
				props.className = classNames.join(' ');
				// console.log(props);
			}

			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/layout/list', 
		editBlockListHook 
	);
	
	console.log("Layout Blocks Scripts: loaded");

} )( window.wp );