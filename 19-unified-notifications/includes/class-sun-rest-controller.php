<?php
/**
 * Versioned REST API for own notifications, preferences, subscriptions, devices and operations.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_REST_Controller {
	/** @var SUN_Notification_Service */ private $notifications;
	/** @var SUN_Preferences */ private $preferences;
	/** @var SUN_Subscriptions */ private $subscriptions;
	/** @var SUN_Wellbeing */ private $wellbeing;
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @var SUN_Reconciliation */ private $reconciliation;
	/** @var SUN_Health */ private $health;
	/** @var SUN_Auth */ private $auth;
	/** @var SUN_Producer_Registry */ private $registry;

	/** @param SUN_Notification_Service $notifications Notifications. @param SUN_Preferences $preferences Preferences. @param SUN_Subscriptions $subscriptions Subscriptions. @param SUN_Wellbeing $wellbeing Wellbeing. @param SUN_Delivery_Service $delivery Delivery. @param SUN_Reconciliation $reconciliation Reconciliation. @param SUN_Health $health Health. @param SUN_Auth $auth Auth. @param SUN_Producer_Registry $registry Registry. */
	public function __construct( SUN_Notification_Service $notifications, SUN_Preferences $preferences, SUN_Subscriptions $subscriptions, SUN_Wellbeing $wellbeing, SUN_Delivery_Service $delivery, SUN_Reconciliation $reconciliation, SUN_Health $health, SUN_Auth $auth, SUN_Producer_Registry $registry ) {$this->notifications=$notifications;$this->preferences=$preferences;$this->subscriptions=$subscriptions;$this->wellbeing=$wellbeing;$this->delivery=$delivery;$this->reconciliation=$reconciliation;$this->health=$health;$this->auth=$auth;$this->registry=$registry;}

	/** @return void */
	public function register_routes(){
		register_rest_route(SUN_REST_NAMESPACE,'/notifications',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'list_items'),'permission_callback'=>array($this,'logged_in'),'args'=>$this->list_args())));
		register_rest_route(SUN_REST_NAMESPACE,'/notifications/(?P<id>[a-f0-9\-]{36})',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'get_item'),'permission_callback'=>array($this,'logged_in')),array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'mutate_item'),'permission_callback'=>array($this,'logged_in'),'args'=>array('action'=>array('required'=>true,'sanitize_callback'=>'sanitize_key'),'version'=>array('sanitize_callback'=>'absint')))));
		register_rest_route(SUN_REST_NAMESPACE,'/notifications/bulk',array(array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'bulk_mutate'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/unread-count',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'unread_count'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/preferences',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'get_preferences'),'permission_callback'=>array($this,'logged_in')),array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'update_preference'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/subscriptions',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'get_subscriptions'),'permission_callback'=>array($this,'logged_in')),array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'update_subscription'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/subscriptions/(?P<id>[a-f0-9\-]{36})',array(array('methods'=>WP_REST_Server::DELETABLE,'callback'=>array($this,'delete_subscription'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/wellbeing',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'wellbeing'),'permission_callback'=>array($this,'logged_in'),'args'=>array('days'=>array('sanitize_callback'=>'absint')))));
		register_rest_route(SUN_REST_NAMESPACE,'/devices',array(array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'register_device'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/devices/(?P<id>[a-f0-9\-]{36})',array(array('methods'=>WP_REST_Server::DELETABLE,'callback'=>array($this,'revoke_device'),'permission_callback'=>array($this,'logged_in'))));
		register_rest_route(SUN_REST_NAMESPACE,'/events',array(array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'ingest_event'),'permission_callback'=>'__return_true')));
		register_rest_route(SUN_REST_NAMESPACE,'/provider/(?P<channel>[a-z0-9_\-]+)/webhook',array(array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'provider_webhook'),'permission_callback'=>'__return_true')));
		register_rest_route(SUN_REST_NAMESPACE,'/health',array(array('methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'health'),'permission_callback'=>array($this,'can_view_health'))));
		register_rest_route(SUN_REST_NAMESPACE,'/dead-letters/(?P<id>[a-f0-9\-]{36})/retry',array(array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'retry_dead_letter'),'permission_callback'=>array($this,'can_retry'))));
	}

	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function list_items($r){return rest_ensure_response($this->notifications->list_notifications(get_current_user_id(),$r->get_params()));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function get_item($r){$row=$this->notifications->get_notification(get_current_user_id(),$r['id']);if(is_wp_error($row)){return $row;}unset($row['id'],$row['recipient_id'],$row['data_ciphertext'],$row['dedupe_key'],$row['deep_link']);$row['open_url']=SUN_Deep_Link::wrapper_url($row['public_id']);return rest_ensure_response($row);}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function mutate_item($r){$limited=$this->rate_limit('user-mutate:'.get_current_user_id(),120,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->notifications->mutate(get_current_user_id(),$r['id'],sanitize_key($r['action']),$r->has_param('version')?absint($r['version']):null);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true,'unread_count'=>$this->notifications->get_unread_count(get_current_user_id())));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function bulk_mutate($r){$limited=$this->rate_limit('user-bulk:'.get_current_user_id(),20,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->notifications->bulk_mutate(get_current_user_id(),sanitize_key($r['action']),array('category'=>sanitize_key((string)$r['category'])));return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true,'updated'=>$result,'unread_count'=>$this->notifications->get_unread_count(get_current_user_id())));}
	/** @return WP_REST_Response */ public function unread_count(){return rest_ensure_response(array('count'=>$this->notifications->get_unread_count(get_current_user_id())));}
	/** @return WP_REST_Response */ public function get_preferences(){return rest_ensure_response(array('items'=>$this->preferences->get_all(get_current_user_id())));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function update_preference($r){$limited=$this->rate_limit('preferences:'.get_current_user_id(),60,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->preferences->update(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
	/** @return WP_REST_Response */ public function get_subscriptions(){return rest_ensure_response(array('items'=>$this->subscriptions->list_for_user(get_current_user_id()),'scope_types'=>$this->subscriptions->scope_types(),'frequencies'=>$this->subscriptions->frequencies()));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function update_subscription($r){$limited=$this->rate_limit('subscriptions:'.get_current_user_id(),60,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->subscriptions->upsert(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function delete_subscription($r){$limited=$this->rate_limit('subscriptions-delete:'.get_current_user_id(),60,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->subscriptions->remove(get_current_user_id(),$r['id']);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response */ public function wellbeing($r){return rest_ensure_response($this->wellbeing->summary(get_current_user_id(),absint($r['days']?:30)));}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function register_device($r){$limited=$this->rate_limit('devices:'.get_current_user_id(),20,HOUR_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->preferences->register_device(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function revoke_device($r){$result=$this->preferences->revoke_device(get_current_user_id(),$r['id']);if(!$result){return new WP_Error('sun_device_not_found',__('Notification device not found.','sabri-unified-notifications'),array('status'=>404));}return rest_ensure_response(array('success'=>true));}

	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */
	public function ingest_event($r){
		$producer=sanitize_key((string)$r->get_header('x-sun-producer'));$timestamp=(string)$r->get_header('x-sun-timestamp');$signature=(string)$r->get_header('x-sun-signature');$raw=(string)$r->get_body();
		$max_bytes=max(1024,(int)apply_filters('sun_event_request_max_bytes',131072,$producer));if(strlen($raw)>$max_bytes){return new WP_Error('sun_event_request_too_large',__('The notification event request is too large.','sabri-unified-notifications'),array('status'=>413));}
		$remote=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'));$anon=$this->rate_limit('producer-anon:'.$remote,600,MINUTE_IN_SECONDS);if(is_wp_error($anon)){return $anon;}
		$verified=$this->registry->verify_signature($producer,$timestamp,$signature,$raw);if(is_wp_error($verified)){return $verified;}
		$limited=$this->rate_limit('producer-auth:'.$producer,(int)apply_filters('sun_producer_rate_limit',120,$producer),MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}
		$event=json_decode($raw,true,32);if(!is_array($event)||JSON_ERROR_NONE!==json_last_error()){return new WP_Error('sun_json_invalid',__('Invalid JSON event.','sabri-unified-notifications'),array('status'=>400));}
		$event['producer']=$producer;$result=$this->notifications->ingest_event($event,'rest');return is_wp_error($result)?$result:rest_ensure_response($result);
	}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function provider_webhook($r){$remote=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'));$limited=$this->rate_limit('webhook:'.$r['channel'].':'.$remote,300,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return $limited;}$result=$this->delivery->provider_webhook($r['channel'],$r->get_json_params()?:array(),$r);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true));}
	/** @return WP_REST_Response */ public function health(){return rest_ensure_response($this->health->snapshot());}
	/** @param WP_REST_Request $r Request. @return WP_REST_Response|WP_Error */ public function retry_dead_letter($r){$result=$this->reconciliation->retry_dead_letter($r['id']);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true));}
	/** @return bool|WP_Error */ public function logged_in(){return is_user_logged_in()&&$this->auth->is_recipient_eligible(get_current_user_id())?true:new WP_Error('sun_auth_required',__('Authentication and account eligibility are required.','sabri-unified-notifications'),array('status'=>401));}
	/** @return bool */ public function can_view_health(){return $this->auth->can_view_health();}
	/** @return bool */ public function can_retry(){return $this->auth->can_retry();}
	/** @param string $bucket Bucket. @param int $limit Limit. @param int $window Window. @return true|WP_Error */ private function rate_limit($bucket,$limit,$window){$key='sun_rl_'.substr(hash('sha256',$bucket),0,32);$state=get_transient($key);$state=is_array($state)?$state:array('count'=>0,'started'=>time());if(time()-(int)$state['started']>=$window){$state=array('count'=>0,'started'=>time());}++$state['count'];set_transient($key,$state,max(1,$window));if($state['count']>max(1,$limit)){return new WP_Error('sun_rate_limited',__('Too many requests. Please wait and try again.','sabri-unified-notifications'),array('status'=>429));}return true;}
	/** @return array<string,mixed> */ private function list_args(){return array('limit'=>array('sanitize_callback'=>'absint'),'before_id'=>array('sanitize_callback'=>'absint'),'status'=>array('sanitize_callback'=>'sanitize_key'),'category'=>array('sanitize_callback'=>'sanitize_key'),'priority'=>array('sanitize_callback'=>'sanitize_key'));}
}
