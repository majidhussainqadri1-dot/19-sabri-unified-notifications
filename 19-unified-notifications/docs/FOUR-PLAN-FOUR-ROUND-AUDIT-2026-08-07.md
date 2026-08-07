# File 19 — Four-Plan / Four-Round Audit and Correction Ledger

Date: 2026-08-07  
Runtime candidate after corrections: **2.1.0**  
Scope: GitHub source, architecture, contracts, security/privacy, Top-20 value requirements, UX/accessibility, deterministic QA and package readiness.  
Non-scope: real Hostinger staging acceptance, real provider credentials/delivery, live companion contracts, production deployment and operational SLO evidence.

## Governing corpus

1. `SSH-PMP-2026-v3.0` — Definitive Integrated Master Plan.
2. Consolidated recovered Founder directives (current approved register).
3. Continuous Value / Global Top-20 Feature Superset Master Plan (`CV-097` through `CV-106` are File 19 notification requirements).
4. `SSH-F19-PLAN-2026-v1.0` — Unified Notifications and Alerts dedicated plan.

Latest explicit Founder decision and later approved central plan prevail over conflicting older text. Runtime evidence proves implementation status only; it cannot silently amend scope.

## Review Round 1 — Architecture, ownership and governing traceability

**Defect groups found: 5. Defect-free checks: 0. All five were corrected before the next round.**

1. No executable four-plan/Top-20 compliance constitution existed; the 2.0.0 traceability file covered the dedicated plan but did not make the newer cross-plan invariants testable.
2. Recipient/Founder authority could fall back to local WordPress state rather than requiring the current canonical File 00 assertion contract.
3. A registered producer could submit an `owner` string that was not its registered canonical owner.
4. Producer event hints could downgrade policy category/priority/sensitivity, including security-sensitive rendering classification.
5. Policy lookup ordered broad rows before more-specific event patterns, preventing deterministic specific-policy precedence.

**Corrections:** `SUN_Four_Plan_Compliance`, File 00 v2 claim boundary, producer-owner equality, non-downgradable policy floors, specific-pattern-first policy lookup.

## Review Round 2 — Fresh adversarial security/privacy review

**Defect groups found: 4. Defect-free checks: 0. All four were corrected before the next round.**

1. **Critical device-token ownership takeover:** the 2.0.0 upsert could reassign the same provider/token hash to another user through `ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)`.
2. Stored deep links were same-origin checked but domain object authorization was not explicitly revalidated by the canonical owner at click time.
3. Event presentation data was size-bounded but not depth-bounded and could carry credential-like fields such as API keys/tokens/secrets.
4. HMAC timestamp/signature and trace identifiers accepted unnecessarily loose shapes, weakening deterministic rejection and diagnostics.

**Corrections:** immutable cross-user device binding, click-time `sun_authorize_notification_deep_link` fail-closed contract, nested payload/secret rejection, bounded trace/signature formats.

## Review Round 3 — Top-20 continuous-value and cross-file review

**Defect groups found: 6. Defect-free checks: 0. All six were corrected before the next round.**

1. `CV-099` lacked granular notification subscriptions by person/topic/community/course/event/doctor/channel and per-scope frequency.
2. `CV-104` lacked explicit creator-bulletin opt-in and bounded one-to-many frequency control.
3. `CV-104/CV-106` lacked a user complaint/report signal for irrelevant or abusive notifications.
4. `CV-106` lacked an aggregate notification-fatigue/value signal and explicit “more notifications is not success” governance.
5. File 19's built-in producer incorrectly treated `Security.*`/`Safety.*` facts as File 19-owned instead of requiring the actual account/safety domain owner to register them.
6. The newly introduced scoped-subscription preference data needed its own schema lifecycle, UI/API, privacy export/erasure and health evidence rather than becoming an undocumented side table.

**Corrections:** `SUN_Subscriptions`, subscription REST/settings/integration contracts, creator bulletin opt-in + one-per-24h user/scope cap, Report Notification, `SUN_Value_Metrics`, File 19 default producer narrowed to `System.*`, independent subscription schema + privacy/health integration.

## Review Round 4 — Post-correction authorization, UX and release QA

**Defect groups found: 6. Defect-free checks: 0. All six were corrected before release QA.**

1. The first File 00 hardening pass still retained a legacy `sabri_membership_claims` fallback; this could undermine the current v2 fail-closed contract.
2. Admin/health/retry capabilities were checked through WordPress capabilities without always re-reading current File 00 eligibility on the same action.
3. Renderer/deep-link routes checked login but did not independently revalidate current account eligibility at presentation/open time.
4. Some filters/card-menu/settings controls were 38–40px rather than the recovered-directive 44px touch-target floor.
5. QA still had the older 19 assertions and no independent four-plan regression audit, allowing the earlier critical device-ownership defect to escape 2.0.0.
6. Release metadata, build script, package audit and workflow still targeted 2.0.0 and therefore could not provide exact 2.1.0 package evidence.

**Corrections:** v2-only File 00 claim contract, live privilege revalidation, renderer/open-route revalidation, 44px RTL/mobile controls, expanded deterministic assertions + `four-plan-audit.sh`, 2.1.0 release/package workflow.

## Four-round result before exact-head CI

| Round | Defect groups found | Corrected in the same review cycle | Unresolved before next round |
|---|---:|---:|---:|
| 1 | 5 | 5 | 0 |
| 2 | 4 | 4 | 0 |
| 3 | 6 | 6 | 0 |
| 4 | 6 | 6 | 0 |
| **Four-round total** | **21** | **21** | **0 known** |

All four mandated reviews found defects. No mandated review round was defect-free. This is evidence of iterative review, not a claim of absolute infallibility.

## Release-preflight correction after the four mandated rounds

The subsequent exact-release preflight found **1 additional defect group**: the scoped-subscription API accepted partial-segment wildcards such as `Publishing.Correction*`, while the runtime matcher intentionally supported only `*`, exact event facts, or namespace wildcards ending in `.*`. Such a value could therefore be stored but never match.

This was corrected before promotion by introducing one shared `valid_event_pattern()` grammar, rejecting unsupported partial wildcards, and adding deterministic regression assertions for global wildcard, exact fact, namespace wildcard and rejected partial wildcard.

**Total known defect groups across the four reviews plus release preflight: 22. Corrected: 22. Known unresolved at this point: 0.** Any later CI/staging defect reopens this ledger and must be corrected before promotion.

## Top-20 File 19 status after corrections

`CV-097` unified center; `CV-098` channel preferences/provider honesty; `CV-099` scoped subscriptions; `CV-100` digest/quiet-hours; `CV-101` appointment alert profile; `CV-102` correction/retraction profile; `CV-103` essential security profile with canonical producer ownership; `CV-104` opt-in/capped creator bulletin + report; `CV-105` truthful delivery ledger/retry; and `CV-106` fatigue/value metrics are represented in code and executable release audits.

The actual domain event producers remain owned by their canonical files. File 19 does not invent appointment, message, publication, marketplace, account-security or clinical state.

## Promotion law

A release can be called **Coded/Packaged/Automated-QA Green** only after exact-head CI passes and the deterministic ZIP/checksum is pinned. It is **not Staging-Accepted** until real WordPress/MySQL activation, upgrade/migration, File 00/08/17/18/20/21 contracts, provider credentials, browsers/devices, accessibility, backup/restore and rollback are accepted. Live deployment and operational status remain later, separate gates.
