#!/usr/bin/env bash
set -euo pipefail
repo_root="$(cd "$(dirname "$0")/.." && pwd)"
plugin_root="$repo_root/sabri-unified-notifications"
cd "$plugin_root"

mapfile -t php_files < <(find . -name '*.php' -type f -print | LC_ALL=C sort)
test "${#php_files[@]}" -ge 20
for file in "${php_files[@]}"; do php -l "$file" >/dev/null; done
node --check assets/js/sun.js

grep -q "Version: 1.1.1" sabri-unified-notifications.php
grep -q "define('SUN_VERSION', '1.1.1')" sabri-unified-notifications.php
grep -q "SUN_CF01_NOTIFICATION_CONTRACT_VERSION', '1.0.0'" sabri-unified-notifications.php
grep -q "class-sun-cf01-clinical-notifications.php" sabri-unified-notifications.php
grep -q "sun_cf01_request_clinical_notification" sabri-unified-notifications.php
grep -q "Stable tag: 1.1.1" readme.txt
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
grep -q "sun.cf01.notification-request" includes/class-sun-cf01-clinical-notifications.php
grep -q "Private notification" includes/class-sun-cf01-clinical-notifications.php
grep -q "requires_click_time_cf01_authorization" includes/class-sun-cf01-clinical-notifications.php
grep -q "delivery_state_is_not_clinical_state" includes/class-sun-cf01-clinical-notifications.php
grep -q "sun_cf01_clinical_destination_resolve" includes/class-sun-cf01-clinical-notifications.php
test -f CF01-CLINICAL-NOTIFICATION-CONTRACT.md
! grep -q "ensure_page(true)" includes/class-sun-activator.php
! grep -q "user_id=VALUES(user_id)" includes/class-sun-rest.php
! grep -q "sabri-notifications-deliveries" includes/class-sun-channels.php

cd "$repo_root"
php tests/cf01-clinical-notification-static.php
php tests/cf01-clinical-notification-runtime.php

echo "Static audit passed (${#php_files[@]} plugin PHP files)."
