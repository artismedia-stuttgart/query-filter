/**
 * Helper functions & utils for trigger features in block editor.
 */
var greyd = greyd || {};

greyd.trigger = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	// trigger event controls
	this.triggerEventControls = function(props) {
		
		var makeActions = function(props) {
			var controls = [];
			if (_.has(props.attributes, 'trigger_event') && _.has(props.attributes.trigger_event, 'actions')) {
				for (var i=0; i<props.attributes.trigger_event?.actions.length; i++) {
					var action = props.attributes.trigger_event?.actions[i];
					controls.push(
						el( 'div', {
							className: 'components-greyd-controlgroup__item',
							'data-index': i,
						}, [
							// remove
							el( wp.components.Button, { 
								className: "components-greyd-controlgroup__remove",
								onClick: (event) => {
									var index = parseInt(event.target.closest('.components-greyd-controlgroup__item').dataset.index);
									// console.log("remove action "+index);
									var actions = props.attributes.trigger_event?.actions;
									actions.splice(index, 1);
									props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, actions: actions } } ); 
								},
								title: __("Delete action", 'greyd_hub')
							}, el( wp.components.Icon, { icon: 'no-alt' } ) ),
							// name
							el( 'div', {}, __("Name", 'greyd_hub') ),
							el( 'input', {
								className: 'components-text-control__input components-base-control',
								placeholder: __("Trigger name", 'greyd_hub'),
								// help: __("Gebe deinem Trigger einen eindeutigen Namen zur Referenzierung im Trigger-Picker.", 'greyd_hub'),
								value: action.name,
								onChange: function(event) { 
									var index = parseInt(event.target.closest('.components-greyd-controlgroup__item').dataset.index);
									var val = event.target.value;
									// console.log("change action "+index+" name: "+val);
									var actions = props.attributes.trigger_event?.actions;
									actions[index].name = val;
									props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, actions: actions } } ); 
								},
							} ),
							// action
							// el( 'div', { style: { marginTop: '10px' }}, __("Action", 'greyd_hub') ),
							el( greyd.components.OptionsControl, {
								label: __("Action", 'greyd_hub'),
								// help: __("Was soll beim Auslösen des Triggers mit diesem Block geschehen?", 'greyd_hub'),
								// className: 'components-select-control__input',
								value: action.action,
								onChange: function(value, event) { 
									var index = parseInt(event.target.closest('.components-greyd-controlgroup__item').dataset.index);
									// console.log("change action "+index+": "+val);
									var actions = props.attributes.trigger_event?.actions;
									actions[index].action = value;
									props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, actions: actions } } ); 
								},
								options: [
									{ value: 'show', label: __("Show", 'greyd_hub') },
									{ value: 'hide', label: __("Hide", 'greyd_hub') },
									{ value: 'toggle', label: __("Hide and show", 'greyd_hub') },
									{ value: 'fadeIn', label: __("Fade in", 'greyd_hub') },
									{ value: 'fadeOut', label: __("Fade out", 'greyd_hub') },
									{ value: 'fadeToggle', label: __("Fade in and out", 'greyd_hub') },
									{ value: 'slideDown', label: __("Open", 'greyd_hub') },
									{ value: 'slideUp', label: __("Close", 'greyd_hub') },
									{ value: 'slideToggle', label: __("Open and close", 'greyd_hub') }
								]
							} ),
						] )
					);
				}
			}
			return controls;
		}

		return el( greyd.components.AdvancedPanelBody, { 
			title: __("Actions", 'greyd_hub'), 
			initialOpen: false, 
			holdsChange: (_.has(props.attributes, 'trigger_event')) && !_.isEmpty(props.attributes.trigger_event?.actions) 
		}, [
			// actions
			el( 'div', { className: 'components-greyd-controlgroup'}, [
				makeActions(props),
				el( wp.components.Button, {
					className: 'components-greyd-controlgroup__add'+( !_.has(props.attributes.trigger_event, 'actions') || props.attributes.trigger_event?.actions.length === 0 ? ' group_is_empty': '' ),
					onClick: function() {
						// console.log('adding parameter');
						var value = [];
						var names = [];
						if (_.has(props.attributes, 'trigger_event') && _.has(props.attributes.trigger_event, 'actions'))
							value = props.attributes?.trigger_event?.actions;
						for (var i=0; i<value.length; i++) names.push(value[i].name);
						var name = 'Trigger '+(value.length+1);
						while (names.indexOf(name) > -1) {
							name += '1';
						}
						value.push({ name: name, action: 'show' });
						props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, actions: value } } ); 
					},
					title: __("Add action", 'greyd_hub')
				}, [
					el( wp.components.Icon, { icon: 'plus-alt2' } ),
					!_.has(props.attributes.trigger_event, 'actions') || props.attributes.trigger_event?.actions.length === 0 ? el( 'span', {}, __("Add action", 'greyd_hub') ) : null
				] )
			] ),

			// onload
			// el( 'span', {}, __("State on page load", 'greyd_hub') ),
			el( greyd.components.ButtonGroupControl, {
				value: (_.has(props.attributes, 'trigger_event')) && props.attributes.trigger_event?.onload === 'hide' ? 'hide' : '',
				label: __("State on page load", 'greyd_hub'),
				options: [
					{ label: __("Normal", "greyd_hub"), value: "" },
					{ label: __("Hidden", "greyd_hub"), value: "hide" }
				],
				style: { marginBottom: '10px' },
				onChange: function(value) { 
					props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, onload: value } } ); 
				},
			} ),
			// siblings todo
			el( wp.components.ToggleControl, {
				label: __("Hide adjacent trigger elements", 'greyd_hub'),
				// help: __("Wenn ausgewählt, werden alle benachbarten Trigger-Elemente (z.B. in der gleichen Spalte) ausgeblendet, sobald dieses Element eingeblendet wird.", 'greyd_hub'),
				checked: (_.has(props.attributes, 'trigger_event')) ? props.attributes.trigger_event?.siblings : false,
				onChange: function(value) { 
					props.setAttributes( { trigger_event: { ...props.attributes.trigger_event, siblings: value } } ); 
				},
			} )
		] );

	}

	//
	// trigger picker controls
	this.triggerPickerControlsLogic = function(props) {
		
		// vars
		var tr = { type: "link", params: {} };
		var triggerType = "";
		if (_.has(props.attributes, "trigger") && _.has(props.attributes.trigger, "type")) {
			triggerType = props.attributes.trigger.type;
			// tr = props.attributes.trigger;
			tr.type = triggerType;
			tr.params[triggerType] = props.attributes.trigger.params;
		}
		var triggerTitle = function() {
			// console.log(triggerType);
			for (var i=0; i<types.length; i++) {
				if (types[i].value == triggerType) 
					return types[i].label;
			}
		}

		// states
		var [ active, setActive ] = wp.element.useState(false);
		var [ hover, setHover ] = wp.element.useState(false);
		var [ edit, setEdit ] = wp.element.useState(false);
		var [ trigger, setTrigger ] = wp.element.useState(tr);
		if (!active && !_.isEqual(trigger, tr)) setTrigger(tr);

		// types
		var types = [
			// { value: '', label: __("Select trigger", 'greyd_hub') },
			{ value: 'link',    label: '🔗 ' + __("Link", 'greyd_hub') },
			{ value: 'dynamic', label: '💾 ' + __("Dynamic link", 'greyd_hub') },
			{ value: 'scroll',  label: '↕️ ' + __("Scroll to", 'greyd_hub') },
			{ value: 'popup',   label: '🎉 ' + __("Pop-up", 'greyd_hub') },
			{ value: 'back',    label: '🔙 ' + __("Back", 'greyd_hub') },
			{ value: 'email',   label: '📧 ' + __("Email", 'greyd_hub') },
			{ value: 'file',    label: '↓ ' + __("Download", 'greyd_hub') },
			{ value: 'event',   label: '🎯 ' + __("Trigger event", 'greyd_hub') },
		];

		if ( greyd.data.post_type === 'greyd_popup' ) {
			types.push( { value: 'popup_close', label: __("Close pop-up", 'greyd_hub') } );
		}

		// options
		var { blocks, popups, files } = wp.data.useSelect(select => ({
			// all blocks to filter anchors and triger_events
			blocks: select("core/block-editor").getBlocks(),
			// popups
			popups: [], // select("core").getEntityRecords( 'postType', 'greyd_popup', { per_page: 100 } ),
			// files
			files: [], // select("core").getEntityRecords( 'postType', 'attachment', { per_page: 100 } ),
		}));

		// dynamic
		var dynamic = [
			{ value: '', label: __("Select dynamic link", 'greyd_hub') },
			greyd?.dynamic?.tags?.getCurrentOptions('trigger', props.clientId),
			{ label: __("Website", 'greyd_hub'), options: greyd?.dynamic?.tags?.getOptions('trigger', 'site') },
		];

		/**
		 * Filter available dynamic triggers.
		 * @filter greyd.dynamic.triggerOptions
		 * 
		 * @param {array} dynamic		Array of dynamic trigger options
		 * @param {string} mode			'trigger'
		 * @param {string} clientId		clientId of this element
		 * 
		 * @return {array} dynamic
		 */
		dynamic = wp.hooks.applyFilters( 'greyd.dynamic.triggerOptions', dynamic, 'trigger', props.clientId );

		// make options arrays
		var options = function(type) {
			if (type == 'type') return types;
			if (type == 'scroll') return [
				{ value: '', label: __("Select target", 'greyd_hub') },
				{ value: '_top', label: __("Page top", 'greyd_hub') },
				...makeOptions(greyd.tools.searchAttribute(blocks, 'anchor')),
				{ value: '_bottom', label: __("Page bottom", 'greyd_hub') } 
			];
			if (type == 'popup') return [
				{ value: '', label: __("Select pop-up", 'greyd_hub') },
				...greyd.dynamic.getOptions(greyd.data.popups)
			];
			if (type == 'file') return [
				{ value: '', label: __("Select file", 'greyd_hub') },
				...greyd.dynamic.getOptions(greyd.data.media_urls)
			];
			if (type == 'event') return [
				{ value: '', label: __("Select event", 'greyd_hub') },
				...makeOptions(greyd.tools.searchAttribute(blocks, 'trigger_event')),
				...makeOptions(greyd.tools.searchAttribute(blocks, 'popoverName')),
				// { value: '__custom', label: __("Enter trigger", 'greyd_hub') }
			];
			if (type == 'dynamic') {
				return dynamic;
			}
		}
		// make options array
		var makeOptions = function(items) {
			// console.log(items);
			var options = [];
			for (var i=0; i<items.length; i++) {
				// console.log(items[i]);
				if (_.has(items[i], 'id')) {
					var label = items[i].title.rendered;
					if (_.has(items[i], 'source_url')) {
						var path = items[i].source_url.split('/');
						label = path[path.length-1];
					}
					options.push( { value: items[i].id, label: label } );
				}
			}
			return options;
		}

		// check if name of trigger exists.
		// also fixes wrongly formatted lowercase names.
		var checkName = function( trigger ) {
			var name = trigger?.params?.event?.name ?? '';
			// var items = greyd.tools.searchAttribute(blocks, 'trigger_event');
			var items = [
				...greyd.tools.searchAttribute(blocks, 'trigger_event'),
				...greyd.tools.searchAttribute(blocks, 'popoverName'),
			]
			// console.log(name);
			// console.log(items);
			if ( name != '' ) {
				var found = false;
				items.forEach( item => {
					if ( !found && item.id == name ) {
						// event found
						found = true;
					}
				} );
				if ( !found ) {
					// search for event name in lowecase
					items.forEach( item => {
						if ( !found && item.id.toLowerCase() == name ) {
							found = true;
							name = item.id;
						}
					} );
					if ( !found ) {
						// no matching event found
						name = '';
					}
					setTrigger({ ...trigger, params: { ...trigger.params, event: { ...trigger.params.event, name: name } } });
				}
			}
			return name;
		}

		var checkTrigger = function() {

			// doesn't need params
			if (trigger.type == 'back' || 
				trigger.type == 'popup_close') return true;

			// needs params
			if (_.has(trigger.params, trigger.type) && !_.isEmpty(trigger.params[trigger.type])) {

				// single param
				if (trigger.type == 'scroll' || 
					trigger.type == 'popup' || 
					trigger.type == 'file') {
						return true;
				}
				// multiple params
				else {
					if (trigger.type == '' || trigger.type == 'link') {
						if (!_.isEmpty(trigger.params[trigger.type].url))
							return true;
					}
					if (trigger.type == 'email') {
						if (!_.isEmpty(trigger.params[trigger.type].address)) {
							if (trigger.params[trigger.type].address.indexOf('@') > -1 &&
								trigger.params[trigger.type].address.indexOf('.') > -1)
								return true;
						}
					}
					if (trigger.type == 'event') {
						if (!_.isEmpty(trigger.params[trigger.type].name)) {
							if (trigger.params[trigger.type].name == '__custom') {
								if (!_.isEmpty(trigger.params[trigger.type].custom))
									return true;
							}
							else return true;
						}
					}
					if (trigger.type == 'dynamic') {
						if (!_.isEmpty(trigger.params[trigger.type].tag))
							return true;
					}
				}
			}
			return false;
		}
		var applyTrigger = function() {
			setEdit(false);
			var new_trigger = { type: trigger.type };
			if (_.has(trigger.params, trigger.type)) {
				new_trigger.params = trigger.params[trigger.type];
			}
			props.setAttributes( { trigger: new_trigger } );
		}

		// preview
		var parseTitle = function(items, slug) {
			// console.log(items);
			// console.log(slug);
			var label = "";
			var help = "";
			for (var i=0; i<items.length; i++)
				if (items[i].id == slug) { 
					var str = items[i].title.rendered.split(' ('); 
					label = str[0];
					if (str.length > 1) {
						str = str[1].split(')');
						help = str[0];
					}
					return { label: label, help: help };
				}
			return false;
		}
		var makePreview = function() {
			var label = '';
			var url = '';
			var atts = {
				onMouseOver: function() { setHover(true) },
				onMouseOut: function() { setHover(false) }
			}

			// link
			if (trigger.type == 'link' && !_.isEmpty(trigger.params.link)) {
				url = trigger.params.link.url;
				label = trigger.params.link.url;
				if (!_.isEmpty(trigger.params.link.title)) label = trigger.params.link.title;
				atts.href = url;
				atts.target = '_blank';
			}
			// back
			else if (trigger.type == 'back') {
				label = __("To the previous page", 'greyd_hub');
			}
			// popup close
			else if (trigger.type == 'popup_close') {
				label = __("Current pop-up", 'greyd_hub');
			}
			// scroll
			else if (trigger.type == 'scroll' && !_.isEmpty(trigger.params.scroll)) {
				label = trigger.params.scroll;
				if (label == '_top') label = __("Page top", 'greyd_hub');
				else if (label == '_bottom') label = __("Page bottom", 'greyd_hub');
				else {
					var title = parseTitle(greyd.tools.searchAttribute(blocks, 'anchor'), label);
					if (title) {
						label = title.label;
						url = title.help;
					}
				}
			}
			// popup
			else if (trigger.type == 'popup' && !_.isEmpty(trigger.params.popup)) {
				label = trigger.params.popup;
				for (var i=0; i<greyd.data.popups.length; i++)
					if (greyd.data.popups[i].id == label) { 
						label = greyd.data.popups[i].title; 
						break; 
					}
			}
			// email
			else if (trigger.type == 'email' && !_.isEmpty(trigger.params.email)) {
				label = trigger.params.email.address;
				var mailto = 'mailto:'+trigger.params.email.address;
				if (!_.isEmpty(trigger.params.email.subject)) {
					url = trigger.params.email.subject;
					mailto += '?subject='+url;
				}
				atts.href = mailto;
			}
			// file
			else if (trigger.type == 'file' && !_.isEmpty(trigger.params.file)) {
				label = trigger.params.file;
				url = "";
				if (greyd.data.media_urls[trigger.params.file]) { 
					label = greyd.data.media_urls[trigger.params.file].title; 
					url = greyd.data.media_urls[trigger.params.file].src;
				}
				if (url != "") {
					var path = url.split('/');
					label = path[path.length-1];
				}
				atts.href = url;
				atts.download = '';
			}
			// event
			else if (trigger.type == 'event' && !_.isEmpty(trigger.params.event)) {
				if (trigger.params.event.name != '__custom') {
					label = trigger.params.event.name;
					var title = parseTitle(greyd.tools.searchAttribute(blocks, 'trigger_event'), label);
					if (title) {
						label = title.label;
						url = title.help;
					}
				}
				else label = trigger.params.event.custom;
			}
			// dynamic
			else if (trigger.type == 'dynamic' && !_.isEmpty(trigger.params.dynamic.tag)) {
				label = greyd.dynamic.tags.renderLabel(trigger.params.dynamic.tag, dynamic);
			}
			// different type
			else {
				// console.log(trigger);
				// console.log(dynamic);
			}
			
			return [
				el( 'a', atts, label),
				(url != '') ? el( 'span', { className: 'greyd-inspector-help' }, url ) : ''
			];
		}

		// hover preview
		var makeHoverPreview = function() {

			// link
			if (trigger.type == 'link' && !_.isEmpty(trigger.params.link)) {
				return el( 'div', { style: { width: 'calc(1280px * 0.2)', height: 'calc(768px * 0.2)' } }, 
					el( 'iframe', { 
						name: "popup-preview-0",
						className: 'greyd-iframe-preview',
						src: trigger.params.link.url+'/?popup_preview=abcde'
					} )
				);
			}
			// back
			// scroll
			// popup
			// email
			// file
			if (trigger.type == 'file' && !_.isEmpty(trigger.params.file)) {
				var width = 250; // '300px'
				var height = 200; // '240px';
				var url = "";
				if (greyd.data.media_urls[trigger.params.file]) { 
					var file = greyd.data.media_urls[trigger.params.file];
					url = file.src;
					if (file.type.indexOf('image') == 0) {
						return el( 'div', { style: { display: 'flex', width: width+'px', height: height+'px' }, className: 'editor-post-featured-image__preview' }, [
							el( 'img', { 
								className: 'greyd-img-preview',
								src: url 
							} )
						] );
					}
					if (file.type == 'application/pdf') {
						return el( 'div', { style: { display: 'flex', width: width+'px', height: height+'px' } }, [
							el( 'iframe', { 
								className: 'greyd-pdf-preview',
								src: url 
							} )
						] );
					}
				}
			}
			// event
			// dynamic

			return '';
		}

		// edit settings
		var makeSettings = function() {

			// link
			if (trigger.type == '' || trigger.type == 'link') {
				return el( wp.blockEditor.__experimentalLinkControl, {
					hasTextControl: true,
					value: trigger.params.link,
					onChange: function(value) { 
						// console.log("set link:");
						// console.log(value);
						setTrigger({ ...trigger, params: { ...trigger.params, link: value } });
					},
					settings: [
						{
							id: 'opensInNewTab',
							title: __( "Open in a new tab", 'greyd_hub' ),
						},
						{
							id: 'download',
							title: __( "Download (only files and images)", 'greyd_hub' ),
						},

						/**
						 * @since 1.7.4 Option to add rel="noreferrer" and rel="external"
						 */
						{
							id: 'noReferrer',
							title: __( "No forwarding information (noreferrer)", 'greyd_hub' ),
						},
						{
							id: 'external',
							title: __( "External link", 'greyd_hub' ),
						},
						/**
						 * @since 1.7.4 Option to not filter the url
						 */
						...(
							typeof wpml_get_block_editor === 'undefined'
							?  []
							: [ {
								id: 'isRawURL',
								title: __( "Prevent filtering of the link (WPML)", 'greyd_hub' ),
							} ]
						)
					]
				} );
			}
			// back (no sets)
			// scroll
			else if (trigger.type == 'scroll') {
				return el( 'div', { className: 'trigger-body__content' }, [
					el( greyd.components.OptionsControl, {
						style: { width: "100%" },
						value: trigger.params.scroll,
						options: options('scroll'),
						onChange: function(value) { 
							// console.log("select anchor: "+value);
							setTrigger({ ...trigger, params: { ...trigger.params, scroll: value } });
						},
					} )
				] );
			}
			// popup
			else if (trigger.type == 'popup') {
				return el( 'div', { className: 'trigger-body__content' }, [
					el( greyd.components.OptionsControl, {
						style: { width: "100%" },
						value: trigger.params.popup,
						options: options('popup'),
						onChange: function(value) { 
							// console.log("select popup: "+value);
							setTrigger({ ...trigger, params: { ...trigger.params, popup: value } });
						},
					} )
				] );
			}
			// email
			else if (trigger.type == 'email') {
				return el( 'div', { className: 'trigger-body__content' }, [
					el( wp.components.TextControl, {
						label: __("Email address", 'greyd_hub'),
						value: (_.has(trigger.params, 'email')) ? trigger.params.email.address : '',
						onChange: function(value) { 
							// console.log("change email address: "+value);
							var email = { address: value };
							if (_.has(trigger.params, 'email')) email = { ...trigger.params.email, address: value };
							setTrigger({ ...trigger, params: { ...trigger.params, email: email } });
						},
					} ),
					el( wp.components.TextControl, {
						label: __("Subject", 'greyd_hub'),
						value: (_.has(trigger.params, 'email')) ? trigger.params.email.subject : '',
						onChange: function(value) { 
							// console.log("change email subject: "+value);
							var email = { subject: value };
							if (_.has(trigger.params, 'email')) email = { ...trigger.params.email, subject: value };
							setTrigger({ ...trigger, params: { ...trigger.params, email: email } });
						},
					} )
				] );
			}
			// file
			else if (trigger.type == 'file') {
				return el( 'div', { className: 'trigger-body__content' }, [
					el( greyd.components.OptionsControl, {
						style: { width: "100%" },
						value: trigger.params.file,
						options: options('file'),
						onChange: function(value) { 
							// console.log("select file: "+value);
							setTrigger({ ...trigger, params: { ...trigger.params, file: value } });
						},
					} )
				] );
			}
			// event
			else if (trigger.type == 'event') {
				return el( 'div', { className: 'trigger-body__content' }, [
					el( greyd.components.OptionsControl, {
						style: { width: "100%" },
						value: checkName(trigger),
						options: options('event'),
						onChange: function(value) { 
							// console.log("select event: "+value);
							var event = { name: value };
							if (_.has(trigger.params, 'event')) event = { ...trigger.params.event, name: value };
							if (event.name != '__custom') delete(event.custom);
							setTrigger({ ...trigger, params: { ...trigger.params, event: event } });
						},
					} ),
					(_.has(trigger.params, 'event') && trigger.params.event.name == '__custom') ?
						el( wp.components.TextControl, {
							label: __("Name of the event", 'greyd_hub'),
							value: (_.has(trigger.params, 'event')) ? trigger.params.event.custom : '',
							onChange: function(value) { 
								// console.log("change custom event name: "+value);
								var event = { custom: value };
								if (_.has(trigger.params, 'event')) event = { ...trigger.params.event, custom: value };
								setTrigger({ ...trigger, params: { ...trigger.params, event: event } });
							},
						} ) : '',
					el( wp.components.ToggleControl, {
						label: __("Trigger already on hover", 'greyd_hub'),
						checked: (_.has(trigger.params, 'event')) ? trigger.params.event.hover : false,
						onChange: function(value) { 
							// console.log("toggle hover: "+value);
							var event = { hover: value };
							if (_.has(trigger.params, 'event')) event = { ...trigger.params.event, hover: value };
							setTrigger({ ...trigger, params: { ...trigger.params, event: event } });
						},
					} ),
					el( wp.components.ToggleControl, {
						label: __("Trigger page-wide", 'greyd_hub'),
						checked: (_.has(trigger.params, 'event')) ? trigger.params.event.global : false,
						onChange: function(value) { 
							// console.log("toggle hover: "+value);
							var event = { global: value };
							if (_.has(trigger.params, 'event')) event = { ...trigger.params.event, global: value };
							setTrigger({ ...trigger, params: { ...trigger.params, event: event } });
						},
					} )
				] );
			}
			// dynamic
			else if (trigger.type == 'dynamic') {
				const isWooAjax = _.has(trigger.params, 'dynamic') && _.has(trigger.params.dynamic, 'tag') && trigger.params.dynamic.tag == "woo_ajax";

				return el( 'div', { className: 'trigger-body__content' }, [
					el( greyd.components.OptionsControl, {
						label: __( 'Dynamic link', 'greyd_hub'),
						style: { width: '100%' },
						value: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.tag : '',
						options: options('dynamic'),
						onChange: function(value) { 
							// console.log("select dynamic: "+value);
							var dynamic = { tag: value };
							if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, tag: value };
							setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
						},
					} ),
					// if woo give some extra options
					isWooAjax ? 
					[ 
						el( greyd.components.OptionsControl, {
							label: __("Link target", 'greyd_hub'),
							style: { width: "100%"},
							value: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.redirects : false,
							options: [
								{ value: 'default', label: __("Default", 'greyd_hub') },
								{ value: 'cart', label: __("View shopping cart", 'greyd_hub') },
								{ value: 'checkout', label: __("To checkout", 'greyd_hub') },
								{ value: 'shop', label: __("View shop", 'greyd_hub') },
								{ value: 'false', label: __("No redirect", 'greyd_hub') },
							],
							onChange: function(value) { 
								// console.log(trigger);
								var dynamic = { redirects: value };
								if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, redirects: value };
								setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
							},
						} ),
						el( wp.components.ToggleControl, {
							label: __("Do not add twice", 'greyd_hub'),
							checked: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.notTwice : false,
							onChange: function(value) { 
								var dynamic = { notTwice: value };
								if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, notTwice: value };
								setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
							},
						} ), 
						el( wp.components.ToggleControl, {
							label: __("Empty the shopping cart beforehand", 'greyd_hub'),
							checked: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.clear : false,
							onChange: function(value) { 
								var dynamic = { clear: value };
								if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, clear: value };
								setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
							},
						} ), 
					]
					: [ 
						el( wp.components.ToggleControl, {
							label: __("Open in a new tab", 'greyd_hub'),
							checked: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.opensInNewTab : false,
							onChange: function(value) { 
								// console.log("toggle opensInNewTab: "+value);
								var dynamic = { opensInNewTab: value };
								if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, opensInNewTab: value };
								setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
							},
						} ),
						el( wp.components.ToggleControl, {
							label: __("Download (only files and images)", 'greyd_hub'),
							checked: (_.has(trigger.params, 'dynamic')) ? trigger.params.dynamic.download : false,
							onChange: function(value) { 
								// console.log("toggle download: "+value);
								var dynamic = { download: value };
								if (_.has(trigger.params, 'dynamic')) dynamic = { ...trigger.params.dynamic, download: value };
								setTrigger({ ...trigger, params: { ...trigger.params, dynamic: dynamic } });
							},
						} )
					],
					el( wp.components.TextControl, {
						label: __( 'Link text (optional)', 'greyd_hub' ),
						value: (_.has(trigger.params.dynamic, 'linkText')) ? trigger.params.dynamic.linkText : '',
						onChange: function(value) { 
							setTrigger({ ...trigger, params: { ...trigger.params, dynamic: { linkText: value } } });
						},
					} ),
				] );
			}

			return '';
		}

		// render popover content
		var popoverContent = function() {

			return (!_.isEmpty(triggerType) && !edit) ? [
				// preview mode
				el( 'div', { className: 'trigger-head flex' }, [
					// label
					el( 'span', {
						className: 'trigger-label'
					}, triggerTitle() ),
					// remove button
					el( wp.components.Button, {
						className: 'is-small',
						style: { color: 'darkred' },
						label: __("Remove trigger", 'greyd_hub'),
						icon: el( greyd.components.GreydIcon, { icon: 'no' } ),
						onClick: function() { 
							// console.log("remove");
							setTrigger({ type: 'link', params: {} });
							props.setAttributes( { trigger: undefined } );
						},
					} ),
					// edit button
					el( wp.components.Button, {
						className: 'is-small',
						label: __("Adjust trigger", 'greyd_hub'),
						icon: el( greyd.components.GreydIcon, { icon: 'edit' } ),
						onClick: function() { 
							// console.log("edit");
							setEdit(true);
						},
					} ),
				] ),
				el( 'div', { className: 'trigger-body' }, [
					// preview
					el( 'span', { 
						className: 'trigger-preview' 
					}, makePreview() ),
					// hover preview
					(hover)
					? el( wp.components.Popover, {
						className: 'components-greyd-preview__content',
					}, makeHoverPreview() )
					: '',
				] )
			] : [ 
				// edit mode
				el( 'div', { className: 'trigger-head' }, [
					// label
					el( 'label', {}, __("Select action", 'greyd_hub') ),
					el( 'div', {
						className: 'flex'
					}, [
						// type
						el( greyd.components.OptionsControl, {
							style: { width: '100%' },
							value: trigger.type,
							options: options('type'),
							onChange: function(value) { 
								// console.log("select");
								setTrigger({ ...trigger, type: value });
							},
						} ),
						// set button
						el( wp.components.Button, {
							disabled: !checkTrigger(),
							className: 'is-small is-primary',
							label: __("Set trigger", 'greyd_hub'),
							icon: el( greyd.components.GreydIcon, { icon: 'saved' } ),
							onClick: function() { 
								// console.log("set");
								applyTrigger();
							},
						} )
					] ),
				] ),
				// settings
				el( 'div', { className: 'trigger-body' },
					makeSettings(),
				)	
			];
		}

		// reset if clicked away
		if (!props.isSelected && active) {
			if (checkTrigger()) applyTrigger();
			setActive(false);
		}

		return {
			triggerType: triggerType,
			active: active,
			setActive: setActive,
			edit: edit,
			popoverContent: popoverContent
		};
		
	}

	//
	// trigger picker controls
	this.triggerPickerControls = function(props) {
		
		const {
			triggerType,
			active,
			setActive,
			edit,
			popoverContent
		} = this.triggerPickerControlsLogic(props);

		// trigger toolbar
		var className = "components-toolbar";
		if (!_.isEmpty(triggerType)) className += ' is-pressed';
		if (_.has(props.attributes, "dynamic_parent")) {
			var enabled = false;
			if (_.has(props.attributes, 'dynamic_fields') && props.attributes.dynamic_fields && props.attributes.dynamic_fields.length > 0) {
				for (var k=0; k<props.attributes.dynamic_fields.length; k++) {
					if (props.attributes.dynamic_fields[k].key == 'trigger') {
						enabled = true;
						break;
					}
				}
			}
			if (!enabled) className += ' disabled';
			// console.log(props.attributes);
		}
		
		return [
			el( wp.components.ToolbarButton, {
				className: className,
				label: _.isEmpty(triggerType) ? __("Select trigger", 'greyd_hub') : __("Adjust trigger", 'greyd_hub'),
				icon: el( greyd.components.GreydIcon, { icon: 'external' } ),
				onClick: function() { 
					// console.log("toggle popover");
					setActive(!active);
				},
			} ),
			active && el( wp.components.Popover, {
				className: 'components-greyd-dropdown__content'+(_.isEmpty(triggerType) || edit ? ' is-edit' : ''),
			}, popoverContent() )
		];

	}

};