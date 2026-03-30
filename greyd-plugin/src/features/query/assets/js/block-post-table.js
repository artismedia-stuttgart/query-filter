/**
 * Post Table Block.
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register Post Table block.
	 */
	wp.blocks.registerBlockType( 'greyd/post-table', {
		apiVersion: 2,
		title: __("Post Table", 'greyd_hub'),
		description: __("Displays selected posts of a post type as a table.", 'greyd_hub'),
		icon: greyd.tools.getBlockIcon('table'),
		category: 'greyd-blocks',
		keywords: [ 'post', 'table', 'query', 'loop' ],
		inserter: false,
		parent: [ 'core/query' ],
		supports: {
			anchor: true,
		},
		styles: [
			{ name: 'stripes', label: __( 'Stripes', 'greyd_hub' ), isDefault: false },
		],
		attributes: {
			anchor: { type: 'string' },
			// dynamic_parent: { type: 'string' }, // dynamic template backend helper
			// dynamic_value: { type: 'string' }, // dynamic template frontend helper
			// css_animation: { type: 'string' },
			// inline_css_id: { type: 'string' },
			inline_css: { type: 'string' },
			columns: { type: 'integer', default: 1 },
			showHead: { type: 'boolean', default: true },
			showFoot: { type: 'boolean', default: true },
			cells: {type: 'object', default: {
				thead: [],
				tbody: [],
				tfoot: [],
				align: [],
				sortable: [],
			} },
			greydClass: { type: 'string', default: '' },
			columnStyles: { type: 'object', default: {} },
			tableStyles: { type: 'object', default: {
				thead: {},
				tbody: {},
				tfoot: {},
			} },
		},

		edit: function( props ) {

			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			const {
				className,
				setAttributes,
				attributes: atts
			} = props;

			// get parent query
			var parent = greyd.tools.isChildOf(props.clientId, 'core/query');
			// console.log(parent);

			// get all posts first
			var { posts } = wp.data.useSelect(select => {
				// get current parent state
				parent = greyd.tools.isChildOf(props.clientId, 'core/query');
				if (!_.has(parent, 'attributes.query')) return false;
				// console.log(parent.attributes.query);
				// console.log(props);
				
				// original query vars
				var parentQuery = parent.attributes.query;
				var postType = (parentQuery.postType) ? parentQuery.postType : 'post';
				var query = {
					offset: parentQuery.offset,
					order: parentQuery.order,
					orderby: parentQuery.orderBy,
				};
				if (parentQuery.taxQuery?.length) query.taxQuery = parentQuery.taxQuery; 
				if (parentQuery.perPage) query.per_page = -1; // get all posts
				if (parentQuery.author) query.author = parentQuery.author; 
				if (parentQuery.search) query.search = parentQuery.search; 
				if (parentQuery.exclude?.length) query.exclude = parentQuery.exclude; 
				if (parentQuery.parents?.length) query.parent = parentQuery.parents; 
				if (parentQuery.sticky) query.sticky = parentQuery.sticky === 'only';
				// console.log("fetch posts");
				
				return {
					posts: select("core").getEntityRecords('postType', postType, query)
				}
			}, [ props, parent ]);
			// then paginate (and filter)
			var { block_posts } = wp.element.useMemo(() => {
				var parentQuery = parent?.attributes?.query;
				var perpage = (parentQuery?.perPage) ? parseInt(parentQuery?.perPage) : (posts ? posts.length : 0); 
				return {
					block_posts: posts?.map( ( post ) => ( {
						postType: post.type,
						postId: post.id,
					} ) ).slice(0, perpage)
				};
			}, [ posts, parent ]);
			// console.log(block_posts);

			// state
			const [ activeCell, setActiveCell ] = wp.element.useState(false);
			if ( !props.isSelected && activeCell ) {
				greyd.tmp = _.omit(greyd.tmp, 'post-table');
				setActiveCell(false);
			}
			// console.log(activeCell);

			// column handling
			const selectColumn = function(index) {
				// delay for one frame
				setTimeout(() => {
					if (index === false) {
						greyd.tmp = _.omit(greyd.tmp, 'post-table');
						setActiveCell(false);
					}
					else {
						var newCell = (activeCell) ? activeCell : {
							sectionName: "tbody",
							rowIndex: 0,
							columnIndex: 0,
						};
						newCell.columnIndex = index;
						// console.log("set new state");
						// console.log(newCell);
						greyd.tmp = { ...greyd.tmp, 'post-table': "tbody" };
						setActiveCell( { ...newCell } );
					}
				}, 0);
			};

			const addColumn = function(index) {
				// new cells
				var cells = { };
				Object.keys(atts.cells).forEach(function(value, i) {
					var section = [ ...atts.cells[value] ];
					var newValue = [ "thead", "tbody", "tfoot" ].indexOf(value) > -1  ? "" : null;
					section.splice(index, 0, newValue);
					cells[value] = section;
				});
				// new responsive columns
				var columnStyles = { ...atts.columnStyles };
				for (var i=atts.columns; i>=index; i--) {
					columnStyles = moveValue(columnStyles, i, i+1, "width");
					columnStyles = moveValue(columnStyles, i, i+1, "hide");
					if (_.has(columnStyles, "responsive")) {
						[ 'sm', 'md', 'lg' ].forEach(function(breakpoint) {
							if (_.has(columnStyles.responsive, breakpoint)) {
								columnStyles.responsive[breakpoint] = moveValue(columnStyles.responsive[breakpoint], i, i+1, "width");
								columnStyles.responsive[breakpoint] = moveValue(columnStyles.responsive[breakpoint], i, i+1, "hide");
							}
						} );
					}
				}
				// console.log(cells);
				// console.log(columnStyles);
				return { cells: cells, columnStyles: columnStyles };
			};

			const deleteColumn = function(index) {
				// new cells
				var cells = { };
				Object.keys(atts.cells).forEach(function(value, i) {
					var section = [ ...atts.cells[value] ];
					section.splice(index, 1);
					cells[value] = section;
				});
				// new responsive columns
				var columnStyles = { ...atts.columnStyles };
				for (var i=index; i<atts.columns; i++) {
					if (i == index) delete columnStyles["width_"+index];
					if (i == index) delete columnStyles["hide_"+index];
					columnStyles = moveValue(columnStyles, i, i-1, "width");
					columnStyles = moveValue(columnStyles, i, i-1, "hide");
					if (_.has(columnStyles, "responsive")) {
						[ 'sm', 'md', 'lg' ].forEach(function(breakpoint) {
							if (_.has(columnStyles.responsive, breakpoint)) {
								if (i == index) delete columnStyles.responsive[breakpoint]["width_"+i];
								if (i == index) delete columnStyles.responsive[breakpoint]["hide_"+i];
								columnStyles.responsive[breakpoint] = moveValue(columnStyles.responsive[breakpoint], i, i-1, "width");
								columnStyles.responsive[breakpoint] = moveValue(columnStyles.responsive[breakpoint], i, i-1, "hide");
							}
						} );
					}
				}
				// console.log(cells);
				// console.log(columnStyles);
				return { cells: cells, columnStyles: columnStyles };
			};

			const switchColumns = function(index1, index2) {
				// new cells
				var cells = { };
				Object.keys(atts.cells).forEach(function(value, i) {
					var section = [ ...atts.cells[value] ];
					var columnValue = section[index1];
					section.splice(index1, 1);
					section.splice(index2, 0, columnValue);
					cells[value] = section;
				});
				// new responsive columns
				var columnStyles = { ...atts.columnStyles };
				columnStyles = switchValues(columnStyles, index1, index2, "width");
				columnStyles = switchValues(columnStyles, index1, index2, "hide");
				if (_.has(columnStyles, "responsive")) {
					[ 'sm', 'md', 'lg' ].forEach(function(breakpoint) {
						if (_.has(columnStyles.responsive, breakpoint)) {
							columnStyles.responsive[breakpoint] = switchValues(columnStyles.responsive[breakpoint], index1, index2, "width");
							columnStyles.responsive[breakpoint] = switchValues(columnStyles.responsive[breakpoint], index1, index2, "hide");
						}
					} );
				}
				// console.log(cells);
				// console.log(columnStyles);
				return { cells: cells, columnStyles: columnStyles };
			};

			const moveValue = function(columnStyles, index1, index2, attribute) {
				if (_.has(columnStyles, attribute+"_"+index1)) {
					columnStyles[attribute+"_"+index2] = columnStyles[attribute+"_"+index1];
					delete columnStyles[attribute+"_"+index1];
				}
				return columnStyles;
			};

			const switchValues = function(columnStyles, index1, index2, attribute) {
				var value1 = null;
				var value2 = null;
				if (_.has(columnStyles, attribute+"_"+index1)) {
					value1 = columnStyles[attribute+"_"+index1];
					delete columnStyles[attribute+"_"+index1];
				}
				if (_.has(columnStyles, attribute+"_"+index2)) {
					value2 = columnStyles[attribute+"_"+index2];
					delete columnStyles[attribute+"_"+index2];
				}
				if (value1 !== null) columnStyles[attribute+"_"+index2] = value1;
				if (value2 !== null) columnStyles[attribute+"_"+index1] = value2;
				return columnStyles;
			};

			// preview rendering
			const makeHead = function() {
				if (!atts.showHead) return;
				return makeSection('thead', 'th', 1);
			};

			const makeBody = function() {
				var count = 1;
				if (parent && block_posts && typeof block_posts !== 'undefined') {
					// console.log(block_posts);
					// console.log('re-render');
					count = block_posts.length;
				}
				return makeSection('tbody', 'td', count);
			};

			const makeFoot = function() {
				if (!atts.showFoot) return;
				return makeSection('tfoot', 'td', 1);
			};

			const makeSection = function(sectionName, tagName, rowCount) {

				var rows = [];
				for (var i=0; i<rowCount; i++) {
					var cols = [];
					for (var j=0; j<atts.columns; j++) {
						cols.push( makeCell(sectionName, tagName, i, j) );
					}
					rows.push( el( 'tr', {}, cols ) );
				}

				return el( sectionName, {}, [
					rows
				] );

			};

			const makeCell = function( sectionName, tagName, rowIndex, columnIndex ) {

				const align = _.has(atts.cells.align, columnIndex) ? 'has-text-align-'+atts.cells.align[columnIndex] : '';
				const sortable = tagName == "th" && _.has(atts.cells.sortable, columnIndex) && atts.cells.sortable[columnIndex] == true ? 'sortable' : '';
				// const hide = _.has(atts.cells.mobile, columnIndex) && atts.cells.mobile[columnIndex] == false ? 'hide-on-mobile' : '';

				let value = _.has(atts.cells[sectionName], columnIndex) ? decodeURIComponent(atts.cells[sectionName][columnIndex]) : "";
				if (tagName == "th" && sortable != "") {
					value += '<span class="icon" contenteditable="false"><span class="iconup"></span><span class="icondown"></span></span>';
				}

				let placeholder = sectionName;
				if (placeholder == "thead") {
					placeholder = __("Header", "greyd_hub");
				}
				else if (placeholder == "tbody") {
					placeholder = __("Body", "greyd_hub");
				}
				else if (placeholder == "tfoot") {
					placeholder = __("Footer", "greyd_hub");
				}

				const isActive = activeCell && activeCell.sectionName == sectionName && activeCell.rowIndex == rowIndex && activeCell.columnIndex == columnIndex;

				const onFocus = () => {
					if (activeCell && activeCell.sectionName == sectionName && activeCell.rowIndex == rowIndex && activeCell.columnIndex == columnIndex) return;
					greyd.tmp = { ...greyd.tmp, 'post-table': sectionName };
					setActiveCell( {
						sectionName: sectionName,
						rowIndex: rowIndex,
						columnIndex: columnIndex
					} );
				}

				const onChange = ( val ) => {
					// console.log("change: "+val);
					var contents = [ ...atts.cells[sectionName] ];
					contents[columnIndex] = encodeURIComponent( val );
					setAttributes( { cells: { ...atts.cells, [sectionName]: contents } } );
				}

				return el( wp.blockEditor.RichText, {
					// inlineToolbar: true,
					style: isActive ? { outline: "1px solid var(--wp-admin-theme-color)" } : {},
					tagName: tagName,
					className: align+' '+sortable,
					value: value,
					placeholder: placeholder+" "+(columnIndex+1),
					onChange: onChange,
					unstableOnFocus: onFocus,
					onFocus: onFocus
				} );
			};

			// styles
			// collect all styles from tableStyles and columnStyles
			const getGreydStyles = function() {
				
				// alignment and colors
				var styles = {
					" table": atts.tableStyles.tbody,
					" table thead": atts.tableStyles.thead,
					" table tfoot": atts.tableStyles.tfoot
				};

				// override/unset customizer setup if table head color or background is set
				var override = {};
				if (_.has(atts.tableStyles.thead, 'color')) override.color = 'inherit';
				if (_.has(atts.tableStyles.thead, 'background')) override.background = 'transparent';
				if (!_.isEmpty(override)) styles[" table thead tr th"] = override;

				// columns
				var hiddenStyle = { maxWidth: "8px", width: "8px", overflow: "hidden", wordWrap: "normal", opacity: "0.5" };
				for (var j=0; j<atts.columns; j++) {

					// split column styles
					var column = {};
					if (_.has(atts.columnStyles, "width_"+j) && atts.columnStyles["width_"+j] != "") {
						column.width = atts.columnStyles["width_"+j];
					}
					if (_.has(atts.columnStyles, "hide_"+j) && atts.columnStyles["hide_"+j] === true) {
						column = { ...column, ...hiddenStyle };
					}
					// responsive
					if (_.has(atts.columnStyles, "responsive") && !_.isEmpty(atts.columnStyles.responsive)) {
						var responsive = {};
						[ 'sm', 'md', 'lg' ].forEach(function(breakpoint) {
							if (_.has(atts.columnStyles.responsive, breakpoint)) {
								if (_.has(atts.columnStyles.responsive[breakpoint], "width_"+j) && atts.columnStyles.responsive[breakpoint]["width_"+j] != "") {
									if (!_.has(responsive, breakpoint)) responsive[breakpoint] = {};
									responsive[breakpoint].width = atts.columnStyles.responsive[breakpoint]["width_"+j];
								}
								if (_.has(atts.columnStyles.responsive[breakpoint], "hide_"+j) && atts.columnStyles.responsive[breakpoint]["hide_"+j] === true) {
									if (!_.has(responsive, breakpoint)) responsive[breakpoint] = {};
									responsive[breakpoint] = { ...responsive[breakpoint], ...hiddenStyle };
								}
							}
						} );
						if (!_.isEmpty(responsive)) column.responsive = responsive;
					}
					// get column style
					if (!_.isEmpty(column)) styles[" table th:nth-child("+(j+1)+"), table td:nth-child("+(j+1)+")"] = column;

				}

				return styles;

			};

			// helper component to make toggleControl responsive
			const ToggleControlResponsive = class extends wp.element.Component {
				constructor() {
					super();
				}
				render() {
					return el( wp.components.ToggleControl, {
						label: this.props.label,
						checked: this.props.value,
						onChange: value => this.props.onChange( value ),
					} );
				}
			};

			// call function to make sure Block is updated when inside a template
			const blockProps = wp.blockEditor.useBlockProps({
				id: atts.anchor,
				className: [ className, atts.greydClass, ( greyd.data?.is_greyd_classic ? '' : 'wp-block-table' ) ].join(' ')
			});

			return [
				
				//  toolbar
				el( wp.blockEditor.BlockControls, { group: 'block' }, [

					// edit table (inset/delete column)
					el( wp.components.ToolbarDropdownMenu, {
						icon: greyd.tools.getCoreIcon('table'),
						label: __( "Edit table", 'greyd_hub' ),
						controls: [
							{
								icon: greyd.tools.getCoreIcon('tableColumnBefore'),
								title: __( "Insert column before", 'greyd_hub' ),
								isDisabled: !activeCell,
								onClick: function() {
									if (!activeCell) return;
									// console.log("tableColumnBefore");
									var newColumns = atts.columns + 1;
									var { cells, columnStyles } = addColumn(activeCell.columnIndex);
									setAttributes( { columns: newColumns, cells: cells, columnStyles: columnStyles } );
									selectColumn(activeCell.columnIndex);
								},
							},
							{
								icon: greyd.tools.getCoreIcon('tableColumnAfter'),
								title: __( "Insert column after", 'greyd_hub' ),
								isDisabled: !activeCell,
								onClick: function() {
									if (!activeCell) return;
									// console.log("tableColumnAfter");
									var newColumns = atts.columns + 1;
									var { cells, columnStyles } = addColumn(activeCell.columnIndex + 1);
									setAttributes( { columns: newColumns, cells: cells, columnStyles: columnStyles } );
									selectColumn(activeCell.columnIndex + 1);
								},
							},
							{
								icon: greyd.tools.getCoreIcon('tableColumnDelete'),
								title: __( "Delete column", 'greyd_hub' ),
								isDisabled: !activeCell,
								onClick: function() {
									if (!activeCell) return;
									// console.log("tableColumnDelete");
									var newColumns = atts.columns - 1;
									var { cells, columnStyles } = deleteColumn(activeCell.columnIndex);
									setAttributes( { columns: newColumns, cells: cells, columnStyles: columnStyles } );
									selectColumn(false);
								},
							}
						]
					} ),

					// move column
					el( wp.components.ToolbarButton, {
						icon: greyd.tools.getCoreIcon('tableColumnMoveLeft'),
						style: { minWidth: '26px', maxWidth: '26px', paddingLeft: '11px', paddingRight: '0px' },
						label: __( "Move column to the left", 'greyd_hub' ),
						isDisabled: !activeCell || activeCell.columnIndex == 0,
						onClick: function() {
							if (!activeCell) return;
							// console.log("move column left");
							var { cells, columnStyles } = switchColumns(activeCell.columnIndex, activeCell.columnIndex - 1);
							setAttributes( { cells: cells, columnStyles: columnStyles } );
							selectColumn(activeCell.columnIndex - 1);
						},
					} ),
					el( wp.components.ToolbarButton, {
						icon: greyd.tools.getCoreIcon('tableColumnMoveRight'),
						style: { minWidth: '26px', maxWidth: '26px', paddingRight: '11px', paddingLeft: '0px' },
						label: __( "Move column to the right", 'greyd_hub' ),
						isDisabled: !activeCell || activeCell.columnIndex == atts.columns-1,
						onClick: function() {
							if (!activeCell) return;
							// console.log("move column right");
							var { cells, columnStyles } = switchColumns(activeCell.columnIndex, activeCell.columnIndex + 1);
							setAttributes( { cells: cells, columnStyles: columnStyles } );
							selectColumn(activeCell.columnIndex + 1);
						},
					} ),
					
					// align column
					el( wp.blockEditor.AlignmentControl, {
						label: __( "Column alignment", 'greyd_hub' ),
						alignmentControls: [
							{
								icon: greyd.tools.getCoreIcon('textAlignLeft'),
								title: __( "Align column left", 'greyd_hub' ),
								isDisabled: !activeCell,
								align: 'left',
							},
							{
								icon: greyd.tools.getCoreIcon('textAlignCenter'),
								title: __( "Align column center", 'greyd_hub' ),
								isDisabled: !activeCell,
								align: 'center',
							},
							{
								icon: greyd.tools.getCoreIcon('textAlignRight'),
								title: __( "Align column right", 'greyd_hub' ),
								isDisabled: !activeCell,
								align: 'right',
							},
						],
						value: _.has(atts.cells.align, activeCell.columnIndex) ? atts.cells.align[activeCell.columnIndex] : false,
						onChange: function(value) { 
							// console.log("align: "+value);
							var alignments = [];
							if (_.has(atts.cells, 'align')) alignments = [ ...atts.cells.align ];
							alignments[activeCell.columnIndex] = value;
							setAttributes( { cells: { ...atts.cells, align: alignments } } );
						},
					} ),

				] ),
				
				//  sidebar
				el( wp.blockEditor.InspectorControls, {}, [

					// table settings
					el( wp.components.PanelBody, {
						title: __("Table", 'greyd_hub'),
						initialOpen: true
					}, [
						
						// align table
						el( greyd.components.ButtonGroupControl, {
							label: __( "Alignment", 'greyd_hub' ),
							value: (_.has(atts, 'tableStyles.tbody.textAlign')) ? atts.tableStyles.tbody.textAlign : '',
							options: [
								{ icon: greyd.tools.getCoreIcon('textAlignLeft'), value: 'left' },
								{ icon: greyd.tools.getCoreIcon('textAlignCenter'), value: 'center' },
								{ icon: greyd.tools.getCoreIcon('textAlignRight'), value: 'right' },
							],
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tbody') ? { ...atts.tableStyles.tbody } : { };
								styles.textAlign = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tbody: styles } } ); 
							},
						} ),
						// colors table
						el( greyd.components.ColorGradientPopupControl, {
							mode: 'color',
							label: __("Text color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.tbody.color')) ? atts.tableStyles.tbody.color : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tbody') ? { ...atts.tableStyles.tbody } : { };
								styles.color = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tbody: styles } } ); 
							},
						} ),
						el( greyd.components.ColorGradientPopupControl, {
							className: 'single',
							label: __("Background color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.tbody.background')) ? atts.tableStyles.tbody.background : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tbody') ? { ...atts.tableStyles.tbody } : { };
								styles.background = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tbody: styles } } ); 
							},
						} ),

						// adjust columns
						el( wp.components.__experimentalNumberControl, {
							label: __( "Number of columns", 'greyd_hub' ),
							value: atts.columns,
							isDragEnabled: true,
							isShiftStepEnabled: false,
							min: 1, step: 1,
							onChange: function(value) { 
								// console.log("tableColumns: "+value);
								var newColumns = parseInt(value);
								var { cells, columnStyles } = newColumns > atts.columns ? addColumn(newColumns - 1) : deleteColumn(newColumns);
								setAttributes( { columns: newColumns, cells: cells, columnStyles: columnStyles } );
								selectColumn(newColumns - 1);
							},
						} ),
						
						// header toggle and settings button
						el( wp.components.ToggleControl, {
							label: __( "Show table header", 'greyd_hub' ),
							checked: atts.showHead,
							onChange: value => {
								setAttributes( { showHead: value } );
							},
						} ),
						// footer toggle
						el( wp.components.ToggleControl, {
							label: __( "Show table footer", 'greyd_hub' ),
							checked: atts.showFoot,
							onChange: value => {
								setAttributes( { showFoot: value } );
							},
						} ),

					] ),

					// header settings
					atts.showHead && el( wp.components.PanelBody, {
						title: __('Header', 'greyd_hub'),
						initialOpen: false
					}, [
						// align
						el( greyd.components.ButtonGroupControl, {
							label: __( "Alignment", 'greyd_hub' ),
							value: (_.has(atts, 'tableStyles.thead.textAlign')) ? atts.tableStyles.thead.textAlign : '',
							options: [
								{ icon: greyd.tools.getCoreIcon('textAlignLeft'), value: 'left' },
								{ icon: greyd.tools.getCoreIcon('textAlignCenter'), value: 'center' },
								{ icon: greyd.tools.getCoreIcon('textAlignRight'), value: 'right' },
							],
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.thead') ? { ...atts.tableStyles.thead } : { };
								styles.textAlign = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, thead: styles } } ); 
							},
						} ),
						// colors
						el( greyd.components.ColorGradientPopupControl, {
							mode: 'color',
							label: __("Text color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.thead.color')) ? atts.tableStyles.thead.color : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.thead') ? { ...atts.tableStyles.thead } : { };
								styles.color = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, thead: styles } } ); 
							},
						} ),
						el( greyd.components.ColorGradientPopupControl, {
							label: __("Background color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.thead.background')) ? atts.tableStyles.thead.background : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.thead') ? { ...atts.tableStyles.thead } : { };
								styles.background = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, thead: styles } } ); 
							},
						} )
					] ),

					// footer settings
					atts.showFoot && el( wp.components.PanelBody, {
						title: __('Footer', 'greyd_hub'),
						initialOpen: false
					}, [
						// align
						el( greyd.components.ButtonGroupControl, {
							label: __( "Alignment", 'greyd_hub' ),
							value: (_.has(atts, 'tableStyles.tfoot.textAlign')) ? atts.tableStyles.tfoot.textAlign : '',
							options: [
								{ icon: greyd.tools.getCoreIcon('textAlignLeft'), value: 'left' },
								{ icon: greyd.tools.getCoreIcon('textAlignCenter'), value: 'center' },
								{ icon: greyd.tools.getCoreIcon('textAlignRight'), value: 'right' },
							],
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tfoot') ? { ...atts.tableStyles.tfoot } : { };
								styles.textAlign = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tfoot: styles } } ); 
							},
						} ),
						// colors
						el( greyd.components.ColorGradientPopupControl, {
							mode: 'color',
							label: __("Text color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.tfoot.color')) ? atts.tableStyles.tfoot.color : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tfoot') ? { ...atts.tableStyles.tfoot } : { };
								styles.color = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tfoot: styles } } ); 
							},
						} ),
						el( greyd.components.ColorGradientPopupControl, {
							label: __("Background color", "greyd_hub"),
							value: (_.has(atts, 'tableStyles.tfoot.background')) ? atts.tableStyles.tfoot.background : '',
							onChange: function(value) { 
								var styles = _.has(atts, 'tableStyles.tfoot') ? { ...atts.tableStyles.tfoot } : { };
								styles.background = value;
								setAttributes( { tableStyles: { ...atts.tableStyles, tfoot: styles } } ); 
							},
						} )
					] ),

					// column settings
					el( wp.components.PanelBody, {
						title: __("Column", 'greyd_hub')+(activeCell.columnIndex > -1 ? ' '+(activeCell.columnIndex+1) : ''),
						initialOpen: true
					}, [
						(activeCell.columnIndex > -1) ? [
							// align
							el( greyd.components.ButtonGroupControl, {
								label: __( "Alignment", 'greyd_hub' ),
								value: _.has(atts.cells.align, activeCell.columnIndex) ? atts.cells.align[activeCell.columnIndex] : '',
								options: [
									{ icon: greyd.tools.getCoreIcon('textAlignLeft'), value: 'left' },
									{ icon: greyd.tools.getCoreIcon('textAlignCenter'), value: 'center' },
									{ icon: greyd.tools.getCoreIcon('textAlignRight'), value: 'right' },
								],
								onChange: function(value) { 
									// console.log(activeCell);
									// console.log(value);
									var alignments = [];
									if (_.has(atts.cells, 'align')) alignments = [ ...atts.cells.align ];
									alignments[activeCell.columnIndex] = value;
									setAttributes( { cells: { ...atts.cells, align: alignments } } );
								},
							} ),
							// sortable
							el( wp.components.ToggleControl, {
								label: __( "Column sortable", 'greyd_hub' ),
								checked: _.has(atts.cells.sortable, activeCell.columnIndex) && atts.cells.sortable[activeCell.columnIndex] == true ? true : false,
								onChange: function(value) { 
									// console.log(activeCell);
									// console.log(value);
									var sortable = [];
									if (_.has(atts.cells, 'sortable')) sortable = [ ...atts.cells.sortable ];
									sortable[activeCell.columnIndex] = value;
									setAttributes( { cells: { ...atts.cells, sortable: sortable } } );
								},
							} ),
							// responsive
							el( 'div', { style: { marginLeft: "-16px", marginRight: "-16px" } },
								el( greyd.components.StylingControlPanel, {
									title: __('Responsive', 'greyd_hub'),
									initialOpen: true,
									supportsResponsive: true,
									parentAttr: "columnStyles",
									blockProps: props,
									controls: [ 
										{
											// width
											label: __("Column width", 'greyd_hub'),
											attribute: "width_"+activeCell.columnIndex,
											control: greyd.components.RangeUnitControl,
											units: ["px", "%", "em", "rem", "vw", "vh"],
											max: 1200,
										},
										{
											// hide
											label: __("Hide column", 'greyd_hub'),
											attribute: "hide_"+activeCell.columnIndex,
											control: ToggleControlResponsive,
										}
									]
								} ),
							),
						] : el( 'i', {}, __( "No column selected.", 'greyd_hub' ) )
					] )

				] ),

				// preview
				el( 'div', { ...blockProps }, [
					el( 'table', {}, [
						makeHead(),
						makeBody(),
						makeFoot(),
					] )
				] ),

				// styles
				el( greyd.components.RenderPreviewStyles, {
					selector: atts.greydClass,
					styles: getGreydStyles()
				} )

			];
		},

	} );

} )( window.wp );