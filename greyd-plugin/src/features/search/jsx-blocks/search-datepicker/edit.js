// WordPress dependencies
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { CheckboxControl, PanelBody, RadioControl, SelectControl, TextControl, Tip, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Flatpickr related imports
import flatpickr from "flatpickr";
import { english } from "flatpickr/dist/l10n/default.js"
import { German } from "flatpickr/dist/l10n/de.js"
import './flatpickr-light.css';
import { predefinedRanges } from "./predefinedRange.js";

// Editor Styles
import './editor.css';

// Import Greyd Components
import '../../../blocks/assets/js/components.js';

const edit = ( props ) => {
	// Set attributes and setAttributes from props
	const { attributes, setAttributes } = props;

	// generate attributes
	const newGreydClass = greyd.tools.getGreydClass( props );
	if ( props.attributes?.greydClass !== newGreydClass ) {
		props.setAttributes( { greydClass: newGreydClass } );
	}

	// Define the available attributes as variables
	const atts = attributes;

	// inherit args from search container
	const newAtts = {};
	if ( props.context[ 'greyd/search/posttype' ] && atts.parentPostType !== props.context[ 'greyd/search/posttype' ] ) {
		newAtts.parentPostType = props.context[ 'greyd/search/posttype' ];
	}
	if ( atts.inherit !== props.context[ 'greyd/search/inherit' ] ) {
		newAtts.inherit = props.context[ 'greyd/search/inherit' ];
	}

	if ( !_.isEmpty(newAtts) ) {
		setAttributes( newAtts );
	}

	let postType = '';
	if ( atts.inherit ) {
		const ptName = greyd.data.post_name.split( "-" );
		postType = ptName[ 1 ];
	} else if ( !_.isEmpty( atts.parentPostType ) ) {
		postType = '' + atts.parentPostType; // clone without reference
	}

	const allRanges = {
		'Today': {
			'label': __( 'Today', 'greyd_hub' ),
			'state': false
		},
		'Next 7 Days': {
			'label': __( 'Next 7 days', 'greyd_hub' ),
			'state': false
		},
		'Last 7 Days': {
			'label': __( 'Last 7 days', 'greyd_hub' ),
			'state': false
		},
		'Next 30 Days': {
			'label': __( 'Next 30 days', 'greyd_hub' ),
			'state': false
		},
		'Last 30 Days': {
			'label': __( 'Last 30 days', 'greyd_hub' ),
			'state': false
		},
		'This Week': {
			'label': __( 'This week', 'greyd_hub' ),
			'state': false
		},
		'Next Week': {
			'label': __( 'Next week', 'greyd_hub' ),
			'state': false
		},
		'Last Week': {
			'label': __( 'Last week', 'greyd_hub' ),
			'state': false
		},
		'This Month': {
			'label': __( 'This month', 'greyd_hub' ),
			'state': false
		},
		'Next Month': {
			'label': __( 'Next month', 'greyd_hub' ),
			'state': false
		},
		'Last Month': {
			'label': __( 'Last month', 'greyd_hub' ),
			'state': false
		},
		'This Year': {
			'label': __( 'This year', 'greyd_hub' ),
			'state': false
		},
		'Next Year': {
			'label': __( 'Next year', 'greyd_hub' ),
			'state': false
		},
		'Last Year': {
			'label': __( 'Last year', 'greyd_hub' ),
			'state': false
		}
	};
	
	// Filter the ranges object to only include the ones where the status is true but keep the entire object
	const activeRanges = Object.keys( allRanges ).reduce( ( acc, key ) => {
		if ( atts.ranges[key] && atts.ranges[key]?.state ) {
			acc[key] = allRanges[key];
		}
		return acc;
	}, {} );

	// Get the fields for the dynamic meta field dropdown
	const getFields = function() {
		var final = [];
		for (var i=0; i<greyd.data.post_types.length; i++) {
			if ( greyd.data.post_types[i]['slug'] == postType && _.has(greyd.data.post_types[i], 'fields') ) {
				for (var j=0; j<greyd.data.post_types[i]['fields'].length; j++) {
					var field = greyd.data.post_types[i]['fields'][j];
					if (
						!_.has(field, "type") ||
						field["type"] === 'hr' ||
						field["type"] === 'space' ||
						field["type"] === 'headline' ||
						field["type"] === 'descr'
					) continue;
					final.push( { value: field['name'], label: field['label'] } );
				}
				break;
			}
		}
		if (final.length == 0) {
			final.push( { value: '', label: sprintf( __( '\"%s\" has no meta fields', 'greyd_hub' ), postType ), disabled: true } );
		}
		else {
			final.unshift( { value: '', label: __( 'Select meta field', 'greyd_hub' ) } );
		}
		return final;
	};

	// Initialize datepicker - this might not work if multiple datepickers are on the same page
	const datepickerInputs = document.querySelectorAll('.greyd-datepicker-input');
	for (const input of datepickerInputs) {	
		new flatpickr(input, {
			mode: atts.mode ? atts.mode : 'range',
			dateFormat: atts.dateFormat ? atts.dateFormat : '',
			enableTime: atts.mode !== 'range' && atts.enableTime ? atts.enableTime : false,
			locale: atts.locale === 'de' ? German : english,
			maxDate: atts.maxDate ? atts.maxDate : '',
			minDate: atts.minDate ? atts.minDate : '',
			position: atts.position ? atts.position : 'auto left',
			plugins: [ predefinedRanges( atts.locale ) ],
			ranges: activeRanges,
			rangesOnly: false,
			time_24hr: atts.time_24hr ? atts.time_24hr : false,
			weekNumbers: atts.weekNumbers ? atts.weekNumbers : false,
		});
	}

	// Set the styles for the datepicker
	const dpStyles = atts.datepickerStyles;

	const style = `
		.flatpickr-calendar {
			${dpStyles.color ? `color: ${dpStyles.color};` : ''}
			${dpStyles.background ? `background-color: ${dpStyles.background};` : ''}
			${dpStyles.padding ? Object.keys(dpStyles.padding).reduce( ( acc, key ) => {
				if ( dpStyles.padding[key] && dpStyles.padding[key] !== 'nullpx' ) {
					acc += `padding-${key}: ${dpStyles.padding[key]};\n`;
				}
				return acc;
			}, '') : '' }
			${ dpStyles.border ? Object.keys(dpStyles.border).reduce( ( acc, key ) => {
				if ( dpStyles.border[key] && ! dpStyles.border[key].startsWith('0px') ) {
					acc += `border-${key}: ${dpStyles.border[key]};\n`;
				}
				return acc;
			}, '') : '' }
			${ dpStyles.borderRadius ? Object.keys(dpStyles.borderRadius).reduce( ( acc, key ) => {
				if ( dpStyles.borderRadius[key] && dpStyles.borderRadius[key] !== 'nullpx' ) {
					acc += `border-${key}-radius: ${dpStyles.borderRadius[key]};\n`;
				}
				return acc;
			}, '') : '' }
		}

		.flatpickr-calendar.arrowTop:after,
		.flatpickr-calendar.arrowTop:before {
			${dpStyles.background ? `border-bottom-color: ${dpStyles.background};` : ''}
		}

		.flatpickr-calendar.arrowBottom:after,
		.flatpickr-calendar.arrowBottom:before {
			${dpStyles.background ? `border-top-color: ${dpStyles.background};` : ''}
		}
	`;

	// Set the styles for the datepicker range buttons
	const dpRBStyles = atts.datepickerRangeButtonStyles;
	// console.log('test', Object.keys(dpRBStyles.border).length);
	const styleRB = `
		.flatpickr-calendar .flatpickr-predefined-ranges button {
			${dpRBStyles.color ? `color: ${dpRBStyles.color};` : ''}
			${dpRBStyles.background ? `background-color: ${dpRBStyles.background};` : ''}
			${dpRBStyles.padding ? Object.keys(dpRBStyles.padding).reduce( ( acc, key ) => {
				if ( dpRBStyles.padding[key] && dpRBStyles.padding[key] !== 'nullpx' ) {
					acc += `padding-${key}: ${dpRBStyles.padding[key]};\n`;
				}
				return acc;
			}, '') : '' }
			${ dpRBStyles.border ? Object.keys(dpRBStyles.border).reduce( ( acc, key ) => {
				if ( dpRBStyles.border[key] && ! dpRBStyles.border[key].startsWith('0px') ) {
					acc += `border-${key}: ${dpRBStyles.border[key]};\n`;
				}
				return acc;
			}, '') : '' }
			${ dpRBStyles.borderRadius ? Object.keys(dpRBStyles.borderRadius).reduce( ( acc, key ) => {
				if ( dpRBStyles.borderRadius[key] && dpRBStyles.borderRadius[key] !== 'nullpx' ) {
					acc += `border-${key}-radius: ${dpRBStyles.borderRadius[key]};\n`;
				}
				return acc;
			}, '') : '' }
		}
	`;

	// Set the styles for the datepicker active state
	const dpActiveStyles = atts.datepickerActiveStyles;
	const styleActive = dpActiveStyles.color || dpActiveStyles.background ? `
		.flatpickr-day.selected,
		.flatpickr-day.startRange,
		.flatpickr-day.endRange,
		.flatpickr-day.selected.inRange,
		.flatpickr-day.startRange.inRange,
		.flatpickr-day.endRange.inRange,
		.flatpickr-day.selected:focus,
		.flatpickr-day.startRange:focus,
		.flatpickr-day.endRange:focus,
		.flatpickr-day.selected:hover,
		.flatpickr-day.startRange:hover,
		.flatpickr-day.endRange:hover,
		.flatpickr-day.selected.prevMonthDay,
		.flatpickr-day.startRange.prevMonthDay,
		.flatpickr-day.endRange.prevMonthDay,
		.flatpickr-day.selected.nextMonthDay,
		.flatpickr-day.startRange.nextMonthDay,
		.flatpickr-day.endRange.nextMonthDay,
		.flatpickr-calendar .flatpickr-predefined-ranges button.active {
			${dpActiveStyles.color ? `color: ${dpActiveStyles.color};` : ''}
			${dpActiveStyles.background ? `background: ${dpActiveStyles.background};` : ''}
		}
	` : '';

	return (
		<>
			<InspectorControls group="settings">
				<PanelBody title={ __( 'Settings', 'greyd_hub' ) }>
					<SelectControl
						label={ __( 'Select date field', 'greyd_hub' ) }
						help={ __( 'Pick the correct date field to connect the datepicker to.', 'greyd_hub' ) }
						value={ atts.filterBy }
						options={ [
							{ label: __( 'Post date', 'greyd_hub' ), value: 'post_date' },
							{ label: __( 'Meta field', 'greyd_hub' ), value: 'meta_date' },
							{ label: __( 'Dynamic meta field', 'greyd_hub' ), value: 'dynamic_meta_date' },
						] }
						onChange={ ( option ) => setAttributes( { filterBy: option } ) }
					/>
					{ atts.filterBy === 'meta_date' && (
						<div class="greyd-meta-field-control">
							<TextControl
								label={ __( 'Meta field', 'greyd_hub' ) }
								help={ __( 'Enter the name of the meta field to filter by.', 'greyd_hub' ) }
								value={ atts.field }
								onChange={ ( value ) => setAttributes( { field: value } ) }
							/>
							<Tip>
								{ __( 'Since not every post of this post type can have that specific meta field assigned, please add your preferred meta field manually. The format has to be YYYY-MM-DD / Y-m-d', 'greyd_hub' ) }
							</Tip>
						</div>
					)}
					{ atts.filterBy === 'dynamic_meta_date' && (
						<SelectControl
							label={ __( 'Dynamic meta field', 'greyd_hub' ) }
							help={ __( 'Select the dynamic meta field to filter by.', 'greyd_hub' ) }
							value={ atts.field }
							options={getFields()}
							onChange={ ( value ) => setAttributes( { field: value } ) }
						/>
					)}
					<RadioControl
						label={ __( 'Datepicker mode', 'greyd_hub' ) }
						help={ __( 'Choose between selecting date ranges or single dates.', 'greyd_hub' ) }
						selected={ atts.mode }
						options={ [
							{ label: __( 'Range', 'greyd_hub' ), value: 'range' },
							{ label: __( 'Single', 'greyd_hub' ), value: 'single' },
						] }
						onChange={ ( option ) => setAttributes( { mode: option } ) }
					/>
					<SelectControl
						label={ __( 'Datepicker position', 'greyd_hub' ) }
						help={ __( 'Choose how the datepicker should be aligned to the input field.', 'greyd_hub' ) }
						value={ atts.position }
						options={ [
							{ label: __( 'Left', 'greyd_hub' ), value: 'auto left' },
							{ label: __( 'Center', 'greyd_hub' ), value: 'auto center' },
							{ label: __( 'Right', 'greyd_hub' ), value: 'auto right' },
						] }
						onChange={ ( option ) => setAttributes( { position: option } ) }
					/>
					<greyd.components.SelectCustomControl 
						label={ __( 'Date format', 'greyd_hub' ) }
						help={ __( 'Set the format of the date. Default: Y-m-d', 'greyd_hub' ) }
						value={ atts.dateFormat }
						onChange={ ( value ) => setAttributes( { dateFormat: value } ) }
						options={ [
							{ label: __( 'Default', 'greyd_hub' ), value: '' },
							{ label: '31.12.1999', value: 'd.m.Y' },
							{ label: '31-12-1999', value: 'd-m-Y' },
							{ label: '1999-12-31', value: 'Y-m-d' },
							{ label: '12-31-1999', value: 'm-d-Y' },
							{ label: '12.31.1999', value: 'm.d.Y' },
							{ label: '31/12/1999', value: 'd/m/Y' },
							{ label: '12/31/1999', value: 'm/d/Y' },
							{ label: '31.12.99', value: 'd.m.y' },
							{ label: '12.31.99', value: 'm.d.y' },
							{ label: '31/12/99', value: 'd/m/y' },
							{ label: '12/31/99', value: 'm/d/y' },
						] }
						customPlaceholder={ __( 'Format: Y-m-d', 'greyd_hub' ) }
					/>
					<TextControl 
						label={ __( 'Placeholder', 'greyd_hub' ) }
						help={ __( 'Set the placeholder text for the input field.', 'greyd_hub' ) }
						value={ atts.placeholder }
						onChange={ ( value ) => setAttributes( { placeholder: value } ) }
					/>
					{/* <SelectControl
						label={ __( 'Language', 'greyd_hub' ) }
						help={ __( 'Select a language for the datepicker. Default: English.', 'greyd_hub' ) }
						value={ atts.locale }
						options={ [
							{ label: __( 'English', 'greyd_hub' ), value: 'en' },
							{ label: __( 'German', 'greyd_hub' ), value: 'de' },
						] }
						onChange={ ( option ) => setAttributes( { locale: option } ) }
					/> */}
					<ToggleControl
						label={ __( 'Enable week numbers', 'greyd_hub' ) }
						help={
							atts.weekNumbers
								? __( 'Week numbers are visible.', 'greyd_hub' )
								: __( 'Week numbers are hidden.', 'greyd_hub' )
						}
						checked={ atts.weekNumbers }
						onChange={ ( value ) => setAttributes( { weekNumbers: value } ) }
					/>
					{ atts.mode !== 'range' && (
						<ToggleControl
							label={ __( 'Enable time', 'greyd_hub' ) }
							help={
								atts.enableTime
									? __( 'Inputs for selecting time are visible.', 'greyd_hub' )
									: __( 'Inputs for selecting time are hidden.', 'greyd_hub' )
							}
							checked={ atts.enableTime }
							onChange={ ( value ) => setAttributes( { enableTime: value } ) }
						/>
					)}
					{ atts.mode !== 'range' && atts.enableTime && (
						<ToggleControl
							label={ __( '24 hour mode', 'greyd_hub' ) }
							help={
								atts.time_24hr
									? __( 'Time is currently displayed in 24 hour mode.', 'greyd_hub' )
									: __( 'Time is currently displayed in AM/PM mode.', 'greyd_hub' )
							}
							checked={ atts.time_24hr }
							onChange={ ( value ) => setAttributes( { time_24hr: value } ) }
						/>
					)}
					<TextControl
						label={ __( 'Minimum date', 'greyd_hub' ) }
						type="date"
						value={ atts.minDate }
						help={ __( 'Set the minimum date that can be selected. Only works with numerical date formats like Y-m-d.', 'greyd_hub' ) }
						onChange={ ( value ) => setAttributes( { minDate: value } ) }
					/>
					<TextControl
						label={ __( 'Maximum date', 'greyd_hub' ) }
						type="date"
						value={ atts.maxDate }
						help={ __( 'Set the maximum date that can be selected. Only works with numerical date formats like Y-m-d.', 'greyd_hub' ) }
						onChange={ ( value ) => setAttributes( { maxDate: value } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Ranges', 'greyd_hub' ) } initialOpen={false}>
					<div className="greyd-help-text">{ __( 'Show or hide date range options.', 'greyd_hub') }</div>
					{ Object.keys( allRanges ).map( ( key ) => (
						
						<div key={ key } className="greyd-datepicker-ranges-settings">
							<CheckboxControl
								label={ allRanges[key]?.label }
								checked={ atts.ranges[key]?.state }
								onChange={ ( value ) => setAttributes( { ranges: { ...atts.ranges, [key]: { ...atts.ranges[key], state: value } } } ) }
								style={{marginBottom: 0 + 'px'}}
							/>
						</div>
					) ) }
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<greyd.components.StylingControlPanel
					title={ __( 'Input width', 'greyd_hub' ) }
					initialOpen={ false }
					supportsResponsive={ true }
					blockProps={ props }
					controls={ [
						{
							label: __( 'Input width', 'greyd_hub' ),
							max: 800,
							attribute: "width",
							control: greyd.components.RangeUnitControl
						},
					] }
				/>
				<greyd.components.StylingControlPanel
					title={__( 'Input label', 'greyd_hub' ) }
					supportsHover={ true }
					parentAttr='labelStyles'
					blockProps={ props }
					controls={ [
						{
							label: __( 'Font size', 'greyd_hub' ),
							attribute: "fontSize",
							units: [ "px", "em", "rem" ],
							max: { px: 60, em: 3, rem: 3 },
							control: greyd.components.RangeUnitControl,
						},
						{
							label: __( 'Color', 'greyd_hub' ),
							attribute: "color",
							control: greyd.components.ColorPopupControl,
						},
					] }
				/>
				<greyd.components.AdvancedPanelBody
					title={ __( 'Individual input field', 'greyd_hub' ) }
					initialOpen={ true }
					holdsChange={ atts.custom ? true : false }
				>
					<ToggleControl
						label={ __( 'Overwrite the design of the field individually.', 'greyd_hub' ) }
						checked={ atts.custom }
						onChange={ ( value ) => setAttributes( { custom: value } ) }
					/>
					<greyd.components.CustomButtonStyles
						blockProps={ props }
						enabled={ atts.custom ? true : false }
						parentAttr='customStyles'
					/>
				</greyd.components.AdvancedPanelBody>
				<greyd.components.StylingControlPanel
					title={ __( 'Dropdown styles', 'greyd_hub' ) }
					blockProps={ props }
					parentAttr='datepickerStyles'
					controls={ [
						{
							label: __( 'Text color', 'greyd_hub' ),
							attribute: "color",
							control: greyd.components.ColorPopupControl,
						},
						{
							label: __( 'Background color', 'greyd_hub' ),
							attribute: "background",
							control: greyd.components.ColorPopupControl,
						},
						{
							label: __( 'Padding', 'greyd_hub' ),
							attribute: "padding",
							units: [ "px", "em", "rem" ],
							max: { px: 100, em: 10, rem: 10 },
							control: greyd.components.DimensionControl,
						},
						{
							label: __( 'Border', 'greyd_hub' ),
							attribute: "border",
							control: greyd.components.BorderControl,
						},
						// {
						// 	label: __( 'Border radius', 'greyd_hub' ),
						// 	attribute: "borderRadius",
						// 	units: [ "px", "em", "rem" ],
						// 	max: { px: 100, em: 10, rem: 10 },
						// 	sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
						// 	labels: {
						// 		'topLeft':		__( 'Top left', 'greyd_hub' ),
						// 		'topRight':	__( 'Top right', 'greyd_hub' ),
						// 		'bottomRight':	__( 'Bottom left', 'greyd_hub' ),
						// 		'bottomLeft': __( 'Bottom right', 'greyd_hub' ),
						// 	},
						// 	control: greyd.components.DimensionControl,
						// }
					] }
				/>
				<greyd.components.StylingControlPanel
					title={ __( 'Datepicker range button styles', 'greyd_hub' ) }
					blockProps={ props }
					parentAttr='datepickerRangeButtonStyles'
					controls={ [
						{
							label: __( 'Text color', 'greyd_hub' ),
							attribute: "color",
							control: greyd.components.ColorPopupControl,
						},
						{
							label: __( 'Background color', 'greyd_hub' ),
							attribute: "background",
							control: greyd.components.ColorPopupControl,
						},
						{
							label: __( 'Padding', 'greyd_hub' ),
							attribute: "padding",
							units: [ "px", "em", "rem" ],
							max: { px: 100, em: 10, rem: 10 },
							control: greyd.components.DimensionControl,
						},
						{
							label: __( 'Border', 'greyd_hub' ),
							attribute: "border",
							control: greyd.components.BorderControl,
						},
						// {
						// 	label: __( 'Border radius', 'greyd_hub' ),
						// 	attribute: "borderRadius",
						// 	units: [ "px", "em", "rem" ],
						// 	max: { px: 100, em: 10, rem: 10 },
						// 	sides: [ "topLeft", "topRight", "bottomRight", "bottomLeft" ],
						// 	labels: {
						// 		'topLeft':		__( 'Top left', 'greyd_hub' ),
						// 		'topRight':	__( 'Top right', 'greyd_hub' ),
						// 		'bottomRight':	__( 'Bottom left', 'greyd_hub' ),
						// 		'bottomLeft': __( 'Bottom right', 'greyd_hub' ),
						// 	},
						// 	control: greyd.components.DimensionControl,
						// }
					] }
				/>
				<greyd.components.StylingControlPanel
					title={ __( 'Datepicker active styles', 'greyd_hub' ) }
					blockProps={ props }
					parentAttr='datepickerActiveStyles'
					controls={ [
						{
							label: __( 'Text color', 'greyd_hub' ),
							attribute: "color",
							control: greyd.components.ColorPopupControl,
						},
						{
							label: __( 'Background color', 'greyd_hub' ),
							attribute: "background",
							control: greyd.components.ColorPopupControl,
						}
					] }
				/>
			</InspectorControls>
			<div {...useBlockProps()}>
				<div className={ atts.greydClass + " input-outer-wrapper" }>
					{ props.isSelected || atts.label.length > 0 ? (
						<div className={ "label_wrap" }>
							<RichText
								tagName="span" // The tag here is the element output and editable in the admin
								value={ atts.label } // Any existing content, either from the database or an attribute default
								allowedFormats={ [ 'core/bold', 'core/italic', 'core/subscript', 'core/superscript', 'greyd/versal', 'greyd/text-background' ] } // Allow the content to be made bold or italic, but do not allow other formatting options
								onChange={ ( value ) => setAttributes( { label: value } ) } // Store updated content as a block attribute
								placeholder={ __( 'Enter a label', 'greyd_hub' ) } // Display this text before any content has been added by the user
								className="label"
							/>
						</div>
					) : null }
					<input
						className={ "greyd-datepicker-input " + ( atts.className ?? '' ) }
						type="text"
						placeholder={ atts.placeholder }
					/>
					<greyd.components.RenderPreviewStyles
						blockProps={ props }
						selector={ 'wp-block#block-' + props.clientId }
						styles={ { "": atts.greydStyles } }
					/>
					<greyd.components.RenderPreviewStyles
						blockProps={ props }
						selector= { atts.greydClass + " .label" }
						styles={ { "": atts.labelStyles } }
					/>
					{ !atts.custom ? null : (
						<greyd.components.RenderPreviewStyles
							blockProps={ props }
							selector={ atts.greydClass + " .greyd-datepicker-input[type='text']" }
							styles={ { "": atts.customStyles } }
							important={ true }
						/>
					
					)}
					<style>
						{ style }
						{ styleRB }
						{ styleActive }
					</style>
				</div>
			</div>
		</>
	);
};

export default edit;