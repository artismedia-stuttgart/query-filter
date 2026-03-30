/**
 * Editor Script for dynamic Template actions.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __, sprintf } = wp.i18n;
	var _ = lodash;

	/**
	 * detach template
	 */

	const detachTemplate = async ( block ) => {
		console.info( 'Detach Dynamic Template', block );

		// get template data
		var template = greyd.dynamic.getTemplate(block.attributes.template);

		// prepare template blocks
		var innerBlocks = disableOverrides( block.innerBlocks );
		// console.log(innerBlocks);
		
		// replace dynamic template block with template blocks
		await wp.data.dispatch( "core/block-editor" ).replaceBlocks(
			block.clientId,
			innerBlocks
		);

		greyd.tools.showSnackbar(
			sprintf( __( "Dynamic Template `%s` detached.", 'greyd_hub' ), template.title ),
			"success", // "info" "warning" "error"
		);

	};

	// recursively remove dynamic attributes
	const disableOverrides = ( blocks ) => {
		return blocks.map( ( block ) => {
			delete block.attributes.dynamic_fields;
			delete block.attributes.dynamic_value;
			delete block.attributes.dynamic_parent;
			delete block.attributes.lock;
			delete block.attributes.templateLock;
			if ( _.has(block.attributes, 'className') && !_.isEmpty(block.attributes.className) ) {
				var classes = block.attributes.className.split( ' ' ).filter( n => n && n != 'dyn' ); // remove empty and 'dyn'
				block.attributes.className = classes.join(' ')
			}
			if ( _.has(block.attributes, 'greydClass') && !_.isEmpty(block.attributes.greydClass) ) {
				block.attributes.greydClass = greyd.tools.getGreydClass(block);
			}
			if ( _.has(block.attributes, 'level') && !_.isEmpty(block.attributes.level) ) {
				block.attributes.level = parseInt(block.attributes.level);
			}
			return wp.blocks.cloneBlock(
				block,
				{},
				disableOverrides( block.innerBlocks )
			);
		} );
	}

	/**
	 * create template
	 */

	const createTemplate = async ( blocks, atts ) => {
		console.info( 'Create Dynamic Template', blocks, atts );

		// prepare categories
		var categories = [ ...atts.categories ];
		if ( atts.new_categories.length > 0 ) {
			// create new categories
			for ( var i=0; i<atts.new_categories.length; i++) {
				const term = await wp.data.dispatch( "core" ).saveEntityRecord( 'taxonomy', 'template_categories', { name: atts.new_categories[i] } );
				// console.log(term);
				if ( term ) categories.push(term.id);
				else {
					greyd.tools.showSnackbar(
						sprintf( __( "Template category `%s` could not be created!", 'greyd_hub' ), atts.new_categories[i] ),
						"warning"
					);
				}
			}
		}
		// console.log(categories);

		// prepare content
		var ids = blocks.map( block => block.clientId );
		if ( atts.overrides ) {
			blocks = enableOverrides(blocks);
		}
		var content = wp.blocks.serialize( blocks );
		// console.log(content);

		// create new template
		var template = {
			title: atts.title,
			content,
			status: 'publish',
			template_categories: categories,
		};
		const post = await wp.data.dispatch( "core" ).saveEntityRecord( 'postType', 'dynamic_template', template );
		// console.log(post);
		if ( !post ) {
			greyd.tools.showSnackbar(
				__( "There has been an error creating the new Dynamic Template!", 'greyd_hub' ),
				"error"
			);
			return false;
		}

		// update greyd.data
		var data = {
			id: post.id,
			slug: post.slug,
			title: post.title.rendered,
			type: "dynamic_template",
			blocks: true,
			lang: greyd.data.language.post,
			edit_link: greyd.data.urls.home+"/wp-admin/post.php?post="+post.id+"&action=edit",
			modified: "new"
		};
		greyd.data.all_templates.push( data );

		// create dynamic block
		var block = wp.blocks.createBlock( "greyd/dynamic", { template: ""+post.id+"" } );
		// replace old blocks with new dynamic block
		await wp.data.dispatch( "core/block-editor" ).replaceBlocks(
			ids,
			block
		);

		// result
		greyd.tools.showSnackbar(
			sprintf( __( "Dynamic Template `%s` created.", 'greyd_hub' ), data.title ),
			"success", // "info" "warning" "error"
		);
		return true;

	};

	// recursively enable dynamic fields
	const enableOverrides = ( blocks ) => {
		blocks.forEach( ( block, i ) => {
			// get dynamic_fields for current block
			var fields = greyd.dynamic.getFields(block.name);
			if ( fields.length > 0 ) {
				var dynamicFields = [];
				fields.forEach( (field, j ) => {
					if (
						(field.key == 'background/image/id' && (!_.has(block.attributes, 'background') || block.attributes.background?.type != 'image')) ||
						(field.key == 'background/anim/id' && (!_.has(block.attributes, 'background') || block.attributes.background?.type != 'animation')) ||
						(field.key == 'background/video/url' && (!_.has(block.attributes, 'background') || block.attributes.background?.type != 'video'))
					) {
						return;
					}
					dynamicFields.push( {
						key: field.key,
						title: field.key+'_'+greyd.tools.generateRandomID(),
						enable: true
					} );
				} );
				blocks[i].attributes.dynamic_fields = dynamicFields;
				var classes = _.has(block.attributes, 'className') && !_.isEmpty(block.attributes.className) ? block.attributes.className.split(' ') : [];
				classes = classes.filter(n => n && n != 'dyn'); // remove empty and 'dyn'
				classes.push('dyn');
				blocks[i].attributes.className = classes.join(' ')
			}
			if ( block.innerBlocks.length > 0 ) {
				blocks[i].innerBlocks = enableOverrides(block.innerBlocks);
			}
		} );
		return blocks;
	}

	/**
	 * create and detach template plugin
	 */

	wp.plugins.registerPlugin( 'greyd-dynamic-template-actions', {

		render: () => {

			// states
			var [ isModalOpen, setIsModalOpen ] = wp.element.useState( false );
			var [ isSaving, setIsSaving ] = wp.element.useState( false );
			var [ atts, setAtts ] = wp.element.useState( {
				title: false,
				categories: [],
				overrides: true
			} );

			// selection
			var exclude = [
				// other "template" blocks
				'core/block', 'core/pattern', 'core/template-part', 'greyd/dynamic',
				// child only blocks
				'core/column', 'greyd/accordion-item', 'greyd/button', 'greyd/hotspot', 'greyd/list-item', 'greyd/popover-button', 'greyd/tab'
			];
			var [ selected, categories ] = wp.data.useSelect( select => {
				var selected = [ select('core/block-editor').getSelectedBlock() ];
				if (selected[0] == null ) {
					selected = select('core/block-editor').getMultiSelectedBlocks();
				}
				var categories = select("core").getEntityRecords( 'taxonomy', 'template_categories' );
				return [ selected, categories ];
			} );
			// console.log(selected);
			if ( !selected || selected.length == 0 ) {
				// show nothing if no block is selected
				return;
			}

			if ( selected.length == 1 && selected[0].name == 'greyd/dynamic' ) {
				// "detach current template" menu item
				return el( wp.editor.PluginBlockSettingsMenuItem, {
					icon: "editor-unlink",
					label: __( "Detach Dynamic Template", 'greyd_hub' ),
					onClick: async () => detachTemplate(selected[0])
				} );
			}
		
			// maybe create template
			var create = true;
			selected.forEach( block => {
				if ( greyd.tools.isChildOf(block.clientId, 'greyd/dynamic') ) create = false;
				if ( exclude.indexOf(block.name) > -1 ) create = false;
			} );
			if ( !create ) {
				// show nothing
				return;
			}

			const closeModal = () => {
				// close and reset states
				setIsModalOpen( false );
				setIsSaving( false );
				setAtts( {
					title: false,
					categories: [],
					overrides: true
				} );
			};
			const submitModal = async () => {
				if ( !atts.title || isSaving ) {
					return;
				}
				// prepare attributes
				const attributes = {
					...atts,
					categories: [],
					new_categories: []
				};
				if ( atts.categories.length > 0 ) {
					// sort categories in existing and new ones
					atts.categories.forEach( ( category ) => {
						found = false;
						categories.forEach( ( cat ) => {
							if ( category == cat.name ) {
								found = true;
								attributes.categories.push( cat.id );
							}
						} );
						if ( !found ) attributes.new_categories.push( category );
					} );
				}
				// create template
				setIsSaving( true );
				if ( await createTemplate( selected, attributes ) ) {
					// close and reset
					closeModal();
				}
				else setIsSaving( false );
			};

			// "create template" menu item and modal
			return [
				// menu button
				el( wp.editor.PluginBlockSettingsMenuItem, {
					icon: greyd.tools.getBlockIcon('dynamic'),
					label: __( "Create Dynamic Template", 'greyd_hub' ),
					onClick: () => setIsModalOpen( true )
				} ),
				// modal
				isModalOpen && el( wp.components.Modal, {
					title: __( "Add New Dynamic Template", 'greyd_hub' ),
					onRequestClose: () => closeModal(),
					overlayClassName: "patterns-menu-items__convert-modal",
					focusOnMount: "firstContentElement",
					size: "small"
				}, [
					el( 'form', {
						onSubmit: (e) => {
							// create template from current selection
							e.preventDefault();
							submitModal();
						}
					}, [
						// inputs
						el( wp.components.__experimentalVStack, {
							spacing: "5"
						}, [
							// name
							el( wp.components.TextControl, {
								className: 'patterns-create-modal__name-input',
								label: __( "Name", 'greyd_hub' ),
								value: atts.title ? atts.title : "",
								placeholder: __( "My Dynamic Template", 'greyd_hub' ),
								onChange: ( value ) => setAtts( { ...atts, title: value } )
							} ),
							// categories
							el( wp.components.FormTokenField, {
								__experimentalExpandOnFocus: true,
								tokenizeOnBlur: true,
								className: "patterns-menu-items__convert-modal-categories",
								label: __( 'Categories', 'greyd_hub' ),
								value: atts.categories,
								suggestions: categories.map( category => category.name ),
								onChange: ( value ) => setAtts( { ...atts, categories: value } )
							} ),
							// dynamic fields
							el( wp.components.ToggleControl, {
								label: __( "Enable content overwrites", 'greyd_hub' ),
								help: __( "Gives all available block attributes a dynamic capability that allows overwriting the content", 'greyd_hub' ),
								checked: atts.overrides,
								onChange: () => setAtts( { ...atts, overrides: !atts.overrides } )
							} )
						] ),
						// buttons
						el( wp.components.__experimentalHStack, {
							justify: "right"
						}, [
							el( wp.components.Button, {
								variant: "tertiary",
								onClick: () => closeModal()
							}, __( "Cancel", 'greyd_hub' ) ),
							el( wp.components.Button, {
								variant: "primary",
								type: "submit",
								"aria-disabled": !atts.title || isSaving,
								isBusy: isSaving
							}, __( "Add", 'greyd_hub' ) )
						] )
					] )
				] )
			];

		}

	} );

} )( window.wp );

