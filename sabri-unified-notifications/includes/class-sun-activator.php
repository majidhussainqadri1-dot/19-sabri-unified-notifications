<?php
defined('ABSPATH') || exit;

final class SUN_Activator {
    public static function activate(): void {
        SUN_DB::install();
        self::set_defaults();
        self::ensure_page(true);
        self::seed_templates();
        if (!wp_next_scheduled('sun_process_deliveries')) wp_schedule_event(time() + 300, 'sun_five_minutes', 'sun_process_deliveries');
        if (!wp_next_scheduled('sun_cleanup_daily')) wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'sun_cleanup_daily');
        if (!wp_next_scheduled('sun_digest_daily')) wp_schedule_event(strtotime('tomorrow 08:00'), 'daily', 'sun_digest_daily');
        if (!wp_next_scheduled('sun_digest_weekly')) wp_schedule_event(strtotime('next sunday 08:30'), 'weekly', 'sun_digest_weekly');
        update_option('sun_plugin_version', SUN_VERSION);
        flush_rewrite_rules(false);
    }

    public static function deactivate(): void {
        foreach (['sun_process_deliveries','sun_cleanup_daily','sun_digest_daily','sun_digest_weekly'] as $hook) {
            $timestamp = wp_next_scheduled($hook);
            while ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
                $timestamp = wp_next_scheduled($hook);
            }
        }
        flush_rewrite_rules(false);
    }

    public static function set_defaults(): void {
        $defaults = [
            'sun_auto_floating_bell' => 1,
            'sun_auto_menu_link' => 0,
            'sun_poll_seconds' => 8,
            'sun_retention_days' => 365,
            'sun_group_window_seconds' => 900,
            'sun_email_enabled' => 1,
            'sun_max_delivery_attempts' => 4,
            'sun_delivery_batch_size' => 25,
            'sun_sms_webhook_url' => '',
            'sun_sms_auth_header' => '',
            'sun_sms_payload_template' => '{"to":"{{phone}}","message":"{{message}}","link":"{{link}}"}',
            'sun_push_webhook_url' => '',
            'sun_push_auth_header' => '',
            'sun_push_payload_template' => '{"token":"{{token}}","title":"{{title}}","body":"{{body}}","link":"{{link}}","notification_id":"{{notification_id}}"}',
            'sun_sync_marketplace' => 1,
            'sun_sync_network' => 1,
            'sun_browser_alerts' => 1,
        ];
        foreach ($defaults as $key => $value) {
            if (get_option($key, null) === null) add_option($key, $value);
        }
    }

    public static function ensure_page(bool $force_content = false): int {
        $page_id = (int) get_option('sun_page_id', 0);
        $page = $page_id ? get_post($page_id) : null;
        if (!$page instanceof WP_Post) $page = get_page_by_path('notifications', OBJECT, 'page');
        if (!$page) {
            $trashed = get_posts(['post_type'=>'page','name'=>'notifications','post_status'=>'trash','numberposts'=>1]);
            if ($trashed) {
                wp_untrash_post($trashed[0]->ID);
                $page = get_post($trashed[0]->ID);
            }
        }
        if ($page instanceof WP_Post) {
            $update = ['ID'=>$page->ID,'post_status'=>'publish','comment_status'=>'closed'];
            if ($force_content || !has_shortcode((string) $page->post_content, 'sabri_notifications')) $update['post_content'] = '[sabri_notifications]';
            wp_update_post($update);
            update_option('sun_page_id', (int) $page->ID);
            return (int) $page->ID;
        }
        $id = wp_insert_post([
            'post_title'=>'Notifications',
            'post_name'=>'notifications',
            'post_content'=>'[sabri_notifications]',
            'post_status'=>'publish',
            'post_type'=>'page',
            'comment_status'=>'closed',
        ], true);
        if (!is_wp_error($id) && $id) {
            update_option('sun_page_id', (int) $id);
            return (int) $id;
        }
        return 0;
    }

    private static function seed_templates(): void {
        global $wpdb;
        if (!SUN_DB::table_exists('templates')) return;
        $now = SUN_Utils::now();
        $templates = [
            ['message','en_US','in_app','','New message','You received a new private message.'],
            ['marketplace_offer','en_US','in_app','','New marketplace offer','A buyer or seller sent a new offer.'],
            ['appointment_confirmed','en_US','in_app','','Appointment confirmed','Your appointment has been confirmed.'],
            ['security_login','en_US','email','Security alert','New sign-in detected','A new sign-in was detected on your account.'],
            ['delivery_failure','en_US','in_app','','Notification delivery failed','An external notification channel failed.'],
        ];
        foreach ($templates as $t) {
            $args = array_merge($t, [$now, $now]);
            $wpdb->query($wpdb->prepare(
                'INSERT IGNORE INTO ' . SUN_DB::table('templates') . ' (event_key,locale,channel,subject,title,body,enabled,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,1,%s,%s)',
                ...$args
            ));
        }
    }
}
