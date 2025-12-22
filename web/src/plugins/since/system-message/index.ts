/**
 * 系统消息插件入口文件
 */
import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  install(_app: App) {
    console.log('[Plugin] 系统消息插件已启动')
  },
  config: {
    enable: true,
    info: {
      name: 'since/system-message',
      version: '1.0.0',
      author: 'Since Team',
      description: '系统消息通知管理插件',
      order: 100
    },
  },
  views: [
    // 管理端路由 - 与后台菜单路径匹配
    {
      name: 'AdminMessageList',
      path: '/admin/system-message/list',
      meta: {
        title: '消息列表',
        permission: 'system-message:list'
      },
      component: () => import('./views/admin/AdminMessageList.vue')
    },
    {
      name: 'AdminMessageStatistics',
      path: '/admin/system-message/statistics',
      meta: {
        title: '消息统计',
        permission: 'system-message:dashboard'
      },
      component: () => import('./views/admin/AdminDashboard.vue')
    },
    {
      name: 'AdminMessageSettings',
      path: '/admin/system-message/settings',
      meta: {
        title: '消息设置',
        permission: 'system-message:settings'
      },
      component: () => import('./views/NotificationSettings.vue')
    }
  ],
}

export default pluginConfig