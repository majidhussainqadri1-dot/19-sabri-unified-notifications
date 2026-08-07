<?php
/**
 * Executable four-plan compliance constitution for File 19.
 *
 * This class contains no domain state. It exposes the governing corpus,
 * canonical ownership boundaries and minimum safety/value floors used by the
 * runtime and deterministic QA.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Four_Plan_Compliance {
	/** @return array<string,mixed> */
	public static function manifest() {
		return array(
			'contract' => 'sun.four-plan-compliance.v1',
			'file'     => 19,
			'owner'    => 'File 19 — Unified Notifications and Alerts',
			'plans'    => array(
				'definitive' => 'SSH-PMP-2026-v3.0',
				'recovered'  => 'Sabri Recovered Directives v2.2',
				'top20'      => 'Continuous Value / Global Top-20 Superset v1.0',
				'dedicated'  => 'SSH-F19-PLAN-2026-v1.0',
			),
			'precedence' => array(
				'latest_explicit_founder_directive',
				'later_approved_central_plan',
				'dedicated_file_19_plan',
				'verified_runtime_evidence',
			),
			'canonical_ownership' => array(
				'owns' => array(
					'notification_projection', 'single_notification_center', 'preferences',
					'quiet_hours', 'digests', 'delivery_attempts', 'delivery_adapters',
					'device_registration', 'retry_dead_letter', 'notification_audit',
				),
				'does_not_own' => array(
					'domain_state', 'messages', 'appointments', 'publishing_state',
					'marketplace_deals', 'identity', 'global_shell', 'search_ranking',
				),
			),
			'constitutional_invariants' => array(
				'one_bell'                 => true,
				'one_center'               => true,
				'producer_owner_binding'   => true,
				'file00_live_claims'       => true,
				'no_paid_advantage'        => true,
				'no_donor_advantage'       => true,
				'provider_success_honesty' => true,
				'privacy_minimization'     => true,
				'rtl_first'                => true,
				'green_primary_brand'      => true,
			),
			'top20_requirements' => array(
				'CV-097' => 'Unified inbox/center for all approved domain events without duplicating domain truth.',
				'CV-098' => 'Per-channel choice; unavailable providers must never be reported as delivered.',
				'CV-099' => 'Granular subscriptions for person/topic/community/course/event/doctor/channel scopes.',
				'CV-100' => 'Daily/weekly digest plus quiet hours and timezone, with urgent safety separated.',
				'CV-101' => 'Appointment reminder event family with no clinical lock-screen detail.',
				'CV-102' => 'Severity-aware correction/retraction alerts for affected users.',
				'CV-103' => 'Essential account-security alert family with trusted recovery path.',
				'CV-104' => 'Opt-in creator bulletins with a frequency cap and reporting path.',
				'CV-105' => 'Truthful queued/sent-or-accepted/delivered/failed/expired/retry delivery ledger.',
				'CV-106' => 'Privacy-safe notification-fatigue metrics; more notifications is never a success KPI.',
			),
		);
	}

	/**
	 * Return the strongest minimum profile required by known four-plan event families.
	 * Generic policy remains authoritative when no special profile exists.
	 *
	 * @param string $event_type Event type.
	 * @return array<string,mixed>
	 */
	public static function profile_for( $event_type ) {
		$profiles = array(
			array( 'pattern' => 'Security.*', 'category' => 'security', 'priority' => 'critical', 'sensitivity' => 'sensitive', 'mandatory' => true, 'digest_allowed' => false ),
			array( 'pattern' => 'Safety.*', 'category' => 'safety', 'priority' => 'critical', 'sensitivity' => 'sensitive', 'mandatory' => true, 'digest_allowed' => false ),
			array( 'pattern' => 'Clinic.Appointment*', 'category' => 'clinic', 'priority' => 'high', 'sensitivity' => 'sensitive', 'mandatory' => false, 'digest_allowed' => true ),
			array( 'pattern' => 'Publishing.Correction*', 'category' => 'publishing', 'priority' => 'high', 'sensitivity' => 'standard', 'mandatory' => false, 'digest_allowed' => true ),
			array( 'pattern' => 'Learning.Correction*', 'category' => 'learning', 'priority' => 'high', 'sensitivity' => 'standard', 'mandatory' => false, 'digest_allowed' => true ),
			array( 'pattern' => 'Social.CreatorBulletin*', 'category' => 'social', 'priority' => 'normal', 'sensitivity' => 'standard', 'mandatory' => false, 'digest_allowed' => true, 'requires_opt_in' => true, 'max_per_24h' => 1 ),
		);
		foreach ( $profiles as $profile ) {
			if ( self::matches( $event_type, $profile['pattern'] ) ) {
				return $profile;
			}
		}
		return array();
	}

	/** @param string $left Priority. @param string $right Priority. @return string */
	public static function strongest_priority( $left, $right ) {
		$order = array( 'low' => 0, 'normal' => 1, 'high' => 2, 'critical' => 3 );
		$left  = isset( $order[ $left ] ) ? $left : 'normal';
		$right = isset( $order[ $right ] ) ? $right : 'normal';
		return $order[ $right ] > $order[ $left ] ? $right : $left;
	}

	/** @param string $left Sensitivity. @param string $right Sensitivity. @return string */
	public static function strongest_sensitivity( $left, $right ) {
		$order = array( 'standard' => 0, 'sensitive' => 1, 'restricted' => 2, 'secret' => 3 );
		$left  = isset( $order[ $left ] ) ? $left : 'standard';
		$right = isset( $order[ $right ] ) ? $right : 'standard';
		return $order[ $right ] > $order[ $left ] ? $right : $left;
	}

	/** @param string $event_type Event type. @param string $pattern Pattern. @return bool */
	private static function matches( $event_type, $pattern ) {
		if ( '*' === $pattern ) {
			return true;
		}
		if ( str_ends_with( $pattern, '*' ) ) {
			return str_starts_with( $event_type, substr( $pattern, 0, -1 ) );
		}
		return hash_equals( $pattern, $event_type );
	}
}
