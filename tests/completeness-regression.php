<?php
/** Regression assertions for the 20-round completeness audit fixes. */
$root=dirname(__DIR__);$plugin=$root.'/19-unified-notifications';$tests=0;$failures=array();
function cr_check($condition,$label){global$tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}
function cr_src($path){global$plugin;return file_get_contents($plugin.'/'.$path);}

$bootstrap=cr_src('19-unified-notifications.php');
cr_check(false!==strpos($bootstrap,"Version: 3.0.4")&&false!==strpos($bootstrap,"SUN_DB_VERSION', '3.0.2"),'runtime/schema release bump');
cr_check(false!==strpos($bootstrap,'class-sun-request-idempotency.php')&&false!==strpos($bootstrap,'class-sun-provider-webhook-verifier.php'),'replay-safety classes bootstrapped');

$auth_src=cr_src('includes/class-sun-auth.php');
cr_check(false!==strpos($auth_src,'SMC_Contracts::assertions')&&false===strpos($auth_src,'sabri_membership_claims_v2'),'File 00 current identity contract replaces retired claims filter');
cr_check(false!==strpos($auth_src,'SAUTH_Passkey_Runtime::current_assurance'),'File 02 owns current step-up assurance');

$advanced=cr_src('includes/class-sun-advanced-rest.php');
cr_check(false!==strpos($advanced,'is_governance_actor_eligible($user_id,true)'),'advanced governance endpoints revalidate File 00 + step-up');
cr_check(false!==strpos($advanced,'is_recipient_eligible($user_id)'),'trace endpoint revalidates current canonical eligibility');

$attention=cr_src('includes/class-sun-attention-service.php');
cr_check(false!==strpos($attention,'sun_device_profile_conflict')&&false!==strpos($attention,"'version' => (int) \$existing['version']"),'per-device optimistic concurrency');
cr_check(false!==strpos($attention,'repair_missing_states'),'missing advanced state repair path');
cr_check(false!==strpos($attention,"'native_action', 'started'")&&false!==strpos($attention,"'native_action', 'completed'"),'native action trace stages');

$plugin_src=cr_src('includes/class-sun-plugin.php');
cr_check(false!==strpos($plugin_src,'$plugin_changed=SUN_VERSION!==get_option')&&false!==strpos($plugin_src,'SUN_Activator::schedule_events();'),'schema-neutral runtime upgrade bookkeeping');
cr_check(false!==strpos($plugin_src,'SUN_Request_Idempotency::register()'),'REST idempotency middleware registered');
cr_check(false!==strpos($plugin_src,'new SUN_Reconciliation($delivery,$this->notifications,$this->attention)'),'reconciliation receives attention repair service');
cr_check(false!==strpos($plugin_src,'$this->experiments'),'experiment service wired into policy graph');

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

$ownership=cr_src('includes/class-sun-four-plan-compliance.php');
cr_check(false!==strpos($ownership,"AccountAuthenticationFailed.v1'=>array('owner'=>2")&&false!==strpos($ownership,"PasswordResetCompleted.v1'=>array('owner'=>2"),'authentication event ownership aligned to File 02');

$activator=cr_src('includes/class-sun-activator.php');$routing=cr_src('includes/class-sun-routing-service.php');$privacy=cr_src('includes/class-sun-privacy.php');
cr_check(false!==strpos($activator,'route_provider varchar(100)')&&false!==strpos($delivery,"'route_provider'"),'route provider identity persisted');
cr_check(false!==strpos($routing,'COALESCE(NULLIF(d.route_provider')&&false!==strpos($routing,'COALESCE(NULLIF(route_provider'),'route-aware cost and rate-cap accounting');
cr_check(false!==strpos($privacy,'route_provider'),'privacy export includes provider route evidence');

$db=cr_src('includes/class-sun-database.php');$idem=cr_src('includes/class-sun-request-idempotency.php');$webhook=cr_src('includes/class-sun-provider-webhook-verifier.php');
cr_check(false!==strpos($db,"'request_idempotency'")&&false!==strpos($activator,'scope_hash char(64)'),'durable REST idempotency schema');
cr_check(false!==strpos($idem,'rest_dispatch_request')&&false!==strpos($idem,'implicit:')&&false!==strpos($idem,'X-SUN-Idempotent-Replay'),'authorized legacy-safe explicit/implicit mutation idempotency');
cr_check(false!==strpos($db,"'webhook_receipts'")&&false!==strpos($webhook,'hash_hmac')&&false!==strpos($webhook,'reserve_replay')&&false!==strpos($webhook,'sun_webhook_replay'),'signed timestamped/custom webhook replay protection');
cr_check(false!==strpos($delivery,'SUN_Provider_Webhook_Verifier::verify'),'delivery webhook path uses core verifier');

$reconciliation=cr_src('includes/class-sun-reconciliation.php');
cr_check(false!==strpos($reconciliation,'expired_idempotency')&&false!==strpos($reconciliation,'expired_webhook_receipts'),'ephemeral replay evidence cleanup');


