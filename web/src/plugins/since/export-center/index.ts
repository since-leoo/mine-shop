/**
 * 导出中心插件入口文件
 *
 * @description 导出中心插件前端组件
 * 菜单由后端数据库管理，前端只需要注册插件配置
 */
import type { App } from 'vue'
import type { Plugin } from '#/global'
import locales from './locales'

const pluginConfig: Plugin.PluginConfig = {
  install(app: App) {
    // 注册多语言
    const i18n = app.config.globalProperties.$i18n
    if (i18n) {
      Object.entries(locales).forEach(([lang, messages]) => {
        i18n.mergeLocaleMessage(lang, messages)
      })
    }
    console.log('[Plugin] 导出中心插件已启动')
  },
  config: {
    enable: true,
    info: {
      name: 'since/export-center',
      version: '1.0.0',
      author: 'Since Team',
      description: '导出中心插件前端组件',
      order: 101,
    },
  },
}

export default pluginConfig
