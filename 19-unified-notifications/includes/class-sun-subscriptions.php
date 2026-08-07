<?php
/**
 * Top-20 granular subscription controls for File 19.
 *
 * Subscription rows are notification-delivery preferences only. They never
 * become relationship, course, community, doctor or channel source-of-truth.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Subscriptions {
	const SCHEMA_VERSION = '1.0.0';

	/** @return void */
	public static function install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = SUN_Database::table( 'subscriptions' );
		$charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint unsigned NOT NULL,
			scope_type varchar(30) NOT NULL,
			scope_id varchar(191) NOT NULL,
			event_pattern varchar(191) NOT NULL DEFAULT '*',
			enabled tinyint(1) NOT NULL DEFAULT 1,
			frequency varchar(20) NOT NULL DEFAULT 'immediate',
			version bigint unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_scope_event (user_id,scope_type,scope_id,event_pattern),
			KEY user_enabled (user_id,enabled),
			KEY scope_lookup (scope_type,scope_id)
		) {$charset};";
		dbDelta( $sql );
		update_option( 'sun_subscriptions_schema_version', self::SCHEMA_VERSION, false );
	}

	/** @return void */
	public static function maybe_install() {
		if ( self::SCHEMA_VERSION !== (string) get_option( 'sun_subscriptions_schema_version', '' ) ) {
			self::install_schema();
		}
	}

	/** @return string[] */
	public static function scope_types() {
		return array( 'person', 'topic', 'community', 'course', 'event', 'doctor', 'channel' );
	}

	/**
	 * List the user's scoped subscription choices.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_for_user( $user_id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT scope_type,scope_id,event_pattern,enabled,frequency,version,updated_at FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d ORDER BY scope_type,scope_id,event_pattern LIMIT 500',
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
	 * Upsert one scoped choice with optimistic concurrency.
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update( $user_id, array $input ) {
		global $wpdb;
		$user_id       = absint( $user_id );
		$scope_type    = sanitize_key( (string) ( $input['scope_type'] ?? '' ) );
		$scope_id      = sanitize_text_field( (string) ( $input['scope_id'] ?? '' ) );
		$event_pattern = sanitize_text_field( (string) ( $input['event_pattern'] ?? '*' ) );
		$frequency     = sanitize_key( (string) ( $input['frequency'] ?? 'immediate' ) );
		if ( ! in_array( $scope_type, self::scope_types(), true ) || '' === $scope_id || strlen( $scope_id ) > 191 ) {
			return new WP_Error( 'sun_subscription_scope_invalid', __( 'The notification subscription scope is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		if ( '*' !== $event_pattern && ! preg_match( '/^[A-Z][A-Za-z0-9]*(?:\.[A-Za-z0-9*]+)+$/', $event_pattern ) ) {
			return new WP_Error( 'sun_subscription_event_invalid', __( 'The notification subscription event pattern is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $frequency, array( 'immediate', 'daily', 'weekly' ), true ) ) {
			return new WP_Error( 'sun_subscription_frequency_invalid', __( 'The notification subscription frequency is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
		}
		$table = SUN_Database::table( 'subscriptions' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id,version FROM {$table} WHERE user_id=%d AND scope_type=%s AND scope_id=%s AND event_pattern=%s LIMIT 1",
				$user_id, $scope_type, $scope_id, $event_pattern
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$expected = absint( $input['version'] ?? ( $row ? $row['version'] : 0 ) );
		if ( $row && $expected !== (int) $row['version'] ) {
			return new WP_Error( 'sun_subscription_conflict', __( 'This notification subscription changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
		}
		$now = SUN_Database::now();
		$data = array(
			'user_id' => $user_id,
			'scope_type' => $scope_type,
			'scope_id' => $scope_id,
			'event_pattern' => $event_pattern,
			'enabled' => ! empty( $input['enabled'] ) ? 1 : 0,
			'frequency' => $frequency,
			'version' => $row ? (int) $row['version'] + 1 : 1,
			'updated_at' => $now,
		);
		if ( $row ) {
			$changed = $wpdb->update( $table, $data, array( 'id' => (int) $row['id'], 'version' => (int) $row['version'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( 1 !== (int) $changed ) {
				return new WP_Error( 'sun_subscription_conflict', __( 'This notification subscription changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
			}
		} else {
			$data['created_at'] = $now;
			$inserted = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( false === $inserted ) {
				return new WP_Error( 'sun_subscription_save_failed', __( 'The notification subscription could not be saved.', 'sabri-unified-notifications' ) );
			}
		}
		SUN_Audit::record( 'subscription_changed', 'notification_subscription', $scope_type . ':' . hash( 'sha256', $scope_id ), array( 'purpose' => 'user_choice', 'enabled' => ! empty( $input['enabled'] ), 'frequency' => $frequency ), $user_id );
		return array(
			'scope_type' => $scope_type,
			'scope_id' => $scope_id,
			'event_pattern' => $event_pattern,
			'enabled' => ! empty( $input['enabled'] ),
			'frequency' => $frequency,
			'version' => (int) $data['version'],
		);
	}

	/**
	 * Resolve a scope-specific delivery decision.
	 *
	 * @param int                 $user_id User ID.
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $scope Event scope.
	 * @param bool                $requires_opt_in Whether no row means deny.
	 * @return array<string,mixed>
	 */
	public function decide( $user_id, $event_type, array $scope, $requires_opt_in = false ) {
		global $wpdb;
		$scope_type = sanitize_key( (string) ( $scope['type'] ?? '' ) );
		$scope_id   = sanitize_text_field( (string) ( $scope['id'] ?? '' ) );
		if ( '' === $scope_type || '' === $scope_id || ! in_array( $scope_type, self::scope_types(), true ) ) {
			return array( 'allowed' => ! $requires_opt_in, 'frequency' => 'immediate', 'matched' => false, 'reason' => $requires_opt_in ? 'opt_in_required' : '' );
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT enabled,frequency,event_pattern FROM ' . SUN_Database::table( 'subscriptions' ) . ' WHERE user_id=%d AND scope_type=%s AND scope_id=%s ORDER BY CHAR_LENGTH(event_pattern) DESC LIMIT 50',
				absint( $user_id ), $scope_type, $scope_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach ( (array) $rows as $row ) {
			if ( $this->matches_pattern( $event_type, (string) $row['event_pattern'] ) ) {
				return array( 'allowed' => (bool) $row['enabled'], 'frequency' => sanitize_key( (string) $row['frequency'] ), 'matched' => true, 'reason' => empty( $row['enabled'] ) ? 'user_scope_disabled' : '' );
			}
		}
		return array( 'allowed' => ! $requires_opt_in, 'frequency' => 'immediate', 'matched' => false, 'reason' => $requires_opt_in ? 'opt_in_required' : '' );
	}

	/**
	 * Check a bounded one-to-many creator bulletin cap without storing creator PII.
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $scope Scope.
	 * @param int                 $max_per_24h Cap.
	 * @return array{allowed:bool,key:string}
	 */
	public function bulletin_cap_check( $user_id, array $scope, $max_per_24h ) {
		$scope_type = sanitize_key( (string) ( $scope['type'] ?? '' ) );
		$scope_id   = sanitize_text_field( (string) ( $scope['id'] ?? '' ) );
		$key = 'sun_bcap_' . substr( hash( 'sha256', absint( $user_id ) . '|' . $scope_type . '|' . $scope_id ), 0, 32 );
		$count = (int) get_transient( $key );
		return array( 'allowed' => $count < max( 1, absint( $max_per_24h ) ), 'key' => $key );
	}

	/** @param string $key Transient key. @return void */
	public function mark_bulletin_sent( $key ) {
		if ( ! str_starts_with( (string) $key, 'sun_bcap_' ) ) {
			return;
		}
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, DAY_IN_SECONDS );
	}

	/**
	 * Apply a more restrictive scope frequency to a category delivery time.
	 *
	 * @param string            $frequency Frequency.
	 * @param DateTimeImmutable $base Base UTC time.
	 * @param string            $timezone Timezone.
	 * @param string            $scope_hash Non-PII scope hash.
	 * @return array{time:DateTimeImmutable,key:string|null}
	 */
	public function schedule( $frequency, DateTimeImmutable $base, $timezone, $scope_hash ) {
		if ( ! in_array( $frequency, array( 'daily', 'weekly' ), true ) ) {
			return array( 'time' => $base, 'key' => null );
		}
		try {
			$tz = new DateTimeZone( (string) $timezone );
		} catch ( Exception $e ) {
			$tz = new DateTimeZone( 'UTC' );
		}
		$local = $base->setTimezone( $tz );
		if ( 'daily' === $frequency ) {
			$next = $local->setTime( 8, 0 );
			if ( $next <= $local ) {
				$next = $next->modify( '+1 day' );
			}
			return array( 'time' => $next->setTimezone( new DateTimeZone( 'UTC' ) ), 'key' => 'scope-daily:' . $scope_hash . ':' . $next->format( 'Y-m-d' ) );
		}
		$next = $local->modify( 'next monday' )->setTime( 8, 0 );
		return array( 'time' => $next->setTimezone( new DateTimeZone( 'UTC' ) ), 'key' => 'scope-weekly:' . $scope_hash . ':' . $next->format( 'o-W' ) );
	}

	/** @param string $event_type Event type. @param string $pattern Pattern. @return bool */
	private function matches_pattern( $event_type, $pattern ) {
		if ( '*' === $pattern ) {
			return true;
		}
		if ( str_ends_with( $pattern, '.*' ) ) {
			return str_starts_with( $event_type, substr( $pattern, 0, -1 ) );
		}
		return hash_equals( $pattern, $event_type );
	}
}
