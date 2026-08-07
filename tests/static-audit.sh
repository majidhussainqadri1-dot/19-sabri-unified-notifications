#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/19-unified-notifications"
fail(){ echo "FAIL: $*" >&2; exit 1; }
find "$PLUGIN" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
node --check "$PLUGIN/assets/js/notifications.js" >/dev/null
node --check "$PLUGIN/assets/js/push-service-worker.js" >/dev/null
[[ -f "$PLUGIN/19-unified-notifications.php" ]] || fail "bootstrap missing"
grep -q "Version: 2.2.0" "$PLUGIN/19-unified-notifications.php" || fail "version mismatch"
grep -q "Requires at least: 7.0" "$PLUGIN/19-unified-notifications.php" || fail "WordPress baseline mismatch"
grep -q "Requires PHP: 8.3" "$PLUGIN/19-unified-notifications.php" || fail "PHP baseline mismatch"
for class in database crypto audit four-plan-compliance operational-gate provider-circuit auth producer-registry event-validator template-engine preferences policy-engine deep-link delivery-service notification-service bulk-service reconciliation health rest-controller renderer router admin privacy activator plugin; do
  [[ -f "$PLUGIN/includes/class-sun-$class.php" ]] || fail "missing class $class"
done
for table in events notifications preferences deliveries templates policies devices dead_letters audit bulk_jobs; do grep -q "'$table'" "$PLUGIN/includes/class-sun-activator.php" || fail "schema table $table missing"; done
for req in ingest_domain_event idempotency quiet digest dead_letter provider_webhook register_device reconcile privacy_erasure bulk; do grep -Rqi "$req" "$PLUGIN/includes" || fail "required behavior marker $req missing"; done
grep -q "sabri_membership_claims_v2" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 canonical claims contract missing"
grep -q "email_verified" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 email verification projection missing"
grep -q "phone_verified" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 phone verification projection missing"
grep -q "step_up_verified" "$PLUGIN/includes/class-sun-auth.php" || fail "governance step-up revalidation missing"
grep -q "single_free_tier" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "single free tier law missing"
grep -q "donor_advantage.*false" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "no donor advantage law missing"
grep -q "search_owner_file.*26" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "File 26 search ownership missing"
grep -q "top20-central-plan > recovered-directives > definitive-master-v3" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "central-plan temporal precedence missing"
grep -q "sun_event_owner_mismatch" "$PLUGIN/includes/class-sun-event-validator.php" || fail "producer canonical-owner binding missing"
grep -q "sun_schema_version_unsupported" "$PLUGIN/includes/class-sun-event-validator.php" || fail "producer schema allowlist missing"
grep -q "effective_port" "$PLUGIN/includes/class-sun-deep-link.php" || fail "strict same-origin port validation missing"
grep -q "SUN_MIN_WP_VERSION" "$PLUGIN/includes/class-sun-activator.php" || fail "activation WordPress baseline not centralized"
grep -q "SUN_MIN_PHP_VERSION" "$PLUGIN/includes/class-sun-activator.php" || fail "activation PHP baseline not centralized"
grep -q "sun_activation_lock" "$PLUGIN/includes/class-sun-activator.php" || fail "concurrent activation lock missing"
grep -q "SUN_Operational_Gate::allows( 'external_delivery' )" "$PLUGIN/includes/adapters/class-sun-email-adapter.php" || fail "email safe-mode gate missing"
grep -q "SUN_Provider_Circuit::is_open" "$PLUGIN/includes/adapters/class-sun-push-adapter.php" || fail "provider circuit breaker missing"
grep -q "is_governance_actor_eligible" "$PLUGIN/includes/class-sun-bulk-service.php" || fail "background bulk actor revalidation missing"
grep -q "'owner'=>'File 19'" "$PLUGIN/includes/class-sun-bulk-service.php" || fail "bulk canonical event owner missing"
grep -q "recipient_id=0" "$PLUGIN/includes/class-sun-privacy.php" || fail "privacy pseudonymization missing"
grep -q "sun_privacy_provider_erasure_requested" "$PLUGIN/includes/class-sun-privacy.php" || fail "provider erasure propagation hook missing"
grep -q "sabri_file20_context_controls_markup_v1" "$PLUGIN/templates/page.php" || fail "File 20 context-control integration missing"
grep -q "X-Robots-Tag" "$PLUGIN/includes/class-sun-router.php" || fail "private route noindex header missing"
! grep -RInE '\beval\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bpassthru\s*\(|\bunserialize\s*\(' "$PLUGIN" --include='*.php' || fail "dangerous function found"
! grep -RInE "(api[_-]?key|secret|token)[[:space:]]*=[[:space:]]*['\"][A-Za-z0-9+/=_-]{20,}" "$PLUGIN" || fail "possible embedded secret"
grep -q "sun_file20_notification_slot" "$PLUGIN/includes/class-sun-plugin.php" || fail "File 20 single-bell slot missing"
echo "PASS: syntax, structure, 40-round four-plan, security/privacy and requirement-marker audit"
