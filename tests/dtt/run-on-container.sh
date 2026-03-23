#!/usr/bin/env bash
#
# Run PHPUnit cache regression tests on the Pantheon appserver container.
#
# This script is invoked by the CI workflow via:
#   terminus remote:drush <site>.<env> -- ev "passthru('bash /code/tests/dtt/run-on-container.sh <url>', \$c); exit(\$c);"
#
# It must run ON the Pantheon container (not the CI runner) so that the test
# process shares the same DB and Redis instance as the web server. Drupal's
# cache tag invalidation writes checksums to Redis; if the test process and
# web server use different Redis instances, cache MISS assertions never trigger.
#
# Usage:
#   bash /code/tests/dtt/run-on-container.sh <DTT_BASE_URL> [phpunit-extra-args...]
#
# Examples:
#   bash /code/tests/dtt/run-on-container.sh https://pr-123-nysenate-2022.pantheonsite.io
#   bash /code/tests/dtt/run-on-container.sh https://... --filter testHomepageMissOnArticleEdit

set -euo pipefail

DTT_BASE_URL="${1:?Usage: $0 <DTT_BASE_URL> [phpunit-extra-args...]}"
shift

cd /code

export DTT_BASE_URL

exec vendor/bin/phpunit \
  -c tests/dtt/phpunit.xml \
  --testsuite existing-site \
  --group cache_regression \
  --testdox \
  "$@"
