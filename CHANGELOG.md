# Changelog

## 3.0.5 — 2026-09-27 — File 01 event-contract closure

- Added File 01 as the required canonical module/contract-registry and reliable platform-event-backbone dependency.
- Added `SUN_Platform_Events` and wired the four File 19 plan-mandated outbound facts: `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1` and `NotificationPreferenceChanged.v1`.
- Bound notification-created/read and preference-change event publication to the same database transaction where the File 19 state change occurs when File 01 is present.
- Added reconciliation of delivery-failure platform events for temporary event-backbone publication failures.
- Added File 01 API/event schema registration and full manifest-shape parity checks instead of version/state-only comparison.
- Completed the manifest command/query inventory with `MarkNotification.v1` and `GetNotificationPreferences.v1`.
- Hardened single and bulk notification mutations plus preference updates against repeated no-op version churn.
- Added File 01 registry/event-backbone health evidence and expanded completeness/static regression coverage.
- Runtime advances to 3.0.5; database schema remains 3.0.2 because no File 19 table/column change is required.
- Staging and Live deployment remain separate evidence gates.

## 3.0.1 — 2026-09-02 — TextBee SMS provider candidate

- Added a first-party TextBee bridge to the existing provider-neutral `sun_send_sms` contract.
- Added truthful `sun_sms_adapter_configured` readiness and `sun_sms_provider_name = textbee` when the API key is defined.
- Kept TextBee credentials out of WordPress options, logs and repository source; production credentials are read only from `wp-config.php` constants.
- Added optional TextBee device and SIM pinning while defaulting to TextBee's account-level device selection.
- Added fail-closed E.164 validation, TLS-only account-level API transport, normalized provider/network errors and non-leaking error data.
- Preserved TextBee `smsBatchId` receipts and added a clearly prefixed local acceptance receipt only for immediate-dispatch responses that contain success counts but no batch id; this does not claim carrier delivery.
- Added deterministic provider tests, package/static regression gates, secure operator documentation and a deterministic 3.0.1 package target.
- Runtime is 3.0.1; database schema remains 3.0.0 because this integration adds no tables or columns.

## 3.0.0 — 2026-08-08 — candidate

- Promoted File 19 from a notification-delivery subsystem to an **Intelligent Attention & Notification Operating System**, while preserving native domain ownership.
- Added explainable attention scoring, Priority Inbox, Focus Modes, Essential-only mode, temporary mute, best-time scheduling, hourly/daily attention budgets and adaptive source frequency capping.
- Added pin/unpin, snooze, Needs Action/Done states, global notification search, history vault and cross-device state-backed attention metadata.
- Added citation-bound AI catch-up summaries and a read-only notification assistant with deterministic non-AI fallback.
- Added semantic grouping keys for smart bundling, verified-source provenance and “Why did I get this / Why important?” evidence.
- Added live/updateable notification projections, automatic expiry continuity and source-event revocation/redaction.
- Added native-owner actionable-notification contracts; File 19 never grants domain authority.
- Added user automation rules, File 26 saved-search/watch contracts, correction/retraction audiences, and learning/clinic/research trigger families.
- Added per-device category/channel/focus preferences and encrypted handoff data.
- Added provider-routing tables and adapters for Web Push/FCM/APNs readiness plus explicit opt-in WhatsApp Business and RCS channels.
- Added multi-provider failover, rate caps, cost-aware routing and truthful unknown-cost handling.
- Added policy simulator, shadow and deterministic canary framework.
- Added privacy-minimized end-to-end trace spans and synthetic non-delivery diagnostics.
- Extended privacy export/erasure, System Check and guarded destructive uninstall to all advanced attention tables.
- Preserved the wellbeing law: **more notifications is not a KPI**; useful action completion, critical-alert success, suppression, complaint/fatigue and delivery reliability are preferred signals.

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
- Added explicit Top-20 CV-097–CV-106 notification contracts, granular subscriptions, semantic event catalog and wellbeing metrics.

## 2.2.0 — 2026-08-07
- Earlier forty-round baseline for central-plan precedence, activation locking, canonical File 00 claims, producer binding, exact-origin links, Safe Mode, provider circuits, bulk governance, privacy lifecycle and canonical packaging.

## 2.0.0 — 2026-08-07
- Replaced the README-only preservation repository with a complete coding candidate and canonical notification runtime.

## 1.0.0 — historical baseline
- Historical preservation baseline only.
