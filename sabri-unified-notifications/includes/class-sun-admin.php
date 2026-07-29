<?php
defined('ABSPATH') || exit;

final class SUN_Admin {
    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_sun_complete_repair', [self::class, 'repair']);
        add_action('admin_post_sun_send_test', [self::class, 'send_test']);
        add_action('admin_notices', [self::class, 'notices']);
    }

    public static function menu(): void {
        add_menu_page('Notifications', 'Notifications', 'manage_options', 'sun-notifications', [self::class, 'dashboard'], 'dashicons-bell', 58);
        add_submenu_page('sun-notifications', 'Overview', 'Overview', 'manage_options', 'sun-notifications', [self::class, 'dashboard']);
        add_submenu_page('sun-notifications', 'Settings', 'Settings', 'manage_options', 'sun-settings', [self::class, 'settings']);
        add_submenu_page('sun-notifications', 'Delivery Log', 'Delivery Log', 'manage_options', 'sun-deliveries', [self::class, 'deliveries']);
        add_submenu_page('sun-notifications', 'System Check', 'System Check', 'manage_options', 'sun-system-check', [self::class, 'system_check']);
    }

    public static function register_settings(): void {
        $bools = ['sun_auto_floating_bell','sun_auto_menu_link','sun_email_enabled','sun_sync_marketplace','sun_sync_network','sun_browser_alerts'];
        foreach ($bools as $key) register_setting('sun_settings', $key, ['type'=>'boolean','sanitize_callback'=>static fn($v): int => empty($v) ? 0 : 1,'default'=>0]);
        foreach (['sun_poll_seconds','sun_retention_days','sun_group_window_seconds','sun_max_delivery_attempts','sun_delivery_batch_size'] as $key) {
            register_setting('sun_settings', $key, ['type'=>'integer','sanitize_callback'=>'absint']);
        }
        foreach (['sun_sms_webhook_url','sun_push_webhook_url'] as $key) register_setting('sun_settings', $key, ['type'=>'string','sanitize_callback'=>'esc_url_raw']);
        foreach (['sun_sms_auth_header','sun_push_auth_header'] as $key) register_setting('sun_settings', $key, ['type'=>'string','sanitize_callback'=>'sanitize_text_field']);
        foreach (['sun_sms_payload_template','sun_push_payload_template'] as $key) register_setting('sun_settings', $key, ['type'=>'string','sanitize_callback'=>[self::class,'sanitize_json_template']]);
    }

    public static function sanitize_json_template(mixed $value): string {
        $value = is_string($value) ? trim(wp_unslash($value)) : '';
        if ($value === '') return '{}';
        json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : '{}';
    }

    private static function guard(): void {
        if (!current_user_can('manage_options')) wp_die('You are not allowed to manage notifications.');
    }

    public static function dashboard(): void {
        self::guard();
        global $wpdb;
        $counts = ['notifications'=>0,'unread'=>0,'queued'=>0,'failed'=>0,'devices'=>0];
        if (SUN_DB::table_exists('notifications')) {
            $counts['notifications']=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_DB::table('notifications'));
            $counts['unread']=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_DB::table('notifications').' WHERE read_at IS NULL AND archived_at IS NULL');
        }
        if (SUN_DB::table_exists('deliveries')) {
            $counts['queued']=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SUN_DB::table('deliveries')." WHERE status IN ('queued','retry','waiting_config')");
            $counts['failed']=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SUN_DB::table('deliveries')." WHERE status='failed'");
        }
        if (SUN_DB::table_exists('devices')) $counts['devices']=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.SUN_DB::table('devices').' WHERE enabled=1');
        echo '<div class="wrap"><h1>Sabri Unified Notifications</h1><p>Central alerts for Marketplace, Network, appointments, social activity, security and administration.</p>';
        echo '<div class="sun-admin-cards">';
        foreach (['Notifications'=>'notifications','Unread'=>'unread','Queued deliveries'=>'queued','Failed deliveries'=>'failed','Active devices'=>'devices'] as $label=>$key) {
            echo '<div class="sun-admin-card"><strong>'.esc_html(number_format_i18n($counts[$key])).'</strong><span>'.esc_html($label).'</span></div>';
        }
        echo '</div><p><a class="button button-primary" href="'.esc_url(SUN_Utils::page_url()).'" target="_blank">Open Notification Center</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=sun-system-check')).'">System Check</a></p>';
        self::admin_styles();
        echo '</div>';
    }

    public static function settings(): void {
        self::guard();
        ?>
        <div class="wrap"><h1>Notification Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sun_settings'); ?>
            <table class="form-table" role="presentation">
                <?php self::checkbox_row('Floating notification bell','sun_auto_floating_bell','Show a modern floating bell to signed-in users.'); ?>
                <?php self::checkbox_row('Navigation link','sun_auto_menu_link','Append a Notifications link to WordPress menus.'); ?>
                <?php self::number_row('Polling interval (seconds)','sun_poll_seconds',5,120); ?>
                <?php self::number_row('Retention (days)','sun_retention_days',30,3650); ?>
                <?php self::number_row('Grouping window (seconds)','sun_group_window_seconds',60,86400); ?>
                <?php self::checkbox_row('Email channel','sun_email_enabled','Send important and immediate alerts through WordPress email.'); ?>
                <?php self::checkbox_row('Browser alerts','sun_browser_alerts','Allow browser notifications after the user grants permission.'); ?>
                <?php self::checkbox_row('Sync Marketplace','sun_sync_marketplace','Import Marketplace notifications when its notification table is available.'); ?>
                <?php self::checkbox_row('Sync Network','sun_sync_network','Import Network notifications when its notification table is available.'); ?>
                <?php self::number_row('Maximum delivery attempts','sun_max_delivery_attempts',1,10); ?>
                <?php self::number_row('Delivery batch size','sun_delivery_batch_size',5,200); ?>
                <?php self::text_row('SMS webhook URL','sun_sms_webhook_url','url'); ?>
                <?php self::text_row('SMS authorization header','sun_sms_auth_header','password'); ?>
                <?php self::textarea_row('SMS JSON payload','sun_sms_payload_template'); ?>
                <?php self::text_row('Push webhook URL','sun_push_webhook_url','url'); ?>
                <?php self::text_row('Push authorization header','sun_push_auth_header','password'); ?>
                <?php self::textarea_row('Push JSON payload','sun_push_payload_template'); ?>
            </table>
            <?php submit_button(); ?>
        </form></div>
        <?php
    }

    private static function checkbox_row(string $label,string $key,string $help=''): void { ?>
        <tr><th scope="row"><?php echo esc_html($label); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked((bool)get_option($key)); ?>> Enabled</label><?php if($help) echo '<p class="description">'.esc_html($help).'</p>'; ?></td></tr>
    <?php }
    private static function number_row(string $label,string $key,int $min,int $max): void { ?>
        <tr><th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input class="small-text" type="number" min="<?php echo esc_attr((string)$min); ?>" max="<?php echo esc_attr((string)$max); ?>" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string)get_option($key)); ?>"></td></tr>
    <?php }
    private static function text_row(string $label,string $key,string $type='text'): void { ?>
        <tr><th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string)get_option($key)); ?>" autocomplete="off"></td></tr>
    <?php }
    private static function textarea_row(string $label,string $key): void { ?>
        <tr><th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><textarea class="large-text code" rows="4" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"><?php echo esc_textarea((string)get_option($key)); ?></textarea></td></tr>
    <?php }

    public static function deliveries(): void {
        self::guard(); global $wpdb;
        $rows = SUN_DB::table_exists('deliveries') ? $wpdb->get_results('SELECT * FROM '.SUN_DB::table('deliveries').' ORDER BY id DESC LIMIT 200',ARRAY_A) : [];
        echo '<div class="wrap"><h1>Delivery Log</h1><table class="widefat striped"><thead><tr><th>ID</th><th>Notification</th><th>User</th><th>Channel</th><th>Status</th><th>Attempts</th><th>Error</th><th>Updated</th></tr></thead><tbody>';
        if (!$rows) echo '<tr><td colspan="8">No delivery records.</td></tr>';
        foreach($rows as $r) echo '<tr><td>'.(int)$r['id'].'</td><td>'.(int)$r['notification_id'].'</td><td>'.(int)$r['user_id'].'</td><td>'.esc_html($r['channel']).'</td><td>'.esc_html($r['status']).'</td><td>'.(int)$r['attempts'].'</td><td>'.esc_html(wp_trim_words((string)$r['last_error'],18)).'</td><td>'.esc_html((string)$r['updated_at']).'</td></tr>';
        echo '</tbody></table></div>';
    }

    public static function system_check(): void {
        self::guard(); global $wpdb;
        $checks=[];
        foreach(['notifications','preferences','deliveries','devices','templates','audit_log'] as $t) $checks['Database: '.$t]=SUN_DB::table_exists($t);
        $page=(int)get_option('sun_page_id');
        $checks['Notifications page']=$page>0 && get_post_status($page)==='publish' && has_shortcode((string)get_post_field('post_content',$page),'sabri_notifications');
        $checks['REST API']=rest_url('sabri-notifications/v1/health')!=='';
        $checks['HTTPS']=is_ssl();
        $checks['Delivery cron']=(bool)wp_next_scheduled('sun_process_deliveries');
        $checks['Daily cleanup cron']=(bool)wp_next_scheduled('sun_cleanup_daily');
        $checks['Marketplace integration']=SUN_DB::external_table_exists($wpdb->prefix.'smp_notifications');
        $checks['Network integration']=SUN_DB::external_table_exists($wpdb->prefix.'sn_notifications');
        echo '<div class="wrap"><h1>Notification System Check</h1><table class="widefat striped"><tbody>';
        foreach($checks as $label=>$ok) echo '<tr><td><strong>'.esc_html($label).'</strong></td><td>'.($ok?'<span style="color:#087f23;font-weight:700">Ready</span>':'<span style="color:#b32d2e;font-weight:700">Needs attention</span>').'</td></tr>';
        echo '</tbody></table><p><strong>Public REST health path:</strong> <code>'.esc_html(rest_url('sabri-notifications/v1/health')).'</code></p><p><strong>Safe Mode:</strong> <code>'.esc_html(add_query_arg('sun_notifications_app','1',home_url('/'))).'</code></p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">'; wp_nonce_field('sun_complete_repair'); echo '<input type="hidden" name="action" value="sun_complete_repair">'; submit_button('Complete Repair','primary','submit',false); echo '</form> ';
        echo '<form style="display:inline-block;margin-left:8px" method="post" action="'.esc_url(admin_url('admin-post.php')).'">'; wp_nonce_field('sun_send_test'); echo '<input type="hidden" name="action" value="sun_send_test">'; submit_button('Send Test Notification','secondary','submit',false); echo '</form></div>';
    }

    public static function repair(): void {
        self::guard(); check_admin_referer('sun_complete_repair');
        SUN_DB::install(); SUN_Activator::set_defaults(); SUN_Activator::ensure_page(true);
        foreach(['sun_process_deliveries','sun_cleanup_daily','sun_digest_daily','sun_digest_weekly'] as $hook) wp_clear_scheduled_hook($hook);
        wp_schedule_event(time()+300,'sun_five_minutes','sun_process_deliveries');
        wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','sun_cleanup_daily');
        wp_schedule_event(strtotime('tomorrow 08:00'),'daily','sun_digest_daily');
        wp_schedule_event(strtotime('next sunday 08:30'),'weekly','sun_digest_weekly');
        flush_rewrite_rules(false); SUN_Utils::audit('complete_repair','system',0);
        wp_safe_redirect(add_query_arg('sun_notice','repaired',admin_url('admin.php?page=sun-system-check'))); exit;
    }

    public static function send_test(): void {
        self::guard(); check_admin_referer('sun_send_test');
        $id=SUN_Core::create(['user_id'=>get_current_user_id(),'actor_user_id'=>get_current_user_id(),'category'=>'system','type'=>'system_test','priority'=>'normal','title'=>'Notification system test','body'=>'The unified notification engine is working correctly.','link'=>SUN_Utils::page_url(),'allow_self'=>true,'dedupe_key'=>'admin-test:'.wp_generate_uuid4()]);
        wp_safe_redirect(add_query_arg('sun_notice',$id?'test_sent':'test_failed',admin_url('admin.php?page=sun-system-check'))); exit;
    }

    public static function notices(): void {
        $notice=sanitize_key((string)($_GET['sun_notice']??''));
        $map=['repaired'=>['success','Notification system repaired successfully.'],'test_sent'=>['success','Test notification sent.'],'test_failed'=>['error','Test notification could not be created.']];
        if(!isset($map[$notice])) return; [$class,$message]=$map[$notice];
        echo '<div class="notice notice-'.esc_attr($class).' is-dismissible"><p>'.esc_html($message).'</p></div>';
    }

    private static function admin_styles(): void {
        echo '<style>.sun-admin-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;max-width:1000px;margin:20px 0}.sun-admin-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.04)}.sun-admin-card strong{display:block;font-size:28px;color:#c45500}.sun-admin-card span{display:block;margin-top:5px;color:#50575e}</style>';
    }
}
