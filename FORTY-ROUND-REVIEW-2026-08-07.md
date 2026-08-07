# File 19 — Fresh Forty-Round Review Ledger — 2026-08-07

Branch: `file19-forty-review-2.4.0`  
Runtime/schema candidate: `2.4.0 / 2.4.0`  
Method: each requested round is independent and sequential; when a defect is found, it is corrected before the next numbered review begins. Staging/live/operational acceptance is not claimed by this ledger.

| Round | Result | Primary finding / correction |
|---:|---|---|
| 01 | DEFECT → FIXED | Removed post-validation event-envelope mutation path; validated envelope is immutable. |
| 02 | DEFECT → FIXED | Added recursive payload depth/node complexity bounds. |
| 03 | DEFECT → FIXED | Invalid expiry now fails closed instead of becoming non-expiring. |
| 04 | DEFECT → FIXED | Added database-safe bounds/format checks to envelope metadata. |
| 05 | DEFECT → FIXED | Requested templates are bound to current event type/wildcard only. |
| 06 | DEFECT → FIXED | Policy resolution now prefers most-specific/newest matching policy. |
| 07 | CLEAN | Subscription essential override, required opt-in scope and concurrency reviewed. |
| 08 | DEFECT → FIXED | Prevented cross-user device-token ownership takeover. |
| 09 | DEFECT → FIXED | Preserved legitimate multiple active devices instead of revoking sibling devices. |
| 10 | DEFECT → FIXED | File 00 is sole positive identity authority; local privilege-grant mutation removed. |
| 11 | CLEAN | Producer registry/signature/type authorization reviewed. |
| 12 | DEFECT → FIXED | Hardened REST intake size/depth/rate isolation and truthful device revoke response. |
| 13 | DEFECT → FIXED | Expired notifications become terminal delivery outcomes and are not retried. |
| 14 | DEFECT → FIXED | Encrypted private metadata corruption now fails closed; no sensitivity downgrade. |
| 15 | DEFECT → FIXED | Provider webhook evidence lookup scoped by channel and bounded IDs. |
| 16 | DEFECT → FIXED | Elapsed notifications hidden/rejected before cron reconciliation. |
| 17 | DEFECT → FIXED | Added fail-closed click-time deep-link domain authorization. |
| 18 | CLEAN | Own-notification mutation and bulk scoping/concurrency reviewed. |
| 19 | CLEAN | Crypto, signed-token purpose/expiry and strict same-origin rules reviewed. |
| 20 | DEFECT → FIXED | Serialized audit-chain writes and recursively minimized/redacted context. |
| 21 | DEFECT → FIXED | Privacy exporter now paginates complete delivery history. |
| 22 | DEFECT → FIXED | Concurrent subscription insert races classified as version conflicts. |
| 23 | DEFECT → FIXED | Completed CV-106 fatigue signals and privacy-minimized complaint accounting/REST. |
| 24 | CLEAN | Founder bulk preview/confirm/step-up/explicit-audience workflow reviewed. |
| 25 | DEFECT → FIXED | Dead-letter retry requires both transactional state transitions to succeed. |
| 26 | CLEAN | Email adapter verified-recipient/circuit/operational-gate behavior reviewed. |
| 27 | DEFECT → FIXED | Push token decryption corruption surfaced safely instead of false unconfigured state. |
| 28 | DEFECT → FIXED | SMS no longer hard-depends on mbstring; provider evidence bounded. |
| 29 | DEFECT → FIXED | Signed unsubscribe no longer reports success when preference write fails. |
| 30 | CLEAN | Renderer eligibility/assets/bell/settings behavior reviewed. |
| 31 | DEFECT → FIXED | Live-region selector no longer overwrites notification-card content. |
| 32 | DEFECT → FIXED | Enforced 44px targets and locale direction inheritance. |
| 33 | CLEAN | Activator/schema/capabilities/schedules/default policies reviewed. |
| 34 | DEFECT → FIXED | Uninstall cleans option-backed queue/audit/activation locks. |
| 35 | DEFECT → FIXED | System Check expanded with DB-version and expiry-cron evidence. |
| 36 | CLEAN | Plugin graph, File 20 single-bell contract, upgrade and cron wiring reviewed. |
| 37 | DEFECT → FIXED | Runtime/schema/package/test harness promoted and aligned to 2.4.0. |
| 38 | DEFECT → FIXED | CI/artifact paths were stale; aligned to 2.4.0 and expanded regressions. |
| 39 | DEFECT → FIXED | README/plugin readme/changelog/release checksum evidence was stale; corrected and exact-head checksum frozen. |
| 40 | DEFECT → FIXED | Final adversarial concurrency review found event-ingestion race truth and pre-commit integration-hook exposure; concurrent duplicate ingestion now resolves idempotently and `sun_notification_created` is emitted only after the canonical transaction commits. |

## Requested forty-round tally

- Defect-bearing rounds: **31**
- Clean rounds: **9**
- Requested rounds completed: **40 / 40**
- Every defect found in a numbered round was corrected before that round/cycle was closed.

## Final deterministic plugin artifact

`f452b54775f7a75707093b550de4bbc618f7dc27c0eb8947c96ea43e53997051  19-sabri-unified-notifications-2.4.0.zip`

This checksum covers the deterministic installable plugin folder `unified-notifications-19/`; root audit/CI metadata do not enter the plugin ZIP.

## Post-Round-40 DoD verification

Because Round 40 itself required a final code correction, the File 19 DoD requires fresh post-change verification rather than treating the Round-40 review as proof of its own fix.

- Verification A — **CLEAN / CI SUCCESS**: exact-head PHP 8.3 + 8.4 unit/static/package/reproducibility/frozen-checksum workflow run `31203217333` completed successfully after the final checksum was frozen.
- Verification B — **required on the ledger-updated exact head**: the ledger-only commit that records the forty-round result triggers the same frozen-checksum CI again. Its result is intentionally not pre-claimed inside this document; it must be read from GitHub evidence.

Staging-Accepted, Live-Deployed and Operational remain separate evidence gates under the governing plans.
