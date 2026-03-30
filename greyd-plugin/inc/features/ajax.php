<?php
/**
 * Features AJAX functionality.
 * 
 * Handles all AJAX requests and data processing for the features page.
 */
namespace Greyd\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new \Greyd\Features\Ajax();

class Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Handle POST data when on features page
		add_action( 'plugins_loaded', array( $this, 'maybe_handle_features_data' ), 1 );
	}

	/**
	 * Check if we need to handle features POST data
	 */
	public function maybe_handle_features_data() {
		// handle POST Data
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'greyd_features' ) {
			// as soon as possible, before features register and inject menus
			if ( ! empty( $_POST ) ) {
				$this->handle_data( $_POST );
			}
		}
	}

	/*
	=======================================================================
		HANDLE FEATURE SETTINGS
	=======================================================================
	*/

	/**
	 * handle POST Data
	 *
	 * @param array $post_data  Raw $_POST data.
	 */
	public function handle_data( $post_data ) {

		// check nonce
		$nonce_action = 'greyd_features_save';
		$nonce        = isset( $post_data['_wpnonce'] ) ? $post_data['_wpnonce'] : null;
		$mode         = is_multisite() && is_network_admin() ? 'global' : 'site';

		// echo '<pre>';
		// print_r($post_data);
		// echo '</pre>';

		$success = false;
		if ( $nonce && wp_verify_nonce( $nonce, $nonce_action ) ) {

			if ( isset( $post_data['mode'] ) && $post_data['mode'] == $mode ) {

				if ( isset( $post_data['include'] ) ) {
					$settings = array();
					if ( isset( $post_data['feature'] ) ) {
						foreach ( $post_data['feature'] as $feature => $value ) {
							if ( $value != 'on' ) {
								continue;
							}
							if ( isset( $post_data['include'][ $feature ] ) ) {
								// add feature setting
								$settings[ $feature ] = $post_data['include'][ $feature ];
							}
						}
					}
					// debug($settings);

					if ( isset( $post_data['requires'] ) ) {
						foreach ( $settings as $setting => $value ) {

							if ( isset( $post_data['requires'][ $setting ] ) && ! empty( $post_data['requires'][ $setting ] ) ) {
								// feature has requirements
								$dependencies = explode( ', ', $post_data['requires'][ $setting ] );
								$check        = array();
								foreach ( $dependencies as $i => $dependency ) {
									$dependencies[ $i ] = trim( $dependency );
									// check if requirement is met
									if ( isset( $settings[ $dependencies[ $i ] ] ) ||
										( $mode == 'site' && isset( \Greyd\Features::get_saved_features()['global'][ $dependencies[ $i ] ] ) ) ) {
										array_push( $check, 'true' );
									} else {
										array_push( $check, 'false' );
									}
								}
								$check = array_unique( $check );
								if ( count( $check ) == 2 || $check[0] == 'false' ) {
									\Greyd\Helper::show_message( sprintf( __( "'%s' feature disabled (required feature no longer available).", 'greyd_hub' ), $setting ), 'info' );
									unset( $settings[ $setting ] );
								}
							}
						}
					}

					// save data
					// echo '<pre>';
					// print_r($settings);
					// echo '</pre>';

					$success = \Greyd\Features::update_features( $mode, $settings );
				}
			}

			if ( $success ) {
				\Greyd\Helper::show_message( __( 'Feature setup saved.', 'greyd_hub' ), 'success' );
			}
		}
		if ( ! $success ) {
			\Greyd\Helper::show_message( __( 'Feature setup could not be saved.', 'greyd_hub' ), 'error' );
		}

	}

}
