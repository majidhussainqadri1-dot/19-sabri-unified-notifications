#!/usr/bin/env bash
set -euo pipefail

export LC_ALL=C
export TZ=UTC

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE="$ROOT/sabri-unified-notifications"
BUILD="$ROOT/build"
STAGE="$BUILD/stage"
BASE="19-sabri-unified-notifications-1.1.1"
PACKAGE="$BUILD/$BASE.zip"
PACKAGE_SHA="$BUILD/$BASE.zip.sha256"
SOURCE_MANIFEST="$BUILD/$BASE.manifest.sha256"
FIXED_TIMESTAMP="198001010000.00"

rm -rf "$STAGE" "$PACKAGE" "$PACKAGE_SHA" "$SOURCE_MANIFEST"
mkdir -p "$STAGE/sabri-unified-notifications"

rsync -a \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='.gitignore' \
  --exclude='build/' \
  --exclude='tests/' \
  --exclude='tools/' \
  "$SOURCE/" "$STAGE/sabri-unified-notifications/"

if find "$STAGE/sabri-unified-notifications" -type l -print -quit | grep -q .; then
  echo 'Packaging refused: symbolic links are not permitted.' >&2
  exit 1
fi

find "$STAGE/sabri-unified-notifications" -type d -exec chmod 0755 {} +
find "$STAGE/sabri-unified-notifications" -type f -exec chmod 0644 {} +

find "$STAGE/sabri-unified-notifications" -type f -name '*.php' -print0 | sort -z | xargs -0 -n1 php -l >/dev/null
node --check "$STAGE/sabri-unified-notifications/assets/js/sun.js"
grep -q 'Version: 1.1.1' "$STAGE/sabri-unified-notifications/sabri-unified-notifications.php"
grep -q "define('SUN_VERSION', '1.1.1')" "$STAGE/sabri-unified-notifications/sabri-unified-notifications.php"
grep -q "SUN_CF01_NOTIFICATION_CONTRACT_VERSION', '1.0.0'" "$STAGE/sabri-unified-notifications/sabri-unified-notifications.php"
test -f "$STAGE/sabri-unified-notifications/includes/class-sun-cf01-clinical-notifications.php"
test -f "$STAGE/sabri-unified-notifications/CF01-CLINICAL-NOTIFICATION-CONTRACT.md"

(
  cd "$STAGE"
  find sabri-unified-notifications -type f ! -name 'MANIFEST.sha256' -print | sort | while IFS= read -r file; do
    sha256sum "$file"
  done > sabri-unified-notifications/MANIFEST.sha256
  sha256sum -c sabri-unified-notifications/MANIFEST.sha256 >/dev/null
)
cp "$STAGE/sabri-unified-notifications/MANIFEST.sha256" "$SOURCE_MANIFEST"

find "$STAGE/sabri-unified-notifications" -exec touch -h -t "$FIXED_TIMESTAMP" {} +
(
  cd "$STAGE"
  find sabri-unified-notifications -type f -print | sort | zip -X -q "$PACKAGE" -@
)
unzip -t "$PACKAGE" >/dev/null
(
  cd "$BUILD"
  sha256sum "$BASE.zip" > "$BASE.zip.sha256"
)
rm -rf "$STAGE"

printf 'Package: %s\n' "$PACKAGE"
printf 'Source manifest: %s\n' "$SOURCE_MANIFEST"
printf 'SHA-256: '
cut -d' ' -f1 "$PACKAGE_SHA"
