<?php
/**
 * Privacy-safe health, observability and System Check evidence.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Health {
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @param SUN_Delivery_Service $delivery Delivery. */ public function __construct(SUN_Delivery_Service $delivery){$this->delivery=$delivery;}
	/** @return array<string,mixed> */
	public function snapshot(){
		global $wpdb,$wp_version;$tables=array();foreach(array('events','notifications','preferences','subscriptions','deliveries','templates','policies','devices','dead_letters','audit','bulk_jobs') as $logical){$table=SUN_Database::table($logical);$tables[$logical]=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table;}
		$queue=SUN_Database::table('deliveries');$dead=SUN_Database::table('dead_letters');$metrics=array('queued'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='queued'"),'failed'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$queue} WHERE status='failed'"),'dead_letter'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$dead} WHERE status='open'"),'oldest_queue_seconds'=>0); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$oldest=$wpdb->get_var("SELECT MIN(created_at) FROM {$queue} WHERE status IN ('queued','failed')");if($oldest){$metrics['oldest_queue_seconds']=max(0,time()-strtotime($oldest.' UTC'));} // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$gate=SUN_Operational_Gate::snapshot();$circuits=SUN_Provider_Circuit::health();$checks=array('schema'=>!in_array(false,$tables,true),'cron_queue'=>(bool)wp_next_scheduled('sun_process_delivery_queue'),'cron_reconcile'=>(bool)wp_next_scheduled('sun_reconcile_notifications'),'encryption'=>!is_wp_error(SUN_Crypto::encrypt('health-probe')),'runtime_compatible'=>version_compare((string)$wp_version,SUN_MIN_WP_VERSION,'>=')&&version_compare(PHP_VERSION,SUN_MIN_PHP_VERSION,'>='),'file00_contract'=>false!==has_filter('sabri_membership_claims_v2'),'queue_lag_ok'=>$metrics['oldest_queue_seconds']<(int)apply_filters('sun_queue_lag_alert_seconds',3600),'dead_letters_ok'=>$metrics['dead_letter']<(int)apply_filters('sun_dead_letter_alert_count',10),'provider_circuits'=>!array_filter($circuits,static function($state){return !empty($state['open']);}),'operational_gate'=>empty($gate['safe_mode_active']));
		$status=in_array(false,$checks,true)?'degraded':'healthy';return array('contract'=>'sun.health.v4','status'=>$status,'plugin_version'=>SUN_VERSION,'db_version'=>get_option('sun_db_version',''),'php'=>PHP_VERSION,'wordpress'=>$wp_version,'minimums'=>array('php'=>SUN_MIN_PHP_VERSION,'wordpress'=>SUN_MIN_WP_VERSION),'checks'=>$checks,'tables'=>$tables,'metrics'=>$metrics,'adapters'=>$this->delivery->adapter_health(),'provider_circuits'=>$circuits,'operational_gate'=>$gate,'four_plan_compliance'=>SUN_Four_Plan_Compliance::snapshot(),'last_reconciliation'=>get_option('sun_last_reconciliation',array()),'generated_at'=>SUN_Database::now());
	}
	/** @return array<string,mixed> */ public function sanitized_export(){$data=$this->snapshot();$data['site']=array('host_hash'=>hash('sha256',(string)wp_parse_url(home_url(),PHP_URL_HOST)));return $data;}
}
