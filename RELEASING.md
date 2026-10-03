<!--
  - SPDX-FileCopyrightText: 2026 Mirza Abazovic
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Releasing to the Nextcloud app store

A release is published by pushing a tag. The workflow
[`appstore-release.yml`](.github/workflows/appstore-release.yml) builds the tarball, creates the GitHub release
and uploads the signed release to https://apps.nextcloud.com/apps/orostelco.

## One-time setup

1. **App registration.** The app id `orostelco` is registered with the app store using the code signing certificate
   issued through [nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests). The
   private key and the certificate must never be committed.
2. **GitHub environment `appstore`** (Settings → Environments), restricted to `v*` tags and with a required reviewer,
   holding these secrets:
   - `APPSTORE_TOKEN`: the API token from https://apps.nextcloud.com/account/token
   - `APP_PRIVATE_KEY`: the full PEM text of the app private key (unencrypted), including the BEGIN and END lines
3. A tag ruleset for `v*` (Settings → Rules), so only maintainers can create or delete release tags.

## Cutting a release

1. Branch from `main` and set the new version in `appinfo/info.xml` (`<version>`) and `package.json`
   (`npm version <version> --no-git-tag-version`). Pre-releases use a suffix such as `0.0.1-alpha1`.
2. Move the `[Unreleased]` entries of `CHANGELOG.md` into a section `## [<version>] - <date>`. The app store and
   the GitHub release both show this text as the release notes.
3. Run `composer run openapi` and commit the result if the OpenAPI files changed.
4. Merge to `main` through a pull request.
5. Optionally run the workflow by hand (Actions → App store release → Run workflow). This builds and checks the
   tarball without publishing it.
6. Tag the merge commit on `main` and push the tag:

   ```
   git checkout main && git pull
   git tag -a v<version> -m "Orostelco <version>"
   git push origin v<version>
   ```

7. Approve the `appstore` environment deployment when GitHub asks for it.

The workflow stops before publishing when the tag does not match `info.xml` and `package.json`, when the tag is not
on `main`, when `CHANGELOG.md` has no section for the version, or when the tarball has unexpected content.

## Notes

- The app store never accepts the same version twice. If a release was rejected after the upload, fix the problem
  and publish the next version (for example `0.0.1-alpha2`). If the workflow failed before the store call, delete
  the GitHub release and the tag, fix the cause and tag again.
- Versions with a suffix (`-alpha1`, `-beta1`, `-rc1`) are pre-releases. The workflow marks the GitHub release as a
  pre-release for them.
- Build the tarball locally with `make appstore`. It is written to `build/appstore/orostelco-<version>.tar.gz`.
