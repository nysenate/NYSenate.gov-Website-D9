TARGET_LOCAL=https://mcrain.ddev.site/
TARGET_CI=http://172.17.0.1:8080
TARGET_DEV=http://develop-rain-demo.pantheonsite.io/

CYPRESS_BINARY := docker run --rm -v ${PWD}:/e2e -w /e2e cypress/included:12.7.0
CYPRESS_BINARY_LOCAL := docker run --net="host" --rm -v ${PWD}:/e2e -w /e2e cypress/included:12.7.0
CYPRESS_HOST_BINARY := npx cypress
ifeq ($(DOCKER),false)
	CYPRESS_BINARY := $(CYPRESS_HOST_BINARY) run
	CYPRESS_BINARY_LOCAL := $(CYPRESS_HOST_BINARY) run
endif
ifeq ($(tags),)
	TAGS_OPTION := grepTags="@smoke --@bug --@wip"
else
	TAGS_OPTION := grepTags="$(tags)"
endif
ENV=--env $(TAGS_OPTION)
# ------------------------------------------------------------------------------

# NOTES:
# This Makefile assumes you've gone through the README steps in tests/cypress

ci-run:
	@$(CYPRESS_BINARY) --config "baseUrl=$(TARGET_CI)" $(ENV)

dev-run:
	@$(CYPRESS_BINARY) --config "baseUrl=$(TARGET_DEV)" $(ENV)

dev-open:
	@$(CYPRESS_HOST_BINARY) open  --config "baseUrl=$(TARGET_DEV)" $(ENV)

local-run:
	@$(CYPRESS_BINARY_LOCAL) --config "baseUrl=$(TARGET_LOCAL)" $(ENV)

local-open:
	@$(CYPRESS_HOST_BINARY) open --config "baseUrl=$(TARGET_LOCAL)" $(ENV)

