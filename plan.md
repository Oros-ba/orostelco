# Plan: Nextcloud application skeleton (issue #1)

Issue: https://github.com/Oros-ba/orostelco/issues/1
Goal: "Initialise nextcloud application that supports nextcloud versions 33-35".

Beyond the bare skeleton this plan also delivers: Nextcloud best-practice tooling and linting, a `/ping`
REST endpoint called from a Vue button, admin settings for the OrosTelco API endpoint and API key,
and a README describing the app.

## Current state
- Repo contains `appinfo/info.xml`, `README.md`, `LICENSE` (AGPL-3.0-or-later).
- `info.xml` declares `min-version="31" max-version="36"`, which does not match the target range 33-35.
- `.omc/` (AI tooling state) is untracked and not ignored.

## Best practices applied (from Nextcloud `app_template` and developer manual)
| Area | Practice |
|---|---|
| Layout | `appinfo/ img/ lib/ src/ templates/ tests/ vendor-bin/` as in `nextcloud/app_template` |
| Backend | PSR-4 `OCA\Orostelco\`, `IBootstrap` Application class, only public `OCP\` APIs, constructor DI |
| Controllers | Attributes (`#[NoAdminRequired]`, `#[ApiRoute]`, etc.), not legacy annotations; `OCSController` for data APIs (docs: prefer OCS over plain REST); rate limiting attributes where relevant |
| Settings | `IAppConfig` (typed getters/setters, `sensitive: true` for secrets); admin settings registered via `ISettings` + `ISection` in `info.xml` |
| Frontend | Vue 3 + `@nextcloud/vue`, built with `@nextcloud/vite-config`; `@nextcloud/axios` + `generateOcsUrl()` for API calls; `@nextcloud/l10n` for strings |
| Quality | PHP: `php-cs-fixer` (nextcloud/coding-standard), `psalm`, `rector`, PHPUnit. JS/Vue: `@nextcloud/eslint-config`, `@nextcloud/stylelint-config`, TypeScript. Licensing: REUSE/SPDX headers |
| API docs | `openapi.json` generated from controller annotations |
| Versions | `.nvmrc`, `engines` in `package.json`, `@nextcloud/browserslist-config` |
| Meta | `CHANGELOG.md`, `CODE_OF_CONDUCT.md`, `.editorconfig` |

Template reference versions (verify against latest before pinning): `@nextcloud/vue ^9`, `vue ^3.5`,
`@nextcloud/vite-config ^2`, `@nextcloud/eslint-config ^8`, `@nextcloud/stylelint-config ^3`,
`vite ^7`, `typescript ^5.9`, Node ^24 / npm ^11, template composer PHP `^8.1`.

## Scope
In: installable/enable-able app, one page, `/ping` endpoint + button, admin settings (endpoint + API key),
lint/format/static analysis, tests, CI, docs.
Out: real OSS/BSS functionality and actual calls to the OrosTelco API (follow-up issues).

## Steps

### 1. Repo hygiene: `.gitignore`
Ignore build output and dependencies, plus all AI-tooling files and folders:
```
# dependencies / build
node_modules/
vendor/
vendor-bin/**/vendor/
js/
css/
build/
*.tar.gz
.phpunit.result.cache
.php-cs-fixer.cache
.eslintcache
coverage/

# AI tooling (never commit)
.omc/
.claude/
.cursor/
.cursorrules
.windsurf/
.aider*
.continue/
.codex/
.gemini/
.github/copilot-instructions.md
.serena/
.playwright-mcp/
CLAUDE.md
AGENTS.md
GEMINI.md
```
Note: `plan.md` is intentionally tracked. If `CLAUDE.md`/`AGENTS.md` are later wanted in the repo, remove those lines.
Also add `.editorconfig`.

### 2. Compatibility range and metadata: `appinfo/info.xml`
- `<nextcloud min-version="33" max-version="35"/>`.
- `<php min-version="..."/>` matching Nextcloud 33's floor (verify in NC 33 admin/release notes).
- `<navigations>` entry so the app appears in the top menu.
- `<settings><admin>OCA\Orostelco\Settings\Admin</admin><admin-section>OCA\Orostelco\Settings\AdminSection</admin-section></settings>`.
- Bump version when settings are added (settings are registered on install/update).
- Add `<screenshot>`, `<documentation>` and `<bugs>` as appropriate for app store.

