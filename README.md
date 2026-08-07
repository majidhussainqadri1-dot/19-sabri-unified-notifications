# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Corrective release candidate

- File number: **19**
- Runtime / schema: **2.2.0 / 2.2.0**
- Source plugin folder: `19-unified-notifications/`
- Canonical installable ZIP top folder: `unified-notifications-19/`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP minimum: **8.3**
- WordPress minimum / project baseline: **7.0.1**
- Deterministic plugin ZIP: `build/19-sabri-unified-notifications-2.2.0.zip`
- Exact ZIP SHA-256: **pending exact-head CI evidence; do not infer**

This repository implements the File 19 master plan and later governing amendments: one in-app center and File 20 bell, versioned domain-event intake, producer/owner provenance binding, explicit lawful recipients, current File 00 verified-account revalidation, idempotent fan-out, category/channel preferences, granular subscriptions, quiet hours, immediate/daily/weekly digests, email/push/SMS adapters, retry/dead-letter handling, devices, privacy lifecycle, healthy-use metrics, health diagnostics, reconciliation and Founder-approved bounded bulk notices.

Four-plan governance is machine-readable in `SUN_Four_Plan_Compliance`: central green, RTL/right-priority, one complete free tier, no donor/payment advantage, File 20 shell ownership, File 25 visual ownership, File 26 search/discovery ownership, no duplicate domain backend, and Top-20 notification capabilities CV-097 through CV-106.

## Fresh four-review correction

The 2.1.0 main baseline was reviewed again from scratch. The fresh reviews found and corrected activation/package drift, missing canonical File 00 email/phone/Founder projections, producer-owner provenance gaps, producer category/sensitivity privilege, missing Top-20 granular subscriptions/frequency and healthy-use metrics, and privacy lifecycle gaps for the new subscription/delivery evidence. See `19-unified-notifications/docs/FOUR-PLAN-AUDIT-2026-08-07.md`.

## Truth of status

The 2.2.0 branch is **specified and coded as a corrective candidate**. Packaged/Automated-QA Green is claimed only after the exact final branch head passes CI and publishes the deterministic artifact/checksum. Hostinger staging acceptance, live provider delivery, production deployment and operational acceptance are separate gates requiring the real platform, provider credentials, companion modules, backup/restore, rollback rehearsal and Founder acceptance.

## Commands

```bash
php tests/unit.php
bash tests/static-audit.sh
bash tests/package-audit.sh
```

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

sun_set_notification_subscription(
    get_current_user_id(),
    'doctor',
    'doctor-public-id',
    true,
    'daily'
);
```

Domain producers retain their own state/decision truth. File 19 stores only notification projections, user notification choices and delivery evidence. Protected user actions consume current `sabri_membership_claims_v2` identity assertions from File 00 and fail closed if that canonical owner is unavailable.

## Documentation

See `19-unified-notifications/docs/` for architecture, four-plan audit, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability.
