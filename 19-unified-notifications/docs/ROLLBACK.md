# Rollback

## Preconditions

- Database and files backup with restore proof.
- Exact old and new package checksums.
- Maintenance window, owner and communication plan.
- Provider sends paused or kill-switched.

## Procedure

1. Stop File 19 delivery and bulk jobs.
2. Export sanitized queue/dead-letter/health evidence.
3. Deactivate 2.0.0 without deleting data.
4. Restore the prior package and, only when necessary, the matching database snapshot.
5. Re-enable old jobs/providers only after compatibility checks.
6. Smoke-test login, File 20 shell, notification center, domain actions and no duplicate sends.
7. Record incident, data delta, compensated/missed notifications and Founder decision.

Uninstall is non-destructive by default. Destructive purge requires the explicit server constant `SUN_ALLOW_DESTRUCTIVE_UNINSTALL=true`, a verified backup and a separate approved operation.
