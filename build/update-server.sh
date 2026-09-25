#!/usr/bin/env bash
#
# Point updates.xml at a built component zip.
#
#   build/update-server.sh dist/Mothership-0.0.25.zip
#
# Rewrites the version, both download URLs, and the enclosure length and SHA-256 hash
# so Joomla's updater accepts the published asset. Run after the zip is uploaded to the
# GitHub release, since the update server is the copy of updates.xml on main.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

ZIP=${1:-}
[ -f "$ZIP" ] || { echo "usage: build/update-server.sh dist/Mothership-<version>.zip" >&2; exit 1; }

NAME=$(basename "$ZIP")
VERSION=${NAME#Mothership-}; VERSION=${VERSION%.zip}
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "update-server: cannot read a version from '$NAME'" >&2; exit 1; }
LENGTH=$(stat -f%z "$ZIP" 2>/dev/null || stat -c%s "$ZIP")
HASH=$(shasum -a 256 "$ZIP" | cut -d' ' -f1 | tr '[:lower:]' '[:upper:]')

python3 - "$VERSION" "$LENGTH" "$HASH" <<'PY'
import re, sys
v, length, h = sys.argv[1:4]
p = 'updates.xml'; s = open(p).read()
url = f'https://github.com/trevorbicewebdesign/Mothership/releases/download/{v}/Mothership-{v}.zip'
s, n = re.subn(r'<version>[^<]*</version>', f'<version>{v}</version>', s, count=1); assert n == 1
s, n = re.subn(r'https://github\.com/[^"<]*/releases/download/[^"<]+\.zip', url, s); assert n == 2, n
s, n = re.subn(r'length="\d*"', f'length="{length}"', s, count=1); assert n == 1
s, n = re.subn(r'hash="[0-9A-Fa-f]*"', f'hash="{h}"', s, count=1); assert n == 1
open(p, 'w').write(s)
print(f'updates.xml -> {v}  length={length}  sha256={h}')
PY
