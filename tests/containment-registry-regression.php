<?php
/**
 * Versioned File 19 gate and File 01 registry ready-state regression.
 * Run against synthetic owner contracts; no Live configuration or data.
 */
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/19-unified-notifications/includes/class-sun-cross-file-contracts.php';

class SPF_Registry {
    public static $module;
    public static $routes;
    public static function get_module( $key ) { return 'file-19' === $key ? self::$module : null; }
    public static function list_routes() { return self::$routes; }
}

$tests=0;$failures=array();
function check_gate( $ok, $message ) { global $tests,$failures; ++$tests; if(!$ok){$failures[]=$message;} }

check_gate( false === SUN_Operational_Gate::allows('external_delivery'), 'required File20/File24 absence fails closed' );
add_filter( 'sun_operational_action_allowed', static function( $allowed, $action, $state ) { return true; } );
check_gate( false === SUN_Operational_Gate::allows('external_delivery'), 'a filter cannot override absent File20/File24 containment' );
check_gate( false === SUN_Operational_Gate::allows('bulk_process'), 'a filter cannot override missing bulk containment owners' );
add_filter( 'sun_file20_safe_mode_active', static function( $state ) { return false; } );
add_filter( 'sun_file24_notification_containment_active', static function( $state ) { return false; } );
check_gate( true === SUN_Operational_Gate::allows('external_delivery'), 'trusted healthy owner contracts permit external delivery' );
$GLOBALS['sun_test_options']['sun_notification_safe_mode']=1;
check_gate( false === SUN_Operational_Gate::allows('external_delivery'), 'a filter cannot override local emergency containment' );
unset( $GLOBALS['sun_test_options']['sun_notification_safe_mode'] );

SPF_Registry::$module=SUN_Cross_File_Contracts::manifest();
SPF_Registry::$routes=SUN_Cross_File_Contracts::routes();
check_gate( true === SUN_Cross_File_Contracts::file01_registry_ready(), 'current File01 manifest and routes are ready' );
SPF_Registry::$module['software_version']='0.0.0';
check_gate( false === SUN_Cross_File_Contracts::file01_registry_ready(), 'stale registered module software version is not ready' );
SPF_Registry::$module=SUN_Cross_File_Contracts::manifest();
SPF_Registry::$module['commands']=array('obsolete-command');
check_gate( false === SUN_Cross_File_Contracts::file01_registry_ready(), 'stale registered commands must not be ready' );
SPF_Registry::$module=SUN_Cross_File_Contracts::manifest();
SPF_Registry::$routes[0]['route_path']='/unrelated/';
check_gate( false === SUN_Cross_File_Contracts::file01_registry_ready(), 'stale File01 route destination path is not ready' );
SPF_Registry::$routes=SUN_Cross_File_Contracts::routes();
SPF_Registry::$routes[0]['destination']='https://other.example.invalid/notifications/';
check_gate( false === SUN_Cross_File_Contracts::file01_registry_ready(), 'untrusted File01 route destination is not ready' );
SPF_Registry::$routes=SUN_Cross_File_Contracts::routes();
check_gate( true === SUN_Cross_File_Contracts::file01_registry_ready(), 'healthy manifest and exact registered routes regain ready state' );

if($failures){fwrite(STDERR,"FAIL (".count($failures)."/".$tests."):\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: ".$tests." fail-closed containment and File01 exact-registry readiness checks\n";
