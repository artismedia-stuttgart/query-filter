import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import { __ } from '@wordpress/i18n';

registerBlockType('greyd/search-filter-buttons', {
	attributes: {
		inherit: { type: "boolean", default: false },
		parentPosttype: { type: 'string', default: '' },
		filterBy: { type: 'string', default: '' },
		multiselect: { type: "boolean", default: false },
		showCount: { type: "boolean", default: false },
		className: { type: 'string', default: '' },
		greydClass: { type: "string", default: "" },
		label: {
			type: 'string',
			default: ''
		},
		greydStyles: {
			type: 'Object',
			default: {}
		},
		customStyles: {
			type: 'Object',
			default: {}
		},
		resetButton: {
			type: 'Object',
			default: {}
		},
	},
	"styles": [
		{
			"name": "radio",
			"label": __( "Radio buttons", "greyd_hub" ),
			"isDefault": true
		},
		{
			"name": "checkbox",
			"label": __( "Checkbox", "greyd-hub" ),
		},
		{
			"name": "switch",
			"label": __( "iOS switch", "greyd_hub" ),
		},
		{
			"name": "tabs",
			"label": __( "Tabs", "greyd_hub" ),
		},
		{
			"name": "chips",
			"label": __( "Chips", "greyd_hub" ),
		},
		{
			"name": "prim",
			"label": __( "Primary buttons", "greyd_hub" ),
		},
		{
			"name": "sec",
			"label": __( "Secondary buttons", "greyd_hub" ),
		},
		{
			"name": "trd",
			"label": __( "Alternative buttons", "greyd_hub" ),
		},
		{
			"name": "clear",
			"label": __( "Clear", "greyd_hub" ),
		}
	],
	usesContext: [
		'greyd/search/posttype',
		'greyd/search/inherit'
	],
	edit: edit,
	save: () => null,
});