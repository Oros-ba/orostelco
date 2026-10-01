<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<LogApi>
 * @psalm-suppress UnusedClass, ClassMustBeFinal
 */
class LogApiMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'orostelco_log_api', LogApi::class);
	}
}
