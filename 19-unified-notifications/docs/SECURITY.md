# Security

## Native controls

- Server-side capability, recipient, object and state authorization.
- Producer allowlist, event-type allowlist, HMAC request signing and replay window.
- CSRF protection through WordPress REST nonces or admin nonces.
- Idempotency keys and unique constraints for event, notification and delivery fan-out.
- Authenticated encryption for private event, notification, device and bulk-job payloads.
- Purpose-bound signed unsubscribe tokens.
- Safe same-origin deep links and click-time authorization/state recheck.
- Template variable allowlists, plain-text rendering and header-injection prevention.
- Bounded recipients, payload sizes, queue batches, retries, provider costs and bulk audiences.
- Privacy-minimized errors, logs, metrics and audit context.

## Secrets

Production should define `SUN_NOTIFICATION_DATA_KEY`, `SUN_NOTIFICATION_SIGNING_KEY` and producer/provider credentials through protected server configuration. Fallback to WordPress salts is supported for continuity but dedicated rotatable keys are preferred. No secret material belongs in Git, public logs or browser payloads.

## Threats covered

IDOR, CSRF, replay, duplicate sends, stale writes, broad-role broadcasting, sensitive lock-screen leakage, malicious templates, open redirects, provider spoofing, queue storms, dead-letter loss, device-token exposure and uncontrolled bulk messaging.

## Required external assurance

Independent penetration testing, provider webhook tests, key-loss/recovery drill, compromised-producer revocation, Hostinger file/database permissions and File 24 evidence review remain staging gates.
