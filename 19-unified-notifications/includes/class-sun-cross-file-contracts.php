<?php
/** Canonical cross-file contracts for File 19. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Cross_File_Contracts {
	/** @return void */
	public static function register() {
		if ( is_admin() ) { add_action( 'admin_init', array( __CLASS__, 'sync_file01_registry' ), 60 ); }
		add_filter( 'sun_file19_cross_file_health', array( __CLASS__, 'health' ) );
		add_filter( 'spcrc/file19_contract_state', array( __CLASS__, 'file24_assurance_state' ), 10, 2 );
	}

	/** @return array<string,mixed> */
	public static function manifest() {
		return array(
			'module_key' => 'file-19', 'owner_file' => '19', 'owner_name' => 'Sabri Unified Notifications and Alerts',
			'slug' => 'sabri-unified-notifications', 'namespace_prefix' => 'SUN_', 'software_version' => SUN_VERSION,
			'contract_version' => '3.0.1', 'state' => 'active',
			'required' => array(
				array( 'module_key'=>'file-00','minimum_version'=>'1.2.44','maximum_version'=>'','purpose'=>'Canonical recipient identity, eligibility and verified contact assertions.','fail_mode'=>'Protected notification access and privileged actions fail closed.' ),
				array( 'module_key'=>'file-20','minimum_version'=>'1.4.17','maximum_version'=>'','purpose'=>'Single global notification bell, center placement and shell Safe Mode.','fail_mode'=>'Shell notification placement is unavailable and external delivery containment remains fail closed.' ),
				array( 'module_key'=>'file-24','minimum_version'=>'0.99.0','maximum_version'=>'','purpose'=>'Cross-cutting security, privacy, provider and incident assurance.','fail_mode'=>'Assurance is degraded; high-risk external operations remain contained.' ),
			),
			'optional' => array(
				array( 'module_key'=>'file-02','minimum_version'=>'1.0.0','maximum_version'=>'','purpose'=>'Fresh passkey authentication assurance for governance actions.','fail_mode'=>'Governance actions requiring step-up are unavailable.' ),
				array( 'module_key'=>'file-25','minimum_version'=>'0.0.1','maximum_version'=>'','purpose'=>'Canonical visual tokens and public presentation components.','fail_mode'=>'File 19 uses scoped accessible fallback presentation only.' ),
				array( 'module_key'=>'file-26','minimum_version'=>'1.0.0','maximum_version'=>'','purpose'=>'Canonical saved-search ownership verification for watch rules.','fail_mode'=>'Saved-search watches cannot be created or changed.' ),
			),
			'capabilities' => array( 'notification_projection','single_bell','preferences','delivery_queue','digest','device_delivery','attention_os','dead_letter','privacy_lifecycle' ),
			'commands' => array( 'IngestNotificationEvent.v1','UpdateNotificationPreferences.v1','RegisterNotificationDevice.v1','RetryNotificationDelivery.v1' ),
			'queries' => array( 'ListNotifications.v1','GetUnreadCount.v1','GetNotificationHealth.v1' ),
			'events' => array( 'NotificationCreated.v1','NotificationRead.v1','NotificationDeliveryFailed.v1','NotificationPreferenceChanged.v1' ),
			'routes' => array( '/notifications/','/settings/notifications/','/notifications/unsubscribe/' ),
			'data_classes' => array( 'private_notification_projection','delivery_evidence','notification_preference','restricted_operational' ),
			'canonical_entities' => array( 'notification','notification_preference','notification_delivery' ),
			'writes' => array(), 'global_shell_owner' => false, 'application_shell_owner' => false,
			'health' => array( 'contract' => 'sun.health.v7', 'callback' => 'sun_notification_capability_contract' ),
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
		return false !== has_filter( 'sun_validate_saved_search_ownership' );
	}

	/** @return true|WP_Error */
	public static function saved_search_owned( $user_id, $owner, $search_id ) {
		$user_id=absint($user_id); $owner=sanitize_key((string)$owner); $search_id=substr(sanitize_text_field((string)$search_id),0,191);
		if($user_id<1||''===$search_id){return new WP_Error('sun_saved_search_invalid',__('A valid saved-search owner and identifier are required.','sabri-unified-notifications'),array('status'=>400));}
		if(false===has_filter('sun_validate_saved_search_ownership')){return new WP_Error('sun_saved_search_owner_unavailable',__('The canonical saved-search owner is unavailable.','sabri-unified-notifications'),array('status'=>503));}
		$external=apply_filters('sun_validate_saved_search_ownership',null,$user_id,$owner,$search_id);
		if(is_wp_error($external)){return $external;}
		if(true===$external){return true;}
		if(false===$external){return new WP_Error('sun_saved_search_not_owned',__('The saved search is not owned by this user.','sabri-unified-notifications'),array('status'=>403));}
		return new WP_Error('sun_saved_search_owner_unverified',__('The saved-search owner did not return a canonical ownership assertion.','sabri-unified-notifications'),array('status'=>503));
	}

	/** File 24 contract state for the canonical assurance matrix. */
	public static function file24_assurance_state( $current = 'unassessed', $definition = array() ) {
		unset( $current, $definition );
		if ( ! defined( 'SUN_VERSION' ) || ! class_exists( 'SUN_Notification_Service' ) ) { return 'missing'; }
		$db = (string) get_option( 'sun_db_version', '' );
		if ( ! defined( 'SUN_DB_VERSION' ) || '' === $db || SUN_DB_VERSION !== $db ) { return 'degraded'; }
		return 'compatible';
	}

	/** @param array<string,mixed> $health Health. @return array<string,mixed> */
	public static function health( $health=array() ) {
		$health=is_array($health)?$health:array();
		$health['file01_registry']=self::file01_registry_ready();
		$surface=false===has_filter('sun_file20_notification_surface_state')?null:apply_filters('sun_file20_notification_surface_state',null);
		$health['file20_single_bell']=is_array($surface)&&'file-20'===($surface['owner']??'')&&!empty($surface['detected'])&&!empty($surface['destination']);
		$health['file20_surface_contract']=is_array($surface)?($surface['contract']??''):'';
		$health['file26_saved_search_verifier']=self::saved_search_verifier_ready();
		$health['visual_owner']='file-25-css-variable-contract';
		return $health;
	}
}
