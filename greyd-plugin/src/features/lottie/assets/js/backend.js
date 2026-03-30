/**
 * Backend Tools for Lottie Animation Block.
 * Makes Animation Preview in Editor and Inspector.
 * todo: media (still in theme)
 *
 * This file is loaded in wp backend only.
 */
(function() {
	
	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		greyd.lottie.initElements();

		// console.log("Backend Lottie Script: loaded");
	} );

} )(jQuery);


greyd.lottie = new function() {

	var _ = lodash;

	/**
	 * lottie animations
	 */
	this.anims = {};
	this.initElements = function() {
		// Thumbnails on Media upload page (still in theme)
		$(document).on('ajaxComplete ajaxCompleteJSON', this.get);
		// Fullsize view (still in theme)
		$(document).on('click', '.attachment-preview.type-application.subtype-json', this.getFullsize);
		// Media attachment page
		if ($('.wp_attachment_holder img').length && $('#attachment_url').length) {
			var url = $('#attachment_url')[0].value;
			console.log(url);
			if (url && url.endsWith('.json')) {
				var id = Math.random().toString(36).substr(2, 16);
				var style = "display:inline-block; width:auto;";
				var html = "<div class='lottie-animation' style='"+style+"' id='"+id+"' data-src='"+url+"' data-anim='auto'></div>";
				$('.wp_attachment_holder p').html(html);
				this.initAnimations();
			}
		}
		// todo: Media list-view
	}


	/**
	 * Get Elements and inject lottie previews.
	 */
	this.get = function() {
		// console.log("media loaded");
		
		/**
		 * Theme aready injects the lottie previews before this is called.
		 * Once deactivated (or other theme) this function will be used.
		 */
		if ( !$('.attachment-preview.type-application.subtype-json .thumbnail .centered').length ) return;

		$('.attachment-preview.type-application.subtype-json .thumbnail .centered').each(function() {
			var id = $(this).closest('li.attachment')?.data('id');
			var url = id && greyd.data?.media_urls?.[id] ? greyd.data.media_urls[id].src : false;
			if ( !url ) {
				var tmp = wp.media?.attachment(id);
				if ( tmp?.attributes?.url ) url = tmp.attributes.url;
			}
			if (url) {
				// console.log(url);
				var id = Math.random().toString(36).substr(2, 16);
				var style = "display:inline-block; width:auto;";
				var html = "<div class='lottie-animation' style='"+style+"' id='"+id+"' data-src='"+url+"' data-anim='auto'></div>";
				$(this).html(html);
				$(this).removeClass('centered');
			}
		});
		greyd.lottie.initAnimations();
	}
	this.getFullsize = function() {
		// console.log("show fullsize");
		
		/**
		 * Theme aready injects the lottie previews before this is called.
		 * Once deactivated (or other theme) this function will be used.
		 */
		if ( !$('.edit-attachment-frame .thumbnail.thumbnail-application img').length ) return;

		$('.edit-attachment-frame .thumbnail.thumbnail-application').each(function() {
			var url = $('#attachment-details-two-column-copy-link').val();
			if (url) {
				// console.log(url);
				var id = Math.random().toString(36).substr(2, 16);
				var html = "<div class='lottie-animation details-image' id='"+id+"' data-src='"+url+"' data-anim='auto'></div>";
				$(this).html(html);
			}
		});
		greyd.lottie.initAnimations();
	}


	/**
	 * Init Animations.
	 * Should be called when Animations are created.
	 */
	this.initTimeout = undefined;
	this.initIds = [];
	this.init = function(ids) {
		this.initIds = [ ...this.initIds, ...ids ];
		if (typeof this.initTimeout === 'undefined') {
			this.initTimeout = setTimeout(function() {
				greyd.lottie.initIds.forEach(function(i) {
					if (_.has(greyd.lottie.anims, i)) {
						// console.log(greyd.lottie.anims[i]);
						if (!greyd.lottie.anims[i].wrapper.isConnected) {
							greyd.lottie.anims[i].renderer.destroy();
							delete greyd.lottie.anims[i];
						}
					}
				});
				greyd.lottie.initAnimations();
				greyd.lottie.initTimeout = undefined;
				greyd.lottie.initIds = [];
			}, 0);
		}
	}


	/**
	 * Set Animations with new url.
	 * Should be called when Animations are updated.
	 */
	this.setTimeout = undefined;
	this.setIds = [];
	this.set = function(ids, newurl) {
		this.setIds = [ ...this.setIds, { ids: ids, url: newurl } ];
		if (typeof this.setTimeout === 'undefined') {
			this.setTimeout = setTimeout(function() {
				greyd.lottie.setIds.forEach(function(anim) {
					anim.ids.forEach(function(i) {
						if (_.has(greyd.lottie.anims, i)) {
							// console.log(greyd.lottie.anims[i]);
							if (greyd.lottie.anims[i].wrapper.dataset.src != anim.url)
								greyd.lottie.anims[i].wrapper.dataset.src = anim.url;
							greyd.lottie.anims[i].renderer.destroy();
							delete greyd.lottie.anims[i];
						}
					});
				});
				greyd.lottie.initAnimations();
				greyd.lottie.setTimeout = undefined;
				greyd.lottie.setIds = [];
			}, 0);
		}
	}


	/**
	 * Load and start Animations.
	 * Called from greyd.lottie.init or greyd.lottie.set
	 */
	this.initAnimations = function() {

		// get json icons from document
		document.querySelectorAll('.lottie-animation').forEach( function(element) {
			// console.log(element);
			greyd.lottie.initAnimation(element);
		} );

		// get icons from editor iframe (Tablet and Mobile preview)
		var iframe = document.getElementsByName('editor-canvas');
		if (iframe.length > 0) {
			var innerDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
			if (innerDoc) innerDoc.querySelectorAll('.lottie-animation').forEach( function(element) {
				// console.log(element);
				greyd.lottie.initAnimation(element);
			} );
		}

	}
	this.initAnimation = function(element) {
		var id = element.id;
		var src = element.dataset.src;
		if (id != "" && src != "") {
			var anim = element.dataset.anim;
			var auto = false;
			if (anim == "auto" || anim == "visible") auto = true;
			greyd.lottie.addAnimation(element, src, auto);
		}
	}
	this.addAnimation = function(element, src, autoplay) {
		if (autoplay == undefined) autoplay = false;
		var id = element.id;
		// console.log(id+', '+src+', '+autoplay);
		if (this.anims[id] && !element.dataset.loaded ) {
			this.anims[id].renderer.destroy();
			delete this.anims[id];
		}
		if (!this.anims[id]) {
			element.dataset.loaded = true;
			this.anims[id] = bodymovin.loadAnimation( {
									container: element,
									renderer: 'svg',
									loop: true,
									autoplay: autoplay,
									path: src
								} );
			if ( autoplay === false && typeof LottieInteractivity !== 'undefined' ) {
				// add interactivity
				this.anims[id].addEventListener('DOMLoaded', function(e) { 
					var { mode, actions } = greyd.lottie.getInteractivity( element.dataset.anim, greyd.lottie.anims[id] );
					if ( mode && actions.length > 0 ) {
						greyd.lottie.anims[id].interactivity = LottieInteractivity.create({
							player: greyd.lottie.anims[id],
							mode: mode,
							actions: actions
						});
					}
				} );
			}
		}
		this.anims[id].setSubframe(false);
	}

	this.getInteractivity = function(type, anim) {
		
		var mode = false;
		var actions = [];
		switch ( type ) {
			case 'hover':
				mode = 'cursor';
				actions = [
					{
						type: "pauseHold",
					}
				];
				break;
			case 'toggle':
				mode = 'cursor';
				actions = [
					{
						type: "toggle",
					}
				];
				break;
			case 'click':
				mode = 'cursor';
				actions = [
					{
						type: "click",
						forceFlag: true,
					}
				];
				break;
			case 'seek_scroll':
				mode = 'scroll';
				actions = [
					{
						visibility: [0, 1],
						type: 'seek',
						frames: [0, anim.totalFrames],
					},
				];
				break;
			case 'seek_cursor':
				mode = 'cursor';
				actions = [
					{
						position: { x: [0, 1], y: [0, 1] },
						type: 'seek',
						frames: [0, anim.totalFrames],
					}
				];
				break;
			case 'seek_cursor_horizontal':
				mode = 'cursor';
				actions = [
					{
						position: { x: [0, 1], y: [-1, 2] },
						type: 'seek',
						frames: [0, anim.totalFrames],
					}
				];
				break;
		}

		return {
			mode: mode,
			actions: actions
		}
	}

	// console.log( "additional lottie tools: loaded" );

};
