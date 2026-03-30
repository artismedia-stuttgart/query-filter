( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __, _x } = wp.i18n;
	var _ = lodash;

	/**
	 * Register the conditional content block.
	 */
	wp.blocks.registerBlockType( 'greyd/conditional-content', {
		title: __( 'Conditional Content', 'greyd_hub' ),
		description: __( "Dependency of user roles, time or other parameters", 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('condition'),
		category: 'greyd-blocks',
		supports: {
			customClassName: false,
		},
		attributes: {
			operator: { type: 'string', default: 'OR' },
			conditions: { type: 'array', default: [] },
			debug: { type: 'bool', default: false },
			live: { type: 'bool', default: false },
			harden: { type: 'bool', default: false },
		},

		edit: function ( props ) {

			// make deep copy of conditions once the block is created.
			// when a block is duplicated the attribute type 'array' is not deep cloned
			// resulting in 'synced' values between original and cloned block.
			var [ id, setID ] = wp.element.useState( false );
			if ( id != props.clientId ) {
				if ( !_.isEmpty(props.attributes.conditions) ) {
					// console.log("conditional-content block created - make deep copy of conditions");
					var newConditions = [];
					props.attributes.conditions.forEach( (cond) => {
						newConditions.push( { ...cond } );
					} );
					props.setAttributes( { conditions: newConditions } );
				}
				setID( props.clientId );
			}

			const hasChildBlocks = greyd.tools.hasChildBlocks( props.clientId );
			const defaultCondition = {
				type: 'urlparam',
				operator: 'is',
				value: '',
				detail: '',
				custom: ''
			};

			// apply filters to enable custom conditions
			const customConditions = wp.hooks.applyFilters( 'greyd.conditional-content.conditions', [
				{
					value: 'default',
					label: _x( "Default (greyd_conditional_content_filter)", 'small', 'greyd_hub' )
				}
			] );

			const types = wp.hooks.applyFilters( 'greyd.conditional-content.types', {
				urlparam: { value: 'urlparam', label: _x( "URL parameter", 'small', 'greyd_hub' ) },
				cookie: { value: 'cookie', label: _x( "Cookie", 'small', 'greyd_hub' ) },
				localStorage: { value: 'localStorage', label: _x( "Local Storage", 'small', 'greyd_hub' ) },
				time: { value: 'time', label: _x( "Time", 'small', 'greyd_hub' ) },
				userrole: { value: 'userrole', label: _x( "User role", 'small', 'greyd_hub' ) },
				post: { value: 'post', label: _x( "Post", 'small', 'greyd_hub' ) },
				taxonomy: { value: 'taxonomy', label: _x( "Post taxonomy", 'small', 'greyd_hub' ) },
				field: { value: 'field', label: _x( "Post meta", 'small', 'greyd_hub' ) },
				posttype: { value: 'posttype', label: _x( "Post type", 'small', 'greyd_hub' ) },
				postIndex: { value: 'postIndex', label: _x( "Post index", 'small', 'greyd_hub' ) },
				search: { value: 'search', label: _x( "Number of posts found", 'small', 'greyd_hub' ) },
				archivetax: { value: 'archivetax', label: _x( "Archive taxonomy", 'small', 'greyd_hub' ) },
				location: { value: 'location', label: _x( "Location (Template Type)", 'small', 'greyd_hub' ) },
				custom: (
					customConditions && customConditions.length
						? { value: 'custom', label: _x( "Custom Filter", 'small', 'greyd_hub' ) }
						: null
				)
			} );

			const opIsIsNot = {
				is: { value: 'is', label: _x( "Is:", 'small', 'greyd_hub' ) },
				is_not: { value: 'is_not', label: _x( "Is not:", 'small', 'greyd_hub' ) },
			};
			const opIsHasEmpty = {
				is: { value: 'is', label: _x( "Has the value:", 'small', 'greyd_hub' ) },
				is_not: { value: 'is_not', label: _x( "Has not the value:", 'small', 'greyd_hub' ) },
				has: { value: 'has', label: _x( "Contains the value:", 'small', 'greyd_hub' ) },
				has_not: { value: 'has_not', label: _x( "Does not contain the value:", 'small', 'greyd_hub' ) },
				not_empty: { value: 'not_empty', label: _x( "Is set", 'small', 'greyd_hub' ) },
				empty: { value: 'empty', label: _x( "Is not set", 'small', 'greyd_hub' ) },
			};
			const operators = wp.hooks.applyFilters( 'greyd.conditional-content.operators', {
				urlparam: { ...opIsHasEmpty },
				cookie: { ...opIsHasEmpty },
				localStorage: { ...opIsHasEmpty },
				time: { ...opIsIsNot },
				userrole: { ...opIsIsNot },
				post: { ...opIsIsNot },
				taxonomy: {
					has: { value: 'has', label: _x( "Has the term:", 'small', 'greyd_hub' ) },
					has_not: { value: 'has_not', label: _x( "Has not the term:", 'small', 'greyd_hub' ) },
					not_empty: { value: 'not_empty', label: _x( "Has at least 1 term assigned", 'small', 'greyd_hub' ) },
					empty: { value: 'empty', label: _x( "Has no term assigned", 'small', 'greyd_hub' ) },
				},
				field: {
					...opIsHasEmpty,
					less: { value: 'less', label: _x( "Smaller than", 'small', 'greyd_hub' ) },
					greater: { value: 'greater', label: _x( "Greater than", 'small', 'greyd_hub' ) },
					past: { value: 'past', label: _x( "In the past (date)", 'small', 'greyd_hub' ) },
					future: { value: 'future', label: _x( "In the future (date)", 'small', 'greyd_hub' ) },
				},
				posttype: { ...opIsIsNot },
				postIndex: {
					...opIsIsNot,
					less: { value: 'less', label: _x( "Smaller than", 'small', 'greyd_hub' ) },
					greater: { value: 'greater', label: _x( "Greater than", 'small', 'greyd_hub' ) },
					even: { value: 'even', label: _x( "Even", 'small', 'greyd_hub' ) },
					odd: { value: 'odd', label: _x( "Odd", 'small', 'greyd_hub' ) },
				},
				search: {
					not_empty: { value: 'not_empty', label: _x( "At least 1 result found", 'small', 'greyd_hub' ) },
					empty: { value: 'empty', label: _x( "No results found", 'small', 'greyd_hub' ) },
					single: { value: 'single', label: _x( "Exactly 1 result found", 'small', 'greyd_hub' ) },
					multiple: { value: 'multiple', label: _x( "More than 1 result found", 'small', 'greyd_hub' ) },
				},
				archivetax: {
					is: { value: 'is', label: _x( "Is archive of the taxonomy:", 'small', 'greyd_hub' ) },
					is_not: { value: 'is_not', label: _x( "Is not archive of this taxonomy:", 'small', 'greyd_hub' ) },
				},
				location: { ...opIsIsNot },
				custom: { ...opIsHasEmpty },
			} );

			const infos = wp.hooks.applyFilters( 'greyd.conditional-content.infos', {
				search: __( "The dependency on found posts only works in query loops or in search and archive templates. Otherwise, this content is not displayed.", 'greyd_hub' ),
				taxonomy: __( "The dependency on taxonomies only works in archive templates. Otherwise, this content is not displayed.", 'greyd_hub' ),
				archivetax: __( "The dependency on archive taxonomies only works in archive templates. Otherwise, this content is not displayed.", 'greyd_hub' ),
				field: __( "The dependency on post type meta fields only works with Greyd post types. Otherwise, this content is not displayed.", 'greyd_hub' ),
				postIndex: __( "The dependency on the index of the current post only works in query loops. The first item displayed has the index '1', the second '2', etc. Otherwise, this value is always '0'.", 'greyd_hub' ),
				// post: __(),
				// location: __(),
				custom: __( "Custom conditons can be defined by other plugins or themes, using the filter hook 'greyd.conditional-content.conditions'.", 'greyd_hub' ),
			} );

			const times = wp.hooks.applyFilters( 'greyd.conditional-content.times', {
				'': { value: '', label: __( "Select time", 'greyd_hub' ) },
				'5-12': { value: '5-12', label: _x( "In the morning (from 5am)", 'small', 'greyd_hub' ) },
				'12-17': { value: '12-17', label: _x( "In the afternoon (from 12 o'clock)", 'small', 'greyd_hub' ) },
				'17-23': { value: '17-23', label: _x( "In the evening (from 5 pm)", 'small', 'greyd_hub' ) },
				'23-24,0-5': { value: '23-24,0-5', label: _x( "At night (11 pm to 5 am)", 'small', 'greyd_hub' ) },
				'custom': { value: 'custom', label: _x( "Individual", 'small', 'greyd_hub' ) }
			} );

			// get posts of posttype
			const getPosts = ( posttype ) => {
				if ( !posttype || _.isEmpty(posttype) ) {
					var final = [];
					greyd.data.all_posttypes.forEach( pt => {
						final = [ ...final, ...getPosts(pt) ];
					} );
					return final;
				}
				var posts = {};
				var filter = [];
				// console.log(posttype);
				if ( _.has(greyd.data.all_posts, posttype) ) {
					for (var i=0; i<greyd.data.all_posts[posttype].length; i++) {
						var post = greyd.data.all_posts[posttype][i];
						var title = post['id']+": "+post['title'];
						var language = "default";
						if ( _.has(post, 'lang') && !_.isEmpty(post.lang) ) {

							// WPML
							if ( typeof post.lang === 'object' && post.lang.language_code != "" ) {
								// console.log(value.lang);
								language = post.lang.language_code;
								title += " ("+language+")";
								if ( language != greyd.data.language.post.language_code && language != greyd.data.language.default ) {
									filter.push(post['id']);
								}
								else if ( _.has(post.lang, 'original_id' )) {
									filter.push(post.lang['original_id']);
								}
							}
							// Polylang
							else if ( typeof post.lang === 'string' && post.lang != "" ) {
								language = post.lang;
								title += " ("+language+")";
								if ( language != greyd.data.language.post && language != greyd.data.language.default ) {
									filter.push(post['id']);
								}
							}
						}
						if ( !_.has(posts, language) ) posts[language] = [];
						posts[language].push( { value: post['id'], label: title } );
					}
				}
				var final = [];
				for (var [lang, options] of Object.entries(posts)) {
					var filtered = [];
					if (filter.length == 0) filtered = options;
					else {
						for (var i=0; i<options.length; i++) {
							// console.log(options[i]);
							if (filter.indexOf(options[i].value) == -1)
								filtered.push(options[i]);
							// else console.log("skipped: "+options[i].value);
						}
					}
					if (filtered.length > 0) {
						if (lang == 'default') {
							final = [ ...filtered, ...final ];
						}
						else {
							final = [ ...final, ...filtered ];
						}
					}
				}
				return final;
			};

			return [

				// sidebar
				el( wp.blockEditor.InspectorControls, {}, [
					// conditions
					el( greyd.components.AdvancedPanelBody, {
						title: __( "Conditions", 'greyd_hub' ),
						holdsChange: props.attributes.conditions.length > 0,
						initialOpen: true
					}, [
						el( 'div', {
							className: 'components-greyd-controlgroup',
						}, [
							...props.attributes.conditions.map( ( condition, i ) => {

								condition = { ...defaultCondition, ...condition };

								// get all taxonomies & terms
								var postTypes = [], taxonomies = [], terms = [], fields = [];
								
								if ( condition.type == 'taxonomy' || condition.type == 'archivetax' ) {
									for ( const [ slug, taxes ] of Object.entries( greyd.data.all_taxes ) ) {
										if ( taxes.length ) {
											postTypes.push( {
												value: slug,
												label: slug
											} );

											if ( slug == condition.detail ) {
												taxes.forEach( taxonomy => {
													if ( taxonomy.values.length ) {
														taxonomies.push( {
															value: taxonomy.slug,
															label: taxonomy.title
														} );

														if ( taxonomy.slug == condition.custom ) {
															taxonomy.values.forEach( term => {
																terms.push( {
																	value: term.slug,
																	label: term.title
																} );
															} );
														}
													}
												} );
											}
										}
									}
								}
								else if ( condition.type == 'posttype' || condition.type == 'post' ) {
									for ( const [ i, slug ] of Object.entries( greyd.data.all_posttypes ) ) {
										postTypes.push( {
											value: slug,
											label: slug
										} );
									}
								}
								else if ( condition.type == 'field' ) {
									greyd.data.post_types.forEach( (pt) => {
										if ( pt.fields && Array.isArray(pt.fields) ) {
											postTypes.push( {
												value: pt.slug,
												label: pt.slug
											} );
											if ( pt.slug == condition.detail ) {
												pt.fields.forEach( (field) => {
													fields.push( {
														value: field.name,
														label: field.label
													} );
												} );
											}
										}
									} );
								}

								// filter
								postTypes = wp.hooks.applyFilters( 'greyd.conditional-content.condition.postTypes', postTypes, condition, props );
								taxonomies = wp.hooks.applyFilters( 'greyd.conditional-content.condition.taxonomies', taxonomies, condition, props );
								terms = wp.hooks.applyFilters( 'greyd.conditional-content.condition.terms', terms, condition, props );
								fields = wp.hooks.applyFilters( 'greyd.conditional-content.condition.fields', fields, condition, props );

								var groupComponents = [];

								// remove
								groupComponents.push( el( wp.components.Button, {
									className: "components-greyd-controlgroup__remove",
									onClick: () => {
										const newConditions = [ ...props.attributes.conditions ];
										newConditions.splice( i, 1 );
										props.setAttributes( { conditions: newConditions } );
									},
									title: __( "Remove condition", 'greyd_hub' )
								}, el( wp.components.Icon, { icon: 'no-alt' } ) ) );

								// escape if type is not defined
								if ( !types[condition.type] ) {

									groupComponents.push( el( wp.components.__experimentalInputControl, {
										label: __( "Type of condition", 'greyd_hub' ),
										value: sprintf( __( "Unknown (%s)", 'greyd_hub' ), condition.type ),
										disabled: true
									} ) );

									return el( 'div', {
										className: 'components-greyd-controlgroup__item',
										'data-index': i,
									}, groupComponents );

								}

								// type
								groupComponents.push( el( greyd.components.OptionsControl, {
									label: __( "Type of condition", 'greyd_hub' ),
									value: condition.type,
									options: Object.values( types ),
									onChange: ( value ) => {
										const newConditions = [ ...props.attributes.conditions ];
										if ( value === 'search' ) {
											newConditions[ i ] = { ...defaultCondition, type: value, operator: 'not_empty' };
										} else if ( value === 'taxonomy' ) {
											newConditions[ i ] = { ...defaultCondition, type: value, operator: 'has', value: [] };
										} else if (
											value == 'archivetax' ||
											value == 'posttype' ||
											value == 'field' ||
											value == 'postIndex' ||
											value == 'post' ||
											value == 'location' ||
											value == 'custom'
										) {
											newConditions[ i ] = { ...defaultCondition, type: value, operator: 'is', value: [] };
										} else {
											newConditions[ i ] = { ...defaultCondition, type: value };
										}
										props.setAttributes( { conditions: newConditions } );
									},
								} ) );

								// url-parm / cookie detail
								if ( condition.type == 'urlparam' || condition.type == 'cookie' ) {
									groupComponents.push( el( greyd.components.OptionsControl, {
										// label: __("Parameter", 'greyd_hub'),
										value: condition.detail,
										options: [
											{ value: '', label: __( "Select parameter", 'greyd_hub' ) },
											...greyd.data.url_params.map( param => {
												return {
													label: param.nicename,
													value: param.name
												};
											} ),
											{ value: 'custom', label: __( "Individual parameter", 'greyd_hub' ) },
										],
										onChange: ( value ) => {
											const newConditions = [ ...props.attributes.conditions ];
											newConditions[ i ].detail = value;
											props.setAttributes( { conditions: newConditions } );
										},
									} ) );
									if ( condition.detail == 'custom' ) {
										groupComponents.push( el( 'input', {
											value: condition.custom,
											placeholder: __( "Individual parameter", 'greyd_hub' ),
											className: 'components-text-control__input components-base-control',
											onInput: ( event ) => {
												const index = parseInt( event.target.closest( '.components-greyd-controlgroup__item' ).dataset.index );
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ index ].custom = event.target.value;
												props.setAttributes( { conditions: newConditions } );
											}
										} ) );
									}
								}

								// localStorage detail
								if ( condition.type == 'localStorage' ) {
									groupComponents.push( el( 'input', {
										value: condition.detail,
										placeholder: __( "Individual parameter", 'greyd_hub' ),
										className: 'components-text-control__input components-base-control',
										onInput: ( event ) => {
											const index = parseInt( event.target.closest( '.components-greyd-controlgroup__item' ).dataset.index );
											const newConditions = [ ...props.attributes.conditions ];
											newConditions[ index ].detail = event.target.value;
											props.setAttributes( { conditions: newConditions } );
										}
									} ) );
								}

								// taxonomy detail
								if ( condition.type == 'taxonomy' || condition.type == 'archivetax' || condition.type == 'field' ) {

									// posttype
									groupComponents.push( el( greyd.components.SelectCustomControl, {
										value: condition.detail,
										options: [
											{ value: '', label: __( "Select post type", 'greyd_hub' ) },
											...postTypes,
										],
										onChange: ( value ) => {
											const newConditions = [ ...props.attributes.conditions ];
											newConditions[ i ].detail = value;
											newConditions[ i ].custom = '';
											newConditions[ i ].value = [];
											props.setAttributes( { conditions: newConditions } );
										},
									} ) );

									// taxonomy
									if ( !_.isEmpty( condition.detail ) && condition.type !== 'field' ) {
										groupComponents.push( el( greyd.components.SelectCustomControl, {
											value: condition.custom,
											options: [
												{ value: '', label: __( "Select taxonomy", 'greyd_hub' ) },
												...taxonomies,
											],
											onChange: ( value ) => {
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].custom = value;
												newConditions[ i ].value = [];
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
									}
									// field
									if ( !_.isEmpty( condition.detail ) && condition.type == 'field' ) {
										groupComponents.push( el( greyd.components.OptionsControl, {
											value: condition.custom,
											options: [
												{ value: '', label: __( "Select meta field", 'greyd_hub' ) },
												...fields,
											],
											onChange: ( value ) => {
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].custom = value;
												newConditions[ i ].value = [];
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
									}

								}

								// post detail
								if ( condition.type == 'post' ) {

									// posttype
									groupComponents.push( el( greyd.components.OptionsControl, {
										value: condition.detail,
										options: [
											{ value: '', label: __( "All post types", 'greyd_hub' ) },
											...postTypes,
										],
										onChange: ( value ) => {
											const newConditions = [ ...props.attributes.conditions ];
											newConditions[ i ].detail = value;
											newConditions[ i ].custom = '';
											if ( value != "" ) newConditions[ i ].value = [];
											props.setAttributes( { conditions: newConditions } );
										},
									} ) );

								}

								// custom filter details
								if ( customConditions.length && condition.type == 'custom' ) {
									groupComponents.push( el( greyd.components.SelectCustomControl, {
										value: condition.detail,
										options: [
											{ value: '', label: __( "Select Custom Condition", 'greyd_hub' ) },
											...customConditions,
										],
										onChange: ( value ) => {
											const newConditions = [ ...props.attributes.conditions ];
											newConditions[ i ].detail = value;
											newConditions[ i ].custom = [];
											props.setAttributes( { conditions: newConditions } );
										},
									} ) );
								}

								// operator
								groupComponents.push( el( greyd.components.OptionsControl, {
									label: __( "Condition", 'greyd_hub' ),
									value: condition.operator,
									options: Object.values( operators[ condition.type ] ),
									onChange: ( value ) => {
										const newConditions = [ ...props.attributes.conditions ];
										newConditions[ i ].operator = value;
										props.setAttributes( { conditions: newConditions } );
									},
								} ) );

								// value
								if (
									condition.operator !== 'empty'
									&& condition.operator !== 'not_empty'
									&& condition.type !== 'search'
									&& condition.operator !== 'even'
									&& condition.operator !== 'odd'
									&& condition.operator !== 'past'
									&& condition.operator !== 'future'
								) {

									groupComponents.push( el( 'div', { style: { marginBottom: '4px' } }, __( "Value", 'greyd_hub' ) ) );

									if ( condition.type == 'urlparam' || condition.type == 'cookie' ) {
										groupComponents.push( el( 'input', {
											value: condition.value,
											placeholder: __( "Value of the parameter", 'greyd_hub' ),
											className: 'components-text-control__input components-base-control',
											onInput: ( event ) => {
												const index = parseInt( event.target.closest( '.components-greyd-controlgroup__item' ).dataset.index );
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ index ].value = event.target.value;
												props.setAttributes( { conditions: newConditions } );
											}
										} ) );
										if ( condition.detail === 'optin' || condition.detail === 'optout' ) {
											groupComponents.push( el( 'div', {
												// className: 'components-tip',
												style: { marginBottom: '10px', color: '#757575', fontSize: 'small' },
												dangerouslySetInnerHTML: {
													__html: [
														"<ol style=\"margin:1em 0 1em 3em;\">",
														"<li><b>true-just</b>: " + _x( 'The action (opt-in or opt-out) was successfully carried out with this page view.', 'small', 'greyd_hub' ) + "</li>",
														"<li><b>true-already</b>: " + _x( 'The action (opt-in or opt-out) has already been carried out successfully.', 'small', 'greyd_hub' ) + "</li>",
														"<li><b>false-found</b>: " + _x( 'No corresponding entry could be found.', 'small', 'greyd_hub' ) + "</li>",
														"<li><b>false-out</b>: " + _x( 'The entry could not be verified (opt-in) because it has already been objected to (opt-out).', 'small', 'greyd_hub' ) + "</li>",
														"</ol>",
														"<i>" + __( 'Example: You only want to display content if the user has confirmed their identity (opt-in). Select "Form opt-in" as the parameter. Set "contains the value:" as the condition and enter "true" as the value. Now the condition applies to both values with the addition "true".', 'greyd_hub' ) + "</i>"
													].join( "" )
												}
											} ) );
										}
									}
									else if ( condition.type == 'localStorage' ) {
										groupComponents.push( el( 'input', {
											value: condition.value,
											placeholder: __( "Value of the parameter", 'greyd_hub' ),
											className: 'components-text-control__input components-base-control',
											onInput: ( event ) => {
												const index = parseInt( event.target.closest( '.components-greyd-controlgroup__item' ).dataset.index );
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ index ].value = event.target.value;
												props.setAttributes( { conditions: newConditions } );
											}
										} ) );
									}
									else if ( condition.type == 'userrole' ) {
										groupComponents.push( el( greyd.components.OptionsControl, {
											value: condition.value,
											options: [
												{ value: '', label: _x( "Select user role", 'small', 'greyd_hub' ) },
												{ value: 'none', label: _x( "Unknown (not logged in)", 'small', 'greyd_hub' ) },
												...Object.entries( greyd.data.user_roles ).map( param => {
													return {
														label: param[ 1 ].name,
														value: param[ 0 ]
													};
												} ),
											],
											onChange: ( value ) => {
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].value = value;
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
									}
									else if ( condition.type == 'time' ) {
										groupComponents.push( el( greyd.components.OptionsControl, {
											value: condition.value,
											options: Object.values( times ),
											onChange: ( value ) => {
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].value = value;
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
										if ( condition.value == 'custom' ) {
											groupComponents.push( el( 'input', {
												value: condition.custom,
												placeholder: __( "Individual time of day", 'greyd_hub' ),
												className: 'components-text-control__input components-base-control',
												onInput: ( event ) => {
													const index = parseInt( event.target.closest( '.components-greyd-controlgroup__item' ).dataset.index );
													const newConditions = [ ...props.attributes.conditions ];
													newConditions[ index ].custom = event.target.value;
													props.setAttributes( { conditions: newConditions } );
												}
											} ) );
											groupComponents.push( el( 'div', {
												// className: 'components-tip',
												style: { marginBottom: '10px', color: '#757575', fontSize: 'small' },
												dangerouslySetInnerHTML: {
													__html: __( "Indicate times in full hours or with minutes and separate them with hyphens (e.g. for 12 to 13:30: <code>12-13:30</code>). Separate multiple time periods with commas (e.g., <code>12-13,15-16</code>). Indicate times before and after midnight as individual sections (e.g., for 10 p.m. to 4 a.m.: <code>22-24,0-4</code>).", 'greyd_hub' )
												}
											} ) );
										}
									}
									else if ( condition.type == 'taxonomy' || condition.type == 'archivetax' ) {

										if ( terms.length ) {
											if ( condition.type == 'taxonomy' ) {
												// use 'FormTokenField' instead of 'SelectCustomControl' with 'multiple' property
												var suggestions = terms.map(function(item) { return item.label } );
												var ids = terms.map(function(item) { return item.value } );
												var value = typeof condition.value === 'string' ? condition.value.split(',') : condition.value;
												groupComponents.push( el( wp.components.FormTokenField, {
													__experimentalShowHowTo: false,
													__experimentalExpandOnFocus: true,
													tokenizeOnBlur: true,
													value: value.map( (item) => { 
														if (ids.indexOf(item) == -1) return item;
														return suggestions[ids.indexOf(item)];
													} ),
													suggestions: suggestions,
													onChange: (value) => {
														// console.log(value);
														value = value.map( (item) => { 
															if (suggestions.indexOf(item) == -1) return item;
															return ids[suggestions.indexOf(item)];
														} );
														const newConditions = [ ...props.attributes.conditions ];
														newConditions[ i ].value = value;
														props.setAttributes( { conditions: newConditions } );
													},
												} ) );
											}
											else {
												groupComponents.push(
													el( greyd.components.SelectCustomControl, {
														value: condition.value,
														options: terms,
														// multiple: condition.type == 'taxonomy',
														// disabled: !terms.length,
														onChange: ( value ) => {
															const newConditions = [ ...props.attributes.conditions ];
															newConditions[ i ].value = value;
															props.setAttributes( { conditions: newConditions } );
														},
													} )
												);
											}
										}
										else {
											groupComponents.push(
												el( wp.components.TextControl, {
													value: condition.value,
													onChange: ( value ) => {
														const newConditions = [ ...props.attributes.conditions ];
														newConditions[ i ].value = value;
														props.setAttributes( { conditions: newConditions } );
													},
												} )
											);
										}

									}
									else if ( condition.type == 'field' ) {

										var field = false;
										if ( condition.operator == 'is' || condition.operator == 'is_not' ) {
											greyd.data.post_types.forEach( pt => {
												if ( pt.slug == condition.detail && pt.fields && Array.isArray(pt.fields) ) {
													pt.fields.forEach( ptfield => {
														if ( ptfield.name == condition.custom ) {
															// console.log(ptfield);
															field = { ...ptfield };
														}
													} );
												}
											} );
										}
										if ( field && ( field.type == 'radio' || field.type == 'dropdown' ) ) {
											var fieldOptions = [ { value: '', label: __( "Select option", 'greyd_hub' ) } ];
											var opts = (field.options ?? "").split(',');
											opts.forEach( opt => {
												var option = opt.trim();
												if ( option.indexOf('=') > -1 ) {
													option = option.split('=');
													fieldOptions.push( { value: option[0].trim(), label: option[1].trim() } );
												}
												else {
													fieldOptions.push( { value: option, label: option } );
												}
											})
											// show options for types 'radio' and 'dropdown'
											groupComponents.push( el( greyd.components.OptionsControl, {
												value: condition.value,
												options: fieldOptions,
												onChange: ( value ) => {
													const newConditions = [ ...props.attributes.conditions ];
													newConditions[ i ].value = value;
													props.setAttributes( { conditions: newConditions } );
												},
											} ) );

										}
										else {
											// text input for other types
											groupComponents.push(
												el( wp.components.TextControl, {
													value: condition.value,
													onChange: ( value ) => {
														const newConditions = [ ...props.attributes.conditions ];
														newConditions[ i ].value = value;
														props.setAttributes( { conditions: newConditions } );
													},
												} )
											);
										}
									}
									else if ( condition.type == 'posttype' ) {
										groupComponents.push(
											el( greyd.components.SelectCustomControl, {
												value: condition.value,
												options: [
													{ value: '', label: __( "Select post type", 'greyd_hub' ) },
													...postTypes,
												],
												disabled: !postTypes.length,
												onChange: ( value ) => {
													const newConditions = [ ...props.attributes.conditions ];
													newConditions[ i ].value = value;
													props.setAttributes( { conditions: newConditions } );
												},
											} )
										);
									}
									else if ( condition.type == 'postIndex' ) {
										groupComponents.push( el( wp.components.__experimentalNumberControl, {
											value: condition.value,
											isDragEnabled: true,
											isShiftStepEnabled: true,
											min: 0,
											step: 1,
											onChange: ( value ) => {
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].value = value;
												props.setAttributes( { conditions: newConditions } );
											}
										} ) );
									}
									else if ( condition.type == 'post' ) {
										var posts = getPosts(condition.detail);
										var suggestions = posts.map(function(item) { return item.label } );
										var ids = posts.map(function(item) { return item.value } );
										groupComponents.push( el( wp.components.FormTokenField, {
											__experimentalShowHowTo: false,
											__experimentalExpandOnFocus: true,
											tokenizeOnBlur: true,
											value: condition.value.map(function(item) { 
												if (ids.indexOf(item) == -1) return item;
												return suggestions[ids.indexOf(item)];
											}),
											suggestions: suggestions,
											onChange: (value) => {
												// console.log(value);
												value = value.map(function(item) { 
													if (suggestions.indexOf(item) == -1) return item;
													return ids[suggestions.indexOf(item)];
												});
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].value = value;
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
									}
									else if ( condition.type == 'location' ) {
										var options = [
											{ value: 'frontPage',	label: __( 'Front Page (Homepage)', 'greyd_hub' ) },
											{ value: 'postsPage',	label: __( 'Posts Page (Blog page)', 'greyd_hub' ) },
											{ value: 'singular',	label: __( 'Singular Page', 'greyd_hub' ) },
											{ value: 'archive',		label: __( 'Archive Page', 'greyd_hub' ) },
											{ value: 'search',		label: __( 'Search Results Page', 'greyd_hub' ) },
											{ value: '404',			label: __( '404 Page', 'greyd_hub' ) },
										];
										var suggestions = options.map(function(item) { return item.label } );
										var ids = options.map(function(item) { return item.value } );
										groupComponents.push( el( wp.components.FormTokenField, {
											__experimentalShowHowTo: false,
											__experimentalExpandOnFocus: true,
											tokenizeOnBlur: true,
											value: condition.value.map(function(item) { 
												if (ids.indexOf(item) == -1) return false;
												return suggestions[ids.indexOf(item)];
											}),
											suggestions: suggestions,
											onChange: (value) => {
												// console.log(value);
												value = value.filter( val => suggestions.indexOf(val) !== -1 ).map(function(item) { 
													if (suggestions.indexOf(item) == -1) return false;
													return ids[suggestions.indexOf(item)];
												});
												const newConditions = [ ...props.attributes.conditions ];
												newConditions[ i ].value = value;
												props.setAttributes( { conditions: newConditions } );
											},
										} ) );
									}
									else if ( condition.type == 'custom' ) {
										groupComponents.push(
											el( wp.components.TextControl, {
												value: condition.value,
												onChange: ( value ) => {
													const newConditions = [ ...props.attributes.conditions ];
													newConditions[ i ].value = value;
													props.setAttributes( { conditions: newConditions } );
												},
											} )
										);
									}
								};

								// info
								if ( infos[condition.type] ) {
									groupComponents.push( el( 'div', {
										className: 'components-tip',
										style: { marginBottom: '10px' }
									}, infos[condition.type] ) );
								}

								groupComponents = wp.hooks.applyFilters( 'greyd.conditional-content.condition.groupComponents', groupComponents, condition, props );

								return el( 'div', {
									className: 'components-greyd-controlgroup__item',
									'data-index': i,
								}, groupComponents );
							} ),
							el( wp.components.Button, {
								className: 'components-greyd-controlgroup__add' + ( props.attributes.conditions.length === 0 ? ' group_is_empty' : '' ),
								onClick: ( event ) => {
									props.setAttributes( { conditions: [ ...props.attributes.conditions, defaultCondition ] } );
									// console.log(props.attributes.conditions);
								},
								title: __( "Add condition", 'greyd_hub' )
							}, [
								el( wp.components.Icon, { icon: 'plus-alt2' } ),
								props.attributes.conditions.length === 0 ? el( 'span', {}, __( "Add condition", 'greyd_hub' ) ) : null
							] )
						] )
					] ),
					// operator
					el( wp.components.PanelBody, {
						title: __( "Behaviour", 'greyd_hub' ),
						initialOpen: true,
						holdsChange: props.attributes.operator !== 'OR' || props.attributes.live || props.attributes.debug,
					}, [
						el( greyd.components.ButtonGroupControl, {
							value: props.attributes.operator,
							options: [
								{ value: 'OR', label: __( "OR", 'greyd_hub' ) },
								{ value: 'AND', label: __( "AND", 'greyd_hub' ) },
							],
							onChange: ( value ) => { props.setAttributes( { operator: value } ); },
							help: __( "With AND all conditions must be true, with OR at least one.", 'greyd_hub' ),
						} ),
						// el( wp.components.Tip, {}, __( "With AND all conditions must be true, with OR at least one.", 'greyd_hub' ) ),
						
						// live
						el( wp.components.ToggleControl, {
							label: __( "Update live", 'greyd_hub' ),
							checked: props.attributes.live,
							onChange: ( value ) => { props.setAttributes( { live: value } ); },
							help: __( "When active, the content will be refreshed based on the condition, without reloading the page. This currently only supports time-conditions.", 'greyd_hub' ),
						} ),

						// harden
						el( wp.components.ToggleControl, {
							label: __( "Avoid caching", 'greyd_hub' ),
							checked: props.attributes.harden,
							onChange: ( value ) => { props.setAttributes( { harden: value } ); },
							help: __( "When active, the condition will be re-checked after pageload to avoid cached values. This currently only supports URL- and cookie-conditions and will work best with a single condition per block.", 'greyd_hub' ),
						} ),

						// debug
						el( wp.components.ToggleControl, {
							label: __( "Enable debug mode", 'greyd_hub' ),
							checked: props.attributes.debug,
							onChange: ( value ) => { props.setAttributes( { debug: value } ); },
							help: __( "A text is displayed in the frontend that helps to identify problems. Attention: do not use on live sites!", 'greyd_hub' ),
						} ),
					] ),
				] ),

				// preview
				el( 'div', { className: props.className + ' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('condition'),
						el( 'div', { className: 'preview-info-title' },
							el( "ul", {}, props.attributes.conditions.map( ( condition, i ) => {

								if ( !types[condition.type] ) return;

								condition = { ...defaultCondition, ...condition };
								let prefix = i < 1 ? "" : ( props.attributes.operator === 'AND' ? __( "AND", 'greyd_hub' ) : __( "OR", 'greyd_hub' ) ) + " ";
								let label = types[ condition.type ].label + " ";
								let oprtr = operators[ condition.type ][ condition.operator ].label;
								let value = condition.operator === 'empty' || condition.operator === 'not_empty' ? "" : " '" + condition.value + "'";

								if ( condition.type === 'urlparam' || condition.type === 'cookie' ) {
									if ( condition.detail === 'custom' ) {
										label += "'" + condition.custom + "' ";
									} else {
										label += "'" + condition.detail + "' ";
									}
								}
								else if ( condition.type === 'time' ) {
									if ( condition.value === 'custom' ) value = " '" + condition.custom + "' " + __( "o'clock", 'greyd_hub' );
									else value = " " + times[ condition.value ].label;
								}
								else if ( condition.type === 'search' ) {
									value = "";
								}

								return el( "li", { dangerouslySetInnerHTML: { __html: prefix + "<strong>" + label + "</strong>" + oprtr + value + "." } } );
							} ) )
						),
					] ),
					el( 'div', { className: 'preview-info-content' }, [
						el( wp.blockEditor.InnerBlocks, { renderAppender: hasChildBlocks ? wp.blockEditor.InnerBlocks.DefaultBlockAppender : wp.blockEditor.InnerBlocks.ButtonBlockAppender } )
					] )
				] )
			];
		},
		save: function ( props ) {
			return el( wp.blockEditor.InnerBlocks.Content );
		}
	} );

} )( window.wp );