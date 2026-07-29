<?php
defined('ABSPATH') || exit;

final class SUN_Utils {
    public static function now(): string {
        return current_time('mysql', true);
    }

    public static function json_encode(array $value): string {
        $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : '{}';
    }

    public static function json_decode(mixed $value, array $default = []): array {
        if (is_array($value)) return $value;
        if (!is_string($value) || trim($value) === '') return $default;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function client_ip(): string {
        return substr(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')), 0, 64);
    }

    public static function audit(string $action, string $object_type = '', int $object_id = 0, array $details = []): void {
        global $wpdb;
        if (!SUN_DB::table_exists('audit_log')) return;
        $wpdb->insert(SUN_DB::table('audit_log'), [
            'actor_user_id' => get_current_user_id(),
            'action' => sanitize_key($action),
            'object_type' => sanitize_key($object_type),
            'object_id' => $object_id,
            'details' => self::json_encode($details),
            'ip_address' => self::client_ip(),
            'created_at' => self::now(),
        ], ['%d','%s','%s','%d','%s','%s','%s']);
    }

    public static function page_url(): string {
        $page_id = (int) get_option('sun_page_id', 0);
        if ($page_id && get_post_status($page_id) === 'publish') {
            $url = get_permalink($page_id);
            if ($url) return (string) $url;
        }
        return add_query_arg('sun_notifications_app', '1', home_url('/'));
    }

    public static function allowed_categories(): array {
        return [
            'messages' => 'Messages',
            'marketplace' => 'Marketplace',
            'appointments' => 'Appointments',
            'social' => 'Social',
            'security' => 'Security',
            'administration' => 'Administration',
            'system' => 'System',
        ];
    }

    public static function allowed_priorities(): array {
        return ['low', 'normal', 'high', 'critical'];
    }

    public static function normalize_category(string $category): string {
        $category = sanitize_key($category);
        return array_key_exists($category, self::allowed_categories()) ? $category : 'system';
    }

    public static function normalize_priority(string $priority): string {
        $priority = sanitize_key($priority);
        return in_array($priority, self::allowed_priorities(), true) ? $priority : 'normal';
    }

    public static function sanitize_link(string $link): string {
        if ($link === '') return '';
        $link = esc_url_raw($link);
        return $link ?: '';
    }

    public static function notification_icon(string $category, string $type = ''): string {
        $icons = [
            'messages' => '💬',
            'marketplace' => '🛍️',
            'appointments' => '📅',
            'social' => '👥',
            'security' => '🔐',
            'administration' => '🛡️',
            'system' => '🔔',
        ];
        return (string) apply_filters('sun_notification_icon', $icons[$category] ?? '🔔', $category, $type);
    }

    public static function get_user_phone(int $user_id): string {
        $keys = ['sn_phone', 'smp_buyer_phone', 'billing_phone', 'phone', 'mobile', 'user_phone'];
        foreach ($keys as $key) {
            $value = trim((string) get_user_meta($user_id, $key, true));
            if ($value !== '') return $value;
        }
        global $wpdb;
        $seller_table = $wpdb->prefix . 'smp_sellers';
        if (SUN_DB::external_table_exists($seller_table)) {
            $value = (string) $wpdb->get_var($wpdb->prepare("SELECT phone FROM $seller_table WHERE user_id=%d LIMIT 1", $user_id));
            if (trim($value) !== '') return trim($value);
        }
        return '';
    }

    public static function mask_sensitive_text(string $text): string {
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\b\d{5}-?\d{7}-?\d\b/', '[identity protected]', $text) ?? $text;
        $text = preg_replace('/\b(?:\+?\d[\d\s\-()]{8,}\d)\b/', '[number protected]', $text) ?? $text;
        $text = preg_replace('/\b\d{4,8}\b/', '[code protected]', $text) ?? $text;
        return trim($text);
    }

    public static function format_relative_time(string $utc): string {
        $timestamp = strtotime($utc . ' UTC');
        if (!$timestamp) return '';
        return human_time_diff($timestamp, time()) . ' ago';
    }

    public static function rate_limit(string $key, int $limit, int $window_seconds): bool {
        $key = 'sun_rl_' . md5($key . '|' . self::client_ip());
        $value = get_transient($key);
        $count = is_array($value) ? (int) ($value['count'] ?? 0) : 0;
        if ($count >= $limit) return false;
        set_transient($key, ['count' => $count + 1], $window_seconds);
        return true;
    }
}
