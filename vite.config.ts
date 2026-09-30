/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'node:path'

export default createAppConfig(
	{
		main: resolve(join('src', 'main.ts')),
		adminSettings: resolve(join('src', 'adminSettings.ts')),
	},
	{
		createEmptyCSSEntryPoints: true,
		// The REUSE license extraction plugin hung during "rendering chunks" on a Windows
		// checkout, so it is disabled until that is understood.
		extractLicenseInformation: false,
	},
)
