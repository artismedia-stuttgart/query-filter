/**
 * Popups Admin script
 */

var greyd = greyd || {};

( function () {

	jQuery( function () {

		if ( typeof $ === 'undefined' ) $ = jQuery;

		greyd.popup.init();

		console.log( 'Pop-up Admin Scripts: loaded' );
	} );

} )( jQuery );

//
// popups backend
greyd.popup = new function () {

	var { __ } = wp.i18n;

	// events
	this.changed = false;
	this.init = function () {

		// wizard
		if ( typeof greyd.wizard !== 'undefined' ) {
			this.initWizard();
		}
		// settings
		if ( $( '.settings_input' ).length > 0 ) {

			$( '.settings_input_switch .greyd_switch input' ).each( function () {
				// input events for switch slider
				greyd.popup.setSlider( this );
				$( this ).on( 'input change', function () {
					greyd.popup.setSlider( this );
				} );
			} );

			$( '.settings_input_switch .settings_options select' ).each( function () {
				greyd.popup.customSelect( this, 'line' );
				$( this ).parent().find( '.input_item' ).on( 'click', function () {
					// click events for custom inputs
					greyd.popup.setSelect( this );
				} );
				// input events for switch buttons
				greyd.popup.setSwitch( this );
				$( this ).on( 'input change', function () {
					greyd.popup.setSwitch( this );
				} );
			} );

			$( '.settings_input_option.checkbox' ).each( function () {

				$( this ).find( 'input[type="checkbox"]' ).on( 'click', function () {
					// console.log($(this).prop('name')+": "+$(this).prop('checked'));
					var new_value = [];
					$( this ).closest( '.settings_input_option' ).find( 'input[type="checkbox"]' ).each( function () {
						if ( $( this ).prop( 'checked' ) ) new_value.push( $( this ).prop( 'name' ) );
					} );
					$( this ).closest( '.settings_input_option' ).find( 'input.settings_input_option_value' ).val( new_value.join( ',' ) );
					$( this ).closest( '.settings_input_option' ).find( 'input.settings_input_option_value' ).trigger( "change" );
				} );

			} );

			$( '.settings_input_option.autotags' ).each( function () {
				greyd.popup.setAutotags( this );
			} );
			if ( $( '.tag_suggestions' ).length > 0 ) {
				greyd.popup.initAutotags();
			}

			$( '.settings_input[data-param_type="info"]' ).each( function () {
				var wrapper = $( this );
				// console.log(wrapper);
				wrapper.find( '.info_toggle' ).on( 'click', function () {
					// console.log('clicked');
					wrapper.find( '.settings_input_options' ).slideToggle( 'fast' );
				} );

			} );

			greyd.popup.setUrlCondition( $( '.settings_input_options[data-param_name="urlparam"] select[name="condition"]' ) );
			$( '.settings_input_options[data-param_name="urlparam"] select[name="condition"]' ).on( 'change', function () {
				greyd.popup.setUrlCondition( this );
			} );
			greyd.popup.setTimeCheckbox( $( '.settings_input_options[data-param_name="time"] .settings_input_option[data-param_name="select"] input[name="custom"]' ) );
			$( '.settings_input_options[data-param_name="time"] .settings_input_option[data-param_name="select"] input[name="custom"]' ).on( 'click', function () {
				greyd.popup.setTimeCheckbox( this );
			} );

			// inputs
			$( '.settings_input .settings_input_value, .settings_input .settings_input_option_value' ).each( function () {
				$( this ).on( 'input change', function () {
					// input events
					greyd.popup.makeSettingsValue();
				} );
			} );
			greyd.popup.makeSettingsValue();

			// alert on leave if settings changed
			$( window ).on( 'beforeunload', greyd.popup.alertOnLeave );
			// don't alert on submit
			$( 'input[type="submit"]' ).on( "click", function () {
				$( window ).off( 'beforeunload', greyd.popup.alertOnLeave );
			} );

			setTimeout( function () {

				// show stuff
				$( '.popup_design .maybe_active' ).removeClass( 'maybe_active' ).addClass( 'active' );
				$( '#popup_preview' ).removeClass( 'hidden' );
				greyd.popup.changed = false;

			}, 0 );

			// console.log("popup settings events added ...");

		}

	};

	this.alertOnLeave = function () {
		if ( greyd.popup.changed )
			return 'Are you sure you want to leave?';
	};

	// settings
	this.setSlider = function ( el ) {
		if ( $( el ).prop( 'checked' ) ) {
			$( el ).closest( '.settings_input' ).removeClass( 'inactive' );
			$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).val( 'on' );
			$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).trigger( "change" );
			$( el ).closest( '.settings_input' ).find( '.settings_input_option_value' ).prop( 'disabled', false );
			$( el ).closest( '.settings_input' ).find( '.settings_input_options' ).slideDown( 'fast' );
		}
		else {
			$( el ).closest( '.settings_input' ).addClass( 'inactive' );
			$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).val( 'off' );
			$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).trigger( "change" );
			$( el ).closest( '.settings_input' ).find( '.settings_input_option_value' ).prop( 'disabled', true );
			$( el ).closest( '.settings_input' ).find( '.settings_input_options' ).slideUp( 'fast' );
		}
	};
	this.setSwitch = function ( el ) {
		if ( $( el ).val() == 'custom' )
			$( el ).closest( '.settings_input' ).find( '.settings_input_options' ).slideDown( 'fast' );
		else
			$( el ).closest( '.settings_input' ).find( '.settings_input_options' ).slideUp( 'fast' );
		if ( $( el ).val() == 'off' )
			$( el ).closest( '.settings_input' ).addClass( 'inactive' );
		else
			$( el ).closest( '.settings_input' ).removeClass( 'inactive' );
		$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).val( $( el ).val() );
		$( el ).closest( '.settings_input' ).find( '.settings_input_value' ).trigger( "change" );
	};
	this.setUrlCondition = function ( el ) {
		if ( $( el ).val() != 'empty' && $( el ).val() != 'not_empty' ) {
			$( '.settings_input_options[data-param_name="urlparam"] .settings_input_option[data-param_name="value"]' ).css( 'opacity', '1' );
		}
		else {
			$( '.settings_input_options[data-param_name="urlparam"] .settings_input_option[data-param_name="value"]' ).css( 'opacity', '0' );
		}
	};
	this.setTimeCheckbox = function ( el ) {
		if ( $( el ).prop( 'checked' ) ) {
			$( '.settings_input_options[data-param_name="time"] .settings_input_option[data-param_name="custom"]' ).slideDown( 'fast' );
		}
		else {
			$( '.settings_input_options[data-param_name="time"] .settings_input_option[data-param_name="custom"]' ).slideUp( 'fast' );
		}
	};

	this.customSelect = function ( el, mode ) {
		$( el ).addClass( 'hidden' );
		var value = $( el ).val();
		var options = el.options;
		var markup = '<div class="' + mode + '_items">';
		if ( mode == 'grid' ) {
			for ( var i = 0; i < options.length; i++ ) {
				if ( i % 3 == 0 ) markup += '<span>';
				var active = '';
				if ( options[ i ].value == value ) active = 'active';
				markup += '<span class="input_item ' + active + '" title="' + options[ i ].label + '" data-index="' + options[ i ].value + '"></span>';
				if ( i % 3 == 2 ) markup += '</span>';
			}
		}
		else if ( mode == 'line' ) {
			for ( var i = 0; i < options.length; i++ ) {
				var active = '';
				if ( options[ i ].value == value ) active = 'active';
				markup += '<span class="input_item ' + active + '" data-index="' + options[ i ].value + '">' + options[ i ].label + '</span>';
			}
		}
		markup += '</div>';
		$( el ).parent().append( markup );
	};
	this.setSelect = function ( el ) {
		var value = $( el ).data( 'index' );
		// console.log("clicked on "+value);
		var parent = $( el ).parent();
		if ( !$( parent ).hasClass( 'line_items' ) ) parent = $( parent ).parent();
		$( parent ).find( '.input_item' ).each( function () {
			$( this ).removeClass( 'active' );
		} );
		$( el ).addClass( 'active' );
		$( parent ).siblings( 'select' ).val( value );
		$( parent ).siblings( 'select' ).trigger( "change" );
	};

	this.setAutotags = function ( el ) {
		var wrapper = $( el );
		var data = wrapper.find( 'input' ).data( 'tags' );
		// console.log(data);
		var value = wrapper.find( 'input' ).val().split( ',' );
		// console.log(value);

		var tags_data = {};
		var suggestions = "";
		$.each( data, function ( key, values ) {
			suggestions += '<span class="tag_suggest">' + key + '</span>';
			$.each( values, function ( title, id ) {
				title = decodeURIComponent( title ).replace( /\+/g, ' ' );
				var display = "data-available='yes'";
				if ( value.includes( id.toString() ) ) display = "data-available='no' style='display:none'";
				suggestions += '<span class="tag_suggest option" data-value="' + id + '" ' + display + '>' + title + '</span>';
				tags_data[ id ] = title;
			} );
		} );
		var tags = "";
		var new_value = [];
		$.each( value, function () {
			if ( typeof tags_data[ this ] !== 'undefined' ) {
				new_value.push( this );
				var title = decodeURIComponent( tags_data[ this ] ).replace( /\+/g, ' ' );
				tags += '<span class="tag" data-value="' + this + '">' + title + '<span class="tag_close dashicons dashicons-no-alt"></span></span>';
			}
		} );
		wrapper.find( 'input' ).val( new_value.join( ',' ) );
		// console.log(tags_data);

		var markup = '<div class="tagarea">';
		markup += '<span class="tags">' + tags + '</span>';
		markup += '<input class="tag_input" type="text" size="3">';
		markup += '<span class="tag_suggestions">' + suggestions + '</span>';
		markup += '</div>';
		wrapper.append( markup );


		wrapper.find( '.tagarea' ).on( 'click', function () {
			$( this ).find( '.tag_input' ).focus();
		} );
		wrapper.find( '.tag_input' ).on( 'focus', function () {
			var inputpos = $( this ).offset();
			var inputheight = $( this ).height();
			var parentpos = $( this ).closest( '.settings_input' ).offset();
			$( this ).siblings( '.tag_suggestions' ).css( 'top', ( inputpos.top - parentpos.top + inputheight ) + 'px' );
			$( this ).siblings( '.tag_suggestions' ).css( 'left', ( inputpos.left - parentpos.left ) + 'px' );
			$( this ).siblings( '.tag_suggestions' ).css( 'display', 'block' );
		} );

		wrapper.find( '.tag_input' ).on( "keypress", function ( e ) {
			var redExp = new RegExp( "[a-zA-Z0-9- ]" );
			var key = String.fromCharCode( !event.charCode ? event.which : event.charCode );
			if ( !redExp.test( key ) ) return false;
			return true;
		} );
		wrapper.find( '.tag_input' ).on( "keyup", function ( e ) {
			var x = e || window.event;
			var key = ( x.keyCode || x.which );
			console.log( key );
			switch ( key ) {
				// case 188: // comma
				//     // $(this).val('');
				//     break;
				// case 40:
				// case 38: //down/up arrow
				//     break;
				// case 13: //enter
				//     break;
				case 27: //esc 
					$( this ).siblings( '.tag_suggestions' ).css( 'display', 'none' );
					break;
				default:
					var val = $( this ).val().toLowerCase().replace( ' ', '' );
					$( this ).siblings( '.tag_suggestions' ).css( 'display', 'block' );
					$( this ).siblings( '.tag_suggestions' ).find( '.tag_suggest.option' ).each( function () {
						if ( $( this ).data( 'available' ) == 'yes' ) {
							var option = $( this ).html().toLowerCase().replace( ' ', '' );
							option += $( this ).data( 'value' ).toString();
							if ( option.indexOf( val ) > -1 ) $( this ).css( 'display', 'block' );
							else $( this ).css( 'display', 'none' );
						}
					} );
					$( this ).attr( 'size', $( this ).val().length + 1 ); //increase size
					break;
			}
		} );

		wrapper.find( '.tag_suggest.option' ).on( 'click', function ( e ) {
			// console.log("add tag");
			// console.log($(this));
			var id = $( this ).data( 'value' );
			var title = decodeURIComponent( $( this ).html() ).replace( /\+/g, ' ' );
			var tags = $( this ).closest( '.autotags' ).find( '.tags' );
			var input = $( this ).closest( '.autotags' ).find( '.settings_input_option_value' );

			var is_cat = ( typeof id === 'string' && id.indexOf( 'cat_' ) ) === 0 ? __( "Category", 'greyd_hub' ) + ": " : "";
			var new_tag = '<span class="tag" data-value="' + id + '">' + is_cat + title + '<span class="tag_close dashicons dashicons-no-alt"></span></span>';
			tags.append( new_tag );
			var new_value = [];
			tags.find( '.tag' ).each( function () {
				new_value.push( $( this ).data( 'value' ) );
			} );
			input.val( new_value.join( ',' ) );
			input.trigger( "change" );

			$( this ).parent().siblings( '.tag_input' ).val( '' );
			$( this ).parent().siblings( '.tag_input' ).attr( 'size', 3 );
			$( this ).css( 'display', 'none' );
			$( this ).data( 'available', 'no' );
			$( this ).siblings( '.tag_suggest.option' ).each( function () {
				if ( $( this ).data( 'available' ) == 'yes' ) {
					$( this ).css( 'display', 'block' );
				}
			} );
		} );
	};
	this.initAutotags = function () {
		$( document ).on( "mouseup", function ( e ) {
			var suggestions = $( ".tag_suggestions" );
			if ( !suggestions.is( e.target ) && // if the target of the click isn't the container...
				suggestions.has( e.target ).length === 0 ) { // ... nor a descendant of the container
				suggestions.css( 'display', 'none' );
			}
		} );
		$( document ).on( 'click', '.tag_close', function ( e ) {
			// var val = $(this).parent().data('value');
			var tag = $( this ).parent();
			var tags = $( this ).closest( '.autotags' ).find( '.tags' );
			var input = $( this ).closest( '.autotags' ).find( '.settings_input_option_value' );

			var val = tag.data( 'value' );
			tag.parent().siblings( ".tag_suggestions" ).find( '.tag_suggest.option' ).each( function () {
				if ( $( this ).data( 'value' ) == val ) {
					$( this ).css( 'display', 'block' );
					$( this ).data( 'available', 'yes' );
				}
			} );
			tag.parent().siblings( ".tag_suggestions" ).css( 'display', 'none' );
			tag.remove();

			var new_value = [];
			tags.find( '.tag' ).each( function () {
				new_value.push( $( this ).data( 'value' ) );
			} );
			input.val( new_value.join( ',' ) );
			input.trigger( "change" );
		} );
		$( document ).on( 'click', '.tag', function ( e ) {
			$( this ).parent().siblings( ".tag_suggestions" ).css( 'display', 'none' );
		} );
	};

	this.makeSettingsValue = function () {

		// console.log("make settings value ...");
		var data = {};
		$( '.settings_input .settings_input_value' ).each( function () {
			var name = $( this ).prop( 'name' );
			var value = $( this ).val();
			if ( $( this ).prop( 'type' ) == "checkbox" ) {
				value = 'false';
				if ( $( this ).prop( 'checked' ) )
					value = 'true';
			}
			data[ name ] = { value: value };
			if ( $( this ).closest( '.settings_input' ).find( '.settings_input_option_value' ).length > 0 ) {
				var options = {};
				$( this ).closest( '.settings_input' ).find( '.settings_input_option_value' ).each( function () {
					var oname = $( this ).prop( 'name' );
					var ovalue = $( this ).val();
					options[ oname ] = ovalue;
				} );
				data[ name ].options = options;
			}
		} );
		// console.log(data);
		// console.log(JSON.stringify(data));
		var old_data = $( '.popup_settings_data' ).val();
		var new_data = JSON.stringify( data );
		if ( new_data != old_data ) {
			// console.log("new settings data found");
			greyd.popup.changed = true;
		}
		$( '.popup_settings_data' ).val( new_data );
		$( '.popup_settings_data' ).trigger( "change" );
	};


	// Wizard
	this.initWizard = function () {

		$( document ).on( 'click', '#menu-posts-greyd_popup a[href*="post-new.php?post_type=greyd_popup"]', greyd.popup.redirectToNew );
		$( document ).on( 'click', '#wp-toolbar a[href*="post-new.php?post_type=greyd_popup"]', greyd.popup.redirectToNew );
		$( document ).on( 'click', '#wpbody-content .page-title-action[href*="post-new.php?post_type=greyd_popup"]', greyd.popup.redirectToNew );

		// popup wizard
		if ( $( '#greyd-wizard.popup_wizard' ).length > 0 ) {
			var name_input = $( '#greyd-wizard #name' );

			name_input.on( 'input change', function () { greyd.wizard.checkExisting( greyd?.data?.popups ); } );
			name_input.on( 'keyup', function () { name_input.addClass( 'changed' ); } );

			$( '#greyd-wizard .content_option' ).on( 'click', function () {
				greyd.wizard.selectContent( this );
				if ( !name_input.hasClass( 'changed' ) ) {
					$( '#greyd-wizard #name' ).val( $( this ).children( 'p' ).text() );
					name_input.trigger( 'change' );
				}
			} );

			$( '#greyd-wizard .double_reset' ).on( 'click', function () { greyd.popup.createPopup( this ); } );
			$( '#greyd-wizard .create' ).on( 'click', function () { greyd.popup.createPopup( this ); } );

			// console.log(location.search);
			if ( location.search == '?post_type=greyd_popup&wizard=add' ) greyd.wizard.initnew();
		}

	};
	this.redirectToNew = function ( e ) {
		e.preventDefault();
		var url = window.location.href.split( 'wp-admin' );
		if ( url[ 1 ].indexOf( "/edit.php?post_type=greyd_popup" ) > -1 ) greyd.wizard.initnew();
		else window.location.href = url[ 0 ] + "wp-admin/edit.php?post_type=greyd_popup&wizard=add";
	};
	this.createPopup = function ( el ) {
		if ( $( el ).hasClass( 'disabled' ) ) return;

		var result = {
			name: $( '#greyd-wizard #name' ).val(),
			slug: $( '#greyd-wizard #slug' ).val(),
			type: $( '#greyd-wizard #ttype' ).val(),
			content: $( '#greyd-wizard .content_option.active' ).attr( 'id' ),
		};
		if ( $( el ).hasClass( 'double_reset' ) ) {
			result[ 'force' ] = 'true';
			result[ 'content' ] = 'empty';
		}
		if ( result.content == 'copy' ) {
			if ( $( '#greyd-wizard #copy_' + result.type ).length == 0 ) result.content = 'empty';
			else {
				result.copy = $( '#greyd-wizard #copy_' + result.type ).val();
				if ( result.copy == "0" ) {
					alert( __( "Please choose a pop-up to copy.", 'greyd_hub' ) );
					return;
				}
			}
		}
		if ( result.name == "" ) {
			alert( __( "Please enter a name for the pop-up.", 'greyd_hub' ) );
			return;
		}

		// create popup post
		// console.log(result);
		greyd.wizard.createPost( 'create_popup', result );
	};

	// Backend
	this.prio_dec = function ( id ) {
		greyd.popup.prio( id, "minus" );
	};
	this.prio_inc = function ( id, ) {
		greyd.popup.prio( id, "plus" );
	};
	this.prio = function ( id, mode ) {
		// console.log(mode+" priority for "+id);
		if ( $( '#popup_' + id + '_priority' ).parent().hasClass( 'blocked' ) ) {
			// console.log("blocked");
			return;
		}
		var value = parseInt( $( '#popup_' + id + '_priority' ).html() );
		if ( mode == "plus" ) value++;
		else if ( mode == "minus" ) value--;
		if ( value < 1 ) console.log( "minimum reached" );
		else if ( value > 9 ) console.log( "maximum reached" );
		if ( value > 0 && value < 10 ) {

			// mod popup via ajax
			var result = {
				id: id,
				value: value,
			};

			// greyd_hub compatibility
			var ajax = {};
			if ( typeof greyd.ajax_url !== 'undefined' ) {
				ajax = {
					ajax_url: greyd.ajax_url,
					nonce: greyd.nonce,
					action: "greyd_admin_ajax"
				};
			}
			else if ( typeof wizzard_details !== 'undefined' ) {
				ajax = {
					ajax_url: wizzard_details.ajax_url,
					nonce: wizzard_details.nonce,
					action: "greyd_ajax"
				};
			}
			else return;


			$.post(
				ajax.ajax_url,
				{
					'action': ajax.action,
					'_ajax_nonce': ajax.nonce,
					'mode': 'mod_popup',
					'data': result,
				},
				function ( response ) {
					if ( response.indexOf( 'error::' ) > -1 ) {
						console.warn( response );
						var tmp = response.split( 'error::' );
						var message = tmp[ 1 ];
						$( '#popup_' + id + '_priority' ).parent().removeClass( 'blocked' );
					}
					else {
						console.info( response );
						$( '#popup_' + id + '_priority' ).html( value );
						$( '#popup_' + id + '_priority' ).parent().removeClass( 'blocked' );
						$( '#popup_' + id + '_priority' ).siblings().removeClass( 'off' );
						if ( value == 1 ) $( '#popup_' + id + '_priority' ).prev().addClass( 'off' );
						else if ( value == 9 ) $( '#popup_' + id + '_priority' ).next().addClass( 'off' );
					}
				}
			);
			$( '#popup_' + id + '_priority' ).parent().addClass( 'blocked' );
		}
	};
};