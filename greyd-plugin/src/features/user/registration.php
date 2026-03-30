<?php
/**
 * Extend Forms to allow registration and update of users from frontend
 *
 * @since 1.5.4
 */
namespace Greyd\User;

use \Greyd\Helper;
use \Greyd\User\Manage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Registration();
class Registration {

	const OPTION = 'user_registration';

	public $default_user_relations;

	public function __construct() {

		if ( ! Manage::wp_up_to_date() ) {
			return;
		}

		// settings
		add_filter( 'formmeta_menu_tabs', array( $this, 'add_menu_tab' ) );
		add_filter( 'greyd_forms_metabox_formmode_options', array( $this, 'add_formmode_options' ) );
		add_action( 'formmeta_save', array( $this, 'save_metabox' ) );

		// handle
		add_action( 'formhandler_before_entry', array( $this, 'handle_create_new_user_form' ), 10, 4 );
		add_action( 'formhandler_before_entry', array( $this, 'handle_update_user_form' ), 10, 4 );

		// handle links from within mails
		add_action( 'parse_request', array( $this, 'handle_confirmation_link' ), 1 );

		// handle logins of unconfirmed users
		add_action( 'wp_authenticate', array( $this, 'is_user_confirmed' ) );

		// add the pending role which is for unconfirmed users
		add_action( 'init', array( $this, 'add_pending_role' ) );

		$this->default_user_relations = array(
			'user_mail'   => __( "Email", 'greyd_hub' ),
			'user_pass'   => __( "Password", 'greyd_hub' ),
			'user_login'  => __( "Username", 'greyd_hub' ),
			'first_name'  => __( "First name", 'greyd_hub' ),
			'last_name'   => __( "Last name", 'greyd_hub' ),
			'description' => __( "Biography", 'greyd_hub' ),
			'user_url'    => __( "Website", 'greyd_hub' ),
			'nickname'    => __( "Nickname", 'greyd_hub' ),
		);

		// hook block rendering
		add_filter( 'render_block', array( $this, 'render_block' ), 40, 2 );
		add_filter( 'formblocks_raw_content', array( $this, 'filter_form_content' ), 40, 2 );
	}

	/*
	=======================================================================

								META BOX

	=======================================================================
	*/

	public function add_menu_tab( $menu_tabs ) {
		array_push(
			$menu_tabs,
			array(
				'href'    => 'field_assignment',
				'name'    => __( "Field assignment", 'greyd_hub' ),
				'call'    => array( $this, 'render_field_assignment' ),
				'general' => false,
			),
			array(
				'href'    => 'registration_mail',
				'name'    => __( "Emails", 'greyd_hub' ),
				'call'    => array( $this, 'render_registration_mail' ),
				'general' => false,
			)
		);
		return $menu_tabs;
	}

	public function add_formmode_options( $form_mode_options ) {

		$form_mode_options['register_user'] = array(
			'value' => 'register_user',
			'label' => _x( "User registration", 'small', 'greyd_hub' ),
			'tabs'  => array(
				'general',
				'field_assignment',
				'registration_mail',
			),
		);
		$form_mode_options['update_user']   = array(
			'value' => 'update_user',
			'label' => _x( "Update user data", 'small', 'greyd_hub' ),
			'tabs'  => array(
				'general',
				'field_assignment',
			),
		);

		return $form_mode_options;
	}

