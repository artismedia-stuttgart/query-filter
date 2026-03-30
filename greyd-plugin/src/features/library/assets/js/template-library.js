/*
	Comments: Javascript File for Template Library
*/

(function() {

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		greyd.templateLibrary.init();

		console.log('Template Library Scripts: loaded');
	} );

} )(jQuery);

greyd.templateLibrary = new function() {

	/**
	 * Class vars
	 */
	this.posts = {};
	this.categories = {};
	this.mode = null;
	this.type = null;
	this.lang = null;
	this.blogID = null;
	this.domain = null;

	this.userThumbnailFileName = null;

	this.isClassic = null;
	this.isClassicMSG = null;

	/**
	 * Init the class
	 */
	this.init = function() {

		var wrapper = $('#template_library');
		if ( wrapper.length === 0 ) return false;

		// console.log("Init template library");

		// set class variables
		greyd.templateLibrary.mode = wrapper.data('mode');
		greyd.templateLibrary.type = wrapper.data('type');
		greyd.templateLibrary.lang = wrapper.data('lang');
		greyd.templateLibrary.isClassic = wrapper.data('is-classic');
		greyd.templateLibrary.isClassicMSG = wrapper.data('is-classic-msg');

		this.addEvents();
	}

	/**
	 * Add all events after jQuery is fully loaded
	 */
	this.addEvents = function() {

		// start via welcome screen
		$('#template_library .start_library').on('click', function() {
			greyd.templateLibrary.handleTabSwitch( $(".template_library_items").first().closest(".template_library_body") );
			greyd.templateLibrary.setHideWelcomeScreenSetting();
		});

		$('.open_library').on('click', function() { greyd.templateLibrary.open(); });
		$('#template_library .back_to_main_library').on('click', function() {
			greyd.templateLibrary.setState("main");
		});

		$('.template_library_add_new_user_template').on('click', function() { greyd.templateLibrary.showUserTemplateForm({id: greyd.templateLibrary.blogID, siteurl: greyd.templateLibrary.domain}) })

		$('#template_library .template_library_tab').on('click', function() {
			greyd.templateLibrary.handleTabSwitch( $(this) )
		 });
	}

	/**
	 * Open template library (+ close wizard if open)
	 */
	this.open = function(data='') {

		if ( data.id && data.siteurl) {
			greyd.templateLibrary.blogID = data.id;
			greyd.templateLibrary.domain = data.siteurl;
		} else {
			greyd.templateLibrary.blogID = $('#template_library').data('blog-id');
			greyd.templateLibrary.domain = $('#template_library').data('blog-domain');
		}

		// maybe it's better to greyd.wizard.finish() or greyd.wizard.close()
		if( $('.wizard').length ) $('.wizard').hide();
		if( $('.wizzard').length ) $('.wizzard').hide();

		// show template library
		greyd.backend.openOverlay('template_library');

		// load templates
		greyd.templateLibrary.fetchTemplates();

		// open user templates on init
		if ( $('#manage_user_templates').length && greyd.templateLibrary.mode == "fullsite" ) {
			greyd.templateLibrary.handleTabSwitch(
				$(".template_library_body[data-type='user_fullsite']")
			);
		}

	}

	/**
	 * Close the template library popup
	 */
	this.close = function() {
		$('.template_library').hide();
	}

	/**
	 * Find the slide and set it to active
	 * @param {string} state (main|details|welcome)
	 */
	this.setState = function(state) {
		this.showSlide( $('#template_library .template_library_content').find("div[data-slide='" + state + "']") );
	}

	/**
	 * Add class 'active' to the current slide
	 * @param {object} slide Slide jQuery object
	 */
	this.showSlide = function(slide) {
		slide.addClass("active");
		slide.siblings().removeClass("active");
	}

	/**
	 * Fetch templates from library
	 *
	 * This appends the fetched posts to the @var posts
	 * and triggers the @function displayTemplates() afterwards.
	 */
	this.fetchTemplates = function() {

		// if ( !jQuery.isEmptyObject(greyd.templateLibrary.posts) ) {
		//     return false;
		// }

		const wrapper = $(".template_library_body.active");

		// already loaded
		if ( wrapper.find(".template_library_item_image.loading").length === 0 ) return;

		// get mode
		const { mode, lang } = greyd.templateLibrary;
		const type = wrapper.data( 'type' );

		// fetch user templates
		if ( type == 'user_fullsite' ) {
			greyd.templateLibrary.fetchUserTemplates();
			return;
		}

		$.ajax({
			method: "GET",
			url: "https://greyd-resources.de/"+( lang === "en" ? "en/" : "" )+"wp-json/greyd/v1/template_library/"+mode+"s/",
			data: mode === 'partial' ? { post_type: type } : null
		})
		.fail(function(xhr) {
			if (typeof xhr.responseJSON === "undefined" || xhr.responseJSON.length === 0) {
				console.log( xhr );
			}
			else {
				console.info( "Greyd RESPONSE:\n * "+xhr.responseJSON.message );
			}
			wrapper.find('.template_library_error').removeClass('hidden');
			wrapper.children(':not(.template_library_error)').remove();
			wrapper.find('.loading').remove();
			return false;
		})
		.done(function( response ) {
			if ( !response.code || response.code !== 'success' || !response.data || !response.data.status || response.data.status !== 200 || !response.data.responseData ) {
				console.warn( response );
				return false;
			}
			console.info( response.message );

			const foundPosts = response.data.responseData.posts;
			const foundCategories = response.data.responseData.categories;

			greyd.templateLibrary.posts = {
				...greyd.templateLibrary.posts,
				...foundPosts
			};
			greyd.templateLibrary.categories = {
				...greyd.templateLibrary.categories,
				...foundCategories
			};
			if (mode !== "pattern") {
				greyd.templateLibrary.displayTemplates( foundPosts, foundCategories, wrapper );
			} else {
				greyd.templateLibrary.displayPatterns( foundPosts, foundCategories, wrapper );
			}
		});
	}

	/**
	 * Display all templates loaded from endpoint
	 */
	this.displayTemplates = function( posts, categories, wrapper ) {

		const tabNumber = wrapper.data('tab');

		var container               = wrapper.find(".template_library_items");
		var categoryWrapper         = $(".template_library_main_wrapper .overlay_sidebar .sidebar_top_content[data-tab='"+tabNumber+"']");
		var placeholderItems        = container.children();
		var placeholderCategories   = categoryWrapper.children(".template_category");
		var itemBlueprint           = placeholderItems.first();
		var categoryBlueprint       = placeholderCategories.first();

		// remove placeholder and loading animation
		placeholderItems.find(".loading").removeClass("loading");
		placeholderItems.remove();
		placeholderCategories.removeClass("loading");
		placeholderCategories.remove();

		// hide container
		categoryWrapper.hide();
		container.hide();

		/**
		 *  Render categories
		 */

		// all categories-button
		const allCategoriesButton = categoryBlueprint.clone().addClass("selected");
		allCategoriesButton.text(categoryBlueprint.data("title"));
		categoryWrapper.append(allCategoriesButton);

		categoryBlueprint.removeAttr("data-title");
		for (const name in categories) {
			const slug = greyd.templateLibrary.cleanCategorySlug( name );
			const title = categories[name];
			var newCategory = categoryBlueprint.clone();
			newCategory.text( title );
			newCategory.attr("data-category", slug);
			categoryWrapper.append(newCategory);
		}

		// add events
		$('#template_library .template_category').on('click', function (e) {
			// e.preventDefault();

			$("#template_library .template_category").removeClass("selected");
			$(this).addClass("selected");

			var slug = $(this).data('category');
			var tab  = $(this).closest('.sidebar_top_content').data('tab');
			var body = $('.template_library_body[data-tab='+tab+']');

			body.children('style').remove();
			if (slug != 0) {
				slug = greyd.templateLibrary.cleanCategorySlug(slug)
				body.prepend('<style> .template_library_body[data-tab="'+tab+'"] .template_library_item:not([data-categories*=";'+slug+';"]) { display: none; } </style>');
			}
		});

		// fade in the wrapper
		categoryWrapper.fadeIn(300);

		/**
		 * Render posts
		 */
		for (const id in posts) {

			const post = posts[id];

			if ( !post.download_url || !post.download_url.length ) {
				console.warn(" post '"+post.title+"' does not contain a download URL. It will not be displayed.", post);
				continue;
			}

			// make a clone of the html structure of the blueprint
			var newItem = itemBlueprint.clone();

			// add id
			newItem.attr("data-id", id);

			// add categories
			categories = Object.keys(post.categories).map(greyd.templateLibrary.cleanCategorySlug); // remove '-2' duplicates
			newItem.attr("data-categories", ";"+categories.join(";")+";");

			// add title
			newItem.find(".template_library_item_title").append("<h3>"+post.title+"</h3>");

			if ( (greyd.templateLibrary.mode == "fullsite" 
				|| post.categories.hasOwnProperty("404")
				|| post.categories.hasOwnProperty("archive-templates")
				|| post.categories.hasOwnProperty("search-templates")
				|| post.categories.hasOwnProperty("content-pages") )
				&& !post.version 
			) {
				newItem.find(".template_library_item_version_chip").removeClass("hidden");
				newItem.attr( "data-version", "classic");
			} else if ( greyd.templateLibrary.mode == "fullsite" && post.version === "fse" ) {
				newItem.find(".template_library_item_version_chip").text( "FSE");
				newItem.find(".template_library_item_version_chip").addClass( "green");
				newItem.attr( "data-version", "fse");
				newItem.find(".template_library_item_version_chip").removeClass("hidden");
			} 

			// add thumbnail
			newItem.find(".template_library_item_image img").attr( "src", post.thumbnail_url );

			// add download url
			newItem.find(".import_template").attr( "data-download-url", post.download_url );
			newItem.find(".install_fullsite_template").attr( "data-download-url", post.download_url );

			// add the caption
			if ( post.caption.length ) {
				newItem.find(".template_library_item_description").append("<p>"+post.caption+"</p>");
			}

			// remove user template button
			newItem.find(".delete_user_template").remove();

			// and append it to the container
			container.append(newItem);
		}

		// add events
		$('#template_library .template_details').on('click', function() {
			greyd.templateLibrary.showDetailsSlide( $(this).closest(".template_library_item").data("id") );
		});
		$('#template_library .import_template').on('click', function(e) {
			e.stopPropagation();
			greyd.templateLibrary.installTemplate("partial", $(this).data("download-url"));
		});

		$('#template_library .install_fullsite_template').on('click', function() {
			
			if (greyd.templateLibrary.isClassic && $(this).closest(".template_library_item").data("version") === "classic") {
				greyd.backend.confirm(
					"fullsite_template_import",
					'',
					greyd.templateLibrary.installTemplate,
					[
						"fullsite",
						$(this).data("download-url")
					]
				);
			} else {
				console.warn( greyd.templateLibrary.isClassicMSG );
				greyd.backend.triggerOverlay(true, { "type": "fail", "css": "fullsite_template_import", "replace": greyd.templateLibrary.isClassicMSG });
			}
	
		});
		container.fadeIn(300);
	}

	/**
	 * Display patterns loaded from endpoint
	 */
	this.displayPatterns = async function( posts, categories, wrapper ) {

		const tabNumber = wrapper.data('tab');

		var container               = wrapper.find(".template_library_items");
		var categoryWrapper         = $(".template_library_main_wrapper .overlay_sidebar .sidebar_top_content[data-tab='"+tabNumber+"']");
		const placeholderItems      = container.children();
		const placeholderCategories = categoryWrapper.children(".template_category");
		const itemBlueprint         = placeholderItems.first();
		const categoryBlueprint     = placeholderCategories.first();

		placeholderItems.find(".loading").removeClass("loading");
		placeholderItems.remove();

		const progressBar = $("#template_library .template_library_progress");
		progressBar.addClass('is-loading');

		/**
		 *  Render categories
		 */
		placeholderCategories.removeClass("loading");
		placeholderCategories.remove();

		// all categories-button
		const allCategoriesButton = categoryBlueprint.clone().addClass("selected");
		allCategoriesButton.text(categoryBlueprint.data("title"));
		categoryWrapper.append(allCategoriesButton);

		categoryBlueprint.removeAttr("data-title");

		for (const name in categories) {
			const slug = greyd.templateLibrary.cleanCategorySlug( name );
			const title = categories[name];
			var newCategory = categoryBlueprint.clone();
			newCategory.text( title );
			newCategory.attr("data-category", slug);
			categoryWrapper.append(newCategory);
		}

		// add category event
		$('#template_library .template_category').on('click', function (e) {
			// e.preventDefault();

			$("#template_library .template_category").removeClass("selected");
			$(this).addClass("selected");

			var slug = $(this).data('category');
			var tab  = $(this).closest('.sidebar_top_content').data('tab');
			var body = $('.template_library_body[data-tab='+tab+']');

			body.children('style').remove();
			if (slug != 0) {
				body.prepend('<style> .template_library_body[data-tab="'+tab+'"] .template_library_item:not([data-categories*=";'+slug+';"]) { display: none; } </style>');
			}
		});

		/**
		 * render the preview <div>s
		 */
		for (let i = 0; i < posts.length; i++) {
			const post = posts[i];
			const id = post.ID;
			const newItem = itemBlueprint.clone();

			// add id
			newItem.attr("data-id", id);

			// add categories
			newItem.attr("data-categories", ";"+Object.keys(post.categories).map(greyd.templateLibrary.cleanCategorySlug).join(";")+";");

			// add title
			newItem.find(".template_library_item_title").append("<h3>"+post.title+"</h3>");

			// add pattern as data attribute
			newItem.find(".import_pattern").attr("data-pattern", post.content);

			// create and prepend the iframe preview container and remove the old placeholder image
			newItem.find(".template_library_item_image").prepend("<div class='template_library_item_iframe_preview_container'></div>");
			newItem.find(".template_library_item_image img").remove();

			// append item, so we can attach the iframe to it
			container.append(newItem);

			// count the progress (up to 20% in this loop)
			let w = String( (20 / posts.length) * i);
			progressBar.width(  w+'%');
		}

		/**
		 * set the event here to give the user the opportunity to import patterns
		 */
		$('#template_library .import_pattern').off()
		$('#template_library .import_pattern').on('click', function() {
			greyd.templateLibrary.insertPattern( $(this).data("pattern") );
		});

		/**
		 * render the pattern preview iframes
		 * this takes a lot of time and is therefore handled seperately.
		 */
		for (let i = 0; i < posts.length; i++) {
			const post = posts[i];
			const id = post.ID;

			const previewContainer = container.find(".template_library_item[data-id='"+id+"'] .template_library_item_iframe_preview_container");
			greyd.templateLibrary.renderIframe( post.content, previewContainer );

			await greyd.templateLibrary.delay(1);

			let w = String( 20 + (80 / posts.length) * i);
			progressBar.width(  w+'%');
		}

		// finish it up
		progressBar.width('100%');
		progressBar.removeClass('is-loading');
	}

	/**
	 * Render a pattern iframe asynchronously.
	 * @param {string} content Post content
	 * @param {object} container jQuery object to append the iframe to
	 */
	this.renderIframe = async function ( content, container ) {
		wp.element.render(
			wp.element.createElement( wp.blockEditor.BlockPreview, {
				blocks: wp.blocks.parse( content )
			} ),
			container[0]
		);
	}

	/**
	 * Get user fullsite templates
	 *
	 * This appends the fetched posts to the @var posts
	 * and triggers the @function displayUserTemplates() afterwards.
	 */
	this.fetchUserTemplates = function() {

		var mode = "get_user_templates";

		$.ajax({
			type: "POST",
			url: greyd.ajax_url,
			data: {
				'action': 'greyd_ajax',
				'_ajax_nonce': greyd.nonce,
				'mode': mode,
				'data': {data: null},
			},
			cache: false,
			success: function(response) {

				let posts = [];

				// // successfull
				if ( response.indexOf('success::') > -1 ) {

					var msg = response.split( 'success::' )[1];

					posts = JSON.parse(msg);
					greyd.templateLibrary.displayUserTemplates( posts );
				}
				// complete with error
				else if ( response.indexOf('error::') > -1 ) {

					console.warn(response);

					$('.template_library_body[data-type="user_fullsite"] .template_library_items > .template_library_item').remove();
					$('.template_library_body[data-type="user_fullsite"] .template_library_error').removeClass('hidden');

					// .not('.keep_me') :not(.template_library_error, .template_library_add_new_user_template),
					return false;
				}
				// unknown state
				else {
					greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": response});
				}
			},
		});

	}

	/**
	 * Display all user fullsite templates
	 */
	this.displayUserTemplates = function( posts ) {

		var wrapper = $(".template_library_main_wrapper .template_library_body[data-type='user_fullsite']");
		var container = wrapper.find(".template_library_items");

		var placeholderItems = container.children();

		var addNewButton = container.find(".template_library_add_new_user_template");

		var itemBlueprint = placeholderItems.first();

		if ( posts.length === 0 ) {
			container.hide();
			return;
		} else {
			wrapper.find(".template_library_error").addClass("hidden");
		}

		if ( !placeholderItems.first().hasClass(".template_library_item") ) {
			itemBlueprint = wrapper.find(".template_library_item").first();
			itemBlueprint.find(".loading").removeClass("loading");
		}


		// remove placeholder and loading animation
		placeholderItems.find(".loading").removeClass("loading");
		placeholderItems.not(addNewButton).remove();


		// hide container
		container.hide();

		/**
		 * Render posts
		 */
		for (const template of posts) {

			// make a clone of the html structure of the blueprint
			var newItem = itemBlueprint.clone();

			newItem.find(".template_library_item_title").children().remove();
			newItem.find(".template_library_item_title").append("<h3>"+template.title+"</h3>");

			// add thumbnail
			newItem.find(".template_library_item_image img").attr( "src", template.thumbnail_url );

			newItem.find(".install_fullsite_template").attr( "data-download-url", template.download_url );

			newItem.find(".delete_user_template").attr( "data-slug", template.slug );

			newItem.find(".delete_user_template, .import_template").removeClass('hidden');

			newItem.find(".template_details").remove();

			// add the caption
			if ( template.description.length ) {
				newItem.find(".template_library_item_description").children().remove();
				newItem.find(".template_library_item_description").append("<p>"+template.description+"</p>");
			}

			// and append it to the container
			// container.append(newItem);
			if (addNewButton.length) {
				newItem.insertBefore(addNewButton);
			} else {
				container.append(newItem);
			}
		}

		// add events
		$('#template_library .install_fullsite_template').on('click', function() {
			greyd.backend.confirm(
				"fullsite_template_import",
				'',
				greyd.templateLibrary.installTemplate,
				[
					"fullsite",
					$(this).data("download-url")
				]
			);
		});
		$('#template_library .delete_user_template').on('click', function() {
			greyd.backend.confirm(
				"delete_user_fullsite_template",
				'',
				greyd.templateLibrary.deleteUserTemplate,
				[
					$(this).data("slug")
				]
			);
		});

		container.fadeIn(300);
	}

	 /**
	 * We need this little helper function for the pattern render loop
	 */
	this.delay = ms => new Promise(res => setTimeout(res, ms))


	/**
	 * Display the template details slide
	 * @param {int} postId ID of the post to show details for
	 */
	this.showDetailsSlide = function(postId) {

		greyd.templateLibrary.resetDetailsSlide();

		// set the general state to detail page
		this.setState("details");

		const { posts, mode } = greyd.templateLibrary;
		const post = posts[ postId ];

		// define containers
		var detailContainer = $("#template_library .template_library_detail_wrapper .template_library_details");
		var screenshotContainer = $("#template_library .template_library_detail_wrapper .template_library_details_screenshots");

		// make a blueprint of the screenshot div
		var screenshotBlueprint = '<div class="template_library_details_screenshot"><img src=""></div>';

		// // remove class "loading"
		// detailContainer.children().removeClass("loading");
		// screenshotContainer.children().removeClass("loading");
		// screenshotContainer.removeClass("loading");

		// append template details
		detailContainer.find(".template_library_details_title").append("<h2>"+post.title+"</h2>");
		detailContainer.find(".template_library_details_description").append(post.description);
		detailContainer.find(".template_library_details_buttons a").attr("href", post.preview_url);
		detailContainer.find(".template_library_details_buttons a").attr("target", "blank");

		// load screenshots
		screenshotContainer.children().remove();
		for (var i=0; i < post.screenshots.length; i++) {
			let newScreenshot = screenshotBlueprint.replace( /src="[^"]*?"/, 'src="'+post.screenshots[i]+'"');
			screenshotContainer.append(newScreenshot);
		}

		// manage events
		if (mode == "fullsite") {
			$('.template_library_details .install_fullsite_template').on('click', function() {
				greyd.backend.confirm(
					"fullsite_template_import",
					'',
					greyd.templateLibrary.installTemplate,
					[ "fullsite", post.download_url ]
				);
			});
		}
		else {
			$('.template_library_details .import_template').off().on('click', function() {
				greyd.templateLibrary.installTemplate("partial", post.download_url);
			});
		}
	}

	/**
	 * Reset the template details slide
	 */
	this.resetDetailsSlide = function() {

		// define containers
		var detailContainer = $("#template_library .template_library_detail_wrapper .template_library_details");
		var screenshotContainer = $("#template_library .template_library_detail_wrapper .template_library_details_screenshots");

		// detailContainer.children().addClass("loading");
		// screenshotContainer.children().addClass("loading");
		// screenshotContainer.addClass("loading");

		detailContainer.children().each( function(index, element) {
			$(element).not(".template_library_details_buttons").empty();
		});

		screenshotContainer.children().remove();
		// screenshotContainer.children().each( function(index, element) {
		//     $(element).find("img").attr("src", "");
		// });
	}

	/**
	 * Download & install a template
	 * @param {string} mode (partial|fullsite)
	 * @param {string} downloadURL From where to download the file
	 */
	this.installTemplate = function(mode, downloadURL) {

		mode = "install_" + mode + "_template";
		let cssMode = mode === "install_fullsite_template" ? "fullsite_template_import" : "partial_template_import";

		if ( mode == "install_partial_template" ) {
			greyd.backend.triggerOverlay( true, { "type": "loading", "css": "partial_template_import" })
		}

		var logContainer = $("#overlay .install_log");
		var installDots = '<span class="dots"><span>.</span><span>.</span><span>.</span></span>'

		$.ajax({
			type: "POST",
			url: greyd.ajax_url,
			data: {
				'action': 'greyd_ajax',
				'_ajax_nonce': greyd.nonce,
				'mode': mode,
				'data': {
					"url": downloadURL,
					"blog_id": greyd.templateLibrary.blogID,
					"domain": greyd.templateLibrary.domain
				},
			},
			cache: false,
			complete: function( jqXHR ) {
				// console.log( jqXHR );

				let responseText = jqXHR?.responseText;

				if ( !responseText || typeof responseText !== 'string' || responseText.indexOf('error::') > -1 ) {
					console.warn( responseText, jqXHR );
					greyd.backend.triggerOverlay( true, { "type": "fail", "css": cssMode });
					// greyd.templateLibrary.close();
					return;
				}

				try {
					var data = jQuery.parseJSON(responseText.split("::END::")[1]);
				} catch(e) {
					console.warn(responseText);
					greyd.backend.triggerOverlay( true, { "type": "fail", "css": cssMode });
					// greyd.templateLibrary.close();
					return;
				}

				greyd.backend.overlayTimeout = 6000;
				if ( data.status.indexOf('success::fullsite') > -1 ) {
					greyd.backend.triggerOverlay( true, { "type": "reload", "css": cssMode } );
				} else if (data.status.indexOf('success::partial') > -1 ) {
					greyd.backend.decide( "partial_template_import", greyd.backend.reloadPage, greyd.templateLibrary.redirectToPartial, [], [data.postID] );
				} else if (data.status.indexOf('error::') > -1) {
					greyd.backend.triggerOverlay( true, { "type": "fail", "css": cssMode });
				}
				// greyd.templateLibrary.close();
			},
			beforeSend: function(jqXHR, settings) {
				var self = this;
				var xhr = settings.xhr;

				settings.xhr = function() {
					var output = xhr();
					output.onreadystatechange = function() {
						if (typeof(self.readyStateChanged) == "function") {
							self.readyStateChanged(this);
						}
					};
					return output;
				};
			},
			readyStateChanged: function(jqXHR) {
				// if it's '3', then it's an update,

				if (jqXHR.readyState == 3) {

					var response = jqXHR.responseText;
					var regex = /::(.*?)::/g;
					var installLog = response.match(regex);
					var cleanedLog = [];
					logContainer.empty();

					installLog.forEach(str => {
						str = str.replace( /::/g, "");
						cleanedLog.push(str);
					});

					var message = cleanedLog[cleanedLog.length-1];
					if (message.includes("END")) return;

					logContainer.append("<span>"+message+"</span>"+installDots);
				}
			}

		});
	}

	/**
	 * Open the imported partial template in edit.php
	 * @param {int} postID WP_Post ID
	 */
	this.redirectToPartial = function(postID) {
		var currentURL = $(location).attr('href');
		var baseAdminURL = currentURL.split('wp-admin/')[0];
		var newURL = baseAdminURL + "wp-admin/post.php?post="+postID+"&action=edit";
		window.location.href = newURL;
	}

	/**
	 * Save the setting to hide the welcome screen
	 */
	this.setHideWelcomeScreenSetting = function() {
		var form = $('#template_library_hide_welcome_form');
		var result = form.serializeArray();
		if(result.length > 0 && result[0].value == 'true') {
			$.ajax({
				type: "POST",
				url: greyd.ajax_url,
				data: {
					'action': 'greyd_ajax',
					'_ajax_nonce': greyd.nonce,
					'mode': "set_hide_welcome_screen_setting",
					'data': result[0],
				},
				cache: false,
				success: function(response) {
				},
				// handle an error
				error: function(jqXHR, textStatus, errorThrown) {
					alert(errorThrown);
				},

			});
			// $('#template_library .template_library_welcome').remove();
		}
	}

	/**
	 * Hide active tab and switch to new tab
	 * @param {number|object} tab Tab number or object with tab data
	 */
	this.handleTabSwitch = function(tab) {

		let targetTabNumber = 1;
		if ( typeof tab === 'object' ) {
			if ( tab.data('tab') ) targetTabNumber = tab.data('tab');
		} else {
			targetTabNumber = parseInt(tab);
		}

		greyd.templateLibrary.setState("main");

		const container = $(".template_library_body.active, .sidebar_top_content.active, .template_library_tab.active");
		container.removeClass("active")

		const targetContainer = $(".template_library_body[data-tab='"+targetTabNumber+"'], .sidebar_top_content[data-tab='"+targetTabNumber+"'], .template_library_tab[data-tab='"+targetTabNumber+"']");
		targetContainer.addClass("active");

		greyd.templateLibrary.fetchTemplates();
	}

	/**
	 * Add the Greyd Template Library button to the editor navbar
	 */
	this.addLibraryButtonToEditor = ( function(window, wp) {

		if (
			( jQuery('body').hasClass('post-type-page') || jQuery('body').hasClass('post-type-dynamic_template') ) &&
			jQuery('#template_library').length > 0
		) {

			// just to keep it cleaner - we refer to our link by id for speed of lookup on DOM.
			var linkID = 'greyd_pattern_library';
			var buttonName = 'Greyd Pattern Library';

			// prepare our custom link's html.
			var linkHTML = '<a id="' + linkID + '" onclick="greyd.templateLibrary.open();" class="components-button template_library_main_button" href="#" >'+buttonName+'</a>';

			// check if gutenberg's editor root element is present.
			var editorEl = document.getElementById( 'editor' );
			if( !editorEl ){ // do nothing if there's no gutenberg root element on page.
				return;
			}

			var unsubscribe = wp.data.subscribe( function () {
				setTimeout( function () {
					if ( !document.getElementById( linkID ) ) {
						var toolbalEl = editorEl.querySelector( '.edit-post-header__toolbar' );
						if( toolbalEl instanceof HTMLElement ){
							toolbalEl.insertAdjacentHTML( 'beforeend', linkHTML );
						}
					}
				}, 1 )
			} );
		}

	} )(window, wp, jQuery)

	/**
	 * Insert a pattern into the editor.
	 * @param {string} pattern content of the pattern
	 */
	this.insertPattern = function(pattern) {
		greyd.backend.triggerOverlay( true, { "type": "loading", "css": "pattern_import" }) ;
		var newBlocks = wp.blocks.parse(pattern);
		wp.data.dispatch('core/block-editor').insertBlocks(newBlocks);
		greyd.templateLibrary.close();
		greyd.backend.triggerOverlay( true, { "type": "success", "css": "pattern_import" }) ;
	}

	/**
	 * Show the dialog window to create a user template
	 * @param {object} data
	 */
	this.showUserTemplateForm = function(data) {

		const mode = "create_user_fullsite_template";

		// set ID & domain
		if ( data.id && data.siteurl) {
			greyd.templateLibrary.blogID = data.id;
			greyd.templateLibrary.domain = data.siteurl;
		}

		const submitButton = $('span[role="confirm"].create_user_fullsite_template');

		greyd.backend.confirm( mode, '', greyd.templateLibrary.createUserTemplate, []);

		$(".create_user_template_homepage_link").attr("href", greyd.templateLibrary.domain);

		// is_multisite() --> select the current blog
		if ( $("#template_library").css("display") == "block" )  {
			$('#create_user_template_form label[for="blogs"]').show();
			$('#create_user_template_form select[name="blogs"]').show();
			$('#create_user_template_form select[name="blogs"]').val(greyd.templateLibrary.blogID);

			$('#create_user_template_form select[name="blogs"]').on('input', function() {
				greyd.templateLibrary.blogID = $(this).val();
				$(".create_user_template_homepage_link").attr("href", "https://" + $(this).find("option:selected").text() );
			});
		}
		// ! is_multisite() --> hide the select
		else {
			$('#create_user_template_form label[for="blogs"]').hide();
			$('#create_user_template_form select[name="blogs"]').hide();
			$('#create_user_template_form label[for="blogs"]').next().hide();
		}

		// event for the thumbnail upload
		$('#fullsite_template_thumbnail').on('click', greyd.templateLibrary.uploadThumbnail);

		// set input values
		$('#create_user_template_form input#title').val( data.name ? data.name : "" );
		$('#create_user_template_form textarea#description').val( data.description ? data.description : "" );
		$(".create_user_fulsite_template_filename").remove();

		// disable submit button as long as the title field is empty
		$('#create_user_template_form input[name="title"]').on('input', function() {
			if ( $(this).val().length > 0 ) {
				submitButton.removeAttr("disabled");
				submitButton.css("pointer-events", "");
			} else {
				submitButton.attr("disabled", "true");
				submitButton.css("pointer-events", "none");
			}
		})
	}

	/**
	 * Create a user fullsite template
	 */
	this.createUserTemplate = function() {

		var mode        = 'create_user_fullsite_template';

		var form        = $('#create_user_template_form');
		var form_data   = form.serializeArray().reduce(function(obj, item) {
			obj[item.name] = item.value;
			return obj;
		}, {} );


		var data = {
			'filename': greyd.templateLibrary.thumbnailFileName,
			'meta': form_data,
			'blog_id': greyd.templateLibrary.blogID,
			'domain': greyd.templateLibrary.domain
		};

		$.post(
			greyd.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': greyd.nonce,
				'mode': mode,
				'data': data
			},
			function(response) {

				// // successfull
				if ( response.indexOf('success::') > -1 ) {

					greyd.backend.triggerOverlay( true, { "type": "success", "css": mode } );

					greyd.templateLibrary.fetchUserTemplates();

				}
				// file exists already
				else if ( response.indexOf('error::existing_template') > -1 ) {
					greyd.backend.decide( mode+"_exists", greyd.backend.fadeOutOverlay , greyd.templateLibrary.showUserTemplateForm, [1], [data]);
				}
				// complete with error
				else if ( response.indexOf('error::') > -1 ) {
					var msg = response.split( 'error::' )[1];
					greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": msg });
				}
				// unknown state
				else {
					greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": response});
				}
			}
		);
	}

	/**
	 * Delete a user fullsite template
	 */
	this.deleteUserTemplate = function(templateSlug) {
		var mode = 'delete_user_fullsite_template';

		var data = {
			'template': templateSlug,
		};
		console.log(data);
		$.post(
			greyd.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': greyd.nonce,
				'mode': mode,
				'data': data
			},
			function(response) {
				console.log(response);

				// // successfull
				if ( response.indexOf('success::') > -1 ) {
					greyd.backend.triggerOverlay( true, { "type": "success", "css": mode } );

					greyd.templateLibrary.fetchUserTemplates();
				}
				// complete with error
				else if ( response.indexOf('error::') > -1 ) {
					var msg = response.split( 'error::' )[1];
					greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": msg });
				}
				// unknown state
				else {
					greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": response});
				}
			}
		);
	}

	/**
	 * Upload a thumbnail for a user fullsite template
	 */
	this.uploadThumbnail = function() {

		var mode = 'upload_fullsite_template_thumbnail';
		var form = $('#create_user_template_form');
		var fileInput = form.find('input[type="file"]');

		var loader = '<span class="loader tiny"></span>';

		const uploadButton = form.find("label[for='fullsite_template_thumbnail']");
		const buttonText = uploadButton.find(".fullsite_template_thumbnail_button_text");
		const buttonIcon = uploadButton.find(".dashicons-arrow-up-alt");

		const submitButton = $('span[role="confirm"].create_user_fullsite_template');

		// reset the fileinput
		fileInput.off();
		fileInput.val('');

		fileInput.on('change', function (e) {

			if ( $(this).val() ) {

				// create formData
				var data = new FormData();
				data.append('action', 'greyd_ajax');
				data.append('_ajax_nonce', greyd.nonce);
				data.append('mode', mode);

				// append file data
				var fileData = fileInput[0].files[0];
				data.append('data', fileData);

				$.ajax({
					'type': 'POST',
					'url': greyd.ajax_url,
					'processData': false,
					'contentType': false,
					'cache': false,
					'data': data,
					beforeSend: function() {
						$('.create_user_fulsite_template_filename').remove();
						uploadButton.append(loader);
						buttonText.hide();
						buttonIcon.hide();
						uploadButton.addClass("loading");

						submitButton.attr('disabled', "true");
						submitButton.css("pointer-events", "none");
					},
					complete: function() {
						uploadButton.removeClass("loading");

						if ($('#create_user_template_form input[name="title"]').val().length > 0 ) {
							submitButton.removeAttr('disabled');
							submitButton.css("pointer-events", "");
						}
					},
					success: function(response) {

						// successfull
						if ( response.indexOf('success::') > -1 ) {

							$("<span class='create_user_fulsite_template_filename dashicons-before dashicons-saved'>"+ fileData.name+"</span>").insertBefore(uploadButton);
							buttonText.show();
							buttonIcon.show();
							uploadButton.find(".loader").remove();

							var msg = response.split( 'success::' )[1];
							greyd.templateLibrary.thumbnailFileName = msg;
						}
						// complete with error
						else if ( response.indexOf('error::') > -1 ) {
							var msg = response.split( 'error::' )[1];
							greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": msg });
						}
						// unknown state
						else {
							greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": response});
						}
					}
				});
			}
		});
	}

	this.cleanCategorySlug = function(slug) {
		return String(slug).replace(/-\d$/, "");
	}
}
