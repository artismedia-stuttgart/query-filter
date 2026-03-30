/**
 * User Management admin script.
 * 
 * @since 1.5.4
 */
document.addEventListener( "DOMContentLoaded", function () {

	/**
	 * metaboxes
	 */
	if ( typeof postboxes !== 'undefined' ) {

		/**
		 * disable some metabox events
		 */
		postboxes.save_state = function ( page ) {
			// console.log("save nothing!");
		};
		postboxes.save_order = function ( page ) {
			// console.log("save nothing!");
		};
		postboxes.handleOrder = function () {
			// console.log("don't move!");
		};
		var sortables = document.querySelectorAll( '.hndle' );
		if ( sortables.length > 0 ) {
			// disable sorting handler
			sortables.forEach( function ( sortable ) {
				sortable.classList.remove( 'hndle' );
			} );
		}
		/**
		 * init core metabox events
		 */
		postboxes.add_postbox_toggles( pagenow );

		/**
		 * Tabs Tables
		 */
		var tabsTables = document.querySelectorAll( '.tabs_table' );
		if ( tabsTables.length > 0 ) {

			tabsTables.forEach( function ( table ) {

				// navi items
				var tableMenu = table.querySelectorAll( 'th li' );
				var tableItems = table.querySelectorAll( 'td li' );

				// tab events
				tableMenu.forEach( function ( item, key ) {
					item.addEventListener( 'click', function ( e ) {
						// console.log(item.dataset.tab);
						tableMenu.forEach( function ( i ) {
							i.classList.remove( 'active' );
						} );
						item.classList.add( 'active' );
						tableItems.forEach( function ( i ) {
							i.classList.remove( 'hidden' );
							if ( item.dataset.tab != 'all' && item.dataset.tab != i.dataset.tab ) {
								i.classList.add( 'hidden' );
							}
						} );
					} );
				} );

			} );

			// console.log("tabs table scripts loaded.");
		}
	}

	/**
	 * roles edit page
	 */
	var roleEditForm = document.querySelectorAll( '#roles-edit-form' );
	if ( roleEditForm.length > 0 ) {

		/**
		 * settings
		 */
		var title = roleEditForm[ 0 ].querySelectorAll( '#titlediv input' );
		if ( title.length > 0 ) {
			// listen for input on title
			title[ 0 ].addEventListener( 'input', function ( e ) {
				// console.log("title changed");
				dirty = true;
			} );
		}
		var slug = roleEditForm[ 0 ].querySelectorAll( '#settings_wording input' );
		if ( slug.length > 0 ) {
			// listen for input on slug
			slug[ 0 ].addEventListener( 'input', function ( e ) {
				// console.log("slug changed");
				dirty = true;
			} );
		}

		/**
		 * capabilities (boxes)
		 */
		var capsBox = roleEditForm[ 0 ].querySelectorAll( '.tabs_table' );
		if ( capsBox.length > 0 ) {

			capsBox.forEach( function ( caps ) {

				// caps navi items
				var capsMenu = caps.querySelectorAll( 'th li' );
				var capsItems = caps.querySelectorAll( 'td li' );

				// listen for input on checkboxes
				capsItems.forEach( function ( i ) {
					var checkbox = i.querySelectorAll( 'input' )[ 0 ];
					checkbox.addEventListener( 'change', function ( e ) {
						// console.log("checkbox changed");
						dirty = true;
					} );
				} );

				// add capability btn
				var addCap = caps.querySelectorAll( '.add_cap' );
				if ( addCap.length > 0 ) {
					var input = addCap[ 0 ].querySelectorAll( 'input' )[ 0 ];
					var btn = addCap[ 0 ].querySelectorAll( '.button' )[ 0 ];
					// add
					btn.addEventListener( 'click', function ( e ) {
						// console.log("add new capability");
						if ( input.value != "" ) {
							// console.log(input.value);
							var cap = input.value;
							var duplicate = false;
							capsItems.forEach( function ( i ) {
								var checkbox = i.querySelectorAll( 'input' )[ 0 ];
								var id = checkbox.id.replace( 'role_capability_', '' );
								if ( cap == id ) duplicate = true;
							} );
							if ( duplicate ) {
								return alert( '"' + cap + '" already exists' );
							}
							var newCap = "<li data-tab='custom'>" +
								"<label for='role_capability_" + cap + "' class='flex'>" + cap +
								"<input type='checkbox' id='role_capability_" + cap + "' name='role_settings[capabilities][" + cap + "]' checked='checked' autocomplete='off'>" +
								"</label></li>";
							addCap[ 0 ].parentElement.insertAdjacentHTML( 'beforebegin', newCap );
							capsItems = caps.querySelectorAll( 'td li' );
							input.value = "";
							dirty = true;
						}
					} );
				};

				// copy capability from other role
				var copyFrom = caps.querySelectorAll( '.copy_from' );
				if ( copyFrom.length > 0 ) {
					var select = copyFrom[ 0 ].querySelectorAll( 'select' )[ 0 ];
					var btn = copyFrom[ 0 ].querySelectorAll( '.button' )[ 0 ];
					// select role
					select.addEventListener( 'change', function ( e ) {
						// console.log("select role "+select.value);
						if ( select.value != "" ) {
							btn.style.display = "inline-block";
						}
						else {
							btn.style.display = "none";
						}
					} );
					// copy selection
					btn.addEventListener( 'click', function ( e ) {
						// console.log("copy capabilities from "+select.value);
						var index = select.options.selectedIndex;
						var caps = JSON.parse( select.options[ index ].dataset[ 'caps' ] );
						capsItems.forEach( function ( i ) {
							var checkbox = i.querySelectorAll( 'input' )[ 0 ];
							// console.log(checkbox);
							var id = checkbox.id.replace( 'role_capability_', '' );
							checkbox.checked = ( typeof caps[ id ] !== 'undefined' );
							checkbox.dispatchEvent( new Event( 'change' ) );
						} );
					} );
				};

				// check/uncheck all visible capabilities
				var toggleAll = caps.querySelectorAll( '.toggle_all' );
				if ( toggleAll.length > 0 ) {
					// uncheck
					var unmark = toggleAll[ 0 ].querySelectorAll( '.unmark_all' )[ 0 ];
					unmark.addEventListener( 'click', function ( e ) {
						// console.log("uncheck all visible capabilities");
						capsItems.forEach( function ( i ) {
							if ( !i.classList.contains( 'hidden' ) ) {
								var checkbox = i.querySelectorAll( 'input' )[ 0 ];
								checkbox.checked = false;
								checkbox.dispatchEvent( new Event( 'change' ) );
							}
						} );
					} );
					// check
					var mark = toggleAll[ 0 ].querySelectorAll( '.mark_all' )[ 0 ];
					mark.addEventListener( 'click', function ( e ) {
						// console.log("check all visible capabilities");
						capsItems.forEach( function ( i ) {
							if ( !i.classList.contains( 'hidden' ) ) {
								var checkbox = i.querySelectorAll( 'input' )[ 0 ];
								checkbox.checked = true;
								checkbox.dispatchEvent( new Event( 'change' ) );
							}
						} );
					} );
				}

			} );

			// console.log("capabilities box scripts loaded.");
		}


		/**
		 * Clean dirty flag on submit
		 */
		roleEditForm[ 0 ].addEventListener( 'submit', function ( e ) {
			// clean for saving;
			dirty = false;
			greyd.metaTable.dirty = false;
		} );

		// console.log("edit role page scripts loaded.");
	}

	/**
	 * roles page
	 */
	var rolesForm = document.querySelectorAll( '#roles-form' );
	if ( rolesForm.length > 0 ) {

		var roles = [];
		var roleItems = rolesForm[ 0 ].querySelectorAll( 'tr td.column-slug' );
		if ( roleItems.length > 0 ) {
			roleItems.forEach( function ( item, key ) {
				if ( item.textContent != 'super' ) roles.push( item.textContent );
			} );
		}

		var deleteButtons = rolesForm[ 0 ].querySelectorAll( '.row-actions .delete a' );
		if ( deleteButtons.length > 0 ) {

			deleteButtons.forEach( function ( item, key ) {
				item.addEventListener( 'click', function ( e ) {
					if ( parseInt( item.dataset.usercount ) > 0 ) {
						// promt for new user role
						var options = roles;
						var index = roles.indexOf( this.dataset.role );
						if ( index > -1 ) options.splice( index, 1 );
						var text = "Enter new role for %s users.\nPossible roles are:\n\n";
						text = text.replace( "%s", this.dataset.usercount );
						// prompt user
						var p = prompt( text + options.join( "\n" ), "" );
						if ( p == null || p == false ) {
							e.preventDefault();
							return false;
						}
						else if ( p == "" || options.indexOf( p ) == -1 ) {
							alert( 'Please enter a valid role.' );
							e.preventDefault();
							return false;
						}
						else {
							// console.log(p);
							// console.log(this);
							this.setAttribute( "href", this.href + "&newroles[0]=" + p );
						}
					}
					else {
						// confirm delete
						var text = 'Are you sure to permanently delete role "%s"?';
						text = text.replace( "%s", this.dataset.role );
						if ( !confirm( text ) ) {
							e.preventDefault();
							return false;
						}
					}
				} );
			} );

		}

		// console.log("roles page scripts loaded.");
	}

	/**
	 * settings (urls) page
	 */
	var settingsForm = document.querySelectorAll( '#settings-form' );
	if ( settingsForm.length > 0 ) {

		/**
		 * Blacklist
		 */
		var blacklist = settingsForm[ 0 ].querySelectorAll( 'td[data-blacklist]' );
		if ( blacklist.length > 0 ) {
			blacklist = JSON.parse( blacklist[ 0 ].dataset.blacklist );
		}
		else blacklist = array();
		// console.log(blacklist);

		/**
		 * All Inputs
		 */
		// url inputs
		var url_inputs = settingsForm[ 0 ].querySelectorAll( 'input[type=text].url_input' );
		if ( url_inputs.length > 0 ) {

			url_inputs.forEach( function ( input ) {
				// console.log(input);

				// get blacklist alert element
				var blacklisted_name_alert = false;
				var next = input.nextElementSibling;
				while ( next ) {
					if ( next.classList.contains( 'blacklisted_name_alert' ) ) {
						blacklisted_name_alert = next;
						break;
					}
					next = next.nextElementSibling;
				}

				// init placeholder
				if ( typeof input.dataset.global !== 'undefined' )
					input.placeholder = input.dataset.global;
				else
					input.placeholder = input.dataset.default;

				// input check
				input.addEventListener( 'input', function ( e ) {
					// console.log('input');
					// console.log(input.value);
					// check pasted string
					var regex = new RegExp( "^[a-zA-Z0-9/_\-]+$" );
					if ( !regex.test( input.value ) ) {
						console.warn( "not allowed characters found and removed!" );
						input.value = input.value.split( new RegExp( "[^a-zA-Z0-9/_\-]" ) ).join( '' );
					}
					// placeholder
					if ( input.name == 'login_url' ) {
						// console.log(input);
						var placeholder = input.value;
						if ( placeholder == "" ) placeholder = input.dataset.default;
						url_inputs.forEach( function ( i ) {
							// change only if there is no global value
							if ( typeof i.dataset.global === 'undefined' ) {
								// i.placeholder = i.dataset.default;
								if ( i.name == 'login_url' ) i.placeholder = placeholder;
								if ( i.name == 'logout_url' ) i.placeholder = placeholder + '?action=logout';
								if ( i.name == 'register_url' ) i.placeholder = placeholder + '?action=register';
								if ( i.name == 'reset_url' ) i.placeholder = placeholder + '?action=lostpassword';
							}
						} );
					}
					// check blacklist
					if ( blacklisted_name_alert ) {
						if ( blacklist.indexOf( e.target.value ) > -1 ) {
							console.warn( e.target.value + " is blacklisted!" );
							blacklisted_name_alert.classList.add( 'open' );
						}
						else blacklisted_name_alert.classList.remove( 'open' );
					}
					dirty = true;
				} );
			} );

		}
		// default role and redirects
		var selects = settingsForm[ 0 ].querySelectorAll( 'input[type=radio], select' );
		if ( selects.length > 0 ) {

			selects.forEach( function ( select ) {
				// console.log(select);
				select.addEventListener( 'change', function ( e ) {
					// console.log("changed");
					dirty = true;
				} );
			} );

		}
		// custom redirects
		var inputs = settingsForm[ 0 ].querySelectorAll( 'input[type=text].custom_hide' );
		if ( inputs.length > 0 ) {

			inputs.forEach( function ( input ) {
				// console.log(input);
				input.addEventListener( 'input', function ( e ) {
					// console.log("changed");
					// check input string
					var regex = new RegExp( "^[A-Za-z0-9\-._~!$&'()*+,;=:@\/?]*$" );
					if ( !regex.test( input.value ) ) {
						console.warn( "not allowed characters found and removed!" );
						input.value = input.value.split( new RegExp( "[^A-Za-z0-9\-._~!$&'()*+,;=:@\/?]" ) ).join( '' );
					}
					dirty = true;
				} );
			} );

		}

		/**
		 * Clean dirty flag on submit
		 */
		settingsForm[ 0 ].addEventListener( 'submit', function ( e ) {
			// clean for saving;
			if ( dirty ) dirty = false;
			else {
				alert( "Nothing to save." );
				e.preventDefault();
				return false;
			}
		} );

		console.log( "settings page scripts loaded." );
	}

	/**
	 * mails page
	 */
	var mailsForm = document.querySelectorAll( '#mails-form' );
	if ( mailsForm.length > 0 ) {

		// more/less
		var more_toggles = mailsForm[ 0 ].querySelectorAll( '.toggle_more' );
		if ( more_toggles.length > 0 ) {

			more_toggles.forEach( function ( more ) {
				// console.log(more);
				more.addEventListener( 'click', function ( e ) {
					var less = more.parentElement.querySelectorAll( '.toggle_less' )[ 0 ];
					var el = more.parentElement.querySelectorAll( '.toggle_element' )[ 0 ];
					more.classList.add( 'hidden' );
					less.classList.remove( 'hidden' );
					el.classList.remove( 'hidden' );
				} );
			} );

		}
		var less_toggles = mailsForm[ 0 ].querySelectorAll( '.toggle_less' );
		if ( less_toggles.length > 0 ) {

			less_toggles.forEach( function ( less ) {
				// console.log(less);
				less.addEventListener( 'click', function ( e ) {
					var more = less.parentElement.querySelectorAll( '.toggle_more' )[ 0 ];
					var el = less.parentElement.querySelectorAll( '.toggle_element' )[ 0 ];
					more.classList.remove( 'hidden' );
					less.classList.add( 'hidden' );
					el.classList.add( 'hidden' );
				} );
			} );

		}

		// insert default (placeholder value)
		var insertButtons = mailsForm[ 0 ].querySelectorAll( '.insert_subject, .insert_message' );
		if ( insertButtons.length > 0 ) {

			insertButtons.forEach( function ( insert ) {
				// console.log(insert);
				insert.addEventListener( 'click', function ( e ) {
					// console.log("insert default value");
					if ( insert.nextElementSibling.disabled != true ) {
						insert.nextElementSibling.value = insert.nextElementSibling.placeholder;
						dirty = true;
					}
				} );
			} );

		}

		// send testmails via ajax
		var testmails = mailsForm[ 0 ].querySelectorAll( '.testmail' );
		if ( testmails.length > 0 ) {

			// greyd_hub compatibility
			var ajax = {};
			if ( typeof wizzard_details !== 'undefined' )
				ajax = { ajax_url: wizzard_details.ajax_url, nonce: wizzard_details.nonce, action: "greyd_ajax" };
			else if ( typeof greyd !== 'undefined' )
				ajax = { ajax_url: greyd.ajax_url, nonce: greyd.nonce, action: "greyd_admin_ajax" };

			testmails.forEach( function ( testmail ) {

				// console.log(testmail);
				testmail.parentElement.addEventListener( 'click', function ( e ) {
					var mailMode = testmail.dataset.testmail_mode == "" ? 'site' : 'global';
					var mailType = testmail.dataset.testmail_type;
					var mailFunction = testmail.dataset.testmail;
					// console.log("make testmail preview: "+mailFunction);

					var makeAddressStrings = function ( mails ) {
						var string = [];
						mails.forEach( function ( value, index ) {
							var address = makeAddressString( value );
							string.push( address );
						} );
						return string.join( ', ' );
					};
					var makeAddressString = function ( mail ) {
						var address = '&lt;' + mail[ 0 ] + '&gt;';
						if ( mail.length > 1 && mail[ 1 ] != "" ) address = mail[ 1 ] + ' ' + address;
						return address;
					};

					// get preview via ajax call
					var data = [
						'action=' + ajax.action,
						'_ajax_nonce=' + ajax.nonce,
						'mode=send_testmail',
						'data[mode]=' + mailMode,
						'data[type]=' + mailType,
						'data[testmail]=' + mailFunction,
						'data[testmode]=preview'
					];
					var request = new XMLHttpRequest();
					request.open( "POST", ajax.ajax_url, true );
					request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded;' );
					request.onreadystatechange = function () {
						// console.log(request.readyState);
						if ( request.readyState === 4 ) {
							// console.log(request.status);
							if ( request.status === 200 ) {
								if ( request.response.indexOf( 'error::' ) > -1 ) {
									console.warn( request.response );
									greyd.backend.triggerOverlay( true, { type: 'fail', css: 'previewmail' } );
								}
								else {
									// console.info( request.response );
									var tmp = request.response.split( 'success::' );
									var msg = JSON.parse( tmp[ 1 ] );
									// console.log(msg);

									// info
									if (msg.dev_link.indexOf('404') == -1) {
										var url = "https://developer.wordpress.org/reference/functions/"+mailFunction+"/";
										var ref = "<a href='"+url+"' target='_blank'>"+mailFunction+"()</a>";
										var info = document.querySelectorAll('#testmail_info')[0];
										info.innerHTML = "Code Reference: "+ref;
									}

									// from
									var from = document.querySelectorAll( '#preview_from' )[ 0 ];
									from.innerHTML = makeAddressString( [ msg.from, msg.from_name ] );
									// to
									var to = document.querySelectorAll( '#preview_to' )[ 0 ];
									to.innerHTML = makeAddressStrings( msg.to );
									// cc
									var cc = document.querySelectorAll( '#preview_cc' )[ 0 ];
									if ( msg.cc.length > 0 ) {
										cc.innerHTML = makeAddressStrings( msg.cc );
										cc.parentElement.classList.remove( 'hidden' );
									}
									else cc.parentElement.classList.add( 'hidden' );
									// bcc
									var bcc = document.querySelectorAll( '#preview_bcc' )[ 0 ];
									if ( msg.bcc.length > 0 ) {
										bcc.innerHTML = makeAddressStrings( msg.bcc );
										bcc.parentElement.classList.remove( 'hidden' );
									}
									else bcc.parentElement.classList.add( 'hidden' );

									// subject
									var subject = document.querySelectorAll( '#preview_subject' )[ 0 ];
									subject.innerHTML = msg.subject;
									// message
									var message = document.querySelectorAll( '#preview_message' )[ 0 ];
									message.innerHTML = msg.message.split( '\n' ).join( '<br>' );

									greyd.backend.decide( "previewmail",
										greyd.backend.fadeOutOverlay, // callback button1
										function () {
											// confirm sending
											var text = 'Send Testmail to the following Receivers?\n\n';
											if ( testmail.dataset.testmail_to != "" ) {
												text = 'Send the Email to the following Receiver set in the Testmail options?\n\n';
												text += testmail.dataset.testmail_to;
											}
											else {
												text = 'Send the Email to the following original Receivers?\n\n';
												text += to.innerHTML;
												if ( msg.cc.length > 0 ) text += "\n" + cc.innerHTML;
												if ( msg.bcc.length > 0 ) text += "\n" + bcc.innerHTML;
											}
											text = text.split( '&lt;' ).join( '<' ).split( '&gt;' ).join( '>' );
											if ( !confirm( text ) ) {
												return false;
											}

											// send email via second ajax call
											// console.log("sending the testmail: "+mailFunction);
											var send_data = [
												'action=' + ajax.action,
												'_ajax_nonce=' + ajax.nonce,
												'mode=send_testmail',
												'data[mode]=' + mailMode,
												'data[type]=' + mailType,
												'data[testmail]=' + mailFunction,
												'data[testmode]=send'
											];
											var send_request = new XMLHttpRequest();
											send_request.open( "POST", ajax.ajax_url, true );
											send_request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded;' );
											send_request.onreadystatechange = function () {
												if ( send_request.readyState === 4 ) {
													if ( send_request.status === 200 ) {
														if (send_request.response.indexOf('error::') > -1) {
															// console.warn( send_request.response );
															var msg = send_request.response.split('error::');
															var msg = msg[1].split('debug::');
															console.warn('------------- SMTP debug -------------\n');
															console.warn( msg[0] );
															// make error debug message
															var errors = msg[1];
															errors = errors.split('\n').join('</p><p>');
															errors = '<p>'+errors+'</p>';
															errors = errors.split('<p></p>').join('');
															errors = '<span class="greyd_info_box error"><span>'+errors+'</span></span>';
															greyd.backend.triggerOverlay(true, { type: 'fail', css: 'testmail', replace: errors });
														}
														else {
															// console.info( send_request.response );
															var msg = send_request.response.split('success::');
															console.info('------------- SMTP debug -------------\n');
															console.info( msg[1] );
															greyd.backend.triggerOverlay(true, { type: 'success', css: 'testmail' });
														}
													}
													else greyd.backend.triggerOverlay( true, { type: 'fail', css: 'testmail' } );
												}
											};
											send_request.send( send_data.join( '&' ) );

											// show second loader
											greyd.backend.triggerOverlay( true, { type: 'loading', css: 'testmail' } );

										}, // callback button2
										[ 1 ], // args button1
										[] // args button2
									);
								}
							}
							else greyd.backend.triggerOverlay( true, { type: 'fail', css: 'previewmail' } );
						}
					};
					request.send( data.join( '&' ) );

					// show loader
					greyd.backend.triggerOverlay( true, { type: 'loading', css: 'previewmail' } );

				} );

			} );

		}

		/**
		 * inputs and selects
		 */
		var inputs = mailsForm[ 0 ].querySelectorAll( 'input, textarea, select' );
		if ( inputs.length > 0 ) {
			// listen for inputs
			inputs.forEach( function ( input ) {
				input.addEventListener( 'input', function ( e ) {
					// console.log("changed");
					dirty = true;
				} );
				input.addEventListener( 'change', function ( e ) {
					// console.log("changed");
					dirty = true;
				} );
			} );
		}

		/**
		 * Clean dirty flag on submit
		 */
		mailsForm[ 0 ].addEventListener( 'submit', function ( e ) {
			// clean for saving;
			if ( dirty ) dirty = false;
			else {
				alert( "Nothing to save." );
				e.preventDefault();
				return false;
			}
		} );

		console.log( "mails page scripts loaded." );
	}

	/**
	 * Check if page is dirty
	 */
	var dirty = false;
	window.onbeforeunload = function () {
		// console.log("check inputs");
		if ( dirty || ( typeof greyd.metaTable !== 'undefined' && greyd.metaTable.dirty ) ) {
			return false;
		}
	};

} );