// import '@wordpress/block-editor';
import { InspectorControls,	useBlockProps } from '@wordpress/block-editor';
import { BaseControl, PanelBody, SelectControl, ToggleControl, Tip, Placeholder, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { has, isEmpty } from 'lodash';

import { getFilterOptions, isButton, isChips, isTabs, getTerms } from './utils.js';

// Import Greyd Components
import '../../../blocks/assets/js/components.js';
import './editor.css';


const edit = ( props ) => {

	const {
		setAttributes,
		attributes: atts
	} = props;

	const {
		parentPosttype : postType
	} = atts;

	const newGreydClass = greyd.tools.getGreydClass( props );
	if ( props.attributes?.greydClass !== newGreydClass ) {
		props.setAttributes( { greydClass: newGreydClass } );
	}

	// inherit args from search container
	const newAtts = {};
	if ( props.context[ 'greyd/search/posttype' ] && !isEmpty( props.context[ 'greyd/search/posttype' ] ) && atts.parentPosttype !== props.context[ 'greyd/search/posttype' ] ) {
		newAtts.parentPosttype = props.context[ 'greyd/search/posttype' ];
	}
	if ( atts.inherit !== props.context[ 'greyd/search/inherit' ] ) {
		newAtts.inherit = props.context[ 'greyd/search/inherit' ];
	}

	if ( !isEmpty(newAtts) ) {
		setAttributes( newAtts );
	}

	const posttypes = [
		{
			label: __( 'Post' ),
			value: 'post'
		},
		{
			label: __( 'Page' ),
			value: 'page'
		},
		...greyd.data.post_types.filter( posttype => has( posttype.arguments, 'search' ) && posttype.arguments.search === "search" ).map( posttype => {
			return {
				label: posttype.title,
				value: posttype.slug
			}
		} )
	];
	const getPosttypeLabel = ( value ) => {
		if ( value == 'post' ) return __( 'Post', 'greyd_hub' );
		if ( value == 'page' ) return __( 'Page', 'greyd_hub' );
		var pt = greyd.data.post_types.filter( posttype => posttype.slug === value );
		return pt?.length == 1 ? pt[0].title : value;
	};

	const filterOptions = getFilterOptions( postType );

	const wrapperClass = classNames( 
		atts.greydClass, 
		{ buttons: isButton( atts ) },
		{ tabs: isTabs( atts ) },
		{ chips: isChips( atts ) }
	); 

	const extraClass = classNames(
		{ button: isButton( atts ) },
		{ tab: isTabs( atts ) },
		{ chip: isChips( atts ) }
	);

	// use state for active term
	const [ activeTerms, setActiveTerms ] = useState( [] );

	const blockProps = useBlockProps({ className: wrapperClass });
	
	const terms = getTerms( atts.filterBy, postType );

	// reset button
	const resetButton = {
		enabled: atts?.resetButton?.enabled,
		label: atts?.resetButton?.label && atts?.resetButton?.label.length > 0 ? atts?.resetButton?.label : __( "Select All", 'greyd_hub' ),
		position: atts?.resetButton?.position && atts?.resetButton?.position.length > 0 ? atts?.resetButton?.position : "before",
		defaultActive: atts?.resetButton?.defaultActive
	}

	return (
		<>
			<InspectorControls group="settings">
				
				<PanelBody title={ __( 'Filter', 'greyd_hub' ) }>

					{ ( !props.context[ 'greyd/search/posttype' ] || isEmpty(props.context['greyd/search/posttype']) ) && <BaseControl>
						<SelectControl
							label={ __( "Post type", 'greyd_hub' ) }
							value={ postType }
							options={ posttypes }
							onChange={ value => setAttributes( { parentPosttype: value } ) }
						/>
					</BaseControl> }
					
					{ !isEmpty(postType) && <>

						{ ( props.context[ 'greyd/search/posttype' ] && !isEmpty(props.context['greyd/search/posttype']) ) && <BaseControl>
							<Tip>
								<p>{ __( 'The selected post type for the search is: ', 'greyd_hub') }<strong>{ getPosttypeLabel( props.context[ 'greyd/search/posttype' ] ) }</strong></p>
							</Tip>
							</BaseControl> }

						{ filterOptions.length == 1 && <BaseControl>
							<Tip>{ __( "Unfortunately, there are no filterable taxonomies available for the selected post type.", 'greyd_hub' ) }</Tip>
						</BaseControl> }

						{ filterOptions.length > 1 && <>
							<SelectControl
								label={__( "Select filter type", 'greyd_hub' )}
								value={ atts.filterBy }
								options={ filterOptions }
								onChange={ value => setAttributes( { filterBy: value } ) }
							/>

							<ToggleControl
								label={ __( "Enable multiselect", 'greyd_hub' ) }
								help={ __( "Users can select multiple filters at once", 'greyd_hub' ) }
								checked={ atts.multiselect }
								onChange={ value => {
									if ( !value ) setActiveTerms( [] );
									props.setAttributes({ multiselect: value })
								} }
							/>
		
							<ToggleControl
								label={ __( "Show count", 'greyd_hub' ) }
								help={ __( "Show the number of posts that match the filter", 'greyd_hub' ) }
								checked={ atts.showCount }
								onChange={ value => props.setAttributes({ showCount: value }) }
							/>
						</> }
					</> }
				</PanelBody>
				
				<PanelBody title={ __( 'Reset / Select All', 'greyd_hub' ) } initialOpen={ true }>

					<ToggleControl
						label={ __( "Enable Reset", 'greyd_hub' ) }
						help={ __( "Add an option to reset all filters, essentially selecting all options.", 'greyd_hub' ) }
						checked={ atts?.resetButton?.enabled }
						onChange={ value => {
							if ( value ) {
								setAttributes( { resetButton: { ...atts.resetButton, enabled: true } } );
							} else {
								setAttributes( { resetButton: { ...atts.resetButton, enabled: false } } );
							}
						} }
					/>
					
					{ resetButton?.enabled && <>
						<TextControl
							label={ __( "Label", 'greyd_hub' ) }
							value={ atts?.resetButton?.label }
							onChange={ value => setAttributes( { resetButton: { ...atts.resetButton, label: value } } ) }
						/>
						<greyd.components.ButtonGroupControl
							label={ __( "Position", 'greyd_hub' ) }
							value={ atts?.resetButton?.position }
							options={ [
								{ label: __( "Before", 'greyd_hub' ), value: 'before' },
								{ label: __( "After", 'greyd_hub' ), value: 'after' },
							] }
							onChange={ value => setAttributes( { resetButton: { ...atts.resetButton, position: value } } ) }
						/>
						<ToggleControl
							label={ __( "Active on load", 'greyd_hub' ) }
							help={ __( "The option will appear selected on loading if no other option is pre-selected.", 'greyd_hub' ) }
							checked={ atts?.resetButton?.defaultActive }
							onChange={ value => {
								if ( value ) setActiveTerms( [] );
								setAttributes( { resetButton: { ...atts.resetButton, defaultActive: value } } )
							} }
						/>
					</> }

				</PanelBody>

			</InspectorControls>
			<InspectorControls group="styles">
				<greyd.components.StylingControlPanel 
					title={__( 'Layout', 'greyd_hub' )}
					supportsResponsive={true}
					supportsActive={true}
					blockProps={props}
					controls={[
						{
							label: __( "Arrangement", 'greyd_hub' ),
							attribute: "flexDirection",
							control: greyd.components.ButtonGroupControl,
							options: [
								{ label: __( "Alongside", 'greyd_hub' ), value: 'row' },
								{ label: __( "Below", 'greyd_hub' ), value: 'column' },
							]
						},
						{
							label: __( "Alignment", 'greyd_hub' ),
							attribute: "alignItems",
							control: greyd.components.ButtonGroupControl,
							options: [
								{ label: __( "Left", 'greyd_hub' ), value: 'flex-start' },
								{ label: __( "Center", 'greyd_hub' ), value: 'center' },
								{ label: __( "Right", 'greyd_hub' ), value: 'flex-end' },
								{ label: __( "Spreaded", 'greyd_hub' ), value: 'space-between' },
							]
						},
						{
							label: __( "Space between", 'greyd_hub' ),
							attribute: "--greyd-filter-buttons-gap",
							control: greyd.components.RangeUnitControl,
							supportsPresets: true,
						}
					]}
				/>

				<greyd.components.CustomButtonStyles
					blockProps={props}
					parentAttr="customStyles"
					supportsActive={true}
				/>
	
			</InspectorControls>


			<div { ...blockProps }>

				{ terms.length > 0 && resetButton?.enabled && resetButton?.position == "before" && <div
					className={ classNames(
						"greyd_filter_button reset-button" + ( resetButton?.defaultActive && isEmpty(activeTerms) ? " is-active" : "" ),
						atts.className,
						extraClass
					) }
					onClick={ () => setActiveTerms( [] ) }
				>
					<input type="radio" />
					<span className='option'></span>
					<span className='label'>{ resetButton?.label }</span>
				</div> }
			
				{ terms.length > 0 && terms.map( (term) => {
					const isActive = activeTerms.includes( term.id );
					return <div
						className={ classNames(
							"greyd_filter_button" + ( isActive ? " is-active" : "" ),
							atts.className,
							extraClass
						) }
						onClick={ () => {
							if ( isActive ) {
								setActiveTerms( atts.multiselect ? activeTerms.filter( id => id !== term.id ) : [] );
							} else {
								setActiveTerms( atts.multiselect ? [ ...activeTerms, term.id ] : [ term.id ] );
							}
						} }
					>
						<input type="radio" />
						<span className='option'></span>
						<span className='label'>{term.title}{ atts.showCount && <span className='count'> ({term.count})</span> }</span>
					</div>
				} ) }

				{ terms.length > 0 && resetButton?.enabled && resetButton?.position == "after" && <div
					className={ classNames(
						"greyd_filter_button reset-button" + ( resetButton?.defaultActive && isEmpty(activeTerms) ? " is-active" : "" ),
						atts.className,
						extraClass
					) }
					onClick={ () => setActiveTerms( [] ) }
				>
					<input type="radio" />
					<span className='option'></span>
					<span className='label'>{ resetButton?.label }</span>
				</div> }

				{ terms.length == 0 && <Placeholder
						// icon={ <BlockIcon icon={ 'forms' } /> }
						label={ __( "Advanced Filter", 'greyd_hub' ) }
						// instructions={placeHolderMessage}
						icon="forms"
						// className="is-large"
					>
						{ filterOptions.length > 1 && <div style={{ minWidth: "200px" }}>
							<p>{ __( "Select a filter type to display the filter buttons.", 'greyd_hub' ) }</p>
							<SelectControl
								// label={__( "Select filter type", 'greyd_hub' )}
								value={ atts.filterBy }
								options={ filterOptions }
								onChange={ value => setAttributes( { filterBy: value } ) }
							/> 
						</div> }
						
						{ filterOptions.length == 1 && <BaseControl>
							<Tip>{ __( "Unfortunately, there are no filterable taxonomies available for the selected post type.", 'greyd_hub' ) }</Tip>
						</BaseControl> }
					</Placeholder> }
			</div>
			<greyd.components.RenderPreviewStyles 
				selector={atts.greydClass}
				styles= {
					{
						// ".wp-block-greyd-search-filter-buttons": atts.greydStyles
						".wp-block-greyd-search-filter-buttons": greyd.tools.fixResponsiveFlexAlignment( {
							...atts.greydStyles,
							flexDirection: atts?.greydStyles?.flexDirection || "row",
						} )
					}
				}
			/>
			<greyd.components.RenderPreviewStyles
				selector={atts.greydClass + " .greyd_filter_button"}
				activeSelector={atts.greydClass + " .greyd_filter_button.is-active"}
				styles= {
					{"": props.attributes.customStyles}
				}
				important={true}
			/>
		</>
	);
};



export default edit;