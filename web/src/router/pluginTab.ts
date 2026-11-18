import type { RouteMeta, RouteRecordRaw } from 'vue-router'

/** 插件中心及插件拥有的旧路由共享顶部标签，内部 URL 和权限保持各自定义。 */
export const pluginTabMeta: Pick<RouteMeta, 'tabKey' | 'tabTitle' | 'tabIcon'> = {
  tabKey: 'MinePluginCenterRoute',
  tabTitle: '插件中心',
  tabIcon: 'carbon:application',
}

export function markPluginTab(route: RouteRecordRaw): RouteRecordRaw {
  route.meta = { ...route.meta, ...pluginTabMeta }
  route.children?.forEach(markPluginTab)
  return route
}

/** 迁移前的主模块入口现在只包装插件视图，仍保留原菜单的权限和地址。 */
export function isLegacyPluginView(component: string): boolean {
  return ['base/views/dashboard/', 'mall/views/diy/', 'mall/views/review/']
    .some(prefix => component.startsWith(prefix))
}
