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
grep -q "Requires at least: 7.0.1" "$PLUGIN/19-unified-notifications.php" || fail "WordPress project baseline mismatch"
grep -q "Requires PHP: 8.3" "$PLUGIN/19-unified-notifications.php" || fail "PHP project baseline mismatch"
grep -Fq "version_compare( (string) \$wp_version, '7.0.1'" "$PLUGIN/includes/class-sun-activator.php" || fail "activation WordPress gate mismatch"
grep -Fq "version_compare( PHP_VERSION, '8.3'" "$PLUGIN/includes/class-sun-activator.php" || fail "activation PHP gate mismatch"
for class in database crypto audit four-plan-compliance auth producer-registry event-validator template-engine preferences subscriptions policy-engine deep-link delivery-service notification-service bulk-service reconciliation health wellbeing rest-controller renderer router admin privacy activator plugin; do
  [[ -f "$PLUGIN/includes/class-sun-$class.php" ]] || fail "missing class $class"
done
for table in events notifications preferences subscriptions deliveries templates policies devices dead_letters audit bulk_jobs; do grep -q "'$table'" "$PLUGIN/includes/class-sun-activator.php" || fail "schema table $table missing"; done
for req in ingest_domain_event idempotency quiet digest dead_letter provider_webhook register_device reconcile privacy_erasure bulk subscription wellbeing; do grep -Rqi "$req" "$PLUGIN/includes" || fail "required behavior marker $req missing"; done
grep -q "sabri_membership_claims_v2" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 canonical claims contract missing"
grep -q "email_verified" "$PLUGIN/includes/class-sun-auth.php" || fail "canonical email verification claim missing"
grep -q "institutional_role" "$PLUGIN/includes/class-sun-auth.php" || fail "canonical Founder institutional claim missing"
grep -q "single_free_tier" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "single free tier law missing"
grep -q "donor_advantage.*false" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "no donor advantage law missing"
grep -q "search_owner_file.*26" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "File 26 search ownership missing"
for cv in CV-097 CV-098 CV-099 CV-100 CV-101 CV-102 CV-103 CV-104 CV-105 CV-106; do grep -q "$cv" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "Top-20 capability $cv missing"; done
grep -q "subscription_scope" "$PLUGIN/includes/class-sun-event-validator.php" || fail "granular subscription event contract missing"
grep -q "sun_subscription_scope_required" "$PLUGIN/includes/class-sun-event-validator.php" || fail "creator bulletin opt-in enforcement missing"
grep -Fq "category    = sanitize_key( (string) \$policy['category'] )" "$PLUGIN/includes/class-sun-policy-engine.php" || fail "policy-owned category enforcement missing"
grep -q "stronger_value" "$PLUGIN/includes/class-sun-policy-engine.php" || fail "priority/sensitivity downgrade prevention missing"
grep -q "subscription_preference" "$PLUGIN/includes/class-sun-policy-engine.php" || fail "subscription policy suppression missing"
grep -q "more-notifications-is-not-a-kpi" "$PLUGIN/includes/class-sun-wellbeing.php" || fail "healthy-use guardrail missing"
! grep -RInE '\beval\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bpassthru\s*\(|\bunserialize\s*\(' "$PLUGIN" --include='*.php' || fail "dangerous function found"
! grep -RInE "(api[_-]?key|secret|token)[[:space:]]*=[[:space:]]*['\"][A-Za-z0-9+/=_-]{20,}" "$PLUGIN" || fail "possible embedded secret"
grep -q "sun_file20_notification_slot" "$PLUGIN/includes/class-sun-plugin.php" || fail "File 20 single-bell slot missing"
grep -q "subscriptions" "$PLUGIN/includes/class-sun-privacy.php" || fail "subscription privacy lifecycle missing"
echo "PASS: syntax, structure, four-plan, Top-20, security-pattern and requirement-marker audit"
