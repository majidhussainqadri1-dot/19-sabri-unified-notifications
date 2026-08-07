<?php
/**
 * Canonical same-origin deep-link validation and click-time authorization.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Deep_Link {
	/**
	 * Validate and canonicalize a target.
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
		if ( ! is_array( $target ) || empty( $target['host'] ) || empty( $home['host'] ) || strtolower( $target['host'] ) !== strtolower( $home['host'] ) ) {
			return '';
		}
		$scheme = strtolower( (string) ( $target['scheme'] ?? 'https' ) );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}
		$path = (string) ( $target['path'] ?? '/' );
		if ( preg_match( '#(?:^|/)\.\.(?:/|$)#', $path ) ) {
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
}
