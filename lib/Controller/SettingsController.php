<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Controller;

use InvalidArgumentException;
use OCA\Orostelco\Service\ConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * Administration settings. Endpoints without #[NoAdminRequired] are admin-only.
 *
 * @psalm-suppress UnusedClass
 */
#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
final class SettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ConfigService $config,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the OrosTelco API settings (the API key is never returned)
	 *
	 * @return DataResponse<Http::STATUS_OK, array{apiEndpoint: string, apiKeySet: bool}, array{}>
	 *
	 * 200: Current settings
	 */
	#[ApiRoute(verb: 'GET', url: '/settings')]
	public function get(): DataResponse {
		return new DataResponse($this->current());
	}

	/**
	 * Set the OrosTelco API endpoint (requires password confirmation)
	 *
	 * @param string $apiEndpoint absolute https URL (http only for localhost); empty clears it
	 * @return DataResponse<Http::STATUS_OK, array{apiEndpoint: string, apiKeySet: bool}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Endpoint saved
	 * 400: Invalid endpoint
	 */
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/settings/endpoint')]
	public function setEndpoint(string $apiEndpoint): DataResponse {
		try {
			$this->config->setApiEndpoint($apiEndpoint);
		} catch (InvalidArgumentException $invalidArgumentException) {
			return new DataResponse(['message' => $invalidArgumentException->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->current());
	}

	/**
	 * Set the OrosTelco API key (stored encrypted, requires password confirmation)
	 *
	 * @param string $apiKey the new API key
	 * @return DataResponse<Http::STATUS_OK, array{apiEndpoint: string, apiKeySet: bool}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Key saved
	 * 400: Invalid key
	 */
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/settings/key')]
	public function setKey(string $apiKey): DataResponse {
		try {
			$this->config->setApiKey($apiKey);
		} catch (InvalidArgumentException $invalidArgumentException) {
			return new DataResponse(['message' => $invalidArgumentException->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($this->current());
	}

	/**
	 * @return array{apiEndpoint: string, apiKeySet: bool}
	 */
	private function current(): array {
		return [
			'apiEndpoint' => $this->config->getApiEndpoint(),
			'apiKeySet' => $this->config->hasApiKey(),
		];
	}
}
