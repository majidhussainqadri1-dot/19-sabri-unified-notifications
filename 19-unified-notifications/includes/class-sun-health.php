<?php
/**
 * Privacy-safe health, observability and System Check evidence.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Health {
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @var SUN_Value_Metrics */ private $value_metrics;
	/** @param SUN_Delivery_Service $delivery Delivery. @param SUN_Value_Metrics $value_metrics Aggregate value metrics. */
	public function __construct( SUN_Delivery_Service $delivery, SUN_Value_Metrics $value_metrics ) { $this->delivery = $delivery; $this->value_metrics = $value_metrics; }

	/** @return array<string,mixed> */
	public function snapshot() {
		global $wpdb, $wp_version;
		$tables = array();
		foreach ( array( 'events','notifications','preferences','subscriptions','deliveries','templates','policies','devices','dead_letters','audit','bulk_jobs' ) as $logical ) {
			$table = SUN_Database::table( $logical );
			$tables[ $logical ] = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		}
		$queue = SUN_Database::table( 'deliveries' );
		$dead  = SUN_Database::table( 'dead_letters' );
		$metrics = array(
			'queued'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$queue} WHERE status='queued'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
			'failed'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$queue} WHERE status='failed'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
			'dead_letter' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$dead} WHERE status='open'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
			'oldest_queue_seconds' => 0,
		);
		$oldest = $wpdb->get_var( "SELECT MIN(created_at) FROM {$queue} WHERE status IN ('queued','failed')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $oldest ) { $metrics['oldest_queue_seconds'] = max( 0, time() - strtotime( $oldest . ' UTC' ) ); }
		$checks = array(
			'schema'       => ! in_array( false, $tables, true ),
			'cron_queue'   => (bool) wp_next_scheduled( 'sun_process_delivery_queue' ),
			'cron_reconcile'=> (bool) wp_next_scheduled( 'sun_reconcile_notifications' ),
			'encryption'   => ! is_wp_error( SUN_Crypto::encrypt( 'health-probe' ) ),
			'queue_lag_ok' => $metrics['oldest_queue_seconds'] < (int) apply_filters( 'sun_queue_lag_alert_seconds', 3600 ),
			'dead_letters_ok' => $metrics['dead_letter'] < (int) apply_filters( 'sun_dead_letter_alert_count', 10 ),
			'four_plan_contract' => 'sun.four-plan-compliance.v1' === (string) SUN_Four_Plan_Compliance::manifest()['contract'],
		);
		$status = in_array( false, $checks, true ) ? 'degraded' : 'healthy';
		return array(
			'contract'      => 'sun.health.v2',
			'status'        => $status,
			'plugin_version'=> SUN_VERSION,
			'db_version'    => get_option( 'sun_db_version', '' ),
			'subscriptions_schema_version' => get_option( 'sun_subscriptions_schema_version', '' ),
			'php'           => PHP_VERSION,
			'wordpress'     => $wp_version,
			'checks'        => $checks,
			'tables'        => $tables,
			'metrics'       => $metrics,
			'value_metrics' => $this->value_metrics->snapshot(),
			'adapters'      => $this->delivery->adapter_health(),
			'last_reconciliation' => get_option( 'sun_last_reconciliation', array() ),
			'generated_at'  => SUN_Database::now(),
		);
	}

	/** @return array<string,mixed> */
	public function sanitized_export() {
		$data = $this->snapshot();
		$data['site'] = array( 'host_hash' => hash( 'sha256', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		return $data;
	}
}
