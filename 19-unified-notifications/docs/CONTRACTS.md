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
