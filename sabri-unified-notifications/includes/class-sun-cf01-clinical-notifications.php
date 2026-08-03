<?php
/**
 * File 19-owned, privacy-minimal notification request contract for CF-01.
 *
 * Producers cannot supply notification copy, patient identifiers, clinical
 * narrative, attachment details, URLs or credentials. File 19 renders fixed
 * generic templates and stores only an opaque, non-authorizing destination
 * reference. The final destination is resolved after click-time authentication
 * and native CF-01 authorization.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

final class SUN_CF01_Clinical_Notifications {
    public const CONTRACT_NAME = 'sun.cf01.notification-request';
    public const CONTRACT_VERSION = '1.0.0';
    private const DEFAULT_TTL = 604800; // 7 days.
    private const MIN_TTL = 300;
    private const MAX_TTL = 2592000; // 30 days.
    private const ALLOWED_KEYS = [
        'recipient_platform_uuid',
        'template_key',
        'action_category',
        'destination_reference',
        'urgency',
        'expires_at',
        'mandatory_policy',
        'correlation_id',
        'dedupe_key',
        'producer_contract',
        'producer_version',
    ];
    private const ACTION_CATEGORIES = [
        'review',
        'follow_up',
        'consent',
        'records',
        'export',
        'access_security',
        'attachment_status',
    ];
    private const URGENCIES = ['low', 'normal', 'high', 'critical'];
    private const MANDATORY_POLICIES = ['none', 'security_required'];
    private const TEMPLATES = [
        'clinical_action_required' => [
            'category' => 'appointments',
            'type' => 'cf01_clinical_action_required',
            'title' => 'Private action required',
            'body' => 'Sign in to review a protected action.',
            'allowed_actions' => ['review', 'consent', 'records'],
            'mandatory' => false,
        ],
        'follow_up_due' => [
            'category' => 'appointments',
            'type' => 'cf01_follow_up_due',
            'title' => 'Private follow-up reminder',
            'body' => 'Sign in to review a protected follow-up reminder.',
            'allowed_actions' => ['follow_up'],
            'mandatory' => false,
        ],
        'follow_up_overdue' => [
            'category' => 'appointments',
            'type' => 'cf01_follow_up_overdue',
            'title' => 'Private follow-up reminder',
            'body' => 'Sign in to review a protected follow-up reminder.',
            'allowed_actions' => ['follow_up'],
            'mandatory' => false,
        ],
        'clinical_record_updated' => [
            'category' => 'appointments',
            'type' => 'cf01_clinical_record_updated',
            'title' => 'Private record update',
            'body' => 'Sign in to review a protected record update.',
            'allowed_actions' => ['records', 'review'],
            'mandatory' => false,
        ],
        'consent_action_required' => [
            'category' => 'appointments',
            'type' => 'cf01_consent_action_required',
            'title' => 'Private consent action',
            'body' => 'Sign in to review a protected consent action.',
            'allowed_actions' => ['consent'],
            'mandatory' => false,
        ],
        'clinical_export_ready' => [
            'category' => 'appointments',
            'type' => 'cf01_clinical_export_ready',
            'title' => 'Private export update',
            'body' => 'Sign in to review a protected export update.',
            'allowed_actions' => ['export'],
            'mandatory' => false,
        ],
        'clinical_attachment_status' => [
            'category' => 'appointments',
            'type' => 'cf01_clinical_attachment_status',
            'title' => 'Private file update',
            'body' => 'Sign in to review a protected file update.',
            'allowed_actions' => ['attachment_status'],
            'mandatory' => false,
        ],
        'clinical_access_alert' => [
            'category' => 'security',
            'type' => 'cf01_clinical_access_alert',
            'title' => 'Private access alert',
            'body' => 'Sign in to review a protected access alert.',
            'allowed_actions' => ['access_security'],
            'mandatory' => true,
        ],
        'break_glass_access_alert' => [
            'category' => 'security',
            'type' => 'cf01_break_glass_access_alert',
            'title' => 'Private access alert',
            'body' => 'Sign in to review a protected access alert.',
            'allowed_actions' => ['access_security'],
            'mandatory' => true,
        ],
    ];

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route('sabri-notifications/v1', '/clinical/(?P<id>\d+)/destination', [
            'methods' => 'GET',
            'callback' => [self::class, 'resolve_destination'],
            'permission_callback' => static function (): bool {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * Create a privacy-minimal notification through a fail-closed owner contract.
     *
     * @return int|WP_Error Notification ID or a structured failure.
     */
    public static function request(array $request) {
        $unknown = array_diff(array_keys($request), self::ALLOWED_KEYS);
        if ($unknown !== []) {
            return self::error('sun_cf01_unknown_field', 'The clinical notification request contains unsupported fields.', 400);
        }
        foreach ($request as $value) {
            if (is_array($value) || is_object($value) || is_resource($value)) {
                return self::error('sun_cf01_complex_value_forbidden', 'Clinical notification request fields must be bounded scalar values.', 400);
            }
        }

        $recipient_uuid = strtolower(trim((string) ($request['recipient_platform_uuid'] ?? '')));
        if (!self::valid_uuid4($recipient_uuid)) {
            return self::error('sun_cf01_recipient_invalid', 'A valid opaque recipient platform UUID is required.', 400);
        }
        $template_key = sanitize_key((string) ($request['template_key'] ?? ''));
        $template = self::TEMPLATES[$template_key] ?? null;
        if (!is_array($template)) {
            return self::error('sun_cf01_template_invalid', 'Select an approved privacy-minimal notification template.', 400);
        }
        $action_category = sanitize_key((string) ($request['action_category'] ?? ''));
        if (!in_array($action_category, self::ACTION_CATEGORIES, true)
            || !in_array($action_category, (array) $template['allowed_actions'], true)) {
            return self::error('sun_cf01_action_category_invalid', 'The notification action category does not match the approved template.', 400);
        }
        $destination_reference = trim((string) ($request['destination_reference'] ?? ''));
        if (!self::valid_opaque($destination_reference, 16, 191)) {
            return self::error('sun_cf01_destination_reference_invalid', 'A bounded opaque destination reference is required.', 400);
        }
        $urgency = sanitize_key((string) ($request['urgency'] ?? 'normal'));
        if (!in_array($urgency, self::URGENCIES, true)) {
            return self::error('sun_cf01_urgency_invalid', 'The notification urgency is invalid.', 400);
        }
        $mandatory_policy = sanitize_key((string) ($request['mandatory_policy'] ?? 'none'));
        if (!in_array($mandatory_policy, self::MANDATORY_POLICIES, true)) {
            return self::error('sun_cf01_mandatory_policy_invalid', 'The mandatory-delivery policy is invalid.', 400);
        }
        $template_mandatory = !empty($template['mandatory']);
        if (($mandatory_policy === 'security_required') !== $template_mandatory) {
            return self::error('sun_cf01_mandatory_policy_mismatch', 'Mandatory delivery is limited to approved access-security alerts.', 400);
        }
        if ($template_mandatory && $urgency !== 'critical') {
            return self::error('sun_cf01_mandatory_urgency_invalid', 'Mandatory access-security alerts must be critical.', 400);
        }

        $correlation_id = trim((string) ($request['correlation_id'] ?? ''));
        $dedupe_key = trim((string) ($request['dedupe_key'] ?? ''));
        if (!self::valid_opaque($correlation_id, 8, 128) || !self::valid_opaque($dedupe_key, 8, 128)) {
            return self::error('sun_cf01_request_identity_invalid', 'Bounded opaque correlation and deduplication keys are required.', 400);
        }
        $producer_contract = trim((string) ($request['producer_contract'] ?? ''));
        $producer_version = trim((string) ($request['producer_version'] ?? ''));
        if (!preg_match('/^[a-z][a-z0-9._-]{2,99}$/', $producer_contract)
            || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $producer_version)) {
            return self::error('sun_cf01_producer_identity_invalid', 'A valid producer contract and version are required.', 400);
        }
        $expires_at = self::expiry((string) ($request['expires_at'] ?? ''));
        if ($expires_at === '') {
            return self::error('sun_cf01_expiry_invalid', 'The notification expiry must be within the approved future window.', 400);
        }

        $authorized = apply_filters(
            'sun_cf01_notification_request_authorized',
            false,
            [
                'recipient_platform_uuid' => $recipient_uuid,
                'template_key' => $template_key,
                'action_category' => $action_category,
                'destination_reference' => $destination_reference,
                'urgency' => $urgency,
                'mandatory_policy' => $mandatory_policy,
                'correlation_id' => $correlation_id,
                'producer_contract' => $producer_contract,
                'producer_version' => $producer_version,
                'expires_at' => $expires_at,
            ]
        );
        if ($authorized !== true) {
            return self::error('sun_cf01_request_not_authorized', 'The clinical notification producer is not authorized.', 403);
        }

        $user_id = apply_filters(
            'sun_cf01_resolve_recipient_platform_uuid',
            0,
            $recipient_uuid,
            $producer_contract,
            $producer_version
        );
        $user_id = is_int($user_id) ? $user_id : 0;
        if ($user_id <= 0 || !get_userdata($user_id)) {
            return self::error('sun_cf01_recipient_unavailable', 'The clinical notification recipient is unavailable.', 404);
        }

        $priority = self::priority($urgency, $template_mandatory);
        $stable_dedupe = hash(
            'sha256',
            self::CONTRACT_NAME . '|' . $recipient_uuid . '|' . $producer_contract . '|' . $dedupe_key . '|' . $template_key . '|' . $destination_reference
        );
        $context = [
            'contract' => self::CONTRACT_NAME,
            'contract_version' => self::CONTRACT_VERSION,
            'template_key' => $template_key,
            'action_category' => $action_category,
            'destination_reference' => $destination_reference,
            'producer_contract' => $producer_contract,
            'producer_version' => $producer_version,
            'correlation_id' => $correlation_id,
            'mandatory_policy' => $mandatory_policy,
            'contains_clinical_content' => false,
            'contains_patient_identity' => false,
            'contains_attachment_content' => false,
            'contains_bearer_authorization' => false,
            'requires_click_time_authentication' => true,
            'requires_click_time_cf01_authorization' => true,
            'delivery_state_is_not_clinical_state' => true,
        ];

        $id = SUN_Core::create([
            'user_id' => $user_id,
            'actor_user_id' => 0,
            'category' => (string) $template['category'],
            'type' => (string) $template['type'],
            'priority' => $priority,
            'sensitivity' => 'clinical',
            'title' => (string) $template['title'],
            'body' => (string) $template['body'],
            'external_title' => 'Private notification',
            'external_body' => 'Sign in to view this protected notification.',
            'link' => SUN_Utils::page_url(),
            'entity_type' => '',
            'entity_id' => 0,
            'source' => 'cf01',
            'source_id' => 0,
            'context' => $context,
            'expires_at' => $expires_at,
            'dedupe_key' => $stable_dedupe,
            'allow_self' => true,
        ]);
        if ($id <= 0) {
            return self::error('sun_cf01_notification_not_created', 'The clinical notification could not be created.', 409);
        }
        return $id;
    }

    /**
     * Resolve the final destination after recipient and native-owner checks.
     *
     * @return WP_REST_Response|WP_Error
     */
    public static function resolve_destination(WP_REST_Request $request) {
        global $wpdb;
        $notification_id = absint($request['id']);
        $user_id = get_current_user_id();
        if ($notification_id <= 0 || $user_id <= 0 || !SUN_DB::table_exists('notifications')) {
            return self::not_found();
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT id,user_id,type,source,context,expires_at FROM ' . SUN_DB::table('notifications') . ' WHERE id=%d AND user_id=%d AND source=%s LIMIT 1',
            $notification_id,
            $user_id,
            'cf01'
        ), ARRAY_A);
        if (!is_array($row)
            || !str_starts_with((string) ($row['type'] ?? ''), 'cf01_')
            || (!empty($row['expires_at']) && strtotime((string) $row['expires_at'] . ' UTC') <= time())) {
            return self::not_found();
        }
        $context = SUN_Utils::json_decode($row['context'] ?? '', []);
        if (($context['contract'] ?? '') !== self::CONTRACT_NAME
            || ($context['contract_version'] ?? '') !== self::CONTRACT_VERSION
            || empty($context['requires_click_time_cf01_authorization'])
            || !empty($context['contains_bearer_authorization'])) {
            return self::not_found();
        }
        $destination_reference = (string) ($context['destination_reference'] ?? '');
        if (!self::valid_opaque($destination_reference, 16, 191)) {
            return self::not_found();
        }

        $resolution = apply_filters(
            'sun_cf01_clinical_destination_resolve',
            null,
            $destination_reference,
            $user_id,
            $context,
            $notification_id
        );
        if (!is_array($resolution)
            || ($resolution['authorized'] ?? false) !== true
            || ($resolution['contains_bearer_authorization'] ?? true) !== false
            || !self::safe_destination((string) ($resolution['url'] ?? ''))) {
            return self::not_found();
        }

        $response = rest_ensure_response([
            'url' => (string) $resolution['url'],
            'authorization_rechecked' => true,
            'bearer_authorization' => false,
            'cache_control' => 'private, no-store',
        ]);
        $response->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        return $response;
    }

    public static function contract(): array {
        return [
            'contract' => self::CONTRACT_NAME,
            'contract_version' => self::CONTRACT_VERSION,
            'producer' => 'File 19',
            'runtime_version' => defined('SUN_VERSION') ? SUN_VERSION : '',
            'templates' => array_keys(self::TEMPLATES),
            'action_categories' => self::ACTION_CATEGORIES,
            'external_preview' => [
                'title' => 'Private notification',
                'body' => 'Sign in to view this protected notification.',
            ],
            'prohibited_content' => [
                'patient_identity',
                'diagnosis',
                'symptoms',
                'remedy',
                'potency',
                'dose',
                'clinical_note',
                'attachment_name_or_content',
                'guardian_detail',
                'break_glass_reason',
                'signed_url',
                'session_or_bearer_credential',
            ],
            'destination_requires_click_time_authentication' => true,
            'destination_requires_native_cf01_authorization' => true,
            'delivery_state_grants_no_clinical_authority' => true,
        ];
    }

    private static function valid_uuid4(string $value): bool {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value) === 1;
    }

    private static function valid_opaque(string $value, int $minimum, int $maximum): bool {
        $length = strlen($value);
        return $length >= $minimum
            && $length <= $maximum
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $value) === 1
            && stripos($value, 'bearer') === false
            && stripos($value, 'token') === false
            && stripos($value, 'password') === false;
    }

    private static function expiry(string $value): string {
        $now = time();
        if ($value === '') {
            return gmdate('Y-m-d H:i:s', $now + self::DEFAULT_TTL);
        }
        if (strlen($value) > 40 || preg_match('/^\d{4}-\d{2}-\d{2}[T ][0-2]\d:[0-5]\d:[0-5]\d(?:Z| ?UTC)?$/', trim($value)) !== 1) {
            return '';
        }
        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp < $now + self::MIN_TTL || $timestamp > $now + self::MAX_TTL) {
            return '';
        }
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private static function priority(string $urgency, bool $mandatory): string {
        if ($mandatory) {
            return 'critical';
        }
        return in_array($urgency, self::URGENCIES, true) ? $urgency : 'normal';
    }

    private static function safe_destination(string $url): bool {
        if ($url === '') {
            return false;
        }
        $parts = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($parts) || !is_array($home)) {
            return false;
        }
        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== strtolower((string) ($home['host'] ?? ''))
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])) {
            return false;
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        $home_port = isset($home['port']) ? (int) $home['port'] : 443;
        if ($port !== $home_port) {
            return false;
        }
        $query = (string) ($parts['query'] ?? '');
        if ($query !== '') {
            parse_str($query, $query_values);
            $stack = [is_array($query_values) ? $query_values : []];
            while ($stack !== []) {
                $values = array_pop($stack);
                foreach ($values as $key => $value) {
                    $key = strtolower((string) $key);
                    if (preg_match('/(?:token|authorization|auth|bearer|signature|signed|secret|password|session|api[_-]?key|access[_-]?key)/i', $key)) {
                        return false;
                    }
                    if (is_array($value)) {
                        $stack[] = $value;
                        continue;
                    }
                    $value = (string) $value;
                    if (stripos($value, 'bearer ') !== false
                        || preg_match('/^[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}$/', $value)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private static function not_found(): WP_Error {
        return self::error('sun_cf01_destination_unavailable', 'The protected notification destination is unavailable.', 404);
    }

    private static function error(string $code, string $message, int $status): WP_Error {
        return new WP_Error($code, $message, ['status' => $status]);
    }
}
