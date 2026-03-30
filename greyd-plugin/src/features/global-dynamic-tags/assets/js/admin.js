/**
 * Add global dynamic tags
 */
( function( wp ) {

	var { __ } = wp.i18n;
	var _ = lodash;

	function addTagOptions( options ) {

		// loop through posttypes
		greyd.data.post_types.forEach( posttype => {
			if ( _.has( posttype, 'arguments' ) && _.has( posttype.arguments, 'is_global_dynamic_tag' ) ) {

				let posttypeOptions = [];

				const {
					singular,
					slug,
					fields = [],
					icon = 'admin-post'
				} = posttype;

				// loop through fields
				fields.forEach( field => {

					const {
						name,
						label,
						type
					} = field;
					
					// not a field with a value
					if (
						_.isEmpty( name )
						|| type == 'headline'
						|| type == 'descr'
						|| type == 'hr'
					) {
						return; // continue
					}

					// push field to posttype options
					posttypeOptions.push( {
						type: type,
						value: 'gdt-' + slug + '-' + name,
						label: _.isEmpty( label ) ? name : label,
						icon: icon,
						keywords: [ 'posttype', 'global' ]
					} )
				});

				// push all posttype options as dynamic options
				if ( posttypeOptions.length ) {
					options.push( {
						label: singular,
						options: posttypeOptions
					} );
				}
			}
		});
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.tags.getRichTextOptions',
		'greyd/hook/dynamic/tagOptions',
		addTagOptions
	);

	function addTriggerOptions( options ) {

		// loop through posttypes
		greyd.data.post_types.forEach( posttype => {
			if ( _.has( posttype, 'arguments' ) && _.has( posttype.arguments, 'is_global_dynamic_tag' ) ) {

				let posttypeOptions = [];

				const {
					singular,
					slug,
					fields,
					icon
				} = posttype;

				// loop through fields
				fields.forEach( field => {

					const {
						name,
						label,
						type
					} = field;
					
					// not a field with a value
					if ( _.isEmpty( name ) ) {
						return; // continue
					}

					if ( type == 'url' ) {
						// push field to posttype options
						posttypeOptions.push( {
							type: type,
							value: 'gdt-' + slug + '-' + name,
							label: _.isEmpty( label ) ? name : label,
							icon: icon,
							keywords: [ 'posttype', 'global' ]
						} )
					}
					else if ( type == 'file' ) {
						// push field to posttype options
						posttypeOptions.push( {
							type: type,
							value: 'gdt-' + slug + '-' + name,
							label: __( "Link to the file:", 'greyd_hub' ) +' '+ ( _.isEmpty( label ) ? name : label ),
							icon: icon,
							keywords: [ 'posttype', 'global' ]
						} )
					}
					else if ( type == 'email' ) {
						// push field to posttype options
						posttypeOptions.push( {
							type: type,
							value: 'gdt-' + slug + '-' + name,
							label: __( "Mail to:", 'greyd_hub' ) +' '+ ( _.isEmpty( label ) ? name : label ),
							icon: icon,
							keywords: [ 'posttype', 'global' ]
						} )
					}

				});

				// push all posttype options as dynamic options
				if ( posttypeOptions.length ) {
					options.push( {
						label: singular,
						options: posttypeOptions
					} );
				}
			}
		});
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.triggerOptions',
		'greyd/hook/dynamic/triggerOptions',
		addTriggerOptions
	);

} )( window.wp );