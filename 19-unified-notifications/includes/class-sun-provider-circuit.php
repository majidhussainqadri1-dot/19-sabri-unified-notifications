<?php
/**
 * Small provider-neutral circuit breaker for external notification channels.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Provider_Circuit {
	/** @param string $channel Channel. @return bool */
	public static function is_open( $channel ) {
		$state = self::state( $channel );
		if ( empty( $state['open_until'] ) ) {
			return false;
		}
		if ( (int) $state['open_until'] <= time() ) {
			self::reset( $channel );
			return false;
		}
		return true;
	}

	/** @param string $channel Channel. @return void */
	public static function record_failure( $channel ) {
		$channel   = sanitize_key( $channel );
		$threshold = max( 2, min( 20, (int) apply_filters( 'sun_provider_circuit_failure_threshold', 5, $channel ) ) );
		$window    = max( MINUTE_IN_SECONDS, (int) apply_filters( 'sun_provider_circuit_window_seconds', 300, $channel ) );
		$cooldown  = max( MINUTE_IN_SECONDS, (int) apply_filters( 'sun_provider_circuit_cooldown_seconds', 300, $channel ) );
		$state     = self::state( $channel );
		$now       = time();
		if ( empty( $state['window_started'] ) || $now - (int) $state['window_started'] > $window ) {
			$state = array( 'failures' => 0, 'window_started' => $now, 'open_until' => 0 );
		}
		$state['failures'] = (int) $state['failures'] + 1;
		if ( $state['failures'] >= $threshold ) {
			$state['open_until'] = $now + $cooldown;
		}
		set_transient( self::key( $channel ), $state, max( $window, $cooldown ) + MINUTE_IN_SECONDS );
	}

	/** @param string $channel Channel. @return void */
	public static function record_success( $channel ) {
		self::reset( $channel );
	}

	/** @return array<string,array<string,mixed>> */
	public static function health() {
		$out = array();
		foreach ( array( 'email', 'push', 'sms' ) as $channel ) {
			$state = self::state( $channel );
			$out[ $channel ] = array(
				'open'       => self::is_open( $channel ),
				'failures'   => (int) ( $state['failures'] ?? 0 ),
				'open_until' => (int) ( $state['open_until'] ?? 0 ),
			);
		}
		return $out;
	}

	/** @param string $channel Channel. @return array<string,int> */
	private static function state( $channel ) {
		$state = get_transient( self::key( $channel ) );
		return is_array( $state ) ? $state : array( 'failures' => 0, 'window_started' => 0, 'open_until' => 0 );
	}

	/** @param string $channel Channel. @return void */
	private static function reset( $channel ) {
		delete_transient( self::key( $channel ) );
	}

	/** @param string $channel Channel. @return string */
	private static function key( $channel ) {
		return 'sun_cb_' . sanitize_key( $channel );
	}
}
