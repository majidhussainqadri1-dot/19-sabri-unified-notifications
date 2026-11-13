<?php
/**
 * File 19 3.0.6 functional privacy and provider-receipt regression checks.
 * Synthetic fixtures only; no live site, contacts or provider traffic.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );

class WP_Error {
    private $code;
    private $message;
    public function __construct( $code = '', $message = '', $data = null ) { $this->code=$code; $this->message=$message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $value, $domain = null ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function get_user_by( $field, $value ) { return 'email' === $field && 'test@example.invalid' === $value ? (object) array( 'ID' => 5 ) : false; }
function apply_filters( $name, $value, ...$args ) { return $value; }
function do_action( $name, ...$args ) { $GLOBALS['sun_test_actions'][]=$name; }

class SUN_Database {
    public static function table( $name ) { return 'sun_' . $name; }
    public static function now() { return '2026-11-13 00:00:00'; }
    public static function canonical_json( $value ) { return json_encode( $value ); }
}
class SUN_Audit {
    public static function record( $type, $target_type, $target_id, $details, $actor = 0 ) { $GLOBALS['sun_test_audit'][]=$type; }
}
class SUN_Trace_Service {
    public static function record( ...$args ) { $GLOBALS['sun_test_trace'][]=$args[1] ?? ''; }
}
class SUN_Platform_Events {
    public static function publish( ...$args ) { return true; }
}
class SUN_Crypto {
    public static function decrypt( $cipher ) { return $cipher; }
    public static function encrypt( $plain ) { return 'encrypted:' . $plain; }
}
class SUN_Provider_Webhook_Verifier {
    /** Simulate already authenticated provider-specific webhook evidence. */
    public static function verify( $channel, $payload, $request ) { return true; }
}
class SUN_Template_Engine {}
class SUN_Auth {}
interface SUN_Delivery_Adapter {
    public function channel();
    public function send( array $delivery, array $notification );
    public function health();
}
class SUN_Test_Adapter implements SUN_Delivery_Adapter {
    public function channel() { return 'sms'; }
    public function send( array $delivery, array $notification ) { return array( 'status' => 'accepted' ); }
    public function health() { return array(); }
}
class SUN_Test_Request {
    private $provider;
    public function __construct( $provider ) { $this->provider=$provider; }
    public function get_header( $header ) { return 'x-sun-provider' === strtolower( $header ) ? $this->provider : ''; }
}

class SUN_Test_DB {
    public $notifications = array();
    public $deliveries = array();
    public $last_error = '';
    public $args = array();
    public $sql = '';
    public $fail_read = false;
    public $fail_legacy = false;
    public $fail_write = false;
    public $writes = 0;
    public function prepare( $sql, ...$args ) { $this->sql=$sql; $this->args=$args; return $sql; }
    public function get_results( $sql, $format = null ) {
        $this->last_error='';
        if( $this->fail_read ) { $this->last_error='synthetic read error'; return null; }
        if( false !== strpos( $sql, 'FROM sun_events' ) ) {
            if( $this->fail_legacy ) { $this->last_error='synthetic legacy error'; return null; }
            return array();
        }
        if( false !== strpos( $sql, 'FROM sun_deliveries' ) && false !== strpos( $sql, 'provider_message_id=%s' ) ) {
            $args=$this->args;
            $rows=array_values( array_filter( $this->deliveries, static function($row) use($args) {
                return $row['provider_message_id']===$args[0] && $row['channel']===$args[1]
                    && ($row['provider']===$args[2] || $row['route_provider']===$args[3]);
            } ) );
            return array_slice( $rows, 0, 2 );
        }
        if( false !== strpos( $sql, 'FROM sun_notifications' ) && false !== strpos( $sql, 'recipient_id=%d' ) ) {
            return array_slice( $this->notifications, (int) $this->args[2], (int) $this->args[1] );
        }
        return array();
    }
    public function get_row( $sql, $format = null ) { $this->last_error=''; return null; }
    public function get_var( $sql ) { return 'trace-synthetic'; }
    public function query( $sql ) { ++$this->writes; return $this->fail_write ? false : 1; }
    public function delete( $table, $where, $format = null ) { ++$this->writes; return $this->fail_write ? false : 0; }
    public function update( $table, $data, $where ) {
        ++$this->writes;
        if( $this->fail_write ) { return false; }
        if( 'sun_deliveries' === $table ) {
            foreach( $this->deliveries as &$row ) {
                if( (int)$row['id']===(int)$where['id'] ) { $row=array_merge($row,$data); break; }
            }
            unset( $row );
        }
        return 1;
    }
}

require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-privacy.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-delivery-service.php';

