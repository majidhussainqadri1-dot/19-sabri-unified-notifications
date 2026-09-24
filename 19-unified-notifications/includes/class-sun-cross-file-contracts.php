<?php
/** Canonical cross-file contracts for File 19. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Cross_File_Contracts {
	const FILE26_SAVED_META = 'sabri_file26_saved_queries_v1';

	/** @return void */
	public static function register() {
		if ( is_admin() ) { add_action( 'admin_init', array( __CLASS__, 'sync_file01_registry' ), 60 ); }
		add_filter( 'sun_file19_cross_file_health', array( __CLASS__, 'health' ) );
	}

	/** @return array<string,mixed> */
	public static function manifest() {
		return array(
			'module_key' => 'file-19', 'owner_file' => '19', 'owner_name' => 'Sabri Unified Notifications and Alerts',
			'slug' => 'sabri-unified-notifications', 'namespace_prefix' => 'SUN_', 'software_version' => SUN_VERSION,
			'contract_version' => '3.0.0', 'state' => 'active',
			'required' => array(), 'optional' => array(),
			'capabilities' => array( 'notification_projection','single_bell','preferences','delivery_queue','digest','device_delivery','attention_os','dead_letter','privacy_lifecycle' ),
			'commands' => array( 'IngestNotificationEvent.v1','UpdateNotificationPreferences.v1','RegisterNotificationDevice.v1','RetryNotificationDelivery.v1' ),
			'queries' => array( 'ListNotifications.v1','GetUnreadCount.v1','GetNotificationHealth.v1' ),
			'events' => array( 'NotificationCreated.v1','NotificationRead.v1','NotificationDeliveryFailed.v1','NotificationPreferenceChanged.v1' ),
			'routes' => array( '/notifications/','/settings/notifications/' ),
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
		);
	}

	/** Attempt authorized File 01 registration; never bypass File 01 governance. */
	public static function sync_file01_registry() {
		if ( ! class_exists( 'SPF_Registry' ) || ! is_user_logged_in() || ! current_user_can( 'manage_sabri_notifications' ) ) { return; }
		$status = array( 'version'=>SUN_VERSION, 'attempted_at'=>SUN_Database::now(), 'manifest'=>false, 'routes'=>array() );
		$existing = SPF_Registry::get_module( 'file-19' );
		$context = array( 'purpose'=>'file19_cross_file_registry_sync' );
		if ( is_array( $existing ) && isset( $existing['record_version'] ) ) { $context['expected_version'] = (int) $existing['record_version']; }
		$result = SPF_Registry::register_manifest( self::manifest(), $context );
		if ( is_wp_error( $result ) ) { $status['error']=$result->get_error_code(); update_option( 'sun_file01_registry_sync', $status, false ); return; }
		$status['manifest'] = true;
		$existing_routes = SPF_Registry::list_routes(); $by_key = array();
		foreach ( (array) $existing_routes as $row ) { $by_key[ $row['route_key'] ] = $row; }
		foreach ( self::routes() as $route ) {
			$ctx = array( 'purpose'=>'file19_cross_file_route_sync' );
			if ( isset( $by_key[ $route['route_key'] ]['record_version'] ) ) { $ctx['expected_version'] = (int) $by_key[ $route['route_key'] ]['record_version']; }
			$mapped = SPF_Registry::map_route( $route, $ctx );
			$status['routes'][ $route['route_key'] ] = is_wp_error( $mapped ) ? $mapped->get_error_code() : 'ok';
		}
		update_option( 'sun_file01_registry_sync', $status, false );
	}

	/** @return bool */
	public static function file01_registry_ready() {
		if ( ! class_exists( 'SPF_Registry' ) ) { return false; }
		$module = SPF_Registry::get_module( 'file-19' );
		if ( ! is_array( $module ) || 'active' !== ( $module['state'] ?? '' ) ) { return false; }
		$needed = array( 'file19-notifications'=>false, 'file19-notification-settings'=>false );
		foreach ( (array) SPF_Registry::list_routes() as $route ) {
			if ( isset( $needed[ $route['route_key'] ] ) && 'file-19' === ( $route['owner_module'] ?? '' ) && 'active' === ( $route['status'] ?? '' ) ) { $needed[ $route['route_key'] ] = true; }
		}
		return ! in_array( false, $needed, true );
	}

	/** @return bool */
	public static function saved_search_verifier_ready() {
		return class_exists( 'Sabri\\File26\\Central_Plan' ) || false !== has_filter( 'sun_validate_saved_search_ownership' );
	}

	/** @return true|WP_Error */
	public static function saved_search_owned( $user_id, $owner, $search_id ) {
		$user_id=absint($user_id); $owner=sanitize_key((string)$owner); $search_id=substr(sanitize_text_field((string)$search_id),0,191);
		if($user_id<1||''===$search_id){return new WP_Error('sun_saved_search_invalid',__('A valid saved-search owner and identifier are required.','sabri-unified-notifications'),array('status'=>400));}
		$external=apply_filters('sun_validate_saved_search_ownership',null,$user_id,$owner,$search_id);
		if(is_wp_error($external)){return $external;}
		if(is_bool($external)){return $external?true:new WP_Error('sun_saved_search_not_owned',__('The saved search is not owned by this user.','sabri-unified-notifications'),array('status'=>403));}
		if(!in_array($owner,array('file26','file-26','search','sabri-file26'),true)){return new WP_Error('sun_saved_search_owner_unverified',__('The saved-search owner cannot be verified.','sabri-unified-notifications'),array('status'=>503));}
		$records=get_user_meta($user_id,self::FILE26_SAVED_META,true);
		if(!is_array($records)||!isset($records[$search_id])||!is_array($records[$search_id])){return new WP_Error('sun_saved_search_not_owned',__('The saved search is not owned by this user.','sabri-unified-notifications'),array('status'=>403));}
		$expires=(string)($records[$search_id]['expires_at']??'');
		if($expires&&strtotime($expires.' UTC')<=time()){return new WP_Error('sun_saved_search_expired',__('The saved search has expired.','sabri-unified-notifications'),array('status'=>410));}
		return true;
	}

	/** @param array<string,mixed> $health Health. @return array<string,mixed> */
	public static function health( $health=array() ) {
		$health=is_array($health)?$health:array();
		$health['file01_registry']=self::file01_registry_ready();
		$health['file20_single_bell']=class_exists('Sabri\\UnifiedShell\\Plugin')&&(bool)has_action('sun_file20_notification_slot');
		$health['file26_saved_search_verifier']=self::saved_search_verifier_ready();
		$health['visual_owner']='file-25-css-variable-contract';
		return $health;
	}
}
