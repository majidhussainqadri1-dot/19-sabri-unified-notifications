<?php
defined('ABSPATH') || exit;

final class SUN_Channels {
    private const LEASE_SECONDS = 120;
    private static bool $cron_filter_registered = false;

    public static function register_cron_schedules(): void { if (self::$cron_filter_registered) return; self::$cron_filter_registered = true; add_filter('cron_schedules',[self::class,'cron_schedules']); }
    public static function init(): void { self::register_cron_schedules(); }
    public static function cron_schedules(array $schedules): array {
        $schedules['sun_five_minutes']=['interval'=>300,'display'=>'Every five minutes (Sabri Notifications)'];
        $schedules['weekly']=$schedules['weekly']??['interval'=>WEEK_IN_SECONDS,'display'=>'Once Weekly'];
        return $schedules;
    }

    public static function queue(int $notification_id,int $user_id,string $channel): void {
        global $wpdb; if(!in_array($channel,['email','sms','push'],true)||!SUN_DB::table_exists('deliveries'))return;
        $now=SUN_Utils::now();
        if($channel==='push'){
            $devices=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.SUN_DB::table('devices').' WHERE user_id=%d AND enabled=1',$user_id));
            if(!$devices){self::insert_delivery($notification_id,$user_id,'push',0,'waiting_config','No registered push device for this user.');return;}
            foreach($devices as $device_id)self::insert_delivery($notification_id,$user_id,'push',(int)$device_id);
        } else self::insert_delivery($notification_id,$user_id,$channel,0);
        if(!wp_next_scheduled('sun_process_deliveries'))wp_schedule_single_event(time()+10,'sun_process_deliveries');
    }

    private static function insert_delivery(int $notification_id,int $user_id,string $channel,int $device_id=0,string $status='queued',string $error=''): void {
        global $wpdb; $now=SUN_Utils::now();
        $wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.SUN_DB::table('deliveries').' (notification_id,user_id,channel,device_id,status,attempts,next_attempt_at,last_error,created_at,updated_at) VALUES (%d,%d,%s,%d,%s,0,%s,%s,%s,%s)',$notification_id,$user_id,$channel,$device_id,$status,$now,$error,$now,$now));
    }

    public static function process_queue(): void {
        global $wpdb; if(!SUN_DB::table_exists('deliveries'))return;
        $limit=max(5,min(200,(int)get_option('sun_delivery_batch_size',25))); $processed=0;
        self::recover_expired_leases();
        while($processed<$limit){$row=self::claim_next();if(!$row)break;self::process_delivery($row);$processed++;}
        $remaining=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SUN_DB::table('deliveries')." WHERE status IN ('queued','retry')");
        if($remaining>0&&!wp_next_scheduled('sun_process_deliveries'))wp_schedule_single_event(time()+60,'sun_process_deliveries');
    }

    private static function claim_next(): ?array {
        global $wpdb; $now=SUN_Utils::now();
        for($i=0;$i<5;$i++){
            $id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM ".SUN_DB::table('deliveries')." WHERE status IN ('queued','retry','waiting_config') AND (next_attempt_at IS NULL OR next_attempt_at<=%s) ORDER BY id ASC LIMIT 1",$now));
            if($id<=0)return null;
            $token=wp_generate_uuid4();$lease=gmdate('Y-m-d H:i:s',time()+self::LEASE_SECONDS);
            $updated=$wpdb->query($wpdb->prepare("UPDATE ".SUN_DB::table('deliveries')." SET status='processing',lease_token=%s,lease_expires_at=%s,updated_at=%s WHERE id=%d AND status IN ('queued','retry','waiting_config')",$token,$lease,$now,$id));
            if($updated===1){$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.SUN_DB::table('deliveries').' WHERE id=%d AND lease_token=%s',$id,$token),ARRAY_A);return is_array($row)?$row:null;}
        }
        return null;
    }

    private static function recover_expired_leases(): void {
        global $wpdb;$now=SUN_Utils::now();
        $wpdb->query($wpdb->prepare("UPDATE ".SUN_DB::table('deliveries')." SET status='retry',lease_token=NULL,lease_expires_at=NULL,next_attempt_at=%s,updated_at=%s WHERE status='processing' AND lease_expires_at IS NOT NULL AND lease_expires_at<%s",$now,$now,$now));
    }

    private static function process_delivery(array $delivery): void {
        global $wpdb;
        $notification=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.SUN_DB::table('notifications').' WHERE id=%d LIMIT 1',(int)$delivery['notification_id']),ARRAY_A);
        if(!is_array($notification)){self::update_delivery((int)$delivery['id'],'cancelled','Notification no longer exists.','',(int)$delivery['attempts']);return;}
        $user=get_userdata((int)$delivery['user_id']);if(!$user instanceof WP_User){self::update_delivery((int)$delivery['id'],'cancelled','User no longer exists.','',(int)$delivery['attempts']);return;}
        $result=match((string)$delivery['channel']){'email'=>self::send_email($notification,$user),'sms'=>self::send_sms($notification,$user),'push'=>self::send_push($notification,$user,(int)$delivery['device_id']),default=>['ok'=>false,'error'=>'Unsupported delivery channel.','response'=>'','waiting'=>false]};
        $attempts=((int)$delivery['attempts'])+1;
        if(!empty($result['ok'])){self::update_delivery((int)$delivery['id'],'sent','',(string)($result['response']??''),$attempts,SUN_Utils::now());return;}
        if(!empty($result['waiting'])){self::update_delivery((int)$delivery['id'],'waiting_config',(string)($result['error']??'Provider configuration required.'),(string)($result['response']??''),$attempts,null,gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS));return;}
        $max=max(1,min(10,(int)get_option('sun_max_delivery_attempts',4)));
        if($attempts>=$max){self::update_delivery((int)$delivery['id'],'failed',(string)($result['error']??'Delivery failed.'),(string)($result['response']??''),$attempts);self::notify_admin_delivery_failure($notification,(string)$delivery['channel'],(string)($result['error']??'Delivery failed.'));return;}
        $delay=min(6*HOUR_IN_SECONDS,(int)pow(2,$attempts)*300);self::update_delivery((int)$delivery['id'],'retry',(string)($result['error']??'Delivery failed.'),(string)($result['response']??''),$attempts,null,gmdate('Y-m-d H:i:s',time()+$delay));
    }

    private static function send_email(array $notification,WP_User $user): array {
        if(!(bool)get_option('sun_email_enabled',1))return['ok'=>false,'waiting'=>true,'error'=>'Email channel is disabled.','response'=>''];
        if(!is_email($user->user_email))return['ok'=>false,'waiting'=>false,'error'=>'User does not have a valid email address.','response'=>''];
        $preview=SUN_Utils::external_preview($notification);$site=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);$subject='['.$site.'] '.$preview['title'];
        $body='<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:16px"><h2 style="margin:0 0 12px;color:#111827">'.esc_html($preview['title']).'</h2><p style="line-height:1.65;color:#374151">'.nl2br(esc_html($preview['body'])).'</p>';
        if(!empty($notification['link']))$body.='<p><a href="'.esc_url((string)$notification['link']).'" style="display:inline-block;background:#FF8A1F;color:#111827;text-decoration:none;padding:11px 18px;border-radius:10px;font-weight:700">Open notification</a></p>';
        $body.='<p style="font-size:12px;color:#6b7280">Sign in before viewing protected notification details.</p></div>';
        $ok=wp_mail($user->user_email,$subject,$body,['Content-Type: text/html; charset=UTF-8']);
        return['ok'=>$ok,'waiting'=>false,'error'=>$ok?'':'wp_mail() returned false.','response'=>$ok?'Accepted by WordPress mail transport.':''];
    }

    private static function send_sms(array $notification,WP_User $user): array {
        $webhook=self::configured_webhook('sun_sms_webhook_url','sn_sms_webhook_url');if($webhook==='')return['ok'=>false,'waiting'=>true,'error'=>'SMS provider is not configured or its HTTPS URL is unsafe.','response'=>''];
        $phone=SUN_Utils::get_user_phone((int)$user->ID);if($phone==='')return['ok'=>false,'waiting'=>false,'error'=>'User phone number is unavailable.','response'=>''];
        $preview=SUN_Utils::external_preview($notification);$message=mb_substr($preview['title'].': '.$preview['body'],0,420);
        $template=(string)get_option('sun_sms_payload_template','{"to":"{{phone}}","message":"{{message}}","link":"{{link}}"}');
        $payload=self::render_json_payload($template,['phone'=>$phone,'message'=>$message,'title'=>$preview['title'],'body'=>$preview['body'],'link'=>(string)$notification['link'],'user_id'=>(string)$user->ID,'notification_id'=>(string)$notification['id']]);
        $auth=SUN_Utils::decrypt_secret((string)get_option('sun_sms_auth_header',''));if($auth==='')$auth=SUN_Utils::decrypt_secret((string)get_option('sn_sms_auth_header',''));
        return self::post_webhook($webhook,$auth,$payload);
    }

    private static function send_push(array $notification,WP_User $user,int $device_id): array {
        $webhook=self::configured_webhook('sun_push_webhook_url');if($webhook==='')return['ok'=>false,'waiting'=>true,'error'=>'Push provider webhook is not configured or its HTTPS URL is unsafe.','response'=>''];
        global $wpdb;$device=$wpdb->get_row($wpdb->prepare('SELECT id,device_type,device_name,token,endpoint,metadata FROM '.SUN_DB::table('devices').' WHERE id=%d AND user_id=%d AND enabled=1 LIMIT 1',$device_id,(int)$user->ID),ARRAY_A);
        if(!is_array($device))return['ok'=>false,'waiting'=>false,'error'=>'Push device is unavailable or does not belong to the user.','response'=>''];
        $token=SUN_Utils::decrypt_secret((string)$device['token']);if($token==='')return['ok'=>false,'waiting'=>false,'error'=>'Push device token could not be decrypted.','response'=>''];
        $preview=SUN_Utils::external_preview($notification);$template=(string)get_option('sun_push_payload_template','{"token":"{{token}}","title":"{{title}}","body":"{{body}}","link":"{{link}}","notification_id":"{{notification_id}}"}');
        $payload=self::render_json_payload($template,['token'=>$token,'endpoint'=>(string)$device['endpoint'],'device_type'=>(string)$device['device_type'],'title'=>$preview['title'],'body'=>mb_substr($preview['body'],0,240),'link'=>(string)$notification['link'],'category'=>(string)$notification['category'],'priority'=>(string)$notification['priority'],'user_id'=>(string)$user->ID,'notification_id'=>(string)$notification['id']]);
        $auth=SUN_Utils::decrypt_secret((string)get_option('sun_push_auth_header',''));
        return self::post_webhook($webhook,$auth,$payload);
    }

    private static function configured_webhook(string $primary,string $fallback=''): string {
        $url=SUN_Utils::validate_webhook_url((string)get_option($primary,''));if($url===''&&$fallback!=='')$url=SUN_Utils::validate_webhook_url((string)get_option($fallback,''));return$url;
    }

    private static function render_json_payload(string $template,array $values): array {
        $escape=static function(string $value):string{$encoded=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);return is_string($encoded)&&strlen($encoded)>=2?substr($encoded,1,-1):$value;};$replace=[];foreach($values as$key=>$value)$replace['{{'.$key.'}}']=$escape((string)$value);$decoded=json_decode(strtr($template,$replace),true);return is_array($decoded)?$decoded:$values;
    }

    private static function post_webhook(string $url,string $auth_header,array $payload): array {
        $validated=SUN_Utils::validate_webhook_url($url);if($validated==='')return['ok'=>false,'waiting'=>false,'error'=>'Unsafe webhook URL rejected.','response'=>''];
        $headers=['Content-Type'=>'application/json'];if($auth_header!==''&&str_contains($auth_header,':')){[$name,$value]=array_map('trim',explode(':',$auth_header,2));if($name!==''&&$value!==''&&!in_array(strtolower($name),['host','content-length'],true))$headers[$name]=$value;}
        $response=wp_remote_post($validated,['timeout'=>15,'redirection'=>0,'reject_unsafe_urls'=>true,'sslverify'=>true,'headers'=>$headers,'body'=>wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        if(is_wp_error($response))return['ok'=>false,'waiting'=>false,'error'=>$response->get_error_message(),'response'=>''];$code=(int)wp_remote_retrieve_response_code($response);$body=mb_substr(wp_strip_all_tags((string)wp_remote_retrieve_body($response)),0,500);
        return['ok'=>$code>=200&&$code<300,'waiting'=>false,'error'=>($code>=200&&$code<300)?'':'Provider returned HTTP '.$code.'.','response'=>'HTTP '.$code.($body!==''?': '.$body:'')];
    }

    private static function update_delivery(int $id,string $status,string $error,string $response,int $attempts,?string $sent_at=null,?string $next_attempt=null): void {
        global $wpdb;$data=['status'=>sanitize_key($status),'attempts'=>$attempts,'last_error'=>sanitize_textarea_field($error),'provider_response'=>sanitize_textarea_field($response),'updated_at'=>SUN_Utils::now(),'next_attempt_at'=>$next_attempt,'lease_token'=>null,'lease_expires_at'=>null];if($sent_at!==null)$data['sent_at']=$sent_at;$wpdb->update(SUN_DB::table('deliveries'),$data,['id'=>$id]);SUN_Utils::audit('delivery_'.$status,'delivery',$id,['attempts'=>$attempts,'error'=>$error]);
    }

    private static function notify_admin_delivery_failure(array $notification,string $channel,string $error): void {
        $admins=get_users(['role__in'=>['administrator'],'fields'=>'ID','number'=>5]);foreach($admins as$admin_id){if((int)$admin_id===(int)$notification['user_id'])continue;SUN_Core::create(['user_id'=>(int)$admin_id,'category'=>'administration','type'=>'delivery_failure','priority'=>'high','sensitivity'=>'private','title'=>'Notification delivery failed','body'=>strtoupper($channel).' delivery failed for notification #'.(int)$notification['id'].'. '.$error,'link'=>admin_url('admin.php?page=sun-deliveries'),'dedupe_key'=>'delivery_failure:'.$channel.':'.(int)$notification['id']]);}
    }

    public static function send_daily_digests(): void { self::send_digests('daily',DAY_IN_SECONDS); }
    public static function send_weekly_digests(): void { self::send_digests('weekly',WEEK_IN_SECONDS); }
    private static function send_digests(string $mode,int $period): void {
        if(!(bool)get_option('sun_email_enabled',1))return;global$wpdb;$rows=$wpdb->get_results('SELECT user_id,settings FROM '.SUN_DB::table('preferences'),ARRAY_A);$since=gmdate('Y-m-d H:i:s',time()-$period);
        foreach($rows?:[] as$row){$prefs=array_replace_recursive(SUN_Core::default_preferences(),SUN_Utils::json_decode($row['settings']??'',[]));if(($prefs['email_mode']??'')!==$mode)continue;$user=get_userdata((int)$row['user_id']);if(!$user instanceof WP_User||!is_email($user->user_email))continue;$notifications=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.SUN_DB::table('notifications').' WHERE user_id=%d AND read_at IS NULL AND archived_at IS NULL AND created_at>=%s ORDER BY id DESC LIMIT 50',(int)$user->ID,$since),ARRAY_A);if(!$notifications)continue;$site=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);$subject=sprintf('[%s] Your %s notification summary',$site,$mode);$body='<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto"><h2>Your notification summary</h2><ul>';foreach($notifications as$n){$preview=SUN_Utils::external_preview($n);$body.='<li style="margin:0 0 12px"><strong>'.esc_html($preview['title']).'</strong><br><span>'.esc_html(mb_substr($preview['body'],0,180)).'</span></li>';}$body.='</ul><p><a href="'.esc_url(SUN_Utils::page_url()).'">Open notification center</a></p></div>';$ok=wp_mail($user->user_email,$subject,$body,['Content-Type: text/html; charset=UTF-8']);SUN_Utils::audit($ok?'digest_sent':'digest_failed','user',(int)$user->ID,['mode'=>$mode,'count'=>count($notifications)]);}
    }
}
