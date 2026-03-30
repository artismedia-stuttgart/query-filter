/**
 * Greyd.Blocks Editor Script for core Query Block extension.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __, _x, sprintf } = wp.i18n;
	var _ = lodash;

	/**
	 * Register custom attributes to core blocks.
	 * - core/query
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (name == 'core/query') {
			// console.log("register query");
			// keep displayLayout to enable smooth deprecation
			settings.attributes.displayLayout = { type: 'object' };
			// disable core deprecations to enable smooth displayLayout deprecation
			settings.deprecated = [];
			// advanced filter
			settings.attributes.advancedFilter = { type: 'array', default: [] };

			/**
			 * Variations for core/query block.
			 */
			settings.variations.unshift(
				{
					name: "template",
					title: __("Post Slider", 'greyd_hub'),
					description: __("Display posts with Dynamic Templates as sliders, grid, etc.", 'greyd_hub'),
					icon: 'slides',
					category: 'greyd-blocks',
					keywords: [ 'post', 'overview', 'slider', 'query', 'loop' ],
					attributes: {
						query: { perPage: 12, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
					},
					innerBlocks: [
						[ "core/post-template", { layout: { type: 'grid', columnCount: 3, items: 12, responsive: { md: { items: 8, columnCount: 2 }, sm: { items: 4, columnCount: 1 } } }, variation: 'slider' }, [
							[ "greyd/dynamic" ]
						] ]
					],
					example: {
						attributes: {
							query: { perPage: 1, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
						},
						innerBlocks: [
							{
								name: "core/post-template",
								attributes: { layout: { type: 'grid', columnCount: 1, items: 1, variation: 'slider' } },
								innerBlocks: [
									{
										name: "core/group",
										attributes: { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'stretch', verticalAlignment: 'space-between' }, style: { spacing: { margin: { top: "var:preset|spacing|medium", bottom: "var:preset|spacing|medium" } } } },
										innerBlocks: [
											{ name: "core/post-featured-image", attributes: { isLink: true, style: { color: [] } } },
											{ name: "core/post-date", attributes: { style: { typography: { fontSize: "16px", fontWeight: "500" } } } },
											{ name: "core/post-title", attributes: { level: 3, isLink: true, style: { spacing: { margin: { top: "var:preset|spacing|small", bottom: "var:preset|spacing|small" } }, fontSize: "medium" } } },
											{ name: "core/post-excerpt" }
										]
									}
								]
							}
						]
					},
					scope: [ "inserter", "block" ]
				},
				{
					name: "liveSearch",
					title: __("Live Search", 'greyd_hub'),
					description: __("Display posts with live filter capabilities", 'greyd_hub'),
					icon: greyd.tools.getCoreIcon( 'search' ),
					category: 'greyd-blocks',
					keywords: [ 'post', 'search', 'filter', 'slider', 'query', 'loop' ],
					attributes: {
						query: { perPage: 12, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
					},
					innerBlocks: [
						[ "greyd/search", { posttype: "post", greydStyles: { align: "flex-end", flexWrap: "nowrap", responsive: { sm: { wrap: "wrap" } } } }, [
							[ "greyd/search-input", { label: __("Search"), greydStyles: { width: "100%" } } ],
							[ "greyd/search-filter", { parentPosttype: "post", filterBy: "category", label: __("Filter"), placeholder: "select", greydStyles: { width: "max(200px, 50%)" } } ]
						] ],
						[ "core/spacer" ],
						[ "core/post-template", { layout: { type: 'grid', columnCount: 3, items: 12, responsive: { md: { items: 8, columnCount: 2 }, sm: { items: 4, columnCount: 1 } } }, variation: 'slider' }, [
							[ "core/group", { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'stretch', verticalAlignment: 'space-between' }, style: { spacing: { margin: { top: "var:preset|spacing|medium", bottom: "var:preset|spacing|medium" } } } }, [
								[ "core/post-featured-image", { isLink: true, style: { color: [] } } ],
								[ "core/post-date", { style: { typography: { fontSize: "16px", fontWeight: "500" } } } ],
								[ "core/post-title", { level: 3, isLink: true, style: { spacing: { margin: { top: "var:preset|spacing|small", bottom: "var:preset|spacing|small" } } }, fontSize: "medium" } ],
								[ "core/post-excerpt" ]
							] ]
						] ]
					],
					example: {
						attributes: {
							query: { perPage: 1, pages: 1, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
						},
						innerBlocks: [
							{
								name: "greyd/search",
								attributes: { posttype: "post", greydStyles: { align: "flex-end", flexWrap: "nowrap", responsive: { sm: { wrap: "wrap" } } } },
								innerBlocks: [
									{
										name: "greyd/search-input",
										attributes: { label: __("Search"), greydStyles: { width: "100%" } }
									},
									{
										name: "greyd/search-filter",
										attributes: { parentPosttype: "post", filterBy: "category", label: __("Filter"), placeholder: "select", greydStyles: { width: "max(200px, 50%)" } }
									}
								]
							},
							{ name: "core/spacer" },
							{
								name: "core/post-template",
								attributes: { layout: { type: 'grid', columnCount: 1, items: 1, variation: 'slider' } },
								innerBlocks: [
									{
										name: "core/group",
										attributes: { layout: { type: 'flex', orientation: 'vertical', justifyContent: 'stretch', verticalAlignment: 'space-between' }, style: { spacing: { margin: { top: "var:preset|spacing|medium", bottom: "var:preset|spacing|medium" } } } },
										innerBlocks: [
											{ name: "core/post-featured-image", attributes: { isLink: true, style: { color: [] } } },
											{ name: "core/post-date", attributes: { style: { typography: { fontSize: "16px", fontWeight: "500" } } } },
											{ name: "core/post-title", attributes: { level: 3, isLink: true, style: { spacing: { margin: { top: "var:preset|spacing|small", bottom: "var:preset|spacing|small" } }, fontSize: "medium" } } },
											{ name: "core/post-excerpt" }
										]
									}
								]
							}
						]
					},
					scope: [ "inserter", "block" ]
				},
				{
					name: "table",
					title: __("Post Table", 'greyd_hub'),
					description: __("Display posts as table", 'greyd_hub'),
					icon: greyd.tools.getBlockIcon( 'table' ),
					category: 'greyd-blocks',
					keywords: [ 'post', 'table', 'query', 'loop' ],
					attributes: {
						query: { perPage: 10, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
					},
					innerBlocks: [
						[ "greyd/post-table" ]
					],
					example: {
						attributes: {
							query: { perPage: 10, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
						},
						innerBlocks: [
							{ name: "greyd/post-table" }
						]
					},
					scope: [ "inserter", "block" ]
				},
				{
					name: "blank",
					title: __("Blank", 'greyd_hub'),
					icon: 'media-default',
					category: 'greyd-blocks',
					keywords: [ 'post', 'overview', 'query', 'loop' ],
					attributes: {
						query: { perPage: 12, pages: 0, offset: 0, postType: "post", order: "desc", orderBy: "date", author: "", search: "", exclude: [], sticky: "", inherit: false }
					},
					innerBlocks: [
						[ "core/post-template", { layout: { type: 'grid', columnCount: 3 } }, [
							[ "core/paragraph" ]
						] ]
					],
					scope: [ "block" ]
				},
			);
			// console.log(settings);
		}

		return settings;
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/query',
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

			// 
			// Extensions for inherited query
			// in single, archive and search templates.
			if (
				greyd.data.post_type == "dynamic_template" &&
				props.name == "core/query" &&
				_.has(props.attributes.query, 'inherit') &&
				props.attributes.query.inherit === true
			) {
				// console.log(props);
				// console.log("listing query");

				var query = {
					inherit: true, postType: "post", orderBy: "date", order: "desc", sticky: "", author: "", search: "", exclude: [],
					perPage: null, offset: 0, pages: 0,
				};
				query['perPage'] = props.attributes.query?.perPage ?? parseInt(greyd.data.posts_per_page);

				var isSingle = greyd.data.template_type == "single";
				var isArchive = greyd.data.template_type == "archives";
				var isSearch = greyd.data.template_type == "search";
				var postType = greyd.data.post_name.indexOf('-') > -1 ? greyd.data.post_name.split(/-(.*)/s)[1] : null;

				if ( greyd.data.template_type == "woo" ) {
					if ( greyd.data.post_name == "woo-product" ) {
						isSingle = true;
					} 
					else if ( greyd.data.post_name.indexOf("woo-product-") == 0 ) {
						isArchive = true;
					}
				}

				if ( isSingle || isArchive || isSearch ) {
					// query['perPage'] = parseInt(greyd.data.posts_per_page);
					if ( postType ) {

						/**
						 * Get the postType slug in archive, single & search templates
						 * based on the curretn template slug.
						 * 
						 * @example
						 * * search-myposttype       -> myposttype
						 * * search-myposttype-news  -> myposttype
						 * * search-my-posttype      -> my-posttype
						 * * search-my-posttype-news -> my-posttype
						 */
						if ( postType.indexOf('-') > -1 ) {
							var postTypeExists = false
							for (const pt of greyd.data.post_types) {
								if (pt.slug == postType) {
									postTypeExists = true;
									break;
								}
							}
							if ( !postTypeExists ) {
								postType = postType.slice( 0, postType.lastIndexOf('-') )
							}
						}

						/**
						 * In FSE templates, the archive template used for categories and tags
						 * is called "archives-category(-general)".
						 */
						if ( postType == 'category' || postType == 'tag' ) {
							postType = 'post';
						}
						// console.log( "dynamic post type:", postType );

						query['postType'] = postType;
					}
				}

				/**
				 * @since at least WP 6.8.2 this line leads to a react error in the site-editor on
				 * on single post templates when inserting a default post template:
				 * 
				 * Minified React error #185
				 * Maximum update depth exceeded. This can happen when a component repeatedly calls
				 * setState inside componentWillUpdate or componentDidUpdate. React limits the number
				 * of nested updates to prevent infinite loops.
				 * @see https://react.dev/errors/185?invariant=185
				 * 
				 * Therefore we comment it out for now.
				 */
				// if ( isSingle ) {
				// 	// from vc_post_tp
				// 	query['perPage'] = 1;
				// }

				// todo: taxQuery
				if ( !_.isEqual(props.attributes.query, query) ) {
					console.log("Query attributes updated:", "new:", query, "old:", props.attributes.query);
					props.attributes.query = query;
				}
					
				// console.log("inherit query", query);
			}

			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/query/list', 
		editBlockListHook 
	);
	

	/**
	 * Add custom edit controls to core blocks.
	 * - core/query
	 * 
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {
		
		return function( props ) {	
			
			/**
			 * =================================================================
			 *                          Query extensions
			 * =================================================================
			 */
			
			/**
			 * Extend query block.
			 */
			if (props.name == "core/query") {

				// console.log(props.attributes);
				// console.log("extending query");

				// make deep copy of advancedFilter once the block is created.
				// when a block is duplicated the attribute type 'array' is not deep cloned
				// resulting in 'synced' values between original and cloned block.
				var [ id, setID ] = wp.element.useState( false );
				if ( id != props.clientId ) {
					// don't manipulate attributes in site-editor previews
					if ( ( location.pathname.indexOf('site-editor.php') > -1 && location.search.includes('canvas=edit') == false ) == false ) {
						
						// make queryId unique
						if ( props.attributes?.queryId !== false ) {
							// console.log("query block created - look for duplicated query id", props);
							var queryBlocks = wp.data.select("core/block-editor").getBlocksByName('core/query');
							if ( queryBlocks && queryBlocks.length > 1 ) {
								queryBlocks = wp.data.select('core/block-editor').getBlocksByClientId(queryBlocks);
								// console.log(queryBlocks);
								var otherIds = [];
								queryBlocks.forEach( b => {
									if ( b.clientId != props.clientId ) {
										otherIds.push(b.attributes.queryId);
									}
								} );
								// console.log(otherIds);
								if ( otherIds.indexOf(props.attributes.queryId) > -1 ) {
									console.warn("duplicate queryId found ");
									var newQueryId = 1;
									while ( otherIds.indexOf(newQueryId) > -1 ) {
										newQueryId++;
									}
									if (
										( !_.has(props.attributes, 'dynamic_parent') || _.isEmpty(props.attributes.dynamic_parent) ) &&
										greyd.tools.isChildOf( props.clientId, "core/template-part" ) === false
									) {
										// change this
										console.info("change queryId to "+newQueryId);
										props.setAttributes( { queryId: newQueryId } );
									}
									else {
										// try to change other
										var found = false;
										queryBlocks.forEach( b => {
											if ( found ) return;
											if ( b.attributes.queryId == props.attributes.queryId ) {
												if (
													( !_.has(b.attributes, 'dynamic_parent') || _.isEmpty(b.attributes.dynamic_parent) ) &&
													greyd.tools.isChildOf( b.clientId, "core/template-part" ) === false
												) {
													// change other
													console.info("change other queryId to "+newQueryId);
													wp.data.dispatch('core/block-editor').updateBlockAttributes( b.clientId, { queryId: newQueryId } );
												}
												else {
													console.warn("can't resolve duplicate queryIds");
												}
												found = true;
											}
										} );
									}
								}
							}
						}

						if ( !_.isEmpty(props.attributes.advancedFilter) ) {
							// console.log("query block created - make deep copy of advancedFilter");
							var newFilter = [];
							props.attributes.advancedFilter.forEach( (filter) => {
								newFilter.push( { ...filter } );
							} );
							props.setAttributes( { advancedFilter: newFilter } );
						}
					}
					setID( props.clientId );
				}

				// sync items per page attributes with core/post-template
				const queryPerPage = _.get(props.attributes.query, 'perPage');
				if ( queryPerPage ) {
					const wrapper = wp.data.select( "core/block-editor" ).getBlocksByClientId( props.clientId )?.[0];
					wrapper?.innerBlocks?.forEach( child => {
						if ( child.name == "core/post-template" ) {
							const itemsPerPage = _.get(child.attributes.layout, 'items');
							// console.log( itemsPerPage, queryPerPage );
							if ( itemsPerPage != queryPerPage ) {
								// console.log("update post-template child");
								wp.data.dispatch('core/block-editor').updateBlockAttributes( child.clientId, { layout: { 
									...child.attributes.layout, 
									items: parseInt( queryPerPage ) 
								} } );
							}
						}
					} );
				}

				const isInherit = _.has(props.attributes, 'query') && _.has(props.attributes.query, 'inherit') && props.attributes.query.inherit;

				return el( wp.element.Fragment, { }, [
					// original block
					el( BlockEdit, props ),
					// sidebar
					el( wp.blockEditor.InspectorControls, { }, [
						// advancedFilter
						greyd.advancedFilter.makeAdvancedFilterPanel( props )
					] ), 
				] );

			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter( 
		'editor.BlockEdit', 
		'greyd/hook/query/edit', 
		editBlockHook 
	);

} )( window.wp );