/**
 * Headless admin script.
 * 
 * @since 1.6.7
 */
document.addEventListener( "DOMContentLoaded", function () {
	
	greyd.headless.init()

	console.log('Headless Backend Scripts: loaded');

} );

greyd.headless = new function() {
	
	this.terminal = new function() {
		
		this.loader = document.getElementById('apiLoader');
		this.console = document.getElementById('apiResult');

		this.showLoader = function() {
			this.clear();
			this.loader.classList.remove('hidden');
		}
		this.hideoader = function() {
			this.loader.classList.add('hidden');
		}

		this.clear = function() {
			this.console.innerText = "";
		}
		this.log = function( msg, mode='string' ) {
			if (mode == 'json') {
				this.console.insertAdjacentText( 'beforeend', JSON.stringify(msg, null, 2) );
			}
			else if (mode == 'xml') {
				this.console.insertAdjacentText( 'beforeend', msg );
			}
			else if (mode == 'html') {
				var src = 'data:text/html;charset=utf-8,'+encodeURIComponent(msg.split('"').join("'"));
				this.console.insertAdjacentHTML( 'beforeend', '<iframe src="'+src+'"></iframe>' );
			}
			else {
				this.console.insertAdjacentHTML( 'beforeend', msg );
			}
			this.console.insertAdjacentHTML( 'beforeend', '<br><br>' );
		}
		this.success = function( msg ) {
			this.log( "<span style='color:lightgreen'>"+msg+"</span>" );
		}
		this.warn = function( msg ) {
			this.log( "<span style='color:orange'>"+msg+"</span>" );
		}
		this.error = function( msg ) {
			this.log( "<span style='color:red'>"+msg+"</span>" );
		}
	}

	this.init = function() {

		if ( !document.querySelector('.headless_wrap') ) return;

		this.initSplitview();

		this.initDrag();

		document.querySelectorAll('.api_route')?.forEach( (api) => this.initApi(api) );

		document.querySelector('.api_route .api_add')?.addEventListener( 'click', ( e ) => {
			var url = e.target.closest('.api_route').querySelector('.api_url').value;
			if ( url == "" ) {
				alert( 'Please enter URL' );
				return;
			}
			try {
				url = new URL(url);
				// console.log(url);
			} 
			catch (error) {
				alert('Not a valid URL');
				return;
			}
			var dummy = document.querySelector('.api_dummy');
			var sortable = document.querySelector('.api_sortable');
			if ( dummy && sortable ) {
				var html = "<div class='api_route new' draggable='true'>"+dummy.innerHTML+"</div>";
				sortable.insertAdjacentHTML( 'beforeend', html );

				var newapi = document.querySelector('.api_route.new');
				newapi.classList.remove('new');
				newapi.querySelector('.api_title').value = url.hostname;
				newapi.querySelector('.api_base_url').value = url.origin;
				newapi.querySelector('.api_url_path').value = url.pathname.substring(1);
				if (url.searchParams.size > 0) {
					var items = newapi.querySelector('.api_items');
					for ( var [ key, value ] of url.searchParams ) {
						// console.log(key, value);
						var dummy = document.querySelector('.api_route_attribute_dummy');
						var html = "<div class='api_item api_route_attribute new'>"+dummy.innerHTML+"</div>";
						var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
						items.insertBefore( node, items.lastElementChild );
		
						var newitem = document.querySelector('.api_item.new');
						newitem.classList.remove('new');
						newitem.querySelector('.api_item_key').value = key;
						newitem.querySelector('.api_item_value').value = value;
					}
				}

				this.set(newapi.querySelector('.api_url'), newapi);
				this.toggle(newapi, '.api_route_edit');
				this.initApi(newapi);

				this.initDrag();
			}
		} );

	}

	this.initApi = function(api) {

		api.querySelectorAll('.api_edit')?.forEach( (btn) => btn.onclick = ( e ) => this.toggle(api, '.api_route_edit') );
		api.querySelectorAll('.api_routes')?.forEach( (btn) => btn.onclick = ( e ) => this.toggle(api, '.api_route_routes') );
		api.querySelectorAll('.api_setup')?.forEach( (btn) => btn.onclick = ( e ) => this.toggle(api, '.api_route_setup') );
		api.querySelectorAll('.api_delete')?.forEach( (btn) => btn.onclick = ( e ) => this.delete(api) );

		// routes
		api.querySelectorAll('.api_route_add')?.forEach( (btn) => btn.onclick = ( e ) => {
			var dummy = document.querySelector('.api_route_dummy');
			if ( dummy ) {
				var html = "<div class='api_body new' data-route=''>"+dummy.innerHTML+"</div>";
				var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
				var items = e.target.closest('.api_route_routes');
				items.insertBefore( node, items.lastElementChild );

				var apibase = api.querySelector('.api_route_edit');
				var newroute = document.querySelector('.api_body.new');
				newroute.classList.remove('new');
				newroute.querySelector('.api_title').value = apibase.querySelector('.api_title').value+" New Route";
				newroute.querySelector('.api_base_url').value = apibase.querySelector('.api_base_url').value;
				apibase.querySelectorAll('.api_route_attribute')?.forEach( (att) => {
					var html = "<div class='api_item new api_route_attribute'>"+att.innerHTML+"</div>";
					var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
					var items = newroute.querySelector('.api_items.api_route_attributes');
					items.insertBefore( node, items.lastElementChild );
	
					var newitem = document.querySelector('.api_item.new');
					newitem.classList.remove('new');
				} );
				apibase.querySelectorAll('.api_route_header')?.forEach( (att) => {
					var html = "<div class='api_item new api_route_header'>"+att.innerHTML+"</div>";
					var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
					var items = newroute.querySelector('.api_items.api_route_headers');
					items.insertBefore( node, items.lastElementChild );
	
					var newitem = document.querySelector('.api_item.new');
					newitem.classList.remove('new');
				} );

				this.set(newroute, api);
				this.toggleRoute(newroute.querySelector('.api_edit_route'));
				this.initApi(api);
				this.initDrag();
			}
		} );
		api.querySelectorAll('.api_route_delete')?.forEach( (btn) => btn.onclick = ( e ) => {
			var route = e.target.closest('.api_body');
			var title = route.querySelector('.api_title').value;
			if ( confirm( 'Delete API Route "'+title+'"?' ) ) {
				route.remove();
				var slug = route.querySelector('.api_slug').value;
				api.querySelectorAll('.api_route_setup > .api_body').forEach( (block) => {
					block.querySelectorAll('.api_block_route option').forEach( (option) => {
						if ( option.value == slug ) {
							option.remove();
						}
					} );
				} );
			}
		} );

		// blocks
		api.querySelectorAll('.api_block_add')?.forEach( (btn) => btn.onclick = ( e ) => {
			var dummy = document.querySelector('.api_block_dummy');
			if ( dummy ) {
				var html = "<div class='api_body new'>"+dummy.innerHTML+"</div>";
				var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
				var items = e.target.closest('.api_route_setup');
				items.insertBefore( node, items.lastElementChild );

				var newblock = document.querySelector('.api_body.new');
				newblock.classList.remove('new');
				var title = api.querySelector('.api_route_edit .api_title').value;
				var slug = api.querySelector('.api_route_edit .api_slug').value;
				var select = newblock.querySelector('.api_block_route');
				select.innerHTML += "<option value='"+slug+"'>"+title+"</option>";
				api.querySelectorAll('.api_route_routes > .api_body').forEach( (route) => {
					var title = route.querySelector('.api_title').value;
					var slug = route.querySelector('.api_slug').value;
					select.innerHTML += "<option value='"+slug+"'>"+title+"</option>";
				} );


				this.setBlock(newblock.querySelector('.api_block_route'));
				this.toggleRoute(newblock.querySelector('.api_edit_block'));
				this.initApi(api);
				this.initDrag();
			}
		} );
		api.querySelectorAll('.api_block_delete')?.forEach( (btn) => btn.onclick = ( e ) => {
			var block = e.target.closest('.api_body');
			var slug = block.querySelector('.api_block_route').value;
			var title = slug;
			block.querySelectorAll('.api_block_route option').forEach( (option) => {
				if ( option.value == slug ) title = option.innerHTML;
			} );
			if ( confirm( 'Delete API Block "'+title+'"?' ) ) {
				block.remove();
			}
		} );

		api.querySelectorAll('.api_edit_route, .api_edit_block')?.forEach( (btn) => btn.onclick = ( e ) => this.toggleRoute(e.target) );
		api.querySelectorAll('.api_input_block')?.forEach( (input) => input.oninput = ( e ) => this.setBlock(e.target) );

		api.querySelectorAll('.api_input')?.forEach( (input) => input.oninput = ( e ) => this.set(e.target, api) );
		api.querySelectorAll('.api_call')?.forEach( (input) => input.onclick = ( e ) => this.call(e.target, api) );

		this.initApiItems(api);

	}

	this.initApiItems = function(api) {

		// add
		api.querySelectorAll('.api_item_add')?.forEach( (add) => {
			add.onclick = ( e ) => {
				var dummyClass = e.target.dataset.dummy;
				var dummy = document.querySelector('.'+dummyClass);
				if ( dummy ) {
					var html = "<div class='api_item new "+dummyClass.replace('_dummy', '')+"'>"+dummy.innerHTML+"</div>";
					var node = new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];
					var items = e.target.closest('.api_items');
					items.insertBefore( node, items.lastElementChild );
	
					var newitem = document.querySelector('.api_item.new');
					newitem.classList.remove('new');
	
					this.initApi(api);
					this.initDrag();
				}
			};
		} );
		
		// change
		api.querySelectorAll('.api_item_key, .api_item_value')?.forEach( (input) => {
			input.oninput = ( e ) => this.set(e.target, api);
		} );

		// delete
		api.querySelectorAll('.api_item_delete')?.forEach( (del) => {
			del.onclick = ( e ) => {
				var element = e.target.closest('.api_items');
				var type = del.parentNode.querySelector('.api_item_key') ? 'api' : 'block';
				if (type == 'api') {
					var key = del.parentNode.querySelector('.api_item_key')?.value;
					var value = del.parentNode.querySelector('.api_item_value')?.value;
				}
				else {
					if (del.parentNode.querySelector('.api_var_key'))
						var key = del.parentNode.querySelector('.api_var_key')?.value;
					else
						var key = del.parentNode.querySelector('.api_action_key')?.value;
					var value = "";
				}
				if ( 
					(key == "" && value == "") || 
					confirm( 'Delete Item "'+key+'"?' ) 
				) {
					del.parentNode.remove();
					if (type == 'api') this.set(element, api);
					if (type == 'block') this.setBlock(element, api);
				}
			};
		} );

		// move
		api.querySelectorAll('.api_action_up')?.forEach( (btn) => btn.onclick = ( e ) => {
			var action = e.target.closest('.api_item');
			if ( action.previousElementSibling === null ) return;
			// console.log("move up");
			// console.log(action);
			action.parentNode.insertBefore( action, action.previousElementSibling );
		} );
		api.querySelectorAll('.api_action_down')?.forEach( (btn) => btn.onclick = ( e ) => {
			var action = e.target.closest('.api_item');
			if ( !action.nextElementSibling?.nextElementSibling ) return;
			// console.log("move down");
			// console.log(action);
			action.parentNode.insertBefore( action, action.nextElementSibling.nextElementSibling );
		} );

	}

	this.initDrag = function() {

		var sortables = document.querySelectorAll('.api_sortable .api_route');
		if ( sortables && sortables.length > 0 ) {
			sortables.forEach( (api) => {
				
				api.querySelectorAll('input').forEach( (input) => {
					input.addEventListener( 'mouseenter', ( e ) => api.draggable = false );
					input.addEventListener( 'mouseleave', ( e ) => api.draggable = true );
				} );

				api.ondragstart = ( e ) => {
					current = api;
					api.classList.add("current");
					api.closest('.api_sortable').classList.add("drop");
				};
				api.ondragenter = ( e ) => {
					if (api != current) api.classList.add("active");
				};
				api.ondragleave = () => api.classList.remove("active");
				api.ondragend = () => {
					api.closest('.api_sortable').classList.remove("drop");
					sortables.forEach( (sortable, i) => {
						sortable.classList.remove("active");
						sortable.classList.remove("current");
					} );
				};

				api.ondragover = ( e ) => e.preventDefault();
				api.ondrop = ( e ) => {
					e.preventDefault();
					if (api != current) {
						var currentpos = 0, droppedpos = 0;
						sortables.forEach( (sortable, i) => {
							if (current == sortable) currentpos = i;
							if (api == sortable) droppedpos = i;
						} );
						if (currentpos < droppedpos)
							api.parentNode.insertBefore(current, api.nextSibling);
						else
							api.parentNode.insertBefore(current, api);
						sortables = document.querySelectorAll('.api_sortable .api_route');
						current = null;
					}
				};

			} );
		}

	}

	this.initSplitview = function() {

		var split = document.querySelector('.headless_wrap .split');
		if ( split ) {
			var parent = split.parentElement.getBoundingClientRect();
			var panel = split.previousElementSibling;
			var down = false;
			split.addEventListener('mousedown', ( e ) => {
				down = true;
				document.body.style.userSelect = 'none';
				document.body.style.cursor = 'col-resize';
				document.body.addEventListener('mousemove', move);
				document.body.addEventListener('mouseup', end);
			} );
			var move = (e) => {
				if (down) {
					// console.log(e);
					var style = "1 0 "+(e.clientX - parent.left)+"px";
					panel.style.flex = style;
				}
				else end();
			}
			var end = (e) => {
				down = false;
				document.body.style.userSelect = 'initial';
				document.body.style.cursor = 'default';
				document.body.removeEventListener('mousemove', move);
				document.body.removeEventListener('mouseup', end);
			}
		}

	}

	this.toggleRoute = function(element) {
		if ( element.classList.contains('active') ) {
			element.closest('.api_body').querySelectorAll('.api_body_item').forEach( (item) => item.classList.add('hidden') );
			element.classList.remove('active');
		}
		else {
			element.closest('.api_body').querySelectorAll('.api_body_item').forEach( (item) => item.classList.remove('hidden') );
			element.classList.add('active');
		}
	}

	this.toggle = function(api, element) {
		if (api.querySelector(element)?.classList.contains('hidden')) {
			api.querySelector('.api_buttons')?.classList.remove('hidden');
			api.querySelector(element.replace('_route_', '_'))?.classList.add('active');
			api.querySelector(element)?.classList.remove('hidden');
		}
		else {
			api.querySelector(element)?.classList.add('hidden');
			api.querySelector(element.replace('_route_', '_'))?.classList.remove('active');
			var close = true;
			api.querySelectorAll('.api_body:not('+element+')')?.forEach( (item) => { if (!item.classList.contains('hidden')) close = false } );
			if (close) api.querySelector('.api_buttons')?.classList.add('hidden');
		}
	}

	this.delete = function(api) {
		var title = api.querySelector('.api_title').value;
		if ( confirm( 'Delete API "'+title+'"?' ) ) {
			api.remove();
			this.initDrag();
		}
	}

	this.setBlock = function(element) {

		var api = element.closest('.api_body');

		var title = api.querySelector('.api_block_route');
		// console.log(title);
		// title = title.selectedOptions[0].innerHTML;

		api.querySelector('.api_headline').innerHTML = title.selectedOptions[0].innerText;

	}

	this.set = function(element, api) {

		api = element.closest('.api_body');

		const slugify = function(text) {
			return text
				.toString()                   // Cast to string (optional)
				.normalize('NFKD')            // The normalize() using NFKD method returns the Unicode Normalization Form of a given string.
				.toLowerCase()                // Convert the string to lowercase letters
				.trim()                       // Remove whitespace from both sides of a string (optional)
				.replace(/\s+/g, '-')         // Replace spaces with -
				.replace(/[^\w\-]+/g, '')     // Remove all non-word chars
				.replace(/\_/g,'-')           // Replace _ with -
				.replace(/\-\-+/g, '-')       // Replace multiple - with single -
				.replace(/\-$/g, '');         // Remove trailing -
		}

		var api_title = api.querySelector('.api_title');
		// api_title.value = api_title.value.trim();
		var api_slug = slugify(api_title.value);
		var api_slug_old = api.querySelector('.api_slug').value;
		if ( api_slug_old != api_slug) {
			api.querySelector('.api_slug').value = api_slug;
			if ( 'route' in api.dataset ) {
				api.dataset.route = api_slug;
				api.closest('.api_route').querySelectorAll('.api_route_setup > .api_body').forEach( (block) => {
					var found = false;
					if ( api_slug_old != "" ) block.querySelectorAll('.api_block_route option').forEach( (option) => {
						if ( option.value == api_slug_old ) {
							option.value = api_slug;
							option.innerHTML = api_title.value;
							found = true;
						}
					} );
					if ( !found ) {
						block.querySelector('.api_block_route').innerHTML += "<option value='"+api_slug+"'>"+api_title.value+"</option>";
					}
				} );
			}
		}
		if ( api.querySelector('.api_headline') ) {
			api.querySelector('.api_headline').innerHTML = api_title.value;
		}
		if ( api.classList.contains('api_route_edit') ) {
			api.closest('.api_route').querySelector('.api_headline').innerHTML = api_title.value;
		}

		var base_url = api.querySelector('.api_base_url');
		var url_path = api.querySelector('.api_url_path');
		var url_atts = [];
		api.querySelectorAll('.api_route_attribute')?.forEach( (att) => {
			var key = att.querySelector('.api_item_key')?.value ?? "";
			var value = att.querySelector('.api_item_value')?.value ?? "";
			if (key != "") {
				url_atts.push(key+"="+value);
			}
		} );
		
		var url = base_url.value;
		if (url_path.value != "") url += "/"+url_path.value;
		if (url_atts.length > 0) url += "?"+url_atts.join("&");

		api.querySelector('.api_url').value = url;

	}

	this.get = function(api) {

		var api_title = api.querySelector('.api_title');
		var api_slug = api.querySelector('.api_slug');
		var slug = api_slug?.value;
		if (slug && !api.classList.contains('api_route_edit')) {
			var api_slug_parent = api.closest('.api_route')?.querySelector('.api_route_edit .api_slug')?.value;
			if (api_slug_parent) slug = api_slug_parent+'/'+slug;
		}
		var base_url = api.querySelector('.api_base_url');
		var url_path = api.querySelector('.api_url_path');
		var method = api.querySelector('.api_method');
		var url_atts = {};
		api.querySelectorAll('.api_route_attribute')?.forEach( (att) => {
			var key = att.querySelector('.api_item_key')?.value ?? "";
			var value = att.querySelector('.api_item_value')?.value ?? "";
			if (key != "") {
				url_atts[key] = value;
			}
		} );
		var headers = {};
		api.querySelectorAll('.api_route_header')?.forEach( (header) => {
			var key = header.querySelector('.api_item_key')?.value ?? "";
			var value = header.querySelector('.api_item_value')?.value ?? "";
			if (key != "" && value != "") {
				headers[key] = value;
			}
		} );
		var url = api.querySelector('.api_url');

		return {
			title: api_title?.value,
			slug: slug,
			base_url: base_url?.value,
			url_path: url_path?.value,
			url_atts: url_atts,
			headers: headers,
			url: url?.value,
			public: url?.value.indexOf(greyd.rest_base) !== 0,
			method: method?.value,
		}
		
	}

	this.call = function(element, api) {

		if ( this.api._busy ) {
			this.terminal.warn('Terminal is busy ...');
			return;
		}

		api = element.closest('.api_route');

		// console.log(element);
		var endpoint = null;
		if ( 'block' in element.dataset && element.dataset.block === "true" ) {
			// get block route
			// console.log("get block route");
			// console.log(element);
			// console.log(api);

			var route_slug = element.closest('.api_body').querySelector('.api_block_route').value;
			if ( route_slug == "" ) {
				this.terminal.clear();
				this.terminal.warn('Please select API Route');
				this.terminal.log( "> " );
				return;
			}
			// console.log("get route: "+route_slug);
			var route = api.querySelector('.api_body[data-route='+route_slug+']');
			if ( !route ) {
				this.terminal.clear();
				this.terminal.warn('API Route "'+route_slug+'" not found');
				this.terminal.log( "> " );
				return;
			}
			// console.log(route);
			endpoint = this.get(route);
			// endpoint.slug = api.querySelector('.api_slug').value+'/'+endpoint.slug
			endpoint.block = {};
			// console.log(endpoint);

			// block data
			var vars = {};
			element.closest('.api_body').querySelectorAll('.api_block_vars .api_item')?.forEach( (variable) => {
				var key = variable.querySelector('.api_var_key')?.value;
				var val = variable.querySelector('.api_var_value')?.value;
				if ( key && val ) vars[key] = val;
			} );
			if ( JSON.stringify(vars) != '{}' ) endpoint.vars = vars;

			var route_prop = element.closest('.api_body').querySelector('.api_block_prop').value;
			endpoint.block.data_prop = route_prop;

			var items = {};
			element.closest('.api_body').querySelectorAll('.api_block_data_item')?.forEach( (item) => {
				var key = item.querySelector('.api_var_key')?.value;
				var actions = [];
				item.querySelectorAll('.api_block_data_item_action')?.forEach( (action) => {
					var actionItem = {
						action: action.querySelector('.api_action_key')?.value,
						value: action.querySelector('.api_action_value')?.value,
					}
					try { actionItem.value = JSON.parse(actionItem.value); }
					catch {}
					actions.push( actionItem );
				} );
				var val = {
					title: item.querySelector('.api_var_value[data-key=title]')?.value,
					description: item.querySelector('.api_var_value[data-key=description]')?.value,
					prop: item.querySelector('.api_var_value[data-key=prop]')?.value,
					actions: actions,
					type: item.querySelector('.api_var_value[data-key=type]')?.value,
				};
				if ( key && val ) items[key] = val;
			} );
			if ( JSON.stringify(items) != '{}' ) endpoint.block.data_item = items;

			// console.log(endpoint);
		}
		else if ( 'posttype' in element.dataset && element.dataset.posttype === "true" ) {
			// get posttype route
			// console.log("get posttype route");
			// console.log(element);
			// console.log(api);
			
			var posttypeJson = element.closest('.api_body').dataset.posttypeJson;
			posttypeJson = JSON.parse(posttypeJson);
			// console.log(posttypeJson);

			var route_slug = posttypeJson?.api_settings?.route;
			if ( !route_slug || route_slug == "" ) {
				this.terminal.clear();
				this.terminal.warn('Please select API Route');
				this.terminal.log( "> " );
				return;
			}
			// console.log("get route: "+route_slug);
			var route = api.querySelector('.api_body[data-route='+route_slug+']');
			if ( !route ) {
				this.terminal.clear();
				this.terminal.warn('API Route "'+route_slug+'" not found');
				this.terminal.log( "> " );
				return;
			}
			// console.log(route);
			endpoint = this.get(route);
			endpoint.posttype = {
				data_prop: posttypeJson?.api_settings?.data_prop,
				data_item: posttypeJson?.api_settings?.data_item
			};

			// console.log(endpoint);
		}
		if ( endpoint === null ) {
			if ( element.closest('.api_body') ) {
				// get route endpoint
				endpoint = this.get(element.closest('.api_body'));
			}
			else if ( api.querySelector('.api_route_edit') ) {
				// get base endpoint
				// endpoint = this.get(api);
				endpoint = this.get(api.querySelector('.api_route_edit'));
			}
			if ( endpoint === null || !endpoint.url || endpoint.url == "" ) {
				// get from url input
				endpoint = this.get(api.querySelector('.api_route_input'));
			}
		}

		if ( endpoint === null || !endpoint.url || endpoint.url == "" ) {
			// console.log(endpoint);
			this.terminal.clear();
			this.terminal.warn('Please enter URL');
			this.terminal.log( "> " );
			return;
		}
		try {
			var test = new URL(endpoint.url);
			// console.log(test);
		} 
		catch (error) {
			this.terminal.clear();
			this.terminal.warn('Not a valid URL');
			this.terminal.log( "> " );
			return;
		}

		var abort = false;
		var matches = endpoint.url.match(/{[a-z-_0-9]*}/g);
		// console.log(matches);
		if ( matches && matches.length > 0 ) {
			var vars = endpoint.vars?? {};
			matches.forEach( (match) => {
				if ( vars[match] ) return;
				var value = prompt( "Enter value for "+match );
				if (value) {
					vars[match] = value;
				}
				else abort = true;
			} );
			endpoint.vars = vars;
		}
		if ( abort ) {
			this.terminal.clear();
			this.terminal.warn('No value given - aborted');
			this.terminal.log( "> " );
			return;
		}

		// var url = "https://ma.tt/index.php?rest_route=/";
		// var url = greyd.rest_base;
		// var url = input.value;
		// if ( url.indexOf('://') == -1 ) {
		// 	url = 'https://'+url;
		// 	api_url.value = url;
		// }

		console.log("fetching", endpoint);

		api.querySelector('.api_call').disabled = true;
		this.terminal.showLoader();
		this.terminal.log( "> "+endpoint.url );

		this.api.ajax( endpoint )
		.then( (result) => {
			// console.log(result);
			this.terminal.success( 'Result Type: '+result.type );
			if (result.type == 'json') {
				// json
				if (typeof result.json.routes !== 'undefined') result.json.routes = Object.keys(result.json.routes); // {};
				if (typeof result.json._links !== 'undefined') result.json._links = undefined;
				if (typeof result.json.authentication !== 'undefined') result.json.authentication = undefined;
				this.terminal.log( result.json, 'json' );
			}
			else {
				// other
				this.terminal.log( result.data, result.type );
			}
		} )
		.catch( (error) => {
			console.warn(error);
			this.terminal.warn( error );
		} )
		.finally( () => {
			api.querySelector('.api_call').disabled = false;
			this.terminal.hideoader();
		} );

	}


	this.api = new function() {

		this._busy = false;
		this._start = function() {
			this._busy = true;
		}
		this._finish = function() {
			this._busy = false;
		}

		this.ajax = async function( endpoint ) {
			
			if (this._busy) {
				return Promise.reject( 'Terminal is busy ...' )
			}
			this._start();

			var data = new FormData();
			data.append( '_ajax_nonce', greyd.nonce );
			data.append( 'action', 'greyd_admin_ajax' );
			data.append( 'mode', 'fetch_api' );
			data.append( 'data', encodeURI(JSON.stringify( endpoint ) ) );

			return fetch( greyd.ajax_url, { 
				method: 'POST',
				body: data,
			} )
			.then( (response) => {
				// console.log(response);
				// get text response
				return response.text();
			} )
			.then( (text) => {
				// console.log(text);
				// handle success/error
				if (text.indexOf('success::') > -1) {
					// valid success
					text = text.split('success::', 2)[1];
					return decodeURIComponent(text);
				}
				else if (text.indexOf('error::') > -1) {
					// valid error
					text = text.split('error::', 2)[1];
					return Promise.reject( text );
				}
				return Promise.reject( 'response not valid' );
			} )
			.then( (data) => {
				// console.log(data);
				// check for json/xml/string response
				try {
					//try to parse via json 
					var json = JSON.parse(data);
					return { type: 'json', data: data, json: json };
				} 
				catch(e) {
					//try xml parsing 
					var parser = new DOMParser;
					var xml = parser.parseFromString(data, "application/xml");
					if (xml.documentElement.nodeName == "parsererror") {
						var html = parser.parseFromString(data, "text/html");
						if (Array.from(html.body.childNodes).some(node => node.nodeType === 1)) {
							// is html
							return { type: 'html', data: data, html: html };
						}
						else {
							// is string/text
							return { type: 'string', data: data };
						}
					}
					else {
						// is xml
						return { type: 'xml', data: data, xml: xml };
					}
				}
			} )
			.finally( () => this._finish() );

		}

		this.get = function( endpoint ) {
			if (this._busy) {
				return Promise.reject( 'api busy' )
			}
			this._start();
			return window.fetch( endpoint, {
				method: 'GET',
				headers: { 'Accept': 'application/json' }
			} )
			.then( this._handleResponse )
			.finally( () => this._finish() );
		}

		this.post = function( endpoint, body ) {
			if (this._busy) {
				return Promise.reject( 'api busy' )
			}
			this._start();
			return window.fetch( endpoint, {
				method: 'POST',
				headers: { 'content-type': 'application/json' },
				body: JSON.stringify( body ),
			} )
			.then( this._handleResponse )
			.finally( () => this._finish() );
		}

		this._handleResponse = function(result) {
			// console.log(result);
			if ( !result.ok ) {
				return Promise.reject( result.statusText );
			}
			var contentType = result.headers.get( 'content-type' );
			if ( contentType && contentType.includes( 'application/json' ) ) {
				return result.json()
			}
			return Promise.reject( 'no JSON' );
		}

	}

	/**
	 * Check if page is dirty
	 */
	this.dirty = false;
	window.onbeforeunload = function() {
		// console.log("check inputs");
		if ( greyd.headless.dirty ) {
			return false;
		}
	};

}