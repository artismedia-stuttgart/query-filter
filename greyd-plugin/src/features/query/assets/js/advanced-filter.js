/**
 * Advanced Filter for Query Loop.
 */

var greyd = greyd || {};

greyd.advancedFilter = new function() {

	var { createElement: el } = wp.element;
	var { __, _x, sprintf } = wp.i18n;
	var _ = lodash;


	// advanced filter controls
	this.makeFilterControls = ( props ) => {

		// set advancedFilter attributes
		var setFilter = function(param, value, target) {
			var values = props.attributes.advancedFilter;
			var index = target;
			if (typeof target !== 'number') {
				index = parseInt(target.closest('.components-greyd-controlgroup__item').dataset.index);
			}
			if (!_.has(values, index)) {
				console.warn("advancedFilter index "+index+" not available");
				return;
			}
			// console.log("change advancedFilter "+index+": "+param+": "+value);
			values[index][param] = value;
			if (param == 'name') {
				values[index] = { name: value };
			}
			props.setAttributes( { advancedFilter: [ ...values ] } );
		};

		// controls
		var optionsControl = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = !_.isEmpty(advancedFilter[atts.param]) ? advancedFilter[atts.param] : '';
			return el( greyd.components.OptionsControl, {
				label: _.has(atts, 'label') ? atts.label : '',
				help: _.has(atts, 'help') ? atts.help : '',
				value: value,
				onChange: function(value) { 
					setFilter( atts.param, value, index );
				},
				options: atts.options
			} );
		};
		var toggleControl = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = _.has(advancedFilter, atts.param) ? advancedFilter[atts.param] : false;
			return el( wp.components.ToggleControl, {
				label: _.has(atts, 'label') ? atts.label : '',
				help: _.has(atts, 'help') ? atts.help : '',
				checked: value,
				onChange: function(value) { 
					setFilter( atts.param, value, index );
				},
			} )
		};
		var radioControl = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = !_.isEmpty(advancedFilter[atts.param]) ? advancedFilter[atts.param] : '';
			return el( wp.components.RadioControl, {
				label: _.has(atts, 'label') ? atts.label : '',
				help: _.has(atts, 'help') ? atts.help : '',
				selected: value,
				onChange: function(value) { 
					setFilter( atts.param, value, index );
				},
				options: atts.options
			} );
		};
		var tokenField = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = !_.isEmpty(advancedFilter[atts.param]) ? advancedFilter[atts.param] : [];
			var suggestions = atts.suggestions.map(function(item) { return item.label } );
			var ids = atts.suggestions.map(function(item) { return item.value } );
			value = value.map(function(item) { 
				if (ids.indexOf(item) == -1) return item;
				return suggestions[ids.indexOf(item)];
			});
			return el( wp.components.FormTokenField, {
				__experimentalExpandOnFocus: true,
				__experimentalShowHowTo: _.has(atts, 'help') && atts.help,
				value: value,
				suggestions: suggestions,
				onChange: function(value) {
					value = value.map(function(item) { 
						if (suggestions.indexOf(item) == -1) return item;
						return ids[suggestions.indexOf(item)];
					});
					setFilter( atts.param, value, index );
				},
				tokenizeOnBlur: true
			} );
		};
		var DateControl = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = !_.isEmpty(advancedFilter[atts.param]) ? advancedFilter[atts.param] : '';
			return el( greyd.components.DatePickerPopupControl, {
				label: _.has(atts, 'label') ? atts.label : null,
				help: _.has(atts, 'help') ? atts.help : null,
				empty: _.has(atts, 'empty') ? atts.empty : null,
				value: value,
				onChange: function(value) { 
					setFilter( atts.param, value, index );
				}
			} );
		};
		var NumberControl = function(atts) {
			var index = atts.index;
			var advancedFilter = props.attributes.advancedFilter[index];
			var value = !_.isEmpty(advancedFilter[atts.param]) ? advancedFilter[atts.param] : '';
			return el( wp.components.__experimentalNumberControl, {
				label: _.has(atts, 'label') ? atts.label : null,
				help: _.has(atts, 'help') ? atts.help : null,
				value: value,
				onChange: function(value) { 
					setFilter( atts.param, value, index );
				},
				..._.has(atts, 'min') ? { min: atts.min } : {},
				..._.has(atts, 'max') ? { max: atts.max } : {},
			} );
		};

		var help = function(name, not=false) {
			if (name == 'taxonomy') {
				if (not) return __("Exclude posts with assigned terms from a taxonomy", 'greyd_hub');
				else return __("Show only posts with assigned terms of a taxonomy", 'greyd_hub');
			}
			if (name == 'author') {
				if (not) return __("Exclude posts from specific authors", 'greyd_hub');
				else return __("Show only posts from specific authors", 'greyd_hub');
			}
			if (name == 'include') {
				if (not) return __("Exclude individual posts", 'greyd_hub');
				else return __("Select individual posts to be shown", 'greyd_hub');
			}
			if (name == 'meta') {
				if (not) return __("Exclude posts with meta field property", 'greyd_hub');
				else return __("Show only posts with meta field property", 'greyd_hub');
			}
			if (name == 'order') {
				if (not) return __("Choose the order in which the posts should be sorted in descending order", 'greyd_hub');
				else return __("Choose the order in which the posts should be sorted in ascending order", 'greyd_hub');
			}
			if (name == 'date') {
				if (not) return __("Exclude posts from a specific date time range", 'greyd_hub');
				else return __("Show only posts from a specific date time range", 'greyd_hub');
			}
			return "";
		};
	
		// selected posttype
		var posttype = 'post';
		if ( _.has(props.attributes, 'query') && _.has(props.attributes.query, 'postType') ) {

			// inherit posttype from global context
			if ( _.has(props.attributes.query, 'inherit') && props.attributes.query.inherit === true ) {
				posttype = greyd.dynamic.getGlobalContextPosttype();
			}
			// use posttype from query
			else if ( _.has(props.attributes.query, 'postType') ) {
				posttype = props.attributes.query.postType;
			}

			// use default posttype
			if ( _.isEmpty(posttype) ) {
				posttype = 'post';
			}
		}

		// get posts of posttype
		var getPosts = function() {
			var posts = {};
			var advancedFilter = [];
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
								advancedFilter.push(post['id']);
							}
							else if ( _.has(post.lang, 'original_id' )) {
								advancedFilter.push(post.lang['original_id']);
							}
						}
						// Polylang
						else if ( typeof post.lang === 'string' && post.lang != "" ) {
							language = post.lang;
							title += " ("+language+")";
							if ( language != greyd.data.language.post && language != greyd.data.language.default ) {
								advancedFilter.push(post['id']);
							}
						}
					}
					if ( !_.has(posts, language) ) posts[language] = [];
					posts[language].push( { value: post['id'], label: title } );
				}
			}
			var final = [];
			for (var [lang, options] of Object.entries(posts)) {
				var advancedFiltered = [];
				if (advancedFilter.length == 0) advancedFiltered = options;
				else {
					for (var i=0; i<options.length; i++) {
						// console.log(options[i]);
						if (advancedFilter.indexOf(options[i].value) == -1)
							advancedFiltered.push(options[i]);
						// else console.log("skipped: "+options[i].value);
					}
				}
				if (advancedFiltered.length > 0) {
					if (lang == 'default') {
						final = [ ...advancedFiltered, ...final ];
					}
					else {
						final = [ ...final, ...advancedFiltered ];
					}
				}
			}

			/**
			 * @filter greyd.advancedFilter.getPosts
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.getPosts',
				final,
				posttype,
				props
			);
		};
		// is posttype hierarchical
		var isHierarchical = function() {
			if ( posttype == 'post' ) return false;
			else if ( posttype == 'page' ) return true;
			else {
				for (var i=0; i<greyd.data.post_types.length; i++) {
					if ( greyd.data.post_types[i]['slug'] == posttype ) {
						if ( _.has(greyd.data.post_types[i]['arguments'], 'hierarchical') ) return true;
						break;
					}
				}
				return false;
			}
		};

		// get taxonomies of posttype
		var getTaxonomies = function() {
			var final = [];
			if ( _.has(greyd.data.all_taxes, posttype) ) {
				for (var i=0; i<greyd.data.all_taxes[posttype].length; i++) {
					var tax = greyd.data.all_taxes[posttype][i];
					if (tax['public']) {
						final.push( { value: tax['slug'], label: tax['title'] } );
					}
				}
			}

			/**
			 * @filter greyd.advancedFilter.getTaxonomies
			 */
			final = wp.hooks.applyFilters(
				'greyd.advancedFilter.getTaxonomies',
				final,
				posttype,
				props
			);

			if (final.length == 0) {
				final.push( { value: '', label: sprintf( __("\"%s\" has no taxonomies", 'greyd_hub'), posttype), disabled: true } );
			}
			else {
				final.unshift( { value: '', label: __("Select taxonomy", 'greyd_hub') } );
			}
			return final;

		};
		// is taxonomy hierarchical
		var isHierarchicalTax = function(tax) {
			if ( _.has(greyd.data.all_taxes, posttype ) ) {
				for (var i=0; i<greyd.data.all_taxes[posttype].length; i++) {
					if ( greyd.data.all_taxes[posttype][i]['slug'] == tax ) {
						if (greyd.data.all_taxes[posttype][i]['hierarchical']) return true;
					}
				}
			}
			return false;
		};
		// get terms of taxonomy
		var getTerms = function(tax) {
			var final = [];
			if ( _.has(greyd.data.all_taxes, posttype ) ) {
				for (var i=0; i<greyd.data.all_taxes[posttype].length; i++) {
					if ( greyd.data.all_taxes[posttype][i]['slug'] == tax ) {
						for (var j=0; j<greyd.data.all_taxes[posttype][i]['values'].length; j++) {
							var term = greyd.data.all_taxes[posttype][i]['values'][j];
							var title = term['title']+" ("+term['count']+")";
							final.push( { value: term['id'], label: title } );
						}
						break;
					}
				}
			}

			/**
			 * @filter greyd.advancedFilter.getTerms
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.getTerms',
				final,
				tax,
				posttype,
				props
			);
		};

		// get all meta fields of posttye
		var getFields = function() {
			var final = [];
			for (var i=0; i<greyd.data.post_types.length; i++) {
				if ( greyd.data.post_types[i]['slug'] == posttype && _.has(greyd.data.post_types[i], 'fields') ) {
					for (var j=0; j<greyd.data.post_types[i]['fields'].length; j++) {
						var field = greyd.data.post_types[i]['fields'][j];
						if (
							!_.has(field, "type") ||
							field["type"] === 'hr' ||
							field["type"] === 'space' ||
							field["type"] === 'headline' ||
							field["type"] === 'descr'
						) continue;
						final.push( { value: field['name'], label: field['label'] } );
					}
					break;
				}
			}

			/**
			 * @filter greyd.advancedFilter.getMetaFields
			 */
			final = wp.hooks.applyFilters(
				'greyd.advancedFilter.getMetaFields',
				final,
				posttype,
				props
			);

			if (final.length == 0) {
				final.push( { value: '', label: sprintf( __("\"%s\" has no meta fields", 'greyd_hub'), posttype), disabled: true } );
			}
			else {
				final.unshift( { value: '', label: __("Select meta field", 'greyd_hub') } );
			}
			return final;

		};

		// get all date meta fields of posttye
		var getDateFields = function() {
			var final = [];
			for (var i=0; i<greyd.data.post_types.length; i++) {
				if ( greyd.data.post_types[i]['slug'] == posttype && _.has(greyd.data.post_types[i], 'fields') ) {
					for (var j=0; j<greyd.data.post_types[i]['fields'].length; j++) {
						var field = greyd.data.post_types[i]['fields'][j];
						if (
							_.has(field, "type") &&
							( field["type"] === 'date' || field["type"] === 'datetime-local' )
						) {
							final.push( { value: field['name'], label: field['label'] } );
						}
					}
					break;
				}
			}
			if (final.length == 0) {
				final.push( { value: '', label: __("Post Date", 'greyd_hub'), disabled: true } );
			}
			else {
				final = [
					{ value: '', label: __("Post Date", 'greyd_hub') },
					{ label: __("Meta fields", 'greyd_hub'), options: final }
				];
			}
			
			/**
			 * @filter greyd.advancedFilter.getMetaDateFields
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.getMetaDateFields',
				final,
				posttype,
				props
			);
		};

		// get all authors/users
		var getAuthors = function() {
			var final = [];
			for (var i=0; i<greyd.data.users.length; i++) {
				final.push( { value: greyd.data.users[i]['id'], label: greyd.data.users[i]['display_name'] } );
			}
			
			/**
			 * @filter greyd.advancedFilter.getAuthors
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.getAuthors',
				final,
				posttype,
				props
			);
		};
		// has posttype author support
		var hasAuthors = function() {
			if ( posttype == 'post' || posttype == 'page' ) return true;
			else {
				for (var i=0; i<greyd.data.post_types.length; i++) {
					if ( greyd.data.post_types[i]['slug'] == posttype ) {
						if ( _.has(greyd.data.post_types[i]['supports'], 'author') ) return true;
						break;
					}
				}
				return false;
			}
		};

		// is setting postviews_counter enabled
		var postviewsEnabled = function() {
			return _.has(greyd.data.settings, 'advanced_search') && greyd.data.settings.advanced_search.postviews_counter == "true";
		};

		var getOptions = function( orderOnce ) {

			var options = [
				{ value: '', label: __("Select filter", 'greyd_hub') },
				{ value: 'taxonomy', label: __("Filter by taxonomy", 'greyd_hub') },
				{ value: 'meta', label: __("Filter by meta field", 'greyd_hub') },
				hasAuthors() && { value: 'author', label: __('Filter by authors', 'greyd_hub') },
				{ value: 'date', label: __('Filter by date', 'greyd_hub') },
				{ value: 'include', label: __("Select individual posts", 'greyd_hub') },
				{ value: 'order', label: __("Sort results", 'greyd_hub'), ...orderOnce ? { disabled: true } : {} },
			];

			/**
			 * @filter greyd.advancedFilter.getOptions
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.getOptions',
				options,
				posttype,
				props
			);
		};

		var makeFilter = function( advancedFilter, i ) {

			var controls = [

				// taxonomy
				advancedFilter.name == 'taxonomy' && [
					optionsControl( {
						index: i,
						label: __("Taxonomy", 'greyd_hub'),
						param: 'taxonomy',
						options: getTaxonomies(),
					} ),
					!_.isEmpty(advancedFilter.taxonomy) && [
						tokenField( {
							index: i,
							param: 'terms',
							suggestions: [
								{ 
									value: 'current_terms', 
									label: advancedFilter.not == true ? 
										__("Exclude terms of the current post", 'greyd_hub') : 
										__("Terms of the current post", 'greyd_hub') 
								},
								{ 
									value: 'current_archive_terms', 
									label: advancedFilter.not == true ? 
										__("Exclude terms of the current archive", 'greyd_hub') : 
										__("Terms of the current archive", 'greyd_hub') 
								},
								{
									value: 'any_terms',
									label: advancedFilter.not == true ? 
										__("Exclude posts with terms", 'greyd_hub') : 
										__("Posts with terms", 'greyd_hub') 
								},
								...getTerms(advancedFilter.taxonomy),
							]
						} ),
						isHierarchicalTax(advancedFilter.taxonomy) && toggleControl( {
							index: i,
							param: 'children',
							label: advancedFilter.not == true ? 
								__("Do not exclude subordinate terms", 'greyd_hub') : 
								__("Exclude subordinate terms", 'greyd_hub')
						} )
					]
				],

				// meta
				advancedFilter.name == 'meta' && [
					optionsControl( {
						index: i,
						param: 'meta',
						options: getFields(),
					} ),
					!_.isEmpty(advancedFilter.meta) && [
						radioControl( {
							index: i,
							param: 'operator',
							options: advancedFilter.not ? [
								{ value: '', label: _x( "Is not set", 'small', 'greyd_hub' ) },
								{ value: 'is', label: _x( "Has not the value:", 'small', 'greyd_hub' ) },
								{ value: 'has', label: _x( "Does not contain the value:", 'small', 'greyd_hub' ) },
							] : [
								{ value: '', label: _x( "Is set", 'small', 'greyd_hub' ) },
								{ value: 'is', label: _x( "Has the value:", 'small', 'greyd_hub' ) },
								{ value: 'has', label: _x( "Contains the value:", 'small', 'greyd_hub' ) },
							]
						} ),
						!_.isEmpty(advancedFilter.operator) && [
							tokenField( {
								index: i,
								param: 'search',
								suggestions: [
									{ 
										value: 'current_meta', 
										label: advancedFilter.not == true ? 
											__("Exclude field value of the current post", 'greyd_hub') : 
											__("Field value of the current post", 'greyd_hub') 
									}
								],
								help: true
							} )
						]
					]
				],

				// author
				advancedFilter.name == 'author' && [
					tokenField( {
						index: i,
						param: 'author',
						suggestions: [
							{ 
								value: 'current_author', 
								label: advancedFilter.not == true ? 
									__("Exclude author of the current post", 'greyd_hub') : 
									__("Author of the current post", 'greyd_hub') 
							},
							{
								value: 'any_author',
								label: advancedFilter.not == true ? 
									__("Exclude posts with author", 'greyd_hub') : 
									__("Posts with author", 'greyd_hub') 
							},
							...getAuthors(),
						]
					} ),
				],

				// date
				advancedFilter.name == 'date' && [
					// select field (default: post_date)
					optionsControl( {
						label: __("Field", 'greyd_hub'),
						index: i,
						param: 'field',
						options: getDateFields(),
					} ),
					el( 'hr', { style: { margin: '0 0 1em', borderBottomColor: '#d8d8d8' } } ),
					// start
					optionsControl( {
						index: i,
						label: __( "Start", 'greyd_hub' ),
						param: 'starttype',
						options: [
							{ value: '', label: __("From selected date on", 'greyd_hub') },
							{ value: 'today', label: __("From today on (dynamic)", 'greyd_hub') },
						],
					} ),
					_.isEmpty(advancedFilter.starttype) && [
						DateControl( {
							index: i,
							// label: __( "Selected start date", 'greyd_hub' ),
							empty: __( "no start date selected", 'greyd_hub' ),
							// empty: advancedFilter.not == true ? 
							// 	__( "No start date selected (exclude all posts, starting from the first)", 'greyd_hub' ) :
							// 	__( "No start date selected (display all posts, starting from the first)", 'greyd_hub' ),
							param: 'start',
						} )
					],
					!_.isEmpty(advancedFilter.starttype) && el( wp.components.Flex, {
						// align bottom
						style: { alignItems: 'flex-end' }
					}, [
						el( wp.components.FlexItem, {}, [
							NumberControl( {
								label: __("Offset", 'greyd_hub'),
								index: i,
								param: 'startspan',
								..._.isEmpty(advancedFilter.field) ? { max: 0 } : {},
							} ),
						] ),
						el( wp.components.FlexItem, {}, [
							optionsControl( {
								index: i,
								param: 'startunit',
								options: [
									{ value: '', label: __("Days", 'greyd_hub') },
									{ value: 'weeks', label: __("Weeks", 'greyd_hub') },
									{ value: 'months', label: __("Months", 'greyd_hub') },
									{ value: 'years', label: __("Years", 'greyd_hub') },
								],
							} ),
						] )
					] ),
					el( 'hr', { style: { margin: '0 0 1em', borderBottomColor: '#d8d8d8' } } ),
					// end
					optionsControl( {
						index: i,
						label: __( "End", 'greyd_hub' ),
						param: 'endtype',
						options: [
							{ value: '', label: __("Until selected date", 'greyd_hub') },
							{ value: 'today', label: __("Until today (dynamic)", 'greyd_hub') },
						],
					} ),
					_.isEmpty(advancedFilter.endtype) && [
						DateControl( {
							index: i,
							// label: __( "Selected end date", 'greyd_hub' ),
							empty: __( "No end date selected", 'greyd_hub' ),
							// empty: advancedFilter.not == true ? 
							// 	__( "No end date selected (exclude all posts, ending with the last)", 'greyd_hub' ) :
							// 	__( "No end date selected (display all posts, ending with the last)", 'greyd_hub' ),
							param: 'end',
						} )
					],
					!_.isEmpty(advancedFilter.endtype) && el( wp.components.Flex, {
						// align bottom
						style: { alignItems: 'flex-end' }
					}, [
						el( wp.components.FlexItem, {}, [
							NumberControl( {
								label: __("Offset", 'greyd_hub'),
								index: i,
								param: 'endspan',
								..._.isEmpty(advancedFilter.field) ? { max: 0 } : {},
							} ),
						] ),
						el( wp.components.FlexItem, {}, [
							optionsControl( {
								index: i,
								param: 'endunit',
								options: [
									{ value: '', label: __("Days", 'greyd_hub') },
									{ value: 'weeks', label: __("Weeks", 'greyd_hub') },
									{ value: 'months', label: __("Months", 'greyd_hub') },
									{ value: 'years', label: __("Years", 'greyd_hub') },
								],
							} ),
						] )
					] ),
					// check
					!_.isEmpty(advancedFilter.start) && !_.isEmpty(advancedFilter.end) && [
						( (new Date(advancedFilter.start).getTime()) > (new Date(advancedFilter.end).getTime()) ) && [
							el( wp.components.Tip, {}, [
								el( 'div', {
									style: { margin: '10px 0' }
								}, __('The end date should be after the start date.', 'greyd_hub'), )
							] )
						]
					],
					el( 'hr', { style: { margin: '0 0 1em', borderBottomColor: '#d8d8d8' } } ),
				],


				// include
				advancedFilter.name == 'include' && [
					tokenField( {
						index: i,
						param: 'include',
						suggestions: [
							{ 
								value: 'current_post', 
								label: advancedFilter.not == true ? 
									__("Exclude current post", 'greyd_hub') : 
									__("Current post", 'greyd_hub') 
							},
							...getPosts(),
						]
					} ),
					isHierarchical() && toggleControl( {
						index: i,
						param: 'children',
						label: __("Only subordinate posts", 'greyd_hub'),
					} ),
				],

				// order
				advancedFilter.name == 'order' && [
					optionsControl( {
						index: i,
						label: __("Order", 'greyd_hub'),
						param: 'order',
						options: advancedFilter.not ? [
							// absteigend
							{ value: '', label: __("Select sorting", 'greyd_hub') },
							{ value: 'date', label: __("Old to new", 'greyd_hub') },
							{ value: 'modified', label: __("Post Modified", 'greyd_hub') },
							{ value: 'title', label: __('Z → A', 'greyd_hub') },
							{ value: 'meta', label: __("Meta descending", 'greyd_hub') },
							isHierarchical() && { value: 'menu_order', label: __("Order descending", 'greyd_hub') },
							postviewsEnabled() && { value: 'views', label: __("Least read", 'greyd_hub') },
							{ value: 'random', label: __("Random", 'greyd_hub') },
						] : [
							// aufsteigend
							{ value: '', label: __("Select sorting", 'greyd_hub') },
							{ value: 'date', label: __("New to old", 'greyd_hub') },
							{ value: 'modified', label: __("Post Modified", 'greyd_hub') },
							{ value: 'title', label: __('A → Z', 'greyd_hub') },
							{ value: 'meta', label: __("Meta ascending", 'greyd_hub') },
							isHierarchical() && { value: 'menu_order', label: __("Order ascending", 'greyd_hub') },
							postviewsEnabled() && { value: 'views', label: __("Most read", 'greyd_hub') },
							{ value: 'random', label: __("Random", 'greyd_hub') },
						]
					} ),
					advancedFilter.order == 'meta' && [
						optionsControl( {
							index: i,
							param: 'meta',
							options: getFields(),
						} ),
						!_.isEmpty(advancedFilter.meta) && [
							radioControl( {
								index: i,
								param: 'operator',
								options: [
									{ value: '', label: _x( "Auto", 'small', 'greyd_hub' ) },
									{ value: 'alphabetical', label: _x( "Alphabetical", 'small', 'greyd_hub' ) },
									{ value: 'chronological', label: _x( "Chronological", 'small', 'greyd_hub' ) },
									{ value: 'numeric', label: _x( "Numerical", 'small', 'greyd_hub' ) }
								]
							} ),

						]
					]
				],

				// negate
				advancedFilter.name != '' && [
					toggleControl( {
						index: i,
						param: 'not',
						label: /*advancedFilter.name != 'order' ? __('ausschließen', 'greyd_hub') :*/ __("Reverse / exclude", 'greyd_hub'),
					} ),
					el( 'p', { className: "greyd-inspector-help" }, help(advancedFilter.name, advancedFilter.not) )
				],

			];

			/**
			 * @filter greyd.advancedFilter.controls
			 */
			return wp.hooks.applyFilters(
				'greyd.advancedFilter.controls',
				controls,
				advancedFilter,
				i,
				posttype,
				props
			);
		};

		// make controls
		var filterControls = [];
		if (!_.isEmpty(props.attributes.advancedFilter)) {
			// console.log(props.attributes.advancedFilter);
			var orderOnce = false;
			for (var i=0; i<props.attributes.advancedFilter.length; i++) {
				var advancedFilter = props.attributes.advancedFilter[i];
				if (advancedFilter.name == 'order') orderOnce = true;
				filterControls.push(
					el( 'div', {
						className: 'components-greyd-controlgroup__item',
						'data-index': i,
					}, [
						// remove
						el( wp.components.Button, { 
							className: "components-greyd-controlgroup__remove",
							onClick: (event) => {
								var index = parseInt(event.target.closest('.components-greyd-controlgroup__item').dataset.index);
								// console.log("remove advancedFilter "+index);
								var values = props.attributes.advancedFilter;
								values.splice(index, 1);
								props.setAttributes( { advancedFilter: [ ...values ] } ); 
							},
							title: __("Remove filter", 'greyd_hub')
						}, el( wp.components.Icon, { icon: 'no-alt' } ) ),

						// options
						optionsControl( {
							index: i,
							label: __('Filter', 'greyd_hub'),
							param: 'name',
							options: getOptions( orderOnce )
						} ),

						// controls
						...makeFilter( advancedFilter, i )
					] )
				);
			}
		}
		return filterControls;
	};


	this.makeAdvancedFilterPanel = ( props ) => {

		var advancedFilter = !_.isEmpty(props.attributes?.advancedFilter) ? props.attributes.advancedFilter : [];

		return el( greyd.components.AdvancedPanelBody, { 
			title: __("Advanced Filter", 'greyd_hub'),
			initialOpen: !_.isEmpty(advancedFilter),
			holdsChange: !_.isEmpty(advancedFilter)
		}, [
			el( 'div', { className: 'components-greyd-controlgroup'}, [
				// makeFilter(),
				greyd.advancedFilter.makeFilterControls( props ),
				// add filter button
				el( wp.components.Button, {
					className: 'components-greyd-controlgroup__add'+( _.isEmpty(advancedFilter) ? ' group_is_empty': '' ),
					onClick: function() {
						// console.log('adding parameter');
						var values = [ ...advancedFilter ];
						values.push( { name: '' } );
						props.setAttributes( { advancedFilter: [ ...values ] } ); 
					},
					title: __("Add filter", 'greyd_hub')
				}, [
					el( wp.components.Icon, { icon: 'plus-alt2' } ),
					_.isEmpty(advancedFilter) ? el( 'span', {}, __("Add filter", 'greyd_hub') ) : null
				] )
			] )
		] );

	};

};