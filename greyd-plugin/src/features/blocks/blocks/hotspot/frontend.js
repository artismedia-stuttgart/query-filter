document.addEventListener("DOMContentLoaded", function() {

	const hotspots = [].slice.call(document.querySelectorAll(".greyd-media-wrapper .greyd-hotspot"));
	
	hotspots.forEach(function(hotspot) {

		const spot      = hotspot.querySelector('.spot');
		const popover   = hotspot.querySelector('.popover');
		const onHover   = hotspot.parentNode.getAttribute('data-on-hover') === "true";
		const autoClose = hotspot.parentNode.getAttribute('data-autoclose') === "true";

		const focusableSelectors = [
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
		];

		if ( !popover ) return;

		/**
		 * Hotspot polyfill
		 */
		if ( typeof HTMLDialogElement !== 'function' ) {

			popover.classList.add( 'polyfill' );

			// move the popover outside of the spot. Otherwise iOS devices do not
			// display the popover as fixed, because the parent is positioned absolute
			popover.parentNode.parentNode.insertBefore( popover, hotspot.nextSibling );

			// register the polyfill
			// @see https://github.com/GoogleChrome/dialog-polyfill
			if ( typeof dialogPolyfill !== 'undefined' ) {
				dialogPolyfill.registerDialog( popover );
			}

			// because we moved the dialog outside the parent, we need to modify any
			// custom popover styles.
			const style = hotspot.querySelector('style');
			if ( style ) {
				style.innerHTML = style.innerHTML.replace( /\s\.popover/, ' + .popover' )
			}
		}

		const togglePopover = () => {
			if ( hotspot.classList.contains('is-open') ) {
				closePopover();
			} else {
				openPopover();
			}
		}

		const openPopover = () => {
			// close others
			if ( autoClose ) {
				[].slice.call(hotspot.parentNode.querySelectorAll(".greyd-hotspot.is-open")).forEach(function(elem){
					elem.classList.remove('is-open');
				});
			}
			hotspot.classList.add('is-open');
			if ( window.innerWidth > 767 ) {
				popover.show();
			} else {
				popover.showModal();
			}

			document.addEventListener( "keydown", (e) => {
				if (e.key === 'Tab') trapTabKey(popover, e)
			});

		}
		const closePopover = () => {
			hotspot.classList.remove('is-open');
			popover.close();
		}

		// Handle click events for both hover and non-hover modes
		spot.addEventListener( 'click', function(e) {
			togglePopover();
		} );

		// Handle keyboard events (Enter and Space)
		spot.addEventListener( 'keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				togglePopover();
			}
		} );

		if ( onHover ) {
			spot.addEventListener( 'mouseout', function(e) {
				closePopover();
			} );
			spot.addEventListener( 'mouseover', function(e) {
				openPopover();
			} );
			// add touchstart event for mobile devices
			spot.addEventListener( 'touchstart', function(e) {
				togglePopover();
				e.preventDefault();
			} );
		}

		// close popovers with ESC-Key
		document.addEventListener('keydown', function(event) {
			if (event.key == "Escape") {
				closePopover();
			}
		});
		
		popover.addEventListener( 'click', function(e) {
			if (e.target === popover) {
				closePopover();
				e.stopPropagation();
			}
		} );

		const trapTabKey = (node, e) => {
			const focusableChildren = getFocusableChildren(node)
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
		const isVisible = (element) => {
			return element =>
			element.offsetWidth ||
			element.offsetHeight ||
			element.getClientRects().length
		}
		
		const getFocusableChildren = (root) => {
			console.log(root);
			const elements = [...root.querySelectorAll(focusableSelectors.join(','))]
			return elements.filter(isVisible)
		}

	});
});