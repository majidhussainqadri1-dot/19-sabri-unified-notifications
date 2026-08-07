#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
"$ROOT/tools/build-release.sh" >/tmp/sun-build.log
ZIP="$ROOT/build/19-sabri-unified-notifications-2.1.0.zip"
unzip -t "$ZIP" >/dev/null
COUNT="$(unzip -Z1 "$ZIP" | grep -c '^19-unified-notifications/')"
[[ "$COUNT" -gt 30 ]] || { echo "FAIL: package too small" >&2; exit 1; }
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"
find "$TMP/19-unified-notifications" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
(cd "$TMP" && sha256sum -c 19-unified-notifications/MANIFEST.sha256 >/dev/null)
grep -q 'Version: 2.1.0' "$TMP/19-unified-notifications/19-unified-notifications.php"
grep -q 'Stable tag: 2.1.0' "$TMP/19-unified-notifications/readme.txt"
[[ -f "$TMP/19-unified-notifications/includes/class-sun-four-plan-compliance.php" ]]
[[ -f "$TMP/19-unified-notifications/includes/class-sun-subscriptions.php" ]]
[[ -f "$TMP/19-unified-notifications/includes/class-sun-value-metrics.php" ]]
cp "$ZIP" "$TMP/first.zip"
"$ROOT/tools/build-release.sh" >/tmp/sun-build-2.log
cmp -s "$TMP/first.zip" "$ZIP" || { echo "FAIL: package is not deterministic" >&2; exit 1; }
echo "PASS: package integrity and deterministic reproduction ($COUNT entries)"
