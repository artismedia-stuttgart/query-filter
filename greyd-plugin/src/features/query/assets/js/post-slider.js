/**
 * Query Loop Scripts
 * - post-slider
 * - sorting
 * - table sorting
 * 
 * Moved from classic greyd default.js
 */

var greyd = greyd || {};

( function() {
	jQuery( function() {
		if ( typeof $ === 'undefined' ) $ = jQuery;

		greyd.query.init();

		console.log( "Query Scripts: loaded" );
	} );
} )( jQuery );


/**
 * init greyd.query var
 */
greyd.query = greyd.query || {};

/**
 * init all query features
 */
greyd.query.init = function() {
	greyd.query.slider.init();
	greyd.query.sorting.init();
	greyd.query.table.init();
}


/**
 * slider features
 * 
 * was global default.js variable 'post'
 */
greyd.query.slider = new function() {
	
	this.loaded = false;
	
	this.init = function() {
		this.addEvents();
		// console.log('Posts Pagination Script: loaded');
	}
	
	this.addEvents = function() {
		var wrapper = $(".greyd-posts-slider.js:not([data-init])");
		if (wrapper.length > 0) {

			// Create an Intersection Observer
			const observer = new IntersectionObserver((entries) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						// The slider is in the viewport
						entry.target.dataset.inViewport = true;
					} else {
						// The slider is out of the viewport
						entry.target.dataset.inViewport = false;
					}
				});
			}, { threshold: 0 });

			wrapper.each(function() {
				if ($(this).hasClass('autoplay')) {
					var time = $(this).data('interval')*1000;
					var that = this;
					$(this).timer = setInterval(function() {
						if (!$(that).hasClass('pause') && !$(that).hasClass('forcePause')) {
							greyd.query.slider.paginateNext($(that));
						}
					}, time);
					// $(this).on('mouseenter', function() {
					//     if ( !$(that).hasClass('pause') ) {
					//         $(that).addClass('pause');
					//         $(this).on('mouseleave', function() {
					//             $(that).removeClass('pause');
					//         });
					//     }
					// });
				}
				
				$(this).off('next').on('next', function() {
					greyd.query.slider.paginateNext(this);
				});
				$(this).off('prev').on('prev', function() {
					greyd.query.slider.paginatePrevious(this);
				});
				$(this).off('first').on('first', function() {
					greyd.query.slider.paginate(this, 1);
				});
				$(this).off('last').on('last', function() {
					var pages = $(this).children('.query-pages-wrapper').children('.query-page');
					greyd.query.slider.paginate(this, pages.length);
				});
				$(this).off('stop').on('stop', function() {
					$(this).addClass('forcePause pause');
				});
				$(this).off('start').on('start', function() {
					$(this).removeClass('forcePause pause');
				});

				/**
				 * We insert the event listeners using PHP for more reliability.
				 * @since 2.7.0
				 */
				// // next/previous
				// $(this).find('.pagination > a, a.pgn_arrows, .pgn_arrows a').off('click').on("click", function(e) {
				// 	var wrapper = $(this).closest('.greyd-posts-slider');
				// 	wrapper.trigger( $(this).data('pagelink') );
				// 	wrapper.trigger('stop');
				// });
				// console.log(
				// 	$(this).find('.pagination > a, a.pgn_arrows, .pgn_arrows a'),
				// 	$(this).find('.pagination > .pgn_numbers > a')
				// );
				
				// // numbers
				// $(this).find('.pagination > .pgn_numbers > a').off('click').on("click", function(e) {
				// 	var wrapper = $(this).closest('.greyd-posts-slider');
				// 	greyd.query.slider.paginate( wrapper, $(this).data('pagelink') );
				// 	wrapper.trigger('stop');
				// });

				// core pagination
				$(this).closest('.wp-block-query').find('.wp-block-query-pagination').each(function() {
					greyd.query.slider.addCorePagination(this);

				});
	
				// touch support
				$(this).off('touchstart').on('touchstart', function(e) { 
					greyd.query.slider.touchStart(this, e);
					$(this).trigger('stop');
				});
				$(this).off('touchmove').on('touchmove', function(e) {
					greyd.query.slider.touchMove(this, e);
					$(this).trigger('stop');
				});

				// add onload event to images inside slider to re-calculate height
				$(this).find('img').each(function() {
					// console.log(this);
					$(this).off('load').on('load', function(e) { 
						// console.log("img loaded");
						if ( greyd.query.slider.loaded ) greyd.query.slider.calc();
					});
				});

				$(this).attr('data-init', 'true');

				// if url param is set, paginate to this page
				var queryid = $(this).data('query-id');
				if ( queryid > -1 ) {
					var urlparams = new URLSearchParams( window.location.search );
					if ( urlparams.has('query-'+queryid+'-page') ) {
						greyd.query.slider.paginate(this, parseInt(urlparams.get('query-'+queryid+'-page')));
					}
				}
	
				// Observe the slider element
				observer.observe(wrapper[0]);
			});

			// size wrapper
			window.addEventListener("resize", function() { 
				greyd.query.slider.calc()
			});
			setTimeout(function(){
				greyd.query.slider.calc()
			}, 100);
			
			$(document).keydown(function(e){
				// arrow left
				if (e.keyCode == 37) {
					var wrapper = $(".greyd-posts-slider.js:hover");
					if ( wrapper.length > 0 ) {
						wrapper.trigger('prev');
						wrapper.trigger('stop');
					}
					// return false;
				}
				// arrow right
				if (e.keyCode == 39) {
					var wrapper = $(".greyd-posts-slider.js:hover");
					if ( wrapper.length > 0 ) {
						wrapper.trigger('next');
						wrapper.trigger('stop');
					}
					// return false;
				}
			});
		}
	}

	this.calc = function() {
		$('.greyd-posts-slider.js > .query-pages-wrapper').each(function(e) {
			var pages   = $(this).children('.query-page');
			var wrapper = $(this).closest('.greyd-posts-slider');
			var current = wrapper.data('currentpage');

			// console.log(offset);
			// $(this).finish().animate({ scrollLeft: offset }, 0);
			
			// get slider height
			var height = $(this)[0].offsetHeight;
			var newHeight = null;

			/**
			 * Adjust the slider height.
			 * 
			 * The naming of the options are misleading:
			 * (1) 'auto'   =>  'as high as highest slide'
			 *                  This is called auto, because the height of the slider
			 *                  is automatically this way due to 'display: flex'. We
			 *                  do not need to do anything in JS.
			 * (2) 'max'    =>  'adjust automatically'
			 *                  We need to adjust the slider's height to the height
			 *                  of the current slide each step.
			 * (3) 'custom' =>  'manual height'
			 *                  Set the height to a manual input.
			 */
			if ( wrapper.data('height') == 'auto' ) {
				// (1)
			}
			else if ( wrapper.data('height') === 'max') {
				// (2)
				newHeight = pages.length > 1 ? pages[current-1].offsetHeight+"px" : null;
			}
			else {
				// (3)
				newHeight = wrapper.data('height');
				if ( screen.width <= 767 && typeof wrapper.data('height_mobile') != 'undefined' && wrapper.data('height_mobile') != 'max' ) {
					newHeight = wrapper.data('height_mobile');
				}
			}

			// set height for new slider
			if ( newHeight && newHeight != height ) {
				$(this).css('height', newHeight);
			}
		});
		
		// set loaded state to only re-calculate height after initial calc is done
		greyd.query.slider.loaded = true;
	}

	/**
	 * When a pagination link is clicked, this function is called to paginate.
	 * @since 2.7.0
	 * 
	 * @param {object} e        The event object.
	 * @param {object} el       The element that was clicked.
	 * @param {number} pagelink The page number to paginate to.
	 */
	this.onPaginateClick = function(e, el, pagelink) {
		// console.log("paginate click", e, el, pagelink);
		var wrapper = $(el).closest('.greyd-posts-slider');
		greyd.query.slider.paginate( wrapper, pagelink );
		wrapper.trigger('stop');
	}
	this.paginateNext = function(wrapper) {
		// console.log("next page");
		var newpage = parseInt($(wrapper).data('currentpage')) + 1;
		this.paginate($(wrapper), newpage);
	}
	this.paginatePrevious = function(wrapper) {
		// console.log("previous page");
		var newpage = parseInt($(wrapper).data('currentpage')) - 1;
		this.paginate($(wrapper), newpage);
	}
	this.paginate = function(wrapper, newpage) {

		// function to check is a string is numeric
		const isNumeric = (str) => {
			if (typeof str != "string") return false;
			return !isNaN(str) && !isNaN(parseFloat(str));
		}

		// get the new page number
		if ( isNumeric(newpage) ) {
			newpage = parseInt(newpage);
		}
		// if not numeric, like 'next' or 'prev', calculate the new page number
		else {
			if ( newpage == 'next' ) {
				newpage = parseInt($(wrapper).data('currentpage')) + 1;
			} else if ( newpage == 'prev' ) {
				newpage = parseInt($(wrapper).data('currentpage')) - 1;
			} else if ( newpage == 'first' ) {
				newpage = 1;
			} else if ( newpage == 'last' ) {
				newpage = $(wrapper).children('.query-pages-wrapper').children('.query-page').length;
			}
		}

		var current = $(wrapper).data('currentpage');
		var pages   = $(wrapper).children('.query-pages-wrapper').children('.query-page');

		// return if no need to go to the next page
		if (current == newpage) return;
		if ( $(wrapper).hasClass('loop') ) {
			if (newpage < 1) newpage = pages.length;
			if (newpage > pages.length) newpage = 1;
		}
		else {
			if (newpage < 1) return;
			if (newpage > pages.length) return;
		}

		// get html elements
		const resultsWrapper = $(wrapper).children('.query-pages-wrapper');
		const activeStep = pages[newpage-1];
		const prev = $(wrapper).find('.pgn_previous');
		const next = $(wrapper).find('.pgn_next');

		// get animation settings
		const config = {
			animation: $(wrapper).data('animation'),
			height: $(wrapper).data('height'),
			heightMobile: $(wrapper).data('height_mobile'),
			offsetLeft: activeStep.offsetLeft,
			scrollToTop: $(wrapper).hasClass('slider_scroll_top'),
			/**
			 * Speed of the animation.
			 * 
			 * @since 2.3.0 Added the global variable 'greydSliderSpeed' to enable
			 *              users to set the speed of the slider in the global scope.
			 */ 
			speed: $(wrapper).data('duration') ?? ( typeof greydSliderSpeed !== "undefined" ? greydSliderSpeed : 200 )
		};

		if ( config.animation == 'none' ) {
			config.speed = 0;
		}
		
		// update html attributes and classes
		$(wrapper).data('currentpage', newpage);
		pages.removeClass('is-current is-prev is-next');
		$(pages[newpage-2]).addClass('is-prev');
		$(activeStep).addClass('is-current');
		$(pages[newpage]).addClass('is-next');
		
		// update url param
		var queryid = $(wrapper).data('query-id');
		if ( queryid > -1 && $(wrapper).data('set-url') ) {
			var urlparams = new URLSearchParams(window.location.search);
			urlparams.set( 'query-'+queryid+'-page', newpage );
			window.history.replaceState( null, null, '?'+urlparams.toString() );
		}

		/**
		 * Adjust the slider height.
		 * 
		 * The naming of the options are misleading:
		 * (1) 'auto'   =>  'as high as highest slide'
		 *                  This is called auto, because the height of the slider
		 *                  is automatically this way due to 'display: flex'. We
		 *                  do not need to do anything in JS.
		 * (2) 'max'    =>  'adjust automatically'
		 *                  We need to adjust the slider's height to the height
		 *                  of the current slide each step.
		 * (3) 'custom' =>  'manual height'
		 *                  Set the height to a manual input.
		 */
		let newHeight;
		if ( config.height == 'auto' ) {
			// (1)
		}
		else if ( config.height === 'max') {
			// (2)
			newHeight = activeStep.offsetHeight;
		}
		else {
			// (3)
			newHeight = config.height;
			if (
				screen.width <= 767
				&& typeof config.heightMobile != 'undefined'
				&& config.heightMobile != 'max'
			) {
				newHeight = config.heightMobile;
			}
		}

		// animate slider
		if ( config.animation == 'fade' ) {

			// fade out
			$(resultsWrapper).finish().animate({ opacity: 0 }, config.speed);
			setTimeout( () => {

				/**
				 * @since 2.7.0 set the height directly and only animate the
				 * scroll x position.
				 * This is necessary to prevent the slider from jumping around
				 * which leads to errors scrolling to top, especially on mobile
				 * and safari browsers.
				 */
				if ( config.scrollToTop && newHeight ) {
					resultsWrapper.css('height', newHeight);
				}
				
				// scroll to new position
				$(resultsWrapper).finish().animate({ scrollLeft: config.offsetLeft }, 0);
				
				// fade in
				$(resultsWrapper).finish().animate({
					opacity: 1,
					...( !config.scrollToTop )  && newHeight ? { height: newHeight } : {}
				}, config.speed);
			}, config.speed );
		}
		else if ( config.animation == 'cover' ) {
			/**
			 * @since 2.7.0 set the height directly and only animate the
			 * scroll x position.
			 * This is necessary to prevent the slider from jumping around
			 * which leads to errors scrolling to top, especially on mobile
			 * and safari browsers.
			 */
			if ( config.scrollToTop && newHeight ) {
				resultsWrapper.css('height', newHeight);
			} else if ( newHeight )  {
				$(resultsWrapper).finish().animate({ height: newHeight }, config.speed, 'swing');
			}
			// ...the rest of cover-flow animation is entirely handled by CSS
		}
		else {
			/**
			 * @since 2.7.0 set the height directly and only animate the
			 * scroll x position.
			 * This is necessary to prevent the slider from jumping around
			 * which leads to errors scrolling to top, especially on mobile
			 * and safari browsers.
			 */
			if ( config.scrollToTop && newHeight ) {
				resultsWrapper.css('height', newHeight);
			}
			console.log( config, newHeight );
			$(resultsWrapper).finish().animate({
				scrollLeft: config.offsetLeft,
				...( !config.scrollToTop ) && newHeight ? { height: newHeight } : {}
			}, config.speed, 'swing');
		}

		// Modify the scroll-to-top logic
		if (config.scrollToTop && wrapper[0].dataset.inViewport === "true") {
			const offsetTop = $(wrapper).offset().top - 100;
			if ($(document).scrollTop() > offsetTop) {
				// backward compatibility with classic theme
				if (typeof nav !== 'undefined') {
					console.log("scroll to top with nav", nav);
					nav.scrollTo(offsetTop);
				} else {
					window.scrollTo({ top: offsetTop, behavior: 'auto' });
				}
			}
		}
		
		// update the pagination
		var pg_wrapper = $(wrapper).find('.pagination');
		if ( pg_wrapper.length ) {
			var pg_pages = $(pg_wrapper).find('.pgn_numbers').children();
			var iconnormal = $(pg_wrapper).data('iconnormal') ?? $(pg_wrapper).find('.pgn_numbers').data('iconnormal');
			var iconactive = $(pg_wrapper).data('iconactive') ?? $(pg_wrapper).find('.pgn_numbers').data('iconactive');
			var imgnormal = $(pg_wrapper).data('imgnormal') ?? $(pg_wrapper).find('.pgn_numbers').data('imgnormal');
			var imgactive = $(pg_wrapper).data('imgactive') ?? $(pg_wrapper).find('.pgn_numbers').data('imgactive');
			var maxnum = $(pg_wrapper).data('maxnum') ?? $(pg_wrapper).find('.pgn_numbers').data('maxnum') ?? false;
	
			for (var i=0; i<pg_pages.length; i++) {
				// console.log( "pagination", $(pg_pages[i]) );
				var p = $(pg_pages[i]);
				if (!$(p).hasClass('pgn_number')) p = $(p).find('.pgn_number');
				if ($(pg_pages[i]).data('pagelink') == current) {
					$(p).removeClass('pgn_current');
					if (typeof iconactive !== 'undefined') $(p).removeClass(iconactive);
					if (typeof iconnormal !== 'undefined') $(p).addClass(iconnormal);
					if (typeof imgnormal !== 'undefined') $(p).children().attr('src', imgnormal);
				}
				if ($(pg_pages[i]).data('pagelink') == newpage) {
					$(p).addClass('pgn_current');
					if (typeof iconnormal !== 'undefined') $(p).removeClass(iconnormal);
					if (typeof iconactive !== 'undefined') $(p).addClass(iconactive);
					if (typeof imgactive !== 'undefined') $(p).children().attr('src', imgactive);
				}
				if (maxnum !== false) {
					var page = $(pg_pages[i]).data('pagelink');
					$(pg_pages[i]).removeClass('hidden dots-before dots-after');
					if ( maxnum == 0 && page == newpage ) {
						if ( page == 1 ) {
							$(pg_pages[i]).addClass('dots-after');
						}
						if ( page == pg_pages.length ) {
							$(pg_pages[i]).addClass('dots-before');
						}
					}
					if ( page > 1 && page < pg_pages.length ) {
						$(pg_pages[i]).removeAttr('aria-hidden');
						if ( page < newpage-maxnum || page > newpage+maxnum ) {
							$(pg_pages[i]).addClass('hidden');
							$(pg_pages[i]).attr('aria-hidden', true);
						}
						if ( page > 2 && page == newpage-maxnum ) {
							$(pg_pages[i]).addClass('dots-before');
						}
						if ( page < pg_pages.length-1 && page == newpage+maxnum ) {
							$(pg_pages[i]).addClass('dots-after');
						}
					}
				}
			}
		}

		// update the arrows
		$(prev).removeClass('pgn_current');
		if (newpage == 1) $(prev).addClass('pgn_current');
		$(next).removeClass('pgn_current');
		if (newpage == pages.length) $(next).addClass('pgn_current');

		// trigger custom JS event
		$(wrapper).trigger("greyd_slider_paginate", [newpage] );
	}
	 
	this.xDown = null; 
	this.touchStart = function(el, evt) {
		greyd.query.slider.xDown = evt.originalEvent.touches[0].clientX;
	}
	this.touchMove = function(el, evt) {
		if (!greyd.query.slider.xDown) {
			return;
		}
		var xUp = evt.originalEvent.touches[0].clientX;    
		var xDiff = greyd.query.slider.xDown - xUp;    
		if (xDiff > 50) {
			// console.log("scroll right");
			$(el).trigger('next');
			$(el).trigger('stop');
			greyd.query.slider.xDown = null; 
		}
		else if (xDiff < -50)  {
			// console.log("scroll left");
			$(el).trigger('prev');
			$(el).trigger('stop');
			greyd.query.slider.xDown = null; 
		}
	}
	
	this.triggerNext = function(el) {
		// console.log(el);
		$(el).closest('.wp-block-query').children('.greyd-posts-slider.js').trigger('next');
	}
	this.triggerPrevious = function(el) {
		// console.log(el);
		$(el).closest('.wp-block-query').children('.greyd-posts-slider.js').trigger('prev');
	}
	this.triggerIndex = function(el, index) {
		// console.log(el);
		$(el).closest('.wp-block-query').children('.greyd-posts-slider.js').each(function() {
			greyd.query.slider.paginate(this, index);
		});
	}
	
	this.addCorePagination = function(el) {

		let allPages = $(el).closest('.wp-block-query').children('.greyd-posts-slider.js').find('.query-page');

		if ( ! allPages || allPages.length < 2 ) return;
		

		// trigger function - also handles 'current' attributes on links
		const trigger = ( mode, element, index = false ) => {
			var wrapper = $(el).closest('.wp-block-query').children('.greyd-posts-slider.js');
			var current = $(wrapper).data('currentpage');
			var pages   = $(wrapper).find('.query-page');
			var isLoop  = $(wrapper).hasClass('loop');
			if (mode == 'previous') {
				current--;
				greyd.query.slider.triggerPrevious(element);
				if (current < 1) {
					if (isLoop) current = pages.length;
					else return;
				}
			}
			if (mode == 'next') {
				current++;
				greyd.query.slider.triggerNext(element);
				if (current > pages.length) {
					if (isLoop) current = 1;
					else return;
				}
			}
			if (mode == 'index') {
				current = index;
				greyd.query.slider.triggerIndex(element, index);
			}
			// set 'current' attributes
			$(el).find('.wp-block-query-pagination-numbers .page-numbers:not(.dots)').each( (j, number) => {
				$(number).removeClass('current');
				$(number).removeAttr('aria-current');
				if ($(number).data('page') == current) {
					$(number).addClass('current');
					$(number).attr('aria-current', 'page');
				}
			})
		};

		// previous
		var previous = $(el).find('.wp-block-query-pagination-previous');
		previous.removeAttr("href");
		previous.off('click').on('click', function(e) { trigger('previous', this) } );

		// next
		var next = $(el).find('.wp-block-query-pagination-next');
		next.removeAttr("href");
		next.off('click').on('click', function(e) { trigger('next', this) } );

		// numbers
		var numbers = $(el).find('.wp-block-query-pagination-numbers .page-numbers:not(.dots)');
		numbers.each( (i, num) => {

			// get all elements attributes
			var attrs = {};
			$.each($(num)[0].attributes, function(idx, attr) {
				attrs[attr.nodeName] = attr.nodeValue;
			});
			// get index
			let index = i+1;
			if ( attrs['href'] ) {
				// var params = Object.fromEntries((new URLSearchParams(attrs['href'])).entries())
				// Object.keys(params).forEach( (key) => {
				// 	if ( key.indexOf('query-') == 0 && key.indexOf('-page') > 0 )
				// 		index = parseInt(params[key]);
				// });
				delete attrs['href'];
			}
			// make onclick event
			attrs['data-page'] = index;
			// attrs['click'] = function(e) { trigger('index', this, index) };
			attrs['onclick'] = "greyd.query.slider.triggerIndex(this, "+index+")";

			// make new link with attributes
			$(num).replaceWith(function() {
				const text = $(this).contents();
				return $("<a />", attrs).append( text );
			});
		});

	}

}

