<?php
/**
 * Loginform block.
 * 
 * @since 1.5.4
 */
if ( !\Greyd\User\Manage::wp_up_to_date() ) {
	return;
}

function greyd_register_loginform_block() {

	// in case we need to update the scripts & styles
	$version = '1.0';

	// register the scripts
	if ( function_exists( 'wp_register_script' ) ) {

		wp_register_script(
			'greyd-loginform-editor-script',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.js',
			array( 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			$version
		);
	}

	// add script translations
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'greyd-loginform-editor-script', 'greyd_hub', trailingslashit( WP_PLUGIN_DIR ) . 'greyd-plugin/languages' );
	}

	// // register the styles
	// if ( function_exists( 'wp_register_style' ) ) {
	// 	wp_register_style(
	// 		'greyd-loginform-frontend-style',
	// 		trailingslashit( plugin_dir_url( __FILE__ ) ) . 'style.css',
	// 		null,
	// 		$version
	// 	);
	// 	wp_register_style(
	// 		'greyd-loginform-editor-style',
	// 		trailingslashit( plugin_dir_url( __FILE__ ) ) . 'editor.css',
	// 		null,
	// 		$version
	// 	);
	// }

	// register the blocks
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'greyd/loginform',
			array(
				'editor_script'   => 'greyd-loginform-editor-script',
				'editor_style'    => 'greyd-loginform-editor-style',
				'style'           => 'greyd-loginform-frontend-style',
				'attributes'      => array(
					'labels' => array(
						'label_username' => 'string',
						'label_password' => 'string',
						'label_remember' => 'string',
						'label_log_in'   => 'string',
					),
				),
				'render_callback' => 'greyd_render_loginform_block',
			)
		);
	}
}

function greyd_render_loginform_block( $atts ) {

	$label_username = isset( $atts['usernameLabel']['label'] ) ? $atts['usernameLabel']['label'] : __( 'Username', 'greyd_hub' );
	$label_password = isset( $atts['passwordLabel']['label'] ) ? $atts['passwordLabel']['label'] : __( 'Password', 'greyd_hub' );
	$label_remember = isset( $atts['remembermeLabel']['label'] ) ? $atts['remembermeLabel']['label'] : __( 'Remember me', 'greyd_hub' );
	$label_login    = isset( $atts['labels']['login'] ) ? $atts['labels']['login'] : __( 'Log in', 'greyd_hub' );

	$redirect_url = isset( $atts['redirectURL'] ) ? $atts['redirectURL'] : admin_url();
	ob_start();

	echo "<div class='greyd-login-form'>";

	// user not logged in
	if ( ! is_user_logged_in() ) {
		wp_login_form( array(
			'redirect'       => $redirect_url,
			'form_id'        => 'loginform-custom',
			'label_username' => $label_username,
			'label_password' => $label_password,
			'label_remember' => $label_remember,
			'label_log_in'   => $label_login,
			// 'echo' => true,
			// 'value_username' => NULL,
			// 'value_remember' => false,
			// 'id_username' => 'user_login',
			// 'id_password' => 'user_pass',
			// 'id_remember' => 'rememberme',
			// 'id_submit' => 'wp-submit',
			'remember' => true,
		) );
	}
	// logged in
	else {
		wp_loginout( home_url() ); // Display "Log Out" link.
		echo ' | ';
		wp_register( '', '' ); // Display "Site Admin" link.
	}
	echo '</div>';
	$html = ob_get_contents();
	ob_end_clean();
	return $html;
}

add_action( 'init', 'greyd_register_loginform_block', 99 );
