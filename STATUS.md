# File 19 Status

| Status layer | Current evidence |
|---|---|
| Specified | File 19 master plan + consolidated central governing corpus + Intelligent Attention 3.0 register + current twenty-round cross-file corrective review |
| Audited baseline | Main HEAD `942bff557c020617c805b5cd982f69ec8a717fac` was the frozen pre-correction baseline |
| Corrected/merged source | Main HEAD `c2881b12fc7e91c050782f7b17bda00d1d69b2f2`; runtime **3.0.5**, DB schema **3.0.2** |
| Coded scope | File 01 registry/event-backbone closure, plan-complete command/query manifest, outbound NotificationCreated/Read/DeliveryFailed/PreferenceChanged events, stronger manifest parity checks, idempotent notification/preference state changes, delivery-failure reconciliation, plus retained Intelligent Attention 3.0 functionality |
| Cross-file owner contracts | File 00 identity, File 01 registry/event backbone, File 02 fresh step-up, File 20 single notification surface/Safe Mode, File 24 containment/assurance, File 25 visual ownership, File 26 saved-search ownership |
| Package | `19-sabri-unified-notifications-3.0.5.zip`, canonical top folder `unified-notifications-19/`; exact-head deterministic SHA-256 **92131b0a5a5e78a9e67016615392e30c221650ebbd2bb9ebed9b577fa4b11a09** |
| Automated QA | **GREEN** on main HEAD `c2881b12fc7e91c050782f7b17bda00d1d69b2f2`; GitHub Actions run **36340985529**, PHP 8.3 and 8.4 jobs passed, including unit, advanced, completeness, TextBee, static/security/privacy, clean-extract package and deterministic rebuild |
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
8. Release evidence is frozen to the deterministic 3.0.5 package checksum produced by exact-main-head CI.

## Repository-completion boundary

Repository evidence is green for the reviewed source/package state. Cross-repository producer compatibility remains subject to exact current companion heads and should be rechecked whenever a producer repository changes.

Staging and Live are separate realities. Exact deployed plugin version, deployed checksum, live DB/schema version, migration state, provider configuration and real-role journeys are not verified by this repository status.
