<?php
/** Canonical same-origin deep-link validation and click-time authorization. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Deep_Link {
	/** @param string $url URL. @return string */
	public static function sanitize( $url ) {
		$url=trim((string)$url);if(''===$url){return '';}
		if(str_starts_with($url,'/')&&!str_starts_with($url,'//')){$url=home_url($url);}
		$home=wp_parse_url(home_url('/'));$target=wp_parse_url($url);if(!is_array($home)||!is_array($target)||empty($home['host'])||empty($target['host'])){return '';}
		$home_scheme=strtolower((string)($home['scheme']??'https'));$target_scheme=strtolower((string)($target['scheme']??$home_scheme));if(!in_array($home_scheme,array('http','https'),true)||!hash_equals($home_scheme,$target_scheme)){return '';}
		if(!hash_equals(strtolower((string)$home['host']),strtolower((string)$target['host']))){return '';}
		$home_port=self::effective_port($home_scheme,isset($home['port'])?(int)$home['port']:null);$target_port=self::effective_port($target_scheme,isset($target['port'])?(int)$target['port']:null);if($home_port!==$target_port){return '';}
		$path=(string)($target['path']??'/');if(preg_match('#(?:^|/)\.\.(?:/|$)#',rawurldecode($path))){return '';}if(isset($target['user'])||isset($target['pass'])){return '';}
		$allowed=(bool)apply_filters('sun_deep_link_allowed',true,$url,$target);return $allowed?esc_url_raw($url):'';
	}

	/**
	 * Re-authorize a stored target at click time. Companion domain owners must
	 * positively attest object/state access; absence of an owner attestation
	 * fails closed to the notification center.
	 *
	 * @param string $url URL.
	 * @param string $context Opaque owner context.
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function authorize_click($url,$context,$user_id){
		$url=self::sanitize($url);$user_id=absint($user_id);if(''===$url||$user_id<1){return '';}
		$authorized=(bool)apply_filters('sun_deep_link_click_authorized',false,$url,sanitize_text_field((string)$context),$user_id);
		return $authorized?$url:'';
	}

	/** @param string $notification_public_id Notification ID. @return string */ public static function wrapper_url($notification_public_id){return home_url('/notifications/open/'.rawurlencode($notification_public_id).'/');}
	/** @param string $scheme Scheme. @param int|null $port Explicit port. @return int */ private static function effective_port($scheme,$port){if(null!==$port&&$port>0){return $port;}return'https'===$scheme?443:80;}
}
