# File 19 V12.3 — Authorized Hostinger S0 Operator Handoff

Prepared: 2026-11-14. Site: https://www.sabrihomeopathy.com. This document is **not** authorization to change the Live website.

## Goal

Collect the exact installed File 19 artifact and WordPress database state as read-only evidence before any production incident diagnosis, migration, deployment or source patch. Do not collect patient records, contacts, identity documents, message text, notifications, credentials, private options, or provider keys.

## Prerequisites

- An authorized Hostinger operator identifies the actual WordPress root using its own wp-config.php and wp-content, not a guessed directory.
- Confirm Bash, sha256sum and WP-CLI availability. Review the exact GitHub tool at tools/file19-readonly-live-freeze.sh before running.
- WP-CLI --skip-plugins --skip-themes can still load must-use plugins; inspect those for side effects before executing any WordPress bootstrap.
- Prepare a private workspace. Script output belongs in a permission-600 /tmp file, not the publicly served directory.
- Ensure separate, approved existing backup and restore evidence is available. The collector does not create/restore backups.

## Read-only collector command

    bash tools/file19-readonly-live-freeze.sh /ABSOLUTE/VERIFIED/WORDPRESS/ROOT

The placeholder must be replaced with the administrator-verified path. Do not paste raw report contents to a public issue. Review and sanitize before sharing through an approved private channel.

## Required evidence fields

| Field | Sanitized evidence required |
|---|---|
| Collection date | UTC time plus host time zone |
| Installed plugin | File 19 active state, exact installed version and relative plugin path |
| Runtime | Actual web PHP, WordPress core, CLI PHP recorded separately |
| Deployed files | Per-file relative SHA-256 and exact package checksum where possible |
| Companion versions | File 00, 01, 02, 20, 24, 25, 26 installed versions, states, owner contracts |
| DB | Actual sun_ tables/columns/indexes and aggregated row counts; no data rows |
| Options | Sanitized sun_plugin_version and sun_db_version only |
| Migration | Completed/deferred/failed historical migration evidence and version |
| Queue and scheduler | File 19 cron hook names, next due, overdue; never execute jobs |
| Safety | Installed File 20/24/local Safe Mode and provider configuration readiness, no keys |
| Logs | Redacted errors, dated WP/PHP stack frames, no user data |
| Resilience | Existing backup timestamps, verified restore evidence and available rollback |

## Parity and root-cause decision

1. Freeze actual Hostinger deployed File 19 metadata before reading repository code as a diagnostic.
2. If installed build is 3.0.7, independently compare the canonical frozen 3.0.7 archive hash 2a77ffcea0ab1cf5a9794f22171d7af280ceea7a3e9658bd10b5a4ae33cbd107 and each relevant file hash. This ZIP checksum is not automatically the hash of an extracted plugin directory.
3. If installed version differs or schema/option state disagrees, mark DRIFT and halt normal application-code debugging. Conduct Deployment-Parity and migration audit first.
4. Only after the exact installed artifact, DB state and relevant logs are known may a live root cause be attributed. Source 3.0.8 is a candidate, not proof of deployment.
5. S0 acceptance is not S10 release authorization; Staging, rollback, Founder acceptance, controlled deploy and S11 retest remain separate.

## Connector observations

WordPress.com reports site_disconnected. WPVibe currently has no registered site, and an attempted connection returned HTTP 530. This is a connection symptom, not evidence of an origin outage, Cloudflare setup, or a File 19 defect. Never disable WAF or security controls merely to connect a tool.

## Sanitized operator receipt template

- Timestamp UTC:
- Authorized operator role (no personal credentials):
- Verified WordPress root (redact path if sensitive):
- Active File 19 version and installed path:
- Deployed package SHA-256, or reason it is unavailable:
- Actual web PHP / WordPress versions:
- DB version / columns / indexes / migration:
- Related File 00/01/02/20/24/25/26 installed states:
- Cron / Safe Mode / provider readiness:
- Redacted runtime error evidence:
- Backup / restore verification:
- Parity verdict: PASS / DRIFT / UNVERIFIED.
- S0 verdict: ACCEPTED / BLOCKED, reviewer role and timestamp.

Do not share secrets or sensitive operational metadata publicly. See docs/FILE19-S0-HOSTINGER-READONLY-FREEZE.md for the original root-level instructions.
