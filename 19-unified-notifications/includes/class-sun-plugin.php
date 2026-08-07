<?php
/**
 * Plugin dependency graph and WordPress lifecycle coordinator.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Plugin {
	/** @var self|null */ private static $instance=null;
	/** @var SUN_Auth */ private $auth;
	/** @var SUN_Producer_Registry */ private $registry;
	/** @var SUN_Preferences */ private $preferences;
	/** @var SUN_Subscriptions */ private $subscriptions;
	/** @var SUN_Notification_Service */ private $notifications;
	/** @var SUN_Renderer */ private $renderer;
	/** @var SUN_Health */ private $health;
	/** @var SUN_Bulk_Service */ private $bulk;
	/** @return self */ public static function instance(){if(null===self::$instance){self::$instance=new self();}return self::$instance;}
	private function __construct(){}
	/** @return void */
	public function boot(){
		$this->auth=new SUN_Auth();
		$this->registry=new SUN_Producer_Registry();
		$templates=new SUN_Template_Engine();
		$this->preferences=new SUN_Preferences($this->auth);
		$this->subscriptions=new SUN_Subscriptions();
		$policy=new SUN_Policy_Engine($this->preferences,$this->registry,$this->subscriptions);
		$validator=new SUN_Event_Validator($this->registry);
		$delivery=new SUN_Delivery_Service($templates,$this->auth);
		$this->notifications=new SUN_Notification_Service($validator,$policy,$templates,$delivery,$this->auth);
		$reconciliation=new SUN_Reconciliation($delivery,$this->notifications);
		$value_metrics=new SUN_Value_Metrics();
		$this->health=new SUN_Health($delivery,$value_metrics);
		$this->bulk=new SUN_Bulk_Service($this->notifications,$this->auth);
		$this->renderer=new SUN_Renderer($this->notifications,$this->preferences,$this->subscriptions,$this->auth);
		$rest=new SUN_REST_Controller($this->notifications,$this->preferences,$this->subscriptions,$delivery,$reconciliation,$this->health,$this->auth,$this->registry);
		$router=new SUN_Router($this->renderer,$this->notifications);
		$admin=new SUN_Admin($this->health,$reconciliation,$this->bulk,$this->auth);
		$privacy=new SUN_Privacy();

		add_action('plugins_loaded',array($this,'load_textdomain'));
		add_action('plugins_loaded',array('SUN_Subscriptions','maybe_install'),20);
		$this->register_cron_interval();
		add_action('init',array($router,'register'),5);
		add_action('rest_api_init',array($rest,'register_routes'));
		add_action('wp_enqueue_scripts',array($this->renderer,'enqueue_assets'));
		add_shortcode('sabri_notification_bell',array($this->renderer,'render_bell'));
		add_shortcode('sabri_notifications',array($this,'center_shortcode'));
		add_shortcode('sabri_notification_settings',array($this->renderer,'render_settings'));
		add_action('sun_process_delivery_queue',array($delivery,'process_queue'));
		add_action('sun_reconcile_notifications',array($reconciliation,'run'));
		add_action('sun_expire_notifications',array($this->notifications,'expire_due'));
		add_action('sun_process_bulk_jobs',array($this->bulk,'process'));
		if ( is_admin() ) { $admin->register(); }
		$privacy->register();
		add_action('sun_file20_notification_slot',array($this,'output_bell'));
		add_filter('sun_file20_notification_contract',array($this,'shell_contract'));
		add_action('upgrader_process_complete',array($this,'maybe_upgrade'),10,2);
	}
	/** @return void */ public function load_textdomain(){load_plugin_textdomain(SUN_TEXT_DOMAIN,false,dirname(SUN_BASENAME).'/languages');}
	/** @param array<string,mixed> $schedules Schedules. @return array<string,mixed> */ public function register_cron_interval(){add_filter('cron_schedules',static function($items){$items['sun_every_minute']=array('interval'=>60,'display'=>__('Every minute (File 19)','sabri-unified-notifications'));return $items;});}
	/** @param array<string,mixed> $atts Attributes. @return string */ public function center_shortcode($atts=array()){return $this->renderer->render_center(shortcode_atts(array(),$atts,'sabri_notifications'));}
	/** @return void */ public function output_bell(){echo $this->renderer->render_bell();} // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	/** @param array<string,mixed> $contract Contract. @return array<string,mixed> */ public function shell_contract($contract){$contract['file19']=array('version'=>SUN_VERSION,'slot'=>'sun_file20_notification_slot','bell_shortcode'=>'[sabri_notification_bell]','center'=>home_url('/notifications/'),'settings'=>home_url('/settings/notifications/'),'subscriptions_rest'=>rest_url(SUN_REST_NAMESPACE.'/subscriptions'));return $contract;}
	/** @return void */ public function maybe_upgrade(){if(SUN_DB_VERSION!==get_option('sun_db_version')){SUN_Activator::install_schema();update_option('sun_db_version',SUN_DB_VERSION,false);SUN_Activator::schedule_events();}SUN_Subscriptions::maybe_install();}
	/** @return SUN_Notification_Service */ public function notifications(){return $this->notifications;}
	/** @return SUN_Preferences */ public function preferences(){return $this->preferences;}
	/** @return SUN_Subscriptions */ public function subscriptions(){return $this->subscriptions;}
	/** @return SUN_Auth */ public function auth(){return $this->auth;}
	/** @return SUN_Renderer */ public function renderer(){return $this->renderer;}
	/** @return SUN_Health */ public function health(){return $this->health;}
	/** @return SUN_Bulk_Service */ public function bulk(){return $this->bulk;}
}
