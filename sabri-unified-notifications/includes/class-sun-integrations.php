<?php
defined('ABSPATH') || exit;

final class SUN_Integrations {
    public static function init(): void {
        add_action('comment_post', [self::class, 'comment_posted'], 10, 3);
        add_action('transition_comment_status', [self::class, 'comment_status_changed'], 10, 3);
        add_action('wp_login', [self::class, 'login_alert'], 20, 2);
        add_action('after_password_reset', [self::class, 'password_changed'], 10, 2);
        add_action('profile_update', [self::class, 'profile_updated'], 10, 3);
        add_action('sun_sync_sources', [self::class, 'sync_all_recent']);
    }

    public static function sync_current_user(int $user_id): void {
        if ($user_id <= 0) return;
        if ((bool) get_option('sun_sync_marketplace', 1)) self::sync_marketplace($user_id);
        if ((bool) get_option('sun_sync_network', 1)) self::sync_network($user_id);
    }

    private static function sync_marketplace(int $user_id): void {
        global $wpdb;
        $table = $wpdb->prefix . 'smp_notifications';
        if (!SUN_DB::external_table_exists($table)) return;
        $last = (int) get_user_meta($user_id, '_sun_smp_last_id', true);
        if ($last > 0) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d AND id>%d ORDER BY id ASC LIMIT 200", $user_id, $last), ARRAY_A);
        } else {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM (SELECT * FROM $table WHERE user_id=%d ORDER BY id DESC LIMIT 100) recent ORDER BY id ASC", $user_id), ARRAY_A);
        }
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            SUN_Core::create([
                'user_id' => $user_id,
                'category' => 'marketplace',
                'type' => (string) ($row['type'] ?? 'marketplace'),
                'priority' => self::source_priority((string) ($row['type'] ?? '')),
                'title' => (string) ($row['title'] ?? 'Marketplace update'),
                'body' => (string) ($row['body'] ?? ''),
                'link' => (string) ($row['link'] ?? ''),
                'source' => 'marketplace',
                'source_id' => $id,
                'allow_self' => true,
            ]);
            if (!empty($row['is_read'])) self::mark_central_source_read('marketplace', $id, $user_id);
            $last = max($last, $id);
        }
        if ($last > 0) update_user_meta($user_id, '_sun_smp_last_id', $last);
    }

    private static function sync_network(int $user_id): void {
        global $wpdb;
        $table = $wpdb->prefix . 'sn_notifications';
        if (!SUN_DB::external_table_exists($table)) return;
        $last = (int) get_user_meta($user_id, '_sun_sn_last_id', true);
        if ($last > 0) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d AND id>%d ORDER BY id ASC LIMIT 200", $user_id, $last), ARRAY_A);
        } else {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM (SELECT * FROM $table WHERE user_id=%d ORDER BY id DESC LIMIT 100) recent ORDER BY id ASC", $user_id), ARRAY_A);
        }
        $network_url = class_exists('SN_Activator') ? SN_Activator::network_url() : home_url('/network/');
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $entity_type = sanitize_key((string) ($row['entity_type'] ?? ''));
            $entity_id = (int) ($row['entity_id'] ?? 0);
            $link = $network_url;
            if ($entity_type && $entity_id) $link = add_query_arg([$entity_type => $entity_id], $network_url);
            SUN_Core::create([
                'user_id' => $user_id,
                'category' => SUN_Core::category_from_type((string) ($row['type'] ?? 'message')),
                'type' => (string) ($row['type'] ?? 'message'),
                'priority' => self::source_priority((string) ($row['type'] ?? 'message')),
                'title' => (string) ($row['title'] ?? 'Network update'),
                'body' => (string) ($row['body'] ?? ''),
                'link' => $link,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'source' => 'network',
                'source_id' => $id,
                'allow_self' => true,
            ]);
            if (!empty($row['is_read'])) self::mark_central_source_read('network', $id, $user_id);
            $last = max($last, $id);
        }
        if ($last > 0) update_user_meta($user_id, '_sun_sn_last_id', $last);
    }

    private static function source_priority(string $type): string {
        $type = sanitize_key($type);
        if (str_contains($type, 'security') || str_contains($type, 'suspend')) return 'critical';
        if (str_contains($type, 'approval') || str_contains($type, 'report') || str_contains($type, 'appointment')) return 'high';
        return 'normal';
    }

    public static function propagate_read(array $source_rows): void {
        global $wpdb;
        foreach ($source_rows as $row) {
            $source = (string) ($row['source'] ?? '');
            $source_id = (int) ($row['source_id'] ?? 0);
            if ($source_id <= 0) continue;
            if ($source === 'marketplace') {
                $table = $wpdb->prefix . 'smp_notifications';
                if (SUN_DB::external_table_exists($table)) $wpdb->update($table, ['is_read'=>1], ['id'=>$source_id], ['%d'], ['%d']);
            } elseif ($source === 'network') {
                $table = $wpdb->prefix . 'sn_notifications';
                if (SUN_DB::external_table_exists($table)) $wpdb->update($table, ['is_read'=>1], ['id'=>$source_id], ['%d'], ['%d']);
            }
        }
    }

    private static function mark_central_source_read(string $source, int $source_id, int $user_id): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            'UPDATE ' . SUN_DB::table('notifications') . ' SET read_at=COALESCE(read_at,%s),seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE source=%s AND source_id=%d AND user_id=%d',
            SUN_Utils::now(), SUN_Utils::now(), SUN_Utils::now(), $source, $source_id, $user_id
        ));
    }

    public static function comment_posted(int $comment_id, int|string $approved, array $commentdata): void {
        if ((string) $approved !== '1') return;
        $comment = get_comment($comment_id);
        if ($comment instanceof WP_Comment) self::notify_approved_comment($comment_id, $comment);
    }

    public static function comment_status_changed(string $new_status, string $old_status, WP_Comment $comment): void {
        if ($new_status !== 'approved' || $old_status === 'approved') return;
        self::notify_approved_comment((int) $comment->comment_ID, $comment);
    }

    private static function notify_approved_comment(int $comment_id, WP_Comment $comment): void {
        $post = get_post((int) $comment->comment_post_ID);
        if (!$post instanceof WP_Post || (int) $post->post_author <= 0 || (int) $post->post_author === (int) $comment->user_id) return;
        $commenter = $comment->user_id ? get_userdata((int) $comment->user_id) : null;
        $name = $commenter instanceof WP_User ? $commenter->display_name : sanitize_text_field((string) $comment->comment_author);
        SUN_Core::create([
            'user_id' => (int) $post->post_author,
            'actor_user_id' => (int) $comment->user_id,
            'category' => 'social',
            'type' => 'comment',
            'priority' => 'normal',
            'sensitivity' => 'private',
            'title' => $name . ' commented on your post',
            'body' => wp_trim_words(wp_strip_all_tags((string) $comment->comment_content), 22),
            'external_title' => 'New comment',
            'external_body' => 'Sign in to view the approved comment on your post.',
            'link' => get_comment_link($comment),
            'entity_type' => 'comment',
            'entity_id' => $comment_id,
            'source' => 'wordpress',
            'source_id' => $comment_id,
            'group_key' => 'post-comments:' . (int) $post->ID,
        ]);
    }

    public static function login_alert(string $user_login, WP_User $user): void {
        $agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown browser'));
        $ip = SUN_Utils::client_ip();
        $fingerprint = hash('sha256', $agent . '|' . $ip);
        $known = (array) get_user_meta((int) $user->ID, '_sun_known_login_devices', true);
        if (in_array($fingerprint, $known, true)) return;
        $known[] = $fingerprint;
        $known = array_slice(array_values(array_unique($known)), -20);
        update_user_meta((int) $user->ID, '_sun_known_login_devices', $known);
        SUN_Core::create([
            'user_id' => (int) $user->ID,
            'actor_user_id' => (int) $user->ID,
            'category' => 'security',
            'type' => 'new_device_login',
            'priority' => 'high',
            'title' => 'New sign-in detected',
            'body' => 'A new browser or network signed in to your account. Review your account security if this was not you.',
            'link' => SUN_Utils::page_url(),
            'context' => ['browser'=>mb_substr($agent,0,190),'ip'=>$ip],
            'dedupe_key' => 'new-device:' . $fingerprint,
            'allow_self' => true,
        ]);
    }

    public static function password_changed(WP_User $user, string $new_pass): void {
        SUN_Core::create([
            'user_id'=>(int)$user->ID,'actor_user_id'=>(int)$user->ID,'category'=>'security','type'=>'password_changed','priority'=>'critical',
            'title'=>'Password changed','body'=>'Your account password was changed. Secure the account immediately if you did not make this change.',
            'link'=>SUN_Utils::page_url(),'dedupe_key'=>'password-changed:' . time(),'allow_self'=>true,
        ]);
    }

    public static function profile_updated(int $user_id, WP_User $old_user_data, array $userdata): void {
        $new = get_userdata($user_id);
        if (!$new instanceof WP_User) return;
        if ((string) $old_user_data->user_email !== (string) $new->user_email) {
            SUN_Core::create([
                'user_id'=>$user_id,'actor_user_id'=>$user_id,'category'=>'security','type'=>'email_changed','priority'=>'critical',
                'title'=>'Email address changed','body'=>'The email address connected to your account was changed.',
                'link'=>SUN_Utils::page_url(),'dedupe_key'=>'email-changed:' . time(),'allow_self'=>true,
            ]);
        }
    }

    public static function sync_all_recent(): void {
        $users = get_users(['fields'=>'ID','number'=>500,'orderby'=>'ID','order'=>'DESC']);
        foreach ($users as $user_id) self::sync_current_user((int) $user_id);
    }
}
