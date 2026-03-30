/**
 * This file handles the install Wizard.
 */
(function() { 

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		wizzard.init();

		console.log('Wizard Scripts: loaded');
	} );

} )(jQuery);

var wizzard = new function() {

	this.alert_on_leave = false;

	this.init = function() {
		// events
		this.addEvents();
	}
	this.addEvents = function() {

		// alert on window leave when var is true
		$(window).on('beforeunload', function(e){
			if (wizzard.alert_on_leave) return true;
			else e = null;
		});

		// add click events
		$('.open_wizzard').on('click', this.open);
		$('.open_wizzard_reset').on('click', function() { wizzard.open('reset') });

		$('.wizzard .reload').on('click', this.reload);
		$('.wizzard .close_wizzard').on('click', this.close);
		$('.wizzard .finish_wizzard').on('click', function(){ wizzard.finish(this); });
		$('.wizzard .start_wizzard').on('click', this.start);
		$('.wizzard .start_library').on('click', function() { greyd.templateLibrary.open(); });
		$('.wizzard .wizzard_option').on('click', this.select);
		$('.wizzard .back').on('click', this.back);
		$('.wizzard .prev').on('click', this.prev);
		$('.wizzard .next').on('click', this.next);
		$('.wizzard .setup').on('click', this.next);
		$('.wizzard .install_mods').on('click', this.install_mods);
		$('.wizzard .install_setup').on('click', this.install_setup);
		$('.wizzard .install_reset').on('click', this.install_reset);

		$('.wizzard .pagination .page').on('click', function() { wizzard.setState($(this).data("slide")) });
		$('.wizzard .settings_wizzard').on('click', function() { wizzard.settings('', false); });
	}

	this.initnew = function() {
		$('#greyd-wizard .wizzard_box').addClass('big');
		wizzard.open();
		wizzard.start();
	}
	this.open = function(mode='') {
		wizzard.reset(mode);
		$('.wizzard').fadeIn();
	}
	this.close = function() {
		$('.wizzard').fadeOut();

		// set timeout to call after href
		if ( location.search.indexOf("&wizard=") > -1 || location.search.indexOf("&wizzard=") > -1 ) {
			setTimeout(function() { wizzard.reload(); },1); // reload without url-param
		}
	}
	this.finish = function(el) {
		var button = $(el);
		if ( button.attr('href') ) {
			if ( !button.attr('target') ) return;
		}
		wizzard.close();
	}
	this.reload = function() {
		location.search = location.search.replace(/\&wizzard\=.*/g, '').replace(/\&wizard\=.*/g, '');
		// location.reload(true);
	}
	this.start = function() {
		$('.wizzard').addClass('started');
		if ($(this).hasClass('reset'))
			wizzard.setState(20);
		else
			wizzard.setState(1);
	}
	this.back = function(el) {
		wizzard.setState(1);
	}
	this.prev = function() {
		var state = $('.wizzard').data('state');
		state--;
		if (state < 1) wizzard.reset();
		else wizzard.setState(state);
	}
	this.next = function() {
		var state = $('.wizzard').data('state');
		state++;
		wizzard.setState(state);
	}

	this.settings = function(optionid='', reset=true) {
		if (reset) wizzard.reset();
		wizzard.setState(8);
		$('.wizzard').addClass('started');
		if ( optionid.length > 0 ) {
			if ($('.wizzard').find('input#'+optionid).length > 0)
				$('.wizzard').find('input#'+optionid).trigger("click");
		}
		if (reset) $('.wizzard').fadeIn();
	}

	this.reset = function(mode='') {
		$('.wizzard').removeClass('started');
		wizzard.setState(0, mode);

		// wizzard.setState(8);
		$('.wizzard .wizzard_content .wizzard_option').each(function() {
			$(this).removeClass('active');
			$(this).data('option', -1);
			var src = $(this).children('img').attr('src');
			if ($(this).hasClass('result')) {
				$(this).children('img').attr('src', src.replace(/(assets\/wizzard\/img\/)(.*)(?=.svg)/g, 'assets/wizzard/img/no-select'));
			} 
			else {
				$(this).children('img').attr('src', src.replace('_select', ''));
			}
		});
		$('.wizzard_content .install_txt').css('opacity', 0);
	}

	this.setState = function(state, mode='') {
		var setstate = state;
		if (mode.length > 0) setstate = state+'-'+mode;

		wizzard.showSlide($('.wizzard .wizzard_content').find("div[data-slide='" + setstate + "']"));
		if (setstate != '0-init' && setstate != '0-reset' && setstate > 0 && setstate < 5) {
			$('.wizzard .wizzard_foot > div').each(function() {
				if ($(this).data('slide') == 1) $(this).show();
				else $(this).hide();
			});
			$('.wizzard .pagination').children().each(function() {
				$(this).removeClass('current');
				if ($(this).index() == setstate-1) $(this).addClass('current');
			});
		}
		else {
			if ( $('.wizzard .wizzard_foot > div[data-slide="'+setstate+'"]').length === 0 ) {
				wizzard.start();
			} else {
				$('.wizzard .wizzard_foot > div').each(function() {
					if ($(this).data('slide') == setstate) $(this).show();
					else $(this).hide();
				});
			}
		}
		$('.wizzard').data('state', state);

		// toggle infos
		var txt = $('.wizzard_content > .active .install_txt');
		var opac = 0;
		if (txt.length > 0) {
			txt.each(function(i, obj){
				setTimeout(function() { $(obj).css('opacity', 1); }, opac);
				opac += 500;
			});
		}
	}

	this.showSlide = function(slide) {
		slide.addClass("active");
		slide.siblings().removeClass("active");
	}

	this.select = function() {
		if ($(this).hasClass('result')) {
			wizzard.setState($(this).index()+1);
		} 
		else {
			$(this).parent().children().each(function() {
				$(this).removeClass('active');
				var src = $(this).children('img').attr('src');
				$(this).children('img').attr('src', src.replace('_select', ''));
			});
			$(this).addClass('active');
			var src = $(this).children('img').attr('src');
			var title = $(this).children('p').html();
			$(this).children('img').attr('src', src.replace('.svg', '_select.svg'));

			var state = $('.wizzard').data('state');
			var option = $(this).index();
			var result = $('.wizzard_option.result')[state-1];
			$(result).data('option', option);
			$(result).attr('title', title);
			$(result).children('img').attr('src', src);
			wizzard.next();
		}
	}

	this.selectContent = function(el) {
		$(el).parent().children().each(function() {
			$(this).removeClass('active');
			var src = $(this).children('img').attr('src');
			$(this).children('img').attr('src', src.replace('_select', ''));
		});
		$(el).addClass('active');
		var src = $(el).children('img').attr('src');
		$(el).children('img').attr('src', src.replace('.png', '_select.png'));
		wizzard.setCopyContent();
	}
	this.setCopyContent = function() {
		var el = $('#greyd-wizard #copy');
		var type = $('#greyd-wizard #ttype').val();
		if ($('#greyd-wizard #copy_'+type).length == 0) {
			$(el).css('display', 'none');
			if ($(el).hasClass('active')) {
				wizzard.selectContent($('#greyd-wizard #default'));
			}
		}
		else {
			$(el).css('display', 'block');
			$(el).find('select').each(function() {
				$(this).css('display', 'none');
			});
			if ($(el).hasClass('active')) {
				$('#greyd-wizard #copy_'+type).css('display', 'block');
			}
		}
	}
	this.checkExisting = function(posts) {
		// console.log("new slug: "+$('#greyd-wizard #slug').val());
		// console.log("new name: "+$('#greyd-wizard #name').val());
		var index = -1;
		if (Array.isArray(posts) && posts.length > 0) {
			var slug = $('#greyd-wizard #slug').val();
			if (slug == "") slug = $('#greyd-wizard #name').val();
			for (var i=0; i<posts.length; i++) {
				if (posts[i].slug == slug || posts[i].title == slug) {
					index = i;
					break;
				}
			}
		}
		if (index > -1) {
			// console.log("template exists!");
			$('#greyd-wizard .name_double').css('display', 'block');
			$('#greyd-wizard .name_double .double_name').html(posts[index].title);
			var url = location.href.split("wp-admin");
			var edit = url[0]+"wp-admin/post.php?post="+posts[index].id+"&action=edit";
			$('#greyd-wizard .name_double .double_edit').attr('href', edit);
			$('#greyd-wizard .name_ok').css('display', 'none');
			$('#greyd-wizard .create.button').addClass('disabled');
		}
		else {
			// console.log("template does not exist!");
			$('#greyd-wizard .name_double').css('display', 'none');
			$('#greyd-wizard .name_ok').css('display', 'block');
			$('#greyd-wizard .create.button').removeClass('disabled');
		}
	}

	this.install = function(mode='check_init_setup') {
		// console.log("check_init_setup");

		wizzard.alert_on_leave = true;
		$('.wizzard .wizzard_head .close_wizzard').hide();
		wizzard.open('install');

		$.post(
			wizzard_details.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': wizzard_details.nonce,
				'mode': mode
			}, 
			function(response) {
				wizzard.alert_on_leave = false;
				$('.wizzard .wizzard_head .close_wizzard').show();

				if (response.indexOf('error::') > -1) {
					console.warn( response );
					var tmp = response.split('error::');
					$('.wizzard .error_msg').html(tmp[1]);
					wizzard.setState(0,'error');
				}
				else if (response.indexOf('exit::') > -1) {
					console.info( response );
					wizzard.setState(0,'confirm');
					$('#install_wizzard .install_basics').on("click", function() {
						wizzard.install('install_basics');
					});
				}
				else {
					console.info( response );
					wizzard.setState(0,'success');
					setTimeout(function() {
						wizzard.setState(0,'init');
					}, 1000);
				}
			}
		);
	}

	this.install_mods = function() {
		var result = [
			{ name: 'font', value: -1 },
			{ name: 'color', value: -1 },
			{ name: 'box', value: -1 },
			{ name: 'menu', value: -1 },
		];
		$('.wizzard_option.result').each(function() {
			result[$(this).index()].value = $(this).data('option');
		});
		// console.log("install_mods");
		// console.log(result);

		wizzard.next();
		setTimeout(function() {
			$.post(
				wizzard_details.ajax_url, {
					'action': 'greyd_ajax',
					'_ajax_nonce': wizzard_details.nonce,
					'mode': 'install_mods',
					'data': result,
				}, 
				function(response) {
					if (response.indexOf('error::') > -1) {
						console.warn( response );
						var tmp = response.split('error::');
						$('.wizzard .error_msg').html(tmp[1]);
						wizzard.setState('0-error');
					}
					else {
						console.info( response );
						wizzard.next();
					}
				}
			);
		}, 1000);

	}

	this.install_setup = function() {
		var result = {
			setup: [
				{ name: 'setup_shop', value: $('.wizzard_content #shop').prop('checked') ? 'checked' : '' },
			],
			plugin: [
				{ name: 'plugin_forms', value: $('.wizzard_content #plugin_forms').prop('checked') ? 'checked' : '' },
				{ name: 'plugin_yoast', value: $('.wizzard_content #plugin_yoast').prop('checked') ? 'checked' : '' },
				{ name: 'plugin_mediareplace', value: $('.wizzard_content #plugin_mediareplace').prop('checked') ? 'checked' : '' },
				// { name: 'plugin_borlabs', value: $('.wizzard_content #plugin_borlabs').prop('checked') ? 'checked' : '' },
				// { name: 'plugin_tinymce', value: $('.wizzard_content #plugin_tinymce').prop('checked') ? 'checked' : '' },
				// { name: 'plugin_wpml', value: $('.wizzard_content #plugin_wpml').prop('checked') ? 'checked' : '' },
				// { name: 'plugin_autoptimize', value: $('.wizzard_content #plugin_autoptimize').prop('checked') ? 'checked' : '' },
				{ name: 'plugin_hidelogin', value: $('.wizzard_content #plugin_hidelogin').prop('checked') ? 'checked' : '' },
				{ name: 'plugin_woocommerce', value: $('.wizzard_content #plugin_woocommerce').prop('checked') ? 'checked' : '' },
				{ name: 'plugin_woocommerce-germanized', value: $('.wizzard_content #plugin_woocommerce-germanized').prop('checked') ? 'checked' : '' },
			],
		};
		// console.log("install_setup");
		// console.log(result);

		wizzard.next();
		setTimeout(function() {
			$.post(
				wizzard_details.ajax_url, {
					'action': 'greyd_ajax',
					'_ajax_nonce': wizzard_details.nonce,
					'mode': 'install_setup',
					'data': result,
				}, 
				function(response) {
					if (response.indexOf('error::') > -1) {
						console.warn( response );
						var tmp = response.split('error::');
						$('.wizzard .error_msg').html(tmp[1]);
						wizzard.setState('0-error');
					}
					else {
						console.info( response );
						wizzard.next();
					}
				}
			);
		}, 1000);

	}

	this.install_reset = function() {
		var result = [
			{ name: 'reset_thememods', value: $('.wizzard_content #reset_thememods').prop('checked') ? 'checked' : '' },
			{ name: 'reset_pages', value: $('.wizzard_content #reset_pages').prop('checked') ? 'checked' : '' },
			{ name: 'reset_templates', value: $('.wizzard_content #reset_templates').prop('checked') ? 'checked' : '' },
			{ name: 'reset_posts', value: $('.wizzard_content #reset_posts').prop('checked') ? 'checked' : '' },
			{ name: 'reset_forms', value: $('.wizzard_content #reset_forms').prop('checked') ? 'checked' : '' },
			{ name: 'reset_posttypes', value: $('.wizzard_content #reset_posttypes').prop('checked') ? 'checked' : '' },
			{ name: 'reset_popups', value: $('.wizzard_content #reset_popups').prop('checked') ? 'checked' : '' },
			{ name: 'reset_menus', value: $('.wizzard_content #reset_menus').prop('checked') ? 'checked' : '' },
			{ name: 'reset_media', value: $('.wizzard_content #reset_media').prop('checked') ? 'checked' : '' },
			{ name: 'reset_plugins', value: $('.wizzard_content #reset_plugins').prop('checked') ? 'checked' : '' },
			{ name: 'install_basics', value: $('.wizzard_content #install_basics').prop('checked') ? 'checked' : '' },
		];
		if ( $('.wizzard_content #reset_products').length > 0 )
			result.push( { name: 'reset_products', value: $('.wizzard_content #reset_products').prop('checked') ? 'checked' : '' } );
		if ( $('.wizzard_content #reset_shop_order').length > 0 )
			result.push( { name: 'reset_shop_order', value: $('.wizzard_content #reset_shop_order').prop('checked') ? 'checked' : '' } );
		// console.log(result);

		wizzard.next();
		$.post(
			wizzard_details.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': wizzard_details.nonce,
				'mode': 'install_reset',
				'data': result,
			}, 
			function(response) {
				if (response.indexOf('error::') > -1) {
					console.warn(response);
					var tmp = response.split('error::');
					$('.wizzard .error_msg').html(tmp[1]);
					wizzard.setState('0-error');
				}
				else {
					console.info( response );
					wizzard.next();
				}
			}
		);
	}

	this.install_silent = function(mode, blog_id, callback) {
		$.post(
			wizzard_details.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': wizzard_details.nonce,
				'mode': mode,
				'data': blog_id
			}, 
			function(response) {
				if (response.indexOf('error::') > -1) {
					if (callback) {
						console.warn( response );
						callback.apply(this, ['fail']);
					}
				}
				else {
					if (callback) {
						console.info( response );
						callback.apply(this, ['reload']);
					}
				}
			}
		);
	}

	this.createPost = function(mode, data) {
		var txt = $('.wizzard_content .install_txt');
		$.post(
			wizzard_details.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': wizzard_details.nonce,
				'mode': mode,
				'data': data
			}, 
			function(response) {
				if (response.indexOf('error::') > -1) {
					console.warn(response);
					var tmp = response.split('error::');

					$('#greyd-wizard .error_msg').html(tmp[1]);
					wizzard.setState('0-error');
				}
				else {
					console.info(response);
					var mode = 'success';
					if (response.indexOf('info::') > -1) mode = 'info';
					var tmp = response.split(mode+'::');
					var msg = tmp[1];
					var url = location.href.split("wp-admin");
					var post_id = msg.split('(id ');
					post_id = post_id[1].split(')');
					var edit = url[0]+"wp-admin/post.php?post="+post_id[0]+"&action=edit";

					console.log(msg);
					if (msg.toLowerCase().includes("pop-up")) {
						$('#greyd-wizard .finish_wizzard').attr('href', edit);
					} else {
						$('#greyd-wizard .finish_wizzard').attr('href', edit);
					}
					$('#greyd-wizard .wizzard_box').removeClass('big');
					$(txt[0]).html($(txt[0]).html().replace('%s', data.name)); 
					$(txt[0]).css('opacity', 1); 
					wizzard.setState(11);
					setTimeout(function() { $(txt[1]).css('opacity', 1); }, 100);
				}
			}
		);
	}
}
