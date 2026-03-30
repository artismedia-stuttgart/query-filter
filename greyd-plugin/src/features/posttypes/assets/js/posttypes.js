/*
	Comments: Javascript File for Custom Posttypes
*/

(function() { 

	jQuery(function() {

		if (typeof $ === 'undefined') $ = jQuery;

		// redirect all post-new links to wizard
		jQuery(document).on('click', '[href*="post-new.php?post_type='+greyd.posttypes.posttype+'"]', greyd.posttypes.redirectToNew);
		jQuery(document).on('click', '.page-title-action.edit-core-posttype', greyd.posttypes.redirectToEdit);
		jQuery(document).on('click', '.page-title-action.create-new-txonomy', greyd.posttypes.redirectToTax);
		
		// fix "preview changes" button in cpt without editor support
		jQuery(document).on('click', '#post-preview.preview.button', (e) => {
			window.open(e.target.href, '_blank').focus();
		});

		console.log('Posttype Scripts: loaded');
	} );

} )(jQuery);

greyd.posttypes = new function() {

	this.posttype = "tp_posttypes";
	this.option = "posttype_settings";

	/**
	 * Posttype wizard functions
	 */
	this.initWizard = function() {

		if ($('#greyd-wizard.posttype_wizard').length > 0) {

			// change
			$('#greyd-wizard #name').on('input change', function() { greyd.posttypes.wizard.inputChanged(this); });
			$('#greyd-wizard #existing_posttype').on('change', function() { greyd.posttypes.wizard.inputChanged(this); });
			$('#greyd-wizard #tax-name').on('input change', function() { greyd.posttypes.wizard.inputChanged(this); });
			// submit
			$('#greyd-wizard .button[data-show]').on('click', function() { greyd.posttypes.wizard.submit(this) });

			// swich modes
			$('#greyd-wizard [data-trigger]').each(function() {
				$(this).on('click', function() { 
					greyd.posttypes.wizard.open($(this).data('trigger')); 
				});
			});
			$('#greyd-wizard .edit_existing_editable').on('click', function() { 
				greyd.posttypes.wizard.open('edit-posttype'); 
			});

			// instant open
			if (location.search == '?post_type='+greyd.posttypes.posttype+'&wizard=add') greyd.posttypes.wizard.open('new-posttype');
			if (location.search == '?post_type='+greyd.posttypes.posttype+'&wizard=edit') greyd.posttypes.wizard.open('edit-posttype');
			if (location.search == '?post_type='+greyd.posttypes.posttype+'&wizard=tax') greyd.posttypes.wizard.open('new-taxonomy');
		}
	}

	// open wizard
	this.redirectToNew = function(e) {
		greyd.posttypes.redirect(e, 'new-posttype', 'add');
	}
	this.redirectToEdit = function(e) {
		greyd.posttypes.redirect(e, 'edit-posttype', 'edit');
	}
	this.redirectToTax = function(e) {
		greyd.posttypes.redirect(e, 'new-taxonomy', 'tax');
	}
	this.redirect = function(e, mode, param) {
		e.preventDefault();
		var url = window.location.href.split('wp-admin');
		if (url[1].indexOf("/edit.php?post_type="+greyd.posttypes.posttype) > -1) greyd.posttypes.wizard.open(mode);
		else window.location.href = url[0]+"wp-admin/edit.php?post_type="+greyd.posttypes.posttype+"&wizard="+param;

	}

	this.wizard = new function() {

		// 
		this.open = function(mode) {
			switch (mode) {
				case 'edit-posttype': this.selectMode('edit-posttype', '#existing_posttype'); break;
				case 'new-taxonomy': this.selectMode('new-taxonomy', '#tax-name'); break;
				default: this.selectMode('new-posttype', '#name'); break;
			}
		}

		// 
		this.selectMode = function(mode, id) {
			greyd.wizard.initnew();
			greyd.wizard.setState(1);
			$('#greyd-wizard .button[data-show]').addClass('disabled');
			$('#greyd-wizard [data-show]').css('display', 'none');
			$('#greyd-wizard [data-show='+mode+']').css('display', 'block');
			$('#greyd-wizard [data-trigger]').removeClass('active');
			$('#greyd-wizard [data-trigger='+mode+']').addClass('active');
			this.inputChanged($(id));
		}

		// called whenever input is changed
		this.inputChanged = function(el) {
			var mode = $(el).closest('[data-show]').data('show');
			var value = $(el).val();
			$('#greyd-wizard .button[data-show='+mode+']').removeClass('disabled');
			$('#greyd-wizard [data-warning]').css('display', 'none');
			if ($(el)[0].nodeName == "INPUT") {
				// input posttype or tax
				this.checkExisting(value, pages, 'clashing_page', mode, false);
				this.checkExisting(value, editable_post_types, 'existing_editable_post', mode);
				this.checkExisting(value, post_types, 'name_double', mode);
			}
			else {
				// select core posttype
				this.checkExisting(value, post_types, 'existing_posttype', mode);
			}
			if (value.replace(/\s/g, "") == "") {
				$('#greyd-wizard .button[data-show='+mode+']').addClass('disabled');
			}
		}

		// 
		this.checkExisting = function(value, posts ,slug, btn, disable=true) {
			// console.log("new slug: "+$('#greyd-wizard #slug').val());
			// console.log("new name: "+$('#greyd-wizard #name').val());
			var index = -1;
			if (Array.isArray(posts) && posts.length > 0) {
				var val1 = value.toLowerCase();
				var val2 = val1.replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
				for (var i=0; i<posts.length; i++) {
					if (
						posts[i].slug == val1 || 
						posts[i].slug == val2 || 
						posts[i].title.toLowerCase() == val1 ||
						posts[i].title.toLowerCase() == val2
					) {
						index = i;
						break;
					}
				}
			}
			$('#greyd-wizard [data-warning='+slug+']').css('display', 'none');
			if (index > -1) {
				// console.log("post exists!");
				var url = location.href.split("wp-admin");
				var edit = url[0]+"wp-admin/post.php?post="+posts[index].id+"&action=edit";
				$('#greyd-wizard .double_edit').attr('href', edit);
				$('#greyd-wizard .double_name').text(posts[index].title);
				$('#greyd-wizard [data-warning]').css('display', 'none');
				$('#greyd-wizard [data-warning='+slug+']').css('display', 'block');
				if (disable) $('#greyd-wizard .button[data-show='+btn+']').addClass('disabled');
			}
		}

		// 
		this.submit = function(el) {
			//console.log(el);
			if ($(el).hasClass('disabled')) return;
			
			var mode = $(el).data('show');
			var input = '#'+$('[data-trigger='+mode+']').data('id');
			var value = $(input).val();
			if (value.replace(/\s/g, "") == "") {
				alert(theme_wordings.wizard.no_name_posttype);
				return;
			}

			$(el).addClass('disabled');

			var result = {
				mode: mode,
				name: value,
				slug: "",
			};
			// console.log(result);
			
			jQuery.post(
				greyd.ajax_url, {
					'action': 'greyd_admin_ajax',
					'_ajax_nonce': greyd.nonce,
					'mode': 'create_posttype',
					'data': result
				}, 
				function(response) {
					if (response.indexOf('error::') > -1) {
						console.warn(response);
						var tmp = response.split('error::');

						$('#greyd-wizard .error_msg').html(tmp[1]);
						greyd.wizard.setState('0-error');
					}
					else {
						// console.info(response);
						var mode = 'success';
						if (response.indexOf('info::') > -1) mode = 'info';
						var tmp = response.split(mode+'::');
						var msg = tmp[1];
						var url = location.href.split("wp-admin");
						var post_id = msg.split('(id ');
						post_id = post_id[1].split(')');
						var edit = url[0]+"wp-admin/post.php?post="+post_id[0]+"&action=edit";

						$('#greyd-wizard .finish_wizard').attr('href', edit);
						$('#greyd-wizard .wizard_box').removeClass('big');
						greyd.wizard.setState(11);
					}
					$(el).removeClass('disabled');
		
				}
			);
		}

	}

	/**
	 * Edit single Posttype functions
	 */

	// Init Posttype functions
	this.init = function() {

		// disable Title if it is an existing editable post type
		this.disableTitle();
		// init iconpicker
		this.initIconpicker();
		// autofill fields on creation
		this.autofillTitle();
		// addTriggers
		this.addTriggers();
		// drag & drop
		this.initDragNDrop();
		// conditions
		this.initConditions();

		greyd.dynamic_posts.filePicker();
	}


	this.disableTitle = function() {
		if ($("body").hasClass("edited_posttype")) {
			$("#titlewrap").addClass("disabled");
		}
	}

	// when title is filled out for the first time --> autofill name & label
	this.autofillTitle = function() {
		var plural = $('input[name="'+this.option+'[plural]"]');
		var singular = $('input[name="'+this.option+'[singular]"]');

		if (!singular.val() && !plural.val() && !$('input[name="post_title"]').val()) {
			$('input[name="post_title"]').on('keyup', function() {
				var value = $(this).val();
				plural.val(value);
				singular.val(value);
			});
		} else if (!singular.val() && !plural.val()) {
			singular.val($('input[name="post_title"]').val());
			plural.val($('input[name="post_title"]').val() + "s");
		}
	}

	this.initDragNDrop = function() {
		var wrapper = $('#greyd_sortable');
		if (wrapper.length === 0) return false;

		$(wrapper).sortable({
			update: function(e) {
				greyd.posttypes.updateRowNumbers();
			},
			placeholder: "sortable_placeholder",
			handle: ".sortable_handle",
			cursor: "grabbing"
		});
	}

	this.initIconpicker = function() {
		if ( $('.iconselect').length > 0 ) {
			$('.iconselect').fontIconPicker({
				hasSearch: true,
				iconsPerPage: 400,
				theme: 'fip-grey',
				source: false,
				iconGenerator: function( icon ) {
					return '<i class="dashicons-before dashicons-' + icon +'"></i>';
				}
			});
		}
	}

	this.addTriggers = function() {
		this.removeTriggers();

		// open sub row
		$(".edit_field").on("click", function() {
			greyd.posttypes.toggleRow($(this).closest(".row_container"));
		});

		// delete row
		$('.delete_field').on("click", function() {
			greyd.posttypes.deleteRow($(this));
		});

		// duplicate row
		$('.duplicate_field').on("click", function() {
			greyd.posttypes.duplicateRow($(this));
		});

		// add row
		$('.add_field').on("click", function() {
			greyd.posttypes.addRow();
		});

		// bind label to keyup
		$('input[name$="[label]"]').on('keyup', function() {
			var label = $(this).closest('.row_container').find('.dyn_field_label');
			var val = $(this).val();
			label.html(val);
		});

		// bind type to select change
		$('select[name$="[type]"]').on('change', function() {
			var val = $(this).val();
			var option = $(this).find('option[value="'+val+'"]');
			var nice_val = option.text();
			var elem = $(this).closest('.row_container').find('.dyn_field_type');
			elem.text(nice_val);

			// manually set the selected attribute (needed for clean duplication)
			var slct = $(this).find('option[selected]');
			if ( slct.length > 0 ) slct[0].removeAttribute('selected');
			option[0].setAttribute('selected', '');

			// if type doesn't need a label (hr, empty)
			var label = $(this).closest('.row_container').find('.dyn_field_label');
			if ( val === 'hr' || val === 'space' ) {
				label.html(nice_val);
			} else {
				var label_val = $(this).closest('.row_container').find('input[name$="[label]"]').val();
				label.html(label_val);
			}
		});

		// bind required to checkbox change
		$('input[name$="[required]"]').each(function(){
			$(this).on('change', function() {
				greyd.posttypes.toggleRequired( $(this) );
			});
			greyd.posttypes.toggleRequired( $(this) );
		});


		// bind name to keyup
		$('input[name$="[name]"]').on('keyup', function() {
			// setting vars
			var input                   = $(this);
			var val                     = input.val();
			var old_val                 = input.data("val");
			var blacklisted_fields      = input.data("blacklisted-fields").split(",");
			var is_blacklisted          = blacklisted_fields.includes(val.toLowerCase());
			var alert_change_name       = input.next('.change_name_alert');
			var alert_blacklisted_name  = input.nextAll('.blacklisted_name_alert');

			if (alert_change_name.length !== 0) {
				if (val !== old_val)
					alert_change_name.addClass("open");
				else
					alert_change_name.removeClass("open");
			}

			if (is_blacklisted){
				alert_change_name.removeClass("open");
				alert_blacklisted_name.addClass("open");
			}
			else {
				alert_blacklisted_name.removeClass("open");
			}
		});

		// bind name to keyup
		$('input[name$="[singular]"]').on('keyup', function() {
			var input = $(this);
			var val = input.val();

			var blacklisted_fields = input.data("blacklisted-names").split(",");
			var is_blacklisted = blacklisted_fields.includes(val.toLowerCase());
			var alert_blacklisted_name = input.nextAll('.blacklisted_name_alert');

			if (is_blacklisted)
				alert_blacklisted_name.addClass("open");
			else
				alert_blacklisted_name.removeClass("open");
		});


		$('input[name$="[name]"]').on('blur', function() {
			var alert = $(this).next('.change_name_alert');
			if (alert.length !== 0)
				alert.removeClass("open");
		});

		// trigger conditions
		$('.trigger_cond').on("change", function() {
			greyd.posttypes.triggerCondition($(this));
		});
	}

	this.toggleRow = function(row) {
		var sub = row.find('.sub');
		var sh = sub.height();
		var th = sub.find('table').outerHeight();
		var height = sh <= 0 ? th : 0;
		// console.log(height);

		greyd.posttypes.updateHeight(sub, height);
		row.toggleClass("open");
	}

	this.closeRow = function(row) {
		var sub = row.find('.sub');
		greyd.posttypes.updateHeight(sub, 0);
		row.removeClass("open");
	}

	this.removeTriggers = function() {
		$('.edit_field, .delete_field, .duplicate_field, .add_field, input[name$="[label]"], select[name$="[type]"], input[name$="[required]"], input[name$="[name]"], .trigger_cond').off();
	}

	this.deleteRow = function(button) {
		var row = button.closest(".row_container");
		var name = row.find(".dyn_field_label").text();
		var confirmed = confirm(`Are you sure you want to delete the field \"${name}\"?`);
		if (confirmed) {
			row.remove();
			greyd.posttypes.updateRowNumbers();
		}
	}

	this.duplicateRow = function(button) {
		var row = button.closest(".row_container");
		var clone = row.clone();

		// modify
		greyd.posttypes.updateHeight(clone.find('.sub'), 0);
		clone.removeClass("open");
		clone.find(".change_name_alert").remove();
		greyd.posttypes.updateNames(clone);

		// insert
		row.after(clone);

		greyd.posttypes.addTriggers();
		greyd.posttypes.updateRowNumbers();
	}

	this.addRow = function() {
		var into = $("#greyd_sortable");
		var row = $(".row_container.hidden");
		var open_row = $(".row_container.open");
		var clone = row.clone();
		var new_row = into.append(clone);

		greyd.posttypes.toggleRow(open_row);
		row.removeClass('hidden');
		greyd.posttypes.toggleRow(row);

		greyd.posttypes.addTriggers();
		greyd.posttypes.updateRowNumbers();
	}

	this.updateRowNumbers = function() {
		setTimeout(function() {
			var rows = $('.fields_table').find('.sub');
			var i = 0;
			//console.log(rows);

			rows.each(function() {
				var n = $(this).attr('data-num');
				var inputs = $(this).find('*[name*="[fields]['+n+']"]');
				// console.log(n+" --> "+i);

				inputs.each(function() {
					var name = $(this).attr('name');
					$(this).attr('name', name.replace(/(\[fields\]\[)(.*)(\]\[)/g, '[fields]['+i+']['));
					//console.log($(this).attr('name'));
				});
				$(this).attr('data-num', i);
				i++;
			});
		}, 100);
	}

	this.updateNames = function(row) {
		var label = row.find('input[name*="[label]"]');
		var label_val = label.val().length > 0 ? "_"+label.val() : '';
		var name = row.find('input[name*="[name]"]');
		var name_val = name.val().length > 0 ? "_"+name.val() : '';
		var text = row.find('.dyn_field_label');
		var text_val = label_val;
		var type = row.find('select[name*="[type]"]');
		var type_val = type.val();

		// console.log(type);
		// console.log(type_val);

		if (type_val === 'hr' || type_val === 'space') {
			text_val = type.find('option[value="'+type_val+'"]').text();
		}

		label.val(label_val);
		text.text(text_val);
		name.val(name_val);
		type.val(type_val);
	}

	this.initConditions = function() {
		var $hide = $('*[data-hide]');
		var $show = $('*[data-show]');

		$hide.each(function() {
			greyd.posttypes.toggleCondition($(this), "hide");
		});
		$show.each(function() {
			greyd.posttypes.toggleCondition($(this), "show");
		});
	}

	this.triggerCondition = function(input) {
		var name = input.attr("name").split("[");
		var field = name[name.length-1].replace("]", "");
		var row = input.closest(".row_container");
		var $hide = row.find('*[data-hide^="'+field+'"]');
		var $show = row.find('*[data-show^="'+field+'"]');
		//console.log($hide);
		//console.log($show);

		$hide.each(function() {
			greyd.posttypes.toggleCondition($(this), "hide");
		});
		$show.each(function() {
			greyd.posttypes.toggleCondition($(this), "show");
		});
		greyd.posttypes.updateHeight(row.find(".sub"));
	}

	this.toggleCondition = function(elem, type="show") {
		var row = elem.closest(".row_container");
		var data = elem.data(type);

		if (typeof data === "undefined") return false;

		var data = data.split("=");
		var field = row.find('*[name$="['+data[0]+']"]');
		var val = field.val();
		var values = data[1].split(",");
		var show = type === "hide" ? true : false;

		if (values.includes(val)) 
			show = !show;
		if (show)
			elem.removeClass("hidden");
		else
			elem.addClass("hidden");
	}

	this.updateHeight = function(sub, height) {
		var height = (typeof height === "undefined") ? sub.find('table').height() : height;
		sub.css({"height": height});
	}

	this.toggleRequired = function(checkbox) {
		var label = checkbox.closest('.row_container').find('.field_label');
		if (checkbox.is(':checked'))
			label.addClass("required");
		else
			label.removeClass("required");
	}

	/*
		Custom taxonomies
	*/
	this.deleteTaxonomy = function(elem) {
		$(elem).closest("tr").remove();
	}

	this.addTaxonomy = function(elem) {
		var table = $(elem).closest(".taxonomy_table");
		table.find("tbody > *:last-child").clone().appendTo(table.find("tbody"));
		table.find("tbody > *:nth-last-child(2)").removeClass("hidden");

		// $(".taxonomy_table tbody > *:last-child").clone().appendTo(".taxonomy_table tbody");
		// $(".taxonomy_table tbody > *:nth-last-child(2)").removeClass("hidden");
	}

}

greyd.dynamic_posts = new function() {

	this.init = function() {
		this.filePicker();
		this.linkPicker();
		// logging
		// console.log('Greyd Dynamic Posts Modul: loaded');
		$("body").addClass("greyd_dynamic_posttype");
	}

	/**
	 *  WP Custom File Picker
	 *
	 *  used within dynamic posts
	*/
	this.filePicker = function() {
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
	 *  WP Custom Link Picker
	 *  used within dynamic posts
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
