<?php
/** Privacy-minimized tamper-evident audit chain. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SUN_Audit {
	/** @param string $action Action. @param string $object_type Object type. @param string|int $object_id Object ID. @param array<string,mixed> $context Safe context. @param int|null $actor_id Actor ID. @return bool */
	public static function record($action,$object_type,$object_id,array $context=array(),$actor_id=null){
		global $wpdb;$table=SUN_Database::table('audit');$actor_id=null===$actor_id?get_current_user_id():absint($actor_id);$trace_id=sanitize_text_field((string)($context['trace_id']??SUN_Database::uuid()));
		$context=self::minimize_context($context);$created=SUN_Database::now();$lock=self::acquire_lock();if(!$lock){return false;}
		try{
			$prev=(string)$wpdb->get_var("SELECT entry_hash FROM {$table} ORDER BY id DESC LIMIT 1"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$body=array('action'=>sanitize_key($action),'object_type'=>sanitize_key($object_type),'object_id'=>substr(sanitize_text_field((string)$object_id),0,191),'actor_id'=>$actor_id,'purpose'=>sanitize_key((string)($context['purpose']??'operation')),'trace_id'=>substr($trace_id,0,100),'context'=>$context,'created_at'=>$created,'prev_hash'=>$prev);
			$hash=hash_hmac('sha256',SUN_Database::canonical_json($body),wp_salt('secure_auth'));
			return false!==$wpdb->insert($table,array('action'=>$body['action'],'object_type'=>$body['object_type'],'object_id'=>$body['object_id'],'actor_id'=>$body['actor_id'],'purpose'=>$body['purpose'],'trace_id'=>$body['trace_id'],'context_json'=>wp_json_encode($context),'prev_hash'=>$prev,'entry_hash'=>$hash,'created_at'=>$created),array('%s','%s','%s','%d','%s','%s','%s','%s','%s','%s'));
		}finally{self::release_lock($lock);}
	}

	/** @param mixed $value Value. @param int $depth Depth. @return mixed */
	private static function minimize_context($value,$depth=0){
		if($depth>5){return '[redacted-depth]';}
		if(is_array($value)){$out=array();$blocked=array('payload','body','title','summary','token','secret','password','authorization','cookie','ciphertext','email','phone','address','medical','message');foreach(array_slice($value,0,50,true) as $key=>$item){$safe_key=sanitize_key((string)$key);if(in_array($safe_key,$blocked,true)||preg_match('/(?:token|secret|password|auth|cookie|cipher|email|phone|address|medical|message|payload)/',$safe_key)){$out[$safe_key]='[redacted]';continue;}$out[$safe_key]=self::minimize_context($item,$depth+1);}return $out;}
		if(is_bool($value)||is_int($value)||is_float($value)||null===$value){return $value;}
		return substr(sanitize_text_field((string)$value),0,500);
	}

	/** @return string|false */
	private static function acquire_lock(){$name='sun_audit_chain_lock';$token=SUN_Database::uuid();$payload=wp_json_encode(array('token'=>$token,'created'=>time()));for($i=0;$i<4;$i++){if(add_option($name,$payload,'',false)){return $token;}$current=json_decode((string)get_option($name,''),true);if(is_array($current)&&time()-(int)($current['created']??0)>15){delete_option($name);continue;}usleep(25000);}return false;}
	/** @param string $token Token. @return void */
	private static function release_lock($token){$current=json_decode((string)get_option('sun_audit_chain_lock',''),true);if(is_array($current)&&hash_equals((string)($current['token']??''),(string)$token)){delete_option('sun_audit_chain_lock');}}
}
