<?php
/**
 * Authorization boundary and File 00 assertion integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Auth {
	/**
	 * Return versioned minimal identity assertions from the canonical File 00 owner.
	 * Local File 19 metadata is never treated as identity truth.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>
	 */
	public function assertions( $user_id ) {
		$user_id = absint( $user_id );
		$user    = get_userdata( $user_id );
		$base    = array(
			'contract'           => 'sun.identity.v2',
			'user_id'            => $user_id,
			'owner_available'    => false,
			'active'             => false,
			'verified'           => false,
			'email_verified'     => false,
			'phone_verified'     => false,
			'suspended'          => true,
			'revoked'            => false,
			'risk_blocked'       => false,
			'guardian_ok'        => false,
			'consent_ok'         => false,
			'step_up_verified'   => false,
			'founder'            => false,
			'institutional_role' => '',
			'locale'             => $user ? get_user_locale( $user_id ) : 'en_US',
			'timezone'           => '',
		);

		/*
		 * Canonical File 00 contract. Null means the owner is unavailable and
		 * protected actions fail closed instead of trusting duplicate user-meta.
		 */
		$claims = apply_filters( 'sabri_membership_claims_v2', null, $user_id );
		if ( is_array( $claims ) ) {
			$base['owner_available']    = true;
			$base['active']             = ! empty( $claims['active'] );
			$base['verified']           = ! empty( $claims['verified'] ) || ! empty( $claims['identity_verified'] );
			$base['email_verified']     = ! empty( $claims['email_verified'] );
			$base['phone_verified']     = ! empty( $claims['phone_verified'] ) || ! empty( $claims['mobile_verified'] );
			$base['suspended']          = ! empty( $claims['suspended'] );
			$base['revoked']            = ! empty( $claims['revoked'] );
			$base['risk_blocked']       = ! empty( $claims['risk_blocked'] ) || ( isset( $claims['risk_state'] ) && in_array( $claims['risk_state'], array( 'blocked', 'denied' ), true ) );
			$base['guardian_ok']        = array_key_exists( 'guardian_ok', $claims ) ? (bool) $claims['guardian_ok'] : empty( $claims['guardian_required'] );
			$base['consent_ok']         = array_key_exists( 'consent_ok', $claims ) ? (bool) $claims['consent_ok'] : true;
			$base['step_up_verified']   = ! empty( $claims['step_up_verified'] ) || ! empty( $claims['recent_step_up'] );
			$base['founder']            = ! empty( $claims['founder'] );
			$base['institutional_role'] = sanitize_key( (string) ( $claims['institutional_role'] ?? '' ) );
			if ( ! empty( $claims['locale'] ) ) {
				$base['locale'] = sanitize_locale_name( (string) $claims['locale'] );
			}
			if ( ! empty( $claims['timezone'] ) ) {
				$base['timezone'] = sanitize_text_field( (string) $claims['timezone'] );
			}
		}

		return (array) apply_filters( 'sun_identity_assertions', $base, $user_id );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_recipient_eligible( $user_id ) {
		return $this->claims_are_active_and_trusted( $this->assertions( $user_id ) );
	}

	/** @param int $notification_recipient Recipient ID. @return bool */
	public function can_access_notification( $notification_recipient ) {
		return is_user_logged_in() && get_current_user_id() === absint( $notification_recipient ) && $this->is_recipient_eligible( get_current_user_id() );
	}

	/** @return bool */
	public function can_manage() {
		$user_id = get_current_user_id();
		return $user_id > 0
			&& $this->claims_are_active_and_trusted( $this->assertions( $user_id ) )
			&& ( current_user_can( 'manage_sabri_notifications' ) || current_user_can( 'manage_options' ) );
	}

	/** @return bool */
	public function can_view_health() {
		$user_id = get_current_user_id();
		return $user_id > 0
			&& $this->claims_are_active_and_trusted( $this->assertions( $user_id ) )
			&& ( current_user_can( 'view_sabri_notification_health' ) || $this->can_manage() );
	}

	/** @return bool */
	public function can_retry() {
		$user_id = get_current_user_id();
		return $user_id > 0
			&& $this->claims_are_active_and_trusted( $this->assertions( $user_id ) )
			&& ( current_user_can( 'retry_sabri_notification_delivery' ) || $this->can_manage() );
	}

	/** @return bool */
	public function can_send_bulk() {
		$user_id = get_current_user_id();
		$claims  = $this->assertions( $user_id );
		return $user_id > 0
			&& $this->claims_are_active_and_trusted( $claims )
			&& ! empty( $claims['step_up_verified'] )
			&& current_user_can( 'send_sabri_bulk_notifications' )
			&& $this->is_founder( $user_id );
	}

	/**
	 * Revalidate a stored actor for background/cron execution.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $require_step_up Whether recent step-up evidence is required.
	 * @return bool
	 */
	public function is_governance_actor_eligible( $user_id, $require_step_up = false ) {
		$claims = $this->assertions( $user_id );
		if ( ! $this->claims_are_active_and_trusted( $claims ) ) {
			return false;
		}
		if ( $require_step_up && empty( $claims['step_up_verified'] ) ) {
			return false;
		}
		return $this->is_founder( $user_id );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_founder( $user_id ) {
		$user_id = absint( $user_id );
		$claims  = $this->assertions( $user_id );
		if ( ! $this->claims_are_active_and_trusted( $claims ) ) {
			return false;
		}

		if ( ! empty( $claims['founder'] ) || 'founder' === $claims['institutional_role'] ) {
			return (bool) apply_filters( 'sun_is_founder', true, $user_id, $claims );
		}

		/*
		 * Compatibility bootstrap is permitted only when File 00 is available and
		 * has not supplied a conflicting institutional role. It can never bypass
		 * suspension/revocation/risk/guardian/consent checks or owner outage.
		 */
		$configured = defined( 'SUN_FOUNDER_USER_ID' ) ? absint( SUN_FOUNDER_USER_ID ) : 0;
		$bootstrap  = '' === $claims['institutional_role'] && $configured > 0 && $configured === $user_id;
		return (bool) apply_filters( 'sun_is_founder', $bootstrap, $user_id, $claims );
	}

	/**
	 * @param array<string,mixed> $claims Claims.
	 * @return bool
	 */
	private function claims_are_active_and_trusted( array $claims ) {
		$ok = ! empty( $claims['owner_available'] )
			&& ! empty( $claims['active'] )
			&& ! empty( $claims['verified'] )
			&& empty( $claims['suspended'] )
			&& empty( $claims['revoked'] )
			&& empty( $claims['risk_blocked'] )
			&& ! empty( $claims['guardian_ok'] )
			&& ! empty( $claims['consent_ok'] );
		return (bool) apply_filters( 'sun_recipient_eligible', $ok, $claims, absint( $claims['user_id'] ?? 0 ) );
	}
}
