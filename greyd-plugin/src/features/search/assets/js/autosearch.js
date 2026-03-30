/**
 * Autofill search frontend script.
 * 
 * @deprecated in theme/default.js
 * @since 1.2.2 moved to hub/autosearch.js
 * @since 1.3.9 moved to blocks/autosearch.js
 * @since 2.3.0 moved to search/assets/js/autosearch.js
 */
( function() {

	if ( typeof $ === 'undefined' ) $ = jQuery;

	$( function() {
		greyd.autosearch.init();
	} );

} )( jQuery );

var greyd = greyd || {};
greyd.autosearch = new function() {

	this.init = function() {

		// don't execute if deprecated version still loads
		if ( typeof autofill !== 'undefined' && typeof autofill.init === 'function' ) {
			return false;
		}

		if ( $( '.custom-select.autosearch, .custom-select.autofill' ).length === 0 ) {
			return false;
		}

		$( '.custom-select.autosearch, .custom-select.autofill' ).each( function() {
			console.log(this);
			greyd.autosearch.initAutoSearch( this );
		} );

	};

	this.initAutoSearch = function( elem ) {

		var wrapper = $( elem );
		var select = wrapper.find( 'select' );
		var input = wrapper.closest( '[id^="input_"], .input-outer-wrapper' ).find( 'input[type="search"]' );
		wrapper.closest( 'form' ).attr( 'autocomplete', 'off' );

		// get the settings
		var settings = {
			showOnClick: wrapper.data( "show-on-click" ),
			forwardOnClick: wrapper.data( "forward-on-click" ),
			filter: wrapper.data( "filter" ),
			resultsPerQuery: parseInt( wrapper.data( "max-results" ) ),
			orderby: wrapper.data( "orderby" ),
			order: wrapper.data( "order" ),
			failed: wrapper.data( "noresult-text" ),
			loading: wrapper.data( "loading-text" )
		};

		// apply params from search form
		const blockForm = wrapper.closest( '.greyd-search-form, .search_form' );
		if ( blockForm.length ) {
			const postTypeInput = blockForm.find( '[name=post_type]' );
			if ( postTypeInput.length ) {
				settings.filter = postTypeInput.val();
			}
		}

		// set params
		var params = {
			order: settings.order,
			orderby: settings.orderby,
			autosearch: 'true',
			lang: greyd.rest_api.lang
		};
		if ( settings.filter ) {
			params.subtype = settings.filter;
		}
		if ( settings.resultsPerQuery && Math.floor( settings.resultsPerQuery ) ) {
			params.per_page = settings.resultsPerQuery;
		}
		params = Object.keys( params ).map( function( key ) {
			return [ key, params[key] ].join( '=' );
		} ).join( '&' );

		// get request URL
		var requestURL = greyd.rest_api.root + greyd.rest_api.routes.autosearch + '&_fields=title,url,date&'+params+'&search=';

		input.one( 'focus', function( e ) {
			// e.stopPropagation();

			var dropdown = wrapper.find( '.select-items' );
			dropdown.attr( 'loading', settings.loading );

			wrapper.find( '.select-selected' ).remove();
			dropdown.children( ':empty' ).remove();

			// append the loader as first child of the dropdown
			// dropdown.append("<div class='loader'>"+settings.loading+"</div>");


			var lastVal = null;

			var xhr = null;
			input.on( 'keyup click', function( event ) {
				event.stopPropagation();

				var value = $( this ).val();

				// toggle the dropdown
				if ( !value.length ) {
					dropdown.addClass( 'select-hide' );
				}
				else if ( settings.showOnClick ) {
					dropdown.removeClass( 'select-hide' );
				}

				const hasChanged = value != lastVal;
				lastVal = value;

				if ( value.length == 0 ) {
					dropdown.empty();
				}
				else if ( hasChanged ) {

					dropdown.children( '.failed' ).remove();
					if ( settings.showOnClick ) {
						dropdown.addClass( 'loading' );
					}

					// abort request if a new request is started
					if ( xhr ) xhr.abort();

					xhr = $.ajax( {
						type: "GET",
						url: requestURL+value
					} )
					.done( function( data ) {

						select.empty();
						dropdown.empty();
						dropdown.removeClass( 'loading' );

						if ( typeof data === 'string' ) {
							try {
								data = JSON.parse(data);
							}
							catch {
								data = false;
							}
						}

						if ( data.length ) {
							for ( var i = 0; i < data.length; i++ ) {
								if ( data[i].url && data[i].title ) {
									select.append( "<option value='"+data[i].url+"'>"+data[i].title+"</option>" );
									dropdown.append( "<div class='animate_fast'>"+data[i].title+"</div>" );
								}
							}
						}
						if ( select.children().length == 0 ) {
							dropdown.append( "<div class='animate_fast failed'>"+settings.failed+"</div>" );
						}

						// set xhr to null for sending only the last requested input
						xhr = null;
					} )
					.fail( function( jqXHR, textStatus ) {
						if ( textStatus !== "abort" ) {
							console.log( "Failed to fetch Data: "+textStatus, jqXHR.responseText );

							select.empty();
							dropdown.empty();
							dropdown.removeClass( 'loading' );
							dropdown.append( "<div class='animate_fast failed'>"+settings.failed+"</div>" );
						}
					} );
				}
			} );

			dropdown.click( function( e ) {
				// update the option 
				$( select ).find( 'option:contains("'+e.target.innerHTML+'")' ).attr( "selected", true );

				let value = $( select ).val();
				// if value contains an en dash replace it with a hyphen because the search does not work with an en dash
				if ( value.includes( '–' ) ) {
					value = value.replace( '–', '-' );
				}

				// redirect to the selected post
				if ( settings.forwardOnClick ) {
					window.location.href = value;
				}
				// default: submit the form with the selected value
				else {
					value = $( select ).find( 'option:selected' ).text();
					// if value contains an en dash replace it with a hyphen because the search does not work with an en dash
					if ( value.includes( '–' ) ) {
						value = value.replace( '–', '-' );
					}
					input.val( value );
					input.closest( "form" ).submit();
				}
			} );
		} );

		return;
	};

};