/**
 * sort posts in frontend via dropdown
 * 
 * was global default.js variable 'sorting'
 */
greyd.query.sorting = new function() {

	this.init = function() {
		// return if not needed
		if ($(".greyd-posts-slider").length === 0) return false;
		
		// normal pages
		if (!($(".greyd-posts-slider.js").length === 0)) {
			this.addEvents();
		}
		// search page
		else {
			if ($('.greyd-posts-slider').attr("live-search") == "true") {
				return false
			} else {
				this.startQuery();
			}
		}
	}

	this.addEvents = function() {
		const sortingDropdown = $('.greyd-posts-slider').find('.sorting');
		sortingDropdown.each(function() {
			var wrapper = $(this);
			var select  = wrapper.find("select");
			var slider  = wrapper.parent();
			var results = slider.find($(".query-post"));

			// sort and redistribute search results
			select.off("change").on("change", function() {
				console.log(slider);
				console.log(results);
				var sorted_elements = greyd.query.sorting.sortElements($(this).val(), results);
				// console.log(sorted_elements);
				greyd.query.sorting.distributeElements(sorted_elements, slider);
			});
		});
		
		$('[id^="filter_"]').each(function() {
			var results = $(this).siblings('[id^="slider_"]').find($(".query-post"));
			if (results) {
				var select = $(this).find("select");
				var filter = select.val();
				$(results).each(function() {
					greyd.query.sorting.filterElements(this, filter);
				});
				select.off("change").on("change", function() {
					var filter = $(this).val();
					$(results).each(function() {
						greyd.query.sorting.filterElements(this, filter);
					});
				});
			}
		});
	}

	this.filterElements = function(el, filter) {
		var vals = JSON.parse(decodeURIComponent($(el).data('filter')));
		filter = parseInt(filter);
		if (filter == null || filter == -1 || vals.indexOf(filter) != -1)
			$(el).css('display', 'block');
		else
			$(el).css('display', 'none');
	}

	this.sortElements = function(mode, search_results) {

		search_results.sort(function (a, b) {

			if (~mode.indexOf("title")) {
				// propably do that via php...
				// var compA = a.dataset.title.toLowerCase().replace(/[^a-zA-Z0-9 ]/g, "");
				// var compB = b.dataset.title.toLowerCase().replace(/[^a-zA-Z0-9 ]/g, "");
				var compA = a.dataset.title;
				var compB = b.dataset.title;
			}
			else if (~mode.indexOf("date")) {
				var compA = a.dataset.date;
				var compB = b.dataset.date;
			}
			else if (~mode.indexOf("views")) {
				// propably do that via php...
				// var compA = (a.dataset.postviews != 0) ? parseInt(a.dataset.postviews) : 0; 
				// var compB = (b.dataset.postviews != 0) ? parseInt(b.dataset.postviews) : 0;
				var compA = parseInt(a.dataset.postviews); 
				var compB = parseInt(b.dataset.postviews);
			}
			//compare
			if (~mode.indexOf("ASC")) {
				return ((compA < compB) ? -1 : ((compA > compB) ? 1 : 0));
			}
			else if (~mode.indexOf("DESC")) {
				return ((compA > compB) ? -1 : ((compA < compB) ? 1 : 0));
			}
		});  
		return search_results;
	}
	this.distributeElements = function(sorted_elements, parent) {
		var posts_per_page = $(".query-pages-wrapper").data("ppp");
		var pages  = $(".query-page").length;
		var i = 0;

		for (var x = 1; x <= pages; x++) {
			var limit_per_page = 1;
			for (; i < sorted_elements.length;) {
				
				parent.find($('.query-page[data-page="' + x + '"]')).append(sorted_elements[i]);
				i++;
				if (limit_per_page == posts_per_page) {
					break;
				}
				limit_per_page++;
			}                
		} 
	}
	this.startQuery = function() {
		const sortingDropdown = $('.greyd-posts-slider').find('.sorting');
		sortingDropdown.find("select").off("change").on('change', function () {
			var url = $(this).val(); // get selected value
			if (url) { // require a URL
				window.location = url; // redirect
			}
			return false;
		});
	}
	
}

