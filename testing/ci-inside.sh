#!/bin/bash
# Runs inside vibecheck-ci. The plugin is mounted at /plugin. moodle-plugin-ci lives on the volume at /ci/mpc.

set -euo pipefail

if [[ ! -x /ci/mpc/bin/moodle-plugin-ci ]]; then
    composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci /ci/mpc ^4
fi
export PATH="/ci/mpc/bin:/ci/mpc/vendor/bin:${PATH}"

# A previous install writes this file. Shell variables set below must win.
rm -f /ci/mpc/.env
rm -rf /ci/moodle /ci/moodledata

selfip="$(hostname -i | awk '{print $1}')"
export MOODLE_BEHAT_WWWROOT="http://${selfip}:8000"
export MOODLE_BEHAT_WDHOST="http://${SELENIUM_HOST}:4444/wd/hub"
echo "Behat wwwroot: ${MOODLE_BEHAT_WWWROOT}"
echo "Behat webdriver: ${MOODLE_BEHAT_WDHOST}"

cd /ci
moodle-plugin-ci install --plugin /plugin --db-host="${DB_HOST}" --db-user=moodle --db-name=moodle

# Mustache lint rejects a path outside the Moodle tree. Install symlinks /plugin in, so replace that link with a copy.
if [[ -d /ci/moodle/public/blocks ]]; then
    installed=/ci/moodle/public/blocks/vibecheck
else
    installed=/ci/moodle/blocks/vibecheck
fi
if [[ -L "${installed}" ]]; then
    rm "${installed}"
    cp -a /plugin "${installed}"
fi

moodle-plugin-ci phplint "${installed}"
moodle-plugin-ci codechecker "${installed}"
moodle-plugin-ci savepoints "${installed}"
moodle-plugin-ci mustache "${installed}"
moodle-plugin-ci phpdoc "${installed}"
moodle-plugin-ci phpunit "${installed}" --fail-on-warning

if [[ -d /ci/moodle/public ]]; then
    webroot=/ci/moodle/public
else
    webroot=/ci/moodle
fi
php -S 0.0.0.0:8000 -t "${webroot}" >/tmp/vibecheck-php-server.log 2>&1 &
echo $! >/tmp/vibecheck-php-server.pid
sleep 1
export MOODLE_START_BEHAT_SERVERS=NO
moodle-plugin-ci behat "${installed}" --profile chrome
