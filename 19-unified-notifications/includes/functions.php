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

/**
 * Get scoped notification subscriptions for the current eligible user.
 *
 * @return array<int,array<string,mixed>>
 */
function sun_get_notification_subscriptions() {
	$user_id = get_current_user_id();
	if ( ! is_user_logged_in() || ! sun_notifications()->auth()->is_recipient_eligible( $user_id ) ) {
		return array();
	}
	return sun_notifications()->subscriptions()->list_for_user( $user_id );
}

/**
 * Set a current-user scoped notification subscription. Domain modules use this
 * contract for person/topic/community/course/event/doctor/channel subscribe UI;
 * the domain object itself remains owned by that domain module.
 *
 * @param array<string,mixed> $input Subscription input.
 * @return array<string,mixed>|WP_Error
 */
function sun_update_notification_subscription( array $input ) {
	$user_id = get_current_user_id();
	if ( ! is_user_logged_in() || ! sun_notifications()->auth()->is_recipient_eligible( $user_id ) ) {
		return new WP_Error( 'sun_auth_required', __( 'Authentication and current account eligibility are required.', 'sabri-unified-notifications' ) );
	}
	return sun_notifications()->subscriptions()->update( $user_id, $input );
}
