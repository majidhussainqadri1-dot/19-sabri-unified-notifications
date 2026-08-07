# Data Dictionary

| Logical table | Canonical purpose | Privacy class |
|---|---|---|
| `sun_events` | immutable producer event receipt, hash and encrypted envelope | Restricted |
| `sun_notifications` | recipient notification projection and lifecycle | Private |
| `sun_preferences` | per-user category/channel choices, quiet hours and digests | Private |
| `sun_subscriptions` | per-user notification-delivery choice for a person/topic/community/course/event/doctor/channel scope; never the underlying follow/membership/course/domain truth | Private |
| `sun_deliveries` | provider-neutral external delivery attempts and evidence | Restricted |
| `sun_templates` | versioned locale/channel templates and safe variables | Internal |
| `sun_policies` | event-category-priority-channel rules | Internal |
| `sun_devices` | encrypted device subscription and token hash | Secret/Restricted |
| `sun_dead_letters` | terminal delivery failures and operator action | Restricted |
| `sun_audit` | minimized tamper-evident action chain | Restricted |
| `sun_bulk_jobs` | explicit audience hash, encrypted notice and bounded progress | Restricted |

`sun_subscriptions` has its own idempotent schema version (`1.0.0`) so the 2.1.0 runtime can add this Top-20 value capability without pretending the unchanged core File 19 schema is a new version. The scope ID is a reference supplied by the canonical owner, not a duplicated doctor/community/course record.

Every mutable record has lifecycle timestamps; high-concurrency records have version or uniqueness controls. Public APIs use allowlisted DTOs and never serialize whole rows. Privacy erasure propagates to notification content, category/channel preferences, scoped subscriptions and device tokens unless a documented retention hold applies. Aggregate fatigue/value metrics expose counts only and never recipient, message or clinical content.
