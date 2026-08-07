# Source Manifest

- Canonical source plugin: `19-unified-notifications/`
- Bootstrap: `19-unified-notifications/19-unified-notifications.php`
- Runtime version: `2.2.0`
- Database version: `2.2.0`
- WordPress minimum/project baseline: `7.0.1`
- PHP minimum: `8.3`
- Canonical installable package top folder: `unified-notifications-19/`
- Core classes: database, crypto, audit, four-plan compliance, File 00 auth, producer registry, event validator, templates, preferences, granular subscriptions, policy, deep links, notification service, delivery service, bulk service, reconciliation, health, wellbeing, REST, renderer, router, admin, privacy, activation and coordinator.
- Delivery adapters: email, web/push and SMS.
- Front end: bell, center, settings, granular subscription controls, healthy-use summary and protected route templates; responsive RTL-aware CSS and progressive JS.
- Top-20 trace: CV-097 through CV-106 in `SUN_Four_Plan_Compliance` and `docs/REQUIREMENTS-TRACEABILITY.md`.
- Repository QA: `tests/` and `tools/`.
- Release output: deterministic `19-sabri-unified-notifications-2.2.0.zip`, generated embedded `MANIFEST.sha256`, and external SHA-256 from exact-head CI.

The repository source directory name is historical/stable source layout; the installable ZIP top folder follows the dedicated File 19 plan's canonical package-folder contract.
