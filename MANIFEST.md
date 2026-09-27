# Source Manifest

- Canonical plugin source: `19-unified-notifications/`
- Bootstrap: `19-unified-notifications/19-unified-notifications.php`
- Runtime version: **3.0.5**
- Database version: **3.0.2**
- File 01 contract version declared by File 19: **3.0.2**
- Canonical installable folder: `unified-notifications-19/`
- REST namespace: `sabri-notifications/v1`
- Core ownership: notification projections, preferences/subscriptions, delivery attempts, notification-specific attention state, retries/dead letters, templates/policies, notification audit and notification intelligence.
- Cross-file required dependencies: File 00 identity/eligibility, File 01 module/contract registry and reliable event backbone, File 20 shell notification surface/Safe Mode, File 24 security/privacy containment.
- Cross-file bounded optional integrations: File 02 fresh step-up, File 25 visual tokens, File 26 saved-search ownership.
- File 01 outbound event contracts: `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1`, `NotificationPreferenceChanged.v1`.
- Commands: `IngestNotificationEvent.v1`, `MarkNotification.v1`, `UpdateNotificationPreferences.v1`, `RegisterNotificationDevice.v1`, `RetryNotificationDelivery.v1`.
- Queries: `ListNotifications.v1`, `GetUnreadCount.v1`, `GetNotificationPreferences.v1`, `GetNotificationHealth.v1`.
- Delivery adapters: email, Web Push, SMS; provider-neutral routing includes FCM/APNs readiness and opt-in WhatsApp/RCS routes. TextBee is the first-party SMS provider bridge when configured.
- Security/privacy: File 00 fail-closed identity, File 02 privileged step-up, sensitive external-content minimization, webhook replay protection, request idempotency, privacy export/erasure/retention, File 24 containment.
- Front end: one File 20-owned bell placement, notification center/settings, protected deep-link route, responsive RTL-aware CSS and progressive JS.
- Repository QA: PHP 8.3/8.4 unit, advanced, completeness, provider, static/security/privacy, package and deterministic rebuild checks.
- Release target: deterministic `19-sabri-unified-notifications-3.0.5.zip` plus SHA-256; checksum is frozen only after exact-candidate CI.

Repository/source evidence does not establish staging or Live deployment parity.
