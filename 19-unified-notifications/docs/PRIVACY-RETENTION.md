# Privacy and Retention

## Principles

Purpose limitation, data minimization, explicit channels, explicit subscription control, essential-alert transparency, no marketing dark patterns, no donor/payment advantage and no use of notification content as analytics truth.

## User controls

- Per-category/channel enablement.
- Quiet hours in the user's timezone, including DST-safe scheduling.
- Immediate, daily or weekly digest choice where policy allows.
- Explicit person/topic/community/course/event/doctor/channel subscription enablement and frequency.
- Device revocation and signed category/channel unsubscribe.
- WordPress privacy export and erasure callbacks.
- Private notification-fatigue summary based only on aggregate counts; no message/body/profile content is copied into a separate behavioral profile.

## Retention baseline

- Notifications: configurable account policy; expired/deleted content is minimized.
- Preferences and granular subscriptions: account lifetime or until changed/erased.
- Device tokens: until revoked, stale or expired; raw tokens are encrypted and never included in privacy export.
- Delivery evidence/dead letters: operational/audit period, then purge by approved schedule. Erasure pseudonymizes direct recipient/provider identifiers in retained delivery tombstones.
- Templates/policies: version history.
- Audit: retention-bounded assurance evidence; privacy-erasure audit records use a pseudonymous subject hash.

A domain-specific legal/safety hold can delay deletion only through `sun_user_retention_hold`; it must be scoped, documented, reviewable and removed when no longer justified.

External provider payloads contain generic content for sensitive notifications. Detailed content is fetched only from the authenticated in-app notification. The wellbeing/fatigue endpoint returns own-user aggregate counts only and is not a ranking, advertising or surveillance signal.
