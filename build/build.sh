#!/usr/bin/env bash
#
# Build the installable zips for a release into dist/.
#
#   build/build.sh
#
# Produces, using the version in mothership.xml:
#   dist/pkg_mothership-<v>.zip                  the package: everything below except the
#                                                tickets module, in one install; updates.xml
#                                                points here
#   dist/Mothership-<v>.zip                      the component
#   dist/plg_mothership-payment_<name>-<v>.zip   one per payment plugin
#   dist/plg_mothership-ai_<name>-<v>.zip        one per AI connector plugin
#   dist/plg_webservices_mothership-<v>.zip      the web services (API) plugin
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
# incomplete. Folders under admin/, site/ and api/ that the manifest does not declare are reported
# too, since they will not be installed.
python3 - <<'PY'
import os, re, sys
x = open('mothership.xml').read()
missing, declared = [], {'admin': set(), 'site': set(), 'api': set()}
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
git archive --format=zip -o "dist/Mothership-$VERSION.zip" HEAD mothership.xml admin site api

# Plugin and module zips need their manifest at the zip root, so archive each directory's tree.
for dir in plugins/mothership-payment/*/; do
  name=$(basename "$dir")
  echo "plugin     dist/plg_mothership-payment_${name}-$VERSION.zip"
  git archive --format=zip -o "dist/plg_mothership-payment_${name}-$VERSION.zip" "HEAD:${dir%/}"
done

for dir in plugins/mothership-ai/*/; do
  name=$(basename "$dir")
  echo "plugin     dist/plg_mothership-ai_${name}-$VERSION.zip"
  git archive --format=zip -o "dist/plg_mothership-ai_${name}-$VERSION.zip" "HEAD:${dir%/}"
done

echo "plugin     dist/plg_webservices_mothership-$VERSION.zip"
git archive --format=zip -o "dist/plg_webservices_mothership-$VERSION.zip" "HEAD:plugins/webservices/mothership"

for dir in modules/mod_mothership_*/; do
  name=$(basename "$dir")
  echo "module     dist/${name}-$VERSION.zip"
  git archive --format=zip -o "dist/${name}-$VERSION.zip" "HEAD:${dir%/}"
done

# The package manifest names the zips it installs; each is one of the zips built above,
# stored under a name without the version so the manifest does not change per release.
echo "package    dist/pkg_mothership-$VERSION.zip"
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
git archive --format=tar HEAD:package | tar -x -C "$STAGE"
python3 - "$STAGE" "$VERSION" "$(git log -1 --format=%ct)" <<'PY'
import os, re, shutil, sys, time, zipfile
stage, v, committed = sys.argv[1], sys.argv[2], int(sys.argv[3])
x = open(os.path.join(stage, 'pkg_mothership.xml')).read()
pv = re.search(r'<version>([^<]*)</version>', x).group(1)
if pv != v:
    sys.exit(f'build: package/pkg_mothership.xml is version {pv} but mothership.xml is {v}. Run build/set-version.sh {v}.')
for name in re.findall(r'<file\b[^>]*>([^<]+)</file>', x):
    built = f'Mothership-{v}.zip' if name == 'com_mothership.zip' else name.replace('.zip', f'-{v}.zip')
    if not os.path.exists(os.path.join('dist', built)):
        sys.exit(f'build: package/pkg_mothership.xml lists {name}, but dist/{built} was not built')
    shutil.copy(os.path.join('dist', built), os.path.join(stage, name))
# Fixed order and dates, so the same commit always builds the same package.
date = time.gmtime(committed)[:6]
with zipfile.ZipFile(f'dist/pkg_mothership-{v}.zip', 'w') as z:
    for name in sorted(os.listdir(stage)):
        info = zipfile.ZipInfo(name, date)
        info.external_attr = 0o644 << 16
        info.compress_type = zipfile.ZIP_STORED if name.endswith('.zip') else zipfile.ZIP_DEFLATED
        z.writestr(info, open(os.path.join(stage, name), 'rb').read())
PY

echo
echo "size        sha256                                                            file"
for f in dist/*.zip; do
  size=$(stat -f%z "$f" 2>/dev/null || stat -c%s "$f")
  printf '%-11s %s  %s\n' "$size" "$(shasum -a 256 "$f" | cut -d' ' -f1)" "$f"
done
