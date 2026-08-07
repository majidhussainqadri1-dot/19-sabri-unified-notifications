# Contracts

## Event envelope `sun.event.v1`

Required fields:

- `producer`: registered stable producer key.
- `owner`: domain owner label and must match the producer registry contract.
- `event_id`: immutable producer-scoped identifier.
- `event_type`: factual domain event such as `Communication.MessageReceived`.
- `schema_version`: semantic numeric version.
- `occurred_at`: ISO-8601 date within the accepted replay/history window.
- `recipients`: explicit canonical user IDs; role-wide guessing is rejected.

Optional fields include actor/subject references, trace ID, category, priority, sensitivity, template key, safe same-origin deep link, expiry, minimized template data and `subscription_scope`.

`subscription_scope` shape:

```php
[
    'type'     => 'person|topic|community|course|event|doctor|channel',
    'id'       => 'domain-owned-stable-public-identifier',
    'required' => true|false,
]
```

A required scope is opt-in: no enabled matching subscription means the recipient projection is suppressed. An explicit disabled matching subscription also suppresses an ordinary scoped update. Security, safety and system categories bypass ordinary subscription suppression. File 19 does not invent the source object represented by the scope.

## Top-20 semantic event catalog

The executable `SUN_Four_Plan_Compliance::event_catalog()` identifies the expected native facts for appointment reminders, correction/retraction alerts, security alerts and opt-in creator bulletins. Examples include `Clinic.AppointmentBooked`, `Publishing.RetractionPublished`, `Security.NewDeviceDetected` and `Social.CreatorBulletinPublished`. The native domain owner emits the fact; File 19 only projects/delivers it.

## PHP API

- `sun_register_notification_producer( $key, $contract )`
- `sun_ingest_domain_event( $event )`
- `sun_get_unread_count( $user_id )`
- `sun_render_notification_bell()`
- `sun_set_notification_subscription( $user_id, $type, $id, $enabled, $frequency, $version )`
- `sun_notification_capability_contract()`

## REST API

Namespace: `sabri-notifications/v1`.

- `GET /notifications`
- `GET|POST /notifications/{public_id}`
- `POST /notifications/bulk`
- `GET /unread-count`
- `GET|POST /preferences`
- `GET|POST /subscriptions`
- `DELETE /subscriptions/{public_id}`
- `GET /wellbeing?days=30` — own-user, content-free aggregate only
- `POST /devices`
- `DELETE /devices/{public_id}`
- `POST /events` with `X-SUN-Producer`, `X-SUN-Timestamp`, `X-SUN-Signature`.
- `POST /provider/{channel}/webhook` with provider-specific signature verification filter.
- Restricted health and dead-letter retry endpoints.

## File 20 contract

The sole bell is emitted at `sun_file20_notification_slot`. The `sun_file20_notification_contract` filter publishes version, center and settings destinations. Companion modules must suppress duplicate bells.

## Delivery adapter filters

- `sun_send_push`
- `sun_send_sms`
- `sun_verify_provider_webhook`
- configuration/name filters for each provider.

Secrets must be resolved from environment, secret manager or protected server configuration; never committed.
