<?php
/**
 * Public integration functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @param array<string,mixed> $event Event envelope. @return array<string,mixed>|WP_Error */
function sun_ingest_domain_event( array $event ) {
	return sun_notifications()->notifications()->ingest_event( $event, 'php' );
}

/** @return string */
function sun_render_notification_bell() {
	return sun_notifications()->renderer()->render_bell();
}

/** @param int|null $user_id User ID, current user when omitted. @return int */
function sun_get_unread_count( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );
	return sun_notifications()->notifications()->get_unread_count( $user_id );
}

/** @param string $producer Producer identifier. @param array<string,mixed> $config Producer contract. @return bool */
function sun_register_notification_producer( $producer, array $config ) {
	return SUN_Producer_Registry::register_runtime( $producer, $config );
}

/**
 * Save an own-user granular subscription from a contextual Follow/Subscribe UI.
 * Cross-user writes require an explicit host authorization filter.
 *
 * @param int $user_id User ID.
 * @param string $scope_type person|topic|community|course|event|doctor|channel.
 * @param string $scope_id Native owner stable identifier.
 * @param bool $enabled Enabled.
 * @param string $frequency immediate|daily|weekly.
 * @param int|null $version Optional optimistic-concurrency version.
 * @return array<string,mixed>|WP_Error
 */
function sun_set_notification_subscription( $user_id, $scope_type, $scope_id, $enabled = true, $frequency = 'immediate', $version = null ) {
	$user_id = absint( $user_id );
	$allowed = is_user_logged_in() && get_current_user_id() === $user_id && sun_notifications()->notifications() && sun_notifications()->subscriptions();
	$allowed = (bool) apply_filters( 'sun_can_manage_subscription_for_user', $allowed, $user_id, $scope_type, $scope_id );
	if ( ! $allowed ) {
		return new WP_Error( 'sun_subscription_forbidden', __( 'You cannot change this notification subscription.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
	}
	$input = array( 'scope_type'=>$scope_type, 'scope_id'=>$scope_id, 'enabled'=>(bool)$enabled, 'frequency'=>$frequency );
	if ( null !== $version ) { $input['version'] = absint( $version ); }
	return sun_notifications()->subscriptions()->upsert( $user_id, $input );
}

/**
 * Machine-readable capability/ownership contract for companion modules and diagnostics.
 *
 * @return array<string,mixed>
 */
function sun_notification_capability_contract() {
	return SUN_Four_Plan_Compliance::snapshot();
}
