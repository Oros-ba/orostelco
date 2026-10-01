<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Orostelco\Tests\Unit\Service;

use OCA\Orostelco\Service\LanguageService;
use OCP\L10N\IFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LanguageServiceTest extends TestCase {
	/**
	 * @return array<string, array{string, string}>
	 */
	public static function headers(): array {
		return [
			'plain german' => ['de', 'de'],
			'german with region' => ['de-DE', 'de'],
			'austrian german prefers german over english' => ['de-AT,en;q=0.5', 'de'],
			'higher q wins' => ['en-US,de;q=0.4', 'en'],
			'q order beats position' => ['en;q=0.3,de;q=0.9', 'de'],
			'unsupported language falls back' => ['fr', 'en'],
			'unsupported first, german second' => ['fr,de;q=0.8', 'de'],
			'region the app ships is kept' => ['de-CH', 'de_CH'],
			'empty header' => ['', 'en'],
			'garbage header' => ['<script>,;;,q=', 'en'],
			'wildcard' => ['*', 'en'],
			'q=0 is ignored' => ['de;q=0, en', 'en'],
			'upper case' => ['DE-de', 'de'],
		];
	}

	#[DataProvider('headers')]
	public function testResolve(string $header, string $expected): void {
		$factory = $this->createMock(IFactory::class);
		$factory->method('findAvailableLanguages')->with('orostelco')->willReturn(['en', 'de', 'de_CH']);

		$this->assertSame($expected, (new LanguageService($factory))->resolve($header));
	}
}
