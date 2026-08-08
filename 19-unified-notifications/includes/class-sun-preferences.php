<?php
/**
 * User notification preferences, quiet hours, digests and devices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SUN_Preferences {
	/** @var SUN_Auth */
	private $auth;

	/** @param SUN_Auth $auth Authorization. */
	public function __construct( SUN_Auth $auth ) {
		$this->auth = $auth;
	}

	/** @param int $user_id User ID. @param string $category Category. @param string $channel Channel. @return array<string,mixed> */
	public function get( $user_id, $category, $channel ) {
		global $wpdb;
		$category = sanitize_key( $category );
		$channel  = sanitize_key( $channel );
		$defaults = $this->defaults( $user_id, $category, $channel );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . SUN_Database::table( 'preferences' ) . ' WHERE user_id=%d AND category=%s AND channel=%s LIMIT 1',
				absint( $user_id ), $category, $channel
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! $row ) { return $defaults; }
		return array_merge(
			$defaults,
			array(
				'enabled'=>(bool)$row['enabled'],
				'digest_frequency'=>sanitize_key($row['digest_frequency']),
				'quiet_enabled'=>(bool)$row['quiet_enabled'],
				'quiet_start'=>$row['quiet_start'],
				'quiet_end'=>$row['quiet_end'],
				'timezone'=>$row['timezone']?:$defaults['timezone'],
				'version'=>(int)$row['version'],
			)
		);
	}

	/** @param int $user_id User ID. @return array<int,array<string,mixed>> */
	public function get_all( $user_id ) {
		$out=array();
		foreach($this->categories() as $category){foreach($this->channels() as $channel){$out[]=$this->get($user_id,$category,$channel);}}
		return $out;
	}

	/** @param int $user_id User ID. @param array<string,mixed> $input Input. @return array<string,mixed>|WP_Error */
	public function update( $user_id, array $input ) {
		global $wpdb;
		$category=sanitize_key((string)($input['category']??''));$channel=sanitize_key((string)($input['channel']??''));
		if(!in_array($category,$this->categories(),true)||!in_array($channel,$this->channels(),true)){
			return new WP_Error('sun_preference_invalid',__('The notification preference is invalid.','sabri-unified-notifications'),array('status'=>400));
		}
		$current=$this->get($user_id,$category,$channel);$version=absint($input['version']??$current['version']);
		if($version!==(int)$current['version']){return new WP_Error('sun_preference_conflict',__('Your notification settings changed in another session. Reload and try again.','sabri-unified-notifications'),array('status'=>409));}
		$essential=in_array($category,array('security','safety','system'),true);$enabled=!empty($input['enabled']);
		if($essential&&'in_app'===$channel){$enabled=true;}
		$digest=sanitize_key((string)($input['digest_frequency']??'immediate'));if(!in_array($digest,array('immediate','daily','weekly'),true)||$essential){$digest='immediate';}
		$timezone=$this->valid_timezone((string)($input['timezone']??$current['timezone']));$start=$this->valid_time((string)($input['quiet_start']??'22:00'));$end=$this->valid_time((string)($input['quiet_end']??'07:00'));
		$now=SUN_Database::now();$table=SUN_Database::table('preferences');
		$id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND category=%s AND channel=%s",$user_id,$category,$channel)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data=array('user_id'=>absint($user_id),'category'=>$category,'channel'=>$channel,'enabled'=>$enabled?1:0,'digest_frequency'=>$digest,'quiet_enabled'=>!empty($input['quiet_enabled'])&&!$essential?1:0,'quiet_start'=>$start,'quiet_end'=>$end,'timezone'=>$timezone,'consent_source'=>sanitize_key((string)($input['consent_source']??'settings')),'consent_at'=>$enabled?$now:null,'version'=>(int)$current['version']+1,'updated_at'=>$now);
		if($id){$updated=$wpdb->update($table,$data,array('id'=>$id,'version'=>$version));if(0===$updated){return new WP_Error('sun_preference_conflict',__('Your notification settings changed in another session. Reload and try again.','sabri-unified-notifications'),array('status'=>409));}}
		else{$data['created_at']=$now;if(false===$wpdb->insert($table,$data)){return new WP_Error('sun_preference_write_failed',__('The notification setting could not be saved.','sabri-unified-notifications'),array('status'=>500));}}
		SUN_Audit::record('preference_changed','preference',$user_id.':'.$category.':'.$channel,array('purpose'=>'user_choice'),$user_id);do_action('sun_notification_preference_changed',$user_id,$category,$channel,$data);return $this->get($user_id,$category,$channel);
	}

	/** @param int $user_id User ID. @param string $category Category. @param string $channel Channel. @param bool $mandatory Mandatory alert. @param DateTimeImmutable|null $now Current instant. @return DateTimeImmutable */
	public function next_delivery_time( $user_id, $category, $channel, $mandatory = false, $now = null ) {
		$pref=$this->get($user_id,$category,$channel);$tz=new DateTimeZone($this->valid_timezone((string)$pref['timezone']));$now=$now instanceof DateTimeImmutable?$now->setTimezone($tz):new DateTimeImmutable('now',$tz);
		if($mandatory||empty($pref['quiet_enabled'])){return $now->setTimezone(new DateTimeZone('UTC'));}
		$start=$this->time_on_date($now,(string)$pref['quiet_start']);$end=$this->time_on_date($now,(string)$pref['quiet_end']);if($start==$end){return $now->setTimezone(new DateTimeZone('UTC'));}
		if($end<$start){if($now>=$start){$end=$end->modify('+1 day');}elseif($now<$end){$start=$start->modify('-1 day');}}
		return($now>=$start&&$now<$end)?$end->setTimezone(new DateTimeZone('UTC')):$now->setTimezone(new DateTimeZone('UTC'));
	}

	/** @param int $user_id User ID. @param string $category Category. @param string $channel Channel. @param DateTimeImmutable $base Base UTC time. @return array{time:DateTimeImmutable,key:string|null} */
	public function digest_schedule( $user_id, $category, $channel, DateTimeImmutable $base ) {
		$pref=$this->get($user_id,$category,$channel);$freq=(string)$pref['digest_frequency'];$tz=new DateTimeZone($this->valid_timezone((string)$pref['timezone']));$local=$base->setTimezone($tz);
		if('daily'===$freq){$next=$local->setTime(8,0);if($next<=$local){$next=$next->modify('+1 day');}return array('time'=>$next->setTimezone(new DateTimeZone('UTC')),'key'=>'daily:'.$next->format('Y-m-d'));}
		if('weekly'===$freq){$next=$local->modify('next monday')->setTime(8,0);return array('time'=>$next->setTimezone(new DateTimeZone('UTC')),'key'=>'weekly:'.$next->format('o-W'));}
		return array('time'=>$base,'key'=>null);
	}

	/** @param int $user_id User ID. @param array<string,mixed> $input Device input. @return array<string,mixed>|WP_Error */
	public function register_device( $user_id, array $input ) {
		global $wpdb;
		$user_id=absint($user_id);$provider=sanitize_key((string)($input['provider']??'webpush'));$platform=sanitize_key((string)($input['platform']??'web'));$token=wp_json_encode($input['token']??array());
		if($user_id<1||strlen($token)<20||strlen($token)>8192){return new WP_Error('sun_device_token_invalid',__('The push device token is invalid.','sabri-unified-notifications'),array('status'=>400));}
		$cipher=SUN_Crypto::encrypt($token);if(is_wp_error($cipher)){return $cipher;}$hash=hash('sha256',$provider.':'.$token);$now=SUN_Database::now();$table=SUN_Database::table('devices');
		$existing=$wpdb->get_row($wpdb->prepare("SELECT id,public_id,user_id FROM {$table} WHERE provider=%s AND token_hash=%s LIMIT 1",$provider,$hash),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		if($existing&&(int)$existing['user_id']!==$user_id){return new WP_Error('sun_device_token_owned',__('This notification device is already bound to another account.','sabri-unified-notifications'),array('status'=>409));}
		if($existing){
			$updated=$wpdb->update($table,array('platform'=>$platform,'token_ciphertext'=>$cipher,'status'=>'active','last_seen_at'=>$now,'updated_at'=>$now),array('id'=>(int)$existing['id'],'user_id'=>$user_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if(false===$updated){return new WP_Error('sun_device_write_failed',__('The notification device could not be saved.','sabri-unified-notifications'),array('status'=>500));}$public_id=(string)$existing['public_id'];
		}else{
			$public_id=SUN_Database::uuid();$inserted=$wpdb->insert($table,array('public_id'=>$public_id,'user_id'=>$user_id,'provider'=>$provider,'platform'=>$platform,'token_hash'=>$hash,'token_ciphertext'=>$cipher,'status'=>'active','last_seen_at'=>$now,'created_at'=>$now,'updated_at'=>$now)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if(false===$inserted){return new WP_Error('sun_device_write_failed',__('The notification device could not be saved.','sabri-unified-notifications'),array('status'=>500));}
		}
		SUN_Audit::record('device_registered','device',$hash,array('purpose'=>'push_delivery','provider'=>$provider),$user_id);return array('id'=>$public_id,'provider'=>$provider,'platform'=>$platform,'status'=>'active');
	}

	/** @param int $user_id User ID. @param string $public_id Public ID. @return bool */
	public function revoke_device( $user_id, $public_id ) {
		global $wpdb;$updated=$wpdb->update(SUN_Database::table('devices'),array('status'=>'revoked','updated_at'=>SUN_Database::now()),array('user_id'=>absint($user_id),'public_id'=>sanitize_text_field($public_id)));
		if($updated){SUN_Audit::record('device_revoked','device',$public_id,array('purpose'=>'user_choice'),$user_id);return true;}
		$exists=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".SUN_Database::table('devices')." WHERE user_id=%d AND public_id=%s AND status='revoked'",absint($user_id),sanitize_text_field($public_id))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $exists>0;
	}

	/** @return string[] */ public function categories(){return array('security','safety','clinic','publishing','learning','social','marketplace','messages','media','system','marketing');}
	/** @return string[] */ public function channels(){return array('in_app','email','push','sms');}

	/** @param int $user_id User ID. @param string $category Category. @param string $channel Channel. @return array<string,mixed> */
	private function defaults( $user_id, $category, $channel ) {
		$claims=$this->auth->assertions($user_id);$essential=in_array($category,array('security','safety','system'),true);$enabled='in_app'===$channel||('email'===$channel&&$essential);
		if('email'===$channel&&empty($claims['email_verified'])){$enabled=false;}if('sms'===$channel&&empty($claims['phone_verified'])){$enabled=false;}$timezone=$claims['timezone']?:wp_timezone_string();
		return array('user_id'=>absint($user_id),'category'=>$category,'channel'=>$channel,'enabled'=>$enabled,'essential'=>$essential,'digest_frequency'=>'immediate','quiet_enabled'=>false,'quiet_start'=>'22:00:00','quiet_end'=>'07:00:00','timezone'=>$this->valid_timezone($timezone),'version'=>0);
	}
	/** @param string $timezone Timezone. @return string */ private function valid_timezone($timezone){try{new DateTimeZone($timezone);return $timezone;}catch(Exception $e){return 'UTC';}}
	/** @param string $time Time. @return string */ private function valid_time($time){return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',$time)?(strlen($time)===5?$time.':00':$time):'22:00:00';}
	/** @param DateTimeImmutable $date Date. @param string $time Time. @return DateTimeImmutable */ private function time_on_date(DateTimeImmutable $date,$time){$parts=array_map('intval',explode(':',$this->valid_time($time)));return $date->setTime($parts[0],$parts[1],$parts[2]??0);}
}
