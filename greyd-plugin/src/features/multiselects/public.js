/**
 * Custom Input Scripts
 * - multiselect
 * - select
 * - checkbox
 * - radio
 * 
 * Moved from classic greyd default.js
 * was global default.js variable 'custom_inputs'
 */

var greyd = greyd || {};

( function() {
	jQuery( function() {
		if ( typeof $ === 'undefined' ) $ = jQuery;

		// do not init if classic theme is detected
		if ( typeof theme !== 'undefined' ) return;

		greyd.input.init();

		console.log( "Input Scripts: loaded" );
	} );
} )( jQuery );


/**
 * init greyd.input var
 */
greyd.input = greyd.input || {};

/**
 * init all inputs
 * multiselects, selects, checkboxes, radios
 */
greyd.input.init = function() {
	greyd.input.multiselects.init();
	greyd.input.selects.init();
	greyd.input.boxes.init();
}

/**
 * close all multiselect and select boxes in the document
 * except the current select box
 */
greyd.input.closeAll = function( current ) {
	greyd.input.multiselects.closeAll();
	greyd.input.selects.closeAll(current);
}


/**
 * Multiselect
 */
greyd.input.multiselects = new function() {

	/**
	 * Custom multiselects
	 */
	this.init = function() {

		if ( typeof $ === 'undefined' ) $ = jQuery;

		// console.log( "greyd.input.multiselects init" );

		$( ".greyd_form select[multiple]" ).each( function() {
			greyd.input.multiselects.render( this );
		} );

		$( ".greyd_multiselect" ).each( function() {
			var wrap = $( this );
			var input = wrap.find( ".input" );

			// open the dropdown
			input.off( "click" ).on( "click", function( e ) {
				e.stopPropagation();
				var open = wrap.hasClass( "open" ) ? false : true;
				$( ".greyd_multiselect" ).each( function() { $( this ).removeClass( "open" ); } );
				greyd.input.closeAll(null);
				if ( open ) {
					wrap.addClass( "open" );
					$( document ).one( "click", function() {
						wrap.removeClass( "open" );
					} );
				}
			} );

			// toggle tag
			var option = wrap.find( ".option" );
			option.off( "click" ).on( "click", function( e ) {
				greyd.input.multiselects.toggleValue( this, e );
			} );

		} );

		// control multiselects and multiradio with keyboard
		document.addEventListener( "keydown", e => {

			const multiselectWrapper = e.target?.closest( ".greyd_multiselect" );
			if ( multiselectWrapper && multiselectWrapper?.querySelector( "input" ) ) {

				if ( multiselectWrapper?.classList?.contains( "open" ) ) {
					if ( e.key == "ArrowUp" || e.key == "ArrowDown" ) {
						e.preventDefault();
						greyd.input.multiselects.focusNextItem( e );
					}
					else if ( e.key == " " && e.target.classList.contains( "option" ) ) {
						const option = e.target;
						e.preventDefault();
						greyd.input.multiselects.toggleValue( option, e );
					}
					else if (
						e.key == "Escape" ||
						e.key == " " && e.target.classList.contains( "input" )
					) {
						e.preventDefault();
						greyd.input.closeAll(null);
					}
					else if ( e.key == "Tab" ) {
						const activeElement = document.activeElement;
						if (
							( e.shiftKey && e.target.classList.contains( "input" ) ) ||
							( !e.shiftKey && !activeElement.nextElementSibling )
						) {
							greyd.input.closeAll(null);
						}
					}
				}
				else {
					if (
						e.key == "Enter" ||
						e.key == " " && e.target.classList.contains( "input" )
					) {
						e.preventDefault();
						multiselectWrapper.classList.add( "open" );
					}
				}
			
			}
			else if ( e.target?.closest( ".greyd_multiradio" ) ) {

				if ( e.key == "ArrowUp" || e.key == "ArrowDown" ) {
					// console.log(e);
					e.preventDefault();
					const activeElement = document.activeElement;
					if ( activeElement.nextElementSibling && e.key == "ArrowDown" ) {
						activeElement.nextElementSibling.focus();
					}
					else if ( activeElement.previousElementSibling && e.key == "ArrowUp" ) {
						activeElement.previousElementSibling.focus();
					}
				}
				else if ( e.key == " " && e.target.classList.contains( "option" ) ) {
					e.preventDefault();
					e.target.onclick();
				}
			}

		} );

	};

	/**
	 * Events
	 */
	this.toggleValue = function( elem, e ) {
		e.stopPropagation();
		elem = $( elem );
		if ( elem.hasClass( 'selected' ) )
			this.removeValue( elem, e );
		else
			this.addValue( elem, e );
	};

	this.removeValue = function( elem, e ) {
		e.stopPropagation();
		elem = $( elem );
		var val = elem.data( 'value' );
		var wrap = elem.closest( '.greyd_multiselect' );

		// reset option
		wrap.find( '.dropdown .option[data-value="' + val + '"]' ).removeClass( 'selected' );

		// remove tag
		wrap.find( '.input .tag[data-value="' + val + '"]' ).remove();

		// remove value
		var field = wrap.find( 'input' );
		var values = field.val().split( ',' );
		values = values.filter( function( v, i ) {
			if ( v != "" && v != val && values.indexOf( v ) == i ) return true;
		} );
		greyd.input.multiselects.setValue( field, values.join( ',' ) );
		// greyd.input.multiselects.setValue( field, field.val().replace(val, "") );
	};

	this.addValue = function( elem, e ) {
		e.stopPropagation();
		elem = $( elem );
		var val = elem.data( 'value' );
		var name = elem.text();
		var wrap = elem.closest( '.greyd_multiselect' );
		var input = wrap.find( '.input' );

		// highlight option
		wrap.find( '.dropdown .option[data-value="' + val + '"]' ).addClass( 'selected' );

		// append tag
		// var tag     = input.data('tag');
		var tag = decodeURIComponent( input.data( 'tag' ) );
		tag = tag.replace( "{value}", val ).replace( "{name}", name );
		input.find( '.tags' ).append( tag );

		// add value
		var field = wrap.find( 'input' );
		var values = field.val().split( ',' );
		values.push( val );
		values = values.filter( function( v, i ) {
			if ( v != "" && values.indexOf( v ) == i ) return true;
		} );
		greyd.input.multiselects.setValue( field, values.join( ',' ) );
		// greyd.input.multiselects.setValue( field, field.val()+","+val );
	};

	this.setValue = function( field, value ) {
		// cleanup double commas or commas at the end or beginning of the string
		// field.val( value.replace(/,+/, ",").replace(/^,|,$/, "") );
		field.val( value );
		field.trigger( "change" );
	};

	this.render = function( select ) {
		var placeholder = '';
		var options = {};
		var values = [];
		var name = select.getAttribute( 'name' );
		var className = select.getAttribute( 'class' );
		var required = select.hasAttribute( 'required' );
		var tag_code = "<span class=\"tag\" data-value=\"{value}\" onclick=\"greyd.input.multiselects.removeValue(this, event);\">{name}<span class=\"icon_close\"></span></span>";

		$( select ).children( 'option' ).each( function() {
			var txt = this.textContent;
			var val = this.hasAttribute( 'value' ) ? this.getAttribute( 'value' ) : '';
			if ( val == '' && placeholder == '' ) {
				placeholder = txt;
			} else {
				val = val == '' ? txt : val;
				options[ val ] = txt;
				if ( this.hasAttribute( 'selected' ) )
					values.push( val );
			}
		} );

		// create wrapper
		var wrap = document.createElement( "div" );
		wrap.setAttribute( "class", "greyd_multiselect" );
		select.after( wrap );

		// remove original select
		select.parentNode.removeChild( select );

		// create input
		var input = document.createElement( "input" );
		input.setAttribute( "type", "text" );
		input.setAttribute( "name", name );
		input.setAttribute( "id", name );
		input.setAttribute( "value", values.join( ',' ) );
		input.setAttribute( "tabindex", -1 );
		if ( required ) input.setAttribute( "required", "required" );
		wrap.appendChild( input );

		// create div.input
		var div = document.createElement( "div" );
		div.setAttribute( "class", "input" + ( className ? ' ' + className : '' ) );

		// make it tabbable
		div.setAttribute( "role", "listbox" );
		div.setAttribute( "tabindex", 0 );
		div.setAttribute( "aria-multiselectable", "true" );
		div.setAttribute( "data-tag", encodeURIComponent( tag_code ) );
		wrap.appendChild( div );

		// create tags
		var tags = document.createElement( "span" );
		tags.setAttribute( "class", "tags" );
		tags.setAttribute( "data-placeholder", placeholder );
		div.appendChild( tags );
		if ( values.length > 0 ) {
			values.forEach( function( value, i ) {
				var tag = $.parseHTML( tag_code.replace( /{value}/, value ).replace( /{name}/, options[ value ] ) );
				$( tags ).append( tag );
			} );
		}

		// create dropdown
		var dropdown = document.createElement( "div" );
		dropdown.setAttribute( "class", "dropdown" );
		wrap.appendChild( dropdown );
		for ( var val in options ) {

			// create option
			var option = document.createElement( "div" );
			option.setAttribute( "class", "option" + ( values.indexOf( val ) >= 0 ? " selected" : "" ) );
			option.setAttribute( "data-value", val );

			// declare it as listbox child
			option.setAttribute( "role", "option" );
			option.setAttribute( "tabindex", 0 );
			dropdown.appendChild( option );

			var icon = document.createElement( "span" );
			icon.setAttribute( "class", "icn" );
			option.appendChild( icon );

			option.innerHTML += options[ val ];
		}
	};

	/**
	 * Helper function for multiselects to switch between options with ArrowUp & Arrow Down
	 * @param {event}
	 */
	this.focusNextItem = ( e ) => {
		const key = e.key;
		const target = e.target;
		const activeElement = document.activeElement;

		const selectWrapper = target.closest( ".greyd_multiselect" );
		const selectGroup = selectWrapper.querySelector( ".dropdown" );
		const items = selectGroup.children;

		if ( activeElement.classList.contains( "input" ) && selectWrapper.classList.contains( "open" ) && key == "ArrowDown" ) {
			items[ 0 ].focus();
		} else if ( activeElement.nextSibling && key == "ArrowDown" ) {
			activeElement.nextSibling.focus();
		} else if ( activeElement.previousSibling && key == "ArrowUp" ) {
			activeElement.previousSibling.focus();
		} else return;
	};
	
	/**
	 * close all multiselect boxes in the document
	 */
	this.closeAll = function() {
		var elements = document.getElementsByClassName("greyd_multiselect");
		for (var i = 0; i < elements.length; i++) {
			elements[i].classList.remove("open");
		}
	}
};

