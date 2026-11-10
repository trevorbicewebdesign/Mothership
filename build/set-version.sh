#!/usr/bin/env bash
#
# Set the release version everywhere it is recorded, then show the diff.
#
#   build/set-version.sh 0.0.25
#
# Touches mothership.xml (attribute and element), package/pkg_mothership.xml and
# composer.json. updates.xml is left alone: it is the live update server, and it is
# pointed at the release by build/update-server.sh once the zip exists (the release
# workflow does this).
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

p = 'package/pkg_mothership.xml'; s = open(p).read()
s, n = re.subn(r'<version>[^<]*</version>', f'<version>{v}</version>', s, count=1)
assert n == 1, 'package/pkg_mothership.xml: version not found'
open(p, 'w').write(s)
print(f'version set to {v}')
PY

git --no-pager diff --stat
