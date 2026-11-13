<?php
/** Synthetic provider acceptance regressions, no external SMS calls. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SUN_TEXTBEE_API_KEY', 'local-fixture' );
class WP_Error {
    private $code;
    public function __construct( $code = '', $message = '', $data = null ) { $this->code=$code; }
    public function get_error_code() { return $this->code; }
}
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function __( $value, $domain = null ) { return $value; }
function sanitize_text_field( $value ) { return trim(strip_tags((string)$value)); }
function wp_json_encode( $value ) { return json_encode($value); }
function wp_remote_post( $url, $args ) { return $GLOBALS['response_fixture']; }
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function add_filter( ...$args ) { return true; }

require_once dirname(__DIR__).'/19-unified-notifications/includes/providers/class-sun-textbee-provider.php';
$tests=0;$failures=array();
function check_result($condition,$label){global $tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}
function fixture($data){$GLOBALS['response_fixture']=array('status'=>200,'body'=>json_encode(array('data'=>$data)));}
function send_fixture(){return SUN_TextBee_Provider::send(null,'+' . '1' . str_repeat('2',10),'Non-sensitive test',array(),array());}
fixture(array('success'=>false,'smsBatchId'=>'failed-batch','failureCount'=>1));
$r=send_fixture();check_result(is_wp_error($r)&&'sun_textbee_rejected'===$r->get_error_code(),'failed batch with receipt rejected');
fixture(array('smsBatchId'=>'receipt-only','successCount'=>0,'failureCount'=>0));
$r=send_fixture();check_result(is_wp_error($r)&&'sun_textbee_rejected'===$r->get_error_code(),'receipt ID is not acceptance');
fixture(array('successCount'=>1,'failureCount'=>1));
$r=send_fixture();check_result(is_wp_error($r)&&'sun_textbee_rejected'===$r->get_error_code(),'partial failure for one recipient rejected');
fixture(array('success'=>false,'successCount'=>1,'failureCount'=>0));
$r=send_fixture();check_result(is_wp_error($r)&&'sun_textbee_rejected'===$r->get_error_code(),'explicit false cannot be overridden by count');
fixture(array('success'=>true,'smsBatchId'=>'good-batch','failureCount'=>0));
$r=send_fixture();check_result(is_array($r)&&true===$r['accepted']&&'good-batch'===$r['provider_message_id'],'verified queued acceptance preserved');
fixture(array('successCount'=>1,'failureCount'=>0));
$r=send_fixture();check_result(is_array($r)&&true===$r['accepted'],'immediate accepted reply preserved');
if($failures){fwrite(STDERR,"FAIL ".count($failures)."/".$tests.": ".implode("; ",$failures)."\n");exit(1);}
echo "PASS: $tests independent TextBee receipt and failure regressions\n";
