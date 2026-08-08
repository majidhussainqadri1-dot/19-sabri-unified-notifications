# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Current 2.4.0 release baseline

- File number: **19**
- Runtime / schema: **2.4.0 / 2.4.0**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Review basis: governing central corpus + File 19 dedicated master plan
- Fresh review method: **40 sequential review → immediate fix → retest rounds**
- Deterministic plugin ZIP SHA-256: `f452b54775f7a75707093b550de4bbc618f7dc27c0eb8947c96ea43e53997051`

File 19 implements one in-app notification center and the File 20 single-bell contract, versioned factual-event intake, explicit lawful recipients, current File 00 verified-account revalidation, idempotent notification projection, channel/category preferences, quiet hours, digests, granular subscriptions, external delivery adapters, retries/dead letters, provider circuit breakers, coordinated Safe Mode, device lifecycle, privacy export/erasure, healthy-use metrics, System Check diagnostics, reconciliation and bounded Founder-governed bulk notices.

Top-20 requirements **CV-097 through CV-106** are represented in machine-readable governance and implementation contracts: unified inbox, channel preferences, granular subscriptions, digests, appointment reminders, correction alerts, security alerts, creator bulletins, delivery ledger and notification-fatigue metrics. File 20 remains shell/bell owner, File 25 visual-system owner and File 26 search/discovery owner; notification projections never become domain source of truth.

## 2.4.0 hardening highlights

The fresh corrective cycle found and repaired defects in post-validation mutation, recursive payload complexity, expiry validation, envelope field bounds, template/event binding, policy precedence, cross-user device-token ownership, multi-device behavior, File 00 authority isolation, REST abuse isolation, elapsed-notification delivery, encrypted metadata fail-closed behavior, provider-webhook scoping, click-time deep-link authorization, audit-chain concurrency/minimization, privacy-export pagination, subscription concurrency, CV-106 feedback signals, dead-letter retry atomicity, provider credential corruption, SMS runtime portability, unsubscribe truthfulness, live-region targeting, 44px accessibility targets, locale-direction inheritance, uninstall lock cleanup, System Check completeness and release/CI evidence alignment.

## Truth of status

Repository source, deterministic packaging and automated tests establish **Specified / Coded / Packaged / Automated-QA Green** for the 2.4.0 release baseline. They do **not** establish Hostinger **Staging-Accepted**, **Live-Deployed** or **Operational** status. Those later gates require the real WordPress/Hostinger environment, companion modules, provider credentials, browser/device/accessibility testing, backup/restore, rollback rehearsal, security/privacy acceptance and Founder approval.

## Commands

```bash
php tests/unit.php
bash tests/static-audit.sh
bash tests/package-audit.sh
```

The deterministic release artifact is built as `build/19-sabri-unified-notifications-2.4.0.zip` with canonical top-level folder `unified-notifications-19/`. Root `RELEASE-SHA256.txt` records the frozen plugin-ZIP checksum and CI verifies deterministic reproduction against it.

## Public integration

```php
sun_register_notification_producer(
    'file17',
    [
        'owner'           => 'File 17',
        'event_types'     => [ 'Communication.*' ],
        'schema_versions' => [ '1.0' ],
        'secret_callback' => static fn () => getenv( 'FILE17_NOTIFICATION_SECRET' ),
    ]
);
```

Domain producers retain their own state/decision truth. File 19 stores notification projections and delivery evidence only. Protected user and governance actions consume current `sabri_membership_claims_v2` assertions from File 00 and fail closed if the canonical identity owner is unavailable.

## Documentation

See [`19-unified-notifications/docs`](19-unified-notifications/docs/) for architecture, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability.
