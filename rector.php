<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/lib',
	])
	->withSkip([
		// IBootstrap requires register() and boot() even when they are empty.
		RemoveEmptyClassMethodRector::class => [__DIR__ . '/lib/AppInfo/Application.php'],
	])
	->withPhpSets(php82: true)
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		privatization: true,
		instanceOf: true,
		earlyReturn: true,
		rectorPreset: true,
		phpunitCodeQuality: true,
	);
