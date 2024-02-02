#!/bin/bash

SCRIPT_DIR=$(cd $(dirname "$0")/../scripts && pwd -P)
BASE_DIR=$(cd $(dirname "$0")/../../../.. && pwd -P)

cd ${BASE_DIR}

COMPOSER_INSTALLED=$(command -v composer)

if [ -z "${COMPOSER_INSTALLED}" ]; then
  echo "Composer ( https://getcomposer.org/) not found, please install."
  exit 1;
fi

if [ ! -d ${BASE_DIR}/vendor/mediacurrent/ci-scripts ]; then
  composer install
fi

CMD=$@
# Default to run "site:build" if no commands provided.
if [ -z "${CMD}" ]; then
  CMD="site:build -Dexisting_config --verbose"
  if [ -z "$(ls -A ${BASE_DIR}/config/sync/*.yml 2>/dev/null)" ]; then
    CMD="site:build"
  fi
fi

./vendor/bin/hobson ${CMD}
