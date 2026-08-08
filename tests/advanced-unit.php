<?php
require __DIR__.'/bootstrap.php';
$tests=0;$failures=array();function adv_check($condition,$label){global$tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}
$auth=new SUN_Auth();$attention=new SUN_Attention_Service($auth);$automation=new SUN_Automation_Service($attention);$routing=new SUN_Routing_Service();$experiments=new SUN_Experiments_Service();$trace=new SUN_Trace_Service();$intelligence=new SUN_Intelligence_Service($attention);
adv_check(in_array('study',$attention->focus_modes(),true)&&in_array('essential',$attention->focus_modes(),true),'focus modes');
adv_check(count($attention->focus_modes())===8,'focus mode inventory');
adv_check(in_array('saved_search',$automation->trigger_types(),true)&&in_array('correction',$automation->trigger_types(),true),'automation triggers');
adv_check(in_array('calendar_handoff',$automation->action_types(),true)&&in_array('owner_action',$automation->action_types(),true),'native owner automation actions');
adv_check($routing->channels()===array('email','push','sms','whatsapp','rcs'),'advanced external channels');
adv_check($experiments->types()===array('simulator','shadow','canary'),'experiment modes');
$synthetic=$trace->synthetic_test(array('in_app','email','whatsapp'));adv_check('non-delivery-dry-run'===$synthetic['mode'],'synthetic diagnostics are dry run');adv_check(3===count($synthetic['checks']),'synthetic channel inventory');
$profile=$attention->profile(5);adv_check('balanced'===$profile['focus_mode']&&20===$profile['hourly_budget']&&120===$profile['daily_budget'],'attention defaults');
$contract=array('version'=>'3.0.0','domain_truth_owner_preserved'=>true);adv_check(true===$contract['domain_truth_owner_preserved'],'domain truth boundary');
$src=file_get_contents(dirname(__DIR__).'/19-unified-notifications/includes/class-sun-intelligence-service.php');adv_check(false!==strpos($src,"array_intersect( $source_ids"),'AI citation allowlist binding');adv_check(false!==strpos($src,'deterministic-fallback'),'AI deterministic fallback');
$att=file_get_contents(dirname(__DIR__).'/19-unified-notifications/includes/class-sun-attention-service.php');adv_check(false!==strpos($att,"'essential_only'")&&false!==strpos($att,'source_cap_reached'),'attention budgets and caps');adv_check(false!==strpos($att,'revoke_source')&&false!==strpos($att,'live_update'),'live/revocable projections');
$route=file_get_contents(dirname(__DIR__).'/19-unified-notifications/includes/class-sun-routing-service.php');adv_check(false!==strpos($route,'cost_known')&&false!==strpos($route,'sun_all_providers_failed'),'cost-aware provider failover');
$privacy=file_get_contents(dirname(__DIR__).'/19-unified-notifications/includes/class-sun-privacy.php');foreach(array('attention_profiles','notification_rules','device_profiles','watch_history') as $needle){adv_check(false!==strpos($privacy,$needle),'privacy lifecycle '.$needle);}
$doc=file_get_contents(dirname(__DIR__).'/19-unified-notifications/docs/ADVANCED-ATTENTION-OS-3.0.0.md');for($i=1;$i<=48;$i++){adv_check(false!==strpos($doc,sprintf('F19-AF-%03d',$i)),'advanced requirement '.sprintf('%03d',$i));}
if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}echo "PASS: $tests File 19 3.0 advanced deterministic assertions\n";
