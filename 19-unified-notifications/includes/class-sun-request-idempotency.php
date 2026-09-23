<?php
/**
 * Durable, replay-safe idempotency for authenticated File 19 REST mutations.
 *
 * Explicit Idempotency-Key headers are retained for 24 hours. Legacy callers
 * receive a short implicit fingerprint window so retries after a lost response
 * remain safe without breaking existing clients.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Request_Idempotency {
	/** @var array<string,array<string,mixed>> */
	private static $contexts = array();

	/** @return void */
	public static function register() {
		add_filter( 'rest_dispatch_request', array( __CLASS__, 'pre_dispatch' ), 10, 4 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'post_dispatch' ), 10, 3 );
	}

	/** @param mixed $result Existing dispatch result. @param WP_REST_Request $request Request. @param string $route Matched route. @param array<string,mixed> $handler Handler. @return mixed */
	public static function pre_dispatch( $result, $request, $route, $handler ) {
		unset( $handler );
		if ( null !== $result || ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) { return $result; }
		$route = (string) $route; $method = strtoupper( (string) $request->get_method() );
		if ( 0 !== strpos( $route, '/' . SUN_REST_NAMESPACE . '/' ) || in_array( $method, array( 'GET','HEAD','OPTIONS' ), true ) ) { return $result; }
		if ( preg_match( '#/' . preg_quote( SUN_REST_NAMESPACE, '#' ) . '/(?:events|provider/)#', $route ) ) { return $result; }
		$user_id = get_current_user_id(); if ( $user_id < 1 ) { return $result; }

		$raw_body = (string) $request->get_body(); $params = method_exists( $request, 'get_params' ) ? (array) $request->get_params() : array();
		$request_hash = hash( 'sha256', $method . "\n" . $route . "\n" . $raw_body . "\n" . SUN_Database::canonical_json( $params ) );
		$explicit = trim( (string) $request->get_header( 'idempotency-key' ) );
		if ( strlen( $explicit ) > 191 ) { return new WP_Error( 'sun_idempotency_key_too_long', __( 'The idempotency key is too long.', 'sabri-unified-notifications' ), array( 'status' => 400 ) ); }
		$key_material = '' !== $explicit ? $explicit : 'implicit:' . $request_hash;
		$key_hash = hash( 'sha256', $key_material );
		$scope_hash = hash( 'sha256', $user_id . '|' . $method . '|' . $route . '|' . $key_hash );
		$table = SUN_Database::table( 'request_idempotency' ); global $wpdb; $now = SUN_Database::now();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE scope_hash=%s LIMIT 1", $scope_hash ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $row && (string) $row['expires_at'] <= $now ) { $wpdb->delete( $table, array( 'id' => (int) $row['id'] ) ); $row = null; }
		if ( $row ) {
			if ( ! hash_equals( (string) $row['request_hash'], $request_hash ) ) { return new WP_Error( 'sun_idempotency_conflict', __( 'This idempotency key was already used for a different request.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
			if ( 'completed' === $row['status'] && ! empty( $row['response_ciphertext'] ) ) {
				$plain = SUN_Crypto::decrypt( (string) $row['response_ciphertext'] );
				if ( ! is_wp_error( $plain ) ) {
					$data = json_decode( $plain, true ); if ( JSON_ERROR_NONE !== json_last_error() ) { $data = array(); }
					$response = new WP_REST_Response( $data, max( 200, (int) $row['response_code'] ) ); $response->header( 'X-SUN-Idempotent-Replay', '1' ); return $response;
				}
			}
			$stale_before = gmdate( 'Y-m-d H:i:s', time() - 2 * MINUTE_IN_SECONDS );
			if ( 'processing' === $row['status'] && (string) $row['updated_at'] > $stale_before ) { return new WP_Error( 'sun_idempotency_in_progress', __( 'An identical request is already being processed.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
			$claimed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='processing',request_hash=%s,updated_at=%s WHERE id=%d AND updated_at<=%s", $request_hash, $now, (int) $row['id'], $stale_before ) );
			if ( 1 !== (int) $claimed ) { return new WP_Error( 'sun_idempotency_in_progress', __( 'An identical request is already being processed.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
		} else {
			$ttl = '' !== $explicit ? DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS; $expires = gmdate( 'Y-m-d H:i:s', time() + $ttl );
			$inserted = $wpdb->insert( $table, array( 'scope_hash'=>$scope_hash,'user_id'=>$user_id,'route'=>substr($route,0,191),'key_hash'=>$key_hash,'request_hash'=>$request_hash,'status'=>'processing','expires_at'=>$expires,'created_at'=>$now,'updated_at'=>$now ) );
			if ( false === $inserted ) { return new WP_Error( 'sun_idempotency_race', __( 'An identical request started concurrently. Retry shortly.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
		}
		self::$contexts[ spl_object_hash( $request ) ] = array( 'scope_hash' => $scope_hash );
		return $result;
	}

	/** @param mixed $response Response. @param WP_REST_Server $server Server. @param WP_REST_Request $request Request. @return mixed */
	public static function post_dispatch( $response, $server, $request ) {
		unset( $server ); $key = is_object( $request ) ? spl_object_hash( $request ) : ''; if ( ! isset( self::$contexts[ $key ] ) ) { return $response; }
		$context = self::$contexts[ $key ]; unset( self::$contexts[ $key ] ); global $wpdb; $table = SUN_Database::table( 'request_idempotency' );
		if ( is_wp_error( $response ) ) { $wpdb->delete( $table, array( 'scope_hash' => $context['scope_hash'] ) ); return $response; }
		$status = is_object( $response ) && method_exists( $response, 'get_status' ) ? (int) $response->get_status() : 200;
		if ( $status < 200 || $status >= 400 ) { $wpdb->delete( $table, array( 'scope_hash' => $context['scope_hash'] ) ); return $response; }
		$data = is_object( $response ) && method_exists( $response, 'get_data' ) ? $response->get_data() : $response;
		$cipher = SUN_Crypto::encrypt( SUN_Database::canonical_json( $data ) ); if ( is_wp_error( $cipher ) ) { $wpdb->delete( $table, array( 'scope_hash' => $context['scope_hash'] ) ); return $response; }
		$wpdb->update( $table, array( 'status'=>'completed','response_ciphertext'=>$cipher,'response_code'=>$status,'updated_at'=>SUN_Database::now() ), array( 'scope_hash'=>$context['scope_hash'],'status'=>'processing' ) );
		return $response;
	}
}
