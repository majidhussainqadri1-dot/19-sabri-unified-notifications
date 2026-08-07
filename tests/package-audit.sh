#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
"$ROOT/tools/build-release.sh" >/tmp/sun-build.log
ZIP="$ROOT/build/19-sabri-unified-notifications-2.2.0.zip"
TOP="unified-notifications-19"
unzip -t "$ZIP" >/dev/null
COUNT="$(unzip -Z1 "$ZIP" | grep -c "^$TOP/")"
[[ "$COUNT" -gt 25 ]] || { echo "FAIL: package too small" >&2; exit 1; }
[[ "$(unzip -Z1 "$ZIP" | head -n1)" == "$TOP/" ]] || { echo "FAIL: canonical package folder mismatch" >&2; exit 1; }
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"
find "$TMP/$TOP" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
(cd "$TMP" && sha256sum -c "$TOP/MANIFEST.sha256" >/dev/null)
grep -q 'Version: 2.2.0' "$TMP/$TOP/19-unified-notifications.php"
grep -q 'Requires PHP: 8.3' "$TMP/$TOP/19-unified-notifications.php"
echo "PASS: canonical package integrity ($COUNT entries)"
