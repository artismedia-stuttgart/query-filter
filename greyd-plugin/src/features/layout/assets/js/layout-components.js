/**
 * Greyd Block Editor Components for Layout Blocks.
 *
 * This file is loaded in the editor.
 */

greyd.components.layout = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	this.HideBlockControl = class extends wp.element.Component {
		constructor() {
			super();
		}
		render() {

			var values = { xs: false, sm: false, md: false, lg: false, ...(this.props.value?? {}) };

			return el( wp.components.BaseControl, {
				label: this.props.label ? this.props.label : null,
				help: this.props.help ? this.props.help : null,
			}, [
				// controls
				el( 'div', { className: 'greyd-inspector-wrapper greyd-icons-inline' }, [
					el( 'div', { className: 'greyd-icon-flex xs' }, [
						el( greyd.components.GreydIcon, {
							icon: 'mobile',
							title: greyd.tools.makeBreakpointTitle("xs") }
						),
						el( wp.components.ToggleControl, {
							checked: values.xs,
							onChange: (value) => { 
								this.props.onChange( { ...values, xs: value } );
							},
						} ),
					] ),
					el( 'div', { className: 'greyd-icon-flex sm' }, [
						el( greyd.components.GreydIcon, {
							icon: 'tablet',
							title: greyd.tools.makeBreakpointTitle("sm") }
						),
						el( wp.components.ToggleControl, {
							checked: values.sm,
							onChange: (value) => { 
								this.props.onChange( { ...values, sm: value } );
							},
						} ),
					] ),
					el( 'div', { className: 'greyd-icon-flex md' }, [
						el( greyd.components.GreydIcon, {
							icon: 'laptop',
							title: greyd.tools.makeBreakpointTitle("md") }
						),
						el( wp.components.ToggleControl, {
							checked: values.md,
							onChange: (value) => { 
								this.props.onChange( { ...values, md: value } );
							},
						} ),
					] ),
					el( 'div', { className: 'greyd-icon-flex lg' }, [
						el( greyd.components.GreydIcon, {
							icon: 'desktop',
							title: greyd.tools.makeBreakpointTitle("lg") }
						),
						el( wp.components.ToggleControl, {
							checked: values.lg,
							onChange: (value) => {
								this.props.onChange( { ...values, lg: value } );
							},
						} ),
					] ),
				] )
			] );
		}
	};

	this.GridControl = class extends wp.element.Component {

		constructor() {
			super();
		}

		render() {
			const {
				label = '',
				min = 1,
				max = 12,
				step = 1,
				defaultValue = '',
				onChange = () => {},
				options = [
					{
						label: __("Inherit ↓", 'greyd_hub'),
						value: ''
					},
					{
						label: __("Automatically", 'greyd_hub'),
						value: 'auto'
					}
				]
			} = this.props;

			const value = this.props.value.replace(/(col|offset)-([a-z]{2}-)?/, '');

			// get slider steps
			const count = max - min;
			let steps = [];
			for ( let i=0; i<=count; i++ ) {

				if ( i % step ) continue;

				const num = min + i;
				const isActive = !isNaN(num) && num <= value;
				steps.push( el( 'span', {
					className: 'greyd-grid-control--slider-thumb' + ( isActive ? ' is-active' : '' ),
					title: num,
				} ) )
			}

			return el( 'div', {
				className: 'greyd-grid-control'
			}, [

				( _.isEmpty(label) ? null : el( greyd.components.AdvancedLabel, {
					label: label + ( value === defaultValue ? '' : ': ' + value ),
					currentValue: value,
					initialValue: defaultValue,
					onClear: () => onChange( '' )
				} ) ),

				el( 'div', {
					className: 'greyd-grid-control--slider'
				}, [
					...steps,
					el( 'input', {
						type: 'range',
						min: min * step,
						max: max,
						step: step,
						value: isNaN(value) ? 0 : value,
						onChange: () => onChange( event.target.value )
					} )
				] ),

				(
					options.length
					? el( 'div', {
						className: 'greyd-grid-control--buttons'
					}, [
						...options.map( (option) => {
							return el( wp.components.Button, {
								className: option.value === value ? 'is-active' : '',
								onClick: () => onChange( option.value )
							}, option.label )
						} )
					] )
					: null
				)


			] )
		}
	}

	//
	// background controls
	this.backgroundInspectorControls = function(props, sets={}) {

		// vars
		if (!_.has(sets, 'overlay')) sets.overlay = true;
		if (!_.has(sets, 'pattern')) sets.pattern = true;
		if (!_.has(sets, 'seperator')) sets.seperator = true;
		var values = greyd.tools.layout.getBackgroundDefaults(sets);
		if (_.has(props.attributes, "background")) {
			values = greyd.tools.getValues(values, props.attributes.background);
		}

		// console.log(values);
		var [parts, setParts ] = wp.element.useState(values);
		// console.log(parts);

		var setBackground = function(val) {
			// console.log(val);
			if (_.has(val.background, 'type')) parts.background = val.background;
			if (_.has(val.background, 'overlay')) parts.overlay = val.background.overlay;
			if (_.has(val.background, 'pattern')) parts.pattern = val.background.pattern;
			if (_.has(val.background, 'seperator')) parts.seperator = val.background.seperator;
			setParts(parts);
			// make background value
			var background = greyd.tools.makeValues(greyd.tools.layout.getBackgroundDefaults(sets), parts);
			// cleaning
			background = greyd.tools.layout.cleanBackgroundvalues(background);
			// console.log(background);
			props.setAttributes( { background: background } );
		}

		return [
			el( greyd.components.AdvancedPanelBody, {
				title: __( "Background element", 'greyd_hub' ),
				initialOpen: false,
				holdsChange: parts.background.type != ""
			}, [
				greyd.components.layout.backgroundControls( {
					clientId: props.clientId,
					attributes: { background: parts.background },
					setAttributes: setBackground
				} )
			] ),
			sets.overlay && el( greyd.components.AdvancedPanelBody, {
				title: __( "Overlay", 'greyd_hub' ),
				initialOpen: false,
				// holdsChange: parts.overlay.type != "",
				holdsColors: [
					parts.overlay.type == "color" && { color: parts.overlay.color },
					parts.overlay.type == "gradient" && { color: parts.overlay.gradient },
				]
			}, [
				greyd.components.layout.overlayControls( {
					clientId: props.clientId,
					attributes: { background: { overlay: parts.overlay } },
					setAttributes: setBackground
				} )
			] ),
			sets.pattern && el( greyd.components.AdvancedPanelBody, {
				title: __( "Pattern", 'greyd_hub' ),
				initialOpen: false,
				holdsChange: parts.pattern.type != ""
			}, [
				greyd.components.layout.patternControls( {
					clientId: props.clientId,
					attributes: { background: { pattern: parts.pattern } },
					setAttributes: setBackground
				} )
			] ),
			sets.seperator && el( greyd.components.AdvancedPanelBody, {
				title: __( "Separator", 'greyd_hub' ),
				initialOpen: false,
				// holdsChange: parts.seperator.type != "",
				holdsColors: [
					parts.seperator.type != "" && { color: parts.seperator.color },
				]
			}, [
				greyd.components.layout.seperatorControls( {
					clientId: props.clientId,
					attributes: { background: { seperator: parts.seperator } },
					setAttributes: setBackground
				} )
			] )
		];
	}

	this.backgroundControls = function(props) {

		var types = [
			{ label: __('None', 'greyd_hub'), value: "" },
			{ label: __("Image", 'greyd_hub'), value: 'image' },
			{ label: __('Video', 'greyd_hub'), value: 'video' },
		];
		if (typeof bodymovin !== 'undefined') {
			types.push( { label: __('Animation', 'greyd_hub'), value: 'animation' } );
		}

		var anim_ids = [ 'anim_'+props.clientId, 'preview_anim_'+props.clientId ];
		if (props.attributes.background.type == "animation") {
			// console.log("init background anim");
			greyd.lottie.init(anim_ids);
		}

		// if (props.attributes.background.image.id.toString().indexOf('_') === 0) {
		// 	console.log(props.attributes.background.image);
		// 	var url = 'http://greyd_beta.localhost/wp-content/uploads/2020/11/greyd-LogoPh-dark.svg';
		// 	if (props.attributes.background.image.url != url)
		// 		props.setAttributes( { background: { ...props.attributes.background, image: { ...props.attributes.background.image, url: url } } } )
		// }

		return [
			// type
			el( greyd.components.ButtonGroupControl, {
				value: props.attributes.background.type,
				options: types,
				onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, type: value } } ); },
			} ),
			el( 'div', { className: (props.attributes.background.type == "") ? 'hidden' : "" }, [
				// image
				el( 'div', { className: (props.attributes.background.type != "image") ? 'hidden' : "" }, [
					el( greyd.components.DynamicImageControl, {
						clientId: props.clientId,
						value: props.attributes.background.image,
						useIdAsTag: true,
						onChange: (value) => {
							// console.log(value);
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
					} ),
					el( 'div', { }, [
						// size
						el( wp.components.SelectControl, {
							label: __("Size", 'greyd_hub'),
							value: props.attributes.background.image.size,
							options: [
								{ label: __("Cover", 'greyd_hub'), value: 'cover' },
								{ label: __("Contain", 'greyd_hub'), value: 'contain' },
								{ label: __("Initial", 'greyd_hub'), value: 'initial' },
							],
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, image: { ...props.attributes.background.image, size: value } } } ); },
						} ),
						// repeat
						el( wp.components.SelectControl, {
							label: __("Repetition", 'greyd_hub'),
							value: props.attributes.background.image.repeat,
							options: [
								{ label: __("No repetition", 'greyd_hub'), value: 'no-repeat' },
								{ label: __("Vertically and horizontally", 'greyd_hub'), value: 'repeat' },
								{ label: __("Horizontally", 'greyd_hub'), value: 'repeat-x' },
								{ label: __("Vertically", 'greyd_hub'), value: 'repeat-y' },
							],
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, image: { ...props.attributes.background.image, repeat: value } } } ); },
						} ),
						// position
						el( wp.components.SelectControl, {
							label: __('Position', 'greyd_hub'),
							value: props.attributes.background.image.position,
							options: [
								{ label: __("Top center", 'greyd_hub'), value: "top center" },
								{ label: __("Top left", 'greyd_hub'), value: 'top left' },
								{ label: __("Top right", 'greyd_hub'), value: 'top right' },
								{ label: __("Centered", 'greyd_hub'), value: "center center" },
								{ label: __("Center left", 'greyd_hub'), value: 'center left' },
								{ label: __("Center right", 'greyd_hub'), value: 'center right' },
								{ label: __("Bottom center", 'greyd_hub'), value: "bottom center" },
								{ label: __("Bottom left", 'greyd_hub'), value: 'bottom left' },
								{ label: __("Bottom right", 'greyd_hub'), value: 'bottom right' },
							],
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, image: { ...props.attributes.background.image, position: value } } } ); },
						} ),
					] ),
				] ),
				// video
				el( 'div', { className: (props.attributes.background.type != "video") ? 'hidden' : "" }, [
					// url
					el( wp.components.TextControl, {
						label: __("Video link", 'greyd_hub'),
						help: __("YouTube or Vimeo URL", 'greyd_hub'),
						value: props.attributes.background.video.url,
						onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, video: { ...props.attributes.background.video, url: value } } } ); },
					} ),
					// aspect
					el( wp.components.SelectControl, {
						label: __("Aspect ratio", 'greyd_hub'),
						value: props.attributes.background.video.aspect,
						options: [
							{ label: __('16/9', 'greyd_hub'), value: "16/9" },
							{ label: __('21/9', 'greyd_hub'), value: "21/9" },
							{ label: __('4/3', 'greyd_hub'), value: "4/3" },
							{ label: __('1/1', 'greyd_hub'), value: "1/1" },
						],
						onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, video: { ...props.attributes.background.video, aspect: value } } } ); },
					} ),
				] ),
				// animation
				el( 'div', {
					className: (typeof bodymovin === 'undefined' || props.attributes.background.type != "animation") ? 'hidden' : ""
				}, [
					el( wp.components.BaseControl, { }, [
						el( wp.blockEditor.MediaUploadCheck, {
							fallback: el( 'p', {
								className: "greyd-inspector-help"
							}, __("To edit the animation, you need permission to upload media.", 'greyd_hub') )
						}, [
							el( wp.blockEditor.MediaUpload, {
								allowedTypes: 'application/json',
								value: props.attributes.background.anim.id,
								onSelect: function(media) {
									greyd.lottie.set([anim_ids[0]], media.url);
									props.setAttributes( { background: { ...props.attributes.background, anim: { ...props.attributes.background.anim, id: media.id, url: media.url } } } );
								},
								render: function(obj) {
									if (props.attributes.background.anim.url) setTimeout(() => {
										greyd.lottie.initAnimations();
									}, 0);
									return el( wp.components.Button, {
										className: !props.attributes.background.anim.url ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
										onClick: obj.open
									}, !props.attributes.background.anim.url ? __( "Select animation", 'greyd_hub' ) : el( 'div', {
										id: anim_ids[1],
										className: 'lottie-animation auto',
										"data-anim": 'auto',
										'data-src': props.attributes.background.anim.url
									} ) )
								},
							} ),
							props.attributes.background.anim.url ? el( wp.components.Button, {
								className: "is-link is-destructive",
								onClick: function() { props.setAttributes( { background: { ...props.attributes.background, anim: { ...props.attributes.background.anim, id: false, url: false } } } ) },
							}, __( "Remove animation", 'greyd_hub' ) ) : ""
						] ),
					] ),
					el( 'div', { className: (!props.attributes.background.anim.url) ? 'hidden' : "" }, [
						// anim
						el( wp.components.SelectControl, {
							label: __("SVG animation", 'greyd_hub'),
							value: props.attributes.background.anim.anim,
							options: [
								{ label: __("Start automatically", 'greyd_hub'), value: 'auto' },
								{ label: __("Start as soon as visible", 'greyd_hub'), value: 'visible' },
								/* @since 2.15.0 */
								{ label: __("Sync with scroll", 'greyd_hub'), value: 'seek_scroll' },
							],
							onChange: function(value) {
								greyd.lottie.set(anim_ids, props.attributes.background.anim.url);
								props.setAttributes( { background: { ...props.attributes.background, anim: { ...props.attributes.background.anim, anim: value } } } );
							},
						} ),
						// position
						el( wp.components.SelectControl, {
							label: __('Position', 'greyd_hub'),
							value: props.attributes.background.anim.position,
							options: [
								{ label: __("Top center", 'greyd_hub'), value: "top_center" },
								{ label: __("Top left", 'greyd_hub'), value: 'top_left' },
								{ label: __("Top right", 'greyd_hub'), value: 'top_right' },
								{ label: __("Centered", 'greyd_hub'), value: "center_center" },
								{ label: __("Center left", 'greyd_hub'), value: 'center_left' },
								{ label: __("Center right", 'greyd_hub'), value: 'center_right' },
								{ label: __("Bottom center", 'greyd_hub'), value: "bottom_center" },
								{ label: __("Bottom left", 'greyd_hub'), value: 'bottom_left' },
								{ label: __("Bottom right", 'greyd_hub'), value: 'bottom_right' },
							],
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, anim: { ...props.attributes.background.anim, position: value } } } ); },
						} ),
						// width
						el( greyd.components.RangeUnitControl, {
							label: __("Width", 'greyd_hub'),
							value: props.attributes.background.anim.width,
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, anim: { ...props.attributes.background.anim, width: value } } } ); },
						} ),
					] ),
				] ),

				// opacity
				el( wp.components.RangeControl, {
					label: __("Opacity", 'greyd_hub'),
					value: props.attributes.background.opacity,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, opacity: value } } ); },
				} ),
				// scroll
				el( wp.components.SelectControl, {
					label: __("Scroll behavior", 'greyd_hub'),
					value: props.attributes.background.scroll.type,
					options: [
						{ label: __("Move with content", 'greyd_hub'), value: "scroll" },
						{ label: __("Fix", 'greyd_hub'), value: 'fixed' },
						{ label: __("Vertical parallax", 'greyd_hub'), value: 'vparallax' },
						{ label: __("Horizontal parallax", 'greyd_hub'), value: 'hparallax' },
					],
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, scroll: { ...props.attributes.background.scroll, type: value } } } ); },
				} ),
				(
					_.has(props.attributes.background.scroll, 'type') && props.attributes.background.scroll.type === 'fixed'
					? el( wp.components.CheckboxControl, {
						label: __("Activate parallax on mobile devices", 'greyd_hub'),
						help: __( "Attention: On iOS devices fixed backgrounds can lead to display errors. Please test your site carefully if you enable this setting.", 'greyd_hub' ),
						checked: props.attributes.background.scroll.parallax_mobile,
						onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, scroll: { ...props.attributes.background.scroll, parallax_mobile: value } } } ); },
					} )
					: null
				),
				(
					_.has(props.attributes.background.scroll, 'type') && props.attributes.background.scroll.type.indexOf('parallax') !== -1
					? el( 'div', { }, [
						el( wp.components.CheckboxControl, {
							label: __("Activate parallax on mobile devices", 'greyd_hub'),
							checked: props.attributes.background.scroll.parallax_mobile,
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, scroll: { ...props.attributes.background.scroll, parallax_mobile: value } } } ); },
						} ),
						el( wp.components.RangeControl, {
							label: __("Parallax speed", 'greyd_hub'),
							help: __("-200% = backwards, 0% = fixed, 200% = twice as fast as normal", 'greyd_hub'),
							min: -200,
							max: 200,
							value: props.attributes.background.scroll.parallax,
							onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, scroll: { ...props.attributes.background.scroll, parallax: value } } } ); },
						} ),
					] )
					: null
				)
			] ),
		];

	}

	/**
	 * Get columns overlay controls.
	 * @param {object} props     Block properties
	 * @returns React Element
	 */
	this.overlayControls = function(props) {

		const background = _.has( props.attributes, 'background' ) ? props.attributes.background : {};
		const overlay = _.has( background, 'overlay' ) ? background.overlay : {};
		const {
			type = '',
			color = null,
			gradient = null,
			opacity = 100
		} = overlay;

		return [
			// type
			el( greyd.components.ButtonGroupControl, {
				value: overlay.type,
				options: [
					{ label: __("None", "greyd_hub"), value: "" },
					{ label: __("Solid", "greyd_hub"), value: "color" },
					{ label: __("Gradient", "greyd_hub"), value: "gradient" },
				],
				onChange: ( val ) => {
					props.setAttributes( { background: { ...background, overlay: {
						...overlay,
						type: val
					} } } );
				}
			} ),
			!_.isEmpty( type ) && el( 'div', { }, [
				// color
				type === 'color' && el( 'div', {}, [
					el( greyd.components.ColorGradientPopupControl, {
						mode: 'color',
						label: __("Color", "greyd_hub"),
						value: color,
						onChange: ( val ) => {
							props.setAttributes( { background: { ...background, overlay: {
								...overlay,
								color: val
							} } } );
						}
					} ),
				] ),
				// gradient
				type === 'gradient' && el( 'div', {}, [
					el( greyd.components.ColorGradientPopupControl, {
						mode: 'gradient',
						label: __("Gradient", "greyd_hub"),
						value: gradient,
						onChange: ( val ) => {
							props.setAttributes( { background: { ...background, overlay: {
								...overlay,
								gradient: val
							} } } );
						}
					} ),
				] ),
				// opacity
				el( wp.components.RangeControl, {
					label: __("Opacity", 'greyd_hub'),
					value: opacity,
					onChange: ( val ) => {
						props.setAttributes( { background: { ...background, overlay: {
							...overlay,
							opacity: val
						} } } );
					}
				} ),
			] ),
		];

	}

	this.patternControls = function(props) {

		var getstyle = function(key) {
			return { backgroundImage: 'url('+greyd.tools.layout.getPattern(key)+')' };
		}
		var options = [
			{ key: '', name: __("No pattern", 'greyd_hub') },
			{ key: 'pattern_01', name: __("Small dots", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_01') },
			{ key: 'pattern_02', name: __("Big dots", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_02') },
			{ key: 'pattern_03', name: __("Grid lines", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_03') },
			{ key: 'pattern_04', name: __("Crosses", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_04') },
			{ key: 'pattern_05', name: __("Horizontal lines", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_05') },
			{ key: 'pattern_06', name: __("Vertical lines", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_06') },
			{ key: 'pattern_07', name: __("Squares and dots", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_07') },
			{ key: 'pattern_08', name: __("Small squares", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_08') },
			{ key: 'pattern_09', name: __("Big squares", 'greyd_hub'), className: 'custom-select-pattern-type', style: getstyle('pattern_09') },
			{ key: 'pattern_custom', name: __("Custom pattern", 'greyd_hub') },
		];
		var type = props.attributes.background.pattern.type;
		for (var i=0; i<options.length; i++) {
			if (options[i].key == type) {
				type = options[i];
				break;
			}
		}

		return [
			// type
			el( wp.components.BaseControl, { }, [
				el( wp.components.CustomSelectControl, {
					options: options,
					value: type,
					onChange: function(value) {
						// console.log(value);
						var val = value.selectedItem.key;
						props.setAttributes( {
							background: {
								...props.attributes.background,
								pattern: {
									...props.attributes.background.pattern,
									type: val
								}
							}
						} );
					},
				} ),
			] ),
			el( 'div', { className: (props.attributes.background.pattern.type == "") ? 'hidden' : "" },
				el( 'div', { className: (props.attributes.background.pattern.type != "pattern_custom") ? 'hidden' : "" },
					// custom
					el( wp.components.BaseControl, { },
						el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) },
							el( wp.blockEditor.MediaUpload, {
								allowedTypes: 'image/*',
								value: props.attributes.background.pattern.id,
								onSelect: function(value) { props.setAttributes( { background: { ...props.attributes.background, pattern: { ...props.attributes.background.pattern, id: value.id, url: value.url } } } ); },
								render: function(obj) {
									return el( wp.components.Button, {
										className: props.attributes.background.pattern.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
										onClick: obj.open
									}, props.attributes.background.pattern.id == -1 ? __( "Select pattern", 'greyd_hub' ) : el( 'img', { src: props.attributes.background.pattern.url } ) )
								},
							} ),
							props.attributes.background.pattern.id !== -1 ? el( wp.components.Button, {
								className: "is-link is-destructive",
								onClick: function() { props.setAttributes( { background: { ...props.attributes.background, pattern: { ...props.attributes.background.pattern, id: -1, url: "" } } } ); },
							}, __( "Remove pattern", 'greyd_hub' ) ) : ""
						),
					),
				),
				// size
				el( greyd.components.RangeUnitControl, {
					label: __("Size", 'greyd_hub'),
					value: props.attributes.background.pattern.size,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, pattern: { ...props.attributes.background.pattern, size: value } } } ); },
				} ),
				// opacity
				el( wp.components.RangeControl, {
					label: __("Opacity", 'greyd_hub'),
					value: props.attributes.background.pattern.opacity,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, pattern: { ...props.attributes.background.pattern, opacity: value } } } ); },
				} ),
				// scroll
				el( wp.components.SelectControl, {
					label: __("Scroll behavior", 'greyd_hub'),
					value: props.attributes.background.pattern.scroll,
					options: [
						{ label: __("Move with content", 'greyd_hub'), value: "scroll" },
						{ label: __("Fixed", 'greyd_hub'), value: 'fixed' },
					],
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, pattern: { ...props.attributes.background.pattern, scroll: value } } } ); },
				} ),
			),
		];

	}

	this.seperatorControls = function(props) {

		const getSeperatorName = function(key, title) {
			var svg =  greyd.tools.layout.getSeperator(key, '20px', '#888');
			return el( 'span', { style: { width: '100%', lineHeight: 'initial' } }, svg, el( 'span', {}, title ) );
		}
		let options = [
			{ key: "", name: __("No separator", 'greyd_hub') }
		];
		const seperators = Object.values( greyd.tools.layout.getSeperators() );
		for (var i=0; i<seperators.length; i++) {
			options.push( {
				key: seperators[i].slug,
				name: getSeperatorName(seperators[i].slug, seperators[i].title),
				className: 'custom-select-seperator-type' }
			);
		}

		var type = props.attributes.background.seperator.type;
		for (var i=0; i<options.length; i++) {
			if (options[i].key == type) {
				type = options[i];
				break;
			}
		}

		return [
			// type
			el( wp.components.BaseControl, { }, [
				el( wp.components.CustomSelectControl, {
					className: 'seperatorselect',
					options: options,
					value: type,
					onChange: function(value) {
						// console.log(value);
						var val = value.selectedItem.key;
						props.setAttributes( {
							background: {
								...props.attributes.background,
								seperator: {
									...props.attributes.background.seperator,
									type: val
								}
							}
						} );
					},
				} ),
			] ),
			el( 'div', { className: (props.attributes.background.seperator.type == "") ? 'hidden' : "" }, [
				// color
				el( greyd.components.ColorGradientPopupControl, {
					className: 'single',
					mode: 'color',
					label: __("Color", "greyd_hub"),
					value: props.attributes.background.seperator.color,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, seperator: { ...props.attributes.background.seperator, color: value } } } ); },
				} ),
				// position
				el( wp.components.SelectControl, {
					label: __('Position', 'greyd_hub'),
					value: props.attributes.background.seperator.position,
					options: [
						{ label: __("Top", 'greyd_hub'), value: "top" },
						{ label: __("Bottom", 'greyd_hub'), value: "bottom" },
						{ label: __("Top and bottom", 'greyd_hub'), value: "top_bottom" },
						{ label: __("Top and bottom, asymmetrical", 'greyd_hub'), value: "top_bottom_asym" },
					],
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, seperator: { ...props.attributes.background.seperator, position: value } } } ); },
				} ),
				// height
				el( greyd.components.RangeUnitControl, {
					label: __("Height", 'greyd_hub'),
					value: props.attributes.background.seperator.height,
					max: 500,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, seperator: { ...props.attributes.background.seperator, height: value } } } ); },
				} ),
				// opacity
				el( wp.components.RangeControl, {
					label: __("Opacity", 'greyd_hub'),
					value: props.attributes.background.seperator.opacity,
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, seperator: { ...props.attributes.background.seperator, opacity: value } } } ); },
				} ),
				// zpos
				el( wp.components.SelectControl, {
					label: __("Level (z-index)", 'greyd_hub'),
					value: props.attributes.background.seperator.zposition,
					options: [
						{ label: __("In between background and content", 'greyd_hub'), value: "0" },
						{ label: __("In front of background and content", 'greyd_hub'), value: "1" },
					],
					onChange: function(value) { props.setAttributes( { background: { ...props.attributes.background, seperator: { ...props.attributes.background.seperator, zposition: value } } } ); },
				} ),
			] ),
		];

	}

	// console.log( "additional layout components: loaded" );

};