# Security

## Native controls

- Current File 00 v2 claims are re-read for recipient eligibility, Founder identity and privileged notification operations; legacy/local metadata never grants authority.
- Server-side capability, recipient, object and state authorization.
- Producer allowlist, event-type allowlist, canonical producer-owner binding, HMAC request signing, signature-format validation and replay window.
- File 19's built-in `sabri-system` producer owns only `System.*`; account `Security.*`, clinical `Safety.*`, appointments, publications, marketplace and communication facts must be registered by their canonical owners.
- CSRF protection through WordPress REST nonces or admin nonces.
- Idempotency keys and unique constraints for event, notification and delivery fan-out.
- Authenticated encryption for private event, notification, device and bulk-job payloads.
- Credential/secret-like event fields are rejected; event data is depth/size bounded.
- Purpose-bound signed unsubscribe tokens.
- Safe same-origin deep-link storage **plus explicit click-time domain authorization** through `sun_authorize_notification_deep_link`. Same-origin alone is not authorization; non-File-19 domain links fail closed until the canonical owner revalidates current access.
- Template variable allowlists, plain-text rendering and header-injection prevention.
- Bounded recipients, payload sizes, queue batches, retries, provider costs and bulk audiences.
- Device provider/token identity is immutable across users; a token already bound to another user cannot be reassigned by an upsert.
- Privacy-minimized errors, logs, metrics and audit context.

## Secrets

Production should define `SUN_NOTIFICATION_DATA_KEY`, `SUN_NOTIFICATION_SIGNING_KEY` and producer/provider credentials through protected server configuration. Fallback to WordPress salts is supported for continuity but dedicated rotatable keys are preferred. No secret material belongs in Git, public logs, event payloads or browser payloads.

## Threats covered in repository QA

IDOR boundaries, replay, owner spoofing, policy-classification downgrade, duplicate sends, stale privilege claims, broad-role broadcasting, credential payload injection, sensitive lock-screen leakage, malicious templates, cross-origin/open redirect paths, stale deep-link authorization, device-token takeover, provider spoofing boundaries, queue storms, dead-letter loss and uncontrolled bulk messaging.

## Staging-only assurance still required

Repository tests cannot prove the live File 00/08/17/18/20/21 authorization contracts or Hostinger/provider behavior. Independent penetration testing, real producer contract negative tests, provider webhook tests, key-loss/recovery drill, compromised-producer revocation, browser/device tests, Hostinger file/database permissions, backup/restore, rollback rehearsal and File 24 evidence review remain staging gates.
