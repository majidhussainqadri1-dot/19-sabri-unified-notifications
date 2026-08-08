<?php
/** Privacy-minimized notification fatigue and healthy-use metrics. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Wellbeing {
	/** @param int $user_id User ID. @param int $days Lookback days. @return array<string,mixed> */
	public function summary($user_id,$days=30){
		global $wpdb;$user_id=absint($user_id);$days=max(1,min(90,absint($days)));$since=gmdate('Y-m-d H:i:s',time()-($days*DAY_IN_SECONDS));$notes=SUN_Database::table('notifications');$deliv=SUN_Database::table('deliveries');$prefs=SUN_Database::table('preferences');$subs=SUN_Database::table('subscriptions');$audit=SUN_Database::table('audit');
		$created=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status<>'deleted'",$user_id,$since));
		$unread=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status='unread'",$user_id,$since));
		$archived=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status='archived'",$user_id,$since));
		$failed=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$deliv} WHERE recipient_id=%d AND created_at>=%s AND status IN ('failed','dead_letter','bounced')",$user_id,$since));
		$disabled=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefs} WHERE user_id=%d AND enabled=0",$user_id));
		$disabled_subscriptions=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subs} WHERE user_id=%d AND enabled=0",$user_id));
		$avg_unread_age=(float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(AVG(TIMESTAMPDIFF(SECOND,created_at,%s))/3600,0) FROM {$notes} WHERE recipient_id=%d AND created_at>=%s AND status='unread'",SUN_Database::now(),$user_id,$since));
		$complaints=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$audit} WHERE actor_id=%d AND action='notification_complaint' AND created_at>=%s",$user_id,$since));
		$unread_ratio=$created>0?round($unread/$created,3):0.0;$rate=round($created/$days,2);$cost=round(($unread_ratio*40)+min(30,$rate*2)+min(20,$complaints*5)+min(10,($disabled+$disabled_subscriptions)*0.5),1);
		$signal='low';if($cost>=70){$signal='high';}elseif($cost>=40){$signal='medium';}
		return array('contract'=>'sun.wellbeing.v2','lookback_days'=>$days,'created'=>$created,'unread'=>$unread,'archived'=>$archived,'external_failures'=>$failed,'disabled_preferences'=>$disabled,'disabled_subscriptions'=>$disabled_subscriptions,'notification_rate_per_day'=>$rate,'average_unread_age_hours'=>round($avg_unread_age,2),'complaints'=>$complaints,'unread_ratio'=>$unread_ratio,'fatigue_cost_score'=>$cost,'fatigue_signal'=>$signal,'guardrail'=>'more-notifications-is-not-a-kpi','generated_at'=>SUN_Database::now());
	}

	/** @param int $user_id User ID. @param string $reason Reason code. @return true|WP_Error */
	public function record_complaint($user_id,$reason){$user_id=absint($user_id);$reason=sanitize_key($reason);$allowed=array('too_frequent','not_relevant','duplicate','poor_timing','unsafe_preview','other');if($user_id<1||!in_array($reason,$allowed,true)){return new WP_Error('sun_complaint_invalid',__('The notification feedback is invalid.','sabri-unified-notifications'),array('status'=>400));}return SUN_Audit::record('notification_complaint','notification_feedback','aggregate',array('reason'=>$reason,'purpose'=>'wellbeing_feedback'),$user_id)?true:new WP_Error('sun_complaint_write_failed',__('The notification feedback could not be saved.','sabri-unified-notifications'),array('status'=>500));}
}
