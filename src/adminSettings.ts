/**
 * SPDX-FileCopyrightText: 2026 Mirza Abazovic
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

import '@nextcloud/password-confirmation/style.css'

createApp(AdminSettings).mount('#orostelco-admin-settings')
