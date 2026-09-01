# TextBee SMS Provider — File 19

File 19 runtime 3.0.1 includes a first-party TextBee bridge for the existing provider-neutral SMS contract.

## Security model

The TextBee API key is **never** stored in `wp_options`, plugin settings, delivery rows, audit logs or repository source. Define it only in `wp-config.php` above the `/* That's all, stop editing! */` line:

```php
define( 'SUN_TEXTBEE_API_KEY', 'paste-your-textbee-api-key-here' );
```

Optional device pinning:

```php
define( 'SUN_TEXTBEE_DEVICE_ID', 'your-device-id' );
```

Optional dual-SIM pinning:

```php
define( 'SUN_TEXTBEE_SIM_SUBSCRIPTION_ID', 1 );
```

Do not paste production credentials into GitHub, WordPress options, support tickets, screenshots or chat logs.

## Provider contract

The bridge registers these existing File 19 hooks:

- `sun_sms_adapter_configured` → advertises readiness only when `SUN_TEXTBEE_API_KEY` is present.
- `sun_sms_provider_name` → reports `textbee` when configured.
- `sun_send_sms` → sends the current safe SMS body to TextBee.

The HTTP request uses the account-level TextBee endpoint:

`POST https://api.textbee.dev/api/v1/gateway/send-sms`

Authentication is sent only in the `x-api-key` request header. Recipients must already be normalized to E.164 by the owning workflow, for example `+923001234567`.

## Acceptance truth

A successful TextBee API response is recorded as `accepted`, not `delivered`. When TextBee returns `smsBatchId`, File 19 preserves it as the provider message receipt. If TextBee immediately dispatches and returns success counts without a batch id, File 19 records a clearly prefixed local acceptance receipt (`textbee-accepted-*`) so downstream OTP workflows have a nonempty receipt without claiming delivery.

Delivery must never be called verified merely because TextBee accepted the API request. Device/SIM state and carrier delivery remain separate realities.

## Live deployment sequence

1. Deploy File 19 3.0.1 from the deterministic package.
2. Confirm `sun_plugin_version = 3.0.1` and `sun_db_version = 3.0.0` after activation.
3. Define `SUN_TEXTBEE_API_KEY` in `wp-config.php`; optionally define device/SIM constants.
4. Refresh Membership Status and confirm File 19 SMS readiness is advertised.
5. Run one controlled `Send Mobile Code` test to a real E.164 number.
6. Verify TextBee acceptance evidence and the actual received OTP separately.
7. Complete the six-digit OTP flow and re-check File 00 mobile ownership state.

## Failure behavior

- No API key: provider remains unconfigured; no outbound request is made.
- Invalid E.164 number: fail closed before HTTP.
- Network error: normalized to `sun_textbee_transport_error` without leaking provider response details or credentials.
- Non-2xx TextBee response: normalized to `sun_textbee_http_error` with only the HTTP status retained.
- Invalid/rejected 2xx response: fail closed.
- Repeated failures remain governed by File 19's existing SMS provider circuit breaker.
