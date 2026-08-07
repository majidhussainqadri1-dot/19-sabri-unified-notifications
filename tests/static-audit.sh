#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/19-unified-notifications"
fail(){ echo "FAIL: $*" >&2; exit 1; }
find "$PLUGIN" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
node --check "$PLUGIN/assets/js/notifications.js" >/dev/null
node --check "$PLUGIN/assets/js/push-service-worker.js" >/dev/null
[[ -f "$PLUGIN/19-unified-notifications.php" ]] || fail "bootstrap missing"
grep -q "Version: 2.1.0" "$PLUGIN/19-unified-notifications.php" || fail "version mismatch"
for class in database crypto audit auth producer-registry four-plan-compliance subscriptions event-validator template-engine preferences policy-engine deep-link delivery-service notification-service bulk-service reconciliation value-metrics health rest-controller renderer router admin privacy activator plugin; do
  [[ -f "$PLUGIN/includes/class-sun-$class.php" ]] || fail "missing class $class"
done
for table in events notifications preferences deliveries templates policies devices dead_letters audit bulk_jobs; do grep -q "'$table'" "$PLUGIN/includes/class-sun-activator.php" || fail "schema table $table missing"; done
grep -q "table( 'subscriptions' )" "$PLUGIN/includes/class-sun-subscriptions.php" || fail "subscriptions schema missing"
for req in ingest_domain_event idempotency quiet digest dead_letter provider_webhook register_device reconcile privacy_erasure bulk subscription_scope notification_report fatigue_signal_count; do grep -Rqi "$req" "$PLUGIN/includes" || fail "required behavior marker $req missing"; done
! grep -RInE '\beval\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bpassthru\s*\(|\bunserialize\s*\(' "$PLUGIN" --include='*.php' || fail "dangerous function found"
! grep -RInE "(api[_-]?key|secret|token)[[:space:]]*=[[:space:]]*['\"][A-Za-z0-9+/=_-]{20,}" "$PLUGIN" || fail "possible embedded secret"
! grep -Rqs "ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)" "$PLUGIN/includes" || fail "cross-user device-token takeover regression"
! grep -Rqs "apply_filters( 'sabri_membership_claims'," "$PLUGIN/includes" || fail "legacy identity fallback detected"
grep -q "sabri_membership_claims_v2" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 v2 claims binding missing"
grep -q "sun_event_owner_mismatch" "$PLUGIN/includes/class-sun-event-validator.php" || fail "canonical producer-owner binding missing"
grep -q "sun_authorize_notification_deep_link" "$PLUGIN/includes/class-sun-deep-link.php" || fail "click-time domain authorization missing"
grep -q "sun_file20_notification_slot" "$PLUGIN/includes/class-sun-plugin.php" || fail "File 20 single-bell slot missing"
grep -q "min-height:44px" "$PLUGIN/assets/css/notifications.css" || fail "44px touch target floor missing"
grep -q "direction:rtl" "$PLUGIN/assets/css/notifications.css" || fail "RTL-first surface marker missing"
grep -q -- "--sun-green:#157347" "$PLUGIN/assets/css/notifications.css" || fail "green primary brand token missing"
bash "$ROOT/tests/four-plan-audit.sh" >/dev/null
echo "PASS: syntax, structure, security-regression, accessibility and four-plan marker audit"
