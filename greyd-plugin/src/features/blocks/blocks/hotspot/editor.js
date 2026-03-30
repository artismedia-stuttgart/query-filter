( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the hotspot wrapper.
	 */
	wp.blocks.registerBlockType( 'greyd/hotspot-wrapper', {
		apiVersion: 2,
		title: __('Hotspot Block', 'greyd_hub'),
		description: __("Create interactive graphics/animations with clickable hotspots.", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('hotspot'),
		category: 'greyd-blocks',
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
			greydClass: { type: 'string', default: '' },

			// image
			image: { type: 'object', default: {
				id: -1,
				url: ''
			} },
			mobileImage: { type: 'object', default: {
				id: -1,
				url: ''
			} },
			mediaType: {type: 'string', default: 'image'},
			customMobile: { type: 'boolean', default: false },
			anim: { type: 'string', default: 'auto' },
			animation: { type: 'object', default: {
				id: -1,
				url: false,
			} },

			// settings
			previewHeight: {type: 'integer'},
			openOnHover: { type: 'boolean', default: false },
			autoClose: { type: 'boolean', default: false },

			// design
			hotspotType: { type: 'string', default: ''},
			hotspotIcon: { type: 'object', default: {
				icon: 'icon_plus_alt',
			}},
			hotspotImage: { type: 'object', default: {
				id: -1,
				url: ''
			}},
			idleAnimation: { type: 'string', default: '' },
			showTriangle: { type: 'boolean', default: true },

			// styles
			hotspotStyles: { type: 'object'},
			popoverStyles: { type: 'object'},
			popoverPosition: {type: 'string', default: '' },
			wrapperStyles: { type: 'object'},

			// usability
			isDragging: { type: 'boolean', default: false },
			targetId: { type: 'string', default: '' },
			locked: { type: 'boolean', default: false },
		},
		providesContext: {
			'greyd/hotspot-type': 'hotspotType',
			'greyd/hotspot-icon': 'hotspotIcon',
			'greyd/hotspot-image': 'hotspotImage',
			'greyd/hotspot-popover-position': 'popoverPosition',
			'greyd/hotspot-idle-animation': 'idleAnimation',
			'greyd/hotspot-locked': 'locked',
		},

		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}
			
			const atts = props.attributes;

			var anim_ids = [ 'inspector_anim_'+props.clientId, 'anim_'+props.clientId];
			if ( atts.mediaType == "animation") {
				greyd.lottie.init(anim_ids);
			}

			const mediaTypes = [
				{ label: __("Image", 'greyd_hub'), value: 'image' },
			];
			if (typeof bodymovin !== 'undefined') mediaTypes.push( { label: __('Animation', 'greyd_hub'), value: 'animation' } );

			// mode for section setting
			const [mode, setMode ] = wp.element.useState("");
			if (!props.isSelected && mode != "") setMode("");

			const hasImage = (_.has(atts.image, "id") && atts.image.id > 0) || (_.has(atts.animation, "id") && atts.animation.id > 0);
			const hasChildBlocks = greyd.tools.hasChildBlocks(props.clientId);

			const [ snackbarShown, setSnackbarShown ] = wp.element.useState( false );
			if ( props.isSelected && !hasChildBlocks && !snackbarShown && hasImage ) {
				greyd.tools.showSnackbar( __( "To add a hotspot, Alt-click (Windows) or Option-click (Mac) on the image. To remove a hotspot, click on the corresponding hotspot while holding the Shift key", 'greyd_hub' ) );
				setSnackbarShown(true);
			}

			// get cursor position relative to wrapper from event
			const getPosition = (e) => {
				const rect   = e.currentTarget.getBoundingClientRect();
				const width  = rect.width;
				const height = rect.height;
				const left   = e.clientX - rect.x;
				const top    = e.clientY - rect.y;

				return {
					left: ( (left / width) * 100 ) + '%',
					top: ( (top / height) * 100 ) + '%'
				}
			}

			// make blockProps to render id and class
			var classNames = ["greyd-media-wrapper", atts.greydClass, atts.className];
			const blockProps = wp.blockEditor.useBlockProps({ className: classNames.join(' ') });
			if ( !_.isEmpty(atts.anchor) ) blockProps.id = atts.anchor;

			return [

				// toolbar
				el( wp.blockEditor.BlockControls, { group: 'inline' }, [
					el( wp.components.ToolbarButton, {
						label: atts.locked ? __("Unlock hotspot positions", 'greyd_hub') : __("Lock hotspot positions", 'greyd_hub'),
						icon: atts.locked ? greyd.tools.getCoreIcon('locked') : greyd.tools.getCoreIcon('unlocked'),
						isPressed: atts.locked,
						onClick: () => { 
							props.setAttributes( { locked: !atts.locked } );
						}
					} ),
				] ),

				// sidebar - settings
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					// keyboard shortcuts
					el( wp.components.PanelBody, {
						title: __( "Instruction", 'greyd_hub' ),
					}, [
						el( 'ul', {
							className: 'edit-post-keyboard-shortcut-help-modal__shortcut-list',
							role: 'list',
							style: { margin: 0 }
						}, [
							el( 'li', {
								className: 'edit-post-keyboard-shortcut-help-modal__shortcut',
								style: { borderTop: 0 }
							}, [
								el( 'div', {
									className: 'edit-post-keyboard-shortcut-help-modal__shortcut-description'
								}, __( "Create hotspot", 'greyd_hub' ) ),
								el( 'div', {
									className: 'edit-post-keyboard-shortcut-help-modal__shortcut-term',
								}, [
									el( 'kbd', {
										className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key-combination',
										'aria-label': __( 'Option Click', 'greyd_hub' )
									}, [
										(
											navigator.platform.toUpperCase().indexOf('MAC') >= 0
											? el( 'kbd', {
												className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key'
											}, '⌥' )
											: el( 'kbd', {
												className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key'
											}, 'alt' )
										),
										el( 'kbd', {
											className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key'
										}, 'click' )
									] )
								] )
							] ),
							el( 'li', {
								className: 'edit-post-keyboard-shortcut-help-modal__shortcut',
								style: { borderBottom: 0 }
							}, [
								el( 'div', {
									className: 'edit-post-keyboard-shortcut-help-modal__shortcut-description'
								}, __( "Remove hotspot", 'greyd_hub' ) ),
								el( 'div', {
									className: 'edit-post-keyboard-shortcut-help-modal__shortcut-term',
								}, [
									el( 'kbd', {
										className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key-combination',
										'aria-label': __( 'Shift Click', 'greyd_hub' )
									}, [
										el( 'kbd', {
											className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key'
										}, '⇧' ),
										el( 'kbd', {
											className: 'edit-post-keyboard-shortcut-help-modal__shortcut-key'
										}, 'click' )
									] )
								] )
							] ),
						] ),
					] ),

					// image
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Content", 'greyd_hub' ),
						initialOpen: false
					}, [
						el( greyd.components.ButtonGroupControl, {
							value: atts.mediaType,
							options: mediaTypes,
							onChange: function(value) { 
								props.setAttributes( { mediaType: value } ); 
							},
						} ),

						atts.mediaType == "image" ? [ 
							el( wp.components.BaseControl, { },
								el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) },
									el( wp.blockEditor.MediaUpload, {
										allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
										value: _.has(atts, 'image.id') ? atts.image.id : -1,
										onSelect: function(value) { props.setAttributes( { image: { id: value.id, url: value.url } } ); },
										render: function(obj) {
											return el( wp.components.Button, { 
												className: !_.has(atts, 'image.id') || atts.image.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
												onClick: obj.open 
											}, !_.has(atts, 'image.id') || atts.image.id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: atts.image.url } ) )
										},
									} ),
									_.has(atts, 'image.id') && atts.image.id !== -1 ? el( wp.components.Button, { 
										className: "is-link is-destructive",
										onClick: function() { props.setAttributes( { image: { id: -1, url: "" } } ) },
									}, __( "Remove image", 'greyd_hub' ) ) : ""
								),
							),
							el( wp.components.BaseControl, { },
								el( wp.components.ToggleControl, {
									label: __( "Mobile use different image", 'greyd_hub' ),
									checked: atts.customMobile,
									onChange: function(value) {
										props.setAttributes( { customMobile: !!value } );
									},
								} )),
								// custom button
								atts.customMobile ? el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) },
								el( wp.blockEditor.MediaUpload, {
									allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
									value: _.has(atts, 'mobileImage.id') ? atts.mobileImage.id : -1,
									onSelect: function(value) { props.setAttributes( { mobileImage: { id: value.id, url: value.url } } ); },
									render: function(obj) {
										return el( wp.components.Button, { 
											className: !_.has(atts, 'mobileImage.id') || atts.mobileImage.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
											onClick: obj.open 
										}, !_.has(atts, 'mobileImage.id') || atts.mobileImage.id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: atts.mobileImage.url } ) )
									},
								} ),
								_.has(atts, 'mobileImage.id') && atts.mobileImage.id !== -1 ? el( wp.components.Button, { 
									className: "is-link is-destructive",
									onClick: function() { props.setAttributes( { mobileImage: { id: -1, url: "" } } ) },
								}, __( "Remove image", 'greyd_hub' ) ) : ""
							) : ''
						] : [
							el( wp.components.BaseControl, { }, [
								el( wp.blockEditor.MediaUploadCheck, { fallback: el(
										'p',
										{ className: "greyd-inspector-help" },
										__("To edit the animation, you need permission to upload media.", 'greyd_hub')
									) },
									el( wp.blockEditor.MediaUpload, {
										allowedTypes: 'application/json',
										value: atts.animation.id,
										onSelect: function(media) { 
											greyd.lottie.set([anim_ids[0]], media.url);
											props.setAttributes({ animation: {id: media.id, url: media.url} }); 
										},
										render: function(obj) {
											if (atts.animation.url) setTimeout(() => {
												greyd.lottie.initAnimations();
											}, 0);
											return el( wp.components.Button, { 
												className: !atts.animation.url ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
												onClick: obj.open 
											}, !atts.animation.url ? __( "Select animation", 'greyd_hub' ) :  el( 'div', { 
												id: anim_ids[1],
												className: 'lottie-animation auto', 
												"data-anim": 'auto',
												'data-src': atts.animation.url 
											} ) )
										},
									} ),
									atts.animation.url ? el( wp.components.Button, { 
										className: "is-link is-destructive",
										onClick: function() { props.setAttributes( {animation: {id: false, url: false }} ) },
									}, __( "Remove animation", 'greyd_hub' ) ) : ""
								),
							] ),
							el( 'div', { className: (!atts.animation.url) ? 'hidden' : "" }, [
								el( wp.components.SelectControl, {
									label: __("SVG animation", 'greyd_hub'),
									value: atts.anim,
									options: [
										{ label: __("Start automatically", 'greyd_hub'), value: 'auto' },
										{ label: __("Start on hover", 'greyd_hub'), value: 'hover' },
										{ label: __("Start as soon as visible", 'greyd_hub'), value: 'visible' },
										/* @since 2.15.0 */
										{ label: __("Sync with scroll", 'greyd_hub'), value: 'seek_scroll' },
										{ label: __("Sync with cursor position", 'greyd_hub'), value: 'seek_cursor' },
										{ label: __("Sync with horizontal cursor position", 'greyd_hub'), value: 'seek_cursor_horizontal' },
										{ label: __("Toggle animation", 'greyd_hub'), value: 'toggle' },
										{ label: __("Play animation on click", 'greyd_hub'), value: 'click' },
									],
									onChange: function(value) {
										greyd.lottie.set(anim_ids, atts.animation.url);
										props.setAttributes( { anim: value } );
									},
								} ),
							] ),
						]
					] ),

					// behaviour
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Behavior", 'greyd_hub' ),
						initialOpen: true,
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Trigger popover on hover", 'greyd_hub' ),
							checked: atts.openOnHover,
							onChange: (value) => props.setAttributes( { openOnHover: !!value } ),
							help: __("This change only affects the frontend.", 'greyd_hub'),
						} ),
						!atts.openOnHover ? el( wp.components.ToggleControl, {
							label: __( "Open only one popover at a time", 'greyd_hub' ),
							checked: atts.autoClose,
							onChange: (value) => props.setAttributes( { autoClose: !!value } ),
							help: __("This change only affects the frontend.", 'greyd_hub'),
						} ) : null,
					] ),
				] ),

				// sidebar - styles
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [

					// wrapper settings
					mode == '' ? [

						// general
						el( wp.components.PanelBody, {
							title: __( "General", 'greyd_hub' ),
						}, [
							
							// type
							el( greyd.components.ButtonGroupControl, {
								// label: __( 'Hotspot Art', 'greyd_hub' ),
								value: atts.hotspotType,
								options: [
									{ label: __('Element', 'greyd_hub'), value: '' },
									{ label: __('Icon', 'greyd_hub'), value: 'icon' },
									{ label: __("Image", 'greyd_hub'), value: 'image' },
								],
								onChange: function(value) { props.setAttributes( { hotspotType: value } ); },
							} ),
						
							// icon
							atts.hotspotType == "icon" ? el( greyd.components.IconPicker, {
								value: _.has(atts, 'hotspotIcon.icon') ? atts.hotspotIcon.icon : '',
								icons: greyd.data.icons,
								onChange: (value) => { 
									props.setAttributes({ hotspotIcon: { ...atts.hotspotIcon, icon: value } }); 
								},
							} ) : null,

							// image
							atts.hotspotType == "image" ? el( wp.components.BaseControl, { }, [
								el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) },
									el( wp.blockEditor.MediaUpload, {
										allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
										value: _.has(atts, 'hotspotImage.id') ? atts.hotspotImage.id : -1,
										onSelect: function(value) { props.setAttributes( { hotspotImage: { id: value.id, url: value.url } } ); },
										render: function(obj) {
											return el( wp.components.Button, { 
												className: !_.has(atts, 'hotspotImage.id') || atts.hotspotImage.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
												onClick: obj.open 
											}, !_.has(atts, 'hotspotImage.id') || atts.hotspotImage.id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: atts.hotspotImage.url } ) )
										},
									} ),
									_.has(atts, 'hotspotImage.id') && atts.hotspotImage.id !== -1 ? el( wp.components.Button, { 
										className: "is-link is-destructive",
										onClick: function() { props.setAttributes( { hotspotImage: { id: -1, url: "" } } ) },
									}, __( "Remove image", 'greyd_hub' ) ) : ""
								),
							] ) : null,

							// to hotspot settings
							el( greyd.components.SectionControl, {
								title: __("Hotspot design", 'greyd_hub'),
								onClick: () => setMode("hotspot")
							} ),

							// to popover settings
							el( greyd.components.SectionControl, {
								title: __("Popover design", 'greyd_hub'),
								onClick: () => { 
									// open first
									const wrapper = wp.data.select("core/block-editor").getBlocksByClientId(props.clientId)[0];
									const hotspots = wrapper.innerBlocks;
									if ( hotspots.length ) {
										hotspots.forEach( hotspot => {
											if ( hotspot.clientId ) {
												wp.data.dispatch('core/block-editor').updateBlockAttributes(hotspot.clientId, { active: true });
												// break the loop
											}
											return false;
										});
									}
									// wp.data.dispatch('core/block-editor').updateBlockAttributes(hotspots[0].clientId, {active: true});
									setMode("popover");
								}
							} ),
						] ),

						// image size
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'wrapperStyles',
							controls: [
								{
									label: __("Width", 'greyd_hub'),
									attribute: "width",
									control: greyd.components.RangeUnitControl, 
									units: ["px", "%"],
									max: 1200,
								},
							]
						} ),

					] : null,

					// hotspot settings
					mode == "hotspot" ? [ 
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __('Hotspot', 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),
							
						atts.hotspotType !== "image" ? el( greyd.components.StylingControlPanel, {
							title: __("Color", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							supportsActive: true,
							blockProps: props,
							parentAttr: 'hotspotStyles',
							controls: atts.hotspotType === "icon" ? [
								{
									label: __("Icon color", 'greyd_hub'),
									attribute: "--hotspot-background-color",
									control: greyd.components.ColorPopupControl
								},
							] : [
								{
									label: __("Inner color", 'greyd_hub'),
									attribute: "--hotspot-background-color",
									control: greyd.components.ColorPopupControl
								},
								{
									label: __("Outer color", 'greyd_hub'),
									attribute: "--hotspot-outline-color",
									control: greyd.components.ColorPopupControl
								},
							]
						} ) : null,

						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							blockProps: props,
							parentAttr: 'hotspotStyles',
							controls: [
								{
									label: __("Size", 'greyd_hub'),
									attribute: "--hotspot-size",
									control: greyd.components.RangeUnitControl,
									max: { px: 100, em: 4 },
									units: ['px', 'em']
								},
								...atts.hotspotType == "" ? [
									{
										label: __("Between inside and outside", 'greyd_hub'),
										attribute: "--hotspot-outline-offset",
										control: greyd.components.RangeUnitControl,
										units: ['px'],
										max: 40
									}
								] : [],
							]
						} ),

						// animation
						el( greyd.components.AdvancedPanelBody, {
							title: __( 'Animation', 'greyd_hub' ),
							initialOpen: true,
							holdsChange: !_.isEmpty(atts.idleAnimation)
						}, [
							el( wp.components.SelectControl, {
								value: atts.idleAnimation,
								options: [
									{ label: __("None", 'greyd_hub'), value: '' }, 
									{ label: __("Pulse", 'greyd_hub'), value: 'hotspot-pulse' }, 
									{ label: __("Flash", 'greyd_hub'), value: 'hotspot-blink' }, 
									{ label: __("Flutter", 'greyd_hub'), value: 'hotspot-wobble' }, 
	
								],
								onChange: function(value) { props.setAttributes( { idleAnimation: value } ); },
							} ),
						] ),

						atts.hotspotType == "" ? [
							el( greyd.components.StylingControlPanel, {
								title: __("Border radius", 'greyd_hub'),
								initialOpen: false,
								blockProps: props,
								parentAttr: 'hotspotStyles',
								controls: [ {
									label: __("Border radius", 'greyd_hub'),
									attribute: "--hotspot-border-radius",
									control: greyd.components.DimensionControl,
									labels: {
										"all": __("All corners", "greyd_hub"),
									},
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
									type: "string"
								} ]
							} ),
						] : null
					] : null,

					// popover settings
					mode == "popover" ? [ 
						el( 'span', {
							style: { display: 'block', paddingTop: '1rem', borderTop: '1px solid #e0e0e0' },
						}),
						el( greyd.components.SectionControl, {
							title: __('Popover', 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),

						el( greyd.components.AdvancedPanelBody, {
							title: __( "General", 'greyd_hub' ),
							initialOpen: true
						}, [
							el( greyd.components.ButtonGroupControl, {
								// title: __("Horizontally", 'greyd_hub'),
								value: atts.popoverPosition,
								options: [
									{ label: __("Bottom", 'greyd_hub'), value: '' },
									{ label: __("Top", 'greyd_hub'), value: 'position-top' },
									{ label: __("Right", 'greyd_hub'), value: 'position-right' },
									{ label: __("Left", 'greyd_hub'), value: 'position-left' },
								],
								style: {
									marginBottom: '16px'
								},
								onChange: (value) => props.setAttributes( { popoverPosition: value })
							} ),

							el( wp.components.ToggleControl, {
								label: __( "Show indicator", 'greyd_hub' ),
								help: __( "A small triangle is displayed at the pop-up", 'greyd_hub' ),
								checked: atts.showTriangle,
								onChange: (value) => props.setAttributes( { showTriangle: !!value } )
							} ),

							el( greyd.components.RangeUnitControl, {
								label: __("Distance to the hotspot", 'greyd_hub'),
								value: _.has(atts.popoverStyles, "--popover-offset") ? atts.popoverStyles["--popover-offset"] : null,
								unit: ['px', 'em'],
								max: { px: 80, em: 2 },
								min: -50,
								onChange: (value) => props.setAttributes( { popoverStyles: { ...atts.popoverStyles, "--popover-offset": value } }),
								supportsPresets: true,
							} ),
						] ),

						// color
						el( greyd.components.StylingControlPanel, {
							title: __("Color", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __('Text', 'greyd_hub'),
									attribute: "--popover-text-color",
									control: greyd.components.ColorPopupControl
								},
								{
									label: __("Background", 'greyd_hub'),
									attribute: "--popover-background-color",
									control: greyd.components.ColorPopupControl
								},
							]
						} ),

						// width
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [ 
								{
									label: __("Maximum width", 'greyd_hub'),
									min: _.has(atts.popoverStyles, "--popover-min-width") ? atts.popoverStyles["--popover-min-width"] : 250,
									max: 800,
									attribute: "--popover-max-width",
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Minimum width", 'greyd_hub'),
									max: 800,
									attribute: "--popover-min-width",
									control: greyd.components.RangeUnitControl,
								} 
							]
						} ),

						// radius
						el( greyd.components.StylingControlPanel, {
							title: __("Border radius", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [ {
								label: __("Border radius", 'greyd_hub'),
								attribute: "--popover-border-radius",
								control: greyd.components.DimensionControl,
								labels: {
									"all": __("All corners", "greyd_hub"),
								},
								sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
								type: "string"
							} ]
						} ),

						// border
						el( greyd.components.StylingControlPanel, {
							title: __("Border", 'greyd_hub'),
							blockProps: props,
							parentAttr: "popoverStyles",
							supportsHover: true,
							sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
							controls: [
								{
									label: __("Border", 'greyd_hub'),
									attribute: "--popover-border",
									control: greyd.components.BorderControl,
								}
							]
						} ),

						// shadow
						el( greyd.components.StylingControlPanel, {
							title: __("Shadow", 'greyd_hub'),
							blockProps: props,
							parentAttr: "popoverStyles",
							supportsHover: true,
							controls: [
								{
									label: __("Drop shadow", 'greyd_hub'),
									attribute: "--popover-box-shadow",
									control: greyd.components.DropShadowControl,
								}
							]
						} ),

						// padding
						el( greyd.components.StylingControlPanel, {
							title: __('Padding', 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [
								{
									label: __("Inside", 'greyd_hub'),
									attribute: "--popover-padding",
									control: greyd.components.DimensionControl,
								}
							]
						} )
					] : null,
				] ),

				// placeholder
				!hasImage ? el( wp.components.Placeholder, {
					// render placeholder
					icon: greyd.tools.getBlockIcon('hotspot'),
					label: __( "Select background", 'greyd_hub' ),
					instructions: __( "First select a background image or animation to start.", 'greyd_hub' ),
				}, [
					// choose template
					el( wp.blockEditor.MediaUploadCheck, {
						fallback: el(
							'p',
							{ className: "greyd-inspector-help" },
							__("To edit the background image, you need permission to upload media.", 'greyd_hub')
						)
					}, [
						el( wp.blockEditor.MediaUpload, {
							allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
							value: _.has(atts, 'image.id') ? atts.image.id : -1,
							onSelect: function(value) { props.setAttributes( { image: { id: value.id, url: value.url }, mediaType: "image" } ); },
							render: function(obj) {
								return el( wp.components.Button, { 
									className: 'is-primary',
									onClick: obj.open 
								}, __( "Select image", 'greyd_hub' ) )
							},
						} ),
						(typeof bodymovin !== 'undefined') ? el( wp.blockEditor.MediaUpload, {
							allowedTypes: 'application/json',
							value: _.has(atts, 'animation.id') ? atts.animation.id : -1,
							onSelect: function(value) { 
								greyd.lottie.set(anim_ids, value.url);
								props.setAttributes( { animation: { id: value.id, url: value.url }, mediaType: "animation" } );
							},
							render: function(obj) {
								return el( wp.components.Button, { 
									className: 'is-primary',
									onClick: obj.open 
								}, __( "Select animation", 'greyd_hub' ) )
							},
						} ) : '',
					] ),
				] ) : null,

				// image preview
				hasImage ? [
					
					el( 'div', {
						...blockProps,
						'data-show-triangle': atts.showTriangle,

						/**
						 * Handle the mouse events of the wrapper:
						 * 
						 * * @param onClick either inserts a spot or closes all spots.
						 * * the start of the drag-event is handled from the spot itself.
						 * * @param onMouseMove handles the actual drag-event.
						 * * the stop of the drag-event is triggered from the spot itself.
						 */
						onClick: (e) => {
							// console.log( 'onClick', props.isSelected, e.target )

							const parentClass = e.target && e.target.offsetParent && e.target.offsetParent.offsetParent ? String(e.target.offsetParent.offsetParent.className) : '';

							if ( parentClass.indexOf('greyd-media-wrapper') === -1 ) return;

							if ( e.altKey && props.isSelected ) {

								// insert hotspot
								const position = getPosition( e );
								console.log( 'Insert hotspot at:', position );
				
								const innerCount = wp.data.select("core/block-editor").getBlocksByClientId( props.clientId )[0].innerBlocks.length;
								let block = wp.blocks.createBlock( "greyd/hotspot", { position: position } );
		
								wp.data.dispatch("core/block-editor").insertBlock( block, innerCount, props.clientId );
							}
							else {
								// if clicked outside close all popovers
								const wrapper = wp.data.select("core/block-editor").getBlocksByClientId(props.clientId)[0];
								const hotspots = wrapper.innerBlocks;
								hotspots.forEach( hotspot => {
									wp.data.dispatch('core/block-editor').updateBlockAttributes( hotspot.clientId, { active: false } );
								});
							}

							/* wire event to lottie player */
							if ( e.target?.classList.contains('lottie-animation') ) return;
							// console.log("click", e);
							var element = e.target?.closest('.greyd-media-wrapper')?.querySelector('.lottie-animation');
							if ( element ) element.dispatchEvent( new MouseEvent( 'click', { bubbles: true, view: e.view } ) );
						},
						onMouseMove: (e) => {
							if ( atts.isDragging && !atts.locked ) {
								const blockAtts = wp.data.select('core/block-editor').getBlockAttributes( atts.targetId );
								// isDragging -> setPositionChanged( true ) in child
								wp.data.dispatch('core/block-editor').updateBlockAttributes( atts.targetId, {
									position: {
										..._.get(blockAtts, 'position'),
										...getPosition( e )
									},
									isPositionChanged: true,
									active: false
								} );
							} 

							/* wire event to lottie player */
							if ( e.target?.classList.contains('lottie-animation') ) return;
							// console.log("move", e);
							var element = e.target?.closest('.greyd-media-wrapper')?.querySelector('.lottie-animation');
							if ( element ) {
								element.dispatchEvent( new MouseEvent( 'mousemove', { bubbles: true, view: e.view, clientX: e.clientX, clientY: e.clientY } ) );
							}
						},
						onMouseEnter: (e) => {
							/* wire event to lottie player */
							if ( e.target?.classList.contains('lottie-animation') ) return;
							// console.log("enter", e);
							var element = e.target?.closest('.greyd-media-wrapper')?.querySelector('.lottie-animation');
							if ( element ) element.dispatchEvent( new MouseEvent( 'mouseenter', { bubbles: true, view: e.view } ) );
						},
						onMouseLeave: (e) => {
							/* wire event to lottie player */
							if ( e.target?.classList.contains('lottie-animation') ) return;
							// console.log("leave", e);
							var element = e.target?.closest('.greyd-media-wrapper')?.querySelector('.lottie-animation');
							if ( element ) element.dispatchEvent( new MouseEvent( 'mouseleave', { bubbles: true, view: e.view } ) );
						}
					}, [
						atts.mediaType == "image" ? [
							// img
							el('img', { 
								src: atts.image.url,
								className: atts.customMobile ? "hotspot-desktop-image hide-on-mobile" : '',
							}),
							atts.customMobile ? el('img', { 
								src: atts.mobileImage.url,
								className: "hotspot-mobile-image hide-on-desktop",
							}) : ''
						] : [
							// anim
							el( 'div', { 
								id: anim_ids[0], 
								className: 'lottie-animation '+atts.anim, 
								"data-anim": atts.anim,
								'data-src': atts.animation.url 
							} )
						],

						el( wp.blockEditor.InnerBlocks, {
							allowedBlocks: ['greyd/hotspot'],
							renderAppender: false
						}),
						
						// help
						el( wp.components.Button, {
							variant: 'tertiary',
							className: 'hotspot-info-button',
							icon: 'editor-help',
							onClick: () => {
								greyd.tools.showSnackbar( __( "To add a hotspot, Alt-click (Windows) or Option-click (Mac) on the image. To remove a hotspot, click on the corresponding hotspot while holding the Shift key", 'greyd_hub' ) );
								// setSnackbarShown(true);
							}
						} )

					] ),
	
					// styles
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass,
						styles: {
							"": atts.wrapperStyles
						}
					}),
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass+" .spot",
						activeSelector: atts.greydClass+" .greyd-hotspot.is-open .spot",
						styles: {
							" ": atts.hotspotStyles,
						},
					}),
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass+" .popover",
						activeSelector: atts.greydClass+" .greyd-hotspot.is-open .popover",
						styles: {
							" ": atts.popoverStyles
						},
					})
				] : null,
			];
			
		},
		save: function( props ) {

			const atts = props.attributes;

			const blockProps = wp.blockEditor.useBlockProps.save();

			return  el( wp.element.Fragment, {}, [

				el('div', { 
					...blockProps,
				}, [
					el('div', {
						className: [ "greyd-media-wrapper", atts.greydClass ].join(' '),
						"data-on-hover": atts.openOnHover,
						"data-autoclose": atts.autoClose,
						'data-show-triangle': atts.showTriangle,
						style: { position: "relative"},
						
					}, [
						atts.mediaType == "image" ? [ el('img', { 
							src: atts.image.url,
							className: atts.customMobile ? "hotspot-desktop-image hide-on-mobile" : '',
							style: { height: "auto", width: "auto", maxWidth: "100%"}
						}),

						atts.customMobile ? el('img', { 
							src: atts.mobileImage.url,
							className: "hotspot-mobile-image hide-on-desktop",
							style: { height: "auto", width: "auto", maxWidth: "100%"}
						}) : '' ] :  
						
						el( 'div', { 
							id: "anim_"+atts.targetId.substring(0,6),
							className: 'lottie-animation '+atts.anim,
							style: { display: "inline-block"},
							"data-anim": atts.animation.anim,
							'data-src': atts.animation.url
						} ) ,

						el( wp.blockEditor.InnerBlocks.Content )
					] ),
					
					el( greyd.components.RenderSavedStyles, {
						selector: atts.greydClass,
						styles: {
							" ": atts.wrapperStyles
						}
					}),
					el( greyd.components.RenderSavedStyles, {
						selector: atts.greydClass+" .spot",
						activeSelector: atts.greydClass+" .greyd-hotspot.is-open .spot",
						styles: {
							" ": atts.hotspotStyles,
						},
					}),
					el( greyd.components.RenderSavedStyles, {
						selector: atts.greydClass+" .popover",
						activeSelector: atts.greydClass+" .greyd-hotspot.is-open .popover",
						styles: {
							" ": atts.popoverStyles
						},
					})
				] )
			]);
		},

		deprecated: [

			/**
			 * @since Full Site Editing.
			 * Properly support blockProps.
			 */
			{
				supports: {
					anchor: true,
					align: true,
					defaultStylePicker: false
				},
				attributes: {
					greydClass: { type: 'string', default: '' },
		
					// image
					image: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mobileImage: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mediaType: {type: 'string', default: 'image'},
					customMobile: { type: 'boolean', default: false },
					anim: { type: 'string', default: 'auto' },
					animation: { type: 'object', default: {
						id: -1,
						url: false,
					} },
		
					// settings
					previewHeight: {type: 'integer'},
					openOnHover: { type: 'boolean', default: false },
					autoClose: { type: 'boolean', default: false },
		
					// design
					hotspotType: { type: 'string', default: ''},
					hotspotIcon: { type: 'object', default: {
						icon: 'icon_plus_alt',
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: ''
					}},
					idleAnimation: { type: 'string', default: '' },
					showTriangle: { type: 'boolean', default: true },
		
					// styles
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: {type: 'string', default: '' },
					wrapperStyles: { type: 'object'},
		
					// usability
					isDragging: { type: 'boolean', default: false },
					targetId: { type: 'string', default: '' },
					locked: { type: 'boolean', default: false },
				},
				providesContext: {
					'greyd/hotspot-type': 'hotspotType',
					'greyd/hotspot-icon': 'hotspotIcon',
					'greyd/hotspot-image': 'hotspotImage',
					'greyd/hotspot-popover-position': 'popoverPosition',
					'greyd/hotspot-idle-animation': 'idleAnimation',
					'greyd/hotspot-locked': 'locked',
				},
				save: function( props ) {

					const atts = props.attributes;
					const id = "anim_"+atts.targetId.substring(0,6);
				
					// save id and class
					var classNames = ["greyd-media-wrapper", atts.greydClass, atts.className];
					if ( !_.isEmpty(atts.align) ) classNames.push( "align"+atts.align );
		
					var blockProps = { className: classNames.join(' ') };
					if ( !_.isEmpty(atts.anchor) ) blockProps.id = atts.anchor;
		
					return  el( wp.element.Fragment, {}, [
		
						el('div', { 
							className: "greyd-hotspot-wrapper v2",
						}, [
							el('div', { 
								...blockProps,
								"data-on-hover": atts.openOnHover,
								"data-autoclose": atts.autoClose,
								'data-show-triangle': atts.showTriangle,
								style: { position: "relative"},
								
							}, [
								atts.mediaType == "image" ? [ el('img', { 
									src: atts.image.url,
									className: atts.customMobile ? "hotspot-desktop-image hide-on-mobile" : '',
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}),
		
								atts.customMobile ? el('img', { 
									src: atts.mobileImage.url,
									className: "hotspot-mobile-image hide-on-desktop",
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}) : '' ] :  
								
								el( 'div', { 
									id: id, 
									className: 'lottie-animation '+atts.anim, 
									style: { display: "inline-block"}, 
									"data-anim": atts.animation.anim,
									'data-src': atts.animation.url 
								} ) ,
		
								el( wp.blockEditor.InnerBlocks.Content )
							] ),
							
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									" ": atts.wrapperStyles
								}
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .spot",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .spot",
								styles: {
									" ": atts.hotspotStyles,
								},
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .popover",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .popover",
								styles: {
									" ": atts.popoverStyles
								},
							})
						] )
					]);
				}
			},

			{
				supports: {
					anchor: true,
					align: true,
					defaultStylePicker: false
				},
				attributes: {
					greydClass: { type: 'string', default: '' },
		
					// image
					image: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mobileImage: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mediaType: {type: 'string', default: 'image'},
					customMobile: { type: 'boolean', default: false },
					anim: { type: 'string', default: 'auto' },
					animation: { type: 'object', default: {
						id: -1,
						url: false,
					} },
		
					// settings
					previewHeight: {type: 'integer'},
					openOnHover: { type: 'boolean', default: false },
					autoClose: { type: 'boolean', default: false },
		
					// design
					hotspotType: { type: 'string', default: ''},
					hotspotIcon: { type: 'object', default: {
						icon: 'icon_plus_alt',
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: ''
					}},
					idleAnimation: { type: 'string', default: '' },
					showTriangle: { type: 'boolean', default: true },
		
					// styles
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: {type: 'string', default: '' },
					wrapperStyles: { type: 'object'},
		
					// usability
					isDragging: { type: 'boolean', default: false },
					targetId: { type: 'string', default: '' },
				},
				// old save function
				save: function( props ) {

					const atts = props.attributes;
					const id = "anim_"+props.clientId;
		
					return  el( wp.element.Fragment, {}, [
		
						el('div', { 
							className: "greyd-hotspot-wrapper",
						}, [
							el('div', { 
								className: ["greyd-media-wrapper", atts.greydClass, "align"+atts.align].join(' '), 
								"data-on-hover": atts.openOnHover,
								"data-autoclose": atts.autoClose,
								'data-show-triangle': atts.showTriangle,
								style: { position: "relative"},
								
							}, [
								atts.mediaType == "image" ? [ el('img', { 
									src: atts.image.url,
									className: atts.customMobile ? "hotspot-desktop-image hide-on-mobile" : '',
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}),
		
								atts.customMobile ? el('img', { 
									src: atts.mobileImage.url,
									className: "hotspot-mobile-image hide-on-desktop",
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}) : '' ] :  
								
								el( 'div', { 
									id: id, 
									className: 'lottie-animation '+atts.anim, 
									style: { display: "inline-block"}, 
									"data-anim": atts.animation.anim,
									'data-src': atts.animation.url 
								} ) ,
		
								el( wp.blockEditor.InnerBlocks.Content )
							] ),
							
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									" ": atts.wrapperStyles
								}
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .spot",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .spot",
								styles: {
									" ": atts.hotspotStyles,
								},
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .popover",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .popover",
								styles: {
									" ": atts.popoverStyles
								},
							})
						] )
					]);
				}
			},
			
			{
				supports: {
					anchor: true,
					align: true,
					defaultStylePicker: false
				},
				attributes: {
					greydClass: { type: 'string', default: '' },
		
					// image
					image: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mobileImage: { type: 'object', default: {
						id: -1,
						url: ''
					} },
					mediaType: {type: 'string', default: 'image'},
					customMobile: { type: 'boolean', default: false },
					anim: { type: 'string', default: 'auto' },
					animation: { type: 'object', default: {
						id: -1,
						url: false,
					} },
		
					// settings
					previewHeight: {type: 'integer'},
					openOnHover: { type: 'boolean', default: false },
					autoClose: { type: 'boolean', default: false },
		
					// design
					hotspotType: { type: 'string', default: ''},
					hotspotIcon: { type: 'object', default: {
						icon: 'icon_plus_alt',
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: ''
					}},
					idleAnimation: { type: 'string', default: '' },
					showTriangle: { type: 'boolean', default: true },
		
					// styles
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: {type: 'string', default: '' },
					wrapperStyles: { type: 'object'},
		
					// usability
					isDragging: { type: 'boolean', default: false },
					targetId: { type: 'string', default: '' },
					locked: { type: 'boolean', default: false },
				},
				save: function( props ) {

					const atts = props.attributes;
					const id = "anim_"+props.clientId;
		
					// save id and class
					var classNames = ["greyd-media-wrapper", atts.greydClass, atts.className];
					if ( !_.isEmpty(atts.align) ) classNames.push( "align"+atts.align );
		
					var blockProps = { className: classNames.join(' ') };
					if ( !_.isEmpty(atts.anchor) ) blockProps.id = atts.anchor;
		
					return  el( wp.element.Fragment, {}, [
		
						el('div', { 
							className: "greyd-hotspot-wrapper v2",
						}, [
							el('div', { 
								...blockProps,
								"data-on-hover": atts.openOnHover,
								"data-autoclose": atts.autoClose,
								'data-show-triangle': atts.showTriangle,
								style: { position: "relative"},
								
							}, [
								atts.mediaType == "image" ? [ el('img', { 
									src: atts.image.url,
									className: atts.customMobile ? "hotspot-desktop-image hide-on-mobile" : '',
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}),
		
								atts.customMobile ? el('img', { 
									src: atts.mobileImage.url,
									className: "hotspot-mobile-image hide-on-desktop",
									style: { height: "auto", width: "auto", maxWidth: "100%"}
								}) : '' ] :  
								
								el( 'div', { 
									id: id, 
									className: 'lottie-animation '+atts.anim, 
									style: { display: "inline-block"}, 
									"data-anim": atts.animation.anim,
									'data-src': atts.animation.url 
								} ) ,
		
								el( wp.blockEditor.InnerBlocks.Content )
							] ),
							
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								styles: {
									" ": atts.wrapperStyles
								}
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .spot",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .spot",
								styles: {
									" ": atts.hotspotStyles,
								},
							}),
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass+" .popover",
								activeSelector: atts.greydClass+" .greyd-hotspot.is-open .popover",
								styles: {
									" ": atts.popoverStyles
								},
							})
						] )
					]);
				},
			}
			
		]

	} );


	/**
	 * Register the hotspots.
	 */
	wp.blocks.registerBlockType( 'greyd/hotspot', {
		apiVersion: 2,
		title: __('Hotspot Spot', 'greyd_hub'),
		description: __("Create a clickable hotspot with a popover.", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('hotspotItem'),
		category: 'greyd-blocks',
		parent: ['greyd/hotspot-wrapper'],
		attributes: {
			position: { type: 'object', default: {
				left: null, 
				right: null
			} },
			hotspotType: { type: 'string', default: ""},
			hotspotIcon: { type: 'object', default: {
				icon: ''
			}},
			hotspotImage: { type: 'object', default: {
				id: -1,
				url: '',
				size: "20px"
			}},
			idleAnimation: { type: 'string', default: ""},
			greydClass: { type: 'string', default: ''},
			hotspotStyles: { type: 'object'},
			popoverStyles: { type: 'object'},
			popoverPosition: { type: 'string', default: '' },
			custom: { type: 'boolean', default: false },
			animationDelay: { type: 'int' },

			// usability
			active: { type: 'boolean', default: false },
			isDragging: { type: 'boolean', default: false },
			isPositionChanged: { type: 'boolean', default: false },
		},
		usesContext: [
			'greyd/hotspot-type', 
			'greyd/hotspot-icon',
			'greyd/hotspot-image',
			'greyd/hotspot-popover-position',
			'greyd/hotspot-popover-offset',
			'greyd/hotspot-idle-animation',
			'greyd/hotspot-locked',
		],

		edit: function( props ) {
		
			const atts = props.attributes;
			
			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const parentId = wp.data.select("core/block-editor").getBlockParentsByBlockName(props.clientId, "greyd/hotspot-wrapper");
			const parent = wp.data.select("core/block-editor").getBlocksByClientId(parentId)[0];
			
			const hasChildBlocks = greyd.tools.hasChildBlocks(props.clientId);

			// check if context has changed
			if ( !atts.custom ) {

				const newAtts = {};

				if (atts.hotspotType != props.context['greyd/hotspot-type'] && !atts.custom) {
					newAtts.hotspotType = props.context['greyd/hotspot-type'];
				}
				if (atts.hotspotIcon != props.context['greyd/hotspot-icon'] && !atts.custom) {
					newAtts.hotspotIcon = props.context['greyd/hotspot-icon'];
				}
				if (atts.hotspotImage != props.context['greyd/hotspot-image'] && !atts.custom) {
					newAtts.hotspotImage = props.context['greyd/hotspot-image'];
				}
				if (atts.popoverPosition != props.context['greyd/hotspot-popover-position'] && !atts.custom) {
					newAtts.popoverPosition = props.context['greyd/hotspot-popover-position'];
				}
				if (atts.idleAnimation != props.context['greyd/hotspot-idle-animation'] && !atts.custom) {
					newAtts.idleAnimation = props.context['greyd/hotspot-idle-animation'];
				}

				if ( !_.isEmpty(newAtts) ) {
					props.setAttributes( newAtts );
				}
			}
			
			// mode for section settings
			var [mode, setMode ] = wp.element.useState("");
			if (!props.isSelected && mode != "") setMode("");

			const allBlocks = wp.blocks.getBlockTypes().map( block => block.name);
			const disallowedBlocks = ['greyd/hotspot-wrapper','greyd/hotspot'];
			const allowedBlocks = allBlocks.filter(function(value, index) {
				return disallowedBlocks.indexOf(value) == -1;
			});

			// make blockProps to render class
			var classNames = ["greyd-hotspot", atts.greydClass, atts.className];
			if ( atts.active ) classNames.push( "is-open" );
			if ( atts.isDragging ) classNames.push( "isDragging" );

			const blockProps = wp.blockEditor.useBlockProps({ className: classNames.join(' ') });

			var locked = props.context['greyd/hotspot-locked'];

			return [

				// toolbar
				el( wp.blockEditor.BlockControls, { group: 'inline' }, [
					el( wp.components.ToolbarButton, {
						label: locked ? __("Unlock hotspot positions", 'greyd_hub') : __("Lock hotspot positions", 'greyd_hub'),
						icon: locked ? greyd.tools.getCoreIcon('locked') : greyd.tools.getCoreIcon('unlocked'),
						isPressed: locked,
						onClick: () => { 
							// props.setAttributes( { locked: !props.attributes.locked } );
							wp.data.dispatch('core/block-editor').updateBlockAttributes(parent.clientId, { locked: !locked });
						}
					} ),
				] ),

				// sidebar
				el( wp.blockEditor.InspectorControls, {}, [

					mode == '' ? [

						// focus parent
						el( wp.components.PanelBody, { }, [
							el( 'p', { className: "greyd-inspector-help" }, __("You can set the general behavior in the parent hotspot block.", 'greyd_hub') ),
							el( wp.components.Button, {
								variant: 'secondary',
								icon: 'visibility',
								onClick: () => {
									wp.data.dispatch('core/block-editor').selectBlock(
										wp.data.select('core/block-editor').getBlockParents(props.clientId).slice(-1).pop()
									)
								}
							}, __("Focus hotspot block", 'greyd_hub') )
						] ),

						el( greyd.components.StylingControlPanel, {
							title: __('Position', 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: 'position',
							controls: [
								{
									className: locked ? 'disabled' : '',
									label: __("From left", 'greyd_hub'),
									attribute: "left",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
									units: ['%']
								},
								{
									className: locked ? 'disabled' : '',
									label: __("From top", 'greyd_hub'),
									attribute: "top",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
									units: ['%']
								},
								{
									className: locked ? 'components-tip' : 'components-tip hidden',
									control: 'p',
									children: __("The position cannot be changed because the hotspots are locked.", 'greyd_hub')
								},
								{
									className: locked ? '' : 'hidden',
									control: wp.components.Button,
									variant: 'secondary',
									icon: greyd.tools.getCoreIcon('unlocked'),
									onClick: () => {
										wp.data.dispatch('core/block-editor').updateBlockAttributes(parent.clientId, { locked: !locked });
									},
									children: __("Unlock all hotspots", 'greyd_hub')
								},
							]
						} ),
						
						// design
						el( greyd.components.AdvancedPanelBody, {
							title: __( 'Design', 'greyd_hub' ),
							initialOpen: true,
							holdsChange: atts.custom || atts.animationDelay,
						}, [

							el( wp.components.RangeControl, {
								label: __("Delay of the animation (ms)", 'greyd_hub'),
								min: 500,
								max: 5000,
								step: 100,
								value: atts.animationDelay,
								onChange: (value) => {
									props.setAttributes( { animationDelay: value } )
								}
							} ),


							// custom ?
							el( wp.components.ToggleControl, {
								label: __( "Overwrite the design of the hotspot individually", 'greyd_hub' ),
								checked: atts.custom,
								onChange: function(value) {
									props.setAttributes( { custom: !!value } );
								},
							} ),

							atts.custom ? [

								// type
								el( greyd.components.ButtonGroupControl, {
									value: atts.hotspotType,
									options: [
										{ label: __('Element', 'greyd_hub'), value: '' },
										{ label: __('Icon', 'greyd_hub'), value: 'icon' },
										{ label: __("Image", 'greyd_hub'), value: 'image' },
									],
									onChange: function(value) { props.setAttributes( { hotspotType: value } ); },
								} ),
							
								// icon
								atts.hotspotType == "icon" ? el( greyd.components.IconPicker, {
									value: _.has(atts, 'hotspotIcon.icon') ? atts.hotspotIcon.icon : '',
									icons: greyd.data.icons,
									onChange: (value) => { 
										props.setAttributes({ hotspotIcon: { ...atts.hotspotIcon, icon: value } }); 
									},
								} ) : null,

								// image
								atts.hotspotType == "image" ? el( wp.components.BaseControl, { }, [
									el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) },
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
											value: _.has(atts, 'hotspotImage.id') ? atts.hotspotImage.id : -1,
											onSelect: function(value) { props.setAttributes( { hotspotImage: { id: value.id, url: value.url } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: !_.has(atts, 'hotspotImage.id') || atts.hotspotImage.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, !_.has(atts, 'hotspotImage.id') || atts.hotspotImage.id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: atts.hotspotImage.url } ) )
											},
										} ),
										_.has(atts, 'hotspotImage.id') && atts.hotspotImage.id !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { props.setAttributes( { hotspotImage: { id: -1, url: "" } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : ""
									),
								] ) : null,

								// to hotspot settings
								el( greyd.components.SectionControl, {
									title: __("Hotspot design", 'greyd_hub'),
									onClick: () => setMode("hotspot")
								} ),

								// to popover settings
								el( greyd.components.SectionControl, {
									title: __("Popover design", 'greyd_hub'),
									onClick: () => { 
										props.setAttributes( { active: true } )
										setMode("popover");
									}
								} ),
							] : null
						] )
					] : null,

					// hotspot settings
					mode == "hotspot" ? [ 

						el( greyd.components.SectionControl, {
							title: __('Hotspot', 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),
							
						atts.hotspotType !== "image" ? el( greyd.components.StylingControlPanel, {
							title: __("Color", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							supportsActive: true,
							blockProps: props,
							parentAttr: 'hotspotStyles',
							controls: atts.hotspotType === "icon" ? [
								{
									label: __("Icon color", 'greyd_hub'),
									attribute: "--hotspot-background-color",
									control: greyd.components.ColorPopupControl
								},
							] : [
								{
									label: __("Inner color", 'greyd_hub'),
									attribute: "--hotspot-background-color",
									control: greyd.components.ColorPopupControl
								},
								{
									label: __("Outer color", 'greyd_hub'),
									attribute: "--hotspot-outline-color",
									control: greyd.components.ColorPopupControl
								},
							]
						} ) : null,

						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							blockProps: props,
							parentAttr: 'hotspotStyles',
							controls: [
								{
									label: __("Size", 'greyd_hub'),
									attribute: "--hotspot-size",
									control: greyd.components.RangeUnitControl,
									max: { px: 100, em: 4 },
									units: ['px', 'em']
								},
								...atts.hotspotType == "" ? [
									{
										label: __("Between inside and outside", 'greyd_hub'),
										attribute: "--hotspot-outline-offset",
										control: greyd.components.RangeUnitControl,
										supportsPresets: true,
										units: ['px'],
										max: 40
									}
								] : [],
							]
						} ),

						// animation
						el( greyd.components.AdvancedPanelBody, {
							title: __( 'Animation', 'greyd_hub' ),
							initialOpen: true,
							holdsChange: !_.isEmpty(atts.idleAnimation)
						}, [
							el( wp.components.SelectControl, {
								value: atts.idleAnimation,
								options: [
									{ label: __("None", 'greyd_hub'), value: '' }, 
									{ label: __("Pulse", 'greyd_hub'), value: 'hotspot-pulse' }, 
									{ label: __("Flash", 'greyd_hub'), value: 'hotspot-blink' }, 
									{ label: __("Flutter", 'greyd_hub'), value: 'hotspot-wobble' }, 
	
								],
								onChange: function(value) { props.setAttributes( { idleAnimation: value } ); },
							} ),
						] ),

						atts.hotspotType == "" ? [
							el( greyd.components.StylingControlPanel, {
								title: __("Border radius", 'greyd_hub'),
								initialOpen: false,
								blockProps: props,
								parentAttr: 'hotspotStyles',
								controls: [ {
									label: __("Border radius", 'greyd_hub'),
									attribute: "--hotspot-border-radius",
									control: greyd.components.DimensionControl,
									labels: {
										"all": __("All corners", "greyd_hub"),
									},
									sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
									type: "string"
								} ]
							} ),
						] : null
					] : null,

					// popover settings
					mode == "popover" ? [ 

						el( greyd.components.SectionControl, {
							title: __('Popover', 'greyd_hub'),
							icon: 'arrow-left-alt',
							buttonText: __("Back", 'greyd_hub'),
							onClick: () => setMode(""),
							isHeader: true
						} ),

						el( greyd.components.AdvancedPanelBody, {
							title: __( 'Position', 'greyd_hub' ),
							initialOpen: true
						}, [
							el( greyd.components.ButtonGroupControl, {
								// title: __("Horizontally", 'greyd_hub'),
								value: atts.popoverPosition,
								options: [
									{ label: __("Bottom", 'greyd_hub'), value: '' },
									{ label: __("Top", 'greyd_hub'), value: 'position-top' },
									{ label: __("Right", 'greyd_hub'), value: 'position-right' },
									{ label: __("Left", 'greyd_hub'), value: 'position-left' },
								],
								onChange: (value) => props.setAttributes( { popoverPosition: value })
							} ),
							el( greyd.components.RangeUnitControl, {
								label: __("Space", 'greyd_hub'),
								value: _.has(atts.popoverStyles, "--popover-offset") ? atts.popoverStyles["--popover-offset"] : null,
								unit: ['px', 'em'],
								max: { px: 80, em: 2 },
								min: -50,
								onChange: (value) => props.setAttributes( { popoverStyles: { ...atts.popoverStyles, "--popover-offset": value } }),
								supportsPresets: true,
							}),
						] ),

						// color
						el( greyd.components.StylingControlPanel, {
							title: __("Color", 'greyd_hub'),
							initialOpen: true,
							supportsHover: true,
							blockProps: props,
							parentAttr: 'popoverStyles',
							controls: [
								{
									label: __('Text', 'greyd_hub'),
									attribute: "--popover-text-color",
									control: greyd.components.ColorPopupControl
								},
								{
									label: __("Background", 'greyd_hub'),
									attribute: "--popover-background-color",
									control: greyd.components.ColorPopupControl
								},
							]
						} ),

						// width
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [ 
								{
									label: __("Maximum width", 'greyd_hub'),
									min: _.has(atts.popoverStyles, "--popover-min-width") ? atts.popoverStyles["--popover-min-width"] : 250,
									max: 800,
									attribute: "--popover-max-width",
									control: greyd.components.RangeUnitControl,
								},
								{
									label: __("Minimum width", 'greyd_hub'),
									max: 800,
									attribute: "--popover-min-width",
									control: greyd.components.RangeUnitControl,
								} 
							]
						} ),

						// radius
						el( greyd.components.StylingControlPanel, {
							title: __("Border radius", 'greyd_hub'),
							initialOpen: false,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [ {
								label: __("Border radius", 'greyd_hub'),
								attribute: "--popover-border-radius",
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
							parentAttr: "popoverStyles",
							supportsHover: true,
							sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
							controls: [
								{
									label: __("Border", 'greyd_hub'),
									attribute: "--popover-border",
									control: greyd.components.BorderControl,
								}
							]
						} ),
						// shadow + hover
						el( greyd.components.StylingControlPanel, {
							title: __("Shadow", 'greyd_hub'),
							blockProps: props,
							parentAttr: "popoverStyles",
							supportsHover: true,
							controls: [
								{
									label: __("Drop shadow", 'greyd_hub'),
									attribute: "--popover-box-shadow",
									control: greyd.components.DropShadowControl,
								}
							]
						} ),

						// greydStyles + responsive
						el( greyd.components.StylingControlPanel, {
							title: __('Padding', 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: props,
							parentAttr: "popoverStyles",
							controls: [
								{
									label: __("Inside", 'greyd_hub'),
									attribute: "--popover-padding",
									control: greyd.components.DimensionControl,
								}
							]
						} )
					] : null,
				]),

				// preview
				el('div', {
					...blockProps,
					...atts.animationDelay ? {
						style: {
							'--animation-delay' : atts.animationDelay+'ms'
						}
					} : null,

					/**
					 * Handle the mouse events of the hotspot.
					 * 
					 * * @param onMouseDown starts the mouse-event on this spot.
					 * * the actual drag-event is handled in the parent wrapper.
					 * * @param onMouseUp stops the mouse-event on this spot.
					 */
					onMouseDown: (e) => {

						if (e.shiftKey) {
							// remove spot
							wp.data.dispatch( 'core/block-editor' ).removeBlock( props.clientId );
						} 
						else {
							const currentClass = e.target ? String(e.target.className) : '';
							const parentClass  = e.target && e.target.offsetParent ? String(e.target.offsetParent.className) : '';
							
							if ( currentClass.indexOf("greyd-hotspot") > -1 || parentClass.indexOf("greyd-hotspot") > -1 ) {

								// start dragging -> setDragging( true ) in parent
								if ( !props.context['greyd/hotspot-locked'] )
									wp.data.dispatch('core/block-editor').updateBlockAttributes(parent.clientId, { isDragging: true, targetId: props.clientId });
								props.setAttributes({ isDragging: true });
							}
						}
					},
					onMouseUp: (e) => {
						if ( atts.isDragging ) {
							const currentClass = e.target ? String(e.target.className) : '';
							const parentClass  = e.target && e.target.offsetParent ? String(e.target.offsetParent.className) : '';
							
							if ( currentClass.indexOf("greyd-hotspot") > -1 || parentClass.indexOf("greyd-hotspot") > -1 ) {

								// stop dragging -> setDragging( false ) in parent
								wp.data.dispatch('core/block-editor').updateBlockAttributes(parent.clientId, { isDragging: false });

								// close all popovers if position changed
								// if ( atts.isPositionChanged ) {
								// 	const hotspots = parent.innerBlocks;
								// 	hotspots.forEach( hotspot => {
								// 		wp.data.dispatch('core/block-editor').updateBlockAttributes(hotspot.clientId, {active: false});
								// 	});
								// }

								props.setAttributes({ isDragging: false, isPositionChanged: false, active: !atts.active });

								// select this block
								wp.data.dispatch('core/block-editor').selectBlock( props.clientId );
							}
						}
					}
				}, [

					// spot
					el('div', {
						className: [
							'spot',
							_.isEmpty(atts.idleAnimation) ? '' : atts.idleAnimation,
							atts.hotspotType == "icon" ? atts.hotspotIcon.icon : ''
						].join(' '),
						'data-type': _.isEmpty(atts.hotspotType) ? 'element' : atts.hotspotType,
						style: atts.hotspotType == "image" ? {
							backgroundImage: "url("+atts.hotspotImage.url+")",
							width: atts.hotspotImage.size,
							height: atts.hotspotImage.size,
							..._.isEmpty(atts.hotspotImage.url) ? {
								outline: '1px dashed var(--hotspot-background-color, var(--wp--preset--color--darkest))'
							} : {}
						} : null
					}),

					// popover
					atts.active ? el('div', {
						className: "popover "+atts.popoverPosition,
					}, [
						el( wp.blockEditor.InnerBlocks, {
							renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender,
							allowedBlocks: allowedBlocks,
						})
					] ) : null
				] ),

				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: {
						"": atts.position,
					}
				}),

				// custom styles
				atts.custom ? [
		
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass + ".greyd-hotspot .spot",
						activeSelector: atts.greydClass + ".greyd-hotspot.is-open .spot",
						styles: {
							" ": atts.hotspotStyles,
						}
					}),
		
					el( greyd.components.RenderPreviewStyles, {
						selector: atts.greydClass + ".greyd-hotspot .popover",
						styles: {
							" ": atts.popoverStyles
						}
					})
				] : ''
			];
			
		},
		save: function( props ) {

			const atts = props.attributes;

			// save class
			var classNames = ["greyd-hotspot", "v2", atts.greydClass, atts.className];

			var classNamesButton = ['spot'];
			if ( !_.isEmpty(atts.idleAnimation) ) classNames.push( atts.idleAnimation );
			if ( atts.hotspotType == "icon" ) classNamesButton.push( atts.hotspotIcon.icon );

			return  el( wp.element.Fragment, {}, [
				el('div', {
					className: classNames.join(' '),
					...atts.animationDelay ? {
						style: {
							'--animation-delay' : atts.animationDelay+'ms'
						}
					} : null,
				}, [

					// spot
					el( 'button', {
						className: classNamesButton.join(' '),
						'data-type': _.isEmpty(atts.hotspotType) ? 'element' : atts.hotspotType,
						style: atts.hotspotType == "image" ? {
							backgroundImage: "url("+atts.hotspotImage.url+")",
							width: atts.hotspotImage.size,
							height: atts.hotspotImage.size
						} : null
					}),

					// popover
					el( 'dialog', {
						className: "popover " + ( atts.popoverPosition ?? '' ),
					}, el( wp.blockEditor.InnerBlocks.Content ) ),

					// styles
					el( greyd.components.RenderSavedStyles, {
						selector: atts.greydClass,
						activeSelector: atts.greydClass + ".is-open",
						styles: {
							" ": atts.position,
							...(
								atts.custom
								? {
									".greyd-hotspot .spot": atts.hotspotStyles,
									".greyd-hotspot .popover": atts.popoverStyles
								}
								: null
							)
						}
					})
				] )
			]);

		},

		deprecated: [
			{
				attributes: {
					position: { type: 'object', default: {
						left: null, 
						right: null
					} },
					hotspotType: { type: 'string', default: ""},
					hotspotIcon: { type: 'object', default: {
						icon: ''
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: '',
						size: "20px"
					}},
					idleAnimation: { type: 'string', default: ""},
					greydClass: { type: 'string', default: ''},
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: { type: 'string', default: '' },
					custom: { type: 'boolean', default: false },
					animationDelay: { type: 'int' },
		
					// usability
					active: { type: 'boolean', default: false },
					isDragging: { type: 'boolean', default: false },
					isPositionChanged: { type: 'boolean', default: false },
				},
				// old save function
				save: function( props ) {
		
					const atts = props.attributes;
		
					return  el( wp.element.Fragment, {}, [
						el('div', {
							className: [ atts.greydClass, "greyd-hotspot" ].join(' '),
							...atts.animationDelay ? {
								style: {
									'--animation-delay' : atts.animationDelay+'ms'
								}
							} : null,
						}, [
		
							// spot
							el( 'button', {
								className: [
									'spot',
									_.isEmpty(atts.idleAnimation) ? '' : atts.idleAnimation,
									atts.hotspotType == "icon" ? atts.hotspotIcon.icon : ''
								].join(' '),
								'data-type': _.isEmpty(atts.hotspotType) ? 'element' : atts.hotspotType,
								style: atts.hotspotType == "image" ? {
									backgroundImage: "url("+atts.hotspotImage.url+")",
									width: atts.hotspotImage.size,
									height: atts.hotspotImage.size
								} : null
							}),
		
							// popover
							el( 'dialog', {
								className: "popover " + atts.popoverPosition,
							}, el( wp.blockEditor.InnerBlocks.Content ) ),
		
							// styles
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								activeSelector: atts.greydClass + ".is-open",
								styles: {
									" ": atts.position,
									...(
										atts.custom
										? {
											".greyd-hotspot .spot": atts.hotspotStyles,
											".greyd-hotspot .popover": atts.popoverStyles
										}
										: null
									)
								}
							})
						] )
					]);
		
				}
			},
			{		
				attributes: {
					position: { type: 'object', default: {
						left: null, 
						right: null
					} },
					hotspotType: { type: 'string', default: ""},
					hotspotIcon: { type: 'object', default: {
						icon: ''
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: '',
						size: "20px"
					}},
					idleAnimation: { type: 'string', default: ""},
					greydClass: { type: 'string', default: ''},
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: { type: 'string', default: '' },
					custom: { type: 'boolean', default: false },
					animationDelay: { type: 'int' },
		
					// usability
					active: { type: 'boolean', default: false },
					isDragging: { type: 'boolean', default: false },
					isPositionChanged: { type: 'boolean', default: false },
				},
				save: function( props ) {

					const atts = props.attributes;
		
					// save class
					var classNames = ["greyd-hotspot", "v2", atts.greydClass, atts.className];
		
					var classNamesButton = ['spot'];
					if ( !_.isEmpty(atts.idleAnimation) ) classNames.push( atts.idleAnimation );
					if ( atts.hotspotType == "icon" ) classNames.push( atts.hotspotIcon.icon );
		
					return  el( wp.element.Fragment, {}, [
						el('div', {
							className: classNames.join(' '),
							...atts.animationDelay ? {
								style: {
									'--animation-delay' : atts.animationDelay+'ms'
								}
							} : null,
						}, [
		
							// spot
							el( 'button', {
								className: classNamesButton.join(' '),
								'data-type': _.isEmpty(atts.hotspotType) ? 'element' : atts.hotspotType,
								style: atts.hotspotType == "image" ? {
									backgroundImage: "url("+atts.hotspotImage.url+")",
									width: atts.hotspotImage.size,
									height: atts.hotspotImage.size
								} : null
							}),
		
							// popover
							el( 'dialog', {
								className: "popover " + atts.popoverPosition,
							}, el( wp.blockEditor.InnerBlocks.Content ) ),
		
							// styles
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								activeSelector: atts.greydClass + ".is-open",
								styles: {
									" ": atts.position,
									...(
										atts.custom
										? {
											".greyd-hotspot .spot": atts.hotspotStyles,
											".greyd-hotspot .popover": atts.popoverStyles
										}
										: null
									)
								}
							})
						] )
					]);
		
				},
			}, 
			{	
				attributes: {
					position: { type: 'object', default: {
						left: null, 
						right: null
					} },
					hotspotType: { type: 'string', default: ""},
					hotspotIcon: { type: 'object', default: {
						icon: ''
					}},
					hotspotImage: { type: 'object', default: {
						id: -1,
						url: '',
						size: "20px"
					}},
					idleAnimation: { type: 'string', default: ""},
					greydClass: { type: 'string', default: ''},
					hotspotStyles: { type: 'object'},
					popoverStyles: { type: 'object'},
					popoverPosition: { type: 'string', default: '' },
					custom: { type: 'boolean', default: false },
					animationDelay: { type: 'int' },
		
					// usability
					active: { type: 'boolean', default: false },
					isDragging: { type: 'boolean', default: false },
					isPositionChanged: { type: 'boolean', default: false },
				},
				save: function( props ) {

					const atts = props.attributes;
		
					// save class
					var classNames = ["greyd-hotspot", "v2", atts.greydClass, atts.className];
		
					var classNamesButton = ['spot'];
					if ( !_.isEmpty(atts.idleAnimation) ) classNames.push( atts.idleAnimation );
					if ( atts.hotspotType == "icon" ) classNames.push( atts.hotspotIcon.icon );
		
					return  el( wp.element.Fragment, {}, [
						el('div', {
							className: classNames.join(' '),
							...atts.animationDelay ? {
								style: {
									'--animation-delay' : atts.animationDelay+'ms'
								}
							} : null,
						}, [
		
							// spot
							el( 'button', {
								className: classNamesButton.join(' '),
								'data-type': _.isEmpty(atts.hotspotType) ? 'element' : atts.hotspotType,
								'aria-label': __("Open popover", 'greyd_hub'),
								style: atts.hotspotType == "image" ? {
									backgroundImage: "url("+atts.hotspotImage.url+")",
									width: atts.hotspotImage.size,
									height: atts.hotspotImage.size
								} : null
							}),
		
							// popover
							el( 'dialog', {
								className: "popover " + atts.popoverPosition,
							}, el( wp.blockEditor.InnerBlocks.Content ) ),
		
							// styles
							el( greyd.components.RenderSavedStyles, {
								selector: atts.greydClass,
								activeSelector: atts.greydClass + ".is-open",
								styles: {
									" ": atts.position,
									...(
										atts.custom
										? {
											".greyd-hotspot .spot": atts.hotspotStyles,
											".greyd-hotspot .popover": atts.popoverStyles
										}
										: null
									)
								}
							})
						] )
					]);
		
				},
			}
		]

	} );

} )( window.wp );