/**
 * Register Autocompleters for the block editor.
 * 
 * @since 1.3.0
 * @see https://developer.wordpress.org/block-editor/reference-guides/components/autocomplete/
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var _ = lodash;

	/**
	 * Dynamic Tag Autocompleter.
	 * 
	 * Appends available dynamic tags when typing #.
	 * @see dynamic.js For available options.
	 */
	const DynamicTagCompleter = {
		name: 'dynamic-tag',
		triggerPrefix: '#', // The prefix that triggers this completer
		options: () => {
			
			const options       = [];
			const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
			const tagOptions    = greyd.dynamic.tags.getRichTextOptions( selectedBlock?.clientId )

			tagOptions.forEach( tagOption => {

				if ( ! tagOption?.options ) {

					// if single option, add it
					if ( tagOption?.value && ! _.isEmpty( tagOption?.value ) ) {
						options.push( tagOption );
					}
					return;
				}

				tagOption.options.forEach( option => {
					options.push( option );
				} );
			} );

			return options;
		},
		getOptionLabel: ( option ) => {
			return option && el( wp.components.Flex, {
				gap: 3,
				align: 'center',
				justify: 'space-between'
			}, [
				el( wp.components.FlexItem, {}, [
					el( wp.components.Icon, { icon: option.icon, className: 'icon' } ),
				] ),
				el( wp.components.FlexBlock, {}, option.label )
			] )
		},
		getOptionKeywords: ( option ) => {
			return option && [
				option.label,
				option.value,
				...( _.has( option, 'keywords' ) ? option.keywords : [] )
			]
		},
		/**
		 * Return the actual tag structure.
		 * @see formats.js 'greyd/dtag'
		 * 
		 * @param {object} option The option @see dynamic.js
		 * @returns React element.
		 */
		getOptionCompletion: ( option ) => {
			return option && el( 'span', {
				'data-tag': option.value,
				'data-params': '',
				className: 'is-tag',
			}, option.label )
		}
	};

	/**
	 * Dynamic Templates Autocompleter.
	 * 
	 * Appends available dynamic templates when typing ++.
	 */
	const DynamicTemplatesCompleter = {
		name: 'dynamic-templates',
		triggerPrefix: '++', // The prefix that triggers this completer
		options: () => greyd.data.all_templates,
		getOptionLabel: ( option ) => {
			return option && el( wp.components.Flex, {
				gap: 3,
				align: 'center',
				justify: 'space-between'
			}, [
				el( wp.components.FlexItem, {}, [
					el( wp.components.Icon, { icon: 'database', className: 'icon' } ),
				] ),
				el( wp.components.FlexBlock, {}, option.title )
			] )
		},
		getOptionKeywords: ( option ) => {
			return option && [
				option.slug,
				option.title
			]
		},
		/**
		 * Return the actual tag structure.
		 * @see formats.js 'greyd/dtag'
		 * 
		 * @param {object} option The option @see dynamic.js
		 * @returns React element.
		 */
		getOptionCompletion: ( option ) => {
			const block = wp.blocks.createBlock( "greyd/dynamic", { template: option.slug } );
			const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();

			if ( selectedBlock.attributes.content == '++' ) {
				wp.data.dispatch( 'core/block-editor' ).replaceBlock(
					selectedBlock.clientId,
					block
				);
			}
			else {

				const insertionPoint = wp.data.select( 'core/block-editor' ).getBlockInsertionPoint();
				const blockParents   = wp.data.select( 'core/block-editor' ).getBlockParents( selectedBlock.clientId );
				const directParent   = blockParents ? blockParents[ blockParents.length - 1 ] : null;
				
				wp.data.dispatch("core/block-editor").insertBlock(
					block,
					insertionPoint.index,
					directParent
				);
			}
			return el( wp.element.Fragment );
		}
	};

	/**
	 * Append the completers.
	 * @param {array} completers All autocompleters.
	 * @returns {array}
	 */
	const appendGreydAutocompleters = ( completers ) => {
		return [
			...completers,
			DynamicTagCompleter,
			DynamicTemplatesCompleter
		];
	}
	
	/**
	 * Register the autocompleters.
	 * 
	 * @filter editor.Autocomplete.completers
	 */
	wp.hooks.addFilter(
		'editor.Autocomplete.completers',
		'greyd/autocompleters/dynamic',
		appendGreydAutocompleters
	);

} )( window.wp );