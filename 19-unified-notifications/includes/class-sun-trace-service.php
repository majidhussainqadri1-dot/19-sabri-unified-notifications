<?php
/** Privacy-minimized end-to-end notification trace and synthetic diagnostics. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Trace_Service {
    /** @param string $trace_id Trace ID. @param string $stage Stage. @param string $status Status. @param array<string,mixed> $detail Detail. @param int|null $notification_id Notification ID. @param int|null $delivery_id Delivery ID. @return void */
    public static function record( $trace_id, $stage, $status, array $detail = array(), $notification_id = null, $delivery_id = null ) {
        global $wpdb;
        $trace_id = substr( sanitize_text_field( (string) $trace_id ), 0, 100 );
        $stage = substr( sanitize_key( (string) $stage ), 0, 50 );
        $status = substr( sanitize_key( (string) $status ), 0, 24 );
        if ( '' === $trace_id || '' === $stage ) { return; }
        $detail = self::minimize( $detail );
        $wpdb->insert(
            SUN_Database::table( 'trace_spans' ),
            array(
                'trace_id' => $trace_id,
                'notification_id' => $notification_id ? absint( $notification_id ) : null,
                'delivery_id' => $delivery_id ? absint( $delivery_id ) : null,
                'stage' => $stage,
                'status' => $status ?: 'unknown',
                'detail_json' => empty( $detail ) ? null : wp_json_encode( $detail ),
                'created_at' => SUN_Database::now(),
            )
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    }

    /** @param string $trace_id Trace ID. @return array<int,array<string,mixed>> */
    public function explorer( $trace_id ) {
        global $wpdb;
        $trace_id = substr( sanitize_text_field( $trace_id ), 0, 100 );
        if ( '' === $trace_id ) { return array(); }
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT stage,status,detail_json,created_at FROM ' . SUN_Database::table( 'trace_spans' ) . ' WHERE trace_id=%s ORDER BY id ASC LIMIT 200',
                $trace_id
            ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        foreach ( (array) $rows as &$row ) {
            $row['detail'] = json_decode( (string) ( $row['detail_json'] ?? '' ), true ) ?: array();
            unset( $row['detail_json'] );
        }
        unset( $row );
        return (array) $rows;
    }

    /** @param string[] $channels Channels. @return array<string,mixed> */
    public function synthetic_test( array $channels = array( 'in_app', 'email', 'push', 'sms' ) ) {
        $allowed = array( 'in_app', 'email', 'push', 'sms', 'whatsapp', 'rcs' );
        $channels = array_values( array_intersect( $allowed, array_map( 'sanitize_key', $channels ) ) );
        $checks = array();
        foreach ( $channels as $channel ) {
            $configured = 'in_app' === $channel ? true : (bool) apply_filters( 'sun_synthetic_channel_configured', false, $channel );
            $latency_ms = (int) apply_filters( 'sun_synthetic_channel_latency_ms', 0, $channel );
            $checks[] = array(
                'channel' => $channel,
                'configured' => $configured,
                'latency_ms' => max( 0, $latency_ms ),
                'status' => $configured ? 'ready' : 'unconfigured',
            );
        }
        return array(
            'mode' => 'non-delivery-dry-run',
            'checked_at' => SUN_Database::now(),
            'checks' => $checks,
            'note' => 'Synthetic diagnostics never claim real provider delivery without provider-side evidence.',
        );
    }

    /** @param array<string,mixed> $detail Detail. @return array<string,mixed> */
    private static function minimize( array $detail ) {
        $blocked = array( 'token', 'secret', 'password', 'body', 'message', 'content', 'email', 'phone', 'payload', 'clinical', 'diagnosis', 'remedy' );
        $out = array();
        foreach ( $detail as $key => $value ) {
            $safe_key = sanitize_key( (string) $key );
            $blocked_key = false;
            foreach ( $blocked as $needle ) { if ( false !== strpos( $safe_key, $needle ) ) { $blocked_key = true; break; } }
            if ( $blocked_key ) { continue; }
            if ( is_scalar( $value ) || null === $value ) {
                $out[ $safe_key ] = is_string( $value ) ? substr( sanitize_text_field( $value ), 0, 191 ) : $value;
            }
        }
        return $out;
    }
}
