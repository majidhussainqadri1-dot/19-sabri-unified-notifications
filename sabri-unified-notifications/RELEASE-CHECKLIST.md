# File 19 Release Checklist

## Automated

- [x] PHP syntax audit
- [x] JavaScript syntax audit
- [x] Static security regression assertions
- [ ] WordPress coding standards
- [ ] Unit/integration tests in a real WordPress test suite

## Staging-required

- [ ] Fresh install on Hostinger staging
- [ ] Upgrade from 1.0.0 with existing notifications, devices, and deliveries
- [ ] Verify old device tokens and provider secrets migrate to encrypted storage
- [ ] Confirm unrelated `/notifications/` page content is never overwritten
- [ ] Confirm database index migration and per-device push queue behavior
- [ ] Test overlapping cron workers and lease recovery
- [ ] Test SMTP success/failure/retry and digest global-disable behavior
- [ ] Test SMS and push against approved HTTPS public endpoints
- [ ] Test Marketplace, Network, and File 20 single-bell integration
- [ ] Test privacy export and erasure
- [ ] Test no-cache/noindex headers
- [ ] Test keyboard, focus, Escape, RTL, reduced motion, and 320–1920 px viewports
- [ ] Backup restore and rollback acceptance

The corrective branch is not production-approved until all unchecked staging items pass.