### 3. PHP backend (`OCA\Orostelco`)
- `lib/AppInfo/Application.php`: `IBootstrap`, `APP_ID = 'orostelco'`.
- `lib/Controller/PageController.php`: `index()` returns `TemplateResponse`, `#[NoAdminRequired]`, `#[NoCSRFRequired]`; loads the Vue bundle via `Util::addScript`.
- `templates/main.php`: `<div id="orostelco"></div>`.
- `appinfo/routes.php`: page route `/`.
- Only `OCP\` APIs; no private `OC\` usage, so the same code runs on 33, 34, 35.

### 4. `/ping` REST endpoint
- `lib/Controller/PingController.php` extending `OCSController` (Nextcloud-recommended for data APIs).
  - `#[NoAdminRequired]` + `#[ApiRoute(verb: 'GET', url: '/ping')]` -> OCS URL `/ocs/v2.php/apps/orostelco/ping`.
  - Returns `DataResponse(['message' => 'pong', 'time' => <ISO-8601 UTC>])`, HTTP 200.
  - `#[UserRateLimit(limit: 30, period: 60)]` to show the abuse-protection pattern.
  - Typed `@psalm-return DataResponse<Http::STATUS_OK, array{message: string, time: string}, array{}>` and docblocks so `openapi.json` is generated correctly (`composer run openapi`).
- Unit test: response status and payload shape (`tests/Unit/Controller/PingControllerTest.php`).
- Frontend (step 6) calls it with `@nextcloud/axios` and `generateOcsUrl('apps/orostelco/ping')`.
- Open question: `/ping` does not use the stored API endpoint/key in this issue; it only proves the frontend-backend round trip. A follow-up can make it probe the OrosTelco API.

### 5. Settings: OrosTelco API endpoint and API key
Both settings are **instance-wide** (one endpoint and one key shared by all users, admin-managed).
Storage (`OCP\AppFramework\Services\IAppConfig`, app-scoped):
- `api_endpoint` (string, non-sensitive, non-lazy).
- `api_key` (string, **`sensitive: true`** so it is encrypted at rest and masked in `occ config:list`).

Backend:
- `lib/Service/ConfigService.php`: typed getters/setters; validates endpoint (`filter_var` URL, https required except localhost for dev, trailing slash normalised); key trimmed, non-empty.
- `lib/Settings/Admin.php` (`ISettings`, renders a template that mounts the Vue admin component), `lib/Settings/AdminSection.php` (`IIconSection`, id `orostelco`, own icon).
- `lib/Controller/SettingsController.php` (`OCSController`):
  - `GET /settings` -> `{api_endpoint, api_key_set: bool}`. **Never returns the key.**
  - `PUT /settings` -> accepts `api_endpoint` and optional `api_key` (omitted = unchanged); admin only (default, i.e. no `#[NoAdminRequired]`); `#[PasswordConfirmationRequired]` when changing the key.
- Tests: `ConfigServiceTest` (validation, key never exposed), `SettingsControllerTest`.

Frontend (`src/settings/AdminSettings.vue`): `NcSettingsSection` with `NcTextField` (endpoint) and `NcPasswordField` (key, placeholder "already set" when `api_key_set`), Save button, `showSuccess`/`showError` toasts.
CLI alternative documented: `occ config:app:set orostelco api_endpoint --value=...`.

### 6. Frontend (Vue 3)
- `package.json`, `vite.config.ts` via `@nextcloud/vite-config` (entry points: `main` and `adminSettings`), `tsconfig.json`, `.nvmrc`, `engines`, browserslist from `@nextcloud/browserslist-config`.
- `src/main.ts`, `src/App.vue`: heading, **"Ping" button** -> calls `/ping`, shows "pong at <time>" or an error state, disables the button while loading. Wrapped in `NcContent`/`NcAppContent`.
- `src/settings/` entry as in step 5.
- l10n: `@nextcloud/l10n` `t('orostelco', '...')` for all strings; `l10n/` directory.
- Icons: `img/app.svg`, `img/app-dark.svg`.
- Component test (vitest + @vue/test-utils): button click triggers a request and renders result (mock axios).

### 7. Linting, formatting, static analysis (Nextcloud standards)
PHP (`composer.json`, tools isolated in `vendor-bin/` like the template):
- `nextcloud/coding-standard` + `php-cs-fixer` (`.php-cs-fixer.dist.php`): `composer cs:check` / `cs:fix`.
- `vimeo/psalm` with `nextcloud/ocp` for the targeted stable branch (`psalm.xml`): `composer psalm`.
- `rector` (`rector.php`) for modernisation; `composer lint` (php -l); `composer test:unit` (PHPUnit, `tests/bootstrap.php`, `phpunit.xml`).
- `composer openapi` generates `openapi.json`; CI fails if it is out of date.
- `roave/security-advisories` as dev dependency.
- PHP requirement: `^8.1` in template; raise to the floor required by NC 33 once verified.

