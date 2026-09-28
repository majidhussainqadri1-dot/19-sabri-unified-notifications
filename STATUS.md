# File 19 Status

| Status layer | Current evidence |
|---|---|
| Specified | File 19 master plan + consolidated central governing corpus + Intelligent Attention 3.0 register + current twenty-round cross-file review |
| Audited baseline | Main commit `942bff557c020617c805b5cd982f69ec8a717fac` was the frozen pre-correction baseline |
| Runtime source lineage | Runtime **3.0.5**, DB schema **3.0.2**; the current runtime source tree was introduced by commit `c2881b12fc7e91c050782f7b17bda00d1d69b2f2` and later release-evidence commits do not alter plugin runtime or schema |
| Exact current repository HEAD | Read the repository `main` ref and its matching GitHub Actions run. This file deliberately does not claim that its own parent commit is the current HEAD, avoiding an impossible self-referential/stale SHA assertion |
| Coded scope | File 01 registry/event-backbone closure, plan-complete command/query manifest, outbound NotificationCreated/Read/DeliveryFailed/PreferenceChanged events, stronger manifest parity checks, idempotent notification/preference state changes, delivery-failure reconciliation, plus retained Intelligent Attention 3.0 functionality |
| Cross-file owner contracts | File 00 identity, File 01 registry/event backbone, File 02 fresh step-up, File 20 single notification surface/Safe Mode, File 24 containment/assurance, File 25 visual ownership, File 26 saved-search ownership |
| Package | `19-sabri-unified-notifications-3.0.5.zip`, canonical top folder `unified-notifications-19/`; frozen deterministic SHA-256 **92131b0a5a5e78a9e67016615392e30c221650ebbd2bb9ebed9b577fa4b11a09** |
| Automated QA | Every changed candidate and merged `main` HEAD must pass the File 19 Quality workflow on PHP 8.3 and 8.4, including unit, advanced, completeness, TextBee, static/security/privacy, clean-extract package and deterministic rebuild gates. GitHub Actions is the authoritative exact-head record |
| Staging-Accepted | **Not claimed** |
| Live-Deployed | **Unverified**; repository code is not deployment evidence |
| Operational | **Not claimed** |

## 3.0.5 corrective scope

The twenty-round review corrected these repository-level defects:

1. File 19 declares File 01 as a required registry/event-backbone dependency.
2. File 01 manifest comparison checks the complete contract-bearing shape rather than only version/state/routes.
3. File 19 registers versioned API and outbound-event schemas in the File 01 registry.
4. The planned `MarkNotification.v1` command and `GetNotificationPreferences.v1` query are present in the manifest.
5. `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1` and `NotificationPreferenceChanged.v1` publish through the File 01 reliable event backbone.
6. Read/archive/unarchive and preference writes are hardened against duplicate no-op churn.
7. Delivery-failure events have a reconciliation path for temporary event-backbone publication failures.
8. Release evidence is frozen to the deterministic 3.0.5 package checksum produced by exact-head CI.
9. Status evidence no longer embeds a necessarily stale “current HEAD” claim; exact-head truth is resolved from the repository ref plus its matching workflow run.

## Repository-completion boundary

Repository evidence is green only when the exact current `main` SHA has a successful matching File 19 Quality workflow and the frozen package checksum still matches. Cross-repository producer compatibility remains subject to exact current companion heads and must be rechecked whenever a producer repository changes.

Staging and Live are separate realities. Exact deployed plugin version, deployed checksum, live DB/schema version, migration state, provider configuration and real-role journeys are not verified by this repository status.
