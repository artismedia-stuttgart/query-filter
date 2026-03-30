/**
 * Dynamic features for the block editor.
 */

var greyd = greyd || {};

greyd.dynamic = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	this.parseQuery = function(queryString) {
		var query = {};
		var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
		for (var i = 0; i < pairs.length; i++) {
			var pair = pairs[i].split('=');
			query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
		}
		return query;
	};

	/**
	 * Dynamic Tags
	 */
	this.tags = new function () {

		/**
		 * Array of available Dynamic Tag Options
		 * 
		 * @filter greyd.dynamic.tags.options
		 */
		this.options = wp.hooks.applyFilters( 'greyd.dynamic.tags.options', {
			tags: {
				basic: [ {
						value: 'now',
						label: __( "Current date", 'greyd_hub' ),
						icon: 'calendar-alt',
						keywords: [ 'date', 'calendar', 'today', 'time', 'clock' ]
					},
					{
						value: 'symbol',
						label: __( "Symbol (copyright, arrow...)", 'greyd_hub' ),
						icon: 'image-filter',
						keywords: [ 'symbol', 'copyright' ]
					},
					{
						value: 'count',
						label: __( "Number of posts", 'greyd_hub' ),
						icon: 'chart-bar',
						keywords: [ 'global', 'number', 'posts' ]
					}
				],
				site: [ {
						value: 'site-title',
						label: __( "Site title", 'greyd_hub' ),
						icon: 'admin-site-alt',
						keywords: [ 'website', 'global', 'headline' ]
					},
					{
						value: 'site-sub',
						label: __( "Website subtitle", 'greyd_hub' ),
						icon: 'admin-site-alt',
						keywords: [ 'website', 'global' ]
					},
					{
						value: 'site-logo',
						label: __( "Website logo", 'greyd_hub' ),
						icon: 'format-image',
						keywords: [ 'website', 'global', 'image', 'icon' ]
					},
					{
						value: 'site-url',
						label: __( 'Website URL', 'greyd_hub' ),
						icon: 'admin-links',
						keywords: [ 'website', 'global', 'link', 'domain' ]
					}
				],
				default: [ {
						value: 'title',
						label: __( "Post title", 'greyd_hub' ),
						icon: 'editor-textcolor',
						keywords: [ 'post', 'headline' ]
					},
					{
						value: 'date',
						label: __( "Post date", 'greyd_hub' ),
						icon: 'calendar-alt',
						keywords: [ 'date', 'calendar', 'time', 'clock' ]
					},
					{
						value: 'post-url',
						label: __( 'Post URL', 'greyd_hub' ),
						icon: 'admin-links',
						keywords: [ 'post', 'type', 'url', 'link', 'button', 'domain' ]
					},
					{
						value: 'post-type',
						label: __( "Post type", 'greyd_hub' ),
						icon: 'admin-post',
						keywords: [ 'post', 'type', 'posttype' ]
					},
					{
						value: 'author',
						label: __( "Author", 'greyd_hub' ),
						icon: 'admin-users',
						keywords: [ 'writer' ]
					},
					...(
						greyd.data?.settings?.advanced_search?.postviews_counter === "true" ? [ {
							value: 'post-views',
							label: __( "Post views", 'greyd_hub' ),
							icon: 'chart-bar',
							keywords: [ 'post', 'count', 'views', 'most read', 'visits' ]
						} ] : []
					)
				],
				page: [ {
						value: 'content',
						label: __( "Post content", 'greyd_hub' ),
						icon: 'editor-paragraph',
						keywords: [ 'post', 'page', 'content', 'text' ]
					},
					{
						value: 'image',
						label: __( "Featured image", 'greyd_hub' ),
						icon: 'format-image',
						keywords: [ 'post', 'thumbnail', 'icon' ]
					}
				],
				post: [ {
						value: 'excerpt',
						label: __( "Description", 'greyd_hub' ),
						icon: 'format-quote',
						keywords: [ 'post', 'content', 'text' ]
					},
					{
						value: 'categories',
						label: __( "Categories", 'greyd_hub' ),
						icon: 'category',
						keywords: [ 'post', 'taxonomy', 'terms' ]
					},
					{
						value: 'tags',
						label: __( "Tags", 'greyd_hub' ),
						icon: 'tag',
						keywords: [ 'post', 'taxonomy', 'terms' ]
					},
					...(
						greyd?.data?.settings?.advanced_search?.postviews_counter !== "true" ? [] : [ {
							value: 'post-views',
							label: __( "Post views", 'greyd_hub' ),
							icon: 'chart-bar',
							keywords: [ 'post', 'count', 'views', 'most read', 'visits' ]
						} ]
					)
				],
				archives: [
					{
						value: 'archive-title',
						label: __( "Archive title", 'greyd_hub' ),
						icon: 'editor-textcolor',
						keywords: [ 'archive', 'headline', 'name', 'title' ]
					},
					{
						value: 'category',
						label: __( "Category", 'greyd_hub' ),
						icon: 'category',
						keywords: [ 'archive', 'post', 'taxonomy', 'terms' ]
					},
					{
						value: 'tag',
						label: __( "Tag", 'greyd_hub' ),
						icon: 'tag',
						keywords: [ 'archive', 'post', 'taxonomy', 'terms' ]
					},
					{
						value: 'post-count',
						label: __( "Number of posts", 'greyd_hub' ),
						icon: 'chart-bar',
						keywords: [ 'archive', 'number', 'count', 'many', 'much' ]
					},
					{
						value: 'posts-per-page',
						label: __( "Number of posts per page", 'greyd_hub' ),
						icon: 'leftright',
						keywords: [ 'archive', 'number', 'count', 'many', 'much', 'page' ]
					},
					{
						value: 'page-num',
						label: __( "Current page", 'greyd_hub' ),
						icon: 'post-status',
						keywords: [ 'archive', 'number', 'count', 'many', 'much', 'page' ]
					},
					{
						value: 'page-count',
						label: __( "Page count", 'greyd_hub' ),
						icon: 'leftright',
						keywords: [ 'archive', 'number', 'count', 'many', 'much', 'page' ]
					}
				],
				search: [ {
						value: 'query',
						label: __( "Query", 'greyd_hub' ),
						icon: 'search',
						keywords: [ 'search', 'term', 'input' ]
					},
					{
						value: 'filter',
						label: __( 'Filter', 'greyd_hub' ),
						icon: 'filter',
						keywords: [ 'search', 'filter', 'taxonomy' ]
					}
				],
				table: [
					{
						value: 'col-sum',
						label: __("Column total", 'greyd_hub'),
						icon: 'editor-table',
						keywords: [ 'column', 'table', 'sum', 'math', 'value', 'total' ]
					},
					{
						value: 'col-average',
						label: __("Average", 'greyd_hub'),
						icon: 'editor-table',
						keywords: [ 'column', 'table', 'sum', 'math', 'value', 'median', 'average' ]
					},

				],
				// woo
				woo: [ {
						value: 'categories',
						label: __( "Categories", 'greyd_hub' ),
						icon: 'category',
						keywords: [ 'woo', 'shop', 'product', 'post', 'taxonomy', 'terms' ]
					},
					{
						value: 'tags',
						label: __( "Tags", 'greyd_hub' ),
						icon: 'tag',
						keywords: [ 'woo', 'shop', 'product', 'post', 'taxonomy', 'terms' ]
					},
					{
						value: 'excerpt',
						label: __( "Product short description", 'greyd_hub' ),
						icon: 'format-quote',
						keywords: [ 'woo', 'shop', 'product', 'content', 'text' ]
					},
					{
						value: 'price',
						label: __( "Price", 'greyd_hub' ),
						icon: 'money-alt',
						keywords: [ 'woo', 'shop', 'product', 'money', 'dollar' ]
					},
					{
						value: 'vat',
						label: __( "VAT ", 'greyd_hub' ),
						icon: 'money-alt',
						keywords: [ 'woo', 'shop', 'product', 'money', 'vat', 'tax' ]
					},
					{
						value: 'sale-badge',
						label: __( "Sale badge", 'greyd_hub' ),
						icon: 'money-alt',
						keywords: [ 'woo', 'shop', 'product', 'money', 'sale', 'badge' ]
					},
					...(
						greyd?.tools?.wooGermanizedPluginActive() ? [ {
								value: 'cart_excerpt',
								label: __( "Shopping cart short description", 'greyd_hub' ),
								icon: 'store',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'content' ]
							},
							{
								value: 'gzd-product-tax-notice',
								label: __( "Tax notice", 'greyd_hub' ),
								icon: 'money-alt',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'vat', 'tax' ]
							},
							{
								value: 'gzd-product-shipping-notice',
								label: __( "Shipping notice", 'greyd_hub' ),
								icon: 'money-alt',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'delivery' ]
							},
							{
								value: 'gzd-product-unit-price',
								label: __( "Unit price", 'greyd_hub' ),
								icon: 'money-alt',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'mones' ]
							},
							{
								value: 'gzd-product-delivery-time',
								label: __( "Delivery time", 'greyd_hub' ),
								icon: 'car',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'shipping' ]
							},
							{
								value: 'gzd-product-units',
								label: __( "Product units", 'greyd_hub' ),
								icon: 'products',
								keywords: [ 'woo', 'shop', 'product', 'germanized' ]
							},
							{
								value: 'gzd-product-defect-description',
								label: __( "Defect description", 'greyd_hub' ),
								icon: 'editor-paragraph',
								keywords: [ 'woo', 'shop', 'product', 'germanized', 'content' ]
							},
						] : []
					)
				],
				user: [
					{
						value: 'user-name',
						label: __( "Username", 'greyd_hub' ),
						icon: 'admin-users',
						keywords: [ 'user', 'login', 'first-name', 'name', 'logout' ]
					},
					{
						value: 'user-email',
						label: __( "User email", 'greyd_hub' ),
						icon: 'admin-users',
						keywords: [ 'user', 'login', 'username', 'email', 'logout' ]
					},
					{
						value: 'user-roles',
						label: __( "User role(s)", 'greyd_hub' ),
						icon: 'admin-users',
						keywords: [ 'user', 'login', 'role', 'logout' ]
					},
				],
			},
			trigger: {
				default: [ {
						value: 'post',
						label: __( "Link to post", 'greyd_hub' )
					},
					{
						value: 'author',
						label: __( "Link to author", 'greyd_hub' )
					},
				],
				page: [ {
					value: 'image',
					label: __( "Link to thumbnail", 'greyd_hub' )
				}, ],
				site: [
					{
						value: 'home',
						label: __( "Link to home page", 'greyd_hub' ),
						icon: 'admin-home',
						keywords: [ 'start', 'home', 'site', 'back' ]
					},
					{
						value: 'logout',
						label: __("Link to home page", 'greyd_hub'),
						icon: 'admin-users',
						keywords: [ 'user', 'login', 'username', 'user', 'logout' ]
					},

				],
				post: [ {
						value: 'post-next',
						label: __( "Link to the next post", 'greyd_hub' )
					},
					{
						value: 'post-previous',
						label: __( "Link to the previous post", 'greyd_hub' )
					},
				],
				archives: [ {
						value: 'page-next',
						label: __( "Link to next page", 'greyd_hub' )
					},
					{
						value: 'page-previous',
						label: __( "Link to previous page", 'greyd_hub' )
					},
				],
				woo: [ {
						value: 'woo_ajax',
						label: __( 'WooCommerce AJAX button', 'greyd_hub' )
					},
				]
			},
			file: {
				page: [ {
					value: 'image',
					label: __( "Featured image", 'greyd_hub' )
				}, ],
				woo: [ {
					value: 'image',
					label: __( "Product image", 'greyd_hub' )
				}, ]
			}
		} );

		/**
		 * Get all Dynamic Tag Options
		 * @since 1.7.4
		 * 
		 * @param {string} clientId Block clientId (optional)
		 * 
		 * @returns {array} Array of Dynamic Tag Options
		 * @returns {object.value} Value of Dynamic Tag Option
		 * @returns {object.label} Label of Dynamic Tag Option
		 * @returns {object.options} Array of Dynamic Tag Options
		 */
		this.getRichTextOptions = function( clientId ) {
			const options = [
				{ value: "", label: __("Select source", 'greyd_hub') }
			]

			if ( clientId ) {
				options.push( greyd.dynamic.tags.getCurrentOptions( 'tags', clientId ) )
			}

			options.push( { label: __("Website", 'greyd_hub'), options: greyd.dynamic.tags.getOptions('tags', 'site') } );
			options.push( { label: __("Current user", 'greyd_hub'), options: greyd.dynamic.tags.getOptions('tags', 'user') } );
			options.push( { label: __("More", 'greyd_hub'), options: greyd.dynamic.tags.getOptions('tags', 'basic') } );

			/**
			 * @filter greyd.dynamic.tags.getRichTextOptions
			 * @since 2.14.0 added clientId (used by api-blocks)
			 */
			return wp.hooks.applyFilters( 'greyd.dynamic.tags.getRichTextOptions', options, clientId );
		}

		/**
		 * Get Tag Options
		 * @param {string} mode Dynamic Tag mode (tags or trigger).
		 * @param {string} type Dynamic Tag type.
		 * @returns {array} Array of Dynamic Tag Options
		 */
		this.getOptions = function(mode, type) {
			let options = [];
			if ( _.has( greyd.dynamic.tags.options, mode ) && _.has( greyd.dynamic.tags.options[mode], type ) ) {
				options = _.clone( greyd.dynamic.tags.options[mode][type] );
			}

			/**
			 * @filter greyd.dynamic.tags.getOptions
			 */
			return wp.hooks.applyFilters(
				'greyd.dynamic.tags.getOptions',
				options,
				mode,
				type
			);
		}

		/**
		 * Get current Tag Options
		 * @param {string} mode Dynamic Tag mode (tags or trigger).
		 * @param {string} clientId Block clientId.
		 * @returns {object.label} Label of current Options
		 * @returns {object.options} Array of current Dynamic Tag Options
		 */
		this.getCurrentOptions = function(mode, clientId) {

			/**
			 * Adjust greyd.data based on site editor template
			 */
			if ( window.location.pathname.indexOf("/site-editor.php") !== -1 ) {
				var search = greyd.dynamic.parseQuery(location.search);
				if ( _.has(search, "canvas") && search.canvas == "edit" ) {
					greyd.data.post_type = "dynamic_template";
					greyd.data.template_type = "more";
					var parsedQuery = false;
					if ( _.has(search, "postId") && search.postId.indexOf("//") !== -1 ) {
						parsedQuery = search.postId.split('//');
					}
					else if ( _.has(search, "p") && search.p.indexOf("//") !== -1 ) {
						// new permalinks in the site-editor since wp 6.8.0
						parsedQuery = search.p.split('//');
					}
					if (parsedQuery && parsedQuery.length == 2) {

						let postName     = parsedQuery[1];
						let templateType = postName;

						if ( postName.indexOf("-") !== -1 ) {
							templateType = postName.split('-', 2)[0];
						}

						/**
						 * In FSE templates, the archive template used for categories and tags
						 * is called "category(-general)" and not "archives-category(-general)".
						 */
						if ( templateType == 'category' || templateType == 'tag' ) {
							postName = 'archives-' + postName;
							templateType = 'archives';
						}
						else if ( templateType == 'archive' ) {
							templateType = 'archives';
						}

						greyd.data.post_name = postName;
						greyd.data.template_type = templateType;
					}
				}
			}

			var current_label = "";
			var current_options = this.getOptions(mode, 'default');
			var template = (clientId) ? greyd.tools.isChildOf(clientId, 'greyd/dynamic') : false;
			var loop = (clientId) ? greyd.tools.isChildOf(clientId, 'core/post-template') : false;
			if (!loop) {
				var block = wp.data.select('core/block-editor').getBlocksByClientId(clientId);
				// console.log(block);
				if (
					block
					&& block.length > 0
					&& block[0] !== null
					&& block[0].name == 'greyd/post-table'
					&& _.has(greyd, 'tmp')
					&& _.has(greyd.tmp, 'post-table')
					&& greyd.tmp['post-table'] == 'tbody' // IS inside of post table body
				) {
					loop = true;
				}
			}
			var query = (clientId) ? greyd.tools.isChildOf(clientId, 'core/query') : false;
			if (query) {
				if (!loop) {
					// console.log(block);
					if (
						block
						&& block.length > 0
						&& block[0] !== null
						&& block[0].name == 'greyd/post-table'
						&& _.has(greyd, 'tmp')
						&& _.has(greyd.tmp, 'post-table')
						&& greyd.tmp['post-table'] != 'tbody' // IS NOT inside of post table body
					) {
						current_label = __("Table", 'greyd_hub');
						current_options = [ 
							...this.getOptions('tags', 'table'),
							// also add archive tags to have these available in foot and head
							...this.getOptions(mode, 'archives') 
						];
					}
					else {
						// query
						current_label = __("Query", 'greyd_hub');
						current_options = this.getOptions(mode, 'archives');
						if (greyd.data.post_type == "dynamic_template" && greyd.data.template_type == "search") {
							// search
							current_label = __("Search query", 'greyd_hub');
							current_options = [ ...this.getOptions(mode, 'search'), ...this.getOptions(mode, 'archives') ];
						}
					}
				}
				else {
					// loop
					var posttype = query.attributes.query.postType;
					// inherit posttype from global context
					if ( _.has(query.attributes.query, 'inherit') && query.attributes.query.inherit === true ) {
						posttype = greyd.data.post_type;
					}
					var [ label, options ] = this.getPosttypeOptions(mode, posttype);
					if (options.length > 0) {
						current_label = label+' - '+__("Loop", 'greyd_hub');
						current_options.push(...options);
					}
				}
			}
			else {
				var all_labels = {
					'current': {
						'female': __("Current", 'greyd_hub'),
						'male': __("Current", 'greyd_hub')
					},
					'selected': {
						'female': __("Selected", 'greyd_hub'),
						'male': __("Selected", 'greyd_hub')
					}
				};
				var labels = all_labels.current;
				var pt = greyd.data.post_type;
				// template
				if (template && _.has(template, 'attributes.postId') && template.attributes.postId != "") {
					// console.log(template.attributes.postId);
					post_pt = greyd.dynamic.getPosttype(template.attributes.postId);
					if (post_pt) {
						labels = all_labels.selected;
						pt = post_pt;
					}
				}
				// post
				if (pt) {
					var [ label, options ] = this.getPosttypeOptions(mode, pt);
					if (label != current_label) {
						if (pt == "dynamic_template")
							current_label = label;
						else if (pt == "page")
							current_label = labels.female+' '+label;
						else
							current_label = labels.male+' '+label;
					}
					if (options.length > 0) {
						current_options.push(...options);
					}
				}
			}

			// remove duplicates
			current_options = current_options.filter((v, i, a) => a.findIndex(t => (t.value === v.value)) === i);
			
			/**
			 * @filter greyd.dynamic.tags.getCurrentOptions
			 */
			return wp.hooks.applyFilters(
				'greyd.dynamic.tags.getCurrentOptions',
				{
					label: current_label,
					options: current_options
				},
				mode,
				clientId
			);
		}

		/**
		 * Get Tag Options of a certain posttype.
		 * 
		 * Mostly a user is editing the post of a certain posttype, like a page, post or product.
		 * We therefore need the dynamic options correlated to this posttype, eg. posts have tags
		 * & categories, pages ususally not while other posttypes have custom fields, taxonomies etc.
		 * 
		 * But when users edit a dynamic or wp-template, we need to get the context of this template to
		 * provide the correct dynamic options. For example, a single post template correlates to a post-
		 * type (default: post) and therefore has the same options as this posttype. Similarly, an archive
		 * template correlates to an archive of a certain posttype or taxonomy and therefore has the same
		 * options as this posttype or taxonomy.
		 * 
		 * @param {string} mode Dynamic Tag mode (tags or trigger).
		 * @param {string} posttype Slug of posttype to search in.
		 * @returns {array[0]} Label of Posttype
		 * @returns {array[1]} Array of Dynamic Tag Options
		 */
		this.getPosttypeOptions = function(mode, posttype) {

			var groupLabel = {
				type: __("Post", 'greyd_hub'),
				posttype: ''
			}
			var current_options = [];

			// user edits a page
			if (posttype == "page") {
				groupLabel.type =  __("Page", 'greyd_hub'),
				groupLabel.posttype = __("Page", 'greyd_hub');
				current_options.push(...this.getOptions(mode, 'page'));
			}
			// user edits a post
			else if (posttype == 'post') {
				// groupLabel.posttype = __("Post", 'greyd_hub');
				current_options.push(...this.getOptions(mode, 'page'), ...this.getOptions(mode, 'post'));
			}
			// user edits a woo product
			else if (posttype == 'product' && greyd.tools.isPluginActive( "woocommerce/woocommerce.php" )) {
				groupLabel.posttype = __("WooCommerce", 'greyd_hub');
				current_options.push(...this.getOptions(mode, 'page'), ...this.getOptions(mode, 'woo'));
			}
			// user edits a dynamic- or wp-template
			else if (posttype == "dynamic_template") {

				groupLabel.posttype = __("Template", 'greyd_hub');

				if (greyd.data.template_type == "single") {
					// // single
					groupLabel.type = __("Single post template", 'greyd_hub');
					// current_options.push(...template_options);
				}
				else if (greyd.data.template_type == "archives") {
					// archives
					groupLabel.type = __("Archive template", 'greyd_hub');
					current_options.push(...this.getOptions(mode, 'archives'));
				}
				else if (greyd.data.template_type == "taxonomy") {
					// archives
					groupLabel.type = __("Taxonomy archive template", 'greyd_hub');
					current_options.push(...this.getOptions(mode, 'archives'));
				}
				else if (greyd.data.template_type == "search") {
					// search
					groupLabel.type = __("Search template", 'greyd_hub');
					current_options.push(...this.getOptions(mode, 'search'), ...this.getOptions(mode, 'archives'));
				}
				else if (greyd.data.template_type == "woo" && greyd.data.post_name == "woo-product") {
					// woo single
					groupLabel.posttype = __("WooCommerce", 'greyd_hub');
					current_options.push(...this.getOptions(mode, 'woo'));
				}
				else if (greyd.data.template_type == "woo" && greyd.data.post_name.indexOf("woo-product-") == 0 ) {
					// woo archives
					groupLabel.posttype = __("WooCommerce archive template", 'greyd_hub');
					current_options.push(...this.getOptions(mode, 'archives'));
				}
				else if (greyd.data.template_type == "page" ) {
					// woo archives
					groupLabel.posttype = __("Page template", 'greyd_hub');
					// current_options.push(...template_options);
				}
			}

			const postTypeContext = greyd.dynamic.getGlobalContextPosttype(posttype);
			
			// add all dynamic options, like custom fields, taxonomies etc.
			const [ label, dynamic_options ] = this.getDynamicOptions(mode, postTypeContext);
			if ( dynamic_options.length > 0 ) {

				current_options.push(...dynamic_options);

				// if ( _.isEmpty(groupLabel.posttype) ) {
					groupLabel.posttype = label;
				// }
			}

			const fullLabel = groupLabel.type + ( _.isEmpty(groupLabel.posttype) ? '' : ' ('+groupLabel.posttype+')' );

			/**
			 * @filter greyd.dynamic.tags.getPosttypeOptions
			 */
			return wp.hooks.applyFilters(
				'greyd.dynamic.tags.getPosttypeOptions',
				[ fullLabel, current_options ],
				mode,
				posttype
			);
		}

		/**
		 * Get Tag Options from Dynamic Posttype
		 * @param {string} mode Dynamic Tag mode (tags or trigger).
		 * @param {string} posttype Slug of posttype to search in.
		 * @returns {array[0]} Label of Dynamic Posttype
		 * @returns {array[1]} Array of Dynamic Tag Options
		 */
		this.getDynamicOptions = function(mode, posttype) {
			var types = [];
			if (mode == 'trigger') types = [ 'url', 'file', 'email' ];
			if (mode == 'file') types = [ 'file', 'url' ];
			var all_types = (types.length == 0);
			var label = posttype;
			var dynamic_options = [];
			for (var i=0; i<greyd.data.post_types.length; i++) {
				// console.log(greyd.data.post_types[i]);
				if (greyd.data.post_types[i].slug == posttype) {
					var pt = greyd.data.post_types[i];
					// categories, tags und supports
					var tags = [];
					if ( _.has( pt, 'taxes' ) ) {
						for (var j=0; j<pt.taxes.length; j++) {
							if ( all_types ) {
								if ( pt.slug === 'post' && ( pt.taxes[j].slug == 'category' || pt.taxes[j].slug == 'post_tag' ) ) continue;
								dynamic_options.push( { 
									value: "taxonomy-"+pt.taxes[j].slug,
									label: pt.taxes[j].title,
									icon: 'category',
									keywords: [ 'custom', 'posttype', 'taxonomy', 'terms' ]
								} );
							}
						}
					}
					/**
					 * @deprecated since the introduction of posttype independant custom taxonomies
					 */
					else {
						if (_.has(pt, "categories")) tags.push('categories');
						if (_.has(pt, "tags")) tags.push('tags');
						if (_.has(pt, "supports")) {
							Object.keys(pt.supports).forEach(function(key, index) {
								if (key === "custom_taxonomies") {
									if (all_types) for (var j=0; j<pt.custom_taxonomies.length; j++) {
										dynamic_options.push( { 
											value: "customtax-"+pt.custom_taxonomies[j].slug, 
											label: pt.custom_taxonomies[j].plural,
											icon: 'category',
											keywords: [ 'custom', 'posttype', 'taxonomy', 'terms' ]
										} );
									}
								}
							});
						}
					}
					if ( pt.slug === 'post' ) {
						tags.push('categories');
						tags.push('tags');
						tags.push('image');
						tags.push('content');
					}
					if (_.has(pt, "supports")) {
						Object.keys(pt.supports).forEach(function(key, index) {
							if (key === "custom_taxonomies") {
								// @deprecated already handled above
							}
							else if (key === "editor") tags.push('content');
							else if (key === "thumbnail") tags.push('image');
							else tags.push(key);
						});
					}
					// console.log(tags);
					for (var j=0; j<tags.length; j++) {
						var found = false;
						// console.log(greyd.dynamic.tags.options);
						if (_.has(greyd.dynamic.tags.options, mode)) {
							Object.keys(greyd.dynamic.tags.options[mode]).forEach(function(key, index) {
								if (!found) for (var k=0; k<greyd.dynamic.tags.options[mode][key].length; k++) {
									if (greyd.dynamic.tags.options[mode][key][k].value == tags[j]) {
										dynamic_options.push(_.clone(greyd.dynamic.tags.options[mode][key][k]));
										found = true;
										break;
									}
								}
							});
						}
						if (!found && all_types) {
							dynamic_options.push( { 
								value: tags[j],
								label: tags[j],
								icon: 'tag',
								keywords: [ 'custom', 'posttype', 'taxonomy', 'terms' ]
							} );
						}
					}
					// console.log(dynamic_options);
					// fields
					if ( _.has(pt, 'fields') && pt.fields ) {
						for ( var j = 0; j < pt.fields.length; j++ ) {
							// console.log(pt.fields[j]);
							if ( [ 'headline', 'descr', 'hr' ].indexOf( pt.fields[ j ].type ) > -1 ) continue;
							if ( all_types || types.indexOf( pt.fields[ j ].type ) > -1 ) {
								// console.log(pt.fields[j].type);
								dynamic_options.push( {
									// value: pt.slug+'-'+pt.fields[j].name,
									value: pt.fields[ j ].name,
									label: pt.fields[ j ].label,
									type: pt.fields[ j ].type,
									icon: 'database',
									keywords: [ 'meta', 'field', 'post', 'custom', 'posttype' ]
								} );
							}
						}
					}
					// posttype label
					label = pt.title;
					break;
				}
			}

			/**
			 * @filter greyd.dynamic.tags.getDynamicOptions
			 */
			return wp.hooks.applyFilters(
				'greyd.dynamic.tags.getDynamicOptions',
				[ label, dynamic_options ],
				mode,
				posttype
			);
		}


		/**
		 * Array of availlable Dynamic Tag Formats
		 * 
		 * @filter greyd.dynamic.tags.formats
		 */
		this.formats = wp.hooks.applyFilters( 'greyd.dynamic.tags.formats', {
			author: [
				{ value: '', label: __("Public name", 'greyd_hub') },
				{ value: 'first_name', label: __("First name", 'greyd_hub') },
				{ value: 'last_name', label: __("Last name", 'greyd_hub') },
				{ value: 'nickname', label: __("Nickname", 'greyd_hub') },
				// { value: 'user_email', label: __("Email", 'greyd_hub') },
				{ value: 'avatar', label: __("Avatar", 'greyd_hub') },
				{ value: 'description', label: __("Biography", 'greyd_hub') },
			],
			symbols: [ 
				{ value: '', label: __("Select symbol", 'greyd_hub') },
				{ value: 'copyright', label: __("Copyright symbol", 'greyd_hub') },
				{ value: 'arrow-up', label: __("Arrow up", 'greyd_hub') },
				{ value: 'arrow-down', label: __("Arrow down", 'greyd_hub') },
				{ value: 'arrow-left', label: __("Arrow left", 'greyd_hub') },
				{ value: 'arrow-right', label: __("Arrow right", 'greyd_hub') },
				// ...
			],
			dates: [
				{ value: '', label: __("Default format", 'greyd_hub') },
				{ value: 'custom', label: __("Individual time format", 'greyd_hub') },
				{ value: 'dmY', label: __("dd.mm.yyyy", 'greyd_hub') },
				{ value: 'dmy', label: __("dd.mm.yy", 'greyd_hub') },
				{ value: 'dm', label: __("dd.mm", 'greyd_hub') },
				{ value: 'ldmY', label: __("Day dd.mm.yyyy", 'greyd_hub') },
				{ value: 'ldmy', label: __("Day dd.mm.yy", 'greyd_hub') },
				{ value: 'ldm', label: __("Day dd.mm", 'greyd_hub') },
				{ value: 'jFY', label: __("d. Month yyyy", 'greyd_hub') },
				{ value: 'jFy', label: __("d. Month yy", 'greyd_hub') },
				{ value: 'jF', label: __("d. Month", 'greyd_hub') },
				{ value: 'ljFY', label: __("Day d. Month yyyy", 'greyd_hub') },
				{ value: 'ljFy', label: __("Day d. Month yy", 'greyd_hub') },
				{ value: 'ljF', label: __("Day d. Month", 'greyd_hub') },
				{ value: 'mY', label: __("MM.YYYY", 'greyd_hub') },
				{ value: 'my', label: __("MM.YY", 'greyd_hub') },
				{ value: 'm', label: __("mm", 'greyd_hub') },
				{ value: 'FY', label: __("Month yyyy", 'greyd_hub') },
				{ value: 'Fy', label: __("Month yy", 'greyd_hub') },
				{ value: 'F', label: __("Month", 'greyd_hub') },
				{ value: 'Y', label: __("yyyy", 'greyd_hub') },
				{ value: 'y', label: __("yy", 'greyd_hub') },
				{ value: 'Gi', label: __("Time (h:i)", 'greyd_hub') },
				{ value: 'His', label: __("Time with seconds (H:i:s)", 'greyd_hub') },
				{ value: 'G', label: __("Hours", 'greyd_hub') },
				{ value: 'm', label: __("Minutes", 'greyd_hub') },
				{ value: 's', label: __("Seconds", 'greyd_hub') }
			],
			seperators: [
				{ value: '', label: __("Comma ( , )", 'greyd_hub') },
				{ value: 'semi', label: __("Semicolon ( ; )", 'greyd_hub') },
				{ value: 'vert', label: __("Vertical dash ( | )", 'greyd_hub') },
				{ value: 'hor', label: __("Horizontal dash ( – )", 'greyd_hub') },
				{ value: 'dot', label: __("Dot ( • )", 'greyd_hub') },
				{ value: 'space', label: __("Space ( )", 'greyd_hub') },
				{ value: 'br', label: __("Line break", 'greyd_hub') }
			],
			styles: [
				{ value: '', label: __("Primary link", 'greyd_hub') },
				// { value: 'sec', label: __("Secondary link", 'greyd_hub') },
				{ value: 'button', label: __("Primary button", 'greyd_hub') },
				{ value: 'button sec', label: __("Secondary button", 'greyd_hub') },
				{ value: 'button trd', label: __("Alternate button", 'greyd_hub') },
				{ value: 'button clear', label: __("Clear button", 'greyd_hub') }
			],
			price: {
				symbols: [
					{ value: '', label: __("Default", 'greyd_hub') },
					{ value: 'after', label: __("Behind", 'greyd_hub') },
					{ value: 'before', label: __("Before", 'greyd_hub') },
					{ value: 'afterh', label: __("Superscript behind", 'greyd_hub') },
					{ value: 'beforeh', label: __("Superscript before", 'greyd_hub') },
					{ value: 'hide', label: __("Hide", 'greyd_hub') }
				],
				formats: [
					{ value: '', label: __("Default", 'greyd_hub') },
					{ value: 'comma', label: __('00,00', 'greyd_hub') },
					{ value: 'none', label: __('00', 'greyd_hub') }
				],
				formats_thousands: [
					{ value: '', label: __("Default", 'greyd_hub') },
					{ value: 'space', label: __('1 000', 'greyd_hub') },
					{ value: 'dot', label: __('1.000', 'greyd_hub') }
				]
			},
		} );

		/**
		 * Get Tag Formats
		 * @param {string} type Dynamic Tag type.
		 * @returns {array} Array of Dynamic Tag Formats
		 */
		this.getFormats = function(type) {
			/**
			 * @filter greyd.dynamic.tags.getFormats
			 */
			return wp.hooks.applyFilters(
				'greyd.dynamic.tags.getFormats',
				_.clone( greyd.dynamic.tags.formats[type] )
			);
		}


		this.match = function(options, dynamic_content) {

			var tags = [];
			var labels = {};
			options.forEach(function(option) {
				// console.log(option);
				tags.push(option.value);
				labels[option.value] = option.label;
			})
			tags = tags.join('|');
			// console.log(tags);

			// console.log(dynamic_content);
			var changed = false;
			for (var i=0; i<dynamic_content.length; i++) {
				if (!_.has(dynamic_content[i], 'dtype')) continue;
				// console.log(dynamic_content[i]);
				var dvalue = decodeURIComponent(dynamic_content[i].dvalue);
				var newvalue = decodeURIComponent(dynamic_content[i].dvalue);
				if (dynamic_content[i].dtype == 'vc_link') {
					// convert vc_link
					var linkk = dvalue.split('|');
					var link = { };
					for (var j=0; j<linkk.length; j++) {
						var pair = linkk[j].split(':');
						if (pair[0] != "" && typeof pair[1] !== 'undefined') 
							link[pair[0]] = decodeURIComponent(pair[1]);
					}
					// console.log(link);
					var dtitle = dynamic_content[i].dtitle;
					for (var j=0; j<dynamic_content.length; j++) {
						if (dynamic_content[j].dtitle == dtitle+' Trigger') {
							// console.log("vc_link to trigger");
							if (dynamic_content[j].dvalue == "") {
								var trigger = {};
								var link_url = _.has(link, 'url') ? link.url : '';
								if (link_url.indexOf('_') === 0) {
									link_url = link_url.slice(1,-1);
									if (link_url == 'post-link') link_url = 'post';
									if (link_url == 'author-link') link_url = 'author';
									if (link_url == 'image-link') link_url = 'image';
									trigger = { type: "dynamic", params: { tag: link_url } };
								}
								else if ( !_.isEmpty(link_url) ) {
									trigger = { type: "link", params: { url: link_url } };
								}
								trigger = encodeURIComponent(JSON.stringify(trigger));
								// console.log(trigger);
								dynamic_content[j].dvalue = trigger;
								changed = true;
							}
						}
						if (dynamic_content[j].dtitle == dtitle+' Text') {
							// console.log("vc_link to text");
							if (dynamic_content[j].dvalue == "") {
								var matched_text = greyd.dynamic.tags.match(options, [{
									dvalue: encodeURIComponent(link.title),
									dtype: 'textarea'
								}]);
								var link_text = (matched_text) ? matched_text[0].dvalue : encodeURIComponent(link.title);
								dynamic_content[j].dvalue = link_text;
								changed = true;
							}
						}
					}
				}
				else if (dynamic_content[i].dtype.indexOf('textarea') > -1) {
					// convert rich text tags
					var regex_tags = new RegExp("(_)("+tags+")(\|.*?)?(_)", 'g');
					var tag = regex_tags.exec(dvalue);
					while (tag != null) {
						// console.log(tag);
						var [ old, pre, dtag, params, post ] = tag;
						if (typeof params !== 'undefined') {
							var params_array = params.split('|');
							params_array.shift();
							// console.log(params_array);
							if (params_array.length > 0) {
								if (dtag == 'content') params = { count: parseInt(params_array[0]) };
								if (dtag == 'author') params = { isLink: params_array[0] == '1' ? true : false };
								if (dtag == 'date') params = { format: params_array[0] };
								if (dtag == 'categories' || dtag == 'tags') {
									params = { format: params_array[0] };
									if (params_array.length == 2) params.isLink = params_array[1] == '1' ? true : false;
								}
								if (dtag == 'price') params = {format: params_array[0] };
							}
						}
						else params = '';
						var n = '<span data-tag="'+dtag+'" data-params="'+JSON.stringify(params).replace(/"/g,'&quot;')+'" class="is-tag">'+labels[dtag]+'</span>';
						newvalue = newvalue.split(old).join(n);
						// next tag
						tag = regex_tags.exec(dvalue);
					}
					if (newvalue != dvalue) {
						dynamic_content[i].dvalue = encodeURIComponent(newvalue);
						changed = true;
					}
				}
				else {
					// other tags
					// console.log(dynamic_content[i]);
				}
			}

			if (changed) return dynamic_content;
			else return false;
		}

		/**
		 * Render Tag
		 * @param {string} tag Dynamic Tag.
		 * @param {object} params Dynamic Tag params.
		 * @returns {string} Rendered Dynamic Tag
		 */
		this.render = function(tag, params) {
			// console.log(tag);
			// console.log(params);
			var txt = '';
			if (params != "") {
				var p = JSON.parse(params);
				if (_.has(p, 'format') && p.format != "") 
					txt = '|'+p.format;
			}
			return tag+txt;
		}

		/**
		 * Render Tag Label
		 * @param {string} tag Dynamic Tag.
		 * @param {array} options Dynamic Tag options to search in.
		 * @returns {string} Label of Dynamic Tag
		 */
		this.renderLabel = function(tag, options) {
			var label = tag;
			for (var i=0; i<options.length; i++) {
				if (_.has(options[i], 'options')) {
					var found = false;
					for (var j=0; j<options[i].options.length; j++) {
						if (options[i].options[j].value == tag) { 
							label = options[i].options[j].label; 
							found = true;
							break; 
						}
					}
					if (found) break;
				}
				else if (options[i].value == tag) { 
					label = options[i].label; 
					break; 
				}
			}
			return label;
		}
	}


	//
	// dynamic templates
	//

	this.getFields = function(name) {
		
		var fields = [];
		// core
		if (name == 'core/columns') fields = [
			{ key: 'background/image/id', type: 'file_picker_image', label: __("Background image", 'greyd_hub'), sc_params: ['dynamic_vc_bg_image'] },
			{ key: 'background/anim/id', type: 'file_picker_json', label: __("Background animation", 'greyd_hub'), sc_params: ['dynamic_vc_bg_animation'] },
			// 'dynamic_vc_bg_animation_placeholder', 
			{ key: 'background/video/url', type: 'textfield', label: __("Background video", 'greyd_hub'), sc_params: ['dynamic_vc_bg_video'] }
		];
		if (name == 'core/spacer') fields = [
			{ key: 'height', type: 'textfield', label: __("Base height", 'greyd_hub'), sc_params: ['dynamic_height'] }
		];
		if (name == 'core/embed') fields = [
			{ key: 'url', type: 'textfield', label: __('URL', 'greyd_hub'), sc_params: ['dynamic_link', 'dynamic_url'] }
		];
		if (name == 'core/paragraph') fields = [
			{ key: 'content', type: 'textarea_html', label: __('Text', 'greyd_hub'), sc_params: ['dynamic_content'] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/heading') fields = [
			{ key: 'content', type: 'textarea', label: __("Heading", 'greyd_hub'), sc_params: ['dynamic_title'] },
			{ key: 'level', type: 'dropdown_h', label: __("Heading level", 'greyd_hub'), sc_params: ['dynamic_h_tag'] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/button') fields = [
			{ key: 'text', type: 'textarea', label: __('Button text', 'greyd_hub'), sc_params: ['dynamic_content', 'dynamic_linkk'] },
			{ key: 'url', type: 'textfield', label: __('URL', 'greyd_hub'), sc_params: ['dynamic_linkk'] },
			{ key: 'trigger', type: 'textfield', label: __('Trigger', 'greyd_hub'), sc_params: ['dynamic_file', 'dynamic_email', 'dynamic_anchor'] }
		];
		if (name == 'core/image') fields = [
			{ key: 'id', type: 'file_picker_image', label: __("Image", 'greyd_hub'), sc_params: ['dynamic_icon'] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/video') fields = [
			{ key: 'src', type: 'textfield', label: __("Source", 'greyd_hub'), sc_params: ['dynamic_link', 'dynamic_url'] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];
		// core @since 1.2.8
		if (name == 'core/media-text') fields = [
			{ key: 'mediaId', type: 'file_picker_image', label: __("Image", 'greyd_hub'), sc_params: [] },
			{ key: 'focalPoint', type: 'focal_point', label: __("Focal point", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/cover') fields = [
			{ key: 'id', type: 'file_picker_file', label: __("Image", 'greyd_hub'), sc_params: [] },
			{ key: 'focalPoint', type: 'focal_point', label: __("Focal point", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/quote') fields = [
			{ key: 'value', type: 'textarea_html', label: __("Quote", 'greyd_hub'), sc_params: [] },
			{ key: 'citation', type: 'textarea_html', label: __("Source", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/pullquote') fields = [
			{ key: 'value', type: 'textarea_html', label: __("Quote", 'greyd_hub'), sc_params: [] },
			{ key: 'citation', type: 'textarea_html', label: __("Source", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'core/verse') fields = [
			{ key: 'content', type: 'textarea_html', label: __('Text', 'greyd_hub'), sc_params: [] }
		];
		// core @since 1.7.0
		if (name == 'core/table-of-contents') fields = [
			{ key: 'rendered', type: 'textfield', label: __("Table of contents", 'greyd_hub'), sc_params: [] }
		];
		// core @since 1.11.0
		if (name == 'core/navigation') fields = [
			{ key: 'ref', type: 'dropdown_navigation', label: __("Menu", 'greyd_hub'), sc_params: [] }
		];
		// @since 2.8.0
		if (name == 'core/group') fields = [
			{ key: 'style/background/backgroundImage/id', type: 'file_picker_image', label: __("Image", 'greyd_hub'), sc_params: [] },
			{ key: 'style/background/backgroundPosition', type: 'focal_point', label: __("Focal point", 'greyd_hub'), sc_params: [] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];

		// greyd
		if (name == 'greyd/anim') fields = [
			{ key: 'id', type: 'file_picker_json', label: __('Animation', 'greyd_hub'), sc_params: ['dynamic_icon'] }
			// 'dynamic_placeholder' 
		];
		if (name == 'greyd/list-item') fields = [
			{ key: 'content', type: 'textarea_html', label: __("List item", 'greyd_hub'), sc_params: ['dynamic_content'] }
		];
		if (name == 'greyd/dynamic') fields = [
			{ key: 'dynamic_content', type: 'dynamic_content', label: __("Leave content dynamic", 'greyd_hub'), sc_params: ['dynamic_dynamic_content'] }
		];

		// new blocks
		if (name == 'greyd/image') fields = [
			{ key: 'image', type: 'textfield', label: __("Image", 'greyd_hub'), sc_params: [] },
			{ key: 'focalPoint', type: 'focal_point', label: __("Focal point", 'greyd_hub'), sc_params: [] },
			{ key: 'sizeSlug', type: 'dropdown_sizes', label: __("Preferred resolution", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'greyd/button') fields = [
			{ key: 'content', type: 'textarea', label: __('Button text', 'greyd_hub'), sc_params: ['dynamic_content', 'dynamic_linkk'] },
			{ key: 'trigger', type: 'textfield', label: __('Trigger', 'greyd_hub'), sc_params: ['dynamic_linkk', 'dynamic_file', 'dynamic_email', 'dynamic_anchor'] }
		];
		if (name == 'greyd/menu') fields = [
			{ key: 'menu', type: 'dropdown_menu', label: __("Menu", 'greyd_hub'), sc_params: ['dynamic_menu'] }
		];
		if (name == 'greyd/box') fields = [
			{ key: 'trigger', type: 'textfield', label: __('Trigger', 'greyd_hub'), sc_params: ['dynamic_vc_content_box_link'] },
			{ key: 'background/image/id', type: 'file_picker_image', label: __("Background image", 'greyd_hub'), sc_params: ['dynamic_vc_content_box_image'] },
			{ key: 'background/anim/id', type: 'file_picker_json', label: __("Background animation", 'greyd_hub'), sc_params: ['dynamic_vc_content_box_animation'] },
			// 'dynamic_vc_content_box_animation_placeholder', 
			{ key: 'background/video/url', type: 'textfield', label: __("Background video", 'greyd_hub'), sc_params: [] },
			{ key: 'greydStyles/maxWidth', type: 'textfield', label: __("Maximum width", 'greyd_hub'), sc_params: [] }
		];
		if (name == 'greyd/form') fields = [
			{ key: 'id', type: 'dropdown_forms', label: __("Form ID", 'greyd_hub'), sc_params: ['dynamic_id'] }
		];
		if (name == 'greyd/accordion-item') fields = [
			{ key: 'title', type: 'textarea_html', label: __("Title", 'greyd_hub'), sc_params: [] },
		];

		if (name == 'greyd/search') fields = [
			// 'dynamic_placeholder' 
			// 'dynamic_holder', 
			// 'dynamic_btn' 
		];
		if (name == 'greyd/popover-button') fields = [
			{ key: 'content', type: 'textarea', label: __('Button text', 'greyd_hub'), sc_params: ['dynamic_content', 'dynamic_linkk'] },
		];
		if (name == 'greyd/anchor') fields = [
			{ key: 'anchor', type: 'textfield', label: __('Anchor name', 'greyd_hub') },
		];


		// var old_fields = {
		// 	vc_row: [ // core/columns
		// 		'dynamic_vc_bg_image', // 'background/image/id' done
		// 		'dynamic_vc_bg_animation', // 'background/anim/id' done
		// 		'dynamic_vc_bg_animation_placeholder', 
		// 		'dynamic_vc_bg_video' // 'background/video/url' done
		// 	],
		// 	vc_column_text: [ // core/paragraph
		// 		'dynamic_content' // 'content' done
		// 	],
		// 	vc_video: [ // core/embed
		// 		'dynamic_link' // 'url' done
		// 	],
		// 	vc_multimedia: [ // core/embed
		// 		'dynamic_url'  // 'url' done
		// 	],
		// 	vc_headlines: [ // core/heading
		// 		'dynamic_title', // 'content' done
		// 		'dynamic_h_tag' // 'level' done
		// 	],
		// 	vc_blank_space: [ // core/spacer
		// 		'dynamic_height' // 'height' done
		// 	],
		// 	vc_icons: [ // core/image + greyd/anim
		// 		'dynamic_icon', // 'id' done
		// 		'dynamic_placeholder' 
		// 	],
		// 	vc_list_item: [ // greyd/list-item
		// 		'dynamic_content' // 'content' done
		// 	],
		// 	dynamic: [ // greyd/dynamic
		// 		'dynamic_dynamic_content' // 'dynamic_content' done
		// 	],
		// 	vc_cbutton: [ // core/button
		// 		'dynamic_linkk', // 'url' done
		// 		'dynamic_content', // 'text' done
		// 		'dynamic_file', // 'trigger' done
		// 		'dynamic_email', // 'trigger' done
		// 		'dynamic_anchor' // 'trigger' done
		// 	],

		// 	// new blocks
		// 	vc_cbutton: [ // greyd/button (new)
		// 		'dynamic_linkk', // 'trigger' done
		// 		'dynamic_content', // 'content' done
		// 		'dynamic_file', // 'trigger' done
		// 		'dynamic_email', // 'trigger' done
		// 		'dynamic_anchor' // 'trigger' done
		// 	],
		// 	vc_footernav: [ // greyd/menu (new)
		// 		'dynamic_menu' // 'menu' done
		// 	],
		// 	vc_dropdownnav: [ // greyd/menu (new)
		// 		'dynamic_menu' // 'menu' done
		// 	],
		// 	vc_content_box: [ // greyd/box (new)
		// 		'dynamic_vc_content_box_link', // 'trigger' done
		// 		'dynamic_vc_content_box_image', // 'background/image/id' done
		// 		'dynamic_vc_content_box_animation', // 'background/anim/id' done
		// 		'dynamic_vc_content_box_animation_placeholder' 
		// 	],
		// 	vc_form: [ // greyd/form (new)
		// 		'dynamic_id' // 'id' done
		// 	],

		// 	vc_search: [ // greyd/search (new)
		// 		'dynamic_placeholder' // todo
		// 	],
		// 	vc_search_form: [ // greyd/search (new)
		// 		'dynamic_holder', // todo
		// 		'dynamic_btn' // todo
		// 	],
		// };

		return fields;
	}
	this.getField = function(name, key) {
		var fields = greyd.dynamic.getFields(name);
		for (var i=0; i<fields.length; i++) {
			if (fields[i].key == key) {
				return fields[i];
			}
		}
		return false;
	}

	/**
	 * Get value of field from attributes object
	 * field can be toplevel attribute of attributes
	 * or
	 * nested part of attributes (e.g. background/image/id)
	 * @param {object} attributes 
	 * @param {string} field 
	 * @returns Value of field or ""
	 */
	this.getFieldValue = function(attributes, field) {
		var val = attributes;
		var keys = field.key.split('/');
		for (var i=0; i<keys.length; i++) {
			if ( _.has(val, keys[i]) )
				val = val[keys[i]];
			else { 
				val = "";
				break;
			}
		}
		if (typeof val === 'undefined') val = "";
		return val;
	}

	this.setFieldValue = function(attributes, field, val) {
		var keys = field.key.split('/');

		// console.log(attributes);
		// console.log(field);
		// console.log(val);
		var att = attributes;
		for (var i=0; i<keys.length; i++) {
			if (i == keys.length-1) {
				// set value
				att[keys[i]] = val;
				if (field.type == "file_picker_file") {
					if (greyd.data.media_urls[val]) {
						if (keys[i] == 'id') {
							att['url'] = greyd.data.media_urls[val].src;
							if (_.has(att, 'backgroundType')) {
								if (greyd.data.media_urls[val].type.indexOf('video/') === 0) att['backgroundType'] = 'video';
								if (greyd.data.media_urls[val].type.indexOf('image/') === 0) att['backgroundType'] = 'image';
							}
							if (_.has(att, 'useFeaturedImage')) att['useFeaturedImage'] = undefined;
						}
					}
				}
				if (field.type == "file_picker_image" || field.type == "file_picker_json") {
					if (greyd.data.media_urls[val]) {
						if (keys[i] == 'id') att['url'] = greyd.data.media_urls[val].src;
						if (keys[i] == 'mediaId') {
							if (greyd.data.media_urls[val].type.indexOf('video/') === 0) att['mediaType'] = 'video';
							if (greyd.data.media_urls[val].type.indexOf('image/') === 0) att['mediaType'] = 'image';
							att['mediaUrl'] = greyd.data.media_urls[val].src;
						}
					}
				}
				if (field.type == "textarea" || field.type == "textarea_html") {
					// make sure val is string
					att[keys[i]] = String(val);
				}
			}
			else {
				if (!_.has(att, keys[i])) att[keys[i]] = {};
				att = att[keys[i]];
			}
		}
		// console.log(attributes);

		return attributes;
	}

	/**
	 * Get the changed dynamic attributes.
	 * Alternate version for @function setFieldValue()
	 * 
	 * @since 1.2.4
	 * 
	 * @param {object} field Dynamic field props.
	 * @param {string} val Field value.
	 * @returns {object}
	 */
	this.getChangedDynamicAttributes = function(field, val) {
		var keys = field.key.split('/');

		// console.log(field);
		// console.log(val);
		var changedAtts = {};
		for (var i=0; i<keys.length; i++) {
			if (i == keys.length-1) {
				
				changedAtts[keys[i]] = val;
				if (field.type == "file_picker_file") {
					if (greyd.data.media_urls[val]) {
						if (keys[i] == 'id') {
							changedAtts['url'] = greyd.data.media_urls[val].src;
							if (_.has(changedAtts, 'backgroundType')) {
								if (greyd.data.media_urls[val].type.indexOf('video/') === 0) changedAtts['backgroundType'] = 'video';
								if (greyd.data.media_urls[val].type.indexOf('image/') === 0) changedAtts['backgroundType'] = 'image';
							}
							if (_.has(changedAtts, 'useFeaturedImage')) changedAtts['useFeaturedImage'] = undefined;
						}
					}
				}
				if (field.type == "file_picker_image" || field.type == "file_picker_json") {
					if (greyd.data.media_urls[val]) {
						if (keys[i] == 'id') changedAtts['url'] = greyd.data.media_urls[val].src;
						if (keys[i] == 'mediaId') {
							changedAtts['mediaType'] = "image";
							changedAtts['mediaUrl'] = greyd.data.media_urls[val].src;
						}
					}
				}
				if (field.type == "textarea" || field.type == "textarea_html") {
					// make sure val is string
					changedAtts[keys[i]] = String(val);
				}
			}
			else {
				if (!_.has(changedAtts, keys[i])) changedAtts[keys[i]] = {};
				changedAtts = changedAtts[keys[i]];
			}
		}
		return changedAtts;
	}

	/**
	 * Holds all cached static dynamic fields, keyed by clientId.
	 * 
	 * @since 1.2.4
	 */
	this.staticDynamicFields = {};

	/**
	 * Cache a dynamic field.
	 * 
	 * @since 1.2.4
	 * 
	 * @param {object} props Block properties.
	 */
	this.cacheStaticField = ( props ) => {
		// console.log( 'cache field:', props.name, props.clientId );
		greyd.dynamic.staticDynamicFields[ props.clientId ] = props.name;
	}

	/**
	 * Whether a dynamic field is cached.
	 * 
	 * @since 1.2.4
	 * 
	 * @param {object} props Block properties.
	 * @returns {bool}
	 */
	this.isFieldCached = ( props ) => {
		return typeof greyd.dynamic.staticDynamicFields[ props.clientId ] !== 'undefined';
	}
	
	this.getForms = function(form_id) {
		var options = [ 
			{ value: "", label: __("Select form", 'greyd_hub') },
			...greyd.dynamic.getOptions(greyd.data.forms, form_id)
		];

		// console.log(options);
		return options;
	}

	this.getOptions = function(data, post_id=-1) {
		var parts = { };
		var options = [];

		let currentLanguage = greyd.data.language.default;
		if ( !_.isEmpty( greyd.data.language.post ) ) {
			if ( typeof greyd.data.language.post === 'object' ) {
				currentLanguage = _.has(greyd.data.language.post, 'language_code') ? greyd.data.language.post.language_code : greyd.data.language.post;
			} else if ( typeof greyd.data.language.post === 'string' ) {
				currentLanguage = greyd.data.language.post;
			}
		}

		// sort post list
		var filter = [];
		for ([key, value] of Object.entries(data)) {
			if (greyd.data.post_id == value['id']) continue;
			var language = "default";
			if ( _.has(value, 'lang') ) {
				// console.log(value.lang);
				if ( _.isEmpty(value.lang) ) {
					// do nothing
				} else if ( typeof value.lang === 'object' && ! _.isEmpty(value.lang.language_code) ) {
					language = value.lang.language_code;
				} else if ( typeof value.lang === 'string' ) {
					language = value.lang;
				}

				// this post has an original id
				if ( typeof value.lang === 'object' && _.has(value.lang, 'original_id') ) {
					if ( language == currentLanguage ) {
						// this is the correct language, filter the other one out
						filter.push(value.lang['original_id']);
					} else {
						// filter this one out...
						continue;
					}
				}
			}
			if (!_.has(parts, language)) parts[language] = [];
			parts[language].push( { value: value['id'], label: value['title'] } );
		}
		// console.log(filter);

		// make optgroups
		if (!_.isEmpty(parts)) {
			var part_options = [];
			// make optgroup with language codes
			for ([lang, opts] of Object.entries(parts)) {
				var opts_filtered = [];
				if (filter.length == 0) opts_filtered = opts;
				else {
					for (var i=0; i<opts.length; i++) {
						// console.log(opts[i]);
						if (filter.indexOf(opts[i].value) == -1 || opts[i].value == post_id) {
							opts_filtered.push(opts[i]);
						}
						// else console.log("skipped: "+opts[i].value);
					}
				}
				if (opts_filtered.length > 0) {
					if (lang == 'default') part_options.unshift( ...opts_filtered );
					else part_options.push( { label: lang, options: opts_filtered } );
				}
			}
			if (part_options.length > 0) options.push(...part_options);
		}

		// console.log(options);
		return options;
	}

	this.getPosts = function() {
		var options = [ 
			{ value: "", label: __("Use current post", 'greyd_hub') } // inherit
		];

		for ([pt, posts] of Object.entries(greyd.data.all_posts)) {
			var pt_title = pt;
			if (pt == 'post') pt_title = __("Posts", 'greyd_hub');
			else if (pt == 'page') pt_title = __("Pages", 'greyd_hub');
			else for (var i=0; i<greyd.data.post_types.length; i++) {
				if (greyd.data.post_types[i].full_slug == pt) {
					pt_title = greyd.data.post_types[i].plural;
					break;
				}
			}
			var opts = [];
			for (var i=0; i<posts.length; i++) {
				opts.push( { value: posts[i]['id'], label: posts[i]['title'] } );
			}
			if (opts.length > 0) options.push( { label: pt_title, options: opts } );
		}
		
		// console.log(options);
		return options;
	}
	this.getPosttype = function(post_id) {
		for ([pt, posts] of Object.entries(greyd.data.all_posts)) {
			for (var i=0; i<posts.length; i++) {
				if (posts[i]['id'] == post_id) {
					return posts[i]['type'];
					break;
				}
			}
		}
		// not found
		return false;
	}

	/**
	 * Get the context of the current dynamic- or wp-template by analyzing the post_name.
	 * Defaults to 'post'.
	 * 
	 * In the site editor this is set in the url:
	 * 
	 * (1) Default archive
	 *     URL:        site-editor.php?postId=greyd-theme//archive&postType=wp_template&canvas=edit
	 *     Post name:  archive
	 *     → Corresponds to the default posttype 'post'
	 * 
	 * (2) Archive for custom posttype
	 *     URL:        site-editor.php?postId=greyd-theme//archive-movie&postType=wp_template&canvas=edit
	 *     Post name:  archive-movie
	 *     → Corresponds to the custom posttype 'movie'
	 * 
	 * (2) Archive of a custom taxonomy
	 *     URL:        site-editor.php?postId=greyd-theme//taxonomy-movie_genre&postType=wp_template&canvas=edit
	 *     Post name:  taxonomy-movie_genre
	 *     → Corresponds to the posttype 'movie' and the taxonomy 'movie_genre'
	 */
	this.getGlobalContextPosttype = function( posttype = false ) {

		if ( !posttype ) {
			posttype = greyd.data.post_type;
		}

		// only continue if user edits a dynamic- or wp-template
		if ( posttype !== "dynamic_template" ) {
			return posttype;
		}
		
		// only continue on certain template types
		if (
			greyd.data.template_type !== "single"
			&& greyd.data.template_type !== "archives"
			&& greyd.data.template_type !== "taxonomy"
			&& greyd.data.template_type !== "search"
			&& greyd.data.template_type !== "woo"
			&& greyd.data.template_type !== "page"
		) {
			return 'post';
		}

		// split only on first occurence of dashes to allow dashes in posttype templateNameParts
		var templateNameParts = greyd.data.post_name.split(/-(.*)/s);
		if ( templateNameParts.length > 1 ) {
			if (templateNameParts[0] !== 'page') {
				templateNameParts.shift();
			}
		}
		else {
			templateNameParts = [ 'post' ];
		}
		// console.log(templateNameParts);

		var templateName = '';
		var found        = false;

		for (var i=0; i<templateNameParts.length; i++) {

			if ( templateName == '' ) {
				templateName = templateNameParts[i];
			} else {
				templateName += '-'+templateNameParts[i];
			}

			for (var j=0; j<greyd.data.post_types.length; j++) {

				const postTypeConfig = greyd.data.post_types[j];
				// console.log( postTypeConfig.slug, templateName );
				
				// posttype matches template context exactly, eg. (1) or (2)
				if ( postTypeConfig.slug == templateName ) {
					return postTypeConfig.slug;
				}

				// posttype matches template context partially, eg. (3) 'movie_'
				if ( templateName.indexOf( postTypeConfig.slug+'_') == 0 ) {

					// console.log( postTypeConfig.slug, templateName );
					
					const [ postTypeSlug, taxonomyName ] = templateName.split('_');

					if ( _.has( postTypeConfig, 'custom_taxonomies' ) ) {
						for (var k=0; k<postTypeConfig.custom_taxonomies.length; k++) {
							if ( postTypeConfig.custom_taxonomies[k].slug == taxonomyName ) {
								return postTypeSlug;
							}
						}
					}
					if ( _.has( postTypeConfig, 'taxes' ) ) {
						for (var k=0; k<postTypeConfig.taxes.length; k++) {
							if ( postTypeConfig.taxes[k].slug == templateName ) {
								return postTypeSlug;
							}
						}
					}
				}
			}
		}
		
		// default to 'post'
		return 'post';
	}

	this.getTemplates = function(template_id, permission=true, useSlug=false) {
		var parts = { 
			'dynamic_template': {},
			'wp_block': {},
			'wp_template': {},
			'wp_template_part': {}
		};
		var options = [ 
			{ value: "", label: __("Please select", 'greyd_hub') }
		];

		// sort template list
		var filter = [];
		for ([key, value] of Object.entries(greyd.data.all_templates)) {
			if (greyd.data.post_id == value['id']) continue;
			if (!permission && value['type'].indexOf('wp_template') === 0) continue;
			var language = "default";
			if ( _.has(value, 'lang') && !_.isEmpty(value.lang) ) {

				// WPML
				if ( typeof value.lang === 'object' && value.lang.language_code != "") {
					// console.log(value.lang);
					language = value.lang.language_code;
					if (_.has(value.lang, 'original_id')) {
						filter.push(value.lang['original_id']);
					}
				}
				// Polylang
				else if (typeof value.lang === 'string') {
					language = value.lang;
				}
			}
			if (!_.has(parts[value['type']], language)) parts[value['type']][language] = [];
			parts[value['type']][language].push( { value: useSlug ? value['slug'] : value['id'], label: value['title'] } );
		}
		// console.log(filter);

		// make optgroups
		for ([key, part] of Object.entries(parts)) {
			// console.log(key);
			// console.log(part);
			var part_options = [];
			var name = "";
			switch (key) {
				case "dynamic_template": name = __("Dynamic Templates", 'greyd_hub'); break;
				case "wp_block":		 name = __("Synced Patterns", 'greyd_hub'); break;
				// Comment out WP Templates and WP Template Parts as they're not useful to be imported here
				// case "wp_template":		 name = __("WP Templates", 'greyd_hub'); break;
				// case "wp_template_part": name = __("WP Template Parts", 'greyd_hub'); break;
			}
			if (name != "" && !_.isEmpty(part)) {
				// make optgroup with language codes
				for ([lang, opts] of Object.entries(part)) {
					var opts_filtered = [];
					if (filter.length == 0) opts_filtered = opts;
					else {
						for (var i=0; i<opts.length; i++) {
							// console.log(opts[i]);
							if (filter.indexOf(opts[i].value) == -1 || opts[i].value == template_id)
								opts_filtered.push(opts[i]);
							// else console.log("skipped: "+opts[i].value);
						}
					}
					if (opts_filtered.length > 0) {
						if (lang == 'default') part_options.unshift( { label: name, options: opts_filtered } );
						else part_options.push( { label: name+" ("+lang+")", options: opts_filtered } );
					}
				}
				if (part_options.length > 0) options.push(...part_options);
			}
		}

		// console.log(options);
		return options;
	}

	this.getTemplate = function(template) {
		var template_details = {
			id: template, 
			slug: template, 
			title: template, 
			type: 'dynamic_template', 
			blocks: false
		}
		for ([key, value] of Object.entries(greyd.data.all_templates)) {
			if (template == value['slug'] || template == value['id']) {
				// console.log(value);
				template_details = value;
				break;
			}
		}
		return template_details;
	}

	this.getDynamicTemplate = function(slug, template, setTemplate) {
		if (template.slug == slug) {
			// console.log("already crawled");
			return;
		}
		$.post(
			greyd.ajax_url, {
				'action': 'greyd_ajax',
				'_ajax_nonce': greyd.nonce,
				'mode': 'crawl_template',
				'data': { slug: slug, post_id: greyd.data.post_id }
			}, 
			function(response) {
				// console.log(response);
				var mode = '';
				var href = '';
				var content = '';
				// NO DYNAMIC CONTENT FOUND
				if (response.indexOf('error::') > -1) {
					var tmp = response.split('error::');
					mode = 'info';
					try {
						var msg = JSON.parse(tmp[1]);
						href = 'post.php?post='+msg.ID+'&action=edit';
						content = msg.result;
					}
					// error converting JSON
					catch(e) {
						mode = 'warning';
						content = tmp[1];
					}
				}
				// DYNAMIC CONTENT FOUND
				else {
					mode = 'success';
					if (response.indexOf('info::') > -1) mode = 'info';
					var tmp = response.split(mode+'::');
					var dynamic_content = JSON.parse(tmp[1]);
					// console.log(dynamic_content);
					href = 'post.php?post='+dynamic_content.ID+'&action=edit';
					var fields = [];
					for (var i=0; i<dynamic_content.result.length; i++) {
						var found = false;
						for (var j=0; j<fields.length; j++) {
							if (fields[j].raw == dynamic_content.result[i].dynamic_field.value) {
								fields[j].count++;
								found = true;
								break;
							}
						}
						if (!found) {
							var tmp = dynamic_content.result[i].dynamic_field.value.split('|', 3);
							var atts = { 
								key: tmp[0], 
								type: tmp[1], 
								title: decodeURIComponent(tmp[2]),
								default: dynamic_content.result[i].static_field.value,
								shortcode: dynamic_content.result[i].shortcode
							};
							if (dynamic_content.result[i].markup.indexOf('<div') === -1) {
								var options = JSON.parse(dynamic_content.result[i].markup);
								if (options.type == 'dropdown') {
									atts.options = options.value;
								}
							}
							fields.push( { 
								raw: dynamic_content.result[i].dynamic_field.value, 
								atts: atts,
								count: 1
							} );
						}
					}
					content = fields;
				}
				// console.log( template );
				// console.log( { slug: slug, mode: mode, href: href, content: content } );
				if (template.href != href) {
					setTemplate( { slug: slug, mode: mode, href: href, content: content } );
				}
			}
		);
	}

	this.getDynamicComponents = function(props, state) {
		// console.log(props);
		// console.log(state);

		// change function
		var set_dynamic_content = function(value) {
			// console.log(value);
			// console.log(_.clone(values)); 
			var val = value.target.value;
			// console.log(val);
			// console.log(encodeURIComponent(val));
			var raw = "";
			if (_.has(value.target, 'raw')) raw = value.target.raw;
			var [ dkey, dtype, dtitle, ddefault ] = raw.split('|');
			var dynamic_content = [];
			for (var j=0; j<values.length; j++) {
				if (values[j].dtype == dtype && values[j].dtitle == decodeURIComponent(dtitle)) {
					// console.log(encodeURIComponent(val));
					// console.log(ddefault);
					var newval = _.clone(values[j]);
					newval.dvalue = encodeURIComponent(val);
					values[j] = _.clone(newval);
					dynamic_content.push( _.clone(newval) );
					// console.log(values[j]);
				}
			}
			// console.log(_.clone(values));
			// console.log(dynamic_content);
			if (_.has(state, 'update')) state.update(dynamic_content);
			else {
				props.attributes.dynamic_content.forEach(function(dc, i) {
					// console.log(i);
					// console.log(dc);
					var found = false;
					dynamic_content.forEach(function(dc2, j) {
						if (dc.dtype == dc2.dtype && dc.dtitle == dc2.dtitle) {
							found = true;
						}
					});
					if (!found) dynamic_content.push(dc);
				});
				props.setAttributes( { dynamic_content: dynamic_content } );
			}
		}

		// prepare values
		var values = [];

		// make components
		var fields = state.content;
		// console.log(_.clone(fields));
		// for (var i=fields.length-1; i>=0; i--) {
		// 	if (_.has(fields[i], 'template')) {
		// 		// nested components found
		// 		for (var j=0; j<fields[i].dynamic_blocks.length; j++) {
		// 			// search for duplicate title
		// 			for (var k=0; k<fields.length; k++) {
		// 				if (!_.has(fields[k], 'template')) {
		// 					if (fields[k].atts.type == fields[i].dynamic_blocks[j].atts.type && 
		// 						fields[k].atts.title == fields[i].dynamic_blocks[j].atts.title) {
		// 						// console.log(fields[i].dynamic_blocks[j]);
		// 						fields[k].count = fields[k].count + fields[i].dynamic_blocks[j].count;
		// 						fields[i].dynamic_blocks[j].duplicate = true;
		// 					}
		// 				}
		// 			}
		// 		}
		// 	}
		// }
		// console.log(_.clone(fields));

		var dynamic_components = [];
		var result = [], indicies = [], index = 0;
		for (var i=0; i<fields.length; i++) {
			if (_.has(fields[i], 'template')) {
				// nested components found
				var sub_result = [], sub_indicies = [];
				for (var j=0; j<fields[i].dynamic_blocks.length; j++) {
					if (fields[i].dynamic_blocks[j].duplicate) continue;
					// get nested component values
					var { dynamic_value, val } = greyd.dynamic.makeDynamicComponentValue(props, fields[i].dynamic_blocks[j]);
					values.push(dynamic_value);
					// make nested component
					sub_result.push(greyd.dynamic.makeDynamicComponent(props, fields[i].dynamic_blocks[j], val, set_dynamic_content, state));
					// save index of dynamic_content placeholder
					if (fields[i].dynamic_blocks[j].atts.type == 'dynamic_content') {
						sub_indicies.push( { index: j, template: fields[i].dynamic_blocks[j].atts.template })
					}
				}
				dynamic_components.push( {
					template: fields[i].template,
					result: sub_result,
					indicies: sub_indicies
				} );
			}
			else {
				// check if same template is nested multiple times
				found = false;
				if (fields[i].atts.type == 'dynamic_content') for (var ii=0; ii<indicies.length; ii++) {
					// console.log(indicies[ii].template, fields[i].atts.template);
					if (indicies[ii].template == fields[i].atts.template) {
						indicies[ii].count++;
						found = true;
						break;
					}
				}
				if ( found ) continue;
				// get component values
				var { dynamic_value, val } = greyd.dynamic.makeDynamicComponentValue(props, fields[i]);
				values.push(dynamic_value);
				// make component
				result.push(greyd.dynamic.makeDynamicComponent(props, fields[i], val, set_dynamic_content, state));
				// save index of dynamic_content placeholder
				if (fields[i].atts.type == 'dynamic_content') {
					indicies.push( { index: index, template: fields[i].atts.template, count: 1 })
				}
				index++;
			}
		}
		// console.log(values);

		// add top level components
		dynamic_components.unshift( {
			template: props.attributes.template,
			result: result,
			indicies: indicies
		} );
		// console.log(dynamic_components);

		// replace dynamic_content placeholder fields with nested components
		for (var i=0; i<dynamic_components.length; i++) {
			if (dynamic_components[i].indicies.length > 0) {
				for (var j=0; j<dynamic_components[i].indicies.length; j++) {
					var s_template = dynamic_components[i].indicies[j].template;
					var s_index = dynamic_components[i].indicies[j].index;
					var s_count = dynamic_components[i].indicies[j].count ?? 1;
					// search all nests for dynamic_content placeholder
					for (var ii=0; ii<dynamic_components.length; ii++) {
						if (dynamic_components[ii].template == s_template) {
							// console.log(dynamic_components[i].result[s_index]);
							// console.log(dynamic_components[ii].result);
							if (typeof dynamic_components[i].result[s_index] != 'undefined' &&
								typeof dynamic_components[ii].result != 'undefined') {
								// replace dynamic_content placeholder
								dynamic_components[i].result[s_index] = greyd.dynamic.makeDynamicComponentNest(dynamic_components[ii], s_count);
							}
							break;
						}
					}

				}
			}
		}

		return dynamic_components[0].result;

	}

	this.makeDynamicComponentValue = function(props, field) {
		var dynamic_value = { 
			dkey: field.atts.key,
			dtype: field.atts.type,
			dtitle: field.atts.title,
			dvalue: '' 
		};
		var val = field.atts.default;
		var vals = _.clone(props.attributes.dynamic_content);
		for (var j=0; j<vals.length; j++) {
			if (vals[j].dtype == field.atts.type && vals[j].dtitle == field.atts.title) {
				// console.log(vals[j]);
				// if (vals[j].dvalue != "") {
					// update default value
					val = decodeURIComponent(vals[j].dvalue);
					dynamic_value.dvalue = vals[j].dvalue;
				// }
				break;
			}
		}
		return { dynamic_value: dynamic_value, val: val }
	}

	this.makeDynamicComponentNest = function(dynamic_component, count) {
		var template = greyd.dynamic.getTemplate(dynamic_component.template);
		var label = __("Embedded template '%s'", 'greyd_hub').replace('%s', template.title );
		if ( count && count > 1 )  label += " - "+count+"x";
		return el( 'div', { className: 'dynamic_control dynamic_control__nested' }, [
			el( 'label', { 
				className: 'greyd-label',
			}, label ),
			el( 'div', {}, el( wp.components.Button, {
				variant: 'secondary',
				isSmall: true,
				className: 'has-text',
				href: 'post.php?post='+template.id+'&action=edit',
				target: '_blank',
				text: __("Edit template", 'greyd_hub'),
				icon: 'external',
				iconPosition: 'right',
				style: { marginBottom: "8px" },
			} ) ),
			dynamic_component.result
		] );
	}

	this.makeDynamicComponent = function(props, field, val, set_dynamic_content, state) {
		// raw field value
		var val_default = field.atts.default;
		// console.log(val_default);
		if (typeof val_default !== 'string') val_default = JSON.stringify(val_default);
		var raw = field.atts.key+'|'+field.atts.type+'|'+encodeURIComponent(field.atts.title)+'|'+encodeURIComponent(val_default);
		// make labels
		var label = field.atts.title;
		var help = false;
		if (_.has(field.atts, 'shortcode')) {
			label += " ("+field.atts.shortcode+")";
		}
		if (_.has(field.atts, 'block')) {
			help = "";
			if (_.has(field.atts, 'label')) help += field.atts.label;
			help += " ("+field.atts.block+")";
			var reset = el( wp.components.Button, { 
				className: "is-small",
				icon: 'undo',
				style: { verticalAlign: 'top', color: "rgb(117, 117, 117)" },
				title: __( "Reset content", 'greyd_hub' ),
				onClick: function() { 
					console.log('reset');
					set_dynamic_content({ target: { value: val_default, raw: raw } });
				},
			} );
			help = [ help, reset ];
		}
		if (field.count > 1) label += " - "+field.count+"x";

		
		// make dynamic component
		var element = "";
		switch (field.atts.type) {
			case 'textarea':
			case 'textarea_html':
				// console.log(props);
				// console.log(field);
				// console.log(val);
				var has_blocks = greyd.dynamic.getTemplate(props.attributes.template).blocks;
				var text = String(val);
				// console.log(text);
				if (!has_blocks) text = text.split('\n').join('<br>');
				element = [
					// richtext field
					el( 'label', { className: 'greyd-label' }, label ),
					el( wp.blockEditor.RichText, {
						inlineToolbar: true,
						tagName: 'div',
						className: 'components-text-control__input',
						value: text,
						style: { height: 'auto' },
						onChange: function(value) { 
							// console.log("change: "+value);
							var newText = value;
							if (!has_blocks) newText = value.split('<br>').join('\n');
							set_dynamic_content({ target: { value: newText, raw: raw } });
						},
					} ),
					help ? el( 'p', { className: 'greyd-inspector-help' }, help ) : '',
				];
				break;
			case 'textfield':
				// console.log(props);
				// console.log(field);
				// console.log(val);
				if (field.atts.key.indexOf('url') > -1) {
					// url field
					var suggestions = false;
					if (field.atts.block == 'core/button') suggestions = true;
					element = [
						el( 'label', { className: 'greyd-label' }, label ),
						el( wp.blockEditor.__experimentalLinkControl, {
							value: { 'url': val },
							settings: [],
							showSuggestions: suggestions,
							forceIsEditingLink: true,
							onChange: function( value ) {
								console.log(value);
								set_dynamic_content({ target: { value: value.url, raw: raw } });
							}
						} ),
						// val !== "" ? el( wp.components.Button, { 
						// 	className: "is-link is-destructive",
						// 	onClick: function() { 
						// 		console.log("clear");
						// 		set_dynamic_content({ target: { value: "", raw: raw } });
						// 	},
						// }, __( "Reset link", 'greyd_hub' ) ) : "",
					];

				}
				else if (field.atts.key.indexOf('trigger') > -1) {
					// console.log(props);
					// console.log(field);
					// console.log(val);
					try { val = JSON.parse(val); } 
					catch(e) { }
					element = [
						el( 'label', { className: 'greyd-label' }, label ),
						el( wp.components.Tip, { }, __("Focus the block to edit the trigger in the toolbar.", 'greyd_hub') ),
						// greyd.tools.components.triggerPickerControls({
						// 	isSelected: true,
						// 	clientId: props.clientId,
						// 	attributes: { trigger: val },
						// 	setAttributes: function(value) {
						// 		console.log(value);
						// 		set_dynamic_content({ target: { value: JSON.stringify(value.trigger), raw: raw } });
						// 	}
						// }),
						// el( wp.components.Button, { 
						// 	className: "is-link",
						// 	style: { display: 'block' },
						// 	onClick: function() { 
						// 		console.log("focus block");
						// 		// wp.data.dispatch('core/block-editor').selectBlock(parent_id);
						// 		// set_dynamic_content({ target: { value: "", raw: raw } });
						// 	},
						// }, __( "Adjust trigger", 'greyd_hub' ) ),
					];
				}
				else if (field.atts.key == 'rendered') {
					// core/table-of-contents
					element = [
						el( 'label', { className: 'greyd-label' }, label ),
						el( wp.components.Tip, { }, __("The contents are filled automatically.", 'greyd_hub') )
					];
				}
				else if (field.atts.key == 'image') {
					// console.log( 'DynamicImageControl', props, field, val);
					try { val = JSON.parse(val); } 
					catch(e) { }
					element = [
						el( 'label', { className: 'greyd-label' }, label ),
						el( greyd.components.DynamicImageControl, {
							clientId: props.clientId,
							value: val,
							onChange: (value) => {
								console.log(value);
								set_dynamic_content({ target: { value: JSON.stringify(value), raw: raw } });
							}
						} ),
					];
				}
				else if (field.atts.key.indexOf('src') > -1) {
					// core/video
					var url = "";
					var id = -1;
					Object.keys( greyd.data.media_urls ).forEach( key => {
						if ( greyd.data.media_urls[key].src == val ) {
							id = key;
							url = val
						}
					} );
					element = [
						el( 'label', { className: 'greyd-label' }, label ),
						el( wp.blockEditor.MediaUploadCheck, { 
							fallback: el( 'p', { className: "greyd-inspector-help" }, __("You need permission to upload media.", 'greyd_hub') ) 
						}, [
							el( wp.blockEditor.MediaUpload, {
								allowedTypes: 'video/*',
								value: id,
								onSelect: function(value) {
									// console.log(value);
									set_dynamic_content({ target: { value: value.url, raw: raw } });
								},
								render: function(obj) {
									return el( wp.components.Button, { 
										className: (url == '') ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
										style: { marginBottom: '8px' },
										onClick: obj.open 
									}, (url == '') ? __( "Select content", 'greyd_hub' ) : [
										el( 'video', { src: url } )
									] )
								},
							} ),
						] )
					];
				}
				else if (field.atts.key == 'height') {
					element = [
						el( greyd.components.RangeUnitControl, {
							label: label,
							value: val === 'null' ? field.atts.default : val,
							modes: [ 'default' ],
							units: [ 'px' ],
							onChange: function(value) {
								set_dynamic_content({ target: { value: value, raw: raw } });
							}
						} ),
					];
				}
				else if (field.atts.key == 'greydStyles/maxWidth') {
					// console.log(props);
					element = [
						el( greyd.components.RangeUnitControl, {
							label: label,
							value: val === 'null' ? field.atts.default : val,
							max: 1200,
							onChange: function(value) {
								set_dynamic_content({ target: { value: value, raw: raw } });
							}
						} ),
					];
				}
				else {
					// just text
					element = [
						el( wp.components.TextControl, {
							label: label,
							value: val,
							// className: 'components-text-control__input',
							onChange: function(value) { 
								console.log("change: "+value);
								set_dynamic_content({ target: { value: value, raw: raw } });
							},
						} ),
					];
				}
				// dynamic tags
				if ( element != "" && ( field.atts.key.indexOf('url') > -1 || field.atts.key.indexOf('src') > -1 ) ) {
					var currentOptions = greyd.dynamic.getTagOptions('file', props);
					element.push(
						el( currentOptions.control, {
							value: (val.toString().indexOf('_') === 0) ? val.slice(1,-1) : '',
							options: currentOptions.options,
							onChange: function(value) { 
								console.log("select dynamic:");
								console.log(value);
								set_dynamic_content({ target: { value: '_'+value+'_', raw: raw } })
							},
						} )
					);
				}
				// help
				if ( element != "" && help ) {
					element.push( el( 'p', { className: 'greyd-inspector-help' }, help ) );
				}
				break;
			case 'focal_point':
				// console.log(props);
				// console.log(field);
				// console.log(val);
				try { val = JSON.parse(val); } 
				catch(e) {
					if ( typeof val === 'string' && val.indexOf('% ') > -1 ) {
						var tmp = val.split('% ');
						val = { x: parseInt(tmp[0])/100.0, y: parseInt(tmp[1])/100.0 };
					}
				}
				element = [
					el( 'label', { className: 'greyd-label' }, label ),
					el( wp.components.FocalPointPicker, {
						value: val,
						onChange: (value) => {
							console.log(value, field.atts.block);
							var final = JSON.stringify(value);
							if ( field.atts.block == 'core/group' ) {
								final = (value.x*100)+'% '+(value.y*100)+'%';
							}
							set_dynamic_content({ target: { value: final, raw: raw } });
						}
					} ),
					help ? el( 'p', { className: 'greyd-inspector-help' }, help ) : '',
				];
				break;
			case 'dropdown_anchor':
			case 'dropdown_menu':
			case 'dropdown_navigation':
			case 'dropdown_forms':
			case 'dropdown_h':
			case 'dropdown_sizes':
				// console.log(props);
				// console.log(field);
				var raw_options = [];
				if (field.atts.type == 'dropdown_anchor') {
					var opts = greyd.tools.searchAttribute(wp.data.select("core/block-editor").getBlocks(), 'anchor');
					// console.log(opts);
					raw_options.push( [ '', __("Select target", 'greyd_hub') ] );
					raw_options.push( [ '_top', __("Page top", 'greyd_hub') ] );
					for (var j=0; j<opts.length; j++) {
						raw_options.push( [ opts[j].id, opts[j].title.rendered ] );
					}
					raw_options.push( [ '_bottom', __("Page bottom", 'greyd_hub') ] );
				}
				else if (field.atts.type == 'dropdown_menu') {
					raw_options.push( [ '', __("Select menu", 'greyd_hub') ] );
					for (var j=0; j<greyd.data.nav_menus.length; j++) {
						raw_options.push( [ greyd.data.nav_menus[j].slug, greyd.data.nav_menus[j].title ] );
					}
				}
				else if (field.atts.type == 'dropdown_navigation') {
					raw_options.push( [ '', __("Select menu", 'greyd_hub') ] );
					if ( greyd.data.navigation_menus ) for (var j=0; j<greyd.data.navigation_menus.length; j++) {
						raw_options.push( [ greyd.data.navigation_menus[j].id, greyd.data.navigation_menus[j].title ] );
					}
				}
				// else if (field.atts.type == 'dropdown_forms') {
				// 	raw_options.push( [ '', __("Select form", 'greyd_hub') ] );
				// 	for (var j=0; j<greyd.data.forms.length; j++) {
				// 		raw_options.push( [ greyd.data.forms[j].id, greyd.data.forms[j].title ] );
				// 	}
				// }
				else if (field.atts.type == 'dropdown_h') raw_options = [
					[1, 'H1'], [2, 'H2'], [3, 'H3'], [4, 'H4'], [5, 'H5'], [6, 'H6']
				];
				else if (_.has(field.atts, 'options')) raw_options = field.atts.options;

				var options = [];
				if (raw_options.length > 0) for (var j=0; j<raw_options.length; j++) {
					// console.log(raw_options[j]);
					options.push( { value: raw_options[j][0], label: raw_options[j][1] } );
				}
				if (field.atts.type == 'dropdown_forms') {
					options = greyd.dynamic.getForms(val);
				}
				if (field.atts.type == 'dropdown_sizes') {
					wp.data.select("core/block-editor").getSettings().imageSizes.forEach( size => {
						options.push( { value: size.slug, label: size.name } );
					} );
					help.push( el( 'div', {}, __( "just a suggestion, the rendered image is selected from the actual available image sizes.", 'greyd_hub' ) ) );
					if ( val != 'full' ) {
						help.push( el( 'div', {}, __( "Animated gif images only preserve their animation with the \"Full Size\" resolution. Other sizes show one frame of animation only.", 'greyd_hub' ) ) );
					}
				}
				// console.log(options);
				element = [
					el( greyd.components.OptionsControl, {
						label: label,
						help: help,
						value: val,
						options: options,
						onChange: function(value) { 
							console.log("select: "+value);
							set_dynamic_content({ target: { value: value, raw: raw } });
						},
					} )
				];
				break;
			case 'file_picker':
			case 'file_picker_file':
			case 'file_picker_json':
			case 'file_picker_image':
				// console.log( 'file_picker', props, field, val);

				var types = '*';
				if (field.atts.type == 'file_picker') types = 'image/*,application/json';
				if (field.atts.type == 'file_picker_json') types = 'application/json';
				if (field.atts.type == 'file_picker_image') types = 'image/*';
				if (field.atts.block == 'core/media-text') types = 'image/*,video/*';
				
				var url = "";
				var mime = "";
				if (greyd.data.media_urls[val]) {
					url = greyd.data.media_urls[val].src;
					mime = greyd.data.media_urls[val].type;
				}
				
				if (mime.indexOf('/json') > 0) {
					// console.log("init dynamic anim");
					var inspector_anim_id = 'anim_'+field.atts.title.split(' ').join('')+'_'+props.clientId;
					if ( _.has( greyd.tools, 'initAnim' ) ) {
						greyd.tools.initAnim([inspector_anim_id]);
					} else if ( _.has( greyd.lottie, 'initAnim' ) ) {
						greyd.lottie.initAnim([inspector_anim_id]);
					}
				}
			
				// dynamic tag options
				var currentOptions = greyd.dynamic.getTagOptions('file', props);
				
				// console.log(options);
				element = [
					// select media
					el( 'label', { className: 'greyd-label' }, label ),
					el( wp.blockEditor.MediaUploadCheck, { 
						fallback: el( 'p', { className: "greyd-inspector-help" }, __("You need permission to upload media.", 'greyd_hub') ) 
					}, [
						el( wp.blockEditor.MediaUpload, {
							allowedTypes: types,
							value: val,
							onSelect: function(value) { 
								// console.log("select:");
								// console.log(value);
								set_dynamic_content({ target: { value: value.id, raw: raw } });
							},
							render: function(obj) {
								return el( wp.components.Button, { 
									className: (url == '') ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview',
									style: { marginBottom: '8px' },
									onClick: obj.open 
								}, (url == '') ? __( "Select content", 'greyd_hub' ) : [
									mime.indexOf('image/') === 0 ? el( 'img', { src: url } ) :
									mime.indexOf('video/') === 0 ? el( 'video', { src: url } ) :
									mime.indexOf('/json') > 0 ? el( 'div', { 
										id: inspector_anim_id, 
										className: 'bm_icon auto', 
										"data-anim": 'auto',
										'data-src': url 
									} ) : el( 'div', { style: { background: '#fff' } }, url )
								] )
							},
						} ),
						// dynamic tag options
						(
							// field.atts.block != 'core/image'
							// && field.atts.block != 'core/media-text'
							// && field.atts.block != 'core/cover'
							// && (
								field.atts.type == 'file_picker'
								|| field.atts.type == 'file_picker_image'
								|| field.atts.type == 'file_picker_file'
							// )
						) ? [
							el( currentOptions.control, {
								value: (val.toString().indexOf('_') === 0) ? val.slice(1,-1) : '',
								options: currentOptions.options,
								onChange: function(value) { 
									console.log("select dynamic:");
									console.log(value);
									set_dynamic_content({ target: { value: '_'+value+'_', raw: raw } })
								},
							} ),
						] : '',
						// reset button
						(val != '' || url != '') ? el( wp.components.Button, { 
							className: "is-link is-destructive",
							style: { display: 'block' },
							onClick: function() { 
								console.log('clear');
								if (_.has(field.atts, 'shortcode')) {
									set_dynamic_content({ target: { value: '', raw: raw } });
								}
								if (_.has(field.atts, 'block')) {
									set_dynamic_content({ target: { value: -1, raw: raw } });
								}
							},
						}, __( "Reset content", 'greyd_hub' ) ) : ""
					] ),
					help ? el( 'p', { className: 'greyd-inspector-help' }, help ) : ''
				];
				break;
			case 'vc_link':
				// console.log(props);
				// console.log(field);
				// console.log(val);
				var link = { 
					'type': 'url', 
					'url': '',
					'id': '',
					'title': ''
				};
				var vals = val.split('|');
				for (var k=0; k<vals.length; k++) {
					if (vals[k] == "") continue;
					var tmp = vals[k].split(':');
					if (tmp[0] == 'url') {
						link.url = decodeURIComponent(tmp[1]);
						link.id = decodeURIComponent(tmp[1]);
					}
					if (tmp[0] == 'title') 
						link.title = decodeURIComponent(tmp[1]);
					if (tmp[0] == 'target' && tmp[1] == '_blank')
						link.opensInNewTab = true;
				}
				element = [
					el( 'label', { className: 'greyd-label' }, label ),
					el( wp.blockEditor.__experimentalLinkControl, {
						value: link,
						onChange: function( value ) {
							console.log(value);
							var newVal = [
								'url:'+(value.url ? encodeURIComponent(value.url) : ''),
								'title:'+(value.title ? encodeURIComponent(value.title) : ''),
								'target:'+(value.opensInNewTab ? '_blank' : ''),
								'rel:',
							];
							set_dynamic_content({ target: { value: newVal.join('|'), raw: raw } });
						}
					} ),
					link.url !== "" ? el( wp.components.Button, { 
						className: "is-link is-destructive",
						onClick: function() { 
							console.log("clear");
							set_dynamic_content({ target: { value: "", raw: raw } });
						},
					}, __( "Reset link", 'greyd_hub' ) ) : "",
					help ? el( 'p', { className: 'greyd-inspector-help' }, help ) : '',
				];
				break;
			case 'dynamic_content':
				var dtemplate = greyd.dynamic.getTemplate(field.atts.template);
				element = [
					el( 'label', { 
						className: 'greyd-label', 
						style: { width: '100%', fontWeight: '600' } 
					}, __("Embedded template '%s'", 'greyd_hub') ),
					el( 'div', { 
						className: 'greyd-label', 
						style: { width: '100%', fontStyle: 'italic' } 
					}, dtemplate.title ),
					el( wp.components.Spinner )
				];
				break;
		}
		return el( 'div', { className: 'dynamic_control' }, element );
	}

	this.getTemplateInfobox = function(props, state) {
		var slug = props.attributes.template;
		var result = state.content;
		if (
			typeof theme_wordings !== "undefined" && 
			typeof theme_wordings.vc[result] !== "undefined" && 
			theme_wordings.vc[result].length > 0
		) {
			return [ 
				el( 'b', {}, theme_wordings.vc[result]+' ' ), 
				el( 'span', {}, theme_wordings.vc[result+'_descr'].replace('%s', slug) ) 
			];
		}
		return result;
	}

	this.getTagOptions = function(mode, props, max = 6) {
		var currentOptions = greyd.dynamic.tags.getCurrentOptions(mode, props.clientId);
		if ( currentOptions.options.length < max ) {
			// use options but remove 'icon' key, otherwise the icon slug will be displayed as text
			return {
				options: currentOptions.options.map( function(item) {
					return { value: item?.value, label: item?.label || item?.value }
				} ),
				control: greyd.components.ButtonGroupControl
			}
		}
		else {
			return {
				options: [
					{ label: __("Select dynamic source", 'greyd_hub'), value: '' },
					currentOptions
				],
				control: greyd.components.OptionsControl
			}
		}
	}

	//
	// dynamic controls
	this.dynamicControls = function(props, state) {

		/**
		 * Convert old shortcode Tags to Dynamic Tags
		 * @param {object} props Block properties.
		 * @param {object} state Dynamic Block Template properties.
		 * @param {string} type Dynamic Tag type.
		 * @returns void
		 */
		const convertShortcodeToTags = function(props, state) {
			
			// console.log("maybe convert tags...");
			if (!_.has(props.attributes, "dynamic_content") || props.attributes.dynamic_content.length == 0) return;
					
			var { options } = greyd.dynamic.tags.getCurrentOptions('tags', props.clientId);
			if (options.length == 0) return;

			var dynamic_content = greyd.dynamic.tags.match(options, props.attributes.dynamic_content);
			if (dynamic_content) state.update(_.clone(dynamic_content));
			
		}
		
		var pt = __("Dynamic Template", 'greyd_hub');
		var content = __("Shortcodes", 'greyd_hub');
		var dynamic = true;
		if (_.has(state, 'postType')) {
			content = __("Blocks", 'greyd_hub');
			if (state.postType == "dynamic_template") convertShortcodeToTags(props, state);
			else dynamic = false;
			if (state.postType == "wp_block") pt = __("Reusable", 'greyd_hub');
			else if (state.postType == "wp_template") pt = __("WP Template", 'greyd_hub');
			else if (state.postType == "wp_template_part") pt = __("WP Template Part", 'greyd_hub');
		}

		var dynamic_controls_enabled = '';
		if (_.has(props.attributes, "dynamic_fields") && props.attributes.dynamic_fields.length > 0) {
			dynamic_controls_enabled = ' disabled';
		}

		// not dynamic
		if ( !dynamic ) {
			return null;
		}
		// loader
		else if ( _.isEmpty(state.mode) ) {
			return el( wp.components.Spinner );
		}
		// dynamic controls
		else if ( state.mode == "success" ) {
			return el( 'div', { 
				className: 'dynamic_controls'+dynamic_controls_enabled,
				'data-block': props.clientId 
			}, greyd.dynamic.getDynamicComponents(props, state) );
		}
		// info message
		else {
			return el( wp.components.Tip, { }, greyd.dynamic.getTemplateInfobox(props, state) );
		}
	}
		
};