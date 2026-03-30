/**
 * Javascript File for Popup-close Button.
 * This file is loaded in the editor, only on popup admin pages.
 * Depends on loading of 'greyd-popups-script' (editor.js).
 */

( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register Popup-Close Block only if not already registered.
	 * remove blocks.registerBlockType( 'greyd/popup-close', ... ) from block plugin blocks.js to use this.
	 */
	var found = wp.blocks.getBlockTypes().filter((block) => { return block.name == "greyd/popup-close" });
	if (found.length == 0) {
		wp.blocks.registerBlockType( 'greyd/popup-close', {
			title: __("Close pop-up", 'greyd_hub'),
			description: __("Button for closing pop-ups", 'greyd_hub'),
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
							title: __('Colors', 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							holdsColors: [
								{ 
									color: (_.has(props.attributes, 'greydStyles.color')) ? props.attributes.greydStyles.color : '', 
									title: __('Icon color', 'greyd_hub') 
								},
								{ 
									color: (_.has(props.attributes, 'greydStyles.backgroundColor')) ? props.attributes.greydStyles.backgroundColor : '', 
									title: __('Background color', 'greyd_hub') 
								}
							],
							blockProps: props,
							controls: [
								{
									label: __('Icon color', 'greyd_hub'),
									attribute: "color",
									control: greyd.components.ColorGradientPopupControl,
									mode: 'color'
								},
								{
									label: __('Background color', 'greyd_hub'),
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
							title: __('Size', 'greyd_hub'),
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
							title: __('Thickness', 'greyd_hub'),
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
								title: __('Border radius', 'greyd_hub'),
								blockProps: props,
								controls: [
									{
										label: __('Radius', 'greyd_hub'),
										attribute: "borderRadius",
										control: greyd.components.DimensionControl,
										type: 'string',
										sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
										max: 20
									},
								]
							} ),
						] )
					] ),

					// preview
					el( 'div' , {
						className: [ 'popup_close_button', props.attributes.className, props.attributes.greydClass ].join(' '),
						"aria-label": __('Close pop-up', 'greyd_hub'),
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
						"aria-label": __('Close pop-up', 'greyd_hub'),
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

} )(
	window.wp,
	greyd.components
);