# Source Manifest

- Canonical plugin: `19-unified-notifications/`
- Bootstrap: `19-unified-notifications/19-unified-notifications.php`
- Runtime version: **3.1.0**
- Database version: **3.1.0**
- Canonical installable folder: `unified-notifications-19/`
- Core ownership: notification projections, preferences, single center/bell contract, attention state, delivery attempts, digests, devices, retries/dead letters, automation/watch rules, provider routing, privacy lifecycle and notification audit.
- Cross-file services: File 00 fail-closed identity claims; File 01 owner-manifest/route synchronization; File 20 one-bell shell detection; File 25 CSS-token consumer bridge with local accessible fallback; File 26 fail-closed saved-search ownership attestation.
- Event boundary: versioned registered producers, exact owner binding, explicit notification DTO allowlists, sensitive-field denial and minimized durable event envelopes.
- Migration boundary: governed legacy adapter inventory/dry-run framework; no unknown historical state is silently migrated or declared complete.
- Delivery adapters: email, web/native push and SMS; TextBee remains the first-party configured SMS provider bridge when `SUN_TEXTBEE_API_KEY` is defined in `wp-config.php`.
- Front end: bell, center, settings and protected route templates; responsive RTL-aware CSS and progressive JavaScript.
- Repository QA: `tests/` and `tools/`, including baseline, advanced, cross-plan completeness, security/privacy and deterministic package assertions.
- Release output: deterministic `19-sabri-unified-notifications-3.1.0.zip` plus SHA-256.
