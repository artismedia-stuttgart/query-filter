/**
 * Greyd.Blocks Editor Script for Trigger Features.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var _ = lodash;

	/**
	 * Register custom attributes to blocks.
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (_.has(settings, 'apiVersion')) {
			// console.log(name);
			// console.log(settings);

			if (name == 'core/group' ||
				name == 'greyd/box') {
				settings.attributes.trigger_event = { type: 'object' };
				// console.log(settings);
			}
			if (name == 'core/group' ||
				name == 'core/button' ||
				name == 'greyd/box' ||
				name == 'greyd/button' ||
				name == 'greyd/image') {
				settings.attributes.trigger = { type: 'object' };
				// console.log(settings);
			}

		}

		return settings;

	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/trigger',
		registerBlockTypeHook
	);
	

	/**
	 * Add custom edit controls to blocks.
	 * 
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {
		
		return function( props ) {	
			
			/**
			 * =================================================================
			 *                          Trigger extensions
			 * =================================================================
			 */

			var block_type = wp.blocks.getBlockType(props.name);
			var extend = false;
			var toolbar = false;
			var sidebar = false;
			var style = false;
			
			/**
			 * Trigger event support.
			 */
			if (_.has(block_type.attributes, 'trigger_event')) {
				extend = true;
				// console.log("adding triggerevents to: "+props.name);
				// console.log(props);
				
				if (!_.isEmpty(props.attributes.trigger_event)) {
					if ( _.has(props.attributes.trigger_event, 'onload') && props.attributes.trigger_event.onload === 'hide' ) {
						style = el( 'style', { className: 'trigger-event-preview' }, "#block-"+props.clientId+" { opacity: 0.35 } " );
					}
				}
				
				// add trigger event inspector controls
				sidebar = greyd.trigger.triggerEventControls(props);
			}

			/**
			 * Trigger action support.
			 */
			if (_.has(block_type.attributes, 'trigger')) {
				extend = true;
				// console.log("adding triggerpicker to: "+props.name);
				// console.log(props);

				if (!_.isEmpty(props.attributes.trigger)) {
					const newGreydClass = greyd.tools.getGreydClass( props );
					if ( props.attributes?.greydClass !== newGreydClass ) {
						props.setAttributes( { greydClass: newGreydClass } );
					}
				}

				// add trigger picker toolbar controls
				toolbar = greyd.trigger.triggerPickerControls(props);
			}

			if (extend) {
				
				return el( wp.element.Fragment, { }, [
					// toolbar
					toolbar && el( wp.blockEditor.BlockControls, { }, [
						toolbar
					] ),
					// original block
					style ? style : null,
					el( BlockEdit, props ),
					// sidebar bottom
					sidebar && el( wp.blockEditor.InspectorControls, { }, [
						sidebar
					] ), 
				] );

			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter( 
		'editor.BlockEdit', 
		'greyd/hook/trigger/edit', 
		editBlockHook 
	);

} )( window.wp );