# Security Policy

## Protected data

Provider authorization headers and device tokens are encrypted at rest with AES-256-GCM using WordPress secret material. They are never returned through public REST responses or rendered back into administrator forms.

For stable production encryption, deployments may define a dedicated high-entropy `SUN_NOTIFICATION_ENCRYPTION_KEY` outside the database. A previous key may temporarily be supplied as `SUN_NOTIFICATION_PREVIOUS_ENCRYPTION_KEY` during controlled rotation.

## Webhooks

Only HTTPS webhooks resolving exclusively to public Internet addresses are accepted. Redirects are disabled, SSL verification is mandatory, and WordPress unsafe-URL rejection is enabled. Provider endpoints should additionally be restricted by infrastructure policy where possible.

## Private surfaces

The notification center and authenticated REST responses send no-store/no-cache and noindex/noarchive directives. Detailed system diagnostics require `manage_options`.

## Reporting

Do not publish credentials, tokens, patient data, identity documents, or exploit details in public issues. Report them privately to the repository owner.
