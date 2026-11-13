# File 19 Status

| Status layer | Current evidence |
|---|---|
| Specified | File 19 master plan + consolidated central governing corpus + Intelligent Attention 3.0 register + current twenty-round cross-file review |
| Audited baseline | Main commit `942bff557c020617c805b5cd982f69ec8a717fac` was the frozen pre-correction baseline |
| Runtime source lineage | Runtime **3.0.7** (correction branch), DB schema **3.0.2**; 3.0.5 runtime lineage began at `c2881b12fc7e91c050782f7b17bda00d1d69b2f2`; 3.0.6 source corrections have been merged into the repository; this is not evidence of staging or Live deployment |
| Exact current repository HEAD | Read the repository `main` ref and its matching GitHub Actions run. This file deliberately does not claim that its own parent commit is the current HEAD, avoiding an impossible self-referential/stale SHA assertion |
| Coded scope | File 01 registry/event-backbone closure, plan-complete command/query manifest, outbound NotificationCreated/Read/DeliveryFailed/PreferenceChanged events, stronger manifest parity checks, idempotent notification/preference state changes, delivery-failure reconciliation, plus retained Intelligent Attention 3.0 functionality |
| Cross-file owner contracts | File 00 identity, File 01 registry/event backbone, File 02 fresh step-up, File 20 single notification surface/Safe Mode, File 24 containment/assurance, File 25 visual ownership, File 26 saved-search ownership |
| Package | `19-sabri-unified-notifications-3.0.7.zip`, canonical top folder `unified-notifications-19/`; candidate SHA-256 **2a77ffcea0ab1cf5a9794f22171d7af280ceea7a3e9658bd10b5a4ae33cbd107** (first 3.0.7 deterministic build; matching exact-head recheck required), while the historical 3.0.6 ZIP SHA-256 remains `0e536825782e1dd83686b7ee4fa017dc57df731fb5e32b8113ac0fc4640eee40` |
| Automated QA | Every changed candidate and merged `main` HEAD must pass the File 19 Quality workflow on PHP 8.3 and 8.4, including unit, advanced, completeness, TextBee, static/security/privacy, clean-extract package and deterministic rebuild gates. GitHub Actions is the authoritative exact-head record |
| Staging-Accepted | **Not claimed** |
| Live-Deployed | **Unverified**; repository code is not deployment evidence |
| Operational | **Not claimed** |

## Inherited 3.0.5 cross-file corrective scope

The prior twenty-round review corrected these repository-level defects:

1. File 19 declares File 01 as a required registry/event-backbone dependency.
2. File 01 manifest comparison checks the complete contract-bearing shape rather than only version/state/routes.
3. File 19 registers versioned API and outbound-event schemas in the File 01 registry.
4. The planned `MarkNotification.v1` command and `GetNotificationPreferences.v1` query are present in the manifest.
5. `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1` and `NotificationPreferenceChanged.v1` publish through the File 01 reliable event backbone.
6. Read/archive/unarchive and preference writes are hardened against duplicate no-op churn.
7. Delivery-failure events have a reconciliation path for temporary event-backbone publication failures.
8. The historical 3.0.5 release evidence was frozen on its own exact-head CI.
9. Status evidence no longer embeds a necessarily stale “current HEAD” claim; exact-head truth is resolved from the repository ref plus its matching workflow run.

## Historical 3.0.6 source defects and corrections

1. F19-NFR-002 / F19-AF-047: privacy export previously returned `done=true` while withholding larger preferences/rules/watch datasets. The source now paginates all auxiliary categories and rejects unreadable SQL results.
2. F19-NFR-002: a failed WordPress data-erasure SQL mutation previously could still be recorded as completed. Failure now yields `WP_Error`; legacy scrub decryption/encryption/write failures also prevent completion.
3. F19-FR-017 / F19-AF-045: delivery webhooks previously matched only provider message ID plus channel. The 3.0.6 candidate requires authenticated provider identity, detects ambiguous cross-recipient receipts and applies provider-aware digest updates.
4. F19-FR-015: TextBee receipt IDs and partly failed response bodies were incorrectly interpreted as API acceptance. Queued and immediate success evidence are now explicit and fail closed.
5. F19-NFR-009: WordPress plugin readme stable tag and runtime description were stale at 3.0.4; 3.0.6 candidate metadata aligns.

Dedicated deterministic functional tests cover 215-row export pagination, SQL and legacy-erasure failures, two provider collisions, digest handling and TextBee false/partial responses. The original 3.0.5 installed version and database have **not** been verified on Live.

## New 3.0.7 evidence-first corrections

The synthetic baseline regression reproduced two source-level defects across 7 failing negative assertions on the inherited 3.0.6 code:

1. **Containment override:** `sun_operational_action_allowed` could return true even while local Safe Mode or the File 20/File 24 required gates denied external/bulk operations. Restricted consumer hooks now cannot expand the centrally authorized permission.
2. **False File 01 registry readiness:** an active module plus active route keys could be reported as ready despite stale software version, obsolete command catalog, altered route path or untrusted destination. Readiness now checks the full normalized module manifest and all canonical route fields.

The 3.0.7 regression suite is located at `tests/containment-registry-regression.php` (11 assertions). Source changes are staged in a separate branch until exact-head PHP 8.3/8.4 CI and deterministic ZIP checksums are proven. No Live incident or deployed-version parity is inferred from source defects.

## Repository-completion boundary

The 3.0.6 repository source was merged; 3.0.7 is a corrective branch pending exact-head checks and review. Repository completion holds only while the exact current main SHA has a successful matching File 19 Quality workflow and the release candidate checksum and all exact-head source hashes remain consistent. Source, staging and Live are independent realities. Cross-repository producer compatibility remains subject to exact current companion heads and must be rechecked whenever a producer repository changes.

Staging and Live are separate realities. Exact deployed plugin version, deployed checksum, live DB/schema version, migration state, provider configuration and real-role journeys are not verified by this repository status.
