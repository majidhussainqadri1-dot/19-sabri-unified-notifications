<?php
defined('ABSPATH') || exit;

final class SUN_Activator {
    public static function activate(): void {
        SUN_DB::install();
        self::set_defaults();
        self::ensure_page();
        self::seed_templates();
        self::schedule_events();
        update_option('sun_plugin_version', SUN_VERSION, false);
        flush_rewrite_rules(false);
    }

    public static function maybe_upgrade(): void {
        $plugin_version=(string)get_option('sun_plugin_version','0');
        $db_version=(string)get_option('sun_db_version','0');
        if (version_compare($plugin_version,SUN_VERSION,'<') || version_compare($db_version,SUN_DB::DB_VERSION,'<')) {
            SUN_DB::install();
            self::set_defaults();
            self::ensure_page();
            self::seed_templates();
            self::schedule_events();
            update_option('sun_plugin_version',SUN_VERSION,false);
            SUN_Utils::audit('plugin_upgraded','system',0,['from'=>$plugin_version,'to'=>SUN_VERSION,'db_from'=>$db_version,'db_to'=>SUN_DB::DB_VERSION]);
        }
    }

    public static function deactivate(): void {
        foreach(['sun_process_deliveries','sun_cleanup_daily','sun_digest_daily','sun_digest_weekly','sun_sync_sources'] as $hook) wp_clear_scheduled_hook($hook);
        flush_rewrite_rules(false);
    }

    public static function set_defaults(): void {
        $defaults=[
            'sun_auto_floating_bell'=>0,
            'sun_auto_menu_link'=>0,
            'sun_poll_seconds'=>30,
            'sun_retention_days'=>365,
            'sun_group_window_seconds'=>900,
            'sun_email_enabled'=>1,
            'sun_max_delivery_attempts'=>4,
            'sun_delivery_batch_size'=>25,
            'sun_sms_webhook_url'=>'',
            'sun_sms_auth_header'=>'',
            'sun_sms_payload_template'=>'{"to":"{{phone}}","message":"{{message}}","link":"{{link}}"}',
            'sun_push_webhook_url'=>'',
            'sun_push_auth_header'=>'',
            'sun_push_payload_template'=>'{"token":"{{token}}","title":"{{title}}","body":"{{body}}","link":"{{link}}","notification_id":"{{notification_id}}"}',
            'sun_sync_marketplace'=>1,
            'sun_sync_network'=>1,
            'sun_browser_alerts'=>1,
        ];
        foreach($defaults as $key=>$value) if(get_option($key,null)===null) add_option($key,$value,'','no');
    }

    public static function schedule_events(): void {
        SUN_Channels::register_cron_schedules();
        $events=[
            ['sun_process_deliveries',time()+300,'sun_five_minutes'],
            ['sun_cleanup_daily',time()+HOUR_IN_SECONDS,'daily'],
            ['sun_digest_daily',self::next_local_time('08:00'),'daily'],
            ['sun_digest_weekly',self::next_local_weekday('sunday','08:30'),'weekly'],
            ['sun_sync_sources',time()+10*MINUTE_IN_SECONDS,'hourly'],
        ];
        foreach($events as [$hook,$timestamp,$schedule]) if(!wp_next_scheduled($hook)) wp_schedule_event($timestamp,$schedule,$hook);
    }

    private static function next_local_time(string $time): int {
        $timezone=wp_timezone(); $now=new DateTimeImmutable('now',$timezone); $target=new DateTimeImmutable($now->format('Y-m-d').' '.$time,$timezone); if($target<=$now)$target=$target->modify('+1 day'); return $target->getTimestamp();
    }
    private static function next_local_weekday(string $weekday,string $time): int {
        $timezone=wp_timezone(); $now=new DateTimeImmutable('now',$timezone); $target=new DateTimeImmutable('next '.$weekday.' '.$time,$timezone); return $target->getTimestamp();
    }

    public static function ensure_page(): int {
        $page_id=(int)get_option('sun_page_id',0);
        $page=$page_id?get_post($page_id):null;
        if($page instanceof WP_Post && (string)get_post_meta($page->ID,'_sun_owned_page',true)==='1') {
            $content=(string)$page->post_content;
            if(!has_shortcode($content,'sabri_notifications')) {
                $content=rtrim($content)."\n\n[sabri_notifications]";
                wp_update_post(['ID'=>$page->ID,'post_content'=>$content]);
            }
            if(get_post_status($page->ID)!=='publish') wp_update_post(['ID'=>$page->ID,'post_status'=>'publish']);
            return (int)$page->ID;
        }

        $existing=get_page_by_path('notifications',OBJECT,'page');
        if($existing instanceof WP_Post && has_shortcode((string)$existing->post_content,'sabri_notifications')) {
            update_option('sun_page_id',(int)$existing->ID,false);
            return (int)$existing->ID;
        }

        $slug=$existing instanceof WP_Post?'notifications-center':'notifications';
        $id=wp_insert_post(['post_title'=>'Notifications','post_name'=>$slug,'post_content'=>'[sabri_notifications]','post_status'=>'publish','post_type'=>'page','comment_status'=>'closed'],true);
        if(!is_wp_error($id)&&$id){update_post_meta((int)$id,'_sun_owned_page','1');update_option('sun_page_id',(int)$id,false);return (int)$id;}
        return 0;
    }

    private static function seed_templates(): void {
        global $wpdb; if(!SUN_DB::table_exists('templates'))return; $now=SUN_Utils::now();
        $templates=[
            ['message','en_US','in_app','','New message','You received a new private message.'],
            ['marketplace_offer','en_US','in_app','','New marketplace offer','A buyer or seller sent a new offer.'],
            ['appointment_confirmed','en_US','in_app','','Appointment confirmed','Your appointment has been confirmed.'],
            ['security_login','en_US','email','Security alert','New sign-in detected','A new sign-in was detected on your account.'],
            ['delivery_failure','en_US','in_app','','Notification delivery failed','An external notification channel failed.'],
        ];
        foreach($templates as $t){$args=array_merge($t,[$now,$now]);$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.SUN_DB::table('templates').' (event_key,locale,channel,subject,title,body,enabled,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,1,%s,%s)',...$args));}
    }
}
