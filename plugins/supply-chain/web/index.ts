import type { Plugin } from '#/global'

const plugin: Plugin.PluginConfig = {
  centerOnly: true,
  install() {},
  config: {
    enable: true,
    info: { name: 'since/supply-chain', version: '1.0.0', author: 'Since', description: '供应链选品、同步、采购履约与执行日志', order: 46 },
  },
  center: {
    title: '供应链',
    icon: 'carbon:delivery',
    order: 46,
    businessManaged: true,
    page: () => import('./views/PluginPage.vue'),
  },
}

export default plugin
