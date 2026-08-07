# Known Limitations

These are external acceptance dependencies, not hidden feature omissions:

- Push and SMS transmission require a configured provider through documented filters.
- Email delivery depends on WordPress/SMTP configuration; provider acceptance is not proof of inbox delivery.
- File 00 assertions and File 20 slot behavior require their real runtime versions.
- WordPress `WP-Cron` timing is best-effort; production should use a reliable server cron trigger.
- Historical 1.0.0 schema migration awaits verified historical package/database evidence.
- Full browser, screen-reader, load, penetration, provider and Hostinger acceptance cannot be proven by repository-only tests.
