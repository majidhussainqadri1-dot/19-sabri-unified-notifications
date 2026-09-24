# File 19 — Sabri Unified Notifications and Alerts

Canonical intelligent notification and attention infrastructure for the **Sabri Social Homeopathy Platform**.

## Current 3.1.0 repository candidate

- File number: **19**
- Runtime / schema: **3.1.0 / 3.1.0**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Deterministic package: `19-sabri-unified-notifications-3.1.0.zip`
- Package SHA-256: **PENDING until the exact 3.1.0 candidate passes deterministic CI rebuild**
- 3.1.0 source is being reviewed on a corrective branch; main/staging/live are not implied by this candidate.
- Governing basis: consolidated central governing corpus + File 19 dedicated master plan + later Founder-approved Intelligent Attention extension

File 19 remains the sole notification projection, preferences, orchestration, delivery, history and notification-intelligence owner. Domain truth remains with the native owner files; File 19 never becomes the source of truth for appointments, messages, publishing, marketplace, identity, search or other domain objects.

## TextBee SMS provider bridge

3.1.0 retains the first-party TextBee bridge and adds completeness/security hardening; 3.0.1 originally added the TextBee bridge to File 19's existing provider-neutral SMS contract. It uses the current account-level TextBee endpoint and keeps credentials outside WordPress data.

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

The 3.1.0 candidate extends the earlier audit closure with the fresh cross-plan findings: File 01 route registry synchronization, File 25 token consumption, fail-closed File 26 saved-search ownership, owner-attested live/revocation mutations, Search/Research/Knowledge/Analytics policies, explicit notification DTO allowlists, minimized and retained event payloads, expired-attention guards, digest overflow/receipt reconciliation, health/region routing, invalid push-token revocation, bulk reason/reversal evidence, expanded System Check and a governed legacy-migration audit framework. Repository CI/package evidence is tracked separately from staging and Live. **Staging-Accepted**, **Live-Deployed** and **Operational** are not implied by source completion. Exact deployed code remains unverified until deployment parity is checked.

## Public integration examples

```php
sun_register_notification_producer('file17', [
    'owner' => 'File 17',
    'event_types' => [ 'Communication.*' ],
    'schema_versions' => [ '1.0' ],
    'secret_callback' => static fn () => getenv('FILE17_NOTIFICATION_SECRET'),
    'mutation_callback' => static fn ($operation, $context) => file17_authorize_notification_mutation($operation, $context),
    'data_schemas' => [ 'Communication.*' => [ 'thread_label' ] ],
]);

sun_register_notification_saved_search($user_id, 'file26', $search_id, 'Diabetes research', 'daily');
sun_update_live_notification($notification_public_id, ['summary' => 'Processing 70%'], 'file17');
sun_revoke_notifications_by_source('file21', $event_id, 'source_retracted');
```

AI is optional and adapter-based. If no approved AI provider is configured, catch-up uses a deterministic summary. Any configured AI summary may cite only notification IDs already authorized for the current user. Domain actions are always re-authorized by their native owner at action time.

See `19-unified-notifications/docs/ADVANCED-ATTENTION-OS-3.0.0.md` for the complete advanced requirement catalogue and implementation map.


## 3.1.0 cross-plan completion

The 3.1.0 / DB 3.1.0 candidate retains the two bounded replay-safety tables and adds cross-plan governance/schema changes. The earlier 3.0.2 release added two bounded operational tables: `sun_request_idempotency` for short-lived encrypted mutation replay responses and `sun_webhook_receipts` for provider webhook replay prevention. Delivery records now retain both the actual provider and canonical route-provider identity so rate caps and known-cost accounting use the route that was actually selected. Reconciliation repairs missing derived attention-state rows and expires short-lived replay evidence. Normal uninstall remains non-destructive.
