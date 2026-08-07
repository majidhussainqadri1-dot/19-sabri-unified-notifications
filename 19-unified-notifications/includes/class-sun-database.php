<?php
/**
 * Database helpers and canonical table names.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Database {
	/** @var string */
	private static $prefix = 'sun_';

	/**
	 * Return a canonical table name.
	 *
	 * @param string $logical Logical name.
	 * @return string
	 */
	public static function table( $logical ) {
		global $wpdb;
		$allowed = array(
			'events', 'notifications', 'preferences', 'subscriptions', 'deliveries', 'templates',
			'policies', 'devices', 'dead_letters', 'audit', 'bulk_jobs',
		);
		if ( ! in_array( $logical, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Unknown SUN table.' );
		}
		return $wpdb->prefix . self::$prefix . $logical;
	}

	/** @return string */
	public static function uuid() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}
		$data    = random_bytes( 16 );
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/** @return string */
	public static function now() {
		return function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );
	}

	/** @return void */
	public static function begin() {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @return void */
	public static function commit() {
		global $wpdb;
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @return void */
	public static function rollback() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @param mixed $value Value. @return string */
	public static function canonical_json( $value ) {
		$value = self::sort_recursive( $value );
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/** @param mixed $value Value. @return mixed */
	private static function sort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::sort_recursive( $item );
		}
		return $value;
	}
}
