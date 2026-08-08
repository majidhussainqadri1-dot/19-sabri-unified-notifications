# File 19 — Fresh Four-Plan / Top-20 Audit and Corrective Register

Date: 2026-08-07 (Pakistan Standard Time)
Corrective runtime/schema: 2.3.0 / 2.3.0
Fresh-review baseline: current `main` at `bd35fd7a339d1cc5ed50a414a64fef9846d062a6`

## Governing corpus

1. SSH-PMP-2026-v3.0 — definitive platform constitution, canonical ownership and release-status law.
2. Sabri Recovered Directives v2.1 — later Founder directives: central green, RTL/right-priority, one complete free tier, zero donor advantage, Islamic privacy/minimization and review→fix→fresh review→retest.
3. Sabri Continuous Value / Top-20 Superset v1.0 — later central plan with CV-097 through CV-106 notification requirements and File 26 discovery ownership.
4. SSH-F19-PLAN-2026-v1.0 — dedicated File 19 notification entity/delivery/preferences/retries/privacy/migration/QA/DoD specification.

Temporal conflict handling follows the platform law: latest explicit Founder/safety decision first; among central plans the later approved central plan overrides only conflicting older clauses; non-conflicting clauses remain cumulative. The dedicated File 19 plan governs its native implementation beneath those central laws.

## Review 1 — Scope, ownership, package and existing 40-round baseline

Fresh result: the current 2.2.0 `main` was materially stronger than the earlier 2.1.0 state and already contained File 00 fail-closed claims, producer owner/schema controls, File 20 one-bell integration, Safe Mode, provider circuit breakers, canonical `unified-notifications-19` release packaging and deterministic QA.

Remaining defect: the executable four-plan layer did not enumerate the Top-20 notification family CV-097–CV-106, and the runtime/data model had no dedicated granular subscription owner for CV-099 or fatigue metric for CV-106.

Correction: 2.3.0 keeps all current-main operational controls, adds the Top-20 capability/event catalog, a canonical subscriptions table/service and privacy-minimized wellbeing metric without creating a second shell, discovery backend or domain-state owner.

## Review 2 — Identity, authorization, provenance and privilege boundaries

Defects found:
- Founder compatibility could still fall back to `SUN_FOUNDER_USER_ID` when File 00 returned no institutional Founder claim; compatibility therefore needed an explicit host opt-in rather than silent fallback.
- Top-20 scoped subscription actions needed to remain own-user and current-eligibility bound.

Corrections:
- Canonical File 00 Founder/institutional claims remain first authority. Numeric compatibility bootstrap now additionally requires explicit `sun_allow_founder_bootstrap` approval and therefore cannot silently outrank File 00.
- REST subscription/wellbeing surfaces use the existing fail-closed File 00 `logged_in()` eligibility boundary; renderer assets/settings are likewise eligibility-gated.
- Existing producer owner binding and optional schema allowlists are preserved.

## Review 3 — Top-20 CV-097 through CV-106 functional completion

Defects found:
- CV-099 granular person/topic/community/course/event/doctor/channel subscription and per-scope frequency was absent.
- CV-101 appointment reminders, CV-102 correction/retraction alerts, CV-103 security alerts and CV-104 opt-in creator bulletins lacked a single executable semantic catalog under File 19.
- CV-106 notification-fatigue measurement was absent.

Corrections:
- Added `SUN_Subscriptions`: explicit scope types, enabled state, optimistic version conflicts and immediate/daily/weekly frequency.
- Added `subscription_scope` to the strict event envelope. Required scoped events are suppressed unless a matching enabled subscription exists; explicit disabled records suppress ordinary scoped events.
- `Social.CreatorBulletinPublished` is rejected unless its envelope carries a required explicit opt-in subscription scope.
- Added factual event catalog for appointment lifecycle, correction/retraction, security and creator-bulletin events while preserving Files 00/08/21 and other native owners as source of truth.
- Added `SUN_Wellbeing` own-user aggregate: volume, unread/archive ratio, delivery failure and muted-preference signals; no message/body/profile/donation/clinical data is copied, and the explicit guardrail is `more-notifications-is-not-a-kpi`.
- Added own-user REST/settings interfaces for subscriptions and wellbeing.

## Review 4 — Adversarial policy, privacy, regression and release review

Critical defects found during the fresh adversarial pass:
- A registered producer could supply another valid category such as `security`, because the old policy engine accepted event category when it happened to be in the global category list. That could convert an ordinary event into an essential category and bypass ordinary subscription behavior.
- An event could also lower policy sensitivity/priority because old event hints replaced policy values when syntactically valid.
- New subscription data had to be incorporated into export/erasure/uninstall/schema-health evidence and regression tests.

Corrections:
- Policy table now owns category absolutely. Producers cannot impersonate `security`, `safety` or `system`.
- Producer priority/sensitivity are upward-only hints: `stronger_value()` can strengthen but never weaken the canonical policy value.
- Subscription records are included in schema health, privacy export/erasure and guarded destructive uninstall; default uninstall remains non-destructive.
- Privacy erasure pseudonymizes retained notification/delivery linkage and removes granular user-choice records, while provider-erasure propagation remains explicit.
- Unit/static/package tests were expanded for CV-097–CV-106, subscription scopes/frequencies, required creator opt-in, package-folder law, File 00 claims, producer owner/schema, policy category authority, sensitivity downgrade prevention and privacy lifecycle.

## Four-round result

All four fresh rounds found concrete repository-owned defects. Every identified defect was corrected before the next release gate. The corrective branch is `file19-four-plan-top20-2.3.0`.

The source-code completion claim is intentionally narrower than production completion. File 19's own DoD still requires exact-head package/checksum/CI evidence, real Hostinger staging, real companion/provider tests, accessibility/load/security evidence, backup/restore/rollback rehearsal and Founder acceptance before Staging-Accepted/Live/Operational status can be asserted.
