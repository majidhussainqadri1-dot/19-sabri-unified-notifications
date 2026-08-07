<?php
/**
 * Plugin Name: Sabri Unified Notifications and Alerts
 * Plugin URI: https://sabrihomeopathy.com/
 * Description: Canonical notification entity, single in-app center and bell, preferences, quiet hours, digests, delivery adapters, retries, dead-letter handling, device registration, privacy lifecycle and operational diagnostics for the Sabri Social Homeopathy Platform.
 * Version: 2.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * Text Domain: sabri-unified-notifications
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUN_VERSION', '2.0.0' );
define( 'SUN_DB_VERSION', '2.0.0' );
define( 'SUN_FILE', __FILE__ );
define( 'SUN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUN_URL', plugin_dir_url( __FILE__ ) );
define( 'SUN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SUN_TEXT_DOMAIN', 'sabri-unified-notifications' );
define( 'SUN_REST_NAMESPACE', 'sabri-notifications/v1' );

require_once SUN_PATH . 'includes/class-sun-database.php';
require_once SUN_PATH . 'includes/class-sun-crypto.php';
require_once SUN_PATH . 'includes/class-sun-audit.php';
require_once SUN_PATH . 'includes/class-sun-auth.php';
require_once SUN_PATH . 'includes/class-sun-producer-registry.php';
require_once SUN_PATH . 'includes/class-sun-event-validator.php';
require_once SUN_PATH . 'includes/class-sun-template-engine.php';
require_once SUN_PATH . 'includes/class-sun-preferences.php';
require_once SUN_PATH . 'includes/class-sun-policy-engine.php';
require_once SUN_PATH . 'includes/class-sun-deep-link.php';
require_once SUN_PATH . 'includes/adapters/interface-sun-delivery-adapter.php';
require_once SUN_PATH . 'includes/adapters/class-sun-email-adapter.php';
require_once SUN_PATH . 'includes/adapters/class-sun-push-adapter.php';
require_once SUN_PATH . 'includes/adapters/class-sun-sms-adapter.php';
require_once SUN_PATH . 'includes/class-sun-delivery-service.php';
require_once SUN_PATH . 'includes/class-sun-notification-service.php';
require_once SUN_PATH . 'includes/class-sun-bulk-service.php';
require_once SUN_PATH . 'includes/class-sun-reconciliation.php';
require_once SUN_PATH . 'includes/class-sun-health.php';
require_once SUN_PATH . 'includes/class-sun-rest-controller.php';
require_once SUN_PATH . 'includes/class-sun-renderer.php';
require_once SUN_PATH . 'includes/class-sun-router.php';
require_once SUN_PATH . 'includes/class-sun-admin.php';
require_once SUN_PATH . 'includes/class-sun-privacy.php';
require_once SUN_PATH . 'includes/class-sun-activator.php';
require_once SUN_PATH . 'includes/class-sun-plugin.php';
require_once SUN_PATH . 'includes/functions.php';

register_activation_hook( __FILE__, array( 'SUN_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SUN_Activator', 'deactivate' ) );

/**
 * Return the canonical plugin coordinator.
 *
 * @return SUN_Plugin
 */
function sun_notifications() {
	return SUN_Plugin::instance();
}

sun_notifications()->boot();
