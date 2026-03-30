/**
 * Greyd.Blocks Editor Script for core post-template Block extension.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __, sprintf } = wp.i18n;
	var _ = lodash;

	// get parent query
	const getParent = ( props ) => {
		var parent = greyd.tools.isChildOf(props.clientId, 'core/query');
		if ( !parent && props.context?.previewPostType ) {
			// fix previews (e.g. pattern)
			parent = { attributes: props.context };
		}
		return parent;
	}

	/**
	 * Register custom attributes to core blocks.
	 * - core/post-template
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (name == 'core/post-template') {
			// console.log("register post-template");
			// settings.attributes.perPage = { type: 'object' };
			settings.attributes.filter = { type: 'object' };
			settings.attributes.pagination = { type: 'object' };
			settings.attributes.arrows = { type: 'object' };
			settings.attributes.sorting = { type: 'object' };
			settings.attributes.animation = { type: 'object' };
			settings.attributes.loader = { type: 'object' };
			// post-slider variation
			settings.attributes.variation = { type: 'string', default: 'default' };
			settings.attributes.HTMLTags = { type: 'object' };
			settings.variations = [
				{
					name: 'post-template',
					title: __( 'Post Template', 'greyd_hub' ),
					icon: greyd.tools.getBlockIcon('postTempate'),
					scope: [ 'transform' ],
					isDefault: true,
					attributes: {
						setVariation: ''
					},
					isActive: ( blockAttributes, variationAttributes ) => {
						return blockAttributes.variation === variationAttributes.setVariation || blockAttributes.variation == 'default';
					}
				},
				{
					name: 'post-slider',
					title: __( 'Post Slider', 'greyd_hub' ),
					description: __( 'Enhanced post template with interactive slider features, like side arrows and pagination.', 'greyd_hub' ),
					icon: greyd.tools.getBlockIcon('postSlider'),
					scope: [ 'transform', 'inserter', 'block' ],
					attributes: {
						setVariation: 'slider'
					},
					isActive: ( blockAttributes, variationAttributes ) => {
						return blockAttributes.variation === variationAttributes.setVariation;
					}
				},
			];
			// console.log(settings);
		}

		return settings;
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/post-template',
		registerBlockTypeHook
	);


	/**
	 * Handle the post template block editor rendering.
	 * Decide whether to render the post-template block as post-slider variation.
	 * 
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			if (props.name != "core/post-template") {
				return el( BlockEdit, props );
			}

			var parent = getParent(props);
			// console.log(parent);

			
			/**
			 * Migrate deprecated animation attribute from parent query block.
			 */
			if ( parent && _.has(parent.attributes, 'animation') ) {
				const animation = greyd.tools.makeValues(defaults.animation, parent.attributes.animation);
				props.setAttributes( { animation: animation } );
				delete parent.attributes.animation;
			}

			/**
			 * Migrate deprecated layout attribute from parent query block.
			 */
			if ( parent && _.has(parent.attributes, 'displayLayout') ) {

				const newAtts = {
					layout: { type: 'default', items: 3 },
					// As soon as we have a displayLayout attribute, we convert it into a slider.
					setVariation: 'slider'
				};

				// items/columns per page
				if ( parent.attributes?.displayLayout?.items ) {
					newAtts.layout.items = parent.attributes.displayLayout.items;
				}
				else if ( parent.attributes?.query?.perPage ) {
					newAtts.layout.items = parseInt( parent.attributes.query.perPage );
				}

				// responsive columns
				if ( parent.attributes?.displayLayout?.type == 'flex' ) {
					newAtts.layout = { ...newAtts.layout, type: 'grid', columnCount: 3 };
					if ( parent.attributes.displayLayout.columns ) {
						newAtts.layout.columnCount = parent.attributes.displayLayout.columns;
					}
					if ( parent.attributes?.displayLayout?.responsive ) {
						newAtts.layout.responsive = {};
						Object.keys(parent.attributes.displayLayout.responsive).forEach( key => {
							newAtts.layout.responsive[key] = { ... parent.attributes.displayLayout.responsive[key] };
							if ( newAtts.layout.responsive[key].columns ) {
								newAtts.layout.responsive[key].columnCount = newAtts.layout.responsive[key].columns;
								delete newAtts.layout.responsive[key].columns;
							}
						} );
					}
				}
				
				// set new attributes
				props.setAttributes( newAtts );
				delete parent.attributes.displayLayout;

				console.info(
					"Query & Post Template block updated:\n",
					"`displayLayout` attributes moved from `core/query` block to `core/post-template` block. New attributes:\n",
					newAtts
				);
			}

			/**
			 * Detect variation by attributes and set to slider if necessary.
			 */
			if ( props.attributes.variation == 'default' ) {
				// old block (pre refactoring) or new inserted
				if (
					!props.isSelected &&
					(
						props.attributes.filter?.enable === true ||
						props.attributes.pagination?.enable ||
						( !_.isEmpty( props.attributes.pagination ) && props.attributes.pagination?.enable !== false ) ||
						props.attributes.arrows?.enable === true ||
						props.attributes.sorting?.enable === true ||
						!_.isEmpty( props.attributes.animation )
					)
				) {
					// convert to slider variation
					props.setAttributes( { variation: 'slider' } );
					console.info(
						"`core/post-template` Block updated to `post-slider` variation.",
						props.attributes
					);
				}
				else {
					// save default variation
					props.setAttributes( { variation: '' } );
					console.info(
						"`core/post-template` Block detected.",
						props.attributes
					);
				}
			}

			/**
			 * Handle variation switch
			 */
			if (
				typeof props.attributes.setVariation !== 'undefined' &&
				props.attributes.setVariation != props.attributes.variation
			) {
				console.log("`core/post-template` variation switched from `"+props.attributes.variation+"` to `"+props.attributes.setVariation+"`");
				// setVariation(props.attributes.variation);
				var new_atts = { variation: props.attributes.setVariation };
				if ( props.attributes.setVariation == "" ) {
					var deactivated = [];
					if ( props.attributes.filter?.enable === true ) {
						new_atts.filter = { ...props.attributes.filter, enable: false };
						deactivated.push( __('Filter', 'greyd_hub') );
					}
					if ( props.attributes.sorting?.enable === true ) {
						new_atts.sorting = { ...props.attributes.sorting, enable: false };
						deactivated.push( __("Order", 'greyd_hub') );
					}
					if ( props.attributes.arrows?.enable === true ) {
						new_atts.arrows = { ...props.attributes.arrows, enable: false };
						deactivated.push( __("Side arrows", 'greyd_hub') );
					}
					if ( props.attributes.pagination?.enable !== false ) {
						new_atts.pagination = { ...props.attributes.pagination, enable: false };
						deactivated.push( __("Classic pagination", 'greyd_hub') );
					}
					// notice for deactivated features
					if ( !_.isEmpty(deactivated) ) {
						var msg = sprintf( __( "Slider features `%s` deactivated.", 'greyd_hub'), deactivated.join("`, `") );
						// console.info( msg );
						greyd.tools.showSnackbar( msg, 'warning' );
					}
				}
				props.setAttributes( new_atts );
				delete props.attributes.setVariation;
			}

			/**
			 * Render post-slider variation.
			 */
			if ( props.attributes.variation == 'slider' ) {
				// console.log('render post-slider variation', props.attributes);
				return editPostSlider( BlockEdit, props );
			}

			// return original block
			// console.log('render original post-template block');
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	/**
	 * Custom post-slider variation for core/post-template.
	 */
	var editPostSlider = ( BlockEdit, props ) => {

		/**
		 * =================================================================
		 *                          Post Slider extensions
		 * =================================================================
		 */

		/**
		 * Extend post-template block as slider
		 */
		if (props.name == "core/post-template" && props.attributes.variation == 'slider' ) {

			// 
			// states
			var [mode, setMode ] = wp.element.useState("");
			// get parent query
			var parent = getParent(props);
			// console.log(parent);

			// console.log('mode: '+mode);
			if ( mode != "" && ( !props.isSelected || props.attributes.variation != 'slider' ) ) {
				// reset mode
				setMode("");
			}

			/**
			 * Live Search support
			 * @since 1.2.0
			 */
			const liveSearchEnabled = (
				greyd.data.settings?.advanced_search?.live_search === "true" &&
				greyd.data?.template_type === 'search' &&
				parent?.attributes?.query?.inherit
			);

			// sync items per page attributes with core/query
			if ( parent && props.isSelected ) {
				const itemsPerPage = _.get(props.attributes.layout, 'items');
				const queryPerPage = _.get(parent.attributes.query, 'perPage');
				// console.log( itemsPerPage, queryPerPage );
				if ( itemsPerPage && itemsPerPage != queryPerPage ) {
					if ( parent?.attributes?.query?.inherit ) {
						// console.log("update items");
						wp.data.dispatch('core/block-editor').updateBlockAttributes( props.clientId, { layout: { 
							...props.attributes.layout, 
							items: parseInt( queryPerPage ) 
						} } );
					}
					else {
						// console.log("update parent query");
						wp.data.dispatch('core/block-editor').updateBlockAttributes( parent.clientId, { query: { 
							...parent.attributes.query, 
							perPage: parseInt( itemsPerPage ) 
						} } );
					}
				}
			}

			// 
			// subscriptions
			var [subscribe, setSubscribe ] = wp.element.useState( { parent: false, viewport: false } );
			// subscribe to viewport change
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
			// subscribe to parent change
			if ( parent && !subscribe.parent ) {
				setSubscribe( { ...subscribe, parent: {
					unsubscribe: wp.data.subscribe(() => {
						// compare values
						var newParent = getParent(props);
						if (!newParent || JSON.stringify(parent.attributes) !== JSON.stringify(newParent.attributes)) {
							// console.log("parent atts changed");
							// set new parent
							parent = newParent;
							// reset subscription (and trigger redraw with new parent atts)
							if (subscribe.parent) subscribe.parent.unsubscribe();
							setSubscribe( { ...subscribe, parent: false } );
						}
					})
				} } );
			}


			/**
			 * (1) values
			 */

			// sorting dropdown
			const sortingLabels = {
				date_DESC: __("Chronological (newest first)", 'greyd_hub'),
				date_ASC: __("Chronological (oldest first)", 'greyd_hub'),
				title_ASC: __("Alphabetical (ascending)", 'greyd_hub'),
				title_DESC: __("Alphabetical (descending)", 'greyd_hub'),
				...( greyd.data.settings?.advanced_search?.postviews_counter == "true" ? {
					views_DESC: __("Post views", 'greyd_hub')
				} : {} ),
				...( greyd.data.settings?.advanced_search?.relevance == "true" ? {
					relevance_DESC: __("Relevance", 'greyd_hub')
				} : {} ),
			};

			// defaults for sorting, pagination and arrows
			const defaults = {
				filter: { 
					enable: false, 
					position: 'top', 
					align: 'end',

					inputStyle: '',
					custom: false,
					customStyles: {},
					empty: '',
					showTaxTitle: false,

					greydClass: greyd.tools.getGreydClass(props, 'filter.greydClass'), // generateGreydClass(),
					greydStyles: {
						width: '',
						margin: {},
					},
				},
				sorting: { 
					enable: false, 
					position: 'top', 
					align: 'end',

					inputStyle: '',
					custom: false,
					customStyles: {},
					options: {
						date_DESC: '',
						date_ASC: '',
						title_DESC: '',
						title_ASC: '',
						views_DESC: '',
						relevance_DESC: '',
					},

					greydClass: greyd.tools.getGreydClass(props, 'sorting.greydClass'), // generateGreydClass(),
					greydStyles: {
						width: '',
						margin: {},
					},
				},
				pagination: { 
					enable: true, 
					position: 'bottom', 
					// align: 'center',
					overlap: false, 

					type: 'icon', 
					text_type: '',
					icon_type: 'icon',
					icon_normal: 'icon_circle-empty',
					icon_active: 'icon_circle-slelected',
					img_normal: -1, 
					img_active: -1,
					maxnum: -1,

					arrows_type: 'icon',
					icon_previous: 'arrow_left', 
					icon_next: 'arrow_right',
					img_previous: -1, 
					img_next: -1,

					greydClass: greyd.tools.getGreydClass(props, 'pagination.greydClass'), // generateGreydClass(),
					greydStyles: {
						justifyContent: 'center',
						color: '',
						opacity: '',
						fontSize: '',
						padding: { top: '', right: '', bottom: '', left: '' },
						gutter: ''
					},
					},
				arrows: { 
					enable: false,
					overlap: false, 

					type: 'icon', 
					icon_previous: 'arrow_left', 
					icon_next: 'arrow_right',
					img_previous: -1, 
					img_next: -1,

					greydClass: greyd.tools.getGreydClass(props, 'arrows.greydClass'), // generateGreydClass(),
					greydStyles: {
						alignItems: 'center',
						color: '',
						opacity: '',
						fontSize: '',
						padding: { top: '', right: '', bottom: '', left: '' }
					},
				},
				animation: {
					anim: '',
					loop: false,
					autoplay: false,
					interval: 5,
					duration: 200,
					height: '',
					height_custom: '500px',
					scroll_top: false,
					url_param: false,
				},
				loader: {
					style: '',
					size: '',
					greydClass: greyd.tools.getGreydClass(props, 'loader.greydClass'), // generateGreydClass(),
					greydStyles: {},
				},
				HTMLTags: {
					parent: 'div',
					child: 'article',
				}
			};

			// computes values
			var values = {
				filter: greyd.tools.getValues(defaults.filter, props.attributes.filter),
				sorting: greyd.tools.getValues(defaults.sorting, props.attributes.sorting),
				pagination: greyd.tools.getValues(defaults.pagination, props.attributes.pagination),
				arrows: greyd.tools.getValues(defaults.arrows, props.attributes.arrows)
			};
			if ( liveSearchEnabled ) {
				values.loader = greyd.tools.getValues( defaults.loader, props.attributes.loader);
			}

			// remove deprecated atts
			if (_.isEmpty(values.pagination.greydStyles, 'justifyContent')) {
				values.pagination.greydStyles.justifyContent = defaults.pagination.greydStyles.justifyContent;
			}
			if (_.isEmpty(values.arrows.greydStyles, 'alignItems')) {
				values.arrows.greydStyles.alignItems = defaults.arrows.greydStyles.alignItems;
			}

			// props
			var filterProps = {
				attributes: values.filter,
				setAttributes: function(value) {
					// console.log(value);
					setValues('filter', value);
				}
			};
			var sortingProps = {
				attributes: values.sorting,
				setAttributes: function(value) {
					// console.log(value);
					setValues('sorting', value);
				}
			};
			var paginationProps = {
				attributes: values.pagination,
				setAttributes: function(value) {
					setValues('pagination', value);
				}
			};
			var arrowProps = {
				attributes: values.arrows,
				setAttributes: function(value) {
					// console.log(value);
					setValues('arrows', value);
				}
			};
			var loaderProps = {
				attributes: values.loader,
				setAttributes: function(value) {
					props.setAttributes( { loader: { ...values.loader, ...value } } )
				}
			};


			var setValues = function(slug, value) {
				if (typeof value !== 'undefined') {
					var new_values = false;
					if (_.has(value, 'greydStyles')) {
						// console.log(value.greydStyles);
						// console.log(values.arrows.greydStyles);
						new_values = { ...values[slug], greydStyles: value.greydStyles };
					}
					if (_.has(value, 'customStyles')) {
						new_values = { ...values[slug], customStyles: value.customStyles };
					}
					else if (_.has(value, slug)) {
						// console.log(value);
						new_values = greyd.tools.makeValues(defaults[slug], { ...values[slug], ...value[slug] });
					}
					if (new_values) props.setAttributes( { [slug]: new_values } );
				}

			};

			var setAnimation = function(values) {
				var new_values = greyd.tools.makeValues(defaults.animation, values.animation);
				props.setAttributes( { animation: new_values } );
			};

			var getImageUrl = function(id) {
				if ( _.has( greyd.data.media_urls, id ) ) {
					return greyd.data.media_urls[id].src
				}
				else {
					return "";
				}
			};


			/**
			 * (2) controls
			 */

			// prepare deprecation of 'filter' and 'order'
			var deprecatedHint = true; // enable to show deprecation hints on features
			var deprecated = false;     // enable to hide feature when settings are empty (next step)
			// deprecation popover states
			var popoverClosed = { filter: false, order: false };
			var [ popoverActive, setPopoverActive ] = wp.element.useState( popoverClosed );

			// deprecation popover with tip
			const makeDeprecationPopover = function(feature) {
				return deprecatedHint && el( 'span', { style: { width: '100%', margin: '0 5px' } },
					el( wp.components.Button, {
						icon: 'info',
						style: { height: '20px' },
						onClick: () => setPopoverActive( { ...popoverClosed, [feature]: true } )
					} ),
					popoverActive[feature] && el( wp.components.Popover, {
						className: 'components-greyd-deprecated-hint-popover',
						focusOnMount: true,
						placement: 'left-start',
						noArrow: false,
						onFocusOutside: ( event ) => setPopoverActive( popoverClosed )
					}, makeDeprecationTip( feature ) )
				);
			};
			// deprecation tip
			const makeDeprecationTip = function(feature) {
				return deprecatedHint && el( wp.components.Tip, {}, [
					el( 'p', {
						style: { fontWeight: '600', margin: '10px 0' }
					}, sprintf( __('The `%s` feature is deprecated.', 'greyd_hub'), feature ) ),
					el( 'p', {
						style: { margin: '10px 0' }
					}, __( 'You can now place the Greyd Search together with the post slider in a query loop to filter or sort posts using blocks.', 'greyd_hub' ) ),
				] );
			};
			// next step: hide feature when unused
			const hideDeprecated = function(feature) {
				var settings = feature == 'filter' ? props.attributes.filter : props.attributes.sorting;
				return deprecated && !settings;
			};

			// performance hint if total posts > 50
			const [ snackbarShown, setSnackbarShown ] = wp.element.useState( false );
			const makePerformanceTip = function() {
				if ( !parent?.attributes?.query?.inherit && total > 50 && props.isSelected && !snackbarShown ) {
					greyd.tools.showSnackbar(
						sprintf( __('This loop renders %s posts on %s pages.', 'greyd_hub'), total, pages )
						+ ' ' + (
							total > 500 ?
							__('There will be performance problems in the frontend.', 'greyd_hub' ) :
							__('This amount of posts may result in performance problems in the frontend.', 'greyd_hub' )
						)
						+ ' ' + __('We recommend you to adjust the maximum number of pages in the parent query loop block.', 'greyd_hub' )
					);
					setSnackbarShown(true);
				}
				else if ( !parent?.attributes?.query?.inherit && total <= 50 && props.isSelected && snackbarShown ) {
					setSnackbarShown(false);
				}
			}

			const renderHTMLTagsSelect = () => {
				return [
					el( greyd.components.AdvancedPanelBody, {
						title: __("HTML Structure", 'greyd_hub'),
						initialOpen: false,
						holdsChange: !_.isEmpty( _.get(props.attributes, 'HTMLTags') ) && (
							_.get(props.attributes, 'HTMLTags.parent') != defaults.HTMLTags.parent
							|| _.get(props.attributes, 'HTMLTags.child') != defaults.HTMLTags.child
						)
					}, [
						el( greyd.components.HTMLTagSelectControl, {
							parentTagLabel: __("Wrapper HTML Element", 'greyd_hub'),
							childTagLabel:  __("Article HTML Element", 'greyd_hub'),
							parentTags: [
								{ label: '<div>', value: 'div' },
								{ label: '<section>', value: 'section' },
								{ label: '<nav>, <ul>', value: ['nav', 'ul'] },
								{ label: '<ul>', value: 'ul' },
							],
							childTags: [
								{ label: '<article>', value: 'article' },
								{ label: '<li>', value: 'li' },
								{ label: '<div>', value: 'div' },
							],
							value: props.attributes.HTMLTags,
							onChange: (value) => {
								props.setAttributes({ HTMLTags: { parent: value.parent, child: value.child } });
							}
						})
					])
				]
			}

			// main inspector controls: elements, animation, (responsive) grid settings
			const mainControls = function() {
				return (
					mode == "" ? [

						// grid columns
						el( greyd.components.StylingControlPanel, {
							title: __("Grid settings", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							parentAttr: "layout",
							blockProps: props,
							controls: [
								...parent?.attributes?.query?.inherit ? [] : [ {
									label: __("Elements per page", 'greyd_hub'),
									attribute: "items",
									control: wp.components.__experimentalNumberControl,
									min: 1,
									// control: wp.components.RangeControl,
									// min: 1, max: 12,
								} ],
								..._.get(props.attributes, 'layout.type', '') !== 'grid' ? [] : [ {
									label: __("Columns", 'greyd_hub'),
									attribute: "columnCount",
									control: __RangeNumberControl,
									min: 1, max: 16,
								} ],
								{
									label: __("Gap", 'greyd_hub'),
									attribute: "gap",
									control: greyd.components.RangeUnitControl,
									supportsPresets: true,
								},
								{
									label: __("Initial Breakpoint", 'greyd_hub'),
									attribute: "initial",
									control: greyd.components.ButtonGroupControl,
									hidden: { 'lg': true, 'md': true, 'sm': true },
									options: [
										{ label: __("Desktop", 'greyd_hub'), icon: el( greyd.components.GreydIcon, { icon: "desktop" } ), value: 'xl' },
										{ label: __("Tablet", 'greyd_hub'), icon: el( greyd.components.GreydIcon, { icon: "laptop" } ), value: 'lg' },
										{ label: __("Tablet small", 'greyd_hub'), icon: el( greyd.components.GreydIcon, { icon: "tablet" } ), value: 'md' },
										{ label: __("Mobile", 'greyd_hub'), icon: el( greyd.components.GreydIcon, { icon: "mobile" } ), value: 'sm' },
									],
								}
							]
						} ),

						// elements
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Slider elements", 'greyd_hub'), 
							initialOpen: false,
							holdsChange: values.pagination.enable || values.arrows.enable
						}, [
							// hint if too many total posts
							// arrows
							liveSearchEnabled ? null : el( 'div', { className: 'is-flex-space-between' },
								el( wp.components.ToggleControl, {
									label: __("Side arrows", 'greyd_hub'),
									checked: values.arrows.enable,
									onChange: function(value) { 
										arrowProps.setAttributes( { arrows: { ...values.arrows, enable: value } } ); 
									},
								} ),
								(values.arrows.enable) ? el( wp.components.Button, {
									icon: 'admin-tools',
									style: { height: '20px' },
									onClick: function() { setMode("arrows") }
								}) : el( 'div' ),
							),
							// pagination
							liveSearchEnabled ? null : el( 'div', { className: 'is-flex-space-between' },
								el( wp.components.ToggleControl, {
									label: __("Pagination", 'greyd_hub'),
									checked: values.pagination.enable,
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, enable: value } } ); 
									},
								} ),
								(values.pagination.enable) ? el( wp.components.Button, {
									icon: 'admin-tools',
									style: { height: '20px' },
									onClick: function() { setMode("numbers") }
								}) : el( 'div' ),
							),
							// load more
							liveSearchEnabled ? el( 'div', {
								className: 'is-flex-space-between',
								onClick: function() { setMode("loader") }
							}, [
								el( 'span', {}, __("\"Load more\" button", 'greyd_hub') ),
								el( wp.components.Button, {
									icon: 'admin-tools',
									style: { height: '20px' }
								})
							] ) : null,
							makePerformanceTip(),
						] ),
						
						// animation
						el( greyd.components.AdvancedPanelBody, { 
							className: (_.get(parent.attributes, 'query.inherit', false) == false) ? '' : 'hidden',
							title: __('Slider animation', 'greyd_hub'), 
							initialOpen: false,
							holdsChange: !_.isEmpty( _.get(props.attributes, 'animation') ) && (
								_.get(props.attributes, 'animation.anim') != defaults.animation.anim
								|| _.get(props.attributes, 'animation.duration') != defaults.animation.duration
								|| _.get(props.attributes, 'animation.loop') != defaults.animation.loop
								|| _.get(props.attributes, 'animation.autoplay') != defaults.animation.autoplay
								|| _.get(props.attributes, 'animation.height') != defaults.animation.height
								|| _.get(props.attributes, 'animation.scroll_top') != defaults.animation.scroll_top
							)
						}, [
							// posts_animation
							el( greyd.components.ButtonGroupControl, {
								label: __( "Animation type", 'greyd_hub' ),
								value: _.get(props.attributes, 'animation.anim', defaults.animation.anim),
								options: [
									{ label: __('Slide', 'greyd_hub'), value: '' },
									{ label: __('Fade', 'greyd_hub'), value: 'fade' },
									{ label: __('Cover flow', 'greyd_hub'), value: 'cover' },
									{ label: __('None', 'greyd_hub'), value: 'none' },
								],
								onChange: function(value) {
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, anim: value } } );
								},
							} ),
							(
								(
									_.get(props.attributes, 'animation.anim', defaults.animation.anim) === ''
									|| _.get(props.attributes, 'animation.anim', defaults.animation.anim) === 'fade'
								) &&
								el( wp.components.RangeControl, {
									label: __( "Transition duration in ms", 'greyd_hub' ),
									value: _.get(props.attributes, 'animation.duration', defaults.animation.duration),
									step: 10, min: 0, max: 1000,
									onChange: function(value) {
										// console.log(value);
										setAnimation( { animation: { ...props.attributes.animation, duration: value } } );
									},
								} )

							),
							// posts_loop
							el( wp.components.ToggleControl, {
								label: __('Loop', 'greyd_hub'),
								help: __("After the last slide, the first one is displayed again.", 'greyd_hub'),
								checked: _.get(props.attributes, 'animation.loop', defaults.animation.loop),
								onChange: function(value) { 
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, loop: value } } );
								},
							} ),
							// posts_autoplay
							el( wp.components.ToggleControl, {
								label: __('Autoplay', 'greyd_hub'),
								help: __("Slides are played automatically.", 'greyd_hub'),
								checked: _.get(props.attributes, 'animation.autoplay', defaults.animation.autoplay),
								onChange: function(value) { 
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, autoplay: value } } );
								},
							} ),
							// posts_interval
							(
								_.get(props.attributes, 'animation.autoplay', defaults.animation.autoplay) == true &&
								el( wp.components.RangeControl, {
									label: __("Interval in s", 'greyd_hub'),
									value: _.get(props.attributes, 'animation.interval', defaults.animation.interval),
									step: 0.1, min: 0, max: 30,
									onChange: function(value) { 
										// console.log(value);
										setAnimation( { animation: { ...props.attributes.animation, interval: value } } );
									},
								} )
							),
							// posts_height
							el( wp.components.SelectControl, {
								label: __( "Height of the slider", 'greyd_hub' ),
								value: _.get(props.attributes, 'animation.height', defaults.animation.height),
								options: [
									{ label: __("Adjust automatically", 'greyd_hub'), value: '' },
									{ label: __("As high as highest slide", 'greyd_hub'), value: 'auto' },
									{ label: __("Enter height", 'greyd_hub'), value: 'custom' },
								],
								onChange: function(value) { 
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, height: value } } );
								},
							} ),
							// posts_height_custom
							(_.get(props.attributes, 'animation.height', defaults.animation.height) == 'custom') ? 
								el( greyd.components.RangeUnitControl, {
									value: _.get(props.attributes, 'animation.height_custom', defaults.animation.height_custom),
									// units: [ '%', 'em' ],
									min: 10, max: 1000,
									onChange: function(value) { 
										// console.log(value);
										setAnimation( { animation: { ...props.attributes.animation, height_custom: value } } );
									},
								} ) : '',
							// posts_scroll_top
							el( wp.components.ToggleControl, {
								label: __("Scroll up", 'greyd_hub'),
								help: __("When the slide is changed, it automatically scrolls up to the beginning of the slider.", 'greyd_hub'),
								checked: _.get(props.attributes, 'animation.scroll_top', defaults.animation.scroll_top),
								onChange: function(value) { 
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, scroll_top: value } } );
								},
							} ),

							// url param
							el( wp.components.ToggleControl, {
								label: __("Set URL parameter", 'greyd_hub'),
								help: [
									__("Change the url parameter ", 'greyd_hub'),
									el( 'code', {}, "query-*-page"),
									__(" when the slide is changed. This enables navigating back to the last active slide before a page was left.", 'greyd_hub')
								],
								checked: _.get(props.attributes, 'animation.url_param', defaults.animation.url_param ),
								onChange: function(value) { 
									// console.log(value);
									setAnimation( { animation: { ...props.attributes.animation, url_param: value } } );
								},
							} ),
						] ),

						renderHTMLTagsSelect(),

						// filter & sort (deprecated)
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Filter & Sort", 'greyd_hub'), 
							initialOpen: false,
							holdsChange: values.filter.enable || values.sorting.enable,
						}, [
							// el( 'hr', { style: { margin: '10px 0' } } ),
							// el( 'p', {}, __("Deprecated", 'greyd_hub') ),
							// filter
							parent?.attributes?.query?.inherit || hideDeprecated('filter')
							? null
							: el( 'div', { className: 'is-flex-space-between' },
								el( wp.components.ToggleControl, {
									className: deprecatedHint ? 'deprecated' : '',
									label: __('Filter', 'greyd_hub'),
									checked: values.filter.enable,
									onChange: function(value) { 
										filterProps.setAttributes( { filter: { ...values.filter, enable: value } } );
									},
								} ),
								// deprecated hint
								makeDeprecationPopover('filter'),
								(values.filter.enable) ? el( wp.components.Button, {
									icon: 'admin-tools',
									style: { height: '20px' },
									onClick: function() { setMode("filter") }
								}) : el( 'div' ),
							),
							// sorting
							el( 'div', { className: 'is-flex-space-between' },
								!hideDeprecated('order') && el( wp.components.ToggleControl, {
									className: deprecatedHint ? 'deprecated' : '',
									label: __("Order", 'greyd_hub'),
									checked: values.sorting.enable,
									onChange: function(value) { 
										sortingProps.setAttributes( { sorting: { ...values.sorting, enable: value } } );
									},
								} ),
								// deprecated hint
								makeDeprecationPopover('order'),
								(values.sorting.enable) ? el( wp.components.Button, {
									icon: 'admin-tools',
									style: { height: '20px' },
									onClick: function() { setMode("sorting") }
								}) : el( 'div' ),
							),
						] ),
					] : [
						el( wp.components.Button, {
							icon: 'arrow-left-alt',
							// style: { height: '20px' },
							onClick: function() { setMode("") }
						}, __("General settings", 'greyd_hub') )
					]
				);
			};
			// filter inspector controls
			const filterControls = function() {
				return (
					mode == "filter" && values.filter.enable && !parent?.attributes?.query?.inherit ? [
						// deprecated hint
						makeDeprecationTip('filter'),
						// basics
						el( greyd.components.AdvancedPanelBody, { 
							title: __('Filter', 'greyd_hub'), 
							initialOpen: true, 
							// holdsChange: parts.background.type != "" 
						}, [
							// position
							el( 'span', {}, __('Position', 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.filter.position,
								options: [
									{ label: __("Top", 'greyd_hub'), value: 'top' },
									{ label: __("Bottom", 'greyd_hub'), value: 'bottom' },
								],
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, position: value } } ); 
								},
							} ),
							// align
							el( 'span', {}, __("Alignment", 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.filter.align,
								options: [
									{ label: __("Left", 'greyd_hub'), value: 'start' },
									{ label: __("Center", 'greyd_hub'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), value: 'end' },
								],
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, align: value } } ); 
								},
							} ),
						] ),
						// design
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Appearance", 'greyd_hub'), 
							initialOpen: false, 
						}, [
							// types
							el( greyd.components.ButtonGroupControl, {
								value: values.filter.inputStyle,
								options: [
									{ label: __('Primary', 'greyd_hub'), value: '' },
									{ label: __('Secondary', 'greyd_hub'), value: 'sec' },
								],
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, inputStyle: value } } ); 
								},
							} ),
							el( wp.components.ToggleControl, {
								label: __( "Overwrite design individually", 'greyd_hub' ),
								checked: values.filter.custom,
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, custom: value } } ); 
									// props.setAttributes( { styles: { ...props.attributes.styles, custom: value } } ); 
								},
							} ),
						] ),
						(values.filter.custom) ? el( greyd.components.CustomButtonStyles, {
							blockProps: filterProps,
							parentAttr: 'customStyles'
						} ) : '',
						// styles
						// greydStyles + responsive
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: filterProps,
							controls: [ {
								label: __("Width", 'greyd_hub'),
								attribute: "width",
								control: greyd.components.RangeUnitControl,
								max: 1000
							} ]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: filterProps,
							controls: [ {
								label: __("Outside", 'greyd_hub'),
								attribute: "margin",
								control: greyd.components.DimensionControl,
							} ]
						} ),
						// labels
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Designations", 'greyd_hub'), 
							initialOpen: false, 
						}, [
							el( wp.components.TextControl, {
								label: __("Select filter", 'greyd_hub'),
								value: values.filter.empty,
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, empty: value } } ); 
								},
							} ), 
							el( wp.components.ToggleControl, {
								label: __( "Show filter title", 'greyd_hub' ),
								checked: values.filter.showTaxTitle,
								onChange: function(value) { 
									filterProps.setAttributes( { filter: { ...values.filter, showTaxTitle: value } } ); 
								},
							} ),
						] ),
					] : []
				);
			};
			// sorting inspector controls
			const sortingControls = function() {
				return ( 
					mode == "sorting" && values.sorting.enable ? [
						// deprecated hint
						makeDeprecationTip('order'),
						// basics
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Order", 'greyd_hub'), 
							initialOpen: true, 
							// holdsChange: parts.background.type != "" 
						}, [
							// position
							el( 'span', {}, __('Position', 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.sorting.position,
								options: [
									{ label: __("Top", 'greyd_hub'), value: 'top' },
									{ label: __("Bottom", 'greyd_hub'), value: 'bottom' },
								],
								onChange: function(value) { 
									sortingProps.setAttributes( { sorting: { ...values.sorting, position: value } } ); 
								},
							} ),
							// align
							el( 'span', {}, __("Alignment", 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.sorting.align,
								options: [
									{ label: __("Left", 'greyd_hub'), value: 'start' },
									{ label: __("Center", 'greyd_hub'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), value: 'end' },
								],
								onChange: function(value) { 
									sortingProps.setAttributes( { sorting: { ...values.sorting, align: value } } ); 
								},
							} ),
						] ),
						// design
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Appearance", 'greyd_hub'), 
							initialOpen: false, 
						}, [
							// types
							el( greyd.components.ButtonGroupControl, {
								value: values.sorting.inputStyle,
								options: [
									{ label: __('Primary', 'greyd_hub'), value: '' },
									{ label: __('Secondary', 'greyd_hub'), value: 'sec' },
								],
								onChange: function(value) { 
									sortingProps.setAttributes( { sorting: { ...values.sorting, inputStyle: value } } ); 
								},
							} ),
							el( wp.components.ToggleControl, {
								label: __( "Overwrite design individually", 'greyd_hub' ),
								checked: values.sorting.custom,
								onChange: function(value) { 
									sortingProps.setAttributes( { sorting: { ...values.sorting, custom: value } } ); 
									// props.setAttributes( { styles: { ...props.attributes.styles, custom: value } } ); 
								},
							} ),
						] ),
						values.sorting.custom && el( greyd.components.CustomButtonStyles, {
							blockProps: sortingProps,
							parentAttr: 'customStyles'
						} ),
						// styles
						// greydStyles + responsive
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: sortingProps,
							controls: [ {
								label: __("Width", 'greyd_hub'),
								attribute: "width",
								control: greyd.components.RangeUnitControl,
								max: 1000
							} ]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: sortingProps,
							controls: [ {
								label: __("Outside", 'greyd_hub'),
								attribute: "margin",
								control: greyd.components.DimensionControl,
							} ]
						} ),
						// options labels
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Designations", 'greyd_hub'), 
							initialOpen: false, 
						}, Object.keys(sortingLabels).map((key) => {
							// console.log(key);
							return el( wp.components.TextControl, {
								label: sortingLabels[key],
								value: _.has(values.sorting.options, key) ? values.sorting.options[key] : '',
								onChange: (value) => sortingProps.setAttributes({
									sorting: {
										...values.sorting,
										options: {
											...values.sorting.options,
											[key]: value
										}
									}
								} ),
							} );
						}) ),
					] : []
				);
			};
			// numbers inspector controls
			const paginationControls = function() {
				return (
					mode == "numbers" && values.pagination.enable && !liveSearchEnabled ? [
						// basics
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Classic pagination", 'greyd_hub'), 
							initialOpen: true, 
							// holdsChange: parts.background.type != "" 
						}, [
							// position
							el( 'span', {}, __('Position', 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.pagination.position,
								options: [
									{ label: __("Top", 'greyd_hub'), value: 'top' },
									{ label: __("Bottom", 'greyd_hub'), value: 'bottom' },
								],
								onChange: function(value) { 
									paginationProps.setAttributes( { pagination: { ...values.pagination, position: value } } ); 
								},
							} ),
							// align
							el( 'span', {}, __("Alignment", 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.pagination.greydStyles.justifyContent,
								options: [
									{ label: __("Left", 'greyd_hub'), value: 'start' },
									{ label: __("Center", 'greyd_hub'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), value: 'end' },
									{ label: __("Spreaded", 'greyd_hub'), value: 'space-between' },
								],
								onChange: function(value) { 
									paginationProps.setAttributes( { pagination: {
										...values.pagination,
										greydStyles: {
											...values.pagination.greydStyles,
											justifyContent: value
										}
									} } ); 
								},
							} ),
							// overlap
							el( wp.components.ToggleControl, {
								label: __("Overlap content", 'greyd_hub'),
								checked: values.pagination.overlap,
								onChange: function(value) { 
									paginationProps.setAttributes( { pagination: { ...values.pagination, overlap: value } } ); 
								},
							} ),
						] ),
						// design numbers
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Page numbers", 'greyd_hub'), 
							initialOpen: true, 
						}, [
							// type
							el( 'span', {}, __("Appearance", 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.pagination.type,
								options: [
									{ label: __('None', 'greyd_hub'), value: '' },
									{ label: __("Digits", 'greyd_hub'), value: 'text' },
									{ label: __('Icon', 'greyd_hub'), value: 'icon' },
									{ label: __("Upload image", 'greyd_hub'), value: 'image' },
								],
								onChange: function(value) { 
									paginationProps.setAttributes( { pagination: { ...values.pagination, type: value } } ); 
								},
							} ),
							(values.pagination.type == 'text') ? [
								// text
								el( wp.components.SelectControl, {
									value: values.pagination.text_type,
									options: [
										{ label: __('1 2 3 ...', 'greyd_hub'), value: '' },
										{ label: __('1. 2. 3. ...', 'greyd_hub'), value: '1.' },
										{ label: __('01 02 03 ...', 'greyd_hub'), value: '01' },
										{ label: __('01. 02. 03. ...', 'greyd_hub'), value: '01.' },
										{ label: __('A B C ...', 'greyd_hub'), value: 'A' },
										{ label: __('a b c ...', 'greyd_hub'), value: 'a' },
									],
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, text_type: value } } ); 
									},
								} )
							] : '',
							(values.pagination.type == 'icon') ? [
								// icon
								el( wp.components.SelectControl, {
									value: values.pagination.icon_type,
									options: [
										{ label: __('Icons', 'greyd_hub'), value: 'icon' },
										{ label: __("Circles", 'greyd_hub'), value: 'dots' },
										{ label: __('Squares', 'greyd_hub'), value: 'blocks' },
									],
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, icon_type: value } } ); 
									},
								} ),
								(values.pagination.icon_type == 'icon') ? [
									el( greyd.components.IconPicker, {
										label: __("Normal pagination", 'greyd_hub'),
										value: values.pagination.icon_normal,
										icons: greyd.data.icons,
										onChange: function(value) { 
											paginationProps.setAttributes( { pagination: { ...values.pagination, icon_normal: value } } ); 
										},
									} ),
									el( greyd.components.IconPicker, {
										label: __("Pagination active", 'greyd_hub'),
										value: values.pagination.icon_active,
										icons: greyd.data.icons,
										onChange: function(value) { 
											paginationProps.setAttributes( { pagination: { ...values.pagination, icon_active: value } } ); 
										},
									} ),
								] : '',
							] : '',
							(values.pagination.type == 'image') ? [
								// image
								el( wp.components.BaseControl, { }, [
									el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) }, [
										// normal image
										el( 'div', {}, __("Image normal", 'greyd_hub') ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.pagination.img_normal,
											onSelect: function(value) { paginationProps.setAttributes( { pagination: { ...values.pagination, img_normal: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.pagination.img_normal == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.pagination.img_normal == -1 ? 
													__( "Select image", 'greyd_hub' ) : 
													el( 'img', { src: getImageUrl(values.pagination.img_normal) } ) 
												)
											},
										} ),
										values.pagination.img_normal !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { paginationProps.setAttributes( { pagination: { ...values.pagination, img_normal: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
										// active image
										el( 'div', { style: { marginTop: '8px' } }, __("Image active", 'greyd_hub') ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.pagination.img_active,
											onSelect: function(value) { paginationProps.setAttributes( { pagination: { ...values.pagination, img_active: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.pagination.img_active == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.pagination.img_active == -1 ? 
													__( "Select image", 'greyd_hub' ) :
													el( 'img', { src: getImageUrl(values.pagination.img_active) } ) 
												)
											},
										} ),
										values.pagination.img_active !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { paginationProps.setAttributes( { pagination: { ...values.pagination, img_active: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
									] ),
								] ),
							] : '',
							// shorten
							(values.pagination.type != '' && !parent?.attributes?.query?.inherit) ? [
								el( wp.components.ToggleControl, {
									label: __("Shorten pagination", 'greyd_hub'),
									// help: __("When there are a lot of pages.", 'greyd_hub'),
									checked: values.pagination.maxnum > -1,
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, maxnum: value ? 1 : -1 } } ); 
									},
								} ),
								values.pagination.maxnum > -1 &&
									el( wp.components.RangeControl, {
										label: __("Visible numbers", 'greyd_hub'),
										help: __("Number of pages visible left and right of the active page. The first and the last page number is always visible, remaining numbers will be shortened to '...'", 'greyd_hub'),
										value: values.pagination.maxnum,
										step: 1, min: 0, max: 15,
										onChange: function(value) { 
											paginationProps.setAttributes( { pagination: { ...values.pagination, maxnum: value } } ); 
										},
									} )
							] : '',
						] ),
						// design arrows
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Arrows", 'greyd_hub'), 
							initialOpen: true, 
						}, [
							// type
							el( 'span', {}, __("Type", 'greyd_hub') ),
							el( greyd.components.ButtonGroupControl, {
								value: values.pagination.arrows_type,
								options: [
									{ label: __('None', 'greyd_hub'), value: '' },
									{ label: __('Icon', 'greyd_hub'), value: 'icon' },
									{ label: __("Upload image", 'greyd_hub'), value: 'image' },
								],
								onChange: function(value) { 
									paginationProps.setAttributes( { pagination: { ...values.pagination, arrows_type: value } } ); 
								},
							} ),
							(values.pagination.arrows_type == 'icon') ? [
								// icon
								el( greyd.components.IconPicker, {
									label: __("Previous icon", 'greyd_hub'),
									value: values.pagination.icon_previous,
									icons: greyd.data.icons,
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, icon_previous: value } } ); 
									},
								} ),
								el( greyd.components.IconPicker, {
									label: __("Next icon", 'greyd_hub'),
									value: values.pagination.icon_next,
									icons: greyd.data.icons,
									onChange: function(value) { 
										paginationProps.setAttributes( { pagination: { ...values.pagination, icon_next: value } } ); 
									},
								} ),
							] : '',
							(values.pagination.arrows_type == 'image') ? [
								// image
								el( wp.components.BaseControl, { }, [
									el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) }, [
										// previous image
										el( 'div', {}, __("Previous image", 'greyd_hub') ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.pagination.img_previous,
											onSelect: function(value) { paginationProps.setAttributes( { pagination: { ...values.pagination, img_previous: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.pagination.img_previous == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.pagination.img_previous == -1 ? 
													__( "Select image", 'greyd_hub' ) : 
													el( 'img', { src: getImageUrl(values.pagination.img_previous) } ) 
												)
											},
										} ),
										values.pagination.img_previous !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { paginationProps.setAttributes( { pagination: { ...values.pagination, img_previous: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
										// next image
										el( 'div', { style: { marginTop: '8px' } }, __("Next image", 'greyd_hub') ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.pagination.img_next,
											onSelect: function(value) { paginationProps.setAttributes( { pagination: { ...values.pagination, img_next: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.pagination.img_next == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.pagination.img_next == -1 ? 
													__( "Select image", 'greyd_hub' ) :
													el( 'img', { src: getImageUrl(values.pagination.img_next) } ) 
												)
											},
										} ),
										values.pagination.img_next !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { paginationProps.setAttributes( { pagination: { ...values.pagination, img_next: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
									] ),
								] ),
							] : '',
						] ),
						// styles
						// color + hover + active
						el( greyd.components.StylingControlPanel, {
							// className: ((values.pagination.type != '' && values.pagination.type != 'image') || values.pagination.arrows_type == 'icon') ? '' : 'hidden',
							title: __("Colors", 'greyd_hub'),
							initialOpen: false,
							supportsHover: true,
							supportsActive: true,
							holdsColors: [
								{ 
									color: (_.has(paginationProps.attributes, 'greydStyles.color')) ? paginationProps.attributes.greydStyles.color : '', 
									title: __("Symbol color", 'greyd_hub') 
								}
							],
							blockProps: paginationProps,
							controls: [ {
								className: 'single'+((values.pagination.type != '' && values.pagination.type != 'image') || values.pagination.arrows_type == 'icon' ? '' : ' hidden'),
								label: __("Symbol color", 'greyd_hub'),
								attribute: "color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							}, {
								label: __("Opacity", 'greyd_hub'),
								attribute: "opacity",
								control: greyd.components.RangeUnitControl,
								units: ['%'],
							} ]
						} ),
						// greydStyles + responsive
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: paginationProps,
							controls: [ {
								label: __("Symbol size", 'greyd_hub'),
								attribute: "fontSize",
								control: greyd.components.RangeUnitControl,
								units: [ 'px', 'em' ],
								max: {
									px: 60,
									em: 3
								}
							} ]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: paginationProps,
							controls: [ {
								label: __("Inside", 'greyd_hub'),
								attribute: "padding",
								control: greyd.components.DimensionControl,
								units: [ 'px', 'em' ],
							}, {
								label: __("In between", 'greyd_hub'),
								attribute: "gutter",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', 'em' ],
								max: {
									px: 60,
									em: 3
								}
							} ]
						} ),
					] : []
				);
			};
			// arrows inspector controls
			const arrowControls = function() {
				return (
					mode == "arrows" && values.arrows.enable && !liveSearchEnabled ? [
						// basics
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Side arrows", 'greyd_hub'), 
							initialOpen: true, 
						}, [
							// position
							el( greyd.components.AdvancedLabel, { label: __("Alignment", 'greyd_hub') } ),
							el( greyd.components.ButtonGroupControl, {
								value: values.arrows.greydStyles.alignItems,
								options: [
									{ label: __("Top", 'greyd_hub'), value: 'start' },
									{ label: __("Center", 'greyd_hub'), value: 'center' },
									{ label: __("Bottom", 'greyd_hub'), value: 'end' },
								],
								onChange: function(value) { 
									arrowProps.setAttributes( { arrows: {
										...values.arrows,
										greydStyles: {
											...values.arrows.greydStyles,
											alignItems: value
										}
									} } ); 
								},
							} ),
							// overlap
							el( wp.components.ToggleControl, {
								label: __("Overlap content", 'greyd_hub'),
								checked: values.arrows.overlap,
								onChange: function(value) { 
									arrowProps.setAttributes( { arrows: { ...values.arrows, overlap: value } } ); 
								},
							} ),
						] ),
						// design
						el( greyd.components.AdvancedPanelBody, { 
							title: __("Appearance", 'greyd_hub'), 
							initialOpen: true, 
						}, [
							// type
							el( greyd.components.AdvancedLabel, { label: __("Appearance", 'greyd_hub') } ),
							el( greyd.components.ButtonGroupControl, {
								value: values.arrows.type,
								options: [
									{ label: __('Icon', 'greyd_hub'), value: 'icon' },
									{ label: __("Upload image", 'greyd_hub'), value: 'image' },
								],
								onChange: function(value) { 
									console.log(value);
									arrowProps.setAttributes( { arrows: { ...values.arrows, type: value } } ); 
								},
							} ),
							(values.arrows.type == 'icon') ? [
								// icon
								el( greyd.components.IconPicker, {
									label: __("Previous icon", 'greyd_hub'),
									value: values.arrows.icon_previous,
									icons: greyd.data.icons,
									onChange: function(value) { 
										arrowProps.setAttributes( { arrows: { ...values.arrows, icon_previous: value } } ); 
									},
								} ),
								el( greyd.components.IconPicker, {
									label: __("Next icon", 'greyd_hub'),
									value: values.arrows.icon_next,
									icons: greyd.data.icons,
									onChange: function(value) { 
										arrowProps.setAttributes( { arrows: { ...values.arrows, icon_next: value } } ); 
									},
								} ),
							] : '',
							(values.arrows.type == 'image') ? [
								// image
								el( wp.components.BaseControl, { }, [
									el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) }, [
										// previous image
										el( greyd.components.AdvancedLabel, { label: __("Previous image", 'greyd_hub') } ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.arrows.img_previous,
											onSelect: function(value) { arrowProps.setAttributes( { arrows: { ...values.arrows, img_previous: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.arrows.img_previous == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.arrows.img_previous == -1 ? 
													__( "Select image", 'greyd_hub' ) : 
													el( 'img', { src: getImageUrl(values.arrows.img_previous) } ) 
												)
											},
										} ),
										values.arrows.img_previous !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { arrowProps.setAttributes( { arrows: { ...values.arrows, img_previous: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
										// next image
										el( 'div', { style: { marginTop: '8px' } }, __("Next image", 'greyd_hub') ),
										el( wp.blockEditor.MediaUpload, {
											allowedTypes: 'image/*',
											value: values.arrows.img_next,
											onSelect: function(value) { arrowProps.setAttributes( { arrows: { ...values.arrows, img_next: value.id } } ); },
											render: function(obj) {
												return el( wp.components.Button, { 
													className: values.arrows.img_next == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, values.arrows.img_next == -1 ? 
													__( "Select image", 'greyd_hub' ) :
													el( 'img', { src: getImageUrl(values.arrows.img_next) } ) 
												)
											},
										} ),
										values.arrows.img_next !== -1 ? el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { arrowProps.setAttributes( { arrows: { ...values.arrows, img_next: -1 } } ) },
										}, __( "Remove image", 'greyd_hub' ) ) : "",
									] ),
								] ),
							] : '',
						] ),
						// styles
						// color + hover
						el( greyd.components.StylingControlPanel, {
							// className: (values.arrows.type != 'icon') ? 'hidden' : '',
							title: __("Colors", 'greyd_hub'),
							initialOpen: false,
							supportsHover: true,
							holdsColors: [
								{ 
									color: (_.has(arrowProps.attributes, 'greydStyles.color')) ? arrowProps.attributes.greydStyles.color : '', 
									title: __("Icon color", 'greyd_hub') 
								}
							],
							blockProps: arrowProps,
							controls: [ {
								className: 'single'+(values.arrows.type != 'icon' ? ' hidden' : ''),
								label: __("Icon color", 'greyd_hub'),
								attribute: "color",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							}, {
								label: __("Background", 'greyd_hub'),
								attribute: "backgroundColor",
								control: greyd.components.ColorGradientPopupControl,
								mode: 'color'
							}, {
								label: __("Opacity", 'greyd_hub'),
								attribute: "opacity",
								control: greyd.components.RangeUnitControl,
								units: ['%'],
							} ]
						} ),
						// greydStyles + responsive
						el( greyd.components.StylingControlPanel, {
							title: __("Size", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: arrowProps,
							controls: [ {
								label: __("Width", 'greyd_hub'),
								attribute: "fontSize",
								control: greyd.components.RangeUnitControl,
								units: [ 'px', 'em' ],
								max: {
									px: 100,
									em: 5
								}
							} ]
						} ),
						el( greyd.components.StylingControlPanel, {
							title: __("Spaces", 'greyd_hub'),
							initialOpen: false,
							supportsResponsive: true,
							blockProps: arrowProps,
							controls: [ {
								label: __("Inside", 'greyd_hub'),
								attribute: "padding",
								control: greyd.components.DimensionControl,
							} ]
						} ),
					] : []
				);
			};
			// load more button inspector controls
			const loaderControls = function() {
				return (
					mode == "loader" ? [
						// basics
						el( greyd.components.AdvancedPanelBody, { 
							title: __("\"Load more\" button", 'greyd_hub'), 
							initialOpen: true,
						}, [
							el( wp.components.SelectControl, {
								label: __('Design', 'greyd_hub'),
								value: values.loader.style,
								onChange: (value) => loaderProps.setAttributes( { style: value } ),
								options: [
									{ value: '', label: __("Primary button", 'greyd_hub') },
									{ value: 'button sec', label: __("Secondary button", 'greyd_hub') },
									{ value: 'button trd', label: __("Alternate button", 'greyd_hub') },
									{ value: 'link', label: __("Primary link", 'greyd_hub') },
									{ value: 'link sec', label: __("Secondary link", 'greyd_hub') },
								],
							} ),
							values.loader.style.indexOf('link') == -1 ? el( greyd.components.ButtonGroupControl, {
								label: __("Size", 'greyd_hub'),
								value: values.loader.size,
								onChange: (value) => loaderProps.setAttributes( { size: value } ),
								options: [
									{ label: __("Small", 'greyd_hub'), value: 'small' },
									{ label: __("Default", 'greyd_hub'), value: '' },
									{ label: __("Big", 'greyd_hub'), value: 'big' },
								],
							} ) : null
						] ),
						el( greyd.components.StylingControlPanel, {
							title: __("Sizes & Spaces", 'greyd_hub'),
							initialOpen: true,
							supportsResponsive: true,
							blockProps: loaderProps,
							controls: [
								{
									label: __("Alignment", 'greyd_hub'),
									attribute: "--align",
									control: greyd.components.ButtonGroupControl,
									options: [
										{ icon: greyd.tools.getCoreIcon('alignLeft'), value: 'left' },
										{ icon: greyd.tools.getCoreIcon('alignCenter'), value: '' },
										{ icon: greyd.tools.getCoreIcon('alignRight'), value: 'right' },
									],
								},
								{
									label: __("Space", 'greyd_hub'),
									attribute: "margin",
									control: greyd.components.DimensionControl,
									min: -300,
									max: 300,
								},
							]
						} ),
					] : []
				);
			};

			//  all inspector controls
			const makeInspectorControls = function() {

				return el( wp.blockEditor.InspectorControls, { }, [
					// general
					...mainControls(),
					// filter
					...filterControls(),
					// sorting
					...sortingControls(),
					// numbers
					...paginationControls(),
					// arrows
					...arrowControls(),
					// load more button
					...loaderControls(),
				] );

			};


			/**
			 * (2) preview
			 */

			// render responsive columns and gap media styles
			const makeResponsivePreviewStyles = function() {

				if ( !_.has(props.attributes, 'layout') ) return null;

				const layout = _.get(props.attributes, 'layout.type', '');
				var style = "";
				var wrapper = '#block-'+props.clientId+'.wp-block-post-template';
				var selector = wrapper+'.is-flex-container > li';

				// gap
				if ( props.attributes.layout?.gap ) {
					var gap = wp.blockEditor.getSpacingPresetCssVar(props.attributes.layout.gap);
					if ( _.isEmpty(gap) || gap == 0 ) gap = "0px";
					style += wrapper + " { --query-block-gap: "+gap+"; } ";
				}
				
				// make responsive styles
				if ( props.attributes.layout?.responsive ) {
					[ 'lg', 'md', 'sm' ].forEach( function(size) {

						let css = "";

						if ( layout == 'grid' ) {
							// responsive columns
							if ( _.has(props.attributes.layout.responsive, size+'.columnCount') ) {
								css += selector + " { width: calc(100% / "+ _.get(props.attributes.layout.responsive, size+'.columnCount', "1") +"); } ";
							}
							else if (size == 'sm') {
								css += selector + " { width: 100%; } ";
							}
							// responsive items per page preview
							if ( _.has(props.attributes.layout.responsive, size+'.items') ) {
								css += selector + ":nth-child( n + "+ ( parseInt(_.get( props.attributes.layout.responsive, size+'.items' )) + 2 ) +" ) { display: none !important; } ";
							}
						}
						// responsive gap
						if ( _.has(props.attributes.layout.responsive, size+'.gap') ) {
							var gap = wp.blockEditor.getSpacingPresetCssVar(props.attributes.layout.responsive[size].gap);
							if ( _.isEmpty(gap) || gap == 0 ) gap = "0px";
							css += wrapper + " { --query-block-gap: "+gap+"; } ";
						}

						if ( css != "" ) {
							style += "@media (max-width: "+( _.get(greyd.data.grid, size) - 0.02 )+"px) { "+css+" } ";
						}
					} );
				}

				// console.log( props.attributes.layout, style )

				// render styles
				if ( style != "" ) {
					return el( 'style', { className: 'greyd_styles' }, style );
				}
				return null;
			};

			// render filter preview
			const filterPreview = function(position) {
				if ( !values.filter.enable || values.filter.position !== position || parent?.attributes?.query?.inherit ) return null;

				var option = values.filter.empty != '' ? values.filter.empty : __("Select filter", 'greyd_hub');
				if (_.has(parent, 'attributes.query.taxQuery') && !_.isEmpty(parent.attributes.query.taxQuery)) {
					// get first selected filter as dummy option to display
					// console.log(parent.attributes.query);
					var pt = parent.attributes.query.postType;
					var taxes = Object.keys(parent.attributes.query.taxQuery);
					var terms = parent.attributes.query.taxQuery[taxes[0]];
					greyd.data.all_taxes[pt].forEach(function(tax) {
						if (tax.slug == taxes[0]) {
							tax.values.forEach(function(term) {
								if (term.id == terms[0]) {
									if (values.filter.showTaxTitle) option = tax.title+': '+term.title;
									else option = term.title;
								}
							})
						}
					});
				}

				return el( 'div', {
					className: values.filter.greydClass+' '+values.filter.position+' filter'+(mode == "filter" ? ' active' : ''), 
					style: { display: "flex", width: "100%", justifyContent: values.filter.align },
					onClick: () => {
						// console.log("select filter");
						setMode("filter");
						wp.data.dispatch('core/block-editor').selectBlock(props.clientId);
					}
				}, [
					el( greyd.components.RenderPreviewStyles, {
						selector: values.filter.greydClass,
						styles: {
							"": values.filter.greydStyles
						}
					} ),
					el( greyd.components.RenderPreviewStyles, {
						selector: values.filter.greydClass+" select",
						styles: {
							"": values.filter.greydStyles,
							" ": (values.filter.custom) ? values.filter.customStyles : {}
						},
						important: true
					} ),
					el( 'select', {
						className: values.filter.inputStyle == 'sec' ? 'is-style-sec' : '',
						style: { 
							// paddingRight: "30px", 
							width: values.filter.width, 
							margin: values.filter.margin 
						}
					}, [
						el( 'option', { value: '' }, option ),
					] )
				] );
			};
			// render sorting preview
			const sortingPreview = function(position) {
				if ( !values.sorting.enable || values.sorting.position !== position ) return null;

				return el( 'div', {
					className: values.sorting.greydClass+' '+values.sorting.position+' sorting'+(mode == "sorting" ? ' active' : ''), 
					style: { display: "flex", width: "100%", justifyContent: values.sorting.align },
					onClick: () => {
						// console.log("select sorting");
						setMode("sorting");
						wp.data.dispatch('core/block-editor').selectBlock(props.clientId);
					}
				}, [
					el( greyd.components.RenderPreviewStyles, {
						selector: values.sorting.greydClass,
						styles: {
							"": values.sorting.greydStyles
						}
					} ),
					el( greyd.components.RenderPreviewStyles, {
						selector: values.sorting.greydClass+" select",
						styles: {
							"": values.sorting.greydStyles,
							" ": (values.sorting.custom) ? values.sorting.customStyles : {}
						},
						important: true
					} ),
					el( 'select', {
						className: values.sorting.inputStyle == 'sec' ? 'is-style-sec' : '',
						style: { 
							// paddingRight: "30px", 
							width: values.sorting.width, 
							margin: values.sorting.margin 
						}
					}, [
						Object.keys(sortingLabels).map( key => {
							// console.log(key);
							// console.log(_.some(values.sorting.options[key], isEmpty));
							return el( 'option', { value: key }, _.some(values.sorting.options[key], _.isEmpty) ? values.sorting.options[key] : sortingLabels[key] );
						} )
					] )
				] );
			};
			// render pagination preview
			const paginationPreview = function(position) {

				if ( liveSearchEnabled || !values.pagination.enable || values.pagination.position !== position ) return null;

				const makeArrow = (direction) => {
					if ( values.pagination.arrows_type === 'icon' ) {
						return el( 'a', { className: values.pagination['icon_'+direction] } );
					}
					else if ( values.pagination.arrows_type === 'image' ) {
						return el( 'a', {}, el( 'img', {
							src: getImageUrl(values.pagination['img_'+direction])
						} ) );
					}
					return null;
				}

				const makeNumbers = () => {

					if (values.pagination.type == '') return null;

					// // get number of pages
					// var pages = 3;
					// if (_.has(parent, 'attributes.query')) {
					// 	// console.log(parent.attributes.query);
					// 	if (block_posts && posts_max > 0) {
					// 		// pages = Math.ceil(posts_max/block_posts.length);
					// 		// console.log( pages, posts_max, block_posts.length )
					// 		if (parent.attributes.query.pages != 0) {
					// 			pages = parseInt(parent.attributes.query.pages);
					// 		}
					// 	}
					// }

					var numbers = [];
					const getMaxnumClass = ( i ) => {
						var maxnum = values.pagination.maxnum ?? -1;
						if ( !parent?.attributes?.query?.inherit && maxnum > -1 && i < pages ) {
							if ( i-1 === maxnum ) return 'dots-after';
							else if ( i-1 > maxnum ) return 'hidden';
						}
						return '';
					};
					if (values.pagination.type == 'text') {
						const makeNumberStyle = function(number, numbers_style) {
							if (numbers_style === 'A' || numbers_style === 'a') {
								var result = '';
								while (number > 0) {
									var t = (number - 1) % 26;
									result = String.fromCharCode(65 + t) + result;
									number = (number - t)/26 | 0;
								}
								if (numbers_style == 'a') result = result.toLowerCase();
							}
							else {
								var result = number.toString();
								if ((numbers_style === '01' || numbers_style === '01.') && result < 10) result = '0'+result;
								if (numbers_style === '1.' || numbers_style === '01.') result += '.';
							}
							return result;
						}
						for (var i=1; i<=pages; i++) {
							var classes = i==1 ? [ 'active' ] : [];
							classes.push( getMaxnumClass(i) );
							numbers[i-1] = el( "a", { className: classes.join(' ') }, makeNumberStyle(i, values.pagination.text_type) );
						}
					}
					else if (values.pagination.type == 'icon') {
						var number = "";
						if (values.pagination.icon_type == 'dots') number = "●"; 
						else if (values.pagination.icon_type == 'blocks') number = "■"; 
						for (var i=1; i<=pages; i++) {
							var classes = i==1 ? [ 'active' ] : [];
							if (values.pagination.icon_type == 'icon') {
								classes.push(i==1 ? values.pagination.icon_active : values.pagination.icon_normal);
							}
							classes.push( getMaxnumClass(i) );
							numbers[i-1] = el( "a", { className: classes.join(' ') }, number );
						}
					}
					else if (values.pagination.type == 'image') {
						const img = { active: getImageUrl(values.pagination.img_active), normal: getImageUrl(values.pagination.img_normal) }
						for (var i=1; i<=pages; i++) {
							var classes = i==1 ? [ 'active' ] : [];
							classes.push( getMaxnumClass(i) );
							numbers[i-1] = el( "a", { className: classes.join(' ') }, el( 'img', { src: i==1 ? img.active : img.normal } ) );
						}
					}

					return el( 'span', {}, numbers );
				}

				return el( 'div', {
					className: values.pagination.greydClass+' '+'pgn numbers '+position+(mode == "numbers" ? ' active' : '')+(values.pagination.overlap ? ' overlap' : ''),
					onClick: function() {
						// console.log("select numbers");
						setMode("numbers");
						wp.data.dispatch('core/block-editor').selectBlock(props.clientId);
					}
				}, [
					// styles
					el( greyd.components.RenderPreviewStyles, {
						selector: values.pagination.greydClass+".pgn.numbers",
						styles: { 
							"": {
								..._.omit(values.pagination.greydStyles, 'color', 'opacity', 'active', 'hover', 'gutter'),
								'--pgn-numbers-gutter': _.get(values.pagination.greydStyles, 'gutter')
							},
						}
					} ),
					el( greyd.components.RenderPreviewStyles, {
						selector: values.pagination.greydClass+".pgn.numbers a",
						styles: { 
							"": _.pick(values.pagination.greydStyles, 'color', 'opacity'),
							".active": _.pick(values.pagination.greydStyles.active, 'color', 'opacity'),
							":hover, ._hover": _.pick(values.pagination.greydStyles, 'hover')
						}
					} ),
					makeArrow('previous'),
					makeNumbers(),
					makeArrow('next')
				] );
			};
			// render arrow preview
			const arrowPreview = function(position) {

				if ( liveSearchEnabled || !values.arrows.enable ) return null;

				const direction = position === 'left' ? 'previous' : 'next';
				var linkStyles = {};
				var greydStyles = { ...values.arrows.greydStyles };

				/**
				 * Fix color inhertiation of custom pagination arrow colors.
				 * 
				 * @since 1.3.3
				 */
				if ( _.has(greydStyles, 'color') ) {
					linkStyles.color = greydStyles.color;
					greydStyles.color = null;
				}
				if ( _.has(greydStyles, 'backgroundColor') ) {
					linkStyles.backgroundColor = greydStyles.backgroundColor;
					greydStyles.backgroundColor = null;
				}
				if ( _.has(greydStyles, 'hover') ) {
					linkStyles.hover = greydStyles.hover;
					greydStyles.hover = null;
				}

				return el( 'div', { 
					className: values.arrows.greydClass+' pgn arrows '+position+(mode == "arrows" ? ' active' : '')+(values.arrows.overlap ? ' overlap' : ''),
					onClick: () => {
						// console.log("select arrows");
						setMode("arrows");
						wp.data.dispatch('core/block-editor').selectBlock(props.clientId);
					}
				}, [
					el( greyd.components.RenderPreviewStyles, {
						selector: values.arrows.greydClass+".pgn.arrows",
						styles: { "": greydStyles }
					} ),
					el( greyd.components.RenderPreviewStyles, {
						selector: values.arrows.greydClass+".pgn.arrows > a",
						styles: { "": linkStyles }
					} ),
					(
						values.arrows.type == 'icon' ? 
						el( "a", { className: values.arrows[ 'icon_'+direction ] } ) :
						el( 'img', { src: getImageUrl(values.arrows[ 'img_'+direction ] ) } )
					)
				] );
			};
			// live search load more button preview
			const loaderPreview = function() {
				if ( !liveSearchEnabled ) return null;

				var buttonClass = 'button';
				if ( !_.isEmpty(values.loader.style) ) {
					buttonClass = values.loader.style;
				}
				if ( !_.isEmpty(values.loader.size) ) {
					buttonClass += ' ' + values.loader.size;
				}
				buttonClass = 'load_more ' + buttonClass;

				return el( 'div', {
					className: 'load_more_wrapper ' + values.loader.greydClass,
					onClick: () => {
						setMode("loader");
						wp.data.dispatch('core/block-editor').selectBlock(props.clientId);
					}
				}, [
					el( greyd.components.RenderPreviewStyles, {
						selector: values.loader.greydClass,
						styles: { "": values.loader.greydStyles }
					} ),
					el( 'div', {
						className: buttonClass
					}, __("Load more", "greyd_hub") )
				] );
			};


			/**
			 * (3) posts and render
			 */

			// get all posts infos
			var { total, pages } = wp.data.useSelect(select => {
				// get current parent state
				parent = getParent(props);
				if (!_.has(parent, 'attributes.query')) return false;
				// console.log(parent.attributes.query);
				// console.log(props);

				// original query vars
				var parentQuery = { ...parent.attributes.query };
				var postType = (parentQuery.postType) ? parentQuery.postType : 'post';
				// console.log(parentQuery);
				var query = {
					offset: parentQuery.offset,
					order: parentQuery.order,
					orderby: parentQuery.orderBy
				};
				// if (parentQuery.taxQuery) query.taxQuery = parentQuery.taxQuery;
				if (parentQuery.taxQuery) {
					query = {
						...query,
						...parentQuery.taxQuery
					};
					if ( query.category ) {
						query.categories = query.category;
						delete query.category;
					}
					if ( query.post_tag ) {
						query.tags = query.post_tag;
						delete query.post_tag;
					}
				}
				if (parentQuery.perPage) query.per_page = parentQuery.perPage;
				if (parentQuery.author) query.author = parentQuery.author;
				if (parentQuery.search) query.search = parentQuery.search;
				if (parentQuery.exclude?.length) query.exclude = parentQuery.exclude;
				if (parentQuery.parents?.length) query.parent = parentQuery.parents;
				if (parentQuery.sticky === 'only') query.sticky = true;
				// console.log("fetch posts", postType, query);

				// get infos for loop
				var perPage = parentQuery.perPage;
				var pages = 0;
				var total = 0;
				if ( false && typeof select("core").getEntityRecordsTotalPages === 'function' ) {
					// since wp 6.8.2 these function always return null
					pages = select("core").getEntityRecordsTotalPages('postType', postType, query);
					total = select("core").getEntityRecordsTotalItems('postType', postType, query);
				}
				else {
					// let posts = select("core").getEntityRecords('postType', postType, parentQuery);
					let posts = select("core").getEntityRecords('postType', postType, {
						...query,
						per_page: -1
					});
					if ( posts ) {
						total = posts.length;
						pages = Math.ceil(total / perPage);
						// console.log( total, pages, perPage );
					}
				}
				if ( parentQuery.pages != 0 && parentQuery.pages < pages ) {
					pages = parentQuery.pages;
					total = pages * perPage;
				}

				return {
					total: total,
					pages: pages,
				}
			}, [ props, parent ]);


			// render block with advanced featues
			return el( wp.element.Fragment, { }, [
				// responsive styles
				makeResponsivePreviewStyles(),
				// render new preview
				el( 'div', { 
					className: "greyd-posts-slider"+(props.isSelected ? " hover" : ''),
				}, [
					filterPreview( 'top' ),
					sortingPreview( 'top' ),
					paginationPreview('top'),
					arrowPreview( 'left' ),
					el( BlockEdit, props ),
					arrowPreview( 'right' ),
					paginationPreview('bottom'),
					sortingPreview( 'bottom' ),
					filterPreview( 'bottom' ),
					loaderPreview(),
				] ),
				// sidebar
				makeInspectorControls(),
			] );

		}

	};

	// helper component used in post-slider grid settings
	const __RangeNumberControl = class extends wp.element.Component {
		render() {
			const {
				value,
				label = "",
				min = 0,
				max = 12
			} = this.props;
			return el( wp.components.BaseControl, {}, [
				label && el( greyd.components.AdvancedLabel, {
					label: label
				} ),
				el( wp.components.Flex, {
					gap: '16px'
				}, [
					el( wp.components.FlexItem, {
						style: { flex: '1' }
					}, [
						el( wp.components.__experimentalNumberControl, {
							size: '__unstable-large',
							value: value,
							min: min, step: 1,
							onChange: ( newValue ) => this.props.onChange( parseInt(newValue) ),
						} )
					] ),
					el( wp.components.FlexItem, {
						style: { flex: '1' }
					}, [
						el( wp.components.RangeControl, {
							withInputField: false,
							value: value,
							min: min, max: max, step: 1,
							onChange: ( newValue ) => this.props.onChange( parseInt(newValue) ),
						} )
					] )
				] )
			] );
		}
	};

	wp.hooks.addFilter( 
		'editor.BlockEdit', 
		'greyd/hook/post-template/edit', 
		editBlockHook 
	);

} )( window.wp );