# File 19 — Intelligent Attention & Notification Operating System 3.0.0

This is the later Founder-approved cumulative extension to File 19. It sits beneath the governing central corpus and above older conflicting File 19 details only where this document is explicit. It does not transfer domain source-of-truth ownership into File 19.

## Constitutional boundary

File 19 owns notification projections, user notification preferences/subscriptions, attention-state metadata, notification search/history, orchestration, delivery evidence, notification-specific intelligence, provider routing, experiments and notification diagnostics. Identity remains File 00; appointments/clinical state remain their native owners; messages/calls remain File 17; marketplace state remains File 18; shell/bell placement remains File 20; publishing remains File 21; search/discovery remains File 26. Actionable notifications must call the native owner and re-authorize at action time.

## Advanced requirement catalogue

| ID | Requirement | Principal implementation |
|---|---|---|
| F19-AF-001 | Smart Priority Engine | `SUN_Attention_Service::attention_score`, policy integration, extensible score filter |
| F19-AF-002 | AI Catch-Up Summary | `SUN_Intelligence_Service::catchup_summary` with deterministic fallback |
| F19-AF-003 | Smart Bundling / semantic clustering | deterministic `group_key` per event/subject and attention-state projection |
| F19-AF-004 | Snooze / remind later | optimistic-concurrency attention state `snoozed_until` |
| F19-AF-005 | Pin / star | `pinned_at` state and Priority Inbox ordering |
| F19-AF-006 | Global Notification Search | own-user bounded search across title/summary/type/category/source |
| F19-AF-007 | Focus Modes | balanced/study/clinic/work/sleep/travel/essential/custom |
| F19-AF-008 | Cross-device state sync | canonical server-side notification and attention-state rows |
| F19-AF-009 | Actionable Notifications | bounded signed-in action catalogue routed through native-owner filter |
| F19-AF-010 | Why did I get this? | policy/event/source/subscription explanation endpoint |
| F19-AF-011 | Notification History Vault | bounded own-user history view with user-configured view horizon |
| F19-AF-012 | Adaptive frequency cap | per-source hourly caps plus attention-budget digest deferral |
| F19-AF-013 | Best-time delivery | user opt-in local preferred time for non-essential external delivery |
| F19-AF-014 | Smart channel routing | routed adapter and ordered provider candidates |
| F19-AF-015 | Extended attention states | pinned, snoozed, needs_action, done, revoked, live revision |
| F19-AF-016 | Live/updating notifications | optimistic in-place projection update and revision counter |
| F19-AF-017 | User Rules Builder | own-user versioned trigger/action rules |
| F19-AF-018 | Saved Search Alerts / Watchlists | File 26 owner/search-id watch rule contract |
| F19-AF-019 | AI Notification Assistant | read-only own-user assistant with bounded source set |
| F19-AF-020 | Citation-bound AI | configured AI citations intersect authorized notification public IDs only |
| F19-AF-021 | Priority Inbox | pinned then attention-score then recency ordering |
| F19-AF-022 | Confidential Notification Mode | preserves existing sensitivity/redaction; no raw sensitive lock-screen payload |
| F19-AF-023 | Remote revocation | source-event withdrawal expires/redacts projections and suppresses pending deliveries |
| F19-AF-024 | Correction/retraction intelligence | engagement watch-history and correction audience resolution |
| F19-AF-025 | Learning intelligence | `Learning.*` automation trigger family; spaced-repetition events remain Learning-owner facts |
| F19-AF-026 | Clinic notification suite | `Clinic.*` automation trigger family; appointment truth remains native owner |
| F19-AF-027 | Research & knowledge watch | `Research.*`/`Knowledge.*` trigger families and saved search integration |
| F19-AF-028 | Native mobile push architecture | Web Push base plus FCM/APNs provider-route readiness |
| F19-AF-029 | WhatsApp Business / RCS | explicit opt-in preference channels, disabled provider routes by default |
| F19-AF-030 | Per-device notification control | per-device focus/category/channel profiles |
| F19-AF-031 | Device-aware handoff | encrypted per-device handoff metadata contract |
| F19-AF-032 | Automatic expiry | inherited expiry enforcement plus live-update expiry contract |
| F19-AF-033 | Notification automation triggers | event/category/source/saved-search/correction/learning/clinic/research triggers |
| F19-AF-034 | Verified Source Badge | bounded provenance label/kind/verified flag |
| F19-AF-035 | Why Important? | deterministic attention reason plus policy/source evidence |
| F19-AF-036 | Digital Wellbeing Dashboard | existing wellbeing plus advanced useful-action/suppression metrics contract |
| F19-AF-037 | Attention Budget | hourly/daily limits for non-essential external attention |
| F19-AF-038 | Essential-only Mode | suppresses non-essential notifications while preserving security/safety/system |
| F19-AF-039 | Temporary Mute | user-owned bounded future mute; essential categories bypass ordinary suppression |
| F19-AF-040 | Policy Simulator | historical no-side-effect dry-run |
| F19-AF-041 | Shadow Mode | compare baseline/candidate decision shapes without sending candidate output |
| F19-AF-042 | Canary Policies | deterministic per-user rollout buckets and explicit rollout percentage |
| F19-AF-043 | End-to-End Trace Explorer | privacy-minimized trace spans by trace ID |
| F19-AF-044 | Synthetic Test Notifications | non-delivery readiness diagnostics; no false delivery claim |
| F19-AF-045 | Multi-provider Failover | ordered routed provider attempts with failure health recording |
| F19-AF-046 | Cost-aware Routing | known-cost prioritization and truthful unknown-cost state |
| F19-AF-047 | Privacy-preserving analytics | minimization, export/erasure, bounded aggregates, no content copying |
| F19-AF-048 | No-dark-pattern wellbeing guardrail | `more-notifications-is-not-a-kpi`; critical success/useful action/fatigue/reliability preferred |

