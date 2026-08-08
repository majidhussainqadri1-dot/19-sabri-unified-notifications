# File 19 — Corrective Release 2.3.0

## Repository release scope

This release is the fresh four-plan / Top-20 corrective successor to the 2.2.0 40-round baseline. It preserves all existing Safe Mode, provider-circuit, retry/dead-letter, privacy, File 00 eligibility and File 20 one-bell controls and adds the explicit CV-097–CV-106 completion layer.

### Added in 2.3.0

- Granular `person|topic|community|course|event|doctor|channel` subscriptions.
- `immediate|daily|weekly` per-scope frequency.
- Required opt-in scope for creator bulletins.
- Factual appointment/correction/security/bulletin event catalog without taking native domain ownership.
- Policy-owned category classification; producers cannot impersonate essential categories.
- Priority/sensitivity can be raised by an event hint but never lowered below policy.
- Private notification-fatigue summary with the explicit guardrail that more notifications are not a KPI.
- Subscription privacy export/erasure, schema health and guarded uninstall coverage.
- Stronger Founder compatibility rule: canonical File 00 first, numeric bootstrap explicit opt-in only.

## Release evidence

The installable artifact name is `19-sabri-unified-notifications-2.3.0.zip`; its canonical ZIP top folder is `unified-notifications-19/`. The release builder emits a sibling `.sha256` and an embedded `MANIFEST.sha256`. GitHub Actions re-builds the package twice and fails unless both SHA-256 values are identical. The exact successful head/package digest is recorded as PR evidence by `File 19 Release Evidence 2.3`.

## Truth of completion

- Specified: complete for known File 19 repository-owned requirements after the four fresh reviews.
- Coded: 2.3.0 corrective candidate.
- Packaged / Automated-QA Green: only when the exact PR head passes Actions and the artifact/checksum evidence is emitted.
- Staging-Accepted: requires real Hostinger/WordPress, native producer/identity/shell/assurance/visual/discovery integrations, provider credentials, real roles/devices/browsers, accessibility/load/security, backup/restore and rollback evidence.
- Live-Deployed / Operational: never inferred from source or CI.

This status separation is mandatory under the platform and File 19 governing plans.
