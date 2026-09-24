import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  centerOnly: true,
  install(_app: App) {
    console.log('[Plugin] 企业微信 SCRM 已启动')
  },
  config: {
    enable: true,
    info: {
      name: 'since/wecom-scrm',
      version: '0.1.0',
      author: 'Since Team',
      description: '企业微信客户、标签与群 SOP 管理',
      order: 102,
    },
  },
  center: {
    title: '企业微信 SCRM',
    icon: 'ant-design:team-outlined',
    description: '统一管理企业微信客户、标签、群聊与群 SOP',
    order: 40,
    page: () => import('./views/PluginPage.vue'),
    settings: { page: () => import('@/modules/plugin-center/components/PluginSettingsPage.vue') },
  },
}

export default pluginConfig
