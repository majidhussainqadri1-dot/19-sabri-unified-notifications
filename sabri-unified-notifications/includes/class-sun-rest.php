<?php
defined('ABSPATH') || exit;

final class SUN_REST {
    private const NS = 'sabri-notifications/v1';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route(self::NS, '/health', [
            'methods' => 'GET',
            'callback' => [self::class, 'health'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/notifications', [
            'methods' => 'GET',
            'callback' => [self::class, 'notifications'],
            'permission_callback' => [self::class, 'logged_in'],
        ]);
        register_rest_route(self::NS, '/notifications/read', [
            'methods' => 'POST',
            'callback' => [self::class, 'mark_read'],
            'permission_callback' => [self::class, 'logged_in'],
        ]);
        register_rest_route(self::NS, '/notifications/seen', [
            'methods' => 'POST',
            'callback' => [self::class, 'mark_seen'],
            'permission_callback' => [self::class, 'logged_in'],
        ]);
        register_rest_route(self::NS, '/notifications/archive', [
            'methods' => 'POST',
            'callback' => [self::class, 'archive'],
            'permission_callback' => [self::class, 'logged_in'],
        ]);
        register_rest_route(self::NS, '/preferences', [
            ['methods'=>'GET','callback'=>[self::class,'get_preferences'],'permission_callback'=>[self::class,'logged_in']],
            ['methods'=>'POST','callback'=>[self::class,'save_preferences'],'permission_callback'=>[self::class,'logged_in']],
        ]);
        register_rest_route(self::NS, '/devices', [
            ['methods'=>'GET','callback'=>[self::class,'devices'],'permission_callback'=>[self::class,'logged_in']],
            ['methods'=>'POST','callback'=>[self::class,'register_device'],'permission_callback'=>[self::class,'logged_in']],
        ]);
        register_rest_route(self::NS, '/devices/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'delete_device'],
            'permission_callback' => [self::class, 'logged_in'],
        ]);
        register_rest_route(self::NS, '/test', [
            'methods' => 'POST',
            'callback' => [self::class, 'test_notification'],
            'permission_callback' => static fn() => current_user_can('manage_options'),
        ]);
    }

    public static function logged_in(): bool {
        return is_user_logged_in();
    }

    public static function health(): WP_REST_Response {
        global $wpdb;
        $tables = [];
        foreach (['notifications','preferences','deliveries','devices','templates','audit_log'] as $table) {
            $tables[$table] = SUN_DB::table_exists($table);
        }
        return rest_ensure_response([
            'ok' => !in_array(false, $tables, true),
            'version' => SUN_VERSION,
            'tables' => $tables,
            'page' => SUN_Utils::page_url(),
            'emailEnabled' => (bool) get_option('sun_email_enabled', 1),
            'smsConfigured' => trim((string) get_option('sun_sms_webhook_url', '')) !== '' || trim((string) get_option('sn_sms_webhook_url', '')) !== '',
            'pushConfigured' => trim((string) get_option('sun_push_webhook_url', '')) !== '',
            'marketplaceDetected' => SUN_DB::external_table_exists($wpdb->prefix . 'smp_notifications'),
            'networkDetected' => SUN_DB::external_table_exists($wpdb->prefix . 'sn_notifications'),
        ]);
    }

    public static function notifications(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $user_id = get_current_user_id();
        SUN_Integrations::sync_current_user($user_id);
        $category = sanitize_key((string) $request->get_param('category'));
        $unread_only = filter_var($request->get_param('unread'), FILTER_VALIDATE_BOOLEAN);
        $after_id = absint($request->get_param('after_id'));
        $page = max(1, absint($request->get_param('page')) ?: 1);
        $per_page = max(5, min(100, absint($request->get_param('per_page')) ?: 30));
        $offset = ($page - 1) * $per_page;

        $where = ['user_id=%d', 'archived_at IS NULL', '(expires_at IS NULL OR expires_at>%s)'];
        $params = [$user_id, SUN_Utils::now()];
        if ($category && array_key_exists($category, SUN_Utils::allowed_categories())) {
            $where[] = 'category=%s';
            $params[] = $category;
        }
        if ($unread_only) $where[] = 'read_at IS NULL';
        if ($after_id) {
            $where[] = 'id>%d';
            $params[] = $after_id;
        }
        $sql_where = implode(' AND ', $where);
        $query = 'SELECT * FROM ' . SUN_DB::table('notifications') . " WHERE $sql_where ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        $rows = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        $items = array_map([SUN_Core::class, 'format_notification'], $rows ?: []);
        $unread = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . SUN_DB::table('notifications') . ' WHERE user_id=%d AND read_at IS NULL AND archived_at IS NULL AND (expires_at IS NULL OR expires_at>%s)',
            $user_id,
            SUN_Utils::now()
        ));
        $latest_id = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COALESCE(MAX(id),0) FROM ' . SUN_DB::table('notifications') . ' WHERE user_id=%d',
            $user_id
        ));
        return rest_ensure_response(['notifications'=>$items,'unread'=>$unread,'latestId'=>$latest_id,'page'=>$page]);
    }

    public static function mark_read(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        $user_id = get_current_user_id();
        $all = filter_var($request->get_param('all'), FILTER_VALIDATE_BOOLEAN);
        $ids = array_values(array_filter(array_map('absint', (array) $request->get_param('ids'))));
        $now = SUN_Utils::now();
        if (!$all && !$ids) return new WP_Error('missing_ids', 'Select at least one notification.', ['status'=>400]);
        if ($all) {
            $source_rows = $wpdb->get_results($wpdb->prepare(
                'SELECT source,source_id FROM ' . SUN_DB::table('notifications') . ' WHERE user_id=%d AND read_at IS NULL',
                $user_id
            ), ARRAY_A);
            $wpdb->query($wpdb->prepare(
                'UPDATE ' . SUN_DB::table('notifications') . ' SET read_at=%s,seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND read_at IS NULL',
                $now,$now,$now,$user_id
            ));
        } else {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $source_rows = $wpdb->get_results($wpdb->prepare(
                'SELECT source,source_id FROM ' . SUN_DB::table('notifications') . " WHERE user_id=%d AND id IN ($placeholders)",
                $user_id,
                ...$ids
            ), ARRAY_A);
            $wpdb->query($wpdb->prepare(
                'UPDATE ' . SUN_DB::table('notifications') . " SET read_at=%s,seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($placeholders)",
                $now,$now,$now,$user_id,
                ...$ids
            ));
        }
        SUN_Integrations::propagate_read($source_rows ?: []);
        return rest_ensure_response(['ok'=>true]);
    }

    public static function mark_seen(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $user_id = get_current_user_id();
        $ids = array_values(array_filter(array_map('absint', (array) $request->get_param('ids'))));
        if (!$ids) return rest_ensure_response(['ok'=>true]);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $now = SUN_Utils::now();
        $wpdb->query($wpdb->prepare(
            'UPDATE ' . SUN_DB::table('notifications') . " SET seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($placeholders)",
            $now,$now,$user_id,
            ...$ids
        ));
        return rest_ensure_response(['ok'=>true]);
    }

    public static function archive(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        $user_id = get_current_user_id();
        $ids = array_values(array_filter(array_map('absint', (array) $request->get_param('ids'))));
        if (!$ids) return new WP_Error('missing_ids','Select at least one notification.',['status'=>400]);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $now = SUN_Utils::now();
        $wpdb->query($wpdb->prepare(
            'UPDATE ' . SUN_DB::table('notifications') . " SET archived_at=%s,read_at=COALESCE(read_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($placeholders)",
            $now,$now,$now,$user_id,
            ...$ids
        ));
        return rest_ensure_response(['ok'=>true]);
    }

    public static function get_preferences(): WP_REST_Response {
        return rest_ensure_response(['preferences'=>SUN_Core::get_preferences(get_current_user_id()),'categories'=>SUN_Utils::allowed_categories()]);
    }

    public static function save_preferences(WP_REST_Request $request): WP_REST_Response {
        $params = $request->get_json_params();
        if (!is_array($params)) $params = [];
        return rest_ensure_response(['preferences'=>SUN_Core::save_preferences(get_current_user_id(), $params)]);
    }

    public static function devices(): WP_REST_Response {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT id,device_type,device_name,enabled,last_seen_at,created_at FROM ' . SUN_DB::table('devices') . ' WHERE user_id=%d ORDER BY id DESC',
            get_current_user_id()
        ), ARRAY_A);
        return rest_ensure_response(['devices'=>$rows ?: []]);
    }

    public static function register_device(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;
        if (!SUN_Utils::rate_limit('device:' . get_current_user_id(), 10, HOUR_IN_SECONDS)) return new WP_Error('rate_limited','Too many device registrations.',['status'=>429]);
        $token = trim((string) $request->get_param('token'));
        if ($token === '' || strlen($token) > 8192) return new WP_Error('invalid_token','A valid device token is required.',['status'=>400]);
        $hash = hash('sha256', $token);
        $now = SUN_Utils::now();
        $metadata = $request->get_param('metadata');
        $metadata = is_array($metadata) ? $metadata : [];
        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . SUN_DB::table('devices') . ' (user_id,device_type,device_name,token,token_hash,endpoint,metadata,enabled,last_seen_at,created_at,updated_at) VALUES (%d,%s,%s,%s,%s,%s,%s,1,%s,%s,%s) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),device_type=VALUES(device_type),device_name=VALUES(device_name),endpoint=VALUES(endpoint),metadata=VALUES(metadata),enabled=1,last_seen_at=VALUES(last_seen_at),updated_at=VALUES(updated_at)',
            get_current_user_id(),
            sanitize_key((string) $request->get_param('device_type')) ?: 'web',
            sanitize_text_field((string) $request->get_param('device_name')),
            $token,
            $hash,
            esc_url_raw((string) $request->get_param('endpoint')),
            SUN_Utils::json_encode($metadata),
            $now,$now,$now
        ));
        $id = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . SUN_DB::table('devices') . ' WHERE token_hash=%s',$hash));
        return rest_ensure_response(['ok'=>true,'id'=>$id]);
    }

    public static function delete_device(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $wpdb->delete(SUN_DB::table('devices'), ['id'=>absint($request['id']),'user_id'=>get_current_user_id()], ['%d','%d']);
        return rest_ensure_response(['ok'=>true]);
    }

    public static function test_notification(WP_REST_Request $request): WP_REST_Response {
        $user_id = absint($request->get_param('user_id')) ?: get_current_user_id();
        $id = SUN_Core::create([
            'user_id'=>$user_id,'actor_user_id'=>get_current_user_id(),'category'=>'system','type'=>'system_test','priority'=>'normal',
            'title'=>'Notification system test','body'=>'The unified notification engine is working correctly.','link'=>SUN_Utils::page_url(),
            'dedupe_key'=>'admin-test:' . wp_generate_uuid4(),'allow_self'=>true,
        ]);
        return rest_ensure_response(['ok'=>$id>0,'notificationId'=>$id]);
    }
}
