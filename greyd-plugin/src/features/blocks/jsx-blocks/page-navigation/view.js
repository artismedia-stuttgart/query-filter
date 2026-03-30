// Frontend JavaScript functionality can be added here if needed
var greyd = greyd || {};

/**
 * Greyd.PostHierarchyNavigation object.
 */
greyd.postHierarchyNavigation = {
	init: () => {
		let navigations = [].slice.call( document.querySelectorAll( ".wp-block-greyd-page-navigation:not([data-init='1'])" ) );

		navigations.forEach( function ( navigation ) {
			const toggles = [].slice.call( navigation.querySelectorAll( '.page-navigation-toggle' ) );

			if ( !toggles.length ) return;

			toggles.forEach( function ( toggle ) {
				toggle.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					const isExpanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
					const panel = document.getElementById( toggle.getAttribute( 'aria-controls' ) );

					// Toggle the expanded state
					toggle.setAttribute( 'aria-expanded', !isExpanded );
					panel.setAttribute( 'aria-hidden', isExpanded );
				} );
			} );

			navigation.dataset.init = '1';
		} );
	}
};

/**
 * Init the scripts.
 */
addEventListener( 'DOMContentLoaded', function () {
	greyd.postHierarchyNavigation.init();
} );