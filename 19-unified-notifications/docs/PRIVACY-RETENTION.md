# Privacy and Retention

## Principles

Purpose limitation, data minimization, explicit channels, user choice, essential-alert transparency, no marketing dark patterns and no use of notification content as analytics truth.

## User controls

- Per-category/channel enablement.
- Quiet hours in the user's timezone, including DST-safe scheduling.
- Immediate, daily or weekly digest choice where policy allows.
- Device revocation and signed category/channel unsubscribe.
- WordPress privacy export and erasure callbacks.

## Retention baseline

- Notifications: configurable account policy; expired/deleted content is minimized.
- Preferences: account lifetime or until changed/erased.
- Device tokens: until revoked, stale or expired.
- Delivery evidence/dead letters: operational/audit period, then purge by approved schedule.
- Templates/policies: version history.
- Audit: retention-bounded assurance evidence.

A domain-specific legal/safety hold can delay deletion only through `sun_user_retention_hold`; it must be scoped, documented, reviewable and removed when no longer justified.

External provider payloads contain generic content for sensitive notifications. Detailed content is fetched only from the authenticated in-app notification.
