/**
 * Greyd.Blocks Frontend Script for Layout Blocks Background Feature.
 *
 * This file is loaded in the frontend only.
 */
(function() {
	
	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;
		
		/**
		 * backgrounds and lazyload
		 * split from theme default.js 'icons' function
		 */
		greyd.backgrounds.init();
		
		/**
		 * lazyload
		 * split from theme default.js 'icons' function
		 * combined with removed 'front-video-lazyload.js'
		 */
		greyd.lazyload.init();

		/**
		 * css animations
		 * refactored from theme default.js 'vc_anim' function
		 */
		greyd.cssAnims.init();

		// console.log("Frontend Backgrounds Script: loaded");
	} );

} )(jQuery);

/**
 * Backgrounds
 */
function onYouTubeIframeAPIReady() {

	greyd.backgrounds.initYoutubeVideos();

	/**
	 * Call theme function aswel because the theme's
	 * 'onYouTubeIframeAPIReady' is overwritten by this.
	 */
	if (typeof icons !== 'undefined' &&
		typeof icons.initYoutubeVideos === 'function') {
		icons.initYoutubeVideos() 
	}

}
greyd.backgrounds = new function() {

	// all backgrounds
	this.bgs = {};

	this.init = function() {

		// check for backgrounds
		if ($('.greyd-background').length == 0) {
			// console.warn("no backgrounds found");
			return;
		}

		// get all backgrounds
		var youtubefound = false;
		$('.greyd-background').each(function() {
			// get background (or parent) id
			var id = $(this).attr('id');
			var that = $(this);
			while (typeof id === 'undefined') {
				// search the next parent with id
				that = that.parent();
				// console.log(that);
				if (that.length == 0) id = "";
				else id = that.attr('id');
			}
			if (id == "") return;

			// get resize behaviour
			var resize = false;
			if ($(this).hasClass('bg_full') || $(this).hasClass('bg_window')) 
				resize = true;
			// get scroll parallax
			var scroll = [];
			$('#'+id+' .bg_vparallax').each(function() {
				scroll.push({ 
					container: this,
					scroll: 'vparallax',
					speed: $(this).data('bg_parallax_speed'),
					enable_mobile: $(this).data('bg_parallax_enable_mobile')
				});
			});
			$('#'+id+' .bg_hparallax').each(function() {
				scroll.push({ 
					container: this,
					scroll: 'hparallax',
					speed: $(this).data('bg_parallax_speed'),
					enable_mobile: $(this).data('bg_parallax_enable_mobile')
				});
			});
			// get video
			var video = false;
			$('#'+id+' .bg_video').each(function() {
				video = {
					src: $(this).children(0).data('bg_video_src'),
					video_id: $(this).children(0).data('bg_video_id'),
					video_aspect: $(this).children(0).data('bg_video_ar'),
					container_id: $(this).children(0).children(0).attr('id'),
				};
				if (video.src == "youtube") youtubefound = true;
			});

			// add
			if (!greyd.backgrounds.bgs[id]) {
				greyd.backgrounds.bgs[id] = { 
					container: this,
					resize: resize,
					scroll: scroll,
					video: video
				};
			}
		});
		if (youtubefound) {
			if (typeof YT === 'undefined') {
				var tag = document.createElement('script');
				tag.src = "https://www.youtube.com/iframe_api";
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
			}
			else this.initYoutubeVideos();
		}

		this.addEvents();
		// console.log('Backgrounds Script: loaded');
	}

	this.initYoutubeVideos = function() {
		// check all backgrounds
		for (var id in greyd.backgrounds.bgs) {
			var bg = greyd.backgrounds.bgs[id];
			// check for youtube video
			if (bg.video && bg.video.src == "youtube") {
				bg.video.player = new YT.Player(bg.video.container_id, {
					height: '100%',
					width: '100%',
					videoId: bg.video.video_id,
					playerVars: {
						autoplay: '1',
						loop: '1',
						controls: '0',
						modestbranding: '1',
						disablekb: '1'
					},
					events: {
						'onReady': greyd.backgrounds.onPlayerReady,
						'onStateChange': greyd.backgrounds.onPlayerStateChange
					}
				});
			}
		}
	}
	this.onPlayerReady = function(event) {
		// console.log("player ready");
		event.target.mute();
		event.target.playVideo();
		greyd.backgrounds.resize();
	}
	this.onPlayerStateChange = function(event) {
		if (event.data == YT.PlayerState.ENDED) {
			event.target.seekTo(0);
			event.target.playVideo();
		}
	}

	this.addEvents = function() {
		// scroll and resize
		$(window).scroll(this.scroll);
		$(window).resize(this.resize);
		this.resize();
	}

	this.resize = function () {
		// console.log("resizing");
		for (var id in greyd.backgrounds.bgs) {
			var bg = greyd.backgrounds.bgs[id];
			if (bg.resize) {
				// console.log("background resizing");
				greyd.backgrounds.resizeBackground(bg);
			}
			if (bg.video) {
				// console.log("video resizing");
				greyd.backgrounds.resizeVideo(bg);
			}
		}
		greyd.backgrounds.scroll();
	}
	this.resizeBackground = function(bg) {
		// console.log(bg.container);
		var margin = -($(bg.container).parent()[0].getBoundingClientRect().left);
		margin += $('body')[0].getBoundingClientRect().left;
		
		//if the margin is greater than half the window-width, chances are the bg sits outside the viewport
		if ( Math.abs(parseInt(margin)) * 2 <= window.innerWidth ) {
			if ( Math.abs(parseInt(margin)) !== 0 ) {
				$(bg.container).css('left', margin);
				$(bg.container).css('right', margin);
			}
		}
	}
	this.resizeVideo = function(bg) {
		// console.log(bg.video);
		var pheight = $(bg.container).css('height').replace("px","");
		var pwidth = $(bg.container).css('width').replace("px","");
		
		var ar = bg.video.video_aspect.split("/");
		ar = ar[0]/ar[1]; // ar = 16/9;
		var height = pheight;
		var width = height * (ar);
		var margin_left = margin_top = 0;

		if (width >= pwidth) {
			margin_left = (pwidth - width) / 2;
		}
		else {
			width = pwidth;
			height = width / (ar);
			margin_top = (pheight - height) / 2;
		}

		// resize
		var iframe = typeof bg.video.container_id === 'undefined' ? $(bg.container).find('iframe') : $("#"+bg.video.container_id);
		console.log(iframe);
		if ( iframe && iframe.length ) {
			iframe.css('height', height+"px");
			iframe.css('width', width+"px");
			iframe.css('margin-left', margin_left+"px");
			iframe.css('margin-top', margin_top+"px");
		}
		else {
			setTimeout( function() {
				iframe = $(bg.container).find('iframe');
				// console.log(iframe);
				bg.video.container_id = iframe.attr('id');
				iframe.css('height', height+"px");
				iframe.css('width', width+"px");
				iframe.css('margin-left', margin_left+"px");
				iframe.css('margin-top', margin_top+"px");
			}, 100 );
		}
	}

	this.scroll = function () {
		// console.log("scrolling");
		var is_mobile = ($('html').hasClass('ios') || $('html').hasClass('android'));
		var viewportTop = $(window).scrollTop();
		var viewportHeight = $(window).height();
		var viewportBottom = viewportTop + viewportHeight;

		for (var id in greyd.backgrounds.bgs) {
			if (greyd.backgrounds.bgs[id].scroll.length > 0) {
				// all parallax layers
				var background = greyd.backgrounds.bgs[id];
				for (var i=0; i<background.scroll.length; i++) {
					// scroll parallax layer
					var bg = background.scroll[i].container;
					var scroll = background.scroll[i].scroll;
					var speed = background.scroll[i].speed;
					var enable_mobile = background.scroll[i].enable_mobile;
					if (!is_mobile || (is_mobile && enable_mobile)) {
						// enable on desktop and check mobile
						var elementTop = $(bg).offset().top;
						var elementHeight = $(bg).outerHeight();
						if (viewportBottom-elementTop > 0 && viewportTop-elementTop < elementHeight) {
							// console.log(viewportBottom-elementTop);
							var delta = (viewportHeight - elementHeight) / 2;
							var pos = ((viewportBottom-elementTop-elementHeight-delta)*(speed/100.0));
							if (scroll == "vparallax") {
								// vertical parallax
								if ($(bg).hasClass('div_parallax')) 
									$(bg).children(0).css('transform', 'translateY('+pos.toFixed(2)+'px)');
								else 
									$(bg).css('background-position-y', pos.toFixed(2)+'px');
							}
							if (scroll == "hparallax") {
								// horizontal parallx
								if ($(bg).hasClass('div_parallax'))
									$(bg).children(0).css('transform', 'translateX('+pos.toFixed(2)+'px)');
								else
									$(bg).css('background-position-x', pos.toFixed(2)+'px');
							}
						}
					}

				}
			}
		}

	}

}

