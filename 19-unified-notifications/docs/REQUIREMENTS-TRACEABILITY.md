# Requirements Traceability

Runtime candidate: **2.1.0**. This matrix distinguishes repository/code evidence from real staging/live evidence.

## Dedicated File 19 functional requirements

| ID | Requirement | Principal implementation | Automated evidence |
|---|---|---|---|
| F19-FR-001 | Versioned event intake | `SUN_Producer_Registry`, `SUN_Event_Validator`, REST `/events` | unit + static audit |
| F19-FR-002 | Idempotency/deduplication | unique producer-event, notification and delivery dedupe keys | static + schema audit |
| F19-FR-003 | Explicit recipient resolution | validator rejects role guessing; eligibility from current File 00 v2 assertions | unit + four-plan audit |
| F19-FR-004 | Policy engine | `SUN_Policy_Engine`, versioned policy table, non-downgradable canonical floors | unit + static audit |
| F19-FR-005 | Template registry | `SUN_Template_Engine`, safe variables, locale/channel/version | unit tests |
| F19-FR-006 | Sensitive redaction | external sensitive templates become generic; credential-like event fields rejected | unit tests |
| F19-FR-007 | In-app notification | `SUN_Notification_Service`, center template | syntax/static audit |
| F19-FR-008 | Single bell | File 20 slot and shortcode contract | four-plan/static audit |
| F19-FR-009 | Notification center | filters, read/unread, archive/unarchive/report, bounded paging | syntax/static audit |
| F19-FR-010 | Preferences | `SUN_Preferences`, REST and settings UI | syntax/static audit |
| F19-FR-011 | Quiet hours | timezone/DST-aware next-delivery calculation | code review + staging target |
| F19-FR-012 | Digests | immediate/daily/weekly schedule and digest key | unit/code review + staging target |
| F19-FR-013 | Email adapter | verified email, safe body, unsubscribe semantics | static + staging target |
| F19-FR-014 | Browser/push adapter | encrypted devices, immutable cross-user token ownership, generic payload, service worker | static/security audit |
| F19-FR-015 | SMS adapter | verified phone, opt-in/essential policy, safe content | static + staging target |
| F19-FR-016 | Queue/retry | lock, bounded batch, exponential backoff/jitter, max attempts | code review + static audit |
| F19-FR-017 | Honest delivery status | queued/accepted/delivered/bounced/failed/suppressed/dead-letter evidence | code review + staging target |
| F19-FR-018 | Deep-link safety | same-origin storage plus `sun_authorize_notification_deep_link` click-time owner authorization | unit + four-plan audit |
| F19-FR-019 | Bulk/admin notices | explicit IDs, preview, Founder/capability confirmation, bounded batches, cancel flag | static + staging target |
| F19-FR-020 | Observability | health snapshot, queue lag, adapters, sanitized export and value/fatigue signals | static audit |
| F19-FR-021 | Reconciliation | expiry, stale devices, stuck/orphan deliveries, dead-letter retry | static + staging target |

## Top-20 / Continuous-Value requirements

| ID | Requirement | Principal implementation | Automated evidence |
|---|---|---|---|
| CV-097 | Unified notification inbox/center without duplicating domain truth | `SUN_Notification_Service`; canonical owner binding; one File 20 bell/center | four-plan audit |
| CV-098 | Per-channel delivery choice and provider honesty | `SUN_Preferences`; delivery adapters; delivery ledger | static/package audit |
| CV-099 | Granular person/topic/community/course/event/doctor/channel subscriptions and frequency | `SUN_Subscriptions`; REST `/subscriptions`; settings UI; public integration helpers | unit + four-plan/static audit |
| CV-100 | Daily/weekly digest, quiet hours/timezone, urgent separation | preferences + policy + scoped schedules; mandatory security/safety profile bypasses digest/quiet suppression | unit + four-plan audit |
| CV-101 | Appointment reminder family without clinical lock-screen detail | `Clinic.Appointment*` high+sensitive profile; external sensitive rendering is generic; clinic owner must register producer | unit + four-plan audit; live File 08 contract pending staging |
| CV-102 | Severity-aware correction/retraction alert family | `Publishing.Correction*` and `Learning.Correction*` high-priority profiles; producer hint can escalate but not downgrade | unit + four-plan audit; live owner contract pending staging |
| CV-103 | Essential account-security alerts with trusted recovery route | `Security.*` critical+sensitive+mandatory profile; File 19 cannot self-author account security; File 00 must register producer; click-time owner auth | unit + four-plan audit; live File 00 contract pending staging |
| CV-104 | Opt-in creator bulletins with cap and reporting path | `Social.CreatorBulletin*` requires scoped opt-in, max 1/24h per user+scope, `Report notification` audit signal | unit + four-plan audit |
| CV-105 | Truthful delivery ledger with retry evidence | delivery queue/status/provider webhook/dead letter/reconciliation; no unconfigured provider success claim | static + staging provider target |
| CV-106 | Notification-fatigue/value metric; volume is not KPI | `SUN_Value_Metrics`: aged unread, archives, muted/digest choices, complaints, suppressed/failed signals; aggregate only | four-plan/static audit |

## Cross-plan governing invariants

`SUN_Four_Plan_Compliance::manifest()` makes the four-plan release constitution executable: one bell/center, canonical ownership, current File 00 claims, no paid/donor advantage, truthful provider states, privacy minimization, RTL-first presentation and green primary brand. It is a QA guard, not a new domain data owner.

## Non-functional requirements

| ID | Gate | Implementation/evidence |
|---|---|---|
| F19-NFR-001 | Object/field authorization | current File 00 v2 claims, recipient-scoped queries, live privilege revalidation, domain click-time authorization; real companion negative tests still required on staging |
| F19-NFR-002 | Privacy lifecycle | encryption, minimization, credential-field rejection, subscription export/erasure, retention hold and docs |
| F19-NFR-003 | Reliability | idempotency, bounded retries, dead letters and degraded external channels |
| F19-NFR-004 | Performance | bounded list/queue/bulk/device/subscription queries and indexed schemas |
| F19-NFR-005 | Accessibility | semantic templates, keyboard controls, 44px touch targets, 320px responsive/RTL CSS, reduced motion and forced-colors support; browser/zoom matrix pending staging |
| F19-NFR-006 | Observability | privacy-safe health, audit, trace IDs and aggregate fatigue/value signals |
| F19-NFR-007 | Migration/rollback | idempotent core schema + separately versioned subscription schema, non-destructive defaults and documented rehearsal |
| F19-NFR-008 | Operability | System Check, reconciliation, queue/dead-letter tools and runbooks |
| F19-NFR-009 | Compatibility | PHP 8.1/8.3/8.4 lint/test matrix; project baseline WP 7.0.1/PHP 8.3 pending real staging |
| F19-NFR-010 | Localization | text domain, locale templates, timezone handling, RTL and English-US base |

## Status truth

Passing this matrix proves **Specified + Coded + Packaged + Automated-QA Green** only after CI succeeds on the exact release head. It does not prove **Staging-Accepted, Live-Deployed, or Operational**; those require real Hostinger/WordPress/MySQL, File 00/08/17/18/20/21 contracts, provider credentials, browsers/devices, backup/restore, rollback rehearsal and Founder acceptance.
