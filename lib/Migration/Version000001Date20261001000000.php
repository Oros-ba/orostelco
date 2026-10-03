<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Creates the table that logs every call to the ping API.
 *
 * @psalm-suppress UnusedClass
 */
final class Version000001Date20261001000000 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @psalm-suppress UndefinedDocblockClass the Doctrine table class is not part of the OCP stubs
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('orostelco_log_api')) {
			return null;
		}

		$table = $schema->createTable('orostelco_log_api');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('called_at', Types::DATETIME, [
			'notnull' => true,
		]);
		$table->addColumn('language', Types::STRING, [
			'notnull' => true,
			'length' => 16,
		]);
		$table->setPrimaryKey(['id']);

		return $schema;
	}
}
