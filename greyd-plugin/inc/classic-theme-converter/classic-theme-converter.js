/**
 * Transform classic contents.
 *
 * @package Greyd
 */

document.addEventListener( "DOMContentLoaded", function () {

	greyd.transform.init();

	console.log( 'Transform classic contents Scripts: loaded' );

} );

greyd.transform = new function () {

	this.snackbarTimeout = null;

	this.init = function () {

		if ( !document.getElementById( 'greyd-classic-converter' ) ) return;

	};

	this.testcall = function () {

		this.ajax.call( {
			mode: 'some_test',
			data: { my: 'testdata' }
		} );
	};

	this.call = function ( element ) {
		// console.log(element);

		var mode = element.dataset.mode;
		var data = {};
		if ( element.dataset.data ) {
			data = JSON.parse( decodeURIComponent( element.dataset.data ) );
		}
		console.log( mode );
		console.log( data );

		element.parentNode.querySelector( '.loading' )?.classList.remove( 'hidden' );

		const snackbar = document.getElementById( 'converter-snackbar' );

		this.ajax.call( {
			mode: mode,
			data: data
		} )
			.then( ( text ) => {
				if ( mode == 'activate_plugin' ) {
					location.reload();
				}
				clearTimeout( greyd.transform.snackbarTimeout );
				// element.parentNode.querySelector( '.dashicons-yes' )?.classList.remove( 'hidden' );
				snackbar.querySelector( '.components-snackbar__icon' ).innerHTML = '✅';
				snackbar.querySelector( '.components-snackbar__text' ).innerHTML = text;
			} )
			.catch( ( error ) => {
				console.warn( error );
				clearTimeout( greyd.transform.snackbarTimeout );
				// element.parentNode.querySelector( '.dashicons-no-alt' )?.classList.remove( 'hidden' );
				snackbar.querySelector( '.components-snackbar__icon' ).innerHTML = '❌';
				snackbar.querySelector( '.components-snackbar__text' ).innerHTML = error;
			} )
			.finally( () => {

				element.parentNode.querySelector( '.loading' )?.classList.add( 'hidden' );

				snackbar.classList.add( 'is-active' );
				greyd.transform.snackbarTimeout = setTimeout( () => {
					snackbar.classList.remove( 'is-active' );
					snackbarTimeout = null;
				}, 3000 );
			} );
	};

	/**
	 * handle ajax calls
	 */
	this.ajax = new function () {

		this._ajax = greyd.transformAjax ?? false;
		this._busy = false;
		this._start = function () {
			this._busy = true;
		};
		this._finish = function () {
			this._busy = false;
		};

		this.call = async function ( config ) {

			if ( this._busy ) {
				return Promise.reject( 'busy ...' );
			}
			if ( !this._ajax ) {
				return Promise.reject( 'no ajax ...' );
			}
			if ( !config.mode || !config.data ) {
				return Promise.reject( 'wrong config ...' );
			}

			this._start();
			console.log( this._ajax );

			var data = new FormData();
			data.append( '_ajax_nonce', this._ajax.nonce );
			data.append( 'action', this._ajax.action );
			data.append( 'mode', config.mode );
			data.append( 'data', encodeURI( JSON.stringify( config.data ) ) );

			return fetch( this._ajax.url, {
				method: 'POST',
				body: data,
			} )
				.then( ( response ) => {
					// console.log(response);
					// error handling
					if ( !response.ok ) {
						console.warn( response );
						return Promise.reject( response.statusText );
					}
					// get text response
					return response.text();
				} )
				.then( ( text ) => {
					// console.log(text);
					// handle success/error
					if ( text.indexOf( 'success::' ) > -1 ) {
						// valid success
						text = text.split( 'success::', 2 )[ 1 ];
						return decodeURIComponent( text );
					}
					else if ( text.indexOf( 'error::' ) > -1 ) {
						// valid error
						text = text.split( 'error::', 2 )[ 1 ];
						return Promise.reject( text );
					}
					return Promise.reject( 'response not valid' );
				} )
				.finally( () => this._finish() );

		};
	};

	/**
	 * Check if page is dirty
	 */
	this.dirty = false;
	window.onbeforeunload = function () {
		// console.log("check inputs");
		if ( greyd.transform.dirty ) {
			return false;
		}
	};

};