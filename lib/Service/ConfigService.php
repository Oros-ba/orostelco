<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Service;

use InvalidArgumentException;
use OCP\AppFramework\Services\IAppConfig;

/**
 * Instance-wide OrosTelco API settings, managed by administrators.
 *
 * The API key is stored as a sensitive app value (encrypted at rest) and is
 * intentionally never exposed through anything that feeds an API response.
 */
final readonly class ConfigService {
	public const KEY_API_ENDPOINT = 'api_endpoint';

	public const KEY_API_KEY = 'api_key';

	private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function getApiEndpoint(): string {
		return $this->appConfig->getAppValueString(self::KEY_API_ENDPOINT);
	}

	public function hasApiKey(): bool {
		return $this->appConfig->getAppValueString(self::KEY_API_KEY) !== '';
	}

	/**
	 * @param string $endpoint absolute https URL (http only for localhost), or an empty string to clear it
	 * @throws InvalidArgumentException when the URL is not acceptable
	 */
	public function setApiEndpoint(string $endpoint): void {
		$this->appConfig->setAppValueString(self::KEY_API_ENDPOINT, $this->normalizeEndpoint($endpoint));
	}

	/**
	 * @throws InvalidArgumentException when the key is empty
	 */
	public function setApiKey(string $apiKey): void {
		$apiKey = trim($apiKey);
		if ($apiKey === '') {
			throw new InvalidArgumentException('The API key must not be empty.');
		}

		$this->appConfig->setAppValueString(self::KEY_API_KEY, $apiKey, sensitive: true);
	}

	private function normalizeEndpoint(string $endpoint): string {
		$endpoint = trim($endpoint);
		if ($endpoint === '') {
			return '';
		}

		$parts = parse_url($endpoint);
		if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
			throw new InvalidArgumentException('The API endpoint must be an absolute URL.');
		}

		if (isset($parts['user']) || isset($parts['pass'])) {
			throw new InvalidArgumentException('The API endpoint must not contain credentials.');
		}

		if (isset($parts['query']) || isset($parts['fragment'])) {
			throw new InvalidArgumentException('The API endpoint must not contain a query string or fragment.');
		}

		$scheme = strtolower($parts['scheme']);
		$isLocal = in_array(strtolower($parts['host']), self::LOCAL_HOSTS, true);
		if ($scheme !== 'https' && ($scheme !== 'http' || !$isLocal)) {
			throw new InvalidArgumentException('The API endpoint must use https (http is only allowed for localhost).');
		}

		return rtrim($endpoint, '/');
	}
}
