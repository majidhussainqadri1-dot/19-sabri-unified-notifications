# Requirements Traceability

## Functional requirements

| ID | Requirement | Principal implementation | Automated evidence |
|---|---|---|---|
| F19-FR-001 | Versioned event intake | `SUN_Producer_Registry`, `SUN_Event_Validator`, REST `/events` | unit + static audit |
| F19-FR-002 | Idempotency/deduplication | unique producer-event/notification/delivery keys plus durable authenticated REST mutation idempotency | completeness regression + static/schema audit |
| F19-FR-003 | Explicit recipient resolution | validator rejects role guessing; eligibility via File 00 assertions | unit tests |
| F19-FR-004 | Policy engine | `SUN_Policy_Engine`, versioned policy table | static audit |
| F19-FR-005 | Template registry | `SUN_Template_Engine`, safe variables, locale/channel/version | unit tests |
| F19-FR-006 | Sensitive redaction | external sensitive templates become generic; auth-fetched details | unit tests |
| F19-FR-007 | In-app notification | `SUN_Notification_Service`, center template | syntax/static audit |
| F19-FR-008 | Single bell | File 20 slot and shortcode contract | static audit |
| F19-FR-009 | Notification center | filters, read/unread, archive/unarchive, bounded paging | syntax/static audit |
| F19-FR-010 | Preferences | `SUN_Preferences`, REST and settings UI | syntax/static audit |
| F19-FR-011 | Quiet hours | timezone/DST-aware next-delivery calculation | code review + staging target |
| F19-FR-012 | Digests | immediate/daily/weekly schedule and digest key | code review + staging target |
| F19-FR-013 | Email adapter | verified email, safe body, unsubscribe semantics | static + staging target |
| F19-FR-014 | Browser/push adapter | encrypted devices, generic payload, service worker | JS/static audit |
| F19-FR-015 | SMS adapter | verified phone, opt-in/essential policy, 320-char safe content | static + staging target |
| F19-FR-016 | Queue/retry | lock, bounded batch, exponential backoff/jitter, max attempts | code review + static audit |
| F19-FR-017 | Honest delivery status | accepted/delivered/bounced/failed/suppressed; signed/timestamped/replay-safe provider webhook verification | completeness regression + code review |
| F19-FR-018 | Deep-link safety | same-origin allowlist and protected click-time route | unit tests |
| F19-FR-019 | Bulk/admin notices | explicit IDs, preview, confirmation, bounded batches, cancel flag | static + staging target |
| F19-FR-020 | Observability | health snapshot, queue lag, adapters, sanitized export | static audit |
| F19-FR-021 | Reconciliation | expiry, stale devices, stuck/orphan deliveries, dead-letter retry, missing attention-state repair, replay-evidence cleanup | completeness regression + static + staging target |

## Non-functional requirements

| ID | Gate | Implementation/evidence |
|---|---|---|
| F19-NFR-001 | Object/field authorization | `SUN_Auth`, recipient-scoped queries, privileged advanced endpoints revalidate canonical File 00/step-up state; negative tests still required on staging |
| F19-NFR-002 | Privacy lifecycle | encryption, minimization, export/erasure, retention hold and docs |
| F19-NFR-003 | Reliability | event/delivery dedupe, durable REST mutation idempotency, bounded retries, dead letters, derived-state repair and degraded external channels |
| F19-NFR-004 | Performance | bounded list/queue/bulk/device queries and indexed schema |
| F19-NFR-005 | Accessibility | semantic templates, keyboard controls, responsive/RTL CSS and staging matrix |
| F19-NFR-006 | Observability | privacy-safe health/audit plus event→policy→projection→queue/provider→action trace stages |
| F19-NFR-007 | Migration/rollback | idempotent schema, non-destructive defaults and documented rehearsal |
| F19-NFR-008 | Operability | System Check, reconciliation, queue/dead-letter tools and runbooks |
| F19-NFR-009 | Compatibility | PHP 8.1+ lint matrix; project baseline WP 7.0.1/PHP 8.3 pending staging |
| F19-NFR-010 | Localization | text domain, locale templates, timezone handling, RTL and English-US base |
