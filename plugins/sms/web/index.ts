import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  centerOnly: true,
  install(_app: App) {
    // 短信发送和验证码服务由后端插件提供，管理入口统一放在插件中心。
  },
  config: {
    enable: true,
    info: {
      name: 'since/sms',
      version: '1.0.0',
      author: 'Since',
      description: '短信验证码发送与频率限制服务',
      order: 40,
    },
  },
  center: {
    title: '短信服务',
    icon: 'carbon:notification',
    description: '管理短信验证码发送、模板和服务状态',
    order: 40,
    page: () => import('./views/PluginPage.vue'),
    settings: { page: () => import('@/modules/plugin-center/components/PluginSettingsPage.vue') },
  },
}

export default pluginConfig
