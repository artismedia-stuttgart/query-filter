import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	FormTokenField,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
	const { postTypes, emptyLabel, label, showLabel } = attributes;

	const availablePostTypes = useSelect( ( select ) => {
		const results = (
			select( 'core' ).getPostTypes( { per_page: 100 } ) || []
		).filter( ( postType ) => postType.viewable );

		return results;
	}, [] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Beitragstyp-Einstellungen', 'query-filter' ) }
				>
					<FormTokenField
						label={ __(
							'Beitragstypen auswählen',
							'query-filter'
						) }
						value={
							postTypes
								? postTypes.map(
										( slug ) =>
											availablePostTypes.find(
												( pt ) => pt.slug === slug
											)?.labels.name || slug
								  )
								: []
						}
						suggestions={ availablePostTypes.map(
							( pt ) => pt.labels.name
						) }
						onChange={ ( names ) =>
							setAttributes( {
								postTypes: names.map(
									( name ) =>
										availablePostTypes.find(
											( pt ) => pt.labels.name === name
										)?.slug || name
								),
							} )
						}
					/>
					<TextControl
						label={ __( 'Beschriftung', 'query-filter' ) }
						value={ label }
						help={ __(
							'Wenn leer, wird keine Beschriftung angezeigt',
							'query-filter'
						) }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<ToggleControl
						label={ __( 'Beschriftung anzeigen', 'query-filter' ) }
						checked={ showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					<TextControl
						label={ __(
							'Beschriftung für "Alle"',
							'query-filter'
						) }
						value={ emptyLabel }
						placeholder={ __( 'Alle', 'query-filter' ) }
						onChange={ ( emptyLabel ) =>
							setAttributes( { emptyLabel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: 'wp-block-query-filter-post-type',
				} ) }
			>
				{ showLabel && (
					<label className="wp-block-query-filter-post-type__label wp-block-query-filter__label">
						{ label }
					</label>
				) }
				<ul className="wp-block-query-filter-post-type__list wp-block-query-filter__list">
					<li className="wp-block-query-filter-post-type__item wp-block-query-filter__item is-active">
						<a href="#">
							<span className="wp-block-query-filter__icon"></span>
							<span className="wp-block-query-filter__label-text">
								{ emptyLabel || __( 'Alle', 'query-filter' ) }
							</span>
						</a>
					</li>
					{ ( postTypes || [] ).map( ( slug ) => (
						<li
							key={ slug }
							className="wp-block-query-filter-post-type__item wp-block-query-filter__item"
						>
							<a href="#">
								<span className="wp-block-query-filter__icon"></span>
								<span className="wp-block-query-filter__label-text">
									{ availablePostTypes.find(
										( pt ) => pt.slug === slug
									)?.labels.name || slug }
								</span>
							</a>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
}
