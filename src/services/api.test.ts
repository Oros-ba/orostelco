/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getSettings, ping, saveEndpoint, saveKey } from './api.ts'

vi.mock('@nextcloud/axios', () => ({ default: { get: vi.fn(), put: vi.fn() } }))
vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword: vi.fn().mockResolvedValue(undefined) }))
vi.mock('@nextcloud/router', () => ({ generateOcsUrl: (path: string) => '/ocs/v2.php/' + path }))

const ocs = <T>(data: T) => ({ data: { ocs: { data } } })

describe('api', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('pings the ocs endpoint and unwraps the payload', async () => {
		vi.mocked(axios.get).mockResolvedValue(ocs({ message: 'pong', time: 'now' }))

		await expect(ping()).resolves.toEqual({ message: 'pong', time: 'now' })
		expect(axios.get).toHaveBeenCalledWith('/ocs/v2.php/apps/orostelco/ping', expect.anything())
	})

	it('reads the settings', async () => {
		vi.mocked(axios.get).mockResolvedValue(ocs({ apiEndpoint: 'https://a.example', apiKeySet: true }))

		await expect(getSettings()).resolves.toEqual({ apiEndpoint: 'https://a.example', apiKeySet: true })
	})

	it('saves the endpoint without asking for a password', async () => {
		vi.mocked(axios.put).mockResolvedValue(ocs({ apiEndpoint: 'https://a.example', apiKeySet: false }))

		await saveEndpoint('https://a.example')

		expect(confirmPassword).not.toHaveBeenCalled()
		expect(axios.put).toHaveBeenCalledWith(
			'/ocs/v2.php/apps/orostelco/settings/endpoint',
			{ apiEndpoint: 'https://a.example' },
			expect.anything(),
		)
	})

	it('asks for the password before saving the key', async () => {
		vi.mocked(axios.put).mockResolvedValue(ocs({ apiEndpoint: '', apiKeySet: true }))

		await saveKey('secret')

		expect(confirmPassword).toHaveBeenCalledTimes(1)
		expect(axios.put).toHaveBeenCalledWith(
			'/ocs/v2.php/apps/orostelco/settings/key',
			{ apiKey: 'secret' },
			expect.anything(),
		)
	})
})
