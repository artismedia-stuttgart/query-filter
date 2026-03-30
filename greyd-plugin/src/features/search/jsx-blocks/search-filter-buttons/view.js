/**
 * Filter buttons
 */
document.addEventListener( "DOMContentLoaded", function () {

	const filterWrappers = document.querySelectorAll( ".wp-block-greyd-search-filter-buttons" );

	filterWrappers.forEach( filterWrapper => {
		
		const filterButtons = filterWrapper.querySelectorAll( ".greyd_filter_button" );
		const isMultiselect = filterWrapper.getAttribute( "data-multiselect" ) === "true" ? true : false;
		const hiddenInput   = filterWrapper.querySelector( "input[type='hidden']" );
		const resetButton   = filterWrapper.querySelector( ".reset-button" );
		const liveRegion    = filterWrapper.querySelector( "[aria-live]" );

		if ( hiddenInput.value.length > 1 ) {
			const values = hiddenInput.value.split( "," );
			filterButtons.forEach( button => {
				const optionValue = button.querySelector( "input[type='radio']" ).value;
				if ( values.includes( optionValue ) ) {
					button.classList.add( "is-active" );
				}
			} );
		}

		if ( !filterButtons.length ) return;

		filterButtons.forEach( filterButton => {
			const optionValue = filterButton.querySelector( "input[type='radio']" ).value;
			
			const activateButton = function () {

				if ( filterButton.classList.contains( "reset-button" ) ) {
					hiddenInput.value = '';
					filterButtons.forEach( button => {
						if ( button !== filterButton ) {
							button.classList.remove( "is-active" );
							button.setAttribute( "aria-pressed", "false" );
						} else {
							button.classList.add( "is-active" );
							button.setAttribute( "aria-pressed", "true" );
						}
					} );
					hiddenInput.dispatchEvent( new Event( "change" ) );
					announceToScreenReader( "All filters cleared" );
					return;
				}
				
				if ( !isMultiselect ) {
					
					const isSelected = filterButton.classList.contains( "is-active" );
					const buttonText = filterButton.querySelector( ".label" ).textContent.trim();
					
					hiddenInput.value = isSelected ? '' : optionValue;
					
					// trigger change event
					hiddenInput.dispatchEvent( new Event( "change" ) );

					filterButtons.forEach( button => {
						if ( button !== filterButton ) {
							button.classList.remove( "is-active" );
							button.setAttribute( "aria-pressed", "false" );
						}
					} );
					
					// Announce the change
					if ( isSelected ) {
						announceToScreenReader( `Filter "${buttonText}" removed` );
					} else {
						announceToScreenReader( `Filter "${buttonText}" selected` );
					}
				} else {
					let values = hiddenInput.value;
					values = values.split( "," ).filter( value => value.length > 0 );

					let index = values.indexOf( optionValue );
					const buttonText = filterButton.querySelector( ".label" ).textContent.trim();

					if ( index > -1 ) {
						values.splice( index, 1 );
						announceToScreenReader( `Filter "${buttonText}" removed` );
					} else {
						values.push( optionValue );
						announceToScreenReader( `Filter "${buttonText}" added` );
					}
					
					hiddenInput.value = values.join( "," );

					if ( resetButton ) {
						resetButton.classList.remove( "is-active" );
						resetButton.setAttribute( "aria-pressed", "false" );
					}

					// trigger change event
					hiddenInput.dispatchEvent( new Event( "change" ) );
				}

				filterButton.classList.toggle( "is-active" );
				filterButton.setAttribute( "aria-pressed", filterButton.classList.contains( "is-active" ) ? "true" : "false" );
			};

			// Click event
			filterButton.addEventListener( "click", activateButton );

			// Keyboard events
			filterButton.addEventListener( "keydown", function ( event ) {
				if ( event.key === "Enter" || event.key === " " ) {
					event.preventDefault();
					activateButton();
				}
			} );

			/**
			 * Remove the default link behavior from the label element
			 * This is necessary because we now use a true <label> element instead of a <span>
			 * @since 2.17.5
			 */
			const label = filterButton.querySelector( "label.label" );
			if ( label ) {
				label.addEventListener( "click", ( e ) => {
					e.preventDefault();
				} );
				label.addEventListener( "keydown", ( e ) => {
					if ( e.key === "Enter" || e.key === " " ) {
						e.preventDefault();
						activateButton();
					}
				} );
			}
		});

		// Function to announce changes to screen readers
		const announceToScreenReader = ( message ) => {
			if ( liveRegion ) {
				liveRegion.textContent = message;
				// Clear the message after a short delay to allow for future announcements
				setTimeout( () => {
					liveRegion.textContent = '';
				}, 1000 );
			}
		};
	});
} );