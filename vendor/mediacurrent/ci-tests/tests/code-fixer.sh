#!/bin/bash
usage()
{
cat << EOF
Usage: ./code-fixer.sh /path/to/docroot

ARGUMENTS:
   $SITE_PATH: Absolute path to Drupal docroot.
EOF
}

SITE_PATH=$1

# Exit if options aren't set.
if [[ -z $SITE_PATH ]]; then
  usage
  exit 1;
fi

# Set the phpcbf standards config.
# phpcbf --config-set installed_paths ${HOME}/.composer/vendor/drupal/coder/coder_sniffer

trap 'rc=$?' ERR

if [ -f ./vendor/bin/phpcbf ]; then
  phpcbf='./vendor/bin/phpcbf --standard=./vendor/drupal/coder/coder_sniffer/Drupal'
elif [ -f ../vendor/bin/phpcbf ]; then
  phpcbf='../vendor/bin/phpcbf --standard=../vendor/drupal/coder/coder_sniffer/Drupal'
else
  phpcbf='phpcbf --standard=Drupal'
fi

if [ -d ${SITE_PATH}/modules/custom ]; then
  echo "Running coding standards fixer for custom modules."
  ${phpcbf} --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml ${SITE_PATH}/modules/custom
fi

if [ -d ${SITE_PATH}/profiles/custom ]; then
  echo "Running coding standards fixer for custom profiles."
  ${phpcbf} --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml ${SITE_PATH}/profiles/custom
fi

if [ -d ${SITE_PATH}/themes/custom ]; then
  echo "Running coding standards fixer for custom themes."
  ${phpcbf} --ignore=/themes/custom/*/node_modules/,/themes/custom/*/src/styleguide/ --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml ${SITE_PATH}/themes/custom
fi

exit ${rc}
