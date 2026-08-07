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
			'central_plan_order'       => array(
				'Sabri-Continuous-Value-Top-20-v1.0',
				'Sabri-Recovered-Directives-v2.1',
				'SSH-PMP-2026-v3.0',
			),
			'precedence'               => 'latest-explicit-founder-and-safety > top20-central-plan > recovered-directives > definitive-master-v3 > dedicated-file-plan > verified-runtime-evidence',
			'brand_primary'            => 'green',
			'rtl_right_priority'       => true,
			'single_free_tier'         => true,
			'donor_advantage'          => false,
			'single_notification_bell' => true,
			'domain_truth_owner'       => false,
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
				'provider_circuit_breaker_and_safe_mode',
				'accessibility_and_low_bandwidth',
				'non_manipulative_notifications',
			),
		);
	}

	/**
	 * Whether a ranking/delivery policy is constitutionally allowed to use payment or donation status.
	 *
	 * @return bool
	 */
	public static function donor_or_payment_advantage_allowed() {
		return false;
	}
}
