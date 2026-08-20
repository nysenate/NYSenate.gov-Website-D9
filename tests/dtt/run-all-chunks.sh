#!/usr/bin/env bash
#
# Run all cache regression test chunks against a Pantheon multidev via Terminus.
#
#
# Reads test-chunks.yml and calls run-on-container.sh once per chunk
# via a separate `terminus remote:drush` invocation. One SSH session per chunk
# prevents Pantheon's connection timeout from aborting a long-running suite and
# keeps peak PHP memory per process well within the container's available RAM.
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

# New Relic is the root cause of the prior SIGKILL (exit 137): its PHP
# extension instruments every function call in the Drupal codebase and fills
# the 9 GB container cgroup with transaction trace data. The fix is
# -d newrelic.enabled=0 in run-on-container.sh — not process replacement here.
OVERALL=0
for i in "${!LABELS[@]}"; do
  echo "=== ${LABELS[$i]} ==="
  terminus remote:drush "$SITE" -- \
    ev "error_reporting(E_ERROR); putenv('PANTHEON_TEST_UA=${PANTHEON_TEST_UA}'); passthru('/code/tests/dtt/run-on-container.sh ${URL} --filter ${FILTERS[$i]}');" \
    || OVERALL=$?
done
exit $OVERALL
