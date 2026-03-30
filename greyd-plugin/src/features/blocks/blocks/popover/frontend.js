/**
 * This software or document includes material copied from or derived from W3C SOFTWARE AND DOCUMENT NOTICE AND LICENSE
 * @src https://www.w3.org/Consortium/Legal/2015/copyright-software-and-document
 * Copyright © 2023 W3C® (MIT, ERCIM, Keio, Beihang)
 * 
 * License
 * By obtaining and/or copying this work, you (the licensee) agree that you have read, understood, and will comply with the following terms and conditions.
 * Permission to copy, modify, and distribute this work, with or without modification, for any purpose and without fee or royalty is hereby granted, provided that you include the following on ALL copies of the work or portions thereof, including modifications:
 * The full text of this NOTICE in a location viewable to users of the redistributed or derivative work.
 * Any pre-existing intellectual property disclaimers, notices, or terms and conditions. If none exist, the W3C Software and Document Short Notice should be included.
 * Notice of any changes or modifications, through a copyright statement on the new code or document such as "This software or document includes material copied from or derived from [title and URI of the W3C document]. Copyright © [YEAR] W3C® (MIT, ERCIM, Keio, Beihang)."
 * 
 * Disclaimers
 * THIS WORK IS PROVIDED "AS IS," AND COPYRIGHT HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE OR DOCUMENT WILL NOT INFRINGE ANY THIRD PARTY PATENTS, COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.
 * COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE SOFTWARE OR DOCUMENT.
 * The name and trademarks of copyright holders may NOT be used in advertising or publicity pertaining to the work without specific, written prior permission. Title to copyright in this work will at all times remain with copyright holders.
 */

'use strict';

/**
 * @namespace aria
 */
var aria = aria || {};

/**
 * @description Key code constants
 */
aria.KeyCode = {
	BACKSPACE: 8,
	TAB: 9,
	RETURN: 13,
	SHIFT: 16,
	ESC: 27,
	SPACE: 32,
	PAGE_UP: 33,
	PAGE_DOWN: 34,
	END: 35,
	HOME: 36,
	LEFT: 37,
	UP: 38,
	RIGHT: 39,
	DOWN: 40,
	DELETE: 46,
};

/**
 * Init the popover events.
 */
document.addEventListener("DOMContentLoaded", function() {

	greyd.popover.init();

})

