# File 19 Status

| Status layer | Current evidence |
|---|---|
| Specified | File 19 master plan + consolidated central governing corpus + Intelligent Attention 3.0 register + current twenty-round cross-file corrective review |
| Audited baseline | Main HEAD `942bff557c020617c805b5cd982f69ec8a717fac` was the frozen pre-correction repository baseline for this review |
| Corrective candidate | Branch `file19-hourly-20-review-20260927-r1`; runtime **3.0.5**, DB schema **3.0.2** |
| Coded scope | File 01 registry/event-backbone closure, plan-complete command/query manifest, outbound NotificationCreated/Read/DeliveryFailed/PreferenceChanged events, stronger manifest parity checks, idempotent notification/preference state changes, delivery-failure reconciliation, plus retained Intelligent Attention 3.0 functionality |
| Cross-file owner contracts | File 00 identity, File 01 registry/event backbone, File 02 fresh step-up, File 20 single notification surface/Safe Mode, File 24 containment/assurance, File 25 visual ownership, File 26 saved-search ownership |
| Package | Target `19-sabri-unified-notifications-3.0.5.zip`, canonical top folder `unified-notifications-19/`; checksum remains **PENDING** until exact-candidate CI deterministic build succeeds |
| Automated QA | **Pending for 3.0.5 corrective branch**; earlier 3.0.4 main HEAD CI was green but is not evidence for this changed candidate |
| Staging-Accepted | **Not claimed** |
| Live-Deployed | **Unverified**; repository code is not deployment evidence |
| Operational | **Not claimed** |

## 3.0.5 corrective scope

This candidate addresses repository-level defects found by the current twenty-round review:

1. File 19 now declares File 01 as a required registry/event-backbone dependency.
2. The File 01 manifest comparison checks the complete contract-bearing shape rather than only version/state/routes.
3. File 19 registers versioned API and outbound-event schemas in the File 01 registry.
4. The planned `MarkNotification.v1` command and `GetNotificationPreferences.v1` query are present in the manifest.
5. `NotificationCreated.v1`, `NotificationRead.v1`, `NotificationDeliveryFailed.v1` and `NotificationPreferenceChanged.v1` are wired to the File 01 reliable event backbone.
6. Read/archive/unarchive and preference writes are hardened against duplicate no-op churn.
7. Delivery-failure events have a reconciliation path for temporary event-backbone publication failures.

## Remaining evidence gates

Before this candidate can be called repository-green, its exact branch/head must pass PHP 8.3/8.4 unit, advanced, completeness, TextBee, static/security/privacy, clean-extract package and deterministic-rebuild gates. Cross-repository producer compatibility must also be rechecked against current owner repositories.

Staging and Live remain separate realities. Exact deployed plugin version, deployed checksum, live DB/schema version, migration state, provider configuration and real-role journeys have not been verified in this repository review.
