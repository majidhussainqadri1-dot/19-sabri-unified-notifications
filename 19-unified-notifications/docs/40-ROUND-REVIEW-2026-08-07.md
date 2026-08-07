# File 19 — Forty Sequential Review / Fix Register

Date: 2026-08-07 (Pakistan Standard Time)  
Corrective runtime/schema: **2.2.0 / 2.2.0**  
Scope: repository-owned File 19 implementation  
Governing corpus:

1. `SSH-PMP-2026-v3.0` — Definitive Master Plan.
2. `Sabri-Recovered-Directives-v2.1` — later recovered Founder directives.
3. `Sabri-Continuous-Value-Top-20-v1.0` — latest of the three central plans in this corpus.
4. `SSH-F19-PLAN-2026-v1.0` — dedicated File 19 master plan.

## Precedence applied

Latest explicit Founder/safety decision > Top-20 central plan > recovered directives > Definitive Master Plan v3.0 > dedicated File 19 plan > verified runtime evidence. The three central plans govern child/file plans; later central-plan amendments supersede conflicting earlier central-plan language while non-conflicting requirements accumulate.

## Method

Each numbered round is a separate review vector. Where a defect was found, the defect was corrected before the next numbered round was accepted. Later rounds therefore review the corrected state rather than repeatedly counting the same unresolved defect. The final round is a fresh adversarial regression gate. “Clean” means no new repository-owned defect was found in that round; it is not a claim of metaphysical infallibility or staging/live acceptance.

## Forty rounds

