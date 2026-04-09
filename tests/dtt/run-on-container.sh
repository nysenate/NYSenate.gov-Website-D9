#!/usr/bin/env bash
#
# Run PHPUnit cache regression tests on the Pantheon appserver container.
#
# This script is invoked by the CI workflow via:
#   terminus remote:drush <site>.<env> -- ev "passthru('bash /code/tests/dtt/run-on-container.sh <url>', \$c); exit(\$c);"
#
# It must run ON the Pantheon container (not the CI runner) so that the test
# process shares the same DB and Redis instance as the web server.
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

# Default CI path: run each chunk in a separate PHP process.
#
# Chunk definitions live in tests/dtt/test-chunks.yml — the single source of
# truth for both this script and the local DDEV command. Edit that file to add,
# remove, or rebalance chunks; no changes are needed here.
#
# symfony/yaml (already required by Drupal core) is used to parse the YAML
# so no additional tooling is required on the Pantheon container.

CHUNKS_FILE="tests/dtt/test-chunks.yml"

# Read label\tfilter pairs from the YAML file.
mapfile -t CHUNK_LINES < <(php -r "
  require 'vendor/autoload.php';
  \$chunks = \Symfony\Component\Yaml\Yaml::parseFile('$CHUNKS_FILE')['chunks'];
  foreach (\$chunks as \$c) { echo \$c['label'] . \"\t\" . \$c['filter'] . PHP_EOL; }
")

OVERALL=0
for line in "${CHUNK_LINES[@]}"; do
  LABEL="${line%%$'\t'*}"
  FILTER="${line#*$'\t'}"
  echo ""
  echo "=== $LABEL ==="
  set +e
  php -d memory_limit=2048M vendor/bin/phpunit \
    -c tests/dtt/phpunit.xml \
    --testsuite existing-site \
    --group cache_regression \
    --testdox \
    --do-not-cache-result \
    --filter "$FILTER" \
    "$@"
  STATUS=$?
  set -e
  if [[ $STATUS -ne 0 ]]; then OVERALL=$STATUS; fi
done
exit $OVERALL
