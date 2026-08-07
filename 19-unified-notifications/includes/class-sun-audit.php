<?php
/**
 * Privacy-minimized tamper-evident audit chain.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Audit {
	/**
	 * Record an audit fact without raw notification payloads.
	 *
	 * @param string              $action Action.
	 * @param string              $object_type Object type.
	 * @param string|int          $object_id Object ID.
	 * @param array<string,mixed> $context Safe context.
	 * @param int|null            $actor_id Actor ID.
	 * @return bool
	 */
	public static function record( $action, $object_type, $object_id, array $context = array(), $actor_id = null ) {
		global $wpdb;
		$table    = SUN_Database::table( 'audit' );
		$actor_id = null === $actor_id ? get_current_user_id() : absint( $actor_id );
		$trace_id = sanitize_text_field( (string) ( $context['trace_id'] ?? SUN_Database::uuid() ) );
		unset( $context['payload'], $context['body'], $context['title'], $context['summary'], $context['token'], $context['secret'] );
		$context = apply_filters( 'sun_audit_safe_context', $context, $action, $object_type );
		$prev    = (string) $wpdb->get_var( "SELECT entry_hash FROM {$table} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$created = SUN_Database::now();
		$body    = array(
			'action'      => sanitize_key( $action ),
			'object_type' => sanitize_key( $object_type ),
			'object_id'   => sanitize_text_field( (string) $object_id ),
			'actor_id'    => $actor_id,
			'purpose'     => sanitize_key( (string) ( $context['purpose'] ?? 'operation' ) ),
			'trace_id'    => $trace_id,
			'context'     => $context,
			'created_at'  => $created,
			'prev_hash'   => $prev,
		);
		$hash = hash_hmac( 'sha256', SUN_Database::canonical_json( $body ), wp_salt( 'secure_auth' ) );
		return false !== $wpdb->insert(
			$table,
			array(
				'action'       => $body['action'],
				'object_type'  => $body['object_type'],
				'object_id'    => $body['object_id'],
				'actor_id'     => $body['actor_id'],
				'purpose'      => $body['purpose'],
				'trace_id'     => $trace_id,
				'context_json' => wp_json_encode( $context ),
				'prev_hash'    => $prev,
				'entry_hash'   => $hash,
				'created_at'   => $created,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}
