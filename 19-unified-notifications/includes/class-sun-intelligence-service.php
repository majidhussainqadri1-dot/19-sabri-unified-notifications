<?php
/** Explainable, citation-bound notification intelligence with deterministic fallback. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Intelligence_Service {
    /** @var SUN_Attention_Service */ private $attention;
    /** @param SUN_Attention_Service $attention Attention. */
    public function __construct( SUN_Attention_Service $attention ) { $this->attention = $attention; }

    /** @param int $user_id User ID. @param int $hours Hours. @return array<string,mixed> */
    public function catchup_summary( $user_id, $hours = 24 ) {
        $hours = max( 1, min( 168, absint( $hours ) ) );
        $profile = $this->attention->profile( $user_id );
        if ( empty( $profile['ai_summary_enabled'] ) ) { return array( 'enabled' => false, 'summary' => '', 'citations' => array(), 'items' => array() ); }
        $result = $this->attention->search( $user_id, '', array( 'limit' => 50, 'sort' => 'priority' ) );
        $cutoff = time() - HOUR_IN_SECONDS * $hours;
        $items = array_values( array_filter( (array) $result['items'], static function( $item ) use ( $cutoff ) { return ! empty( $item['created_at'] ) && strtotime( $item['created_at'] . ' UTC' ) >= $cutoff; } ) );
        return $this->summarize( $user_id, $items, 'catchup' );
    }

    /** @param int $user_id User ID. @param string $query Query. @return array<string,mixed>|WP_Error */
    public function assistant( $user_id, $query ) {
        $query = trim( sanitize_textarea_field( (string) $query ) );
        if ( '' === $query || strlen( $query ) > 500 ) { return new WP_Error( 'sun_assistant_query_invalid', __( 'Ask a short question about your notifications.', 'sabri-unified-notifications' ), array( 'status' => 400 ) ); }
        $lower = strtolower( $query ); $args = array( 'limit' => 30, 'sort' => 'priority' ); $term = '';
        $categories = array( 'security','safety','clinic','publishing','learning','social','marketplace','messages','media','system','marketing' );
        foreach ( $categories as $category ) { if ( false !== strpos( $lower, $category ) ) { $args['category'] = $category; break; } }
        if ( false !== strpos( $lower, 'important' ) || false !== strpos( $lower, 'priority' ) || false !== strpos( $lower, 'اہم' ) ) { $args['sort'] = 'priority'; }
        if ( false !== strpos( $lower, 'action' ) || false !== strpos( $lower, 'عمل' ) ) { $args['needs_action'] = true; }
        if ( preg_match( '/(?:about|for|on)\s+([A-Za-z0-9 _\-]{3,80})/i', $query, $m ) ) { $term = trim( $m[1] ); }
        $search = $this->attention->search( $user_id, $term, $args );
        $summary = $this->summarize( $user_id, (array) $search['items'], 'assistant', $query );
        $summary['query'] = $query; $summary['read_only'] = true;
        $summary['mutation_note'] = __( 'The assistant never changes notification or domain state without an explicit, separately authorized action.', 'sabri-unified-notifications' );
        return $summary;
    }

    /** @param int $user_id User ID. @param string $public_id ID. @return array<string,mixed>|WP_Error */
    public function explain( $user_id, $public_id ) { return $this->attention->why( $user_id, $public_id ); }

    /** @param int $user_id User ID. @param array<int,array<string,mixed>> $items Items. @param string $purpose Purpose. @param string $query Query. @return array<string,mixed> */
    private function summarize( $user_id, array $items, $purpose, $query = '' ) {
        $items = array_slice( $items, 0, 30 );
        $source_ids = array_values( array_filter( array_map( static function( $item ) { return sanitize_text_field( (string) ( $item['public_id'] ?? '' ) ); }, $items ) ) );
        $payload = array(
            'purpose' => sanitize_key( $purpose ), 'query' => $query,
            'items' => array_map( static function( $item ) {
                return array(
                    'id' => (string) ( $item['public_id'] ?? '' ), 'category' => sanitize_key( (string) ( $item['category'] ?? '' ) ),
                    'priority' => sanitize_key( (string) ( $item['priority'] ?? '' ) ), 'title' => substr( sanitize_text_field( (string) ( $item['title'] ?? '' ) ), 0, 240 ),
                    'summary' => substr( sanitize_text_field( (string) ( $item['summary'] ?? '' ) ), 0, 500 ), 'attention_score' => absint( $item['attention_score'] ?? 0 ),
                    'source_label' => substr( sanitize_text_field( (string) ( $item['source_label'] ?? '' ) ), 0, 191 ), 'source_verified' => ! empty( $item['source_verified'] ), 'created_at' => (string) ( $item['created_at'] ?? '' ),
                );
            }, $items ),
        );
        $ai = apply_filters( 'sun_notification_ai_generate', null, $payload, $user_id );
        if ( is_array( $ai ) && ! empty( $ai['summary'] ) ) {
            $raw_citations = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) ( $ai['citations'] ?? array() ) ) ) ) );
            $citations = array_values( array_intersect( $source_ids, $raw_citations ) );
            $invalid_citations = array_values( array_diff( $raw_citations, $source_ids ) );
            if ( ! empty( $invalid_citations ) || ( ! empty( $items ) && empty( $citations ) ) ) {
                SUN_Audit::record( 'notification_ai_output_rejected', 'notification_summary', sanitize_key( $purpose ), array( 'reason' => 'citation_boundary', 'invalid_count' => count( $invalid_citations ), 'purpose' => 'ai_safety' ), $user_id );
                $fallback = $this->deterministic_summary( $items );
                $fallback['ai_rejected'] = 'citation_boundary';
                return $fallback;
            }
            return array( 'enabled' => true, 'mode' => 'configured-ai', 'summary' => substr( sanitize_textarea_field( (string) $ai['summary'] ), 0, 6000 ), 'citations' => $citations, 'items' => $items );
        }
        return $this->deterministic_summary( $items );
    }

    /** @param array<int,array<string,mixed>> $items Items. @return array<string,mixed> */
    private function deterministic_summary( array $items ) {
        if ( empty( $items ) ) { return array( 'enabled' => true, 'mode' => 'deterministic-fallback', 'summary' => __( 'There are no matching notifications in this view.', 'sabri-unified-notifications' ), 'citations' => array(), 'items' => array() ); }
        $important = array_values( array_filter( $items, static function( $item ) { return (int) ( $item['attention_score'] ?? 0 ) >= 75 || in_array( (string) ( $item['priority'] ?? '' ), array( 'high', 'critical' ), true ); } ) );
        $categories = array(); foreach ( $items as $item ) { $cat = sanitize_key( (string) ( $item['category'] ?? 'other' ) ); $categories[ $cat ] = ( $categories[ $cat ] ?? 0 ) + 1; } arsort( $categories );
        $parts = array(); $parts[] = sprintf( _n( '%d notification matched.', '%d notifications matched.', count( $items ), 'sabri-unified-notifications' ), count( $items ) );
        if ( $important ) { $parts[] = sprintf( _n( '%d is high priority.', '%d are high priority.', count( $important ), 'sabri-unified-notifications' ), count( $important ) ); }
        if ( $categories ) { $top = array_slice( $categories, 0, 3, true ); $labels = array(); foreach ( $top as $category => $count ) { $labels[] = $category . ': ' . $count; } $parts[] = __( 'Top categories:', 'sabri-unified-notifications' ) . ' ' . implode( ', ', $labels ) . '.'; }
        $top_items = array_slice( $items, 0, 5 ); foreach ( $top_items as $item ) { if ( ! empty( $item['title'] ) ) { $parts[] = '• ' . substr( sanitize_text_field( (string) $item['title'] ), 0, 180 ); } }
        return array( 'enabled' => true, 'mode' => 'deterministic-fallback', 'summary' => implode( "\n", $parts ), 'citations' => array_values( array_map( static function( $item ) { return (string) $item['public_id']; }, $top_items ) ), 'items' => $items );
    }
}
