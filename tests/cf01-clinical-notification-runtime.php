<?php
/** File 19 CF-01 runtime/adversarial contracts; WordPress is intentionally mocked. */
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
define('SUN_VERSION', '1.1.1');

final class WP_Error {
    private string $code;
    private string $message;
    private array $data;
    public function __construct(string $code = '', string $message = '', array $data = []) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data(): array { return $this->data; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }

final class WP_User {
    public int $ID;
    public function __construct(int $id) { $this->ID = $id; }
}

final class WP_REST_Response {
    public array $data;
    public int $status;
    public array $headers = [];
    public function __construct(array $data = [], int $status = 200) { $this->data = $data; $this->status = $status; }
    public function header(string $name, string $value): void { $this->headers[$name] = $value; }
}

final class WP_REST_Request implements ArrayAccess {
    private array $params;
    public function __construct(array $params = []) { $this->params = $params; }
    public function offsetExists($offset): bool { return array_key_exists((string) $offset, $this->params); }
    public function offsetGet($offset) { return $this->params[(string) $offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->params[(string) $offset] = $value; }
    public function offsetUnset($offset): void { unset($this->params[(string) $offset]); }
}

$GLOBALS['cf01_filters'] = [];
$GLOBALS['cf01_current_user'] = 42;
$GLOBALS['cf01_routes'] = [];
function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void {}
function register_rest_route(string $namespace, string $route, array $args): void { $GLOBALS['cf01_routes'][$namespace . $route] = $args; }
function is_user_logged_in(): bool { return $GLOBALS['cf01_current_user'] > 0; }
function sanitize_key(string $value): string { return strtolower(preg_replace('/[^a-z0-9_-]/', '', $value) ?? ''); }
function apply_filters(string $hook, $value, ...$args) {
    $callback = $GLOBALS['cf01_filters'][$hook] ?? null;
    return is_callable($callback) ? $callback($value, ...$args) : $value;
}
function get_userdata(int $user_id) { return $user_id === 42 ? new WP_User(42) : false; }
function home_url(string $path = ''): string { return 'https://example.com' . $path; }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function absint($value): int { return abs((int) $value); }
function get_current_user_id(): int { return (int) $GLOBALS['cf01_current_user']; }
function rest_ensure_response($value): WP_REST_Response { return $value instanceof WP_REST_Response ? $value : new WP_REST_Response((array) $value); }

final class SUN_Utils {
    public static function page_url(): string { return 'https://example.com/notifications/'; }
    public static function json_decode($value, array $default = []): array {
        if (is_array($value)) return $value;
        $decoded = is_string($value) ? json_decode($value, true) : null;
        return is_array($decoded) ? $decoded : $default;
    }
}

final class SUN_Core {
    public static array $created = [];
    public static array $dedupe = [];
    public static string $mode = 'normal';
    public static array $preferences = ['categories' => ['appointments' => true, 'security' => true]];
    public static function get_preferences(int $user_id): array { return self::$preferences; }
    public static function create(array $args): int {
        self::$created[] = $args;
        if (self::$mode !== 'normal') return 0;
        $key = (string) ($args['dedupe_key'] ?? '');
        if ($key !== '' && isset(self::$dedupe[$key])) return self::$dedupe[$key];
        $id = 100 + count(self::$dedupe) + 1;
        if ($key !== '') self::$dedupe[$key] = $id;
        return $id;
    }
}

final class SUN_DB {
    public static function table_exists(string $name): bool { return $name === 'notifications'; }
    public static function table(string $name): string { return 'wp_sun_' . $name; }
}

final class CF01_WPDB_Mock {
    public ?array $row = null;
    public function prepare(string $query, ...$args): array { return ['query' => $query, 'args' => $args]; }
    public function get_row($prepared, $format = null): ?array {
        if (!is_array($prepared) || !is_array($this->row)) return null;
        $args = $prepared['args'] ?? [];
        if ((int) ($args[0] ?? 0) !== (int) ($this->row['id'] ?? 0)) return null;
        if ((int) ($args[1] ?? 0) !== (int) ($this->row['user_id'] ?? 0)) return null;
        if ((string) ($args[2] ?? '') !== (string) ($this->row['source'] ?? '')) return null;
        return $this->row;
    }
}
$GLOBALS['wpdb'] = new CF01_WPDB_Mock();

require __DIR__ . '/../sabri-unified-notifications/includes/class-sun-cf01-clinical-notifications.php';

$checks = 0;
function runtime_check(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}
function error_code($value): string { return $value instanceof WP_Error ? $value->get_error_code() : ''; }
function valid_request(array $overrides = []): array {
    return array_replace([
        'recipient_platform_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'template_key' => 'follow_up_due',
        'action_category' => 'follow_up',
        'destination_reference' => 'cf01-destination.abcdef1234567890',
        'urgency' => 'normal',
        'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
        'mandatory_policy' => 'none',
        'correlation_id' => 'correlation.abcdef1234',
        'dedupe_key' => 'dedupe.abcdef1234',
        'producer_contract' => 'cf01.clinical-events',
        'producer_version' => '1.0.0',
    ], $overrides);
}

SUN_CF01_Clinical_Notifications::register_routes();
runtime_check(isset($GLOBALS['cf01_routes']['sabri-notifications/v1/clinical/(?P<id>\d+)/destination']), 'protected destination route is registered');

$result = SUN_CF01_Clinical_Notifications::request(valid_request(['patient_name' => 'Patient A']));
runtime_check(error_code($result) === 'sun_cf01_unknown_field', 'patient identity field is rejected');
$result = SUN_CF01_Clinical_Notifications::request(valid_request(['diagnosis' => 'Private diagnosis']));
runtime_check(error_code($result) === 'sun_cf01_unknown_field', 'diagnosis field is rejected');
$result = SUN_CF01_Clinical_Notifications::request(valid_request(['title' => 'Custom clinical title']));
runtime_check(error_code($result) === 'sun_cf01_unknown_field', 'arbitrary notification copy is rejected');
$result = SUN_CF01_Clinical_Notifications::request(valid_request(['destination_reference' => 'https://example.com/private']));
runtime_check(error_code($result) === 'sun_cf01_destination_reference_invalid', 'direct destination URL is rejected');
$result = SUN_CF01_Clinical_Notifications::request(valid_request(['template_key' => 'follow_up_due', 'action_category' => 'records']));
runtime_check(error_code($result) === 'sun_cf01_action_category_invalid', 'template/action mismatch is rejected');
$result = SUN_CF01_Clinical_Notifications::request(valid_request(['mandatory_policy' => 'security_required']));
runtime_check(error_code($result) === 'sun_cf01_mandatory_policy_mismatch', 'routine reminder cannot bypass preferences or quiet hours');
$result = SUN_CF01_Clinical_Notifications::request(valid_request());
runtime_check(error_code($result) === 'sun_cf01_request_not_authorized', 'producer authorization fails closed');

$GLOBALS['cf01_filters']['sun_cf01_notification_request_authorized'] = static function ($default, array $request): bool {
    return ($request['producer_contract'] ?? '') === 'cf01.clinical-events';
};
$result = SUN_CF01_Clinical_Notifications::request(valid_request());
runtime_check(error_code($result) === 'sun_cf01_recipient_unavailable', 'recipient resolution fails closed');
$GLOBALS['cf01_filters']['sun_cf01_resolve_recipient_platform_uuid'] = static function ($default, string $uuid): int {
    return $uuid === 'f47ac10b-58cc-4372-a567-0e02b2c3d479' ? 42 : 0;
};

$bare_time = SUN_CF01_Clinical_Notifications::request(valid_request([
    'expires_at' => gmdate('Y-m-d H:i:s', time() + 86400),
]));
runtime_check(error_code($bare_time) === 'sun_cf01_expiry_invalid', 'bare server-local expiry is rejected');
$invalid_hour = SUN_CF01_Clinical_Notifications::request(valid_request([
    'expires_at' => gmdate('Y-m-d', time() + 86400) . 'T29:00:00Z',
]));
runtime_check(error_code($invalid_hour) === 'sun_cf01_expiry_invalid', 'invalid UTC hour is rejected');

$result = SUN_CF01_Clinical_Notifications::request(valid_request());
runtime_check($result === 101, 'authorized privacy-minimal notification is created');
$created = SUN_Core::$created[count(SUN_Core::$created) - 1];
runtime_check(($created['external_title'] ?? '') === 'Private notification', 'external title is generic');
runtime_check(($created['external_body'] ?? '') === 'Sign in to view this protected notification.', 'external body is generic');
runtime_check(($created['link'] ?? '') === 'https://example.com/notifications/', 'external link exposes only the notification center');
runtime_check(($created['entity_id'] ?? -1) === 0 && ($created['source_id'] ?? -1) === 0, 'notification stores no clinical object identifier');
runtime_check(($created['context']['contains_clinical_content'] ?? true) === false, 'context asserts no clinical content');
runtime_check(($created['context']['contains_patient_identity'] ?? true) === false, 'context asserts no patient identity');
runtime_check(($created['context']['contains_bearer_authorization'] ?? true) === false, 'context asserts no bearer authorization');
runtime_check(!isset($created['recipient_platform_uuid']) && !isset($created['context']['recipient_platform_uuid']), 'recipient platform UUID is not copied into notification metadata');
runtime_check(!isset($created['patient_name']) && !isset($created['diagnosis']) && !isset($created['remedy']) && !isset($created['dose']), 'created payload contains no prohibited clinical fields');

$replay = SUN_CF01_Clinical_Notifications::request(valid_request());
runtime_check($replay === 101, 'idempotent replay returns the same notification identity');
runtime_check(count(SUN_Core::$dedupe) === 1, 'idempotent replay creates no second dedupe identity');

SUN_Core::$preferences['categories']['appointments'] = false;
$created_before_suppression = count(SUN_Core::$created);
$suppressed = SUN_CF01_Clinical_Notifications::request(valid_request(['dedupe_key' => 'dedupe.suppressed123']));
runtime_check(error_code($suppressed) === 'sun_cf01_notification_suppressed', 'routine category opt-out returns an explicit suppressed outcome');
runtime_check(($suppressed instanceof WP_Error) && (($suppressed->get_error_data()['retryable'] ?? true) === false), 'preference suppression is explicitly non-retryable');
runtime_check(count(SUN_Core::$created) === $created_before_suppression, 'suppressed notification never reaches the delivery core');
SUN_Core::$preferences['categories']['appointments'] = true;
SUN_Core::$mode = 'database_failure';
$failed = SUN_CF01_Clinical_Notifications::request(valid_request(['dedupe_key' => 'dedupe.databasefail123']));
runtime_check(error_code($failed) === 'sun_cf01_notification_not_created', 'database failure remains distinct from preference suppression');
SUN_Core::$mode = 'normal';

$mandatory = SUN_CF01_Clinical_Notifications::request(valid_request([
    'template_key' => 'clinical_access_alert',
    'action_category' => 'access_security',
    'urgency' => 'critical',
    'mandatory_policy' => 'security_required',
    'destination_reference' => 'cf01-access.abcdef1234567890',
    'dedupe_key' => 'dedupe.access123456',
]));
runtime_check(is_int($mandatory) && $mandatory > 0, 'approved access-security alert is created');
$mandatory_args = SUN_Core::$created[count(SUN_Core::$created) - 1];
runtime_check(($mandatory_args['category'] ?? '') === 'security' && ($mandatory_args['priority'] ?? '') === 'critical', 'mandatory exception is limited to critical security delivery');
runtime_check(($mandatory_args['external_title'] ?? '') === 'Private notification', 'mandatory lock-screen preview remains generic');

$context = $created['context'];
$GLOBALS['wpdb']->row = [
    'id' => 101,
    'user_id' => 42,
    'type' => 'cf01_follow_up_due',
    'source' => 'cf01',
    'context' => json_encode($context, JSON_UNESCAPED_SLASHES),
    'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
];
unset($GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve']);
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'destination resolution fails closed without native CF-01 authorization');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function ($default, string $reference, int $user_id): array {
    return ['authorized' => true, 'contains_bearer_authorization' => false, 'url' => 'https://evil.example/private'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'cross-origin destination is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function (): array {
    return ['authorized' => true, 'contains_bearer_authorization' => false, 'url' => 'http://example.com/private'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'non-HTTPS destination is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function (): array {
    return ['authorized' => true, 'contains_bearer_authorization' => true, 'url' => 'https://example.com/clinical/private'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'resolver-declared bearer authorization is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function (): array {
    return ['authorized' => true, 'contains_bearer_authorization' => false, 'url' => 'https://example.com/clinical/private?access_token=secret'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'bearer-like query parameter is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function (): array {
    return ['authorized' => true, 'contains_bearer_authorization' => false, 'url' => 'https://example.com/clinical/private?next%5Bauthorization_token%5D=secret'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'encoded nested bearer key is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function (): array {
    return ['authorized' => true, 'contains_bearer_authorization' => false, 'url' => 'https://example.com/clinical/private?next=aaaaaaaaaa.bbbbbbbbbb.cccccccccc'];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'JWT-like query value is rejected');

$GLOBALS['cf01_filters']['sun_cf01_clinical_destination_resolve'] = static function ($default, string $reference, int $user_id): array {
    return [
        'authorized' => $reference === 'cf01-destination.abcdef1234567890' && $user_id === 42,
        'contains_bearer_authorization' => false,
        'url' => 'https://example.com/clinical/private-record',
    ];
};
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check($resolution instanceof WP_REST_Response, 'same-origin non-bearer destination resolves after native authorization');
runtime_check(($resolution->data['authorization_rechecked'] ?? false) === true, 'destination response confirms action-time authorization');
runtime_check(($resolution->data['bearer_authorization'] ?? true) === false, 'destination response carries no bearer grant');
runtime_check(($resolution->headers['Cache-Control'] ?? '') === 'private, no-store, no-cache, must-revalidate, max-age=0', 'destination response is private and no-store');

$GLOBALS['cf01_current_user'] = 7;
$resolution = SUN_CF01_Clinical_Notifications::resolve_destination(new WP_REST_Request(['id' => 101]));
runtime_check(error_code($resolution) === 'sun_cf01_destination_unavailable', 'cross-recipient notification existence is not disclosed');

$contract = SUN_CF01_Clinical_Notifications::contract();
runtime_check(($contract['delivery_state_grants_no_clinical_authority'] ?? false) === true, 'contract declares delivery state as non-authorizing');
runtime_check(in_array('diagnosis', $contract['prohibited_content'] ?? [], true), 'contract publishes the diagnosis prohibition');
runtime_check(($contract['external_preview']['title'] ?? '') === 'Private notification', 'contract publishes the generic external preview');

echo "File 19 CF-01 runtime/adversarial contracts: $checks PASS, 0 FAIL\n";
