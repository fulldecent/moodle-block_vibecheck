#!/bin/bash
# Run lint, PHPUnit, and Behat for block_vibecheck in Apple container.
# Postgres runs in a second container on the vibecheck network. Docker is not used.

set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
log="${VIBECHECK_LOG:-/tmp/vibecheck-apple-container-test.log}"
mkdir -p "$(dirname "$log")"
exec > >(tee "$log") 2>&1

echo "Log: $log"
echo "Plugin: $root"
echo $$ > /tmp/vibecheck-apple-container-test.pid

if ! container image list | awk 'NR>1 {print $1":"$2}' | grep -qx 'vibecheck-php:8.3'; then
    container build -t vibecheck-php:8.3 -f "$root/testing/Containerfile" "$root/testing"
fi

container network create vibecheck >/dev/null 2>&1 || true

if ! container list --all 2>/dev/null | awk 'NR>1 {print $1}' | grep -qx 'vibecheck-db'; then
    container run -d --name vibecheck-db --network vibecheck \
        -e POSTGRES_HOST_AUTH_METHOD=trust \
        -e POSTGRES_USER=moodle \
        -e POSTGRES_DB=moodle \
        -m 2G \
        postgres:17
fi

ready=0
for _ in $(seq 1 60); do
    if container exec vibecheck-db pg_isready -U moodle >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 2
done
if [[ "$ready" -ne 1 ]]; then
    echo "Postgres did not become ready"
    container logs vibecheck-db || true
    exit 1
fi

# moodle-plugin-ci creates the database. The Postgres image already created it.
container exec vibecheck-db psql -U moodle -d postgres -v ON_ERROR_STOP=1 -c 'DROP DATABASE IF EXISTS moodle;'
container exec vibecheck-db psql -U moodle -d postgres -v ON_ERROR_STOP=1 -c 'DROP DATABASE IF EXISTS moodle_behat;' || true

if ! container list --all 2>/dev/null | awk 'NR>1 {print $1}' | grep -qx 'vibecheck-selenium'; then
    container run -d --name vibecheck-selenium --network vibecheck \
        --shm-size 2G -m 3G \
        selenium/standalone-chrome:4
fi

# Apple container 1.5 does not resolve container names. Use the address inspect reports.
dbip="$(container inspect vibecheck-db | python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["status"]["networks"][0]["ipv4Address"].split("/")[0])')"
echo "Postgres address: $dbip"

selready=0
selip=""
for _ in $(seq 1 90); do
    if container exec vibecheck-selenium curl -fsS http://127.0.0.1:4444/status >/dev/null 2>&1; then
        selip="$(container inspect vibecheck-selenium | python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["status"]["networks"][0]["ipv4Address"].split("/")[0])')"
        selready=1
        break
    fi
    sleep 2
done
if [[ "$selready" -ne 1 ]]; then
    echo "Selenium did not become ready"
    container logs vibecheck-selenium || true
    exit 1
fi
echo "Selenium address: $selip"

container volume create vibecheck-ci >/dev/null 2>&1 || true
container rm -f vibecheck-ci >/dev/null 2>&1 || true
container run --rm --name vibecheck-ci --network vibecheck \
    --mount "type=bind,source=${root},target=/plugin" \
    --mount "type=volume,source=vibecheck-ci,target=/ci" \
    -m 8G -c 6 \
    -e MOODLE_BRANCH=MOODLE_502_STABLE \
    -e DB=pgsql \
    -e DB_HOST="$dbip" \
    -e DB_USER=moodle \
    -e DB_NAME=moodle \
    -e SELENIUM_HOST="$selip" \
    -e MOODLE_BEHAT_DEFAULT_BROWSER=chrome \
    -e "MOODLE_BEHAT_CHROME_CAPABILITIES=['extra_capabilities' => ['chromeOptions' => ['args' => ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu']]]]" \
    -w /ci \
    vibecheck-php:8.3 \
    bash /plugin/testing/ci-inside.sh

echo "All requested checks finished."
