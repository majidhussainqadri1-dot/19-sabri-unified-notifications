<?php
/**
 * Encryption and signing primitives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Crypto {
	/**
	 * Encrypt private notification data at rest.
	 *
	 * @param string $plaintext Plaintext.
	 * @return string|WP_Error
	 */
	public static function encrypt( $plaintext ) {
		$key = self::key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plaintext, $nonce, $key );
			return 's1:' . base64_encode( $nonce . $cipher );
		}
		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv     = random_bytes( 12 );
			$tag    = '';
			$cipher = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false === $cipher ) {
				return new WP_Error( 'sun_crypto_failed', __( 'Private notification data could not be encrypted.', 'sabri-unified-notifications' ) );
			}
			return 'o1:' . base64_encode( $iv . $tag . $cipher );
		}
		return new WP_Error( 'sun_crypto_unavailable', __( 'A supported encryption extension is required.', 'sabri-unified-notifications' ) );
	}

	/**
	 * Decrypt private notification data.
	 *
	 * @param string $ciphertext Ciphertext.
	 * @return string|WP_Error
	 */
	public static function decrypt( $ciphertext ) {
		$key = self::key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		if ( str_starts_with( $ciphertext, 's1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$raw = base64_decode( substr( $ciphertext, 3 ), true );
			if ( false === $raw || strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
				return new WP_Error( 'sun_crypto_invalid', __( 'Private notification data is unavailable.', 'sabri-unified-notifications' ) );
			}
			$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$body  = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			try {
				$plain = sodium_crypto_secretbox_open( $body, $nonce, $key );
			} catch ( Throwable $exception ) {
				$plain = false;
			}
			return false === $plain ? new WP_Error( 'sun_crypto_invalid', __( 'Private notification data is unavailable.', 'sabri-unified-notifications' ) ) : $plain;
		}
		if ( str_starts_with( $ciphertext, 'o1:' ) && function_exists( 'openssl_decrypt' ) ) {
			$raw = base64_decode( substr( $ciphertext, 3 ), true );
			if ( false === $raw || strlen( $raw ) < 28 ) {
				return new WP_Error( 'sun_crypto_invalid', __( 'Private notification data is unavailable.', 'sabri-unified-notifications' ) );
			}
			$iv    = substr( $raw, 0, 12 );
			$tag   = substr( $raw, 12, 16 );
			$body  = substr( $raw, 28 );
			$plain = openssl_decrypt( $body, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			return false === $plain ? new WP_Error( 'sun_crypto_invalid', __( 'Private notification data is unavailable.', 'sabri-unified-notifications' ) ) : $plain;
		}
		return new WP_Error( 'sun_crypto_format', __( 'Private notification data is unavailable.', 'sabri-unified-notifications' ) );
	}

	/**
	 * Create a purpose-bound token.
	 *
	 * @param array<string,mixed> $claims Claims.
	 * @param int                 $ttl    Seconds.
	 * @return string
	 */
	public static function sign_token( array $claims, $ttl = 86400 ) {
		$claims['iat'] = time();
		$claims['exp'] = time() + max( 60, absint( $ttl ) );
		$payload       = self::base64url_encode( SUN_Database::canonical_json( $claims ) );
		$signature     = hash_hmac( 'sha256', $payload, self::signing_key(), true );
		return $payload . '.' . self::base64url_encode( $signature );
	}

	/**
	 * Verify a purpose-bound token.
	 *
	 * @param string $token Token.
	 * @param string $purpose Required purpose.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function verify_token( $token, $purpose ) {
		$parts = explode( '.', (string) $token );
		if ( 2 !== count( $parts ) ) {
			return new WP_Error( 'sun_token_invalid', __( 'This link is invalid or expired.', 'sabri-unified-notifications' ) );
		}
		$expected = self::base64url_encode( hash_hmac( 'sha256', $parts[0], self::signing_key(), true ) );
		if ( ! hash_equals( $expected, $parts[1] ) ) {
			return new WP_Error( 'sun_token_invalid', __( 'This link is invalid or expired.', 'sabri-unified-notifications' ) );
		}
		$decoded = self::base64url_decode( $parts[0] );
		$claims  = json_decode( $decoded, true );
		if ( ! is_array( $claims ) || empty( $claims['exp'] ) || time() > (int) $claims['exp'] || (string) ( $claims['purpose'] ?? '' ) !== $purpose ) {
			return new WP_Error( 'sun_token_expired', __( 'This link is invalid or expired.', 'sabri-unified-notifications' ) );
		}
		return $claims;
	}

	/** @return string|WP_Error */
	private static function key() {
		$material = defined( 'SUN_NOTIFICATION_DATA_KEY' ) ? SUN_NOTIFICATION_DATA_KEY : ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' );
		if ( strlen( (string) $material ) < 32 ) {
			return new WP_Error( 'sun_key_missing', __( 'Notification encryption is not configured.', 'sabri-unified-notifications' ) );
		}
		return hash( 'sha256', 'sun:data:' . $material, true );
	}

	/** @return string */
	private static function signing_key() {
		$material = defined( 'SUN_NOTIFICATION_SIGNING_KEY' ) ? SUN_NOTIFICATION_SIGNING_KEY : ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : wp_salt( 'auth' ) );
		return hash( 'sha256', 'sun:sign:' . $material, true );
	}

	/** @param string $data Data. @return string */
	private static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/** @param string $data Data. @return string */
	private static function base64url_decode( $data ) {
		$pad = strlen( $data ) % 4;
		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		$decoded = base64_decode( strtr( $data, '-_', '+/' ), true );
		return false === $decoded ? '' : $decoded;
	}
}
