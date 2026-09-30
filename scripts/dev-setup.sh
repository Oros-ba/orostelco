#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Mirza Abazovic
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Checks the prerequisites for local Orostelco development and prints the next steps.
# It does not change anything on your machine.
set -u

app_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
missing=0

check() {
	local name="$1" hint="$2"
	if command -v "$name" >/dev/null 2>&1; then
		printf 'ok       %s\n' "$name"
	else
		printf 'MISSING  %s (%s)\n' "$name" "$hint"
		missing=1
	fi
}

check git "https://git-scm.com"
check docker "Docker Desktop with WSL2 integration on Windows, Docker Engine on Linux"
check php "PHP 8.2 or newer"
check composer "https://getcomposer.org"
check node "see .nvmrc"
check npm "ships with node"

if command -v docker >/dev/null 2>&1 && ! docker compose version >/dev/null 2>&1; then
	echo "MISSING  docker compose v2"
	missing=1
fi

if command -v node >/dev/null 2>&1 && [ -f "$app_dir/.nvmrc" ]; then
	wanted="$(tr -d 'v\r\n' <"$app_dir/.nvmrc")"
	have="$(node -v | tr -d 'v')"
	case "$have" in
		"$wanted"*) printf 'ok       node %s matches .nvmrc (%s)\n' "$have" "$wanted" ;;
		*) printf 'WARNING  node %s, .nvmrc wants %s (nvm use / fnm use)\n' "$have" "$wanted" ;;
	esac
fi

case "$app_dir" in
	*/workspace/server/apps-extra/orostelco) echo "ok       app is inside a nextcloud-docker-dev workspace" ;;
	*) echo "NOTE     clone the app to <nextcloud-docker-dev>/workspace/server/apps-extra/orostelco (see README)" ;;
esac

if [ "$missing" -ne 0 ]; then
	echo
	echo "Install the missing tools and run this script again."
	exit 1
fi

cat <<EOF

Next steps (inside $app_dir):
  make dev-build     # composer install, npm ci, development build
  make dev-enable    # occ app:enable orostelco in the running docker-dev container
  make watch         # rebuild the frontend on change
EOF