/*
 * jq.TableSort -- jQuery Table sorter Plug-in
 * Copyright (c) 2017 Dmitry Zavodnikov
 * Licensed under the MIT License
 * 
 * was global default.js variable 'tablesort'
 */
greyd.query.table = new function() {
	
	this.init = function() {
		
		// do not init if classic theme is detected
		if ( typeof tablesort !== 'undefined' ) return;

		// return if not needed
		if ( $(".posts_table").length === 0 ) return false;

		this.config  = {
			styles: {
				'sort':     'sortable', 
				'asc':      'ascending', 
				'desc':     'descending', 
				'unsort':   'unsorted'
			},
			defaultColumn: 0,
			defaultOrder: 'asc',
			selector: function(tbody, column) {
				var groups = [];
				$.each($(tbody).find('tr'), function(index, tr) {
					var td = $(tr).find('td')[column];
					groups.push({
						'elem': [tr], 
						'text': $(td).text()
					});
				});
				return groups;
			},
			comparator: function(a, b) {
				function convertToNum(x) { return parseFloat( x.replace(',','.') ); }
				var regex = /^[0-9\s.,]+$/;
				a = a.text, b = b.text;
				if ( a.match(regex) && b.match(regex) ) return convertToNum(b) - convertToNum(a);
				return a.localeCompare(b);
			}
		};
		
		$(".posts_table").each( function(i, wrapper) {
			// Add click listener to the header.
			$.each(greyd.query.table.getSortableTableHeaders(wrapper), function(j, th) {
				$(th).off("click").on("click", function(event) {
					var clickColumn = $.inArray(event.currentTarget, greyd.query.table.getAllTableHeaders( $(this).closest("table") ));
					
					greyd.query.table.changeOrder(wrapper, clickColumn);
				});
				// $(th).append("<span class='icon'><span></span><span></span></span>"); // already added via php
			});
			// Table sort on load
			//greyd.query.table.changeOrder(table, greyd.query.table.config.defaultColumn);
		});
	}
	
	this.getAllTableHeaders = function(wrapper) {
		return $(wrapper).find('thead > tr > th');
	}
	
	this.getNthTableHeaders = function(wrapper, n) {
		return $(wrapper).find('thead > tr > th:nth-child('+(n+1)+')');
	}

	this.getSortableTableHeaders = function(wrapper) {
		return greyd.query.table.getAllTableHeaders(wrapper).filter(function(index){
			return $(this).hasClass(greyd.query.table.config.styles['sort']);
		});
	}

	this.changeOrder = function(wrapper, column) {
		var th = greyd.query.table.getNthTableHeaders(wrapper, column);
		
		// Order
		var sortOrder = greyd.query.table.config.defaultOrder;
		if (th.hasClass(greyd.query.table.config.styles['asc'])) {
			sortOrder = 'desc';
		}
		if (th.hasClass(greyd.query.table.config.styles['desc'])) {
			sortOrder = 'asc';
		}
		
		// Reset
		var headers = greyd.query.table.getSortableTableHeaders(wrapper);
		headers.removeClass( greyd.query.table.config.styles['asc']+" "+greyd.query.table.config.styles['desc'] );
		headers.addClass( greyd.query.table.config.styles['unsort'] );
		
		// Set classes
		th.removeClass(greyd.query.table.config.styles['unsort']);
		th.addClass(greyd.query.table.config.styles[sortOrder]);
		
		// Group
		var tbody = $(wrapper).find('tbody');
		var groups = greyd.query.table.config.selector(tbody, column);

		// Sorting
		groups.sort(function(a, b){
			var res = greyd.query.table.config.comparator(a, b);
			return sortOrder == 'asc' ? res : -1 * res;
		});

		// Change order
		var rows = parseInt( $(wrapper).data('rows') ), i = 0, k = 0;
		$.each(groups, function(i, trList) {
			if ( i === rows * ( k+1 ) ) k++;
			$.each(trList.elem, function(j, tr) {
				tbody[k].append(tr);
			});
			i++;
		});
	}

};
