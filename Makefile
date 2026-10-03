# SPDX-FileCopyrightText: 2026 Mirza Abazovic
# SPDX-License-Identifier: AGPL-3.0-or-later

app_name = orostelco
app_version = $(shell sed -n 's|.*<version>\(.*\)</version>.*|\1|p' appinfo/info.xml | head -n 1)
build_dir = build
appstore_dir = $(build_dir)/appstore
# Location of a nextcloud-docker-dev checkout; the default fits an app cloned to
# nextcloud-docker-dev/workspace/server/apps-extra/orostelco
DOCKER_DEV_DIR ?= ../../../..
DOCKER_DEV_SERVICE ?= nextcloud

.PHONY: all build dev-build watch lint lint-fix test dev-enable appstore clean help

all: build

help:
	@echo "make build        install dependencies and build the frontend for production"
	@echo "make dev-build    install dependencies and build the frontend in development mode"
	@echo "make watch        rebuild the frontend on every change"
	@echo "make lint         run all linters and static analysis"
	@echo "make lint-fix     fix what the code style tools can fix"
	@echo "make test         run PHP and JavaScript unit tests"
	@echo "make dev-enable   enable the app in a running nextcloud-docker-dev container"
	@echo "make appstore     create build/appstore/$(app_name)-<version>.tar.gz"
	@echo "make clean        remove build output and dependencies"

build:
	composer install --no-dev --optimize-autoloader
	npm ci
	npm run build

dev-build:
	composer install
	npm ci
	npm run dev

watch:
	npm run watch

lint:
	composer lint
	composer cs:check
	composer psalm
	npm run lint
	npm run stylelint
	npm run typecheck

lint-fix:
	composer cs:fix
	npm run lint:fix
	npm run stylelint:fix

test:
	composer test:unit
	npm test

dev-enable:
	cd $(DOCKER_DEV_DIR) && docker compose exec $(DOCKER_DEV_SERVICE) occ app:enable $(app_name)

# The app has no PHP runtime dependencies (lib/ is autoloaded by Nextcloud), so vendor/ is not shipped.
# The archive must contain a single top level folder named like the app id.
appstore:
	$(MAKE) clean
	npm ci
	npm run build
	mkdir -p $(appstore_dir)/$(app_name)
	cp -r appinfo css img js l10n lib templates LICENSES CHANGELOG.md LICENSE README.md openapi.json $(appstore_dir)/$(app_name)/
	find $(appstore_dir)/$(app_name) -name '*.map' -delete
	tar -czf $(appstore_dir)/$(app_name)-$(app_version).tar.gz -C $(appstore_dir) $(app_name)
	tar -tzf $(appstore_dir)/$(app_name)-$(app_version).tar.gz | grep -qx '$(app_name)/appinfo/info.xml'
	@echo "Created $(appstore_dir)/$(app_name)-$(app_version).tar.gz"

clean:
	rm -rf $(build_dir) js css node_modules vendor vendor-bin/*/vendor
