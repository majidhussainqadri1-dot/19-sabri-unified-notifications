<?php
/**
 * Granular opt-in notification subscriptions for Top-20 value requirements.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Subscriptions {
	/** @return string[] */
	public function scope_types() {
		return array( 'person', 'topic', 'community', 'course', 'event', 'doctor', 'channel' );
	}

	/** @return string[] */
	public function frequencies() {
		return array( 'immediate', 'daily', 'weekly' );
	}

	/**
	 * List the current user's explicit subscriptions.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_for_user( $user_id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT public_id,scope_type,scope_id,enabled,frequency,version,created_at,updated_at FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d ORDER BY scope_type,scope_id',
				absint( $user_id )
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach ( (array) $rows as &$row ) {
			$row['enabled'] = (bool) $row['enabled'];
			$row['version'] = (int) $row['version'];
		}
		unset( $row );
		return (array) $rows;
	}

	/**
	 * Create or update one explicit subscription with optimistic concurrency.
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function upsert( $user_id, array $input ) {
		global $wpdb;
		$scope_type = sanitize_key( (string) ( $input['scope_type'] ?? '' ) );
		$scope_id   = sanitize_text_field( (string) ( $input['scope_id'] ?? '' ) );
		$frequency  = sanitize_key( (string) ( $input['frequency'] ?? 'immediate' ) );
		$enabled    = ! array_key_exists( 'enabled', $input ) || ! empty( $input['enabled'] );

		if ( ! in_array( $scope_type, $this->scope_types(), true ) || '' === $scope_id || strlen( $scope_id ) > 191 ) {
			return new WP_Error( 'sun_subscription_invalid', __( 'The notification subscription scope is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $frequency, $this->frequencies(), true ) ) {
			return new WP_Error( 'sun_subscription_frequency_invalid', __( 'The notification frequency is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}

		$table   = SUN_Database::table( 'subscriptions' );
		$current = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id=%d AND scope_type=%s AND scope_id=%s LIMIT 1", absint( $user_id ), $scope_type, $scope_id ),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$expected = array_key_exists( 'version', $input ) ? absint( $input['version'] ) : (int) ( $current['version'] ?? 0 );
		if ( $current && $expected !== (int) $current['version'] ) {
			return new WP_Error( 'sun_subscription_conflict', __( 'This notification subscription changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
		}

		$now  = SUN_Database::now();
		$data = array(
			'user_id'    => absint( $user_id ),
			'scope_type' => $scope_type,
			'scope_id'   => $scope_id,
			'enabled'    => $enabled ? 1 : 0,
			'frequency'  => $frequency,
			'version'    => (int) ( $current['version'] ?? 0 ) + 1,
			'updated_at' => $now,
		);

		if ( $current ) {
			$updated = $wpdb->update( $table, $data, array( 'id' => (int) $current['id'], 'version' => (int) $current['version'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( 1 !== (int) $updated ) {
				return new WP_Error( 'sun_subscription_conflict', __( 'This notification subscription changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
			}
			$public_id = (string) $current['public_id'];
		} else {
			$public_id          = SUN_Database::uuid();
			$data['public_id']  = $public_id;
			$data['created_at'] = $now;
			$inserted = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( false === $inserted ) {
				return new WP_Error( 'sun_subscription_write_failed', __( 'The notification subscription could not be saved.', 'sabri-unified-notifications' ) );
			}
		}

		SUN_Audit::record( 'subscription_changed', 'notification_subscription', $public_id, array( 'scope_type' => $scope_type, 'purpose' => 'user_choice' ), $user_id );
		return array(
			'id'         => $public_id,
			'scope_type' => $scope_type,
			'scope_id'   => $scope_id,
			'enabled'    => $enabled,
			'frequency'  => $frequency,
			'version'    => $data['version'],
		);
	}

	/** @param int $user_id User ID. @param string $public_id Public ID. @return true|WP_Error */
	public function remove( $user_id, $public_id ) {
		global $wpdb;
		$deleted = $wpdb->delete( SUN_Database::table( 'subscriptions' ), array( 'user_id' => absint( $user_id ), 'public_id' => sanitize_text_field( $public_id ) ), array( '%d', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( 1 !== (int) $deleted ) {
			return new WP_Error( 'sun_subscription_not_found', __( 'Notification subscription not found.', 'sabri-unified-notifications' ), array( 'status' => 404 ) );
		}
		SUN_Audit::record( 'subscription_removed', 'notification_subscription', $public_id, array( 'purpose' => 'user_choice' ), $user_id );
		return true;
	}

	/**
	 * Resolve whether a scoped event is wanted and, if so, its requested frequency.
	 * Essential security/safety/system notices are never disabled by a subscription.
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $event Event.
	 * @param string              $category Resolved category.
	 * @return array{allowed:bool,frequency:?string,matched:bool}
	 */
	public function evaluate_event( $user_id, array $event, $category ) {
		if ( in_array( $category, array( 'security', 'safety', 'system' ), true ) ) {
			return array( 'allowed' => true, 'frequency' => null, 'matched' => false );
		}
		$scope = isset( $event['subscription_scope'] ) && is_array( $event['subscription_scope'] ) ? $event['subscription_scope'] : array();
		if ( empty( $scope['type'] ) || empty( $scope['id'] ) ) {
			return array( 'allowed' => true, 'frequency' => null, 'matched' => false );
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT enabled,frequency FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d AND scope_type=%s AND scope_id=%s LIMIT 1',
				absint( $user_id ), sanitize_key( (string) $scope['type'] ), sanitize_text_field( (string) $scope['id'] )
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $row ) {
			return array( 'allowed' => empty( $scope['required'] ), 'frequency' => null, 'matched' => false );
		}
		return array(
			'allowed'   => ! empty( $row['enabled'] ),
			'frequency' => in_array( $row['frequency'], $this->frequencies(), true ) ? $row['frequency'] : 'immediate',
			'matched'   => true,
		);
	}
}
