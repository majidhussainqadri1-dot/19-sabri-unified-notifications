<?php
/**
 * Fail-closed contracts for File 01, File 20, File 25 and File 26.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Cross_File_Contracts {
	/** @return array<string,mixed> */
	public static function foundation_manifest() {
		return array(
			'module_key' => 'file-19',
			'owner_file' => '19',
			'owner_name' => 'Unified Notifications and Alerts',
			'slug' => 'sabri-unified-notifications',
			'namespace_prefix' => 'SUN_',
			'software_version' => SUN_VERSION,
			'contract_version' => '1.0.0',
			'state' => 'registered',
			'required' => array(
				array( 'module_key'=>'file-00','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'canonical identity and eligibility claims','fail_mode'=>'recipient and privileged actions fail closed without File 00 claims' ),
				array( 'module_key'=>'file-01','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'canonical module contract and route registry','fail_mode'=>'File 19 remains locally routable but registry readiness is degraded until synchronized' ),
				array( 'module_key'=>'file-20','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'single global bell and shell placement','fail_mode'=>'standalone notification routes remain available without creating duplicate global navigation' ),
				array( 'module_key'=>'file-24','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'security privacy compliance and assurance governance','fail_mode'=>'sensitive governance remains fail closed and operational readiness is degraded' ),
			),
			'optional' => array(
				array( 'module_key'=>'file-25','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'canonical visual tokens and accessible component styling','fail_mode'=>'File 19 uses accessible local fallback tokens while reporting the visual contract as unavailable' ),
				array( 'module_key'=>'file-26','minimum_version'=>'0.1.0','maximum_version'=>'','purpose'=>'saved-search ownership verification and watch identifiers','fail_mode'=>'saved-search rule creation fails closed without owner verification' ),
			),
			'capabilities' => array( 'single_notification_center','preferences','attention','delivery','digests','devices','retries','dead_letters','notification_rules','saved_search_watches','correction_notices','provider_routing','privacy_lifecycle' ),
			'commands' => array( 'IngestNotificationEvent.v1','MarkNotification.v1','UpdateNotificationPreferences.v1','RegisterNotificationDevice.v1','RetryNotificationDelivery.v1' ),
			'queries' => array( 'ListNotifications.v1','GetUnreadNotificationCount.v1','GetNotificationPreferences.v1','GetNotificationDeliveryHealth.v1' ),
			'events' => array( 'NotificationCreated.v1','NotificationRead.v1','NotificationDeliveryFailed.v1','NotificationPreferenceChanged.v1' ),
			'routes' => array( '/notifications/','/settings/notifications/','/notifications/unsubscribe/' ),
			'data_classes' => array( 'private_notification','restricted_delivery','preference','device_token','audit','operational' ),
			'canonical_entities' => array( 'notification','notification_preference','notification_delivery','notification_device','notification_rule' ),
			'writes' => array(),
			'global_shell_owner' => false,
			'application_shell_owner' => false,
			'health' => array( 'callback'=>'sun_notification_capability_contract','contract'=>'sun.health.v7' ),
		);
	}

	/** @return array<int,array<string,mixed>> */
	public static function foundation_routes() {
		return array(
			array( 'route_key'=>'file19-notifications','route_path'=>'/notifications/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/notifications/'),'redirects'=>array() ),
			array( 'route_key'=>'file19-notification-settings','route_path'=>'/settings/notifications/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/settings/notifications/'),'redirects'=>array() ),
			array( 'route_key'=>'file19-notification-unsubscribe','route_path'=>'/notifications/unsubscribe/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/notifications/unsubscribe/'),'redirects'=>array() ),
		);
	}

	/**
	 * Synchronize File 19's owner manifest and static route families into File 01.
	 * This mutates File 01 only through its public registry API and therefore
	 * inherits File 01 authorization, audit, transition and concurrency controls.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function sync_foundation_registry() {
		if ( ! class_exists( 'SPF_Registry' ) ) {
			return new WP_Error( 'sun_file01_registry_unavailable', __( 'File 01 registry is not available.', 'sabri-unified-notifications' ), array( 'status'=>503 ) );
		}
		$manifest = self::foundation_manifest();
		$existing = SPF_Registry::get_module( 'file-19' );
		if ( is_array( $existing ) && ! empty( $existing['state'] ) ) {
			$manifest['state'] = (string) $existing['state'];
		}
		$manifest_needs_sync = ! is_array( $existing )
			|| (string) ( $existing['software_version'] ?? '' ) !== SUN_VERSION
			|| (string) ( $existing['contract_version'] ?? '' ) !== (string) $manifest['contract_version']
			|| (array) ( $existing['routes'] ?? array() ) !== $manifest['routes'];
		if ( $manifest_needs_sync ) {
			$context = array( 'purpose'=>'file19_owner_registry_sync' );
			if ( is_array( $existing ) && isset( $existing['record_version'] ) ) { $context['expected_version'] = (int) $existing['record_version']; }
			$result = SPF_Registry::register_manifest( $manifest, $context );
			if ( is_wp_error( $result ) ) { return $result; }
		}

		$current_routes = SPF_Registry::list_routes();
		if ( is_wp_error( $current_routes ) ) { return $current_routes; }
		$by_key = array();
		foreach ( (array) $current_routes as $route ) {
			if ( is_array( $route ) && ! empty( $route['route_key'] ) ) { $by_key[ (string) $route['route_key'] ] = $route; }
		}
		$mapped = array();
		foreach ( self::foundation_routes() as $route ) {
			$current = $by_key[ $route['route_key'] ] ?? null;
			$matches = is_array( $current )
				&& (string) ( $current['route_path'] ?? '' ) === $route['route_path']
				&& (string) ( $current['owner_module'] ?? '' ) === 'file-19'
				&& (string) ( $current['layout_context'] ?? '' ) === $route['layout_context']
				&& (string) ( $current['status'] ?? '' ) === $route['status']
				&& (string) ( $current['destination'] ?? '' ) === $route['destination'];
			if ( $matches ) { $mapped[] = $route['route_key']; continue; }
			$context = array( 'purpose'=>'file19_route_registry_sync' );
			if ( is_array( $current ) && isset( $current['record_version'] ) ) { $context['expected_version'] = (int) $current['record_version']; }
			$result = SPF_Registry::map_route( $route, $context );
			if ( is_wp_error( $result ) ) { return $result; }
			$mapped[] = $route['route_key'];
		}
		$state = array( 'status'=>'synchronized','software_version'=>SUN_VERSION,'routes'=>$mapped,'checked_at'=>SUN_Database::now() );
		update_option( 'sun_file01_registry_state', $state, false );
		return $state;
	}

	/** @return array<string,mixed> */
	public static function foundation_status() {
		$status = array( 'available'=>false,'manifest_current'=>false,'routes_current'=>false,'ready'=>false );
		if ( ! class_exists( 'SPF_Registry' ) ) { return $status; }
		$status['available'] = true;
		$module = SPF_Registry::get_module( 'file-19' );
		$status['manifest_current'] = is_array( $module )
			&& (string) ( $module['software_version'] ?? '' ) === SUN_VERSION
			&& (string) ( $module['contract_version'] ?? '' ) === '1.0.0';
		$routes = SPF_Registry::list_routes();
		if ( is_wp_error( $routes ) ) { return $status; }
		$index = array();
		foreach ( (array) $routes as $route ) { if ( is_array( $route ) && isset( $route['route_key'] ) ) { $index[ $route['route_key'] ] = $route; } }
		$status['routes_current'] = true;
		foreach ( self::foundation_routes() as $expected ) {
			$actual = $index[ $expected['route_key'] ] ?? null;
			if ( ! is_array( $actual )
				|| (string) ( $actual['route_path'] ?? '' ) !== $expected['route_path']
				|| (string) ( $actual['owner_module'] ?? '' ) !== 'file-19'
				|| (string) ( $actual['status'] ?? '' ) !== 'active' ) {
				$status['routes_current'] = false; break;
			}
		}
		$status['ready'] = $status['manifest_current'] && $status['routes_current'];
		return $status;
	}

	/** @return array<string,mixed> */
	public static function shell_status() {
		$contract = apply_filters( 'sun_file20_notification_contract', array() );
		$file19 = is_array( $contract ) ? ( $contract['file19'] ?? array() ) : array();
		return array(
			'available' => defined( 'SABRI_SHELL_VERSION' ) || has_action( 'sun_file20_notification_slot' ),
			'single_bell' => is_array( $file19 ) && ! empty( $file19['single_bell'] ),
			'slot' => is_array( $file19 ) ? (string) ( $file19['slot'] ?? '' ) : '',
		);
	}

	/**
	 * File 25 remains the visual owner. File 19 merely consumes a versioned
	 * attestation and CSS-variable namespace; no visual-truth claim is fabricated.
	 *
	 * @return array<string,mixed>
	 */
	public static function visual_status() {
		$contract = apply_filters( 'sabri_file25_notification_visual_contract', null, array(
			'consumer'=>'file-19',
			'required_css_variables'=>array(
				'--sabri-file25-color-primary','--sabri-file25-color-primary-strong','--sabri-file25-color-surface',
				'--sabri-file25-color-border','--sabri-file25-color-text','--sabri-file25-color-muted','--sabri-file25-color-danger',
				'--sabri-file25-radius-card','--sabri-file25-shadow-card',
			),
		) );
		$ready = is_array( $contract )
			&& true === ( $contract['ready'] ?? false )
			&& 'file-25' === sanitize_key( (string) ( $contract['owner'] ?? '' ) )
			&& preg_match( '/^[0-9]+.[0-9]+.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/', (string) ( $contract['version'] ?? '' ) );
		return array( 'ready'=>(bool)$ready,'contract'=>is_array($contract)?array('owner'=>$contract['owner']??'','version'=>$contract['version']??''):array() );
	}

	/**
	 * Strict owner attestation for File 26 saved-search IDs.
	 *
	 * @return true|WP_Error
	 */
	public static function authorize_saved_search( $user_id, $owner, $search_id ) {
		$user_id = absint( $user_id ); $owner = substr( sanitize_key( (string) $owner ), 0, 100 ); $search_id = substr( sanitize_text_field( (string) $search_id ), 0, 191 );
		if ( $user_id < 1 || '' === $owner || '' === $search_id ) {
			return new WP_Error( 'sun_saved_search_invalid', __( 'A valid saved-search owner and identifier are required.', 'sabri-unified-notifications' ), array( 'status'=>400 ) );
		}
		$evidence = apply_filters( 'sun_file26_saved_search_authorized', null, $user_id, $owner, $search_id );
		$allowed = true === $evidence;
		if ( is_array( $evidence ) ) {
			$allowed = true === ( $evidence['authorized'] ?? false )
				&& absint( $evidence['user_id'] ?? 0 ) === $user_id
				&& hash_equals( $owner, sanitize_key( (string) ( $evidence['owner'] ?? '' ) ) )
				&& hash_equals( $search_id, sanitize_text_field( (string) ( $evidence['search_id'] ?? '' ) ) );
		}
		return $allowed ? true : new WP_Error( 'sun_saved_search_owner_unverified', __( 'The saved search could not be verified with its canonical owner.', 'sabri-unified-notifications' ), array( 'status'=>503 ) );
	}

	/** @return bool */
	public static function saved_search_verifier_ready() { return false !== has_filter( 'sun_file26_saved_search_authorized' ); }
}
