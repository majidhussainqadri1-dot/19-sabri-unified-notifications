<?php
/**
 * Privacy-minimized notification fatigue and healthy-use metrics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Wellbeing {
	/**
	 * Return an own-user aggregate only. No notification body, subject, contact,
	 * donation/payment or clinical detail is copied into a behavioral profile.
	 *
	 * @param int $user_id User ID.
	 * @param int $days Lookback days.
	 * @return array<string,mixed>
	 */
	public function summary( $user_id, $days = 30 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$days    = max( 1, min( 90, absint( $days ) ) );
		$since   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$notes   = SUN_Database::table( 'notifications' );
		$deliv   = SUN_Database::table( 'deliveries' );
		$prefs   = SUN_Database::table( 'preferences' );

		$created = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status<>'deleted'", $user_id, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$unread  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status='unread'", $user_id, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$archived= (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status='archived'", $user_id, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$failed  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliv} WHERE recipient_id=%d AND created_at>=%s AND status IN ('failed','dead_letter','bounced')", $user_id, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$disabled= (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefs} WHERE user_id=%d AND enabled=0", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$unread_ratio = $created > 0 ? round( $unread / $created, 3 ) : 0.0;
		$signal = 'low';
		if ( $created >= 100 && $unread_ratio >= 0.7 ) {
			$signal = 'high';
		} elseif ( $created >= 40 && $unread_ratio >= 0.5 ) {
			$signal = 'medium';
		}

		return array(
			'contract'             => 'sun.wellbeing.v1',
			'lookback_days'        => $days,
			'created'              => $created,
			'unread'               => $unread,
			'archived'             => $archived,
			'external_failures'    => $failed,
			'disabled_preferences' => $disabled,
			'unread_ratio'         => $unread_ratio,
			'fatigue_signal'       => $signal,
			'guardrail'            => 'more-notifications-is-not-a-kpi',
			'generated_at'         => SUN_Database::now(),
		);
	}
}
