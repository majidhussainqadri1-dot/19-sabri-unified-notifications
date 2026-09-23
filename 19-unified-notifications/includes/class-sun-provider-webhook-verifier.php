<?php
/**
 * Provider-neutral signed webhook verification with timestamp and durable replay prevention.
 * Providers with native signature schemes may register sun_verify_provider_webhook.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Provider_Webhook_Verifier {
	/** @param string $channel Channel. @param array<string,mixed> $payload Payload. @param WP_REST_Request|null $request Request. @return true|WP_Error */
	public static function verify( $channel, array $payload, $request = null ) {
		$channel = sanitize_key( $channel );
		if ( has_filter( 'sun_verify_provider_webhook' ) ) {
			$custom = apply_filters( 'sun_verify_provider_webhook', false, $channel, $payload, $request );
			return true === $custom ? true : ( is_wp_error( $custom ) ? $custom : new WP_Error( 'sun_webhook_unverified', __( 'Provider webhook verification failed.', 'sabri-unified-notifications' ), array( 'status' => 401 ) ) );
		}
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_header' ) || ! method_exists( $request, 'get_body' ) ) { return new WP_Error( 'sun_webhook_request_missing', __( 'Provider webhook request evidence is missing.', 'sabri-unified-notifications' ), array( 'status' => 401 ) ); }
		$provider = sanitize_key( (string) $request->get_header( 'x-sun-provider' ) );
		$timestamp = (string) $request->get_header( 'x-sun-timestamp' );
		$signature = strtolower( trim( (string) $request->get_header( 'x-sun-signature' ) ) );
		$window = max( 60, min( 3600, (int) apply_filters( 'sun_provider_webhook_replay_window', 300, $channel, $provider ) ) );
		if ( '' === $provider || ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > $window || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) ) { return new WP_Error( 'sun_webhook_signature_invalid', __( 'Provider webhook signature metadata is invalid or expired.', 'sabri-unified-notifications' ), array( 'status' => 401 ) ); }
		$secret = self::secret( $channel, $provider ); if ( '' === $secret ) { return new WP_Error( 'sun_webhook_signature_unconfigured', __( 'Provider webhook verification is not configured.', 'sabri-unified-notifications' ), array( 'status' => 503 ) ); }
		$raw = (string) $request->get_body(); $expected = hash_hmac( 'sha256', $timestamp . "\n" . $raw, $secret );
		if ( ! hash_equals( $expected, $signature ) ) { return new WP_Error( 'sun_webhook_signature_invalid', __( 'Provider webhook signature verification failed.', 'sabri-unified-notifications' ), array( 'status' => 401 ) ); }
		$replay_key = hash( 'sha256', $channel . '|' . $provider . '|' . $timestamp . '|' . $signature );
		global $wpdb; $table = SUN_Database::table( 'webhook_receipts' ); $now = SUN_Database::now();
		$inserted = $wpdb->insert( $table, array( 'replay_key'=>$replay_key,'channel'=>$channel,'provider_key'=>$provider,'received_at'=>$now,'expires_at'=>gmdate('Y-m-d H:i:s',time()+2*$window) ) );
		if ( false === $inserted ) {
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE replay_key=%s LIMIT 1", $replay_key ) );
			return new WP_Error( $exists ? 'sun_webhook_replay' : 'sun_webhook_replay_store_failed', $exists ? __( 'This provider webhook was already processed.', 'sabri-unified-notifications' ) : __( 'Provider webhook replay evidence could not be stored safely.', 'sabri-unified-notifications' ), array( 'status' => $exists ? 409 : 500 ) );
		}
		SUN_Audit::record( 'provider_webhook_verified', 'provider_webhook', $replay_key, array( 'channel'=>$channel,'provider'=>$provider,'purpose'=>'provider_status' ), 0 );
		return true;
	}

	/** @param string $channel Channel. @param string $provider Provider. @return string */
	private static function secret( $channel, $provider ) {
		$default = '';
		$names = array(
			'SUN_PROVIDER_' . strtoupper( preg_replace( '/[^A-Za-z0-9]+/', '_', $channel . '_' . $provider ) ) . '_WEBHOOK_SECRET',
			'SUN_PROVIDER_' . strtoupper( preg_replace( '/[^A-Za-z0-9]+/', '_', $channel ) ) . '_WEBHOOK_SECRET',
		);
		foreach ( $names as $name ) { if ( defined( $name ) && '' !== trim( (string) constant( $name ) ) ) { $default = trim( (string) constant( $name ) ); break; } }
		return (string) apply_filters( 'sun_provider_webhook_secret', $default, $channel, $provider );
	}
}
