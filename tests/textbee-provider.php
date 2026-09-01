<?php
/** Deterministic TextBee provider bridge assertions. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'SUN_TEXTBEE_API_KEY', 'test-api-key-not-a-real-secret' );
define( 'SUN_TEXTBEE_DEVICE_ID', 'device-test-123' );
define( 'SUN_TEXTBEE_SIM_SUBSCRIPTION_ID', 2 );

$GLOBALS['sun_textbee_filters'] = array();
$GLOBALS['sun_textbee_http_fixture'] = array(
	'code' => 200,
	'body' => '{"data":{"success":true,"smsBatchId":"batch-123","recipientCount":1}}',
);
$GLOBALS['sun_textbee_last_request'] = null;

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $value, $domain = null ) { unset( $domain ); return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { $GLOBALS['sun_textbee_filters'][ $tag ][] = array( $callback, $priority, $accepted_args ); return true; }
function wp_remote_post( $url, $args ) { $GLOBALS['sun_textbee_last_request'] = array( 'url' => $url, 'args' => $args ); return $GLOBALS['sun_textbee_http_fixture']; }
function wp_remote_retrieve_response_code( $response ) { return (int) ( $response['code'] ?? 0 ); }
function wp_remote_retrieve_body( $response ) { return (string) ( $response['body'] ?? '' ); }

require_once dirname( __DIR__ ) . '/19-unified-notifications/includes/providers/class-sun-textbee-provider.php';

$tests = 0;
$failures = array();
function check_textbee( $condition, $label ) { global $tests, $failures; ++$tests; if ( ! $condition ) { $failures[] = $label; } }

SUN_TextBee_Provider::register();
check_textbee( true === SUN_TextBee_Provider::configured( false ), 'configured when wp-config API key exists' );
check_textbee( 'textbee' === SUN_TextBee_Provider::provider_name( 'not-configured' ), 'provider name advertised' );
check_textbee( isset( $GLOBALS['sun_textbee_filters']['sun_send_sms'] ), 'send filter registered' );

$result = SUN_TextBee_Provider::send( null, '+923001234567', 'Sabri verification code: 123456', array(), array() );
check_textbee( is_array( $result ) && ! empty( $result['accepted'] ), 'accepted TextBee response mapped' );
check_textbee( 'textbee' === $result['provider'], 'provider identity mapped' );
check_textbee( 'batch-123' === $result['provider_message_id'], 'provider batch receipt preserved' );
$request = $GLOBALS['sun_textbee_last_request'];
check_textbee( 'https://api.textbee.dev/api/v1/gateway/send-sms' === $request['url'], 'current account-level endpoint used' );
check_textbee( 'test-api-key-not-a-real-secret' === $request['args']['headers']['x-api-key'], 'API key sent only as header' );
$payload = json_decode( $request['args']['body'], true );
check_textbee( array( '+923001234567' ) === $payload['recipients'], 'E.164 recipient payload' );
check_textbee( 'device-test-123' === $payload['deviceId'], 'optional device pinning' );
check_textbee( 2 === $payload['simSubscriptionId'], 'optional SIM pinning' );

$GLOBALS['sun_textbee_http_fixture'] = array( 'code' => 200, 'body' => '{"data":{"successCount":1,"failureCount":0}}' );
$immediate = SUN_TextBee_Provider::send( null, '+923001234567', 'Immediate', array(), array() );
check_textbee( is_array( $immediate ) && ! empty( $immediate['provider_message_id'] ), 'immediate acceptance gets nonempty local receipt' );
check_textbee( 0 === strpos( $immediate['provider_message_id'], 'textbee-accepted-' ), 'local receipt is truthfully prefixed' );

$GLOBALS['sun_textbee_http_fixture'] = array( 'code' => 401, 'body' => '{"message":"unauthorized"}' );
$unauthorized = SUN_TextBee_Provider::send( null, '+923001234567', 'Denied', array(), array() );
check_textbee( is_wp_error( $unauthorized ) && 'sun_textbee_http_error' === $unauthorized->get_error_code(), 'HTTP rejection fails closed' );
check_textbee( false === strpos( $unauthorized->get_error_message(), SUN_TEXTBEE_API_KEY ), 'secret never appears in error message' );

$GLOBALS['sun_textbee_http_fixture'] = new WP_Error( 'http_request_failed', 'network detail containing no credential' );
$transport = SUN_TextBee_Provider::send( null, '+923001234567', 'Transport', array(), array() );
check_textbee( is_wp_error( $transport ) && 'sun_textbee_transport_error' === $transport->get_error_code(), 'transport failure normalized' );

$invalid_phone = SUN_TextBee_Provider::send( null, '03001234567', 'Bad phone', array(), array() );
check_textbee( is_wp_error( $invalid_phone ) && 'sun_textbee_phone_invalid' === $invalid_phone->get_error_code(), 'non-E.164 phone rejected' );

$prior = array( 'accepted' => true, 'provider' => 'other', 'provider_message_id' => 'other-1' );
check_textbee( $prior === SUN_TextBee_Provider::send( $prior, '+923001234567', 'Prior', array(), array() ), 'existing provider result preserved' );

if ( $failures ) {
	fwrite( STDERR, "FAIL (" . count( $failures ) . "/$tests):\n - " . implode( "\n - ", $failures ) . "\n" );
	exit( 1 );
}
echo "PASS: $tests deterministic TextBee provider assertions\n";
