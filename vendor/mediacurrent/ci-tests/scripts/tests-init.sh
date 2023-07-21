#!/bin/bash

VENDOR_DIR=$1

# Exit if options aren't set.
if [[ "web/vendor" != ${VENDOR_DIR} ]]; then
  VENDOR_DIR="vendor"
fi

rsync -avz --ignore-existing ./${VENDOR_DIR}/mediacurrent/ci-tests/tests ./

if [[ ! -f ./tests/behat/behat.local.yml ]]; then
  cp ./tests/behat/behat.local.yml.example ./tests/behat/behat.local.yml
fi