## New data domains

- `sun_attention_profiles`: user focus, attention budgets, best-time, essential-only, mute and history-view settings.
- `sun_notification_states`: pin/snooze/action/provenance/attention/group/live/revocation state linked to canonical notification projection.
- `sun_notification_rules`: user-owned automation rules.
- `sun_device_profiles`: per-device focus/category/channel and encrypted handoff state.
- `sun_provider_routes`: provider-neutral channel routes, priority, cost-known state, region/cap/health metadata.
- `sun_experiments`: simulator/shadow/canary configuration and privacy-safe metrics.
- `sun_trace_spans`: minimized operational trace evidence.
- `sun_watch_history`: user/object engagement needed to notify materially affected readers after correction/retraction.

## Security, privacy and AI laws

1. File 00 remains the positive identity authority; protected advanced endpoints fail closed when current eligibility cannot be established.
2. Essential security/safety/system policy cannot be downgraded by AI, focus modes, attention budgets, temporary mute or ordinary user automation.
3. AI is an optional summarization/ranking assistance adapter, never domain authority. No provider configured means deterministic behavior, not fabricated AI output.
4. AI summaries may cite only notification IDs already returned from the current authorized own-user result set.
5. User automation does not directly mutate another file’s domain state. `owner_action`, calendar handoff and similar actions require the native owner integration to re-authorize.
6. WhatsApp/RCS provider routes are disabled by default and require explicit user opt-in plus approved provider credentials/policies.
7. FCM/APNs are provider-adapter readiness, not claims of configured delivery.
8. Trace detail is minimized/redacted; secrets, message bodies, clinical facts, emails, phones and raw payloads are not trace data.
9. Privacy export/erasure covers advanced user-owned attention, rules, device profiles and correction-watch history.
10. Normal uninstall remains non-destructive. Destructive uninstall remains an explicit guarded operation.

## Release gates

- **Specified:** this advanced register + Word master-plan addendum.
- **Coded:** reviewable 3.0.0 source implements the listed contracts.
- **Packaged:** deterministic `19-sabri-unified-notifications-3.0.0.zip` with canonical `unified-notifications-19/` folder and frozen checksum.
- **Automated-QA Green:** PHP 8.3/8.4 unit/static/security/privacy/package/reproducibility gates pass on exact head.
- **Staging-Accepted:** real WordPress/Hostinger upgrade/fresh install, real File 00/File 20/producer contracts, provider credentials, browser/mobile/RTL/accessibility, security/load, backup/restore and rollback rehearsal pass.
- **Live-Deployed / Operational:** separate Founder-approved production and monitoring/support evidence.

No repository-only test may be used to claim Hostinger staging, provider acceptance, live deployment or operational completion.
