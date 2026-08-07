<?php
/**
 * Restricted administration, diagnostics, templates, queues and bulk notices.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Admin {
	/** @var SUN_Health */ private $health;
	/** @var SUN_Reconciliation */ private $reconciliation;
	/** @var SUN_Bulk_Service */ private $bulk;
	/** @var SUN_Auth */ private $auth;
	/** @param SUN_Health $health Health. @param SUN_Reconciliation $reconciliation Reconciliation. @param SUN_Bulk_Service $bulk Bulk. @param SUN_Auth $auth Auth. */
	public function __construct(SUN_Health $health,SUN_Reconciliation $reconciliation,SUN_Bulk_Service $bulk,SUN_Auth $auth){$this->health=$health;$this->reconciliation=$reconciliation;$this->bulk=$bulk;$this->auth=$auth;}
	/** @return void */ public function register(){add_action('admin_menu',array($this,'menu'));add_action('admin_post_sun_run_reconciliation',array($this,'run_reconciliation'));add_action('admin_post_sun_export_health',array($this,'export_health'));add_action('admin_post_sun_bulk_preview',array($this,'bulk_preview'));add_action('admin_post_sun_bulk_confirm',array($this,'bulk_confirm'));}
	/** @return void */ public function menu(){add_menu_page(__('Notifications','sabri-unified-notifications'),__('Notifications','sabri-unified-notifications'),'manage_sabri_notifications','sabri-notifications',array($this,'render'),'dashicons-bell',58);}
	/** @return void */ public function render(){if(!$this->auth->can_manage()){wp_die(esc_html__('Access denied.','sabri-unified-notifications'));}$health=$this->health->snapshot();$recent=$this->recent_deliveries();$dead=$this->dead_letters();include SUN_PATH.'templates/admin.php';}
	/** @return void */ public function run_reconciliation(){check_admin_referer('sun_run_reconciliation');if(!$this->auth->can_manage()){wp_die('Forbidden',403);}$this->reconciliation->run();wp_safe_redirect(admin_url('admin.php?page=sabri-notifications&notice=reconciled'));exit;}
	/** @return void */ public function export_health(){check_admin_referer('sun_export_health');if(!$this->auth->can_view_health()){wp_die('Forbidden',403);}nocache_headers();header('Content-Type: application/json; charset=UTF-8');header('Content-Disposition: attachment; filename="file-19-health-'.gmdate('Ymd-His').'.json"');echo wp_json_encode($this->health->sanitized_export(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);exit;}
	/** @return void */ public function bulk_preview(){check_admin_referer('sun_bulk_preview');$ids=preg_split('/[\s,]+/',sanitize_textarea_field(wp_unslash($_POST['user_ids']??'')));$result=$this->bulk->preview((array)$ids,array('title'=>sanitize_text_field(wp_unslash($_POST['title']??'')),'summary'=>sanitize_textarea_field(wp_unslash($_POST['summary']??'')),'priority'=>sanitize_key($_POST['priority']??'normal'),'deep_link'=>esc_url_raw(wp_unslash($_POST['deep_link']??''))));if(is_wp_error($result)){wp_die(esc_html($result->get_error_message()));}set_transient('sun_bulk_preview_'.get_current_user_id(),$result,10*MINUTE_IN_SECONDS);wp_safe_redirect(admin_url('admin.php?page=sabri-notifications&notice=bulk-preview'));exit;}
	/** @return void */ public function bulk_confirm(){check_admin_referer('sun_bulk_confirm');$result=$this->bulk->confirm(sanitize_text_field($_POST['job_id']??''),sanitize_text_field($_POST['confirmation_code']??''));if(is_wp_error($result)){wp_die(esc_html($result->get_error_message()));}delete_transient('sun_bulk_preview_'.get_current_user_id());wp_safe_redirect(admin_url('admin.php?page=sabri-notifications&notice=bulk-queued'));exit;}
	/** @return array<int,array<string,mixed>> */ private function recent_deliveries(){global $wpdb;return (array)$wpdb->get_results("SELECT public_id,channel,status,attempt_count,last_error_code,created_at,updated_at FROM ".SUN_Database::table('deliveries')." ORDER BY id DESC LIMIT 25",ARRAY_A);} // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
	/** @return array<int,array<string,mixed>> */ private function dead_letters(){global $wpdb;return (array)$wpdb->get_results("SELECT public_id,object_type,error_code,error_safe,attempt_count,status,created_at FROM ".SUN_Database::table('dead_letters')." WHERE status='open' ORDER BY id DESC LIMIT 25",ARRAY_A);} // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
}
