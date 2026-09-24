# File 19 — Sabri Unified Notifications and Alerts

Canonical intelligent notification and attention infrastructure for the **Sabri Social Homeopathy Platform**.

## Current 3.0.4 repository candidate

- File number: **19**
- Runtime / schema: **3.0.4 / 3.0.2**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Deterministic package target: `19-sabri-unified-notifications-3.0.4.zip`
- Package checksum: **pending exact-head CI rebuild**
- Governing basis: consolidated central governing corpus + File 19 dedicated master plan + later Founder-approved Intelligent Attention extension

3.0.4 is the cross-file completion candidate. It moves File 19 to the current File 00 `SMC_Contracts::assertions()` / `smc_assertions_v1` identity contract, consumes File 02 purpose-bound AAL2 authentication assurance for sensitive governance, declares File 00/File 20 as required File 01 dependencies, binds containment to File 20 Safe Mode, publishes the File 24 assurance-matrix state, requires an explicit File 26 saved-search ownership verifier instead of reading another module's private storage, completes the governed bulk-notice form/controller evidence path, and exposes reversible legacy migration dry-run/execute/rollback operations.

File 19 remains the sole notification projection, preferences, orchestration, delivery, history and notification-intelligence owner. Domain truth remains with the native owner files; File 19 never becomes the source of truth for appointments, messages, publishing, marketplace, identity, search or other domain objects.

## TextBee SMS provider bridge

3.0.2 retains the first-party TextBee bridge and adds completeness/security hardening; 3.0.1 originally added the TextBee bridge to File 19's existing provider-neutral SMS contract. It uses the current account-level TextBee endpoint and keeps credentials outside WordPress data.

Production configuration belongs in `wp-config.php` only:

```php
define( 'SUN_TEXTBEE_API_KEY', 'your-secret-api-key' );
```

Optional device/SIM pinning:

```php
define( 'SUN_TEXTBEE_DEVICE_ID', 'your-device-id' );
define( 'SUN_TEXTBEE_SIM_SUBSCRIPTION_ID', 1 );
```

Do not commit production credentials to GitHub or store them in `wp_options`. See `19-unified-notifications/docs/TEXTBEE-SMS.md` for the complete security and Live verification sequence.

TextBee API acceptance is recorded as **accepted**, never automatically as **delivered**. Carrier delivery remains a separate operational fact.

## Intelligent Attention & Notification OS 3.0

3.0 adds, in one coherent architecture: explainable smart priority, priority inbox, citation-bound AI catch-up summaries and notification assistant, semantic grouping keys, snooze, pin, needs-action/done state, global notification search/history, focus modes, attention budgets, essential-only mode, temporary mute, best-time delivery, adaptive source frequency capping, live/updateable and remotely revocable projections, native-owner actionable notifications, verified-source provenance, correction/retraction watch audiences, user automation rules, File 26 saved-search watches, learning/clinic/research trigger families, per-device controls and encrypted handoff state, FCM/APNs-ready native push contracts, opt-in WhatsApp Business/RCS routing, multi-provider failover, cost-aware routing, policy simulator, shadow/canary framework, privacy-minimized trace explorer, synthetic diagnostics and wellbeing metrics whose guardrail is `more-notifications-is-not-a-kpi`.

Existing 2.4 controls remain: one in-app center/File 20 single bell, versioned factual-event intake, File 00 fail-closed identity revalidation, idempotency, preferences, quiet hours, digests, subscriptions, external delivery adapters, retries/dead letters, provider circuits, Safe Mode, privacy export/erasure, reconciliation and bounded Founder-governed bulk notices.

## Truth of status

The 3.0.4 repository candidate closes the latest current-contract and cross-file audit findings while preserving earlier 3.0.x hardening: canonical re-authorization for privileged advanced REST, executable automation rules, saved-search owner binding, runtime shadow/canary evaluation, end-to-end trace stages, optimistic per-device concurrency, schema-neutral version bookkeeping, routed-provider identity/cost/rate accounting, missing-state reconciliation, durable authenticated mutation idempotency, and signed/timestamped/replay-protected provider webhooks. Repository CI/package evidence is tracked separately from staging and Live. **Staging-Accepted**, **Live-Deployed** and **Operational** are not implied by source completion. Exact deployed code remains unverified until deployment parity is checked.

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

AI is optional and adapter-based. If no approved AI provider is configured, catch-up uses a deterministic summary. Any configured AI summary may cite only notification IDs already authorized for the current user. Domain actions are always re-authorized by their native owner at action time.

See `19-unified-notifications/docs/ADVANCED-ATTENTION-OS-3.0.0.md` for the complete advanced requirement catalogue and implementation map.


## 3.0.x completeness hardening

The 3.0.x line includes two bounded operational tables: `sun_request_idempotency` for short-lived encrypted mutation replay responses and `sun_webhook_receipts` for provider webhook replay prevention. Delivery records now retain both the actual provider and canonical route-provider identity so rate caps and known-cost accounting use the route that was actually selected. Reconciliation repairs missing derived attention-state rows and expires short-lived replay evidence. Normal uninstall remains non-destructive.
