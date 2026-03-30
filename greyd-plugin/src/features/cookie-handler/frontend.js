/**
 * Cookie handler frontend script.
 * Avoid caching of conditional content and hidden fields if they use url-params or cookies
 */

window.addEventListener('load', (event) => {
	
	// get elements
	var conditionElements = document.querySelectorAll( ".wp-block-greyd-conditional-content[data-harden-conditions]" );
	var fieldElements = document.querySelectorAll( "form [data-harden-field]" );
	var liveFieldElements = document.querySelectorAll( "form [data-live-field]" );
	// console.log( conditionElements, fieldElements );
	if (
		( !conditionElements || conditionElements.length == 0 ) &&
		( !fieldElements || fieldElements.length == 0 ) &&
		( !liveFieldElements || liveFieldElements.length == 0 )
	) {
		// console.info("exit");
		return;
	}

	/**
	 * Match condition.
	 *
	 * @param string is         Current value.
	 * @param string should     What the value should be.
	 * @param string condition  Actual condition.
	 * @returns bool
	 */
	const matchCondition = function( is, should, condition = 'is' ) {

		// sanitize
		is = !is ? "" : (""+is+"").trim();
		should = !should ? "" : (""+should+"").trim();

		if ( condition === 'is' ) {
			return should === is;
		} 
		else if ( condition === 'is_not' ) {
			return should !== is;
		} 
		else if ( condition === 'has' ) {
			return is.indexOf( should ) > -1;
		} 
		else if ( condition === 'has_not' ) {
			return is.indexOf( should ) == -1;
		} 
		else if ( condition === 'empty' ) {
			return is === "";
		} 
		else if ( condition === 'not_empty' ) {
			return is !== "";
		}
		return false;

	};

	/**
	 * Check conditions.
	 * 
	 * @param array conditions  Array of conditions.
	 * @param object data       All url params and cookie values.
	 */
	const checkConditions = function( conditions, data ) {

		// check input
		if ( !conditions || conditions.length == 0 ) {
			return;
		}

		conditions.forEach( elementConditions => {

			if ( !elementConditions || elementConditions.length == 0 ) {
				return;
			}

			var results = [];
			elementConditions.forEach( condition => {

				var key = condition.detail;
				var values = {};
				if ( condition.type == 'localStorage' ) {
					values = localStorage;
				}
				else if ( condition.type == 'urlparam' || condition.type == 'cookie' ) {
					values = condition.type == 'urlparam' ? data.url_values : data.cookie_values;
					if ( key == 'custom' ) {
						key = condition.custom;
						values = condition.type == 'urlparam' ? data.all_url_values : data.all_cookie_values;
					}
				}
				// console.log(key, values);

				var is = values[key] ? values[key] : null;
				var match = matchCondition( is, condition.value, condition.operator );
				results.push( match );

			} );

			var operator = elementConditions[0].mainoperator ?? "OR";
			var id = elementConditions[0].id;
			// console.log(results, operator, id);
			var final = operator === "OR" ? results.indexOf( true ) > -1 : results.indexOf( false ) === -1;
			if ( final ) {
				// console.log( "condition "+id+" should be visible" );
				var element = document.getElementById( id );
				if ( element ) element.style.display = 'block';
			}
			else {
				// console.log( "condition "+id+" should NOT be visible" );
				var element = document.getElementById( id );
				if ( element ) element.remove();
			}

		} );

	};

	/**
	 * Check hidden form fields.
	 * 
	 * @param array fields      Array of fields.
	 * @param object data       All url params and cookie values.
	 */
	const checkHiddenFields = function( fields, data ) {

		// check input
		if ( !fields || fields.length == 0 ) {
			return;
		}
		
		fields.forEach( field => {
			// console.log(field);

			var value = '';
			if ( field.type == 'url' ) {
				value = window.location.origin+window.location.pathname;
			}
			else if ( field.type == 'live_url' ) {
				value = window.location.href;
			}
			else {
				var key = field.name;
				var values = field.type == 'cookie' ? data.all_cookie_values : data.all_url_values;
				value = values[key] ? values[key] : field.default;
			}

			// console.log( "field "+field.id+" should be "+value );
			var element = document.getElementById( field.id );
			if ( element ) {
				element.value = value;
				// trigger change event
				element.dispatchEvent( new Event( 'change' ) );
			}

		} );

	};

	/**
	 * Get all url params and cookie values from rest.
	 * 
	 * @returns array
	 */
	const getValues = async function() {

		// get all url params to send them with the api request
		var urlparams = new URLSearchParams( window.location.search );
		urlparams = Object.fromEntries( urlparams.entries() );

		// get all url params and cookie values from rest and re-check conditions
		return fetch( greyd.rest_api.root + 'greyd/v1/get_url_and_cookie_values', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				urlparams: urlparams,
			} ),
		} ).then( ( res ) => {
			// console.log( res );
			// get json response
			return res.json();
		} ).then( ( data ) => {
			// console.log( 'url_and_cookie_values:', data );
			// handle success/error
			if (
				data &&
				data.url_values && data.all_url_values &&
				data.cookie_values && data.all_cookie_values
			) {
				return data;
			}
			return Promise.reject( 'response not valid' );
		} ).catch( ( err ) => {
			// log error
			console.error( "fetch error: ", err );
		} );

	};

	/**
	 * Get data from Elements.
	 * 
	 * @param nodeList elements  List of dom elements.
	 * @param string attribute   Name of the data attribute to get data from.
	 * @returns array
	 */
	const getData = function( elements, attribute ) {

		if ( !elements || elements.length == 0 || attribute == "" ) {
			return false;
		}

		var result = [];

		// console.log(elements);
		if ( elements && elements.length > 0 ) {
			elements.forEach( element => {
				// console.log(element);
				try {
					var value = element.getAttribute(attribute);
					var json = JSON.parse( value );
					result.push( json );
				}
				catch {}
				element.removeAttribute(attribute);
			} );
			result = result.filter( item => item );
		}
		if ( !result || result.length == 0 ) {
			return false;
		}
		return result;

	}

	
	// get conditions and hidden fields data
	var conditions  = getData( conditionElements, "data-harden-conditions" );
	var hiddenFields = getData( fieldElements, "data-harden-field" );
	if ( conditions || hiddenFields ) {

		// get url and cookie values, then check
		// console.groupCollapsed("sending rest request ...");
		// if ( conditions ) console.log("hardening conditions");
		// if ( hiddenFields ) console.log("hardening hidden fields");
		// console.groupEnd();
		getValues().then( ( data ) => {
			if ( data ) {
				// console.info("rest data received");
				checkConditions( conditions, data );
				checkHiddenFields( hiddenFields, data );
			}
		} );

	}
	
	// get hidden fields (of type 'live_url') data
	var liveFields = getData( liveFieldElements, "data-live-field" );
	if ( liveFields ) {

		// check/fill field initially
		checkHiddenFields( liveFields, {} );

		// listen for change of url
		window.addEventListener("hashchange", function() {
			// console.log(window.location.href);
			checkHiddenFields( liveFields, {} );
		});

	}

} );
