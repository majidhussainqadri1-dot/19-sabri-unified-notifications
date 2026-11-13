# File 19 — V12.1 central-law requirement traceability supplement

**Governing source:** Sabri Social Homeopathy Platform Central Master Plan V12.1, 10 November 2026; chapter 11, File 19, page 147.

**Preservation rule:** this is a repository-level documentation supplement. The immutable File 19 plugin runtime 3.0.7, the already-approved `19-sabri-unified-notifications-3.0.7.zip`, and its SHA-256 `2a77ffcea0ab1cf5a9794f22171d7af280ceea7a3e9658bd10b5a4ae33cbd107` are **not modified** by this documentation change.

V12.1 has exactly 81 File 19 canonical requirement IDs: 21 FR, 10 NFR, 2 CEN and 48 AF. The plugin's retained `REQUIREMENTS-TRACEABILITY.md` maps 21 FR and 10 NFR IDs; the retained `ADVANCED-ATTENTION-OS-3.0.0.md` maps 48 AF IDs. This supplement explicitly maps the previously omitted two **CEN** IDs. A source inventory is not equivalent to functional or staging/live acceptance.

## V12.1 central ownership and authorization laws

| ID | V12.1 intent — concise faithful rendering | Source implementation trace | Pending acceptance evidence |
|---|---|---|---|
| F19-CEN-01 | File 19 implements its canonical owner contract, the central single-free-tier/green/privacy/safety laws, the File 26 search/projection ownership boundary and native tests. | `SUN_Cross_File_Contracts::manifest()` and `saved_search_owned()`, `SUN_Four_Plan_Compliance`, File 00 membership, File 01 registry, File 20 one-bell/Safe Mode, File 24 containment, and File 26 authoritative saved-search claim. `tests/unit.php` and `tests/containment-registry-regression.php` verify selected local invariants. | Actual installed-owner versions, domain workflows, free/green/privacy/safety behavior, File 26 search watch ownership, role-bound positive/negative tests and no duplicate canonical search store on staging and Live. |
| F19-CEN-02 | Owner create/update/delete through authorized native commands; reads, events, indexes, caches and notification projections protected by versioned contracts and authorization rechecks. | `SUN_Auth`, `SUN_Event_Validator`, recipient-scoped `SUN_Notification_Service`, `SUN_Request_Idempotency`, `SUN_Deep_Link::authorize_click()`, File 01 `SUN_Platform_Events` and native-owner action callbacks. | Live runtime authorization for created/edited/deleted source objects, revoked access, stale caches/indexes, retraction, event replay, click-time owner checks and every role boundary; evidence from current deployed companion builds. |

**Important:** runtime source code and self-contained tests can support only source-level mapping. The above acceptance fields are unresolved until staging/live role-bound evidence is collected.

## Completion ledger

- 81 / 81 unique requirement identifiers are expected across these three documentation tables and checked by `tests/requirements-catalogue-81.php`.
- Before adding this supplement, the regression failed **3 of 84 checks**: the two F19-CEN IDs were missing and total unique count was 79 instead of 81.
- The regression does not check 81 behavior outcomes or verify WordPress deployment.
- The original 3.0.7 package checksum remains unchanged; only root-level repository documentation, tests, workflow, and their source SHA-256 manifest are eligible for change.
- Actual installed File 19 version, DB version, migration state, live errors, provider delivery and owner integrations remain unverified due disconnected site connections.
