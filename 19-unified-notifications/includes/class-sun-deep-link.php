<?php
/**
 * Canonical same-origin deep-link validation and click-time authorization.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Deep_Link {
	/**
	 * Validate and canonicalize a target against the exact site origin.
	 * Host-only comparison is insufficient because scheme/port changes can cross
	 * an origin boundary or downgrade transport security.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function sanitize( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) {
			$url = home_url( $url );
		}

		$home   = wp_parse_url( home_url( '/' ) );
		$target = wp_parse_url( $url );
		if ( ! is_array( $home ) || ! is_array( $target ) || empty( $home['host'] ) || empty( $target['host'] ) ) {
			return '';
		}

		$home_scheme   = strtolower( (string) ( $home['scheme'] ?? 'https' ) );
		$target_scheme = strtolower( (string) ( $target['scheme'] ?? $home_scheme ) );
		if ( ! in_array( $home_scheme, array( 'http', 'https' ), true ) || ! hash_equals( $home_scheme, $target_scheme ) ) {
			return '';
		}
		if ( ! hash_equals( strtolower( (string) $home['host'] ), strtolower( (string) $target['host'] ) ) ) {
			return '';
		}

		$home_port   = self::effective_port( $home_scheme, isset( $home['port'] ) ? (int) $home['port'] : null );
		$target_port = self::effective_port( $target_scheme, isset( $target['port'] ) ? (int) $target['port'] : null );
		if ( $home_port !== $target_port ) {
			return '';
		}

		$path = (string) ( $target['path'] ?? '/' );
		if ( preg_match( '#(?:^|/)\.\.(?:/|$)#', rawurldecode( $path ) ) ) {
			return '';
		}
		if ( isset( $target['user'] ) || isset( $target['pass'] ) ) {
			return '';
		}

		$allowed = (bool) apply_filters( 'sun_deep_link_allowed', true, $url, $target );
		return $allowed ? esc_url_raw( $url ) : '';
	}

	/**
	 * Create the protected notification-open URL.
	 *
	 * @param string $notification_public_id Notification ID.
	 * @return string
	 */
	public static function wrapper_url( $notification_public_id ) {
		return home_url( '/notifications/open/' . rawurlencode( $notification_public_id ) . '/' );
	}

	/**
	 * @param string   $scheme Scheme.
	 * @param int|null $port Explicit port.
	 * @return int
	 */
	private static function effective_port( $scheme, $port ) {
		if ( null !== $port && $port > 0 ) {
			return $port;
		}
		return 'https' === $scheme ? 443 : 80;
	}
}
