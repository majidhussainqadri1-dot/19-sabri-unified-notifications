<?php
/**
 * Authorization boundary and canonical File 00 / File 02 integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Auth {
	/**
	 * Return minimal identity assertions from the canonical File 00 owner.
	 * File 19 never promotes local metadata into identity authority.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>
	 */
	public function assertions( $user_id ) {
		$user_id = absint( $user_id );
		$user    = get_userdata( $user_id );
		$base    = array(
			'contract'           => 'sun.identity.v3',
			'source_contract'    => '',
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
			'founder'            => false,
			'institutional_role' => '',
			'locale'             => $user ? get_user_locale( $user_id ) : 'en_US',
			'timezone'           => '',
		);

		$claims = $this->file00_assertions( $user_id );
		if ( ! is_array( $claims ) ) {
			return $base;
		}

		$contract = sanitize_text_field( (string) ( $claims['contract_version'] ?? $claims['contract'] ?? '' ) );
		$subject  = absint( $claims['user_id'] ?? 0 );
		if ( $subject !== $user_id || '' === $contract ) {
			return $base;
		}

		$status        = sanitize_key( (string) ( $claims['status'] ?? '' ) );
		$eligible      = ! empty( $claims['eligible'] );
		$approved      = array_key_exists( 'approved', $claims ) ? ! empty( $claims['approved'] ) : $eligible;
		$suspended     = ! empty( $claims['suspended'] ) || in_array( $status, array( 'suspended','rejected','expired','appeal_review','erasure_pending','invalid_application' ), true );
		$identity_ok   = array_key_exists( 'identity_documents_current', $claims ) ? ! empty( $claims['identity_documents_current'] ) : ( array_key_exists( 'identity_evidence_current', $claims ) ? ! empty( $claims['identity_evidence_current'] ) : $eligible );
		$guardian_ok   = array_key_exists( 'guardian_verified', $claims ) ? ! empty( $claims['guardian_verified'] ) : ( array_key_exists( 'guardian_ok', $claims ) ? ! empty( $claims['guardian_ok'] ) : true );
		$consent_ok    = array_key_exists( 'consent_ok', $claims ) ? ! empty( $claims['consent_ok'] ) : true;
		$account_class = sanitize_key( (string) ( $claims['account_class'] ?? $claims['institutional_role'] ?? '' ) );

		$base['source_contract']    = $contract;
		$base['owner_available']    = true;
		$base['active']             = $eligible && $approved && ! $suspended;
		$base['verified']           = $identity_ok && $approved;
		$base['email_verified']     = ! empty( $claims['email_verified'] );
		$base['phone_verified']     = ! empty( $claims['phone_verified'] ) || ! empty( $claims['mobile_verified'] );
		$base['suspended']          = $suspended;
		$base['revoked']            = ! empty( $claims['revoked'] ) || 'erasure_pending' === $status;
		$risk_state                 = sanitize_key( (string) ( $claims['risk_state'] ?? '' ) );
		$base['risk_blocked']       = ! empty( $claims['risk_blocked'] ) || in_array( $risk_state, array( 'blocked','denied' ), true );
		$base['guardian_ok']        = $guardian_ok;
		$base['consent_ok']         = $consent_ok;
		$base['founder']            = ! empty( $claims['founder'] ) || 'founder' === $account_class;
		$base['institutional_role'] = $account_class;
		if ( ! empty( $claims['locale'] ) ) {
			$base['locale'] = sanitize_locale_name( (string) $claims['locale'] );
		}
		if ( ! empty( $claims['timezone'] ) ) {
			$base['timezone'] = sanitize_text_field( (string) $claims['timezone'] );
		}
		return $base;
	}

	/**
	 * Resolve the current canonical File 00 assertion.
	 *
	 * Current File 00 exposes SMC_Contracts::assertions() and smc_assertions_v1.
	 * The historical sabri_membership_claims_v2 contract is retained only as a
	 * bounded backwards-compatibility fallback for older deployments.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>|null
	 */
	private function file00_assertions( $user_id ) {
		if ( class_exists( 'SMC_Contracts' ) && is_callable( array( 'SMC_Contracts', 'assertions' ) ) ) {
			try {
				$result = SMC_Contracts::assertions( $user_id );
				if ( is_array( $result ) ) {
					return $result;
				}
			} catch ( Throwable $error ) {
				unset( $error );
				return null;
			}
		}

		if ( false !== has_filter( 'smc_assertions_v1' ) ) {
			$result = apply_filters( 'smc_assertions_v1', array(), $user_id );
			if ( is_array( $result ) && absint( $result['user_id'] ?? 0 ) === absint( $user_id ) ) {
				return $result;
			}
		}

		if ( false !== has_filter( 'sabri_membership_claims_v2' ) ) {
			$result = apply_filters( 'sabri_membership_claims_v2', null, $user_id );
			if ( is_array( $result ) ) {
				return $result;
			}
		}
		return null;
	}

	/**
	 * Consume File 02 purpose/scope-bound strong-auth assurance.
	 *
	 * @param int    $user_id User ID.
	 * @param string $purpose Purpose.
	 * @param string $scope Opaque bounded scope.
	 * @return bool
	 */
	public function has_current_step_up( $user_id, $purpose = 'notification_governance', $scope = 'file19:governance' ) {
		$user_id = absint( $user_id );
		if ( $user_id < 1 || ! class_exists( 'SA_Authentication_Assurance' ) || ! is_callable( array( 'SA_Authentication_Assurance', 'assertion' ) ) ) {
			return false;
		}
		try {
			$result = SA_Authentication_Assurance::assertion( $user_id, sanitize_key( $purpose ), substr( sanitize_text_field( $scope ), 0, 512 ) );
		} catch ( Throwable $error ) {
			unset( $error );
			return false;
		}
		if ( ! is_array( $result ) ) {
			return false;
		}
		return 'valid' === (string) ( $result['result'] ?? '' )
			&& 'aal2' === (string) ( $result['assurance_level'] ?? '' )
			&& 'webauthn_passkey' === (string) ( $result['method'] ?? '' )
			&& 'sa.cf01.authentication-assurance' === (string) ( $result['contract'] ?? '' );
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
			&& $this->has_current_step_up( $user_id, 'notification_governance', 'file19:bulk-send' )
			&& current_user_can( 'send_sabri_bulk_notifications' )
			&& $this->is_founder( $user_id );
	}

	/** @param int $user_id User ID. @param bool $require_step_up Require recent step-up. @return bool */
	public function is_governance_actor_eligible( $user_id, $require_step_up = false ) {
		$claims = $this->assertions( $user_id );
		if ( ! $this->claims_are_active_and_trusted( $claims ) ) {
			return false;
		}
		if ( $require_step_up && ! $this->has_current_step_up( $user_id, 'notification_governance', 'file19:governance' ) ) {
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

		$configured = defined( 'SUN_FOUNDER_USER_ID' ) ? absint( SUN_FOUNDER_USER_ID ) : 0;
		$bootstrap  = '' === $claims['institutional_role']
			&& $configured > 0
			&& $configured === $user_id
			&& (bool) apply_filters( 'sun_allow_founder_bootstrap', false, $user_id, $claims );
		return (bool) apply_filters( 'sun_is_founder', $bootstrap, $user_id, $claims );
	}

	/** @return bool */
	public static function file00_contract_available() {
		return ( class_exists( 'SMC_Contracts' ) && is_callable( array( 'SMC_Contracts', 'assertions' ) ) )
			|| false !== has_filter( 'smc_assertions_v1' )
			|| false !== has_filter( 'sabri_membership_claims_v2' );
	}

	/** @return bool */
	public static function file02_assurance_available() {
		return class_exists( 'SA_Authentication_Assurance' ) && is_callable( array( 'SA_Authentication_Assurance', 'assertion' ) );
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
