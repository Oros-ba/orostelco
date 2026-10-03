<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Controller;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use OCA\Orostelco\AppInfo\Application;
use OCA\Orostelco\Db\LogApi;
use OCA\Orostelco\Db\LogApiMapper;
use OCA\Orostelco\Service\LanguageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-suppress UnusedClass
 */
final class PingController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ITimeFactory $time,
		private readonly IFactory $l10nFactory,
		private readonly LanguageService $languageService,
		private readonly LogApiMapper $logMapper,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Ping the Orostelco backend
	 *
	 * The answer is translated to the language requested in the Accept-Language header
	 * (English when that language is not available). The call is logged with time and language.
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string, time: string}, array{Content-Language: string}>
	 *
	 * 200: Pong returned in the requested language with the current server time (UTC, ISO 8601)
	 */
	#[NoAdminRequired]
	#[UserRateLimit(limit: 30, period: 60)]
	#[ApiRoute(verb: 'GET', url: '/ping')]
	public function ping(): DataResponse {
		$now = $this->time->now()->setTimezone(new DateTimeZone('UTC'));
		$language = $this->languageService->resolve($this->request->getHeader('accept-language'));

		$this->logCall(DateTime::createFromImmutable($now), $language);

		return new DataResponse(
			[
				'message' => $this->l10nFactory->get(Application::APP_ID, $language)->t('pong'),
				'time' => $now->format(DateTimeInterface::ATOM),
			],
			Http::STATUS_OK,
			['Content-Language' => $language],
		);
	}

	/**
	 * A broken log table must not break the ping, so failures are only reported.
	 */
	private function logCall(DateTime $calledAt, string $language): void {
		try {
			$entry = new LogApi();
			$entry->setCalledAt($calledAt);
			$entry->setLanguage($language);
			$this->logMapper->insert($entry);
		} catch (Throwable $e) {
			$this->logger->warning('Could not log the ping call', ['exception' => $e, 'app' => Application::APP_ID]);
		}
	}
}