	/**
	 * handles the process of creating a new user through a frontend form
	 *
	 * @param object $post        Post object of the form
	 * @param array  $form_fields all form fields of the corresponding form
	 */
	public function render_field_assignment( $post, $form_fields ) {

		$meta            = get_post_meta( $post->ID, self::OPTION, true );
		$form_mode       = get_post_meta( $post->ID, 'form_mode', true );

		if ( ! $meta ) {
			$meta = array();
		} elseif ( is_object( $meta ) ) {
			$meta = json_decode( json_encode( $meta ), true );
		}

		$field_relations = isset( $meta['field_relations'] ) ? $meta['field_relations'] : array();
		$selected_role   = isset( $meta['role'] ) ? $meta['role'] : 'subscriber';
		$title           = '';

		// get the extended roles array and reverse it so the
		$roles = array_reverse( Manage::get_roles( false ) );

		if ( $form_mode == 'register_user' ) {
			$title = __( "User registration", 'greyd_hub' );
		} elseif ( $form_mode == 'update_user' ) {
			$title = __( "Update user", 'greyd_hub' );
		}

		// get all meta fields
		$meta_fields = array();
		foreach( $roles as $role ) {
			foreach( $role['fields'] as $field ) {
				$field[ 'name' ] = 'greyd_' . $field[ 'name' ];
				$meta_fields[ $field['name'] ] = $field;
			}
		}

		?>
		<table class='form_meta_box'>
			<thead>
				<tr>
					<th colspan='2'>
						<div><?php echo $title; ?></div>
					</th>
				</tr>
				<tr>
					<td>
						<label><?php echo __( "User role", 'greyd_hub' ); ?></label>
						<p>
							<select name="<?php echo self::OPTION; ?>[role]">
							<?php
							if ( $form_mode == 'update_user' ) {
								echo "<option value=''>-- " . __( "Every role", 'greyd_hub' ) . ' --</option>';
							}
							foreach ( $roles as $role ) {

								if ( $role['slug'] === 'super' || $role['slug'] === 'administrator' || $role['slug'] === 'pending' ) {
									continue;
								}

								if ( $role['slug'] === $selected_role ) {
									$attribute   = 'selected="selected"';
								} else {
									$attribute = '';
								}

								echo "<option value='{$role['slug']}' {$attribute}>{$role['title']}</option>";
							}
							?>
							</select>
						</p>
						<?php
						if ( $form_mode == 'register_user' ) {
							echo Helper::render_info_box( array(
								'style' => 'blue',
								'text'  => __( "Select the user role that the user should receive after successful registration.", 'greyd_hub' ),
							) );
						} elseif ( $form_mode == 'update_user' ) {
							echo Helper::render_info_box( array(
								'style' => 'blue',
								'text'  => __( "If you select a specific user role, the form will only be displayed for users with this role. If you do not select a role, the form is displayed for all users.", 'greyd_hub' ),
							) );
						}
						?>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="2" style="padding-top:1em;">
						<?php echo __( "Assign fields", 'greyd_hub' ); ?>
						<small><?php echo __( 'Select which form field should be used to create the respective user.', 'small', 'greyd_hub' ); ?></small>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="inner-table">
						<table>
							<thead>
								<tr>
									<th style="width:20%;"><?php echo __( "Form field", 'greyd_hub' ); ?></th>
									<td style="padding:0 .5em;text-align:center;vertical-align:baseline;width:40%;">→</td>
									<th><?php echo __( "User fields", 'greyd_hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( count( $form_fields ) < 0 ) : ?>
									<tr>
										<td colspan="3">
											<div class="greyd_info_box info">
												<span class="dashicons dashicons-info"></span>
												<span><?php echo __( "To display fields, please save the form.", 'greyd_hub' ); ?></span>
											</div>
										</td>
									</tr>
								<?php else :
									$i = 0;
									if ( is_array( $field_relations ) && count( $field_relations ) > 0 ) {
										foreach ( $field_relations as $field_name => $relation ) {
											// echo "<tr class='".( $i === 0 ?\ 'hide' : '' )."' data-index='$i'><td>";
											echo "<tr data-index='{$i}'><td>";

											echo "<select name='" . self::OPTION . "[form_fields][]' class='field_relations frm'>";
											foreach ( $form_fields as $value => $field ) {
												if ( $field_name === $value ) {
													$attribute = 'selected="selected"';
												} else {
													$attribute = '';
												}
												echo "<option value='{$value}' {$attribute}>{$field}</option>";
											}
											echo '</select>';

											echo '</td>' .
											"<td style='padding:0 .5em;text-align:center;vertical-align:baseline;'>→</td>" .
											"<td class='flex-row'>";
											echo "<select name='" . self::OPTION . "[field_assignment][]' class='user_fields' data-init-value='{$relation}'>".
												"<option value='none' selected='selected'>" . __( 'Please select', 'greyd_hub' ) . "</option>";
											echo "<optgroup label='" . __( "Standard user fields", 'greyd_hub' ) . "'>";
											foreach ( $this->default_user_relations as $value => $label ) {
												if ( $form_mode === 'update_user' && $value === 'user_login' ) {
													continue;
												}
												if ( $relation === $value ) {
													$attribute = 'selected="selected"';
												} else {
													$attribute = '';
												}
												echo "<option value='{$value}' {$attribute}>{$label}</option>";
											}
											echo '</optgroup>';
											echo "<optgroup label='" . __( "User meta fields", 'greyd_hub' ) . "'>";
											if ( isset( $meta_fields ) && is_array( $meta_fields ) ) {
												foreach ( $meta_fields as $meta_field ) {
													if ( $relation === $meta_field['name'] ) {
														$attribute = 'selected="selected"';
													} else {
														$attribute = '';
													}
													echo "<option value='{$meta_field['name']}' $attribute >{$meta_field['label']}</option>";
												}
											}
											echo '</optgroup>';
											echo '</select>';

											echo '<span class="button remove">✕</span></td></tr>';
											$i++;
										}
									} else {
										echo "<tr data-index='0'><td>";

										echo "<select name='" . self::OPTION . "[form_fields][]' class='field_relations frm'>";
										foreach ( $form_fields as $value => $field ) {
											echo "<option value='{$value}' {$attribute}>{$field}</option>";
										}
										echo '</select>';

										echo '</td>' .
										"<td style='padding:0 .5em;text-align:center;vertical-align:baseline;'>→</td>" .
										"<td class='flex-row'>";
										echo "<select name='" . self::OPTION . "[field_assignment][]' class='user_fields' data-init-value=''>".
										"<option value='none' selected='selected'>" . __( 'Please select ', 'greyd_hub' ) . "</option>";
										echo "<optgroup label='" . __( "Standard user fields", 'greyd_hub' ) . "'>";
										foreach ( $this->default_user_relations as $value => $label ) {
											echo "<option value='{$value}'>{$label}</option>";
										}
										echo '</optgroup>';
										echo "<optgroup label='" . __( "User meta fields", 'greyd_hub' ) . "'>";
										if ( $form_mode == 'register_user' && isset( $meta_fields ) && is_array( $meta_fields ) ) {
											foreach ( $meta_fields as $meta_field ) {
												echo "<option value='{$meta_field['name']}' $attribute >{$meta_field['label']}</option>";
											}
										}
										echo '</optgroup>';
										echo '</select>';

										echo '<span class="button remove">✕</span></td></tr>';
									}
								endif; ?>
								<tr>
									<td colspan="3">
										<button class="button greyd add">+ <?php echo __( "Add field", 'greyd_hub' ); ?></button>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		// display warning when not all fields are set
		if ( $form_mode == 'register_user' ) {

			$field_relations_values = array_values( $field_relations );

			if (
				in_array( 'user_pass', $field_relations_values )
				&& in_array( 'user_mail', $field_relations_values )
				&& in_array( 'user_login', $field_relations_values )
			) {
				echo Helper::render_info_box(
					array(
						'style' => 'green',
						'text'  => __( "All required fields for successful user registration are set.", 'greyd_hub' ),
					)
				);
			} elseif (
				in_array( 'user_pass', $field_relations_values )
				&& in_array( 'user_mail', $field_relations_values )
			) {
				echo Helper::render_info_box(
					array(
						'style' => 'orange',
						'text'  => __( "The minimum required fields for a successful user registration are set, but no field for \"Username\" was created. The username is thus generated from the email, which can lead to errors and gives the user fewer options. We recommend you to assign an appropriate field.", 'greyd_hub' ),
					)
				);
			} else {
				echo Helper::render_info_box(
					array(
						'style' => 'red',
						'text'  => __( "Not all fields are set for successful user registration. You must have at least \"email\" and \"password\" fields assigned for users to register.", 'greyd_hub' ),
					)
				);
			}
		}
	}

	/**
	 * Render the mails metabox for formmode "user registration
	 *
	 * @param int $post_id  The form post ID.
	 */
	public function render_registration_mail( $post ) {

		$meta  = get_post_meta( $post->ID, self::OPTION, true );
		$pages = get_pages();

		if ( ! $meta ) {
			$meta = array();
		} elseif ( is_object( $meta ) ) {
			$meta = json_decode( json_encode( $meta ), true );
		}

		$mail_from_name = isset( $meta['registration_mail']['mail_from_name'] ) ? $meta['registration_mail']['mail_from_name'] : '';
		$mail_from      = isset( $meta['registration_mail']['mail_from'] ) ? $meta['registration_mail']['mail_from'] : '';
		$email          = isset( $meta['registration_mail']['email'] ) ? $meta['registration_mail']['email'] : '';
		$mail_subject   = isset( $meta['registration_mail']['mail_subject'] ) ? $meta['registration_mail']['mail_subject'] : '';
		$mail_content   = isset( $meta['registration_mail']['mail_content'] ) ? $meta['registration_mail']['mail_content'] : '';

		$verify_link      = isset( $meta['registration_mail']['verify_link'] ) ? $meta['registration_mail']['verify_link'] : '';
		$verify_link_name = isset( $meta['registration_mail']['verify_link_name'] ) ? $meta['registration_mail']['verify_link_name'] : '';

		// START RENDERING
		echo "<table class='form_meta_box'>";

		// Header
		echo '<tr>' .
				'<th colspan=2><div>' . __( "User registration email", 'greyd_hub' ) . '</div></th>' .
			'</tr>';

		// Zeile 1: Absender
		echo '<tr>';
			echo '<td>' .
					__( "Sender", 'greyd_hub' ) .
					'<small>' . __( "Which information do you want to display as the sender?", 'greyd_hub' ) . '</small>' .
					'</td>';
			echo "<td class='flex-row'>";
				echo "<input type='text' name='" . self::OPTION . "[registration_mail][mail_from_name]' value='$mail_from_name' placeholder='" . __( 'Name', 'greyd_hub' ) . "'>";
				echo "<input type='text' name='" . self::OPTION . "[registration_mail][mail_from]' value='$mail_from' placeholder='" . __( "Email address", 'greyd_hub' ) . "'>";
			echo '</td>';
		echo '</tr>';

		// Zeile 3: subject
		echo '<tr>';
			echo '<td>' .
					__( "Subject", 'greyd_hub' ) . '</td>';
			echo '<td>';
				echo "<input type='text' name='" . self::OPTION . "[registration_mail][mail_subject]' value='$mail_subject' placeholder='" . __( "Enter the subject of the email here.", 'greyd_hub' ) . "'>";
			echo '</td>';
		echo '</tr>';

		// Zeile 4: content
		echo '<tr>';
			echo '<td>' .
					__( "Content", 'greyd_hub' ) .
					"<span class='small'>" . __( "Dynamic elements", 'greyd_hub' ) . ':';
						echo '<br>[registration_link]';
					echo '</span>' .
					'</td>';
			echo '<td>';
				wp_editor(
					htmlspecialchars_decode( $mail_content ),
					'mail_content',
					$settings = array(
						'textarea_name' => self::OPTION . '[registration_mail][mail_content]',
						'editor_height' => 200,
						'media_buttons' => true,
					)
				);
				echo "<i class='info'>" . __( "Enter the contents of the email here. A confirmation link is automatically sent along. If you want to include dynamic form elements, copy them from the left and paste them into your email text.", 'greyd_hub' ) . '</i>';
			echo '</td>';
		echo '</tr>';

		// Zeile 5: Link
		echo '<tr>';
			echo '<td>' .
					__( "Verification link", 'greyd_hub' ) .
					'<small>' . __( "Where should the user be directed after clicking on the confirmation link?", 'greyd_hub' ) . '</small>' .
					'</td>';
			echo "<td><div class='flex-row'>";

				$is_custom_verify_link = ! empty( $verify_link ) && ! is_numeric( $verify_link );
				echo "<select name='" . self::OPTION . "[registration_mail][verify_link]'>";
					echo "<option value='--'>" . __( "Select page", 'greyd_hub' ) . '</option>';
		foreach ( $pages as $page ) {
			$s = $verify_link == $page->ID ? 'selected="selected"' : '';
			echo "<option value='$page->ID' $s> $page->post_title</option>";
		}
					$s = $is_custom_verify_link ? 'selected="selected"' : '';
					echo "<option value='custom' $s> " . __( "Individual URL", 'greyd_hub' ) . '</option>';
				echo '</select>';
				echo "<input class='" . ( $is_custom_verify_link ? '' : 'hidden' ) . "' type='text' name='" . self::OPTION . "[registration_mail][verify_link_custom]' value='" . ( $is_custom_verify_link ? $verify_link : \Greyd\Helper::get_home_url() ) . "' placeholder='" . __( "Individual URL", 'greyd_hub' ) . "'>";
				echo "<input type='text' name='" . self::OPTION . "[registration_mail][verify_link_name]' value='$verify_link_name' placeholder='" . __( "Link text", 'greyd_hub' ) . "'>";
			echo '</div>';
			echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

	/**
	 * Save and rearrange meta data
	 *
	 * @param array $post_args postargs of the post
	 */
	function save_metabox( $post_args ) {
		$data    = isset( $post_args['data'] ) ? $post_args['data'] : array();
		$post_id = isset( $post_args['post_id'] ) ? $post_args['post_id'] : array();

		$form_mode = isset( $data['form_mode'] ) ? $data['form_mode'] : 'normal';

		$raw_settings = isset( $data[ self::OPTION ] ) ? $data[ self::OPTION ] : array();
		$role         = isset( $raw_settings['role'] ) ? $raw_settings['role'] : 'subscriber';

		$form_fields      = isset( $raw_settings['form_fields'] ) && is_array($raw_settings['form_fields']) ? $raw_settings['form_fields'] : array();
		$field_assignment = isset( $raw_settings['field_assignment'] ) && is_array($raw_settings['field_assignment']) ? $raw_settings['field_assignment'] : array();

		// filter all empty value and "--", "none" etc. from the arrays
		$form_fields = array_filter( $form_fields, function( $value ) {
			return ! empty( $value ) && $value !== 'none' && $value !== '--';
		} );
		$field_assignment = array_filter( $field_assignment, function( $value ) {
			return ! empty( $value ) && $value !== 'none' && $value !== '--';
		} );

		// if the arrays have different lengths, return
		// otherwise 'array_combine' will throw a fatal error
		if ( count( $form_fields ) !== count( $field_assignment ) ) {
			return;
		}
		
		$field_relations  = array_combine( $form_fields, $field_assignment );

		$registration_mail = isset( $raw_settings['registration_mail'] ) ? $raw_settings['registration_mail'] : array();

		$settings['field_relations']   = $field_relations;
		$settings['role']              = $role;
		$settings['registration_mail'] = $registration_mail;

		update_post_meta( $post_id, self::OPTION, $settings );
	}

	/*
	=======================================================================

						REGISTRATION / UPDATE FORM HANDLER

	=======================================================================
	*/

	/**
	 * handles the process of creating a new user through a frontend form
	 *
	 * @param int    $post_id    The form post ID
	 * @param array  $data       The data of the form
	 * @param string $host       ip of the user
	 * @param string $form_mode  form mode of the form
	 */
	function handle_create_new_user_form( $post_id, $data, $host, $form_mode ) {

		if ( $form_mode !== 'register_user' ) {
			return;
		}

		$post_id         = isset( $post_id ) ? $post_id : 0;
		$data            = isset( $data ) ? $data : null;
		$settings        = get_post_meta( $post_id, self::OPTION, true );
		$field_relations = isset( $settings['field_relations'] ) ? $settings['field_relations'] : null;
		$token           = wp_create_nonce( 'greyd_tp_forms_' . $post_id . $host );

		$user_data = array();

		if ( is_array( $data ) ) {
			foreach ( $data as $field_name => $value ) {

				if ( array_key_exists( $field_name, $field_relations ) ) {
					if ( array_key_exists( $field_relations[ $field_name ], $this->default_user_relations ) ) {
						// Standard User Fields
						$user_data[ $field_relations[ $field_name ] ] = $value;
					} else {
						// User Metafields
						$user_data['user_meta'][ $field_relations[ $field_name ] ] = $value;
					}
				}
			}
		}

		if ( ! isset( $user_data['user_pass'] ) || ! isset( $user_data['user_mail'] ) ) {
			do_action( 'formhandler_error', __( "The field assignments were not set completely", 'greyd_hub' ) );
		}

		if ( ! isset( $user_data['user_login'] ) ) {
			$user_data['user_login'] = str_replace( '@', '-', $user_data['user_mail'] );
		}

		// do_action( 'formhandler_error', $user_data );

		// Separate Data
		$default_newuser = array(
			'user_pass'   => $user_data['user_pass'], // wp_hash_password( $user_data['user_pass'] ),
			'user_login'  => $user_data['user_login'],
			'user_email'  => $user_data['user_mail'],
			'first_name'  => isset( $user_data['first_name'] ) ? $user_data['first_name'] : '',
			'last_name'   => isset( $user_data['last_name'] ) ? $user_data['last_name'] : '',
			'description' => isset( $user_data['description'] ) ? $user_data['description'] : '',
			'role'        => 'pending',
		);

		// do_action( 'formhandler_error', $default_newuser );

		// create the user
		$user_id = wp_insert_user( $default_newuser );

		// unset the password here because we don't need it anymore
		unset( $user_data['user_pass'] );

		// error handling
		if ( is_wp_error( $user_id ) ) {
			var_error_log( $user_id );
			var_error_log( $user_id->get_error_message() );
			do_action( 'formhandler_error', $user_id->get_error_message() );
		}
		else if ( ! $user_id ) {
			do_action( 'formhandler_error', __( "An unknown error has occurred during registration.", 'greyd_hub' ) );
		}

		// add user meta fields
		if ( isset( $user_data['user_meta'] ) && is_array( $user_data['user_meta'] ) ) {
			foreach ( $user_data['user_meta'] as $meta_field => $value ) {
				update_user_meta( $user_id, $meta_field, $value );
			}
		}

		add_user_meta( $user_id, 'not_confirmed', $token, true );
		add_user_meta( $user_id, 'role_if_confirmed', $settings['role'], true );

		// send the confirmation mail
		$result = $this->send_confirmation_mail( $post_id, $user_data, $token, $user_id );

		if ( $result !== true ) {
			$msg = __( "The verification mail could not be sent.", 'greyd_hub' );
			if ( is_wp_error( $result ) ) {
				$msg .= ' ' . $result->get_error_message();
			}
			do_action( 'formhandler_error', $msg );
		}

		// success
		do_action(
			'formhandler_success',
			array(
				'message' => __( "User created. A confirmation link was sent by email.", 'greyd_hub' ),
			)
		);
	}

	/**
	 * handles the process of updating an existing user through a frontend form
	 *
	 * @param int    $post_id    The form post ID
	 * @param array  $data       The data of the form
	 * @param string $host       ip of the user
	 * @param string $form_mode  form mode of the form
	 */
	function handle_update_user_form( $post_id, $data, $host, $form_mode ) {

		if ( $form_mode !== 'update_user' ) {
			return;
		}

		// get the current user
		$current_user = wp_get_current_user();
		if ( ! $current_user || ! $current_user->exists() ) {
			$msg = __( "The required rights to update the user profile are missing", 'greyd_hub' );
			do_action( 'formhandler_error', $msg );
		}

		// get the settings
		$user_data       = array();
		$post_id         = isset( $post_id ) ? $post_id : 0;
		$data            = isset( $data ) ? $data : null;
		$settings        = get_post_meta( $post_id, self::OPTION, true );
		$field_relations = isset( $settings['field_relations'] ) ? $settings['field_relations'] : null;

		$update_allowed = $current_user;
		if ( ! empty( $settings['role'] ) && ! in_array( $settings['role'], (array) $current_user->roles ) ) {
			$update_allowed = false;
		}

		if ( ! $update_allowed ) {
			$msg = __( "The required rights to update the user profile are missing", 'greyd_hub' );
			do_action( 'formhandler_error', $msg );
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $field_name => $value ) {

				if ( array_key_exists( $field_name, $field_relations ) ) {
					if ( array_key_exists( $field_relations[ $field_name ], $this->default_user_relations ) ) {
						// Standard User Fields
						$user_data[ $field_relations[ $field_name ] ] = $value;
					} else {
						// User Metafields
						if ( $field_relations[ $field_name ] == 'none' ) {
							continue;
						}
						$user_data['user_meta'][ $field_relations[ $field_name ] ] = $value;
					}
				}
			}
		}

		// first we update the user meta fields
		if ( isset( $user_data['user_meta'] ) ) {
			foreach ( $user_data['user_meta'] as $meta_field => $value ) {
				$metadata = update_user_meta( $current_user->ID, $meta_field, $value );

				if ( is_wp_error( $metadata ) ) {
					$msg = $metadata->get_error_message();
					do_action( 'formhandler_log', 'Fehler beim aktualisieren der Nutzer Metadaten des Feldes "' . $meta_field . '": ' . $msg );
				}
				elseif ( ! $metadata ) {
					do_action( 'formhandler_log', 'Fehler beim aktualisieren der Nutzer Metadaten des Feldes "' . $meta_field . '".' );
				}
				else {
					do_action( 'formhandler_log', 'Nutzer Metadaten des Feldes "' . $meta_field . '" aktualisiert' );
				}
			}
		}

		// then we update the user data, his has to be done after the
		// user meta fields because if the password is updated, the
		// user will be logged out
		$new_user_data = array( 'ID' => $current_user->ID );

		if ( isset( $user_data['user_pass'] ) ) {
			$new_user_data['user_pass'] = $user_data['user_pass']; // wp_hash_password( $user_data['user_pass'] );
		}
		if ( isset( $user_data['user_mail'] ) ) {
			$new_user_data['user_email'] = $user_data['user_mail'];
		}
		if ( isset( $user_data['first_name'] ) ) {
			$new_user_data['first_name'] = $user_data['first_name'];
		}
		if ( isset( $user_data['last_name'] ) ) {
			$new_user_data['last_name'] = $user_data['last_name'];
		}
		if ( isset( $user_data['description'] ) ) {
			$new_user_data['description'] = $user_data['description'];
		}

		$result = wp_update_user( $new_user_data );

		if ( is_wp_error( $result ) ) {
			$msg = $result->get_error_message();
			do_action( 'formhandler_error', $msg );
		}
		else if ( ! $result ) {
			do_action( 'formhandler_error', __( "An unknown error occurred during the update.", 'greyd_hub' ) );
		}
		else {
			do_action(
				'formhandler_success',
				array(
					'message' => __( "User profile updated", 'greyd_hub' ),
				)
			);
		}
	}

	/**
	 * handle url-params from mail link
	 */
	public function handle_confirmation_link() {
		// early exit
		if ( ! isset( $_GET['token'] ) && ! isset( $_GET['user_id'] ) ) {
			return false;
		}

		// $token = isset($_GET['token']) ? filter_input($_GET['token']) : (isset($_GET['optin']) ? $_GET['optin'] : '');
		$user_id = filter_input( INPUT_GET, 'user_id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

		if ( $user_id ) {
			// get user meta confirmation token field
			$token = get_user_meta( $user_id, 'not_confirmed', true );
			$role  = get_user_meta( $user_id, 'role_if_confirmed', true );

			if ( isset( $token ) && $token == filter_input( INPUT_GET, 'token' ) ) {
				$result = delete_user_meta( $user_id, 'not_confirmed', $token );
				if ( $result ) {
					delete_user_meta( $user_id, 'role_if_confirmed', $role );

					$u = new \WP_User( $user_id );
					// Remove pending role
					$u->remove_role( 'pending' );
					// Add subscriber role
					$u->add_role( $role );
				}
			} else {
				// already confirmed
			}
		}
		// redirect after
		if ( isset( $_GET['forms_redirect'] ) ) {
			wp_redirect( urldecode( $_GET['forms_redirect'] ) );
			exit;
		}
	}

	/**
	 * send the confirmation mail with the registration link
	 */
	function send_confirmation_mail( $post_id, $data, $token, $user_id ) {

		// Mails atts
		if ( ! isset( $data['user_mail'] ) ) {
			return false;
		} else {
			$mail_to = $data['user_mail'];
		}

		$registration_link = '<br><br>' . self::build_registration_link( $post_id, null, $token, $user_id );
		$meta              = get_post_meta( $post_id, self::OPTION, true );
		$registration_mail = $meta['registration_mail'];
		$content           = $registration_mail['mail_content'];

		// content
		if ( ! empty( $content ) ) {
			$content = \Greyd\Forms\Helper::replace_form_data_tags( $content, $data, $post_id, $token, $user_id );
			$content = html_entity_decode( nl2br( $content ) );
		} else {
			$content = __( "Thank you very much.<br><br>Please click on the following link to confirm your identity:", 'greyd_hub' ) . $registration_link;
		}

		/**
		 * Set 'From:' inside headers
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_mail/
		 */
		$headers        = array();
		$subject        = $registration_mail['mail_subject'];
		$subject        = ! empty( $subject ) ? $subject : __( "Verify your identity", 'greyd_hub' );
		$mail_from      = $registration_mail['mail_from'];
		$mail_from_name = $registration_mail['mail_from_name'];

		if ( empty( $mail_from ) ) {
			$sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
			if ( 'www.' === substr( $sitename, 0, 4 ) ) {
				$sitename = substr( $sitename, 4 );
			}

			$mail_from = "wordpress@{$sitename}";
		}
		if ( empty( $mail_from_name ) ) {
			$headers[] = "From: $mail_from";
		} else {
			$headers[] = "From: $mail_from_name <$mail_from>\n";
		}

		// set filter
		add_filter( 'wp_mail_content_type', array( 'Greyd\Forms\Helper', 'wpdocs_set_html_mail_content_type' ) );
		add_filter( 'wp_mail_charset', array( $this, 'utf8' ) );
		add_action( 'wp_mail_failed', array( $this, 'on_mail_error' ) );

		// send Mail
		$return = wp_mail( $mail_to, $subject, $content, $headers );

		if ( count( $this->mail_errors ) ) {
			return $this->mail_errors[0];
		}

		// Reset content-type to avoid conflicts -- https://core.trac.wordpress.org/ticket/23578
		remove_filter( 'wp_mail_content_type', array( 'Greyd\Forms\Helper', 'wpdocs_set_html_mail_content_type' ) );
		remove_filter( 'wp_mail_charset', array( $this, 'utf8' ) );
		remove_filter( 'wp_mail_failed', array( $this, 'on_mail_error' ) );

		return $return;
	}

	/**
	 * Build the option link
	 *
	 * @param string $post_id   WP_Post ID
	 * @param array  $data       Form data with field values
	 * @param string $token     Entry token
	 *
	 * @return string           <a href="...">...</a>
	 */
	public static function build_registration_link( $post_id, $data, $token = '', $user_id = '' ) {

		// get url dynamically
		$meta             = get_post_meta( $post_id, self::OPTION, true );
		$verify_link      = isset( $meta['registration_mail']['verify_link'] ) ? $meta['registration_mail']['verify_link'] : '';
		$verify_link_name = isset( $meta['registration_mail']['verify_link_name'] ) ? $meta['registration_mail']['verify_link_name'] : '';
		$url              = \Greyd\Forms\Helper::get_url_from_post_meta( $verify_link );

		$args = array(
			'token' => $token,
			'form'  => $post_id,
			'user_id',
		);

		if ( isset( $user_id ) ) {
			$args['user_id'] = $user_id;
		}

		$href              = add_query_arg( $args, \Greyd\Forms\Helper::add_url_params_to_link( $url, $data ) );
		$link_name         = ! empty( $verify_link_name ) ? $verify_link_name : _x( "Confirm email", 'small', 'greyd_hub' );
		$registration_link = "<a href='$href' color><span><font>$link_name</font></span></a>";

		return $registration_link;
	}

	/**
	 * handles and checks logins of unconfirmed users
	 *
	 * @param string $username  user of the user logging in
	 */
	function is_user_confirmed( $username ) {
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			$user = get_user_by( 'email', $username );
			if ( ! $user ) {
				return $username;
			}
		}
		// "not confirmed" - page here
		$login_page = home_url( '/login/' );
		if ( get_user_meta( $user->ID, 'not_confirmed', true ) != false ) {
			wp_redirect( $login_page . '?login=failed' );
			exit;
		}
	}

	function add_pending_role() {
		if ( get_option( 'pending_role_version' ) < 1 ) {
			add_role(
				'pending',
				__( "Not verified", 'greyd_hub' ),
				array(
					'read'    => true,
					'level_0' => true,
				)
			);
			update_option( 'pending_role_version', 1 );
		}
	}

	/**
	 * Holds all mail errors
	 */
	public $mail_errors = array();

	public function utf8() {
		return 'UTF-8';
	}

	/**
	 * on wp_mail() error event
	 */
	public function on_mail_error( $wp_error ) {
		$this->mail_errors[] = $wp_error;
	}

	/*
	=======================================================================

						Frontend

	=======================================================================
	*/

	/**
	 * Holds the form id
	 *
	 * @var int
	 */
	public $form_id = 0;

	public $field_relations = array();

	/**
	 * Fill the form fields with related user data.
	 *
	 * @param string $block_content     pre-rendered Block Content
	 * @param object $block             parsed Block
	 *
	 * @return string $block_content    altered Block Content
	 */
	public function render_block( $block_content, $block ) {

		if ( ! isset( $block['blockName'] ) || empty( $block['blockName'] ) ) {
			return $block_content;
		}

		// only for our form blocks (namespace)
		if ( strpos( $block['blockName'], 'greyd-forms/' ) !== 0 ) {
			return $block_content;
		}

		// only if we have a form id and field relations
		if ( empty( $this->form_id ) || empty( $this->field_relations ) ) {
			return $block_content;
		}

		$field_name = isset( $block['attrs']['name'] ) ? $block['attrs']['name'] : '';
		$relation   = isset( $this->field_relations[ $field_name ] ) ? $this->field_relations[ $field_name ] : '';
		$relation   = $relation === 'user_mail' ? 'user_email' : $relation;

		/**
		 * Do not alter the content if the relation is not set.
		 * Also password fields should not be altered, as WordPress uses a
		 * one way encryption to store the passwords using a variation of md5.
		 * There is no way to reverse this.
		 * 
		 * @since 1.8.4
		 */
		if ( $relation === 'none' || empty( $relation ) || $relation === 'user_pass' ) {
			return $block_content;
		}

		$value = '';
		$user  = wp_get_current_user();

		if ( ! $user || ! isset( $user->data ) ) {
			return $block_content;
		}

		// default user fields
		if ( isset( $user->data->$relation ) ) {
			$value = $user->data->$relation;
		}
		// user meta
		elseif ( ! empty( $relation ) && $relation !== 'none' ) {
			$value = get_user_meta( $user->ID, $relation, true );
		}

		// Handle checkbox values
		if ( strpos( $value, '[on]' ) !== false ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		//debug($block);

		if ( ! empty( $value ) ) {
			if ( $block['blockName'] === 'greyd-forms/checkbox' || $block['blockName'] === 'greyd-forms/radiobutton' ) {
				$block_content = preg_replace(
					'/(<input[^>]*name="' . $field_name . '"[^>]*)(>)/',
					'$1 ' . $checked . '$2',
					$block_content
				);
			} else {
				$block_content = preg_replace(
					'/(name="' . $field_name . '")/',
					'$1 value="' . esc_attr( $value ) . '"',
					$block_content
				);
			}
		}

		return $block_content;
	}

	/**
	 * Filter the form content based on the user state.
	 */
	public function filter_form_content( $content, $post ) {

		$form_mode = get_post_meta( $post->ID, 'form_mode', true );

		if ( 'update_user' === $form_mode ) {

			if ( ! is_user_logged_in() ) {
				return Helper::show_frontend_message( __( "You must be logged in to edit your user data.", 'greyd_hub' ) );
			}

			$this->form_id = $post->ID;
	
			$settings        = get_post_meta( $post->ID, self::OPTION, true );
			$field_relations = isset( $settings['field_relations'] ) ? $settings['field_relations'] : array();
	
			$this->field_relations = $field_relations;
		}
		else if ( 'register_user' === $form_mode ) {

			if ( is_user_logged_in() ) {
				return Helper::show_frontend_message( __( "You are already logged in.", 'greyd_hub' ) );
			}
		}

		return $content;
	}
}
