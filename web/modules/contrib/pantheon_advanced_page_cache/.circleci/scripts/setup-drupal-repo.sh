#!/bin/bash
set -e
export TERMINUS_ENV=$CIRCLE_BUILD_NUM

if [ "$TERMINUS_BASE_ENV" = "dev" ]; then
  export TERMINUS_BASE_ENV=master
fi

# Bring the code down to Circle so that modules can be added via composer.
git clone $(terminus connection:info ${TERMINUS_SITE}.dev --field=git_url) --branch $TERMINUS_BASE_ENV drupal-site
cd drupal-site

git checkout -b $TERMINUS_ENV

# requiring other modules below was throwing an error if this dependency was not updated first.
# I think because the composer.lock file for the site has dev-master as the version for this
# dependency. But the CI process calling this file runs against a different branch name thanks to the
# git clone command above.
composer update "pantheon-upstreams/upstream-configuration"

composer -- config repositories.papc vcs git@github.com:pantheon-systems/pantheon_advanced_page_cache.git

# dev-2.x does not match anything, should be 2.x-dev as per https://getcomposer.org/doc/articles/aliases.md#branch-alias.
export BRANCH_PART="dev-${CIRCLE_BRANCH}"
if [ $CIRCLE_BRANCH = "2.x" ]; then
  export BRANCH_PART="2.x-dev"
fi
# Composer require the given commit of this module
composer -- require "drupal/views_custom_cache_tag:1.x-dev" "drupal/pantheon_advanced_page_cache:${BRANCH_PART}#${CIRCLE_SHA1}"

# Don't commit a submodule
rm -rf web/modules/contrib/pantheon_advanced_page_cache/.git/

# Make a git commit
git add .
git commit -m 'Result of build step'
