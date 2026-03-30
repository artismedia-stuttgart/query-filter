/**
 * Query Block frontend features:
 * Responsive columns, sorting & filtering.
 * 
 * @since 1.5.0
 */
document.addEventListener("DOMContentLoaded", function() {

	var mediaQueries = { trigger: {} };
	function initQueries() {

		// get live-queries
		var queryBlocks = document.querySelectorAll('.wp-block-query .greyd-posts-slider[live-query="true"]');
		// console.log(queryBlocks);
		if (queryBlocks.length == 0) return;
		
		// get wp rest api url from localized script
		var requestURL = greyd.rest_api.root + greyd.rest_api.routes.livequery;
		var requestURL2 = greyd.rest_api.root + greyd.rest_api.routes.livequeries;

		// initial breakpoint to trigger events on load
		var initialBreakpoint = null;
		var requestBreakpoint = false;

		// prepare post data
		const prepareData = (obj, perPage = false) => {

			// get block data
			var blockdata = atob(obj.wrap.getAttribute("data-block-data"));
			// console.log(blockdata);
			var block = JSON.parse(blockdata);
			block.attrs.query.anchor = obj.wrap.id;

			var taxQuery = {};
			var inheritQueryData = {};

			/**
			 * PostTemplate modifications
			 */
			// modify sorting attributes
			if (obj.sorting.length > 0 || obj.search?.sorting?.length > 0) {
				// console.log(obj.sorting, obj.search.sorting);
				var sort = obj.search?.sorting?.length > 0 ? obj.search.sorting : obj.sorting;
				var sortValue = sort[0].selectedOptions[0].value;
				if (sortValue.indexOf(' ') > 0) {
					sort = sortValue.split(' ');
					// console.log(sort);
					block.attrs.query.query.orderBy = sort[0];
					block.attrs.query.query.order = sort[1].toLowerCase();

					// loop through block.attrs.query.advancedFilter and remove order
					// to make sure the order is not set twice
					if ( block.attrs.query.advancedFilter && block.attrs.query.advancedFilter.length > 0 ) {
						block.attrs.query.advancedFilter.forEach( function( f, i ) {
							if ( f.name === 'order' ) {
								// remove custom order from advancedFilter
								block.attrs.query.advancedFilter.splice( i, 1 );
							}
						} );
					}

					inheritQueryData.order = sort[1].toLowerCase();
					inheritQueryData.orderby = sort[0];
				}
			}
			// modify filter (taxQuery) attributes
			if (obj.filter.length > 0 || obj.search?.filter?.length > 0) {
				// console.log(obj.filter, obj.search.filter);
				var filter = [
					...obj.filter.length > 0 ? obj.filter : [],
					...obj.search?.filter?.length > 0 ? obj.search.filter : [],
				];
				// console.log(filter);
				filter.forEach(function(filter) {
					const val = filter.value.split(',');
					val.forEach(function(f) {
						if (f != "") {
							if ( f.indexOf('|') > 0 ) {
								var [ slug, id ] = f.split('|');
								if (typeof taxQuery[slug] === 'undefined') taxQuery[slug] = [];
								taxQuery[slug].push(parseInt(id));
							}
							else {
								var slug = filter.name;
								if (typeof taxQuery[slug] === 'undefined') taxQuery[slug] = [];
								taxQuery[slug].push(f);
							}
						}
					})
					// console.log( filter, val, taxQuery );
				});
				block.attrs.query.query.taxQuery = taxQuery;
				block.attrs.query.query.taxQueryRelationship = 'AND';
			}
			// modify perPage attribute
			if (obj.perPage.length > 0) {
				var ppp = obj.perPage[0].selectedOptions[0].value;
				block.attrs.query.query.perPage = ppp;
			} else if (perPage) {
				block.attrs.query.query.perPage = perPage;
			}

			/**
			 * Search modifications
			 */
			// text input
			if (obj.search.input?.length > 0) {
				block.attrs.query.query.search = obj.search.input[0].value;

				inheritQueryData.s = obj.search.input[0].value;
			}
			// datepicker
			if (obj.search.date?.length > 0) {
				
				const dateWrapper = obj.search.date[0];
				const filterBy = dateWrapper.getAttribute('data-filter-by');
				const field = dateWrapper.getAttribute('data-field');

				block.attrs.query.query.date = {};
				block.attrs.query.query.date[filterBy] = {};
				block.attrs.query.query.date.filterBy = filterBy;

				const inputs = dateWrapper.parentNode.querySelectorAll(`input[name^="${filterBy}"]`);

				if (filterBy === "post_date" && inputs.length === 2) {
					const after = inputs[0].value;
					const before = inputs[1].value;

					if (after && before) {
						block.attrs.query.query.date[filterBy] = {
							after: after,
							before: before
						};
					}
				} else if (filterBy === "meta_date" || filterBy === "dynamic_meta_date" && inputs.length === 3) {
					let from, to;

					for (const input of inputs) {
						if ( input.matches(`input[name="${filterBy}[from]"`) ) {
							from = input.value;
						} else if ( input.matches(`input[name="${filterBy}[to]"`) ) {
							to = input.value;
						}
					}

					if (from && to) {
						block.attrs.query.query.date[filterBy] = {
							field: field,
							from: from,
							to: to
						};
					}
				}
			}
			// radio buttons
			if (obj.search.buttons?.length > 0) {

				// loop through all buttons (NodeList)
				[ ...obj.search.buttons ].forEach( button => {

					// get taxonomy name
					const taxonomyName = button.getAttribute('name');
					if (typeof taxQuery[taxonomyName] === 'undefined') taxQuery[taxonomyName] = [];

					if ( button.value && button.value.length ) {
						if ( button.value.indexOf(',') > 0 ) {
							const values = button.value.split(',');
							values.forEach(function(v) {
								taxQuery[taxonomyName].push(v);
							});
						} else {
							taxQuery[taxonomyName].push( button.value );
						}
					}
				} );
				
				block.attrs.query.query.taxQuery = taxQuery;
				block.attrs.query.query.taxQueryRelationship = 'AND';
			}


			// abort old request
			// happens when two ore more breakpoints are crossed
			if (obj.request) {
				// console.info("abort "+obj.wrap.id);
				obj.request.abort("abort");
			}

			// make post data
			var postdata = { block: JSON.stringify(block) };
			if ( block.attrs.query.advancedFilter ) {
				// send global post id for advanced filter
				postdata.postId = obj.wrap.getAttribute("data-post-id") ?? -1
				// add queried object id for 'current_archive_terms' filter
				postdata.queried_object_id = obj.wrap.getAttribute("data-queried-object-id") ?? -1
			}
			var query_vars = JSON.parse(obj.wrap.getAttribute("data-wp-query"));
			if ( query_vars ) {
				// send wp_query for conditional content
				postdata.wp_query = obj.wrap.getAttribute("data-wp-query");

				if ( inheritQueryData ) {
					let wp_query = JSON.parse( postdata.wp_query );
					wp_query = { ...wp_query, ...inheritQueryData };
					postdata.wp_query = JSON.stringify( wp_query );
				}
			}

			// send language
			if ( greyd?.rest_api?.lang ) {
				postdata.lang = greyd.rest_api.lang;
			}

			return postdata;

		};

		const showLoader = (obj) => {
			// show spinner
			obj.spinner.style.display = 'block';
			obj.spinner.style.position = 'absolute';
			obj.spinner.style.marginTop = '-'+obj.wrap.clientHeight+'px';
			obj.spinner.style.maxWidth = 'none';
			// hide greyd-posts-slider
			obj.wrap.style.opacity = 0;
		};
		const hideLoader = (obj) => {
			// hide spinner
			obj.spinner.style.display = 'none';
			// end request
			obj.request = false;
		};
		const setContent = (obj, content) => {
			// remove spinner
			obj.spinner.remove();
			// remove next sibling styleheets
			var styles = obj.wrap.nextElementSibling;
			while (styles && styles.tagName == 'STYLE') {
				styles.remove();
				styles = obj.wrap.nextElementSibling;
			}
			// set content
			obj.wrap.outerHTML = content;
		};

		// re-init custom JS events
		const reInit = ( obj ) => {

			// deprecated classic greyd features
			if ( typeof custom_inputs !== 'undefined' && typeof custom_inputs.init === 'function' ) {
				custom_inputs.init();
			}
			if ( typeof posts !== 'undefined' && typeof posts.init === 'function' ) {
				posts.init();
			}
			if ( typeof sorting !== 'undefined' && typeof sorting.init === 'function' ) {
				sorting.init();
			}
			if ( typeof tablesort !== 'undefined' && typeof tablesort.init === 'function' ) {
				tablesort.init();
			}
			// input (multiselects, selects, checkboxes, radios) features FSE
			if ( typeof greyd.input !== 'undefined' && typeof greyd.input.init === 'function' ) {
				greyd.input.init();
			}
			// query (post-slider) features FSE
			if ( typeof greyd.query !== 'undefined' && typeof greyd.query.init === 'function' ) {
				greyd.query.init();
			}
			if ( typeof greyd.trigger !== 'undefined' && typeof greyd.trigger.init === 'function' ) {
				greyd.trigger.init();
			}
			if ( typeof greyd.lazyload !== 'undefined' && typeof greyd.lazyload.init === 'function' ) {
				greyd.lazyload.init();
			}
			if ( typeof greyd.scrollObserver !== 'undefined' && typeof greyd.scrollObserver.init === 'function' ) {
				greyd.scrollObserver.init();
			}
			if ( typeof greyd.animations !== 'undefined' && typeof greyd.animations.init === 'function' ) {
				greyd.animations.init();
			}
			if ( typeof greyd.cssAnims !== 'undefined' && typeof greyd.cssAnims.init === 'function' ) {
				greyd.cssAnims.init();
			}
			if ( typeof greyd.accordions !== 'undefined' && typeof greyd.accordions.init === 'function' ) {
				greyd.accordions.init();
			}
			// re-init popovers
			if ( typeof greyd.popover !== 'undefined' && typeof greyd.popover.initPopover === 'function' ) {
				greyd.popover.initPopover();
			}
			// re-init forms
			if ( typeof greyd.forms !== 'undefined' && typeof greyd.forms.initForms === 'function' ) {
				greyd.forms.initForms();
			}
		
			// re-init this
			mediaQueries.trigger = {};
			initQueries();

			/**
			 * Custom Event: 'greyd-livequery-success'
			 * 
			 * This event is fired after a live query has successfully completed and all DOM updates
			 * and re-initializations are finished. It allows other plugins, themes, or custom code
			 * to hook into the live query lifecycle and perform additional initialization tasks
			 * on the newly loaded content.
			 * 
			 * Event Details:
			 * - Event Type: Custom DOM Event (no data payload)
			 * - Target Element: .wp-block-query wrapper containing the live query
			 * - Timing: Fired after content update and all greyd feature re-initialization
			 * - Bubbles: No (standard Event constructor default)
			 * - Cancelable: No (standard Event constructor default)
			 * 
			 * Common Use Cases:
			 * - Re-initialize third-party JavaScript libraries on new content
			 * - Update custom UI components that depend on the query results
			 * - Re-bind event handlers to newly loaded elements
			 * - Trigger analytics or tracking for dynamic content loading
			 * - Initialize accessibility features on new DOM elements
			 * 
			 * Usage Example:
			 * ```javascript
			 * document.addEventListener('DOMContentLoaded', function() {
			 *     const queryBlock = document.querySelector('.wp-block-query');
			 *     if (queryBlock) {
			 *         queryBlock.addEventListener('greyd-livequery-success', function(event) {
			 *             // Your custom re-initialization code here
			 *             console.log('Live query completed for:', event.target);
			 *             
			 *             // Example: Re-initialize a custom library
			 *             if (typeof myCustomLibrary !== 'undefined') {
			 *                 myCustomLibrary.init(event.target);
			 *             }
			 *         });
			 *     }
			 * });
			 * ```
			 * 
			 * Multiple Query Support:
			 * When multiple queries update simultaneously (e.g., responsive breakpoint changes),
			 * the event is fired individually for each affected .wp-block-query wrapper.
			 * 
			 * @since 1.5.0 Event introduced with live query functionality
			 * @since 2.7.0 Enhanced to support multiple simultaneous query updates
			 */
			const customEvent  = new Event( 'greyd-livequery-success' );
			if ( Array.isArray( obj ) ) {
				obj.forEach( item => {
					const queryWrapper = document.getElementById( item?.key ? item?.key : item?.wrap?.id )?.closest( '.wp-block-query' );
					if ( queryWrapper ) queryWrapper.dispatchEvent( customEvent );
				} );
			} else {
				const queryWrapper = document.getElementById( obj?.key ? obj?.key : obj?.wrap?.id )?.closest( '.wp-block-query' );
				if ( queryWrapper ) queryWrapper.dispatchEvent( customEvent );
			}
		};


		// live query change function
		const triggerChange = (obj, perPage = false) => {

			// make post data
			var postdata = prepareData(obj, perPage);

			// send request
			obj.request = jQuery.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: requestURL,
				data: JSON.stringify( postdata ),
			})
			.always( function() {
				// hide loader
				hideLoader( obj );
			} )
			.done( function onSuccess( response ) {
				// console.log(response);
	
				// set new content
				setContent( obj, response.block_content )
				// init custom JS events
				reInit( obj );
			} )
			.fail( function onError( XHR, textStatus ) {
				if (textStatus == "abort") {
					// console.info("old call aborted");
				}
				else {
					console.error("Failed to fetch Data: " + textStatus);
					console.log(XHR);
				}
			} );

			// mod html
			// show loader
			showLoader( obj );

		};

		// media query trigger function
		const triggerMatch = (event, first = false) => {
			// console.log(event);
			if (event.matches) {
				// console.log(event);
				var bps = { 'sm': ".hidden-xs", 'md': ".hidden-sm", 'lg': ".hidden-md", 'xl': ".hidden-lg" };
				var bp = event.target.bp;
				// console.log('Switch to '+bp+' screen.');
				// console.log(Object.keys(mediaQueries.trigger));
				var trigger = [];
				Object.keys(mediaQueries.trigger).forEach( (key, index) => {
					// console.log(key);
					// console.log(mediaQueries.trigger[key]);
					var items = mediaQueries.trigger[key].items;
					if (typeof items[bp] !== 'undefined') {
						// console.info('Switch '+key+' to '+items[bp]+' items per page.');
						// don't reload if it is inside a hidden element
						if ( mediaQueries.trigger[key].obj.wrap.closest(bps[bp]) ) return;
						// don't reload if this is the initial trigger and initial is set to this brakpoint
						if ( first && bp == mediaQueries.trigger[key].initial ) return;
						// don't reload if the items per page have not chaged (replaces the "one child check")
						if ( items[bp] == mediaQueries.trigger[key].current ) return;
						// add to call
						trigger.push( {
							key: key,
							postdata: prepareData(mediaQueries.trigger[key].obj, items[bp])
						} );
					}
				} );
				// console.log(trigger);
				triggerBreakpoint( trigger );
			} 
		};
		
		// trigger all live-queries in one call
		const triggerBreakpoint = ( objs ) => {

			// objs.forEach( item => {
			// 	let wpBlockPostTemplate = mediaQueries.trigger[item.key].obj.wrap;

			// 	// get the direct child '.query-pages-wrapper'
			// 	let queryPagesWrapper = wpBlockPostTemplate.querySelector('.query-pages-wrapper');

			// 	// see if it only has one child
			// 	if (queryPagesWrapper && queryPagesWrapper.children.length === 1) {
			// 		// remove this from objs because we only need 
			// 		objs = objs.filter( obj => obj.key !== item.key );
			// 		console.log('Removed '+item.key+' from trigger list.');
			// 	}
			// } );
			
			if (objs.length === 0) return;

			// abort old request
			// happens when two ore more breakpoints are crossed
			if (requestBreakpoint) {
				requestBreakpoint.abort("abort");
			}

			// send request
			requestBreakpoint = jQuery.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: requestURL2,
				data: JSON.stringify( objs ),
			})
			.always( function() {
				// hide all loaders
				objs.forEach( item => { hideLoader( mediaQueries.trigger[item.key].obj ) } );
				// end request
				requestBreakpoint = false;
			} )
			.done( function onSuccess( response ) {
				// console.log(response);
				// console.log(Object.keys(response));
	
				// set new contents
				objs.forEach( item => { setContent( mediaQueries.trigger[item.key].obj, response[item.key].block_content ) } );
				// init custom JS events
				reInit( objs );
			} )
			.fail( function onError( XHR, textStatus ) {
				if (textStatus == "abort") {
					// console.info("old call aborted");
				}
				else {
					console.error("Failed to fetch Data: " + textStatus);
					console.log(XHR);
				}
			} );

			// show all loaders
			objs.forEach( item => { showLoader( mediaQueries.trigger[item.key].obj ) } );
		};


		// process live-query elements
		queryBlocks.forEach( (wrapper, key) => {
			// console.log(wrapper);
			let obj = {
				wrap: wrapper,
				sorting: wrapper.querySelectorAll('.sorting select'),
				perPage: wrapper.parentElement.querySelectorAll('.perPage select'),
				filter: wrapper.parentElement.querySelectorAll('.filter input, .filter select'),
				spinner: wrapper.nextElementSibling,
				search: false,
				request: false
			};
			if ( wrapper.closest('.wp-block-query').querySelector('.wp-block-greyd-search.greyd-search-form') ) {
				const queryBlock = wrapper.closest('.wp-block-query');
				obj.search = {
					forms: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form'),
					input: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form input[type=search]'),
					sorting: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form .sorting select'),
					filter: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form .filter input, .wp-block-greyd-search.greyd-search-form .filter select'),
					date: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form .greyd-datepicker-input'),
					buttons: queryBlock.querySelectorAll('.wp-block-greyd-search.greyd-search-form .wp-block-greyd-search-filter-buttons input[type=hidden]')
				}
			}
			// console.log(obj);

			/**
			 * Add select and multiselect events
			 * need to use jQuery as eventlistener
			 * because multiselect trigger is fired with jQuery
			 */
			jQuery([...obj.filter, ...obj.perPage]).off('change').on('change', function(event) {
				triggerChange(obj);
			});

			/**
			 * Search events
			 */
			if ( obj.search ) {
				const isEnterKey = (e) => {
					// lock the enter key
					var keyCode = e.keyCode || e.which;
					if (keyCode === 13) {
						e.preventDefault();
						return true;
					}
					return false;
				}
				// input
				jQuery(obj.search.input).off("keypress keyup search").on('keypress', function(e) {
					if ( isEnterKey(e) ) return false;
				}).on("keyup search", function(e) {
					if ( isEnterKey(e) ) return false;
					// sync input
					obj.search.input.forEach( input => input.value = e.target.value );
					triggerChange(obj);
				});
				// sorting
				jQuery([ ...obj.search.sorting ]).off("change").on("change", function(e) {
					// sync input
					obj.search.sorting.forEach( input => {
						var opt = input.parentElement.querySelectorAll('[data-value="'+e.target.value+'"]');
						if ( opt[0] && !opt[0].classList.contains('same-as-selected') ) jQuery(opt).trigger('click');
					} );
					triggerChange(obj);
				});
				// filter and date
				jQuery([ ...obj.search.filter, ...obj.search.date, ...obj.search.buttons ]).off("change").on("change", function(e) {
					triggerChange(obj);
				});
				// submit
				jQuery(obj.search.forms).off("submit").on("submit", function(e) {
					e.preventDefault();
					triggerChange(obj);
				});
			}

			/**
			 * Add media query events
			 */
			let perPage = obj.wrap.getAttribute("data-perPage");
			if ( perPage && !wrapper.parentElement.closest('.greyd-posts-slider[live-query="true"]') ) {
				
				// get perPage data
				perPage = JSON.parse(perPage);
				// console.log(perPage);
				const bps = [ 'sm', 'md', 'lg', 'xl' ];
				const breakpoints = perPage['breakpoints'];
				const current = perPage['current'];
				const initial = perPage['initial'] ?? 'xl';
				let items = {};
				if (typeof perPage['items']['xl'] !== 'undefined')
					items = { 'sm': perPage['items']['xl'], 'md': perPage['items']['xl'], 'lg': perPage['items']['xl'], 'xl': perPage['items']['xl'] };
				if (typeof perPage['items']['lg'] !== 'undefined')
					items = { ...items, 'sm': perPage['items']['lg'], 'md': perPage['items']['lg'], 'lg': perPage['items']['lg'] };
				if (typeof perPage['items']['md'] !== 'undefined')
					items = { ...items, 'sm': perPage['items']['md'], 'md': perPage['items']['md'] };
				if (typeof perPage['items']['sm'] !== 'undefined')
					items = { ...items, 'sm': perPage['items']['sm'] };
				// console.log(items);

				// add to trigger list
				mediaQueries.trigger[obj.wrap.id] = { obj: obj, current: current, initial: initial, items: items };

				/**
				 * make media query conditions once on init of first query
				 */
				bps.forEach( (bp, index) => {
					if (typeof mediaQueries[bp] === 'undefined') {
						const breakpoint = breakpoints[bp];
						const conditions = [];
						if (index == 0) conditions.push('(max-width: '+breakpoint+'px)');
						else {
							var previous = breakpoints[bps[index-1]];
							if (index < 3) {
								conditions.push('(min-width: '+previous+'px)');
								conditions.push('(max-width: '+breakpoint+'px)');
							}
							else conditions.push('(min-width: '+previous+'px)');
						}
						// console.log(conditions);

						// match media change event
						mediaQueries[bp] = {};
						mediaQueries[bp].event = window.matchMedia(conditions.join(' and '));
						mediaQueries[bp].event.onchange = triggerMatch;
						mediaQueries[bp].event.bp = bp;
						// console.log(mediaQueries[bp]);
						if (mediaQueries[bp].event.matches) {
							// save breakpoint for initial trigger
							initialBreakpoint = bp;
						} 
					}
				} );
			}
		} );

		// initial trigger
		if ( initialBreakpoint ) {
			triggerMatch({ matches: true, target: { bp: initialBreakpoint } }, true);
		}

		// console.log("query scripts loaded.");
	}
	initQueries();
});