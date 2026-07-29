<?php
defined('ABSPATH') || exit;

final class SUN_DB {
    public const DB_VERSION = '1.1.0';

    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'sun_' . $name;
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $sql = [];
        $sql[] = 'CREATE TABLE ' . self::table('notifications') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            category varchar(40) NOT NULL DEFAULT 'system',
            type varchar(80) NOT NULL DEFAULT 'general',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            sensitivity varchar(20) NOT NULL DEFAULT 'private',
            title varchar(255) NOT NULL DEFAULT '',
            body text NULL,
            external_title varchar(255) NOT NULL DEFAULT '',
            external_body text NULL,
            link text NULL,
            image_url text NULL,
            entity_type varchar(60) NOT NULL DEFAULT '',
            entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
            context longtext NULL,
            source varchar(60) NOT NULL DEFAULT 'sabri',
            source_id bigint(20) unsigned NOT NULL DEFAULT 0,
            dedupe_key varchar(191) NULL DEFAULT NULL,
            group_key varchar(191) NOT NULL DEFAULT '',
            group_count int(10) unsigned NOT NULL DEFAULT 1,
            seen_at datetime NULL,
            read_at datetime NULL,
            archived_at datetime NULL,
            expires_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY dedupe_key (dedupe_key),
            KEY user_read (user_id,read_at),
            KEY user_archive (user_id,archived_at),
            KEY user_created (user_id,created_at),
            KEY user_category (user_id,category),
            KEY source_ref (source,source_id,user_id),
            KEY priority (priority),
            KEY sensitivity (sensitivity),
            KEY expires_at (expires_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table('preferences') . " (
            user_id bigint(20) unsigned NOT NULL,
            settings longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (user_id)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table('deliveries') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            channel varchar(30) NOT NULL,
            device_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(30) NOT NULL DEFAULT 'queued',
            attempts smallint(5) unsigned NOT NULL DEFAULT 0,
            next_attempt_at datetime NULL,
            lease_token char(36) NULL,
            lease_expires_at datetime NULL,
            provider_response longtext NULL,
            last_error text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            sent_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY notification_channel_device (notification_id,channel,device_id),
            KEY queue (status,next_attempt_at),
            KEY lease (status,lease_expires_at),
            KEY user_id (user_id),
            KEY channel (channel)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table('devices') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            device_type varchar(30) NOT NULL DEFAULT 'web',
            device_name varchar(190) NOT NULL DEFAULT '',
            token longtext NOT NULL,
            token_hash char(64) NOT NULL,
            endpoint text NULL,
            metadata longtext NULL,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            last_seen_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY user_enabled (user_id,enabled)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table('templates') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_key varchar(100) NOT NULL,
            locale varchar(20) NOT NULL DEFAULT 'en_US',
            channel varchar(30) NOT NULL DEFAULT 'in_app',
            subject varchar(255) NOT NULL DEFAULT '',
            title text NULL,
            body longtext NULL,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_locale_channel (event_key,locale,channel),
            KEY enabled (enabled)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . self::table('audit_log') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(80) NOT NULL,
            object_type varchar(50) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            details longtext NULL,
            ip_address varchar(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY actor_user (actor_user_id),
            KEY action (action),
            KEY object_ref (object_type,object_id),
            KEY created_at (created_at)
        ) $charset;";

        foreach ($sql as $statement) dbDelta($statement);
        self::migrate_legacy_data();
        update_option('sun_db_version', self::DB_VERSION, false);
    }

    private static function migrate_legacy_data(): void {
        global $wpdb;
        if (self::table_exists('deliveries')) {
            $indexes = $wpdb->get_results('SHOW INDEX FROM ' . self::table('deliveries'), ARRAY_A);
            foreach ($indexes ?: [] as $index) {
                if (($index['Key_name'] ?? '') === 'notification_channel') {
                    $wpdb->query('ALTER TABLE ' . self::table('deliveries') . ' DROP INDEX notification_channel');
                    break;
                }
            }
        }
        foreach (['sun_sms_auth_header','sun_push_auth_header'] as $option) {
            $value = (string) get_option($option, '');
            if ($value !== '') {
                $encrypted = SUN_Utils::is_encrypted($value) ? SUN_Utils::reencrypt_secret($value) : SUN_Utils::encrypt_secret($value);
                if ($encrypted !== '' && $encrypted !== $value) update_option($option, $encrypted, false);
            }
        }
        if (self::table_exists('notifications')) {
            $wpdb->query("UPDATE " . self::table('notifications') . " SET sensitivity='private' WHERE sensitivity='' OR sensitivity IS NULL");
        }
        if (self::table_exists('devices')) {
            $rows = $wpdb->get_results('SELECT id,token FROM ' . self::table('devices') . " WHERE token<>'' LIMIT 500", ARRAY_A);
            foreach ($rows ?: [] as $row) {
                $token = (string) $row['token'];
                $encrypted = SUN_Utils::is_encrypted($token) ? SUN_Utils::reencrypt_secret($token) : SUN_Utils::encrypt_secret($token);
                if ($encrypted !== '' && $encrypted !== $token) $wpdb->update(self::table('devices'), ['token'=>$encrypted], ['id'=>(int)$row['id']], ['%s'], ['%d']);
            }
        }
    }

    public static function table_exists(string $name): bool {
        global $wpdb;
        $table = self::table($name);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    public static function external_table_exists(string $table): bool {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    public static function cleanup(): void {
        global $wpdb;
        $days = max(30, (int) get_option('sun_retention_days', 365));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('notifications') . ' WHERE archived_at IS NOT NULL AND created_at < %s', $cutoff));
        $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('notifications') . ' WHERE expires_at IS NOT NULL AND expires_at < %s', SUN_Utils::now()));
        $wpdb->query($wpdb->prepare("DELETE FROM " . self::table('deliveries') . " WHERE status IN ('sent','failed','cancelled') AND created_at < %s", $cutoff));
        $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table('audit_log') . ' WHERE created_at < %s', gmdate('Y-m-d H:i:s', time() - (max(90, $days) * DAY_IN_SECONDS))));
        $wpdb->query($wpdb->prepare("UPDATE " . self::table('deliveries') . " SET status='retry',lease_token=NULL,lease_expires_at=NULL,next_attempt_at=%s WHERE status='processing' AND lease_expires_at IS NOT NULL AND lease_expires_at<%s", SUN_Utils::now(), SUN_Utils::now()));
    }
}
