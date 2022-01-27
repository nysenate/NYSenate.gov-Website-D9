#!/bin/bash
usage()
{
cat << EOF
Usage: behat-run.sh uri.dev

ARGUMENTS:
   $URI: URI of site to run behat against.
EOF
}

URI=$1

# Exit if options aren't set.
if [[ -z $URI ]]; then
  usage
  exit 1;
fi

# Use environment variable if specified
if [[ -z ${BEHAT_BROWSER} ]]; then
  BEHAT_BROWSER="chromium-browser"
  BEHAT_BROWSER_OPTIONS="--disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222 --no-sandbox > /dev/null 2>&1"
fi

if [ ! -z ${BEHAT_BROWSER} ] && !(pgrep -f "${BEHAT_BROWSER}" > /dev/null); then
  ${BEHAT_BROWSER} ${BEHAT_BROWSER_OPTIONS} &
fi

cd `dirname $0`

export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"base_url" : "'$URI'"}}}'

# Run behat.
if [ -f ./vendor/bin/behat ]; then
  ./vendor/bin/behat ${@:2}
elif [ -f ../../vendor/bin/behat ]; then
  ../../vendor/bin/behat ${@:2}
else
  behat ${@:2}
fi
