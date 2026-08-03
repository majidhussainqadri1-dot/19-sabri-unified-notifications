# Changelog

## 1.1.1 — CF-01 privacy-minimal clinical notification contract candidate

- Added versioned `sun.cf01.notification-request` contract `1.0.0`.
- Added a strict producer helper that rejects arbitrary title/body, unknown fields, nested values, direct URLs and bearer-like references.
- Added File 19-owned fixed generic in-app templates and invariant external preview text.
- Added opaque recipient UUID resolution and fail-closed producer authorization adapters.
- Added hashed deduplication identity without storing a clinical object identifier.
- Added routine preference, mute, do-not-disturb and quiet-hours inheritance.
- Limited mandatory delivery to fixed critical access-security templates.
- Added authenticated, recipient-bound, same-origin, non-bearer destination resolution with native CF-01 action-time authorization.
- Added explicit non-authority law: delivery/read/archive status cannot mutate or authorize clinical state.
- Added static and runtime/adversarial contract suites and PHP 8.0/8.3 CI coverage.
- No patient table, clinical content, real-data route, migration, staging or production authorization is included.

## 1.1.0 — Corrective security, privacy, reliability, and integration release

- Prevented push-token ownership transfer between user accounts.
- Encrypted device tokens and provider authorization headers at rest.
- Added masked secret editing and safe secret-preservation behavior.
- Enforced HTTPS/public-network webhook validation, no redirects, SSL verification, and unsafe-URL rejection.
- Replaced destructive Notifications page repair with plugin-owned, non-destructive page management.
- Added automatic database-version upgrades and legacy schema/data migration.
- Added atomic delivery leases, expired-lease recovery, and per-device push deliveries.
- Added sensitivity-aware external previews for email, SMS, push, browser alerts, and toasts.
- Added archive history, restore actions, category counters, and REST no-cache protections.
- Restricted detailed health diagnostics to administrators.
- Added WordPress privacy export and erasure integration.
- Added broader audit logging for notification, delivery, device, preference, archive, and configuration events.
- Corrected the Delivery Log administrator URL.
- Corrected comment notifications so only approved comments notify authors.
- Made digest email honor the global email switch and use protected previews.
- Disabled the floating bell by default for Unified Application Shell compatibility.
- Improved keyboard focus, Escape handling, focus trapping, RTL readiness, touch targets, reduced motion, and approved Sabri Orange branding.
- Increased default polling interval and throttled Marketplace/Network synchronization.
