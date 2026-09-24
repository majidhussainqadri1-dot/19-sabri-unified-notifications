<?php
/** Privacy-safe health, observability and System Check evidence. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Health {
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @param SUN_Delivery_Service $delivery Delivery. */
	public function __construct(SUN_Delivery_Service $delivery){$this->delivery=$delivery;}

	/** @return array<string,mixed> */
	public function snapshot(){
		global $wpdb,$wp_version;
		$tables=array();
		foreach(array('events','notifications','preferences','subscriptions','deliveries','templates','policies','devices','dead_letters','audit','bulk_jobs','attention_profiles','notification_states','notification_rules','device_profiles','provider_routes','experiments','trace_spans','watch_history','request_idempotency','webhook_receipts') as $logical){
			$table=SUN_Database::table($logical);
			$tables[$logical]=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table;
		}
		$queue=SUN_Database::table('deliveries');
		$dead=SUN_Database::table('dead_letters');
		$metrics=array(
			'queued'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='queued'"),
			'failed'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='failed'"),
			'expired'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='expired'"),
			'dead_letter'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$dead} WHERE status='open'"),
			'oldest_queue_seconds'=>0,
			'attention_profiles'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_Database::table('attention_profiles')),
			'automation_rules'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_Database::table('notification_rules').' WHERE enabled=1'),
			'active_experiments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SUN_Database::table('experiments')." WHERE status='running'")
		);
		$oldest=$wpdb->get_var("SELECT MIN(created_at) FROM {$queue} WHERE status IN ('queued','failed')");
		if($oldest){$metrics['oldest_queue_seconds']=max(0,time()-strtotime($oldest.' UTC'));}

		$gate=SUN_Operational_Gate::snapshot();
		$circuits=SUN_Provider_Circuit::health();
		$legacy=SUN_Legacy_Migration::health();
		$cross=SUN_Cross_File_Contracts::health();

		$checks=array(
			'schema'=>!in_array(false,$tables,true),
			'db_version'=>SUN_DB_VERSION===get_option('sun_db_version',''),
			'cron_queue'=>(bool)wp_next_scheduled('sun_process_delivery_queue'),
			'cron_reconcile'=>(bool)wp_next_scheduled('sun_reconcile_notifications'),
			'cron_expire'=>(bool)wp_next_scheduled('sun_expire_notifications'),
			'encryption'=>!is_wp_error(SUN_Crypto::encrypt('health-probe')),
			'runtime_compatible'=>version_compare((string)$wp_version,SUN_MIN_WP_VERSION,'>=')&&version_compare(PHP_VERSION,SUN_MIN_PHP_VERSION,'>='),
			'file00_contract'=>SUN_Auth::file00_contract_available(),
			'file02_authentication_assurance'=>SUN_Auth::file02_assurance_available(),
			'file01_registry'=>!empty($cross['file01_registry']),
			'file20_single_bell'=>!empty($cross['file20_single_bell']),
			'file24_assurance_contract'=>!empty($cross['file24_assurance_contract']),
			'file25_visual_contract'=>!empty($cross['file25_visual_contract']),
			'file26_saved_search_verifier'=>!empty($cross['file26_saved_search_verifier']),
			'queue_lag_ok'=>$metrics['oldest_queue_seconds']<(int)apply_filters('sun_queue_lag_alert_seconds',3600),
			'dead_letters_ok'=>$metrics['dead_letter']<(int)apply_filters('sun_dead_letter_alert_count',10),
			'provider_circuits'=>!array_filter($circuits,static function($state){return!empty($state['open']);}),
			'operational_gate'=>empty($gate['safe_mode_active']),
			'attention_os'=>(bool)class_exists('SUN_Attention_Service'),
			'automation'=>(bool)class_exists('SUN_Automation_Service'),
			'routing'=>(bool)class_exists('SUN_Routing_Service'),
			'experiments'=>(bool)class_exists('SUN_Experiments_Service'),
			'trace'=>(bool)class_exists('SUN_Trace_Service'),
			'legacy_migration_gate'=>!empty($legacy['ready'])
		);

		/* Required runtime dependencies and core safety controls determine health.
		 * Optional companion capabilities remain visible without falsely making a
		 * standalone notification runtime unhealthy. */
		$required=array(
			'schema','db_version','cron_queue','cron_reconcile','cron_expire','encryption','runtime_compatible',
			'file00_contract','file01_registry','file20_single_bell','file24_assurance_contract',
			'queue_lag_ok','dead_letters_ok','provider_circuits','operational_gate',
			'attention_os','automation','routing','experiments','trace','legacy_migration_gate'
		);
		$required_failed=false;
		foreach($required as $key){if(empty($checks[$key])){$required_failed=true;break;}}
		$status=$required_failed?'degraded':'healthy';

		return array(
			'contract'=>'sun.health.v7',
			'status'=>$status,
			'plugin_version'=>SUN_VERSION,
			'db_version'=>get_option('sun_db_version',''),
			'php'=>PHP_VERSION,
			'wordpress'=>$wp_version,
			'minimums'=>array('php'=>SUN_MIN_PHP_VERSION,'wordpress'=>SUN_MIN_WP_VERSION),
			'checks'=>$checks,
			'optional_capabilities'=>array(
				'file02_authentication_assurance'=>!empty($checks['file02_authentication_assurance']),
				'file25_visual_contract'=>!empty($checks['file25_visual_contract']),
				'file26_saved_search_verifier'=>!empty($checks['file26_saved_search_verifier'])
			),
			'tables'=>$tables,
			'metrics'=>$metrics,
			'adapters'=>$this->delivery->adapter_health(),
			'provider_circuits'=>$circuits,
			'operational_gate'=>$gate,
			'legacy_migration'=>$legacy,
			'cross_file'=>$cross,
			'four_plan_compliance'=>SUN_Four_Plan_Compliance::snapshot(),
			'last_reconciliation'=>get_option('sun_last_reconciliation',array()),
			'generated_at'=>SUN_Database::now()
		);
	}
	/** @return array<string,mixed> */
	public function sanitized_export(){
		$data=$this->snapshot();
		$data['site']=array('host_hash'=>hash('sha256',(string)wp_parse_url(home_url(),PHP_URL_HOST)));
		return$data;
	}
}
