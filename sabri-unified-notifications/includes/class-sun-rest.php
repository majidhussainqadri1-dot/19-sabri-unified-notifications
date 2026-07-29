<?php
defined('ABSPATH') || exit;

final class SUN_REST {
    private const NS='sabri-notifications/v1';
    public static function init(): void { add_action('rest_api_init',[self::class,'register_routes']); add_filter('rest_post_dispatch',[self::class,'no_cache_private_responses'],10,3); }

    public static function no_cache_private_responses(WP_REST_Response $response, WP_REST_Server $server, WP_REST_Request $request): WP_REST_Response {
        if (str_starts_with($request->get_route(), '/' . self::NS) && $request->get_route() !== '/' . self::NS . '/health') {
            $response->header('Cache-Control','private, no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma','no-cache');
            $response->header('X-Robots-Tag','noindex, nofollow, noarchive, nosnippet');
        }
        return $response;
    }
    public static function register_routes(): void {
        register_rest_route(self::NS,'/health',['methods'=>'GET','callback'=>[self::class,'health'],'permission_callback'=>'__return_true']);
        register_rest_route(self::NS,'/health/details',['methods'=>'GET','callback'=>[self::class,'health_details'],'permission_callback'=>static fn():bool=>current_user_can('manage_options')]);
        register_rest_route(self::NS,'/notifications',['methods'=>'GET','callback'=>[self::class,'notifications'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/notifications/read',['methods'=>'POST','callback'=>[self::class,'mark_read'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/notifications/seen',['methods'=>'POST','callback'=>[self::class,'mark_seen'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/notifications/archive',['methods'=>'POST','callback'=>[self::class,'archive'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/notifications/unarchive',['methods'=>'POST','callback'=>[self::class,'unarchive'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/preferences',[
            ['methods'=>'GET','callback'=>[self::class,'get_preferences'],'permission_callback'=>[self::class,'logged_in']],
            ['methods'=>'POST','callback'=>[self::class,'save_preferences'],'permission_callback'=>[self::class,'logged_in']],
        ]);
        register_rest_route(self::NS,'/devices',[
            ['methods'=>'GET','callback'=>[self::class,'devices'],'permission_callback'=>[self::class,'logged_in']],
            ['methods'=>'POST','callback'=>[self::class,'register_device'],'permission_callback'=>[self::class,'logged_in']],
        ]);
        register_rest_route(self::NS,'/devices/(?P<id>\d+)',['methods'=>'DELETE','callback'=>[self::class,'delete_device'],'permission_callback'=>[self::class,'logged_in']]);
        register_rest_route(self::NS,'/test',['methods'=>'POST','callback'=>[self::class,'test_notification'],'permission_callback'=>static fn():bool=>current_user_can('manage_options')]);
    }
    public static function logged_in(): bool { return is_user_logged_in(); }
    public static function health(): WP_REST_Response { return rest_ensure_response(['ok'=>SUN_DB::table_exists('notifications'),'version'=>SUN_VERSION]); }
    public static function health_details(): WP_REST_Response {
        global$wpdb;$tables=[];foreach(['notifications','preferences','deliveries','devices','templates','audit_log']as$table)$tables[$table]=SUN_DB::table_exists($table);
        return rest_ensure_response(['ok'=>!in_array(false,$tables,true),'version'=>SUN_VERSION,'dbVersion'=>(string)get_option('sun_db_version'),'tables'=>$tables,'page'=>SUN_Utils::page_url(),'emailEnabled'=>(bool)get_option('sun_email_enabled',1),'smsConfigured'=>SUN_Utils::validate_webhook_url((string)get_option('sun_sms_webhook_url',''))!=='','pushConfigured'=>SUN_Utils::validate_webhook_url((string)get_option('sun_push_webhook_url',''))!=='','marketplaceDetected'=>SUN_DB::external_table_exists($wpdb->prefix.'smp_notifications'),'networkDetected'=>SUN_DB::external_table_exists($wpdb->prefix.'sn_notifications')]);
    }

    public static function notifications(WP_REST_Request $request): WP_REST_Response {
        global$wpdb;$user_id=get_current_user_id();
        // External source synchronization is throttled and no longer executed on every polling request.
        if(SUN_Utils::rate_limit('sync-user:'.$user_id,1,MINUTE_IN_SECONDS))SUN_Integrations::sync_current_user($user_id);
        $category=sanitize_key((string)$request->get_param('category'));$unread=filter_var($request->get_param('unread'),FILTER_VALIDATE_BOOLEAN);$after=absint($request->get_param('after_id'));$page=max(1,absint($request->get_param('page'))?:1);$per=max(5,min(100,absint($request->get_param('per_page'))?:30));$archived=filter_var($request->get_param('archived'),FILTER_VALIDATE_BOOLEAN);$offset=($page-1)*$per;
        $where=['user_id=%d',$archived?'archived_at IS NOT NULL':'archived_at IS NULL','(expires_at IS NULL OR expires_at>%s)'];$params=[$user_id,SUN_Utils::now()];
        if($category&&array_key_exists($category,SUN_Utils::allowed_categories())){$where[]='category=%s';$params[]=$category;}if($unread)$where[]='read_at IS NULL';if($after){$where[]='id>%d';$params[]=$after;}
        $sql=implode(' AND ',$where);$query='SELECT * FROM '.SUN_DB::table('notifications')." WHERE $sql ORDER BY id DESC LIMIT %d OFFSET %d";$rows=$wpdb->get_results($wpdb->prepare($query,...array_merge($params,[$per,$offset])),ARRAY_A);
        $active_clause=$archived?'archived_at IS NOT NULL':'archived_at IS NULL';
        $unread_count=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.SUN_DB::table('notifications')." WHERE user_id=%d AND read_at IS NULL AND $active_clause AND (expires_at IS NULL OR expires_at>%s)",$user_id,SUN_Utils::now()));
        $total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.SUN_DB::table('notifications')." WHERE user_id=%d AND $active_clause AND (expires_at IS NULL OR expires_at>%s)",$user_id,SUN_Utils::now()));
        $category_rows=$wpdb->get_results($wpdb->prepare('SELECT category,COUNT(*) c FROM '.SUN_DB::table('notifications')." WHERE user_id=%d AND $active_clause AND (expires_at IS NULL OR expires_at>%s) GROUP BY category",$user_id,SUN_Utils::now()),ARRAY_A);$counts=array_fill_keys(array_keys(SUN_Utils::allowed_categories()),0);foreach($category_rows?:[]as$row)$counts[(string)$row['category']]=(int)$row['c'];
        $latest=(int)$wpdb->get_var($wpdb->prepare('SELECT COALESCE(MAX(id),0) FROM '.SUN_DB::table('notifications').' WHERE user_id=%d',$user_id));
        return rest_ensure_response(['notifications'=>array_map([SUN_Core::class,'format_notification'],$rows?:[]),'unread'=>$unread_count,'total'=>$total,'categoryCounts'=>$counts,'latestId'=>$latest,'page'=>$page]);
    }

    private static function ids(WP_REST_Request $request): array { return array_values(array_unique(array_filter(array_map('absint',(array)$request->get_param('ids'))))); }
    public static function mark_read(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global$wpdb;$user_id=get_current_user_id();$all=filter_var($request->get_param('all'),FILTER_VALIDATE_BOOLEAN);$ids=self::ids($request);$now=SUN_Utils::now();if(!$all&&!$ids)return new WP_Error('missing_ids','Select at least one notification.',['status'=>400]);
        if($all){$source=$wpdb->get_results($wpdb->prepare('SELECT source,source_id FROM '.SUN_DB::table('notifications').' WHERE user_id=%d AND read_at IS NULL',$user_id),ARRAY_A);$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('notifications').' SET read_at=%s,seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND read_at IS NULL',$now,$now,$now,$user_id));}
        else{$ph=implode(',',array_fill(0,count($ids),'%d'));$source=$wpdb->get_results($wpdb->prepare('SELECT source,source_id FROM '.SUN_DB::table('notifications')." WHERE user_id=%d AND id IN ($ph)",$user_id,...$ids),ARRAY_A);$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('notifications')." SET read_at=%s,seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($ph)",$now,$now,$now,$user_id,...$ids));}
        SUN_Integrations::propagate_read($source?:[]);SUN_Utils::audit('notifications_read','user',$user_id,['all'=>$all,'ids'=>$ids]);return rest_ensure_response(['ok'=>true]);
    }
    public static function mark_seen(WP_REST_Request $request): WP_REST_Response { global$wpdb;$ids=self::ids($request);if(!$ids)return rest_ensure_response(['ok'=>true]);$ph=implode(',',array_fill(0,count($ids),'%d'));$now=SUN_Utils::now();$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('notifications')." SET seen_at=COALESCE(seen_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($ph)",$now,$now,get_current_user_id(),...$ids));SUN_Utils::audit('notifications_seen','user',get_current_user_id(),['ids'=>$ids]);return rest_ensure_response(['ok'=>true]); }
    public static function archive(WP_REST_Request $request): WP_REST_Response|WP_Error { return self::set_archive($request,true); }
    public static function unarchive(WP_REST_Request $request): WP_REST_Response|WP_Error { return self::set_archive($request,false); }
    private static function set_archive(WP_REST_Request $request,bool $archive): WP_REST_Response|WP_Error { global$wpdb;$ids=self::ids($request);if(!$ids)return new WP_Error('missing_ids','Select at least one notification.',['status'=>400]);$ph=implode(',',array_fill(0,count($ids),'%d'));$now=SUN_Utils::now();if($archive)$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('notifications')." SET archived_at=%s,read_at=COALESCE(read_at,%s),updated_at=%s WHERE user_id=%d AND id IN ($ph)",$now,$now,$now,get_current_user_id(),...$ids));else$wpdb->query($wpdb->prepare('UPDATE '.SUN_DB::table('notifications')." SET archived_at=NULL,updated_at=%s WHERE user_id=%d AND id IN ($ph)",$now,get_current_user_id(),...$ids));SUN_Utils::audit($archive?'notifications_archived':'notifications_unarchived','user',get_current_user_id(),['ids'=>$ids]);return rest_ensure_response(['ok'=>true]); }

    public static function get_preferences(): WP_REST_Response { return rest_ensure_response(['preferences'=>SUN_Core::get_preferences(get_current_user_id()),'categories'=>SUN_Utils::allowed_categories()]); }
    public static function save_preferences(WP_REST_Request $request): WP_REST_Response { $params=$request->get_json_params();return rest_ensure_response(['preferences'=>SUN_Core::save_preferences(get_current_user_id(),is_array($params)?$params:[])]); }
    public static function devices(): WP_REST_Response { global$wpdb;$rows=$wpdb->get_results($wpdb->prepare('SELECT id,device_type,device_name,enabled,last_seen_at,created_at FROM '.SUN_DB::table('devices').' WHERE user_id=%d ORDER BY id DESC',get_current_user_id()),ARRAY_A);return rest_ensure_response(['devices'=>$rows?:[]]); }

    public static function register_device(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global$wpdb;$user_id=get_current_user_id();if(!SUN_Utils::rate_limit('device:'.$user_id,10,HOUR_IN_SECONDS))return new WP_Error('rate_limited','Too many device registrations.',['status'=>429]);
        $token=trim((string)$request->get_param('token'));if($token===''||strlen($token)>8192)return new WP_Error('invalid_token','A valid device token is required.',['status'=>400]);$hash=hash('sha256',$token);
        $existing=$wpdb->get_row($wpdb->prepare('SELECT id,user_id FROM '.SUN_DB::table('devices').' WHERE token_hash=%s LIMIT 1',$hash),ARRAY_A);
        if(is_array($existing)&&(int)$existing['user_id']!==$user_id)return new WP_Error('token_owned','This device token is already registered to another account.',['status'=>409]);
        $encrypted=SUN_Utils::encrypt_secret($token);if($encrypted==='')return new WP_Error('encryption_failed','The device token could not be secured.',['status'=>500]);$now=SUN_Utils::now();$metadata=$request->get_param('metadata');$metadata=is_array($metadata)?SUN_Utils::redact_array($metadata):[];
        $data=['user_id'=>$user_id,'device_type'=>sanitize_key((string)$request->get_param('device_type'))?:'web','device_name'=>sanitize_text_field((string)$request->get_param('device_name')),'token'=>$encrypted,'token_hash'=>$hash,'endpoint'=>esc_url_raw((string)$request->get_param('endpoint'),['https']),'metadata'=>SUN_Utils::json_encode($metadata),'enabled'=>1,'last_seen_at'=>$now,'updated_at'=>$now];
        if(is_array($existing)){$id=(int)$existing['id'];$wpdb->update(SUN_DB::table('devices'),$data,['id'=>$id,'user_id'=>$user_id]);}else{$data['created_at']=$now;$wpdb->insert(SUN_DB::table('devices'),$data);$id=(int)$wpdb->insert_id;}
        $waiting=$wpdb->get_col($wpdb->prepare("SELECT notification_id FROM ".SUN_DB::table('deliveries')." WHERE user_id=%d AND channel='push' AND device_id=0 AND status='waiting_config'",$user_id));
        foreach($waiting?:[] as$notification_id)SUN_Channels::queue((int)$notification_id,$user_id,'push');
        if($waiting)$wpdb->query($wpdb->prepare("UPDATE ".SUN_DB::table('deliveries')." SET status='cancelled',last_error='Replaced by per-device delivery records.',updated_at=%s WHERE user_id=%d AND channel='push' AND device_id=0 AND status='waiting_config'",SUN_Utils::now(),$user_id));
        SUN_Utils::audit('device_registered','device',$id,['user_id'=>$user_id,'device_type'=>$data['device_type']]);return rest_ensure_response(['ok'=>true,'id'=>$id]);
    }
    public static function delete_device(WP_REST_Request $request): WP_REST_Response { global$wpdb;$id=absint($request['id']);$deleted=$wpdb->delete(SUN_DB::table('devices'),['id'=>$id,'user_id'=>get_current_user_id()],['%d','%d']);if($deleted)SUN_Utils::audit('device_deleted','device',$id);return rest_ensure_response(['ok'=>(bool)$deleted]); }
    public static function test_notification(WP_REST_Request $request): WP_REST_Response { $user_id=absint($request->get_param('user_id'))?:get_current_user_id();$id=SUN_Core::create(['user_id'=>$user_id,'actor_user_id'=>get_current_user_id(),'category'=>'system','type'=>'system_test','priority'=>'normal','sensitivity'=>'private','title'=>'Notification system test','body'=>'The unified notification engine is working correctly.','link'=>SUN_Utils::page_url(),'dedupe_key'=>'admin-test:'.wp_generate_uuid4(),'allow_self'=>true]);return rest_ensure_response(['ok'=>$id>0,'notificationId'=>$id]); }
}
