import { registerBlockType, createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import Edit from './edit';
import './style.css';
import './editor.css';

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	// No save function needed for dynamic blocks
	example: {
		attributes: {
			postType: 'page',
			inheritPostType: false,
			maxDepth: 3,
		},
	},
	title: __( 'Page Navigation', 'greyd_hub' ),
	description: __( 'Display a hierarchical navigation of pages, posts or custom post types', 'greyd_hub' ),
	icon: greyd.tools.getCoreIcon( 'pageList' ),
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ 'core/page-list' ],
				transform: function ( attributes ) {
					console.log( attributes );
					return createBlock(
						'core/page-list',
						{
							className: attributes.className,
						}
					);
				},
			},
		],
		from: [
			{
				type: 'block',
				blocks: [ 'core/page-list' ],
				transform: function ( attributes ) {
					console.log( attributes );
					return createBlock(
						'greyd/page-navigation',
						{
							className: attributes.className,
						}
					);
				},
			},
		],
	},
} ); 