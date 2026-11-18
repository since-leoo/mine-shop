/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import { useNProgress } from '@vueuse/integrations/useNProgress'
import { useSorted } from '@vueuse/core'
import type { MineRoute, MineTabbar, SystemSettings } from '#/global'
import useCache from '@/hooks/useCache.ts'

const useTabStore = defineStore(
  'useTabStore',
  () => {
    const { set, get } = useCache()
    const route = useRoute()
    const router = useRouter()
    const settingStore = useSettingStore()
    const keepAliveStore = useKeepAliveStore()
    const iframeKeepLiveStore = useIframeKeepAliveStore()
    const welcomePage = settingStore.getSettings('welcomePage') as SystemSettings.welcomePage
    const tabList = ref<MineTabbar[]>([])
    const { isLoading } = useNProgress()
    const defaultTab = ref<MineTabbar>({
      name: welcomePage.name,
      path: welcomePage.path,
      fullPath: welcomePage.path,
      i18n: 'menu.welcome',
      icon: welcomePage.icon,
      title: welcomePage.title,
      affix: true,
    } as MineTabbar)

    function initTab() {
      const cachedTabs = get('tabList', [])
      tabList.value = Array.isArray(cachedTabs) ? cachedTabs : []
      normalizeTabs()
      if (tabList.value?.length === 0) {
        addTab(defaultTab.value)

        const temp = router.getRoutes().filter(item => keepAliveStore.list.includes(item.name as string)) as MineRoute.routeRecord[]
        temp?.map((item: any) => {
          addTab({
            name: item.name,
            path: item.path,
            fullPath: item.fullPath,
            i18n: item.meta?.i18n,
            icon: item.meta?.icon,
            title: item.meta?.title,
            affix: item.meta?.affix === true,
            tabKey: item.meta?.tabKey,
            tabTitle: item.meta?.tabTitle,
            tabIcon: item.meta?.tabIcon,
          } as MineTabbar)
        })
      }

      watch(() => tabList.value, () => storage(), { immediate: true, deep: true })
    }

    function getTabKey(tab: MineTabbar): string {
      return tab.tabKey || tab.fullPath || tab.path
    }

    function currentTabKey(): string {
      return typeof route.meta.tabKey === 'string' && route.meta.tabKey
        ? route.meta.tabKey
        : route.fullPath
    }

    function isCurrentTab(tab: MineTabbar): boolean {
      return getTabKey(tab) === currentTabKey()
    }

    function normalizeTab(tab: MineTabbar): MineTabbar {
      const fullPath = tab.fullPath || tab.path
      const resolved = router.resolve(fullPath)
      const meta = resolved.meta
      const normalized = { ...tab, fullPath }
      for (const key of ['tabKey', 'tabTitle', 'tabIcon'] as const) {
        if (typeof meta[key] === 'string' && meta[key]) normalized[key] = meta[key] as string
      }
      if (normalized.tabKey) {
        if (normalized.tabTitle) {
          normalized.title = normalized.tabTitle
          delete normalized.i18n
        }
        if (normalized.tabIcon) normalized.icon = normalized.tabIcon
      }
      return normalized
    }

    /** 动态路由注册完成后再次调用，将旧缓存中的插件子页归并到同一个标签。 */
    function normalizeTabs() {
      const normalized: MineTabbar[] = []
      const byKey = new Map<string, MineTabbar>()
      for (const tab of tabList.value) {
        if (!tab || !(tab.fullPath || tab.path)) continue
        const next = normalizeTab(tab)
        const key = getTabKey(next)
        const existing = byKey.get(key)
        if (existing) {
          const affix = existing.affix || next.affix
          Object.assign(existing, next, { affix })
        }
        else {
          byKey.set(key, next)
          normalized.push(next)
        }
      }
      // 恢复旧缓存时，以当前地址为准，避免激活标签退回另一个插件子页。
      const active = byKey.get(currentTabKey())
      if (active?.tabKey && route.name) {
        Object.assign(active, normalizeTab({
          ...active,
          name: String(route.name),
          path: route.path,
          fullPath: route.fullPath,
        }))
      }
      tabList.value = normalized
      storage()
    }

    function findTab(tab: MineTabbar): MineTabbar | undefined {
      const key = getTabKey(normalizeTab(tab))
      return tabList.value.find(item => getTabKey(item) === key)
    }

    function removeTabCache(tab: MineTabbar) {
      const names = new Set([tab.name])
      if (tab.tabKey) {
        for (const record of router.getRoutes()) {
          if (record.meta.tabKey !== tab.tabKey) continue
          if (typeof record.name === 'string') names.add(record.name)
          const component = record.components?.default as { name?: string } | undefined
          if (component?.name) names.add(component.name)
        }
      }
      keepAliveStore.remove([...names])
      if (tab.tabKey || tab.path.indexOf('MineIframe') > 0) iframeKeepLiveStore.remove([...names])
    }

    function affixTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (!target) return
      target.affix = true
      useSorted(tabList.value,
        (a: MineTabbar, b: MineTabbar) => Number(b.affix) - Number(a.affix),
        { dirty: true },
      )
    }

    function cancelAffixTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (target) target.affix = false
    }

    function addTab(route: MineTabbar) {
      if (route.name === 'MineSystemError' || settingStore.getSettings('app').whiteRoute.includes(route.name)) {
        return
      }
      const next = normalizeTab(route)
      const existing = tabList.value.find(item => getTabKey(item) === getTabKey(next))
      if (existing) {
        if (next.tabKey) {
          Object.assign(existing, next, { affix: existing.affix })
          if (existing.tabTitle) delete existing.i18n
        }
      }
      else {
        tabList.value.push({ ...next, affix: next.affix === true })
      }
    }

    async function maxSizeTab(tab: MineTabbar) {
      await go(tab)
      document.querySelector('#app')?.classList.add('mine-max-size')
    }

    function exitMaxSizeTab() {
      document.querySelector('#app')?.classList.remove('mine-max-size')
    }

    async function refreshTab(tab: MineTabbar | null = null) {
      const target = tab ? findTab(tab) : getCurrentTab()
      if (!target) return
      isLoading.value = true
      keepAliveStore.hidden()
      removeTabCache(target)
      try {
        await nextTick()
        const resolved = router.resolve(target.fullPath)
        const component = resolved.matched.at(-1)?.components?.default as { name?: string } | undefined
        if (resolved.meta.type === 'I') iframeKeepLiveStore.add(target.name)
        else if (resolved.meta.cache) keepAliveStore.add(component?.name || target.name)
        keepAliveStore.display()
        await go(target)
      }
      finally {
        keepAliveStore.display()
        isLoading.value = false
      }
    }

    async function closeTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (!target || target.affix) return
      const key = getTabKey(target)
      const index = tabList.value.findIndex(item => getTabKey(item) === key)
      if (isCurrentTab(target)) {
        const neighbor = tabList.value[index + 1] ?? tabList.value[index - 1]
        if (neighbor) await router.push(neighbor.fullPath)
      }
      tabList.value = tabList.value.filter(item => getTabKey(item) !== key)
      removeTabCache(target)
    }

    async function closeOtherTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (!target) return
      tabList.value = tabList.value?.filter((item: any) => {
        if (getTabKey(item) === getTabKey(target) || item.affix === true) {
          return true
        }
        else {
          removeTabCache(item)
          return false
        }
      })
      await go(target)
    }
    async function closeLeftTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (!target) return
      const index = tabList.value.findIndex(item => getTabKey(item) === getTabKey(target))

      if (index !== -1) {
        tabList.value = tabList.value.filter((item: MineTabbar, idx: number) => {
          if (idx >= index || item.affix === true) {
            return true
          }
          else {
            removeTabCache(item)
            return false
          }
        })
        await go(target)
      }
    }
    async function closeRightTab(tab: MineTabbar) {
      const target = findTab(tab)
      if (!target) return
      const index = tabList.value.findIndex(item => getTabKey(item) === getTabKey(target))

      if (index !== -1) {
        tabList.value = tabList.value.filter((item: MineTabbar, idx: number) => {
          if (idx <= index || item.affix === true) {
            return true
          }
          else {
            removeTabCache(item)
            return false
          }
        })
        await go(target)
      }
    }

    function closeCurrentTab() {
      const current = getCurrentTab()
      if (current) return closeTab(current)
    }

    function getCurrentTab() {
      return tabList.value.find(isCurrentTab)
    }

    function changeTabTitle(title: string, tab: MineTabbar | null = null) {
      const target = tab ? findTab(tab) : getCurrentTab()
      if (!target) return
      target.title = target.tabTitle || title
      delete target.i18n
      useSettingStore().setTitle(target.title)
      storage()
    }

    function clearTab() {
      tabList.value = [defaultTab.value]
      keepAliveStore.clean()
      iframeKeepLiveStore.clean()
    }

    function storage() {
      set('tabList', tabList.value)
    }

    async function go(item?: MineTabbar) {
      if (!item) return
      // 标签对象在组内导航时原地更新；收藏项仍须打开它保存的具体子页。
      await router.replace(item.fullPath || item.path)
    }

    return {
      tabList,
      defaultTab,
      go,
      initTab,
      normalizeTabs,
      getTabKey,
      isCurrentTab,
      changeTabTitle,
      affixTab,
      cancelAffixTab,
      maxSizeTab,
      exitMaxSizeTab,
      refreshTab,
      addTab,
      closeTab,
      closeOtherTab,
      closeLeftTab,
      closeRightTab,
      getCurrentTab,
      closeCurrentTab,
      clearTab,
    }
  },
)

export default useTabStore
