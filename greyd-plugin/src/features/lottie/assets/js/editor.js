/**
 * Greyd.Blocks Editor Script for Lottie Animation Block.
 *
 * This file is loaded in block editor pages and modifies the editor experience.
 */

( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Stop loading if Lottie is disabled.
	 */
	if (greyd.data.settings.lottie?.mode == 'disable') return

	/**
	 * Register Animation block.
	 */
	wp.blocks.registerBlockType( 'greyd/anim', {
		apiVersion: 2,
		title: __('Animation', 'greyd_hub'),
		description: __("Include a Lottie/bodymovin JSON animation", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('animation'),
		category: 'greyd-blocks',
		
		supports: {
			anchor: true,
			align: true,
			// spacing: {
			// 	padding: true,
			// }
		},
		attributes: {
			css_animation: { type: 'string' },
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			id: { type: 'integer' },
			url: { 
				type: 'string',
				source: 'attribute',
				selector: '.lottie-animation',
				attribute: 'data-src',
			},
			anim: { type: 'string', default: 'auto' },
			w: { type: 'string', default: 'auto' },
			link: { type: 'object', properties: {
				url: { type: 'string' },
				title: { type: 'string' },
				opensInNewTab: { type: 'boolean' },
			}, default: { url: "", title: "", opensInNewTab: false } },
			greydClass: { type: "string", default: "" },
		},

		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// console.log("init block anim");
			var anim_ids = [ 'anim_'+props.clientId, 'preview_anim_'+props.clientId ];
			greyd.lottie.init(anim_ids);

			// preview wrapper
			var wrapper_atts = {};
			if (_.has(props.attributes, 'link') && props.attributes["link"]["url"] != "") {
				wrapper_atts['style'] = { cursor: 'pointer' };
				wrapper_atts['title'] = "open: "+props.attributes["link"]["title"]+" ("+props.attributes["link"]["url"]+")";
			}
			var blockProps = wp.blockEditor.useBlockProps(wrapper_atts);

			return [

				// sidebar - settings
				el( wp.blockEditor.InspectorControls, {}, [
					el( wp.components.PanelBody, {
						title: __("General", 'greyd_hub'),
						initialOpen: true
					}, [
						el( wp.components.BaseControl, { }, [
							el( wp.blockEditor.MediaUploadCheck, {
								fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the animation, you need permission to upload media.", 'greyd_hub') )
							}, [
								el( wp.blockEditor.MediaUpload, {
									allowedTypes: 'application/json',
									value: props.attributes.id,
									onSelect: function(media) { 
										// console.log('reset '+id);
										// console.log(media.url);
										greyd.lottie.set([anim_ids[0]], media.url);
										props.setAttributes( { 
											id: media.id, 
											url: media.url 
										} ); 
									},
									render: function(obj) {
										if (props.attributes.url) setTimeout(() => {
											greyd.lottie.initAnimations();
										}, 0);
										return el( wp.components.Button, { 
											className: !props.attributes.url ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
											onClick: obj.open 
										}, !props.attributes.url ? __( "Select animation", 'greyd_hub' ) :  el( 'div', { 
											id: anim_ids[1],
											className: 'lottie-animation auto', 
											"data-anim": 'auto',
											'data-src': props.attributes.url 
										} )  )
									},
								} ),
								props.attributes.url ? el( wp.components.Button, { 
									className: "is-link is-destructive",
									onClick: function() { props.setAttributes( { id: false, url: false } ) },
								}, __( "Remove animation", 'greyd_hub' ) ) : ""
							] ),
						] ),
					
						el( 'div', { className: (!props.attributes.url) ? 'hidden' : "" }, [
							el( wp.components.SelectControl, {
								label: __("SVG animation", 'greyd_hub'),
								value: props.attributes.anim,
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
									greyd.lottie.set(anim_ids, props.attributes.url);
									props.setAttributes( { anim: value } );
								},
							} ),
							el( wp.components.__experimentalUnitControl, {
								label: __("Width", 'greyd_hub'),
								labelPosition: 'edge',
								className: 'is-edge-layout',
								value: props.attributes.w,
								onChange: function(value) { props.setAttributes( { w: value } ); },
							} ),
						] ),
					] ),
						
					el( wp.components.PanelBody, {
						title: __( "Link", 'greyd_hub' ),
						className: (!props.attributes.url) ? 'hidden' : "",
						initialOpen: true
					}, [
						el( wp.blockEditor.__experimentalLinkControl, {
							value: props.attributes.link,
							onChange: function( value ) {
								props.setAttributes( { link: value } );
							}
						} ),
						props.attributes.link.url !== "" ? el( wp.components.Button, { 
							className: "is-link is-destructive",
							onClick: function() { props.setAttributes( { link: { url: "", title: "", opensInNewTab: false } } ); },
						}, __( "Clear link", 'greyd_hub' ) ) : "" 
					] ),
				] ),

				// preview
				el( 'div', {
					...blockProps
				}, [
					!props.attributes.url ? el( wp.blockEditor.MediaPlaceholder, {
						labels: { 
							title: __( "Select animation", 'greyd_hub' ),
							instructions: __( "Upload a Lottie/bodymovin animation in JSON format or choose one from your media ibrary.", 'greyd_hub' ),
						},
						allowedTypes: 'application/json',
						value: props.attributes.id,
						onSelect: function(media) {
							// console.log('set '+id);
							// console.log(media.url);
							greyd.lottie.set(anim_ids, media.url);
							return props.setAttributes( {
								id: media.id,
								url: media.url
							} );
						}
					} ) : el( 'div', { 
						id: anim_ids[0],
						className: 'lottie-animation '+props.attributes.anim, 
						style: { display: "inline-block", width: props.attributes.w }, 
						"data-anim": props.attributes.anim,
						'data-src': props.attributes.url 
					} ) 
				] ),
			];
		},
		save: function( props ) {
			// wrapper
			var wrapper_atts = {};
			if (_.has(props.attributes, 'link') && props.attributes["link"]["url"] != "") {
				wrapper_atts['style'] = { cursor: 'pointer' };
				var target = "_self";
				if (_.has(props.attributes["link"], 'opensInNewTab') && props.attributes["link"]["opensInNewTab"]) target = "_blank";
				wrapper_atts['onclick'] = 'window.open("'+props.attributes["link"]["url"]+'", "'+target+'")';
			}
			const blockProps = wp.blockEditor.useBlockProps.save();

			return el( 'div', {
				...blockProps,
				...wrapper_atts
			}, props.attributes.url ? el( 'div', { 
				id: greyd.tools.getGreydClass(props), 
				className: 'lottie-animation '+props.attributes.anim, 
				style: { width: props.attributes.w },
				"data-anim": props.attributes.anim,
				'data-src': props.attributes.url
			} ) : '' );
		},

		deprecated: [
			/**
			 * @deprecated since 1.2.7
			 */
			{
				attributes: {
					dynamic_parent: { type: 'string' }, // dynamic template backend helper
					dynamic_value: { type: 'string' }, // dynamic template frontend helper
					dynamic_fields: { type: 'array' },
					css_animation: { type: 'string' },
					inline_css: { type: 'string' },
					inline_css_id: { type: 'string' },
					id: { type: 'integer' },
					url: { 
						type: 'string',
						source: 'attribute',
						selector: '.bm_icon',
						attribute: 'data-src',
					},
					anim: { type: 'string', default: 'auto' },
					w: { type: 'string', default: 'auto' },
					link: { type: 'object', properties: {
						url: { type: 'string' },
						title: { type: 'string' },
						opensInNewTab: { type: 'boolean' },
					}, default: { url: "", title: "", opensInNewTab: false } },
				},
				save: function( props ) {
					var id = "anim_"+props.clientId;
					var wrapper_atts = { className: props.attributes.className+' vc_icons', style: {} };
					// link
					if (_.has(props.attributes, 'link') && props.attributes["link"]["url"] != "") {
						wrapper_atts['style']['cursor'] = 'pointer';
						var target = "_self";
						if (_.has(props.attributes["link"], 'opensInNewTab') && props.attributes["link"]["opensInNewTab"]) target = "_blank";
						wrapper_atts['onclick'] = 'window.open("'+props.attributes["link"]["url"]+'", "'+target+'")';
					}
					return el( 'div', wrapper_atts, props.attributes.url ? el( 'div', { 
						id: id, 
						className: 'bm_icon '+props.attributes.anim, 
						style: { width: props.attributes.w },
						"data-anim": props.attributes.anim,
						'data-src': props.attributes.url
					} ) : '' );
				}
			}
		]
	} );

	
	/**
	 * ready/init
	 */
	wp.domReady(function () {

		/**
		 * Scan Blocks for invalid 'greyd/anim' Blocks recursively.
		 * Returned ids are used to make a forced Block Recovery.
		 * 
		 * @param {array} blocks 
		 * @returns {array} ids
		 */
		var scanBlocks = function(blocks) {
			var ids = [];
			blocks.forEach(block => {
				if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
					ids = [ ...ids, ...scanBlocks(block.innerBlocks) ];
				}
				if ( block.name == 'greyd/anim' && !block.isValid ) {
					ids.push(block.clientId);
				}
			});
			return ids;
		}

		/**
		 * Scan and Fix invalid 'greyd/anim' Blocks.
		 */
		const unsubscribe = wp.data.subscribe( () => {
			// run if getEntityRecords is resolved
			if ( wp.data.select( 'core' ).getEntityRecords( 'postType', 'wp_block' ) !== null ) {
				unsubscribe();
				// get invalid blocks
				var invalid = scanBlocks( wp.data.select( 'core/editor' ).getEditorBlocks() );
				// console.log(invalid);
				invalid.forEach(clientId => {
					var { name, attributes, innerBlocks, originalContent } = wp.data.select('core/block-editor').getBlock(clientId);
					if ( !_.has(attributes, 'greydClass') || _.isEmpty(attributes.greydClass) ) {
						/**
						 * Forced Block Recovery for invalid Block.
						 * In the case when 'greydClass' is not defined, a normal Block deprecation is not possible.
						 * The ID of the saved markup saved was generated by 'greyd.tools.generateGreydClass()' on every save.
						 * This meant that no save function was valid and the Block was invalid on every reload.
						 */
						attributes.url = greyd.data.media_urls[attributes.id].src;
						attributes.className = attributes.className.replace('vc_icons', '');
						attributes.greydClass = greyd.tools.getGreydClass( {} );
						var newBlock = wp.blocks.createBlock( name, attributes, innerBlocks );
						wp.data.dispatch( 'core/block-editor' ).replaceBlock( clientId, newBlock );
						console.info("Updated Block: "+name);
					}
					else if ( originalContent.indexOf('wp-block-greyd-anim') == -1 ) {
						/**
						 * Properly support blockProps.
						 * If 'wp-block-greyd-anim' class is not saved, the content is 'apiVersion: 2'
						 * but saved without the proper use of the blockProps function.
						 * Deprecations couldn't handle this state, so we force the block recovery at this point.
						 */
						// 	console.log(originalContent);
						var newBlock = wp.blocks.createBlock( name, attributes, innerBlocks );
						wp.data.dispatch( 'core/block-editor' ).replaceBlock( clientId, newBlock );
						console.info("Updated Block: "+name);
					}
				});
			}
		} );

	});

	// console.log("Lottie Blocks Scripts: loaded");

} )( window.wp );