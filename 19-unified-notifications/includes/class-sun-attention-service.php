<?php
/** Intelligent attention, inbox state, focus, search, history and live-notification service. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Attention_Service {
    /** @var SUN_Auth */ private $auth;
    /** @param SUN_Auth $auth Authorization. */
    public function __construct( SUN_Auth $auth ) { $this->auth = $auth; }

    /** @return string[] */
    public function focus_modes() { return array( 'balanced', 'study', 'clinic', 'work', 'sleep', 'travel', 'essential', 'custom' ); }

    /** @param int $user_id User ID. @return array<string,mixed> */
    public function profile( $user_id ) {
        global $wpdb;
        $user_id = absint( $user_id );
        $defaults = array(
            'user_id' => $user_id, 'focus_mode' => 'balanced', 'essential_only' => false,
            'hourly_budget' => 20, 'daily_budget' => 120, 'best_time_enabled' => false,
            'best_time_local' => '', 'ai_summary_enabled' => true, 'history_days' => 90,
            'muted_until' => null, 'version' => 0,
        );
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SUN_Database::table( 'attention_profiles' ) . ' WHERE user_id=%d LIMIT 1', $user_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( ! $row ) { return $defaults; }
        return array_merge( $defaults, array(
            'focus_mode' => in_array( $row['focus_mode'], $this->focus_modes(), true ) ? $row['focus_mode'] : 'balanced',
            'essential_only' => (bool) $row['essential_only'],
            'hourly_budget' => (int) $row['hourly_budget'],
            'daily_budget' => (int) $row['daily_budget'],
            'best_time_enabled' => (bool) $row['best_time_enabled'],
            'best_time_local' => (string) $row['best_time_local'],
            'ai_summary_enabled' => (bool) $row['ai_summary_enabled'],
            'history_days' => (int) $row['history_days'],
            'muted_until' => $row['muted_until'],
            'version' => (int) $row['version'],
        ) );
    }

    /** @param int $user_id User ID. @param array<string,mixed> $input Input. @return array<string,mixed>|WP_Error */
    public function update_profile( $user_id, array $input ) {
        global $wpdb;
        $user_id = absint( $user_id );
        $current = $this->profile( $user_id );
        $expected = absint( $input['version'] ?? $current['version'] );
        if ( $expected !== (int) $current['version'] ) {
            return new WP_Error( 'sun_attention_profile_conflict', __( 'Your attention settings changed in another session. Reload and try again.', 'sabri-unified-notifications' ), array( 'status' => 409 ) );
        }
        $mode = sanitize_key( (string) ( $input['focus_mode'] ?? $current['focus_mode'] ) );
        if ( ! in_array( $mode, $this->focus_modes(), true ) ) { $mode = 'balanced'; }
        $best_time = $this->valid_time( (string) ( $input['best_time_local'] ?? $current['best_time_local'] ) );
        $muted_until = $this->valid_future_datetime( $input['muted_until'] ?? null );
        $now = SUN_Database::now();
        $data = array(
            'user_id' => $user_id,
            'focus_mode' => $mode,
            'essential_only' => ! empty( $input['essential_only'] ) ? 1 : 0,
            'hourly_budget' => max( 1, min( 200, absint( $input['hourly_budget'] ?? $current['hourly_budget'] ) ) ),
            'daily_budget' => max( 1, min( 2000, absint( $input['daily_budget'] ?? $current['daily_budget'] ) ) ),
            'best_time_enabled' => ! empty( $input['best_time_enabled'] ) ? 1 : 0,
            'best_time_local' => $best_time ?: null,
            'ai_summary_enabled' => array_key_exists( 'ai_summary_enabled', $input ) ? ( ! empty( $input['ai_summary_enabled'] ) ? 1 : 0 ) : ( $current['ai_summary_enabled'] ? 1 : 0 ),
            'history_days' => max( 7, min( 365, absint( $input['history_days'] ?? $current['history_days'] ) ) ),
            'muted_until' => $muted_until,
            'version' => (int) $current['version'] + 1,
            'updated_at' => $now,
        );
        $table = SUN_Database::table( 'attention_profiles' );
        if ( 0 === (int) $current['version'] ) {
            $data['created_at'] = $now;
            if ( false === $wpdb->insert( $table, $data ) ) { return new WP_Error( 'sun_attention_profile_write_failed', __( 'Attention settings could not be saved.', 'sabri-unified-notifications' ), array( 'status' => 500 ) ); }
        } else {
            $updated = $wpdb->update( $table, $data, array( 'user_id' => $user_id, 'version' => $expected ) );
            if ( 1 !== (int) $updated ) { return new WP_Error( 'sun_attention_profile_conflict', __( 'Your attention settings changed in another session. Reload and try again.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
        }
        SUN_Audit::record( 'attention_profile_changed', 'attention_profile', (string) $user_id, array( 'focus_mode' => $mode, 'purpose' => 'user_choice' ), $user_id );
        return $this->profile( $user_id );
    }

    /**
     * Adjust policy output before projection. Essential security/safety/system facts are never downgraded.
     * @param int $user_id User ID. @param array<string,mixed> $event Event. @param array<string,mixed> $decision Decision. @return array<string,mixed>
     */
    public function adjust_policy_decision( $user_id, array $event, array $decision ) {
        $profile = $this->profile( $user_id );
        $category = sanitize_key( (string) ( $decision['category'] ?? '' ) );
        $mandatory = ! empty( $decision['mandatory'] ) || in_array( $category, array( 'security', 'safety', 'system' ), true );
        $score = $this->attention_score( $category, (string) ( $decision['priority'] ?? 'normal' ), $event, $profile );
        $decision['attention_score'] = $score;
        $decision['attention_reason'] = $this->importance_reason( $category, $score, $profile, $event );
        if ( $mandatory ) { return $decision; }
        if ( ! empty( $profile['muted_until'] ) && $profile['muted_until'] > SUN_Database::now() ) {
            $decision['suppressed'] = true; $decision['suppress_reason'] = 'temporary_mute'; $decision['deliveries'] = array(); return $decision;
        }
        if ( ! empty( $profile['essential_only'] ) || 'essential' === $profile['focus_mode'] ) {
            $decision['suppressed'] = true; $decision['suppress_reason'] = 'essential_only'; $decision['deliveries'] = array(); return $decision;
        }
        $budget = $this->budget_state( $user_id, $profile );
        $source_capped = $this->source_cap_reached( $user_id, $event, $category );
        $hold_until = null;
        if ( 'sleep' === $profile['focus_mode'] ) { $hold_until = $this->next_local_time( $user_id, '07:00:00' ); }
        elseif ( ! empty( $profile['best_time_enabled'] ) && ! empty( $profile['best_time_local'] ) ) { $hold_until = $this->next_local_time( $user_id, (string) $profile['best_time_local'] ); }
        foreach ( (array) ( $decision['deliveries'] ?? array() ) as &$delivery ) {
            if ( $hold_until && ( empty( $delivery['scheduled_at'] ) || $delivery['scheduled_at'] < $hold_until ) ) { $delivery['scheduled_at'] = $hold_until; }
            if ( $budget['hourly_exceeded'] || $budget['daily_exceeded'] || $source_capped ) {
                $delivery['scheduled_at'] = max( (string) ( $delivery['scheduled_at'] ?? SUN_Database::now() ), $this->next_local_time( $user_id, '08:00:00' ) );
                $delivery['digest_key'] = 'attention-budget:' . gmdate( 'Y-m-d', strtotime( $delivery['scheduled_at'] ) );
            }
        }
        unset( $delivery );
        $decision['budget_state'] = $budget; $decision['source_frequency_capped'] = $source_capped;
        return $decision;
    }

    /** @param string $public_id Notification public ID. @param int $user_id User ID. @param array<string,mixed> $event Event. @param array<string,mixed> $decision Decision. @return void */
    public function on_notification_created( $public_id, $user_id, array $event, array $decision ) {
        global $wpdb;
        $note = $wpdb->get_row( $wpdb->prepare( 'SELECT id,category,priority FROM ' . SUN_Database::table( 'notifications' ) . ' WHERE public_id=%s AND recipient_id=%d LIMIT 1', sanitize_text_field( $public_id ), absint( $user_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( ! $note ) { return; }
        $profile = $this->profile( $user_id );
        $score = isset( $decision['attention_score'] ) ? absint( $decision['attention_score'] ) : $this->attention_score( (string) $note['category'], (string) $note['priority'], $event, $profile );
        $provenance = apply_filters( 'sun_notification_source_provenance', array( 'label' => (string) ( $event['owner'] ?? $event['producer'] ?? 'Platform' ), 'kind' => 'sabri-system' === ( $event['producer'] ?? '' ) ? 'system' : 'module', 'verified' => 'sabri-system' === ( $event['producer'] ?? '' ) ), $event, $user_id );
        if ( ! is_array( $provenance ) ) { $provenance = array(); }
        $group_material = (string) ( $event['event_type'] ?? '' ) . '|' . (string) ( $event['subject']['type'] ?? '' ) . '|' . (string) ( $event['subject']['id'] ?? '' );
        if ( ! empty( $event['data']['group_key'] ) ) { $group_material = sanitize_text_field( (string) $event['data']['group_key'] ); }
        $group_key = hash( 'sha256', $group_material );
        $actions = $this->sanitize_actions( $event['data']['actions'] ?? array() );
        $meta = array( 'actions' => $actions, 'why' => array( 'policy_key' => sanitize_key( (string) ( $decision['policy_key'] ?? '' ) ), 'event_type' => sanitize_text_field( (string) ( $event['event_type'] ?? '' ) ), 'producer' => sanitize_key( (string) ( $event['producer'] ?? '' ) ), 'subscription_frequency' => sanitize_key( (string) ( $decision['subscription_frequency'] ?? '' ) ), 'attention_reason' => sanitize_text_field( (string) ( $decision['attention_reason'] ?? '' ) ) ) );
        $cipher = SUN_Crypto::encrypt( SUN_Database::canonical_json( $meta ) ); if ( is_wp_error( $cipher ) ) { $cipher = null; }
        $now = SUN_Database::now(); $table = SUN_Database::table( 'notification_states' );
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$table} (notification_id,user_id,attention_score,attention_reason,group_key,source_label,source_kind,source_verified,live_revision,version,last_activity_at,meta_ciphertext,created_at,updated_at) VALUES (%d,%d,%d,%s,%s,%s,%s,%d,%d,%d,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE attention_score=VALUES(attention_score),attention_reason=VALUES(attention_reason),group_key=VALUES(group_key),source_label=VALUES(source_label),source_kind=VALUES(source_kind),source_verified=VALUES(source_verified),last_activity_at=VALUES(last_activity_at),meta_ciphertext=VALUES(meta_ciphertext),updated_at=VALUES(updated_at)",
            (int) $note['id'], absint( $user_id ), min( 100, $score ), substr( sanitize_text_field( (string) ( $decision['attention_reason'] ?? '' ) ), 0, 191 ), $group_key, substr( sanitize_text_field( (string) ( $provenance['label'] ?? '' ) ), 0, 191 ), substr( sanitize_key( (string) ( $provenance['kind'] ?? 'module' ) ), 0, 50 ), ! empty( $provenance['verified'] ) ? 1 : 0, 1, 1, $now, $cipher, $now, $now
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        SUN_Trace_Service::record( (string) ( $event['trace_id'] ?? $public_id ), 'attention_projection', 'created', array( 'category' => $note['category'], 'score' => $score, 'grouped' => true ), (int) $note['id'] );
    }

    /** @param int $user_id User ID. @param string $public_id ID. @param string $action Action. @param mixed $value Value. @param int|null $expected_version Expected state version. @return array<string,mixed>|WP_Error */
    public function mutate_state( $user_id, $public_id, $action, $value = null, $expected_version = null ) {
        global $wpdb; $row = $this->state_row( $user_id, $public_id ); if ( is_wp_error( $row ) ) { return $row; }
        if ( null !== $expected_version && absint( $expected_version ) !== (int) $row['state_version'] ) { return new WP_Error( 'sun_attention_state_conflict', __( 'This notification changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
        $data = array( 'updated_at' => SUN_Database::now(), 'last_activity_at' => SUN_Database::now(), 'version' => (int) $row['state_version'] + 1 );
        switch ( sanitize_key( $action ) ) {
            case 'pin': $data['pinned_at'] = SUN_Database::now(); break;
            case 'unpin': $data['pinned_at'] = null; break;
            case 'snooze': $until = $this->valid_future_datetime( $value ); if ( ! $until ) { return new WP_Error( 'sun_snooze_invalid', __( 'Choose a valid future reminder time.', 'sabri-unified-notifications' ), array( 'status' => 400 ) ); } $data['snoozed_until'] = $until; break;
            case 'unsnooze': $data['snoozed_until'] = null; break;
            case 'needs_action': $data['action_state'] = 'needs_action'; break;
            case 'done': $data['action_state'] = 'done'; break;
            case 'clear_action': $data['action_state'] = 'none'; break;
            default: return new WP_Error( 'sun_attention_action_invalid', __( 'The notification attention action is invalid.', 'sabri-unified-notifications' ), array( 'status' => 400 ) );
        }
        $updated = $wpdb->update( SUN_Database::table( 'notification_states' ), $data, array( 'id' => (int) $row['state_id'], 'version' => (int) $row['state_version'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( 1 !== (int) $updated ) { return new WP_Error( 'sun_attention_state_conflict', __( 'This notification changed in another session.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
        SUN_Audit::record( 'notification_attention_' . sanitize_key( $action ), 'notification', $public_id, array( 'purpose' => 'user_action' ), $user_id );
        return $this->state_row( $user_id, $public_id );
    }

    /** @param int $user_id User ID. @param array<string,mixed> $args Args. @return array<string,mixed> */ public function priority_inbox( $user_id, array $args = array() ) { $args['sort'] = 'priority'; return $this->search( $user_id, '', $args ); }

    /** @param int $user_id User ID. @param string $query Query. @param array<string,mixed> $args Args. @return array<string,mixed> */
    public function search( $user_id, $query = '', array $args = array() ) {
        global $wpdb; $user_id = absint( $user_id ); $limit = max( 1, min( 50, absint( $args['limit'] ?? 20 ) ) );
        $where = array( 'n.recipient_id=%d', "n.status<>'deleted'" ); $params = array( $user_id );
        if ( '' !== trim( $query ) ) { $like = '%' . $wpdb->esc_like( substr( sanitize_text_field( $query ), 0, 100 ) ) . '%'; $where[] = '(n.title LIKE %s OR n.summary LIKE %s OR n.event_type LIKE %s OR n.category LIKE %s OR s.source_label LIKE %s)'; array_push( $params, $like, $like, $like, $like, $like ); }
        foreach ( array( 'category', 'priority' ) as $field ) { if ( ! empty( $args[ $field ] ) ) { $where[] = "n.{$field}=%s"; $params[] = sanitize_key( $args[ $field ] ); } }
        if ( ! empty( $args['after'] ) ) { $after = gmdate( 'Y-m-d H:i:s', strtotime( (string) $args['after'] ) ?: 0 ); if ( '1970-01-01 00:00:00' !== $after ) { $where[] = 'n.created_at>=%s'; $params[] = $after; } }
        if ( ! empty( $args['pinned'] ) ) { $where[] = 's.pinned_at IS NOT NULL'; }
        if ( ! empty( $args['needs_action'] ) ) { $where[] = "s.action_state='needs_action'"; }
        if ( empty( $args['include_snoozed'] ) ) { $where[] = '(s.snoozed_until IS NULL OR s.snoozed_until<=%s)'; $params[] = SUN_Database::now(); }
        if ( empty( $args['include_revoked'] ) ) { $where[] = 's.revoked_at IS NULL'; }
        $order = 's.pinned_at IS NOT NULL DESC, s.attention_score DESC, n.id DESC'; if ( 'recent' === ( $args['sort'] ?? '' ) ) { $order = 's.pinned_at IS NOT NULL DESC, n.id DESC'; }
        $params[] = $limit;
        $sql = 'SELECT n.public_id,n.event_type,n.category,n.priority,n.icon,n.title,n.summary,n.status,n.created_at,n.expires_at,n.version AS notification_version,s.pinned_at,s.snoozed_until,s.action_state,s.attention_score,s.attention_reason,s.group_key,s.source_label,s.source_kind,s.source_verified,s.live_revision,s.version AS state_version,s.revoked_at FROM ' . SUN_Database::table( 'notifications' ) . ' n LEFT JOIN ' . SUN_Database::table( 'notification_states' ) . ' s ON s.notification_id=n.id WHERE ' . implode( ' AND ', $where ) . " ORDER BY {$order} LIMIT %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        foreach ( (array) $rows as &$row ) { $row['open_url'] = SUN_Deep_Link::wrapper_url( $row['public_id'] ); $row['pinned'] = ! empty( $row['pinned_at'] ); $row['source_verified'] = (bool) $row['source_verified']; }
        unset( $row ); return array( 'items' => (array) $rows, 'query' => $query, 'count' => count( (array) $rows ) );
    }

    /** @param int $user_id User ID. @param int|null $days Days. @return array<string,mixed> */
    public function history( $user_id, $days = null ) { $profile = $this->profile( $user_id ); $days = null === $days ? (int) $profile['history_days'] : max( 1, min( 365, absint( $days ) ) ); return $this->search( $user_id, '', array( 'limit' => 50, 'sort' => 'recent', 'include_snoozed' => true, 'include_revoked' => true, 'after' => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days ) ) ); }

    /** @param int $user_id User ID. @param string $public_id ID. @return array<string,mixed>|WP_Error */
    public function why( $user_id, $public_id ) { $row = $this->state_row( $user_id, $public_id ); if ( is_wp_error( $row ) ) { return $row; } $meta = $this->state_meta( $row ); return array( 'attention_score' => (int) $row['attention_score'], 'attention_reason' => (string) $row['attention_reason'], 'source' => array( 'label' => (string) $row['source_label'], 'kind' => (string) $row['source_kind'], 'verified' => (bool) $row['source_verified'] ), 'why' => (array) ( $meta['why'] ?? array() ), 'actions' => array_values( (array) ( $meta['actions'] ?? array() ) ) ); }

    /** @param int $user_id User ID. @param string $public_id ID. @param string $action_key Action key. @return mixed|WP_Error */
    public function execute_action( $user_id, $public_id, $action_key ) {
        $row = $this->state_row( $user_id, $public_id ); if ( is_wp_error( $row ) ) { return $row; }
        if ( ! $this->auth->is_recipient_eligible( $user_id ) ) { return new WP_Error( 'sun_action_forbidden', __( 'Your current account is not eligible for this action.', 'sabri-unified-notifications' ), array( 'status' => 403 ) ); }
        $meta = $this->state_meta( $row ); $actions = (array) ( $meta['actions'] ?? array() ); $action_key = sanitize_key( $action_key ); $selected = null;
        foreach ( $actions as $action ) { if ( is_array( $action ) && $action_key === ( $action['key'] ?? '' ) ) { $selected = $action; break; } }
        if ( ! $selected ) { return new WP_Error( 'sun_notification_action_unknown', __( 'This notification action is unavailable.', 'sabri-unified-notifications' ), array( 'status' => 404 ) ); }
        $result = apply_filters( 'sun_notification_execute_action', null, $selected, $row, $user_id ); if ( null === $result ) { return new WP_Error( 'sun_notification_action_owner_unavailable', __( 'The owning module is not available to perform this action.', 'sabri-unified-notifications' ), array( 'status' => 503 ) ); } if ( is_wp_error( $result ) ) { return $result; }
        SUN_Audit::record( 'notification_action_executed', 'notification', $public_id, array( 'action' => $action_key, 'purpose' => 'native_owner_action' ), $user_id ); return $result;
    }

    /** @param string $public_id Notification ID. @param array<string,mixed> $patch Patch. @return true|WP_Error */
    public function live_update( $public_id, array $patch ) {
        global $wpdb; $note = $wpdb->get_row( $wpdb->prepare( 'SELECT id,recipient_id,version,status FROM ' . SUN_Database::table( 'notifications' ) . ' WHERE public_id=%s LIMIT 1', sanitize_text_field( $public_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( ! $note || in_array( $note['status'], array( 'deleted', 'expired' ), true ) ) { return new WP_Error( 'sun_live_notification_not_found', __( 'The live notification is unavailable.', 'sabri-unified-notifications' ) ); }
        $data = array( 'updated_at' => SUN_Database::now(), 'version' => (int) $note['version'] + 1 );
        if ( array_key_exists( 'title', $patch ) ) { $data['title'] = substr( sanitize_text_field( (string) $patch['title'] ), 0, 500 ); }
        if ( array_key_exists( 'summary', $patch ) ) { $data['summary'] = substr( sanitize_textarea_field( (string) $patch['summary'] ), 0, 2000 ); }
        if ( ! empty( $patch['expires_at'] ) ) { $future = $this->valid_future_datetime( $patch['expires_at'] ); if ( ! $future ) { return new WP_Error( 'sun_live_expiry_invalid', __( 'The live notification expiry is invalid.', 'sabri-unified-notifications' ) ); } $data['expires_at'] = $future; }
        $updated = $wpdb->update( SUN_Database::table( 'notifications' ), $data, array( 'id' => (int) $note['id'], 'version' => (int) $note['version'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( 1 !== (int) $updated ) { return new WP_Error( 'sun_live_notification_conflict', __( 'The live notification changed concurrently.', 'sabri-unified-notifications' ), array( 'status' => 409 ) ); }
        $wpdb->query( $wpdb->prepare( 'UPDATE ' . SUN_Database::table( 'notification_states' ) . ' SET live_revision=live_revision+1,version=version+1,last_activity_at=%s,updated_at=%s WHERE notification_id=%d', SUN_Database::now(), SUN_Database::now(), (int) $note['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        do_action( 'sun_live_notification_updated', $public_id, $patch ); return true;
    }

    /** @param string $producer Producer. @param string $event_id Event ID. @param string $reason Reason. @return int */
    public function revoke_source( $producer, $event_id, $reason = 'source_withdrawn' ) {
        global $wpdb; $producer = sanitize_key( $producer ); $event_id = substr( sanitize_text_field( $event_id ), 0, 191 ); $reason = substr( sanitize_key( $reason ), 0, 100 ); $now = SUN_Database::now();
        $notes = $wpdb->get_results( $wpdb->prepare( 'SELECT id,public_id FROM ' . SUN_Database::table( 'notifications' ) . " WHERE producer=%s AND event_id=%s AND status NOT IN ('deleted','expired') LIMIT 1000", $producer, $event_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = 0; foreach ( (array) $notes as $note ) {
            $wpdb->update( SUN_Database::table( 'notifications' ), array( 'status' => 'expired', 'title' => __( 'Update withdrawn', 'sabri-unified-notifications' ), 'summary' => __( 'The source withdrew or replaced this update.', 'sabri-unified-notifications' ), 'deep_link' => null, 'updated_at' => $now ), array( 'id' => (int) $note['id'] ) );
            $wpdb->update( SUN_Database::table( 'notification_states' ), array( 'revoked_at' => $now, 'revoke_reason' => $reason, 'updated_at' => $now ), array( 'notification_id' => (int) $note['id'] ) );
            $wpdb->query( $wpdb->prepare( 'UPDATE ' . SUN_Database::table( 'deliveries' ) . " SET status='suppressed',last_error_code=%s,last_error_safe=%s,updated_at=%s WHERE notification_id=%d AND status IN ('queued','failed')", 'source_revoked', 'Source update withdrawn', $now, (int) $note['id'] ) ); ++$count;
        }
        if ( $count ) { SUN_Audit::record( 'notification_source_revoked', 'source_event', $producer . ':' . $event_id, array( 'count' => $count, 'reason' => $reason, 'purpose' => 'source_correction' ), 0 ); } return $count;
    }

    /** @param int $user_id User ID. @param string $device_public_id Device ID. @param array<string,mixed> $input Input. @return array<string,mixed>|WP_Error */
    public function update_device_profile( $user_id, $device_public_id, array $input ) {
        global $wpdb; $user_id = absint( $user_id ); $device_public_id = sanitize_text_field( $device_public_id );
        $owned = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SUN_Database::table( 'devices' ) . ' WHERE public_id=%s AND user_id=%d AND status<>%s', $device_public_id, $user_id, 'revoked' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( ! $owned ) { return new WP_Error( 'sun_device_profile_not_found', __( 'Notification device not found.', 'sabri-unified-notifications' ), array( 'status' => 404 ) ); }
        $categories = array_values( array_intersect( array( 'security','safety','clinic','publishing','learning','social','marketplace','messages','media','system','marketing' ), array_map( 'sanitize_key', (array) ( $input['categories'] ?? array() ) ) ) );
        $channels = array_values( array_intersect( array( 'push','email','sms','whatsapp','rcs' ), array_map( 'sanitize_key', (array) ( $input['channels'] ?? array() ) ) ) );
        $mode = sanitize_key( (string) ( $input['focus_mode'] ?? 'inherit' ) ); if ( 'inherit' !== $mode && ! in_array( $mode, $this->focus_modes(), true ) ) { $mode = 'inherit'; }
        $handoff = array_key_exists( 'handoff', $input ) ? SUN_Crypto::encrypt( SUN_Database::canonical_json( $input['handoff'] ) ) : null; if ( is_wp_error( $handoff ) ) { return $handoff; }
        $table = SUN_Database::table( 'device_profiles' ); $now = SUN_Database::now(); $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id,version FROM {$table} WHERE device_public_id=%s AND user_id=%d LIMIT 1", $device_public_id, $user_id ), ARRAY_A );
        $data = array( 'device_public_id' => $device_public_id, 'user_id' => $user_id, 'focus_mode' => $mode, 'categories_json' => wp_json_encode( $categories ), 'channels_json' => wp_json_encode( $channels ), 'handoff_ciphertext' => $handoff, 'version' => (int) ( $existing['version'] ?? 0 ) + 1, 'updated_at' => $now );
        if ( $existing ) { $wpdb->update( $table, $data, array( 'id' => (int) $existing['id'] ) ); } else { $data['created_at'] = $now; $wpdb->insert( $table, $data ); }
        return array( 'device_id' => $device_public_id, 'focus_mode' => $mode, 'categories' => $categories, 'channels' => $channels, 'version' => $data['version'] );
    }

    /** @param int $user_id User ID. @return array<int,array<string,mixed>> */
    public function device_profiles( $user_id ) { global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT device_public_id,focus_mode,categories_json,channels_json,version,updated_at FROM ' . SUN_Database::table( 'device_profiles' ) . ' WHERE user_id=%d ORDER BY id DESC LIMIT 50', absint( $user_id ) ), ARRAY_A ); foreach ( (array) $rows as &$row ) { $row['categories'] = json_decode( (string) $row['categories_json'], true ) ?: array(); $row['channels'] = json_decode( (string) $row['channels_json'], true ) ?: array(); unset( $row['categories_json'], $row['channels_json'] ); } unset( $row ); return (array) $rows; }

    /** @param int $user_id User ID. @param string $object_type Type. @param string $object_id ID. @param string $engagement Engagement. @return void */
    public function record_engagement( $user_id, $object_type, $object_id, $engagement = 'read' ) { global $wpdb; $user_id = absint( $user_id ); $object_type = substr( sanitize_key( $object_type ), 0, 50 ); $object_id = substr( sanitize_text_field( $object_id ), 0, 191 ); $engagement = substr( sanitize_key( $engagement ), 0, 32 ); if ( $user_id < 1 || '' === $object_type || '' === $object_id ) { return; } $table = SUN_Database::table( 'watch_history' ); $now = SUN_Database::now(); $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (user_id,object_type,object_id,engagement_type,first_seen_at,last_seen_at) VALUES (%d,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE engagement_type=VALUES(engagement_type),last_seen_at=VALUES(last_seen_at)", $user_id, $object_type, $object_id, $engagement, $now, $now ) ); }

    /** @param string $object_type Type. @param string $object_id ID. @return int[] */
    public function correction_audience( $object_type, $object_id ) { global $wpdb; $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . SUN_Database::table( 'watch_history' ) . ' WHERE object_type=%s AND object_id=%s ORDER BY last_seen_at DESC LIMIT 5000', substr( sanitize_key( $object_type ), 0, 50 ), substr( sanitize_text_field( $object_id ), 0, 191 ) ) ); return array_values( array_unique( array_map( 'absint', (array) $ids ) ) ); }

    /** @param int $user_id User ID. @return array<string,mixed> */
    public function wellbeing_metrics( $user_id ) {
        global $wpdb; $since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
        $created = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SUN_Database::table( 'notifications' ) . ' WHERE recipient_id=%d AND created_at>=%s', absint( $user_id ), $since ) );
        $done = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . SUN_Database::table( 'notification_states' ) . " WHERE user_id=%d AND action_state='done' AND updated_at>=%s", absint( $user_id ), $since ) );
        $suppressed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . SUN_Database::table( 'deliveries' ) . " WHERE recipient_id=%d AND status='suppressed' AND updated_at>=%s", absint( $user_id ), $since ) );
        return array( 'period_days' => 30, 'updates' => $created, 'useful_action_completion' => $done, 'suppressed_or_bundled_signal' => $suppressed, 'kpi_guardrail' => 'more-notifications-is-not-a-kpi' );
    }

    /** @param int $user_id User ID. @param string $category Category. @param string $priority Priority. @param array<string,mixed> $event Event. @param array<string,mixed> $profile Profile. @return int */
    private function attention_score( $category, $priority, array $event, array $profile ) { $score_map = array( 'low' => 25, 'normal' => 50, 'high' => 75, 'critical' => 95 ); $score = $score_map[ sanitize_key( $priority ) ] ?? 50; if ( in_array( $category, array( 'security', 'safety' ), true ) ) { $score += 10; } elseif ( 'clinic' === $category ) { $score += 8; } elseif ( in_array( $category, array( 'learning', 'publishing' ), true ) ) { $score += 4; } $mode_map = array( 'study' => array( 'learning', 'publishing', 'media' ), 'clinic' => array( 'clinic', 'messages' ), 'work' => array( 'publishing', 'messages', 'marketplace' ), 'travel' => array( 'security', 'clinic', 'messages' ) ); if ( isset( $mode_map[ $profile['focus_mode'] ] ) && in_array( $category, $mode_map[ $profile['focus_mode'] ], true ) ) { $score += 10; } if ( ! empty( $event['expires_at'] ) && strtotime( $event['expires_at'] ) - time() <= HOUR_IN_SECONDS ) { $score += 8; } return max( 0, min( 100, (int) apply_filters( 'sun_notification_attention_score', $score, $category, $priority, $event, $profile ) ) ); }
    /** @param string $category Category. @param int $score Score. @param array<string,mixed> $profile Profile. @param array<string,mixed> $event Event. @return string */
    private function importance_reason( $category, $score, array $profile, array $event ) { if ( in_array( $category, array( 'security', 'safety' ), true ) ) { return 'Critical safety or security information.'; } if ( 'clinic' === $category ) { return 'Time-sensitive clinic or appointment information.'; } if ( isset( $event['subscription_scope']['type'] ) ) { return 'You explicitly follow this ' . sanitize_key( (string) $event['subscription_scope']['type'] ) . '.'; } if ( $score >= 80 ) { return 'High-priority update under your current notification policy.'; } if ( 'balanced' !== $profile['focus_mode'] ) { return 'Ranked using your current ' . sanitize_key( (string) $profile['focus_mode'] ) . ' focus mode.'; } return 'Matched your notification preferences and current policy.'; }
    /** @param int $user_id User ID. @param array<string,mixed> $profile Profile. @return array<string,bool|int> */
    private function budget_state( $user_id, array $profile ) { global $wpdb; $table = SUN_Database::table( 'notifications' ); $hour = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ); $day = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ); $hour_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE recipient_id=%d AND created_at>=%s AND status<>'deleted'", absint( $user_id ), $hour ) ); $day_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE recipient_id=%d AND created_at>=%s AND status<>'deleted'", absint( $user_id ), $day ) ); return array( 'hour_count' => $hour_count, 'day_count' => $day_count, 'hourly_exceeded' => $hour_count >= (int) $profile['hourly_budget'], 'daily_exceeded' => $day_count >= (int) $profile['daily_budget'] ); }
    /** @param int $user_id User ID. @param array<string,mixed> $event Event. @param string $category Category. @return bool */
    private function source_cap_reached( $user_id, array $event, $category ) { global $wpdb; $cap = max( 1, min( 100, (int) apply_filters( 'sun_source_hourly_cap', 8, $event['producer'] ?? '', $category, $user_id ) ) ); $count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SUN_Database::table( 'notifications' ) . ' WHERE recipient_id=%d AND producer=%s AND category=%s AND created_at>=%s', absint( $user_id ), sanitize_key( (string) ( $event['producer'] ?? '' ) ), sanitize_key( $category ), gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) ); return $count >= $cap; }
    /** @param int $user_id User ID. @param string $time Local time. @return string */
    private function next_local_time( $user_id, $time ) { $claims = $this->auth->assertions( $user_id ); try { $tz = new DateTimeZone( (string) ( $claims['timezone'] ?? 'UTC' ) ); } catch ( Exception $e ) { $tz = new DateTimeZone( 'UTC' ); } $parts = array_map( 'intval', explode( ':', $this->valid_time( $time ) ?: '08:00:00' ) ); $now = new DateTimeImmutable( 'now', $tz ); $target = $now->setTime( $parts[0], $parts[1], $parts[2] ?? 0 ); if ( $target <= $now ) { $target = $target->modify( '+1 day' ); } return $target->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ); }
    /** @param int $user_id User ID. @param string $public_id Public ID. @return array<string,mixed>|WP_Error */
    private function state_row( $user_id, $public_id ) { global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( 'SELECT n.id AS notification_id,n.public_id,n.recipient_id,n.event_type,n.category,n.priority,n.status,n.version AS notification_version,s.id AS state_id,s.pinned_at,s.snoozed_until,s.action_state,s.attention_score,s.attention_reason,s.group_key,s.source_label,s.source_kind,s.source_verified,s.live_revision,s.version AS state_version,s.revoked_at,s.meta_ciphertext FROM ' . SUN_Database::table( 'notifications' ) . ' n INNER JOIN ' . SUN_Database::table( 'notification_states' ) . ' s ON s.notification_id=n.id WHERE n.public_id=%s AND n.recipient_id=%d LIMIT 1', sanitize_text_field( $public_id ), absint( $user_id ) ), ARRAY_A ); return $row ?: new WP_Error( 'sun_attention_state_not_found', __( 'Notification state not found.', 'sabri-unified-notifications' ), array( 'status' => 404 ) ); }
    /** @param array<string,mixed> $row State row. @return array<string,mixed> */ private function state_meta( array $row ) { if ( empty( $row['meta_ciphertext'] ) ) { return array(); } $plain = SUN_Crypto::decrypt( $row['meta_ciphertext'] ); if ( is_wp_error( $plain ) ) { return array(); } $decoded = json_decode( $plain, true ); return is_array( $decoded ) ? $decoded : array(); }
    /** @param mixed $actions Actions. @return array<int,array<string,string>> */ private function sanitize_actions( $actions ) { if ( ! is_array( $actions ) ) { return array(); } $out = array(); foreach ( array_slice( $actions, 0, 5 ) as $action ) { if ( ! is_array( $action ) ) { continue; } $key = sanitize_key( (string) ( $action['key'] ?? '' ) ); $label = substr( sanitize_text_field( (string) ( $action['label'] ?? '' ) ), 0, 80 ); $owner_action = substr( sanitize_key( (string) ( $action['owner_action'] ?? $key ) ), 0, 80 ); if ( '' === $key || '' === $label ) { continue; } $out[] = array( 'key' => $key, 'label' => $label, 'owner_action' => $owner_action ); } return $out; }
    /** @param mixed $value Value. @return string|null */ private function valid_future_datetime( $value ) { if ( null === $value || '' === $value ) { return null; } $ts = strtotime( (string) $value ); if ( false === $ts || $ts <= time() || $ts > time() + YEAR_IN_SECONDS ) { return null; } return gmdate( 'Y-m-d H:i:s', $ts ); }
    /** @param string $time Time. @return string */ private function valid_time( $time ) { return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time ) ? ( 5 === strlen( $time ) ? $time . ':00' : $time ) : ''; }
}
