#!/bin/bash
usage()
{
cat << EOF
Usage: ./security-review.sh uri.dev /path/to/docroot

ARGUMENTS:
   $URI: URI of site to run code review against.
   $SITE_PATH: Absolute path to Drupal docroot.
EOF
}

URI=$1
SITE_PATH=$2

# Exit if options aren't set.
if [[ -z $SITE_PATH || -z $URI ]]; then
  usage
  exit 1;
fi

cd $SITE_PATH

# Relatively move to contrib directory.
cd sites/all/modules/contrib

# Get security_review module in case it doesn't exist.
if [ ! -f security_review/security_review.module ]; then
  wget http://ftp.drupal.org/files/projects/security_review-7.x-1.2.tar.gz > /dev/null 2>&1
  tar -xvzf security_review-7.x-1.2.tar.gz > /dev/null 2>&1
  rm security_review-7.x-1.2.tar.gz
fi
cd ../custom
drush en security_review --uri=$URI -y > /dev/null 2>&1
drush cc drush > /dev/null 2>&1

# Run security_reviewq module.
drush security-review --uri=$URI
