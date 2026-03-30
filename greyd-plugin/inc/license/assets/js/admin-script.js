/**{
 * Greyd License amdin script.
 * 
 * This file handles the license key input UI.
 */
( function () {

	jQuery( function () {

		if ( typeof $ === 'undefined' ) $ = jQuery;

		greyd.licenseAdmin.init();

		console.log( 'License Scripts: loaded' );
	} );

} )( jQuery );

var greyd = greyd || {};

greyd.licenseAdmin = new function () {

	/**
	 * Holds the option name.
	 */
	this.optionName = '';

	/**
	 * Holds the API url.
	 */
	this.url = "https://update.greyd.io/license/api/v2/";

	/**
	 * Holds the current domain
	 */
	this.domain = page_details.domain;

	/**
	 * Holds the current version
	 */
	this.version = page_details.version;

	/**
	 * Holds the full license object retrieved from the server.
	 */
	this.fullLicense = {};

	/**
	 * Reload the page after X seconds.
	 */
	this.reloadAfterSeconds = 5 * 60;

	/**
	 * Holds the reload timeout.
	 */
	this.reloadTimeout = null;

	/**
	 * Init the class
	 */
	this.init = function () {

		this.optionName = option_name;

		// check for changes of the license key
		$( "input#" + this.optionName + "_key" ).on( "change", greyd.licenseAdmin.retrieveLicense );

		// update classes is state is updated
		$( 'input#' + this.optionName + '_domain_status' ).on( "change", function () {
			$( '#states' ).removeClass().addClass(
				greyd.licenseAdmin.getDomainStatus()
			);
		} );

		// validate license on load
		if ( greyd.licenseAdmin.getKey().length ) {
			greyd.licenseAdmin.retrieveLicense();
		} else {
			this.validateLicense();
		}
	};

	/**
	 * Validate the license using the encrypted license key.
	 */
	this.validateLicense = function () {

		const key = greyd.licenseAdmin.getEncryptedKey();
		if ( !key.length ) {
			$( "#submit.hidden" ).removeClass( "hidden" );
			greyd.licenseAdmin.setDomainStatus( 'empty' );
			return;
		}

		$( ".unverified" ).removeClass( "hidden" );
		$( "input#" + greyd.licenseAdmin.optionName + "_key" ).parent().addClass( 'loading' );

		$.ajax( {
			method: "POST",
			url: greyd.licenseAdmin.url + "validate/",
			data: JSON.stringify( {
				key: key,
				fingerprint: greyd.licenseAdmin.domain,
				version: greyd.licenseAdmin.version
			} )
		} )
			.done( function ( response ) {
				console.log( "Greyd.License: license fetched.", response );

				$( "input#" + greyd.licenseAdmin.optionName + "_key" ).parent().removeClass( 'loading' );

				if ( response.key ) {
					greyd.licenseAdmin.setEncryptedKey( response.key );
				}

				if ( response.message.status ) {
					greyd.licenseAdmin.setDomainStatus( response.message.status );

					if ( response.message.status === "version_invalid" ) {
						if ( response.data && response.data.version ) {
							greyd.licenseAdmin.setVersionDownloads( response.data.version );
						}
					}
				}
			} )
			.fail( function ( xhr ) {
				console.error( xhr );

				$( "input#" + greyd.licenseAdmin.optionName + "_key" ).parent().removeClass( 'loading' );

				greyd.licenseAdmin.setDomainStatus( '_error' );
			} );
	};

	/**
	 * Retrieve the full license from the server.
	 */
	this.retrieveLicense = function () {

		$( ".unverified" ).remove();
		$( ".verified" ).removeClass( "hidden" );
		$( "#submit.hidden" ).removeClass( "hidden" );

		$( "input#" + greyd.licenseAdmin.optionName + "_key" ).parent().addClass( 'loading' );

		// clear timeout
		window.clearTimeout( greyd.licenseAdmin.reloadTimeout );

		const key = greyd.licenseAdmin.getKey();
		if ( !key.length ) {

			greyd.licenseAdmin.setEncryptedKey( '' );
			greyd.licenseAdmin.setDomainStatus( 'empty' );
			greyd.licenseAdmin.setFullLicense( {} );
			greyd.licenseAdmin.updateInterface();

			return;
		}

		$.ajax( {
			method: "POST",
			url: greyd.licenseAdmin.url + "get/",
			data: JSON.stringify( {
				key: key,
				fingerprint: greyd.licenseAdmin.domain,
				version: greyd.licenseAdmin.version
			} )
		} )
			.done( function ( response ) {
				console.log( "Greyd.License: license retrieved", response );

				if ( response.key ) {
					greyd.licenseAdmin.setEncryptedKey( response.key );
				}

				if ( response.message.status ) {
					greyd.licenseAdmin.setDomainStatus( response.message.status );

					if ( response.message.status === "version_invalid" ) {
						if ( response.data && response.data.version ) {
							greyd.licenseAdmin.setVersionDownloads( response.data.version );
						}
					}
				}

				if ( response.data && response.data.license ) {
					greyd.licenseAdmin.setFullLicense( response.data.license );

					greyd.licenseAdmin.saveForm();
				}
				else {
					greyd.licenseAdmin.setFullLicense( {} );
				}
				greyd.licenseAdmin.updateInterface();

			} )
			.fail( function ( xhr ) {

				// proper license server response
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					console.error( xhr.responseJSON.message );
					$( '.invalid .error_text' ).html( xhr.responseJSON.message.text );
					greyd.licenseAdmin.setDomainStatus( 'invalid' );
				}
				// unknown error
				else {
					console.error( xhr );
					greyd.licenseAdmin.setDomainStatus( '_error' );
				}

				greyd.licenseAdmin.setEncryptedKey( '' );
				greyd.licenseAdmin.setFullLicense( {} );
				greyd.licenseAdmin.updateInterface();
			} );
	};

	/**
	 * Restart the reload timeout.
	 */
	this.restartReloadTimeout = function () {
		console.log( "Greyd.License: restart timeout to reload the page in " + greyd.licenseAdmin.reloadAfterSeconds + " seconds" );
		window.clearTimeout( greyd.licenseAdmin.reloadTimeout );
		greyd.licenseAdmin.reloadTimeout = window.setTimeout( () => {
			location.reload();
			return false;
		}, greyd.licenseAdmin.reloadAfterSeconds * 1000 );
	};


	/**
	 * =================================================================
	 *                          Interface
	 * =================================================================
	 */

	/**
	 * Update the interface based on the fullLicense object
	 */
	this.updateInterface = function () {

		const license = greyd.licenseAdmin.getFullLicense();
		const status = greyd.licenseAdmin.getDomainStatus();

		const newPolicies = {
			'Basic': 'Single',
			'Growth': 'Growth',
			'Agency': 'Scale',
			'Freelancer': 'Starter'
		};
		

		// remove loading
		$( "input#" + greyd.licenseAdmin.optionName + "_key" ).parent().removeClass( 'loading' );

		$.each( license, function ( key, val ) {

			// convert dates
			if ( key === "created" || key === "expire" ) {
				val = greyd.licenseAdmin.formatDate( val );
			}
			else if ( key === "max" ) {
				if ( license.policy == "Basic" || license.policy == "Bundle" ) {
					val = val === null ? "∞" : val;
				}
				else {
					val = "";
				}
			}
			else if ( key === "maxUsesTotal" ) {
				if ( license.policy == "Basic" || license.policy == "Bundle" ) {
					val = "";
				}
				else {
					val = val === null ? "∞" : val;
				}
			}
			else if ( key === "bonus" ) {
				val = !val ? "" : " +" + val;
			}
			else if ( key === "policy" ) {
				val = newPolicies[ val ];
			}

			// set inputs & text
			$( '.' + greyd.licenseAdmin.optionName + '_' + key ).html( val );

			// set machine names
			if ( typeof val === "number" ) {
				if ( val !== 1 ) {
					$( '.' + greyd.licenseAdmin.optionName + '_' + key + '_name' ).html( machine_plural );
				}
				else {
					$( '.' + greyd.licenseAdmin.optionName + '_' + key + '_name' ).html( machine_singular );
				}
			}
		} );

		// show or hide domain infos
		if ( typeof license.count !== "undefined" && license.count > 0 ) {
			$( '.license_infos.domain_infos' ).removeClass( 'hidden' );
		}
		else {
			$( '.license_infos.domain_infos' ).addClass( 'hidden' );
		}

		// show or hide license meta
		if (
			license.licenseKey && (
				status === "is_valid" ||
				status.indexOf( "limit_reached" ) !== -1 ||
				status.indexOf( "limit_open" ) !== -1 ||
				status === "version_invalid"
			)
		) {
			$( '.license_infos.license_meta' ).removeClass( 'hidden' );
		}
		else {
			$( '.license_infos.license_meta' ).addClass( 'hidden' );
		}

		// set policy
		$( '.license_infos.license_meta' ).attr( "data-policy", license.policy );

		// check frozen
		if ( license.frozen === true ) {
			console.warn( "Your License is frozen." );
			$( ".isfrozen" ).removeClass( "hidden" );
			$( ".isnotfrozen" ).addClass( "hidden" );
		}
		else {
			$( ".isnotfrozen" ).removeClass( "hidden" );
			$( ".isfrozen" ).addClass( "hidden" );
		}

		// set machines
		var select = $( 'select[name="greyd_active_domains"]' ); // select

		// remove old machines
		select.find( 'option.greyd_active_domain_name' ).remove(); // select
		$( ".license_infos tr.greyd_active_domain:not(.hidden)" ).remove(); // table-row

		if ( typeof license.machines !== "undefined" ) {

			// loop
			$.each( license.machines, function ( id, machine ) {

				select.append( "<option class='greyd_active_domain_name' value='" + id + "'>" + machine.domain + "</option>" ); // select

				const blueprint = $( ".license_infos tr.greyd_active_domain.hidden" ); // table-row
				const copiedRow = blueprint.clone(); // table-row
				const pastedRow = copiedRow.insertBefore( blueprint ).removeClass( 'hidden' );

				pastedRow.find( '.greyd_active_domain_name' ).html( machine.domain );
				pastedRow.find( '.greyd_active_domain_created' ).html( greyd.licenseAdmin.formatDate( machine.created ) );
				pastedRow.find( '.button[role="delete"]' ).attr( 'data-id', id ).attr( 'data-fingerprint', machine.domain );

				if ( machine.domain === greyd.licenseAdmin.domain ) {
					pastedRow.addClass( "current" );
				}
			} );
		}

		greyd.licenseAdmin.restartReloadTimeout();
	};

	/**
	 * Set download links for older theme & plugin versions.
	 * 
	 * @param {string} version 
	 */
	this.setVersionDownloads = function ( version ) {

		// modify version to last major one (0.8.6.1 --> 0.8.0)
		if ( version.indexOf( "." ) !== -1 ) {
			version = version.split( "." ).slice( 0, 2 ).join( "." ) + ".0";
		}

		let oldVersion = version.split( "." ).slice( 0, 3 ).join( "" );

		if ( oldVersion.length == 0 ) {
			oldVersion = '100';
		} else if ( version.length == 1 ) {
			oldVersion = version + '00';
		} else if ( version.length == 2 ) {
			oldVersion = version + '0';
		}

		$( ".state.version_invalid .download_links a" ).each( function () {
			if ( $(this).attr("href").includes("greyd_suite") ) { 
				$( this ).attr( "href", $( this ).attr( "href" ) + "_" + oldVersion + ".zip" );
			} else {
				$( this ).attr( "href", $( this ).attr( "href" ) + "." + version + ".zip" );
			}
		} );
	};

	/**
	 * Called whenever a overwrite-select is changed
	 * @param {DOM Element} elem select element
	 */
	this.selectChanged = function ( elem ) {
		if ( $( elem ).val().length ) {
			$( elem ).siblings( '.button' ).removeAttr( 'disabled' );
		} else {
			$( elem ).siblings( '.button' ).attr( 'disabled', 'disabled' );
		}
		greyd.licenseAdmin.restartReloadTimeout();
	};

	/**
	 * Send the form to the options.php endpoint
	 */
	this.saveForm = function () {
		$.post(
			'options.php',
			$( "form.greyd_settings_license" ).serialize()
		).fail( () => {
			console.log( 'Greyd.License: form could not be saved in background' );
		} )
			.done( () => {
				console.log( 'Greyd.License: form saved in background' );
				greyd.licenseAdmin.restartReloadTimeout();
			} );
	};


	/**
	 * =================================================================
	 *                          API Actions
	 * =================================================================
	 */

	/**
	 * Activate the current domain
	 */
	this.activateDomain = function () {

		const key = greyd.licenseAdmin.getKey();
		if ( !key.length ) return false;

		greyd.backend.triggerOverlay( true, {
			type: 'loading',
			css: 'activate',
			replace: greyd.licenseAdmin.domain
		} );

		$.ajax( {
			method: "POST",
			url: greyd.licenseAdmin.url + "activate/",
			data: JSON.stringify( {
				key: key,
				fingerprint: greyd.licenseAdmin.domain,
				version: greyd.licenseAdmin.version
			} )
		} )
			.done( function ( response ) {
				console.log( "Greyd.License: activated domain", response );

				greyd.backend.triggerOverlay( true, {
					type: 'success',
					css: 'activate',
					replace: greyd.licenseAdmin.domain
				} );

				greyd.licenseAdmin.retrieveLicense();
			} )
			.fail( function ( xhr ) {
				console.error( xhr );

				greyd.backend.triggerOverlay( true, {
					type: 'fail',
					css: 'activate',
					replace: greyd.licenseAdmin.domain
				} );
			} );
	};

	/**
	 * Deactivate a certain domain
	 * 
	 * @param {DOM Element} elem button
	 */
	this.deactivateDomain = function ( elem ) {

		const key = greyd.licenseAdmin.getKey();
		const fingerprint = $( elem ).data( "fingerprint" );

		if ( !key.length || !fingerprint.length ) return false;

		const deactivateCallback = ( domain ) => {
			$.ajax( {
				method: "POST",
				url: greyd.licenseAdmin.url + "delete/",
				data: JSON.stringify( {
					key: key,
					fingerprint: domain,
					version: greyd.licenseAdmin.version
				} )
			} )
				.done( function ( response ) {
					console.log( "Greyd.License: deactivated domain", response );

					greyd.backend.triggerOverlay( true, {
						type: 'success',
						css: 'delete',
						replace: domain
					} );

					greyd.licenseAdmin.retrieveLicense();
				} )
				.fail( function ( xhr ) {
					console.error( xhr );

					greyd.backend.triggerOverlay( true, {
						type: 'fail',
						css: 'delete',
						replace: domain
					} );
				} );
		};

		greyd.backend.confirm(
			"delete",
			fingerprint,
			deactivateCallback,
			[ fingerprint ]
		);
	};

	/**
	 * Overwrite a certain domain with the current domain.
	 * 
	 * @param {DOM Element} elem button
	 */
	this.overwriteDomain = function ( elem ) {

		const key = greyd.licenseAdmin.getKey();
		const select = $( elem ).siblings( "select" );
		const selectedId = select.val();
		const selectedName = select.find( 'option[value="' + selectedId + '"]' ).text();

		if ( !key.length || !selectedId.length ) return false;

		const overwriteCallback = ( id, domain ) => {
			$.ajax( {
				method: "POST",
				url: greyd.licenseAdmin.url + "overwrite/",
				data: JSON.stringify( {
					key: key,
					fingerprint: greyd.licenseAdmin.domain,
					version: greyd.licenseAdmin.version,
					id: id
				} )
			} )
				.done( function ( response ) {
					console.log( "Greyd.License: overwritten domain", response );

					greyd.backend.triggerOverlay( true, {
						type: 'success',
						css: 'overwrite',
						replace: domain
					} );

					greyd.licenseAdmin.retrieveLicense();
				} )
				.fail( function ( xhr ) {
					console.error( xhr );

					greyd.backend.triggerOverlay( true, {
						type: 'fail',
						css: 'overwrite',
						replace: domain
					} );
				} );
		};

		greyd.backend.confirm(
			"overwrite",
			selectedName,
			overwriteCallback,
			[ selectedId, selectedName ]
		);
	};


	/**
	 * =================================================================
	 *                          Helper
	 * =================================================================
	 */

	this.getKey = function () {
		return $( "input#" + this.optionName + "_key" ).val();
	};

	this.getEncryptedKey = function () {
		return $( "input#" + this.optionName + "_key_encrypted" ).val();
	};

	this.setEncryptedKey = function ( val ) {
		$( "input#" + this.optionName + "_key_encrypted" ).val( val ).trigger( "change" );
	};

	this.getDomainStatus = function () {
		return $( 'input#' + this.optionName + '_domain_status' ).val();
	};

	this.setDomainStatus = function ( val ) {
		$( 'input#' + this.optionName + '_domain_status' ).val( val ).trigger( "change" );
	};

	this.getFullLicense = function () {
		return this.fullLicense;
	};

	this.setFullLicense = function ( fullLicense ) {
		this.fullLicense = fullLicense;
	};

	this.formatDate = function ( date ) {
		if ( typeof date === "string" && date.indexOf( "-" ) !== -1 ) {
			return date.split( "-" ).reverse().join( "." );
		}
		return date;
	};
};