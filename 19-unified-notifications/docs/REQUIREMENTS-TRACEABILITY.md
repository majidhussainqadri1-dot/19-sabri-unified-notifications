# Requirements Traceability

## File 19 functional requirements

| ID | Requirement | Principal implementation | Automated evidence |
|---|---|---|---|
| F19-FR-001 | Versioned event intake | `SUN_Producer_Registry`, owner-bound `SUN_Event_Validator`, REST `/events` | unit + static audit |
| F19-FR-002 | Idempotency/deduplication | unique producer-event, notification and delivery dedupe keys | static + schema audit |
| F19-FR-003 | Explicit recipient resolution | validator rejects role guessing; eligibility via current File 00 assertions | unit tests |
| F19-FR-004 | Policy engine | `SUN_Policy_Engine`, versioned policy table, scoped subscription evaluation | static audit |
| F19-FR-005 | Template registry | `SUN_Template_Engine`, safe variables, locale/channel/version | unit tests |
| F19-FR-006 | Sensitive redaction | external sensitive templates become generic; auth-fetched details | unit tests |
| F19-FR-007 | In-app notification | `SUN_Notification_Service`, center template | syntax/static audit |
| F19-FR-008 | Single bell | File 20 slot and shortcode contract | static audit |
| F19-FR-009 | Notification center | filters, read/unread, archive/unarchive, bounded paging | syntax/static audit |
| F19-FR-010 | Preferences | `SUN_Preferences`, REST and settings UI | syntax/static audit |
| F19-FR-011 | Quiet hours | timezone/DST-aware next-delivery calculation | code review + staging target |
| F19-FR-012 | Digests | immediate/daily/weekly schedule, grouped delivery and overflow handling | code review + staging target |
| F19-FR-013 | Email adapter | verified email, safe body, unsubscribe semantics | static + staging target |
| F19-FR-014 | Browser/push adapter | encrypted devices, generic payload, service worker | JS/static audit |
| F19-FR-015 | SMS adapter | verified phone, opt-in/essential policy, safe content | static + staging target |
| F19-FR-016 | Queue/retry | lock, bounded batch, exponential backoff/jitter, max attempts | code review + static audit |
| F19-FR-017 | Honest delivery status | accepted/delivered/bounced/failed/suppressed; verified webhook | code review |
| F19-FR-018 | Deep-link safety | same-origin allowlist and protected click-time route | unit tests |
| F19-FR-019 | Bulk/admin notices | explicit IDs, preview, confirmation, bounded batches, cancel flag | static + staging target |
| F19-FR-020 | Observability | health snapshot, queue lag, adapters, four-plan evidence, sanitized export | static audit |
| F19-FR-021 | Reconciliation | expiry, stale devices, stuck/orphan deliveries, dead-letter retry | static + staging target |

## Top-20 central-plan notification trace

| ID | Requirement | 2.2.0 implementation | Guardrail |
|---|---|---|---|
| CV-097 | Unified inbox | one canonical notification entity, center and File 20 bell | notification is not domain state truth |
| CV-098 | Channel preference | in-app/email/push/SMS preferences + honest adapter states | unavailable provider never reported delivered |
| CV-099 | Granular subscription | `SUN_Subscriptions`, REST/PHP API and settings UI for person/topic/community/course/event/doctor/channel | essential security/safety/system not suppressed |
| CV-100 | Digest | immediate/daily/weekly + quiet hours/timezone; subscription frequency can govern external digest | urgent mandatory safety separate |
| CV-101 | Appointment reminders | explicit `Clinic.Appointment*` semantic fact catalog; File 08 remains owner | sensitive external preview redacted |
| CV-102 | Correction alert | correction/retraction semantic facts from publishing owner | severity/priority remains policy governed |
| CV-103 | Security alert | new-device/password/MFA/export/role-change fact catalog + mandatory Security policy | trusted File 00 truth and recovery boundary |
| CV-104 | Creator bulletin | opt-in `Social.CreatorBulletinPublished` with required granular subscription | frequency control; no broad-role guessing |
| CV-105 | Delivery ledger | deliveries, retries, webhooks, dead letters, audit and provider evidence | PII-minimized export/retention |
| CV-106 | Notification fatigue metric | own-user `SUN_Wellbeing` aggregate + `/wellbeing` and settings summary | no content copy; “more notifications” is not KPI |

## Non-functional requirements

| ID | Gate | Implementation/evidence |
|---|---|---|
| F19-NFR-001 | Object/field authorization | `SUN_Auth`, recipient-scoped queries, admin capabilities, producer-owner binding; negative tests required on staging |
| F19-NFR-002 | Privacy lifecycle | encryption, minimization, preference/subscription/device export, erasure/pseudonymization, retention hold and docs |
| F19-NFR-003 | Reliability | idempotency, bounded retries, dead letters and degraded external channels |
| F19-NFR-004 | Performance | bounded list/queue/bulk/device queries and indexed schema |
| F19-NFR-005 | Accessibility | semantic templates, keyboard controls, responsive/RTL CSS, reduced-motion support and staging matrix |
| F19-NFR-006 | Observability | privacy-safe health, four-plan evidence, audit and trace IDs |
| F19-NFR-007 | Migration/rollback | idempotent additive 2.2.0 schema, non-destructive defaults and documented rehearsal |
| F19-NFR-008 | Operability | System Check, reconciliation, queue/dead-letter tools and runbooks |
| F19-NFR-009 | Compatibility | runtime gate PHP 8.3+ and WordPress 7.0.1+; CI PHP 8.3/8.4; real Hostinger acceptance pending |
| F19-NFR-010 | Localization | text domain, locale templates, timezone handling, RTL and American-English base |

Repository tests prove deterministic logic/static/package properties only. File 19 DoD items that explicitly require real staging, provider credentials, browser/device/accessibility/load/security/backup/restore/rollback and Founder sign-off remain staging evidence, not silently converted into source-code claims.
