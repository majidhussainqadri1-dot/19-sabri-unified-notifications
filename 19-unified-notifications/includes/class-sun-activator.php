<?php
/**
 * Activation, schema, capabilities and schedules.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Activator {
	/** @return void */
	public static function activate() {
		global $wp_version;
		$lock_key = 'sun_activation_lock';
		$locked   = add_option( $lock_key, time(), '', false );
		if ( ! $locked ) {
			$existing = (int) get_option( $lock_key, 0 );
			if ( $existing > 0 && time() - $existing > 300 ) {
				delete_option( $lock_key );
				$locked = add_option( $lock_key, time(), '', false );
			}
		}
		if ( ! $locked ) {
			deactivate_plugins( SUN_BASENAME );
			wp_die( esc_html__( 'Sabri Unified Notifications activation is already in progress. Please retry shortly.', 'sabri-unified-notifications' ) );
		}

		try {
			if ( version_compare( (string) $wp_version, SUN_MIN_WP_VERSION, '<' ) ) {
				deactivate_plugins( SUN_BASENAME );
				wp_die( esc_html( sprintf( __( 'Sabri Unified Notifications requires WordPress %s or newer.', 'sabri-unified-notifications' ), SUN_MIN_WP_VERSION ) ) );
			}
			if ( version_compare( PHP_VERSION, SUN_MIN_PHP_VERSION, '<' ) ) {
				deactivate_plugins( SUN_BASENAME );
				wp_die( esc_html( sprintf( __( 'Sabri Unified Notifications requires PHP %s or newer.', 'sabri-unified-notifications' ), SUN_MIN_PHP_VERSION ) ) );
			}
			self::install_schema();
			self::install_capabilities();
			self::seed_defaults();
			self::schedule_events();
			add_rewrite_rule( '^notifications/?$', 'index.php?sun_notifications_route=center', 'top' );
			add_rewrite_rule( '^settings/notifications/?$', 'index.php?sun_notifications_route=settings', 'top' );
			add_rewrite_rule( '^notifications/open/([a-f0-9\-]{36})/?$', 'index.php?sun_notifications_route=open&sun_notification_id=$matches[1]', 'top' );
			add_rewrite_rule( '^notifications/unsubscribe/([^/]+)/?$', 'index.php?sun_notifications_route=unsubscribe&sun_notification_token=$matches[1]', 'top' );
			add_rewrite_rule( '^sabri-notifications-service-worker\.js$', 'index.php?sun_notifications_route=service-worker', 'top' );
			update_option( 'sun_plugin_version', SUN_VERSION, false );
			update_option( 'sun_db_version', SUN_DB_VERSION, false );
			update_option( 'sun_activation_snapshot', self::activation_snapshot(), false );
			flush_rewrite_rules( false );
		} finally {
			delete_option( $lock_key );
		}
	}

	/** @return void */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'sun_process_delivery_queue' );
		wp_clear_scheduled_hook( 'sun_reconcile_notifications' );
		wp_clear_scheduled_hook( 'sun_expire_notifications' );
		wp_clear_scheduled_hook( 'sun_process_bulk_jobs' );
		flush_rewrite_rules( false );
	}

	/** @return void */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'sun_process_delivery_queue' ) ) {
			wp_schedule_event( time() + 30, 'sun_every_minute', 'sun_process_delivery_queue' );
		}
		if ( ! wp_next_scheduled( 'sun_reconcile_notifications' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'sun_reconcile_notifications' );
		}
		if ( ! wp_next_scheduled( 'sun_expire_notifications' ) ) {
			wp_schedule_event( time() + 600, 'hourly', 'sun_expire_notifications' );
		}
	}

	/** @return void */
	public static function install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$events  = SUN_Database::table( 'events' );
		$notes   = SUN_Database::table( 'notifications' );
		$prefs   = SUN_Database::table( 'preferences' );
		$deliv   = SUN_Database::table( 'deliveries' );
		$temps   = SUN_Database::table( 'templates' );
		$policy  = SUN_Database::table( 'policies' );
		$devices = SUN_Database::table( 'devices' );
		$dead    = SUN_Database::table( 'dead_letters' );
		$audit   = SUN_Database::table( 'audit' );
		$bulk    = SUN_Database::table( 'bulk_jobs' );

		$sql = array();
		$sql[] = "CREATE TABLE {$events} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			producer varchar(100) NOT NULL,
			event_id varchar(191) NOT NULL,
			event_type varchar(191) NOT NULL,
			schema_version varchar(32) NOT NULL,
			owner varchar(100) NOT NULL,
			occurred_at datetime NOT NULL,
			trace_id varchar(100) NOT NULL,
			payload_hash char(64) NOT NULL,
			payload_ciphertext longtext NULL,
			status varchar(32) NOT NULL DEFAULT 'received',
			error_code varchar(100) NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY producer_event (producer,event_id),
			KEY event_type (event_type),
			KEY status_created (status,created_at),
			KEY trace_id (trace_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$notes} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			recipient_id bigint unsigned NOT NULL,
			producer varchar(100) NOT NULL,
			event_id varchar(191) NOT NULL,
			event_type varchar(191) NOT NULL,
			category varchar(50) NOT NULL,
			priority varchar(20) NOT NULL DEFAULT 'normal',
			template_key varchar(191) NOT NULL,
			template_version varchar(32) NOT NULL,
			locale varchar(20) NOT NULL DEFAULT 'en_US',
			icon varchar(80) NOT NULL DEFAULT 'bell',
			title text NOT NULL,
			summary text NOT NULL,
			data_ciphertext longtext NULL,
			deep_link text NULL,
			deep_link_context varchar(191) NULL,
			status varchar(20) NOT NULL DEFAULT 'unread',
			read_at datetime NULL,
			archived_at datetime NULL,
			expires_at datetime NULL,
			version bigint unsigned NOT NULL DEFAULT 1,
			dedupe_key char(64) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY recipient_status_created (recipient_id,status,created_at),
			KEY recipient_category_created (recipient_id,category,created_at),
			KEY recipient_priority_created (recipient_id,priority,created_at),
			KEY expires_at (expires_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$prefs} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint unsigned NOT NULL,
			category varchar(50) NOT NULL,
			channel varchar(20) NOT NULL,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			digest_frequency varchar(20) NOT NULL DEFAULT 'immediate',
			quiet_enabled tinyint(1) NOT NULL DEFAULT 0,
			quiet_start time NULL,
			quiet_end time NULL,
			timezone varchar(64) NULL,
			consent_source varchar(100) NULL,
			consent_at datetime NULL,
			version bigint unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_category_channel (user_id,category,channel),
			KEY user_id (user_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$deliv} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			notification_id bigint unsigned NOT NULL,
			recipient_id bigint unsigned NOT NULL,
			channel varchar(20) NOT NULL,
			provider varchar(100) NULL,
			status varchar(30) NOT NULL DEFAULT 'queued',
			attempt_count smallint unsigned NOT NULL DEFAULT 0,
			max_attempts smallint unsigned NOT NULL DEFAULT 5,
			scheduled_at datetime NOT NULL,
			next_attempt_at datetime NULL,
			last_attempt_at datetime NULL,
			provider_message_id varchar(191) NULL,
			last_error_code varchar(100) NULL,
			last_error_safe text NULL,
			digest_key varchar(191) NULL,
			dedupe_key char(64) NOT NULL,
			accepted_at datetime NULL,
			delivered_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY queue (status,next_attempt_at,scheduled_at),
			KEY recipient_channel (recipient_id,channel,status),
			KEY notification_id (notification_id),
			KEY provider_message_id (provider_message_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$temps} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			template_key varchar(191) NOT NULL,
			event_type varchar(191) NOT NULL,
			channel varchar(20) NOT NULL,
			locale varchar(20) NOT NULL DEFAULT 'en_US',
			version varchar(32) NOT NULL,
			title_template text NOT NULL,
			body_template text NOT NULL,
			allowed_variables longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			approved_by bigint unsigned NULL,
			approved_at datetime NULL,
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY template_identity (template_key,channel,locale,version),
			KEY active_lookup (event_type,channel,locale,status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$policy} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			policy_key varchar(191) NOT NULL,
			event_pattern varchar(191) NOT NULL,
			category varchar(50) NOT NULL,
			priority varchar(20) NOT NULL,
			channels_json longtext NOT NULL,
			mandatory tinyint(1) NOT NULL DEFAULT 0,
			sensitivity varchar(30) NOT NULL DEFAULT 'standard',
			digest_allowed tinyint(1) NOT NULL DEFAULT 1,
			status varchar(20) NOT NULL DEFAULT 'active',
			version varchar(32) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY policy_version (policy_key,version),
			KEY event_status (event_pattern,status)
		) {$charset};";

		$sql[] = "CREATE TABLE {$devices} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			user_id bigint unsigned NOT NULL,
			provider varchar(50) NOT NULL,
			platform varchar(50) NOT NULL,
			token_hash char(64) NOT NULL,
			token_ciphertext longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			last_seen_at datetime NULL,
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY provider_token (provider,token_hash),
			KEY user_status (user_id,status),
			KEY expires_at (expires_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$dead} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			event_id bigint unsigned NULL,
			delivery_id bigint unsigned NULL,
			object_type varchar(30) NOT NULL,
			error_code varchar(100) NOT NULL,
			error_safe text NOT NULL,
			attempt_count smallint unsigned NOT NULL DEFAULT 0,
			next_action varchar(100) NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			resolved_by bigint unsigned NULL,
			resolved_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY status_created (status,created_at),
			KEY delivery_id (delivery_id),
			KEY event_id (event_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$audit} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			action varchar(100) NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id varchar(191) NOT NULL,
			actor_id bigint unsigned NOT NULL DEFAULT 0,
			purpose varchar(100) NOT NULL,
			trace_id varchar(100) NOT NULL,
			context_json longtext NULL,
			prev_hash char(64) NULL,
			entry_hash char(64) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY object_lookup (object_type,object_id),
			KEY actor_created (actor_id,created_at),
			KEY trace_id (trace_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$bulk} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			created_by bigint unsigned NOT NULL,
			audience_hash char(64) NOT NULL,
			recipient_count int unsigned NOT NULL,
			event_type varchar(191) NOT NULL,
			payload_ciphertext longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'preview',
			confirmation_hash char(64) NOT NULL,
			cancel_requested tinyint(1) NOT NULL DEFAULT 0,
			processed_count int unsigned NOT NULL DEFAULT 0,
			failed_count int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY status_created (status,created_at)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/** @return void */
	private static function install_capabilities() {
		$caps = array(
			'manage_sabri_notifications',
			'view_sabri_notification_health',
			'retry_sabri_notification_delivery',
			'send_sabri_bulk_notifications',
		);
		foreach ( array( 'administrator' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/** @return void */
	private static function seed_defaults() {
		global $wpdb;
		$now = SUN_Database::now();
		$policies = array(
			array( 'security', 'Security.*', 'security', 'critical', array( 'in_app', 'email', 'push' ), 1, 'sensitive', 0 ),
			array( 'safety', 'Safety.*', 'safety', 'critical', array( 'in_app', 'email', 'push' ), 1, 'sensitive', 0 ),
			array( 'clinic', 'Clinic.*', 'clinic', 'high', array( 'in_app', 'email', 'push' ), 0, 'sensitive', 1 ),
			array( 'publishing', 'Publishing.*', 'publishing', 'normal', array( 'in_app', 'email' ), 0, 'standard', 1 ),
			array( 'learning', 'Learning.*', 'learning', 'normal', array( 'in_app', 'email' ), 0, 'standard', 1 ),
			array( 'social', 'Social.*', 'social', 'normal', array( 'in_app', 'push' ), 0, 'standard', 1 ),
			array( 'marketplace', 'Marketplace.*', 'marketplace', 'high', array( 'in_app', 'email', 'push' ), 0, 'sensitive', 1 ),
			array( 'messages', 'Communication.*', 'messages', 'normal', array( 'in_app', 'push' ), 0, 'sensitive', 1 ),
			array( 'media', 'Media.*', 'media', 'normal', array( 'in_app', 'email' ), 0, 'standard', 1 ),
			array( 'system', 'System.*', 'system', 'high', array( 'in_app', 'email' ), 1, 'standard', 0 ),
		);
		foreach ( $policies as $item ) {
			$wpdb->query(
				$wpdb->prepare(
					'INSERT IGNORE INTO ' . SUN_Database::table( 'policies' ) . ' (policy_key,event_pattern,category,priority,channels_json,mandatory,sensitivity,digest_allowed,status,version,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%d,%s,%d,%s,%s,%s,%s)',
					$item[0], $item[1], $item[2], $item[3], wp_json_encode( $item[4] ), $item[5], $item[6], $item[7], 'active', '1.0.0', $now, $now
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		$allowed = wp_json_encode( array( 'actor_name', 'object_name', 'action_name', 'summary', 'site_name' ) );
		$channels = array( 'in_app', 'email', 'push', 'sms' );
		foreach ( $channels as $channel ) {
			$wpdb->query(
				$wpdb->prepare(
					'INSERT IGNORE INTO ' . SUN_Database::table( 'templates' ) . ' (template_key,event_type,channel,locale,version,title_template,body_template,allowed_variables,status,approved_by,approved_at,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s)',
					'generic-' . $channel, '*', $channel, 'en_US', '1.0.0', '{{action_name}}', '{{summary}}', $allowed, 'active', get_current_user_id(), $now, $now, $now
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	/** @return array<string,mixed> */
	private static function activation_snapshot() {
		global $wp_version;
		return array(
			'plugin_version' => SUN_VERSION,
			'db_version'     => SUN_DB_VERSION,
			'php'            => PHP_VERSION,
			'wordpress'      => $wp_version,
			'min_php'        => SUN_MIN_PHP_VERSION,
			'min_wordpress'  => SUN_MIN_WP_VERSION,
			'activated_at'   => SUN_Database::now(),
			'multisite'      => is_multisite(),
		);
	}
}
