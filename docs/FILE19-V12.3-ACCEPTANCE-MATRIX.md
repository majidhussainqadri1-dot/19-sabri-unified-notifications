# File 19 — V12.3 canonical 81-ID acceptance matrix

**Recorded:** 2026-11-14. **Repository:** `19-sabri-unified-notifications`.
**Governing primary source:** Sabri Website Master Plan V12.3, File 19, pages 191–196, plus central CEN-REL-001 and V12.3 cross-cutting UX/QA/operations rules.
**Baseline:** `main` at `66a4de6077e32c4c6cd692ae3d906afc4a5f3eb2`, runtime source 3.0.7, declared DB 3.0.2. A separate 3.0.8 source candidate is under review. Live/Hostinger artifact, active DB and migration are not verified.

The existence of a source file, a code path, an identifier or a green unit test is NOT acceptance. Every row below is deliberately `PENDING-LIVE-AND-STAGING` until time-stamped, role-bound evidence exists. Do not auto-promote rows based on CI. V12.3 additional cross-cutting obligations (single bell, native owner truth, Safe Mode, no sensitive preview, RTL, WCAG, safe fallback, per-category effective settings, event provenance, rollback) are extra acceptance assertions, not extra invented F19 identifiers.

## Source-to-acceptance inventory

| Requirement ID | V12.3 scope name / historical source label | State | Mandatory evidence family |
|---|---|---|---|
| F19-FR-001 | Versioned event intake | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-002 | Idempotency/deduplication | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-003 | Explicit recipient resolution | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-004 | Policy engine | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-005 | Template registry | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-006 | Sensitive redaction | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-007 | In-app notification | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-008 | Single bell | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-009 | Notification center | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-010 | Preferences | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-011 | Quiet hours | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-012 | Digests | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-013 | Email adapter | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-014 | Browser/push adapter | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-015 | SMS adapter | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-016 | Queue/retry | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-017 | Honest delivery status | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-018 | Deep-link safety | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-019 | Bulk/admin notices | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-020 | Observability | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-FR-021 | Reconciliation | PENDING-LIVE-AND-STAGING | real-role positive/negative/unauthorized, invalid inputs and owner events |
| F19-NFR-001 | Object/field authorization | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-002 | Privacy lifecycle | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-003 | Reliability | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-004 | Performance | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-005 | Accessibility | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-006 | Observability | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-007 | Migration/rollback | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-008 | Operability | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-009 | Compatibility | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-NFR-010 | Localization | PENDING-LIVE-AND-STAGING | security/privacy/performance/a11y/migration measured evidence |
| F19-CEN-01 | File 19 implements its canonical owner contract, the central single-free-tier/green/privacy/safety laws, the File 26 search/projection ownership boundary and native tests. | PENDING-LIVE-AND-STAGING | canonical owner and current authorization under deployed contracts |
| F19-CEN-02 | Owner create/update/delete through authorized native commands; reads, events, indexes, caches and notification projections protected by versioned contracts and authorization rechecks. | PENDING-LIVE-AND-STAGING | canonical owner and current authorization under deployed contracts |
| F19-AF-001 | Smart Priority Engine | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-002 | AI Catch-Up Summary | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-003 | Smart Bundling / semantic clustering | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-004 | Snooze / remind later | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-005 | Pin / star | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-006 | Global Notification Search | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-007 | Focus Modes | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-008 | Cross-device state sync | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-009 | Actionable Notifications | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-010 | Why did I get this? | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-011 | Notification History Vault | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-012 | Adaptive frequency cap | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-013 | Best-time delivery | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-014 | Smart channel routing | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-015 | Extended attention states | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-016 | Live/updating notifications | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-017 | User Rules Builder | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-018 | Saved Search Alerts / Watchlists | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-019 | AI Notification Assistant | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-020 | Citation-bound AI | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-021 | Priority Inbox | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-022 | Confidential Notification Mode | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-023 | Remote revocation | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-024 | Correction/retraction intelligence | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-025 | Learning intelligence | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-026 | Clinic notification suite | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-027 | Research & knowledge watch | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-028 | Native mobile push architecture | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-029 | WhatsApp Business / RCS | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-030 | Per-device notification control | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-031 | Device-aware handoff | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-032 | Automatic expiry | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-033 | Notification automation triggers | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-034 | Verified Source Badge | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-035 | Why Important? | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-036 | Digital Wellbeing Dashboard | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-037 | Attention Budget | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-038 | Essential-only Mode | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-039 | Temporary Mute | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-040 | Policy Simulator | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-041 | Shadow Mode | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-042 | Canary Policies | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-043 | End-to-End Trace Explorer | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-044 | Synthetic Test Notifications | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-045 | Multi-provider Failover | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-046 | Cost-aware Routing | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-047 | Privacy-preserving analytics | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |
| F19-AF-048 | No-dark-pattern wellbeing guardrail | PENDING-LIVE-AND-STAGING | real-role positive/negative, source-owner and disabled-provider truth |

