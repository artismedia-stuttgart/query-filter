/**
 * Login form block editor script.
 * 
 * @since 1.5.4
 */
( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;

	/**
	 * Register the loginform block.
	 */
	wp.blocks.registerBlockType( 'greyd/loginform', {
		title: __( 'Login Form', 'greyd_hub' ),
		description: __( 'Show a user login form on your website.', 'greyd_hub' ),
		icon: greyd.tools.getBlockIcon('login'),
		category: 'greyd-blocks',
		keywords: [ 'login', 'loginform' ],
		supports: {
			anchor: true,
		},

		attributes: {

			redirectURL: {
				type: "string"
			},
			rememberme: {
				type: "bool",
				default: true,
			},
			usernameLabel: {
				type: "object",
				properties: {
					label: { type: "string" },
					labelStyles: { type: "object" },
				}, default: {
					label: __( 'Username', 'greyd_hub' ),
					labelStyles: {}
				}
			},
			passwordLabel: {
				type: "object",
				properties: {
					label: { type: "string" },
					labelStyles: { type: "object" },
				}, default: {
					label: __( 'Password', 'greyd_hub' ),
					labelStyles: {}
				}
			},
			remembermeLabel: {
				type: "object",
				properties: {
					label: { type: "string" },
					labelStyles: { type: "object" },
				}, default: {
					label: __( 'Remember me', 'greyd_hub' ),
					labelStyles: {}
				}
			},
			submitLabel: {
				type: "object",
				properties: {
					label: { type: "string" },
					labelStyles: { type: "object" },
				}, default: {
					label: __( 'Log in', 'greyd_hub' ),
					labelStyles: {}
				}
			},
		},

		edit: function ( props ) {

			// generate attributes
			const newGreydClass = greyd.tools.getGreydClass( props );
			if ( props.attributes?.greydClass !== newGreydClass ) {
				props.setAttributes( { greydClass: newGreydClass } );
			}

			// atts
			const atts = props.attributes;


			return [



				el( wp.blockEditor.InspectorControls, {},

					// ALLGEMEIN
					el( wp.components.PanelBody, { title: __( 'General', 'greyd_hub' ), initialOpen: true },
						// Feldname
						el( wp.components.TextControl, {
							label: __( 'Redirect URL', 'greyd_hub' ),
							value: atts.redirectURL,
							onChange: ( value ) => props.setAttributes( { redirectURL: value } )
						} ),

						// Pflichtfeld
						el( wp.components.ToggleControl, {
							label: __( "Show remember me checkbox", 'greyd_hub' ),
							checked: !!atts.rememberme,
							onChange: ( value ) => props.setAttributes( { rememberme: !!value } )
						} ),
					),
				),
				// preview

				el( 'div', {
					id: "loginform-custom"
				}, [

					el( "p", {
						className: "login-username",
					}, [
						el( 'div', {
							className: "label_wrap",
						}, [
							el( 'span', {
								className: "label",
							}, [
								el( wp.blockEditor.RichText, {
									tagName: "span",
									className: "label-content",
									value: atts.usernameLabel.label,
									onChange: ( value ) => props.setAttributes( { ...atts.usernameLabel, usernameLabel: { label: value } } ),
									placeholder: props.isSelected ? __( 'Enter here', 'greyd_hub' ) : "",
									allowedFormats: [],
								} ),
							] ),
						] ),
						el( "input", {
							id: "user_login",
							type: 'text',
							readOnly: true
						} )
					] ),
					el( "p", {
						className: "login-password",
					}, [
						el( 'div', {
							className: "label_wrap",
						}, [
							el( 'span', {
								className: "label",
							}, [
								el( wp.blockEditor.RichText, {
									tagName: "span",
									className: "label-content",
									value: atts.passwordLabel.label,
									onChange: ( value ) => props.setAttributes( { ...atts.passwordLabel, passwordLabel: { label: value } } ),
									placeholder: props.isSelected ? __( 'Enter here', 'greyd_hub' ) : "",
									allowedFormats: [],
								} ),
							] ),
						] ),
						el( "input", {
							id: "user_pass",
							type: 'password',
							readOnly: true
						} )
					] ),
					atts.rememberme ? el( "div", {
						className: "login-remember label checkbox-label",
					}, [
						el( "label", {
							for: "rememberme",
						}, [
							el( "input", {
								id: "rememberme",
								type: 'checkbox',
								readOnly: true
							} ),
							el( "span", {} ),

						] ),
						el( "div", { style: { cursor: 'auto', display: 'inline-block' } },
							el( wp.blockEditor.RichText, {
								tagName: "span",
								className: "label-content",
								value: atts.remembermeLabel.label,
								onChange: ( value ) => props.setAttributes( { ...atts.remembermeLabel, remembermeLabel: { label: value } } ),
								// allowedFormats: [ 'core/bold', 'core/italic', 'core/subscript', 'core/superscript', 'greyd/versal', 'greyd/text-background' ],
							} )
						)
					] ) : '',
					el( "p", {
						className: "login-submit",
					},
						el( "input", {
							id: "wp-submit",
							className: 'button button-primary',
							type: 'submit',
							readOnly: true,
							value: __( 'Log in', 'greyd_hub' )
						} )
					)
				] )
			];
		},
	} );

} )( window.wp );