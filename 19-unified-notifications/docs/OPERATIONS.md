# Operations

## Jobs

- `sun_process_delivery_queue`: every minute, bounded and lock-protected.
- `sun_reconcile_notifications`: hourly.
- `sun_expire_notifications`: hourly.
- `sun_process_bulk_jobs`: single-event continuation in batches.

## Signals

Queue depth, oldest queue age, accepted/failed/suppressed/dead-letter counts, provider configuration, cron availability, schema health, encryption probe and reconciliation result. No raw message, patient, deal or identity payload is exposed in metrics.

## Incident controls

- Pause external adapters with provider configuration filters/kill switches.
- Revoke producer secret/registry entry.
- Retain in-app truth while external providers are degraded.
- Retry dead letters only after root-cause confirmation.
- Run safe reconciliation and export health evidence.
- Never claim delivered unless a verified provider callback proves it.

## SLO starting targets

- In-app projection p95 under 5 seconds after accepted event under normal load.
- External queue starts p95 under 60 seconds for immediate notices.
- No duplicate notification/delivery for the same dedupe identity.
- Critical queue lag alert at 15 minutes; general degraded threshold at 60 minutes.
- Zero sensitive content in external subjects/lock-screen payloads for sensitive classes.
