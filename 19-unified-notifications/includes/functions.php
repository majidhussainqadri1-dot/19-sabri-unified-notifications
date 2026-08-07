<?php
/**
 * Public integration functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ingest one versioned factual domain event.
 *
 * Domain owners should call this function instead of writing notification tables.
 * The event must contain explicit lawful recipients; broad role guessing is rejected.
 *
 * @param array<string,mixed> $event Event envelope.
 * @return array<string,mixed>|WP_Error
 */
function sun_ingest_domain_event( array $event ) {
	return sun_notifications()->notifications()->ingest_event( $event, 'php' );
}

/**
 * Render the single canonical notification bell.
 *
 * @return string
 */
function sun_render_notification_bell() {
	return sun_notifications()->renderer()->render_bell();
}

/**
 * Get the current user's reconciled unread count.
 *
 * @param int|null $user_id User ID, current user when omitted.
 * @return int
 */
function sun_get_unread_count( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	return sun_notifications()->notifications()->get_unread_count( $user_id );
}

/**
 * Register a producer configuration without exposing secrets in the repository.
 *
 * This helper appends to the runtime registry through a filter-safe static store.
 *
 * @param string              $producer Producer identifier.
 * @param array<string,mixed> $config   Producer contract.
 * @return bool
 */
function sun_register_notification_producer( $producer, array $config ) {
	return SUN_Producer_Registry::register_runtime( $producer, $config );
}
