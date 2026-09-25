#!/usr/bin/env bash
#
# Build the installable zips for a release into dist/.
#
#   build/build.sh
#
# Produces, using the version in mothership.xml:
#   dist/Mothership-<v>.zip                      the component; updates.xml points here
#   dist/plg_mothership-payment_<name>-<v>.zip   one per payment plugin
#   dist/mod_mothership_<name>-<v>.zip           one per admin module
#
# Every zip is cut from HEAD with `git archive`, so only committed files ship and the
# export-ignore rules in .gitattributes apply. This reproduces the old manual process
# (GitHub source archive, re-zipped without the top-level folder) exactly.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

VERSION=$(sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' mothership.xml | head -1)
[ -n "$VERSION" ] || { echo "build: could not read <version> from mothership.xml" >&2; exit 1; }

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "build: the working tree has uncommitted changes. git archive packages HEAD, so commit first." >&2
  exit 1
fi

# Everything the component manifest declares must exist; a missing folder installs silently
# incomplete. Folders under admin/ and site/ that the manifest does not declare are reported
# too, since they will not be installed.
python3 - <<'PY'
import os, re, sys
x = open('mothership.xml').read()
missing, declared = [], {'admin': set(), 'site': set()}
for m in re.finditer(r'<files folder="(\w+)">(.*?)</files>', x, re.S):
    base = m.group(1)
    for tag, name in re.findall(r'<(folder|filename)>([^<]+)</\1>', m.group(2)):
        declared[base].add(name)
        if not os.path.exists(os.path.join(base, name)):
            missing.append(os.path.join(base, name))
for m in re.finditer(r'<languages folder="(\w+)">(.*?)</languages>', x, re.S):
    declared[m.group(1)].add('language')
    for name in re.findall(r'>([^<]+)</language>', m.group(2)):
        p = os.path.join(m.group(1), name.lstrip('/'))
        if not os.path.exists(p):
            missing.append(p)
if missing:
    print('build: mothership.xml declares paths that do not exist:', file=sys.stderr)
    for p in missing: print('  ' + p, file=sys.stderr)
    sys.exit(1)
for base, names in declared.items():
    for entry in sorted(os.listdir(base)):
        if os.path.isdir(os.path.join(base, entry)) and entry not in names:
            print(f'build: note: {base}/{entry}/ is not declared in mothership.xml and will not be installed')
PY

rm -rf dist
mkdir -p dist

echo "component  dist/Mothership-$VERSION.zip"
git archive --format=zip -o "dist/Mothership-$VERSION.zip" HEAD mothership.xml admin site

# Plugin and module zips need their manifest at the zip root, so archive each directory's tree.
for dir in plugins/mothership-payment/*/; do
  name=$(basename "$dir")
  echo "plugin     dist/plg_mothership-payment_${name}-$VERSION.zip"
  git archive --format=zip -o "dist/plg_mothership-payment_${name}-$VERSION.zip" "HEAD:${dir%/}"
done

for dir in modules/mod_mothership_*/; do
  name=$(basename "$dir")
  echo "module     dist/${name}-$VERSION.zip"
  git archive --format=zip -o "dist/${name}-$VERSION.zip" "HEAD:${dir%/}"
done

echo
echo "size        sha256                                                            file"
for f in dist/*.zip; do
  size=$(stat -f%z "$f" 2>/dev/null || stat -c%s "$f")
  printf '%-11s %s  %s\n' "$size" "$(shasum -a 256 "$f" | cut -d' ' -f1)" "$f"
done
