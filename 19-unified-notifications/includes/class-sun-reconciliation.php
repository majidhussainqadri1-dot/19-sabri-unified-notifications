<?php
/**
 * Notification, delivery, counter, device and schema reconciliation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Reconciliation {
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @var SUN_Notification_Service */ private $notifications;

	/** @param SUN_Delivery_Service $delivery Delivery. @param SUN_Notification_Service $notifications Notifications. */
	public function __construct( SUN_Delivery_Service $delivery, SUN_Notification_Service $notifications ) {
		$this->delivery      = $delivery;
		$this->notifications = $notifications;
	}

	/**
	 * Run bounded safe repairs. No domain state is synthesized.
	 *
	 * @return array<string,int>
	 */
	public function run() {
		global $wpdb;
		$now = SUN_Database::now();
		$result = array(
			'expired_notifications' => $this->notifications->expire_due(),
			'stale_devices'         => (int) $wpdb->query( $wpdb->prepare( "UPDATE " . SUN_Database::table( 'devices' ) . " SET status='expired',updated_at=%s WHERE status IN ('pending','active','stale') AND expires_at IS NOT NULL AND expires_at<=%s", $now, $now ) ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			'stuck_deliveries'      => (int) $wpdb->query( $wpdb->prepare( "UPDATE " . SUN_Database::table( 'deliveries' ) . " SET status='failed',last_error_code='worker_timeout',last_error_safe='Delivery worker timed out.',next_attempt_at=%s,updated_at=%s WHERE status='sending' AND last_attempt_at<%s", $now, $now, gmdate( 'Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS ) ) ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			'orphan_deliveries'     => 0,
			'dead_letter_duplicates'=> 0,
		);
		$delivery_table = SUN_Database::table( 'deliveries' );
		$notes_table    = SUN_Database::table( 'notifications' );
		$result['orphan_deliveries'] = (int) $wpdb->query( "UPDATE {$delivery_table} d LEFT JOIN {$notes_table} n ON n.id=d.notification_id SET d.status='suppressed',d.last_error_code='orphan_notification',d.last_error_safe='Notification no longer exists.',d.updated_at='{$now}' WHERE n.id IS NULL AND d.status IN ('queued','failed','sending')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		SUN_Audit::record( 'reconciliation_completed', 'system', 'file-19', array_merge( $result, array( 'purpose' => 'reconciliation' ) ), 0 );
		update_option( 'sun_last_reconciliation', array( 'at' => $now, 'result' => $result ), false );
		return $result;
	}

	/**
	 * Retry a dead-letter after an operator confirms the root cause is fixed.
	 *
	 * @param string $public_id Dead-letter public ID.
	 * @return true|WP_Error
	 */
	public function retry_dead_letter( $public_id ) {
		global $wpdb;
		$dead = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SUN_Database::table( 'dead_letters' ) . ' WHERE public_id=%s AND status=%s LIMIT 1', sanitize_text_field( $public_id ), 'open' ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! $dead || empty( $dead['delivery_id'] ) ) {
			return new WP_Error( 'sun_dead_letter_not_found', __( 'Dead-letter item not found.', 'sabri-unified-notifications' ), array( 'status' => 404 ) );
		}
		SUN_Database::begin();
		try {
			$wpdb->update( SUN_Database::table( 'deliveries' ), array( 'status'=>'queued','attempt_count'=>0,'next_attempt_at'=>SUN_Database::now(),'last_error_code'=>null,'last_error_safe'=>null,'updated_at'=>SUN_Database::now() ), array( 'id'=>(int) $dead['delivery_id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( SUN_Database::table( 'dead_letters' ), array( 'status'=>'resolved','resolved_by'=>get_current_user_id(),'resolved_at'=>SUN_Database::now(),'updated_at'=>SUN_Database::now() ), array( 'id'=>(int) $dead['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			SUN_Database::commit();
		} catch ( Throwable $e ) {
			SUN_Database::rollback();
			return new WP_Error( 'sun_dead_letter_retry_failed', __( 'The delivery could not be requeued.', 'sabri-unified-notifications' ) );
		}
		SUN_Audit::record( 'dead_letter_retried', 'dead_letter', $public_id, array( 'delivery_id' => (int) $dead['delivery_id'], 'purpose' => 'operator_recovery' ) );
		return true;
	}
}
