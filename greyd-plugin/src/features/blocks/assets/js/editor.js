/**
 * Greyd.Blocks Editor Script.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 * It registers styles, plugins, hooks and more.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __, sprintf } = wp.i18n;
	var _ = lodash;

	// ready/init
	wp.domReady(function () {

		/**
		 * Register Block Styles
		 */

		/* seperator */
		wp.blocks.unregisterBlockStyle( 'core/separator', 'dots' );
		wp.blocks.registerBlockStyle( 'core/separator', { name: 'bar', label: __('Bar', 'greyd_hub') } );

		/* image */
		wp.blocks.registerBlockStyle( 'core/image', { name: 'rounded-corners', label: __("Rounded corners", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'has-shadow', label: __("Shadow", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'diagonal-up', label: __("Diagonal (up)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'diagonal-down', label: __("Diagonal (down)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'rotate-left', label: __("Rotated (left)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'rotate-right', label: __("Rotated (right)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'tilt-left', label: __("3D (left)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/image', { name: 'tilt-right', label: __("3D (right)", 'greyd_hub') } );

		/* media & text */
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'rounded-corners', label: __("Rounded corners", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'has-shadow', label: __("Shadow", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'diagonal-up', label: __("Diagonal (up)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'diagonal-down', label: __("Diagonal (down)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'rotate-left', label: __("Rotated (left)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'rotate-right', label: __("Rotated (right)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'tilt-left', label: __("3D (left)", 'greyd_hub') } );
		wp.blocks.registerBlockStyle( 'core/media-text', { name: 'tilt-right', label: __("3D (right)", 'greyd_hub') } );

		// var newContent = data.select('core/editor').getEditedPostAttribute('content');
		// console.log(newContent);
		// // ... [manipulate content string as you please ]...
		// var newBlocks = wp.blocks.parse(newContent);
		// data.dispatch('core/block-editor').resetBlocks(newBlocks);
	});

	/**
	 * Register custom attributes to core blocks.
	 * 
	 * @hook wp.blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {
		// console.log(name);
		// console.log(settings);
		
		if (_.has(settings, 'apiVersion') && settings.apiVersion > 1) {
			// console.log(name);
			// console.log(settings);
			
			if (_.has(settings, 'supports.color.gradients')) {
				// console.log(settings);
				// add deprecated setting to handle block validation
				if (!_.has(settings, 'deprecated')) settings.deprecated = [];
				settings.deprecated.push({ 
					attributes: settings.attributes, 
					save: function(props) {
						// console.log(props);
						// hook save function
						var saved = settings.save(props);
						if (_.has(props.attributes, 'style.color.gradient')) {
							// inject gradient with colors to check for old saved state without 'var(--colorXX)' colors
							saved.props.style = { background: props.attributes.style.color.gradient };
						}
						// console.log(saved);
						return saved;
					}
				});
			}

			/**
			 * @since 1.7.0 moved to layout
			 */
			// if (name == 'core/group' || 
			// 	name == 'core/columns') {
			// 	settings.attributes.disable_element = { type: 'boolean' };
			// }
			// if (name == 'core/group' || 
			// 	name == 'core/columns' || 
			// 	name == 'core/column' || 
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
			// 	name == 'core/query') {
			// 	settings.attributes.inline_css = { type: 'string' };
			// 	settings.attributes.inline_css_id = { type: 'string' };
			// }
			// if (name == 'core/embed' || 
			// 	name == 'core/html' ||
			// 	name == 'core/query' ||
			// 	name == 'core/search' ||
			// 	name == 'core/latest-comments' ||
			// 	name == 'core/calendar' ||
			// 	name == 'core/page-list' ||
			// 	name == 'core/tag-cloud' ||
			// 	name == 'core/latest-posts' ||
			// 	name == "core/categories" ||
			// 	name == "core/archives" ||
			// 	name == "core/rss" ) {
			// 	settings.supports.anchor = true;
			// 	settings.attributes.anchor = { type: 'string' };
			// }
			
			if (
				name == 'core/group'
				|| name == 'core/columns'
				|| name == 'core/paragraph'
				|| name == 'core/heading'
				|| name == 'core/image'
				|| name == 'core/video'
				|| name == 'core/separator'
				|| name == 'core/site-logo'
			) {
				settings.attributes.greydClass = { type: 'string' };
				settings.attributes.greydStyles = { type: 'object' };
			}

			if (name == 'core/group') {
				// settings.supports.spacing.padding = false;
				settings.supports.align = true;
				// set transform.from from '*' to array of all registered blocks without exclude
				var transforms = [];
				var exclude = [ name, 'greyd/box', 'greyd/anchor' ];
				greyd.data.all_block_types.forEach(function(val, i) {
					if (exclude.indexOf(val) == -1) transforms.push(val);
				});
				settings.transforms.from[0].blocks = transforms;
				// fix deprecation until fix is merged to core in 6.7
				// https://github.com/WordPress/gutenberg/pull/63837
				if (
					settings.deprecated.length == 6 && settings.deprecated[0].isEligible &&
					greyd.tools.versionCompare(greyd.data.versions["wp"], "6.7", "<") &&
					( !greyd.data.versions["gutenberg"] || greyd.tools.versionCompare(greyd.data.versions["gutenberg"], "18.9.0", "<") )
				) {
					settings.deprecated[0].isEligible = ( { layout } ) =>
						layout?.inherit || ( layout?.contentSize && layout?.type !== 'constrained' );
					// console.log(settings);
				}
			}
			if (name == 'core/spacer') {
				// responsive
				settings.attributes.responsive = { type: 'object' };

				// default value
				if ( ! greyd.data.is_greyd_classic && _.has(settings.attributes, 'height') && _.has(settings.attributes.height, 'default') ) {
					settings.attributes.height.default = 'var:preset|spacing|large';
				}
			}
			if (name == 'core/embed') {
				settings.attributes.width = { type: 'string', default: '' };
				// console.log(settings);
			}
			if (name == 'core/html') {
				settings.supports.className = true;
				settings.supports.customClassName = true;
				// console.log(settings);
			}
			if (name == 'core/heading') {
				settings.supports.__experimentalFontStyle = true;
				settings.supports.__experimentalFontWeight = true;
				// console.log(settings);
			}
			if (name == 'core/separator') {
				settings.supports.className = true;
				settings.supports.customClassName = true;
				settings.supports.align = true;
				settings.attributes.dots = { type: 'boolean', default: false };
			}
			if (name == 'core/video') {
				settings.attributes.mobile = { type: 'object', default: {
					breakpoint: 'sm',
					id: -1,
					url: ''
				} };
			}
			
			if (name == 'core/archives') {
				// console.log(settings);
				settings.attributes = {
					...settings.attributes,
					filter: { type: 'object', default: {
						post_type: 'post',
						type: 'monthly',
						order: '',
						hierarchical: 0,
						date_format: ''
					} },
					styles: { type: 'object', default: {
						style: '',
						size: '',
						custom: 0,
						icon: {
							content: '',
							position: 'after',
							size: '100%',
							margin: '10px'
						}
					} },
					greydClass: { type: 'string'},
					customStyles: { type: 'object'},
				};
			}
			
			if (name == 'core/social-links') {
				// settings.supports.align = false;
				// console.log(settings);
			}
			if (name == 'core/list') {
				// console.log(settings);
			}
		}
		return settings;
	};

	/**
	 * Add custom controls to core blocks.
	 * 
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {		
			// console.log(props);

			var extend = false;
			var ex = {
				before_original_block_controls: [],
				after_original_block_controls: [],
				before_original_inspector_controls: [],
				before_original_inspector_controls_styles: [],
				before_original_advanced_controls: [],
				before_original_block: [],
				original_block: el( BlockEdit, props ),
				after_original_block: [],
				after_original_inspector_controls: [],
				after_original_inspector_controls_styles: [],
				after_original_advanced_controls: [],
			}
			var block_type = wp.blocks.getBlockType(props.name);
			// console.log(block_type);


			/**
			 * =================================================================
			 *                          General controls (multiple blocks)
			 * =================================================================
			 */

			/**
			 * Disable element support.
			 * @since 1.7.0 moved to layout
			 */
			// if (_.has(block_type.attributes, "disable_element")) {
			// 	extend = true;
			// 	// console.log("add disable_element support to: "+props.name);

			// 	ex.after_original_advanced_controls.push(
			// 		el( wp.components.ToggleControl, {
			// 			label: __("Hide", 'greyd_hub'),
			// 			checked: props.attributes.disable_element,
			// 			onChange: function(value) { 
			// 				var classNames = [];
			// 				if ( value ) classNames.push('is-hidden');
			// 				// saved className
			// 				if (_.has(props.attributes, 'className') && !_.isEmpty(props.attributes.className)) {
			// 					// remove 'is-hidden'
			// 					var oldClasses = props.attributes.className.split(/is-hidden\s*/g);
			// 					// add all other
			// 					classNames.push( ...oldClasses );
			// 				}
			// 				// clean
			// 				classNames = greyd.tools.cleanClassArray(classNames);
			// 				// console.log(classNames);
			// 				props.setAttributes( { disable_element: value, className: classNames.join(' ') } ); 
			// 			},
			// 		} )
			// 	);
			// }

			/**
			 * Inline CSS support.
			 * @since 1.7.0 moved to layout
			 */
			// if (_.has(block_type.attributes, "inline_css")) {
			// 	extend = true;
			// 	// console.log("add inline_css support to: "+props.name);

			// 	if (props.attributes.inline_css != "" && typeof props.attributes.inline_css !== 'undefined') 
			// 		ex.before_original_block.push(el( 'style', { className: 'greyd_styles' }, "#block-"+props.clientId+" { "+props.attributes.inline_css+" } " ));
					
			// 	ex.after_original_advanced_controls.push(
			// 		el( wp.components.TextareaControl, {
			// 			label: __('Inline styling', 'greyd_hub'),
			// 			// help: __('Notice: This inline style will override any other inline style generated by Gutenberg.', 'greyd_hub'),
			// 			value: props.attributes.inline_css,
			// 			// https://wpreset.com/add-codemirror-editor-plugin-theme/
			// 			// onLoad: function() { console.log(this); wp.codeEditor.initialize($(this)) },
			// 			onChange: function(value) { 
			// 				var css_id = (_.has(props.attributes, 'anchor') && props.attributes.anchor != "") ? props.attributes.anchor : 'block-'+props.clientId;
			// 				props.setAttributes( { inline_css: value, inline_css_id: css_id } ); 
			// 			},
			// 		} )
			// 	);
			// }
			

			/**
			 * fix greydStyles color value in cases when the transparent color preset ist wrongly saved.
			 * @since. 2.2.0
			 */
			if (
				_.has(props.attributes, 'greydStyles') &&
				!_.isEmpty(props.attributes.greydStyles) &&
				JSON.stringify( props.attributes.greydStyles ).indexOf( "var(--wp--preset--color--rgb(255,255,255,0))" ) > -1
			) {
				var greydStlyes = JSON.stringify( props.attributes.greydStyles )
					.split( "var(--wp--preset--color--rgb(255,255,255,0))" )
					.join( "var(--wp--preset--color--transparent)" );
				props.setAttributes( { greydStyles: JSON.parse( greydStlyes ) } );
				console.info("fixed Transparent color in `"+props.name+"` Block.");
				// console.log(props.attributes.greydStyles, "to", JSON.parse( greydStlyes ));
			}


			/**
			 * greydStyles support.
			 */
			if (_.has(block_type.attributes, "greydStyles") && props.name.indexOf('core/') === 0) {
				extend = true;
				// console.log("add greydStyles support to: "+props.name);

				// helper component to make toggleControl responsive
				const ToggleControlResponsive = class extends wp.element.Component {
					constructor() {
						super();
					}
					render() {
						return el( wp.components.ToggleControl, {
							label: this.props.label,
							checked: this.props.value,
							onChange: value => this.props.onChange( value ),
						} );
					}
				};

				// add greydStyles inspector controls
				if (props.name == "core/group") {
					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Inside", 'greyd_hub'),
									attribute: "padding",
									control: greyd.components.DimensionControl,
								},
								{
									label: __("Outside", 'greyd_hub'),
									attribute: "margin",
									control: greyd.components.DimensionControl,
									min: -300,
									max: 300,
								}
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Width", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									attribute: "width",
									label: __("Width", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "minWidth",
									label: __("Minimum width", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "maxWidth",
									label: __("Maximum width", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								}
							]
						} ),
						greyd.data.is_greyd_alpha && el( greyd.components.StylingControlPanel, {
							title: __("Height", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									attribute: "height",
									label: __("Height", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "minHeight",
									label: __("Minimum height", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "maxHeight",
									label: __("Maximum height", 'greyd_hub'),
									max: 1200,
									control: greyd.components.RangeUnitControl,
								}
							]
						} ),
					);
				}
				if (props.name == "core/columns") {
					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Inside", 'greyd_hub'),
									attribute: "padding",
									control: greyd.components.DimensionControl,
									sides: [ "top", "bottom" ],
									labels: {
										"all": __("Top/bottom", "greyd_hub")
									},
								},
								{
									label: __("Outside", 'greyd_hub'),
									attribute: "margin",
									control: greyd.components.DimensionControl,
									min: -300,
									max: 300,
									sides: [ "top", "bottom" ],
									labels: {
										"all": __("Top/bottom", "greyd_hub")
									},
								}
							]
						} ),
					);
				}
				if (props.name == "core/heading" || props.name == "core/paragraph") {

					ex.after_original_inspector_controls.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Text lines", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							controls: [ 
							// line-clamp
							{
								label: __("Text lines", 'greyd_hub'),
								attribute: "-webkit-line-clamp",
								min: 0,
								control: wp.components.__experimentalNumberControl,
							},
							{
								hidden: { 
									'': !_.has(props.attributes, 'greydStyles["-webkit-line-clamp"]') || props.attributes.greydStyles['-webkit-line-clamp'] == 0,
									lg: !_.has(props.attributes, 'greydStyles.responsive.lg["-webkit-line-clamp"]') || props.attributes.greydStyles.responsive.lg['-webkit-line-clamp'] == 0,
									md: !_.has(props.attributes, 'greydStyles.responsive.md["-webkit-line-clamp"]') || props.attributes.greydStyles.responsive.md['-webkit-line-clamp'] == 0,
									sm: !_.has(props.attributes, 'greydStyles.responsive.sm["-webkit-line-clamp"]') || props.attributes.greydStyles.responsive.sm['-webkit-line-clamp'] == 0,
								},
								label: __("Force number of text lines", 'greyd_hub'),
								attribute: "forceheight",
								control: ToggleControlResponsive,
							} ]
						} ),
					);
					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Width", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [ {
								label: __("Maximum width", 'greyd_hub'),
								attribute: "maxWidth",
								max: 1200,
								control: greyd.components.RangeUnitControl,
							} ]
						} ),
					);
					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [ {
								label: __("Space", 'greyd_hub'),
								attribute: "margin",
								min: -200,
								control: greyd.components.DimensionControl,
							} ]
						} ),
					);
				}

				if (props.name == "core/image" || props.name == "core/video") {
					
					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Width", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [ {
								label: __("Maximum width", 'greyd_hub'),
								max: 1920,
								attribute: "maxWidth",
								control: greyd.components.RangeUnitControl,
							} ]
						} )
					);
				}

				if (props.name == "core/separator") {
					const isDots = props.attributes.dots;

					ex.after_original_inspector_controls_styles.push( !isDots ? [
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Line weight", 'greyd_hub'),
									attribute: "height",
									max: 60,
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Width", 'greyd_hub'),
									attribute: "width",
									max: 1920,
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Border radius", 'greyd_hub'),
									attribute: "borderRadius",
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
									control: greyd.components.DimensionControl,
									type: "string",
									max: 100,
								},
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Opacity", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							controls: [
								{
									label: __("Opacity", 'greyd_hub'),
									attribute: "opacity",
									units: ["%"],
									control: greyd.components.RangeUnitControl,
								},
							]
						} )
					] : [
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									label: __("Size of the points", 'greyd_hub'),
									attribute: "borderBottomWidth",
									min: 1,
									max: 60,
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Width", 'greyd_hub'),
									attribute: "width",
									max: 1920,
									control: greyd.components.RangeUnitControl,
								},
							]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Opacity", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							controls: [
								{
									label: __("Opacity", 'greyd_hub'),
									attribute: "opacity",
									units: ["%"],
									control: greyd.components.RangeUnitControl,
								},
							]
						} )
					] );
				}
	
				if (props.name == "core/site-logo") {
					ex.after_original_inspector_controls.push(
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							controls: [
								{
									attribute: "width",
									label: __("Width", 'greyd_hub'),
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "minWidth",
									label: __("Minimum width", 'greyd_hub'),
									control: greyd.components.RangeUnitControl,
								},
								{
									attribute: "maxWidth",
									label: __("Maximum width", 'greyd_hub'),
									max: 1920,
									control: greyd.components.RangeUnitControl,
								}
							],
							help: __("Unfortunately, the adjustments to the width of the logo cannot be displayed in the editor. Take a look at the site preview to see the changes.", 'greyd_hub'),
						} ),
					);
				}


				// render greydStyles
				if (_.has(props.attributes, 'greydStyles') && !_.isEmpty(props.attributes.greydStyles)) {
					// console.log(props.attributes.greydStyles);
					const newGreydClass = greyd.tools.getGreydClass( props );
					if ( props.attributes?.greydClass !== newGreydClass ) {
						props.setAttributes( { greydClass: newGreydClass } );
					}
					// var styleObj = { "": { ...props.attributes.greydStyles } };
					var styleObj = { "": JSON.parse(JSON.stringify( props.attributes.greydStyles )) };

					if (props.name == "core/heading" || props.name == "core/paragraph") {
						// fix alignments for headings & paragraphs
						// console.log(props.attributes);
						var margin = _.has(props.attributes.greydStyles, 'margin') ? { ...props.attributes.greydStyles.margin } : {};
						const align = _.has(props.attributes, 'textAlign') && props.attributes.textAlign != "" ? props.attributes.textAlign : ( _.has(props.attributes, 'align') && props.attributes.align != "" ? props.attributes.align : null );
						if (align == "right") {
							margin.left = 'auto';
						}
						else if (align == "center") {
							margin.left = 'auto';
							margin.right = 'auto';
						}
						if ( !_.isEmpty(margin) ) {
							styleObj = { "": { ...props.attributes.greydStyles, margin: margin } };
						}

						// force number of lines from line-clamp
						var level = props.name == "core/heading" ? ( "H"+(_.has(props.attributes, 'level') ? props.attributes.level : 2) ) : "";
						[ '', 'lg', 'md', 'sm' ].forEach( (bp) => {
							var gs = props.attributes.greydStyles;
							if ( bp != '' ) {
								if ( !_.has(props.attributes.greydStyles, 'responsive') || !_.has(props.attributes.greydStyles.responsive, bp) ) return;
								gs = props.attributes.greydStyles.responsive[bp];
							}
							var lines = _.has(gs, '-webkit-line-clamp') ? gs['-webkit-line-clamp'] : false;
							if (lines) {
								var height = 'auto';
								if ( lines > 0 && _.has(gs, 'forceheight') && gs.forceheight === true ) {
									var lineHeight = 'var(--'+level+'lineHeight)';
									if ( !greyd.data.is_greyd_classic ) {
										if ( level == '' ) lineHeight = 'var(--wp--custom--line-height--normal)';
										else lineHeight = 'var(--wp--custom--line-height--tight)';
									}
									height = 'calc('+lineHeight+' * '+lines+' * 1em)';
								}
								// set
								if ( bp == '' ) styleObj[""]["min-height"] = height;
								else styleObj[""].responsive[bp]["min-height"] = height;
							}
						} );
					}
					
					var greydCSS = greyd.tools.composeCSS( styleObj, '', true, true );

					// fix elements without a class
					var style = "";
					var oldsel = ' . {';
					var newsel = props.name == "core/columns" ? ' #row-'+props.clientId+' {' : ' #block-'+props.clientId+' {';
					style += greydCSS.split(oldsel).join(newsel);
					// console.log(style);
					if (style != "") ex.before_original_block.push(el( 'style', { className: 'greyd_styles' }, style ));
				}

			}


			/**
			 * =================================================================
			 *                          Custom extensions (per block)
			 * =================================================================
			 */

			/**
			 * Extend the spacer.
			 */
			if ( props.name == "core/spacer" ) {
				extend = true;
				// console.log("add responsive size support to: "+props.name);
				
				/**
				 * convert deprecated em val
				 * @since WordPress 5.9
				 */
				if (_.has(props.attributes, 'responsive.height')) {
					[ 'sm', 'md', 'lg', 'xl' ].forEach(function(breakpoint) { 
						if (_.has(props.attributes.responsive.height, breakpoint) && props.attributes.responsive.height[breakpoint].indexOf('em') > -1) {
							props.attributes.responsive.height[breakpoint] = (parseFloat(props.attributes.responsive.height[breakpoint]) * 100)+"%";
						}
					});
				}

				/**
				 * check if height has unit and add 'px' if not
				 * @since 1.6.9
				 */
				if ( props.attributes?.height && parseInt(props.attributes.height) == props.attributes.height ) {
					// console.info("spacer without unit!");
					props.attributes.height += 'px';
				}

				const defaults = greyd.data.is_greyd_classic ? { height: { sm: "40%", md: "60%", lg: "80%", xl: "100%" } } : { height: { sm: "100%", md: "100%", lg: "100%", xl: "100%" } };
				const values = greyd.tools.getValues(defaults, props.attributes.responsive);

				const setValues = function(val) {
					// console.log(val);
					// make new value
					var value = greyd.tools.makeValues(defaults, val);
					// console.log(value);
					props.setAttributes( { responsive: value } );
				}

				ex.after_original_inspector_controls.push(
					el( greyd.components.AdvancedPanelBody, { 
						title: __("Scaling", 'greyd_hub'), 
						initialOpen: true,
						holdsChange: !_.isEmpty(props.attributes.responsive) 
					}, [
						el( 'div', { className: 'greyd-icon-flex flex lg' }, [
							el( greyd.components.GreydIcon, { 
								icon: 'desktop', 
								title: greyd.tools.makeBreakpointTitle("lg") } 
							),
							el( greyd.components.RangeUnitControl, {
								value: values?.height?.xl,
								units: [ '%' ],
								modes: [ 'numeric' ],
								max: { '%': 200 },
								onChange: function(value) { setValues( { height: { ...values?.height, xl: value } } ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex md' }, [
							el( greyd.components.GreydIcon, { 
								icon: 'laptop', 
								title: greyd.tools.makeBreakpointTitle("md") } 
							),
							el( greyd.components.RangeUnitControl, {
								value: values?.height?.lg,
								units: [ '%' ],
								modes: [ 'numeric' ],
								max: { '%': 200 },
								onChange: function(value) { setValues( { height: { ...values?.height, lg: value } } ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex sm' }, [
							el( greyd.components.GreydIcon, { 
								icon: 'tablet', 
								title: greyd.tools.makeBreakpointTitle("sm") } 
							),
							el( greyd.components.RangeUnitControl, {
								value: values?.height?.md,
								units: [ '%' ],
								modes: [ 'numeric' ],
								max: { '%': 200 },
								onChange: function(value) { setValues( { height: { ...values?.height, md: value } } ); },
							} ),
						] ),
						el( 'div', { className: 'greyd-icon-flex flex xs' }, [
							el( greyd.components.GreydIcon, { 
								icon: 'mobile', 
								title: greyd.tools.makeBreakpointTitle("xs") } 
							),
							el( greyd.components.RangeUnitControl, {
								value: values?.height?.sm,
								units: [ '%' ],
								modes: [ 'numeric' ],
								max: { '%': 200 },
								onChange: function(value) { setValues( { height: { ...values?.height, sm: value } } ); },
							} )
						] ),
						el( 'p', { className: "greyd-inspector-help" }, __("The base height is scaled by these values per breakpoint.", 'greyd_hub') ),
					] )
				);
				
				/**
				 * In order to visualize the scaling of the spacer, we do a little trick:
				 * 
				 * * We apply a margin-bottom to the spacer, adjusting for the difference
				 *   between the base height and the scaled height.
				 * * We apply a background to the ::before pseudo element to show the
				 *   difference.
				 * 
				 * @since 1.2.2
				 */
				const baseHeight  = props.attributes.height; // in px
				let previewStyles = { responsive: {} };
				let pseudoStyles  = { responsive: {} };

				for (const [breakpoint, value] of Object.entries(values?.height)) {

					const numVal = (greyd.tools.getNumValue(value) / 100) - 1;

					// give the spacer a margin bottom
					const marginBottom = 'calc('+baseHeight+' * '+numVal+' )';

					// style the ::before element to visualize the scaling
					const beforeStyles = {};
					beforeStyles.height = 'calc(100% * '+Math.abs(numVal)+')';
					if ( numVal < 0 ) {
						beforeStyles.top = 'auto';
						beforeStyles.bottom = '0px';
					} else {
						beforeStyles.top = '100%';
						beforeStyles.bottom = 'auto';
					}

					// apply the styles
					if ( breakpoint == 'xl' ) {
						previewStyles.marginBottom = marginBottom;
						pseudoStyles = { ...pseudoStyles, ...beforeStyles };
					} else {
						previewStyles.responsive[breakpoint] = { marginBottom: marginBottom };
						pseudoStyles.responsive[breakpoint] = beforeStyles;
					}
				}
				ex.before_original_block.push(
					el( greyd.components.RenderPreviewStyles, {
						selector: 'is-root-container #block-'+props.clientId,
						styles: {
							"": previewStyles,
							"::before": pseudoStyles,
						}
					} )
				);
			}

			/**
			 * Extend the embed wrapper.
			 */
			if (props.name == "core/embed") {
				extend = true;
				// console.log("add size support to: "+props.name);

				ex.before_original_inspector_controls.push(
					el( wp.components.PanelBody, { title: __('Design', 'greyd_hub'), initialOpen: true },
						el( wp.components.__experimentalUnitControl, {
							label: __("Width", 'greyd_hub'),
							labelPosition: 'edge',
							className: 'is-edge-layout',
							value: props.attributes.width,
							onChange: function(value) { 
								props.setAttributes( { width: value } ); 
							},
						} ),
					)
				);
				
				var style = "";
				if (_.has(props.attributes, 'width') && props.attributes.width != "") {
					var width = props.attributes.width;
					style += "#block-"+props.clientId+" { max-width: "+width+"; width: "+width+"; } ";
				}
				if (style != "") ex.before_original_block.push(el( 'style', { className: 'greyd_styles' }, style ));

			}

			/**
			 * Extend the core archives
			 */
			if (props.name == "core/archives") {
				extend = true;

				/**
				 * Add filter controls
				 */

				// post types
				const allPostTypes = [
					{ value: 'post', label: __("Posts", 'greyd_hub') },
					{ value: 'page', label: __("Pages", 'greyd_hub') },
				];

				// archive types
				const defaultTypes = [
					{ value: 'monthly', label: __("By month (default)", 'greyd_hub') },
					{ value: 'yearly', label: __("By year", 'greyd_hub') },
					{ value: 'daily', label: __("By day", 'greyd_hub') },
				];
				const customTypes = {
					post: [
						{ value: 'category', label: __("By category", 'greyd_hub') },
						{ value: 'post_tag', label: __("By tag", 'greyd_hub') },
					],
					page: []
				}

				// sorting types
				const orderTypes = {
					date: [
						{ value: 'DESC', label: __("Newest first", 'greyd_hub') },
						{ value: 'ASC', label: __("Oldest first", 'greyd_hub') },
					],
					name: [
						{ value: 'name ASC', label: __('A → Z', 'greyd_hub') },
						{ value: 'name DESC', label: __('Z → A', 'greyd_hub') },
						{ value: 'count ASC', label: __("Most posts first", 'greyd_hub') },
						{ value: 'count DESC', label: __("Fewest posts first", 'greyd_hub') },
					],
				};

				// date formats
				const dateFormats = {
					yearly: [
						{ value: '', label: __("Default format", 'greyd_hub') },
						{ value: 'Y', label: __("yyyy", 'greyd_hub') },
						{ value: 'y', label: __("yy", 'greyd_hub') },
					],
					monthly: [
						{ value: '', label: __("Default format", 'greyd_hub') },
						{ value: 'F Y', label: __("Month yyyy", 'greyd_hub') },
						{ value: 'F y', label: __("Month yy", 'greyd_hub') },
						{ value: 'F', label: __("Month", 'greyd_hub') },
						{ value: 'm.Y', label: __("MM.YYYY", 'greyd_hub') },
						{ value: 'm.y', label: __("MM.YY", 'greyd_hub') },
						{ value: 'm', label: __("mm", 'greyd_hub') },
					],
					daily: [
						{ value: '', label: __("Default format", 'greyd_hub') },
						{ value: 'd.m.Y', label: __("dd.mm.yyyy", 'greyd_hub') },
						{ value: 'd.m.y', label: __("dd.mm.yy", 'greyd_hub') },
						{ value: 'd.m', label: __("dd.mm", 'greyd_hub') },
						{ value: 'l d.m.Y', label: __("Day dd.mm.yyyy", 'greyd_hub') },
						{ value: 'l d.m.y', label: __("Day dd.mm.yy", 'greyd_hub') },
						{ value: 'l d.m', label: __("Day dd.mm", 'greyd_hub') },
						{ value: 'j. F Y', label: __("d. Month yyyy", 'greyd_hub') },
						{ value: 'j. F y', label: __("d. Month yy", 'greyd_hub') },
						{ value: 'j. F', label: __("d. Month", 'greyd_hub') },
						{ value: 'l j. F Y', label: __("Day d. Month yyyy", 'greyd_hub') },
						{ value: 'l j. F y', label: __("Day d. Month yy", 'greyd_hub') },
						{ value: 'l j. F', label: __("Day d. Month", 'greyd_hub') },
						{ value: 'G:i', label: __("Time (h:i)", 'greyd_hub') },
						{ value: 'H:i:s', label: __("Time with seconds (H:i:s)", 'greyd_hub') },
						{ value: 'G', label: __("Hours", 'greyd_hub') },
						{ value: 'm', label: __("Minutes", 'greyd_hub') },
						{ value: 's', label: __("Seconds", 'greyd_hub') }
					],
				};

				// get all post type data
				greyd.data.post_types.forEach(postType => {

					// post types
					if ( postType.slug != 'post' && postType.slug != 'page' ) {
						allPostTypes.push( {
							label: postType.plural,
							value: postType.slug
						} );
					}

					// archive types
					const archiveTypes = [];
					if ( _.has(postType, 'categories') ) {
						archiveTypes.push( {
							label: __("By category", 'greyd_hub'),
							value: postType.slug + '_category'
						} );
					}
					if ( _.has(postType, 'tags') ) {
						archiveTypes.push( {
							label: __("By tag", 'greyd_hub'),
							value: postType.slug + '_tags'
						} );
					}
					if ( _.has(postType, 'custom_taxonomies') && Array.isArray(postType.custom_taxonomies) ) {
						postType.custom_taxonomies.forEach(taxonomy => {
							archiveTypes.push( {
								label: __("By %s", 'greyd_hub').replace('%s', taxonomy.plural),
								value: postType.slug + '_' + taxonomy.slug
							} );
						});
					}

					customTypes[postType.slug] = archiveTypes;
				});

				// get props
				const defaultFilter = block_type.attributes.filter.default;
				const { post_type, type, order, hierarchical, date_format } = { ...defaultFilter, ...props.attributes.filter };
				const orderType = type == 'yearly' || type == 'monthly' || type == 'daily' ? 'date' : 'name';

				// build controls
				ex.after_original_inspector_controls.push(
					el( wp.components.PanelBody, { title: __("Filter and Sorting", 'greyd_hub'), initialOpen: true }, [
						el( wp.components.SelectControl, {
							label: __("Post type", 'greyd_hub'),
							value: post_type,
							options: allPostTypes,
							onChange: function(value) { props.setAttributes( { filter: { ...defaultFilter, post_type: value } } ); },
						} ),
						el( wp.components.SelectControl, {
							label: __("Archive type", 'greyd_hub'),
							value: type,
							options: [
								...defaultTypes,
								...( _.has(customTypes, post_type) ? customTypes[post_type] : [])
							],
							onChange: function(value) { props.setAttributes( { filter: { ...defaultFilter, post_type: post_type, type: value } } ); },
						} ),
						el( wp.components.SelectControl, {
							label: __("Order", 'greyd_hub'),
							value: order,
							options: orderTypes[orderType],
							onChange: function(value) { props.setAttributes( { filter: { ...props.attributes.filter, order: value } } ); },
						} ),
						(
							orderType === 'name'
							? el( wp.components.ToggleControl, {
								label: __("Show subcategories", 'greyd_hub'),
								checked: hierarchical,
								onChange: function(value) { props.setAttributes( { filter: { ...props.attributes.filter, hierarchical: value } } ); },
							} )
							: el( wp.components.SelectControl, {
								label: __("Date format", 'greyd_hub'),
								value: date_format,
								options: dateFormats[type],
								onChange: function(value) { props.setAttributes( { filter: { ...props.attributes.filter, date_format: value } } ); },
							} )
						)
					] )
				);

				/**
				 * Add styling controls
				 */
				// get props
				const defaultStyles = block_type.attributes.styles.default;
				const { style, size, custom, icon } = { ...defaultStyles, ...props.attributes.styles };
				const displayAsDropdown = props.attributes.displayAsDropdown;

				styleOptions = displayAsDropdown ? [
					{ value: '', label: __("Primary input field", 'greyd_hub') },
					{ value: 'sec', label: __("Secondary input field", 'greyd_hub') },
				] : [
					{ label: __("Links", 'greyd_hub'), options: [
						{ value: '', label: __("Primary links", 'greyd_hub') },
						{ value: 'sec', label: __("Secondary links", 'greyd_hub') },
					] },
					{ label: __('Buttons', 'greyd_hub'), options: [
						{ value: 'button', label: __("Primary buttons", 'greyd_hub') },
						{ value: 'button sec', label: __("Secondary buttons", 'greyd_hub') },
						{ value: 'button trd', label: __("Alternative buttons", 'greyd_hub') },
					] },
				];

				// build controls
				ex.after_original_inspector_controls.push(
					// icon
					displayAsDropdown ? null : el( greyd.components.ButtonIconControl, {
						value: icon,
						onChange: function(value) { props.setAttributes( { styles: { ...props.attributes.styles, icon: value } } ); },
					} ),
				);
				ex.after_original_inspector_controls_styles.push(
					// design panel
					el( wp.components.PanelBody, { title: __('Design', 'greyd_hub'), initialOpen: true }, [
						el( greyd.components.OptionsControl, {
							label: __("Display as", 'greyd_hub'),
							value: style,
							options: styleOptions,
							onChange: function(value) { props.setAttributes( { styles: { ...props.attributes.styles, style: value } } ); },
						} ),
						(
							displayAsDropdown || style.indexOf('button') < 0 ? null : 
							el( greyd.components.ButtonGroupControl, {
								label: __("Size", 'greyd_hub'),
								value: size,
								style: { marginBottom: '14px' },
								options: [
									{ value: "small", label: __( "Small", 'greyd_hub' ) },
									{ value: "", label: __( "Default", 'greyd_hub' ) },
									{ value: "big", label: __( "Big", 'greyd_hub' ) },
								],
								onChange: function(value) { props.setAttributes( { styles: { ...props.attributes.styles, size: value } } ); },
							} )
						),
						el( wp.components.ToggleControl, {
							label: __( "Overwrite design individually", 'greyd_hub' ),
							checked: custom,
							onChange: function(value) { props.setAttributes( { styles: { ...props.attributes.styles, custom: value } } ); },
						} )
					] )
				);

				// custom styles
				if ( props.attributes.styles.custom ) {

					props.attributes.greydClass = greyd.tools.getGreydClass(props)

					ex.after_original_inspector_controls_styles.push(
						el( greyd.components.CustomButtonStyles, {
							blockProps: props,
							parentAttr: 'customStyles'
						} )
					);
					ex.before_original_block.push(
						el( greyd.components.RenderPreviewStyles, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.customStyles,
							},
							important: true
						} )
					);
				}

			}

			/**
			 * Extend social links.
			 * 
			 * @deprecated
			 */
			if (props.name == "core/social-links") {

				if (_.has(props.attributes, 'align')) {
					// convert align to layout
					if (props.attributes.align == 'left') {
						props.attributes.layout = { type: "flex", justifyContent: "left" };
					}
					else if (props.attributes.align == 'center') {
						props.attributes.layout = { type: "flex", justifyContent: "center" };
					}
					else if (props.attributes.align == 'right') {
						props.attributes.layout = { type: "flex", justifyContent: "right" };
					}
					delete props.attributes.align;
				}

			}

			/**
			 * Extend the separator.
			 */
			if (props.name == "core/separator") {
				extend = true;
			
				ex.before_original_inspector_controls.push(
					el( wp.components.PanelBody, {title: __("General", 'greyd_hub'), initialOpen: true }, [
						// Pflichtfeld
						el( wp.components.ToggleControl, {
							label: __( "Display as dots", 'greyd_hub' ),
							checked: props.attributes.dots,
							onChange: (value) => {
								var dotStyles = {};
								var greydStyles = _.has(props.attributes, "greydStyles") ? props.attributes.greydStyles : {};

								if (value) {
									dotStyles =  { borderStyle: "dotted", borderColor: "", background: "none" };
									if (!_.has(greydStyles, "borderBottomWidth") || greydStyles.borderBottomWidth === "") greydStyles.borderBottomWidth = "1px"
									if (_.has(greydStyles, "height")) 		delete greydStyles.height;
									if (_.has(greydStyles, "borderRadius")) delete greydStyles.borderRadius;
								}
								else {
									if (_.has(greydStyles, "borderStyle")) delete greydStyles.borderStyle;
									if (_.has(greydStyles, "borderColor")) delete greydStyles.borderColor;
									if (_.has(greydStyles, "borderBottomWidth")) delete greydStyles.borderBottomWidth;
									if (_.has(greydStyles, "borderStyle")) delete greydStyles.Style;
									if (_.has(greydStyles, "background")) 	delete greydStyles.background;
								}

								props.setAttributes({ 
									greydStyles: {...greydStyles, ...dotStyles},
									dots: value 
								} )
							}
						} ),
					] ),
				);
			}

			/**
			 * Extend video.
			 */
			if (props.name == "core/video") {
				extend = true;
					
				// subscribe to viewport change
				var [viewport, setViewport ] = wp.element.useState( greyd.tools.getDeviceType() );
				var unsubscribeViewport = wp.data.subscribe(() => {
					// compare values
					var newViewport = greyd.tools.getDeviceType();
					if (viewport !== newViewport) {
						// console.log("viewport changed to "+newViewport);
						// set new viewport
						setViewport(newViewport);
						// reset subscription
						unsubscribeViewport();
					}
				});

				// check if mobile video should be shown in current viewport
				var isMobileVideo = function() {
					if ( _.has(props.attributes.mobile, 'id') && props.attributes.mobile.id != -1) {
						var bp = props.attributes.mobile.breakpoint;
						if (viewport == "Mobile" && bp == 'sm') {
							// mobile video preview only on mobile
							return true;
						}
						if (viewport == "Tablet" && (bp == 'sm' || bp == 'md')) {
							// mobile video preview on mobile and tablet
							return true;
						}
						if (viewport == "Desktop") {
							// no distinct preview for bigger screens
							// show normal video
							return false;
						}
					}
					return false;
				}

				// keep primary video in state
				var [video, setVideo ] = wp.element.useState( { id: props.attributes.id, src: props.attributes.src } );
				// if it changes via toolbar in mobile preview, change the mobile video instead
				if ( video.id != props.attributes.id ) {
					if (isMobileVideo()) {
						// console.log("mobile video changed!");
						// reset primary video and change mobile video
						props.setAttributes( {
							id: video.id,
							src: video.src,
							mobile: {
								...props.attributes.mobile,
								id: props.attributes.id,
								url: props.attributes.src
							}
						} );
					}
					else setVideo( { id: props.attributes.id, src: props.attributes.src } );
				}

				// 
				// mobile video preview
				if (isMobileVideo()) {
					// show block with different video
					ex.original_block = el( BlockEdit, { ...props, attributes: { ...props.attributes, 
						id: _.has(props.attributes.mobile, 'id') ? props.attributes.mobile.id : -1,
						src: props.attributes.mobile.url 
					} } );
				}
				
				// 
				// sidebar for mobile video
				ex.after_original_inspector_controls.unshift(
					el( greyd.components.AdvancedPanelBody, {
						title: __("Mobile video", 'greyd_hub'),
						initialOpen: isMobileVideo(), // false,
						holdsChange: _.has(props.attributes.mobile, 'id') && props.attributes.mobile.id != -1
					}, [

						el( wp.components.BaseControl, {}, [
							el( wp.blockEditor.MediaUploadCheck, { 
								fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the video, you must be authorized to upload media.", 'greyd_hub') )
							}, [
								el( wp.blockEditor.MediaUpload, {
									allowedTypes: 'video/*',
									value: _.has(props.attributes.mobile, 'id') ? props.attributes.mobile.id : -1,
									onSelect: function(value) { 
										props.setAttributes( { mobile: { ...props.attributes.mobile, id: value.id, url: value.url } } );
									},
									render: function(obj) {
										return el( wp.components.Button, {
											className: !_.has(props.attributes.mobile, 'id') || props.attributes.mobile.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
											onClick: obj.open
										}, _.has(props.attributes.mobile, 'id') && props.attributes.mobile.id == -1 ? __( "Select video", 'greyd_hub' ) : el( 'video', { 
											title: props.attributes.mobile.url, 
											src: props.attributes.mobile.url 
										} ) )
									},
								} ),
								_.has(props.attributes.mobile, 'id') && props.attributes.mobile.id !== -1 ? el( wp.components.Button, {
									className: "is-link is-destructive",
									onClick: function() {
										props.setAttributes( { mobile: { ...props.attributes.mobile, id: -1, url: "" } } );
									},
								}, __( "Remove video", 'greyd_hub' ) ) : ""
							] )
						] ),
						el( greyd.components.ButtonGroupControl, {
							label: __('Breakpoint', 'greyd_hub'),
							help: __("The mobile video is used up to the selected breakpoint. On larger screens, the main video is displayed.", 'greyd_hub'),
							value: _.has(props.attributes.mobile, 'breakpoint') ? props.attributes.mobile.breakpoint : '',
							options: [
								{ value: 'sm', icon: el( greyd.components.GreydIcon, {
									icon: 'mobile',
									title: greyd.tools.makeBreakpointTitle("xs") }
								) },
								{ value: 'md', icon: el( greyd.components.GreydIcon, {
									icon: 'tablet',
									title: greyd.tools.makeBreakpointTitle("sm") }
								) },
								{ value: 'lg', icon: el( greyd.components.GreydIcon, {
									icon: 'laptop',
									title: greyd.tools.makeBreakpointTitle("md") }
								) },
								{ value: 'xl', icon: el( greyd.components.GreydIcon, {
									icon: 'desktop',
									title: greyd.tools.makeBreakpointTitle("lg") }
								) },
							],
							onChange: function(value) { 
								props.setAttributes( { mobile: { ...props.attributes.mobile, breakpoint: value } } );
							},
						} ),

					] )
				);

			}

		
			/**
			 * Render the extensions.
			 */
			if (extend) {
				// console.log("extending supports for: "+props.name);
				// console.log(ex);
				if (ex.before_original_block_controls.length > 0) {
					ex.before_original_block_controls = el( wp.blockEditor.BlockControls, {}, ex.before_original_block_controls);
				}
				if (ex.before_original_inspector_controls.length > 0) {
					ex.before_original_inspector_controls = el( wp.blockEditor.InspectorControls, {}, ex.before_original_inspector_controls);
				}
				if (ex.before_original_inspector_controls_styles.length > 0) {
					ex.before_original_inspector_controls_styles = el( wp.blockEditor.InspectorControls, { group: 'styles' }, ex.before_original_inspector_controls_styles);
				}
				if (ex.before_original_advanced_controls.length > 0) {
					ex.before_original_advanced_controls = el( wp.blockEditor.InspectorAdvancedControls, {}, ex.before_original_advanced_controls);
				}

				if (ex.after_original_block_controls.length > 0) {
					ex.after_original_block_controls = el( wp.blockEditor.BlockControls, { group: 'inline' }, ex.after_original_block_controls);
				}
				if (ex.after_original_inspector_controls.length > 0) {
					ex.after_original_inspector_controls = el( wp.blockEditor.InspectorControls, {}, ex.after_original_inspector_controls);
				}
				if (ex.after_original_inspector_controls_styles.length > 0) {
					ex.after_original_inspector_controls_styles = el( wp.blockEditor.InspectorControls, { group: 'styles' }, ex.after_original_inspector_controls_styles);
				}
				if (ex.after_original_advanced_controls.length > 0) {
					ex.after_original_advanced_controls = el( wp.blockEditor.InspectorAdvancedControls, {}, ex.after_original_advanced_controls );
				}

				ex.original_block = el( BlockEdit, { ...props } );

				return el( wp.element.Fragment, {}, [
					ex.before_original_block_controls, 
					ex.after_original_block_controls, 
					ex.before_original_inspector_controls, 
					ex.before_original_inspector_controls_styles, 
					ex.before_original_advanced_controls, 
					ex.before_original_block,
					ex.original_block,
					ex.after_original_block, 
					ex.after_original_inspector_controls,
					ex.after_original_inspector_controls_styles,
					ex.after_original_advanced_controls,
				] );
			}
			else return ex.original_block;
		};
	}, 'editBlockHook' );

	// hook save block
	var saveBlockHook = function(props, name, atts) {
		// console.log(name);
		
		/**
		 * inline_css_id deprecated
		 * @since 1.7.0
		 */
		// // https://stackoverflow.com/questions/51166133/how-to-add-more-advanced-fields-in-each-gutenberg-blocks
		// if (_.has(atts, 'inline_css_id') && atts['inline_css_id'] != "") {
		// 	var css_id = (_.has(atts, 'anchor') && atts['anchor'] != "") ? atts['anchor'] : atts['inline_css_id'];
		// 	if (props.id != css_id) assign(props, { id: css_id });
		// }
		if ( greyd.data.is_greyd_classic ) {
			if (_.has(name, 'supports.color.gradients') && _.has(atts, 'style.color.gradient') && atts.style.color.gradient != "") {
				// console.log(name);
				// console.log(atts);
				// save gradient with 'var(--colorXX)' colors
				var gradient = atts.style.color.gradient;
				greyd.data.colors.forEach(function(color, i) {
					var slug = 'var(--'+color.slug.replace("-", "")+')';
					gradient = gradient.split(color.color).join(slug);
					gradient = gradient.split(greyd.tools.hex2rgbString(color.color)).join(slug);
				});
				props.style.background = gradient;
			}
		}
		/**
		 * Quickfix: buggy block deprecation since gutenberg 14.7.0 (v5)
		 * @deprecated since 2.4.0 to prevent block editor errors
		 */
		// if (name.name == 'core/heading') {
		// 	// console.log(atts);
		// 	// console.log(props);
		// 	if (_.has(props, 'className') && props.className) {
		// 		props.className = props.className.replace('wp-block-heading ', '');
		// 	}
		// }

		return props;
	};

	// hooks
	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook',
		registerBlockTypeHook
	);
	wp.hooks.addFilter( 
		'editor.BlockEdit', 
		'greyd/hook/edit', 
		editBlockHook 
	);
	wp.hooks.addFilter(
		'blocks.getSaveContent.extraProps',
		'greyd/hook/save',
		saveBlockHook
	);

	var editBlockListHook = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			// console.log(BlockListBlock);
			// console.log(props);
			if (props.name == "core/paragraph") {
				// delete props.attributes.dropCap;
			}

			/**
			 * if block is not valid and has a 'content' attribute,
			 * try to recover it by stripping all 'p' tags from the originalContent.
			 */
			if (props.block.isValid === false && _.has(props.attributes, "content") && _.has(props.block, "originalContent")) {
				if (props.block.originalContent.indexOf('<p') > -1) {
					// console.log(props);
					// strip all 'p' tags
					props.block.attributes.content = props.block.originalContent.replace(/<\/?p[^>]*>/gi, '');
					// make new block and replace in editor
					var newBlock = wp.blocks.createBlock( props.name, props.block.attributes, [] );
					wp.data.dispatch( 'core/block-editor' ).replaceBlock( props.clientId, newBlock );
					// render old block as valid while it is being replaced
					props.isValid = true;
					// log info
					console.info("Block `"+props.name+"` updated\n", "new content:\n", props.block.attributes.content);
				}
			}

			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/list', 
		editBlockListHook 
	);
	


	/**
	 * Hover Helper
	 * 
	 * @source https://unfoldingneurons.com/2019/fantastic-hooks-and-where-to-use-them
	 */
	wp.plugins.registerPlugin( 'greyd-helper', {
		render: function() {

			var isLoading = true;

			// get user
			var user = wp.data.useSelect( function(select) {
				return select( 'core' ).getCurrentUser();
			}, [] );

			// get user meta
			var usermeta = wp.data.useSelect((select) => {
				return select( 'core' ).getEntityRecord(
					'root', 'user', user.id
				);
			}, [ user ] );

			// loading state
			if ( user ) {
				isLoading = wp.data.useSelect((select) => {
					return select( 'core/data' ).isResolving('core', 'getEntityRecord', [
						'root', 'user', user.id
					]);
				}, [ user ] );
			}

			// saving state
			const [ isSaving, setSaving ] = wp.element.useState( false );

			const hoverHelper = () => {
				
				var [ hoverHelper, setHoverHelper ] = wp.element.useState( false );
				var [ hoverHelperColors, setHoverHelperColors ] = wp.element.useState( false );
				var [ editor, setEditor ] = wp.element.useState( false );

				// get editor
				if ( !editor && !wp.editSite ) {
					// console.log("get editor");
					var editorWrapper = document.querySelector( ".editor-styles-wrapper" );
					if ( !editorWrapper ) {
						var iframe = document.querySelector( "iframe[name=editor-canvas]" );
						if ( iframe ) {
							editorWrapper = iframe.contentWindow.document.querySelector( '.editor-styles-wrapper' );
						}
					}
					if ( editorWrapper ) {
						var editorContainer = editorWrapper.querySelector('.is-root-container');
						if ( editorContainer ) {
							setEditor( editorContainer );
						}
					}
				}
				// console.log(editor);

				if ( !editor ) {
					return [
						el( wp.components.Tip, {}, __(
							'This function is not available in this area.',
							'greyd_hub'
						) )
					];
				}

				if ( isLoading ) {
					return el( wp.components.Spinner, {} );
				}

				if ( usermeta ) {
					// addClass
					if ( usermeta.greyd_user_settings.hover_helper ) {
						editor.classList.add('hover_helper');
					}
					if ( usermeta.greyd_user_settings.hover_helper_colors ) {
						editor.classList.add('hover_helper_colors');
					}
				}

				return [
					// 'Hover Helper' 
					el( wp.components.ToggleControl, {
						label: __("Show borders", 'greyd_hub'),
						help: __( "Narrow lines are displayed on hover around some blocks to improve the overview.", 'greyd_hub' ),
						checked: usermeta ? usermeta.greyd_user_settings.hover_helper : hoverHelper,
						onChange: (value) => {

							// console.log("toggle hover helper");
							if ( value ) {
								editor.classList.add('hover_helper');
							} else {
								editor.classList.remove('hover_helper');
							}

							// save user meta
							if ( usermeta ) {
								usermeta.greyd_user_settings.hover_helper = value;
								if ( !isSaving ) {
									setSaving(true);
									wp.data.dispatch( 'core' ).saveEntityRecord( 'root', 'user', usermeta ).then( function(data) {
										// console.log("then");
										// console.log(data);
									} ).catch( function(error) {
										console.error(error);
									} ).finally( function() {
										// console.log("finally");
										setSaving(false);
									} );
								}
							} else {
								setHoverHelper( value );
							}
						},
					} ),
					el( wp.components.ToggleControl, {
						label: __("Show colored lines", 'greyd_hub'),
						help: __( "Colored auxiliary lines are displayed on hover.", 'greyd_hub' ),
						checked: usermeta ? usermeta.greyd_user_settings.hover_helper_colors : hoverHelperColors,
						onChange: (value) => {

							// console.log("toggle hover helper colors");
							if ( value ) {
								editor.classList.add('hover_helper_colors');
							} else {
								editor.classList.remove('hover_helper_colors');
							}

							// save user meta
							if ( usermeta ) {
								usermeta.greyd_user_settings.hover_helper_colors = value;
								if (!isSaving) {
									setSaving(true);
									wp.data.dispatch( 'core' ).saveEntityRecord( 'root', 'user', usermeta ).then( function(data) {
										// console.log("then");
										// console.log(data);
									} ).catch( function(error) {
										console.log("error");
										console.log(error);
									} ).finally( function() {
										// console.log("finally");
										setSaving(false);
									} );
								}
							} else {
								setHoverHelperColors( value );
							}
						},
					} ),
					// // debug button
					// el( wp.components.Button, {
					// 	className: 'is-secondary is-small',
					// 	style: { marginBottom: "8px" },
					// 	onClick: function() { 
					// 		// window.open(state.href, '_blank');
					// 		console.log("doing something ...");
					// 	},
					// }, el( 'span', {}, __('test', 'greyd_hub') ) ),
					// el( 'pre', {}, JSON.stringify(usermeta.greyd_user_settings, null, 4) ),
				];
			};

			/**
			 * Automatic Recursive Block Recovery with just a button click
			 * 
			 * @source https://wpstackable.com/blog/how-to-recover-all-broken-blocks-in-one-command-in-wordpress/
			 */
			const autoBlockRecover = () => {
				const recursivelyRecoverInvalidBlockList = (blocks) => {
					const _blocks = [...blocks]
					let recoveryCalled = false
					const recursivelyRecoverBlocks = willRecoverBlocks => {
						willRecoverBlocks.forEach( _block => {
							recoveryCalled = true
							const newBlock = recoverBlock( _block )
							for ( const key in newBlock ) {
								_block[key] = newBlock[key]
							}
		
							if ( _block.innerBlocks.length ) {
								recursivelyRecoverBlocks( _block.innerBlocks )
							}
						} )
					}
				
					recursivelyRecoverBlocks( _blocks )
					return [_blocks, recoveryCalled]
				}
				
				const recoverBlock = ( { name, attributes, innerBlocks } ) =>
					wp.blocks.createBlock( name, attributes, innerBlocks );
				
				const recoverBlocks = blocks => {
					return blocks.map( _block => {
						const block = _block

						if ( _block.name === 'core/block' ) {
							const { attributes: { ref } } = _block
							const parsedBlocks = wp.blocks.parse( wp.data.select( 'core' ).getEntityRecords( 'postType', 'wp_block', { include: [ref] } )?.[0]?.content?.raw ) || []
				
							const [recoveredBlocks, recoveryCalled] = recursivelyRecoverInvalidBlockList( parsedBlocks )
				
							if ( recoveryCalled ) {
								console.log( sprintf( __('Block %s was successfully restored.', 'small', 'greyd_hub'), block.name + ' (' + block.clientId + ')') );
								return {
									blocks: recoveredBlocks,
									isReusable: true,
									ref,
								}
							}
						}
				
						if ( block.innerBlocks && block.innerBlocks.length ) {
							const newInnerBlocks = recoverBlocks( block.innerBlocks )
							if ( newInnerBlocks.some( block => block.recovered ) ) {
								block.innerBlocks = newInnerBlocks
								block.replacedClientId = block.clientId
								block.recovered = true
							}
						}
				
						if ( !block.isValid ) {
							const newBlock = recoverBlock( block )
							newBlock.replacedClientId = block.clientId
							newBlock.recovered = true
							console.log( sprintf( __('Block %s wurde erfolgreich wiederhergestellt.', 'small', 'greyd_hub'), block.name + ' (' + block.clientId + ')'  ) );
							
							return newBlock
						}
				
						return block
					} )
				}

				return el( wp.components.Button, {
					className: "is-primary",
					onClick: function() {  
						// Recover all the blocks that we can find.
						const mainBlocks = recoverBlocks( wp.data.select( 'core/editor' ).getEditorBlocks() )
						// Replace the recovered blocks with the new ones.
						mainBlocks.forEach( block => {
							if ( block.isReusable && block.ref ) {
								// Update the reusable blocks.
								wp.data.dispatch( 'core' ).editEntityRecord( 'postType', 'wp_block', block.ref, { content: wp.blocks.serialize( block.blocks ) } )
							}
						
							if ( block.recovered && block.replacedClientId ) {
								wp.data.dispatch( 'core/block-editor' ).replaceBlock( block.replacedClientId, block )
							}
						} )
					},
				}, __( "Restore blocks automatically", 'greyd_hub' ) )
			};

			/**
			 * Customize the preview (for templates, forms etc...)
			 */
			if ( greyd.data.post_type !== 'page' && !disablePreviewHelper ) {

				const meta = wp.data.useSelect( function(select) {
					return select('core/editor').getEditedPostAttribute('meta');
				}, [] );

				if (typeof meta !== 'undefined' && meta['greyd_block_editor_preview']) {

					const defaultOptions = wp.hooks.applyFilters(
						'greyd.previewDefault',
						{
							enabled: false,
							maxWidth: "",
							backgroundColor: "",
						},
						greyd.data.post_id,
						greyd.data.post_type
					);
					const viewOptions = {
						...defaultOptions,
						...meta['greyd_block_editor_preview']
					}

					// update editor
					if ( viewOptions.enabled ) {
						$('.edit-post-visual-editor').addClass('greyd_block_editor_preview');
						document.documentElement.style.setProperty('--previewMaxWidth', viewOptions.maxWidth);
						document.documentElement.style.setProperty('--previewBackgroundColor', viewOptions.backgroundColor);
					} else {
						$('.edit-post-visual-editor').removeClass('greyd_block_editor_preview');
					}
	
					const changedCallback = function( name, value) {
						viewOptions[name] = value;
						wp.data.dispatch('core/editor').editPost( {
							meta: {
								'greyd_block_editor_preview': viewOptions
							}
						} );
					};
					
					var viewControl = function() {

						if ( ! $('.editor-styles-wrapper').length ) {
							return [
								el( wp.components.Tip, { }, __(
									'This function is not available in this area.',
									'greyd_hub'
								) )
							];
						}

						return [
							el( wp.components.ToggleControl, {
								label: __("Customize preview window", 'greyd_hub'),
								help: __( "Adjust width and background for this post", 'greyd_hub' ),
								checked: viewOptions.enabled,
								onChange: function(value) { 
									changedCallback('enabled', value);
								},
							} ),
							(
								viewOptions.enabled ? [
									el( greyd.components.RangeUnitControl, {
										label: __("Maximum width", 'greyd_hub'),
										units: [ 'px' ],
										value: viewOptions.maxWidth,
										min: 200,
										max: 1200,
										onChange: function(value) {
											changedCallback('maxWidth', value);
										}
									} ),
									el( greyd.components.ColorGradientPopupControl, {
										className: 'single',
										mode: 'color',
										label: __("Background color", "greyd_hub"),
										value: viewOptions.backgroundColor,
										onChange: function(value) {
											changedCallback('backgroundColor', greyd.tools.convertVarToColor( value ) );
										},
									} )
								] : null
							)
	
						];
					};
				}
			}

			/**
			 * Render
			 */
			return el( wp.editor?.PluginSidebar ?? wp.editPost.PluginSidebar, {
				name: 'greyd-helper',
				icon: el( wp.components.Icon, { icon: 'visibility'} ),
				title: __('Greyd Editor Helper', 'greyd_hub')
			}, [
				el( wp.components.PanelBody, { title: __('Hover helper', 'greyd_hub'), initialOpen: true },
					hoverHelper()
				),
				typeof viewControl !== 'undefined' && el( wp.components.PanelBody, {
					title: __("Customize preview", 'greyd_hub'),
					initialOpen: true
				}, viewControl() ),

				el( wp.components.PanelBody, { title: __("Restore blocks", 'greyd_hub'), initialOpen: true },
					autoBlockRecover()
				),
	
			] );
		},
	} );

} )( window.wp );