# Architecture

## Canonical boundary

File 19 owns notification projections, preferences, device registrations, delivery attempts, retry/dead-letter state, templates, policies and notification audit. It does not own messages, appointments, publication decisions, marketplace deals or security incidents. Domain owners emit factual past-tense events with explicit lawful recipients.

## Layers

1. **Contracts:** producer registry, versioned event envelope and File 00 identity assertions.
2. **Application:** validator, policy engine, template engine, notification fan-out, delivery queue, bulk preview/confirmation and reconciliation.
3. **Data:** ten normalized MySQL tables; encrypted restricted payloads; explicit public DTOs.
4. **Adapters:** email, web/push and SMS. Provider IDs are evidence, never notification truth.
5. **Experience:** one File 20 bell, one center, preferences, admin diagnostics and safe routes.
6. **Assurance:** native authorization, audit chain, health metrics and File 24 evidence integration.

## Invariants

- One producer event plus recipient/policy/template/version yields at most one notification.
- One notification/channel/digest bucket yields at most one delivery attempt record.
- External providers cannot change domain state.
- Every protected read/write revalidates the current user and object ownership.
- Sensitive details are fetched in-app after authentication; lock-screen/email/SMS text is minimized.
- No direct companion-module writes to File 19 tables.
- Queue processing is bounded, lock-protected and retry/dead-letter aware.