$checks=0;$failures=array();
function t( $condition, $message ) { global $checks,$failures; ++$checks; if(!$condition){$failures[]=$message;} }
function error_code( $result ) { return is_wp_error($result) ? $result->get_error_code() : ''; }
$GLOBALS['sun_test_actions']=array();
$GLOBALS['sun_test_audit']=array();
$GLOBALS['sun_test_trace']=array();
$GLOBALS['wpdb']=new SUN_Test_DB();
$wpdb=$GLOBALS['wpdb'];

for( $i=1; $i<=215; ++$i ){
    $wpdb->notifications[]=array(
        'public_id'=>(string)$i,'category'=>'system','priority'=>'normal','title'=>'Synthetic notification',
        'summary'=>'No personal data','status'=>'unread','created_at'=>'2026-11-13 00:00:00',
        'read_at'=>null,'archived_at'=>null
    );
}
$privacy=new SUN_Privacy();
$p1=$privacy->export('test@example.invalid',1);
$p2=$privacy->export('test@example.invalid',2);
$p3=$privacy->export('test@example.invalid',3);
t(!is_wp_error($p1)&&100===count($p1['data'])&&!$p1['done'],'privacy page 1 contains first 100 rows');
t(!is_wp_error($p2)&&100===count($p2['data'])&&!$p2['done'],'privacy page 2 contains next 100 rows');
t(!is_wp_error($p3)&&15===count($p3['data'])&&$p3['done'],'privacy page 3 finishes after all 215 rows');
t('notification-101'===$p2['data'][0]['item_id'],'privacy pagination does not repeat earlier IDs');
$wpdb->fail_read=true;
t('sun_privacy_export_query_failed'===error_code($privacy->export('test@example.invalid',1)),'failed export SQL never claims success');
$wpdb->fail_read=false;
$wpdb->fail_write=true;
$failed=$privacy->erase('test@example.invalid',1);
t('sun_privacy_erasure_db_failed'===error_code($failed),'failed privacy SQL stops WordPress from completing erasure');
t(in_array('privacy_erasure_failed',$GLOBALS['sun_test_audit'],true),'privacy erasure failure is audited');
$wpdb->fail_write=false;
$wpdb->fail_legacy=true;
t('sun_privacy_legacy_scrub_failed'===error_code($privacy->erase('test@example.invalid',1)),'failed legacy scrub blocks completion');
$wpdb->fail_legacy=false;
$ok=$privacy->erase('test@example.invalid',1);
t(is_array($ok)&&true===$ok['done'],'successful local erasure reaches completed page');

$service=(new ReflectionClass('SUN_Delivery_Service'))->newInstanceWithoutConstructor();
$service->register_adapter(new SUN_Test_Adapter());
function provider_row($id,$provider,$recipient=5,$digest=null){
    return array('id'=>$id,'public_id'=>'delivery-'.$id,'notification_id'=>$id,'recipient_id'=>$recipient,
        'status'=>'accepted','channel'=>'sms','digest_key'=>$digest,'attempt_count'=>1,
        'provider_message_id'=>'same-msg','provider'=>$provider,'route_provider'=>$provider);
}
$wpdb->deliveries=array(provider_row(1,'alpha'),provider_row(2,'beta'));
$res=$service->provider_webhook('sms',array('provider_message_id'=>'same-msg','status'=>'delivered'),new SUN_Test_Request('alpha'));
t(true===$res,'verified alpha provider receipt is accepted');
t('delivered'===$wpdb->deliveries[0]['status']&&'accepted'===$wpdb->deliveries[1]['status'],'beta message cannot be updated by alpha receipt');
$res=$service->provider_webhook('sms',array('provider_message_id'=>'same-msg','status'=>'delivered','provider'=>'beta'),new SUN_Test_Request('alpha'));
t('sun_webhook_provider_mismatch'===error_code($res),'payload cannot override verified provider');
$res=$service->provider_webhook('sms',array('provider_message_id'=>'same-msg','status'=>'delivered'),new SUN_Test_Request(''));
t('sun_webhook_provider_identity_required'===error_code($res),'provider-less receipt is rejected');
$wpdb->deliveries=array(provider_row(3,'alpha',5),provider_row(4,'alpha',6));
$res=$service->provider_webhook('sms',array('provider_message_id'=>'same-msg','status'=>'delivered'),new SUN_Test_Request('alpha'));
t('sun_webhook_delivery_ambiguous'===error_code($res),'ambiguous provider receipt cannot update another recipient');
$wpdb->deliveries=array(provider_row(5,'alpha',5,'digest-a'),provider_row(6,'alpha',5,'digest-a'));
$res=$service->provider_webhook('sms',array('provider_message_id'=>'same-msg','status'=>'delivered'),new SUN_Test_Request('alpha'));
t(true===$res,'same-provider same-recipient digest receipt is allowed');

if($failures){fwrite(STDERR,"FAIL ".count($failures)."/".$checks." regression checks:\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: $checks File 19 privacy and signed-provider receipt regression checks\n";