(function() { 

	/**
	 * heartbeat hook
	 */

	jQuery(function() {

		jQuery( document ).on( 'heartbeat-send', function ( event, data ) {
			// Add additional data to Heartbeat to trigger templates update.
			data.get_templates = 'true';
		});

		jQuery( document ).on( 'heartbeat-tick', function ( event, data ) {
			// Check for template data
			if ( !data.templates ) {
				return;
			}
		
			// console.log("checking...");
			if ( JSON.stringify(greyd.data.all_templates) !== JSON.stringify(data.templates.all_templates) ) {
				console.warn( 'new templates:', data.templates );

				// templates changed: get some details
				var after = [ ...data.templates.all_templates ];
				var change = { added: [], removed: [], changed: [] };
				greyd.data.all_templates.forEach( templateBefore => {
					var found = false;
					after.forEach( ( templateAfter, i ) => {
						if ( templateBefore.id == templateAfter.id ) {
							if ( templateBefore.modified != "new" && templateBefore.modified != templateAfter.modified ) {
								change.changed.push( templateAfter );
							}
							found = true;
							delete after[i];
						}
					} );
					if ( !found ) {
						change.removed.push(templateBefore);
					}
				} );
				after = after.filter( n => n );
				if ( after.length > 0 ) change.added = [ ...after ];
				// console.log(change);

				// set new templates data
				greyd.data.all_templates = [ ...data.templates.all_templates ];

				// show info
				// [ 'added', 'removed', 'changed' ].forEach( action => {
				// 	if ( change[action].length > 0 ) {
				// 		change[action].forEach( template => {
				// 			if (template.id == greyd.data.post_id ) return;
				// 			switch (action) {
				// 				case 'added':
				// 					greyd.tools.showSnackbar( sprintf( __( "New Dynamic Template `%s` has been created.", 'greyd_hub' ), template.title ) );
				// 					break;
				// 				case 'removed':
				// 					greyd.tools.showSnackbar( sprintf( __( "Dynamic Template `%s` has been deleted.", 'greyd_hub' ), template.title ) );
				// 					break;
				// 				case 'changed':
				// 					greyd.tools.showSnackbar( sprintf( __( "Dynamic Template `%s` has been modified.", 'greyd_hub' ), template.title ) );
				// 					break;
				// 			}
							
				// 		} );
				// 	}
				// } );
				
			}
		});

		// console.log('heartbeat hook: initialized');

	} );

} )(jQuery);