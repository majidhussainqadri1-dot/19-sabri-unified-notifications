<?php
/** WordPress privacy exporter/eraser and retention lifecycle integration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Privacy {
	/** @return void */ public function register(){add_filter('wp_privacy_personal_data_exporters',array($this,'exporters'));add_filter('wp_privacy_personal_data_erasers',array($this,'erasers'));add_action('sun_privacy_retention',array($this,'retention'));}
	/** @param array<string,mixed> $exporters Exporters. @return array<string,mixed> */ public function exporters($exporters){$exporters['sabri-unified-notifications']=array('exporter_friendly_name'=>__('Sabri Notifications','sabri-unified-notifications'),'callback'=>array($this,'export'));return $exporters;}
	/** @param array<string,mixed> $erasers Erasers. @return array<string,mixed> */ public function erasers($erasers){$erasers['sabri-unified-notifications']=array('eraser_friendly_name'=>__('Sabri Notifications','sabri-unified-notifications'),'callback'=>array($this,'erase'));return $erasers;}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function export($email,$page=1){
		global $wpdb;
		$user=get_user_by('email',$email);
		if(!$user){return array('data'=>array(),'done'=>true);}
		$limit=100;$page=max(1,absint($page));$offset=($page-1)*$limit;$data=array();$counts=array();
		$queries=array(
			'notes'=>array('sql'=>'SELECT public_id,category,priority,title,summary,status,created_at,read_at,archived_at FROM '.SUN_Database::table('notifications').' WHERE recipient_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notifications','label'=>__('Notifications','sabri-unified-notifications'),'prefix'=>'notification-','id'=>'public_id'),
			'deliveries'=>array('sql'=>'SELECT public_id,channel,provider,route_provider,status,attempt_count,scheduled_at,accepted_at,delivered_at,created_at,updated_at FROM '.SUN_Database::table('deliveries').' WHERE recipient_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-deliveries','label'=>__('Notification Delivery History','sabri-unified-notifications'),'prefix'=>'delivery-','id'=>'public_id'),
			'prefs'=>array('sql'=>'SELECT category,channel,enabled,digest_frequency,quiet_enabled,quiet_start,quiet_end,timezone,consent_source,consent_at,created_at,updated_at FROM '.SUN_Database::table('preferences').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-preferences','label'=>__('Notification Preferences','sabri-unified-notifications'),'prefix'=>'preference-','id'=>null),
			'subs'=>array('sql'=>'SELECT public_id,scope_type,scope_id,enabled,frequency,created_at,updated_at FROM '.SUN_Database::table('subscriptions').' WHERE user_id=%d ORDER BY scope_type,scope_id,id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-subscriptions','label'=>__('Notification Subscriptions','sabri-unified-notifications'),'prefix'=>'subscription-','id'=>'public_id'),
			'devices'=>array('sql'=>'SELECT public_id,provider,platform,status,last_seen_at,expires_at,created_at,updated_at FROM '.SUN_Database::table('devices').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-devices','label'=>__('Notification Devices','sabri-unified-notifications'),'prefix'=>'device-','id'=>'public_id'),
			'states'=>array('sql'=>'SELECT n.public_id,s.pinned_at,s.snoozed_until,s.action_state,s.attention_score,s.attention_reason,s.source_label,s.source_kind,s.source_verified,s.live_revision,s.revoked_at,s.revoke_reason,s.created_at,s.updated_at FROM '.SUN_Database::table('notification_states').' s INNER JOIN '.SUN_Database::table('notifications').' n ON n.id=s.notification_id WHERE s.user_id=%d ORDER BY s.id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-attention-state','label'=>__('Notification Attention State','sabri-unified-notifications'),'prefix'=>'attention-','id'=>'public_id'),
			'rules'=>array('sql'=>'SELECT public_id,name,trigger_type,trigger_json,action_json,enabled,last_matched_at,created_at,updated_at FROM '.SUN_Database::table('notification_rules').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-rules','label'=>__('Notification Automation Rules','sabri-unified-notifications'),'prefix'=>'rule-','id'=>'public_id'),
			'device_profiles'=>array('sql'=>'SELECT device_public_id,focus_mode,categories_json,channels_json,created_at,updated_at FROM '.SUN_Database::table('device_profiles').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-device-profiles','label'=>__('Notification Device Preferences','sabri-unified-notifications'),'prefix'=>'device-profile-','id'=>'device_public_id'),
			'watch'=>array('sql'=>'SELECT object_type,object_id,engagement_type,first_seen_at,last_seen_at FROM '.SUN_Database::table('watch_history').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d','group'=>'sabri-notification-watch-history','label'=>__('Notification Correction Watch History','sabri-unified-notifications'),'prefix'=>'watch-','id'=>null),
		);
		foreach($queries as $key=>$config){
			$rows=$wpdb->get_results($wpdb->prepare($config['sql'],(int)$user->ID,$limit,$offset),ARRAY_A);
			if(!is_array($rows)){return new WP_Error('sun_privacy_export_query_failed',__('Notification privacy export could not read the requested records safely.','sabri-unified-notifications'));}
			$counts[$key]=count($rows);
			foreach($rows as $index=>$row){
				$record_id=null===$config['id']?(string)($offset+$index+1):(string)($row[$config['id']]??'');
				$data[]=$this->export_item($config['group'],$config['label'],$config['prefix'].$record_id,$row);
			}
		}
		if(1===$page){
			$profile=$wpdb->get_row($wpdb->prepare('SELECT focus_mode,essential_only,hourly_budget,daily_budget,best_time_enabled,best_time_local,ai_summary_enabled,history_days,muted_until,created_at,updated_at FROM '.SUN_Database::table('attention_profiles').' WHERE user_id=%d LIMIT 1',$user->ID),ARRAY_A);
			if(null===$profile&&!empty($wpdb->last_error)){return new WP_Error('sun_privacy_export_query_failed',__('Notification privacy export could not read the settings safely.','sabri-unified-notifications'));}
			if($profile){$data[]=$this->export_item('sabri-notification-attention',__('Notification Attention Settings','sabri-unified-notifications'),'attention-profile',$profile);}
		}
		return array('data'=>$data,'done'=>!in_array($limit,$counts,true));
	}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function erase($email,$page=1){
		global $wpdb;
		$user=get_user_by('email',$email);
		if(!$user){return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);}
		$hold=(bool)apply_filters('sun_user_retention_hold',false,$user->ID);
		if($hold){return array('items_removed'=>false,'items_retained'=>true,'messages'=>array(__('Some notification records are under an approved retention hold.','sabri-unified-notifications')),'done'=>true);}
		$notes=SUN_Database::table('notifications');$devices=SUN_Database::table('devices');$prefs=SUN_Database::table('preferences');
		$subs=SUN_Database::table('subscriptions');$deliveries=SUN_Database::table('deliveries');
		$states=SUN_Database::table('notification_states');$now=SUN_Database::now();
		$failed=0;$removed=false;
		/* Record every mutation's result. A failed DB write must never be
		 * reported as successful privacy erasure or audited as completed. */
		$updates=array(
			$wpdb->prepare("UPDATE {$notes} SET recipient_id=0,status='deleted',title='',summary='',data_ciphertext=NULL,deep_link=NULL,deep_link_context=NULL,read_at=NULL,archived_at=NULL,updated_at=%s WHERE recipient_id=%d",$now,$user->ID),
			$wpdb->prepare("UPDATE {$deliveries} SET recipient_id=0,provider_message_id=NULL,last_error_code=NULL,last_error_safe=NULL,updated_at=%s WHERE recipient_id=%d",$now,$user->ID),
			$wpdb->prepare("UPDATE {$states} SET user_id=0,attention_reason=NULL,source_label=NULL,meta_ciphertext=NULL,pinned_at=NULL,snoozed_until=NULL,action_state='none',updated_at=%s WHERE user_id=%d",$now,$user->ID),
		);
		foreach($updates as $statement){
			$changed=$wpdb->query($statement);
			if(false===$changed){++$failed;}else{$removed=$removed||$changed>0;}
		}
		foreach(array('devices','preferences','subscriptions','attention_profiles','notification_rules','device_profiles','watch_history','request_idempotency') as $logical){
			$changed=$wpdb->delete(SUN_Database::table($logical),array('user_id'=>$user->ID),array('%d'));
			if(false===$changed){++$failed;}else{$removed=$removed||$changed>0;}
		}
		/* Report external and legacy erasure only after database mutations pass.
		 * Partial deletion must be recorded as retained until explicit retry. */
		if($failed>0){
			SUN_Audit::record('privacy_erasure_failed','user',hash('sha256','erase-error:'.$user->ID),array('purpose'=>'privacy_request','failed_mutations'=>$failed),0);
			return new WP_Error('sun_privacy_erasure_db_failed',__('Notification data erasure was interrupted by a database write failure. The request has not been completed.','sabri-unified-notifications'));
		}
		$legacy=$this->scrub_legacy_event_payloads((int)$user->ID,$page);
		if(!empty($legacy['failed'])){
			SUN_Audit::record('privacy_erasure_failed','user',hash('sha256','erase-legacy:'.$user->ID),array('purpose'=>'privacy_request','failed_mutations'=>(int)$legacy['failed']),0);
			return new WP_Error('sun_privacy_legacy_scrub_failed',__('Legacy notification data could not be checked or erased safely. The request remains incomplete.','sabri-unified-notifications'));
		}
		if(!empty($legacy['done'])){
			do_action('sun_privacy_provider_erasure_requested',(int)$user->ID,$email);
			SUN_Audit::record('privacy_erasure_completed','user',hash('sha256','erased:'.$user->ID),array('purpose'=>'privacy_request','advanced_attention'=>true,'legacy_payload_scan_done'=>true),0);
		}
		return array('items_removed'=>$removed||!empty($legacy['changed']),'items_retained'=>false,'messages'=>array(),'done'=>!empty($legacy['done']));
	}

	/** @return array<string,int> */ public function retention(){global$wpdb;$events=SUN_Database::table('events');$minimize_days=max(7,min(365,(int)apply_filters('sun_event_payload_retention_days',30)));$delete_days=max($minimize_days,min(730,(int)apply_filters('sun_event_row_retention_days',180)));$minimize_before=gmdate('Y-m-d H:i:s',time()-$minimize_days*DAY_IN_SECONDS);$delete_before=gmdate('Y-m-d H:i:s',time()-$delete_days*DAY_IN_SECONDS);$minimized=(int)$wpdb->query($wpdb->prepare("UPDATE {$events} SET payload_ciphertext=NULL,updated_at=%s WHERE status='processed' AND payload_ciphertext IS NOT NULL AND created_at<%s",SUN_Database::now(),$minimize_before));$deleted=(int)$wpdb->query($wpdb->prepare("DELETE e FROM {$events} e LEFT JOIN ".SUN_Database::table('notifications')." n ON n.producer=e.producer AND n.event_id=e.event_id WHERE e.created_at<%s AND n.id IS NULL",$delete_before));return array('payloads_minimized'=>max(0,$minimized),'rows_deleted'=>max(0,$deleted));}
	/** @param int $user_id User ID. @param int $page Page. @return array<string,mixed> */
	private function scrub_legacy_event_payloads($user_id,$page=1){
		global $wpdb;
		$table=SUN_Database::table('events');$limit=200;$page=max(1,absint($page));$offset=($page-1)*$limit;
		$rows=$wpdb->get_results($wpdb->prepare("SELECT id,payload_ciphertext FROM {$table} WHERE payload_ciphertext IS NOT NULL ORDER BY id ASC LIMIT %d OFFSET %d",$limit,$offset),ARRAY_A);
		if(!is_array($rows)){return array('changed'=>0,'done'=>false,'failed'=>1,'page'=>$page);}
		$changed=0;$failed=0;
		foreach($rows as $row){
			$plain=SUN_Crypto::decrypt($row['payload_ciphertext']);
			if(is_wp_error($plain)){++$failed;continue;}
			$payload=json_decode($plain,true);
			if(!is_array($payload)){++$failed;continue;}
			$dirty=false;
			if(isset($payload['recipients'])&&is_array($payload['recipients'])){
				$before=count($payload['recipients']);
				$payload['recipients']=array_values(array_filter($payload['recipients'],static function($r)use($user_id){return absint(is_array($r)?($r['user_id']??0):$r)!==$user_id;}));
				$dirty=$dirty||$before!==count($payload['recipients']);
			}
			foreach(array('actor','subject')as$k){if(isset($payload[$k]['id'])&&absint($payload[$k]['id'])===$user_id){$payload[$k]=array();$dirty=true;}}
			if(!$dirty){continue;}
			$cipher=SUN_Crypto::encrypt(SUN_Database::canonical_json($payload));
			if(is_wp_error($cipher)){++$failed;continue;}
			$updated=$wpdb->update($table,array('payload_ciphertext'=>$cipher,'updated_at'=>SUN_Database::now()),array('id'=>(int)$row['id']));
			if(false===$updated){++$failed;}else{++$changed;}
		}
		return array('changed'=>$changed,'done'=>count($rows)<$limit&&0===$failed,'failed'=>$failed,'page'=>$page);
	}
	/** @param string $group_id Group ID. @param string $group_label Label. @param string $item_id Item ID. @param array<string,mixed> $row Row. @return array<string,mixed> */ private function export_item($group_id,$group_label,$item_id,array $row){$data=array();foreach($row as $key=>$value){$data[]=array('name'=>ucwords(str_replace('_',' ',$key)),'value'=>(string)$value);}return array('group_id'=>$group_id,'group_label'=>$group_label,'item_id'=>$item_id,'data'=>$data);}
}
