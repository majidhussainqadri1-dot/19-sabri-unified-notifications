#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)/sabri-unified-notifications"
cd "$root"

find . -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
node --check assets/js/sun.js

grep -q "Version: 1.1.0" sabri-unified-notifications.php
grep -q "DB_VERSION = '1.1.0'" includes/class-sun-db.php
grep -q "token_owned" includes/class-sun-rest.php
grep -q "encrypt_secret" includes/class-sun-rest.php
grep -q "validate_webhook_url" includes/class-sun-channels.php
grep -q "lease_token" includes/class-sun-channels.php
grep -q "notifications/unarchive" includes/class-sun-rest.php
grep -q "wp_privacy_personal_data_exporters" includes/class-sun-privacy.php
grep -q "sun_auto_floating_bell'=>0" includes/class-sun-activator.php
grep -q "X-Robots-Tag" includes/class-sun-shortcodes.php
grep -q "#FF8A1F" assets/css/sun.css
! grep -q "ensure_page(true)" includes/class-sun-activator.php
! grep -q "user_id=VALUES(user_id)" includes/class-sun-rest.php
! grep -q "sabri-notifications-deliveries" includes/class-sun-channels.php

echo "Static audit passed."
