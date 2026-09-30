<!--
  - SPDX-FileCopyrightText: 2026 Mirza Abazovic
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Nextcloud application skeleton for Nextcloud 33 to 35 (PHP 8.2 or newer).
- Top menu entry that opens a Vue 3 page with a "Ping" button.
- `GET /ocs/v2.php/apps/orostelco/ping` endpoint answering `pong` with the server time.
- Administration settings for the instance wide OrosTelco API endpoint and OrosTelco API key. The key is stored encrypted and is never returned by the API.
- Development tooling following the Nextcloud conventions: php-cs-fixer, Psalm, Rector, PHPUnit, ESLint, stylelint, vue-tsc, Vitest, REUSE and GitHub Actions workflows.
- Local development guide based on nextcloud-docker-dev.
