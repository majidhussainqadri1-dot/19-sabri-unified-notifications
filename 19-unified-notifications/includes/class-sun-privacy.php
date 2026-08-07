<?php
/**
 * WordPress privacy exporter/eraser and retention lifecycle integration.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Privacy {
	/** @return void */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
	}

	/** @param array<string,mixed> $exporters Exporters. @return array<string,mixed> */
	public function exporters( $exporters ) {
		$exporters['sabri-unified-notifications'] = array( 'exporter_friendly_name' => __( 'Sabri Notifications', 'sabri-unified-notifications' ), 'callback' => array( $this, 'export' ) );
		return $exporters;
	}

	/** @param array<string,mixed> $erasers Erasers. @return array<string,mixed> */
	public function erasers( $erasers ) {
		$erasers['sabri-unified-notifications'] = array( 'eraser_friendly_name' => __( 'Sabri Notifications', 'sabri-unified-notifications' ), 'callback' => array( $this, 'erase' ) );
		return $erasers;
	}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function export( $email, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', $email );
		if ( ! $user ) { return array( 'data'=>array(), 'done'=>true ); }
		$limit = 100; $offset = max( 0, absint( $page ) - 1 ) * $limit;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT public_id,category,priority,title,summary,status,created_at,read_at,archived_at FROM ' . SUN_Database::table( 'notifications' ) . ' WHERE recipient_id=%d ORDER BY id ASC LIMIT %d OFFSET %d', $user->ID, $limit, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data = array();
		foreach ( $rows as $row ) {
			$data[] = $this->export_item( 'sabri-notifications', __( 'Notifications', 'sabri-unified-notifications' ), 'notification-' . $row['public_id'], $row );
		}
		if ( 1 === absint( $page ) ) {
			$prefs = $wpdb->get_results( $wpdb->prepare( 'SELECT category,channel,enabled,digest_frequency,quiet_enabled,quiet_start,quiet_end,timezone,updated_at FROM ' . SUN_Database::table( 'preferences' ) . ' WHERE user_id=%d ORDER BY category,channel', $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( (array) $prefs as $index => $row ) { $data[] = $this->export_item( 'sabri-notification-preferences', __( 'Notification preferences', 'sabri-unified-notifications' ), 'preference-' . $index, $row ); }
			$subs = $wpdb->get_results( $wpdb->prepare( 'SELECT public_id,scope_type,scope_id,enabled,frequency,created_at,updated_at FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d ORDER BY scope_type,scope_id', $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( (array) $subs as $row ) { $data[] = $this->export_item( 'sabri-notification-subscriptions', __( 'Notification subscriptions', 'sabri-unified-notifications' ), 'subscription-' . $row['public_id'], $row ); }
			$devices = $wpdb->get_results( $wpdb->prepare( 'SELECT public_id,provider,platform,status,last_seen_at,expires_at,created_at FROM ' . SUN_Database::table( 'devices' ) . ' WHERE user_id=%d ORDER BY id', $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( (array) $devices as $row ) { $data[] = $this->export_item( 'sabri-notification-devices', __( 'Notification devices', 'sabri-unified-notifications' ), 'device-' . $row['public_id'], $row ); }
			$deliveries = $wpdb->get_results( $wpdb->prepare( 'SELECT public_id,channel,provider,status,attempt_count,scheduled_at,accepted_at,delivered_at,created_at FROM ' . SUN_Database::table( 'deliveries' ) . ' WHERE recipient_id=%d ORDER BY id DESC LIMIT 500', $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( (array) $deliveries as $row ) { $data[] = $this->export_item( 'sabri-notification-deliveries', __( 'Notification delivery history', 'sabri-unified-notifications' ), 'delivery-' . $row['public_id'], $row ); }
		}
		return array( 'data'=>$data, 'done'=>count( $rows ) < $limit );
	}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function erase( $email, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', $email );
		if ( ! $user ) { return array( 'items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true ); }
		$hold = (bool) apply_filters( 'sun_user_retention_hold', false, $user->ID );
		if ( $hold ) { return array( 'items_removed'=>false,'items_retained'=>true,'messages'=>array( __( 'Some notification records are under an approved retention hold.', 'sabri-unified-notifications' ) ),'done'=>true ); }
		$notes = SUN_Database::table( 'notifications' );
		$devices = SUN_Database::table( 'devices' );
		$prefs = SUN_Database::table( 'preferences' );
		$subs = SUN_Database::table( 'subscriptions' );
		$deliveries = SUN_Database::table( 'deliveries' );
		$wpdb->query( $wpdb->prepare( "UPDATE {$notes} SET status='deleted',title='',summary='',data_ciphertext=NULL,deep_link=NULL,deep_link_context=NULL,updated_at=%s WHERE recipient_id=%d", SUN_Database::now(), $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $wpdb->prepare( "UPDATE {$deliveries} SET recipient_id=0,provider_message_id=NULL,last_error_safe=NULL,updated_at=%s WHERE recipient_id=%d", SUN_Database::now(), $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $devices, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $prefs, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $subs, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		SUN_Audit::record( 'privacy_erasure_completed', 'user', hash( 'sha256', 'user:' . (string) $user->ID ), array( 'purpose'=>'privacy_request','pseudonymized_delivery_ledger'=>true ), 0 );
		return array( 'items_removed'=>true,'items_retained'=>true,'messages'=>array( __( 'Notification content and user controls were removed; minimal delivery and audit tombstones were retained without direct recipient identifiers.', 'sabri-unified-notifications' ) ),'done'=>true );
	}

	/** @param string $group Group. @param string $label Label. @param string $id ID. @param array<string,mixed> $row Row. @return array<string,mixed> */
	private function export_item( $group, $label, $id, array $row ) {
		return array(
			'group_id' => $group,
			'group_label' => $label,
			'item_id' => $id,
			'data' => array_map( static function( $key, $value ){ return array( 'name'=>ucwords( str_replace( '_',' ',$key ) ), 'value'=>(string) $value ); }, array_keys( $row ), array_values( $row ) ),
		);
	}
}