JS/Vue/CSS (`package.json` scripts):
- `@nextcloud/eslint-config`: `npm run lint`, `npm run lint:fix`.
- `@nextcloud/stylelint-config`: `npm run stylelint`.
- `npm run typecheck` (`vue-tsc --noEmit`), `npm run test` (vitest), `npm run build`.

Licensing/meta:
- SPDX headers (copyright text and AGPL-3.0-or-later license identifier tags) on all sources, `REUSE.toml`, `reuse lint` in CI.
- `CHANGELOG.md` (keep-a-changelog), `CODE_OF_CONDUCT.md`.
- Optional: pre-commit hook (lint-staged) running cs-fixer/eslint on staged files.

`Makefile`: `make build`, `make lint`, `make test`, `make appstore`.

### 8. CI (GitHub Actions)
- **Lint**: php-cs-fixer, psalm, eslint, stylelint, typecheck, reuse, openapi up-to-date, `info.xml` XSD validation.
- **PHPUnit matrix**: Nextcloud `stable33`, `stable34`, `master` (35), with supported PHP versions each.
- **Integration smoke** per Nextcloud version: install server, `occ app:enable orostelco`, request `/ocs/v2.php/apps/orostelco/ping` (expect 200 + `pong`), `occ app:disable`.
- **Frontend**: `npm ci`, `npm run build`, vitest.
- NC 35 may only exist as `master`; mark that job allowed-to-fail until a stable branch exists.
- Reuse the official Nextcloud workflow templates (`nextcloud/.github`) where possible.

### 9. Local development environment (standard Nextcloud way)
Use the official **`nextcloud/nextcloud-docker-dev`** environment (recommended by the Nextcloud developer
manual; the manual alternative is a hand-installed server git checkout, which we do not document). The app repo
stays standalone and is mounted into a dev Nextcloud; no Nextcloud server code lives in this repo.

