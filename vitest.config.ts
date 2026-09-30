/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
	plugins: [vue()],
	test: {
		environment: 'jsdom',
		// the forks pool fails to start workers on some Windows setups
		pool: 'threads',
		include: ['src/**/*.test.ts'],
		// @nextcloud/vue and friends ship ESM that must go through vite
		server: { deps: { inline: [/@nextcloud\//] } },
	},
})
