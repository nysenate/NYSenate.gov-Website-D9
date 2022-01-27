#!/bin/bash -e
usage()
{
cat << EOF
Usage: ./drupal-check.sh /path/to/docroot

ARGUMENTS:
   $SITE_PATH: Path to Drupal docroot.
EOF
}

SITE_PATH=$1

# Exit if options aren't set.
if [[ -z $SITE_PATH ]]; then
  usage
  exit 1;
fi

if [ -f ./vendor/bin/drupal-check ]; then
  DRUPALCHECK='./vendor/bin/drupal-check'
elif [ -f ../vendor/bin/drupal-check ]; then
  DRUPALCHECK='../vendor/bin/drupal-check'
else
  DRUPALCHECK='drupal-check'
fi

if [ -d ${SITE_PATH}/modules/custom ]; then
  echo "Running drupal-check for custom modules."
  ${DRUPALCHECK} ${SITE_PATH}/modules/custom
fi

if [ -d ${SITE_PATH}/profiles/custom ]; then
  echo "Running drupal-check for custom profiles."
  ${DRUPALCHECK} ${SITE_PATH}/profiles/custom
fi

if [ -d ${SITE_PATH}/themes/custom ]; then
  echo "Running drupal-check for custom themes."
  ${DRUPALCHECK} ${SITE_PATH}/themes/custom
fi
