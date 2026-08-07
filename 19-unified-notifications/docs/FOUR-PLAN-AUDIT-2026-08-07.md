# File 19 — Four-Plan Audit and Corrective Register

Date: 2026-08-07 (Pakistan Standard Time)
Corrective release: 2.1.0 / schema 2.1.0

## Governing corpus and precedence

1. SSH-PMP-2026-v3.0 — platform constitution, canonical ownership, status truth, verified entry, security/privacy and release gates.
2. Sabri Recovered Directives v2.1 — later Founder amendments including central green, RTL/right-priority, one complete free tier, no donor advantage, global single navigation, continued review/fix and privacy/minimization.
3. Sabri Continuous Value / Top-20 Superset v1.0 — one-roof value, top-platform notification utility, user control, healthy-use, low-bandwidth/accessibility, File 26 discovery ownership and continuous-value governance.
4. SSH-F19-PLAN-2026-v1.0 — File 19 canonical notification entity, single bell/center, preferences, quiet hours, digests, adapters, retry/dead-letter, audit, privacy, migration, rollback and QA.

Precedence applied: latest explicit Founder/safety rule > recovered directives > later central Top-20 constitution > dedicated File 19 requirement > verified runtime evidence. Older paid-tier language in an earlier file plan is not an active runtime rule.

## Review round 1 — Constitution, ownership and business-law

Defects found:
- No executable four-plan trace/precedence manifest.
- Runtime baseline still declared WordPress 6.6 / PHP 8.1 rather than the current project baseline.
- No explicit machine-readable guarantee that donor/payment state cannot affect notification delivery, importance or access.
- File 26 search/discovery ownership and File 20/25 shell/visual ownership were only documentary, not executable governance metadata.

Corrections:
- Added `SUN_Four_Plan_Compliance` with all four governing plan IDs, canonical owners, green/RTL/free-tier/no-donor rules and no-duplicate-backend invariants.
- Raised corrective runtime/schema to 2.1.0 and project minimums to WordPress 7.0 / PHP 8.3.

Result: no known repository-owned constitution/business-law blocker.

## Review round 2 — Identity, authorization, privacy and trust

Defects found:
- Critical: File 19 treated local `sun_*` user-meta as suspension/verification/guardian truth, violating File 00 canonical ownership.
- Critical: when File 00 was absent, protected notification actions could fall back to duplicated local identity state instead of failing closed.
- High: recipient eligibility did not explicitly re-evaluate revocation, risk blocking and consent state.
- Medium: Founder determination did not consume canonical institutional claims.

Corrections:
- Reworked `SUN_Auth` to consume `sabri_membership_claims_v2` on every protected path.
- Removed local File 19 identity metadata as authority.
- Added live active/verified/suspended/revoked/risk/guardian/consent checks and fail-closed behavior when canonical claims are unavailable.
- Founder governance now accepts the canonical institutional claim, with the configured Founder ID only as bootstrap compatibility.

Result: no known repository-owned identity/authorization blocker.

## Review round 3 — Top-20 value, UX, healthy-use and cross-file integration

Findings:
- Single bell/center, filters, read/archive/bulk controls, quiet hours, digests, email/push/SMS, safe deep links, queue/retry/dead-letter, green visual system, RTL, reduced motion and responsive behavior already existed.
- No paid/donor priority path exists in policy resolution; the compliance contract now makes that prohibition explicit.
- Search/discovery remains outside File 19 and is explicitly assigned to File 26; File 19 exposes notifications rather than creating a second search backend.
- File 20 remains the one-bell placement owner and File 25 remains visual-token owner.

Correction:
- Added regression assertions for four-plan ownership, green brand, one complete free tier and no donor advantage.

Result: no known repository-owned Top-20/UX/cross-file blocker.

## Review round 4 — Adversarial release, regression and truth-status

Adversarial cases reviewed:
- duplicate/replayed producer events; cross-origin deep links; broad-role bulk recipients; unsafe template variables/scripts; sensitive external previews; provider failure; dead-letter retry; stale identity claims; File 00 outage; donation/payment influence; duplicate bell ownership; search-owner drift; rollback/package reproducibility.

Corrections and evidence:
- Added deterministic tests for fail-closed File 00 outage and valid verified canonical claims.
- Added deterministic four-plan compliance assertions.
- Updated release/build/CI identity to the corrective 2.1.0 artifact.
- Four-plan compliance is included in operational health evidence without exposing user data.

Truth-status after this audit:
- Specified: complete for repository-owned File 19 scope.
- Coded: corrective 2.1.0 branch complete for identified defects.
- Packaged/Automated-QA: must be proven by the PR exact-head CI artifact/checksum.
- Staging-Accepted, Live-Deployed, Operational: not claimed; require real Hostinger/WordPress/provider/integration evidence.
