/**
 * Greyd Installer React App
 */
var greyd = greyd || {};

/**
 * Define the app
 */
greyd.installer = new function () {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	this.createNotice = ( props ) => {
		if ( _.isEmpty( props?.text ) ) return;

		wp.data.dispatch( "core/notices" ).removeNotices( wp.data.select( 'core/notices' ).getNotices().map( ( notice ) => notice.id ) );

		const {
			text,
			style = 'info',
			...moreProps
		} = props;

		const icon = props?.icon ? props?.icon : { success: '✅', warning: '⚠️', error: '❌', info: '💡' }[ style ];

		wp.data.dispatch( "core/notices" ).createNotice(
			style,
			text,
			{
				type: "snackbar",
				...moreProps,
				icon: icon
			}
		);
	};

	/**
	 * The app component
	 * 
	 * @param {object} props
	 * 
	 * @return {object}
	 */
	this.App = class extends wp.element.Component {

		constructor () {
			super();

			const savedTools = [];
			const selectedTools = [];
			if ( greyd_installer_args.active_tools ) {
				if ( greyd_installer_args.active_tools[ 'greyd-theme' ] ) {
					savedTools.push( 'greyd-theme' );
					selectedTools.push( 'greyd-theme' );
				}
				// if ( greyd_installer_args.active_tools[ 'gutenberg' ] ) {
				// 	savedTools.push( 'gutenberg' );
				// 	selectedTools.push( 'gutenberg' );
				// }
				if ( greyd_installer_args.active_tools[ 'greyd-forms' ] ) {
					savedTools.push( 'greyd-forms' );
					selectedTools.push( 'greyd-forms' );
				}
				if ( greyd_installer_args.active_tools[ 'greyd_globalcontent' ] ) {
					savedTools.push( 'greyd_globalcontent' );
					selectedTools.push( 'greyd_globalcontent' );
				}
			}

			this.state = {
				activeStepIndex: 0,
				selectedTools: selectedTools,
				savedTools: savedTools,
				selectedFeature: 'default',
				childThemeActive: greyd_installer_args.active_tools[ 'child-theme' ],
				classicContentsFound: ! _.isEmpty( greyd_installer_args.links[ 'classic_converter' ] )
			};
		}

		fireConfetti() {
			var count = 200;
			var defaults = {
				origin: { y: 0.7 }
			};

			function fire( particleRatio, opts ) {
				confetti( {
					...defaults,
					...opts,
					particleCount: Math.floor( count * particleRatio )
				} );
			}

			fire( 0.25, {
				spread: 26,
				startVelocity: 55,
			} );
			fire( 0.2, {
				spread: 60,
			} );
			fire( 0.35, {
				spread: 100,
				decay: 0.91,
				scalar: 0.8
			} );
			fire( 0.1, {
				spread: 120,
				startVelocity: 25,
				decay: 0.92,
				scalar: 1.2
			} );
			fire( 0.1, {
				spread: 120,
				startVelocity: 45,
			} );
		}

		render() {

			const {
				isDirty = false,
				activeStepIndex,
				selectedTools,
				savedTools,
				selectedFeature,
				savedFeature,
				childThemeActive,
				classicContentsFound
			} = this.state;

			if ( activeStepIndex === 3 && !isDirty ) {
				this.fireConfetti();
			}

			const tools = [
				{
					name: 'greyd-theme',
					title: __( 'Greyd Theme', 'greyd_hub' ),
					description: __( 'Speed meets precision! Craft lightning-fast, flawless websites using the Site Editor.', 'greyd_hub' ),
				},
				{
					name: 'greyd-forms',
					title: __( 'Greyd.Forms plugin', 'greyd_hub' ),
					description: __( 'Effortless forms, seamless design! Create versatile forms integrated perfectly into your design', 'greyd_hub' ),
				},
				// {
				// 	name: 'gutenberg',
				// 	title: __( 'Gutenberg plugin', 'greyd_hub' ),
				// 	description: __( 'The plugin is optional. It offers new experimental features that are not yet available in the WordPress core.', 'greyd_hub' ),
				// 	optional: true,
				// }
				{
					name: 'greyd_globalcontent',
					title: __( 'Greyd Global Content plugin', 'greyd_hub' ),
					description: __( 'Synchronize content and elements on any number of websites.', 'greyd_hub' ),
					chip: {
						title: __( 'License needed', 'greyd_hub' ),
						color: 'orange'
					}
				}
			];

			const features = [
				{
					name: 'default',
					title: __( 'Default setup', 'greyd_hub' ),
					description: __( 'Features that you need in almost every website: Dynamic Templates, Custom Post Types, Meta Fields, Pop-ups and more', 'greyd_hub' ),
				},
				{
					name: 'all',
					title: __( 'All the features', 'greyd_hub' ),
					description: __( 'Get the full power of the Suite.', 'greyd_hub' )
				},
				{
					name: 'minimal',
					title: __( 'Stripped down to minimum', 'greyd_hub' ),
					description: __( 'Blocks and the Hub. That\'s it.', 'greyd_hub' )
				}
			];

			const checkArraysForSameValues = ( arr1, arr2 ) => {
				return arr1.sort().toString() === arr2.sort().toString();
			};

			const saveTools = () => {

				// check if features are already saved
				if ( savedTools && checkArraysForSameValues( savedTools, selectedTools ) ) {
					greyd.installer.createNotice( {
						text: __( "Selected tools are already active!", 'greyd_hub' ),
						icon: '👍'
					} );
					this.setState( {
						activeStepIndex: activeStepIndex + 1
					} );
					return;
				}

				// make button busy
				this.setState( { isDirty: true } );

				wp.apiFetch( {
					path: '/greyd/v1/installer/tools',
					method: 'POST',
					data: {
						selectedTools: selectedTools
					},
				} ).then( ( res ) => {

					console.log( res );

					const response = JSON.parse( res );

					const newState = { isDirty: false };

					if ( response?.status === 200 ) {
						greyd.installer.createNotice( {
							text: __( "Tools activated successfully!", 'greyd_hub' ),
							style: 'success',
							icon: '🚀',
						} );
						newState.savedTools = selectedTools;
						newState.activeStepIndex = activeStepIndex + 1;
					} else {

						let text = __( "Tools couldn't be activated. Make sure you are allowed to activate plugins.", 'greyd_hub' );
						if ( response?.errors ) {
							text = __( "Tools couldn't be activated. Error(s):", 'greyd_hub' ) + ' ' + response.errors.join( ', ' );
						}

						greyd.installer.createNotice( {
							text: text,
							style: 'error',
							actions: [
								{
									label: __( 'Plugins page', 'greyd_hub' ),
									url: greyd_installer_args.links.plugins
								}
							]
						} );
					}

					this.setState( newState );

				} ).catch( ( err ) => {
					console.error( "apiFetch error: ", err );

					if ( typeof err === 'object' && err?.code === 'invalid_json' ) {
						greyd.installer.createNotice( {
							text: __( "It seems that the REST API is not working properly (error code: 'invalid_json'). Please check if the REST API is enabled and working.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Site health', 'greyd_hub' ),
									url: greyd_installer_args.links.site_health
								}
							]
						} );
					} else {
						greyd.installer.createNotice( {
							text: __( "Tools couldn't be activated. Make sure you are allowed to activate plugins.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Plugins page', 'greyd_hub' ),
									url: greyd_installer_args.links.plugins
								}
							]
						} );
					}
					this.setState( { isDirty: false } );
				} );
			};

			const saveFeatures = () => {

				// check if features are already saved
				if ( savedFeature && savedFeature == selectedFeature ) {
					greyd.installer.createNotice( {
						text: __( "Selected features already saved.", 'greyd_hub' ),
						icon: '👍'
					} );
					this.setState( {
						activeStepIndex: activeStepIndex + 1
					} );
					return;
				}

				// make button busy
				this.setState( { isDirty: true } );

				wp.apiFetch( {
					path: '/greyd/v1/installer/features',
					method: 'POST',
					data: {
						selectedFeature: selectedFeature
					},
				} ).then( ( res ) => {

					console.log( res );

					const response = JSON.parse( res );

					const newState = { isDirty: false };

					if ( response?.status === 200 ) {
						greyd.installer.createNotice( {
							text: __( "Settings saved and features activated.", 'greyd_hub' ),
							style: 'success',
							icon: '🎉',
						} );
						newState.savedFeature = selectedFeature;
						newState.activeStepIndex = activeStepIndex + 1;
					} else {

						let text = __( "Features couldn't be saved. Maybe try it again on the features page.", 'greyd_hub' );
						if ( response?.errors ) {
							text = __( "Features couldn't be saved. Error(s):", 'greyd_hub' ) + ' ' + response.errors.join( ', ' );
						}

						greyd.installer.createNotice( {
							text: text,
							style: 'error',
							actions: [
								{
									label: __( 'Features page', 'greyd_hub' ),
									url: greyd_installer_args.links.features,
								}
							]
						} );
					}

					this.setState( newState );

				} ).catch( ( err ) => {
					console.error( "apiFetch error: ", err );
					
					if ( typeof err === 'object' && err?.code === 'invalid_json' ) {
						greyd.installer.createNotice( {
							text: __( "It seems that the REST API is not working properly (error code: 'invalid_json'). Please check if the REST API is enabled and working.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Site health', 'greyd_hub' ),
									url: greyd_installer_args.links.site_health
								}
							]
						} );
					} else {
						greyd.installer.createNotice( {
							text: __( "Features couldn't be saved. Maybe try it again on the features page.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Features page', 'greyd_hub' ),
									url: greyd_installer_args.links.features,
								}
							]
						} );
					}
					this.setState( { isDirty: false } );
				} );
			};

			const installChildTheme = () => {

				// check if features are already saved
				if ( childThemeActive || greyd_installer_args.active_tools[ 'child-theme' ] ) {
					greyd.installer.createNotice( {
						text: __( "Child theme already active! Time to dive into customization!", 'greyd_hub' ),
						icon: '🌟'
					} );
					return;
				}

				// make button busy
				this.setState( { isDirty: true } );

				wp.apiFetch( {
					path: '/greyd/v1/installer/create-child-theme',
					method: 'POST'
				} ).then( ( res ) => {

					console.log( res );

					const response = JSON.parse( res );

					const newState = { isDirty: false };

					if ( response?.status === 200 ) {
						greyd.installer.createNotice( {
							text: __( "Child theme created. Ready to customize like a pro!", 'greyd_hub' ),
							style: 'success',
							icon: '🎨',
							actions: [
								{
									label: __( 'Themes page', 'greyd_hub' ),
									url: greyd_installer_args.links.themes,
								}
							]
						} );
						newState.childThemeActive = true;
					} else {

						let text = __( "Child theme creation failed. Check if you are allowed to install themes.", 'greyd_hub' );
						if ( response?.errors ) {
							text = __( "Child theme creation failed because of the following error(s):", 'greyd_hub' ) + ' ' + response.errors.join( ', ' );
						}

						greyd.installer.createNotice( {
							text: text,
							style: 'error',
							actions: [
								{
									label: __( 'Themes page', 'greyd_hub' ),
									url: greyd_installer_args.links.themes,
								}
							]
						} );
					}

					this.setState( newState );
				} ).catch( ( err ) => {
					console.error( "apiFetch error: ", err );
					
					if ( typeof err === 'object' && err?.code === 'invalid_json' ) {
						greyd.installer.createNotice( {
							text: __( "It seems that the REST API is not working properly (error code: 'invalid_json'). Please check if the REST API is enabled and working.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Site health', 'greyd_hub' ),
									url: greyd_installer_args.links.site_health
								}
							]
						} );
					} else {
						greyd.installer.createNotice( {
							text: __( "Child theme creation failed. Check if you are allowed to install themes.", 'greyd_hub' ),
							style: 'error',
							actions: [
								{
									label: __( 'Themes page', 'greyd_hub' ),
									url: greyd_installer_args.links.themes,
								}
							]
						} );
					}
					this.setState( { isDirty: false } );
				} );
			};

			const getStep = () => {

				if ( activeStepIndex === 0 ) {
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: 'greyd-installer-dialog--content',
							key: 'step-0'
						}, [
							el( 'h2', {}, __( 'Site setup', 'greyd_hub' ) ),
							el( 'p', {}, __( "Install required tools, setup your features and install a child theme. Ready to thrive in just four simple steps!", 'greyd_hub' ) ),
							el( 'ol', {}, [
								el( 'li', {}, [
									el( 'span', {}, __( 'Getting started', 'greyd_hub' ) ),
								] ),
								el( 'li', {}, [
									el( 'span', {}, __( 'Install tools', 'greyd_hub' ) ),
								] ),
								el( 'li', {}, [
									el( 'span', {}, __( 'Select features', 'greyd_hub' ) ),
								] ),
								el( 'li', {}, [
									el( 'span', {}, __( 'Finish', 'greyd_hub' ) ),
								] ),
							] )
						] ),
						el( wp.components.Flex, {
							className: 'greyd-installer-dialog-actions',
							style: {
								marginTop: '2rem',
							},
							justify: 'space-between',
						}, [
							// el( wp.components.FlexItem, {}, [
							// 	el( wp.components.Button, {
							// 		variant: "tertiary",
							// 		disabled: isDirty,
							// 		className: 'is-size-large',
							// 		href: greyd_installer_args.links.wp_dashboard,
							// 	}, __( 'Skip for now', 'greyd_hub' ) ),
							// ] ),
							el( wp.components.FlexBlock, {}, [
								el( wp.components.Button, {
									isPrimary: true,
									disabled: isDirty,
									className: 'is-size-large',
									onClick: () => this.setState( { activeStepIndex: activeStepIndex === 0 ? 1 : 0 } )
								}, [
									el( 'span', {}, __( 'Get started', 'greyd_hub' ) ),
									el( 'svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '24',
										height: '24',
										'aria-hidden': 'true',
										focusable: 'false',
										style: {
											marginLeft: '1rem',
										}
									}, [
										el( 'path', {
											d: 'm14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z'
										} )
									] )
								] ),
							] )
						] )
					] );
				}

				if ( activeStepIndex === 1 ) {
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: 'greyd-installer-dialog--content',
							key: 'step-1'
						}, [
							el( 'h2', {}, __( 'Install tools', 'greyd_hub' ) ),
							...tools.map( ( tool ) => {
								return el( greyd.installer.SelectCard, {
									checked: tool?.required || selectedTools.includes( tool.name ) || savedTools.includes( tool.name ),
									disabled: tool?.required || savedTools.includes( tool.name ),
									onChange: ( value ) => {
										this.setState( {
											selectedTools: value
												? [ ...selectedTools, tool.name ]
												: selectedTools.filter( ( t ) => t !== tool.name )
										} );
									},
									label: tool.title,
									description: tool.description,
									chips: [
										tool?.required ? {
											label: __( 'Required', 'greyd_hub' ),
											color: 'purple'
										} : null,
										tool?.optional ? {
											label: __( 'Optional', 'greyd_hub' ),
											color: 'orange'
										} : null,
										tool?.chip ? {
											label: tool?.chip?.title || tool?.chip,
											color: tool?.chip?.color || 'purple'
										} : null,
										savedTools.includes( tool.name ) ? {
											label: __( 'Already active', 'greyd_hub' ),
											color: 'green'
										} : null
									]
								} );
							} )
						] ),
						el( wp.components.Flex, {
							className: 'greyd-installer-dialog-actions',
							style: {
								marginTop: '2rem',
							},
							justify: 'space-between',
						}, [
							el( wp.components.FlexItem, {}, [
								el( wp.components.Button, {
									variant: "tertiary",
									disabled: isDirty,
									className: 'is-size-large',
									onClick: () => this.setState( { activeStepIndex: activeStepIndex - 1 } )
								}, [
									el( 'svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '24',
										height: '24',
										'aria-hidden': 'true',
										focusable: 'false',
										style: {
											marginRight: '1rem',
										}
									}, [
										el( 'path', {
											d: 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z'
										} )
									] ),
									el( 'span', {}, __( 'Back', 'greyd_hub' ) ),
								] ),
							] ),
							el( wp.components.FlexBlock, {}, [
								el( wp.components.Button, {
									isPrimary: true,
									isBusy: isDirty,
									disabled: isDirty,
									className: 'is-size-large',
									onClick: () => saveTools()
								}, [
									el( 'span', {}, isDirty ? __( 'Installing...', 'greyd_hub' ) : __( 'Install tools', 'greyd_hub' ) ),
									el( 'svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '24',
										height: '24',
										'aria-hidden': 'true',
										focusable: 'false',
										style: {
											marginLeft: '1rem',
										}
									}, [
										el( 'path', {
											d: 'm14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z'
										} )
									] )
								] ),
							] )
						] )
					] );
				}

				if ( activeStepIndex === 2 ) {
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: 'greyd-installer-dialog--content',
							key: 'step-2'
						}, [
							el( 'h2', {}, __( 'Select features', 'greyd_hub' ) ),
							...features.map( ( feature ) => {
								return el( greyd.installer.SelectCard, {
									variant: 'radio',
									value: feature.name,
									checked: feature.name === selectedFeature,
									onChange: ( value ) => {
										this.setState( {
											selectedFeature: value
										} );
									},
									label: feature.title,
									description: feature.description
								} );
							} )
						] ),
						el( wp.components.Flex, {
							className: 'greyd-installer-dialog-actions',
							style: {
								marginTop: '2rem',
							},
							justify: 'space-between',
						}, [
							el( wp.components.FlexItem, {}, [
								el( wp.components.Button, {
									variant: "tertiary",
									disabled: isDirty,
									className: 'is-size-large',
									onClick: () => this.setState( { activeStepIndex: activeStepIndex - 1 } )
								}, [
									el( 'svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '24',
										height: '24',
										'aria-hidden': 'true',
										focusable: 'false',
										style: {
											marginRight: '1rem',
										}
									}, [
										el( 'path', {
											d: 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z'
										} )
									] ),
									el( 'span', {}, __( 'Back', 'greyd_hub' ) ),
								] ),
							] ),
							el( wp.components.FlexBlock, {}, [
								el( wp.components.Button, {
									isPrimary: true,
									isBusy: isDirty,
									disabled: isDirty,
									className: 'is-size-large',
									onClick: () => saveFeatures()
								}, [
									el( 'span', {}, isDirty ? __( 'Activating...', 'greyd_hub' ) : __( 'Activate features', 'greyd_hub' ) ),
									el( 'svg', {
										xmlns: 'http://www.w3.org/2000/svg',
										viewBox: '0 0 24 24',
										width: '24',
										height: '24',
										'aria-hidden': 'true',
										focusable: 'false',
										style: {
											marginLeft: '1rem',
										}
									}, [
										el( 'path', {
											d: 'm14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z'
										} )
									] )
								] )
							] )
						] )
					] );
				}

				if ( activeStepIndex === 3 ) {
					return el( wp.element.Fragment, {}, [
						el( 'div', {
							className: 'greyd-installer-dialog--content',
							key: 'step-3'
						}, [
							el( wp.components.Flex, { align: 'flex-start' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'h2', {}, __( 'Setup complete!', 'greyd_hub' ) ),
									el( 'p', {}, __( 'What would you like to do next?', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										isPrimary: true,
										className: 'is-size-large',
										href: greyd_installer_args.links.wp_dashboard,
									}, [
										el( 'span', {}, __( 'Leave', 'greyd_hub' ) ),
										el( 'svg', {
											xmlns: 'http://www.w3.org/2000/svg',
											viewBox: '0 0 24 24',
											width: '24',
											height: '24',
											'aria-hidden': 'true',
											focusable: 'false',
											style: {
												marginLeft: '1rem',
											}
										}, [
											el( 'path', {
												d: 'm14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z'
											} )
										] )
									] )
								] )
							] ),

							// classic converter
							classicContentsFound && el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'label', {}, __( 'Convert your theme', 'greyd_hub' ) ),
									el( 'p', {}, __( 'Transform your old classic Greyd.Suite setup to the new FSE features.', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										variant: "secondary",
										href: greyd_installer_args.links.classic_converter,
									}, __( 'Classic to FSE Converter ', 'greyd_hub' ) )
								] )
							] ),

							// home
							el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'label', {}, __( 'View your site', 'greyd_hub' ) ),
									el( 'p', {}, __( 'Open your site to check out the live frontend view.', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										variant: "secondary",
										href: greyd_installer_args.links.home,
									}, __( 'Homepage', 'greyd_hub' ) )
								] )
							] ),

							// child theme
							(
								savedTools.includes( 'greyd-theme' ) &&
								el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
									el( wp.components.FlexBlock, {}, [
										el( 'label', {}, __( 'Create child theme', 'greyd_hub' ) ),
										el( 'p', {}, __( 'Child themes are the recommended way of modifying an existing theme.', 'greyd_hub' ) ),
									] ),
									el( wp.components.FlexItem, {}, [
										el( wp.components.Button, {
											variant: "secondary",
											isBusy: isDirty,
											disabled: isDirty || childThemeActive,
											onClick: () => { !childThemeActive && installChildTheme(); }
										}, [
											isDirty ? __( 'Creating...', 'greyd_hub' ) : ( childThemeActive ? __( '✓ Active', 'greyd_hub' ) : __( 'Create & activate', 'greyd_hub' ) )
										] )
									] )
								] )
							),

							// license
							el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'label', {}, __( 'Activate license', 'greyd_hub' ) ),
									el( 'p', {}, __( 'Enter your license key to get the full power of the Suite.', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										variant: "secondary",
										href: greyd_installer_args.links.license,
									}, __( 'License page', 'greyd_hub' ) ),
								] )
							] ),

							// helpcenter
							el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'label', {}, __( 'Greyd Helpcenter', 'greyd_hub' ) ),
									el( 'p', {}, __( 'Browse through our tutorials and documentation to learn more about the Suite.', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										variant: "secondary",
										href: greyd_installer_args.links.greyd_helpcenter,
									}, __( 'Helpcenter', 'greyd_hub' ) ),
								] )
							] ),

							// edit
							el( wp.components.Flex, { className: 'greyd-installer-dialog--section' }, [
								el( wp.components.FlexBlock, {}, [
									el( 'label', {}, __( 'Edit your site', 'greyd_hub' ) ),
									el( 'p', {}, __( 'Go to the Site Editor to view and edit your homepage and customize your site.', 'greyd_hub' ) ),
								] ),
								el( wp.components.FlexItem, {}, [
									el( wp.components.Button, {
										variant: "secondary",
										href: greyd_installer_args.links.editor,
									}, __( 'Edit site', 'greyd_hub' ) ),
								] )
							] ),
						] )
					] );
				}
			};

			return el( 'div', {
				className: 'greyd-installer-container',
				'data-step': activeStepIndex.toString(),
			}, [
				el( 'div', { className: 'greyd-installer-head' }, [
					el( wp.components.Button, {
						isPrimary: false,
						className: 'back-to-dash',
						isSmall: true,
						href: greyd_installer_args.links.wp_dashboard,
						icon: 'wordpress'
					}, __( 'Back to dashboard', 'greyd_hub' ) )
				] ),
				el( 'div', { className: 'greyd-installer-claim' }, [
					el( 'img', { src: greyd_installer_args.logo, alt: 'Greyd Logo' } ),
					el( 'h1', {}, __( 'Welcome to Greyd: The all-in-one driving force of your WordPress business', 'greyd_hub' ) ),
					el( 'p', {}, __( 'Greyd.Suite is the comprehensive platform to scale up your WordPress business and build the web empire you\'ve always dreamed of. Whether you are a freelancer, agency or large corporation, your WordPress revolution starts here!', 'greyd_hub' ) ),
				] ),
				el( 'div', { className: 'greyd-installer-dialog' }, [
					getStep(),
					el( 'div', {
						className: 'greyd-installer-dialog--pagination'
					}, [
						el( 'button', {
							className: 'greyd-installer-dialog--pagination--dot',
							'data-active': activeStepIndex == 0 ? 'true' : 'false',
							onClick: () => this.setState( { activeStepIndex: 0 } )
						} ),
						el( 'button', {
							className: 'greyd-installer-dialog--pagination--dot',
							'data-active': activeStepIndex == 1 ? 'true' : 'false',
							onClick: () => this.setState( { activeStepIndex: 1 } )
						} ),
						el( 'button', {
							className: 'greyd-installer-dialog--pagination--dot',
							'data-active': activeStepIndex == 2 ? 'true' : 'false',
							onClick: () => this.setState( { activeStepIndex: 2 } )
						} ),
						el( 'button', {
							className: 'greyd-installer-dialog--pagination--dot',
							'data-active': activeStepIndex == 3 ? 'true' : 'false',
							onClick: () => this.setState( { activeStepIndex: 3 } )
						} ),
					] )
				] ),
				el( wp.components.SnackbarList, {
					notices: wp.data.select( 'core/notices' ).getNotices(),
				} )
			] );
		}

	};

	/**
	 * Select card component
	 * 
	 * @param {object} props
	 * @property {bool} checked         Whether the card is checked
	 * @property {string} label         The label of the card
	 * @property {string} description   The description of the card
	 * @property {bool} disabled        Whether the card is disabled
	 * @property {array} chips          Chips to display
	 * @property {string} variant       Can be one of: checkbox, radio
	 * @property {function} onChange    Callback function when the card is clicked
	 * @property {string} value         The value of the card
	 * 
	 * @return {object}
	 */
	this.SelectCard = class extends wp.element.Component {

		constructor () {
			super();
		}

		render() {

			const {
				checked = false,
				label,
				description = '',
				disabled = false,
				chips = [],
				variant = 'checkbox',
				onChange = () => { },
				value = null,
			} = this.props;

			return el( wp.components.Flex, {
				className: 'greyd-installer-card',
				align: 'flex-start',
				onClick: () => {
					if ( disabled ) {
						return;
					}
					onChange(
						variant === 'radio'
							? value
							: !checked
					);
				},
				...( disabled ? {
					'aria-disabled': 'true',
				} : {} )
			}, [
				el( wp.components.FlexItem, {}, [ (
					variant === 'radio'
						? el( wp.components.RadioControl, {
							selected: checked,
							options: [
								{
									label: '',
									value: true,
								}
							],
							onChange: () => { }
						} )
						: el( wp.components.CheckboxControl, {
							checked: checked,
							disabled: disabled,
							onChange: () => { }
						} )
				) ] ),
				el( wp.components.FlexBlock, {}, [
					el( 'label', { className: 'greyd-installer-card--title' }, label ),
					el( 'p', { className: 'greyd-installer-card--description' }, description ),
				] ),
				chips.length > 0 && el( 'div', {
					className: 'greyd-installer-card--chips',
				}, chips.map( ( chip ) => {
					return ( chip && chip?.label ) && el( 'span', {
						className: 'greyd-installer-card--chip',
						...( chip?.color ? {
							'data-color': chip.color,
						} : {} )
					}, chip.label );
				} ) )
			] );
		}
	};
};

/**
 * Create the root element and render the app
 */
wp.domReady( function () {
	const root = wp.element.createRoot( document.getElementById( 'greyd-installer' ) );
	root.render( wp.element.createElement( greyd.installer.App ) );
} );