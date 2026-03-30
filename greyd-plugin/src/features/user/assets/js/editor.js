/**
 * User Management Editor Script.
 * This file is loaded in block editor pages and modifies the editor experience.
 * 
 * @since 1.5.4
 */
( function ( wp ) {

	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Add or remove support for blocks based on user capabilities.
	 * 
	 * @hook blocks.registerBlockType
	 */
	function modifyBlockSupport( settings, name ) {
		// console.log(name);
		// console.log(settings);
		// console.log(greyd_block_caps);

		if ( _.has( greyd_block_caps, name ) ) {
			if ( greyd_block_caps[ name ][ "visible" ] == false ) {
				if ( !_.has( settings, 'supports' ) ) settings.supports = {};
				settings.supports.inserter = false;
			}
		}

		return settings;
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/user/hook',
		modifyBlockSupport
	);


	/**
	 * Add dynamic tags based on user meta fields.
	 * @param {Array} options
	 * @returns {Array}
	 */
	function addTagOptions( options ) {

		// default user meta fields
		// ...that are not in the default dynamic tags.
		let userOptions = {
			first_name: {
				label: __( "First name", 'greyd_hub' ),
				value: 'user-meta-first_name',
				icon: 'admin-users',
				keywords: [ 'user', 'meta' ]
			},
			last_name: {
				label: __( "Last name", 'greyd_hub' ),
				value: 'user-meta-last_name',
				icon: 'admin-users',
				keywords: [ 'user', 'meta' ]
			},
			nickname: {
				label: __( "Nickname", 'greyd_hub' ),
				value: 'user-meta-nickname',
				icon: 'admin-users',
				keywords: [ 'user', 'meta' ]
			},
			user_url: {
				label: __( "Website", 'greyd_hub' ),
				value: 'user-meta-user_url',
				icon: 'admin-users',
				keywords: [ 'user', 'meta' ]
			},
			description: {
				label: __( "Biography", 'greyd_hub' ),
				value: 'user-meta-description',
				icon: 'admin-users',
				keywords: [ 'user', 'meta' ]
			}
		};

		// loop through user roles
		Object.values( greyd.data.user_roles ).forEach( function( role ) {

			const metaFields = role?.meta_fields;

			if ( ! metaFields || _.isEmpty( metaFields ) ) return;

			metaFields.forEach( function( field ) {

				const {
					name,
					label,
					type
				} = field;
				
				// not a field with a value
				if ( _.isEmpty( name ) ) {
					return; // continue
				}

				if (
					type == 'headline'
					|| type == 'descr'
					|| type == 'hr'
					|| type == 'space'
				) return;

				if ( ! _.has( userOptions, name ) ) {
					userOptions[name] = {
						label: _.isEmpty( label ) ? name : label,
						value: 'user-meta-' + name,
						icon: 'admin-users',
						keywords: [ 'user', 'meta' ]
					};
				}
			} );
		} );

		// convert to array
		userOptions = Object.values( userOptions );

		if ( userOptions.length ) {
			options.push( {
				label: __("User meta fields", 'greyd_hub'),
				options: userOptions
			} );
		}
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.tags.getRichTextOptions',
		'greyd/hook/dynamic/tagOptions',
		addTagOptions
	);

	/**
	 * Add dynamic trigger tags based on user meta fields.
	 * @param {Array} options 
	 * @returns {Array}
	 */
	function addTriggerOptions( options ) {

		const userOptions = [];

		// loop through user roles
		Object.values( greyd.data.user_roles ).forEach( function( role ) {

			const metaFields = role?.meta_fields;

			if ( ! metaFields || _.isEmpty( metaFields ) ) return;

			metaFields.forEach( function( field ) {

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
					userOptions.push( {
						label: _.isEmpty( label ) ? name : label,
						value: 'user-meta-' + name,
						icon: 'admin-users',
						keywords: [ 'user', 'meta' ]
					} );
				}
				else if ( type == 'file' ) {
					userOptions.push( {
						label: __( "Link to the file:", 'greyd_hub' ) +' '+ ( _.isEmpty( label ) ? name : label ),
						value: 'user-meta-' + name,
						icon: 'admin-users',
						keywords: [ 'user', 'meta' ]
					} );
				}
			} );
		} );

		if ( userOptions.length ) {
			options.push( {
				label: __("User meta fields", 'greyd_hub'),
				options: userOptions
			} );
		}
		
		return options;
	}

	wp.hooks.addFilter(
		'greyd.dynamic.triggerOptions',
		'greyd/hook/dynamic/triggerOptions',
		addTriggerOptions
	);

} )( window.wp );