#!/bin/bash

SCRIPT_DIR=$(cd $(dirname "$0") && pwd -P)

usage()
{
cat << EOF
Usage: pa11y-review.sh uri.dev

ARGUMENTS:
   $URI: URI of site to run pa11y review against.
EOF
}

URI=$1

# Exit if options aren't set.
if [[ -z $URI ]]; then
  usage
  exit 1;
fi

export PATH=$PATH:$(npm config --global get prefix)/bin

# Run pa11y.
pa11y --config=${SCRIPT_DIR}/pa11y.json --standard=WCAG2AA --timeout=120000 --ignore=WCAG2AA.Principle1.Guideline1_4.1_4_3.G18.Fail $URI
