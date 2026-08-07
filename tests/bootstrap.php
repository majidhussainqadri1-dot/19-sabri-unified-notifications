<?php
/** Minimal WordPress-compatible stubs for deterministic File 19 unit tests. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'AUTH_KEY', str_repeat( 'a', 64 ) );
define( 'SECURE_AUTH_KEY', str_repeat( 'b', 64 ) );
define( 'DAY_IN_SECONDS', 86400 );
define( 'YEAR_IN_SECONDS', 31536000 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['sun_test_filters'] = array();
$GLOBALS['sun_test_transients'] = array();
class WP_Error {
	private $code; private $message; private $data;
	public function __construct( $code='', $message='', $data=null ){ $this->code=$code; $this->message=$message; $this->data=$data; }
	public function get_error_code(){ return $this->code; }
	public function get_error_message(){ return $this->message; }
	public function get_error_data(){ return $this->data; }
}
function is_wp_error($v){return $v instanceof WP_Error;}
function __($s,$d=null){return $s;}
function sanitize_key($s){return strtolower(preg_replace('/[^a-z0-9_\-]/','',(string)$s));}
function sanitize_text_field($s){return trim(strip_tags((string)$s));}
function sanitize_textarea_field($s){return trim(strip_tags((string)$s));}
function sanitize_locale_name($s){return preg_replace('/[^A-Za-z0-9_\-]/','',(string)$s);}
function esc_url_raw($s){return filter_var((string)$s,FILTER_SANITIZE_URL);}
function absint($v){return abs((int)$v);}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function wp_strip_all_tags($s){return strip_tags((string)$s);}
function get_bloginfo($k=''){return 'Sabri Test';}
function home_url($path='/'){return 'https://sabrihomeopathy.com'.('/'===substr($path,0,1)?$path:'/'.$path);}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function wp_salt($scheme='auth'){return hash('sha256','test-'.$scheme);}
function apply_filters($tag,$value,...$args){foreach($GLOBALS['sun_test_filters'][$tag]??array() as $cb){$value=$cb($value,...$args);}return $value;}
function add_filter($tag,$cb){$GLOBALS['sun_test_filters'][$tag][]=$cb;}
function current_time($type,$gmt=false){return gmdate('Y-m-d H:i:s');}
function wp_generate_uuid4(){return '123e4567-e89b-42d3-a456-426614174000';}
function wp_timezone_string(){return 'UTC';}
function untrailingslashit($s){return rtrim((string)$s,"/\\");}
function get_transient($key){return $GLOBALS['sun_test_transients'][$key]??false;}
function set_transient($key,$value,$expiration){$GLOBALS['sun_test_transients'][$key]=$value;return true;}
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-database.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-crypto.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-deep-link.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-producer-registry.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-four-plan-compliance.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-subscriptions.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-event-validator.php';
require_once dirname(__DIR__).'/19-unified-notifications/includes/class-sun-template-engine.php';
