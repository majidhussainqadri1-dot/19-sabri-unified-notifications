<?php
/** REST endpoints for advanced attention, intelligence, rules, routing, experiments and trace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Advanced_REST {
    /** @var SUN_Attention_Service */ private $attention; /** @var SUN_Intelligence_Service */ private $intelligence; /** @var SUN_Automation_Service */ private $automation; /** @var SUN_Routing_Service */ private $routing; /** @var SUN_Experiments_Service */ private $experiments; /** @var SUN_Trace_Service */ private $trace; /** @var SUN_Auth */ private $auth;
    public function __construct( SUN_Attention_Service $attention, SUN_Intelligence_Service $intelligence, SUN_Automation_Service $automation, SUN_Routing_Service $routing, SUN_Experiments_Service $experiments, SUN_Trace_Service $trace, SUN_Auth $auth ) { $this->attention=$attention;$this->intelligence=$intelligence;$this->automation=$automation;$this->routing=$routing;$this->experiments=$experiments;$this->trace=$trace;$this->auth=$auth; }

    /** @return void */
    public function register_routes() {
        register_rest_route( SUN_REST_NAMESPACE, '/attention/profile', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'profile'),'permission_callback'=>array($this,'logged_in') ), array( 'methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'update_profile'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/attention/priority', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'priority'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/attention/search', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'search'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/attention/history', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'history'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/notifications/(?P<id>[a-f0-9\-]{36})/attention', array( array( 'methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'mutate_attention'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/notifications/(?P<id>[a-f0-9\-]{36})/why', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'why'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/notifications/(?P<id>[a-f0-9\-]{36})/actions/(?P<action>[a-z0-9_\-]+)', array( array( 'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'execute_action'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/intelligence/catchup', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'catchup'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/intelligence/assistant', array( array( 'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'assistant'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/rules', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'rules'),'permission_callback'=>array($this,'logged_in') ), array( 'methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'save_rule'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/rules/(?P<id>[a-f0-9\-]{36})', array( array( 'methods'=>WP_REST_Server::DELETABLE,'callback'=>array($this,'delete_rule'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/devices/profiles', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'device_profiles'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/devices/(?P<id>[a-f0-9\-]{36})/attention', array( array( 'methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'update_device_profile'),'permission_callback'=>array($this,'logged_in') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/routing/cost', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'routing_cost'),'permission_callback'=>array($this,'can_view_routing_cost') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/experiments/simulate', array( array( 'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'simulate'),'permission_callback'=>array($this,'can_manage_experiments') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/experiments', array( array( 'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'create_experiment'),'permission_callback'=>array($this,'can_manage_experiments') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/experiments/(?P<id>[a-f0-9\-]{36})/status', array( array( 'methods'=>WP_REST_Server::EDITABLE,'callback'=>array($this,'experiment_status'),'permission_callback'=>array($this,'can_manage_experiments') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/traces/(?P<trace>[A-Za-z0-9._:\-]{1,100})', array( array( 'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'trace'),'permission_callback'=>array($this,'can_view_trace') ) ) );
        register_rest_route( SUN_REST_NAMESPACE, '/synthetic', array( array( 'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'synthetic'),'permission_callback'=>array($this,'can_synthetic') ) ) );
    }

    public function profile(){return rest_ensure_response(array('profile'=>$this->attention->profile(get_current_user_id()),'focus_modes'=>$this->attention->focus_modes()));}
    public function update_profile($r){$result=$this->attention->update_profile(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function priority($r){return rest_ensure_response($this->attention->priority_inbox(get_current_user_id(),$r->get_params()));}
    public function search($r){return rest_ensure_response($this->attention->search(get_current_user_id(),sanitize_text_field((string)$r['q']),$r->get_params()));}
    public function history($r){return rest_ensure_response($this->attention->history(get_current_user_id(),absint($r['days']?:0)?:null));}
    public function mutate_attention($r){$input=$r->get_json_params()?:$r->get_params();$result=$this->attention->mutate_state(get_current_user_id(),$r['id'],sanitize_key((string)($input['action']??'')),$input['value']??null,isset($input['version'])?absint($input['version']):null);return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function why($r){$result=$this->attention->why(get_current_user_id(),$r['id']);return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function execute_action($r){$limited=$this->rate_limit('advanced-action:'.get_current_user_id(),60,MINUTE_IN_SECONDS);if(is_wp_error($limited)){return$limited;}$result=$this->attention->execute_action(get_current_user_id(),$r['id'],$r['action']);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true,'result'=>$result));}
    public function catchup($r){return rest_ensure_response($this->intelligence->catchup_summary(get_current_user_id(),absint($r['hours']?:24)));}
    public function assistant($r){$input=$r->get_json_params()?:$r->get_params();$result=$this->intelligence->assistant(get_current_user_id(),(string)($input['query']??''));return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function rules(){return rest_ensure_response(array('items'=>$this->automation->list_rules(get_current_user_id()),'trigger_types'=>$this->automation->trigger_types(),'action_types'=>$this->automation->action_types()));}
    public function save_rule($r){$result=$this->automation->upsert_rule(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function delete_rule($r){$result=$this->automation->remove_rule(get_current_user_id(),$r['id']);return is_wp_error($result)?$result:rest_ensure_response(array('success'=>true));}
    public function device_profiles(){return rest_ensure_response(array('items'=>$this->attention->device_profiles(get_current_user_id())));}
    public function update_device_profile($r){$result=$this->attention->update_device_profile(get_current_user_id(),$r['id'],$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function routing_cost($r){return rest_ensure_response($this->routing->cost_snapshot(get_current_user_id(),sanitize_key((string)$r['channel'])));}
    public function simulate($r){$input=$r->get_json_params()?:$r->get_params();return rest_ensure_response($this->experiments->simulate_policy((array)($input['candidate']??array()),absint($input['days']??7)));}
    public function create_experiment($r){$result=$this->experiments->create(get_current_user_id(),$r->get_json_params()?:$r->get_params());return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function experiment_status($r){$input=$r->get_json_params()?:$r->get_params();$result=$this->experiments->set_status($r['id'],sanitize_key((string)($input['status']??'')));return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function trace($r){return rest_ensure_response(array('trace_id'=>$r['trace'],'spans'=>$this->trace->explorer($r['trace'])));}
    public function synthetic($r){$input=$r->get_json_params()?:$r->get_params();return rest_ensure_response($this->trace->synthetic_test((array)($input['channels']??array('in_app','email','push','sms','whatsapp','rcs'))));}

    /** @return bool|WP_Error */ public function logged_in(){return is_user_logged_in()&&$this->auth->is_recipient_eligible(get_current_user_id())?true:new WP_Error('sun_auth_required',__('Authentication and account eligibility are required.','sabri-unified-notifications'),array('status'=>401));}
    /** @return bool */ public function can_manage_experiments(){return is_user_logged_in()&&$this->auth->is_recipient_eligible(get_current_user_id())&&current_user_can('manage_sabri_notification_experiments');}
    /** @return bool */ public function can_view_trace(){return is_user_logged_in()&&$this->auth->is_recipient_eligible(get_current_user_id())&&current_user_can('view_sabri_notification_trace');}
    /** @return bool */ public function can_synthetic(){return is_user_logged_in()&&$this->auth->is_recipient_eligible(get_current_user_id())&&current_user_can('run_sabri_notification_synthetic_tests');}
    /** @return bool */ public function can_view_routing_cost(){return is_user_logged_in()&&$this->auth->can_view_health();}
    /** @param string $bucket Bucket. @param int $limit Limit. @param int $window Window. @return true|WP_Error */ private function rate_limit($bucket,$limit,$window){$key='sun_adv_rl_'.substr(hash('sha256',$bucket),0,32);$state=get_transient($key);$state=is_array($state)?$state:array('count'=>0,'started'=>time());if(time()-(int)$state['started']>=$window){$state=array('count'=>0,'started'=>time());}++$state['count'];set_transient($key,$state,max(1,$window));return $state['count']>max(1,$limit)?new WP_Error('sun_rate_limited',__('Too many requests. Please wait and try again.','sabri-unified-notifications'),array('status'=>429)):true;}
}
