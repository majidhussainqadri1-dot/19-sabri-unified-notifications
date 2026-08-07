<?php
/**
 * Authorization boundary and File 00 assertion integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Auth {
	/**
	 * Return current File 00 v2 identity assertions.
	 *
	 * Protected notification actions fail closed when the canonical membership
	 * assertion provider is unavailable. Legacy/local user metadata is never an
	 * authority substitute. Local WordPress data is used only for locale/timezone
	 * presentation fallbacks.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>
	 */
	public function assertions( $user_id ) {
		$user_id = absint( $user_id );
		$user    = get_userdata( $user_id );
		$claims  = apply_filters( 'sabri_membership_claims_v2', null, $user_id );
		$claims  = is_array( $claims ) ? $claims : array();
		$available = ! empty( $claims );
		$status = sanitize_key( (string) ( $claims['account_status'] ?? $claims['status'] ?? 'unknown' ) );
		$blocked_states = array( 'suspended', 'revoked', 'blocked', 'security_hold', 'deleted', 'rejected' );
		$suspended = in_array( $status, $blocked_states, true )
			|| ! empty( $claims['suspended'] )
			|| ! empty( $claims['revoked'] )
			|| ! empty( $claims['security_hold'] );
		$risk_hold = ! empty( $claims['risk_hold'] )
			|| in_array( sanitize_key( (string) ( $claims['risk_state'] ?? '' ) ), array( 'hold', 'blocked', 'critical' ), true );
		$minor = ! empty( $claims['is_minor'] ) || ! empty( $claims['minor'] );
		$guardian_ok = ! $minor || ! empty( $claims['guardian_consent_verified'] ) || ! empty( $claims['guardian_verified'] );
		$consent_ok = ! array_key_exists( 'notification_processing_consent', $claims ) || ! empty( $claims['notification_processing_consent'] );
		$approved = $available
			&& ! $suspended
			&& ! $risk_hold
			&& $guardian_ok
			&& $consent_ok
			&& in_array( $status, array( 'approved', 'active', 'verified' ), true );
		$locale = isset( $claims['locale'] ) ? sanitize_locale_name( (string) $claims['locale'] ) : ( $user ? get_user_locale( $user_id ) : 'en_US' );
		$timezone = isset( $claims['timezone'] ) ? sanitize_text_field( (string) $claims['timezone'] ) : (string) get_user_meta( $user_id, 'sun_timezone', true );

		return array(
			'contract'          => 'sun.identity.v2',
			'claims_available'  => $available,
			'claims_version'    => sanitize_text_field( (string) ( $claims['claims_version'] ?? $claims['version'] ?? '' ) ),
			'user_id'           => $user_id,
			'active'            => $approved,
			'suspended'         => $suspended,
			'risk_hold'         => $risk_hold,
			'minor'             => $minor,
			'guardian_ok'       => $guardian_ok,
			'consent_ok'        => $consent_ok,
			'email_verified'    => $approved && ! empty( $claims['email_verified'] ),
			'phone_verified'    => $approved && ! empty( $claims['phone_verified'] ),
			'founder'           => $approved && ( ! empty( $claims['institutional_founder'] ) || ! empty( $claims['founder'] ) ),
			'verified_doctor'   => $approved && ! empty( $claims['verified_doctor'] ),
			'locale'            => $locale ?: 'en_US',
			'timezone'          => $timezone,
			'reason'            => $this->eligibility_reason( $available, $suspended, $risk_hold, $guardian_ok, $consent_ok, $status ),
		);
	}

	/** @param int $user_id User ID. @return bool */
	public function is_recipient_eligible( $user_id ) {
		$claims = $this->assertions( $user_id );
		$ok     = ! empty( $claims['active'] ) && empty( $claims['suspended'] ) && empty( $claims['risk_hold'] ) && ! empty( $claims['guardian_ok'] ) && ! empty( $claims['consent_ok'] );
		return (bool) apply_filters( 'sun_recipient_eligible', $ok, $claims, absint( $user_id ) );
	}

	/** @param int $notification_recipient Recipient ID. @return bool */
	public function can_access_notification( $notification_recipient ) {
		return is_user_logged_in() && get_current_user_id() === absint( $notification_recipient ) && $this->is_recipient_eligible( get_current_user_id() );
	}

	/** @return bool */
	public function can_manage() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->is_recipient_eligible( $user_id ) && ( current_user_can( 'manage_sabri_notifications' ) || current_user_can( 'manage_options' ) );
	}

	/** @return bool */
	public function can_view_health() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->is_recipient_eligible( $user_id ) && ( current_user_can( 'view_sabri_notification_health' ) || $this->can_manage() );
	}

	/** @return bool */
	public function can_retry() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->is_recipient_eligible( $user_id ) && ( current_user_can( 'retry_sabri_notification_delivery' ) || $this->can_manage() );
	}

	/** @return bool */
	public function can_send_bulk() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->is_recipient_eligible( $user_id ) && current_user_can( 'send_sabri_bulk_notifications' ) && $this->is_founder( $user_id );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_founder( $user_id ) {
		$claims = $this->assertions( $user_id );
		$founder = ! empty( $claims['claims_available'] ) && ! empty( $claims['active'] ) && ! empty( $claims['founder'] );
		return (bool) apply_filters( 'sun_is_founder', $founder, absint( $user_id ), $claims );
	}

	/** @return string */
	private function eligibility_reason( $available, $suspended, $risk_hold, $guardian_ok, $consent_ok, $status ) {
		if ( ! $available ) {
			return 'membership-provider-unavailable';
		}
		if ( $suspended ) {
			return 'account-blocked';
		}
		if ( $risk_hold ) {
			return 'risk-hold';
		}
		if ( ! $guardian_ok ) {
			return 'guardian-verification-required';
		}
		if ( ! $consent_ok ) {
			return 'notification-consent-required';
		}
		if ( ! in_array( $status, array( 'approved', 'active', 'verified' ), true ) ) {
			return 'account-not-approved';
		}
		return '';
	}
}
