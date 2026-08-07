# Migration and Compatibility

## Historical state

The prior Git repository contained only a README identifying a historical `1.0.0` package. The source and database schema of that package were not present in Git, so this release does not invent unverified migration assumptions.

## 2.0.0 migration law

1. Take immutable database/files backup and record the installed historical package checksum.
2. Clone to access-controlled staging.
3. Inventory any `sun_*` or legacy notification tables/options before activation.
4. Run activation with public/provider gates closed.
5. `dbDelta` creates/adds the 2.0.0 schema idempotently; no foreign tables/pages/users are modified.
6. Produce a dry-run mapping for any historical rows. Import only after the exact old schema is verified and a reversible adapter is approved.
7. Reconcile notification counts, delivery states, devices and producer compatibility.
8. Test reactivation and repeat migration to prove idempotency.

Unknown historical data is preserved rather than silently deleted or coerced. A future verified legacy adapter must be versioned and tested separately.
