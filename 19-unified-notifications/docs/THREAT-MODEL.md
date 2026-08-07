# Threat Model

| Threat | Control | Residual/acceptance gate |
|---|---|---|
| Forged producer | registry, event-type allowlist, HMAC, replay window | secret rotation/revocation drill |
| Duplicate event/send | unique dedupe identities, transactions, idempotent jobs | concurrency/load test |
| Cross-user IDOR | recipient-scoped queries and current identity recheck | penetration/negative REST tests |
| Sensitive lock-screen leak | sensitivity policy and generic external rendering | provider/device screenshots |
| Open redirect | same-origin canonicalization and protected open route | encoded/Unicode redirect tests |
| Template injection | variable allowlist, stripping and header controls | adversarial template tests |
| Device-token theft | hash + authenticated encryption + private DTO | key/file permission review |
| Queue abuse/cost spike | bounded batch/retries, explicit channels, bulk confirmation and provider kill switch | provider budget alerts |
| Webhook spoof | provider-specific verification filter and status allowlist | real provider signature tests |
| Stale/parallel updates | row version, atomic claim and unique constraints | MySQL race tests |
| Data retention breach | erasure, expiry, token revocation and hold boundary | backup/deletion propagation test |
| Notification as domain truth | source deep link and click-time domain recheck | companion contract tests |
| Dark-pattern spam | default channel limits, preferences, unsubscribe, explicit bulk audience | governance review |
