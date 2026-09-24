import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  centerOnly: true,
  install(_app: App) {
    console.log('[Plugin] 微信组件已启动')
  },
  config: {
    enable: true,
    info: {
      name: 'since/wechat',
      version: '1.0.0',
      author: 'Since Team',
      description: '微信小程序与公众号能力组件',
      order: 30,
    },
  },
  center: {
    title: '微信组件',
    icon: 'ant-design:wechat-outlined',
    description: '管理微信小程序与公众号的服务配置和运行状态',
    order: 30,
    page: () => import('./views/PluginPage.vue'),
    settings: { page: () => import('@/modules/plugin-center/components/PluginSettingsPage.vue') },
  },
}

export default pluginConfig
