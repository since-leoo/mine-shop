import type { LocationQueryRaw, Router, RouteLocationRaw } from 'vue-router'

/** 装修子页面由插件中心承载，不依赖商城菜单是否配置了隐藏路由。 */
export function diyCenterLocation(query: LocationQueryRaw = {}): RouteLocationRaw {
  return {
    name: 'MinePluginCenterRoute',
    params: { pluginName: ['since', 'diy'] },
    query,
  }
}

/** 兼容收藏的旧地址；已有菜单路由继续保留，避免覆盖其权限配置。 */
export function registerDiyLegacyRoutes(router: Router) {
  for (const view of ['page', 'editor'] as const) {
    const path = `/mall/diy/${view}`
    if (router.getRoutes().some(route => route.path === path)) {
      continue
    }
    router.addRoute('MineRootLayoutRoute', {
      name: `since:diy:legacy-${view}`,
      path,
      redirect: to => diyCenterLocation(view === 'editor' ? { ...to.query, view: 'editor' } : {}),
    })
  }
}
