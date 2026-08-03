# File 19 ↔ CF-01 Privacy-Minimal Notification Contract

## Governing identity

- File 19 candidate runtime: `1.1.1`
- Contract: `sun.cf01.notification-request`
- Contract version: `1.0.0`
- Canonical notification and delivery owner: File 19
- Canonical clinical object, consent, relationship and authorization owner: future CF-01
- Status: implemented candidate; Draft, unmerged, unaccepted and not authorized for clinical runtime

## Constitutional boundary

A notification is a derivative delivery record. It is not a clinical record, consent record, follow-up completion, prescription state, treating relationship, authorization grant or bearer credential. Delivery, read, seen, archived, failed or expired status must never complete, mutate, delete or authorize a clinical workflow.

The general `sabri_notify_user()` helper is intentionally not the CF-01 contract. Clinical producers must call:

```php
sun_cf01_request_clinical_notification($request)
```

The strict helper rejects arbitrary notification copy, unknown fields, nested values, clinical content, direct URLs and bearer-like references.

## Allowed producer request

| Field | Law |
|---|---|
| `recipient_platform_uuid` | File 00-owned opaque UUIDv4; resolved server-side through an approved adapter |
| `template_key` | Exact File 19 allowlisted template |
| `action_category` | Exact allowlisted category compatible with the selected template |
| `destination_reference` | Bounded opaque non-authorizing reference; never a URL |
| `urgency` | `low`, `normal`, `high` or `critical` |
| `expires_at` | Bounded future expiry between five minutes and thirty days |
| `mandatory_policy` | `none`, or `security_required` only for approved access-security alerts |
| `correlation_id` | Bounded opaque trace identity |
| `dedupe_key` | Bounded opaque producer idempotency identity |
| `producer_contract` | Versioned producer contract name |
| `producer_version` | Semantic producer version |

Any other field fails closed.

## Prohibited producer and stored content

The contract accepts and stores no patient name, diagnosis, symptom, remedy, potency, dose, clinical note, questionnaire answer, attachment name/content, guardian detail, break-glass reason, signed URL, clinical object ID, session credential, reusable token, password, authorization header or producer-authored title/body.

File 19 stores only fixed generic template text and bounded context flags. The recipient platform UUID is used for server-side resolution but is not copied into notification context.

## External disclosure law

Email subjects, SMS, push previews, browser alerts and lock screens always receive:

```text
Private notification
Sign in to view this protected notification.
```

No template, event, urgency or mandatory status may weaken this external preview.

## In-app templates

In-app titles and bodies are File 19-owned fixed generic strings. They may communicate only a broad protected action class such as private follow-up reminder, protected record update, consent action, export update, file update or access alert. Producers cannot replace or interpolate them.

## Destination law

The persisted notification link is only the File 19 notification center. It never contains the opaque CF-01 destination reference.

The final destination is available only through:

```text
GET /wp-json/sabri-notifications/v1/clinical/{notification_id}/destination
```

At resolution time File 19 requires:

1. current authenticated user;
2. recipient ownership of the notification;
3. current, unexpired File 19 notification state;
4. exact File 19 contract identity;
5. valid opaque destination reference;
6. native CF-01 resolver decision with `authorized=true`;
7. explicit `contains_bearer_authorization=false`;
8. same-origin HTTPS destination with matching port;
9. no URL user information, fragment or bearer-like query key.

Failure returns a generic not-found response and does not disclose notification or object existence.

## Preferences, quiet hours and mandatory exceptions

Routine clinical reminders use the ordinary File 19 preference, category mute, do-not-disturb and quiet-hours paths. A suppressed routine notification is never reported as delivered.

Only the fixed `clinical_access_alert` and `break_glass_access_alert` templates may request `security_required`; they must be critical and remain externally generic. This exception reports protected account/record access and does not include the reason, actor, patient or record.

## Idempotency and delivery

File 19 derives a stable hashed deduplication identity from contract, recipient UUID, producer contract, producer dedupe identity, template and opaque destination reference. Existing File 19 database uniqueness, per-channel/device uniqueness, delivery leases, retries, waiting-provider state and bounded failures remain authoritative.

Provider acceptance is transport evidence only. It grants no clinical authority and does not prove that a user viewed or completed an action.

## Failure behavior

| Condition | Result |
|---|---|
| Unknown field or arbitrary copy | Reject, no notification |
| Invalid recipient UUID or owner resolution | Reject, no disclosure |
| Producer contract unavailable/denied | Fail closed |
| Routine preference/quiet-hours suppression | No false delivery claim |
| Provider unavailable | Existing File 19 `waiting_config`, `retry` or `failed` state |
| Resolver unavailable/denied/stale | Generic destination unavailable |
| Cross-recipient request | Generic destination unavailable |
| External, HTTP or bearer-like destination | Generic destination unavailable |

## Acceptance boundary

This candidate does not authorize clinical runtime, real patient data, File 20 deep-link activation, Hostinger staging or production. Acceptance still requires the File 19 corrective base to be accepted, immutable producer/consumer fixtures, File 20 destination integration, privacy/security review, staging provider tests, migration/rollback evidence and Founder-approved change control.
