<?php
/**
 * Plugin Name: Sabri Unified Notifications & Alerts
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Central notification infrastructure for the Sabri Social Homeopathy Platform: in-app alerts, privacy-aware browser alerts, email, secured SMS/push webhooks, preferences, delivery logs, retries, Marketplace and Network synchronization, administration and future mobile-app integration.
 * Version: 1.1.0
 * Author: Sabri Homeopathy
 * Text Domain: sabri-unified-notifications
 * Requires at least: 6.5
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

define('SUN_VERSION', '1.1.0');
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
require_once SUN_DIR . 'includes/class-sun-privacy.php';
require_once SUN_DIR . 'includes/class-sun-activator.php';

// Activation hooks can run after plugins_loaded; register custom schedules immediately.
SUN_Channels::register_cron_schedules();

register_activation_hook(__FILE__, ['SUN_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['SUN_Activator', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    SUN_Activator::maybe_upgrade();
    SUN_Core::init();
    SUN_Channels::init();
    SUN_Integrations::init();
    SUN_REST::init();
    SUN_Shortcodes::init();
    SUN_Admin::init();
    SUN_Privacy::init();
}, 5);

/**
 * Public integration helper for Sabri modules and approved extensions.
 *
 * Supported privacy fields include:
 * - sensitivity: public|private|clinical|identity|security
 * - external_title / external_body: safe lock-screen and email previews
 * - requires_authenticated_open: defaults to true for non-public notifications
 */
function sabri_notify_user(array $notification): int {
    return SUN_Core::create($notification);
}
