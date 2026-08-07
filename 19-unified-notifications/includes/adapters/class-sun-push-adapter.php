<?php
/**
 * Provider-neutral private web/push adapter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Push_Adapter implements SUN_Delivery_Adapter {
	/** @return string */
	public function channel() {
		return 'push';
	}

	/**
	 * @param array<string,mixed> $delivery Delivery.
	 * @param array<string,mixed> $notification Notification.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send( array $delivery, array $notification ) {
		global $wpdb;
		if ( ! SUN_Operational_Gate::allows( 'external_delivery' ) ) {
			return new WP_Error( 'sun_external_delivery_contained', __( 'External notification delivery is temporarily contained.', 'sabri-unified-notifications' ) );
		}
		if ( SUN_Provider_Circuit::is_open( 'push' ) ) {
			return new WP_Error( 'sun_provider_circuit_open', __( 'Push delivery is temporarily paused after repeated provider failures.', 'sabri-unified-notifications' ) );
		}
		$devices = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT public_id,provider,platform,token_ciphertext FROM " . SUN_Database::table( 'devices' ) . " WHERE user_id=%d AND status='active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 20",
				(int) $delivery['recipient_id'], SUN_Database::now()
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( empty( $devices ) ) {
			return array( 'status' => 'suppressed', 'reason' => 'no_active_device' );
		}
		$payload = array(
			'title' => (string) ( $notification['external']['push']['title'] ?? __( 'New private update', 'sabri-unified-notifications' ) ),
			'body'  => (string) ( $notification['external']['push']['body'] ?? __( 'Sign in to review it securely.', 'sabri-unified-notifications' ) ),
			'url'   => SUN_Deep_Link::wrapper_url( $notification['public_id'] ),
			'tag'   => 'sun-' . substr( $notification['dedupe_key'], 0, 24 ),
		);
		$sent = 0;
		$ids  = array();
		$provider_failed = false;
		foreach ( $devices as $device ) {
			$token = SUN_Crypto::decrypt( $device['token_ciphertext'] );
			if ( is_wp_error( $token ) ) {
				continue;
			}
			$result = apply_filters( 'sun_send_push', null, $device, json_decode( $token, true ), $payload, $delivery, $notification );
			if ( is_wp_error( $result ) ) {
				$provider_failed = true;
				continue;
			}
			if ( is_array( $result ) && ! empty( $result['accepted'] ) ) {
				++$sent;
				if ( ! empty( $result['provider_message_id'] ) ) {
					$ids[] = sanitize_text_field( $result['provider_message_id'] );
				}
			}
		}
		if ( 0 === $sent ) {
			if ( $provider_failed || (bool) apply_filters( 'sun_push_adapter_configured', false ) ) {
				SUN_Provider_Circuit::record_failure( 'push' );
				return new WP_Error( 'sun_push_rejected', __( 'The push provider did not accept the notification.', 'sabri-unified-notifications' ) );
			}
			return array( 'status' => 'suppressed', 'reason' => 'provider_unconfigured' );
		}
		SUN_Provider_Circuit::record_success( 'push' );
		return array(
			'status'              => 'accepted',
			'provider'            => 'filtered-push-adapter',
			'provider_message_id' => implode( ',', array_slice( $ids, 0, 5 ) ),
		);
	}

	/** @return array<string,mixed> */
	public function health() {
		$circuit = SUN_Provider_Circuit::health();
		return array(
			'channel'       => 'push',
			'configured'    => (bool) apply_filters( 'sun_push_adapter_configured', false ),
			'provider'      => (string) apply_filters( 'sun_push_provider_name', 'not-configured' ),
			'vapid_key'     => (bool) apply_filters( 'sun_push_public_key', '' ),
			'circuit_open'  => ! empty( $circuit['push']['open'] ),
		);
	}
}
