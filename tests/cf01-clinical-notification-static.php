<?php
/** File 19 CF-01 static ownership/privacy contracts; WordPress is not loaded. */
declare(strict_types=1);

$root = dirname(__DIR__);
$main = (string) file_get_contents($root . '/sabri-unified-notifications/sabri-unified-notifications.php');
$provider = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-cf01-clinical-notifications.php');
$core = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-core.php');
$channels = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-channels.php');
$utils = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-utils.php');

$checks = 0;
function cf01_static_check(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

cf01_static_check(str_contains($main, 'Version: 1.1.1'), 'plugin header identifies File 19 1.1.1');
cf01_static_check(str_contains($main, "define('SUN_VERSION', '1.1.1')"), 'runtime version identifies File 19 1.1.1');
cf01_static_check(str_contains($main, "define('SUN_CF01_NOTIFICATION_CONTRACT_VERSION', '1.0.0')"), 'CF-01 contract version is explicit');
cf01_static_check(str_contains($main, 'class-sun-cf01-clinical-notifications.php'), 'strict provider loads from bootstrap');
cf01_static_check(str_contains($main, 'SUN_CF01_Clinical_Notifications::register()'), 'strict provider lifecycle is registered');
cf01_static_check(str_contains($main, 'sun_cf01_request_clinical_notification'), 'strict public producer helper exists');
cf01_static_check(str_contains($provider, "public const CONTRACT_NAME = 'sun.cf01.notification-request'"), 'contract name is exact');
cf01_static_check(str_contains($provider, "public const CONTRACT_VERSION = '1.0.0'"), 'contract version is exact');
cf01_static_check(str_contains($provider, 'array_diff(array_keys($request), self::ALLOWED_KEYS)'), 'unknown request fields fail closed');
cf01_static_check(!str_contains($provider, "'title',\n        'body'"), 'producer allowlist contains no arbitrary title/body fields');
cf01_static_check(!str_contains($provider, "'link',") && !str_contains($provider, "'url',"), 'producer allowlist contains no direct URL field');
cf01_static_check(str_contains($provider, "'external_title' => 'Private notification'"), 'external title is fixed and generic');
cf01_static_check(str_contains($provider, "'external_body' => 'Sign in to view this protected notification.'"), 'external body is fixed and generic');
cf01_static_check(str_contains($provider, "'contains_clinical_content' => false"), 'clinical-content exclusion is explicit');
cf01_static_check(str_contains($provider, "'contains_patient_identity' => false"), 'patient-identity exclusion is explicit');
cf01_static_check(str_contains($provider, "'contains_attachment_content' => false"), 'attachment-content exclusion is explicit');
cf01_static_check(str_contains($provider, "'contains_bearer_authorization' => false"), 'bearer-authorization exclusion is explicit');
cf01_static_check(str_contains($provider, "'delivery_state_is_not_clinical_state' => true"), 'delivery state cannot mutate clinical state');
cf01_static_check(str_contains($provider, "'requires_click_time_cf01_authorization' => true"), 'click-time native authorization is explicit');
cf01_static_check(str_contains($provider, "'sun_cf01_notification_request_authorized'"), 'producer authorization fails closed through an owner contract');
cf01_static_check(str_contains($provider, "'sun_cf01_resolve_recipient_platform_uuid'"), 'recipient UUID resolution is owner-executed');
cf01_static_check(str_contains($provider, "'sun_cf01_clinical_destination_resolve'"), 'destination resolution is owner-executed');
cf01_static_check(str_contains($provider, "'link' => SUN_Utils::page_url()"), 'external/in-app notification link is only the File 19 notification center');
cf01_static_check(str_contains($provider, "'source' => 'cf01'") && str_contains($provider, "'source_id' => 0"), 'notification is namespaced without exposing a clinical object ID');
cf01_static_check(str_contains($provider, "'entity_type' => ''") && str_contains($provider, "'entity_id' => 0"), 'no clinical entity identity is stored in generic entity columns');
cf01_static_check(str_contains($provider, "'security_required'") && str_contains($provider, "'access_security'"), 'mandatory delivery is restricted to access-security templates');
cf01_static_check(str_contains($core, 'if(!$mandatory&&empty($prefs[\'categories\'][$category]))return 0'), 'non-mandatory clinical notifications honor category opt-out');
cf01_static_check(str_contains($core, 'if(!empty($prefs[\'do_not_disturb\'])&&!$mandatory)$channels=[]'), 'non-mandatory clinical notifications honor do-not-disturb');
cf01_static_check(str_contains($core, 'if(self::is_quiet_now($prefs)&&!$mandatory)'), 'non-mandatory clinical notifications honor quiet hours');
cf01_static_check(str_contains($channels, "status='retry'") && str_contains($channels, 'waiting_config'), 'provider outage and retry states remain explicit');
cf01_static_check(str_contains($channels, 'notification_channel_device'), 'channel/device delivery deduplication remains database-backed');
cf01_static_check(str_contains($utils, "'clinical'=>['Private clinical update'"), 'legacy generic clinical preview fallback remains protected');
cf01_static_check(str_contains($provider, "'Cache-Control', 'private, no-store"), 'destination response is private and no-store');
cf01_static_check(str_contains($provider, "'X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet'"), 'destination response is non-indexable');
cf01_static_check(str_contains($provider, "isset(\$parts['user'])") && str_contains($provider, "isset(\$parts['pass'])"), 'same-origin resolver rejects URL user information');
cf01_static_check(str_contains($provider, "'bearer_authorization' => false"), 'destination response carries no bearer authorization');
$create_offset = strpos($provider, 'SUN_Core::create([');
$create_payload = $create_offset === false ? '' : substr($provider, $create_offset, 2600);
cf01_static_check(!preg_match('/patient_name|diagnosis|symptoms|remedy|potency|dosage|clinical_note|attachment_name|guardian_detail|break_glass_reason|signed_url\s*=>/i', $create_payload), 'created notification payload contains no prohibited clinical fields');

echo "File 19 CF-01 static contracts: $checks PASS, 0 FAIL\n";
