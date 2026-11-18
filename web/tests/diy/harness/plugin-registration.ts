import type { Plugin } from '#/global'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import usePluginStore from '@/store/modules/usePluginStore.ts'
export { default as customerService } from '@/plugins/since/customer-service/index.ts'
export { default as exportCenter } from '@/plugins/since/export-center/index.ts'

// Use a separate app and Pinia so lifecycle probes cannot alter the mounted DIY editor.
export function createRegistrationFixture(plugins: Record<string, Plugin.PluginConfig>) {
  const app = createApp({ render: () => null })
  const pinia = createPinia()
  const i18n = createI18n({
    legacy: false,
    locale: 'zh_CN',
    fallbackLocale: 'zh_CN',
    messages: { zh_CN: { applicationTitle: '商城后台' } },
  })
  app.use(pinia)
  app.use(i18n)
  app.config.globalProperties.$plugins = plugins
  return { app, i18n, store: usePluginStore(pinia) }
}
