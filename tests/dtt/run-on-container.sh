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

# If a specific --filter was passed (manual debugging), run as a single
# invocation so the caller's filter is not inadvertently overridden.
if printf '%s\n' "$@" | grep -q -- '--filter'; then
  exec php -d memory_limit=2048M vendor/bin/phpunit \
    -c tests/dtt/phpunit.xml \
    --testsuite existing-site \
    --group cache_regression \
    --testdox \
    --do-not-cache-result \
    "$@"
fi

# Default CI path: run each test class in a separate PHP process.
#
# PHPUnit accumulates Mink session state and Redis-unserialized objects across
# every test in a single process. On Pantheon's 2 GB container this exhausts
# memory partway through the full 64-test suite. Running one class per process
# keeps peak RSS well under 1 GB while total test time is unchanged.
CLASSES=(
  AnonymousCacheHitTest
  AuthenticatedDynamicCacheTest
  CacheMissInvalidationTest
  NoCachePoisoningTest
)

OVERALL=0
for CLASS in "${CLASSES[@]}"; do
  php -d memory_limit=2048M vendor/bin/phpunit \
    -c tests/dtt/phpunit.xml \
    --testsuite existing-site \
    --group cache_regression \
    --testdox \
    --do-not-cache-result \
    --filter "$CLASS" \
    "$@"
  STATUS=$?
  if [[ $STATUS -ne 0 ]]; then OVERALL=$STATUS; fi
done
exit $OVERALL
