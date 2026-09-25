#!/usr/bin/env bash
#
# Set the release version everywhere it is recorded, then show the diff.
#
#   build/set-version.sh 0.0.25
#
# Touches mothership.xml (attribute and element), composer.json, and the version and
# download URLs in updates.xml. The package length and hash in updates.xml are filled
# in by build/update-server.sh once the zip exists (the release workflow does this).
# Nothing is committed; review the diff, then commit, tag, and push:
#
#   git commit -am "Release 0.0.25" && git tag 0.0.25 && git push origin main 0.0.25
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

VERSION=${1:-}
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "usage: build/set-version.sh <major.minor.patch>" >&2; exit 1; }

python3 - "$VERSION" <<'PY'
import json, re, sys
v = sys.argv[1]

p = 'mothership.xml'; s = open(p).read()
s, n1 = re.subn(r'(<extension\b[^>]*\bversion=")[^"]*(")', r'\g<1>' + v + r'\2', s, count=1)
s, n2 = re.subn(r'<version>[^<]*</version>', f'<version>{v}</version>', s, count=1)
assert n1 == 1 and n2 == 1, 'mothership.xml: version markers not found'
open(p, 'w').write(s)

p = 'composer.json'; s = open(p).read()
s, n = re.subn(r'("version":\s*")[^"]*(")', r'\g<1>' + v + r'\2', s, count=1)
assert n == 1, 'composer.json: version not found'
open(p, 'w').write(s)

p = 'updates.xml'; s = open(p).read()
s, n = re.subn(r'<version>[^<]*</version>', f'<version>{v}</version>', s, count=1)
assert n == 1, 'updates.xml: version not found'
s, n = re.subn(r'releases/download/[^/]+/Mothership-[^"<]+\.zip', f'releases/download/{v}/Mothership-{v}.zip', s)
assert n == 2, f'updates.xml: expected 2 download URLs, found {n}'
open(p, 'w').write(s)
print(f'version set to {v}')
PY

git --no-pager diff --stat
