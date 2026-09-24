# Requirements Traceability

## Functional requirements

| ID | Requirement | Principal implementation | Automated evidence |
|---|---|---|---|
| F19-FR-001 | Versioned event intake | `SUN_Producer_Registry`, `SUN_Event_Validator`, REST `/events` | unit + static audit |
| F19-FR-002 | Idempotency/deduplication | unique producer-event/notification/delivery keys plus durable authenticated REST mutation idempotency | completeness regression + static/schema audit |
| F19-FR-003 | Explicit recipient resolution | validator rejects role guessing; eligibility via current File 00 `SMC_Contracts::assertions()` | unit + completeness regression |
| F19-FR-004 | Policy engine | `SUN_Policy_Engine`, versioned policy table | static audit |
| F19-FR-005 | Template registry | `SUN_Template_Engine`, safe variables, locale/channel/version | unit tests |
| F19-FR-006 | Sensitive redaction | external sensitive templates become generic; auth-fetched details | unit tests |
| F19-FR-007 | In-app notification | `SUN_Notification_Service`, center template | syntax/static audit |
| F19-FR-008 | Single bell | File 20-owned notification-surface contract and shortcode detection | unit/static + companion File 20 regression |
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
| F19-FR-019 | Bulk/admin notices | explicit IDs, documented reason/compensation plan, preview, confirmation, bounded batches, cancel flag | completeness/static + staging target |
| F19-FR-020 | Observability | health snapshot, queue lag, adapters, sanitized export | static audit |
| F19-FR-021 | Reconciliation | expiry, stale devices, stuck/orphan deliveries, dead-letter retry, missing attention-state repair, replay-evidence cleanup | completeness regression + static + staging target |

## Non-functional requirements

| ID | Gate | Implementation/evidence |
|---|---|---|
| F19-NFR-001 | Object/field authorization | `SUN_Auth`, recipient-scoped queries, privileged advanced endpoints revalidate canonical File 00 identity and fresh File 02 passkey step-up separately; negative tests still required on staging |
| F19-NFR-002 | Privacy lifecycle | encryption, minimization, export/erasure, retention hold and docs |
| F19-NFR-003 | Reliability | event/delivery dedupe, durable REST mutation idempotency, bounded retries, dead letters, derived-state repair and degraded external channels |
| F19-NFR-004 | Performance | bounded list/queue/bulk/device queries and indexed schema |
| F19-NFR-005 | Accessibility | semantic templates, keyboard controls, responsive/RTL CSS and staging matrix |
| F19-NFR-006 | Observability | privacy-safe health/audit plus event→policy→projection→queue/provider→action trace stages |
| F19-NFR-007 | Migration/rollback | idempotent schema, non-destructive defaults and documented rehearsal |
| F19-NFR-008 | Operability | System Check, reconciliation, queue/dead-letter tools and runbooks |
| F19-NFR-009 | Compatibility | PHP 8.3/8.4 automated matrix; project baseline WP 7.0.1/PHP 8.3 pending staging |
| F19-NFR-010 | Localization | text domain, locale templates, timezone handling, RTL and English-US base |


## 3.0.4 cross-file owner-contract traceability

| Boundary | Canonical owner | File 19 evidence | Companion evidence / gate |
|---|---|---|---|
| Recipient identity, membership eligibility and verified contact | File 00 | `SUN_Auth::assertions()` consumes `SMC_Contracts::assertions()`; retired `sabri_membership_claims_v2` is rejected by static regression | exact File 00 runtime required on staging |
| Fresh privileged authentication step-up | File 02 | `SUN_Auth::file02_step_up_verified()` consumes bounded current passkey assurance and enforces freshness | companion File 02 runtime required for privileged actions |
| Module/route/dependency registry | File 01 | structured required/optional manifest, route mapping, activation/admin governed sync and health evidence | File 01 authorization decides whether registry writes are accepted |
| Single notification bell/center placement and global Safe Mode | File 20 | consumes `sun_file20_notification_surface_state` and `sun_file20_safe_mode_active`; missing owner contract fails closed for external delivery | File 20 companion regression publishes both owner signals |
| Security/privacy incident containment | File 24 | publishes `spcrc/file19_contract_state`; consumes File 24-owned notification-containment signal; absent signal fails closed | File 24 companion regression derives containment from canonical security-state requests |
| Shared visual tokens/components | File 25 | scoped `--sabri-*` token bridge with accessible fallback | exact File 25 runtime/staging visual acceptance remains external |
| Saved-search/watch ownership | File 26 | invokes only `sun_validate_saved_search_ownership`; no File 26 private user-meta read | File 26 companion regression validates ownership from its own canonical storage |

## Intelligent Attention requirements F19-AF-001–048

Repository evidence is split by behavior rather than by mere requirement-ID presence. `tests/advanced-unit.php`, `tests/completeness-regression.php` and `tests/static-audit.sh` collectively bind the advanced catalogue to concrete implementation families:

- **AF-001–006:** explainable priority, priority inbox, grouping, source provenance, notification search/history.
- **AF-007–015:** focus modes, cross-device state, native-owner actions, why/provenance, history, frequency caps, best-time routing and extended attention state.
- **AF-016–024:** live update, user rules, File 26 saved-search watches, read-only assistant/AI citation binding, confidential mode, revocation and correction audience.
- **AF-025–032:** learning/clinic/research event families, native push readiness, WhatsApp/RCS, device profiles/handoff and verified-source handling.
- **AF-033–040:** semantic batching/digests, wellbeing controls, provider routing/cost controls and simulator dry-run behavior.
- **AF-041–048:** shadow/canary policy evaluation, end-to-end trace, synthetic non-delivery diagnostics, failover, cost-aware routing, privacy-preserving analytics and no-dark-pattern KPI guardrail.

Provider delivery, real cross-device synchronization, accessibility, load, browser/mobile behavior, configured AI/provider behavior and domain-owner end-to-end journeys remain staging/operational evidence and are not converted into repository claims.
