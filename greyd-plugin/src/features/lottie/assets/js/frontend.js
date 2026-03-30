/**
 * Greyd.Blocks Frontend Script for Lottie Animations Feature.
 *
 * This file is loaded in the frontend only.
 */
(function() {
	
	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;
		
		/**
		 * lottie
		 * split from theme default.js 'icons' function
		 */
		greyd.lottie.init();

		// console.log("Frontend Lottie Script: loaded");
	} );

} )(jQuery);


/**
 * Lottie/Bodymovin
 */
greyd.lottie = new function() {

	this.className = '.lottie-animation';
	this.setup = {
		lottie: { mode: 'default' },
		lazyload: 'false',
	};
	this.anims = {};

	this.init = function() {
		
		// check for animations
		if ( $(this.className).length == 0 ) {
			// console.warn("no lottie animations found");
			return;
		}

		// get setup
		if (typeof greyd.setup.lottie !== 'undefined') {
			this.setup.lottie = greyd.setup.lottie;
		}
		if (typeof greyd.setup.lazyload !== 'undefined') {
			this.setup.lazyload = greyd.setup.lazyload;
		}

		// Stop loading if Lottie is disabled
		if (this.setup.lottie.mode == 'disable') return;

		// get animations
		if ( typeof bodymovin === 'undefined' && this.setup.lottie.mode === 'lazy' ) {
			// lazyload lottie
			var is_mobile = ($('html').hasClass('ios') || $('html').hasClass('android'));
			if (!is_mobile || this.setup.lottie.mobile === "true") {
				var libsrc = this.setup.lottie.src;
				setTimeout(function() {
					$.getScript(libsrc, function() {
						// console.log("lottie lib loaded");
						greyd.lottie.initIcons();
						greyd.lottie.scroll();
					});
				}, this.setup.lottie.time*1000);
			}
			this.addEvents();
		} 
		else {
			// load now
			this.initIcons();
		}

		// console.log('lottie Script: loaded');
	}
	
	this.initIcons = function () {
		if (typeof bodymovin === 'undefined') return;

		$('body').trigger('lottieLoaded');
		
		$(this.className).each(function() {
			// console.log($(this).attr('id'));
			var id = $(this).attr('id');
			var src = $(this).data('src');
			var anim = $(this).data('anim');
			if (!anim) {
				anim = 'auto';
				if ($(this).hasClass('hover')) anim = 'hover';
				if ($(this).hasClass('visible')) anim = 'visible';
				if ($(this).hasClass('seek_scroll')) anim = 'seek_scroll';
				if ($(this).hasClass('seek_cursor')) anim = 'seek_cursor';
				if ($(this).hasClass('seek_cursor')) anim = 'seek_cursor_horizontal';
				if ($(this).hasClass('toggle')) anim = 'toggle';
				if ($(this).hasClass('click')) anim = 'click';
			}
			greyd.lottie.addIcon( {
				'icon': id,
				'src': src,
				'anim': anim
			} );
			$(this).removeAttr('data-src');
		});
	}
	this.addIcon = function(config) { 
		// console.log(config);
		if (typeof bodymovin === 'undefined') return;
		if (!config.icon) return;

		if (!this.anims[config.icon]) {
			if ( this.setup.lazyload === 'true' ) {
				// lazyload animation
				this.anims[config.icon] = { 'status': "lazy", ...config };
			}
			else {
				// load now
				this.loadIcon( config );
			}
		}
	}
	this.loadIcon = function(config) { 
		// console.log(config);
		var { icon, src, anim } = config;
		this.anims[icon] = bodymovin.loadAnimation( {
								container: document.getElementById(icon),
								renderer: 'svg',
								loop: true,
								autoplay: anim == "auto",
								path: src
							} );
		this.anims[icon].setSubframe(false);
		this.anims[icon].addEventListener('DOMLoaded', function(e) { 
			// console.log('element loaded'); 
			$('#'+icon).parent().find('.lottie-animation-placeholder').hide();
			$('#'+icon).css('display', 'inline-block');

			if ( anim != "auto" && typeof LottieInteractivity !== 'undefined' ) {
				// add interactivity
				var { mode, actions } = greyd.lottie.getInteractivity( anim, greyd.lottie.anims[icon] );
				if ( mode && actions.length > 0 ) {
					// create
					greyd.lottie.anims[icon].interactivity = LottieInteractivity.create({
						player: greyd.lottie.anims[icon],
						mode: mode,
						actions: actions
					});
					if ( mode == 'scroll' ) {
						// init scroll position
						window.dispatchEvent(new CustomEvent('scroll'));
					}
				}
			}
		});
	}
	
	this.getInteractivity = function(type, anim) {
		
		var mode = false;
		var actions = [];
		switch ( type ) {
			case 'visible':
				mode = 'scroll';
				actions = [
					{
						visibility: [0, 0.01],
						type: 'stop',
						frames: [0],
					},
					{
						visibility: [0.01, 0.99],
						type: 'play',
					},
					{
						visibility: [0.99, 1],
						type: 'stop',
						frames: [0],
					},
				];
				break;
			case 'hover':
				mode = 'cursor';
				actions = [
					{
						type: "pauseHold",
					}
				];
				break;
			case 'toggle':
				mode = 'cursor';
				actions = [
					{
						type: "toggle",
					}
				];
				break;
			case 'click':
				mode = 'cursor';
				actions = [
					{
						type: "click",
						forceFlag: true,
					}
				];
				break;
			case 'seek_scroll':
				mode = 'scroll';
				actions = [
					{
						visibility: [0, 1],
						type: 'seek',
						frames: [0, anim.totalFrames],
					},
				];
				break;
			case 'seek_cursor':
				mode = 'cursor';
				actions = [
					{
						position: { x: [0, 1], y: [0, 1] },
						type: 'seek',
						frames: [0, anim.totalFrames],
					}
				];
				break;
			case 'seek_cursor_horizontal':
				mode = 'cursor';
				actions = [
					{
						position: { x: [0, 1], y: [-1, 2] },
						type: 'seek',
						frames: [0, anim.totalFrames],
					}
				];
				break;
		}

		return {
			mode: mode,
			actions: actions
		}
	}

	/**
	 * @since 2.15.0 used only for lazy loading
	 */
	this.addEvents = function() {
		// scroll and resize
		$(window).scroll(this.scroll);
		$(window).resize(this.scroll);
		this.scroll();
	}

	this.scroll = function () {
		// console.log("scrolling");
		var viewportTop = $(window).scrollTop();
		var viewportHeight = $(window).height();
		var viewportBottom = viewportTop + viewportHeight;
		
		var loading = [];
		for (var icon in greyd.lottie.anims) {
			var anim = greyd.lottie.anims[icon];
			if (anim.status == "lazy") {
				var wrapper = $('#'+icon).parent();
				var elementTop = wrapper.offset().top - 50;
				var elementBottom = elementTop + wrapper.outerHeight() + 100;
				if (elementBottom > viewportTop && elementTop < viewportBottom) {
					// console.log("lazyload "+icon);
					loading.push(anim.src);
					greyd.lottie.loadIcon(anim);
				}
			}
		}

		// load other anims with the same src
		if (loading.length > 0) {
			for (var icon in greyd.lottie.anims) {
				var anim = greyd.lottie.anims[icon];
				if (anim.status == "lazy") {
					if (loading.indexOf(anim.src) > -1) {
						// console.log("found one more");
						greyd.lottie.loadIcon(anim);
					}
				}
			}
		}
	}

}
