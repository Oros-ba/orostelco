<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Tests\Unit\Controller;

use OCA\Orostelco\AppInfo\Application;
use OCA\Orostelco\Controller\SettingsController;
use OCA\Orostelco\Service\ConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SettingsControllerTest extends TestCase {
	/** @var array<string, array{value: string, sensitive: bool}> */
	private array $store = [];

	private SettingsController $controller;

	protected function setUp(): void {
		$this->store = [];

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueString')->willReturnCallback(
			fn (string $key, string $default = ''): string => $this->store[$key]['value'] ?? $default,
		);
		$appConfig->method('setAppValueString')->willReturnCallback(
			function (string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
				$this->store[$key] = ['value' => $value, 'sensitive' => $sensitive];
				return true;
			},
		);

		$this->controller = new SettingsController(
			Application::APP_ID,
			$this->createMock(IRequest::class),
			new ConfigService($appConfig),
		);
	}

	public function testGetReturnsDefaults(): void {
		$response = $this->controller->get();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['apiEndpoint' => '', 'apiKeySet' => false], $response->getData());
	}

	public function testSetEndpointSavesAndReturnsCurrentSettings(): void {
		$response = $this->controller->setEndpoint('https://api.example.com/');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['apiEndpoint' => 'https://api.example.com', 'apiKeySet' => false], $response->getData());
	}

	public function testSetEndpointRejectsInvalidValue(): void {
		$response = $this->controller->setEndpoint('http://api.example.com');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertArrayHasKey('message', $response->getData());
		$this->assertSame([], $this->store);
	}

	public function testSetKeyStoresSensitiveValueAndNeverEchoesIt(): void {
		$response = $this->controller->setKey('top-secret');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['apiEndpoint' => '', 'apiKeySet' => true], $response->getData());
		$this->assertStringNotContainsString('top-secret', json_encode($response->getData(), JSON_THROW_ON_ERROR));
		$this->assertSame(['value' => 'top-secret', 'sensitive' => true], $this->store[ConfigService::KEY_API_KEY]);
	}

	public function testGetNeverExposesTheApiKey(): void {
		$this->controller->setKey('top-secret');

		$response = $this->controller->get();

		$this->assertSame(['apiEndpoint' => '', 'apiKeySet' => true], $response->getData());
		$this->assertStringNotContainsString('top-secret', json_encode($response->getData(), JSON_THROW_ON_ERROR));
	}

	public function testSetKeyRejectsEmptyKey(): void {
		$response = $this->controller->setKey('  ');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->store);
	}

	public function testSettingsEndpointsAreAdminOnly(): void {
		foreach (['get', 'setEndpoint', 'setKey'] as $method) {
			$attributes = (new ReflectionMethod(SettingsController::class, $method))
				->getAttributes(NoAdminRequired::class);
			$this->assertSame([], $attributes, $method . ' must not be callable by non-admins');
		}
	}

	public function testChangingTheKeyRequiresPasswordConfirmation(): void {
		$attributes = (new ReflectionMethod(SettingsController::class, 'setKey'))
			->getAttributes(PasswordConfirmationRequired::class);

		$this->assertCount(1, $attributes);
	}
}
