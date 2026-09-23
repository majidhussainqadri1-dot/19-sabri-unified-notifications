<?php
/** Regression assertions for the 20-round completeness audit fixes. */
$root=dirname(__DIR__);$plugin=$root.'/19-unified-notifications';$tests=0;$failures=array();
function cr_check($condition,$label){global$tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}
function cr_src($path){global$plugin;return file_get_contents($plugin.'/'.$path);}

$bootstrap=cr_src('19-unified-notifications.php');
cr_check(false!==strpos($bootstrap,"Version: 3.0.2")&&false!==strpos($bootstrap,"SUN_DB_VERSION', '3.0.1"),'runtime/schema release bump');
cr_check(false!==strpos($bootstrap,'class-sun-request-idempotency.php')&&false!==strpos($bootstrap,'class-sun-provider-webhook-verifier.php'),'replay-safety classes bootstrapped');

$advanced=cr_src('includes/class-sun-advanced-rest.php');
cr_check(false!==strpos($advanced,'is_governance_actor_eligible($user_id,true)'),'advanced governance endpoints revalidate File 00 + step-up');
cr_check(false!==strpos($advanced,'is_recipient_eligible($user_id)'),'trace endpoint revalidates current canonical eligibility');

$attention=cr_src('includes/class-sun-attention-service.php');
cr_check(false!==strpos($attention,'sun_device_profile_conflict')&&false!==strpos($attention,"'version' => (int) $existing['version']"),'per-device optimistic concurrency');
cr_check(false!==strpos($attention,'repair_missing_states'),'missing advanced state repair path');
cr_check(false!==strpos($attention,"'native_action', 'started'")&&false!==strpos($attention,"'native_action', 'completed'"),'native action trace stages');

$plugin=cr_src('includes/class-sun-plugin.php');
cr_check(false!==strpos($plugin,'$plugin_changed=SUN_VERSION!==get_option')&&false!==strpos($plugin,'SUN_Activator::schedule_events();'),'schema-neutral runtime upgrade bookkeeping');
cr_check(false!==strpos($plugin,'SUN_Request_Idempotency::register()'),'REST idempotency middleware registered');
cr_check(false!==strpos($plugin,'new SUN_Reconciliation($delivery,$this->notifications,$this->attention)'),'reconciliation receives attention repair service');
cr_check(false!==strpos($plugin,'$this->experiments'),'experiment service wired into policy graph');

$automation=cr_src('includes/class-sun-automation-service.php');
cr_check(false!==strpos($automation,'execute_rule_action')&&false!==strpos($automation,'digest-scheduled'),'automation actions execute instead of match-only');
cr_check(false!==strpos($automation,'sun_notification_rule_owner_action'),'native-owner automation remains owner-authorized');
cr_check(false!==strpos($automation,"Search.SavedSearchMatched")&&false!==strpos($automation,"trigger['owner']"),'saved-search rule binds search owner and ID');

$experiments=cr_src('includes/class-sun-experiments-service.php');$policy=cr_src('includes/class-sun-policy-engine.php');
cr_check(false!==strpos($experiments,'evaluate_decision')&&false!==strpos($experiments,'assigned_to_canary')&&false!==strpos($experiments,'record_shadow_result'),'shadow/canary runtime evaluation');
cr_check(false!==strpos($policy,'evaluate_decision'),'policy engine invokes experiment runtime');
cr_check(false!==strpos($policy,"'policy_decision'"),'policy trace stage');

$notifications=cr_src('includes/class-sun-notification-service.php');$delivery=cr_src('includes/class-sun-delivery-service.php');
foreach(array('event_intake','event_processed','projection_created','queue_enqueue') as $stage){cr_check(false!==strpos($notifications,$stage),'trace stage '.$stage);}
foreach(array('provider_attempt','provider_receipt') as $stage){cr_check(false!==strpos($delivery,$stage),'trace stage '.$stage);}

$activator=cr_src('includes/class-sun-activator.php');$routing=cr_src('includes/class-sun-routing-service.php');$privacy=cr_src('includes/class-sun-privacy.php');
cr_check(false!==strpos($activator,'route_provider varchar(100)')&&false!==strpos($delivery,"'route_provider'"),'route provider identity persisted');
cr_check(false!==strpos($routing,'COALESCE(NULLIF(d.route_provider')&&false!==strpos($routing,'COALESCE(NULLIF(route_provider'),'route-aware cost and rate-cap accounting');
cr_check(false!==strpos($privacy,'route_provider'),'privacy export includes provider route evidence');

$db=cr_src('includes/class-sun-database.php');$idem=cr_src('includes/class-sun-request-idempotency.php');$webhook=cr_src('includes/class-sun-provider-webhook-verifier.php');
cr_check(false!==strpos($db,"'request_idempotency'")&&false!==strpos($activator,'scope_hash char(64)'),'durable REST idempotency schema');
cr_check(false!==strpos($idem,'implicit:')&&false!==strpos($idem,'X-SUN-Idempotent-Replay'),'legacy-safe explicit/implicit mutation idempotency');
cr_check(false!==strpos($db,"'webhook_receipts'")&&false!==strpos($webhook,'hash_hmac')&&false!==strpos($webhook,'sun_webhook_replay'),'signed timestamped webhook replay protection');
cr_check(false!==strpos($delivery,'SUN_Provider_Webhook_Verifier::verify'),'delivery webhook path uses core verifier');

$reconciliation=cr_src('includes/class-sun-reconciliation.php');
cr_check(false!==strpos($reconciliation,'expired_idempotency')&&false!==strpos($reconciliation,'expired_webhook_receipts'),'ephemeral replay evidence cleanup');

if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: $tests completeness-audit regression assertions\n";
