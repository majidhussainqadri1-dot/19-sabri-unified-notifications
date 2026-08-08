<?php
/** Advanced attention, automation, routing and experimentation schema for File 19. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Advanced_Activator {
    /** @return void */
    public static function activate() {
        self::install_schema();
        self::install_capabilities();
        self::seed_defaults();
    }

    /** @return void */
    public static function install_schema() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $profiles = SUN_Database::table( 'attention_profiles' );
        $states = SUN_Database::table( 'notification_states' );
        $rules = SUN_Database::table( 'notification_rules' );
        $devices = SUN_Database::table( 'device_profiles' );
        $routes = SUN_Database::table( 'provider_routes' );
        $experiments = SUN_Database::table( 'experiments' );
        $traces = SUN_Database::table( 'trace_spans' );
        $watch = SUN_Database::table( 'watch_history' );
        $sql = array();

        $sql[] = "CREATE TABLE {$profiles} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            focus_mode varchar(32) NOT NULL DEFAULT 'balanced',
            essential_only tinyint(1) NOT NULL DEFAULT 0,
            hourly_budget smallint unsigned NOT NULL DEFAULT 20,
            daily_budget smallint unsigned NOT NULL DEFAULT 120,
            best_time_enabled tinyint(1) NOT NULL DEFAULT 0,
            best_time_local time NULL,
            ai_summary_enabled tinyint(1) NOT NULL DEFAULT 1,
            history_days smallint unsigned NOT NULL DEFAULT 90,
            muted_until datetime NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY user_id (user_id), KEY focus_mode (focus_mode), KEY muted_until (muted_until)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$states} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            notification_id bigint unsigned NOT NULL,
            user_id bigint unsigned NOT NULL,
            pinned_at datetime NULL,
            snoozed_until datetime NULL,
            action_state varchar(24) NOT NULL DEFAULT 'none',
            attention_score smallint unsigned NOT NULL DEFAULT 50,
            attention_reason varchar(191) NULL,
            group_key char(64) NULL,
            source_label varchar(191) NULL,
            source_kind varchar(50) NULL,
            source_verified tinyint(1) NOT NULL DEFAULT 0,
            live_revision bigint unsigned NOT NULL DEFAULT 1,
            version bigint unsigned NOT NULL DEFAULT 1,
            revoked_at datetime NULL,
            revoke_reason varchar(100) NULL,
            superseded_by char(36) NULL,
            last_activity_at datetime NULL,
            meta_ciphertext longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY notification_id (notification_id), KEY user_priority (user_id,attention_score), KEY user_pinned (user_id,pinned_at), KEY user_snoozed (user_id,snoozed_until), KEY group_key (group_key), KEY source_kind (source_kind), KEY revoked_at (revoked_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$rules} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            user_id bigint unsigned NOT NULL,
            name varchar(191) NOT NULL,
            trigger_type varchar(40) NOT NULL,
            trigger_json longtext NOT NULL,
            action_json longtext NOT NULL,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            version bigint unsigned NOT NULL DEFAULT 1,
            last_matched_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY user_enabled (user_id,enabled), KEY trigger_type (trigger_type)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$devices} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            device_public_id char(36) NOT NULL,
            user_id bigint unsigned NOT NULL,
            focus_mode varchar(32) NOT NULL DEFAULT 'inherit',
            categories_json longtext NULL,
            channels_json longtext NULL,
            handoff_ciphertext longtext NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY device_public_id (device_public_id), KEY user_id (user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$routes} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            channel varchar(24) NOT NULL,
            provider_key varchar(100) NOT NULL,
            priority smallint unsigned NOT NULL DEFAULT 100,
            enabled tinyint(1) NOT NULL DEFAULT 0,
            cost_micros bigint unsigned NULL,
            cost_known tinyint(1) NOT NULL DEFAULT 0,
            regions_json longtext NULL,
            max_per_hour int unsigned NULL,
            config_key varchar(191) NULL,
            health_state varchar(24) NOT NULL DEFAULT 'unknown',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY channel_provider (channel,provider_key), KEY route_lookup (channel,enabled,priority)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$experiments} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            owner_id bigint unsigned NOT NULL,
            experiment_type varchar(24) NOT NULL,
            policy_key varchar(191) NOT NULL,
            config_json longtext NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'draft',
            rollout_percent decimal(5,2) NOT NULL DEFAULT 0.00,
            metrics_json longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY type_status (experiment_type,status), KEY policy_key (policy_key)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$traces} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            trace_id varchar(100) NOT NULL,
            notification_id bigint unsigned NULL,
            delivery_id bigint unsigned NULL,
            stage varchar(50) NOT NULL,
            status varchar(24) NOT NULL,
            detail_json longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id), KEY trace_stage (trace_id,stage), KEY notification_id (notification_id), KEY delivery_id (delivery_id), KEY created_at (created_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$watch} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id varchar(191) NOT NULL,
            engagement_type varchar(32) NOT NULL,
            first_seen_at datetime NOT NULL,
            last_seen_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY user_object (user_id,object_type,object_id), KEY object_lookup (object_type,object_id), KEY user_seen (user_id,last_seen_at)
        ) {$charset};";

        foreach ( $sql as $statement ) { dbDelta( $statement ); }
    }

    /** @return void */
    public static function seed_defaults() {
        global $wpdb;
        $table = SUN_Database::table( 'provider_routes' );
        $now = SUN_Database::now();
        $defaults = array(
            array( 'email', 'wordpress-mail', 100, 1 ),
            array( 'push', 'webpush', 100, 1 ),
            array( 'push', 'fcm', 80, 0 ),
            array( 'push', 'apns', 80, 0 ),
            array( 'sms', 'default-sms', 100, 1 ),
            array( 'whatsapp', 'whatsapp-business', 100, 0 ),
            array( 'rcs', 'rcs-business', 100, 0 ),
        );
        foreach ( $defaults as $row ) {
            $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO {$table} (public_id,channel,provider_key,priority,enabled,cost_known,health_state,created_at,updated_at) VALUES (%s,%s,%s,%d,%d,%d,%s,%s,%s)",
                SUN_Database::uuid(), $row[0], $row[1], $row[2], $row[3], 0, 'unknown', $now, $now
            ) );
        }
    }

    /** @return void */
    private static function install_capabilities() {
        $caps = array(
            'manage_sabri_notification_experiments',
            'view_sabri_notification_trace',
            'run_sabri_notification_synthetic_tests',
        );
        $role = get_role( 'administrator' );
        if ( ! $role ) { return; }
        foreach ( $caps as $cap ) { $role->add_cap( $cap ); }
    }
}
