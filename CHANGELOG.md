# Changelog

## 2.4.0 — 2026-08-07

- Ran a new sequential forty-round review/fix cycle against the governing central corpus and the File 19 dedicated master plan.
- Made validated event envelopes immutable after security validation; added payload depth/node bounds, strict expiry rejection and database-safe metadata limits.
- Bound explicitly requested templates to the current event type and made policy selection deterministic by specificity/newness while preserving non-downgradable priority/sensitivity.
- Removed File 19-local positive identity-authority mutation; File 00 remains the sole positive membership/Founder authority.
- Prevented cross-user push-token takeover while preserving legitimate multi-device registrations.
- Hardened REST event intake with request limits, JSON depth limits, anonymous abuse isolation and post-signature producer rate limits.
- Made elapsed notifications non-readable/non-mutable/non-deliverable before background expiry reconciliation; added terminal expired delivery evidence.
- Added fail-closed click-time deep-link authorization and fail-closed handling for encrypted notification metadata corruption.
- Serialized audit-chain writes and recursively minimized/redacted audit context.
- Completed paginated privacy export of delivery history and strengthened subscription concurrency semantics.
- Expanded CV-106 wellbeing/fatigue signals and privacy-minimized complaint accounting.
- Made dead-letter retry atomic across delivery and dead-letter transitions.
- Hardened push/SMS provider corruption/dependency handling and truthful unsubscribe failure behavior.
- Corrected front-end live-region targeting, 44px interactive targets and locale-direction inheritance.
- Expanded System Check evidence and cleaned option-backed locks during uninstall.
- Promoted runtime/schema, deterministic packaging and PHP 8.3/8.4 CI to 2.4.0.

## 2.3.0 — 2026-08-07

- Added explicit Top-20 CV-097–CV-106 notification contracts.
- Added granular person/topic/community/course/event/doctor/channel subscriptions with immediate/daily/weekly frequencies.
- Added appointment/correction/security/creator-bulletin event catalog and wellbeing metrics.
- Hardened policy-owned category, sensitivity/priority floors and current File 00 governance claims.

## 2.2.0 — 2026-08-07

- Earlier forty-round baseline for central-plan precedence, activation locking, canonical File 00 claims, producer owner/schema binding, exact-origin links, Safe Mode, provider circuits, bulk governance, privacy lifecycle and canonical packaging.

## 2.0.0 — 2026-08-07

- Replaced the README-only preservation repository with a complete coding candidate.
- Added canonical event intake, one notification center/File 20 bell, preferences, digests, delivery adapters, retries/dead letters, devices, privacy lifecycle, health diagnostics and deterministic QA.

## 1.0.0 — historical baseline

The former repository preserved only metadata about an original package. Its package source was not present in the Git tree and is not treated as verified implementation evidence for current releases.
