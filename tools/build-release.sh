#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)";PLUGIN="$ROOT/19-unified-notifications";BUILD="$ROOT/build";STAGE="$BUILD/stage";NAME="19-sabri-unified-notifications-3.0.0.zip";CANONICAL="unified-notifications-19"
rm -rf "$STAGE" "$BUILD/$NAME" "$BUILD/$NAME.sha256";mkdir -p "$STAGE";cp -a "$PLUGIN" "$STAGE/$CANONICAL"
(cd "$STAGE";find "$CANONICAL" -type f ! -name MANIFEST.sha256 -print0|LC_ALL=C sort -z|xargs -0 sha256sum > "$CANONICAL/MANIFEST.sha256")
python3 - "$STAGE/$CANONICAL" "$BUILD/$NAME" <<'PY'
from __future__ import annotations
import stat,sys
from pathlib import Path
from zipfile import ZIP_DEFLATED,ZipFile,ZipInfo
source=Path(sys.argv[1]).resolve();archive=Path(sys.argv[2]).resolve();base=source.name;fixed=(2026,8,8,0,0,0);paths=sorted(source.rglob('*'),key=lambda p:p.relative_to(source).as_posix())
with ZipFile(archive,'w',compression=ZIP_DEFLATED,compresslevel=9,strict_timestamps=True) as zf:
 i=ZipInfo(f'{base}/',fixed);i.create_system=3;i.external_attr=(stat.S_IFDIR|0o755)<<16;i.compress_type=ZIP_DEFLATED;zf.writestr(i,b'',compress_type=ZIP_DEFLATED,compresslevel=9)
 for p in paths:
  rel=p.relative_to(source).as_posix();name=f'{base}/{rel}'
  if p.is_dir():
   i=ZipInfo(name.rstrip('/')+'/',fixed);i.create_system=3;i.external_attr=(stat.S_IFDIR|0o755)<<16;i.compress_type=ZIP_DEFLATED;zf.writestr(i,b'',compress_type=ZIP_DEFLATED,compresslevel=9);continue
  i=ZipInfo(name,fixed);i.create_system=3;i.external_attr=(stat.S_IFREG|(0o755 if p.stat().st_mode&stat.S_IXUSR else 0o644))<<16;i.compress_type=ZIP_DEFLATED;zf.writestr(i,p.read_bytes(),compress_type=ZIP_DEFLATED,compresslevel=9)
PY
sha256sum "$BUILD/$NAME">"$BUILD/$NAME.sha256";unzip -t "$BUILD/$NAME">/dev/null;[[ "$(unzip -Z1 "$BUILD/$NAME"|head -n1)" == "$CANONICAL/" ]]||{ echo "invalid top-level folder" >&2;exit 1;};echo "Built $BUILD/$NAME";cat "$BUILD/$NAME.sha256"
