<?php
/**
 * Plugin Name: Sabri Unified Notifications & Alerts
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Central notification infrastructure for Sabri Homeopathy: in-app alerts, browser alerts, email, SMS/push webhooks, preferences, delivery logs, retries, Marketplace and Network synchronization, admin monitoring and future mobile-app integration.
 * Version: 1.0.0
 * Author: Sabri Homeopathy
 * Text Domain: sabri-unified-notifications
 * Requires at least: 6.5
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

define('SUN_VERSION', '1.0.0');
define('SUN_FILE', __FILE__);
define('SUN_DIR', plugin_dir_path(__FILE__));
define('SUN_URL', plugin_dir_url(__FILE__));

require_once SUN_DIR . 'includes/class-sun-db.php';
require_once SUN_DIR . 'includes/class-sun-utils.php';
require_once SUN_DIR . 'includes/class-sun-core.php';
require_once SUN_DIR . 'includes/class-sun-channels.php';
require_once SUN_DIR . 'includes/class-sun-integrations.php';
require_once SUN_DIR . 'includes/class-sun-rest.php';
require_once SUN_DIR . 'includes/class-sun-shortcodes.php';
require_once SUN_DIR . 'includes/class-sun-admin.php';
require_once SUN_DIR . 'includes/class-sun-activator.php';

register_activation_hook(__FILE__, ['SUN_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['SUN_Activator', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    SUN_Core::init();
    SUN_Channels::init();
    SUN_Integrations::init();
    SUN_REST::init();
    SUN_Shortcodes::init();
    SUN_Admin::init();
});

/**
 * Public integration helper for all Sabri modules and third-party extensions.
 *
 * Example:
 * sabri_notify_user([
 *   'user_id' => 15,
 *   'type' => 'appointment_confirmed',
 *   'category' => 'appointments',
 *   'priority' => 'high',
 *   'title' => 'Appointment confirmed',
 *   'body' => 'Your appointment has been confirmed.',
 *   'link' => home_url('/appointments/'),
 * ]);
 */
function sabri_notify_user(array $notification): int {
    return SUN_Core::create($notification);
}
