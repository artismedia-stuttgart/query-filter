/**
 * Re-initializes WordPress core lightbox functionality for dynamically loaded content
 * 
 * This function recreates the core WordPress lightbox behavior found in wp-includes/blocks/image/view.js
 * for images that are loaded via AJAX/live filtering. The core WordPress lightbox uses the 
 * Interactivity API which doesn't automatically bind to dynamically inserted content.
 * 
 * @param {HTMLElement} domElement - The DOM element to search for lightbox images within
 * 
 * WordPress Core Dependencies:
 * - Requires existing .wp-lightbox-overlay element in DOM (created by core)
 * - Uses core CSS custom properties for animation and positioning
 * - Expects .wp-lightbox-container structure around images
 * - Relies on .lightbox-image-container structure within overlay
 * 
 * Core WordPress Lightbox Features Replicated:
 * 1. setOverlayStyles() - Complete replication from wp-includes/blocks/image/view.js:208-349
 * 2. Image positioning and scaling calculations
 * 3. Responsive sizing with viewport padding
 * 4. Natural vs display ratio handling
 * 5. iOS Safari whitespace fix (+1px adjustment)
 * 
 * Adaptations Made:
 * - Removed Interactivity API state management dependency
 * - Added manual click event handlers instead of data-wp-on--click
 * - Direct CSS custom property manipulation instead of state.overlayStyles
 * - Simplified overlay activation (classList.toggle vs complex state management)
 * 
 * @since 1.0.0
 * @see wp-includes/blocks/image/view.js WordPress core lightbox implementation
 */
