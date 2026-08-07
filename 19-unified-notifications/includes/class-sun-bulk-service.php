<?php
/**
 * Founder-approved bulk administrative notification previews and bounded execution.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Bulk_Service {
	/** @var SUN_Notification_Service */ private $notifications;
	/** @var SUN_Auth */ private $auth;
	/** @param SUN_Notification_Service $notifications Notifications. @param SUN_Auth $auth Auth. */
	public function __construct( SUN_Notification_Service $notifications, SUN_Auth $auth ) { $this->notifications=$notifications; $this->auth=$auth; }

	/** @param int[] $user_ids Explicit recipients. @param array<string,mixed> $event Event template. @return array<string,mixed>|WP_Error */
	public function preview( array $user_ids, array $event ) {
		global $wpdb;
		if ( ! SUN_Operational_Gate::allows( 'bulk_preview' ) ) { return new WP_Error('sun_bulk_contained',__('Bulk notices are temporarily disabled by the operational safety gate.','sabri-unified-notifications'),array('status'=>503)); }
		if ( ! $this->auth->can_send_bulk() ) { return new WP_Error('sun_bulk_forbidden',__('Founder approval and current step-up verification are required for bulk notices.','sabri-unified-notifications'),array('status'=>403)); }
		$user_ids=array_values(array_unique(array_filter(array_map('absint',$user_ids))));
		$maximum=(int)apply_filters('sun_bulk_max_recipients',5000);
		if(empty($user_ids)||count($user_ids)>$maximum){return new WP_Error('sun_bulk_audience_invalid',__('The explicit bulk audience is invalid.','sabri-unified-notifications'),array('status'=>400));}
		$event_type=sanitize_text_field((string)($event['event_type']??'System.AdministrativeNotice'));
		$data=array(
			'user_ids'=>$user_ids,
			'event'=>array(
				'owner'=>'File 19',
				'event_type'=>$event_type,
				'schema_version'=>'1.0',
				'category'=>sanitize_key((string)($event['category']??'system')),
				'priority'=>sanitize_key((string)($event['priority']??'normal')),
				'sensitivity'=>sanitize_key((string)($event['sensitivity']??'standard')),
				'deep_link'=>SUN_Deep_Link::sanitize((string)($event['deep_link']??'')),
				'data'=>array('action_name'=>sanitize_text_field((string)($event['title']??__('Platform notice','sabri-unified-notifications'))),'summary'=>sanitize_textarea_field((string)($event['summary']??''))),
			),
		);
		$cipher=SUN_Crypto::encrypt(SUN_Database::canonical_json($data)); if(is_wp_error($cipher)){return $cipher;}
		$public_id=SUN_Database::uuid();$audience_hash=hash('sha256',implode(',',$user_ids));$confirmation=wp_generate_password(12,false,false);$confirm_hash=wp_hash_password($confirmation);$now=SUN_Database::now();
		$ok=$wpdb->insert(SUN_Database::table('bulk_jobs'),array('public_id'=>$public_id,'created_by'=>get_current_user_id(),'audience_hash'=>$audience_hash,'recipient_count'=>count($user_ids),'event_type'=>$event_type,'payload_ciphertext'=>$cipher,'status'=>'preview','confirmation_hash'=>$confirm_hash,'cancel_requested'=>0,'processed_count'=>0,'failed_count'=>0,'created_at'=>$now,'updated_at'=>$now)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if(false===$ok){return new WP_Error('sun_bulk_preview_failed',__('The bulk preview could not be created.','sabri-unified-notifications'));}
		SUN_Audit::record('bulk_preview_created','bulk_job',$public_id,array('count'=>count($user_ids),'event_type'=>$event_type,'purpose'=>'bulk_notice'));
		return array('id'=>$public_id,'recipient_count'=>count($user_ids),'event_type'=>$event_type,'confirmation_code'=>$confirmation,'status'=>'preview');
	}

	/** @param string $public_id Job ID. @param string $confirmation Confirmation code. @return true|WP_Error */
	public function confirm( $public_id, $confirmation ) {
		global $wpdb;
		if(!SUN_Operational_Gate::allows('bulk_confirm')){return new WP_Error('sun_bulk_contained',__('Bulk notices are temporarily disabled by the operational safety gate.','sabri-unified-notifications'),array('status'=>503));}
		if(!$this->auth->can_send_bulk()){return new WP_Error('sun_bulk_forbidden',__('Founder approval and current step-up verification are required.','sabri-unified-notifications'),array('status'=>403));}
		$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.SUN_Database::table('bulk_jobs').' WHERE public_id=%s AND status=%s LIMIT 1',sanitize_text_field($public_id),'preview'),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if(!$row||!wp_check_password((string)$confirmation,$row['confirmation_hash'])){return new WP_Error('sun_bulk_confirmation_invalid',__('Bulk confirmation is invalid.','sabri-unified-notifications'),array('status'=>400));}
		if((int)$row['created_by']!==get_current_user_id()){return new WP_Error('sun_bulk_actor_mismatch',__('The bulk notice must be confirmed by the same authorized governance actor.','sabri-unified-notifications'),array('status'=>403));}
		$updated=$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>'queued','updated_at'=>SUN_Database::now()),array('id'=>(int)$row['id'],'status'=>'preview')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if(1!==(int)$updated){return new WP_Error('sun_bulk_confirmation_conflict',__('The bulk notice changed before confirmation.','sabri-unified-notifications'),array('status'=>409));}
		if(!wp_next_scheduled('sun_process_bulk_jobs')){wp_schedule_single_event(time()+5,'sun_process_bulk_jobs');}
		SUN_Audit::record('bulk_job_confirmed','bulk_job',$public_id,array('count'=>(int)$row['recipient_count'],'purpose'=>'bulk_notice'));
		return true;
	}

	/** @param string $public_id Job ID. @return true|WP_Error */
	public function cancel($public_id){global $wpdb;if(!$this->auth->can_send_bulk()){return new WP_Error('sun_bulk_forbidden',__('Founder approval and current step-up verification are required.','sabri-unified-notifications'),array('status'=>403));}$updated=$wpdb->query($wpdb->prepare("UPDATE ".SUN_Database::table('bulk_jobs')." SET cancel_requested=1,status=IF(status='preview','cancelled',status),updated_at=%s WHERE public_id=%s AND created_by=%d AND status IN ('preview','queued','processing')",SUN_Database::now(),sanitize_text_field($public_id),get_current_user_id())); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $updated?true:new WP_Error('sun_bulk_not_found',__('Bulk job not found.','sabri-unified-notifications'),array('status'=>404));}

	/** @param int $job_limit Job limit. @return array<string,int> */
	public function process( $job_limit = 2 ) {
		global $wpdb;
		$stats=array('jobs'=>0,'created'=>0,'failed'=>0,'held'=>0);
		if(!SUN_Operational_Gate::allows('bulk_process')){return $stats;}
		$jobs=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".SUN_Database::table('bulk_jobs')." WHERE status IN ('queued','processing') AND cancel_requested=0 ORDER BY id ASC LIMIT %d",max(1,min(10,absint($job_limit)))),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		foreach($jobs as $job){
			++$stats['jobs'];
			if(!$this->auth->is_governance_actor_eligible((int)$job['created_by'],true)){
				$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>'held','updated_at'=>SUN_Database::now()),array('id'=>(int)$job['id'])); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				SUN_Audit::record('bulk_job_held','bulk_job',$job['public_id'],array('reason'=>'actor_revalidation_failed','purpose'=>'bulk_notice'),0);
				++$stats['held'];
				continue;
			}
			$plain=SUN_Crypto::decrypt($job['payload_ciphertext']);if(is_wp_error($plain)){$this->fail_job($job,'decrypt_failed');++$stats['failed'];continue;}$payload=json_decode($plain,true);if(!is_array($payload)){$this->fail_job($job,'payload_invalid');++$stats['failed'];continue;}$user_ids=array_slice((array)$payload['user_ids'],(int)$job['processed_count'],100);if(empty($user_ids)){$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>'completed','updated_at'=>SUN_Database::now()),array('id'=>(int)$job['id']));continue;}$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>'processing','updated_at'=>SUN_Database::now()),array('id'=>(int)$job['id']));$event=$payload['event'];$event['producer']='sabri-system';$event['owner']='File 19';$event['event_id']='bulk:'.$job['public_id'].':'.(int)$job['processed_count'];$event['occurred_at']=gmdate(DATE_ATOM);$event['recipients']=array_map(static function($id){return array('user_id'=>(int)$id);},$user_ids);$result=$this->notifications->ingest_event($event,'bulk');$created=is_wp_error($result)?0:(int)$result['created'];$failed=is_wp_error($result)?count($user_ids):0;$processed=(int)$job['processed_count']+count($user_ids);$status=$processed>=(int)$job['recipient_count']?'completed':'processing';$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>$status,'processed_count'=>$processed,'failed_count'=>(int)$job['failed_count']+$failed,'updated_at'=>SUN_Database::now()),array('id'=>(int)$job['id']));$stats['created']+=$created;$stats['failed']+=$failed;
		}
		if($wpdb->get_var("SELECT COUNT(*) FROM ".SUN_Database::table('bulk_jobs')." WHERE status IN ('queued','processing') AND cancel_requested=0")){wp_schedule_single_event(time()+30,'sun_process_bulk_jobs');} // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $stats;
	}

	/** @param array<string,mixed> $job Job. @param string $reason Reason. @return void */ private function fail_job(array $job,$reason){global $wpdb;$wpdb->update(SUN_Database::table('bulk_jobs'),array('status'=>'failed','failed_count'=>(int)$job['recipient_count'],'updated_at'=>SUN_Database::now()),array('id'=>(int)$job['id']));SUN_Audit::record('bulk_job_failed','bulk_job',$job['public_id'],array('reason'=>$reason,'purpose'=>'bulk_notice'),0);}
}
