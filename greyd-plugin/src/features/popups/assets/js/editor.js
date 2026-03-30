/**
 * Javascript File for Popups Design Setup.
 * This file is loaded in the editor.
 */

( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Render function.
	 * @param {object} atts	Compose function atts.
	 * 		@property {string} postType			The post_type of the current post.
	 * 		@property {object} popupDesign		All current Meta values of this post.
	 * 		@property {function} setPopupDesign	Function to update Meta values.
	 */
	var greydPopups = function(atts) {

		// console.log( atts )

		/*
		=======================================================================
			Data
		=======================================================================
		*/

		/**
		 * Convert the Popup Design Object.
		 * Old Data (pre Blocks) will be converted into a version 2 Object.
		 * 
		 * @param {object} original		Popup Design values Object from Database.
		 * @returns {object} converted	Converted Design values Object version 2.
		 */
		var convertValues = function(original) {

			// already converted to v2
			if (_.has(original, 'version') && original['version'] == 2) {

				// deep copy
				let converted = { ...original };

				/**
				 * @since 1.5.6 Fix missing object values.
				 * 
				 * Somehow the values for mobile & hover are no proper Objects.
				 * Instead of a value like:   { position: 'top left' }
				 * they are set as:           [ position: 'top left' ]
				 * We convert them by making a deep copy of the values.
				 */
				if ( _.has( converted.layout, 'mobile' ) ) {
					converted.layout.mobile = { ...converted.layout.mobile };
				}
				if ( _.has( converted.design, 'hover' ) ) {
					converted.design.hover = { ...converted.design.hover };
				}

				// fix '100' values
				if ( converted.layout?.size?.height == '100' ) converted.layout.size.height = '100%';
				if ( converted.layout?.mobile?.size?.height == '100' ) converted.layout.mobile.size.height = '100%';
				if ( converted.layout?.size?.width == '100' ) converted.layout.size.width = '100%';
				if ( converted.layout?.mobile?.size?.width == '100' ) converted.layout.mobile.size.width = '100%';
				// fix empty and 'nullpx' values
				if ( converted.layout?.margin ) {
					if ( _.isEmpty(converted.layout.margin) ) converted.layout.margin = {};
					['top', 'bottom', 'left', 'right'].forEach( (side) => {
						if ( !converted.layout.margin[side] ) converted.layout.margin[side] = '0px';
						else if ( converted.layout.margin[side].indexOf('null') === 0 )  converted.layout.margin[side] = '0px';
					} );
					if (  converted.layout.mobile?.margin ) {
						if ( _.isEmpty(converted.layout.mobile.margin) ) converted.layout.mobile.margin = {};
						['top', 'bottom', 'left', 'right'].forEach( (side) => {
							if ( !converted.layout.mobile.margin[side] ) converted.layout.mobile.margin[side] = '0px';
							else if ( converted.layout.mobile.margin[side].indexOf('null') === 0 )  converted.layout.mobile.margin[side] = '0px';
						} );
					}
				}
				if ( converted.layout?.padding ) {
					if ( _.isEmpty(converted.layout.padding) ) converted.layout.padding = {};
					['top', 'bottom', 'left', 'right'].forEach( (side) => {
						if ( !converted.layout.padding[side] ) converted.layout.padding[side] = '0px';
						else if ( converted.layout.padding[side].indexOf('null') === 0 )  converted.layout.padding[side] = '0px';
					} );
					if (  converted.layout.mobile?.padding ) {
						if ( _.isEmpty(converted.layout.mobile.padding) ) converted.layout.mobile.padding = {};
						['top', 'bottom', 'left', 'right'].forEach( (side) => {
							if ( !converted.layout.mobile.padding[side] ) converted.layout.mobile.padding[side] = '0px';
							else if ( converted.layout.mobile.padding[side].indexOf('null') === 0 )  converted.layout.mobile.padding[side] = '0px';
						} );
					}
				}

				// console.log(converted);
				return converted;
			}

			/**
			 * Convert single Value.
			 * @param {string} value 	Single old Value.
			 * @returns {sting} value	New Value.
			 */
			const convertValue = function(value) {
				
				if (value.indexOf('color_') == 0) {
					if ( greyd.data?.is_greyd_classic ) return 'var(--wp--preset--color--'+value.replace('_', '-')+')';
					else {
						if (value == 'color_11') return 'var(--wp--preset--color--primary)';	 // primary
						if (value == 'color_12') return 'var(--wp--preset--color--secondary)';	 // secondary
						if (value == 'color_13') return 'var(--wp--preset--color--tertiary)';	 // alternate
						if (value == 'color_31') return 'var(--wp--preset--color--foreground)';  // very dark
						if (value == 'color_21') return 'var(--wp--preset--color--dark)';		 // dark
						if (value == 'color_32') return 'var(--wp--preset--color--mediumdark)';  // slightly dark
						if (value == 'color_22') return 'var(--wp--preset--color--mediumlight)'; // slightly bright
						if (value == 'color_33') return 'var(--wp--preset--color--base)';		 // bright
						if (value == 'color_23') return 'var(--wp--preset--color--background)';  // very bright
						if (value == 'color_61') return 'var(--wp--preset--color--darkest)';	 // black
						if (value == 'color_62') return 'var(--wp--preset--color--lightest)';	 // white
						if (value == 'color_63') return 'var(--wp--preset--color--transparent)'; // transparent
					}
				}
				
				if (value == 'tl') return 'top left';		// oben links
				if (value == 'tc') return 'top center';		// oben mitte
				if (value == 'tr') return 'top right';		// oben rechts
				if (value == 'ml') return 'center left';	// mitte links
				if (value == 'mc') return 'center center';	// mitte mitte
				if (value == 'mr') return 'center right';	// mitte rechts
				if (value == 'bl') return 'bottom left';	// unten links
				if (value == 'bc') return 'bottom center';	// unten mitte
				if (value == 'br') return 'bottom right';	// unten rechts

				// width/height
				if (value == '100') return '100%';

				// align
				if (value == 'top') return 'start';
				if (value == 'middle') return 'center';
				if (value == 'bottom') return 'end';

				// shadow
				if (value.indexOf('+') > 0) {
					value = value.split('+');
					value[4] = convertValue(value[4]);
					return value.join(' ');
				}

				return value;

			};

			// make basic v2 Object from old Values
			let converted = {
				'version': 2,
				// layout
				'layout': {
					'position': _.has(original, 'position') ? convertValue(original['position']) : items['layout']['position']['default'],
					'size': { 
						'width': _.has(original, 'width') ? ( original['width'] == 'custom' ? original['width_custom'] : convertValue(original['width']) ) : items['layout']['size']['default']['width'], 
						'height': _.has(original, 'height') ? ( original['height'] == 'custom' ? original['height_custom'] : convertValue(original['height']) ) : items['layout']['size']['default']['height'], 
						'align': _.has(original, 'align') ? convertValue(original['align']) : items['layout']['size']['default']['align'], 
					},
					'padding': { 
						'top': _.has(original, 'padding_tb') ? original['padding_tb']+'px' : items['layout']['padding']['default']['top'], 
						'left': _.has(original, 'padding_lr') ? original['padding_lr']+'px' : items['layout']['padding']['default']['left'], 
						'right': _.has(original, 'padding_lr') ? original['padding_lr']+'px' : items['layout']['padding']['default']['right'], 
						'bottom' : _.has(original, 'padding_tb') ? original['padding_tb']+'px' : items['layout']['padding']['default']['bottom'] 
					},
					'margin': { 
						'top': _.has(original, 'margin_tb') ? original['margin_tb']+'px' : items['layout']['margin']['default']['top'], 
						'left': _.has(original, 'margin_lr') ? original['margin_lr']+'px' : items['layout']['margin']['default']['left'], 
						'right': _.has(original, 'margin_lr') ? original['margin_lr']+'px' : items['layout']['margin']['default']['right'], 
						'bottom' : _.has(original, 'margin_tb') ? original['margin_tb']+'px' : items['layout']['margin']['default']['bottom'] 
					},
					'mobile': {}
				},
				// design
				'design': {
					'colors': {
						'text': _.has(original, 'text_color') ? convertValue(original['text_color']) : items['design']['colors']['default']['text'],
						'headline': _.has(original, 'head_color') ? convertValue(original['head_color']) : items['design']['colors']['default']['headline'],
						'background': _.has(original, 'bg_color') ? convertValue(original['bg_color']) : items['design']['colors']['default']['background'],
					},
					'border_radius': { 
						'topLeft': _.has(original, 'border_radius') ? original['border_radius']+'px' : items['design']['border_radius']['default']['topLeft'], 
						'topRight' : _.has(original, 'border_radius') ? original['border_radius']+'px' : items['design']['border_radius']['default']['topRight'], 
						'bottomRight': _.has(original, 'border_radius') ? original['border_radius']+'px' : items['design']['border_radius']['default']['bottomRight'], 
						'bottomLeft' : _.has(original, 'border_radius') ? original['border_radius']+'px' : items['design']['border_radius']['default']['bottomLeft']
					},
					'border': _.has(original, 'border_enable') && original['border_enable'] == 'true' ? { 
						'width': _.has(original, 'border_width') ? original['border_width']+'px' : items['design']['border']['default']['width'], 
						'style': _.has(original, 'border_style') ? original['border_style'] : items['design']['border']['default']['style'], 
						'color': _.has(original, 'border_color') ? convertValue(original['border_color']) : items['design']['border']['default']['color']
					} : items['design']['border']['default'],
					'shadow': _.has(original, 'shadow_enable') && original['shadow_enable'] == 'true' ? convertValue(original['shadow']) : items['design']['shadow']['default'],
					'hover': {}
				},
				// more
				'more': {
					'overlay_enable': _.has(original, 'overlay_enable') ? original['overlay_enable'] : items['more']['overlay_enable']['default'],
					'overlay_color': _.has(original, 'overlay_color') ? convertValue(original['overlay_color']) : items['more']['overlay_color']['default'],
					'overlay_opacity': _.has(original, 'overlay_opacity') ? original['overlay_opacity']/100 : items['more']['overlay_opacity']['default'],
					'overlay_effect': {
						'effect': _.has(original, 'overlay_effect') ? original['overlay_effect'] : items['more']['overlay_effect']['default']['effect'],
						'blur': _.has(original, 'overlay_effect_blur') ? original['overlay_effect_blur'] : items['more']['overlay_effect']['default']['blur'],
						'brightness': _.has(original, 'overlay_effect_brightness') ? original['overlay_effect_brightness'] : items['more']['overlay_effect']['default']['brightness'],
						'contrast': _.has(original, 'overlay_effect_contrast') ? original['overlay_effect_contrast'] : items['more']['overlay_effect']['default']['contrast'],
						'grayscale': _.has(original, 'overlay_effect_grayscale') ? original['overlay_effect_grayscale'] : items['more']['overlay_effect']['default']['grayscale'],
						'hue-rotate': _.has(original, 'overlay_effect_hue-rotate') ? original['overlay_effect_hue-rotate'] : items['more']['overlay_effect']['default']['hue-rotate'],
						'invert': _.has(original, 'overlay_effect_invert') ? original['overlay_effect_invert'] : items['more']['overlay_effect']['default']['invert'],
						'saturate': _.has(original, 'overlay_effect_saturate') ? original['overlay_effect_saturate'] : items['more']['overlay_effect']['default']['saturate'],
						'sepia': _.has(original, 'overlay_effect_sepia') ? original['overlay_effect_sepia'] : items['more']['overlay_effect']['default']['sepia'],
					},
					'overlay_close': _.has(original, 'overlay_close') ? original['overlay_close'] : items['more']['overlay_close']['default'],
					'overlay_noscroll': _.has(original, 'overlay_noscroll') ? original['overlay_noscroll'] : items['more']['overlay_noscroll']['default'],
					'anim_type': _.has(original, 'anim_type') ? original['anim_type'] : items['more']['anim_type']['default'],
					'anim_body' : { 'transform': 'none', 'origin': 'center center' },
					'anim_time': _.has(original, 'anim_time') ? original['anim_time'] : items['more']['anim_time']['default'],
					'overflow': _.has(original, 'overflow') ? original['overflow'] : items['more']['overflow']['default'],
					'css_class': _.has(original, 'css_class') ? original['css_class'] : items['more']['css_class']['default'],
				}
			};

			// convert layout mobile
			if (_.has(original, 'mobile_enable') && original['mobile_enable'] == 'true') {
				if (_.has(original, 'mobile_position')) converted['layout']['mobile']['position'] = convertValue(original['mobile_position']);
				if (_.has(original, 'mobile_width') || _.has(original, 'mobile_height') || _.has(original, 'mobile_align')) {
					converted['layout']['mobile']['size'] = {};
					if (_.has(original, 'mobile_width')) {
						converted['layout']['mobile']['size']['width'] = original['mobile_width'] == 'custom' ? original['mobile_width_custom'] : convertValue(original['mobile_width']);
					}
					if (_.has(original, 'mobile_height')) {
						converted['layout']['mobile']['size']['height'] = original['mobile_height'] == 'custom' ? original['mobile_height_custom'] : convertValue(original['mobile_height']);
					}
					if (_.has(original, 'mobile_align')) converted['layout']['mobile']['size']['align'] = convertValue(original['mobile_align']);
				}
				if (_.has(original, 'mobile_padding_tb') || _.has(original, 'mobile_padding_lr')) {
					converted['layout']['mobile']['padding'] = {};
					if (_.has(original, 'mobile_padding_tb')) {
						converted['layout']['mobile']['padding']['top'] = original['mobile_padding_tb']+'px';
						converted['layout']['mobile']['padding']['bottom'] = original['mobile_padding_tb']+'px';
					}
					if (_.has(original, 'mobile_padding_lr')) {
						converted['layout']['mobile']['padding']['left'] = original['mobile_padding_lr']+'px';
						converted['layout']['mobile']['padding']['right'] = original['mobile_padding_lr']+'px';
					}
				}
				if (_.has(original, 'mobile_margin_tb') || _.has(original, 'mobile_margin_lr')) {
					converted['layout']['mobile']['margin'] = {};
					if (_.has(original, 'mobile_margin_tb')) {
						converted['layout']['mobile']['margin']['top'] = original['mobile_margin_tb']+'px';
						converted['layout']['mobile']['margin']['bottom'] = original['mobile_margin_tb']+'px';
					}
					if (_.has(original, 'mobile_margin_lr')) {
						converted['layout']['mobile']['margin']['left'] = original['mobile_margin_lr']+'px';
						converted['layout']['mobile']['margin']['right'] = original['mobile_margin_lr']+'px';
					}
				}
			}
			// convert design hover
			if (_.has(original, 'hover_enable') && original['hover_enable'] == 'true') {
				if (_.has(original, 'hover_text_color') || _.has(original, 'hover_head_color') || _.has(original, 'hover_bg_color')) {
					converted['design']['hover']['colors'] = {};
					if (_.has(original, 'hover_text_color')) converted['design']['hover']['colors']['text'] = convertValue(original['hover_text_color']);
					if (_.has(original, 'hover_head_color')) converted['design']['hover']['colors']['headline'] = convertValue(original['hover_head_color']);
					if (_.has(original, 'hover_bg_color')) converted['design']['hover']['colors']['background'] = convertValue(original['hover_bg_color']);
				}
				if (_.has(original, 'border_enable') && original['border_enable'] == 'true' && _.has(original, 'hover_border_color')) {
					converted['design']['hover']['border'] = { 
						'color': convertValue(original['hover_border_color'])
					}
				}
				if (_.has(original, 'shadow_enable') && original['shadow_enable'] == 'true' && _.has(original, 'hover_shadow')) {
					converted['design']['hover']['shadow'] = convertValue(original['hover_shadow']);
				}
			}

			// console.log(converted);
			return converted;

		};

		/**
		 * Basic definition of all Popup Design Items.
		 * Including type, label and default Value.
		 */
		var colorForeground = 'var(--wp--preset--color--foreground)';
		var colorBackground = 'var(--wp--preset--color--background)';
		// if (typeof greyd.data !== 'undefined') {
		if ( greyd.data?.is_greyd_classic ) {
			colorForeground = 'var(--wp--preset--color--color-21)';
			colorBackground = 'var(--wp--preset--color--color-23)';
		}
		const items = {
			'layout' : {
				'position' : { 
					'type'          : 'matrix', 
					'param_name'    : 'position', 
					'label'         : __('Position', 'greyd_hub'), 
					'default'       : 'center center', 
					'mobile'		: true
				},
				'size' : {
					'type'          : 'size', 
					'param_name'    : 'size', 
					'default'       : { 'width': 'auto', 'height': 'auto', 'align': 'center', }, 
					'mobile'		: true
				},
				'padding' : { 
					'type'          : 'dimension', 
					'param_name'    : 'padding', 
					'label'         : __('Padding', 'greyd_hub'),
					'default'       : { 'top': '1em', 'left' : '1em', 'right': '1em', 'bottom' : '1em' }, 
					'mobile'		: true
				},
				'margin' : { 
					'type'          : 'dimension', 
					'param_name'    : 'margin', 
					'label'         : __("Space", 'greyd_hub'),
					'default'       : { 'top': '1.5em', 'left' : '1.5em', 'right': '1.5em', 'bottom' : '1.5em' }, 
					'mobile'		: true
				},
			},
			'design' : {
				'colors' : { 
					'type'          : 'colors', 
					'param_name'    : 'colors', 
					'default'       : {
						'text'			: colorForeground,
						'headline'		: colorForeground,
						'background'	: colorBackground,
					},
					'hover'			: true
				},
				'border_radius' : { 
					'type'          : 'border_radius', 
					'param_name'    : 'border_radius', 
					'label'         : __("Border radius", 'greyd_hub'), 
					'default'       : { 'topLeft': '5px', 'topRight' : '5px', 'bottomRight': '5px', 'bottomLeft' : '5px' }, 
					'hover'			: true
				},
				'border' : {
					'type'          : 'border', 
					'param_name'    : 'border', 
					'label'         : __("Border", 'greyd_hub'), 
					'default'       : { 'width': '0px', 'style': 'solid', 'color': colorForeground }, 
					'hover'			: true
				},
				'shadow' : { 
					'type'          : 'shadow', 
					'param_name'    : 'shadow', 
					'label'         : __("Drop shadow", 'greyd_hub'), 
					'default'       : 'none',
					'hover'			: true
				},
			},
			'more' : {
				'overlay_enable' : { 
					'type'          : 'checkbox', 
					'param_name'    : 'overlay_enable', 
					'head'          : __("Page overlay", 'greyd_hub'), 
					'label'         : __("Activate page overlay", 'greyd_hub'), 
					'default'       : 'true', 
				},
				'overlay_color' : { 
					'type'          : 'colorselect', 
					'param_name'    : 'overlay_color', 
					'label'         : __("Color", 'greyd_hub'), 
					'default'       : 'var(--wp--preset--color--black)',
					'dependency'  : [
						{ 'element' : 'overlay_enable', 'value' : [ 'true' ] },
					], 
				},
				'overlay_opacity' : { 
					'type'          : 'range', 
					'param_name'    : 'overlay_opacity', 
					'label'         : __("Opacity", 'greyd_hub'),
					'input_attrs'   : { 'min' : 0, 'max' : 1, 'step' : 0.01 }, 
					'default'       : '0.25',
					'dependency'  : [
						{ 'element' : 'overlay_enable', 'value' : [ 'true' ] },
					], 
				},
				'overlay_effect' : { 
					'type'          : 'effect', 
					'param_name'    : 'overlay_effect', 
					'label'         : __("Overlay effect", 'greyd_hub'),
					'description'   : __("Attention: Overlay effects cost a lot of resources and can appear \"jerky\" depending on the device.", 'greyd_hub'),
					'default'       : {
						'effect'		: 'blur',
						'blur'			: '10',
						'brightness'	: '110',
						'contrast'		: '110',
						'grayscale'		: '10',
						'hue-rotate'	: '10',
						'invert'		: '10',
						'saturate'		: '110',
						'sepia'			: '10',
					},
					'dependency'  : [
						{ 'element' : 'overlay_enable', 'value' : [ 'true' ] },
					], 
				},
				'overlay_close' : { 
					'type'          : 'checkbox', 
					'param_name'    : 'overlay_close', 
					'label'         : __("Close pop-up on overlay click", 'greyd_hub'), 
					'default'       : 'true',
					'dependency'  : [
						{ 'element' : 'overlay_enable', 'value' : [ 'true' ] },
					], 
				},
				'overlay_noscroll' : { 
					'type'          : 'checkbox', 
					'param_name'    : 'overlay_noscroll', 
					'label'         : __("Disable scrolling on page", 'greyd_hub'), 
					'default'       : 'false',
					'dependency'  : [
						{ 'element' : 'overlay_enable', 'value' : [ 'true' ] },
					], 
				},

				'anim_type' : { 
					'type'          : 'dropdown', 
					'param_name'    : 'anim_type', 
					'head'          : __("Animation", 'greyd_hub'), 
					'label'         : __("Pop-up animation type", 'greyd_hub'), 
					'options'       : { 
						'none'			: __("No animation", 'greyd_hub'),
						'fade'			: __('Fade in', 'greyd_hub'), 
						'scale'			: __('Scale up', 'greyd_hub'), 
						'slide_top'		: __("Slide in top", 'greyd_hub'), 
						'slide_right'	: __("Slide in right", 'greyd_hub'), 
						'slide_bottom'	: __("Slide in bottom", 'greyd_hub'), 
						'slide_left'	: __("Slide in left", 'greyd_hub'), 
						'flip_vertical' : __("Flip in (vertically)", 'greyd_hub'), 
						'flip_horizontal' : __("Flip in (horizontally)", 'greyd_hub'), 
						'flip_3d'		: __('Flip in (3D)', 'greyd_hub'), 
					}, 
					'default'       : 'fade', 
				},
				'anim_body' : { 
					'type'          : 'transform', 
					'param_name'    : 'anim_body', 
					'label'         : __("Body transformation", 'greyd_hub'),
					'default'       : { 'transform' : 'none', 'origin': 'center center' }
				},

				'anim_time' : { 
					'type'          : 'range', 
					'param_name'    : 'anim_time', 
					'label'         : { 'lbl' : __("Duration", 'greyd_hub'), 'unit' : 'ms' }, 
					'input_attrs'   : { 'min' : 0, 'max' : 5000, 'step' : 1 }, 
					'default'       : '300',
					'dependency'  : [
						{ 'element' : 'anim_type', 'not_value' : [ 'none' ] },
					], 
				},

				'overflow' : { 
					'type'          : 'dropdown', 
					'param_name'    : 'overflow', 
					'head'          : __("More settings", 'greyd_hub'), 
					'label'         : __("Overflow within pop-up", 'greyd_hub'), 
					'options'       : { 
						'auto'			: __("Scrollable", 'greyd_hub'), 
						'visible'		: __("Visible", 'greyd_hub'), 
						'hidden'		: __("Not visible", 'greyd_hub'),
					}, 
					'default'    : 'auto' 
				},
				'css_class' : { 
					'type'          : 'input', 
					'param_name'    : 'css_class', 
					'label'         : __("CSS class", 'greyd_hub'), 
					'placeholder'   : __("Separate class names with space", 'greyd_hub'), 
					'default'       : ''  
				},
			},
		};
		
		/**
		 * Values State.
		 * Gets the original (and converted) Popup Design Object and hold all changes.
		 */
		var [ values, setValue ] = wp.element.useState( convertValues(atts.popupDesign) );

		// console.log( atts.popupDesign );
		// console.log( atts.popupDesign.layout.mobile, typeof atts.popupDesign.layout.mobile );

		// console.log(values);

		/**
		 * Update a single Value in the Design Object.
		 * Called from each component and triggers the postmeta change for saving.
		 * 
		 * @param {array} slugs 	The Object Properties Path of the new Value.
		 * 		@property [0] type	layout, design or more.
		 * 		@property [1] slug	actual value slug.
		 * 		@property [2] sec	hover, mobile or undefined.
		 * @param {any} value 	The new Value.
		 */
		var updateValue = function(slugs, value) {

			// console.log(slugs);
			// console.log(value);
			var [ type, slug, sec ] = slugs;
			
			// update preview styles
			updateStyle(slugs, value);

			// make new value
			var newValue = { ...values };
			if (!_.has(newValue, type)) newValue[type] = {};
			if (typeof sec !== 'undefined') {
				// mobile or hover
				if (!_.has(newValue[type], sec)) newValue[type][sec] = {};
				newValue[type][sec][slug] = value;
			}
			else {
				newValue[type][slug] = value;
			}

			// add timestamp to make sure the value is saved
			// https://github.com/WordPress/gutenberg/issues/25668
			newValue["modified"] = Date.now();

			// set postmeta
			atts.setPopupDesign( newValue );
			// set state value
			setValue( newValue );

			// console.log( 'updateValue', newValue );

			// make other script dirty
			greyd.popup.changed = true;
		};

		/*
		=======================================================================
			Preview styles
		=======================================================================
		*/

		/**
		 * Collect all css vars to preview the Popup from the Design Object.
		 * 
		 * @returns {object} styleVars	Collection of array with all css vars.
		 * 		@property {string[]} normal	All basic css vars.
		 * 		@property {string[]} mobile	All mobile css vars.
		 * 		@property {string[]} hover	All hover css vars.
		 */
		var makeStyleVars = function() {
			
			var styleVars = {
				'normal': [],
				'mobile': [],
				'hover': []
			}

			// cycle through all items
			Object.keys(items).forEach(function(type) {

				var varSlug = '--popup--'+type;
				var varSlugMobile = varSlug+'--mobile';
				var varSlugHover = varSlug+'--hover';

				Object.keys(items[type]).forEach(function(slug) {

					var item = items[type][slug];
					// console.log(item);

					// get value or default
					var value = _.has(values[type], slug) ? values[type][slug] : item['default'];

					// position
					if (slug == 'position') {
						var [ align, justify ] = value.split(' ');
						if (align == 'top') align = 'start';
						if (align == 'bottom') align = 'end';
						styleVars['normal'].push(varSlug+'--align: '+align+';');
						styleVars['normal'].push(varSlug+'--justify: '+justify+';');
						if (_.has(values[type], 'mobile') && _.has(values[type]['mobile'], slug)) {
							var [ align, justify ] = values[type]['mobile'][slug].split(' ');
							if (align == 'top') align = 'start';
							if (align == 'bottom') align = 'end';
							styleVars['mobile'].push(varSlugMobile+'--align: '+align+';');
							styleVars['mobile'].push(varSlugMobile+'--justify: '+justify+';');
						}
						else {
							styleVars['mobile'].push(varSlugMobile+'--align: var('+varSlug+'--align);');
							styleVars['mobile'].push(varSlugMobile+'--justify: var('+varSlug+'--justify);');
						}
					}
					// border
					else if (slug == 'border') {
						var { width, style, color } = value;
						[ 'top', 'bottom', 'left', 'right' ].forEach(function(side) {
							if (!_.has(value, side)) value[side] = { width: width, style: style, color: color };
							Object.keys(value[side]).forEach(function(key) {
								styleVars['normal'].push(varSlug+'--'+slug+'-'+side+'-'+key+': '+value[side][key]+';');
								var valueHover = 'var('+varSlug+'--'+slug+'-'+side+'-'+key+')';
								if (_.has(values[type], 'hover') && _.has(values[type]['hover'], slug) && _.has(values[type]['hover'][slug], side) && _.has(values[type]['hover'][slug][side], key)) {
									valueHover = values[type]['hover'][slug][side][key];
								}
								styleVars['hover'].push(varSlugHover+'--'+slug+'-'+side+'-'+key+': '+valueHover+';');
							});
						});
					}
					// overlay
					else if (slug == 'overlay_enable') {
						styleVars['normal'].push(varSlug+'--overlay: '+(value == 'true' ? '100%' : '0')+';');
					}
					else if (slug == 'overlay_effect') {
						var effect = value['effect'];
						if (effect != 'none') {
							var val = value[effect];
							var unit = "%";
							if (effect == 'blur') unit = "px";
							if (effect == 'brightness') unit = "";
							if (effect == 'hue-rotate') unit = "deg";
							effect += "("+val+unit+")";
						}
						styleVars['normal'].push(varSlug+'--'+slug+': '+effect+';');
					}
					// body transform
					else if (slug == 'anim_body') {
						var transform = value['transform'];
						if (transform != 'none') {
							if (transform == 'translate') {
								var valX = _.has(value, 'translateX') ? value['translateX'] : "0px";
								var valY = _.has(value, 'translateY') ? value['translateY'] : "0px";
								transform += "("+valX+", "+valY+")";
							}
							else if (transform == 'scale') {
								var valX = _.has(value, 'scaleX') ? value['scaleX']+"%" : "100%";
								var valY = _.has(value, 'scaleY') ? value['scaleY']+"%" : "100%";
								transform += "("+valX+", "+valY+")";
							}
							else if (transform == 'rotate') {
								var val = _.has(value, 'rotateZ') ? value['rotateZ']+"deg" : "0deg";
								transform += "("+val+")";
							}
							else transform = 'none';
						}
						var origin = value['origin'];
						styleVars['normal'].push(varSlug+'--'+slug+'-transform: '+transform+';');
						styleVars['normal'].push(varSlug+'--'+slug+'-origin: '+origin+';');
					}
					else if (slug == 'anim_type' || slug == 'anim_time') {
						// no css var
					}
					// everything else
					else if (item['type'] != 'checkbox' && item['type'] != 'input') {

						if (typeof item['default'] === 'object') {
							Object.keys(item['default']).forEach(function(key) {
								var val = _.has(value, key) ? value[key] : value;
								if (item['type'] == 'dimension') val = greyd.tools.getSpacingPresetCssVar(val);
								styleVars['normal'].push(varSlug+'--'+slug+'-'+key+': '+val+';');
								if (_.has(item, 'mobile')) {
									var valueMobile = 'var('+varSlug+'--'+slug+'-'+key+')';
									if (_.has(values[type], 'mobile') && _.has(values[type]['mobile'], slug)) {
										valueMobile = _.has(values[type]['mobile'][slug], key) ? values[type]['mobile'][slug][key] : values[type]['mobile'][slug];
									}
									if (item['type'] == 'dimension') valueMobile = greyd.tools.getSpacingPresetCssVar(valueMobile);
									styleVars['mobile'].push(varSlugMobile+'--'+slug+'-'+key+': '+valueMobile+';');
								}
								if (_.has(item, 'hover')) {
									var valueHover = 'var('+varSlug+'--'+slug+'-'+key+')';
									if (_.has(values[type], 'hover') && _.has(values[type]['hover'], slug)) {
										valueHover = _.has(values[type]['hover'][slug], key) ? values[type]['hover'][slug][key] : values[type]['hover'][slug];
									}
									styleVars['hover'].push(varSlugHover+'--'+slug+'-'+key+': '+valueHover+';');
								}
							} );
						}
						else {
							styleVars['normal'].push(varSlug+'--'+slug+': '+value+';');
							if (_.has(item, 'mobile')) {
								var valueMobile = 'var('+varSlug+'--'+slug+')';
								if (_.has(values[type], 'mobile') && _.has(values[type]['mobile'], slug)) {
									valueMobile = values[type]['mobile'][slug];
								}
								styleVars['mobile'].push(varSlugMobile+'--'+slug+': '+valueMobile+';');
							}
							if (_.has(item, 'hover')) {
								var valueHover = 'var('+varSlug+'--'+slug+')';
								if (_.has(values[type], 'hover') && _.has(values[type]['hover'], slug)) {
									valueHover = values[type]['hover'][slug];
								}
								styleVars['hover'].push(varSlugHover+'--'+slug+': '+valueHover+';');
							}
						}

					}

					// initially set vertical alignment
					if (slug == 'size' && _.has(value, 'align')) {
						// console.log(item);
						// console.log(value);
						var editor = state.root.querySelector('.is-root-container.wp-block-post-content');
						editor.classList.remove('align-start', 'align-center', 'align-end');
						editor.classList.add('align-'+value['align']);
					}

				} );
				
			} );

			return styleVars;

		}

		/**
		 * Apply the updated Value to the css vars for Preview.
		 * Called with each 'updateValue' call.
		 * 
		 * @param {array} slugs 	The Object Properties Path of the new Value.
		 * @param {any} value 	The new Value.
		 */
		var updateStyle = function(slugs, value) {
			
			var [ type, slug, sec ] = slugs;

			var varSlug = '--popup--'+type;
			if (typeof sec !== 'undefined') {
				varSlug += '--'+sec;
			}

			if (slug == 'position') {
				var [ align, justify ] = value.split(' ');
				if (align == 'top') align = 'start';
				if (align == 'bottom') align = 'end';
				updateStyleVars({
					[varSlug+'--align']: align,
					[varSlug+'--justify']: justify
				});
			}
			else if (slug == 'size') {
				var width = value['width'];
				if (width == '100' || width == 'custom') width = '100%'
				var height = value['height'];
				if (height == '100' || height == 'custom') height = '100%'
				updateStyleVars({
					[varSlug+'--'+slug+'-width']: width,
					[varSlug+'--'+slug+'-height']: height,
					[varSlug+'--'+slug+'-align']: value['align']
				});
				var editor = state.root.querySelector('.is-root-container.wp-block-post-content');
				editor.classList.remove('align-start', 'align-center', 'align-end');
				editor.classList.add('align-'+value['align']);
			}

			else if (slug == 'border_radius') {
				if (typeof value === 'string') value = {  topLeft: value, topRight: value, bottomLeft: value, bottomRight: value };
				updateStyleVars({
					[varSlug+'--'+slug+'-topLeft']: value['topLeft'],
					[varSlug+'--'+slug+'-topRight']: value['topRight'],
					[varSlug+'--'+slug+'-bottomLeft']: value['bottomLeft'],
					[varSlug+'--'+slug+'-bottomRight']: value['bottomRight']
				});
			}
			else if (slug == 'border') {
				var { width, style, color } = value;
				var vars = {};
				[ 'top', 'bottom', 'left', 'right' ].forEach(function(side) {
					if (!_.has(value, side)) value[side] = { width: width, style: style, color: color };
					Object.keys(value[side]).forEach(function(key) {
						vars[varSlug+'--'+slug+'-'+side+'-'+key] = value[side][key];
					});
				});
				updateStyleVars(vars);
			}

			else if (slug == 'overlay_enable') {
				if (value == 'true') {
					var styleValue = values['more']['overlay_effect']['effect'];
					if (styleValue != 'none') {
						var val = values['more']['overlay_effect'][styleValue];
						var unit = "%";
						if (styleValue == 'blur') unit = "px";
						if (styleValue == 'brightness') unit = "";
						if (styleValue == 'hue-rotate') unit = "deg";
						styleValue += "("+val+unit+")";
					}
					updateStyleVars({
						[varSlug+'--overlay_effect']: styleValue,
						[varSlug+'--overlay']: '100%'
					});
				}
				else {
					updateStyleVars({
						[varSlug+'--overlay_effect']: 'none',
						[varSlug+'--overlay']: '0'
					});
				}
			}
			else if (slug == 'overlay_effect') {
				var styleValue = value['effect'];
				if (styleValue != 'none') {
					var val = value[styleValue];
					var unit = "%";
					if (styleValue == 'blur') unit = "px";
					if (styleValue == 'brightness') unit = "";
					if (styleValue == 'hue-rotate') unit = "deg";
					styleValue += "("+val+unit+")";
				}
				updateStyleVars({ [varSlug+'--overlay_effect']: styleValue });
			}
			else if (slug == 'css_class') {
				var wrapper = state.root.querySelector('.popup_wrapper');
				wrapper.classList.forEach(function(val) {
					if (val != 'popup_wrapper') wrapper.classList.remove(val);
				});
				if (value != "") wrapper.classList.add(value);
			}
			
			else if (slug == 'anim_body') {
				var transform = value['transform'];
				if (transform != 'none') {
					if (transform == 'translate') {
						var valX = _.has(value, 'translateX') ? value['translateX'] : "0px";
						var valY = _.has(value, 'translateY') ? value['translateY'] : "0px";
						transform += "("+valX+", "+valY+")";
					}
					else if (transform == 'scale') {
						var valX = _.has(value, 'scaleX') ? value['scaleX']+"%" : "100%";
						var valY = _.has(value, 'scaleY') ? value['scaleY']+"%" : "100%";
						transform += "("+valX+", "+valY+")";
					}
					else if (transform == 'rotate') {
						var val = _.has(value, 'rotateZ') ? value['rotateZ']+"deg" : "0deg";
						transform += "("+val+")";
					}
					else transform = 'none';
				}
				var origin = value['origin'];
				updateStyleVars({ 
					[varSlug+'--anim_body-transform']: transform, 
					[varSlug+'--anim_body-origin']: origin 
				});

			}
			else if (slug == 'anim_type' || slug == 'anim_time') {
				// no css var
			}

			else if (items[type][slug]['type'] != 'checkbox' && items[type][slug]['type'] != 'input') {
				
				if (typeof value === 'object') {
					var vars = {};
					Object.keys(value).forEach(function(key) {
						vars[varSlug+'--'+slug+'-'+key] = value[key];
					});
					updateStyleVars(vars);
				}
				else {
					updateStyleVars({ [varSlug+'--'+slug]: value });
				}

			}

		}

		/**
		 * Update a collection of css vars to their new value.
		 * Each Property name defines the css var name and its value the new css Value.
		 * 
		 * @param {object} vars 	css vars to update.
		 */
		var updateStyleVars = function(vars) {

			// console.log(document.querySelector('#popup_style'));
			var styleElement = state.root.querySelector('#popup_style');
			var stylesheet = styleElement?.sheet;
			// loop stylesheet's cssRules
			if ( stylesheet ) for (var j=0; j<stylesheet.cssRules.length; j++) {

				if ( typeof stylesheet.cssRules[j].style === 'undefined' || 
					 stylesheet.cssRules[j].style.length === 0 ) { 
					continue; 
				}
				
				// loop stylesheet's cssRules' style (property names)
				for (var k=0; k<stylesheet.cssRules[j].style.length; k++) {
					if (Object.keys(vars).indexOf(stylesheet.cssRules[j].style[k]) > -1) {
						var key = stylesheet.cssRules[j].style[k];
						var value = vars[stylesheet.cssRules[j].style[k]];
						// console.log(value);
						// set property
						if ( _.isEmpty( value ) && !_.isNumber( value ) ) value = 'none';
						stylesheet.cssRules[j].style.setProperty(key, value);
					}
				}
			}
			
		}

		/**
		 * Start a Preview of the open and close Animation.
		 * this is triggered by click on the overlay, or by clicking the Button in the sidebar.
		 */
		var closePreview = function(force) {

			if (values['more']['overlay_close'] === 'false' && !force) return;
			// console.log("preview close animation ...");
	
			var popup_preview = jQuery(state.root.querySelector('.popup_preview'));
			if (popup_preview.hasClass('busy')) return;
			popup_preview.addClass('busy');
	
			var overlay = jQuery(state.root.querySelector('.popup_overlay'));
			var wrapper = jQuery(state.root.querySelector('.is-root-container.wp-block-post-content'));
			var website = jQuery(state.root.querySelector('.popup_website > iframe'));
			
			var anim = values['more']['anim_type'];
			var time = parseInt(values['more']['anim_time']);
			var dur = (time/1000.0);
			var opacity = parseFloat(values['more']['overlay_opacity']);
			var opacity_var = 'var(--popup--more--overlay_opacity)';
			var filter_var = 'var(--popup--more--overlay_effect)';
			var transform_var = 'var(--popup--more--anim_body-transform)';
			// console.log(anim+" "+time+" "+opacity);
	
			if (anim == 'none') {
				time = 0; 
				dur = 0;
				popup_preview.addClass('hidden');
			}
			else if (anim == 'fade') {
				popup_preview.animate( {"opacity": '0'}, time, function() { popup_preview.addClass('hidden') } );
			}
			else if (anim.indexOf('slide_') == 0) {
				if (anim == 'slide_top') {
					overlay.animate( {"top": '100%', "opacity": '0'}, time );
					popup_preview.animate( {"top": '-100%'}, time, function() { popup_preview.addClass('hidden') } );
				}
				else if (anim == 'slide_bottom') {
					overlay.animate( {"top": '-100%', "opacity": '0'}, time );
					popup_preview.animate( {"top": '100%'}, time, function() { popup_preview.addClass('hidden') } );
				}
				else if (anim == 'slide_left') {
					overlay.animate( {"left": '100%', "opacity": '0'}, time );
					popup_preview.animate( {"left": '-100%'}, time, function() { popup_preview.addClass('hidden') } );
				}
				else if (anim == 'slide_right') {
					overlay.animate( {"left": '-100%', "opacity": '0'}, time );
					popup_preview.animate( {"left": '100%'}, time, function() { popup_preview.addClass('hidden') } );
				}
			}
			else {
				var dur_old = wrapper.css('transition-duration');
				if (anim == 'scale')
					wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'scale(0)') } );
				else if (anim == 'flip_vertical')
					wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotateY(-90deg)') } );
				else if (anim == 'flip_horizontal')
					wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotateX(-90deg)') } );
				else if (anim == 'flip_3d')
					wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotate3d(1,1,1,240deg) scale(0)') } );
				overlay.animate( {"opacity": '0'}, time, function() { popup_preview.addClass('hidden') } );
			}
			website.animate( {"transition-duration": dur+'s'}, 0, function() { website.css('filter', 'none'), website.css('transform', 'none') } );
	
	
			var reopen = 500;
			setTimeout(function() {
	
				popup_preview.removeClass('hidden');
	
				if (anim == 'none') {
				} 
				else if (anim == 'fade') {
					popup_preview.animate( {"opacity": '1'}, time );
				}
				else if (anim.indexOf('slide_') == 0) {
					overlay.animate( {"top": '0', "left": '0', "opacity": opacity}, time, function() { 
						overlay.css('opacity', opacity_var);
					} );
					popup_preview.animate( {"top": '0', "left": '0'}, time );
				}
				else {
					var dur = (time/1000.0);
					if (anim == 'scale')
						wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'scale(1)') } );
					else if (anim == 'flip_vertical')
						wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotateY(0deg)') } );
					else if (anim == 'flip_horizontal')
						wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotateX(0deg)') } );
					else if (anim == 'flip_3d')
						wrapper.animate( {"transition-duration": dur+'s'}, 0, function() { wrapper.css('transform', 'rotate3d(1,1,1,0) scale(1)') } );
					overlay.animate( {"opacity": opacity}, time, function() { 
						wrapper.css('transition-duration', dur_old);
						overlay.css('opacity', opacity_var);
					} );
				}
				website.animate( {"transition-duration": dur+'s'}, 0, function() { 
					website.css('filter', filter_var);
					website.css('transform', transform_var);
					website.css('transition-duration', dur_old);
				} );
	
			}, reopen+time);
	
			setTimeout(function() {
				popup_preview.removeClass('busy');
			}, reopen+time+time+100);

		}

		/*
		=======================================================================
			State
		=======================================================================
		*/

		// get global styles (init colors)
		// needed to prevent errors in the components
		var styles = wp.data.useSelect( function(select) {
			return select( 'core' ).__experimentalGetCurrentThemeBaseGlobalStyles();
		} );

		// get the current viewport
		var getViewport = function() {
			if ( _.has(wp.data.select( 'core/editor' ), 'getDeviceType') ) {
				return wp.data.select( 'core/editor' ).getDeviceType();
			} else {
				if ( !_.has(wp.data.select("core/edit-post"), "__experimentalGetPreviewDeviceType") ) return false;
				return wp.data.select("core/edit-post").__experimentalGetPreviewDeviceType();
			}
		}

		// bundle all subscription in one state object
		// this way, subscribes are only initialized once and don't add up while editing
		var [ unsubscribe, setUnsubscribe ] = wp.element.useState({ 
			saving: 'activate',
			viewport: 'activate', 
			editor: 'activate', // false,
			iframe: false 
		});

		// state object to detect changes in the viewport
		var [ state, setState ] = wp.element.useState({
			state: '',
			viewport: getViewport(),
			root: '', // document
		});
		// console.log(state);

		// subscribe to saving post
		if (unsubscribe.saving === 'activate') {
			var unsubscribeSaving = wp.data.subscribe(() => {
				// console.log("check if saving...");
				const isSavingPost = wp.data.select('core/editor').isSavingPost();
				if (isSavingPost) {
					// console.log("post saving");
					greyd.popup.changed = false;
				}
			});
			setUnsubscribe( { 
				...unsubscribe, 
				saving: unsubscribeSaving 
			} );
		}

		// subscribe to viewport change
		if (unsubscribe.viewport === 'activate') {
			var unsubscribeViewportChange = wp.data.subscribe(() => {
				// console.log("checking viewport...");
				var newViewport = getViewport();
				if (state.viewport !== newViewport) {
					// console.log("viewport changed to "+newViewport);
					unsubscribeViewportChange();
					setUnsubscribe({ 
						...unsubscribe, 
						viewport: false, 
						editor: 'activate'
					});
					setState({ ...state, viewport: newViewport, root: '' });
				}
			});
			setUnsubscribe( { 
				...unsubscribe, 
				viewport: unsubscribeViewportChange
			} );
		}

		// subscribe to editor root change
		if ( unsubscribe.editor === 'activate' ) {
			var unsubscribeEditorChange = wp.data.subscribe(() => {
				if (state.root == "") {
					// console.log("checking editor...");
					if ( document.querySelector('.is-root-container.wp-block-post-content') ) {
						// console.log("non-iframed editor loaded");
						unsubscribeEditorChange();
						setUnsubscribe({ 
							...unsubscribe, 
							editor: false, 
							viewport: 'activate'
						});
						setState({ ...state, state: '', root: document });
					}
					else {
						// console.log("checking editor iframe...");
						var previewClass = state.viewport == 'Tablet' ? '.is-tablet-preview' : '.is-mobile-preview';
						if ( document.querySelector('.is-iframed iframe') || document.querySelector(previewClass+' > iframe') ) {
							// console.log("iframed editor loaded");
							unsubscribeEditorChange();
							setUnsubscribe({ 
								...unsubscribe, 
								editor: false, 
								iframe: 'activate' 
							});
							setState({ ...state, state: '', root: '' });
							// make autosave to trigger state update
							wp.data.dispatch('core/editor').autosave();
						}
					}
				}
			});
			setUnsubscribe( { 
				...unsubscribe, 
				editor: unsubscribeEditorChange
			} );
		}

		// subscribe to editor iframe change (Tablet and Mobile)
		if (unsubscribe.iframe === 'activate') {	
			var unsubscribeIFrameChange = wp.data.subscribe(() => {
				if (state.root == "") {
					// console.log("checking iframe root...");
					var iframe = document.querySelector('.is-iframed iframe');
					if ( !iframe ) {
						var previewClass = state.viewport == 'Tablet' ? '.is-tablet-preview' : '.is-mobile-preview';
						iframe = document.querySelector(previewClass+' > iframe');
					}
					if (iframe && iframe.contentWindow.document.querySelector('.is-root-container.wp-block-post-content')) {
						// console.log("iframe root loaded");
						unsubscribeIFrameChange();
						setUnsubscribe({ 
							...unsubscribe, 
							iframe: false, 
							viewport: 'activate'
						});
						setState({ ...state, state: '', root: iframe.contentWindow.document });
					}
				}
			});
			setUnsubscribe( { 
				...unsubscribe, 
				iframe: unsubscribeIFrameChange
			} );
		}

		// if editor is lost, subscribe to search editor again
		if (
			state.state == "" &&
			state.root != "" &&
			!state.root.querySelector('.is-root-container.wp-block-post-content')
		) {
			unsubscribeViewportChange();
			setUnsubscribe({ 
				...unsubscribe, 
				viewport: false, 
				editor: 'activate',
				iframe: false 
			});
			setState({ ...state, root: '' });
		}

		// add additional markup to preview popups
		// needs to be done on each viewport change since the html markup is reset
		if ( state.state == "" && state.root != "" && styles ) {

			// loaded
			// console.log("data loaded.");
			// console.log(state.root);
			// console.log(styles);

			if (state.root.querySelectorAll('.popup_wrapper').length == 0) {
	
				var bodyHeight = "";
				if (state.viewport != 'Desktop') {
					// add popup styles to tablet and mobile
					var head = state.root.head;
					var link = state.root.createElement("link");
					link.type = "text/css";
					link.rel = "stylesheet";
					link.href = greyd.popupStyles;
					head.appendChild(link);
					bodyHeight = 'height: 100vh !important;';
				}

				var styleVars = makeStyleVars();
				// console.log(styleVars);

				var style = '<style id="popup_style">'+
								'body { '+bodyHeight+' '+styleVars['normal'].join(' ')+' '+styleVars['mobile'].join(' ')+' '+styleVars['hover'].join(' ')+' }'+
							'</style>';
				var website = '<div class="popup_website">'+
								'<iframe name="popup-preview-0" src="'+greyd.home+'/?popup_preview=1234"></iframe>'+
								''+
							'</div>';
				
				var editor = state.root.querySelector('.is-root-container.wp-block-post-content');
				editor.insertAdjacentHTML('beforebegin', '<div class="popup_wrapper"></div>');
				var wrapper = state.root.querySelector('.popup_wrapper');
				// move elements into wrapper
				wrapper.insertAdjacentHTML('afterbegin', style+website+'<div class="popup_preview"><div class="popup_overlay"></div></div>');
				// move editor into preview
				var preview = state.root.querySelector('.popup_preview');
				preview.appendChild(editor);
				// add click event to overlay
				var overlay = state.root.querySelector('.popup_overlay');
				overlay.addEventListener('click', function(e) {
					// console.log('close popup preview');
					closePreview();
				});

			}

			// loaded
			setState({ ...state, state: 'loaded' });
	
		}

		/*
		=======================================================================
			Menus
		=======================================================================
		*/

		// basic menu definition
		const menu = [
			{
				title: __('Layout', 'greyd_hub'),
				slug: "layout",
				icon: 'layout',
				tabs: [
					{
						label: __("Default", "greyd_hub"),
						slug: ""
					},
					{
						label: __("Mobile", "greyd_hub"),
						slug: "mobile",
						previewClass: "has-mobile"
					}
				]
			},
			{
				title: __('Design', 'greyd_hub'),
				slug: "design",
				icon: 'admin-appearance',
				tabs: [
					{
						label: __("Default", "greyd_hub"),
						slug: ""
					},
					{
						label: __("Hover", "greyd_hub"),
						slug: "hover",
						previewClass: "has-hover"
					}
				]
			},
			{
				title: __("Extended", 'greyd_hub'),
				slug: "more",
				icon: 'admin-generic'
			},
		];
		
		/**
		 * Get the current menu item.
		 * @param {string} slug name of the item
		 * @returns {object}
		 */
		var getMenuItem = (slug) => {
			var menuItem = false;
			var [ main, sub = "" ] = slug.split('.');
			menu.forEach((item) => {
				if (item.slug == main) {
					menuItem = item;
				}
			});
			return menuItem;
		}
		
		// ste object to track the menu state
		var [ mode, setMode ] = wp.element.useState("");
		var current = getMenuItem(mode);

		/*
		=======================================================================
			Render
		=======================================================================
		*/

		/**
		 * Render a menu Panel.
		 * @param {object} atts Attributes
		 * @returns {WPElement[]}
		 */
		function renderPanel(atts) {

			var elements = [];

			Object.keys(items[atts.item.slug]).forEach(function(key, index) {
				// console.log(key);
				var item = items[atts.item.slug][key];
				// console.log(item);
				if (_.has(item, 'head')) {
					elements.push(
						(index > 0 ) ? el( "hr" ) : null,
						el( "h2", {}, item['head'] )
					);
					if (key == 'anim_type') {
						elements.push(
							el( wp.components.Button, { 
								style: { marginBottom: '16px' },
								className: 'button-secondary',
								onClick: function() { closePreview(true)} 
							}, __('Preview animation', 'greyd_hub') )
						);
					}
				}
				var show = true;
				if (_.has(item, 'dependency')) {

					// console.log("check dependency");
					// console.log(item['dependency']);
					var dependency = item['dependency'];
					for (var i=0; i<dependency.length; i++) {

						var value = _.has(values[atts.item.slug], dependency[i].element) ? values[atts.item.slug][dependency[i].element] : items[atts.item.slug][dependency[i].element]['default'];
						// console.log(value);
						var part_enabled = false;
						if (dependency[i].value != undefined) {
							part_enabled = false;
							for (var j=0; j<dependency[i].value.length; j++) {
								if (value == dependency[i].value[j]) {
									part_enabled = true;
									break;
								}
							}
						}
						else if (dependency[i].not_value != undefined) {
							part_enabled = true;
							for (var j=0; j<dependency[i].not_value.length; j++) {
								if (value == dependency[i].not_value[j]) {
									part_enabled = false;
									break;
								}
							}
						}
						if (!part_enabled) {
							show = false;
							break;
						}

					}

				}

				if (show) {
					var label = item['label'];
					if (_.has(label, 'lbl')) {
						var unit = _.has(label, 'unit') ? ' ('+label['unit']+')' : '';
						label = label.lbl+unit;
					}
					// var value = item['default'];
					var value = _.has(values[atts.item.slug], item['param_name']) ? values[atts.item.slug][item['param_name']] : item['default'];
					var value_mobile = _.has(item, 'mobile') ? (_.has(values[atts.item.slug], 'mobile') && _.has(values[atts.item.slug]['mobile'], item['param_name']) ? values[atts.item.slug]['mobile'][item['param_name']] : value) : false;
					var value_hover = _.has(item, 'hover') ? (_.has(values[atts.item.slug], 'hover') && _.has(values[atts.item.slug]['hover'], item['param_name']) ? values[atts.item.slug]['hover'][item['param_name']] : value) : false;

					switch (item['type']) {
						// with mobile
						case 'matrix': 
							elements.push(
								el( 'label', {}, label ),
								el( wp.components.__experimentalAlignmentMatrixControl, {
									value: value,
									onChange: (newValue) => {
										// console.log(item['param_name'], newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									mobile: value_mobile ? {
										value: value_mobile,
										onChange: (newValue) => {
											// console.log('mobile_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'mobile'], newValue);
										}
									} : undefined
								} )
							);
							break;
						case 'size': 
							elements.push(
								el( greyd.components.popup.SizeControl, {
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									mobile: value_mobile ? {
										value: value_mobile,
										onChange: (newValue) => {
											// console.log('mobile_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'mobile'], newValue);
										}
									} : undefined
								} )
							);
							break;
						case 'dimension':
							elements.push(
								el( greyd.components.DimensionControl, {
									label: label,
									value: value,
									onChange: (newValue) => {
										// check for empty and 'nullpx' values
										if ( typeof newValue === 'string' ) {
											if ( _.isEmpty(newValue) ) newValue = '0px';
											else if ( newValue.indexOf('null') === 0 ) newValue = newValue.replace('null', '0');
										}
										else if ( typeof newValue === 'object' ) ['top', 'bottom', 'left', 'right'].forEach( (side) => {
											if ( !newValue[side] ) newValue[side] = '0px';
											else if ( newValue[side].indexOf('null') === 0 ) newValue[side] = newValue[side].replace('null', '0');
											newValue[side] = greyd.tools.getSpacingPresetCssVar(newValue[side]);
										} );
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									mobile: value_mobile ? {
										value: value_mobile,
										onChange: (newValue) => {
											// check for empty and 'nullpx' values
											if ( typeof newValue === 'string' ) {
												if ( _.isEmpty(newValue) ) newValue = '0px';
												else if ( newValue.indexOf('null') === 0 ) newValue = newValue.replace('null', '0');
											}
											else if ( typeof newValue === 'object' ) ['top', 'bottom', 'left', 'right'].forEach( (side) => {
												if ( !newValue[side] ) newValue[side] = '0px';
												else if ( newValue[side].indexOf('null') === 0 ) newValue[side] = newValue[side].replace('null', '0');
												newValue[side] = greyd.tools.getSpacingPresetCssVar(newValue[side]);
											} );
											// console.log('mobile_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'mobile'], newValue);
										}
									} : undefined
								} )
							);
							break;
						// with hover
						case 'colors': 
							elements.push(
								el( greyd.components.popup.ColorsControl, {
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									hover: value_hover ? {
										value: value_hover,
										onChange: (newValue) => {
											// console.log('hover_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'hover'], newValue);
										}
									} : undefined
								} )
							);
							break;
						case 'border_radius': 
							elements.push(
								el( wp.blockEditor.__experimentalBorderRadiusControl, {
									label: label,
									values: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									hover: value_hover ? {
										values: value_hover,
										onChange: (newValue) => {
											// console.log('hover_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'hover'], newValue);
										}
									} : undefined
								} )
							);
							break;
						case 'border': 
							var colorValue = function(val) {
								if (_.has(val, 'color')) return { ...val, color: greyd.tools.popup.getColorValue(val.color) };
								else {
									return {
										'top': { ...val.top, color: greyd.tools.popup.getColorValue(val.top.color) },
										'bottom': { ...val.bottom, color: greyd.tools.popup.getColorValue(val.bottom.color) },
										'left': { ...val.left, color: greyd.tools.popup.getColorValue(val.left.color) },
										'right': { ...val.right, color: greyd.tools.popup.getColorValue(val.right.color) },
									}
								}
							}
							var colorVariable = function(val) {
								if (_.has(val, 'color')) return { ...val, color: greyd.tools.popup.getColorVariable(val.color) };
								else {
									return {
										'top': { ...val.top, color: greyd.tools.popup.getColorVariable(val.top.color) },
										'bottom': { ...val.bottom, color: greyd.tools.popup.getColorVariable(val.bottom.color) },
										'left': { ...val.left, color: greyd.tools.popup.getColorVariable(val.left.color) },
										'right': { ...val.right, color: greyd.tools.popup.getColorVariable(val.right.color) },
									}
								}
							}
							elements.push(
								el( wp.components.BaseControl, { }, [
									el( wp.components.__experimentalBorderBoxControl, {
										label: label,
										__experimentalHasMultipleOrigins: true,
										withSlider: true,
										enableAlpha: true,
										colors: greyd.tools.popup.getColors(),
										value: colorValue(value),
										onChange: (newValue) => {
											newValue = colorVariable(newValue);
											// console.log(newValue);
											updateValue([atts.item.slug, item['param_name']], newValue);
										},
										hover: value_hover ? {
											value: value_hover,
											onChange: (newValue) => {
												newValue = colorVariable(newValue);
												// console.log('hover_'+item['param_name'], newValue);
												updateValue([atts.item.slug, item['param_name'], 'hover'], newValue);
											}
										} : undefined
									} )
								] )
							);
							break;
						case 'shadow': 
							elements.push(
								el( greyd.components.popup.DropShadowControl, {
									label: label,
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									},
									hover: value_hover ? {
										value: value_hover,
										onChange: (newValue) => {
											// console.log('hover_'+item['param_name'], newValue);
											updateValue([atts.item.slug, item['param_name'], 'hover'], newValue);
										}
									} : undefined
								} )
							);
							break;
						// single
						case 'range': 
							var min = item['input_attrs']['min'];
							var max = item['input_attrs']['max'];
							var step = item['input_attrs']['step'];
							elements.push(
								el( wp.components.RangeControl, {
									// style: { width: "100%" },
									label: label,
									value: parseFloat(value),
									min: min,
									max: max,
									step: step,
									onChange: (newValue) => {
										// console.log([atts.item.slug, item['param_name']], newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									}
								} )
							);
							break;
						case 'colorselect': 
							elements.push(
								el( "div", { }, [
									el( greyd.components.popup.ColorGradientPopupControl, {
										// mode: 'color',
										label: label,
										value: value,
										onChange: (newValue) => {
											// console.log(newValue);
											updateValue([atts.item.slug, item['param_name']], newValue);
										}
									} )
								] )
							);
							break;
						case 'effect': 
							elements.push(
								el( greyd.components.popup.OverlayEffectControl, {
									label: label,
									help: item['description'],
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									}
								} )
							);
							break;
						case 'transform': 
							elements.push(
								el( greyd.components.popup.BodyTransformControl, {
									label: label,
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									}
								} )
							);
							break;
						case 'checkbox': 
							elements.push(
								el( wp.components.ToggleControl, {
									label: label,
									checked: value === 'true',
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue === true ? 'true' : 'false');
									}
								} )
							);
							break;
						case 'select': 
						case 'dropdown': 
							var options = [];
							Object.keys(item['options']).forEach(function(key) {
								options.push( { label: item['options'][key], value: key } );
							});
							if (item['type'] == 'dropdown') {
								elements.push(
									el( greyd.components.OptionsControl, {
										style: { width: "100%" },
										label: label,
										value: value,
										options: options,
										onChange: (newValue) => {
											// console.log(newValue);
											updateValue([atts.item.slug, item['param_name']], newValue);
										}
									} )
								);
							}
							else if (item['type'] == 'select') {
								elements.push(
									el( 'label', {}, label ),
									el( greyd.components.ButtonGroupControl, {
										value: value,
										options: options,
										onChange: (newValue) => {
											// console.log(newValue);
											updateValue([atts.item.slug, item['param_name']], newValue);
										}
									} )
								);
							}
							break;
						default:
							elements.push(
								el( wp.components.TextControl, {
									label: label,
									value: value,
									onChange: (newValue) => {
										// console.log(newValue);
										updateValue([atts.item.slug, item['param_name']], newValue);
									}
								} )
							);
					}
				}
			} )

			return el( greyd.components.popup.MoreAdvancedPanelBody, { 
				title: atts.item.title, 
				tabs: atts.item.tabs,
			}, elements );
		}

		// show spinner while loading
		if ( !state.state || state.state !== 'loaded' ) {
			
			return el( 'div', {
				className: 'greyd-styles-panel',
				style: {
					textAlign: 'center'
				}
			}, [
				el( wp.components.Spinner ),
				el( 'p', {
					style: {
						marginTop: '2em'
					}
				}, __('Loading data...', 'greyd_hub') )
			] );

		}
		
		// render full sidebar
		return el( 'div', { className: 'greyd-styles-panel popups-panel' }, [

			// top area
			el( "div", {
				className: 'panel-head',
				style: {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center'
				}
			}, [
				
				el( 'div', {
					style: {
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center'
					}
				}, [

					// go back
					(mode != "") ? el( wp.components.Button, {
						variant: 'secondary',
						icon: 'arrow-left-alt',
						onClick: () => setMode( mode.indexOf('.') > 0 ? current.slug : '' )
					} ) : null,

					el( 'span', { className: "panel-title" },
						// current ? _.get( getMenuItem(mode), 'title' ) : __('Customize your popup', 'greyd_hub')
						__('Customize your pop-up', 'greyd_hub')
					),
					
				] ),

			] ),

			// main
			(mode == "") ? [

				// main menu
				el( 'div', { className: 'panel-menu' }, [
					menu.map((item) => el( wp.components.Button, {
						disabled: item.disabled ? 'disabled' : '',
						style: item.hidden ? { display: 'none' } : {},
						className: 'panel-menu-item',
						icon: item.icon,
						onClick: function() { setMode(item.slug) }
					}, item.title ) ),

					// el( wp.components.Tip, {}, [
					// 	el( "span", {}, __('The master tip.', 'greyd_hub') ),
					// ] )
				] ),

			] : [

				// individual panels
				menu.map((item) => {

					if (mode.indexOf(item.slug) == 0) {
						if (_.has(current, 'menu')) {
							// render sub menu
							// return renderSubmenuPanel();
						}
						else {
							// render panel
							return renderPanel({
								parent: "",
								item: current,
							});
						}
					}

				} )
			],

		] );
	}

	/**
	 * Render in DocumentSettingsPanel.
	 * @param {object} atts	Compose function atts.
	 * 		@property {string} postType			The post_type of the current post.
	 * 		@property {object} popupDesign		All current Meta values of this post.
	 * 		@property {function} setPopupDesign	Function to update Meta values.
	 */
	var greydPopupsPanel = function(atts) {
		
		// console.log(atts);
		if (atts.postType == 'greyd_popup') {
			// only on popup posttype scrren
			return el(
				wp.editPost.PluginDocumentSettingPanel, {
					initialOpen: true,
					name: 'greyd-popups',
					icon: el( wp.components.Icon, { icon: ''} ),
					title: __('Style', 'greyd_hub')
				}, greydPopups(atts)
			);
		} 
		else {
			// don't render on other post_types
			return null;
		}

	}

	/**
	 * Register Feature as plugin.
	 */
	wp.plugins.registerPlugin( 'greyd-popups', {
		// render composed HOC
		render: wp.compose.compose( [
			// get data
			wp.data.withSelect( ( select ) => {
				return {
					postType: select( 'core/editor' ).getCurrentPostType(),
					popupDesign: select( 'core/editor' ).getEditedPostAttribute( 'meta' ).popup_design
				};
			} ),
			// set data
			wp.data.withDispatch( ( dispatch ) => {
				return {
					setPopupDesign( newMeta ) {
						// console.log( 'newMeta', newMeta )
						dispatch( 'core/editor' ).editPost( { meta: { popup_design: newMeta } } );
					},
				};
			} )
		] )( greydPopupsPanel )
	} );

} )(
	window.wp,
	greyd.components
);