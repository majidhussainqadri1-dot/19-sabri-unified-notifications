# File 19 — 3.0.7 source-level corrective audit (13 November 2026)

## Evidence authority and safety boundary

This document records tested **repository-source** defects against the V12.1 central master plan. It is **not** evidence that these defects exist in the presently deployed live artifact; the exact installed File 19 version, WordPress options, database, migrations, provider configuration and logs have not been verified. Do not deploy until the Live Reality Freeze / parity sequence has succeeded.

- Source baseline: merged `main` SHA `f506b7556afae873a9426f4f5d64df682c13219c`, File 19 plugin 3.0.6, declared schema 3.0.2.
- New candidate: 3.0.7, declared schema **3.0.2 unchanged**, no DB migration in this source batch.
- Source candidate package: `19-sabri-unified-notifications-3.0.7.zip`.
- Deterministic ZIP SHA-256: `2a77ffcea0ab1cf5a9794f22171d7af280ceea7a3e9658bd10b5a4ae33cbd107`, independently check exact-HEAD GitHub Actions before acceptance.
- Historical 3.0.6 release checksum: `0e536825782e1dd83686b7ee4fa017dc57df731fb5e32b8113ac0fc4640eee40`.

## New defects — 2 roots and 7 failing pre-fix assertions

### SUN-307-001 — Consumer filter could bypass authoritative containment

**Original code:** `SUN_Operational_Gate::allows()` calculated a denial for missing File 20 / File 24, local emergency Safe Mode or protected bulk actions, then returned the unrestricted result of the generic `sun_operational_action_allowed` filter. A lower-priority consumer returning `true` could negate the owner's denial, without any audited override or owner re-authorization.

**Reproduction:** synthetic regression `tests/containment-registry-regression.php` ran against pre-fix 3.0.6 source and failed three negative assertions: absent File 20/File 24, bulk processing without required owners, and local emergency Safe Mode could be overridden by a `true` callback.

**Root cause:** a narrowing/consumer hook was treated as an authority to broaden positive permission, contrary to the ownership model.

**Correction:** `SUN_Operational_Gate::allows()` returns the logical AND of its original authorized decision and the consumer-filter result. Filters can reject a permitted action but cannot make a forbidden high-risk action permitted. Verified normal healthy owner state remains usable.

**Required staging cases:** absent File 20, absent File 24, local Safe Mode enabled, user/role denied, healthy owners, consumer hook denying an otherwise allowed action and real bulk send disabled under containment. No actual external sending in synthetic tests.

### SUN-307-002 — Stale File 01 manifest / routes could be reported as ready

**Original code:** `SUN_Cross_File_Contracts::file01_registry_ready()` relied on the module's `active` flag and merely the presence of the three required route keys with active owner/state. It did not compare the authoritative module's version and declared command/query/event contract shape or the actual route path, layout, destination and redirect metadata.

**Reproduction:** synthetic pre-fix suite failed four negative assertions: stale software version, stale command list, altered canonical route path and untrusted route destination were all reported ready.

**Root cause:** a weak `readiness` predicate despite the existence of `manifest_is_current()` for File 01 registration/synchronization.

**Correction:** readiness now invokes the full manifest canonical comparator and exact route-owner/path/layout/status/destination/redirects checks. Any missing, unreadable, outdated or foreign registry evidence is treated as **not ready**.

**Required staging cases:** up-to-date exact File 01 module + routes, stale module, stale schema/commands, altered route path and destination, missing route, active-but-wrong owner, registry lookup error. All states must be sourced from **installed** companion File 01, not repository snippets.

## Automated tests and acceptance criteria

The 11-case deterministic suite exercises both positive and negative behavior:

- Missing File 20/File 24 fails closed.
- Consumer hook cannot override missing owner states, bulk containment or local Safe Mode.
- All healthy authoritative owner signals allow the permitted external action.
- Exact File 01 manifest and routes report ready; stale software version, commands, path and destination fail readiness; reverting to exact owner data restores readiness.

On the inherited 3.0.6 source, **7 of 11** assertions failed in GitHub Actions run `43366775370`, establishing the source-level defects before modification. The patched candidate produced **11/11 passing** assertions in subsequent CI, with source/package checksum closure still governed by the final exact SHA. This is not an actual WordPress runtime test.

## Companion source compatibility snapshot (not installed versions)

The following current GitHub companion snapshots had matching relevant symbols during this review; they must be refreshed before staging:

| Owner | GitHub source HEAD snapshot | Required interface |
|---|---|---|
| File 00 | `2fa7c022ee9cd1b65432e900579512f304532442` | `SMC_Contracts::assertions()` |
| File 01 | `adf6dbb9980a85f25f7cf4c2ee679b52703c2e71` | `SPF_Registry::get_module/list_routes`, event bus |
| File 02 | `224c39bcb8c28f77504c7348dbad41226753c7e8` | `SAUTH_Passkey_Runtime::current_assurance()` |
| File 20 | `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` | `sun_file20_safe_mode_active`, single notification surface |
| File 24 | `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` | `sun_file24_notification_containment_active` |
| File 25 | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` | canonical visual tokens |
| File 26 | `1b064c6ea9f81195611ed001d5457e8ced65460a` | `sun_validate_saved_search_ownership` |

The File 01 `module_dto()` method returns the same module fields that File 19's canonical comparator checks; no speculative private-schema comparison was introduced.

## Live gate and defect-count accounting

| Evidence axis | State |
|---|---|
| Newly proven source root causes | 2 |
| Failing source reproductions before fix | 7 of 11 synthetic assertions |
| Source code corrected in review branch | 2 (pending exact-HEAD CI and review) |
| Merged into main | 0 for 3.0.7 at time of authoring |
| WordPress/Jetpack access | site_disconnected |
| WPVibe access | HTTP 530; root cause unknown |
| Deployed Version / DB Version / Migration State | Unverified |
| Staging and Live end-to-end test | Not run; blocked by administrative access / parity |
| Current incident | No claimed Live defect or Live resolution |

**Non-negotiable deployment rule:** Live symptom → live evidence → exact deployed version → DB/schema state → parity → root cause → source correction where applicable → test → package → staged deployment → Live re-test → parity confirmation. The 81 V12.1 requirements retain their individual acceptance gates under `docs/FILE19-V12.1-3.0.6-COMPLETION-GATES.md`; this document adds new evidence rather than claiming all 81 are passed.