var greyd = greyd || {}
greyd.popover = new function() {

	this.resizeObserver = false;
	this.setVwUnit = function() {
		var vw = document.body.clientWidth / 100;
		document.body.style.setProperty('--vw-unit', vw+'px');
	}


	this.init = function() {

		// observe resize
		this.resizeObserver = new ResizeObserver( (entries) => {
			for ( var entry of entries ) {
				// console.log(entry.target);
				var popover = entry.target;
				if ( typeof popover.onupdatevars === 'function' ) popover.onupdatevars();
			}
		} );

		// set vw unit
		window.addEventListener('resize', greyd.popover.setVwUnit);
		this.setVwUnit();
			
		// close all popovers on click outside
		window.addEventListener('click', () => {
			window.dispatchEvent(new Event('closePopovers'));
		} );

		// init all popovers
		this.initPopover();

	}
	
	// 
	this.initPopover = function() {

		const popovers = [].slice.call(document.querySelectorAll(".wp-block-greyd-popover"));

		if ( popovers.length === 0 ) return;

		popovers.forEach( function( popover ) {

			// add events only once
			if (popover.dataset.initialized) return;
			popover.dataset.initialized = true;

			const dialog   = popover.querySelector(".wp-block-greyd-popover-popup > [role=dialog]");

			if ( !dialog ) return;

			const dialogId = dialog.getAttribute("id");
			const buttons  = [].slice.call(popover.querySelectorAll("[role=button][aria-expanded][aria-controls='"+dialogId+"']"));
			const backdrop = popover.querySelector(".dialog-backdrop");

			if ( buttons.length === 0 ) return;

			// timeout variable for hover delay
			let hoverCloseTimeout = null;

			const openPopover = ( elem ) => {

				// clear any pending close timeout
				if (hoverCloseTimeout) {
					clearTimeout(hoverCloseTimeout);
					hoverCloseTimeout = null;
				}

				// close all popovers before opening a new one
				window.dispatchEvent(new Event('closePopovers'));

				buttons.forEach( function(button) {
					button.setAttribute("aria-expanded", "true");
					if ( button.classList.contains("greyd-burger-btn") ) {
						button.childNodes[0].classList?.add('is-active');
					}
				} );
				elem = elem ? elem : buttons[0];
				openDialog( elem.getAttribute('aria-controls'), elem );

				// observe element
				popover.onupdatevars = () => {
					if ( dialog.hasAttribute("open") ) computeVars( elem );
				}
				greyd.popover.resizeObserver.observe( popover );
			}

			const closePopover = ( elem ) => {

				if ( ! dialog.hasAttribute("open") ) return;

				// clear any pending close timeout
				if (hoverCloseTimeout) {
					clearTimeout(hoverCloseTimeout);
					hoverCloseTimeout = null;
				}

				buttons.forEach( function(button) {
					button.setAttribute("aria-expanded", "false");
					if ( button.classList.contains("greyd-burger-btn") ) {
						button.childNodes[0].classList?.remove('is-active');
					}
				} );
				closeDialog( elem );

				// unobserve element
				popover.onupdatevars = undefined;
				greyd.popover.resizeObserver.unobserve( popover );
				
				// close offcanvas
				if ( dialog.classList.contains('is-variation-offcanvas') ) {
					// unobserve body
					document.body.onupdatevars = undefined;
					greyd.popover.resizeObserver.unobserve( document.body );
					// remove offcanvas
					document.body.classList.remove('is-position-default');
					document.body.classList.remove('is-position-left');
					document.body.classList.remove('is-position-top');
					document.body.classList.remove('is-position-bottom');
					setTimeout(() => {
						document.body.classList.remove('is-offcanvas');
						document.body.style.removeProperty('--offcanvas-delta');
					}, 300);
				}
			}

			const initVars = ( button ) => {
				// init only main trigger button
				if ( button.classList.contains('popover-close-button') ) return;

				// init positioning vars for dropdown
				if ( dialog.classList.contains('is-variation-dropdown') ) {
					// get alignment
					var margin = button.classList.contains('aligncenter') ? '0 auto' : '0';
					if ( button.classList.contains('alignright') ) margin = '0 0 0 auto';

					// set vars
					popover.style.setProperty('--button-width', button.offsetWidth+'px');
					popover.style.setProperty('--button-height', button.offsetHeight+'px');
					popover.style.setProperty('--button-align', margin);
				}
			}

			const computeVars = ( button ) => {
				// calculate only main trigger button
				if ( button.classList.contains('popover-close-button') ) return;

				// 
				greyd.popover.setVwUnit();

				// calculate offcanvas
				if ( dialog.classList.contains('is-variation-offcanvas') ) {
					// init offcanvas
					if ( !document.body.classList.contains('is-offcanvas') ) {
						document.body.classList.add('is-offcanvas');
						if ( dialog.classList.contains('is-position-default') ) {
							document.body.classList.add('is-position-default');
						}
						if ( dialog.classList.contains('is-position-left') ) {
							document.body.classList.add('is-position-left');
						}
						if ( dialog.classList.contains('is-position-top') ) {
							document.body.classList.add('is-position-top');
						}
						if ( dialog.classList.contains('is-position-bottom') ) {
							document.body.classList.add('is-position-bottom');
						}

						// unobserve element
						popover.onupdatevars = undefined;
						greyd.popover.resizeObserver.unobserve( popover );
						// observe body
						document.body.onupdatevars = () => {
							if ( dialog.hasAttribute("open") ) computeVars( button );
						}
						greyd.popover.resizeObserver.observe( document.body );
					}

					// calculate delta
					var comp = getComputedStyle(dialog);
					// console.log(comp);
					var width = comp.getPropertyValue('width');
					var height = comp.getPropertyValue('height');
					var marginX = 'calc('+( comp.getPropertyValue('margin') ?? '0px' )+' * 2)';
					var marginY = 'calc('+( comp.getPropertyValue('margin') ?? '0px' )+' * 2)';
					if ( comp.getPropertyValue('margin').trim().indexOf(' ') > -1) {
						var m = comp.getPropertyValue('margin').trim().split(' ', 4);
						if ( m.length == 1) {
							marginX = m[0];
							marginY = m[0];
						}
						else if ( m.length == 2 ) {
							marginX = m[1];
							marginY = m[0];
						}
						else if ( m.length == 3 ) {
							marginX = m[1];
							marginY = 'calc('+m[0]+' + '+m[2]+')';
						}
						else if ( m.length == 4 ) {
							marginX = 'calc('+m[1]+' + '+m[3]+')';
							marginY = 'calc('+m[0]+' + '+m[2]+')';
						}
					}
					if ( document.body.classList.contains('is-position-default') ) {
						document.body.style.setProperty('--offcanvas-delta', 'calc(0px - ('+width+' + '+marginX+'))');
					}
					if ( document.body.classList.contains('is-position-left') ) {
						document.body.style.setProperty('--offcanvas-delta', 'calc('+width+' + '+marginX+')');
					}
					if ( document.body.classList.contains('is-position-top') ) {
						document.body.style.setProperty('--offcanvas-delta', 'calc('+height+' + '+marginY+')');
					}
					if ( document.body.classList.contains('is-position-bottom') ) {
						document.body.style.setProperty('--offcanvas-delta', 'calc(0px - ('+height+' + '+marginY+'))');
					}
				}

				// calculate positioning vars for dropdown
				if ( dialog.classList.contains('is-variation-dropdown') ) {
					// get margins
					var marginL = 'var(--dialog-margin)';
					var marginR = 'var(--dialog-margin)';
					var marginX = 'calc(var(--dialog-margin) * 2)';
					var marginY = 'calc(var(--dialog-margin) * 2)';
					var comp = getComputedStyle(dialog);
					// console.log(comp.getPropertyValue('--dialog-margin'));
					if ( comp.getPropertyValue('--dialog-margin').trim().indexOf(' ') > -1) {
						var m = comp.getPropertyValue('--dialog-margin').trim().split(' ', 4);
						if ( m.length == 1) {
							marginL = m[0];
							marginR = m[0];
							marginX = m[0];
							marginY = m[0];
						}
						else if ( m.length == 2 ) {
							marginL = m[1];
							marginR = m[1];
							marginX = m[1];
							marginY = m[0];
						}
						else if ( m.length == 3 ) {
							marginL = m[1];
							marginR = m[1];
							marginX = m[1];
							marginY = 'calc('+m[0]+' + '+m[2]+')';
						}
						else if ( m.length == 4 ) {
							marginL = m[3];
							marginR = m[1];
							marginX = 'calc('+m[1]+' + '+m[3]+')';
							marginY = 'calc('+m[0]+' + '+m[2]+')';
						}
					}

					// measure
					var div = document.createElement('div');
					div.style.display = 'none';
					div.style.height = marginL;
					div.style.width = marginR;
					dialog.appendChild(div);
					marginL = parseFloat(getComputedStyle(div).getPropertyValue('height'));
					marginR = parseFloat(getComputedStyle(div).getPropertyValue('width'));
					div.remove();

					// delta
					var delta = 0.5 * (button.offsetWidth - dialog.offsetWidth);
					var buttonBounds = button.getBoundingClientRect();
					// console.log(buttonBounds);
					var clientWidth = document.body.clientWidth;
					if ( buttonBounds.x + delta - marginL <= 0 ) {
						delta = 0 - buttonBounds.x + marginL;
					}
					if ( buttonBounds.x + buttonBounds.width - delta + marginR >= clientWidth ) {
						delta = clientWidth - dialog.offsetWidth - buttonBounds.x - marginR;
					}

					// set vars
					popover.style.setProperty('--button-left', Math.round(buttonBounds.x)+'px');
					popover.style.setProperty('--button-right', Math.round(clientWidth - buttonBounds.width - buttonBounds.x)+'px');
					popover.style.setProperty('--button-delta', delta+'px');
					popover.style.setProperty('--dialog-margin-x', marginX);
					popover.style.setProperty('--dialog-margin-y', marginY);
					// re-set button size (may change after first load)
					popover.style.setProperty('--button-width', Math.round(buttonBounds.width)+'px');
					popover.style.setProperty('--button-height', Math.round(buttonBounds.height)+'px');
				}
			}
		
			buttons.forEach( (button) => {
				// toggle on click
				button.addEventListener('click', () => {
					if ( dialog.hasAttribute("open") ) {
						closePopover( button );
					} 
					else {
						openPopover( button );
					}
				} );
				// show on hover
				if (
					popover.dataset.popoverToggle == 'hover' &&
					dialog.classList.contains('is-variation-dropdown')
				) {
					button.addEventListener('mouseenter', () => {
						if ( !dialog.hasAttribute("open") ) {
							openPopover( button );
						}
					} );
					
					// handle mouseleave with delay
					popover.addEventListener('mouseleave', () => {
						if ( dialog.hasAttribute("open") ) {
							// set timeout to close after 300ms
							hoverCloseTimeout = setTimeout(() => {
								// check if user is still not hovering over popover content
								const isHoveringButton = button.matches(':hover');
								const isHoveringDialog = dialog.matches(':hover');
								const isHoveringPopover = popover.querySelector(':hover') !== null;
								
								// only close if user is not hovering over any popover content
								if (!isHoveringButton && !isHoveringDialog && !isHoveringPopover) {
									closePopover( button );
								}
								hoverCloseTimeout = null;
							}, 300);
						}
					} );
					
					// cancel close timeout when entering dialog area
					dialog.addEventListener('mouseenter', () => {
						if (hoverCloseTimeout) {
							clearTimeout(hoverCloseTimeout);
							hoverCloseTimeout = null;
						}
					} );
				}
				initVars( button );
			} );
			
			// add global event to toggle popover
			window.addEventListener('toggle_'+dialogId, () => {
				buttons[0].click();
			} );

			// close on click on backdrop
			backdrop.addEventListener('click', function() {
				closePopover( this );
			} );

			// close when clicked outside
			window.addEventListener('closePopovers', () => {
				closePopover( dialog );
			} );

			// prevent closing when clicked inside
			popover.addEventListener('click', function( event ) {
				event.stopPropagation();
			} );
		} )

	}

}

