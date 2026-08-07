<?php
/**
 * Optional verified-phone SMS adapter with strict safe-content limits.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_SMS_Adapter implements SUN_Delivery_Adapter {
	/** @var SUN_Auth */
	private $auth;

	/** @param SUN_Auth $auth Auth. */
	public function __construct( SUN_Auth $auth ) {
		$this->auth = $auth;
	}

	/** @return string */
	public function channel() {
		return 'sms';
	}

	/**
	 * @param array<string,mixed> $delivery Delivery.
	 * @param array<string,mixed> $notification Notification.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send( array $delivery, array $notification ) {
		if ( ! SUN_Operational_Gate::allows( 'external_delivery' ) ) {
			return new WP_Error( 'sun_external_delivery_contained', __( 'External notification delivery is temporarily contained.', 'sabri-unified-notifications' ) );
		}
		if ( SUN_Provider_Circuit::is_open( 'sms' ) ) {
			return new WP_Error( 'sun_provider_circuit_open', __( 'SMS delivery is temporarily paused after repeated provider failures.', 'sabri-unified-notifications' ) );
		}
		$claims = $this->auth->assertions( (int) $delivery['recipient_id'] );
		if ( empty( $claims['phone_verified'] ) ) {
			return array( 'status' => 'suppressed', 'reason' => 'phone_unverified' );
		}
		$phone = (string) apply_filters( 'sun_verified_phone_for_user', '', (int) $delivery['recipient_id'], $claims );
		if ( ! preg_match( '/^\+[1-9][0-9]{7,14}$/', $phone ) ) {
			return array( 'status' => 'suppressed', 'reason' => 'phone_unavailable' );
		}
		$body = (string) ( $notification['external']['sms']['body'] ?? __( 'You have a new private update. Sign in to review it.', 'sabri-unified-notifications' ) );
		$body = mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $body ) ) ), 0, 320 );
		$result = apply_filters( 'sun_send_sms', null, $phone, $body, $delivery, $notification );
		if ( is_wp_error( $result ) ) {
			SUN_Provider_Circuit::record_failure( 'sms' );
			return $result;
		}
		if ( ! is_array( $result ) || empty( $result['accepted'] ) ) {
			if ( (bool) apply_filters( 'sun_sms_adapter_configured', false ) ) {
				SUN_Provider_Circuit::record_failure( 'sms' );
				return new WP_Error( 'sun_sms_rejected', __( 'The SMS provider did not accept the notification.', 'sabri-unified-notifications' ) );
			}
			return array( 'status' => 'suppressed', 'reason' => 'provider_unconfigured' );
		}
		SUN_Provider_Circuit::record_success( 'sms' );
		return array(
			'status'              => 'accepted',
			'provider'            => sanitize_key( (string) ( $result['provider'] ?? 'filtered-sms-adapter' ) ),
			'provider_message_id' => sanitize_text_field( (string) ( $result['provider_message_id'] ?? '' ) ),
		);
	}

	/** @return array<string,mixed> */
	public function health() {
		$circuit = SUN_Provider_Circuit::health();
		return array(
			'channel'       => 'sms',
			'configured'    => (bool) apply_filters( 'sun_sms_adapter_configured', false ),
			'provider'      => (string) apply_filters( 'sun_sms_provider_name', 'not-configured' ),
			'circuit_open'  => ! empty( $circuit['sms']['open'] ),
		);
	}
}
