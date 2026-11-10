#!/usr/bin/env bash
#
# Run an isolated copy of the docker-compose.yml stack for this checkout, so several
# checkouts (git worktrees, one per unit of work) can each have their own Joomla and
# database at the same time.
#
#   docker/instance.sh [-n <name>] up [--selenium]   start, install Mothership, print the URLs
#   docker/instance.sh [-n <name>] url               print the URLs again
#   docker/instance.sh [-n <name>] test <suite> ...  codecept run <suite> ... inside the stack
#   docker/instance.sh [-n <name>] compose ...       any other docker compose command
#   docker/instance.sh [-n <name>] down              stop and delete the stack and its database
#   docker/instance.sh list                          every instance on this machine
#
# <name> defaults to the checkout's folder name, so each worktree gets its own instance
# without naming it. The compose project is mothership-<name> (a folder already called
# mothership-<name> is not prefixed twice).
#
# Unlike a plain `docker compose up`, an instance publishes every port on a random free
# port of 127.0.0.1, does not restart with Docker, and leaves $live_site empty so that
# Joomla answers on whatever port it was given. Inside the stack nothing changes: the
# tests still reach it as http://joomla.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

usage() { sed -n '3,13p' "$0" | sed 's/^# \{0,1\}//' >&2; exit 1; }

NAME=$(basename "$PWD")
if [ "${1:-}" = "-n" ]; then
  [ -n "${2:-}" ] || usage
  NAME=$2; shift 2
fi
# Compose project names allow only lowercase letters, digits, dashes and underscores.
NAME=$(printf '%s' "$NAME" | tr '[:upper:]' '[:lower:]' | tr -c 'a-z0-9_-' '-')
# A worktree folder is usually already called mothership-<something>.
PROJECT="mothership-${NAME#mothership-}"

COMMAND=${1:-}
[ -n "$COMMAND" ] || usage
shift

# The same throwaway admin the CI workflow creates.
ADMIN_USER='admin'
ADMIN_PASSWORD='password123!test'

export JOOMLA_PORT=127.0.0.1:0
export DB_PORT=127.0.0.1:0
export SELENIUM_PORT=127.0.0.1:0
export MAILPIT_UI_PORT=127.0.0.1:0
export MAILPIT_SMTP_PORT=127.0.0.1:0
export MOTHERSHIP_RESTART=no
export MOTHERSHIP_LIVE_SITE=

compose() { docker compose -p "$PROJECT" "$@"; }
joomla_cli() { compose exec -T joomla php /var/www/html/cli/joomla.php "$@"; }
mysql_query() { compose exec -T db mysql -uroot -proot -N -e "$1" 2>/dev/null; }

# Where a service's container port is published on the host, e.g. 127.0.0.1:55012.
published() { compose port "$1" "$2" 2>/dev/null || true; }

print_urls() {
  local site mail db
  site=$(published joomla 80); mail=$(published mailpit 8025); db=$(published db 3306)
  [ -n "$site" ] || { echo "instance: $PROJECT is not running. Start it with: docker/instance.sh -n $NAME up" >&2; exit 1; }
  echo "instance: $PROJECT"
  echo "  site     http://$site/"
  echo "  admin    http://$site/administrator/  ($ADMIN_USER / $ADMIN_PASSWORD)"
  echo "  mailpit  http://$mail/"
  echo "  mysql    $db  (root / root, database local)"
}

# The steps the CI workflow runs after the services are healthy. The admin user is
# created last, so its presence means the whole setup finished.
install_mothership() {
  [ "$(mysql_query "SELECT COUNT(*) FROM local.jos_users WHERE username = '$ADMIN_USER'")" = "0" ] || return 0

  echo "instance: installing Mothership into $PROJECT"
  joomla_cli extension:discover
  joomla_cli extension:discover:install
  mysql_query "UPDATE local.jos_extensions SET enabled = 1 WHERE type = 'plugin' AND folder = 'mothership-payment' AND element IN ('zelle', 'paybycheck');"
  joomla_cli user:add --username="$ADMIN_USER" --name='Site Admin' --password="$ADMIN_PASSWORD" --email='admin@example.com' --usergroup='Super Users' --quiet

  # The Codeception Db module reloads this before each test.
  mkdir -p tests/_support/Data/db
  compose exec -T db mysqldump -uroot -proot --no-tablespaces --skip-comments --extended-insert=false local \
    > tests/_support/Data/db/dump.sql 2>/dev/null
}

case "$COMMAND" in
  up)
    SERVICES=(joomla db mailpit)
    case "${1:-}" in
      --selenium) SERVICES+=(selenium) ;;
      "") ;;
      *) usage ;;
    esac
    [ -f tests/.env ] || cp tests/.env.sample tests/.env
    # docker-compose.yml mounts the manifest over this path inside the ./admin mount.
    # Docker Desktop cannot create that mountpoint itself, so a fresh checkout needs it.
    [ -e admin/mothership.xml ] || cp mothership.xml admin/mothership.xml
    compose up -d --wait --wait-timeout 180 "${SERVICES[@]}"
    install_mothership
    print_urls
    ;;
  url)
    print_urls
    ;;
  test)
    [ -n "${1:-}" ] || usage
    # Only the browser suite needs Selenium; naming the service enables its profile.
    if [ "$1" = "acceptance" ]; then
      compose up -d --wait --wait-timeout 180 selenium
    fi
    compose exec -T joomla /var/www/html/vendor/bin/codecept run "$@"
    ;;
  compose)
    compose "$@"
    ;;
  down)
    compose --profile selenium down --volumes --remove-orphans
    ;;
  list)
    docker compose ls --all --filter name=mothership-
    ;;
  *)
    usage
    ;;
esac