| Round | Review vector | Result | Correction / evidence |
|---:|---|---|---|
| 1 | Four-plan identity, hierarchy and temporal precedence | **DEFECT** | Executable precedence incorrectly placed recovered directives ahead of the later Top-20 central plan. `SUN_Four_Plan_Compliance` corrected to Top-20 > recovered > Definitive, while preserving latest explicit Founder/safety supremacy. |
| 2 | Canonical File 19 ownership and exclusions | **CLEAN** | Notification projection/delivery remains File 19-owned; domain truth remains with producers. |
| 3 | Single notification bell/center and File 20 placement | **CLEAN** | One File 20 slot remains the canonical output; no second bell backend added. |
| 4 | Domain-event truth vs notification projection | **CLEAN** | Events are consumed as facts; notification records remain derivative projections. |
| 5 | Seven-status truthfulness | **CLEAN** | Coded/packaged/QA/staging/live/operational remain distinct; no staging/live claim added. |
| 6 | WordPress/PHP runtime baseline consistency | **DEFECT** | Plugin header said WP 7.0/PHP 8.3 while activation still enforced 6.6/8.1. Activation now uses `SUN_MIN_WP_VERSION=7.0` and `SUN_MIN_PHP_VERSION=8.3`. |
| 7 | Concurrent activation/migration safety | **DEFECT** | No activation lock existed. Added atomic activation lock with stale-lock recovery and guaranteed release. |
| 8 | Schema/index/idempotent database layout | **CLEAN** | Event, notification, delivery, preference, device, dead-letter, audit and bulk uniqueness/index structure remains intact. |
| 9 | File 00 as canonical identity owner | **CLEAN** | Existing 2.1.0 fail-closed `sabri_membership_claims_v2` integration remained correct. |
| 10 | Verified email/phone claims consumed by delivery channels | **DEFECT** | File 00 projection omitted `email_verified`/`phone_verified`, causing legitimate external channels to suppress. Added canonical email/mobile verification projection. |
| 11 | Suspension/revocation/risk/guardian/consent revalidation | **CLEAN** | Recipient eligibility already rechecks these canonical claims and fails closed on owner outage. |
| 12 | Privileged admin/health/retry authorization | **DEFECT** | WordPress capability alone could outlive File 00 suspension/revocation state. Manage/health/retry paths now require current trusted File 00 claims as well as capability. |
| 13 | Founder, institutional role and step-up governance | **DEFECT** | Founder/bootstrap path could be too permissive and bulk lacked explicit current step-up. Founder identity now requires current File 00 truth; bulk requires `step_up_verified`; configured ID is compatibility-only and cannot bypass canonical state. |
| 14 | Front-end bell/center/settings stale-account access | **DEFECT** | Renderer checked login only. Bell, center, settings and assets now revalidate current File 00 eligibility. |
| 15 | REST own-object authorization and rate limiting | **CLEAN** | Own-notification routes, preferences/devices and operational routes retain permission callbacks and bounded rate limits. |
| 16 | Producer event-type authorization | **CLEAN** | Registry pattern authorization remains fail-closed for unknown/disallowed producers/types. |
| 17 | Producer canonical owner binding | **DEFECT** | Authorized producer could submit an arbitrary textual owner. Event validator now requires exact registered canonical owner and stores that owner. |
| 18 | Producer schema-version compatibility | **DEFECT** | Event envelope syntax was checked but registered producer schema allowlists were not enforced. Optional `schema_versions` contract is now enforced. |
| 19 | HMAC/replay-window request protection | **CLEAN** | Timestamp window + HMAC comparison remains intact. |
| 20 | Event/notification/delivery deduplication | **CLEAN** | Producer-event uniqueness and per-recipient/policy/template delivery dedupe remain intact. |
| 21 | Deep-link exact same-origin validation | **DEFECT** | Host-only comparison permitted same-host scheme downgrade or alternate ports. Deep links now require exact scheme/host/effective-port origin, reject credentials and encoded traversal. |
| 22 | Click-time authorization | **DEFECT** | Notification open route authenticated but did not independently recheck current File 00 eligibility before resolving the target. Added click-time eligibility revalidation. |
| 23 | Private route cache/index/referrer behavior | **DEFECT** | Canonical private routes lacked explicit noindex/referrer policy at router level. Added no-cache, `X-Robots-Tag: noindex, nofollow, noarchive`, and `Referrer-Policy: same-origin`; service worker gets `nosniff`. |
| 24 | Shared Back/Home controls and safe navigation | **DEFECT** | Local inline `history.back()` could leave the site and duplicated File 20 behavior. Page now consumes File 20 context-control contract when available; fallback Back checks same-origin referrer and otherwise uses a canonical destination. |
| 25 | Notification-center actions/counters | **CLEAN** | Read/unread/archive/unarchive/bounded bulk user mutations and unread counters remain consistent. |
| 26 | Preferences, essential categories and user control | **CLEAN** | Category/channel preferences, essential in-app rules and optimistic concurrency remain coherent after the channel-claim correction. |
| 27 | Quiet hours, timezone and DST | **CLEAN** | User-timezone computation and UTC scheduling remain bounded and DST-aware through `DateTimeZone`. |
| 28 | Immediate/daily/weekly digests and overflow | **CLEAN** | Digest grouping, item cap and safe overflow summary remain implemented. |
| 29 | Transactional email and unsubscribe semantics | **CLEAN** | Verified-address requirement, signed unsubscribe token and safe open/settings links remain present; channel claim defect was already fixed in round 10. |
| 30 | Browser push/device lifecycle | **CLEAN** | Permission UX, service worker, encrypted device token storage, rotation/revocation and private payload projection remain present. |
| 31 | SMS verified-phone/cost-safe policy | **CLEAN** | SMS remains optional, verified-phone gated and content-bounded; channel claim defect was already fixed in round 10. |
| 32 | Queue claiming, exponential backoff, jitter and dead-letter | **CLEAN** | Existing queue/dead-letter implementation remains bounded and idempotent. |
| 33 | Provider outage circuit breaker | **DEFECT** | Retries/dead-letter existed but no provider circuit breaker. Added `SUN_Provider_Circuit` with failure threshold/window/cooldown and health evidence; email/push/SMS adapters use it. |
| 34 | Safe Mode / kill-switch containment | **DEFECT** | File 19 lacked a native high-risk operational gate coordinated with Files 20/24. Added `SUN_Operational_Gate`; external delivery and bulk operations fail closed while in-app reading can remain available. |
| 35 | Provider webhook verification and transition integrity | **CLEAN** | Provider webhook signature/filter gate, allowed statuses and state-transition checks remain intact. |
| 36 | Founder bulk preview/confirm/background execution | **DEFECT** | Three issues: bulk default owner used `File19` instead of canonical `File 19`; queued jobs did not revalidate creator on cron; containment did not gate bulk. Fixed owner, same-actor confirm, current founder+step-up background revalidation, hold state and safe-mode gating. |
| 37 | Privacy export, erasure and provider propagation | **DEFECT** | Export covered notifications only and erasure retained direct user IDs in notification/delivery rows. Export now includes safe preferences/device/delivery metadata; erasure pseudonymizes recipient IDs, clears sensitive/provider identifiers, deletes device/preferences and emits provider-erasure hook. |
| 38 | Retention hold, audit and data minimization | **CLEAN** | Legal/approved retention hold remains honored; audit records remain purpose-scoped and privacy erasure uses a pseudonymous audit object ID. |
| 39 | Canonical package identity and release automation | **DEFECT** | Build still emitted 2.1.0 and installed top-level folder `19-unified-notifications`, conflicting with File 19 package constitution. Build/CI/package audit now target 2.2.0 and canonical install folder `unified-notifications-19/`. |
| 40 | Fresh final adversarial regression after all corrections | **PENDING EXACT-HEAD CI** | Must be accepted only after exact-head unit/static/package/reproducibility CI is green. If CI reveals a defect, this round is reopened, fixed and rerun before merge. |

## Count before final exact-head CI

- Defect-bearing review rounds: **18**
- Clean review rounds already established: **21**
- Round 40: **pending exact-head CI**

On a green exact-head Round 40, the final count becomes **18 defect-bearing rounds + 22 clean rounds = 40 total**. If Round 40 finds a defect, it must be corrected and rerun; the final classification will then record Round 40 as defect-bearing rather than clean.

## Corrective surface summary

The 2.2.0 correction set changes governance precedence, activation/version safety, File 00 channel/governance claims, privileged and front-end authorization, producer owner/schema contracts, deep-link origin safety, click-time authorization, private route headers, File 20 contextual controls, provider circuit breakers, operational containment, bulk governance, privacy lifecycle, health evidence, tests and canonical deterministic packaging.

## Status truth

Repository coding may be called complete only after final exact-head CI is green and no known repository-owned defect remains. Hostinger staging, real WordPress/MySQL/provider/companion integration, browser/device evidence, backup/restore, rollback rehearsal, Founder acceptance, live deployment and operational monitoring remain separate release statuses.
