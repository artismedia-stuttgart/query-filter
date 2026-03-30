/**
 * Script for basic Greyd Backend
 */
(function() {

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		/**
		 * Compatibility with old class var used in theme and plugins.
		 * used by globalcontent, forms and one call from theme
		 */
		greyd_backend = greyd.backend;

		greyd.backend.init();

		console.log('Backend Scripts: loaded');
	} );

} )(jQuery);

greyd.backend = new function() {

	this.overlayTimeout;

	this.init = function() {
		// add some events to the wrapper
		this.addEvents();
	}
	this.addEvents = function() {

		// add trigger to escape-button
		$(".greyd_overlay .button[role='escape']").on("click", function() {
			greyd.backend.triggerOverlay(false, {
				id: $(this).closest(".greyd_overlay").attr("id")
			} );
		});

		// greyd info popup
		$(".greyd_popup_wrapper").on("click", function(e){
			greyd.backend.togglePopup(e, $(this));
		});

		// greyd system status popup
		var status_dialog = $(".greyd_system_status_wrapper dialog");

		$(".greyd_system_status_wrapper .open_modal_button").on("click", function(e){
			e.stopPropagation();
			if ( ! status_dialog[0].open ) status_dialog[0].showModal();
		});

		// greyd system status popup close
		$(".greyd_system_status_wrapper .close_modal_button").on("click", function(e){
			if ( status_dialog[0].open ) {
				status_dialog[0].close('closedialog');
			}
		});

		// greyd copy system status to clipboard
		$(".system_status_button").on("click", function(e){
			e.stopPropagation();
			var content = $(".greyd_system_status_text").val();
			navigator.clipboard.writeText(content);
			$(this).addClass('copied');
			$(this).children('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
			$(this).children('.text').addClass('hidden');
			$(this).children('.text.copied').removeClass('hidden');
		});

		// tabs
		$(".block_tabs .block_tab").on("click", function(){
			if ( $(this).hasClass("active") ) return;

			$(this).siblings(".block_tab.active").removeClass("active");
			$(this).addClass("active");
		})
	}

	this.togglePopup = function(e, clicked) {
		// console.log('Pop Up!');
		e.stopPropagation();
		var $toggle = clicked.children('.toggle');
		var dialog  = clicked.children('dialog');
		if ( dialog.length ) {
			if ( dialog[0].open ) dialog[0].close();
			else dialog[0].showModal();
		} else {
			$toggle.toggleClass("checked dashicons-info dashicons-no");
			$(document).one('click', function() {
				$toggle.removeClass("checked dashicons-no").addClass("dashicons-info");
			});
		}
	}

	this.toggleElemByClass = function(cls) {
		toggle = $(".toggle_"+cls);
		if (toggle.length !== 0) toggle.toggleClass("hidden");
	}

	this.toggleRadioByClass = function(cls, val) {
		$("[class*='"+cls+"']:not(."+cls+"_"+val+")").addClass("hidden");
		$("."+cls+"_"+val).removeClass("hidden");
	}

	/**
	 * Show an overlay for different states and actions.
	 *
	 * Works best with overlays build with the function:
	 * @see \tp\managementgeneral->render_overlay() (inc/general.php)
	 * use @filter greyd_overlay_contents to apply contents.
	 *
	 * @param {bool} show   Whether to show or hide the overlay
	 * @param {object} atts   Whether to show or hide the overlay
	 *      @property {string} type     CSS-class of wrapper (direct child of the overlay) to be shown
	 *      @property {string} css      CSS-class of direct child of the wrapper to be shown
	 *      @property {string} replace  Content inside '.replace' will be replaced by the given string
	 *      @property {string} id       ID of the overlay (default: 'overlay').
	 */
	this.triggerOverlay = function( show, atts ) {
		show = typeof show !== 'undefined' ? show : true;
		atts = atts ? atts : {};

		var type    = typeof atts.type !== "undefined"      ? atts.type     : "loading";
		var css     = typeof atts.css !== "undefined"       ? atts.css      : "init";
		var replace = typeof atts.replace !== "undefined"   ? atts.replace  : "";
		var id      = typeof atts.id !== "undefined"        ? atts.id       : "overlay";

		console.log( show, type, css, replace, id );

		clearTimeout(greyd.backend.overlayTimeout);
		var overlay = $(".greyd_overlay#"+id);
		if (overlay.length === 0) return false;

		if (show === true) {

			// show type
			var wrapper = overlay.children("."+type+", .always");
			wrapper.siblings().addClass("hidden");
			wrapper.each(function() {
				$(this).removeClass("hidden");
			});

			// replace elements
			if (replace.length > 0) {
				wrapper.find(".replace").html(replace);
			}

			// show elements
			wrapper.find(".depending").addClass("hidden");
			// if multiple classes are defined, show each

			if ( css && css.length ) {
				String(css).split(" ").forEach( k => {
					wrapper.find( ".depending."+k ).removeClass("hidden");
				});
				// show elements were both classes need to be set
				wrapper.find( ".depending." + css.split(" ").join('-') ).removeClass("hidden");
			}


			/**
			 * here we check if there are 2 overlays on the page (on hub page),
			 * we then loop through the wrappers and search for wrapper where only hidden containers are.
			 * Now we know that the overlay is not needed and can finally hide it
			 */
			if (overlay.length === 2) {
				// console.log("found 2 overlays");
				wrapper.each(function() {
					if ($(this).children(".depending").not(".hidden").length == 0) {
						// console.log("found not needed overlay --> hiding it");
						$(this).parent().addClass("hidden");
					} else {
						$(this).parent().removeClass("hidden");
					}
				});
			} else {
				// finally show overlay
				overlay.removeClass("hidden");
			}


			// hide overlay...
			if (type === "success") {
				greyd.backend.fadeOutOverlay();
			} else if (type === "fail") {
				// greyd.backend.fadeOutOverlay(5000);
			}
			// ...or reload page
			else if (type === "reload") {
				greyd.backend.reloadPage();
			}
		}
		else {
			$(".greyd_overlay").addClass("hidden");
			clearTimeout(greyd.backend.overlayTimeout);
		}
	}

	/**
	 * Confirm action via overlay
	 *
	 * @param string css        passed to the overlay function
	 * @param string replace    passed to the overlay function
	 * @param string callback   name of function to be called after confirmation
	 * @param array  args       arguments to be applied to the callback-function
	 */
	this.confirm = function(css, replace, callback, args, id) {
		var overlayArgs = {
			type: "confirm",
			css: css,
			replace: replace,
			id: id
		}
		this.triggerOverlay(true, overlayArgs);
		$(".greyd_overlay .button[role='confirm']").off("click").on("click", function() {
			if ( typeof callback === 'function' ) {
				callback.apply(this, args);
			}
			overlayArgs.type = "loading";
			greyd.backend.triggerOverlay( true, overlayArgs );
		});
	}

	/**
	 * Decide between two action via overlay
	 *
	 * @param {string} css             passed to the overlay function
	 * @param {object[]} callbacks     array of objects with callback and args
	 *  @property {function} callback  name of function to be called after confirmation
	 *  @property {array} args         arguments to be applied to the callback-function
	 */
	this.decide = function(css, callbacks, deprecated1, deprecated2, deprecated3 ) {
		this.triggerOverlay(true, {"type": "decision", "css": css, "replace": ""});

		// console.log( typeof callbacks, callbacks, deprecated1, deprecated2, deprecated3 );

		var callback1, args1, callback2, args2;
		if ( typeof callbacks === 'object' ) {
			if ( callbacks?.callback ) {
				callbacks = [ callbacks ];
			}
			callback1 = callbacks[0]?.callback;
			args1     = callbacks[0]?.args;
			if ( callbacks[1]?.callback ) {
				callback2 = callbacks[1]?.callback;
				args2     = callbacks[1]?.args;
			}
		} else {
			callback1 = callbacks;
			callback2 = deprecated1;
			args1     = deprecated2;
			args2     = deprecated3;
		}

		$(".greyd_overlay .button[role='decision'][decision='0']").off("click").on("click", function() {
			if ( typeof callback1 === 'function' ) callback1.apply(this, args1);
		});
		$(".greyd_overlay .button[role='decision'][decision='1']").off("click").on("click", function() {
			if ( typeof callback2 === 'function' ) callback2.apply(this, args2);
		});
	}

	this.fadeOutOverlay = function( time ) {
		time = time ? time : 2400;
		clearTimeout(greyd.backend.overlayTimeout);
		greyd.backend.overlayTimeout = setTimeout( function() {
			greyd.backend.triggerOverlay(false);
		}, time );
	}

	this.reloadPage = function( time ) {
		var form = $("form#reload_page");
		if ( form.length === 0 ) {
			form = $("#wpfooter").after("<form method='post' id='reload_page'></form>");
		}

		time = time ? time : 1500;
		clearTimeout(greyd.backend.overlayTimeout);
		greyd.backend.overlayTimeout = setTimeout( function() {
			$("form#reload_page").submit();
		}, time );
	}

	this.addPageTitleAction = function( label, args ) {
		if (typeof label === 'string') {
			var css     = args.css ? args.css : "";
			var url     = args.url ? " href='"+args.url+"'" : "";
			var id      = args.id  ? " id='"+args.id+"'" : "";
			var onclick = args.onclick ? " onclick='"+args.onclick+"'" : "";
			$("hr.wp-header-end").before("<a class='button-ghost page-title-action "+css+"'"+id+url+onclick+">"+label+"</a>");
		}
	}

	this.toggleOverlay = function(id) {
		$(".greyd_overlay_v2"+( id ? "#"+id : "" )).toggleClass("is-active");
	}

	this.openOverlay = function(id) {
		$(".greyd_overlay_v2"+( id ? "#"+id : "" )).addClass("is-active");
	}

	this.closeOverlay = function(id) {
		$(".greyd_overlay_v2"+( id ? "#"+id : "" )).removeClass("is-active");
	}

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
