<?php
/**
 * TextBee SMS provider bridge for File 19.
 *
 * Credentials are read only from wp-config.php constants. They are never stored
 * in WordPress options, logs, audit context or repository source.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_TextBee_Provider {
	private const ENDPOINT = 'https://api.textbee.dev/api/v1/gateway/send-sms';

	/** @return void */
	public static function register() {
		add_filter( 'sun_sms_adapter_configured', array( __CLASS__, 'configured' ), 20 );
		add_filter( 'sun_sms_provider_name', array( __CLASS__, 'provider_name' ), 20 );
		add_filter( 'sun_send_sms', array( __CLASS__, 'send' ), 20, 5 );
	}

	/** @param bool $configured Existing provider state. @return bool */
	public static function configured( $configured ) {
		return (bool) $configured || '' !== self::api_key();
	}

	/** @param string $provider Existing provider name. @return string */
	public static function provider_name( $provider ) {
		return '' !== self::api_key() ? 'textbee' : (string) $provider;
	}

	/**
	 * @param mixed $result Existing provider result.
	 * @param string $phone E.164 recipient.
	 * @param string $body SMS body.
	 * @param array<string,mixed> $delivery Delivery context.
	 * @param array<string,mixed> $notification Notification context.
	 * @return mixed
	 */
	public static function send( $result, $phone, $body, array $delivery, array $notification ) {
		unset( $delivery, $notification );
		if ( null !== $result ) {
			return $result;
		}

		$api_key = self::api_key();
		if ( '' === $api_key ) {
			return null;
		}
		if ( ! preg_match( '/^\+[1-9][0-9]{7,14}$/', (string) $phone ) ) {
			return new WP_Error( 'sun_textbee_phone_invalid', __( 'The SMS recipient number is invalid.', 'sabri-unified-notifications' ) );
		}

		$payload = array(
			'recipients' => array( (string) $phone ),
			'message'    => (string) $body,
		);
		$device_id = self::device_id();
		if ( '' !== $device_id ) {
			$payload['deviceId'] = $device_id;
		}
		$sim_subscription_id = self::sim_subscription_id();
		if ( null !== $sim_subscription_id ) {
			$payload['simSubscriptionId'] = $sim_subscription_id;
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout'     => 15,
				'redirection' => 0,
				'sslverify'   => true,
				'headers'     => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
					'x-api-key'    => $api_key,
				),
				'body'        => wp_json_encode( $payload ),
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'sun_textbee_transport_error',
				__( 'The SMS provider could not be reached.', 'sabri-unified-notifications' ),
				array( 'cause' => sanitize_key( (string) $response->get_error_code() ) )
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'sun_textbee_http_error',
				__( 'The SMS provider rejected the request.', 'sabri-unified-notifications' ),
				array( 'status' => $status )
			);
		}

		$raw = (string) wp_remote_retrieve_body( $response );
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || ! is_array( $decoded['data'] ?? null ) ) {
			return new WP_Error( 'sun_textbee_response_invalid', __( 'The SMS provider returned an invalid response.', 'sabri-unified-notifications' ) );
		}
		$data = $decoded['data'];
		$success_count = isset( $data['successCount'] ) ? (int) $data['successCount'] : 0;
		$failure_count = isset( $data['failureCount'] ) ? (int) $data['failureCount'] : 0;
		$provider_receipt = sanitize_text_field( (string) ( $data['smsBatchId'] ?? '' ) );
		$accepted = ! empty( $data['success'] ) || '' !== $provider_receipt || ( $success_count > 0 && 0 === $failure_count );
		if ( ! $accepted ) {
			return new WP_Error( 'sun_textbee_rejected', __( 'The SMS provider did not accept the message.', 'sabri-unified-notifications' ) );
		}

		if ( '' === $provider_receipt ) {
			$provider_receipt = 'textbee-accepted-' . substr( hash( 'sha256', $raw . '|' . (string) $phone . '|' . (string) microtime( true ) ), 0, 32 );
		}

		return array(
			'accepted'            => true,
			'provider'            => 'textbee',
			'provider_message_id' => substr( $provider_receipt, 0, 191 ),
		);
	}

	/** @return string */
	private static function api_key() {
		return defined( 'SUN_TEXTBEE_API_KEY' ) ? trim( (string) constant( 'SUN_TEXTBEE_API_KEY' ) ) : '';
	}

	/** @return string */
	private static function device_id() {
		if ( ! defined( 'SUN_TEXTBEE_DEVICE_ID' ) ) {
			return '';
		}
		return substr( sanitize_text_field( (string) constant( 'SUN_TEXTBEE_DEVICE_ID' ) ), 0, 191 );
	}

	/** @return int|null */
	private static function sim_subscription_id() {
		if ( ! defined( 'SUN_TEXTBEE_SIM_SUBSCRIPTION_ID' ) ) {
			return null;
		}
		$value = (int) constant( 'SUN_TEXTBEE_SIM_SUBSCRIPTION_ID' );
		return $value > 0 ? $value : null;
	}
}
