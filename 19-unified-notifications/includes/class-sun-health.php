<?php
/** Privacy-safe health, observability and cross-file System Check evidence. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Health {
	private $delivery;
	public function __construct(SUN_Delivery_Service $delivery){$this->delivery=$delivery;}

	public function snapshot(){
		global $wpdb,$wp_version;
		$logical_tables=array('events','notifications','preferences','subscriptions','deliveries','templates','policies','devices','dead_letters','audit','bulk_jobs','attention_profiles','notification_states','notification_rules','device_profiles','provider_routes','experiments','trace_spans','watch_history','request_idempotency','webhook_receipts');
		$tables=array();
		foreach($logical_tables as $logical){$table=SUN_Database::table($logical);$tables[$logical]=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table;}
		$column_checks=array(
			'bulk_governance'=>$this->columns_present('bulk_jobs',array('reason','compensation_plan')),
			'provider_routing'=>$this->columns_present('provider_routes',array('regions_json','max_per_hour','health_state','cost_known')),
			'replay_safety'=>$this->columns_present('request_idempotency',array('scope_hash','response_hash','expires_at'))&&$this->columns_present('webhook_receipts',array('channel','signature_hash','received_at','expires_at')),
		);
		$queue=SUN_Database::table('deliveries');$dead=SUN_Database::table('dead_letters');
		$metrics=array('queued'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='queued'"),'failed'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='failed'"),'expired'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='expired'"),'dead_letter'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$dead} WHERE status='open'"),'oldest_queue_seconds'=>0,'attention_profiles'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_Database::table('attention_profiles')),'automation_rules'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_Database::table('notification_rules').' WHERE enabled=1'),'active_experiments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SUN_Database::table('experiments')." WHERE status='running'"));
		$oldest=$wpdb->get_var("SELECT MIN(created_at) FROM {$queue} WHERE status IN ('queued','failed')");if($oldest){$metrics['oldest_queue_seconds']=max(0,time()-strtotime($oldest.' UTC'));}
		try{$foundation=SUN_Cross_File_Contracts::foundation_status();}catch(Throwable $e){$foundation=array('available'=>false,'ready'=>false,'error_class'=>get_class($e));}
		try{$shell=SUN_Cross_File_Contracts::shell_status();}catch(Throwable $e){$shell=array('available'=>false,'single_bell'=>false,'error_class'=>get_class($e));}
		try{$visual=SUN_Cross_File_Contracts::visual_status();}catch(Throwable $e){$visual=array('ready'=>false,'error_class'=>get_class($e));}
		$migration=SUN_Migration_Audit::status();$gate=SUN_Operational_Gate::snapshot();$circuits=SUN_Provider_Circuit::health();
		$checks=array(
			'schema'=>!in_array(false,$tables,true),'schema_columns'=>!in_array(false,$column_checks,true),'db_version'=>SUN_DB_VERSION===get_option('sun_db_version',''),
			'cron_queue'=>(bool)wp_next_scheduled('sun_process_delivery_queue'),'cron_reconcile'=>(bool)wp_next_scheduled('sun_reconcile_notifications'),'cron_expire'=>(bool)wp_next_scheduled('sun_expire_notifications'),'cron_privacy_retention'=>(bool)wp_next_scheduled('sun_notification_privacy_retention'),
			'encryption'=>!is_wp_error(SUN_Crypto::encrypt('health-probe')),'runtime_compatible'=>version_compare((string)$wp_version,SUN_MIN_WP_VERSION,'>=')&&version_compare(PHP_VERSION,SUN_MIN_PHP_VERSION,'>='),
			'file00_contract'=>false!==has_filter('sabri_membership_claims_v2'),'file01_registry'=>!empty($foundation['ready']),'file20_single_bell'=>!empty($shell['available'])&&!empty($shell['single_bell']),'file25_visual_contract'=>!empty($visual['ready']),'file26_saved_search_verifier'=>SUN_Cross_File_Contracts::saved_search_verifier_ready(),'legacy_migration_evidence'=>!empty($migration['verified']),
			'queue_lag_ok'=>$metrics['oldest_queue_seconds']<(int)apply_filters('sun_queue_lag_alert_seconds',3600),'dead_letters_ok'=>$metrics['dead_letter']<(int)apply_filters('sun_dead_letter_alert_count',10),'provider_circuits'=>!array_filter($circuits,static function($state){return!empty($state['open']);}),'operational_gate'=>empty($gate['safe_mode_active']),
			'attention_os'=>(bool)class_exists('SUN_Attention_Service'),'automation'=>(bool)class_exists('SUN_Automation_Service'),'routing'=>(bool)class_exists('SUN_Routing_Service'),'experiments'=>(bool)class_exists('SUN_Experiments_Service'),'trace'=>(bool)class_exists('SUN_Trace_Service')
		);
		$status=in_array(false,$checks,true)?'degraded':'healthy';
		return array('contract'=>'sun.health.v7','status'=>$status,'plugin_version'=>SUN_VERSION,'db_version'=>get_option('sun_db_version',''),'php'=>PHP_VERSION,'wordpress'=>$wp_version,'minimums'=>array('php'=>SUN_MIN_PHP_VERSION,'wordpress'=>SUN_MIN_WP_VERSION),'checks'=>$checks,'tables'=>$tables,'column_checks'=>$column_checks,'metrics'=>$metrics,'adapters'=>$this->delivery->adapter_health(),'provider_circuits'=>$circuits,'operational_gate'=>$gate,'cross_file'=>array('file01'=>$foundation,'file20'=>$shell,'file25'=>$visual,'file26_saved_search_verifier'=>SUN_Cross_File_Contracts::saved_search_verifier_ready()),'migration'=>$migration,'four_plan_compliance'=>SUN_Four_Plan_Compliance::snapshot(),'last_reconciliation'=>get_option('sun_last_reconciliation',array()),'generated_at'=>SUN_Database::now());
	}
	public function sanitized_export(){$data=$this->snapshot();$data['site']=array('host_hash'=>hash('sha256',(string)wp_parse_url(home_url(),PHP_URL_HOST)));return$data;}
	private function columns_present($logical,array $required){
		global $wpdb;$table=SUN_Database::table($logical);
		if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table){return false;}
		$columns=(array)$wpdb->get_col('SHOW COLUMNS FROM '.esc_sql($table),0); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return !array_diff($required,$columns);
	}
}
