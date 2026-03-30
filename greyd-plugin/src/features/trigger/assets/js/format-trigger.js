/**
 * Register block editor RichText Trigger format.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	wp.richText.registerFormatType( 'greyd/trigger', {
		title: __('Trigger', 'greyd_hub'),
		tagName: 'span',
		className: 'is-button',
		attributes: {
			class: 'class',
			type: 'data-type',
			params: 'data-params',
		},
		edit: function ( props ) {
			
			
			// get the selected block
			var { parent } = wp.data.useSelect(select => ({
				parent: select('core/block-editor').getSelectedBlock()
			}));

			// don't show trigger in core button RichText
			if ( parent.name == 'core/button' ) return null;

			// get the anchor for positioning the popover
			var anchor = _.has( wp.richText, 'useAnchor' ) ? {
				/**
				 * wp.richText.useAnchor is not supported in Gutenberg
				 * Version prior to 14.0.
				 */
				anchor: wp.richText.useAnchor( {
					editableContentElement: props.contentRef.current,
					value: props.value,
					settings: {
						tagName: 'span',
						className: 'is-button',
						name: 'greyd/trigger'
					}
				} )
			} : {
				anchorRef: _.isEmpty(props.value.text) ? null : wp.richText.useAnchorRef( {
					ref: props.contentRef,
					value: props.value,
					settings: {
						tagName: 'span',
						className: 'is-button',
						name: 'greyd/trigger'
					}
				} )
			};

			// get atts
			var { class: className, type, params } = props.activeAttributes;
			if ( !_.isEmpty( params ) ) {
				params = JSON.parse( params );
			}
			// console.log(props);
			// console.log(className, type, params);

			// 
			var triggerProps = {
				clientId: parent.clientId,
				attributes: { },
				setAttributes: function( newTrigger ) {
					// console.log(newTrigger);
					var trigger = { class: className ? className : 'link' };
					if (_.has(newTrigger, 'trigger') && _.has(newTrigger.trigger, 'type')) {
						trigger.type = newTrigger.trigger.type;
						if (_.has(newTrigger.trigger, 'params'))
							trigger.params = JSON.stringify(newTrigger.trigger.params);
					}
					props.onChange(
						wp.richText.applyFormat( props.value, {
							type: 'greyd/trigger',
							attributes: trigger
						} )
					);

				},
				isSelected: true // props.isActive
			}
			if (type) {
				triggerProps.attributes.trigger = { type: type };
				triggerProps.attributes.trigger.params = params ? params : "";
			}

			const {
				triggerType,
				active,
				setActive,
				edit,
				popoverContent
			} = greyd.trigger.triggerPickerControlsLogic(triggerProps);
	
			if (props.isActive && !active) setActive(true);
			if (!props.isActive && active) setActive(false);
			

			return [
				el( wp.blockEditor.RichTextToolbarButton, {
					name: 'link',
					icon: el( greyd.components.GreydIcon, { icon: 'external' } ),
					title: __('Trigger', 'greyd_hub'),
					onClick: function() {
						if (props.isActive) {
							// console.log( 'remove trigger' );
							props.onChange(
								wp.richText.removeFormat( props.value, 'greyd/trigger' )
							);
						}
						else {
							// console.log( 'set trigger' );
							var atts = {};
							if (className) atts.class = className;
							if (type) {
								atts.type = type;
								if (params) atts.params = JSON.stringify(params);
							}
							props.onChange(
								wp.richText.applyFormat( props.value, {
									type: 'greyd/trigger',
									attributes: atts
								} )
							);
							setTimeout(function() {
								/**
								 * todo: when applying format to selection, 
								 * the popover is at the wrong position (0/0). 
								 */
								// console.log("setting focus to element ...");
								props.contentRef.current.focus();
							}, 0);
						}
						// props.onFocus();
						// props.contentRef.current.focus();
					},
					isActive: props.isActive,
				} ),
				props.isActive && el( wp.components.Popover, {
					className: "components-greyd-dropdown__content"+(_.isEmpty(triggerType) || edit ? ' is-edit' : ''),
					placement: "bottom-start",
					...anchor,
					// focusOnMount: props.contentRef.current
				}, [
					// trigger components
					...popoverContent(),
					// select style
					!_.isEmpty(triggerType) && !edit && el( greyd.components.OptionsControl, {
						style: { margin: "0px 12px 4px", width: "auto" },
						value: className,
						options: [
							{ value: 'link is-style-link-prim', label: __("Primary link", 'greyd_hub') },
							{ value: 'link is-style-link-sec', label: __("Secondary link", 'greyd_hub') },
							{ value: 'button is-style-prim small', label: __("Primary button", 'greyd_hub') },
							{ value: 'button is-style-sec small', label: __("Secondary button", 'greyd_hub') },
							{ value: 'button is-style-trd small', label: __("Alternative button", 'greyd_hub') },
						],
						onChange: function(value) { 
							// console.log("select", value);
							var trigger = { class: value, type: type };
							if ( !_.isEmpty( params ) ) {
								trigger.params = JSON.stringify( params );
							}
							props.onChange(
								wp.richText.applyFormat( props.value, {
									type: 'greyd/trigger',
									attributes: trigger
								} ) 
							);
						},
					} ),
				] )
			];
		},
	} );

} )( window.wp );