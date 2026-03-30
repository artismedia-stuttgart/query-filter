/**
 * Greyd.Blocks Editor Script for API Block.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;

	// console.log(greyd.data.settings);
	function getVariations() {
		var variations = [];
		Object.values(greyd.data.settings?.api?.apis).forEach( (api) => {
			if ( api.slug && api.blocks ) {
				// api.blocks?.forEach( (block) => {
				Object.values(api.blocks).forEach( (block) => {
					var route = false;
					if ( api.slug == block.route ) {
						route = {};
					}
					else {
						// api.routes?.forEach( (api_route) => {
						Object.values(api.routes)?.forEach( (api_route) => {
							if ( api_route.slug == block.route ) {
								route = api_route;
							}
						} );
					}
					if ( route ) {
						var slug = api.slug+'/'+block.route;
						var title = (api.title?? api.slug)+(route.title ? ": "+route.title : "");
						var description = route.description?? api.description?? " ";

						variations.push( {
							name: slug,
							title: title,
							description: description,
							category: 'greyd-blocks-apis',
							keywords: [ 'api', 'custom' ],
							attributes: { api: slug },
							scope: [ "inserter", "block", "transform" ],
							isActive: (block, variation) => block.api == variation.api,
						} );
					}
				} );
			}
		} );
		return variations;
	}

	/**
	 * Register API Block.
	 */
	wp.blocks.registerBlockType( 'greyd/api', {
		apiVersion: 3,
		title: __('API', 'greyd_hub'),
		description: __('Insert an API', 'greyd_hub'),
		icon: el( wp.components.Icon, { icon: 'rest-api', style: { transform: 'scaleX(-1)' } } ),
		category: 'greyd-blocks-apis',

		variations: getVariations(),

		attributes: {
			anchor: { type: "string" },
			greydClass: { type: 'string', default: '' },
			greydStyles: { type: 'object', default: {} },
			api: { type: "string", default: "" }, // variation attribute - no control
			vars: { type: 'object', default: {} },
			inputs: { type: 'object', default: {} },
			render: { type: "string", default: "" },
		},

		edit: function( props ) {
			// console.log(props);

			// make greydClass
			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// subscribe to viewport change
			var [subscribe, setSubscribe ] = wp.element.useState( { viewport: false } );
			var viewport = greyd.tools.getDeviceType();
			if ( !subscribe.viewport ) {
				setSubscribe( { ...subscribe, viewport: {
					unsubscribe: wp.data.subscribe(() => {
						// compare values
						var newViewport = greyd.tools.getDeviceType();
						if (viewport !== newViewport) {
							// console.log("viewport changed to "+newViewport);
							// set new viewport
							viewport = newViewport;
							// reset subscription
							if (subscribe.viewport) subscribe.viewport.unsubscribe();
							setSubscribe( { ...subscribe, viewport: false } );
						}
					})
				} } );
			}

			// api call states
			var [ busy, setBusy ] = wp.element.useState( false );
			var [ api, setApi ] = wp.element.useState( { 
				slug: props.attributes.api, 
				endpoint: false, 
				items: false
			} );
			// get api and items
			if ( busy === false ) {

				// check for changed api
				if ( api.slug != props.attributes.api ) {
					setApi( { 
						slug: props.attributes.api, 
						endpoint: false, 
						items: false
					} );
				}
				
				// get api
				if ( api.slug != "" ) {
					if ( api.endpoint === false ) {
						// get endpoint from apis settings
						endpoint = getEndpoint(api.slug);
						// console.log(endpoint);
						if ( endpoint ) {
							api.endpoint = endpoint;
							setApi( { ...api, endpoint: endpoint } );
						}
					}
					else if ( api.items === false ) {
						// set api vars
						if ( api.endpoint.vars && JSON.stringify(props.attributes.vars) != '{}' ) {
							Object.keys(props.attributes.vars).forEach( (key) => {
								if ( api.endpoint.vars[key] )
									api.endpoint.vars[key] = props.attributes.vars[key];
							} );
						}
						// call endpoint and get items
						greyd.headless.api.ajax( api.endpoint )
							.then( (result) => {
								console.log(result);
								if (result.type == 'json') {
									// json
									if ( !Array.isArray(result.json) ) {
										result.json = [ result.json ];
									}
									api.items = result.json;
									setApi( { ...api, items: result.json } );
								}
								else {
									// other
									// console.warn( result.data, result.type );
									api.items = [ result.data ];
									setApi( { ...api, items: [ result.data ] } );
								}
							} )
							.catch( (error) => {
								console.warn(error);
								api.items = [];
								setApi( { ...api, items: [] } );
							} )
							.finally( () => {
								setBusy( false );
							} );
						setBusy( true );
					}
				}

			}
			
			// get (inner) blocks
			var { blocks } = wp.data.useSelect( (select) => {
				return {
					blocks: select("core/block-editor").getBlocks(props.clientId)
				}
			}, [ props ] );
			// console.log(blocks);

			// get post-dummies once items from api are loaded
			var { items, itemsCount } = wp.element.useMemo( () => {
				return {
					items: api.items?.map ? api.items.map( ( item, index ) => ( {
						id: index
					} ) ) : false,
					itemsCount: api.items ? api.items.length : 0,
				};
			}, [ api ] );
			// console.log(items, itemsCount);

			// 
			// props
			
			var classNames = props.attributes.greydClass+" grid-container";
			var inner = {
				template: [[ 'greyd/dynamic' ]],
				directInsert: true,
			};

			const blockProps = wp.blockEditor.useBlockProps( { className: classNames } );
			const innerBlocksProps = wp.blockEditor.useInnerBlocksProps( { className: 'wp-block-api-item' }, inner );
			const blockPreviewProps = wp.blockEditor.__experimentalUseBlockPreview( { blocks, props: { className: 'wp-block-api-item' } } );

			// active state
			var [ activeItem, setActiveItem ] = wp.element.useState();
			// console.log(activeItem);
			const isActive = (id) => {
				return id === (activeItem || items[0].id);
			}

			// render active
			const makeActive = () => {
				// console.log("make active");
				return el( 'li', { 
					...innerBlocksProps,
					'data-id': activeItem || items[0].id
				} );
			};

			// render preview
			const makePreview = (id) => {
				// console.log("make preview");
				return el( 'li', { 
					...blockPreviewProps,
					tabIndex: 0,
					'data-id': id,
					role: 'button',
					onClick: () => {
						setActiveItem( id );
					},
					onKeyPress: () => {
						setActiveItem( id );
					},
					style: { display: isActive(id) ? 'none' : undefined }
				} );
			};

			// get value for responsive items preview
			const getPerPage = () => {
				if ( items ) {
					var perPage = props.attributes.greydStyles?.items ? parseInt(props.attributes.greydStyles.items) : (items ? items.length : 0);
					// console.log(items);
					// console.log(viewport);
					if ( viewport == "Tablet" && props.attributes.greydStyles?.responsive?.md?.items ) {
						perPage = parseInt(props.attributes.greydStyles.responsive.md.items);
					}
					else if ( viewport == "Mobile" && props.attributes.greydStyles?.responsive?.sm?.items ) {
						perPage = parseInt(props.attributes.greydStyles.responsive.sm.items);
					}
					return perPage;
				}
				return 0;
			}

			const renderApi = () => {
				// console.log(api.items);
				// console.log(items);

				if ( blocks && items ) {

					// set the responsive items value to slice the items array
					var perPage = getPerPage();
					// console.log(perPage);

					if (
						!items.length
						|| items.length == 0
						|| perPage == 0
					) {
						// no items
						return [ el( 'p', { ...blockProps }, __( 'No elements found.', 'greyd_hub' ) ) ];
					}

					// render items
					return [
						el( 'ul', { ...blockProps },
							items
							.slice( 0, perPage )
							.map( (item) => ( 
								el( wp.blockEditor.BlockContextProvider, {
									value: item.id,
									children:  [
										isActive( item.id ) ? 
										makeActive() : null, 
										makePreview( item.id )
									] 
								} ) 
							) )
						),
						el( greyd.components.RenderPreviewStyles, {
							selector: props.attributes.greydClass,
							styles: {
								"": props.attributes.greydStyles,
							}
						})
					];
				}

				// waiting spinner
				// console.log('waiting...');
				return [ el( wp.components.Spinner, { ...blockProps } ) ];
			};

			const renderApiInfo = () => {

				var info = [
					el( 'div', {}, itemsCount+" Items found in API response." ),
					el( 'br', {} ),
				];
				if ( props.attributes.render == "" ) {
					var tags = [];
					Object.keys(api.endpoint.block?.data_item ? api.endpoint.block.data_item : {})?.forEach( (item) => {
						var title = item;
						if ( api.endpoint.block.data_item[item].title ) {
							title = api.endpoint.block.data_item[item].title+" ("+item+")";
						}
						tags.push( el( 'div', {}, "- "+title) );
						if ( api.endpoint.block.data_item[item].description ) {
							tags.push( el( 'i', { style: { marginLeft: '16px' } }, api.endpoint.block.data_item[item].description) );
						}
					} );
					if ( tags.length == 0 ) {
						tags.push( el( 'i', {}, "None") );
					}
					info.push( 
						el( 'div', {}, "Item properties (dynamic tags):" ),
						...tags
					);
				}
				else {
					info.push( 
						el( 'div', {}, "To render the response data, add a filter called" ),
						el( 'code', {}, "greyd_render_block_api_data" ),
						el( 'div', {}, "as described in the Block preview" )
					);
				}

				return el( wp.components.Tip, {}, info );

			}

			const renderVars = () => {

				var vars = [];

				if ( api.endpoint?.vars ) {
					Object.keys(api.endpoint.vars).forEach( (key) => {
						vars.push(
							el( wp.components.TextControl, {
								label: key,
								value: props.attributes.vars[key]?? "",
								placeholder: api.endpoint.vars[key],
								autocomplete: 'off',
								onChange: function(value) { 
									// console.log("change: "+value);
									var vars = { ...props.attributes.vars };
									vars[key] = value;
									props.setAttributes( { vars: vars } );
									setApi( { ...api, items: false } );
								},
							} ),
							el( wp.components.ToggleControl, {
								label: "Show Input",
								checked: props.attributes.inputs[key]?? false,
								onChange: function(value) { 
									// console.log("change: "+value);
									var inputs = { ...props.attributes.inputs };
									inputs[key] = value;
									props.setAttributes( { inputs: inputs } );
								},
							} )
						);
					} );
				}

				return vars;

			}

			const renderInputs = () => {
				
				var inputs = [];

				if ( props.attributes?.inputs && api.endpoint?.vars ) {
					Object.keys(props.attributes.inputs).forEach( (key) => {
						if ( props.attributes.inputs[key] && api.endpoint.vars[key] ) {
							inputs.push( el( 'div', {
								className: "greyd-search-form",
								style: { marginBottom: "var(--Pmargin)" }
							}, [
								el( 'input', {
									className: "input",
									style: { width: "auto" },
									name: key,
									value: api.endpoint.vars[key]
								} ),
								el( 'button', {
									className: "button",
								},  "Search" )
							] ) );
						}
					} );
				}

				return inputs;

			}

			return el( wp.element.Fragment, {}, [

				// preview
				api.slug == "" ? [ 
					// empty
					el( 'pre', { ...blockProps }, "Greyd API - Please select an API Variation to show content." ),
				] : props.attributes.render == "" ? [
					// template
					...renderInputs(),
					...renderApi(),
				] : [
					// filter
					el( 'div', { ...blockProps }, [
						el( wp.serverSideRender, {
							block: 'greyd/api',
							attributes: { ...props.attributes },
							httpMethod: 'POST',
						} ),
					] ),
				],

				// // toolbar
				// el( wp.blockEditor.BlockControls, {}, [

				// ] ),

				// sidebar
				el( wp.blockEditor.InspectorControls, {}, [
					
					el( greyd.components.AdvancedPanelBody, {
						className: api.endpoint?.vars ? '' : 'hidden',
						title: __( 'Values', 'greyd_hub' ),
					}, [
						...renderVars()
					] ),

					el( greyd.components.AdvancedPanelBody, {
						title: __( 'Output', 'greyd_hub' ),
					}, [
						api.slug == "" ? [ 
							// empty
							el( wp.components.Tip, {}, "Please select an API Variation to show content." ),
						] : [
							// template or filter
							el( greyd.components.ButtonGroupControl, {
								value: props.attributes.render,
								options: [
									{ value: "", label: __( 'With template', 'greyd_hub' ) },
									{ value: "filter", label: __( 'With filter', 'greyd_hub' ) },
								],
								onChange: function(value) {
									props.setAttributes( { render: value } );
								},
							} ),
							// info
							renderApiInfo()
						]

					] ),

					el( greyd.components.StylingControlPanel, {
						className: api.slug == "" || props.attributes.render != "" ? 'hidden' : '',
						title: __('Layout', 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						blockProps: props,
						controls: [
							{
								label: __('Items', 'greyd_hub'),
								attribute: "items",
								control: wp.components.RangeControl,
								// control: wp.components.__experimentalNumberControl,
								min: 1, max: itemsCount,
							},
							{
								label: __('Columns', 'greyd_hub'),
								attribute: "--columns",
								control: wp.components.RangeControl,
								// control: wp.components.__experimentalNumberControl,
								min: 1, max: 12,
							},
						]
					}),

				] )

			] );
		},

		save: function( props ) {
			if (props.attributes.api != "" && props.attributes.render == "") {
				return el( wp.blockEditor.InnerBlocks.Content );
			}
			return null;
		},

	} );

	// 
	// Filters
	// 

	function addDynamicTags( options, mode, clientId ) {
		// console.log(options, mode, clientId);
		
		var endpoint = getApi(clientId);
		if ( endpoint ) {
			return addOptions( options, mode, endpoint );
		}
		else {
			return options;
		}

	}

	function getApi(clientId) {

		var endpoint = null;
		var apiParent = (clientId) ? greyd.tools.isChildOf(clientId, 'greyd/api') : false;
		if ( apiParent && apiParent.attributes?.api != "") {
			endpoint = getEndpoint(apiParent.attributes.api);
		}
		return endpoint;

	}

	function getEndpoint(slug) {

		slug = slug.split('/');
		var endpoint = null;
		Object.values(greyd.data.settings?.api?.apis).forEach( (api) => {
		// greyd.data.settings?.api?.apis?.forEach( (api) => {
			if ( api.slug == slug[0] ) {
				endpoint = {
					slug: slug.join('/'),
					title: api.title,
					base_url: api.base_url,
					url_path: api.url_path,
					url_atts: api.url_atts == "" ? [] : api.url_atts,
					headers: api.headers == "" ? [] : api.headers,
				};
				if ( slug.length > 1 ) {
					Object.values(api.routes)?.forEach( (route) => {
					// api.routes?.forEach( (route) => {
						if ( route.slug == slug[1] ) {
							endpoint.url_path = route.url_path;
							endpoint.url_atts = { ...endpoint.url_atts, ...(route.url_atts == "" ? [] : route.url_atts) };
							endpoint.headers = { ...endpoint.headers, ...(route.headers == "" ? [] : route.headers) };
						}
					} );
					Object.values(api.blocks).forEach( (block) => {
					// api.blocks?.forEach( (block) => {
						if ( block.route == slug[1] ) {
							endpoint.block = block;
							if ( block.vars ) endpoint.vars = block.vars;
						}
					} );
					// console.log(endpoint);
				}

			}
		} );
		return endpoint;
	}

	function addOptions( options, mode, endpoint ) {

		if ( endpoint !== null ) {

			var types = [];
			if (mode == 'trigger') types = [ 'url', 'file', 'email' ];
			if (mode == 'file') types = [ 'file' ];
			var all_types = (types.length == 0);
			
			var current_label = "API - "+endpoint.title;
			var current_options = [];
			
			Object.keys(endpoint.block?.data_item ? endpoint.block.data_item : {})?.forEach( (item) => {
				var value = endpoint.block.data_item[item];
				var title = value.title ? value.title : item;
				if ( all_types || types.indexOf(value.type) > -1 ) {
					current_options.push( {
						value: item, // 'home',
						label: (mode == 'trigger' ? 'Link to ' : '')+title, // __( 'Link zur Startseite', 'greyd_hub' ),
						icon: 'rest-api',
						keywords: [ 'api' ]
					} );
				}
			} );

			if (current_options.length > 0) {
				options.splice(1, 0, {
					label: current_label, options: current_options
				});
			}

		}

		return options;
	}
	
	/** @since 2.14.0 */
	wp.hooks.addFilter( 'greyd_blocks_dynamic_files', 'greyd', addDynamicTags );
	wp.hooks.addFilter( 'greyd.dynamic.triggerOptions', 'greyd', addDynamicTags );
	wp.hooks.addFilter( 'greyd.dynamic.tags.getRichTextOptions', 'greyd', ( options, clientId ) => addDynamicTags( options, '', clientId ) );


} )( window.wp );