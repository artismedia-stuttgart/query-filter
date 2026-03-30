var greyd = greyd || {};

/**
 * Greyd.Accordions object.
 */
greyd.accordions = {
	
	init: () => {

		let accordions = [].slice.call(document.querySelectorAll(".wp-block-greyd-accordion:not([data-init='1'])"));
		
		accordions.forEach(function(accordion) {
	
			const autoClose = accordion.dataset.autoclose == 'true';
			const openFirst = accordion.dataset.openfirst == 'true';

			const titles = [].slice.call(accordion.querySelectorAll('.wp-block-greyd-accordion__title'));
	
			if ( ! titles.length ) return;

			const openSection = (title) => {
				title.setAttribute('aria-expanded', 'true');
				title.nextElementSibling.removeAttribute('hidden'); /** @since 2.17.5 */
			}

			const closeSection = (title) => {
				title.setAttribute('aria-expanded', 'false');
				title.nextElementSibling.setAttribute('hidden', true); /** @since 2.17.5 */
			}
	
			if ( openFirst ) {
				openSection(titles[0]);
			}
	
			titles.forEach(function(title) {
				title.addEventListener( 'click', function(e) {
		
					if ( title.getAttribute('aria-expanded') == 'false' ) {
		
						if ( autoClose ) {
							const activeSections = accordion.querySelectorAll('.wp-block-greyd-accordion__title[aria-expanded="true"]');
							Array.prototype.forEach.call(activeSections, function (section) {
								closeSection(section);
							});
						}
						openSection(title);
					}
					else {
						closeSection(title);
					}
				} )
			});
	
			accordion.dataset.init = '1';
		});
	}
};

/**
 * Init the scripts.
 */
addEventListener( 'DOMContentLoaded', function () {
	greyd.accordions.init();
} );