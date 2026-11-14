# File 19 V12.3 — two focused source review passes (not staging acceptance)

**Evidence date:** 2026-11-14. **Scope:** GitHub source candidate 3.0.8 against the File 19 V12.3 plan. These reviews do NOT replace two full acceptance reviews against deployed/staging code.

## Round 1 — Browser UI, translation, safe navigation and fallback theme

**Inputs:** V12.3 File 19 pp. 191–196; cross-cutting File 20/25/UX controls; source templates/center.php, templates/settings.php, assets/js/notifications.js, includes/class-sun-renderer.php and assets/css/notifications.css.

- Observed: PHP templates use gettext, but the JavaScript renderer used hardcoded English labels for dynamically refreshed cards, Why/source explanations, empty state and push feedback. Source root cause confirmed by the new browser-JS Node test failing on the pre-fix branch GitHub CI run 43592667828.
- Correction: central WordPress-localized dictionary in class-sun-renderer.php and tr() consumed by JavaScript. The test performs real dynamic DOM template construction against localized marker strings; no live browser claimed.
- Observed: dynamically rendered href was HTML-escaped but not scheme/origin-validated. HTML escaping does not prevent navigation to a javascript: or cross-origin URL. The server currently emits a safe notification wrapper; exploitability of a malformed response on Live is not established.
- Hardening: same-origin HTTP(S) URL validator plus canonical local fallback; Node tests cover valid in-site route, javascript:, data:, //evil.example, and absolute cross-site links.
- Observed: File 19 scoped fallback #157347 differed from V12.3 #087A4E; File 25 token remains primary. Corrected fallback only; static calculated white text contrast about 5.38:1 (not WCAG acceptance).
- Preserved: no new notification owner, no PWA/shell duplication, no DB/schema change, no new provider credentials, no real event/sending side effects.

Round 1 outcome: **source corrections proposed in candidate**; exact-head CI and independently reviewed release evidence required. Full Urdu/Arabic translation files, real responsive screenshots, screen-reader behavior and File 25 live token parity remain pending.

## Round 2 — Authorization, webhook and release-boundary source sampling

**Inputs:** includes/class-sun-rest-controller.php, class-sun-auth.php, class-sun-operational-gate.php, class-sun-provider-webhook-verifier.php, class-sun-deep-link.php, class-sun-privacy.php; V12.3 F19-FR-001/006/017/018/019, NFR-001/002/007, CEN-001/002 and CEN-REL-001.

- Source sampling observes a public REST entry for producer events, accompanied by explicit request-body size handling and a producer-signature verification call. Its registration permission callback being public alone is NOT proof of a vulnerability; end-to-end forged-event and rate-limit tests remain necessary.
- Source sampling observes provider webhook HMAC/time-window and replay-reservation logic; provider identity collisions and actual transport callbacks must still be tested on staging.
- Source sampling observes File 00 canonical eligibility and File 02 fresh privileged assurance dependencies, Safe Mode/containment ownership, click-time deep-link authorization and privacy export/erasure hooks. These *calls exist* but current deployed owner contracts and DB effects have not been verified.
- The V12.3 review did not prove any additional distinct live root cause. It explicitly refuses to mark the system secure/operational from static inspection.

Round 2 outcome: **no additional independently reproduced source root cause from the sampled areas**; detailed hostile-role/WordPress/DB/companion staging tests remain BLOCKED until S0/S2/S3.

## Additional cross-file service worker source review — 2026-11-14

- File 20 PWA default configuration registers a root-scope service worker. File 19 3.0.8 Web Push registration did so too when the user enabled browser notifications.
- Regression on the inherited 3.0.8 source explicitly observed root scope in GitHub Actions run 43595055700 (both PHP jobs). Source candidate 3.0.9 scopes new File 19 Push registrations under /sabri-notifications-push/ via a WordPress-generated URL.
- No old browser registrations are silently unregistered; multi-version upgrade, File20 PWA offline mode and actual push delivery must be tested on isolated staging, then Live only after approved release.

## Required later full two-pass acceptance

- Full acceptance Review A on sanitized staging: 81 requirement ID journeys, owner/role matrix, accessibility, locale, delivery states, API contracts, 10k+ load. Every finding receives a reproducible defect, correction and regression.
- Full acceptance Review B AFTER Review A corrections: stale schema, partial migrations, restore, revoked identity, concurrency, cache/device/push, provider outage, deployment drift, rollback and live-transition gates. Retest all affected areas.
- V12.3 zero-known-defects in the enabled scope, independent File 24 review, founder approval, authorized deployment, Live re-test and post-deploy parity are not substituted by this source document.

## Final stage ledger

| Area | Result |
|---|---|
| Source UI/URL/CSS review | Completed as source sampling and corrective candidate |
| Exact 3.0.8 CI/package/source hashes | Must be read from matching latest branch/head run |
| Hostinger deployed source and DB | UNVERIFIED |
| Stage acceptance Reviews A/B | NOT PERFORMED |
| Production release and live retest | NOT PERFORMED |

Do not use the phrase production Resolved while the last three rows remain unverified.
