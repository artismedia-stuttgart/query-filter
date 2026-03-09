import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { label, layout } = attributes;

	const sortOptions = [
		{ label: 'Chronologisch (neueste zuerst)', value: 'date DESC' },
		{ label: 'Chronologisch (älteste zuerst)', value: 'date ASC' },
		{ label: 'Alphabetisch (A-Z)', value: 'title ASC' },
		{ label: 'Alphabetisch (Z-A)', value: 'title DESC' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Sortier-Einstellungen', 'query-filter' ) }
				>
					<TextControl
						label={ __( 'Beschriftung', 'query-filter' ) }
						value={ label }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<SelectControl
						label={ __( 'Darstellung', 'query-filter' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Links', 'query-filter' ),
								value: 'links',
							},
							{
								label: __(
									'Dropdown (Select)',
									'query-filter'
								),
								value: 'dropdown',
							},
						] }
						onChange={ ( layout ) => setAttributes( { layout } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: 'wp-block-query-filter-sort',
				} ) }
			>
				<div className="sorting-links-wrapper">
					{ label && (
						<span className="sorting-label">{ label }</span>
					) }
					{ layout === 'links' ? (
						<ul className="sorting-links">
							{ sortOptions.map( ( option ) => (
								<li
									key={ option.value }
									className="sorting-item"
								>
									<a
										href="#"
										onClick={ ( e ) => e.preventDefault() }
									>
										{ option.label }
									</a>
								</li>
							) ) }
						</ul>
					) : (
						<div className="custom-select">
							<div className="select-selected">
								<span>{ sortOptions[ 0 ].label }</span>
								<span className="select-icon"></span>
							</div>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