$cross=cr_src('includes/class-sun-cross-file-contracts.php');$legacy=cr_src('includes/class-sun-legacy-migration.php');
cr_check(false!==strpos($cross,'SPF_Registry::register_manifest')&&false!==strpos($cross,'SPF_Registry::map_route'),'File 01 manifest/route registry adapter');
cr_check(false!==strpos($cross,'sun_validate_saved_search_ownership')&&false===strpos($cross,'sabri_file26_saved_queries_v1')&&false!==strpos($automation,'SUN_Cross_File_Contracts::saved_search_owned'),'File 26 saved-search ownership uses owner contract and never private meta');
cr_check(false!==strpos($cross,"'module_key'=>'file-00'")&&false!==strpos($cross,"'module_key'=>'file-20'")&&false!==strpos($cross,"'module_key'=>'file-24'"),'File 01 manifest declares required File 00/20/24 dependencies');
cr_check(false!==strpos($cross,'spcrc/file19_contract_state')&&false!==strpos($cross,'sun_file20_notification_surface_state'),'File 24 assurance and File 20 surface contracts published/consumed');
cr_check(false!==strpos($attention,"n.status NOT IN ('deleted','expired')")&&false!==strpos($attention,'n.expires_at IS NULL OR n.expires_at>%s'),'attention search/state expiry guards');
$validator=cr_src('includes/class-sun-event-validator.php');
cr_check(false!==strpos($validator,'sun_event_data_field_not_allowed')&&false!==strpos($validator,'sun_event_sensitive_data_forbidden'),'event payload allowlist and sensitive-key block');
cr_check(false!==strpos($notifications,"'subscription_scope'=>\$event['subscription_scope']")&&false===strpos(substr($notifications,strpos($notifications,'$stored_payload='),500),"'recipients'"),'persisted event payload excludes recipient list');
cr_check(false!==strpos($delivery,"home_url('/notifications/')")&&false!==strpos($delivery,'digest_key=%s'),'digest overflow center link and receipt fanout');
cr_check(false!==strpos($routing,'sun_recipient_delivery_region')&&false!==strpos($routing,"CASE health_state WHEN 'healthy'"),'region/health-aware provider routing');
cr_check(false!==strpos($privacy,'scrub_legacy_event_payloads')&&false!==strpos($privacy,'sun_event_payload_retention_days'),'legacy payload erasure and event retention');
cr_check(false!==strpos($activator,"array('search','Search.*'")&&false!==strpos($activator,"array('analytics','Analytics.*'"),'Search/Research/Knowledge/Analytics policy families');
$bulk=cr_src('includes/class-sun-bulk-service.php');
cr_check(false!==strpos($bulk,'sun_bulk_governance_evidence_required')&&false!==strpos($activator,'compensation_plan text NULL'),'bulk reason and compensation evidence');
$admin=cr_src('includes/class-sun-admin.php');$admin_template=cr_src('templates/admin.php');
cr_check(false!==strpos($admin,"'reason'=>")&&false!==strpos($admin,"'compensation_plan'=>")&&false!==strpos($admin_template,'name="reason"')&&false!==strpos($admin_template,'name="compensation_plan"'),'bulk governance evidence wired through admin controller and UI');
$health=cr_src('includes/class-sun-health.php');
cr_check(false!==strpos($health,"'request_idempotency','webhook_receipts'")&&false!==strpos($health,"'file01_registry'")&&false!==strpos($health,"'legacy_migration_gate'"),'health covers current schema and cross-file gates');
cr_check(false!==strpos($health,"'file00_contract'")&&false!==strpos($health,"'file02_step_up_provider'")&&false!==strpos($health,"'file24_containment_contract'"),'health uses current File 00/02/24 contract evidence');
$functions=cr_src('includes/functions.php');
cr_check(false!==strpos($functions,'sun_live_owner_required')&&false!==strpos($attention,'sun_live_owner_mismatch'),'live projection update is producer-bound');
$css=cr_src('assets/css/notifications.css');
cr_check(false!==strpos($css,'--sabri-color-primary')&&false!==strpos($css,'--sabri-shadow-card'),'File 25 visual token bridge');
cr_check(false!==strpos($legacy,'sun_legacy_notification_sources')&&false!==strpos($legacy,'sun_legacy_notification_migration_applicable')&&false!==strpos($legacy,'legacy_source_contracts_unverified')&&false!==strpos($legacy,'public static function execute')&&false!==strpos($legacy,'public static function rollback'),'historical migration is reversible, applicability-aware and evidence-gated');
$push=cr_src('includes/adapters/class-sun-push-adapter.php');cr_check(false!==strpos($push,'sun_push_invalid_token_codes')&&false!==strpos($push,"'status'=>'revoked'"),'invalid push tokens are revoked');
cr_check(false!==strpos($privacy,'$page=1')&&false!==strpos($privacy,'\'done\'=>count((array)$rows)<$limit'),'legacy event erasure is paginated to completion');

if($failures){fwrite(STDERR,"FAIL (".count($failures)."/$tests):\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: $tests completeness-audit regression assertions\n";
