<?php
/**
 * Admin page to manage worpress mails
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mails_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( !Manage::wp_up_to_date() ) {
			return;
		}

	}

	/*
	=======================================================================
		Testmail
	=======================================================================
	*/

	/**
	 * Ajax function to preview or send a Test Email.
	 * Called from user-management.php when ajax call happens.
	 *
	 * @see action 'greyd_ajax_mode_'
	 *
	 * @param array $post_data   $_POST data.
	 */
	public function send_testmail( $post_data ) {

		$mode      = $post_data['mode'];
		$type      = $post_data['type'];
		$mail_slug = $post_data['testmail'];

		// debug
		echo "\n\n" . $type . ' testmail: ' . $mail_slug . "\n\n";

		$return = '';
		if ( isset( $this->mail_slugs[ $type ] ) &&
			in_array( $mail_slug, $this->mail_slugs[ $type ] ) &&
			( function_exists( $mail_slug ) || method_exists( $this, $mail_slug ) ) ) {

			$testmode = $post_data['testmode'];
			if ( $testmode == 'preview' ) {
				// get testmail preview

				add_action(
					'phpmailer_init',
					function( $phpmailer ) {

						// debug($_POST["data"]["testmail"]);
						$url  = 'https://developer.wordpress.org/reference/functions/' . $_POST['data']['testmail'] . '/';
						$test = @get_headers( $url );

						// debug($phpmailer);
						$data = array(
							'from'      => $phpmailer->From,
							'from_name' => $phpmailer->FromName,
							'to'        => $phpmailer->getToAddresses(),
							'cc'        => $phpmailer->getCcAddresses(),
							'bcc'       => $phpmailer->getBccAddresses(),
							'subject'   => $phpmailer->Subject,
							'message'   => $phpmailer->Body,
							'dev_link'  => $test[0],
						);
						// send output data
						echo json_encode( $data );

						// abort mailer
						$phpmailer->ClearAllRecipients();

					},
					9999
				);

			}
			if ( $testmode == 'send' ) {
				// send the testmail and check if it succeeded

				add_action(
					'phpmailer_init',
					function( $phpmailer ) {

						// debug($phpmailer);
						$mode = $_POST['data']['mode'];
						if ( $mode == 'site' ) {
							$testaddress = $this->get_value( 'testmail', 'to' );
						} else {
							$testaddress = $this->get_global_value( 'testmail', 'to' );
						}
						if ( ! empty( $testaddress ) ) {
							// clear
							$phpmailer->ClearAllRecipients();
							// add single mail to
							$phpmailer->addAddress( $testaddress, 'Tester' );
						}
						// set debug mode
						$phpmailer->SMTPDebug   = 1;
						$phpmailer->Debugoutput = 'array';

					},
					9999
				);

				// after sent
				add_action(
					'wp_mail_succeeded',
					function( $data ) {
						// debug('wp_mail_succeeded');
						// debug($data);
						echo 'success';
					}
				);
				add_action(
					'wp_mail_failed',
					function( $error ) {
						// debug('wp_mail_failed');
						// debug($error);
						echo 'debug::' . htmlentities( $error->errors['wp_mail_failed'][0] );
					}
				);

			}

			// call function that renders the mail and get output
			ob_start();
			$this->call_mail_function( $mode, $type, $mail_slug );
			$return = ob_get_contents();
			ob_end_clean();

		}

		echo $return;
		echo "\n\n" . '------------- debug end -------------' . "\n\n";

		// return
		if ( $return == '' ) {
			// debug('wp_mail_failed');
			echo 'error::';
		} else {
			if ( strpos( $return, '<pre>' ) !== false ) {
				// remove debugs
				$return = preg_replace( '/<pre>(.|\n)*<\/pre>/', '', $return );
			}
			if ( strpos( $return, 'SMTP Error:' ) !== false ) {
				echo 'error::' . $return;
			} else {
				echo 'success::' . $return;
			}
		}

		// end ajax request
		die();

	}

	/**
	 * Send a Testmail with all current settings.
	 * Useful to debug SMTP setup and email addresses.
	 * Called via ajax 'send_testmail' and 'call_mail_function'.
	 *
	 * @param string  $mode      Render mode "site" or "global".
	 * @param WP_User $user     User object (Current User or Testuser - Reciever of Testmail)
	 * @return void             Sends Testmail via wp_mail.
	 */
	public function settings_test_mail( $mode, $user ) {

		// get settings
		if ( $mode == 'site' ) {
			$smtp_settings = Manage::get_merged_settings( 'smtp' );
			$settings      = Manage::get_merged_settings( 'mails' );
		} else {
			$smtp_settings = Manage::get_settings( 'smtp', 'global' );
			$settings      = Manage::get_settings( 'mails', 'global' );
		}

		// render smtp settings
		$message = __( 'SMTP settings:', 'greyd_hub' );
		$list    = array(
			__( 'Host', 'greyd_hub' ) . ': ' . $smtp_settings['host'],
			__( 'Port', 'greyd_hub' ) . ': ' . $smtp_settings['port'],
		);
		if ( $smtp_settings['auth'] ) {
			$list[] = __( 'Authentification', 'greyd_hub' ) . ': ' . $smtp_settings['username'] . ' / ' . str_repeat( '●', strlen( $smtp_settings['password'] ) );
		} else {
			$list[] = __( 'Authentification: off', 'greyd_hub' );
		}
		if ( $smtp_settings['encryption'] ) {
			$list[] = __( 'Encryption', 'greyd_hub' ) . ': ' . $smtp_settings['encryption'];
		} else {
			$list[] = __( 'Encryption: off', 'greyd_hub' );
		}
		$message .= "\n\n- " . implode( "\n- ", $list ) . "\n\n";

		// render mail settings
		$message .= __( 'Email sender:', 'greyd_hub' );
		$list     = array(
			__( 'Name', 'greyd_hub' ) . ': ' . htmlentities( $settings['from']['name'] ),
			__( 'Address', 'greyd_hub' ) . ': ' . htmlentities( $settings['from']['address'] ),
		);
		$message .= "\n\n- " . implode( "\n- ", $list ) . "\n\n";

		$message .= __( 'Receiver of user emails:', 'greyd_hub' );
		$list     = array(
			__( 'CC', 'greyd_hub' ) . ': ' . htmlentities( $settings['user_to']['cc'] ),
			__( 'BCC', 'greyd_hub' ) . ': ' . htmlentities( $settings['user_to']['bcc'] ),
		);
		$message .= "\n\n- " . implode( "\n- ", $list ) . "\n\n";

		$message .= __( 'Receiver of admin emails:', 'greyd_hub' );
		$list     = array(
			__( 'To', 'greyd_hub' ) . ': ' . htmlentities( $settings['admin_to']['to'] ),
			__( 'CC', 'greyd_hub' ) . ': ' . htmlentities( $settings['admin_to']['cc'] ),
			__( 'BCC', 'greyd_hub' ) . ': ' . htmlentities( $settings['admin_to']['bcc'] ),
		);
		$message .= "\n\n- " . implode( "\n- ", $list ) . "\n\n";

		// mail setup
		$blogname   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$smtp_email = array(
			'to'      => $user->user_email,
			'subject' => sprintf( __( '[%s] test email' ), $blogname ),
			'message' => $message,
			'headers' => '',
		);

		// send mail
		wp_mail(
			$smtp_email['to'],
			$smtp_email['subject'],
			$smtp_email['message'],
			$smtp_email['headers']
		);

	}

	/**
	 * Add overlay contents for testmail.
	 *
	 * @see filter 'greyd_overlay_contents'
	 *
	 * @param array $contents
	 * @return array $contents
	 */
	public function add_overlay_contents( $contents ) {

		$overlay_contents = array(
			// preview
			'previewmail' => array(
				'loading'  => array(
					'title' => __( 'Loading email preview', 'greyd_hub' ),
					// 'descr'     => __("...", 'greyd_hub')
				),
				'decision' => array(
					'title'   => __( 'Email preview', 'greyd_hub' ),
					'content' => "<p id='testmail_info'></p>
									<div id='testmail_preview'>
										<div id='testmail_head'>
											<big><strong id='preview_subject'></strong></big><br><br>
											<div><strong class='label'>From:</strong><span id='preview_from'></span></div>
											<div><strong class='label'>To:</strong><span id='preview_to'></span></div>
											<small><div><strong class='label'>Cc:</strong><span id='preview_cc'></span></div></small>
											<small><div><strong class='label'>Bcc:</strong><span id='preview_bcc'></span></div></small>
										</div>
										<div id='testmail_body'>
											<span id='preview_message'></span>
										</div>
									</div>",
					'button1' => __( 'Close', 'greyd_hub' ),
					'button2' => __( 'Send test email', 'greyd_hub' ),
				),
				'fail'     => array(
					'title' => __( 'Email preview failed!', 'greyd_hub' ),
					'descr' => __( 'Some emails can not be previewed or sent as a test by calling a function.', 'greyd_hub' ),
				),
			),
			// send
			'testmail'    => array(
				'loading' => array(
					'title' => __( 'Sending test email', 'greyd_hub' ),
					// 'descr'     => __("...", 'greyd_hub')
				),
				'success' => array(
					'title' => __( 'Test email sent!', 'greyd_hub' ),
					'descr' => __( 'Check your inbox to see if it arrived correctly.', 'greyd_hub' ),
				),
				'fail'    => array(
					'title' => __( 'Email sending failed!', 'greyd_hub' ),
					'descr' => '<span class="replace"></span>',
				),
			),
		);

		return array_merge(
			$contents,
			$overlay_contents
		);
	}

	/*
	=======================================================================
		Data
	=======================================================================
	*/

	/**
	 * Array of available Mails (slugs).
	 */
	private $mail_slugs = array(
		'user'  => array(
			'settings_test_mail',
			'wp_new_user_notification',
			'invited_user_email',
			'send_confirmation_on_profile_email',
			'wp_update_user_email',
			'retrieve_password',
			'wp_update_user_password',
			// multisite
			'wpmu_signup_user_notification',
			'wpmu_welcome_user_notification',
			'wpmu_signup_blog_notification',
			'wpmu_welcome_notification',
		),
		'admin' => array(
			'wp_new_user_notification',
			'wp_password_change_notification',
			'update_option_new_admin_email',
			'wp_site_admin_email_change_notification',
			// wp installed
			'wp_new_blog_notification',
			// multisite
			'newuser_notify_siteadmin',
			'wpmu_new_site_admin_notification',
			'newblog_notify_siteadmin',
			'delete_site_email_content',
			'update_network_option_new_admin_email',
			'wp_network_admin_email_change_notification',
		),
	);

	/**
	 * Get saved settings value.
	 *
	 * @param string      $type      first level.
	 * @param string|bool $slug second level or false.
	 * @param string|bool $key  third level or false.
	 * @param string|bool $mode site, global or false.
	 * @return string $value
	 */
	private function get_value( $type, $slug = false, $key = false, $mode = false ) {

		// get settings
		$mails = Manage::get_settings( 'mails', $mode );

		if ( $slug && $key && isset( $mails[ $type ][ $slug ][ $key ] ) ) {
			// debug('- '.$type.' '.$slug.' '.$key.':');
			// debug($mails[$type][$slug][$key]);
			return $mails[ $type ][ $slug ][ $key ];
		}
		if ( $slug && ! $key && isset( $mails[ $type ][ $slug ] ) ) {
			return $mails[ $type ][ $slug ];
		}
		if ( ! $slug && ! $key && isset( $mails[ $type ] ) ) {
			return $mails[ $type ];
		}

		return '';
	}

	/**
	 * Get saved global settings value.
	 * Calls get_value function with $mode = "global"
	 *
	 * @return string $value
	 */
	private function get_global_value( $type, $slug = false, $key = false ) {
		return $this->get_value( $type, $slug, $key, 'global' );
	}

	/**
	 * Get all mail settings, details, strings, values and defaults for rendering.
	 *
	 * @param string $type  Type (first level).
	 * @param string $slug  Slug (second level).
	 * @param array  $args   Additional values for defaults and rendering.
	 * @return mixed|bool $values    Mixed array of defaults or false.
	 */
	private function get_default_value( $type, $slug, $args = array() ) {

		/*
		 todo: emails from core:

		  -- multisite
			\wp-includes\ms-functions.php
			  -- new site (multisite)
					// Sends a confirmation request email to a user when they sign up for a new site. The new site will not become active until the confirmation link is clicked.
					apply_filters( 'wpmu_signup_blog_notification_email', ... )
					apply_filters( 'wpmu_signup_blog_notification_subject', ... )
					// Notifies the site administrator that their site activation was successful.
					apply_filters( 'update_welcome_email', ... )
					apply_filters( 'update_welcome_subject', ... )
					// Notifies the network admin that a new site has been activated.
					apply_filters( 'newblog_notify_siteadmin', ... )
					// Notifies the Multisite network administrator that a new site was created.
					apply_filters( 'new_site_email', ... )
			  -- new user (multisite)
					// Sends a confirmation request email to a user when they sign up for a new user account (without signing up for a site at the same time). The user account will not become active until the confirmation link is clicked.
					apply_filters( 'wpmu_signup_user_notification_email', ... )
					apply_filters( 'wpmu_signup_user_notification_subject', ... )
					// Notifies a user that their account activation has been successful.
					apply_filters( 'update_welcome_user_email', ... )
					apply_filters( 'update_welcome_user_subject', ... )
					// Notifies the network admin that a new user has been activated.
					apply_filters( 'newuser_notify_siteadmin', ... )
			  -- change network admin email (multisite)
					// Sends a confirmation request email when a change of network admin email address is attempted.
					apply_filters( 'new_network_admin_email_content', ... )
					// Sends an email to the old network admin email address when the network admin email address changes.
					apply_filters( 'network_admin_email_change_email', ... )

			\wp-admin\ms-delete-site.php
			  -- delete site (multisite)
					// Filters the text for the email sent to the site admin when a request to delete a site in a Multisite network is submitted.
					apply_filters( 'delete_site_email_content', ... )

		  -- singlesite
			  -- change admin email
			\wp-admin\includes\misc.php
					// Sends a confirmation request email when a change of site admin email address is attempted.
					apply_filters( 'new_admin_email_content', ... )
			\wp-includes\functions.php
					// Send an email to the old site admin email address when the site admin email address changes.
					apply_filters( 'site_admin_email_change_email', ... )

			\wp-includes\pluggable.php
			  -- comments
				// Notify an author (and/or others) of a comment/trackback/pingback on a post.
				apply_filters( 'comment_notification_recipients', ... )
				apply_filters( 'comment_notification_notify_author', ... )
				apply_filters( 'comment_notification_text', ... )
				apply_filters( 'comment_notification_subject', ... )
				// Notifies the moderator of the site about a new comment that is awaiting approval.
				apply_filters( 'comment_moderation_recipients', ... )
				apply_filters( 'comment_moderation_text', ... )
				apply_filters( 'comment_moderation_subject', ... )
			  -- new user
					// Email login credentials to a newly-registered user.
					apply_filters( 'wp_new_user_notification_email', ... )
					// A new user registration notification is also sent to admin email.
					apply_filters( 'wp_new_user_notification_email_admin', ... )
			  -- change password
					// Notify the blog admin of a user changing password, normally via email.
					apply_filters( 'wp_password_change_notification_email', ... )

			\wp-admin\user-new.php
			  -- new user
					// Filters the contents of the email sent when an existing user is invited to join the site.
					apply_filters( 'invited_user_email', ... )

			\wp-includes\user.php
			  -- change password
					// Filters the contents of the email sent when the user's password is changed.
					apply_filters( 'password_change_email', ... )
			  -- recover password
					// Handles sending a password retrieval email to a user.
					apply_filters( 'retrieve_password_notification_email', ... )
			  -- change email
					// Sends a confirmation request email when a change of user email address is attempted.
					apply_filters( 'new_user_email_content', ... )
					// Filters the contents of the email sent when the user's email is changed.
					apply_filters( 'email_change_email', ... )
			  -- action request
				// Notifies the site administrator via email when a request is confirmed.
				apply_filters( 'user_request_confirmed_email_to', ... )
				apply_filters( 'user_request_confirmed_email_subject', ... )
				apply_filters( 'user_request_confirmed_email_content', ... )
				// Notifies the user when their erasure request is fulfilled.
				apply_filters( 'user_erasure_fulfillment_email_to', ... )
				apply_filters( 'user_erasure_fulfillment_email_subject', ... )
				apply_filters( 'user_erasure_fulfillment_email_content', ... )
				// Send a confirmation request email to confirm an action.
				apply_filters( 'user_request_action_email_subject', ... )
				apply_filters( 'user_request_action_email_content', ... )

			\wp-admin\includes\privacy-tools.php
			  -- action request
				// Send an email to the user with a link to the personal data export file
				apply_filters( 'wp_privacy_personal_data_email_to', ... )
				apply_filters( 'wp_privacy_personal_data_email_subject', ... )
				apply_filters( 'wp_privacy_personal_data_email_content', ... )

		  -- admin only
			\wp-admin\includes\upgrade.php
			  -- installation
					// Notifies the site admin that the installation of WordPress is complete.
					apply_filters( 'wp_installed_email', ... )

			\wp-admin\includes\class-wp-automatic-updater.php
			  -- automatic updates
				// Sends an email upon the completion or failure of a background core update.
				apply_filters( 'auto_core_update_email', ... )
				// Sends an email upon the completion or failure of a plugin or theme background update.
				apply_filters( 'auto_plugin_theme_update_email', ... )
				// Prepares and sends an email of a full log of background update results, useful for debugging and geekery.
				apply_filters( 'automatic_updates_debug_email', ... )

			\wp-includes\class-wp-recovery-mode-email-service.php
			  -- recovery mode
				// Sends the Recovery Mode email to the site admin email address.
				apply_filters( 'recovery_email_support_info', ... )
				apply_filters( 'recovery_email_debug_info', ... )
				apply_filters( 'recovery_mode_email', ... )

		*/

		/*
		 dummy case for email definition
			case 'xx':
				// @see xx.php (ff)
				$subject  = ;
				$message  = ;
				$message .= "\n\n";
				$placeholder = array(  );
				return array(
					'title' => __('', 'greyd_hub'),
					'description' => '',
					'subject' => array( 'default' => $subject, 'value' => $this->get_value($type, $slug, 'subject') ),
					'content' => array( 'default' => $message, 'value' => $this->get_value($type, $slug, 'content'), 'placeholder' => $placeholder )
				);
				break;
		*/

		// settings type name
		$name = $type . '_mails';

		// default return value
		$values = false;

		// user mail defaults
		if ( $type == 'user' ) {
			switch ( $slug ) {
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					$subject     = sprintf( __( '[%s] Login Details', 'greyd_hub' ), 'BLOG_NAME' );
					$message     = sprintf( __( 'Username: %s', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __( 'To set your password, visit the following address:', 'greyd_hub' ) . "\n\n";
					$message    .= 'RESETLINK' . "\n\n" . 'LOGINLINK' . "\n";
					$placeholder = array( 'BLOG_NAME', 'USERNAME', 'USERMAIL', 'RESETLINK', 'LOGINLINK' );
					return array(
						'title'       => __( 'New user', 'greyd_hub' ),
						'description' => __( 'Welcome email sent to new users.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// invite User
				case 'invited_user_email':
					// @see \wp-admin\user-new.php (88ff)
					$subject     = sprintf( __( '[%s] Joining Confirmation', 'greyd_hub' ), 'BLOG_NAME' );
					$message     = __( 'Hi,', 'greyd_hub' ) . "\n\n";
					$message    .= sprintf( __( 'You&lsquo;ve been invited to join &lsquo;%s&lsquo; at', 'greyd_hub' ), 'BLOG_NAME' ) . "\n";
					$message    .= sprintf( __( '%1$s with the role of %2$s.', 'greyd_hub' ), 'HOMELINK', 'USERROLE' ) . "\n\n";
					$message    .= __( 'Please click the following link to confirm the invite:', 'greyd_hub' ) . "\n";
					$message    .= 'CONFIRMLINK';
					$placeholder = array( 'BLOG_NAME', 'USERROLE', 'CONFIRMLINK' );
					return array(
						'work_in_progress' => true,
						'no_testmail'      => true,
						'title'            => __( 'Invite user', 'greyd_hub' ),
						'description'      => __( 'Email sent when an existing user is invited to join the site.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// change user email
				case 'send_confirmation_on_profile_email':
					// @see \wp-includes\user.php (3602ff)
					$subject     = sprintf( __( '[%s] Email Change Request', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'Howdy %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __( 'You recently requested to have the email address on your account changed.', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'If this is correct, please click on the following link to change it:' . "\n" .
						'CHANGELINK',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'You can safely ignore and delete this email if you do not want to' . "\n" .
						'take this action.',
						'greyd_hub'
					) . "\n\n";
					$message    .= __( 'This email has been sent to NEWMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL', 'CHANGELINK' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'Change user email address request', 'greyd_hub' ),
						'description'      => __( 'Email sent when a change of user email address is attempted.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $type, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $type, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'wp_update_user_email':
					// @see \wp-includes\user.php (2487ff)
					$subject     = sprintf( __( '[%s] Email Changed', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'Hi %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __( 'This notice confirms that your email address on SITE_NAME was changed to NEWMAIL.', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'If you did not change your email, please contact the Site Administrator at' . "\n" .
						'ADMINMAIL',
						'greyd_hub'
					) . "\n\n";
					$message    .= __( 'This email has been sent to USERMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL', 'ADMINMAIL' );
					return array(
						'work_in_progress' => true,
						'no_testmail'      => true,
						'title'            => __( 'Change user email address', 'greyd_hub' ),
						'description'      => __( 'Email sent when the user\'s email is changed.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// recover user password
				case 'retrieve_password':
					// @see \wp-includes\user.php (3018ff)
					$subject     = sprintf( __( '[%s] Password Reset', 'greyd_hub' ), 'SITE_NAME' );
					$message     = __( 'Someone has requested a password reset for the following account:', 'greyd_hub' ) . "\n\n";
					$message    .= sprintf( __( 'Site name: %s', 'greyd_hub' ), 'SITE_NAME' ) . "\n\n";
					$message    .= sprintf( __( 'Username: %s', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __( 'If this was a mistake, ignore this email and nothing will happen.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'To reset your password, visit the following address:', 'greyd_hub' ) . "\n\n";
					$message    .= 'RESETLINK' . "\n\n";
					$message    .= sprintf( __( 'This password reset request originated from the IP address %s.', 'greyd_hub' ), 'IPADDRESS' ) . "\n";
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'RESETLINK', 'IPADDRESS' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'Reset user password request', 'greyd_hub' ),
						'description'      => __( 'Reset password notification email sent to the user.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// change user password
				case 'wp_update_user_password':
					// @see \wp-includes\user.php (2487ff)
					$subject     = sprintf( __( '[%s] Password Changed', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'Hi %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __( 'This notice confirms that your password was changed on SITE_NAME.', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'If you did not change your password, please contact the Site Administrator at' . "\n" .
						'ADMINMAIL',
						'greyd_hub'
					) . "\n\n";
					$message    .= __( 'This email has been sent to USERMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'ADMINMAIL' );
					return array(
						'work_in_progress' => true,
						'no_testmail'      => true,
						'title'            => __( 'Change user password', 'greyd_hub' ),
						'description'      => __( 'Email sent when the user\'s password is changed.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				//
				// multisite new User
				case 'wpmu_signup_user_notification':
					// @see \wp-includes\ms-functions.php (1055ff)
					$subject     = sprintf( __( '[%1$s] Activate %2$s', 'greyd_hub' ), 'SITE_NAME', 'USERNAME' );
					$message     = __( 'To activate your user, please click the following link:', 'greyd_hub' );
					$message    .= "\n\n" . 'ACTIVATELINK' . "\n\n";
					$message    .= __( 'After you activate, you will receive *another email* with your login.', 'greyd_hub' );
					$placeholder = array( 'SITE_NAME', 'USERNAME', 'USERMAIL', 'ACTIVATELINK' );
					return array(
						'multisite'   => true,
						'title'       => __( 'New user signup', 'greyd_hub' ),
						'description' => __( 'Notification email of new user signup.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'wpmu_welcome_user_notification':
					// @see \wp-includes\ms-functions.php (1822ff)
					$subject     = sprintf( __( 'New %1$s User: %2$s', 'greyd_hub' ), 'SITE_NAME', 'USERNAME' );
					$message     = get_site_option( 'welcome_user_email' );
					$placeholder = array( 'SITE_NAME', 'USERNAME', 'PASSWORD', 'LOGINLINK' );
					// description
					$settings_url = network_admin_url( 'settings.php#welcome_user_email' );
					$description  = __( 'Welcome email after user activation.', 'greyd_hub' ) . '<br><br>';
					$description .= sprintf( __( 'You can edit this Email in the %1$sNetwork Settings%2$s.', 'greyd_hub' ), '<a href="' . $settings_url . '" target="_blank">', '</a>' );
					return array(
						'disabled'    => true,
						'multisite'   => true,
						'title'       => __( 'User activation', 'greyd_hub' ),
						'description' => $description,
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// multisite new blog
				case 'wpmu_signup_blog_notification':
					// @see \wp-includes\ms-functions.php (920ff)
					$subject     = sprintf( __( '[%1$s] Activate %2$s' ), 'SITE_NAME', 'BLOG_URL' );
					$message     = __( 'To activate your site, please click the following link:', 'greyd_hub' );
					$message    .= "\n\n" . 'ACTIVATELINK' . "\n\n";
					$message    .= __( 'After you activate, you will receive *another email* with your login.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'After you activate, you can visit your site here:', 'greyd_hub' );
					$message    .= "\n\n" . 'BLOG_URL';
					$placeholder = array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'USERMAIL', 'ACTIVATELINK' );
					return array(
						'multisite'   => true,
						'title'       => __( 'New blog', 'greyd_hub' ),
						'description' => __( 'New blog notification email.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'wpmu_welcome_notification':
					// @see \wp-includes\ms-functions.php (1589ff)
					$subject     = sprintf( __( 'New %1$s Site: %2$s' ), 'SITE_NAME', 'BLOG_NAME' );
					$message     = get_site_option( 'welcome_email' );
					$placeholder = array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'PASSWORD' );
					// description
					$settings_url = network_admin_url( 'settings.php#welcome_email' );
					$description  = __( 'Welcome email sent after site activation.', 'greyd_hub' ) . '<br><br>';
					$description .= sprintf( __( 'You can edit this email in the %1$sNetwork Settings%2$s.', 'greyd_hub' ), '<a href="' . $settings_url . '" target="_blank">', '</a>' );
					return array(
						'disabled'    => true,
						'multisite'   => true,
						'title'       => __( 'Blog activation', 'greyd_hub' ),
						'description' => $description,
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
			}
		}

		// admin mail defaults
		if ( $type == 'admin' ) {
			switch ( $slug ) {
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					$subject     = sprintf( __( '[%s] New User Registration', 'greyd_hub' ), 'BLOG_NAME' );
					$message     = sprintf( __( 'New user registration on your site %s:', 'greyd_hub' ), 'BLOG_NAME' ) . "\n\n";
					$message    .= sprintf( __( 'Username: %s', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= sprintf( __( 'Email: %s', 'greyd_hub' ), 'USERMAIL' ) . "\n";
					$placeholder = array( 'BLOG_NAME', 'USERNAME', 'USERMAIL' );
					return array(
						'title'       => __( 'New user', 'greyd_hub' ),
						'description' => __( 'Welcome email sent to the site owner.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// change user password
				case 'wp_password_change_notification':
					// @see \wp-includes\pluggable.php (2036ff)
					$subject     = sprintf( __( '[%s] Password Changed', 'greyd_hub' ), 'BLOG_NAME' );
					$message     = sprintf( __( 'Password changed for user: %s', 'greyd_hub' ), 'USERNAME' );
					$placeholder = array( 'BLOG_NAME', 'USERNAME', 'USERMAIL' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'Change user password', 'greyd_hub' ),
						'description'      => __( 'Password change notification email sent to the site admin.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// change admin email
				case 'update_option_new_admin_email':
					// @see \wp-admin\includes\misc.php (1413ff)
					$subject     = sprintf( __( '[%s] New Admin Email Address', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'Howdy %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __(
						'Someone with administrator capabilities recently requested to have the' . "\n" .
									'administration email address changed on this site:' . "\n" .
						'SITE_URL',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'To confirm this change, please click on the following link:' . "\n" .
						'CHANGELINK',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'You can safely ignore and delete this email if you do not want to' . "\n" .
						'take this action.',
						'greyd_hub'
					) . "\n\n";
					$message    .= __( 'This email has been sent to NEWMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL', 'CHANGELINK' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'Change admin email address request', 'greyd_hub' ),
						'description'      => __( 'Email sent when a change of site admin email address is attempted.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $type, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $type, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'wp_site_admin_email_change_notification':
					// @see \wp-includes\functions.php (7633ff)
					$subject     = sprintf( __( '[%s] Admin Email Changed', 'greyd_hub' ), 'SITE_NAME' );
					$message     = __( 'Hi,', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'This notice confirms that the admin email address was changed on SITE_NAME.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'The new admin email address is NEWMAIL.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'This email has been sent to USERMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'Change admin email address', 'greyd_hub' ),
						'description'      => __( 'Email sent when the site admin email address is changed.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				//
				// wp installation
				case 'wp_new_blog_notification':
					// @see \wp-admin\includes\upgrade.php (565ff)
					$subject     = __( 'New WordPress Site', 'greyd_hub' );
					$message     = __( 'Your new WordPress site has been successfully set up at:', 'greyd_hub' ) . "\n\n";
					$message    .= 'BLOG_URL' . "\n\n";
					$message    .= __( 'You can log in to the administrator account with the following information:', 'greyd_hub' ) . "\n\n";
					$message    .= sprintf( __( 'Username: %s', 'greyd_hub' ), 'USERNAME' ) . "\n";
					$message    .= sprintf( __( 'Password: %s', 'greyd_hub' ), 'PASSWORD' ) . "\n";
					$message    .= sprintf( __( 'Log in here: %s', 'greyd_hub' ), 'LOGINLINK' ) . "\n\n";
					$message    .= __( 'We hope you enjoy your new site. Thanks!', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'--The WordPress Team' . "\n" .
						'https://wordpress.org/',
						'greyd_hub'
					) . "\n";
					$placeholder = array( 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'PASSWORD', 'LOGINLINK' );
					return array(
						'work_in_progress' => true,
						'title'            => __( 'WordPress installed', 'greyd_hub' ),
						'description'      => __( 'Email sent to the site admin when WordPress is installed.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				//
				// multisite new User
				case 'newuser_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1482ff)
					$subject     = sprintf( __( 'New User Registration: %s', 'greyd_hub' ), 'USERNAME' );
					$message     = sprintf( __( 'New user: %s', 'greyd_hub' ), 'USERNAME' ) . "\n";
					$message    .= sprintf( __( 'Remote IP address: %s', 'greyd_hub' ), 'IPADDRESS' ) . "\n\n";
					$message    .= sprintf( __( 'Disable these notifications: %s', 'greyd_hub' ), 'SETTINGSLINK' );
					$placeholder = array( 'SITE_NAME', 'USERNAME', 'USERMAIL', 'IPADDRESS', 'SETTINGSLINK' );
					return array(
						'multisite'   => true,
						'title'       => __( 'User activation', 'greyd_hub' ),
						'description' => __( 'Notifies the network admin that a new user has been activated.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// multisite new blog
				case 'wpmu_new_site_admin_notification':
					// @see \wp-includes\ms-functions.php (1706ff)
					$subject     = sprintf( __( '[%s] New Site Created', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'New site created by %s', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= sprintf( __( 'Address: %s', 'greyd_hub' ), 'BLOG_URL' ) . "\n";
					$message    .= sprintf( __( 'Name: %s', 'greyd_hub' ), 'BLOG_NAME' );
					$placeholder = array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'USERMAIL' );
					return array(
						'multisite'   => true,
						'title'       => __( 'New blog', 'greyd_hub' ),
						'description' => __( 'Notifies the network admin that a new site was created.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'newblog_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1417ff)
					$subject     = sprintf( __( 'New site registration: %s', 'greyd_hub' ), 'BLOG_URL' );
					$message     = sprintf( __( 'New site: %s', 'greyd_hub' ), 'BLOG_NAME' ) . "\n";
					$message    .= sprintf( __( 'URL: %s', 'greyd_hub' ), 'BLOG_URL' ) . "\n";
					$message    .= sprintf( __( 'Remote IP address: %s', 'greyd_hub' ), 'IPADDRESS' ) . "\n\n";
					$message    .= sprintf( __( 'Disable these notifications: %s', 'greyd_hub' ), 'SETTINGSLINK' );
					$placeholder = array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'IPADDRESS', 'SETTINGSLINK' );
					return array(
						'multisite'   => true,
						'title'       => __( 'Blog activation', 'greyd_hub' ),
						'description' => __( 'Notifies the network admin that a new site has been activated.', 'greyd_hub' ),
						'subject'     => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'     => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// multisite delete blog
				case 'delete_site_email_content':
					// @see \wp-admin\ms-delete-site.php (47ff)
					$subject     = sprintf( __( '[%s] Delete My Site', 'greyd_hub' ), 'BLOG_NAME' );
					$message     = sprintf( __( 'Howdy %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __(
						'You recently clicked the &lsquo;Delete Site&lsquo; link on your site and filled in a' . "\n" .
						'form on that page.',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'If you really want to delete your site, click the link below. You will not' . "\n" .
									'be asked to confirm again so only click this link if you are absolutely certain:' . "\n" .
						'DELETELINK',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'If you delete your site, please consider opening a new site here' . "\n" .
									'some time in the future! (But remember your current site and username' . "\n" .
						'are gone forever.)',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'Thanks for using the site,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'BLOG_NAME', 'USERNAME', 'DELETELINK' );
					return array(
						'work_in_progress' => true,
						'no_testmail'      => true,
						'multisite'        => true,
						'title'            => __( 'Delete blog request', 'greyd_hub' ),
						'description'      => __( 'Email sent to the site admin when a request to delete a site is submitted.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				// multisite change admin email
				case 'update_network_option_new_admin_email':
					// @see \wp-includes\ms-functions.php (2804ff)
					$subject     = sprintf( __( '[%s] Network Admin Email Change Request', 'greyd_hub' ), 'SITE_NAME' );
					$message     = sprintf( __( 'Howdy %s,', 'greyd_hub' ), 'USERNAME' ) . "\n\n";
					$message    .= __(
						'You recently requested to have the network admin email address on' . "\n" .
						'your network changed.',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'If this is correct, please click on the following link to change it:' . "\n" .
						'CHANGELINK',
						'greyd_hub'
					) . "\n\n";
					$message    .= __(
						'You can safely ignore and delete this email if you do not want to' . "\n" .
						'take this action.',
						'greyd_hub'
					) . "\n\n";
					$message    .= __( 'This email has been sent to NEWMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL', 'CHANGELINK' );
					return array(
						'work_in_progress' => true,
						'multisite'        => true,
						'title'            => __( 'Change network admin email address request', 'greyd_hub' ),
						'description'      => __( 'Email sent when a change of network admin email address is attempted.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
				case 'wp_network_admin_email_change_notification':
					// @see \wp-includes\ms-functions.php (2892ff)
					$subject     = sprintf( __( '[%s] Network Admin Email Change Request', 'greyd_hub' ), 'SITE_NAME' );
					$message     = __( 'Hi,', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'This notice confirms that the network admin email address was changed on SITE_NAME.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'The new network admin email address is NEWMAIL.', 'greyd_hub' ) . "\n\n";
					$message    .= __( 'This email has been sent to USERMAIL', 'greyd_hub' ) . "\n\n";
					$message    .= __(
						'Regards,' . "\n" .
									'All at SITE_NAME' . "\n" .
						'SITE_URL',
						'greyd_hub'
					);
					$placeholder = array( 'SITE_NAME', 'SITE_URL', 'USERNAME', 'USERMAIL', 'NEWMAIL' );
					return array(
						'work_in_progress' => true,
						'multisite'        => true,
						'title'            => __( 'Change network admin email address', 'greyd_hub' ),
						'description'      => __( 'Email sent when the network admin email address is changed.', 'greyd_hub' ),
						'subject'          => array(
							'default' => $subject,
							'value'   => $this->get_global_value( $name, $slug, 'subject' ),
						),
						'content'          => array(
							'default'     => $message,
							'value'       => $this->get_global_value( $name, $slug, 'content' ),
							'placeholder' => $placeholder,
						),
					);
					break;
			}
		}

		// check global setting and use as default
		$check_global = function( $default, $type, $slug ) {
			$info = '';
			if ( is_multisite() && ! is_network_admin() ) {
				$global_value = $this->get_global_value( $type, $slug );
				if ( $global_value ) {
					$default = $global_value;
					$info    = Helper::render_info_box(
						array(
							'text' => sprintf(
								__( 'Set to %1$s in %2$sNetwork User Email Settings%3$s.', 'greyd_hub' ),
								'<strong>' . $default . '</strong>',
								'<a href="' . network_admin_url( 'admin.php?page=greyd_user&tab=mail' ) . '" target="_blank">',
								'</a>'
							),
						)
					) . '<br>';
				}
			}
			return array( $default, $info );
		};

		// from/to header
		if ( $type == 'from' ) {
			switch ( $slug ) {
				case 'name':
					list( $default, $info ) = $check_global( $args['name'], 'from', 'name' );
					return array(
						'title'       => __( 'Name', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'from', 'name' ),
						'description' =>
							$info .
							sprintf( __( 'Default: %s', 'greyd_hub' ), '<strong>' . $args['name'] . '</strong>' ) . '<br>' .
							__( 'On multisite setups:', 'greyd_hub' ) . '<br>' .
							sprintf( __( 'Sometimes %s (network name)', 'greyd_hub' ), '<strong>' . $args['alt']['name'] . '</strong>' ) . '<br>' .
							sprintf( __( 'Or %s', 'greyd_hub' ), '<strong>' . $args['alt2']['name'] . '</strong>' ),
					);
					break;
				case 'address':
					list( $default, $info ) = $check_global( $args['address'], 'from', 'address' );
					return array(
						'title'       => __( 'Email address', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'from', 'address' ),
						'description' =>
							$info .
							sprintf( __( 'Default: %s', 'greyd_hub' ), '<strong>' . $args['address'] . '</strong>' ) . '<br>' .
							__( 'On multisite setups:', 'greyd_hub' ) . '<br>' .
							sprintf( __( 'Sometimes %s', 'greyd_hub' ), '<strong>' . $args['alt']['address'] . '</strong>' ) . '<br>' .
							sprintf( __( 'Or %s (admin email)', 'greyd_hub' ), '<strong>' . $args['alt2']['address'] . '</strong>' ),
					);
					break;
			}
		}
		if ( $type == 'user_to' ) {
			switch ( $slug ) {
				case 'to':
					return array(
						'title'       => __( 'User email address', 'greyd_hub' ),
						'default'     => $args['user'],
						'value'       => '',
						'description' =>
							__( 'This address (yours) represents the user email and can not be changed.', 'greyd_hub' ) . '<br>' .
							__( 'You can add additional recipients to all user emails in the section below.', 'greyd_hub' ),
					);
					break;
				case 'cc':
					list( $default, $info ) = $check_global( '', 'user_to', 'cc' );
					return array(
						'title'       => __( 'CC - additional recipient(s)', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'user_to', 'cc' ),
						'description' => $info,
					);
					break;
				case 'bcc':
					list( $default, $info ) = $check_global( '', 'user_to', 'bcc' );
					return array(
						'title'       => __( 'BCC - additional hidden recipient(s)', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'user_to', 'bcc' ),
						'description' => $info,
					);
					break;
			}
		}
		if ( $type == 'admin_to' ) {
			switch ( $slug ) {
				case 'to':
					list( $default, $info ) = $check_global( $args['admin'], 'admin_to', 'to' );
					return array(
						'title'       => __( 'Admin email address', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'admin_to', 'to' ),
						'description' =>
							$info .
							sprintf( __( 'Default: %s (admin email)', 'greyd_hub' ), '<strong>' . $args['admin'] . '</strong>' ) . '<br>' .
							__( 'It is possible to redirect all admin emails to another address by changing this value.', 'greyd_hub' ) . '<br>' .
							__( 'However, it is recommended to leave the default admin email and add additional recipients in the section below.', 'greyd_hub' ),
					);
					break;
				case 'cc':
					list( $default, $info ) = $check_global( '', 'admin_to', 'cc' );
					return array(
						'title'       => __( 'CC - additional recipient(s)', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'admin_to', 'cc' ),
						'description' => $info,
					);
					break;
				case 'bcc':
					list( $default, $info ) = $check_global( '', 'admin_to', 'bcc' );
					return array(
						'title'       => __( 'BCC - additional hidden recipient(s)', 'greyd_hub' ),
						'default'     => $default,
						'value'       => $this->get_value( 'admin_to', 'bcc' ),
						'description' => $info,
					);
					break;
			}
		}

		// testmail
		if ( $type == 'testmail' ) {
			switch ( $slug ) {
				case 'to':
					return array(
						'title'       => __( 'Receiver of test emails', 'greyd_hub' ),
						'default'     => '',
						'value'       => $this->get_value( 'testmail', 'to' ),
						'description' =>
							__( 'Insert an email address to be the single receiver of test emails.', 'greyd_hub' ) . '<br>' .
							__( 'If empty, test emails are sent to all of their original recipients (including CC and BCC address(es) if set).', 'greyd_hub' ),
					);
					break;
				case 'user':
					return array(
						'title'       => __( 'Test user', 'greyd_hub' ),
						'default'     => '',
						'value'       => $this->get_value( 'testmail', 'user' ),
						'description' =>
							__( 'Choose a user to be represented in the test emails.', 'greyd_hub' ) . '<br>' .
							__( 'By default, this is <strong>you</strong>.', 'greyd_hub' ),
					);
					break;
				case 'blog':
					return array(
						'title'       => __( 'Test blog', 'greyd_hub' ),
						'default'     => '',
						'value'       => $this->get_value( 'testmail', 'blog' ),
						'description' =>
							__( 'Choose a blog to be used in the test emails.', 'greyd_hub' ) . '<br>' .
							__( 'By default, this is the <strong>current blog</strong>.', 'greyd_hub' ),
					);
					break;
			}
		}

		return $values;

	}

	/**
	 * Make a Testmail by calling the core function that calls wp_mail().
	 * This is not always possible, sometimes env variables must be set or other scripts need to be executed before.
	 * Called from send_testmail ajax function.
	 *
	 * @param string $mode      Render mode "site" or "global".
	 * @param string $type      Type of Mail to render "user" or "admin".
	 * @param string $mail_slug Mail slug to render.
	 */
	private function call_mail_function( $mode, $type, $mail_slug ) {

		// debug(array( $type, $mail_slug));

		// get test setup
		// $user = 5;
		// $blog = 16;
		if ( $mode == 'site' ) {
			$user = $this->get_value( 'testmail', 'user' );
			$blog = $this->get_value( 'testmail', 'blog' );
		} else {
			$user = $this->get_global_value( 'testmail', 'user' );
			$blog = $this->get_global_value( 'testmail', 'blog' );
		}
		// debug("saved testmail user id: ".$user);
		// debug("saved testmail blog id: ".$blog);

		// testuser
		if ( ! empty( $user ) ) {
			$user = get_user_by( 'id', $user );
		} else {
			$user = wp_get_current_user();
		}
		$user_id = $user->ID;

		$new_user_name  = $user->data->user_login;
		$new_user_email = $user->data->user_email;
		$key            = '1234567890';
		$password       = 'N/A';

		$old_mail = get_site_option( 'admin_email' );
		$new_mail = 'new-email@' . explode( '@', $old_mail )[1];

		$blog            = self::get_blog_details( empty( $blog ) ? null : $blog );
		$blog_id         = $blog->blog_id;
		$new_blog_domain = $blog->domain;
		$new_blog_path   = $blog->path;
		$new_blog_name   = $blog->blogname;

		// debug(array( $blog_id, $new_blog_domain, $new_blog_path, $new_blog_name ));

		// user mail functions
		if ( $type == 'user' ) {
			switch ( $mail_slug ) {
				// smtp test
				case 'settings_test_mail':
					// @see function settings_test_mail
					$this->settings_test_mail( $mode, $user );
					break;
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					wp_new_user_notification( $user_id, null, 'user' );
					break;
				// invite User
				case 'invited_user_email':
					// @see \wp-admin\user-new.php (88ff)
					// no function to call ...
					break;
				// change user email
				case 'send_confirmation_on_profile_email':
					// @see \wp-includes\user.php (3602ff)
					$current_user     = wp_get_current_user();
					$_POST['user_id'] = $current_user->ID;
					$_POST['email']   = $new_mail;
					send_confirmation_on_profile_email();
					break;
				case 'wp_update_user_email':
					// @see \wp-includes\user.php (2487ff)
					// function can not be called without changing the user ...
					break;
				// recover user password
				case 'retrieve_password':
					// @see \wp-includes\user.php (3018ff)
					retrieve_password( $new_user_name );
					break;
				// change user password
				case 'wp_update_user_password':
					// @see \wp-includes\user.php (2487ff)
					// function can not be called without changing the user ...
					break;
				//
				// multisite new user
				case 'wpmu_signup_user_notification':
					// @see \wp-includes\ms-functions.php (1055ff)
					wpmu_signup_user_notification( $new_user_name, $new_user_email, $key );
					break;
				case 'wpmu_welcome_user_notification':
					// @see \wp-includes\ms-functions.php (1822ff)
					wpmu_welcome_user_notification( $user_id, $password );
					break;
				// multisite new blog
				case 'wpmu_signup_blog_notification':
					// @see \wp-includes\ms-functions.php (920ff)
					wpmu_signup_blog_notification( $new_blog_domain, $new_blog_path, $new_blog_name, $new_user_name, $new_user_email, $key );
					break;
				case 'wpmu_welcome_notification':
					// @see \wp-includes\ms-functions.php (1589ff)
					wpmu_welcome_notification( $blog_id, $user_id, $password, $new_blog_name );
					break;
			}
		}

		// admin mail functions
		if ( $type == 'admin' ) {
			switch ( $mail_slug ) {
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					wp_new_user_notification( $user_id, null, 'admin' );
					break;
				// change user password
				case 'wp_password_change_notification':
					// @see \wp-includes\pluggable.php (2036ff)
					wp_password_change_notification( $user );
					break;
				// change admin email
				case 'update_option_new_admin_email':
					// @see \wp-admin\includes\misc.php (1413ff)
					update_option_new_admin_email( $old_mail, $new_mail );
					break;
				case 'wp_site_admin_email_change_notification':
					// @see \wp-includes\functions.php (7633ff)
					wp_site_admin_email_change_notification( $old_mail, $new_mail, '' );
					break;
				//
				// wp installation
				case 'wp_new_blog_notification':
					// @see \wp-admin\includes\upgrade.php (565ff)
					// require_once ABSPATH . 'wp-admin/includes/upgrade.php';
					// wp_new_blog_notification( $blog->blogname, $blog->home, $user_id, $password );
					// no working ...
					break;
				//
				// multisite new user
				case 'newuser_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1482ff)
					newuser_notify_siteadmin( $user_id );
					break;
				// multisite new blog
				case 'wpmu_new_site_admin_notification':
					// @see \wp-includes\ms-functions.php (1706ff)
					wpmu_new_site_admin_notification( $blog_id, $user_id );
					break;
				case 'newblog_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1417ff)
					newblog_notify_siteadmin( $blog_id );
					break;
				// multisite delete blog
				case 'delete_site_email_content':
					// @see \wp-admin\ms-delete-site.php (47ff)
					// no function to call ...
					break;
				// multisite change admin email
				case 'update_network_option_new_admin_email':
					// @see \wp-includes\ms-functions.php (2804ff)
					update_network_option_new_admin_email( $old_mail, $new_mail );
					break;
				case 'wp_network_admin_email_change_notification':
					// @see \wp-includes\ms-functions.php (2892ff)
					wp_network_admin_email_change_notification( '', $new_mail, $old_mail, null );
					break;
			}
		}

	}

	/**
	 * Modify Mail contents by adding various Filter.
	 * Called from user-management.php when Theme functions and settings are available.
	 *
	 * @param string $type      Type of Mail to filter "user" or "admin".
	 * @param string $mail_slug Mail slug to filter.
	 */
	public static function add_mail_filter( $type, $mail_slug ) {

		// user mail filter
		if ( $type == 'user' ) {
			switch ( $mail_slug ) {
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					add_filter(
						'wp_new_user_notification_email',
						function( $wp_new_user_notification_email, $user, $blogname ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $user, $blogname, $key ) = $atts;
								$resetlink                     = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
								$loginlink                     = wp_login_url();
								$string                        = str_replace(
									array( 'BLOG_NAME', 'USERNAME', 'USERMAIL', 'RESETLINK', 'LOGINLINK' ),
									array( $blogname, $user->user_login, $user->user_email, $resetlink, $loginlink ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_settings( 'mails' );
							$value    = $settings['user_mails']['wp_new_user_notification'];
							// subject
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$subject                                   = replace_strings( $value['subject'], array( $user, $blogname, '' ) );
								$wp_new_user_notification_email['subject'] = $subject;
							}
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								// extract activation key
								preg_match_all( '/&key=((.|\n)*)&/', $wp_new_user_notification_email['message'], $matches );
								if ( $matches && count( $matches[1] ) > 0 ) {
									// debug($matches);
									$key = $matches[1][0];
								} else {
									$key = '';
								}
								$message                                   = replace_strings( $value['content'], array( $user, $blogname, $key ) );
								$wp_new_user_notification_email['message'] = $message;
							}
							return $wp_new_user_notification_email;
						},
						99,
						3
					);
					break;
				// invite User
				case 'invited_user_email':
					// @see \wp-admin\user-new.php (88ff)
					// todo
					break;
				// change user email
				case 'send_confirmation_on_profile_email':
					// @see \wp-includes\user.php (3602ff)
					// todo
					break;
				case 'wp_update_user_email':
					// @see \wp-includes\user.php (2487ff)
					// todo
					break;
				// recover user password
				case 'retrieve_password':
					// @see \wp-includes\user.php (3018ff)
					// todo
					break;
				// change user password
				case 'wp_update_user_password':
					// @see \wp-includes\user.php (2487ff)
					// todo
					break;
				//
				// multisite new user
				case 'wpmu_signup_user_notification':
					// @see \wp-includes\ms-functions.php (1055ff)
					add_filter(
						'wpmu_signup_user_notification_email',
						function( $msg, $user_login, $user_email, $key, $meta ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $user_login, $user_email, $key ) = $atts;
								$site_name                             = esc_html( get_site_option( 'site_name' ) );
								$activatelink                          = site_url( 'wp-activate.php?key=' . $key );
								$string                                = str_replace(
									array( 'SITE_NAME', 'USERNAME', 'USERMAIL', 'ACTIVATELINK' ),
									array( $site_name, $user_login, $user_email, $activatelink ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['user_mails']['wpmu_signup_user_notification'];
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message = replace_strings( $value['content'], array( $user_login, $user_email, $key ) );
								$msg     = $message;
							}
							return $msg;
						},
						99,
						5
					);
					add_filter(
						'wpmu_signup_user_notification_subject',
						function( $msg, $user_login, $user_email, $key, $meta ) {
							// replace function
							function replace_strings_subject( $string, $atts ) {
								list( $user_login, $user_email, $key ) = $atts;
								$site_name                             = esc_html( get_site_option( 'site_name' ) );
								$activatelink                          = site_url( 'wp-activate.php?key=' . $key );
								$string                                = str_replace(
									array( 'SITE_NAME', 'USERNAME', 'USERMAIL', 'ACTIVATELINK' ),
									array( $site_name, $user_login, $user_email, $activatelink ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['user_mails']['wpmu_signup_user_notification'];
							// message
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$message = replace_strings_subject( $value['subject'], array( $user_login, $user_email, $key ) );
								$msg     = $message;
							}
							return $msg;
						},
						99,
						5
					);
					break;
				case 'wpmu_welcome_user_notification':
					// @see \wp-includes\ms-functions.php (1822ff)
					// todo
					break;
				// multisite new blog
				case 'wpmu_signup_blog_notification':
					// @see \wp-includes\ms-functions.php (920ff)
					add_filter(
						'wpmu_signup_blog_notification_email',
						function( $msg, $domain, $path, $title, $user_login, $user_email, $key, $meta ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $domain, $path, $title, $user_login, $user_email, $key ) = $atts;
								$site_name = esc_html( get_site_option( 'site_name' ) );
								if ( ! is_subdomain_install() || get_current_network_id() != 1 ) {
									$activate_url = network_site_url( 'wp-activate.php?key=' . $key );
								} else {
									$activate_url = 'http://' . $domain . $path . 'wp-activate.php?key=' . $key;
								}
								$string = str_replace(
									array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'USERMAIL', 'ACTIVATELINK' ),
									array( $site_name, $title, esc_url( 'http://' . $domain . $path ), $user_login, $user_email, esc_url( $activate_url ) ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['user_mails']['wpmu_signup_blog_notification'];
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message = replace_strings( $value['content'], array( $domain, $path, $title, $user_login, $user_email, $key ) );
								$msg     = $message;
							}
							return $msg;
						},
						99,
						8
					);
					add_filter(
						'wpmu_signup_blog_notification_subject',
						function( $msg, $domain, $path, $title, $user_login, $user_email, $key, $meta ) {
							// replace function
							function replace_strings_subject( $string, $atts ) {
								list( $domain, $path, $title, $user_login, $user_email ) = $atts;
								$site_name = esc_html( get_site_option( 'site_name' ) );
								$string    = str_replace(
									array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'USERMAIL' ),
									array( $site_name, $title, esc_url( 'http://' . $domain . $path ), $user_login, $user_email ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['user_mails']['wpmu_signup_blog_notification'];
							// message
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$message = replace_strings_subject( $value['subject'], array( $domain, $path, $title, $user_login, $user_email ) );
								$msg     = $message;
							}
							return $msg;
						},
						99,
						8
					);
					break;
				case 'wpmu_welcome_notification':
					// @see \wp-includes\ms-functions.php (1589ff)
					// todo
					break;
			}
		}

		// admin mail filter
		if ( $type == 'admin' ) {
			switch ( $mail_slug ) {
				// new User
				case 'wp_new_user_notification':
					// @see \wp-includes\pluggable.php (2098ff)
					add_filter(
						'wp_new_user_notification_email_admin',
						function( $wp_new_user_notification_email_admin, $user, $blogname ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $user, $blogname ) = $atts;
								$string                  = str_replace(
									array( 'BLOG_NAME', 'USERNAME', 'USERMAIL' ),
									array( $blogname, $user->user_login, $user->user_email ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_settings( 'mails' );
							$value    = $settings['admin_mails']['wp_new_user_notification'];
							// subject
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$subject = replace_strings( $value['subject'], array( $user, $blogname ) );
								$wp_new_user_notification_email_admin['subject'] = $subject;
							}
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message = replace_strings( $value['content'], array( $user, $blogname ) );
								$wp_new_user_notification_email_admin['message'] = $message;
							}
							return $wp_new_user_notification_email_admin;
						},
						99,
						3
					);
					break;
				// change user password
				case 'wp_password_change_notification':
					// @see \wp-includes\pluggable.php (2036ff)
					// todo
					break;
				// change admin email
				case 'update_option_new_admin_email':
					// @see \wp-admin\includes\misc.php (1413ff)
					// todo
					break;
				case 'wp_site_admin_email_change_notification':
					// @see \wp-includes\functions.php (7633ff)
					// todo
					break;
				//
				// wp installation
				case 'wp_new_blog_notification':
					// @see \wp-admin\includes\upgrade.php (565ff)
					// todo
					break;
				//
				// multisite new user
				case 'newuser_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1482ff)
					add_filter(
						'newuser_notify_siteadmin',
						function( $msg, $user ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $user ) = $atts;
								$site_name    = esc_html( get_site_option( 'site_name' ) );
								$ip           = wp_unslash( $_SERVER['REMOTE_ADDR'] );
								$settingslink = esc_url( network_admin_url( 'settings.php' ) );
								$string       = str_replace(
									array( 'SITE_NAME', 'USERNAME', 'USERMAIL', 'IPADDRESS', 'SETTINGSLINK' ),
									array( $site_name, $user->user_login, $user->user_email, $ip, $settingslink ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['admin_mails']['newuser_notify_siteadmin'];
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message = replace_strings( $value['content'], array( $user ) );
								$msg     = $message;
							}
							// subject
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$subject = replace_strings( $value['subject'], array( $user ) );
								$msg    .= '::newuser_notify_siteadmin_subject::' . $subject;
							}
							return $msg;
						},
						99,
						2
					);
					add_filter(
						'wp_mail',
						function( $attributes ) {
							// debug('wp_mail');
							// debug($attributes);
							if ( strpos( $attributes['message'], '::newuser_notify_siteadmin_subject::' ) !== false ) {
								list($body, $subject)  = explode( '::newuser_notify_siteadmin_subject::', $attributes['message'] );
								$attributes['subject'] = $subject;
								$attributes['message'] = $body;
							}
							return $attributes;
						}
					);
					break;
				// multisite new blog
				case 'wpmu_new_site_admin_notification':
					// @see \wp-includes\ms-functions.php (1706ff)
					add_filter(
						'new_site_email',
						function( $new_site_email, $site, $user ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $site, $user ) = $atts;
								$site_name           = esc_html( get_site_option( 'site_name' ) );
								$blog_url            = get_site_url( $site->id );
								$blog_name           = get_blog_option( $site->id, 'blogname' );
								$string              = str_replace(
									array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'USERNAME', 'USERMAIL' ),
									array( $site_name, $blog_name, $blog_url, $user->user_login, $user->user_email ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['admin_mails']['wpmu_new_site_admin_notification'];
							// subject
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$subject                   = replace_strings( $value['subject'], array( $site, $user ) );
								$new_site_email['subject'] = $subject;
							}
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message                   = replace_strings( $value['content'], array( $site, $user ) );
								$new_site_email['message'] = $message;
							}
							return $new_site_email;
						},
						99,
						3
					);
					break;
				case 'newblog_notify_siteadmin':
					// @see \wp-includes\ms-functions.php (1417ff)
					add_filter(
						'newblog_notify_siteadmin',
						function( $msg, $blog_id ) {
							// replace function
							function replace_strings( $string, $atts ) {
								list( $blog_name, $blog_url ) = $atts;
								$site_name                    = esc_html( get_site_option( 'site_name' ) );
								$ip                           = wp_unslash( $_SERVER['REMOTE_ADDR'] );
								$settingslink                 = esc_url( network_admin_url( 'settings.php' ) );
								$string                       = str_replace(
									array( 'SITE_NAME', 'BLOG_NAME', 'BLOG_URL', 'IPADDRESS', 'SETTINGSLINK' ),
									array( $site_name, $blog_name, $blog_url, $ip, $settingslink ),
									$string
								);
								return $string;
							}
							// get settings
							$settings = Manage::get_global_settings( 'mails' );
							$value    = $settings['admin_mails']['newblog_notify_siteadmin'];
							// blog detail for replacing
							switch_to_blog( $blog_id );
							$blog_name = get_option( 'blogname' );
							$blog_url  = site_url();
							restore_current_blog();
							// message
							if ( isset( $value['content'] ) && ! empty( $value['content'] ) ) {
								$message = replace_strings( $value['content'], array( $blog_name, $blog_url ) );
								$msg     = $message;
							}
							// subject
							if ( isset( $value['subject'] ) && ! empty( $value['subject'] ) ) {
								$subject = replace_strings( $value['subject'], array( $blog_name, $blog_url ) );
								$msg    .= '::newblog_notify_siteadmin_subject::' . $subject;
							}
							return $msg;
						},
						99,
						2
					);
					add_filter(
						'wp_mail',
						function( $attributes ) {
							// debug('wp_mail');
							// debug($attributes);
							if ( strpos( $attributes['message'], '::newblog_notify_siteadmin_subject::' ) !== false ) {
								list($body, $subject)  = explode( '::newblog_notify_siteadmin_subject::', $attributes['message'] );
								$attributes['subject'] = $subject;
								$attributes['message'] = $body;
							}
							return $attributes;
						}
					);
					break;
				// multisite delete blog
				case 'delete_site_email_content':
					// @see \wp-admin\ms-delete-site.php (47ff)
					// todo
					break;
				// multisite change admin email
				case 'update_network_option_new_admin_email':
					// @see \wp-includes\ms-functions.php (2804ff)
					// todo
					break;
				case 'wp_network_admin_email_change_notification':
					// @see \wp-includes\ms-functions.php (2892ff)
					// todo
					break;
			}
		}

	}

	public static function get_blog_details( $blog = null ) {
		if ( function_exists( '\get_blog_details' ) ) {
			return \get_blog_details();
		} else {
			return (object) array(
				'blog_id'  => get_current_blog_id(),
				'domain'   => \Greyd\Helper::get_home_url(),
				'home'     => \Greyd\Helper::get_home_url(),
				'blogname' => get_bloginfo( 'name' ),
				'path'     => ABSPATH,
			);
		}
	}

	/*
	=======================================================================
		Render
	=======================================================================
	*/

	/**
	 * Render mail settings page
	 */
	public function render_mail_page() {

		// add overlay contents for testmail
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );

		// handle POST Data
		if ( ! empty( $_POST ) && isset( $_POST['submit'] ) ) {
			$response = $this->save_data( $_POST );
			// debug($response);
			if ( $response ) {
				echo Helper::show_message( __( 'Email settings saved.', 'greyd_hub' ), 'success inline' );
			} else {
				echo Helper::show_message( __( 'Unable to save email settings.', 'greyd_hub' ), 'error inline' );
			}
		}

		// current
		$current_user = wp_get_current_user();
		$current_blog = self::get_blog_details();

		// core defaults
		$blogname  = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$site_name = esc_html( get_site_option( 'site_name' ) );
		$site_url  = wp_parse_url( network_home_url(), PHP_URL_HOST );
		$from      = array(
			'name'    => 'WordPress',
			'address' => str_replace( '@www.', '@', 'wordpress@' . $site_url ),
			'alt'     => array(
				'name'    => $site_name,
				'address' => str_replace( '@www.', '@', 'support@' . $site_url ),
			),
			'alt2'    => array(
				'name'    => 'Site Admin',
				'address' => get_option( 'admin_email' ),
			),
		);
		$to        = array(
			'user'  => $current_user->data->user_email,
			'admin' => get_option( 'admin_email' ),
		);

		// all mail settings, details and defaults
		$mails = array(
			// smtp
			'smtp'        => Manage::get_settings( 'smtp' ),
			// emails header
			'from'        => array(
				'name'    => $this->get_default_value( 'from', 'name', $from ),
				'address' => $this->get_default_value( 'from', 'address', $from ),
			),
			'user_to'     => array(
				'to'  => $this->get_default_value( 'user_to', 'to', $to ),
				'cc'  => $this->get_default_value( 'user_to', 'cc' ),
				'bcc' => $this->get_default_value( 'user_to', 'bcc' ),
			),
			'admin_to'    => array(
				'to'  => $this->get_default_value( 'admin_to', 'to', $to ),
				'cc'  => $this->get_default_value( 'admin_to', 'cc' ),
				'bcc' => $this->get_default_value( 'admin_to', 'bcc' ),
			),
			// individual emails content
			'user_mails'  => array(),
			'admin_mails' => array(),
			// testmail setup
			'testmail'    => array(
				'to'   => $this->get_default_value( 'testmail', 'to' ),
				'user' => $this->get_default_value( 'testmail', 'user' ),
				'blog' => $this->get_default_value( 'testmail', 'blog' ),
			),
		);
		foreach ( $this->mail_slugs as $type => $slugs ) {
			foreach ( $slugs as $mail_slug ) {
				$default = $this->get_default_value( $type, $mail_slug );
				if ( ! $default ) {
					continue;
				}
				if ( ! is_multisite() && isset( $default['multisite'] ) ) {
					continue;
				}
				if ( is_multisite() && is_network_admin() && ! isset( $default['multisite'] ) ) {
					continue;
				}
				$mails[ $type . '_mails' ][ $mail_slug ] = $default;
			}
		}
		// debug($mails);

		//
		// title
		echo '<h1>' . __( 'Email settings', 'greyd_hub' ) . '</h1>';

		$this->add_meta_boxes();

		// form
		echo "<form id='mails-form' method='post'>";
		wp_nonce_field( 'greyd_users_mail' );

			// basic settings
			echo "<table class='form-table settings_table'>";

				// smtp
				$this->render_smtp_settings( $mails['smtp'] );

				// from/to header
				$this->render_header_settings( array( $mails['from'], $mails['user_to'], $mails['admin_to'] ) );

			echo '</table>';

			// emails
			echo "<div id='poststuff' class='metabox-holder'>";

				// render metaboxes
				echo "<div class='postbox-container'>";
					do_meta_boxes(
						'edit-mails',
						'normal',
						array(
							'type'  => 'user',
							'mails' => $mails['user_mails'],
						)
					);
					do_meta_boxes(
						'edit-admin-mails',
						'normal',
						array(
							'type'  => 'admin',
							'mails' => $mails['admin_mails'],
						)
					);
				echo '</div>';

			echo '</div>';

			// testmail settings
			echo "<table class='form-table settings_table'>";

				// testmail
				$this->render_testmail_settings( $mails['testmail'] );

			echo '</table>';

		// Submit
		echo submit_button() . '</form>';

	}

	/**
	 * Render SMTP settings
	 *
	 * @param array $settings   SMTP settings.
	 */
	public function render_smtp_settings( $settings ) {

		// get vars
		list( $enable, $host, $port, $auth, $username, $password, $encryption ) = array_values( $settings );
		$placeholder = array(
			'host'     => '',
			'port'     => 587,
			'username' => '',
			'password' => '',
		);

		// check global setting and use as default
		$info = '';
		if ( is_multisite() && ! is_network_admin() ) {
			$global_value   = Manage::get_global_settings( 'smtp' );
			$default_values = Manage::get_defaults();
			if ( $global_value && $global_value != $default_values['smtp'] ) {
				// debug($global_value);
				if ( $global_value['enable'] && ! empty( $global_value['host'] ) ) {
					// set placeholder
					$placeholder = array(
						'host'     => $global_value['host'],
						'port'     => $global_value['port'],
						'username' => $global_value['username'],
						'password' => str_repeat( '●', strlen( $global_value['password'] ) ),
					);
					// get global setup
					$text = array( '<strong>' . __( 'Host', 'greyd_hub' ) . ':</strong> ' . $placeholder['host'] . ' : ' . $placeholder['port'] );
					if ( $global_value['auth'] ) {
						$text[] = '<strong>' . __( 'Authentification', 'greyd_hub' ) . ':</strong> ' . $placeholder['username'] . ' / ' . $placeholder['password'];
					}
					if ( $global_value['encryption'] ) {
						$text[] = '<strong>' . __( 'Encryption', 'greyd_hub' ) . ':</strong> ' . $global_value['encryption'];
					}
					// make infobox
					$info = Helper::render_info_box(
						array(
							'text' => sprintf(
								__( 'Set in %1$sNetwork User Email Settings%2$s to the following values.', 'greyd_hub' ) . '<br>' .
								__( 'The setup can be individually overridden for this blog.', 'greyd_hub' ) . '<br>' .
								'<p>- ' . implode( '<br>- ', $text ) . '</p>',
								'<a href="' . network_admin_url( 'admin.php?page=greyd_user&tab=mail' ) . '" target="_blank">',
								'</a>'
							),
						)
					);
				}
			}
		}

		// smtp settings
		$toggle_enable = 'document.querySelector(".toggle_smtp").classList.toggle("hidden")';
		$toggle_auth   = 'document.querySelector(".toggle_smtp_auth").classList.toggle("hidden")';
		echo '<tr>
				<th>' . __( 'SMTP', 'greyd_hub' ) . "</th>
				<td>
					<label for='smtp[enable]'>
						<input type='checkbox' id='smtp[enable]' name='smtp[enable]' " . ( $enable ? "checked='checked'" : '' ) . " onchange='" . $toggle_enable . "' autocomplete='off'/>
						<span>" . __( 'Enable SMTP for PHPMailer', 'greyd_hub' ) . "</span><br>
						<small class='color_light'>" . __( 'Configurate an SMTP server to send emails via a verified address - and thus no longer end up in the spam folder.', 'greyd_hub' ) . "</small>
					</label>
					
					<div class='toggle_smtp " . ( $enable ? '' : 'hidden' ) . "'>

						<div class='flex_input'>
							<label for='smtp[host]'>
								<div>" . __( 'SMTP host', 'greyd_hub' ) . "</div>
								<input type='text' id='smtp[host]' name='smtp[host]' value='" . $host . "' placeholder='" . $placeholder['host'] . "' autocomplete='off'/>
							</label>

							<label for='smtp[port]' style='max-width:100px'>
								<div>" . __( 'SMTP port', 'greyd_hub' ) . "</div>
								<input type='number' id='smtp[port]' name='smtp[port]' value='" . $port . "' placeholder='" . $placeholder['port'] . "' step='1' min='0' max='65535' autocomplete='off'/>
							</label>
						</div>

						<label for='smtp[auth]'>
							<input type='checkbox' id='smtp[auth]' name='smtp[auth]' " . ( $auth ? "checked='checked'" : '' ) . " onchange='" . $toggle_auth . "' autocomplete='off'/>
							" . __( 'Enable authentification', 'greyd_hub' ) . "
						</label>

						<div class='toggle_smtp_auth " . ( $auth ? '' : 'hidden' ) . "'>
							<div class='flex_input'>
								<label for='smtp[username]'>
									<div>" . __( 'Username', 'greyd_hub' ) . "</div>
									<input type='text' id='smtp[username]' name='smtp[username]' value='" . $username . "' placeholder='" . $placeholder['username'] . "' autocomplete='off'/>
								</label>
								<label for='smtp[password]'>
									<div>" . __( 'Password', 'greyd_hub' ) . "</div>
									<input type='password' id='smtp[password]' name='smtp[password]' value='" . $password . "' placeholder='" . $placeholder['password'] . "' autocomplete='off'/>
								</label>
							</div>
						</div>

						<label for='smtp[encryption]'>
							<span style='display:block'>" . __( 'Encryption', 'greyd_hub' ) . ":</span><br>
							<div class='radio_input'>
								<label for='smtp[encryption]-false'>
									<input type='radio' name='smtp[encryption]' value='false' id='smtp[encryption]-false' " . ( $encryption == false ? "checked='checked'" : '' ) . "  autocomplete='off'/>
									<span>" . __( 'None', 'greyd_hub' ) . "</span>
								</label>
								<label for='smtp[encryption]-ssl'>
									<input type='radio' name='smtp[encryption]' value='ssl' id='smtp[encryption]-ssl' " . ( $encryption === 'ssl' ? "checked='checked'" : '' ) . "  autocomplete='off'/>
									<span>" . __( 'SSL', 'greyd_hub' ) . "</span>
								</label>
								<label for='smtp[encryption]-tls'>
									<input type='radio' name='smtp[encryption]' value='tls' id='smtp[encryption]-tls' " . ( $encryption === 'tls' ? "checked='checked'" : '' ) . "  autocomplete='off'/>
									<span>" . __( 'TLS', 'greyd_hub' ) . '</span>
								</label>
							</div>
						</label>
		
					</div>

					' . $info . '

				</td>
			</tr>';

	}

	/**
	 * Render From/To settings
	 *
	 * @param array $settings   Mail settings.
	 */
	public function render_header_settings( $settings ) {

		// get vars
		list( $from, $user_to, $admin_to ) = $settings;

		// sender settings
		echo '<tr>
				<th>' . __( 'Email sender', 'greyd_hub' ) . "</th>
				<td>
					<div class='flex_input'>
						<label for='mail_settings_from_name'>
							<div>" . $from['name']['title'] . "</div>
							<input type='text' name='from[name]' id='mail_settings_from_name' placeholder='" . $from['name']['default'] . "' value='" . $from['name']['value'] . "' autocomplete='off'>
							" . ( ! empty( $from['name']['description'] ) ? "<small class='color_light'>" . $from['name']['description'] . '</small>' : '' ) . "
						</label>
						<label for='mail_settings_from_address'>
							<div>" . $from['address']['title'] . "</div>
							<input type='email' name='from[address]' id='mail_settings_from_address' placeholder='" . $from['address']['default'] . "' value='" . $from['address']['value'] . "' autocomplete='off'>
							" . ( ! empty( $from['address']['description'] ) ? "<small class='color_light'>" . $from['address']['description'] . '</small>' : '' ) . '
						</label>
					</div>
				</td>
			</tr>';

		// receiver user mails
		$initial_open      = $user_to['cc']['default'] != '' || $user_to['bcc']['default'] != '' || $user_to['cc']['value'] != '' || $user_to['bcc']['value'] != '';
		$toggle_less_class = $initial_open ? '' : 'hidden';
		$toggle_more_class = ! $initial_open ? '' : 'hidden';
		echo '<tr>
				<th>' . __( 'Receiver of user emails', 'greyd_hub' ) . "</th>
				<td>
					<label for='mail_settings_user_to_to'>
						<div>" . $user_to['to']['title'] . "</div>
						<input style='width:100%' type='email' id='mail_settings_user_to_to' placeholder='" . $user_to['to']['default'] . "' value='' autocomplete='off' disabled>
						" . ( ! empty( $user_to['to']['description'] ) ? "<small class='color_light'>" . $user_to['to']['description'] . '</small>' : '' ) . "
					</label>

					<span class='toggle_more " . $toggle_more_class . "'>" . __( 'Show more recipients', 'greyd_hub' ) . " 🡣</span>
					<span class='toggle_less " . $toggle_less_class . "'>" . __( 'Show less recipients', 'greyd_hub' ) . " 🡡</span><br><br>
					<div class='toggle_element " . $toggle_less_class . "'>
						<label for='mail_settings_user_to_cc'>
							<div>" . $user_to['cc']['title'] . "</div>
							<input style='width:100%' type='text' name='user_to[cc]' id='mail_settings_user_to_cc' placeholder='" . $user_to['cc']['default'] . "' value='" . $user_to['cc']['value'] . "' autocomplete='off'>
							" . ( ! empty( $user_to['cc']['description'] ) ? "<small class='color_light'>" . $user_to['cc']['description'] . '</small>' : '' ) . "
						</label>
						<label for='mail_settings_user_to_bcc'>
							<div>" . $user_to['bcc']['title'] . "</div>
							<input style='width:100%' type='text' name='user_to[bcc]' id='mail_settings_user_to_bcc' placeholder='" . $user_to['bcc']['default'] . "' value='" . $user_to['bcc']['value'] . "' autocomplete='off'>
							" . ( ! empty( $user_to['bcc']['description'] ) ? "<small class='color_light'>" . $user_to['bcc']['description'] . '</small>' : '' ) . '
						</label>
					</div>

				</td>
			</tr>';

		// receiver admin mails
		$initial_open      = $admin_to['cc']['default'] != '' || $admin_to['bcc']['default'] != '' || $admin_to['cc']['value'] != '' || $admin_to['bcc']['value'] != '';
		$toggle_less_class = $initial_open ? '' : 'hidden';
		$toggle_more_class = ! $initial_open ? '' : 'hidden';
		echo '<tr>
				<th>' . __( 'Receiver of admin emails', 'greyd_hub' ) . "</th>
				<td>
					<label for='mail_settings_admin_to_to'>
						<div>" . $admin_to['to']['title'] . "</div>
						<input style='width:100%' type='email' name='admin_to[to]' id='mail_settings_admin_to_to' placeholder='" . $admin_to['to']['default'] . "' value='" . $admin_to['to']['value'] . "' autocomplete='off'>
						" . ( ! empty( $admin_to['to']['description'] ) ? "<small class='color_light'>" . $admin_to['to']['description'] . '</small>' : '' ) . "
					</label>

					<span class='toggle_more " . $toggle_more_class . "'>" . __( 'Show more recipients', 'greyd_hub' ) . " 🡣</span>
					<span class='toggle_less " . $toggle_less_class . "'>" . __( 'Show less recipients', 'greyd_hub' ) . " 🡡</span><br><br>
					<div class='toggle_element " . $toggle_less_class . "'>
						<label for='mail_settings_admin_to_cc'>
							<div>" . $admin_to['cc']['title'] . "</div>
							<input style='width:100%' type='text' name='admin_to[cc]' id='mail_settings_admin_to_cc' placeholder='" . $admin_to['cc']['default'] . "' value='" . $admin_to['cc']['value'] . "' autocomplete='off'>
							" . ( ! empty( $admin_to['cc']['description'] ) ? "<small class='color_light'>" . $admin_to['cc']['description'] . '</small>' : '' ) . "
						</label>
						<label for='mail_settings_admin_to_bcc'>
							<div>" . $admin_to['bcc']['title'] . "</div>
							<input style='width:100%' type='text' name='admin_to[bcc]' id='mail_settings_admin_to_bcc' placeholder='" . $admin_to['bcc']['default'] . "' value='" . $admin_to['bcc']['value'] . "' autocomplete='off'>
							" . ( ! empty( $admin_to['bcc']['description'] ) ? "<small class='color_light'>" . $admin_to['bcc']['description'] . '</small>' : '' ) . '
						</label>
					</div>

				</td>
			</tr>';

	}

	/**
	 * Render Testmail settings
	 *
	 * @param array $settings   Testmail settings.
	 */
	public function render_testmail_settings( $settings ) {

		// get user options
		$current_user = wp_get_current_user();
		$user_options = "<option value=''>ID " . $current_user->data->ID . ' | ' . $current_user->data->user_login . ' &lt;' . $current_user->data->user_email . '&gt;</option>';
		$users        = is_multisite() && is_network_admin() ? get_users( array( 'blog_id' => 0 ) ) : get_users();
		foreach ( $users as $user ) {
			// debug($user->data);
			if ( $user->data->ID == $current_user->data->ID ) {
				continue;
			}
			$sel           = $user->data->ID == $settings['user']['value'] ? 'selected' : '';
			$user_options .= "<option value='" . $user->data->ID . "' " . $sel . '>ID ' . $user->data->ID . ' | ' . $user->data->user_login . ' &lt;' . $user->data->user_email . '&gt;</option>';
		}
		// get blog options
		$current_blog = self::get_blog_details();
		$blog_options = "<option value=''>ID " . $current_blog->blog_id . ' | ' . $current_blog->blogname . ' | ' . $current_blog->home . '</option>';

		$sites = function_exists( 'get_sites' ) ? get_sites( array( 'number' => 999 ) ) : array( self::get_blog_details() );

		foreach ( $sites as $blog ) {
			// debug($blog);
			if ( $blog->blog_id == $current_blog->blog_id ) {
				continue;
			}
			$sel           = $blog->blog_id == $settings['blog']['value'] ? 'selected' : '';
			$blog_options .= "<option value='" . $blog->blog_id . "' " . $sel . '>ID ' . $blog->blog_id . ' | ' . $blog->blogname . ' | ' . $blog->home . '</option>';
		}

		// testmail
		$testmail_data  = 'data-testmail="settings_test_mail" ';
		$testmail_data .= 'data-testmail_type="user" ';
		$testmail_data .= 'data-testmail_mode="' . ( is_multisite() && is_network_admin() ) . '" ';
		$testmail_data .= 'data-testmail_to="' . $this->get_value( 'testmail', 'to' ) . '"';
		$testmail       = '<span class="button button-ghost small">' . __( 'Send test email', 'greyd_hub' ) . ' <span class="testmail dashicons dashicons-visibility" ' . $testmail_data . '></span></span>';

		// testmail settings
		echo '<tr>
				<th>
					' . __( 'Test emails', 'greyd_hub' ) . '
					<small>' . __( 'Only used for preview and sending of test emails on this page.', 'greyd_hub' ) . '</small>
					<small>' . __( 'These settings are not used in any other context.', 'greyd_hub' ) . '</small>
					' . $testmail . "
				</th>
				<td>
					<label for='mail_settings_testmail_to'>
						<div>" . $settings['to']['title'] . "</div>
						<input style='width:100%' type='email' name='testmail[to]' id='mail_settings_testmail_to' placeholder='" . $settings['to']['default'] . "' value='" . $settings['to']['value'] . "' autocomplete='off'>
						" . ( ! empty( $settings['to']['description'] ) ? "<small class='color_light'>" . $settings['to']['description'] . '</small>' : '' ) . "
					</label>
					<div class='flex_input'>
						<label for='mail_settings_testmail_user'>
							<div>" . $settings['user']['title'] . "</div>
							<select style='width:100%' name='testmail[user]' id='mail_settings_testmail_user' autocomplete='off'>" . $user_options . '</select>
							' . ( ! empty( $settings['user']['description'] ) ? "<small class='color_light'>" . $settings['user']['description'] . '</small>' : '' ) . "
						</label>
						<label for='mail_settings_testmail_blog'>
							<div>" . $settings['blog']['title'] . "</div>
							<select style='width:100%' name='testmail[blog]' id='mail_settings_testmail_blog' autocomplete='off'>" . $blog_options . '</select>
							' . ( ! empty( $settings['blog']['description'] ) ? "<small class='color_light'>" . $settings['blog']['description'] . '</small>' : '' ) . '
						</label>
					</div>
				</td>
			</tr>';

	}

	/*
	=======================================================================
		Metaboxes
	=======================================================================
	*/

	/**
	 * Add Metaboxes
	 */
	public function add_meta_boxes() {

		// metaboxes
		add_meta_box(
			'greyd_mails_user', // ID
			__( 'Emails sent to the user', 'greyd_hub' ), // Title
			array( $this, 'render_mails_box' ), // Callback
			'edit-mails', // screen name
			'normal'
		);
		add_meta_box(
			'greyd_mails_admin', // ID
			__( 'Emails sent to the admin (site owner)', 'greyd_hub' ), // Title
			array( $this, 'render_mails_box' ), // Callback
			'edit-admin-mails', // screen name
			'normal'
		);

	}

	/**
	 * Render an emails metabox.
	 *
	 * @param array $args
	 *      @property string type
	 *      @property mixed[] mails
	 */
	public function render_mails_box( $args ) {

		$mails = $args['mails'];
		$name  = $args['type'] . '_mails';
		// debug($mails);

		$menu    = '<li data-tab="all" class="active"><big>' . __( 'All emails', 'greyd_hub' ) . '</big></li>';
		$content = '';

		foreach ( $mails as $mail_slug => $mail ) {
			// sanitize
			$mail = array_replace_recursive(
				array(
					'no_testmail' => false,
					'disabled'    => false,
					'multisite'   => false,
					'title'       => __( 'Title', 'greyd_hub' ),
					'description' => '',
					'subject'     => array(
						'default' => __( 'Subject', 'greyd_hub' ),
						'value'   => '',
					),
					'content'     => array(
						'default'     => __( 'Message', 'greyd_hub' ),
						'value'       => '',
						'placeholder' => array(),
					),
				),
				$mail
			);

						$menu_disabled = '';
			$disabled                  = '';
			// menu icon
			$icon = '';
			// menu icon after
			$icon_after = '';
			// testmail
			$testmail = '';
			// description
			$description = $mail['description'];
			// inserts
			$insert_subject = '';
			$insert_message = '';
			// placeholder
			$placeholder = '';

						$disabled = $mail['disabled'] ? 'disabled' : '';
			// wip
			if ( isset( $mail['work_in_progress'] ) && $mail['work_in_progress'] == true ) {
				// disable
				$disabled = 'disabled';
				// menu icon after
				$icon_after = ' 🚧';
				// description
				if ( $description != '' ) {
					$description .= '<br><br>';
				}
				$description .= '🚧 ' . __( 'Sorry, this email can not be edited yet.', 'greyd_hub' ) . ' 🚧';
			}
			if ( $mail['multisite'] ) {
				// menu icon
				$icon = '<span class="dashicons dashicons-admin-multisite"></span> ';
				if ( is_multisite() && ! is_network_admin() ) {
					// description
					if ( strpos( $description, 'network/settings' ) === false ) {
						if ( $description != '' ) {
							$description .= '<br><br>';
						}
						$settings_url = network_admin_url( 'admin.php?page=greyd_user&tab=mail' );
						$description .= sprintf( __( 'You can edit this email in the %1$sNetwork User Email Settings%2$s.', 'greyd_hub' ), '<a href="' . $settings_url . '" target="_blank">', '</a>' );
					}
					// disable
					$menu_disabled = 'disabled';
					$disabled      = 'disabled';
				}
			} elseif ( is_network_admin() ) {
				// description
				if ( $description != '' ) {
					$description .= '<br><br>';
				}
				$description .= __( 'You can edit this email for each blog individually.', 'greyd_hub' );
				// disable
				$disabled = 'disabled';
			}
			if ( $disabled == '' ) {
				// inserts
				$insert_subject = "<small class='insert_subject'>" . __( 'Insert default subject', 'greyd_hub' ) . ' 🡣</small>';
				$insert_message = "<small class='insert_message'>" . __( 'Insert default message', 'greyd_hub' ) . ' 🡣</small>';
			}
			if ( $mail['no_testmail'] == false ) {
				// testmail
				$testmail_data  = "data-testmail='" . $mail_slug . "' ";
				$testmail_data .= "data-testmail_type='" . $args['type'] . "' ";
				$testmail_data .= "data-testmail_mode='" . ( is_multisite() && is_network_admin() ) . "' ";
				$testmail_data .= "data-testmail_to='" . $this->get_value( 'testmail', 'to' ) . "'";
				$testmail       = "<span class='button button-ghost small'><span class='testmail dashicons dashicons-visibility' title='" . __( 'Preview test email', 'greyd_hub' ) . "' " . $testmail_data . '></span></span>';
			}
			if ( count( $mail['content']['placeholder'] ) > 0 ) {
				// placeholder
				$placeholder = "<small class='color_light'>" . sprintf( __( 'Use %s as placeholders.', 'greyd_hub' ), implode( ', ', $mail['content']['placeholder'] ) ) . '</small>';
			}

			// menu item
			$changed = $mail['subject']['value'] != '' || $mail['content']['value'] != '' ? 'changed' : '';
			$menu   .= '<li data-tab="' . $mail_slug . '" class="' . $menu_disabled . ' ' . $changed . '">' . $icon . '<big>' . $mail['title'] . '</big>' . $icon_after . '</li>';
			// content
			$content .= "<li data-tab='" . $mail_slug . "'>

							<h3>" . $mail['title'] . ' ' . $testmail . '</h3>
							<p>' . $description . "</p>

							<label for='" . $name . '_' . $mail_slug . "_subject' class='" . $disabled . "'>
								<div>" . __( 'Subject', 'greyd_hub' ) . '</div>
								' . $insert_subject . "
								<input style='width:100%' type='text' name='" . $name . '[' . $mail_slug . "][subject]' id='" . $name . '_' . $mail_slug . "_subject' placeholder='" . $mail['subject']['default'] . "' value='" . $mail['subject']['value'] . "' autocomplete='off' " . $disabled . ">
							</label><br>

							<label for='" . $name . '_' . $mail_slug . "_content' class='" . $disabled . "'>
								<div>" . __( 'Message', 'greyd_hub' ) . '</div>
								' . $insert_message . "
								<textarea name='" . $name . '[' . $mail_slug . "][content]' id='" . $name . '_' . $mail_slug . "_content' rows='12' placeholder='" . $mail['content']['default'] . "' autocomplete='off' " . $disabled . '>' . $mail['content']['value'] . '</textarea>
								' . $placeholder . '
							</label>

						</li>';
		}

		// render
		echo '<table class="greyd_table vertical meta_table tabs_table" style="min-height:485px">';

			echo '<tr id="user_mails">
					<th><ul>' . $menu . '</ul></th>
					<td><div class="items_ul"><ul>' . $content . '</ul></div></td>
				</tr>';

		echo '</table>';

	}

	/*
	=======================================================================
		Save
	=======================================================================
	*/

	/**
	 * handle POST Data for saving
	 *
	 * @param array $post_data  Raw $_POST data.
	 */
	public function save_data( $post_data ) {

		// verify nonce
		$nonce = isset( $post_data['_wpnonce'] ) ? $post_data['_wpnonce'] : null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'greyd_users_mail' ) ) {
			return false;
		}

		// debug("save mails:");
		// debug($post_data);
		// return false;

		// stmp
		if ( isset( $post_data['smtp'] ) ) {
			$smtp               = $post_data['smtp'];
			$smtp['enable']     = isset( $smtp['enable'] ) && $smtp['enable'] === 'on' ? true : false;
			$smtp['auth']       = isset( $smtp['auth'] ) && $smtp['auth'] === 'on' ? true : false;
			$smtp['encryption'] = $smtp['encryption'] === 'false' ? false : $smtp['encryption'];
			// get old settings
			$old = Manage::get_settings( 'smtp' );
			if ( $smtp != $old ) {
				// debug($smtp);
				// debug($old);
				Manage::save_settings( 'smtp', $smtp );
			}
		}

		// make new settings
		$mails = array();
		// debug($mails);

		// from
		if ( isset( $post_data['from'] ) ) {
			// debug($post_data["from"]);
			$mails['from'] = $post_data['from'];
			if ( empty( $mails['from']['name'] ) && empty( $mails['from']['address'] ) ) {
				unset( $mails['from'] );
			}
		}
		// user_to
		if ( isset( $post_data['user_to'] ) ) {
			// debug($post_data["user_to"]);
			$mails['user_to'] = $post_data['user_to'];
			if ( empty( $mails['user_to']['cc'] ) && empty( $mails['user_to']['bcc'] ) ) {
				unset( $mails['user_to'] );
			}
		}
		// admin_to
		if ( isset( $post_data['admin_to'] ) ) {
			// debug($post_data["admin_to"]);
			$mails['admin_to'] = $post_data['admin_to'];
			if ( empty( $mails['admin_to']['to'] ) && empty( $mails['admin_to']['cc'] ) && empty( $mails['admin_to']['bcc'] ) ) {
				unset( $mails['admin_to'] );
			}
		}

		// user_mails
		if ( isset( $post_data['user_mails'] ) ) {
			// debug($post_data["user_mails"]);
			$user_mails = array();
			foreach ( $post_data['user_mails'] as $mail_slug => $mail ) {
				if ( empty( $mail['subject'] ) && empty( $mail['content'] ) ) {
					continue;
				}
				$user_mails[ $mail_slug ] = $mail;
			}
			if ( empty( $user_mails ) ) {
				unset( $mails['user_mails'] );
			} else {
				$mails['user_mails'] = $user_mails;
			}
		}
		// admin_mails
		if ( isset( $post_data['admin_mails'] ) ) {
			// debug($post_data["admin_mails"]);
			$admin_mails = array();
			foreach ( $post_data['admin_mails'] as $mail_slug => $mail ) {
				if ( empty( $mail['subject'] ) && empty( $mail['content'] ) ) {
					continue;
				}
				$admin_mails[ $mail_slug ] = $mail;
			}
			if ( empty( $admin_mails ) ) {
				unset( $mails['admin_mails'] );
			} else {
				$mails['admin_mails'] = $admin_mails;
			}
		}

		// testmail
		if ( isset( $post_data['testmail'] ) ) {
			// debug($post_data["admin_to"]);
			$mails['testmail'] = $post_data['testmail'];
			if ( empty( $mails['testmail']['to'] ) && empty( $mails['testmail']['user'] ) && empty( $mails['testmail']['blog'] ) ) {
				unset( $mails['testmail'] );
			}
		}

		// save
		if ( empty( $mails ) ) {
			$mails = '';
		}
		return Manage::save_settings( 'mails', $mails );
	}

}
