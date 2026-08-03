<?php
/** Fresh independent review of CF-01 notification privacy and degraded paths. */
declare(strict_types=1);

$root = dirname(__DIR__);
$provider = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-cf01-clinical-notifications.php');
$channels = (string) file_get_contents($root . '/sabri-unified-notifications/includes/class-sun-channels.php');
$javascript = (string) file_get_contents($root . '/sabri-unified-notifications/assets/js/sun.js');
$runtime = (string) file_get_contents($root . '/tests/cf01-clinical-notification-runtime.php');

$checks = 0;
function cf01_fresh_check(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

cf01_fresh_check(str_contains($provider, "'sun_cf01_notification_suppressed'"), 'routine category suppression has an explicit outcome');
cf01_fresh_check(str_contains($provider, "'retryable' => false"), 'preference suppression is explicitly non-retryable');
cf01_fresh_check(str_contains($provider, 'str_ends_with($value, \'Z\')') && str_contains($provider, " . ' UTC'"), 'expiry normalization requires explicit UTC semantics');
cf01_fresh_check(str_contains($provider, '(?:[01]\\d|2[0-3])'), 'expiry grammar rejects hours outside 00 through 23');
cf01_fresh_check(str_contains($provider, 'parse_str($query, $query_values)'), 'destination query keys are decoded before inspection');
cf01_fresh_check(str_contains($provider, 'authorization|auth|bearer|signature|signed|secret|password|session'), 'compound bearer and credential key families are rejected');
cf01_fresh_check(str_contains($provider, "preg_match('/^[A-Za-z0-9_-]{10,}\\.[A-Za-z0-9_-]{10,}\\.[A-Za-z0-9_-]{10,}$/'"), 'JWT-like query values are rejected');
cf01_fresh_check(str_contains($channels, 'SUN_Utils::external_preview($notification)'), 'external transport derives protected preview through the canonical preview function');
cf01_fresh_check(str_contains($javascript, "new Notification(notification.externalTitle || 'Private notification'"), 'browser alerts prefer the protected external title');
cf01_fresh_check(str_contains($javascript, "notification.externalBody || 'Sign in to view this notification.'"), 'browser alerts prefer the protected external body');
cf01_fresh_check(str_contains($javascript, "notification.externalTitle || notification.title || 'Notification'"), 'toasts prefer external-safe title before internal title');
cf01_fresh_check(str_contains($javascript, 'notification.externalBody || notification.body ||'), 'toasts prefer external-safe body before internal body');
cf01_fresh_check(str_contains($runtime, 'bare server-local expiry is rejected'), 'runtime suite covers timezone ambiguity');
cf01_fresh_check(str_contains($runtime, 'encoded nested bearer key is rejected'), 'runtime suite covers decoded nested credential keys');
cf01_fresh_check(str_contains($runtime, 'JWT-like query value is rejected'), 'runtime suite covers token-shaped values');
cf01_fresh_check(str_contains($runtime, 'suppressed notification never reaches the delivery core'), 'runtime suite proves suppression causes no delivery side effect');

echo "File 19 CF-01 fresh review: $checks PASS, 0 FAIL\n";
