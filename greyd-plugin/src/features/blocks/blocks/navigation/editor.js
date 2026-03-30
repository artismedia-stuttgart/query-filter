/**
 * Navigation Extension Editor Script
 * - core/navigation
 * - core/navigation-submenu
 * - core/navigation-link
 * 
 * @since 1.13.0
 * 
 * This file is loaded in block editor pages and modifies the editor experience.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	/**
	 * Register custom attributes to core navigation blocks.
	 *
	 * @hook blocks.registerBlockType
	 */
	var registerBlockTypeHook = function(settings, name) {

		if (_.has(settings, 'apiVersion') && settings.apiVersion > 1) {
			// console.log(name);

			if (name == 'core/navigation' ||
				name == 'core/navigation-submenu' ||
				name == 'core/navigation-link') {
				// console.log(settings);
				settings.attributes.custom = { type: 'bool', default: 0 };
				settings.attributes.greydClass = { type: 'string' };
				settings.attributes.customStyles = { type: 'object' };
			}
			if (name == 'core/navigation') {
				// console.log(settings);
				settings.attributes.submenu = { type: 'string', default: '' };
				// settings.attributes.anchoractive = { type: 'bool', default: 0 };
				settings.attributes.anchoractive = { type: 'object', default: {
					enable: 0,
					start: '0%',
					end: '100%',
					multiple: '',
					none: ''
				} };
			}

		}
		return settings;

	}

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/navigation',
		registerBlockTypeHook
	);


	/**
	 * Add custom edit controls to core navigation blocks.
	 *
	 * @hook editor.BlockEdit
	 */
	var editBlockHook = wp.compose.createHigherOrderComponent( function( BlockEdit ) {

		return function( props ) {

			/**
			 * Extend core navigation
			 */
			if (props.name == 'core/navigation' ||
				props.name == 'core/navigation-submenu' ||
				props.name == 'core/navigation-link') {

				var extend = true;
				// check site-editor screen
				var urlParams = new URLSearchParams(window.location.search);
				if (
					props.name == 'core/navigation' &&
					urlParams.has('postType') &&
					urlParams.get('postType') == 'wp_navigation'
				) extend = false;

				// don't extend on wp_navigation edit screen
				// core/navigation wrapper block is not saved
				if ( extend ) {

					const makeInspectorControlsStyles = () => {

						// add custom styles component
						return el( wp.blockEditor.InspectorControls, { group: 'styles' }, [
							
							// custom button
							el( greyd.components.AdvancedPanelBody, {
								title: __( "Individual design", 'greyd_hub' ),
								initialOpen: true,
								holdsChange: props.attributes.custom ? true : false
							}, [
								el( wp.components.ToggleControl, {
									label: __( "Overwrite design individually", 'greyd_hub' ),
									checked: props.attributes.custom,
									onChange: function(value) {
										props.setAttributes( { custom: !!value } );
									},
								} ),
							] ),
							el( greyd.components.CustomButtonStyles, {
								enabled: props.attributes.custom ? true : false,
								blockProps: props,
								parentAttr: 'customStyles',
								supportsActive: true
							} )

						] );

					};

					const makeInspectorControls = () => {

						if ( props.name != 'core/navigation' ) return null;

						// disable openSubmenusOnClick and showSubmenuIcon when behaviour is 'show' or 'hide'
						if ( props.attributes.submenu && props.attributes.submenu != '' && props.attributes.submenu != 'toggle' ) {
							if ( props.attributes.openSubmenusOnClick ) props.setAttributes( { openSubmenusOnClick: false } );
							if ( props.attributes.showSubmenuIcon ) props.setAttributes( { showSubmenuIcon: false } );
						}

						// anchoractive
						const options = [
							{
								label: __( "0% - element at top edge", 'greyd_hub' ),
								value: '0%'
							},
							{ label: __( '10%', 'greyd_hub' ), value: '10%' },
							{ label: __( '20%', 'greyd_hub' ), value: '20%' },
							{ label: __( '30%', 'greyd_hub' ), value: '30%' },
							{ label: __( '40%', 'greyd_hub' ), value: '40%' },
							{
								label: __( "50% - element in the center of the screen", 'greyd_hub' ),
								value: '50%'
							},
							{ label: __( '60%', 'greyd_hub' ), value: '60%' },
							{ label: __( '70%', 'greyd_hub' ), value: '70%' },
							{ label: __( '80%', 'greyd_hub' ), value: '80%' },
							{ label: __( '90%', 'greyd_hub' ), value: '90%' },
							{
								label: __( "100% - element at bottom edge", 'greyd_hub' ),
								value: '100%'
							}
						];
						const setAnchoractive = ( key, value ) => {
							var anchoractive = { ...props.attributes.anchoractive };
							anchoractive[key] = value;
							props.setAttributes( { anchoractive: anchoractive } );
						};

						return el( wp.blockEditor.InspectorControls, { }, [
							
							// add submenus behaviour
							el( greyd.components.AdvancedPanelBody, {
								title: __('Submenus', 'greyd_hub'),
								initialOpen: true,
								holdsChange: props.attributes.submenu != ""
							}, [

								el( greyd.components.ButtonGroupControl, {
									label: __( 'Override behaviour', 'greyd_hub' ),
									value: props.attributes.submenu ? props.attributes.submenu : '',
									options: [
										{ value: "", label: __( 'Default', 'greyd_hub' ) },
										{ value: "toggle", label: __( 'Toggle', 'greyd_hub' ) },
										{ value: "show", label: __( 'Show', 'greyd_hub' ) },
										{ value: "hide", label: __( 'Hide', 'greyd_hub' ) },
									],
									onChange: function(value) { 
										props.setAttributes( { submenu: value } );
									},
								} ),
								el( wp.components.Notice, { 
									className: props.attributes.submenu && props.attributes.submenu != '' && props.attributes.submenu != 'toggle' ? '' : 'is-hidden',
									status: 'warning',
									isDismissible: false
								}, [
									el( 'p', {}, __( 'This option overrides the default submenu behaviours "click" and "hover".', 'greyd_hub' ) ),
									__( 'If set, those submenu behaviours and and their options are disabled.', 'greyd_hub' ),
								] )

							] ),

							// add active behaviour
							el( greyd.components.AdvancedPanelBody, {
								title: __('Anchor active style', 'greyd_hub'),
								initialOpen: true,
								holdsChange: props.attributes.anchoractive?.enable ? true : false
							}, [

								el( wp.components.ToggleControl, {
									label: __('Activate', 'greyd_hub'),
									checked: props.attributes.anchoractive?.enable ? true : false,
									onChange: (value) => setAnchoractive('enable', value),
								} ),

								props.attributes.anchoractive?.enable ? [

									// help notice
									el( wp.components.BaseControl, {}, [
										el( wp.components.Notice, { 
											status: 'warning',
											isDismissible: false
										}, [
											__( 'If a menu-item points to an anchor-element of a page, it is highlighted as "active" as long as the anchor-element is in the viewport.', 'greyd_hub' ),
											/* translation */
											// __( 'Wenn ein Menüpunkt auf einen Anker-Element einer Seite zeigt, so wird er als „active“ hervorgehoben solange sich das Anker-Element im sichtbaren Bereich befindet.', 'greyd_hub' ),
										] )
									] ),

									// viewport
									el( wp.components.BaseControl, {
										help: parseInt(props.attributes.anchoractive?.start) > parseInt(props.attributes.anchoractive?.end) ?
											__( 'Viewport start should be smaller than end!', 'greyd_hub' ) : '',
									}, [
										el( greyd.components.OptionsControl, {
											style: { marginBottom: '-8px' },
											label: __( 'Viewport start', 'greyd_hub' ),
											value: props.attributes.anchoractive?.start?? '100%',
											options: options,
											onChange: (value) => setAnchoractive('start', value),
										} ),
										el( greyd.components.OptionsControl, {
											label: __( 'Viewport end', 'greyd_hub' ),
											value: props.attributes.anchoractive?.end?? '0%',
											options: options,
											onChange: (value) => setAnchoractive('end', value),
										} )
									] ),


									// multiple/none
									el( wp.components.BaseControl, {}, [
										el( wp.components.RadioControl, {
											label: __( 'Multiple active elements', 'greyd_hub' ),
											selected: props.attributes.anchoractive?.multiple?? '',
											options: [
												{ value: "",        label: __( 'Allow multiple active elements', 'greyd_hub' ) },
												{ value: "closest", label: __( 'Only closest to viewport center', 'greyd_hub' ) },
												{ value: "latest",  label: __( 'Only latest element in viewport', 'greyd_hub' ) },
											],
											onChange: (value) => setAnchoractive('multiple', value),
										} ),
										el( wp.components.RadioControl, {
											label: __( "No active element", 'greyd_hub' ),
											selected: props.attributes.anchoractive?.none?? '',
											options: [
												{ value: "",        label: __( 'Allow no active element', 'greyd_hub' ) },
												{ value: "closest", label: __( 'Highlight closest to viewport center', 'greyd_hub' ) },
												{ value: "keep",    label: __( 'Keep element active until next', 'greyd_hub' ) },
											],
											onChange: (value) => setAnchoractive('none', value),
										} )
									] ),
									
								] : [],

							] ),

						] );
					};

					const makeStyles = () => {

						if ( !props.attributes.custom || JSON.stringify(props.attributes.customStyles) == '{}' ) return null;

						const newGreydClass = greyd.tools.getGreydClass( props );
						if ( props.attributes?.greydClass !== newGreydClass ) {
							props.setAttributes( { greydClass: newGreydClass } );
						}

						// split styles (normal and hover)
						var styles = { ...props.attributes.customStyles };
						if ( styles.hover ) delete styles.hover;
						if ( styles.active ) delete styles.active;
						// make selectors
						var selectors = { " .wp-block-navigation-item__content": styles };
						if ( props.attributes.customStyles?.hover ) {
							selectors[" .wp-block-navigation-item__content:hover"] = { ...props.attributes.customStyles.hover };
							selectors["._hover .wp-block-navigation-item__content"] = { ...props.attributes.customStyles.hover };
						}
						// icon
						if ( props.name == 'core/navigation' || props.name == 'core/navigation-submenu' ) {
							if ( props.attributes.customStyles?.color ) {
								selectors[" .wp-block-navigation__submenu-icon svg"] = { stroke: props.attributes.customStyles.color };
							}
							if ( props.attributes.customStyles?.hover?.color ) {
								selectors[" .wp-block-navigation-item__content:hover + .wp-block-navigation__submenu-icon svg"] = { stroke: props.attributes.customStyles.hover.color };
								selectors["._hover .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg"] = { stroke: props.attributes.customStyles.hover.color };
							}
						}
						// active
						if ( props.attributes.customStyles?.active ) {
							selectors["._active .wp-block-navigation-item__content"] = { ...props.attributes.customStyles.active };
							// active icon
							if ( props.attributes.customStyles?.active?.color && (props.name == 'core/navigation' || props.name == 'core/navigation-submenu') ) {
								selectors["._active .wp-block-navigation-item__content + .wp-block-navigation__submenu-icon svg"] = { stroke: props.attributes.customStyles.active.color };
							}
						}

						// style preview
						return el( greyd.components.RenderPreviewStyles, {
							selector: 'is-root-container #block-'+props.clientId,
							styles: selectors,
							important: true
						} );

					};

					return el( wp.element.Fragment, { }, [
						// style
						makeStyles(),
						// original block
						el( BlockEdit, props ),
						// inspector
						makeInspectorControls(),
						makeInspectorControlsStyles(),
					] );

				}

			}

			// return original block
			return el( BlockEdit, props );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/navigation/edit',
		editBlockHook
	);


	/**
	 * Manipulate attributes before edit.
	 * 
	 * @hook editor.BlockListBlock
	 */
	var editBlockListHook = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			// console.log(BlockListBlock);
			// console.log(props);

			/**
			 * Extend core navigation
			 */
			if ( props.name == 'core/navigation' ) {
				// get classnames
				var classNames = [];
				if ( props.attributes?.className && !_.isEmpty(props.attributes?.className) ) {
					classNames = props.attributes.className.split(' ');
				} else if ( props?.className && !_.isEmpty(props?.className) ) {
					classNames = props.className.split(' ');
				}
				// fix submenu arrows
				if ( !props.attributes.openSubmenusOnClick && classNames.indexOf('open-on-hover-click') == -1 ) {
					classNames.push( 'open-on-hover-click' );
				}
				else if ( props.attributes.openSubmenusOnClick && classNames.indexOf('open-on-hover-click') > -1 ) {
					classNames.splice(classNames.indexOf('open-on-hover-click'), 1);
				}
				if ( props.attributes.showSubmenuIcon === false && classNames.indexOf('open-on-hover-click') > -1 ) {
					classNames.splice(classNames.indexOf('open-on-hover-click'), 1);
				}
				// submenus behaviour
				if ( classNames.indexOf('submenus-show') > -1 ) classNames.splice(classNames.indexOf('submenus-show'), 1);
				if ( classNames.indexOf('submenus-toggle') > -1 ) classNames.splice(classNames.indexOf('submenus-toggle'), 1);
				if ( classNames.indexOf('submenus-hide') > -1 ) classNames.splice(classNames.indexOf('submenus-hide'), 1);
				if ( props.attributes.submenu && props.attributes.submenu != "" ) {
					if ( props.attributes.submenu == 'show' ) classNames.push( 'submenus-show' ); 
					if ( props.attributes.submenu == 'toggle' ) classNames.push( 'submenus-toggle' ); 
					if ( props.attributes.submenu == 'hide' ) classNames.push( 'submenus-hide' ); 
				}
				// set classnames
				props.attributes.className = classNames.join(' ');
			}

			return el( BlockListBlock, props );
		};
	}, 'editBlockListHook' );

	wp.hooks.addFilter( 
		'editor.BlockListBlock', 
		'greyd/hook/navigation/list', 
		editBlockListHook 
	);
	
} )( window.wp );