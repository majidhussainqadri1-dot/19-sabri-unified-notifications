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
			$data[] = array( 'group_id'=>'sabri-notifications', 'group_label'=>__( 'Notifications', 'sabri-unified-notifications' ), 'item_id'=>'notification-' . $row['public_id'], 'data'=>array_map( static function( $key, $value ){ return array( 'name'=>ucwords( str_replace( '_',' ',$key ) ), 'value'=>(string) $value ); }, array_keys( $row ), array_values( $row ) ) );
		}
		if ( 1 === absint( $page ) ) {
			$subscriptions = $wpdb->get_results(
				$wpdb->prepare( 'SELECT scope_type,scope_id,event_pattern,enabled,frequency,updated_at FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d ORDER BY id ASC LIMIT 500', $user->ID ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( (array) $subscriptions as $index => $row ) {
				$data[] = array( 'group_id'=>'sabri-notification-subscriptions', 'group_label'=>__( 'Notification Subscriptions', 'sabri-unified-notifications' ), 'item_id'=>'subscription-' . $index, 'data'=>array_map( static function( $key, $value ){ return array( 'name'=>ucwords( str_replace( '_',' ',$key ) ), 'value'=>(string) $value ); }, array_keys( $row ), array_values( $row ) ) );
			}
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
		$subscriptions = SUN_Database::table( 'subscriptions' );
		$wpdb->query( $wpdb->prepare( "UPDATE {$notes} SET status='deleted',title='',summary='',data_ciphertext=NULL,deep_link=NULL,updated_at=%s WHERE recipient_id=%d", SUN_Database::now(), $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $devices, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $prefs, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $subscriptions, array( 'user_id'=>$user->ID ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		SUN_Audit::record( 'privacy_erasure_completed', 'user', (string) $user->ID, array( 'purpose'=>'privacy_request' ), 0 );
		return array( 'items_removed'=>true,'items_retained'=>false,'messages'=>array(),'done'=>true );
	}
}
