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
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

final class PingControllerTest extends TestCase {
	public function testPingReturnsPongWithUtcTime(): void {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-09-30T20:00:00+02:00'));

		$controller = new PingController(Application::APP_ID, $this->createMock(IRequest::class), $time);
		$response = $controller->ping();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(
			['message' => 'pong', 'time' => '2026-09-30T18:00:00+00:00'],
			$response->getData(),
		);
	}
}
