# File 19 — Fresh Four-Plan Audit and Corrective Register

Date: 2026-08-07 (Pakistan Standard Time)
Corrective release: 2.2.0 / schema 2.2.0
Baseline re-reviewed: main 720be418f548d03a1def9a841f8173c2f1e8919b

## Governing corpus and precedence

1. SSH-PMP-2026-v3.0 — platform constitution, canonical ownership, seven status layers, security/privacy and release gates.
2. Sabri Recovered Directives v2.1 — later Founder amendments: central green, RTL/right-priority, one complete free tier, zero donor advantage, continued review/fix, Islamic privacy/minimization and post-GitHub harmonization.
3. Sabri Continuous Value / Top-20 Superset v1.0 — CV-097 through CV-106 notification requirements, healthy-use, one-roof value, accessibility and File 26 discovery ownership.
4. SSH-F19-PLAN-2026-v1.0 — File 19 canonical notification projection/entity, single bell/center, preferences, quiet hours, digests, delivery adapters, retries/dead-letter, device lifecycle, privacy, migration, rollback, QA and DoD.

Precedence applied: latest explicit Founder/safety rule > recovered directives > later Top-20 central constitution > dedicated File 19 plan > verified runtime evidence. Historical paid-tier language is superseded by the current one-complete-free-tier law.

## Fresh review round 1 — Constitution, runtime baseline, packaging and ownership

Defects found on the 2.1.0 main baseline:
- Activation code still admitted WordPress 6.6 and PHP 8.1 even though the plugin header/governing project baseline had moved to WordPress 7.0.1 / PHP 8.3.
- Deterministic ZIP still used `19-unified-notifications/` as its top folder instead of the File 19 plan's canonical package folder `unified-notifications-19/`.
- Release tooling and CI still identified the old 2.1.0 artifact.

Corrections:
- Activation and metadata now require WordPress 7.0.1 and PHP 8.3; health reports baseline drift as degraded.
- Runtime/schema raised to 2.2.0.
- Deterministic release ZIP now stages exactly one `unified-notifications-19/` top-level folder and verifies its embedded manifest.
- Package and CI tests now target 2.2.0 and the canonical package folder.

Round result: defects found and corrected.

## Fresh review round 2 — File 00 identity, authorization and provenance

Defects found:
- `SUN_Auth` consumed File 00 claims but silently discarded `email_verified` and `phone_verified`; therefore channel defaults/adapters could not consistently receive the canonical verification facts.
- It also discarded `founder` and `institutional_role` before `is_founder()` checked them; canonical Founder authorization therefore did not work as documented.
- The configured numeric Founder ID could act as an implicit fallback, potentially outranking File 00.
- A registered producer's event type was checked, but the event's claimed `owner` was not bound to the producer registry contract.

Corrections:
- File 00 email, phone, Founder and institutional-role claims are preserved as minimal current assertions.
- Canonical File 00 Founder identity now works; numeric bootstrap is disabled unless an explicit host filter enables it.
- Protected recipient eligibility remains fail-closed when File 00 is unavailable and rechecks active/verified/suspended/revoked/risk/guardian/consent state.
- Event validation now rejects producer/owner mismatches and normalizes the registered owner as provenance.

Round result: defects found and corrected.

## Fresh review round 3 — Top-20 CV-097–CV-106 value completion

Defects found:
- The prior compliance registry named Top-20 governance generally but did not implement the explicit CV-099 granular subscription family (person/topic/community/course/event/doctor/channel with frequency).
- CV-101 appointment reminders, CV-102 correction/retraction alerts, CV-103 account-security alerts and CV-104 opt-in creator bulletins lacked an explicit semantic contract/catalog in File 19.
- CV-106 notification-fatigue measurement was absent.
- The central settings surface had category/channel preferences but no explicit subscription management.

Corrections:
- Added canonical `subscriptions` table and `SUN_Subscriptions` with optimistic concurrency, explicit scope types, enable/disable and immediate/daily/weekly frequency.
- Added producer-supplied `subscription_scope` validation; opt-in-required scoped events are suppressed unless subscribed. Security/safety/system notices cannot be suppressed by ordinary subscription records.
- Subscription frequency now governs external digest scheduling when present, while the in-app center remains the unified eligible-update history.
- Added Top-20 capability registry CV-097 through CV-106 and semantic event catalog for appointment, correction/retraction, security and creator-bulletin facts. Native domain owners remain the source of truth.
- Added privacy-minimized `SUN_Wellbeing` 30-day aggregates and `/wellbeing` endpoint; the guardrail explicitly states that more notifications are not a KPI.
- Added REST/PHP integration for granular subscriptions and an accessible central settings UI for viewing, updating, adding and removing them.

Round result: defects found and corrected.

## Fresh review round 4 — Adversarial privacy, regression, UI and release evidence

Defects found:
- The new subscription records initially required explicit privacy export/erasure and guarded-uninstall coverage.
- Delivery ledger erasure retained direct recipient IDs after notification content deletion.
- Regression tests did not yet prove canonical Founder/email/phone claims, producer-owner binding, Top-20 capability inventory, scope validation, canonical package folder or healthy-use marker.

Corrections:
- Privacy exporter now includes safe preference, subscription, device metadata and bounded delivery history; raw device tokens and notification ciphertext are not exported.
- Erasure removes devices/preferences/subscriptions, deletes notification content/deep links and pseudonymizes direct recipient IDs/provider IDs in retained delivery tombstones, subject to an approved retention hold.
- Guarded destructive uninstall includes the subscription table; default uninstall remains non-destructive.
- Accessible responsive subscription and wellbeing surfaces were added without creating a second shell or visual-system owner.
- Deterministic unit/static/package suites were expanded to cover the fresh defects and Top-20 requirements.

Round result: defects found and corrected. Final exact-head package/Automated-QA evidence must come from CI after this final source change.

## Truth-status rule

Repository completion and production completion are intentionally separate:
- Specified: complete for known File 19 repository-owned scope after the four-plan reconciliation.
- Coded: 2.2.0 corrective candidate contains all corrections above.
- Packaged / Automated-QA Green: only after exact-head CI proves deterministic build, package integrity and tests.
- Staging-Accepted: requires real WordPress/Hostinger, File 00/20/24/25/26 integrations, provider credentials, role/device/browser/accessibility/load/security/backup/restore/rollback evidence and Founder acceptance.
- Live-Deployed / Operational: not inferred from source or CI.

Zero-known-defect means zero known unresolved repository-owned blocker after the four fresh reviews; it is not a claim of mathematical infallibility and review reopens on new evidence.