## Evidence and sign-off requirements

Each row must receive `requirement_id, owner, source_sha, deployed_sha, db_schema, migration_state, test_id, test_role, test_environment, positive, invalid, unauthorized, failure, timestamp, privacy_redaction, screenshots_or_logs, defect_refs, independent_review, sign_off`. Acceptable states: `PENDING-LIVE-AND-STAGING`, `BLOCKED-WITH-OWNER`, `TESTED-STAGING`, `VERIFIED-LIVE`, or `PROVEN-DISABLED` (optional feature only, with explicit owner/reviewer-approved isolation). No row is `VERIFIED-LIVE` without a real post-deployment retest.

### Cross-file boundary
- File 00: live identity/consent/age/suspension; File 01: durable event backbone and manifest/route versions; File 02: step-up; File 20: one bell/notification settings effective values/Safe Mode; File 24: containment and independent assurance; File 25: `#087A4E` visual token; File 26: search/watch owner.
- File 08/17/21/22: never copy source-of-truth appointments, communications or publishing events into File 19 as independently authoritative data.
- SMTP/TextBee/Web Push/FCM/APNs/WhatsApp/RCS: activated only with approved provider, opt-in, privacy and cost limits; API `accepted` is not handset `delivered`.
- V12.3 release gate `CEN-REL-001`: zero known defects in active scope/dependencies; optional capabilities may be isolated only with independent proof, safe fallback and documented owner decision.

### Executable test boundaries
`tests/requirements-catalogue-81.php` proves only 81 V12.1 identifiers. `tests/v123-acceptance-inventory.php` validates this V12.3 evidence ledger; neither proves 81 runtime outcomes. `tests/v123-localization-ui.test.cjs` asserts actual browser-JS rendering with localized configuration and malicious-link negatives, not WordPress/Hostinger runtime.

## S0–S11 status

| Stage | Current evidence | Acceptance |
|---|---|---|
| S0 Live Reality Freeze | Hostinger artifact/DB/logs/backup not collected; Jetpack disconnected; WPVibe site unregistered | BLOCKED |
| S1 exact GitHub evidence | Historical `main` 3.0.7 source/CI verified; 3.0.8 branch source/build evidence pending | PARTIAL |
| S2 parity/migration | No confirmed deployed checksum or actual DB migration version | BLOCKED |
| S3 companion contracts | Installed companion builds unverified | BLOCKED |
| S4 21 FR + 2 CEN | Source mappings exist; per-role end-to-end evidence not collected | BLOCKED |
| S5 48 AF | Source mappings exist; per-feature real-role acceptance not collected | BLOCKED |
| S6 provider delivery | No approved live provider end-to-end receipt proven | BLOCKED |
| S7 privacy/security | Synthetic unit tests; independent staging security acceptance missing | BLOCKED |
| S8 UX/RTL/performance | V12.3 source UI regression under correction; real devices/loads not measured | BLOCKED |
| S9 isolated staging/two reviews | Not accepted | BLOCKED |
| S10 controlled deployment | Not authorized before S0–S9 | BLOCKED |
| S11 live re-test/operational | Not performed | BLOCKED |

**Never close File 19 Issue #23 or claim resolved from CI alone.**
