<?php
require __DIR__.'/bootstrap.php';
$tests=0;$failures=array();
function check($condition,$label){global $tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}

check(SUN_Database::canonical_json(array('b'=>2,'a'=>1))==='{"a":1,"b":2}','canonical JSON sorting');
check((bool)preg_match('/^[a-f0-9\-]{36}$/',SUN_Database::uuid()),'UUID format');
$cipher=SUN_Crypto::encrypt('private payload');check(!is_wp_error($cipher),'encryption available');check(SUN_Crypto::decrypt($cipher)==='private payload','encryption round trip');
$token=SUN_Crypto::sign_token(array('purpose'=>'unsubscribe','user_id'=>7),300);$claims=SUN_Crypto::verify_token($token,'unsubscribe');check(!is_wp_error($claims)&&7===$claims['user_id'],'signed token verification');check(is_wp_error(SUN_Crypto::verify_token($token,'other')),'token purpose binding');

check(SUN_Deep_Link::sanitize('/notifications/')==='https://sabrihomeopathy.com/notifications/','relative deep-link normalization');
check(SUN_Deep_Link::sanitize('https://evil.example/path')==='','cross-origin deep-link rejection');
check(SUN_Deep_Link::sanitize('javascript:alert(1)')==='','unsafe scheme rejection');
$note=array('producer'=>'file17','event_type'=>'Communication.MessageReceived');
check(SUN_Deep_Link::authorize_for_notification('https://sabrihomeopathy.com/notifications/',$note,7)!=='','internal notification deep link allowed');
check(SUN_Deep_Link::authorize_for_notification('https://sabrihomeopathy.com/messages/abc',$note,7)==='','domain deep link fails closed without owner authorization');
add_filter('sun_authorize_notification_deep_link',static function($allowed,$url){return str_contains($url,'/messages/');});
check(SUN_Deep_Link::authorize_for_notification('https://sabrihomeopathy.com/messages/abc',$note,7)!=='','domain deep link owner authorization hook');

$registry=new SUN_Producer_Registry();
check(true===$registry->authorize_type('sabri-system','System.HealthChanged'),'default system producer authorization');
check(is_wp_error($registry->authorize_type('sabri-system','Security.PasswordChanged')),'File 19 cannot impersonate File 00 security truth');
check(is_wp_error($registry->authorize_type('sabri-system','Marketplace.DealChanged')),'producer type denial');
SUN_Producer_Registry::register_runtime('file17',array('owner'=>'File 17','event_types'=>array('Communication.*')));
check(true===$registry->authorize_type('file17','Communication.MessageReceived'),'runtime producer');

$validator=new SUN_Event_Validator($registry);
$event=array('producer'=>'file17','owner'=>'File 17','event_id'=>'msg:1','event_type'=>'Communication.MessageReceived','schema_version'=>'1.0','occurred_at'=>gmdate(DATE_ATOM),'recipients'=>array(array('user_id'=>5),array('user_id'=>5)),'data'=>array('summary'=>'Private message'));
$valid=$validator->validate($event);check(!is_wp_error($valid)&&1===count($valid['recipients']),'event validation and recipient dedupe');
$spoof=$event;$spoof['owner']='File 19';check(is_wp_error($validator->validate($spoof))&&'sun_event_owner_mismatch'===$validator->validate($spoof)->get_error_code(),'canonical owner spoof rejection');
$broad=$event;$broad['recipients']=array(array('role'=>'subscriber'));check(is_wp_error($validator->validate($broad)),'broad role recipient rejection');
$badtype=$event;$badtype['recipients']=array(5);$badtype['event_type']='bad type';check(is_wp_error($validator->validate($badtype)),'invalid event type rejection');
$secret=$event;$secret['event_id']='msg:secret';$secret['data']=array('api_key'=>'must-never-travel');$secret_result=$validator->validate($secret);check(is_wp_error($secret_result)&&'sun_event_secret_field_rejected'===$secret_result->get_error_code(),'secret-like payload field rejection');
$scope=$event;$scope['event_id']='msg:scope';$scope['subscription_scope']=array('type'=>'community','id'=>'community-7');$scope_result=$validator->validate($scope);check(!is_wp_error($scope_result)&&'community'===$scope_result['subscription_scope']['type'],'granular subscription scope validation');

