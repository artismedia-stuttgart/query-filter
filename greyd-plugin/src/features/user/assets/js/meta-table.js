/**
 * Backend metafields table script.
 * 
 * @since 1.5.4
 */
( function () {

	jQuery( function () {

		if ( typeof $ === 'undefined' ) $ = jQuery;

		greyd.metaTable.init();

		console.log( 'Metafields Table Scripts: loaded' );
	} );

} )( jQuery );

greyd.metaTable = new function () {

	/**
	 * Init Metafields functions
	 */
	this.dirty = false;
	this.init = function () {

		if ( $( '#greyd_sortable' ).length === 0 ) return false;

		// addTriggers
		this.addTriggers();
		// drag & drop
		this.initDragNDrop();
		// conditions
		this.initConditions();

	};

	/**
	 * Events
	 */
	this.addTriggers = function () {
		this.removeTriggers();

		// open sub row
		$( ".fields_table .edit_field" ).on( "click", function () {
			greyd.metaTable.toggleRow( $( this ).closest( ".row_container" ) );
		} );

		// delete row
		$( '.fields_table .delete_field' ).on( "click", function () {
			greyd.metaTable.deleteRow( $( this ) );
			greyd.metaTable.dirty = true;
		} );

		// duplicate row
		$( '.fields_table .duplicate_field' ).on( "click", function () {
			greyd.metaTable.duplicateRow( $( this ) );
			greyd.metaTable.dirty = true;
		} );

		// add row
		$( '.fields_table .add_field' ).on( "click", function () {
			greyd.metaTable.addRow();
			greyd.metaTable.dirty = true;
		} );

		// bind label to keyup
		$( '.fields_table input[name$="[label]"]' ).on( 'keyup', function () {
			var label = $( this ).closest( '.row_container' ).find( '.dyn_field_label' );
			var val = $( this ).val();
			label.html( val );
			greyd.metaTable.dirty = true;
		} );

		// bind name to keyup
		$( '.fields_table input[name$="[name]"]' ).on( 'keyup', function () {
			// setting vars
			var input = $( this );
			var val = input.val();
			var old_val = input.data( "val" );
			var blacklisted_fields = input.data( "blacklisted-fields" ).split( "," );
			var is_blacklisted = blacklisted_fields.includes( val.toLowerCase() );
			var alert_change_name = input.next( '.change_name_alert' );
			var alert_blacklisted_name = input.nextAll( '.blacklisted_name_alert' );

			if ( alert_change_name.length !== 0 ) {
				if ( val !== old_val )
					alert_change_name.addClass( "open" );
				else
					alert_change_name.removeClass( "open" );
			}

			if ( is_blacklisted ) {
				alert_change_name.removeClass( "open" );
				alert_blacklisted_name.addClass( "open" );
			}
			else {
				alert_blacklisted_name.removeClass( "open" );
			}
			greyd.metaTable.dirty = true;
		} );

		$( '.fields_table input[name$="[name]"]' ).on( 'blur', function () {
			var alert = $( this ).next( '.change_name_alert' );
			if ( alert.length !== 0 ) {
				alert.removeClass( "open" );
			}
		} );

		// bind type to select change
		$( '.fields_table select[name$="[type]"]' ).on( 'change', function () {
			var val = $( this ).val();
			var option = $( this ).find( 'option[value="' + val + '"]' );
			var nice_val = option.text();
			var elem = $( this ).closest( '.row_container' ).find( '.dyn_field_type' );
			elem.text( nice_val );

			// manually set the selected attribute (needed for clean duplication)
			var slct = $( this ).find( 'option[selected]' );
			if ( slct.length > 0 ) slct[ 0 ].removeAttribute( 'selected' );
			option[ 0 ].setAttribute( 'selected', '' );

			// if type doesn't need a label (hr, empty)
			var label = $( this ).closest( '.row_container' ).find( '.dyn_field_label' );
			if ( val === 'hr' || val === 'space' ) {
				label.html( nice_val );
			}
			else {
				var label_val = $( this ).closest( '.row_container' ).find( 'input[name$="[label]"]' ).val();
				label.html( label_val );
			}
			greyd.metaTable.dirty = true;
		} );

		// bind required to checkbox change
		$( '.fields_table input[name$="[required]"]' ).each( function () {
			$( this ).on( 'change', function () {
				greyd.metaTable.toggleRequired( $( this ) );
				greyd.metaTable.dirty = true;
			} );
			greyd.metaTable.toggleRequired( $( this ) );
		} );

		// trigger conditions
		$( '.fields_table .trigger_cond' ).on( "change", function () {
			greyd.metaTable.triggerCondition( $( this ) );
		} );
	};
	this.removeTriggers = function () {
		$( '.edit_field, .delete_field, .duplicate_field, .add_field, input[name$="[label]"], select[name$="[type]"], input[name$="[required]"], input[name$="[name]"], .trigger_cond' ).off();
	};

	/**
	 * actions
	 */
	this.toggleRow = function ( row ) {
		var sub = row.find( '.sub' );
		var sh = sub.height();
		var th = sub.find( 'table' ).outerHeight();
		var height = sh <= 0 ? th : 0;
		// console.log(height);

		greyd.metaTable.updateHeight( sub, height );
		row.toggleClass( "open" );
	};
	this.deleteRow = function ( button ) {
		var row = button.closest( ".row_container" );
		var name = row.find( ".dyn_field_label" ).text();
		var confirmed = confirm( `Are you sure you want to delete the field "${ name }"?` );
		if ( confirmed ) {
			row.remove();
			greyd.metaTable.updateRowNumbers();
		}
	};
	this.duplicateRow = function ( button ) {
		var row = button.closest( ".row_container" );
		var clone = row.clone();

		// modify
		greyd.metaTable.updateHeight( clone.find( '.sub' ), 0 );
		clone.removeClass( "open" );
		clone.find( ".change_name_alert" ).remove();
		greyd.metaTable.updateNames( clone );

		// insert
		row.after( clone );

		greyd.metaTable.addTriggers();
		greyd.metaTable.updateRowNumbers();
	};
	this.addRow = function () {
		var into = $( "#greyd_sortable" );
		var row = $( ".row_container.hidden" );
		var open_row = $( ".row_container.open" );
		var clone = row.clone();
		var new_row = into.append( clone );

		greyd.metaTable.toggleRow( open_row );
		row.removeClass( 'hidden' );
		greyd.metaTable.toggleRow( row );

		greyd.metaTable.addTriggers();
		greyd.metaTable.updateRowNumbers();
	};

	/**
	 * Drag'n'Drop
	 */
	this.initDragNDrop = function () {
		var wrapper = $( '#greyd_sortable' );
		if ( wrapper.length === 0 ) return false;

		$( wrapper ).sortable( {
			update: function ( e ) {
				greyd.metaTable.updateRowNumbers();
				greyd.metaTable.dirty = true;
			},
			placeholder: "sortable_placeholder",
			handle: ".sortable_handle",
			cursor: "grabbing"
		} );
	};

	/**
	 * Conditions
	 */
	this.initConditions = function () {
		var $hide = $( '*[data-hide]' );
		var $show = $( '*[data-show]' );

		$hide.each( function () {
			greyd.metaTable.toggleCondition( $( this ), "hide" );
		} );
		$show.each( function () {
			greyd.metaTable.toggleCondition( $( this ), "show" );
		} );
	};
	this.triggerCondition = function ( input ) {
		var name = input.attr( "name" ).split( "[" );
		var field = name[ name.length - 1 ].replace( "]", "" );
		var row = input.closest( ".row_container" );
		var $hide = row.find( '*[data-hide^="' + field + '"]' );
		var $show = row.find( '*[data-show^="' + field + '"]' );
		//console.log($hide);
		//console.log($show);

		$hide.each( function () {
			greyd.metaTable.toggleCondition( $( this ), "hide" );
		} );
		$show.each( function () {
			greyd.metaTable.toggleCondition( $( this ), "show" );
		} );
		greyd.metaTable.updateHeight( row.find( ".sub" ) );
	};
	this.toggleCondition = function ( elem, type = "show" ) {
		var row = elem.closest( ".row_container" );
		var data = elem.data( type );

		if ( typeof data === "undefined" ) return false;

		var data = data.split( "=" );
		var field = row.find( '*[name$="[' + data[ 0 ] + ']"]' );
		var val = field.val();
		var values = data[ 1 ].split( "," );
		var show = type === "hide" ? true : false;

		if ( values.includes( val ) )
			show = !show;
		if ( show )
			elem.removeClass( "hidden" );
		else
			elem.addClass( "hidden" );
	};

	/**
	 * functions
	 */
	this.updateRowNumbers = function () {
		setTimeout( function () {
			var rows = $( '.fields_table' ).find( '.sub' );
			var i = 0;
			//console.log(rows);

			rows.each( function () {
				var n = $( this ).attr( 'data-num' );
				var inputs = $( this ).find( '*[name*="[fields][' + n + ']"]' );
				// console.log(n+" --> "+i);

				inputs.each( function () {
					var name = $( this ).attr( 'name' );
					$( this ).attr( 'name', name.replace( /(\[fields\]\[)(.*)(\]\[)/g, '[fields][' + i + '][' ) );
					//console.log($(this).attr('name'));
				} );
				$( this ).attr( 'data-num', i );
				i++;
			} );
		}, 100 );
	};

	this.updateNames = function ( row ) {
		var label = row.find( 'input[name*="[label]"]' );
		var label_val = label.val().length > 0 ? "_" + label.val() : '';
		var name = row.find( 'input[name*="[name]"]' );
		var name_val = name.val().length > 0 ? "_" + name.val() : '';
		var text = row.find( '.dyn_field_label' );
		var text_val = label_val;
		var type = row.find( 'select[name*="[type]"]' );
		var type_val = type.val();

		console.log( type );
		console.log( type_val );

		if ( type_val === 'hr' || type_val === 'space' ) {
			text_val = type.find( 'option[value="' + type_val + '"]' ).text();
		}

		label.val( label_val );
		text.text( text_val );
		name.val( name_val );
		type.val( type_val );
	};

	this.updateHeight = function ( sub, height ) {
		var height = ( typeof height === "undefined" ) ? sub.find( 'table' ).height() : height;
		sub.css( { "height": height } );
	};

	this.toggleRequired = function ( checkbox ) {
		var label = checkbox.closest( '.row_container' ).find( '.field_label' );
		if ( checkbox.is( ':checked' ) )
			label.addClass( "required" );
		else
			label.removeClass( "required" );
	};

	/**
	 * Open the user_meta wizard.
	 * Called by native onclick attribute on copy button.
	 * @since 1.7.4
	 */
	this.openWizard = function () {

		const mode = 'user_meta';

		greyd.backend.decide( mode, [
			// escape button option
			{
				callback: () => greyd.backend.triggerOverlay( false )
			},
			// confirm button option
			{
				callback: () => greyd.metaTable.checkCopySettings()
			}
		] );
	}

	/**
	 * Check the copy settings and ask the user to confirm.
	 */
	this.checkCopySettings = function () {

		const mode = 'user_meta';

		const copyRole    = $( '#copyRole' ).val();
		const roleText    = $( '#copyRole option:selected' ).text();
		const copyAction  = $( 'input[name="copyAction"]:checked' ).val();
		const copyText    = $( 'label[for="'+copyAction+'"]' ).data( 'replace' );

		greyd.backend.confirm(
			mode,
			copyText + ' ' + roleText,
			() => {

				// remove all the current rows
				if ( copyAction == 'copy-overwrite' ) {
					$( '.row_container:not(.hidden)' ).remove();
				}

				// add new rows
				if ( copyAction.indexOf( 'copy-' ) > -1 ) {
					const fieldsData = $( '#copyRole option:selected' ).data( 'fields' );
					if ( fieldsData ) {

						var into = $( "#greyd_sortable" );
						var row = $( ".row_container.hidden" );

						// add new rows
						fieldsData.forEach( () => {
							into.append( row.clone() );
						} );

						// update rows
						greyd.metaTable.updateRowNumbers();
						greyd.metaTable.addTriggers();

						// without timeout the fields are not updated correctly
						setTimeout( () => {

							const indexOffset = $( '.row_container' ).length - fieldsData.length - 1;

							// add data
							fieldsData.forEach( ( field, index ) => {
								greyd.metaTable.updateMetaFieldInputsByIndex( index + indexOffset, field );
							} );
						
							// show all except last one
							$( ".row_container.hidden:not(:last-child)" ).removeClass( 'hidden' );
							
							// trigger success
							greyd.backend.triggerOverlay( true, { "type": "success", "css": mode } )

						}, 100 );
					}
					else greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": 'no fields' });
				}
				else {

					let index = 0;
					const fields = [];
					$( '.row_container:not(.hidden)' ).each( function () {
						fields.push( greyd.metaTable.getMetaFieldValueByIndex( index ) );
						index++;
					} );

					console.log( fields );

					$.post(
						greyd.ajax_url, {
							action: 'greyd_ajax',
							'_ajax_nonce': greyd.nonce,
							mode: mode,
							data: {
								copyRole: copyRole,
								copyAction: copyAction,
								fields: fields
							}
						},
						function(response) {
							console.log(response);

							// // successfull
							if ( response.indexOf('success::') > -1 ) {
								greyd.backend.triggerOverlay( true, { "type": "success", "css": mode } );
							}
							// complete with error
							else if ( response.indexOf('error::') > -1 ) {
								var msg = response.split( 'error::' )[1];
								greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": msg });
							}
							// unknown state
							else {
								greyd.backend.triggerOverlay(true, {"type": "fail", "css": mode, "replace": response });
							}

							greyd.metaTable.updateRowNumbers();
						}
					);
				}
			}
		);
	}

	/**
	 * Get the metafield values from the table by index.
	 * @param {number} index
	 * @returns {object}
	 */
	this.getMetaFieldValueByIndex = function(index) {
		var roleSettingsObject = {};

		// Use jQuery to select input fields with names containing "role_settings[fields][index]"
		$('[name^="role_settings[fields][' + index + ']"]').each(function() {
			var name = $(this).attr('name'); // Get the name attribute
			var value = $(this).val();       // Get the value of the input field

			$( this ).is( ':checkbox' ) && ( value = $( this ).is( ':checked' ) ? value : 0 );
			if ( !value || !value.length ) return;
			
			if ( name && name.length ) {
				// Split the name using square brackets and extract the last part
				var keys = name.split('[');
				var key = keys[keys.length - 1].replace(']', '');
				// Assign the value with the simplified key
				roleSettingsObject[key] = value;
			}
		});
	
		return roleSettingsObject;
	}

	/**
	 * Update the metafield inputs by index.
	 * @param {number} index 
	 * @param {object} valuesObject Object of key/value pairs
	 */
	this.updateMetaFieldInputsByIndex = function(index, valuesObject) {
		// Iterate through the valuesObject
		for (var key in valuesObject) {
			if (valuesObject.hasOwnProperty(key)) {
				var value = valuesObject[key];
				var inputName = 'role_settings[fields][' + index + '][' + key + ']';
				console.log(inputName, value);
	
				// Use jQuery to select the input field by name and update its value
				$('[name="' + inputName + '"]').val(value).trigger('keyup')
			}
		}
	}

};
