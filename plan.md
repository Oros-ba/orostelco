# Plan: German language support and ping call log (issue #3)

Issue: https://github.com/Oros-ba/orostelco/issues/3
Branch: `feature/3-german-language-support`
Goal: "Introduce multilingual support for German". The previous plan (issue #1, skeleton) is in git history.

## Requirements (from the issue)
1. The UI label "GUI for telco OSS/BSS applications." and the button "Ping" are translated when the
   Nextcloud user's language is German. The button becomes **"Klingeln"**.
2. Every call from the frontend to the backend API carries the user's language in the `Accept-Language` header.
3. A simple DB table `orostelco_log_api` (issue name: `log_api`) records each call to the ping API, with time and language.
4. `GET /ping` returns "pong" in German when German is requested.

## Current state
- `src/App.vue` already wraps strings in `t('orostelco', ...)`, but there is no `l10n/` directory, so nothing is translated.
- `src/services/api.ts` sends only `OCS-APIRequest` and `Accept: application/json`; no language header.
- `lib/Controller/PingController.php` returns a hard-coded `['message' => 'pong', 'time' => <UTC ISO 8601>]`.
- The app has no database tables and no `lib/Migration/` or `lib/Db/`.
- `info.xml`: version `0.0.1`, Nextcloud 33-35, PHP >= 8.2.

## Design decisions
| Topic | Decision | Why |
|---|---|---|
| Translation files | `l10n/de.json` and `l10n/de.js` (Nextcloud format). The same file also serves the backend, so the German "pong" lives in one place | Nextcloud loads them automatically for `t()` in JS and PHP; no extra i18n library |
| Language source (frontend) | `getLanguage()` from `@nextcloud/l10n`, `_` replaced by `-` (BCP 47, e.g. `de`, `de-DE`) | The Nextcloud user language may differ from the browser language, so set it explicitly |
| Where the header is set | Once, in `src/services/api.ts` (shared header builder), so every current and future call gets it | One place; not just `/ping` |
| Language source (backend) | Parse `Accept-Language` in a small `LanguageService`: take tags by q-value, match the primary subtag (`de-AT` -> `de`) against the languages available for the app (`IFactory::findAvailableLanguages`), else `en` | Independent of the session language; testable without Nextcloud |
| Translated pong | `IFactory::get('orostelco', $lang)->t('pong')` | Reuses `l10n/de.json` |
| Table name | `orostelco_log_api` (Nextcloud adds the `oc_` prefix, so `oc_orostelco_log_api`). The issue says `log_api`; the app-prefix convention was chosen instead | Avoids clashes with core and other apps in the shared namespace |
| Columns | `id` (bigint, autoincrement, PK), `called_at` (datetime, UTC, not null), `language` (string 16, not null) | "time and language" only. No user id or IP, which keeps it GDPR-light |
| Migration | `lib/Migration/Version000001Date20261001000000.php` (`SimpleMigrationStep`, `changeSchema`), plus `info.xml` version bump to `0.0.2` | Nextcloud runs migrations on enable/upgrade when the version changes |
| DB access | `OCA\Orostelco\Db\LogApi` (Entity) and `LogApiMapper` (QBMapper) | Standard Nextcloud pattern, only public `OCP\` APIs |
| Log failure | If writing the log row fails, log a warning via `LoggerInterface` and still return pong | A broken audit table must not break ping |

## Steps

### 1. Translations (`l10n/`)
- Collect all `t()`/`$l->t()` strings from `src/` (incl. `AdminSettings.vue`), `templates/` and `lib/`.
- Add `l10n/de.json` and `l10n/de.js` with at least:
  | English | German |
  |---|---|
  | `Ping` | `Klingeln` |
  | `GUI for telco OSS/BSS applications.` | `GUI für Telco-OSS/BSS-Anwendungen.` |
  | `{message} at {time}` | `{message} um {time}` |
  | `The ping request failed` | `Die Ping-Anfrage ist fehlgeschlagen` |
  | `pong` (new source string, see step 4) | `Ping` (decided, see decisions) |
  | remaining admin-settings strings | translated |
- Check that `REUSE.toml` covers `l10n/**` (licence annotation), otherwise the REUSE lint fails.
- English stays the source language; no `en.json` is needed.

### 2. Frontend: `Accept-Language` header
- `src/services/api.ts`: replace the constant `OCS_HEADERS` with a function that adds
  `'Accept-Language': getLanguage().replace('_', '-')`. All calls (`ping`, `getSettings`, `saveEndpoint`, `saveKey`) use it.
- `src/services/api.test.ts`: assert the header for `de` and `en`, and for a `de_DE`-style value (normalised to `de-DE`).
- `src/App.test.ts`: render with German translations and check the button text is `Klingeln`.

### 3. Database: `orostelco_log_api`
- Migration creating `orostelco_log_api` with the columns above.
- `LogApi` entity (`calledAt`, `language`) and `LogApiMapper` (`insert` via the inherited `QBMapper::insert`).
- Register nothing extra; Nextcloud autowires the mapper.

### 4. Backend: `/ping`
- `LanguageService::resolve(string $acceptLanguage): string`.
- `PingController` gets `IFactory`, `LogApiMapper`, `LoggerInterface`, `LanguageService` via constructor DI.
  1. `$lang = $service->resolve($this->request->getHeader('Accept-Language'))`
  2. `message = $factory->get(Application::APP_ID, $lang)->t('pong')`
  3. insert `LogApi(calledAt = now UTC, language = $lang)`, wrapped in try/catch (see above)
  4. return `['message' => ..., 'time' => ...]` (response shape unchanged, so the frontend needs no change)
- Update the docblock and regenerate `openapi*.json` with the project's OpenAPI extractor, then commit the result.
- Set a `Content-Language` response header to the resolved language (`de` or `en`) via `$response->addHeader()`.

### 5. Tests
- `LanguageServiceTest`: `de`, `de-DE`, `de-AT,en;q=0.5`, `en-US,de;q=0.4`, `fr` (unsupported -> `en`), empty or garbage header -> `en`, q=0 tags ignored.
- `PingControllerTest`: update the existing test (new constructor args); add German header -> message `Ping` and `Content-Language: de`, and mapper throws -> still 200 with message.
- `LogApiMapper`: integration test against the Nextcloud test DB if the PHPUnit setup supports it, otherwise verify manually (see Verification).

### 6. Docs
- `README.md`: a "Languages" section (supported: en, de; how to add one) and a note on `orostelco_log_api`.
- `CHANGELOG.md`: add an entry under the next version.

## Verification
1. `make` targets for lint, psalm and unit tests pass (PHP and JS); REUSE lint passes.
2. Local Nextcloud (`scripts/dev-setup.sh`): run `occ app:upgrade orostelco` (or disable/enable) and confirm `oc_orostelco_log_api` exists.
3. Set the Nextcloud user language to Deutsch (Personal settings, Language). The page shows the German label and the **Klingeln** button.
4. Click the button and check in the browser dev tools that the request carries `Accept-Language: de`, and the answer shows the German pong.
5. `curl -H 'OCS-APIRequest: true' -H 'Accept-Language: de' .../ocs/v2.php/apps/orostelco/ping` returns the German message with `Content-Language: de`, and with `Accept-Language: fr` or no header returns `pong`.
6. `SELECT * FROM oc_orostelco_log_api` shows one row per call with the right UTC time and language.
7. Switch the user back to English and confirm English strings and `pong`.

## Risks / notes
- `Accept-Language` set from JS is legal (it is not a forbidden header), but it overrides the browser's own value; that is intended.
- The table has no retention policy. If `/ping` is polled it grows unbounded; a cleanup job is a possible follow-up (out of scope).
- Nextcloud caps table names at 27 characters without the prefix; `orostelco_log_api` (17) is well within this.

## Scope
In: German translations of the current UI, `Accept-Language` on frontend calls, translated `/ping`, `orostelco_log_api` table + logging, tests, docs.
Out: other languages, translation via Transifex workflow, translating `info.xml` metadata, log viewer UI or retention job.

## Decisions (resolved)
- **German "pong"**: German has no real word for it, so the German message is **"Ping"** (`l10n/de.json`: `pong` -> `Ping`). English stays `pong`.
- **Table name**: `orostelco_log_api` (Nextcloud app-prefix convention) instead of `log_api`.
- **`Content-Language`**: `/ping` returns it with the resolved language.
