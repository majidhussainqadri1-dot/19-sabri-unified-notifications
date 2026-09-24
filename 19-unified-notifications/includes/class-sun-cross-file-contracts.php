<?php
/** Canonical cross-file contracts for File 19. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Cross_File_Contracts {
	const FILE26_SAVED_META = 'sabri_file26_saved_queries_v1';

	/** @return void */
	public static function register() {
		/* Registration remains authorization-bound, but is no longer limited to wp-admin. */
		add_action( 'init', array( __CLASS__, 'sync_file01_registry' ), 60 );
		add_filter( 'sun_file19_cross_file_health', array( __CLASS__, 'health' ) );
		add_filter( 'spcrc/file19_contract_state', array( __CLASS__, 'file24_contract_state' ), 10, 2 );
	}

	/** @return array<string,mixed> */
	public static function manifest() {
		return array(
			'module_key' => 'file-19', 'owner_file' => '19', 'owner_name' => 'Sabri Unified Notifications and Alerts',
			'slug' => 'sabri-unified-notifications', 'namespace_prefix' => 'SUN_', 'software_version' => SUN_VERSION,
			'contract_version' => '3.0.1', 'state' => 'active',
			'required' => array( 'file-00', 'file-20' ),
			'optional' => array( 'file-02', 'file-24', 'file-25', 'file-26' ),
			'capabilities' => array( 'notification_projection','single_bell','preferences','delivery_queue','digest','device_delivery','attention_os','dead_letter','privacy_lifecycle' ),
			'commands' => array( 'IngestNotificationEvent.v1','UpdateNotificationPreferences.v1','RegisterNotificationDevice.v1','RetryNotificationDelivery.v1' ),
			'queries' => array( 'ListNotifications.v1','GetUnreadCount.v1','GetNotificationHealth.v1' ),
			'events' => array( 'NotificationCreated.v1','NotificationRead.v1','NotificationDeliveryFailed.v1','NotificationPreferenceChanged.v1' ),
			'routes' => array( '/notifications/','/settings/notifications/','/notifications/unsubscribe/' ),
			'data_classes' => array( 'private_notification_projection','delivery_evidence','notification_preference','restricted_operational' ),
			'canonical_entities' => array( 'notification','notification_preference','notification_delivery' ),
			'writes' => array(), 'global_shell_owner' => false, 'application_shell_owner' => false,
			'health' => array( 'contract' => 'sun.health.v6', 'callback' => 'sun_notification_capability_contract' ),
		);
	}

	/** @return array<int,array<string,mixed>> */
	public static function routes() {
		return array(
			array( 'route_key'=>'file19-notifications','route_path'=>'/notifications/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/notifications/'),'redirects'=>array() ),
			array( 'route_key'=>'file19-notification-settings','route_path'=>'/settings/notifications/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/settings/notifications/'),'redirects'=>array() ),
			array( 'route_key'=>'file19-notification-unsubscribe','route_path'=>'/notifications/unsubscribe/','owner_module'=>'file-19','layout_context'=>'minimal','status'=>'active','destination'=>home_url('/notifications/unsubscribe/'),'redirects'=>array() ),
		);
	}

	/** Attempt authorized File 01 registration; never bypass File 01 governance. */
	public static function sync_file01_registry() {
		if ( ! class_exists( 'SPF_Registry' ) || ! is_user_logged_in() || ! current_user_can( 'manage_sabri_notifications' ) ) { return; }
		$status = array( 'version'=>SUN_VERSION, 'attempted_at'=>SUN_Database::now(), 'manifest'=>false, 'routes'=>array() );
		$existing = SPF_Registry::get_module( 'file-19' );
		$context = array( 'purpose'=>'file19_cross_file_registry_sync' );
		if ( is_array( $existing ) && isset( $existing['record_version'] ) ) { $context['expected_version'] = (int) $existing['record_version']; }
		$manifest=self::manifest();$manifest_current=is_array($existing)&&($existing['software_version']??'')===SUN_VERSION&&($existing['contract_version']??'')===$manifest['contract_version']&&($existing['state']??'')==='active'&&array_values((array)($existing['routes']??array()))===array_values($manifest['routes']);
		$result=$manifest_current?array('unchanged'=>true):SPF_Registry::register_manifest($manifest,$context);
		if ( is_wp_error( $result ) ) { $status['error']=$result->get_error_code(); update_option( 'sun_file01_registry_sync', $status, false ); return; }
		$status['manifest'] = true;
		$existing_routes = SPF_Registry::list_routes(); $by_key = array();
		foreach ( (array) $existing_routes as $row ) { $by_key[ $row['route_key'] ] = $row; }
		foreach ( self::routes() as $route ) {
			$ctx = array( 'purpose'=>'file19_cross_file_route_sync' );
			if ( isset( $by_key[ $route['route_key'] ]['record_version'] ) ) { $ctx['expected_version'] = (int) $by_key[ $route['route_key'] ]['record_version']; }
			$current=$by_key[$route['route_key']]??null;$route_current=is_array($current)&&($current['route_path']??'')===$route['route_path']&&($current['owner_module']??'')===$route['owner_module']&&($current['layout_context']??'')===$route['layout_context']&&($current['status']??'')===$route['status']&&($current['destination']??'')===$route['destination'];$mapped=$route_current?array('unchanged'=>true):SPF_Registry::map_route($route,$ctx);
			$status['routes'][ $route['route_key'] ] = is_wp_error( $mapped ) ? $mapped->get_error_code() : ( $route_current ? 'unchanged' : 'ok' );
		}
		update_option( 'sun_file01_registry_sync', $status, false );
	}

	/** @return bool */
	public static function file01_registry_ready() {
		if ( ! class_exists( 'SPF_Registry' ) ) { return false; }
		$module = SPF_Registry::get_module( 'file-19' );
		if ( ! is_array( $module ) || 'active' !== ( $module['state'] ?? '' ) ) { return false; }
		$needed = array( 'file19-notifications'=>false, 'file19-notification-settings'=>false, 'file19-notification-unsubscribe'=>false );
		foreach ( (array) SPF_Registry::list_routes() as $route ) {
			if ( isset( $needed[ $route['route_key'] ] ) && 'file-19' === ( $route['owner_module'] ?? '' ) && 'active' === ( $route['status'] ?? '' ) ) { $needed[ $route['route_key'] ] = true; }
		}
		return ! in_array( false, $needed, true );
	}

	/** @return bool */
	public static function saved_search_verifier_ready() {
		/* File 26 must publish an explicit ownership verifier; class presence alone is not authority. */
		return false !== has_filter( 'sun_validate_saved_search_ownership' );
	}

	/** @return true|WP_Error */
	public static function saved_search_owned( $user_id, $owner, $search_id ) {
		$user_id=absint($user_id); $owner=sanitize_key((string)$owner); $search_id=substr(sanitize_text_field((string)$search_id),0,191);
		if($user_id<1||''===$search_id){return new WP_Error('sun_saved_search_invalid',__('A valid saved-search owner and identifier are required.','sabri-unified-notifications'),array('status'=>400));}
		if(false===has_filter('sun_validate_saved_search_ownership')){return new WP_Error('sun_saved_search_owner_unavailable',__('The canonical saved-search ownership verifier is unavailable.','sabri-unified-notifications'),array('status'=>503));}
		$external=apply_filters('sun_validate_saved_search_ownership',null,$user_id,$owner,$search_id);
		if(is_wp_error($external)){return $external;}
		if(true===$external){return true;}
		if(false===$external){return new WP_Error('sun_saved_search_not_owned',__('The saved search is not owned by this user.','sabri-unified-notifications'),array('status'=>403));}
		return new WP_Error('sun_saved_search_owner_unverified',__('The canonical saved-search owner returned no authoritative ownership result.','sabri-unified-notifications'),array('status'=>503));
	}

	/** @param array<string,mixed> $health Health. @return array<string,mixed> */
	/** @return bool */
	public static function file20_single_bell_ready() {
		if ( ! class_exists( 'Sabri\\UnifiedShell\\Integrations' ) || ! is_callable( array( 'Sabri\\UnifiedShell\\Integrations', 'detect' ) ) ) {
			return false;
		}
		try {
			$detected = \Sabri\UnifiedShell\Integrations::detect();
		} catch ( Throwable $error ) {
			unset( $error );
			return false;
		}
		return is_array( $detected ) && ! empty( $detected['notifications'] ) && shortcode_exists( 'sabri_notification_bell' );
	}

	/** @return bool */
	public static function file25_visual_contract_ready() {
		return false !== has_filter( 'sun_file25_notification_visual_contract' )
			|| false !== has_filter( 'sabri_file25_notification_visual_contract' );
	}

	/**
	 * File 24 integration matrix consumes a bounded compatibility state.
	 * This is runtime contract compatibility only; it is not staging/live assurance evidence.
	 *
	 * @param mixed $state Existing state.
	 * @param mixed $definition File 24 definition.
	 * @return string
	 */
	public static function file24_contract_state( $state='unassessed', $definition=array() ) {
		unset( $state, $definition );
		$native = class_exists( 'SUN_Event_Validator' )
			&& class_exists( 'SUN_Provider_Webhook_Verifier' )
			&& class_exists( 'SUN_Deep_Link' )
			&& class_exists( 'SUN_Crypto' );
		if ( ! $native ) { return 'blocked'; }
		return ! empty( SUN_Operational_Gate::snapshot()['safe_mode_active'] ) ? 'degraded' : 'compatible';
	}

	public static function health( $health=array() ) {
		$health=is_array($health)?$health:array();
		$health['file01_registry']=self::file01_registry_ready();
		$health['file20_single_bell']=self::file20_single_bell_ready();
		$health['file24_assurance_contract']=false!==has_filter('spcrc/file19_contract_state');
		$health['file25_visual_contract']=self::file25_visual_contract_ready();
		$health['file26_saved_search_verifier']=self::saved_search_verifier_ready();
		return $health;
	}
}
