/**
 * Greyd.Blocks Editor Script for dynamic Template Block.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register Template Block.
	 */
	wp.blocks.registerBlockType( 'greyd/dynamic', {
		title: __('Template', 'greyd_hub'),
		description: __("Insert a Dynamic Template", 'greyd_hub'),
		icon: typeof greyd.tools.getBlockIcon !== "undefined" ? greyd.tools.getBlockIcon('dynamic') : el( "svg", {
			width: 24,
			height: 24,
			viewBox: "0 0 24 24",
			fill: "none",
			xmlns: "http://www.w3.org/2000/svg",
			className: "greyd-dynamic-template-block-icon"
		}, [
			el( "path", {
				fillRule: "evenodd",
				clipRule: "evenodd",
				d: "M12.0038 3.13574L19.7464 7.57019V16.4652L12.0038 20.8626L4.26123 16.4652V7.57019L12.0038 3.13574ZM12.7537 18.7116L18.2464 15.5921V9.27025L12.7537 12.4476V18.7116ZM12.0038 4.86434L17.4625 7.99077L12.0038 11.1485L6.54502 7.99078L12.0038 4.86434Z"
			} )
		] ),
		category: 'greyd-blocks',

		edit: function( props ) {
			// console.log(props);

			// get dynamic content values
			// var values = _.clone(props.attributes.dynamic_content);
			// var parent_id = -1;
			// if (_.has(props.attributes, "dynamic_parent") && typeof props.attributes.dynamic_parent !== "undefined") {
			// 	// this block is a nested block
			// 	// search for top level parent and get dynamic values
			// 	parent_id = props.attributes.dynamic_parent;
			// 	var parent = wp.data.select('core/block-editor').getBlock(parent_id);
			// 	while (_.has(parent.attributes, "dynamic_parent") && typeof parent.attributes.dynamic_parent !== "undefined") {
			// 		parent_id = parent.attributes.dynamic_parent;
			// 		parent = wp.data.select('core/block-editor').getBlock(parent_id);
			// 	}
			// 	// console.log(parent.attributes);
			// 	values = _.clone(parent.attributes.dynamic_content);
			// }
			// console.log(_.clone(values));

			var getValues = () => {
				
				var dynamic_values = _.clone(props.attributes.dynamic_content);
				var parent_id = -1;
				if (_.has(props.attributes, "dynamic_parent") && typeof props.attributes.dynamic_parent !== "undefined") {
					// this block is a nested block
					// search for top level parent and get dynamic values
					parent_id = props.attributes.dynamic_parent;
					var parent = wp.data.select('core/block-editor').getBlock(parent_id);
					while ( parent && _.has(parent.attributes, "dynamic_parent") && typeof parent.attributes.dynamic_parent !== "undefined") {
						parent_id = parent.attributes.dynamic_parent;
						parent = wp.data.select('core/block-editor').getBlock(parent_id);
					}
					// console.log(parent.attributes);
					if ( parent && parent?.attributes?.dynamic_content ) {
						dynamic_values = _.clone(parent.attributes.dynamic_content);
					}
				}
				// console.log(dynamic_values);
				return {
					parent_id: parent_id,
					values: dynamic_values
				};

			}

			var matchValues = (clientId, attributes, field) => {
				let changed = false;
				
				for (var j=0; j<values.length; j++) {
					if (values[j].dtitle == field.title) {
						// console.log(field);
						// console.log(values[j]);

						// update value
						var val = decodeURIComponent(values[j].dvalue);
						try {
							json = JSON.parse(val);
							if ( typeof json !== 'number' ) val = json;
						}
						catch(e) { }
						var key_val = greyd.dynamic.getFieldValue(attributes, field);
						if (!_.isEqual(key_val, val)) {
							changed = true;

							// console.log(clientId, val, key_val);

							// types do not match
							if ( values[j].dtype != field.type ) {
								/**
								 * Support transformed vc image dvalue
								 * When vc_icons shortcodes with a dynamic value are being transformed into
								 * blocks, the previous dynamic value only contains an image-id, and not a 
								 * full image-object.
								 */
								if ( values[j].dkey === 'icon' && field.key === 'image' ) {
									if ( typeof val === 'number' ) {
										val = {
											type: 'file',
											id: val,
											url: _.has(greyd.data.media_urls, val) ? JSON.parse(JSON.stringify(greyd.data.media_urls[val].src)) : '',
											tag: ''
										};
									}
									else if ( typeof val === 'string' ) {
										val = { type: 'file', id: -1, url: '', tag: val };
									}
								}
								// other non matching types are not supported
								else continue;
							}

							// console.log("set "+field.key+" from "+key_val+" to "+val);
							attributes = greyd.dynamic.setFieldValue(attributes, field, val);
							// update anim
							if (field.type == "file_picker_json") {
								if ( _.has(greyd.data.media_urls, val) ) {
									// console.log("set dynamic anim");
									var editor_id = 'anim_'+clientId;
									var inspector_id = 'anim_'+field.title.split(' ').join('')+'_'+props.clientId;
									var newurl = JSON.parse(JSON.stringify(greyd.data.media_urls[val].src));
									greyd.lottie.set([editor_id, inspector_id], newurl);
								}
							}
							
						}
						break;
					}
				}
				return changed ? attributes : false;
				// return attributes;
			}

			var updateInnerBlocks = (dynamic_content) => {
				// update values
				values = dynamic_content;
				// replate inner blocks with dynamic attributes
				// replaceInnerBlocks(props.clientId, []);
				var save = greyd.tools.hashCode(inner_blocks);
				var newInnerBlocks = updateBlocks( inner_blocks );
				if ( !_.isEqual(save, greyd.tools.hashCode(newInnerBlocks)) ) {
					// console.log('replaceInnerBlocks', newInnerBlocks );
					replaceInnerBlocks(props.clientId, newInnerBlocks, false);
				}
			}

			var updateBlocks = (blocks) => {
				for (var i=0; i<blocks.length; i++) {
					let changed = false;
					if (
						_.has(blocks[i].attributes, 'dynamic_fields')
						&& blocks[i].attributes.dynamic_fields
						&& blocks[i].attributes.dynamic_fields.length > 0
					) {
						for (var k=0; k<blocks[i].attributes.dynamic_fields.length; k++) {

							if ( !blocks[i].attributes.dynamic_fields[k].enable ) continue;

							if ( greyd.dynamic.isFieldCached(blocks[i]) ) {
								// console.log("field cached");
								continue;
							}

							var field = blocks[i].attributes.dynamic_fields[k];
							var f = greyd.dynamic.getField(blocks[i].name, field.key);

							// compare field value
							field.type = f.type;
							const newAtts = matchValues(blocks[i].clientId, blocks[i].attributes, field);
							if ( newAtts ) {
								changed = true;
								// console.log('updateAtts', newAtts, 'oldAtts:', blocks[i].attributes );
								blocks[i].attributes = newAtts;
							}
							// if (blocks[i].name == 'greyd/box') console.log(blocks[i]);
						}
					}
					// console.log(blocks[i]);
					// inner blocks
					if (blocks[i].innerBlocks.length > 0) {
						// console.log('updateInnerBlocks', blocks[i].innerBlocks );
						blocks[i].innerBlocks = updateBlocks(blocks[i].innerBlocks);
					}
				}
				return blocks;
			}

			var makeTemplate = (blocks) => {
				var blocks_template = [];
				var dynamic_blocks = [];
				for (var i=0; i<blocks.length; i++) {
					var block_attributes_static = _.clone(blocks[i].attributes);
					delete(block_attributes_static.dynamic_fields);
					// make dynamic attributes
					var block_attributes = _.clone(blocks[i].attributes);
					var block_defaults = {};
					if (_.has(block_attributes, 'dynamic_fields') && block_attributes.dynamic_fields && block_attributes.dynamic_fields.length > 0) {
						for (var k=0; k<block_attributes.dynamic_fields.length; k++) {
							if (!block_attributes.dynamic_fields[k].enable) continue;
							var field = block_attributes.dynamic_fields[k];
							delete(block_attributes_static[field.key]);
							var f = greyd.dynamic.getField(blocks[i].name, field.key);
							// dynamic block
							var dynamic_block = {
								atts: {
									key: field.key,
									title: field.title,
									type: f.type, // field.type,
									label: f.label, // greyd.tools.getFieldLabel(field),
									// default: block_attributes[field.key],
									default: greyd.dynamic.getFieldValue(block_attributes, field),
									block: blocks[i].name,
								},
								count: 1
							};
							if (field.key == 'dynamic_content') {
								// nested
								dynamic_block.atts.template = block_attributes.template;
							}
							dynamic_blocks.push( dynamic_block );
							block_defaults[field.key] = dynamic_block.atts.default;
							// compare field value
							field.type = f.type;
							const newAtts = matchValues(blocks[i].clientId, block_attributes, field);
							if ( newAtts ) {
								block_attributes = newAtts;
							}
						}
					}
					// save static and default attributes
					block_attributes.dynamic_value = {
						static: _.clone(block_attributes_static),
						defaults: _.clone(block_defaults)
					};
					// link block to parent
					block_attributes.dynamic_parent = props.clientId;
					// inner blocks
					block_attributes.lock = { move: true, remove: true };
					if (blocks[i].name == 'core/column') {
						block_attributes.templateLock = 'all';
					}
					// console.log(block_attributes);

					// make dynamic block
					var inner = [ blocks[i].name, block_attributes ];
					if (blocks[i].innerBlocks.length > 0) {
						var [ bt, db ] = makeTemplate(blocks[i].innerBlocks);
						inner.push( bt );
						if (db.length > 0) {
							for (var j=0; j<db.length; j++)
								dynamic_blocks.push( db[j] );
						}
					}
					// template block
					blocks_template.push( inner );
				}
				// console.log(dynamic_blocks);
				var dblocks = []
				for (var i=0; i<dynamic_blocks.length; i++) {
					var found = false;
					for (var j=0; j<dblocks.length; j++) {
						if (dblocks[j].atts.type == dynamic_blocks[i].atts.type && 
							dblocks[j].atts.title == dynamic_blocks[i].atts.title) {
							dblocks[j].count++;
							found = true; break;
						}
					}
					if (!found) {
						dblocks.push(dynamic_blocks[i]);
					}
				}
				// console.log(dblocks);
				return [ blocks_template, dblocks ];
			}
			
			// trigger template reload
			var [ isReloading, setIsReloading ] = wp.element.useState(false);
			var reloadTemplate = () => {
				// invalidate and reload template
				wp.data.dispatch('core/data').invalidateResolution('core', 'getEntityRecord', [ 'postType', dtemplate.type, select_id ]);
				wp.data.dispatch('core/data').startResolution('core', 'getEntityRecord', [ 'postType', dtemplate.type, select_id ]);
				// set reloading and save current element dimensions (avoid too much layout shift)
				var element = document.getElementById('block-'+props.clientId);
				if ( !element && document.querySelector('.is-iframed iframe') ) {
					var iframe = document.querySelector('.is-iframed iframe');
					element = iframe.contentWindow.document.getElementById('block-'+props.clientId);
				}
				setIsReloading( { width: element?.offsetWidth ?? 100, height: element?.offsetHeight ?? 100 } );
				setIsReloading( { width: element.offsetWidth, height: element.offsetHeight } );
				// wait until new template is resolved
				const reload = wp.data.subscribe( () => {
					// check if getEntityRecord is resolved
					if ( !wp.data.select('core/data').isResolving('core', 'getEntityRecord', [ 'postType', dtemplate.type, select_id ]) ) {
						reload();
						replaceInnerBlocks(props.clientId, []);
						props.setAttributes( { dynamic_content: [ ...props.attributes.dynamic_content ] } );
						setIsReloading( false );
					}
				} );
			}

			// get template details
			var dtemplate = greyd.dynamic.getTemplate(props.attributes.template);
			var select_id = dtemplate.id;
			if (dtemplate.type == 'wp_template' || dtemplate.type == 'wp_template_part') {
				select_id = greyd.data.theme + '//' + dtemplate.slug;
			}
			// console.log(dtemplate);

			var { parent_id, values } = getValues();			
			var [ vals, setVals ] = wp.element.useState(values);
			var [ template, setTemplate ] = wp.element.useState({ slug: '', mode: '', href: '', content: '' });
			var { replaceInnerBlocks } = wp.data.dispatch("core/block-editor");
			var { inner_blocks, blocks, permission } = wp.data.useSelect(select => ({
				inner_blocks: select("core/block-editor").getBlocks(props.clientId),
				blocks: select("core").getEntityRecord( 'postType', dtemplate.type, select_id ),
				permission: select( 'core' ).canUser( 'create', 'pages' )
			}));
			// console.log(blocks);
			// console.log(_.clone(values));
			// console.log(_.clone(vals));

			// handle reference (ID or slug)
			const isReferenceForced = () => greyd.data.template_use_reference == 'forceID' || greyd.data.template_use_reference == 'forceSlug';
			if (
				greyd.data.template_use_reference == 'forceSlug' &&
				(
					!props.attributes.useSlug
					|| ( props.attributes.template != "" && props.attributes.template != dtemplate.slug )
				)
			) {
				// console.log("force useSlug to true");
				props.setAttributes( { useSlug: true, template: dtemplate.slug } );
			}
			else if (
				greyd.data.template_use_reference == 'forceID' &&
				(
					!!props.attributes.useSlug
					|| ( props.attributes.template != "" && props.attributes.template != ""+dtemplate.id+"" )
				)
			) {
				// console.log("force useSlug to false");
				props.setAttributes( { useSlug: false, template: ""+dtemplate.id+"" } );
			}
			else if (
				props.isSelected &&
				!_.isEmpty(props.attributes.template) &&
				parent_id == -1 &&
				!isReferenceForced() &&
				( props.attributes.template == parseInt(props.attributes.template) ) == !!props.attributes.useSlug
			) {
				// console.log("set useSlug to "+(props.attributes.useSlug ? "false" : "true"));
				props.setAttributes( { useSlug: !props.attributes.useSlug } );
			}

			if (!_.isEqual(values, vals)) {
				// console.log("update template blocks");
				setVals(values);
				updateInnerBlocks(_.clone(values));
			}

			var blocks_template = [];
			var dynamic_blocks = [];
			if (typeof blocks !== 'undefined') {
				
				// make innerBlocks template
				blocks = wp.blocks.parse(blocks.content.raw);
				var [ blocks_template, dynamic_blocks ] = makeTemplate(blocks);

				/**
				 * do not fix templates with nested dynamic contents, because
				 * the number of fields is usually not matching.
				 * @since 1.3.9
				 */
				var hasNestedTemplate = false;
				dynamic_blocks.forEach( dynamicBlock => {
					if ( dynamicBlock.atts.block === "greyd/dynamic" && dynamicBlock.atts.key === "dynamic_content" ) {
						hasNestedTemplate = true;
						return false;
					}
				});
 
				/**
				 * Fix orphaned saved dynamic contents
				 * 
				 * If more saved values exist than dynamic blocks, usually we have some
				 * orphaned values inside dynamic blocks. So we check every value, if 
				 * it's dynamic field is still present and is matching the right content
				 * type.
				 * 
				 * @since 1.2.2
				 */
				// if (
				// 	dynamic_blocks.length
				// 	&& values.length > dynamic_blocks.length
				// 	/** do not fix nested templates @since 1.2.5 */
				// 	&& !_.has(props.attributes, 'dynamic_parent')
				// 	/** do not fix nested templates @since 1.2.9 */
				// 	&& !( _.has(props.attributes, 'dynamic_nests') && typeof props.attributes.dynamic_nests !== 'undefined' &&  props.attributes.dynamic_nests.length )
				// 	/** do not fix templates with nested dynamic contents @since 1.3.9 */
				// 	&& !hasNestedTemplate
				// ) {
					
				// 	// get all dynamic field types keyed by title.
				// 	let fieldTypes = {};
				// 	for (var k in dynamic_blocks) {
				// 		let blck = dynamic_blocks[k];
				// 		fieldTypes[blck.atts.title] = blck.atts.key;
				// 	}
				// 	// console.log(dynamic_blocks);
				// 	// console.log( fieldTypes );
				// 	// console.log( values );

				// 	// filter the values to only include matching values
				// 	let newValues = {};
				// 	values.forEach( value => {
				// 		// only return values with matching name
				// 		if (value.dtitle in fieldTypes) {
				// 			// if there is another filed with the same name,
				// 			// use the one with matching content type.
				// 			if (value.dtitle in newValues) {
				// 				if (fieldTypes[value.dtitle] != value.dkey) return;
				// 			}
				// 			newValues[value.dtitle] = value;
				// 		}
				// 	});
				// 	newValues = Object.values(newValues);

				// 	// if the values changed, we update the attribute
				// 	if (!_.isEqual(values, newValues)) {
				// 		// console.log(newValues);
				// 		console.info("dynamic content values updated. New:", newValues, "Old:", values);
				// 		props.setAttributes( { dynamic_content: newValues } );
				// 	}
				// }

				// check nesting
				if (parent_id != -1) {
					// block is a nested block
					// add dynamic values to top level parent 
					// console.log(parent.attributes.dynamic_nests);
					var nest = {
						template: props.attributes.template,
						dynamic_blocks: dynamic_blocks
					};
					var nests = [];
					var parent = wp.data.select('core/block-editor').getBlock(parent_id);
					if ( parent ) {
						if (_.has(parent.attributes, "dynamic_nests") && typeof parent.attributes.dynamic_nests !== "undefined") {
							nests = parent.attributes.dynamic_nests;
						}
						var found = false;
						for (var i=0; i<nests.length; i++) {
							if (nests[i].template == props.attributes.template) {
								nests[i] = nest;
								found = true;
								break;
							}
						}
						if (!found) {
							nests.push(nest);
						}
						parent.attributes.dynamic_nests = nests;
					}
				}
				else if (_.has(props.attributes, "dynamic_nests") && typeof props.attributes.dynamic_nests !== "undefined") {
					// block is a top level parent
					// add nested dynamic values
					var is_dynamic = false;
					dynamic_blocks.forEach(function(db, ii) {
						if (db.atts.type == 'dynamic_content') is_dynamic = true;
					});
					if (is_dynamic) dynamic_blocks = [...dynamic_blocks, ...props.attributes.dynamic_nests];
					else props.attributes.dynamic_nests = undefined;
					// dynamic_blocks = [...dynamic_blocks, ...props.attributes.dynamic_nests];
				}
				// console.log(blocks_template);
				// console.log(_.clone(dynamic_blocks));
				
			}

			var template_blocks = {
				mode: (typeof blocks !== 'undefined') ? (dynamic_blocks.length > 0 ? "success" : "info") : "", 
				postType: dtemplate.type, 
				href: 'post.php?post='+dtemplate.id+'&action=edit',
				blocks: blocks_template,
				content: dynamic_blocks.length > 0 ? dynamic_blocks : "no_content",
				update: updateInnerBlocks,
			};
			// console.log(template_blocks);

			if (props.attributes.template != "") {
				if (dtemplate.blocks) {
					if (template.slug != "") {
						// console.log("resetting dynamic state");
						setTemplate( { slug: '', mode: '', href: '', content: '' } );
					}
				}
				else {
					if (props.isSelected && template.slug != props.attributes.template) {
						// console.log("crawling dynamic template");
						greyd.dynamic.getDynamicTemplate(props.attributes.template, template, setTemplate);
					}
				}
			}
			else {
				if (template.slug != "") {
					// console.log("resetting all");
					setTemplate( { slug: '', mode: '', href: '', content: '' } );
				}
			}

			
			let inner_content = null;

			// render placeholder
			if ( _.isEmpty(props.attributes.template) ) {
				inner_content = el( wp.components.Placeholder, {
					icon: typeof greyd.tools.getBlockIcon !== "undefined" ? greyd.tools.getBlockIcon('dynamic') : el( "svg", {
						width: 24,
						height: 24,
						viewBox: "0 0 24 24",
						fill: "none",
						xmlns: "http://www.w3.org/2000/svg",
						className: "greyd-dynamic-template-block-icon"
					}, [
						el( "path", {
							fillRule: "evenodd",
							clipRule: "evenodd",
							d: "M12.0038 3.13574L19.7464 7.57019V16.4652L12.0038 20.8626L4.26123 16.4652V7.57019L12.0038 3.13574ZM12.7537 18.7116L18.2464 15.5921V9.27025L12.7537 12.4476V18.7116ZM12.0038 4.86434L17.4625 7.99077L12.0038 11.1485L6.54502 7.99078L12.0038 4.86434Z"
						} )
					] ),
					label: __( "Select template", 'greyd_hub' ),
					instructions: __( "Select a template from the list or create a new template.", 'greyd_hub' ),
				}, [
					el( wp.components.BaseControl, { }, [
						// choose template
						el( greyd.components.OptionsControl, {
							style: { padding: '0px 24px 0px 8px', height: '36px' },
							value: '',
							options: greyd.dynamic.getTemplates('', permission, !!props.attributes.useSlug),
							onChange: function(value) { 
								// console.log(value);
								props.attributes.dynamic_nests = undefined;
								replaceInnerBlocks(props.clientId, []);
								props.setAttributes( { template: value } );
							},
						} ),
					] ),
					permission && el( wp.components.Button, {
						className: 'is-primary',
						style: { marginLeft: '20px' },
						onClick: function() { window.open('edit.php?post_type=dynamic_template&wizard=add', '_blank') },
					}, __("Create new template", 'greyd_hub') ),

					el( 'div', {
						style: { width: '100%' }
					}, [
						el( wp.components.Tip, { }, __("Did you know that you can type '++' to directly insert a template?", 'greyd_hub') )
					] )
				] );
			}
			// render reloading spinner
			else if ( isReloading ) {
				inner_content = el( 'div', {
					style: {
						width: isReloading.width+'px',
						height: isReloading.height+'px'
					} }, el( wp.components.Spinner ) );
			}
			// render inner blocks
			else if ( dtemplate.blocks ) {
				inner_content = el( 'div', { className: 'block-library-block__reusable-block-template-container' }, [
					el( wp.blockEditor.InnerBlocks, { 
						template: template_blocks.blocks, 
						templateLock: 'all' 
					} ) 
				] );
			}
			// render loading spinner
			else if ( template.slug != "" && template.slug != props.attributes.template ) {
				inner_content = el( wp.components.Spinner );
			}
			// render shortcode template
			else {
				var postId = (_.has(props.attributes, "postId") && !_.isEmpty(props.attributes.postId)) ? props.attributes.postId : props.context.postId;
				inner_content = [el( wp.serverSideRender, {
					block: 'greyd/dynamic',
					attributes: {
						postId: ""+postId+"", // make sure type is string
						template: props.attributes.template,
						dynamic_content: props.attributes.dynamic_content,
					},
					className: props.attributes?.className || props?.className || '',
					httpMethod: 'POST',
				} )];
			}

			return [

				// preview
				el( 'div', { id: props.attributes.anchor }, inner_content ),

				// toolbar
				props.attributes.template != "" && permission && el( wp.blockEditor.BlockControls, {}, [
					// reload
					el( wp.components.ToolbarGroup, {}, [
						el( wp.components.ToolbarItem, {
							as: wp.components.ToolbarButton,
							label: __("Reload", 'greyd_hub'),
							icon: el( "svg", {
								xmlns: "http://www.w3.org/2000/svg",
								width: 24, height: 24, viewBox: "0 0 24 24",
							}, [
								el( "path", {
									d: "m11.3 17.2-5-5c-.1-.1-.1-.3 0-.4l2.3-2.3-1.1-1-2.3 2.3c-.7.7-.7 1.8 0 2.5l5 5H7.5v1.5h5.3v-5.2h-1.5v2.6zm7.5-6.4-5-5h2.7V4.2h-5.2v5.2h1.5V6.8l5 5c.1.1.1.3 0 .4l-2.3 2.3 1.1 1.1 2.3-2.3c.6-.7.6-1.9-.1-2.5z"
								} )
							] ),
							onClick: () => {
								reloadTemplate();
							},
						} )
					] ),
					// edit
					el( wp.components.ToolbarGroup, { 
						className: 'show-label'  
					}, [
						el( wp.components.ToolbarItem, {
							as: wp.components.ToolbarButton,
							text: __("Edit template", 'greyd_hub'),
							icon: 'edit',
							onClick: () => {
								window.open(dtemplate.edit_link, '_blank')
							},
						} )
					] ),
					// reset
					el( wp.components.ToolbarGroup, { 
						className: 'show-label'  
					}, [
						el( wp.components.ToolbarItem, {
							as: wp.components.ToolbarButton,
							text: __("Reset", 'greyd_hub'),
							disabled: _.isEmpty( props.attributes?.dynamic_content ),
							onClick: () => {
								if ( _.isEmpty( props.attributes?.dynamic_content ) ) return;
								replaceInnerBlocks(props.clientId, []);
								props.setAttributes( { dynamic_content: [] } );
							},
						} )
					] ),
				] ),

				// sidebar
				el( wp.blockEditor.InspectorControls, {}, [
					el( wp.components.PanelBody, { title: __("Select template", 'greyd_hub'), initialOpen: true }, [
						// choose template
						el( greyd.components.OptionsControl, {
							style: { maxWidth: '100%' },
							value: !!props.attributes.useSlug ? dtemplate.slug : dtemplate.id,
							options: greyd.dynamic.getTemplates(
								!!props.attributes.useSlug ? dtemplate.slug : dtemplate.id,
								permission,
								!!props.attributes.useSlug
							),
							onChange: function(value) { 
								// console.log(value);
								props.attributes.dynamic_nests = undefined;
								replaceInnerBlocks(props.clientId, []);
								props.setAttributes( { template: value } );
							},
						} ),
						props.attributes.template != "" && permission ? [
							// edit template
							el( wp.components.Button, {
								variant: 'secondary',
								isSmall: true,
								className: 'has-text',
								href: dtemplate.edit_link,
								target: '_blank',
								text: __("Edit template", 'greyd_hub'),
								icon: 'external',
								iconPosition: 'right',
							} ),
							// replace with translation
							el( greyd.components.TranslationHint, {
								label: __("A translation has been found for the embedded template", 'greyd_hub')+": ",
								post_id: props.attributes.template,
								posts: greyd.data.all_templates,
								onClick: function(value) {
									// console.log("change to "+value);
									props.attributes.dynamic_nests = undefined;
									replaceInnerBlocks(props.clientId, []);
									props.setAttributes( { template: value } );
								}
							} )
						] : null
					] ),
					props.attributes.template != "" && 
					!greyd.tools.isChildOf(props.clientId, 'core/post-template') &&
					!greyd.tools.isChildOf(props.clientId, 'core/query') && 
					el( greyd.components.AdvancedPanelBody, {
						title: __("Display Post", 'greyd_hub'),
						initialOpen: false,
						holdsChange: _.has(props.attributes, 'postId') && !_.isEmpty(props.attributes.postId)
					}, [
						// choose post
						el( greyd.components.OptionsControl, {
							style: { maxWidth: '100%' },
							value: props.attributes.postId,
							options: greyd.dynamic.getPosts(),
							onChange: function(value) { 
								// console.log(value);
								props.setAttributes( { postId: value } );
							},
						} ),
						// info
						( !_.has(props.attributes, 'postId') || _.isEmpty(props.attributes.postId) ) && (
							el( wp.components.Tip, { }, __("Use the template to display the data of a single post.", 'greyd_hub') )
						)
					] ),
					el( wp.components.PanelBody, { title: __("Dynamic Elements", 'greyd_hub'), initialOpen: true }, [
						(
							_.isEmpty( props.attributes.template )
							? el( wp.components.Tip, { }, __("Please select a template to display dynamic content.", 'greyd_hub') )
							: greyd.dynamic.dynamicControls( props, ( dtemplate.blocks ? template_blocks : template ) )
						),
						// debug
						// el( 'pre', { }, 'mode: '+template.mode ),
						// reload button
						// el( wp.components.Button, {
						// 	className: 'is-primary',
						// 	style: { marginLeft: '20px' },
						// 	onClick: () => { updateInnerBlocks( props.attributes.dynamic_content ) }
						// }, __('reload', 'greyd_hub') ),
					] )
				] ),

				// inspector advanced
				el( wp.blockEditor.InspectorAdvancedControls, { }, [
					// use slug instead of id for saving
					el( wp.components.ToggleControl, {
						label: __("Use template slug", 'greyd_hub'),
						// help: isReferenceForced() ?
						// 	__("Template reference is forced by use of the filter 'greyd_dynamic_block_use_reference'.", 'greyd_hub') :
						// 	__("Use the slug instead of the ID as template reference.", 'greyd_hub'),
						disabled: isReferenceForced(),
						checked: !!props.attributes.useSlug,
						onChange: value => {

							props.setAttributes( { useSlug: !!value, template: !!value ? dtemplate.slug : ""+dtemplate.id+"" } )
						}
					} ),
					el( 'p', { className: 'greyd-inspector-help' }, [
						isReferenceForced() ?
							__("Template reference is forced by use of the filter 'greyd_dynamic_block_use_reference'.", 'greyd_hub') :
							__("Use the slug instead of the ID as template reference.", 'greyd_hub')
					] ),
				] )

			];
		},

	} );

} )( window.wp );