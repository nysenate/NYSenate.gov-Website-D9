#!/usr/bin/env bash
#
# Run PHPUnit cache regression tests on the Pantheon appserver container.
#
# The CI workflow invokes this script once per chunk via terminus:
#   terminus remote:drush <site>.<env> -- ev "putenv('PANTHEON_TEST_UA=...'); putenv('DTT_BASE_URL=...'); passthru('/code/tests/dtt/run-on-container.sh <url> --filter <filter>');"
#
# Key PHP flags used here:
#   -d newrelic.enabled=0       Disable New Relic instrumentation. Without this, the New Relic
#                               PHP extension hooks into every function call across the entire
#                               Drupal 11 codebase and fills the 9 GB container cgroup with its
#                               transaction trace buffer, triggering SIGKILL (exit 137).
#   -d opcache.enable_cli=1     Enable OPcache for the CLI process (speeds up class loading).
#   -d memory_limit=512M        Generous ceiling; tests use ~40 MB in practice.
#
# Chunking and chunk iteration are handled by tests/dtt/run-all-chunks.sh
# (used by CI and for manual Pantheon runs) and by .ddev/commands/web/run-cache-tests
# for local DDEV runs. This script's sole job is to provide a stable, correctly-quoted
# PHPUnit invocation on the container and to serve as a documented entry point for
# manual debugging via SSH:
#   bash /code/tests/dtt/run-on-container.sh https://pr-123-nysenate-2022.pantheonsite.io --filter testFoo
#
# It must run ON the Pantheon container (not the CI runner) so that the test
# process shares the same DB and Redis instance as the web server.
#
# Usage:
#   bash /code/tests/dtt/run-on-container.sh <DTT_BASE_URL> [phpunit-extra-args...]

set -euo pipefail

DTT_BASE_URL="${1:?Usage: $0 <DTT_BASE_URL> [phpunit-extra-args...]}"
shift

cd /code

export DTT_BASE_URL

exec php -d newrelic.enabled=0 -d opcache.enable_cli=1 -d memory_limit=512M vendor/bin/phpunit \
  -c tests/dtt/phpunit.xml \
  --testsuite existing-site \
  --group cache_regression \
  --testdox \
  --colors=never \
  --do-not-cache-result \
  "$@"
