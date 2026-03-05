import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
	const {
		taxonomy,
		emptyLabel,
		label,
		showLabel,
		showIcons,
		iconSize,
		allLast,
		defaultTerm,
		showCount,
	} = attributes;

	const taxonomies = useSelect(
		( select ) => {
			const results = (
				select( 'core' ).getTaxonomies( { per_page: 100 } ) || []
			).filter( ( taxonomy ) => taxonomy.visibility.publicly_queryable );

			if ( results && results.length > 0 && ! taxonomy ) {
				setAttributes( {
					taxonomy: results[ 0 ].slug,
					label: results[ 0 ].name,
				} );
			}

			return results;
		},
		[ taxonomy ]
	);

	const termIcons = useSelect( ( select ) => {
		return (
			select( 'core' ).getEntityRecord( 'root', 'site' )
				?.query_filter_term_icons || {}
		);
	}, [] );

	const terms = useSelect(
		( select ) => {
			const records =
				select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, {
					number: 50,
				} ) || [];

			return records.map( ( term ) => {
				const iconId = termIcons[ term.id ];
				const iconMedia = iconId
					? select( 'core' ).getMedia( iconId )
					: null;
				return {
					...term,
					iconUrl:
						iconMedia?.media_details?.sizes?.thumbnail
							?.source_url || iconMedia?.source_url,
				};
			} );
		},
		[ taxonomy, termIcons ]
	);

	const allItem = (
		<li
			key="all"
			className="wp-block-query-filter-taxonomy__item wp-block-query-filter__item is-active"
		>
			<a href="#">
				<span
					className="wp-block-query-filter__icon"
					style={ {
						width: showIcons ? iconSize : undefined,
						height: showIcons ? iconSize : undefined,
					} }
				></span>
				<span className="wp-block-query-filter__label-text">
					{ emptyLabel || __( 'All', 'query-filter' ) }
				</span>
			</a>
		</li>
	);

	const termItems = terms.map( ( term ) => (
		<li
			key={ term.slug }
			className="wp-block-query-filter-taxonomy__item wp-block-query-filter__item"
		>
			<a href="#">
				<span
					className="wp-block-query-filter__icon"
					style={ {
						width: showIcons ? iconSize : undefined,
						height: showIcons ? iconSize : undefined,
					} }
				>
					{ showIcons && term.iconUrl && (
						<img
							src={ term.iconUrl }
							alt=""
							style={ {
								width: iconSize,
								height: iconSize,
								objectFit: 'contain',
							} }
						/>
					) }
				</span>
				<span className="wp-block-query-filter__label-text">
					{ term.name } { showCount && `(${ term.count })` }
				</span>
			</a>
		</li>
	) );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Taxonomy Settings', 'query-filter' ) }>
					<SelectControl
						label={ __( 'Select Taxonomy', 'query-filter' ) }
						value={ taxonomy }
						options={ ( taxonomies || [] ).map( ( taxonomy ) => ( {
							label: taxonomy.name,
							value: taxonomy.slug,
						} ) ) }
						onChange={ ( taxonomy ) =>
							setAttributes( {
								taxonomy,
								label: taxonomies.find(
									( tax ) => tax.slug === taxonomy
								).name,
							} )
						}
					/>
					<TextControl
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						help={ __(
							'If empty then no label will be shown',
							'query-filter'
						) }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<ToggleControl
						label={ __( 'Show Label', 'query-filter' ) }
						checked={ showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Icons', 'query-filter' ) }
						checked={ showIcons }
						onChange={ ( showIcons ) =>
							setAttributes( { showIcons } )
						}
					/>
					{ showIcons && (
						<RangeControl
							label={ __( 'Icon Size', 'query-filter' ) }
							value={ iconSize }
							onChange={ ( iconSize ) =>
								setAttributes( { iconSize } )
							}
							min={ 16 }
							max={ 128 }
						/>
					) }
					<ToggleControl
						label={ __( 'Show Post Count', 'query-filter' ) }
						checked={ showCount }
						onChange={ ( showCount ) =>
							setAttributes( { showCount } )
						}
					/>
					<ToggleControl
						label={ __( 'Sort "All" last', 'query-filter' ) }
						checked={ allLast }
						onChange={ ( allLast ) => setAttributes( { allLast } ) }
					/>
					<SelectControl
						label={ __( 'Default Selected Term', 'query-filter' ) }
						value={ defaultTerm }
						options={ [
							{ label: __( 'None', 'query-filter' ), value: '' },
							...terms.map( ( term ) => ( {
								label: term.name,
								value: term.slug,
							} ) ),
						] }
						onChange={ ( defaultTerm ) =>
							setAttributes( { defaultTerm } )
						}
					/>
					<TextControl
						label={ __( 'Empty Choice Label', 'query-filter' ) }
						value={ emptyLabel }
						placeholder={ __( 'All', 'query-filter' ) }
						onChange={ ( emptyLabel ) =>
							setAttributes( { emptyLabel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'wp-block-query-filter' } ) }>
				{ showLabel && (
					<label className="wp-block-query-filter-taxonomy__label wp-block-query-filter__label">
						{ label }
					</label>
				) }
				<ul className="wp-block-query-filter-taxonomy__list wp-block-query-filter__list">
					{ ! allLast && allItem }
					{ termItems }
					{ allLast && allItem }
				</ul>
			</div>
		</>
	);
}
