/**
 * Register block editor RichText formats.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register Dynamic Tag RichText format.
	 */
	wp.richText.registerFormatType( 'greyd/dtag', {
		title: __('Dynamic Tag', 'greyd_hub'),
		tagName: 'span',
		className: 'is-tag',
		contenteditable: false,
		attributes: {
			tag: 'data-tag',
			params: 'data-params',
		},
		edit: function ( props ) {
			// console.log(props);

			var parent = null;
			// var options = [ 
			// 	{ value: "", label: __("Select source", 'greyd_hub') }
			// ];

			// check if parent isset
			if ( typeof props.contentRef.current !== 'undefined' ) {
				// console.log(props.contentRef);
				// console.log(props.contentRef.current);
				if (_.has(props.contentRef.current.dataset, 'block')) {
					parent = props.contentRef.current.dataset.block;
				}
				else {
					var current = props.contentRef.current;
					while (current && !_.has(current.dataset, 'block')) {
						current = current.parentElement;
					}
					if (current && _.has(current.dataset, 'block')) {
						parent = current.dataset.block;
					}
					// else console.log("no parent clientId found!");
				}
			}

			/**
			 * Get all dynamic tag options.
			 * @since 1.7.4
			 */
			const options = greyd.dynamic.tags.getRichTextOptions( parent );

			let { tag: dtag, params } = props.activeAttributes;
			if ( !_.isEmpty( params ) ) {
				params = JSON.parse( params );
			}

			const updateFormat = function(new_dtag, new_params) {
				var oldtext = greyd.dynamic.tags.renderLabel(dtag, options);
				var text = greyd.dynamic.tags.renderLabel(new_dtag, options);
				if (oldtext == text) {
					props.onChange(
						wp.richText.applyFormat( props.value, {
							type: 'greyd/dtag',
							attributes: {
								tag: dtag,
								params: JSON.stringify(new_params)
							}
						} ) 
					);
				}
				else {
					var newpos = Math.floor(text.length/2);
					if (dtag != "") {
						var newstart = props.value.start;
						var newend = props.value.end;
						// console.log(dtag);
						while (props.value.formats[newstart] && 
							props.value.formats[newstart][0].type == 'greyd/dtag' && 
							props.value.formats[newstart][0].attributes.tag == dtag) {
							newstart--;
						}
						while (props.value.formats[newend] && 
							props.value.formats[newend][0].type == 'greyd/dtag' && 
							props.value.formats[newend][0].attributes.tag == dtag) {
							newend++;
						}
						props.value.start = newstart+1;
						props.value.end = newend;
					}
					props.onChange(
						wp.richText.insert( props.value, 
							wp.richText.applyFormat( 
								wp.richText.create( { 
									text: text,
								} ), {
									type: 'greyd/dtag',
									attributes: {
										tag: new_dtag,
										params: JSON.stringify(new_params)
									}
								}, 0, text.length 
							)
						)
					);
					setTimeout(function() {
						// // console.log("setting cursor position ...");
						// var sel = window.getSelection();
						// var range = document.createRange();
						// if (range.startContainer.length && newpos <= range.startContainer.length) {
						// 	range.setStart(sel.focusNode, newpos);
						// 	sel.removeAllRanges();
						// 	sel.addRange(range);
						// }
					}, 0);
				}
			}
			
			const tagParams = function() {
				// get tag type from current options
				var type = false;
				options.forEach( option => {
					if ( !type && option.options ) option.options.forEach( opt => {
						if ( !type && opt.type && opt.value && opt.value == dtag ) {
							type = opt.type;
						}
					} );
				} );
				// make controls
				var controls = [];
				switch (true) {
					case dtag == 'symbol':
						if (!_.has(params, 'symbol')) params.symbol = ''; 
						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.symbol,
							options: greyd.dynamic.tags.getFormats('symbols'),
							onChange: function(value) { 
								// console.log( 'change symbol' );
								var newval = { symbol: value };
								updateFormat(dtag, newval);
							},
						} ));
						break;
					case dtag == 'now':
					case dtag == 'date':
					case type == 'date' || type == 'datetime-local':
						if (!_.has(params, 'format')) params.format = ''; 
						if (!_.has(params, 'customFormat')) params.customFormat = null; 

						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.format,
							options: greyd.dynamic.tags.getFormats('dates'),
							onChange: function(value) { 
								// console.log( 'change date format' );
								var newval = { format: value, customFormat: null };
								updateFormat(dtag, newval);
							},
						} ));
						if (params.format == "custom") {
							controls.push([
								el( wp.components.TextControl, {
									label: __("Individual time format", 'greyd_hub'),
									style: { width: "100%", marginBottom: '3px' },
									value: params.customFormat,
									onChange: function(value) { 
										// console.log( 'change date format' );
										var newval = { format: "custom", customFormat: value };
										updateFormat(dtag, newval);
									},
								}),
								el( 'small', {},  __("Here you can enter an individual time format. Use the standard PHP time format. (e.g. Y-m-d H:i:s becomes 2021-11-27 13:22:40)", 'greyd_hub') )
							]);
						}
			
						break;
					case dtag == 'content': 
						if (!_.has(params, 'count')) params.count = -1; 
						controls.push([
							el( wp.components.RangeControl, {
								label: __("Character count", 'greyd_hub'),
								value: parseInt(params.count),
								step: 1, min: -1, max: 500,
								onChange: function(value) { 
									// console.log( 'change count' );
									var newval = { count: value };
									updateFormat(dtag, newval);
								},
							} ),
							el( 'small', {},  __("When you set a character limit to '0', the entire content will be displayed unfiltered. This way, settings of this block might not affect the frontend, such as margins or colors. Use a character limit to avoid this or use the 'Content' block instead.", 'greyd_hub') )
						]);
						break;
					case dtag == 'site-title':
					case dtag == 'site-url':
					case dtag == 'post-url':
					case dtag == 'site-sub':
					case dtag == 'title':
					case type == 'email' || type == 'url':
						if (!_.has(params, 'isLink')) params.isLink = false; 
						if (!_.has(params, 'linkStyle')) params.linkStyle = ''; 
						if (!_.has(params, 'blank')) params.blank = false; 
						controls.push(el( wp.components.ToggleControl, {
							label: __("Link", 'greyd_hub'),
							checked: params.isLink,
							onChange: function(value) { 
								// console.log( 'toggle link' );
								var newval = { };
								if (value) newval = { 
									isLink: value,
									linkStyle: params.linkStyle, 
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						if (params.isLink) {
							controls.push(el( greyd.components.OptionsControl, {
								style: { width: "100%", marginTop: '10px', marginBottom: '10px' },
								value: params.linkStyle,
								options: greyd.dynamic.tags.getFormats('styles'),
								onChange: function(value) { 
									// console.log( 'change link format' );
									var newval = { 
										isLink: params.isLink,
										linkStyle: value, 
										blank: params.blank 
									};
									updateFormat(dtag, newval);
								},
							} ));
							type !== 'email' && controls.push(el( wp.components.ToggleControl, {
								label: __("Open in a new tab", 'greyd_hub'),
								checked: params.blank,
								onChange: function(value) { 
									// console.log( 'toggle target' );
									var newval = { 
										isLink: params.isLink,
										linkStyle: params.linkStyle, 
										blank: value 
									};
									updateFormat(dtag, newval);
								},
							} ));
						}
						break;
					case dtag == 'site-logo':
					case dtag == 'image':
						if (!_.has(params, 'width')) params.width = '100%'; 
						if (!_.has(params, 'isLink')) params.isLink = false; 
						if (!_.has(params, 'blank')) params.blank = false; 
						controls.push(el( wp.components.__experimentalUnitControl, {
							label: __("Width", 'greyd_hub'),
							style: { marginBottom: '10px' },
							labelPosition: 'edge',
							value: params.width,
							units: [ { value: '%', label: '%' }, { value: 'px', label: 'px' } ],
							onChange: function(value) { 
								// console.log( 'change width' );
								var newval = { 
									width: value,
								};
								if (params.isLink) newval = { 
									width: value,
									isLink: params.isLink,
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						controls.push(el( wp.components.ToggleControl, {
							label: __("Link", 'greyd_hub'),
							checked: params.isLink,
							onChange: function(value) { 
								// console.log( 'toggle link' );
								var newval = { 
									width: params.width
								};
								if (value) newval = { 
									width: params.width,
									isLink: value,
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						if (params.isLink) {
							controls.push(el( wp.components.ToggleControl, {
								label: __("Open in a new tab", 'greyd_hub'),
								checked: params.blank,
								onChange: function(value) { 
									// console.log( 'toggle target' );
									var newval = { 
										width: params.width,
										isLink: params.isLink,
										blank: value 
									};
									updateFormat(dtag, newval);
								},
							} ));
						}
						break;
					case dtag == 'author':
						if (!_.has(params, 'format')) params.format = ''; 
						if (!_.has(params, 'width')) params.width = '24px'; 
						if (!_.has(params, 'isLink')) params.isLink = false; 
						if (!_.has(params, 'linkStyle')) params.linkStyle = ''; 
						if (!_.has(params, 'blank')) params.blank = false; 
						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.format,
							options: greyd.dynamic.tags.getFormats('author'),
							onChange: function(value) { 
								// console.log( 'change author format' );
								var newval = { 
									format: value,
									width: params.width
								};
								if (params.isLink) newval = { 
									format: value,
									width: params.width,
									isLink: params.isLink,
									linkStyle: params.linkStyle, 
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						if (params.format == 'avatar') controls.push(el( wp.components.__experimentalUnitControl, {
							label: __("Width", 'greyd_hub'),
							style: { marginBottom: '10px' },
							labelPosition: 'edge',
							value: params.width,
							units: [ { value: 'px', label: 'px' } ],
							min: 1, max: 96,
							onChange: function(value) { 
								// console.log( 'change width' );
								var newval = { 
									format: params.format,
									width: value,
								};
								if (params.isLink) newval = { 
									format: params.format,
									width: value,
									isLink: params.isLink,
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						controls.push(el( wp.components.ToggleControl, {
							label: __("Link", 'greyd_hub'),
							checked: params.isLink,
							onChange: function(value) { 
								// console.log( 'toggle link' );
								var newval = { 
									format: params.format,
									width: params.width
								};
								if (value) newval = { 
									format: params.format,
									width: params.width,
									isLink: value,
									linkStyle: params.linkStyle, 
									blank: params.blank 
								};
								updateFormat(dtag, newval);
							},
						} ));
						if (params.isLink) {
							if (params.format !== 'avatar') 
								controls.push(el( greyd.components.OptionsControl, {
									style: { width: "100%", marginTop: '10px', marginBottom: '10px' },
									value: params.linkStyle,
									options: greyd.dynamic.tags.getFormats('styles'),
									onChange: function(value) { 
										// console.log( 'change link format' );
										var newval = { 
											format: params.format,
											width: params.width,
											isLink: params.isLink,
											linkStyle: value, 
											blank: params.blank 
										};
										updateFormat(dtag, newval);
									},
								} ));
							controls.push(el( wp.components.ToggleControl, {
								label: __("Open in a new tab", 'greyd_hub'),
								checked: params.blank,
								onChange: function(value) { 
									// console.log( 'toggle target' );
									var newval = { 
										format: params.format,
										width: params.width,
										isLink: params.isLink,
										linkStyle: params.linkStyle, 
										blank: value
									};
									updateFormat(dtag, newval);
								},
							} ));
						}
						break;
					case dtag == 'categories':
					case dtag == 'tags':
					case dtag.indexOf('customtax-') === 0:
					case dtag.indexOf('taxonomy-') === 0:
						if (!_.has(params, 'format')) params.format = '';
						if (!_.has(params, 'isLink')) params.isLink = false;
						if (!_.has(params, 'description')) params.description = '';
						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.format,
							options: greyd.dynamic.tags.getFormats('seperators'),
							onChange: function(value) { 
								// console.log( 'change seperator format' );
								var newval = { 
									format: value,
									isLink: params.isLink,
									description: params.description
								};
								updateFormat(dtag, newval);
							},
						} ));
						controls.push(el( wp.components.ToggleControl, {
							label: __("Link", 'greyd_hub'),
							checked: params.isLink,
							onChange: function(value) { 
								// console.log( 'toggle link' );
								var newval = { 
									format: params.format, 
									isLink: value,
									description: params.description
								};
								updateFormat(dtag, newval);
							},
						} ));
						/**
						 * @since 2.11.0 option for category description
						 */
						controls.push( 
							el( wp.components.BaseControl, {}, [
								el( greyd.components.ButtonGroupControl, {
									label: __("Description", 'greyd_hub'),
									value: params.description,
									options: [
										{ value: '', label: __("Hide", 'greyd_hub') },
										{ value: 'show', label: __("Show", 'greyd_hub') },
										{ value: 'only', label: __("Only", 'greyd_hub') },
									],
									help: __("'Show' will display the description after the term name. 'Only' will display only the description.", 'greyd_hub'),
									onChange: function(value) {
										// console.log( 'change description' );
										var newval = { 
											format: params.format,
											isLink: params.isLink,
											description: value
										};
										updateFormat(dtag, newval);
									}
								} )
							] )
						);

						break;
					case dtag == 'count':
						var { taxes } = wp.data.useSelect(select => ({
							taxes: select("core").getTaxonomies()
						}));
						var terms = {};
						taxes.forEach(function(val, i) {
							// console.log(val);
							if (val.visibility.public == true) {
								var { t } = wp.data.useSelect(select => ({
									t: select("core").getEntityRecords('taxonomy', val.slug, {
										per_page: -1,
										orderby: 'name',
										order: 'asc',
										hide_empty: true
									})
								}));
								if (!_.isEmpty(t)) terms[val.slug] = t;
							}
						});
						// console.log(terms);
						if (!_.has(params, 'format')) params.format = ''; 
						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.format,
							options: [
								{ value: '', label: __("Select", 'greyd_hub') },
								{ value: 'posttype', label: __("Post Type", 'greyd_hub') },
								// ...
							],
							onChange: function(value) { 
								// console.log( 'change count format' );
								var newval = { 
									format: value, 
								};
								if (value == 'posttype') newval.posttype = params.posttype;
								updateFormat(dtag, newval);
							},
						} ));
						if (params.format == 'posttype') {
							if (!_.has(params, 'posttype')) params.posttype = ''; 
							var opts = [
								{ value: '', label: __("Select post type", 'greyd_hub') },
								{ value: 'post', label: __("Posts", 'greyd_hub') },
								{ value: 'page', label: __("Pages", 'greyd_hub') },
								// ... dpt
							];
							greyd.data.post_types.forEach(function(val, i) {
								opts.push({ value: val.slug, label: val.plural });
							});
							controls.push(el( greyd.components.OptionsControl, {
								style: { width: "100%", marginBottom: '10px' },
								value: params.posttype,
								options: opts,
								onChange: function(value) { 
									// console.log( 'change count posttype format' );
									var newval = { 
										format: params.format, 
										posttype: value,
									};
									updateFormat(dtag, newval);
								},
							} ));
							if (params.posttype != '' && params.posttype != 'page') {
								if (!_.has(params, 'taxonomy')) params.taxonomy = ''; 
								var opts = [ { value: '', label: __("All posts", 'greyd_hub') } ];
								taxes.forEach(function(val, i) {
									// console.log(val);
									if (val.types.indexOf(params.posttype) > -1) opts.push({ value: val.slug, label: val.name });
								});
								if (opts.length > 1) {
									controls.push(el( greyd.components.OptionsControl, {
										style: { width: "100%", marginBottom: '10px' },
										value: params.taxonomy,
										options: opts,
										onChange: function(value) { 
											// console.log( 'change count tax format' );
											var newval = { 
												format: params.format, 
												posttype: params.posttype,
												taxonomy: value,
											};
											updateFormat(dtag, newval);
										},
									} ));
									if (params.taxonomy != '' && _.has(terms, params.taxonomy)) {
										if (!_.has(params, 'term')) params.term = ''; 
										var opts = [ { value: '', label: __("Select", 'greyd_hub') } ];

										terms[params.taxonomy].forEach(function(val, i) {
											// console.log(val);
											opts.push({ value: val.id, label: val.name+' ('+val.count+')' });
										});
										controls.push(el( greyd.components.OptionsControl, {
											style: { width: "100%", marginBottom: '10px' },
											value: params.term,
											options: opts,
											onChange: function(value) { 
												// console.log( 'change count tax term format' );
												var newval = { 
													format: params.format, 
													posttype: params.posttype,
													taxonomy: params.taxonomy,
													term: value
												};
												updateFormat(dtag, newval);
											},
										} ));
									}

								}
							}
						}

						break;
					case dtag == 'price' && greyd.tools.isPluginActive( "woocommerce/woocommerce.php" ):
						if (!_.has(params, 'format')) params.format = ''; 
						if (!_.has(params, 'format_thousands')) params.format_thousands = ''; 
						if (!_.has(params, 'showSale')) params.showSale = false;
						const options = greyd.dynamic.tags.getFormats('price');
						// console.log(params);
						controls.push(el( greyd.components.OptionsControl, {
							label: __("Currency symbol", 'greyd_hub'),
							style: { width: "100%", marginBottom: '10px' },
							value: params.symbol,
							options: options.symbols,
							onChange: function(value) { 
								// console.log( 'change price symbol' );
								var newval = { 
									symbol: value,
									format: params.format,
									format_thousands: params.format_thousands,
									showSale: params.showSale
								};
								updateFormat(dtag, newval);
							},
						} ));
						controls.push(el( greyd.components.OptionsControl, {
							label: __("Formatting", 'greyd_hub'),
							style: { width: "100%", marginBottom: '10px' },
							value: params.format,
							options: options.formats,
							onChange: function(value) { 
								// console.log( 'change price format' );
								var newval = { 
									format: value,
									symbol: params.symbol,
									format_thousands: params.format_thousands,
									showSale: params.showSale
								};
								updateFormat(dtag, newval);
							},
						} ));
						/**
						 * format thousands seperator
						 * @since 1.7.0
						 */
						controls.push(el( greyd.components.OptionsControl, {
							style: { width: "100%", marginBottom: '10px' },
							value: params.format_thousands,
							options: options.formats_thousands,
							onChange: function(value) { 
								// console.log( 'change price format thousands' );
								var newval = { 
									format_thousands: value,
									format: params.format,
									symbol: params.symbol,
									showSale: params.showSale
								};
								updateFormat(dtag, newval);
							},
						} ));
						controls.push(el( wp.components.ToggleControl, {
							label: __("Show crossed out price", 'greyd_hub'),
							checked: params.showSale,
							onChange: (value) => { 
								var newval = { 
									showSale: value,
									symbol: params.symbol,
									format: params.format,
									format_thousands: params.format_thousands
								};
								updateFormat(dtag, newval);
							},
						} ));
						break;
					case dtag == 'archive-title':
						if (!_.has(params, 'hidePrefix')) params.hidePrefix = false;
						controls.push(el( wp.components.ToggleControl, {
							label: __("Remove prefix", 'greyd_hub'),
							help: __("The preceding text describing the archive type (e.g. 'Year:' in 'Year: 2022') is removed.", 'greyd_hub'),
							checked: params.hidePrefix,
							onChange: (value) => updateFormat( dtag, { hidePrefix: value } )
						} ));
						break;
					case dtag == 'sale-badge' && greyd.tools.isPluginActive( "woocommerce/woocommerce.php" ):
						if (!_.has(params, 'showPercent')) params.showPercent = false;
						controls.push(el( wp.components.ToggleControl, {
							label: __("Show discount", 'greyd_hub'),
							checked: params.showPercent,
							onChange: (value) => updateFormat( dtag, { showPercent: value } )
						} ));
						break;
					case dtag == 'excerpt':
						if (!_.has(params, 'dontStripTags')) params.dontStripTags = false;
						controls.push(el( wp.components.ToggleControl, {
							label: __("Keep formatting and ignore character limit", 'greyd_hub'),
							checked: params.dontStripTags,
							onChange: function(value) { 
								var newval = { 
									dontStripTags: value
								};
								updateFormat(dtag, newval);
							},
						} ));
						break;
					case dtag == 'col-sum':
					case dtag == 'col-average':

						// decimal places count
						if ( !_.has(params, 'count') ) {
							params.count = -1;
						}
						controls.push(
							el( wp.components.__experimentalNumberControl, {
								style: { marginBottom: '10px' },
								label: __("Decimal places", 'greyd_hub'),
								value: parseInt(params.count),
								step: 1, min: -1,
								onChange: function(value) { 
									// console.log( 'change decimal count' );
									var newval = {
										...params,
										count: value
									};
									updateFormat(dtag, newval);
								},
							} )
						);

						// decimal format
						if ( !_.has(params, 'format') ) {
							params.format = '';
						}
						controls.push(
							el( greyd.components.OptionsControl, {
								label: __("Format", 'greyd_hub'),
								value: params.format,
								options: [
									{ value: '',            label: __("Automatically", 'greyd_hub') },
									{ value: 'none-comma',  label: __('Only comma: 4200,00', 'greyd_hub') },
									{ value: 'none-dot',    label: __('Only dot: 4200.00', 'greyd_hub') },
									{ value: 'dot-comma',   label: __('Dot and comma: 4.200,00', 'greyd_hub') },
									{ value: 'comma-dot',   label: __('Comma and dot: 4,200.00', 'greyd_hub') },
									{ value: 'space-comma', label: __('Space and comma: 4 200,00', 'greyd_hub') },
									{ value: 'space-dot',   label: __('Space and dot: 4 200.00', 'greyd_hub') },
								],
								onChange: function(value) {
									// console.log( 'change thousands separators' );
									var newval = {
										...params,
										format: value
									};
									updateFormat(dtag, newval);
								},
							} )
						);

						break;
				}

				if (controls.length > 0) {
					controls.unshift(el( "hr" ));
					// controls.unshift(el( "h4", { }, __('Optionen', 'greyd_hub') ));
				}

				return controls;
			}

			const PopoverContent = () => {
				if ( _.isEmpty(dtag) ) {
					const flexOptions = [];
					options.forEach( option => {
						_.has(option, 'options') && option.options.forEach( opt => flexOptions.push( opt ) )
					} );
					return flexOptions.map( option => {
						return el( wp.components.Button, {
							className: 'components-autocomplete__result',
							onClick: () => {
								updateFormat( option.value, '' )
							}
						}, [
							el( wp.components.Flex, {
								gap: 3,
								align: 'center',
								justify: 'space-between'
							}, [
								el( wp.components.FlexItem, {}, [
									_.has( option, 'icon' ) ? el( wp.components.Icon, { icon: option.icon, className: 'icon' } ) : null,
								] ),
								el( wp.components.FlexBlock, {}, option.label )
							] )
						] )
					} )
				}

				return [
					el( wp.components.BaseControl, {}, [
						el( "h4", {}, __('Dynamic Tag', 'greyd_hub') ),
						el( greyd.components.OptionsControl, {
							className: 'tagselect',
							value: dtag,
							options: options,
							onChange: function(value) { 
								// console.log( 'change dynamic tag' );
								// console.log(props);
								updateFormat(value, '');
							},
						} ),
						tagParams()
					] ),
					el( wp.components.Tip, {}, [
						el( 'small', {},  __("Type # when entering text to select a dynamic tag.", 'greyd_hub') )
					] ),
				]
			}

			return [
				el( wp.blockEditor.RichTextToolbarButton, {
					name: 'bold',
					icon: el( wp.components.Icon, { icon: 'database'} ),
					title: __('Dynamic Tag', 'greyd_hub'),
					onClick: function() {
						if (props.isActive) {
							// console.log( 'remove dynamic tag' );
							props.onChange(
								wp.richText.removeFormat( props.value, 'greyd/dtag' )
							);
						}
						else {
							// console.log( 'set dynamic tag' );
							props.onChange(
								wp.richText.applyFormat( props.value, {
									type: 'greyd/dtag',
									attributes: {
										tag: dtag ? dtag : '',
										params: JSON.stringify(params ? params : {})
									}
								} )
							);
						}
					},
					isActive: props.isActive,
				} ),
				props.isActive && el( wp.components.Popover, {
					className: "components-greyd-format__content",
					...(
						/**
						 * wp.richText.useAnchor is not supported in Gutenberg
						 * Version prior to 14.0.
						 */
						_.has( wp.richText, 'useAnchor' )
						? {
							anchor: wp.richText.useAnchor( {
								editableContentElement: props.contentRef.current,
								value: props.value,
								settings: {
									tagName: 'span',
									className: 'is-tag',
									name: 'greyd/dtag'
								}
							} )
						}
						: {
							anchorRef: _.isEmpty(props.value.text) ? null : wp.richText.useAnchorRef( {
								ref: props.contentRef,
								value: props.value,
								settings: {
									tagName: 'span',
									className: 'is-tag',
									name: 'greyd/dtag'
								}
							} )
						}
					)
					
				}, PopoverContent() )
			]
		},
	} );

} )( window.wp );