/**
 * normal Select
 */
greyd.input.selects = new function() {
	
	/**
	 * Custom selects
	 */
	this.init = function() {
		var x, i, j, selElmnt, a, b, c;
		// look for any elements with the class "custom-select":
		x = document.getElementsByClassName("custom-select");
		// console.log(x);
		// x = document.getElementsByTagName("select");
		for (i = 0; i < x.length; i++) {
			// console.log(x[i]);
			selElmnt = x[i].getElementsByTagName("select")[0];
			if (typeof selElmnt === 'undefined') continue;
			var t = x[i].getElementsByClassName("select-selected")[0];
			if (typeof t !== 'undefined') continue;
			// selElmnt = x[i];
			// for each element, create a new DIV that will act as the selected item:
			a = document.createElement("DIV");
			a.setAttribute("class", "select-selected");
			if (selElmnt.hasAttribute("style")) a.setAttribute("style", selElmnt.getAttribute("style"));
			if (selElmnt.hasAttribute("title")) a.setAttribute("title", selElmnt.getAttribute("title"));
			a.setAttribute("onmouseover", selElmnt.getAttribute("onmouseover"));
			a.setAttribute("onmouseout", selElmnt.getAttribute("onmouseout"));
			a.innerHTML = "<span>" + selElmnt.options[selElmnt.selectedIndex].innerHTML + "</span>";
			// x[i].appendChild(a);
			selElmnt.parentNode.insertBefore(a, selElmnt.nextSibling);
			a.innerHTML += "<span class=\"select-icon\" aria-hidden=\"true\"></span>";
			// x[i].parentElement.appendChild(a);
			// for each element, create a new DIV that will contain the option list:
			b = document.createElement("DIV");
			b.setAttribute("class", "select-items select-hide");
			b.style.marginTop = '-'+selElmnt.style.marginBottom;
			b.style.fontSize = selElmnt.style.fontSize;
			b.style.fontWeight = selElmnt.style.fontWeight;
			b.style.color = selElmnt.style.color;
			b.style.background = selElmnt.style.background;
			b.style.borderRadius = selElmnt.style.borderRadius;
			b.style.borderColor = selElmnt.style.borderColor;
			b.style.borderWidth = selElmnt.style.borderWidth;
			b.style.borderLeftStyle = selElmnt.style.borderLeftStyle;
			b.style.borderRightStyle = selElmnt.style.borderRightStyle;
			b.style.borderTopStyle = selElmnt.style.borderTopStyle;
			b.style.borderBottomStyle = selElmnt.style.borderBottomStyle;

			// for each option in the original select element...
			for (j = 0; j < selElmnt.length; j++) {
				// create a new DIV that will act as an option item:
				c = document.createElement("DIV");
				c.innerHTML = selElmnt.options[j].innerHTML;
				c.dataset.value = selElmnt.options[j].getAttribute('value');

				// custom css and hover
				if (selElmnt.getAttribute("onmouseover") && selElmnt.getAttribute("onmouseout")) {
					c.style.color = selElmnt.style.color;
					c.style.background = selElmnt.style.background;
					c.style.paddingLeft = selElmnt.style.paddingLeft;
					c.style.paddingRight = selElmnt.style.paddingRight;
					c.style.paddingTop = "5px";
					c.style.paddingBottom = "5px";
					// make onmouseover
					var t = selElmnt.getAttribute("onmouseover");
					t = t.split(' !important').join('');
					if (t.indexOf('color: ') > -1) {
						t = t.split('color: ');
						var color = t[1].split(';');
						var jscolor = "$(this).css('color','"+color[0]+"');";
						t = t[1];
					}
					if (t.indexOf('background: ') > -1) {
						t = t.split('background: ');
						var background = t[1].split(';');
						var jsbackground = "$(this).css('background','"+background[0]+"');";
					}
					// c.setAttribute("onmouseover", "$(this).css('color','"+color[0]+"');$(this).css('background','"+background[0]+"');");
					c.setAttribute("onmouseover", jscolor+jsbackground);
					// make onmouseout
					var t = selElmnt.getAttribute("onmouseout");
					t = t.split(' !important').join('');
					if (t.indexOf('color: ') > -1) {
						t = t.split('color: ');
						var color = t[1].split(';');
						var jscolor = "$(this).css('color','"+color[0]+"');";
						t = t[1];
					}
					if (t.indexOf('background: ') > -1) {
						t = t.split('background: ');
						var background = t[1].split(';');
						var jsbackground = "$(this).css('background','"+background[0]+"');";
					}
					// c.setAttribute("onmouseout", "$(this).css('color','"+color[0]+"');$(this).css('background','"+background[0]+"');");
					c.setAttribute("onmouseout", jscolor+jsbackground);
				}

				// when an item is clicked, update the original select box
				c.addEventListener("click", function(e) {
					const select = this.parentNode.parentNode.getElementsByTagName("select")[0];
					const index = [...this.parentElement.children].indexOf( this );
					select.selectedIndex = index;
					select.dispatchEvent(new Event('change'));
					// close the dropdown
					this.parentNode.classList.add("select-hide");
					this.parentNode.previousSibling.classList.remove("select-arrow-active");
				});
				b.appendChild(c);
			}
			selElmnt.parentNode.insertBefore(b, a.nextSibling);

			// when the select box is clicked, close any other select boxes,
			a.addEventListener("click", function(e) {
				// and open/close the current select box:
				e.stopPropagation();
				greyd.input.closeAll(this);
				// this.nextSibling.classList.toggle("select-hide");
				this.classList.toggle("select-arrow-active");
				$(this.nextSibling).toggleClass("select-hide");
			});

			// change the fake select when changing the real select
			selElmnt.addEventListener("change", function(e) {
				const select = this;
				const fakeSelect = select.parentNode.getElementsByClassName('select-selected')[0];
				fakeSelect.innerHTML = "<span>" + select.options[select.selectedIndex].innerHTML + "</span>";
				fakeSelect.innerHTML += "<span class=\"select-icon\" aria-hidden=\"true\"></span>";
				const fakeOptions = select.parentNode.getElementsByClassName('select-items')[0];
				const selectedOptions = fakeOptions.getElementsByClassName("same-as-selected");
				selectedOptions.length && selectedOptions[0].classList.remove("same-as-selected");
				fakeOptions.children[ select.selectedIndex ].classList.add("same-as-selected");
			})
		}
		// if the user clicks anywhere outside the select box,
		// then close all select boxes:
		document.addEventListener("click", greyd.input.closeAll);
	}
	
	/**
	 * close all select boxes in the document
	 * except the current select box
	 */
	this.closeAll = function( current ) {
		var except = [];
		var elements = document.getElementsByClassName("select-items");
		var selected = document.getElementsByClassName("select-selected");
		for ( var i=0; i<selected.length; i++ ) {
			if ( selected[i] == current ) {
				except.push(i);
			}
			else {
				selected[i].classList.remove("select-arrow-active");
			}
		}
		for ( var i=0; i<elements.length; i++ ) {
			if ( except.indexOf(i) ) {
				elements[i].classList.add("select-hide");
			}
		}
	}

};

/**
 * radio buttons & checkboxes
 */
greyd.input.boxes = new function() {

	/**
	 * Custom radio buttons & checkboxes
	 */
	this.init = function() {
		let inputs = document.getElementsByTagName("input");
		for ( var i=0; i<inputs.length; i++ ) {
			// console.log(inputs[i]);
			const input = inputs[i];

			// check if next sibling already is an empty span
			const nextSibling = input.nextSibling;
			if (nextSibling && nextSibling.tagName == "SPAN") continue;

			const isCustomCheckbox = input.type == "checkbox" && input.closest('form.greyd_form') !== null;
			const isCustomRadio = input.type == "radio" && ( input.closest('form.greyd_form') !== null || input.closest('form.greyd-search-form ') !== null );

			if ( isCustomCheckbox || isCustomRadio ) {
				// console.log(input);
				var lbl = document.createElement("span");
				if ( input.hasAttribute("title") ) {
					lbl.setAttribute("title", input.getAttribute("title"));
				}
				input.parentNode.insertBefore( lbl, input.nextSibling );
			}
		}
	}

};
