/**
 * Aria Attributes editor controls.
 * 
 * @since 2.17.0
 */

/**
 * @namespace greyd
 */
var greyd = greyd || {};
greyd.components = greyd.components || {};

( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * AriaAttributesControl component.
	 * 
	 * @property {object} value Current value.
	 * @property {string} label Label.
	 * @property {callback} onChange Callback function to be called when value is changed.
	 * 
	 * @returns {object} AriaAttributesControl component.
	 */
	greyd.components.AriaAttributesControl = class extends wp.element.Component {

		constructor(props) {
			super(props);
			this.state = {
				isAdding: false,
				newAttr: '',
				newValue: '',
				...this.getDefaultState()
			};
		}

		getDefaultState() {
			return {
				isOpen: false,
				tempValue: this.props.value || {}
			};
		}

		ariaOptions = [
			{ value: 'aria-label', label: 'aria-label', type: 'text' },
			{ value: 'aria-labelledby', label: 'aria-labelledby', type: 'text' },
			{ value: 'aria-describedby', label: 'aria-describedby', type: 'text' },
			{ value: 'aria-hidden', label: 'aria-hidden', type: 'boolean' },
			// ...add more as needed
		];

		getAttrType(attr) {
			const found = this.ariaOptions.find(opt => opt.value === attr);
			return found ? found.type : 'text';
		}

		handleAdd = () => {
			this.setState({ isAdding: true, newAttr: '', newValue: '' });
		}

		handleAttrChange = (val) => {
			this.setState({ newAttr: val, newValue: this.getAttrType(val) === 'boolean' ? false : '' });
		}

		handleValueChange = (val) => {
			this.setState({ newValue: val });
		}

		handleAddConfirm = () => {
			const { newAttr, newValue, tempValue } = this.state;
			if (!newAttr) return;
			const updated = { ...tempValue, [newAttr]: newValue };
			this.setState({ tempValue: updated, isAdding: false, newAttr: '', newValue: '' });
			this.props.onChange(updated);
		}

		handleRemove = (attr) => {
			const updated = { ...this.state.tempValue };
			delete updated[attr];
			this.setState({ tempValue: updated });
			this.props.onChange(updated);
		}

		handleFieldChange = (attr, val) => {
			const updated = { ...this.state.tempValue, [attr]: val };
			this.setState({ tempValue: updated });
			this.props.onChange(updated);
		}

		render() {
			const { label = '' } = this.props;
			const { isAdding, newAttr, newValue, tempValue } = this.state;
			const ariaOptions = this.ariaOptions.filter(opt => !(tempValue && tempValue[opt.value] !== undefined));

			return el(wp.element.Fragment, {}, [
				(label.length > 0 ? el(wp.components.BaseControl.VisualLabel, {}, label) : null),
				el('div', { className: 'greyd-aria-attributes-list' },
					Object.keys(tempValue || {}).length === 0 && el('p', {}, __('No ARIA attributes set.', 'greyd_hub')),
					Object.entries(tempValue || {}).map(([attr, val]) => {
						const type = this.getAttrType(attr);
						return el('div', { key: attr, className: 'aria-row' }, [
							el('div', { className: 'aria-label' }, attr),
							el('div', { className: 'aria-control' },
								type === 'boolean'
									? el(wp.components.ToggleControl, {
										checked: !!val,
										onChange: (v) => this.handleFieldChange(attr, v),
										style: { marginLeft: 0, marginRight: 16 }
									})
									: el(wp.components.TextControl, {
										value: val,
										onChange: (v) => this.handleFieldChange(attr, v),
										style: { marginLeft: 0, marginRight: 16 }
									})
							),
							el('div', { className: 'aria-trash' },
								el(wp.components.Button, {
									icon: 'trash',
									onClick: () => this.handleRemove(attr),
									style: { color: '#cc1818', marginLeft: 8 }
								})
							)
						]);
					})
				),
				isAdding && el('div', { className: 'add-row' }, [
					el('div', { className: 'add-row-controls' }, [
						el(wp.components.SelectControl, {
							value: newAttr,
							options: [{ label: __('Select ARIA attribute', 'greyd_hub'), value: '' }, ...ariaOptions],
							onChange: this.handleAttrChange,
							style: { minWidth: 100, marginRight: 8 }
						}),
						newAttr && this.getAttrType(newAttr) === 'boolean' && el(wp.components.ToggleControl, {
							checked: !!newValue,
							onChange: (v) => this.handleValueChange(v),
							style: { marginRight: 8 }
						}),
						newAttr && this.getAttrType(newAttr) === 'text' && el(wp.components.TextControl, {
							value: newValue,
							onChange: (v) => this.handleValueChange(v),
							placeholder: __('Value', 'greyd_hub'),
							style: { marginRight: 8 }
						}),
						newAttr && this.getAttrType(newAttr) === 'number' && el(wp.components.TextControl, {
							value: newValue,
							type: 'number',
							onChange: (v) => this.handleValueChange(v),
							placeholder: __('Value', 'greyd_hub'),
							style: { marginRight: 8 }
						})
					]),
					el('div', { className: 'add-row-buttons' }, [
						el(wp.components.Button, {
							isPrimary: true,
							onClick: this.handleAddConfirm,
							disabled: !newAttr
						}, __('Add', 'greyd_hub')),
						el(wp.components.Button, {
							isTertiary: true,
							onClick: () => this.setState({ isAdding: false, newAttr: '', newValue: '' })
						}, __('Cancel', 'greyd_hub'))
					])
				]),
				el(wp.components.Button, {
					isSecondary: true,
					icon: 'plus',
					style: { marginTop: 12 },
					onClick: this.handleAdd,
					disabled: isAdding || ariaOptions.length === 0
				}, __('Add Aria Attribute', 'greyd_hub'))
			]);
		}
	};

	/**
	 * Register aria label attribute to supported blocks.
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerAriaLabelControl = function ( settings, name ) {

		// Add aria label attribute to ALL blocks that have apiVersion
		if ( !_.has( settings, 'apiVersion' ) ) return settings;

		// do not support excluded blocks
		if ( [
			// 'core/query', // Now supported
			// 'core/post-template', // Now supported
			'greyd/conditional-content',
			'greyd/forms'
		].indexOf( name ) !== -1 ) {
			return settings;
		}

		// Add the attribute to all blocks
		if ( !_.has( settings.attributes, 'greydAriaAttributes' ) ) {
			settings.attributes.greydAriaAttributes = { type: 'object' };
		}

		// add attribute to all deprecations
		if ( typeof settings.deprecated !== 'undefined' ) {
			for (var i=0; i < settings.deprecated.length; i++) {
				if ( settings.deprecated[i] && typeof settings.deprecated[i].attributes !== 'undefined' ) {
					if ( !_.has( settings.deprecated[i].attributes, 'greydAriaAttributes' ) ) {
						settings.deprecated[i].attributes = {
							...settings.deprecated[i]?.attributes,
							greydAriaAttributes: { type: 'object' }
						}
					}
				}
			}
		}

		return settings;
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/aria-attributes',
		registerAriaLabelControl
	);

	/**
	 * Add aria label editor controls to supported blocks.
	 * 
	 * @hook editor.BlockEdit
	 */
	const addAriaLabelControl = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {

		return function ( props ) {

			const blockType = wp.blocks.getBlockType( props.name );
			
			let holdsChange = false;
			let controls = [];

			// greydAriaAttributes support - check if block has the attribute
			if ( blockType && _.has( blockType.attributes, 'greydAriaAttributes' ) ) {

				const greydAriaAttributes = _.get( props.attributes, 'greydAriaAttributes' );

				holdsChange = holdsChange || !_.isEmpty( greydAriaAttributes );
				controls.push(
					el( greyd.components.AriaAttributesControl, {
						value: greydAriaAttributes,
						onChange: ( val ) => {
							props.setAttributes( { greydAriaAttributes: val } );
						}
					} )
				);
			}
			
			// Use AdvancedPanelBody if available, otherwise use PanelBody
			const PanelComponent = greyd.components.AdvancedPanelBody || wp.components.PanelBody;

			// render
			return el( wp.element.Fragment, {}, [
				el( BlockEdit, {...props } ),
				controls.length > 0 ? el( wp.blockEditor.InspectorControls, {}, [
					el( PanelComponent, {
						title: __( 'Aria Attributes', 'greyd_hub' ),
						...( greyd.components.AdvancedPanelBody ? { holdsChange: holdsChange, initialOpen: false } : { initialOpen: false } )
					}, controls )
				] ) : null,
			] );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/aria-attributes/edit',
		addAriaLabelControl,
		99
	);

	// Debug: Log that the script is loaded
	// console.log( 'Aria Attributes editor.js Script: loaded' );

} )( window.wp );
