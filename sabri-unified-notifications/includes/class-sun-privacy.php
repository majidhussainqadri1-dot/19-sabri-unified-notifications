<?php
defined('ABSPATH') || exit;

final class SUN_Privacy {
    public static function init(): void {
        add_filter('wp_privacy_personal_data_exporters',[self::class,'register_exporter']);
        add_filter('wp_privacy_personal_data_erasers',[self::class,'register_eraser']);
    }
    public static function register_exporter(array $exporters): array { $exporters['sabri-unified-notifications']=['exporter_friendly_name'=>'Sabri Unified Notifications','callback'=>[self::class,'export']];return$exporters; }
    public static function register_eraser(array $erasers): array { $erasers['sabri-unified-notifications']=['eraser_friendly_name'=>'Sabri Unified Notifications','callback'=>[self::class,'erase']];return$erasers; }

    public static function export(string $email,int $page=1): array {
        $user=get_user_by('email',$email);if(!$user instanceof WP_User)return['data'=>[],'done'=>true];global$wpdb;$limit=100;$offset=max(0,($page-1)*$limit);$data=[];
        $notifications=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.SUN_DB::table('notifications').' WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d',(int)$user->ID,$limit,$offset),ARRAY_A);
        foreach($notifications?:[]as$n)$data[]=['group_id'=>'sun-notifications','group_label'=>'Notifications','item_id'=>'notification-'.(int)$n['id'],'data'=>[['name'=>'Category','value'=>(string)$n['category']],['name'=>'Type','value'=>(string)$n['type']],['name'=>'Priority','value'=>(string)$n['priority']],['name'=>'Sensitivity','value'=>(string)($n['sensitivity']??'private')],['name'=>'Title','value'=>(string)$n['title']],['name'=>'Body','value'=>(string)$n['body']],['name'=>'Created','value'=>(string)$n['created_at']],['name'=>'Read','value'=>(string)$n['read_at']],['name'=>'Archived','value'=>(string)$n['archived_at']]]];
        if($page===1){$prefs=$wpdb->get_var($wpdb->prepare('SELECT settings FROM '.SUN_DB::table('preferences').' WHERE user_id=%d',(int)$user->ID));if($prefs)$data[]=['group_id'=>'sun-notification-preferences','group_label'=>'Notification Preferences','item_id'=>'preferences-'.(int)$user->ID,'data'=>[['name'=>'Settings','value'=>$prefs]]];$devices=$wpdb->get_results($wpdb->prepare('SELECT id,device_type,device_name,enabled,last_seen_at,created_at FROM '.SUN_DB::table('devices').' WHERE user_id=%d',(int)$user->ID),ARRAY_A);foreach($devices?:[]as$d)$data[]=['group_id'=>'sun-notification-devices','group_label'=>'Notification Devices','item_id'=>'device-'.(int)$d['id'],'data'=>[['name'=>'Type','value'=>(string)$d['device_type']],['name'=>'Name','value'=>(string)$d['device_name']],['name'=>'Enabled','value'=>(string)$d['enabled']],['name'=>'Last seen','value'=>(string)$d['last_seen_at']],['name'=>'Created','value'=>(string)$d['created_at']]]];}
        return['data'=>$data,'done'=>count($notifications)<$limit];
    }

    public static function erase(string $email,int $page=1): array {
        $user=get_user_by('email',$email);if(!$user instanceof WP_User)return['items_removed'=>false,'items_retained'=>false,'messages'=>[],'done'=>true];global$wpdb;$user_id=(int)$user->ID;$removed=false;
        if($page===1){$wpdb->delete(SUN_DB::table('preferences'),['user_id'=>$user_id],['%d']);$wpdb->delete(SUN_DB::table('devices'),['user_id'=>$user_id],['%d']);$wpdb->delete(SUN_DB::table('deliveries'),['user_id'=>$user_id],['%d']);$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('audit_log')." SET actor_user_id=0,ip_address='',details='{}' WHERE actor_user_id=%d",$user_id));delete_user_meta($user_id,'_sun_smp_last_id');delete_user_meta($user_id,'_sun_sn_last_id');delete_user_meta($user_id,'_sun_known_login_devices');$removed=true;}
        $ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.SUN_DB::table('notifications').' WHERE user_id=%d LIMIT 200',$user_id));if($ids){$ph=implode(',',array_fill(0,count($ids),'%d'));$wpdb->query($wpdb->prepare('DELETE FROM '.SUN_DB::table('deliveries')." WHERE notification_id IN ($ph)",...array_map('intval',$ids)));$wpdb->query($wpdb->prepare('DELETE FROM '.SUN_DB::table('notifications')." WHERE id IN ($ph)",...array_map('intval',$ids)));$removed=true;}
        return['items_removed'=>$removed,'items_retained'=>false,'messages'=>[],'done'=>!$ids];
    }
}
