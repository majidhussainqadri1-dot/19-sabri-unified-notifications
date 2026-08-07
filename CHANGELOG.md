# Changelog

## 2.1.0 — 2026-08-07

- Added an executable four-plan compliance constitution covering Definitive v3.0, recovered directives, Top-20/Continuous-Value CV-097–CV-106 and the dedicated File 19 plan.
- Replaced fail-open/local identity assumptions with current File 00 v2 claims for recipient, Founder and privileged-operation authorization.
- Bound every event owner to the registered canonical producer owner and stopped File 19 from impersonating account-security or clinical-safety domain truth.
- Made policy category, priority and sensitivity floors non-downgradable by producers; more-specific policies now take precedence.
- Added bounded event-data depth, credential/secret-field rejection and stricter trace/signature validation.
- Fixed cross-user device-token takeover by making provider/token ownership immutable across users.
- Added explicit click-time deep-link authorization; domain links fail closed unless the canonical owner re-authorizes current access.
- Added granular person/topic/community/course/event/doctor/channel notification subscriptions, scoped daily/weekly frequency and user-visible management.
- Added opt-in/frequency-capped creator bulletins and a user Report Notification signal.
- Added privacy-safe notification-fatigue/value metrics where more notifications is explicitly not a success KPI.
- Extended privacy export/erasure to scoped subscriptions and extended health checks to the new schema and four-plan contract.
- Raised remaining notification controls/inputs to 44px touch targets, reinforced RTL/mobile behavior and retained reduced-motion/forced-colors support.
- Expanded deterministic QA and four-plan regression coverage and prepared a reproducible 2.1.0 package candidate.

## 2.0.0 — 2026-08-07

- Replaced the README-only preservation repository with a complete coding candidate.
- Added canonical event intake with producer registry, HMAC replay protection and strict versioned envelopes.
- Added explicit-recipient authorization, idempotent event/notification/delivery keys and encrypted private payloads.
- Added one notification center, one File 20 bell contract, unread/read/archive controls and safe deep links.
- Added category/channel preferences, quiet hours with timezone/DST handling and daily/weekly digests.
- Added email, provider-neutral web push and optional SMS adapters with honest provider states.
- Added queue locking, exponential backoff, dead letters, provider webhooks and reconciliation.
- Added private device lifecycle, WordPress privacy export/erasure and retention-hold boundary.
- Added privacy-safe health metrics, System Check, safe reconciliation and Founder-approved bulk-notice preview/confirmation.
- Added responsive RTL-aware accessible UI, REST API, routes, admin operations and no-dark-pattern defaults.
- Added deterministic package build, manifests, unit/static/package tests and GitHub Actions.

## 1.0.0 — historical baseline

The former repository preserved only metadata about an original package. Its package source was not present in the Git tree and is not treated as verified implementation evidence for this release.
