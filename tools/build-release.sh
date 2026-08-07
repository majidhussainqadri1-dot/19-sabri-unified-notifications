#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/19-unified-notifications"
BUILD="$ROOT/build"
STAGE="$BUILD/stage"
NAME="19-sabri-unified-notifications-2.1.0.zip"

rm -rf "$STAGE" "$BUILD/$NAME" "$BUILD/$NAME.sha256"
mkdir -p "$STAGE"
cp -a "$PLUGIN" "$STAGE/19-unified-notifications"

(
  cd "$STAGE"
  find 19-unified-notifications -type f ! -name MANIFEST.sha256 -print0 \
    | LC_ALL=C sort -z \
    | xargs -0 sha256sum > 19-unified-notifications/MANIFEST.sha256
)

python3 - "$STAGE/19-unified-notifications" "$BUILD/$NAME" <<'PY'
from __future__ import annotations

import stat
import sys
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

source = Path(sys.argv[1]).resolve()
archive = Path(sys.argv[2]).resolve()
base = source.name
fixed_time = (2026, 8, 7, 0, 0, 0)

paths = sorted(source.rglob('*'), key=lambda p: p.relative_to(source).as_posix())
with ZipFile(archive, 'w', compression=ZIP_DEFLATED, compresslevel=9, strict_timestamps=True) as zf:
    top = ZipInfo(f'{base}/', fixed_time)
    top.create_system = 3
    top.external_attr = (stat.S_IFDIR | 0o755) << 16
    top.compress_type = ZIP_DEFLATED
    zf.writestr(top, b'', compress_type=ZIP_DEFLATED, compresslevel=9)

    for path in paths:
        rel = path.relative_to(source).as_posix()
        arcname = f'{base}/{rel}'
        if path.is_dir():
            info = ZipInfo(arcname.rstrip('/') + '/', fixed_time)
            info.create_system = 3
            info.external_attr = (stat.S_IFDIR | 0o755) << 16
            info.compress_type = ZIP_DEFLATED
            zf.writestr(info, b'', compress_type=ZIP_DEFLATED, compresslevel=9)
            continue

        info = ZipInfo(arcname, fixed_time)
        info.create_system = 3
        mode = 0o755 if path.stat().st_mode & stat.S_IXUSR else 0o644
        info.external_attr = (stat.S_IFREG | mode) << 16
        info.compress_type = ZIP_DEFLATED
        zf.writestr(info, path.read_bytes(), compress_type=ZIP_DEFLATED, compresslevel=9)
PY

sha256sum "$BUILD/$NAME" > "$BUILD/$NAME.sha256"
unzip -t "$BUILD/$NAME" >/dev/null
[[ "$(unzip -Z1 "$BUILD/$NAME" | head -n1)" == "19-unified-notifications/" ]] || {
  echo "invalid top-level folder" >&2
  exit 1
}

echo "Built $BUILD/$NAME"
cat "$BUILD/$NAME.sha256"
