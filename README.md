# Orostelco for Nextcloud

Nextcloud GUI for OSS/BSS applications in telecommunications.

Orostelco is a Nextcloud app. It adds an **Orostelco** entry to the top menu and lets an administrator connect the
instance to an OrosTelco API. The app is in early development: today it contains the application skeleton, a
connectivity check (the **Ping** button) and the administration settings. The real OSS/BSS functionality follows
in later issues.

Licence: AGPL-3.0-or-later.

## Features

- **Orostelco page** in the top menu with a **Ping** button. It calls the backend `/ping` endpoint and shows the answer.
- **Administration settings** (Administration settings -> Orostelco) for the instance wide
  **OrosTelco API endpoint** and **OrosTelco API key**.
- REST endpoint `GET /ocs/v2.php/apps/orostelco/ping` (see [API](#api)).
- **Languages**: English and German (see [Languages](#languages)).

## Requirements

| | |
|---|---|
| Nextcloud | 33, 34 or 35 |
| PHP | 8.2 or newer (Nextcloud 35 itself needs 8.3 or newer) |
| Node.js / npm | only for building from source: see [`.nvmrc`](.nvmrc) (24) |

Nextcloud `master` is Nextcloud 36 and is **not** supported yet, `appinfo/info.xml` refuses to enable the app there.

## Installation

The app is not in the Nextcloud app store yet. To install it on an existing server, build it and put it into the
apps folder:

```bash
cd /path/to/nextcloud/custom_apps
git clone https://github.com/Oros-ba/orostelco.git
cd orostelco
npm ci && npm run build
occ app:enable orostelco
```

## Configuration

Open **Administration settings -> Orostelco** and fill in

- **OrosTelco API endpoint**: absolute `https` URL of the API, for example `https://api.example.com`.
  Plain `http` is only accepted for `localhost` (and Nextcloud only calls local addresses when `allow_local_remote_servers` is enabled). Leave it empty to clear the setting.
- **OrosTelco API key**: stored encrypted in the Nextcloud database, never shown again and never returned by the
  API. Leave the field empty to keep the current key. Changing the endpoint or the key asks for your password.

Both settings are **per instance**: one endpoint and one key shared by all users, changeable by administrators only.

The same values can be set on the command line, for example in scripts:

```bash
occ config:app:set orostelco api_endpoint --value=https://api.example.com
occ config:app:set orostelco api_key --value=YOUR_KEY --sensitive
```

The `occ` route skips the validation of the admin page, so double check the endpoint yourself.

## API

| Method and path (below `/ocs/v2.php/apps/orostelco`) | Who | Purpose |
|---|---|---|
| `GET /ping` | any logged in user | Returns `pong` (German: `Ping`) and the server time (UTC), in the language of the `Accept-Language` header, which is also returned as `Content-Language`. Every call is logged in `orostelco_log_api`. Rate limited to 30 requests per minute and user. |
| `GET /settings` | administrators | Current endpoint and whether a key is set. |
| `PUT /settings/endpoint` | administrators | Set the endpoint (`apiEndpoint`), password confirmation required in the browser. |
| `PUT /settings/key` | administrators | Set the API key (`apiKey`), password confirmation required in the browser. |

```bash
curl -u USER:PASSWORD -H 'OCS-APIRequest: true' -H 'Accept: application/json' https://cloud.example.com/ocs/v2.php/apps/orostelco/ping
# {"ocs":{"meta":{"status":"ok","statuscode":200,"message":"OK"},"data":{"message":"pong","time":"2026-09-30T22:27:44+00:00"}}}
```

The OpenAPI description is generated from the controllers: [`openapi.json`](openapi.json) (public endpoints),
[`openapi-administration.json`](openapi-administration.json) and [`openapi-full.json`](openapi-full.json).

## Languages

The app is available in English (source language) and German. When a Nextcloud user selects German
(Personal settings -> Language) the page label and the button (**Klingeln**) are shown in German.
The frontend sends the user language in the `Accept-Language` header of every API call and `/ping`
answers in that language (`Content-Language` response header; English if the language is not available).

To add a language, add `l10n/<code>.js` and `l10n/<code>.json` (copy `de.*`) with the translated strings,
including `pong`, the answer of `/ping`.

### Ping log

Each call to `/ping` is stored in the table `orostelco_log_api` (`oc_orostelco_log_api` with the default
table prefix) with the UTC time (`called_at`) and the answer language (`language`). Neither user nor IP is stored.
The table is created by a migration when the app is enabled or upgraded (`occ app:upgrade orostelco`).
Rows are not deleted automatically.

## Development

The standard Nextcloud way is [nextcloud-docker-dev](https://github.com/nextcloud/nextcloud-docker-dev): Nextcloud
runs in Docker containers and this app is mounted into them, so a change in your checkout shows up immediately.
The steps below were verified on Windows 11 with WSL2 (Ubuntu 24.04) and Docker Desktop. On Linux and macOS skip the
Windows specific notes. **Everything created by nextcloud-docker-dev is for local development only**: it uses default
passwords (`admin` / `admin`) and insecure settings.

### 1. Prerequisites

- Git and Docker with Compose v2. On Windows use Docker Desktop with the WSL2 backend and enable the integration
  for your Ubuntu distribution.
- **On Windows work inside WSL2** (Ubuntu), keep all checkouts in the Linux file system (`~/...`), not under
  `/mnt/c`. It is much faster and file changes are detected.
- Node.js 24 (see `.nvmrc`; for example with [nvm](https://github.com/nvm-sh/nvm) or
  [fnm](https://github.com/Schniz/fnm)) and npm. Newer Node versions are not supported by the Nextcloud frontend
  libraries: `npm install` would silently pick very old library versions.
- PHP 8.2+ and [Composer](https://getcomposer.org) if you want to run the PHP tooling on your machine.
  Alternatively run it inside the container (step 8).

`scripts/dev-setup.sh` checks these and prints the next steps once the app is cloned.

### 2. Get nextcloud-docker-dev

```bash
cd ~
git clone https://github.com/nextcloud/nextcloud-docker-dev
cd nextcloud-docker-dev
./bootstrap.sh
```

The bootstrap clones the Nextcloud server (about 2 GB) and a few apps into `workspace/` and creates `.env`.
It also runs `sudo` to add `nextcloud.local` to `/etc/hosts`; answer the password prompt, or see step 4 if you
manage the hosts file yourself.

### 3. Get the app into the development environment

Clone this repository into the **shared apps folder** `data/apps-extra`. It is mounted into every container
(`nextcloud`, `stable33`, `stable34`, `stable35`, ...), so one checkout serves all supported Nextcloud versions:

```bash
cd ~/nextcloud-docker-dev/data/apps-extra
git clone https://github.com/Oros-ba/orostelco.git
cd orostelco
```

### 4. Hostnames

The containers answer to `<name>.local` host names, for example `stable35.local`. Add the ones you use to the hosts
file of the machine that runs the **browser**:

- Linux / macOS: `/etc/hosts`
- Windows: `C:\Windows\System32\drivers\etc\hosts` (edit as administrator). Editing `/etc/hosts` inside WSL
  does not affect the Windows browser.

```
127.0.0.1 nextcloud.local stable33.local stable34.local stable35.local
```

Instead of the hosts file you can also use a wildcard resolver such as dnsmasq, see the docker-dev hostnames docs.

### 5. Pick a Nextcloud version and start it

The default container `nextcloud` runs the development branch of the server, which is **Nextcloud 36** and therefore
refuses this app (supported: 33 to 35). Use one of the stable containers. They need a checkout of the matching server
branch, created with a git worktree, for example for Nextcloud 35:

```bash
cd ~/nextcloud-docker-dev/workspace/server
git fetch --depth=1 origin stable35:stable35
git worktree add ../stable35 stable35
cd ../stable35 && git submodule update --init --depth=1

cd ~/nextcloud-docker-dev
docker compose up -d stable35
```

Repeat with `stable33` / `stable34` to test the other versions. The first start pulls the images and installs
Nextcloud, give it a few minutes (`docker compose logs -f stable35`). Then open http://stable35.local and log in with
`admin` / `admin`.

If port 80 or 443 is already used on your machine, `docker compose up` fails with "ports are not available". Add

```
PROXY_PORT_HTTP=8080
PROXY_PORT_HTTPS=8443
```

to `~/nextcloud-docker-dev/.env`, start again and use http://stable35.local:8080.

### 6. Build the frontend and enable the app

```bash
cd ~/nextcloud-docker-dev/data/apps-extra/orostelco
make dev-build                      # composer install, npm ci, development build
cd ~/nextcloud-docker-dev
docker compose exec stable35 occ app:enable orostelco
```

`make dev-enable` runs the last command for you when you set `DOCKER_DEV_DIR=~/nextcloud-docker-dev` and
`DOCKER_DEV_SERVICE=stable35`. If the app is not found, check `docker compose exec stable35 occ app:list`: it must
be listed, otherwise the path in step 3 is wrong. You should now see **Orostelco** in the top menu and
**Administration settings -> Orostelco**.

### 7. Edit loop

```bash
make watch          # rebuilds the frontend on every change, reload the browser afterwards
```

PHP changes take effect immediately because the folder is mounted. docker-dev runs Nextcloud with `debug` enabled,
so stack traces are shown and scripts are not minified.

### 8. Tests and linters

```bash
make lint           # php lint, php-cs-fixer, psalm, eslint, stylelint, vue-tsc
make lint-fix       # apply the automatic fixes
make test           # PHPUnit and Vitest
```

| Tool | Command |
|---|---|
| Coding standard (php-cs-fixer) | `composer cs:check`, `composer cs:fix` |
| Static analysis (Psalm) | `composer psalm` |
| Modernisation (Rector) | `composer rector` |
| PHPUnit | `composer test:unit` |
| OpenAPI spec | `composer openapi` (commit the changed `openapi*.json`) |
| ESLint | `npm run lint`, `npm run lint:fix` |
| stylelint | `npm run stylelint` |
| TypeScript | `npm run typecheck` |
| Vitest | `npm test` |
| REUSE licence headers | `reuse lint` |

To run the PHP tests against the real server, run them inside the container (no PHP needed on the host):

```bash
cd ~/nextcloud-docker-dev
docker compose exec stable35 bash -c 'cd /var/www/html/apps-shared/orostelco && composer install && composer test:unit'
```

### 9. Debugging and logs

```bash
docker compose exec stable35 occ log:tail           # Nextcloud log
docker compose logs -f stable35                     # web server and PHP log
```

Xdebug is preinstalled in the containers, see the docker-dev documentation (tools -> Xdebug) for the IDE settings.

### 10. Reset

`docker compose down -v` in `~/nextcloud-docker-dev` removes the containers and their data; the next `up` installs a
fresh Nextcloud.

### Troubleshooting

| Problem | Fix |
|---|---|
| `ports are not available` on `docker compose up` | Step 5, use `PROXY_PORT_HTTP` / `PROXY_PORT_HTTPS`. |
| `stable35.local` does not resolve in the browser | Step 4, edit the hosts file of the machine running the browser (on Windows not the WSL one). |
| App does not appear in `occ app:list` | The checkout must be in `nextcloud-docker-dev/data/apps-extra/orostelco`, then `docker compose restart stable35`. |
| "App not compatible" on enable | You are on the `nextcloud` (Nextcloud 36) container, use a stable container. |
| Blank page, script not found | Run `npm run build` / `make watch` in the app folder, hard reload the browser. |
| `npm install` picks tiny old versions (Vue 2, `@nextcloud/vue` 4) | Wrong Node version, `nvm use` / `fnm use` (Node 24) and reinstall. |
| Vitest fails with "Failed to start forks worker" | Already handled, the config uses the `threads` pool. |

More in the [nextcloud-docker-dev docs](https://nextcloud.github.io/nextcloud-docker-dev/) and the
[Nextcloud developer manual](https://docs.nextcloud.com/server/latest/developer_manual/).

## Project layout

```
appinfo/            info.xml (metadata, navigation, settings registration)
lib/                PHP backend (namespace OCA\Orostelco)
  AppInfo/          Application bootstrap
  Controller/       PageController, PingController, SettingsController
  Db/               LogApi entity and mapper (ping call log)
  Migration/        Database migrations
  Service/          ConfigService (endpoint and key, validation), LanguageService (Accept-Language)
  Settings/         Admin settings and section
l10n/               Translations (de)
templates/          PHP templates that mount the Vue apps
src/                Vue 3 frontend (main.ts, adminSettings.ts, components, services)
img/                Icons
tests/              PHPUnit tests
vendor-bin/         Isolated PHP tools (cs-fixer, psalm, phpunit, rector, openapi-extractor)
.github/workflows/  CI: lint, node build and tests, Nextcloud 33/34/35 compatibility matrix
openapi*.json       Generated API description
```

## Supported versions

The app supports the Nextcloud versions agreed in [issue #1](https://github.com/Oros-ba/orostelco/issues/1): 33, 34
and 35. To change the range edit `<nextcloud min-version max-version>` in `appinfo/info.xml`, the `php` minimum, the
`nextcloud/ocp` branch in `composer.json` (lowest supported version) and the matrix in
`.github/workflows/phpunit.yml`.

## Contributing

Open an issue or a pull request at https://github.com/Oros-ba/orostelco. Please run `make lint` and `make test`
first, keep the REUSE licence headers (`reuse lint`) and add a line to [`CHANGELOG.md`](CHANGELOG.md). This project
follows the [Code of Conduct](CODE_OF_CONDUCT.md).
