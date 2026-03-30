/**
 * Greyd.Blocks Editor Script for dynamic Blocks.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	const isDynamicTemplatePost = () => {
		return greyd.data.post_type == "dynamic_template"
			&& ( greyd.data.template_type == "dynamic" || _.isEmpty(greyd.data.template_type) );
	}

	/**
	 * Register custom attributes to blocks.
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (_.has(settings, 'apiVersion')) {
			// console.log(name);

			// dynamic template helper atts
			settings.attributes.dynamic_parent = { type: 'string' }; // backend helper
			settings.attributes.dynamic_value = { type: 'string' }; // frontend helper
			
			// dynamic fields
			if (
				name == 'core/columns' || 
				name == 'core/spacer' || 
				name == 'core/embed' || 
				name == 'core/paragraph' || 
				name == 'core/heading' ||
				name == 'core/button' ||
				name == 'core/image' ||
				name == 'core/video' ||
				// core @since 1.2.8
				name == 'core/media-text' ||
				name == 'core/cover' ||
				name == 'core/quote' ||
				name == 'core/pullquote' ||
				name == 'core/verse' ||
				name == 'core/table-of-contents' ||
				name == 'core/navigation' ||
				name == 'core/group' ||
				// greyd @since 1.3.9
				name == 'greyd/box' ||
				name == 'greyd/anim' ||
				name == 'greyd/button' ||
				name == 'greyd/popover-button' ||
				name == 'greyd/anchor' ||
				name == 'greyd/menu' ||
				name == 'greyd/image' ||
				name == 'greyd/list-item' ||
				name == 'greyd/accordion-item' ||
				name == 'greyd/dynamic' 
			) {
				// console.log(settings);
				settings.attributes.dynamic_fields = { type: 'array' };
			}

			if (name == 'core/table-of-contents') {
				// console.log(settings);
				settings.attributes.rendered = { type: 'string', default: '' };
			}

			if (settings.apiVersion > 1) {

				// Enable transform from dynamic core blocks like 
				// 'site-title' to other blocks with dynamic tags
				if (
					name == 'core/site-logo' || 
					name == 'core/site-tagline' ||
					name == 'core/site-title' ||
					name == 'core/post-title' ||
					name == 'core/post-content' ||
					name == 'core/post-date' ||
					name == 'core/post-excerpt' ||
					name == 'core/post-featured-image' ||
					name == 'core/post-terms' ||
					name == 'core/post-author' ||
					name == 'core/post-author-name' ||
					name == 'core/post-author-biography' ||
					name == 'core/post-navigation-link'
				) {
					var newBlock = false;
					const makeValue = function(block, atts) {
						// console.log(block);
						// console.log(atts);
						var dtag = false;
						if (_.has(block, 'dtag')) dtag = block.dtag;
						else if (_.has(atts, 'term')) {
							if (atts.term == 'category') dtag = 'categories';
							if (atts.term == 'post_tag') dtag = 'tags';
						}
						if (dtag) {
							// get dtag params
							var params = {};
							if (_.has(block, 'params')) params = block.params;
	
							if (_.has(atts, 'isLink') && atts.isLink) {
								params.isLink = true;
								if (_.has(atts, 'linkTarget') && atts.linkTarget == '_blank')
									params.blank = true;
							}
	
							// make new value
							// console.log( JSON.stringify(params));
							var value = { content: '<span data-tag="'+dtag+'" data-params="'+JSON.stringify(params).replace(/"/g,'&quot;')+'" class="is-tag">'+dtag+'</span>' };
							if (dtag == 'author') {
								// console.log(atts);
								value.content = '<strong>'+value.content+'</strong>';
								if (!_.has(atts, 'showAvatar') || atts.showAvatar == true) {
									var params_ava = { format: 'avatar', width: '48px' };
									if (_.has(atts, 'avatarSize')) params_ava.width = atts.avatarSize+'px';
									value.content += '<br><span data-tag="'+dtag+'" data-params="'+JSON.stringify(params_ava).replace(/"/g,'&quot;')+'" class="is-tag">'+dtag+'</span>'
								}
								if (_.has(atts, 'showBio') && atts.showBio) {
									var params_bio = { format: 'description' };
									value.content += '<br><span data-tag="'+dtag+'" data-params="'+JSON.stringify(params_bio).replace(/"/g,'&quot;')+'" class="is-tag">'+dtag+'</span>'
								}
							}
							// console.log(value);
							// get attribute values
							for ([k, v] of Object.entries(wp.blocks.getBlockType(block.name).attributes)) {
								var key = k;
								if (key == 'align') key = 'textAlign';
								if (k != 'style' && _.has(atts, key)) {
									// console.log(k+': '+atts[key]);
									value[k] = atts[key];
								}
							}
							// console.log(value);
							return value;
						}
					}
	
					// site elements
					if (name == 'core/site-title') {
						newBlock = { name: 'core/heading', dtag: 'site-title', params: { isLink: true } };
					}
					else if (name == 'core/site-tagline') {
						newBlock = { name: 'core/paragraph', dtag: 'site-sub' };
					}
					else if (name == 'core/site-logo') {
						newBlock = { name: 'core/paragraph', dtag: 'site-logo', params: { isLink: true } };
					}
					// post elements
					else if (name == 'core/post-title') {
						newBlock = { name: 'core/heading', dtag: 'title' };
					}
					else if (name == 'core/post-content') {
						newBlock = { name: 'core/paragraph', dtag: 'content' };
					}
					else if (name == 'core/post-date') {
						newBlock = { name: 'core/paragraph', dtag: 'date' };
					}
					else if (name == 'core/post-excerpt') {
						newBlock = { name: 'core/paragraph', dtag: 'excerpt' };
					}
					else if (name == 'core/post-featured-image') {
						newBlock = { name: 'core/paragraph', dtag: 'image' };
					}
					else if (name == 'core/post-terms') {
						newBlock = { name: 'core/paragraph', params: { isLink: true } };
					}
					else if (name == 'core/post-author') {
						newBlock = { name: 'core/paragraph', dtag: 'author' };
					}
					else if (name == 'core/post-author-name') {
						newBlock = { name: 'core/paragraph', dtag: 'author', params: { showAvatar: false } };
					}
					else if (name == 'core/post-author-biography') {
						newBlock = { name: 'core/paragraph', dtag: 'author', params: { showAvatar: false, showBio: true } };
					}
					else if (name == 'core/post-navigation-link') {
						// todo
					}
	
					// add transform to block with dynamic tag 
					if (newBlock) {
						var to = {
							type: 'block',
							blocks: [ newBlock.name ],
							transform: function ( attributes ) {
								return wp.blocks.createBlock(
									newBlock.name,
									makeValue(newBlock, attributes)
								);
							},
						};
						if (_.has(settings, 'transforms')) {
							if (_.has(settings.transforms, 'to')) settings.transforms.to.push(to);
							else settings.transforms.to = [ to ];
						}
						else settings.transforms = { to: [ to ] };
						
						// hide blocks from inserter
						// settings.supports.inserter = false;
					}
				}

				// add deprecated greyd attributes (greydStyles & greydClass) for core-blocks
				if ( 
					(name == 'core/heading' ||
					name == 'core/paragraph' || 
					name == 'core/group' ||
					name == 'core/columns' ||
					name == 'core/image' || 
					name == 'core/video' ||
					name == 'core/separator') && 
					settings?.deprecated.length 
				) {
					for (var i=0; i < settings.deprecated.length; i++) {
						if (settings.deprecated[i]) {
							settings.deprecated[i].attributes = {
								...settings.deprecated[i]?.attributes,
								greydStyles: { type: 'object' },
								greydClass: { type: 'object' }
							}
						}
					}
				}

				// add deprecated greyd dynamic attributes for core-blocks
				if ( 
					(name == 'core/columns' || 
					name == 'core/spacer' || 
					name == 'core/embed' || 
					name == 'core/paragraph' || 
					name == 'core/heading' ||
					name == 'core/button' ||
					name == 'core/image' ||
					name == 'core/video' ||
					// core @since 1.2.8
					name == 'core/media-text' ||
					name == 'core/cover' ||
					name == 'core/quote' ||
					name == 'core/pullquote' ||
					name == 'core/verse' ||
					name == 'core/group' ) && 
					settings?.deprecated.length 
				) {
					for (var i=0; i < settings.deprecated.length; i++) {
						if (settings.deprecated[i]) {
							settings.deprecated[i].attributes = {
								...settings.deprecated[i]?.attributes,
								dynamic_fields: { type: 'array' },
								dynamic_parent: { type: 'string' }, // backend helper
								dynamic_value: { type: 'string' }, // frontend helper	
							}
						}
					}
				}

			}
		}

		// // hide comments blocks from inserter
		// if (name.indexOf('comment') > -1 && _.has(settings, 'supports')) {
		// 	// console.log(settings);
		// 	settings.supports.inserter = false;
		// }

		return settings;

	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/dynamic',
		registerBlockTypeHook
	);


	/**
	 * Manipulate attributes before edit.
	 * 
	 * @hook editor.BlockListBlock
	 */
	var editBlockListHook = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			// console.log(BlockListBlock);
			// console.log(props);

			// blocks with dynamic_parent attribute inside greyd/dynamic block
			if (_.has(props.attributes, "dynamic_parent") && typeof props.attributes.dynamic_parent !== "undefined") {
				// console.log(props);

				if (_.has(props.attributes, "dynamic_value") && typeof props.attributes.dynamic_value !== "undefined" &&
					_.has(props.attributes.dynamic_value, "static") && !_.isEmpty(props.attributes.dynamic_value.static)) {
					var [ resets, setResets ] = wp.element.useState( {} );
					var [ deletes, setDeletes ] = wp.element.useState( {} );
					// console.log(resets);
					// console.log(deletes);

					// list of variable fields
					var excluded_fields = [
						'dynamic_fields',
						'dynamic_value',
						'dynamic_parent',
						'anchor',
						'greydClass',
						'templateLock',
						'lock',
						'inline_css_id',
						'layout',
						'titleTag',
						'variation'
					];
					// add looped fields
					if (!_.isEmpty(resets)) Object.keys(resets).forEach(function(key, index) { 
						if (resets[key][0] > 10) {
							console.warn("Block "+props.name+": Field "+key+" in reset loop -> ignore & debug!");
							// console.log(_.clone(props));
							excluded_fields.push(key);
						} 
					});
					if (!_.isEmpty(deletes)) Object.keys(deletes).forEach(function(key, index) { 
						if (deletes[key][0] > 10) {
							console.warn("Block "+props.name+": Field "+key+" in delete loop -> ignore & debug!");
							// console.log(_.clone(props));
							excluded_fields.push(key);
						}
					});
					// get list of dynamic fields
					if (_.has(props.attributes, "dynamic_fields") && typeof props.attributes.dynamic_fields !== "undefined") {
						// console.log(props.attributes.dynamic_fields);
						for (var i=0; i<props.attributes.dynamic_fields.length; i++) {
							var field = props.attributes.dynamic_fields[i];
							if (field.key.indexOf('/') > -1) {
								var tmp = field.key.split('/');
								excluded_fields.push(tmp[0]);
							}
							else excluded_fields.push(field.key);
							if (field.key == 'id' && field.type.indexOf('file_picker') == 0) {
								excluded_fields.push('url');
								excluded_fields.push('backgroundType');
								excluded_fields.push('useFeaturedImage');
							}
							if (field.key == 'mediaId' && field.type.indexOf('file_picker') == 0) {
								excluded_fields.push('mediaType');
								excluded_fields.push('mediaUrl');
							}
						};
						if (props.name == "core/table-of-contents") {
							// console.log(props);
							excluded_fields.push('headings');
						}
					}
					// exclude className only when there are no (or just one) style variation
					var block_type = wp.blocks.getBlockType(props.name);
					if ( _.isEmpty(block_type.styles) || block_type.styles.length < 2 ) {
						excluded_fields.push('className');
					}
					
					// console.log(excluded_fields);
					// console.log(props);

					Object.keys(props.attributes).forEach(function(key, index) {
						if (excluded_fields.indexOf(key) > -1) {
							// console.info("Field "+key+" is dynamic or excluded");
							// console.log(props.attributes);
						}
						if (excluded_fields.indexOf(key) == -1 && _.has(props.attributes, key)) {
							// console.log(key);
							// console.log(props.attributes[key]);
							// reset static fields if changed
							var show_snackbar = false;
							if (_.has(props.attributes.dynamic_value.static, key)) {
								if (!_.isEqual(props.attributes[key], props.attributes.dynamic_value.static[key])) {
									// console.log(props);
									// console.info("Field "+key+" is static -> reset");
									props.attributes[key] = props.attributes.dynamic_value.static[key];
									// add reset count to avoid loops
									var count = (_.has(resets, key)) ? resets[key][0]+1 : 1;
									if (_.has(resets, key) && (Date.now()-resets[key][1]) > 1000) { show_snackbar = true; count--; }
									setResets( { ...resets, [key]: [count, Date.now()] } );
									// greyd.tools.showSnackbar( __("Diese Eigenschaft wurde nicht dynamisch gemacht und kann demnach nicht bearbeitet werden.", 'greyd_hub') );
								}
							}
							else {
								// console.info("Field "+key+" is static -> delete");
								delete props.attributes[key];
								// add delete count to avoid loops
								var count = (_.has(deletes, key)) ? deletes[key][0]+1 : 1;
								if (_.has(deletes, key) && (Date.now()-deletes[key][1]) > 1000) { show_snackbar = true; count--; }
								setDeletes( { ...deletes, [key]: [count, Date.now()] } );
								// greyd.tools.showSnackbar( __("This property was not made dynamic and cannot be edited.", 'greyd_hub') );
							}
							if (show_snackbar) {
								console.info("Field "+key+" is static -> show snackbar");
								greyd.tools.showSnackbar( __("This property was not made dynamic and cannot be edited.", 'greyd_hub') );
							}
						}
					});
				}
			}
			
			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/dynamic/list', 
		editBlockListHook 
	);
	

	/**
	 * Add custom edit controls to blocks.
	 * 
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {
		
		return function( props ) {	
			
			/**
			 * Add custom dynamic behaviours to single blocks
			 */
			if (_.has(props.attributes, "dynamic_parent") && typeof props.attributes.dynamic_parent !== "undefined") {

				/**
				 * Extend table-of-contents block inside greyd/dynamic block
				 */
				if ( props.name == "core/table-of-contents" ) {
					var block_type = wp.blocks.getBlockType(props.name);
					var blockProps = wp.blockEditor.useBlockProps(props);
					// console.log(props);
					// console.log(block_type);
					// console.log(blockProps);

					// simulate save to get markup for dynamic content
					var content = el( 'nav', { 
						className: blockProps.className.replace("block-editor-block-list__block ", "").replace("wp-block ", ""), 
						style: blockProps.style 
					}, block_type.save( { attributes: { headings: props.attributes.headings?? [] } } ) );
					// render react element
					var element = document.createElement('div');
					ReactDOM.render( content, element, () => {
						// get markup
						var toc = element.childNodes[0].childNodes[0].innerHTML;
						var wrapper = element.innerHTML.replace(toc, "").split('><nav');
						var html = wrapper[0]+'>'+toc+'</nav>';
						if ( html != props.attributes.rendered ) {
							// console.log("set toc", html);
							props.setAttributes( { rendered: html } );
						}
					} );

				}

				/**
				 * Update main style attribute when dynamic value is changed.
				 * This triggers an update of the preview in the template.
				 */
				if ( props.name == "core/group" ) {
					// console.log(props);
					var [ dynamic, setDynamic ] = wp.element.useState( {
						id: props.attributes.style?.background?.backgroundImage?.id,
						focal: props.attributes.style?.background?.backgroundPosition
					} );
					if (
						dynamic.id !== props.attributes.style?.background?.backgroundImage?.id ||
						dynamic.focal !== props.attributes.style?.background?.backgroundPosition
					) {
						props.setAttributes( { style: { ...props.attributes.style } } );
						setDynamic( {
							id: props.attributes.style?.background?.backgroundImage?.id,
							focal: props.attributes.style?.background?.backgroundPosition
						} );
					}
				}

			}

			/**
			 * =================================================================
			 *                          Dynamic extensions
			 * =================================================================
			 */

			/**
			 * Extend blocks with dynamic_parent attribute inside greyd/dynamic block
			 */
			if (_.has(props.attributes, "dynamic_parent") && typeof props.attributes.dynamic_parent !== "undefined") {

				// maybe select parent if clicked inside the block
				var select_parent = true;

				// This is the top level parent (default = true)
				// In dynamic template posts: default = false
				var is_top = ! isDynamicTemplatePost();

				// get the parent dynamic template that holds the dynamic contents
				// a template can be wrapped in another template and so on...
				var parent_id = props.attributes.dynamic_parent;
				
				// check if dynamic_parent is still the correct parent
				// when a template is duplicated, the original dynamic_parent is also copied,
				// until the next save&reload the duplicate will behave as the original one.
				var parentBlocks = wp.data.select( 'core/block-editor' ).getBlockParents(props.clientId);
				if (parentBlocks.indexOf(parent_id) == -1) {
					// search new parent
					for (var i=parentBlocks.length-1; i>-1; i--) {
						var parentAtts = wp.data.select('core/block-editor').getBlock(parentBlocks[i]);
						if (parentAtts && parentAtts.name == 'greyd/dynamic') {
							// console.log("dynamic_parent changed");
							parent_id = parentBlocks[i];
							break;
						}
					}
				}

				// get the parent (greyd/dynamic) Block
				const checkDynamic = ( attributes ) => {
					return attributes?.dynamic_fields?.length > 0 && attributes.dynamic_fields[0].key == 'dynamic_content' && attributes.dynamic_fields[0].enable;
				}
				var parent = wp.data.select('core/block-editor').getBlock(parent_id);
				var parentDynamicFields = [];
				if ( parent ) {
					parentDynamicFields = [ checkDynamic( parent.attributes ) ];
					while (_.has(parent, 'attributes') && _.has(parent.attributes, "dynamic_parent") && typeof parent.attributes.dynamic_parent !== "undefined") {
						is_top = false;
						parent_id = parent.attributes.dynamic_parent;
						parent = wp.data.select('core/block-editor').getBlock(parent_id);
						if (parent) {
							parentDynamicFields.push( checkDynamic( parent.attributes ) );
						}
					}
				}

				if ( ! parent ) {
					return el( BlockEdit, props );
				}

				// only execute, if the field is not part of the static cache
				if ( !greyd.dynamic.isFieldCached(props) ) {

					// get dynamic_fields for current block
					var fields = greyd.dynamic.getFields(props.name);
					if (fields.length > 0) {
						
						// get saved dynamic_fields
						var dynamic_fields = [];
						if (_.has(props.attributes, "dynamic_fields") && props.attributes.dynamic_fields.length > 0) {
							props.attributes.dynamic_fields.forEach(function(f, i) {
								var field = JSON.parse(JSON.stringify(f));
								if (field.enable) dynamic_fields.push(field);
							});
						}
						// console.log(props);
						// console.log(dynamic_fields);
						if (dynamic_fields.length > 0) {

							// get closest parent
							var first_parent_id = props.attributes.dynamic_parent;
							var first_parent = wp.data.select('core/block-editor').getBlock(first_parent_id);
							// console.log(first_parent.attributes);
							// console.log(first_parent);

							// make fields only dynamic if dymamic_fields in all parents are present 
							// meaning that the dynamic field with type 'dynamic_content' is set and sub-blocks should be editable
							var is_dynamic = parentDynamicFields.length && parentDynamicFields[0] === true;
							if ( is_dynamic ) {
								for ( var i = 0; i < parentDynamicFields.length-1; i++ ) {
									if ( parentDynamicFields[i] === false ) {
										// not dynamic if one of the parents is not
										is_dynamic = false;
										break;
									}
								}
							}
							if ( isDynamicTemplatePost() && parent_id == first_parent_id ) {
								// revert logic in dynamic_template posts
								// if dynamic_field is set, the content should not be editable
								is_dynamic = !is_dynamic;
							}

							// if this is the top-level parent or it's contents are made dynamic
							if (is_top || is_dynamic) {

								// get values from top level parent
								var dynamic_content = _.clone(parent.attributes.dynamic_content);
								var changed = false;

								// console.log(fields);
								// console.log(dynamic_fields);
								// console.log(props.attributes.dynamic_value.defaults);

								// search all dynamic_fields
								for (var i=0; i<fields.length; i++) {
									if (fields[i].type == 'dynamic_content') continue;

									// search for matching saved dynamic_field
									for (var j=0; j<dynamic_fields.length; j++) {
										if (dynamic_fields[j].key == fields[i].key) {

											// do not select parent
											select_parent = false;
											// get field and dynamic value
											var field = dynamic_fields[j];
											var tmp = greyd.dynamic.getFieldValue(props.attributes, field);
											// console.log(tmp);
											if (
												typeof tmp !== 'string' &&
												field.type != 'textarea' &&
												field.type != 'textarea_html'
											) {
												tmp = JSON.stringify(tmp);
											}
											var val = encodeURIComponent(tmp);
											// console.log(val);
											var dynamic_content_field = { 
												dkey: field.key, 
												dtype: fields[i].type, 
												dtitle: field.title, 
												dvalue: val 
											};
											// compare to default value
											if (_.has(props.attributes.dynamic_value, 'defaults.'+field.key)) {
												var val_default = _.clone(props.attributes.dynamic_value.defaults[field.key]);
												if (typeof val_default !== 'string') {
													if (Object.keys(val_default).length == 0) val_default = '{}';
													else val_default = JSON.stringify(val_default);
												}
												val_default = encodeURIComponent(val_default);
												if (val_default == val) {
													dynamic_content_field = undefined;
												}
											}
											// search saved dynamic_content for maching field
											// console.log(dynamic_content);
											var found = false;
											for (var k=dynamic_content.length-1; k>=0; k--) {
												if (dynamic_content[k].dtype == fields[i].type && dynamic_content[k].dtitle == field.title) {
													if (dynamic_content[k].dvalue != val) {
														// set value to parents dynamic_content
														if (typeof dynamic_content_field !== 'undefined')
															dynamic_content[k] = dynamic_content_field;
														else {
															// console.log("checking nests before deleting ...");
															// console.log(_.clone(props.attributes));
															// console.log(_.clone(parent.attributes));
															// console.log(_.clone(first_parent.attributes));
															var double = false;
															if (_.has(parent.attributes, "dynamic_nests") && 
																typeof parent.attributes.dynamic_nests !== 'undefined' && 
																parent.attributes.dynamic_nests.length > 0) {
																// console.log(dynamic_content[k]);
																parent.attributes.dynamic_nests.forEach(function(nest, ii) {
																	if (_.has(nest, 'dynamic_blocks')) {
																		nest.dynamic_blocks.forEach(function(db, jj) {
																			// console.log(db);
																			if (dynamic_content[k].dtype == db.atts.type && dynamic_content[k].dtitle == db.atts.title) {
																				// console.log("found double");
																				double = true;
																				dynamic_content[k] = { 
																					dkey: field.key, 
																					dtype: fields[i].type, 
																					dtitle: field.title, 
																					dvalue: val_default 
																				};
																			}
																		});
																	}
																});
															}
															if (!double) dynamic_content.splice(k, 1);
															// dynamic_content.splice(k, 1);
														}
														changed = true;
													}
													found = true;
												}
											}
											if (!found && typeof dynamic_content_field !== 'undefined') {
												// add value to parents dynamic_content
												dynamic_content.push(dynamic_content_field);
												changed = true;
											}
											break;
										}
									}
								}

								/**
								 * If select_parent is still true, this field is not dynamic and therefore not
								 * an editable field. We cache it to enhance performance.
								 */
								if ( select_parent ) {
									greyd.dynamic.cacheStaticField( props );
								}
								// change parents dynamic_content
								else if (
									changed === true &&
									// change parent attributes only from a selected block or the parent template block.
									// prevents editor loops when multiple fields have the same key (name)
									( props.isSelected || wp.data.select( 'core/block-editor' ).getSelectedBlock()?.clientId == parent.clientId )
								) {
									// console.log( 'dynamic_content changed:', dynamic_content, props.clientId );
									wp.data.dispatch('core/block-editor').updateBlockAttributes(parent.clientId, { dynamic_content: dynamic_content });
								}
							}
							// if closest parent is not dynamic and 'dynamic_content' is set
							// apply its value to sub-blocks
							else if (_.has(first_parent.attributes, "dynamic_content") && first_parent.attributes.dynamic_content.length > 0) {

								// console.log(fields, first_parent.attributes.dynamic_content);

								// get values from closest parent
								var dynamic_content = _.clone(first_parent.attributes.dynamic_content);

								// search all dynamic_fields
								for (var i=0; i<fields.length; i++) {

									// search for matching saved dynamic_field
									for (var j=0; j<dynamic_fields.length; j++) {
										if (dynamic_fields[j].key == fields[i].key) {

											// search saved dynamic_content for maching field
											for (var k=0; k<dynamic_content.length; k++) {
												if (dynamic_content[k].dtype == fields[i].type && dynamic_content[k].dtitle == dynamic_fields[j].title) {
													// update value
													var val = decodeURIComponent(dynamic_content[k].dvalue);
													try { val = JSON.parse(val); } 
													catch(e) { }
													greyd.dynamic.cacheStaticField( props );
													
													const changedAtts = greyd.dynamic.getChangedDynamicAttributes( fields[i], val );
													// console.log( 'setFieldValue:', changedAtts );
													props.setAttributes( changedAtts );

													/**
													 * @deprecated since 1.2.4
													 * props.attributes = greyd.dynamic.setFieldValue(props.attributes, fields[i], val);
													 */
												}
											}

										}
									}

								}

							}

						}
					}
				}

				if (
					select_parent &&
					props.name != "core/post-template" &&
					// allow opening popovers inside templates
					props.name != "greyd/popover-button" &&
					props.name != "greyd/popover-popup"
				) {
					// var sidebar = el( 'div', { className: 'template_layer' } );
					if ( props.isSelected ) {
						setTimeout(() => {
							wp.data.dispatch('core/block-editor').selectBlock(parent_id);
							// jQuery('#block-'+parent_id).focus();
						}, 0);
					}
				}
				else {

					/**
					 * Add 'template_layer' Component.
					 * @since Gutenberg 15.0.0
					 * Deprecated because the InspectorControls Slot is in the wrong Tab.
					 * No other Slots are available.
					 */
					// var sidebar = el( 'div', { className: 'template_layer' }, [
					// 	el( 'div', { className: 'template_layer__box' }, [
					// 		el( 'span', { className: 'dashicon dashicons dashicons-lock' } ),
					// 		el( 'p', {}, [
					// 			el( 'strong', {}, __("This element is part of a Dynamic Template.", 'greyd_hub') ),
					// 		] ),
					// 		el( 'p', {}, __("Within a dynamic template, only content can be exchanged that was made dynamic when the template was edited (use the toolbar).", 'greyd_hub') ),
					// 		el( 'p', {}, __("To edit other properties of this block, focus the template and edit it.", 'greyd_hub') ),
					// 		el( 'button', {
					// 			className: 'components-button is-secondary is-small has-text',
					// 			onClick: () => {
					// 				wp.data.dispatch('core/block-editor').selectBlock(parent_id);
					// 				// jQuery('#block-'+parent_id).focus();
					// 			}
					// 		}, __("Focus template block", 'greyd_hub') ),
					// 	] )
					// ] );

					/**
					 * Inject 'template_layer' Markup with jQuery.
					 * @since Gutenberg 15.0.0
					 * With https://github.com/WordPress/gutenberg/pull/45005
					 * (Sidebar: Split block tools into menu, settings, and appearance tabs)
					 * the Sidebar Tabs are added. This overrides our previous implementation.
					 * Until more Slots are created by core, the only stable solution is by using jQuery.
					 */
					if ( props.isSelected ) {
						setTimeout(() => {
							var sidebar = 
								'<div class="template_layer__box">'+
									'<span class="dashicon dashicons dashicons-lock">'+'</span>'+
									'<p>'+
										'<strong>'+__("This element is part of a Dynamic Template.", 'greyd_hub')+'</strong>'+
									'</p>'+
									'<p>'+__("Within a Dynamic Template, only content can be exchanged that was made dynamic when the template was edited (use the toolbar).", 'greyd_hub')+'</p>'+
									'<p>'+__("To edit other properties of this block, focus the template and edit it.", 'greyd_hub')+'</p>'+
									'<button class="components-button is-secondary is-small has-text" '+
										'data-parent="'+parent_id+'" '+
										'onclick=" wp.data.dispatch(\'core/block-editor\').selectBlock(\''+parent_id+'\'); ">'+
										__("Focus template block", 'greyd_hub')+
									'</button>'+
								'</div>';
							if ( jQuery('.template_layer').length == 0 ) {
								// console.log("add template layer");
								jQuery('.block-editor-block-inspector').append('<div class="template_layer">'+sidebar+'</div>');
							}
							else if (jQuery('.template_layer button').data('parent') != parent_id) {
								// console.log("change template layer");
								jQuery('.template_layer').html(sidebar);
							}
						}, 0);
					}

				}

			}
			else if ( jQuery('.template_layer').length > 0 ) {
				
				var hide = props.isSelected;
				// check if selected block is post-template and direct parent of currently selected (greyd/dynamic)
				if ( !hide ) {
					var current = wp.data.select( 'core/block-editor' ).getSelectedBlock();
					if (
						current && 
						current.name == 'core/post-template' && 
						_.has(current, 'innerBlocks') && 
						current.innerBlocks.length > 0 &&
						current.innerBlocks[0].clientId == props.clientId
					) {
						hide = true;
					}
				}

				if ( hide ) {
					/**
					 * Remove jQuery Markup.
					 * @since Gutenberg 15.0.0 (see other comments)
					 */
					// console.log("remove template layer");
					jQuery('.template_layer').remove();
				}
			}
			
			/**
			 * Extend blocks with dynamic attributes & controls within 'Dynamic Template' posts
			 */
			if ( isDynamicTemplatePost() ) {
				
				// get dynamic_fields for current block
				var fields = greyd.dynamic.getFields(props.name);
				if (fields.length > 0) {
					// console.log(props.attributes);

					// init dynamic_fields
					var dynamic_fields = [];
					if (_.has(props.attributes, "dynamic_fields") && props.attributes.dynamic_fields && props.attributes.dynamic_fields.length > 0) {
						var changed = false;
						props.attributes.dynamic_fields.forEach(function(f, i) {
							var field = JSON.parse(JSON.stringify(f));
							// console.log(field);
							if (field.enable && field.title == "") {
								// init random title when switch is enabled
								field.title = field.key+'_'+greyd.tools.generateRandomID();
								changed = true;
							}
							// if (_.has(field, 'type')) {
							// 	delete field.type;
							// 	changed = true;
							// }
							// dynamic_fields.push(field);
							if (field.enable)
								dynamic_fields.push(field);
							else
								changed = true;
						});
						if (changed) props.setAttributes( { dynamic_fields: dynamic_fields } );
					}
					// console.log(dynamic_fields);
					var [ dynamicFields, setDynamicFields ] = wp.element.useState( dynamic_fields );
					// console.log(dynamicFields);

					var makeFields = function(props) {
						// console.log(fields);
						// console.log(dynamic_fields);

						function search_dynamic_field(key) {
							var index = -1;
							for (var i=0; i<dynamic_fields.length; i++) {
								if (dynamic_fields[i].key == key) {
									index = i;
									break;
								}
							}
							return index;
						}
						// change function
						function set_dynamic_field(value) {
							if (value.enable) {
								var index = -1;
								for (var j=0; j<dynamicFields.length; j++) {
									if (value.key == dynamicFields[j].key) {
										index = j;
										break;
									}
								}
								if (value.key == "level") {
									for (var k=0; k<dynamicFields.length; k++) {
										if (dynamicFields[k].key == 'content') {
											value.title = dynamicFields[k].title+" H-Tag";
											break;
										}
									}
								}
								if (value.title == "") {
									if (index == -1) {
										value.title = value.key+'_'+greyd.tools.generateRandomID();
									}
									else value.title = dynamicFields[index].title;
								}
								else {
									if (index == -1) dynamicFields.push(value); 
									else dynamicFields[index].title = value.title; 
									setDynamicFields(dynamicFields);
								}
							}
							var found = false;
							var index = search_dynamic_field(value.key);
							if (index != -1) {
								if (value.enable == false) 
									dynamic_fields.splice(index, 1);
								else
									dynamic_fields[index] = value;
								found = true;
							}
							if (value.enable && !found) dynamic_fields.push(value);
							// console.log(dynamic_fields);
							// add 'dyn' class to make the block editable
							var classes = _.has(props.attributes, 'className') && !_.isEmpty(props.attributes.className) ? props.attributes.className.split(' ') : [];
							classes = classes.filter(n => n && n != 'dyn'); // remove empty and 'dyn'
							var is_dyn = value.enable ? true : false;
							if (!is_dyn) dynamic_fields.forEach(function(field, i) {
								if (field.enable) is_dyn = true;
							});
							if (is_dyn) classes.push('dyn');
							// set attributes
							props.setAttributes( { dynamic_fields: dynamic_fields, className: classes.join(' ') } );
						}

						var result = [ ];
						fields.forEach(function(f, i) {
							var field = JSON.parse(JSON.stringify(f));
							var dfield = {
								key: field.key,
								title: '',
								enable: false
							};
							var index = search_dynamic_field(dfield.key);
							if (index != -1) dfield = _.clone(dynamic_fields[index]);
							
							var show = true;

							if (
								( dfield.key == 'background/image/id' && ( !_.has(props.attributes, 'background') || props.attributes.background?.type != 'image' ) ) ||
								( dfield.key == 'background/anim/id' && ( !_.has(props.attributes, 'background') || props.attributes.background?.type != 'animation' ) ) ||
								( dfield.key == 'background/video/url' && ( !_.has(props.attributes, 'background') || props.attributes.background?.type != 'video' ) ) ||
								( props.name == "core/media-text" && dfield.key == 'focalPoint' && !( props.attributes?.mediaType == 'image' && props.attributes?.imageFill === true ) )
							) {
								// hide when attribute not active
								show = false;
							}
							if (dfield.key == 'level') {
								show = false;
								var index = search_dynamic_field('content');
								if (index != -1 && dynamic_fields[index].enable == true) show = true;
							}
							if (show) {
								// make toggle
								result.push( el( wp.components.ToggleControl, {
									label: field.label,
									checked: dfield.enable,
									onChange: function(value) { 
										console.log("toggle "+dfield.key);
										dfield.enable = value;
										// change value
										set_dynamic_field(dfield);
									},
								} ) );
								if (dfield.enable && dfield.key != 'dynamic_content' && dfield.key != 'level') {
									var help = undefined;
									if (fields.length > 1 && fields.length-1 == i) {
										help = __("If you give the same name to several items, they will be filled with the same content later.", 'greyd_hub');
									}
									if (dfield.key == 'greydStyles/maxWidth') {
										help = __("Only the base \"Desktop\" value for maximum width can be dynamic. Responsive values are not supported and can not be changed when using the template.", 'greyd_hub');
									}
									// make fieldname input
									result.push( el( wp.components.TextControl, {
										label: __("Field name", 'greyd_hub'),
										help: help,
										value: dfield.title,
										onChange: function(value) { 
											// console.log("change "+dfield.key+" to "+value);
											dfield.title = value;
											// change value
											set_dynamic_field(dfield);
											// change h-tag title with heading content
											if (dfield.key == 'content' && props.name == 'core/heading') {
												// console.log(dfield);
												// console.log(props);
												var index = search_dynamic_field('level');
												if (index != -1 && dynamic_fields[index].enable == true) {
													set_dynamic_field({
														key: "level",
														title: dfield.title+" H-Tag",
														enable: true
													});
												}
											}
										},
									} ) );
								}
							}
						});
						
						return result;
					}

					var getTip = function(props) {
						if (props.name == "core/image") {
							return el( wp.components.Tip, { }, __("To make this image dynamic, transform it into the Dynamic Image block. To do this, click on the image icon on the left side of the toolbar.", 'greyd_hub') );
						}
						else if (props.name == "core/button") {
							return el( wp.components.Tip, { }, __("To make this button dynamic, transform it into the block \"Greyd Buttons\". To do this, click on the button icon on the left side of the toolbar.", 'greyd_hub') );
						}
						else {
							return el( wp.components.Tip, {}, el( 'p', {}, __("By making the contents of blocks in templates dynamic, you can fill or change them when you later include them in a page. This allows you to use the template in different places with different content.", 'greyd_hub') ) )
						}
					}

					var dfields = makeFields(props);
					// console.log( 'extend sidebar:', dfields );

					// extend sidebar
					return el( wp.element.Fragment, { }, [
						// sidebar
						el( wp.blockEditor.InspectorControls, { }, [
							el(
								wp.components.PanelBody,
								{ 
									title: __("Dynamic Elements", 'greyd_hub'), 
									initialOpen: true, 
									// className: (dfields.length == 0) ? 'hidden' : '' 
								},
								[
									// dynamic fields
									...dfields,
									// tip
									getTip(props),
								]
							)
						] ), 
						// original block
						el( BlockEdit, props ),
					] );
				}
			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter( 
		'editor.BlockEdit', 
		'greyd/hook/dynamic/edit', 
		editBlockHook 
	);

} )( window.wp );