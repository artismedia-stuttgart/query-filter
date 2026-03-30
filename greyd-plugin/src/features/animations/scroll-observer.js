/**
 * Greyd public scroll event handler.
 * 
 * This script can handle scroll events globally to save ressources.
 * Elements can be observed entering and/or leaving the viewport
 * or while they are in the viewport.
 * You can easily add elements to be observed using this function:
 * @see greyd.scrollObserver.observeElement()
 * 
 * @since 1.6.0
 * @namespace greyd
 * 
 * @note This script is not loaded by default. Currently it is only used
 * inside the animation feature and therefore stil located here.
 * @todo Move this script to a more appropriate location as soon
 * as it is used in other features as well.
 * 
 * @todo IntersectionObserver polyfill:
 * We still need to add a polyfill for backwards compatiblity with
 * older browsers, even though support for it is above 95% as of
 * January 2023.
 * @link https://caniuse.com/intersectionobserver
 */
var greyd = greyd || {};

greyd.scrollObserver = new function () {

	/**
	 * This class defines the way observed target elements are saved.
	 * @param {documentElement} target
	 * @param {function} callback
	 * @param {string} type
	 * 
	 * @see greyd.scrollObserver.observeElement() for more details.
	 */
	class observedElement {
		constructor ( target, callback, type ) {
			this.target = target;
			this.callback = callback;
			this.type = typeof type === 'undefined' ? 'once' : type;
		}
	}

	/**
	 * Holds all targets to be observed.
	 * @var {observedElement[]}
	 */
	this.allObservedElements = [];

	/**
	 * Holds all observed targets that are currently in the viewport.
	 * @var {documentElement[]}
	 */
	this.allViewportElements = [];

	/**
	 * Holds the IntersectionObserver.
	 * @link https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
	 * @var {object}
	 */
	this.intersectionObserver;

	/**
	 * Holds the configuration for the IntersectionObserver.
	 */
	this.intersectionObserverConfig = {
		root: null, // The element used as the viewport for checking visibility
		rootMargin: '0px', // Margin around the root. Can have values similar to the CSS
		threshold: 0  // what percentage of the target is visible
	};

	/**
	 * Init the observer.
	 * This function is automatically called once the first element
	 * is registered using `greyd.scrollObserver.observeElement()`
	 */
	this.init = function () {

		// define the IntersectionObserver
		if ( "IntersectionObserver" in window ) {
			this.intersectionObserver = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.intersectionRatio >= 0 ) {
							this.entersViewport( entry.target );
						} else {
							this.leavesViewport( entry.target );
						}
					} );
				},
				this.intersectionObserverConfig
			);
		} else {
			/** @todo fallback for IntersectionObserver  */
		}

		// define scroll event listener
		document.addEventListener( 'scroll', this.onScroll );
	};

	/**
	 * Start observing an element.
	 * @param {documentElement} target DOM element.
	 * @param {function} callback      Callback function. Called with target as argument.
	 *      @argument {documentElement} target
	 *      @argument {object} event
	 *      @argument {bool} inViewport
	 * @param {string} type            How to observe the element:
	 *      @value once    Callback is called one time as soon as the target is in the viewport.
	 *                     After that the element is no longer observed.
	 *      @value change  Target stays observed and callback is fired each time the target
	 *                     enters or leaves the viewport.
	 *      @value always  Target stays observed and callback is fired on every scroll event
	 *                     while the element is in the viewport.
	 */
	this.observeElement = function ( target, callback, type ) {

		// init the observer
		if ( ! this.intersectionObserver ) {
			this.init();
		}

		this.intersectionObserver.observe( target );
		this.allObservedElements.push(
			new observedElement( target, callback, type )
		);
	};

	/**
	 * Stop observing an element.
	 * @param {documentElement} target DOM element.
	 */
	this.unobserveElement = function ( target ) {
		this.intersectionObserver.unobserve( target );

		// remove target from current observed elements
		let index = this.allObservedElements.indexOf( target );
		if ( index > -1 ) {
			this.allObservedElements.splice( index, 1 );
		}

		// remove target from current viewport elements
		index = this.allViewportElements.indexOf( target );
		if ( index > -1 ) {
			this.allViewportElements.splice( index, 1 );
		}
	};

	/**
	 * A target element enters the viewport.
	 * @param {documentElement} target DOM element.
	 */
	this.entersViewport = function ( target ) {

		var elements = this.getObservedElements( target );
		if ( elements ) elements.forEach( ( element ) => {
				
			const { callback, type } = element;

			if ( type === 'always' ) {
				// add target to current viewport elements.
				this.allViewportElements.push( target );
			}
			else if ( type === 'once' ) {
				// unobserve, because we no longer need to watch it.
				this.unobserveElement( target );
			}

			callback( target, null, true );
			
		} );

	};

	/**
	 * A target element leaves the viewport.
	 * @param {documentElement} target DOM element.
	 */
	this.leavesViewport = function ( target ) {
	
		// remove target from current viewport elements
		const index = this.allViewportElements.indexOf( target );
		if ( index > -1 ) {
			this.allViewportElements.splice( index, 1 );
		}

		var elements = this.getObservedElements( target );
		if ( elements ) elements.forEach( ( element ) => {
				
			const { callback, type } = element;

			if ( type === 'change' ) {
				// trigger change
				callback( target, null, false );
			}

		} );

	};

	/**
	 * A target element is in the viewport.
	 * @param {documentElement} target DOM element.
	 * @param {object} e Scroll event.
	 */
	this.whileInViewport = function ( target, e ) {

		var elements = this.getObservedElements( target );
		if ( elements ) elements.forEach( ( element ) => {

			const { callback } = element;

			callback( target, e, true );

		} );

	};

	/**
	 * User scrolls.
	 * @param {object} e Scroll event.
	 */
	this.onScroll = function ( e ) {

		if ( this.allViewportElements.length === 0 ) return;

		this.allViewportElements.forEach(
			target => this.whileInViewport( target, e )
		)
	};

	/**
	 * Get the observedElement objects of the current target.
	 * @since 1.13.0
	 * return array instead of single element to allow
	 * multiple callbacks on one observed element.
	 * 
	 * @param {documentElement} target DOM element.
	 * @returns {observedElement[]}
	 */
	this.getObservedElements = function ( target ) {
		const result = this.allObservedElements.filter( obj => {
			return obj.target === target;
		} );
		return result.length ? result : null;
	};


	/**
	 * bind keyword "this" inside functions to the class object
	 */
	this.init                   = this.init.bind( this );
	this.observeElement         = this.observeElement.bind( this );
	this.unobserveElement       = this.unobserveElement.bind( this );
	this.entersViewport         = this.entersViewport.bind( this );
	this.leavesViewport         = this.leavesViewport.bind( this );
	this.onScroll               = this.onScroll.bind( this );
	this.whileInViewport        = this.whileInViewport.bind( this );
	this.getObservedElements    = this.getObservedElements.bind( this );
};