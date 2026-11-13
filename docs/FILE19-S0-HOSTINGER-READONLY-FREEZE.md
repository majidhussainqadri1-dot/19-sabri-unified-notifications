# File 19 — S0 Hostinger read-only Live Reality Freeze

**Date:** 2026-11-13. **Purpose:** collect *read-only evidence* about the exact installed File 19 artifact and database before any live defect diagnosis, repository patch, migration or deployment. This procedure does not claim that Jetpack connectivity or hosting is broken.

## Preconditions

- Only an **authorized Hostinger / WordPress administrator** should run the script from an authenticated shell.
- Verify the exact file path and contents from the File 19 official repository. Do not run a copied or modified script from an untrusted source.
- Have the **absolute path to the WordPress root**, with `wp-config.php` and `wp-content`. Never use a guessed root or run on another person's site.
- Ensure `wp` (WP-CLI), Bash and `sha256sum` are available. The script issues only read-oriented commands but normal WordPress bootstrapping can load **must-use plugins**; the owner should understand these hooks before running.
- Output is created via `mktemp` with permissions `600` under `/tmp`, never under the public website directory. It contains server metadata and belongs only in a private administrator workspace.

## Operator invocation

```sh
bash tools/file19-readonly-live-freeze.sh /absolute/path/to/wordpress
```

The script outputs only the private file path. It does not upload the file anywhere, connect any external service, send any notification, run a migration, update an option, change plugin state or exercise user/clinical endpoints.

## Collected evidence and limitations

| Observed area | Collected | Not inferred |
|---|---|---|
| WordPress/PHP | WP-CLI WordPress core version, PHP CLI version | PHP-FPM / web-server PHP runtime or live health |
| Active components | plugin and theme identifiers, statuses and versions | exact installed compatibility or operational readiness |
| File 19 plugin | known slugs, reported installed version/status | active runtime behavior without real tests |
| Schema versions | **only** `sun_plugin_version` and `sun_db_version` options | complete migration history or pending upgrades |
| DB structure | `sun_` table names, column metadata and aggregate row counts only | record contents, encryption state or user data correctness |
| Jobs | File 19 cron names/schedules only; no execution | reliable server cron firing or actual delivery |
| Deployed files | SHA256 digest of each file, `MANIFEST.sha256` self-check when present | parity with GitHub until comparing to approved 3.0.7 artifact |
| Error/log analysis | **not collected** | root cause, server outages and security incidents |

The script deliberately **does not read** email addresses, patient records, messages, sessions, provider secrets, private notification content, full `wp_options` values, raw logs or audit payloads. Operators should inspect and redact the private report before sharing it. Folder/table identifiers and hashes still constitute sensitive operational metadata; publish only to approved recipients.

## Required manual S0 attestation

After collecting the report, the administrator must independently provide:

1. Exact host deployment artifact provenance and checksum, and plugin active path/version; compare with approved archive `19-sabri-unified-notifications-3.0.7.zip` SHA-256 `2a77ffcea0ab1cf5a9794f22171d7af280ceea7a3e9658bd10b5a4ae33cbd107`.
2. Current **web** PHP and WP version, installed related File 00/01/02/20/24/25/26 plugin versions, relevant dependency status and Safe Mode status.
3. Current migration/upgrade state, historical plugin presence, relevant `sun_` options **only in sanitized, approved form**, and supported read-only migration dry-run evidence. Do not print secret option payloads.
4. Sanitized runtime error/log evidence, with timestamps and no patient/credential identifiers.
5. Current backup/restore evidence. No backup or restore action is performed by the script.

Any GitHub main/PR/CI evidence is **source truth** until the deployed file hashes and module versions establish exact parity. If the installed artifact differs, stop normal debugging and perform a **Deployment-Parity Audit** before proposing code changes.

## Current connection blocker

WordPress.com marks `www.sabrihomeopathy.com` as `site_disconnected`. WPVibe's attempted connection returned HTTP 530. Neither identifies the failing network hop or establishes that WordPress is down. Reconnection or approved host-shell evidence is needed. Do **not** disable firewalls, WAF, security plugins or account controls to bypass these symptoms.

**Acceptance gate remains blocked:** Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification Status must be recorded as separate facts. The site is not classified `Resolved` without live retest and post-deployment parity.
