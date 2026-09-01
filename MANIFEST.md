# Source Manifest

- Canonical plugin: `19-unified-notifications/`
- Bootstrap: `19-unified-notifications/19-unified-notifications.php`
- Runtime version: `3.0.1`
- Database version: `3.0.0`
- Canonical installable folder: `unified-notifications-19/`
- Core classes: database, crypto, audit, File 00 auth projection, producer registry, event validator, templates, preferences, subscriptions, policy, deep links, attention/intelligence/automation, routing, notification service, delivery service, bulk service, reconciliation, health, REST, renderer, router, admin, privacy, activation and coordinator.
- Delivery adapters: email, web/push and SMS; TextBee is the first-party configured SMS provider bridge when `SUN_TEXTBEE_API_KEY` is defined in `wp-config.php`.
- TextBee provider source: `19-unified-notifications/includes/providers/class-sun-textbee-provider.php`.
- TextBee operator guide: `19-unified-notifications/docs/TEXTBEE-SMS.md`.
- Front end: bell, center, settings and protected route templates; responsive RTL-aware CSS and progressive JS.
- Repository QA: `tests/` and `tools/`, including deterministic TextBee provider assertions.
- Release output: deterministic `19-sabri-unified-notifications-3.0.1.zip` plus SHA-256.
