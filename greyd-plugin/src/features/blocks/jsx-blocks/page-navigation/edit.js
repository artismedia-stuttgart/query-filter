import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

// Import Greyd Components
import '../../assets/js/components.js';

export default function Edit( props ) {

	const {
		setAttributes,
		attributes
	} = props;
	const { maxDepth, postType, inheritPostType, icon, iconExpanded } = attributes;

	// get greyd class
	const newGreydClass = greyd.tools.getGreydClass( props );
	if ( props.attributes?.greydClass !== newGreydClass ) {
		props.setAttributes( { greydClass: newGreydClass } );
	}

	// get block props
	const blockProps = useBlockProps( {
		className: props.attributes.greydClass
	} );

	// Get available post types
	const postTypes = useSelect( ( select ) => {
		const types = select( 'core' ).getPostTypes( { per_page: -1 } );
		return types ? types.filter( type => type.viewable ) : [];
	}, [] );

	// // Get current post type if in editor
	// const currentPostType = useSelect( ( select ) => {
	// 	const postId = select( 'core/editor' )?.getCurrentPostId();
	// 	if ( !postId ) return null;
	// 	const post = select( 'core' ).getEntityRecord( 'postType', 'post', postId );
	// 	return post?.type;
	// }, [] );

	const postTypeOptions = [
		// { label: __( 'Inherit from current context', 'greyd_hub' ), value: 'inherit' },
		...postTypes.map( type => ( {
			label: type.name,
			value: type.slug
		} ) )
	];

	return (
		<>
			<InspectorControls group="settings">
				<PanelBody title={__( 'Data', 'greyd_hub' )}>
					<SelectControl
						label={ inheritPostType ? __( 'Fallback Post Type', 'greyd_hub' ) : __( 'Post Type', 'greyd_hub' )}
						value={postType}
						options={postTypeOptions}
						onChange={( value ) => setAttributes( { postType: value } )}
					/>
					<ToggleControl
						label={__( 'Inherit post type from current context', 'greyd_hub' )}
						checked={inheritPostType}
						onChange={( value ) => {
							setAttributes( {
								inheritPostType: value
							} );
						}}
					/>
					<RangeControl
						label={__( 'Maximum depth', 'greyd_hub' )}
						value={maxDepth}
						onChange={( value ) => setAttributes( { maxDepth: value } )}
						min={1}
						max={5}
					/>
				</PanelBody>
				<PanelBody title={__( 'Icons', 'greyd_hub' )} initialOpen={false}>
					<greyd.components.IconPicker
						label={__( 'Icon closed', 'greyd_hub' )}
						value={icon}
						onChange={( value ) => setAttributes( { icon: value } )}
					/>
					<greyd.components.IconPicker
						label={__( 'Icon opened', 'greyd_hub' )}
						value={iconExpanded}
						onChange={( value ) => setAttributes( { iconExpanded: value } )}
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<greyd.components.StylingControlPanel
					title={__( 'Colors', 'greyd_hub' )}
					supportsHover={true}
					blockProps={props}
					parentAttr="linkStyles"
					controls={[
						{
							label: __( "Text Color", 'greyd_hub' ),
							attribute: "--post-nav-color",
							control: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						},
						{
							label: __( "Active Color", 'greyd_hub' ),
							attribute: "--post-nav-color-current",
							control: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						}
					]}
				/>
				<greyd.components.StylingControlPanel
					title={__( 'Layout', 'greyd_hub' )}
					supportsResponsive={true}
					blockProps={props}
					controls={[
						{
							label: __( "Justification", 'greyd_hub' ),
							attribute: "--post-nav-justify",
							control: greyd.components.ButtonGroupControl,
							options: [
								{ label: __( "Left", 'greyd_hub' ), value: 'flex-start' },
								{ label: __( "Center", 'greyd_hub' ), value: 'center' },
								{ label: __( "Right", 'greyd_hub' ), value: 'flex-end' },
								{ label: __( "Spreaded", 'greyd_hub' ), value: 'space-between' },
							]
						},
						{
							label: __( "Gap", 'greyd_hub' ),
							attribute: "--post-nav-gap",
							control: greyd.components.SwitchableSpacingControl,
							supportsPresets: true,
						}
					]}
				/>
				<greyd.components.StylingControlPanel
					title={__( 'Icon', 'greyd_hub' )}
					supportsHover={true}
					blockProps={props}
					parentAttr="chevronStyles"
					controls={[
						{
							label: __( "Color", 'greyd_hub' ),
							attribute: "--post-nav-chevron-color",
							control: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						},
						{
							label: __( "Active Color", 'greyd_hub' ),
							attribute: "--post-nav-chevron-color-current",
							control: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						},
						{
							label: __( "Size", 'greyd_hub' ),
							attribute: "--post-nav-chevron-size",
							control: greyd.components.FontSizePicker,
						}
					]}
				/>
				<greyd.components.StylingControlPanel
					title={__( 'Child Levels', 'greyd_hub' )}
					supportsResponsive={true}
					blockProps={props}
					controls={[
						{
							label: __( "Font Size", 'greyd_hub' ),
							attribute: "--post-nav-font-size-child",
							control: greyd.components.FontSizePicker,
						},
						{
							label: __( "Indentation", 'greyd_hub' ),
							attribute: "--post-nav-indent",
							control: greyd.components.SwitchableSpacingControl,
							supportsPresets: true,
						}
					]}
				/>
				<greyd.components.StylingControlPanel
					title={__( 'Line', 'greyd_hub' )}
					blockProps={props}
					controls={[
						{
							label: __( "Width", 'greyd_hub' ),
							attribute: "--post-nav-line-width",
							control: greyd.components.RangeUnitControl,
							units: [ 'px' ],
							min: 0,
							max: 10,
							step: 1
						},
						{
							label: __( "Indentation", 'greyd_hub' ),
							attribute: "--post-nav-line-indent",
							control: greyd.components.SwitchableSpacingControl,
							supportsPresets: true
						},
						{
							label: __( "Color", 'greyd_hub' ),
							attribute: "--post-nav-line-color",
							control: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						}
					]}
				/>
			</InspectorControls>
			<div {...blockProps}>
				<ServerSideRender
					block="greyd/page-navigation"
					attributes={attributes}
					ErrorResponsePlaceholder={({ error }) => (
						<div className="components-placeholder">
							<div className="components-placeholder__label">
								{__('Error loading navigation', 'greyd_hub')}
							</div>
							<div className="components-placeholder__instructions">
								{error?.message || __('An error occurred while loading the navigation.', 'greyd_hub')}
							</div>
						</div>
					)}
				/>
				<greyd.components.RenderPreviewStyles
					selector={attributes.greydClass}
					styles={
						{ "": props.attributes.greydStyles }
					}
					important={true}
				/>
				<greyd.components.RenderPreviewStyles
					selector={attributes.greydClass + " .page-navigation-item > a"}
					styles={
						{ "": props.attributes.linkStyles }
					}
				/>
				<greyd.components.RenderPreviewStyles
					selector={attributes.greydClass + " .page-navigation-item > .page-navigation-toggle"}
					styles={
						{ "": props.attributes.chevronStyles }
					}
				/>
			</div>
		</>
	);
} 