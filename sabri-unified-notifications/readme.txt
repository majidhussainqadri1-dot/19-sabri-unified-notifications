=== Sabri Unified Notifications & Alerts ===
Contributors: sabrihomeopathy
Tags: notifications, alerts, marketplace, chat, email, sms, push
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: Proprietary - Sabri Homeopathy

A central, modern notification system for Sabri Homeopathy. It provides a notification centre, floating bell, unread counters, categories, preferences, browser alerts while the site is open, email delivery, optional SMS and push webhooks, retry logs, digests, device registration, REST endpoints, audit logs, System Check and Marketplace/Network synchronisation.

== Shortcodes ==
[sabri_notifications]
[sabri_notification_bell]

== Installation ==
Upload the ZIP from Plugins > Add Plugin > Upload Plugin. Activate it, then open Notifications > System Check and run Complete Repair.

== Important ==
Email uses WordPress wp_mail and depends on the site's mail configuration. SMS and external/mobile push require a compatible provider webhook configured by the administrator. Do not paste secret keys into public pages or screenshots.
