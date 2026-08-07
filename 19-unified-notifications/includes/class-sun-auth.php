<?php
/**
 * Authorization boundary and File 00 assertion integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Auth {
	/**
	 * Return versioned minimal identity assertions.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>
	 */
	public function assertions( $user_id ) {
		$user = get_userdata( absint( $user_id ) );
		$base = array(
			'contract'       => 'sun.identity.v1',
			'user_id'        => absint( $user_id ),
			'active'         => (bool) $user,
			'suspended'      => (bool) get_user_meta( $user_id, 'sun_suspended', true ),
			'email_verified' => (bool) get_user_meta( $user_id, 'sun_email_verified', true ),
			'phone_verified' => (bool) get_user_meta( $user_id, 'sun_phone_verified', true ),
			'guardian_ok'    => ! (bool) get_user_meta( $user_id, 'sun_guardian_required', true ) || (bool) get_user_meta( $user_id, 'sun_guardian_verified', true ),
			'locale'         => $user ? get_user_locale( $user_id ) : 'en_US',
			'timezone'       => (string) get_user_meta( $user_id, 'sun_timezone', true ),
		);
		return (array) apply_filters( 'sun_identity_assertions', $base, absint( $user_id ) );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_recipient_eligible( $user_id ) {
		$claims = $this->assertions( $user_id );
		$ok     = ! empty( $claims['active'] ) && empty( $claims['suspended'] ) && ! empty( $claims['guardian_ok'] );
		return (bool) apply_filters( 'sun_recipient_eligible', $ok, $claims, absint( $user_id ) );
	}

	/** @param int $notification_recipient Recipient ID. @return bool */
	public function can_access_notification( $notification_recipient ) {
		return is_user_logged_in() && get_current_user_id() === absint( $notification_recipient ) && $this->is_recipient_eligible( get_current_user_id() );
	}

	/** @return bool */
	public function can_manage() {
		return current_user_can( 'manage_sabri_notifications' ) || current_user_can( 'manage_options' );
	}

	/** @return bool */
	public function can_view_health() {
		return current_user_can( 'view_sabri_notification_health' ) || $this->can_manage();
	}

	/** @return bool */
	public function can_retry() {
		return current_user_can( 'retry_sabri_notification_delivery' ) || $this->can_manage();
	}

	/** @return bool */
	public function can_send_bulk() {
		return current_user_can( 'send_sabri_bulk_notifications' ) && $this->is_founder( get_current_user_id() );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_founder( $user_id ) {
		$configured = defined( 'SUN_FOUNDER_USER_ID' ) ? absint( SUN_FOUNDER_USER_ID ) : 0;
		$default    = $configured > 0 && $configured === absint( $user_id );
		return (bool) apply_filters( 'sun_is_founder', $default, absint( $user_id ) );
	}
}
