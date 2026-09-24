<?php
/** Evidence-first legacy notification migration gate. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Legacy_Migration {
	const OPTION='sun_legacy_notification_migration';
	public static function register(){add_filter('sun_file19_legacy_migration_health',array(__CLASS__,'health'));}
	/** @return array<int,array<string,mixed>> */
	public static function sources(){
		$sources=apply_filters('sun_legacy_notification_sources',array());$out=array();
		foreach((array)$sources as$source){
			if(!is_array($source)){continue;}
			$key=substr(sanitize_key((string)($source['key']??'')),0,80);
			if(''===$key||!is_callable($source['inventory']??null)||!is_callable($source['migrate']??null)||!is_callable($source['rollback']??null)){continue;}
			$out[$key]=array('key'=>$key,'owner'=>substr(sanitize_key((string)($source['owner']??'')),0,80),'version'=>substr(sanitize_text_field((string)($source['version']??'')),0,32),'inventory'=>$source['inventory'],'migrate'=>$source['migrate'],'rollback'=>$source['rollback']);
		}
		return array_values($out);
	}
	/** @return array<string,mixed> */
	public static function dry_run(){
		$report=array('generated_at'=>SUN_Database::now(),'sources'=>array(),'ready'=>true);
		foreach(self::sources() as$source){
			$result=call_user_func($source['inventory']);
			if(is_wp_error($result)){$report['ready']=false;$report['sources'][$source['key']]=array('status'=>'error','code'=>$result->get_error_code());continue;}
			$report['sources'][$source['key']]=array('status'=>'inventoried','owner'=>$source['owner'],'version'=>$source['version'],'evidence_hash'=>hash('sha256',SUN_Database::canonical_json($result)),'inventory'=>$result);
		}
		if(empty($report['sources'])){$report['ready']=false;$report['blocking_reason']='legacy_source_contracts_unverified';}
		update_option(self::OPTION,$report,false);return$report;
	}
	/** @param string $key Source key. @param string $evidence_hash Dry-run hash. @return array<string,mixed>|WP_Error */
	public static function execute($key,$evidence_hash){
		$key=sanitize_key((string)$key);$state=get_option(self::OPTION,array());$sources=array();
		foreach(self::sources() as$source){$sources[$source['key']]=$source;}
		if(empty($sources[$key])||empty($state['ready'])||empty($state['sources'][$key]['evidence_hash'])||!hash_equals((string)$state['sources'][$key]['evidence_hash'],(string)$evidence_hash)){return new WP_Error('sun_legacy_migration_evidence_required',__('A current matching dry-run inventory is required before legacy migration.','sabri-unified-notifications'),array('status'=>409));}
		$result=call_user_func($sources[$key]['migrate'],$state['sources'][$key]['inventory'],$evidence_hash);
		if(is_wp_error($result)){return$result;}if(!is_array($result)||empty($result['receipt'])||empty($result['rollback_evidence'])){return new WP_Error('sun_legacy_migration_receipt_invalid',__('The legacy owner did not return reversible migration evidence.','sabri-unified-notifications'),array('status'=>500));}
		$state['executions'][$key]=array('receipt'=>$result['receipt'],'rollback_evidence'=>$result['rollback_evidence'],'executed_at'=>SUN_Database::now(),'evidence_hash'=>$evidence_hash);update_option(self::OPTION,$state,false);return$state['executions'][$key];
	}
	/** @param string $key Source key. @return array<string,mixed>|WP_Error */
	public static function rollback($key){
		$key=sanitize_key((string)$key);$state=get_option(self::OPTION,array());$sources=array();foreach(self::sources() as$source){$sources[$source['key']]=$source;}
		if(empty($sources[$key])||empty($state['executions'][$key])){return new WP_Error('sun_legacy_rollback_evidence_missing',__('No verified migration receipt is available for rollback.','sabri-unified-notifications'),array('status'=>404));}
		$execution=$state['executions'][$key];$result=call_user_func($sources[$key]['rollback'],$execution['receipt'],$execution['rollback_evidence']);if(is_wp_error($result)){return$result;}
		$state['executions'][$key]['rolled_back_at']=SUN_Database::now();$state['executions'][$key]['rollback_result']=$result;update_option(self::OPTION,$state,false);return$state['executions'][$key];
	}
	/** @return array<string,mixed> */
	public static function health(){
		$state=get_option(self::OPTION,array());$sources=self::sources();
		return array('ready'=>!empty($state['ready'])&&!empty($sources),'declared_sources'=>count($sources),'last_dry_run'=>$state['generated_at']??null,'blocking_reason'=>$state['blocking_reason']??(empty($sources)?'legacy_source_contracts_unverified':null),'rule'=>'no historical table or bell is migrated without an owner-declared reversible adapter and dry-run evidence');
	}
}