function reInitCoreLightboxEvents( domElement ) {

	if ( ! domElement ) return;

	// Early return if no WordPress core lightbox overlay exists
	const overlay = document.querySelector('.wp-lightbox-overlay');
	if ( ! overlay ) return;

	const images = domElement.querySelectorAll('.wp-lightbox-container img');
	if ( ! images || images.length === 0 ) return;

	/**
	 * Calculates and sets CSS custom properties for lightbox overlay positioning and sizing
	 * 
	 * This function is a complete replication of the setOverlayStyles callback from 
	 * WordPress core (wp-includes/blocks/image/view.js:208-349) with adaptations for 
	 * direct DOM manipulation instead of Interactivity API state management.
	 * 
	 * Core WordPress Algorithm Preserved:
	 * - Natural vs original image ratio calculations
	 * - Responsive container sizing with viewport padding
	 * - Image scaling and positioning for lightbox animation
	 * - Support for different image aspect ratios
	 * - iOS Safari rendering fix (+1px container adjustment)
	 * 
	 * Key Differences from Core:
	 * - Uses direct overlay.style.setProperty() instead of state.overlayStyles string
	 * - Simplified image size detection (removed targetWidth/targetHeight attributes)
	 * - Removed object-fit: contain special handling for scaleAttr
	 * 
	 * CSS Custom Properties Set (matching core implementation):
	 * - --wp--lightbox-initial-top-position: Starting Y position for animation
	 * - --wp--lightbox-initial-left-position: Starting X position for animation  
	 * - --wp--lightbox-container-width: Target container width
	 * - --wp--lightbox-container-height: Target container height
	 * - --wp--lightbox-image-width: Final image width in lightbox
	 * - --wp--lightbox-image-height: Final image height in lightbox
	 * - --wp--lightbox-scale: Scale factor for zoom animation
	 * - --wp--lightbox-scrollbar-width: Scrollbar compensation
	 * 
	 * @param {HTMLImageElement} img - The image element that was clicked to open lightbox
	 */
	const setOverlayStyles = ( img ) => {
		// WORDPRESS CORE CODE: Lines 212-221 from wp-includes/blocks/image/view.js
		// Extract image dimensions and screen position - identical to core implementation
		let {
			naturalWidth,
			naturalHeight,
			offsetWidth: originalWidth,
			offsetHeight: originalHeight
		} = img;
		let {
			x: screenPosX,
			y: screenPosY
		} = img.getBoundingClientRect();

		// WORDPRESS CORE CODE: Lines 223-226 from wp-includes/blocks/image/view.js
		// Natural ratio of the image clicked to open the lightbox.
		const naturalRatio = naturalWidth / naturalHeight;
		// Original ratio of the image clicked to open the lightbox.
		let originalRatio = originalWidth / originalHeight;

		// SIMPLIFIED: Core uses state.currentImage.targetWidth/targetHeight attributes
		// We default to natural dimensions for dynamically loaded images
		let imgMaxWidth = naturalWidth;
		let imgMaxHeight = naturalHeight;

		// Ratio of the biggest image stored in the database.
		let imgRatio = imgMaxWidth / imgMaxHeight;
		let containerMaxWidth = imgMaxWidth;
		let containerMaxHeight = imgMaxHeight;
		let containerWidth = imgMaxWidth;
		let containerHeight = imgMaxHeight;

		// WORDPRESS CORE CODE: Lines 259-299 from wp-includes/blocks/image/view.js
		// Checks if the target image has a different ratio than the original
		// one (thumbnail). Recalculates the width and height.
		if (naturalRatio.toFixed(2) !== imgRatio.toFixed(2)) {
			if (naturalRatio > imgRatio) {
				// If the width is reached before the height, it keeps the maxWidth
				// and recalculates the height unless the difference between the
				// maxHeight and the reducedHeight is higher than the maxWidth,
				// where it keeps the reducedHeight and recalculate the width.
				const reducedHeight = imgMaxWidth / naturalRatio;
				if (imgMaxHeight - reducedHeight > imgMaxWidth) {
					imgMaxHeight = reducedHeight;
					imgMaxWidth = reducedHeight * naturalRatio;
				} else {
				imgMaxHeight = imgMaxWidth / naturalRatio;
				}
			} else {
				// If the height is reached before the width, it keeps the maxHeight
				// and recalculate the width unlesss the difference between the
				// maxWidth and the reducedWidth is higher than the maxHeight, where
				// it keeps the reducedWidth and recalculate the height.
				const reducedWidth = imgMaxHeight * naturalRatio;
				if (imgMaxWidth - reducedWidth > imgMaxHeight) {
					imgMaxWidth = reducedWidth;
					imgMaxHeight = reducedWidth / naturalRatio;
				} else {
					imgMaxWidth = imgMaxHeight * naturalRatio;
				}
			}
			containerWidth = imgMaxWidth;
			containerHeight = imgMaxHeight;
			imgRatio = imgMaxWidth / imgMaxHeight;

			// Calculates the max size of the container.
			if (originalRatio > imgRatio) {
				containerMaxWidth = imgMaxWidth;
				containerMaxHeight = containerMaxWidth / originalRatio;
			} else {
				containerMaxHeight = imgMaxHeight;
				containerMaxWidth = containerMaxHeight * originalRatio;
			}
		}

		// If the image has been pixelated on purpose, it keeps that size.
		if (originalWidth > containerWidth || originalHeight > containerHeight) {
			containerWidth = originalWidth;
			containerHeight = originalHeight;
		}

		// WORDPRESS CORE CODE: Lines 307-332 from wp-includes/blocks/image/view.js
		// Calculates the final lightbox image size and the scale factor.
		// MaxWidth is either the window container (accounting for padding) or
		// the image resolution.
		let horizontalPadding = 0;
		if (window.innerWidth > 480) {
			horizontalPadding = 80;
		} else if (window.innerWidth > 1920) {
			horizontalPadding = 160;
		}
		const verticalPadding = 80;
		const targetMaxWidth = Math.min(window.innerWidth - horizontalPadding, containerWidth);
		const targetMaxHeight = Math.min(window.innerHeight - verticalPadding, containerHeight);
		const targetContainerRatio = targetMaxWidth / targetMaxHeight;
		if (originalRatio > targetContainerRatio) {
			// If targetMaxWidth is reached before targetMaxHeight.
			containerWidth = targetMaxWidth;
			containerHeight = containerWidth / originalRatio;
		} else {
			// If targetMaxHeight is reached before targetMaxWidth.
			containerHeight = targetMaxHeight;
			containerWidth = containerHeight * originalRatio;
		}
		const containerScale = originalWidth / containerWidth;
		const lightboxImgWidth = imgMaxWidth * (containerWidth / containerMaxWidth);
		const lightboxImgHeight = imgMaxHeight * (containerHeight / containerMaxHeight);

		// WORDPRESS CORE CODE: Lines 333-348 from wp-includes/blocks/image/view.js
		// As of this writing, using the calculations above will render the
		// lightbox with a small, erroneous whitespace on the left side of the
		// image in iOS Safari, perhaps due to an inconsistency in how browsers
		// handle absolute positioning and CSS transformation. In any case,
		// adding 1 pixel to the container width and height solves the problem,
		// though this can be removed if the issue is fixed in the future.
		
		// ADAPTATION: Direct CSS property setting instead of state.overlayStyles string
		overlay.style.setProperty('--wp--lightbox-initial-top-position', screenPosY + 'px');
		overlay.style.setProperty('--wp--lightbox-initial-left-position', screenPosX + 'px');
		overlay.style.setProperty('--wp--lightbox-container-width', containerWidth + 1 + 'px');
		overlay.style.setProperty('--wp--lightbox-container-height', containerHeight + 1 + 'px');
		overlay.style.setProperty('--wp--lightbox-image-width', lightboxImgWidth + 'px');
		overlay.style.setProperty('--wp--lightbox-image-height', lightboxImgHeight + 'px');
		overlay.style.setProperty('--wp--lightbox-scale', containerScale);
		overlay.style.setProperty('--wp--lightbox-scrollbar-width', window.innerWidth - document.documentElement.clientWidth + 'px');
		overlay.style.setProperty('--wp--lightbox-scrollbar-width', '0px');
	}

	/**
	 * Bind click handlers to dynamically loaded lightbox images
	 * 
	 * Replaces the WordPress core Interactivity API bindings (data-wp-on--click="actions.showLightbox")
	 * with manual event listeners. This is necessary because the Interactivity API doesn't 
	 * automatically bind to content inserted after page load.
	 * 
	 * Core WordPress Equivalent:
	 * - actions.showLightbox() in wp-includes/blocks/image/view.js:103-124
	 * - Uses state management and callbacks.setOverlayStyles()
	 */
	images.forEach( img => {

		img.addEventListener('click', () => {

			// find image inside overlay by .lightbox-image-container
			const lightboxImageContainer = overlay.querySelector('.lightbox-image-container');
			const lightboxImage = overlay.querySelector('.lightbox-image-container img');
			if ( lightboxImage ) {
				// Update overlay image with clicked image data
				lightboxImage.src = img.src;
				lightboxImage.alt = img.alt;
				lightboxImage.title = img.title;
				lightboxImage.style = img.style;

				// Apply WordPress core positioning/sizing calculations
				setOverlayStyles( img );
			}

			// Simple overlay activation (replaces core's state.overlayEnabled = true)
			overlay.classList.toggle('active');
		});
	} );

	/**
	 * Close lightbox on overlay click
	 * 
	 * Simplified version of WordPress core's actions.hideLightbox()
	 * Original includes scroll position restoration and focus management
	 */
	overlay.addEventListener('click', () => {
		overlay.classList.remove('active');
	});
}


/**
 * @since 2.7.0 Added lightbox re-initialization for live query compatibility
 * @see greyd.query.reInitCoreLightboxEvents() Full lightbox re-initialization implementation
 * @see live-filter.js:301-357 Where 'greyd-livequery-success' event is fired
 */
document.addEventListener("DOMContentLoaded", function() {
	const queriesWithLightboxImages = document.querySelectorAll('.wp-block-query:has(.greyd-posts-slider[live-query="true"] .wp-lightbox-container)');
	if ( queriesWithLightboxImages && queriesWithLightboxImages.length > 0 ) {
		queriesWithLightboxImages.forEach( wrapper => {
			wrapper.addEventListener('greyd-livequery-success', function() {
				reInitCoreLightboxEvents( wrapper );
			} );
		} );
	}
});