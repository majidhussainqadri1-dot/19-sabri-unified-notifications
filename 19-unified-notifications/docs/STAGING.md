# Hostinger Staging Acceptance

## Stage 0 — immutable inputs

- Record exact Git commit, ZIP SHA-256, source manifest and WordPress/PHP versions.
- Backup database/files/configuration and prove isolated restore.
- Sanitize and access-control the staging clone.
- Disable production provider credentials and real SMS costs.

## Stage 1 — install/upgrade

- Fresh install and activation without fatal/error log output.
- Upgrade from any recovered historical 1.0.0 package/schema after inventory and dry run.
- Deactivate/reactivate; repeated activation; cron registration; rewrite routes.
- No mutation of users, domain tables, pages or other plugin options.

## Stage 2 — integrations

- File 00 active/suspended/guardian/verified email/phone assertions.
- File 20 one bell, center/settings routes and duplicate-bell suppression.
- File 24 assurance/secrets/incident evidence.
- Representative producers from clinic, publishing, learning, communication, marketplace, media and security.
- Old/new/missing/incompatible provider behavior fails safely.

## Stage 3 — functional workflows

- One, duplicate and concurrent event ingestion.
- 0/1/many explicit recipients; ineligible/suspended/minor state changes.
- All categories/priorities/channels, quiet hours across DST and daily/weekly digests.
- Read/unread/archive/unarchive/bulk controls, unsubscribe and device revoke.
- Queue retry, timeout, dead letter, operator retry, provider accepted/delivered/bounce/failure.
- Bulk preview/confirmation/cancel/partial failure and no role-wide guessing.

## Stage 4 — assurance

- IDOR, CSRF, replay, XSS, SQLi, header injection, open redirect, SSRF-like webhook payloads and rate/cost abuse.
- Cache/noindex/cross-user leakage.
- Keyboard, screen reader, focus, 400% zoom, forced colors, reduced motion and RTL mixed text.
- 10k+ notifications, queue load and p75/p95 measurements.
- Backup/restore, key recovery, rollback and post-rollback smoke tests.

## Release gate

No critical/high defect, no known unresolved required behavior, exact evidence linked to commit/package, Founder visual/functional acceptance and explicit production authorization.
