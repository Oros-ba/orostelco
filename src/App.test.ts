/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App.vue'
import { ping } from './services/api.ts'

vi.mock('./services/api.ts', () => ({ ping: vi.fn() }))

describe('App', () => {
	beforeEach(() => {
		vi.mocked(ping).mockReset()
	})

	it('calls the ping endpoint and shows the pong when the button is clicked', async () => {
		vi.mocked(ping).mockResolvedValue({ message: 'pong', time: '2026-09-30T18:00:00+00:00' })
		const wrapper = mount(App)

		expect(wrapper.find('[data-test="ping-result"]').exists()).toBe(false)
		await wrapper.find('[data-test="ping-button"]').trigger('click')
		await flushPromises()

		expect(ping).toHaveBeenCalledTimes(1)
		expect(wrapper.find('[data-test="ping-result"]').text()).toContain('pong')
		expect(wrapper.find('[data-test="ping-result"]').text()).toContain('2026-09-30T18:00:00+00:00')
	})

	it('shows an error when the ping fails', async () => {
		vi.mocked(ping).mockRejectedValue(new Error('network'))
		const wrapper = mount(App)

		await wrapper.find('[data-test="ping-button"]').trigger('click')
		await flushPromises()

		expect(wrapper.find('[data-test="ping-error"]').exists()).toBe(true)
		expect(wrapper.find('[data-test="ping-result"]').exists()).toBe(false)
	})
})
