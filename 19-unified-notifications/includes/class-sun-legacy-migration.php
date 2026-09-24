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
	/** @return array<string,mixed> */
	public static function health(){
		$state=get_option(self::OPTION,array());$sources=self::sources();
		return array('ready'=>!empty($state['ready'])&&!empty($sources),'declared_sources'=>count($sources),'last_dry_run'=>$state['generated_at']??null,'blocking_reason'=>$state['blocking_reason']??(empty($sources)?'legacy_source_contracts_unverified':null),'rule'=>'no historical table or bell is migrated without an owner-declared reversible adapter and dry-run evidence');
	}
}
