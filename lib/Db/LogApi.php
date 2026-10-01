<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;

/**
 * One call to the ping API.
 *
 * @method DateTime getCalledAt()
 * @method void setCalledAt(DateTime $calledAt)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 *
 * @psalm-suppress PropertyNotSetInConstructor, PossiblyUnusedProperty
 */
final class LogApi extends Entity {
	/** @var DateTime UTC time of the call */
	protected $calledAt;

	/** @var string language the call was answered in, e.g. "de" */
	protected $language = '';

	public function __construct() {
		$this->addType('calledAt', 'datetime');
		$this->addType('language', 'string');
	}
}
