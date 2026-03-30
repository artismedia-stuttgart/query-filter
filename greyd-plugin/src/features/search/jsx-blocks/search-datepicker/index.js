import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import { __ } from '@wordpress/i18n';

registerBlockType('greyd/search-datepicker', {
	attributes: {
		custom: {
			type: 'boolean',
			default: false
		},
		customStyles: {
			type: 'object',
			default: {}
		},
		dateFormat: {
			type: 'string',
			default: ''
		},
		datepickerStyles: {
			type: 'object',
			default: {}
		},
		datepickerActiveStyles: {
			type: 'object',
			default: {}
		},
		datepickerRangeButtonStyles: {
			type: 'object',
			default: {}
		},
		enableTime: {
			type: 'boolean',
			default: false
		},
		filterBy: {
			type: 'string',
			default: 'post_date'
		},
		greydClass: {
			type: 'string',
			default: ''
		},
		greydStyles: {
			type: 'object',
			default: {}
		},
		inherit: {
			type: 'boolean',
			default: false
		},
		keepOpen: {
			type: 'boolean',
			default: false
		},
		label: {
			type: 'string',
			default: ''
		},
		labelStyles: {
			type: 'object',
			default: {}
		},
		locale: {
			type: 'string'
		},
		maxDate: {
			type: 'string',
			default: ''
		},
		field: {
			type: 'string',
			default: ''
		},
		minDate: {
			type: 'string',
			default: ''
		},
		mode: {
			type: 'string',
			default: 'range'
		},
		parentPostType: {
			type: 'string',
			default: ''
		},
		placeholder: {
			type: 'string',
			default: __( 'Pick a date', 'greyd_hub' )
		},
		position: {
			type: 'string',
			default: 'auto left'
		},
		ranges: {
			type: 'object',
			default: {}
		},
		time_24hr: {
			type: 'boolean',
			default: false
		},
		weekNumbers: {
			type: 'boolean',
			default: false
		},
	},
	styles: [
		{
			name: 'prim',
			label: __( 'Primary field', 'greyd_hub' ),
			isDefault: true
		},
		{
			name: 'sec',
			label: __( 'Secondary field', 'greyd_hub' )
		},
	],
	usesContext: [
		'greyd/search/posttype',
		'greyd/search/inherit'
	],
	edit: edit,
	save: () => null,
});