=== Sabri Unified Notifications & Alerts ===
Contributors: sabrihomeopathy
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 1.1.0
License: Proprietary — All rights reserved

Central, privacy-aware notification infrastructure for the Sabri Social Homeopathy Platform.

== Description ==

Includes in-app notifications, one authoritative shell-compatible bell, unread and category counters, archive/history/restore, preferences, quiet hours, protected email/SMS/push previews, encrypted provider secrets and device tokens, atomic delivery leases, retries, digests, Marketplace and Network synchronization, administrator diagnostics, audit logging, privacy export/erasure, repair, and safe mode.

Shortcodes:
* [sabri_notifications]
* [sabri_notification_bell]

Public health endpoint:
* /wp-json/sabri-notifications/v1/health

Administrator-only diagnostics:
* /wp-json/sabri-notifications/v1/health/details

== Installation ==

1. Take a complete staging backup.
2. Install and activate the plugin on staging.
3. Open Notifications > System Check.
4. Run Complete Repair. It will not overwrite unrelated page content.
5. Test in-app notifications first.
6. Configure SMTP and approved HTTPS SMS/push provider endpoints where required.
7. Complete RELEASE-CHECKLIST.md before production deployment.

== Changelog ==

= 1.1.0 =
Security, privacy, queue reliability, archive/history, accessibility, upgrade migration, and Unified Application Shell corrective release.
