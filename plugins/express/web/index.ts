import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  centerOnly: true,
  install(_app: App) {
    // 物流查询能力由后端插件提供，管理入口统一放在插件中心。
  },
  config: {
    enable: true,
    info: {
      name: 'since/express',
      version: '1.0.0',
      author: 'Since',
      description: '物流轨迹查询与快递服务配置',
      order: 30,
    },
  },
  center: {
    title: '物流查询',
    icon: 'carbon:delivery-truck',
    description: '管理物流轨迹查询服务和快递100接口配置',
    order: 30,
    page: () => import('./views/PluginPage.vue'),
    settings: { page: () => import('@/modules/plugin-center/components/PluginSettingsPage.vue') },
  },
}

export default pluginConfig
