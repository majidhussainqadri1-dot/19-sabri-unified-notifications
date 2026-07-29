<?php
defined('ABSPATH') || exit;

final class SUN_Shortcodes {
    private static bool $assets_enqueued=false;
    public static function init(): void {
        add_shortcode('sabri_notifications',[self::class,'notifications_shortcode']);
        add_shortcode('sabri_notification_bell',[self::class,'bell_shortcode']);
        add_action('wp_enqueue_scripts',[self::class,'register_assets']);
        add_action('wp_footer',[self::class,'floating_bell'],30);
        add_filter('wp_nav_menu_items',[self::class,'menu_link'],20,2);
        add_action('template_redirect',[self::class,'protect_private_surfaces'],0);
        add_filter('wp_robots',[self::class,'private_robots']);
    }
    public static function register_assets(): void { wp_register_style('sun-notifications',SUN_URL.'assets/css/sun.css',[],SUN_VERSION);wp_register_script('sun-notifications',SUN_URL.'assets/js/sun.js',[],SUN_VERSION,true); }
    private static function enqueue_assets(): void { if(self::$assets_enqueued)return;self::$assets_enqueued=true;wp_enqueue_style('sun-notifications');wp_enqueue_script('sun-notifications');wp_localize_script('sun-notifications','SUN_CONFIG',['restUrl'=>esc_url_raw(rest_url('sabri-notifications/v1/')),'nonce'=>wp_create_nonce('wp_rest'),'pageUrl'=>SUN_Utils::page_url(),'isLoggedIn'=>is_user_logged_in(),'pollSeconds'=>max(15,min(300,(int)get_option('sun_poll_seconds',30))),'browserAlerts'=>(bool)get_option('sun_browser_alerts',1),'siteName'=>wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES),'categories'=>SUN_Utils::allowed_categories(),'strings'=>['empty'=>'No notifications yet.','error'=>'Notifications could not be loaded. Please try again.','browserBlocked'=>'Browser alerts are blocked in your browser settings.']]); }
    public static function notifications_shortcode(): string { if(!is_user_logged_in())return'<div class="sun-login-required"><h2>Notifications</h2><p>Please sign in to view your private notifications.</p><a class="sun-primary-button" href="'.esc_url(wp_login_url(SUN_Utils::page_url())).'">Sign in</a></div>';self::enqueue_assets();ob_start();include SUN_DIR.'templates/notifications-app.php';return(string)ob_get_clean(); }
    public static function bell_shortcode(): string { if(!is_user_logged_in())return'';self::enqueue_assets();return self::bell_markup('inline'); }
    public static function floating_bell(): void { $enabled=(bool)get_option('sun_auto_floating_bell',0);$enabled=(bool)apply_filters('sun_should_render_floating_bell',$enabled);if(!is_user_logged_in()||!$enabled||is_admin())return;self::enqueue_assets();echo'<div class="sun-floating-wrap">'.self::bell_markup('floating').'</div>'; }
    private static function bell_markup(string $mode): string { return'<div class="sun-bell" data-sun-bell data-mode="'.esc_attr($mode).'"><button class="sun-bell-button" type="button" aria-label="Open notifications" aria-expanded="false" aria-haspopup="dialog"><span class="sun-bell-icon" aria-hidden="true">🔔</span><span class="sun-bell-count" data-sun-count hidden>0</span></button><section class="sun-bell-panel" role="dialog" aria-modal="false" aria-label="Notifications" tabindex="-1" hidden><header><div><strong>Notifications</strong><small data-sun-status>Live updates</small></div><button type="button" class="sun-icon-button" data-sun-close aria-label="Close notifications">×</button></header><div class="sun-panel-actions"><button type="button" data-sun-mark-all>Mark all as read</button><a href="'.esc_url(SUN_Utils::page_url()).'">View all</a></div><div class="sun-mini-list" data-sun-mini-list><div class="sun-loading">Loading…</div></div></section></div>'; }
    public static function menu_link(string $items,object $args): string { if(!is_user_logged_in()||!(bool)get_option('sun_auto_menu_link',0))return$items;self::enqueue_assets();return$items.'<li class="menu-item sun-menu-notifications"><a href="'.esc_url(SUN_Utils::page_url()).'">Notifications <span data-sun-menu-count></span></a></li>'; }

    public static function protect_private_surfaces(): void {
        $safe=isset($_GET['sun_notifications_app'])&&(string)$_GET['sun_notifications_app']==='1';$page_id=(int)get_option('sun_page_id',0);$is_page=$page_id>0&&is_page($page_id);if(!$safe&&!$is_page)return;
        nocache_headers();header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',true);header('Pragma: no-cache',true);header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet',true);header('Referrer-Policy: same-origin',true);
        if($safe){status_header(200);self::enqueue_assets();include SUN_DIR.'templates/notifications-standalone.php';exit;}
    }
    public static function private_robots(array $robots): array { $page_id=(int)get_option('sun_page_id',0);if($page_id>0&&is_page($page_id)){$robots['noindex']=true;$robots['nofollow']=true;$robots['noarchive']=true;$robots['nosnippet']=true;}return$robots; }
}
