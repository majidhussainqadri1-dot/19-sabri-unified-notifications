# File 19 — Sabri Unified Notifications and Alerts

Canonical intelligent notification and attention infrastructure for the **Sabri Social Homeopathy Platform**.

## Current 3.0.1 corrective release candidate

- File number: **19**
- Runtime / schema: **3.0.1 / 3.0.0**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Deterministic package: `19-sabri-unified-notifications-3.0.1.zip`
- Frozen SHA-256: `577b7e635f8b45520bce4861ee327b71a8d85283a6dc22e379cf1a5955c77073`
- Ten-round corrective QA branch evidence: run `31255205117` — success on PHP 8.3 and 8.4 before checksum-freeze commit; exact frozen-checksum verification is required again on the final candidate head.
- Governing basis: consolidated central governing corpus + File 19 dedicated master plan + Founder-approved Intelligent Attention 3.0 extension.

File 19 remains the sole notification projection, preferences, orchestration, delivery, history and notification-intelligence owner. Domain truth remains with the native owner files; File 19 never becomes the source of truth for appointments, messages, publishing, marketplace, identity, search or other domain objects.

## Intelligent Attention & Notification OS 3.0

The 3.0 requirement catalogue adds, in one coherent architecture: explainable smart priority, Priority Inbox, citation-bound AI catch-up summaries and notification assistant, semantic grouping keys, snooze, pin, needs-action/done state, global notification search/history, focus modes, attention budgets, essential-only mode, temporary mute, best-time delivery, adaptive source frequency capping, live/updateable and remotely revocable projections, native-owner actionable notifications, verified-source provenance, correction/retraction watch audiences, user automation rules, File 26 saved-search watches, learning/clinic/research trigger families, per-device controls and encrypted handoff state, FCM/APNs-ready native push contracts, opt-in WhatsApp Business/RCS routing, multi-provider failover, cost-aware routing, policy simulator, shadow/canary framework, privacy-minimized trace explorer, synthetic diagnostics and wellbeing metrics whose guardrail is `more-notifications-is-not-a-kpi`.

Runtime **3.0.1** is the ten-round corrective implementation of that unchanged 3.0 catalogue. It fixes partial profile/device state preservation, per-device optimistic concurrency, provider-health routing order, privileged routing-cost evidence, strict rejection of hallucinated AI citation IDs, saved-search owner binding, automation partial-update/input safety, complete paginated advanced privacy export, and core read/archive controls after dynamic inbox rendering.

Existing 2.4 controls remain: one in-app center/File 20 single bell, versioned factual-event intake, File 00 fail-closed identity revalidation, idempotency, preferences, quiet hours, digests, subscriptions, external delivery adapters, retries/dead letters, provider circuits, Safe Mode, privacy export/erasure, reconciliation and bounded Founder-governed bulk notices.

## Truth of status

The 3.0.1 candidate may be called **Specified / Coded / Packaged / Automated-QA Green** only when the exact final head passes the frozen-checksum workflow. Hostinger **Staging-Accepted**, **Live-Deployed** and **Operational** remain separate real-environment gates requiring companion contracts, configured providers, browser/device/accessibility tests, load/security testing, backup/restore, rollback rehearsal, privacy acceptance and Founder approval.

## Public integration examples

```php
sun_register_notification_producer('file17', [
    'owner' => 'File 17',
    'event_types' => [ 'Communication.*' ],
    'schema_versions' => [ '1.0' ],
    'secret_callback' => static fn () => getenv('FILE17_NOTIFICATION_SECRET'),
]);

sun_register_notification_saved_search($user_id, 'file26', $search_id, 'Diabetes research', 'daily');
sun_update_live_notification($notification_public_id, ['summary' => 'Processing 70%']);
sun_revoke_notifications_by_source('file21', $event_id, 'source_retracted');
```

AI is optional and adapter-based. If no approved AI provider is configured, catch-up uses a deterministic summary. Configured AI output containing any citation outside the current authorized notification result set is rejected and falls back deterministically. Domain actions are always re-authorized by their native owner at action time.

See `19-unified-notifications/docs/ADVANCED-ATTENTION-OS-3.0.0.md` for the complete advanced requirement catalogue and implementation map, and `TEN-ROUND-REAUDIT-2026-08-08.md` for the corrective audit register.
