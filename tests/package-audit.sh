#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
"$ROOT/tools/build-release.sh" >/tmp/sun-build.log
ZIP="$ROOT/build/19-sabri-unified-notifications-2.1.0.zip"
unzip -t "$ZIP" >/dev/null
COUNT="$(unzip -Z1 "$ZIP" | grep -c '^19-unified-notifications/')"
[[ "$COUNT" -gt 25 ]] || { echo "FAIL: package too small" >&2; exit 1; }
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"
find "$TMP/19-unified-notifications" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
(cd "$TMP" && sha256sum -c 19-unified-notifications/MANIFEST.sha256 >/dev/null)
echo "PASS: package integrity ($COUNT entries)"
