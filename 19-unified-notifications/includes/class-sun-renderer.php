<?php
/**
 * Accessible bell, notification center and settings rendering.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Renderer {
	/** @var SUN_Notification_Service */ private $notifications;
	/** @var SUN_Preferences */ private $preferences;
	/** @var SUN_Auth */ private $auth;
	/** @param SUN_Notification_Service $notifications Notifications. @param SUN_Preferences $preferences Preferences. @param SUN_Auth $auth Auth. */
	public function __construct( SUN_Notification_Service $notifications, SUN_Preferences $preferences, SUN_Auth $auth ) { $this->notifications=$notifications; $this->preferences=$preferences; $this->auth=$auth; }

	/** @return string */
	public function render_bell() {
		if ( ! $this->current_user_eligible() ) { return ''; }
		$count = $this->notifications->get_unread_count( get_current_user_id() );
		ob_start(); include SUN_PATH . 'templates/bell.php'; return (string) ob_get_clean();
	}

	/** @param array<string,mixed> $args Args. @return string */
	public function render_center( array $args = array() ) {
		if ( ! is_user_logged_in() ) { return '<div class="sun-notice">' . esc_html__( 'Please sign in to view notifications.', 'sabri-unified-notifications' ) . '</div>'; }
		if ( ! $this->current_user_eligible() ) { return '<div class="sun-notice" role="status">' . esc_html__( 'Notifications are unavailable until your current account eligibility can be verified.', 'sabri-unified-notifications' ) . '</div>'; }
		$data = $this->notifications->list_notifications( get_current_user_id(), $args );
		ob_start(); include SUN_PATH . 'templates/center.php'; return (string) ob_get_clean();
	}

	/** @return string */
	public function render_settings() {
		if ( ! is_user_logged_in() ) { return '<div class="sun-notice">' . esc_html__( 'Please sign in to manage notification settings.', 'sabri-unified-notifications' ) . '</div>'; }
		if ( ! $this->current_user_eligible() ) { return '<div class="sun-notice" role="status">' . esc_html__( 'Notification settings are unavailable until your current account eligibility can be verified.', 'sabri-unified-notifications' ) . '</div>'; }
		$items = $this->preferences->get_all( get_current_user_id() );
		ob_start(); include SUN_PATH . 'templates/settings.php'; return (string) ob_get_clean();
	}

	/** @return void */
	public function enqueue_assets() {
		if ( ! $this->current_user_eligible() ) { return; }
		wp_enqueue_style( 'sun-notifications', SUN_URL . 'assets/css/notifications.css', array(), SUN_VERSION );
		wp_enqueue_script( 'sun-notifications', SUN_URL . 'assets/js/notifications.js', array(), SUN_VERSION, true );
		wp_localize_script( 'sun-notifications', 'SUNNotifications', array(
			'restUrl'=>esc_url_raw(rest_url(SUN_REST_NAMESPACE.'/')),
			'nonce'=>wp_create_nonce('wp_rest'),
			'pollMs'=>max(30000,(int)apply_filters('sun_unread_poll_ms',60000)),
			'pushPublicKey'=>(string)apply_filters('sun_push_public_key',''),
			'workerUrl'=>esc_url_raw(home_url('/sabri-notifications-service-worker.js')),
			'homeUrl'=>esc_url_raw(home_url('/')),
			'i18n'=>array('error'=>__('The action could not be completed.','sabri-unified-notifications'),'saved'=>__('Saved.','sabri-unified-notifications')),
		) );
	}

	/** @return bool */
	private function current_user_eligible() {
		return is_user_logged_in() && $this->auth->is_recipient_eligible( get_current_user_id() );
	}
}
