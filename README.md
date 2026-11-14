# File 19 — Sabri Unified Notifications and Alerts

Canonical intelligent notification and attention infrastructure for the **Sabri Social Homeopathy Platform**.

## Current 3.0.9 V12.3 Web Push and File 20 PWA coexistence candidate — NOT LIVE

- File 19's PushManager subscription now uses the isolated /sabri-notifications-push/ scope instead of the root '/' scope owned by File 20's PWA, with a WordPress home_url-aware scope from script configuration.
- Node test simulates push registration, verifies scope, device registration, and localized feedback. It was introduced red against 3.0.8 root scope source before correction.
- Browser registrations left over from earlier deployed versions must be inventoried on isolated staging; no silent unregister or live migration is attempted.
- The historical reproducible 3.0.8 ZIP SHA-256 is b2611522c0dcbc1d081dd83e4a051cabc06eaef5be7af675a0ee40865329dc41. New source requires a separate 3.0.9 checksum and all S0–S11 release gates.

## Historical 3.0.8 V12.3 source candidate — NOT LIVE

- New source-side localization of dynamic notification labels, same-origin safe links and V12.3 primary-green fallback.
- Browser-side regression covers localized controls and invalid/external links.
- 3.0.7 remains an immutable historical release; 3.0.8 has a separate candidate hash.
- S0 deployed-state evidence, real providers, companion integrations, staging and Live acceptance remain unverified.

## Historical 3.0.7 source containment and registry hardening

- Runtime / schema: **3.0.7 / 3.0.2**. This remains source truth only; no Live version or DB status has been confirmed.
- File 20/File 24/local Safe Mode containment is non-overridable by an ordinary File 19 consumer filter. Consumer hooks may restrict but never re-authorize a denied high-risk action.
- File 01 readiness now requires exact canonical module manifest and trusted route metadata (path, status, destination, layout and redirects), rather than merely an active module and active route keys.
- Added independently executable positive/negative tests covering all affected states. This 3.0.7 source candidate is not staging or production acceptance.

## Historical 3.0.6 source corrections

- File number: **19**
- Runtime / schema: **3.0.6 / 3.0.2**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Deterministic package candidate: `19-sabri-unified-notifications-3.0.6.zip`
- Frozen checksum is established only after the final exact-head CI run; staging/live/operational states remain separate.

File 19 remains the sole notification projection, preferences, orchestration, delivery, history and notification-intelligence owner. Domain truth remains with the native owner files. Current cross-file contracts are: File 00 identity/eligibility, File 01 module/contract registry plus reliable platform event backbone, File 02 fresh authentication step-up, File 20 single shell surface and Safe Mode, File 24 assurance/containment, File 26 saved-search ownership, and File 25 visual-token ownership.

## 3.0.6 File 01 event-contract closure

The 3.0.6 repository candidate closes the remaining File 19 contract gap found by the fresh twenty-round cross-file review. File 19 now registers its API/event contract surfaces with File 01, declares File 01 as a required dependency, and publishes the four plan-mandated notification facts through the File 01 durable event backbone: `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1` and `NotificationPreferenceChanged.v1`. Notification creation/read and preference changes are transaction-bound; delivery-failure publication is retried by reconciliation if the event backbone was temporarily unavailable after the delivery-state change. The manifest now also includes the planned `MarkNotification.v1` command and `GetNotificationPreferences.v1` query.

This remains repository/source evidence only. It does not prove staging or Live deployment parity.

## TextBee SMS provider bridge

3.0.6 retains the first-party TextBee bridge and adds completeness/security hardening; 3.0.1 originally added the TextBee bridge to File 19's existing provider-neutral SMS contract. It uses the current account-level TextBee endpoint and keeps credentials outside WordPress data.

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

The 3.0.6 corrective candidate closes the fresh cross-file twenty-round audit findings, in addition to the earlier completeness fixes: canonical re-authorization for privileged advanced REST, executable automation rules, saved-search owner binding, runtime shadow/canary evaluation, end-to-end trace stages, optimistic per-device concurrency, schema-neutral version bookkeeping, routed-provider identity/cost/rate accounting, missing-state reconciliation, durable authenticated mutation idempotency, and signed/timestamped/replay-protected provider webhooks. Repository CI/package evidence is tracked separately from staging and Live. **Staging-Accepted**, **Live-Deployed** and **Operational** are not implied by source completion. Exact deployed code remains unverified until deployment parity is checked.

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


## 3.0.6 corrective cross-file hardening

The retained 3.0.2 schema foundation added two bounded operational tables; the 3.0.6 runtime keeps DB schema 3.0.2 and corrects cross-file ownership/contracts without an unnecessary schema bump: `sun_request_idempotency` for short-lived encrypted mutation replay responses and `sun_webhook_receipts` for provider webhook replay prevention. Delivery records now retain both the actual provider and canonical route-provider identity so rate caps and known-cost accounting use the route that was actually selected. Reconciliation repairs missing derived attention-state rows and expires short-lived replay evidence. Normal uninstall remains non-destructive.