Developer flow to implement and document (verify each command against the current docker-dev docs while writing):
1. Prerequisites: Git, Docker with Compose v2, Node/npm (version from `.nvmrc`), PHP + Composer (for lint/tests on host; optional since they can also run in the container).
   On Windows: use WSL2 (clone everything inside the WSL filesystem, not `C:\`) for speed and file-watch support.
2. Clone the dev environment and bootstrap it:
   ```
   git clone https://github.com/nextcloud/nextcloud-docker-dev
   cd nextcloud-docker-dev && ./bootstrap.sh
   ```
3. Clone **this app** into the dev server's extra-apps folder so it is mounted into the container:
   `nextcloud-docker-dev/workspace/server/apps-extra/orostelco`
   (alternative for several stable versions: clone elsewhere and point `ADDITIONAL_APPS_PATH` in docker-dev's `.env` at the parent folder, so one checkout serves all containers).
4. Add the hostname to the hosts file (`nextcloud.local`, plus `stable33.local` etc. for other versions) as described in the docker-dev hostname docs.
5. Start and log in: `docker compose up -d nextcloud` -> http://nextcloud.local, user `admin` / password `admin` (dev only, insecure by design).
6. Build the frontend and enable the app:
   ```
   cd apps-extra/orostelco && composer install && npm ci && npm run build
   docker compose exec nextcloud occ app:enable orostelco
   ```
7. Edit loop: `npm run watch` (Vite rebuilds on change, reload the browser); PHP changes are live via the mount. Dev mode is on in docker-dev (`debug => true`), so stack traces and non-minified assets appear.
8. Test against each supported version: use docker-dev's stable containers (git worktree of `stable33`, `stable34`; `master` = 35) as described in its "stable versions" docs, e.g. `docker compose up -d stable33` -> http://stable33.local, and run `occ app:enable orostelco` there.
9. Run quality checks and tests: `make lint`, `make test`, or the individual composer/npm scripts from step 7; PHPUnit can run inside the container (`docker compose exec nextcloud` in the app folder) to use the real server.
10. Debugging and logs: Xdebug (docker-dev "tools" docs, configure IDE to the container), logs via `docker compose exec nextcloud tail -f data/nextcloud.log`, `occ log:tail`.
11. Reset: `docker compose down -v` wipes the dev instance.

Repo additions to support this:
- `Makefile` targets: `make dev-build` (composer + npm build), `make dev-enable` (prints/runs the `occ app:enable`), `make watch`.
- `.nvmrc`, `.editorconfig`, and `composer.json`/`package.json` scripts already listed in step 7 so a fresh clone works after `composer install && npm ci`.
- Optional `scripts/dev-setup.sh` that checks prerequisites (docker, node version from `.nvmrc`, composer) and prints the next steps; no secrets and no hard-coded paths.
- Optional `.devcontainer`/VS Code tasks are out of scope.

### 10. README update
Rewrite `README.md` to describe the app:
- What Orostelco is: a Nextcloud GUI for telecommunications OSS/BSS applications, current status.
- Feature list: main page with Ping button, admin settings.
- **Requirements**: Nextcloud 33-35, PHP (per NC), Node/npm for development.
- **Installation**: from app store (when released) / manual (`apps/` or `custom_apps/`, `occ app:enable orostelco`).
- **Configuration**: Administration settings -> Orostelco: *OrosTelco API endpoint* and *OrosTelco API key* (stored encrypted), plus the `occ` alternative.
- **API**: `GET /ocs/v2.php/apps/orostelco/ping` example with `curl`, link to `openapi.json`.
- **Development (getting started for a new developer)**: a copy-pasteable walkthrough of step 9: prerequisites (incl. WSL2 note for Windows), clone `nextcloud-docker-dev` and bootstrap, clone this app into `workspace/server/apps-extra/orostelco`, hosts entry, `docker compose up -d nextcloud`, login (`admin`/`admin`), `composer install && npm ci && npm run build`, `occ app:enable orostelco`, watch mode, testing against NC 33/34/35 containers, lint/test commands, Xdebug and logs, resetting the environment, and a short troubleshooting list (hostname not resolving, app not listed -> check mount path and `occ app:list`, stale JS -> rebuild/hard reload). Links to the docker-dev docs and the Nextcloud developer manual.
- Project layout overview (`lib/`, `src/`, `appinfo/`, `tests/`) so a newcomer knows where things live.
- Supported-version policy, contributing, license (AGPL-3.0-or-later).

## Acceptance criteria
- [ ] `.gitignore` excludes `.omc/` and other AI tooling files/folders; `git status` shows them clean.
- [ ] `info.xml` validates against the app store XSD and declares NC 33-35.
- [ ] App enables/disables on NC 33, 34, 35 without errors in `nextcloud.log`.
- [ ] Top-menu entry opens the Vue page; clicking **Ping** shows the `pong` response from `/ocs/v2.php/apps/orostelco/ping`.
- [ ] Admin can set OrosTelco API endpoint and API key in Administration settings; key is stored sensitive/encrypted and never returned by the API; invalid endpoint is rejected.
- [ ] Non-admin users cannot read or change settings.
- [ ] `composer cs:check`, `composer psalm`, `composer test:unit`, `npm run lint`, `npm run stylelint`, `npm run typecheck`, `npm test`, `npm run build`, `reuse lint` all pass locally and in CI.
- [ ] `openapi.json` is generated and committed.
- [ ] README describes the app, requirements, installation, configuration, API, and development.
- [ ] A new developer can follow only the README on a clean machine (clone docker-dev, clone this repo into `apps-extra`, build, enable) and reach the working app with the Ping button at http://nextcloud.local; the walkthrough is verified once end to end before merging.
- [ ] `make dev-build` / `make watch` work from a fresh clone.

## Risks / open questions
- NC 35 availability and the exact PHP/Node/`@nextcloud/vue` versions for NC 33-35 must be confirmed upstream; template pins are for reference only.
- Whether `/ping` should later test connectivity to the configured OrosTelco API.
- Decided: the API endpoint and API key are **per instance**, configured by admins only (app-level `IAppConfig`, no per-user settings, no personal settings page). Per-user keys are out of scope.
- App id/namespace (`orostelco` / `Orostelco`) should be confirmed final before first release.
- docker-dev details (service names for stable containers, `ADDITIONAL_APPS_PATH`, hostnames, Windows/WSL2 behaviour, whether a `stable35`/master container matches NC 35) were only partly confirmed from its docs and must be verified by actually running the setup before the README is finalised.
- docker-dev is explicitly insecure (default passwords); README must say it is for local development only.

## Suggested commits
1. `chore: gitignore AI tooling, editorconfig`
2. `chore: align info.xml with Nextcloud 33-35`
3. `feat: PHP app bootstrap, route and page controller`
4. `feat: /ping OCS endpoint with tests`
5. `feat: admin settings for OrosTelco API endpoint and key`
6. `feat: Vue frontend with ping button`
7. `chore: php-cs-fixer, psalm, rector, eslint, stylelint, reuse`
8. `ci: lint, test and compatibility matrix workflows`
9. `chore: Makefile dev targets and dev-setup script`
10. `docs: README (app description, local development guide), CHANGELOG`
