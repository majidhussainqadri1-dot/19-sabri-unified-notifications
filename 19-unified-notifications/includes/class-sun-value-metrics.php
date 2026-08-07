<?php
/**
 * Aggregate, privacy-minimized notification value/fatigue signals (CV-106).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Value_Metrics {
	/**
	 * Return aggregate 30-day signals only; never recipient, message or clinical data.
	 * More notifications is explicitly not a success KPI.
	 *
	 * @return array<string,mixed>
	 */
	public function snapshot() {
		global $wpdb;
		$notifications = SUN_Database::table( 'notifications' );
		$preferences   = SUN_Database::table( 'preferences' );
		$deliveries    = SUN_Database::table( 'deliveries' );
		$audit         = SUN_Database::table( 'audit' );
		$since30       = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
		$since7        = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
		$created30     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notifications} WHERE created_at>=%s", $since30 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$aged_unread   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notifications} WHERE status='unread' AND created_at<%s", $since7 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$archived30    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notifications} WHERE status='archived' AND updated_at>=%s", $since30 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$muted_choices = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$preferences} WHERE enabled=0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$digest_choices= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$preferences} WHERE enabled=1 AND digest_frequency IN ('daily','weekly')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$suppressed30  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliveries} WHERE status='suppressed' AND updated_at>=%s", $since30 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$failed30      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliveries} WHERE status IN ('failed','dead_letter','bounced') AND updated_at>=%s", $since30 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$complaints30  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$audit} WHERE action='notification_report' AND created_at>=%s", $since30 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$fatigue_signals = $aged_unread + $archived30 + $muted_choices + $digest_choices + $complaints30;
		return array(
			'contract' => 'sun.value-metrics.v1',
			'window_days' => 30,
			'created' => $created30,
			'aged_unread_over_7d' => $aged_unread,
			'archived' => $archived30,
			'muted_preferences' => $muted_choices,
			'digest_preferences' => $digest_choices,
			'user_complaints' => $complaints30,
			'suppressed_deliveries' => $suppressed30,
			'failed_or_bounced_deliveries' => $failed30,
			'fatigue_signal_count' => $fatigue_signals,
			'governance' => 'more_notifications_is_not_a_success_kpi',
		);
	}
}
