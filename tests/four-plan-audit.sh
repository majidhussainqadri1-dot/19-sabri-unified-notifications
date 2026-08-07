#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/19-unified-notifications"
COMPLIANCE="$PLUGIN/includes/class-sun-four-plan-compliance.php"
fail(){ echo "FAIL: $*" >&2; exit 1; }
[[ -f "$COMPLIANCE" ]] || fail "four-plan compliance class missing"
for marker in 'SSH-PMP-2026-v3.0' 'Recovered Directives' 'Top-20 Superset' 'SSH-F19-PLAN-2026-v1.0'; do
  grep -q "$marker" "$COMPLIANCE" || fail "governing plan marker missing: $marker"
done
for n in $(seq -w 97 106); do
  grep -q "CV-$n" "$COMPLIANCE" || fail "Top-20 requirement CV-$n missing"
done
for invariant in one_bell one_center producer_owner_binding file00_live_claims no_paid_advantage no_donor_advantage provider_success_honesty privacy_minimization rtl_first green_primary_brand; do
  grep -q "'$invariant'" "$COMPLIANCE" || fail "invariant missing: $invariant"
done
for feature in subscription_scope requires_opt_in max_per_24h creator_bulletin_frequency_cap notification_report fatigue_signal_count sun_authorize_notification_deep_link sun_event_owner_mismatch sun_device_token_unavailable sabri_membership_claims_v2; do
  grep -Rqs "$feature" "$PLUGIN/includes" "$PLUGIN/templates" || fail "four-plan behavior missing: $feature"
done
! grep -Rqs "apply_filters( 'sabri_membership_claims'," "$PLUGIN/includes" || fail "legacy File 00 claims fallback must not grant access"
! grep -Rqs "ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)" "$PLUGIN/includes" || fail "device ownership takeover regression detected"
echo "PASS: four governing plans and CV-097..CV-106 executable compliance markers"
