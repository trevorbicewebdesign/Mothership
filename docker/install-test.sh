#!/usr/bin/env bash
#
# Put the release zips through Joomla's installer, the way a site owner would, and check
# what each step leaves behind. The other suites run against source mounted into Joomla,
# so nothing else exercises the packages, the update path or the uninstall.
#
#   docker/install-test.sh [<dist-dir>]
#
# <dist-dir> holds the zips to test; without it build/build.sh builds them into dist/
# (that needs a clean working tree). The steps, on a Joomla that has never seen Mothership:
#
#   1. install the previous release, cut from the newest tag below the manifest version
#   2. update it to the build under test: the schema updates must run
#   3. uninstall: no table, menu item, file or extension row may remain
#   4. install the build under test from nothing: the schema must equal the updated one
#   5. install every plugin and module zip
#   6. install the package over all of that, which is what a site is told to install
#
# Runs in its own compose project on random ports and deletes it afterwards. Set KEEP=1
# to leave the stack up for a look around.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

PROJECT=mothership-install-test
export JOOMLA_PORT=127.0.0.1:0
export DB_PORT=127.0.0.1:0
export MOTHERSHIP_RESTART=no
export MOTHERSHIP_LIVE_SITE=

compose() { docker compose -p "$PROJECT" -f docker-compose.yml -f docker/docker-compose.install.yml "$@"; }
joomla_cli() { compose exec -T joomla php /var/www/html/cli/joomla.php "$@"; }
mysql_query() { compose exec -T db mysql -uroot -proot -N -e "$1" 2>/dev/null; }

fail() { echo "install-test: FAILED: $*" >&2; exit 1; }
# check <what> <expected> <actual>
check() {
  [ "$2" = "$3" ] || fail "$1: expected '$2', got '$3'"
  echo "  ok  $1: $3"
}

