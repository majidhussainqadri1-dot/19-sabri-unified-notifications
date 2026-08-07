# Review and Correction — Round 2 (Fresh/Adversarial)

Focus: replay/race/IDOR, duplicate sends, provider failure, sensitive leakage, stale state, bulk abuse, uninstall/rollback and source truth.

Corrections applied:

1. Queue rows are atomically claimed before adapter execution.
2. Stuck `sending` rows are reconciled to retryable failure.
3. Dead-letter retry resets attempts only after operator authorization.
4. Bulk notices require Founder capability, explicit IDs, preview and a second confirmation token.
5. Notification list cursor is computed before private database IDs are removed from DTOs.
6. Custom cron interval is registered before activation scheduling.
7. Activation installs protected routes before rewrite flush.
8. File 19 internal bulk events use the registered system producer rather than an unregistered key.
9. External delivery states distinguish accepted/delivered/suppressed/bounced/failed.
10. Uninstall is non-destructive unless an explicit protected server constant authorizes purge.

Result: zero known unresolved repository-level defects after lint, deterministic unit, static/security and clean-package checks. Real staging/provider/security acceptance remains explicitly pending.
