<?php
/**
 * Transactional email adapter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Email_Adapter implements SUN_Delivery_Adapter {
	/** @var SUN_Auth */
	private $auth;
	/** @var SUN_Template_Engine */
	private $templates;

	/** @param SUN_Auth $auth Auth. @param SUN_Template_Engine $templates Templates. */
	public function __construct( SUN_Auth $auth, SUN_Template_Engine $templates ) {
		$this->auth      = $auth;
		$this->templates = $templates;
	}

	/** @return string */
	public function channel() {
		return 'email';
	}

	/**
	 * @param array<string,mixed> $delivery Delivery.
	 * @param array<string,mixed> $notification Notification.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send( array $delivery, array $notification ) {
		$user   = get_userdata( (int) $delivery['recipient_id'] );
		$claims = $this->auth->assertions( (int) $delivery['recipient_id'] );
		if ( ! $user || empty( $claims['email_verified'] ) || ! is_email( $user->user_email ) ) {
			return array( 'status' => 'suppressed', 'reason' => 'email_unverified' );
		}
		$rendered = $notification['external']['email'] ?? array( 'title' => $notification['title'], 'body' => $notification['summary'] );
		$subject  = trim( preg_replace( '/[\r\n]+/', ' ', (string) $rendered['title'] ) );
		$body     = (string) $rendered['body'];
		$open_url = SUN_Deep_Link::wrapper_url( $notification['public_id'] );
		$token    = SUN_Crypto::sign_token(
			array(
				'purpose'  => 'unsubscribe',
				'user_id'  => (int) $delivery['recipient_id'],
				'category' => $notification['category'],
				'channel'  => 'email',
			),
			30 * DAY_IN_SECONDS
		);
		$unsubscribe = home_url( '/notifications/unsubscribe/' . rawurlencode( $token ) . '/' );
		$message     = $body . "\n\n" . __( 'Open securely:', 'sabri-unified-notifications' ) . ' ' . $open_url . "\n" . __( 'Notification settings:', 'sabri-unified-notifications' ) . ' ' . home_url( '/settings/notifications/' ) . "\n" . __( 'Unsubscribe from this category and channel:', 'sabri-unified-notifications' ) . ' ' . $unsubscribe;
		$headers     = array( 'Content-Type: text/plain; charset=UTF-8' );
		$headers[]   = 'List-Unsubscribe: <' . esc_url_raw( $unsubscribe ) . '>';
		$result      = wp_mail( $user->user_email, $subject, $message, $headers );
		if ( ! $result ) {
			return new WP_Error( 'sun_email_rejected', __( 'The email provider did not accept the message.', 'sabri-unified-notifications' ) );
		}
		return array(
			'status'              => 'accepted',
			'provider'            => 'wp_mail',
			'provider_message_id' => '',
		);
	}

	/** @return array<string,mixed> */
	public function health() {
		return array(
			'channel'    => 'email',
			'configured' => (bool) apply_filters( 'sun_email_adapter_configured', true ),
			'provider'   => 'wp_mail',
		);
	}
}
