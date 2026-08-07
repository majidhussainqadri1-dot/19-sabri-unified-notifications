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
	/** @var SUN_Subscriptions */
	private $subscriptions;

	/** @param SUN_Preferences $preferences Preferences. @param SUN_Producer_Registry $registry Registry. @param SUN_Subscriptions $subscriptions Subscriptions. */
	public function __construct( SUN_Preferences $preferences, SUN_Producer_Registry $registry, SUN_Subscriptions $subscriptions ) {
		$this->preferences   = $preferences;
		$this->registry      = $registry;
		$this->subscriptions = $subscriptions;
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

		$scope = isset( $event['subscription_scope'] ) && is_array( $event['subscription_scope'] ) ? $event['subscription_scope'] : array();
		$subscription = $this->subscriptions->decide(
			(int) $recipient['user_id'],
			(string) $event['event_type'],
			$scope,
			! empty( $profile['requires_opt_in'] )
		);
		if ( ! $mandatory && empty( $subscription['allowed'] ) ) {
			return array(
				'policy_key' => $policy['policy_key'],
				'policy_version' => $policy['version'],
				'category' => $category,
				'priority' => $priority,
				'mandatory' => false,
				'sensitivity' => $sensitivity,
				'digest_allowed' => $digest_allowed,
				'channels' => array(),
				'deliveries' => array(),
				'suppressed' => true,
				'suppression_reason' => sanitize_key( (string) ( $subscription['reason'] ?? 'subscription_disabled' ) ),
				'four_plan_profile' => $profile,
				'subscription' => $subscription,
			);
		}

		$cap = array( 'allowed' => true, 'key' => '' );
		if ( ! $mandatory && ! empty( $profile['max_per_24h'] ) ) {
			$cap = $this->subscriptions->bulletin_cap_check( (int) $recipient['user_id'], $scope, (int) $profile['max_per_24h'] );
			if ( empty( $cap['allowed'] ) ) {
				return array(
					'policy_key' => $policy['policy_key'],
					'policy_version' => $policy['version'],
					'category' => $category,
					'priority' => $priority,
					'mandatory' => false,
					'sensitivity' => $sensitivity,
					'digest_allowed' => $digest_allowed,
					'channels' => array(),
					'deliveries' => array(),
					'suppressed' => true,
					'suppression_reason' => 'creator_bulletin_frequency_cap',
					'four_plan_profile' => $profile,
					'subscription' => $subscription,
					'bulletin_cap_key' => (string) $cap['key'],
				);
			}
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
			$digest = $mandatory || ! $digest_allowed ? array( 'time' => $base, 'key' => null ) : $this->preferences->digest_schedule( $recipient['user_id'], $category, $channel, $base );
			$scope_frequency = sanitize_key( (string) ( $subscription['frequency'] ?? 'immediate' ) );
			if ( ! $mandatory && $digest_allowed && in_array( $scope_frequency, array( 'daily', 'weekly' ), true ) ) {
				$scope_hash = substr( hash( 'sha256', (string) ( $scope['type'] ?? '' ) . '|' . (string) ( $scope['id'] ?? '' ) ), 0, 16 );
				$scope_digest = $this->subscriptions->schedule( $scope_frequency, $base, (string) $pref['timezone'], $scope_hash );
				if ( $scope_digest['time'] > $digest['time'] ) {
					$digest = $scope_digest;
				}
			}
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
			'suppressed'      => false,
			'four_plan_profile' => $profile,
			'subscription'    => $subscription,
			'bulletin_cap_key'=> (string) ( $cap['key'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $decision Decision. @return void */
	public function mark_notification_created( array $decision ) {
		if ( ! empty( $decision['bulletin_cap_key'] ) ) {
			$this->subscriptions->mark_bulletin_sent( (string) $decision['bulletin_cap_key'] );
		}
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
