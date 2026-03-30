/**
 * Greyd.Blocks Frontend Script for Trigger Feature.
 *
 * This file is loaded in the frontend only.
 */
(function() {
	
	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;
		
		/**
		 * trigger handler
		 * refactored from theme default.js 'greyd_trigger' function
		 */
		greyd.trigger.init();

		// console.log("Frontend Trigger Script: loaded");
	} );

} )(jQuery);


/**
 * Custom greyd-trigger events.
 */
greyd.trigger = new function() {

	this.init = function() {

		/**
		 * Support keys 'Enter' & 'Space'
		 * (not only trigger, but all elements with onclick attribute)
		 * 
		 * still handeled by themes greyd_trigger function - activate later
		 */
		// $('[onclick]').on('keydown', function(e) {
		// 	if ( e.keyCode === 13 || e.keyCode === 32 ) {
		// 		e.preventDefault();
		// 		this.click();
		// 	}
		// });

		/**
		 * Handle hidden links 
		 * 
		 * Class renamed from 'hidden-box-link' to 'hidden-trigger-link'
		 * so the themes greyd_trigger function is deprecated.
		 */
		$('.hidden-trigger-link').each(function(){
			var elem = this;

			/**
			 * Support cmd/ctrl click to open in new tab
			 * @param {object} event current JS event
			 * @param {object} prevEvent previous JS event, passed via $(...).trigger('click', e);
			 */
			$(elem).on('click', function(e, prevEvent) {
				var cmdClicked = e.metaKey || e.ctrlKey;
				var cmdClickedPrev = typeof prevEvent !== 'undefined' && (prevEvent.metaKey || prevEvent.ctrlKey);

				if ( cmdClicked || cmdClickedPrev ) {
					e.preventDefault();
					window.open( $(this).attr('href'), '_blank' );
				}
				else if ( typeof prevEvent !== 'undefined' ) {
					//we actually have to trigger the click because $(...).trigger('click') doesn't
					this.click();
				}
				
			});

			// When direct siblings are clicked, we trigger the link
			$(elem).siblings(':not([href])').on('click', function(e) {
				$(elem).trigger('click', e);
			});
		});

		/**
		 * Setup greyd trigger
		 * 
		 * Data attribute renamed from 'data-ontrigger' to 'data-greyd-trigger'
		 * Custom body event renamed from 'greyd_trigger' to 'greyd-trigger'
		 * so the themes greyd_trigger functions are deprecated.
		 */
		if ( $("[data-greyd-trigger], [onclick*='greyd.trigger.trigger'], [onmouseenter*='greyd.trigger.trigger']").length === 0 ) {
			// console.warn("no trigger found");
			return;
		}

		/**
		 * If an element is hidden onload, it is set to 'display: none' by editor.css.
		 * After showing this element with a jQuery function, it will have 'display: block'.
		 * This will break if the element should be flex, so this is fixed by initially 
		 * resetting the flex css and hide it again. That way jQuery knows how to show it again.
		 * @since 2.14.0
		 */
		$('[data-greyd-trigger][data-onload="hide"]').each(function(){
			// console.log($(this).css('display'));
			if ( $(this).hasClass('is-layout-flex') || $(this).hasClass( 'wp-block-group-is-layout-flex' ) ) {
				$(this).css( 'display', 'flex' );
				$(this).hide();
			}
		});

		// remove previous onTrigger event
		$("body").off( "greyd-trigger", greyd.trigger.onTrigger );
		$("body").on( "greyd-trigger", greyd.trigger.onTrigger );
		
		// console.log('Trigger Script: initialised');
	}

	this.onTrigger = function( e, event, trigger, isGlobal ) {
		// console.log("Custom event 'greyd-trigger' triggered: "+trigger);

		// search for trigger action
		if ( isGlobal ) {
			var targets = $("[data-greyd-trigger*='"+trigger+"|']");
		}
		else {
			var targets = $( event.target ).closest( ".dynamic, .main, .navigation, body" ).find("[data-greyd-trigger*='"+trigger+"|']");
		}
		
		if ( targets.length > 0 ) {
			// trigger the actions
			targets.each( function(i, elem) {
				var target  = $(elem);
				var regex   = new RegExp( trigger+"[A-Za-z\|]+", "g" );
				var data    = target.data("greyd-trigger");
				var setup   = data ? data.match( regex ).slice(-1).pop() : ""; // get the last defined setup for this trigger, as there could be multiple
				var action  = setup ? setup.split("|")[1] : 'show';

				if ( typeof target[action] === 'function' ) {
					
					// hide siblings
					if (target.data("siblings") == "hide") target.siblings("[data-greyd-trigger]").stop().hide();

					// call jQuery function
					target.stop();
					target.css({transition: 'none'});
					target[action]();
				}
			} );
		}
		else {
			// search for popover
			if ( $(".wp-block-greyd-popover[data-popover-name='"+trigger+"']").length > 0 ) {
				var targetQuery = ".wp-block-greyd-popover[data-popover-name='"+trigger+"'] .wp-block-greyd-popover-popup > [role=dialog]";
				if ( isGlobal ) {
					var targets = $(targetQuery);
				}
				else {
					var targets = $( event.target ).closest( ".query-post, .dynamic, .main, .navigation, body" ).find(targetQuery);
				}
				if ( targets.length > 0 ) {
					// trigger the last popover (all previous will be closed anyway)
					var target = targets[targets.length-1];
					const dialogId = $(target).attr("id");
					window.dispatchEvent( new Event('toggle_'+dialogId) );
					event.stopPropagation();
				}
			}
		}
	}

	/**
	 * Trigger a custom greyd-trigger Event.
	 * 
	 * Function renamed from 'greyd_trigger.trigger()' to 'greyd.trigger.trigger()'
	 * so the themes greyd_trigger function is deprecated.
	 * 
	 * @param {Event} event		original event containing the target element
	 * @param {string} string	name of the trigger
	 * @param {bool} isGlobal	trigger globally
	 */
	this.trigger = function(event, string, isGlobal) {
		// console.log( "Trigger event: "+string+", is global? "+(isGlobal ? "true" : "false") );

		$("body").trigger("greyd-trigger", [event, string, isGlobal] );

		// trigger resize event to update nested elements
		window.dispatchEvent(new Event('resize'));
	}
}