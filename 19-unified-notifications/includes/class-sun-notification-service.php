<?php
/**
 * Canonical notification entity and event-consumption service.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Notification_Service {
	/** @var SUN_Event_Validator */ private $validator;
	/** @var SUN_Policy_Engine */ private $policy;
	/** @var SUN_Template_Engine */ private $templates;
	/** @var SUN_Delivery_Service */ private $delivery;
	/** @var SUN_Auth */ private $auth;

	/** @param SUN_Event_Validator $validator Validator. @param SUN_Policy_Engine $policy Policy. @param SUN_Template_Engine $templates Templates. @param SUN_Delivery_Service $delivery Delivery. @param SUN_Auth $auth Auth. */
	public function __construct( SUN_Event_Validator $validator, SUN_Policy_Engine $policy, SUN_Template_Engine $templates, SUN_Delivery_Service $delivery, SUN_Auth $auth ) {
		$this->validator = $validator;
		$this->policy    = $policy;
		$this->templates = $templates;
		$this->delivery  = $delivery;
		$this->auth      = $auth;
	}

	/** @param array<string,mixed> $event Raw event. @param string $source Ingestion source. @return array<string,mixed>|WP_Error */
	public function ingest_event( array $event, $source = 'php' ) {
		global $wpdb;
		$event = $this->validator->validate( $event );
		if ( is_wp_error( $event ) ) { return $event; }
		$events = SUN_Database::table( 'events' );
		$found  = $wpdb->get_row( $wpdb->prepare( "SELECT id,public_id,status FROM {$events} WHERE producer=%s AND event_id=%s LIMIT 1", $event['producer'], $event['event_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $found ) { return array( 'event_public_id' => $found['public_id'], 'status' => 'duplicate', 'created' => 0, 'suppressed' => 0 ); }
		$payload_json = SUN_Database::canonical_json( $event );
		$cipher = SUN_Crypto::encrypt( $payload_json );
		if ( is_wp_error( $cipher ) ) { return $cipher; }
		$event_public_id = SUN_Database::uuid();
		$created = 0;
		$suppressed = 0;
		SUN_Database::begin();
		try {
			$inserted = $wpdb->insert(
				$events,
				array(
					'public_id'=>$event_public_id,'producer'=>$event['producer'],'event_id'=>$event['event_id'],'event_type'=>$event['event_type'],
					'schema_version'=>$event['schema_version'],'owner'=>$event['owner'],'occurred_at'=>$event['occurred_at'],'trace_id'=>$event['trace_id'],
					'payload_hash'=>hash('sha256',$payload_json),'payload_ciphertext'=>$cipher,'status'=>'processing','created_at'=>SUN_Database::now(),'updated_at'=>SUN_Database::now(),
				),
				array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' )
			);
			if ( false === $inserted ) { throw new RuntimeException( 'event_insert_failed' ); }
			foreach ( $event['recipients'] as $recipient ) {
				if ( ! $this->auth->is_recipient_eligible( (int) $recipient['user_id'] ) ) { ++$suppressed; continue; }
				$decision = $this->policy->decide( $event, $recipient );
				if ( is_wp_error( $decision ) ) { throw new RuntimeException( $decision->get_error_code() ); }
				if ( ! empty( $decision['suppressed'] ) ) {
					++$suppressed;
					SUN_Audit::record( 'notification_suppressed', 'event_recipient', $event_public_id . ':' . (int) $recipient['user_id'], array( 'reason' => sanitize_key( (string) ( $decision['suppress_reason'] ?? 'policy' ) ), 'trace_id' => $event['trace_id'], 'purpose' => 'user_preference' ), 0 );
					continue;
				}
				$result = $this->create_notification( $event, $recipient, $decision );
				if ( is_wp_error( $result ) ) {
					if ( 'sun_notification_duplicate' === $result->get_error_code() ) { continue; }
					throw new RuntimeException( $result->get_error_code() );
				}
				++$created;
			}
			$wpdb->update( $events, array( 'status'=>'processed','updated_at'=>SUN_Database::now() ), array( 'producer'=>$event['producer'],'event_id'=>$event['event_id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			SUN_Database::commit();
		} catch ( Throwable $exception ) {
			SUN_Database::rollback();
			return new WP_Error( 'sun_event_processing_failed', __( 'The event could not be processed safely.', 'sabri-unified-notifications' ), array( 'trace_id'=>$event['trace_id'],'reason'=>sanitize_key($exception->getMessage()) ) );
		}
		SUN_Audit::record( 'event_ingested', 'event', $event_public_id, array( 'producer'=>$event['producer'],'event_type'=>$event['event_type'],'created'=>$created,'suppressed'=>$suppressed,'source'=>sanitize_key($source),'trace_id'=>$event['trace_id'],'purpose'=>'event_intake' ), 0 );
		do_action( 'sun_event_processed', $event, $created, $suppressed );
		return array( 'event_public_id'=>$event_public_id,'status'=>'processed','created'=>$created,'suppressed'=>$suppressed );
	}

	/** @param array<string,mixed> $event Event. @param array<string,mixed> $recipient Recipient. @param array<string,mixed> $decision Policy. @return array<string,mixed>|WP_Error */
	private function create_notification( array $event, array $recipient, array $decision ) {
		global $wpdb;
		$user_id = (int) $recipient['user_id'];
		$locale = $recipient['locale'] ?: ( $this->auth->assertions( $user_id )['locale'] ?? 'en_US' );
		$template = $this->templates->resolve( $event['event_type'], 'in_app', $locale, $event['template_key'] );
		$variables = $this->variables( $event );
		$rendered = $this->templates->render( $template, $variables, 'in_app', $decision['sensitivity'] );
		$deep_link = SUN_Deep_Link::sanitize( $event['deep_link'] );
		$dedupe = hash( 'sha256', implode( '|', array( $event['producer'],$event['event_id'],$user_id,$decision['policy_key'],$template['template_key'],$template['version'] ) ) );
		$table = SUN_Database::table( 'notifications' );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE dedupe_key=%s LIMIT 1", $dedupe ) ) ) { return new WP_Error( 'sun_notification_duplicate', __( 'This notification already exists.', 'sabri-unified-notifications' ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data_cipher = SUN_Crypto::encrypt( SUN_Database::canonical_json( array( 'variables'=>$variables,'_sensitivity'=>$decision['sensitivity'],'subject'=>$event['subject'],'actor'=>$event['actor'],'owner'=>$event['owner'] ) ) );
		if ( is_wp_error( $data_cipher ) ) { return $data_cipher; }
		$public_id = SUN_Database::uuid(); $now = SUN_Database::now();
		$ok = $wpdb->insert( $table, array(
			'public_id'=>$public_id,'recipient_id'=>$user_id,'producer'=>$event['producer'],'event_id'=>$event['event_id'],'event_type'=>$event['event_type'],
			'category'=>$decision['category'],'priority'=>$decision['priority'],'template_key'=>$template['template_key'],'template_version'=>$template['version'],
			'locale'=>sanitize_locale_name($locale),'icon'=>$this->icon_for_category($decision['category']),'title'=>$rendered['title'],'summary'=>$rendered['body'],
			'data_ciphertext'=>$data_cipher,'deep_link'=>$deep_link?:null,'deep_link_context'=>$event['deep_context']?:null,'status'=>'unread','expires_at'=>$event['expires_at'],
			'version'=>1,'dedupe_key'=>$dedupe,'created_at'=>$now,'updated_at'=>$now,
		), array( '%s','%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s' ) );
		if ( false === $ok ) { return new WP_Error( 'sun_notification_insert_failed', __( 'The notification could not be created.', 'sabri-unified-notifications' ) ); }
		$id = (int) $wpdb->insert_id;
		foreach ( $decision['deliveries'] as $delivery ) {
			$result = $this->delivery->enqueue( $id, $user_id, $delivery, $dedupe );
			if ( is_wp_error( $result ) ) { return $result; }
		}
		SUN_Audit::record( 'notification_created', 'notification', $public_id, array( 'category'=>$decision['category'],'priority'=>$decision['priority'],'trace_id'=>$event['trace_id'],'purpose'=>'notification_projection' ), 0 );
		do_action( 'sun_notification_created', $public_id, $user_id, $event, $decision );
		return array( 'id'=>$id,'public_id'=>$public_id,'dedupe_key'=>$dedupe );
	}

	/** @param int $user_id User ID. @param array<string,mixed> $args Filters. @return array<string,mixed> */
	public function list_notifications( $user_id, array $args = array() ) {
		global $wpdb;
		$limit=max(1,min(50,absint($args['limit']??20))); $before=absint($args['before_id']??PHP_INT_MAX);
		$where=array('recipient_id=%d','id<%d',"status<>'deleted'"); $params=array(absint($user_id),$before);
		if(!empty($args['status'])&&in_array($args['status'],array('unread','read','archived'),true)){$where[]='status=%s';$params[]=$args['status'];}
		if(!empty($args['category'])){$where[]='category=%s';$params[]=sanitize_key($args['category']);}
		if(!empty($args['priority'])){$where[]='priority=%s';$params[]=sanitize_key($args['priority']);}
		$params[]=$limit+1;
		$sql='SELECT id,public_id,category,priority,icon,title,summary,status,read_at,archived_at,expires_at,created_at,deep_link_context,version FROM '.SUN_Database::table('notifications').' WHERE '.implode(' AND ',$where).' ORDER BY id DESC LIMIT %d';
		$rows=$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$has_more=count($rows)>$limit; $rows=array_slice($rows,0,$limit); $next=$rows?(int)$rows[count($rows)-1]['id']:0;
		foreach($rows as &$row){$row['open_url']=SUN_Deep_Link::wrapper_url($row['public_id']);unset($row['id']);}unset($row);
		return array('items'=>$rows,'has_more'=>$has_more,'next_before_id'=>$has_more?$next:0,'unread_count'=>$this->get_unread_count($user_id));
	}

	/** @param int $user_id User ID. @param string $public_id Public ID. @return array<string,mixed>|WP_Error */
	public function get_notification( $user_id, $public_id ) {
		global $wpdb;
		$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.SUN_Database::table('notifications').' WHERE public_id=%s AND recipient_id=%d LIMIT 1',sanitize_text_field($public_id),absint($user_id)),ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if(!$row||'deleted'===$row['status']){return new WP_Error('sun_notification_not_found',__('Notification not found.','sabri-unified-notifications'),array('status'=>404));}
		return $row;
	}

	/** @param int $user_id User ID. @param string $public_id ID. @param string $action Action. @param int|null $expected_version Version. @return true|WP_Error */
	public function mutate( $user_id, $public_id, $action, $expected_version = null ) {
		global $wpdb; $row=$this->get_notification($user_id,$public_id); if(is_wp_error($row)){return $row;}
		if(null!==$expected_version&&(int)$expected_version!==(int)$row['version']){return new WP_Error('sun_notification_conflict',__('This notification changed in another session.','sabri-unified-notifications'),array('status'=>409));}
		$data=array('updated_at'=>SUN_Database::now(),'version'=>(int)$row['version']+1);
		switch($action){
			case 'read':$data['status']='read';$data['read_at']=SUN_Database::now();break;
			case 'unread':$data['status']='unread';$data['read_at']=null;$data['archived_at']=null;break;
			case 'archive':$data['status']='archived';$data['archived_at']=SUN_Database::now();break;
			case 'unarchive':$data['status']=empty($row['read_at'])?'unread':'read';$data['archived_at']=null;break;
			case 'delete':$data['status']='deleted';$data['title']='';$data['summary']='';$data['data_ciphertext']=null;$data['deep_link']=null;break;
			default:return new WP_Error('sun_notification_action_invalid',__('Notification action is invalid.','sabri-unified-notifications'),array('status'=>400));
		}
		$updated=$wpdb->update(SUN_Database::table('notifications'),$data,array('id'=>(int)$row['id'],'recipient_id'=>absint($user_id),'version'=>(int)$row['version'])); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if(1!==(int)$updated){return new WP_Error('sun_notification_conflict',__('This notification changed in another session.','sabri-unified-notifications'),array('status'=>409));}
		SUN_Audit::record('notification_'.$action,'notification',$public_id,array('purpose'=>'user_action'),$user_id); do_action('sun_notification_'.$action,$row,$user_id); return true;
	}

	/** @param int $user_id User ID. @param string $action Action. @param array<string,mixed> $filters Filters. @return int|WP_Error */
	public function bulk_mutate( $user_id, $action, array $filters = array() ) {
		global $wpdb; if(!in_array($action,array('read','archive','unarchive'),true)){return new WP_Error('sun_bulk_action_invalid',__('Bulk action is invalid.','sabri-unified-notifications'));}
		$where=array('recipient_id=%d',"status<>'deleted'");$params=array(absint($user_id));if(!empty($filters['category'])){$where[]='category=%s';$params[]=sanitize_key($filters['category']);}
		$set="status='read',read_at=%s";$set_params=array(SUN_Database::now()); if('archive'===$action){$set="status='archived',archived_at=%s";} if('unarchive'===$action){$set="status=IF(read_at IS NULL,'unread','read'),archived_at=NULL";$set_params=array();}
		$sql='UPDATE '.SUN_Database::table('notifications')." SET {$set},version=version+1,updated_at=%s WHERE ".implode(' AND ',$where).' LIMIT 1000';
		$count=$wpdb->query($wpdb->prepare($sql,array_merge($set_params,array(SUN_Database::now()),$params))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		SUN_Audit::record('notifications_bulk_'.$action,'notification_set',(string)$user_id,array('count'=>(int)$count,'purpose'=>'user_action'),$user_id); return (int)$count;
	}

	/** @param int $user_id User ID. @return int */
	public function get_unread_count( $user_id ) {
		global $wpdb;if(!$this->auth->is_recipient_eligible($user_id)){return 0;}
		return min(999,(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".SUN_Database::table('notifications')." WHERE recipient_id=%d AND status='unread' AND (expires_at IS NULL OR expires_at>%s)",absint($user_id),SUN_Database::now()))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @param string $public_id Notification ID. @return string|WP_Error */
	public function resolve_open_target( $public_id ) {
		$row=$this->get_notification(get_current_user_id(),$public_id);if(is_wp_error($row)){return $row;}
		$result=$this->mutate(get_current_user_id(),$public_id,'read',(int)$row['version']);
		if(is_wp_error($result)&&'sun_notification_conflict'!==$result->get_error_code()){return $result;}
		$target=SUN_Deep_Link::sanitize((string)$row['deep_link']);return $target?:home_url('/notifications/');
	}

	/** @return int */
	public function expire_due() {
		global $wpdb;return (int)$wpdb->query($wpdb->prepare("UPDATE ".SUN_Database::table('notifications')." SET status='expired',updated_at=%s,version=version+1 WHERE status NOT IN ('expired','deleted') AND expires_at IS NOT NULL AND expires_at<=%s",SUN_Database::now(),SUN_Database::now())); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @param array<string,mixed> $event Event. @return array<string,mixed> */
	private function variables( array $event ) {
		$data=$event['data'];return array('actor_name'=>(string)($data['actor_name']??''),'object_name'=>(string)($data['object_name']??''),'action_name'=>(string)($data['action_name']??$event['event_type']),'summary'=>(string)($data['summary']??__('A new update is available.','sabri-unified-notifications')),'site_name'=>get_bloginfo('name'));
	}

	/** @param string $category Category. @return string */
	private function icon_for_category( $category ) {
		$map=array('security'=>'shield','safety'=>'warning','clinic'=>'heart','publishing'=>'edit','learning'=>'book','social'=>'users','marketplace'=>'store','messages'=>'message','media'=>'play','system'=>'settings');return $map[$category]??'bell';
	}
}
