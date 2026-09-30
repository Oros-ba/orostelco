# Plan: Nextcloud application skeleton (issue #1)

Issue: https://github.com/Oros-ba/orostelco/issues/1
Goal: "Initialise nextcloud application that supports nextcloud versions 33-35".

## Current state
- Repo only contains `appinfo/info.xml`, `README.md`, `LICENSE` (AGPL-3.0-or-later).
- `info.xml` declares `min-version="31" max-version="36"`, which does not match the target range 33-35.

## Scope
In: a minimal, installable, enable-able app with one page, routing, a frontend build, CI and docs.
Out: any real OSS/BSS functionality (follow-up issues).

## Steps

1. **Fix compatibility range** in `appinfo/info.xml`: `<nextcloud min-version="33" max-version="35"/>`.
   Add a `<php min-version="..."/>` dependency matching the PHP floor of Nextcloud 33 (verify against
   the Nextcloud server 33 release notes / admin manual before committing). Add `<bugs>`/`<screenshot>` only if needed.
   Add a `<navigations>` entry so the app shows in the top menu.

2. **PHP backend (namespace `OCA\Orostelco`)**
   - `lib/AppInfo/Application.php` implementing `IBootstrap` (`APP_ID = 'orostelco'`).
   - `lib/Controller/PageController.php`: `index()` returns a `TemplateResponse`
     (`#[NoAdminRequired]`, `#[NoCSRFRequired]` attributes, not legacy annotations).
   - `appinfo/routes.php`: route `page#index` at `/`.
   - `templates/main.php`: mount point `<div id="orostelco"></div>` and load script/style.
   - Use only public `OCP\` APIs so the same code runs on 33, 34 and 35.

3. **Frontend skeleton**
   - `package.json`, `vite.config.js` (via `@nextcloud/vite-config`), `src/main.js`, `src/App.vue`
     (Vue + `@nextcloud/vue`), output to `js/`. Use the Vue major version supported by
     `@nextcloud/vue` for NC 33-35 (verify).
   - Hello-world page proving the app renders inside the Nextcloud shell.
   - `l10n/` folder and `@nextcloud/l10n` wiring for translatable strings.
   - Icon `img/app.svg` and `img/app-dark.svg`.

4. **Tooling and quality**
   - `composer.json` (PSR-4 `OCA\Orostelco\` -> `lib/`, dev deps: `nextcloud/ocp`, `phpunit`, `psalm`, `php-cs-fixer`).
   - `tests/bootstrap.php`, `phpunit.xml`, one unit test for `PageController`.
   - `.gitignore` (node_modules, vendor, js build output), `.editorconfig`, `Makefile` (`make build`, `make test`, `make appstore`).
   - `REUSE`/SPDX headers (`AGPL-3.0-or-later`) on all new source files.

5. **Compatibility verification (the actual acceptance test for "33-35")**
   - GitHub Actions matrix over Nextcloud `stable33`, `stable34`, `master`/35 (and the PHP versions each supports):
     install server, `occ app:enable orostelco`, run PHPUnit, run `occ app:check-code`,
     and a smoke request to `/index.php/apps/orostelco/` expecting HTTP 200.
   - Lint workflow: `composer run cs:check`, `psalm`, `npm run lint`, `npm run build`.
   - Manual check: copy the app into a local Nextcloud 33 (docker `nextcloud:33`) and confirm
     enable, menu entry, page render, disable, and remove work without log errors.

6. **Docs**
   - Update `README.md`: requirements (NC 33-35, PHP, Node), dev setup, build, test, install.
   - Note the supported-version policy and how to bump it.

## Acceptance criteria
- [ ] `info.xml` validates against the app store XSD and declares NC 33-35.
- [ ] App can be enabled/disabled via `occ` and UI on NC 33, 34 and 35 with no errors in `nextcloud.log`.
- [ ] Navigation entry opens a page rendered by the Vue frontend.
- [ ] `composer test`, lint and `npm run build` pass locally and in CI.
- [ ] README documents setup and supported versions.

## Risks / open questions
- NC 35 may only exist as `master` for now; CI for it may be allowed-to-fail until a stable branch exists.
- PHP minimum and Vue/`@nextcloud/vue` major for NC 33-35 must be confirmed from upstream docs.
- Decide whether the app id/namespace (`orostelco` / `Orostelco`) is final before the first release.

## Suggested commits
1. `chore: align info.xml with Nextcloud 33-35`
2. `feat: PHP app bootstrap, route and page controller`
3. `feat: Vue frontend skeleton and build`
4. `test: phpunit setup and controller test`
5. `ci: compatibility matrix and lint workflows`
6. `docs: README setup and compatibility`
