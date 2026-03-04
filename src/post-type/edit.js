import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes, context } ) {
	const { emptyLabel, label, showLabel } = attributes;

	const allPostTypes = useSelect( ( select ) => {
		return (
			( select( 'core' ).getPostTypes( { per_page: 100 } ) || [] ).filter(
				( type ) => type.viewable
			) || []
		);
	}, [] );

	let contextPostTypes = ( context.query.postType || '' )
		.split( ',' )
		.map( ( type ) => type.trim() );

	// Support for enhanced query loop block plugin.
	if ( Array.isArray( context.query.multiple_posts ) ) {
		contextPostTypes = contextPostTypes.concat(
			context.query.multiple_posts
		);
	}

	const postTypes = contextPostTypes.map( ( postType ) => {
		return (
			allPostTypes.find( ( type ) => type.slug === postType ) || {
				slug: postType,
				name: postType,
			}
		);
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Post Type Settings', 'query-filter' ) }>
					<TextControl
						label={ __( 'Label', 'query-filter' ) }
						value={ label }
						defaultValue={ __( 'Content Type', 'query-filter' ) }
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
					<label className="wp-block-query-filter-post-type__label wp-block-query-filter__label">
						{ label || __( 'Content Type', 'query-filter' ) }
					</label>
				) }
				<ul className="wp-block-query-filter-post-type__list wp-block-query-filter__list">
					<li className="wp-block-query-filter-post-type__item wp-block-query-filter__item is-active">
						<a href="#">
							<span className="wp-block-query-filter__icon"></span>
							<span className="wp-block-query-filter__label-text">
								{ emptyLabel || __( 'All', 'query-filter' ) }
							</span>
						</a>
					</li>
					{ postTypes.map( ( type ) => (
						<li
							key={ type.slug }
							className="wp-block-query-filter-post-type__item wp-block-query-filter__item"
						>
							<a href="#">
								<span className="wp-block-query-filter__icon"></span>
								<span className="wp-block-query-filter__label-text">
									{ type.name }
								</span>
							</a>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
}
