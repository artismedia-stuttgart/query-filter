/**
 * Add acf dynamic tags
 */
( function( wp ) {

	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Functions
	 */


	/**
	 * Get ACF field values recursively.
	 * 
	 * @param {object} field 
	 * @param {string} prefix 
	 * @param {bool} extendLabels 
	 * @returns {array} options
	 */
	function getAcfFieldOptions( field, prefix = '', extendLabels = true ) {

		var options = [];
		const {
			name,
			label,
			type
		} = field;
		
		// console.log( 'acf field', field );

		if (
			type === 'message' ||
			type === 'accordion' ||
			type === 'tab'
		) {
			return options; // continue
		}

		const slug = _.isEmpty(prefix) ? 'acf-'+name : prefix+'['+name+']';
		var title = _.isEmpty( label ) ? name : label;

		if ( extendLabels === true ) {
			if ( type == 'email' ) {
				title = __( "Email to:", 'greyd_hub' ) +' '+ title;
			}
			else if ( type == 'image' || type == 'file' ) {
				title = __( "Link to the file:", 'greyd_hub' ) +' '+ title;
			}
			else if ( type == 'post_object' || type == 'page_link' || type == 'relationship' ) {
				title = __( "Link to the post:", 'greyd_hub' ) +' '+ title;
			}
			else if ( type == 'taxonomy' ) {
				title = __( "Link to the term:", 'greyd_hub' ) +' '+ title;
			}
			else if ( type == 'user' ) {
				title = __( "Link to the user:", 'greyd_hub' ) +' '+ title;
			}
		}


		// groups
		if ( type === 'group' ) {
			var suboptions = [];
			field.sub_fields.forEach( sub_field => {
				suboptions = [
					...suboptions,
					...getAcfFieldOptions(sub_field, slug, extendLabels)
				];
			});
			// merge sub options
			options = [
				...options,
				...suboptions,
			];
		}
		
		// default field
		else {
			options.push( {
				type: type,
				value: slug,
				label: title,
				icon: 'welcome-widgets-menus',
				keywords: [ 'posttype', 'acf', 'field', 'advanced', 'custom', 'fields', 'secure', 'data', 'meta', 'metadata' ]
			} );
		}

		return options;

	};

	/**
	 * Get ACF options of a posttype.
	 * 
	 * @param {string} currentPostType 
	 * @param {array|bool} allowedFieldTypes 
	 * @param {bool} extendLabels 
	 * @returns {array} acfOptions
	 */
	function getAcfOptions( currentPostType, allowedFieldTypes = false, extendLabels = true ) {

		var acfOptions = [];

		// loop through posttypes
		greyd.data.post_types.forEach( posttype => {

			// Only show ACF options if the post type has the ACF property, there's something in it and the current post type matches
			// The check on the post type is needed, otherwise all ACF fields regardless of the post type are shown
			if (
				! _.has( posttype, 'acf' )
				|| posttype.acf.length === 0
				|| posttype.slug !== currentPostType
			) {
				return;
			}

			// loop through fields
			posttype.acf.forEach( field => {
				acfOptions = [
					...acfOptions,
					...getAcfFieldOptions(field, '', extendLabels)
				];
			});

			// filter allowed field types
			if ( allowedFieldTypes ) {
				acfOptions = acfOptions.filter( ( option ) => {
					return allowedFieldTypes.indexOf( option.type ) > -1;
				} );
			}

		});
		
		return acfOptions;
	}

	/**
	 * Get current posttype.
	 * 
	 * @param {string|false|null} clientId 
	 * @returns {string} currentPostType
	 */
	function getCurrentPosttype( clientId ) {

		if ( !clientId ) {
			// If a dynamic tag option is selected, a block must be selected
			const selectedBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();
			clientId = (selectedBlock && selectedBlock?.clientId) ? selectedBlock.clientId : false;
		}

		const query = clientId ? greyd.tools.isChildOf(clientId, 'core/query') : false;
		const currentPostType = query ? query?.attributes?.query?.postType : false;

		if ( !currentPostType ) {
			return greyd.dynamic.getGlobalContextPosttype();
		}
		else {
			return currentPostType;
		}
	}


	/**
	 * Hooks
	 */


	/**
	 * Add ACF fields to RichText dynamic Tags.
	 * @filter 'greyd.dynamic.tags.getRichTextOptions'
	 * 
	 * @param {array} options 
	 * @param {string} clientId 
	 * @returns {array} options
	 */
	function addTagOptions( options, clientId ) {

		// get current posttype
		const currentPostType = getCurrentPosttype( clientId );

		if ( currentPostType ) {
			// get all ACF options
			var acfOptions = getAcfOptions( currentPostType );
			// add ACF options
			if ( acfOptions && acfOptions.length ) {
				options.push( {
					label: 'ACF',
					options: acfOptions
				} );
			}
		}
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.tags.getRichTextOptions',
		'greyd/hook/dynamic/tagOptions',
		addTagOptions
	);


	/**
	 * Add ACF fields to Trigger options.
	 * @filter 'greyd.dynamic.triggerOptions'
	 * 
	 * @param {array} options 
	 * @param {string} mode 
	 * @param {string} clientId 
	 * @returns {array} options
	 */
	function addTriggerOptions( options, mode, clientId ) {

		// get current posttype
		const currentPostType = getCurrentPosttype( clientId );
		const allowedFieldTypes = [
			'email',
			'url',
			'link',
			'image',
			'file',
			'post_object',
			'page_link',
			'relationship',
			'taxonomy',
			'user',
			'group',
		];

		if ( currentPostType ) {
			// get filtered ACF options
			var acfOptions = getAcfOptions( currentPostType, allowedFieldTypes );
			// add ACF options
			if ( acfOptions.length ) {
				options.push( {
					label: 'ACF',
					options: acfOptions
				} );
			}
		}
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.triggerOptions',
		'greyd/hook/dynamic/triggerOptions',
		addTriggerOptions
	);


	/**
	 * Add ACF fields to Dynamic Image options.
	 * @filter 'greyd_blocks_dynamic_files'
	 * 
	 * @param {array} options 
	 * @param {string} mode 
	 * @param {string} clientId 
	 * @returns {array} options
	 */
	function addDynamicImageOptions( options, mode, clientId ) {

		// get current posttype
		const currentPostType = getCurrentPosttype( clientId );
		const allowedFieldTypes = [
			'link',
			'image',
			'file',
		];

		if ( currentPostType ) {
			// get filtered ACF options
			var acfOptions = getAcfOptions( currentPostType, allowedFieldTypes );
			// add ACF options
			if ( acfOptions.length ) {
				options.push( {
					label: 'ACF',
					options: acfOptions
				} );
			}
		}

		return options;
	}

	wp.hooks.addFilter(
		'greyd_blocks_dynamic_files',
		'greyd/hook/dynamic/imageOptions',
		addDynamicImageOptions
	);


	/**
	 * Add ACF fields to Advanced Filter options.
	 * @filter 'greyd.advancedFilter.getMetaFields'
	 * 
	 * @param {array} options 
	 * @param {string} posttype 
	 * @param {object} props 
	 * @returns {array} options
	 */
	function addAdvancedFilterMetaFields( options, posttype, props ) {
		// console.log(options);
		// console.log(posttype);
		// console.log(props);

		// get all ACF options
		var acfOptions = getAcfOptions( posttype );
		// add ACF options
		if ( acfOptions.length > 0 ) {
			console.log(acfOptions);
			acfOptions.forEach( ( opt, i ) => {
				if ( opt.type == 'image' || opt.type == 'file' ) {
					acfOptions[i].value += '[filename]';
				}
			} );
			options.push( {
				label: 'ACF',
				options: acfOptions
			} );
		}

		return options;
	};

	wp.hooks.addFilter(
		'greyd.advancedFilter.getMetaFields',
		'greyd/hook/advancedFilter/getMetaFields',
		addAdvancedFilterMetaFields
	);


	/**
	 * Add ACF fields to Advanced Filter date options.
	 * @filter 'greyd.advancedFilter.getMetaDateFields'
	 * 
	 * @param {array} options 
	 * @param {string} posttype 
	 * @param {object} props 
	 * @returns {array} options
	 */
	function addAdvancedFilterMetaDateFields( options, posttype, props ) {

		const allowedFieldTypes = [
			'date_picker',
			'date_time_picker',
		];

		// get all ACF options
		var acfOptions = getAcfOptions( posttype, allowedFieldTypes );
		// add ACF options
		if ( acfOptions.length > 0 ) {
			options.push( {
				label: 'ACF',
				options: acfOptions
			} );
		}

		return options;
	};

	wp.hooks.addFilter(
		'greyd.advancedFilter.getMetaDateFields',
		'greyd/hook/advancedFilter/getMetaDateFields',
		addAdvancedFilterMetaDateFields
	);

	/**
	 * Add ACF fields to Conditional-Content field options.
	 * @filter 'greyd.advancedFilter.getMetaDateFields'
	 * 
	 * @param {array} fields 
	 * @param {object} condition 
	 * @param {object} props 
	 * @returns {array} fields 
	 */
	function addConditionalContentMetaFields( fields, condition, props ) {
		if ( condition && condition.type == 'field' ) {
			var currentPostType = condition.detail ?? false;
			if ( currentPostType ) {
				// get all ACF options
				var acfOptions = getAcfOptions( currentPostType, false, false );
				// add ACF options
				if ( acfOptions.length > 0 ) {
					fields.push( {
						label: 'ACF',
						options: acfOptions
					} );
				}
			}
		}
		return fields;
	}

	wp.hooks.addFilter(
		'greyd.conditional-content.condition.fields',
		'greyd/hook/conditional-content/getMetaFields',
		addConditionalContentMetaFields
	);

} )( window.wp );