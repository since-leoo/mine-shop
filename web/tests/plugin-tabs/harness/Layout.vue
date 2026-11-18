<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Tabbar from '@/layouts/components/bars/tabbar/index.tsx'
import useTabStore from '@/store/modules/useTabStore.ts'
import useKeepAliveStore from '@/store/modules/useKeepAliveStore.ts'
import useIframeKeepAliveStore from '@/store/modules/useIframeKeepAliveStore.ts'
import useCache from '@/hooks/useCache.ts'
import { mounts } from './runtime'

const route = useRoute()
const router = useRouter()
const tabs = useTabStore()
const keepAlive = useKeepAliveStore()
const iframe = useIframeKeepAliveStore()
tabs.initTab()
tabs.normalizeTabs()
const current = computed(() => route.fullPath)
// Mirror the application keep-alive registration lifecycle, retaining actual components.
watch(() => route.fullPath, () => {
  if (!route.meta.cache) return
  const component = route.matched.at(-1)?.components?.default as any
  keepAlive.add(component?.name || String(route.name))
}, { immediate: true })
;(window as any).__pluginTabsTest = {
  snapshot: () => JSON.parse(JSON.stringify({
    tabs: tabs.tabList,
    current: route.fullPath,
    currentName: route.name,
    cached: useCache().get('tabList', []),
    alive: keepAlive.list,
    iframe: iframe.iframeList,
    mounts,
    routes: router.getRoutes().map(item => ({ name: item.name, path: item.path, tabKey: item.meta.tabKey, tabTitle: item.meta.tabTitle, tabIcon: item.meta.tabIcon })),
  })),
}
</script>

<template>
  <Tabbar />
  <nav aria-label="测试导航">
    <RouterLink to="/plugin-center">插件总览</RouterLink>
    <RouterLink to="/items/1?view=a">普通商品 A</RouterLink>
    <RouterLink to="/items/1?view=b">普通商品 B</RouterLink>
    <RouterLink to="/items/2?view=a">普通商品 C</RouterLink>
    <RouterLink to="/other">其它页面</RouterLink>
    <RouterLink to="/legacy-wechat?tab=menu">微信旧菜单</RouterLink>
    <RouterLink to="/legacy-diy?page=2">装修旧菜单</RouterLink>
    <RouterLink to="/legacy-workbench">工作台旧菜单</RouterLink>
    <RouterLink to="/views-plugin/17?tab=detail">插件声明路由</RouterLink>
    <RouterLink to="/hook-plugin/31?tab=detail">插件 hook 路由</RouterLink>
    <RouterLink to="/dashboard/analysis">静态工作台</RouterLink>
    <button @click="tabs.closeCurrentTab()">关闭当前标签</button>
    <button @click="tabs.changeTabTitle('子页面自定义标题')">改当前标题</button>
  </nav>
  <output data-testid="current-url">{{ current }}</output>
  <RouterView v-slot="{ Component }">
    <KeepAlive :include="keepAlive.list">
      <component :is="Component" v-if="keepAlive.getShowState()" :key="route.fullPath" />
    </KeepAlive>
  </RouterView>
</template>
