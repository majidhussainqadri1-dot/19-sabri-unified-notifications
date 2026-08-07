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
for class in database crypto audit four-plan-compliance auth producer-registry event-validator template-engine preferences policy-engine deep-link delivery-service notification-service bulk-service reconciliation health rest-controller renderer router admin privacy activator plugin; do
  [[ -f "$PLUGIN/includes/class-sun-$class.php" ]] || fail "missing class $class"
done
for table in events notifications preferences deliveries templates policies devices dead_letters audit bulk_jobs; do grep -q "'$table'" "$PLUGIN/includes/class-sun-activator.php" || fail "schema table $table missing"; done
for req in ingest_domain_event idempotency quiet digest dead_letter provider_webhook register_device reconcile privacy_erasure bulk; do grep -Rqi "$req" "$PLUGIN/includes" || fail "required behavior marker $req missing"; done
grep -q "sabri_membership_claims_v2" "$PLUGIN/includes/class-sun-auth.php" || fail "File 00 canonical claims contract missing"
grep -q "single_free_tier" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "single free tier law missing"
grep -q "donor_advantage.*false" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "no donor advantage law missing"
grep -q "search_owner_file.*26" "$PLUGIN/includes/class-sun-four-plan-compliance.php" || fail "File 26 search ownership missing"
! grep -RInE '\beval\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bpassthru\s*\(|\bunserialize\s*\(' "$PLUGIN" --include='*.php' || fail "dangerous function found"
! grep -RInE "(api[_-]?key|secret|token)[[:space:]]*=[[:space:]]*['\"][A-Za-z0-9+/=_-]{20,}" "$PLUGIN" || fail "possible embedded secret"
grep -q "sun_file20_notification_slot" "$PLUGIN/includes/class-sun-plugin.php" || fail "File 20 single-bell slot missing"
echo "PASS: syntax, structure, four-plan, security-pattern and requirement-marker audit"
