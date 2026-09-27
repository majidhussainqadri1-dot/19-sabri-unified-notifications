# Contracts

## Event envelope `sun.event.v1`

Required fields:

- `producer`: registered stable producer key.
- `owner`: domain owner label.
- `event_id`: immutable producer-scoped identifier.
- `event_type`: past-tense domain fact such as `Communication.MessageReceived`.
- `schema_version`: semantic numeric version.
- `occurred_at`: ISO-8601 date within the accepted replay/history window.
- `recipients`: explicit canonical user IDs; role-wide guessing is rejected.

Optional fields: actor/subject references, trace ID, category, priority, sensitivity, template key, safe same-origin deep link, expiry and minimized template data.

## PHP API

- `sun_register_notification_producer( $key, $contract )`
- `sun_ingest_domain_event( $event )`
- `sun_get_unread_count( $user_id )`
- `sun_render_notification_bell()`

## REST API

Namespace: `sabri-notifications/v1`.

- `GET /notifications`
- `GET|POST /notifications/{public_id}`
- `POST /notifications/bulk`
- `GET /unread-count`
- `GET|POST /preferences`
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

## File 01 registry and outbound event backbone

File 01 is the canonical registry and reliable platform-event backbone. File 19 registers two versioned registry contracts when the authorized File 01 governance surface is available:

- `sun.notifications.api@1.0.0`: File 19 REST namespace, commands, queries, authorization and privacy boundaries.
- `sun.notifications.events@1.0.0`: the four File 19-owned outbound notification facts and their privacy/retention semantics.

File 19 publishes only notification facts, never native domain truth:

| Event | Aggregate | Privacy class | Minimum payload |
|---|---|---|---|
| `NotificationCreated.v1` | notification | internal | notification public ID, category, priority, producer, source event ID, status |
| `NotificationRead.v1` | notification | internal | notification public ID, status |
| `NotificationDeliveryFailed.v1` | notification delivery | restricted | delivery public ID, channel, attempt, terminal flag, error code |
| `NotificationPreferenceChanged.v1` | notification preference | restricted | opaque preference ref, category, channel, enabled, digest frequency, quiet-hours flag, version |

The File 01 event bus supplies the durable outbox, deduplication, retry/dead-letter and at-least-once dispatch semantics. Consumers remain idempotent. File 19 never includes raw notification body, email, phone, device token, clinical detail, message body or provider secret in these cross-file event payloads.

Notification-created/read and preference-change facts are inserted into the File 01 outbox before the surrounding state transaction commits when File 01 is installed. Delivery failure is an external side-effect/result and cannot be rolled back; File 19 therefore records the delivery state first and reconciliation republishes the same idempotent failure fact if the File 01 publish step was temporarily unavailable.

Repository absence of File 01 is tolerated for isolated source tests, but health reports the required dependency as unavailable and staging/release readiness must remain degraded.