VERSION=$(sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' mothership.xml | head -1)
[ -n "$VERSION" ] || fail "could not read <version> from mothership.xml"

# The newest release tag that is not the version under test. A shallow clone has no tags.
PREVIOUS=$(git tag --list '[0-9]*.[0-9]*.[0-9]*' --sort=-v:refname | grep -vx "$VERSION" | head -1 || true)
[ -n "$PREVIOUS" ] || fail "no earlier release tag found; fetch tags first (git fetch --tags)"

DIST=${1:-}
if [ -z "$DIST" ]; then
  build/build.sh > /dev/null
  DIST=dist
fi
[ -f "$DIST/Mothership-$VERSION.zip" ] || fail "$DIST/Mothership-$VERSION.zip does not exist"

WORK=$(mktemp -d)
cleanup() {
  rm -rf "$WORK"
  [ -n "${KEEP:-}" ] || compose down --volumes --remove-orphans > /dev/null 2>&1
}
trap cleanup EXIT

# A release zip is `git archive` of its tag (see build/build.sh), so this is that zip.
# api/ arrived with 0.0.27; a release that has it declares it in its manifest.
PREVIOUS_PATHS=(mothership.xml admin site)
if git cat-file -e "$PREVIOUS:api" 2>/dev/null; then
  PREVIOUS_PATHS+=(api)
fi
git archive --format=zip -o "$WORK/Mothership-$PREVIOUS.zip" "$PREVIOUS" "${PREVIOUS_PATHS[@]}"

# What the build under test promises: its tables, and the newest schema update it carries.
# shellcheck disable=SC2016  # the backticks are SQL, not a command substitution
EXPECTED_TABLES=$(sed -n 's/^CREATE TABLE IF NOT EXISTS `#__mothership_\([a-z_]*\)`.*/\1/p' admin/sql/install.mysql.utf8.sql | sort | tr '\n' ' ')
EXPECTED_SCHEMA=$(find admin/sql/updates/mysql -name '*.sql' -exec basename {} .sql \; | sort -V | tail -1)

install_zip() {
  local name
  name=$(basename "$1")
  compose cp "$1" "joomla:/tmp/$name" > /dev/null 2>&1
  # The CLI can exit 0 after printing an installer error, so the message is checked too.
  if ! joomla_cli extension:install --path="/tmp/$name" > "$WORK/cli.log" 2>&1 \
    || ! grep -q 'installed successfully' "$WORK/cli.log"; then
    cat "$WORK/cli.log" >&2
    fail "installing $name"
  fi
}

extension_id() { mysql_query "SELECT extension_id FROM local.jos_extensions WHERE type = 'component' AND element = 'com_mothership'"; }
installed_version() { mysql_query "SELECT JSON_UNQUOTE(JSON_EXTRACT(manifest_cache, '\$.version')) FROM local.jos_extensions WHERE type = 'component' AND element = 'com_mothership'"; }
tables() { mysql_query "SELECT REPLACE(table_name, 'jos_mothership_', '') FROM information_schema.tables WHERE table_schema = 'local' AND table_name LIKE 'jos\\_mothership\\_%' ORDER BY table_name" | tr '\n' ' '; }
schema_version() { mysql_query "SELECT version_id FROM local.jos_schemas WHERE extension_id = '$(extension_id)'"; }
menu_items() { mysql_query "SELECT COUNT(*) FROM local.jos_menu WHERE client_id = 1 AND link LIKE '%option=com_mothership%'"; }
# Every column and index of every Mothership table, for comparing two installs.
schema_dump() {
  mysql_query "SELECT table_name, column_name, column_type, is_nullable, IFNULL(column_default, 'NULL'), extra FROM information_schema.columns WHERE table_schema = 'local' AND table_name LIKE 'jos\\_mothership\\_%' ORDER BY table_name, column_name"
  mysql_query "SELECT table_name, index_name, non_unique, seq_in_index, column_name FROM information_schema.statistics WHERE table_schema = 'local' AND table_name LIKE 'jos\\_mothership\\_%' ORDER BY table_name, index_name, seq_in_index"
}
file_count() { compose exec -T joomla sh -c 'ls -d /var/www/html/administrator/components/com_mothership /var/www/html/components/com_mothership 2>/dev/null | wc -l' | tr -d ' '; }

echo "install-test: Mothership $VERSION, updating from $PREVIOUS"
compose up -d --wait --wait-timeout 180 joomla db > /dev/null 2>&1 || fail "the stack did not start"
check "a Joomla without Mothership" "" "$(tables)"

echo "1. install $PREVIOUS"
install_zip "$WORK/Mothership-$PREVIOUS.zip"
check "installed version" "$PREVIOUS" "$(installed_version)"

echo "2. update to $VERSION"
install_zip "$DIST/Mothership-$VERSION.zip"
check "installed version" "$VERSION" "$(installed_version)"
check "tables" "$EXPECTED_TABLES" "$(tables)"
check "schema version" "$EXPECTED_SCHEMA" "$(schema_version)"
schema_dump > "$WORK/schema-updated.txt"

echo "3. uninstall"
joomla_cli extension:remove "$(extension_id)" --no-interaction > "$WORK/cli.log" 2>&1 || { cat "$WORK/cli.log" >&2; fail "uninstalling"; }
check "extension row" "" "$(extension_id)"
check "tables left behind" "" "$(tables)"
check "admin menu items left behind" "0" "$(menu_items)"
check "component folders left behind" "0" "$(file_count)"

echo "4. install $VERSION from nothing"
install_zip "$DIST/Mothership-$VERSION.zip"
check "installed version" "$VERSION" "$(installed_version)"
check "tables" "$EXPECTED_TABLES" "$(tables)"
check "schema version" "$EXPECTED_SCHEMA" "$(schema_version)"
schema_dump > "$WORK/schema-fresh.txt"
# A column added to install.sql without a matching file in sql/updates (or the reverse)
# shows up here: sites that update would end up with a different database.
diff -u "$WORK/schema-updated.txt" "$WORK/schema-fresh.txt" > "$WORK/schema.diff" \
  || { cat "$WORK/schema.diff" >&2; fail "an updated site (-) and a fresh install (+) have different schemas"; }
echo "  ok  an updated site and a fresh install have the same schema"

echo "5. plugins and modules"
count=0
for zip in "$DIST"/plg_*.zip "$DIST"/mod_*.zip; do
  [ -f "$zip" ] || continue
  install_zip "$zip"
  count=$((count + 1))
done
installed=$(mysql_query "SELECT COUNT(*) FROM local.jos_extensions WHERE (type = 'plugin' AND folder = 'mothership-payment') OR (type = 'plugin' AND folder = 'webservices' AND element = 'mothership') OR (type = 'module' AND element LIKE 'mod\\_mothership\\_%')")
check "plugin and module zips installed" "$count" "$installed"
[ "$count" -gt 0 ] || fail "no plugin or module zips in $DIST"

echo "6. install the package"
[ -f "$DIST/pkg_mothership-$VERSION.zip" ] || fail "$DIST/pkg_mothership-$VERSION.zip does not exist"
install_zip "$DIST/pkg_mothership-$VERSION.zip"
check "package version" "$VERSION" "$(mysql_query "SELECT JSON_UNQUOTE(JSON_EXTRACT(manifest_cache, '\$.version')) FROM local.jos_extensions WHERE type = 'package' AND element = 'pkg_mothership'")"
check "installed version" "$VERSION" "$(installed_version)"
check "tables" "$EXPECTED_TABLES" "$(tables)"

echo "install-test: passed"
