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
$registry=new SUN_Producer_Registry();check(true===$registry->authorize_type('sabri-system','System.HealthChanged'),'default producer authorization');check(is_wp_error($registry->authorize_type('sabri-system','Marketplace.DealChanged')),'producer type denial');
SUN_Producer_Registry::register_runtime('file17',array('owner'=>'File 17','event_types'=>array('Communication.*')));check(true===$registry->authorize_type('file17','Communication.MessageReceived'),'runtime producer');
$validator=new SUN_Event_Validator($registry);$event=array('producer'=>'file17','owner'=>'File 17','event_id'=>'msg:1','event_type'=>'Communication.MessageReceived','schema_version'=>'1.0','occurred_at'=>gmdate(DATE_ATOM),'recipients'=>array(array('user_id'=>5),array('user_id'=>5)),'data'=>array('summary'=>'Private message'));$valid=$validator->validate($event);check(!is_wp_error($valid)&&1===count($valid['recipients']),'event validation and recipient dedupe');
$event['recipients']=array(array('role'=>'subscriber'));check(is_wp_error($validator->validate($event)),'broad role recipient rejection');
$event['recipients']=array(5);$event['event_type']='bad type';check(is_wp_error($validator->validate($event)),'invalid event type rejection');
$engine=new SUN_Template_Engine();check(true===$engine->validate_template('{{action_name}}','{{summary}}',array('action_name','summary')),'safe template validation');check(is_wp_error($engine->validate_template('<script>alert(1)</script>','x',array())),'script template rejection');check(is_wp_error($engine->validate_template('{{secret}}','x',array('summary'))),'unknown variable rejection');
$render=$engine->render(array('title_template'=>'{{action_name}}','body_template'=>'{{summary}}','allowed_variables'=>'["action_name","summary"]'),array('action_name'=>'Ready','summary'=>'Done'),'email','sensitive');check($render['title']==='You have a new private update','sensitive external redaction');
if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}echo "PASS: $tests deterministic unit assertions\n";
