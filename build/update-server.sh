#!/usr/bin/env bash
#
# Point updates.xml at a built package zip.
#
#   build/update-server.sh dist/pkg_mothership-0.0.28.zip
#
# Rewrites the version, both download URLs, the <sha256> element Joomla checks, and the
# enclosure length and hash so Joomla's updater accepts the published asset. Run after the
# zip is uploaded to the GitHub release, since the update servers are the copies of these
# files on main.
#
# There are two files because Joomla reads only one extension from an update server of
# this kind (it keeps the newest entry in the file, whatever it is for):
#
#   updates-package.xml  the package's update server. Moves with every release. Created
#                        here by the first package release.
#   updates.xml          the update server of sites that installed the component on its
#                        own, before there was a package. It is pointed at the first
#                        package release and then never changed, so those sites are
#                        offered one update, which installs the package, and from then on
#                        follow updates-package.xml like everyone else.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

ZIP=${1:-}
[ -f "$ZIP" ] || { echo "usage: build/update-server.sh dist/pkg_mothership-<version>.zip" >&2; exit 1; }

NAME=$(basename "$ZIP")
VERSION=${NAME#pkg_mothership-}; VERSION=${VERSION%.zip}
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "update-server: cannot read a version from '$NAME'; expected pkg_mothership-<version>.zip" >&2; exit 1; }
LENGTH=$(stat -f%z "$ZIP" 2>/dev/null || stat -c%s "$ZIP")
HASH=$(shasum -a 256 "$ZIP" | cut -d' ' -f1 | tr '[:lower:]' '[:upper:]')

python3 - "$VERSION" "$LENGTH" "$HASH" <<'PY'
import os, re, sys
v, length, h = sys.argv[1:4]
url = f'https://github.com/trevorbicewebdesign/Mothership/releases/download/{v}/pkg_mothership-{v}.zip'

def point(s):
    s, n = re.subn(r'<version>[^<]*</version>', f'<version>{v}</version>', s, count=1); assert n == 1
    s, n = re.subn(r'https://github\.com/[^"<]*/releases/download/[^"<]+\.zip', url, s); assert n == 2, n
    s, n = re.subn(r'length="\d*"', f'length="{length}"', s, count=1); assert n == 1
    s, n = re.subn(r'hash="[0-9A-Fa-f]*"', f'hash="{h}"', s, count=1); assert n == 1
    # Joomla only verifies the <sha256> element (lowercase hex); the enclosure hash attribute is ignored.
    s, n = re.subn(r'<sha256>[0-9a-fA-F]*</sha256>', f'<sha256>{h.lower()}</sha256>', s, count=1); assert n == 1, 'no <sha256> element'
    return s

component = open('updates.xml').read()
assert component.count('<update>') == 1 and '<element>com_mothership</element>' in component, 'updates.xml must hold the one com_mothership entry'

if os.path.exists('updates-package.xml'):
    package = open('updates-package.xml').read()
else:
    # The first package release: the package's entry starts as a copy of the component's.
    # A package is not tied to the administrator, and Joomla matches an entry to an
    # installed extension by client as well as by element and type.
    package = component.replace('<element>com_mothership</element>', '<element>pkg_mothership</element>')
    package = package.replace('<type>component</type>', '<type>package</type>')
    package = package.replace('<client>administrator</client>', '<client>site</client>')
assert package.count('<update>') == 1 and all(t in package for t in ('<element>pkg_mothership</element>', '<type>package</type>', '<client>site</client>')), 'updates-package.xml must hold the one pkg_mothership entry'
open('updates-package.xml', 'w').write(point(package))
print(f'updates-package.xml -> {v}  length={length}  sha256={h}')

if 'pkg_mothership-' not in component:
    open('updates.xml', 'w').write(point(component))
    print(f'updates.xml -> {v}: component-only sites are now offered the package')
PY
