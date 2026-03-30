/**
 * Greyd Dashboard
 */
var greyd = greyd || { backend: {} };

greyd.dashboard = new function() {

	/**
	 * Install and activate plugin from dashboard
	 * 
	 * @param {DOM Element} elem button element
	 */
	this.activatePlugin = function ( elem ) {
		// get plugin data
		plugin_slug = elem.dataset.slug;
		plugin = elem.dataset.title;

		const activatePluginCallback = ( plugin_slug ) => {
			$.ajax( {
				method: "POST",
				url: ajax_var.url,
				data: {
					action : 'install_greyd_plugins',
					nonce: ajax_var.nonce,
					plugin: plugin_slug,
				}
			})
			.done( function ( response ) {
				console.log( "Plugin installation confirmed!", response );

				if (response.includes('Everything went smooth.')) {
					// if the request is successful, show success overlay and reload the page
					greyd.backend.triggerOverlay( true, {
						type: 'success',
						css: 'activate-plugin',
						replace: plugin
					} );
					location.reload();
				} else if (response.includes('Could not install the new plugin')) {
					// if the plugin installation or activation fails, show error overlay
					greyd.backend.triggerOverlay( true, {
						type: 'fail',
						css: 'activate-plugin',
						replace: plugin
					} );
				}
			} )
			.fail( function ( xhr ) {
				console.error( xhr );
				// if the request fails, show error overlay
				greyd.backend.triggerOverlay( true, {
					type: 'fail',
					css: 'activate-plugin',
					replace: plugin
				} );
			} );
		};

		// call confirm overlay and pass plugin slug as data
		greyd.backend.confirm(
			"activate-plugin",
			plugin,
			activatePluginCallback,
			[ plugin_slug ]
		);
	};

	/**
	 * Install and activate theme from dashboard
	 * 
	 * @param {DOM Element} elem button element
	 */
	this.activateTheme = function ( elem ) {
		// set to theme name for display
		theme = 'Greyd Theme';

		const activateThemeCallback = ( theme ) => {
			$.ajax( {
				method: "POST",
				url: ajax_var.url,
				data: {
					action : 'install_greyd_theme',
					nonce: ajax_var.nonce,
				}
			})
			.done( function ( response ) {
				console.log( "Theme installation confirmed!", response );

				if (response.includes('Everything went smooth.')) {
					// if the request is successful, show success overlay and reload the page
					greyd.backend.triggerOverlay( true, {
						type: 'success',
						css: 'activate-theme',
						replace: theme
					} );
					location.reload();
				} else if (response.includes('Could not install the new theme')) {
					// if the plugin installation or activation fails, show error overlay
					greyd.backend.triggerOverlay( true, {
						type: 'fail',
						css: 'activate-theme',
						replace: theme
					} );
				}
			} )
			.fail( function ( xhr ) {
				console.error( xhr );
				// if the request fails, show error overlay
				greyd.backend.triggerOverlay( true, {
					type: 'fail',
					css: 'activate-theme',
					replace: theme
				} );
			} );
		};

		// call confirm overlay and pass plugin slug as data
		greyd.backend.confirm(
			"activate-theme",
			theme,
			activateThemeCallback,
			[ theme ]
		);
	};

	/**
	 * Activate feature from dashboard
	 * 
	 * @param {DOM Element} elem button element
	 */
	this.activateFeature = function ( elem ) {
		
		// get feature data
		feature_slug = elem.dataset.slug;
		feature_name = elem.dataset.title;

		greyd.backend.triggerOverlay( true, {
			type: 'loading',
			css: 'activate-feature',
			replace: feature_name,
		} );

		$.ajax( {
			method: "POST",
			url: ajax_var.url,
			data: {
				action : 'activate_greyd_features',
				nonce: ajax_var.nonce,
				feature: feature_slug,
			}
		})
		.done( function ( response ) {
			console.log( "Feature activation confirmed!", response );

			if (response.includes('Everything went smooth.')) {
				// if the request is successful, show success overlay and reload the page
				greyd.backend.triggerOverlay( true, {
					type: 'success',
					css: 'activate-feature',
					replace: feature_name,
				} );
				location.reload();
			} else if (response.includes('Could not activate the feature')) {
				// if the plugin installation or activation fails, show error overlay
				greyd.backend.triggerOverlay( true, {
					type: 'fail',
					css: 'activate-feature',
					replace: feature_name,
				} );
			}
		} )
		.fail( function ( xhr ) {
			console.error( xhr );
			// if the request fails, show error overlay
			greyd.backend.triggerOverlay( true, {
				type: 'fail',
				css: 'activate-feature',
				replace: feature_name,
			} );
		} );
	};
	
}
