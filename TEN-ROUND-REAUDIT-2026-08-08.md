# File 19 — Ten-Round Corrective Re-Audit — 2026-08-08

This record documents ten distinct post-3.0 review/fix rounds against the governing central corpus, the File 19 dedicated master plan and the current repository source. Every discovered defect was corrected before continuing; affected regression evidence was then strengthened. This record covers repository/source/package evidence only and does not convert Hostinger staging, provider, live or operational gates into completed status.

## Review register

| Round | Focus | Defect? | Finding | Correction / verification |
|---|---|---:|---|---|
| 1 | QA truthfulness and requirement traceability | Yes | Advanced assertions were predominantly structural and the traceability register did not explicitly map all `F19-AF-001..048`, creating risk of overstating live functional evidence. | Expanded `advanced-unit.php`; documented deterministic-vs-staging evidence boundary; mapped all advanced requirements and corrected current runtime compatibility wording. |
| 2 | Attention-profile state semantics | Yes | Partial updates could silently reset omitted `essential_only`, `best_time_enabled` and `muted_until`. | Omitted fields now preserve canonical current state; explicit values still mutate; version conflict check retained. |
| 3 | Per-device profiles and concurrency | Yes | Partial device updates could clear categories/channels/focus/handoff; expected-version conflict protection and write-failure handling were incomplete. | Preserve omitted fields; validate handoff; add optimistic concurrency; report insert/update failures. |
| 4 | Expiry, revocation and stale-delivery safety | No | Existing canonical re-load before delivery, expiry rejection, click-time state check and source revocation/suppression already met the reviewed requirement. | No source change required; retained as a fresh no-defect review result. External provider recall after acceptance is not falsely claimed. |
| 5 | Provider routing and operational privacy | Yes | Provider health was recorded but not used in route ordering; routing-cost/provider evidence was exposed to any eligible signed-in user. | Healthy/unknown/degraded route ordering added; cost evidence restricted to notification-health privilege; experiment/trace/synthetic gates also require current File 00 eligibility. |
| 6 | AI citation integrity | Yes | Authorized citation IDs were intersected, but a model response containing hallucinated/out-of-scope IDs could still have its prose accepted. | Reject configured-AI output containing any disallowed citation, or citationless output for non-empty source material; audit rejection; deterministic fallback. |
| 7 | Automation and File 26 watch ownership | Yes | Saved-search matching used search ID without enforcing declared owner; empty source/category triggers were accepted; partial rule update could disable an enabled rule. | Bind owner + search ID; reject empty source/category triggers; preserve omitted `enabled`; normalize saved-search owner. |
| 8 | Privacy export completeness | Yes | Advanced attention-state and correction-watch export paths had fixed first-page limits and could truncate large accounts. | Paginate both datasets with WordPress exporter page/offset and include them in exporter completion logic. |
| 9 | Dynamic notification-center action parity | Yes | AJAX-rendered Priority/Search/History cards lost read/unread and archive/unarchive actions available on initial server-rendered cards. | Dynamic renderer now emits the core read/archive controls in addition to advanced actions. |
| 10 | Release identity, compile/package integrity and exact-head QA | Yes | Corrected source could not truthfully retain the old frozen 3.0.0 artifact identity; first exact-head run also exposed a PHP parse regression in the rewritten automation service. | Bumped runtime to 3.0.1 with schema 3.0.0; renamed package and CI gates; fixed automation parse regression; PHP 8.3/8.4 run `31255205117` passed all stages; package SHA-256 frozen as `577b7e635f8b45520bce4861ee327b71a8d85283a6dc22e379cf1a5955c77073`; final frozen-checksum run must remain green after this documentation-only freeze. |

## Defect distribution

- Rounds with defects: **1, 2, 3, 5, 6, 7, 8, 9, 10**.
- Rounds with no new blocking defect: **4**.
- Known unresolved repository-level defect after the completed correction cycle: **none**, subject to the final exact-head frozen-checksum CI remaining green.

## Corrected release identity

- Runtime: **3.0.1**
- Database/schema: **3.0.0**
- Package: `19-sabri-unified-notifications-3.0.1.zip`
- Canonical package top folder: `unified-notifications-19/`
- SHA-256: `577b7e635f8b45520bce4861ee327b71a8d85283a6dc22e379cf1a5955c77073`
- Pre-freeze corrective CI: `31255205117`, PHP 8.3/8.4 success; 53 baseline assertions, 78 advanced deterministic assertions, static/security/privacy audit, 85-entry clean-extract package audit, deterministic rebuild and artifact upload.

## Truth boundary

`Specified`, `Coded`, `Packaged` and `Automated-QA Green` are repository/release-candidate states. `Staging-Accepted`, `Live-Deployed` and `Operational` remain separate. In particular, real File 00/File 20/producer contracts, Hostinger WordPress environment, configured FCM/APNs/WhatsApp/RCS or other providers, browsers/devices/RTL/accessibility, load/security testing, backup/restore, rollback rehearsal and Founder acceptance remain real-environment gates.
