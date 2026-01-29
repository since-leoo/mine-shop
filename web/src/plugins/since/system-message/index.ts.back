/**
 * 系统消息插件入口文件
 * 
 * @description 系统消息通知管理插件
 * 菜单由后端数据库管理，前端只需要注册插件配置
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
  // 菜单由后端数据库管理，组件通过 pluginViews glob 自动加载
  // 无需在此定义 views，后端 menu 表的 component 字段会自动匹配
}

export default pluginConfig
