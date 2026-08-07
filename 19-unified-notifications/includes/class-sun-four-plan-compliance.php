<?php
/**
 * Four-plan constitutional compliance registry for File 19.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Four_Plan_Compliance {
	/**
	 * Return the executable governance snapshot used by runtime diagnostics/tests.
	 *
	 * @return array<string,mixed>
	 */
	public static function snapshot() {
		return array(
			'contract'                 => 'sun.four-plan-compliance.v2',
			'file'                     => 19,
			'canonical_owner'          => 'Unified Notifications and Alerts',
			'governing_plans'          => array(
				'SSH-PMP-2026-v3.0',
				'Sabri-Recovered-Directives-v2.1',
				'Sabri-Continuous-Value-Top-20-v1.0',
				'SSH-F19-PLAN-2026-v1.0',
			),
			'precedence'               => 'latest-founder-and-safety > recovered-directives > top20-central-plan > dedicated-file-plan > verified-runtime-evidence',
			'brand_primary'            => 'green',
			'rtl_right_priority'       => true,
			'single_free_tier'         => true,
			'donor_advantage'          => false,
			'single_notification_bell' => true,
			'domain_truth_owner'       => false,
			'package_top_folder'       => 'unified-notifications-19',
			'project_baseline'         => array( 'wordpress' => '7.0.1', 'php' => '8.3' ),
			'search_owner_file'        => 26,
			'shell_owner_file'         => 20,
			'visual_owner_file'        => 25,
			'identity_owner_file'      => 0,
			'communication_owner_file' => 17,
			'marketplace_owner_file'   => 18,
			'notification_owner_file'  => 19,
			'home_feed_owner_file'     => 21,
			'privacy_assurance_file'   => 24,
			'principles'               => array(
				'no_duplicate_bell',
				'no_duplicate_domain_backend',
				'no_paid_or_donor_delivery_priority',
				'click_time_authorization',
				'privacy_minimization',
				'quiet_hours_and_user_control',
				'queue_retry_dead_letter_resilience',
				'accessibility_and_low_bandwidth',
				'non_manipulative_notifications',
			),
			'top20_capabilities'       => self::top20_capabilities(),
			'event_catalog'            => self::event_catalog(),
		);
	}

	/** @return array<string,array<string,mixed>> */
	public static function top20_capabilities() {
		return array(
			'CV-097' => array( 'name' => 'Unified inbox', 'surface' => 'single-center', 'guardrail' => 'domain-state-not-owned' ),
			'CV-098' => array( 'name' => 'Channel preference', 'surface' => 'preferences', 'guardrail' => 'no-false-provider-success' ),
			'CV-099' => array( 'name' => 'Granular subscription', 'surface' => 'subscriptions', 'scopes' => array( 'person', 'topic', 'community', 'course', 'event', 'doctor', 'channel' ), 'guardrail' => 'essential-security-cannot-be-suppressed' ),
			'CV-100' => array( 'name' => 'Digest', 'surface' => 'quiet-hours-digests', 'frequencies' => array( 'immediate', 'daily', 'weekly' ), 'guardrail' => 'urgent-safety-separate' ),
			'CV-101' => array( 'name' => 'Appointment reminders', 'surface' => 'Clinic.* events', 'guardrail' => 'no-clinical-lock-screen-detail' ),
			'CV-102' => array( 'name' => 'Correction alert', 'surface' => 'Publishing/Knowledge correction facts', 'guardrail' => 'severity-based' ),
			'CV-103' => array( 'name' => 'Security alert', 'surface' => 'Security.* events', 'guardrail' => 'trusted-recovery-path' ),
			'CV-104' => array( 'name' => 'Creator bulletin', 'surface' => 'scoped Social.CreatorBulletin event', 'guardrail' => 'opt-in-frequency-cap-report' ),
			'CV-105' => array( 'name' => 'Delivery ledger', 'surface' => 'deliveries/dead-letters/audit', 'guardrail' => 'provider-evidence-and-pii-minimization' ),
			'CV-106' => array( 'name' => 'Notification fatigue metric', 'surface' => 'wellbeing aggregate', 'guardrail' => 'more-notifications-is-not-a-kpi' ),
		);
	}

	/**
	 * Semantic facts expected from native domain owners. File 19 never creates
	 * the appointment/publication/security source-of-truth represented here.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function event_catalog() {
		return array(
			'Clinic.AppointmentBooked'       => array( 'owner' => 8, 'category' => 'clinic', 'priority' => 'high' ),
			'Clinic.AppointmentRescheduled'  => array( 'owner' => 8, 'category' => 'clinic', 'priority' => 'high' ),
			'Clinic.AppointmentPreVisitDue'  => array( 'owner' => 8, 'category' => 'clinic', 'priority' => 'high' ),
			'Clinic.AppointmentStarting'     => array( 'owner' => 8, 'category' => 'clinic', 'priority' => 'high' ),
			'Clinic.AppointmentFollowUpDue'  => array( 'owner' => 8, 'category' => 'clinic', 'priority' => 'normal' ),
			'Publishing.CorrectionPublished' => array( 'owner' => 21, 'category' => 'publishing', 'priority' => 'high' ),
			'Publishing.RetractionPublished' => array( 'owner' => 21, 'category' => 'publishing', 'priority' => 'critical' ),
			'Security.NewDeviceDetected'     => array( 'owner' => 0, 'category' => 'security', 'priority' => 'critical' ),
			'Security.PasswordChanged'       => array( 'owner' => 0, 'category' => 'security', 'priority' => 'critical' ),
			'Security.MFAChanged'            => array( 'owner' => 0, 'category' => 'security', 'priority' => 'critical' ),
			'Security.DataExportRequested'   => array( 'owner' => 0, 'category' => 'security', 'priority' => 'critical' ),
			'Security.RoleChanged'           => array( 'owner' => 0, 'category' => 'security', 'priority' => 'critical' ),
			'Social.CreatorBulletin'         => array( 'owner' => 21, 'category' => 'social', 'priority' => 'normal', 'subscription_required' => true ),
		);
	}

	/** @return bool */
	public static function donor_or_payment_advantage_allowed() {
		return false;
	}
}
