<!--
  - SPDX-FileCopyrightText: 2026 Mirza Abazovic
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { ping } from './services/api.ts'

const loading = ref(false)
const result = ref<string | null>(null)
const error = ref<string | null>(null)

/**
 * Call the backend /ping endpoint and show the answer
 */
async function onPing() {
	loading.value = true
	result.value = null
	error.value = null
	try {
		const response = await ping()
		result.value = t('orostelco', '{message} at {time}', { message: response.message, time: response.time })
	} catch {
		error.value = t('orostelco', 'The ping request failed')
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcContent appName="orostelco">
		<NcAppContent :class="$style.content">
			<div :class="$style.panel">
				<h2>{{ t('orostelco', 'Orostelco') }}</h2>
				<p>{{ t('orostelco', 'GUI for telco OSS/BSS applications.') }}</p>

				<NcButton
					data-test="ping-button"
					variant="primary"
					:disabled="loading"
					@click="onPing">
					{{ t('orostelco', 'Ping') }}
				</NcButton>

				<NcNoteCard v-if="result" data-test="ping-result" type="success">
					{{ result }}
				</NcNoteCard>
				<NcNoteCard v-if="error" data-test="ping-error" type="error">
					{{ error }}
				</NcNoteCard>
			</div>
		</NcAppContent>
	</NcContent>
</template>

<style module>
.content {
	display: flex;
	justify-content: center;
	margin: 16px;
}

.panel {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12px;
	max-width: 600px;
}
</style>
