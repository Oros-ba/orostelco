<!--
  - SPDX-FileCopyrightText: 2026 Mirza Abazovic
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { getSettings, saveEndpoint, saveKey } from '../services/api.ts'

const apiEndpoint = ref('')
const apiKey = ref('')
const apiKeySet = ref(false)
const saving = ref(false)
const endpointError = ref('')

onMounted(async () => {
	try {
		const settings = await getSettings()
		apiEndpoint.value = settings.apiEndpoint
		apiKeySet.value = settings.apiKeySet
	} catch {
		showError(t('orostelco', 'Could not load the settings'))
	}
})

/**
 * Extract the server side validation message of a failed request, if any
 *
 * @param e error thrown by axios
 */
function messageOf(e: unknown): string {
	const message = (e as { response?: { data?: { ocs?: { data?: { message?: string } } } } })
		?.response?.data?.ocs?.data?.message
	return message ?? t('orostelco', 'Saving failed')
}

/**
 * Save the endpoint and, when one was typed, the API key
 */
async function onSave() {
	saving.value = true
	endpointError.value = ''
	try {
		try {
			await saveEndpoint(apiEndpoint.value)
		} catch (e) {
			endpointError.value = messageOf(e)
			return
		}

		if (apiKey.value.trim() !== '') {
			const settings = await saveKey(apiKey.value)
			apiKeySet.value = settings.apiKeySet
			apiKey.value = ''
		}
		showSuccess(t('orostelco', 'Settings saved'))
	} catch (e) {
		showError(messageOf(e))
	} finally {
		saving.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('orostelco', 'Orostelco')"
		:description="t('orostelco', 'Connection to the OrosTelco API, shared by all users of this instance.')">
		<div :class="$style.form">
			<NcTextField
				v-model="apiEndpoint"
				data-test="endpoint"
				:label="t('orostelco', 'OrosTelco API endpoint')"
				placeholder="https://api.example.com"
				:error="endpointError !== ''"
				:helperText="endpointError" />

			<NcPasswordField
				v-model="apiKey"
				data-test="key"
				autocomplete="new-password"
				:label="t('orostelco', 'OrosTelco API key')"
				:helperText="apiKeySet ? t('orostelco', 'A key is already set. Leave empty to keep it.') : ''" />

			<NcButton
				data-test="save"
				variant="primary"
				:disabled="saving"
				@click="onSave">
				{{ t('orostelco', 'Save') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<style module>
.form {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12px;
	max-width: 400px;
}
</style>
