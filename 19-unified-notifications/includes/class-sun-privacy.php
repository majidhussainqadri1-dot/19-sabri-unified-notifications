<?php
/** WordPress privacy exporter/eraser and retention lifecycle integration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Privacy {
	/** @return void */ public function register(){add_filter('wp_privacy_personal_data_exporters',array($this,'exporters'));add_filter('wp_privacy_personal_data_erasers',array($this,'erasers'));}
	/** @param array<string,mixed> $exporters Exporters. @return array<string,mixed> */ public function exporters($exporters){$exporters['sabri-unified-notifications']=array('exporter_friendly_name'=>__('Sabri Notifications','sabri-unified-notifications'),'callback'=>array($this,'export'));return $exporters;}
	/** @param array<string,mixed> $erasers Erasers. @return array<string,mixed> */ public function erasers($erasers){$erasers['sabri-unified-notifications']=array('eraser_friendly_name'=>__('Sabri Notifications','sabri-unified-notifications'),'callback'=>array($this,'erase'));return $erasers;}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function export($email,$page=1){
		global $wpdb;$user=get_user_by('email',$email);if(!$user){return array('data'=>array(),'done'=>true);}$limit=100;$page=max(1,absint($page));$offset=($page-1)*$limit;$data=array();
		$rows=$wpdb->get_results($wpdb->prepare('SELECT public_id,category,priority,title,summary,status,created_at,read_at,archived_at FROM '.SUN_Database::table('notifications').' WHERE recipient_id=%d ORDER BY id ASC LIMIT %d OFFSET %d',$user->ID,$limit,$offset),ARRAY_A);foreach($rows as $row){$data[]=$this->export_item('sabri-notifications',__('Notifications','sabri-unified-notifications'),'notification-'.$row['public_id'],$row);}
		$deliveries=$wpdb->get_results($wpdb->prepare('SELECT public_id,channel,provider,status,attempt_count,scheduled_at,accepted_at,delivered_at,created_at,updated_at FROM '.SUN_Database::table('deliveries').' WHERE recipient_id=%d ORDER BY id ASC LIMIT %d OFFSET %d',$user->ID,$limit,$offset),ARRAY_A);foreach($deliveries as $delivery){$data[]=$this->export_item('sabri-notification-deliveries',__('Notification Delivery History','sabri-unified-notifications'),'delivery-'.$delivery['public_id'],$delivery);}
		if(1===$page){
			$prefs=$wpdb->get_results($wpdb->prepare('SELECT category,channel,enabled,digest_frequency,quiet_enabled,quiet_start,quiet_end,timezone,consent_source,consent_at,created_at,updated_at FROM '.SUN_Database::table('preferences').' WHERE user_id=%d ORDER BY id ASC',$user->ID),ARRAY_A);foreach($prefs as $index=>$pref){$data[]=$this->export_item('sabri-notification-preferences',__('Notification Preferences','sabri-unified-notifications'),'preference-'.($index+1),$pref);}
			$subs=$wpdb->get_results($wpdb->prepare('SELECT public_id,scope_type,scope_id,enabled,frequency,created_at,updated_at FROM '.SUN_Database::table('subscriptions').' WHERE user_id=%d ORDER BY scope_type,scope_id',$user->ID),ARRAY_A);foreach($subs as $sub){$data[]=$this->export_item('sabri-notification-subscriptions',__('Notification Subscriptions','sabri-unified-notifications'),'subscription-'.$sub['public_id'],$sub);}
			$devices=$wpdb->get_results($wpdb->prepare('SELECT public_id,provider,platform,status,last_seen_at,expires_at,created_at,updated_at FROM '.SUN_Database::table('devices').' WHERE user_id=%d ORDER BY id ASC',$user->ID),ARRAY_A);foreach($devices as $device){$data[]=$this->export_item('sabri-notification-devices',__('Notification Devices','sabri-unified-notifications'),'device-'.$device['public_id'],$device);}
		}
		return array('data'=>$data,'done'=>count($rows)<$limit&&count($deliveries)<$limit);
	}

	/** @param string $email Email. @param int $page Page. @return array<string,mixed> */
	public function erase($email,$page=1){
		global $wpdb;$user=get_user_by('email',$email);if(!$user){return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);}$hold=(bool)apply_filters('sun_user_retention_hold',false,$user->ID);if($hold){return array('items_removed'=>false,'items_retained'=>true,'messages'=>array(__('Some notification records are under an approved retention hold.','sabri-unified-notifications')),'done'=>true);}
		$notes=SUN_Database::table('notifications');$devices=SUN_Database::table('devices');$prefs=SUN_Database::table('preferences');$subs=SUN_Database::table('subscriptions');$deliveries=SUN_Database::table('deliveries');$now=SUN_Database::now();
		$wpdb->query($wpdb->prepare("UPDATE {$notes} SET recipient_id=0,status='deleted',title='',summary='',data_ciphertext=NULL,deep_link=NULL,deep_link_context=NULL,read_at=NULL,archived_at=NULL,updated_at=%s WHERE recipient_id=%d",$now,$user->ID));
		$wpdb->query($wpdb->prepare("UPDATE {$deliveries} SET recipient_id=0,provider_message_id=NULL,last_error_code=NULL,last_error_safe=NULL,updated_at=%s WHERE recipient_id=%d",$now,$user->ID));
		$wpdb->delete($devices,array('user_id'=>$user->ID),array('%d'));$wpdb->delete($prefs,array('user_id'=>$user->ID),array('%d'));$wpdb->delete($subs,array('user_id'=>$user->ID),array('%d'));
		do_action('sun_privacy_provider_erasure_requested',(int)$user->ID,$email);SUN_Audit::record('privacy_erasure_completed','user',hash('sha256','erased:'.$user->ID),array('purpose'=>'privacy_request'),0);return array('items_removed'=>true,'items_retained'=>false,'messages'=>array(),'done'=>true);
	}
	/** @param string $group_id Group ID. @param string $group_label Label. @param string $item_id Item ID. @param array<string,mixed> $row Row. @return array<string,mixed> */ private function export_item($group_id,$group_label,$item_id,array $row){$data=array();foreach($row as $key=>$value){$data[]=array('name'=>ucwords(str_replace('_',' ',$key)),'value'=>(string)$value);}return array('group_id'=>$group_id,'group_label'=>$group_label,'item_id'=>$item_id,'data'=>$data);}
}
