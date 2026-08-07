<?php
/**
 * Canonical front-end routes and protected deep-link handling.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Router {
	/** @var SUN_Renderer */ private $renderer;
	/** @var SUN_Notification_Service */ private $notifications;
	/** @param SUN_Renderer $renderer Renderer. @param SUN_Notification_Service $notifications Notifications. */
	public function __construct( SUN_Renderer $renderer, SUN_Notification_Service $notifications ) { $this->renderer=$renderer; $this->notifications=$notifications; }

	/** @return void */
	public function register() {
		add_rewrite_rule('^notifications/?$','index.php?sun_notifications_route=center','top');
		add_rewrite_rule('^settings/notifications/?$','index.php?sun_notifications_route=settings','top');
		add_rewrite_rule('^notifications/open/([a-f0-9\-]{36})/?$','index.php?sun_notifications_route=open&sun_notification_id=$matches[1]','top');
		add_rewrite_rule('^notifications/unsubscribe/([^/]+)/?$','index.php?sun_notifications_route=unsubscribe&sun_notification_token=$matches[1]','top');
		add_rewrite_rule('^sabri-notifications-service-worker\.js$','index.php?sun_notifications_route=service-worker','top');
		add_filter('query_vars',array($this,'query_vars'));
		add_filter('template_include',array($this,'template_include'),99);
		add_action('template_redirect',array($this,'template_redirect'));
	}
	/** @param string[] $vars Vars. @return string[] */ public function query_vars($vars){return array_merge($vars,array('sun_notifications_route','sun_notification_id','sun_notification_token'));}
	/** @param string $template Template. @return string */ public function template_include($template){$route=get_query_var('sun_notifications_route');return in_array($route,array('center','settings'),true)?SUN_PATH.'templates/page.php':$template;}
	/** @return void */
	public function template_redirect() {
		$route=get_query_var('sun_notifications_route');
		if('service-worker'===$route){nocache_headers();header('Content-Type: application/javascript; charset=UTF-8');header('Service-Worker-Allowed: /');readfile(SUN_PATH.'assets/js/push-service-worker.js');exit;}
		if('open'===$route){auth_redirect();if(!sun_notifications()->auth()->is_recipient_eligible(get_current_user_id())){wp_safe_redirect(home_url('/notifications/?notice=unavailable'));exit;}$target=$this->notifications->resolve_open_target((string)get_query_var('sun_notification_id'));if(is_wp_error($target)){wp_safe_redirect(home_url('/notifications/?notice=unavailable'));exit;}wp_safe_redirect($target);exit;}
		if('unsubscribe'===$route){$claims=SUN_Crypto::verify_token(rawurldecode((string)get_query_var('sun_notification_token')),'unsubscribe');if(is_wp_error($claims)){wp_safe_redirect(home_url('/settings/notifications/?notice=invalid'));exit;}if(!empty($claims['user_id'])){sun_notifications()->preferences()->update((int)$claims['user_id'],array('category'=>$claims['category'],'channel'=>$claims['channel'],'enabled'=>false,'version'=>sun_notifications()->preferences()->get((int)$claims['user_id'],$claims['category'],$claims['channel'])['version'],'consent_source'=>'signed_unsubscribe'));}wp_safe_redirect(home_url('/settings/notifications/?notice=unsubscribed'));exit;}
		if(in_array($route,array('center','settings'),true)&&!is_user_logged_in()){auth_redirect();}
	}
}
