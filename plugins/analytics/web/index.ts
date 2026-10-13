import type { Plugin } from '#/global'
const plugin: Plugin.PluginConfig = {
  centerOnly: true,
  install() {},
  config: { enable: true, info: { name: 'since/analytics', version: '1.0.0', author: 'Since', description: '经营分析', order: 40 } },
  center: { title: '经营分析', icon: 'carbon:application', order: 40, page: () => import('./views/PluginPage.vue') },
}
export default plugin
