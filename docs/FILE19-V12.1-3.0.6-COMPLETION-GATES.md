# File 19 — V12.1 completion gates for runtime 3.0.6

**Evidence record:** 2026-11-13. **Scope:** repository candidate only, not an assertion about www.sabrihomeopathy.com deployed code.

## Governing scope

The 10-Nov-2026 Sabri Central Master Plan V12.1, chapter 11, pp. 145–150 defines exactly 81 File 19 identifiers: F19-FR-001–021 (21 functional), F19-NFR-001–010 (10 nonfunctional), F19-CEN-01–02 (2 owner laws), F19-AF-001–048 (48 advanced).
This document supplements, and does not replace, those requirements. Requirements are accepted individually only on actual positive, negative, role-bound and privacy-safe execution evidence.

## Proven repository baseline and five source findings

- Pre-correction main: `04078025b643ab7696e4cb4e37826bf152defa18`.
- Pre-correction plugin: 3.0.5; schema: 3.0.2.
- Pre-correction reproducible package SHA-256: `92131b0a5a5e78a9e67016615392e30c221650ebbd2bb9ebed9b577fa4b11a09`.
- 3.0.6 candidate: fixes **five** verified source findings while retaining schema 3.0.2.
- First fully passing candidate CI after functional regression additions: run `43357339251` on candidate SHA `1becce36f7bd7344b2cfbfe840246c4ff9eaa4b9`; PHP 8.3 and PHP 8.4 green. Later HEAD must be independently green before merge.
- 3.0.6 reproducible package candidate SHA-256: `0e536825782e1dd83686b7ee4fa017dc57df731fb5e32b8113ac0fc4640eee40`; frozen file `RELEASE-SHA256.txt` and matching exact-head CI required.
- Source defects corrected: truncated personal-data export, false erasure success on database failure, provider receipt collisions, false TextBee acceptance, and stale WordPress readme release metadata.
- No client credentials, personal data, clinical records or real notification traffic were used in synthetic tests.

## Mandatory execution order (S0–S11)

| Gate | Required actions | Evidence and acceptance |
|---|---|---|
| S0 — Live Reality Freeze | Read-only inventory of active plugin version/path/source checksum, PHP/WP version, active configuration *without revealing secrets*, logs, relevant tables, `sun_plugin_version`, `sun_db_version`, `dbDelta`/migration evidence, cron and provider readiness | Timestamped Hostinger inventory, read-only outputs, no writes; otherwise **Blocked** |
| S1 — Repository Freeze | Resolve actual main/PR HEAD; compare SHA, File 19 manifests, deterministic archive and matching workflow run | SHA-identified source, package digest, CI 8.3/8.4; not deployment proof |
| S2 — Parity / Migration | Compare deployed artifact with source and detect drift before application debugging; inventory historical 1.0.0/other schema; stage reversible dry run, backups and restore rehearsal | Proven exact deployed checksum or explicit **unverified** status; migration applicable/not applicable proven by owner attestation |
| S3 — Cross-file contract integrity | Check exact installed File 00, 01, 02, 20, 24, 25, 26 versions and their authoritative contract hooks/data rows | Owner assertions, File 01 contract registry/outbox, File 20 one bell & Safe Mode, File 24 containment, File 26 search ownership, File 25 tokens |
| S4 — Core (21 FR + 2 CEN) | Signed event intake, dedupe under concurrency, recipient eligibility, policy, redaction, one bell, notification center, preferences, quiet hours, digests, deliveries, retry, deep links, admin bulk and reconciliation | Every applicable F19-FR/CEN ID has test case + positive and unauthorized/invalid cases; no owner data copied |
| S5 — Advanced (48 AF) | Priority, groups, focus, snooze/pin, state sync, native actions, explanations, history, caps, best time, live updates, rules, AI fallback/citations, correction/retraction, device controls, experiments, privacy-safe analytics | Every applicable F19-AF ID has executable or explicit provider/companion-blocked evidence; no fabricated AI/provider success |
| S6 — External delivery | Verify opt-in/eligibility, SMTP mailbox outcome, TextBee API accepted vs phone/carrier delivered, Web Push permissions, token revocation, FCM/APNs/WhatsApp/RCS approved providers only | Provider-specific webhook signatures/receipt provenance, failover/cost limits, no real sending during synthetic diagnostics |
| S7 — Security/privacy | Test IDOR, CSRF, HMAC/replay, webhooks same-ID different providers, malicious payloads, erasure SQL failures, legacy decryption failures, export pagination, consent/retention | Zero unresolved critical/high risks, signed evidence and complete privacy requests |
| S8 — Performance/accessibility/localization | Browser matrix, 400% zoom, keyboard, screen-reader, RTL Urdu/Arabic, time zones/DST, load 10k+, queue p75/p95, server cron | Measured outputs and real user-journey acceptance; static styles alone insufficient |
| S9 — Staging Acceptance | Restore sanitized clone; install/upgrade/deactivate; real owner producers and role matrix; execute S3–S8 tests and rollback rehearsal | Founder accepts visuals/function; no critical/high issue; versioned evidence attached |
| S10 — Controlled Release | Only after S0–S9: freeze exact package SHA, take backups, explicit authorization, bounded deploy, health and provider gates initially closed | Deployed artifact checksum matches approved ZIP; migration and rollout evidence recorded |
| S11 — Live Retest & Operations | Test bell/center, eligible/ineligible roles, provider outcomes, queue, rollback, security/incident controls, error logs and observability | Confirm `Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification` separately; only then Resolved |

## Source compatibility snapshot (not deployed truth)

On 2026-11-13, public companion GitHub HEADs inspected:

| Owner | GitHub source SHA (snapshot) | Expected interface |
|---|---|---|
| File 00 | `2fa7c022ee9cd1b65432e900579512f304532442` | `SMC_Contracts::assertions()` |
| File 01 | `adf6dbb9980a85f25f7cf4c2ee679b52703c2e71` | `SPF_Event_Bus::publish()`, registry |
| File 02 | `224c39bcb8c28f77504c7348dbad41226753c7e8` | `SAUTH_Passkey_Runtime::current_assurance()` |
| File 20 | `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` | `sun_file20_safe_mode_active`, notification surface |
| File 24 | `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` | `sun_file24_notification_containment_active` |
| File 25 | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` | design tokens and safe fallbacks |
| File 26 | `1b064c6ea9f81195611ed001d5457e8ced65460a` | `sun_validate_saved_search_ownership` |

A source hook's existence is not a staging/live contract pass. Re-fetch all companion HEADs and compare exact installed versions at S3.

## Live access blocker at this review

WordPress.com/Jetpack described `www.sabrihomeopathy.com` as disconnected for site-scoped tools; separate WPVibe access had no registered site and returned HTTP 530 on a connection attempt. Neither result alone proves WordPress itself is down or identifies a root cause.

Do not disable security controls, retry unsafe deployments, infer installation, or mutate production as a workaround. Request authorized reconnection/Hostinger read-only evidence. No live deployment is authorized by this document.

## Defect accounting law

Report `new / confirmed / corrected locally / merged / staged accepted / live re-tested / remaining / blocked` distinctly; never turn a passing source unit test into a completed Live incident. Any new live defect follows **Live symptom → Live evidence → exact deployed version → DB/schema → deployment parity → root cause → fix → test → package → deploy → live retest**.

**Release rule:** 3.0.6 remains a candidate until its current exact HEAD and recorded checksum pass, and a reviewed PR is accepted. It is not `Live-Deployed` or `Operational`.
