<?php
defined('ABSPATH') || exit;

final class SUN_Shortcodes {
    private static bool $assets_enqueued = false;

    public static function init(): void {
        add_shortcode('sabri_notifications', [self::class, 'notifications_shortcode']);
        add_shortcode('sabri_notification_bell', [self::class, 'bell_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_action('wp_footer', [self::class, 'floating_bell'], 30);
        add_filter('wp_nav_menu_items', [self::class, 'menu_link'], 20, 2);
        add_action('template_redirect', [self::class, 'safe_mode']);
    }

    public static function register_assets(): void {
        wp_register_style('sun-notifications', SUN_URL . 'assets/css/sun.css', [], SUN_VERSION);
        wp_register_script('sun-notifications', SUN_URL . 'assets/js/sun.js', [], SUN_VERSION, true);
    }

    private static function enqueue_assets(): void {
        if (self::$assets_enqueued) return;
        self::$assets_enqueued = true;
        wp_enqueue_style('sun-notifications');
        wp_enqueue_script('sun-notifications');
        wp_localize_script('sun-notifications', 'SUN_CONFIG', [
            'restUrl' => esc_url_raw(rest_url('sabri-notifications/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'pageUrl' => SUN_Utils::page_url(),
            'isLoggedIn' => is_user_logged_in(),
            'pollSeconds' => max(5, min(120, (int) get_option('sun_poll_seconds', 8))),
            'browserAlerts' => (bool) get_option('sun_browser_alerts', 1),
            'siteName' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            'categories' => SUN_Utils::allowed_categories(),
            'strings' => [
                'empty' => 'No notifications yet.',
                'error' => 'Notifications could not be loaded. Please try again.',
                'markAllRead' => 'Mark all as read',
                'browserPermission' => 'Enable browser alerts',
                'browserBlocked' => 'Browser alerts are blocked in your browser settings.',
            ],
        ]);
    }

    public static function notifications_shortcode(): string {
        if (!is_user_logged_in()) {
            return '<div class="sun-login-required"><h2>Notifications</h2><p>Please sign in to view your private notifications.</p><a class="sun-primary-button" href="' . esc_url(wp_login_url(SUN_Utils::page_url())) . '">Sign in</a></div>';
        }
        self::enqueue_assets();
        ob_start();
        include SUN_DIR . 'templates/notifications-app.php';
        return (string) ob_get_clean();
    }

    public static function bell_shortcode(): string {
        if (!is_user_logged_in()) return '';
        self::enqueue_assets();
        return self::bell_markup('inline');
    }

    public static function floating_bell(): void {
        if (!is_user_logged_in() || !(bool) get_option('sun_auto_floating_bell', 1) || is_admin()) return;
        self::enqueue_assets();
        echo '<div class="sun-floating-wrap">' . self::bell_markup('floating') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private static function bell_markup(string $mode): string {
        return '<div class="sun-bell" data-sun-bell data-mode="' . esc_attr($mode) . '">
            <button class="sun-bell-button" type="button" aria-label="Open notifications" aria-expanded="false">
                <span class="sun-bell-icon" aria-hidden="true">🔔</span>
                <span class="sun-bell-count" data-sun-count hidden>0</span>
            </button>
            <section class="sun-bell-panel" aria-label="Notifications" hidden>
                <header><div><strong>Notifications</strong><small data-sun-status>Live updates</small></div><button type="button" class="sun-icon-button" data-sun-close aria-label="Close">×</button></header>
                <div class="sun-panel-actions"><button type="button" data-sun-mark-all>Mark all as read</button><a href="' . esc_url(SUN_Utils::page_url()) . '">View all</a></div>
                <div class="sun-mini-list" data-sun-mini-list><div class="sun-loading">Loading…</div></div>
            </section>
        </div>';
    }

    public static function menu_link(string $items, object $args): string {
        if (!is_user_logged_in() || !(bool) get_option('sun_auto_menu_link', 0)) return $items;
        self::enqueue_assets();
        $items .= '<li class="menu-item sun-menu-notifications"><a href="' . esc_url(SUN_Utils::page_url()) . '">Notifications <span data-sun-menu-count></span></a></li>';
        return $items;
    }

    public static function safe_mode(): void {
        if (!isset($_GET['sun_notifications_app']) || (string) $_GET['sun_notifications_app'] !== '1') return;
        status_header(200);
        nocache_headers();
        self::enqueue_assets();
        include SUN_DIR . 'templates/notifications-standalone.php';
        exit;
    }
}
