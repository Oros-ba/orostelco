<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Tests\Unit\Controller;

use DateTimeImmutable;
use OCA\Orostelco\AppInfo\Application;
use OCA\Orostelco\Controller\PingController;
use OCA\Orostelco\Db\LogApi;
use OCA\Orostelco\Db\LogApiMapper;
use OCA\Orostelco\Service\LanguageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

final class PingControllerTest extends TestCase {
	private LogApiMapper&MockObject $mapper;

	private LoggerInterface&MockObject $logger;

	private function controller(string $acceptLanguage): PingController {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-09-30T20:00:00+02:00'));

		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->with('accept-language')->willReturn($acceptLanguage);

		$factory = $this->createMock(IFactory::class);
		$factory->method('findAvailableLanguages')->willReturn(['en', 'de']);
		$factory->method('get')->willReturnCallback(function (string $app, string $language): IL10N {
			$this->assertSame(Application::APP_ID, $app);
			$l10n = $this->createMock(IL10N::class);
			$l10n->method('t')->willReturnCallback(static fn (string $text): string => $language === 'de' && $text === 'pong' ? 'Ping' : $text);

			return $l10n;
		});

		$this->mapper = $this->createMock(LogApiMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		return new PingController(
			Application::APP_ID,
			$request,
			$time,
			$factory,
			new LanguageService($factory),
			$this->mapper,
			$this->logger,
		);
	}

	/**
	 * Reads the header property directly, Response::getHeaders() needs a running server.
	 */
	private function header(DataResponse $response, string $name): string {
		/** @var array<string, string> $headers */
		$headers = (new ReflectionProperty(Response::class, 'headers'))->getValue($response);

		return $headers[$name];
	}

	public function testPingReturnsPongWithUtcTimeInEnglishByDefault(): void {
		$response = $this->controller('')->ping();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(
			['message' => 'pong', 'time' => '2026-09-30T18:00:00+00:00'],
			$response->getData(),
		);
		$this->assertSame('en', $this->header($response, 'Content-Language'));
	}

	public function testPingAnswersInGerman(): void {
		$response = $this->controller('de-DE,de;q=0.9,en;q=0.5')->ping();

		$this->assertSame('Ping', $response->getData()['message']);
		$this->assertSame('de', $this->header($response, 'Content-Language'));
	}

	public function testPingLogsTimeAndLanguage(): void {
		$controller = $this->controller('de');
		$this->mapper->expects($this->once())->method('insert')->with($this->callback(
			function (LogApi $entry): bool {
				$this->assertSame('de', $entry->getLanguage());
				$this->assertSame('2026-09-30 18:00:00', $entry->getCalledAt()->format('Y-m-d H:i:s'));

				return true;
			},
		))->willReturnArgument(0);

		$controller->ping();
	}

	public function testPingStillAnswersWhenLoggingFails(): void {
		$controller = $this->controller('de');
		$this->mapper->method('insert')->willThrowException(new \RuntimeException('db down'));
		$this->logger->expects($this->once())->method('warning');

		$response = $controller->ping();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('Ping', $response->getData()['message']);
	}
}
