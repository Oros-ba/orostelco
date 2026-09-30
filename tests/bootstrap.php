<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Inside a Nextcloud server checkout (apps-extra/orostelco) use the server test bootstrap
// so the tests run against the real server.
$serverBootstrap = __DIR__ . '/../../../tests/bootstrap.php';
if (is_file($serverBootstrap)) {
	require_once $serverBootstrap;
	require_once __DIR__ . '/../vendor/autoload.php';

	OC_App::loadApp('orostelco');
	OC_Hook::clear();
	return;
}

// Standalone (host or CI without a server): the public API from nextcloud/ocp is enough
// for the pure unit tests, it only ships stubs without an autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
	foreach (['OCP', 'NCU'] as $namespace) {
		if (str_starts_with($class, $namespace . chr(92))) {
			$file = __DIR__ . '/../vendor/nextcloud/ocp/' . str_replace(chr(92), '/', $class) . '.php';
			if (is_file($file)) {
				require_once $file;
			}
		}
	}
});
