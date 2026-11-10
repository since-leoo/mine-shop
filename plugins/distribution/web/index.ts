import type { Plugin } from '#/global'

const plugin: Plugin.PluginConfig = {
  centerOnly: true,
  install() {},
  config: {
    enable: true,
    info: { name: 'since/distribution', version: '1.0.0', author: 'Since', description: '邀请关系、粉丝团队、一级销售佣金及提现管理', order: 45 },
  },
  center: {
    title: '分销中心',
    icon: 'ph:users-three',
    order: 45,
    businessManaged: true,
    page: () => import('./views/PluginPage.vue'),
    settings: { page: () => import('./views/Settings.vue') },
  },
}
export default plugin
