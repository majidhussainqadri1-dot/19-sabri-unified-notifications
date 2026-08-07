# Backup and Restore

A valid backup set contains WordPress database, plugin files, protected configuration/keys and provider settings captured consistently. Initial platform objective: RPO no worse than 24 hours and RTO no worse than 8 hours; critical environments should set tighter measured targets.

Restore acceptance requires:

1. Isolated restore to a sanitized environment.
2. Schema/table/count/checksum verification.
3. Encryption-key access and sample decrypt test.
4. Queue/dead-letter/device reconciliation.
5. File 00 authorization and File 20 bell/route tests.
6. Provider adapters disabled until explicit reauthorization.
7. User read/archive/preferences/device workflows.
8. Post-restore monitoring and evidence record.
