# File 19 V12.3 — evidence-first corrective ledger

**Date:** 2026-11-14. **Scope:** GitHub source candidate branch only. Never interpret this as a production incident report.
**Source baseline:** `main@66a4de6077e32c4c6cd692ae3d906afc4a5f3eb2`; frozen runtime 3.0.7, declared schema 3.0.2, successful GitHub quality run 43369054072.
**New candidate:** 3.0.8 with schema declaration unchanged at 3.0.2. No Hostinger deployment or migration was performed.

## V12.3 findings and containment

| Finding | Evidence and root | Severity in source | Work performed | Completion boundary |
|---|---|---|---|---|
| F19-V123-SRC-001 Dynamic interface untranslated | The live DOM renderer in `assets/js/notifications.js` hard-coded Pin, Unpin, Snooze, Done, Needs action, Why, empty state, and browser-push permission feedback rather than `SUNNotifications.i18n`. New Node regression reproduced missing localized `pin` on baseline branch CI 43592667828 (PHP 8.3 and 8.4 jobs failed). | Medium; nonproduction source | WordPress translator keys added in `class-sun-renderer.php`; dynamic JS now reads those keys; end-to-end Node DOM/fetch regression added. | **Fixed in candidate source only** once exact-head CI passes. Real Urdu/Arabic catalogue, File 20 UI and actual browser/screen-reader acceptance still unverified |
| F19-V123-SRC-002 Untrusted dynamic href | `renderAdvanced` interpolated `escapeHtml(item.open_url)` into `href`. HTML escaping does not validate navigation scheme or origin. This is defense-in-depth for malformed API results; the server's current safe wrapper contract mitigates ordinary paths and no Live exploit is claimed. | Security hardening, exploitability not established | `safeOpenUrl()` now accepts current-origin HTTP(S) only, otherwise falls back to `/notifications/`. Malicious scheme and cross-origin negatives added to Node regression. | Candidate source protected; actual owner deep-link authorization and cross-file deployed contract require staging |
| F19-V123-SRC-003 Visual fallback drift | Scoped CSS primary fallback `#157347` differed from V12.3/ File25 primary `#087A4E`; if File25 token is loaded, override may hide this difference. | Conditional visual inconsistency | Fallback changed to `#087A4E`; canonical File25 variable remains source of truth; calculated white contrast against this green is approximately 5.38:1, subject to real UI contrast tests. | Source-only correction; final File25 design/RTL screenshots and WCAG checks blocked |
| F19-V123-REQ-004 Evidence coverage gap | Historical 84 identifier assertions recognize 81 requirement IDs but do not execute 81 actual owner/role/backend acceptance journeys. V12.3 adds explicit cross-cutting UX/QA/CEN-REL obligations. | Process gap, not proof of missing runtime feature | Created 81-row V12.3 acceptance matrix and inventory CI; all require real staging and Live evidence. | BLOCKED: current GitHub CI must not be treated as operational acceptance |

### F19-V123-CF-005 — Conditional File 20 / File 19 service-worker scope conflict

- **Source comparison:** File 20 `main@8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` enables `pwa_shell` by default and registers its PWA service worker using a home-root scope. File 19 3.0.8 source also registered its separate Push worker at scope `/`.
- **Reproduction:** New Node push-registration regression on inherited 3.0.8 source failed in both PHP matrices at GitHub Actions run `43595055700`, showing actual scope `/` while dedicated push scope was expected.
- **Root cause:** two independent clients attempted to own a root-scope service-worker registration; this is a conditional cross-file source integration risk, not a proven Live incident.
- **Source correction:** 3.0.9 candidate uses `home_url('/sabri-notifications-push/')` as File19-owned worker scope while keeping the existing PushManager device registration. File20 PWA remains the root-scope owner.
- **Unresolved acceptance:** existing browser registrations from older versions must be inventoried and tested on staging; push permission, offline PWA, old worker retirement, no confidential cached content, and production parity remain unverified. No blind unregister or Live deployment.

## Historical source corrections (not new issues)

Five 3.0.6 and two 3.0.7 source-level root causes had already been corrected on main. Their existence and CI fixes are not evidence of defects in installed production. None is marked Live Resolved.

## Operational blockers

1. **S0 / BLOCKED:** Exact installed File 19 path/version/hash, actual WordPress DB options/tables/columns and migration state are not accessible. WordPress.com reports `site_disconnected`; WPVibe reports no registered site. Those are connector symptoms, not host outage proof.
2. **S2 / BLOCKED:** No approved deployed-artifact ZIP/source hash comparison, no historical notification-migration inventory, no staged reversible dry-run.
3. **S3 / BLOCKED:** Installed File 00/01/02/20/24/25/26 companion versions/manifest parity not attested.
4. **S4–S9 / BLOCKED:** Role-accurate event/notification E2E, providers, independent security/RTL/performance tests, two corrective reviews, rollback rehearsal and Founder staging sign-off not completed.
5. **S10–S11 / BLOCKED:** No authorized production rollout or post-deploy parity/retest performed.

## Required next operator handoff

Run `tools/file19-readonly-live-freeze.sh` only on the verified WordPress root and only by an authorized Hostinger operator after reviewing its commands and must-use-plugin bootstrap effects. Collect only sanitized metadata; independently provide web PHP, installed companion states, backup/restore, redacted logs and exact deployed archive evidence as listed in `docs/FILE19-S0-HOSTINGER-READONLY-FREEZE.md`. Stop normal bug debugging if deployed/repository drift exists.

## Exact defect counts — never conflate stages

- **Historical source root causes:** 7, fixed in repository lineage; Live verified 0.
- **This V12.3 branch:** 1 reproducible localization source defect; 1 defensive link hardening issue; 1 conditional CSS fallback mismatch; 1 acceptance evidence gap; plus 1 conditional cross-file service-worker registration risk with source-only correction.
- **Production defects identified through Live evidence:** 0 (none tested; NOT a finding that production is defect-free).
- **Production defects resolved through Live retest:** 0.
- **Known acceptance blockers:** S0 and dependent stages S2–S11, including providers, companion contract and two-review gates.
- **Current release decision:** do not deploy; complete S0–S9 before any controlled rollout.

## Release status record

| Field | Evidenced state |
|---|---|
| Repository HEAD | Resolve the exact current branch and main ref at report time; never treat a hard-coded historical SHA as current |
| Deployed Version | UNVERIFIED |
| DB Version | UNVERIFIED; 3.0.2 is source-declared only |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Ledger columns for every follow-up:** Found new | Source fixed | Merged main | Staging accepted | Live retested | Still open | Blockers.
