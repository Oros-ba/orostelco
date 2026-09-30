/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'

export interface PingResponse {
	message: string
	time: string
}

export interface Settings {
	apiEndpoint: string
	apiKeySet: boolean
}

interface OcsResponse<T> {
	ocs: { data: T }
}

const OCS_HEADERS = {
	'OCS-APIRequest': 'true',
	Accept: 'application/json',
}

/**
 * Build the OCS url of an orostelco route
 *
 * @param path route below apps/orostelco, starting with a slash
 */
function url(path: string): string {
	return generateOcsUrl('apps/orostelco' + path)
}

/**
 * Call the backend /ping endpoint
 */
export async function ping(): Promise<PingResponse> {
	const { data } = await axios.get<OcsResponse<PingResponse>>(url('/ping'), { headers: OCS_HEADERS })
	return data.ocs.data
}

/**
 * Read the admin settings (the API key itself is never returned, only whether it is set)
 */
export async function getSettings(): Promise<Settings> {
	const { data } = await axios.get<OcsResponse<Settings>>(url('/settings'), { headers: OCS_HEADERS })
	return data.ocs.data
}

/**
 * Save the OrosTelco API endpoint
 *
 * @param apiEndpoint absolute https URL (empty clears it)
 */
export async function saveEndpoint(apiEndpoint: string): Promise<Settings> {
	const { data } = await axios.put<OcsResponse<Settings>>(url('/settings/endpoint'), { apiEndpoint }, { headers: OCS_HEADERS })
	return data.ocs.data
}

/**
 * Save the OrosTelco API key, asks the admin to confirm their password first
 *
 * @param apiKey the new API key
 */
export async function saveKey(apiKey: string): Promise<Settings> {
	await confirmPassword()
	const { data } = await axios.put<OcsResponse<Settings>>(url('/settings/key'), { apiKey }, { headers: OCS_HEADERS })
	return data.ocs.data
}
