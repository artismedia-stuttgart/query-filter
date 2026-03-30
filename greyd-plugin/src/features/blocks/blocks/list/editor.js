/**
 * Editor Script for Greyd List Blocks.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Additional tools/helper functions.
	 * moved from greyd.tools
	 */
	greyd.tools.list = new function() {

		/**
		 * Get all list-style options.
		 * 
		 * @param {string} type	'web', 'num' or 'all' (default)
		 * @returns array of list-style options
		 */
		this.getListStyleOptions = function(type="all") {
			var web = [
				{ label: __("• Filled circle", 'greyd_hub'), value: 'disc' },
				{ label: __("◦ Open circle", 'greyd_hub'), value: 'circle' },
				{ label: __("▪ Filled square", 'greyd_hub'), value: 'square' }
			];
			var num = [
				{ label: __("Numerical (1. 2. ...)", 'greyd_hub'), value: 'decimal' },
				{ label: __("Numerical with zero (01. 02. ...)", 'greyd_hub'), value: 'decimal-leading-zero' },
				{ label: __("Roman small (i. ii. ...)", 'greyd_hub'), value: 'lower-roman' },
				{ label: __("Roman capitals (I. II. ...)", 'greyd_hub'), value: 'upper-roman' },
				{ label: __("Greek small (α. β. ...)", 'greyd_hub'), value: 'lower-greek' },
				// { label: __('armenisch (Ա. Բ. ...)', 'greyd_hub'), value: 'armenian' },
				// { label: __('georgisch (ა. ბ. ...)', 'greyd_hub'), value: 'georgian' },
				{ label: __("Alphabetical small (a. b. ...)", 'greyd_hub'), value: 'lower-alpha' },
				{ label: __("Alphabetical capitals (A. B. ...)", 'greyd_hub'), value: 'upper-alpha' },
				// { label: __('keine Hervorhebung', 'greyd_hub'), value: ' none' },
			];
			if (type == "all") return web+num;
			if (type == "num") return num;
			if (type == "web") return web;
		};

		/**
		 * Split style object in parts.
		 * 
		 * @param {object} $styleObj   All default, hover & responsive styles.
		 * @param {object} $attribute  Attribute settings:
		 *      @property {string} name     Name of the attribute to split.
		 *      @property {string} rename   Optional new name for the attribute.
		 *      @property {string} default  Optional default value for the attribute.
		 * 
		 * @returns {object} All values of the attribute, including 'responsive', 'hover' & 'active'
		 */
		this.splitStyleObj = function(styleObj, attribute) {
			var { name, rename, def } = attribute;
			if (typeof rename !== 'string') rename = name;
			var obj = {};
			if (_.has(styleObj, name)) obj[rename] = styleObj[name];
			if (_.has(styleObj, 'responsive')) {
				var responsive = {};
				[ 'lg', 'md', 'sm' ].forEach(function(i) {
					if (_.has(styleObj.responsive, i+'.'+name))
						responsive[i] = { [rename]: styleObj.responsive[i][name] };
				});
				if (!_.isEmpty(responsive)) obj.responsive = responsive;
			}
			if (_.has(styleObj, 'hover.'+name)) obj.hover = { [rename]: styleObj.hover[name] };
			if (_.has(styleObj, 'active.'+name)) obj.active = { [rename]: styleObj.active[name] };
			if (!_.has(obj, rename) || obj[rename] == '') obj[rename] = def;
			// console.log(obj);
			return obj;
		}

		/**
		 * Get the direct parent of a Block by clientId.
		 * Parent is searched by name, false if not found.
		 * @param {string} clientId		clientId of the Block
		 * @param {string} parent		Name of the parent to search for
		 * @returns {object|bool}		Parent Block Attributes or false
		 */
		this.getDirectParent = function(clientId, parent) {
			var parentBlocks = wp.data.select( 'core/block-editor' ).getBlockParents(clientId);
			if (parentBlocks.length > 0) {
				var parentAtts = wp.data.select('core/block-editor').getBlocksByClientId([parentBlocks[parentBlocks.length-1]]);
				// console.log(parentAtts[0]);
				if (parentAtts[0].name == parent) 
					return parentAtts[0];
			}
			return false;
		}

		/**
		 * Handle cursor on merge of list items
		 */
		this.getCursorPosition = function() {
			var selection = window.getSelection();
			var pos = -1;

			if (selection.focusNode) {
				var node = selection.focusNode; 
				pos = selection.focusOffset;

				while (node) {
					if (_.has(node, 'className') && node.className == 'block-editor-rich-text__editable rich-text') break;

					if (node.previousSibling) {
						node = node.previousSibling;
						if (_.has(node, 'textContent'))
							pos += node.textContent.length;
					} 
					else {
						node = node.parentNode;
						if (node === null) break;
					}
				}
			}

			return pos;
		};
		this.setCursorPosition = function(pos) {
			var sel = window.getSelection();
			var node = sel.focusNode;
			while (node.className != 'block-editor-rich-text__editable rich-text') {
				node = node.parentNode;
			}

			if (node) {
				range = greyd.tools.list.createRange(node, pos);
				if (range) {
					range.collapse(false);
					sel.removeAllRanges();
					sel.addRange(range);
				}
			}
		}
		this.createRange = function(node, pos, range) {
			if (!range) {
				range = document.createRange()
				range.selectNode(node);
				range.setStart(node, 0);
			}

			if (pos === 0) {
				range.setEnd(node, pos);
			} 
			else if (node && pos >0) {
				if (node.nodeType === Node.TEXT_NODE) {
					if (node.textContent.length < pos) {
						pos -= node.textContent.length;
					} 
					else {
						range.setEnd(node, pos);
						pos = 0;
					}
				} 
				else {
					for (var i=0; i<node.childNodes.length; i++) {
						range = greyd.tools.list.createRange(node.childNodes[i], pos, range);
						if (pos === 0) break;
					}
				}
			} 

			return range;
		};

	};

	/**
	 * Register List Block.
	 */
	wp.blocks.registerBlockType( 'greyd/list', {
		title: __("List (Greyd)", 'greyd_hub'),
		description: __("List with bullet points, icons and images.", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('list'),
		category: 'greyd-blocks',
		keywords: [ 'list', 'bullet', 'numbers', 'notes', 'points', 'icons' ],

		supports: {
			anchor: true,
			align: true,
			typography: {
				fontSize: true,
				__experimentalFontStyle: true,
				__experimentalFontWeight: true,
				__experimentalTextTransform: true,
				__experimentalDefaultControls: {
					fontSize: true
				}
			},
			spacing: {
				margin: true,
				padding: true
			}
		},
		attributes: {
			css_animation: { type: 'string' },
			inline_css: { type: 'string' },
			inline_css_id: { type: 'string' },
			// todo
			align: { type: 'string', default: "" },
			type: { type: 'string', default: "web" },
			color: { type: 'string' }, 						// new -> text color
			gap: { type: 'string', default: "10px" }, 		// deprecated -> moved to layout
			fullwidth: { type: 'boolean', default: false }, // deprecated -> removed
			web: { type: 'object', default: {}, properties: { 
				style: { type: 'string' },
				position: { type: 'string' },
				align_y: { type: 'string' },
				start: { type: 'boolean' }, 				// new -> set start of numeric list
				reverse: { type: 'boolean' }, 				// new -> reverse numeric list
			} },
			icon: { type: 'object', properties: { 			// changed -> no defaults
				icon: { type: 'string' },
				url: { type: 'string' },
				id: { type: 'string' },
				color: { type: 'string' },
				size: { type: 'string' },
				margin: { type: 'string' },
				position: { type: 'string' },
				align_y: { type: 'string' },
				align_x: { type: 'string' },
			}, default: {
				icon: 'icon_check',
				color: ''
			} },
			layout: { type: 'object', properties: { 		// new -> layout elements
				// fullwidth: { type: 'boolean' },
				gap: { type: 'string' },
				indent: { type: 'string' }, 				// new -> indent depth
			} }
		},
		providesContext: {
			'greyd/list-type': 'type',
			'greyd/list-web': 'web',
			'greyd/list-icon': 'icon',
		},

		edit: function( props ) {


			// convert old fullwidth and gap
			var layout = _.has(props.attributes, 'layout') ? props.attributes.layout : {};
			var oldlayout = _.clone(layout);
			if (_.has(props.attributes, 'fullwidth')) {
				// var fullwidth = props.attributes.fullwidth;
				// if (fullwidth) layout.fullwidth = true;
				delete props.attributes.fullwidth;
			}
			if (_.has(props.attributes, 'gap')) {
				var gap = props.attributes.gap;
				if (gap != '10px') layout.gap = gap;
				delete props.attributes.gap;
			}
			if (JSON.stringify(oldlayout) != JSON.stringify(layout)) {
				props.setAttributes({ layout: layout });
				console.info("list layout atts converted.");
			}

			var bullets = [ 'disc', 'circle', 'square' ];
			if (_.has(props.attributes, 'type') && _.has(props.attributes, 'web.style')) {
				if (props.attributes.type == 'web' && bullets.indexOf(props.attributes.web.style) == -1) {
					props.setAttributes({ type: 'num' });
					console.info("list type converted.");
				}
			}

			// selectors
			var selector_ul = "#block-"+props.clientId+" [data-ul='"+props.clientId+"']";
			var selector_data = selector_ul+" > div > div > [data-type^='greyd/']";
			var selector_li = selector_data+" > li";
			var selector_icon = selector_li+" > .list_icon";
			var selector_content = selector_li+" > .list_content";

			var wrapper_style = "max-width: 100%; ";
			var li_style = ""; // "width: max-content; "; // fullwidth: 100%
			var icon_style = "";
			var responsive_style = '';

			if ( _.has(props.attributes, 'color') && props.attributes.color && !_.isEmpty(props.attributes.color) ) {
				var color = props.attributes.color;
				if ( color && typeof color === 'string' && color.indexOf("color-") === 0 ) {
					color = "var(--"+color.replace('-', '')+")";
				}
				li_style += "color: "+color+"; ";
			}

			if (props.attributes.type == '') {
				wrapper_style += "list-style: none; ";
			}
			else if (props.attributes.type == 'web' | props.attributes.type == 'num') {
				var web_atts = {
					style: "disc",
					position: "left",
					align_y: "",
				};
				if (_.has(props.attributes, 'web')) web_atts = { ...web_atts, ...props.attributes.web };
				// console.log(web_atts);
				wrapper_style += "list-style: "+web_atts.style+"; ";

				// position
				var getPositionCSS = function(pos) {
					if (pos == 'right') {
						return  selector_data+" { margin-left: 0px; margin-right: 20px; direction: rtl; } "+
								selector_content+" { direction: ltr; } ";
					}
					else {
						return  selector_data+" { margin-left: 20px; margin-right: 0px; } ";
					}
				}
				var position = greyd.tools.list.splitStyleObj(web_atts, { name: 'position' });
				if (_.has(position, 'position')) responsive_style += getPositionCSS(position.position);
				if (_.has(position, 'responsive')) {
					[ 'lg', 'md', 'sm' ].forEach(function(i) {
						if (_.has(position.responsive, i+'.position')) {
							responsive_style += "@media (max-width: "+( _.get(greyd.data.grid, i) - 0.02 )+"px) { "+getPositionCSS(position.responsive[i].position)+"} ";
						}
					});
				}
				// align
				var getAlign = function(align) {
					if (align == "start") return "text-top";
					else if (align == "center") return "middle";
					else if (align == "end") return "text-bottom";
					return "top";
				};
				var getAlignCSS = function(align) {
					return selector_content+" { vertical-align: "+getAlign(align)+"; } ";
				}
				var align_y = greyd.tools.list.splitStyleObj(web_atts, { name: 'align_y' });
				if (_.has(align_y, 'align_y')) responsive_style += getAlignCSS(align_y.align_y);
				if (_.has(align_y, 'responsive')) {
					[ 'lg', 'md', 'sm' ].forEach(function(i) {
						if (_.has(align_y.responsive, i+'.align_y')) {
							responsive_style += "@media (max-width: "+( _.get(greyd.data.grid, i) - 0.02 )+"px) { "+getAlignCSS(align_y.responsive[i].align_y)+"} ";
						}
					});
				}
			}
			else if (props.attributes.type == 'icon' || props.attributes.type == 'img') {
				var icon_atts = {
					icon: "",
					url: "",
					id: -1,
					color: "",
					size: "20px",
					margin: "10px",
					position: "left",
					align_y: "start",
					align_x: "start",
				};
				if (_.has(props.attributes, 'icon')) icon_atts = { ...icon_atts, ...props.attributes.icon };
				// console.log(icon_atts);
				wrapper_style += "list-style: none; ";
				li_style += "display: flex; ";

				// position
				var getPositionCSS = function(pos) {
					var margin = greyd.tools.getSpacingPresetCssVar(icon_atts.margin);
					if (pos == 'left') {
						return  selector_li+" { flex-direction: row; } "+
								selector_icon+" { margin-right: "+margin+"; } ";
					}
					else if (pos == 'right') {
						return  selector_li+" { flex-direction: row-reverse; } "+
								selector_icon+" { margin-left: "+margin+"; } ";
					}
					else if (pos == 'top') {
						return  selector_li+" { flex-direction: column; } "+
								selector_icon+" { margin-bottom: "+margin+"; } ";
					}
					else if (pos == 'bottom') {
						return  selector_li+" { flex-direction: column-reverse; } "+
								selector_icon+" { margin-top: "+margin+"; } ";
					}
				}
				var position = greyd.tools.list.splitStyleObj(icon_atts, { name: 'position' });
				if (_.has(position, 'position')) responsive_style += getPositionCSS(position.position);
				if (_.has(position, 'responsive')) {
					[ 'lg', 'md', 'sm' ].forEach(function(i) {
						if (_.has(position.responsive, i+'.position')) {
							responsive_style += "@media (max-width: "+( _.get(greyd.data.grid, i) - 0.02 )+"px) { "+getPositionCSS(position.responsive[i].position)+"} ";
						}
					});
				}
				// align
				var getAlign = function(align) {
					if (align == "first") return "start";
					if (align == "start") return "start";
					if (align == "center") return "center";
					if (align == "end") return "end";
					return align;
				};
				var getAlignCSS = function(align) {
					var content_style = (align == "first") ? selector_content+" { margin-top: calc( ( "+icon_atts.size+" - ( 1em * var(--lineHeight, var(--wp--custom--line-height--normal)) ) ) / 2 ); } " : '';
					return selector_li+" { align-items: "+getAlign(align)+"; } "+content_style;
				}
				var align_direction = (position.position == 'left' || position.position == 'right') ? 'align_y' : 'align_x';
				if (_.has(icon_atts, align_direction)) responsive_style += getAlignCSS(icon_atts[align_direction]);
				if (_.has(icon_atts, 'responsive')) {
					[ 'lg', 'md', 'sm' ].forEach(function(i) {
						var dir = (!_.has(position, 'responsive.'+i+'.position') || position.responsive[i].position == 'left' || position.responsive[i].position == 'right') ? 'align_y' : 'align_x';
						if (_.has(icon_atts.responsive, i+'.'+dir)) {
							responsive_style += "@media (max-width: "+( _.get(greyd.data.grid, i) - 0.02 )+"px) { "+getAlignCSS(icon_atts.responsive[i][dir])+"} ";
						}
					});
				}
				// color
				if (props.attributes.type == 'icon') {
					var color = icon_atts.color;
					if ( color && typeof color === 'string' && color.indexOf("color-") === 0 ) {
						color = "var(--"+color.replace('-', '')+")";
					}
					icon_style += "color: "+color+"; ";
					icon_style += "font-size: "+icon_atts.size+"; ";
				}
				// size
				if (props.attributes.type == 'img') {
					var icon_size = icon_atts.size;
					var icon_src = icon_atts.url;
					icon_style += "width: "+icon_size+"; min-width: "+icon_size+"; height: "+icon_size+"; ";
					icon_style += "background: url("+icon_src+"); background-repeat: no-repeat; background-position: center; background-size: contain; ";
				}
			}

			var style = ""; // "#block-"+props.clientId+" { margin-left: var(--indent, auto) !important; } ";
			style += selector_ul+" { "+wrapper_style+" } ";
			if (li_style != "") style += selector_li+" { "+li_style+" } ";
			if (icon_style != "") style += selector_icon+" { "+icon_style+" } ";
			if (responsive_style != "") style += responsive_style;
			// more responsive styles
			// gap
			var layout_gap = greyd.tools.list.splitStyleObj(props.attributes.layout, { name: 'gap', rename: 'margin-top', def: '10px' });
			var gap = greyd.tools.composeCSS( { "": layout_gap }, '', false, false );
			style += gap.split(". {").join(selector_data+" {");
			// indent
			var layout_indent = greyd.tools.list.splitStyleObj(props.attributes.layout, { name: '--indent', rename: '--indent', def: '20px' });
			var indent = greyd.tools.composeCSS( { "": layout_indent }, '', false, false );
			style += indent.split(". {").join(selector_ul+" {");

			// style = el( 'style', { className: 'greyd_styles' }, style );

			return [
				el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
					// colors
					el( greyd.components.AdvancedPanelBody, { 
						title: __("Colors", 'greyd_hub'), 
						initialOpen: true, 
						holdsColors: [
							{ color: props.attributes.color, title: __("Text color", 'greyd_hub') },
							props.attributes.type == "icon" && { color: (_.has(props.attributes.icon, 'color') ? props.attributes.icon.color : ''), title: __("Icon color", 'greyd_hub') }
						]
					}, [
						el( greyd.components.ColorGradientPopupControl, {
							mode: 'color',
							label: __("Text color", "greyd_hub"),
							value: props.attributes.color,
							onChange: function(value) { props.setAttributes( { color: value } ); },
						} ),
						(props.attributes.type == "icon") && el( greyd.components.ColorGradientPopupControl, {
							mode: 'color',
							label: __("Icon color", "greyd_hub"),
							value: (_.has(props.attributes.icon, 'color') ? props.attributes.icon.color : ''),
							onChange: function(value) { props.setAttributes( { icon: { ...props.attributes.icon, color: value } } ); },
						} )
					] ),
					// layout
					el( greyd.components.StylingControlPanel, {
						title: __("Spaces", 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						parentAttr: "layout",
						blockProps: props,
						controls: [ 
							{
								label: __("Space between", 'greyd_hub'),
								attribute: "gap",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', '%', 'em' ],
								max: { 'px': 50, '%': 100, 'em': 5 },
							}, 
							{
								label: __("Indentation", 'greyd_hub'),
								attribute: "--indent",
								control: greyd.components.RangeUnitControl,
								supportsPresets: true,
								units: [ 'px', '%', 'em' ],
								max: { 'px': 100, '%': 100, 'em': 5 },
							}, 
							// {
							// 	hidden: { lg: true, md: true, sm: true },
							// 	label: __('Elemente auf volle Breite strecken', 'greyd_hub'),
							// 	attribute: "fullwidth",
							// 	control: wp.components.ToggleControl,
							// 	checked: _.has(props.attributes, 'layout.fullwidth') ? props.attributes.layout.fullwidth : false,
							// } 
						]
					} ),
				]),
				el( wp.blockEditor.InspectorControls, { group: 'settings' }, [
					el( greyd.components.AdvancedPanelBody, { title: __("General", 'greyd_hub'), initialOpen: true }, [
						// type
						el( wp.components.SelectControl, {
							label: __("Appearance", 'greyd_hub'),
							value: props.attributes.type,
							options: [
								{ label: __("Paragraphs only", 'greyd_hub'), value: "" },
								// { label: __('Web-Standards (Bulletpoints & Nummerierungen)', 'greyd_hub'), value: 'web' },
								{ label: __('Bulletpoints', 'greyd_hub'), value: 'web' },
								{ label: __("Numbering", 'greyd_hub'), value: 'num' },
								{ label: __('Icon', 'greyd_hub'), value: 'icon' },
								{ label: __("Image", 'greyd_hub'), value: 'img' },
							],
							onChange: function(value) { 
								var web = _.has(props.attributes, 'web') ? _.clone(props.attributes.web) : {};
								if (value == 'num') web.style = 'decimal';
								else if (_.has(web, 'style')) {
									if (value == 'web' && bullets.indexOf(web.style) == -1) web.style = 'disc';
								}
								props.setAttributes( { type: value, web: web } ); 
							},
						} ),
						// list-style
						(props.attributes.type == "web" || props.attributes.type == "num") && el( wp.components.SelectControl, {
							value: _.has(props.attributes, 'web.style') ? props.attributes.web.style : '',
							options: greyd.tools.list.getListStyleOptions(props.attributes.type),
							onChange: function(value) { props.setAttributes( { web: { ...props.attributes.web, style: value } } ); },
						} ),
						// num options
						(props.attributes.type == "num") && [
							el( wp.components.__experimentalNumberControl, {
								label: __( "Start value", 'greyd_hub' ),
								value: _.has(props.attributes, 'web.start') ? props.attributes.web.start : '',
								isDragEnabled: true,
								isShiftStepEnabled: true,
								shiftStep: 10,
								step: 1,
								onChange: function(value) { props.setAttributes( { web: { ...props.attributes.web, start: value } } ); },
							} ),
							el( wp.components.ToggleControl, {
								label: __( "Invert numbering", 'greyd_hub' ),
								checked: _.has(props.attributes, 'web.reverse') ? props.attributes.web.reverse : false,
								onChange: function(value) { props.setAttributes( { web: { ...props.attributes.web, reverse: value } } ); },
							} ),
						],
						// icon
						(props.attributes.type == "icon") && el( greyd.components.IconPicker, {
							value: _.has(props.attributes, 'icon.icon') ? props.attributes.icon.icon : '',
							icons: greyd.data.icons,
							onChange: function(value) { 
								props.setAttributes( { icon: { ...props.attributes.icon, icon: value } } ); 
							},
						} ),
						// image
						(props.attributes.type == "img") && el( wp.components.BaseControl, { }, [
							el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) }, [
								el( wp.blockEditor.MediaUpload, {
									value: _.has(props.attributes, 'icon.id') ? props.attributes.icon.id : -1,
									onSelect: function(value) { props.setAttributes( { icon: { ...props.attributes.icon, id: value.id, url: value.url } } ); },
									render: function(obj) {
										return el( wp.components.Button, { 
											className: !_.has(props.attributes, 'icon.id') || props.attributes.icon.id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
											onClick: obj.open 
										}, !_.has(props.attributes, 'icon.id') || props.attributes.icon.id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: props.attributes.icon.url } ) )
									},
								} ),
								_.has(props.attributes, 'icon.id') && props.attributes.icon.id !== -1 ? el( wp.components.Button, { 
									className: "is-link is-destructive",
									onClick: function() { props.setAttributes( { icon: { ...props.attributes.icon, id: -1, url: "" } } ) },
								}, __( "Remove image", 'greyd_hub' ) ) : ""
							] ),
						] ),
						// size and margin
						(props.attributes.type == "icon" || props.attributes.type == "img") && [ 
							el( greyd.components.RangeUnitControl, {
								label: __("Size", 'greyd_hub'),
								value: _.has(props.attributes, 'icon.size') ? props.attributes.icon.size : '',
								units: [ 'px', '%', 'em' ],
								max: { 'px': 200, '%': 200, 'em': 10 },
								onChange: function(value) { props.setAttributes( { icon: { ...props.attributes.icon, size: value } } ); },
							} ),
							el( greyd.components.RangeUnitControl, {
								label: __("Space to the text", 'greyd_hub'),
								value: _.has(props.attributes, 'icon.size') ? props.attributes.icon.margin : '',
								units: [ 'px', '%', 'em' ],
								max: { 'px': 200, '%': 200, 'em': 10 },
								onChange: function(value) { props.setAttributes( { icon: { ...props.attributes.icon, margin: value } } ); },
								supportsPresets: true,
							} ), 
						]

					] ),
					// layout web
					(props.attributes.type == "web" || props.attributes.type == "num") && el( greyd.components.StylingControlPanel, {
						title: __("Position of the bullet points", 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						parentAttr: "web",
						blockProps: props,
						controls: [ 
							{
								label: __('Position', 'greyd_hub'),
								attribute: "position",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionLeft'), value: 'left' },
									{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionRight'), value: 'right' },
								],
							}, 
							{
								// hidden: !_.has(props.attributes, 'web.position') || props.attributes.web.position == "",
								hidden: { 
									lg: !props.attributes.web?.responsive?.lg?.position || props.attributes.web?.responsive?.lg?.position == "",
									md: !props.attributes.web?.responsive?.md?.position || props.attributes.web?.responsive?.md?.position == "",
									sm: !props.attributes.web?.responsive?.sm?.position || props.attributes.web?.responsive?.sm?.position == "",
								},
								label: __("Alignment", 'greyd_hub'),
								attribute: "align_y",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ label: __("Top", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYStart'), value: 'start' },
									// { label: __("Center to first line", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYFirst'), value: 'first' },
									{ label: __("Centered", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYCenter'), value: 'center' },
									{ label: __("Bottom", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYEnd'), value: 'end' },
								],
							} 
						]
					} ),
					// layout icon and image
					(props.attributes.type == "icon" || props.attributes.type == "img") && el( greyd.components.StylingControlPanel, {
						title: __("Position of the bullet points", 'greyd_hub'),
						initialOpen: true,
						supportsResponsive: true,
						parentAttr: "icon",
						blockProps: props,
						controls: [ 
							{
								label: __('Position', 'greyd_hub'),
								attribute: "position",
								control: greyd.components.ButtonGroupControl, // greyd.components.OptionsControl,
								options: [
									{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionLeft'), value: 'left' },
									{ label: __("Top", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionTop'), value: 'top' },
									{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionRight'), value: 'right' },
									{ label: __("Bottom", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listPositionBottom'), value: 'bottom' },
								],
							}, 
							{
								// hidden: !_.has(props.attributes, 'icon.position') || props.attributes.icon.position == "top" || props.attributes.icon.position == "bottom",
								hidden: { 
									'': !props.attributes.icon?.position || props.attributes.icon?.position == "top" || props.attributes.icon?.position == "bottom",
									lg: !props.attributes.icon?.responsive?.lg?.position || props.attributes.icon?.responsive?.lg?.position == "top" || props.attributes.icon?.responsive?.lg?.position == "bottom",
									md: !props.attributes.icon?.responsive?.md?.position || props.attributes.icon?.responsive?.md?.position == "top" || props.attributes.icon?.responsive?.md?.position == "bottom",
									sm: !props.attributes.icon?.responsive?.sm?.position || props.attributes.icon?.responsive?.sm?.position == "top" || props.attributes.icon?.responsive?.sm?.position == "bottom",
								},
								label: __("Alignment", 'greyd_hub'),
								attribute: "align_y",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ label: __("Top", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYStart'), value: 'start' },
									{ label: __("Center to first line", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYFirst'), value: 'first' },
									{ label: __("Centered", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYCenter'), value: 'center' },
									{ label: __("Bottom", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignYEnd'), value: 'end' },
								],
							}, 
							{
								// hidden: !_.has(props.attributes, 'icon.position') || props.attributes.icon.position == "left" || props.attributes.icon.position == "right",
								hidden: { 
									'': !props.attributes.icon?.position || props.attributes.icon?.position == "left" || props.attributes.icon?.position == "right",
									lg: !props.attributes.icon?.responsive?.lg?.position || props.attributes.icon?.responsive?.lg?.position == "left" || props.attributes.icon?.responsive?.lg?.position == "right",
									md: !props.attributes.icon?.responsive?.md?.position || props.attributes.icon?.responsive?.md?.position == "left" || props.attributes.icon?.responsive?.md?.position == "right",
									sm: !props.attributes.icon?.responsive?.sm?.position || props.attributes.icon?.responsive?.sm?.position == "left" || props.attributes.icon?.responsive?.sm?.position == "right",
								},
								label: __("Alignment", 'greyd_hub'),
								attribute: "align_x",
								control: greyd.components.ButtonGroupControl,
								options: [
									{ label: __("Left", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignXStart'), value: 'start' },
									{ label: __("Center", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignXCenter'), value: 'center' },
									{ label: __("Right", 'greyd_hub'), icon: greyd.tools.getCoreIcon('listAlignXEnd'), value: 'end' },
								],
							} 
						]
					} ),
				] ), 
				el( 'style', { className: 'greyd_styles' }, style ),
				el( props.attributes.type == "num" ? 'ol' : 'ul', { 
					'data-ul': props.clientId,
					id: props.attributes.anchor, 
					className: props.className,
					reversed: props.attributes.type == "num" && _.has(props.attributes, 'web.reverse') && props.attributes.web.reverse == true ? true : undefined,
					start: props.attributes.type == "num" && _.has(props.attributes, 'web.start') ? props.attributes.web.start : undefined,
				}, [
					el( wp.blockEditor.InnerBlocks, {
						template: [ ['greyd/list-item', {}], ['greyd/list-item', {}] ],
						// templateLock: false,
						allowedBlocks: [ 'greyd/list', 'greyd/list-item', 'greyd/box', 'core/group' ]
					} )
				] )
			];
		},
		save: function( props ) {
			var type = 'ul';
			var atts = { 
				id: props.attributes.anchor, 
				className: props.className 
			};
			if (props.attributes.type == "num") {
				if (_.has(props.attributes, 'web.reverse') && props.attributes.web.reverse == true) {
					type = 'ol';
					atts.reversed = true;
				}
				if (_.has(props.attributes, 'web.start') && props.attributes.web.start != '') {
					type = 'ol';
					atts.start = props.attributes.web.start;
				}
			}
			return el( type, atts, el( wp.blockEditor.InnerBlocks.Content ) );
		},

		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'core/list' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert core list to greyd list');
						// console.log(attributes);
						// console.log(innerBlocks);

						var parse_list = function(list) {
							var items = [];
							for (var i=0; i<list.childNodes.length; i++) {
								if (list.childNodes[i].nodeName == 'LI') {
									var c = "";
									var nest = [];
									for (var j=0; j<list.childNodes[i].childNodes.length; j++) {
										if (list.childNodes[i].childNodes[j].nodeName == 'UL' ||
											list.childNodes[i].childNodes[j].nodeName == 'OL') {
											// console.log("nest found");
											nest = parse_list(list.childNodes[i].childNodes[j]);
										}
										else if (list.childNodes[i].childNodes[j].nodeName == '#text') {
											// console.log("plain text found");
											c += list.childNodes[i].childNodes[j].textContent;
										}
										else {
											// console.log("formated text found");
											c += list.childNodes[i].childNodes[j].outerHTML;
										}
									}
									if (!_.isEmpty(c)) {
										items.push(wp.blocks.createBlock(
											'greyd/list-item',
											{ content: c }
										));
									}
									if (!_.isEmpty(nest)) {
										items.push(wp.blocks.createBlock(
											'greyd/list',
											newatts,
											nest
										));
									}
								}
							}
							return items;
						}

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						newatts.type = 'web';
						if (_.has(attributes, 'ordered') && attributes.ordered == true) {
							newatts.type = 'num';
							newatts.web = { style: 'decimal' };
						}

						var inner = [];
						if (_.has(attributes, 'values') && attributes.values != '') {

							var list = document.createElement("div");
							list.innerHTML = attributes.values;
							// console.log(list);
							inner = parse_list(list);
							// console.log(inner);

						}

						return wp.blocks.createBlock(
							'greyd/list',
							newatts,
							inner
						);
					},
				},

				{
					type: 'block',
					blocks: [ 'greyd/list-item' ],
					isMultiBlock: true,
					__experimentalConvert: function(innerBlocks) {
						var parent = greyd.tools.list.getDirectParent(innerBlocks[0].clientId, 'greyd/list');
						if (!parent) {
							console.log("no parent list found");
							return innerBlocks[0];
						}
						console.log('__experimentalConvert * to nested list');
						// console.log(innerBlocks);

						var inner = innerBlocks.map( ( block ) => {
							return wp.blocks.createBlock(
								block.name,
								block.attributes,
								block.innerBlocks
							);
						} );

						return wp.blocks.createBlock(
							'greyd/list',
							parent.attributes,
							inner
						);
					},
				}
			],
			to: [
				{
					type: 'block',
					blocks: [ 'core/list' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert greyd list to core list');
						// console.log(attributes);
						// console.log(innerBlocks);

						var make_rt_value = function(value) {
							if (typeof value === 'string') return value;
							var string = "";
							value.forEach(function(item, i) {
								if (typeof item === 'object') {
									var tag = item.type;
									var atts = "";
									Object.keys(item.props).forEach(function(key, j) {
										if (key != 'children') {
											atts += ' '+key+'="'+item.props[key]+'"';
										}
									});
									var children = make_rt_value(item.props.children);
									string += '<'+tag+atts+'>'+children+'</'+tag+'>';
								}
								if (typeof item === 'string') string += item;
							});
							return string;
						}
						var make_value = function(inner) {
							var value = "";
							for (var i=0; i<inner.length; i++) {
								// console.log(inner[i]);
								var content = "";
								if (_.has(inner[i], 'attributes.content')) {
									// convert richtext tags
									content = make_rt_value(inner[i].attributes.content);
								}
								if (_.has(inner, i+1) && inner[i+1].name == 'greyd/list') {
									i++;
									// console.log(inner[i]);
									var sub = make_value(inner[i].innerBlocks);
									if (sub != "") {
										content += '<ul>'+sub+'</ul>';
									}
								}
								value += '<li>'+content+'</li>';
							}
							return value;
						}

						var newatts = greyd.tools.transformDefaultAtts(attributes);
						if (_.has(attributes, 'type') && (attributes.type == 'web' || attributes.type == 'num')) {
							var nums = [ 'decimal', 'decimal-leading-zero', 'lower-roman', 'upper-roman' ];
							if (_.has(attributes, 'web') && nums.indexOf(attributes.web.style) > -1) {
								newatts.ordered = true;
							}
						}
						var values = make_value(innerBlocks);
						if (values != "") newatts.values = values;

						return wp.blocks.createBlock(
							'core/list',
							newatts,
							[]
						);
					},
				}
			]
		}
	} );


	/**
	 * Register List Item Block.
	 */
	wp.blocks.registerBlockType( 'greyd/list-item', {
		apiVersion: 2,
		title: __("List element", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('listItem'),
		category: 'greyd-blocks',
		parent: [ 'greyd/list' ],

		supports: {
			className: false,
			customClassName: false,
			typography: {
				fontSize: true,
				__experimentalDefaultControls: {
					fontSize: true
				}
			},
		},
		attributes: {
			content: {
				type: 'String', 					  // changed from 'array',
				source: 'html', 					  // changed from 'children',
				selector: 'p',
				default: ""
			},
			type: { type: 'string', default: "" },
			icon: { type: 'string', default: "" },
			indent: { type: 'number' }, 			  // new indent
			custom: { type: 'boolean' }, 			  // new override trigger
			override: { type: 'object', properties: { // new override elements
				style: { type: 'string' }, 				// for web/num
				icon: { type: 'string' }, 				// for icon
				color: { type: 'string' }, 				// for icon
				url: { type: 'string' }, 				// for img
				id: { type: 'string' }, 				// for img
			} }
		},
		usesContext: [
			'greyd/list-type', 
			'greyd/list-web', 
			'greyd/list-icon',
		],

		edit: function( props ) {
			// check context
			if ( JSON.stringify(props.context) == '{}' ) {
				var parent = greyd.tools.list.getDirectParent(props.clientId, 'greyd/list')
				if (parent) {
					// console.log(parent);
					props.context = {
						'greyd/list-type': parent.attributes.type, 
						'greyd/list-web': parent.attributes.web, 
						'greyd/list-icon': parent.attributes.icon,
					};
				}
			}
			// console.log(props.context);

			// handle overrides in state

			const listType = props.context['greyd/list-type'];
			const overrides = props.attributes.override;

			const getOverrides = function() {
				let override = {};
				if (listType == 'web' && _.has(overrides, 'style')) override.style = overrides.style;
				if (listType == 'num' && _.has(overrides, 'style')) override.style = overrides.style;
				if (listType == 'icon' && _.has(overrides, 'icon')) override.icon = overrides.icon;
				if (listType == 'icon' && _.has(overrides, 'color')) override.color = overrides.color;
				if (listType == 'img' && _.has(overrides, 'id')) override.id = overrides.id;
				if (listType == 'img' && _.has(overrides, 'url')) override.url = overrides.url;
				return override;
			}

			// changed context
			let newAtts = {};
			if (props.attributes.type != listType) {
				newAtts.type = listType;
				if (props.attributes.custom) {
					newAtts.override = getOverrides();
				}
			}
			if ( listType == 'icon' && typeof props.context['greyd/list-icon'] !== 'undefined' && _.has(props.context['greyd/list-icon'], 'icon') ) {
				if ( props.attributes.icon != props.context['greyd/list-icon'].icon ) {
					newAtts.icon = props.context['greyd/list-icon'].icon;
				}
			}
			if ( !_.isEmpty(newAtts) ) {
				props.setAttributes( newAtts );
			}

			// icon for rendering
			let icon = "";
			if (props.attributes.type != '') {
				let icon_Class = [ 'list_icon' ];
				let style = {};
				if (props.attributes.type == 'icon') {
					if (props.attributes.custom && _.has(overrides, 'icon'))
						icon_Class.push(overrides.icon);
					else icon_Class.push(props.attributes.icon);
					if (props.attributes.custom && _.has(overrides, 'color')) {
						let col = overrides.color;
						if (col.indexOf("color-") === 0) col = 'var(--'+col.replace('-', '')+')';
						style = { style: { color: col } };
					}
				}
				if (props.attributes.type == 'img') {
					if (props.attributes.custom && _.has(overrides, 'url')) {
						style = { style: { backgroundImage: 'url("'+overrides.url+'")' } };
					}
				}
				icon = el( 'span', { className: icon_Class.join(' '), ...style } );
			}

			// events
			const onMerge = function() {
				if (_.has(props.attributes, "dynamic_parent")) return;
				// console.log("merge");
				let parentBlocks = wp.data.select( 'core/block-editor' ).getBlockParents(props.clientId);
				let order = wp.data.select( 'core/block-editor' ).getBlockOrder(parentBlocks[parentBlocks.length-1]);
				let index = order.indexOf(props.clientId);
				let pos = greyd.tools.list.getCursorPosition();
				// console.log(pos);
				if (pos == 0 && index > 0) {
					// console.log("... to previous item");
					let sibling = wp.data.select('core/block-editor').getBlock(order[index-1]);
					let newpos = sibling.attributes.content.replace(/<\/?[^>]+>/g, "").length;
					// console.log(newpos);
					let content = sibling.attributes.content+props.attributes.content;
					wp.data.dispatch('core/block-editor').updateBlockAttributes(sibling.clientId, { content: content });
					wp.data.dispatch('core/block-editor').removeBlock(props.clientId);
					setTimeout(() => { greyd.tools.list.setCursorPosition(newpos) }, 0);
				}
				else if (pos > 0 && index < order.length-1) {
					// console.log("... from next item");
					let sibling = wp.data.select('core/block-editor').getBlock(order[index+1]);
					let content = props.attributes.content+sibling.attributes.content;
					wp.data.dispatch('core/block-editor').removeBlock(sibling.clientId);
					props.setAttributes( { content: content } ); 
				}
				// else console.log("... not possible");
			}

			const onSplit = function(event) {
				// console.log("split to new listitem");
				// console.log(event);
				// get context
				let parentBlocks = wp.data.select( 'core/block-editor' ).getBlockParents(props.clientId);
				let parent = parentBlocks[parentBlocks.length-1];
				let order = wp.data.select( 'core/block-editor' ).getBlockOrder(parent);
				let index = order.indexOf(props.clientId)+1;
				// get content parts
				let [ first_part, second_part ] = wp.richText.split(event.value);
				// set this block with fist part
				// props.setAttributes( { content: wp.richText.toHTMLString({ value: first_part }) } ); 
				event.onChange(first_part);
				// make new list-item block with second part
				let newItem = wp.blocks.createBlock( 'greyd/list-item', { ...props.attributes, content: wp.richText.toHTMLString({ value: second_part }) } );
				wp.data.dispatch('core/block-editor').insertBlock(newItem, index, parent);
			}

			const onIndent = function(event) {
				// console.log("indent");
				// console.log(event);
				let indent = _.has(props.attributes, 'indent') ? props.attributes.indent : 0;
				if (indent < 9999) props.setAttributes( { indent: indent+1 } ); /* indent not limited -> tbd */
				else console.log("... not possible");
				event.onFocus();
			}

			const onOutdent = function(event) {
				// console.log("outdent");
				// console.log(event);
				let indent = _.has(props.attributes, 'indent') ? props.attributes.indent : 0;
				if (indent > 0) props.setAttributes( { indent: indent-1 } ); 
				else console.log("... not possible");
				event.onFocus();
			}

			const shortcuts = function(event) {
				// let { value, onChange, onFocus } = event;
				if (_.has(props.attributes, "dynamic_parent")) return [];
				else return [
					el( wp.blockEditor.RichTextShortcut, {
						// type: "shift",
						character: "Enter",
						onUse: () => onSplit(event)
					} ),
					el( wp.blockEditor.RichTextShortcut, {
						type: "shift",
						character: "Tab",
						onUse: () => onOutdent(event)
					} ),
					el( wp.blockEditor.RichTextShortcut, {
						// type: "shift",
						character: "Tab",
						onUse: () => onIndent(event)
					} ),
					// toolbar
					el( wp.blockEditor.BlockControls, { group: 'block' }, [
						el( wp.components.ToolbarButton, {
							label: __('Outdent', 'greyd_hub'),
							icon: greyd.tools.getCoreIcon('formatOutdent'),
							isDisabled: !(_.has(props.attributes, 'indent') && props.attributes.indent > 0),
							onClick: () => onOutdent(event),
						} ),
						el( wp.components.ToolbarButton, {
							label: __('Indent', 'greyd_hub'),
							icon: greyd.tools.getCoreIcon('formatIndent'),
							isDisabled: false, /* indent not limited -> tbd */
							onClick: () => onIndent(event),
						} )
					] )
				];
			}

			// call function to make sure Block is updated when inside a template
			const blockProps = wp.blockEditor.useBlockProps();

			return [
				el( wp.blockEditor.InspectorControls, {}, [
					props.attributes.type != '' && el( greyd.components.AdvancedPanelBody, { 
						title: __("Style", 'greyd_hub'), 
						initialOpen: true, 
						holdsChange: props.attributes.custom
					}, [
						el( wp.components.ToggleControl, {
							label: __( "Overwrite style", 'greyd_hub' ),
							checked: props.attributes.custom,
							onChange: function(value) {
								if (value) {
									let override = getOverrides();
									props.setAttributes( { custom: true, override: override } );
								}
								else props.setAttributes( { custom: false, override: {} } );
							},
						} ),
						// overrides
						props.attributes.custom && [
							// web style
							(props.attributes.type == 'web' || props.attributes.type == 'num') && [
								el( wp.components.SelectControl, {
									// label: __("Style", 'greyd_hub'),
									value: _.has(overrides, 'style') ? overrides.style : props.context['greyd/list-web'].style,
									options: greyd.tools.list.getListStyleOptions(props.attributes.type),
									onChange: function(value) { 
										// console.log(value);
										let override = { style: value };
										props.setAttributes( { override: override } ); 
										// setOverrides( { ...overrides, ...override } );
									},
								} ),
							],
							// icon
							props.attributes.type == 'icon' && [
								el( greyd.components.IconPicker, {
									value: _.has(overrides, 'icon') ? overrides.icon : props.context['greyd/list-icon'].icon,
									icons: greyd.data.icons,
									onChange: function(value) { 
										// console.log(value);
										let override = { icon: value };
										if (_.has(props.attributes, 'override.color')) override.color = props.attributes.override.color;
										props.setAttributes( { override: override } ); 
										// setOverrides( { ...overrides, ...override } );
									},
								} )
							],
							// image
							props.attributes.type == 'img' && [
								el( wp.components.BaseControl, { }, [
									el( wp.blockEditor.MediaUploadCheck, { fallback: el( 'p', { className: "greyd-inspector-help" }, __("To edit the background image, you need permission to upload media.", 'greyd_hub') ) }, [
										el( wp.blockEditor.MediaUpload, {
											value: _.has(overrides, 'id') ? overrides.id : props.context['greyd/list-icon'].id,
											onSelect: function(value) { 
												let override = { id: value.id, url: value.url };
												props.setAttributes( { override: override } ); 
												// setOverrides( { ...overrides, ...override } );
											},
											render: function(obj) {
												let id = _.has(overrides, 'id') ? overrides.id : props.context['greyd/list-icon'].id;
												let url = _.has(overrides, 'url') ? overrides.url : props.context['greyd/list-icon'].url;
												return el( wp.components.Button, { 
													className: id == -1 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
													onClick: obj.open 
												}, id == -1 ? __( "Select image", 'greyd_hub' ) : el( 'img', { src: url } ) )
											},
										} ),
										_.has(overrides, 'id') && el( wp.components.Button, { 
											className: "is-link is-destructive",
											onClick: function() { 
												props.setAttributes( { override: {} } );
												// let override = overrides;
												// delete(overrides.id);
												// delete(overrides.url);
												// setOverrides( override );
											},
										}, __( "Reset image", 'greyd_hub' ) )
									] ),
								] ),
							],
							// text color tbd
							// icon color
							props.attributes.type == 'icon' && [
								el( greyd.components.ColorGradientPopupControl, {
									mode: 'color',
									label: __("Icon color", "greyd_hub"),
									value: _.has(overrides, 'color') ? overrides.color : props.context['greyd/list-icon'].color,
									onChange: function(value) { 
										// console.log(value);
										let override = { color: value };
										if (_.has(props.attributes, 'override.icon')) override.icon = props.attributes.override.icon;
										props.setAttributes( { override: override } ); 
										// setOverrides( { ...overrides, ...override } );
									},
								} ),
							],
						]
					] )
				] ),
				el( 'div', { ...blockProps }, [
					el( 'li', {
						style: { 
							marginLeft: 'calc('+(_.has(props.attributes, 'indent') ? props.attributes.indent : 0)+' * var(--indent))',
							listStyle: props.attributes.custom && _.has(props.attributes, 'override.style') ? props.attributes.override.style : 'inherit',
						}
					}, [
						icon,
						el( 'span', { className: 'list_content' }, [
							el( wp.blockEditor.RichText, {
								tagName: 'p',
								// multiline: "br",
								value: props.attributes.content,
								placeholder: __( "List item", 'greyd_hub' ),
								onChange: function(value) { 
									// console.log("change");
									props.setAttributes( { content: value } ); 
								},
								onMerge: onMerge,
							}, shortcuts )
						] )
					] )
				] )
			];
		},
		save: function( props ) {
			// console.log(props)
			let icon = "";
			if (props.attributes.type != '') {
				let icon_Class = [ 'list_icon' ];
				let color = {};
				if (props.attributes.type == 'icon') {
					if (props.attributes.custom && _.has(props.attributes, 'override.icon'))
						icon_Class.push(props.attributes.override.icon);
					else icon_Class.push(props.attributes.icon);
					if (props.attributes.custom && _.has(props.attributes, 'override.color')) {
						let col = props.attributes.override.color;
						// console.log( col );
						if ( col.indexOf("color-") === 0 ) {
							col = 'var(--'+col.replace('-', '')+')';
						} else if ( col.indexOf('var(u002du002d') === 0 ) {
							col = col.replace('var(u002du002d', 'var(--');
						}
						color = { style: { color: col } };
					}
				}
				icon = el( 'span', { className: icon_Class.join(' '), ...color } );
			}
			return el( 'li', {}, [
					icon,
					el( 'span', { className: 'list_content' }, [
						el( wp.blockEditor.RichText.Content, {
							tagName: 'p',
							value: props.attributes.content,
						} )
					] )
				]
			);
		},

		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'greyd/list' ],
					transform: function ( attributes, innerBlocks ) {
						console.log('convert nested list to list-item');
						// console.log(attributes);
						// console.log(innerBlocks);

						let values = [];
						for (let i=0; i<innerBlocks.length; i++) {
							// console.log(innerBlocks[i]);
							if (_.has(innerBlocks[i], 'attributes.content')) {
								values.push(innerBlocks[i].attributes.content);
							}
						}

						return wp.blocks.createBlock(
							'greyd/list-item',
							{ content: values.join('<br>') }
						);
					}
				}
			],
			to: []
		}
	} );


	/**
	 * Add custom edit controls to core blocks.
	 *
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {

			/**
			 * Group & Content-Box support for greyd/list.
			 */
			if (props.name == "core/group" || props.name == "greyd/box") {
				// console.log("add list support to: "+props.name);

				var parent = greyd.tools.list.getDirectParent(props.clientId, 'greyd/list')
				if (parent) {
					var iconclass = parent.attributes.type == 'icon' && _.has(parent.attributes, 'icon.icon') ? parent.attributes.icon.icon : '';
					if (props.name == "core/group") {
						return el( wp.element.Fragment, { }, [
							el( 'div', { 'data-type': 'greyd/list-group' },
								el( 'li', {},
									el( 'span', { className: 'list_icon '+iconclass } ), 
									el( 'span', { className: 'list_content' }, el( BlockEdit, props ) ) 
								)
							)
						] );
					}
					if (props.name == "greyd/box") {
						return el( wp.element.Fragment, { }, [
							el( 'li', {},
								el( 'span', { className: 'list_icon '+iconclass } ), 
								el( 'span', { className: 'list_content' }, el( BlockEdit, props ) ) 
							)
						] );
					}
				}

			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/list/edit',
		editBlockHook
	);

} )( window.wp );