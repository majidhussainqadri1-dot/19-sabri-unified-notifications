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

		/* Canonical File 00 contract. File 19 consumes File 00's public class API
		 * directly; private metadata and retired compatibility filters are not identity truth. */
		$claims = null;
		if ( class_exists( 'SMC_Contracts' ) && is_callable( array( 'SMC_Contracts', 'assertions' ) ) ) {
			try {
				$candidate = SMC_Contracts::assertions( $user_id );
				if ( is_array( $candidate )
					&& absint( $candidate['user_id'] ?? 0 ) === $user_id
					&& preg_match( '/^\\d+\\.\\d+(?:\\.\\d+)?$/', (string) ( $candidate['contract_version'] ?? '' ) ) ) {
					$claims = $candidate;
				}
			} catch ( Throwable $error ) {
				unset( $error );
			}
		}
		if ( is_array( $claims ) ) {
			$status = sanitize_key( (string) ( $claims['status'] ?? '' ) );
			$eligible = ! empty( $claims['eligible'] );
			$base['owner_available']    = true;
			$base['active']             = ! empty( $claims['approved'] ) && $eligible;
			$base['verified']           = $eligible && ! empty( $claims['identity_documents_current'] );
			$base['email_verified']     = ! empty( $claims['email_verified'] );
			$base['phone_verified']     = ! empty( $claims['phone_verified'] );
			$base['suspended']          = ! empty( $claims['suspended'] );
			$base['revoked']            = 'erasure_pending' === $status;
			$base['risk_blocked']       = in_array( $status, array( 'rejected', 'appeal_review', 'invalid_application' ), true );
			$base['guardian_ok']        = ! empty( $claims['guardian_verified'] );
			$base['consent_ok']         = $eligible;
			$base['step_up_verified']   = $this->file02_step_up_verified( $user_id );
			$base['founder']            = 'founder' === sanitize_key( (string) ( $claims['account_class'] ?? '' ) );
			$base['institutional_role'] = sanitize_key( (string) ( $claims['account_class'] ?? '' ) );
		}

		/*
		 * Security boundary: File 00 is the sole positive identity authority.
		 * File 19 intentionally exposes no post-processing filter that can turn a
		 * failed canonical assertion into active/verified/founder authority.
		 */
		return $base;
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
		return $user_id > 0 && $this->claims_are_active_and_trusted( $this->assertions( $user_id ) ) && ( current_user_can( 'manage_sabri_notifications' ) || current_user_can( 'manage_options' ) );
	}

	/** @return bool */
	public function can_view_health() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->claims_are_active_and_trusted( $this->assertions( $user_id ) ) && ( current_user_can( 'view_sabri_notification_health' ) || $this->can_manage() );
	}

	/** @return bool */
	public function can_retry() {
		$user_id = get_current_user_id();
		return $user_id > 0 && $this->claims_are_active_and_trusted( $this->assertions( $user_id ) ) && ( current_user_can( 'retry_sabri_notification_delivery' ) || $this->can_manage() );
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

	/** @param int $user_id User ID. @param bool $require_step_up Require recent step-up. @return bool */
	public function is_governance_actor_eligible( $user_id, $require_step_up = false ) {
		$claims = $this->assertions( $user_id );
		if ( ! $this->claims_are_active_and_trusted( $claims ) ) { return false; }
		if ( $require_step_up && empty( $claims['step_up_verified'] ) ) { return false; }
		return $this->is_founder( $user_id );
	}

	/** @param int $user_id User ID. @return bool */
	public function is_founder( $user_id ) {
		$user_id = absint( $user_id );
		$claims  = $this->assertions( $user_id );
		if ( ! $this->claims_are_active_and_trusted( $claims ) ) { return false; }
		if ( ! empty( $claims['founder'] ) || 'founder' === $claims['institutional_role'] ) {
			return (bool) apply_filters( 'sun_is_founder', true, $user_id, $claims );
		}

		/*
		 * Emergency/bootstrap compatibility never outranks File 00 silently.
		 * It requires an explicit host opt-in in addition to an exact configured ID.
		 */
		$configured = defined( 'SUN_FOUNDER_USER_ID' ) ? absint( SUN_FOUNDER_USER_ID ) : 0;
		$bootstrap  = '' === $claims['institutional_role']
			&& $configured > 0
			&& $configured === $user_id
			&& (bool) apply_filters( 'sun_allow_founder_bootstrap', false, $user_id, $claims );
		return (bool) apply_filters( 'sun_is_founder', $bootstrap, $user_id, $claims );
	}

	/** Current File 02 passkey assurance. File 00 never supplies authentication step-up. */
	private function file02_step_up_verified( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id < 1 || ! is_user_logged_in() || get_current_user_id() !== $user_id
			|| ! class_exists( 'SAUTH_Passkey_Runtime' ) || ! is_callable( array( 'SAUTH_Passkey_Runtime', 'current_assurance' ) ) ) {
			return false;
		}
		try {
			$assurance = SAUTH_Passkey_Runtime::current_assurance( $user_id );
		} catch ( Throwable $error ) {
			unset( $error );
			return false;
		}
		if ( ! is_array( $assurance ) || empty( $assurance['passkey_asserted'] )
			|| 'file02' !== (string) ( $assurance['owner'] ?? '' )
			|| 'webauthn_passkey' !== (string) ( $assurance['method'] ?? '' ) ) {
			return false;
		}
		if ( defined( 'SAUTH_PASSKEY_CONTRACT_VERSION' )
			&& (string) SAUTH_PASSKEY_CONTRACT_VERSION !== (string) ( $assurance['contract_version'] ?? '' ) ) {
			return false;
		}
		$verified_at = absint( $assurance['verified_at'] ?? 0 );
		return $verified_at > 0 && $verified_at <= time() + 60 && $verified_at >= time() - 300;
	}

	/** @param array<string,mixed> $claims Claims. @return bool */
	private function claims_are_active_and_trusted( array $claims ) {
		return ! empty( $claims['owner_available'] )
			&& ! empty( $claims['active'] )
			&& ! empty( $claims['verified'] )
			&& empty( $claims['suspended'] )
			&& empty( $claims['revoked'] )
			&& empty( $claims['risk_blocked'] )
			&& ! empty( $claims['guardian_ok'] )
			&& ! empty( $claims['consent_ok'] );
	}
}
