<?php
/**
 * Fail-closed legacy-notification migration inventory and adapter governance.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Migration_Audit {
	/** @return array<string,array<string,mixed>> */
	public static function adapters() {
		$raw = apply_filters( 'sun_legacy_notification_migration_adapters', array() );
		$out = array();
		foreach ( (array) $raw as $key=>$adapter ) {
			if ( ! is_array( $adapter ) ) { continue; }
			$key = sanitize_key( is_string($key) ? $key : (string)($adapter['key']??'') );
			if ( '' === $key || empty( $adapter['owner'] ) || empty( $adapter['contract_version'] ) ) { continue; }
			$callbacks_ok = true;
			foreach ( array( 'inventory','dry_run','apply','rollback','disable_legacy_send' ) as $callback ) {
				if ( empty( $adapter[$callback] ) || ! is_callable( $adapter[$callback] ) ) { $callbacks_ok=false; break; }
			}
			if ( ! $callbacks_ok ) { continue; }
			$out[$key]=array(
				'key'=>$key,
				'owner'=>substr(sanitize_text_field((string)$adapter['owner']),0,100),
				'contract_version'=>substr(sanitize_text_field((string)$adapter['contract_version']),0,32),
				'inventory'=>$adapter['inventory'],'dry_run'=>$adapter['dry_run'],'apply'=>$adapter['apply'],'rollback'=>$adapter['rollback'],'disable_legacy_send'=>$adapter['disable_legacy_send'],
			);
		}
		return $out;
	}

	/** @return array<string,mixed> */
	public static function inventory() {
		$items=array();$complete=true;
		foreach(self::adapters() as $key=>$adapter){
			try{$result=call_user_func($adapter['inventory']);}
			catch(Throwable $e){$result=new WP_Error('sun_legacy_inventory_exception',__('A legacy notification inventory adapter failed safely.','sabri-unified-notifications'));}
			if(is_wp_error($result)){$complete=false;$items[$key]=array('owner'=>$adapter['owner'],'status'=>'error','error'=>$result->get_error_code());continue;}
			$result=is_array($result)?$result:array();
			$items[$key]=array(
				'owner'=>$adapter['owner'],'contract_version'=>$adapter['contract_version'],'status'=>'inventoried',
				'bells'=>max(0,(int)($result['bells']??0)),'tables'=>max(0,(int)($result['tables']??0)),'templates'=>max(0,(int)($result['templates']??0)),
				'preferences'=>max(0,(int)($result['preferences']??0)),'devices'=>max(0,(int)($result['devices']??0)),'producers'=>max(0,(int)($result['producers']??0)),
				'provider_tokens'=>max(0,(int)($result['provider_tokens']??0)),'counters'=>max(0,(int)($result['counters']??0)),
			);
		}
		return array('framework'=>'sun.legacy-migration.v1','complete'=>$complete,'adapters'=>$items,'adapter_count'=>count($items),'generated_at'=>SUN_Database::now());
	}

	/**
	 * Dry-run only. Applying or rolling back remains an explicit owner/admin action.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function dry_run_all( SUN_Auth $auth ) {
		if(!$auth->can_manage()){return new WP_Error('sun_legacy_migration_forbidden',__('Notification migration review requires current management authority.','sabri-unified-notifications'),array('status'=>403));}
		$reports=array();
		foreach(self::adapters() as $key=>$adapter){
			try{$report=call_user_func($adapter['dry_run']);}
			catch(Throwable $e){$report=new WP_Error('sun_legacy_dry_run_exception',__('A legacy notification dry-run failed safely.','sabri-unified-notifications'));}
			if(is_wp_error($report)){return $report;}
			$reports[$key]=is_array($report)?$report:array('status'=>'invalid-report');
		}
		$state=array('status'=>'dry-run-complete','reports'=>$reports,'adapter_count'=>count($reports),'checked_at'=>SUN_Database::now());
		update_option('sun_legacy_migration_state',$state,false);
		SUN_Audit::record('legacy_notification_migration_dry_run','migration','file-19',array('adapter_count'=>count($reports),'purpose'=>'migration_rehearsal'),get_current_user_id());
		return $state;
	}

	/** @return array<string,mixed> */
	public static function status() {
		$state=get_option('sun_legacy_migration_state',array('status'=>'unverified'));
		$adapters=self::adapters();
		$verified=in_array((string)($state['status']??''),array('verified','not-required'),true);
		return array(
			'framework_ready'=>true,
			'adapter_count'=>count($adapters),
			'evidence_status'=>sanitize_key((string)($state['status']??'unverified')),
			'verified'=>$verified,
			'note'=>$verified?'Legacy migration evidence is recorded.':'Historical legacy state remains unverified; no destructive cutover is inferred.',
		);
	}
}
