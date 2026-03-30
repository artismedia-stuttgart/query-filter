/**
 * Rank Math Integration
 * https://rankmath.com/kb/content-analysis-api/
 */
( function( wp ) {

	// save resolving templates
	var resolving = [];
	const isResolving = ( posttype, id ) => {
		return wp.data.select('core/data').isResolving(
			'core',
			'getEntityRecord',
			[ 'postType', posttype, id ]
		);
	}
	const getTemplate = ( slugOrId ) => {
		var template = false;
		greyd.data.all_templates.forEach( ( tmp ) => {
			if ( !template && ( tmp.id == slugOrId || tmp.slug == slugOrId ) ) {
				template = tmp;
			}
		} );
		return template;
	}

	// replace all dynamic blocks with their innerBlocks recursively
	const switchDynamic = ( blocks ) => {

		var content = [];
		blocks.forEach( ( block ) => {
			if ( block.name == "greyd/dynamic" && block.attributes.template != "" ) {
				var template = getTemplate( block.attributes.template );
				if ( isResolving( template.type, template.id ) ) {
					// save if template is not yet loaded
					resolving.push(template);
				}
				var inner = block.innerBlocks?.length ? switchDynamic( block.innerBlocks ) : [];
				content = [ ...content, ...inner ];
			}
			else {
				content = [ ...content, block ];
			}
		} );
		return content;

	};

	// filter content analysis data.
	const filterRankMathContent = ( content ) => {

		// console.info("analyse content");
		// console.log(content);

		// get blocks from editor
		const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
		// console.log(blocks);

		if ( !blocks || blocks.length < 1 ) {
			return content;
		}

		// replace all dynamic blocks with their innerBlocks
		var newContent = switchDynamic( blocks );
		// console.log(newContent);

		// if not all templates are resolved, we wait and refresh the rankmath content analysis once all are loaded
		if ( resolving.length > 0 ) {
			
			const reload = wp.data.subscribe( () => {
				// check if getEntityRecord on all templates are resolved
				var loaded = true;
				resolving.forEach( template => {
					if ( loaded && isResolving( template.type, template.id ) ) {
						loaded = false;
					}
				} );
				if ( loaded ) {
					// console.info("all templates loaded");
					// recalculate rankmath
					reload();
					resolving = [];
					rankMathEditor.refresh( 'content' );
				}
			} );

		}
		
		// serialize the new content
		return wp.blocks.serialize( newContent );

	};

	// This will hook into content analysis data.
	wp.hooks.addFilter(
		'rank_math_content',
		'greyd/hook/rank-math/content-analysis',
		filterRankMathContent
	);

} )( window.wp );