<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Orostelco\Service\ConfigService;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigServiceTest extends TestCase {
	private IAppConfig&MockObject $appConfig;

	private ConfigService $service;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->service = new ConfigService($this->appConfig);
	}

	/**
	 * @return \Iterator<string, array{string, string}>
	 */
	public static function validEndpoints(): \Iterator {
		yield 'https' => ['https://api.example.com', 'https://api.example.com'];
		yield 'trailing slash removed' => ['https://api.example.com/v1/', 'https://api.example.com/v1'];
		yield 'surrounding whitespace trimmed' => ['  https://api.example.com  ', 'https://api.example.com'];
		yield 'http allowed for localhost' => ['http://localhost:8080/', 'http://localhost:8080'];
		yield 'http allowed for 127.0.0.1' => ['http://127.0.0.1:8080', 'http://127.0.0.1:8080'];
		yield 'empty clears the endpoint' => ['', ''];
	}

	#[DataProvider('validEndpoints')]
	public function testSetApiEndpointStoresNormalizedValue(string $input, string $expected): void {
		$this->appConfig->expects($this->once())
			->method('setAppValueString')
			->with(ConfigService::KEY_API_ENDPOINT, $expected);

		$this->service->setApiEndpoint($input);
	}

	/**
	 * @return \Iterator<string, array{string}>
	 */
	public static function invalidEndpoints(): \Iterator {
		yield 'not a url' => ['not a url'];
		yield 'relative' => ['/api/v1'];
		yield 'plain http for remote host' => ['http://api.example.com'];
		yield 'unsupported scheme' => ['ftp://api.example.com'];
		yield 'credentials in url' => ['https://user:pass@api.example.com'];
		yield 'query string' => ['https://api.example.com?x=1'];
		yield 'fragment' => ['https://api.example.com#top'];
		yield 'invalid host' => ['https://exa mple.com'];
	}

	#[DataProvider('invalidEndpoints')]
	public function testSetApiEndpointRejectsInvalidValue(string $input): void {
		$this->appConfig->expects($this->never())->method('setAppValueString');

		$this->expectException(InvalidArgumentException::class);
		$this->service->setApiEndpoint($input);
	}

	public function testApiKeyIsStoredTrimmedAndSensitive(): void {
		$this->appConfig->expects($this->once())
			->method('setAppValueString')
			->with(ConfigService::KEY_API_KEY, 'secret', false, true);

		$this->service->setApiKey('  secret ');
	}

	public function testEmptyApiKeyIsRejected(): void {
		$this->appConfig->expects($this->never())->method('setAppValueString');

		$this->expectException(InvalidArgumentException::class);
		$this->service->setApiKey('   ');
	}

	public function testHasApiKey(): void {
		$this->appConfig->method('getAppValueString')->willReturn('secret');

		$this->assertTrue($this->service->hasApiKey());
	}

	public function testHasNoApiKeyByDefault(): void {
		$this->appConfig->method('getAppValueString')->willReturn('');

		$this->assertFalse($this->service->hasApiKey());
	}
}
