/**
 * Registers all greyd/ blocks.
 */
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	const transformDefaultAtts = function ( attributes ) {

		var newatts = {};
		if ( _.has( attributes, 'anchor' ) && attributes[ "anchor" ] != "" ) {
			newatts.anchor = attributes.anchor;
		}
		if ( _.has( attributes, 'className' ) && attributes[ "className" ] != "" ) {
			newatts.className = attributes.className;
		}
		if ( _.has( attributes, 'inline_css' ) && attributes[ "inline_css" ] != "" ) {
			newatts.inline_css = attributes.inline_css;
			newatts.inline_css_id = attributes.inline_css_id;
		}
		if ( _.has( attributes, 'trigger' ) && attributes[ "trigger" ] != "" ) {
			newatts.trigger = attributes.trigger;
		}
		return newatts;
	};

	wp.blocks.registerBlockType( 'greyd/anchor', {
		title: __( "Anchor target", 'greyd_hub' ),
		description: __( "Definition of anchors", 'greyd_hub' ),
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

		edit: function ( props ) {
			var offset = "";
			if ( _.has( props.attributes, "greydStyles.marginTop" ) ) {
				props.attributes.greydStyles[ '--anchorcustommargin' ] = props.attributes.greydStyles.marginTop;
				delete props.attributes.greydStyles.marginTop;
			}
			if ( _.has( props.attributes, "greydStyles.--anchorcustommargin" ) ) offset = props.attributes.greydStyles[ '--anchorcustommargin' ];

			return [
				// inspector
				el( wp.blockEditor.InspectorControls, {}, [
					el( wp.components.PanelBody, { title: __( "General", 'greyd_hub' ), initialOpen: true }, [
						el( wp.components.TextControl, {
							label: __( "Anchor title", 'greyd_hub' ),
							value: props.attributes.anchor,
							onChange: function ( value ) { props.setAttributes( { anchor: value } ); },
							help: __(
								"Choose a unique name for this anchor. The name will be converted to a URL-friendly format (special characters and spaces will be encoded). Note that anchor names are case-sensitive."
							),
						} ),
						el( wp.components.Tip, {}, [
							__( "For details about what characters are encoded, see the PHP documentation: ", 'greyd_hub' ),
							el( wp.components.ExternalLink, {
								href: "https://www.php.net/manual/en/function.rawurlencode.php",
								children: "rawurlencode()",
								target: "_blank"
							} )
						] )
					] ),
					el( greyd.components.StylingControlPanel, {
						title: __( "Offset", 'greyd_hub' ),
						initialOpen: false,
						supportsResponsive: true,
						blockProps: props,
						controls: [ {
							label: __( "Vertical offset", 'greyd_hub' ),
							attribute: "--anchorcustommargin",
							control: greyd.components.RangeUnitControl,
							supportsPresets: true,
							min: -500,
							max: 500
						} ]
					} ),
				] ),
				// preview
				el( 'div', { className: props.className + ' preview-info-wrapper' }, [
					props.isSelected && offset !== "" && el( 'div', { className: 'preview-anchor-helper', style: { marginTop: offset } } ),
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('anchor'),
						el( 'div', { className: 'preview-info-title' }, [
							el( 'ul', {}, el( 'li', { id: props.attributes.anchor, }, [
								el( 'strong', {}, __( "Anchor target", 'greyd_hub' ) + ': ' ),
								el( wp.blockEditor.RichText, {
									format: 'string',
									tagName: 'span',
									style: { flex: '1' },
									value: props.attributes.anchor,
									placeholder: __( "Enter anchor name", 'greyd_hub' ),
									allowedFormats: [ 'greyd/dtag' ],
									onChange: function ( value ) {
										props.setAttributes( { anchor: value } );
									},
								} ),
							] ) ),
						] ),
					] ),
				] )
			];
		},
		save: function ( props ) {
			return el( 'div', {
				className: 'greyd-anchor-target--wrapper',
			}, [
				el( 'div', {
					id: props.attributes.anchor,
					className: 'greyd-anchor-target',
				} )
			] );
		},

		deprecated: [
			/**
			 * @since 2.4.0 Assign anchor to a div with className
			 */
			{
				attributes: {
					anchor: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
				},
				supports: {
					className: false,
					customClassName: false,
				},
				save: function ( props ) {
					return el( 'div', {
						style: { all: 'unset', position: 'relative' }
					}, [
						el( 'div', {
							id: props.attributes.anchor,
							className: 'greyd-anchor-target',
						} )
					] )
				},
			},
			/**
			 * @since 1.8.5 Wrap anchor in relative div to prevent mislocation.
			 */
			{
				attributes: {
					anchor: { type: 'string', default: '' },
					greydStyles: { type: 'object' },
				},
				supports: {
					className: false,
					customClassName: false,
				},
				save: function ( props ) {
					return el( 'div', {
						id: props.attributes.anchor,
						className: 'greyd-anchor-target',
					} );
				},
			},
		],

		transforms: {
			to: [
				{
					type: 'block',
					blocks: [ 'core/group' ],
					isMatch: function ( attributes ) {
						// console.log(attributes);
						if ( _.has( attributes, 'anchor' ) && attributes[ "anchor" ] != "" ) {
							return true;
						}
						return false;
					},
					transform: function ( attributes, innerBlocks ) {
						console.log( 'convert group to anchor' );
						// console.log(attributes);
						// console.log(innerBlocks);

						var newatts = transformDefaultAtts( attributes );

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

} )( window.wp );