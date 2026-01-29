/**
 * 插件菜单配置属性测试
 * 
 * **功能: plugin-frontend-menu, 属性 1: 菜单定义完整性**
 * **验证需求：需求 1.1, 1.2, 1.3, 1.4**
 * 
 * 这是一个编译时类型测试文件，用于验证插件菜单配置的完整性
 * 如果类型定义有问题，TypeScript 编译器会报错
 * 
 * 运行方式: npm run lint:tsc (vue-tsc --noEmit)
 */

import type { App } from 'vue'
import type { PluginMenu, PluginConfigWithMenus } from '../../types/plugin-menu'

/**
 * 属性 1.1: 插件配置应支持 menus 属性来定义菜单结构
 * 
 * *对于任何*有效的插件菜单配置，系统应能够正确解析包含 menus 属性的配置
 */
const testMenusPropertySupport: PluginConfigWithMenus = {
  install(_app: App) {},
  config: {
    enable: true,
    info: {
      name: 'test-plugin',
      version: '1.0.0',
      author: 'Test',
      description: 'Test plugin with menus',
    },
  },
  menus: [
    {
      name: 'test:menu',
      path: '/test',
      meta: { title: '测试菜单' },
    },
  ],
}

/**
 * 属性 1.2: 菜单定义应支持多级嵌套结构（父菜单和子菜单）
 * 
 * *对于任何*有效的插件菜单配置，系统应能够正确解析任意深度嵌套的菜单结构
 */
const testNestedMenuStructure: PluginMenu = {
  name: 'level1',
  path: '/level1',
  meta: { title: '一级菜单', type: 'M' },
  children: [
    {
      name: 'level2',
      path: '/level1/level2',
      meta: { title: '二级菜单', type: 'M' },
      children: [
        {
          name: 'level3',
          path: '/level1/level2/level3',
          meta: { title: '三级菜单', type: 'M' },
          children: [
            {
              name: 'level4',
              path: '/level1/level2/level3/level4',
              meta: { title: '四级菜单', type: 'M' },
            },
          ],
        },
      ],
    },
  ],
}

/**
 * 属性 1.3: 菜单定义应支持完整的菜单元数据（标题、图标、路径、权限等）
 * 
 * *对于任何*有效的插件菜单配置，系统应能够正确解析各种元数据组合
 */
const testFullMetadata: PluginMenu = {
  name: 'full-meta-menu',
  path: '/full-meta',
  redirect: '/full-meta/child',
  expand: true,
  meta: {
    title: '完整元数据菜单',
    i18n: 'menu.fullMeta',
    badge: () => 'New',
    icon: 'ep:setting',
    affix: false,
    hidden: false,
    subForceShow: true,
    copyright: true,
    link: undefined,
    breadcrumbEnable: true,
    activeName: 'full-meta-menu',
    cache: true,
    type: 'M',
    auth: ['menu:view', 'menu:edit'],
    role: ['admin', 'manager'],
    user: ['user1', 'user2'],
  },
}

/**
 * 属性 1.4: 菜单定义应支持国际化配置（i18n）
 * 
 * *对于任何*有效的插件菜单配置，系统应能够正确解析国际化配置
 */
const testI18nSupport: PluginMenu = {
  name: 'i18n-menu',
  path: '/i18n',
  meta: {
    title: '国际化菜单',
    i18n: 'plugin.systemMessage.title',
    icon: 'ep:message',
    type: 'M',
  },
  children: [
    {
      name: 'i18n-menu:child1',
      path: '/i18n/child1',
      meta: {
        title: '子菜单1',
        i18n: 'plugin.systemMessage.list',
        icon: 'ep:list',
        type: 'M',
      },
    },
    {
      name: 'i18n-menu:child2',
      path: '/i18n/child2',
      meta: {
        title: '子菜单2',
        i18n: 'plugin.systemMessage.statistics',
        icon: 'ep:data-analysis',
        type: 'M',
      },
    },
  ],
}

/**
 * 测试 system-message 插件的实际配置结构
 * 
 * 验证实际插件配置符合类型定义
 */
const testSystemMessagePluginConfig: PluginConfigWithMenus = {
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
      order: 100,
    },
  },
  menus: [
    {
      name: 'plugin:system:message',
      path: '/admin/system-message',
      redirect: '/admin/system-message/list',
      meta: {
        title: '消息管理',
        i18n: 'plugin.systemMessage.title',
        icon: 'ep:message',
        type: 'M',
      },
      children: [
        {
          name: 'plugin:system:message:list',
          path: '/admin/system-message/list',
          meta: {
            title: '消息列表',
            i18n: 'plugin.systemMessage.list',
            icon: 'ep:list',
            type: 'M',
            auth: ['system-message:list'],
          },
        },
        {
          name: 'plugin:system:message:statistics',
          path: '/admin/system-message/statistics',
          meta: {
            title: '消息统计',
            i18n: 'plugin.systemMessage.statistics',
            icon: 'ep:data-analysis',
            type: 'M',
            auth: ['system-message:dashboard'],
          },
        },
        {
          name: 'plugin:system:message:settings',
          path: '/admin/system-message/settings',
          meta: {
            title: '消息设置',
            i18n: 'plugin.systemMessage.settings',
            icon: 'ep:setting',
            type: 'M',
            auth: ['system-message:settings'],
          },
        },
      ],
    },
  ],
  views: [
    {
      name: 'plugin:system:message:list',
      path: '/admin/system-message/list',
      meta: {
        title: '消息列表',
        hidden: true,
      },
      component: () => Promise.resolve({ default: {} }),
    },
    {
      name: 'plugin:system:message:statistics',
      path: '/admin/system-message/statistics',
      meta: {
        title: '消息统计',
        hidden: true,
      },
      component: () => Promise.resolve({ default: {} }),
    },
    {
      name: 'plugin:system:message:settings',
      path: '/admin/system-message/settings',
      meta: {
        title: '消息设置',
        hidden: true,
      },
      component: () => Promise.resolve({ default: {} }),
    },
  ],
}

/**
 * 测试外链菜单类型
 */
const testExternalLinkMenu: PluginMenu = {
  name: 'external-link',
  path: '/external',
  meta: {
    title: '外部链接',
    type: 'L',
    link: 'https://github.com',
    icon: 'ep:link',
  },
}

/**
 * 测试 iframe 菜单类型
 */
const testIframeMenu: PluginMenu = {
  name: 'iframe-menu',
  path: '/iframe',
  meta: {
    title: 'Iframe 页面',
    type: 'I',
    link: 'https://example.com',
    icon: 'ep:monitor',
  },
}

/**
 * 测试按钮类型菜单
 */
const testButtonMenu: PluginMenu = {
  name: 'button-menu',
  path: '/button',
  meta: {
    title: '按钮',
    type: 'B',
    auth: ['button:click'],
  },
}

// 导出以避免 "unused variable" 警告
export {
  testMenusPropertySupport,
  testNestedMenuStructure,
  testFullMetadata,
  testI18nSupport,
  testSystemMessagePluginConfig,
  testExternalLinkMenu,
  testIframeMenu,
  testButtonMenu,
}
