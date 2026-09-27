<?php
/**
 * File 01 platform event-backbone adapter for File 19.
 *
 * File 19 owns notification facts only. Domain truth remains with native owners.
 * This adapter publishes only privacy-minimized notification facts through the
 * canonical File 01 reliable event bus when that dependency is available.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Platform_Events {
	const CONTRACT_VERSION = '1.0.0';

	/** @return bool */
	public static function backbone_available() {
		return class_exists( 'SPF_Event_Bus' ) && is_callable( array( 'SPF_Event_Bus', 'publish' ) );
	}

	/** @return array<string,array<string,mixed>> */
	public static function contracts() {
		return array(
			'NotificationCreated.v1' => array(
				'aggregate_type' => 'notification',
				'privacy_class'  => 'internal',
				'retention'      => 'File 01 event-backbone policy; File 19 operational audit policy.',
				'required'       => array( 'notification_public_id', 'category', 'priority', 'producer', 'source_event_id', 'status' ),
			),
			'NotificationRead.v1' => array(
				'aggregate_type' => 'notification',
				'privacy_class'  => 'internal',
				'retention'      => 'File 01 event-backbone policy; File 19 operational audit policy.',
				'required'       => array( 'notification_public_id', 'status' ),
			),
			'NotificationDeliveryFailed.v1' => array(
				'aggregate_type' => 'notification_delivery',
				'privacy_class'  => 'restricted',
				'retention'      => 'Operational/audit policy; no notification body or recipient contact data.',
				'required'       => array( 'delivery_public_id', 'channel', 'attempt', 'terminal', 'error_code' ),
			),
			'NotificationPreferenceChanged.v1' => array(
				'aggregate_type' => 'notification_preference',
				'privacy_class'  => 'restricted',
				'retention'      => 'Account-life preference audit policy; no contact address or raw device token.',
				'required'       => array( 'preference_ref', 'category', 'channel', 'enabled', 'digest_frequency', 'quiet_enabled', 'version' ),
			),
		);
	}

	/**
	 * Publish a bounded File 19 fact through File 01.
	 *
	 * File 01 is a required dependency for contract-bearing File 19 mutations.
	 * If it is unavailable, transactional File 19 mutations fail closed; delivery
	 * failure facts are reconciled later because provider outcomes cannot be rolled back.
	 *
	 * @param string              $event_name Event contract name.
	 * @param string              $aggregate_id Opaque File 19 aggregate identifier.
	 * @param array<string,mixed> $payload Privacy-minimized payload.
	 * @param string              $dedupe_key Stable idempotency key.
	 * @return true|WP_Error
	 */
	public static function publish( $event_name, $aggregate_id, array $payload, $dedupe_key = '' ) {
		$contracts = self::contracts();
		if ( ! isset( $contracts[ $event_name ] ) ) {
			return new WP_Error( 'sun_platform_event_contract_unknown', __( 'The File 19 platform event contract is unknown.', 'sabri-unified-notifications' ) );
		}
		if ( ! self::backbone_available() ) {
			return new WP_Error( 'sun_file01_event_backbone_unavailable', __( 'The required File 01 event backbone is unavailable.', 'sabri-unified-notifications' ), array( 'status'=>503 ) );
		}
		$contract = $contracts[ $event_name ];
		$aggregate_id = sanitize_text_field( (string) $aggregate_id );
		if ( '' === $aggregate_id || strlen( $aggregate_id ) > 191 ) {
			return new WP_Error( 'sun_platform_event_aggregate_invalid', __( 'The File 19 platform event aggregate is invalid.', 'sabri-unified-notifications' ) );
		}
		$dedupe_key = '' !== (string) $dedupe_key
			? substr( sanitize_text_field( (string) $dedupe_key ), 0, 191 )
			: hash( 'sha256', $event_name . '|' . $aggregate_id . '|' . SUN_Database::canonical_json( $payload ) );

		$result = SPF_Event_Bus::publish(
			$event_name,
			(string) $contract['aggregate_type'],
			$aggregate_id,
			$payload,
			1,
			$dedupe_key,
			(string) $contract['privacy_class']
		);
		return is_wp_error( $result ) ? $result : true;
	}

	/** @return array<string,mixed> */
	public static function health() {
		return array(
			'contract_version' => self::CONTRACT_VERSION,
			'file01_event_backbone' => self::backbone_available(),
			'published_contracts' => array_keys( self::contracts() ),
		);
	}
}
