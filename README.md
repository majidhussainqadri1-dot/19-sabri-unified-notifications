# File 19 — Sabri Unified Notifications and Alerts

Canonical notification infrastructure for the **Sabri Social Homeopathy Platform**.

## Release candidate

- File number: **19**
- Runtime: **2.1.0**
- Core schema: **2.0.0**; scoped-subscription schema: **1.0.0**
- Plugin folder: `19-unified-notifications`
- Text domain: `sabri-unified-notifications`
- REST namespace: `sabri-notifications/v1`
- PHP: **8.1+**
- WordPress project baseline: **7.0.1 / PHP 8.3** (staging verification required)

This repository implements the File 19 master plan plus the current four-plan governing corpus: one in-app center and File 20 bell, versioned canonical domain-event intake, explicit lawful recipients, idempotent fan-out, channel/category preferences, person/topic/community/course/event/doctor/channel subscriptions, quiet hours, digests, email/push/SMS adapters, retry/dead-letter handling, devices, click-time deep-link authorization, privacy lifecycle, fatigue/value metrics, health diagnostics, reconciliation and Founder-approved bounded bulk notices.

## Truth of status

Repository source, deterministic package tooling and automated checks are present. This does **not** claim Hostinger staging acceptance, real provider delivery, live deployment or operational acceptance. Those gates require the real platform, current File 00 claims, companion module contracts, provider credentials, browsers/devices, backup/restore, rollback rehearsal and Founder acceptance.

## Commands

```bash
php tests/unit.php
bash tests/four-plan-audit.sh
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

sun_ingest_domain_event(
    [
        'producer'       => 'file17',
        'owner'          => 'File 17',
        'event_id'       => 'message:123:recipient:45',
        'event_type'     => 'Communication.MessageReceived',
        'schema_version' => '1.0',
        'occurred_at'    => gmdate( DATE_ATOM ),
        'recipients'     => [ [ 'user_id' => 45 ] ],
        'subscription_scope' => [ 'type' => 'person', 'id' => 'doctor-123' ],
        'sensitivity'    => 'sensitive',
        'deep_link'      => '/messages/conversation/abc/',
        'data'           => [
            'action_name' => 'New message',
            'summary'     => 'Open the platform to review it securely.',
        ],
    ]
);
```

A same-origin URL is **not** an authorization grant. The canonical domain owner must re-authorize current access when a notification is opened:

```php
add_filter(
    'sun_authorize_notification_deep_link',
    static function ( $allowed, $url, $notification, $user_id ) {
        if ( 'file17' !== $notification['producer'] ) {
            return $allowed;
        }
        return my_file17_current_user_can_open_url( $user_id, $url );
    },
    10,
    4
);
```

Domain modules can expose contextual subscribe controls using `sun_update_notification_subscription()` or the authenticated `/subscriptions` REST contract. Domain producers retain their own state/decision truth; File 19 stores only notification preferences/projections and delivery evidence.

## Documentation

See [`19-unified-notifications/docs`](19-unified-notifications/docs/) for architecture, contracts, data dictionary, security, privacy, migration, rollback, operations, staging and traceability. The four-review defect ledger and four-plan compliance record are maintained there as release evidence.