aria.Utils = aria.Utils || {};

( function () {

	aria.Utils.dialogOpenClass = 'has-dialog';

	/*
	 * When util functions move focus around, set this true so the focus listener
	 * can ignore the events.
	 */
	aria.Utils.IgnoreUtilFocusChanges = false;

	/**
	 * Polyfill
	 * @src https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
	 */
	aria.Utils.matches = function ( element, selector ) {
		if ( !Element.prototype.matches ) {
			Element.prototype.matches =
				Element.prototype.matchesSelector ||
				Element.prototype.mozMatchesSelector ||
				Element.prototype.msMatchesSelector ||
				Element.prototype.oMatchesSelector ||
				Element.prototype.webkitMatchesSelector ||
				function ( s ) {
					var matches = element.parentNode.querySelectorAll( s );
					var i = matches.length;
					while ( --i >= 0 && matches.item( i ) !== this ) {
						// empty
					}
					return i > -1;
				};
		}
	
		return element.matches( selector );
	};

	/**
	 * Check if an element is focusable
	 * @param element
	 *          DOM node for which to find the last focusable descendant.
	 * @returns {boolean}
	 *          true if element is focusable
	 */
	aria.Utils.isFocusable = function ( element ) {
		if ( element.tabIndex < 0 ) {
			return false;
		}

		if ( element.disabled ) {
			return false;
		}

		switch ( element.nodeName ) {
			case 'A':
				return !!element.href && element.rel != 'ignore';
			case 'INPUT':
				return element.type != 'hidden';
			case 'BUTTON':
			case 'SELECT':
			case 'TEXTAREA':
				return true;
			default:
				return false;
		}
	};
	
	/**
	 * Remove a DOM element.
	 * @param item
	 *          DOM node for which to find the last focusable descendant.
	 * @returns {boolean}
	 */
	aria.Utils.remove = function ( item ) {
		if ( item.remove && typeof item.remove === 'function' ) {
			return item.remove();
		}
		if (
			item.parentNode &&
			item.parentNode.removeChild &&
			typeof item.parentNode.removeChild === 'function'
		) {
			return item.parentNode.removeChild( item );
		}
		return false;
	};

	/**
	 * Set focus on descendant nodes until the first focusable element is
	 *       found.
	 * @param element
	 *          DOM node for which to find the first focusable descendant.
	 * @returns {boolean}
	 *          true if a focusable element is found and focus is set.
	 */
	aria.Utils.focusFirstDescendant = function ( element ) {
		for ( var i = 0; i < element.childNodes.length; i++ ) {
			var child = element.childNodes[ i ];
			if (
				aria.Utils.attemptFocus( child ) ||
				aria.Utils.focusFirstDescendant( child )
			) {
				return true;
			}
		}
		return false;
	};

	/**
	 * Find the last descendant node that is focusable.
	 * @param element
	 *          DOM node for which to find the last focusable descendant.
	 * @returns {boolean}
	 *          true if a focusable element is found and focus is set.
	 */
	aria.Utils.focusLastDescendant = function ( element ) {
		for ( var i = element.childNodes.length - 1; i >= 0; i-- ) {
			var child = element.childNodes[ i ];
			if (
				aria.Utils.attemptFocus( child ) ||
				aria.Utils.focusLastDescendant( child )
			) {
				return true;
			}
		}
		return false;
	};

	/**
	 * Set Attempt to set focus on the current node.
	 * @param element
	 *          The node to attempt to focus on.
	 * @returns {boolean}
	 *  true if element is focused.
	 */
	aria.Utils.attemptFocus = function ( element ) {
		if ( !aria.Utils.isFocusable( element ) ) {
			return false;
		}

		aria.Utils.IgnoreUtilFocusChanges = true;
		try {
			element.focus();
		} catch ( e ) {
			// continue regardless of error
		}
		aria.Utils.IgnoreUtilFocusChanges = false;
		return document.activeElement === element;
	}; // end attemptFocus

	/* Modals can open modals. Keep track of them with this array. */
	aria.OpenDialogList = aria.OpenDialogList || new Array( 0 );

	/**
	 * @returns {object} the last opened dialog (the current dialog)
	 */
	aria.getCurrentDialog = function () {
		if ( aria.OpenDialogList && aria.OpenDialogList.length ) {
			return aria.OpenDialogList[ aria.OpenDialogList.length - 1 ];
		}
	};

	aria.closeCurrentDialog = function () {
		var currentDialog = aria.getCurrentDialog();
		if ( currentDialog ) {
			// currentDialog.close();
			window.dispatchEvent(new Event('closePopovers'));
			return true;
		}

		return false;
	};

	aria.handleEscape = function ( event ) {
		var key = event.which || event.keyCode;

		if ( key === 27 && aria.closeCurrentDialog() ) {
			event.stopPropagation();
		}
	};

	document.addEventListener( 'keyup', aria.handleEscape );

	/**
	 * @class
	 * Dialog object providing modal focus management.
	 *
	 * Assumptions: The element serving as the dialog container is present in the
	 * DOM and hidden. The dialog container has role='dialog'.
	 * @param dialogId
	 *          The ID of the element serving as the dialog container.
	 * @param focusAfterClosed
	 *          Either the DOM node or the ID of the DOM node to focus when the
	 *          dialog closes.
	 * @param focusFirst
	 *          Optional parameter containing either the DOM node or the ID of the
	 *          DOM node to focus when the dialog opens. If not specified, the
	 *          first focusable element in the dialog will receive focus.
	 */
	aria.Dialog = function ( dialogId, focusAfterClosed, focusFirst ) {
		this.dialogNode = document.getElementById( dialogId );
		if ( this.dialogNode === null ) {
			throw new Error( 'No element found with id="' + dialogId + '".' );
		}

		var validRoles = [ 'dialog', 'alertdialog' ];
		var isDialog = ( this.dialogNode.getAttribute( 'role' ) || '' )
			.trim()
			.split( /\s+/g )
			.some( function ( token ) {
				return validRoles.some( function ( role ) {
					return token === role;
				} );
			} );
		if ( !isDialog ) {
			throw new Error(
				'Dialog() requires a DOM element with ARIA role of dialog or alertdialog.'
			);
		}

		// Wrap in an individual backdrop element if one doesn't exist
		// Native <dialog> elements use the ::backdrop pseudo-element, which
		// works similarly.
		const backdropClass = 'dialog-backdrop';
		if ( this.dialogNode.nextSibling?.classList?.contains( backdropClass ) ) {
			this.backdropNode = this.dialogNode.nextSibling;
		} else {
			this.backdropNode = document.createElement( 'div' );
			this.backdropNode.className = backdropClass;
			this.backdropNode.setAttribute( 'onclick', 'closeDialog(this)' );
			this.dialogNode.parentNode.insertBefore(
				this.backdropNode,
				this.dialogNode.nextSibling

			);
		}

		// Disable scroll on the body element
		if ( !this.dialogNode.classList.contains( 'is-variation-dropdown' ) ) {
			document.body.classList.add( aria.Utils.dialogOpenClass );
		}

		if ( typeof focusAfterClosed === 'string' ) {
			this.focusAfterClosed = document.getElementById( focusAfterClosed );
		} else if ( typeof focusAfterClosed === 'object' ) {
			this.focusAfterClosed = focusAfterClosed;
		} else {
			throw new Error(
				'the focusAfterClosed parameter is required for the aria.Dialog constructor.'
			);
		}

		if ( typeof focusFirst === 'string' ) {
			this.focusFirst = document.getElementById( focusFirst );
		} else if ( typeof focusFirst === 'object' ) {
			this.focusFirst = focusFirst;
		} else {
			this.focusFirst = null;
		}

		// Bracket the dialog node with two invisible, focusable nodes.
		// While this dialog is open, we use these to make sure that focus never
		// leaves the document even if dialogNode is the first or last node.
		var preDiv = document.createElement( 'div' );
		this.preNode = this.dialogNode.parentNode.insertBefore(
			preDiv,
			this.dialogNode
		);
		this.preNode.tabIndex = 0;
		var postDiv = document.createElement( 'div' );
		this.postNode = this.dialogNode.parentNode.insertBefore(
			postDiv,
			this.dialogNode.nextSibling
		);
		this.postNode.tabIndex = 0;

		// If this modal is opening on top of one that is already open,
		// get rid of the document focus listener of the open dialog.
		if ( aria.OpenDialogList.length > 0 ) {
			aria.getCurrentDialog().removeListeners();
		}

		this.addListeners();
		aria.OpenDialogList.push( this );
		this.dialogNode.setAttribute( 'open', true );

		/**
		 * Set a timeout before focusing the first element. Otherwise
		 * this could lead to overflow errors and the inner content
		 * scrolls down because the browser tries to focus an element
		 * that isn't visible.
		 */
		setTimeout( () => {
			if ( this.focusFirst ) {
				this.focusFirst.focus();
			} else {
				aria.Utils.focusFirstDescendant( this.dialogNode );
			}
		}, 100 );

		this.lastFocus = document.activeElement;
	};

	/**
	 * @description
	 *  Hides the current top dialog,
	 *  removes listeners of the top dialog,
	 *  restore listeners of a parent dialog if one was open under the one that just closed,
	 *  and sets focus on the element specified for focusAfterClosed.
	 */
	aria.Dialog.prototype.close = function () {
		aria.OpenDialogList.pop();
		this.removeListeners();
		aria.Utils.remove( this.preNode );
		aria.Utils.remove( this.postNode );
		this.dialogNode.removeAttribute( 'open' );
		this.focusAfterClosed.focus();

		// pause videos & iframes inside
		const videos = this.dialogNode.querySelectorAll(".wp-block-video video, .wp-block-embed iframe");
		if ( NodeList.prototype.isPrototypeOf(videos) ) {
			videos.forEach(video => {
				if (video.tagName == "VIDEO") video.pause();
				if (video.tagName == "IFRAME") {
					const src = video.src;
					video.src = src;
				}
			});
		}

		// If a dialog was open underneath this one, restore its listeners.
		if ( aria.OpenDialogList.length > 0 ) {
			aria.getCurrentDialog().addListeners();
		} else {
			document.body.classList.remove( aria.Utils.dialogOpenClass );
		}
	};

	aria.Dialog.prototype.addListeners = function () {
		document.addEventListener( 'focus', this.trapFocus, true );
	};

	aria.Dialog.prototype.removeListeners = function () {
		document.removeEventListener( 'focus', this.trapFocus, true );
	};

	aria.Dialog.prototype.trapFocus = function ( event ) {
		if ( aria.Utils.IgnoreUtilFocusChanges ) {
			return;
		}
		var currentDialog = aria.getCurrentDialog();
		if ( currentDialog.dialogNode.contains( event.target ) ) {
			currentDialog.lastFocus = event.target;
		} else {
			aria.Utils.focusFirstDescendant( currentDialog.dialogNode );
			if ( currentDialog.lastFocus == document.activeElement ) {
				aria.Utils.focusLastDescendant( currentDialog.dialogNode );
			}
			currentDialog.lastFocus = document.activeElement;
		}
	};

	window.openDialog = function ( dialogId, focusAfterClosed, focusFirst ) {
		new aria.Dialog( dialogId, focusAfterClosed, focusFirst );
	};

	window.closeDialog = function ( closeButton ) {
		aria.getCurrentDialog()?.close();
	};
} )();

/**
 * @since 2.10.0 If an anchor link inside a popover is clicked, close the popover.
 */
document.addEventListener( "DOMContentLoaded", function() {

	// get all anchor link elements inside popover popups
	let popoverAnchorLinks = document.querySelectorAll('.wp-block-greyd-popover-popup a[href*="#"]');

	// return if none found
	if ( popoverAnchorLinks.length == 0 ) return;

	// trigger 'closePopovers' event when an anchor link is clicked
	popoverAnchorLinks.forEach( ( link ) => {
		link.addEventListener('click', () => {
			window.dispatchEvent( new Event('closePopovers') );
		});
	});

} );