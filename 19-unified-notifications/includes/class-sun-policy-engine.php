<?php
/**
 * Notification policy, channel, mandatory, quiet-hour, digest and subscription decisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Policy_Engine {
	/** @var SUN_Preferences */ private $preferences;
	/** @var SUN_Producer_Registry */ private $registry;
	/** @var SUN_Subscriptions */ private $subscriptions;

	/** @param SUN_Preferences $preferences Preferences. @param SUN_Producer_Registry $registry Registry. @param SUN_Subscriptions $subscriptions Subscriptions. */
	public function __construct( SUN_Preferences $preferences, SUN_Producer_Registry $registry, SUN_Subscriptions $subscriptions ) {
		$this->preferences   = $preferences;
		$this->registry      = $registry;
		$this->subscriptions = $subscriptions;
	}

	/**
	 * Resolve policy and lawful delivery channels for one recipient.
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
		$category    = $event['category'] && in_array( $event['category'], $this->preferences->categories(), true ) ? $event['category'] : $policy['category'];
		$priority    = in_array( $event['priority'], array( 'low', 'normal', 'high', 'critical' ), true ) ? $event['priority'] : $policy['priority'];
		$mandatory   = (bool) $policy['mandatory'];
		$sensitivity = in_array( $event['sensitivity'], array( 'standard', 'sensitive', 'restricted', 'secret' ), true ) ? $event['sensitivity'] : $policy['sensitivity'];

		$subscription = $this->subscriptions->evaluate_event( (int) $recipient['user_id'], $event, $category );
		if ( empty( $subscription['allowed'] ) ) {
			return array(
				'suppressed'      => true,
				'suppress_reason' => 'subscription_preference',
				'category'        => $category,
				'priority'        => $priority,
				'mandatory'       => false,
				'sensitivity'     => $sensitivity,
				'channels'        => array(),
				'deliveries'      => array(),
			);
		}

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
			$base = $this->preferences->next_delivery_time( $recipient['user_id'], $category, $channel, $mandatory );
			if ( $mandatory || ! $policy['digest_allowed'] ) {
				$digest = array( 'time' => $base, 'key' => null );
			} elseif ( ! empty( $subscription['frequency'] ) ) {
				$digest = $this->digest_for_frequency( (string) $subscription['frequency'], $base, (string) $pref['timezone'] );
			} else {
				$digest = $this->preferences->digest_schedule( $recipient['user_id'], $category, $channel, $base );
			}
			$deliveries[] = array(
				'channel'      => $channel,
				'scheduled_at' => $digest['time']->format( 'Y-m-d H:i:s' ),
				'digest_key'   => $digest['key'],
			);
		}
		return array(
			'suppressed'             => false,
			'policy_key'             => $policy['policy_key'],
			'policy_version'         => $policy['version'],
			'category'               => $category,
			'priority'               => $priority,
			'mandatory'              => $mandatory,
			'sensitivity'            => $sensitivity,
			'digest_allowed'         => (bool) $policy['digest_allowed'],
			'subscription_frequency' => $subscription['frequency'],
			'channels'               => $channels,
			'deliveries'             => $deliveries,
		);
	}

	/** @param string $event_type Event type. @return array<string,mixed>|null */
	private function find_policy( $event_type ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM " . SUN_Database::table( 'policies' ) . " WHERE status='active' ORDER BY id ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $row ) {
			if ( $this->registry->matches_pattern( $event_type, $row['event_pattern'] ) ) {
				return $row;
			}
		return null;
	}

	/** @param string $frequency Frequency. @param DateTimeImmutable $base Base UTC. @param string $timezone Timezone. @return array{time:DateTimeImmutable,key:string|null} */
	private function digest_for_frequency( $frequency, DateTimeImmutable $base, $timezone ) {
		if ( 'immediate' === $frequency ) {
			return array( 'time' => $base, 'key' => null );
		}
		try {
			$tz = new DateTimeZone( $timezone ?: 'UTC' );
		} catch ( Exception $e ) {
			$tz = new DateTimeZone( 'UTC' );
		}
		$local = $base->setTimezone( $tz );
		if ( 'daily' === $frequency ) {
			$next = $local->setTime( 8, 0 );
			if ( $next <= $local ) {
				$next = $next->modify( '+1 day' );
			}
			return array( 'time' => $next->setTimezone( new DateTimeZone( 'UTC' ) ), 'key' => 'daily:' . $next->format( 'Y-m-d' ) );
		}
		$next = $local->modify( 'next monday' )->setTime( 8, 0 );
		return array( 'time' => $next->setTimezone( new DateTimeZone( 'UTC' ) ), 'key' => 'weekly:' . $next->format( 'o-W' ) );
	}
}
