/**
 * Live search frontend script
 *
 * @since 0.8.8
 */

(function() {

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		greyd.livesearch.init();

		console.log('Live search Scripts: loaded');
	} );

} )(jQuery);

greyd.livesearch = new function() {

	/**
	 * Hold the current request
	 */
	this.request = null;

	/**
	 * Hold the current request args
	 */
	this.request_args = {}

	/**
	 * Hold the current CSS ID
	 */
	this.cssID = 1;

	/**
	 * Init the feature
	 */
	this.init = function() {

		if ( $('.greyd-posts-slider[live-search="true"]').length === 0 ) {
			return false;
		}

		$('.greyd-posts-slider[live-search="true"]').each(function(){
			greyd.livesearch.initSearch( $(this) );
		});
	}

	this.getObjects = function( wrapper ) {
		if ( greyd.rest_api.is_greyd_blocks ) {
			var parent = wrapper.parent();

			const isSearchTemplate = document.body.classList.contains("search");

			return {
				wrap: wrapper,
				parent: parent,
				search: {
					wrap: $(':is(main, .wp-site-blocks) form[role="search"]'),
					input: $(':is(main, .wp-site-blocks) form[role="search"] input[type="search"]')
				},
				posts: wrapper.find('.query-post'),
				results: wrapper.find('.query-page'),
				sorting: isSearchTemplate ? $(".greyd-search-form .sorting select") : wrapper.find('.sorting select'),
				filter: $('[id^="search_"] [id^="filter_"] select, [id^="search_"] [id^="filter_"] .greyd_multiselect input, .filter .greyd_multiselect input, .filter select'),
				date: $('.greyd-search-form .greyd-datepicker-input'),
				buttons: $('.greyd-search-form .wp-block-greyd-search-filter-buttons input[type=hidden]'),
				spinner: parent.children('.loading_spinner_wrapper'),
				loadMore: {
					wrap: parent.find('.load_more_wrapper'),
					button: parent.find('.load_more_wrapper .load_more'),
					spinner: parent.find('.load_more_wrapper .loading_spinner_wrapper'),
				},
				noResult: parent.children('.no_result')
			};
		}
		/**
		 * @deprecated WPBakery wrapper
		 */
		else if ( greyd.rest_api.is_vc )  {
			const spinner_html = "<div class='loading_spinner_wrapper' style='display:none;'><div class='loading_spinner'><div></div> <div></div> <div></div> <div></div> </div></div>";
			wrapper.prepend( spinner_html );
			wrapper.siblings('.load_more_wrapper').append( spinner_html );
			wrapper.append( "<p class='message info no_result' style='display: none;'>"+( wrapper.data("livesearch-labels").no_result )+"</p>");

			var load_more_wrapper = wrapper.siblings('.load_more_wrapper');

			var objects = {
				search: {
					wrap: $('main [id^="search_"]'),
					input: $('main [id^="search_"] input[type="search"]')
				},
				wrap: wrapper,
				parent: wrapper.parent(),
				posts: wrapper.find('.query-post'),
				results: wrapper.find('.query-page'),
				sorting: wrapper.siblings('[id^="sorting_"]').find("select"),
				filter: $('[id^="search_"] [id^="filter_"] select, [id^="search_"] [id^="filter_"] .greyd_multiselect input'),
				date: false,
				buttons: false,
				spinner: wrapper.find('.loading_spinner_wrapper'),
				loadMore: {
					wrap: load_more_wrapper,
					button: load_more_wrapper.find('.load_more'),
					spinner: load_more_wrapper.find('.loading_spinner_wrapper'),
				},
				noResult: wrapper.find('.no_result')
			};

			if (objects.sorting.length) objects.sorting.find('option').get(0).remove();

			// remove pagination and show load more
			wrapper.find('.pagination').remove();
			if ( wrapper.data('post-params')['page_count'] > 1 ) {
				objects.loadMore.button.show();
			}

			return objects;
		}
		else return false;
	}

	/**
	 * Init the search (per wrapper)
	 * @param {object} wrapper jQuery object of the wrapper
	 */
	this.initSearch = function( wrapper ) {

		var obj = greyd.livesearch.getObjects( wrapper );
		if (!obj) return;

		// build the full url
		var full_url = greyd.rest_api.root + greyd.rest_api.routes.livesearch;

		// get teh post type
		var post_type = 'any';
		const postTypeFromUrl = greyd.livesearch.getUrlParam("post_type");
		if ( postTypeFromUrl && postTypeFromUrl.length ) {
			post_type = postTypeFromUrl;
		}
		else {
			const postTypeInput = obj.search.wrap.find('input[name="post_type"]');
			if ( postTypeInput && postTypeInput.length ) {
				post_type = postTypeInput.val();
			}
		}

		// prevent submit for each button that is not a submit button
		$('.greyd-search-form button:not([type=submit])').on('click', function(e) {
			console.log('Prevent submit');
			e.preventDefault();
		} );

		// set query args
		this.request_args = {
			s:              greyd.livesearch.getUrlParam("s"),
			paged:          1,
			posts_per_page: parseInt( greyd.rest_api.posts_per_page ),
			filter: {
				post_type: post_type
			},
			lang: greyd.rest_api.lang,
			logged_in: $('body').hasClass('logged-in'),

			// layout args
			layout:         obj.wrap.data("post-layout"),
			setup:          obj.wrap.data("post-setup"),
			params:         obj.wrap.data("post-params"),
			table:          obj.wrap.data("post-table"),
			block:          obj.wrap.data("block-data")
		}

		// set query args for filter dropdowns
		obj.filter.each(function() {
			var filter_name     = $(this).prop("name");
			var filter_value    = $(this).val();
			greyd.livesearch.request_args.filter[filter_name] = filter_value;
		});

		// set query args from url
		var order, orderby;
		if ( order = greyd.livesearch.getUrlParam("order") ) {
			greyd.livesearch.request_args.filter.order = order;
		}
		if ( orderby = greyd.livesearch.getUrlParam("orderby") ) {
			greyd.livesearch.request_args.filter.orderby = orderby;
		}

		/**
		 * Always called before a search is triggered
		 */
		function onBeforeSend(reset) {

			// abort the current request
			if (greyd.livesearch.request) greyd.livesearch.request.abort();

			// reset
			if (reset) {
				greyd.livesearch.request_args.paged = 1;

				// mod html
				obj.posts.remove();
				obj.results.html("");
				obj.noResult.hide(); // remove the "no result" box
				obj.spinner.show();
				obj.loadMore.button.hide();

				// update url parameters
				var newUrlParams = greyd.livesearch.request_args.filter;
				newUrlParams['s'] = greyd.livesearch.request_args.s;
				greyd.livesearch.updateUrlQueryParams( newUrlParams );

				// update search query string
				if ( $('.query--search-query').length ) {
					$('.query--search-query').each(function() {
						$(this).text(greyd.livesearch.request_args.s);
					});
				}
			}

			// console.log( greyd.livesearch.request_args );
		}

		/**
		 * Always called after ajax is finished
		 */
		function onDefault() {
			obj.spinner.hide();
			obj.loadMore.spinner.hide();
		}

		/**
		 * Called when ajax was successfull
		 * @param {object} response response data
		 */
		function onSuccess( response ) {
			// console.log(response);

			// posts found
			if ( typeof response.results !== 'undefined' && response.results.length ) {

				let _wrap = obj.results;

				// table support
				if ( greyd.livesearch.request_args.table ) {
					if ( obj.results.children("table").length === 0 ) {
						obj.results.append("<table><tbody></tbody></table>");
					}
					_wrap = obj.results.find('tbody');
				}

				// append posts
				for ( var i=0; i < response.results.length; i++ ) {
					_wrap.append(response.results[i]);
				}

				// append style
				if ( typeof response.styles !== 'undefined' && response.styles.length ) {
					obj.results.append( response.styles.replace("title='greyd_custom_css'", "id='greyd_greyd.livesearch_css_"+greyd.livesearch.cssID+"'") );
					greyd.livesearch.cssID++;
				}

				if ( response.results.length < greyd.livesearch.request_args.posts_per_page ) {
					obj.loadMore.button.hide();
				} else {
					obj.loadMore.button.show();
				}

				// init custom JS events
				// deprecated classic greyd features
				if ( typeof custom_inputs !== 'undefined' && typeof custom_inputs.init === 'function' ) {
					custom_inputs.init();
				}
				// input (multiselects, selects, checkboxes, radios) features FSE
				if ( typeof greyd.input !== 'undefined' && typeof greyd.input.init === 'function' ) {
					greyd.input.init();
				}
				if ( typeof greyd.trigger !== 'undefined' && typeof greyd.trigger.init === 'function' ) {
					greyd.trigger.init();
				}

			} else {
				// no posts found
				obj.noResult.show();
				obj.loadMore.button.hide();
			}

			// set xhr to null for sending only the last requested input
			xhr = null;

			// update post count spans
			if ( typeof response.found_posts !== 'undefined' ) {
				let postCount = typeof response.found_posts !== 'undefined' ? response.found_posts : '0';
				// update found posts count
				if ( $('.query--found-posts').length ) {
					$('.query--found-posts').each(function() {
						if ( $(this).hasClass('count') && typeof counter.restartCounter === 'function' ) {
							counter.restartCounter($(this), postCount);
						}
						else {
							$(this).text(postCount);
						}
					});
				}
			}
		}

		/**
		 * Called when ajax completed with error
		 * @param {object} jqXHR
		 * @param {string} textStatus
		 */
		function onError( jqXHR, textStatus ) {
			// console.log(jqXHR.responseText);
			if ( textStatus !== "abort" ) {
				console.error( "Failed to fetch Data: " + textStatus, jqXHR );
			}
		}

		var oldQuery = this.request_args.s;

		// input changed
		obj.search.input.each(function() {

			// live search on input
			$(this).on("keyup search", function() {

				var query = $(this).val();

				// abort if it hasn't changed
				if ( query == oldQuery ) return;

				// mod query vars
				greyd.livesearch.request_args.s = query;

				// update document title
				greyd.livesearch.updateDocumentTitle( query, oldQuery );

				oldQuery = query;

				onBeforeSend(true); // true: reset

				greyd.livesearch.request = $.ajax({
					type: "POST",
					contentType: "application/json; charset=utf-8",
					url: full_url,
					data: JSON.stringify(greyd.livesearch.request_args),
				})
				.always( onDefault )
				.done( onSuccess )
				.fail( onError );
			});

			// lock the enter key
			$(this).on('keyup keypress', function(e) {
				var keyCode = e.keyCode || e.which;
				if (keyCode === 13) {
					e.preventDefault();
					return false;
				}
			});
		});

		// load more
		obj.loadMore.button.on("click", function() {

			greyd.livesearch.request_args.paged++;

			obj.loadMore.button.hide();
			obj.loadMore.spinner.show();

			onBeforeSend(false); // false: do not reset

			greyd.livesearch.request = $.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: full_url,
				data: JSON.stringify(greyd.livesearch.request_args),
			})
			.always( onDefault )
			.done( onSuccess )
			.fail( onError );
		});

		// live sorting
		obj.sorting.on("change", function() {

			// mod query vars
			const val = this.value;
			let sortingMode = null;

			if (val.includes("_")) {
				sortingMode = val.split("_");
			} else {
				sortingMode = val.split(" ");
			}

			greyd.livesearch.request_args.filter.orderby = sortingMode[0];
			greyd.livesearch.request_args.filter.order   = sortingMode[1];

			onBeforeSend(true); // true: reset

			greyd.livesearch.request = $.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: full_url,
				data: JSON.stringify(greyd.livesearch.request_args),
			})
			.always( onDefault )
			.done( onSuccess )
			.fail( onError );
		});

		// filter dropdown
		obj.filter.on("change", function() {

			// mod query vars
			var filter_name     = $(this).prop("name");
			var filter_value    = $(this).val();

			greyd.livesearch.request_args.filter[filter_name] = filter_value;

			onBeforeSend(true); // true: reset

			greyd.livesearch.request = $.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: full_url,
				data: JSON.stringify(greyd.livesearch.request_args),
			})
			.always( onDefault )
			.done( onSuccess )
			.fail( onError );
		});

		// date picker
		if ( obj.date ) {

			const setDateFilter = ( dateInput ) => {

				const hiddenInputs = $( dateInput ).nextAll("input[type=hidden]");

				if ( hiddenInputs.length === 0 ) {
					// = cleared
					const filterBy = $( dateInput ).data("filter-by");
					greyd.livesearch.request_args.filter[filterBy] = "";
				}
				else {
					// set the request args dynamically
					hiddenInputs.each( function() {
						var name = $(this).prop("name");
						var val  = $(this).val();
						
						// name can be like "post_date[after]" or "meta_date[from]"
						// -> split by [ and ] and append as object
						if ( name.includes("[") ) {
							var split = name.split("[");
							name = split[0];
							var index = split[1].replace("]", "");
							if ( !greyd.livesearch.request_args.filter[name] ) {
								greyd.livesearch.request_args.filter[name] = {};
							}
							greyd.livesearch.request_args.filter[name][index] = val;
						} else {
							greyd.livesearch.request_args.filter[name] = val;
						}
					} );
				}

				console.log( greyd.livesearch.request_args.filter );
			}

			setDateFilter( obj.date );

			// on change
			obj.date.on("change", function() {

				setDateFilter( this );

				onBeforeSend(true); // true: reset

				greyd.livesearch.request = $.ajax({
					type: "POST",
					contentType: "application/json; charset=utf-8",
					url: full_url,
					data: JSON.stringify(greyd.livesearch.request_args),
				})
				.always( onDefault )
				.done( onSuccess )
				.fail( onError );
			});
		}

		// filter buttons
		if ( obj.buttons ) obj.buttons.on("change", function() {

			// mod query vars
			var filter_name     = $(this).prop("name");
			var filter_value    = $(this).val();

			greyd.livesearch.request_args.filter[filter_name] = filter_value;

			onBeforeSend(true); // true: reset

			greyd.livesearch.request = $.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: full_url,
				data: JSON.stringify(greyd.livesearch.request_args),
			})
			.always( onDefault )
			.done( onSuccess )
			.fail( onError );
		});
		
		// submit action
		$(':is(main, .wp-site-blocks) form[role="search"]').on( 'submit', function(e) {
			e.preventDefault();

			onBeforeSend(true); // true: reset

			greyd.livesearch.request = $.ajax({
				type: "POST",
				contentType: "application/json; charset=utf-8",
				url: full_url,
				data: JSON.stringify(greyd.livesearch.request_args),
			})
			.always( onDefault )
			.done( onSuccess )
			.fail( onError );
		})
	}

	this.getUrlParam = function( paramName ) {
		const searchURL    = window.location.search.substring(1);
		const paramStrings = searchURL.split('&');

		for (var i = 0; i < paramStrings.length; i++) {

			if ( paramStrings[i].indexOf('=') === -1 ) {
				// skip if no '=' (invalid parameter)
				continue;
			}

			let currentParamName = paramStrings[i].split('=');

			if ( currentParamName[0] === paramName ) {
				if ( currentParamName[1] === false ) {
					return false;
				}
				
				return currentParamName[1].length ? decodeURIComponent(currentParamName[1]) : '';
			}
		}
		return false;
	};

	/**
	 * Update query parameters in the current window.location
	 * @param {object} newParams Params to be updated in the url
	 */
	this.updateUrlQueryParams = function( newParams ) {
		if ( !newParams || typeof newParams !== 'object' ) return;

		var paramString = window.location.search;

		for (var key in newParams) {
			
			// if newParams[key] is string
			if ( typeof newParams[key] === 'string' ) {
				var regex = new RegExp('(^|\\?|&)('+key+')=.*?(&|#|$)', 'g');
				var newVal = greyd.livesearch.fixedEncodeURIComponent( newParams[key] );

				// replace parameter with the new value
				paramString = paramString.replace( regex, '$1$2='+newVal+'$3' );
			}
			// if newParams[key] is object
			else if ( typeof newParams[key] === 'object' ) {
				for (var subKey in newParams[key]) {
					var regex = new RegExp('(^|\\?|&)('+key+'%5B'+subKey+'%5D)=.*?(&|#|$)', 'g');
					var newVal = greyd.livesearch.fixedEncodeURIComponent( newParams[key][subKey] );

					// replace parameter with the new value
					paramString = paramString.replace( regex, '$1$2='+newVal+'$3' );
				}
			}
		}

		window.history.replaceState( null, null, paramString );
	}

	/**
	 * Improved version of encodeURIComponent
	 * @link https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
	 * @param {string} str
	 * @returns {string}
	 */
	this.fixedEncodeURIComponent = function(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, function(c) {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}

	/**
	 * Update the document title if the search term is included.
	 * @param {string} newQuery Current search term.
	 * @param {string} oldQuery Previous search term.
	 */
	this.updateDocumentTitle = function( newQuery, oldQuery ) {
		var regex = new RegExp( '("|\'|„|‚|»|›|“|‘)('+oldQuery+')("|\'|“|‘|«|‹|”|’)', 'g' );
		document.title = document.title.replace( regex, '$1'+newQuery+'$3' );
	}
}