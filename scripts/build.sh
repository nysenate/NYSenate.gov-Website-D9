#!/bin/bash

# SITE_DOMAIN="visittheusa.mcdev"
# SITE_DATABASE="visittheusa_mcdev"
# SITE_NAME="Visit The USA"

SCRIPT_DIR=$(cd $(dirname "$0") && pwd -P)
BASE_DIR=$(cd ${SCRIPT_DIR}/.. && pwd -P)

cd ${BASE_DIR}

# Ensure required packages are installed
composer install

if [ -f ${BASE_DIR}/vendor/mediacurrent/ci-scripts/scripts/vagrant-init.sh ]; then
  echo "Ensure the vagrant supporting files are installed."
  ${BASE_DIR}/vendor/mediacurrent/ci-scripts/scripts/vagrant-init.sh ${BASE_DIR};
fi

source ${SCRIPT_DIR}/common.sh

SITE_DOMAIN=${DRUPALVM_vagrant_hostname}
SITE_DATABASE=${DRUPALVM_vagrant_machine_name}

VAGRANT_INSTALLED=$(command -v vagrant)
VAGRANT_UP=$(vagrant status | grep "The VM is running")

# start vagrant if required
if [ ! -z "${VAGRANT_INSTALLED}" ] && [ -z "${VAGRANT_UP}" ]; then
  echo "starting Vagrant"
  vagrant up
fi

SITE_INSTALL_CMD="site-install ${DRUPALVM_drupal_install_profile} \
  --sites-subdir='${SITE_DOMAIN}' \
  --db-url='mysql://${DRUPALVM_drupal_mysql_user}:${DRUPALVM_drupal_mysql_password}@localhost/${SITE_DATABASE}' \
  --account-name='${DRUPALVM_drupal_account_name}' \
  --account-pass='${DRUPALVM_drupal_account_pass}' \
  -y"

# Drupal site installation
if [ -d ${BASE_DIR}/web/sites/${SITE_DOMAIN} ]; then
  chmod u+w ${BASE_DIR}/web/sites/${SITE_DOMAIN}
  chmod u+w ${BASE_DIR}/web/sites/${SITE_DOMAIN}/settings.php
fi

cd ${BASE_DIR}/web

SITE_ALIAS=$(${BASE_DIR}/bin/drush sa | grep "^@${SITE_DOMAIN}$")

if [ ! -z "${SITE_ALIAS}" ]; then
  echo "Installing ${SITE_DOMAIN} using the drush alias"
  ${BASE_DIR}/bin/drush ${SITE_ALIAS} "${SITE_INSTALL_CMD}"
else
  echo "Logging into vagrant for ${SITE_DOMAIN} installation"
  vagrant ssh -c "cd /home/vagrant/docroot/web; /home/vagrant/docroot/bin/drush ${SITE_INSTALL_CMD}"
fi
chmod -R ugo+w ${BASE_DIR}/web/sites/${SITE_DOMAIN}/files
