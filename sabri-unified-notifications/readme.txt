=== Sabri Unified Notifications & Alerts ===
Contributors: sabrihomeopathy
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 1.1.1
License: Proprietary — All rights reserved

Central, privacy-aware notification infrastructure for the Sabri Social Homeopathy Platform.

== Description ==

Includes in-app notifications, one authoritative shell-compatible bell, unread and category counters, archive/history/restore, preferences, quiet hours, protected email/SMS/push previews, encrypted provider secrets and device tokens, atomic delivery leases, retries, digests, Marketplace and Network synchronization, administrator diagnostics, audit logging, privacy export/erasure, repair, and safe mode.

Version 1.1.1 adds the strict `sun.cf01.notification-request` contract for future CF-01 clinical notifications. It accepts only allowlisted template metadata and an opaque non-authorizing destination reference. It accepts no arbitrary title/body, patient identity, diagnosis, symptoms, remedy, potency, dose, clinical note, attachment content, guardian detail, break-glass reason, direct clinical URL or bearer credential. External previews remain generic and final destinations require click-time authentication and native CF-01 authorization.

Shortcodes:
* [sabri_notifications]
* [sabri_notification_bell]

Public health endpoint:
* /wp-json/sabri-notifications/v1/health

Administrator-only diagnostics:
* /wp-json/sabri-notifications/v1/health/details

Protected CF-01 destination resolver:
* /wp-json/sabri-notifications/v1/clinical/{notification_id}/destination

== Installation ==

1. Take a complete staging backup.
2. Install and activate the plugin on staging.
3. Open Notifications > System Check.
4. Run Complete Repair. It will not overwrite unrelated page content.
5. Test in-app notifications first.
6. Configure SMTP and approved HTTPS SMS/push provider endpoints where required.
7. Complete RELEASE-CHECKLIST.md before production deployment.

The CF-01 contract remains inactive unless approved producer, recipient-resolution and native-destination authorization adapters are installed. The source candidate does not authorize real clinical data or production use.

== Changelog ==

= 1.1.1 =
Added a fail-closed, privacy-minimal CF-01 notification request and click-time destination-resolution contract. External previews are fixed and generic; delivery state grants no clinical authority.

= 1.1.0 =
Security, privacy, queue reliability, archive/history, accessibility, upgrade migration, and Unified Application Shell corrective release.
