/**
 * Greyd.Hub Backend Script.
 *
 * @since 1.3.4
 */
(function() {

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		greyd.hub.init();

		console.log('Hub Scripts: loaded');
	} );

} )(jQuery);

greyd.hub = new function() {

	var { __ } = wp.i18n;

	this.tables = [];

	/**
	 * Initialize Events
	 */
	this.init = function() {

		// trigger overlay if var 'trigger_log_overlay' is set
		if (typeof trigger_log_overlay !== "undefined") {
			console.log(trigger_log_overlay);
			greyd.backend.triggerOverlay(trigger_log_overlay[0], trigger_log_overlay[1]);
		}

		// trigger overlay if var 'trigger_overlay' is set
		if (typeof trigger_overlay !== "undefined") {
			console.log(trigger_overlay);
			greyd.backend.triggerOverlay(trigger_overlay[0], trigger_overlay[1]);
		}

		// fade out normal overlay on start
		var overlay = $(".greyd_overlay#overlay");
		if (overlay.length !== 0) {
			if (!overlay.hasClass("hidden")) {
				console.log("try to fade out normal overlay on start");
				if (overlay.children(":not(.hidden)").hasClass("success")) {
					// greyd.backend.fadeOutOverlay();
				}
				else if (overlay.children(":not(.hidden)").hasClass("reload")) {
					greyd.backend.reloadPage();
				}
				else {
					// greyd.backend.fadeOutOverlay(3000);
				}
			}
		}

		$('#init-loader').remove();

		// init button trigger
		$("[data-event=button]").on('click', function() {
			var args = $(this).data('args');
			// console.log(args);
			// website tiles
			if (typeof args === 'undefined') {
				greyd.hub.clickButton(this);
			}
			else if (args == 'advanced') {
				greyd.hub.clickButtonAdvanced(this);
			}
			else if (args == 'admin') {
				greyd.hub.clickButtonAdmin(this);
			}
			// backups
			else if (args == 'full') {
				greyd.hub.clickNextLoading(this, "export_full");
			}
			else if (args == 'trash') {
				greyd.hub.confirmTrash(this);
			}
			// database
			else if (args == 'tables-import') {
				greyd.hub.importTables(this);
			}
			else if (args == 'tables-toggle') {
				greyd.hub.clickToggleCheck(this);
			}
			else if (args == 'tables') {
				greyd.hub.clickButtonTables(this);
			}
			// staging
			else if (args == 'staging') {
				greyd.hub.clickButtonStaging(this);
			}
		});

		// new navbar
		this.initHubNavbar();

		// wizard
		this.initHubWizard();
		this.initNewSiteWizard();

		// staging
		this.initStaging();

		// en- & disable buttons when checkboxes are clicked
		$(".hub_tables input").on("change", function() {
			greyd.hub.enableButtons();
		});

		// change website infos inline form
		this.initChangeBloginfo();

		// set timeout for admin refresh-notice
		this.initRefreshNotice(2);
	}

	/**
	 * Show Notice to refresh the site after some time.
	 * @param {int} minutes
	 */
	this.initRefreshNotice = function(minutes=2) {
		clearTimeout(this.refresh_timeout);
		this.refresh_timeout = setTimeout(function() {
			$('#greyd_hub_refresh').slideDown();
			$('#greyd_hub_refresh .notice-dismiss').on("click", function(){
				$('#greyd_hub_refresh').remove();
			});
		}, minutes * 60 * 1000);
	}
	this.refresh_timeout;

	/*
	=======================================================================
		Hub Websites Tab
	=======================================================================
	*/

	/**
	 * Hub Navbar
	 */
	this.initHubNavbar = function() {

		const wrap = $(".hub-nav-wrap");

		if ( wrap.length === 0 ) return;

		// toggle fixed navbar
		const toggleFixedNav = () => {
			const top = wrap[0].getBoundingClientRect().top;
			const hasClass = wrap.hasClass('fixed');
			if ( top < 33 && !hasClass ) {
				wrap.addClass('fixed');
			}
			if ( top > 34 && hasClass ) {
				wrap.removeClass('fixed');
			}
		}

		// anchor navigation
		let switchActiveAnchor = () => {}
		if ( $(".hub-nav-wrap .block_tabs").length ) {

			const anchorTabs = $(".hub-nav-wrap .hub-nav .block_tabs a[href^='#']");
			let anchorElements = [];
			$.each( anchorTabs, function() {
				const anchor = $(this).attr("href");
				anchorElements.unshift( $(anchor) );
			} );

			const getAnchorInView = () => {
				for ( let element of anchorElements ) {
					if ( ! element.length ) continue;

					if ( element[0].getBoundingClientRect().top < (window.innerHeight / 4)  ) {
						return "#"+element.attr("id");
					}
				}
				return "#";
			}

			// switch active anchor
			let currentAnchorInView = "#";
			switchActiveAnchor = () => {
				const anchor = getAnchorInView();
				if ( anchor != currentAnchorInView ) {
					currentAnchorInView = anchor;
					$(".hub-nav-wrap .hub-nav .block_tabs a.active").removeClass("active");
					$(`.hub-nav-wrap .hub-nav .block_tabs a[href='${anchor}']`).addClass("active");
				}
			}

			// smooth anchor scrolling
			$(".hub-nav-wrap .block_tabs a[href^='#']").on('click',function (e) {
				e.preventDefault();
				const anchor = this.hash;
				target = $(anchor);
				$('html, body').stop().animate({
					'scrollTop':  target.length ? (target.offset().top - 200) : 0
				}, 250, 'swing', function () {
					if (history.pushState) {
						history.pushState(null, null, anchor);
					} 
					else {
						location.hash = anchor;
					}
				});
			});
		}

		// on scroll functions
		$(window).on('scroll', function() {
			toggleFixedNav();
			switchActiveAnchor();
		});
		toggleFixedNav();
		switchActiveAnchor();

		// switch layout
		$("#layout-tiles, #layout-list").on('change', function() {
			greyd.hub.switchLayoutMode(this);
		});

		// toggle thumbs
		$("#thumbs-toggle").on('change', function() {
			greyd.hub.toggleThumbsMode(this);
		});

		// Switch tabs
		$("#tabs-action, #tabs-info, #tabs-admin").on('change', function() {
			greyd.hub.switchTabsGlobal(this);
		});
		$(".greyd_tile .tab").on('click', function() {
			greyd.hub.switchTabsInner(this);
		});

		// filter sites
		$("#filter-sites").on("keyup search", function() {
			greyd.hub.searchSites(this);
		});

		// set layoutMode
		const layoutMode = this.getLocal('hub_layout') ?? 'tiles';
		console.log("initial layout: "+layoutMode);
		if ( layoutMode ) {
			$('.hub-nav input[name="layout"]#layout-'+layoutMode).prop('checked', 'checked');
			$('.hub-nav input[name="layout"]#layout-'+layoutMode).trigger("change");
		}

		// set innerTab
		const innerTab = this.getLocal('hub_inner_tab') ?? 'action';
		console.log("initial innerTab: "+innerTab);
		if ( innerTab ) {
			$('.hub-nav input[name="tabs"]#tabs-'+innerTab).prop('checked', 'checked');
			$('.hub-nav input[name="tabs"]#tabs-'+innerTab).trigger("change");
		}

		// loaded
		wrap.removeClass("is-loading");

		// prevent submit of nav-bar form - only live actions
		wrap.on('submit', function(e) {
			e.preventDefault();
		});

		// set thumbsMode
		// this is handled last, because it takes the most time
		const thumbsMode = this.getLocal('hub_thumbs');
		// console.log("initial thumbs: "+thumbsMode);
		if ( thumbsMode && thumbsMode == "1") {
			$('#thumbs-toggle').prop('checked', 'checked');
			$('#thumbs-toggle').trigger("change");
		}

	}

	/**
	 * Filter sites via search.
	 * @param {object} el DOM Element
	 */
	this.searchSites = function(el) {

		const value = String(el.value).toLowerCase();
		const label = $(el).parent("label");

		if ( value.length ) {
			label.addClass("has-value");
		} 
		else {
			label.removeClass("has-value");
		}

		// filter tiles
		$(".hub_panel .greyd_tile[data-name]").each(function() {
			const data      = $(this).data();
			const name      = data.name.toLowerCase();
			const domain    = data.domain.toLowerCase();
			if ( name.indexOf(value) === -1 && domain.indexOf(value) === -1 ) {
				$(this).addClass("hidden");
			}
			else if ( $(this).hasClass("hidden") ) {
				$(this).removeClass("hidden");
			}
		});
	}

	/**
	 * Toggle website previews.
	 * @param {object} el event target
	 */
	this.toggleThumbsMode = function(el) {
		// get all websistes
		var sites = [];
		$(".hub_panel .greyd_tile").each(function() {
			sites.push({
				'element': this,
				'blogid': $(this).data('id'),
				'domain': $(this).data('domain').toLowerCase(),
				'title': $(this).data('name').toString().toLowerCase()
			});
		});
		if (sites.length > 0) {
			if ($(el).prop('checked')) {
				// show website thumbs
				for (var i=0; i<sites.length; i++) {
					var siteurl = $(sites[i].element).find('.hub_frame').data('siteurl');
					$(sites[i].element).find('.hub_frame iframe').attr( 'src', siteurl+'?hide_admin_bar=true' );
					$(sites[i].element).find('.hub_frame iframe').attr('loading', 'lazy');
					if ( greyd.hub.getLocal('hub_layout') == "list" ) {
						$(sites[i].element).find('.hub_frame_wrapper').show().addClass("loading");
					} 
					else {
						$(sites[i].element).find('.hub_frame_wrapper').slideDown().addClass("loading");
					}
				}
				this.delLocal('hub_thumbs');
				this.setLocal('hub_thumbs', "1");
			}
			else {
				// hide website thumbs
				for (var i=0; i<sites.length; i++) {
					if ( greyd.hub.getLocal('hub_layout') == "list" ) {
						$(sites[i].element).find('.hub_frame_wrapper').hide().removeClass("loading");
					} 
					else {
						$(sites[i].element).find('.hub_frame_wrapper').slideUp().removeClass("loading");
					}
					$(sites[i].element).find('.hub_frame iframe').attr('src', '');
				}
				this.delLocal('hub_thumbs');
				this.setLocal('hub_thumbs', "0");
			}
		}
	}

	/**
	 * Switch the layout mode.
	 * @param {object} el event target
	 */
	this.switchLayoutMode = function(el) {
		const mode = $(el).val();
		if ( mode == "list" ) {
			$(".hub_panel").addClass( "layout-mode-list" );
		} 
		else {
			$(".hub_panel").removeClass( "layout-mode-list" );
		}
		this.delLocal('hub_layout');
		this.setLocal('hub_layout', mode);
	}

	/**
	 * Switch tabs globally from the navbar.
	 * @param {object} el event target
	 */
	this.switchTabsGlobal = function(el) {
		const tab = $(el).val();
		const innerTabs = $('.greyd_tile .tab_list .tab[data-show="'+tab+'"]');

		innerTabs.each(function() {
			greyd.hub.switchTabsInner( this );
		});

		this.delLocal('hub_inner_tab');
		this.setLocal('hub_inner_tab', tab);
	}

	/**
	 * Switch tabs individually from the tiles.
	 * @param {object} el event target
	 */
	this.switchTabsInner = function(el) {
		if ( $(el).hasClass("active")) return;

		$(el).siblings(".active").removeClass("active");
		$(el).addClass("active");
		const content = $(el).closest(".tab_list").siblings('.tab_view[data-tab="'+$(el).data("show")+'"]');
		//console.log(content);
		content.siblings().removeClass("active");
		content.addClass("active");
	}

	/**
	 * Local storage handler.
	 */
	this.setLocal = function(name, value) {
		var tmp = localStorage.getItem(name);
		if (!tmp)
			localStorage.setItem(name, JSON.stringify(value));
	}
	this.getLocal = function(name) {
		return tmp = JSON.parse(localStorage.getItem(name));
	}
	this.delLocal = function(name) {
		localStorage.removeItem(name);
	}

	/**
	 * Change blog info inside the tiles.
	 */
	this.initChangeBloginfo = function() {
		// var form    = "form.change_bloginfo2";
		// var input   = form+" .input";

		var form    = "form.change_bloginfo, form.change_bloginfo2";
		var input   = "form.change_bloginfo .input, form.change_bloginfo2 .input";
		var buttons = "form.change_bloginfo .buttons, form.change_bloginfo2 .buttons";
		var select  = "form.change_bloginfo2 select.input";
		
		// on select change
		$(select).on( "change", function(e) {
			// change fontweight
			var $select = $(this);
			if ($(this).val() == '0')
				$select.css('fontWeight', 'normal');
			else
				$select.css('fontWeight', '600');
			// toggle pw input
			var $input = $(this).parent().children("[name=pw]");
			if ($(this).val() == '3')
				$input.css('display', 'block');
			else
				$input.css('display', 'none');
		});

		// on input focus
		$(input+', '+buttons).on( "focusin", function(e) {
			var $form = $(this).parent();
			$form.removeClass("loading success failed");
			setTimeout(function(){
				$form.addClass("focus");
			}, 1);
		});
		// on input blur
		$(input+', '+buttons).on( "blur", function(e){
			var $form = $(this).parent();
			setTimeout(function(){
				$form.removeClass("focus");
			}, 0);
		});

		// on key down
		$(input).on( "keydown", function (e) {
			// submit on enter
			if (e.which == 13) {
				e.preventDefault();
				$(this).parent().trigger("submit");
			}
			// blur on escape
			else if (e.which == 27) {
				$(this).trigger('blur');
			}
		});

		// on form reset
		$(form).on( "reset", function(e){
			e.preventDefault();
			var $form = $(this);
			var $input = $form.children(".input");
			$input.each(function() {
				$(this).val( $.trim( $(this).data('reset') ) );
				$(this).change();
				if ($form.children(".preview"))
					$form.children(".preview").text( $(this).val() );
			})
			$(input).trigger('blur');
		});

		// on form submit
		$(form).on( "submit", function(e){
			e.preventDefault();

			var $form   = $(this);
			var $input  = $form.children(".input");
			var $preview = $form.children(".preview")
			var name    = $input.attr('name');
			var value   = String($input.val()).trim();
			var reset   = String($input.data('reset')).trim();
			var blogid  = $form.closest(".greyd_tile").data('id');

			// add pw to protected value
			if ( name === "protected" && value == '3' ) {
				var pass = $(this).children("[name=pw]");
				value += '::'+pass.val();
			}

			// unchanged
			if ( value == reset ) {
				$input.trigger('blur');
				return;
			}

			// maxlengths
			if ( name === "blogname" ) {
				if ( 40 < value.length ) value = value.substring( 0, 40 );
			}
			else if ( name === "blogdescription" ) {
				if ( 255 < value.length ) value = value.substring( 0, 254 ) + '\u2026';
			}

			$form.removeClass("focus").addClass("loading");
			$preview.text(value);
			$input.trigger("blur");

			// greyd_hub compatibility
			var ajax = {};
			if (typeof wizzard_details !== 'undefined')
				ajax = { ajax_url: wizzard_details.ajax_url, nonce: wizzard_details.nonce, action: "greyd_ajax" };
			else if (typeof greyd !== 'undefined')
				ajax = { ajax_url: greyd.ajax_url, nonce: greyd.nonce, action: "greyd_admin_ajax" };

			$.post(
				ajax.ajax_url, {
					'action': ajax.action,
					'_ajax_nonce': ajax.nonce,
					'mode': 'change_bloginfo',
					'data': {
						'name': name,
						'value': value,
						'blogid': blogid
					}
				},
				function(response) {
					greyd.wizard.alert_on_leave = false;
					$('.wizard .wizard_head .close_wizard').show();

					if (response.indexOf('error::') > -1) {
						console.warn( response );
						var cls = 'failed';
						$form.trigger('reset');
					}
					else {
						console.info( response );
						var cls  = 'success';

						if ( name === "protected" && value.indexOf('3::') === 0 ) {
							$($input[0]).data('reset', '3');
						}
						else $input.data('reset', value);
						if ( name === 'blogname' && ajax.is_multisite === 'false' ) {
							var $siteName = $( '#wp-admin-bar-site-name' ).children( 'a' ).first();
							$siteName.text(value);
						}
					}

					$form.removeClass("loading").addClass(cls);
					setTimeout(function(){
						$form.removeClass(cls);
					}, 2000);
				}
			);
		});
	}

	/*
	=======================================================================
		Website Tiles
	=======================================================================
	*/

	// simple (mods)
	this.clickButton = function(el) {
		var id = $(el).attr('id');
		var data_holder = $(el).parent().children('.data-holder');
		if ( data_holder.length == 0 ) {
			data_holder = $(el).parent().parent().children('.data-holder');
		}
		// console.log( 'Button clicked: '+id );

		if (id == 'btn_export_mods') {
			try {
				var domain = data_holder.data('domain');
				var dataString = data_holder.data('mods');
				var filename = data_holder.data('name');

				// console.log(dataString);
				var pom = document.createElement('a');
				pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + dataString);
				pom.setAttribute('download', filename+'.data');

				if (document.createEvent) {
					var event = document.createEvent('MouseEvents');
					event.initEvent('click', true, true);
					pom.dispatchEvent(event);
				}
				else {
					pom.click();
				}
				greyd.backend.triggerOverlay(true, {type:'success', css:'export_mods'});
			}
			catch(err) {
				// alert(domain+":\n\nUnable to export Thememods!");
				greyd.backend.triggerOverlay(true, {type:'fail', css:'export_mods'});
			}
		}
		else if (id == 'btn_select_mods') {
			if ($(el).siblings('.select').length > 0) {
				$(el).siblings('.select').remove();
				$(el).removeClass('active');
				return;
			}
			$(el).addClass('active');

			var siteurl = data_holder.data('siteurl');
			var domain = data_holder.data('domain');
			var blogid = data_holder.data('id');

			// options
			var mods = data_holder.data('mods');
			var all_mods = [];
			var all_options = "<option value=''>...</option>";
			all_options += "<option value='up'>" + __('Upload file', 'greyd_hub' ) + "</option>";
			var i = 0;
			$(".data-holder[data-mods]").each(function() {
				var dataString = $(this).data('mods');
				var dataDomain = $(this).data('domain');
				if (dataString !== mods) {
					var data = JSON.parse(decodeURIComponent(dataString));
					all_mods.push(data);
					all_options += "<option value='"+i+"'>"+dataDomain+"</option>";
					i++;
				}
			});

			// make select
			var select = $("<div class='select'>"+
							"<select style='width: calc(100% - 50px); margin-right: 7px'>"+all_options+"</select>"+
							"<span class='button button-ghost'><span class='dashicons dashicons-no-alt'></span></span>"+
						"</div>");

			// events
			select.find('select').on('change', function() {
				// console.log('change');
				// console.log($(this).val());
				if ($(this).val() == 'up') {
					// console.log('trigger file upload');
					var pom = document.createElement('input');
					pom.setAttribute('type', 'file');
					pom.setAttribute('accept', '.data');
					pom.setAttribute('onchange', 'greyd.hub.readFile(this, "'+siteurl+'", "'+blogid+'")' );
					if (document.createEvent) {
						var event = document.createEvent('MouseEvents');
						event.initEvent('click', true, true);
						pom.dispatchEvent(event);
					}
					else {
						pom.click();
					}
				}
				else {
					// console.log("trigger");
					var index = parseInt($(this).val());
					if (typeof all_mods[index] !== 'undefined') {
						greyd.backend.confirm("import_mods", domain, greyd.hub.importMods, [blogid, all_mods[index], siteurl]);
					}
				}
			});
			select.find('.button').on('click', function() {
				select.remove();
				$(el).removeClass('active');
			});
			// add
			data_holder.append(select);
		}
		else if (id == 'btn_import_mods' ||
				id == 'btn_reset_mods' ||
				id == 'btn_activate_mods' ||
				id == 'btn_delete_mods') {
			var siteurl = data_holder.data('siteurl');
			var domain = data_holder.data('domain');
			var blogid = data_holder.data('id');
			var inputid = 'edit_mods_data_'+blogid;
			var submitid = 'submit_edit_mods_'+blogid;

			if (id == 'btn_reset_mods') {
				greyd.backend.confirm("reset_mods", domain, greyd.hub.resetMods, [blogid, siteurl]);
			}
			else if (id == 'btn_import_mods') {
				var pom = document.createElement('input');
				pom.setAttribute('type', 'file');
				pom.setAttribute('accept', '.data');
				pom.setAttribute('onchange', 'greyd.hub.readFile(this, "'+siteurl+'", "'+blogid+'")' );
				if (document.createEvent) {
					var event = document.createEvent('MouseEvents');
					event.initEvent('click', true, true);
					pom.dispatchEvent(event);
				}
				else {
					pom.click();
				}
			}

		}

	}
	this.resetMods = function(blogid, siteurl) {
		var raw_mods = $('#raw_mods').data('mods');
		$("[data-siteurl='"+siteurl+"'] #edit_mods_data_"+blogid).val(encodeURIComponent(JSON.stringify(raw_mods, undefined, 4)));
		$("[data-siteurl='"+siteurl+"'] #submit_edit_mods_"+blogid).trigger("click");
	}
	this.readFile = function(input, new_siteurl, new_blog_id) {
		var tmp = new_siteurl.split('://');
		var domain = tmp[1];
		var file = input.files[0];
		if (file) {
			var reader = new FileReader();
			reader.readAsText(file, "UTF-8");
			reader.onload = function(evt) {
				// console.log(evt.target.result);
				try {
					var data = JSON.parse(evt.target.result);
					if (data['mods'] == undefined || data['version'] == undefined) {
						console.log(data);
						greyd.backend.triggerOverlay(true, {"type":'fail', "css":'import_mods'});
						return;
					}
				}
				catch(err) {
					console.log("error reading file");
					console.log(evt.target.result);
					greyd.backend.triggerOverlay(true, {"type":'fail', "css":'import_mods'});
					return;
				}
				greyd.backend.confirm("import_mods", domain, greyd.hub.importMods, [new_blog_id, data, new_siteurl]);
			}
			reader.onerror = function(evt) {
				greyd.backend.triggerOverlay(true, {"type":'fail', "css":'import_mods'});
			}
		}
	}
	this.importMods = function(blogid, data, siteurl) {
		var inputdata = greyd.hub.readData(data, siteurl, blogid);
		if (inputdata) {
			$("[data-siteurl='"+siteurl+"'] #edit_mods_data_"+blogid).val(inputdata);
			$("[data-siteurl='"+siteurl+"'] #submit_edit_mods_"+blogid).trigger("click");
		}
		else greyd.backend.triggerOverlay(true, {"type":'fail', "css":'import_mods'});
	}
	this.readData = function(data, new_siteurl, new_blog_id) {
		if (!data['mods']) return false;

		var old_blog_id = data['blog_id'];
		var old_siteurl = data['siteurl'].split('://');
		var old_http = old_siteurl[0];
		var old_domain = old_siteurl[1];

		var tmp = new_siteurl.split('://');
		var new_http = tmp[0];
		var new_domain = tmp[1];

		var old_url = old_http+":\/\/"+old_domain;
		var new_url = new_http+":\/\/"+new_domain;

		var old_uploads = old_url+"\/"+"wp-content\/uploads";
		var new_uploads = new_url+"\/"+"wp-content\/uploads";
		if (typeof greyd.upload_url !== 'undefined') {
			// console.log(greyd.upload_url);
			new_uploads = greyd.upload_url;
		}
		if (old_blog_id != "1") old_uploads += "\/sites\/"+old_blog_id;
		if (new_blog_id != "1") new_uploads += "\/sites\/"+new_blog_id;

		var dataMods = data['mods'];
		var datakeys = Object.keys(dataMods);
		for (var i=0; i<datakeys.length; i++) {
			var k = datakeys[i];
			var v = dataMods[k];
			// console.log(k+": "+v);
			if (typeof v == "string") {
				if (v.indexOf(old_uploads) > -1)
					data['mods'][k] = data['mods'][k].split(old_uploads).join(new_uploads);
				if (v.indexOf(old_url) > -1)
					data['mods'][k] = data['mods'][k].split(old_url).join(new_url);

				if (v.indexOf("'") > -1)
					data['mods'][k] = data['mods'][k].split("'").join("\"");
				if (v.indexOf("+") > -1)
					data['mods'][k] = data['mods'][k].split("+").join(" ");
			}
		}
		// console.log(data);

		// var inputdata = encodeURIComponent(JSON.stringify(data['mods']));
		var inputdata = encodeURIComponent(JSON.stringify(data['mods'], undefined, 4)); //nice
		return inputdata;
	}

	// advanced (media & database)
	this.clickButtonAdvanced = function(el) {
		var id = $(el).attr('id');
		var data_holder = $(el).closest('.data-holder');
		// console.log( 'Button clicked: '+id );

		if (id == 'btn_export_files' ||
			id == 'btn_import_files' ||
			id == 'btn_export_plugins' ||
			id == 'btn_export_plugins_internal' ||
			id == 'btn_import_plugins' ||
			id == 'btn_export_themes' ||
			id == 'btn_import_themes' ||
			id == 'btn_export_db' ||
			id == 'btn_import_db' ||
			id == 'btn_export_content' ||
			id == 'btn_import_content' ||
			id == 'btn_export_site' ||
			id == 'btn_import_site' ||
			id == 'btn_export_styles' ||
			id == 'btn_import_styles' ||
			id == 'btn_clean_site' ||
			id == 'btn_clean_content' ||
			// id == 'btn_create_user_template' ||
			id == 'btn_clean_db') {

			var blogid = data_holder.data('id');
			var domain = data_holder.data('domain');
			var name = 'edit_'+id.split('_')[2]+'_';
			// var name = id.indexOf('_files') > -1 ? 'edit_files_' : 'edit_db_';

			// export
			if (id.indexOf('_export_') > -1) {
				var exportid = name+'export_'+blogid;
				var submitid = 'submit_'+name+blogid;
				if (id == 'btn_export_files') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_files'});
				}
				else if (id == 'btn_export_plugins') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_plugins'});
				}
				else if (id == 'btn_export_plugins_internal') {
					exportid = name+'export_internal_'+blogid;
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_plugins'});
				}
				else if (id == 'btn_export_themes') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_themes'});
				}
				else if (id == 'btn_export_db') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_db'});
				}
				else if (id == 'btn_export_content') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_content'});
				}
				else if (id == 'btn_export_site') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_site'});
				}
				else if (id == 'btn_export_styles') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_mods'});
				}
				// console.log($("#"+exportid));
				// console.log($("#"+submitid));
				$("[data-domain='"+domain+"'] #"+exportid).val("yes");
				$("[data-domain='"+domain+"'] #"+submitid).trigger("click");
			}
			// clean export
			else if (id.indexOf('_clean_') > -1) {
				var exportid = name+'clean_'+blogid;
				var submitid = 'submit_edit_clean_'+blogid;
				if (id == 'btn_clean_db') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_db'});
				}
				else if (id == 'btn_clean_content') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_content'});
				}
				else if (id == 'btn_clean_site') {
					greyd.backend.triggerOverlay(true, {"type":'loading', "css":'export_site'});
				}
				// console.log(id);
				// console.log(exportid);
				// console.log(submitid);
				$("[data-domain='"+domain+"'] #"+exportid).val("yes");
				$("[data-domain='"+domain+"'] #"+submitid).trigger("click");
			}

			// import
			else {
				// console.log( 'Button clicked: '+id );
				// var domain = data_holder.data('domain');
				var fileid = name+'file_'+blogid;
				var importid = name+'import_'+blogid;
				$("[data-domain='"+domain+"'] #"+fileid).val("");
				$("[data-domain='"+domain+"'] #"+importid).val("yes");
				if (id == 'btn_import_files' ||
					id == 'btn_import_plugins' ||
					id == 'btn_import_themes' ||
					id == 'btn_import_content' ||
					id == 'btn_import_site') {
					$("[data-domain='"+domain+"'] #"+fileid).attr("accept", ".zip");
				}
				else if (id == 'btn_import_db') {
					$("[data-domain='"+domain+"'] #"+fileid).attr("accept", ".sql");
				}
				else if (id == 'btn_import_styles') {
					$("[data-domain='"+domain+"'] #"+fileid).attr("accept", ".json,.zip");
				}
				$("[data-domain='"+domain+"'] #"+fileid).attr("onchange", 'greyd.hub.chooseFile("'+id+'","'+domain+'","'+blogid+'","'+name+'")');
				$("[data-domain='"+domain+"'] #"+fileid).trigger("click");
			}
		}

		if (id == 'btn_select_db' ||
			id == 'btn_select_files' ||
			id == 'btn_select_plugins' ||
			id == 'btn_select_themes' ||
			id == 'btn_select_content' ||
			id == 'btn_select_site') {

			if ($(el).siblings('.select').length > 0) {
				$(el).siblings('.select').remove();
				$(el).removeClass('active');
				return;
			}
			$(el).addClass('active');

			var blogid = data_holder.data('id');
			var domain = data_holder.data('domain');
			var name = 'edit_'+id.split('_')[2]+'_';

			var file_type = '.zip';
			if (id == 'btn_select_content' || id == 'btn_select_site') file_type = /(?:fullsite|content)_[0-9]+.zip/;
			else if (id == 'btn_select_plugins') file_type = /plugins_[0-9]+.zip/;
			else if (id == 'btn_select_themes') file_type = /themes_[0-9]+.zip/;
			else if (id == 'btn_select_files') file_type = /uploads_[0-9]+.zip/;
			else if (id == 'btn_select_db') file_type = '.sql';

			// options
			var all_options = "<option value=''>...</option>";
			all_options += "<option value='up'>" + __( 'Upload file', 'greyd_hub' ) + "</option>";

			// get backup files
			var files = JSON.parse(backup_files);
			if (files) {
				var all_files = "";
				Object.entries(files).sort((a,b) => b-a).forEach(([key, value]) => {
					var date = key;
					var opts = "";
					var vals = value.sort((a,b) => a.name.localeCompare(b.name));
					for (var i=0; i<vals.length; i++) {
						if (vals[i]['name'].search(file_type) > -1) {
							date = vals[i]['date'];
							opts += "<option value='"+vals[i]['url']+"'>"+vals[i]['name']+"</option>";
						}
					}
					if (opts != "") all_files += "<optgroup label='"+date+"'>"+opts+"</optgroup>";
				});
				if (all_files != "") {
					all_files = "<option value=''>...</option>"+all_files;
					all_options += "<option value='file'>" + __( 'Backups', 'greyd_hub' ) + "</option>";
				}
			}

			// get all blogs
			if ($(".greyd_tile[data-id]").length > 1) {
				var all_blogs = "";
				$(".greyd_tile[data-id]").each(function() {
					var siteurl = $(this).data('domain');
					if (siteurl !== domain) {
						var value = $(this).data('network')+'|'+$(this).data('id');
						all_blogs += "<option value='"+value+"'>"+siteurl+"</option>";
					}
				});
				if (all_blogs != "") {
					all_blogs = "<option value=''>...</option>"+all_blogs;
					all_options += "<option value='blog'>" + __( 'Other blog', 'greyd_hub' ) + "</option>";
				}
			}

			// make selects
			var select = $("<div class='select'>"+
							"<select style='width: calc(100% - 50px); margin-right: 7px'>"+all_options+"</select>"+
							"<span class='button button-ghost'><span class='dashicons dashicons-no-alt'></span></span>"+
						"</div>");

			var select_file = $("<div class='select' style='display:none'>"+
									"<select style='width: calc(100% - 50px); margin-right: 7px'>"+all_files+"</select>"+
									"<span class='button button-ghost'><span class='dashicons dashicons-no-alt'></span></span>"+
								"</div>");

			var select_blog = $("<div class='select' style='display:none'>"+
									"<select style='width: calc(100% - 50px); margin-right: 7px'>"+all_blogs+"</select>"+
									"<span class='button button-ghost'><span class='dashicons dashicons-no-alt'></span></span>"+
								"</div>");

			// events
			var trigger = function() {
				// console.log('trigger file or remote blog');
				// console.log($(this).val());
				if ($(this).val() != '') {
					$("[data-domain='"+domain+"'] #"+name+'import_'+blogid).val($(this).val());
					var confirm_id = id.replace("btn_select_", "import_");
					greyd.backend.confirm(confirm_id, domain, greyd.hub.importFiles, [name, blogid, domain]);
				}
			};
			var reset = function() {
				select.find('select').val('');
				select.find('select').change();
			};

			select_blog.find('select').on('change', trigger);
			select_blog.find('.button').on('click', reset);

			select_file.find('select').on('change', trigger);
			select_file.find('.button').on('click', reset);

			select.find('select').on('change', function() {
				if ($(this).val() == 'file') {
					select_file.show();
					select_blog.hide();
				}
				else if ($(this).val() == 'blog') {
					select_file.hide();
					select_blog.show();
				}
				else {
					select_file.hide();
					select_blog.hide();
				}
				if ($(this).val() == 'up') {
					// console.log('trigger file upload');
					var fileid = name+'file_'+blogid;
					$("[data-domain='"+domain+"'] #"+fileid).val("");
					if (id == 'btn_select_db')
						$("[data-domain='"+domain+"'] #"+fileid).attr("accept", ".sql");
					else
						$("[data-domain='"+domain+"'] #"+fileid).attr("accept", ".zip");
					$("[data-domain='"+domain+"'] #"+fileid).attr("onchange", 'greyd.hub.chooseFile("'+id.replace("btn_select_", "import_")+'","'+domain+'","'+blogid+'","'+name+'")');
					$("[data-domain='"+domain+"'] #"+fileid).trigger("click");
				}
			});
			select.find('.button').on('click', function() {
				select.remove();
				select_file.remove();
				select_blog.remove();
				$(el).removeClass('active');
			});

			// add selects
			data_holder.append(select);
			data_holder.append(select_file);
			data_holder.append(select_blog);
		}

	}

	this.chooseFile = function(id, domain, blogid, name) {
		const input     = document.getElementById( name + 'file_' + blogid );
		const maxlength = parseInt( input.getAttribute('maxlength'), 10 );
		const filename  = input.files[0].name;
		const filesize  = input.files[0].size;

		// check if file is smaller than the max upload limit and small than the maximum chunksize of the uploader
		if ( maxlength && filesize < maxlength && filesize < greyd.uploader.maxChunkSize ) {
			// make direct upload
			id = id.replace("btn_", "", id);
			$("[data-domain='"+domain+"'] #"+name+'import_'+blogid).val("yes");
			greyd.backend.confirm(id, domain, greyd.hub.importFiles, [name, blogid, domain]);
		}
		else if (filesize < greyd.uploader.maxFileSize) {
			// make chunked upload
			greyd.backend.confirm("upload_file", filename+" ("+greyd.hub.formatBytes(filesize)+")", greyd.hub.uploadFile, [id, name, blogid, domain]);
		}
		else {
			// file way too big > 2GB
			var msg = 'Filesize ('+greyd.hub.formatBytes(filesize)+') exceeds the max upload limit of the Uploader ('+greyd.hub.formatBytes(greyd.uploader.maxFileSize)+').'
			greyd.backend.triggerOverlay(true, { css: 'upload', type: 'fail', replace: msg });
		}
	}
	this.uploadFile = function(id, name, blogid, domain) {
		const input = document.getElementById( name + 'file_' + blogid );
		greyd.uploader.init(input, greyd.hub.uploadFinished, [id, name, blogid, domain]);
	}
	this.uploadFinished = function(id, name, blogid, domain, filename) {
		if (filename != '') {
			$("[data-domain='"+domain+"'] #"+name+'file_'+blogid).val('');
			$("[data-domain='"+domain+"'] #"+name+'import_'+blogid).val(filename);
			var confirm_id = id.replace("btn_", "");
			greyd.backend.confirm(confirm_id, domain, greyd.hub.importFiles, [name, blogid, domain]);
		}
	}

	/**
	 * Format bytes to readable string.
	 *
	 * @param int $size         bytes to format.
	 * @param int $precision    precision rounding the value.
	 *
	 * @return string           eg. 128MB
	 */
	this.formatBytes = function(bytes, decimals = 2) {
		if (bytes === 0) return '0 Bytes';

		const k = 1024;
		const dm = decimals < 0 ? 0 : decimals;
		const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		const i = Math.floor(Math.log(bytes) / Math.log(k));

		return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + '&thinsp;' + sizes[i];
	}

	this.importFiles = function(name, blogid, domain) {
		$("[data-domain='"+domain+"'] #"+name+"import_clear_"+blogid).val("yes");
		$("[data-domain='"+domain+"'] #submit_"+name+blogid).trigger("click");
	}

	// admin
	this.clickButtonAdmin = function(el) {
		var id = $(el).attr('id');
		var data_holder = $(el).closest('.data-holder');
		// console.log( 'Admin Button clicked: '+id );

		var domain = data_holder.data('domain');
		var blogid = data_holder.data('id');

		if (id == 'btn_reset_website' ||
			id == 'btn_delete_website' ||
			id == 'btn_check_greyd') {
			var name = 'edit_'+id.split('_')[2]+'_';
			// set mode and ask confirm
			$("#"+name+"mode_"+blogid).val(id.split('_')[1]);
			greyd.backend.confirm(id.replace("btn_", ""), domain, greyd.hub.confirmButtonAdmin, [name, blogid]);
		}
		else if (id == 'btn_init_greyd') {
			greyd.backend.confirm(id.replace("btn_", ""), domain, greyd.hub.confirmInitAdmin, [blogid]);
		}
		else if (id == 'btn_setup_greyd') {
			greyd.backend.confirm(id.replace("btn_", ""), domain, greyd.hub.confirmSetupAdmin, [blogid]);
		}
	}
	this.confirmButtonAdmin = function(name, blogid) {
		// console.log(name+" "+blogid);
		$("#submit_"+name+blogid).trigger("click");
	}
	this.confirmInitAdmin = function(blogid) {
		/**
		 * Backward compatibility function for silent Greyd installation.
		 * @todo refactor to setup a new installation client.
		 */
		greyd.wizard.install_silent('install_reset_silent', blogid, greyd.hub.responseInitAdmin);
	}
	this.responseInitAdmin = function(result) {
		greyd.backend.triggerOverlay(true, {"type":result, "css":'init_greyd'});
	}
	this.confirmSetupAdmin = function(blogid) {
		/**
		 * Backward compatibility function for silent Greyd installation.
		 * @todo refactor to setup a new installation client.
		 */
		greyd.wizard.install_silent('install_basics_silent', blogid, greyd.hub.responseSetupAdmin);
	}
	this.responseSetupAdmin = function(result) {
		greyd.backend.triggerOverlay(true, {"type":result, "css":'setup_greyd'});
	}

	/*
	=======================================================================
		Backups Tab
	=======================================================================
	*/

	// delete File on button click
	this.confirmTrash = function(el) {
		// console.log($(el).val().replace('delete::', ''));
		// console.log(el);
		// alert($(this).val());
		var button = $(el).next()
		if (button.val().indexOf('delete::') > -1) {
			filename = button.val().replace('delete::', '');
			greyd.backend.confirm("delete", filename, this.clickThis, [button]);
		}
		else {
			alert("Unable to delete File!");
			return false;
		}
	}
	// click next element but trigger overlay 'loading' before
	this.clickNextLoading = function(el, css) {
		greyd.backend.triggerOverlay(true, {"type": "loading", "css": css});
		this.clickThis($(el).next());
	}

	/*
	=======================================================================
		Database Tab
	=======================================================================
	*/

	// check & uncheck all checkboxes in tables
	this.clickToggleCheck = function(el) {
		// console.log(el);
		var checked = true;
		if ($(el).data('checked') == true) checked = false;
		$(el).data('checked', checked);
		$(el).parent().children().each(function() {
			if ($(this).children().length > 0) $(this).children()[0].checked = checked;
		});
		if (checked && $(el).parent().data('id') > 0) {
			$('#replace_title_old').val($(el).parent().data('name'));
			$('#replace_description_old').val($(el).parent().data('description'));
			$('#replace_url_old').val($(el).parent().data('domain'));
			$('#replace_admin_old').val($(el).parent().data('admin'));
			// $('#replace_pre_old').val($(el).parent().data('prefix'));
			$('#replace_id_old').val($(el).parent().data('id'));
		}
		else {
			$('#replace_title_old').val('');
			$('#replace_description_old').val('');
			$('#replace_url_old').val('');
			$('#replace_admin_old').val('');
			$('#replace_pre_old').val('');
			$('#replace_id_old').val('');
		}
		greyd.hub.enableButtons();
	}
	this.enableButtons = function() {
		greyd.hub.tables = [];
		$(".hub_tables").each(function() {
			$(this).find("input").each(function() {
				if ($(this).prop('checked')) {
					greyd.hub.tables.push($(this).attr('name'));
				}
			});
		});
		// console.log(greyd.hub.tables);
		if (greyd.hub.tables.length == 0) {
			$(".hub_table_button").addClass("disabled");
		}
		else {
			$(".hub_table_button").removeClass("disabled");
		}
	}
	// delete & export tables
	this.clickButtonTables = function(el) {
		if (greyd.hub.tables.length == 0) {
			alert("No Tables have been selected!");
			return false;
		}
		var button = $(el).next();
		if (button.val() == "Export Tables") {
			greyd.backend.confirm("export_tables", greyd.hub.tables.join("\n"), this.clickThis, [button]);
		}
		else if (button.val() == "Delete Tables") {
			greyd.backend.confirm("delete_tables", greyd.hub.tables.join("\n"), this.clickThis, [button]);
		}
		else {
			alert("Die Aktion konnte nicht durchgeführt werden");
			return false;
		}
	}
	// import tables
	this.importTables = function(el) {
		var fileinput = $(el).siblings('input[type="file"]');
		var submitid = $(el).siblings('input[type="submit"]').attr("id");
		// console.log(fileinput);

		fileinput.removeAttr("onchange")
		fileinput.val("");
		fileinput.attr("onchange", 'greyd.backend.confirm("import_tables", "", greyd.hub.clickThis, [$("#'+submitid+'")])');
		fileinput.trigger("click");
	}

	// function used by clickTrash, overlaySubmit, clickButtonTables
	this.clickThis = function(el) {
		el.click();
	}

	/*
	=======================================================================
		Relocation Wizard
	=======================================================================
	*/

	this.initHubWizard = function() {

		// overlay
		const form = $("form#hub-relocation");
		const button = $("#btn_hub_wizard");

		if ( form.length === 0 ) return;

		// save the data
		let defaultData = {
			domain: "",
			name: "",
			description: "",
			action: ""
		};
		let data = {
			source: defaultData,
			destination: defaultData
		};

		// add open trigger
		$("[data-event=wizard]").on('click', function() {
			var args = $(this).data('args');
			greyd.hub.openHubWizard(args);
		});

		// handle select change
		form.find("select").on( "change", function() {
			const value = String( $(this).val() );
			const name = $(this).attr("name");
			const wrapper = $(this).closest(".preview-wrap");

			// get data
			let inputData;
			if ( value.length > 0 ) {
				inputData = $(this).find("option[value='"+value+"']").data();
			}
			if ( inputData ) data[name] = inputData;
			else data[name] = defaultData;

			// dis- / enable button
			if ( data.source.domain.length && data.destination.domain.length ) {
				button.removeClass('disabled');
			} 
			else {
				button.addClass('disabled');
			}

			// set form action url
			if ( data.destination.action !== form.attr("action") ) {
				form.attr("action", data.destination.action);
			}

			// replace text
			$.each( data[name], function( key, val ) {
				wrapper.find(".replace[data-replace="+key+"]").text(val);
			});

			// set link
			wrapper.find(".replace_domain").attr("href", data[name].domain);

			// set hub frame
			const wrap = wrapper.find(".hub_frame_wrapper");
			const iframe = wrap.find("iframe");
			const clone = iframe.clone();
			iframe.remove();
			if ( data[name].domain.length === 0 ) {
				wrap.removeClass("loading").addClass("empty");
			} 
			else {
				wrap.removeClass("empty").addClass("loading");
			}
			clone.attr("src", data[name].domain+"?hide_admin_bar=true" );
			wrap.find(".hub_frame").append( clone );
		} );

		// trigger confirm
		button.on( "click", function() {

			const remoteInfo = $("#overlay .remote-destination-info");
			if ( data.destination.action.length > 0 ) {

				const anchor = remoteInfo.find("a");
				anchor.attr("href", data.destination.action);
				anchor.text( data.destination.action.match(/:\/\/([^\/]+?)\//)[1] );

				remoteInfo.removeClass("hidden");
			}
			else {
				remoteInfo.addClass("hidden");
			}

			greyd.backend.confirm(
				'relocation',
				data.source.domain+" → "+data.destination.domain,
				() => form.find("input[type=submit]").click()
			)
		} );
	}

	this.openHubWizard = function(value) {

		if ( typeof value !== "undefined" ) {
			// open from button inside tile
			const select = $("form#hub-relocation select#relocation--source");
			select.val( value );
			select.trigger( "change" );
		}

		// open overlay
		greyd.backend.openOverlay("hub_wizard");
	}

	/*
	=======================================================================
		New Site Wizard
	=======================================================================
	*/

	this.initNewSiteWizard = function() {
		
		// overlay
		const form = $("form#new-site");
		const button = $("#btn_new_site_wizard");

		// add open trigger
		$("[data-event=new-site]").on('click', function() {
			// open overlay
			greyd.backend.openOverlay("new_site_wizard");
		});

		// handle input change
		form.find("input").on('input', function() {
			if ($("#site-address").val() != "" && $("#site-title").val())
				button.removeClass('disabled');
			else
				button.addClass('disabled');
		} );

		// trigger confirm
		button.on( "click", function() {
			form.find("input[type=submit]").click();
		} );
	}


	/*
	=======================================================================
		Staging
	=======================================================================
	*/

	this.initStaging = function() {

		// init wizard
		this.initStagingWizard();

		if ( $(".greyd_stage_wrapper").length ) {
			// set thumbs
			var thumbsMode = this.getLocal('hub_thumbs');
			// console.log("initial thumbs: "+thumbsMode);
			if ( thumbsMode && thumbsMode == "1") {
				// show thumbs
				$(".hub_panel .greyd_stage_wrapper .greyd_tile").each(function() {
					const iframe = $(this).find("iframe");
					const wrap = $(this).find(".hub_frame_wrapper");
					const url = $(this).find(".hub_frame").data("siteurl");
					wrap.removeClass("hidden").addClass("loading");
					iframe.attr("src", url+"?hide_admin_bar=true" );
				});
			}
		}

		// toggle stages
		$("[data-event=switch-staging-version]:not(.stage_error)").on('click', function() {
			const wrapper = $(this).closest(".greyd_staging_pair");
			const live = wrapper.find(".greyd_staging_live");
			const staging = wrapper.find(".greyd_staging_stage");
			if (staging.hasClass("hidden")) {
				// show staging
				live.fadeOut(50, 'swing', function() {
					staging.fadeIn(50);
					staging.removeClass("hidden");
					live.addClass("hidden");
				});
			} 
			else if (live.hasClass("hidden")) {
				// show live
				staging.fadeOut(50, 'swing', function() {
					live.fadeIn(50);
					live.removeClass("hidden");
					staging.addClass("hidden");
				});
			}
		});

	}

	/*
	=======================================================================
		Staging Tab
	=======================================================================
	*/

	this.clickButtonStaging = function(el) {
		var id = $(el).attr('id');
		var data_holder = $(el).closest('.data-holder');
		// console.log( 'Staging Button clicked: '+id );

		var submit = data_holder.data('id');
		var liveDomain = data_holder.data('live-domain');
		var stageDomain = data_holder.data('stage-domain');

		if (
			id == 'btn_staging_reconnect_stage' ||
			id == 'btn_staging_reconnect_live' ||
			id == 'btn_staging_disconnect' ||
			id == 'btn_staging_push' ||
			id == 'btn_staging_pull' ||
			id == 'btn_staging_reset'
		) {
			var mode = id.replace("btn_", "");
			var replace = stageDomain+" → "+liveDomain;
			greyd.backend.confirm(mode, replace, greyd.hub.confirmButtonStaging, [mode, submit]);
			$("#overlay").removeClass("large");
			$("#overlay").removeClass("wide");
			if (mode == "staging_push") {
				// toggle push button
				var design = $("#staging-push-design");
				var db = $("#staging-push-db");
				var files = $("#staging-push-files");
				$(".button.staging_push").addClass("disabled");
				if (design[0].checked || db[0].checked || files[0].checked) {
					$(".button.staging_push").removeClass("disabled");
				}
				$("#staging-push-design, #staging-push-db, #staging-push-files").off().on('change', function() {
					$(".button.staging_push").addClass("disabled");	
					if (design[0].checked || db[0].checked || files[0].checked) {
						$(".button.staging_push").removeClass("disabled");
					}
				});
			}
			// show action/redirect info
			greyd.hub.maybeShowRedirectInfo($(el).parent().find("form"));
		}
		else if (id == 'btn_staging_log') {
			var replace = stageDomain+" → "+liveDomain;
			greyd.backend.triggerOverlay( true, {
				type: "alert",
				css: 'staging_log',
				replace: replace
			} );
			$("#overlay").removeClass("wide");
			$("#overlay").addClass("large");
			var log = JSON.parse(decodeURIComponent(data_holder.data('log')));
			var logTable = "";
			for (var i=0; i<log.length; i++) {
				logTable += "<tr><td>"+log[i]["date"]+"</td><td>"+log[i]["action"]+"</td></tr>";
			}
			$("#staging-log-body").html(logTable);
		}
		else if (id == 'btn_staging_changes_posts' || id == 'btn_staging_changes_options') {
			var mode = id.replace("btn_staging_changes_", "");
			var replace = data_holder.data('last_push');
			greyd.backend.triggerOverlay( true, {
				type: "alert",
				css: 'staging_changes',
				replace: replace
			} );
			$("#overlay").removeClass("large");
			$("#overlay").removeClass("wide");
			$("#staging-changes-head-posts").css("display", "none");
			$("#staging-changes-head-options").css("display", "none");
			if (mode == "posts") {
				$("#overlay").addClass("wide");
				$("#staging-changes-head-posts").css("display", "inline");
			}
			else if (mode == "options") {
				$("#overlay").addClass("large");
				$("#staging-changes-head-options").css("display", "inline");
			}
			var stageHome = stageDomain;
			if (data_holder.data('stage-home') != "") {
				stageHome = "<a href='"+data_holder.data('stage-home')+"'>"+stageDomain+"</a>";
			}
			$("#staging-changes-stage-url").html(stageHome);
			var liveHome = liveDomain;
			if (data_holder.data('live-home') != "") {
				liveHome = "<a href='"+data_holder.data('live-home')+"'>"+liveDomain+"</a>";
			}
			$("#staging-changes-live-url").html(liveHome);
			var changes = JSON.parse(decodeURIComponent(data_holder.data('changes')));
			var logTable = "";
			for (var i=0; i<changes[mode].length; i++) {
				logTable += "<tr><td>"+changes[mode][i].stage.markup+"</td><td>"+changes[mode][i].live.markup+"</td></tr>";
			}
			$("#staging-changes-body").html(logTable);
		}
	}
	this.confirmButtonStaging = function(mode, name) {
		// console.log(mode+" "+name);
		var buttonId = ("#submit_"+mode+"_"+name).split('/').join('\\/');
		if (mode == "staging_push") {
			// make push data
			var data = [];
			if ($("#staging-push-design")[0].checked) data.push('design');
			if ($("#staging-push-db")[0].checked) data.push('db');
			if ($("#staging-push-files")[0].checked) data.push('files');
			$(buttonId).parent().find("[name=push_data]").val(data);
		}
		$(buttonId).trigger("click");
	}

	this.maybeShowRedirectInfo = function(form) {
		var remoteInfo = $("#overlay .remote-stage-info");
		remoteInfo.addClass("hidden");
		if ( form?.attr("action")?.length > 0 ) {
			// console.log(form.attr("action"));
			var current = window.location.href;
			if (current.indexOf(form.attr("action")) == -1) {
				var link = remoteInfo.find("a");
				link.attr("href", form.attr("action"));
				link.text( form.attr("action").match(/:\/\/([^\/]+?)\//)[1] );
				remoteInfo.removeClass("hidden");
			}
		}
	}

	/*
	=======================================================================
		Staging Wizard
	=======================================================================
	*/

	this.initStagingWizard = function() {
		
		// overlay
		const form = $("form#new-stage");
		const button = $("#btn_new_stage_wizard");

		// add open trigger
		$("[data-event=new-stage]").on('click', function() {
			var args = $(this).data('args');
			if ( typeof args !== "undefined" ) {
				// open from button inside tile
				var live = $("#staging-live");
				live.val( args );
				live.trigger( "change" );
			}
			// open overlay
			greyd.backend.openOverlay("new_stage_wizard");
		});

		// radio/checkbox toggles
		form.find("[data-show]").each(function() {
			var id = $(this).data('show');
			// console.log($("#"+id));
			if ($("#"+id) && $("#"+id).attr('checked')) $(this).css('display', 'flex');
			else $(this).css('display', 'none');
		});
		form.find("[data-toggle]").on('change', function() {
			// console.log($(this));
			if ($(this).attr('type') == "radio" || $(this)[0].checked) {
				form.find("[data-trigger="+$(this).data('toggle')+"]").css('display', 'none');
				form.find("[data-show="+$(this).attr('id')+"]").css('display', 'flex');
			}
			else {
				form.find("[data-show="+$(this).attr('id')+"]").css('display', 'none');
			}
		});

		// handle input change
		form.find("select, [name=mode]").on('change', function() {
			// console.log($(this));
			var mode = form.find("[name=mode]:checked").val();
			var live = $("#staging-live").val();
			var stage = $("#staging-stage").val();

			// set domain
			if ($(this)[0].localName == 'select') {
				// get data
				var inputData = false;
				var value = $(this).val();
				var name = $(this).attr('name');
				// preview
				var previewWrapper = $(this).closest(".preview-wrap");
				const frameWrapper = previewWrapper.find(".hub_frame_wrapper");
				const iframe = frameWrapper.find("iframe");
				const newIframe = iframe.clone();
				iframe.remove();
				if ( value.length > 0 ) {
					inputData = $(this).find("option[value='"+value+"']").data();
					// console.log(inputData);
					if ($(this)[0].id == 'staging-live') {
						// set placeholder and suggestion
						var replace = form.find("[name=stage-address]").data('network');
						var suggest = inputData.domain.split("://")[1].replace(replace, '')+"-stage";
						form.find("[name=stage-address]")[0].placeholder = suggest;
						form.find("[name=stage-suggest]").val(suggest);
					}
					form.find("[name="+name+"-domain]").val(inputData.domain);
					form.find("[name="+name+"-title]").val(inputData.name);
					form.find("[name="+name+"-description]").val(inputData.description);
					// preview
					previewWrapper.find(".replace_domain").attr("href", inputData.domain);
					previewWrapper.find(".replace[data-replace=domain]").text(inputData.domain);
					previewWrapper.find(".replace[data-replace=name]").text(inputData.name);
					previewWrapper.find(".replace[data-replace=description]").text(inputData.description);
					// set hub frame
					frameWrapper.removeClass("empty").addClass("loading");
					newIframe.attr("src", inputData.domain+"?hide_admin_bar=true" );
					frameWrapper.find(".hub_frame").append( newIframe );
					
				}
				else {
					if ($(this)[0].id == 'staging-live') {
						// reset placeholder and suggestion
						form.find("[name=stage-address]")[0].placeholder = "";
						form.find("[name=stage-suggest]").val("");
					}
					form.find("[name="+name+"-domain]").val("");
					form.find("[name="+name+"-title]").val("");
					form.find("[name="+name+"-description]").val("");
					// preview
					previewWrapper.find(".replace_domain").attr("href", "");
					previewWrapper.find(".replace[data-replace=domain]").text("");
					previewWrapper.find(".replace[data-replace=name]").text("");
					previewWrapper.find(".replace[data-replace=description]").text("");
					// set hub frame
					frameWrapper.removeClass("loading").addClass("empty");
					newIframe.attr("src", "" );
					frameWrapper.find(".hub_frame").append( newIframe );
				}
			}

			// set form action url
			form.attr("action", "");
			if ( mode == "link" && stage.length > 0 ) {
				var inputData = $("#staging-stage").find("option[value='"+stage+"']").data();
				var current = window.location.href;
				if (current.indexOf(inputData.action) == -1) {
					form.attr("action", inputData.action);
				}
			}

			// enable/disable infos and submit button
			form.find(".staging_info_remote_pair").addClass('hidden');
			form.find(".staging_info_local_pair").addClass('hidden');
			form.find(".staging_info_same_pair").addClass('hidden');
			if ( (mode == "create" && live != "") || (live != "" && stage != "" && live != stage) ) {
				var liveAction = $("#staging-live").find("option[value='"+live+"']").data("action");
				if (mode == "create") var stageAction = window.location.href.split("&")[0];
				else var stageAction = $("#staging-stage").find("option[value='"+stage+"']").data("action");
				if (liveAction != stageAction) form.find(".staging_info_remote_pair").removeClass('hidden');
				else form.find(".staging_info_local_pair").removeClass('hidden');
				// enable submit
				button.removeClass('disabled');
			}
			else {
				if (live == stage) form.find(".staging_info_same_pair").removeClass('hidden');
				// disable submit
				button.addClass('disabled');
			}
		} );

		// trigger confirm
		button.on( "click", function() {
			// show create/link info
			var mode = form.find("[name=mode]:checked").val();
			var linkInfo = $("#overlay .remote-stage-link");
			var linkCreate = $("#overlay .remote-stage-create");
			linkInfo.addClass("hidden");
			linkCreate.addClass("hidden");
			if (mode == 'create') {
				linkCreate.removeClass("hidden");
			}
			else {
				linkInfo.removeClass("hidden");
			}
			// show action/redirect info
			greyd.hub.maybeShowRedirectInfo(form);
			// get domains (for replace)
			var domainLive = form.find("[name=live-domain]").val();
			var domainStage = form.find("[name=stage-domain]").val();
			if (mode == 'create' || domainStage == "") {
				domainStage = form.find("[name=stage-address]").val();
				if (domainStage == "") {
					domainStage = form.find("[name=stage-suggest]").val();
					if (domainStage == "") domainStage = "*new-stage*"
				}
			}
			// confirm
			greyd.backend.confirm(
				'staging_connect',
				domainLive+" → "+domainStage,
				function() { form.find("input[type=submit]").click() }
			);
		} );
	}

}

/**
 * Uploader.
 * Upload big files in chunks via ajax calls.
 */
greyd.uploader = new function() {

	this.initialized = false;
	this.ajax = { };
	this.file;

	this.timeStart = 0;
	this.abort = false;
	this.paused = false;
	this.nextChunk = false;

	// 5MB default chunk size
	this.chunkSize = 1024*1024*5;

	// 50MB maximum chunk size
	this.maxChunkSize = 1024*1024*50;

	// 2GB default max filesize
	this.maxFileSize = 1024*1024*1024*2;

	/**
	 * Initialize the Uploader.
	 *
	 * @param {object} input        html input type file containing the file reference.
	 * @param {function} callback   Function to call after everything is uploaded.
	 * @param {array} args          Array of arguments for the callback Function.
	 * @returns void    Starts uploading first chunk.
	 */
	this.init = function(input, callback, args) {

		// init events only once
		if (!this.initialized) {

			// greyd_hub compatibility
			if (typeof wizzard_details !== 'undefined')
				this.ajax = { ajax_url: wizzard_details.ajax_url, nonce: wizzard_details.nonce, action: "greyd_ajax" };
			else if (typeof greyd !== 'undefined')
				this.ajax = { ajax_url: greyd.ajax_url, nonce: greyd.nonce, action: "greyd_admin_ajax" };

			// add events only once
			document.querySelector(".greyd_overlay .loading .uploader-pause").addEventListener('click', function(e) {
				greyd.uploader.pauseUpload();
			} );
			// document.querySelector(".greyd_overlay .loading .uploader-abort").addEventListener('click', function(e) {
			//     greyd.uploader.abortUpload();
			// } );
			document.querySelector(".greyd_overlay .loading a.escape").addEventListener('click', function(e) {
				e.preventDefault();
				greyd.uploader.abortUpload();
			} );

			this.initialized = true;
		}

		// save callback
		this.ajax.callback = callback;
		this.ajax.callback_args = args;

		// init chunk size
		const maxlength = parseInt( input.getAttribute('maxlength') );
		// console.log(maxlength);
		if (maxlength && maxlength < this.maxChunkSize) {
			this.chunkSize = maxlength - 1024; // allow 1KB overhead
			console.log('setting chunk size to: '+this.chunkSize);
		}

		// get file
		this.file = input.files[0];
		var fileSize = this.file.size;
		if (fileSize > this.maxFileSize) {
			console.log('The file you have chosen is too large: '+fileSize);
			return;
		}
		var numberOfChunks = Math.ceil(fileSize / this.chunkSize);

		// init vars
		this.timeStart = new Date().getTime();
		this.abort = false;
		this.paused = false;
		this.nextChunk = false;

		// init UI
		this.cleanup();
		document.querySelector(".greyd_overlay .loading .uploader").style.display = 'block';
		document.querySelector(".greyd_overlay .loading .uploader-file-title").innerHTML = this.file.name;
		document.querySelector(".greyd_overlay .loading .uploader-file-size").innerHTML  = greyd.hub.formatBytes( this.file.size );

		// start with first chunk
		this.uploadChunk(1, numberOfChunks);

	}

	/**
	 * Upload a single chunk.
	 * Repeated until all chunks are uploaded.
	 *
	 * @param {int} chunk           Number (index) of current chunk (start with 1).
	 * @param {int} numberOfChunks  Total number of cunks to upload.
	 */
	this.uploadChunk = function (chunk, numberOfChunks) {

		// slice file into desired chunk
		var start = (chunk-1) * this.chunkSize;
		var stop = start + this.chunkSize;
		var blob = this.file.slice(start, stop);
		// console.log(blob);

		// prepare form data
		var fd = new FormData();
		fd.append("action", this.ajax.action);
		fd.append("_ajax_nonce", this.ajax.nonce);
		fd.append("mode", 'upload_part');
		fd.append("data", encodeURIComponent( JSON.stringify( {
			'chunk_number': chunk,
			'number_of_chunks': numberOfChunks,
			'final_name': this.file.name,
			'abort': this.abort // if true: tmp file will be deleted and 'error::' sent
		} ) ));
		fd.append("chunk", blob);

		// send data with chunk as blob
		jQuery.ajax({
			url: this.ajax.ajax_url,
			data: fd,
			processData: false,
			contentType: false,
			type: "POST",

			success: function(response) {
				if (response.indexOf('error::') > -1) {
					// console.warn( response );
					console.warn("Upload failed");
					var msg = response.split('error::');
					greyd.uploader.cleanup();
					greyd.backend.triggerOverlay(true, { css: 'upload_file', type: 'fail', replace: msg[1] });
				}
				else if (response.indexOf('abort::') > -1) {
					console.warn("Upload aborted");
					greyd.uploader.cleanup();
					greyd.backend.triggerOverlay(true, { css: 'upload_file', type: 'abort' });
					greyd.backend.fadeOutOverlay();
				}
				else {
					// console.info( response );
					if (chunk < numberOfChunks) {
						// chunk is uploaded
						console.log("loaded chunk #"+chunk+"/"+numberOfChunks);

						// check next action
						if (!greyd.uploader.paused) {
							// process next chunk
							greyd.uploader.uploadChunk((chunk + 1), numberOfChunks);
						}
						else {
							// pause and save state
							greyd.uploader.statusUpdate('paused');
							greyd.uploader.nextChunk = {
								chunk: chunk + 1,
								numberOfChunks: numberOfChunks,
								timePause: new Date().getTime()
							};
						}
					}
					else {
						// file is uploaded
						console.log("finished "+chunk+" chunks");
						var msg = response.split('success::');
						greyd.uploader.finishUpload(msg[1]);
					}

				}
			},
			error: function(error) {

			}

		});

		// ui: check next action
		if (this.abort)
			this.statusUpdate('abort');
		else
			this.progressUpdate(chunk, numberOfChunks);

	}

	/**
	 * Pause/Unpause the Uploader.
	 */
	this.pauseUpload = function() {

		if (!this.paused) {

			// set pause
			this.paused = true;
			this.statusUpdate('pausing');
			document.querySelector(".greyd_overlay .loading .uploader-pause").classList.remove('dashicons-controls-pause');
			document.querySelector(".greyd_overlay .loading .uploader-pause").classList.add('dashicons-controls-play');
			document.querySelector(".greyd_overlay .loading").classList.add('paused');

		}
		else if (this.nextChunk) {

			// unpause
			this.paused = false;
			this.progressUpdate(this.nextChunk.chunk, this.nextChunk.numberOfChunks);
			document.querySelector(".greyd_overlay .loading .uploader-pause").classList.add('dashicons-controls-pause');
			document.querySelector(".greyd_overlay .loading .uploader-pause").classList.remove('dashicons-controls-play');
			document.querySelector(".greyd_overlay .loading").classList.remove('paused');

			// recalculate starttime
			var timeResume = new Date().getTime();
			this.timeStart = this.timeStart + (timeResume - this.nextChunk.timePause);
			this.uploadChunk(this.nextChunk.chunk, this.nextChunk.numberOfChunks);
			this.nextChunk = false;

		}

	}

	/**
	 * Abort the Uploader.
	 */
	this.abortUpload = function() {

		// flag abort
		this.abort = true;
		this.statusUpdate('abort');

		if (this.paused && this.nextChunk) {
			// unpause to trigger abort
			this.pauseUpload();
		}

	}

	/**
	 * Finish the Upload.
	 * Triggered after all chunks are successfully uploaded.
	 *
	 * @param {string} finalName    Full url to the uploaded file.
	 */
	this.finishUpload = function(finalName) {

		// cleanup UI
		this.cleanup();

		// call callback
		var args = [ ...this.ajax.callback_args, finalName ];
		this.ajax.callback.apply(this, args);

	}

	/**
	 * Cleanup the UI elements
	 * Triggered on startup and after upload is finished or aborted.
	 */
	this.cleanup = function() {

		// reset UI
		document.querySelector(".greyd_overlay .loading .uploader").style.display = 'none';
		document.querySelector(".greyd_overlay .loading .uploader-file-title").innerHTML = '';
		document.querySelector(".greyd_overlay .loading .uploader-file-size").innerHTML = '';
		document.querySelector(".greyd_overlay .loading .uploader-progress-bar").style.width = '0%';
		document.querySelector(".greyd_overlay .loading .uploader-percent").innerHTML = '0%';
		document.querySelector(".greyd_overlay .loading .uploader-status").innerHTML = '';

	}

	/**
	 * Update progress to UI elements.
	 * Triggered after every chunk upload.
	 *
	 * @param {int} progress    Uploaded Chunk index.
	 * @param {int} total       Total number of Chunks.
	 */
	this.progressUpdate = function(progress, total) {

		// update progressbar
		var percent = Math.ceil((progress / total) * 100);
		document.querySelector(".greyd_overlay .loading .uploader-progress-bar").style.width = percent + '%';
		document.querySelector(".greyd_overlay .loading .uploader-percent").textContent = percent + '%';

		// calculate estimated time remaining
		if (progress < 10 && percent < 10) {
			this.statusUpdate('wait');
		}
		else if (progress % 2 === 0) {
			// recalculate every second chunk
			var currentTime = new Date().getTime();
			var time_used = currentTime - this.timeStart;
			var time_left = (time_used/(progress / total)) * (1-(progress / total)) / 1000;
			time_left = Math.ceil( Math.abs( time_left ) );
			if (time_left > 60) {
				time_left = Math.ceil( time_left / 60 );
				this.statusUpdate('minutes', time_left);
			}
			else {
				this.statusUpdate('seconds', time_left);
			}
		}

	};

	/**
	 * Update UI status.
	 * Called with every progress update and when pausing or aborting.
	 *
	 * @param {string} status   Status mode (html element has data element with string)
	 * @param {string} replace  String to replace placeholder (seconds/minutes)
	 */
	this.statusUpdate = function(status, replace="") {

		// set UI status
		var element = document.querySelector(".greyd_overlay .loading .uploader-status");
		element.innerHTML = element.dataset[status].replace("%s", replace);

	}
}