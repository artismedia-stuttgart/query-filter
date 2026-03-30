/**
 * Handle tranformation from shortcode to blocks.
 */
( function( wp ) {

	var _ = lodash;

	// maps
	var shortcodeMap = {
		// to core columns
		'vc_section': 		{ block: "core/group" },
		'vc_row': 			{ block: "core/columns" },
		'vc_row_inner': 	{ block: "core/columns", atts: { row_type: "row_xxxl" } },
		// to core column
		'vc_column': 		{ block: "core/column" },
		'vc_column_inner': 	{ block: "core/column" },
		// to core blocks
		'vc_column_text': 	{ block: "core/paragraph" },
		'vc_video': 		{ block: "core/embed" },
		'vc_multimedia': 	{ block: "core/embed" },
		'vc_headlines': 	{ block: "core/heading" },
		'vc_blank_space': 	{ block: "core/spacer" },
		'vc_raw_html': 		{ block: "core/html" },
		'vc_raw_js': 		{ block: "core/html" },
		'vc_icons': 		{ block: "core/image" },
		'vc_smshare': 		{ block: "core/social-links", atts: { mode: 'share' } },
		'vc_smchannels': 	{ block: "core/social-links", atts: { mode: 'channels' } },
		'vc_search_form': 	{ block: "core/search" }, // deprecated
		'vc_search': 		{ block: "core/search" },
		'vc_gutenberg': 	{ block: "core/group" },
		// to greyd blocks
		'vc_cbutton_anchor':{ block: "greyd/anchor" },
		'vc_content_box': 	{ block: "greyd/box" },
		'vc_cbutton': 		{ block: "greyd/button", wrapper: "greyd/buttons" },
		'vc_list': 			{ block: "greyd/list" },
		'vc_list_item': 	{ block: "greyd/list-item" },
		'vc_cond_content':	{ block: "core/group", atts: {} },
		'vc_cond_content_item': { block: "greyd/conditional-content" },
		// 'vc_tta_accordion':	{ block: "greyd/accord" },
		// 'vc_tta_section': 	{ block: "greyd/accord-item" },
		'vc_counter': 		{ block: "greyd/counter" },
		'vc_copyright': 	{ block: "greyd/copyright" },
		'vc_close': 		{ block: "greyd/popup-close" },
		'vc_footernav': 	{ block: "greyd/menu" },
		'vc_dropdownnav': 	{ block: "greyd/menu" },
		// to greyd forms blocks
		'vc_form': 			{ block: "greyd/form" },
		'vc_form_input': 		{ block: "greyd-forms/input"},
		'vc_form_checkbox': 	{ block: "greyd-forms/checkbox"},
		'vc_form_captcha': 		{ block: "greyd-forms/recaptcha"},
		'vc_form_hidden': 		{ block: "greyd-forms/hidden-field"},
		'vc_form_radio': 		{ block: "greyd-forms/radiobuttons"},
		'vc_form_button': 		{ block: "greyd-forms/submitbutton"},
		'vc_form_upload': 		{ block: "greyd-forms/upload"},
		'vc_form_icon_panels': 	{ block: "greyd-forms/iconpanels"},
		'vc_form_icon_panel': 	{ block: "greyd-forms/iconpanel"},
		'vc_form_conditional': 	{ block: "core/group", atts: {} },
		'vc_form_dropdown': 	{ block: "greyd-forms/dropdown"},

		// to greyd template blocks
		'dynamic': 			{ block: "greyd/dynamic" },
		'vc_posts_links':	{ block: "core/archives" },
		'vc_posts_overview':{ block: "core/query" },
		'vc_posts_tp':		{ block: "core/query", atts: { inherit: true } },
		'vc_post_tp': 		{ block: "core/query", atts: { inherit: true, single: true } },
		'vc_posts_details_tp': { block: "core/query", atts: { inherit: true, details: true } },
	};

	var shortcodeMapVC = {
		// vc other
		'vc_empty_space': 	 { block: "core/spacer" },
		'vc_custom_heading': { block: "core/heading" },
		'vc_btn': 			 { block: "greyd/button", wrapper: "greyd/buttons" },

		'vc_separator': 	 { block: "core/separator" },
		'vc_text_separator': { block: "core/separator" },
		'vc_zigzag': 		 { block: "core/separator", atts: { zigzag: true } },

		'vc_single_image':	 	 { block: "greyd/image" },
		'vc_gallery':	 		 { block: "core/gallery" },
		'vc_images_carousel':	 { block: "core/gallery" },
		'vc_media_grid':		 { block: "core/gallery" },
		'vc_masonry_media_grid': { block: "core/gallery" },

		'vc_cta':			 { block: "greyd/box", atts: { cta: true } },
		'vc_message':		 { block: "greyd/box", atts: { message: true } },
		'vc_hoverbox':		 { block: "greyd/box", atts: { hoverbox: true } },

		'vc_toggle':		 { block: "greyd/box", atts: { toggle: true } },
		'vc_tta_tabs':	 	 { block: "greyd/box", atts: { tabs: true } },
		'vc_tta_tour':	 	 { block: "greyd/box", atts: { tour: true } },
		'vc_tta_accordion':	 { block: "greyd/box", atts: { accordion: true } },
		'vc_tta_pageable':	 { block: "greyd/box", atts: { pageable: true } },
		'vc_tta_section':	 { block: "greyd/box", atts: { section: true } },
		
		'vc_posts_slider':	 { block: "core/query", atts: { slider: true } },
		'vc_basic_grid':	 { block: "core/query" },
		'vc_masonry_grid':	 { block: "core/query" },

		'vc_gmaps':	 		 { block: "greyd/iframe", atts: { maps: true } },
		'vc_facebook': 		 { block: "greyd/iframe", atts: { fb: true } },
		'vc_tweetmeme':		 { block: "greyd/iframe", atts: { twitter: true } },
		'vc_flickr': 		 { block: "core/embed" },

		'vc_wp_search':	 	 { block: "core/search" },
		'vc_wp_meta':	 	 { block: "core/group" },
		'vc_wp_recentcomments': { block: "core/latest-comments" },
		'vc_wp_calendar':	 { block: "core/calendar" },
		'vc_wp_pages':	 	 { block: "core/page-list" },
		'vc_wp_tagcloud':	 { block: "core/tag-cloud" },
		'vc_wp_custommenu':	 { block: "greyd/menu" },
		'vc_wp_text':	 	 { block: "core/paragraph" },
		'vc_wp_posts':	 	 { block: "core/latest-posts" },
		'vc_wp_categories':	 { block: "core/categories" },
		'vc_wp_archives':	 { block: "core/archives" },
		'vc_wp_rss':	 	 { block: "core/rss" },

	};

	var attsMapVC = {
		"core/spacer":		[ { compute: function(atts) {
								    // common atts
									atts = computeVcBasicAtts(atts);
									// height = height
									if (!_.has(atts, "height")) atts["height"] = "32px"; // vc default
									return atts;
							  } } ],
		"core/heading":		[ { compute: function(atts) {
									// common atts
									return computeVcHeadingAtts(atts);
							  } } ],
		"greyd/button":		[ { compute: function(atts) {
								    // common atts
									return computeVcButtonAtts(atts);
							  } } ],
		"core/separator":	[ { transform: function(atts) { 
								    if (_.has(atts, "title")) {
										// wrap in group
										return "core/group";
									}
								    else if (_.has(atts, "zigzag")) {
										// columns background seperator
										return "core/columns";
									}
									return "core/separator";
							  } },
							  { inner: function(atts) { 
								  	var items = [];
									if (_.has(atts, "title")) {
										// text and seperator in group
										var sep_atts = computeVcSeperatorAtts(atts);
										sep_atts["greydStyles"]["width"] = (parseInt(sep_atts["greydStyles"]["width"].replace('%', '')) - 5)+'%';
										
										items = [
											wp.blocks.createBlock( "core/paragraph", { 
												content: atts["title"], 
												textAlign: 'center',
												inline_css: "white-space: nowrap !important; min-width: unset !important;"
											} )
										];
										if (_.has(atts, "title_align") && atts["title_align"] == "separator_align_left") {
											items.push(wp.blocks.createBlock( "core/separator", sep_atts ));
										}
										else if (_.has(atts, "title_align") && atts["title_align"] == "separator_align_right") {
											items.unshift(wp.blocks.createBlock( "core/separator", sep_atts ));
										}
										else {
											sep_atts["greydStyles"]["width"] = (parseInt(sep_atts["greydStyles"]["width"].replace('%', '')) / 2)+'%';
											items.unshift(wp.blocks.createBlock( "core/separator", sep_atts ));
											items.push(wp.blocks.createBlock( "core/separator", sep_atts ));
										}
									}
								    else if (_.has(atts, "zigzag")) {
										// make empty column with spacer
										var height = _.has(atts, "el_border_width") ? atts["el_border_width"]+'px' : "8px";
										items = [
											wp.blocks.createBlock( "core/column", { }, [
												wp.blocks.createBlock( "core/spacer", { height: height } )
											] )
										];
									}
									return items;
							  } },
							  { compute: function(atts) {
								    // common atts
									atts = computeVcBasicAtts(atts);
									
									if (_.has(atts, "title")) {
										// group row layout atts
										atts = {
											anchor: _.has(atts, "anchor") ? atts['anchor'] : undefined,
											className: _.has(atts, "className") ? atts['className'] : undefined,
											inline_css: "min-width: 100%; "+(_.has(atts, "inline_css") ? atts['inline_css'] : ''),
											css_animation: _.has(atts, "css_animation") ? atts['css_animation'] : undefined,
											layout: {
												type: "flex",
												allowOrientation: false,
												flexWrap: "nowrap",
												justifyContent: "center"
											}
										};
									}
								    else if (_.has(atts, "zigzag")) {
										// atts for columns with seperator
										// color
										var color = _.has(atts, "color") ? computeVcColor(atts, 'color', 'custom_color') : '';
										var height = _.has(atts, "el_border_width") ? atts["el_border_width"]+'px' : "8px";
										atts = {
											anchor: _.has(atts, "anchor") ? atts['anchor'] : undefined,
											className: _.has(atts, "className") ? atts['className'] : undefined,
											inline_css: _.has(atts, "inline_css") ? atts['inline_css'] : undefined,
											css_animation: _.has(atts, "css_animation") ? atts['css_animation'] : undefined,
											background: {
												seperator: {
													type: "zigzag_seperator",
													height: height,
													color: color
												}
											}
										};
										
									}
									else {
										// compute simple seperator
										atts = computeVcSeperatorAtts(atts);
									}

									return atts;
							  } } ],

		"greyd/image":		[ { transform: function(atts) { 
								    if (_.has(atts, "title")) {
										// wrap in group
										return "core/group";
									}
									return "greyd/image";
							  } },
							  { inner: function(atts) { 
								  	var items = [];
									if (_.has(atts, "title")) {
										// title
										if (_.has(atts, "title")) {
											var align = _.has(atts, "alignment") ? atts['alignment'] : "center";
											items.push(wp.blocks.createBlock( "core/heading", { 
												content: atts["title"], 
												textAlign: align,
												level: 4
											} ));
										}
										// image
										items.push(wp.blocks.createBlock( "greyd/image", computeVcImageAtts(atts) ));
									}
									return items;
							  } },
							  { compute: function(atts) {
								    // common atts
									atts = computeVcBasicAtts(atts);

								    if (_.has(atts, "title")) {

										// group atts
										atts = {
											anchor: _.has(atts, "anchor") ? atts['anchor'] : undefined,
											className: _.has(atts, "className") ? atts['className'] : undefined,
											inline_css: "min-width: 100%; "+(_.has(atts, "inline_css") ? atts['inline_css'] : ''),
											css_animation: _.has(atts, "css_animation") ? atts['css_animation'] : undefined,
										};

									}
									else {
										// compute simple image
										atts = computeVcImageAtts(atts);
									}
									
									return atts;
							  } } ],
		"core/gallery":		[ { transform: function(atts) { 
								    if (_.has(atts, "title")) {
										// wrap in group
										return "core/group";
									}
									return "core/gallery";
							  } },
							  { inner: function(atts) { 
									var imgs = [];
								  	var click = 'media';
									var links = [];
									var target = false;

									// get images
									if (_.has(atts, "source") && atts["source"] == "external_link") {
										// external_link
										if (_.has(atts, "custom_srcs")) {
											// console.log(atts["custom_srcs"]);
											imgs = decodeURIComponent(atob(atts["custom_srcs"].replace('#E\-8_', ''))).split(',');
											delete atts['custom_srcs'];
										}
										delete atts['source'];
									}
									else if (_.has(atts, "images")) {
										// media_library -> default
										imgs = atts["images"].split(',');
										delete atts['images'];
									}
									if (_.has(atts, "include")) {
										// media_grid and masonry_media_grid
										click = "media";
										imgs = atts["include"].split(',');
										delete atts['include'];
									}

									// click event
									if (_.has(atts, "onclick")) {
										if (atts["onclick"] == "") click = "none";
										else if (atts["onclick"] != "custom_link") click = "media";
										else {
											click = "custom";
											if (_.has(atts, "custom_links")) {
												// console.log(atts["custom_links"]);
												links = decodeURIComponent(atob(atts["custom_links"].replace('#E\-8_', ''))).split(',');
												delete atts['custom_links'];
											}
										}
										if (_.has(atts, "custom_links_target") && atts["custom_links_target"] == "_blank") {
											target = "_blank";
											delete atts['custom_links_target'];
										}
										delete atts['onclick'];
									}

									// make gallery images
								  	var items = [];
									for (var i=0; i<imgs.length; i++) {
										var img_atts = {};
										// image
										if (parseInt(imgs[i]) == imgs[i]) {
											if (_.has(greyd.data.media_urls, imgs[i])) {
												// get image url
												img_atts.url = greyd.data.media_urls[imgs[i]].src;
												img_atts.id = imgs[i];
											}
										}
										else img_atts.url = imgs[i];
										// click
										if (click != "none") {
											img_atts.linkDestination = click;
											if (click == "media")
												img_atts.href = img_atts.url;
											else if (click == "custom" && _.has(links, i))
												img_atts.href = links[i];
											if (target) 
												img_atts.linkTarget = target;
										}
										items.push(wp.blocks.createBlock( "core/image", img_atts ));
									}

									// wrap in group
									if (_.has(atts, "title")) {
										// title
										var title_block = wp.blocks.createBlock( "core/heading", { 
											content: atts["title"],
											level: 4
										} );
										// gallery
										var gallery_block = wp.blocks.createBlock( "core/gallery", computeVcGalleryAtts(atts), items );
										items = [ title_block, gallery_block ];
									}
									return items;
							  } },
							  { compute: function(atts) {
									// css_animation
									if (_.has(atts, "initial_loading_animation")) {
										atts['css_animation'] = atts['initial_loading_animation'];
										delete atts['initial_loading_animation'];
									}
								    // common atts
									atts = computeVcBasicAtts(atts);

								    if (_.has(atts, "title")) {
										// group atts
										atts = {
											anchor: _.has(atts, "anchor") ? atts['anchor'] : undefined,
											className: _.has(atts, "className") ? atts['className'] : undefined,
											inline_css: "min-width: 100%; "+(_.has(atts, "inline_css") ? atts['inline_css'] : ''),
											css_animation: _.has(atts, "css_animation") ? atts['css_animation'] : undefined,
										};
									}
									else {
										// compute gallery
										atts = computeVcGalleryAtts(atts);
									}
									
									return atts;
							  } } ],

		"greyd/box":		[ { transform: function(atts) { 
									// console.log(atts);
								    if (_.has(atts, "accordion")) {
										// convert to accordion
										return "greyd/accordion";
									}
									return "greyd/box";
								} },
							  { key: "content", fill: function(inner) { return inner.trim().split('\n').join('<br>') } },
							  { inner: function(atts, inner) { 
								  	var makeHeadingAtts = function(pre, atts) {
										var h_atts = {};
										if (_.has(atts, pre+"link")) 			h_atts["link"] = atts[pre+"link"];
										if (_.has(atts, pre+"font_container"))	h_atts["font_container"] = atts[pre+"font_container"];
										if (_.has(atts, pre+"css_animation"))	h_atts["css_animation"] = atts[pre+"css_animation"];
										if (_.has(atts, pre+"el_id")) 			h_atts["el_id"] = atts[pre+"el_id"];
										if (_.has(atts, pre+"el_class")) 		h_atts["el_class"] = atts[pre+"el_class"];
										return h_atts;
									};
									var makeButtonAtts = function(pre, atts) {
										var b_atts = {};
										if (_.has(atts, pre+"title"))				b_atts["title"] = atts[pre+"title"];
										if (_.has(atts, pre+"link"))				b_atts["link"] = atts[pre+"link"];
										if (_.has(atts, pre+"size"))				b_atts["size"] = atts[pre+"size"];
										if (_.has(atts, pre+"align"))				b_atts["align"] = atts[pre+"align"];
										if (_.has(atts, pre+"shape"))				b_atts["shape"] = atts[pre+"shape"];
										if (_.has(atts, pre+"style"))				b_atts["style"] = atts[pre+"style"];
										if (_.has(atts, pre+"color"))				b_atts["color"] = atts[pre+"color"];
										if (_.has(atts, pre+"custom_background"))	b_atts["custom_background"] = atts[pre+"custom_background"];
										if (_.has(atts, pre+"custom_text"))			b_atts["custom_text"] = atts[pre+"custom_text"];
										if (_.has(atts, pre+"outline_custom_color"))			b_atts["outline_custom_color"] = atts[pre+"outline_custom_color"];
										if (_.has(atts, pre+"outline_custom_hover_background"))	b_atts["outline_custom_hover_background"] = atts[pre+"outline_custom_hover_background"];
										if (_.has(atts, pre+"outline_custom_hover_text"))		b_atts["outline_custom_hover_text"] = atts[pre+"outline_custom_hover_text"];
										if (_.has(atts, pre+"gradient_color_1"))	b_atts["gradient_color_1"] = atts[pre+"gradient_color_1"];
										if (_.has(atts, pre+"gradient_color_2"))	b_atts["gradient_color_2"] = atts[pre+"gradient_color_2"];
										if (_.has(atts, pre+"gradient_custom_color_1"))		b_atts["gradient_custom_color_1"] = atts[pre+"gradient_custom_color_1"];
										if (_.has(atts, pre+"gradient_custom_color_2"))		b_atts["gradient_custom_color_2"] = atts[pre+"gradient_custom_color_2"];
										if (_.has(atts, pre+"gradient_text_color"))			b_atts["gradient_text_color"] = atts[pre+"gradient_text_color"];
										if (_.has(atts, pre+"el_id"))				b_atts["el_id"] = atts[pre+"el_id"];
										if (_.has(atts, pre+"el_class"))			b_atts["el_class"] = atts[pre+"el_class"];
										return b_atts;
									};
									var triggerButton = function(trigger_id, with_icon) {
										var button_atts = {
											custom: true,
											greydClass: greyd.tools.generateGreydClass(),
											customStyles: {
												fontSize: "22px",
												border: { top: "0px", left: "0px", right: "0px", bottom: "0px" },
												padding: { top: "0px", left: "0px", right: "0px", bottom: "0px" },
												background: "var(\u002d\u002dcolor63)", // transparent
												color: "var(\u002d\u002dcolor31)",		// very dark
												boxShadow: "none"
											},
											trigger: {
												type: "event",
												params: { name: trigger_id }
											}
										}
										if (with_icon) {
											button_atts["icon"] = {
												content: "icon_plus",
												position: "before",
												size: "100%",
												margin: "10px"
											};
										}
										return button_atts;
									};

									// make items
								  	var items = [];

									// message
									if (_.has(atts, "message")) {
										// content
										var content = wp.blocks.createBlock( "greyd/list-item", { content: atts['content'] } );
										// list
										var color = "";
										if (_.has(atts, "color"))
											color = atts["color"];
										if (color == "" && _.has(atts, "message_box_color"))
											color = atts["message_box_color"];
										var icon = "";
										if (color != "") {
											if (color.indexOf("info") > -1) icon = "icon_info_alt";
											if (color.indexOf("warning") > -1) icon = "icon_error-triangle_alt";
											if (color.indexOf("success") > -1) icon = "icon_check_alt2";
											if (color.indexOf("danger") > -1) icon = "icon_close_alt2";
										}
										items.push(wp.blocks.createBlock( "greyd/list", { type: "icon", icon: { icon: icon, position: "left", align_y: "center" } }, [content] ));
									}

									// hoverbox
									if (_.has(atts, "hoverbox")) {
										// title(s)
										var h_prim = _.has(atts, "primary_title") ? atts["primary_title"] : '';
										var h_hover = _.has(atts, "hover_title") ? atts["hover_title"] : '';
										if (h_prim == h_hover) h_hover = "";
										// title
										if (h_prim == "" && h_hover == "") {
											items.push(wp.blocks.createBlock( "core/heading", { level: 2 } ));
										}
										if (h_prim != "") {
											var h_atts = { text: h_prim, level:  2 };
											if (_.has(atts, "use_custom_fonts_primary_title")) {
												h_atts = { ...h_atts, ...makeHeadingAtts("primary_title_", atts) };
											}
											items.push(wp.blocks.createBlock( "core/heading", computeVcHeadingAtts(h_atts) ));
										}
										// title hover
										if (h_hover != "") {
											var h_atts = { text: h_hover, level:  h_prim == "" ? 2 : 4 };
											if (_.has(atts, "use_custom_fonts_hover_title")) {
												h_atts = { ...h_atts, ...makeHeadingAtts("hover_title_", atts) };
											}
											items.push(wp.blocks.createBlock( "core/heading", computeVcHeadingAtts(h_atts) ));
										}
										// content
										var c_atts = { content: atts['content'] };
										if (_.has(atts, "align")) c_atts["align"] = atts["align"];
										items.push(wp.blocks.createBlock( "core/paragraph", c_atts ));
										// button
										if (_.has(atts, "hover_add_button")) {
											var b_atts = makeButtonAtts("hover_btn_", atts);
											items.push(wp.blocks.createBlock( "greyd/buttons", { align: atts["hover_btn_align"] }, [wp.blocks.createBlock( "greyd/button", computeVcButtonAtts(b_atts) )] ));
										}
									}

									// cta
									if (_.has(atts, "cta")) {
										var h_atts = { level: 2 };
										// title
										if (_.has(atts, "h2")) {
											h_atts["text"] = atts["h2"];
											if (_.has(atts, "use_custom_fonts_h2")) {
												h_atts = { ...h_atts, ...makeHeadingAtts("h2_", atts) };
											}
										}
										items.push(wp.blocks.createBlock( "core/heading", computeVcHeadingAtts(h_atts) ));
										// sub
										if (_.has(atts, "h4")) {
											var h_atts = { level: 4, text: atts["h4"] };
											if (_.has(atts, "use_custom_fonts_h4")) {
												h_atts = { ...h_atts, ...makeHeadingAtts("h4_", atts) };
											}
											items.push(wp.blocks.createBlock( "core/heading", computeVcHeadingAtts(h_atts) ));
										}
										// content
										var c_atts = { content: atts['content'] };
										if (_.has(atts, "txt_align")) c_atts["align"] = atts["txt_align"];
										items.push(wp.blocks.createBlock( "core/paragraph", c_atts ));
										// items.push(wp.blocks.createBlock( "core/paragraph", { content: atts['content'] } ));
										// button
										if (_.has(atts, "add_button")) {
											var b_atts = makeButtonAtts("btn_", atts);
											if (atts["add_button"] == "top")
												items.unshift(wp.blocks.createBlock( "greyd/buttons", { align: atts["btn_align"] }, [wp.blocks.createBlock( "greyd/button", computeVcButtonAtts(b_atts) )] ));
											else if (atts["add_button"] == "bottom")
												items.push(wp.blocks.createBlock( "greyd/buttons", { align: atts["btn_align"] }, [wp.blocks.createBlock( "greyd/button", computeVcButtonAtts(b_atts) )] ));
										}
									}

									// toggle
									if (_.has(atts, "toggle")) {
										// button with trigger
										var trigger_id = greyd.tools.generateRandomID();
										var bs_atts = {};
										var b_atts = triggerButton("trigger_"+trigger_id, true);
										if (_.has(atts, "title")) {
											b_atts["content"] = atts["title"];
											if (_.has(atts, "use_custom_heading")) {
												var h_atts = computeVcHeadingAtts(makeHeadingAtts("custom_", atts));
												if (_.has(h_atts, "textAlign")) 
													bs_atts["align"] = h_atts["textAlign"];
												if (_.has(h_atts, "style")) {
													if (_.has(h_atts["style"], "color") && _.has(h_atts["style"]["color"], "text")) 
														b_atts["customStyles"]["color"] = h_atts["style"]["color"]["text"];
													if (_.has(h_atts["style"], "typography") && _.has(h_atts["style"]["typography"], "fontSize")) 
														b_atts["customStyles"]["fontSize"] = h_atts["style"]["typography"]["fontSize"];
												}
											}
										}
										// style
										if (_.has(atts, "style")) {
											// plus
											if (atts["style"] == "simple") b_atts["icon"]["content"] = "icon_plus-box";
											if (atts["style"] == "round") b_atts["icon"]["content"] = "icon_plus_alt";
											if (atts["style"] == "round_outline") b_atts["icon"]["content"] = "icon_plus_alt2";
											// arrows
											if (atts["style"] == "rounded") b_atts["icon"]["content"] = "arrow_carrot-down_alt";
											if (atts["style"] == "rounded_outline") b_atts["icon"]["content"] = "arrow_carrot-down_alt2";
											if (atts["style"] == "square") b_atts["icon"]["content"] = "arrow_triangle-down_alt";
											if (atts["style"] == "square_outline") b_atts["icon"]["content"] = "arrow_triangle-down_alt2";
											// right arrow
											if (atts["style"] == "arrow") {
												b_atts["icon"]["content"] = "arrow_carrot-down";
												b_atts["icon"]["position"] = "after";
											}
											if (atts["style"] == "text_only") delete b_atts["icon"];
										}
										// size
										if (_.has(b_atts, "icon") && _.has(atts, "size")) {
											if (atts["size"] == "sm") b_atts["icon"]["size"] = "85%";
											if (atts["size"] == "lg") b_atts["icon"]["size"] = "150%";
										}
										items.push(wp.blocks.createBlock( "greyd/buttons", bs_atts, [wp.blocks.createBlock( "greyd/button", b_atts )] ));

										// action
										var g_atts = {
											trigger_event: {
												actions: [ { name: "Trigger "+trigger_id, action: "slideToggle" } ],
												onload: "hide"
											}
										}
										if (_.has(atts, "open") && atts["open"] == "true") {
											g_atts["trigger_event"]["onload"] = "";
										}
										// content
										items.push(wp.blocks.createBlock( "core/group", g_atts, [ wp.blocks.createBlock( "core/paragraph", { content: atts['content'] } ) ] ));
									}

									// tta
									if (_.has(atts, "tabs") || _.has(atts, "tour") || _.has(atts, "pageable")) {
										// console.log(inner);
										var newInner = [];
										// set active section
										var active_index = _.has(atts, "active_section") ? parseInt(atts["active_section"])-1 : 0;

										// get box and button styles
										var color_basic = "var(\u002d\u002dcolor33)"; // hell
										var color = _.has(atts, "color") ? getVcColor(atts["color"]) : color_basic;
										var box_style = {
											padding: { top: "10px", left: "10px", right: "10px", bottom: "10px" },
											borderRadius: "5px",
											background: color_basic
										};
										var button_style = {
											fontSize: "22px",
											borderRadius: "5px",
											border: { top: "0px", left: "0px", right: "0px", bottom: "0px" },
											background: color_basic,
											color: "var(\u002d\u002dcolor31)",		// very dark
											boxShadow: "none"
										};
										// shape
										if (_.has(atts, "shape")) {
											if (atts["shape"] == "square") {
												box_style["borderRadius"] = "0px";
												button_style["borderRadius"] = "0px";
											}
											if (atts["shape"] == "round") {
												box_style["borderRadius"] = "2em";
												button_style["borderRadius"] = "2em";
											}
										}
										// style
										if (_.has(atts, "style")) {
											// modern = classic = default
											if (atts["style"] == "flat") {
												box_style["background"] = color;
												button_style["background"] = color;
											}
											if (atts["style"] == "outline") {
												var border = { 
													top: "2px solid "+color, 
													left: "2px solid "+color, 
													right: "2px solid "+color, 
													bottom: "2px solid "+color
												};
												box_style["background"] = "";
												box_style["border"] = border;
												button_style["color"] = color;
												button_style["background"] = "var(\u002d\u002dcolor63)"; // transparent
												button_style["border"] = border;
											}
										}
										// gap
										if (_.has(atts, "gap")) {
											var gap = atts["gap"]+'px';
											if (_.has(atts, "tabs")) {
												if (_.has(atts, "tab_position") && atts["tab_position"] == "bottom")
													box_style["margin"] = { top: "0px", left: "0px", right: "0px", bottom: gap };
												else
													box_style["margin"] = { top: gap, left: "0px", right: "0px", bottom: "0px" };
											}
											if (_.has(atts, "tour")) {
												if (_.has(atts, "tab_position") && atts["tab_position"] == "right")
													box_style["margin"] = { top: "0px", left: "0px", right: gap, bottom: "0px" };
												else
													box_style["margin"] = { top: "0px", left: gap, right: "0px", bottom: "0px" };
											}
										}
										if ((_.has(atts, "no_fill_content_area") && atts["no_fill_content_area"] == "true") ||
											(_.has(atts, "no_fill") && atts["no_fill"] == "true") ||
											_.has(atts, "pageable")) {
											// reset box style
											box_style = false;
										}

										// get button icon
										var button_icon = false;
										if (_.has(atts, "pageable")) {
											// get pagination icon
											var icon = "icon_circle-empty";
											if (_.has(atts, "pagination_style")) {
												if (atts["pagination_style"] == "") icon = "";
												if (atts["pagination_style"] == "outline-square") icon = "icon_box-selected";
												if (atts["pagination_style"] == "outline-round") icon = "icon_circle-slelected";
												if (atts["pagination_style"] == "flat-round") icon = "icon_circle-empty";
												if (atts["pagination_style"] == "flat-square") icon = "icon_box-empty";
												if (atts["pagination_style"] == "flat-rounded") icon = "icon_stop";
											}
											button_icon = {
												content: icon,
												position: "before",
												size: "100%",
												margin: "0px"
											};
											// icon button style
											button_style = {
												fontSize: "22px",
												border: { top: "0px", left: "0px", right: "0px", bottom: "0px" },
												padding: { top: "0px", left: "0px", right: "0px", bottom: "0px" },
												background: "var(\u002d\u002dcolor63)", // transparent
												color: "var(\u002d\u002dcolor31)",		// very dark
												boxShadow: "none"
											};
											if (_.has(atts, "pagination_color")) {
												button_style["color"] = getVcColor(atts["pagination_color"]);
											}
										}

										// action animation
										var action = "toggle";
										if (_.has(atts, "pageable")) action = "fadeToggle";

										// buttons
										var buttons = [];
										for (var i=0; i<inner.length; i++) {
											if (inner[i]["name"] == "greyd/box") {
												if (_.has(inner[i]["attributes"], "trigger_event") && inner[i]["attributes"]["trigger_event"]["actions"].length > 0) {
													
													// set active section
													if (i == active_index) inner[i]["attributes"]["trigger_event"]["onload"] = "";
													// set action
													inner[i]["attributes"]["trigger_event"]["actions"][0]["action"] = action;

													// box style
													if (box_style) {
														inner[i]["attributes"]["greydClass"] = greyd.tools.generateGreydClass();
														inner[i]["attributes"]["greydStyles"] = box_style;
													}

													// get trigger name
													var trigger_name = inner[i]["attributes"]["trigger_event"]["actions"][0]["name"];

													// button atts
													var b_atts = triggerButton(trigger_name, false);
													b_atts["customStyles"] = button_style;
													if (button_icon) b_atts["icon"] = button_icon;

													if (!_.has(atts, "pageable")) {
														// get button title
														var button_title = trigger_name;
														if (typeof inner[i]["attributes"]["trigger_event"]["title"] !== 'undefined') {
															button_title = inner[i]["attributes"]["trigger_event"]["title"];
															delete inner[i]["attributes"]["trigger_event"]["title"];
														}
														b_atts["content"] = button_title;
													}

													// collect buttons
													buttons.push(wp.blocks.createBlock( "greyd/button", b_atts ));
													newInner.push(inner[i]);

												}
											}
										}

										// buttons position
										if (_.has(atts, "tabs")) {
											var bs_atts = {};
											if (_.has(atts, "alignment")) bs_atts["align"] = atts["alignment"];
											if (_.has(atts, "tab_position") && atts["tab_position"] == "bottom")
												newInner.push(wp.blocks.createBlock( "greyd/buttons", bs_atts, buttons ));
											else
												newInner.unshift(wp.blocks.createBlock( "greyd/buttons", bs_atts, buttons ));
										}
										if (_.has(atts, "tour")) {
											var bs_atts = {};
											if (_.has(atts, "alignment")) bs_atts["align"] = atts["alignment"];
											for (var i=0; i<buttons.length; i++) {
												buttons[i] = wp.blocks.createBlock( "greyd/buttons", bs_atts, [buttons[i]] )
											}
											var size = 4;
											if (_.has(atts, "controls_size")) {
												if (atts["controls_size"] == "xl") size = 6;
												if (atts["controls_size"] == "lg") size = 5;
												if (atts["controls_size"] == "sm") size = 3;
												if (atts["controls_size"] == "xs") size = 2;
											}
											var col1 = wp.blocks.createBlock( "core/column", { responsive: { width: { xs: "col-"+size, sm: "" } } }, buttons );
											var col2 = wp.blocks.createBlock( "core/column", { responsive: { width: { xs: "col-auto" } } }, newInner );
											if (_.has(atts, "tab_position") && atts["tab_position"] == "right")
												newInner = [wp.blocks.createBlock( "core/columns", {}, [ col2, col1 ] )];
											else
												newInner = [wp.blocks.createBlock( "core/columns", {}, [ col1, col2 ] )];
										}
										if (_.has(atts, "pageable")) {
											if (_.has(atts, "tab_position") && atts["tab_position"] == "top")
												newInner.unshift(wp.blocks.createBlock( "greyd/buttons", { align: "center" }, buttons ));
											else
												newInner.push(wp.blocks.createBlock( "greyd/buttons", { align: "center" }, buttons ));
										}

										// title
										if (_.has(atts, "title")) {
											var h_atts = { content: atts["title"] };
											if (_.has(atts, "title_tag") && atts["title_tag"] != "p") {
												h_atts["level"] = parseInt(atts["title_tag"]);
											}
											newInner.unshift(wp.blocks.createBlock( "core/heading", h_atts ));
										}

										return newInner;
									}

									// accordion
									if (_.has(atts, "accordion")) {

										var newInner = [];
										for (var i=0; i<inner.length; i++) {
											if (inner[i]["name"] == "greyd/box" && _.has(inner[i]["attributes"], "trigger_event") && inner[i]["attributes"]["trigger_event"]["actions"].length > 0) {
													
												// get title
												var button_title = inner[i]["attributes"]["trigger_event"]["actions"][0]["name"];
												if (typeof inner[i]["attributes"]["trigger_event"]["title"] !== 'undefined') {
													button_title = inner[i]["attributes"]["trigger_event"]["title"];
													delete inner[i]["attributes"]["trigger_event"]["title"];
												}
												// make plain accordion-item with title
												newInner.push(wp.blocks.createBlock( "greyd/accordion-item", { title: button_title }, inner[i].innerBlocks ));
												
											}
										}

										// extra title
										if (_.has(atts, "title")) {
											var h_atts = { content: atts["title"] };
											if (_.has(atts, "title_tag") && atts["title_tag"] != "p") {
												h_atts["level"] = parseInt(atts["title_tag"]);
											}
											newInner.unshift(wp.blocks.createBlock( "core/heading", h_atts ));
										}

										return newInner;
									}

									// section
									if (_.has(atts, "section")) return inner;

									return items;
							  } },
							  { compute: function(atts) {

									// section
									if (_.has(atts, "section")) {
										// el_id
										if (_.has(atts, "tab_id")) {
											atts['el_id'] = atts['tab_id'];
											delete atts['tab_id'];
										}
										// action
										atts["trigger_event"] = {
											actions: [ { name: "trigger_"+greyd.tools.generateRandomID(), action: "toggle" } ],
											onload: "hide",
											siblings: true,
											title: atts["title"]
										};
									}

									// common atts
									atts = computeVcBasicAtts(atts);

									// message
									if (_.has(atts, "message")) {
										
										// style
										var radius = "";
										if (atts["style"] == "rounded") radius = "5px";
										if (atts["style"] == "square") radius = "0px";
										if (atts["style"] == "round") radius = "2em";
										var greydStyles = {
											padding: { top: "10px", left: "10px", right: "10px", bottom: "10px" },
											borderRadius: radius,
										}

										// color
										// message_box_color
										var color = "";
										if (_.has(atts, "color"))
											color = atts["color"];
										if (color == "" && _.has(atts, "message_box_color"))
											color = atts["message_box_color"];
										color = getVcColor(color);

										// message_box_style
										if (_.has(atts, "message_box_style")) {
											if (atts["message_box_style"] == "outline" || atts["message_box_style"] == "3d") {
												greydStyles["color"] = color;
												greydStyles["border"] = { 
													top: '2px solid '+color, 
													right: '2px solid '+color, 
													bottom: '2px solid '+color, 
													left: '2px solid '+color 
												};
												greydStyles["background"] = 'transparent';
											}
											else greydStyles["background"] = color;
										}
										else greydStyles["background"] = color;

										atts["greydClass"] = greyd.tools.generateGreydClass();
										atts["greydStyles"] = _.clone(greydStyles);
									}
									
									// cta
									if (_.has(atts, "cta")) {

										// shape
										var radius = "";
										if (atts["shape"] == "rounded") radius = "5px";
										if (atts["shape"] == "square") radius = "0px";
										if (atts["shape"] == "round") radius = "2em";
										var greydStyles = {
											padding: {"top":"20px","left":"20px","right":"20px","bottom":"20px"},
											borderRadius: radius,
										}

										// color
										var color = getVcColor(_.has(atts, 'color') ? atts["color"] : "classic");

										// style
										if (_.has(atts, "style")) {
											if (atts["style"] == "outline" || atts["style"] == "3d") {
												greydStyles["border"] = { 
													top: '2px solid '+color, 
													right: '2px solid '+color, 
													bottom: '2px solid '+color, 
													left: '2px solid '+color 
												};
												greydStyles["background"] = 'transparent';
											}
											else if (atts["style"] == "custom") {
												var text = _.has(atts, 'custom_text') ? atts["custom_text"] : "";
												var background = _.has(atts, 'custom_background') ? atts["custom_background"] : "";
												greydStyles["color"] = text;
												greydStyles["background"] = background;
											}
											else greydStyles["background"] = color;
										}
										else greydStyles["background"] = color;

										// el_width
										if (_.has(atts, "el_width")) {
											if (atts["el_width"] == "xl") greydStyles["maxWidth"] = "90%";
											if (atts["el_width"] == "lg") greydStyles["maxWidth"] = "80%";
											if (atts["el_width"] == "md") greydStyles["maxWidth"] = "70%";
											if (atts["el_width"] == "sm") greydStyles["maxWidth"] = "60%";
											if (atts["el_width"] == "xs") greydStyles["maxWidth"] = "50%";
										}

										atts["greydClass"] = greyd.tools.generateGreydClass();
										atts["greydStyles"] = _.clone(greydStyles);
									}

									// hoverbox
									if (_.has(atts, "hoverbox")) {

										// shape
										var radius = "";
										if (atts["shape"] == "rounded") radius = "5px";
										if (atts["shape"] == "square") radius = "0px";
										if (atts["shape"] == "round") radius = "2em";
										var greydStyles = {
											padding: {"top":"20px","left":"20px","right":"20px","bottom":"20px"},
											borderRadius: radius,
										}

										// color
										var color = getVcColor(_.has(atts, 'hover_background_color') ? atts["hover_background_color"] : "grey");
										if (color == "custom" &&_.has(atts, "hover_custom_background")) {
											color = atts["hover_custom_background"];
										}
										greydStyles["background"] = color;

										// image
										if (_.has(atts, "image") && _.has(greyd.data.media_urls, atts["image"])) {
											atts["background"] = {
												type: "image",
												image: {
													id: atts["image"],
													url: greyd.data.media_urls[atts["image"]].src
												}
											};
										}

										// el_width
										if (_.has(atts, "el_width")) {
											greydStyles["maxWidth"] = atts["el_width"]+"%";
										}

									}

									// accordion
									if (_.has(atts, "accordion")) {
										
										// style -> not supported (flat, outline, etc)
										// color/shape -> only for head/button
										// c_align -> not supported
										// active_section -> only first open possible
										// css -> not supported
										// css_animation -> not supported

										// collapsible
										if (_.has(atts, "collapsible_all")) delete atts["autoClose"];
										else atts["autoClose"] = true;

										// active section (only first)
										if (_.has(atts, "active_section")) delete atts["active_section"];
										else atts["openFirst"] = false;

										// icon
										if (_.has(atts, "c_icon")) {
											var icon = ["", ""];
											if (atts["c_icon"] == "chevron") icon = ["arrow_carrot-down", "arrow_carrot-up"];
											if (atts["c_icon"] == "plus") icon = ["icon_plus", "icon_minus-06"];
											if (atts["c_icon"] == "triangle") icon = ["arrow_triangle-down", "arrow_triangle-up"];
											delete atts["c_icon"];
											atts["iconNormal"] = icon[0];
											atts["iconActive"] = icon[1];
										}
										atts["iconPosition"] = 'hasiconleft';
										if (_.has(atts, "c_position")) {
											if (atts["c_position"] == "right") {
												atts["iconPosition"] = '';
											}
											delete atts["c_position"];
										}

										// styles
										var greyd_style = {};
										// color
										if (_.has(atts, "color")) {
											var color = getVcColor(atts["color"]);
											greyd_style["\u002d\u002daccord-title-bg-color"] = color;
											// greyd_style["\u002d\u002daccord-bg-color"] = color;
											delete atts["color"];
										}
										// shape
										if (_.has(atts, "shape")) {
											if (atts["shape"] == "square") {
												greyd_style["\u002d\u002daccord-title-radius"] = "0px";
											}
											if (atts["shape"] == "round") {
												greyd_style["\u002d\u002daccord-title-radius"] = "2em";
											}
											delete atts["shape"];
										}
										// style -> not supported (no border/outline possible)
										if (_.has(atts, "style")) {
											delete atts["style"];
										}

										// gap spacing no_fill
										if (_.has(atts, "gap")) {
											var gap = atts["gap"]+'px';
											greyd_style["\u002d\u002daccord-content-margin-bottom"] = gap;
											delete atts["gap"];
										}
										if (_.has(atts, "spacing")) {
											var spacing = atts["spacing"]+'px';
											greyd_style["\u002d\u002daccord-content-padding"] = spacing+' 0px 0px 0px';
											delete atts["spacing"];
										}
										if (_.has(atts, "no_fill")) {
											delete atts["no_fill"];
										}

										if (!_.isEmpty(greyd_style)) {
											atts["greydClass"] = greyd.tools.generateGreydClass();
											atts["greydStyles"] = greyd_style;
										}

									}

									return atts;
							  } } ],
		"core/query":		[ { inner: function(atts) { 

									var items = [];

									// title
									if (_.has(atts, "title")) {
										items.push(wp.blocks.createBlock( "core/heading", { content: atts["title"] } ));
										delete atts['title'];
									}

									var post_elements = [];
									var template_atts = { pagination: { arrows_type: "" } };

									// slider
									if (_.has(atts, "slider")) {
										// animation
										if (!_.has(atts, "interval") || atts["interval"] != 0) {
											var interval = _.has(atts, "interval") ? parseInt(atts["interval"]) : 3;
											template_atts["animation"] = { autoplay: true, interval: interval };
										}
										// image
										var i_atts = { image: { type: "dynamic", tag: "image" } };
										if (_.has(atts, "thumb_size") && atts["thumb_size"].indexOf('x') > -1) {
											var size = atts["thumb_size"].split("x");
											i_atts["greydClass"] = greyd.tools.generateGreydClass();
											i_atts["greydStyles"] = { width: size[0]+'px' };

										}
										post_elements.push(wp.blocks.createBlock( "greyd/image", i_atts ));
										// content
										if (!_.has(atts, "slides_content") || atts["slides_content"] == "teaser") {
											// title
											if (!_.has(atts, "slides_title") || atts["slides_title"] == "1" || atts["slides_title"] == "yes") {
												post_elements.push(wp.blocks.createBlock( "core/heading", { content: '<span data-tag="title" data-params="&quot;&quot;" class="is-tag">Title</span>' } ));
											}
											post_elements.push(wp.blocks.createBlock( "core/paragraph", { content: '<span data-tag="excerpt" data-params="&quot;&quot;" class="is-tag">Excerpt</span>' } ));
										}
										// link
										if (!_.has(atts, "link") || atts["link"] != "link_no") {
											var trigger = { type: "dynamic", params: { tag: "post" } };
											if (atts["link"] == "link_image") trigger["params"]["tag"] = "image";
											post_elements = [wp.blocks.createBlock( "greyd/box", { trigger: trigger }, post_elements )];
										}
									}
									// grid
									else {
										// animation
										var animation = {};
										if (_.has(atts, "loop") && atts["loop"] == "yes") {
											animation["loop"] = true;
										}
										if (_.has(atts, "autoplay") && atts["autoplay"] != "-1") {
											animation["autoplay"] = true;
											animation["interval"] = parseInt(atts["autoplay"]);
										}
										if (!_.isEmpty(animation)) {
											template_atts["animation"] = animation;
										}
										// arrows
										if (_.has(atts, "arrows_design") && atts["arrows_design"] != "none") {
											template_atts["arrows"] = { enable: true, overlap: true };
											// overlap
											if (_.has(atts, "arrows_position") && atts["arrows_position"] == "outside") {
												template_atts["arrows"]["overlap"] = false;
											}
											if (_.has(atts, "arrows_color")) {
												template_atts["arrows"]["greydClass"] = greyd.tools.generateGreydClass();
												template_atts["arrows"]["greydStyles"] = { color: getVcColor(atts["arrows_color"]) };
											}
											// arrow
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_08_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_07_left") {
												// default
											}
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_01_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_04_left") {
												template_atts["arrows"]["icon_previous"] = "arrow_carrot-left";
												template_atts["arrows"]["icon_next"] = "arrow_carrot-right";
											}
											// carrot
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_02_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_05_left") {
												template_atts["arrows"]["icon_previous"] = "arrow_carrot-left_alt2";
												template_atts["arrows"]["icon_next"] = "arrow_carrot-right_alt2";
											}
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_03_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_06_left") {
												template_atts["arrows"]["icon_previous"] = "arrow_carrot-left_alt";
												template_atts["arrows"]["icon_next"] = "arrow_carrot-right_alt";
											}
											// triangle
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_09_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_10_left") {
												template_atts["arrows"]["icon_previous"] = "arrow_triangle-left";
												template_atts["arrows"]["icon_next"] = "arrow_triangle-right";
											}
											if (atts["arrows_design"] == "vc_arrow-icon-arrow_11_left" ||
												atts["arrows_design"] == "vc_arrow-icon-arrow_12_left") {
												template_atts["arrows"]["icon_previous"] = "arrow_triangle-left_alt";
												template_atts["arrows"]["icon_next"] = "arrow_triangle-right_alt";
											}

										}
										// pagination
										template_atts["pagination"]["greydClass"] = greyd.tools.generateGreydClass();
										template_atts["pagination"]["greydStyles"] = { justifyContent: "center" };
										if (_.has(atts, "paging_color")) {
											template_atts["pagination"]["greydStyles"]["color"] = getVcColor(atts["paging_color"]);
										}
										if (_.has(atts, "paging_design")) {
											if (atts["paging_design"] == "none") template_atts["pagination"]["enable"] = false;
											if (atts["paging_design"].indexOf("pagination_") == 0) {
												// advanced text options are not supported
												template_atts["pagination"]["type"] = "text";
											}
											if (atts["paging_design"] == "square_dots") {
												template_atts["pagination"]["icon_type"] = "icon";
												template_atts["pagination"]["icon_normal"] = "icon_box-empty";
												template_atts["pagination"]["icon_active"] = "icon_box-selected";
											}
											if (atts["paging_design"] == "radio_dots") {
												// default
												template_atts["pagination"]["icon_type"] = "icon";
												template_atts["pagination"]["icon_normal"] = "icon_circle-empty";
												template_atts["pagination"]["icon_active"] = "icon_circle-slelected";
											}
											if (atts["paging_design"] == "point_dots") {
												template_atts["pagination"]["icon_type"] = "dots";
												template_atts["pagination"]["greydClass"] = greyd.tools.generateGreydClass();
												var style = { opacity: "50%", active: { opacity: "100%" }, hover: { opacity: "100%" } };
												template_atts["pagination"]["greydStyles"] = { ...template_atts["pagination"]["greydStyles"], ...style };
											}
											if (atts["paging_design"] == "fill_square_dots" || atts["paging_design"] == "round_fill_square_dots") {
												template_atts["pagination"]["icon_type"] = "blocks";
												template_atts["pagination"]["greydClass"] = greyd.tools.generateGreydClass();
												var style = { opacity: "50%", active: { opacity: "100%" }, hover: { opacity: "100%" } };
												template_atts["pagination"]["greydStyles"] = { ...template_atts["pagination"]["greydStyles"], ...style };
											}
										}

										// elements
										var makeImageBlock = function(link_to) {
											var i_atts = { image: { type: "dynamic", tag: "image" } };
											if (link_to) i_atts["trigger"] = { type: "dynamic", params: { tag: link_to } };
											return wp.blocks.createBlock( "greyd/image", i_atts );
										}
										var makeDateBlock = function(align) {
											var d_atts = { content: '<span data-tag="date" data-params="&quot;&quot;" class="is-tag">Date</span>' };
											if (align) d_atts["align"] = align;
											return wp.blocks.createBlock( "core/paragraph", d_atts );
										}
										var makeTitleBlock = function(align) {
											var t_atts = { content: '<span data-tag="title" data-params="&quot;&quot;" class="is-tag">Title</span>' };
											if (align) t_atts["textAlign"] = align;
											return wp.blocks.createBlock( "core/heading", t_atts );
										}
										var makeContentBlock = function(align) {
											var c_atts = { content: '<span data-tag="excerpt" data-params="&quot;&quot;" class="is-tag">Excerpt</span>' };
											if (align) c_atts["align"] = align;
											return wp.blocks.createBlock( "core/paragraph", c_atts );
										}
										var makeButtonBlock = function(align) {
											var b_atts = { trigger: { type: "dynamic", params: { tag: "post" } }, content: "Weiterlesen" };
											var bs_atts = {};
											if (align) bs_atts["align"] = align;
											return wp.blocks.createBlock( "greyd/buttons", bs_atts, [wp.blocks.createBlock( "greyd/button", b_atts )] );
										}
										var makeBoxBlock = function(elements, link_to, bg_img) {
											var box_atts = {};
											if (link_to) box_atts["trigger"] = { type: "dynamic", params: { tag: link_to } };
											if (bg_img) {
												box_atts["background"] = { type: "image", image: { id: "_image_" } };
												box_atts["greydClass"] = greyd.tools.generateGreydClass();
												box_atts["greydStyles"] = { padding: { top: "10px", left: "10px", right: "10px", bottom: "10px" } };
											}
											return wp.blocks.createBlock( "greyd/box", box_atts, elements );
										}
										var makeColumnsBlock = function(elements1, elements2) {
											var c_atts = {};
											return wp.blocks.createBlock( "core/columns", c_atts, [
												wp.blocks.createBlock( "core/column", {}, elements1 ),
												wp.blocks.createBlock( "core/column", {}, elements2 )
											] );
										}

										// post grid item
										if (!_.has(atts, "item") ||
											atts["item"] == "none" ||
											atts["item"] == "basicGrid_ScaleInWithRotation" ||
											atts["item"] == "masonryGrid_Default" ||
											atts["item"] == "masonryGrid_FadeIn" ||
											atts["item"] == "masonryGrid_IconSlideOut" ||
											atts["item"] == "masonryGrid_ScaleWithRotation") {
											// img title excerpt button
											post_elements.push(
												makeImageBlock("post"),
												makeTitleBlock(),
												makeContentBlock(),
												makeButtonBlock()
											);
										}
										else if (atts["item"] == "basicGrid_SlideBottomWithIcon") {
											// img title excerpt button (center)
											post_elements.push(
												makeImageBlock("post"),
												makeTitleBlock("center"),
												makeContentBlock("center"),
												makeButtonBlock("center")
											);
										}
										else if (atts["item"] == "basicGrid_SlideFromTop") {
											// bg_img title excerpt button
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock(),
													makeContentBlock(),
													makeButtonBlock()
												], false, "image")
											);
										}
										else if (atts["item"] == "basicGrid_VerticalFlip" || 
												atts["item"] == "masonryGrid_BlurOut") {
											// bg_img title excerpt (center)
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock("center"),
													makeContentBlock("center")
												], "post", "image")
											);
										}
										else if (atts["item"] == "basicGrid_NoAnimation" || 
												atts["item"] == "masonryGrid_OverlayWithRotation") {
											// bg_img date title (center)
											post_elements.push(
												makeBoxBlock([
													makeDateBlock("center"),
													makeTitleBlock("center")
												], "post", "image")
											);
										}
										else if (atts["item"] == "basicGrid_GoTopSlideout" || 
												atts["item"] == "basicGrid_TextFirst" ||
												atts["item"] == "masonryGrid_SlideFromLeft" ||
												atts["item"] == "masonryGrid_GoTop") {
											// bg_img title excerpt
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock(),
													makeContentBlock()
												], "post", "image")
											);
										}
										else if (atts["item"] == "masonryGrid_SlideoOutFromRight") {
											// bg_img title
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock()
												], "post", "image")
											);
										}
										else if (atts["item"] == "basicGrid_SlideFromLeft") {
											// bg_img date title
											post_elements.push(
												makeBoxBlock([
													makeDateBlock(),
													makeTitleBlock()
												], "post", "image")
											);
										}
										else if (atts["item"] == "basicGrid_FadeInWithSideContent" ||
												atts["item"] == "masonryGrid_WithSideContent") {
											// img / date title excerpt button (columns)
											post_elements.push(
												makeColumnsBlock([
													makeImageBlock("post"),
												], [
													makeDateBlock(),
													makeTitleBlock(),
													makeContentBlock(),
													makeButtonBlock()
												])
											);
										}

										// media grid item
										else if (atts["item"] == "mediaGrid_Default" ||
												atts["item"] == "mediaGrid_SimpleOverlay" ||
												atts["item"] == "mediaGrid_FadeInWithIcon" ||
												atts["item"] == "mediaGrid_ScaleWithRotation" ||
												atts["item"] == "mediaGrid_ScaleInWithIcon" ||
												atts["item"] == "masonryMedia_Default" ||
												atts["item"] == "masonryMedia_BorderedScale" ||
												atts["item"] == "masonryMedia_SolidBlurOut" ||
												atts["item"] == "masonryMedia_ScaleWithRotationLight" ||
												atts["item"] == "masonryMedia_SlideTop" ||
												atts["item"] == "masonryMedia_SimpleBlurWithScale") {
											// bg_img
											post_elements.push(
												makeBoxBlock([
												], "image", "image")
											);
										}
										else if (atts["item"] == "mediaGrid_BorderedScaleWithTitle" ||
												atts["item"] == "mediaGrid_SlideInTitle" ||
												atts["item"] == "masonryMedia_SimpleOverlay") {
											// bg_img title (center)
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock("center")
												], "image", "image")
											);
										}
										else if (atts["item"] == "masonryMedia_SlideWithTitleAndCaption") {
											// bg_img title excerpt
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock(),
													makeContentBlock()
												], "image", "image")
											);
										}
										else if (atts["item"] == "mediaGrid_SlideOutCaption") {
											// bg_img title excerpt (center)
											post_elements.push(
												makeBoxBlock([
													makeTitleBlock("center"),
													makeContentBlock("center")
												], "image", "image")
											);
										}
										else if (atts["item"] == "mediaGrid_HorizontalFlipWithFade") {
											// bg_img date title (center)
											post_elements.push(
												makeBoxBlock([
													makeDateBlock("center"),
													makeTitleBlock("center")
												], "image", "image")
											);
										}
										else if (atts["item"] == "mediaGrid_BlurWithContentBlock" ||
												atts["item"] == "masonryMedia_ScaleWithContentBlock") {
											// img title excerpt
											post_elements.push(
												makeImageBlock("image"),
												makeTitleBlock(),
												makeContentBlock()
											);
										}

										// default
										else {
											// date title excerpt
											post_elements.push(
												makeBoxBlock([
													makeDateBlock(),
													makeTitleBlock(),
													makeContentBlock()
												], "post", false)
											);
										}

									}

									items.push(wp.blocks.createBlock( "core/post-template", template_atts, post_elements ));

									return items;
							  } },
							  { compute: function(atts) {
									// css_animation
									if (_.has(atts, "initial_loading_animation")) {
										atts['css_animation'] = atts['initial_loading_animation'];
										delete atts['initial_loading_animation'];
									}
								    // common atts
									atts = computeVcBasicAtts(atts);

									// query
									var query = {
										// settings
										inherit: false, postType: "post", orderBy: "date", order: "desc", sticky: "",
										// filter
										taxQuery: {}, author: "", search: "", exclude: [],
										// toolbar
										perPage: 1, offset: 0, pages: 0,
									};

									// slider
									if (_.has(atts, "slider")) {
										// count
										if (!_.has(atts, "count") || atts["count"].toLowerCase() != "all") {
											query["pages"] = _.has(atts, "count") ? atts["count"] : 3;
											delete atts["count"];
										}
										// posttypes
										if (_.has(atts, "posttypes")) {
											// only the first posttype is supported
											var pts = atts["posttypes"].split(',');
											query["postType"] = pts[0];
											delete atts["postType"];
										}
										// orderby
										if (_.has(atts, "orderby") && atts["orderby"] == "title") {
											// other order options are not supported
											query["orderBy"] = "title";
											delete atts["orderBy"];
										}
										// order
										if (_.has(atts, "order")) {
											query["order"] = atts["order"].toLowerCase();
											delete atts["order"];
										}
										// categories
										if (_.has(atts, "categories")) {
											// todo
										}
									}
									// grid
									else {
										atts["displayLayout"] = { 
											type: "flex",
											columns: 3
										};
										// element_width
										if (_.has(atts, "element_width")) {
											if (atts["element_width"] == "2") atts["displayLayout"]["columns"] = 6;
											if (atts["element_width"] == "3") atts["displayLayout"]["columns"] = 4;
											if (atts["element_width"] == "6") atts["displayLayout"]["columns"] = 2;
											if (atts["element_width"] == "12") atts["displayLayout"]["columns"] = 1;
										}

										// post_type
										if (_.has(atts, "post_type")) {
											// only public posttypes are supported
											query["postType"] = atts["post_type"];
											delete atts["post_type"];
										}
										// max_items
										query["perPage"] = 10;
										query["pages"] = 1;
										if (_.has(atts, "max_items")) {
											query["perPage"] = atts["max_items"] != -1 ? atts["max_items"] : 100;
											delete atts["max_items"];
										}
										// items_per_page
										if (_.has(atts, "items_per_page")) {
											var pages = Math.ceil(query["perPage"] / atts["items_per_page"]);
											query["perPage"] = atts["items_per_page"];
											query["pages"] = pages;
											delete atts["items_per_page"];
										}
										// orderby
										if (_.has(atts, "orderby") && atts["orderby"] == "title") {
											// other order options are not supported
											query["orderBy"] = "title";
											delete atts["orderBy"];
										}
										// order
										if (_.has(atts, "order")) {
											query["order"] = atts["order"].toLowerCase();
											delete atts["order"];
										}
										// offset
										if (_.has(atts, "offset")) {
											query["offset"] = atts["offset"];
											delete atts["offset"];
										}
									}

									// categories/taxonomies
									if (_.has(atts, "taxonomies") || _.has(atts, "categories")) {
										if (_.has(greyd.data.all_taxes, query['postType'])) {

											var getTax = function(tax_id) {
												for (var i=0; i<greyd.data.all_taxes[query['postType']].length; i++) {
													if (_.has(greyd.data.all_taxes[query['postType']][i], "values")) {
														for (var j=0; j<greyd.data.all_taxes[query['postType']][i]["values"].length; j++) {
															if (greyd.data.all_taxes[query['postType']][i]["values"][j].id == tax_id ||
																greyd.data.all_taxes[query['postType']][i]["values"][j].slug == tax_id) {
																return {
																	slug: greyd.data.all_taxes[query['postType']][i]['slug'],
																	value: greyd.data.all_taxes[query['postType']][i]["values"][j]
																};
															}
														}
													}
												}
												return false;
											}

											var taxes = [];
											if (_.has(atts, "taxonomies")) taxes = atts["taxonomies"].split(',').map(function(v) { return parseInt(v) });
											if (_.has(atts, "categories")) taxes = atts["categories"].split(',');
											for (var i=0; i<taxes.length; i++) {
												// console.log(parseInt(taxes[i]));
												var tax = getTax(taxes[i]);
												// console.log(tax);
												if (tax) {
													if (!_.has(query['taxQuery'], tax["slug"])) query['taxQuery'][tax["slug"]] = [];
													query['taxQuery'][tax["slug"]].push(tax["value"]["id"]);
												}
											}
											// console.log(query['taxQuery']);
										}
									}

									atts["query"] = query;
									
									return atts;
							  } } ],

		"greyd/iframe":		mappingsVcWidget("greyd/iframe"),
		"core/embed":		mappingsVcWidget("core/embed"),

		"core/search": 			mappingsVcWidget("core/search"),
		"core/group": 			mappingsVcWidget("core/group"),
		"core/latest-comments": mappingsVcWidget("core/latest-comments"),
		"core/calendar": 		mappingsVcWidget("core/calendar"),
		"core/page-list": 		mappingsVcWidget("core/page-list"),
		"core/tag-cloud": 		mappingsVcWidget("core/tag-cloud"),
		"greyd/menu": 			mappingsVcWidget("greyd/menu"),
		"core/paragraph":		mappingsVcWidget("core/paragraph"),
		"core/latest-posts": 	mappingsVcWidget("core/latest-posts"),
		"core/categories":		mappingsVcWidget("core/categories"),
		"core/archives":		mappingsVcWidget("core/archives"),
		"core/rss":				mappingsVcWidget("core/rss"),
	};

	function mappingsVcWidget(name) {
		return [ 
			{ key: "content", fill: function(inner) { return inner.trim().split('\n').join('<br>') } },
			{ transform: function(atts) { 
				return transformVcWidget(name, atts);
			} },
			{ inner: function(atts) { 
				return innerItemsVcWidget(name, atts);
			} },
			{ compute: function(atts) {
				return computeVcWidget(name, atts);
			} } 
		];
	}
	function transformVcWidget(name, atts) {
		if (_.has(atts, "title")) {
			// wrap in group
			return "core/group";
		}
		return name;
	}
	function innerItemsVcWidget(name, atts) {
		var items = [];
		if (_.has(atts, "title")) {
			// title
			items.push(wp.blocks.createBlock( "core/heading", { 
				content: atts["title"], 
				level: 4
			} ));
			if (name != "core/group") {
				// not vc_wp_meta links
				// block
				items.push(wp.blocks.createBlock( name, computeVcWidgetAtts(name, atts) ));
			}
		}
		if (name == "core/group") {
			// vc_wp_meta links
			items.push(wp.blocks.createBlock( "core/loginout", {} ));
			items.push(wp.blocks.createBlock( "greyd/buttons", {}, [wp.blocks.createBlock( "greyd/button", {
				trigger: { type: "link", params: { url: greyd.data.urls.rss } },
				content: "Post Feed",
				className: "is-style-link-prim"
			} )] ));
			items.push(wp.blocks.createBlock( "greyd/buttons", {}, [wp.blocks.createBlock( "greyd/button", {
				trigger: { type: "link", params: { url: greyd.data.urls.comments_rss } },
				content: "Comments Feed",
				className: "is-style-link-prim"
			} )] ));
		}
		return items;
	}
	function computeVcWidget(name, atts) {
		// common atts
		atts = computeVcBasicAtts(atts);
		// with title
		if (_.has(atts, "title")) {
			// group atts
			atts = {
				anchor: _.has(atts, "anchor") ? atts['anchor'] : undefined,
				className: _.has(atts, "className") ? atts['className'] : undefined,
			};
		}
		else {
			// block atts
			atts = computeVcWidgetAtts(name, atts);
		}
		return atts;
	}
	function computeVcWidgetAtts(name, atts) {
		if (name == "core/embed") {
			// vc_flickr
			var flickr_id = "95572727@N00";
			if (_.has(atts, "flickr_id")) {
				flickr_id = atts["flickr_id"];
				delete atts["flickr_id"];
			}
			atts["url"] = "https://www.flickr.com/photos/"+flickr_id;

		}
		if (name == "greyd/iframe") {
			// vc_facebook
			if (_.has(atts, "fb")) {
				var type = _.has(atts, "type") ? atts["type"] : "standard";
				atts["url"] = "https://www.facebook.com/plugins/like.php?href="+greyd.data.urls.current+"&layout="+type+"&show_faces=false&action=like&colorscheme=light";
				if (type == "standard") atts["greydStyles"] = { height: "30px" };
				if (type == "button_count") atts["greydStyles"] = { height: "20px" };
				if (type == "box_count") atts["greydStyles"] = { height: "40px" };
				atts["greydClass"] = greyd.tools.generateGreydClass();
				delete atts["type"];
			}
			// vc_tweetmeme
			if (_.has(atts, "twitter")) {

				// url
				var url = "https://platform.twitter.com/widgets/tweet_button.c1cdceed40059a51b374bf347e6a2ae0.de.html";
				if (_.has(atts, "type")) {
					if (atts["type"] == "follow") {
						url = "https://platform.twitter.com/widgets/follow_button.c1cdceed40059a51b374bf347e6a2ae0.de.html";
					}
				}
				// params
				var id = greyd.tools.generateRandomID();
				var params = [ 
					"dnt=true", 
					"id=twitter-widget-"+id, 
					"original_referer="+encodeURIComponent(greyd.data.urls.current) 
				];
				// size
				if (_.has(atts, "large_button") && atts["large_button"] == "true") {
					atts["greydStyles"] = { height: "28px" };
					params.push("size=l");
				}
				else {
					atts["greydStyles"] = { height: "20px" };
					params.push("size=m");
				}
				atts["greydClass"] = greyd.tools.generateGreydClass();
				// lang
				// time

				// share
				if (!_.has(atts, "type") || atts["type"] == "share") {
					params.push("type=share");

					// url
					if (_.has(atts, "share_use_page_url") && atts["share_use_page_url"] == "" && _.has(atts, "share_use_custom_url")) {
						params.push("url="+encodeURIComponent(atts["share_use_custom_url"]));
						delete atts["share_use_page_url"];
						delete atts["share_use_custom_url"];
					}
					else params.push("url="+encodeURIComponent(greyd.data.urls.current));
					// text
					if (_.has(atts, "share_text_page_title") && atts["share_text_page_title"] == "" && _.has(atts, "share_text_custom_text")) {
						params.push("text="+encodeURIComponent(atts["share_text_custom_text"]));
						delete atts["share_text_page_title"];
						delete atts["share_text_custom_text"];
					}
					else params.push("text="+encodeURIComponent(greyd.data.post_title));
					// via
					if (_.has(atts, "share_via")) {
						params.push("via="+encodeURIComponent(atts["share_via"]));
						delete atts["share_via"];
					}
					// related
					if (_.has(atts, "share_recommend")) {
						params.push("related="+encodeURIComponent(atts["share_recommend"]));
						delete atts["share_recommend"];
					}
					// hashtags
					if (_.has(atts, "share_hashtag")) {
						params.push("hashtags="+encodeURIComponent(atts["share_hashtag"]));
						delete atts["share_hashtag"];
					}
				}
				// follow
				else if (atts["type"] == "follow") {

					// screen_name
					if (_.has(atts, "follow_user")) {
						params.push("screen_name="+encodeURIComponent(atts["follow_user"]));
						delete atts["follow_user"];
						// show_count
						var show_count = "show_count=false";
						if (_.has(atts, "show_followers_count") && atts["show_followers_count"] == "true") {
							show_count = "show_count=true";
							delete atts["show_followers_count"];
						}
						params.push(show_count);
						// show_screen_name
						var show_screen_name = "show_screen_name=true";
						if (_.has(atts, "follow_show_username") && atts["follow_show_username"] == "") {
							show_screen_name = "show_screen_name=false";
							delete atts["follow_show_username"];
						}
						params.push(show_screen_name);
					}
				}
				// hashtag
				else if (atts["type"] == "hashtag") {
					params.push("type=hashtag");

					// url
					if (_.has(atts, "hashtag_no_url") && atts["hashtag_no_url"] == "" && _.has(atts, "hashtag_custom_tweet_url")) {
						params.push("url="+encodeURIComponent(atts["hashtag_custom_tweet_url"]));
						delete atts["hashtag_no_url"];
						delete atts["hashtag_custom_tweet_url"];
					}
					// text
					if (_.has(atts, "hashtag_no_default") && atts["hashtag_no_default"] == "" && _.has(atts, "hashtag_custom_tweet_text")) {
						params.push("text="+encodeURIComponent(atts["hashtag_custom_tweet_text"]));
						delete atts["hashtag_no_default"];
						delete atts["hashtag_custom_tweet_text"];
					}
					// button_hashtag
					if (_.has(atts, "hashtag_hash")) {
						params.push("button_hashtag="+encodeURIComponent(atts["hashtag_hash"]));
						delete atts["hashtag_hash"];
					}
					// related
					var rel = [];
					if (_.has(atts, "hashtag_recommend_1")) {
						rel.push(atts["hashtag_recommend_1"]);
						delete atts["hashtag_recommend_1"];
					}
					if (_.has(atts, "hashtag_recommend_2")) {
						rel.push(atts["hashtag_recommend_2"]);
						delete atts["hashtag_recommend_2"];
					}
					if (rel.length > 0) {
						params.push("related="+encodeURIComponent(rel.join(',')));
					}
				}
				// mention
				else if (atts["type"] == "mention") {
					params.push("type=mention");

					// text
					if (_.has(atts, "mention_no_default") && atts["mention_no_default"] == "" && _.has(atts, "mention_custom_tweet_text")) {
						params.push("text="+encodeURIComponent(atts["mention_custom_tweet_text"]));
						delete atts["mention_no_default"];
						delete atts["mention_custom_tweet_text"];
					}
					// screen_name
					if (_.has(atts, "mention_tweet_to")) {
						params.push("screen_name="+encodeURIComponent(atts["mention_tweet_to"]));
						delete atts["mention_tweet_to"];
					}
					// related
					var rel = [];
					if (_.has(atts, "mention_recommend_1")) {
						rel.push(atts["mention_recommend_1"]);
						delete atts["mention_recommend_1"];
					}
					if (_.has(atts, "mention_recommend_2")) {
						rel.push(atts["mention_recommend_2"]);
						delete atts["mention_recommend_2"];
					}
					if (rel.length > 0) {
						params.push("related="+encodeURIComponent(rel.join(',')));
					}
				}

				atts["url"] = url+"#"+params.join('&');

			}
			// vc_gmaps
			if (_.has(atts, "maps")) {
				atts["greydClass"] = greyd.tools.generateGreydClass();
				atts["greydStyles"] = { width: "100%" };
				if (_.has(atts, "link")) {
					var link = decodeURIComponent(atob(atts["link"].replace('#E\-8_', '')));
					var regex_atts = new RegExp(/([\w-]+)="([^"]*)"/, 'g');
					var attribute = regex_atts.exec(link);
					while (attribute != null) {
						// console.log(attribute);
						var key = attribute[1];
						var value = attribute[2];
						if (key == "src") {
							atts["url"] = value;
						}
						if (key == "height") {
							atts["greydStyles"]["height"] = value+"px";
						}
						// next attribute
						attribute = regex_atts.exec(link);
					}
					delete atts["link"];
				}
				if (_.has(atts, "size") && atts["size"] != "") {
					var height = parseInt(atts["size"])+"px";
					if (!_.has(atts, "greydStyles") || atts["greydStyles"]["height"] != height) {
						atts["greydStyles"]["height"] = height;
					}
					delete atts["size"];
				}
			}
		}
		// wp widgets
		// core/calendar (nothing to convert)
		// core/page-list (nothing to convert)
		// core/tag-cloud (nothing to convert)
		// core/paragraph (nothing to convert)
		if (name == "core/search") {
			atts["showLabel"] = false;
		}
		if (name == "core/latest-comments") {
			atts = { ...atts, displayAvatar: false, displayDate: false, displayExcerpt: false };
			if (_.has(atts, "number")) {
				atts["commentsToShow"] = parseInt(atts["number"]);
				delete atts["number"];
			}
		}
		if (name == "greyd/menu") {
			if (_.has(atts, "nav_menu")) {
				for (var i=0; i<greyd.data.nav_menus.length; i++) {
					if (greyd.data.nav_menus[i]["id"] == atts["nav_menu"]) {
						atts["menu"] = greyd.data.nav_menus[i]["slug"];
						break;
					}
				}
				delete atts["nav_menu"];
			}
		}
		if (name == "core/latest-posts") {
			if (_.has(atts, "number")) {
				atts["postsToShow"] = parseInt(atts["number"]);
				delete atts["number"];
			}
			if (_.has(atts, "show_date") && atts["show_date"] == "1") {
				atts["displayPostDate"] = true;
				delete atts["show_date"];
			}
		}
		if (name == "core/categories" || name == "core/archives") {
			if (_.has(atts, "options")) {
				var options = atts["options"].split(',');
				if (options.indexOf("dropdown") > -1) atts["displayAsDropdown"] = true;
				if (options.indexOf("count") > -1) atts["showPostCounts"] = true;
				if (options.indexOf("hierarchical") > -1) atts["showHierarchy"] = true;
				delete atts["options"];
			}
		}
		if (name == "core/rss") {
			if (_.has(atts, "url")) {
				atts["feedURL"] = atts["url"];
				delete atts["url"];
				if (_.has(atts, "items")) {
					atts["itemsToShow"] = parseInt(atts["items"]);
					delete atts["items"];
				}
				if (_.has(atts, "options")) {
					var options = atts["options"].split(',');
					if (options.indexOf("show_summary") > -1) atts["displayExcerpt"] = true;
					if (options.indexOf("show_author") > -1) atts["displayAuthor"] = true;
					if (options.indexOf("show_date") > -1) atts["displayDate"] = true;
					delete atts["options"];
				}
			}
		}
		return atts;
	}

	function computeVcBasicAtts(atts) {
		// class
		if (_.has(atts, "el_class")) {
			atts['className'] = atts['el_class'];
			delete atts['el_class'];
		}
		// id
		if (_.has(atts, "el_id")) {
			atts['anchor'] = atts['el_id'].split(' ').join('_');
			delete atts['el_id'];
		}
		// css
		if (_.has(atts, "css")) {
			atts['inline_css'] = convertInlineCSS(atts['css']);
			delete atts['css'];
		}
		// css_animation

		return atts;
	}
	function computeVcColor(atts, param, custom) {
		var color = '';
		if (_.has(atts, param)) {
			color = getVcColor(atts[param]);
			if (atts[param] == 'custom' && _.has(atts, custom)) {
				color = atts[custom];
			}
		}
		return color;
	}
	function getVcColor(color) {
		var colors = {
			'': '',
			'info': '#dff2fe',
			'warning': '#fff4e2 ',
			'success': '#e6fdf8 ',
			'danger': '#fdeaea ',
			'alert-info': '#dff2fe',
			'alert-warning': '#fff4e2',
			'alert-success': '#e6fdf8',
			'alert-danger': '#fdeaea',
			'classic': '#f0f0f0 ',
			'default': '#f7f7f7',
			'primary': '#08c',
			'info': '#58b9da',
			'success': '#6ab165',
			'warning': '#f90',
			'danger': '#ff675b',
			'inverse': '#555',
			'blue': '#5472d2',
			'turquoise': '#00c1cf',
			'pink': '#fe6c61',
			'violet': '#8d6dc4',
			'peacoc': '#4cadc9',
			'chino': '#cec2ab',
			'mulled-wine': '#50485b',
			'mulled_wine': '#50485b',
			'vista-blue': '#75d69c',
			'vista_blue': '#75d69c',
			'orange': '#f7be68',
			'sky': '#5aa1e3',
			'green': '#6dab3c',
			'juicy-pink': '#f4524d',
			'juicy_pink': '#f4524d',
			'sandy-brown': '#f79468',
			'sandy_brown': '#f79468',
			'purple': '#b97ebb',
			'black': '#2a2a2a',
			'grey': '#ebebeb',
			'white': '#ffffff',
			'custom': ''
		};
		if (_.has(colors, color)) return colors[color];
		else return color;
	}

	function computeVcHeadingAtts(atts) {
		// common atts
		atts = computeVcBasicAtts(atts);
		
		// source and text
		if (_.has(atts, "source")) {
			if (atts["source"] == 'post_title') {
				atts["content"] = '<span class="is-tag" data-tag="title" data-params="">Title</span>';
			}
			delete atts['source'];
		}
		else if (_.has(atts, "text")) {
			atts['content'] = decodeURIComponent(atts['text']);
			delete atts['text'];
		}

		// link
		if (_.has(atts, "link")) {
			var link = convertLink(atts["link"]);
			var url = _.has(link, "url") ? link["url"] : "";
			var target = _.has(link, "target") ? 'target="_blank"' : "";
			atts["content"] = '<a href="'+url+'" '+target+'>'+atts["content"]+'</a>';
			delete atts["link"];
		}

		// font_container
		if (_.has(atts, "font_container")) {
			var typo = convertLink(atts["font_container"]);
			// console.log(typo);
			if (_.has(typo, "tag")) {
				// values 'p' and 'div' not supported
				if (typo["tag"] == 'p' || typo["tag"] == 'div') atts["level"] = 6;
				else atts["level"] = parseInt(typo["tag"].replace('h', ''));
			}
			if (_.has(typo, "text_align")) {
				// value 'justify' not supported
				if (typo["text_align"] == 'justify') atts["textAlign"] = 'center';
				else atts["textAlign"] = typo["text_align"];
			}
			if (_.has(typo, "font_size")) {
				var size = typo["font_size"];
				if (!_.has(atts, "style")) atts["style"] = { "typography": { "fontSize": size } };
				else if (!_.has(atts["style"], "typography")) atts["style"]["typography"] = { "fontSize": size };
				else atts["style"]["typography"]["fontSize"] = size;
			}
			// line_height		// not supported
			// text color
			if (_.has(typo, "color")) {
				atts["col"] = typo["color"];
				atts = computeTextColor("col", atts);
			}
			delete atts["font_container"];
		}

		// use_theme_fonts	// always 'yes'
		// google_fonts		// not supported

		return atts;
	}

	function computeVcButtonAtts(atts) {
		// common atts
		atts = computeVcBasicAtts(atts);

		// content
		if (_.has(atts, "title")) {
			atts["content"] = atts["title"];
			delete atts["title"];
		}

		// link
		if (_.has(atts, "link")) {
			var link = convertLink(atts["link"]);
			atts['trigger'] = {
				type: 'link',
				params: {
					url: _.has(link, "url") ? link["url"] : "",
					title: _.has(link, "title") ? link["title"] : "",
					opensInNewTab: _.has(link, "target") ? 1 : 0
				}
			}
			delete atts["link"];
		}
		// size
		if (_.has(atts, "size")) {
			if (atts["size"] == "lg") atts["size"] = "big";
			else if (atts["size"] == "md") atts["size"] = "";
			else atts["size"] = "small";
		}

		// style
		if (_.has(atts, "style")) {
			// default: modern/classic
			atts['className'] = 'is-style-prim';
			// styles: flat/outline/3d
			if (atts['style'] == "flat") atts['className'] = "is-style-sec";
			else if (atts['style'] == "outline") atts['className'] = "is-style-trd";
			else if (atts['style'] == "3d") atts['className'] = "is-style-trd";
			else {
				// custom styles
				atts["custom"] = true;
				// custom/outline-custom/gradient/gradient-custom
				if (atts['style'] == "custom") {
					var col = _.has(atts, "custom_background") ? atts["custom_background"] : "#ededed";
					var txt = _.has(atts, "custom_text") ? atts["custom_text"] : "#666666";
					atts["customStyles"] = {
						border: { top: '0px', right: '0px', bottom: '0px', left: '0px' },
						background: col,
						color: txt,
					};
				}
				else if (atts['style'] == "outline-custom") {
					var col = _.has(atts, "outline_custom_color") ? atts["outline_custom_color"] : "#666666";
					var bg_hover = _.has(atts, "outline_custom_hover_background") ? atts["outline_custom_hover_background"] : "#666666";
					var col_hover = _.has(atts, "outline_custom_hover_text") ? atts["outline_custom_hover_text"] : "#ffffff";
					atts["customStyles"] = {
						border: { 
							top: '2px solid '+col, 
							right: '2px solid '+col, 
							bottom: '2px solid '+col, 
							left: '2px solid '+col 
						},
						background: 'transparent',
						color: col,
						hover: {
							border: { 
								top: '2px solid '+col_hover, 
								right: '2px solid '+col_hover, 
								bottom: '2px solid '+col_hover, 
								left: '2px solid '+col_hover 
							},
							background: bg_hover,
							color: col_hover
						}
					};
				}
				else if (atts['style'] == "gradient") {
					var col1 = getVcColor(_.has(atts, "gradient_color_1") ? atts["gradient_color_1"] : "turquoise");
					var col2 = getVcColor(_.has(atts, "gradient_color_2") ? atts["gradient_color_2"] : "blue");
					atts["customStyles"] = {
						border: { top: '0px', right: '0px', bottom: '0px', left: '0px' },
						background: "linear-gradient(90deg,"+col1+" 0%,"+col2+" 100%)",
						hover: {
							background: "linear-gradient(90deg,"+col2+" 0%,"+col1+" 100%)",
						}
					};
				}
				else if (atts['style'] == "gradient-custom") {
					var col1 = _.has(atts, "gradient_custom_color_1") ? atts["gradient_custom_color_1"] : "#dd3333";
					var col2 = _.has(atts, "gradient_custom_color_2") ? atts["gradient_custom_color_2"] : "#eeee22";
					var txt = _.has(atts, "gradient_text_color") ? atts["gradient_text_color"] : "#ffffff";
					atts["customStyles"] = {
						border: { top: '0px', right: '0px', bottom: '0px', left: '0px' },
						background: "linear-gradient(90deg,"+col1+" 0%,"+col2+" 100%)",
						color: txt,
						hover: {
							background: "linear-gradient(90deg,"+col2+" 0%,"+col1+" 100%)",
						}
					};
				}
			}

			delete atts["style"];
		}
		if (!_.has(atts, "customStyles") && _.has(atts, "color")) {
			atts["custom"] = true;
			var col = getVcColor(_.has(atts, "color") ? atts["color"] : 'grey');
			if (atts['className'] == 'is-style-trd') {
				atts["customStyles"] = {
					border: { 
						top: '2px solid '+col, 
						right: '2px solid '+col, 
						bottom: '2px solid '+col, 
						left: '2px solid '+col 
					},
					color: col,
				};
			}
			else {
				atts["customStyles"] = {
					border: { top: '0px', right: '0px', bottom: '0px', left: '0px' },
					background: col,
				};
			}
			delete atts["color"];
		}

		// shape
		if (_.has(atts, "shape")) {
			var radius = "";
			if (atts["shape"] == "rounded") radius = "5px";
			if (atts["shape"] == "square") radius = "0px";
			if (atts["shape"] == "round") radius = "2em";
			var customStyles = {
				borderRadius: radius
			};
			if (_.has(atts, "customStyles")) {
				customStyles = { ...atts["customStyles"], ...customStyles};
			}
			atts["custom"] = true;
			atts["customStyles"] = customStyles;
			delete atts["shape"];
		}

		return atts;
	}

	function computeVcSeperatorAtts(atts) {
		// color
		if (_.has(atts, "color")) {
			atts["customColor"] = computeVcColor(atts, 'color', 'accent_color');
			delete atts['color'];
			delete atts['accent_color'];
		}
		// align			// not supported

		// greydStyles
		var greydStyles = { width: '100%', opacity: '100%' };

		// style
		if (_.has(atts, "style")) {
			// dashed and dotted
			if  (atts["style"] == 'dashed' || atts["style"] == 'dotted') {
				atts["dots"] = true;
				greydStyles.borderStyle = "dotted";
				greydStyles.borderColor = "";
				greydStyles.background = "none";
			}
			// double and shadow not supported
			delete atts['style'];
		}

		// border_width
		if (_.has(atts, "border_width")) {
			if (_.has(atts, "dots") && atts["dots"] === true) {
				greydStyles.borderBottomWidth = atts["border_width"]+'px';
			}
			else {
				greydStyles.height = atts["border_width"]+'px';
			}
			delete atts['border_width'];
		}

		// el_width
		if (_.has(atts, "el_width") && atts['el_width'] != "") {
			greydStyles.width = atts['el_width']+'%';
			delete atts['el_width'];
		}

		atts["greydClass"] = greyd.tools.generateGreydClass();
		atts["greydStyles"] = _.clone(greydStyles);

		return atts;
	}

	function computeVcImageAtts(atts) {
		// alignment
		if (_.has(atts, "alignment")) {
			atts['align'] = atts['alignment'];
			delete atts['alignment'];
		}
		// source
		var img = { type: "file" };
		if (_.has(atts, "source")) {
			// media_library -> default
			// external_link -> not supported
			// featured_image
			if (atts['source'] == "featured_image") {
				img = {
					type: "dynamic",
					tag: "image"
				};
			}
			delete atts['source'];
		}
		// image
		if (_.has(atts, "image")) {
			if (img["type"] == "file") {
				img["id"] = atts['image'];
			}
			delete atts['image'];
		}
		atts['image'] = img;

		// image size
		if (_.has(atts, "img_size")) {
			if (atts['img_size'].indexOf("x") > 0) {
				var width = atts['img_size'].split("x");
				atts["greydStyles"] = { width: width[0]+'px' };
			}
			else {
				if (atts['img_size'] == "thumbnail") atts["greydStyles"] = { width: '150px' };
				if (atts['img_size'] == "medium") atts["greydStyles"] = { width: '300px' };
				if (atts['img_size'] == "full") atts["greydStyles"] = { width: '1024px' };
				else atts["greydStyles"] = { width: '100%' };
			}
			if (_.has(atts, "greydStyles")) atts["greydClass"] = greyd.tools.generateGreydClass();
			delete atts['img_size'];
		}

		// style
		if (_.has(atts, "style")) {
			var classes = _.has(atts, "className") ? atts["className"] : "";
			switch (atts['style']) {
				case "":				// no style
				case "vc_box_border":	// not supported
				case "vc_box_outline":	// not supported
					break;
				case "vc_box_rounded": 
					classes += "is-style-rounded-corners"; 
					break;
				case "vc_box_shadow":
				case "vc_box_shadow_border":
				case "vc_box_shadow_3d": 
					classes += "is-style-has-shadow"; 
					break;
				default: 				// default: rounded (vc_box_***_circle)
					classes += "is-style-rounded";
			}
			atts["className"] = classes;
			delete atts['style'];
		}

		// trigger
		var trigger = false;
		if (_.has(atts, "onclick") && atts["onclick"] != "") {
			if (atts["onclick"] == "custom_link" && _.has(atts, "link")) {
				// custom link
				trigger = {
					type: "link",
					params: { url: atts["link"] }
				};
				delete atts['link'];
			}
			else if (_.has(atts, "image")) {
				// image link
				if (atts['image']['type'] == 'dynamic') {
					// dynamic link
					trigger = {
						type: "dynamic",
						params: { tag: "image" }
					};
				}
				else if (_.has(greyd.data.media_urls, atts['image']['id'])) {
					// get image url
					trigger = {
						type: "link",
						params: { url: greyd.data.media_urls[atts['image']['id']].src }
					};
				}
			}
			if (trigger) {
				if (_.has(atts, "img_link_target") && atts["img_link_target"] == "_blank") {
					trigger.params.opensInNewTab = true;
					delete atts['img_link_target'];
				}
				atts["trigger"] = trigger;
			}
			delete atts['onclick'];
		}

		return atts;
	}

	function computeVcGalleryAtts(atts) {
		// image size
		var size = "";
		if (_.has(atts, "external_img_size")) {
			// external_link
			size = atts['external_img_size'];
			delete atts['external_img_size'];
		}
		else if (_.has(atts, "img_size")) {
			// media_library -> default
			size = atts["img_size"];
			delete atts['img_size'];
		}
		else if (_.has(atts, "element_width")) {
			// media_grid and masonry_media_grid
			switch(atts['element_width']) {
				case "2": atts["columns"] = 6; break;
				case "3": atts["columns"] = 4; break;
				case "4": atts["columns"] = 3; break;
				case "6": atts["columns"] = 2; break;
				case "12": atts["columns"] = 1; break;
			}
			delete atts['element_width'];
		}
		if (size != "") {
			var width = '100%';
			if (size.indexOf("x") > 0) {
				width = size.split("x");
				width = parseInt(width[0]);
			}
			else {
				if (size == "thumbnail") width = 150;
				if (size == "medium") width = 300;
				if (size == "full") width = 1024;
				else width = '100%';
			}
			if (width == '100%') atts["columns"] = 1;
			else if (width < 150) atts["columns"] = 8;
			else if (width < 300) atts["columns"] = 4;
			else if (width < 1024) atts["columns"] = 2;
			else atts["columns"] = 1;
		}

		return atts;
	}

	function computeVcBackground(atts, inner) {
		var box_atts = computeVcBackgroundAtts(atts);
		if (!_.isEmpty(box_atts)) {
			// get css
			if (_.has(atts, "inline_css")) {
				var value = atts["inline_css"].split(';\n');
				var greydStyles = {};
				var padding = {};
				var margin = {};
				for (var i=0; i<value.length; i++) {
					if (_.isEmpty(value[i])) continue;
					var pair = value[i].split(':');
					pair[1] = pair[1].replace(" !important", "");
					[ "top", "right", "bottom", "left" ].forEach(function(side) {
						if (pair[0] == "padding-"+side) padding[side] = pair[1];
						if (pair[0] == "margin-"+side) margin[side] = pair[1];
					});
				}
				if (!_.isEmpty(padding)) {
					greydStyles["padding"] = { top: "0px", right: "0px", bottom: "0px", left: "0px", ...padding };
				}
				if (!_.isEmpty(margin)) {
					greydStyles["margin"] = { top: "0px", right: "0px", bottom: "0px", left: "0px", ...margin };
				}
				if (!_.isEmpty(greydStyles)) {
					box_atts["greydClass"] = greyd.tools.generateGreydClass();
					box_atts["greydStyles"] = greydStyles;
				}
				delete atts["inline_css"];
			}

			// make inner box
			inner = [ wp.blocks.createBlock( "greyd/box", box_atts, inner) ];
		}
		return inner;
	}
	function computeVcBackgroundAtts(atts) {
		var box_atts = {};
		// video
		if (_.has(atts, "video_bg") && atts["video_bg"] == "yes") {
			box_atts["background"] = greyd.tools.layout.getBackgroundDefaults().background;
			box_atts["background"]["type"] = "video";
			if (_.has(atts, "video_bg_url") && atts["video_bg_url"] != "") {
				box_atts["background"]["video"]['url'] = atts["video_bg_url"];
				delete atts["video_bg_url"];
			}
		}
		// image
		else if (_.has(atts, "parallax") && atts["parallax"] != "") {
			box_atts["background"] = greyd.tools.layout.getBackgroundDefaults().background;
			box_atts["background"]["type"] = "image";
			if (_.has(atts, "parallax_image") && atts["parallax_image"] != "") {
				var media = parseInt(atts["parallax_image"]);
				delete atts["parallax_image"];
				if (_.has(greyd.data.media_urls, media)) media = greyd.data.media_urls[media];
				if (typeof media === 'object') {
					box_atts["background"]["image"]["url"] = media["src"];
					box_atts["background"]["image"]["id"] = media["id"];
				}
			}
		}
		// scroll
		if (!_.isEmpty(box_atts)) {
			if (box_atts["background"]["type"] == "video") {
				if (_.has(atts, "video_bg_parallax") && atts["video_bg_parallax"] != "") {
					box_atts["background"]["scroll"]["type"] = "vparallax";
					var speed = _.has(atts, "parallax_speed_video") ? parseFloat(atts["parallax_speed_video"]) : 1.5;
					box_atts["background"]["scroll"]["parallax"] = Math.floor((speed-1.0)*200);
					delete atts["parallax_speed_video"];
					delete atts["video_bg_parallax"];
				}
			}
			if (box_atts["background"]["type"] == "image") {
				box_atts["background"]["scroll"]["type"] = "vparallax";
				var speed = _.has(atts, "parallax_speed_bg") ? parseFloat(atts["parallax_speed_bg"]) : 1.5;
				box_atts["background"]["scroll"]["parallax"] = Math.floor((speed-1.0)*200);
				delete atts["parallax_speed_bg"];
				
			}
		}
		return box_atts;
	}

	var attsMap = {
		// to core blocks
		"core/group":		[ 	{ key: "el_class", map: "className" }, 
							  	{ key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  	{ inner: function(atts, inner) { 
									// vc converter
									return computeVcBackground(atts, inner);
								} },
								{ compute: function(atts) { 
									var classes = [];
									if (_.has(atts, "disable_element") && atts["disable_element"] == "yes") classes.push("is-hidden");
									if (_.has(atts, "className")) classes.push(atts["className"]);
									atts["className"] = classes.join(' ');
									return atts;
							  	} },
								{ computeConditional: function(atts, inner) {
									// console.log(atts);
									// console.log(inner);
									var regex_sc = new RegExp(/\[(\[?)(\w+)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)(\[\/\2\]))?)(\]?)/, 'g');
									var regex_atts = new RegExp(/([\w-]+)="([^"]*)"/, 'g');
									var shortcode = regex_sc.exec(inner);
									
									if (shortcode[2] !== "vc_form_condition") return;
									
									var shortcodeBlocks = [];

									var firstCondition = true;

									while (shortcode != null) {
										var newBlock = null;
										var newInnerBlocks = null;
										var innerBlocks = shortcode[5];

										var atts_sc = shortcode[3];
										
										var attribute = regex_atts.exec(atts_sc);

										var childAtts = {};
										var newAtts = {};

										while (attribute != null) {
											// console.log(attribute);
											var key = attribute[1];
											var value = attribute[2];
											childAtts[key] = value;
											// skip layout atts
											attribute = regex_atts.exec(atts_sc);
										}
										
										// Operator
										if ( _.has(childAtts, "condition_type") ) {
											newAtts["operator"] = decodeHtml(childAtts["condition_type"]) == "&&" ? "AND" : "OR";
										}
										
										// Container nach Versenden stehen lassen
										if ( _.has(childAtts, "reset_after") ) {
											newAtts["disableReset"] = childAtts["reset_after"];
										}
										
										// Container nach Versenden stehen lassen
										if ( _.has(childAtts, "anim") ) {
											newAtts["animation"] = childAtts["anim"];
										}
												

										// Field
										var dependantFields = [];
										if ( _.has(atts, "dependant_field") ) {
											dependantFields.push(atts["dependant_field"]);
										}
										if ( _.has(atts, "dependant_field2") ) {
											dependantFields.push(atts["dependant_field2"]);
										}
										if ( _.has(atts, "dependant_field3") ) {
											dependantFields.push(atts["dependant_field3"]);
										}
										// console.log(atts);
										// console.log(childAtts);
										// console.log(dependantFields);
										// Conditions
										const conditions = [];

										if ( _.has(childAtts, "options") ) {
											const options = JSON.parse( decodeURIComponent( childAtts['options'] ) );
											console.log(options);
											options.forEach(function(option) {
												const field = parseInt(option.field);
												console.log(field);
												conditions.push( {condition: option.condition, field: dependantFields[field], value: option.value ? option.value : ""} )
											});
											newAtts["conditions"] = conditions;
										}

										if ( _.has(childAtts, "default_view") && childAtts["default_view"] == "show"){
											newAtts["hideOnload"] = false;
										}

										// parse innerBlocks
										newInnerBlocks = parseBlocks(innerBlocks).innerBlocks;

										// create Blocks
										newBlock = wp.blocks.createBlock( "greyd-forms/condition", newAtts, newInnerBlocks );

										// push Blocks to Array
										if (newBlock != null) shortcodeBlocks.push(newBlock);

										// get the next shortcode
										shortcode = regex_sc.exec(inner);
									}
								
									return shortcodeBlocks;
								}}
							
							],
		"core/columns":		[ { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "content_placement", map: "verticalAlignment" },
							  { key: "row_type", default: "row_xxl" },
							  { compute: function(atts) { 
									// row
									if (wp.blocks.getBlockType("core/columns").attributes.row) {
										atts["row"] = JSON.parse(JSON.stringify(wp.blocks.getBlockType("core/columns").attributes.row.default));
										if (_.has(atts, "row_type")) {
											atts["row"]["type"] = atts["row_type"];
											delete atts["row_type"];
										}
									}
									// if (_.has(atts, "ignore_inner_gutter")) {
									// 	atts["row"]["ignore_inner_gutter"] = (atts["ignore_inner_gutter"] == "true");
									// 	delete atts["ignore_inner_gutter"];
									// }
									// if (_.has(atts, "override_type")) {
									// 	var override_type = atts["override_type"].split(',');
									// 	delete atts["override_type"];
									// 	if (override_type.indexOf("override_sm") > -1) atts["row"]["override"]["sm"] = true;
									// 	if (override_type.indexOf("override_md") > -1) atts["row"]["override"]["md"] = true;
									// 	if (override_type.indexOf("override_lg") > -1) atts["row"]["override"]["lg"] = true;
									// 	if (override_type.indexOf("override_xl") > -1) atts["row"]["override"]["xl"] = true;
									// }
									// if (_.has(atts, "override_gutter")) {
									// 	var override_gutter = atts["override_gutter"].split(',');
									// 	delete atts["override_gutter"];
									// 	if (override_gutter.indexOf("override_sm") > -1) atts["row"]["override_gutter"]["sm"] = true;
									// 	if (override_gutter.indexOf("override_md") > -1) atts["row"]["override_gutter"]["md"] = true;
									// 	if (override_gutter.indexOf("override_lg") > -1) atts["row"]["override_gutter"]["lg"] = true;
									// 	if (override_gutter.indexOf("override_xl") > -1) atts["row"]["override_gutter"]["xl"] = true;
									// }
									var classes = [];
									// var classes = [ "vc_row", atts["row"]["type"] ];
									// if (atts["row"]["ignore_inner_gutter"] == true) classes.push("ignore_inner_gutter");
									// if (atts["row"]["override"]["sm"] == true) classes.push("row_override_sm");
									// if (atts["row"]["override"]["md"] == true) classes.push("row_override_md");
									// if (atts["row"]["override"]["lg"] == true) classes.push("row_override_lg");
									// if (atts["row"]["override"]["xl"] == true) classes.push("row_override_xl");
									// if (atts["row"]["override_gutter"]["sm"] == true) classes.push("row_override_gutter_sm");
									// if (atts["row"]["override_gutter"]["md"] == true) classes.push("row_override_gutter_md");
									// if (atts["row"]["override_gutter"]["lg"] == true) classes.push("row_override_gutter_lg");
									// if (atts["row"]["override_gutter"]["_xl"] == true) classes.push("row_override_gutter_xl");
									if (_.has(atts, "disable_element") && atts["disable_element"] == "yes") {
										classes.push("is-hidden");
									}
									if (_.has(atts, "flex_stretch_content") && !_.isEmpty(atts.flex_stretch_content)) {
										classes.push("flex-stretch-content-"+atts.flex_stretch_content);
										delete atts.flex_stretch_content;
									}
									if (_.has(atts, "className")) {
										classes.push(atts["className"]);
									}
									atts["className"] = classes.join(' ');

									// background
									var background = greyd.tools.layout.getBackgroundDefaults();
									atts["background"] = { 
										...background.background, 
										overlay: background.overlay, 
										pattern: background.pattern, 
										seperator: background.seperator 
									};
									if (_.has(atts, "vc_bg_size")) {
										// atts["background"]["size"] = atts["vc_bg_size"];
										delete atts["vc_bg_size"];
									}
									if (_.has(atts, "vc_bg_type") && atts["vc_bg_type"] != "") {
										atts = computeBackground("vc_bg", atts);
									}
									else {
										// vc converter
										var box_atts = computeVcBackgroundAtts(atts);
										if (!_.isEmpty(box_atts)) {
											atts["background"] = { ...atts["background"], ...box_atts["background"] };
										}
									}
									// overlay
									if (_.has(atts, "vc_bg_overlay") && atts["vc_bg_overlay"] == "true") {
										delete atts["vc_bg_overlay"];
										if (_.has(atts, "vc_bg_overlay_type") && atts["vc_bg_overlay_type"] != "") {
											if (_.has(atts, "vc_bg_overlay_color_opacity")) {
												atts["background"]["overlay"]["opacity"] = parseInt(atts["vc_bg_overlay_color_opacity"]);
												delete atts["vc_bg_overlay_color_opacity"];
											}
											var type = atts["vc_bg_overlay_type"].replace('vc_bg_', '');
											if (type == "gradient2") type = "gradient";
											atts["background"]["overlay"]["type"] = type;
											delete atts["vc_bg_overlay_type"];
											if (type == "color") {
												atts["background"]["overlay"]["color"] = computeColor("vc_bg_overlay_colorselect", atts);
											}
											if (type == "gradient") {
												atts["background"]["overlay"]["gradient"] = computeGradient("vc_bg_overlay_gradient2", atts);
											}
										}
									}
									// pattern
									if (_.has(atts, "vc_bg_overlay_pattern") && atts["vc_bg_overlay_pattern"] != "") {
										var pattern = atts["background"]["pattern"];
										pattern["type"] = atts["vc_bg_overlay_pattern"];
										delete atts["vc_bg_overlay_pattern"];
										if (_.has(atts, "vc_bg_overlay_cpattern") && atts["vc_bg_overlay_cpattern"] != "") {
											var image = parseInt(atts["vc_bg_overlay_cpattern"]);
											delete atts["vc_bg_overlay_cpattern"];
											if (_.has(greyd.data.media_urls, image)) image = greyd.data.media_urls[image];
											if (typeof image === 'object') {
												pattern["url"] = image["src"];
												pattern["id"] = image["id"];
											}
										}
										if (_.has(atts, "vc_bg_overlay_opacity")) {
											pattern["opacity"] = parseInt(atts["vc_bg_overlay_opacity"]);
											delete atts["vc_bg_overlay_opacity"];
										}
										if (_.has(atts, "vc_bg_overlay_size") && atts["vc_bg_overlay_size"] != "") {
											pattern["size"] = atts["vc_bg_overlay_size"].replace('px', '')+"px";
											delete atts["vc_bg_overlay_size"];
										}
										if (_.has(atts, "vc_bg_overlay_scroll") && atts["vc_bg_overlay_scroll"] != "") {
											pattern["scroll"] = atts["vc_bg_overlay_scroll"];
											delete atts["vc_bg_overlay_scroll"];
										}
										atts["background"]["pattern"] = pattern;
									}
									// seperator
									if (_.has(atts, "vc_bg_seperator") && atts["vc_bg_seperator"] == "true") {
										delete atts["vc_bg_seperator"];
										if (_.has(atts, "vc_bg_seperator_type") && atts["vc_bg_seperator_type"] != "") {
											var seperator = atts["background"]["seperator"];
											seperator["type"] = atts["vc_bg_seperator_type"];
											delete atts["vc_bg_seperator_type"];
											if (_.has(atts, "vc_bg_seperator_height") && atts["vc_bg_seperator_height"] != "") {
												seperator["height"] = atts["vc_bg_seperator_height"].replace('px', '')+"px";
												delete atts["vc_bg_seperator_height"];
											}
											if (_.has(atts, "vc_bg_seperator_position") && atts["vc_bg_seperator_position"] != "") {
												seperator["position"] = atts["vc_bg_seperator_position"];
												delete atts["vc_bg_seperator_position"];
											}
											if (_.has(atts, "vc_bg_seperator_zposition") && atts["vc_bg_seperator_zposition"] != "") {
												seperator["zposition"] = atts["vc_bg_seperator_zposition"];
												delete atts["vc_bg_seperator_zposition"];
											}
											if (_.has(atts, "vc_bg_seperator_colorselect")) {
												seperator["color"] = computeColor("vc_bg_seperator_colorselect", atts);
											}
											if (_.has(atts, "vc_bg_seperator_opacity")) {
												seperator["opacity"] = parseInt(atts["vc_bg_seperator_opacity"]);
												delete atts["vc_bg_seperator_opacity"];
											}
											atts["background"]["seperator"] = seperator;
										}
									}

									// cleaning
									atts["background"] = greyd.tools.layout.cleanBackgroundvalues(atts["background"]);
									if (_.isEmpty(atts["background"])) delete atts["background"];
									// console.log(atts["background"]);
									return atts;
							  } } ],
		"core/column":		[ { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { inner: function(atts, inner) { 
									// vc converter
									return computeVcBackground(atts, inner);
							  } },
							  { compute: function(atts) { 
									// responsive
									// atts["responsive"] = JSON.parse(JSON.stringify(wp.blocks.getBlockType("core/column").attributes.responsive.default));
									var defaults = {
										width: { xs: "col-12", sm: "", md: "", lg: "" },
										offset: { xs: "", sm: "", md: "", lg: "" },
										disable: { xs: false, sm: false, md: false, lg: false },
									};
									atts["responsive"] = JSON.parse(JSON.stringify(defaults));
									if (_.has(atts, "width")) {
										var width = atts["width"];
										delete atts["width"];
										if (width == "100%") width = "col-md-12";
										else {
											if (width.indexOf("/") > -1) {
												var frac = width.split('/'); 
												if (frac[1] == "5") width = "col-md-"+width;
												else width = "col-md-"+parseInt(12 * (parseInt(frac[0]) / parseInt(frac[1])));
											}
										}
										atts["responsive"]["width"]["md"] = width;
									}
									var offset = [];
									if (_.has(atts, "offset")) {
										offset = atts["offset"].split(' ');
										delete atts["offset"];
										if (offset.indexOf("vc_hidden-xs") > -1) atts["responsive"]["disable"]["xs"] = true;
										if (offset.indexOf("vc_hidden-sm") > -1) atts["responsive"]["disable"]["sm"] = true;
										if (offset.indexOf("vc_hidden-md") > -1) atts["responsive"]["disable"]["md"] = true;
										if (offset.indexOf("vc_hidden-lg") > -1) atts["responsive"]["disable"]["lg"] = true;
										for (var i=0; i<offset.length; i++) {
											// offset
											if (offset[i].indexOf('vc_col-xs-offset') > -1) atts["responsive"]["offset"]["xs"] = offset[i].replace('vc_col-xs-offset', 'offset');
											else if (offset[i].indexOf('vc_col-xs') > -1) 	atts["responsive"]["width"]["xs"] = offset[i].replace('vc_col-xs', 'col');
											if (offset[i].indexOf('vc_col-sm-offset') > -1) atts["responsive"]["offset"]["sm"] = offset[i].replace('vc_col-sm-offset', 'offset-sm');
											else if (offset[i].indexOf('vc_col-sm') > -1) 	atts["responsive"]["width"]["sm"] = offset[i].replace('vc_col-sm', 'col-sm');
											if (offset[i].indexOf('vc_col-md-offset') > -1) atts["responsive"]["offset"]["md"] = offset[i].replace('vc_col-md-offset', 'offset-md');
											else if (offset[i].indexOf('vc_col-md') > -1) 	atts["responsive"]["width"]["md"] = offset[i].replace('vc_col-md', 'col-md');
											if (offset[i].indexOf('vc_col-lg-offset') > -1) atts["responsive"]["offset"]["lg"] = offset[i].replace('vc_col-lg-offset', 'offset-lg');
											else if (offset[i].indexOf('vc_col-lg') > -1) 	atts["responsive"]["width"]["lg"] = offset[i].replace('vc_col-lg', 'col-lg');
										}
									}
									atts["responsive"] = greyd.tools.makeValues(defaults, atts["responsive"]);
									// console.log(atts["responsive"]);
									if (_.isEmpty(atts["responsive"]["width"])) atts["responsive"]["width"] = { sm: "" } 
									else if (_.isEmpty(atts["responsive"]["width"]["sm"])) {
										atts["responsive"]["width"]["sm"] = "";
									}
									if (_.isEmpty(atts["responsive"])) delete atts["responsive"];
									var classes = [];
									if (_.has(atts, "className")) {
										var className = atts["className"].split(' ');
										for (var i=0; i<className.length; i++) {
											// push
											if (className[i].indexOf('vc_col-xs-push') > -1)      { 
												classes.push(className[i].replace('vc_col-xs-push', 'push'));
												// atts["responsive"]["push"]["xs"] = className[i].replace('vc_col-xs-push', 'push');
												// atts["responsive"]["push_pull"]["xs"] = "push";
											}
											else if (className[i].indexOf('vc_col-sm-push') > -1) { 
												classes.push(className[i].replace('vc_col-sm-push', 'push-sm'));
												// atts["responsive"]["push"]["sm"] = className[i].replace('vc_col-sm-push', 'push-sm');
												// atts["responsive"]["push_pull"]["sm"] = "push";
											}
											else if (className[i].indexOf('vc_col-md-push') > -1) { 
												classes.push(className[i].replace('vc_col-md-push', 'push-md'));
												// atts["responsive"]["push"]["md"] = className[i].replace('vc_col-md-push', 'push-md');
												// atts["responsive"]["push_pull"]["md"] = "push";
											}
											else if (className[i].indexOf('vc_col-lg-push') > -1) { 
												classes.push(className[i].replace('vc_col-lg-push', 'push-lg'));
												// atts["responsive"]["push"]["lg"] = className[i].replace('vc_col-lg-push', 'push-lg');
												// atts["responsive"]["push_pull"]["lg"] = "push";
											}
											// pull
											else if (className[i].indexOf('vc_col-xs-pull') > -1) { 
												classes.push(className[i].replace('vc_col-xs-pull', 'pull'));
												// atts["responsive"]["pull"]["xs"] = className[i].replace('vc_col-xs-pull', 'pull');
												// atts["responsive"]["push_pull"]["xs"] = "pull";
											}
											else if (className[i].indexOf('vc_col-sm-pull') > -1) { 
												classes.push(className[i].replace('vc_col-sm-pull', 'pull-sm'));
												// atts["responsive"]["pull"]["sm"] = className[i].replace('vc_col-sm-pull', 'pull-sm');
												// atts["responsive"]["push_pull"]["sm"] = "pull";
											}
											else if (className[i].indexOf('vc_col-md-pull') > -1) { 
												classes.push(className[i].replace('vc_col-md-pull', 'pull-md'));
												// atts["responsive"]["pull"]["md"] = className[i].replace('vc_col-md-pull', 'pull-md');
												// atts["responsive"]["push_pull"]["md"] = "pull";
											}
											else if (className[i].indexOf('vc_col-lg-pull') > -1) { 
												classes.push(className[i].replace('vc_col-lg-pull', 'pull-lg'));
												// atts["responsive"]["pull"]["lg"] = className[i].replace('vc_col-lg-pull', 'pull-lg');
												// atts["responsive"]["push_pull"]["lg"] = "pull";
											}
											// other class
											else classes.push(className[i]);
										}
									}
									atts["className"] = classes.join(' ');
									return atts;
							  } } ],
		"core/paragraph":	[ { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "content", fill: function(inner) { 
								    var { options } = greyd.dynamic.tags.getCurrentOptions('tags', false);
									var matched_content = greyd.dynamic.tags.match(options, [{
										dvalue: encodeURIComponent(inner),
										dtype: 'textarea'
									}]);
									var content = (matched_content) ? decodeURIComponent(matched_content[0].dvalue) : inner;
									// console.log(content);
								  	return content.trim().split('\n').join('<br>');
							  	} } ],
		"core/embed":		[ { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "link" , map: "url" },
							  { key: "el_width" , map: "width", convert: function(value) { console.log(value); return value+"%" } },
							  { key: "pull", map: "align", convert: function(value) { return value.replace('_', '') } },
							  { key: "iframe_width" , map: "width" },
							  { key: "iframe_height" , map: "height" },
							//   { key: "src" , map: "providerNameSlug" },
							//   { key: "src" , transform: { value: "iframe", block: "greyd/iframe" } },
							  { transform: function(atts) { 
									block_sc = "core/embed";
									if (_.has(atts, "src") && (atts["src"] == "iframe")) {
										block_sc = "greyd/iframe";
									}
									return block_sc;
						  	  } },
							  { compute: function(atts) { 
									atts["type"] = "rich";
									atts["className"] = ((_.has(atts, 'el_class') ? atts["el_class"] : "")+" wp-embed-aspect-16-9 wp-has-aspect-ratio").trim();
									atts["responsive"] = true;
									if (_.has(atts, "url")) {
										if (atts["url"].indexOf("youtu") > -1) {
											atts["src"] = "youtube";
											atts["type"] = "video";
										}
										else if (atts["url"].indexOf("vimeo") > -1) {
											atts["src"] = "vimeo";
											atts["type"] = "video";
										}
									}
									return atts;
							  } } ],
		"core/heading":		[ { key: "title" , map: "content" }, 
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "align", map: "textAlign", convert: function(value) { return value.replace('_', '') } },
							  { key: "text", map: "content", convert: function(value) { return decodeURIComponent(value) } },
							  { compute: function(atts) {
									if (_.has(atts, "content")) { 
										var { options } = greyd.dynamic.tags.getCurrentOptions('tags', false);
										var matched_content = greyd.dynamic.tags.match(options, [{
											dvalue: encodeURIComponent(atts['content']),
											dtype: 'textarea'
										}]);
										if (matched_content) atts['content'] = decodeURIComponent(matched_content[0].dvalue);
									}
									if (_.has(atts, "versal")) {
										if (atts['versal'] == "true") atts['content'] = '<span class="is-upper">'+atts['content']+'</span>';
										delete atts['versal'];
									}
									if (_.has(atts, "color")) {
										atts = computeTextColor("color", atts);
									}
									if (_.has(atts, "h") && _.has(atts, "h_tag")) {
										atts['fontSize'] = atts['h'].replace('h', 'h-');
										delete atts['h'];
									}
									else if (_.has(atts, "h")) {
										atts['level'] = parseInt(atts['h'].replace('h', ''));
										delete atts['h'];
									}
									if (_.has(atts, "h_tag")) {
										atts['level'] = parseInt(atts['h_tag'].replace('h', ''));
										delete atts['h_tag'];
									}
									if (_.has(atts, "font_size")) {
										var size = atts['font_size'];
										delete atts['font_size'];
										if (size.indexOf('em') > -1) {
											var body = 16;
											for (var j=0; j<greyd.data.fontSizes.length; j++) {
												if (greyd.data.fontSizes[j].slug == 'txt') {
													body = greyd.data.fontSizes[j].size;
													break;
												}
											}
											size = body*(size.replace('em', ''));
										}
										if (!_.has(atts, "style")) atts["style"] = { "typography": { "fontSize": size } };
										else if (!_.has(atts["style"], "typography")) atts["style"]["typography"] = { "fontSize": size };
										else atts["style"]["typography"]["fontSize"] = size;
									}
									if (_.has(atts, "font_weight")) {
										var weight = atts['font_weight'];
										delete atts['font_weight'];
										if (!_.has(atts, "style")) atts["style"] = { "typography": { "fontStyle": "normal", "fontWeight": weight } };
										else if (!_.has(atts["style"], "typography")) atts["style"]["typography"] = { "fontStyle": "normal", "fontWeight": weight };
										else {
											atts["style"]["typography"]["fontStyle"] = "normal";
											atts["style"]["typography"]["fontWeight"] = weight;
										}
									}
									return atts;
							  } } ],
		"core/spacer":		[ { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "height", convert: function(value) { return parseInt(value.replace('px', ''))+'px' } },
							  { compute: function(atts) {
									// responsive
									var defaults = { sm: "25%", md: "50%", lg: "75%", xl: "100%" };
									var height = {};
									if (_.has(atts, "height_sm")) {
										if (atts["height_sm"] != defaults.sm) height.sm = parseInt(atts["height_sm"])+'%';
										delete atts["height_sm"];
									}
									if (_.has(atts, "height_md")) {
										if (atts["height_md"] != defaults.md) height.md = parseInt(atts["height_md"])+'%';
										delete atts["height_md"];
									}
									if (_.has(atts, "height_lg")) {
										if (atts["height_lg"] != defaults.lg) height.lg = parseInt(atts["height_lg"])+'%';
										delete atts["height_lg"];
									}
									if (_.has(atts, "height_xl")) {
										if (atts["height_xl"] != defaults.xl) height.xl = parseInt(atts["height_xl"])+'%';
										delete atts["height_xl"];
									}
									if (!_.isEmpty(height)) atts["responsive"] = { height: height };
									// var height = computeResponsive("height", atts, atts["responsive"]["height"]);
									// delete height['max'];
									// atts["responsive"]["height"] = height;
									return atts;
							  } } ],
		"core/html":		[ { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "content", fill: function(inner) { return decodeURIComponent(atob(inner)) } } ],
		"core/image":		[ { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "width", map: "w" },
							  { key: "pull", map: "align", convert: function(value) { return value.replace('_', '') } },
							  { key: "sizeSlug", default: "large" },
							  { key: "linkDestination", default: "none" },
							  { transform: function(atts) { 
									block_sc = "core/image";

									const hasDynamicFields = _.has(atts, "dynamic_icon") && !_.isEmpty(atts.dynamic_icon);
									const usesDynamicTag   = _.has(atts, "icon") && String(atts.icon).indexOf('_') === 0;

									// convert it into 'greyd/image'
									if ( hasDynamicFields || usesDynamicTag ) {
										block_sc = 'greyd/image';
									}
									// maybe convert it into 'greyd/anim'
									else if (_.has(atts, "icon")) {
										var icon = atts["icon"];
										if (icon != "") {
											var media = "";
											if (_.has(greyd.data.media_urls, parseInt(icon))) media = greyd.data.media_urls[parseInt(icon)];
											if (media != "") {
												if (media.src && media.src.indexOf('.json') > -1) block_sc = "greyd/anim";
											}
										}
									}
									return block_sc;
							  } },
							  { compute: function(atts) {
									console.log(atts);
									var mode = "img";

									// get icon
									if (_.has(atts, "icon")) {
										var icon = atts["icon"];
										if (icon != "") {
											var media = "";
											if (_.has(greyd.data.media_urls, parseInt(icon))) media = greyd.data.media_urls[parseInt(icon)];
											if (media != "") {

												// convert it into 'greyd/anim'
												if (media.src && media.src.indexOf('.json') > -1) {
													mode = "anim";
												}
												atts["url"] = media.src;
												atts["id"] = media.id;
											}
										}
									}

									const hasDynamicFields = _.has(atts, "dynamic_icon") && !_.isEmpty(atts.dynamic_icon);
									const usesDynamicTag   = _.has(atts, "icon") && String(atts.icon).indexOf('_') === 0;

									// convert it into 'greyd/image'
									if ( hasDynamicFields || usesDynamicTag ) {
										if (hasDynamicFields) {
											atts.dynamic_fields = [
												{ key: 'image', 'title': atts.dynamic_icon.split('|')[2], 'enable': true }
											];
										}
										atts.image = {
											type: usesDynamicTag ? 'dynamic' : 'file',
											tag: usesDynamicTag ? String(atts.icon).replace(/_/g, '') : '',
											url: _.has(atts, 'url') ? atts.url : '',
											id: usesDynamicTag ? -1 : _.get(atts, 'icon'),
										}
										if ( _.has(atts, 'w') ) {
											atts.greydStyles = {
												width: atts.w
											}
										}
										delete atts.dynamic_icon;
										delete atts.w;
										return atts;
									}

									// convert link
									if (_.has(atts, "link")) {
										var link = convertLink(atts["link"]);
										delete atts["link"];
										if (mode == "anim") {
											atts["link"] = array();
											if (_.has(link, "title")) atts["link"]["title"] = link["title"];
											if (_.has(link, "url")) atts["link"]["url"] = link["url"];
											if (_.has(link, "target") && link["target"] == "_blank") atts["link"]["opensInNewTab"] = true;
										}
										else {
											// if (_.has(link, "title")) atts["text"] = link["title"];
											if (_.has(link, "url")) atts["href"] = link["url"];
											if (_.has(link, "target") && link["target"] != "") {
												atts["linkTarget"] = link["target"].trim();
												atts["rel"] = "noreferrer noopener";
											}
										}
									}
								  	return atts;
						  	  } } ],
		"core/social-links":[ { key: "pull", map: "align", convert: function(value) { return value.replace('_', '') } },
							  { inner: function(atts) { 
								  	var channels = [];
									Object.keys(atts).forEach(function(key, index) { 
										if (atts[key] == "true") 
											channels.push(wp.blocks.createBlock( "core/social-link", { service: key, url: atts[key+'_url'] } )); 
									});
								  	return channels;
							  } } ],
		"core/search":		[ { key: "holder" , map: "placeholder" },
							  { key: "btn" , map: "buttonText" },
							  { key: "showLabel" , default: false } ],
		// to greyd blocks
		"greyd/anchor":		[  ],
		"greyd/box":		[ { key: "vc_content_box_class", map: "className"},
							  { compute: function(atts) {
									// trigger
									if (_.has(atts, "vc_content_box_link")) {
										var link = convertLink(atts["vc_content_box_link"]);
										delete atts["vc_content_box_link"];
										if (_.has(link, "url") && link["url"] != "") {
											var trigger = { type: 'link', params: { url: link["url"] } }
											if (_.has(link, "target") && link["target"] == "_blank") trigger["params"]["opensInNewTab"] = true;
											atts["trigger"] = trigger;
										}
									}

									// background
									var background = greyd.tools.layout.getBackgroundDefaults();
									atts["background"] = { 
										...background.background
									};
									if (_.has(atts, "vc_content_box_type") && atts["vc_content_box_type"] != "") {
										atts = computeBackground("vc_content_box", atts);
									}
									// cleaning
									atts["background"] = greyd.tools.layout.cleanBackgroundvalues(atts["background"]);
									if (_.isEmpty(atts["background"])) delete atts["background"];

									// greydStyles
									var makeStyles = function(breakpoint, top, right) {
										var res = {};
										if (top[breakpoint] != '' || right[breakpoint] != '') {
											if (top[breakpoint] != '') {
												var val = top[breakpoint];
												if (val.indexOf('px') == -1) val += 'px';
												res["top"] = val;
												res["bottom"] = val;
											}
											if (right[breakpoint] != '') {
												var val = right[breakpoint];
												if (val.indexOf('px') == -1) val += 'px';
												res["right"] = val;
												res["left"] = val;
											}
										}
										return res;
									}
									var greydStyles = {};
									if (_.has(atts, "vc_content_boxpadding_right_sm")) {
										atts["vc_content_box_padding_right_sm"] = atts["vc_content_boxpadding_right_sm"];
										delete atts["vc_content_boxpadding_right_sm"];
									}
									var paddingTop = computeResponsive("vc_content_box_padding_top", atts, "");
									var paddingRight = computeResponsive("vc_content_box_padding_right", atts, "");
									greydStyles["padding"] = makeStyles('max', paddingTop, paddingRight);
									if (paddingTop.max != '' || paddingRight.max != '') {
										greydStyles["padding"] = makeStyles('max', paddingTop, paddingRight);
									}
									var marginTop = computeResponsive("vc_content_box_margin_top", atts, "");
									var marginRight = computeResponsive("vc_content_box_margin_right", atts, "");
									if (marginTop.max != '' || marginRight.max != '') {
										greydStyles["margin"] = makeStyles('max', marginTop, marginRight);
									}
									var minHeight = computeResponsive("vc_content_box_height", atts, "");
									if (minHeight.max != '') {
										greydStyles["minHeight"] = minHeight.max;
									}
									// responsive
									var greydStyles_responsive = { "lg": {}, "md": {}, "sm": {} };
									[ "lg", "md", "sm" ].forEach(function(value) {
										var padding = makeStyles(value, paddingTop, paddingRight);
										if (!_.isEmpty(padding) && !_.isEqual(padding, greydStyles["padding"])) {
											greydStyles_responsive[value]["padding"] = padding;
										}
										var margin = makeStyles(value, marginTop, marginRight);
										if (!_.isEmpty(margin) && !_.isEqual(margin, greydStyles["margin"])) {
											greydStyles_responsive[value]["margin"] = margin;
										}
										if (minHeight[value] != '' && !_.isEqual(minHeight[value], greydStyles["minHeight"])) {
											greydStyles_responsive[value]["minHeight"] = minHeight[value];
										}
									});
									if (_.isEmpty(greydStyles_responsive["lg"])) delete greydStyles_responsive["lg"];
									if (_.isEmpty(greydStyles_responsive["md"])) delete greydStyles_responsive["md"];
									if (_.isEmpty(greydStyles_responsive["sm"])) delete greydStyles_responsive["sm"];
									if (!_.isEmpty(greydStyles_responsive)) {
										greydStyles["responsive"] = greydStyles_responsive;
									}

									// textcolor
									if (_.has(atts, "vc_content_box_text_colorselect")) {
										var color = computeColor("vc_content_box_text_colorselect", atts);
										greydStyles['color'] = color;
									}
									// backgroundcolor
									if (_.has(atts, "backgroundColor")) {
										greydStyles['background'] = atts["backgroundColor"];
										delete atts["backgroundColor"];
									}
									else if (_.has(atts, ['style','color','background'])) {
										greydStyles['background'] = atts["style"]["color"]["background"];
										delete atts["style"]["color"]["background"];
									}
									// gradient
									if (_.has(atts, "gradient")) {
										greydStyles['background'] = atts["gradient"];
										delete atts["gradient"]
									}
									else if (_.has(atts, ['style','color','gradient'])) {
										greydStyles['background'] = atts["style"]["color"]["gradient"];
										delete atts["style"]["color"]["gradient"];
									}
									if (_.has(atts, "style")) {
										if (_.has(atts, ['style','color']) && _.isEmpty(atts["style"]["color"])) 
											delete atts["style"]["color"];
										if (_.isEmpty(atts["style"])) 
											delete atts["style"];
									}

									// border-radius
									if (_.has(atts, "vc_content_box_border_radius")) {
										greydStyles["borderRadius"] = parseInt(atts["vc_content_box_border_radius"]);
										delete atts["vc_content_box_border_radius"];
									}
									// border
									if (_.has(atts, "vc_content_box_border") && atts["vc_content_box_border"] == "true") {
										delete atts["vc_content_box_border"];
										var border = computeBorder('vc_content_box_border', '', atts);
										var color = border.color;
										if (color.indexOf("color-") === 0 ) {
											color = "var(--"+color.replace("-", "")+")";
										}
										var style = border.style;
										greydStyles["border"] = {
											top: border.width.top+' '+style+' '+color,
											right: border.width.right+' '+style+' '+color,
											bottom: border.width.bottom+' '+style+' '+color,
											left: border.width.left+' '+style+' '+color,
										};
									}
									// shadow
									if (_.has(atts, "vc_content_box_sdw") && atts["vc_content_box_sdw"] == "true") {
										delete atts["vc_content_box_sdw"];
										if (_.has(atts, "vc_content_box_shadow")) {
											greydStyles['boxShadow'] = atts["vc_content_box_shadow"].replace('color_', 'color-');
											delete atts["vc_content_box_shadow"];
										}
									}
									[ "_x", "_y", "_blur", "_spread", "_colorselect" ].forEach(function(value) {
										if (_.has(atts, "vc_content_box_sdw"+value)) delete atts["vc_content_box_sdw"+value];
										if (_.has(atts, "vc_content_box_sdw"+value+"_hover")) delete atts["vc_content_box_sdw"+value+"_hover"];
									});
									
									// hover
									var greydStyles_hover = {};
									if (_.has(atts, "vc_content_box_text_colorselect_hover")) {
										var color_hover = computeColor("vc_content_box_text_colorselect_hover", atts);
										if (color_hover != greydStyles['color']) greydStyles_hover['color'] = color_hover;
									}
									// border-radius
									// if (_.has(atts, "vc_content_box_border_radius_hover")) {
									// 	greydStyles_hover["borderRadius"] = parseInt(atts["vc_content_box_border_radius_hover"]);
									// 	delete atts["vc_content_box_border_radius_hover"];
									// }
									// background
									if (_.has(atts, "vc_content_box_colorselect_hover")) {
										var color_hover = computeColor("vc_content_box_colorselect_hover", atts);
										if (color_hover != greydStyles['background']) greydStyles_hover["background"] = color_hover;
									}
									// border
									if (_.has(atts, "vc_content_box_border_hover")) {
										var border = computeBorder('vc_content_box_border', '_hover', atts);
										var color = border.color;
										if (color.indexOf("color-") === 0 ) {
											color = "var(--"+color.replace("-", "")+")";
										}
										var style = border.style;
										greydStyles_hover["border"] = {
											top: border.width.top+' '+style+' '+color,
											right: border.width.right+' '+style+' '+color,
											bottom: border.width.bottom+' '+style+' '+color,
											left: border.width.left+' '+style+' '+color,
										};
									}
									// shadow
									if (_.has(atts, "vc_content_box_shadow_hover")) {
										var shadow_hover = atts["vc_content_box_shadow_hover"].replace('color_', 'color-');
										delete atts["vc_content_box_shadow_hover"];
										if (shadow_hover != greydStyles['boxShadow']) greydStyles_hover['boxShadow'] = shadow_hover;
									}
									if (!_.isEmpty(greydStyles_hover)) {
										greydStyles["hover"] = greydStyles_hover;
									}
									// justify
									if (_.has(atts, "vc_content_box_flex_justify_content")) {
										greydStyles.justifyContent = atts["vc_content_box_flex_justify_content"];
										delete atts["vc_content_box_flex_justify_content"];
									}

									// greydClass
									if (!_.isEmpty(greydStyles)) {
										atts["greydStyles"] = greydStyles;
										atts['greydClass'] = greyd.tools.generateGreydClass();
									}

									// transition
									var transition = {};
									if (_.has(atts, "vc_content_box_transition_duration")) {
										transition["duration"] = atts["vc_content_box_transition_duration"];
										delete atts["vc_content_box_transition_duration"];
									}
									if (_.has(atts, "vc_content_box_transition_effect")) {
										transition["effect"] = atts["vc_content_box_transition_effect"];
										delete atts["vc_content_box_transition_effect"];
									}
									if (!_.isEmpty(transition)) {
										atts["transition"] = transition;
									}

									// console.log(atts);
									return atts;
							  } } ],
		"greyd/buttons":	[ { key: "pull", map: "align", convert: function(value) { return value.replace('_', '') } },
							  { key: "align", map: "align" } ],
		"greyd/button":		[ { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "content", fill: function(inner) { return inner.trim().split('\n').join('<br>') } },
							  { key: "size", map: "size", convert: function(value) { return value.replace('_', '') } },
							{ compute: function(atts) {

								/**
								 * Button trigger (link, popup, ...)
								 */
								const style = _.has(atts, "style") ? atts["style"] : "";
								// link button
								if (_.has(atts, "linkk")) {
									var { options } = greyd.dynamic.tags.getCurrentOptions('tags', false);
									var matched_content = greyd.dynamic.tags.match(options, [
										{ dtype: 'vc_link', dtitle: 'link', dvalue: encodeURIComponent(atts['linkk']) },
										{ dtitle: 'link Trigger', dvalue: "" },
										{ dtitle: 'link Text', dvalue: "" },
									]);
									// console.log(matched_content);
									if (matched_content) {
										atts['trigger'] = JSON.parse(decodeURIComponent(matched_content[1].dvalue));
										atts['content'] = decodeURIComponent(matched_content[2].dvalue);
									}
									else {
										var link = convertLink(atts["linkk"]);
										if (_.has(link, "title")) atts["content"] = link["title"];
										atts['trigger'] = {
											type: 'link',
											params: {
												url: _.has(link, "url") ? link["url"] : "",
												title: _.has(link, "title") ? link["title"] : "",
												opensInNewTab: _.has(link, "target") ? 1 : 0
											}
										}
									}
									delete atts["linkk"];
								}
								// download button
								else if (style == "download") {
									atts['trigger'] = {
										type: 'file',
										params: _.has(atts, "file") ? atts["file"] : "",
									}
									delete atts["file"];
								}
								// email button
								else if (style == "email") {
									atts['trigger'] = {
										type: 'email',
										params: {
											address: _.has(atts, "email") ? atts["email"] : "",
											subject: _.has(atts, "subject") ? atts["subject"] : "",
										}
									}
									delete atts["email"];
									delete atts["subject"];
								}
								// back button
								else if (style == "back") {
									atts['trigger'] = {
										type: 'back'
									}
								}
								// scroll-to button
								else if (style == "down") {
									atts['trigger'] = {
										type: 'scroll',
										params: _.has(atts, "anchor") ? atts["anchor"] : "",
									}
									delete atts["anchor"];
								}
								// trigger button
								else if (style == "greyd_trigger") {
									atts['trigger'] = {
										type: 'event',
										params: {
											name: _.has(atts, "trigger_name") ? atts["trigger_name"] : "",
											custom: _.has(atts, "custom_trigger_name") ? atts["custom_trigger_name"] : "",
											hover: _.has(atts, "trigger_hover") && atts["trigger_hover"].indexOf('true') > -1 ? 1 : 0,
											global: _.has(atts, "trigger_hover") && atts["trigger_hover"].indexOf('global') > -1 ? 1 : 0,
										}
									}
									delete atts["trigger_name"];
									delete atts["custom_trigger_name"];
									delete atts["trigger_hover"];
								}
								// popup button
								else if (style == "popup_open") {
									atts['trigger'] = {
										type: 'popup',
										params: _.has(atts, "popup") ? atts["popup"] : ""
									}
									delete atts["popup"];
								}
								// popup_close button
								else if (style == "popup_close") {
									atts['trigger'] = {
										type: 'popup_close',
									}
								}
								delete atts["style"];

								/**
								 * Icon
								 */
								if (_.has(atts, "icon")) {
									atts["icon"] = {
										'content': atts['icon'],
										'position': _.has(atts, "icon_align") && atts["icon_align"] == 'left' ? 'before' : 'after',
										'size': _.has(atts, "icon_size") ? atts["icon_size"] : '100%',
										'margin': _.has(atts, "icon_margin") ? atts["icon_margin"] : '10px',
									}
									delete atts["icon_align"];
									delete atts["icon_size"];
									delete atts["icon_margin"];
								}

								/**
								 * Misc
								 */
								if (_.has(atts, "versal")) {
									if (atts['versal'] == "true") atts['content'] = '<span class="is-upper">'+atts['content']+'</span>';
									delete atts['versal'];
								}
								if (_.has(atts, "min_width")) {
									atts["greydStyles"] = {
										'width': atts['min_width']
									}
									delete atts['min_width'];
								}

								/**
								 * Button Design
								 */
								atts['mode'] = _.has(atts, "mode") ? atts['mode'] : '';
								atts['className'] = 'is-style-link-prim';
								if (atts['mode'] == "_button") {
									atts['className'] = "is-style-prim";
								} else if (atts['mode'] == "_button _sec") {
									atts['className'] = "is-style-sec";
								} else if (atts['mode'] == "_button _trd") {
									atts['className'] = "is-style-trd";
								} else if (atts['mode'] == "_sec") {
									atts['className'] = "is-style-link-sec";
								} else if (atts['mode'] == "custom2") {
									atts['className'] = "is-style-prim";
								}

								/**
								 * Custom Button
								 */
								if ( atts['mode'] == 'custom2') {

									atts["custom"] = 1;
									atts["customStyles"] = computeCustomButtonStyles(atts, 'btn_');

									delete atts['btn_color'];
									delete atts['btn_background'];
									delete atts['btn_font_size'];
									delete atts['btn_padding_top'];
									delete atts['btn_padding_right'];
									delete atts['btn_border_radius'];
									delete atts['btn_border'];
									delete atts['btn_border_width'];
									delete atts['btn_border_top'];
									delete atts['btn_border_right'];
									delete atts['btn_border_bottom'];
									delete atts['btn_border_left'];
									delete atts['btn_shadow'];
									delete atts['btn_color_hover'];
									delete atts['btn_background_hover'];
									delete atts['btn_border_hover'];
									delete atts['btn_shadow_hover'];
								}
								delete atts['mode'];

								// if (_.has(atts, "size")) {
								// 	atts["size"] = atts["size"].replace('_', '');
								// 	var size = atts["size"];
								// 	if (size != "") {
								// 		size = "is-"+size;
								// 		if (_.has(atts, "className")) size += " "+atts["className"];
								// 		atts["className"] = size;
								// 	}
								// }

								/**
								 * Return
								 */
								return atts;

							  } } ],
		"greyd/list":		[ { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { key: "vc_list_type", map: "type" },
							  { key: "list_text_align", map: "align" },
							  { key: "list_gap", map: "gap" },
							  { key: "list_fullwidth", map: "fullwidth", convert: function(value) { return value == "true" } },
							  { compute: function(atts) {
								  
									atts["web"] = {}; // JSON.parse(JSON.stringify(wp.blocks.getBlockType("greyd/list").attributes.web.default));
									if (_.has(atts, "type") && atts["type"] == "web") {
										if (_.has(atts, "vc_list_web") && atts["vc_list_web"] != "") {
											atts['web']['style'] = atts["vc_list_web"];
											delete atts["vc_list_web"];
										}
										if (_.has(atts, "list_icon_position_web") && atts["list_icon_position_web"] != "") {
											atts['web']['position'] = atts["list_icon_position_web"];
											delete atts["list_icon_position_web"];
										}
										if (_.has(atts, "list_icon_align_web") && atts["list_icon_align_web"] != "") {
											atts['web']['align_y'] = atts["list_icon_align_web"];
											delete atts["list_icon_align_web"];
										}
									}
									atts["icon"] = {}; // JSON.parse(JSON.stringify(wp.blocks.getBlockType("greyd/list").attributes.icon.default));
									if (_.has(atts, "type") && (atts["type"] == "icon" || atts["type"] == "img")) {
										if (_.has(atts, "vc_list_icon_size") && atts["vc_list_icon_size"] != "") {
											atts['icon']['size'] = atts["vc_list_icon_size"];
											delete atts["vc_list_icon_size"];
										}
										if (_.has(atts, "vc_list_icon_margin") && atts["vc_list_icon_margin"] != "") {
											atts['icon']['margin'] = atts["vc_list_icon_margin"];
											delete atts["vc_list_icon_margin"];
										}
										if (_.has(atts, "list_icon_position") && atts["list_icon_position"] != "") {
											atts['icon']['position'] = atts["list_icon_position"];
											delete atts["list_icon_position"];
										}
										if (_.has(atts, "list_icon_align_y") && atts["list_icon_align_y"] != "") {
											atts['icon']['align_y'] = atts["list_icon_align_y"];
											delete atts["list_icon_align_y"];
										}
										if (_.has(atts, "list_icon_align_x") && atts["list_icon_align_x"] != "") {
											atts['icon']['align_x'] = atts["list_icon_align_x"];
											delete atts["list_icon_align_x"];
										}
										if (atts["type"] == "icon") {
											if (_.has(atts, "vc_list_icon") && atts["vc_list_icon"] != "") {
												atts['icon']['icon'] = atts["vc_list_icon"];
												delete atts["vc_list_icon"];
											}
											if (_.has(atts, "vc_list_colorselect") && atts["vc_list_colorselect"] != "") {
												atts['icon']['color'] = computeColor("vc_list_colorselect", atts);
											}
										}
										if (atts["type"] == "img") {
											if (_.has(atts, "vc_list_image")) {
												var icon = atts["vc_list_image"];
												delete atts["vc_list_image"];
												if (icon != "") {
													var media = "";
													if (_.has(greyd.data.media_urls, parseInt(icon))) media = greyd.data.media_urls[parseInt(icon)];
													if (media != "") {
														atts['icon']["url"] = media.src;
														atts['icon']["id"] = media.id;
													}
												}
											}
										}
									}
									
									return atts;
							  } } ],
		"greyd/list-item":	[ { key: "content", fill: function(inner) { 
								    var { options } = greyd.dynamic.tags.getCurrentOptions('tags', false);
									var matched_content = greyd.dynamic.tags.match(options, [{
										dvalue: encodeURIComponent(inner),
										dtype: 'textarea'
									}]);
									var content = (matched_content) ? decodeURIComponent(matched_content[0].dvalue) : inner;
									// console.log(content);
								  	return content.trim().split('\n').join('<br>');
							  	} } ],
		"greyd/conditional-content": [ 
							{ compute: function(atts) {
								const newAtts = {
									operator: _.has(atts, 'condition_type') ? 'AND' : 'OR',
									conditions: []
								};
								const options = JSON.parse( decodeURIComponent( atts['options'] ) );
								if ( Array.isArray(options) ) {
									options.forEach( option => {
										const type = _.has(option, 'type') ? option.type : null;
										if ( type === 'urlparam' || type === 'cookie' ) {
											newAtts.conditions.push( {
												type: type,
												operator: _.has(option, 'condition_urlparam') ? option.condition_urlparam : 'is',
												value: _.has(option, 'urlparam_value') ? option.urlparam_value : '',
												detail: _.has(option, 'urlparam') ? option.urlparam : '',
												custom: _.has(option, 'custom_urlparam') ? option.custom_urlparam : '',
											} );
										}
										else if ( type === 'userrole' ) {
											newAtts.conditions.push( {
												type: type,
												operator: _.has(option, 'condition_userrole') ? option.condition_userrole : 'is',
												value: _.has(option, 'userrole_value') ? option.userrole_value : '',
												detail: '',
												custom: '',
											} );
										}
										else if ( type === 'time' ) {
											newAtts.conditions.push( {
												type: type,
												operator: _.has(option, 'condition_time') ? option.condition_time : 'is',
												value: _.has(option, 'time_value') ? option.time_value : '',
												detail: '',
												custom: _.has(option, 'custom_time') ? option.custom_time : '',
											} );
										}
										else if ( type === 'search' ) {
											newAtts.conditions.push( {
												type: type,
												operator: _.has(option, 'condition_search') ? option.condition_search : 'is',
												value: '',
												detail: '',
												custom: '',
											} );
										}
									});
								}
								return newAtts;
							} } ],

		"greyd/accord":		[ { transform: function(atts) { 
									// wrap in group
									return "core/group";
								} },
							  { key: "el_class", map: "className" }, 
							  { key: "el_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } },
							  { key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
							  { compute: function(atts) {
									var colors = {
										'': '',
										'blue': '#5472d2',
										'turquoise': '#00c1cf',
										'pink': '#fe6c61',
										'violet': '#8d6dc4',
										'peacoc': '#4cadc9',
										'chino': '#cec2ab',
										'mulled-wine': '#50485b',
										'vista-blue': '#75d69c',
										'orange': '#f7be68',
										'sky': '#5aa1e3',
										'green': '#6dab3c',
										'juicy-pink': '#f4524d',
										'sandy-brown': '#f79468',
										'purple': '#b97ebb',
										'black': '#2a2a2a',
										'grey': '#ebebeb',
										'white': '#ffffff',
									};  
									if (_.has(atts, "color")) {
										var color = colors[atts["color"]];
										delete atts["color"];
										if (!_.has(atts, "style")) atts["style"] = { color: { background: color } };
										else if (!_.has(atts["style"], "color")) atts["style"]["color"] = { background: color };
										else atts["style"]["color"]["background"] = color;
									}
									return atts;
							  } } ],
		"greyd/accord-item":[ { transform: function(atts) { 
									// wrap in group
									return "core/group";
								} },
							  { key: "el_class", map: "className" }, 
							  { key: "tab_id", map: "anchor", convert: function(value) { return value.split(' ').join('_') } } ],
		"greyd/counter":	[ { transform: function(atts) { 
									// make paragraph
									return "core/paragraph";
								} },
								{ key: "content", fill: function(inner) { return inner.trim() } },
								{ compute: function(atts) {
									// console.log(atts);
									var content = _.has(atts, "content") ? atts["content"].trim() : '';
									if (content != "") {
										var num = "0";
										var type = _.has(atts, "type") ? atts["type"] : "manual";
										if (type == "manual") {
											var num = _.has(atts, "manual") ? atts["manual"] : '42';
										}
										else if (type == "dynamic") {
											if (_.has(atts, "posttype")) {
												var pt = atts["posttype"];
												var params = '&quot;format&quot;:&quot;posttype&quot;, &quot;posttype&quot;:&quot;'+pt+'&quot;';
												if (_.has(atts, pt+'_taxonomy')) {
													var tax = atts[pt+'_taxonomy'];
													params += ', &quot;taxonomy&quot;:&quot;'+tax+'&quot;';
													if (_.has(atts, tax+'_term')) {
														var term = atts[tax+'_term'];
														params += ', &quot;term&quot;:&quot;'+term+'&quot;';
													}
												}
												num = '<span data-tag="count" data-params="{'+params+'}" class="is-tag">Anzahl</span>';
											}
										}
										content = content.split('_count_').join(num);
										
									}
									var newAtts = { 'content': content };
									// console.log(newAtts);
									return newAtts;
							    } } ],
		"greyd/copyright":	[ { transform: function(atts) { 
									// wrap in columns
									return "core/columns";
								} },
								{ inner: function(atts) { 
									// console.log(atts);
									var txt = _.has(atts, 'text') ? atts['text'] : '_c_ _year_ _name_';
									var img = _.has(atts, 'img') ? parseInt(atts['img']) : -1;
									var layout = _.has(atts, 'layout') ? atts['layout'] : 'center';
									if (layout == 'center') img = -1;
									if (layout == 'center2') txt = '';
									// get text with dynamic tags
									if (txt != "") {
										var islink = _.has(atts, 'no_link') ? 'false' : 'true';
										var c = '<span data-tag="symbol" data-params="{&quot;symbol&quot;:&quot;copyright&quot;}" class="is-tag">Symbol</span>';
										var year = '<span data-tag="now" data-params="{&quot;format&quot;:&quot;Y&quot;}" class="is-tag">Heute</span>';
										var name = '<span data-tag="site-title" data-params="{&quot;isLink&quot;:'+islink+'}" class="is-tag">Website Titel</span>';
										txt = txt.split('_c_').join(c).split('_year_').join(year).split('_name_').join(name);
									}
									// get icon image
									if (img != -1) {
										var img_src = "";
										if (_.has(greyd.data.media_urls, img)) img_src = greyd.data.media_urls[img].src;
										if (img_src == "") img = -1;
										else img = '<img class="wp-image-'+img+'" style="width: 22px;" src="'+img_src+'" alt="">';
									}
									// make inner blocks
									var items = [];
									if (img == -1 && layout != 'center2') {
										// just text
										items.push(wp.blocks.createBlock( "core/column", {
											className: "col-12 ",
											responsive: { width: { xs: "col-12", sm: "" } }
										}, [ wp.blocks.createBlock( "core/paragraph", { align: layout, content: txt } ) ] ));
									}
									else if (txt == '' && layout != 'center') {
										// just icon
										var img_layout = 'right';
										if (layout == 'center2') img_layout = 'center';
										else if (layout == 'right') img_layout = 'left';
										items.push(wp.blocks.createBlock( "core/column", {
											className: "col-12 ",
											responsive: { width: { xs: "col-12", sm: "" } }
										}, [ wp.blocks.createBlock( "core/paragraph", { align: img_layout, content: img } ) ] ));

									}
									else {
										// text and icon
										var txt_layout = layout;
										var img_layout = (layout == 'right') ? 'left' : 'right';
										var txt_column = wp.blocks.createBlock( "core/column", {
											className: "col-6 ",
											responsive: { width: { xs: "col-6", sm: "" } }
										}, [ wp.blocks.createBlock( "core/paragraph", { align: txt_layout, content: txt } ) ] );
										var img_column = wp.blocks.createBlock( "core/column", {
											className: "col-6 ",
											responsive: { width: { xs: "col-6", sm: "" } }
										}, [ wp.blocks.createBlock( "core/paragraph", { align: img_layout, content: img } ) ] );

										if (layout == 'right') items.push(img_column, txt_column);
										if (layout == 'left') items.push(txt_column, img_column);
									}
									return items;
								} } ],

		"greyd/popup-close":[ { compute: function(atts) {

									const styles = {
										'width':  _.has(atts, "size") ? atts["size"]+'px' : '40px',
										'fontSize':  _.has(atts, "width") ? atts["width"]+'px' : '4px',
										'hover': { }
									};
									if ( _.has(atts, "color") ) styles["color"] = transformColorIntoVar(atts["color"]);
									if ( _.has(atts, "color_bg") ) styles["backgroundColor"] = transformColorIntoVar(atts["color_bg"]);
									if ( _.has(atts, "color_hover") ) styles["hover"]["color"] = transformColorIntoVar(atts["color_hover"]);
									if ( _.has(atts, "color_bg_hover") ) styles["hover"]["backgroundColor"] = transformColorIntoVar(atts["color_bg_hover"]);
									if ( _.has(atts, "border_radius") ) styles["borderRadius"] = atts["border_radius"]+'px';

									const newAtts = { 'greydStyles': styles };
									if ( _.has(atts, "align") ) newAtts["align"] = atts["align"].replace('_', '');

									return newAtts;
							  } } ],
		"greyd/menu":		[  { compute: function(atts) {
								const newAtts = {
									menu: _.has(atts, 'menu') ? atts.menu : '',
									depth: 0,
									listStyles: {
										flexDirection: _.has(atts, 'direction') ? ( atts.direction.indexOf('row') > -1 ? 'row' : 'column' ) : '',
										alignItems: _.has(atts, 'justify') ? atts.justify.replace('menu_row_', '') : (_.has(atts, 'align') ? atts.align.replace('menu_column_', '') : ''),
										responsive: {
											lg: {
												flexDirection: '',
												alignItems: '',
											},
											md: {
												flexDirection: '',
												alignItems: '',
											},
											sm: {
												flexDirection: '',
												alignItems: '',
											},
										}
									},
									itemStyles: {
										fontSize: _.has(atts, 'text_size') ? { normal: '', smaller: '0.7em', small: '0.85em', big: '1.1em', bigger: '1.2em' }[atts.text_size] : ''
									},
									subStyles: {},
								};
								// responsive values
								for (const [breakpoint, values] of Object.entries(newAtts.listStyles.responsive)) {
									// direction
									if ( _.has( atts, 'direction_'+breakpoint ) ) {
										values.flexDirection = atts['direction_'+breakpoint].replace('menu_'+breakpoint+'_', '');
									}
									// alignment
									if ( _.has( atts, 'justify_'+breakpoint ) ) {
										values.alignItems = atts['justify_'+breakpoint].replace('menu_'+breakpoint+'_row_', '');
									}
									else if ( _.has( atts, 'align_'+breakpoint ) ) {
										values.alignItems = atts['align_'+breakpoint].replace('menu_'+breakpoint+'_column_', '');
									}
									newAtts.listStyles.responsive[breakpoint] = values;
								}
								return newAtts;
							} } ],
		// to greyd template blocks
		"greyd/dynamic":		[ { compute: function(atts) {
										if (_.has(atts, "dynamic_content")) {
											var values = atts['dynamic_content'].split('::');
											values.shift();
											for (var i=0; i<values.length; i++) {
												var [ key, type, title, val ] = values[i].split('|');
												values[i] = { dkey: key, dtype: type, dtitle: decodeURIComponent(title), dvalue: val };
											}
											atts['dynamic_content'] = values;
										}
										// console.log(atts);
										return atts;
								  } } ],
		"core/archives":		[ { compute: function(atts) {

									// default
									const newAtts = {
										displayAsDropdown: 0,
										showPostCounts: 0,
										align: 'left',
										filter: {
											post_type: 'post',
											type: 'yearly', // this was the first option in vc
											order: '',
											hierarchical: 0,
											date_format: ''
										},
										styles: {
											style: '',
											size: '',
											custom: 0,
											icon: {
												content: '',
												position: 'after',
												size: '100%',
												margin: '10px'
											}
										},
										customStyles: {}
									}

									// general
									if ( _.has(atts, "option_count") ) newAtts.showPostCounts = 1;
									if ( _.has(atts, "mode") && atts.mode == 'dropdown' ) newAtts.displayAsDropdown = 1;
									if ( _.has(atts, "content_pull") ) newAtts.align = atts["content_pull"].replace('_', '') ;

									// filter
									if ( _.has(atts, "pt") ) newAtts.filter.post_type = atts["pt"];
									if ( _.has(atts, "pt_by_cat") ) newAtts.filter.post_type = atts["pt_by_cat"];
									if ( _.has(atts, "pt_by_tag") ) newAtts.filter.post_type = atts["pt_by_tag"];
									if ( _.has(atts, "archive_type") ) newAtts.filter.type = atts["archive_type"];
									if ( _.has(atts, "option_hierarchical") ) newAtts.filter.hierarchical = 1;
									if ( _.has(atts, "content_sort") ) newAtts.filter.order = atts["content_sort"];
									if ( _.has(atts, "content_sort_date") ) newAtts.filter.order = atts["content_sort_date"];
									if ( newAtts.filter.type == 'yearly' ) {
										if ( _.has(atts, "date_format_yearly") ) newAtts.filter.date_format = atts["date_format_yearly"];
									}
									else if ( newAtts.filter.type == 'monthly' ) {
										if ( _.has(atts, "date_format_monthly") ) newAtts.filter.date_format = atts["date_format_monthly"];
									}
									else if ( newAtts.filter.type == 'daily' ) {
										if ( _.has(atts, "date_format_daily") ) newAtts.filter.date_format = atts["date_format_daily"];
									}

									// styles
									if ( _.has(atts, "link_style") ) {
										if ( _.has(atts, "link_style") ) newAtts.styles.style = atts['link_style'].replace(/(_|(link))/g, '').trim();
										if ( atts['link_style'] == 'custom' ) {
											newAtts.styles.custom = 1;
											newAtts.customStyles = computeCustomButtonStyles(atts, 'link_');
										}
									} else if ( _.has(atts, "dropdown_style") ) {
										if ( _.has(atts, "dropdown_style") ) newAtts.styles.style = atts['dropdown_style'].replace(/(_|(dropdown))/g, '').trim();
										if ( atts['dropdown_style'] == 'custom' ) {
											newAtts.styles.custom = 1;
											newAtts.customStyles = computeCustomButtonStyles(atts, 'dropdown_');
										}
									}
									if (_.has(atts, "link_icon")) {
										newAtts.styles.icon = {
											'content': atts['link_icon'],
											'position': _.has(atts, "link_icon_align") && atts["link_icon_align"] == 'left' ? 'before' : 'after',
											'size': _.has(atts, "link_icon_size") ? atts["link_icon_size"] : '100%',
											'margin': _.has(atts, "link_icon_margin") ? atts["link_icon_margin"] : '10px',
										}
									}
									
									return newAtts;
							  } } ],
		"core/query":			[ { transform: function(atts) { 
										if (_.has(atts, 'mode') && atts['mode'] === 'fixed') {
											// from vc_posts_overview with one or handpicked posts
											// compute atts['posts'] as inner blocks
											// each as greyd/dynamic with selected post
											return "core/group";
										}
										return "core/query";
								  } },
								  { inner: function(atts) { 
										// functions
										var make_size = function(text_size) {
											var size = 15;
											if (text_size == 'smaller') return Math.ceil(size*0.7)+'px';
											if (text_size == 'small') return Math.ceil(size*0.85)+'px';
											if (text_size == 'big') return Math.ceil(size*1.1)+'px';
											if (text_size == 'bigger') return Math.ceil(size*1.2)+'px';
											if (text_size == 'biggest') return Math.ceil(size*2.2)+'px';
											return size+'px';
										}
										var make_size_color = function(post_atts) {
											var element_atts = {};
											// text_size
											var atts_style = {};
											if (_.has(post_atts, 'text_size') && post_atts["text_size"] != 'normal' && post_atts["text_size"] != 'bold')
												atts_style = { typography: { fontSize: make_size(post_atts["text_size"]) } };
											// text_color
											if (_.has(post_atts, "text_color")) {
												var color = computeColor("text_color", post_atts);
												if (color.indexOf("color-") === 0 ) element_atts.textColor = color;
												else atts_style = { color: color };
											}
											if (!_.isEmpty(atts_style)) element_atts.style = atts_style;
											return element_atts;
										}
										var check_blocktype = function(name) {
											var types = wp.blocks.getBlockTypes();
											for (var i=0; i<types.length; i++) {
												if (types[i]['name'] == name) return true;
											}
											return false;
										}
										var make_title_block = function(atts, param) {
											var title_atts = { content: atts[param] };
											if (_.has(atts, param+"_pull")) title_atts.textAlign = atts[param+"_pull"].replace('_', '');
											if (_.has(atts, param+"_size") && atts[param+"_size"].indexOf('h') == 0) 
												title_atts.level = parseInt(atts[param+"_size"].replace('h', ''));
											else title_atts.fontSize = "txt";
											var title_style = {};
											if (_.has(atts, param+"_color")) {
												var color = computeColor(param+"_color", atts);
												if (color.indexOf("color-") === 0 ) title_atts.textColor = color;
												else title_style.color = color;
											}
											if (_.has(atts, param+"_weight")) title_style.typography = { fontWeight: atts[param+"_weight"] };
											if (!_.isEmpty(title_style)) {
												title_atts.style = title_style;
											}
											return wp.blocks.createBlock( "core/heading", title_atts )
										}
										var make_template_atts = function() {
											var template = {
												'template': '',
												'dynamic_content': '',
											};
											if (_.has(atts, "post_template")) {
												template['template'] = atts['post_template'];
											}
											if (_.has(atts, "dynamic_post_content")) {
												var values = atts['dynamic_post_content'].split('::');
												values.shift();
												for (var i=0; i<values.length; i++) {
													var [ key, type, title, val ] = values[i].split('|');
													values[i] = { dkey: key, dtype: type, dtitle: decodeURIComponent(title), dvalue: val };
												}
												template['dynamic_content'] = values;
											}
											return template;
										}

										var items = [];
										
										// setup mode
										var post_elements = [];
										var post_setup_mode = "manual";
										if (_.has(atts, "post_setup_mode")) post_setup_mode = atts['post_setup_mode'];
										if (_.has(atts, "details") && atts["details"]) post_setup_mode = "";

										// fixed posts
										if (_.has(atts, 'mode') && atts['mode'] === 'fixed') {
											// from vc_posts_overview with one or handpicked posts
											// compute atts['posts'] as inner blocks
											// each as greyd/dynamic with selected post
											if (post_setup_mode == 'template') {
												// post_setup_mode == 'manual' is not supported
												if (_.has(atts, 'posts') && atts['posts'] != '') {
													var posts = JSON.parse(decodeURIComponent(atts['posts']));
													if (posts) for (var i=0; i<posts.length; i++) {
														var post_id_slug = 'posts_'+posts[i]['posts_pt']+'_id';
														var post_id = parseInt(posts[i][post_id_slug]);
														if (post_id) items.push(wp.blocks.createBlock( "greyd/dynamic", {
															...make_template_atts(),
															postId: post_id
														} ));
													}
												}
											}
											return items;
										}

										// latest posts
										if (post_setup_mode == 'template') {
											post_elements.push(wp.blocks.createBlock( "greyd/dynamic", {
												...make_template_atts()
											} ));
										}
										else if (post_setup_mode == 'manual') {
											// post_setup_mode == "manual";
											var post_setup = "";
											if (_.has(atts, "post_setup")) {
												post_setup = decodeURIComponent(atts['post_setup']);
												post_setup = JSON.parse(post_setup);
												// console.log(post_setup);
												for (var i=0; i<post_setup.length; i++) {
													var element_atts = {};
													// align
													if (_.has(post_setup[i], 'pull')) element_atts.textAlign = post_setup[i]['pull'].replace('_', '');
													if (post_setup[i]['post_element'] == 'title') {
														// title_size
														if (_.has(post_setup[i], "title_size") && post_setup[i]["title_size"].indexOf('h') == 0) 
															element_atts.level = parseInt(post_setup[i]["title_size"].replace('h', ''));
														else element_atts.fontSize = "txt";
														// text_color
														if (_.has(post_setup[i], "text_color")) {
															var color = computeColor("text_color", post_setup[i]);
															if (color.indexOf("color-") === 0 ) element_atts.textColor = color;
															else element_atts.style = { color: color };
														}
														// title_versal (no support)
														if (check_blocktype("core/post-title"))
															post_elements.push(wp.blocks.createBlock( "core/post-title", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'image') {
														if (_.has(element_atts, 'textAlign')) {
															if (element_atts.textAlign != 'center') element_atts.align = element_atts.textAlign;
															delete element_atts.textAlign;
														}
														else element_atts.align = 'left';
														// content_width
														if (_.has(post_setup[i], 'content_width')) element_atts.width = post_setup[i]['content_width'];
														// link_to
														if (_.has(post_setup[i], 'link_to')) element_atts.isLink = true;
														if (check_blocktype("core/post-featured-image"))
															post_elements.push(wp.blocks.createBlock( "core/post-featured-image", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'content') {
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														// content_length (no support)
														if (check_blocktype("core/post-content") && check_blocktype("core/post-excerpt")) {
															if (_.has(post_setup[i], 'content_length') && post_setup[i]["content_length"] == -1)
																post_elements.push(wp.blocks.createBlock( "core/post-content", element_atts ));
															else
																post_elements.push(wp.blocks.createBlock( "core/post-excerpt", element_atts ));
														}
													}
													if (post_setup[i]['post_element'] == 'author') {
														element_atts.showAvatar = false;
														element_atts.showBio = false;
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														// link_to (no support)
														if (check_blocktype("core/post-author"))
															post_elements.push(wp.blocks.createBlock( "core/post-author", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'categories') {
														element_atts.term = 'category';
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														// link_to (always)
														if (check_blocktype("core/post-terms"))
															post_elements.push(wp.blocks.createBlock( "core/post-terms", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'tags') {
														element_atts.term = 'post_tag';
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														// link_to (always)
														if (check_blocktype("core/post-terms"))
															post_elements.push(wp.blocks.createBlock( "core/post-terms", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'date') {
														// date_format (no support)
														// element_atts.format = "j. M Y G:i";
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														if (check_blocktype("core/post-date"))
															post_elements.push(wp.blocks.createBlock( "core/post-date", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'link') {
														if (_.has(element_atts, 'textAlign')) {
															element_atts.align = element_atts.textAlign;
															delete element_atts.textAlign;
														}
														var btn_atts = {};
														btn_atts.trigger = { type: "dynamic", params: { tag: "post" } };
														// link_text
														if (_.has(post_setup[i], "link_text")) btn_atts.content = post_setup[i]["link_text"];
														// link_icon
														if (_.has(post_setup[i], "link_icon"))  {
															var icon = { content: post_setup[i]["link_icon"] };
															if (_.has(post_setup[i], "link_icon_align")) icon.position = post_setup[i]["link_icon_align"];
															if (_.has(post_setup[i], "link_icon_margin")) icon.margin = post_setup[i]["link_icon_margin"];
															if (_.has(post_setup[i], "link_icon_size")) icon.size = post_setup[i]["link_icon_size"];
															btn_atts.icon = icon;
														}
														// link_style
														btn_atts.className = "is-style-link-prim";
														if (_.has(post_setup[i], "link_style"))  {
															if (post_setup[i]["link_style"] == "_button") btn_atts.className = "is-style-prim";
															if (post_setup[i]["link_style"] == "_button _sec") btn_atts.className = "is-style-sec";
															if (post_setup[i]["link_style"] == "_button _trd") btn_atts.className = "is-style-trd";
															if (post_setup[i]["link_style"] == "_link _sec") btn_atts.className = "is-style-link-sec";
															if (post_setup[i]["link_style"] == "custom") {
																btn_atts["custom"] = 1;
																btn_atts["customStyles"] = computeCustomButtonStyles(post_setup[i], 'link_');
															}
														}
														post_elements.push(wp.blocks.createBlock( "greyd/buttons", element_atts, [
															wp.blocks.createBlock( "greyd/button", btn_atts ) 
														] ));
													}
													if (post_setup[i]['post_element'] == 'empty') {
														element_atts = { 
															height: '20px',
															responsive: { height: { sm: "100%", md: "100%", lg: "100%", xl: "100%" } }
														} 
														// empty_size
														if (_.has(post_setup[i], "empty_size")) {
															element_atts.height = parseInt(post_setup[i]["empty_size"])+'px';
															// empty_size_xs
															if (_.has(post_setup[i], "empty_size_xs")) element_atts.responsive.height.sm = parseInt(post_setup[i]["empty_size_xs"])+'%';
															// empty_size_sm
															if (_.has(post_setup[i], "empty_size_sm")) element_atts.responsive.height.md = parseInt(post_setup[i]["empty_size_sm"])+'%';
															// empty_size_md
															if (_.has(post_setup[i], "empty_size_md")) element_atts.responsive.height.lg = parseInt(post_setup[i]["empty_size_md"])+'%';
															// empty_size_lg
															if (_.has(post_setup[i], "empty_size_lg")) element_atts.responsive.height.xl = parseInt(post_setup[i]["empty_size_lg"])+'%';
														}
														post_elements.push(wp.blocks.createBlock( "core/spacer", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'text') {
														// text_size text_color
														element_atts = { ...element_atts, ...make_size_color(post_setup[i]) };
														element_atts.content = (_.has(post_setup[i], "text_content")) ? post_setup[i]['text_content'] : '';
														post_elements.push(wp.blocks.createBlock( "core/paragraph", element_atts ));
													}
													if (post_setup[i]['post_element'] == 'seperator') {
														if (_.has(element_atts, 'textAlign')) {
															element_atts.align = element_atts.textAlign;
															delete element_atts.textAlign;
														}
														// sep_size
														if (_.has(post_setup[i], "sep_size") && post_setup[i]["sep_size"] != "100%")
															element_atts.className = "is-style-default";
														else
															element_atts.className = "is-style-wide";
														// sep_width (no support)
														// sep_style (no support)
														// sep_color
														if (_.has(post_setup[i], "sep_color")) {
															var color = computeColor("sep_color", post_setup[i]);
															if (color.indexOf("color-") === 0 ) element_atts.color = color;
															else element_atts.customColor = color;
														}
														post_elements.push(wp.blocks.createBlock( "core/separator", element_atts ));
													}
													// woocommerce (not supported)
													// price
													// cart-excerpt
													// cart
													// woo_ajax
												}
											}

											var wrap = {};
											var styles = {};
											// console.log(_.clone(atts));
											if (!_.has(atts, "post_linkfull") || atts["post_linkfull"] != 'no') {
												wrap.trigger = { type: "dynamic", params: { tag: "post" } };
												if (atts["post_linkfull"] == 'no')  wrap.trigger.params.opensInNewTab = true;
											}
											if (_.has(atts, "post_background")) {
												var color = computeColor("post_background", atts);
												styles.background = color;
											}
											if (_.has(atts, "post_border_radius")) {
												styles.borderRadius = parseInt(atts["post_border_radius"])+'px';
											}
											if (_.has(atts, "post_padding_top") || _.has(atts, "post_padding_right")) {
												var padding_top = _.has(atts, "post_padding_top") ? parseInt(atts["post_padding_top"])+'px' : '0px';
												var padding_right = _.has(atts, "post_padding_right") ? parseInt(atts["post_padding_right"])+'px' : '0px';
												styles.padding = {
													top: padding_top,
													right: padding_right,
													bottom: padding_top,
													left: padding_right,
												};
											}
											if (_.has(atts, "post_border_width")) {
												// console.log(atts);
												var width = parseInt(atts["post_border_width"])+'px';
												if (_.has(atts, "post_border_color")) {
													var color = computeColor("post_border_color", atts);
													if (color.indexOf("color-") === 0 ) {
														color = "var(--"+color.replace("-", "")+")";
													}
													var t = _.has(atts, "post_border_top") ? atts["post_border_top"] : 'none';
													var r = _.has(atts, "post_border_right") ? atts["post_border_right"] : 'none';
													var b = _.has(atts, "post_border_bottom") ? atts["post_border_bottom"] : 'none';
													var l = _.has(atts, "post_border_left") ? atts["post_border_left"] : 'none';
													styles.border = {
														top: width+' '+t+' '+color,
														right: width+' '+r+' '+color,
														bottom: width+' '+b+' '+color,
														left: width+' '+l+' '+color,
													};
												}
											}
											// console.log(styles);
											if (!_.isEmpty(styles)) {
												wrap.greydStyles = styles;
												wrap.greydClass = greyd.tools.generateGreydClass();
											}
											// console.log(wrap);
											if (!_.isEmpty(wrap)) {
												post_elements = [ wp.blocks.createBlock( "greyd/box", wrap, post_elements ) ];
											}

										}
										
										// page_title_* block
										if (_.has(atts, "page_title") && atts["page_title"] != "") {
											if (_.has(atts, "page_title_layout") && atts["title_layout"] == 'bottom')
												post_elements.push(make_title_block(atts, "page_title"));
											else
												post_elements.unshift(make_title_block(atts, "page_title"));
										}
										
										// make inner blocks

										// title_* block
										if (_.has(atts, "title") && atts["title"] != "") {
											items.push(make_title_block(atts, "title"));
										}

										// details_title_* block
										if (_.has(atts, "details_title") && atts["details_title"] != "") {
											items.push(make_title_block(atts, "details_title"));
										}

										var template_atts = { pagination: { enable: false } };
										if (!_.has(atts, 'single')) {
											// console.log(_.clone(atts));
											var ppp = (_.has(atts, "ppp")) ? parseInt(atts['ppp']) : -1;
											// make sorting and pagination only when posts are not limited
											if (ppp == -1) {
												// sorting
												var pre = 'sorting';
												if (_.has(atts, 'search_sorting_dropdown_frontend')) pre = 'search_sorting';
												if (_.has(atts, pre+'_dropdown_frontend') && atts[pre+'_dropdown_frontend'] == 'show') {
													template_atts.sorting = { enable: true, align: 'start' };
													if (_.has(atts, pre+'_position')) {
														if (atts[pre+'_position'].indexOf('bottom') > -1) {
															template_atts.sorting.position = 'bottom';
														}
														if (atts[pre+'_position'].indexOf('flex-start') > -1) {
															template_atts.sorting.align = 'start';
														}
														if (atts[pre+'_position'].indexOf('flex-end') > -1) {
															template_atts.sorting.align = 'end';
														}
													}
													// inputStyle
													if (_.has(atts, pre+'_style')) {
														if (atts[pre+'_style'] == '_input _sec')
															template_atts.sorting.inputStyle = 'sec';
														else if (atts[pre+'_style'] == 'custom') {
															// custom
															template_atts.sorting.custom = true;
															template_atts.sorting.customStyles = computeCustomButtonStyles(atts, 'sorting_');
														}
													}
													// options labels
													var options = {};
													if (_.has(atts, pre+'_text_date_desc')) options.date_DESC = atts[pre+'_text_date_desc'];
													if (_.has(atts, pre+'_text_date_asc')) options.date_ASC = atts[pre+'_text_date_asc'];
													if (_.has(atts, pre+'_text_title_asc')) options.title_DESC = atts[pre+'_text_title_asc'];
													if (_.has(atts, pre+'_text_title_desc')) options.title_ASC = atts[pre+'_text_title_desc'];
													if (!_.isEmpty(options)) template_atts.sorting.options = options;

													// greydStyles
													var greydStyles = {
														width: '40%',
														padding: { top: '30px', right: '0px', bottom: '30px', left: '0px' }
													}
													// width
													if (_.has(atts, pre+'_width')) {
														var padding = parseInt(atts[pre+'_width']);
														var unit = atts[pre+'_width'].replace(padding, '');
														if (unit == '') unit = 'px';
														greydStyles.padding.top = padding+unit;
													}
													// padding
													if (_.has(atts, pre+'_margin_top')) {
														var padding = parseInt(atts[pre+'_margin_top']);
														greydStyles.padding.top = padding+'px';
													}
													if (_.has(atts, pre+'_margin_bottom')) {
														var padding = parseInt(atts[pre+'_margin_bottom']);
														greydStyles.padding.bottom = padding+'px';
													}
													if (!_.isEmpty(greydStyles)) template_atts.sorting.greydStyles = greydStyles;
												}
												// pagination
												template_atts.pagination = { enable: true };
												if (_.has(atts, 'pagination_layout')) {
													// console.log(_.clone(atts));
													switch (atts['pagination_layout']) {
														case 'bottom':
															template_atts.pagination = { enable: true };
															break;
														case 'top':
															template_atts.pagination = { enable: true, position: 'top' };
															break;
														case 'bottom_side': // pagination2
															template_atts.pagination = { enable: true, arrows_type: '' };
															template_atts.arrows = { enable: true };
															break;
														case 'top_side': // pagination2
															template_atts.pagination = { enable: true, position: 'top', arrows_type: '' };
															template_atts.arrows = { enable: true };
															break;
														case 'none_side': // pagination2
															template_atts.pagination = { enable: false };
															template_atts.arrows = { enable: true };
															break;
														case 'only_bottom': // pagination2
															template_atts.pagination = { enable: true, arrows_type: '' };
															break;
														case 'only_top': // pagination2
															template_atts.pagination = { enable: true, position: 'top', arrows_type: '' };
															break;
														case 'none':
															template_atts.pagination = { enable: false };
															break;
													}
												}
												if (!_.has(atts, 'pagination_layout') || atts['pagination_layout'] == 'bottom' || atts['pagination_layout'] == 'top') {
													// pagination_*
													// console.log(_.clone(atts));
													if (_.has(atts, 'pagination_pull')) {
														if (atts['pagination_pull'] == '_left') template_atts.pagination.align = 'start';
														if (atts['pagination_pull'] == '_right') template_atts.pagination.align = 'end';
														if (atts['pagination_pull'] == '_space') template_atts.pagination.align = 'space-between';
													}
													if (_.has(atts, 'pagination_overlap') && atts['pagination_overlap'] == 'enable') template_atts.pagination.overlap = true;
													// types
													if (!_.has(atts, 'pagination_numbers') || atts['pagination_numbers'] == '') {
														template_atts.pagination.type = 'text';
														if (_.has(atts, 'pagination_numbers_style')) template_atts.pagination.text_type = atts['pagination_numbers_style'];
													}
													if (_.has(atts, 'pagination_numbers')) {
														if (atts['pagination_numbers'] == 'icons') {
															template_atts.pagination.type = 'icon';
															if (_.has(atts, 'pagination_numbers_icon')) template_atts.pagination.icon_normal = atts['pagination_numbers_icon'];
															if (_.has(atts, 'pagination_numbers_icon_active')) template_atts.pagination.icon_active = atts['pagination_numbers_icon_active'];
														}
														else if (atts['pagination_numbers'] == 'none') {
															template_atts.pagination.type = '';
														}
														else if (atts['pagination_numbers'] == 'custom') {
															template_atts.pagination.type = 'image';
															template_atts.pagination.arrows_type = 'image';
															if (_.has(atts, 'pagination_img_previous')) template_atts.pagination.img_previous = atts['pagination_img_previous'];
															if (_.has(atts, 'pagination_numbers_img')) template_atts.pagination.img_normal = atts['pagination_numbers_img'];
															if (_.has(atts, 'pagination_numbers_img_active')) template_atts.pagination.img_active = atts['pagination_numbers_img_active'];
															if (_.has(atts, 'pagination_img_next')) template_atts.pagination.img_next = atts['pagination_img_next'];
														}
													}
													if (template_atts.pagination.type != 'image') {
														if (_.has(atts, 'pagination_icon_previous')) template_atts.pagination.icon_previous = atts['pagination_icon_previous'];
														if (_.has(atts, 'pagination_icon_next')) template_atts.pagination.icon_next = atts['pagination_icon_next'];
													}

													// greydStyles
													var greydStyles = {
														padding: { top: '30px', right: '5px', bottom: '0px', left: '5px' },
														gutter: '10px',
													}
													// padding
													if (_.has(atts, 'pagination_margin_top')) {
														greydStyles.padding.top = parseInt(atts['pagination_margin_top'])+'px';
													}
													if (_.has(atts, 'pagination_padding')) {
														var padding = parseInt(atts['pagination_padding']);
														greydStyles.padding.top = (parseInt(greydStyles.padding.top) + padding)+'px';
														greydStyles.padding.bottom = padding+'px';
													}
													if (_.has(atts, 'pagination_icon_padding')) {
														var padding = parseInt(atts['pagination_icon_padding']);
														greydStyles.padding.right = padding+'px';
														greydStyles.padding.left = padding+'px';
													}
													// gutter
													if (_.has(atts, 'pagination_numbers_padding')) {
														greydStyles.gutter = parseInt(atts['pagination_numbers_padding'])+'px';
													}
													// color
													if (_.has(atts, 'pagination_color')) {
														var color = atts['pagination_color'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.color = color;
													}
													if (_.has(atts, 'pagination_color_hover')) {
														var color = atts['pagination_color_hover'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.hover = { color: color };
													}
													if (_.has(atts, 'pagination_color_active')) {
														var color = atts['pagination_color_active'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.active = { color: color };
													}
													// opacity
													if (_.has(atts, 'pagination_opacity')) {
														greydStyles.opacity = parseInt(atts['pagination_opacity'])+'%';
													}
													if (_.has(atts, 'pagination_opacity_hover')) {
														greydStyles.hover = { ...greydStyles.hover, opacity: parseInt(atts['pagination_opacity_hover'])+'%' };
													}
													if (_.has(atts, 'pagination_opacity_active')) {
														greydStyles.active = { ...greydStyles.active, opacity: parseInt(atts['pagination_opacity_active'])+'%' };
													}
													// fontSize
													if (template_atts.pagination.type != 'image' && _.has(atts, 'pagination_text_size')) {
														greydStyles.fontSize = make_size(atts['pagination_text_size']);
													}
													if (template_atts.pagination.type == 'image' && _.has(atts, 'pagination_img_size')) {
														greydStyles.fontSize = parseInt(atts['pagination_img_size'])+'px';
													}
													if (!_.isEmpty(greydStyles)) template_atts.pagination.greydStyles = greydStyles;
												}
												else if (_.has(template_atts, 'pagination') && template_atts.pagination.enable) {
													// pagination2_numbers_*
													// console.log(_.clone(atts));
													if (_.has(atts, 'pagination2_numbers_align')) {
														if (atts['pagination2_numbers_align'] == '_left') template_atts.pagination.align = 'start';
														if (atts['pagination2_numbers_align'] == '_right') template_atts.pagination.align = 'end';
														if (atts['pagination2_numbers_align'] == '_space') template_atts.pagination.align = 'space-between';
													}
													if (_.has(atts, 'pagination2_numbers_overlap') && atts['pagination2_numbers_overlap'] == 'enable') template_atts.pagination.overlap = true;
													// types
													if (!_.has(atts, 'pagination2_numbers') || atts['pagination2_numbers'] == '') {
														template_atts.pagination.type = 'text';
														if (_.has(atts, 'pagination2_numbers_style')) template_atts.pagination.text_type = atts['pagination2_numbers_style'];
													}
													if (_.has(atts, 'pagination2_numbers')) {
														if (atts['pagination2_numbers'] == 'icons') {
															template_atts.pagination.type = 'icon';
															if (_.has(atts, 'pagination2_numbers_icon')) template_atts.pagination.icon_normal = atts['pagination2_numbers_icon'];
															if (_.has(atts, 'pagination2_numbers_icon_active')) template_atts.pagination.icon_active = atts['pagination2_numbers_icon_active'];
														}
														else if (atts['pagination2_numbers'] == 'custom') {
															template_atts.pagination.type = 'image';
															if (_.has(atts, 'pagination2_numbers_img')) template_atts.pagination.img_normal = atts['pagination2_numbers_img'];
															if (_.has(atts, 'pagination2_numbers_img_active')) template_atts.pagination.img_active = atts['pagination2_numbers_img_active'];
														}
														else if (atts['pagination2_numbers'] == 'dots' || atts['pagination2_numbers'] == 'blocks') {
															template_atts.pagination.type = 'icon';
															template_atts.pagination.icon_type = atts['pagination2_numbers'];
														}
													}

													// greydStyles
													var greydStyles = {
														padding: { top: '10px', right: '0px', bottom: '10px', left: '0px' },
														gutter: '10px',
													}
													// padding
													if (_.has(atts, 'pagination2_numbers_padding_tb')) {
														var padding = parseInt(atts['pagination2_numbers_padding_tb']);
														greydStyles.padding.top = padding+'px';
														greydStyles.padding.bottom = padding+'px';
													}
													if (_.has(atts, 'pagination2_numbers_padding_lr')) {
														var padding = parseInt(atts['pagination2_numbers_padding_lr']);
														greydStyles.padding.right = padding+'px';
														greydStyles.padding.left = padding+'px';
													}
													// gutter
													if (_.has(atts, 'pagination2_numbers_space')) {
														greydStyles.gutter = parseInt(atts['pagination2_numbers_space'])+'px';
														if (_.has(atts, 'pagination2_numbers_space_mobile')) {
															greydStyles.responsive = { md: { gutter: parseInt(atts['pagination2_numbers_space_mobile'])+'px' } };
														}
													}
													// color
													if (_.has(atts, 'pagination2_numbers_color')) {
														var color = atts['pagination2_numbers_color'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.color = color;
													}
													if (_.has(atts, 'pagination2_numbers_color_hover')) {
														var color = atts['pagination2_numbers_color_hover'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.hover = { color: color };
													}
													if (_.has(atts, 'pagination2_numbers_color_active')) {
														var color = atts['pagination2_numbers_color_active'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.active = { color: color };
													}
													// opacity
													if (_.has(atts, 'pagination2_numbers_opacity')) {
														greydStyles.opacity = parseInt(atts['pagination2_numbers_opacity'])+'%';
													}
													if (_.has(atts, 'pagination2_numbers_opacity_hover')) {
														greydStyles.hover = { ...greydStyles.hover, opacity: parseInt(atts['pagination2_numbers_opacity_hover'])+'%' };
													}
													if (_.has(atts, 'pagination2_numbers_opacity_active')) {
														greydStyles.active = { ...greydStyles.active, opacity: parseInt(atts['pagination2_numbers_opacity_active'])+'%' };
													}
													// fontSize
													if (_.has(atts, 'pagination2_numbers_text_size')) {
														greydStyles.fontSize = parseInt(atts['pagination2_numbers_text_size'])+'px';
													}
													if (_.has(atts, 'pagination2_numbers_img_size')) {
														greydStyles.fontSize = parseInt(atts['pagination2_numbers_img_size'])+'px';
													}
													if (_.has(atts, 'pagination2_numbers_img_size_mobile')) {
														greydStyles.responsive = { md: { ...greydStyles.responsive.md , fontSize: parseInt(atts['pagination2_numbers_img_size_mobile'])+'px' } };
													}
													if (!_.isEmpty(greydStyles)) template_atts.pagination.greydStyles = greydStyles;
												}
												if (_.has(template_atts, 'arrows') && template_atts.arrows.enable) {
													// pagination2_arrows_*
													// console.log(_.clone(atts));
													if (_.has(atts, 'pagination2_arrows_align')) {
														if (atts['pagination2_arrows_align'] == 'top') template_atts.arrows.position = 'start';
														if (atts['pagination2_arrows_align'] == 'bottom') template_atts.arrows.position = 'end';
													}
													if (_.has(atts, 'pagination2_arrows_overlap') && atts['pagination2_arrows_overlap'] == 'enable') template_atts.arrows.overlap = true;
													// types
													if (!_.has(atts, 'pagination2_arrows') || atts['pagination2_arrows'] == '') {
														template_atts.arrows.type = 'icon';
														if (_.has(atts, 'pagination2_arrows_icon_previous')) template_atts.arrows.icon_previous = atts['pagination2_arrows_icon_previous'];
														if (_.has(atts, 'pagination2_arrows_icon_next')) template_atts.arrows.icon_next = atts['pagination2_arrows_icon_next'];
													}
													else if (atts['pagination2_arrows'] == 'custom') {
														template_atts.arrows.type = 'image';
														if (_.has(atts, 'pagination2_arrows_img_previous')) template_atts.arrows.img_previous = atts['pagination2_arrows_img_previous'];
														if (_.has(atts, 'pagination2_arrows_img_next')) template_atts.arrows.img_next = atts['pagination2_arrows_img_next'];
													}

													// greydStyles
													var greydStyles = {
														padding: { top: '10px', right: '10px', bottom: '10px', left: '10px' },
														fontSize: '40px'
													}
													// padding
													if (_.has(atts, 'pagination2_arrows_padding_tb')) {
														var padding = parseInt(atts['pagination2_arrows_padding_tb']);
														greydStyles.padding.top = padding+'px';
														greydStyles.padding.bottom = padding+'px';
													}
													if (_.has(atts, 'pagination2_arrows_padding_lr')) {
														var padding = parseInt(atts['pagination2_arrows_padding_lr']);
														greydStyles.padding.right = padding+'px';
														greydStyles.padding.left = padding+'px';
													}
													// color
													if (_.has(atts, 'pagination2_arrows_color')) {
														var color = atts['pagination2_arrows_color'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.color = color;
													}
													if (_.has(atts, 'pagination2_arrows_color_hover')) {
														var color = atts['pagination2_arrows_color_hover'].replace('_', ''); 
														if (color.indexOf("color") === 0 ) color = 'var(--'+color+')';
														greydStyles.hover = { color: color };
													}
													// opacity
													if (_.has(atts, 'pagination2_arrows_opacity')) {
														greydStyles.opacity = parseInt(atts['pagination2_arrows_opacity'])+'%';
													}
													if (_.has(atts, 'pagination2_arrows_opacity_hover')) {
														greydStyles.hover = { ...greydStyles.hover, opacity: parseInt(atts['pagination2_arrows_opacity_hover'])+'%' };
													}
													// fontSize
													if (_.has(atts, 'pagination2_arrows_size')) {
														greydStyles.fontSize = parseInt(atts['pagination2_arrows_size'])+'px';
													}
													if (_.has(atts, 'pagination2_arrows_size_mobile')) {
														greydStyles.responsive = { md: { ..._.has(greydStyles.responsive, 'md') ? greydStyles.responsive.md : {} , fontSize: parseInt(atts['pagination2_arrows_size_mobile'])+'px' } };
													}
													if (!_.isEmpty(greydStyles)) template_atts.arrows.greydStyles = greydStyles;
												}
											}
											// anim
											var anim = {};
											if (_.has(atts, "posts_animation") && atts['posts_animation'] != 'slide') anim.anim = atts['posts_animation'];
											if (_.has(atts, "posts_height") && atts['posts_height'] != 'max') anim.height = atts['posts_height'];
											if (_.has(atts, "posts_height_custom") && atts['posts_height_custom'] != '500px') anim.height_custom = atts['posts_height_custom'];
											// 'posts_height_custom_mobile', // no support
											if (_.has(atts, "posts_scroll_top") && atts['posts_scroll_top'] == 'enable') anim.scroll_top = true;
											if (_.has(atts, "posts_loop") && atts['posts_loop'] == 'enable') anim.loop = true;
											if (_.has(atts, "posts_autoplay") && atts['posts_autoplay'] == 'enable') anim.autoplay = true;
											if (_.has(atts, "posts_interval") && atts['posts_interval'] != '5') anim.interval = parseInt(atts['posts_interval']);
											if (_.has(atts, "posts_id") && atts['posts_id'] != '') template_atts['anchor'] = atts['posts_id'];
											if (!_.isEmpty(anim)) template_atts["animation"] = anim;

										}
										items.push(wp.blocks.createBlock( "core/post-template", template_atts, post_elements ));

										if (!_.has(atts, 'single')) {
											// posts_margin_bottom (Abstand darunter) to spacer block
											var bottom = (_.has(atts, 'posts_margin_bottom')) ? parseInt(atts['posts_margin_bottom']) : 30;
											if (bottom > 0) items.push(wp.blocks.createBlock( "core/spacer", { height: bottom+'px' } ));
										}
										
										return items;
								  } },
								  { compute: function(atts) {
										// console.log(atts);
										var new_atts = {};
										
										// from vc_posts_overview with one or handpicked posts just return empty atts for group
										// else compute atts with query
										if (!_.has(atts, 'mode') || atts['mode'] != 'fixed') {

											var query = {
												// settings
												inherit: false, postType: "post", orderBy: "date", order: "desc", sticky: "",
												// filter
												taxQuery: {}, author: "", search: "", exclude: [],
												// toolbar
												perPage: null, offset: 0, pages: 0,
											};
											if (_.has(atts, 'inherit') && atts['inherit']) {
												// from vc_posts_tp used in archives templates
												query['inherit'] = true;
												query['perPage'] = parseInt(greyd.data.posts_per_page);
												if (greyd.data.post_type == "dynamic_template") {
													if (greyd.data.template_type == "single" || greyd.data.template_type == "archives" || greyd.data.template_type == "search") {
														if (greyd.data.post_name.indexOf('-') > -1) {
															var pt = greyd.data.post_name.split('-');
															query['postType'] = pt[1];
														}
													}
												}
												if (_.has(atts, 'single') && atts['single']) {
													// from vc_post_tp
													query['perPage'] = 1;
												}
												else {
													var layout = (_.has(atts, "posts_layout")) ? atts['posts_layout'] : 'grid';
													if (layout == 'grid' || layout == 'horizontal') {
														var columns = (_.has(atts, "posts_columns")) ? parseInt(atts['posts_columns']) : 5;
														if (columns > 1) {
															new_atts["displayLayout"] = { 
																"type": "flex",
																"columns": columns
															};
														}
													}
												}
											}
											else {
												// from vc_posts_overview
												// atts['mode'] is latest
												// fixed atts['posts'] is deprecated
												if (_.has(atts, "pt")) {
													query['postType'] = atts['pt'];
												}
												if (query['postType'] == "post") {
													if (_.has(atts, "categoryid")) {
														var cat = parseInt(atts['categoryid']);
														if (cat != 0) query['taxQuery']["category"] = [cat];
													}
													if (_.has(atts, "tagid")) {
														var tag = parseInt(atts['tagid']);
														if (tag != 0) query['taxQuery']["post_tag"] = [tag];
													}
												}
												else if (query['postType'] != "page") {
													// dpt
													for (var i=0; i<greyd.data.post_types.length; i++) {
														// console.log(greyd.data.post_types[i]);
														if (greyd.data.post_types[i].slug == query['postType']) {
															var pt = greyd.data.post_types[i];
															if (_.has(pt, "taxes") && pt.taxes.length > 0) {
																// get categories, tags and customtax
																for (var j=0; j<pt.taxes.length; j++) {
																	var slug = pt.taxes[j].slug;
																	// slug+'_categoryid'
																	// slug+'_tagid'
																	if (_.has(atts, slug+'id')) {
																		query['taxQuery'][slug] = [ parseInt(atts[slug+'id']) ];
																		// query[slug+'Ids'] = [ parseInt(atts[slug+'id']) ];
																	}
																	// "customtax_"+name
																	if (_.has(atts, 'customtax_'+slug)) {
																		query['taxQuery'][slug] = [ parseInt(atts['customtax_'+slug]) ];
																		// query[slug+'Ids'] = [ parseInt(atts['customtax_'+slug]) ];
																	}
																}
															}
															break;
														}
													}
												}

												// initial sorting
												if (_.has(atts, 'sorting_mode_initial')) {
													var sort = atts['sorting_mode_initial'].split(' ');
													query['orderBy'] = sort[0];
													query['order'] = sort[1].toLowerCase();
												}

												var layout = (_.has(atts, "posts_layout")) ? atts['posts_layout'] : 'grid';
												var columns = (_.has(atts, "posts_columns")) ? parseInt(atts['posts_columns']) : 5;
												var rows = (_.has(atts, "posts_rows")) ? parseInt(atts['posts_rows']) : 5;
												switch (layout) {
													case 'grid': 
														query['perPage'] = columns*rows; break;
													case 'horizontal': 
														query['perPage'] = columns; break;
													case 'vertical': 
													case 'table':
														columns = 1;
														query['perPage'] = rows; break;
													case 'single':
														columns = 1; rows = 1;
														query['perPage'] = 1; break;
												}
												var ppp = (_.has(atts, "ppp")) ? parseInt(atts['ppp']) : -1;
												if (ppp != -1) query['pages'] = Math.ceil(ppp/query['perPage']);

												if (columns > 1) {
													new_atts["displayLayout"] = { 
														type: "flex",
														columns: columns
													};
													if (_.has(atts, "responsive_breaks") && atts['responsive_breaks'] == 'manual') {
														// responsive_breaks
														var responsive = {};
														if (_.has(atts, "post_columns_lg")) responsive.lg = { columns: atts['post_columns_lg'] };
														if (_.has(atts, "post_columns_md")) responsive.md = { columns: atts['post_columns_md'] };
														if (_.has(atts, "post_columns_sm")) responsive.sm = { columns: atts['post_columns_sm'] };
														if (!_.isEmpty(responsive)) new_atts["displayLayout"]["responsive"] = responsive;
													}
												}
												if (layout == 'table') new_atts["displayLayout"] = { type: "table", columns: columns };
												// if (layout == 'single') new_atts["displayLayout"] = { type: "slider", columns: columns };
												// deprecated (Abstand zwischen den Beiträgen)
												// delete atts['posts_padding'];
												// delete atts['posts_padding_lg'];
												// delete atts['posts_padding_md'];
												// delete atts['posts_padding_sm'];

												// todo table, slider
												// console.log(_.clone(atts));
											}
											// add query atts
											new_atts['query'] = query;
										}

										// new_atts['raw'] = JSON.stringify(atts, null, 2);
										// console.log(_.clone(new_atts));
										
										return new_atts;
								  } } ],
		// to greyd blocks forms
		"greyd/form":		[  ],
		"greyd-forms/input": 		[	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "rows", map: "rows", convert: function(value) { return lodash.toNumber(value) } },
										{ key: "alignment", map: "align" },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ key: "area", map: "area", convert: function(value) { return !!value } },
										{ compute: function(atts) {
											
											/**
											 * Label
											 */

											if ( _.has(atts, "label_font_size") ) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 * Tooltip
											 */

											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}
											
											/**
											 * Block & Custom Styles
											 */

											if (atts['style'] == "custom") {
												atts['custom'] = true;
												atts["customStyles"] = computeCustomInputStyles(atts, 'input_');

												delete atts['style'];
												delete atts['input_color'];
												delete atts['input_background'];
												delete atts['input_font_size'];
												delete atts['input_padding_top'];
												delete atts['input_padding_right'];
												delete atts['input_border_radius'];
												delete atts['input_border'];
												delete atts['input_border_width'];
												delete atts['input_border_top'];
												delete atts['input_border_right'];
												delete atts['input_border_bottom'];
												delete atts['input_border_left'];
												delete atts['input_shadow'];
												delete atts['input_color_hover'];
												delete atts['input_background_hover'];
												delete atts['input_border_hover'];
												delete atts['input_shadow_hover'];
											} else if ( atts['style'] == "sec") {
												delete atts['style'];
												atts['className'] = "is-style-sec";
											} else {
												delete atts['style'];
												atts['className'] = "is-style-prim";
											}
											
											/**
											 * Width
											 */

											if (_.has(atts, "width")) {
												atts["greydStyles"] = {
													'width': atts['width']
												}
												delete atts['width'];
											}


											var restrictions = {}

											if (_.has(atts, "min") ) {
												restrictions["min"] = lodash.toNumber(atts['min']);
												delete atts['min'];
											}
											if (_.has(atts, "max") ) {
												restrictions["max"] = lodash.toNumber(atts['max']);
												delete atts['max'];
											}
											if (_.has(atts, "minlength") ) {
												restrictions["minlength"] = lodash.toNumber(atts['minlength']);
												delete atts['minlength'];
											}
											if (_.has(atts, "maxlength") ) {
												restrictions["maxlength"] = lodash.toNumber(atts['maxlength']);
												delete atts['maxlength'];
											}
											if (_.has(atts, "start") ) {
												restrictions["start"] = lodash.toNumber(atts['start']);
												delete atts['start'];
											}
											if (_.has(atts, "step") ) {
												restrictions["step"] = lodash.toNumber(atts['step']);
												delete atts['step'];
											}

											if (!_.isEmpty(restrictions)) {
												atts["restrictions"] = restrictions;
											}


									
											/**
											 * Return
											 */
											return atts;

									} } ],
		"greyd-forms/checkbox": 	[	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ key: "is_agb", map: "useSetting", convert: function(value) { 
											if (value == "agb") return true;
											else return false; }
										},
										{ key: "content", fill: function(inner) { return inner.trim().split('\n').join('<br>') } },
										{ compute: function(atts) {
											

											/**
											 * Block Styles
											 */

											if (atts['style'] == "radio") {
												delete atts['style'];
												atts['className'] = "is-style-radio";
											} else if ( atts['style'] == "switch") {
												delete atts['style'];
												atts['className'] = "is-style-switch";
											} else {
												delete atts['style'];
												atts['className'] = "is-style-checkbox";
											}

											/**
											 * fontsize
											 */

											if (_.has(atts, "fontsize")) {
												atts["greydStyles"] = {
													
													'fontSize': atts['fontsize']
												}
												delete atts['fontsize'];
											}

											/**
											 * lineheight
											 */

											if (_.has(atts, "lineheight")) {
												atts["greydStyles"] = {
													...atts["greydStyles"],
													'lineHeight': atts['lineheight']
												}
												delete atts['lineheight'];
											}
											
											/**
											 * marginLeft
											 */

											 if (_.has(atts, "marginleft")) {
												atts["greydStyles"] = {
													...atts["greydStyles"],
													'marginLeft': atts['marginleft']
												}
												delete atts['marginleft'];
											}

											/**
											 * textcolor
											 */

											 if (_.has(atts, "textcolor")) {
												atts["greydStyles"] = {
													...atts["greydStyles"],
													'fontColor': transformColorIntoVar( atts['textcolor'] )
												}
												delete atts['textcolor'];
											}
											return atts;
										} }
								 
								
									],
		"greyd-forms/submitbutton": [	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "text", map: "content" },
										{ key: "size", map: "size", convert: function(value) { return value.replace('_', '') } },
										{ key: "alignment", map: "align" },
										{ compute: function(atts) {

											/**
											 * Label
											 */

											if ( _.has(atts, "label_font_size") ) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 * Tooltip
											 */

											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}

											/**
											 * Icon
											 */

											if (_.has(atts, "icon")) {
												atts["icon"] = {
													'content': atts['icon'],
													'position': _.has(atts, "icon_align") && atts["icon_align"] == 'left' ? 'before' : 'after',
													'size': _.has(atts, "icon_size") ? atts["icon_size"] : '100%',
													'margin': _.has(atts, "icon_margin") ? atts["icon_margin"] : '10px',
												}
												delete atts["icon_align"];
												delete atts["icon_size"];
												delete atts["icon_margin"];
											}


											/**
											 * Button Design
											 */
			
											atts['className'] = 'is-style-prim';
											if (atts['type'] == "sec") {
												atts['className'] = "is-style-sec";
											} else if (atts['type'] == "trd") {
												atts['className'] = "is-style-trd";
											} 

											/**
											* Custom Button
											*/

											if (atts['type'] == "custom") {
												atts['custom'] = true;
												atts["customStyles"] = computeCustomButtonStyles(atts, 'btn_');
												delete atts['btn_color'];
												delete atts['btn_background'];
												delete atts['btn_font_size'];
												delete atts['btn_padding_top'];
												delete atts['btn_padding_right'];
												delete atts['btn_border_radius'];
												delete atts['btn_border'];
												delete atts['btn_border_width'];
												delete atts['btn_border_top'];
												delete atts['btn_border_right'];
												delete atts['btn_border_bottom'];
												delete atts['btn_border_left'];
												delete atts['btn_shadow'];
												delete atts['btn_color_hover'];
												delete atts['btn_background_hover'];
												delete atts['btn_border_hover'];
												delete atts['btn_shadow_hover'];
											}
											delete atts['mode'];

											/**
											* Return
											*/
											return atts;
									} } ],
		"greyd-forms/radiobuttons": [	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ compute: function(atts) {

											console.log(atts);

											atts['className'] = "is-style-radio"

											var iconStyles = {
												hover: {},
												active: {}
											};

											if ( _.has(atts, "style") && atts['style'] == "switch") {
												delete atts['style'];
												atts['className'] = "is-style-switch";

												if ( _.has(atts, "switch_color") ) {
													iconStyles["color"] = transformColorIntoVar( atts["switch_color"] ); 
													delete atts["switch_color"];
												} 
												if ( _.has(atts, "switch_color_selected") ) {
													iconStyles["active"]["color"] = transformColorIntoVar( atts["switch_color_selected"] ); 
													delete atts["switch_color_selected"];
												}

											} else if ( _.has(atts, "style") && atts['style'] == "checkbox") {
												delete atts['style'];
												atts['className'] = "is-style-checkbox";
											}

											if (!_.isEmpty(iconStyles)) {
												atts["iconStyles"] = iconStyles;
											}

											/**
											 * Label
											 */
											if ( _.has(atts, "label_font_size") ) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 * Options Font Size
											 */
											if ( _.has(atts, "options_font_size") ) {
												atts["greydStyles"] = {
													'fontSize': atts['options_font_size']
												}
												delete atts['options_font_size'];
											}

											/**
											 * width
											 */
											if (_.has(atts, "width")) {
												atts["greydStyles"] = {
													...atts["greydStyles"],
													'width': atts['width']
												}
												delete atts['width'];
											}

											/**
											 * layout
											 */
											 if (_.has(atts, "layout")) {
												atts["greydStyles"] = {
													...atts["greydStyles"],
													'display': atts['layout']
												}
												delete atts['layout'];
											}
	
											/**
											 * Tooltip
											 */
											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}
												
											/**
											 * Return
											 */
											return atts; 
										} },
										{ inner: function(atts) {

											var name = _.has(atts, "name") ? atts["name"] : "";

											let options = [];
											let newOptions = [];
											
											if ( _.has(atts, "inputtype") && atts["inputtype"] == "list" ) {
												if ( _.has(atts, "list_options") ) {

													options = atts["list_options"].split(",");
				
													let cache = [];
													options.forEach( function( option ) {
														option = option.trim();
														cache.push( {
															show: option.split("=")[0],
															value: option.includes("=") ? option.split("=")[1] : option,
														} )
													} ) ;
													options = cache;
												} 
											} else if ( _.has(atts, "inputtype") && atts["inputtype"] == "predefined" ) {

												// predefined lists?

											} else if ( _.has(atts, "options") ) {
												options = JSON.parse( decodeURIComponent(atts["options"]) );
											}

											options.forEach(function(option) { 
												newOptions.push(wp.blocks.createBlock( "greyd-forms/radiobutton-item", { title: option.show, value: option.value, name: name } )); 
											});

											return newOptions;
											
										} }
										
									],
		"greyd-forms/dropdown":		[	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "alignment", map: "align" },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ compute: function( atts ) {

											/**
											 * Label
											 */
											if ( _.has(atts, "label_font_size") ) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 *  width
											 */
											if (_.has(atts, "width")) {
												atts["greydStyles"] = {
													'width': atts['width']
												}
												delete atts['width'];
											}

											/**
											 * Tooltip
											 */

											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}
											
											/**
											 * Block & Custom Styles
											 */
											if (atts['style'] == "custom") {
												atts['custom'] = true;
												atts["customStyles"] = computeCustomInputStyles(atts, 'input_');

												delete atts['style'];
												delete atts['input_color'];
												delete atts['input_background'];
												delete atts['input_font_size'];
												delete atts['input_padding_top'];
												delete atts['input_padding_right'];
												delete atts['input_border_radius'];
												delete atts['input_border'];
												delete atts['input_border_width'];
												delete atts['input_border_top'];
												delete atts['input_border_right'];
												delete atts['input_border_bottom'];
												delete atts['input_border_left'];
												delete atts['input_shadow'];
												delete atts['input_color_hover'];
												delete atts['input_background_hover'];
												delete atts['input_border_hover'];
												delete atts['input_shadow_hover'];
											} else if ( atts['style'] == "sec") {
												delete atts['style'];
												atts['className'] = "is-style-sec";
											} else {
												delete atts['style'];
												atts['className'] = "is-style-prim";
											}

											if ( _.has(atts, "inputtype") && atts["inputtype"] == "list" ) {
												if ( _.has(atts, "list_options") ) {

													optionsString = atts["list_options"];
													options = optionsString.split(",");
													let cache = [];
													options.forEach( function( option ) {
														option = option.trim();
														cache.push( {
															show: option.split("=")[0],
															value: option.includes("=") ? option.split("=")[1] : option,
														} )
													} ) ;
													options = cache;
												} 
											} else if ( _.has(atts, "options") ) {
												var options = JSON.parse( decodeURIComponent( atts["options"] ) );
												
											} else {
												var options = [];
											}

											
											
											var newOptions = [];
											options.forEach(function(option) { 
												newOptions.push( { title: option.show, value: option.value } ); 
											});

											atts["options"] = newOptions;

											/**
											 * Return
											 */
											return atts; 

										} }
									],
		"greyd-forms/upload":		[	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "alignment", map: "align" },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ compute: function( atts ) {

											/**
											 * Label
											 */
											if (_.has(atts, "label_font_size")) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 * Tooltip
											 */

											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}

											/**
											 * Block & Custom Styles
											 */
											if (atts['style'] == "custom") {
												atts['custom'] = true;
												atts["customStyles"] = computeCustomInputStyles(atts, 'input_');

												delete atts['style'];
												delete atts['input_color'];
												delete atts['input_background'];
												delete atts['input_font_size'];
												delete atts['input_padding_top'];
												delete atts['input_padding_right'];
												delete atts['input_border_radius'];
												delete atts['input_border'];
												delete atts['input_border_width'];
												delete atts['input_border_top'];
												delete atts['input_border_right'];
												delete atts['input_border_bottom'];
												delete atts['input_border_left'];
												delete atts['input_shadow'];
												delete atts['input_color_hover'];
												delete atts['input_background_hover'];
												delete atts['input_border_hover'];
												delete atts['input_shadow_hover'];
											} else if ( atts['style'] == "sec") {
												delete atts['style'];
												atts['className'] = "is-style-sec";
											} else {
												delete atts['style'];
												atts['className'] = "is-style-prim";
											}
											
											/**
											 * Width
											 */
											if (_.has(atts, "width")) {
												atts["greydStyles"] = {
													'width': atts['width']
												}
												delete atts['width'];
											}
											
											/**
											 * Icon
											 */
											if (_.has(atts, "icon")) {
												atts["icon"] = {
													'content': atts['icon'],
													'position': _.has(atts, "icon_align") && atts["icon_align"] == 'left' ? 'before' : 'after',
													'size': _.has(atts, "icon_size") ? atts["icon_size"] : '100%',
													'margin': _.has(atts, "icon_margin") ? atts["icon_margin"] : '10px',
												}
												delete atts["icon_align"];
												delete atts["icon_size"];
												delete atts["icon_margin"];
											}

											if (_.has(atts, "max") ) {
												var max = atts['max'].replace(/,/g, '.');
												max = lodash.toNumber(max);
												delete atts['max'];
											}
											
											if (_.has(atts, "filetype")) {
												if ( atts['filetype'] == "other" ) {
													atts["file"] = {
														type: atts['filetype'],
														max: max,
														custom: _.has(atts, "custom_filetype") ? atts["custom_filetype"] : ""
													}
													delete atts['custom_filetype'];	
												} else {
													atts["file"] = {
														type: atts['filetype'],
														max: max,
														custom: ""
													}
													delete atts['filetype'];
												}
											}

											// if boxlayout is selected it defaults to normal uploadfield
											if ( _.has(atts, "layout") && atts["layout"] == "upload-box" ) {
												delete atts["height"];
											}

											return atts;
										} }
									],
		"greyd-forms/hidden-field": [ 	{ compute: function( atts ) {

											if ( _.has(atts, "type") && atts["type"] == "dynamic" ) {
												delete atts["type"];
												atts["isDynamic"] = true;
											} else {
												delete atts["type"];
												atts["isDynamic"] = false;
											}

											if ( _.has(atts, "value_cookie") ) {
												atts["value"] = atts["value_cookie"];
												atts["type"] = "cookie";
												delete atts["value_cookie"];
											} else if ( _.has(atts, "value_urlparam") ) {
												atts["value"] = atts["value_urlparam"];
												atts["type"] = "";
												delete atts["value_urlparam"];
											}

											return atts;
										} }
									],
		"greyd-forms/recaptcha":	[	{ key: "hide", map: "hideBanner" }
									],
		"greyd-forms/iconpanels":	[	{ key: "css", map: "inline_css", convert: function(value) { return convertInlineCSS(value) } },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ compute: function( atts ) {


											/**
											 * Label
											 */

											if ( _.has(atts, "label_font_size") ) {
												atts["labelStyles"] = {
													'fontSize': atts['label_font_size']
												}
												delete atts['label_font_size'];
											}

											/**
											 * Tooltip
											 */

											if ( _.has(atts, "tooltip") ) {
												atts['tooltip'] = {
													visible: false,
													content: atts['tooltip']
											  	}
											} else {
												atts['tooltip'] = {
													visible: false,
													content: ""
											  	}
											}

											if (_.has(atts, "justify_content")) {
												switch (atts["justify_content"]) {
													case "flex-justify-start":
														atts["align"] = "left";
														break;
													case "flex-justify-center":
														atts["align"] = "center";
														break;
													case "flex-justify-end":
														atts["align"] = "right";
														break;
												}
												delete atts["justify_content"];
											}

											var wrapperStyles = {};
											wrapperStyles["flexDirection"] = "";
											wrapperStyles["alignItems"] = "center";
											if (_.has(atts, "flex_direction")) {
												var wrapperFlexDirection = atts["flex_direction"].replace(/flex-/g,'');
												wrapperStyles["flexDirection"] = wrapperFlexDirection;
												wrapperStyles["alignItems"] = "flex-start";
												delete atts["flex_direction"];
											}
											if (!_.isEmpty(wrapperStyles)) {
												atts["wrapperStyles"] = wrapperStyles;
											}

											var panelStyles = {
												hover: {},
												active: {}
											}
											
											panelStyles["flexDirection"] = "";

											if (_.has(atts, "panel_flex_direction")) {
												var panelFlexDirection = atts["panel_flex_direction"].replace(/flex-/g,'');
												panelStyles["flexDirection"] = panelFlexDirection;
												delete atts["panel_flex_direction"];
											}

											if (_.has(atts, "bgcolor")) {
												panelStyles['backgroundColor'] = transformColorIntoVar( atts["bgcolor"] );
												delete atts["bgcolor"];
											}
											if (_.has(atts, "bgcolor_hover")) {
												panelStyles["hover"]['backgroundColor'] = transformColorIntoVar( atts["bgcolor_hover"] );
												delete atts["bgcolor_hover"];
											}
											if (_.has(atts, "bgcolor_select")) {
												panelStyles["active"]['backgroundColor'] = transformColorIntoVar( atts["bgcolor_select"] );
												delete atts["bgcolor_select"];
											}
											if (_.has(atts, "textcolor")) {
												panelStyles['color'] = transformColorIntoVar( atts["textcolor"] );
												delete atts["textcolor"];
											}
											if (_.has(atts, "textcolor_hover")) {
												panelStyles["hover"]['color'] = transformColorIntoVar( atts["textcolor_hover"] );
												delete atts["textcolor_hover"];
											}
											if (_.has(atts, "textcolor_select")) {
												panelStyles["active"]['color'] = transformColorIntoVar( atts["textcolor_select"] );
												delete atts["textcolor_select"];
											}
											if (_.has(atts, "opacity")) {
												var opacity = atts["opacity"].replace(/,/g, '.');
												panelStyles["opacity"] = lodash.toNumber(opacity) * 100;
												delete atts["opacity"];
											}
											if (_.has(atts, "font_size")) {
												panelStyles["fontSize"] = atts["font_size"];
												delete atts["font_size"];
											}
											if (_.has(atts, "border_radius")) {
												panelStyles["borderRadius"] = (atts["border_radius"]+" ").repeat(4);
												delete atts["border_radius"];
											}

											if (_.has(atts, "padding_y")) {
												var paddingY = atts["padding_y"];
												delete atts["padding_y"];
											}
											if (_.has(atts, "padding_x")) {
												var paddingX = atts["padding_x"];
												delete atts["padding_x"];
											}
											if (paddingY || paddingX) {
												panelStyles["padding"] = {
													top: paddingY,
													right: paddingX,
													bottom: paddingY,
													left:paddingX
												}
											}
										
											if (!_.isEmpty(panelStyles)) {
												atts["panelStyles"] = panelStyles;
											}

											var imgStyles = {};

											if (_.has(atts, "icon_size")) {
												imgStyles['height'] = atts["icon_size"];
												delete atts["icon_size"];
											}

											if (!_.isEmpty(imgStyles)) {
												atts["imgStyles"] = imgStyles;
											}

											delete atts["line_height"];

											return atts;
										}}
									],
		"greyd-forms/iconpanel":	[  	{ key: "label", map: "title" },
										{ key: "name", map: "value" },
										{ key: "required", map: "required", convert: function(value) { return !!value } },
										{ compute: function( atts ) {
											
											let normalID, hoverID, activeID;
											let iconNormal = {id: -1}, iconHover = {id: -1}, iconActive = {id: -1};

											if ( _.has( atts, "icon") ) {
												normalID = lodash.toNumber( atts['icon'] );
												delete atts["icon"];
											}	
											if ( _.has( atts, "icon_hover") ) {
												hoverID = lodash.toNumber( atts['icon_hover'] );
												delete atts["icon_hover"];
											}
											if ( _.has( atts, "icon_select") ) {
												activeID = lodash.toNumber( atts['icon_select'] );
												delete atts["select"];
											}
												
											if (_.has(greyd.data.media_urls, normalID)) iconNormal = { id: normalID, url: greyd.data.media_urls[normalID].src };
											if (_.has(greyd.data.media_urls, hoverID)) iconHover = { id: hoverID, url: greyd.data.media_urls[hoverID].src };
											if (_.has(greyd.data.media_urls, activeID)) iconActive = { id: activeID, url: greyd.data.media_urls[activeID].src };
											
											let images = {
												normal: iconNormal,
												hover: iconHover,
												active: iconActive,
												current: "normal"
											};

											atts["images"] = images;
											
											return atts;
										}}
			
									],
	};


	// helper
	function convertLink(value) {
		var linkk = value.split('|');
		var link = { };
		for (var i=0; i<linkk.length; i++) {
			var pair = linkk[i].split(':');
			if (pair[0] != "" && typeof pair[1] !== 'undefined') 
				link[pair[0]] = decodeURIComponent(pair[1]);
		}
		return link;
	}
	function convertInlineCSS(value) {
		return value.split('{')[1].split('}')[0].split(';').join(';\n');
	}

	function computeTextColor(param, atts) {
		var color = atts[param];
		if (typeof color === 'undefined') color = "";
		delete atts[param];
		color = color.replace('_', '-'); 
		if (color.indexOf("color-") > -1)
			atts["textColor"] = color; 
		else {
			if (!_.has(atts, "style")) atts["style"] = { "color": { "text": color } };
			else if (!_.has(atts["style"], "color")) atts["style"]["color"] = { "text": color };
			else atts["style"]["color"]["text"] = color;
		}
		return atts;
	}
	function computeColor(param, atts) {
		// var param = pre+'_colorselect';
		var color = atts[param];
		if (typeof color === 'undefined') color = "";
		delete atts[param];
		color = color.replace('_', '-'); 
		return color;
	}

	/**
	 * Transform color value from WPBakery to CSS-variable
	 * @param {string} color 	Variable name (eg. "color_31")
	 * @returns {string}		Value of the color (eg. "var(--wp--preset--color--foreground)")
	 */
	function transformColorIntoVar(color) {
		if ( color.indexOf("color_") === 0 ) {
			color = "var(--"+color.replace("_", "")+")";
		}
		return color;
	}

	/**
	 * Transform custom button styles from WPBakery to CSS-variable
	 * @param {object} atts 	Shortcode attributes
	 * @returns {object}		greydStyles
	 */
	function computeCustomButtonStyles(atts, prefix) {
		atts[prefix+"border_width"] 	= _.has(atts, prefix+"border_width") ? atts[prefix+"border_width"] : '0px';
		atts[prefix+"border"] 			= _.has(atts, prefix+"border") ? transformColorIntoVar(atts[prefix+"border"]) : 'transparent';
		atts[prefix+"border_hover"] 	= _.has(atts, prefix+"border_hover") ? transformColorIntoVar(atts[prefix+"border_hover"]) : 'transparent';

		return {
			color: _.has(atts, prefix+"color") ? transformColorIntoVar(atts[prefix+"color"]) : '',
			background: _.has(atts, prefix+"background") ? transformColorIntoVar(atts[prefix+"background"]) : '',
			fontSize: _.has(atts, prefix+"font_size") ? atts[prefix+"font_size"] : '',
			padding: {
				top: _.has(atts, prefix+"padding_top") ? atts[prefix+"padding_top"] : '',
				right: _.has(atts, prefix+"padding_right") ? atts[prefix+"padding_right"] : '',
				bottom: _.has(atts, prefix+"padding_top") ? atts[prefix+"padding_top"] : '',
				left: _.has(atts, prefix+"padding_right") ? atts[prefix+"padding_right"] : '',
			},
			borderRadius: _.has(atts, prefix+"border_radius") ? atts['link_border_radius']+" ".repeat(4) : '',
			border: {
				top: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_top") ? atts[prefix+"border_top"] : 'none' )+' '+atts[prefix+"border"],
				right: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_right") ? atts[prefix+"border_right"] : 'none' )+' '+atts[prefix+"border"],
				bottom: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_bottom") ? atts[prefix+"border_bottom"] : 'none' )+' '+atts[prefix+"border"],
				left: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_left") ? atts[prefix+"border_left"] : 'none' )+' '+atts[prefix+"border"],
			},
			boxShadow: _.has(atts, prefix+"shadow") ? atts[prefix+"shadow"].replace("_", "-") : '',
			hover: {
				color: _.has(atts, prefix+"color_hover") ? transformColorIntoVar(atts[prefix+"color_hover"]) : '',
				background: _.has(atts, prefix+"background_hover") ? transformColorIntoVar(atts[prefix+"background_hover"]) : '',
				border: {
					top: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_top") ? atts[prefix+"border_top"] : 'none' )+' '+atts[prefix+"border_hover"],
					right: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_right") ? atts[prefix+"border_right"] : 'none' )+' '+atts[prefix+"border_hover"],
					bottom: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_bottom") ? atts[prefix+"border_bottom"] : 'none' )+' '+atts[prefix+"border_hover"],
					left: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_left") ? atts[prefix+"border_left"] : 'none' )+' '+atts[prefix+"border_hover"],
				},
				boxShadow: _.has(atts, prefix+"shadow_hover") ? atts[prefix+"shadow_hover"].replace("_", "-") : '',
			}
		};
	}

	/**
	 * Transform custom input styles from WPBakery to CSS-variable
	 * @param {object} atts 	Shortcode attributes
	 * @returns {object}		greydStyles
	 */
	function computeCustomInputStyles(atts, prefix) {
		atts[prefix+"border_width"] 	= _.has(atts, prefix+"border_width") ? atts[prefix+"border_width"] : '0px';
		atts[prefix+"border"] 			= _.has(atts, prefix+"border") ? transformColorIntoVar(atts[prefix+"border"]) : 'transparent';
		atts[prefix+"border_hover"] 	= _.has(atts, prefix+"border_hover") ? transformColorIntoVar(atts[prefix+"border_hover"]) : 'transparent';

		atts[prefix+"border_width"] = _.includes(atts[prefix+"border_width"], "px") ? atts[prefix+"border_width"] : atts[prefix+"border_width"]+"px";

		return {
			color: _.has(atts, prefix+"color") ? transformColorIntoVar(atts[prefix+"color"]) : '',
			background: _.has(atts, prefix+"background") ? transformColorIntoVar(atts[prefix+"background"]) : '',
			fontSize: _.has(atts, prefix+"font_size") ? atts[prefix+"font_size"] : '',
			padding: {
				top: _.has(atts, prefix+"padding_top") ? atts[prefix+"padding_top"] : '',
				right: _.has(atts, prefix+"padding_right") ? atts[prefix+"padding_right"] : '',
				bottom: _.has(atts, prefix+"padding_top") ? atts[prefix+"padding_top"] : '',
				left: _.has(atts, prefix+"padding_right") ? atts[prefix+"padding_right"] : '',
			},
			borderRadius: _.has(atts, prefix+"border_radius") ? atts[prefix+'border_radius']+"px"+" ".repeat(4) : '',
			border: {
				top: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_top") ? atts[prefix+"border_top"] : 'none' )+' '+atts[prefix+"border"],
				right: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_right") ? atts[prefix+"border_right"] : 'none' )+' '+atts[prefix+"border"],
				bottom: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_bottom") ? atts[prefix+"border_bottom"] : 'none' )+' '+atts[prefix+"border"],
				left: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_left") ? atts[prefix+"border_left"] : 'none' )+' '+atts[prefix+"border"],
			},
			
			hover: {
				color: _.has(atts, prefix+"color_hover") ? transformColorIntoVar(atts[prefix+"color_hover"]) : '',
				background: _.has(atts, prefix+"background_hover") ? transformColorIntoVar(atts[prefix+"background_hover"]) : '',
				border: {
					top: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_top") ? atts[prefix+"border_top"] : 'none' )+' '+atts[prefix+"border_hover"],
					right: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_right") ? atts[prefix+"border_right"] : 'none' )+' '+atts[prefix+"border_hover"],
					bottom: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_bottom") ? atts[prefix+"border_bottom"] : 'none' )+' '+atts[prefix+"border_hover"],
					left: atts[prefix+"border_width"]+' '+( _.has(atts, prefix+"border_left") ? atts[prefix+"border_left"] : 'none' )+' '+atts[prefix+"border_hover"],
				},
			}
		};

	}


	function computeGradient(param, atts) {
		// var param = pre+'_gradient2';
		var gradient = atts[param];
		if (typeof gradient === 'undefined') gradient = "";
		delete atts[param];
		// "scroll|angle	|145|color_11:0		|color_12:100";
		// "scroll|angle	|192|color_31:0		|color_63:100";
		// "scroll|angle	|145|color_11:0		|color_12:100";
		// "scroll|angle	|202|color_11:0.0	|color_12:100";
		// "scroll|bottom	|0  |color_33:0		|color_62:100.0";
		// "scroll|top		|180|color_31:0.0	|color_63:80.6";
		// "scroll|top		|180|color_33:49.99	|color_23:50";
		// "scroll|angle	|29 |color_21:0.0	|color_31:100";
		// "scroll|angle	|29 |color_11:0.0	|color_12:100.0";
		// "scroll|angle	|45 |#f700c1:0		|#990000:47.8	|color_11:100"
		var tmp = gradient.split('|');
		var deg = angle = parseInt(tmp[2]);
		if (angle > 337 || angle < 22) angle = "n";
		else if (angle < 67) angle = "ne";
		else if (angle < 112) angle = "e";
		else if (angle < 157) angle = "se";
		else if (angle < 202) angle = "s";
		else if (angle < 227) angle = "sw";
		else if (angle < 292) angle = "w";
		else if (angle < 337) angle = "nw";
		
		var grad = "";
		var style = "";
		for (var i=3; i<tmp.length; i++) {
			if (grad != "") grad += "-to-";
			if (style != "") style += ", ";
			var col = tmp[i].split(':');
			var color = col[0].replace('_', '-');
			grad += color;
			if (color.indexOf('color-') > -1) {
				// color = "var(--"+color.replace('-', '')+")";
				for (var j=0; j<greyd.data.colors.length; j++) {
					if (greyd.data.colors[j].slug == color) {
						color = greyd.data.colors[j].color;
						break;
					}
				}
			}
			style += color+" "+col[1]+"%";
		}
		grad += "-"+angle;
		
		for (var j=0; j<greyd.data.gradients.length; j++) {
			if (greyd.data.gradients[j].slug == grad) {
				return grad;
			}
		}
		return "linear-gradient("+deg+"deg, "+style+")";
	}
	function computeResponsive(pre, atts, value) {
		if (value == "") value = { sm: "", md: "", lg: "", xl: "", max: "" };
		if (_.has(atts, pre+"_sm")) {
			value.sm = atts[pre+"_sm"];
			value.max = atts[pre+"_sm"];
			delete atts[pre+"_sm"];
		}
		if (_.has(atts, pre+"_md")) {
			value.md = atts[pre+"_md"];
			value.max = atts[pre+"_md"];
			delete atts[pre+"_md"];
		}
		if (_.has(atts, pre+"_lg")) {
			value.lg = atts[pre+"_lg"];
			value.max = atts[pre+"_lg"];
			delete atts[pre+"_lg"];
		}
		if (_.has(atts, pre+"_xl")) {
			value.xl = atts[pre+"_xl"];
			value.max = atts[pre+"_xl"];
			delete atts[pre+"_xl"];
		}
		return value;
	}

	function computeBorder(pre, post, atts) {
		var border = {
			style: "solid",
			width: { top: "1px", right: "1px", bottom: "1px", left: "1px" },
			color: "",
		};
		if (_.has(atts, pre+"_colorselect"+post)) {
			border['color'] = computeColor(pre+"_colorselect"+post, atts);
		}
		if (_.has(atts, pre+"_width"+post)) {
			var width = parseInt(atts[pre+"_width"+post]);
			border['width'] = {
				top: width+'px',
				right: width+'px',
				bottom: width+'px',
				left: width+'px'
			};
			delete atts[pre+"_width"+post];
		}
		var style = "solid";
		if (_.has(atts, pre+"_left"+post) && atts[pre+"_left"+post] != "") {
			if (atts[pre+"_left"+post] == 'none') border['width']['left'] = '0px';
			else style = atts[pre+"_left"+post];
			delete atts[pre+"_left"+post];
		}
		else 
		if (_.has(atts, pre+"_top"+post) && atts[pre+"_top"+post] != "") {
			if (atts[pre+"_top"+post] == 'none') border['width']['top'] = '0px';
			else style = atts[pre+"_top"+post];
			delete atts[pre+"_top"+post];
		}
		if (_.has(atts, pre+"_right"+post) && atts[pre+"_right"+post] != "") {
			if (atts[pre+"_right"+post] == 'none') border['width']['right'] = '0px';
			else style = atts[pre+"_right"+post];
			delete atts[pre+"_right"+post];
		}
		if (_.has(atts, pre+"_bottom"+post) && atts[pre+"_bottom"+post] != "") {
			if (atts[pre+"_bottom"+post] == 'none') border['width']['bottom'] = '0px';
			else style = atts[pre+"_bottom"+post];
			delete atts[pre+"_bottom"+post];
		}
		border["style"] = style;
		return border;
	}

	function computeBackground(pre, atts) {

		if (_.has(atts, pre+"_opacity")) {
			atts["background"]["opacity"] = parseInt(atts[pre+"_opacity"]);
			delete atts[pre+"_opacity"];
		}
		var type = atts[pre+"_type"].replace(pre+'_', '');
		if (type == "gradient2") type = "gradient";
		atts["background"]["type"] = type;
		delete atts[pre+"_type"];
		// color
		if (type == "color" || type == "image" || type == "animation" || type == "video") {
			var col = computeColor(pre+'_colorselect', atts);
			if (col.indexOf('#') === 0) {
				if ( typeof atts["style"] === "undefined" ) atts["style"] = { color: {} };
				atts["style"]["color"]["background"] = col;
			} else {
				atts["backgroundColor"] = col;
			}
		}
		// gradient
		if (type == "gradient") {
			var grad = computeGradient(pre+'_gradient2', atts);
			if (grad.indexOf('linear-gradient') > -1) {
				if ( typeof atts["style"] === "undefined" ) {
					atts["style"] = { color: { gradient: grad } };
				} else {
					atts["style"]["color"]["gradient"] = grad;
				}
			} else {
				atts["gradient"] = grad;
			}
		}
		if (type == "color" || type == "gradient") {
			atts["background"]["type"] = "";
		}
		// image
		if (type == "image") {
			if (_.has(atts, pre+"_image") && atts[pre+"_image"] != "") {
				var media = parseInt(atts[pre+"_image"]);
				delete atts[pre+"_image"];
				if (_.has(greyd.data.media_urls, media)) media = greyd.data.media_urls[media];
				if (typeof media === 'object') {
					var image = atts["background"]["image"];
					image["url"] = media["src"];
					image["id"] = media["id"];
					if (_.has(atts, pre+"_image_size") && atts[pre+"_image_size"] != "") {
						image["size"] = atts[pre+"_image_size"];
						delete atts[pre+"_image_size"];
					}
					if (_.has(atts, pre+"_image_repeat") && atts[pre+"_image_repeat"] != "") {
						image["repeat"] = atts[pre+"_image_repeat"];
						delete atts[pre+"_image_repeat"];
					}
					if (_.has(atts, pre+"_image_position") && atts[pre+"_image_position"] != "") {
						image["position"] = atts[pre+"_image_position"].replace('_', ' ');
						delete atts[pre+"_image_position"];
					}
					atts["background"]["image"] = image;
				}
			}
		}
		// animation
		else if (type == "animation") {
			if (_.has(atts, pre+"_animation_file") && atts[pre+"_animation_file"] != "") {
				var media = parseInt(atts[pre+"_animation_file"]);
				delete atts[pre+"_animation_file"];
				if (_.has(greyd.data.media_urls, media)) media = greyd.data.media_urls[media];
				if (typeof media === 'object') {

					var anim = atts["background"]["anim"];
					anim["url"] = media["src"];
					anim["id"] = media["id"];
					if (_.has(atts, pre+"_animation") && atts[pre+"_animation"] != "") {
						anim["anim"] = atts[pre+"_animation"];
						delete atts[pre+"_animation"];
					}
					if (_.has(atts, pre+"_anim_size")) {
						if (atts[pre+"_anim_size"] == "full") {
							anim["width"] = "100%";
							if (_.has(atts, pre+"_anim_full_position") && atts[pre+"_anim_full_position"] != "") {
								anim["position"] = atts[pre+"_anim_full_position"];
								delete atts[pre+"_anim_full_position"];
							}
						}
						else {
							if (_.has(atts, pre+"_anim_position") && atts[pre+"_anim_position"] != "") {
								anim["position"] = atts[pre+"_anim_position"];
								delete atts[pre+"_anim_position"];
							}
						}
						delete atts[pre+"_anim_size"];
					}
					atts["background"]["anim"] = anim;

				}
			}
		}
		// video
		else if (type == "video") {
			var video = atts["background"]["video"];
			if (_.has(atts, pre+"_video") && atts[pre+"_video"] != "") {
				video['url'] = atts[pre+"_video"];
				delete atts[pre+"_video"];
			}
			if (_.has(atts, pre+"_video_aspect") && atts[pre+"_video_aspect"] != "") {
				video['aspect'] = atts[pre+"_video_aspect"];
				delete atts[pre+"_video_aspect"];
			}
			atts["background"]["video"] = video;
		}
		// scroll
		if (_.has(atts, pre+"_image_scroll") && atts[pre+"_image_scroll"] != "") {
			atts["background"]["scroll"]["type"] = atts[pre+"_image_scroll"];
			delete atts[pre+"_image_scroll"];
			if (atts["background"]["scroll"]["type"].indexOf('parallax') > -1) {
				if (_.has(atts, pre+"_image_parallax") && atts[pre+"_image_parallax"] != "") {
					atts["background"]["scroll"]["parallax"] = parseInt(atts[pre+"_image_parallax"]);
					delete atts[pre+"_image_parallax"];
				}
				if (_.has(atts, pre+"_image_parallax_mobile") && atts[pre+"_image_parallax_mobile"] != "") {
					atts["background"]["scroll"]["parallax_mobile"] = atts[pre+"_image_parallax_mobile"];
					delete atts[pre+"_image_parallax_mobile"];
				}
			}
		}

		return atts;

	}

	function computeDynamicFields(type, atts) {
		// console.log(type);
		// console.log(atts);

		if (_.has(wp.blocks.getBlockType(type), "attributes") && _.has(wp.blocks.getBlockType(type).attributes, "dynamic_fields")) {
			
			var dfields = [];
			// get dynamic_fields of blocktype
			var fields = greyd.dynamic.getFields(type);
			if (fields.length > 0) {
				var del = [];
				for (var i=0; i<fields.length; i++) {
					// get shortcode params of dynamic_field
					if (_.has(fields[i], 'sc_params')) {
						var params = fields[i].sc_params;
						for (var j=0; j<params.length; j++) {
							if (_.has(atts, params[j])) {
								// convert shortcode param to dynamic_field
								if (atts[params[j]] != "") {
									var [ dkey, dtype, dtitle ] = atts[params[j]].split('|');
									var key = fields[i].key;
									var title = decodeURIComponent(dtitle);
									if (dtype == 'vc_link') {
										if (key == 'text' || key == 'content') title += " Text";
										if (key == 'url') title += " URL";
										if (key == 'trigger') title += " Trigger";
									}
									dfields.push( {
										key: key,
										title: decodeURIComponent(title),
										enable: true
									} );
								}
							}
						}
						del.push(...params);
					}
				}
				if (del.length > 0) for (var i=0; i<del.length; i++) {
					delete atts[del[i]];
				}
			}
			if (dfields.length > 0) atts['dynamic_fields'] = dfields;

		}

		return atts;
	}

	function decodeHtml(text) {
		return text
			.replace(/&amp;/g, '&')
			.replace(/&lt;/g , '<')
			.replace(/&gt;/g, '>')
			.replace(/&quot;/g, '"')
			.replace(/&#039;/g, "'")
			.replace(/&nbsp;/g, " ")
			.replace(/\<p\>/g, '')
			.replace(/\<br\>/g, '')
			.replace(/\<\/p\>/g , '');
	}

	 
	/**
	 * parser function (shortcodes -> blocks)
	 * @param {string} content 	RAW Content with shortcodes
	 * @returns {object}		Converted Blocks
	 */
	function parseBlocks(content) {
		// console.log(content);
		var regex_sc = new RegExp(/\[(\[?)(\w+)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)(\[\/\2\]))?)(\]?)/, 'g');
		var regex_inner_sc = new RegExp(/\[(\[?)(\w+)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)(\[\/\2\]))?)(\]?)/, 'g');
		var regex_atts = new RegExp(/([\w-]+)="([^"]*)"/, 'g');

		var shortcodeBlocks = [];
		var shortcode = regex_sc.exec(content);
		while (shortcode != null) {

			// console.log(shortcode);
			var sc_raw = shortcode[0];
			// shortcode[1] opening tag '['
			var sc_name = shortcode[2];
			var sc_atts = shortcode[3];
			// shortcode[4] unused
			var sc_inner = shortcode[5];
			// shortcode[6] full closing tag '[/{sc_name}]'
			// shortcode[7] closing tag ']'

			if ( sc_name == "vc_form_condition" ) {
				// console.log( "extern processing of conditional fields");
				shortcode = regex_sc.exec(content);
				continue;
			}

			// vars
			var map_sc = null;
			var map_atts = [];
			var newBlock = null;

			// get shortcode map for new block type
			if (_.has(shortcodeMap, sc_name) && _.has(shortcodeMap[sc_name], 'block')) {
				// get map for new block type
				// console.log(sc_name);
				var map_sc = shortcodeMap[sc_name];
				if (_.has(attsMap, map_sc['block'])) map_atts = attsMap[map_sc['block']];
			}
			else if (_.has(shortcodeMapVC, sc_name) && _.has(shortcodeMapVC[sc_name], 'block')) {
				// get map for new block type
				// console.log(sc_name);
				var map_sc = shortcodeMapVC[sc_name];
				if (_.has(attsMapVC, map_sc['block'])) map_atts = attsMapVC[map_sc['block']];
			}

			// check if block type is registered
			// console.log(map_sc['block']);
			if (!wp.blocks.getBlockType(map_sc['block']) ) {
				map_sc = null;
			}

			// parse shortcode to block
			if (map_sc != null) {
				// get new block type
				// console.log(sc_name);
				var block_name = map_sc['block'];
				
				// extract inner blocks
				var innerText = "";
				var innerBlocks = [];
				if (sc_inner != null && sc_inner != "") {
					innerText = sc_inner.trim();
					if (sc_name == "vc_gutenberg") {
						// console.log(innerText);
						// console.log(decodeURIComponent(innerText));
						innerText = decodeHtml(innerText);
						// console.log(innerText);
						// innerBlocks = [ innerText ];
						innerBlocks = wp.blocks.rawHandler({
							HTML: decodeURIComponent(innerText)
						});
					}
					else {
						var innerShortcode = regex_inner_sc.exec(innerText);
						while (innerShortcode != null) {
							// console.log(innerShortcode);
							// matched text: innerShortcode[0]
							// capturing group n: innerShortcode[n]
							// innerBlocks = innerBlocks.concat(wp.blocks.rawHandler({
							// 	HTML: innerShortcode[0].trim()
							// }));
							innerBlocks = innerBlocks.concat(parseBlocks(innerShortcode[0].trim()));
							// next inner shortcode
							innerShortcode = regex_inner_sc.exec(innerText);
						}
					}
				}

				// extract attributes
				var atts_raw = {};
				var block_atts = {};
				if (_.has(map_sc, 'atts'))
					block_atts = JSON.parse(JSON.stringify(map_sc['atts']));
				if (sc_atts != null && sc_atts != "") {
					var atts_sc = sc_atts;
					// console.log(atts_sc);
					var attribute = regex_atts.exec(atts_sc);
					while (attribute != null) {
						// console.log(attribute);
						var key = attribute[1];
						var value = attribute[2];
						atts_raw[key] = value;
						// skip layout atts
						if (key.indexOf("sub_seperator_") !== 0 && 
							key.indexOf("seperator_") !== 0 && 
							key.indexOf("new_line_") !== 0 &&
							key.indexOf("info_") !== 0) {
							// map and convert and transform attribute
							for (var j=0; j<map_atts.length; j++) {
								if (key == map_atts[j]['key']) {
									var map = map_atts[j];
									// if (_.has(map, 'transform') && value == map.transform.value)
									// 	block_name = map.transform.block;
									if (_.has(map, 'map')) 
										key = map.map;
									if (_.has(map, 'convert')) 
										value = map.convert(value);
								}
							}
							block_atts[key] = value;
						}
						// next attribute
						attribute = regex_atts.exec(atts_sc);
					}
				}
				// if (block_name == "greyd/posts") 
				// 	console.log(atts_raw);
				// default and fill attributes
				for (var j=0; j<map_atts.length; j++) {
					var map = map_atts[j];
					if (_.has(map, 'transform'))
						block_name = map.transform(block_atts);
					if (_.has(map, 'default') && !_.has(block_atts, map.key))
						block_atts[map.key] = map.default;
					if (_.has(map, 'fill') && innerText != "")
						block_atts[map.key] = map.fill(innerText);
					if (_.has(map, 'inner'))
						innerBlocks = map.inner(block_atts, innerBlocks);
					if (_.has(map, 'raw'))
						block_atts['raw'] = JSON.stringify(atts_raw, null, 2);
					if (_.has(map, 'compute'))
						block_atts = map.compute(block_atts);
					if (_.has(map, 'computeConditional') && sc_name == "vc_form_conditional")
						innerBlocks = map.computeConditional(block_atts, innerText);
				}
				
				block_atts = computeDynamicFields(block_name, block_atts);
				// if (block_name == "greyd/posts") 
					// console.log(block_atts);

				// create new block
				// console.log(block_name);
				// console.log(block_atts);
				// console.log(innerBlocks);
				
				newBlock = wp.blocks.createBlock( block_name, block_atts, innerBlocks );
				// console.log(newBlock);
				// check for wrapper block
				if (_.has(map_sc, 'wrapper')) {
					var wrapper = map_sc['wrapper'];
					var wrapperAtts = {};
					map_atts = [];
					if (_.has(attsMap, wrapper)) map_atts = attsMap[wrapper];
					Object.keys(block_atts).forEach(function(key, index) {
						var k = "";
						var v = block_atts[key];
						for (var j=0; j<map_atts.length; j++) {
							if (key == map_atts[j]['key']) {
								var map = map_atts[j];
								if (_.has(map, 'map')) 
									k = map.map;
								if (_.has(map, 'convert')) 
									v = map.convert(v);
							}
						}
						if (k != "") wrapperAtts[k] = v;
					});
					newBlock = wp.blocks.createBlock( wrapper, wrapperAtts, [ newBlock ] );
				}
			}
			else {
				// add new paragraph block for unknown shortcode
				console.log("found unknown shortcode: "+sc_name);
				newBlock = wp.blocks.createBlock( 'core/paragraph', { content: sc_raw } );
			}
			// add new block
			if (newBlock != null) shortcodeBlocks.push(newBlock);
			// next shortcode
			shortcode = regex_sc.exec(content);
		}
		// console.log(shortcodeBlocks);
		// return new block(s)
		if (shortcodeBlocks.length == 1) return shortcodeBlocks[0];
		return wp.blocks.createBlock( 'core/group', { }, shortcodeBlocks );
	}

	// transform hook
	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/transform',
		function(settings, name) {
			// console.log(settings.supports);
			if (name == 'core/shortcode') {
				settings.transforms = {
					from: [
						{
							type: 'raw',
							isMatch: function( node ) {
								// console.log(node);
								if (node.nodeName == "P") {
									// check for shortcode
									if (/^\[[a-zA-Z_-]+[\s\]]/.test(node.innerHTML)) return true;
									// check if ignore flag is set
									if (_.has(node, 'ignore')) return true;
								}
								return false;
							},
							transform: function( node ) {
								// console.log(node);
								// check if ignore flag is set
								if (_.has(node, 'ignore')) {
									// console.log("already processed content");
									return false;
								}
								else {
									// console.log("found shortcode");
									var content = node.innerHTML.trim();
									var sibling = node.nextElementSibling;
									while (sibling != null) {
										// console.log(sibling.innerHTML.trim());
										content += "\n\n"+sibling.innerHTML.trim(); // add content
										sibling.ignore = true; // set ignore flag
										// select next sibling
										sibling = sibling.nextElementSibling;
									}
									return parseBlocks(content);
								}
							},
							priority: 0
						},
					]
				};
				// console.log(settings);
			}
			
			return settings;
		}
	);

} )(
	window.wp
);
