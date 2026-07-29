# Privacy Contract

File 19 stores private notifications, preferences, delivery status, encrypted device tokens, device metadata, audit records, and integration cursors.

The plugin:

- registers WordPress personal-data exporters and erasers;
- excludes raw device tokens and provider secrets from exports;
- deletes user notification, preference, device, and delivery records on approved erasure;
- anonymizes related audit actor/IP/detail fields;
- marks private pages and REST responses as no-store and noindex;
- uses sensitivity-aware external previews so clinical, identity, security, and private content is not copied to lock screens or email subjects by default.

Module integrations should explicitly set `sensitivity`, `external_title`, and `external_body` when a safe contextual preview is required.
