#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/19-unified-notifications"
BUILD="$ROOT/build"
NAME="19-sabri-unified-notifications-2.0.0.zip"
rm -rf "$BUILD/stage" "$BUILD/$NAME"
mkdir -p "$BUILD/stage"
cp -a "$PLUGIN" "$BUILD/stage/19-unified-notifications"
find "$BUILD/stage" -type f -exec touch -t 202608070000.00 {} +
find "$BUILD/stage" -type d -exec touch -t 202608070000.00 {} +
(
 cd "$BUILD/stage"
 find 19-unified-notifications -type f ! -name MANIFEST.sha256 -print0 | sort -z | xargs -0 sha256sum > 19-unified-notifications/MANIFEST.sha256
 touch -t 202608070000.00 19-unified-notifications/MANIFEST.sha256
 find 19-unified-notifications -type d -exec touch -t 202608070000.00 {} +
 TZ=UTC zip -X -q -9 -r "$BUILD/$NAME" 19-unified-notifications
)
sha256sum "$BUILD/$NAME" > "$BUILD/$NAME.sha256"
unzip -t "$BUILD/$NAME" >/dev/null
[[ "$(unzip -Z1 "$BUILD/$NAME" | head -n1)" == 19-unified-notifications/* ]] || { echo "invalid top-level folder" >&2; exit 1; }
echo "Built $BUILD/$NAME"
cat "$BUILD/$NAME.sha256"
