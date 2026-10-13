import type { Plugin } from '#/global'
import { registerDiyLegacyRoutes } from './navigation.ts'
const plugin: Plugin.PluginConfig = {
  centerOnly: true,
  install() {},
  config: { enable: true, info: { name: 'since/diy', version: '1.0.0', author: 'Since', description: '店铺装修', order: 40 } },
  center: { title: '店铺装修', icon: 'carbon:application', order: 40, page: () => import('./views/PluginPage.vue') },
  hooks: { registerRoute: registerDiyLegacyRoutes },
}
export default plugin
