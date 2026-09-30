<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Controller;

use DateTimeInterface;
use DateTimeZone;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
final class PingController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ITimeFactory $time,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Ping the Orostelco backend
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string, time: string}, array{}>
	 *
	 * 200: Pong returned with the current server time (UTC, ISO 8601)
	 */
	#[NoAdminRequired]
	#[UserRateLimit(limit: 30, period: 60)]
	#[ApiRoute(verb: 'GET', url: '/ping')]
	public function ping(): DataResponse {
		$now = $this->time->now()->setTimezone(new DateTimeZone('UTC'));

		return new DataResponse([
			'message' => 'pong',
			'time' => $now->format(DateTimeInterface::ATOM),
		]);
	}
}
