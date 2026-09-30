<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Orostelco\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_ID, Application::APP_ID . '-main');
Util::addStyle(Application::APP_ID, Application::APP_ID . '-main');

?>

<div id="orostelco"></div>
