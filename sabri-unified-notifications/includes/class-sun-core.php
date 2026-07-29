<?php
defined('ABSPATH') || exit;

final class SUN_Core {
    public static function init(): void {
        add_action('sabri_notify', [self::class, 'action_notify'], 10, 1);
        add_action('sun_notify', [self::class, 'action_notify'], 10, 1);
        add_action('sun_process_deliveries', ['SUN_Channels', 'process_queue']);
        add_action('sun_cleanup_daily', [SUN_DB::class, 'cleanup']);
        add_action('sun_digest_daily', ['SUN_Channels', 'send_daily_digests']);
        add_action('sun_digest_weekly', ['SUN_Channels', 'send_weekly_digests']);
    }

    public static function action_notify(mixed $args): void {
        if (is_array($args)) self::create($args);
    }

    public static function default_preferences(): array {
        return [
            'categories' => array_fill_keys(array_keys(SUN_Utils::allowed_categories()), true),
            'browser_enabled' => true,
            'sound_enabled' => true,
            'email_mode' => 'important', // off, important, immediate, daily, weekly
            'sms_mode' => 'critical', // off, critical
            'push_mode' => 'important', // off, important, all
            'quiet_enabled' => false,
            'quiet_start' => '22:00',
            'quiet_end' => '07:00',
            'do_not_disturb' => false,
            'muted_types' => [],
            'muted_entities' => [],
        ];
    }

    public static function get_preferences(int $user_id): array {
        global $wpdb;
        $defaults = self::default_preferences();
        if ($user_id <= 0 || !SUN_DB::table_exists('preferences')) return $defaults;
        $raw = $wpdb->get_var($wpdb->prepare('SELECT settings FROM ' . SUN_DB::table('preferences') . ' WHERE user_id=%d', $user_id));
        $saved = SUN_Utils::json_decode($raw, []);
        $prefs = array_replace_recursive($defaults, $saved);
        foreach (array_keys(SUN_Utils::allowed_categories()) as $category) {
            $prefs['categories'][$category] = !empty($prefs['categories'][$category]);
        }
        return $prefs;
    }

