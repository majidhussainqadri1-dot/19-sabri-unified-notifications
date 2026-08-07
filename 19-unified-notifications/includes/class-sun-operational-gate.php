<?php
/**
 * Coordinated File 19 operational containment gate.
 *
 * File 20 owns global shell Safe Mode and File 24 owns cross-cutting assurance;
 * File 19 remains responsible for fail-closed behavior of its own high-risk
 * operations. In-app reading remains available where authorization permits.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Operational_Gate {
	/**
	 * Return a privacy-safe current containment snapshot.
	 *
	 * @return array<string,mixed>
	 */
	public static function snapshot() {
		$local  = (bool) get_option( 'sun_notification_safe_mode', false );
		$file20 = (bool) apply_filters( 'sun_file20_safe_mode_active', false );
		$file24 = (bool) apply_filters( 'sun_file24_notification_containment_active', false );
		$active = $local || $file20 || $file24;

		return array(
			'contract'                  => 'sun.operational-gate.v1',
			'safe_mode_active'          => $active,
			'local_containment'         => $local,
			'file20_safe_mode'          => $file20,
			'file24_containment'        => $file24,
			'in_app_reading_allowed'    => true,
			'external_delivery_allowed' => ! $active,
			'bulk_send_allowed'         => ! $active,
		);
	}

	/**
	 * Check a named File 19 action. Consumers may narrow permission further,
	 * but may not silently bypass an active containment state without an
	 * explicit audited override filter.
	 *
	 * @param string $action Action key.
	 * @return bool
	 */
	public static function allows( $action ) {
		$action = sanitize_key( (string) $action );
		$state  = self::snapshot();
		$allowed = true;

		if ( in_array( $action, array( 'external_delivery', 'bulk_preview', 'bulk_confirm', 'bulk_process' ), true ) ) {
			$allowed = empty( $state['safe_mode_active'] );
		}

		return (bool) apply_filters( 'sun_operational_action_allowed', $allowed, $action, $state );
	}
}