/**
 * Lazyloading.
 * Updated to use intersection observer, no jQuery.
 * - background images
 * - videos
 * - fonts (todo)
 */
greyd.lazyload = new function() {
	
	this.setup = {
		lazyload: 'false',
	};

	this.observer = false;

	this.init = function() {

		// get setup
		if (typeof greyd.setup.lazyload !== 'undefined') {
			this.setup.lazyload = greyd.setup.lazyload;
		}

		// Stop loading if Lazyloading is disabled and there is no video to load.
		if (this.setup.lazyload == 'false' && document.querySelectorAll('video[data-src]:not([src])') === 0) return;

		// check for elements to lazyload
		var elements = document.querySelectorAll('.bg_image:not(.visible), video[data-src]:not([src])');
		if (elements.length === 0) {
			// console.warn("no lazyload elements found");
			return;
		}

		if (!greyd.lazyload.observer) {
			// define the observer
			greyd.lazyload.observer = new IntersectionObserver(
				function onIntersection(entries, opts) {
					// console.log(entries, opts);
					entries.forEach(entry => {

						if (entry.isIntersecting) {

							/**
							 * Background image lazy loading.
							 */
							if (entry.target.classList.contains('bg_image')) {
								var bg = entry.target;
								// console.log(bg);

								if (typeof bg.dataset.srcset !== 'undefined') {
									// get best img from srcset
									var srcset = JSON.parse(bg.dataset.srcset);
									var newset = { 
										src: getComputedStyle(bg, false).backgroundImage.replace('url("', '').replace('")',''), 
										newsrc: '' 
									};
									var width = window.screen.width * window.devicePixelRatio;
									for (var i in srcset) {
										if (i > width && newset.src != srcset[i]) {
											newset.newsrc = srcset[i];
											break;
										}
									}
									if (newset.newsrc == '' && newset.src != srcset['full']) {
										newset.newsrc = srcset['full'];
									}
									// switch images
									if (typeof newset.newsrc !== 'undefined' && newset.newsrc != '') {
										// set image
										bg.style.backgroundImage = 'url('+newset.newsrc+')';
										// load other images with the same src
										elements.forEach( (element) => {
											if (!element.classList.contains('visible')) {
												var src = getComputedStyle(element, false).backgroundImage.replace('url("', '').replace('")','');
												if (src == newset.src) {
													// set image
													element.style.backgroundImage = 'url('+newset.newsrc+')';
													// remove observer
													element.classList.add('visible');
													greyd.lazyload.observer.unobserve(element);
												}
											}
										} );
									}
								}

								// remove from observer
								bg.classList.add('visible');
								greyd.lazyload.observer.unobserve(bg);

							}

							/**
							 * Video lazy loading.
							 * moved from 'front-video-lazyload.js'
							 * 
							 * If the property 'preload' is set to 'auto' or 'none' the video 'src' is
							 * lazyloaded by 'data-src' via an IntersectionObserver.
							 * 
							 * @link https://web.dev/lazy-loading-video/
							 * @see render_block() @ greyd_blocks/inc/render.php
							 * 
							 * @since 1.2.5
							 */
							if ( entry.target.tagName.toLowerCase() == "video" ) {
								// console.log( "Lazy loading video: ", entry.target.dataset.src );
		
								// load entry
								entry.target.src = entry.target.dataset.src;
								entry.target.load();
			
								// remove from observer
								delete entry.target.dataset.src;
								greyd.lazyload.observer.unobserve(entry.target);
							}

						}

					} );
				}, 
				{
					threshold: 0, // percentage of target's visible area. Triggers "onIntersection"
				}
			);
		}

		// add all elements to observer
		elements.forEach( (element) => {
			greyd.lazyload.observer.observe( element );
		} );

		// console.log('Lazyload Script: loaded');
	}

}


/**
 * CSS Animations ported from VC.
 * Updated to use intersection observer, no jQuery.
 */
greyd.cssAnims = new function() {

	this.observer = false;

	this.init = function() {

		// check for animations
		var objs = document.querySelectorAll('.animate-when-almost-visible');
		if (objs.length === 0) {
			// console.warn("no css animations found");
			return;
		}

		if (!greyd.cssAnims.observer) {
			// define the observer
			greyd.cssAnims.observer = new IntersectionObserver(
				function onIntersection(entries, opts) {
					// console.log(entries, opts);
					entries.forEach(entry => {
						// triggered class
						var triggerClass = "start-animation";
						if (entry.isIntersecting && !entry.target.classList.contains(triggerClass)) {
							// console.log("trigger animation")
							entry.target.classList.add(triggerClass);
							entry.target.classList.add("animated");
							// remove from observer
							greyd.cssAnims.observer.unobserve(entry.target);
						}
					} );
				}, 
				{
					threshold: 0, // percentage of target's visible area. Triggers "onIntersection"
				}
			);
		}

		// add all elements with css animation to observer
		objs.forEach( (element) => {
			greyd.cssAnims.observer.observe( element );
		} );

		// console.log('css Animations Script: loaded');
	}

};