    public static function save_preferences(int $user_id, array $input): array {
        global $wpdb;
        $current = self::get_preferences($user_id);
        $categories = [];
        foreach (array_keys(SUN_Utils::allowed_categories()) as $category) {
            $categories[$category] = isset($input['categories'][$category])
                ? (bool) $input['categories'][$category]
                : (bool) ($current['categories'][$category] ?? true);
        }
        // Security and system notices remain available in-app even when optional categories are muted.
        $prefs = [
            'categories' => $categories,
            'browser_enabled' => isset($input['browser_enabled']) ? (bool) $input['browser_enabled'] : (bool) $current['browser_enabled'],
            'sound_enabled' => isset($input['sound_enabled']) ? (bool) $input['sound_enabled'] : (bool) $current['sound_enabled'],
            'email_mode' => in_array(($input['email_mode'] ?? $current['email_mode']), ['off','important','immediate','daily','weekly'], true) ? (string) ($input['email_mode'] ?? $current['email_mode']) : 'important',
            'sms_mode' => in_array(($input['sms_mode'] ?? $current['sms_mode']), ['off','critical'], true) ? (string) ($input['sms_mode'] ?? $current['sms_mode']) : 'critical',
            'push_mode' => in_array(($input['push_mode'] ?? $current['push_mode']), ['off','important','all'], true) ? (string) ($input['push_mode'] ?? $current['push_mode']) : 'important',
            'quiet_enabled' => isset($input['quiet_enabled']) ? (bool) $input['quiet_enabled'] : (bool) $current['quiet_enabled'],
            'quiet_start' => self::sanitize_time((string) ($input['quiet_start'] ?? $current['quiet_start'])),
            'quiet_end' => self::sanitize_time((string) ($input['quiet_end'] ?? $current['quiet_end'])),
            'do_not_disturb' => isset($input['do_not_disturb']) ? (bool) $input['do_not_disturb'] : (bool) $current['do_not_disturb'],
            'muted_types' => array_values(array_unique(array_map('sanitize_key', (array) ($input['muted_types'] ?? $current['muted_types'])))),
            'muted_entities' => array_values(array_unique(array_map('sanitize_text_field', (array) ($input['muted_entities'] ?? $current['muted_entities'])))),
        ];
        $now = SUN_Utils::now();
        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . SUN_DB::table('preferences') . ' (user_id,settings,created_at,updated_at) VALUES (%d,%s,%s,%s) ON DUPLICATE KEY UPDATE settings=VALUES(settings),updated_at=VALUES(updated_at)',
            $user_id,
            SUN_Utils::json_encode($prefs),
            $now,
            $now
        ));
        SUN_Utils::audit('preferences_updated', 'user', $user_id);
        return $prefs;
    }

    private static function sanitize_time(string $time): string {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : '22:00';
    }

    public static function is_quiet_now(array $prefs): bool {
        if (empty($prefs['quiet_enabled'])) return false;
        $start = (string) ($prefs['quiet_start'] ?? '22:00');
        $end = (string) ($prefs['quiet_end'] ?? '07:00');
        $now = wp_date('H:i');
        if ($start === $end) return true;
        if ($start < $end) return $now >= $start && $now < $end;
        return $now >= $start || $now < $end;
    }

    public static function create(array $args): int {
        global $wpdb;
        if (!SUN_DB::table_exists('notifications')) return 0;

        $user_id = absint($args['user_id'] ?? 0);
        if ($user_id <= 0 || !get_userdata($user_id)) return 0;

        $category = SUN_Utils::normalize_category((string) ($args['category'] ?? self::category_from_type((string) ($args['type'] ?? ''))));
        $type = sanitize_key((string) ($args['type'] ?? 'general')) ?: 'general';
        $priority = SUN_Utils::normalize_priority((string) ($args['priority'] ?? self::default_priority($category, $type)));
        $title = sanitize_text_field((string) ($args['title'] ?? 'Notification'));
        $body = sanitize_textarea_field((string) ($args['body'] ?? ''));
        $link = SUN_Utils::sanitize_link((string) ($args['link'] ?? ''));
        $image_url = esc_url_raw((string) ($args['image_url'] ?? ''));
        $entity_type = sanitize_key((string) ($args['entity_type'] ?? ''));
        $entity_id = absint($args['entity_id'] ?? 0);
        $source = sanitize_key((string) ($args['source'] ?? 'sabri')) ?: 'sabri';
        $source_id = absint($args['source_id'] ?? 0);
        $actor_user_id = absint($args['actor_user_id'] ?? 0);
        $group_key = sanitize_text_field((string) ($args['group_key'] ?? ''));
        $context = is_array($args['context'] ?? null) ? $args['context'] : [];
        $expires_at = self::sanitize_datetime((string) ($args['expires_at'] ?? ''));
        $now = SUN_Utils::now();

        if ($actor_user_id === $user_id && empty($args['allow_self'])) return 0;

        $dedupe_key = isset($args['dedupe_key']) ? sanitize_text_field((string) $args['dedupe_key']) : '';
        if ($dedupe_key === '' && $source_id > 0) {
            $dedupe_key = substr(hash('sha256', $source . '|' . $source_id . '|' . $user_id), 0, 64);
        } elseif ($dedupe_key !== '') {
            $dedupe_key = substr(hash('sha256', $dedupe_key . '|' . $user_id), 0, 64);
        }

        if ($dedupe_key !== '') {
            $existing = (int) $wpdb->get_var($wpdb->prepare(
                'SELECT id FROM ' . SUN_DB::table('notifications') . ' WHERE dedupe_key=%s LIMIT 1',
                $dedupe_key
            ));
            if ($existing) return $existing;
        }

        $prefs = self::get_preferences($user_id);
        $mandatory = in_array($category, ['security','administration','system'], true) && in_array($priority, ['high','critical'], true);
        if (!$mandatory && empty($prefs['categories'][$category])) return 0;
        if (!$mandatory && in_array($type, (array) $prefs['muted_types'], true)) return 0;
        if (!$mandatory && $entity_type && $entity_id && in_array($entity_type . ':' . $entity_id, (array) $prefs['muted_entities'], true)) return 0;

        $should_group = !empty($args['group_similar']) || ($category === 'social' && $group_key !== '');
        if ($should_group && $group_key !== '') {
            $window = max(60, (int) get_option('sun_group_window_seconds', 900));
            $cutoff = gmdate('Y-m-d H:i:s', time() - $window);
            $existing_row = $wpdb->get_row($wpdb->prepare(
                'SELECT id,group_count FROM ' . SUN_DB::table('notifications') . ' WHERE user_id=%d AND group_key=%s AND read_at IS NULL AND archived_at IS NULL AND created_at>=%s ORDER BY id DESC LIMIT 1',
                $user_id,
                $group_key,
                $cutoff
            ), ARRAY_A);
            if (is_array($existing_row)) {
                $wpdb->update(SUN_DB::table('notifications'), [
                    'actor_user_id' => $actor_user_id,
                    'title' => $title,
                    'body' => $body,
                    'link' => $link,
                    'image_url' => $image_url,
                    'group_count' => ((int) $existing_row['group_count']) + 1,
                    'context' => SUN_Utils::json_encode($context),
                    'updated_at' => $now,
                ], ['id' => (int) $existing_row['id']], ['%d','%s','%s','%s','%s','%d','%s','%s'], ['%d']);
                return (int) $existing_row['id'];
            }
        }

        $inserted = $wpdb->insert(SUN_DB::table('notifications'), [
            'user_id' => $user_id,
            'actor_user_id' => $actor_user_id,
            'category' => $category,
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'image_url' => $image_url,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'context' => SUN_Utils::json_encode($context),
            'source' => $source,
            'source_id' => $source_id,
            'dedupe_key' => $dedupe_key !== '' ? $dedupe_key : null,
            'group_key' => $group_key,
            'group_count' => 1,
            'expires_at' => $expires_at ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%d','%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%d','%s','%s','%s','%d','%s','%s']);

        if (!$inserted) return 0;
        $notification_id = (int) $wpdb->insert_id;
        self::queue_channels($notification_id, $user_id, $category, $priority, $prefs, $mandatory);
        do_action('sun_notification_created', $notification_id, $args);
        return $notification_id;
    }

    private static function queue_channels(int $notification_id, int $user_id, string $category, string $priority, array $prefs, bool $mandatory): void {
        $channels = [];
        $email_mode = (string) ($prefs['email_mode'] ?? 'important');
        if ($mandatory || $email_mode === 'immediate' || ($email_mode === 'important' && in_array($priority, ['high','critical'], true))) {
            $channels[] = 'email';
        }
        $sms_mode = (string) ($prefs['sms_mode'] ?? 'critical');
        if (($mandatory || $sms_mode === 'critical') && $priority === 'critical') {
            $channels[] = 'sms';
        }
        $push_mode = (string) ($prefs['push_mode'] ?? 'important');
        if ($push_mode === 'all' || ($push_mode === 'important' && in_array($priority, ['high','critical'], true)) || ($mandatory && $priority === 'critical')) {
            $channels[] = 'push';
        }
        if (!empty($prefs['do_not_disturb']) && !$mandatory) $channels = [];
        if (self::is_quiet_now($prefs) && !$mandatory) {
            $channels = array_values(array_diff($channels, ['sms','push']));
        }
        foreach (array_unique($channels) as $channel) {
            SUN_Channels::queue($notification_id, $user_id, $channel);
        }
    }

    public static function format_notification(array $row): array {
        $actor = !empty($row['actor_user_id']) ? get_userdata((int) $row['actor_user_id']) : null;
        return [
            'id' => (int) $row['id'],
            'category' => (string) $row['category'],
            'type' => (string) $row['type'],
            'priority' => (string) $row['priority'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'link' => (string) $row['link'],
            'imageUrl' => (string) $row['image_url'],
            'icon' => SUN_Utils::notification_icon((string) $row['category'], (string) $row['type']),
            'entityType' => (string) $row['entity_type'],
            'entityId' => (int) $row['entity_id'],
            'context' => SUN_Utils::json_decode($row['context'] ?? '', []),
            'groupCount' => max(1, (int) $row['group_count']),
            'isSeen' => !empty($row['seen_at']),
            'isRead' => !empty($row['read_at']),
            'createdAt' => (string) $row['created_at'],
            'relativeTime' => SUN_Utils::format_relative_time((string) $row['created_at']),
            'actor' => $actor instanceof WP_User ? [
                'id' => (int) $actor->ID,
                'name' => (string) $actor->display_name,
                'avatar' => get_avatar_url($actor->ID, ['size' => 96]),
            ] : null,
        ];
    }

    public static function category_from_type(string $type): string {
        $type = sanitize_key($type);
        if (str_contains($type, 'message') || str_contains($type, 'conversation') || str_contains($type, 'call')) return 'messages';
        if (str_contains($type, 'market') || str_contains($type, 'seller') || str_contains($type, 'listing') || str_contains($type, 'offer') || str_contains($type, 'product')) return 'marketplace';
        if (str_contains($type, 'appointment') || str_contains($type, 'clinic') || str_contains($type, 'prescription')) return 'appointments';
        if (str_contains($type, 'comment') || str_contains($type, 'like') || str_contains($type, 'follow') || str_contains($type, 'mention') || str_contains($type, 'post')) return 'social';
        if (str_contains($type, 'login') || str_contains($type, 'password') || str_contains($type, 'security') || str_contains($type, 'otp') || str_contains($type, 'account')) return 'security';
        if (str_contains($type, 'approval') || str_contains($type, 'report') || str_contains($type, 'moderation') || str_contains($type, 'admin')) return 'administration';
        return 'system';
    }

    public static function default_priority(string $category, string $type): string {
        if ($category === 'security') return str_contains($type, 'login') ? 'high' : 'critical';
        if (in_array($category, ['appointments','administration'], true)) return 'high';
        if ($category === 'social') return 'low';
        return 'normal';
    }

    private static function sanitize_datetime(string $value): string {
        if ($value === '') return '';
        $timestamp = strtotime($value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : '';
    }
}
