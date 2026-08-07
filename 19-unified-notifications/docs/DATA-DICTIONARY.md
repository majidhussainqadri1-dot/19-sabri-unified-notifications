# Data Dictionary

| Logical table | Canonical purpose | Privacy class |
|---|---|---|
| `sun_events` | immutable producer event receipt, hash and encrypted envelope | Restricted |
| `sun_notifications` | recipient notification projection and lifecycle | Private |
| `sun_preferences` | per-user category/channel choices, quiet hours and digests | Private |
| `sun_subscriptions` | explicit person/topic/community/course/event/doctor/channel subscription and frequency | Private |
| `sun_deliveries` | provider-neutral external delivery attempts and evidence | Restricted |
| `sun_templates` | versioned locale/channel templates and safe variables | Internal |
| `sun_policies` | event-category-priority-channel rules | Internal |
| `sun_devices` | encrypted device subscription and token hash | Secret/Restricted |
| `sun_dead_letters` | terminal delivery failures and operator action | Restricted |
| `sun_audit` | minimized tamper-evident action chain | Restricted |
| `sun_bulk_jobs` | explicit audience hash, encrypted notice and bounded progress | Restricted |

Every mutable user-control record has lifecycle timestamps and version/uniqueness controls. Public APIs use allowlisted DTOs and never serialize whole database rows. The wellbeing/fatigue surface is a runtime aggregate over existing notification/preference/delivery evidence and stores no separate behavioral profile.

Privacy erasure deletes notification content, devices, preferences and granular subscriptions and pseudonymizes direct recipient/provider identifiers in minimal delivery tombstones unless an approved retention hold applies. Raw device tokens and encrypted private payloads are not exposed by the privacy exporter.
