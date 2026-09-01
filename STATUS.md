# File 19 Status

| Status layer | Current evidence |
|---|---|
| Specified | File 19 master plan + central governing corpus + Founder-approved Intelligent Attention 3.0 addendum mapped; TextBee SMS operational integration added without changing domain ownership |
| Coded | **3.0.1** runtime with first-party TextBee SMS bridge, wp-config-only secret handling, fail-closed HTTP/E.164 validation, truthful provider acceptance receipts, and preserved 3.0 attention/intelligence/automation/routing scope |
| Database schema | **3.0.0** — unchanged; TextBee integration adds no table or column |
| Packaged | Deterministic `19-sabri-unified-notifications-3.0.1.zip`, canonical `unified-notifications-19/`, frozen SHA-256 `a358cf67b6a942a0cef63aed93f9652eba82e294d7ba1e6744bcbcd638f18d70` |
| Automated-QA Green | Checksum-freeze run `33570462543` passed PHP 8.3/8.4 baseline unit assertions, 3.0 advanced assertions, 18 deterministic TextBee assertions, static/security/privacy regression, clean-extract package audit, deterministic rebuild and frozen-checksum verification |
| Standalone TextBee provider | Real Android/SIM provider path manually confirmed outside WordPress; this does not by itself prove File 19 Live integration |
| Staging-Accepted | Not claimed for 3.0.1 |
| Live-Deployed | **Current known Live is 3.0.0, not 3.0.1** until the exact 3.0.1 package is deployed and parity re-verified |
| Operational | Not claimed; requires Live secret configuration, readiness re-check, real `Send Mobile Code`, receipt, received OTP and completed verification |

## 3.0.1 scope

3.0.1 retains the complete 3.0 Intelligent Attention & Notification OS and adds the TextBee bridge to the existing provider-neutral SMS contract. The bridge reads `SUN_TEXTBEE_API_KEY` only from `wp-config.php`, optionally accepts `SUN_TEXTBEE_DEVICE_ID` and `SUN_TEXTBEE_SIM_SUBSCRIPTION_ID`, registers File 19 SMS readiness/provider/send hooks, uses TLS-verified account-level TextBee transport, preserves bounded provider receipts, and never upgrades provider acceptance into an unproven carrier-delivery claim.

## 3.0 scope retained

Smart/explainable priority; AI catch-up and read-only assistant with source-ID citation binding; semantic grouping keys; snooze/pin/needs-action/done; search/history; focus modes; attention budgets; essential-only and temporary mute; best-time scheduling; adaptive source caps; live/revocable projections; native-owner actions; source provenance; correction/retraction audience tracking; user automation and saved-search watches; learning/clinic/research trigger families; per-device controls and handoff data; Web Push/FCM/APNs architecture; opt-in WhatsApp/RCS adapters; multi-provider failover and cost-aware routing; simulator/shadow/canary framework; trace explorer; synthetic diagnostics; privacy lifecycle and wellbeing guardrails.

## Truth boundary

Repository evidence establishes **Specified / Coded / Deterministically Packaged / Automated-QA Green** for the 3.0.1 candidate. It does not prove that 3.0.1 is currently deployed on Hostinger, that the production TextBee API key is configured, or that a File 00 mobile OTP has traversed File 19 → TextBee → Android/SIM → recipient and then been verified. Those are separate Live gates.

Final Live acceptance sequence: deploy exact package → confirm File 19 runtime 3.0.1 and DB 3.0.0 → configure secret in `wp-config.php` → re-check SMS readiness → send controlled OTP → confirm provider acceptance and actual receipt → verify six-digit code → confirm File 00 mobile-ownership state.
