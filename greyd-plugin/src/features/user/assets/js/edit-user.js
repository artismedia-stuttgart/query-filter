/**
 * User edit page script.
 * 
 * This file includes a custom file picker and a custom link picker into user edit
 * pages to enable metafields with those types.
 * 
 * @since 1.7.0
 */
var greyd = greyd || {};

(function() {
	jQuery(function() {
		if (typeof $ === 'undefined') $ = jQuery;
		greyd.editUser.init();

		console.log('user-edit.js');
	} );
} )(jQuery);

greyd.editUser = new function() {

	this.init = function() {

		console.log('init');

		this.filePicker();
		this.linkPicker();
	}

	/**
	 * WP Custom File Picker
	 */
	this.filePicker = function() {

		console.log('filePicker');

		var frame;

		$('.custom_filepicker .button').on('click', function(e) {
			e.preventDefault();

			var input   = $(this).siblings('input');
			var preview = $(this).siblings('img');
			var src     = $(this).siblings('.image_url');
			var remove  = $(this).siblings('.button.remove');

			// add image
			if ($(this).hasClass('add')) {
				// Create the media frame.
				frame = wp.media.frames.file_frame = wp.media({
					multiple: false	// Set to true to allow multiple files to be selected
				});

				// When an image is selected, run a callback.
				frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = frame.state().get('selection').first().toJSON();
					// Do something with attachment.id and/or attachment.url here
					var thumb = attachment.icon;
					if (typeof attachment.sizes !== 'undefined') {
						if (typeof attachment.sizes.medium !== 'undefined') {
							thumb = attachment.sizes.medium.url;
						}
						else if (attachment.sizes.full.url.indexOf('.json') === -1) {
							thumb = attachment.sizes.full.url;
						}
					}
					preview.attr( 'src', thumb );
					src.html( attachment.url );
					input.val( attachment.id );
					remove.removeClass('hidden');
				});

				// open the file modal
				frame.open();
			}
			else if ($(this).hasClass('remove')) {
				preview.attr( 'src', '' );
				src.html( '' );
				input.val( '' );
				$(this).addClass('hidden');
			}
		});
	}

	/**
	 * WP Custom Link Picker
	 */
	this.linkPicker = function() {

		// open the popup
		$('.custom_wp_link .button.add').on("click", function() {
			var button  = $(this);
			var input   = button.siblings('input');
			var val     = input.val();
			var id      = input.attr('id');

			// open the link modal
			wpLink.open(id, val); // wpLink.open( $editor_id, url, text)

			// modify link modal
			$("#wp-link-wrap").removeClass("has-text-field");
			$("#wp-link-wrap .link-target").hide();
		});

		$(document).on( 'wplink-close', function( wrap ) {
			$('#wp-link-wrap .link-target').show();
		});

		// append the url
		$('.custom_wp_link input').on("change", function() {
			var input   = $(this);
			var text    = input.siblings("b");
			var val     = input.val();
			if ( val.length === 0 || val.indexOf("\"") === -1 ) return false;

			val = val.split("\"")[1];
			input.val(val);
			text.html(val);
			input.siblings('.button.remove').removeClass('hidden');
		});

		// remove the url
		$('.custom_wp_link .button.remove').on("click", function() {
			var button  = $(this);
			var input   = button.siblings('input');
			var text    = button.siblings("b");
			input.val('');
			text.html('');
			button.addClass('hidden');
		});
	}

}
