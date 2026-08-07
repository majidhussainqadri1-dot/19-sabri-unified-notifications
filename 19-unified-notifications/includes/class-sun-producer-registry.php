<?php
/**
 * Versioned producer registry and request signature verification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Producer_Registry {
	/** @var array<string,array<string,mixed>> */
	private static $runtime = array();

	/**
	 * Register one producer contract at runtime.
	 *
	 * @param string              $producer Producer key.
	 * @param array<string,mixed> $config Contract.
	 * @return bool
	 */
	public static function register_runtime( $producer, array $config ) {
		$producer = sanitize_key( $producer );
		if ( '' === $producer || empty( $config['owner'] ) ) {
			return false;
		}
		self::$runtime[ $producer ] = $config;
		return true;
	}

	/**
	 * Return the registry after companion-module filters.
	 *
	 * File 19 owns only its notification/system facts. Account Security.*, clinical
	 * Safety.*, appointment, publication, marketplace and communication facts must
	 * be explicitly registered by their canonical owners.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		$registry = self::$runtime;
		$registry['sabri-system'] = $registry['sabri-system'] ?? array(
			'owner'       => 'File 19',
			'event_types' => array( 'System.*' ),
			'internal'    => true,
		);
		return (array) apply_filters( 'sun_registered_producers', $registry );
	}

	/** @param string $producer Producer. @return array<string,mixed>|null */
	public function get( $producer ) {
		$all = $this->all();
		return isset( $all[ $producer ] ) && is_array( $all[ $producer ] ) ? $all[ $producer ] : null;
	}

	/**
	 * Verify that a producer is registered and may emit this event type.
	 *
	 * @param string $producer Producer.
	 * @param string $event_type Event type.
	 * @return true|WP_Error
	 */
	public function authorize_type( $producer, $event_type ) {
		$config = $this->get( $producer );
		if ( ! $config ) {
			return new WP_Error( 'sun_unknown_producer', __( 'The notification producer is not registered.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		$patterns = isset( $config['event_types'] ) && is_array( $config['event_types'] ) ? $config['event_types'] : array();
		foreach ( $patterns as $pattern ) {
			if ( $this->matches_pattern( $event_type, (string) $pattern ) ) {
				return true;
			}
		}
		return new WP_Error( 'sun_event_type_denied', __( 'This producer is not authorized for the event type.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
	}

	/**
	 * Verify a REST signature.
	 *
	 * Signature contract: HMAC-SHA256 of "timestamp\nraw-body" in hex.
	 *
	 * @param string $producer Producer.
	 * @param string $timestamp Unix timestamp.
	 * @param string $signature Hex signature.
	 * @param string $raw_body Raw body.
	 * @return true|WP_Error
	 */
	public function verify_signature( $producer, $timestamp, $signature, $raw_body ) {
		$config = $this->get( $producer );
		if ( ! $config ) {
			return new WP_Error( 'sun_unknown_producer', __( 'The notification producer is not registered.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		if ( ! empty( $config['verify_callback'] ) && is_callable( $config['verify_callback'] ) ) {
			$result = call_user_func( $config['verify_callback'], $timestamp, $signature, $raw_body, $producer );
			return true === $result ? true : new WP_Error( 'sun_signature_invalid', __( 'The producer signature is invalid.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		if ( ! is_numeric( $timestamp ) || ! preg_match( '/^[0-9]{9,12}$/', (string) $timestamp ) || abs( time() - (int) $timestamp ) > (int) apply_filters( 'sun_producer_replay_window', 300, $producer ) ) {
			return new WP_Error( 'sun_signature_expired', __( 'The producer request is outside the allowed replay window.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		if ( ! preg_match( '/^[a-f0-9]{64}$/', strtolower( (string) $signature ) ) ) {
			return new WP_Error( 'sun_signature_invalid', __( 'The producer signature is invalid.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
		}
		$secret = $this->resolve_secret( $producer, $config );
		if ( '' === $secret ) {
			return new WP_Error( 'sun_signature_unconfigured', __( 'The producer signature verifier is not configured.', 'sabri-unified-notifications' ), array( 'status' => 503 ) );
		}
		$expected = hash_hmac( 'sha256', $timestamp . "\n" . $raw_body, $secret );
		return hash_equals( $expected, strtolower( (string) $signature ) ) ? true : new WP_Error( 'sun_signature_invalid', __( 'The producer signature is invalid.', 'sabri-unified-notifications' ), array( 'status' => 403 ) );
	}

	/** @param string $event_type Event type. @param string $pattern Pattern. @return bool */
	public function matches_pattern( $event_type, $pattern ) {
		if ( '*' === $pattern ) {
			return true;
		}
		if ( str_ends_with( $pattern, '.*' ) ) {
			return str_starts_with( $event_type, substr( $pattern, 0, -1 ) );
		}
		return hash_equals( $pattern, $event_type );
	}

	/**
	 * @param string              $producer Producer.
	 * @param array<string,mixed> $config Config.
	 * @return string
	 */
	private function resolve_secret( $producer, array $config ) {
		if ( ! empty( $config['secret_callback'] ) && is_callable( $config['secret_callback'] ) ) {
			return (string) call_user_func( $config['secret_callback'], $producer );
		}
		$constant = 'SUN_PRODUCER_' . strtoupper( str_replace( '-', '_', $producer ) ) . '_SECRET';
		return defined( $constant ) ? (string) constant( $constant ) : '';
	}
}
