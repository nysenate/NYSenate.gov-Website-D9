#!/usr/bin/env bash
#
# Run all cache regression test chunks against a Pantheon multidev via Terminus.
#
# Reads tests/dtt/test-chunks.yml and calls run-on-container.sh once per chunk
# via a separate `terminus remote:drush` invocation. One SSH session per chunk
# prevents Pantheon's connection timeout from aborting a long-running suite and
# keeps peak PHP memory per process well under the container's 2 GB limit.
#
# This script runs on the local machine (or CI runner) — not on the container.
# PANTHEON_TEST_UA must be exported in the environment before calling this
# script (a GitHub Actions secret in CI; set in your shell for local use).
#
# Usage:
#   PANTHEON_TEST_UA=<token> bash tests/dtt/run-all-chunks.sh <site>.<env> <url>
#
# Example:
#   PANTHEON_TEST_UA=<token> bash tests/dtt/run-all-chunks.sh \
#     nysenate-2022.pr-368 \
#     https://pr-368-nysenate-2022.pantheonsite.io

# -uo pipefail but NOT -e: a failing terminus call must not abort the loop
# before remaining chunks run. Exit code accumulation is manual via OVERALL.
set -uo pipefail

SITE="${1:?Usage: $0 <site>.<env> <url>}"
URL="${2:?Usage: $0 <site>.<env> <url>}"

: "${PANTHEON_TEST_UA:?PANTHEON_TEST_UA must be set in the environment}"

# Resolve path to test-chunks.yml relative to this script.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CHUNKS_FILE="${SCRIPT_DIR}/test-chunks.yml"

# Parse test-chunks.yml. Fail immediately if parsing fails so we never
# silently report success with zero tests run.
CHUNKS=$(python3 -c "
import yaml
chunks = yaml.safe_load(open('${CHUNKS_FILE}'))['chunks']
for c in chunks:
    print(c['label'] + '\t' + c['filter'])
") || { echo "ERROR: Failed to parse ${CHUNKS_FILE}"; exit 1; }

if [[ -z "$CHUNKS" ]]; then
  echo "ERROR: test-chunks.yml parsed successfully but contained no chunks"
  exit 1
fi

# Pre-load into arrays before the first terminus call. If terminus opened SSH
# while a while-read loop was still consuming stdin, it would silently drop
# the remaining chunk lines.
LABELS=()
FILTERS=()
while IFS=$'\t' read -r LABEL FILTER; do
  LABELS+=("$LABEL")
  FILTERS+=("$FILTER")
done <<< "$CHUNKS"

# exit() inside drush ev is treated as abnormal termination by Drush, which
# overrides the exit code to 1 regardless of the value passed. Throwing an
# exception instead maps pass/fail cleanly: Drush exits 0 on normal completion
# and 1 on an uncaught exception.
OVERALL=0
for i in "${!LABELS[@]}"; do
  echo "=== ${LABELS[$i]} ==="
  terminus remote:drush "$SITE" -- \
    ev "error_reporting(E_ERROR); putenv('PANTHEON_TEST_UA=${PANTHEON_TEST_UA}'); passthru('bash /code/tests/dtt/run-on-container.sh $URL --filter $(printf '%q' "${FILTERS[$i]}") 2>&1', \$c); if (\$c !== 0) { throw new \Exception('PHPUnit failed with exit code ' . \$c); }" \
    || OVERALL=$?
done
exit $OVERALL
