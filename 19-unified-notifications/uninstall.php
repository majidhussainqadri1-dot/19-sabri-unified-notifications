<?php
/** Non-destructive uninstall by default. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
wp_clear_scheduled_hook('sun_process_delivery_queue');wp_clear_scheduled_hook('sun_reconcile_notifications');wp_clear_scheduled_hook('sun_expire_notifications');wp_clear_scheduled_hook('sun_process_bulk_jobs');wp_clear_scheduled_hook('sun_notification_privacy_retention');
delete_option('sun_delivery_queue_lock');delete_option('sun_audit_chain_lock');delete_option('sun_activation_lock');delete_transient('sun_delivery_queue_lock');
if(!defined('SUN_ALLOW_DESTRUCTIVE_UNINSTALL')||true!==SUN_ALLOW_DESTRUCTIVE_UNINSTALL){return;}
global $wpdb;foreach(array('webhook_receipts','request_idempotency','trace_spans','experiments','provider_routes','watch_history','device_profiles','notification_rules','notification_states','attention_profiles','bulk_jobs','audit','dead_letters','devices','policies','templates','deliveries','subscriptions','preferences','notifications','events') as $logical){$table=$wpdb->prefix.'sun_'.$logical;$wpdb->query("DROP TABLE IF EXISTS `{$table}`");}
foreach(array('sun_plugin_version','sun_db_version','sun_activation_snapshot','sun_last_reconciliation','sun_notification_safe_mode','sun_notification_emergency_disabled','sun_file01_registry_state','sun_legacy_migration_state') as $option){delete_option($option);}
