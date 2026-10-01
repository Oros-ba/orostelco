<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Service;

use OCA\Orostelco\AppInfo\Application;
use OCP\L10N\IFactory;

/**
 * Picks the answer language of an API call from its Accept-Language header.
 */
final readonly class LanguageService {
	public const FALLBACK = 'en';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IFactory $l10nFactory,
	) {
	}

	/**
	 * @param string $acceptLanguage raw header value, e.g. "de-AT,de;q=0.9,en;q=0.5"
	 * @return string the first requested language the app ships, "en" if there is none
	 */
	public function resolve(string $acceptLanguage): string {
		$available = $this->l10nFactory->findAvailableLanguages(Application::APP_ID);

		// Nextcloud writes regions upper case (de_CH), headers are compared lower case
		$byLowerCase = array_combine(array_map('strtolower', $available), $available);

		foreach ($this->parse($acceptLanguage) as $tag) {
			foreach ([$tag, explode('_', $tag)[0]] as $candidate) {
				if (isset($byLowerCase[$candidate])) {
					return $byLowerCase[$candidate];
				}
			}
		}

		return self::FALLBACK;
	}

	/**
	 * @return list<string> language tags (lower case, "_" separated) ordered by descending q-value
	 */
	private function parse(string $header): array {
		$tags = [];
		foreach (explode(',', $header) as $position => $part) {
			$fields = explode(';', trim($part));
			$tag = strtolower(str_replace('-', '_', trim($fields[0])));
			if ($tag === '' || $tag === '*' || preg_match('/^[a-z]{1,8}(_[a-z0-9]{1,8})*$/', $tag) !== 1) {
				continue;
			}

			$quality = 1.0;
			foreach (array_slice($fields, 1) as $parameter) {
				if (preg_match('/^\s*q\s*=\s*([0-9.]+)\s*$/i', $parameter, $matches) === 1) {
					$quality = (float)$matches[1];
				}
			}
			if ($quality <= 0.0) {
				continue;
			}

			$tags[] = ['tag' => $tag, 'quality' => $quality, 'position' => $position];
		}

		usort($tags, static fn (array $a, array $b): int => [$b['quality'], $a['position']] <=> [$a['quality'], $b['position']]);

		return array_map(static fn (array $entry): string => $entry['tag'], $tags);
	}
}
