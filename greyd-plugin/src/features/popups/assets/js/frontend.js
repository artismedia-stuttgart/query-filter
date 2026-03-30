/**
 * Popups frontend script
 */

(function() {
    
    if (typeof $ === 'undefined') $ = jQuery;
    $(function() {
        popups.init();
    } );

} )(jQuery);

var popups = new function() {
    
    // a11y
    this.lastFocus = null;

    this.on_init = [];
    this.on_scroll = [];
    this.on_idle = [];
    this.on_exit = [];

    this.focusableSelectors = [
        'a[href]:not([tabindex^="-"])',
        'area[href]:not([tabindex^="-"])',
        'input:not([type="hidden"]):not([type="radio"]):not([disabled]):not([tabindex^="-"])',
        'input[type="radio"]:not([disabled]):not([tabindex^="-"]):checked',
        'select:not([disabled]):not([tabindex^="-"])',
        'textarea:not([disabled]):not([tabindex^="-"])',
        'button:not([disabled]):not([tabindex^="-"])',
        'iframe:not([tabindex^="-"])',
        'audio[controls]:not([tabindex^="-"])',
        'video[controls]:not([tabindex^="-"])',
        '[contenteditable]:not([tabindex^="-"])',
        '[tabindex]:not([tabindex^="-"])',
    ]

    this.init = function() {

        // count pageviews for delay condition
        // was classic greyd default.js function theme.count()
        var item = 'count';
        if (typeof theme === 'undefined') {
            item = 'greyd-session-count';
            var count = sessionStorage.getItem(item);
            if (!count) count = 0; 
            count++;
            sessionStorage.setItem(item, count);
        }

        $('.popups .popup').each(function() {
            var show_popup = true;
            var conditions = $(this).data('conditions');
            // console.log(conditions);
            if (show_popup && conditions.delay != 'off') {
                // check delay condition
                show_popup = false;
                var count = sessionStorage.getItem(item);
                if (!count) count = 0; 
                if (parseInt(count) > parseInt(conditions.delay)) show_popup = true;
            }
            if (show_popup && conditions.referer != 'off') {
                // check referer condition
                show_popup = false;
                var is = document.referrer;
                var should = conditions.referer.value;
                if (conditions.referer.condition === "is") {
                    if (should === is) show_popup = true;
                } 
                else if (conditions.referer.condition === "is_not") {
                    if (should !== is) show_popup = true;
                } 
                else if (conditions.referer.condition === "has") {
                    if (is.indexOf(should) > -1) show_popup = true;
                } 
                else if (conditions.referer.condition === "has_hot") {
                    if (is.indexOf(should) == -1) show_popup = true;
                }
            }
            if (conditions.device != 'off' || conditions.browser != 'off') {
                // check device/browser condition
                var device = popups.checkBrowser();
                if (show_popup && conditions.device != 'off') {
                    show_popup = false;
                    if (conditions.device.indexOf(device.system) > -1) show_popup = true;
                }
                if (show_popup && conditions.browser != 'off') {
                    show_popup = false;
                    if (conditions.browser.indexOf(device.browser) > -1) show_popup = true;
                }
            }
            
            if (show_popup) {
                var trigger = $(this).data('trigger');
                // console.log(trigger);
                if (trigger.on_init != 'off') {
                    popups.on_init.push(this);
                    var that = this;
                    setTimeout(function() {
                        popups.maybe_open(that);
                    }, trigger.on_init*1000);
                }
                if (trigger.on_scroll != 'off') {
                    popups.on_scroll.push(this);
                }
                if (trigger.on_idle != 'off') {
                    popups.on_idle.push(this);
                }
                if (trigger.on_exit != 'off') {
                    popups.on_exit.push(this);
                }
            }
            else $(this).remove();
        });

        // set will-change if necessary
        if ( $('.popups .popup .popup_overlay[data-effect]').length > 0 ) {
            $('body .navigation .header_wrapper').css('will-change', 'filter');
        }

        var lastScroll = $(document).scrollTop();
        $(window).on('scroll', function(event) {
            var scroll_pos = $(document).scrollTop();
            var scroll_max = $(document).height()-$(window).height();
            // console.log(scroll_pos);
            // console.log(scroll_max);
            var dir = 'down';
            if (scroll_pos < lastScroll) dir = 'up';
            // console.log(dir);
            $.each(popups.on_scroll, function() {
                var trigger = $(this).data('trigger');
                var scroll_trigger = trigger.on_scroll.replace('px','');
                if (scroll_trigger.indexOf('%') > -1) 
                    scroll_trigger = scroll_max*(scroll_trigger.replace('%','')/100.0);
                // console.log(scroll_trigger);
                if ((scroll_pos > scroll_trigger && lastScroll < scroll_trigger && dir == 'down'))
                    popups.open(this);
                // if ((scroll_pos < scroll_trigger && lastScroll > scroll_trigger && dir == 'up'))
                //     popups.open(this);
            });
            lastScroll = scroll_pos;
        });
        
        var timer = []
        $(window).on('mousemove', function(event) {
            // console.log("abort idle timer");
            $.each(timer, function() {
                clearTimeout(this);
            });
            $.each(popups.on_idle, function() {
                var trigger = $(this).data('trigger');
                var that = this;
                var timeout = setTimeout(function() {
                    popups.open(that);
                }, trigger.on_idle*1000);
                
                var index = timer.indexOf(timeout);
                if (index > -1) timer[index] = timeout;
                else timer.push(timeout);
            });
        });
        
        $(window).on('mouseout', function(event) {
            event = event ? event : window.event;
            var from = event.relatedTarget || event.toElement;
            if ( (!from || from.nodeName == "HTML") && event.clientY <= 100 ) {
                // console log("left top bar");
                $.each(popups.on_exit, function() {
                    popups.open(this);
                });
            }
        });

        /**
         * Open popup on click (custom)
         * @example https://www.website.de/site/?open-popup=search
         * @param {int|string} 'open-popup' Set this URL-parameter with the popup post-id or slug.
         */
        $('[href*="open-popup="]').on('click keyup', function(e) {
            e.preventDefault();
            var match = $(this).attr('href').match(/open-popup=[^#&?]+/);
            if (match) {
                var slugOrID = match[0].replace('open-popup=', '');
                openGreydPopup( slugOrID );
            }
        });

        /**
         * Close popup when ESC is pressed.
         */
        $(window).on('keyup', function(event) {
            // console.log(event.key);
            if (event.key == "Escape") {
                $('.popups .popup').each(function() {
                    if (!$(this).hasClass('hidden')) popups.close(this);
                });
            }
        });

        /**
         * Elements that close a popup with onclick can also be triggered by 'space' or 'enter'
         */
        $('[onclick*="popups.close"]').each(function() {
         
            // allow focus on element
        
            if ( !$(this).hasClass("popup_overlay")) {
                $(this).attr('tabindex', 0);
                $(this).attr('role', 'button');
            }

            // add key event
            $(this).on('keydown', function(event) {
                // console.log(event.key);
                if (event.key == " " || event.key == "Enter") {
                    event.preventDefault();
                    popups.close(this);
                }
            });
        });
    }

    this.maybe_open = function(el) {
        var popup = $(el);
        // console.log("maybe open popup "+popup.attr('id'));
        index = -1;
        $.each(popups.on_init, function() {
            if ($(this).attr('id') == popup.attr('id')) 
                index = popups.on_init.indexOf(this);
        });
        if (index > -1) popups.open(el);
    }
    this.open = function(el) {
        if ($('html').hasClass('not_compatible')) return;
        
        var popup = $(el);
        var overlay = popup.find('.popup_overlay');
        var wrapper = popup.find('.popup_content .popup_wrapper');
        const content = popup.find('.popup_content');
        // console.log("open popup "+popup.attr('id'));

        if (popup.hasClass('hidden')) {

            popup.removeClass('hidden');
                 
            // a11y: set focus
            popups.lastFocus = document.activeElement;
            let inputs = popup.find('input, textarea, select, .popup_close_button');
 
            if (inputs[0]) inputs[0].focus();

            document.addEventListener( "keydown", (e) => {
                if (e.key === 'Tab') popups.trapTabKey(popup, e)
            })

            // close nav (classic greyd)
            if ( typeof nav !== 'undefined' && typeof nav.closeMenus === 'function' ) nav.closeMenus();
            // close popovers
            window.dispatchEvent(new Event('closePopovers'));

            var busy = popup.data('busy');
            if (typeof busy !== 'undefined' && busy == 'yes') return;
            popup.data('busy', 'yes');
    
            // animate
            setTimeout( function() {
                var anim = popup.data('anim_type');
                var time = 0;
                if (typeof anim !== 'undefined') {
                    time = popup.data('anim_time');
                    var opacity = overlay.data('opacity');
                    if (anim == 'fade') {
                        popup.css( {"opacity": '1'} );
                    }
                    else if (anim.indexOf('slide_') == 0) {
                        overlay.css( {"top": '0', "left": '0', "opacity": opacity} );
                        popup.css( {"top": '0', "left": '0'} );
                    }
                    else {
                        var dur_old = wrapper.css('transition-duration');
                        var dur = (time/1000.0);
                        if (anim == 'scale')
                            wrapper.css('transform', 'scale(1)');
                        else if (anim == 'flip_vertical')
                            wrapper.css('transform', 'rotateY(0deg)');
                        else if (anim == 'flip_horizontal')
                            wrapper.css('transform', 'rotateX(0deg)');
                        else if (anim == 'flip_3d')
                            wrapper.css('transform', 'rotate3d(1,1,1,0) scale(1)');
                        overlay.css( {"opacity": opacity} );
                    }
                }
                // overlay
                var effect = overlay.data('effect');
                if (typeof effect !== 'undefined') {
                    popups.addFilter(effect, time);
                }
                // body transform
                var transform = popup.data('transform');
                if (typeof transform !== 'undefined') {
                    var origin = popup.data('origin');
                    if (typeof origin === 'undefined') origin = 'center';
                    popups.setTransform(transform, origin, time);
                }

                var noscroll = overlay.data('noscroll');
                if (typeof noscroll !== 'undefined' && noscroll === true) {
                    popups.blockScroll(popup.attr('id'));
                }
                
                var highest = popup.data('highest');
                if (typeof highest !== 'undefined' && highest === true) {
                    // console.log("close all with lower prio");
                    var current = popup;
                    while (current.prev().length > 0) {
                        current = current.prev();
                        popups.close(current);
                    }
                }
                var hashtag = popup.data('hashtag');
                if (typeof hashtag !== 'undefined' && hashtag === true) {
                    window.location.hash = popup.attr('name');
                }
                
                setTimeout(function() {
                    popup.data('busy', 'no');
                }, time);
            }, 0 );

			/**
			 * Trigger custom Javascript event 'open' when popup is opened.
			 * 
			 * @param {Event} event         The event object.
			 * @param {HTMLElement} target  The element that triggered the popup, e.g. a button.
			 * 
			 * @example vanilla JS:
			 * document.getElementById('popup_123').addEventListener('open', function(event, target) { console.log('Popup opened', event, target, this); });
			 * 
			 * @example jQuery:
			 * $('#popup_123').on('open', function(event, target) { console.log('Popup opened', event, target, this); });
			 */
			popup.trigger('open', [ ( event && event?.target ? event.target : null ) ]);
        }
    }

    this.btnClose = function(el) {
        if (typeof nav !== 'undefined' && typeof nav.checkKey === 'function' && nav.checkKey(event)) return true;
        this.close(el);
    }
    this.close = function(el) {
        var popup = $(el).closest('.popup');
        var overlay = popup.find('.popup_overlay');
        var wrapper = popup.find('.popup_content .popup_wrapper');
        // console.log("close popup "+popup.attr('id'));

        if (!popup.hasClass('hidden')) {
            // a11y: reset focus
            if (popups.lastFocus.closest('.is-position-fixed')) {
				popups.lastFocus.focus({ preventScroll: true });
			} else {
				popups.lastFocus.focus();
			}

            var busy = popup.data('busy');
            if (typeof busy !== 'undefined' && busy == 'yes') return;
            popup.data('busy', 'yes');
    
            // animate
            var anim = popup.data('anim_type');
            var time = 0;
            if (typeof anim !== 'undefined') {
                time = popup.data('anim_time');
                if (anim == 'fade') {
                    popup.css( {"opacity": '0'} );
                }
                else if (anim.indexOf('slide_') == 0) {
                    if (anim == 'slide_top') {
                        overlay.css( {"top": '100%', "opacity": '0'} );
                        popup.css( {"top": '-100%'} );
                    }
                    else if (anim == 'slide_bottom') {
                        overlay.css( {"top": '-100%', "opacity": '0'} );
                        popup.css( {"top": '100%'} );
                    }
                    else if (anim == 'slide_left') {
                        overlay.css( {"left": '100%', "opacity": '0'} );
                        popup.css( {"left": '-100%'});
                    }
                    else if (anim == 'slide_right') {
                        overlay.css( {"left": '-100%', "opacity": '0'} );
                        popup.css( {"left": '100%'} );
                    }
                }
                else {
                    var dur_old = wrapper.css('transition-duration');
                    var dur = (time/1000.0);
                    if (anim == 'scale')
                        wrapper.css('transform', 'scale(0)');
                    else if (anim == 'flip_vertical')
                        wrapper.css('transform', 'rotateY(-90deg)');
                    else if (anim == 'flip_horizontal')
                        wrapper.css('transform', 'rotateX(-90deg)');
                    else if (anim == 'flip_3d')
                        wrapper.css('transform', 'rotate3d(1,1,1,240deg) scale(0)');
                    overlay.css( {"opacity": '0'} );
                }
                setTimeout( function() { popup.addClass('hidden') }, time );
            }
            else {
                popup.addClass('hidden');
            }

            // overlay
            var effect = overlay.data('effect');
            if (typeof effect !== 'undefined') {
                popups.removeFilter(effect, time);
            }
            // body transform
            var transform = popup.data('transform');
            if (typeof transform !== 'undefined') {
                popups.setTransform('none', '', time);
            }

            var noscroll = overlay.data('noscroll');
            if (typeof noscroll !== 'undefined' && noscroll === true) {
                popups.unblockScroll(popup.attr('id'));
            }
        
            var once = popup.data('once');
            if (typeof once !== 'undefined' && once === true) {
                setTimeout(function() {
                    var cookiename = popup.data('cookie');
                    if ( cookiename && cookiename.length > 0 ) {
                        document.cookie = cookiename+"=true;"+( $("html").hasClass("ie") ? "" : " expires=0;")+" path=/";
                    }
                    // console.log("deleting popup ... "+popup.attr('name'));
                    popups.remove(popup);
                }, time);
            }

            const videos = popup[0].querySelectorAll(".wp-block-video video, .wp-block-embed iframe");
            if ( NodeList.prototype.isPrototypeOf(videos) ) {
                videos.forEach(video => {
                    if (video.tagName == "VIDEO") video.pause();
                    if (video.tagName == "IFRAME") {
                        const src = video.src;
                        video.src = src;
                    }
                });
            }

            setTimeout(function() {
                popup.data('busy', 'no');
            }, time);
        }
    }

    this.btnToggle = function(el) {
        if (typeof nav !== 'undefined' && typeof nav.checkKey === 'function' && nav.checkKey(event)) return true;
        this.toggle(el);
    }
    this.toggle = function(el) {
        if ( $(el).hasClass('hidden') )
            popups.open(el);
        else
            popups.close( $(el).children()[0] );
    }

    this.remove = function(popup) {
        var id = popup.attr('id');
        var index = -1;
        $.each(popups.on_scroll, function() {
            if ($(this).attr('id') == id) index = popups.on_scroll.indexOf(this);
        });
        if (index > -1) popups.on_scroll.splice(index, 1);
        index = -1;
        $.each(popups.on_idle, function() {
            if ($(this).attr('id') == id) index = popups.on_idle.indexOf(this);
        });
        if (index > -1) popups.on_idle.splice(index, 1);
        index = -1;
        $.each(popups.on_exit, function() {
            if ($(this).attr('id') == id) index = popups.on_exit.indexOf(this);
        });
        if (index > -1) popups.on_exit.splice(index, 1);
        index = -1;
        $.each(popups.on_init, function() {
            if ($(this).attr('id') == id) index = popups.on_init.indexOf(this);
        });
        if (index > -1) popups.on_init.splice(index, 1);
        popup.remove();
    }
    
    this.addFilter = function(effect, time) {
        var filters = $('section.popups').data('filter');
        if (typeof filters === 'undefined') filters = [];
        filters.push(effect);
        var index = filters.indexOf('none');
        if (index > -1) filters.splice(index, 1);
        // console.log(filters);

        $('section.popups').data('filter', filters);
        popups.setFilter(filters.join(' '), time);
    }
    this.removeFilter = function(effect, time) {
        var filters = $('section.popups').data('filter');
        if (typeof filters === 'undefined') filters = [];
        var index = filters.indexOf(effect);
        if (index > -1) filters.splice(index, 1);
        // console.log(filters);

        $('section.popups').data('filter', filters);
        if (filters.length == 0) filters = 'none';
        else filters = filters.join(' ');
        popups.setFilter(filters, time);
    }
    this.setFilter = function(filters, time) {
        var dur_old = $('body main').css('transition-duration') || $('body .wp-site-blocks').css('transition-duration');
        var dur = (time/1000.0);
        var selector = 'body header, body main, body footer'; // classic
        if ( $('body .wp-site-blocks').length ) selector = 'body .wp-site-blocks'; // fse
        $(selector).animate( {"transition-duration": dur+'s'}, 0, function() { 
            $(selector).css('filter', filters);
            setTimeout(function() {
                $(selector).css('transition-duration', dur_old);
            }, time+1);
        } );
        // $('body .main').css('transition-duration', dur+'s');
        // $('body .navigation .header_wrapper').animate( {"transition-duration": dur+'s'}, 0, function() { 
        //     $('body .navigation .header_wrapper, body .main').css('filter', filters);
        //     setTimeout(function() {
        //         $('body .navigation .header_wrapper, body .main').css('transition-duration', dur_old);
        //     }, time+1);
        // } );
    }

    this.setTransform = function(transform, origin, time) {
        var dur_old = $('body main').css('transition-duration') || $('body .wp-site-blocks').css('transition-duration');
        var dur = (time/1000.0);
        var selector = 'body header > div:not(.offmenu_wrapper), body main, body footer'; // classic
        if ( $('body .wp-site-blocks').length ) selector = 'body .wp-site-blocks'; // fse
        $(selector).animate( {"transition-duration": dur+'s'}, 0, function() { 
            $(selector).css('transform', transform);
            $(selector).css('transform-origin', origin);
            setTimeout(function() {
                $(selector).css('transition-duration', dur_old);
            }, time+1);
        } );
    }

    this.blockScroll = function(id) {
        var blocker = $('section.popups').data('noscroll');
        if (typeof blocker === 'undefined') blocker = [];
        blocker.push(id);

        $('section.popups').data('noscroll', blocker);
        $('html').css('overflow', 'hidden');
    }
    this.unblockScroll = function(id) {
        var blocker = $('section.popups').data('noscroll');
        if (typeof blocker === 'undefined') blocker = [];
        var index = blocker.indexOf(id);
        if (index > -1) blocker.splice(index, 1);

        $('section.popups').data('noscroll', blocker);
        if (blocker.length == 0) $('html').css('overflow', '');
        else $('html').css('overflow', 'hidden');
    }

    // check browser
    this.checkBrowser = function() {

        var getVersion = function(name) {
            if (name == "MSIE") {
                if (navigator.userAgent.search("Trident") >= 0) {
                    var tmp = navigator.userAgent.split("Trident/");
                    var tmp1 = tmp[1].split("; ");
                    if (tmp1[0].indexOf("6.") > -1) return "10.0";
                    else if(tmp1[0].indexOf("7.") > -1) return "11.0";
                }
                else {
                    var tmp = navigator.userAgent.split(MSIE+" ");
                    var tmp1 = tmp[1].split("; ");
                    return tmp1[0];
                }
            }
            else {
                var tmp = navigator.userAgent.split(name+"/");
                var tmp1 = tmp[1].split(" ");
                return tmp1[0];
            }
        }
    
        // console.log(navigator.userAgent);
        /*
        windows 7:
        firefox: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0
        chrome:  Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36
        opera:   Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36 OPR/54.0.2952.64
        ie11:    Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)
        windows 10:
        firefox: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0
        ie11:    Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729)
        edge:    Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16299
        OXS:
        safari:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.2 Safari/605.1.15
        chrome:  Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.75 Safari/537.36
        mobile: 
        simulated with chrome:
        iPhone:  Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1
        iPad:    Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1
        S5:      Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Mobile Safari/537.36
        simulated with firefox:
        iPhone:  Mozilla/5.0 (iPhone; CPU iPhone OS 10_2_1 like Mac OS X) AppleWebKit/602.4.6 (KHTML, like Gecko) Version/10.0 Mobile/14D27 Safari/602.1
        iPad:    Mozilla/5.0 (iPad; CPU OS 4_3_5 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8L1 Safari/6533.18.5
        S5:      Mozilla/5.0 (Linux; Android 6.0.1; SM-G900V Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36
        */
        // dataset
        var dataset = {
            'system': '',
            'browser': '',
            'version': 0,
        };
        // system
        var deviceAgent = navigator.userAgent.toLowerCase();
        if (deviceAgent.match(/(iphone|ipod|ipad)/)) {
            //$("html").addClass("mobile");
            dataset.system = "ios";
        }
        else if (navigator.userAgent.search("Android") >= 0) {
            //$("html").addClass("mobile");
            dataset.system = "android";
        }
        else if (navigator.userAgent.search("Windows NT") >= 0) {
            dataset.system = "win";
        }
        else if (navigator.userAgent.search("Macintosh") >= 0) {
            dataset.system = "osx";
        }
        else {
            dataset.system = "unknown";
        }
        // browser
        if (navigator.userAgent.search("MSIE") >= 0 ||
            navigator.userAgent.search("Trident") >= 0) {
            dataset.browser = "ie";
            dataset.version = getVersion("MSIE");
        }
        else if (navigator.userAgent.search("Edge") >= 0) {
            dataset.browser = "edge";            
            dataset.version = getVersion("Edge");
        }
        else if (navigator.userAgent.search("OPR") >= 0) {
            dataset.browser = "opera";
            dataset.version = getVersion("OPR");
        }
        else if (navigator.userAgent.search("Opera") >= 0) {
            dataset.browser = "opera";
            dataset.version = getVersion("Opera");
        }
        else if (navigator.userAgent.search("Firefox") >= 0) {
            dataset.browser = "firefox";
            dataset.version = getVersion("Firefox");
        }
        else if (navigator.userAgent.search("Chrome") >= 0) {
            dataset.browser = "chrome";
            dataset.version = getVersion("Chrome");
        }
        else if (navigator.userAgent.search("Safari") >= 0) {
            dataset.browser = "safari";
            dataset.version = getVersion("Version");
        }
        else {
            dataset.browser = "unknown";
        }

        return dataset;
    }

    this.trapTabKey = function(node, e) {
        const focusableChildren = popups.getFocusableChildren(node)
        const focusedItemIndex = focusableChildren.indexOf(document.activeElement)
        const lastIndex = focusableChildren.length - 1
        const withShift = e.shiftKey
        
        if (withShift && focusedItemIndex === 0) {
            focusableChildren[lastIndex].focus()
            e.preventDefault()
        } else if (!withShift && focusedItemIndex === lastIndex) {
            focusableChildren[0].focus()
            e.preventDefault()
        }
    }
    this.isVisible = function(element) {
        return element =>
        element.offsetWidth ||
        element.offsetHeight ||
        element.getClientRects().length
    }
      
    this.getFocusableChildren = function(root) {
        const elements = [...root[0].querySelectorAll(popups.focusableSelectors.join(','))]
        
        return elements.filter(popups.isVisible)
    }
}

/**
 * Open a Greyd.Popup by id or slug.
 * @param {mixed} popup Slug, ID or jQuery-object of the popup
 */
function openGreydPopup ( popup ) {
    if ( typeof popup === 'object' && popup.length ) {
        popups.open( popup );
    }
    else {
        var el = $( '#popup_'+popup );
        if ( !el.length ) {
            el = $( '.popup[name='+popup+']' );
        }
        if ( !el.length ) {
            el = $( '.popup[name='+popup.toLowerCase()+']' );
        }
        if ( el.length ) popups.open( el );
    }
}