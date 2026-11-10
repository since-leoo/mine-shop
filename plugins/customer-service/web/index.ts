import type { App } from 'vue'
import type { Plugin } from '#/global'
import locales from './locales'

const pluginConfig: Plugin.PluginConfig = {
  centerOnly: true,
  install(_app: App, { i18n }: Plugin.InstallOptions = {}) {
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
  center: {
    title: '客服中心',
    icon: 'ant-design:customer-service-outlined',
    description: '统一管理客服会话、坐席和常见问题',
    order: 20,
    page: () => import('./views/PluginPage.vue'),
  },
}

export default pluginConfig