$engine=new SUN_Template_Engine();check(true===$engine->validate_template('{{action_name}}','{{summary}}',array('action_name','summary')),'safe template validation');check(is_wp_error($engine->validate_template('<script>alert(1)</script>','x',array())),'script template rejection');check(is_wp_error($engine->validate_template('{{secret}}','x',array('summary'))),'unknown variable rejection');
$render=$engine->render(array('title_template'=>'{{action_name}}','body_template'=>'{{summary}}','allowed_variables'=>'["action_name","summary"]'),array('action_name'=>'Ready','summary'=>'Done'),'email','sensitive');check($render['title']==='You have a new private update','sensitive external redaction');

$manifest=SUN_Four_Plan_Compliance::manifest();
check('sun.four-plan-compliance.v1'===$manifest['contract'],'four-plan executable contract');
check(4===count($manifest['plans']),'four governing plans represented');
check(10===count($manifest['top20_requirements']),'Top-20 CV-097 through CV-106 represented');
check(!empty($manifest['constitutional_invariants']['no_donor_advantage']),'no donor advantage invariant');
check(!empty($manifest['constitutional_invariants']['green_primary_brand']),'green primary brand invariant');
$security=SUN_Four_Plan_Compliance::profile_for('Security.PasswordChanged');
check('critical'===$security['priority']&&!empty($security['mandatory'])&&'sensitive'===$security['sensitivity'],'security alert minimum profile');
$clinic=SUN_Four_Plan_Compliance::profile_for('Clinic.AppointmentRescheduled');
check('clinic'===$clinic['category']&&'high'===$clinic['priority'],'appointment alert minimum profile');
$bulletin=SUN_Four_Plan_Compliance::profile_for('Social.CreatorBulletinPublished');
check(!empty($bulletin['requires_opt_in'])&&1===$bulletin['max_per_24h'],'creator bulletin opt-in and cap profile');
check('critical'===SUN_Four_Plan_Compliance::strongest_priority('normal','critical'),'priority cannot be downgraded');
check('restricted'===SUN_Four_Plan_Compliance::strongest_sensitivity('restricted','standard'),'sensitivity cannot be downgraded');

$subscriptions=new SUN_Subscriptions();
check(SUN_Subscriptions::valid_event_pattern('*'),'global subscription wildcard grammar');
check(SUN_Subscriptions::valid_event_pattern('Publishing.CorrectionIssued'),'exact subscription event grammar');
check(SUN_Subscriptions::valid_event_pattern('Publishing.*'),'namespace subscription wildcard grammar');
check(!SUN_Subscriptions::valid_event_pattern('Publishing.Correction*'),'partial-segment wildcard rejected');
$cap=$subscriptions->bulletin_cap_check(7,array('type'=>'person','id'=>'doctor-8'),1);check(!empty($cap['allowed']),'creator bulletin first delivery under cap');
$subscriptions->mark_bulletin_sent($cap['key']);$capped=$subscriptions->bulletin_cap_check(7,array('type'=>'person','id'=>'doctor-8'),1);check(empty($capped['allowed']),'creator bulletin second delivery capped for 24h');
$daily=$subscriptions->schedule('daily',new DateTimeImmutable('2026-08-07 10:00:00',new DateTimeZone('UTC')),'Asia/Karachi','abc123');check(str_starts_with((string)$daily['key'],'scope-daily:'),'scope-specific daily digest schedule');

if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}echo "PASS: $tests deterministic unit assertions\n";
