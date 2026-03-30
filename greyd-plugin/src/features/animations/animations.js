/**
 * Greyd public animations handler.
 * 
 * @since 1.6.0
 * @namespace greyd
 * 
 * @todo Fix issue with elements leaving the viewport horizontally.
 * @description
 * This function uses an IntersectionObserver to watch if elements
 * enter or leave the viewport. This is used to animate targets.
 * If an element leaves the viewport during the animation, it
 * technically leaves the viewport and therefore is no longer
 * observed. There is no native way to move it back in.
 * This problem could be fixed, using an scroll-event handler.
 * So whenever an element has an translateX() set, we also need
 * to watch it with a scroll event handler.
 * @see greyd.scrollObserver for details on the mechanic.
 */
var greyd = greyd || {};

/**
 * Greyd.Animations class.
 */
greyd.animations = new function () {

	/**
	 * Whether the user has 'reduced-motion' turned on.
	 * @var {bool}
	 */
	this.usesReducedMotion = false;

	/**
	 * Init the class & events.
	 */
	this.init = function () {
		
		let animTargets = document.querySelectorAll( "[data-anim-action]:not([data-anim-init='1'])" );

		if ( animTargets.length === 0 ) return;

		const mediaQuery = window.matchMedia("prefers-reduced-motion: reduce");
		this.usesReducedMotion = !mediaQuery || mediaQuery.matches;

		animTargets.forEach( target => {
			greyd.animations.setAnimationEvents( target );
		} );
	};

	/**
	 * Setup all the necessary animation events.
	 * @param {documentElement} target DOM element.
	 */
	this.setAnimationEvents = function ( target ) {

		const animEvent = target.dataset.animEvent;

		switch ( animEvent ) {

			/**
			 * Hover & sticky events are handled via CSS.
			 */
			case "hover":
			case "parentHover":
			case "parentSticky":
				break;

			/**
			 * Add Click events.
			 */
			case "click":
				target.addEventListener( 'click', ( e ) => {
					target.dataset.animTriggered = target.dataset.animTriggered === "true" ? "false" : "true"
				} );
				break;
			case "parentClick":
				const parentSelector = target.dataset.animParent;
				const closestParent  = greyd.animations.getClosest( target, parentSelector );
				if ( closestParent ){
					closestParent.addEventListener( 'click', ( e ) => {
						target.dataset.animTriggered = target.dataset.animTriggered === "true" ? "false" : "true"
					} );
				}
				break;

			/**
			 * Add Scroll events
			 */
			case "whileScroll":
			case "onScroll":
				greyd.scrollObserver.observeElement(
					target,
					greyd.animations.whileInViewport,
					'always' // animEvent === 'whileScroll' ? 'always' : 'once'
				)
				break;

			/**
			 * Add Sticky events
			 */
			case "isSticky":
				greyd.scrollObserver.observeElement(
					target,
					greyd.animations.whenSticky,
					'always' // animEvent === 'whileScroll' ? 'always' : 'once'
				)

				break;

			/**
			 * @since 2.10.0 load animations.
			 */
			case "load":
				target.dataset.animTriggered = "true";
				if ( target.dataset.animReverse === "true" ) {
					setInterval( () => {
						target.dataset.animTriggered = target.dataset.animTriggered === "true" ? "false" : "true"
					}, parseInt( target.dataset.animTime ) );
				}
				break;

		}

		// set data-anim-init=true
		target.dataset.animInit = "1";
	};

	/**
	 * Animate an element once it's in the viewport.
	 * @param {documentElement} target DOM element.
	 * @param {object} event
	 * @param {bool} inViewport
	 */
	this.whileInViewport = function ( target, e, inViewport ) {

		const animEvent = target.dataset.animEvent;

		if ( animEvent === 'onScroll' ) {
			greyd.animations.animateElementOnScroll( target );
		}
		else if ( animEvent === 'whileScroll' ) {
			greyd.animations.animateElementWhileScroll( target )
		}
	};

	/**
	 * Animate an element once it's sticky
	 * @param {documentElement} target DOM element.
	 * @param {object} event
	 * @param {bool} inViewport
	 */
	this.whenSticky = function ( target ) {

		if ( target.className.indexOf('is-position-sticky') < 0 ) {
			greyd.scrollObserver.unobserveElement( target );
		}

		if ( typeof target.dataset.offsetTop === "undefined" ) {
			target.dataset.offsetTop = parseInt( window.getComputedStyle(target).getPropertyValue('top') ) + 1;
		} else {
			const boundingClientRect = target.getBoundingClientRect();
			if ( boundingClientRect.y <= target.dataset.offsetTop ) {
				target.dataset.animTriggered = "true";
			} else {
				target.dataset.animTriggered = "false";
			}
		}
	};
	
	/**
	 * Animate a target element now.
	 * @param {documentElement} target DOM element.
	 */
	this.animateElementOnScroll = function ( target ) {

		const animStart = target.dataset.animStart ? parseInt( target.dataset.animStart ) : 50;

		if ( isNaN(animStart) ) {
			console.error('Invalid data-anim-start attribute:', target);
			return;
		}

		/**
		 * @since 1.7.1 We added the option to use individual scroll points.
		 * 
		 * Relative values have the suffix '%', absolute values have the suffix 'px'.
		 * Those were added for proper backward compatibility, because we used simple
		 * numeric values before, like '50' or '100', that are now converted to '50%'
		 * and '100px' respectively.
		 */
		if ( typeof target.dataset.animStart == 'string' && target.dataset.animStart.indexOf('px') > -1 ) {
			
			const scrollTop  = window.pageYOffset || document.documentElement.scrollTop;

			if ( target.dataset.animReverse === "true" ) {
				if ( animStart > scrollTop ) {
					target.dataset.animTriggered = "false";
				} else {
					target.dataset.animTriggered = "true";
				}
			} else {
				if ( animStart <= scrollTop ) {
					target.dataset.animTriggered = "true";
					greyd.scrollObserver.unobserveElement( target );
				}
			}
		}
		/**
		 * Scroll point relative to the target.
		 */
		else {

			// get scroll offset
			const startLine = (window.innerHeight / 100) * animStart;
			
			// trigger animation in both directions
			if ( target.dataset.animReverse === "true" ) {
				const boundingClientRect = target.getBoundingClientRect();
				const targetCenter       = boundingClientRect.y + (boundingClientRect.height / 2);
				if ( targetCenter <= startLine && target.dataset.animTriggered === "false" ) {
					target.dataset.animTriggered = "true";
				} else if ( targetCenter > startLine && target.dataset.animTriggered === "true" ) {
					target.dataset.animTriggered = "false";
				}
			}
			// trigger animation only once
			else if ( target.getBoundingClientRect().y <= startLine ) {
				target.dataset.animTriggered = "true";
				greyd.scrollObserver.unobserveElement( target );
			}
		}
	};

	/**
	 * Animate a target element while scrolling
	 * @param {documentElement} target DOM element.
	 */
	this.animateElementWhileScroll = function ( target ) {

		const animStart = target.dataset.animStart ? parseInt(target.dataset.animStart) : 50;
		const animEnd = target.dataset.animEnd ? parseInt(target.dataset.animEnd) : 50;

		if (isNaN(animStart)) {
			console.error('Invalid data-anim-start attribute:', target);
			return;
		} else if (isNaN(animEnd)) {
			console.error('Invalid data-anim-end attribute:', animEnd);
			return;
		}

		// get scroll offset
		const boundingClientRect = target.getBoundingClientRect();
		const targetCenter       = boundingClientRect.y + (boundingClientRect.height / 2);
		const endLine            = (window.innerHeight / 100) * animEnd;
		let startLine          = (window.innerHeight / 100) * animStart;
		
		// return if the element is not in the area between the startLine and endLine
		if ( targetCenter > startLine || targetCenter < endLine ) {
			// set the offset to 0 so the animation starts from the beginning
			// when the element comes back into view.
			target.dataset.animOffset = 0;

			// if the anim was triggered before, reset the styles
			if ( target.dataset.animTriggered === "true" ) {
				const progress = targetCenter > startLine ? 0 : 1;
				const styles = greyd.animations.getAnimatedStyles( target, progress );
				for (let property in styles) {
					target.style[ property ] = styles[property];
				}
				target.dataset.animTriggered = "false";
			}

			return;
		}

		// If the element is within the defined area between the
		// startLine and endLine on load, the data attribute 'offset'
		// is not set yet. We set it now and return to prevent the
		// animation from 'jumping' to it's start position.
		if ( !target.hasAttribute('data-anim-offset') ) {
			const offset = parseInt(startLine - targetCenter);
			target.dataset.animOffset = offset;
			return;
		}

		// if we actually have an offset, we need to check if the element
		// is in between the startLine+offset and the endline.
		const offset = target.dataset.animOffset ? parseInt(target.dataset.animOffset) : 0;
		if ( offset > 0 ) {
			startLine = startLine - offset;

			if ( targetCenter > startLine || targetCenter < endLine ) {
				return;
			}
		}

		// calculate the progress of the animation between startLine and endLine.
		const progress = ( targetCenter - startLine ) / (endLine - startLine);

		// console.log( targetCenter, startLine, endLine, offset, progress );

		// set the styles
		const styles = greyd.animations.getAnimatedStyles( target, progress );
		for (let property in styles) {
			target.style[ property ] = styles[property];
		}

		if ( target.dataset.animTriggered === "false" ) {
			target.dataset.animTriggered = "true";
		}
	};

	/**
	 * Note: CSS variables (like var(--wp--preset--color--background)) cannot be 
	 * interpolated numerically. The animation system now detects CSS variables,
	 * resolves them to their actual color values using greyd.tools.convertVarToColor,
	 * and uses a threshold-based approach (50% progress) to switch between
	 * the initial and final states.
	 */

	/**
	 * Animate a target element while scrolling
	 * @param {documentElement} target DOM element.
	 * @param {float} progress relative value between 0 and 1
	 */
	this.getAnimatedStyles = function ( target, progress ) {

		// uses reduced motion
		if ( greyd.animations.usesReducedMotion ) {
			return greyd.animations.cssStringToObject( target.dataset.animTo );
		}

		// get css properties
		const styles         = {};
		const fromProperties = greyd.animations.cssStringToObject( target.dataset.animFrom );
		const toProperties   = greyd.animations.cssStringToObject( target.dataset.animTo );
		const properties     = Object.keys( { ...fromProperties, ...toProperties } );

		let computedStyle    = null;

		properties.forEach( property => {

			let fromValue = property in fromProperties ? fromProperties[ property ] : null;
			let toValue   = property in toProperties   ? toProperties[ property ]   : null;

			if ( ! fromValue || ! toValue ) {
				return;
			}

			// Check if either value contains CSS variables
			const fromHasCssVars = fromValue.indexOf('var(') > -1;
			const toHasCssVars = toValue.indexOf('var(') > -1;
			
			if ( fromHasCssVars || toHasCssVars ) {

				// handle color changes by using color-mix()
				if ( fromValue.indexOf('color') > -1 || toValue.indexOf('color') > -1 ) {
					styles[ property ] = 'color-mix(in hsl, ' + fromValue + ' ' + (100 - (progress * 100)) + '%, ' + toValue + ' ' + (100 * progress) + '%)';
					return;
				}
				// handle units by trying to resolve them to actual values
				else {

					// For CSS variables, we need to resolve them to actual values
					// and then use a threshold-based approach since we can't interpolate
					let resolvedFromValue = fromValue;
					let resolvedToValue = toValue;
					
					if ( ! computedStyle ) {
						computedStyle = window.getComputedStyle(document.body);
					}

					if ( fromHasCssVars ) {
						resolvedFromValue = computedStyle.getPropertyValue( fromValue.replace('var(', '').replace(')', '') );
					}

					if ( toHasCssVars ) {
						resolvedToValue = computedStyle.getPropertyValue( toValue.replace('var(', '').replace(')', '') );
					}
					
					// If conversion didn't work, keep original values
					// (this prevents undefined values from breaking the animation)
					if ( !resolvedFromValue || !resolvedToValue ) {
						styles[ property ] = progress < 0.5 ? fromValue : toValue;
						return;
					}
					else {
						fromValue = resolvedFromValue;
						toValue = resolvedToValue;
					}
				}
			}

			// try to parse numeric values
			const fromPieces = fromValue.match(/(\w+\()|([+-]?\d*\.\d+|[+-]?\d+)|(\))|([\%\w]+)|(\s+)|(,)/g);
			const toPieces   = toValue.match(/(\w+\()|([+-]?\d*\.\d+|[+-]?\d+)|(\))|([\%\w]+)|(\s+)|(,)/g);

			if ( fromPieces && toPieces ) {
				const pieces = fromPieces.map( ( from, i ) => {
					const to = toPieces[i];
					if ( greyd.animations.isNumeric( from ) && greyd.animations.isNumeric( to ) ) {
						return Math.round( greyd.animations.mapProgress( progress, 0, 1, from, to )  * 1000) / 1000;
					}
					return to;
				} );
				
				styles[ property ] = pieces.join('');
			} else {
				// fallback for non-parsable values
				styles[ property ] = progress < 0.5 ? fromValue : toValue;
			}
		} );

		return styles;
	}

	/**
	 * Converts a string into an object, where each CSS property is used as
	 * a key in the object, and each value is used as the value for that key.
	 * 
	 * @example
	 * * input:  'transform: translateX(-42px); color: #ddd;'
	 * * return: { transform: "translateX(-42px)", color: "#ddd" }
	 * 
	 * @param {string} cssString CSS styles.
	 * @returns {object}
	 */
	this.cssStringToObject = function (cssString) {
		let cssObject = {};
		let cssProperties = cssString.split(';');
		cssProperties.forEach(property => {
			let keyValue = property.split(':');
			if (keyValue.length === 2) {
				cssObject[keyValue[0].trim()] = keyValue[1].trim();
			}
		});
		return cssObject;
	}

	/**
	 * Converts an object into a string, where each CSS property is used as
	 * a key in the object, and each value is used as the value for that key.
	 * 
	 * @example
	 * * input:  { transform: "translateX(-42px)", color: "#ddd" }
	 * * return: 'transform: translateX(-42px); color: #ddd;'
	 * 
	 * @param {object} cssObject CSS styles object.
	 * @returns {string}
	 */
	this.objectToCssString = function(cssObject) {
		let cssString = "";
		for (let key in cssObject) {
			cssString += key + ": " + cssObject[key] + "; ";
		}
		return cssString;
	}

	/**
	 * Maps the relative value of a number in the range betwenn 2 numbers
	 * to a different range and returns the value.
	 * @param {int} value relative value between 0 and 1
	 * @param {int} inMin minimal progress.
	 * @param {int} inMax maximal progress.
	 * @param {int} outMin minimal value.
	 * @param {int} outMax maximal value.
	 * @returns 
	 */
	this.mapProgress = function ( value, inMin, inMax, outMin, outMax ) {
		if ( outMin < outMax ) {
			return (value - inMin) * (outMax - outMin) / (inMax - inMin) + Math.min(outMin, outMax);
		} else {
			return (value - inMin) * (outMax - outMin) / (inMax - inMin) + Math.max(outMin, outMax);
		}
	};

	/**
	 * Whether a value is numeric.
	 * @param {mixed} value 
	 * @returns {bool}
	 */
	this.isNumeric = function(value) {
		return !isNaN(parseFloat(value)) && isFinite(value);
	}

	/**
	 * Get the closest element by a certain selector.
	 * @param {*} elem 
	 * @param {*} selector 
	 * @returns 
	 */
	this.getClosest = function ( elem, selector ) {

		// Element.matches() polyfill
		if ( !Element.prototype.matches ) {
			Element.prototype.matches =
				Element.prototype.matchesSelector ||
				Element.prototype.mozMatchesSelector ||
				Element.prototype.msMatchesSelector ||
				Element.prototype.oMatchesSelector ||
				Element.prototype.webkitMatchesSelector ||
				function ( s ) {
					var matches = ( this.document || this.ownerDocument ).querySelectorAll( s ),
						i = matches.length;
					while ( --i >= 0 && matches.item( i ) !== this ) { }
					return i > -1;
				};
		}

		// Get the closest matching element
		for ( ; elem && elem !== document; elem = elem.parentNode ) {
			if ( elem.matches( selector ) ) return elem;
		}
		return null;
	};
	
	// this.isInViewport = function ( el ) {
	// 	const clientRect = el.getBoundingClientRect();
	// 	return (
	// 		clientRect.top >= 0 &&
	// 		clientRect.left >= 0 &&
	// 		clientRect.bottom <= ( window.innerHeight || document.documentElement.clientHeight ) &&
	// 		clientRect.right <= ( window.innerWidth || document.documentElement.clientWidth )
	// 	);
	// };
};

/**
 * Init the scripts.
 */
addEventListener( 'DOMContentLoaded', function () {
	greyd.animations.init();
} );