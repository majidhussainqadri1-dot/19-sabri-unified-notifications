<?php
/**
 * Notification policy, channel, mandatory, quiet-hour and digest decisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Policy_Engine {
	/** @var SUN_Preferences */
	private $preferences;
	/** @var SUN_Producer_Registry */
	private $registry;

	/** @param SUN_Preferences $preferences Preferences. @param SUN_Producer_Registry $registry Registry. */
	public function __construct( SUN_Preferences $preferences, SUN_Producer_Registry $registry ) {
		$this->preferences = $preferences;
		$this->registry    = $registry;
	}

	/**
	 * Resolve policy and lawful delivery channels for one recipient.
	 *
	 * Producer hints may only narrow channels or strengthen priority/sensitivity.
	 * They cannot downgrade the canonical File 19 policy classification.
	 *
	 * @param array<string,mixed> $event Event.
	 * @param array<string,mixed> $recipient Recipient.
	 * @return array<string,mixed>|WP_Error
	 */
	public function decide( array $event, array $recipient ) {
		$policy = $this->find_policy( $event['event_type'] );
		if ( ! $policy ) {
			return new WP_Error( 'sun_policy_missing', __( 'No active notification policy matches this event.', 'sabri-unified-notifications' ) );
		}
		$profile = SUN_Four_Plan_Compliance::profile_for( (string) $event['event_type'] );
		$category = sanitize_key( (string) ( $profile['category'] ?? $policy['category'] ) );
		if ( ! in_array( $category, $this->preferences->categories(), true ) ) {
			$category = sanitize_key( (string) $policy['category'] );
		}
		$priority = SUN_Four_Plan_Compliance::strongest_priority( sanitize_key( (string) $policy['priority'] ), sanitize_key( (string) ( $profile['priority'] ?? 'low' ) ) );
		$event_priority = sanitize_key( (string) ( $event['priority'] ?? '' ) );
		if ( in_array( $event_priority, array( 'low', 'normal', 'high', 'critical' ), true ) ) {
			$priority = SUN_Four_Plan_Compliance::strongest_priority( $priority, $event_priority );
		}
		$sensitivity = SUN_Four_Plan_Compliance::strongest_sensitivity( sanitize_key( (string) $policy['sensitivity'] ), sanitize_key( (string) ( $profile['sensitivity'] ?? 'standard' ) ) );
		$event_sensitivity = sanitize_key( (string) ( $event['sensitivity'] ?? '' ) );
		if ( in_array( $event_sensitivity, array( 'standard', 'sensitive', 'restricted', 'secret' ), true ) ) {
			$sensitivity = SUN_Four_Plan_Compliance::strongest_sensitivity( $sensitivity, $event_sensitivity );
		}
		$mandatory = (bool) $policy['mandatory'] || ! empty( $profile['mandatory'] );
		$digest_allowed = (bool) $policy['digest_allowed'] && ! ( isset( $profile['digest_allowed'] ) && ! $profile['digest_allowed'] );
		$channels = json_decode( (string) $policy['channels_json'], true );
		$channels = is_array( $channels ) ? array_values( array_intersect( $channels, $this->preferences->channels() ) ) : array( 'in_app' );
		if ( ! empty( $recipient['channels'] ) ) {
			$channels = array_values( array_intersect( $channels, $recipient['channels'] ) );
		}
		if ( ! in_array( 'in_app', $channels, true ) ) {
			array_unshift( $channels, 'in_app' );
		}

		$deliveries = array();
		foreach ( array_unique( $channels ) as $channel ) {
			$pref = $this->preferences->get( $recipient['user_id'], $category, $channel );
			if ( 'in_app' !== $channel && empty( $pref['enabled'] ) && ! ( $mandatory && in_array( $channel, array( 'email', 'push' ), true ) ) ) {
				continue;
			}
			if ( 'in_app' === $channel ) {
				continue;
			}
			$base   = $this->preferences->next_delivery_time( $recipient['user_id'], $category, $channel, $mandatory );
			$digest = $mandatory || ! $digest_allowed ? array( 'time' => $base, 'key' => null ) : $this->preferences->digest_schedule( $recipient['user_id'], $category, $channel, $base );
			$deliveries[] = array(
				'channel'      => $channel,
				'scheduled_at' => $digest['time']->format( 'Y-m-d H:i:s' ),
				'digest_key'   => $digest['key'],
			);
		}
		return array(
			'policy_key'      => $policy['policy_key'],
			'policy_version'  => $policy['version'],
			'category'        => $category,
			'priority'        => $priority,
			'mandatory'       => $mandatory,
			'sensitivity'     => $sensitivity,
			'digest_allowed'  => $digest_allowed,
			'channels'        => $channels,
			'deliveries'      => $deliveries,
			'four_plan_profile' => $profile,
		);
	}

	/** @param string $event_type Event type. @return array<string,mixed>|null */
	private function find_policy( $event_type ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM " . SUN_Database::table( 'policies' ) . " WHERE status='active' ORDER BY CHAR_LENGTH(event_pattern) DESC,id ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $row ) {
			if ( $this->registry->matches_pattern( $event_type, $row['event_pattern'] ) ) {
				return $row;
			}
		}
		return null;
	}
}
