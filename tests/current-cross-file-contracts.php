<?php
/** Current companion-contract regression assertions for File 19. */
$root=dirname(__DIR__);$plugin=$root.'/19-unified-notifications';$tests=0;$failures=array();
function cf_check($ok,$label){global$tests,$failures;++$tests;if(!$ok){$failures[]=$label;}}
function cf_src($path){global$plugin;return file_get_contents($plugin.'/'.$path);}

$auth=cf_src('includes/class-sun-auth.php');
$cross=cf_src('includes/class-sun-cross-file-contracts.php');
$gate=cf_src('includes/class-sun-operational-gate.php');
$health=cf_src('includes/class-sun-health.php');
$admin=cf_src('includes/class-sun-admin.php');
$template=cf_src('templates/admin.php');
$legacy=cf_src('includes/class-sun-legacy-migration.php');
$compliance=cf_src('includes/class-sun-four-plan-compliance.php');

cf_check(strpos($auth,'SMC_Contracts::assertions')!==false && strpos($auth,'smc_assertions_v1')!==false,'File 00 current assertion contract');
cf_check(strpos($auth,'SA_Authentication_Assurance::assertion')!==false && strpos($auth,'notification_governance')!==false,'File 02 purpose/scope-bound assurance');
cf_check(strpos($cross,"'required' => array( 'file-00', 'file-20' )")!==false,'File 01 required dependency manifest');
cf_check(strpos($cross,'spcrc/file19_contract_state')!==false,'File 24 assurance matrix publisher');
cf_check(strpos($cross,'sun_validate_saved_search_ownership')!==false && strpos($cross,'get_user_meta($user_id,self::FILE26_SAVED_META')===false,'File 26 owner API without private-meta bypass');
cf_check(strpos($gate,'Sabri\\UnifiedShell\\SafeMode')!==false && strpos($gate,'::disabled()')!==false,'File 20 canonical Safe Mode');
cf_check(strpos($health,"'file20_single_bell'")!==false && strpos($health,"'file24_assurance_contract'")!==false,'health exposes real cross-file readiness');
cf_check(strpos($admin,"'reason'=>")!==false && strpos($admin,"'compensation_plan'=>")!==false && strpos($template,'name="reason"')!==false && strpos($template,'name="compensation_plan"')!==false,'bulk governance evidence reaches backend');
cf_check(strpos($admin,'legacy_migration_dry_run')!==false && strpos($admin,'legacy_migration_execute')!==false && strpos($admin,'legacy_migration_rollback')!==false,'governed legacy migration operator path');
cf_check(strpos($legacy,"'status'=>$applicable?")!==false && strpos($legacy,"'not_applicable'")!==false,'legacy migration not-applicable state');
cf_check(strpos($compliance,"'Security.NewDeviceDetected'=>array('owner'=>2")!==false && strpos($compliance,"'Security.PasswordChanged'=>array('owner'=>2")!==false,'authentication event ownership aligned to File 02');

if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: $tests current cross-file contract assertions\n";
