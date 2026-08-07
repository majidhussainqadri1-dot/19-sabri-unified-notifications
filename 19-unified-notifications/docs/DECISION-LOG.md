# Decision Log

1. **One canonical owner:** File 19 owns notification projection and delivery; domain truth remains native.
2. **One bell:** all placement routes through File 20; duplicate module bells are prohibited.
3. **Explicit recipients:** File 19 never guesses recipients from broad roles in the event envelope.
4. **At-least-once intake:** consumers are idempotent; duplicates are suppressed by stable identities.
5. **Sensitive external minimization:** detailed private content is in-app only after authorization.
6. **Honest delivery:** `accepted` is not presented as `delivered` without verified provider evidence.
7. **Provider-neutral adapters:** email/push/SMS credentials and transports are injected, not hard-coded.
8. **Non-destructive uninstall:** data purge is a separate guarded operation.
9. **Repository compatibility:** actual repository name remains `19-sabri-unified-notifications`; canonical runtime folder is `19-unified-notifications`.
10. **New 2.0.0 candidate:** historical package source was absent, so implementation is not falsely represented as a recovered 1.0.0 source.
