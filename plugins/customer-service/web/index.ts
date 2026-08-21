import type { App } from 'vue'
import type { Plugin } from '#/global'
import locales from './locales'

const pluginConfig: Plugin.PluginConfig = {
  install(app: App) {
    const i18n = app.config.globalProperties.$i18n
    if (i18n) {
      Object.entries(locales).forEach(([locale, messages]) => i18n.mergeLocaleMessage(locale, messages))
    }
  },
  config: {
    enable: true,
    info: {
      name: 'since/customer-service',
      version: '0.1.0',
      author: 'Since Team',
      description: '商城实时客服工作台',
      order: 102,
    },
  },
}

export default pluginConfig
