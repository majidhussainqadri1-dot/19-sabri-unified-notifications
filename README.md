# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Release candidate

- File number: **19**
- Runtime: **2.0.0**
- Plugin folder: `19-unified-notifications`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP: **8.1+**
- WordPress project baseline: **7.0.1 / PHP 8.3** (staging verification required)

This repository implements the File 19 master plan: one in-app center and File 20 bell, versioned domain-event intake, explicit lawful recipients, idempotent fan-out, preferences, quiet hours, digests, email/push/SMS adapters, retry/dead-letter handling, devices, privacy lifecycle, health diagnostics, reconciliation and Founder-approved bounded bulk notices.

## Truth of status

Repository source, deterministic package tooling and automated checks are present. This does **not** claim Hostinger staging acceptance, live provider delivery, live deployment or operational acceptance. Those gates require the real platform, provider credentials, companion modules, backup/restore and Founder acceptance.

## Commands

```bash
php tests/unit.php
bash tests/static-audit.sh
bash tests/package-audit.sh
```

The release artifact is built as `build/19-sabri-unified-notifications-2.0.0.zip`.

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

sun_ingest_domain_event(
    [
        'producer'       => 'file17',
        'owner'          => 'File 17',
        'event_id'       => 'message:123:recipient:45',
        'event_type'     => 'Communication.MessageReceived',
        'schema_version' => '1.0',
        'occurred_at'    => gmdate( DATE_ATOM ),
        'recipients'     => [ [ 'user_id' => 45 ] ],
        'sensitivity'    => 'sensitive',
        'deep_link'      => '/messages/conversation/abc/',
        'data'           => [
            'action_name' => 'New message',
            'summary'     => 'Open the platform to review it securely.',
        ],
    ]
);
```

Domain producers retain their own state/decision truth. File 19 stores only notification projections and delivery evidence.

## Documentation

See [`19-unified-notifications/docs`](19-unified-notifications/docs/) for architecture, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability.
