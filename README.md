# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Corrective release

- File number: **19**
- Runtime / schema: **2.1.0 / 2.1.0**
- Plugin folder: `19-unified-notifications`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Deterministic plugin ZIP SHA-256: `d50203e62d409c12bc609e2dfac01d0dc233a15e06bb0883378eec5730e7629a`

This repository implements the File 19 master plan and the later governing amendments: one in-app center and File 20 bell, versioned domain-event intake, explicit lawful recipients, current File 00 verified-account revalidation, idempotent fan-out, preferences, quiet hours, digests, email/push/SMS adapters, retry/dead-letter handling, devices, privacy lifecycle, health diagnostics, reconciliation and Founder-approved bounded bulk notices.

Four-plan governance is machine-readable in `SUN_Four_Plan_Compliance`: central green, RTL/right-priority, one complete free tier, no donor/payment advantage, File 20 shell ownership, File 25 visual ownership, File 26 search/discovery ownership and no duplicate domain backend.

## Truth of status

Specified, coded, deterministically packaged and automated-QA green are evidenced in GitHub. This does **not** claim Hostinger staging acceptance, live provider delivery, live deployment or operational acceptance. Those gates require the real platform, provider credentials, companion modules, backup/restore, rollback rehearsal and Founder acceptance.

## Commands

```bash
php tests/unit.php
bash tests/static-audit.sh
bash tests/package-audit.sh
```

The release artifact is built as `build/19-sabri-unified-notifications-2.1.0.zip`.

## Public integration

```php
sun_register_notification_producer(
    'file17',
    [
        'owner'       => 'File 17',
        'event_types' => [ 'Communication.*' ],
        'secret_callback' => static fn () => getenv( 'FILE17_NOTIFICATION_SECRET' ),
    ]
);
```

Domain producers retain their own state/decision truth. File 19 stores only notification projections and delivery evidence. Protected user actions consume current `sabri_membership_claims_v2` identity assertions from File 00 and fail closed if that canonical owner is unavailable.

## Documentation

See [`19-unified-notifications/docs`](19-unified-notifications/docs/) for architecture, four-plan audit, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability.
