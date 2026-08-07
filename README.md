# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Forty-round corrective release

- File number: **19**
- Runtime / schema: **2.2.0 / 2.2.0**
- Repository source folder: `19-unified-notifications`
- Canonical installable package folder: `unified-notifications-19`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum: **7.0**
- Review basis: three central governing plans + File 19 dedicated master plan
- Review cycles: **40 sequential review → fix/retest rounds**

This repository implements the File 19 master plan and later governing amendments: one in-app center and File 20 bell, versioned domain-event intake, explicit lawful recipients, current File 00 verified-account revalidation, idempotent fan-out, preferences, quiet hours, digests, email/push/SMS adapters, retry/dead-letter handling, provider circuit breakers, coordinated safe mode, devices, privacy lifecycle, health diagnostics, reconciliation and Founder-approved bounded bulk notices.

Four-plan governance is machine-readable in `SUN_Four_Plan_Compliance`: the later Top-20 central plan precedes the earlier recovered directives and Definitive Master Plan where they conflict; central green, RTL/right-priority, one complete free tier, no donor/payment advantage, File 20 shell ownership, File 25 visual ownership, File 26 search/discovery ownership and no duplicate domain backend remain enforced.

## Truth of status

Specified and coded status are repository evidence. Packaged and automated-QA-green status must be tied to the exact final 2.2.0 head and deterministic artifact. This does **not** claim Hostinger staging acceptance, live provider delivery, live deployment or operational acceptance. Those gates require the real platform, provider credentials, companion modules, backup/restore, rollback rehearsal and Founder acceptance.

## Commands

```bash
php tests/unit.php
bash tests/static-audit.sh
bash tests/package-audit.sh
```

The release artifact is built as `build/19-sabri-unified-notifications-2.2.0.zip` with canonical top-level folder `unified-notifications-19/`.

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

Domain producers retain their own state/decision truth. File 19 stores only notification projections and delivery evidence. Protected user and governance actions consume current `sabri_membership_claims_v2` identity assertions from File 00 and fail closed if that canonical owner is unavailable.

## Documentation

See [`19-unified-notifications/docs`](19-unified-notifications/docs/) for architecture, the forty-round audit, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability.
