/**
 * Navigation Extension frontend features:
 * anchor-active-style.
 * 
 * @since 1.13.0
 * @see greyd.scrollObserver for details on the mechanic.
 */

document.addEventListener( "DOMContentLoaded", function() {

	if ( !greyd?.scrollObserver ) return;

	function initNavigations() {

		// get navigation blocks with anchor-active-style
		var navBlocks = document.querySelectorAll('.wp-block-navigation.is-anchor-active-style');
		if ( navBlocks.length == 0 ) return;

		navBlocks.forEach( ( wrapper, key ) => {

			const setup = wrapper.dataset.anchoractive ? JSON.parse(wrapper.dataset.anchoractive) : {};
			if ( !setup.enable) return;

			const activeLinks = wrapper.querySelectorAll('a.wp-block-navigation-item__content[href*="#"]');
			if ( activeLinks.length == 0 ) return;

			// setup
			let start = setup.start ? parseInt(setup.start) : 0;
			let end = setup.end ? parseInt(setup.end) : 100;
			if ( end < start ) {
				let tmp = start;
				start = end;
				end = tmp;
			}
			const multiple = setup.multiple ? setup.multiple : '';
			const none = setup.none ? setup.none : '';
	
			let items = [];
			activeLinks.forEach( ( link, key ) => {
				const item = link.parentElement;
				// get items href, anchor-id and anchor-element
				const href = link.href;
				const id = href.split('#')[1];
				const anchor = document.getElementById(id);
				if ( anchor ) {
					// console.log(item);
					// console.log(href);
					items.push( { item: item, anchor: anchor } );

					var simple = start == 0 && end == 100 && multiple == '' && none == '';
					if ( simple ) {
						// simple observer (default setup)
						greyd.scrollObserver.observeElement(
							anchor,
							( target, event, inViewport ) => {
								// toggle active class
								if ( inViewport ) {
									item.classList.add('current-menu-item');
								}
								else {
									item.classList.remove('current-menu-item');
								}
							},
							'change'
						);
					}
					else {
						// advanced observer
						greyd.scrollObserver.observeElement(
							anchor,
							( target, event, inViewport ) => {

								// get offsets
								var boundingClientRect = target.getBoundingClientRect();
								var targetTop          = boundingClientRect.y;
								var targetBottom       = targetTop + boundingClientRect.height;
								var endLine            = (window.innerHeight / 100) * end;
								var startLine          = (window.innerHeight / 100) * start;

								// get distance to center function
								const getDistance = ( element ) => {
									// get offsets
									var bounds = element.getBoundingClientRect();
									var top    = bounds.y;
									var bottom = top + bounds.height;
									var center = (endLine + startLine) / 2;
									// var center = window.innerHeight / 2;
									// calculate distance
									var distance = 0;
									if ( bottom < center && top < center ) {
										// above center
										distance = center - bottom;
									}
									if ( bottom > center && top > center ) {
										// below center
										distance = top - center;
									}
									return distance;
								};

								// check if target is between start and end
								var inside = targetBottom > startLine && targetTop < endLine;
								
								// multiple: show closest to center
								if ( inside && multiple == 'closest' ) {

									// calculate this distance to center
									var distance = getDistance(target);
									// check if other item is active
									items.forEach( (other) => {
										// escape this and non active items
										if ( other.item === item || !other.item.classList.contains('current-menu-item') ) return;
										// calculate other distance
										if ( getDistance(other.anchor) < distance ) {
											// don't show this if other item is closer to center
											inside = false;
										}
									} );

								}

								// multiple: show only latest
								if ( inside && !item.classList.contains('current-menu-item') && multiple == 'latest' ) {

									// deactivate other elements
									items.forEach( (other) => {
										// escape this and non active items
										if ( other.item === item || !other.item.classList.contains('current-menu-item') ) return;
										other.item.classList.remove('current-menu-item');
									} );

								}
								
								// toggle active class
								if ( inside ) {
									item.classList.add('current-menu-item');
								}
								else {
									item.wasActive = item.classList.contains('current-menu-item');
									if ( item.wasActive ) items.forEach( (other) => {
										// escape this
										if ( other.item === item ) return;
										other.item.wasActive = false;
									} );

									item.classList.remove('current-menu-item');
								}

								// no active
								if ( none != '' ) {

									// check if any item is active
									var anyActive = false;
									var distances = [];
									items.forEach( (other, index) => {
										// remove keeper class
										if ( other.item.classList.contains('keep-anchor-active') ) {
											other.item.classList.remove('current-menu-item', 'keep-anchor-active');
										}
										// check if item is active
										if ( other.item.classList.contains('current-menu-item') ) {
											anyActive = true;
										}
										else {
											// get distance to center for closest/fallback
											distances.push( { index: index, distance: getDistance(other.anchor) } );
										}
									} );
									if ( !anyActive ) {

										// keep active until next element enters
										if ( none == 'keep' ) {
											items.forEach( (other, index) => {
												// check if item was active
												if ( other.item.wasActive ) {
													other.item.classList.add('current-menu-item', 'keep-anchor-active');
													anyActive = true;
												}
											} );
										}

										// activate closest element
										// fallback / none == 'closest'
										if ( !anyActive ) {
											// sort by distance to center
											distances.sort((a, b) => a.distance - b.distance);
											// add keeper class if no other item is active
											items[distances[0].index].item.classList.add('current-menu-item', 'keep-anchor-active');
										}
										
									}

								}

							},
							'always'
						);

						// remove active class initially
						// (observeElement 'always' does not trigger on inViewport false)
						item.classList.remove('current-menu-item');
					}
				}
			} );

		} );

		console.log("navigation scripts loaded.");
	}

	initNavigations();

} );