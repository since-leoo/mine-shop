import type { Plugin } from '#/global'
const plugin: Plugin.PluginConfig = {
  centerOnly: true,
  install() {},
  config: { enable: true, info: { name: 'since/product-review', version: '1.0.0', author: 'Since', description: '商品评价', order: 40 } },
  center: { title: '商品评价', icon: 'carbon:application', order: 40, page: () => import('./views/PluginPage.vue') },
}
export default plugin
